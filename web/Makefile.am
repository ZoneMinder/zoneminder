AUTOMAKE_OPTIONS = gnu

# You need to set this to your web directory
webdir = /var/www/html
# And these to the user and group of your webserver
webuser = apache
webgroup = apache

web_DATA = zmactions.php zmconfig.php zmdb.php zmfilter.php zmfuncs.php zmhtml.php zm.php zmstyles.css zmwml.php

EXTRA_DIST = $(web_DATA)

# Yes, you are correct. This is a HACK!
install-data-local:
	( cd $(webdir); chown $(webuser):$(webgroup) $(web_DATA) )
