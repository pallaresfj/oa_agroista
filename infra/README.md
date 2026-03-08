# agroista/infra

Institution-level deployment templates for Dokploy.

## Single-institution stack template

- `docker-compose.institucion-template.yml`
- Services: `auth`, `planes`, `asistencia`, `silo`, `mysql`, `redis`
- DB isolation: one MySQL server with one DB per app (`db_auth`, `db_planes`, `db_asistencia`, `db_silo`)
- Redis isolation: app-level prefixes (`auth_`, `planes_`, `asistencia_`, `silo_`)

## Replication flow to another institution

1. Fork the 6 repositories (`auth`, `planes`, `asistencia`, `silo`, `core`, `infra`).
2. Create a new Dokploy project for the institution.
3. Apply this compose template with institution-specific env values.
4. Provision 4 databases and distinct DB users.
5. Run migrations and seeders per service.
6. Register OAuth clients in `auth` for the 3 client apps.

