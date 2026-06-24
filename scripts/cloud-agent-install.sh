#!/bin/sh
set -eu

ROOT_DIR=$(CDPATH= cd -- "$(dirname -- "$0")/.." && pwd)

printf '>>> [cloud-agent-install] start\n'

composer install --no-interaction --working-dir="$ROOT_DIR"

if [ ! -d "$ROOT_DIR/frontend/member-spa/node_modules/@angular-devkit/build-angular" ]; then
  make -C "$ROOT_DIR" member-spa-install
else
  printf '>>> [cloud-agent-install] member-spa dependencies already present, skipping npm install\n'
fi

printf '<<< [cloud-agent-install] complete\n'
