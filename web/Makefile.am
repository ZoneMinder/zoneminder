AUTOMAKE_OPTIONS = gnu

# This should be set to your CGI directory
webdir = @WEB_PREFIX@
# And these to the user and group of your webserver
webuser = @WEB_USER@
webgroup = @WEB_GROUP@

web_DATA = zmactions.php zmconfig.php zmdb.php zmfilter.php zmfuncs.php zmhtml.php zm.php zmstyles.css zmwml.php

EXTRA_DIST = $(web_DATA)

# Yes, you are correct. This is a HACK!
install-data-local:
	( cd $(webdir); chown $(webuser):$(webgroup) $(web_DATA) )
