# Runbook Monorepo -> Dokploy (Hostinger VPS)

Este runbook estandariza el despliegue del ecosistema `oa_agroista` en Dokploy con 4 apps separadas (`auth`, `planes`, `asistencia`, `silo`) y servicios de datos compartidos (`mysql`, `redis`).

## Topologia objetivo

- 4 aplicaciones Dokploy (una por app Laravel).
- 1 servicio MySQL compartido.
- 1 servicio Redis compartido.
- 1 base de datos y 1 usuario MySQL por app:
  - `db_auth` / `auth_user`
  - `db_planes` / `planes_user`
  - `db_asistencia` / `asistencia_user`
  - `db_silo` / `silo_user`
- Subdominios del mismo dominio raiz:
  - `auth.<dominio>`
  - `planes.<dominio>`
  - `asistencia.<dominio>`
  - `silo.<dominio>`

## Archivos operativos por app

- `apps/auth/docker-compose.dokploy.yml`
- `apps/auth/.env.dokploy.example`
- `apps/planes/docker-compose.dokploy.yml`
- `apps/planes/.env.dokploy.example`
- `apps/asistencia/docker-compose.dokploy.yml`
- `apps/asistencia/.env.dokploy.example`
- `apps/silo/docker-compose.dokploy.yml`
- `apps/silo/.env.dokploy.example`

## Preflight tecnico (antes de Dokploy)

Ejecutar desde la raiz del repo:

```bash
set -e

for app in auth planes asistencia silo; do
  echo "===> composer validate: $app"
  (cd apps/$app && composer validate --strict --no-check-publish)
done

for app in auth planes asistencia silo; do
  echo "===> docker build target app: $app"
  docker build --target app -f apps/$app/Dockerfile apps/$app
done
```

Pruebas Laravel (sqlite como CI):

```bash
set -e

for app in auth planes asistencia silo; do
  echo "===> tests: $app"
  (
    cd apps/$app
    php -r "file_exists('database/database.sqlite') || touch('database/database.sqlite');"
    APP_ENV=testing \
    APP_KEY=base64:$(php -r "echo base64_encode(random_bytes(32));") \
    DB_CONNECTION=sqlite \
    DB_DATABASE=database/database.sqlite \
    php artisan test
  )
done
```

## Configuracion en Dokploy

### 1) Crear servicios compartidos de datos

1. Crear servicio MySQL (`mysql-shared`) con volumen persistente.
2. Crear servicio Redis (`redis-shared`) con volumen persistente.
3. Crear las 4 bases de datos y usuarios dedicados en MySQL.

### 2) Crear apps del ecosistema

Para cada app (`auth`, `planes`, `asistencia`, `silo`):

1. Crear una app nueva en Dokploy conectada a este monorepo.
2. Modo de despliegue: `Docker Compose`.
3. Archivo compose:
   - `apps/auth/docker-compose.dokploy.yml`
   - `apps/planes/docker-compose.dokploy.yml`
   - `apps/asistencia/docker-compose.dokploy.yml`
   - `apps/silo/docker-compose.dokploy.yml`
   - Nota de red: estos compose se conectan explicitamente a `dokploy-network` para alcanzar servicios compartidos (MySQL/Redis) desde `web`, `queue` y `scheduler`.
4. Servicio publico HTTP: `web` puerto `80`.
5. Cargar variables desde `.env.dokploy.example` correspondiente y ajustar valores reales.

Variables obligatorias comunes:

- `APP_KEY`
- `APP_URL`
- `SUPERADMIN_EMAILS` (lista CSV de correos con acceso al panel `admin`)
- `DB_HOST` usando **Internal Host** del servicio MySQL en Dokploy (ej. `mysql-shared-xxxxxx`)
- `DB_PORT=3306`
- `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` segun app
- `REDIS_HOST` usando **Internal Host** del servicio Redis en Dokploy (ej. `redis-shared-xxxxxx`)
- `REDIS_PORT=6379`
- `REDIS_PREFIX` unico por app (`auth_`, `planes_`, `asistencia_`, `silo_`)
- `SESSION_DOMAIN=` (vacio, no usar literal `null`)
- `SESSION_SECURE_COOKIE=true`

Variables SSO obligatorias:

- En `auth`: `PLANES_*`, `ASISTENCIA_*`, `SILO_*`, `SSO_*`, `CORS_ALLOWED_ORIGINS`
- En clientes: `SSO_DISCOVERY_URL`, `SSO_ISSUER`, `SSO_CLIENT_ID`, `SSO_CLIENT_SECRET`, `SSO_REDIRECT_URI`, `AUTH_API_BASE`

### 3) Orden de despliegue

Desplegar en este orden:

1. `auth`
2. `planes`
3. `asistencia`
4. `silo`

### 4) Comandos one-time post deploy

### auth (servicio `web`)

```bash
php artisan migrate --force
php artisan db:seed --class=SuperAdminsSeeder --force
php artisan passport:keys --force
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Opcional bootstrap inicial:

```bash
php artisan db:seed --force
```

### planes / asistencia (servicio `web`)

```bash
php artisan migrate --force
php artisan filament:assets
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### silo (servicio `web`)

Con `SILO_BOOTSTRAP_ON_START=true`, `silo` ejecuta automaticamente en startup:

- `php artisan migrate --force`
- `php artisan db:seed --class=Database\Seeders\RolePermissionSeeder --force`
- `php artisan db:seed --class=Database\Seeders\AdminUserSeeder --force`

Comandos manuales (solo si necesitas forzar):

```bash
php artisan filament:assets
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## Validacion operativa

### 1) Salud base

- `GET /up` devuelve 200 en las 4 apps.
- Contenedores `web`, `queue`, `scheduler` en estado healthy/running.

### 2) Smoke SSO

1. Iniciar login desde `planes`.
2. Redirigir a `auth` y completar autenticacion.
3. Volver a callback de `planes` sin error de `state`, `issuer` o `audience`.
4. Repetir para `asistencia` y `silo`.

### 3) Logout frontchannel

1. Cerrar sesion desde `auth`.
2. Verificar cierre de sesion local en clientes (`planes`, `asistencia`, `silo`).

### 4) Scheduler y queue

- `asistencia`: verificar ejecucion de `attendance:generate-absences` segun configuracion.
- `silo`: verificar ejecucion de `drive:sync-unclassified`.
- Revisar logs de `queue` para jobs fallidos.

## CI/CD por webhook

Configurar en GitHub Secrets del monorepo:

- `DOKPLOY_WEBHOOK_AUTH`
- `DOKPLOY_WEBHOOK_PLANES`
- `DOKPLOY_WEBHOOK_ASISTENCIA`
- `DOKPLOY_WEBHOOK_SILO`

`deploy-monorepo.yml` disparara el webhook de cada app segun rutas modificadas.

## Nota sobre plantillas de infra

- `infra/docker-compose.institucion-template.yml` queda como plantilla para **imagenes preconstruidas (registry/GHCR)**.
- Para estrategia actual de monorepo + build en Dokploy, usar los compose por app en `apps/*/docker-compose.dokploy.yml`.
