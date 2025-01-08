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
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
*/

#ifndef ZM_FFMPEG_H
#define ZM_FFMPEG_H

#include "zm_config.h"
#include "zm_define.h"

#include <memory>

extern "C" {

#ifdef HAVE_LIBSWRESAMPLE
  #include "libswresample/swresample.h"
#else
  #ifdef HAVE_LIBAVRESAMPLE
    #include "libavresample/avresample.h"
  #endif
#endif

// AVUTIL
#if HAVE_LIBAVUTIL_AVUTIL_H
#include "libavutil/avassert.h"
#include <libavutil/avutil.h>
#include <libavutil/base64.h>
#include <libavutil/mathematics.h>
#include <libavutil/avstring.h>
#include "libavutil/audio_fifo.h"
#include "libavutil/imgutils.h"
#if HAVE_LIBAVUTIL_HWCONTEXT_H
  #include "libavutil/hwcontext.h"
#endif

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

#if LIBAVUTIL_VERSION_CHECK(54, 6, 0, 6, 0)
#include <libavutil/imgutils.h>
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
#elif HAVE_FFMPEG_AVCODEC_H
#include <ffmpeg/avcodec.h>
#endif /* HAVE_LIBAVCODEC_AVCODEC_H */

/*
 * LIBAVCODEC_VERSION_CHECK checks for the right version of libav and FFmpeg
 * The original source is vlc (in modules/codec/avcodec/avcommon_compat.h)
 * a is the major version
 * b and c the minor and micro versions of libav
 * d and e the minor and micro versions of FFmpeg */
#define LIBAVCODEC_VERSION_CHECK(a, b, c, d, e) \
    ( (LIBAVCODEC_VERSION_MICRO <  100 && LIBAVCODEC_VERSION_INT >= AV_VERSION_INT(a, b, c) ) || \
      (LIBAVCODEC_VERSION_MICRO >= 100 && LIBAVCODEC_VERSION_INT >= AV_VERSION_INT(a, d, e) ) )

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

/* A single function to initialize ffmpeg, to avoid multiple initializations */
void FFMPEGInit();
void FFMPEGDeInit();

#if HAVE_LIBAVUTIL
enum _AVPIXELFORMAT GetFFMPEGPixelFormat(unsigned int p_colours, unsigned p_subpixelorder);
#endif // HAVE_LIBAVUTIL

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

    inline static const std::string av_make_error_string(int errnum) {
        static char errbuf[AV_ERROR_MAX_STRING_SIZE];
#if LIBAVUTIL_VERSION_CHECK(50, 13, 0, 13, 0)
        av_strerror(errnum, errbuf, AV_ERROR_MAX_STRING_SIZE);
#else
		snprintf(errbuf, AV_ERROR_MAX_STRING_SIZE, "libav error %d", errnum);
#endif
        return (std::string)errbuf;
    }

    #undef av_err2str
    #define av_err2str(errnum) av_make_error_string(errnum).c_str()

  /* The following is copied directly from newer ffmpeg */
  #if LIBAVUTIL_VERSION_CHECK(52, 7, 0, 17, 100)
  #else
    int av_dict_parse_string(AVDictionary **pm, const char *str,
                            const char *key_val_sep, const char *pairs_sep,
                            int flags);
  #endif

#endif // __cplusplus


#endif // ( HAVE_LIBAVUTIL_AVUTIL_H || HAVE_LIBAVCODEC_AVCODEC_H || HAVE_LIBAVFORMAT_AVFORMAT_H || HAVE_LIBAVDEVICE_AVDEVICE_H )

#ifndef av_rescale_delta
/**
 * Rescale a timestamp while preserving known durations.
 */
int64_t av_rescale_delta(AVRational in_tb, int64_t in_ts,  AVRational fs_tb, int duration, int64_t *last, AVRational out_tb);
#endif

#ifndef av_clip64
/**
 * Clip a signed 64bit integer value into the amin-amax range.
 * @param a value to clip
 * @param amin minimum value of the clip range
 * @param amax maximum value of the clip range
 * @return clipped value
 */
static av_always_inline av_const int64_t av_clip64_c(int64_t a, int64_t amin, int64_t amax)
{
    if      (a < amin) return amin;
    else if (a > amax) return amax;
    else               return a;
}

#define av_clip64        av_clip64_c
#endif

void zm_dump_stream_format(AVFormatContext *ic, int i, int index, int is_output);
void zm_dump_codec(const AVCodecContext *codec);
#if LIBAVCODEC_VERSION_CHECK(57, 64, 0, 64, 0)
void zm_dump_codecpar(const AVCodecParameters *par);
#endif

#if LIBAVUTIL_VERSION_CHECK(57, 28, 100, 28, 0)
#define zm_dump_frame(frame, text) Debug(1, "%s: format %d %s sample_rate %" PRIu32 " nb_samples %d" \
      " layout %" PRIu64 " pts %" PRId64, \
      text, \
      frame->format, \
      av_get_sample_fmt_name((AVSampleFormat)frame->format), \
      frame->sample_rate, \
      frame->nb_samples, \
      frame->ch_layout.u.mask, \
      frame->pts \
      );
