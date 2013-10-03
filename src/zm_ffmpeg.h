/*
 * ZoneMinder FFMPEG Interface, $Date$, $Revision$
 * Copyright (C) 2001-2008 Philip Coombes
 * 
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
*/  

#ifndef ZM_FFMPEG_H
#define ZM_FFMPEG_H
#include <stdint.h>
#if HAVE_LIBAVCODEC

#ifdef __cplusplus
extern "C" {
#endif
#if HAVE_LIBAVUTIL_AVUTIL_H
#include <libavutil/avutil.h>
#include <libavutil/base64.h>
#elif HAVE_FFMPEG_AVUTIL_H
#include <ffmpeg/avutil.h>
#include <ffmpeg/base64.h>
/*#else
#error "No location for avutils.h found"*/
#endif
#if HAVE_LIBAVCODEC_AVCODEC_H
#include <libavcodec/avcodec.h>
#if LIBAVCODEC_VERSION_MAJOR < 54
#include <libavcodec/opt.h>
#endif
#endif
#if HAVE_LIBAVFORMAT_AVFORMAT_H
#include <libavformat/avformat.h>
#elif HAVE_FFMPEG_AVFORMAT_H
#include <ffmpeg/avformat.h>
/*#else
#error "No location for avformat.h found"*/
#endif
#if HAVE_LIBSWSCALE
#if HAVE_LIBSWSCALE_SWSCALE_H
#include <libswscale/swscale.h>
#include <libavutil/mathematics.h> // this is a fix for error: 'av_rescale_q' was not declared in this scope
#elif HAVE_FFMPEG_SWSCALE_H
#include <ffmpeg/swscale.h>
/*#else
#error "No location for swscale.h found"*/
#endif
#endif // HAVE_LIBSWSCALE
#ifdef __cplusplus
}
#endif

#if LIBAVFORMAT_VERSION_INT < AV_VERSION_INT(53, 4, 0)
 #if defined(AVIO_WRONLY)
   #define AVIO_FLAG_WRITE AVIO_WRONLY
 #else
   #define AVIO_FLAG_WRITE URL_WRONLY
 #endif
#endif

#if FFMPEG_VERSION_INT == 0x000408
#define ZM_FFMPEG_048	1
#elif FFMPEG_VERSION_INT == 0x000409
#if LIBAVCODEC_VERSION_INT < ((50<<16)+(0<<8)+0)
#define ZM_FFMPEG_049	1
#else // LIBAVCODEC_VERSION_INT
#define ZM_FFMPEG_SVN	1
#endif // LIBAVCODEC_VERSION_INT
#else // FFMPEG_VERSION_INT
#define ZM_FFMPEG_SVN	1
#endif // FFMPEG_VERSION_INT

/* Fix for not having SWS_CPU_CAPS_SSE2 defined */
#ifndef SWS_CPU_CAPS_SSE2
#define SWS_CPU_CAPS_SSE2     0x02000000
#endif
                               
#endif // HAVE_LIBAVCODEC

#endif // ZM_FFMPEG_H

