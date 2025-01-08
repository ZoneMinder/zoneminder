/*
 * ZoneMinder FFMPEG implementation, $Date$, $Revision$
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

#include "zm_ffmpeg.h"

#include "zm_logger.h"
#include "zm_rgb.h"

extern "C" {
#include "libavutil/pixdesc.h"
}

#if HAVE_LIBAVCODEC || HAVE_LIBAVUTIL || HAVE_LIBSWSCALE

void log_libav_callback(void *ptr, int level, const char *fmt, va_list vargs) {
  Logger *log = Logger::fetch();
  int log_level = 0;
  if (level == AV_LOG_QUIET) { // -8
    log_level = Logger::NOLOG;
  } else if (level == AV_LOG_PANIC) { //0
    log_level = Logger::PANIC;
  } else if (level == AV_LOG_FATAL) { // 8
    log_level = Logger::FATAL;
  } else if (level == AV_LOG_ERROR) { // 16
    log_level = Logger::WARNING; // ffmpeg outputs a lot of errors that don't really affect anything.
  } else if (level == AV_LOG_WARNING) { //24
    log_level = Logger::INFO;
  } else if (level == AV_LOG_INFO) { //32
    log_level = Logger::DEBUG1;
  } else if (level == AV_LOG_VERBOSE) { //40
    log_level = Logger::DEBUG2;
  } else if (level == AV_LOG_DEBUG) { //48
    log_level = Logger::DEBUG3;
#ifdef AV_LOG_TRACE
  } else if (level == AV_LOG_TRACE) {
    log_level = Logger::DEBUG8;
#endif
#ifdef AV_LOG_MAX_OFFSET
  } else if (level == AV_LOG_MAX_OFFSET) {
    log_level = Logger::DEBUG9;
#endif
  } else {
    Error("Unknown log level %d", level);
  }

  if (log and (log->level() >= log_level) ) {
    char logString[8192];
    int length = vsnprintf(logString, sizeof(logString)-1, fmt, vargs);
    if (length > 0) {
      if (static_cast<size_t>(length) > sizeof(logString)-1) length = sizeof(logString)-1;
      // ffmpeg logs have a carriage return, so replace it with terminator
      logString[length-1] = 0;
      log->logPrint(false, __FILE__, __LINE__, log_level, "%s", logString);
    } else {
      log->logPrint(false, __FILE__, __LINE__, AV_LOG_ERROR, "Can't encode log from av. fmt was %s", fmt);
    }
  }
}

static bool bInit = false;

void FFMPEGInit() {

  if (!bInit) {
    if (logDebugging() && config.log_ffmpeg) {
      av_log_set_level(AV_LOG_DEBUG);
      av_log_set_callback(log_libav_callback); 
      Info("Enabling ffmpeg logs, as LOG_DEBUG+LOG_FFMPEG are enabled in options");
    } else {
      Debug(1,"Not enabling ffmpeg logs, as LOG_FFMPEG and/or LOG_DEBUG is disabled in options, or this monitor is not part of your debug targets");
      av_log_set_level(AV_LOG_QUIET);
    }
#if !LIBAVFORMAT_VERSION_CHECK(58, 9, 58, 9, 0)
    av_register_all();
#endif
    avformat_network_init();
    bInit = true;
  }
}

void FFMPEGDeInit() {
  avformat_network_deinit();
  bInit = false;
}

#if HAVE_LIBAVUTIL
enum _AVPIXELFORMAT GetFFMPEGPixelFormat(unsigned int p_colours, unsigned p_subpixelorder) {
  enum _AVPIXELFORMAT pf;

  Debug(8,"Colours: %d SubpixelOrder: %d",p_colours,p_subpixelorder);

  switch (p_colours) {
    case ZM_COLOUR_RGB24:
        if(p_subpixelorder == ZM_SUBPIX_ORDER_BGR) {
          /* BGR subpixel order */
          pf = AV_PIX_FMT_BGR24;
        } else {
          /* Assume RGB subpixel order */
          pf = AV_PIX_FMT_RGB24;
        }
        break;
    case ZM_COLOUR_RGB32:
        if (p_subpixelorder == ZM_SUBPIX_ORDER_ARGB) {
          /* ARGB subpixel order */
          pf = AV_PIX_FMT_ARGB;
        } else if (p_subpixelorder == ZM_SUBPIX_ORDER_ABGR) {
          /* ABGR subpixel order */
          pf = AV_PIX_FMT_ABGR;
        } else if (p_subpixelorder == ZM_SUBPIX_ORDER_BGRA) {
          /* BGRA subpixel order */
          pf = AV_PIX_FMT_BGRA;
        } else {
          /* Assume RGBA subpixel order */
          pf = AV_PIX_FMT_RGBA;
        }
        break;
    case ZM_COLOUR_GRAY8:
      pf = AV_PIX_FMT_GRAY8;
      break;
    default:
      Panic("Unexpected colours: %d", p_colours);
      pf = AV_PIX_FMT_GRAY8; /* Just to shush gcc variable may be unused warning */
      break;
  }

  return pf;
}
/* The following is copied directly from newer ffmpeg. */
#if LIBAVUTIL_VERSION_CHECK(52, 7, 0, 17, 100)
#else
static int parse_key_value_pair(AVDictionary **pm, const char **buf,
                                const char *key_val_sep, const char *pairs_sep,
                                int flags)
{
    char *key = av_get_token(buf, key_val_sep);
    char *val = nullptr;
    int ret;

    if (key && *key && strspn(*buf, key_val_sep)) {
      (*buf)++;
      val = av_get_token(buf, pairs_sep);
    }

    if (key && *key && val && *val)
      ret = av_dict_set(pm, key, val, flags);
    else
      ret = AVERROR(EINVAL);

    av_freep(&key);
    av_freep(&val);

    return ret;
}

