AUTOMAKE_OPTIONS = gnu

LDADD =    @MYSQL_LIBS@
CPPFLAGS = @MYSQL_CFLAGS@

# This should be set to your CGI directory
cgidir = @CGI_PREFIX@
# And these to the user and group of your webserver
webuser = @WEB_USER@
webgroup = @WEB_GROUP@

bin_PROGRAMS = zmc zma zmu zms

zm_SOURCES = zm.cpp zmdbg.c jmemdst.c
zmc_SOURCES = zmc.cpp $(zm_SOURCES)
zma_SOURCES = zma.cpp $(zm_SOURCES)
zms_SOURCES = zms.cpp $(zm_SOURCES)
zmu_SOURCES = zmu.cpp $(zm_SOURCES)

noinst_HEADERS = zm.h zmcfg.h zmdbg.h jinclude.h font_6x11.h

EXTRA_DIST = 

# Yes, you are correct. This is a HACK!
install-exec-local:
	( cd @bindir@; mv zms $(cgidir) )
	( cd $(cgidir); chown $(webuser):$(webgroup) zms )
