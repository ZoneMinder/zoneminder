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
#include <cinttypes>

#include "zm_ffmpeg.h"
#include "zm_image.h"
#include "zm_rgb.h"

#if HAVE_LIBAVCODEC || HAVE_LIBAVUTIL || HAVE_LIBSWSCALE

void log_libav_callback( void *ptr, int level, const char *fmt, va_list vargs ) {
  Logger *log = Logger::fetch();
  int log_level = 0;
  if ( level == AV_LOG_QUIET ) { // -8
    log_level = Logger::NOLOG;
  } else if ( level == AV_LOG_PANIC ) { //0
    log_level = Logger::PANIC;
  } else if ( level == AV_LOG_FATAL ) { // 8
    log_level = Logger::FATAL;
  } else if ( level == AV_LOG_ERROR ) { // 16
    log_level = Logger::WARNING; // ffmpeg outputs a lot of errors that don't really affect anything.
    //log_level = Logger::ERROR;
  } else if ( level == AV_LOG_WARNING ) { //24
    log_level = Logger::INFO;
    //log_level = Logger::WARNING;
  } else if ( level == AV_LOG_INFO ) { //32
    log_level = Logger::DEBUG1;
    //log_level = Logger::INFO;
  } else if ( level == AV_LOG_VERBOSE ) { //40
    log_level = Logger::DEBUG2;
  } else if ( level == AV_LOG_DEBUG ) { //48
    log_level = Logger::DEBUG3;
#ifdef AV_LOG_TRACE
  } else if ( level == AV_LOG_TRACE ) {
    log_level = Logger::DEBUG8;
#endif
#ifdef AV_LOG_MAX_OFFSET
  } else if ( level == AV_LOG_MAX_OFFSET ) {
    log_level = Logger::DEBUG9;
#endif
  } else {
    Error("Unknown log level %d", level);
  }

  if ( log ) {
    char            logString[8192];
    vsnprintf(logString, sizeof(logString)-1, fmt, vargs);
    log->logPrint(false, __FILE__, __LINE__, log_level, logString);
  }
}

void FFMPEGInit() {
  static bool bInit = false;

  if ( !bInit ) {
    if ( logDebugging() )
      av_log_set_level( AV_LOG_DEBUG ); 
    else
      av_log_set_level( AV_LOG_QUIET ); 
    if ( config.log_ffmpeg ) 
        av_log_set_callback(log_libav_callback); 
    else
        Info("Not enabling ffmpeg logs, as LOG_FFMPEG is disabled in options");
    av_register_all();
    avformat_network_init();
    bInit = true;
  }
}

#if HAVE_LIBAVUTIL
enum _AVPIXELFORMAT GetFFMPEGPixelFormat(unsigned int p_colours, unsigned p_subpixelorder) {
  enum _AVPIXELFORMAT pf;

  Debug(8,"Colours: %d SubpixelOrder: %d",p_colours,p_subpixelorder);

