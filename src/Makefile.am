AUTOMAKE_OPTIONS = gnu

LDADD =    @MYSQL_LIBS@
CPPFLAGS = @MYSQL_CFLAGS@

cgidir = /var/www/cgi-bin
bin_PROGRAMS = zmc zma zmu
cgi_PROGRAMS = zms

zm_SOURCES = zm.cpp zmdbg.c jmemdst.c
zmc_SOURCES = zmc.cpp $(zm_SOURCES)
zma_SOURCES = zma.cpp $(zm_SOURCES)
zms_SOURCES = zms.cpp $(zm_SOURCES)
zmu_SOURCES = zmu.cpp $(zm_SOURCES)

noinst_HEADERS = zm.h zmcfg.h zmdbg.h jinclude.h font_6x11.h

EXTRA_DIST = 