#else
#define zm_dump_frame(frame, text) Debug(1, "%s: format %d %s sample_rate %" PRIu32 " nb_samples %d" \
      " layout %" PRIu64 " pts %" PRId64, \
      text, \
      frame->format, \
      av_get_sample_fmt_name((AVSampleFormat)frame->format), \
      frame->sample_rate, \
      frame->nb_samples, \
      frame->channel_layout, \
      frame->pts \
      );
#endif

#if LIBAVUTIL_VERSION_CHECK(58, 7, 100, 7, 0)
#define zm_dump_video_frame(frame, text) Debug(1, "%s: format %d %s %dx%d linesize:%dx%d pts: %" PRId64 " keyframe: %d", \
      text, \
      frame->format, \
      av_get_pix_fmt_name((AVPixelFormat)frame->format), \
      frame->width, \
      frame->height, \
      frame->linesize[0], frame->linesize[1], \
      frame->pts, \
      frame->flags && AV_FRAME_FLAG_KEY \
      );
#elif LIBAVUTIL_VERSION_CHECK(54, 4, 0, 74, 100)
#define zm_dump_video_frame(frame, text) Debug(1, "%s: format %d %s %dx%d linesize:%dx%d pts: %" PRId64 " keyframe: %d", \
      text, \
      frame->format, \
      av_get_pix_fmt_name((AVPixelFormat)frame->format), \
      frame->width, \
      frame->height, \
      frame->linesize[0], frame->linesize[1], \
      frame->pts, \
      frame->key_frame \
      );
#else
#define zm_dump_video_frame(frame,text) Debug(1, "%s: format %d %s %dx%d linesize:%dx%d pts: %" PRId64, \
      text, \
      frame->format, \
      "unsupported", \
      frame->width, \
      frame->height, \
      frame->linesize[0], frame->linesize[1], \
      frame->pts \
      );
#endif

#if LIBAVCODEC_VERSION_CHECK(57, 64, 0, 64, 0)
# define AV_PACKET_DURATION_FMT PRId64
#else
# define AV_PACKET_DURATION_FMT "d"
#endif

#if LIBAVCODEC_VERSION_CHECK(57, 64, 0, 64, 0)
#define CODEC_TYPE(stream) stream->codecpar->codec_type
#else
#define CODEC_TYPE(stream) stream->codec->codec_type
#endif
#if LIBAVCODEC_VERSION_CHECK(57, 64, 0, 64, 0)
#define CODEC(stream) stream->codecpar
#else
#define CODEC(stream) stream->codec
#endif


#ifndef DBG_OFF
# define ZM_DUMP_PACKET(pkt, text) \
  Debug(2, "%s: pts: %" PRId64 ", dts: %" PRId64 \
    ", size: %d, stream_index: %d, flags: %04x, keyframe(%d) pos: %" PRId64 ", duration: %" AV_PACKET_DURATION_FMT, \
    text,\
    pkt->pts,\
    pkt->dts,\
    pkt->size,\
    pkt->stream_index,\
    pkt->flags,\
    pkt->flags & AV_PKT_FLAG_KEY,\
    pkt->pos,\
    pkt->duration)

# define ZM_DUMP_STREAM_PACKET(stream, pkt, text) \
  if (logDebugging()) { \
    double pts_time = static_cast<double>(av_rescale_q(pkt->pts, stream->time_base, AV_TIME_BASE_Q)) / AV_TIME_BASE; \
    \
    Debug(2, "%s: pts: %" PRId64 " * %u/%u=%f, dts: %" PRId64 \
      ", size: %d, stream_index: %d, %s flags: %04x, keyframe(%d) pos: %" PRId64", duration: %" AV_PACKET_DURATION_FMT, \
      text, \
      pkt->pts, \
      stream->time_base.num, \
      stream->time_base.den, \
      pts_time, \
      pkt->dts, \
      pkt->size, \
      pkt->stream_index, \
      av_get_media_type_string(CODEC_TYPE(stream)), \
      pkt->flags, \
      pkt->flags & AV_PKT_FLAG_KEY, \
      pkt->pos, \
    pkt->duration); \
  }

#else
# define ZM_DUMP_PACKET(pkt, text)
# define ZM_DUMP_STREAM_PACKET(stream, pkt, text)
#endif

#if LIBAVCODEC_VERSION_CHECK(56, 8, 0, 60, 100)
    #define zm_av_packet_unref(packet) av_packet_unref(packet)
    #define zm_av_packet_ref(dst, src) av_packet_ref(dst, src)
