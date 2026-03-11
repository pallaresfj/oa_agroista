# Despliegue Paso a Paso en Dokploy: `auth` + `planes`

Guia operativa desde cero para desplegar en Dokploy las apps del monorepo:

- `auth` (Identity Provider SSO)
- `planes` (cliente SSO)

Esta version ya viene adaptada con tus valores reales de infraestructura actual.

## 0) Datos reales usados en esta guia

- Proyecto Dokploy: `Ecosistema Agro`
- Environment: `production`
- Servicio MySQL: `mysql-shared`
- Internal Host MySQL actual: `mysql-shared-goni44`
- Servicio Redis: `redis-shared`
- Internal Host Redis actual: `redis-shared-f9kamn`
- BD auth: `db_auth` / usuario `auth_user`
- BD planes: `db_planes` / usuario `planes_user`
- Dominio institucional: `iedagropivijay.edu.co`
- Subdominio temporal auth: `oa-auth.iedagropivijay.edu.co`
- Subdominio temporal planes: `oa-planes.iedagropivijay.edu.co`
- Subdominio final auth: `auth.iedagropivijay.edu.co`
- Subdominio final planes: `planes.iedagropivijay.edu.co`

Nota: si Dokploy regenera el internal host al recrear servicios, actualiza `DB_HOST`/`REDIS_HOST`.

---

## 1) Prerrequisitos

1. VPS con Dokploy operativo.
2. Acceso al panel web de Dokploy.
3. Acceso al DNS del dominio.
4. Repo conectado: `github.com/pallaresfj/oa_agroista.git`.

---

## 2) Crear proyecto y environment

1. Ir a `Projects`.
2. `Create Project`.
3. Nombre: `Ecosistema Agro`.
4. Crear o seleccionar environment `production`.

---

## 3) Crear MySQL compartido

1. En el proyecto: `Create Service` -> `Database` -> `MySQL`.
2. Configurar:
   - Name: `mysql-shared`
   - Docker Image: `mysql:8.4`
   - Database Name inicial: `db_auth`
   - Database User inicial: `auth_user`
   - Database Password: (tu clave fuerte)
   - Database Root Password: (tu clave root fuerte)
3. `Create`.
4. Entrar al servicio y pulsar `Deploy` (y `Start` si aplica).

Resultado esperado: servicio en running.

---

## 4) Crear Redis compartido

Si aparece Redis como base de datos:

1. `Create Service` -> `Database` -> `Redis`.
2. Name: `redis-shared`.
3. `Create` -> `Deploy`.

Si no aparece Redis, crear como Compose con imagen `redis:7-alpine`.

Resultado esperado: servicio en running.

---

## 5) Crear BD y usuario de `planes` en MySQL

1. Entrar a `mysql-shared` -> `Open Terminal`.
2. Ejecutar:

```bash
mysql -u root -p
```

3. En MySQL:

```sql
CREATE DATABASE IF NOT EXISTS db_planes CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE USER IF NOT EXISTS 'planes_user'@'%' IDENTIFIED BY 'CAMBIAR_PASSWORD_PLANES';

GRANT ALL PRIVILEGES ON db_auth.* TO 'auth_user'@'%';
GRANT ALL PRIVILEGES ON db_planes.* TO 'planes_user'@'%';

FLUSH PRIVILEGES;

SHOW DATABASES;
SELECT user, host FROM mysql.user WHERE user IN ('auth_user','planes_user');
```

---

## 6) DNS temporal (sin tumbar produccion actual)

Crear registros `A` hacia la IP publica del VPS:

- `oa-auth.iedagropivijay.edu.co`
- `oa-planes.iedagropivijay.edu.co`

Esperar propagacion DNS.

---

## 7) Crear y desplegar app `auth`

### 7.1 Crear servicio

1. `Create Service` -> `Application`.
2. Repo: `github.com/pallaresfj/oa_agroista.git`.
3. Source/Build: Docker Compose desde repo.
4. Compose path: `apps/auth/docker-compose.dokploy.yml`.
5. Exponer servicio `web` puerto `80`.
6. Domain: `oa-auth.iedagropivijay.edu.co`.
7. Activar HTTPS automatico.

### 7.2 Cargar variables de entorno

Pegar el `.env` completo de `AUTH` de la seccion final de este documento.