int av_dict_parse_string(AVDictionary **pm, const char *str,
    const char *key_val_sep, const char *pairs_sep,
    int flags) {
  if (!str)
    return 0;

  /* ignore STRDUP flags */
  flags &= ~(AV_DICT_DONT_STRDUP_KEY | AV_DICT_DONT_STRDUP_VAL);

  while (*str) {
    int ret;
    if ( (ret = parse_key_value_pair(pm, &str, key_val_sep, pairs_sep, flags)) < 0)
      return ret;

    if (*str)
      str++;
  }

  return 0;
}
#endif
#endif // HAVE_LIBAVUTIL

#endif // HAVE_LIBAVCODEC || HAVE_LIBAVUTIL || HAVE_LIBSWSCALE

#if HAVE_LIBAVUTIL
#if LIBAVUTIL_VERSION_CHECK(56, 0, 0, 17, 100)
int64_t av_rescale_delta(AVRational in_tb, int64_t in_ts,  AVRational fs_tb, int duration, int64_t *last, AVRational out_tb){
  int64_t a, b, this_thing;

  av_assert0(in_ts != AV_NOPTS_VALUE);
  av_assert0(duration >= 0);

  if (*last == AV_NOPTS_VALUE || !duration || in_tb.num*(int64_t)out_tb.den <= out_tb.num*(int64_t)in_tb.den) {
simple_round:
    *last = av_rescale_q(in_ts, in_tb, fs_tb) + duration;
    return av_rescale_q(in_ts, in_tb, out_tb);
  }

  a =  av_rescale_q_rnd(2*in_ts-1, in_tb, fs_tb, AV_ROUND_DOWN)   >>1;
  b = (av_rescale_q_rnd(2*in_ts+1, in_tb, fs_tb, AV_ROUND_UP  )+1)>>1;
  if (*last < 2*a - b || *last > 2*b - a)
    goto simple_round;

  this_thing = av_clip64(*last, a, b);
  *last = this_thing + duration;

  return av_rescale_q(this_thing, fs_tb, out_tb);
}
#endif
#endif

static void zm_log_fps(double d, const char *postfix) {
  uint64_t v = lrintf(d * 100);
  if (!v) {
    Debug(1, "%1.4f %s", d, postfix);
  } else if (v % 100) {
    Debug(1, "%3.2f %s", d, postfix);
  } else if (v % (100 * 1000)) {
    Debug(1, "%1.0f %s", d, postfix);
  } else {
    Debug(1, "%1.0fk %s", d / 1000, postfix);
  }
}

