AUTOMAKE_OPTIONS = gnu

# This should be set to your CGI directory
webdir = @WEB_PREFIX@
# And these to the user and group of your webserver
webuser = @WEB_USER@
webgroup = @WEB_GROUP@

web_DATA = zmactions.php zmconfig.php zmdb.php zmfuncs.php zmhtml.php zm.php zmstyles.css zmwml.php

EXTRA_DIST = zmconfig.php.z zmactions.php zmdb.php zmfuncs.php zmhtml.php zm.php zmstyles.css zmwml.php

# Yes, you are correct. This is a HACK!
install-data-local:
	( cd $(webdir); chown $(webuser):$(webgroup) $(web_DATA) )
	@-( cd $(webdir); if ! test -e images; then mkdir images; fi; chown $(webuser):$(webgroup) images; chmod u+w images )
	@-( cd $(webdir); if ! test -e sounds; then mkdir sounds; fi; chown $(webuser):$(webgroup) sounds; chmod u+w sounds )
	@-( cd $(webdir); if ! test -e temp; then mkdir temp; fi; chown $(webuser):$(webgroup) temp; chmod u+w temp )
	-( cd $(webdir); ln -s zm.php index.php )
