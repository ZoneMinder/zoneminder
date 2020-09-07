#ifndef ZM_CMAKE_CONFIG_H
#define ZM_CMAKE_CONFIG_H

/* This file is used by cmake to create config.h for ZM */

/* General system checks */
#cmakedefine BSD 1
#cmakedefine SOLARIS 1
#cmakedefine HAVE_LINUX_VIDEODEV_H 1
#cmakedefine HAVE_LIBV4L1_VIDEODEV_H 1
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
#cmakedefine HAVE_OPENSSL_MD5_H 1
#cmakedefine HAVE_LIBCRYPTO 1
#cmakedefine HAVE_LIBPTHREAD 1
#cmakedefine HAVE_PTHREAD_H
#cmakedefine HAVE_LIBPCRE 1
#cmakedefine HAVE_PCRE_H 1
#cmakedefine HAVE_LIBGCRYPT 1
#cmakedefine HAVE_GCRYPT_H 1
#cmakedefine HAVE_LIBGNUTLS 1
#cmakedefine HAVE_GNUTLS_GNUTLS_H 1
#cmakedefine HAVE_LIBMYSQLCLIENT 1
#cmakedefine HAVE_MYSQL_H 1
#cmakedefine HAVE_LIBAVFORMAT 1
#cmakedefine HAVE_LIBAVFORMAT_AVFORMAT_H 1
#cmakedefine HAVE_LIBAVCODEC 1
#cmakedefine HAVE_LIBAVCODEC_AVCODEC_H 1
#cmakedefine HAVE_LIBAVDEVICE 1
#cmakedefine HAVE_LIBAVDEVICE_AVDEVICE_H 1
#cmakedefine HAVE_LIBAVUTIL 1
#cmakedefine HAVE_LIBAVUTIL_AVUTIL_H 1
#cmakedefine HAVE_LIBAVUTIL_MATHEMATICS_H 1
#cmakedefine HAVE_LIBAVUTIL_HWCONTEXT_H 1
#cmakedefine HAVE_LIBSWSCALE 1
#cmakedefine HAVE_LIBSWSCALE_SWSCALE_H 1
#cmakedefine HAVE_LIBSWRESAMPLE 1
#cmakedefine HAVE_LIBSWRESAMPLE_SWRESAMPLE_H 1
#cmakedefine HAVE_LIBAVRESAMPLE 1
#cmakedefine HAVE_LIBAVRESAMPLE_AVRESAMPLE_H 1
#cmakedefine HAVE_LIBVLC 1
#cmakedefine HAVE_VLC_VLC_H 1
#cmakedefine HAVE_LIBVNC 1
#cmakedefine HAVE_RFB_RFB_H 1
#cmakedefine HAVE_LIBX264 1
#cmakedefine HAVE_X264_H 1
#cmakedefine HAVE_LIBMP4V2 1
#cmakedefine HAVE_MP4_H 1
#cmakedefine HAVE_MP4V2_H 1
#cmakedefine HAVE_MP4V2_MP4V2_H 1
#cmakedefine HAVE_LIBJWT 1

/* Authenication checks */
#cmakedefine HAVE_MD5_OPENSSL 1
#cmakedefine HAVE_MD5_GNUTLS 1
#cmakedefine HAVE_DECL_MD5 1
#cmakedefine HAVE_DECL_GNUTLS_FINGERPRINT 1

/* Few ZM options that are needed by the source code */
#cmakedefine ZM_MEM_MAPPED 1

/* Its safe to assume that signal return type is void. This is a fix for zm_signal.h */
#define RETSIGTYPE void

#endif
