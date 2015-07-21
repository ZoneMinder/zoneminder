AUTOMAKE_OPTIONS = gnu

pkglibdir = @ZM_PLUGINSPKGLIBDIR@

pkglib_LTLIBRARIES = libzm_plugin_openalpr.la

# This prevents to link the plugin with unnecessary libraries
LIBS=

AM_CPPFLAGS = \
	-I$(top_srcdir)/src $(opencv_CFLAGS)

libzm_plugin_openalpr_la_SOURCES = \
	openalpr_plugin.cpp

libzm_plugin_openalpr_la_LDFLAGS = \
	-avoid-version -module -shared -export-dynamic

libzm_plugin_openalpr_la_LIBADD = \
	@OPENALPR_LIBS@ @BOOST_PROGRAM_OPTION_LIBS@ @OPENCV_CORE_LIBS@ \
	    @OPENCV_IMGPROC_LIBS@

noinst_HEADERS = \
	openalpr_plugin.h
