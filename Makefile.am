AUTOMAKE_OPTIONS = foreign

# And these to the user and group of your webserver
webuser = @WEB_USER@
webgroup = @WEB_GROUP@

sysconf_DATA = \
	zm.conf

SUBDIRS = \
	src \
	scripts \
	db \
	misc

EXTRA_DIST = \
	zm.conf.in \
	zmconfgen.pl.in

# Yes, you are correct. This is a HACK!
install-data-hook:
	( cd $(DESTDIR)$(sysconfdir); chown $(webuser):$(webgroup) $(sysconf_DATA); chmod 600 $(sysconf_DATA) )
	( if ! test -e $(DESTDIR)$(ZM_RUNDIR); then mkdir -p $(DESTDIR)$(ZM_RUNDIR); fi; if test "$(DESTDIR)$(ZM_RUNDIR)" != "/var/run"; then chown $(webuser):$(webgroup) $(DESTDIR)$(ZM_RUNDIR); chmod u+w $(DESTDIR)$(ZM_RUNDIR); fi )
	( if ! test -e $(DESTDIR)$(ZM_TMPDIR); then mkdir -m 700 -p $(DESTDIR)$(ZM_TMPDIR); fi; if test "$(DESTDIR)$(ZM_TMPDIR)" != "/tmp"; then chown $(webuser):$(webgroup) $(DESTDIR)$(ZM_TMPDIR); chmod u+w $(DESTDIR)$(ZM_TMPDIR); fi )

uninstall-hook:
	@-( cd $(DESTDIR)$(webdir); rm -rf events graphics images sounds temp )
	@-( if test "$(DESTDIR)$(ZM_RUNDIR)" != "/var/run"; then rm -rf $(DESTDIR)$(ZM_RUNDIR); fi )
	@-( if test "$(DESTDIR)$(ZM_TMPDIR)" != "/tmp"; then rm -rf $(DESTDIR)$(ZM_TMPDIR); fi )
	@-( if test "$(DESTDIR)$(ZM_LOGDIR)" != "/var/log"; then rm -rf $(DESTDIR)$(ZM_LOGDIR); fi )
