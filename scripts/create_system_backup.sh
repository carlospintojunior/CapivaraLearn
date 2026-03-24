#!/bin/bash

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd "$SCRIPT_DIR/.." && pwd)"
ENV_FILE="$PROJECT_ROOT/includes/environment.ini"
BACKUP_ROOT="${BACKUP_ROOT:-$PROJECT_ROOT/backup/system_backups}"
KEEP_LAST="${KEEP_LAST:-15}"
RETENTION_DAYS="${RETENTION_DAYS:-30}"

if [[ ! -f "$ENV_FILE" ]]; then
    echo "Arquivo environment.ini nao encontrado em $ENV_FILE" >&2
    exit 1
fi

if ! command -v mysqldump >/dev/null 2>&1; then
    echo "mysqldump nao encontrado no PATH" >&2
    exit 1
fi

if ! command -v tar >/dev/null 2>&1; then
    echo "tar nao encontrado no PATH" >&2
    exit 1
fi

get_ini_value() {
    local key="$1"
    php -r '$config = parse_ini_file($argv[1], true); $section = $config["production"] ?? []; $value = $section[$argv[2]] ?? ""; echo is_bool($value) ? ($value ? "1" : "0") : $value;' "$ENV_FILE" "$key"
}

DB_HOST="$(get_ini_value db_host)"
DB_NAME="$(get_ini_value db_name)"
DB_USER="$(get_ini_value db_user)"
DB_PASS="$(get_ini_value db_pass)"

TIMESTAMP="$(date '+%Y%m%d_%H%M%S')"
WORK_DIR="$(mktemp -d "${TMPDIR:-/tmp}/capivaralearn-backup-${TIMESTAMP}-XXXX")"
FINAL_ARCHIVE="$BACKUP_ROOT/capivaralearn_system_backup_${TIMESTAMP}.tar.gz"
STATUS_FILE="$BACKUP_ROOT/latest_backup.json"

cleanup() {
    rm -rf "$WORK_DIR"
}
trap cleanup EXIT

mkdir -p "$BACKUP_ROOT"

echo "[1/4] Gerando dump do banco de dados..."
MYSQL_PWD="$DB_PASS" mysqldump \
    --single-transaction \
    --routines \
    --triggers \
    --events \
    --default-character-set=utf8mb4 \
    -h "$DB_HOST" \
    -u "$DB_USER" \
    "$DB_NAME" > "$WORK_DIR/database.sql"
gzip -9 "$WORK_DIR/database.sql"

echo "[2/4] Empacotando codigo da aplicacao..."
tar -czf "$WORK_DIR/code.tar.gz" \
    --exclude='./.git' \
    --exclude='./backup/system_backups' \
    --exclude='./cache' \
    --exclude='./logs' \
    -C "$PROJECT_ROOT" .

echo "[3/4] Empacotando anexos..."
ATTACHMENTS=(
    "public/assets/videos/testes_especiais"
    "public/assets/images/testes_especiais"
)

ATTACHMENTS_TO_INCLUDE=()
for attachment in "${ATTACHMENTS[@]}"; do
    if [[ -d "$PROJECT_ROOT/$attachment" ]]; then
        ATTACHMENTS_TO_INCLUDE+=("$attachment")
    fi
done

if (( ${#ATTACHMENTS_TO_INCLUDE[@]} > 0 )); then
    tar -czf "$WORK_DIR/attachments.tar.gz" -C "$PROJECT_ROOT" "${ATTACHMENTS_TO_INCLUDE[@]}"
else
    tar -czf "$WORK_DIR/attachments.tar.gz" -T /dev/null
fi

echo "[4/4] Gerando manifesto e pacote final..."
cat > "$WORK_DIR/manifest.json" <<EOF
{
  "project": "CapivaraLearn",
  "created_at": "$(date '+%Y-%m-%d %H:%M:%S')",
  "timezone": "America/Sao_Paulo",
  "database": "${DB_NAME}",
  "artifacts": {
    "database_dump": "database.sql.gz",
    "code_archive": "code.tar.gz",
    "attachments_archive": "attachments.tar.gz"
  },
  "retention": {
    "days": ${RETENTION_DAYS},
    "keep_last": ${KEEP_LAST}
  }
}
EOF

tar -czf "$FINAL_ARCHIVE" -C "$WORK_DIR" database.sql.gz code.tar.gz attachments.tar.gz manifest.json

cat > "$STATUS_FILE" <<EOF
{
  "latest_archive": "$(basename "$FINAL_ARCHIVE")",
  "created_at": "$(date '+%Y-%m-%d %H:%M:%S')",
  "size_bytes": $(stat -c%s "$FINAL_ARCHIVE")
}
EOF

find "$BACKUP_ROOT" -maxdepth 1 -type f -name 'capivaralearn_system_backup_*.tar.gz' -mtime +"$RETENTION_DAYS" -delete

mapfile -t EXISTING_ARCHIVES < <(find "$BACKUP_ROOT" -maxdepth 1 -type f -name 'capivaralearn_system_backup_*.tar.gz' | sort -r)
if (( ${#EXISTING_ARCHIVES[@]} > KEEP_LAST )); then
    for (( index=KEEP_LAST; index<${#EXISTING_ARCHIVES[@]}; index++ )); do
        rm -f "${EXISTING_ARCHIVES[$index]}"
    done
fi

echo "Backup concluido: $FINAL_ARCHIVE"