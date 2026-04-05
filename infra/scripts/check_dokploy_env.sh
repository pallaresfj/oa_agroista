#!/usr/bin/env bash
set -euo pipefail

if [[ $# -ne 2 ]]; then
  echo "Uso: $0 <app> <archivo-env>"
  echo "Apps soportadas: auth | planes | asistencia | lectura | silo"
  exit 1
fi

APP="$1"
ENV_FILE="$2"

case "$APP" in
  auth|planes|asistencia|lectura|silo) ;;
  *)
    echo "Error: app no soportada: $APP"
    exit 1
    ;;
esac

if [[ ! -f "$ENV_FILE" ]]; then
  echo "Error: no existe el archivo: $ENV_FILE"
  exit 1
fi

ERRORS=0
WARNINGS=0

get_value_raw() {
  local key="$1"
  local line
  line="$(grep -E "^${key}=" "$ENV_FILE" | tail -n 1 || true)"
  if [[ -z "$line" ]]; then
    echo ""
    return
  fi
  echo "${line#*=}"
}

normalize_value() {
  local v="$1"
  # trim leading/trailing spaces
  v="$(echo "$v" | sed -E 's/^[[:space:]]+//; s/[[:space:]]+$//')"

  # strip matching surrounding single/double quotes
  if [[ "$v" =~ ^\".*\"$ ]]; then
    v="${v:1:${#v}-2}"
  elif [[ "$v" =~ ^\'.*\'$ ]]; then
    v="${v:1:${#v}-2}"
  fi

  echo "$v"
}

has_key() {
  local key="$1"
  grep -qE "^${key}=" "$ENV_FILE"
}

is_placeholder() {
  local value="$1"
  local key="$2"

  local lower
  lower="$(echo "$value" | tr '[:upper:]' '[:lower:]')"

  if [[ "$value" == *"<dominio>"* ]]; then
    return 0
  fi

  if [[ "$lower" == *"change-me"* || "$lower" == *"cambiar"* ]]; then
    return 0
  fi

  if [[ "$lower" == *"example.com"* ]]; then
    return 0
  fi

  # For secrets/keys, reject obvious fake values.
  if [[ "$key" =~ (SECRET|PASSWORD|APP_KEY|CLIENT_ID) ]] && [[ "$lower" =~ ^(null|test|dummy|xxx)$ ]]; then
    return 0
  fi

  return 1
}

require_key_nonempty() {
  local key="$1"
  local raw value

  if ! has_key "$key"; then
    echo "[ERROR] Falta clave: $key"
    ERRORS=$((ERRORS + 1))
    return
  fi

  raw="$(get_value_raw "$key")"
  value="$(normalize_value "$raw")"

  if [[ -z "$value" ]]; then
    echo "[ERROR] Clave vacía: $key"
    ERRORS=$((ERRORS + 1))
    return
  fi

  if is_placeholder "$value" "$key"; then
    echo "[ERROR] Placeholder detectado en $key: $value"
    ERRORS=$((ERRORS + 1))
  fi
}

require_key_value() {
  local key="$1"
  local expected="$2"
  local raw value

  if ! has_key "$key"; then
    echo "[ERROR] Falta clave: $key"
    ERRORS=$((ERRORS + 1))
    return
  fi

  raw="$(get_value_raw "$key")"
  value="$(normalize_value "$raw")"

  if [[ "$value" != "$expected" ]]; then
    echo "[ERROR] Valor inválido en $key (actual='$value', esperado='$expected')"
    ERRORS=$((ERRORS + 1))
  fi
}

warn_if_missing_or_empty() {
  local key="$1"
  local raw value

  if ! has_key "$key"; then
    echo "[WARN] Clave no encontrada (opcional): $key"
    WARNINGS=$((WARNINGS + 1))
    return
  fi

  raw="$(get_value_raw "$key")"
  value="$(normalize_value "$raw")"

  if [[ -z "$value" ]]; then
    echo "[WARN] Clave opcional vacía: $key"
    WARNINGS=$((WARNINGS + 1))
  fi
}

check_common() {
  local required=(
    APP_KEY
    APP_URL
    DB_HOST
    DB_PORT
    DB_DATABASE
    DB_USERNAME
    DB_PASSWORD
    SESSION_SECURE_COOKIE
  )

  for k in "${required[@]}"; do
    require_key_nonempty "$k"
  done

  # SESSION_DOMAIN vacío es válido y recomendado.
  if ! has_key "SESSION_DOMAIN"; then
    echo "[WARN] Falta SESSION_DOMAIN (se recomienda definirlo vacío: SESSION_DOMAIN=)"
    WARNINGS=$((WARNINGS + 1))
  fi

  require_key_value "SESSION_SECURE_COOKIE" "true"
}

check_auth() {
  local required=(
    SUPERADMIN_EMAILS
    PLANES_BASE_URL PLANES_CLIENT_ID PLANES_CLIENT_SECRET
    ASISTENCIA_BASE_URL ASISTENCIA_CLIENT_ID ASISTENCIA_CLIENT_SECRET
    LECTURA_BASE_URL LECTURA_CLIENT_ID LECTURA_CLIENT_SECRET
    SILO_BASE_URL SILO_CLIENT_ID SILO_CLIENT_SECRET
    CORS_ALLOWED_ORIGINS
    SSO_ALLOWED_REDIRECT_HOSTS
    SSO_POST_LOGOUT_REDIRECT_HOSTS
    SSO_FRONTCHANNEL_LOGOUT_CLIENTS
    SSO_FRONTCHANNEL_LOGOUT_SECRETS
  )

  for k in "${required[@]}"; do
    require_key_nonempty "$k"
  done
}

check_client_app() {
  local bootstrap_key="$1"

  local required=(
    SSO_DISCOVERY_URL
    SSO_ISSUER
    SSO_CLIENT_ID
    SSO_CLIENT_SECRET
    SSO_REDIRECT_URI
    AUTH_API_BASE
    SSO_FRONTCHANNEL_LOGOUT_CLIENT_KEY
    SSO_FRONTCHANNEL_LOGOUT_SECRET
  )

  for k in "${required[@]}"; do
    require_key_nonempty "$k"
  done

  require_key_value "$bootstrap_key" "false"
}

check_common

case "$APP" in
  auth)
    check_auth
    ;;
  planes)
    check_client_app "PLANES_BOOTSTRAP_ON_START"
    ;;
  asistencia)
    check_client_app "ASISTENCIA_BOOTSTRAP_ON_START"
    ;;
  lectura)
    check_client_app "LECTURA_BOOTSTRAP_ON_START"
    warn_if_missing_or_empty "LECTURA_ADMIN_EMAIL"
    ;;
  silo)
    check_client_app "SILO_BOOTSTRAP_ON_START"
    ;;
esac

echo
if [[ "$ERRORS" -gt 0 ]]; then
  echo "Resultado: FAIL ($ERRORS errores, $WARNINGS warnings)"
  exit 2
fi

echo "Resultado: OK (0 errores, $WARNINGS warnings)"
exit 0
