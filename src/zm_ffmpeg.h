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
#include "zm.h"
#include "zm_image.h"

#ifdef __cplusplus
extern "C" {
#endif

// AVUTIL
#if HAVE_LIBAVUTIL_AVUTIL_H
#include <libavutil/avutil.h>
#include <libavutil/base64.h>
#include <libavutil/mathematics.h>

/* LIBAVUTIL_VERSION_CHECK checks for the right version of libav and FFmpeg
 * The original source is vlc (in modules/codec/avcodec/avcommon_compat.h)
 * a is the major version
 * b and c the minor and micro versions of libav
 * d and e the minor and micro versions of FFmpeg */
#define LIBAVUTIL_VERSION_CHECK(a, b, c, d, e) \
  ( (LIBAVUTIL_VERSION_MICRO <  100 && LIBAVUTIL_VERSION_INT >= AV_VERSION_INT(a, b, c) ) || \
    (LIBAVUTIL_VERSION_MICRO >= 100 && LIBAVUTIL_VERSION_INT >= AV_VERSION_INT(a, d, e) ) )

#if LIBAVUTIL_VERSION_CHECK(50, 29, 0, 29, 0)
#include <libavutil/opt.h>
#else
#include <libavcodec/opt.h>
#endif
#elif HAVE_FFMPEG_AVUTIL_H
#include <ffmpeg/avutil.h>
#include <ffmpeg/base64.h>
#include <ffmpeg/mathematics.h>
#include <ffmpeg/opt.h>
#endif /* HAVE_LIBAVUTIL_AVUTIL_H */
  
#if defined(HAVE_LIBAVUTIL_AVUTIL_H) 
#if LIBAVUTIL_VERSION_CHECK(51, 42, 0, 74, 100)
  #define _AVPIXELFORMAT AVPixelFormat
#else
  #define _AVPIXELFORMAT PixelFormat
  #define AV_PIX_FMT_NONE PIX_FMT_NONE
  #define AV_PIX_FMT_RGB444 PIX_FMT_RGB444
  #define AV_PIX_FMT_RGB555 PIX_FMT_RGB555
  #define AV_PIX_FMT_RGB565 PIX_FMT_RGB565
  #define AV_PIX_FMT_BGR24 PIX_FMT_BGR24
  #define AV_PIX_FMT_RGB24 PIX_FMT_RGB24
  #define AV_PIX_FMT_BGRA PIX_FMT_BGRA
  #define AV_PIX_FMT_ARGB PIX_FMT_ARGB
  #define AV_PIX_FMT_ABGR PIX_FMT_ABGR
  #define AV_PIX_FMT_RGBA PIX_FMT_RGBA
  #define AV_PIX_FMT_GRAY8 PIX_FMT_GRAY8
  #define AV_PIX_FMT_YUYV422 PIX_FMT_YUYV422
  #define AV_PIX_FMT_YUV422P PIX_FMT_YUV422P
  #define AV_PIX_FMT_YUV411P PIX_FMT_YUV411P
  #define AV_PIX_FMT_YUV444P PIX_FMT_YUV444P
  #define AV_PIX_FMT_YUV410P PIX_FMT_YUV410P
  #define AV_PIX_FMT_YUV420P PIX_FMT_YUV420P
  #define AV_PIX_FMT_YUVJ444P PIX_FMT_YUVJ444P
  #define AV_PIX_FMT_UYVY422 PIX_FMT_UYVY422
  #define AV_PIX_FMT_YUVJ420P PIX_FMT_YUVJ420P
  #define AV_PIX_FMT_YUVJ422P PIX_FMT_YUVJ422P
  #define AV_PIX_FMT_UYVY422 PIX_FMT_UYVY422
  #define AV_PIX_FMT_UYYVYY411 PIX_FMT_UYYVYY411
  #define AV_PIX_FMT_BGR565 PIX_FMT_BGR565
  #define AV_PIX_FMT_BGR555 PIX_FMT_BGR555
  #define AV_PIX_FMT_BGR8 PIX_FMT_BGR8
  #define AV_PIX_FMT_BGR4 PIX_FMT_BGR4
  #define AV_PIX_FMT_BGR4_BYTE PIX_FMT_BGR4_BYTE
  #define AV_PIX_FMT_RGB8 PIX_FMT_RGB8
  #define AV_PIX_FMT_RGB4 PIX_FMT_RGB4
  #define AV_PIX_FMT_RGB4_BYTE PIX_FMT_RGB4_BYTE
  #define AV_PIX_FMT_NV12 PIX_FMT_NV12
  #define AV_PIX_FMT_NV21 PIX_FMT_NV21
  #define AV_PIX_FMT_RGB32_1 PIX_FMT_RGB32_1
  #define AV_PIX_FMT_BGR32_1 PIX_FMT_BGR32_1
  #define AV_PIX_FMT_GRAY16BE PIX_FMT_GRAY16BE
  #define AV_PIX_FMT_GRAY16LE PIX_FMT_GRAY16LE
  #define AV_PIX_FMT_YUV440P PIX_FMT_YUV440P
  #define AV_PIX_FMT_YUVJ440P PIX_FMT_YUVJ440P
  #define AV_PIX_FMT_YUVA420P PIX_FMT_YUVA420P
  //#define AV_PIX_FMT_VDPAU_H264 PIX_FMT_VDPAU_H264
  //#define AV_PIX_FMT_VDPAU_MPEG1 PIX_FMT_VDPAU_MPEG1
  //#define AV_PIX_FMT_VDPAU_MPEG2 PIX_FMT_VDPAU_MPEG2
#endif
#endif /* HAVE_LIBAVUTIL_AVUTIL_H */

// AVCODEC
#if HAVE_LIBAVCODEC_AVCODEC_H
#include <libavcodec/avcodec.h>

/*
 * LIBAVCODEC_VERSION_CHECK checks for the right version of libav and FFmpeg
 * The original source is vlc (in modules/codec/avcodec/avcommon_compat.h)
 * a is the major version
 * b and c the minor and micro versions of libav
 * d and e the minor and micro versions of FFmpeg */
#define LIBAVCODEC_VERSION_CHECK(a, b, c, d, e) \
  ( (LIBAVCODEC_VERSION_MICRO <  100 && LIBAVCODEC_VERSION_INT >= AV_VERSION_INT(a, b, c) ) || \
    (LIBAVCODEC_VERSION_MICRO >= 100 && LIBAVCODEC_VERSION_INT >= AV_VERSION_INT(a, d, e) ) )

#elif HAVE_FFMPEG_AVCODEC_H
#include <ffmpeg/avcodec.h>
#endif /* HAVE_LIBAVCODEC_AVCODEC_H */

#if defined(HAVE_LIBAVCODEC_AVCODEC_H)
#if LIBAVCODEC_VERSION_CHECK(54, 25, 0, 51, 100)
  #define _AVCODECID AVCodecID
#else
  #define _AVCODECID CodecID
#endif
#endif /* HAVE_LIBAVCODEC_AVCODEC_H */

// AVFORMAT
#if HAVE_LIBAVFORMAT_AVFORMAT_H
#include <libavformat/avformat.h>

/* LIBAVFORMAT_VERSION_CHECK checks for the right version of libav and FFmpeg
 * The original source is vlc (in modules/codec/avcodec/avcommon_compat.h)
 * a is the major version
 * b and c the minor and micro versions of libav
 * d and e the minor and micro versions of FFmpeg */
#define LIBAVFORMAT_VERSION_CHECK(a, b, c, d, e) \
  ( (LIBAVFORMAT_VERSION_MICRO <  100 && LIBAVFORMAT_VERSION_INT >= AV_VERSION_INT(a, b, c) ) || \
    (LIBAVFORMAT_VERSION_MICRO >= 100 && LIBAVFORMAT_VERSION_INT >= AV_VERSION_INT(a, d, e) ) )

#elif HAVE_FFMPEG_AVFORMAT_H
#include <ffmpeg/avformat.h>
#endif /* HAVE_LIBAVFORMAT_AVFORMAT_H */

// AVDEVICE
#if HAVE_LIBAVDEVICE_AVDEVICE_H
#include <libavdevice/avdevice.h>

/* LIBAVDEVICE_VERSION_CHECK checks for the right version of libav and FFmpeg
 * a is the major version
 * b and c the minor and micro versions of libav
 * d and e the minor and micro versions of FFmpeg */
#define LIBAVDEVICE_VERSION_CHECK(a, b, c, d, e) \
  ( (LIBAVDEVICE_VERSION_MICRO <  100 && LIBAVDEVICE_VERSION_INT >= AV_VERSION_INT(a, b, c) ) || \
    (LIBAVDEVICE_VERSION_MICRO >= 100 && LIBAVDEVICE_VERSION_INT >= AV_VERSION_INT(a, d, e) ) )

#elif HAVE_FFMPEG_AVDEVICE_H
#include <ffmpeg/avdevice.h>
#endif /* HAVE_LIBAVDEVICE_AVDEVICE_H */

// SWSCALE
#if HAVE_LIBSWSCALE_SWSCALE_H
#include <libswscale/swscale.h>

/* LIBSWSCALE_VERSION_CHECK checks for the right version of libav and FFmpeg
 * a is the major version
 * b and c the minor and micro versions of libav
 * d and e the minor and micro versions of FFmpeg */
#define LIBSWSCALE_VERSION_CHECK(a, b, c, d, e) \
  ( (LIBSWSCALE_VERSION_MICRO <  100 && LIBSWSCALE_VERSION_INT >= AV_VERSION_INT(a, b, c) ) || \
    (LIBSWSCALE_VERSION_MICRO >= 100 && LIBSWSCALE_VERSION_INT >= AV_VERSION_INT(a, d, e) ) )

#elif HAVE_FFMPEG_SWSCALE_H
#include <ffmpeg/swscale.h>
#endif /* HAVE_LIBSWSCALE_SWSCALE_H */

#ifdef __cplusplus
}
#endif

