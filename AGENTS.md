# Balancirk - Joomla 4 Extension

## Overview

Balancirk is a Joomla 4 extension package for managing members, students, lessons, subscriptions, and attendance for a gymnastics/circus school. It consists of:

- **com_balancirk** — Main Joomla MVC component (admin + site + API)
- **plg_webservices_balancirk** — Web services plugin (REST API routes)
- **joomlaology** — Shared PHP utility library

This is NOT a standalone application. It requires installation into a Joomla 4 CMS instance.

The Angular member SPA is developed separately on the `Single-page-site-ontwikkeling` branch and is not part of `master`.

## Conventions

- Write **code comments** and **in-repository documentation** (README sections, inline notes in source) in **English**. User-facing Joomla language strings stay in their locale files.
- **`balancirk_changelog.xml` entries must be in English.**

## Packages & testing

- For test installs, **only** use root `pkg_balancirk.zip` (never sub-zips alone, never versioned `1.x.y.zip` as the install source).
- Rebuild without bumping the version:

```bash
make -B packages/com_balancirk.zip packages/balancirk.zip pkg_balancirk.zip
```

- Do **not** run `make` / `make all` or `./scripts/version.sh bump` for ordinary fixes or test packages. That bumps the version; version bumps are **release-only** (see below).
- Joomla `method="upgrade"` does **not** delete files removed from a new zip. If files must disappear on upgrade, add them to `$deleteFiles` / `$deleteFolders` in `components/com_balancirk/script.php` and call `removeFiles()`. Never remove active API files used for presence/attendance (`PresencesController`, routes in `plugins/balancirk/balancirk.php`).

## Releases (master only)

1. Next version = latest **published GitHub release tag** + patch (check `gh release list`; do not trust a higher number already sitting in XML if it was never released).
2. Set `<version>` in `balancirk.xml`, `pkg_balancirk.xml`, and `components/com_balancirk/balancirk.xml`.
3. Add an English entry to `balancirk_changelog.xml` and a matching block to `balancirk_update.xml`.
4. Rebuild `pkg_balancirk.zip` (command above). Do **not** commit zip/tar.gz artifacts; GitHub Releases store the installable package.
5. Open a PR into `master` (branch is protected: **squash-merge**, no merge commits). After merge, tag `VERSION` on `master` and push the tag — GitHub Actions publishes the release with `pkg_balancirk.zip`.
6. **Checksum:** the Release workflow hashes the built `pkg_balancirk.zip`, writes `<sha256>` into `balancirk_update.xml`, and opens/squash-merges a follow-up PR to `master`. No manual checksum step is required. Helper script: `scripts/set-update-checksum.sh`.

## Cursor Cloud specific instructions

Cloud Agents use the install script in `.cursor/environment.json` to refresh dependencies after checkout:

```bash
composer install --no-interaction
```

### Prerequisites (installed by update script)

- PHP 8.3+ with extensions: cli, xml, mbstring, tokenizer, zip
- Composer (for phpcs dev dependency)
- GNU Make + zip (for building packages)

### Lint

```bash
./vendor/bin/phpcs --standard=PSR12 components/ plugins/ libraries/
```

Note: The `composer.json` `cs-check` script targets `src/` which does not exist at root level. Use the command above to lint actual source directories.

### Build

```bash
make -B packages/com_balancirk.zip packages/balancirk.zip pkg_balancirk.zip
```

Rebuilds the installable Joomla package zip without bumping the version. Use this for test packages.

### Testing

There are no automated unit/integration tests in this repository. Validation is done via:
1. `phpcs` linting (PSR-12 standard)
2. Building `pkg_balancirk.zip` successfully
3. Installing **`pkg_balancirk.zip`** into a Joomla 4 test instance (external)

### Important notes

- The `vendor/` directory is committed to the repo, so `composer install` is fast (no network needed if lock file matches).
- The `debug` target in `components/com_balancirk/Makefile` deploys to a remote server via SSH — do not use it in cloud environments.
- Build zips (`pkg_balancirk.zip`, `packages/*.zip`, `*.tar.gz`) are gitignored; build them locally for tests, publish via GitHub Releases.
