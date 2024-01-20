
all: admin media site api script.php balancirk.xml
	@if [ -f ../../packages/com_balancirk.zip ]; then  rm ../../packages/com_balancirk.zip ; fi 
	zip -r ../../packages/com_balancirk.zip $+

debug: admin media site 
	(cd com_balancirk/admin ; tar czof - . ) | ( ssh -t  cococo.be '(cd /opt/test/administrator/components/com_balancirk; tar xzf - )' ) 
	(cd com_balancirk/media	; tar czof - .) | (ssh -t cococo.be '(cd /opt/test/media/com_balancirk ; tar xzf -)')
	(cd com_balancirk/site ; tar czof - . ) | ( ssh -t cococo.be "(cd /opt/test/components/com_balancirk; tar xzf -)" ) 
	ssh -q cococo.be "/bin/chown -R www-data:www-data /opt/test/administrator/components/com_balancirk /opt/test/components/com_balancirk" 
	
