AUTOMAKE_OPTIONS = gnu

AM_CPPFLAGS = @MYSQL_CFLAGS@ @MARIADB_CFLAGS@ @FFMPEG_CFLAGS@ -Wall -finline-functions -fomit-frame-pointer
# Compile zm binaries with -rdynamic to enable visiblily of global static
# variables in shared libraries (plugins)
AM_CXXFLAGS = -rdynamic

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
	zmstreamer

zm_SOURCES = \
	zm_box.cpp \
	zm_buffer.cpp \
	zm_camera.cpp \
	zm_comms.cpp \
	zm_coord.cpp \
	zm_curl_camera.cpp \
	zm.cpp \
	zm_event.cpp \
	zm_exception.cpp \
	zm_file_camera.cpp \
	zm_ffmpeg_camera.cpp \
    zm_libvlc_camera.cpp \
	zm_local_camera.cpp \
	zm_monitor.cpp \
	zm_ffmpeg.cpp \
	zm_mpeg.cpp \
	zm_regexp.cpp \
	zm_remote_camera.cpp \
	zm_remote_camera_http.cpp \
	zm_remote_camera_rtsp.cpp \
	zm_rtp.cpp \
	zm_rtp_ctrl.cpp \
	zm_rtp_data.cpp \
	zm_rtp_source.cpp \
	zm_rtsp.cpp \
	zm_rtsp_auth.cpp \
	zm_sdp.cpp \
	zm_signal.cpp \
	zm_stream.cpp \
	zm_time.cpp \
	zm_timer.cpp \
	zm_user.cpp \
	$(SOURCES_common)

# These source files are used both for zm binaries and the libzmplugins
# convenience library
SOURCES_common = \
	zm_config.cpp \
	zm_db.cpp \
	zm_image.cpp \
	zm_jpeg.cpp \
	zm_logger.cpp \
	zm_poly.cpp \
	zm_utils.cpp \
	zm_thread.cpp \
	zm_zone.cpp

# These header files are used both for zm binaries and the libzmplugins
# convenience library
HEADERS_common = \
	jinclude.h \
	zm.h \
	zm_box.h \
	zm_config_defines.h \
	zm_config.h \
	zm_coord.h \
	zm_db.h \
	zm_event.h \
	zm_ffmpeg.h \
	zm_image.h \
	zm_jpeg.h \
	zm_logger.h \
	zm_mem_utils.h \
	zm_mpeg.h \
	zm_poly.h \
	zm_rgb.h \
	zm_stream.h \
	zm_utils.h \
	zm_zone.h

zmc_SOURCES = zmc.cpp $(zm_SOURCES)
zma_SOURCES = zma.cpp $(zm_SOURCES)
zms_SOURCES = zms.cpp $(zm_SOURCES)
zmu_SOURCES = zmu.cpp $(zm_SOURCES)
zmf_SOURCES = zmf.cpp $(zm_SOURCES)
zmstreamer_SOURCES = zmstreamer.cpp $(zm_SOURCES)

# These libraries are linked only to zm binaries in order to avoid detection
# of unwanted dependencies during plugin packaging
zm_LDADD = @DL_LIBS@ @PCRE_LIBS@ @CURL_LIBS@ @BZ2_LIBS@ @X264_LIBS@  \
	@SWSCALE_LIBS@ @AVFORMAT_LIBS@ @AVCODEC_LIBS@ @AVUTIL_LIBS@ \
	@AVDEVICE_LIBS@ @AVCORE_LIBS@ @VLC_LIBS@ @CRYPTO_LIBS@ @GCRYPT_LIBS@ \
	@GNUTLS_LIBS@ @GNUTLS_OPENSSL_LIBS@

zmc_LDADD = $(zm_LDADD)
zma_LDADD = $(zm_LDADD)
zms_LDADD = $(zm_LDADD)
zmu_LDADD = $(zm_LDADD)
zmf_LDADD = $(zm_LDADD)
zmstreamer_LDADD = $(zm_LDADD)

noinst_HEADERS = \
	zm_buffer.h \
	zm_camera.h \
	zm_comms.h \
	zm_curl_camera.h \
	zm_exception.h \
	zmf.h \
	zm_file_camera.h \
	zm_ffmpeg_camera.h \
	zm_font.h \
    zm_libvlc_camera.h \
	zm_local_camera.h \
	zm_monitor.h \
	zm_regexp.h \
	zm_remote_camera.h \
	zm_remote_camera_http.h \
	zm_remote_camera_rtsp.h \
	zm_rtp_ctrl.h \
	zm_rtp_data.h \
	zm_rtp.h \
	zm_rtp_source.h \
	zm_rtsp.h \
	zm_sdp.h \
	zm_signal.h \
	zm_thread.h \
	zm_time.h \
	zm_timer.h \
	zm_user.h

if ZM_HAS_PLUGIN_SUPPORT
# Add objects to zm binaries for plugins management
zm_SOURCES += \
	zm_detector.cpp \
	zm_image_analyser.cpp \
	zm_plugin.cpp \
	zm_plugin_manager.cpp

# Build a convenience library for plugin development
noinst_LTLIBRARIES = libzmplugins.la
libzmplugins_la_SOURCES = zm_detector.cpp $(SOURCES_common)

# A hack to avoid conflicts between objects created both with libtool
# and without (objects will be prefixed with "libzmplugins_la")
libzmplugins_la_CPPFLAGS = $(AM_CPPFLAGS)

# Install the necessary headers for plugin development
pkginclude_HEADERS = \
	zm_detector.h \
	zm_image_analyser.h \
	zm_plugin.h \
	zm_plugin_manager.h \
	$(HEADERS_common)

# Install a pkg-config file for a proper handling of the libzmplugins
# convenience library
pkgconfigdir = $(libdir)/pkgconfig
pkgconfig_DATA = libzmplugins.pc
else
# Don't intall plugin headers if no support
noinst_HEADERS += \
	zm_detector.h \
	zm_plugin.h \
	zm_plugin_manager.h \
	zm_image_analyser.h \
	$(HEADERS_common)
endif

EXTRA_DIST = \
	zm_config.h.in \
	zm_threaddata.cpp

dist-hook:
	@( rm $(distdir)/zm_config.h )

# Yes, you are correct. This is a HACK!
# And the manual installation of libzmplugins.a is necessary because libtool has
# no option to force installation of convenience libraries.
install-exec-hook:
	( cd $(DESTDIR)@bindir@; mkdir -p $(DESTDIR)$(cgidir); mv zms $(DESTDIR)$(cgidir) )
	( cd $(DESTDIR)$(cgidir); chown $(webuser):$(webgroup) zms; ln -f zms nph-zms )
	install -D -m 644 .libs/libzmplugins.a $(DESTDIR)$(libdir)/libzmplugins.a

uninstall-hook:
	( cd $(DESTDIR)$(cgidir); rm -f zms nph-zms )
	( cd $(DESTDIR)$(libdir); rm -f libzmplugins.a )

