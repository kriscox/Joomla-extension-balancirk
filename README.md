# Balancirk - Local Test Stack

Deze repository bevat een Docker-gebaseerde teststack voor Joomla + MariaDB + API-tests.

## Snel starten

1. Maak een lokale testconfig:
```bash
make test-env
```
2. Start de stack:
```bash
make container-up
```
3. Open Joomla op `http://localhost:8080` en rond de installatie af.

## Package installeren en testen

1. Bouw en installeer de extension in de container:
```bash
make container-install
```
2. Run unit + Postman/Newman tests:
```bash
make container-test
```

## Snapshot vanuit Unite

Refresh op een remote host (standaard `cococo.be`, profiel `balancirk_test`):
```bash
make snapshot-refresh
```

Refresh op `cococo003`:
```bash
make snapshot-refresh-cococo003
```

Refresh + download laatste `.jpa`:
```bash
make snapshot-refresh-download REMOTE_HOST=cococo003
```

DB snapshot lokaal herstellen:
```bash
make snapshot-restore SQL_DUMP=/pad/naar/dump.sql.gz
```

Optioneel met Joomla files archive:
```bash
make snapshot-restore SQL_DUMP=/pad/naar/dump.sql.gz FILES_ARCHIVE=/pad/naar/site_files.tar.gz
```

## Docker op remote host

Controleer Docker versie op remote host:
```bash
make remote-docker-check REMOTE_HOST=cococo003
```

Installeer Docker + Compose op Debian-host via SSH:
```bash
make remote-docker-install REMOTE_HOST=cococo003
```

## Belangrijke bestanden

- `docker-compose.test.yml`: teststack definitie
- `.env.test.example`: voorbeeldvariabelen voor lokale stack
- `scripts/container/run-tests.sh`: container test runner
- `scripts/container/restore-from-snapshot.sh`: DB/files restore in container
- `scripts/container/refresh-unite-remote.sh`: remote unite refresh helper

## Angular ledenmodule (one-page)

Er is nu een aparte Angular SPA voorzien in `frontend/member-spa` voor de ledenmodule (`profiel + kinderen`, mobile-first, PWA-ready).

Belangrijkste commando's:
```bash
make member-spa-install
make member-spa-build
make member-spa-deploy
```

Na deploy wordt de build geplaatst in `components/com_balancirk/media/member-spa/browser` en kan je in Joomla een menu-item maken naar:

`index.php?option=com_balancirk&view=member&layout=spa`

### Git-workflow (SPA-ontwikkeling)

SPA-wijzigingen lopen via een aparte integratiebranch, niet rechtstreeks naar `master`:

| Branch | Doel |
|--------|------|
| `Single-page-site-ontwikkeling` | Integratiebranch voor alle SPA-werk |
| `cursor/...` of feature branches | Concrete wijzigingen (bijv. `cursor/single-page-site-3637`) |
| `master` | Productie / Joomla-releases (SPA merge pas wanneer klaar) |

**Stappen:**

1. Branch af van `Single-page-site-ontwikkeling` (niet van `master`).
2. Open een PR met **base** `Single-page-site-ontwikkeling`.
3. Na review: merge in `Single-page-site-ontwikkeling`.
4. Als de SPA-kern stabiel is: aparte PR van `Single-page-site-ontwikkeling` → `master`.

Meer detail over de Angular-app zelf: `frontend/member-spa/README.md`.
