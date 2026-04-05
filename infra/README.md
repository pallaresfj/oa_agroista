# agroista/infra

Institution-level deployment templates for Dokploy.

## Monorepo Dokploy runbook (recommended)

- `DOKPLOY_MONOREPO_RUNBOOK.md`
- Topology: 4 apps separated (`auth`, `planes`, `asistencia`, `silo`) + shared `mysql` and `redis`.
- Build strategy: each app builds from this monorepo using `apps/*/docker-compose.dokploy.yml`.

## Migrated DB cutover runbook (no migrations)

- `DOKPLOY_MIGRATED_DB_CUTOVER_RUNBOOK.md`
- Scope: 5 apps (`auth`, `planes`, `asistencia`, `lectura`, `silo`) on a new VPS with Dokploy.
- Rule: do not run `migrate`/`db:seed` on first startup when DB schemas are already migrated.
- Validation helpers:
  - `scripts/check_dokploy_env.sh` (validate app env files before deploy)
  - `scripts/check_dokploy_health.sh` (check `/up` + auth OIDC discovery after deploy)

## Prebuilt-images template (legacy/optional)

- `docker-compose.institucion-template.yml`
- Purpose: deployments that consume prebuilt registry images (`ghcr.io/...`).
- Services: `auth`, `planes`, `asistencia`, `silo`, `mysql`, `redis`
- DB isolation: one MySQL server with one DB per app (`db_auth`, `db_planes`, `db_asistencia`, `db_silo`)
- Redis isolation: app-level prefixes (`auth_`, `planes_`, `asistencia_`, `silo_`)

## Replication flow to another institution

1. Fork this monorepo (`oa_agroista`).
2. Create a new Dokploy project for the institution.
3. Follow `DOKPLOY_MONOREPO_RUNBOOK.md` using per-app compose files.
4. Provision 4 databases and distinct DB users in shared MySQL.
5. Set app-specific Redis prefixes in shared Redis.
6. Run migrations and bootstrap commands per service.
