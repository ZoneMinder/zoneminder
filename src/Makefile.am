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

noinst_HEADERS = zm.h zmcfg.h zmdbg.h

# man page
#man_MANS = gdu.1x

# we want these in the dist tarball
#EXTRA_DIST = back.xpm reload.xpm root.xpm \
             #home.xpm stop.xpm about.xpm gdu.1x
