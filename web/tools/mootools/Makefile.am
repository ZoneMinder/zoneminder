AUTOMAKE_OPTIONS = gnu

webdir = @WEB_PREFIX@/tools/mootools

dist_web_DATA = \
	mootools-1.2.3-core-nc.js \
	mootools-1.2.3-core-yc.js \
	mootools-1.2.3.1-more-nc.js \
	mootools-1.2.3.1-more-yc.js

# Yes, you are correct. This is a HACK!
install-data-hook:
	( cd $(DESTDIR)$(webdir); rm -f mootools-core.js mootools-more.js )
	( cd $(DESTDIR)$(webdir); ln -s mootools-1.2.3-core-nc.js mootools-core.js )
	( cd $(DESTDIR)$(webdir); ln -s mootools-1.2.3.1-more-nc.js mootools-more.js )

uninstall-hook:
	@-( cd $(DESTDIR)$(webdir); rm -f mootools-* )
