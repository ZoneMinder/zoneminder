#!/usr/bin/env bash
set -euo pipefail

require_env() {
  local name="$1"
  if [ -z "${!name:-}" ]; then
    echo "Missing required environment variable: ${name}" >&2
    exit 1
  fi
}

escape_sed() {
  printf '%s' "$1" | sed -e 's/[\\/&|]/\\&/g'
}

update_conf() {
  local key="$1"
  local value="$2"
  local file="/etc/zm/zm.conf"

  if [ ! -f "$file" ]; then
    return
  fi

  local escaped
  escaped=$(escape_sed "$value")
  if grep -q "^${key}=" "$file"; then
    sed -i "s|^${key}=.*|${key}=${escaped}|" "$file"
  else
    printf '\n%s=%s\n' "$key" "$value" >> "$file"
  fi
}

init_dirs() {
  install -d /run/apache2 /var/lock/apache2
  install -d -o www-data -g www-data /var/log/zm /var/lib/zm
  install -d -o www-data -g www-data \
    /var/cache/zoneminder \
    /var/cache/zoneminder/cache \
    /var/cache/zoneminder/events \
    /var/cache/zoneminder/images \
    /var/cache/zoneminder/temp
}

wait_for_db() {
  local timeout="${ZM_DB_TIMEOUT:-60}"
  local start=$SECONDS

  echo "Waiting for database at ${ZM_DB_HOST}..."
  while ! MYSQL_PWD="$ZM_DB_PASS" mysqladmin ping -h "$ZM_DB_HOST" -u "$ZM_DB_USER" --silent; do
    if (( SECONDS - start >= timeout )); then
      echo "Timed out waiting for database after ${timeout}s." >&2
      exit 1
    fi
    sleep 2
  done
}

config_has_table() {
  MYSQL_PWD="$ZM_DB_PASS" mysql -N -s -h "$ZM_DB_HOST" -u "$ZM_DB_USER" \
    -e "SELECT 1 FROM information_schema.tables WHERE table_schema='${ZM_DB_NAME}' AND table_name='Config' LIMIT 1;"
}

init_db_schema() {
  if ! config_has_table | grep -q 1; then
    if [ ! -f /usr/share/zoneminder/db/zm_create.sql ]; then
      echo "Missing /usr/share/zoneminder/db/zm_create.sql for schema init." >&2
      exit 1
    fi
    echo "Initializing ZoneMinder database schema..."
    sed -e '/^CREATE DATABASE /d' -e '/^USE /d' /usr/share/zoneminder/db/zm_create.sql | \
      MYSQL_PWD="$ZM_DB_PASS" mysql -h "$ZM_DB_HOST" -u "$ZM_DB_USER" "$ZM_DB_NAME"
  fi
}

run_updates() {
  if ! command -v zmupdate.pl >/dev/null 2>&1; then
    echo "zmupdate.pl not found; cannot update database." >&2
    exit 1
  fi
  echo "Running zmupdate.pl (non-interactive)..."
  zmupdate.pl --nointeractive
  zmupdate.pl --nointeractive -f
}

start_zm() {
  if command -v zmdc.pl >/dev/null 2>&1; then
    zmdc.pl startup
  else
    echo "zmdc.pl not found; skipping ZM startup." >&2
  fi
}

main() {
  require_env ZM_DB_HOST
  require_env ZM_DB_USER
  require_env ZM_DB_PASS
  : "${ZM_DB_NAME:=zm}"

  update_conf ZM_DB_HOST "$ZM_DB_HOST"
  update_conf ZM_DB_NAME "$ZM_DB_NAME"
  update_conf ZM_DB_USER "$ZM_DB_USER"
  update_conf ZM_DB_PASS "$ZM_DB_PASS"

  init_dirs
  wait_for_db
  init_db_schema
  run_updates
  start_zm

  exec apachectl -D FOREGROUND
}

main "$@"
