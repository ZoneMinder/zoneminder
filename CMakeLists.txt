# Main CMake file for the ZoneMinder project.
# Created by mastertheknife (Kfir Itzhak)
# For more information and installation, see the INSTALL file
#
cmake_minimum_required(VERSION 3.5.0)
project(zoneminder)
file(STRINGS "version.txt" zoneminder_VERSION)
# make API version a minor of ZM version
set(zoneminder_API_VERSION "${zoneminder_VERSION}.1")

# Make sure the submodules are there
if(NOT EXISTS "${CMAKE_SOURCE_DIR}/web/api/app/Plugin/Crud/Lib/CrudControllerTrait.php")
  message(SEND_ERROR "The git submodules are not available. Please run git submodule update --init --recursive")
endif()

# CMake does not allow out-of-source build if CMakeCache.exists 
# in the source folder. Abort and notify the user
if((NOT (CMAKE_BINARY_DIR STREQUAL CMAKE_SOURCE_DIR)) AND (EXISTS "${CMAKE_SOURCE_DIR}/CMakeCache.txt"))
  message(FATAL_ERROR " You are attempting to do an out-of-source build,
    but a cmake cache file for an in-source build exists. Please delete
    the file CMakeCache.txt from the source folder to proceed.")
endif()

# Default build type. To change the build type, 
# use the CMAKE_BUILD_TYPE configuration option.
if(NOT CMAKE_BUILD_TYPE)
  set(CMAKE_BUILD_TYPE Release CACHE STRING "Build type: Release or Debug" FORCE)
endif()

# Can assist in troubleshooting
#set(CMAKE_VERBOSE_MAKEFILE ON)
#set(CMAKE_INSTALL_ALWAYS ON)

# Default CLFAGS and CXXFLAGS:
set(CMAKE_C_FLAGS_RELEASE "-O2")
set(CMAKE_CXX_FLAGS_RELEASE "-O2")
set(CMAKE_C_FLAGS_DEBUG "-g")
set(CMAKE_CXX_FLAGS_DEBUG "-g")
set(CMAKE_C_FLAGS_OPTIMISED "-O3")
set(CMAKE_CXX_FLAGS_OPTIMISED "-O3")
set(CMAKE_INCLUDE_CURRENT_DIR ON)
set(CMAKE_MODULE_PATH ${CMAKE_MODULE_PATH} "${CMAKE_SOURCE_DIR}/cmake/Modules/")
set(CMAKE_CXX_STANDARD 17)
set(CMAKE_CXX_STANDARD_REQUIRED ON)
set(CMAKE_EXPORT_COMPILE_COMMANDS ON)

add_compile_options(-D_FILE_OFFSET_BITS=64)

include(ConfigureBaseTargets)
include(CheckPlatform)

# GCC below 6.0 doesn't support __target__("fpu=neon") attribute, required for compiling ARM Neon code, otherwise compilation fails.
# Must use -mfpu=neon compiler flag instead, but only do that for processors that support neon, otherwise strip the neon code alltogether,
# because passing -fmpu=neon is unsafe to processors that don't support neon

# Arm neon support only tested on Linux. If your arm hardware is running a non-Linux distro and is using gcc then contact us.
if(ZM_SYSTEM_PROC MATCHES "^arm")
  if(CMAKE_COMPILER_IS_GNUCXX AND CMAKE_CXX_COMPILER_VERSION VERSION_LESS 6.0)
    exec_program(grep ARGS " neon " "/proc/cpuinfo" OUTPUT_VARIABLE neonoutput RETURN_VALUE neonresult)
    if(neonresult EQUAL 0)
      set(CMAKE_C_FLAGS_RELEASE "${CMAKE_C_FLAGS_RELEASE} -mfpu=neon")
      set(CMAKE_CXX_FLAGS_RELEASE "${CMAKE_CXX_FLAGS_RELEASE} -mfpu=neon")
      set(CMAKE_C_FLAGS_DEBUG "${CMAKE_C_FLAGS_DEBUG} -mfpu=neon")
      set(CMAKE_CXX_FLAGS_DEBUG "${CMAKE_CXX_FLAGS_DEBUG} -mfpu=neon")
    else()
      add_definitions(-DZM_STRIP_NEON=1)
      message(STATUS "ARM Neon is not available on this processor. Neon functions will be absent")
    endif()
  endif()
endif()

# Modules that we need:
include(GNUInstallDirs)
include(CheckIncludeFile)
include(CheckIncludeFiles)
include(CheckFunctionExists)
include(CheckTypeSize)
include(CheckSendfile)

# Configuration options
mark_as_advanced(
  FORCE ZM_EXTRA_LIBS
  ZM_MYSQL_ENGINE
  ZM_NO_MMAP
  CMAKE_INSTALL_FULL_BINDIR
  ZM_PERL_MM_PARMS
  ZM_PERL_SEARCH_PATH
  ZM_TARGET_DISTRO
  ZM_PATH_MAP
  ZM_PATH_ARP
  ZM_PATH_ARP_SCAN
  ZM_PATH_RM
  ZM_PATH_UNAME
  ZM_PATH_IP
  ZM_PATH_IFCONFIG
  ZM_CONFIG_DIR
  ZM_CONFIG_SUBDIR
  ZM_DETECT_SYSTEMD
  ZM_SYSTEMD
  ZM_MANPAGE_DEST_PREFIX)

option(ENABLE_WERROR "Fail the build if a compiler warning is emitted" 0)
option(BUILD_TEST_SUITE "Build the test suite" 0)
option(BUILD_MAN "Build man pages" 1)
option(ASAN "DEBUGGING: Build with AddressSanitizer (ASan) support" 0)
option(TSAN "DEBUGGING: Build with ThreadSanitizer (TSan) support" 0)

if(ASAN AND TSAN)
  message(FATAL_ERROR "ASAN and TSAN options are mutually exclusive")
endif()

set(ZM_RUNDIR "/var/run/zm" CACHE PATH
  "Location of transient process files, default: /var/run/zm")
set(ZM_SOCKDIR "/var/run/zm" CACHE PATH
  "Location of Unix domain socket files, default /var/run/zm")
set(ZM_TMPDIR "/var/tmp/zm" CACHE PATH
  "Location of temporary files, default: /tmp/zm")
set(ZM_LOGDIR "/var/log/zm" CACHE PATH
  "Location of generated log files, default: /var/log/zm")
set(ZM_WEBDIR "${CMAKE_INSTALL_FULL_DATADIR}/zoneminder/www" CACHE PATH
  "Location of the web files, default: <prefix>/${CMAKE_INSTALL_DATADIR}/zoneminder/www")
set(ZM_CGIDIR "${CMAKE_INSTALL_FULL_LIBEXECDIR}/zoneminder/cgi-bin" CACHE PATH
  "Location of the cgi-bin files, default: <prefix>/${CMAKE_INSTALL_LIBEXECDIR}/zoneminder/cgi-bin")
set(ZM_CACHEDIR "/var/cache/zoneminder" CACHE PATH
  "Location of the web server cache busting files, default: /var/cache/zoneminder")
set(ZM_CONTENTDIR "/var/lib/zoneminder" CACHE PATH
  "Location of dynamic content (events and images), default: /var/lib/zoneminder")
set(ZM_FONTDIR "${CMAKE_INSTALL_FULL_DATADIR}/zoneminder/fonts" CACHE PATH
  "Location of the font files used for timestamping, default: <prefix>/${CMAKE_INSTALL_DATADIR}/zoneminder/fonts")

set(ZM_DB_TYPE "mysql" CACHE STRING
  "Type of database where the ZoneMinder database located, default: mysql")
set(ZM_DB_HOST "localhost" CACHE STRING
  "Hostname where the ZoneMinder database located, default: localhost")
set(ZM_DB_NAME "zm" CACHE STRING
  "Name of the ZoneMinder database, default: zm")
set(ZM_DB_USER "zmuser" CACHE STRING
  "Name of the ZoneMinder database user, default: zmuser")
set(ZM_DB_PASS "zmpass" CACHE STRING
  "Password of the ZoneMinder database user, default: zmpass")
set(ZM_WEB_USER "" CACHE STRING
  "The user apache or the local web server runs on. Leave empty for automatic detection.
     If that fails, you can use this variable to force")
set(ZM_WEB_GROUP "" CACHE STRING
  "The group apache or the local web server runs on,
    Leave empty to be the same as the web user")
set(ZM_DIR_EVENTS "${ZM_CONTENTDIR}/events" CACHE PATH
  "Location where events are recorded to, default: ZM_CONTENTDIR/events")
set(ZM_DIR_SOUNDS "sounds" CACHE PATH
  "Location to look for optional sound files, default: sounds")
set(ZM_PATH_ZMS "/cgi-bin/nph-zms" CACHE PATH
  "Web url to zms streaming server, default: /cgi-bin/nph-zms")
set(ZM_PATH_API "/zm/api" CACHE PATH
  "Web url to api, default: /zm/api")
set(ZM_PATH_SHUTDOWN "/sbin/shutdown" CACHE PATH
  "Path to shutdown binary, default: /sbin/shutdown")

# Advanced
set(ZM_PATH_MAP "/dev/shm" CACHE PATH
  "Location to save mapped memory files, default: /dev/shm")
set(ZM_PATH_ARP "" CACHE PATH
  "Full path to compatible arp binary. Leave empty for automatic detection.")
set(ZM_PATH_ARP_SCAN "" CACHE PATH
  "Full path to compatible scan_arp binary. Leave empty for automatic detection.")
set(ZM_PATH_RM "" CACHE PATH
  "Full path to compatible rm binary. Leave empty for automatic detection.")
set(ZM_PATH_UNAME "" CACHE PATH
  "Full path to compatible uname binary. Leave empty for automatic detection.")
set(ZM_PATH_IP "" CACHE PATH
  "Full path to compatible ip binary. Leave empty for automatic detection.")
set(ZM_PATH_IFCONFIG "" CACHE PATH
  "Full path to compatible ifconfig binary. Leave empty for automatic detection.")
set(ZM_CONFIG_DIR "/${CMAKE_INSTALL_SYSCONFDIR}" CACHE PATH
  "Location of ZoneMinder configuration, default system config directory")
set(ZM_CONFIG_SUBDIR "${ZM_CONFIG_DIR}/conf.d" CACHE PATH
  "Location of ZoneMinder configuration subfolder, default: ZM_CONFIG_DIR/conf.d")
set(ZM_EXTRA_LIBS "v4l2" CACHE STRING
  "A list of optional libraries, separated by semicolons, e.g. ssl;theora")
set(ZM_MYSQL_ENGINE "InnoDB" CACHE STRING
  "MySQL engine to use with database, default: InnoDB")
set(ZM_NO_MMAP "OFF" CACHE BOOL
  "Set to ON to not use mmap shared memory. Shouldn't be enabled unless you
    experience problems with the shared memory. default: OFF")
set(ZM_NO_LIBVLC "OFF" CACHE BOOL
  "Set to ON to skip libvlc checks and force building ZM without libvlc. default: OFF")
set(ZM_NO_LIBVNC "OFF" CACHE BOOL
  "Set to ON to skip libvnc checks and force building ZM without libvnc. default: OFF")
set(ZM_NO_CURL "OFF" CACHE BOOL
  "Set to ON to skip cURL checks and force building ZM without cURL. default: OFF")
set(ZM_NO_X10 "OFF" CACHE BOOL
  "Set to ON to build ZoneMinder without X10 support. default: OFF")
set(ZM_ONVIF "ON" CACHE BOOL
  "Set to ON to enable basic ONVIF support. This is EXPERIMENTAL and may not
    work with all cameras claiming to be ONVIF compliant. default: ON")
set(ZM_NO_PCRE "OFF" CACHE BOOL
  "Set to ON to skip libpcre3 checks and force building ZM without libpcre3. default: OFF")
set(ZM_NO_RTSPSERVER "OFF" CACHE BOOL
  "Set to ON to skip building ZM with rtsp server support. default: OFF")
set(ZM_PERL_MM_PARMS INSTALLDIRS=vendor NO_PACKLIST=1 NO_PERLLOCAL=1 CACHE STRING
  "By default, ZoneMinder's Perl modules are installed into the Vendor folders,
	as defined by your installation of Perl. You can change that here. Consult Perl's 
	MakeMaker documentation for a definition of acceptable parameters. If you set this 
	to something that causes the modules to be installed outside Perl's normal search 
	path, then you will also need to set ZM_PERL_SEARCH_PATH accordingly.")
set(ZM_PERL_SEARCH_PATH "" CACHE PATH
  "Use to add a folder to your Perl's search path. This will need to be set in cases
	where ZM_PERL_MM_PARMS has been modified such that ZoneMinder's Perl modules are 
	installed outside Perl's default search path.")
set(ZM_TARGET_DISTRO "" CACHE STRING
  "Build ZoneMinder for a specific distribution.  Currently, valid names are: fc, el, OS13, FreeBSD")
set(ZM_DETECT_SYSTEMD "ON" CACHE BOOL
  "Set to OFF to disable detection of systemd. default: ON")
set(ZM_SYSTEMD "OFF" CACHE BOOL
  "Set to ON to force building ZM with systemd support. default: OFF")
set(ZM_MANPAGE_DEST_PREFIX "share/man" CACHE PATH
  "Relative path used to install ZoneMinder's Man pages into a
    non-standard folder. Most Linux users will not need to change this.
    BSD users may need to set this.")
set(ZM_CAKEPHP_CACHE "Apc" CACHE STRING
  "Set the CakePHP cache engine, default: Apc")

# Supported crypto backends. Using OpenSSL by default to be compatible with jwt-cpp.
set(ZM_CRYPTO_BACKEND_OPTIONS gnutls openssl)
set(ZM_CRYPTO_BACKEND openssl CACHE STRING "Determines which crypto backend should be used.")
set_property(CACHE ZM_CRYPTO_BACKEND PROPERTY STRINGS ${ZM_CRYPTO_BACKEND_OPTIONS})

if(NOT ZM_CRYPTO_BACKEND IN_LIST ZM_CRYPTO_BACKEND_OPTIONS)
  message(FATAL_ERROR "Invalid value for ZM_CRYPTO_BACKEND. Possible options: ${ZM_CRYPTO_BACKEND_OPTIONS}")
endif()

# Supported JWT backends. Using jwt-cpp as default.
set(ZM_JWT_BACKEND_OPTIONS libjwt jwt_cpp)
set(ZM_JWT_BACKEND jwt_cpp CACHE STRING "Determines which JWT backend should be used.")
set_property(CACHE ZM_JWT_BACKEND PROPERTY STRINGS ${ZM_JWT_BACKEND_OPTIONS})

if(NOT ZM_JWT_BACKEND IN_LIST ZM_JWT_BACKEND_OPTIONS)
  message(FATAL_ERROR "Invalid value for ZM_JWT_BACKEND. Possible options: ${ZM_JWT_BACKEND_OPTIONS}")
endif()

if((ZM_TARGET_DISTRO MATCHES "^el") OR (ZM_TARGET_DISTRO MATCHES "^fc"))
  set(ZM_RUNDIR "/run/zoneminder")
  set(ZM_SOCKDIR "/var/lib/zoneminder/sock")
  set(ZM_TMPDIR "/var/lib/zoneminder/temp")
  set(ZM_LOGDIR "/var/log/zoneminder")
  set(ZM_CONFIG_DIR "/etc/zm")
  set(ZM_CONFIG_SUBDIR "/etc/zm/conf.d")
  set(ZM_WEBDIR "/usr/share/zoneminder/www")
  set(ZM_CGIDIR "/usr/libexec/zoneminder/cgi-bin")
  set(ZM_DIR_EVENTS "/var/lib/zoneminder/events")
  set(ZM_PATH_ZMS "/cgi-bin-zm/nph-zms")
elseif(ZM_TARGET_DISTRO STREQUAL "OS13")
  set(ZM_RUNDIR "/var/run/zoneminder")
  set(ZM_TMPDIR "/var/run/zoneminder")
  set(ZM_CONTENTDIR "/var/run/zoneminder")
  set(ZM_LOGDIR "/var/log/zoneminder")
  set(ZM_WEB_USER "wwwrun")
  set(ZM_WEB_GROUP "www")
  set(ZM_WEBDIR "/srv/www/htdocs/zoneminder")
  set(ZM_CGIDIR "/srv/www/cgi-bin")
elseif(ZM_TARGET_DISTRO STREQUAL "FreeBSD")
  set(ZM_RUNDIR "/var/run/zm")
  set(ZM_SOCKDIR "/var/run/zm")
  set(ZM_TMPDIR "/var/tmp/zm")
  set(ZM_CONTENTDIR "/usr/local/var/lib/zoneminder")
  set(ZM_WEB_USER "www")
  set(ZM_WEB_GROUP "www")
  set(ZM_CONFIG_DIR "/usr/local/etc/zm")
  set(ZM_CONFIG_SUBDIR "/usr/local/etc/zm/conf.d")
  set(ZM_WEBDIR "/usr/local/share/zoneminder/www")
  set(ZM_CGIDIR "/usr/local/libexec/zoneminder/cgi-bin")
  set(ZM_PERL_MM_PARMS "INSTALLDIRS=site")
endif()

if(BUILD_MAN)
  message(STATUS "Building man pages: Yes (default)")
  set(ZM_PERL_MM_PARMS_FULL ${ZM_PERL_MM_PARMS})
else()
  message(STATUS "Building man pages: No")
  list(APPEND ZM_PERL_MM_PARMS_FULL ${ZM_PERL_MM_PARMS}
    "INSTALLMAN1DIR=none"
    "INSTALLMAN3DIR=none")
endif()

# Required for certain checks to work
set(CMAKE_EXTRA_INCLUDE_FILES ${CMAKE_EXTRA_INCLUDE_FILES} stdio.h stdlib.h math.h signal.h)
# This is required to enable searching in lib64 (if exists), do not change
set_property(GLOBAL PROPERTY FIND_LIBRARY_USE_LIB64_PATHS ON)

# Set the systemd flag if systemd is autodetected or ZM_SYSTEMD has been set
if(ZM_SYSTEMD OR (ZM_DETECT_SYSTEMD AND ((IS_DIRECTORY /usr/lib/systemd/system) OR (IS_DIRECTORY /lib/systemd/system))))
  set(WITH_SYSTEMD 1)
endif()

# System checks
check_include_file("execinfo.h" HAVE_EXECINFO_H)
if(HAVE_EXECINFO_H)
  check_function_exists("backtrace" HAVE_DECL_BACKTRACE)
  if(NOT HAVE_DECL_BACKTRACE)
    find_library(EXECINFO_LIBRARY NAMES execinfo)
    if(EXECINFO_LIBRARY)
      list(APPEND ZM_BIN_LIBS "-lexecinfo")
    endif()
  endif()
  check_function_exists("backtrace_symbols" HAVE_DECL_BACKTRACE_SYMBOLS)
endif()
check_include_file("ucontext.h" HAVE_UCONTEXT_H)
check_include_file("sys/sendfile.h" HAVE_SYS_SENDFILE_H)
check_include_file("sys/syscall.h" HAVE_SYS_SYSCALL_H)
check_function_exists("syscall" HAVE_SYSCALL)
check_function_exists("sendfile" HAVE_SENDFILE)
check_function_exists("posix_memalign" HAVE_POSIX_MEMALIGN)
check_type_size("siginfo_t" HAVE_SIGINFO_T)
check_type_size("ucontext_t" HAVE_UCONTEXT_T)

# *** LIBRARY CHECKS ***

# zlib
find_package(ZLIB)
if(ZLIB_FOUND)
  set(HAVE_LIBZLIB 1)
  list(APPEND ZM_BIN_LIBS "${ZLIB_LIBRARIES}")
  include_directories("${ZLIB_INCLUDE_DIR}")
  set(CMAKE_REQUIRED_INCLUDES "${ZLIB_INCLUDE_DIR}")
  check_include_file("zlib.h" HAVE_ZLIB_H)
  set(optlibsfound "${optlibsfound} zlib")
else()
  set(optlibsnotfound "${optlibsnotfound} zlib")
endif()

# Do not check for cURL if ZM_NO_CURL is on
if(NOT ZM_NO_CURL)
  # cURL
  find_package(CURL REQUIRED)
  if(CURL_FOUND)
    set(HAVE_LIBCURL 1)
    list(APPEND ZM_BIN_LIBS ${CURL_LIBRARIES})
    include_directories(${CURL_INCLUDE_DIRS})
    set(CMAKE_REQUIRED_INCLUDES ${CURL_INCLUDE_DIRS})
    check_include_file("curl/curl.h" HAVE_CURL_CURL_H)
    set(optlibsfound "${optlibsfound} cURL")
  else()
    set(optlibsnotfound "${optlibsnotfound} cURL")
  endif()
endif()

# jpeg
find_package(JPEG)
if(JPEG_FOUND)
  set(HAVE_LIBJPEG 1)
  list(APPEND ZM_BIN_LIBS "${JPEG_LIBRARIES}")
  #link_directories(${JPEG_LIBRARY})
  include_directories("${JPEG_INCLUDE_DIR}")
  set(CMAKE_REQUIRED_INCLUDES "${JPEG_INCLUDE_DIR}")
  check_include_files("stdio.h;jpeglib.h" HAVE_JPEGLIB_H)
  if(NOT HAVE_JPEGLIB_H)
    message(FATAL_ERROR
      "ZoneMinder requires libjpeg headers - check that libjpeg development packages are installed")
  endif()
else()
  message(FATAL_ERROR
    "ZoneMinder requires jpeg but it was not found on your system")
endif()

# libjwt
if (${ZM_JWT_BACKEND} STREQUAL "libjwt")
  find_package(LibJWT REQUIRED COMPONENTS ${ZM_CRYPTO_BACKEND})
  if(LIBJWT_FOUND)
    set(HAVE_LIBJWT 1)
    set(optlibsfound "${optlibsfound} LIBJWT")
  else()
    set(optlibsnotfound "${optlibsnotfound} LIBJWT")
  endif()
endif()

# GnuTLS
if (${ZM_CRYPTO_BACKEND} STREQUAL "gnutls")
  find_library(GNUTLS_LIBRARIES gnutls REQUIRED)
  if(GNUTLS_LIBRARIES)
    set(HAVE_LIBGNUTLS 1)
    list(APPEND ZM_BIN_LIBS "${GNUTLS_LIBRARIES}")
    find_path(GNUTLS_INCLUDE_DIR gnutls/gnutls.h)
    if(GNUTLS_INCLUDE_DIR)
      include_directories("${GNUTLS_INCLUDE_DIR}")
      set(CMAKE_REQUIRED_INCLUDES "${GNUTLS_INCLUDE_DIR}")
    endif()
    mark_as_advanced(FORCE GNUTLS_LIBRARIES GNUTLS_INCLUDE_DIR)
    set(optlibsfound "${optlibsfound} GnuTLS")
  else()
    set(optlibsnotfound "${optlibsnotfound} GnuTLS")
  endif()
# OpenSSL
elseif (${ZM_CRYPTO_BACKEND} STREQUAL "openssl")
  find_package(OpenSSL REQUIRED)
  if(OPENSSL_FOUND)
    set(HAVE_LIBOPENSSL 1)
    list(APPEND ZM_BIN_LIBS "${OPENSSL_LIBRARIES}")
    include_directories("${OPENSSL_INCLUDE_DIR}")
    set(CMAKE_REQUIRED_INCLUDES "${OPENSSL_INCLUDE_DIR}")
    set(optlibsfound "${optlibsfound} OpenSSL")
  else()
    set(optlibsnotfound "${optlibsnotfound} OpenSSL")
  endif()
endif()

# pthread (using find_library and find_path)
find_library(PTHREAD_LIBRARIES pthread)
if(PTHREAD_LIBRARIES)
  set(HAVE_LIBPTHREAD 1)
  list(APPEND ZM_BIN_LIBS "${PTHREAD_LIBRARIES}")
  find_path(PTHREAD_INCLUDE_DIR pthread.h)
  if(PTHREAD_INCLUDE_DIR)
    include_directories("${PTHREAD_INCLUDE_DIR}")
    set(CMAKE_REQUIRED_INCLUDES "${PTHREAD_INCLUDE_DIR}")
  endif()
  mark_as_advanced(FORCE PTHREAD_LIBRARIES PTHREAD_INCLUDE_DIR)
  check_include_file("pthread.h" HAVE_PTHREAD_H)
  if(NOT HAVE_PTHREAD_H)
    message(FATAL_ERROR "ZoneMinder requires pthread headers - check that pthread development packages are installed")
  endif()
else()
  message(FATAL_ERROR "ZoneMinder requires pthread but it was not found on your system")
endif()

# Do not check for cURL if ZM_NO_CURL is on
if(NOT ZM_NO_PRCE)
  # pcre (using find_library and find_path)
  find_library(PCRE_LIBRARIES pcre)
  if(PCRE_LIBRARIES)
    set(HAVE_LIBPCRE 1)
    list(APPEND ZM_BIN_LIBS "${PCRE_LIBRARIES}")
    find_path(PCRE_INCLUDE_DIR pcre.h)
    if(PCRE_INCLUDE_DIR)
      include_directories("${PCRE_INCLUDE_DIR}")
      set(CMAKE_REQUIRED_INCLUDES "${PCRE_INCLUDE_DIR}")
    endif()
    mark_as_advanced(FORCE PCRE_LIBRARIES PCRE_INCLUDE_DIR)
    check_include_file("pcre.h" HAVE_PCRE_H)
    set(optlibsfound "${optlibsfound} PCRE")
  else()
    set(optlibsnotfound "${optlibsnotfound} PCRE")
  endif()
endif()

if(NOT ZM_NO_MQTT)
  find_package(MOSQUITTO)
  if(MOSQUITTO_FOUND)
    include_directories(${MOSQUITTO_INCLUDE_DIRS})
    list(APPEND ZM_BIN_LIBS "${MOSQUITTO_LIBRARY}")
    set(optlibsfound "${optlibsfound} Mosquitto")
  else()
    set(optlibsnotfound "${optlibsnotfound} Mosquitto")
  endif (MOSQUITTO_FOUND)

  find_package(MOSQUITTOPP)
  if(MOSQUITTOPP_FOUND)
    include_directories(${MOSQUITTOPP_INCLUDE_DIRS})
    list(APPEND ZM_BIN_LIBS "${MOSQUITTOPP_LIBRARY}")
    set(optlibsfound "${optlibsfound} Mosquittopp")
  else()
    set(optlibsnotfound "${optlibsnotfound} Mosquittopp")
  endif (MOSQUITTOPP_FOUND)
endif()

# mysqlclient (using find_library and find_path)
  find_library(MYSQLCLIENT_LIBRARIES mysqlclient PATH_SUFFIXES mysql)
if(MYSQLCLIENT_LIBRARIES)
  set(HAVE_LIBMYSQLCLIENT 1)
  list(APPEND ZM_BIN_LIBS "${MYSQLCLIENT_LIBRARIES}")
  find_path(MYSQLCLIENT_INCLUDE_DIR mysql.h PATH_SUFFIXES mysql)
  if(MYSQLCLIENT_INCLUDE_DIR)
    include_directories("${MYSQLCLIENT_INCLUDE_DIR}")
    set(CMAKE_REQUIRED_INCLUDES "${MYSQLCLIENT_INCLUDE_DIR}")
  endif()
  mark_as_advanced(FORCE MYSQLCLIENT_LIBRARIES MYSQLCLIENT_INCLUDE_DIR)
  check_include_file("mysql.h" HAVE_MYSQL_H)
  if(NOT HAVE_MYSQL_H)
    message(FATAL_ERROR "ZoneMinder requires MySQL headers - check that MySQL development packages are installed")
  endif()
else()
  message(FATAL_ERROR "ZoneMinder requires mysqlclient but it was not found on your system")
endif()

find_package(FFMPEG 55.34.100 REQUIRED
  COMPONENTS
    avcodec
    avdevice
    avformat
    avutil
    swresample
    swscale)

set(CMAKE_REQUIRED_INCLUDES ${FFMPEG_avutil_INCLUDE_DIRS})
check_include_file("libavutil/hwcontext.h" HAVE_LIBAVUTIL_HWCONTEXT_H)

set(PATH_FFMPEG "")
set(OPT_FFMPEG "no")

# Find the path to the ffmpeg executable
find_program(FFMPEG_EXECUTABLE
  NAMES ffmpeg avconv
  PATH_SUFFIXES ffmpeg)
if(FFMPEG_EXECUTABLE)
  set(PATH_FFMPEG "${FFMPEG_EXECUTABLE}")
  set(OPT_FFMPEG "yes")
  mark_as_advanced(FFMPEG_EXECUTABLE)
endif()

# Do not check for libvlc if ZM_NO_LIBVLC is on
if(NOT ZM_NO_LIBVLC)
  # libvlc (using find_library and find_path)
  find_library(LIBVLC_LIBRARIES vlc)
  if(LIBVLC_LIBRARIES)
    set(HAVE_LIBVLC 1)
    #list(APPEND ZM_BIN_LIBS "${LIBVLC_LIBRARIES}")
    find_path(LIBVLC_INCLUDE_DIR "vlc/vlc.h")
    if(LIBVLC_INCLUDE_DIR)
      include_directories("${LIBVLC_INCLUDE_DIR}")
      set(CMAKE_REQUIRED_INCLUDES "${LIBVLC_INCLUDE_DIR}")
    endif()
    mark_as_advanced(FORCE LIBVLC_LIBRARIES LIBVLC_INCLUDE_DIR)
    check_include_file("vlc/vlc.h" HAVE_VLC_VLC_H)
    set(optlibsfound "${optlibsfound} libVLC")
  else()
    set(optlibsnotfound "${optlibsnotfound} libVLC")
  endif()
endif()

if(NOT ZM_NO_LIBVNC)
  # libvncclient (using find_library and find_path)
  find_library(LIBVNC_LIBRARIES vncclient)
  if(LIBVNC_LIBRARIES)
    set(HAVE_LIBVNC 1)
    #list(APPEND ZM_BIN_LIBS "${LIBVNC_LIBRARIES}")
    find_path(LIBVNC_INCLUDE_DIR "rfb/rfb.h")
    if(LIBVNC_INCLUDE_DIR)
      include_directories("${LIBVNC_INCLUDE_DIR}")
      set(CMAKE_REQUIRED_INCLUDES "${LIBVNC_INCLUDE_DIR}")
    endif()
    mark_as_advanced(FORCE LIBVNC_LIBRARIES LIBVNC_INCLUDE_DIR)
    check_include_file("rfb/rfb.h" HAVE_RFB_RFB_H)
    set(optlibsfound "${optlibsfound} libVNC")
  else()
    set(optlibsnotfound "${optlibsnotfound} libVNC")
  endif()
endif()

#find_package(Boost 1.36.0)
#if(Boost_FOUND)
#include_directories(${Boost_INCLUDE_DIRS})
##set(CMAKE_REQUIRED_INCLUDES "${Boost_INCLUDE_DIRS}")
#list(APPEND ZM_BIN_LIBS "${Boost_LIBRARIES}")
#endif()


find_package(GSOAP 2.8.91)
if (GSOAP_FOUND)
  set(optlibsfound "${optlibsfound} gsoap")
  add_definitions(-DWITH_GSOAP)
else()
  set(optlibsnotfound "${optlibsnotfound} gsoap")
endif()

if(NOT ZM_NO_RTSPSERVER)
  set(HAVE_RTSP_SERVER 1)
else()
  set(HAVE_RTSP_SERVER 0)
endif()

#
# *** END OF LIBRARY CHECKS ***

# If libjwt is not present we fall back to jwt-cpp which requires OpenSSL
if((NOT HAVE_LIBJWT) AND (NOT HAVE_LIBOPENSSL))
  message(FATAL_ERROR "Using the jwt-cpp backend requires OpenSSL as crypto backend.")
endif()

find_package(V4L2)
if(TARGET V4L2::videodev2)
  set(ZM_HAS_V4L2 1)
else()
  set(ZM_HAS_V4L2 0)
  message(AUTHOR_WARNING "Video 4 Linux headers weren't found - Analog and USB camera support will not be available")
endif()

# Check for PCRE and enable ZM_PCRE accordingly
set(ZM_PCRE 0)
if(HAVE_LIBPCRE AND HAVE_PCRE_H)
  set(ZM_PCRE 1)
endif()

# Check for mmap and enable in all components
set(ZM_MEM_MAPPED 0)
set(ENABLE_MMAP no)
if(NOT ZM_NO_MMAP)
  set(ZM_MEM_MAPPED 1)
  set(ENABLE_MMAP yes)
  set(ZM_MMAP_PERLPACKAGE "Sys::Mmap")
endif()

# Check for the ONVIF flag and enable ZM_HAS_ONVIF accordingly
set(ZM_HAS_ONVIF 0)
if(ZM_ONVIF)
  set(ZM_HAS_ONVIF 1)
endif()

# Check for Perl
find_package(Perl)
if(NOT PERL_FOUND)
  message(FATAL_ERROR "ZoneMinder requires Perl 5.6.0 or newer but it was not found on your system")
endif()

# Checking for perl modules requires FindPerlModules.cmake
# Check all required modules at once
# TODO: Add checking for the optional modules
find_package(
  PerlModules COMPONENTS Sys::Syslog DBI DBD::mysql
  Getopt::Long Time::HiRes Date::Manip LWP::UserAgent
  ExtUtils::MakeMaker ${ZM_MMAP_PERLPACKAGE})
if(NOT PERLMODULES_FOUND)
  message(WARNING "Not all required perl modules were found on your system")
endif()

# Attempt to check which user apache (or other web server) runs on by 
# searching for a user beginning with apache or www and then cutting the user 
# from the first matching user line
if(ZM_WEB_USER STREQUAL "")
  # Check for a user matching ^apache and cut the username from the
  # userline in the first match
  file(STRINGS "/etc/passwd" userline_apache REGEX "^apache")
  file(STRINGS "/etc/passwd" userline_www REGEX "^www")
  if(NOT (userline_apache STREQUAL ""))
    execute_process(
      COMMAND echo ${userline_apache}
      COMMAND cut -d: -f1 OUTPUT_VARIABLE ZM_WEB_USER
      OUTPUT_STRIP_TRAILING_WHITESPACE)
  elseif(NOT (userline_www STREQUAL ""))
    execute_process(
      COMMAND echo ${userline_www}
      COMMAND cut -d: -f1 OUTPUT_VARIABLE ZM_WEB_USER
      OUTPUT_STRIP_TRAILING_WHITESPACE)
  endif()
  message(STATUS "Detected web server user: ${ZM_WEB_USER}")
endif()
# Check if webgroup contains anything. If not, use the web user as the web group
if(NOT ZM_WEB_GROUP)
  set(ZM_WEB_GROUP ${ZM_WEB_USER})
endif()
message(STATUS "Using web user: ${ZM_WEB_USER}")
message(STATUS "Using web group: ${ZM_WEB_GROUP}")

if(WITH_SYSTEMD)
  # Check for polkit
  find_package(Polkit)
  if(NOT POLKIT_FOUND)
    message(WARNING "Running ZoneMinder requires polkit. Building ZoneMinder requires the polkit development package.")
  endif()
endif()

# Find the path to an arp compatible executable
if(ZM_PATH_ARP STREQUAL "")
  find_program(ARP_EXECUTABLE arp)
  if(ARP_EXECUTABLE)
    set(ZM_PATH_ARP "${ARP_EXECUTABLE}")
    mark_as_advanced(ARP_EXECUTABLE)
  else()
    find_program(ARP_EXECUTABLE ip)
    if(ARP_EXECUTABLE)
      set(ZM_PATH_ARP "${ARP_EXECUTABLE} neigh")
      mark_as_advanced(ARP_EXECUTABLE)
    endif()
  endif()
  if(ARP_EXECUTABLE-NOTFOUND)
    message(WARNING "Unable to find a compatible arp binary. Monitor probe will not function.")
  endif()
endif()

# Find the path to an arp-scan compatible executable
if(ZM_PATH_ARP_SCAN STREQUAL "")
  find_program(ARP_SCAN_EXECUTABLE arp-scan)
  if(ARP_SCAN_EXECUTABLE)
    set(ZM_PATH_ARP_SCAN "${ARP_SCAN_EXECUTABLE}")
    mark_as_advanced(ARP_SCAN_EXECUTABLE)
  endif()
  if(ARP_SCAN_EXECUTABLE-NOTFOUND)
    message(WARNING "Unable to find a compatible arp-scan binary. Monitor probe will be less powerful.")
  endif()
endif()

# Find the path to an rm compatible executable
if(ZM_PATH_RM STREQUAL "")
  find_program(RM_EXECUTABLE rm)
  if(RM_EXECUTABLE)
    set(ZM_PATH_RM "${RM_EXECUTABLE}")
    mark_as_advanced(RM_EXECUTABLE)
  endif()
  if(RM_EXECUTABLE-NOTFOUND)
    message(WARNING "Unable to find a compatible rm binary.")
  endif()
endif()

# Find the path to a uname compatible executable
if(ZM_PATH_UNAME STREQUAL "")
  find_program(UNAME_EXECUTABLE uname)
  if(UNAME_EXECUTABLE)
    set(ZM_PATH_UNAME "${UNAME_EXECUTABLE}")
    mark_as_advanced(UNAME_EXECUTABLE)
  endif()
  if(UNAME_EXECUTABLE-NOTFOUND)
    message(WARNING "Unable to find a compatible uname binary.")
  endif()
endif()

# Find the path to a uname compatible executable
if(ZM_PATH_IP STREQUAL "")
  find_program(IP_EXECUTABLE ip)
  if(IP_EXECUTABLE)
    set(ZM_PATH_IP "${IP_EXECUTABLE}")
    mark_as_advanced(IP_EXECUTABLE)
  endif()
  if(IP_EXECUTABLE-NOTFOUND)
    message(WARNING "Unable to find a compatible ip binary.")
  endif()
endif()

# Find the path to a ifconfig compatible executable
if(ZM_PATH_IFCONFIG STREQUAL "")
  find_program(IFCONFIG_EXECUTABLE ifconfig)
  if(IFCONFIG_EXECUTABLE)
    set(ZM_PATH_IFCONFIG "${IFCONFIG_EXECUTABLE}")
    mark_as_advanced(IFCONFIG_EXECUTABLE)
  endif()
  if(IFCONFIG_EXECUTABLE-NOTFOUND)
    message(WARNING "Unable to find a compatible ifconfig binary.")
  endif()
endif()

# Some variables that zm expects
set(ZM_PID "${ZM_RUNDIR}/zm.pid")
set(ZM_CONFIG "${ZM_CONFIG_DIR}/zm.conf")
set(VERSION "${zoneminder_VERSION}")
set(API_VERSION "${zoneminder_API_VERSION}")
set(PKGDATADIR "${CMAKE_INSTALL_PREFIX}/${CMAKE_INSTALL_DATADIR}/zoneminder")
set(BINDIR "${CMAKE_INSTALL_FULL_BINDIR}")
set(LIBDIR "${CMAKE_INSTALL_FULL_LIBDIR}")
set(SYSCONFDIR "/${CMAKE_INSTALL_SYSCONFDIR}")
set(WEB_PREFIX "${ZM_WEBDIR}")
set(CGI_PREFIX "${ZM_CGIDIR}")
set(WEB_USER "${ZM_WEB_USER}")
set(WEB_GROUP "${ZM_WEB_GROUP}")
if(ZM_PERL_SEARCH_PATH)
  set(EXTRA_PERL_LIB "use lib '${ZM_PERL_SEARCH_PATH}'; # Include custom perl install path")
else()
  set(EXTRA_PERL_LIB "# Include from system perl paths only")
endif()

# If this is an out-of-source build, copy the files we need to the binary directory
if(NOT (CMAKE_BINARY_DIR STREQUAL CMAKE_SOURCE_DIR))
  file(COPY "${CMAKE_CURRENT_SOURCE_DIR}/conf.d" DESTINATION "${CMAKE_CURRENT_BINARY_DIR}" PATTERN "*.in" EXCLUDE)
endif()

# Generate files from the .in files
configure_file(zm.conf.in "${CMAKE_CURRENT_BINARY_DIR}/zm.conf" @ONLY)
configure_file(conf.d/01-system-paths.conf.in "${CMAKE_CURRENT_BINARY_DIR}/conf.d/01-system-paths.conf" @ONLY)
configure_file(zoneminder-config.cmake "${CMAKE_CURRENT_BINARY_DIR}/config.h" @ONLY)
configure_file(zmconfgen.pl.in "${CMAKE_CURRENT_BINARY_DIR}/zmconfgen.pl" @ONLY)
configure_file(zmlinkcontent.sh.in "${CMAKE_CURRENT_BINARY_DIR}/zmlinkcontent.sh" @ONLY)

# Create a target for man pages
include(Pod2Man)

# Process subdirectories
add_subdirectory(dep)
add_subdirectory(src)
add_subdirectory(scripts)
add_subdirectory(db)
add_subdirectory(fonts)
add_subdirectory(web)
add_subdirectory(misc)
add_subdirectory(onvif)

if(BUILD_TEST_SUITE)
  message("Building unit tests: Yes")
  find_package(Catch2 REQUIRED)

  include(CTest)
  add_subdirectory(tests)
else()
  message("Building unit tests: No (default)")
endif()

# Process distro subdirectories
if((ZM_TARGET_DISTRO MATCHES "^el") OR (ZM_TARGET_DISTRO MATCHES "^fc"))
  add_subdirectory(distros/redhat)
elseif()
  add_subdirectory(distros/opensuse)
endif()

# Print optional libraries detection status
message(STATUS "Optional libraries found:${optlibsfound}")
message(STATUS "Optional libraries not found:${optlibsnotfound}")

message(STATUS "Enabled crypto backend: ${ZM_CRYPTO_BACKEND}")
message(STATUS "Enabled JWT backend: ${ZM_JWT_BACKEND}")

# Run ZM configuration generator
message(STATUS "Running ZoneMinder configuration generator")
execute_process(COMMAND perl ${CMAKE_CURRENT_BINARY_DIR}/zmconfgen.pl RESULT_VARIABLE ZMCONFGEN_RESULT)
if(ZMCONFGEN_RESULT EQUAL 0)
  message(STATUS "ZoneMinder configuration generator completed successfully")
else()
  message(FATAL_ERROR "ZoneMinder configuration generator failed. Exit code: ${zmconfgen_result}")
endif()

# Install zm.conf
install(FILES "${CMAKE_CURRENT_BINARY_DIR}/zm.conf" DESTINATION "${ZM_CONFIG_DIR}")
install(DIRECTORY "${CMAKE_CURRENT_BINARY_DIR}/conf.d/" DESTINATION "${ZM_CONFIG_SUBDIR}" PATTERN "*.in" EXCLUDE)

# Uninstall target
configure_file(
  "${CMAKE_CURRENT_SOURCE_DIR}/cmake/cmake_uninstall.cmake.in"
  "${CMAKE_CURRENT_BINARY_DIR}/cmake/cmake_uninstall.cmake"
  IMMEDIATE @ONLY)
add_custom_target(uninstall
  COMMAND ${CMAKE_COMMAND} -P ${CMAKE_CURRENT_BINARY_DIR}/cmake/cmake_uninstall.cmake)

# Configure CCache if available
find_program(CCACHE_FOUND ccache)
if(CCACHE_FOUND)
  set_property(GLOBAL PROPERTY RULE_LAUNCH_COMPILE ccache)
  set_property(GLOBAL PROPERTY RULE_LAUNCH_LINK ccache)
endif()

install(DIRECTORY icons DESTINATION "${CMAKE_INSTALL_DATADIR}/zoneminder/")