#if ( HAVE_LIBAVUTIL_AVUTIL_H || HAVE_LIBAVCODEC_AVCODEC_H || HAVE_LIBAVFORMAT_AVFORMAT_H || HAVE_LIBAVDEVICE_AVDEVICE_H )

#if !LIBAVFORMAT_VERSION_CHECK(52, 107, 0, 107, 0)
 #if defined(AVIO_WRONLY)
   #define AVIO_FLAG_WRITE AVIO_WRONLY
 #else
   #define AVIO_FLAG_WRITE URL_WRONLY
 #endif
#endif

#if HAVE_LIBAVUTIL
enum _AVPIXELFORMAT GetFFMPEGPixelFormat(unsigned int p_colours, unsigned p_subpixelorder);
#endif // HAVE_LIBAVUTIL


/* SWScale wrapper class to make our life easier and reduce code reuse */
#if HAVE_LIBSWSCALE && HAVE_LIBAVUTIL
class SWScale {
public:
  SWScale();
  ~SWScale();
  int SetDefaults(enum _AVPIXELFORMAT in_pf, enum _AVPIXELFORMAT out_pf, unsigned int width, unsigned int height);
  int ConvertDefaults(const Image* img, uint8_t* out_buffer, const size_t out_buffer_size);
  int ConvertDefaults(const uint8_t* in_buffer, const size_t in_buffer_size, uint8_t* out_buffer, const size_t out_buffer_size);
  int Convert(const Image* img, uint8_t* out_buffer, const size_t out_buffer_size, enum _AVPIXELFORMAT in_pf, enum _AVPIXELFORMAT out_pf, unsigned int width, unsigned int height);
  int Convert(const uint8_t* in_buffer, const size_t in_buffer_size, uint8_t* out_buffer, const size_t out_buffer_size, enum _AVPIXELFORMAT in_pf, enum _AVPIXELFORMAT out_pf, unsigned int width, unsigned int height);

protected:
  bool gotdefaults;
  struct SwsContext* swscale_ctx;
  AVFrame* input_avframe;
  AVFrame* output_avframe;
  enum _AVPIXELFORMAT default_input_pf;
  enum _AVPIXELFORMAT default_output_pf;
  unsigned int default_width;
  unsigned int default_height;
};
#endif // HAVE_LIBSWSCALE && HAVE_LIBAVUTIL

