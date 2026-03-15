# Deploy de `planes` en Dokploy (Monorepo)

Esta guia aplica al monorepo `oa_agroista`.

Para el flujo completo de las 4 apps revisar primero:

- `infra/DOKPLOY_MONOREPO_RUNBOOK.md`

## Archivos de esta app

- `apps/planes/Dockerfile`
- `apps/planes/docker-compose.dokploy.yml`
- `apps/planes/.env.dokploy.example`

## Configuracion en Dokploy (app `planes`)

1. Crear app en Dokploy conectada al monorepo.
2. Seleccionar despliegue por Docker Compose.
3. Archivo compose: `apps/planes/docker-compose.dokploy.yml`.
4. Servicio publico: `web` puerto `80`.
5. Variables: copiar desde `apps/planes/.env.dokploy.example` y ajustar valores reales.

Variables minimas:

- `APP_KEY`
- `APP_URL=https://planes.<dominio>`
- `DB_HOST=mysql-shared`
- `DB_DATABASE=db_planes`
- `DB_USERNAME=planes_user`
- `DB_PASSWORD=...`
- `REDIS_HOST=redis-shared`
- `REDIS_PREFIX=planes_`
- `SSO_ISSUER=https://auth.<dominio>`
- `SSO_DISCOVERY_URL=https://auth.<dominio>/.well-known/openid-configuration`
- `AUTH_API_BASE=https://auth.<dominio>/api/ecosystem`
- `SSO_SUPPORT_EMAILS=soporte@<dominio>` (o una lista separada por comas)
- `PLANES_BOOTSTRAP_ON_START=true`

## Bootstrap automatico al arrancar

Con `PLANES_BOOTSTRAP_ON_START=true`, el contenedor `web` ejecuta automaticamente:

- `php artisan migrate --force` (con reintentos)
- `php artisan shield:generate --all --panel=admin --option=permissions --no-interaction`
- `php artisan db:seed --class=Database\\Seeders\\RolePermissionSafeSeeder --force`

Esto evita comandos manuales para dejar permisos listos en cada deploy.

## Fallback manual (solo si necesitas depurar)

Ejecutar en `web`:

```bash
php artisan migrate --force
php artisan shield:generate --all --panel=admin --option=permissions --no-interaction
php artisan db:seed --class=Database\\Seeders\\RolePermissionSafeSeeder --force
```

## Verificacion rapida

- `GET /up` responde 200.
- Login SSO desde `/sso/login` completa callback correctamente.
- Livewire en panel `/admin` responde 200 en requests `/livewire/*`.
