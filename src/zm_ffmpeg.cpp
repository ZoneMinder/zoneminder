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
#include "zm_utils.h"

extern "C" {
#include <libavutil/pixdesc.h>
}

/*
      AVCodecID codec_id;
      const char *codec_codec;
      const char *codec_name;
      enum AVPixelFormat sw_pix_fmt;
      enum AVPixelFormat hw_pix_fmt;
      AVHWDeviceType hwdevice_type;
      const char *hwdevice_default
      const char *options_default
      */

static CodecData dec_codecs[] = {
  { AV_CODEC_ID_AV1, "av1", "libsvtav1", AV_PIX_FMT_YUV420P, AV_PIX_FMT_YUV420P, AV_HWDEVICE_TYPE_NONE, nullptr, nullptr },
  { AV_CODEC_ID_AV1, "av1", "libaom-av1", AV_PIX_FMT_YUV420P, AV_PIX_FMT_YUV420P, AV_HWDEVICE_TYPE_NONE, nullptr, nullptr },
  { AV_CODEC_ID_MJPEG, "mjpeg", "mjpeg", AV_PIX_FMT_YUVJ422P, AV_PIX_FMT_YUVJ422P, AV_HWDEVICE_TYPE_NONE, nullptr, nullptr },
  { AV_CODEC_ID_H264, "h264", "h264", AV_PIX_FMT_YUV420P, AV_PIX_FMT_YUV420P, AV_HWDEVICE_TYPE_NONE, nullptr, nullptr },
  { AV_CODEC_ID_H264, "h264", "h264_v4l2m2m", AV_PIX_FMT_YUV420P, AV_PIX_FMT_YUV420P, AV_HWDEVICE_TYPE_NONE, nullptr, nullptr },
  { AV_CODEC_ID_H265, "hevc", "hevc", AV_PIX_FMT_YUV420P, AV_PIX_FMT_YUV420P, AV_HWDEVICE_TYPE_NONE, nullptr, nullptr },
  { AV_CODEC_ID_H265, "hevc", "hevc_v4l2m2m", AV_PIX_FMT_YUV420P, AV_PIX_FMT_YUV420P, AV_HWDEVICE_TYPE_NONE, nullptr, nullptr },
  { AV_CODEC_ID_H265, "hevc", "hevc_cuvid", AV_PIX_FMT_NV12, AV_PIX_FMT_NV12, AV_HWDEVICE_TYPE_NONE, nullptr, nullptr },
  { AV_CODEC_ID_H265, "hevc", "libx265", AV_PIX_FMT_YUV420P, AV_PIX_FMT_YUV420P, AV_HWDEVICE_TYPE_NONE, nullptr, nullptr },
#if HAVE_LIBAVUTIL_HWCONTEXT_H && LIBAVCODEC_VERSION_CHECK(57, 107, 0, 107, 0)
  { AV_CODEC_ID_H264, "h264", "h264_qsv", AV_PIX_FMT_YUV420P, AV_PIX_FMT_QSV, AV_HWDEVICE_TYPE_QSV, nullptr, nullptr },
  { AV_CODEC_ID_AV1, "av1", "av1_vaapi", AV_PIX_FMT_YUV420P, AV_PIX_FMT_VAAPI, AV_HWDEVICE_TYPE_VAAPI, nullptr, nullptr },
  { AV_CODEC_ID_H265, "hevc", "hevc_vaapi", AV_PIX_FMT_NV12, AV_PIX_FMT_VAAPI, AV_HWDEVICE_TYPE_VAAPI, nullptr, nullptr },
  { AV_CODEC_ID_H265, "hevc", "hevc_qsv", AV_PIX_FMT_YUV420P, AV_PIX_FMT_QSV, AV_HWDEVICE_TYPE_QSV, nullptr, nullptr },
#endif
};