#if LIBAVCODEC_VERSION_CHECK(57, 64, 0, 64, 0)
void zm_dump_codecpar(const AVCodecParameters *par) {
  Debug(1, "Dumping codecpar codec_type(%d %s) codec_id(%d %s) codec_tag(%" PRIu32 ") width(%d) height(%d) bit_rate(%" PRIu64 ") format(%d %s)",
      par->codec_type,
      av_get_media_type_string(par->codec_type),
      par->codec_id,
      avcodec_get_name(par->codec_id),
      par->codec_tag,
      par->width,
      par->height,
      par->bit_rate,
      par->format,
      (((AVPixelFormat)par->format == AV_PIX_FMT_NONE) ? "none" : av_get_pix_fmt_name((AVPixelFormat)par->format))
      ); 
}
#endif

void zm_dump_codec(const AVCodecContext *codec) {
  Debug(1, "Dumping codec_context codec_type(%d %s) codec_id(%d %s) width(%d) height(%d)  timebase(%d/%d) format(%s) "
      "gop_size %d max_b_frames %d me_cmp %d me_range %d qmin %d qmax %d",
    codec->codec_type,
    av_get_media_type_string(codec->codec_type),
    codec->codec_id,
    avcodec_get_name(codec->codec_id),
    codec->width,
    codec->height,
    codec->time_base.num,
    codec->time_base.den,
#if LIBAVCODEC_VERSION_CHECK(57, 64, 0, 64, 0)
    (codec->pix_fmt == AV_PIX_FMT_NONE ? "none" : av_get_pix_fmt_name(codec->pix_fmt)),
#else
    "unsupported on avconv",
#endif
    codec->gop_size,
    codec->max_b_frames,
    codec->me_cmp,
    codec->me_range,
    codec->qmin,
    codec->qmax
    );
}

/* "user interface" functions */
void zm_dump_stream_format(AVFormatContext *ic, int i, int index, int is_output) {
  Debug(1, "Dumping stream index i(%d) index(%d)", i, index);
  int flags = (is_output ? ic->oformat->flags : ic->iformat->flags);
  AVStream *st = ic->streams[i];
  AVDictionaryEntry *lang = av_dict_get(st->metadata, "language", nullptr, 0);
#if LIBAVCODEC_VERSION_CHECK(57, 64, 0, 64, 0)
  AVCodecParameters *codec = st->codecpar;
#else
  AVCodecContext *codec = st->codec;
#endif

  Debug(1, "    Stream #%d:%d", index, i);

  /* the pid is an important information, so we display it */
  /* XXX: add a generic system */
  if (flags & AVFMT_SHOW_IDS)
    Debug(1, "ids [0x%x]", st->id);
  if (lang)
    Debug(1, "language (%s)", lang->value);
  Debug(1, "frame_size:%d stream timebase: %d/%d",
      codec->frame_size,
      st->time_base.num, st->time_base.den
      );

#if LIBAVCODEC_VERSION_CHECK(57, 64, 0, 64, 0)
  Debug(1, "codec: %s %s",
      avcodec_get_name(st->codecpar->codec_id),
      av_get_media_type_string(st->codecpar->codec_type)
      );
#else
  char buf[256];
  avcodec_string(buf, sizeof(buf), st->codec, is_output);
  Debug(1, "codec: %s", buf);
#endif

  if (st->sample_aspect_ratio.num && // default
      av_cmp_q(st->sample_aspect_ratio, codec->sample_aspect_ratio)
      ) {
    AVRational display_aspect_ratio;
    av_reduce(&display_aspect_ratio.num,
        &display_aspect_ratio.den,
        codec->width  * (int64_t)st->sample_aspect_ratio.num,
        codec->height * (int64_t)st->sample_aspect_ratio.den,
        1024 * 1024);
    Debug(1, ", SAR %d:%d DAR %d:%d",
        st->sample_aspect_ratio.num, st->sample_aspect_ratio.den,
        display_aspect_ratio.num, display_aspect_ratio.den);
  }

  if (codec->codec_type == AVMEDIA_TYPE_VIDEO) {
    int fps = st->avg_frame_rate.den && st->avg_frame_rate.num;
    int tbn = st->time_base.den && st->time_base.num;

    if (fps)
      zm_log_fps(av_q2d(st->avg_frame_rate), "fps");
    if (tbn)
      zm_log_fps(1 / av_q2d(st->time_base), "stream tb numerator");
  } else if (codec->codec_type == AVMEDIA_TYPE_AUDIO) {
    Debug(1, "profile %d channels %d sample_rate %d",
        codec->profile, 
#if LIBAVUTIL_VERSION_CHECK(57, 28, 100, 28, 0)
        codec->ch_layout.nb_channels,
#else
        codec->channels,
#endif
        codec->sample_rate);
  } else {
    Debug(1, "Unknown codec type %d", codec->codec_type);
  }

  if (st->disposition & AV_DISPOSITION_DEFAULT)
    Debug(1, " (default)");
  if (st->disposition & AV_DISPOSITION_DUB)
    Debug(1, " (dub)");
  if (st->disposition & AV_DISPOSITION_ORIGINAL)
    Debug(1, " (original)");
  if (st->disposition & AV_DISPOSITION_COMMENT)
    Debug(1, " (comment)");
  if (st->disposition & AV_DISPOSITION_LYRICS)
    Debug(1, " (lyrics)");
  if (st->disposition & AV_DISPOSITION_KARAOKE)
    Debug(1, " (karaoke)");
  if (st->disposition & AV_DISPOSITION_FORCED)
    Debug(1, " (forced)");
  if (st->disposition & AV_DISPOSITION_HEARING_IMPAIRED)
    Debug(1, " (hearing impaired)");
  if (st->disposition & AV_DISPOSITION_VISUAL_IMPAIRED)
    Debug(1, " (visual impaired)");
  if (st->disposition & AV_DISPOSITION_CLEAN_EFFECTS)
    Debug(1, " (clean effects)");

  //dump_metadata(NULL, st->metadata, "    ");

  //dump_sidedata(NULL, st, "    ");
}