### 7.3 Deploy

1. Pulsar `Deploy`.
2. Esperar build y estado running.

### 7.4 Bootstrap inicial en auth-web

Entrar a terminal del contenedor `auth-web` y ejecutar:

```bash
cd /var/www/html
php artisan migrate --force
php artisan db:seed --class=SuperAdminsSeeder --force
php artisan passport:keys --force
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### 7.5 Verificar salud

Abrir:

- `https://oa-auth.iedagropivijay.edu.co/up`

Debe mostrar `Application up`.

---

## 8) Crear cliente OAuth de `planes` en `auth`

En `auth-web`:

```bash
cd /var/www/html
php artisan passport:client --name="Planes Dokploy" --redirect_uri="https://oa-planes.iedagropivijay.edu.co/sso/callback"
```

### 8.1 Datos exactos del cliente `planes` (temporal y final)

Usar estos valores para evitar errores de callback/logout:

- Redirect URIs:
  - Temporal: `https://oa-planes.iedagropivijay.edu.co/sso/callback`
  - Final (cutover): `https://planes.iedagropivijay.edu.co/sso/callback`
- Frontchannel logout URIs:
  - Temporal: `https://oa-planes.iedagropivijay.edu.co/sso/frontchannel-logout`
  - Final (cutover): `https://planes.iedagropivijay.edu.co/sso/frontchannel-logout`
- Scopes permitidos:
  - `openid`
  - `email`
  - `profile`
  - Formato en `.env`: `SSO_SCOPES="openid email profile"`

Guardar:

- `Client ID`
- `Client Secret`

Luego actualizar en env de auth:

- `PLANES_CLIENT_ID`
- `PLANES_CLIENT_SECRET`

Y en env de planes:

- `SSO_CLIENT_ID`
- `SSO_CLIENT_SECRET`

---

## 9) Crear y desplegar app `planes`

### 9.1 Crear servicio

1. `Create Service` -> `Application`.
2. Repo: `github.com/pallaresfj/oa_agroista.git`.
3. Compose path: `apps/planes/docker-compose.dokploy.yml`.
4. Servicio publico `web` puerto `80`.
5. Domain: `oa-planes.iedagropivijay.edu.co`.
6. Activar HTTPS automatico.

### 9.2 Cargar variables

Pegar el `.env` completo de `PLANES` de la seccion final.

Importante:

- `SSO_SUPPORT_EMAILS=pallaresfj@iedagropivijay.edu.co`
- `PLANES_BOOTSTRAP_ON_START=true`

### 9.3 Deploy

1. Pulsar `Deploy`.
2. Esperar `web`, `queue`, `scheduler` en running.

`planes` ya ejecuta bootstrap automatico al iniciar `web`.

### 9.4 Verificar salud

- `https://oa-planes.iedagropivijay.edu.co/up`

---

## 10) Validacion funcional minima

1. Entrar a `https://oa-planes.iedagropivijay.edu.co/admin`.
2. Login SSO redirige a `oa-auth` y vuelve a `oa-planes`.
3. Usuario soporte `pallaresfj@iedagropivijay.edu.co` entra con menu completo.
4. Logout desde auth cierra sesion en planes.

---

## 11) Cutover a subdominios finales (cuando decidas)

Cuando todo este aprobado en `oa-*`:

1. Configurar DNS final:
   - `auth.iedagropivijay.edu.co`
   - `planes.iedagropivijay.edu.co`
2. Cambiar dominios en Dokploy.
3. Actualizar variables de `APP_URL`, `PLANES_BASE_URL`, `SSO_*`, `AUTH_API_BASE`.
4. Crear/actualizar cliente OAuth de planes con redirect final:
   - `https://planes.iedagropivijay.edu.co/sso/callback`
5. Redeploy en orden:
   - primero `auth`
   - luego `planes`

---

## 12) .env completo AUTH (temporal oa-auth)

Pega este bloque en Environment del servicio `auth` en Dokploy:

