cmake_minimum_required (VERSION 2.6)
project (zoneminder)
set(zoneminder_VERSION "1.28.1")

# CMake does not allow out-of-source build if CMakeCache.exists in the source folder. Abort and notify the user to save him from headache why it doesn't work.
if((NOT (CMAKE_BINARY_DIR STREQUAL CMAKE_SOURCE_DIR)) AND (EXISTS "${CMAKE_SOURCE_DIR}/CMakeCache.txt"))
	message(FATAL_ERROR " You are attempting to do an out-of-source build, but a cmake cache file for an in-source build exists. Please delete the file CMakeCache.txt from the source folder to proceed.")
endif((NOT (CMAKE_BINARY_DIR STREQUAL CMAKE_SOURCE_DIR)) AND (EXISTS "${CMAKE_SOURCE_DIR}/CMakeCache.txt"))

# Default build type. To change the build type, use the CMAKE_BUILD_TYPE configuration option.
if(NOT CMAKE_BUILD_TYPE)
	set(CMAKE_BUILD_TYPE Release CACHE STRING "Build type: Release or Debug" FORCE)
endif(NOT CMAKE_BUILD_TYPE)

# Can assist in troubleshooting
#set(CMAKE_VERBOSE_MAKEFILE ON)
#set(CMAKE_INSTALL_ALWAYS ON)

# Default CLFAGS and CXXFLAGS:
set(CMAKE_C_FLAGS_RELEASE "-Wall -D__STDC_CONSTANT_MACROS -O2")
set(CMAKE_CXX_FLAGS_RELEASE "-Wall -D__STDC_CONSTANT_MACROS -O2")
set(CMAKE_C_FLAGS_DEBUG "-Wall -D__STDC_CONSTANT_MACROS -g")
set(CMAKE_CXX_FLAGS_DEBUG "-Wall -D__STDC_CONSTANT_MACROS -g")
set(CMAKE_INCLUDE_CURRENT_DIR ON)
set(CMAKE_MODULE_PATH ${CMAKE_MODULE_PATH} "${CMAKE_SOURCE_DIR}/cmake/")

# Modules that we need:
include (GNUInstallDirs)
include (CheckIncludeFile)
include (CheckIncludeFiles)
include (CheckFunctionExists)
include (CheckPrototypeDefinition)
include (CheckTypeSize)
include (CheckStructHasMember)
include (CheckSendfile)

include (SetGeneralOptions)
include (SetAdvancedOptions)

# Reassign some variables if a target distro has been specified
if((ZM_TARGET_DISTRO STREQUAL "f21") OR (ZM_TARGET_DISTRO STREQUAL "f20"))
	set(ZM_RUNDIR "/var/run/zoneminder")
	set(ZM_SOCKDIR "/var/lib/zoneminder/sock")
	set(ZM_TMPDIR "/var/lib/zoneminder/temp")
	set(ZM_LOGDIR "/var/log/zoneminder")
elseif(ZM_TARGET_DISTRO STREQUAL "el6")
	set(ZM_RUNDIR "/var/run/zoneminder")
	set(ZM_SOCKDIR "/var/lib/zoneminder/sock")
	set(ZM_TMPDIR "/var/lib/zoneminder/temp")
	set(ZM_LOGDIR "/var/log/zoneminder")
	set(ZM_WEBDIR "/usr/share/zoneminder/www")
	set(ZM_CGIDIR "/usr/libexec/zoneminder/cgi-bin")
elseif(ZM_TARGET_DISTRO STREQUAL "el7")
	set(ZM_RUNDIR "/var/run/zoneminder")
	set(ZM_SOCKDIR "/var/lib/zoneminder/sock")
	set(ZM_TMPDIR "/var/lib/zoneminder/temp")
	set(ZM_LOGDIR "/var/log/zoneminder")
	set(ZM_CONFIG_DIR "/etc/zm")
	set(ZM_WEBDIR "/usr/share/zoneminder/www")
	set(ZM_CGIDIR "/usr/libexec/zoneminder/cgi-bin")
elseif(ZM_TARGET_DISTRO STREQUAL "OS13")
	set(ZM_RUNDIR "/var/run/zoneminder")
	set(ZM_TMPDIR "/var/run/zoneminder")
	set(ZM_CONTENTDIR "/var/run/zoneminder")
	set(ZM_LOGDIR "/var/log/zoneminder")
	set(ZM_WEB_USER "wwwrun")
	set(ZM_WEB_GROUP "www")
	set(ZM_WEBDIR "/srv/www/htdocs/zoneminder")
	set(ZM_CGIDIR "/srv/www/cgi-bin")
endif((ZM_TARGET_DISTRO STREQUAL "f21") OR (ZM_TARGET_DISTRO STREQUAL "f20"))

