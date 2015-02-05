AC_PREREQ(2.59)
AC_INIT(zm,1.28.1,[http://www.zoneminder.com/forums/ - Please check FAQ first],zoneminder,http://www.zoneminder.com/downloads.html)
AM_INIT_AUTOMAKE
AC_CONFIG_SRCDIR(src/zm.h)
AC_CONFIG_HEADERS(config.h)

AC_SUBST([AM_CXXFLAGS], [-D__STDC_CONSTANT_MACROS])

AC_SUBST(VERSION)
#
# Platform specific setup
#
#############################
AC_CANONICAL_HOST
# Check for which host we are on and setup a few things
# specifically based on the host
case $host_os in
  darwin* )
        # Do something specific for mac
		HOST_OS='darwin'
        ;;
  linux*)
        # Do something specific for linux
		HOST_OS='linux'
        ;;
   *BSD*)
        # Do something specific for BSD
		HOST_OS='BSD'
        ;;
    *)
        #Default Case
        AC_MSG_ERROR([Your platform is not currently supported])
        ;;
esac

AC_SUBST(HOST_OS)

AC_ARG_VAR(ZM_DB_TYPE,[Type of the ZoneMinder database, default mysql])
AC_ARG_VAR(ZM_DB_HOST,[Hostname where ZoneMinder database located, default localhost])
AC_ARG_VAR(ZM_DB_NAME,[Name of ZoneMinder database, default zm])
AC_ARG_VAR(ZM_DB_USER,[Name of ZoneMinder database user, default zmuser])
AC_ARG_VAR(ZM_DB_PASS,[Password of ZoneMinder database user, default zmpass])
AC_ARG_VAR(ZM_SSL_LIB,[Library to use for ssl functions, default gnutls])
AC_ARG_VAR(ZM_MYSQL_ENGINE,[MySQL engine to use with database, default InnoDB])
AC_ARG_VAR(ZM_RUNDIR,[Location of transient process files, default /var/run/zm])
AC_ARG_VAR(ZM_SOCKDIR,[Location of Unix domain socket files, default /var/run/zm])
AC_ARG_VAR(ZM_TMPDIR,[Location of temporary files, default /var/tmp/zm])
AC_ARG_VAR(ZM_LOGDIR,[Location of generated log files, default /var/log/zm])
AC_ARG_VAR(ZM_CONFIG_DIR,[Location of ZoneMinder configuration, default system config directory])

if test "$ZM_DB_TYPE" == ""; then
	AC_SUBST(ZM_DB_TYPE,[mysql])
fi
if test "$ZM_DB_HOST" == ""; then
	AC_SUBST(ZM_DB_HOST,[localhost])
fi
if test "$ZM_DB_NAME" == ""; then
	AC_SUBST(ZM_DB_NAME,[zm])
fi
if test "$ZM_DB_USER" == ""; then
	AC_SUBST(ZM_DB_USER,[zmuser])
fi
if test "$ZM_DB_PASS" == ""; then
	AC_SUBST(ZM_DB_PASS,[zmpass])
fi
if test "$ZM_SSL_LIB" == ""; then
	AC_SUBST(ZM_SSL_LIB,gnutls)
fi
if test "$ZM_MYSQL_ENGINE" == ""; then
	AC_SUBST(ZM_MYSQL_ENGINE,InnoDB)
fi
if test "$ZM_RUNDIR" == ""; then
	AC_SUBST(ZM_RUNDIR,[/var/run/zm])
fi
if test "$ZM_SOCKDIR" == ""; then
	AC_SUBST(ZM_SOCKDIR,[/var/run/zm])
fi
if test "$ZM_TMPDIR" == ""; then
	AC_SUBST(ZM_TMPDIR,[/tmp/zm])
fi
if test "$ZM_LOGDIR" == ""; then
	AC_SUBST(ZM_LOGDIR,[/var/log/zm])
