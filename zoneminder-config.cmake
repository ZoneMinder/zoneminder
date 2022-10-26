#ifndef ZM_CMAKE_CONFIG_H
#define ZM_CMAKE_CONFIG_H

/* This file is used by cmake to create config.h for ZM */

/* General system checks */
#cmakedefine BSD 1
#cmakedefine SOLARIS 1
#cmakedefine HAVE_LINUX_VIDEODEV2_H 1
#cmakedefine HAVE_EXECINFO_H 1
#cmakedefine HAVE_UCONTEXT_H 1
#cmakedefine HAVE_SYS_SENDFILE_H 1
#cmakedefine HAVE_SYS_SYSCALL_H 1
#cmakedefine HAVE_SYSCALL 1
#cmakedefine HAVE_SENDFILE 1
#cmakedefine HAVE_DECL_BACKTRACE 1
#cmakedefine HAVE_DECL_BACKTRACE_SYMBOLS 1
#cmakedefine HAVE_POSIX_MEMALIGN 1
#cmakedefine HAVE_SIGINFO_T 1
#cmakedefine HAVE_UCONTEXT_T 1

/* Library checks and their header files */
#cmakedefine HAVE_LIBZLIB 1
#cmakedefine HAVE_ZLIB_H 1
#cmakedefine HAVE_LIBCURL 1
#cmakedefine HAVE_CURL_CURL_H 1
#cmakedefine HAVE_LIBJPEG 1
#cmakedefine HAVE_JPEGLIB_H 1
#cmakedefine HAVE_LIBOPENSSL 1
#cmakedefine HAVE_LIBPTHREAD 1
#cmakedefine HAVE_PTHREAD_H
#cmakedefine HAVE_LIBPCRE 1
#cmakedefine HAVE_PCRE_H 1
#cmakedefine HAVE_LIBGNUTLS 1
#cmakedefine HAVE_LIBMYSQLCLIENT 1
#cmakedefine HAVE_MYSQL_H 1
#cmakedefine HAVE_LIBSOCI 1
#cmakedefine HAVE_LIBSOCI_MYSQL 1
#cmakedefine HAVE_LIBSOCI_POSTGRESQL 1
#cmakedefine HAVE_LIBAVUTIL_HWCONTEXT_H 1
#cmakedefine HAVE_LIBVLC 1
#cmakedefine HAVE_VLC_VLC_H 1
#cmakedefine HAVE_LIBVNC 1
#cmakedefine HAVE_RFB_RFB_H 1
#cmakedefine HAVE_LIBJWT 1
#cmakedefine HAVE_RTSP_SERVER 1

/* Few ZM options that are needed by the source code */
#cmakedefine ZM_MEM_MAPPED 1
#cmakedefine ZM_HAS_V4L2 1

/* Its safe to assume that signal return type is void. This is a fix for zm_signal.h */
#define RETSIGTYPE void

#endif
