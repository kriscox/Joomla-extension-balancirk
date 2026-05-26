#!/usr/bin/env bash

set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
cd "${ROOT_DIR}"

COMPOSE_FILE="${COMPOSE_FILE:-docker-compose.test.yml}"
ENV_FILE="${ENV_FILE:-.env.test}"
SQL_DUMP="${1:-}"
FILES_ARCHIVE="${2:-}"

if [ -z "${SQL_DUMP}" ]; then
    echo "Usage: $0 <db_dump.sql|db_dump.sql.gz> [site_files.tar.gz]"
    exit 1
fi

if [ ! -f "${SQL_DUMP}" ]; then
    echo "SQL dump not found: ${SQL_DUMP}"
    exit 1
fi

if [ ! -f "${ENV_FILE}" ]; then
    echo "Environment file not found: ${ENV_FILE}"
    exit 1
fi

# shellcheck disable=SC1090
source "${ENV_FILE}"
DB_ROOT_PASSWORD="${JOOMLA_DB_ROOT_PASSWORD:-root}"
DB_NAME="${JOOMLA_DB_NAME:-joomla}"

COMPOSE_CMD=(docker compose --env-file "${ENV_FILE}" -f "${COMPOSE_FILE}")

"${COMPOSE_CMD[@]}" up -d db joomla

echo "Restoring database into ${DB_NAME}..."
if [[ "${SQL_DUMP}" == *.gz ]]; then
    gunzip -c "${SQL_DUMP}" | "${COMPOSE_CMD[@]}" exec -T db sh -lc \
        "mysql -uroot -p\"${DB_ROOT_PASSWORD}\" \"${DB_NAME}\""
else
    cat "${SQL_DUMP}" | "${COMPOSE_CMD[@]}" exec -T db sh -lc \
        "mysql -uroot -p\"${DB_ROOT_PASSWORD}\" \"${DB_NAME}\""
fi

if [ -n "${FILES_ARCHIVE}" ]; then
    if [ ! -f "${FILES_ARCHIVE}" ]; then
        echo "Files archive not found: ${FILES_ARCHIVE}"
        exit 1
    fi

    echo "Restoring Joomla files from ${FILES_ARCHIVE}..."
    TMP_FILE="/tmp/site_files.tar.gz"
    "${COMPOSE_CMD[@]}" cp "${FILES_ARCHIVE}" "joomla:${TMP_FILE}"
    "${COMPOSE_CMD[@]}" exec -T joomla sh -lc \
        "cd /var/www/html && tar xzf \"${TMP_FILE}\" && rm -f \"${TMP_FILE}\""
fi

echo "Snapshot restore completed."
