# Despliegue de `lectura` en Dokploy (Paso a Paso)

Esta guia es para desplegar la app `lectura` del monorepo `oa_agroista` en un VPS con Dokploy, usando:

- MySQL compartido ya existente.
- Base de datos nueva: `db_lectura`.
- Usuario nuevo: `lectura_user`.

## 1) Antes de empezar

Necesitas tener:

1. Acceso al panel web de Dokploy.
2. Acceso al repositorio `oa_agroista`.
3. Servicio MySQL ya creado y en estado running.
4. (Opcional) Servicio Redis compartido.
5. Dominio o subdominio para lectura, por ejemplo: `lectura.tudominio.com`.

Archivos usados por esta app:

- `apps/lectura/docker-compose.dokploy.yml`
- `apps/lectura/.env.dokploy.example`
- `apps/lectura/Dockerfile`
- `apps/lectura/docker/entrypoint.sh`

## 2) Crear DB y usuario dentro del contenedor MySQL

En Dokploy:

1. Abre el servicio MySQL compartido.
2. Entra a `Open Terminal`.
3. Ejecuta:

```bash
mysql -u root
```

4. Pega y ejecuta este SQL:

```sql
CREATE DATABASE IF NOT EXISTS db_lectura
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

CREATE USER IF NOT EXISTS 'lectura_user'@'%' IDENTIFIED BY 'CAMBIAR_PASSWORD_LECTURA';
GRANT ALL PRIVILEGES ON db_lectura.* TO 'lectura_user'@'%';
FLUSH PRIVILEGES;

SHOW DATABASES LIKE 'db_lectura';
SELECT user, host FROM mysql.user WHERE user = 'lectura_user';
```

Si `lectura_user` ya existia y quieres cambiarle clave:

```sql
ALTER USER 'lectura_user'@'%' IDENTIFIED BY 'NUEVA_PASSWORD_LECTURA';
FLUSH PRIVILEGES;
```

## 3) Crear la app `lectura` en Dokploy

1. `Create Service` -> `Application`.
2. Conecta el repo `oa_agroista`.
3. Tipo de despliegue: **Docker Compose from repository**.
4. `Compose path`: `apps/lectura/docker-compose.dokploy.yml`.
5. Servicio publico: `web` puerto `80`.
6. Asigna dominio y activa HTTPS.

## 4) Variables de entorno (Environment)

En Dokploy, copia como base `apps/lectura/.env.dokploy.example` y completa valores reales.

Minimo obligatorio:

- `APP_KEY` (generada previamente).
- `APP_URL=https://lectura.<dominio>`
- `DB_HOST=<internal-host-del-mysql>`
- `DB_PORT=3306`
- `DB_DATABASE=db_lectura`
- `DB_USERNAME=lectura_user`
- `DB_PASSWORD=<password-real-de-lectura_user>`
- `SSO_DISCOVERY_URL=https://auth.<dominio>/.well-known/openid-configuration`
- `SSO_ISSUER=https://auth.<dominio>`
- `SSO_CLIENT_ID=<cliente-lectura>`
- `SSO_CLIENT_SECRET=<secreto-lectura>`
- `SSO_REDIRECT_URI=https://lectura.<dominio>/sso/callback`
- `AUTH_API_BASE=https://auth.<dominio>/api/ecosystem`

### APP_KEY

Generala una vez (en tu maquina local):

```bash
php -r "echo 'base64:'.base64_encode(random_bytes(32)).PHP_EOL;"
```

Luego pega ese valor en `APP_KEY`.

## 5) Sobre Redis (opcional)

Esta app puede funcionar sin Redis (solo con MySQL):

- `SESSION_DRIVER=file`
- `QUEUE_CONNECTION=database`
- `CACHE_STORE=database`

Si ya tienes Redis compartido en Dokploy, puedes usar:

- `SESSION_DRIVER=redis`
- `QUEUE_CONNECTION=redis`
- `CACHE_STORE=redis`
- `REDIS_HOST=<internal-host-del-redis>`

## 6) Primer deploy

1. Pulsa `Deploy`.
2. Espera a que queden running:
   - `web`
   - `queue`
   - `scheduler`

El `web` ejecuta bootstrap automatico al iniciar:

- `php artisan migrate --force`
- `php artisan shield:generate --all --panel=app --option=permissions --no-interaction`
- `php artisan db:seed --force`

Eso deja tablas, permisos, roles y datos base listos.

## 7) Verificaciones despues de desplegar

1. Salud de la app:
   - `https://lectura.<dominio>/up` debe responder 200.
2. Login:
   - `https://lectura.<dominio>/app/login` debe abrir la pantalla de acceso.
3. Logs:
   - Revisar que `web` no tenga errores de DB/SSO.
   - Revisar que `queue` y `scheduler` esten corriendo sin reinicios continuos.

## 8) Comandos de soporte (si algo falla)

En terminal del contenedor `web`:

```bash
cd /var/www/html
php artisan optimize:clear
php artisan migrate --force
php artisan db:seed --force
php artisan config:cache
```

## 9) Checklist final

- [ ] `db_lectura` existe.
- [ ] `lectura_user` existe y tiene permisos sobre `db_lectura`.
- [ ] `DB_HOST` apunta al **Internal Host** real del MySQL en Dokploy.
- [ ] `APP_KEY` configurada.
- [ ] SSO (`SSO_CLIENT_ID` / `SSO_CLIENT_SECRET`) configurado.
- [ ] `/up` responde correctamente.
- [ ] Se puede iniciar sesion en `/app/login`.