#if !LIBAVCODEC_VERSION_CHECK(54, 25, 0, 51, 100)
#define AV_CODEC_ID_NONE CODEC_ID_NONE
#define AV_CODEC_ID_PCM_MULAW CODEC_ID_PCM_MULAW
#define AV_CODEC_ID_PCM_ALAW CODEC_ID_PCM_ALAW
#define AV_CODEC_ID_PCM_S16BE CODEC_ID_PCM_S16BE
#define AV_CODEC_ID_QCELP CODEC_ID_QCELP
#define AV_CODEC_ID_MP2 CODEC_ID_MP2
#define AV_CODEC_ID_MP3 CODEC_ID_MP3
#define AV_CODEC_ID_MJPEG CODEC_ID_MJPEG
#define AV_CODEC_ID_H261 CODEC_ID_H261
#define AV_CODEC_ID_MPEG1VIDEO CODEC_ID_MPEG1VIDEO
#define AV_CODEC_ID_MPEG2VIDEO CODEC_ID_MPEG2VIDEO
#define AV_CODEC_ID_MPEG2TS CODEC_ID_MPEG2TS
#define AV_CODEC_ID_H263 CODEC_ID_H263
#define AV_CODEC_ID_H264 CODEC_ID_H264
#define AV_CODEC_ID_MPEG4 CODEC_ID_MPEG4
#define AV_CODEC_ID_AAC CODEC_ID_AAC
#define AV_CODEC_ID_AMR_NB CODEC_ID_AMR_NB
#endif

/*
 * Some versions of libav does not contain this definition.
 */
#ifndef AV_ERROR_MAX_STRING_SIZE
#define AV_ERROR_MAX_STRING_SIZE 64
#endif

/*
 * C++ friendly version of av_err2str taken from http://libav-users.943685.n4.nabble.com/Libav-user-g-4-7-2-fails-to-compile-av-err2str-td4656417.html.
 * Newer g++ versions fail with "error: taking address of temporary array" when using native libav version.
 */
#ifdef  __cplusplus

  inline static const std::string av_make_error_string(int errnum)
  {
    char errbuf[AV_ERROR_MAX_STRING_SIZE];
#if LIBAVUTIL_VERSION_CHECK(50, 13, 0, 13, 0)
    av_strerror(errnum, errbuf, AV_ERROR_MAX_STRING_SIZE);
#else
    snprintf(errbuf, AV_ERROR_MAX_STRING_SIZE, "libav error %d", errnum);
#endif
    return (std::string)errbuf;
  }

  #undef av_err2str
  #define av_err2str(errnum) av_make_error_string(errnum).c_str()

  #endif // __cplusplus


#endif // ( HAVE_LIBAVUTIL_AVUTIL_H || HAVE_LIBAVCODEC_AVCODEC_H || HAVE_LIBAVFORMAT_AVFORMAT_H || HAVE_LIBAVDEVICE_AVDEVICE_H )

#endif // ZM_FFMPEG_H
