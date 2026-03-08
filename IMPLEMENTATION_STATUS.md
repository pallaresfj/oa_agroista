# Implementation Status - Single-Institution Replicable Architecture

Date: 2026-03-07

## Implemented

### 1) Shared package scaffold (`agroista/core`)

- `agroista/core/composer.json`
- OIDC client: `src/Sso/OidcClient.php`
- Local provisioning strategy: `src/Auth/LocalUserProvisioner.php`
- Institution API client + cache context:
  - `src/Institution/InstitutionConfigClient.php`
  - `src/Institution/InstitutionContext.php`
- Provider + package config:
  - `src/CoreServiceProvider.php`
  - `config/agroista-core.php`

### 2) `auth` as configurable identity hub

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

Implemented in `gestionplanes`, `silo`, `teachingassistance`:

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

- `agroista/infra/docker-compose.institucion-template.yml`
- `agroista/infra/README.md`

## Validation run

- PHP syntax checks passed for all new/changed files.
- Route checks passed:
  - auth API ecosystem routes registered
  - auth Filament resources registered
  - SSO callback routes intact in all 3 client apps

## Pending for next iteration

- Wire `agroista/core` as Composer dependency in each app repository (target: VCS package, not local path).
- Enforce `client_credentials` scope middleware for ecosystem API reads/writes.
- Full RBAC migration for `silo` and `teachingassistance` to Shield/Spatie.
- CI/CD reusable workflows and institutional fork governance automation.

