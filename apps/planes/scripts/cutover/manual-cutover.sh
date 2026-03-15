#!/usr/bin/env bash
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
SQL_DIR="$SCRIPT_DIR/sql"
TABLES_FILE="$SCRIPT_DIR/migrated_tables.txt"
PATCH_USERS_SQL="$SQL_DIR/patch_users_columns.sql"
ORPHAN_CHECKS_SQL="$SQL_DIR/orphan_checks.sql"
DEFAULT_APP_PATH="$(cd "$SCRIPT_DIR/../.." && pwd)"

BACKUP_DIR="${BACKUP_DIR:-/tmp/planes-cutover}"
DUMP_FILE="${DUMP_FILE:-}"
APP_PATH="${APP_PATH:-$DEFAULT_APP_PATH}"

log() {
  printf '[cutover] %s\n' "$*"
}

warn() {
  printf '[cutover][warn] %s\n' "$*" >&2
}

die() {
  printf '[cutover][error] %s\n' "$*" >&2
  exit 1
}

require_cmd() {
  local cmd="${1:?command required}"
  command -v "$cmd" >/dev/null 2>&1 || die "Missing required command: $cmd"
}

require_env() {
  local missing=()
  local name

  for name in "$@"; do
    if [[ -z "${!name:-}" ]]; then
      missing+=("$name")
    fi
  done

  if [[ ${#missing[@]} -gt 0 ]]; then
    die "Missing required env vars: ${missing[*]}"
  fi
}

load_migrated_tables() {
  MIGRATED_TABLES=()

  while IFS= read -r line; do
    [[ -z "$line" ]] && continue
    [[ "$line" =~ ^# ]] && continue
    MIGRATED_TABLES+=("$line")
  done < "$TABLES_FILE"

  [[ ${#MIGRATED_TABLES[@]} -gt 0 ]] || die "No migrated tables found in $TABLES_FILE"
}

mysql_exec_db() {
  local host="$1"
  local port="$2"
  local user="$3"
  local password="$4"
  local database="$5"
  local sql="$6"

  MYSQL_PWD="$password" mysql \
    --protocol=tcp \
    --host="$host" \
    --port="$port" \
    --user="$user" \
    --database="$database" \
    --batch \
    --raw \
    --skip-column-names \
    --execute="$sql"
}

mysql_exec_server() {
  local host="$1"
  local port="$2"
  local user="$3"
  local password="$4"
  local sql="$5"

  MYSQL_PWD="$password" mysql \
    --protocol=tcp \
    --host="$host" \
    --port="$port" \
    --user="$user" \
    --batch \
    --raw \
    --skip-column-names \
    --execute="$sql"
}

mysqldump_tables() {
  local host="$1"
  local port="$2"
  local user="$3"
  local password="$4"
  local database="$5"
  shift 5

  MYSQL_PWD="$password" mysqldump \
    --protocol=tcp \
    --host="$host" \
    --port="$port" \
    --user="$user" \
    --single-transaction \
    --quick \
    --hex-blob \
    --set-gtid-purged=OFF \
    "$database" "$@"
}

build_counts_query() {
  local query=''
  local table
  local is_first=1

  for table in "${MIGRATED_TABLES[@]}"; do
    if [[ $is_first -eq 1 ]]; then
      query="SELECT '${table}' AS table_name, COUNT(*) AS row_count FROM \\`${table}\\`"
      is_first=0
    else
      query+=$'\nUNION ALL\n'
      query+="SELECT '${table}' AS table_name, COUNT(*) AS row_count FROM \\`${table}\\`"
    fi
  done

  printf '%s\n' "$query"
}

ensure_backup_dir() {
  mkdir -p "$BACKUP_DIR"
}

new_dump_file_path() {
  local timestamp
  timestamp="$(date +%Y%m%d_%H%M%S)"

  if [[ -n "$DUMP_FILE" ]]; then
    printf '%s\n' "$DUMP_FILE"
    return
  fi

  printf '%s/planes_cutover_%s.sql\n' "$BACKUP_DIR" "$timestamp"
}

resolve_dump_file() {
  if [[ -n "$DUMP_FILE" ]]; then
    printf '%s\n' "$DUMP_FILE"
    return
  fi

  local latest_dump
  latest_dump="$(find "$BACKUP_DIR" -maxdepth 1 -type f -name 'planes_cutover_*.sql' -print | sort | tail -n 1)"

  if [[ -z "$latest_dump" ]]; then
    die "No dump file found under $BACKUP_DIR (expected planes_cutover_*.sql). Set DUMP_FILE explicitly."
  fi

  printf '%s\n' "$latest_dump"
}

command_backup_target() {
  require_cmd mysqldump
  require_cmd tar
  require_env TARGET_DB_HOST TARGET_DB_PORT TARGET_DB_USER TARGET_DB_PASSWORD TARGET_DB_NAME

  ensure_backup_dir

  local timestamp db_backup media_backup
  timestamp="$(date +%Y%m%d_%H%M%S)"
  db_backup="$BACKUP_DIR/${TARGET_DB_NAME}_before_cutover_${timestamp}.sql"

  log "Creating target DB backup: $db_backup"
  mysqldump_tables \
    "$TARGET_DB_HOST" "$TARGET_DB_PORT" "$TARGET_DB_USER" "$TARGET_DB_PASSWORD" "$TARGET_DB_NAME" \
    > "$db_backup"

  if [[ -n "${MEDIA_TARGET_DIR:-}" ]]; then
    if [[ -d "$MEDIA_TARGET_DIR" ]]; then
      media_backup="$BACKUP_DIR/media_before_cutover_${timestamp}.tar.gz"
      log "Creating media backup: $media_backup"
      tar -czf "$media_backup" -C "$MEDIA_TARGET_DIR" .
    else
      warn "MEDIA_TARGET_DIR does not exist: $MEDIA_TARGET_DIR"
    fi
  fi

  log "Target backup completed"
}

command_create_cutover_db() {
  require_cmd mysql
  require_env TARGET_DB_HOST TARGET_DB_PORT TARGET_DB_USER TARGET_DB_PASSWORD CUTOVER_DB_NAME

  log "Creating cutover DB if missing: $CUTOVER_DB_NAME"
  mysql_exec_server \
    "$TARGET_DB_HOST" "$TARGET_DB_PORT" "$TARGET_DB_USER" "$TARGET_DB_PASSWORD" \
    "CREATE DATABASE IF NOT EXISTS \\`${CUTOVER_DB_NAME}\\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

  log "Cutover DB ready"
}

command_dump_source() {
  require_cmd mysqldump
  require_env SOURCE_DB_HOST SOURCE_DB_PORT SOURCE_DB_USER SOURCE_DB_PASSWORD SOURCE_DB_NAME

  load_migrated_tables
  ensure_backup_dir

  local dump_file
  dump_file="$(new_dump_file_path)"

  log "Creating source functional dump: $dump_file"
  mysqldump_tables \
    "$SOURCE_DB_HOST" "$SOURCE_DB_PORT" "$SOURCE_DB_USER" "$SOURCE_DB_PASSWORD" "$SOURCE_DB_NAME" \
    "${MIGRATED_TABLES[@]}" \
    > "$dump_file"

  log "Source dump completed"
  printf '%s\n' "$dump_file"
}

command_restore_target() {
  require_cmd mysql
  require_env TARGET_DB_HOST TARGET_DB_PORT TARGET_DB_USER TARGET_DB_PASSWORD CUTOVER_DB_NAME

  local dump_file
  ensure_backup_dir
  dump_file="$(resolve_dump_file)"
  [[ -f "$dump_file" ]] || die "Dump file not found: $dump_file"

  local table_count
  table_count="$(mysql_exec_server \
    "$TARGET_DB_HOST" "$TARGET_DB_PORT" "$TARGET_DB_USER" "$TARGET_DB_PASSWORD" \
    "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = '${CUTOVER_DB_NAME}';")"

  if [[ "$table_count" != "0" && "${ALLOW_NONEMPTY_CUTOVER_DB:-false}" != "true" ]]; then
    die "Cutover DB is not empty ($table_count tables). Set ALLOW_NONEMPTY_CUTOVER_DB=true to override."
  fi

  log "Restoring dump into $CUTOVER_DB_NAME from $dump_file"
  MYSQL_PWD="$TARGET_DB_PASSWORD" mysql \
    --protocol=tcp \
    --host="$TARGET_DB_HOST" \
    --port="$TARGET_DB_PORT" \
    --user="$TARGET_DB_USER" \
    --database="$CUTOVER_DB_NAME" \
    < "$dump_file"

  log "Restore completed"
}

command_patch_users_schema() {
  require_cmd mysql
  require_env TARGET_DB_HOST TARGET_DB_PORT TARGET_DB_USER TARGET_DB_PASSWORD CUTOVER_DB_NAME
  [[ -f "$PATCH_USERS_SQL" ]] || die "SQL file not found: $PATCH_USERS_SQL"

  log "Applying users schema patch in $CUTOVER_DB_NAME"
  MYSQL_PWD="$TARGET_DB_PASSWORD" mysql \
    --protocol=tcp \
    --host="$TARGET_DB_HOST" \
    --port="$TARGET_DB_PORT" \
    --user="$TARGET_DB_USER" \
    --database="$CUTOVER_DB_NAME" \
    < "$PATCH_USERS_SQL"

  log "Users schema patch completed"
}

run_artisan_with_cutover_db() {
  local command=("$@")

  DB_CONNECTION=mysql \
  DB_HOST="$TARGET_DB_HOST" \
  DB_PORT="$TARGET_DB_PORT" \
  DB_DATABASE="$CUTOVER_DB_NAME" \
  DB_USERNAME="$TARGET_DB_USER" \
  DB_PASSWORD="$TARGET_DB_PASSWORD" \
  php artisan "${command[@]}"
}

command_bootstrap_target() {
  require_cmd php
  require_env TARGET_DB_HOST TARGET_DB_PORT TARGET_DB_USER TARGET_DB_PASSWORD CUTOVER_DB_NAME
  [[ -f "$APP_PATH/artisan" ]] || die "artisan not found under APP_PATH=$APP_PATH"

  log "Running post-restore bootstrap against $CUTOVER_DB_NAME"

  (
    cd "$APP_PATH"

    run_artisan_with_cutover_db migrate --force

    if run_artisan_with_cutover_db list --raw | grep -q '^shield:generate$'; then
      run_artisan_with_cutover_db shield:generate --all --panel=admin --option=permissions --no-interaction
    else
      warn "shield:generate command not found. Skipping permission generation."
    fi

    run_artisan_with_cutover_db db:seed --class=Database\\Seeders\\RolePermissionSafeSeeder --force
  )

  log "Bootstrap completed"
}

write_counts_file() {
  local host="$1"
  local port="$2"
  local user="$3"
  local password="$4"
  local database="$5"
  local output_file="$6"

  local query
  query="$(build_counts_query)"

  mysql_exec_db "$host" "$port" "$user" "$password" "$database" "$query" \
    | sort -k1,1 > "$output_file"
}

command_validate_counts() {
  require_cmd mysql
  require_cmd join
  require_cmd sort
  require_cmd awk
  require_env SOURCE_DB_HOST SOURCE_DB_PORT SOURCE_DB_USER SOURCE_DB_PASSWORD SOURCE_DB_NAME
  require_env TARGET_DB_HOST TARGET_DB_PORT TARGET_DB_USER TARGET_DB_PASSWORD CUTOVER_DB_NAME

  load_migrated_tables
  ensure_backup_dir

  local timestamp source_counts target_counts comparison_report
  timestamp="$(date +%Y%m%d_%H%M%S)"
  source_counts="$BACKUP_DIR/source_counts_${timestamp}.tsv"
  target_counts="$BACKUP_DIR/target_counts_${timestamp}.tsv"
  comparison_report="$BACKUP_DIR/counts_comparison_${timestamp}.tsv"

  log "Generating source counts: $source_counts"
  write_counts_file \
    "$SOURCE_DB_HOST" "$SOURCE_DB_PORT" "$SOURCE_DB_USER" "$SOURCE_DB_PASSWORD" "$SOURCE_DB_NAME" \
    "$source_counts"

  log "Generating target counts: $target_counts"
  write_counts_file \
    "$TARGET_DB_HOST" "$TARGET_DB_PORT" "$TARGET_DB_USER" "$TARGET_DB_PASSWORD" "$CUTOVER_DB_NAME" \
    "$target_counts"

  join -t $'\t' -a1 -a2 -e 'MISSING' -o '0,1.2,2.2' "$source_counts" "$target_counts" > "$comparison_report"

  log "Counts comparison report: $comparison_report"

  if awk -F $'\t' '$2 != $3 { print }' "$comparison_report" | tee /dev/stderr | grep -q '.'; then
    die "Count validation failed (mismatched rows found)."
  fi

  log "Count validation passed"
}

command_validate_orphans() {
  require_cmd mysql
  require_cmd awk
  require_env TARGET_DB_HOST TARGET_DB_PORT TARGET_DB_USER TARGET_DB_PASSWORD CUTOVER_DB_NAME
  [[ -f "$ORPHAN_CHECKS_SQL" ]] || die "SQL file not found: $ORPHAN_CHECKS_SQL"

  ensure_backup_dir

  local timestamp orphan_report
  timestamp="$(date +%Y%m%d_%H%M%S)"
  orphan_report="$BACKUP_DIR/orphan_checks_${timestamp}.tsv"

  log "Running orphan checks against $CUTOVER_DB_NAME"

  MYSQL_PWD="$TARGET_DB_PASSWORD" mysql \
    --protocol=tcp \
    --host="$TARGET_DB_HOST" \
    --port="$TARGET_DB_PORT" \
    --user="$TARGET_DB_USER" \
    --database="$CUTOVER_DB_NAME" \
    --batch \
    --raw \
    --skip-column-names \
    < "$ORPHAN_CHECKS_SQL" \
    | tee "$orphan_report"

  log "Orphan report: $orphan_report"

  if awk -F $'\t' '$2 != 0 { print }' "$orphan_report" | tee /dev/stderr | grep -q '.'; then
    die "Orphan validation failed (rows with orphan_count > 0)."
  fi

  log "Orphan validation passed"
}

command_sync_media() {
  require_cmd bash

  if [[ -z "${MEDIA_SYNC_SOURCE_DIRS:-}" ]]; then
    die "MEDIA_SYNC_SOURCE_DIRS is required for sync-media"
  fi

  [[ -f "$APP_PATH/scripts/sync-public-media.sh" ]] || die "sync-public-media.sh not found under APP_PATH=$APP_PATH"

  log "Syncing media using MEDIA_SYNC_SOURCE_DIRS=$MEDIA_SYNC_SOURCE_DIRS"

  (
    cd "$APP_PATH"
    MEDIA_SYNC_SOURCE_DIRS="$MEDIA_SYNC_SOURCE_DIRS" bash scripts/sync-public-media.sh
  )

  log "Media sync completed"
}

command_list_tables() {
  load_migrated_tables
  printf '%s\n' "${MIGRATED_TABLES[@]}"
}

command_go_nogo_checklist() {
  cat <<'CHECKLIST'
Go/No-Go checklist (must be YES for all):
1. Conteos source vs target iguales para todas las tablas migradas.
2. Orphan checks con orphan_count=0 en todas las relaciones.
3. /up responde 200 en oa-planes.
4. Login SSO en /admin exitoso.
5. Usuario soporte puede entrar y ver panel.
6. CRUD minimo validado en Area, Asignatura y Centro.
7. Media sync ejecutado y faltantes aceptados explicitamente.
CHECKLIST
}

usage() {
  cat <<'HELP'
Manual cutover helper for planes -> oa-planes.

Usage:
  bash scripts/cutover/manual-cutover.sh <command>

Commands:
  backup-target       Backup current target DB/media before cutover.
  create-cutover-db   Create CUTOVER_DB_NAME if missing.
  dump-source         Dump migrated functional tables from source DB.
  restore-target      Restore dump into CUTOVER_DB_NAME.
  patch-users-schema  Apply users.auth_subject + users.last_sso_login_at patch.
  bootstrap-target    Run migrate + shield + role permission seeder.
  validate-counts     Compare row counts source vs target for migrated tables.
  validate-orphans    Validate orphan_count=0 on key relationships.
  sync-media          Run public media sync (plan-cover, center-cover).
  list-tables         Print migrated table list.
  go-nogo-checklist   Print final go/no-go checklist.
  help                Show this help.

Recommended sequence:
  1) backup-target
  2) create-cutover-db
  3) dump-source
  4) restore-target
  5) patch-users-schema
  6) bootstrap-target
  7) sync-media
  8) validate-counts
  9) validate-orphans
HELP
}

main() {
  local command="${1:-help}"

  case "$command" in
    backup-target)
      command_backup_target
      ;;
    create-cutover-db)
      command_create_cutover_db
      ;;
    dump-source)
      command_dump_source
      ;;
    restore-target)
      command_restore_target
      ;;
    patch-users-schema)
      command_patch_users_schema
      ;;
    bootstrap-target)
      command_bootstrap_target
      ;;
    validate-counts)
      command_validate_counts
      ;;
    validate-orphans)
      command_validate_orphans
      ;;
    sync-media)
      command_sync_media
      ;;
    list-tables)
      command_list_tables
      ;;
    go-nogo-checklist)
      command_go_nogo_checklist
      ;;
    help|-h|--help)
      usage
      ;;
    *)
      usage
      die "Unknown command: $command"
      ;;
  esac
}

main "$@"
