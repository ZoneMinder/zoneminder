AUTOMAKE_OPTIONS = gnu

# And these to the user and group of your webserver
webuser = @WEB_USER@
webgroup = @WEB_GROUP@

sysconf_DATA = \
	zm.conf

SUBDIRS = \
	src \
	web \
	scripts \
	db

EXTRA_DIST = \
	zmconfig.pl.in \
	zmconfig_eml.txt \
	zmconfig_msg.txt \
	README.html \
	README.pdf \
	README.rtf

# Yes, you are correct. This is a HACK!
install-data-hook:
	( cd $(DESTDIR)$(sysconfdir); chown $(webuser):$(webgroup) $(sysconf_DATA); chmod 600 $(sysconf_DATA) )

