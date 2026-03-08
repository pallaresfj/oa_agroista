# Implementation Status - Single-Institution Replicable Architecture

Date: 2026-03-07

## Implemented

### 1) Shared package scaffold (`agroista/core`)

- `core/composer.json`
- OIDC client: `core/src/Sso/OidcClient.php`
- Local provisioning strategy: `core/src/Auth/LocalUserProvisioner.php`
- Institution API client + cache context:
  - `core/src/Institution/InstitutionConfigClient.php`
  - `core/src/Institution/InstitutionContext.php`
- Provider + package config:
  - `core/src/CoreServiceProvider.php`
  - `core/config/agroista-core.php`

### 2) `auth` as configurable identity hub

Implemented in `apps/auth`:

- New data model:
  - `institutions`
  - `institution_settings`
  - `ecosystem_apps`
  - `ecosystem_app_redirect_uris`
  - extension for `user_app_access` (`ecosystem_app_id`)
- New API routes:
  - `GET /api/ecosystem/institution`
  - `PUT /api/ecosystem/institution`
  - `GET /api/ecosystem/apps`
- New Filament resources:
  - Institutions
  - Institution settings
  - Ecosystem apps
- OIDC claims now include `institution_code` in ID token / userinfo.

### 3) Client apps federation minimum

Implemented in `apps/planes`, `apps/silo`, `apps/asistencia`:

- Users federation migration:
  - `auth_subject` (unique nullable)
  - `institution_code` (indexed)
  - `last_sso_login_at`
- SSO callback/session-check now persist:
  - `auth_subject`
  - `institution_code`
  - `last_sso_login_at`
- Guardrail: if local `auth_subject` exists and differs from incoming `sub`, session is rejected.

### 4) Infra template for institutional replication

- `infra/docker-compose.institucion-template.yml`
- `infra/README.md`

### 5) Monorepo migration completed

- `oa_auth`, `oa_planes`, `oa_asistencia`, `oa_silo` imported into:
  - `apps/auth`
  - `apps/planes`
  - `apps/asistencia`
  - `apps/silo`
- Migration executed with `git subtree` preserving history.
- Single Git repository is now `oa_agroista`.

## Pending for next iteration

- Define path strategy for `agroista/core` in monorepo (`core` source of truth).
- CI/CD path-based workflows per app in monorepo.
- Full RBAC migration for `apps/silo` and `apps/asistencia` to Shield/Spatie.
- Institutional replication playbook from monorepo template.
