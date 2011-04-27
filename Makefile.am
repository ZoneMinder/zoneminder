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
	db \
	misc

EXTRA_DIST = \
	zm.conf.in \
	zmconfgen.pl.in

# Yes, you are correct. This is a HACK!
install-data-hook:
	( cd $(DESTDIR)$(sysconfdir); chown $(webuser):$(webgroup) $(sysconf_DATA); chmod 600 $(sysconf_DATA) )
	( if ! test -e $(ZM_RUNDIR); then mkdir -p $(ZM_RUNDIR); fi; chown $(webuser):$(webgroup) $(ZM_RUNDIR); chmod u+w $(ZM_RUNDIR) )
	( if ! test -e $(ZM_TMPDIR); then mkdir -p $(ZM_TMPDIR); fi; chown $(webuser):$(webgroup) $(ZM_TMPDIR); chmod u+w $(ZM_TMPDIR) )
	( if ! test -e $(ZM_LOGDIR); then mkdir -p $(ZM_LOGDIR); fi; chown $(webuser):$(webgroup) $(ZM_LOGDIR); chmod u+w $(ZM_LOGDIR) )

uninstall-hook:
	@-( cd $(DESTDIR)$(webdir); rm -rf events graphics images sounds temp )
	@-( rm -rf $(ZM_RUNDIR) $(ZM_TMPDIR) $(ZM_LOGDIR) )
