# Deploy de `auth` en Dokploy (Monorepo)

Esta guia aplica al monorepo `oa_agroista`.

Flujo completo del ecosistema:

- `infra/DOKPLOY_MONOREPO_RUNBOOK.md`

## Archivos de esta app

- `apps/auth/Dockerfile`
- `apps/auth/docker-compose.dokploy.yml`
- `apps/auth/.env.dokploy.example`

## Configuracion en Dokploy (app `auth`)

1. Crear app en Dokploy conectada al monorepo.
2. Seleccionar despliegue por Docker Compose.
3. Archivo compose: `apps/auth/docker-compose.dokploy.yml`.
4. Servicio publico: `web` puerto `80`.
5. Variables: copiar desde `apps/auth/.env.dokploy.example` y ajustar valores reales.

Variables minimas:

- `APP_KEY`
- `APP_URL=https://auth.<dominio>`
- `DB_HOST=mysql-shared`
- `DB_DATABASE=db_auth`
- `DB_USERNAME=auth_user`
- `DB_PASSWORD=...`
- `REDIS_HOST=redis-shared`
- `REDIS_PREFIX=auth_`
- `GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET`, `GOOGLE_REDIRECT_URI`
- `PLANES_*`, `ASISTENCIA_*`, `SILO_*`

## Primer despliegue (one-time)

Ejecutar en servicio `web`:

```bash
php artisan migrate --force
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

## Verificacion rapida

- `GET /up` responde 200.
- Discovery OIDC: `/.well-known/openid-configuration` responde 200.
- Login Google y callback funcionan en `auth`.
