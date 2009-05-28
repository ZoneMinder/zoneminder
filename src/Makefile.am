AUTOMAKE_OPTIONS = gnu

AM_CPPFLAGS = @MYSQL_CFLAGS@ @FFMPEG_CFLAGS@ -Wall -Wno-sign-compare -fno-inline
AM_CXXFLAGS = -frepo

CLEANFILES = *.rpo

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
	zm_box.cpp \
	zm_buffer.cpp \
	zm_camera.cpp \
	zm_comms.cpp \
	zm_config.cpp \
	zm_coord.cpp \
	zm.cpp \
	zm_db.cpp \
	zm_debug.c \
	zm_event.cpp \
	zm_exception.cpp \
	zm_file_camera.cpp \
	zm_ffmpeg_camera.cpp \
	zm_image.cpp \
	zm_jpeg.c \
	zm_local_camera.cpp \
	zm_monitor.cpp \
	zm_ffmpeg.cpp \
	zm_mpeg.cpp \
	zm_poly.cpp \
	zm_regexp.cpp \
	zm_remote_camera.cpp \
	zm_remote_camera_http.cpp \
	zm_remote_camera_rtsp.cpp \
	zm_rtp.cpp \
	zm_rtp_ctrl.cpp \
	zm_rtp_data.cpp \
	zm_rtp_source.cpp \
	zm_rtsp.cpp \
	zm_sdp.cpp \
	zm_signal.cpp \
	zm_stream.cpp \
	zm_thread.cpp \
	zm_time.cpp \
	zm_timer.cpp \
	zm_user.cpp \
	zm_utils.cpp \
	zm_zone.cpp

zmc_SOURCES = zmc.cpp $(zm_SOURCES)
zma_SOURCES = zma.cpp $(zm_SOURCES)
zms_SOURCES = zms.cpp $(zm_SOURCES)
zmu_SOURCES = zmu.cpp $(zm_SOURCES)
zmf_SOURCES = zmf.cpp $(zm_SOURCES)
zmfix_SOURCES = zmfix.cpp zm_config.cpp zm_regexp.cpp zm_debug.c zm_db.cpp zm.cpp

noinst_HEADERS = \
	jinclude.h \
	zm_box.h \
	zm_buffer.h \
	zm_camera.h \
	zm_comms.h \
	zm_config_defines.h \
	zm_config.h \
	zm_coord.h \
	zm_db.h \
	zm_debug.h \
	zm_event.h \
	zm_exception.h \
	zmf.h \
	zm_file_camera.h \
	zm_ffmpeg_camera.h \
	zm_font.h \
	zm_font.h \
	zm.h \
	zm_image.h \
	zm_jpeg.h \
	zm_local_camera.h \
	zm_mem_utils.h \
	zm_monitor.h \
	zm_ffmpeg.h \
	zm_mpeg.h \
	zm_poly.h \
	zm_regexp.h \
	zm_remote_camera.h \
	zm_remote_camera_http.h \
	zm_remote_camera_rtsp.h \
	zm_rgb.h \
	zm_rtp_ctrl.h \
	zm_rtp_data.h \
	zm_rtp.h \
	zm_rtp_source.h \
	zm_rtsp.h \
	zm_sdp.h \
	zm_signal.h \
	zm_stream.h \
	zm_thread.h \
	zm_time.h \
	zm_timer.h \
	zm_user.h \
	zm_utils.h \
	zm_zone.h

EXTRA_DIST = \
	zm_config.h.in \
	zm_config_defines.h \
	zm_threaddata.cpp

dist-hook:
	@( rm $(distdir)/zm_config.h )

# Yes, you are correct. This is a HACK!
install-exec-hook:
	( cd $(DESTDIR)@bindir@; mkdir -p $(DESTDIR)$(cgidir); mv zms $(DESTDIR)$(cgidir) )
	( cd $(DESTDIR)$(cgidir); chown $(webuser):$(webgroup) zms; ln -f zms nph-zms )
	( cd $(DESTDIR)@bindir@; chmod u+s zmfix )

uninstall-hook:
	( cd $(DESTDIR)$(cgidir); rm -f zms nph-zms )

