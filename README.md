# oa_agroista Monorepo

Monorepo del ecosistema OA con una sola institucion por despliegue.

## Estructura

- `apps/auth`: Identity Provider (OAuth2/OIDC + Socialite Google)
- `apps/planes`: App de planes academicos
- `apps/asistencia`: App de asistencia
- `apps/silo`: App de gestion agropecuaria
- `core`: Paquete compartido `agroista/core`
- `infra`: Plantillas de despliegue Dokploy/Hostinger

## Regla operativa

Todo cambio del ecosistema se hace en este repositorio.
No trabajar en carpetas externas `oa_auth`, `oa_planes`, `oa_asistencia`, `oa_silo` para evitar divergencia.

## Flujo base

1. Crear rama en este repo.
2. Cambiar solo en `apps/*`, `core` o `infra`.
3. Validar pruebas por app modificada.
4. Push y PR en este repo.

## CI/CD monorepo (GitHub Actions)

- CI por rutas: `.github/workflows/ci-monorepo.yml`
- Deploy por rutas: `.github/workflows/deploy-monorepo.yml`
- Workflows reutilizables:
  - `.github/workflows/reusable-app-ci.yml`
  - `.github/workflows/reusable-app-deploy.yml`

### Comportamiento

- Si cambias `apps/auth/**` corre CI/deploy de `auth`.
- Si cambias `apps/planes/**` corre CI/deploy de `planes`.
- Si cambias `apps/asistencia/**` corre CI/deploy de `asistencia`.
- Si cambias `apps/silo/**` corre CI/deploy de `silo`.
- Si cambias `core/**` o workflows, corre CI/deploy de las 4 apps.

### Secrets requeridos para deploy

- `DOKPLOY_WEBHOOK_AUTH`
- `DOKPLOY_WEBHOOK_PLANES`
- `DOKPLOY_WEBHOOK_ASISTENCIA`
- `DOKPLOY_WEBHOOK_SILO`

Cada secret debe ser la URL del webhook de deploy del servicio en Dokploy.

## Notas de despliegue

- Cada app se despliega como servicio independiente en Dokploy.
- Cada app mantiene su base de datos propia.
- Integracion entre apps solo via API y tokens de `auth`.