  switch(p_colours) {
    case ZM_COLOUR_RGB24:
      {
        if(p_subpixelorder == ZM_SUBPIX_ORDER_BGR) {
          /* BGR subpixel order */
          pf = AV_PIX_FMT_BGR24;
        } else {
          /* Assume RGB subpixel order */
          pf = AV_PIX_FMT_RGB24;
        }
        break;
      }
    case ZM_COLOUR_RGB32:
      {
        if(p_subpixelorder == ZM_SUBPIX_ORDER_ARGB) {
          /* ARGB subpixel order */
          pf = AV_PIX_FMT_ARGB;
        } else if(p_subpixelorder == ZM_SUBPIX_ORDER_ABGR) {
          /* ABGR subpixel order */
          pf = AV_PIX_FMT_ABGR;
        } else if(p_subpixelorder == ZM_SUBPIX_ORDER_BGRA) {
          /* BGRA subpixel order */
          pf = AV_PIX_FMT_BGRA;
        } else {
          /* Assume RGBA subpixel order */
          pf = AV_PIX_FMT_RGBA;
        }
        break;
      }
    case ZM_COLOUR_GRAY8:
      pf = AV_PIX_FMT_GRAY8;
      break;
    default:
      Panic("Unexpected colours: %d",p_colours);
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
    char *val = NULL;
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

int hacked_up_context2_for_older_ffmpeg(AVFormatContext **avctx, AVOutputFormat *oformat, const char *format, const char *filename) {
  AVFormatContext *s = avformat_alloc_context();
  int ret = 0;

  *avctx = NULL;
  if (!s) {
    av_log(s, AV_LOG_ERROR, "Out of memory\n");
    ret = AVERROR(ENOMEM);
    return ret;
  }

  if (!oformat) {
    if (format) {
      oformat = av_guess_format(format, NULL, NULL);
      if (!oformat) {
        av_log(s, AV_LOG_ERROR, "Requested output format '%s' is not a suitable output format\n", format);
        ret = AVERROR(EINVAL);
      }
    } else {
      oformat = av_guess_format(NULL, filename, NULL);
      if (!oformat) {
        ret = AVERROR(EINVAL);
        av_log(s, AV_LOG_ERROR, "Unable to find a suitable output format for '%s'\n", filename);
      }
    }
  }

  if (ret) {
    avformat_free_context(s);
    return ret;
  }

  s->oformat = oformat;
#if 0
  if (s->oformat->priv_data_size > 0) {
      if (s->oformat->priv_class) {
        // This looks wrong, we just allocated priv_data and now we are losing the pointer to it.FIXME
        *(const AVClass**)s->priv_data = s->oformat->priv_class;
        av_opt_set_defaults(s->priv_data);
      } else {
    s->priv_data = av_mallocz(s->oformat->priv_data_size);
    if ( ! s->priv_data) {
      av_log(s, AV_LOG_ERROR, "Out of memory\n");
      ret = AVERROR(ENOMEM);
      return ret;
    }
    s->priv_data = NULL;
  }
#endif

  if (filename) strncpy(s->filename, filename, sizeof(s->filename)-1);
  *avctx = s;
  return 0;
}

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
void zm_dump_codecpar ( const AVCodecParameters *par ) {
  Debug(1, "Dumping codecpar codec_type(%d) codec_id(%d) codec_tag(%d) width(%d) height(%d) bit_rate(%d) format(%d = %s)", 
    par->codec_type,
    par->codec_id,
    par->codec_tag,
    par->width,
    par->height,
    par->bit_rate,
    par->format,
    ((AVPixelFormat)par->format == AV_PIX_FMT_NONE ? "none" : av_get_pix_fmt_name((AVPixelFormat)par->format))
); 
}
#endif

void zm_dump_codec(const AVCodecContext *codec) {
  Debug(1, "Dumping codec_context codec_type(%d) codec_id(%d) width(%d) height(%d)  timebase(%d/%d) format(%s)",
    codec->codec_type,
    codec->codec_id,
    codec->width,
    codec->height,
    codec->time_base.num,
    codec->time_base.den,
#if LIBAVCODEC_VERSION_CHECK(57, 64, 0, 64, 0)
    (codec->pix_fmt == AV_PIX_FMT_NONE ? "none" : av_get_pix_fmt_name(codec->pix_fmt))
#else
    "unsupported on avconv"
#endif
); 
}

/* "user interface" functions */
void zm_dump_stream_format(AVFormatContext *ic, int i, int index, int is_output) {
  char buf[256];
  Debug(1, "Dumping stream index i(%d) index(%d)", i, index );
  int flags = (is_output ? ic->oformat->flags : ic->iformat->flags);
  AVStream *st = ic->streams[i];
  AVDictionaryEntry *lang = av_dict_get(st->metadata, "language", NULL, 0);

  Debug(1, "    Stream #%d:%d", index, i);

  /* the pid is an important information, so we display it */
  /* XXX: add a generic system */
  if (flags & AVFMT_SHOW_IDS)
    Debug(1, "[0x%x]", st->id);
  if (lang)
    Debug(1, "(%s)", lang->value);
  Debug(1, ", frames:%d, timebase: %d/%d", st->codec_info_nb_frames, st->time_base.num, st->time_base.den);
  avcodec_string(buf, sizeof(buf), st->codec, is_output);
  Debug(1, ": %s", buf);
#if LIBAVCODEC_VERSION_CHECK(57, 64, 0, 64, 0)
  AVCodecParameters *codec = st->codecpar;
#else
  AVCodecContext *codec = st->codec;
#endif

  if (st->sample_aspect_ratio.num && // default
      av_cmp_q(st->sample_aspect_ratio, codec->sample_aspect_ratio)) {
    AVRational display_aspect_ratio;
    av_reduce(&display_aspect_ratio.num, &display_aspect_ratio.den,
        codec->width  * (int64_t)st->sample_aspect_ratio.num,
        codec->height * (int64_t)st->sample_aspect_ratio.den,
        1024 * 1024);
    Debug(1, ", SAR %d:%d DAR %d:%d",
        st->sample_aspect_ratio.num, st->sample_aspect_ratio.den,
        display_aspect_ratio.num, display_aspect_ratio.den);
  }

  if (st->codec->codec_type == AVMEDIA_TYPE_VIDEO) {
    int fps = st->avg_frame_rate.den && st->avg_frame_rate.num;
    int tbn = st->time_base.den && st->time_base.num;
    int tbc = st->codec->time_base.den && st->codec->time_base.num;

    if (fps || tbn || tbc)
      Debug(3, "\n" );

    if (fps)
      zm_log_fps(av_q2d(st->avg_frame_rate), tbn || tbc ? "fps, " : "fps");
    if (tbn)
      zm_log_fps(1 / av_q2d(st->time_base), tbc ? "stream tb numerator , " : "stream tb numerator");
    if (tbc)
      zm_log_fps(1 / av_q2d(st->codec->time_base), "codec time base:");
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
  Debug(1, "\n");

  //dump_metadata(NULL, st->metadata, "    ");

  //dump_sidedata(NULL, st, "    ");
}

int check_sample_fmt(AVCodec *codec, enum AVSampleFormat sample_fmt) {
  const enum AVSampleFormat *p = codec->sample_fmts;

  while (*p != AV_SAMPLE_FMT_NONE) {
    if (*p == sample_fmt)
      return 1;
    else Debug(2, "Not %s", av_get_sample_fmt_name( *p ) );
    p++;
  }
  return 0;
}

#if LIBAVCODEC_VERSION_CHECK(56, 8, 0, 60, 100)
#else
unsigned int zm_av_packet_ref( AVPacket *dst, AVPacket *src ) {
  av_new_packet(dst,src->size);
  memcpy(dst->data, src->data, src->size);
  dst->flags = src->flags;
  return 0;
}
#endif

bool is_video_stream( AVStream * stream ) {
  #if LIBAVCODEC_VERSION_CHECK(57, 64, 0, 64, 0)
      if ( stream->codecpar->codec_type == AVMEDIA_TYPE_VIDEO ) {
  #else
  #if (LIBAVCODEC_VERSION_CHECK(52, 64, 0, 64, 0) || LIBAVUTIL_VERSION_CHECK(50, 14, 0, 14, 0))
      if ( stream->codec->codec_type == AVMEDIA_TYPE_VIDEO ) {
  #else
      if ( stream->codec->codec_type == CODEC_TYPE_VIDEO ) {
  #endif
  #endif
    return true;
  }
  return false;
}


bool is_audio_stream( AVStream * stream ) {
  #if LIBAVCODEC_VERSION_CHECK(57, 64, 0, 64, 0)
      if ( stream->codecpar->codec_type == AVMEDIA_TYPE_AUDIO ) {
  #else
  #if (LIBAVCODEC_VERSION_CHECK(52, 64, 0, 64, 0) || LIBAVUTIL_VERSION_CHECK(50, 14, 0, 14, 0))
      if ( stream->codec->codec_type == AVMEDIA_TYPE_AUDIO ) {
  #else
      if ( stream->codec->codec_type == CODEC_TYPE_AUDIO ) {
  #endif
  #endif
    return true;
  }
  return false;
}

int zm_receive_frame( AVCodecContext *context, AVFrame *frame, AVPacket &packet ) {
  int ret;
#if LIBAVCODEC_VERSION_CHECK(57, 64, 0, 64, 0)
  if ( (ret = avcodec_send_packet(context, &packet)) < 0  ) {
    Error( "Unable to send packet %s, continuing",
       av_make_error_string(ret).c_str() );
    return 0;
  }

#if HAVE_AVUTIL_HWCONTEXT_H
  if ( hwaccel ) {
    if ( (ret = avcodec_receive_frame(context, hwFrame)) < 0 ) {
      Error( "Unable to receive frame %d: %s, continuing", streams[packet.stream_index].frame_count,
         av_make_error_string(ret).c_str() );
      return 0;
    }
    if ( (ret = av_hwframe_transfer_data(frame, hwFrame, 0)) < 0 ) {
      Error( "Unable to transfer frame at frame %d: %s, continuing", streams[packet.stream_index].frame_count,
          av_make_error_string(ret).c_str() );
      return 0;
    }
  } else {
#endif
    if ( (ret = avcodec_receive_frame(context, frame)) < 0 ) {
      Error( "Unable to send packet %s, continuing", av_make_error_string(ret).c_str() );
      return 0;
    }
#if HAVE_AVUTIL_HWCONTEXT_H
  }
#endif

# else
  int frameComplete = 0;
  while ( !frameComplete ) {
    if ( (ret = zm_avcodec_decode_video( context, frame, &frameComplete, &packet )) < 0 ) {
      Error( "Unable to decode frame at frame: %s, continuing",
          av_make_error_string(ret).c_str() );
      return 0;
    }
  }
#endif
  return 1;
} // end int zm_receive_frame( AVCodecContext *context, AVFrame *frame, AVPacket &packet )
void dumpPacket(AVPacket *pkt, const char *text) {
  char b[10240];

  snprintf(b, sizeof(b),
           " pts: %" PRId64 ", dts: %" PRId64
           ", data: %p, size: %d, stream_index: %d, flags: %04x, keyframe(%d) pos: %" PRId64
           ", duration: %" 
#if LIBAVCODEC_VERSION_CHECK(57, 64, 0, 64, 0)
           PRId64
#else
           "d"
#endif
           "\n",
           pkt->pts, 
           pkt->dts,
           pkt->data,
           pkt->size,
           pkt->stream_index,
           pkt->flags,
           pkt->flags & AV_PKT_FLAG_KEY,
           pkt->pos,
           pkt->duration);
  Debug(2, "%s:%d:%s: %s", __FILE__, __LINE__, text, b);
}
