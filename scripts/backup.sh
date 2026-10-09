#!/usr/bin/env bash
set -euo pipefail
: "${DB_NAME:?Set DB_NAME}" "${DB_USER:?Set DB_USER}" "${DB_PASS:?Set DB_PASS}"
backup_dir="${BACKUP_DIR:-./database/backups}"
mkdir -p "$backup_dir"
stamp="$(date +%Y%m%d-%H%M%S)"
MYSQL_PWD="$DB_PASS" mysqldump --single-transaction --routines --triggers -u "$DB_USER" -h "${DB_HOST:-127.0.0.1}" "$DB_NAME" | gzip > "$backup_dir/hfops-$stamp.sql.gz"
find "$backup_dir" -type f -name 'hfops-*.sql.gz' -mtime +30 -delete
