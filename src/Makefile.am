AUTOMAKE_OPTIONS = gnu

LDADD =    @MYSQL_LIBS@
CPPFLAGS = @MYSQL_CFLAGS@

# You need to set this to your CGI directory
cgidir = /var/www/cgi-bin
# And these to the user and group of your webserver
webuser = apache
webgroup = apache

cgi_PROGRAMS = zms
bin_PROGRAMS = zmc zma zmu

zm_SOURCES = zm.cpp zmdbg.c jmemdst.c
zmc_SOURCES = zmc.cpp $(zm_SOURCES)
zma_SOURCES = zma.cpp $(zm_SOURCES)
zms_SOURCES = zms.cpp $(zm_SOURCES)
zmu_SOURCES = zmu.cpp $(zm_SOURCES)

noinst_HEADERS = zm.h zmcfg.h zmdbg.h jinclude.h font_6x11.h

EXTRA_DIST = 

# Yes, you are correct. This is a HACK!
install-exec-local:
	( cd @bindir@; mv $(cgi_PROGRAMS) $(cgidir) )
	( cd $(cgidir); chown $(webuser):$(webgroup) $(cgi_PROGRAMS) )
