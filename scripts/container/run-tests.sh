#!/usr/bin/env bash

set -euo pipefail

cd /workspace

if [ ! -f "composer.json" ]; then
    echo "composer.json not found in /workspace"
    exit 1
fi

if [ ! -d "vendor" ]; then
    composer install --no-interaction --prefer-dist
fi

POSTMAN_ENV_FILE="${POSTMAN_ENVIRONMENT:-/tmp/balancirk-postman-env.json}"
cat >"${POSTMAN_ENV_FILE}" <<EOF
{
  "id": "balancirk-api-environment",
  "name": "Balancirk API Environment (Container)",
  "values": [
    { "key": "base_url", "value": "${TEST_BASE_URL:-http://joomla}", "enabled": true },
    { "key": "test_username", "value": "${TEST_USERNAME:-admin}", "enabled": true },
    { "key": "test_password", "value": "${TEST_PASSWORD:-changeMe123!}", "enabled": true },
    { "key": "jwt_token", "value": "", "enabled": true }
  ],
  "_postman_variable_scope": "environment"
}
EOF

POSTMAN_ENVIRONMENT="${POSTMAN_ENV_FILE}" \
PHPUNIT_SUITE="${PHPUNIT_SUITE:-Unit}" \
bash run-tests.sh