fi
AC_DEFINE_DIR([SYSCONFDIR],[sysconfdir],[Expanded configuration directory])
if test "$ZM_CONFIG_DIR" == ""; then
	AC_SUBST(ZM_CONFIG_DIR,[$SYSCONFDIR])
fi

LIB_ARCH=lib
AC_ARG_WITH(libarch,
    [  --with-libarch=<lib>       architecture library path to use, default lib],
    [LIB_ARCH=$with_libarch],
    AC_MSG_WARN([You can call configure with the --with-libarch option.
    This tells configure where to find architecture specific libraries.
    The default of 'lib' is usually ok but 64 bit machines may require lib64.
    e.g. --with-libarch=lib or --with-libarch=lib64])
)
AC_SUBST(LIB_ARCH)

LDFLAGS="-L/usr/lib/${build_alias} ${LDFLAGS}"

MYSQL_PREFIX=/usr
AC_ARG_WITH(mysql,
    [  --with-mysql=<path>        prefix of MySQL installation, default /usr],
    [MYSQL_PREFIX=$with_mysql],
    AC_MSG_WARN([You can call configure with the --with-mysql option.
    This tells configure where to find the MySql C library and headers if configure cannot
    locate them automatically.
    e.g. --with-mysql=/usr/local or --with-mysql=/usr])
)
AC_SUBST(MYSQL_PREFIX)
MYSQL_LIBS="-L${MYSQL_PREFIX}/${LIB_ARCH}/mysql"
MYSQL_CFLAGS="-I${MYSQL_PREFIX}/include"
AC_SUBST(MYSQL_LIBS)
AC_SUBST(MYSQL_CFLAGS)
LDFLAGS="$LDFLAGS ${MYSQL_LIBS}"


MARIADB_PREFIX=/usr
AC_ARG_WITH(mariadb,
    [  --with-mariadb=<path>        prefix of MariaDB installation, default /usr],
    [MYSQL_PREFIX=$with_mariadb],
    AC_MSG_WARN([You can call configure with the --with-mariadb option.
    This tells configure where to find the mariaDB C library and headers if configure cannot
    locate them automatically.
    e.g. --with-mariadb=/usr/local or --with-mariadb=/usr])
)
AC_SUBST(MARIADB_PREFIX)
MARIADB_LIBS="-L${MARIADB_PREFIX}/${LIB_ARCH}/mariadb"
MARIADB_CFLAGS="-I${MARIADB_PREFIX}/include"
AC_SUBST(MARIADB_LIBS)
AC_SUBST(MARIADB_CFLAGS)
LDFLAGS="$LDFLAGS ${MARIADB_LIBS}"

POLKIT_PREFIX=/usr
AC_ARG_WITH(polkit,
[ --with-polkit=<path> prefix of polkit root directory, default /usr],
[POLKIT_PREFIX=$with_polkit],
AC_MSG_WARN([You can call configure with the --with-polkit option.
This tells configure where to place the polkit policy files.])
)
AC_SUBST(POLKIT_PREFIX)
PKG_CHECK_MODULES(POLKIT, polkit-gobject-1)

FFMPEG_PREFIX=/usr
AC_ARG_WITH(ffmpeg,
    [  --with-ffmpeg=<path>       prefix of ffmpeg root directory for libavcodec etc, default /usr],
    [FFMPEG_PREFIX=$with_ffmpeg],
    AC_MSG_WARN([You can call configure with the --with-ffmpeg option.
    This tells configure where to find the ffmpeg root directory within which are the libavcodec 
    and libavformat files that can be used to build true MPEG streaming into ZoneMinder. Ensure that
    your copy of ffmpeg has installed libraries as well as binaries (use 'make installlib'). If you
    are using a local install of ffmpeg you may have to remove or rename a previous real installation
    as the headers and libraries from that will probably be picked up before your local copy.
    e.g. --with-ffmpeg=/usr/local])
)
AC_SUBST(FFMPEG_PREFIX)
FFMPEG_LIBS="-L${FFMPEG_PREFIX}/${LIB_ARCH}"
FFMPEG_CFLAGS="-I${FFMPEG_PREFIX}/include -D__STDC_CONSTANT_MACROS"
AC_SUBST(FFMPEG_LIBS)
AC_SUBST(FFMPEG_CFLAGS)

