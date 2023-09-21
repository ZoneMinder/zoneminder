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
#include <libswresample/swresample.h>

// AVUTIL
#include <libavutil/avassert.h>
#include <libavutil/avutil.h>
#include <libavutil/base64.h>
#include <libavutil/mathematics.h>
#include <libavutil/avstring.h>
#include <libavutil/audio_fifo.h>
#include <libavutil/imgutils.h>
#include <libavutil/opt.h>
#if HAVE_LIBAVUTIL_HWCONTEXT_H
  #include <libavutil/hwcontext.h>
#endif

/* LIBAVUTIL_VERSION_CHECK checks for the right version of libav and FFmpeg
 * The original source is vlc (in modules/codec/avcodec/avcommon_compat.h)
 * a is the major version
 * b and c the minor and micro versions of libav
 * d and e the minor and micro versions of FFmpeg */
#define LIBAVUTIL_VERSION_CHECK(a, b, c, d, e) \
    ( (LIBAVUTIL_VERSION_MICRO <  100 && LIBAVUTIL_VERSION_INT >= AV_VERSION_INT(a, b, c) ) || \
      (LIBAVUTIL_VERSION_MICRO >= 100 && LIBAVUTIL_VERSION_INT >= AV_VERSION_INT(a, d, e) ) )

#define _AVPIXELFORMAT AVPixelFormat

// AVCODEC
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

#define _AVCODECID AVCodecID

// AVFORMAT
#include <libavformat/avformat.h>

/* LIBAVFORMAT_VERSION_CHECK checks for the right version of libav and FFmpeg
 * The original source is vlc (in modules/codec/avcodec/avcommon_compat.h)
 * a is the major version
 * b and c the minor and micro versions of libav
 * d and e the minor and micro versions of FFmpeg */
#define LIBAVFORMAT_VERSION_CHECK(a, b, c, d, e) \
    ( (LIBAVFORMAT_VERSION_MICRO <  100 && LIBAVFORMAT_VERSION_INT >= AV_VERSION_INT(a, b, c) ) || \
      (LIBAVFORMAT_VERSION_MICRO >= 100 && LIBAVFORMAT_VERSION_INT >= AV_VERSION_INT(a, d, e) ) )

// SWSCALE
#include <libswscale/swscale.h>

/* LIBSWSCALE_VERSION_CHECK checks for the right version of libav and FFmpeg
 * a is the major version
 * b and c the minor and micro versions of libav
 * d and e the minor and micro versions of FFmpeg */
#define LIBSWSCALE_VERSION_CHECK(a, b, c, d, e) \
    ( (LIBSWSCALE_VERSION_MICRO <  100 && LIBSWSCALE_VERSION_INT >= AV_VERSION_INT(a, b, c) ) || \
      (LIBSWSCALE_VERSION_MICRO >= 100 && LIBSWSCALE_VERSION_INT >= AV_VERSION_INT(a, d, e) ) )

}

/* A single function to initialize ffmpeg, to avoid multiple initializations */
void FFMPEGInit();
void FFMPEGDeInit();

enum _AVPIXELFORMAT GetFFMPEGPixelFormat(unsigned int p_colours, unsigned p_subpixelorder);

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
    inline static const std::string av_make_error_string(int errnum) {
        static char errbuf[AV_ERROR_MAX_STRING_SIZE];
        av_strerror(errnum, errbuf, AV_ERROR_MAX_STRING_SIZE);
        return (std::string)errbuf;
    }

    #undef av_err2str
    #define av_err2str(errnum) av_make_error_string(errnum).c_str()

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
void zm_dump_codecpar(const AVCodecParameters *par);

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

# define AV_PACKET_DURATION_FMT PRId64

#define CODEC_TYPE(stream) stream->codecpar->codec_type
#define CODEC(stream) stream->codecpar

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

#define zm_av_packet_unref(packet) av_packet_unref(packet)
#define zm_av_packet_ref(dst, src) av_packet_ref(dst, src)

#define zm_av_frame_alloc() av_frame_alloc()

int check_sample_fmt(const AVCodec *codec, enum AVSampleFormat sample_fmt);
enum AVPixelFormat fix_deprecated_pix_fmt(enum AVPixelFormat );

bool is_video_stream(const AVStream *);
bool is_audio_stream(const AVStream *);
bool is_video_context(const AVCodec *);
bool is_audio_context(const AVCodec *);

int zm_receive_packet(AVCodecContext *context, AVPacket &packet);

int zm_send_packet_receive_frame(AVCodecContext *context, AVFrame *frame, AVPacket &packet);
int zm_send_frame_receive_packet(AVCodecContext *context, AVFrame *frame, AVPacket &packet);

void zm_packet_copy_rescale_ts(const AVPacket *ipkt, AVPacket *opkt, const AVRational src_tb, const AVRational dst_tb);

int zm_resample_audio(SwrContext *resample_ctx, AVFrame *in_frame, AVFrame *out_frame);
int zm_resample_get_delay(SwrContext *resample_ctx, int time_base);

int zm_add_samples_to_fifo(AVAudioFifo *fifo, AVFrame *frame);
int zm_get_samples_from_fifo(AVAudioFifo *fifo, AVFrame *frame);

struct zm_free_av_packet
{
    void operator()(AVPacket *pkt) const
    {
        av_packet_free(&pkt);
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

struct zm_free_av_frame
{
    void operator()(AVFrame *frame) const
    {
        av_frame_free(&frame);
    }
};

using av_frame_ptr = std::unique_ptr<AVFrame, zm_free_av_frame>;

#endif // ZM_FFMPEG_H
