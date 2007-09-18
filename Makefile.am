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
	zm.conf.in

# Yes, you are correct. This is a HACK!
install-data-hook:
	( cd $(DESTDIR)$(sysconfdir); chown $(webuser):$(webgroup) $(sysconf_DATA); chmod 600 $(sysconf_DATA) )
	-( if ! test -e $(RUNDIR); then mkdir $(RUNDIR); fi )
	( if ! test -e $(ZM_RUNDIR); then mkdir $(ZM_RUNDIR); fi; chown $(webuser):$(webgroup) $(ZM_RUNDIR); chmod u+w $(ZM_RUNDIR) )

uninstall-hook:
	@-( cd $(DESTDIR)$(webdir); rm -rf events graphics images sounds temp )
	@-( rm -rf $(ZM_RUNDIR) )
