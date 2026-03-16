# Lectura (`lectura.iedagropivijay.edu.co`)

App web para medir velocidad lectora con control docente, basada en:

- Laravel 12
- Filament 5
- Livewire 3
- MySQL / SQLite
- SSO institucional del ecosistema OA

## Capacidades

- Gestión de estudiantes
- Banco de lecturas con conteo automático de palabras
- Sesión de lectura en vivo controlada por el docente
- Registro manual de errores por tipo:
  - omisión
  - sustitución
  - inserción
  - vacilación
- Histórico de intentos por estudiante

## Requisitos

- PHP 8.2+
- Composer 2+
- MySQL o SQLite
- Node.js 22+

## Instalación local

```bash
composer install
cp .env.example .env
php artisan key:generate
php -r "file_exists('database/database.sqlite') || touch('database/database.sqlite');"
php artisan migrate --seed
npm install
npm run dev
php artisan serve
```

## Variables principales

```dotenv
APP_URL=https://oa-lectura.test
SSO_DISCOVERY_URL=https://oa-auth.test/.well-known/openid-configuration
SSO_ISSUER=https://oa-auth.test
SSO_CLIENT_ID=
SSO_CLIENT_SECRET=
SSO_REDIRECT_URI=https://oa-lectura.test/sso/callback
LECTURA_ADMIN_EMAIL=admin@institucion.edu.co
```

## Panel

- `/app/dashboard`: resumen general
- `/app/sesion-lectura`: evaluación en vivo
- `/app/students`: estudiantes
- `/app/reading-passages`: lecturas
- `/app/reading-attempts`: historial de intentos

## Deploy en Dokploy

- Guía paso a paso: `docs/DOKPLOY_DEPLOY.md`