LDFLAGS="${FFMPEG_LIBS} $LDFLAGS"
CFLAGS="${FFMPEG_CFLAGS} $CFLAGS"
CPPFLAGS="${FFMPEG_CFLAGS} $CPPFLAGS"

EXTRA_LIBS=
AC_ARG_WITH(extralibs,
    [  --with-extralibs="<libs>"  string containing extra libraries to pass to link, default empty],
    [EXTRA_LIBS=$with_extralibs],
    AC_MSG_WARN([You can call configure with the --with-extralibs option.
    Ordinarily you will need to use this option only when your copy of ffmpeg has been built
    with support for additional formats and you would use this option to detail which additional
    libraries ffmpeg was built with so that it is able to link successfully with ZoneMinder.
    You will need to wrap this option in quotes if it contains any spaces.
    e.g. --with-extralibs="-lmp3lame"])
)
AC_SUBST(EXTRA_LIBS)

LDFLAGS="$LDFLAGS ${EXTRA_LIBS}"

AC_ARG_WITH(webdir,
    [  --with-webdir=<path>       prefix of web directory],
    [WEB_PREFIX=$with_webdir],
    AC_MSG_ERROR([You must call configure with the --with-webdir option.
    This tells configure where to install PHP and web files and scripts.
    e.g. --with-webdir=/var/www/html or --with-webdir=/www/vhtdocs/<site>])
)
AC_SUBST(WEB_PREFIX)

AC_ARG_WITH(cgidir,
    [  --with-cgidir=<path>       prefix of cgi directory],
    [CGI_PREFIX=$with_cgidir],
    AC_MSG_ERROR([You must call configure with the --with-cgidir option.
    This tells configure where to install cgi files and scripts.
    e.g. --with-cgidir=/var/www/cgi-bin or --with-webdir=/www/vhtdocs/<site>/cgi-bin])
)
AC_SUBST(CGI_PREFIX)

WEB_USER=apache
AC_ARG_WITH(webuser,
    [  --with-webuser=<user>      name of web user, default apache],
    [WEB_USER=$with_webuser],
    AC_MSG_WARN([You can call configure with the --with-webuser option.
    This tells configure what the user name of the web user is if it is not the default of 'apache'.
    e.g. --with-webuser=apache or --with-webuser=web])
)
AC_SUBST(WEB_USER)

WEB_GROUP=apache
AC_ARG_WITH(webgroup,
    [  --with-webgroup=<group>    name of web group, default apache],
    [WEB_GROUP=$with_webgroup],
    AC_MSG_WARN([You can call configure with the --with-webgroup option.
    This tells configure what the group name of the web group is if it is not the default of 'apache'.
    e.g. --with-webgroup=apache or --with-webgroup=web])
)
AC_SUBST(WEB_GROUP)

WEB_HOST=zm.local
AC_ARG_WITH(webhost,
    [  --with-webhost=<hostname>    name of web hostname, default zm.local],
    [WEB_HOST=$with_webhost],
    AC_MSG_WARN([You can call configure with the --with-webhost option.
    This tells configure what the host name is for name based virtual hosting. This is only used to populate the sample web/zmHttpd.conf file.
    e.g. --with-webhost=zm.localdomain])
)
AC_SUBST(WEB_HOST)

