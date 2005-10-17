AUTOMAKE_OPTIONS = gnu

# This should be set to your web directory
webdir = @WEB_PREFIX@/graphics
# And these to the user and group of your webserver
webuser = @WEB_USER@
webgroup = @WEB_GROUP@

web_DATA = \
	arrow-d.gif \
	arrow-dl.gif \
	arrow-dr.gif \
	arrow-l.gif \
	arrow-r.gif \
	arrow-u.gif \
	arrow-ul.gif \
	arrow-ur.gif \
	arrow-l-u.gif \
	arrow-l-d.gif \
	center.gif \
	seq-u.gif \
	seq-d.gif

EXTRA_DIST = $(web_DATA)

# Yes, you are correct. This is a HACK!
install-data-hook:
	( cd $(DESTDIR)$(webdir); chown $(webuser):$(webgroup) $(web_DATA) )
