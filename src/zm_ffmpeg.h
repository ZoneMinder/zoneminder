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
#if LIBAVUTIL_VERSION_INT > AV_VERSION_INT(50, 28, 0)
#include <libavutil/opt.h>
#else
#include <libavcodec/opt.h>
#endif
#elif HAVE_FFMPEG_AVUTIL_H
#include <ffmpeg/avutil.h>
#include <ffmpeg/base64.h>
#include <ffmpeg/mathematics.h>
#include <ffmpeg/opt.h>
#endif

// AVCODEC
#if HAVE_LIBAVCODEC_AVCODEC_H
#include <libavcodec/avcodec.h>
#elif HAVE_FFMPEG_AVCODEC_H
#include <ffmpeg/avcodec.h>
#endif
	
#if defined(HAVE_LIBAVCODEC_AVCODEC_H)
#if LIBAVCODEC_VERSION_INT >= AV_VERSION_INT(54,25,0)
    #define _AVCODECID AVCodecID
#else
    #define _AVCODECID CodecID
#endif
#endif

// AVFORMAT
#if HAVE_LIBAVFORMAT_AVFORMAT_H
#include <libavformat/avformat.h>
#elif HAVE_FFMPEG_AVFORMAT_H
#include <ffmpeg/avformat.h>
#endif

// AVDEVICE
#if HAVE_LIBAVDEVICE_AVDEVICE_H
#include <libavdevice/avdevice.h>
#elif HAVE_FFMPEG_AVDEVICE_H
#include <ffmpeg/avdevice.h>
#endif

// SWSCALE
#if HAVE_LIBSWSCALE_SWSCALE_H
#include <libswscale/swscale.h>
#elif HAVE_FFMPEG_SWSCALE_H
#include <ffmpeg/swscale.h>
#endif

#ifdef __cplusplus
}
#endif

#if ( HAVE_LIBAVUTIL_AVUTIL_H || HAVE_LIBAVCODEC_AVCODEC_H || HAVE_LIBAVFORMAT_AVFORMAT_H || HAVE_LIBAVDEVICE_AVDEVICE_H )

#if LIBAVFORMAT_VERSION_INT < AV_VERSION_INT(53, 4, 0)
 #if defined(AVIO_WRONLY)
   #define AVIO_FLAG_WRITE AVIO_WRONLY
 #else
   #define AVIO_FLAG_WRITE URL_WRONLY
 #endif
#endif

/* Fix for not having SWS_CPU_CAPS_SSE2 defined */
#ifndef SWS_CPU_CAPS_SSE2
#define SWS_CPU_CAPS_SSE2     0x02000000
#endif


#if HAVE_LIBAVUTIL
enum PixelFormat GetFFMPEGPixelFormat(unsigned int p_colours, unsigned p_subpixelorder);
#endif // HAVE_LIBAVUTIL


/* SWScale wrapper class to make our life easier and reduce code reuse */
#if HAVE_LIBSWSCALE && HAVE_LIBAVUTIL
class SWScale {
public:
	SWScale();
	~SWScale();
	int SetDefaults(enum PixelFormat in_pf, enum PixelFormat out_pf, unsigned int width, unsigned int height);
	int ConvertDefaults(const Image* img, uint8_t* out_buffer, const size_t out_buffer_size);
	int ConvertDefaults(const uint8_t* in_buffer, const size_t in_buffer_size, uint8_t* out_buffer, const size_t out_buffer_size);
	int Convert(const Image* img, uint8_t* out_buffer, const size_t out_buffer_size, enum PixelFormat in_pf, enum PixelFormat out_pf, unsigned int width, unsigned int height);
	int Convert(const uint8_t* in_buffer, const size_t in_buffer_size, uint8_t* out_buffer, const size_t out_buffer_size, enum PixelFormat in_pf, enum PixelFormat out_pf, unsigned int width, unsigned int height);

protected:
	bool gotdefaults;
	struct SwsContext* swscale_ctx;
	AVFrame* input_avframe;
	AVFrame* output_avframe;
	enum PixelFormat default_input_pf;
	enum PixelFormat default_output_pf;
	unsigned int default_width;
	unsigned int default_height;
};
#endif // HAVE_LIBSWSCALE && HAVE_LIBAVUTIL

#if LIBAVCODEC_VERSION_INT < AV_VERSION_INT(54, 25, 0)
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
#if LIBAVUTIL_VERSION_INT >= AV_VERSION_INT(50, 12, 13)
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
