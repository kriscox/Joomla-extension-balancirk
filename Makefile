com_balancirk.zip: $(shell find com_balancirk -type f)
	rm com_balancirk.zip
	(cd com_balancirk ; zip -r ../com_balancirk.zip .)

release: com_balancirk.zip
	tar czf $(version).tar.gz balancirk_changelog.xml balancirk_update.xml com_balancirk README.md;
	zip -r $(version).zip balancirk_changelog.xml balancirk_update.xml com_balancirk README.md
