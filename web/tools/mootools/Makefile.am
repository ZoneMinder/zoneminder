AUTOMAKE_OPTIONS = gnu

webdir = @WEB_PREFIX@/tools/mootools

dist_web_DATA = \
	mootools-core-1.3.2-nc.js \
	mootools-core-1.3.2-yc.js \
	mootools-more-1.3.2.1-nc.js \
	mootools-more-1.3.2.1-yc.js

# Yes, you are correct. This is a HACK!
install-data-hook:
	( cd $(DESTDIR)$(webdir); rm -f mootools-core.js mootools-more.js )
	( cd $(DESTDIR)$(webdir); ln -s mootools-core-1.3.2-yc.js mootools-core.js )
	( cd $(DESTDIR)$(webdir); ln -s mootools-more-1.3.2.1-yc.js mootools-more.js )

uninstall-hook:
	@-( cd $(DESTDIR)$(webdir); rm -f mootools-* )