int check_sample_fmt(const AVCodec *codec, enum AVSampleFormat sample_fmt) {
  const enum AVSampleFormat *p = codec->sample_fmts;

  while (*p != AV_SAMPLE_FMT_NONE) {
    if (*p == sample_fmt)
      return 1;
    else Debug(2, "Not %s", av_get_sample_fmt_name( *p ) );
    p++;
  }
  return 0;
}

enum AVPixelFormat fix_deprecated_pix_fmt(enum AVPixelFormat fmt) {
  // Fix deprecated formats
  switch ( fmt ) {
    case AV_PIX_FMT_YUVJ422P  :
      return AV_PIX_FMT_YUV422P;
    case AV_PIX_FMT_YUVJ444P   :
      return AV_PIX_FMT_YUV444P;
    case AV_PIX_FMT_YUVJ440P :
      return AV_PIX_FMT_YUV440P;
    case AV_PIX_FMT_NONE :
    case AV_PIX_FMT_YUVJ420P :
      return AV_PIX_FMT_YUV420P;
    default:
      return fmt;
  }
}

#if LIBAVCODEC_VERSION_CHECK(56, 8, 0, 60, 100)
#else
unsigned int zm_av_packet_ref(AVPacket *dst, AVPacket *src) {
  av_new_packet(dst,src->size);
  memcpy(dst->data, src->data, src->size);
  dst->flags = src->flags;
  dst->pts = src->pts;
  dst->dts = src->dts;
  dst->duration = src->duration;
  dst->stream_index = src->stream_index;
  return 0;
}
const char *avcodec_get_name(enum AVCodecID id) {
  const AVCodecDescriptor *cd;
  if ( id == AV_CODEC_ID_NONE)
    return "none";
  cd = avcodec_descriptor_get(id);
  if (cd)
    return cd->name;
  AVCodec *codec;
  codec = avcodec_find_decoder(id);
  if (codec) 
    return codec->name;
  codec = avcodec_find_encoder(id);
  if (codec)
    return codec->name;
  return "unknown codec";
}

void av_packet_rescale_ts(
    AVPacket *pkt,
    AVRational src_tb,
    AVRational dst_tb
    ) {
  if ( pkt->pts != AV_NOPTS_VALUE)
    pkt->pts = av_rescale_q(pkt->pts, src_tb, dst_tb);
  if ( pkt->dts != AV_NOPTS_VALUE)
    pkt->dts = av_rescale_q(pkt->dts, src_tb, dst_tb);
  if ( pkt->duration != AV_NOPTS_VALUE)
    pkt->duration = av_rescale_q(pkt->duration, src_tb, dst_tb);
}
#endif