static CodecData enc_codecs[] = {
#if HAVE_LIBAVUTIL_HWCONTEXT_H && LIBAVCODEC_VERSION_CHECK(57, 107, 0, 107, 0)
  { AV_CODEC_ID_H265, "hevc", "hevc_vaapi", AV_PIX_FMT_NV12, AV_PIX_FMT_VAAPI, AV_HWDEVICE_TYPE_VAAPI, nullptr, nullptr },
  { AV_CODEC_ID_H265, "hevc", "hevc_qsv", AV_PIX_FMT_YUV420P, AV_PIX_FMT_QSV, AV_HWDEVICE_TYPE_QSV, nullptr, nullptr },
  { AV_CODEC_ID_H265, "hevc", "hevc_nvenc", AV_PIX_FMT_NV12, AV_PIX_FMT_NV12, AV_HWDEVICE_TYPE_NONE, nullptr, nullptr },
  { AV_CODEC_ID_H264, "h264", "h264_vaapi", AV_PIX_FMT_NV12, AV_PIX_FMT_VAAPI, AV_HWDEVICE_TYPE_VAAPI, nullptr, nullptr },
  { AV_CODEC_ID_H264, "h264", "h264_qsv", AV_PIX_FMT_YUV420P, AV_PIX_FMT_QSV, AV_HWDEVICE_TYPE_QSV, nullptr, nullptr },
  { AV_CODEC_ID_H264, "h264", "h264_nvenc", AV_PIX_FMT_NV12, AV_PIX_FMT_NV12, AV_HWDEVICE_TYPE_NONE, nullptr, nullptr },
  { AV_CODEC_ID_H264, "h264", "h264_omx", AV_PIX_FMT_YUV420P, AV_PIX_FMT_YUV420P,  AV_HWDEVICE_TYPE_NONE, nullptr, nullptr },
  { AV_CODEC_ID_H264, "h264", "h264_v4l2m2m", AV_PIX_FMT_YUV420P, AV_PIX_FMT_YUV420P,  AV_HWDEVICE_TYPE_NONE, nullptr, nullptr },
  { AV_CODEC_ID_AV1, "av1", "av1_qsv", AV_PIX_FMT_YUV420P, AV_PIX_FMT_QSV, AV_HWDEVICE_TYPE_QSV, nullptr, nullptr },
  { AV_CODEC_ID_AV1, "av1", "av1_vaapi", AV_PIX_FMT_YUV420P, AV_PIX_FMT_VAAPI, AV_HWDEVICE_TYPE_VAAPI, nullptr, nullptr },
#endif
  { AV_CODEC_ID_H265, "hevc", "libx265", AV_PIX_FMT_YUV420P, AV_PIX_FMT_YUV420P, AV_HWDEVICE_TYPE_NONE, nullptr, nullptr },
  { AV_CODEC_ID_H264, "h264", "h264", AV_PIX_FMT_YUV420P, AV_PIX_FMT_YUV420P, AV_HWDEVICE_TYPE_NONE, nullptr, nullptr },
  { AV_CODEC_ID_H264, "h264", "libx264", AV_PIX_FMT_YUV420P, AV_PIX_FMT_YUV420P, AV_HWDEVICE_TYPE_NONE, nullptr, nullptr },
  { AV_CODEC_ID_MJPEG, "mjpeg", "mjpeg", AV_PIX_FMT_YUVJ420P, AV_PIX_FMT_YUVJ420P, AV_HWDEVICE_TYPE_NONE, nullptr, nullptr },
  { AV_CODEC_ID_VP9, "vp9", "libvpx-vp9", AV_PIX_FMT_YUV420P, AV_PIX_FMT_YUV420P, AV_HWDEVICE_TYPE_NONE, nullptr, nullptr },
  { AV_CODEC_ID_AV1, "av1", "libsvtav1", AV_PIX_FMT_YUV420P, AV_PIX_FMT_YUV420P, AV_HWDEVICE_TYPE_NONE, nullptr, nullptr },
  { AV_CODEC_ID_AV1, "av1", "libaom-av1", AV_PIX_FMT_YUV420P, AV_PIX_FMT_YUV420P, AV_HWDEVICE_TYPE_NONE, nullptr, nullptr },
};

std::list<const CodecData*> get_encoder_data(const std::string &wanted_codec, const std::string &wanted_encoder) {
  std::list<const CodecData*> results;

  for (unsigned int i = 0; i < sizeof(enc_codecs) / sizeof(*enc_codecs); i++) {
    const CodecData *chosen_codec_data = &enc_codecs[i];
    if (wanted_encoder != "" and wanted_encoder != "auto") {
      if (wanted_encoder != enc_codecs[i].codec_name) {
        Debug(1, "Not the right codec name %s != %s", enc_codecs[i].codec_name, wanted_encoder.c_str());
        continue;
      }
    }

    if ((!wanted_codec.empty() and wanted_codec != "auto") and (enc_codecs[i].codec_codec != wanted_codec)) {
      Debug(4, "Not the right codec id %s != %s for %s",
          chosen_codec_data->codec_codec,
          wanted_codec.c_str(),
          chosen_codec_data->codec_name
          );
      continue;
    }
    const AVCodec *codec = avcodec_find_encoder_by_name(chosen_codec_data->codec_name);
    if (!codec) {
      Debug(1, "Didn't find codec for %s", chosen_codec_data->codec_name);
      continue;
    }
    results.push_back(chosen_codec_data);
  }
  return results;
}