```env
APP_NAME="Auth SSO"
APP_ENV=production
APP_KEY=PEGAR_APP_KEY_AUTH
APP_DEBUG=false
APP_URL=https://oa-auth.iedagropivijay.edu.co

APP_LOCALE=es
APP_FALLBACK_LOCALE=es
APP_FAKER_LOCALE=es_ES
APP_TIMEZONE=America/Bogota
APP_MAINTENANCE_DRIVER=file

LOG_CHANNEL=stderr
LOG_STACK=single
LOG_LEVEL=info

DB_CONNECTION=mysql
DB_HOST=mysql-shared-goni44
DB_PORT=3306
DB_DATABASE=db_auth
DB_USERNAME=auth_user
DB_PASSWORD=PEGAR_PASSWORD_AUTH_USER

SESSION_DRIVER=redis
SESSION_LIFETIME=120
SESSION_ENCRYPT=false
SESSION_COOKIE=auth_sso_session
SESSION_PATH=/
SESSION_DOMAIN=
SESSION_SECURE_COOKIE=true
SESSION_HTTP_ONLY=true
SESSION_SAME_SITE=lax

BROADCAST_CONNECTION=log
FILESYSTEM_DISK=public
QUEUE_CONNECTION=redis
CACHE_STORE=redis

REDIS_CLIENT=phpredis
REDIS_HOST=redis-shared-f9kamn
REDIS_PASSWORD=null
REDIS_PORT=6379
REDIS_PREFIX=auth_

MAIL_MAILER=log
MAIL_SCHEME=null
MAIL_HOST=127.0.0.1
MAIL_PORT=2525
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_FROM_ADDRESS="noreply@iedagropivijay.edu.co"
MAIL_FROM_NAME="${APP_NAME}"

GOOGLE_CLIENT_ID=PEGAR_GOOGLE_CLIENT_ID
GOOGLE_CLIENT_SECRET=PEGAR_GOOGLE_CLIENT_SECRET
GOOGLE_REDIRECT_URI=https://oa-auth.iedagropivijay.edu.co/auth/google/callback
GOOGLE_SESSION_CHECK_REDIRECT_URI=https://oa-auth.iedagropivijay.edu.co/auth/google/session-check/callback

ISSUER=https://oa-auth.iedagropivijay.edu.co
OIDC_FORCE_HTTPS=true
OIDC_KEY_ID=passport-rsa-1

INSTITUTION_EMAIL_DOMAIN=iedagropivijay.edu.co
INSTITUTION_CODE=iedagropivijay
INSTITUTION_DEFAULT_NAME="IED Agro Pivijay"
INSTITUTION_DEFAULT_NIT=
INSTITUTION_DEFAULT_LOGO_URL=
INSTITUTION_DEFAULT_PRIMARY_COLOR=#f50404
INSTITUTION_DEFAULT_SUCCESS_COLOR=#00c853
INSTITUTION_DEFAULT_INFO_COLOR=#0288d1
INSTITUTION_DEFAULT_WARNING_COLOR=#ff9800
INSTITUTION_DEFAULT_DANGER_COLOR=#b71c1c
SUPERADMIN_EMAILS=pallaresfj@iedagropivijay.edu.co

PLANES_BASE_URL=https://oa-planes.iedagropivijay.edu.co
PLANES_CLIENT_ID=PEGAR_CLIENT_ID_PLANES
PLANES_CLIENT_SECRET=PEGAR_CLIENT_SECRET_PLANES
ASISTENCIA_BASE_URL=
ASISTENCIA_CLIENT_ID=
ASISTENCIA_CLIENT_SECRET=
SILO_BASE_URL=
SILO_CLIENT_ID=
SILO_CLIENT_SECRET=

TOKEN_TTL_MINUTES=30
REFRESH_TOKEN_TTL_DAYS=14

CORS_ALLOWED_ORIGINS=https://oa-planes.iedagropivijay.edu.co
SSO_ALLOWED_REDIRECT_HOSTS=oa-planes.iedagropivijay.edu.co,oa-auth.iedagropivijay.edu.co
SSO_INSECURE_REDIRECT_HOSTS=
SSO_POST_LOGOUT_REDIRECT_HOSTS=oa-planes.iedagropivijay.edu.co,oa-auth.iedagropivijay.edu.co
SSO_FRONTCHANNEL_LOGOUT_CLIENTS=planes|https://oa-planes.iedagropivijay.edu.co/sso/frontchannel-logout
SSO_FRONTCHANNEL_LOGOUT_SECRETS=planes|PEGAR_SECRET_FRONTCHANNEL_PLANES
SSO_FRONTCHANNEL_LOGOUT_TTL_SECONDS=120

GOOGLE_LOGOUT_FROM_BROWSER=true
GOOGLE_SESSION_CHECK_ENABLED=true
GOOGLE_SESSION_CHECK_INTERVAL_SECONDS=60
GOOGLE_SESSION_CHECK_TIMEOUT_SECONDS=8
```

