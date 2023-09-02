com_balancirk.zip: $(shell find com_balancirk -type f)
	rm com_balancirk.zip
	(cd com_balancirk ; zip -r ../com_balancirk.zip .)

release: com_balancirk.zip
	tar czf $(version).tar.gz balancirk_changelog.xml balancirk_update.xml com_balancirk README.md;
	zip -r $(version).zip balancirk_changelog.xml balancirk_update.xml com_balancirk README.md

debug: com_balancirk.zip 
	(cd com_balancirk/admin ; tar czof - . ) | ( ssh -t  cococo.be '(cd /opt/test/administrator/components/com_balancirk; tar xzf - )' ) 
	(cd com_balancirk/site ; tar czof - . ) | ( ssh -t cococo.be "(cd /opt/test/components/com_balancirk; tar xzf -)" ) 
	ssh -q cococo.be "/bin/chown -R www-data:www-data /opt/test/administrator/components/com_balancirk /opt/test/components/com_balancirk" 
	