std::list<const CodecData*> get_decoder_data(int wanted_codec, const std::string &wanted_decoder) {
  std::list<const CodecData*> results;

  for (unsigned int i = 0; i < sizeof(dec_codecs) / sizeof(*dec_codecs); i++) {
    const CodecData *chosen_codec_data = &dec_codecs[i];
    if (!wanted_decoder.empty() and wanted_decoder != "auto") {
      if (wanted_decoder != chosen_codec_data->codec_name) {
        Debug(1, "Not the right codec name %s != %s", chosen_codec_data->codec_name, wanted_decoder.c_str());
        continue;
      }
    }
    if (wanted_codec and (chosen_codec_data->codec_id != wanted_codec)) {
      Debug(1, "Not the right codec id %d %s != wanted %d %s for %s",
          chosen_codec_data->codec_id,
          avcodec_get_name(chosen_codec_data->codec_id),
          wanted_codec,
          avcodec_get_name((AVCodecID)wanted_codec),
          chosen_codec_data->codec_name
          );
      continue;
    }
    const AVCodec *codec = avcodec_find_decoder_by_name(chosen_codec_data->codec_name);
    if (!codec) {
      Debug(1, "Didn't find codec for %s", chosen_codec_data->codec_name);
      continue;
    }
    results.push_back(chosen_codec_data);
  }
  return results;
}

#if HAVE_LIBAVUTIL_HWCONTEXT_H
#if LIBAVCODEC_VERSION_CHECK(57, 89, 0, 89, 0)
static enum AVPixelFormat hw_pix_fmt = AV_PIX_FMT_NONE;
static enum AVPixelFormat get_hw_format(
  AVCodecContext *ctx,
  const enum AVPixelFormat *pix_fmts
) {
  const enum AVPixelFormat *p;

  for ( p = pix_fmts; *p != -1; p++ ) {
    if ( *p == hw_pix_fmt )
      return *p;
  }

  Error("Failed to get HW surface format for %s.",
        av_get_pix_fmt_name(hw_pix_fmt));
  for ( p = pix_fmts; *p != -1; p++ )
    Error("Available HW surface format was %s.",
          av_get_pix_fmt_name(*p));

  return AV_PIX_FMT_NONE;
}
#if !LIBAVUTIL_VERSION_CHECK(56, 22, 0, 14, 0)
static enum AVPixelFormat find_fmt_by_hw_type(const enum AVHWDeviceType type) {
  switch (type) {
  case AV_HWDEVICE_TYPE_VAAPI:
        return AV_PIX_FMT_VAAPI;
  case AV_HWDEVICE_TYPE_DXVA2:
    return AV_PIX_FMT_DXVA2_VLD;
  case AV_HWDEVICE_TYPE_D3D11VA:
    return AV_PIX_FMT_D3D11;
  case AV_HWDEVICE_TYPE_VDPAU:
    return AV_PIX_FMT_VDPAU;
  case AV_HWDEVICE_TYPE_CUDA:
    return AV_PIX_FMT_CUDA;
  case AV_HWDEVICE_TYPE_QSV:
    return AV_PIX_FMT_VAAPI;
#ifdef QUADRA
  case AV_HWDEVICE_TYPE_NI_QUADRA:
    return AV_PIX_FMT_NI_QUAD;
#endif
#ifdef AV_HWDEVICE_TYPE_MMAL
  case AV_HWDEVICE_TYPE_MMAL:
    return AV_PIX_FMT_MMAL;
#endif
  case AV_HWDEVICE_TYPE_VIDEOTOOLBOX:
    return AV_PIX_FMT_VIDEOTOOLBOX;
  default:
    return AV_PIX_FMT_NONE;
  }
}
#endif
#endif
#endif

