# Runbook: Corte a VPS Nuevo en Dokploy (5 apps, sin migraciones)

Este documento es para desplegar `auth`, `planes`, `asistencia`, `lectura` y `silo` en un VPS nuevo con Dokploy, usando bases de datos **ya migradas**.

Regla principal:

- **No ejecutar migraciones ni seeders durante el primer arranque**.

## 0) Qué debes tener antes de iniciar

1. Dokploy funcionando en el VPS nuevo.
2. Repo `oa_agroista` conectado en Dokploy.
3. MySQL y Redis creados en Dokploy (`running`).
4. Bases y usuarios ya existentes:
   - `db_auth` / `auth_user`
   - `db_planes` / `planes_user`
   - `db_asistencia` / `asistencia_user`
   - `db_lectura` / `lectura_user`
   - `db_silo` / `silo_user`
5. Variables productivas actuales (sobre todo `APP_KEY` y credenciales SSO).

## 1) Preparar ventana de corte

1. Baja TTL DNS a `300` (idealmente 24h antes).
2. Define ventana corta de mantenimiento.
3. Respaldar `.env` productivos del VPS actual (las 5 apps).

Checklist rápido:

- [ ] TTL en 300
- [ ] Ventana definida
- [ ] Backup de `.env` completado

## 2) Congelar el entorno viejo (antes del deploy nuevo)

En el VPS viejo:

1. Detén workers (`queue` y `scheduler`) para evitar doble ejecución de jobs.
2. Activa mantenimiento temporal en front si aplica.

## 3) Verificar servicios compartidos en el VPS nuevo

En Dokploy:

1. Verifica MySQL `running`.
2. Verifica Redis `running`.
3. Copia el **Internal Host** real de ambos servicios (se usa en `DB_HOST` y `REDIS_HOST`).
4. Verifica que cada app puede usar su DB/usuario.

## 4) Crear las 5 apps en Dokploy

Crea una app por servicio, todas desde el mismo monorepo:

- `auth` -> `apps/auth/docker-compose.dokploy.yml`
- `planes` -> `apps/planes/docker-compose.dokploy.yml`
- `asistencia` -> `apps/asistencia/docker-compose.dokploy.yml`
- `lectura` -> `apps/lectura/docker-compose.dokploy.yml`
- `silo` -> `apps/silo/docker-compose.dokploy.yml`

Parámetros comunes:

- Tipo: `Docker Compose from repository`
- Servicio público: `web` puerto `80`
- Dominio por app:
  - `auth.<dominio>`
  - `planes.<dominio>`
  - `asistencia.<dominio>`
  - `lectura.<dominio>`
  - `silo.<dominio>`

## 5) Cargar variables por app (sin placeholders)

Usa como base:

- `apps/auth/.env.dokploy.example`
- `apps/planes/.env.dokploy.example`
- `apps/asistencia/.env.dokploy.example`
- `apps/lectura/.env.dokploy.example`
- `apps/silo/.env.dokploy.example`

Reglas críticas:

1. Copia **exactamente** los `APP_KEY` productivos existentes (no regenerar).
2. Copia `SSO_CLIENT_ID`, `SSO_CLIENT_SECRET`, `SSO_FRONTCHANNEL_*` reales.
3. En `auth`, completa `PLANES_*`, `ASISTENCIA_*`, `LECTURA_*`, `SILO_*` con valores reales.
4. Reemplaza `DB_HOST` y `REDIS_HOST` con el Internal Host real de Dokploy.
5. Mantén `SESSION_DOMAIN=` vacío.
6. Mantén `SESSION_SECURE_COOKIE=true`.
7. No dejes placeholders (`<dominio>`, `change-me`, etc.).

Tip: valida tus archivos con el script:

```bash
./infra/scripts/check_dokploy_env.sh auth /ruta/al/auth.env
./infra/scripts/check_dokploy_env.sh planes /ruta/al/planes.env
./infra/scripts/check_dokploy_env.sh asistencia /ruta/al/asistencia.env
./infra/scripts/check_dokploy_env.sh lectura /ruta/al/lectura.env
./infra/scripts/check_dokploy_env.sh silo /ruta/al/silo.env
```

## 6) Desactivar bootstrap para evitar migraciones/seeders

Antes del primer deploy en Dokploy, fija:

- `PLANES_BOOTSTRAP_ON_START=false`
- `ASISTENCIA_BOOTSTRAP_ON_START=false`
- `LECTURA_BOOTSTRAP_ON_START=false`
- `SILO_BOOTSTRAP_ON_START=false`

Y en `auth`:

- No ejecutar manualmente `php artisan migrate`.
- No ejecutar manualmente `php artisan db:seed`.

## 7) Desplegar en orden

Orden recomendado:

1. `auth`
2. `planes`
3. `asistencia`
4. `lectura`
5. `silo`

En cada app, espera estado:

- `web` -> running/healthy
- `queue` -> running
- `scheduler` -> running

## 8) Validación técnica post-deploy

Desde tu máquina:

```bash
./infra/scripts/check_dokploy_health.sh <dominio>
```

Ejemplo:

```bash
./infra/scripts/check_dokploy_health.sh midominio.com
```

Valida también en logs de Dokploy:

- Sin ejecuciones de `migrate`.
- Sin errores de esquema SQL.

## 9) Prueba SSO end-to-end (manual)

1. Login desde `planes`.
2. Login desde `asistencia`.
3. Login desde `lectura`.
4. Login desde `silo`.
5. En cada caso debe redirigir a `auth` y volver al callback correcto.
6. Probar logout y confirmar cierre de sesión en clientes.

## 10) Corte DNS

1. Actualiza A/AAAA o proxy a IP del VPS nuevo.
2. Monitorea 30-60 min:
   - logs `web/queue/scheduler`
   - jobs fallidos
   - picos 4xx/5xx

## 11) Contingencia (rollback)

Si hay incompatibilidad de esquema:

1. **No** ejecutes migraciones en caliente.
2. Revierte DNS al VPS anterior.
3. Compara versión de código vs versión de esquema.
4. Prepara corrección controlada y nueva ventana.

## 12) Checklist final

- [ ] 5 apps creadas en Dokploy con compose correcto
- [ ] Variables productivas cargadas sin placeholders
- [ ] `*_BOOTSTRAP_ON_START=false` en clientes
- [ ] Sin `migrate/seed` manual en `auth`
- [ ] `/up` OK en 5 apps
- [ ] OIDC discovery OK en `auth`
- [ ] Login/logout SSO OK en 4 clientes
- [ ] DNS apuntando al VPS nuevo
- [ ] Monitoreo sin errores críticos