# Required for certain checks to work
set(CMAKE_EXTRA_INCLUDE_FILES ${CMAKE_EXTRA_INCLUDE_FILES} stdio.h stdlib.h math.h signal.h)
# This is required to enable searching in lib64 (if exists), do not change
set_property(GLOBAL PROPERTY FIND_LIBRARY_USE_LIB64_PATHS ON)

# System checks
check_include_file("libv4l1-videodev.h" HAVE_LIBV4L1_VIDEODEV_H)
if(NOT HAVE_LIBV4L1_VIDEODEV_H)
	check_include_file("linux/videodev.h" HAVE_LINUX_VIDEODEV_H)
endif(NOT HAVE_LIBV4L1_VIDEODEV_H)
check_include_file("linux/videodev2.h" HAVE_LINUX_VIDEODEV2_H)
check_include_file("execinfo.h" HAVE_EXECINFO_H)
check_include_file("ucontext.h" HAVE_UCONTEXT_H)
check_include_file("sys/sendfile.h" HAVE_SYS_SENDFILE_H)
check_include_file("sys/syscall.h" HAVE_SYS_SYSCALL_H)
check_function_exists("syscall" HAVE_SYSCALL)
check_function_exists("sendfile" HAVE_SENDFILE)
check_function_exists("backtrace" HAVE_DECL_BACKTRACE)
check_function_exists("backtrace_symbols" HAVE_DECL_BACKTRACE_SYMBOLS)
check_function_exists("posix_memalign" HAVE_POSIX_MEMALIGN)
check_type_size("siginfo_t" HAVE_SIGINFO_T)
check_type_size("ucontext_t" HAVE_UCONTEXT_T)

# *** LIBRARY CHECKS ***
include (External_zlib)
include (External_curl)
include (External_jpeg)
include (External_openssl)
include (External_pthread)
include (External_pcre)
include (External_gcrypt)
include (External_gnutls)
include (External_mysqlclient)
include (External_ffmpeg)
include (External_libvlc)
# *** END OF LIBRARY CHECKS ***

# Check for gnutls or crypto
if((NOT HAVE_LIBCRYPTO) AND (NOT HAVE_LIBGNUTLS))
	message(FATAL_ERROR " zm requires crypto or gnutls but none were found on your system")
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
if((NOT HAVE_LINUX_VIDEODEV_H) AND (NOT HAVE_LIBV4L1_VIDEODEV_H) AND (NOT HAVE_LINUX_VIDEODEV2_H))
	message(AUTHOR_WARNING " Video 4 Linux headers weren't found - Analog and USB camera support will not be available")
endif((NOT HAVE_LINUX_VIDEODEV_H) AND (NOT HAVE_LIBV4L1_VIDEODEV_H) AND (NOT HAVE_LINUX_VIDEODEV2_H))
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

# Check for authenication functions
if(HAVE_OPENSSL_MD5_H)
	set(CMAKE_REQUIRED_LIBRARIES "${OPENSSL_LIBRARIES}")
	set(CMAKE_REQUIRED_INCLUDES "${OPENSSL_INCLUDE_DIR}")
	check_prototype_definition(MD5 "unsigned char *MD5(const unsigned char *d, size_t n, unsigned char *md)" "NULL" "openssl/md5.h" HAVE_MD5_OPENSSL)
endif(HAVE_OPENSSL_MD5_H)
if(HAVE_GNUTLS_OPENSSL_H)
	set(CMAKE_REQUIRED_LIBRARIES "${GNUTLS_LIBRARIES}")
	set(CMAKE_REQUIRED_INCLUDES "${GNUTLS_INCLUDE_DIR}")
	check_prototype_definition(MD5 "unsigned char *MD5 (const unsigned char *buf, unsigned long len, unsigned char *md)" "NULL" "gnutls/openssl.h" HAVE_MD5_GNUTLS)
endif(HAVE_GNUTLS_OPENSSL_H)
if(HAVE_GNUTLS_GNUTLS_H)
	set(CMAKE_REQUIRED_LIBRARIES "${GNUTLS_LIBRARIES}")
	set(CMAKE_REQUIRED_INCLUDES "${GNUTLS_INCLUDE_DIR}")
	check_prototype_definition(gnutls_fingerprint "int gnutls_fingerprint (gnutls_digest_algorithm_t algo, const gnutls_datum_t * data, void *result, size_t * result_size)" "0" "stdlib.h;gnutls/gnutls.h" HAVE_DECL_GNUTLS_FINGERPRINT)
endif(HAVE_GNUTLS_GNUTLS_H)
if(HAVE_MD5_OPENSSL OR HAVE_MD5_GNUTLS)
	set(HAVE_DECL_MD5 1)
else(HAVE_MD5_OPENSSL OR HAVE_MD5_GNUTLS)
	message(AUTHOR_WARNING " ZM requires a working MD5 function for hashed authenication but none were found - hashed authenication will not be available")
