#!/usr/bin/env sh
set -e

mkdir -p \
  /var/www/html/storage/framework/cache/data \
  /var/www/html/storage/framework/sessions \
  /var/www/html/storage/framework/views \
  /var/www/html/storage/logs \
  /var/www/html/bootstrap/cache

chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

if [ ! -L /var/www/html/public/storage ]; then
  php /var/www/html/artisan storage:link || true
fi

if [ "${1:-}" = "apache2-foreground" ] && [ "${ASISTENCIA_BOOTSTRAP_ON_START:-true}" = "true" ]; then
  echo "[asistencia] Running startup bootstrap (migrations + permissions + seed)..."

  # Ensure stale config cache from previous deploys does not hide new env/config keys.
  php /var/www/html/artisan config:clear || true

  tries=0
  max_tries="${ASISTENCIA_BOOTSTRAP_MAX_TRIES:-20}"
  sleep_seconds="${ASISTENCIA_BOOTSTRAP_SLEEP_SECONDS:-3}"

  until php /var/www/html/artisan migrate --force; do
    tries=$((tries + 1))
    if [ "$tries" -ge "$max_tries" ]; then
      echo "[asistencia] migrate failed after ${max_tries} attempts."
      exit 1
    fi
    echo "[asistencia] migrate failed (attempt ${tries}/${max_tries}); retrying in ${sleep_seconds}s..."
    sleep "$sleep_seconds"
  done

  if php /var/www/html/artisan list --raw | grep -q "^shield:generate$"; then
    php /var/www/html/artisan shield:generate --all --panel=app --option=permissions --no-interaction
  else
    echo "[asistencia] shield:generate command not found, skipping permission generation."
  fi

  php /var/www/html/artisan db:seed --force
  php /var/www/html/artisan config:cache || true
fi

exec "$@"