bool is_video_stream(const AVStream * stream) {
  #if LIBAVCODEC_VERSION_CHECK(57, 64, 0, 64, 0)
  if (stream->codecpar->codec_type == AVMEDIA_TYPE_VIDEO)
  #else
  #if (LIBAVCODEC_VERSION_CHECK(52, 64, 0, 64, 0) || LIBAVUTIL_VERSION_CHECK(50, 14, 0, 14, 0))
  if (stream->codec->codec_type == AVMEDIA_TYPE_VIDEO)
  #else
  if (stream->codec->codec_type == CODEC_TYPE_VIDEO)
  #endif
  #endif
      {
    return true;
  }
  #if LIBAVCODEC_VERSION_CHECK(57, 64, 0, 64, 0)
  Debug(2, "Not a video type %d != %d for stream %d", stream->codecpar->codec_type, AVMEDIA_TYPE_VIDEO, stream->index);
  #endif

  return false;
}

bool is_video_context(const AVCodecContext *codec_context) {
  return
  #if (LIBAVCODEC_VERSION_CHECK(52, 64, 0, 64, 0) || LIBAVUTIL_VERSION_CHECK(50, 14, 0, 14, 0))
      (codec_context->codec_type == AVMEDIA_TYPE_VIDEO);
  #else
      (codec_context->codec_type == CODEC_TYPE_VIDEO);
  #endif
}

bool is_audio_stream(const AVStream * stream) {
  #if LIBAVCODEC_VERSION_CHECK(57, 64, 0, 64, 0)
  if (stream->codecpar->codec_type == AVMEDIA_TYPE_AUDIO)
  #else
  #if (LIBAVCODEC_VERSION_CHECK(52, 64, 0, 64, 0) || LIBAVUTIL_VERSION_CHECK(50, 14, 0, 14, 0))
  if (stream->codec->codec_type == AVMEDIA_TYPE_AUDIO)
  #else
  if (stream->codec->codec_type == CODEC_TYPE_AUDIO)
  #endif
  #endif
      {
    return true;
  }
  return false;
}

bool is_audio_context(const AVCodecContext *codec_context) {
  return
  #if (LIBAVCODEC_VERSION_CHECK(52, 64, 0, 64, 0) || LIBAVUTIL_VERSION_CHECK(50, 14, 0, 14, 0))
      (codec_context->codec_type == AVMEDIA_TYPE_AUDIO);
  #else
      (codec_context->codec_type == CODEC_TYPE_AUDIO);
  #endif
}

int zm_receive_packet(AVCodecContext *context, AVPacket &packet) {
#if LIBAVCODEC_VERSION_CHECK(57, 64, 0, 64, 0)
  int ret = avcodec_receive_packet(context, &packet);
  if ((ret < 0) and (AVERROR_EOF != ret)) {
    Error("Error encoding (%d) (%s)", ret, av_err2str(ret));
  }
  return ret; // 1 or 0
#else
  int got_packet = 0;
  int ret = avcodec_encode_audio2(context, &packet, nullptr, &got_packet);
  if (ret < 0) {
    Error("Error encoding (%d) (%s)", ret, av_err2str(ret));
    return ret;
  }
  return got_packet; // 1
#endif
}  // end int zm_receive_packet(AVCodecContext *context, AVPacket &packet)

