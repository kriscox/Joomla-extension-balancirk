pkg_balancirk.zip: packages/com_balancirk.zip packages/balancirk.zip language/nl-BE/pkg_balancirk.sys.ini language/en-GB/pkg_balancirk.sys.ini
	rm pkg_balancirk.zip
	zip -r pkg_balancirk.zip components/com_balancirk.zip plugins/balancirk.zip language/nl-BE/pkg_balancirk.sys.ini language/en-GB/pkg_balancirk.sys.ini)

release: pkg_balancirk.zip
	tar czf $(version).tar.gz balancirk_changelog.xml balancirk_update.xml _balancirk README.md
	zip -r $(version).zip balancirk_changelog.xml balancirk_update.xml com_balancirk README.md

packages/com_balancirk.zip: components/com_balancirk
	$(MAKE) -C components/com_balancirk

packages/balancirk.zip: plugins/balancirk
	$(MAKE) -C plugins/balancirk