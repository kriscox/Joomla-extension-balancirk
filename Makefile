pkg_balancirk.zip: pkg_balancirk.xml packages/com_balancirk.zip packages/balancirk.zip language/nl-BE/pkg_balancirk.sys.ini language/en-GB/pkg_balancirk.sys.ini | components/com_balancirk plugins/balancirk
	@if [ -f pkg_balancirk.zip ]; then rm pkg_balancirk.zip ; fi
	zip -r pkg_balancirk.zip $+

release: pkg_balancirk.zip
	tar czf $(version).tar.gz balancirk.xml balancirk_changelog.xml balancirk_update.xml pkg_balancirk.zip README.md
	zip -r $(version).zip balancirk.xml balancirk_changelog.xml balancirk_update.xml pkg_balancirk.zip README.md

packages/com_balancirk.zip: components/com_balancirk
	$(MAKE) -C components/com_balancirk

packages/balancirk.zip: plugins/balancirk
	$(MAKE) -C plugins/balancirk