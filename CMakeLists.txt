# Main CMake file for the ZoneMinder project.
# Created by mastertheknife (Kfir Itzhak)
# For more information and installation, see the INSTALL file
#
cmake_minimum_required (VERSION 2.8.7)
cmake_policy(SET CMP0054 NEW)
project (zoneminder)
file (STRINGS "version" zoneminder_VERSION)
# make API version a minor of ZM version
set(zoneminder_API_VERSION "${zoneminder_VERSION}.1")

# Make sure the submodules are there
if( NOT EXISTS "${CMAKE_SOURCE_DIR}/web/api/app/Plugin/Crud/Lib/CrudControllerTrait.php" )
message( SEND_ERROR "The git submodules are not available. Please run
git submodule update --init --recursive")
endif( NOT EXISTS "${CMAKE_SOURCE_DIR}/web/api/app/Plugin/Crud/Lib/CrudControllerTrait.php" )

# CMake does not allow out-of-source build if CMakeCache.exists 
# in the source folder. Abort and notify the user
if(
    (NOT (CMAKE_BINARY_DIR STREQUAL CMAKE_SOURCE_DIR)) 
    AND (EXISTS "${CMAKE_SOURCE_DIR}/CMakeCache.txt"))
        message(FATAL_ERROR " You are attempting to do an out-of-source build, 
            but a cmake cache file for an in-source build exists. Please delete 
            the file CMakeCache.txt from the source folder to proceed.")
endif(
    (NOT (CMAKE_BINARY_DIR STREQUAL CMAKE_SOURCE_DIR)) 
    AND (EXISTS "${CMAKE_SOURCE_DIR}/CMakeCache.txt"))

# Default build type. To change the build type, 
# use the CMAKE_BUILD_TYPE configuration option.
if(NOT CMAKE_BUILD_TYPE)
	set(CMAKE_BUILD_TYPE 
            Release CACHE STRING "Build type: Release or Debug" FORCE)
endif(NOT CMAKE_BUILD_TYPE)

# Can assist in troubleshooting
#set(CMAKE_VERBOSE_MAKEFILE ON)
#set(CMAKE_INSTALL_ALWAYS ON)

# Host OS Check
set(HOST_OS "")
if(${CMAKE_SYSTEM_NAME} STREQUAL "Linux")
	set(HOST_OS "linux")
endif(${CMAKE_SYSTEM_NAME} STREQUAL "Linux")
if(${CMAKE_SYSTEM_NAME} MATCHES ".*(SunOS|Solaris).*")
	set(HOST_OS "solaris")
	set(SOLARIS 1)
endif(${CMAKE_SYSTEM_NAME} MATCHES ".*(SunOS|Solaris).*")
if(${CMAKE_SYSTEM_NAME} MATCHES ".*BSD.*")
	set(HOST_OS "BSD")
	set(BSD 1)
endif(${CMAKE_SYSTEM_NAME} MATCHES ".*BSD.*")
if(${CMAKE_SYSTEM_NAME} STREQUAL "Darwin")
	set(HOST_OS "darwin")
endif(${CMAKE_SYSTEM_NAME} STREQUAL "Darwin")
if(NOT HOST_OS)
	message(FATAL_ERROR 
            "ZoneMinder was unable to deterimine the host OS. Please report this. Value of CMAKE_SYSTEM_NAME: ${CMAKE_SYSTEM_NAME}")
endif(NOT HOST_OS)

set (CMAKE_CXX_STANDARD 11)
SET(CMAKE_CXX_FLAGS "${CMAKE_CXX_FLAGS} -std=c++11")
# Default CLFAGS and CXXFLAGS:
set(CMAKE_C_FLAGS_RELEASE "-Wall -D__STDC_CONSTANT_MACROS -O2")
set(CMAKE_CXX_FLAGS_RELEASE "-Wall -D__STDC_CONSTANT_MACROS -O2")
set(CMAKE_C_FLAGS_DEBUG "-Wall -D__STDC_CONSTANT_MACROS -g")
set(CMAKE_CXX_FLAGS_DEBUG "-Wall -D__STDC_CONSTANT_MACROS -g")
set(CMAKE_INCLUDE_CURRENT_DIR ON)
set(CMAKE_MODULE_PATH ${CMAKE_MODULE_PATH} "${CMAKE_SOURCE_DIR}/cmake/Modules/")

# GCC below 6.0 doesn't support __target__("fpu=neon") attribute, required for compiling ARM Neon code, otherwise compilation fails.
# Must use -mfpu=neon compiler flag instead, but only do that for processors that support neon, otherwise strip the neon code alltogether,
# because passing -fmpu=neon is unsafe to processors that don't support neon

# Arm neon support only tested on Linux. If your arm hardware is running a non-Linux distro and is using gcc then contact us.
IF (CMAKE_SYSTEM_NAME MATCHES "Linux")
    string(TOLOWER "${CMAKE_SYSTEM_PROCESSOR}" ZM_SYSTEM_PROC)
    IF((ZM_SYSTEM_PROC STREQUAL "") OR (ZM_SYSTEM_PROC STREQUAL "unknown"))
        execute_process(COMMAND uname -m OUTPUT_VARIABLE ZM_SYSTEM_PROC ERROR_VARIABLE ZM_SYSTEM_PROC_ERR)

        # maybe make the following error checks fatal
        IF(ZM_SYSTEM_PROC_ERR)
            message(WARNING "\nAn error occurred while attempting to determine the system processor:\n${ZM_SYSTEM_PROC_ERR}")
        ENDIF(ZM_SYSTEM_PROC_ERR)
        IF(NOT ZM_SYSTEM_PROC)
           message(WARNING "\nUnable to determine the system processor. This may cause a build failure.\n")
        ENDIF(ZM_SYSTEM_PROC)

    ENDIF((ZM_SYSTEM_PROC STREQUAL "") OR (ZM_SYSTEM_PROC STREQUAL "unknown"))

    IF(ZM_SYSTEM_PROC MATCHES "^arm")
	    IF(CMAKE_COMPILER_IS_GNUCXX AND CMAKE_CXX_COMPILER_VERSION VERSION_LESS 6.0)
                EXEC_PROGRAM(grep ARGS " neon " "/proc/cpuinfo" OUTPUT_VARIABLE neonoutput RETURN_VALUE neonresult)
		    IF(neonresult EQUAL 0)
                        set(CMAKE_C_FLAGS_RELEASE "${CMAKE_C_FLAGS_RELEASE} -mfpu=neon")
                        set(CMAKE_CXX_FLAGS_RELEASE "${CMAKE_CXX_FLAGS_RELEASE} -mfpu=neon")
                        set(CMAKE_C_FLAGS_DEBUG "${CMAKE_C_FLAGS_DEBUG} -mfpu=neon")
                        set(CMAKE_CXX_FLAGS_DEBUG "${CMAKE_CXX_FLAGS_DEBUG} -mfpu=neon")
		    ELSE(neonresult EQUAL 0)
                        add_definitions(-DZM_STRIP_NEON=1)
                        message(STATUS "ARM Neon is not available on this processor. Neon functions will be absent")
                    ENDIF(neonresult EQUAL 0)
            ENDIF(CMAKE_COMPILER_IS_GNUCXX AND CMAKE_CXX_COMPILER_VERSION VERSION_LESS 6.0)
    ENDIF(ZM_SYSTEM_PROC MATCHES "^arm")
ENDIF (CMAKE_SYSTEM_NAME MATCHES "Linux")

# Modules that we need:
include (GNUInstallDirs)
include (CheckIncludeFile)
include (CheckIncludeFiles)
include (CheckFunctionExists)
include (CheckPrototypeDefinition_fixed)
include (CheckTypeSize)
include (CheckStructHasMember)
include (CheckSendfile)

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
    ZM_CONFIG_DIR
    ZM_CONFIG_SUBDIR
    ZM_SYSTEMD
    ZM_MANPAGE_DEST_PREFIX)

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
set(ZM_DB_HOST "localhost" CACHE STRING 
    "Hostname where ZoneMinder database located, default: localhost")
set(ZM_DB_NAME "zm" CACHE STRING 
    "Name of ZoneMinder database, default: zm")
set(ZM_DB_USER "zmuser" CACHE STRING 
    "Name of ZoneMinder database user, default: zmuser")
set(ZM_DB_PASS "zmpass" CACHE STRING 
    "Password of ZoneMinder database user, default: zmpass")
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
set(ZM_PATH_SHUTDOWN "/sbin/shutdown" CACHE PATH
    "Path to shutdown binary, default: /sbin/shutdown")

# Advanced
set(ZM_PATH_MAP "/dev/shm" CACHE PATH
    "Location to save mapped memory files, default: /dev/shm")
set(ZM_PATH_ARP "" CACHE PATH
    "Full path to compatible arp binary. Leave empty for automatic detection.")
set(ZM_CONFIG_DIR "/${CMAKE_INSTALL_SYSCONFDIR}" CACHE PATH 
    "Location of ZoneMinder configuration, default system config directory")
set(ZM_CONFIG_SUBDIR "${ZM_CONFIG_DIR}/conf.d" CACHE PATH 
    "Location of ZoneMinder configuration subfolder, default: ZM_CONFIG_DIR/conf.d")
set(ZM_EXTRA_LIBS "" CACHE STRING 
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
    "Build ZoneMinder for a specific distribution.  Currently, valid names are: fc27, fc26, el7, OS13, FreeBSD")
set(ZM_SYSTEMD "OFF" CACHE BOOL 
    "Set to ON to force building ZM with systemd support. default: OFF")
set(ZM_MANPAGE_DEST_PREFIX "share/man" CACHE PATH 
	"Relative path used to install ZoneMinder's Man pages into a
         non-standard folder. Most Linux users will not need to change this.
         BSD users may need to set this.")

# Reassign some variables if a target distro has been specified
if((ZM_TARGET_DISTRO MATCHES "^el") OR (ZM_TARGET_DISTRO MATCHES "^fc"))
	set(ZM_RUNDIR "/var/run/zoneminder")
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
endif((ZM_TARGET_DISTRO MATCHES "^el") OR (ZM_TARGET_DISTRO MATCHES "^fc"))

# Required for certain checks to work
set(CMAKE_EXTRA_INCLUDE_FILES 
    ${CMAKE_EXTRA_INCLUDE_FILES} stdio.h stdlib.h math.h signal.h
    )
# Required for including headers from the this folder
include_directories("${CMAKE_BINARY_DIR}")
# This is required to enable searching in lib64 (if exists), do not change
set_property(GLOBAL PROPERTY FIND_LIBRARY_USE_LIB64_PATHS ON)

# Set the systemd flag if systemd is autodetected or ZM_SYSTEMD has been set
if(ZM_SYSTEMD OR (IS_DIRECTORY /usr/lib/systemd/system) OR (IS_DIRECTORY /lib/systemd/system))
    set(WITH_SYSTEMD 1)
endif(ZM_SYSTEMD OR (IS_DIRECTORY /usr/lib/systemd/system) OR (IS_DIRECTORY /lib/systemd/system))

# System checks
check_include_file("libv4l1-videodev.h" HAVE_LIBV4L1_VIDEODEV_H)
if(NOT HAVE_LIBV4L1_VIDEODEV_H)
	check_include_file("linux/videodev.h" HAVE_LINUX_VIDEODEV_H)
endif(NOT HAVE_LIBV4L1_VIDEODEV_H)
check_include_file("linux/videodev2.h" HAVE_LINUX_VIDEODEV2_H)
check_include_file("execinfo.h" HAVE_EXECINFO_H)
if (HAVE_EXECINFO_H)
  check_function_exists("backtrace" HAVE_DECL_BACKTRACE)
  if (NOT HAVE_DECL_BACKTRACE)
    find_library (EXECINFO_LIBRARY NAMES execinfo)
    if (EXECINFO_LIBRARY)
        list(APPEND ZM_BIN_LIBS "-lexecinfo")
    endif (EXECINFO_LIBRARY)
  endif (NOT HAVE_DECL_BACKTRACE)
  check_function_exists("backtrace_symbols" HAVE_DECL_BACKTRACE_SYMBOLS)
endif (HAVE_EXECINFO_H)
check_include_file("ucontext.h" HAVE_UCONTEXT_H)
check_include_file("sys/sendfile.h" HAVE_SYS_SENDFILE_H)
check_include_file("sys/syscall.h" HAVE_SYS_SYSCALL_H)
check_function_exists("syscall" HAVE_SYSCALL)
check_function_exists("sendfile" HAVE_SENDFILE)
check_function_exists("posix_memalign" HAVE_POSIX_MEMALIGN)
check_type_size("siginfo_t" HAVE_SIGINFO_T)
check_type_size("ucontext_t" HAVE_UCONTEXT_T)

# *** LIBRARY CHECKS ***

if (UNIX)
    include (CheckLibraryExists)
    CHECK_LIBRARY_EXISTS(rt clock_gettime "time.h" HAVE_CLOCK_GETTIME)
    if(NOT HAVE_CLOCK_GETTIME)
        message(FATAL_ERROR "clock_gettime not found")
    else(NOT HAVE_CLOCK_GETTIME)
        list(APPEND ZM_BIN_LIBS "-lrt")
    endif(NOT HAVE_CLOCK_GETTIME)
endif(UNIX)

# zlib
find_package(ZLIB)
if(ZLIB_FOUND)
	set(HAVE_LIBZLIB 1)
	list(APPEND ZM_BIN_LIBS "${ZLIB_LIBRARIES}")
	include_directories("${ZLIB_INCLUDE_DIR}")
	set(CMAKE_REQUIRED_INCLUDES "${ZLIB_INCLUDE_DIR}")
	check_include_file("zlib.h" HAVE_ZLIB_H)
	set(optlibsfound "${optlibsfound} zlib")
else(ZLIB_FOUND)
	set(optlibsnotfound "${optlibsnotfound} zlib")
endif(ZLIB_FOUND)

# Do not check for cURL if ZM_NO_CURL is on
if(NOT ZM_NO_CURL)
	# cURL
	find_package(CURL)
	if(CURL_FOUND)
		set(HAVE_LIBCURL 1)
		list(APPEND ZM_BIN_LIBS ${CURL_LIBRARIES})
		include_directories(${CURL_INCLUDE_DIRS})
		set(CMAKE_REQUIRED_INCLUDES ${CURL_INCLUDE_DIRS})
		check_include_file("curl/curl.h" HAVE_CURL_CURL_H)
		set(optlibsfound "${optlibsfound} cURL")
	else(CURL_FOUND)
		set(optlibsnotfound "${optlibsnotfound} cURL")
	endif(CURL_FOUND)
endif(NOT ZM_NO_CURL)

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
	endif(NOT HAVE_JPEGLIB_H)
else(JPEG_FOUND)
	message(FATAL_ERROR 
            "ZoneMinder requires jpeg but it was not found on your system")
endif(JPEG_FOUND)

# LIBJWT
find_package(LibJWT)
if(LIBJWT_FOUND)
	set(HAVE_LIBJWT 1)
	set(optlibsfound "${optlibsfound} LIBJWT")
	list(APPEND ZM_BIN_LIBS "${LIBJWT_LIBRARY}")
else(LIBJWT_FOUND)
	set(optlibsnotfound "${optlibsnotfound} LIBJWT")
endif(LIBJWT_FOUND)

# gnutls (using find_library and find_path)
find_library(GNUTLS_LIBRARIES gnutls)
if(GNUTLS_LIBRARIES)
	set(HAVE_LIBGNUTLS 1)
	list(APPEND ZM_BIN_LIBS "${GNUTLS_LIBRARIES}")
	find_path(GNUTLS_INCLUDE_DIR gnutls/gnutls.h)
	if(GNUTLS_INCLUDE_DIR)
		include_directories("${GNUTLS_INCLUDE_DIR}")
		set(CMAKE_REQUIRED_INCLUDES "${GNUTLS_INCLUDE_DIR}")
	endif(GNUTLS_INCLUDE_DIR)
	mark_as_advanced(FORCE GNUTLS_LIBRARIES GNUTLS_INCLUDE_DIR)
	check_include_file("gnutls/gnutls.h" HAVE_GNUTLS_GNUTLS_H)
	set(optlibsfound "${optlibsfound} GnuTLS")
else(GNUTLS_LIBRARIES)
	set(optlibsnotfound "${optlibsnotfound} GnuTLS")
endif(GNUTLS_LIBRARIES)

# OpenSSL
if(NOT HAVE_LIBGNUTLS OR NOT HAVE_LIBJWT)
    find_package(OpenSSL)
    if(OPENSSL_FOUND)
        set(HAVE_LIBOPENSSL 1)
        set(HAVE_LIBCRYPTO 1)
        list(APPEND ZM_BIN_LIBS "${OPENSSL_LIBRARIES}")
        include_directories("${OPENSSL_INCLUDE_DIR}")
        set(CMAKE_REQUIRED_INCLUDES "${OPENSSL_INCLUDE_DIR}")
        check_include_file("openssl/md5.h" HAVE_OPENSSL_MD5_H)
        set(optlibsfound "${optlibsfound} OpenSSL")
    else(OPENSSL_FOUND)
        set(optlibsnotfound "${optlibsnotfound} OpenSSL")
    endif(OPENSSL_FOUND)
endif(NOT HAVE_LIBGNUTLS OR NOT HAVE_LIBJWT)

# pthread (using find_library and find_path)
find_library(PTHREAD_LIBRARIES pthread)
if(PTHREAD_LIBRARIES)
	set(HAVE_LIBPTHREAD 1)
	list(APPEND ZM_BIN_LIBS "${PTHREAD_LIBRARIES}")
	find_path(PTHREAD_INCLUDE_DIR pthread.h)
	if(PTHREAD_INCLUDE_DIR)
		include_directories("${PTHREAD_INCLUDE_DIR}")
		set(CMAKE_REQUIRED_INCLUDES "${PTHREAD_INCLUDE_DIR}")
	endif(PTHREAD_INCLUDE_DIR)
	mark_as_advanced(FORCE PTHREAD_LIBRARIES PTHREAD_INCLUDE_DIR)
	check_include_file("pthread.h" HAVE_PTHREAD_H)
	if(NOT HAVE_PTHREAD_H)
		message(FATAL_ERROR 
                    "ZoneMinder requires pthread headers - check that pthread development packages are installed")
	endif(NOT HAVE_PTHREAD_H)
else(PTHREAD_LIBRARIES)
	message(FATAL_ERROR 
            "ZoneMinder requires pthread but it was not found on your system")
endif(PTHREAD_LIBRARIES)

# pcre (using find_library and find_path)
find_library(PCRE_LIBRARIES pcre)
if(PCRE_LIBRARIES)
	set(HAVE_LIBPCRE 1)
	list(APPEND ZM_BIN_LIBS "${PCRE_LIBRARIES}")
	find_path(PCRE_INCLUDE_DIR pcre.h)
	if(PCRE_INCLUDE_DIR)
		include_directories("${PCRE_INCLUDE_DIR}")
		set(CMAKE_REQUIRED_INCLUDES "${PCRE_INCLUDE_DIR}")
	endif(PCRE_INCLUDE_DIR)
	mark_as_advanced(FORCE PCRE_LIBRARIES PCRE_INCLUDE_DIR)
	check_include_file("pcre.h" HAVE_PCRE_H)
	set(optlibsfound "${optlibsfound} PCRE")
else(PCRE_LIBRARIES)
	set(optlibsnotfound "${optlibsnotfound} PCRE")
endif(PCRE_LIBRARIES)

# gcrypt (using find_library and find_path)
find_library(GCRYPT_LIBRARIES gcrypt)
if(GCRYPT_LIBRARIES)
	set(HAVE_LIBGCRYPT 1)
	list(APPEND ZM_BIN_LIBS "${GCRYPT_LIBRARIES}")
	find_path(GCRYPT_INCLUDE_DIR gcrypt.h)
	if(GCRYPT_INCLUDE_DIR)
		include_directories("${GCRYPT_INCLUDE_DIR}")
		set(CMAKE_REQUIRED_INCLUDES "${GCRYPT_INCLUDE_DIR}")
	endif(GCRYPT_INCLUDE_DIR)
	mark_as_advanced(FORCE GCRYPT_LIBRARIES GCRYPT_INCLUDE_DIR)
	check_include_file("gcrypt.h" HAVE_GCRYPT_H)
	set(optlibsfound "${optlibsfound} GCrypt")
else(GCRYPT_LIBRARIES)
	set(optlibsnotfound "${optlibsnotfound} GCrypt")
endif(GCRYPT_LIBRARIES)

# mysqlclient (using find_library and find_path)
find_library(MYSQLCLIENT_LIBRARIES mysqlclient PATH_SUFFIXES mysql)
if(MYSQLCLIENT_LIBRARIES)
	set(HAVE_LIBMYSQLCLIENT 1)
	list(APPEND ZM_BIN_LIBS "${MYSQLCLIENT_LIBRARIES}")
	find_path(MYSQLCLIENT_INCLUDE_DIR mysql.h PATH_SUFFIXES mysql)
	if(MYSQLCLIENT_INCLUDE_DIR)
		include_directories("${MYSQLCLIENT_INCLUDE_DIR}")
		set(CMAKE_REQUIRED_INCLUDES "${MYSQLCLIENT_INCLUDE_DIR}")
	endif(MYSQLCLIENT_INCLUDE_DIR)
	mark_as_advanced(FORCE MYSQLCLIENT_LIBRARIES MYSQLCLIENT_INCLUDE_DIR)
	check_include_file("mysql.h" HAVE_MYSQL_H)
	if(NOT HAVE_MYSQL_H)
		message(FATAL_ERROR 
                    "ZoneMinder requires MySQL headers - check that MySQL development packages are installed")
	endif(NOT HAVE_MYSQL_H)
else(MYSQLCLIENT_LIBRARIES)
	message(FATAL_ERROR 
            "ZoneMinder requires mysqlclient but it was not found on your system")
endif(MYSQLCLIENT_LIBRARIES)

# x264 (using find_library and find_path)
find_library(X264_LIBRARIES x264)
if(X264_LIBRARIES)
	set(HAVE_LIBX264 1)
	list(APPEND ZM_BIN_LIBS "${X264_LIBRARIES}")
	find_path(X264_INCLUDE_DIR x264.h)
	if(X264_INCLUDE_DIR)
		include_directories("${X264_INCLUDE_DIR}")
		set(CMAKE_REQUIRED_INCLUDES "${X264_INCLUDE_DIR}")
	endif(X264_INCLUDE_DIR)
	mark_as_advanced(FORCE X264_LIBRARIES X264_INCLUDE_DIR)
	check_include_files("stdint.h;x264.h" HAVE_X264_H)
	set(optlibsfound "${optlibsfound} x264")
else(X264_LIBRARIES)
	set(optlibsnotfound "${optlibsnotfound} x264")
endif(X264_LIBRARIES)

# mp4v2 (using find_library and find_path)
find_library(MP4V2_LIBRARIES mp4v2)
if(MP4V2_LIBRARIES)
	set(HAVE_LIBMP4V2 1)
	list(APPEND ZM_BIN_LIBS "${MP4V2_LIBRARIES}")

        # mp4v2/mp4v2.h
	find_path(MP4V2_INCLUDE_DIR mp4v2/mp4v2.h)
	if(MP4V2_INCLUDE_DIR)
		include_directories("${MP4V2_INCLUDE_DIR}")
		set(CMAKE_REQUIRED_INCLUDES "${MP4V2_INCLUDE_DIR}")
		check_include_file("mp4v2/mp4v2.h" HAVE_MP4V2_MP4V2_H)
	endif(MP4V2_INCLUDE_DIR)

        # mp4v2.h
	find_path(MP4V2_INCLUDE_DIR mp4v2.h)
	if(MP4V2_INCLUDE_DIR)
		include_directories("${MP4V2_INCLUDE_DIR}")
		set(CMAKE_REQUIRED_INCLUDES "${MP4V2_INCLUDE_DIR}")
		check_include_file("mp4v2.h" HAVE_MP4V2_H)
	endif(MP4V2_INCLUDE_DIR)

	# mp4.h
	find_path(MP4V2_INCLUDE_DIR mp4.h)
	if(MP4V2_INCLUDE_DIR)
		include_directories("${MP4V2_INCLUDE_DIR}")
		set(CMAKE_REQUIRED_INCLUDES "${MP4V2_INCLUDE_DIR}")
		check_include_file("mp4.h" HAVE_MP4_H)
	endif(MP4V2_INCLUDE_DIR)

	mark_as_advanced(FORCE MP4V2_LIBRARIES MP4V2_INCLUDE_DIR)
	set(optlibsfound "${optlibsfound} mp4v2")
else(MP4V2_LIBRARIES)
	set(optlibsnotfound "${optlibsnotfound} mp4v2")
endif(MP4V2_LIBRARIES)

set(PATH_FFMPEG "")
set(OPT_FFMPEG "no")
# avformat (using find_library and find_path)
find_library(AVFORMAT_LIBRARIES avformat)
if(AVFORMAT_LIBRARIES)
	set(HAVE_LIBAVFORMAT 1)
	list(APPEND ZM_BIN_LIBS "${AVFORMAT_LIBRARIES}")
	find_path(AVFORMAT_INCLUDE_DIR "libavformat/avformat.h" /usr/include/ffmpeg)
	if(AVFORMAT_INCLUDE_DIR)
		include_directories("${AVFORMAT_INCLUDE_DIR}")
		set(CMAKE_REQUIRED_INCLUDES "${AVFORMAT_INCLUDE_DIR}")
	endif(AVFORMAT_INCLUDE_DIR)
	mark_as_advanced(FORCE AVFORMAT_LIBRARIES AVFORMAT_INCLUDE_DIR)
	check_include_file("libavformat/avformat.h" HAVE_LIBAVFORMAT_AVFORMAT_H)
	set(optlibsfound "${optlibsfound} AVFormat")
else(AVFORMAT_LIBRARIES)
	set(optlibsnotfound "${optlibsnotfound} AVFormat")
endif(AVFORMAT_LIBRARIES)

# avcodec (using find_library and find_path)
find_library(AVCODEC_LIBRARIES avcodec)
if(AVCODEC_LIBRARIES)
	set(HAVE_LIBAVCODEC 1)
	list(APPEND ZM_BIN_LIBS "${AVCODEC_LIBRARIES}")
	find_path(AVCODEC_INCLUDE_DIR "libavcodec/avcodec.h" /usr/include/ffmpeg)
	if(AVCODEC_INCLUDE_DIR)
		include_directories("${AVCODEC_INCLUDE_DIR}")
		set(CMAKE_REQUIRED_INCLUDES "${AVCODEC_INCLUDE_DIR}")
	endif(AVCODEC_INCLUDE_DIR)
	mark_as_advanced(FORCE AVCODEC_LIBRARIES AVCODEC_INCLUDE_DIR)
	check_include_file("libavcodec/avcodec.h" HAVE_LIBAVCODEC_AVCODEC_H)
	set(optlibsfound "${optlibsfound} AVCodec")
else(AVCODEC_LIBRARIES)
  message(WARNING "\nWhile it should be possible to build ZM without AVCODEC the result will pretty useless.")
	set(optlibsnotfound "${optlibsnotfound} AVCodec")
endif(AVCODEC_LIBRARIES)

# avdevice (using find_library and find_path)
find_library(AVDEVICE_LIBRARIES avdevice)
if(AVDEVICE_LIBRARIES)
	set(HAVE_LIBAVDEVICE 1)
	list(APPEND ZM_BIN_LIBS "${AVDEVICE_LIBRARIES}")
	find_path(AVDEVICE_INCLUDE_DIR "libavdevice/avdevice.h" /usr/include/ffmpeg)
	if(AVDEVICE_INCLUDE_DIR)
		include_directories("${AVDEVICE_INCLUDE_DIR}")
		set(CMAKE_REQUIRED_INCLUDES "${AVDEVICE_INCLUDE_DIR}")
	endif(AVDEVICE_INCLUDE_DIR)
	mark_as_advanced(FORCE AVDEVICE_LIBRARIES AVDEVICE_INCLUDE_DIR)
	check_include_file("libavdevice/avdevice.h" HAVE_LIBAVDEVICE_AVDEVICE_H)
	set(optlibsfound "${optlibsfound} AVDevice")
else(AVDEVICE_LIBRARIES)
	set(optlibsnotfound "${optlibsnotfound} AVDevice")
endif(AVDEVICE_LIBRARIES)

# avutil (using find_library and find_path)
find_library(AVUTIL_LIBRARIES avutil)
if(AVUTIL_LIBRARIES)
	set(HAVE_LIBAVUTIL 1)
	list(APPEND ZM_BIN_LIBS "${AVUTIL_LIBRARIES}")
	find_path(AVUTIL_INCLUDE_DIR "libavutil/avutil.h" /usr/include/ffmpeg)
	if(AVUTIL_INCLUDE_DIR)
		include_directories("${AVUTIL_INCLUDE_DIR}")
		set(CMAKE_REQUIRED_INCLUDES "${AVUTIL_INCLUDE_DIR}")
	endif(AVUTIL_INCLUDE_DIR)
	mark_as_advanced(FORCE AVUTIL_LIBRARIES AVUTIL_INCLUDE_DIR)
	check_include_file("libavutil/avutil.h" HAVE_LIBAVUTIL_AVUTIL_H)
	check_include_file("libavutil/mathematics.h" HAVE_LIBAVUTIL_MATHEMATICS_H)
	check_include_file("libavutil/hwcontext.h" HAVE_LIBAVUTIL_HWCONTEXT_H)
	set(optlibsfound "${optlibsfound} AVUtil")
else(AVUTIL_LIBRARIES)
	set(optlibsnotfound "${optlibsnotfound} AVUtil")
endif(AVUTIL_LIBRARIES)

# swscale (using find_library and find_path)
find_library(SWSCALE_LIBRARIES swscale)
if(SWSCALE_LIBRARIES)
	set(HAVE_LIBSWSCALE 1)
	list(APPEND ZM_BIN_LIBS "${SWSCALE_LIBRARIES}")
	find_path(SWSCALE_INCLUDE_DIR "libswscale/swscale.h" /usr/include/ffmpeg)
	if(SWSCALE_INCLUDE_DIR)
		include_directories("${SWSCALE_INCLUDE_DIR}")
		set(CMAKE_REQUIRED_INCLUDES "${SWSCALE_INCLUDE_DIR}")
	endif(SWSCALE_INCLUDE_DIR)
	mark_as_advanced(FORCE SWSCALE_LIBRARIES SWSCALE_INCLUDE_DIR)
	check_include_file("libswscale/swscale.h" HAVE_LIBSWSCALE_SWSCALE_H)
	set(optlibsfound "${optlibsfound} SWScale")
else(SWSCALE_LIBRARIES)
	set(optlibsnotfound "${optlibsnotfound} SWScale")
endif(SWSCALE_LIBRARIES)

# SWresample (using find_library and find_path)
find_library(SWRESAMPLE_LIBRARIES swresample)
if(SWRESAMPLE_LIBRARIES)
  set(HAVE_LIBSWRESAMPLE 1)
  list(APPEND ZM_BIN_LIBS "${SWRESAMPLE_LIBRARIES}")
  find_path(SWRESAMPLE_INCLUDE_DIR "libswresample/swresample.h" /usr/include/ffmpeg)
  if(SWRESAMPLE_INCLUDE_DIR)
    include_directories("${SWRESAMPLE_INCLUDE_DIR}")
    set(CMAKE_REQUIRED_INCLUDES "${SWRESAMPLE_INCLUDE_DIR}")
  endif(SWRESAMPLE_INCLUDE_DIR)
  mark_as_advanced(FORCE SWRESAMPLE_LIBRARIES SWRESAMPLE_INCLUDE_DIR)
  check_include_file("libswresample/swresample.h" HAVE_LIBSWRESAMPLE_SWRESAMPLE_H)
  set(optlibsfound "${optlibsfound} SWResample")
else(SWRESAMPLE_LIBRARIES)
  set(optlibsnotfound "${optlibsnotfound} SWResample")

  # AVresample (using find_library and find_path)
  find_library(AVRESAMPLE_LIBRARIES avresample)
  if(AVRESAMPLE_LIBRARIES)
    set(HAVE_LIBAVRESAMPLE 1)
    list(APPEND ZM_BIN_LIBS "${AVRESAMPLE_LIBRARIES}")
    find_path(AVRESAMPLE_INCLUDE_DIR "libavresample/avresample.h" /usr/include/ffmpeg)
    if(AVRESAMPLE_INCLUDE_DIR)
      include_directories("${AVRESAMPLE_INCLUDE_DIR}")
      set(CMAKE_REQUIRED_INCLUDES "${AVRESAMPLE_INCLUDE_DIR}")
    endif(AVRESAMPLE_INCLUDE_DIR)
    mark_as_advanced(FORCE AVRESAMPLE_LIBRARIES AVRESAMPLE_INCLUDE_DIR)
    check_include_file("libavresample/avresample.h" HAVE_LIBAVRESAMPLE_AVRESAMPLE_H)
    set(optlibsfound "${optlibsfound} AVResample")
  else(AVRESAMPLE_LIBRARIES)
    set(optlibsnotfound "${optlibsnotfound} AVResample")
  endif(AVRESAMPLE_LIBRARIES)

endif(SWRESAMPLE_LIBRARIES)

# Find the path to the ffmpeg executable
find_program(FFMPEG_EXECUTABLE 
	NAMES ffmpeg avconv
	PATH_SUFFIXES ffmpeg)
if(FFMPEG_EXECUTABLE)
	set(PATH_FFMPEG "${FFMPEG_EXECUTABLE}")
	set(OPT_FFMPEG "yes")
	mark_as_advanced(FFMPEG_EXECUTABLE)
endif(FFMPEG_EXECUTABLE)

# Do not check for libvlc if ZM_NO_LIBVLC is on
if(NOT ZM_NO_LIBVLC)
	# libvlc (using find_library and find_path)
	find_library(LIBVLC_LIBRARIES vlc)
	if(LIBVLC_LIBRARIES)
		set(HAVE_LIBVLC 1)
		list(APPEND ZM_BIN_LIBS "${LIBVLC_LIBRARIES}")
		find_path(LIBVLC_INCLUDE_DIR "vlc/vlc.h")
		if(LIBVLC_INCLUDE_DIR)
			include_directories("${LIBVLC_INCLUDE_DIR}")
			set(CMAKE_REQUIRED_INCLUDES "${LIBVLC_INCLUDE_DIR}")
		endif(LIBVLC_INCLUDE_DIR)
		mark_as_advanced(FORCE LIBVLC_LIBRARIES LIBVLC_INCLUDE_DIR)
		check_include_file("vlc/vlc.h" HAVE_VLC_VLC_H)
		set(optlibsfound "${optlibsfound} libVLC")
	else(LIBVLC_LIBRARIES)
		set(optlibsnotfound "${optlibsnotfound} libVLC")
	endif(LIBVLC_LIBRARIES)
endif(NOT ZM_NO_LIBVLC)

if(NOT ZM_NO_LIBVNC)
	# libvncclient (using find_library and find_path)
	find_library(LIBVNC_LIBRARIES vncclient)
	if(LIBVNC_LIBRARIES)
		set(HAVE_LIBVNC 1)
		list(APPEND ZM_BIN_LIBS "${LIBVNC_LIBRARIES}")
		find_path(LIBVNC_INCLUDE_DIR "rfb/rfb.h")
		if(LIBVNC_INCLUDE_DIR)
			include_directories("${LIBVNC_INCLUDE_DIR}")
			set(CMAKE_REQUIRED_INCLUDES "${LIBVNC_INCLUDE_DIR}")
		endif(LIBVNC_INCLUDE_DIR)
		mark_as_advanced(FORCE LIBVNC_LIBRARIES LIBVNC_INCLUDE_DIR)
		check_include_file("rfb/rfb.h" HAVE_RFB_RFB_H)
		set(optlibsfound "${optlibsfound} libVNC")
	else(LIBVNC_LIBRARIES)
		set(optlibsnotfound "${optlibsnotfound} libVNC")
	endif(LIBVNC_LIBRARIES)
endif(NOT ZM_NO_LIBVNC)

#find_package(Boost 1.36.0)
#if(Boost_FOUND)
  #include_directories(${Boost_INCLUDE_DIRS})
  ##set(CMAKE_REQUIRED_INCLUDES "${Boost_INCLUDE_DIRS}")
  #list(APPEND ZM_BIN_LIBS "${Boost_LIBRARIES}")
#endif()
#
# *** END OF LIBRARY CHECKS ***

# Check for gnutls or crypto
if((NOT HAVE_LIBCRYPTO) AND (NOT HAVE_LIBGNUTLS))
	message(FATAL_ERROR 
            "ZoneMinder requires crypto or gnutls but none were found on your system")
endif((NOT HAVE_LIBCRYPTO) AND (NOT HAVE_LIBGNUTLS))

# Check for V4L header files and enable ZM_HAS_V4L, ZM_HAS_V4L1, ZM_HAS_V4L2 accordingly
# Setting to zeros first is required because ZM uses #define for these
set(ZM_HAS_V4L 0)
set(ZM_HAS_V4L1 0)
set(ZM_HAS_V4L2 0)
if(HAVE_LINUX_VIDEODEV_H OR HAVE_LIBV4L1_VIDEODEV_H)
	set(ZM_HAS_V4L 1)
	set(ZM_HAS_V4L1 1)
endif(HAVE_LINUX_VIDEODEV_H OR HAVE_LIBV4L1_VIDEODEV_H)
if(HAVE_LINUX_VIDEODEV2_H)
	set(ZM_HAS_V4L 1)
	set(ZM_HAS_V4L2 1)
endif(HAVE_LINUX_VIDEODEV2_H)
if((NOT HAVE_LINUX_VIDEODEV_H) 
    AND (NOT HAVE_LIBV4L1_VIDEODEV_H) 
    AND (NOT HAVE_LINUX_VIDEODEV2_H))
	message(AUTHOR_WARNING 
            "Video 4 Linux headers weren't found - Analog and USB camera support will not be available")
endif((NOT HAVE_LINUX_VIDEODEV_H) 
    AND (NOT HAVE_LIBV4L1_VIDEODEV_H) 
    AND (NOT HAVE_LINUX_VIDEODEV2_H))
# Check for PCRE and enable ZM_PCRE accordingly
set(ZM_PCRE 0)
if(HAVE_LIBPCRE AND HAVE_PCRE_H)
	set(ZM_PCRE 1)
endif(HAVE_LIBPCRE AND HAVE_PCRE_H)
# Check for mmap and enable in all components
set(ZM_MEM_MAPPED 0)
set(ENABLE_MMAP no)
if(NOT ZM_NO_MMAP)
	set(ZM_MEM_MAPPED 1)
	set(ENABLE_MMAP yes)
	set(ZM_MMAP_PERLPACKAGE "Sys::Mmap")
endif(NOT ZM_NO_MMAP)

# Check for the ONVIF flag and enable ZM_HAS_ONVIF accordingly
set(ZM_HAS_ONVIF 0)
if(ZM_ONVIF)
	set(ZM_HAS_ONVIF 1)
endif(ZM_ONVIF)

# Check for authentication functions
if(HAVE_OPENSSL_MD5_H)
	set(CMAKE_REQUIRED_LIBRARIES "${OPENSSL_LIBRARIES}")
	set(CMAKE_REQUIRED_INCLUDES "${OPENSSL_INCLUDE_DIR}")
	check_prototype_definition(
            MD5 
            "unsigned char *MD5(const unsigned char *d, size_t n, unsigned char *md)" "NULL" "openssl/md5.h" 
            HAVE_MD5_OPENSSL)
endif(HAVE_OPENSSL_MD5_H)

if(HAVE_GNUTLS_GNUTLS_H)
	set(CMAKE_REQUIRED_LIBRARIES "${GNUTLS_LIBRARIES}")
	set(CMAKE_REQUIRED_INCLUDES "${GNUTLS_INCLUDE_DIR}")
	check_prototype_definition(
            gnutls_fingerprint 
            "int gnutls_fingerprint (gnutls_digest_algorithm_t algo, const gnutls_datum_t * data, void *result, size_t * result_size)" "0" "stdlib.h;gnutls/gnutls.h" 
            HAVE_DECL_GNUTLS_FINGERPRINT)
endif(HAVE_GNUTLS_GNUTLS_H)

if(HAVE_MD5_OPENSSL)
	set(HAVE_DECL_MD5 1)
endif(HAVE_MD5_OPENSSL)

if((NOT HAVE_MD5_OPENSSL) AND (NOT HAVE_DECL_GNUTLS_FINGERPRINT))
	message(AUTHOR_WARNING 
        "ZoneMinder requires a working MD5 function for hashed authentication but 
        none were found - hashed authentication will not be available")
endif((NOT HAVE_MD5_OPENSSL) AND (NOT HAVE_DECL_GNUTLS_FINGERPRINT))

# Dirty fix for zm_user only using openssl's md5 if gnutls and gcrypt are not available.
# This needs to be fixed in zm_user.[h,cpp] but such fix will also require changes to configure.ac
if(HAVE_LIBCRYPTO AND HAVE_OPENSSL_MD5_H AND HAVE_MD5_OPENSSL)
	set(HAVE_GCRYPT_H 0)
	set(HAVE_GNUTLS_OPENSSL_H 0)
endif(HAVE_LIBCRYPTO AND HAVE_OPENSSL_MD5_H AND HAVE_MD5_OPENSSL)

# Check for Perl
find_package(Perl)
if(NOT PERL_FOUND)
	message(FATAL_ERROR 
            "ZoneMinder requires Perl 5.6.0 or newer but it was not found on your system")
endif(NOT PERL_FOUND)

# Checking for perl modules requires FindPerlModules.cmake
# Check all required modules at once
# TODO: Add checking for the optional modules
find_package(
    PerlModules COMPONENTS Sys::Syslog DBI DBD::mysql 
    Getopt::Long Time::HiRes Date::Manip LWP::UserAgent 
    ExtUtils::MakeMaker ${ZM_MMAP_PERLPACKAGE})
if(NOT PERLMODULES_FOUND)
	message(FATAL_ERROR 
            "Not all required perl modules were found on your system")
endif(NOT PERLMODULES_FOUND)

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
	endif(NOT (userline_apache STREQUAL ""))
	message(STATUS "Detected web server user: ${ZM_WEB_USER}")
endif(ZM_WEB_USER STREQUAL "")
# Check if webgroup contains anything. If not, use the web user as the web group
if(NOT ZM_WEB_GROUP)
	set(ZM_WEB_GROUP ${ZM_WEB_USER})
endif(NOT ZM_WEB_GROUP)
message(STATUS "Using web user: ${ZM_WEB_USER}")
message(STATUS "Using web group: ${ZM_WEB_GROUP}")

if(WITH_SYSTEMD)
    # Check for polkit
    find_package(Polkit)
    if(NOT POLKIT_FOUND)
	    message(FATAL_ERROR 
                "Running ZoneMinder requires polkit. Building ZoneMinder requires the polkit development package.")
    endif(NOT POLKIT_FOUND)
endif(WITH_SYSTEMD)

# Find the path to an arp compatible executable
if(ZM_PATH_ARP STREQUAL "")
    find_program(ARP_EXECUTABLE arp)
    if(ARP_EXECUTABLE)
        set(ZM_PATH_ARP "${ARP_EXECUTABLE}")
        mark_as_advanced(ARP_EXECUTABLE)
    else(ARP_EXECUTABLE)
        find_program(ARP_EXECUTABLE ip)
            if(ARP_EXECUTABLE)
                set(ZM_PATH_ARP "${ARP_EXECUTABLE} neigh")
                mark_as_advanced(ARP_EXECUTABLE)
            endif(ARP_EXECUTABLE)
    endif(ARP_EXECUTABLE)
    if(ARP_EXECUTABLE-NOTFOUND)
        message(WARNING "Unable to find a compatible arp binary. Monitor probe will not function." )
    endif(ARP_EXECUTABLE-NOTFOUND)
endif(ZM_PATH_ARP STREQUAL "")

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
set(ZM_DB_TYPE "mysql")
if(ZM_PERL_SEARCH_PATH)
	set(EXTRA_PERL_LIB "use lib '${ZM_PERL_SEARCH_PATH}'; # Include custom perl install path")
else(ZM_PERL_SEARCH_PATH)
	set(EXTRA_PERL_LIB "# Include from system perl paths only")
endif(ZM_PERL_SEARCH_PATH)

# If this is an out-of-source build, copy the files we need to the binary directory
if(NOT (CMAKE_BINARY_DIR STREQUAL CMAKE_SOURCE_DIR))
	file(COPY "${CMAKE_CURRENT_SOURCE_DIR}/conf.d" DESTINATION "${CMAKE_CURRENT_BINARY_DIR}" PATTERN "*.in" EXCLUDE)
endif(NOT (CMAKE_BINARY_DIR STREQUAL CMAKE_SOURCE_DIR))

# Generate files from the .in files
configure_file(zm.conf.in "${CMAKE_CURRENT_BINARY_DIR}/zm.conf" @ONLY)
configure_file(conf.d/01-system-paths.conf.in "${CMAKE_CURRENT_BINARY_DIR}/conf.d/01-system-paths.conf" @ONLY)
configure_file(zoneminder-config.cmake "${CMAKE_CURRENT_BINARY_DIR}/config.h" @ONLY)
configure_file(zmconfgen.pl.in "${CMAKE_CURRENT_BINARY_DIR}/zmconfgen.pl" @ONLY)
configure_file(zmlinkcontent.sh.in "${CMAKE_CURRENT_BINARY_DIR}/zmlinkcontent.sh" @ONLY)

# Create a target for man pages
include(Pod2Man)
ADD_MANPAGE_TARGET()

# Process subdirectories

# build a bcrypt static library
set(BUILD_SHARED_LIBS_SAVED "${BUILD_SHARED_LIBS}")
set(BUILD_SHARED_LIBS OFF)
add_subdirectory(src/libbcrypt EXCLUDE_FROM_ALL)
set(BUILD_SHARED_LIBS "${BUILD_SHARED_LIBS_SAVED}")

add_subdirectory(src)
add_subdirectory(scripts)
add_subdirectory(db)
add_subdirectory(web)
add_subdirectory(misc)

# Enable ONVIF support
if(ZM_ONVIF)
	add_subdirectory(onvif)
endif(ZM_ONVIF)

# Process distro subdirectories
if((ZM_TARGET_DISTRO MATCHES "^el") OR (ZM_TARGET_DISTRO MATCHES "^fc"))
	add_subdirectory(distros/redhat)
elseif(ZM_TARGET_DISTRO STREQUAL "OS13")
	add_subdirectory(distros/opensuse)
endif((ZM_TARGET_DISTRO MATCHES "^el") OR (ZM_TARGET_DISTRO MATCHES "^fc"))

# Print optional libraries detection status
message(STATUS "Optional libraries found:${optlibsfound}")
message(STATUS "Optional libraries not found:${optlibsnotfound}")

# Run ZM configuration generator
message(STATUS "Running ZoneMinder configuration generator")
execute_process(COMMAND perl ${CMAKE_CURRENT_BINARY_DIR}/zmconfgen.pl RESULT_VARIABLE zmconfgen_result)
if(zmconfgen_result EQUAL 0)
	message(STATUS 
            "ZoneMinder configuration generator completed successfully")
else(zmconfgen_result EQUAL 0)
	message(FATAL_ERROR 
            "ZoneMinder configuration generator failed. Exit code: ${zmconfgen_result}")
endif(zmconfgen_result EQUAL 0)

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
endif(CCACHE_FOUND)

install(DIRECTORY icons DESTINATION "${CMAKE_INSTALL_DATADIR}/zoneminder/")