endif(HAVE_MD5_OPENSSL OR HAVE_MD5_GNUTLS)
# Dirty fix for zm_user only using openssl's md5 if gnutls and gcrypt are not available.
# This needs to be fixed in zm_user.[h,cpp] but such fix will also require changes to configure.ac
if(HAVE_LIBCRYPTO AND HAVE_OPENSSL_MD5_H AND HAVE_MD5_OPENSSL)
	set(HAVE_GCRYPT_H 0)
	set(HAVE_GNUTLS_OPENSSL_H 0)
endif(HAVE_LIBCRYPTO AND HAVE_OPENSSL_MD5_H AND HAVE_MD5_OPENSSL)

# Attempt to check which user apache (or other web server) runs on by searching for a user beginning with apache or www and then cutting the user from the first matching user line
if(ZM_WEB_USER STREQUAL "")
	# Check for a user matching ^apache and cut the username from the userline in the first match
	file(STRINGS "/etc/passwd" userline_apache REGEX "^apache")
	file(STRINGS "/etc/passwd" userline_www REGEX "^www")
	if(NOT (userline_apache STREQUAL ""))
		execute_process(COMMAND echo ${userline_apache} COMMAND cut -d: -f1 OUTPUT_VARIABLE ZM_WEB_USER OUTPUT_STRIP_TRAILING_WHITESPACE)
	elseif(NOT (userline_www STREQUAL ""))
		execute_process(COMMAND echo ${userline_www} COMMAND cut -d: -f1 OUTPUT_VARIABLE ZM_WEB_USER OUTPUT_STRIP_TRAILING_WHITESPACE)
	endif(NOT (userline_apache STREQUAL ""))
	message(STATUS "Detected web server user: ${ZM_WEB_USER}")
endif(ZM_WEB_USER STREQUAL "")
# Check if webgroup contains anything. If not, use the web user as the web group
if(NOT ZM_WEB_GROUP)
	set(ZM_WEB_GROUP ${ZM_WEB_USER})
endif(NOT ZM_WEB_GROUP)
message(STATUS "Using web user: ${ZM_WEB_USER}")
message(STATUS "Using web group: ${ZM_WEB_GROUP}")

# Some variables that zm expects
set(ZM_PID "${ZM_RUNDIR}/zm.pid")
set(ZM_CONFIG "${ZM_CONFIG_DIR}/zm.conf")
set(VERSION "${zoneminder_VERSION}")
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

# Generate files from the .in files
configure_file(cmake/zm.conf.in "${CMAKE_CURRENT_BINARY_DIR}/zm.conf" @ONLY)
configure_file(cmake/zoneminder-config.cmake "${CMAKE_CURRENT_SOURCE_DIR}/src/config.h" @ONLY)
configure_file(cmake/zmconfgen.pl.in "${CMAKE_CURRENT_BINARY_DIR}/zmconfgen.pl" @ONLY)
configure_file(cmake/zmlinkcontent.sh.in "${CMAKE_CURRENT_BINARY_DIR}/zmlinkcontent.sh" @ONLY)

# Process subdirectories
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
if((ZM_TARGET_DISTRO STREQUAL "f21") OR (ZM_TARGET_DISTRO STREQUAL "f20"))
	add_subdirectory(distros/fedora)
elseif((ZM_TARGET_DISTRO STREQUAL "el6") OR (ZM_TARGET_DISTRO STREQUAL "el7"))
	add_subdirectory(distros/redhat)
elseif(ZM_TARGET_DISTRO STREQUAL "OS13")
	add_subdirectory(distros/opensuse)
endif((ZM_TARGET_DISTRO STREQUAL "f21") OR (ZM_TARGET_DISTRO STREQUAL "f20"))

# Print optional libraries detection status
message(STATUS "Optional libraries found:${optlibsfound}")
message(STATUS "Optional libraries not found:${optlibsnotfound}")

# Run ZM configuration generator
message(STATUS "Running ZoneMinder configuration generator")
execute_process(COMMAND perl ./zmconfgen.pl RESULT_VARIABLE zmconfgen_result)
if(zmconfgen_result EQUAL 0)
	message(STATUS "ZoneMinder configuration generator completed successfully")
else(zmconfgen_result EQUAL 0)
	message(FATAL_ERROR "ZoneMinder configuration generator failed. Exit code: ${zmconfgen_result}")
endif(zmconfgen_result EQUAL 0)

# Install zm.conf
install(FILES "${CMAKE_CURRENT_BINARY_DIR}/zm.conf" DESTINATION "${ZM_CONFIG_DIR}")

# Uninstall target
configure_file(
    "${CMAKE_CURRENT_SOURCE_DIR}/cmake/cmake_uninstall.cmake.in"
    "${CMAKE_CURRENT_BINARY_DIR}/cmake/cmake_uninstall.cmake"
    IMMEDIATE @ONLY)
add_custom_target(uninstall
    COMMAND ${CMAKE_COMMAND} -P ${CMAKE_CURRENT_BINARY_DIR}/cmake/cmake_uninstall.cmake)

