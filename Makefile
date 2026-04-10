SHELL := /bin/sh

VERSION_SCRIPT := ./scripts/version.sh
COMPOSE_FILE ?= docker-compose.test.yml
ENV_FILE ?= .env.test
COMPOSE := docker compose --env-file $(ENV_FILE) -f $(COMPOSE_FILE)
REMOTE_HOST ?= cococo.be
UNITE_PROFILE ?= balancirk_test

.PHONY: all release test-env container-up container-down container-reset container-logs container-shell container-test container-install snapshot-refresh snapshot-refresh-download snapshot-refresh-cococo003 snapshot-restore remote-docker-check remote-docker-install

all:
	@new_version=`$(VERSION_SCRIPT) bump patch`; \
	echo "Bumped version to $$new_version"; \
	$(MAKE) pkg_balancirk.zip

pkg_balancirk.zip: pkg_balancirk.xml packages/com_balancirk.zip packages/balancirk.zip language/nl-BE/pkg_balancirk.sys.ini language/en-GB/pkg_balancirk.sys.ini | components/com_balancirk plugins/balancirk
	@if [ -f pkg_balancirk.zip ]; then rm pkg_balancirk.zip ; fi
	zip -r pkg_balancirk.zip $+

release:
	@$(VERSION_SCRIPT) ensure-clean
	@new_version=`$(VERSION_SCRIPT) bump major`; \
	echo "Bumped version to $$new_version"; \
	$(MAKE) pkg_balancirk.zip; \
	rm -f "$$new_version.tar.gz" "$$new_version.zip"; \
	tar czf "$$new_version.tar.gz" balancirk.xml balancirk_changelog.xml balancirk_update.xml pkg_balancirk.zip README.md; \
	zip -r "$$new_version.zip" balancirk.xml balancirk_changelog.xml balancirk_update.xml pkg_balancirk.zip README.md; \
	git add balancirk.xml pkg_balancirk.xml components/com_balancirk/balancirk.xml packages/com_balancirk.zip packages/balancirk.zip pkg_balancirk.zip "$$new_version.tar.gz" "$$new_version.zip"; \
	git commit -m "Release $$new_version"; \
	git tag "$$new_version"; \
	git push origin HEAD; \
	git push origin "$$new_version"

packages/com_balancirk.zip: components/com_balancirk
	$(MAKE) -C components/com_balancirk

packages/balancirk.zip: plugins/balancirk
	$(MAKE) -C plugins/balancirk

test-env:
	@if [ ! -f "$(ENV_FILE)" ]; then \
		cp .env.test.example "$(ENV_FILE)"; \
		echo "Created $(ENV_FILE) from .env.test.example"; \
	else \
		echo "$(ENV_FILE) already exists"; \
	fi

container-up: test-env
	@$(COMPOSE) up -d db joomla phpmyadmin tester
	@echo "Joomla: http://localhost:$${JOOMLA_HTTP_PORT:-8080}"
	@echo "phpMyAdmin: http://localhost:$${PHPMYADMIN_HTTP_PORT:-8081}"

container-down:
	@$(COMPOSE) down

container-reset:
	@$(COMPOSE) down -v

container-logs:
	@$(COMPOSE) logs -f --tail 100 joomla db tester

container-shell:
	@$(COMPOSE) exec tester bash

container-test: test-env
	@$(COMPOSE) up -d db joomla tester
	@$(COMPOSE) exec -T tester bash scripts/container/run-tests.sh

container-install: test-env pkg_balancirk.zip
	@$(COMPOSE) up -d db joomla
	@$(COMPOSE) cp pkg_balancirk.zip joomla:/tmp/pkg_balancirk.zip
	@$(COMPOSE) exec -T joomla bash /workspace/scripts/container/install-extension.sh /tmp/pkg_balancirk.zip

snapshot-refresh:
	@bash scripts/container/refresh-unite-remote.sh "$(REMOTE_HOST)" "$(UNITE_PROFILE)"

snapshot-refresh-download:
	@DOWNLOAD_JPA=1 bash scripts/container/refresh-unite-remote.sh "$(REMOTE_HOST)" "$(UNITE_PROFILE)"

snapshot-refresh-cococo003:
	@bash scripts/container/refresh-unite-remote.sh cococo003 "$(UNITE_PROFILE)"

snapshot-restore:
	@if [ -z "$(SQL_DUMP)" ]; then \
		echo "Usage: make snapshot-restore SQL_DUMP=path/to/dump.sql[.gz] [FILES_ARCHIVE=path/to/site_files.tar.gz]"; \
		exit 1; \
	fi
	@bash scripts/container/restore-from-snapshot.sh "$(SQL_DUMP)" "$(FILES_ARCHIVE)"

remote-docker-check:
	@ssh "$(REMOTE_HOST)" 'docker --version && (docker compose version || docker-compose --version)'

remote-docker-install:
	@bash scripts/container/install-docker-remote.sh "$(REMOTE_HOST)"
