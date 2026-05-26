#!/usr/bin/env bash

set -euo pipefail

PACKAGE_PATH="${1:-/workspace/pkg_balancirk.zip}"
JOOMLA_CLI="/var/www/html/cli/joomla.php"

if [ ! -f "${PACKAGE_PATH}" ]; then
    echo "Package not found: ${PACKAGE_PATH}"
    exit 1
fi

if [ ! -f "${JOOMLA_CLI}" ]; then
    echo "Joomla CLI not found at ${JOOMLA_CLI}"
    exit 1
fi

if [ ! -f "/var/www/html/configuration.php" ]; then
    echo "Joomla is not installed yet. Finish the web installer first."
    exit 1
fi

php "${JOOMLA_CLI}" extension:install "${PACKAGE_PATH}"
echo "Extension installed from ${PACKAGE_PATH}"