int setup_hwaccel(
    AVCodecContext *codec_ctx,
    const CodecData *codec_data,
    AVBufferRef * &hw_device_ctx,
    const std::string &device,
    int width,
    int height) {
#if HAVE_LIBAVUTIL_HWCONTEXT_H && LIBAVCODEC_VERSION_CHECK(57, 107, 0, 107, 0)
  if (codec_data->hwdevice_type == AV_HWDEVICE_TYPE_NONE) {
    return 0;
  }
  int ret = av_hwdevice_ctx_create(&hw_device_ctx,
      codec_data->hwdevice_type,
      device.empty() ? codec_data->hwdevice_default : device.c_str(),
      nullptr, 0);
  if (0>ret) {
    Error("Failed to create hwdevice_ctx %s", av_make_error_string(ret).c_str());
    return ret;
  }
  codec_ctx->get_format = get_hw_format;
  codec_ctx->hw_device_ctx = av_buffer_ref(hw_device_ctx);

  AVBufferRef *hw_frames_ref;
  AVHWFramesContext *frames_ctx = nullptr;

  if (!(hw_frames_ref = av_hwframe_ctx_alloc(hw_device_ctx))) {
    Error("Failed to create hwaccel frame context.");
    return -1;
  }
  frames_ctx = (AVHWFramesContext *)(hw_frames_ref->data);
  frames_ctx->format    = codec_data->hw_pix_fmt;
  frames_ctx->sw_format = codec_data->sw_pix_fmt;
  frames_ctx->width     = width;
  frames_ctx->height    = height;
  frames_ctx->initial_pool_size = 20;
  if ((ret = av_hwframe_ctx_init(hw_frames_ref)) < 0) {
    Error("Failed to initialize hwaccel frame context."
        "Error code: %s", av_err2str(ret));
    av_buffer_unref(&hw_frames_ref);
  } else {
    codec_ctx->hw_frames_ctx = av_buffer_ref(hw_frames_ref);
    if (!codec_ctx->hw_frames_ctx) {
      Error("Failed to allocate hw_frames_ctx");
      return -1;
    }
  }
  av_buffer_unref(&hw_frames_ref);
  av_buffer_unref(&hw_device_ctx);
#endif
  return 0;
} // end setup_hwaccel



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

