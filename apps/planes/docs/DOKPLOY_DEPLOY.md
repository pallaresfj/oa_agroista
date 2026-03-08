# Deploy en Dokploy (VPS)

Esta guía deja `gestionplanes` listo para Dokploy usando `docker-compose.dokploy.yml`.

## 1) Archivos preparados

- `Dockerfile`: build de producción (PHP 8.4 + Apache + assets Vite).
- `.dockerignore`: reduce contexto de build y evita subir secretos locales.
- `docker/entrypoint.sh`: prepara permisos y `storage:link`.
- `docker-compose.dokploy.yml`: servicios `web`, `queue`, `scheduler`, `redis`.
- `.env.dokploy.example`: plantilla de variables para Dokploy.

## 2) Crear la app en Dokploy

1. Crear proyecto/app nueva en Dokploy.
2. Conectar el repositorio Git de `gestionplanes`.
3. Seleccionar despliegue por `Docker Compose`.
4. Indicar archivo compose: `docker-compose.dokploy.yml`.
5. Servicio público (HTTP): `web`, puerto `80`.

## 3) Variables de entorno (Dokploy)

Copiar variables de `.env.dokploy.example` al panel de variables de Dokploy y configurar valores reales.

Mínimos obligatorios:

- `APP_KEY` (generar con: `php -r "echo 'base64:'.base64_encode(random_bytes(32)).PHP_EOL;"`)
- `APP_URL`
- `DB_*`
- `SSO_*` (si el login SSO está activo)

Recomendado para producción:

- `APP_ENV=production`
- `APP_DEBUG=false`
- `LOG_CHANNEL=stderr`
- `QUEUE_CONNECTION=redis`
- `CACHE_STORE=redis`
- `SESSION_DRIVER=redis`
- `SESSION_SECURE_COOKIE=true`

Importante para cookies/sesión:

- `SESSION_DOMAIN` debe quedar vacío (`SESSION_DOMAIN=`) para usar el host actual.
- No uses el literal `null` (`SESSION_DOMAIN=null`) porque rompe persistencia de sesión en algunos entornos.

## 4) Primer despliegue

Antes de ejecutar cachés, valida señal técnica en el servicio `web`:

```bash
printenv APP_URL SESSION_DOMAIN SESSION_SECURE_COOKIE APP_ENV APP_DEBUG
php artisan migrate:status
php artisan route:list --path=livewire
php artisan route:list --path=admin
```

Después del primer deploy, correr una sola vez en el servicio `web`:

```bash
php artisan migrate --force
php artisan filament:assets
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Si necesitas importar una BD existente, importa primero el `.sql` y luego ejecuta solo:

```bash
php artisan migrate --force
```

## 5) Actualizaciones

En cada release:

1. Dokploy hace `build` y recrea contenedores.
2. Ejecutar en `web`:

```bash
php artisan migrate --force
php artisan filament:assets
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

No necesitas `queue:restart` manual: el contenedor `queue` se recrea en cada despliegue.

## 6) Salud y verificación

- Health endpoint: `GET /up`
- Login panel: `/admin/login`
- Panel: `/admin`
- Público: `/planes`, `/centers`

Chequeo post-login del panel:

1. Abrir DevTools > Network en `/admin`.
2. Confirmar que las peticiones `/livewire/*` (por ejemplo `/livewire/update`) respondan `200`.
3. Si hay `419/401/500`, revisar variables de sesión y logs de `web`.

Verificar logs en Dokploy:

- `web`: errores HTTP y app.
- `queue`: jobs fallidos.
- `scheduler`: ejecución por minuto de `schedule:run`.

## 7) Si usarás Redis externo

Edita `docker-compose.dokploy.yml`:

1. Elimina servicio `redis`.
2. Elimina `depends_on.redis` de `web`, `queue`, `scheduler`.
3. Ajusta `REDIS_HOST` al hostname externo.

## 8) Notas de seguridad

- Nunca subas `.env` real al repo.
- No ejecutes `php artisan migrate:fresh` en producción.
- Mantén volúmenes persistentes para:
  - `/var/www/html/storage`
  - `/var/www/html/bootstrap/cache`
  - `/data` (si usas redis del compose)