int zm_send_packet_receive_frame(
    AVCodecContext *context,
    AVFrame *frame,
    AVPacket &packet) {
  int ret;
#if LIBAVCODEC_VERSION_CHECK(57, 64, 0, 64, 0)
  if ((ret = avcodec_send_packet(context, &packet)) < 0) {
    Error("Unable to send packet %s, continuing",
       av_make_error_string(ret).c_str());
    return ret;
  }

  if ((ret = avcodec_receive_frame(context, frame)) < 0) {
    if (AVERROR(EAGAIN) == ret) {
      // The codec may need more samples than it has, perfectly valid
      Debug(2, "Codec not ready to give us a frame");
    } else {
      Error("Could not recieve frame (error %d = '%s')", ret,
          av_make_error_string(ret).c_str());
    }
    return ret;
  }
  // In this api the packet is always consumed, so return packet.bytes
  return packet.size;
# else
  int frameComplete = 0;
  if (is_video_context(context)) {
    ret = zm_avcodec_decode_video(context, frame, &frameComplete, &packet);
    Debug(2, "ret from decode_video %d, framecomplete %d", ret, frameComplete);
  } else {
    ret = avcodec_decode_audio4(context, frame, &frameComplete, &packet);
    Debug(2, "ret from decode_audio %d, framecomplete %d", ret, frameComplete);
  }
  if (ret < 0) {
    Error("Unable to decode frame: %s", av_make_error_string(ret).c_str());
    return ret;
  }
  return frameComplete ? ret : 0;
#endif
}  // end int zm_send_packet_receive_frame(AVCodecContext *context, AVFrame *frame, AVPacket &packet)

// NETINT - the following functions break send_frame and receive_packet to separate functions
#if LIBAVCODEC_VERSION_CHECK(57, 64, 0, 64, 0)
int zm_send_frame_internal(AVCodecContext *ctx, AVFrame *frame) {
  int ret;
  if (( (ret = avcodec_send_frame(ctx, frame)) < 0 ) and frame) {
    Error("Could not send frame (error '%s')",
          av_make_error_string(ret).c_str());
    return ret;
  }
  return 1;
}

/* Returns < 0 on error, 0 if codec not ready, 1 on success
 */
int zm_receive_packet_internal(AVCodecContext *ctx, AVFrame *frame, AVPacket &packet) {
  int ret;
  if ((ret = avcodec_receive_packet(ctx, &packet)) < 0) {
    if (AVERROR(EAGAIN) == ret) {
      // The codec may need more samples than it has, perfectly valid
      Debug(2, "Codec not ready to give us a packet");
      return 0;
    } else if (frame) {
      // May get EOF if frame is NULL because it signals flushing
      Error("Could not recieve packet (error %d = '%s')", ret,
            av_make_error_string(ret).c_str());
    }
    zm_av_packet_unref(&packet);
    return ret;
  }
  return 1;
}
#endif



/* Returns < 0 on error, 0 if codec not ready, 1 on success
 */
int zm_send_frame_receive_packet(AVCodecContext *ctx, AVFrame *frame, AVPacket &packet) {
  int ret;
  #if LIBAVCODEC_VERSION_CHECK(57, 64, 0, 64, 0)
    if (( (ret = avcodec_send_frame(ctx, frame)) < 0 ) and frame) {
      Error("Could not send frame (error '%s')",
            av_make_error_string(ret).c_str());
      return ret;
    }

    if ((ret = avcodec_receive_packet(ctx, &packet)) < 0) {
      if (AVERROR(EAGAIN) == ret) {
        // The codec may need more samples than it has, perfectly valid
        Debug(2, "Codec not ready to give us a packet");
        return 0;
      } else if (frame) {
        // May get EOF if frame is NULL because it signals flushing
        Error("Could not recieve packet (error %d = '%s')", ret,
              av_make_error_string(ret).c_str());
      }
      zm_av_packet_unref(&packet);
      return ret;
    }
  #else
    int data_present;
    if ((ret = avcodec_encode_audio2(
            ctx, &packet, frame, &data_present)) < 0) {
      Error("Could not encode frame (error '%s')",
            av_make_error_string(ret).c_str());
      zm_av_packet_unref(&packet);
      return ret;
    }
    if (!data_present) {
      Debug(2, "Not ready to out a frame yet.");
      zm_av_packet_unref(&packet);
      return 0;
    }
  #endif
  return 1;
}  // end int zm_send_frame_receive_packet

void zm_free_codec(AVCodecContext **ctx) {
  if (*ctx) {
    avcodec_close(*ctx);
#if LIBAVCODEC_VERSION_CHECK(57, 64, 0, 64, 0)
    // We allocate and copy in newer ffmpeg, so need to free it
    avcodec_free_context(ctx);
#endif
    *ctx = NULL;
  } // end if 
}