#else
    unsigned int zm_av_packet_ref( AVPacket *dst, AVPacket *src );
    #define zm_av_packet_unref( packet ) av_free_packet( packet )
    const char *avcodec_get_name(AVCodecID id);

    void av_packet_rescale_ts(AVPacket *pkt, AVRational src_tb, AVRational dst_tb);
#endif
#if LIBAVCODEC_VERSION_CHECK(57, 24, 1, 45, 101)
#define zm_avcodec_decode_video(context, rawFrame, frameComplete, packet) \
 avcodec_send_packet(context, packet); \
 avcodec_receive_frame(context, rawFrame);
#else
    #define av_packet_alloc new AVPacket
#if LIBAVCODEC_VERSION_CHECK(52, 23, 0, 23, 0)
  #define zm_avcodec_decode_video(context, rawFrame, frameComplete, packet) \
      avcodec_decode_video2(context, rawFrame, frameComplete, packet)
#else
   #define zm_avcodec_decode_video(context, rawFrame, frameComplete, packet) \
      avcodec_decode_video(context, rawFrame, frameComplete, packet->data, packet->size)
#endif
#endif

#if LIBAVCODEC_VERSION_CHECK(55, 28, 1, 45, 101)
  #define zm_av_frame_alloc() av_frame_alloc()
#else
  #define zm_av_frame_alloc() avcodec_alloc_frame()
#endif

#if ! LIBAVCODEC_VERSION_CHECK(55, 28, 1, 45, 101)
  #define av_frame_free( input_avframe ) av_freep( input_avframe )
#endif

int check_sample_fmt(const AVCodec *codec, enum AVSampleFormat sample_fmt);
enum AVPixelFormat fix_deprecated_pix_fmt(enum AVPixelFormat );

bool is_video_stream(const AVStream *);
bool is_audio_stream(const AVStream *);
bool is_video_context(const AVCodec *);
bool is_audio_context(const AVCodec *);

int zm_receive_packet(AVCodecContext *context, AVPacket &packet);

int zm_send_packet_receive_frame(AVCodecContext *context, AVFrame *frame, AVPacket &packet);
int zm_send_frame_receive_packet(AVCodecContext *context, AVFrame *frame, AVPacket &packet);

#if LIBAVCODEC_VERSION_CHECK(57, 64, 0, 64, 0)
int zm_send_frame_internal(AVCodecContext *ctx, AVFrame *frame);
int zm_receive_packet_internal(AVCodecContext *ctx, AVFrame *frame, AVPacket &packet);
#endif

void zm_packet_copy_rescale_ts(const AVPacket *ipkt, AVPacket *opkt, const AVRational src_tb, const AVRational dst_tb);

#if defined(HAVE_LIBSWRESAMPLE) || defined(HAVE_LIBAVRESAMPLE)
int zm_resample_audio(
#if defined(HAVE_LIBSWRESAMPLE)
    SwrContext *resample_ctx,
#else
#if defined(HAVE_LIBAVRESAMPLE)
    AVAudioResampleContext *resample_ctx,
#endif
#endif
    AVFrame *in_frame,
    AVFrame *out_frame
    );
int zm_resample_get_delay(
#if defined(HAVE_LIBSWRESAMPLE)
    SwrContext *resample_ctx,
#else
#if defined(HAVE_LIBAVRESAMPLE)
    AVAudioResampleContext *resample_ctx,
#endif
#endif
    int time_base
    );

#endif

int zm_add_samples_to_fifo(AVAudioFifo *fifo, AVFrame *frame);
int zm_get_samples_from_fifo(AVAudioFifo *fifo, AVFrame *frame);

struct zm_free_av_packet
{
    void operator()(AVPacket *pkt) const
    {
#if LIBAVCODEC_VERSION_CHECK(57, 107, 0, 107, 100)
        av_packet_free(&pkt);
#else
        av_free_packet(pkt);
#endif
    }
};

using av_packet_ptr = std::unique_ptr<AVPacket, zm_free_av_packet>;

struct av_packet_guard
{
    av_packet_guard() : packet{nullptr}
    {
    }
    explicit av_packet_guard(const av_packet_ptr& p) : packet{p.get()}
    {
    }
    explicit av_packet_guard(AVPacket *p) : packet{p}
    {
    }
    ~av_packet_guard()
    {
	if (packet)
	    av_packet_unref(packet);
    }

    void acquire(const av_packet_ptr& p)
    {
	packet = p.get();
    }
    void acquire(AVPacket *p)
    {
	packet = p;
    }
    void release()
    {
	packet = nullptr;
    }

private:
    AVPacket *packet;
};

#endif // ZM_FFMPEG_H
