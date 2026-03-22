SHELL := /bin/sh

VERSION_SCRIPT := ./scripts/version.sh

.PHONY: all release

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
