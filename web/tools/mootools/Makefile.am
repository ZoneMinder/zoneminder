AUTOMAKE_OPTIONS = gnu

webdir = @WEB_PREFIX@/tools/mootools

dist_web_DATA = \
	mootools-core-1.4.5-compat.js \
	mootools-more-1.5.1.js

# Yes, you are correct. This is a HACK!
install-data-hook:
	( cd $(DESTDIR)$(webdir); rm -f mootools-core.js mootools-more.js )
	( cd $(DESTDIR)$(webdir); ln -sf mootools-core-1.4.5-compat.js mootools-core.js )
	( cd $(DESTDIR)$(webdir); ln -sf mootools-more-1.5.1.js mootools-more.js )

uninstall-hook:
	@-( cd $(DESTDIR)$(webdir); rm -f mootools-* )