void zm_packet_copy_rescale_ts(const AVPacket *ipkt, AVPacket *opkt, const AVRational src_tb, const AVRational dst_tb) {
  opkt->pts = ipkt->pts;
  opkt->dts = ipkt->dts;
  opkt->duration = ipkt->duration;
  av_packet_rescale_ts(opkt, src_tb, dst_tb);
}

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
    ) {
#if defined(HAVE_LIBSWRESAMPLE)
  if (in_frame) {
    // Resample the in_frame into the audioSampleBuffer until we process the whole
    // decoded data. Note: pts does not survive resampling or converting
    Debug(2, "Converting %d to %d samples using swresample",
        in_frame->nb_samples, out_frame->nb_samples);
  } else {
    Debug(2, "Sending NULL frame to flush resampler");
  }
  int ret = swr_convert_frame(resample_ctx, out_frame, in_frame);
  if (ret < 0) {
    Error("Could not resample frame (error '%s')",
        av_make_error_string(ret).c_str());
    return 0;
  }
  Debug(3, "swr_get_delay %" PRIi64, swr_get_delay(resample_ctx, out_frame->sample_rate));
#else
#if defined(HAVE_LIBAVRESAMPLE)
  if (!in_frame) {
    Error("Flushing resampler not supported by AVRESAMPLE");
    return 0;
  }
  int ret = avresample_convert(resample_ctx, nullptr, 0, 0, in_frame->data,
                            0, in_frame->nb_samples);
  if (ret < 0) {
    Error("Could not resample frame (error '%s')",
        av_make_error_string(ret).c_str());
    return 0;
  }
  int samples_available = avresample_available(resample_ctx);
  if (samples_available < out_frame->nb_samples) {
    Debug(1, "Not enough samples yet (%d)", samples_available);
    return 0;
  }

  // Read a frame audio data from the resample fifo
  if (avresample_read(resample_ctx, out_frame->data, out_frame->nb_samples) !=
      out_frame->nb_samples) {
    Warning("Error reading resampled audio.");
    return 0;
  }
#endif
#endif
  zm_dump_frame(out_frame, "Out frame after resample");
  return 1;
}

int zm_resample_get_delay(
#if defined(HAVE_LIBSWRESAMPLE)
        SwrContext *resample_ctx,
#else
#if defined(HAVE_LIBAVRESAMPLE)
        AVAudioResampleContext *resample_ctx,
#endif
#endif
        int time_base
    ) { 
#if defined(HAVE_LIBSWRESAMPLE)
  return swr_get_delay(resample_ctx, time_base);
#else
#if defined(HAVE_LIBAVRESAMPLE)
  return avresample_available(resample_ctx);
#endif
#endif
}
#endif

int zm_add_samples_to_fifo(AVAudioFifo *fifo, AVFrame *frame) {
  int ret = av_audio_fifo_realloc(fifo, av_audio_fifo_size(fifo) + frame->nb_samples);
  if (ret < 0) {
    Error("Could not reallocate FIFO to %d samples",
        av_audio_fifo_size(fifo) + frame->nb_samples);
    return 0;
  }
  /** Store the new samples in the FIFO buffer. */
  ret = av_audio_fifo_write(fifo, (void **)frame->data, frame->nb_samples);
  if (ret < frame->nb_samples) {
    Error("Could not write data to FIFO. %d written, expecting %d. Reason %s",
        ret, frame->nb_samples, av_make_error_string(ret).c_str());
    return 0;
  }
  return 1;
}

int zm_get_samples_from_fifo(AVAudioFifo *fifo, AVFrame *frame) {
  // AAC requires 1024 samples per encode.  Our input tends to be something else, so need to buffer them.
  if (frame->nb_samples > av_audio_fifo_size(fifo)) {
    Debug(1, "Not enough samples in fifo for AAC codec frame_size %d > fifo size %d",
         frame->nb_samples, av_audio_fifo_size(fifo));
    return 0;
  }

  if (av_audio_fifo_read(fifo, (void **)frame->data, frame->nb_samples) < frame->nb_samples) {
    Error("Could not read data from FIFO");
    return 0;
  }
//out_frame->nb_samples = frame_size;
  zm_dump_frame(frame, "Out frame after fifo read");
  return 1;
}