#if LIBAVUTIL_VERSION_CHECK(56, 0, 0, 17, 100)
int64_t av_rescale_delta(AVRational in_tb, int64_t in_ts,  AVRational fs_tb, int duration, int64_t *last, AVRational out_tb) {
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

void zm_dump_codecpar(const AVCodecParameters *par) {
  Debug(1, "Dumping codecpar codec_type %d %s codec_id %d %s codec_tag %c%c%c%c"
        " width %d height %d bit_rate%" PRIu64 " bpcs %d bprs %d format%d %s"
        " extradata:%d:%s profile %d level %d field order %d color_range %d"
        " color_primaries %d color_trc %d color_space %d location %d video_delay %d",
        static_cast<int>(par->codec_type),
        av_get_media_type_string(par->codec_type),
        static_cast<int>(par->codec_id),
        avcodec_get_name(par->codec_id),
        (char)(par->codec_tag& 0xFF),
        (char)((par->codec_tag >> 8) & 0xFF),
        (char)((par->codec_tag >> 16) & 0xFF),
        (char)((par->codec_tag >> 24) & 0xFF),
        par->width,
        par->height,
        par->bit_rate,
        par->bits_per_coded_sample,
        par->bits_per_raw_sample,
        par->format,
        (((AVPixelFormat)par->format == AV_PIX_FMT_NONE) ? "none" : av_get_pix_fmt_name((AVPixelFormat)par->format)),
  par->extradata_size, ByteArrayToHexString(nonstd::span<const uint8> {
    par->extradata,
#if __cplusplus >= 202002L
    static_cast<std::span<const unsigned char>::size_type>(par->extradata_size)
#else
    static_cast<nonstd::span_lite::span<const unsigned char>::size_type>(par->extradata_size)
#endif
  }).c_str(),
  par->profile,
  par->level,
  static_cast<int>(par->field_order),
  static_cast<int>(par->color_range),
  static_cast<int>(par->color_primaries),
  static_cast<int>(par->color_trc),
  static_cast<int>(par->color_space),
  static_cast<int>(par->chroma_location),
  static_cast<int>(par->video_delay)
       );
}

void zm_dump_codec(const AVCodecContext *codec) {
  Debug(1, "Dumping codec_context codec_type %d %s codec_id %d %s tag %c%c%c%c width %d height %d timebase %d/%d format %s profile %d level %d "
        "gop_size %d has_b_frames %d max_b_frames %d me_cmp %d me_range %d qmin %d qmax %d bit_rate %" PRId64 " qcompress %f extradata:%d:%s",
        codec->codec_type,
        av_get_media_type_string(codec->codec_type),
        codec->codec_id,
        avcodec_get_name(codec->codec_id),
        (char)(codec->codec_tag& 0xFF),
        (char)((codec->codec_tag >> 8) & 0xFF),
        (char)((codec->codec_tag >> 16) & 0xFF),
        (char)((codec->codec_tag >> 24) & 0xFF),
        codec->width,
        codec->height,
        codec->time_base.num,
        codec->time_base.den,
        (codec->pix_fmt == AV_PIX_FMT_NONE ? "none" : av_get_pix_fmt_name(codec->pix_fmt)),
        codec->profile,
        codec->level,
        codec->gop_size,
        codec->has_b_frames,
        codec->max_b_frames,
        codec->me_cmp,
        codec->me_range,
        codec->qmin,
        codec->qmax,
        codec->bit_rate,
        codec->qcompress,
        codec->extradata_size,
  ByteArrayToHexString(nonstd::span<const uint8> {
    codec->extradata,
#if __cplusplus >= 202002L
    static_cast<std::span<const unsigned char>::size_type>(codec->extradata_size)
#else
    static_cast<nonstd::span_lite::span<const unsigned char>::size_type>(codec->extradata_size)
#endif
  }).c_str()
       );
}

/* "user interface" functions */
void zm_dump_stream_format(AVFormatContext *ic, int i, int index, int is_output) {
  Debug(1, "Dumping stream index i(%d) index(%d)", i, index);
  AVStream *st = ic->streams[i];
  int flags = (is_output ? ic->oformat->flags : ic->iformat->flags);
  if (flags & AVFMT_SHOW_IDS)
    Debug(1, "ids [0x%x]", st->id);
  Debug(1, "    Stream #%d:%d", index, i);
  zm_dump_stream(st);
}
void zm_dump_stream(AVStream *st) {
  AVDictionaryEntry *lang = av_dict_get(st->metadata, "language", nullptr, 0);
  AVCodecParameters *codec = st->codecpar;

  /* the pid is an important information, so we display it */
  /* XXX: add a generic system */
  if (lang)
    Debug(1, "language (%s)", lang->value);
  Debug(1, "frame_size:%d stream timebase: %d/%d", codec->frame_size, st->time_base.num, st->time_base.den);
  Debug(1, "codec: %s %s", avcodec_get_name(st->codecpar->codec_id), av_get_media_type_string(st->codecpar->codec_type));

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
  } else {
    Debug(1, ", SAR %d:%d ",
          st->sample_aspect_ratio.num, st->sample_aspect_ratio.den);
  }

  if (codec->codec_type == AVMEDIA_TYPE_VIDEO) {
    int fps = st->avg_frame_rate.den && st->avg_frame_rate.num;
    int tbn = st->time_base.den && st->time_base.num;

    if (fps)
      zm_log_fps(av_q2d(st->avg_frame_rate), "fps");
    if (tbn)
      zm_log_fps(1 / av_q2d(st->time_base), "stream tb numerator");
  } else if (codec->codec_type == AVMEDIA_TYPE_AUDIO) {
#if LIBAVUTIL_VERSION_CHECK(57, 28, 100, 28, 0)
    Debug(1, "profile %d channels %d sample_rate %d",
          codec->profile, codec->ch_layout.nb_channels, codec->sample_rate);
#else
    Debug(1, "profile %d channels %d sample_rate %d",
          codec->profile, codec->channels, codec->sample_rate);
#endif
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
#if LIBAVCODEC_VERSION_CHECK(61, 19,100, 19, 100)
  const enum AVSampleFormat *sample_fmts;
  int num_sample_fmts;
  int ret = avcodec_get_supported_config(nullptr, codec, AV_CODEC_CONFIG_SAMPLE_FORMAT, 0, (const void **) &sample_fmts, &num_sample_fmts);
  if (ret < 0)
    return ret;
  if (sample_fmts) {
    for (int i = 0; i < num_sample_fmts; i++) {
      if (sample_fmt == sample_fmts[i])
        return 1;
    }
  } // end if sample_fmts

#else
  const enum AVSampleFormat *p = codec->sample_fmts;

  while (*p != AV_SAMPLE_FMT_NONE) {
    if (*p == sample_fmt)
      return 1;
    else Debug(2, "Not %s", av_get_sample_fmt_name( *p ) );
    p++;
  }
#endif
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

bool is_video_stream(const AVStream * stream) {
  if (stream->codecpar->codec_type == AVMEDIA_TYPE_VIDEO) {
    return true;
  }

  Debug(2, "Not a video type %d != %d", stream->codecpar->codec_type, AVMEDIA_TYPE_VIDEO);
  return false;
}

bool is_video_context(const AVCodecContext *codec_context) {
  return codec_context->codec_type == AVMEDIA_TYPE_VIDEO;
}

bool is_audio_stream(const AVStream *stream) {
  return stream->codecpar->codec_type == AVMEDIA_TYPE_AUDIO;
}

bool is_audio_context(const AVCodecContext *codec_context) {
  return codec_context->codec_type == AVMEDIA_TYPE_AUDIO;
}

int zm_receive_packet(AVCodecContext *context, AVPacket &packet) {
  int ret = avcodec_receive_packet(context, &packet);
  if ((ret < 0) and (AVERROR_EOF != ret)) {
    Error("Error encoding (%d) (%s)", ret, av_err2str(ret));
  }
  return ret; // 1 or 0
}  // end int zm_receive_packet(AVCodecContext *context, AVPacket &packet)

int zm_send_packet_receive_frame(AVCodecContext *context, AVFrame *frame, AVPacket &packet) {
  int pkt_ret, frm_ret;

  pkt_ret = avcodec_send_packet(context, &packet);
  frm_ret = avcodec_receive_frame(context, frame);

  if (pkt_ret == 0 && frm_ret == 0) {
    // In this api the packet is always consumed, so return packet.bytes
    return packet.size;
  } else if (pkt_ret != 0 && pkt_ret != AVERROR(EAGAIN)) {
    //Error("Could not send packet (error %d = %s)", pkt_ret,
          //av_make_error_string(pkt_ret).c_str());
    return pkt_ret;
  } else if (frm_ret != 0 && frm_ret != AVERROR(EAGAIN)) {
    //Error("Could not receive frame (error %d = %s)", frm_ret,
          //av_make_error_string(frm_ret).c_str());
    return frm_ret;
  }

  return 0;
}  // end int zm_send_packet_receive_frame(AVCodecContext *context, AVFrame *frame, AVPacket &packet)

/* Returns < 0 on error, 0 if codec not ready, 1 on success
 */
int zm_send_frame_receive_packet(AVCodecContext *ctx, AVFrame *frame, AVPacket &packet) {
  int frm_ret, pkt_ret;

  frm_ret = avcodec_send_frame(ctx, frame);
  pkt_ret = avcodec_receive_packet(ctx, &packet);

  if (frm_ret != 0 && frame) {
    Error("Could not send frame (error '%s')",
          av_make_error_string(frm_ret).c_str());
    return frm_ret;
  } else if (pkt_ret != 0) {
    if (pkt_ret == AVERROR(EAGAIN)) {
      // The codec may need more samples than it has, perfectly valid
      Debug(2, "Codec not ready to give us a packet");
      return 0;
    } else if (frame) {
      // May get EOF if frame is NULL because it signals flushing
      Error("Could not receive packet (error %d = '%s')", pkt_ret,
            av_make_error_string(pkt_ret).c_str());
    }
    zm_av_packet_unref(&packet);
    return pkt_ret;
  }
  return 1;
}  // end int zm_send_frame_receive_packet

void zm_free_codec(AVCodecContext **ctx) {
  if (*ctx) {
    // We allocate and copy in newer ffmpeg, so need to free it
    avcodec_free_context(ctx);
    *ctx = nullptr;
  }
}

void zm_packet_copy_rescale_ts(const AVPacket *ipkt, AVPacket *opkt, const AVRational src_tb, const AVRational dst_tb) {
  opkt->pts = ipkt->pts;
  opkt->dts = ipkt->dts;
  opkt->duration = ipkt->duration;
  av_packet_rescale_ts(opkt, src_tb, dst_tb);
}

int zm_resample_audio(SwrContext *resample_ctx, AVFrame *in_frame, AVFrame *out_frame) {
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
  zm_dump_frame(out_frame, "Out frame after resample");
  return 1;
}

int zm_resample_get_delay(SwrContext *resample_ctx, int time_base) {
  return swr_get_delay(resample_ctx, time_base);
}

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
