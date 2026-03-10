# Deploy de `silo` en Dokploy (Monorepo)

Esta guia aplica al monorepo `oa_agroista`.

Flujo completo del ecosistema:

- `infra/DOKPLOY_MONOREPO_RUNBOOK.md`

## Archivos de esta app

- `apps/silo/Dockerfile`
- `apps/silo/docker-compose.dokploy.yml`
- `apps/silo/.env.dokploy.example`

## Configuracion en Dokploy (app `silo`)

1. Crear app en Dokploy conectada al monorepo.
2. Seleccionar despliegue por Docker Compose.
3. Archivo compose: `apps/silo/docker-compose.dokploy.yml`.
4. Servicio publico: `web` puerto `80`.
5. Variables: copiar desde `apps/silo/.env.dokploy.example` y ajustar valores reales.

Variables minimas:

- `APP_KEY`
- `APP_URL=https://silo.<dominio>`
- `DB_HOST=mysql-shared`
- `DB_DATABASE=db_silo`
- `DB_USERNAME=silo_user`
- `DB_PASSWORD=...`
- `REDIS_HOST=redis-shared`
- `REDIS_PREFIX=silo_`
- `SSO_ISSUER=https://auth.<dominio>`
- `SSO_DISCOVERY_URL=https://auth.<dominio>/.well-known/openid-configuration`
- `AUTH_API_BASE=https://auth.<dominio>/api/ecosystem`
- `GOOGLE_DRIVE_*` y `GOOGLE_WORKSPACE_*` (si usas integraciones Google)

## Primer despliegue (one-time)

Ejecutar en servicio `web`:

```bash
php artisan migrate --force
php artisan filament:assets
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## Verificacion rapida

- `GET /up` responde 200.
- Login SSO completa callback correctamente.
- Revisar logs de `scheduler` para `drive:sync-unclassified`.
