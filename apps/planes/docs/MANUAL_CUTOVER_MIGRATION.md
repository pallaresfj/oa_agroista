# Migracion Manual `planes` -> `oa-planes` (Cutover + Rollback)

Runbook operativo para migracion **offline** con ventana de mantenimiento, carga por `mysqldump` y rollback por snapshot + reversa de trafico.

## Alcance funcional

Tablas incluidas en migracion:

- `migrations`
- `users`
- `roles`
- `permissions`
- `role_has_permissions`
- `model_has_roles`
- `model_has_permissions`
- `school_profiles`
- `plans`
- `plan_user`
- `subjects`
- `subject_user`
- `topics`
- `rubrics`
- `centers`
- `teachers`
- `students`
- `activities`
- `budgets`

Tablas excluidas (tecnicas):

- `sessions`
- `cache`
- `cache_locks`
- `jobs`
- `job_batches`
- `failed_jobs`
- `password_reset_tokens`

Media incluida:

- `storage/app/public/plan-cover`
- `storage/app/public/center-cover`

## Artefactos de soporte en este repo

- Script principal: `apps/planes/scripts/cutover/manual-cutover.sh`
- Variables ejemplo: `apps/planes/scripts/cutover/runbook.env.example`
- Patch SQL users: `apps/planes/scripts/cutover/sql/patch_users_columns.sql`
- Validacion orfanos: `apps/planes/scripts/cutover/sql/orphan_checks.sql`
- Lista tablas migradas: `apps/planes/scripts/cutover/migrated_tables.txt`

## Preparacion (pre-corte)

1. Copia variables base y completa credenciales reales fuera de git:

```bash
cp apps/planes/scripts/cutover/runbook.env.example /secure/path/planes-cutover.env
```

2. Carga variables en shell de operacion:

```bash
set -a
source /secure/path/planes-cutover.env
set +a
```

3. Confirma conectividad DB origen/destino (desde la maquina operativa) y acceso a `oa-planes`.

4. Activa mantenimiento en `oa-planes` antes de tocar datos.

5. Genera backup de seguridad del destino actual (DB + media):

```bash
bash apps/planes/scripts/cutover/manual-cutover.sh backup-target
```

## Ejecucion de corte (ventana)

### 1) Congelar origen

1. Habilita mantenimiento en `planes` actual.
2. Bloquea acceso de usuarios/escrituras.

### 2) Preparar DB de corte en destino

```bash
bash apps/planes/scripts/cutover/manual-cutover.sh create-cutover-db
```

### 3) Dump funcional desde origen

```bash
bash apps/planes/scripts/cutover/manual-cutover.sh dump-source
```

Este paso genera `planes_cutover_*.sql` en `BACKUP_DIR`.

### 4) Restore en destino

```bash
bash apps/planes/scripts/cutover/manual-cutover.sh restore-target
```

Notas:

- El script exige DB de corte vacia por seguridad.
- Para forzar import en DB no vacia: `ALLOW_NONEMPTY_CUTOVER_DB=true`.

### 5) Patch de esquema users (idempotente)

```bash
bash apps/planes/scripts/cutover/manual-cutover.sh patch-users-schema
```

Patch aplicado:

- `users.auth_subject` (nullable + unique)
- `users.last_sso_login_at` (nullable)

### 6) Bootstrap app sobre DB de corte

```bash
bash apps/planes/scripts/cutover/manual-cutover.sh bootstrap-target
```

Internamente ejecuta:

- `php artisan migrate --force`
- `php artisan shield:generate --all --panel=admin --option=permissions --no-interaction` (si existe comando)
- `php artisan db:seed --class=Database\Seeders\RolePermissionSafeSeeder --force`

### 7) Sincronizacion de media

Configura `MEDIA_SYNC_SOURCE_DIRS` en tu env (rutas separadas por `:`) y ejecuta:

```bash
bash apps/planes/scripts/cutover/manual-cutover.sh sync-media
```

### 8) Validaciones Go/No-Go

Conteos source vs target:

```bash
bash apps/planes/scripts/cutover/manual-cutover.sh validate-counts
```

Orfanos referenciales:

```bash
bash apps/planes/scripts/cutover/manual-cutover.sh validate-orphans
```

Checklist final:

```bash
bash apps/planes/scripts/cutover/manual-cutover.sh go-nogo-checklist
```

Validaciones funcionales obligatorias:

- `GET /up` responde `200`
- Login SSO en `/admin`
- Usuario soporte accede al panel
- CRUD minimo: Area, Asignatura, Centro
- Faltantes de media aceptados explicitamente

## Cutover de dominio final (`planes.*`)

Si Go/No-Go es exitoso:

1. Actualiza cliente OAuth en `auth` con URIs finales:
- `/sso/callback`
- `/sso/session-check/callback`
- `/sso/frontchannel-logout`

2. Actualiza variables `APP_URL`, `SSO_*`, `AUTH_API_BASE` en `planes`.
3. Cambia DNS/routing al nuevo servicio.
4. Redeploy en orden:
- `auth`
- `planes`

Referencia de entorno Dokploy: `infra/DOKPLOY_AUTH_PLANES_PASO_A_PASO.md` (seccion de cutover final).

## Rollback

### Si falla antes de DNS

- Mantener trafico en app antigua.
- Cancelar corte.
- Revisar inconsistencia y repetir ventana.

### Si falla despues de DNS

1. Revertir DNS/routing al `planes` anterior.
2. Restaurar configuracion OAuth/redirects previa en `auth`.
3. Restaurar snapshot DB/media del destino.
4. Confirmar login y operacion estable en app anterior.

## Comandos utiles

Listar tablas funcionales definidas para migracion:

```bash
bash apps/planes/scripts/cutover/manual-cutover.sh list-tables
```

Ver ayuda del script:

```bash
bash apps/planes/scripts/cutover/manual-cutover.sh help
```
