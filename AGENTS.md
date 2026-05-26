# Balancirk - Joomla 4 Extension

## Overview

Balancirk is a Joomla 4 extension package for managing members, students, lessons, subscriptions, and attendance for a gymnastics/circus school. It consists of:

- **com_balancirk** — Main Joomla MVC component (admin + site + API)
- **plg_webservices_balancirk** — Web services plugin (REST API routes)
- **joomlaology** — Shared PHP utility library

This is NOT a standalone application. It requires installation into a Joomla 4 CMS instance.

## Cursor Cloud specific instructions

### Prerequisites (installed by update script)

- PHP 8.3+ with extensions: cli, xml, mbstring, tokenizer, zip
- Composer (for phpcs dev dependency)
- GNU Make + zip (for building packages)
- Node.js 22+ / npm (for Angular member-spa frontend)

### Lint

```bash
./vendor/bin/phpcs --standard=PSR12 components/ plugins/ libraries/
```

Note: The `composer.json` `cs-check` script targets `src/` which does not exist at root level. Use the command above to lint actual source directories.

### Build

```bash
make -B pkg_balancirk.zip
```

This forces a full rebuild of the installable Joomla package zip (including sub-packages for the component and plugin). The `-B` flag unconditionally rebuilds all targets.

To build the Angular member-spa frontend:

```bash
make member-spa-build
```

The update script pre-installs `frontend/member-spa/node_modules` so this target runs without network access.

### Testing

There are no automated unit/integration tests in this repository. Validation is done via:
1. `phpcs` linting (PSR-12 standard)
2. Building the package zip successfully
3. Installing into a Joomla 4 instance (external)

### Important notes

- The `vendor/` directory is committed to the repo, so `composer install` is fast (no network needed if lock file matches).
- The `debug` target in `components/com_balancirk/Makefile` deploys to a remote server via SSH — do not use it in cloud environments.
- Built artifacts (`pkg_balancirk.zip`, `packages/*.zip`) are also committed to the repo; `make` rebuilds them fresh.
