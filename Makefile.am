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
	( if ! test -e $(ZM_RUNDIR); then mkdir -p $(ZM_RUNDIR); fi; if test "$(ZM_RUNDIR)" != "/var/run"; then chown $(webuser):$(webgroup) $(ZM_RUNDIR); chmod u+w $(ZM_RUNDIR); fi )
	( if ! test -e $(ZM_TMPDIR); then mkdir -m 700 -p $(ZM_TMPDIR); fi; if test "$(ZM_TMPDIR)" != "/tmp"; then chown $(webuser):$(webgroup) $(ZM_TMPDIR); chmod u+w $(ZM_TMPDIR); fi )
	( if ! test -e $(ZM_LOGDIR); then mkdir -p $(ZM_LOGDIR); fi; if test "$(ZM_LOGDIR)" != "/var/log"; then chown $(webuser):$(webgroup) $(ZM_LOGDIR); chmod u+w $(ZM_LOGDIR); fi )

uninstall-hook:
	@-( cd $(DESTDIR)$(webdir); rm -rf events graphics images sounds temp )
	@-( if test "$(ZM_RUNDIR)" != "/var/run"; then rm -rf $(ZM_RUNDIR); fi )
	@-( if test "$(ZM_TMPDIR)" != "/tmp"; then rm -rf $(ZM_TMPDIR); fi )
	@-( if test "$(ZM_LOGDIR)" != "/var/log"; then rm -rf $(ZM_LOGDIR); fi )
