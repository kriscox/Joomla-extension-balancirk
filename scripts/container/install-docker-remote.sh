#!/usr/bin/env bash

set -euo pipefail

REMOTE_HOST="${1:-}"

if [ -z "${REMOTE_HOST}" ]; then
    echo "Usage: $0 <remote_host>"
    exit 1
fi

ssh -o BatchMode=yes "${REMOTE_HOST}" '
set -e
if ! command -v docker >/dev/null 2>&1; then
    apt-get update
    apt-get install -y docker.io docker-compose
fi
systemctl enable --now docker
docker --version
if docker compose version >/dev/null 2>&1; then
    docker compose version
else
    docker-compose --version
fi
'