ENABLE_DEBUG=yes
AC_ARG_ENABLE(debug,
    [  --enable-debug=<yes|no>    enable or disable debug, default enabled],
    [ENABLE_DEBUG=$enable_debug],
    AC_MSG_WARN([You can call configure with the --enable-debug=<yes|no> or --disable-debug option.
    This tells configure whether to compile ZoneMinder with debug included. Although debug is included
    by default it is not output unless explicitly switched on elsewhere. These checks may induce a
    small penalty on performance and if you are after squeezing the maximum possible performance out
    of ZoneMinder you may use this switch to prevent debug from being compiled in.
    e.g. --enable-debug=yes or --disable-debug])
)
if test "$ENABLE_DEBUG" != "yes"; then
  AC_DEFINE(ZM_DBG_OFF,1,"Whether debug is switched off and compiled out")
fi

ENABLE_MMAP=yes
AC_ARG_ENABLE(mmap,
    [  --enable-mmap=<yes|no> enable or disabled mapped memory versus shared memory, default mapped],
    [ENABLE_MMAP=$enable_mmap],
    AC_MSG_WARN([You can call configure with the --enable-mmap=<yes|no> or --disable-mmap option.
    This tells configure whether to compile ZoneMinder with mmap support rather than IPC shared
    memory. This is a feature that uses memory mapped into files which all processes can share.
    Memory mapping requires less configuration and is more flexible than shared memory but may
    slow down your system unless the mapped files are configured to reside on a fast or RAM based
    filesystem which will normally be the case by default.
    e.g. --enable-mmap=yes or --disable-mmap])
)
if test "$ENABLE_MMAP" == "yes"; then
  AC_DEFINE(ZM_MEM_MAPPED,1,"Whether to use mapped rather than shared memory")
else
  AC_DEFINE(ZM_MEM_MAPPED,0,"Whether to use mapped rather than shared memory")
fi
AC_SUBST(ENABLE_MMAP)

# Compiler
AC_LANG_CPLUSPLUS

# Checks for programs.
AC_PROG_CXX
AC_PROG_CC
AC_PROG_INSTALL
AC_PROG_LN_S
AC_PROG_RANLIB
AC_PROG_MAKE_SET

# Checks for typedefs, structures, and compiler characteristics.
AC_HEADER_STDBOOL
AC_C_CONST
AC_TYPE_UID_T
AC_C_INLINE
AC_TYPE_MODE_T
AC_TYPE_SIZE_T
AC_HEADER_TIME
AC_STRUCT_TM
AC_TYPE_SIGNAL

