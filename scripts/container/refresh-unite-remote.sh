#!/usr/bin/env bash

set -euo pipefail

REMOTE_HOST="${1:-cococo.be}"
PROFILE_NAME="${2:-balancirk_test}"
DOWNLOAD_JPA="${DOWNLOAD_JPA:-0}"
TARGET_DIR="${TARGET_DIR:-snapshots}"

REMOTE_SCRIPT="/opt/unite/refresh_${PROFILE_NAME}.sh"

echo "Running ${REMOTE_SCRIPT} on ${REMOTE_HOST}..."
ssh -o BatchMode=yes "${REMOTE_HOST}" "${REMOTE_SCRIPT}"

if [ "${DOWNLOAD_JPA}" = "1" ]; then
    mkdir -p "${TARGET_DIR}"
    LATEST_JPA="$(ssh -o BatchMode=yes "${REMOTE_HOST}" "ls -1t /opt/unite/site-balancirk.be-*.jpa | head -n 1")"
    if [ -n "${LATEST_JPA}" ]; then
        echo "Downloading ${LATEST_JPA} to ${TARGET_DIR}..."
        scp "${REMOTE_HOST}:${LATEST_JPA}" "${TARGET_DIR}/"
    fi
fi

echo "Remote Unite refresh completed on ${REMOTE_HOST}."
