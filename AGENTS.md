# Balancirk - Joomla 6 Extension

## Overview

Balancirk is a Joomla 6 extension package for managing members, students, lessons, subscriptions, and attendance for a gymnastics/circus school. It consists of:

- **com_balancirk** — Main Joomla MVC component (admin + site + API)
- **plg_webservices_balancirk** — Web services plugin (REST API routes)
- **joomlaology** — Shared PHP utility library
- **frontend/spa** — Angular PWA (members, teachers, accounting; admin later)

This is NOT a standalone application. It requires installation into a Joomla 6 CMS instance.

## Conventions

- Write **code comments** and **in-repository documentation** (README sections, inline notes in source) in **English**. User-facing Joomla language strings stay in their locale files.

## Cursor Cloud specific instructions

Cloud Agents use the install script in `.cursor/environment.json` to refresh dependencies after checkout:

```bash
composer install --no-interaction && cd frontend/spa && npm install --no-audit --no-fund
```

### Prerequisites (installed by update script)

- PHP 8.3+ with extensions: cli, xml, mbstring, tokenizer, zip
- Composer (for phpcs dev dependency)
- GNU Make + zip (for building packages)
- Node.js 22+ / npm (for Angular spa frontend)

### Lint

```bash
./vendor/bin/phpcs --standard=PSR12 components/ plugins/ libraries/
```

Note: The `composer.json` `cs-check` script targets `src/` which does not exist at root level. Use the command above to lint actual source directories.

### Build

```bash
make -B
```

This forces a full rebuild of the installable Joomla package zip (including sub-packages for the component and plugin). The `-B` flag unconditionally rebuilds all targets.

To build the Angular spa frontend:

```bash
make spa-build
```

The update script pre-installs `frontend/spa/node_modules` so this target runs without network access.

### Testing

There are no automated unit/integration tests in this repository. Validation is done via:
1. `phpcs` linting (PSR-12 standard)
2. Building the package zip successfully
3. Installing into a Joomla 6 instance (external)

### Important notes

- The `vendor/` directory is committed to the repo, so `composer install` is fast (no network needed if lock file matches).
- The `debug` target in `components/com_balancirk/Makefile` deploys to a remote server via SSH — do not use it in cloud environments.
- Built artifacts (`pkg_balancirk.zip`, `packages/*.zip`) are also committed to the repo; `make` rebuilds them fresh.
