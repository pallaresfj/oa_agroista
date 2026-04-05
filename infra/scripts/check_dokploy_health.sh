#!/usr/bin/env bash
set -euo pipefail

if [[ $# -lt 1 ]]; then
  echo "Uso: $0 <dominio-base> [protocolo]"
  echo "Ejemplo: $0 midominio.com https"
  exit 1
fi

BASE_DOMAIN="$1"
PROTO="${2:-https}"
APPS=(auth planes asistencia lectura silo)
FAILED=0

check_url() {
  local label="$1"
  local url="$2"
  local code

  code="$(curl -k -sS -o /dev/null -w '%{http_code}' --max-time 12 "$url" || true)"

  if [[ "$code" == "200" ]]; then
    echo "[OK]   $label -> $url (HTTP $code)"
  else
    echo "[FAIL] $label -> $url (HTTP ${code:-000})"
    FAILED=1
  fi
}

echo "== Health checks /up =="
for app in "${APPS[@]}"; do
  check_url "$app" "$PROTO://$app.$BASE_DOMAIN/up"
done

echo
echo "== OIDC discovery auth =="
check_url "auth oidc" "$PROTO://auth.$BASE_DOMAIN/.well-known/openid-configuration"

echo
if [[ "$FAILED" -eq 0 ]]; then
  echo "Resultado: OK (todos los checks respondieron 200)."
  exit 0
fi

echo "Resultado: FAIL (hay checks con error)."
exit 2