AC_CHECK_TYPES(siginfo_t,,,[#include <signal.h>])
AC_CHECK_TYPES(ucontext_t,,,[#include <signal.h>])

# Checks for library functions.
AC_PROG_GCC_TRADITIONAL
AC_FUNC_MALLOC
AC_FUNC_MMAP
AC_FUNC_SELECT_ARGTYPES
AC_FUNC_STAT
AC_FUNC_STRFTIME
AC_FUNC_STRTOD
AC_FUNC_VPRINTF
AC_CHECK_FUNCS([gethostbyname gethostname gettimeofday memmove memset mkdir munmap posix_memalign putenv select sendfile socket sqrt strcasecmp strchr strcspn strerror strncasecmp strrchr strspn strstr strtol strtoull])
AC_CHECK_FUNCS([syscall sleep usleep ioctl ioctlsocket sigaction])

# Other programs
AC_CHECK_PROG(OPT_FFMPEG,ffmpeg,yes,no)
AC_PATH_PROG(PATH_FFMPEG,ffmpeg)
AC_CHECK_PROG(OPT_NETPBM,pnmscale,yes,no)
AC_PATH_PROG(PATH_NETPBM,pnmscale)
if test "$OPT_NETPBM" == "yes"; then
PATH_NETPBM=`dirname $PATH_NETPBM`
fi

# Checks for libraries.
AC_SEARCH_LIBS(mysql_init,[mysqlclient mariadbclient],,AC_MSG_ERROR(zm requires libmysqlclient.a or libmariadbclient.a))
AC_CHECK_LIB(jpeg,jpeg_start_compress,,AC_MSG_ERROR(zm requires libjpeg.a))
AC_CHECK_LIB(pthread,pthread_create,,AC_MSG_ERROR(zm requires libpthread.a))
AC_CHECK_LIB(dl,dlsym,,AC_MSG_ERROR(zm requires libdl.a))
if test "$ZM_SSL_LIB" == "openssl"; then
AC_CHECK_HEADERS(openssl/md5.h,,AC_MSG_WARN(zm requires openssl/md5.h header to be installed for openssl),)
AC_CHECK_LIB(crypto,MD5,,AC_MSG_WARN([libcrypto.a is required for authenticated streaming - use ZM_SSL_LIB option to select gnutls instead]))
else
AC_CHECK_HEADERS(gnutls/openssl.h,AC_SUBST(ZM_HAS_GNUTLS_OPENSSL,1),AC_SUBST(ZM_HAS_GNUTLS_OPENSSL,0),)
AC_CHECK_HEADERS(gnutls/gnutls.h,AC_SUBST(ZM_HAS_GNUTLS,1),AC_SUBST(ZM_HAS_GNUTLS,0),)
if test "$ZM_HAS_GNUTLS_OPENSSL" == "0" && test "$ZM_HAS_GNUTLS" == "0"; then
AC_MSG_WARN(gnutls is required for authenticated streaming - use ZM_SSL_LIB option to select openssl instead)
fi
AC_CHECK_HEADERS(gcrypt.h,,AC_MSG_WARN(zm requires libgcrypt headers to be installed for gnutls),)
AC_CHECK_LIB(gcrypt,gcry_check_version,,AC_MSG_WARN([libgcrypt.a is required for authenticated streaming - use ZM_SSL_LIB option to select openssl instead]))
AC_CHECK_LIB(gnutls,gnutls_fingerprint,,AC_MSG_WARN([libgnutls.a is required for authenticated streaming - use ZM_SSL_LIB option to select openssl instead]))
if test "$ZM_HAS_GNUTLS_OPENSSL" == "1"; then
AC_CHECK_LIB(gnutls-openssl,MD5,,AC_MSG_WARN([libgnutls.a is required for authenticated streaming - use ZM_SSL_LIB option to select openssl instead]))
fi
fi
AC_CHECK_LIB(pcre,pcre_compile,,AC_MSG_WARN(libpcre.a may be required for remote/network camera support))
AC_CHECK_LIB(z,zlibVersion)
AC_CHECK_LIB(x264,x264_predict_16x16_init)
AC_CHECK_LIB(avutil,av_malloc,,AC_MSG_WARN(libavutil.a may be required for MPEG streaming))
# Don't bother to warn about this one
AC_CHECK_LIB(avcore,av_image_copy,,)
AC_CHECK_LIB(avcodec,avcodec_version,,AC_MSG_WARN(libavcodec.a is required for MPEG streaming))
AC_CHECK_LIB(avformat,avformat_version,,AC_MSG_WARN(libavformat.a is required for MPEG streaming))
#AC_CHECK_LIB(avcodec,avcodec_open,,AC_MSG_WARN(libavcodec.a is required for MPEG streaming))
#AC_CHECK_LIB(avformat,av_new_stream,,AC_MSG_WARN(libavformat.a is required for MPEG streaming))
AC_CHECK_LIB(avdevice,avdevice_register_all,,AC_MSG_WARN(libavdevice.a may be required for MPEG streaming))
AC_CHECK_LIB(swscale,sws_scale,,,-lswscale)
AC_CHECK_LIB(vlc,libvlc_new,,AC_MSG_WARN(libvlc.a may be required for streaming))
AC_CHECK_LIB(bz2,BZ2_bzCompress,,AC_MSG_WARN(zm requires libbz2.a for recent versions of ffmpeg))
AC_CHECK_LIB(z,compress,,)
AC_CHECK_LIB(curl,curl_global_init,,)

# Checks for header files.
AC_FUNC_ALLOCA
AC_HEADER_STDC
AC_CHECK_HEADERS([fcntl.h limits.h memory.h stddef.h stdlib.h string.h strings.h sys/param.h sys/time.h syslog.h unistd.h values.h])
AC_CHECK_HEADERS([netdb.h netinet/in.h arpa/inet.h sys/ioctl.h sys/socket.h sys/un.h glob.h sys/sendfile.h])
AC_CHECK_HEADERS(execinfo.h,,,)
AC_CHECK_HEADERS(ucontext.h,,,)
AC_CHECK_HEADERS(sys/syscall.h,,,)
AC_CHECK_HEADERS(pthread.h,,,)
AC_CHECK_HEADERS(linux/videodev.h,AC_SUBST(ZM_HAS_V4L1,1),AC_SUBST(ZM_HAS_V4L1,0),)
AC_CHECK_HEADERS(linux/videodev2.h,AC_SUBST(ZM_HAS_V4L2,1),AC_SUBST(ZM_HAS_V4L2,0),)
if test "$ZM_HAS_V4L1" == "1" || test "$ZM_HAS_V4L2" == "1"; then
AC_SUBST(ZM_HAS_V4L,1)
else
AC_SUBST(ZM_HAS_V4L,0)
AC_MSG_WARN(zm requires Video4Linux or Video4Linux2 to be installed for analog or USB camera support)
fi
AC_CHECK_HEADERS(jpeglib.h,,AC_MSG_ERROR(zm requires libjpeg headers to be installed),)
AC_CHECK_HEADERS(mysql/mysql.h,,AC_MSG_ERROR(zm requires MySQL headers - check that MySQL development packages are installed),)
AC_LANG_PUSH([C])
AC_CHECK_HEADERS(libavutil/avutil.h,,,)
AC_CHECK_HEADERS(libavcodec/avcodec.h,,,)
AC_CHECK_HEADERS(libavformat/avformat.h,,,)
AC_CHECK_HEADERS(libswscale/swscale.h,,,)
AC_LANG_POP([C])
AC_CHECK_HEADERS(pcre/pcre.h,AC_SUBST(ZM_PCRE,"1"),,)
AC_CHECK_HEADERS(pcre.h,AC_SUBST(ZM_PCRE,"1"),,)
if test "$ENABLE_MMAP" == "yes"; then
AC_CHECK_HEADERS(sys/mman.h,,,)
AC_CHECK_HEADERS(fcntl.h,,,)
else
AC_CHECK_HEADERS(sys/ipc.h,,,)
AC_CHECK_HEADERS(sys/shm.h,,,)
fi
AC_CHECK_HEADERS(zlib.h,,,)
AC_CHECK_HEADERS(vlc/vlc.h,,,)
AC_CHECK_HEADERS(curl/curl.h,,,)

if test "$ZM_SSL_LIB" == "openssl"; then
AC_CHECK_DECLS(MD5,,AC_MSG_ERROR([zm requires openssl/md5.h - use ZM_SSL_LIB option to select gnutls instead]),[#include <stdlib.h>
#include <openssl/md5.h>])
else
if test "$ZM_HAS_GNUTLS_OPENSSL" == "1"; then
AC_CHECK_DECLS(MD5,,AC_MSG_ERROR([zm requires gnutls/openssl.h - use ZM_SSL_LIB option to select openssl instead]),[#include <stdlib.h>
#include <gnutls/openssl.h>])
else
AC_CHECK_DECLS(gnutls_fingerprint,,AC_MSG_ERROR([zm requires gnutls/gnutls.h - use ZM_SSL_LIB option to select openssl instead]),[#include <stdlib.h>
#include <gnutls/gnutls.h>])
fi
fi
AC_CHECK_DECLS(backtrace,,,[#include <execinfo.h>])
AC_CHECK_DECLS(backtrace_symbols,,,[#include <execinfo.h>])

AC_SUBST(LDFLAGS)

AC_PROG_PERL_VERSION(5.6.0)

# Compulsory perl modules
AC_PROG_PERL_MODULES(Sys::Syslog,,AC_MSG_ERROR(zm requires SYS:Syslog))
AC_PROG_PERL_MODULES(DBI,,AC_MSG_ERROR(zm requires DBI))
AC_PROG_PERL_MODULES(DBD::mysql,,AC_MSG_ERROR(zm requires DBD::mysql))
AC_PROG_PERL_MODULES(Getopt::Long,,AC_MSG_ERROR(zm requires Getopt::Long))
AC_PROG_PERL_MODULES(Time::HiRes,,AC_MSG_ERROR(zm requires Time::HiRes))
AC_PROG_PERL_MODULES(Date::Manip,,AC_MSG_ERROR(zm requires Date::Manip))
AC_PROG_PERL_MODULES(LWP::UserAgent,,AC_MSG_ERROR(zm requires LWP::UserAgent))
AC_PROG_PERL_MODULES(ExtUtils::MakeMaker,,AC_MSG_ERROR(zm requires ExtUtils::MakeMaker))
if test "$ENABLE_MMAP" == "yes"; then
AC_PROG_PERL_MODULES(Sys::Mmap,,AC_MSG_ERROR(zm requires Sys::Mmap for mapped memory - set --enable-mmap=no to use IPC shared memory instead))
fi

# Optional perl modules
AC_PROG_PERL_MODULES(Module::Load,,AC_MSG_WARN(Module::Load is required for PTZ camera control))
AC_PROG_PERL_MODULES(Device::SerialPort,,AC_MSG_WARN(Device::SerialPort is required for RS232/RS485 PTZ camera control))
AC_PROG_PERL_MODULES(Net::FTP,,AC_MSG_WARN(Net::FTP is required for automatic event uploading using ftp))
AC_PROG_PERL_MODULES(Net::SFTP::Foreign,,AC_MSG_WARN(Net::SFTP::Foreign is required for automatic event uploading using sftp))
AC_PROG_PERL_MODULES(Expect,,AC_MSG_WARN(Expect is required for automatic event uploading using sftp))
AC_PROG_PERL_MODULES(Archive::Tar,,AC_MSG_WARN(Archive::Tar may be required for automatic event uploading))
AC_PROG_PERL_MODULES(Archive::Zip,,AC_MSG_WARN(Archive::Zip may be required for automatic event uploading))
AC_PROG_PERL_MODULES(Net::SMTP,,AC_MSG_WARN(Net::SMTP may be required for automatic event email notification))
AC_PROG_PERL_MODULES(MIME::Lite,,AC_MSG_WARN(MIME::Lite may be required for automatic event email notification))
AC_PROG_PERL_MODULES(MIME::Entity,,AC_MSG_WARN(MIME::Entity may be required for automatic event email notification))
AC_PROG_PERL_MODULES(X10::ActiveHome,,AC_MSG_WARN(X10::ActiveHome is required for X.10 support))

AC_DEFINE_DIR([BINDIR],[bindir],[Expanded binary directory])
AC_DEFINE_DIR([LIBDIR],[libdir],[Expanded library directory])
AC_DEFINE_DIR([DATADIR],[datadir],[Expanded data directory])
AC_SUBST(PKGDATADIR,"$DATADIR/$PACKAGE")
AC_SUBST(ZM_PID,"$ZM_RUNDIR/zm.pid")
#AC_DEFINE_DIR([SYSCONFDIR],[sysconfdir],[Expanded configuration directory])
#AC_SUBST(ZM_CONFIG,"$SYSCONFDIR/zm.conf")
AC_SUBST(ZM_CONFIG,"$ZM_CONFIG_DIR/zm.conf")

# Slight hack for non-standard perl install paths
if test "$prefix" != "NONE"; then
    PERL_SITE_PREFIX=`perl -V:siteprefix | sed -e "s/.*='\(.*\)';/\1/"`
    PERL_SITE_LIB=`perl -V:installsitelib | sed -e "s/.*='\(.*\)';/\1/"`
    PERL_LIB_PATH=`echo $PERL_SITE_LIB | sed -e "s|^$PERL_SITE_PREFIX||"`
    EXTRA_PERL_LIB="use lib '$prefix$PERL_LIB_PATH'; # Include custom perl install path"
    PERL_MM_PARMS="\"PREFIX=$prefix INSTALLDIRS=vendor\""
else
    EXTRA_PERL_LIB="# Include from system perl paths only"
    PERL_MM_PARMS="\"INSTALLDIRS=vendor\""
fi
AC_SUBST(PERL_MM_PARMS)
AC_SUBST(EXTRA_PERL_LIB)


AC_CONFIG_FILES([Makefile zm.conf zmconfgen.pl db/Makefile db/zm_create.sql misc/Makefile misc/apache.conf misc/logrotate.conf misc/syslog.conf misc/com.zoneminder.systemctl.policy misc/com.zoneminder.systemctl.rules scripts/Makefile scripts/zm scripts/zmaudit.pl scripts/zmcontrol.pl scripts/zmdc.pl scripts/zmfilter.pl scripts/zmpkg.pl scripts/zmtrack.pl scripts/zmcamtool.pl scripts/zmsystemctl.pl scripts/zmtrigger.pl scripts/zmupdate.pl scripts/zmvideo.pl scripts/zmwatch.pl scripts/zmx10.pl scripts/zmdbbackup scripts/zmdbrestore scripts/zmeventdump scripts/zmlogrotate.conf scripts/ZoneMinder/lib/ZoneMinder/Base.pm scripts/ZoneMinder/lib/ZoneMinder/Config.pm scripts/ZoneMinder/lib/ZoneMinder/Memory.pm scripts/ZoneMinder/lib/ZoneMinder/ConfigData.pm src/Makefile src/zm_config.h web/Makefile web/ajax/Makefile web/css/Makefile web/graphics/Makefile web/includes/Makefile web/includes/config.php web/js/Makefile web/lang/Makefile web/skins/Makefile web/skins/classic/Makefile web/skins/classic/ajax/Makefile web/skins/classic/css/Makefile web/skins/classic/graphics/Makefile web/skins/classic/includes/Makefile web/skins/classic/js/Makefile web/skins/classic/lang/Makefile web/skins/classic/views/Makefile web/skins/classic/views/css/Makefile web/skins/classic/views/js/Makefile web/skins/mobile/Makefile web/skins/mobile/ajax/Makefile web/skins/mobile/css/Makefile web/skins/mobile/graphics/Makefile web/skins/mobile/includes/Makefile web/skins/mobile/lang/Makefile web/skins/mobile/views/Makefile web/skins/mobile/views/css/Makefile web/tools/Makefile web/tools/mootools/Makefile web/views/Makefile web/skins/xml/Makefile web/skins/xml/views/Makefile web/skins/xml/includes/Makefile web/skins/flat/Makefile web/skins/flat/ajax/Makefile web/skins/flat/css/Makefile web/skins/flat/graphics/Makefile web/skins/flat/includes/Makefile web/skins/flat/js/Makefile web/skins/flat/lang/Makefile web/skins/flat/views/Makefile web/skins/flat/views/css/Makefile web/skins/flat/views/js/Makefile])

# Create the definitions for compilation and defaults for the database
AC_CONFIG_COMMANDS([src/zm_config_defines.h],[perl ./zmconfgen.pl])
# Manually generate the perl Makefile maker
AC_CONFIG_COMMANDS([scripts/ZoneMinder/Makefile],[(cd scripts/ZoneMinder; echo "perl Makefile.PL $PERL_MM_PARMS"; perl Makefile.PL $PERL_MM_PARMS)],[PERL_MM_PARMS=$PERL_MM_PARMS])

AC_OUTPUT
