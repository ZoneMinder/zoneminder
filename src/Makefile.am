AUTOMAKE_OPTIONS = gnu

LDADD = @MYSQL_LIBS@
AM_CPPFLAGS = @MYSQL_CFLAGS@ @FFMPEG_CFLAGS@

# This should be set to your CGI directory
cgidir = @CGI_PREFIX@
# And these to the user and group of your webserver
webuser = @WEB_USER@
webgroup = @WEB_GROUP@

bin_PROGRAMS = \
	zmc \
	zma \
	zmu \
	zms \
	zmf \
	zmfix

zm_SOURCES = \
	zm.cpp \
	zm_db.cpp \
	zm_config.cpp \
	zm_coord.cpp \
	zm_box.cpp \
	zm_image.cpp \
	zm_event.cpp \
	zm_zone.cpp \
	zm_camera.cpp \
	zm_local_camera.cpp \
	zm_remote_camera.cpp \
	zm_monitor.cpp \
	zm_mpeg.cpp \
	zm_debug.c \
	zm_jpeg.c

zmc_SOURCES = zmc.cpp $(zm_SOURCES)
zma_SOURCES = zma.cpp $(zm_SOURCES)
zms_SOURCES = zms.cpp $(zm_SOURCES)
zmu_SOURCES = zmu.cpp $(zm_SOURCES)
zmf_SOURCES = zmf.cpp $(zm_SOURCES)
zmfix_SOURCES = zmfix.cpp zm_debug.c zm_db.cpp zm.cpp

noinst_HEADERS = \
	zm.h \
	zm_db.h \
	zm_font.h \
	zm_rgb.h \
	zm_config.h \
	zm_config_defines.h \
	zm_debug.h \
	zm_coord.h \
	zm_box.h \
	zm_image.h \
	zm_event.h \
	zm_zone.h \
	zm_camera.h \
	zm_local_camera.h \
	zm_remote_camera.h \
	zm_monitor.h \
	zm_font.h \
	jinclude.h \
	zmf.h \
	zm_jpeg.h \
	zm_mpeg.h

EXTRA_DIST = zm_config.h.z

dist-hook:
	@( rm $(distdir)/zm_config.h )

# Yes, you are correct. This is a HACK!
install-exec-hook:
	( cd @bindir@; mv zms $(cgidir) )
	( cd $(cgidir); chown $(webuser):$(webgroup) zms )
	( cd @bindir@; chmod u+s zmfix )