---

## 13) .env completo PLANES (temporal oa-planes)

Pega este bloque en Environment del servicio `planes` en Dokploy:

```env
APP_NAME="GESTION ACADEMICA"
APP_ENV=production
APP_KEY=PEGAR_APP_KEY_PLANES
APP_DEBUG=false
APP_URL=https://oa-planes.iedagropivijay.edu.co

APP_LOCALE=es
APP_FALLBACK_LOCALE=es
APP_FAKER_LOCALE=es_ES

APP_MAINTENANCE_DRIVER=file

LOG_CHANNEL=stderr
LOG_STACK=single
LOG_LEVEL=info

DB_CONNECTION=mysql
DB_HOST=mysql-shared-goni44
DB_PORT=3306
DB_DATABASE=db_planes
DB_USERNAME=planes_user
DB_PASSWORD=PEGAR_PASSWORD_PLANES_USER

SESSION_DRIVER=redis
SESSION_LIFETIME=120
SESSION_ENCRYPT=false
SESSION_COOKIE=oa_planes_session
SESSION_PATH=/
SESSION_DOMAIN=
SESSION_SECURE_COOKIE=true

BROADCAST_CONNECTION=log
FILESYSTEM_DISK=public
QUEUE_CONNECTION=redis
CACHE_STORE=redis

REDIS_CLIENT=phpredis
REDIS_HOST=redis-shared-f9kamn
REDIS_PASSWORD=null
REDIS_PORT=6379
REDIS_PREFIX=planes_

MAIL_MAILER=log
MAIL_SCHEME=null
MAIL_HOST=127.0.0.1
MAIL_PORT=2525
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_FROM_ADDRESS="noreply@iedagropivijay.edu.co"
MAIL_FROM_NAME="${APP_NAME}"

SSO_DISCOVERY_URL=https://oa-auth.iedagropivijay.edu.co/.well-known/openid-configuration
SSO_ISSUER=https://oa-auth.iedagropivijay.edu.co
SSO_CLIENT_ID=PEGAR_CLIENT_ID_PLANES
SSO_CLIENT_SECRET=PEGAR_CLIENT_SECRET_PLANES
SSO_SUPPORT_EMAILS=pallaresfj@iedagropivijay.edu.co
SSO_REDIRECT_URI=https://oa-planes.iedagropivijay.edu.co/sso/callback
SSO_SCOPES="openid email profile"
SSO_PROMPT=login
SSO_SESSION_CHECK_ENABLED=true
SSO_SESSION_CHECK_INTERVAL_SECONDS=60
SSO_SESSION_CHECK_TIMEOUT_SECONDS=12
SSO_SESSION_CHECK_PROMPT=none
SSO_IDP_LOGOUT_URL=https://oa-auth.iedagropivijay.edu.co/logout
SSO_CLIENT_KEY=planes
SSO_FRONTCHANNEL_LOGOUT_CLIENT_KEY=planes
SSO_FRONTCHANNEL_LOGOUT_SECRET=PEGAR_SECRET_FRONTCHANNEL_PLANES
SSO_FRONTCHANNEL_LOGOUT_TTL_SECONDS=120
SSO_FRONTCHANNEL_LOGOUT_NEXT_HOSTS=oa-auth.iedagropivijay.edu.co,oa-planes.iedagropivijay.edu.co,accounts.google.com,appengine.google.com
SSO_HTTP_TIMEOUT=10
AUTH_API_BASE=https://oa-auth.iedagropivijay.edu.co/api/ecosystem

PLANES_BOOTSTRAP_ON_START=true
PLANES_BOOTSTRAP_MAX_TRIES=20
PLANES_BOOTSTRAP_SLEEP_SECONDS=3
```

---

## 14) Comando rapido para generar APP_KEY

Puedes generar una key asi:

```bash
php -r "echo 'base64:'.base64_encode(random_bytes(32)).PHP_EOL;"
```

Usa una para `auth` y otra distinta para `planes`.
