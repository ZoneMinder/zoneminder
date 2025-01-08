// ZoneMinder Video Storage Implementation
// Written by Chris Wiggins
// http://chriswiggins.co.nz
// Modification by Steve Gilvarry
//
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; either version 2
// of the License, or (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
//

#include "zm_videostore.h"

#include "zm_logger.h"
#include "zm_monitor.h"

extern "C" {
#include <libavutil/time.h>
#include <libavutil/display.h>
}

#include <string>

/*
      AVCodecID codec_id;
      char *codec_codec;
      char *codec_name;
      enum AVPixelFormat sw_pix_fmt;
      enum AVPixelFormat hw_pix_fmt;
      AVHWDeviceType hwdevice_type;
      */

VideoStore::CodecData VideoStore::codec_data[] = {
#if HAVE_LIBAVUTIL_HWCONTEXT_H && LIBAVCODEC_VERSION_CHECK(57, 107, 0, 107, 0)
  { AV_CODEC_ID_H265, "h265", "hevc_vaapi", AV_PIX_FMT_NV12, AV_PIX_FMT_VAAPI, AV_HWDEVICE_TYPE_VAAPI },
  { AV_CODEC_ID_H265, "h265", "h265_ni_quadra_dec", AV_PIX_FMT_YUV420P, AV_PIX_FMT_NI_QUAD, AV_HWDEVICE_TYPE_NI_QUADRA },
  { AV_CODEC_ID_H265, "h265", "h265_ni_quadra_enc", AV_PIX_FMT_YUV420P, AV_PIX_FMT_NI_QUAD, AV_HWDEVICE_TYPE_NI_QUADRA },
  { AV_CODEC_ID_H265, "h265", "hevc_nvenc", AV_PIX_FMT_NV12, AV_PIX_FMT_NV12, AV_HWDEVICE_TYPE_NONE },
  { AV_CODEC_ID_H265, "h265", "libx265", AV_PIX_FMT_YUV420P, AV_PIX_FMT_YUV420P, AV_HWDEVICE_TYPE_NONE  },

  { AV_CODEC_ID_H264, "h264", "h264_vaapi", AV_PIX_FMT_NV12, AV_PIX_FMT_VAAPI, AV_HWDEVICE_TYPE_VAAPI },
  { AV_CODEC_ID_H264, "h264", "h264_ni_quadra_dec", AV_PIX_FMT_YUV420P, AV_PIX_FMT_NI_QUAD, AV_HWDEVICE_TYPE_NI_QUADRA },
  { AV_CODEC_ID_H264, "h264", "h264_ni_quadra_enc", AV_PIX_FMT_YUV420P, AV_PIX_FMT_NI_QUAD, AV_HWDEVICE_TYPE_NI_QUADRA },
  { AV_CODEC_ID_H264, "h264", "h264_nvenc", AV_PIX_FMT_NV12, AV_PIX_FMT_NV12, AV_HWDEVICE_TYPE_NONE },
  { AV_CODEC_ID_H264, "h264", "h264_omx", AV_PIX_FMT_YUV420P, AV_PIX_FMT_YUV420P,  AV_HWDEVICE_TYPE_NONE },
  { AV_CODEC_ID_H264, "h264", "h264", AV_PIX_FMT_YUV420P, AV_PIX_FMT_YUV420P,  AV_HWDEVICE_TYPE_NONE },
  { AV_CODEC_ID_H264, "h264", "libx264", AV_PIX_FMT_YUV420P, AV_PIX_FMT_YUV420P, AV_HWDEVICE_TYPE_NONE  },
  { AV_CODEC_ID_MJPEG, "mjpeg", "mjpeg", AV_PIX_FMT_YUVJ422P, AV_PIX_FMT_YUVJ422P, AV_HWDEVICE_TYPE_NONE },
  { AV_CODEC_ID_AV1, "av1", "av1_ni_quadra_enc", AV_PIX_FMT_YUV420P, AV_PIX_FMT_NI_QUAD, AV_HWDEVICE_TYPE_NI_QUADRA },
  { AV_CODEC_ID_AV1, "av1", "libaom-av1", AV_PIX_FMT_YUV420P, AV_PIX_FMT_YUV420P, AV_HWDEVICE_TYPE_NONE },
#else
  { AV_CODEC_ID_H265, "h265", "libx265", AV_PIX_FMT_YUV420P, AV_PIX_FMT_YUV420P },

  { AV_CODEC_ID_H264, "h264", "h264", AV_PIX_FMT_YUV420P, AV_PIX_FMT_YUV420P },
  { AV_CODEC_ID_H264, "h264", "libx264", AV_PIX_FMT_YUV420P, AV_PIX_FMT_YUV420P },
  { AV_CODEC_ID_MJPEG, "mjpeg", "mjpeg", AV_PIX_FMT_YUVJ422P, AV_PIX_FMT_YUVJ422P },
#endif
};

VideoStore::VideoStore(
    const char *filename_in,
    const char *format_in,
    AVStream *p_video_in_stream,
    AVCodecContext *p_video_in_ctx,
    AVStream *p_audio_in_stream,
    AVCodecContext *p_audio_in_ctx,
    Monitor *p_monitor
    ) :
  chosen_codec_data(nullptr),
  monitor(p_monitor),
  out_format(nullptr),
  oc(nullptr),
  video_out_stream(nullptr),
  audio_out_stream(nullptr),
  video_out_codec(nullptr),
  video_in_ctx(p_video_in_ctx),
  video_out_ctx(nullptr),
  video_in_stream(p_video_in_stream),
  audio_in_stream(p_audio_in_stream),
  audio_in_codec(nullptr),
  audio_in_ctx(p_audio_in_ctx),
  audio_out_codec(nullptr),
  audio_out_ctx(nullptr),
  video_in_frame(nullptr),
  in_frame(nullptr),
  out_frame(nullptr),
  hw_frame(nullptr),
  packets_written(0),
  frame_count(0),
  hw_device_ctx(nullptr),
#if defined(HAVE_LIBSWRESAMPLE) || defined(HAVE_LIBAVRESAMPLE)
  resample_ctx(nullptr),
#if defined(HAVE_LIBSWRESAMPLE)
  fifo(nullptr),
#endif
#endif
  converted_in_samples(nullptr),
  filename(filename_in),
  format(format_in),
  video_first_pts(AV_NOPTS_VALUE),
  video_first_dts(AV_NOPTS_VALUE),
  audio_first_pts(AV_NOPTS_VALUE),
  audio_first_dts(AV_NOPTS_VALUE),
  video_last_pts(AV_NOPTS_VALUE),
  audio_last_pts(AV_NOPTS_VALUE),
  next_dts(nullptr),
  audio_next_pts(0),
  max_stream_index(-1),
  reorder_queue_size(0)
{
  FFMPEGInit();
  swscale.init();
  opkt = av_packet_ptr{av_packet_alloc()};
}  // VideoStore::VideoStore

bool VideoStore::open() {
  Debug(1, "Opening video storage stream %s format: %s", filename, format);

  int ret = avformat_alloc_output_context2(&oc, nullptr, nullptr, filename);
  if (ret < 0) {
    Warning(
        "Could not create video storage stream %s as no out ctx"
        " could be assigned based on filename: %s",
        filename, av_make_error_string(ret).c_str());
  }

  // Couldn't deduce format from filename, trying from format name
  if (!oc) {
    avformat_alloc_output_context2(&oc, nullptr, format, filename);
    if (!oc) {
      Error(
          "Could not create video storage stream %s as no out ctx"
          " could not be assigned based on filename or format %s",
          filename, format);
      return false;
    }
  } // end if ! oc

  AVDictionary *pmetadata = nullptr;
  ret = av_dict_set(&pmetadata, "title", "Zoneminder Security Recording", 0);
  if (ret < 0) Warning("%s:%d: title set failed", __FILE__, __LINE__);

  AVDictionary *opts = nullptr;
  std::string options = monitor->GetEncoderOptions();
  ret = av_dict_parse_string(&opts, options.c_str(), "=", ",#\n", 0);
  if (ret < 0) {
    Warning("Could not parse ffmpeg encoder options list '%s'", options.c_str());
  } else {
    const AVDictionaryEntry *entry = av_dict_get(opts, "reorder_queue_size", nullptr, AV_DICT_MATCH_CASE);
    if (entry) {
      if (monitor->GetOptVideoWriter() == Monitor::ENCODE) {
        Debug(1, "reorder_queue_size ignored for non-passthrough");
      } else {
        reorder_queue_size = std::stoul(entry->value);
        Debug(1, "reorder_queue_size set to %zu", reorder_queue_size);
      }
      // remove it to prevent complaining later.
      av_dict_set(&opts, "reorder_queue_size", nullptr, AV_DICT_MATCH_CASE);
    }
  }

  oc->metadata = pmetadata;
#if !LIBAVFORMAT_VERSION_CHECK(59, 16, 0, 2, 0)
  oc->oformat->flags |= AVFMT_TS_NONSTRICT; // allow non increasing dts
#endif
  out_format = const_cast<AVOutputFormat *>(oc->oformat);

  if (video_in_stream) {
#if LIBAVCODEC_VERSION_CHECK(57, 64, 0, 64, 0)
    zm_dump_codecpar(video_in_stream->codecpar);
#endif
    if (monitor->GetOptVideoWriter() == Monitor::PASSTHROUGH) {
      // Don't care what codec, just copy parameters
      video_out_ctx = avcodec_alloc_context3(nullptr);
      // There might not be a useful video_in_stream.  v4l in might not populate this very
#if LIBAVCODEC_VERSION_CHECK(57, 64, 0, 64, 0)
      ret = avcodec_parameters_to_context(video_out_ctx, video_in_stream->codecpar);
#else
      ret = avcodec_copy_context(video_out_ctx, video_in_ctx);
#endif
      if (ret < 0) {
        Error("Could not initialize ctx parameters");
        return false;
      }
      video_out_ctx->pix_fmt = fix_deprecated_pix_fmt(video_out_ctx->pix_fmt);
      if (oc->oformat->flags & AVFMT_GLOBALHEADER) {
#if LIBAVCODEC_VERSION_CHECK(56, 35, 0, 64, 0)
        video_out_ctx->flags |= AV_CODEC_FLAG_GLOBAL_HEADER;
#else
        video_out_ctx->flags |= CODEC_FLAG_GLOBAL_HEADER;
#endif
      }
      video_out_ctx->time_base = video_in_ctx->time_base;
      if (!(video_out_ctx->time_base.num && video_out_ctx->time_base.den)) {
        Debug(2,"No timebase found in video in context, defaulting to Q");
        video_out_ctx->time_base = AV_TIME_BASE_Q;
      }
    } else if (monitor->GetOptVideoWriter() == Monitor::ENCODE) {
      int wanted_codec = monitor->OutputCodec();
      if (!wanted_codec) {
        // default to h264
        //Debug(2, "Defaulting to H264");
        //wanted_codec = AV_CODEC_ID_H264;
        // FIXME what is the optimal codec?  Probably low latency h264 which is effectively mjpeg
      } else {
				if (AV_CODEC_ID_H264 != 27 and wanted_codec > 3) {
					// Older ffmpeg had AV_CODEC_ID_MPEG2VIDEO_XVMC at position 3 has been deprecated
					wanted_codec += 1;
				}
        Debug(2, "Codec wanted %d %s", wanted_codec, avcodec_get_name((AVCodecID)wanted_codec));
      }
      std::string wanted_encoder = monitor->Encoder();

      for (unsigned int i = 0; i < sizeof(codec_data) / sizeof(*codec_data); i++) {
        chosen_codec_data = &codec_data[i];
        if (wanted_encoder != "" and wanted_encoder != "auto") {
          if (wanted_encoder != codec_data[i].codec_name) {
            Debug(1, "Not the right codec name %s != %s", codec_data[i].codec_name, wanted_encoder.c_str());
            continue;
          }
        }
        if (wanted_codec and (codec_data[i].codec_id != wanted_codec)) {
          Debug(1, "Not the right codec %d %s != %d %s",
							codec_data[i].codec_id,
							avcodec_get_name(codec_data[i].codec_id),
							wanted_codec,
							avcodec_get_name((AVCodecID)wanted_codec)
							);
          continue;
        }

        video_out_codec = avcodec_find_encoder_by_name(codec_data[i].codec_name);
        if (!video_out_codec) {
          Debug(1, "Didn't find encoder for %s", codec_data[i].codec_name);
          continue;
        }
        Debug(1, "Found video codec for %s", codec_data[i].codec_name);
        video_out_ctx = avcodec_alloc_context3(video_out_codec);
        if (oc->oformat->flags & AVFMT_GLOBALHEADER) {
#if LIBAVCODEC_VERSION_CHECK(56, 35, 0, 64, 0)
          video_out_ctx->flags |= AV_CODEC_FLAG_GLOBAL_HEADER;
#else
          video_out_ctx->flags |= CODEC_FLAG_GLOBAL_HEADER;
#endif
        }

        // When encoding, we are going to use the timestamp values instead of packet pts/dts
        video_out_ctx->time_base = AV_TIME_BASE_Q;
        video_out_ctx->codec_id = codec_data[i].codec_id;
        video_out_ctx->pix_fmt = codec_data[i].hw_pix_fmt;
        Debug(1, "Setting pix fmt to %d %s", codec_data[i].hw_pix_fmt, av_get_pix_fmt_name(codec_data[i].hw_pix_fmt));
        video_out_ctx->level = 32;

        // Don't have an input stream, so need to tell it what we are sending it, or are transcoding
        video_out_ctx->width = monitor->Width();
        video_out_ctx->height = monitor->Height();
        video_out_ctx->codec_type = AVMEDIA_TYPE_VIDEO;

        if (video_out_ctx->codec_id == AV_CODEC_ID_H264) {
          video_out_ctx->bit_rate = 2000000;
          video_out_ctx->gop_size = 12;
          video_out_ctx->max_b_frames = 1;
        } else if (video_out_ctx->codec_id == AV_CODEC_ID_MPEG2VIDEO) {
          /* just for testing, we also add B frames */
          video_out_ctx->max_b_frames = 2;
        } else if (video_out_ctx->codec_id == AV_CODEC_ID_MPEG1VIDEO) {
          /* Needed to avoid using macroblocks in which some coeffs overflow.
           * This does not happen with normal video, it just happens here as
           * the motion of the chroma plane does not match the luma plane. */
          video_out_ctx->mb_decision = 2;
        }
#if HAVE_LIBAVUTIL_HWCONTEXT_H && LIBAVCODEC_VERSION_CHECK(57, 107, 0, 107, 0)
        if (codec_data[i].hwdevice_type != AV_HWDEVICE_TYPE_NONE) {
          Debug(1, "Setting up hwdevice");
	  if (codec_data[i].hwdevice_type == AV_HWDEVICE_TYPE_NI_QUADRA) {
		ret = av_hwdevice_ctx_create(&hw_device_ctx, codec_data[i].hwdevice_type, "-1", nullptr, 0);
	  } else {
		ret = av_hwdevice_ctx_create(&hw_device_ctx, codec_data[i].hwdevice_type, nullptr, nullptr, 0);
	  }
          if (0>ret) {
            Error("Failed to create hwdevice_ctx");
            continue;
          }

          AVBufferRef *hw_frames_ref;
          AVHWFramesContext *frames_ctx = nullptr;

          if (!(hw_frames_ref = av_hwframe_ctx_alloc(hw_device_ctx))) {
            Error("Failed to create hwaccel frame context.");
            continue;
          }
          frames_ctx = (AVHWFramesContext *)(hw_frames_ref->data);
          frames_ctx->format    = codec_data[i].hw_pix_fmt;
          frames_ctx->sw_format = codec_data[i].sw_pix_fmt;
          frames_ctx->width     = monitor->Width();
          frames_ctx->height    = monitor->Height();
          frames_ctx->initial_pool_size = 20;
          if ((ret = av_hwframe_ctx_init(hw_frames_ref)) < 0) {
            Error("Failed to initialize hwaccel frame context."
                "Error code: %s", av_err2str(ret));
            av_buffer_unref(&hw_frames_ref);
          } else {
            video_out_ctx->hw_frames_ctx = av_buffer_ref(hw_frames_ref);
            if (!video_out_ctx->hw_frames_ctx) {
              Error("Failed to allocate hw_frames_ctx");
            }
          }
          av_buffer_unref(&hw_frames_ref);
          av_buffer_unref(&hw_device_ctx);
        }  // end if hwdevice_type != NONE
#endif

        if ((ret = avcodec_open2(video_out_ctx, video_out_codec, &opts)) < 0) {
          if (wanted_encoder != "" and wanted_encoder != "auto") {
            Warning("Can't open video codec (%s) %s",
                video_out_codec->name,
                av_make_error_string(ret).c_str()
                );
          } else {
            Debug(1, "Can't open video codec (%s) %s",
                video_out_codec->name,
                av_make_error_string(ret).c_str()
                );
          }
          video_out_codec = nullptr;
        }

        AVDictionaryEntry *e = nullptr;
        while ((e = av_dict_get(opts, "", e, AV_DICT_IGNORE_SUFFIX)) != nullptr) {
          Warning("Encoder Option %s not recognized by ffmpeg codec", e->key);
        }
        av_dict_parse_string(&opts, options.c_str(), "=", ",#\n", 0);
        if (reorder_queue_size) {
          // remove it to prevent complaining later.
          av_dict_set(&opts, "reorder_queue_size", nullptr, AV_DICT_MATCH_CASE);
        }

        if (video_out_codec) break;
#if LIBAVCODEC_VERSION_CHECK(57, 64, 0, 64, 0)
        // We allocate and copy in newer ffmpeg, so need to free it
        avcodec_free_context(&video_out_ctx);
        if (hw_device_ctx) av_buffer_unref(&hw_device_ctx);
#endif
      }  // end foreach codec

      if (!video_out_codec) {
        Error("Can't open video codec!");
        return false;
      }  // end if can't open codec
      Debug(2, "Success opening codec");
    }  // end if copying or transcoding
    zm_dump_codec(video_out_ctx);
  }  // end if video_in_stream

  video_out_stream = avformat_new_stream(oc, video_out_codec);
  if (!video_out_stream) {
    Error("Unable to create video out stream");
    return false;
  }
  max_stream_index = video_out_stream->index;
#if LIBAVCODEC_VERSION_CHECK(57, 64, 0, 64, 0)
  ret = avcodec_parameters_from_context(video_out_stream->codecpar, video_out_ctx);
  if ( ret < 0 ) {
    Error("Could not initialize stream parameteres");
    return false;
  }
#else
  avcodec_copy_context(video_out_stream->codec, video_out_ctx);
#endif
  last_dts[video_out_stream->index] = AV_NOPTS_VALUE;

  reorder_queues[video_out_stream->index] = {};

  video_out_stream->time_base = video_in_stream ? video_in_stream->time_base : AV_TIME_BASE_Q;
  if (monitor->GetOptVideoWriter() == Monitor::PASSTHROUGH) {
    // Only set orientation if doing passthrough, otherwise the frame image will be rotated
    Monitor::Orientation orientation = monitor->getOrientation();
    if (orientation) {
#if LIBAVCODEC_VERSION_CHECK(59, 37, 100, 37, 100)
      int32_t* displaymatrix = static_cast<int32_t*>(av_malloc(sizeof(int32_t)*9));
      Debug(3, "Have orientation %d", orientation);
      if (orientation == Monitor::ROTATE_0) {
      } else if (orientation == Monitor::ROTATE_90) {
        av_display_rotation_set(displaymatrix, 90);
      } else if (orientation == Monitor::ROTATE_180) {
        av_display_rotation_set(displaymatrix, 180);
      } else if (orientation == Monitor::ROTATE_270) {
        av_display_rotation_set(displaymatrix, 270);
      } else {
        Warning("Unsupported Orientation(%d)", orientation);
      }
#endif
#if LIBAVCODEC_VERSION_CHECK(60, 31, 102, 31, 102)
      av_packet_side_data_add(
          &video_out_stream->codecpar->coded_side_data,
          &video_out_stream->codecpar->nb_coded_side_data,
          AV_PKT_DATA_DISPLAYMATRIX,
          (int32_t *)displaymatrix, sizeof(int32_t)*9, 0);
#else
#if LIBAVCODEC_VERSION_CHECK(59, 37, 100, 37, 100)
      av_stream_add_side_data(video_out_stream,
          AV_PKT_DATA_DISPLAYMATRIX,
          (uint8_t *)displaymatrix,
          sizeof(*displaymatrix));
#endif
#endif
      if (orientation == Monitor::ROTATE_0) {
      } else if (orientation == Monitor::ROTATE_90) {
        ret = av_dict_set(&video_out_stream->metadata, "rotate", "90", 0);
        if (ret < 0) Warning("%s:%d: title set failed", __FILE__, __LINE__);
      } else if (orientation == Monitor::ROTATE_180) {
        ret = av_dict_set(&video_out_stream->metadata, "rotate", "180", 0);
        if (ret < 0) Warning("%s:%d: title set failed", __FILE__, __LINE__);
      } else if (orientation == Monitor::ROTATE_270) {
        ret = av_dict_set(&video_out_stream->metadata, "rotate", "270", 0);
        if (ret < 0) Warning("%s:%d: title set failed", __FILE__, __LINE__);
      } else {
        Warning("Unsupported Orientation(%d)", orientation);
      }
    } // end if orientation
  }  // end if passthrough

  if (audio_in_stream and audio_in_ctx) {
    Debug(2, "Have audio_in_stream %p", audio_in_stream);

    if (CODEC(audio_in_stream)->codec_id != AV_CODEC_ID_AAC) {
      audio_out_codec = avcodec_find_encoder(AV_CODEC_ID_AAC);
      if (!audio_out_codec) {
        Error("Could not find codec for AAC");
      } else {
#if LIBAVCODEC_VERSION_CHECK(57, 64, 0, 64, 0)
        audio_in_ctx = avcodec_alloc_context3(audio_out_codec);
        ret = avcodec_parameters_to_context(audio_in_ctx,
            audio_in_stream->codecpar);
        audio_in_ctx->time_base = audio_in_stream->time_base;
#else
        audio_in_ctx = audio_in_stream->codec;
#endif

#if LIBAVCODEC_VERSION_CHECK(57, 64, 0, 64, 0)
        audio_out_ctx = avcodec_alloc_context3(audio_out_codec);
        if (!audio_out_ctx) {
          Error("could not allocate codec ctx for AAC");
          return false;
        }
#else
        audio_out_ctx = audio_out_stream->codec;
#endif
        audio_out_stream = avformat_new_stream(oc, audio_out_codec);
        audio_out_stream->time_base = audio_in_stream->time_base;

        if (!setup_resampler()) {
          return false;
        }
      }  // end if found AAC codec
    } else {
      Debug(2, "Got AAC");

      // normally we want to pass params from codec in here
      // but since we are doing audio passthrough we don't care
      audio_out_stream = avformat_new_stream(oc, audio_out_codec);
      if (!audio_out_stream) {
        Error("Could not allocate new stream");
        return false;
      }
      audio_out_stream->time_base = audio_in_stream->time_base;

#if LIBAVCODEC_VERSION_CHECK(57, 64, 0, 64, 0)
      // Just use the ctx to copy the parameters over
      audio_out_ctx = avcodec_alloc_context3(audio_out_codec);
      if (!audio_out_ctx) {
        Error("Could not allocate new output_context");
        return false;
      }

      // We don't actually care what the time_base is..
      audio_out_ctx->time_base = audio_in_ctx->time_base;

      // Copy params from instream to ctx
      ret = avcodec_parameters_to_context(
          audio_out_ctx, audio_in_stream->codecpar);
      if (ret < 0) {
        Error("Unable to copy audio params to ctx %s",
              av_make_error_string(ret).c_str());
      }
      ret = avcodec_parameters_from_context(
          audio_out_stream->codecpar, audio_out_ctx);
      if (ret < 0) {
        Error("Unable to copy audio params to stream %s",
              av_make_error_string(ret).c_str());
      }
#else
      audio_out_ctx = audio_out_stream->codec;
      ret = avcodec_copy_context(audio_out_ctx, audio_in_stream->codec);
      if (ret < 0) {
        Error("Unable to copy audio ctx %s",
              av_make_error_string(ret).c_str());
        audio_out_stream = nullptr;
        return false;
      }  // end if
      audio_out_ctx->codec_tag = 0;
#endif

#if LIBAVCODEC_VERSION_CHECK(59, 24, 100, 24, 100)
      /* Seems like technically we could have multple channels, so let's not implement this for ffmpeg 5 */
#else
      if (audio_out_ctx->channels > 1) {
        Warning("Audio isn't mono, changing it.");
        audio_out_ctx->channels = 1;
      } else {
        Debug(3, "Audio is mono");
      }
#endif
    } // end if is AAC

    if (oc->oformat->flags & AVFMT_GLOBALHEADER) {
#if LIBAVCODEC_VERSION_CHECK(56, 35, 0, 64, 0)
      audio_out_ctx->flags |= AV_CODEC_FLAG_GLOBAL_HEADER;
#else
      audio_out_ctx->flags |= CODEC_FLAG_GLOBAL_HEADER;
#endif
    }

    // We will assume that subsequent stream allocations will increase the index
    max_stream_index = audio_out_stream->index;
    last_dts[audio_out_stream->index] = AV_NOPTS_VALUE;
  }  // end if audio_in_stream

  //max_stream_index is 0-based, so add 1
  next_dts = new int64_t[max_stream_index+1];
  for (int i = 0; i <= max_stream_index; i++) {
    next_dts[i] = 0;
  }

  /* open the out file, if needed */
  if (!(out_format->flags & AVFMT_NOFILE)) {
    ret = avio_open2(&oc->pb, filename, AVIO_FLAG_WRITE, nullptr, nullptr);
    if (ret < 0) {
      Error("Could not open out file '%s': %s", filename,
          av_make_error_string(ret).c_str());
      return false;
    }
  }

  zm_dump_stream_format(oc, 0, 0, 1);
  if (audio_out_stream) zm_dump_stream_format(oc, 1, 0, 1);

  if (!opts) {
    ret = av_dict_parse_string(&opts, options.c_str(), "=", "#,\n", 0);
    if (ret < 0) {
      Warning("Could not parse ffmpeg output options '%s'", options.c_str());
    }
    if (reorder_queue_size) {
      av_dict_set(&opts, "reorder_queue_size", nullptr, AV_DICT_MATCH_CASE);
    }
  }

  const AVDictionaryEntry *movflags_entry = av_dict_get(opts, "movflags", nullptr, AV_DICT_MATCH_CASE);
  if (!movflags_entry) {
    Debug(1, "setting movflags to frag_keyframe+empty_moov");
    // Shiboleth reports that this may break seeking in mp4 before it downloads
    av_dict_set(&opts, "movflags", "frag_keyframe+empty_moov", 0);
  } else {
    Debug(1, "using movflags %s", movflags_entry->value);
  }
  if ((ret = avformat_write_header(oc, &opts)) < 0) {
    Warning("Unable to set movflags trying with defaults.");
    ret = avformat_write_header(oc, nullptr);
  } else if (av_dict_count(opts) != 0) {
    Info("some options not used, turn on debugging for a list.");
    AVDictionaryEntry *e = nullptr;
    while ((e = av_dict_get(opts, "", e, AV_DICT_IGNORE_SUFFIX)) != nullptr) {
      Debug(1, "Encoder Option %s=>%s", e->key, e->value);
      if (!e->value) {
        av_dict_set(&opts, e->key, nullptr, 0);
      }
    }
  }
  if (opts) av_dict_free(&opts);
  if (ret < 0) {
    Error("Error occurred when writing out file header to %s: %s",
        filename, av_make_error_string(ret).c_str());
    avio_closep(&oc->pb);
    return false;
  }

  zm_dump_stream_format(oc, 0, 0, 1);
  if (audio_out_stream) zm_dump_stream_format(oc, 1, 0, 1);
  return true;
} // end bool VideoStore::open()

void VideoStore::flush_codecs() {
  // The codec queues data.  We need to send a flush command and out
  // whatever we get. Failures are not fatal.
  av_packet_ptr pkt{av_packet_alloc()};

  if (!pkt) {
    Error("Unable to allocate packet.");
    return;
  }

  // I got crashes if the codec didn't do DELAY, so let's test for it.
  if (video_out_ctx->codec && ( video_out_ctx->codec->capabilities & 
#if LIBAVCODEC_VERSION_CHECK(57, 64, 0, 64, 0)
        AV_CODEC_CAP_DELAY
#else
        CODEC_CAP_DELAY
#endif
        )) {
    // Put encoder into flushing mode
    while ((zm_send_frame_receive_packet(video_out_ctx, nullptr, *pkt)) > 0) {
      av_packet_guard pkt_guard{pkt};
      av_packet_rescale_ts(pkt.get(),
          video_out_ctx->time_base,
          video_out_stream->time_base);
      if (video_out_ctx->codec_id == AV_CODEC_ID_AV1) {
        if (pkt->duration <= 0)
        {
          if (video_last_pts != AV_NOPTS_VALUE)
          {
            pkt->duration = pkt->pts - video_last_pts;
            Debug(1, "duration calc: pts(%" PRId64 ") - last_pts(%" PRId64 ") = (%" PRId64 ") => (%" PRId64 ")",
                pkt->pts,
                video_last_pts,
                pkt->pts - video_last_pts,
                pkt->duration
                );
            if (pkt->duration <= 0) {
              pkt->duration = av_rescale_q(1, video_in_stream->time_base, video_out_stream->time_base);
            }
          } else {
            pkt->duration = av_rescale_q(1, video_in_stream->time_base, video_out_stream->time_base);
          }
        }
        video_last_pts = pkt->pts;
      }
      write_packet(pkt.get(), video_out_stream);
    } // while have buffered frames
    Debug(1, "Done writing buffered video.");
  } // end if have delay capability

  if (audio_out_codec) {
    // The codec queues data.  We need to send a flush command and out
    // whatever we get. Failures are not fatal.

    int frame_size = audio_out_ctx->frame_size;
    /*
     * At the end of the file, we pass the remaining samples to
     * the encoder. */
    while (zm_resample_get_delay(resample_ctx, audio_out_ctx->sample_rate)) {
      zm_resample_audio(resample_ctx, nullptr, out_frame);

      if (zm_add_samples_to_fifo(fifo, out_frame)) {
        // Should probably set the frame size to what is reported FIXME
        if (zm_get_samples_from_fifo(fifo, out_frame)) {
          if (zm_send_frame_receive_packet(audio_out_ctx, out_frame, *pkt) > 0) {
            av_packet_guard pkt_guard{pkt};
            av_packet_rescale_ts(pkt.get(),
                audio_out_ctx->time_base,
                audio_out_stream->time_base);
            write_packet(pkt.get(), audio_out_stream);
          }
        }  // end if data returned from fifo
      }
    } // end while have buffered samples in the resampler

    Debug(2, "av_audio_fifo_size = %d", av_audio_fifo_size(fifo));
    while (av_audio_fifo_size(fifo) > 0) {
      /* Take one frame worth of audio samples from the FIFO buffer,
       * encode it and write it to the output file. */

      Debug(1, "Remaining samples in fifo for AAC codec frame_size %d > fifo size %d",
          frame_size, av_audio_fifo_size(fifo));

      // SHould probably set the frame size to what is reported FIXME
      if (av_audio_fifo_read(fifo, (void **)out_frame->data, frame_size)) {
        if (zm_send_frame_receive_packet(audio_out_ctx, out_frame, *pkt)) {
          av_packet_guard pkt_guard{pkt};
          pkt->stream_index = audio_out_stream->index;

          av_packet_rescale_ts(pkt.get(),
              audio_out_ctx->time_base,
              audio_out_stream->time_base);
          write_packet(pkt.get(), audio_out_stream);
        }
      }  // end if data returned from fifo
    }  // end while still data in the fifo

#if LIBAVCODEC_VERSION_CHECK(57, 64, 0, 64, 0)
      // Put encoder into flushing mode
      avcodec_send_frame(audio_out_ctx, nullptr);
#endif

    while (true) {
      if (0 >= zm_receive_packet(audio_out_ctx, *pkt)) {
        Debug(1, "No more packets");
        break;
      }
      av_packet_guard pkt_guard{pkt};

      ZM_DUMP_PACKET(pkt, "raw from encoder");
      av_packet_rescale_ts(pkt.get(), audio_out_ctx->time_base, audio_out_stream->time_base);
      ZM_DUMP_STREAM_PACKET(audio_out_stream, pkt, "writing flushed packet");
      write_packet(pkt.get(), audio_out_stream);
    }  // while have buffered frames
  }  // end if audio_out_codec
}  // end flush_codecs

VideoStore::~VideoStore() {

  for (auto &n : reorder_queues) {
    auto &queue = n.second;
    Debug(1, "Queue for %d length is %zu", n.first, queue.size());
    while (!queue.empty()) {
      auto pkt = queue.front();
      queue.pop_front();
      if (pkt->codec_type == AVMEDIA_TYPE_VIDEO) {
        writeVideoFramePacket(pkt);
      } else if (pkt->codec_type == AVMEDIA_TYPE_AUDIO) {
        writeAudioFramePacket(pkt);
      }
      //delete pkt;
    }
  }

  if (oc->pb) {
    flush_codecs();

    // Flush Queues
    Debug(4, "Flushing interleaved queues");
    av_interleaved_write_frame(oc, nullptr);

    Debug(1, "Writing trailer");
    /* Write the trailer before close */
    int rc;
    if ((rc = av_write_trailer(oc)) < 0) {
      Error("Error writing trailer %s", av_err2str(rc));
    } else {
      Debug(3, "Success Writing trailer");
    }

    // When will we not be using a file ?
    if (!(out_format->flags & AVFMT_NOFILE)) {
      /* Close the out file. */
      Debug(4, "Closing");
      if ((rc = avio_close(oc->pb)) < 0) {
        Error("Error closing avio %s", av_err2str(rc));
      }
    } else {
      Debug(3, "Not closing avio because we are not writing to a file.");
    }
    oc->pb = nullptr;
  }  // end if oc->pb

  // I wonder if we should be closing the file first.
  // I also wonder if we really need to be doing all the ctx
  // allocation/de-allocation constantly, or whether we can just re-use it.
  // Just do a file open/close/writeheader/etc.
  // What if we were only doing audio recording?

  if (video_out_stream) {
    video_in_ctx = nullptr;

    avcodec_close(video_out_ctx);
    Debug(3, "Freeing video_out_ctx");
    avcodec_free_context(&video_out_ctx);
    if (hw_device_ctx) {
      Debug(3, "Freeing hw_device_ctx");
      av_buffer_unref(&hw_device_ctx);
    }
  }  // end if video_out_stream

  if (audio_out_stream) {
#if LIBAVCODEC_VERSION_CHECK(57, 64, 0, 64, 0)
    // We allocate and copy in newer ffmpeg, so need to free it
    //avcodec_free_context(&audio_in_ctx);
#endif
    //Debug(4, "Success freeing audio_in_ctx");
    audio_in_codec = nullptr;

    if (audio_out_ctx) {
      Debug(4, "Success closing audio_out_ctx");
      avcodec_close(audio_out_ctx);
#if LIBAVCODEC_VERSION_CHECK(57, 64, 0, 64, 0)
      avcodec_free_context(&audio_out_ctx);
#endif
    }

#if defined(HAVE_LIBAVRESAMPLE) || defined(HAVE_LIBSWRESAMPLE)
    if (resample_ctx) {
      if (fifo) {
        av_audio_fifo_free(fifo);
        fifo = nullptr;
      }
  #if defined(HAVE_LIBSWRESAMPLE)
      swr_free(&resample_ctx);
  #else
    #if defined(HAVE_LIBAVRESAMPLE)
      avresample_close(resample_ctx);
      avresample_free(&resample_ctx);
    #endif
  #endif
    }
    if (in_frame) {
      av_frame_free(&in_frame);
      in_frame = nullptr;
    }
    if (out_frame) {
      av_frame_free(&out_frame);
      out_frame = nullptr;
    }
    if (converted_in_samples) {
      av_free(converted_in_samples);
      converted_in_samples = nullptr;
    }
#endif
  } // end if audio_out_stream

  Debug(4, "free context");
  /* free the streams */
  avformat_free_context(oc);
  delete[] next_dts;
  next_dts = nullptr;
} // VideoStore::~VideoStore()

bool VideoStore::setup_resampler() {
#if !defined(HAVE_LIBSWRESAMPLE) && !defined(HAVE_LIBAVRESAMPLE)
  Error("Not built with resample library. Cannot do audio conversion to AAC");
  return false;
#else
  int ret;

#if LIBAVCODEC_VERSION_CHECK(57, 64, 0, 64, 0)
  // Newer ffmpeg wants to keep everything separate... so have to lookup our own
  // decoder, can't reuse the one from the camera.
  audio_in_codec = avcodec_find_decoder(audio_in_stream->codecpar->codec_id);
  audio_in_ctx = avcodec_alloc_context3(audio_in_codec);
  // Copy params from instream to ctx
  ret = avcodec_parameters_to_context(audio_in_ctx, audio_in_stream->codecpar);
  if (ret < 0) {
    Error("Unable to copy audio params to ctx %s",
        av_make_error_string(ret).c_str());
  }
#else
// codec is already open in ffmpeg_camera
  audio_in_ctx = audio_in_stream->codec;
  audio_in_codec = reinterpret_cast<const AVCodec *>(audio_in_ctx->codec);
  if (!audio_in_codec) {
    audio_in_codec = avcodec_find_decoder(audio_in_stream->codec->codec_id);
    if (!audio_in_codec) {
      return false;
    }
  }
#endif

  // if the codec is already open, nothing is done.
  if ((ret = avcodec_open2(audio_in_ctx, audio_in_codec, nullptr)) < 0) {
    Error("Can't open audio in codec!");
    return false;
  }

  Debug(2, "Got something other than AAC (%s)", audio_in_codec->name);

#if LIBAVCODEC_VERSION_CHECK(59, 24, 100, 24, 100)
#else
  // Some formats (i.e. WAV) do not produce the proper channel layout
  if (audio_in_ctx->channel_layout == 0) {
    Debug(2, "Setting input channel layout to mono");
    // Perhaps we should not be modifying the audio_in_ctx....
    audio_in_ctx->channel_layout = av_get_channel_layout("mono");
  }
#endif

  /* put sample parameters */
  audio_out_ctx->bit_rate = audio_in_ctx->bit_rate <= 32768 ? audio_in_ctx->bit_rate : 32768;
  audio_out_ctx->sample_rate = audio_in_ctx->sample_rate;
  audio_out_ctx->sample_fmt = audio_in_ctx->sample_fmt;
#if LIBAVCODEC_VERSION_CHECK(59, 24, 100, 24, 0)
  av_channel_layout_copy(&audio_out_ctx->ch_layout, &audio_in_ctx->ch_layout);
#else
  audio_out_ctx->channels = audio_in_ctx->channels;
  audio_out_ctx->channel_layout = audio_in_ctx->channel_layout;
  audio_out_ctx->sample_fmt = audio_in_ctx->sample_fmt;
#if LIBAVCODEC_VERSION_CHECK(56, 8, 0, 60, 100)
  if (!audio_out_ctx->channel_layout) {
    Debug(3, "Correcting channel layout from (%" PRIi64 ") to (%" PRIi64 ")",
        audio_out_ctx->channel_layout,
        av_get_default_channel_layout(audio_out_ctx->channels)
        );
      audio_out_ctx->channel_layout = av_get_default_channel_layout(audio_out_ctx->channels);
  }
#endif
#endif

  if (audio_out_codec->supported_samplerates) {
    int found = 0;
    for (unsigned int i = 0; audio_out_codec->supported_samplerates[i]; i++) {
      if (audio_out_ctx->sample_rate ==
          audio_out_codec->supported_samplerates[i]) {
        found = 1;
        break;
      }
    }
    if (found) {
      Debug(3, "Sample rate is good %d", audio_out_ctx->sample_rate);
    } else {
      audio_out_ctx->sample_rate = audio_out_codec->supported_samplerates[0];
      Debug(1, "Sample rate is no good, setting to (%d)",
            audio_out_codec->supported_samplerates[0]);
    }
  }

  /* check that the encoder supports s16 pcm in */
  if (!check_sample_fmt(audio_out_codec, audio_out_ctx->sample_fmt)) {
    Debug(3, "Encoder does not support sample format %s, setting to FLTP",
        av_get_sample_fmt_name(audio_out_ctx->sample_fmt));
    audio_out_ctx->sample_fmt = AV_SAMPLE_FMT_FLTP;
  }

  // Example code doesn't set the codec tb.  I think it just uses whatever defaults
  //audio_out_ctx->time_base = (AVRational){1, audio_out_ctx->sample_rate};

  AVDictionary *opts = nullptr;
  // Needed to allow AAC
  if ((ret = av_dict_set(&opts, "strict", "experimental", 0)) < 0) {
    Error("Couldn't set experimental");
  }
  ret = avcodec_open2(audio_out_ctx, audio_out_codec, &opts);
  av_dict_free(&opts);
  if (ret < 0) {
    Error("could not open codec (%d) (%s)",
        ret, av_make_error_string(ret).c_str());
    audio_out_codec = nullptr;
    audio_out_ctx = nullptr;
    audio_out_stream = nullptr;
    return false;
  }
  zm_dump_codec(audio_out_ctx);

  audio_out_stream->time_base = (AVRational){1, audio_out_ctx->sample_rate};
#if LIBAVCODEC_VERSION_CHECK(57, 64, 0, 64, 0)
  if ( (ret = avcodec_parameters_from_context(
          audio_out_stream->codecpar,
          audio_out_ctx)) < 0 ) {
    Error("Could not initialize stream parameteres");
    return false;
  }
  zm_dump_codecpar(audio_out_stream->codecpar);
#endif

  Debug(3,
        "Time bases: AUDIO in stream (%d/%d) in codec: (%d/%d) out "
        "stream: (%d/%d) out codec (%d/%d)",
        audio_in_stream->time_base.num, audio_in_stream->time_base.den,
        audio_in_ctx->time_base.num, audio_in_ctx->time_base.den,
        audio_out_stream->time_base.num, audio_out_stream->time_base.den,
        audio_out_ctx->time_base.num, audio_out_ctx->time_base.den);

  Debug(1,
        "Audio in bit_rate (%" AV_PACKET_DURATION_FMT ") sample_rate(%d) channels(%d) fmt(%d) frame_size(%d)",
        audio_in_ctx->bit_rate, audio_in_ctx->sample_rate,
#if LIBAVCODEC_VERSION_CHECK(59, 24, 100, 24, 0)
        audio_in_ctx->ch_layout.nb_channels,
#else
        audio_in_ctx->channels,
#endif
        audio_in_ctx->sample_fmt,
        audio_in_ctx->frame_size);
  Debug(1,
        "Audio out context bit_rate (%" AV_PACKET_DURATION_FMT ") sample_rate(%d) channels(%d) fmt(%d) frame_size(%d)",
        audio_out_ctx->bit_rate, audio_out_ctx->sample_rate,
#if LIBAVCODEC_VERSION_CHECK(59, 24, 100, 24, 0)
        audio_out_ctx->ch_layout.nb_channels,
#else
        audio_out_ctx->channels,
#endif
        audio_out_ctx->sample_fmt,
        audio_out_ctx->frame_size);

#if LIBAVCODEC_VERSION_CHECK(57, 64, 0, 64, 0)
  Debug(1,
        "Audio out stream bit_rate (%" PRIi64 ") sample_rate(%d) channels(%d) fmt(%d) frame_size(%d)",
        audio_out_stream->codecpar->bit_rate, audio_out_stream->codecpar->sample_rate,
#if LIBAVCODEC_VERSION_CHECK(59, 24, 100, 24, 0)
        audio_out_stream->codecpar->ch_layout.nb_channels,
#else
        audio_out_stream->codecpar->channels,
#endif
        audio_out_stream->codecpar->format,
        audio_out_stream->codecpar->frame_size);
#else
  Debug(1,
      "Audio out bit_rate (%d) sample_rate(%d) channels(%d) fmt(%d) "
      "layout(%" PRIi64 ") frame_size(%d)",
      audio_out_stream->codec->bit_rate, audio_out_stream->codec->sample_rate,
      audio_out_stream->codec->channels, audio_out_stream->codec->sample_fmt,
      audio_out_stream->codec->channel_layout, audio_out_stream->codec->frame_size);
#endif

  /** Create a new frame to store the audio samples. */
  if (!in_frame) {
    if (!(in_frame = zm_av_frame_alloc())) {
      Error("Could not allocate in frame");
      return false;
    }
  }

  /** Create a new frame to store the audio samples. */
  if (!(out_frame = zm_av_frame_alloc())) {
    Error("Could not allocate out frame");
    av_frame_free(&in_frame);
    return false;
  }
  out_frame->sample_rate = audio_out_ctx->sample_rate;

  if (!(fifo = av_audio_fifo_alloc(
          audio_out_ctx->sample_fmt,
#if LIBAVCODEC_VERSION_CHECK(59, 24, 100, 24, 0)
          audio_out_ctx->ch_layout.nb_channels
#else
          audio_out_ctx->channels
#endif
          , 1))) {
    Error("Could not allocate FIFO");
    return false;
  }
#if defined(HAVE_LIBSWRESAMPLE)
#if LIBAVCODEC_VERSION_CHECK(59, 24, 100, 24, 100)
  if ((ret = swr_alloc_set_opts2(&resample_ctx,
      &audio_out_ctx->ch_layout,
      audio_out_ctx->sample_fmt,
      audio_out_ctx->sample_rate,
      &audio_in_ctx->ch_layout,
      audio_in_ctx->sample_fmt,
      audio_in_ctx->sample_rate,
      0, nullptr)) < 0) {
    Error("Could not allocate resample context");
    return false;
  }
#else
  resample_ctx = swr_alloc_set_opts(nullptr,
      audio_out_ctx->channel_layout,
      audio_out_ctx->sample_fmt,
      audio_out_ctx->sample_rate,
      audio_in_ctx->channel_layout,
      audio_in_ctx->sample_fmt,
      audio_in_ctx->sample_rate,
      0, nullptr);
  if (!resample_ctx) {
    Error("Could not allocate resample context");
    av_frame_free(&in_frame);
    av_frame_free(&out_frame);
    return false;
  }
#endif
  if ((ret = swr_init(resample_ctx)) < 0) {
    Error("Could not open resampler");
    av_frame_free(&in_frame);
    av_frame_free(&out_frame);
    swr_free(&resample_ctx);
    return false;
  }
  Debug(1,"Success setting up SWRESAMPLE");
#else
#if defined(HAVE_LIBAVRESAMPLE)
  // Setup the audio resampler
  resample_ctx = avresample_alloc_context();

  if (!resample_ctx) {
    Error("Could not allocate resample ctx");
    av_frame_free(&in_frame);
    av_frame_free(&out_frame);
    return false;
  }

  av_opt_set_int(resample_ctx, "in_channel_layout",
      audio_in_ctx->channel_layout, 0);
  av_opt_set_int(resample_ctx, "in_sample_fmt",
      audio_in_ctx->sample_fmt, 0);
  av_opt_set_int(resample_ctx, "in_sample_rate",
      audio_in_ctx->sample_rate, 0);
  av_opt_set_int(resample_ctx, "in_channels",
      audio_in_ctx->channels, 0);
  av_opt_set_int(resample_ctx, "out_channel_layout",
      audio_in_ctx->channel_layout, 0);
  av_opt_set_int(resample_ctx, "out_sample_fmt",
      audio_out_ctx->sample_fmt, 0);
  av_opt_set_int(resample_ctx, "out_sample_rate",
      audio_out_ctx->sample_rate, 0);
  av_opt_set_int(resample_ctx, "out_channels",
      audio_out_ctx->channels, 0);

  if ((ret = avresample_open(resample_ctx)) < 0) {
    Error("Could not open resample ctx");
    return false;
  } else {
    Debug(2, "Success opening resampler");
  }
#endif
#endif

  out_frame->nb_samples = audio_out_ctx->frame_size;
  out_frame->format = audio_out_ctx->sample_fmt;
#if LIBAVCODEC_VERSION_CHECK(56, 8, 0, 60, 100)
#if LIBAVCODEC_VERSION_CHECK(59, 24, 100, 24, 100)
  out_frame->ch_layout = audio_out_ctx->ch_layout,
#else
  out_frame->channels = audio_out_ctx->channels;
  out_frame->channel_layout = audio_out_ctx->channel_layout;
#endif
#endif
  out_frame->sample_rate = audio_out_ctx->sample_rate;

  // The codec gives us the frame size, in samples, we calculate the size of the
  // samples buffer in bytes
  unsigned int audioSampleBuffer_size = av_samples_get_buffer_size(
      nullptr,
#if LIBAVCODEC_VERSION_CHECK(59, 24, 100, 24, 100)
      audio_out_ctx->ch_layout.nb_channels,
#else
      audio_out_ctx->channels,
#endif
      audio_out_ctx->frame_size,
      audio_out_ctx->sample_fmt, 0);
  converted_in_samples = reinterpret_cast<uint8_t *>(av_malloc(audioSampleBuffer_size));

  if (!converted_in_samples) {
    Error("Could not allocate converted in sample pointers");
    return false;
  } else {
    Debug(2, "Frame Size %d, sample buffer size %d", audio_out_ctx->frame_size, audioSampleBuffer_size);
  }

  // Setup the data pointers in the AVFrame
  if (avcodec_fill_audio_frame(
        out_frame,
#if LIBAVCODEC_VERSION_CHECK(59, 24, 100, 24, 100)
        audio_out_ctx->ch_layout.nb_channels,
#else
        audio_out_ctx->channels,
#endif
        audio_out_ctx->sample_fmt,
        (const uint8_t *)converted_in_samples,
        audioSampleBuffer_size, 0) < 0) {
    Error("Could not allocate converted in sample pointers");
    return false;
  }

  return true;
#endif
}  // end bool VideoStore::setup_resampler()

int VideoStore::writePacket(const std::shared_ptr<ZMPacket> zm_pkt) {
  int stream_index;
  if (zm_pkt->codec_type == AVMEDIA_TYPE_VIDEO) {
    stream_index = video_out_stream->index;
  } else if (zm_pkt->codec_type == AVMEDIA_TYPE_AUDIO) {
    if (!audio_out_stream) {
      Debug(1, "Called writeAudioFramePacket when no audio_out_stream");
      return 0;
      // FIXME -ve return codes do not free packet in ffmpeg_camera at the moment
    }
    stream_index = audio_out_stream->index;
  } else {
    Error("Unknown stream type in packet (%d)", zm_pkt->codec_type);
    return -1;
  }
  auto &queue = reorder_queues[stream_index];
  Debug(1, "Queue size for %d is %zu", stream_index, queue.size());

  AVPacket *av_pkt = zm_pkt->packet.get();
  // queue the packet
  bool have_out_of_order = false;
  auto rit = queue.rbegin();
  // Find the previous packet for the stream, and check dts
  while (rit != queue.rend()) {
    AVPacket *p = ((*rit)->packet).get();
    if (p->dts <= av_pkt->dts) {
      Debug(1, "Found in order packet");
      // packets are in order, everything is fine
      break;
    } else {
      have_out_of_order = true;
    }
    rit++;
  }  // end while

  if (have_out_of_order) {
    queue.insert(rit.base(), zm_pkt);
    if (rit == queue.rend()) {
      Warning("Unable to re-order packet");
    } else {
      Debug(1, "Found out of order packet");
    }
  } else {
    queue.push_back(zm_pkt);
    Debug(1, "Pushing on queue %d, size is %zu", stream_index, queue.size());
  }

  if (queue.size() > reorder_queue_size) {
    auto pkt = queue.front();
    queue.pop_front();
    if (pkt->codec_type == AVMEDIA_TYPE_VIDEO) {
      return writeVideoFramePacket(pkt);
    } else if (pkt->codec_type == AVMEDIA_TYPE_AUDIO) {
      return writeAudioFramePacket(pkt);
    }
  }
  return 0;
}

int VideoStore::writeVideoFramePacket(const std::shared_ptr<ZMPacket> zm_packet) {
  av_packet_guard pkt_guard;

  frame_count += 1;

  // if we have to transcode
  if (monitor->GetOptVideoWriter() == Monitor::ENCODE) {
    Debug(3, "Have encoding video frame count (%d)", frame_count);

    if (!zm_packet->out_frame) {
      Debug(3, "Have no out frame. codec is %s sw_pf %d %s hw_pf %d %s %dx%d",
          chosen_codec_data->codec_name,
          chosen_codec_data->sw_pix_fmt, av_get_pix_fmt_name(chosen_codec_data->sw_pix_fmt),
          chosen_codec_data->hw_pix_fmt, av_get_pix_fmt_name(chosen_codec_data->hw_pix_fmt),
          video_out_ctx->width, video_out_ctx->height
          );
      AVFrame *out_frame = zm_packet->get_out_frame(video_out_ctx->width, video_out_ctx->height, chosen_codec_data->sw_pix_fmt);
      if (!out_frame) {
        Error("Unable to allocate a frame");
        return 0;
      }

      if (zm_packet->image) {
        Debug(2, "Have an image, convert it");
        //Go straight to out frame
        swscale.Convert(
            zm_packet->image, 
            zm_packet->buffer,
            zm_packet->codec_imgsize,
            zm_packet->image->AVPixFormat(),
            chosen_codec_data->sw_pix_fmt,
            video_out_ctx->width,
            video_out_ctx->height
            );
      } else if (!zm_packet->in_frame) {
        Debug(4, "Have no in_frame");
        if (zm_packet->packet->size and !zm_packet->decoded) {
          Debug(4, "Decoding");
          if (!zm_packet->decode(video_in_ctx)) {
            Debug(2, "unable to decode yet.");
            return 0;
          }
          // Go straight to out frame
          swscale.Convert(zm_packet->in_frame, out_frame);
        } else {
          Error("Have neither in_frame or image in packet %d!",
              zm_packet->image_index);
          return 0;
        } // end if has packet or image
      } else {
        // Have in_frame.... may need to convert it to out_frame
        swscale.Convert(zm_packet->in_frame, zm_packet->out_frame);
      } // end if no in_frame
    } // end if no out_frame

    AVFrame *frame = zm_packet->out_frame;

#if HAVE_LIBAVUTIL_HWCONTEXT_H
    if (video_out_ctx->hw_frames_ctx) {
      int ret;
      if (!(hw_frame = av_frame_alloc())) {
        ret = AVERROR(ENOMEM);
        return ret;
      }
      if ((ret = av_hwframe_get_buffer(video_out_ctx->hw_frames_ctx, hw_frame, 0)) < 0) {
        Error("Error code: %s", av_err2str(ret));
        av_frame_free(&hw_frame);
        return ret;
      }
      if (!hw_frame->hw_frames_ctx) {
        Error("Outof ram!");
        av_frame_free(&hw_frame);
        return 0;
      }
      if ((ret = av_hwframe_transfer_data(hw_frame, zm_packet->out_frame, 0)) < 0) {
        Error("Error while transferring frame data to surface: %s.", av_err2str(ret));
        av_frame_free(&hw_frame);
        return ret;
      }

      frame = hw_frame;
    }  // end if hwaccel
#endif

    //zm_packet->out_frame->coded_picture_number = frame_count;
    //zm_packet->out_frame->display_picture_number = frame_count;
    //zm_packet->out_frame->sample_aspect_ratio = (AVRational){ 0, 1 };
    // Do this to allow the encoder to choose whether to use I/P/B frame
    //zm_packet->out_frame->pict_type = AV_PICTURE_TYPE_NONE;
    //zm_packet->out_frame->key_frame = zm_packet->keyframe;
#if LIBAVCODEC_VERSION_CHECK(60, 3, 0, 3, 0)
    frame->duration = 0;
#elif LIBAVCODEC_VERSION_CHECK(57, 64, 0, 64, 0)
    frame->pkt_duration = 0;
#endif

    //Setup to copy the pts from the incoming frame, instead of recclulating it.
    //int64_t in_pts = (zm_packet->timestamp.tv_sec * (uint64_t)1000000) + zm_packet->timestamp.tv_usec;
    int64_t in_pts = zm_packet->in_frame->pts;
    if (video_first_pts == AV_NOPTS_VALUE) {
      video_first_pts = in_pts;
      Debug(2, "No video_first_pts, set to (%" PRId64 ") secs(%" PRIi64 ") usecs(%" PRIi64 ")",
            video_first_pts,
            static_cast<int64>(zm_packet->timestamp.tv_sec),
            static_cast<int64>(zm_packet->timestamp.tv_usec));
      frame->pts = 0;
    } else {
      uint64_t useconds = in_pts - video_first_pts;
      // ensuring both the output stream and context have a properly scaled time_base based on the input stream, rather than the hardcoded default used earlier (AV_TIME_BASE_Q)
      frame->pts = av_rescale_q(useconds, video_in_stream->time_base, video_out_ctx->time_base);
      Debug(2,
            "Setting pts for frame(%d) to (%" PRId64 ") from (start %" PRIu64 " - %" PRIu64 " - secs(%" PRIi64 ") usecs(%" PRIi64 ") @ %d/%d",
            frame_count,
            frame->pts,
            video_first_pts,
            in_pts,
            static_cast<int64>(zm_packet->timestamp.tv_sec),
            static_cast<int64>(zm_packet->timestamp.tv_usec),
            video_out_ctx->time_base.num,
            video_out_ctx->time_base.den);
    }

    av_init_packet(opkt);
    opkt->data = nullptr;
    opkt->size = 0;

#if LIBAVCODEC_VERSION_CHECK(57, 64, 0, 64, 0)
    if (video_out_ctx->codec_id == AV_CODEC_ID_AV1) {
      /*
      Debug(1, "Entering NetInt Patched Code. RkR1");
      if (hw_frame)
      {
          int ref_count[4] = {0};
          ref_count[0] = hw_frame->buf[0] ? av_buffer_get_ref_count(hw_frame->buf[0]) : -1;
          ref_count[1] = hw_frame->buf[1] ? av_buffer_get_ref_count(hw_frame->buf[1]) : -1;
          ref_count[2] = hw_frame->buf[2] ? av_buffer_get_ref_count(hw_frame->buf[2]) : -1;
          ref_count[3] = hw_frame->buf[3] ? av_buffer_get_ref_count(hw_frame->buf[3]) : -1;
          Debug(1, "hw_frame: before zm_send_frame_internal ref_count %d %d %d %d",
              ref_count[0], ref_count[1], ref_count[2], ref_count[3]
              );
      }
      */
      int ret = zm_send_frame_internal(video_out_ctx, frame);
      if (ret <= 0) {
        if (ret < 0) {
          Error("Could not send frame (error '%s')", av_make_error_string(ret).c_str());
        }
        if (hw_frame) av_frame_free(&hw_frame);
        return ret;
      }
    
      // NETINT - add receive packets loop, similar to FFmpeg do_video_out
      while (1) {
        av_init_packet(opkt);
        opkt->data = nullptr;
        opkt->size = 0;
          
        int ret = zm_receive_packet_internal(video_out_ctx, frame, *opkt);
    
        if (ret <= 0) {
          if (ret < 0) {
            Error("Could not receive packet (error '%s')", av_make_error_string(ret).c_str());
          } 
          if (hw_frame)
          {
            /*
            int ref_count[4] = {0};
            ref_count[0] = hw_frame->buf[0] ? av_buffer_get_ref_count(hw_frame->buf[0]) : -1;
            ref_count[1] = hw_frame->buf[1] ? av_buffer_get_ref_count(hw_frame->buf[1]) : -1;
            ref_count[2] = hw_frame->buf[2] ? av_buffer_get_ref_count(hw_frame->buf[2]) : -1;
            ref_count[3] = hw_frame->buf[3] ? av_buffer_get_ref_count(hw_frame->buf[3]) : -1;
            Debug(1, "hw_frame: before av_frame_free ref_count %d %d %d %d",
                ref_count[0], ref_count[1], ref_count[2], ref_count[3]
                );  
            */
            av_frame_free(&hw_frame);
          } 
          return ret;
        }

 	ZM_DUMP_PACKET((*opkt), "packet returned by codec");

        // Need to adjust pts/dts values from codec time to stream time
        if (opkt->pts != AV_NOPTS_VALUE)
          opkt->pts = av_rescale_q(opkt->pts, video_out_ctx->time_base, video_out_stream->time_base);
        if (opkt->dts != AV_NOPTS_VALUE)
          opkt->dts = av_rescale_q(opkt->dts, video_out_ctx->time_base, video_out_stream->time_base);
        Debug(1, "Timebase conversions using %d/%d -> %d/%d",
            video_out_ctx->time_base.num,
            video_out_ctx->time_base.den,
            video_out_stream->time_base.num,
            video_out_stream->time_base.den);

        int64_t duration = 0;
        if (video_last_pts != AV_NOPTS_VALUE)
        {
          duration = opkt->pts - video_last_pts;
          Debug(1, "duration calc: pts(%" PRId64 ") - last_pts(%" PRId64 ") = (%" PRId64 ") => (%" PRId64 ")",
              opkt->pts,
              video_last_pts,
              opkt->pts - video_last_pts,
              duration
              );
          if (duration <= 0) {
            duration = av_rescale_q(1, video_in_stream->time_base, video_out_stream->time_base);
          }
        } else if (zm_packet->in_frame) {
          if (zm_packet->in_frame->pkt_duration) {
            duration = av_rescale_q(
                zm_packet->in_frame->pkt_duration,
                video_in_stream->time_base,
                video_out_stream->time_base);
            Debug(1, "duration from ipkt: pts(%" PRId64 ") = pkt_duration(%" PRId64 ") => (%" PRId64 ") (%d/%d) (%d/%d)",
                zm_packet->in_frame->pts,
                zm_packet->in_frame->pkt_duration,
                duration,
                video_in_stream->time_base.num,
                video_in_stream->time_base.den,
                video_out_stream->time_base.num,
                video_out_stream->time_base.den
                );
          }
        }
        video_last_pts = opkt->pts;
        opkt->duration = duration;

        write_packet(opkt, video_out_stream);
        zm_av_packet_unref(opkt);
      }
    }
    else
#endif
    {
      int ret = zm_send_frame_receive_packet(video_out_ctx, frame, *opkt);
      if (ret <= 0) {
        if (ret < 0) {
          Error("Could not send frame (error '%s')", av_make_error_string(ret).c_str());
        }
        if (hw_frame) av_frame_free(&hw_frame);
        return ret;
      }
      ZM_DUMP_PACKET((*opkt), "packet returned by codec");

      // Need to adjust pts/dts values from codec time to stream time
      if (opkt->pts != AV_NOPTS_VALUE)
        opkt->pts = av_rescale_q(opkt->pts, video_out_ctx->time_base, video_out_stream->time_base);
      if (opkt->dts != AV_NOPTS_VALUE)
        opkt->dts = av_rescale_q(opkt->dts, video_out_ctx->time_base, video_out_stream->time_base);
      Debug(1, "Timebase conversions using %d/%d -> %d/%d",
          video_out_ctx->time_base.num,
          video_out_ctx->time_base.den,
          video_out_stream->time_base.num,
          video_out_stream->time_base.den);

      int64_t duration = 0;
      if (zm_packet->in_frame) {
        if (zm_packet->in_frame->pkt_duration) {
          duration = av_rescale_q(
              zm_packet->in_frame->pkt_duration,
              video_in_stream->time_base,
              video_out_stream->time_base);
          Debug(1, "duration from ipkt: pts(%" PRId64 ") = pkt_duration(%" PRId64 ") => (%" PRId64 ") (%d/%d) (%d/%d)",
              zm_packet->in_frame->pts,
              zm_packet->in_frame->pkt_duration,
              duration,
              video_in_stream->time_base.num,
              video_in_stream->time_base.den,
              video_out_stream->time_base.num,
              video_out_stream->time_base.den
              );
        } else if (video_last_pts != AV_NOPTS_VALUE) {
          duration = av_rescale_q(
                zm_packet->in_frame->pts - video_last_pts,
                video_in_stream->time_base,
                video_out_stream->time_base);
          Debug(1, "duration calc: pts(%" PRId64 ") - last_pts(%" PRId64 ") = (%" PRId64 ") => (%" PRId64 ")",
              zm_packet->in_frame->pts,
              video_last_pts,
              zm_packet->in_frame->pts - video_last_pts,
              duration
              );
          if (duration <= 0) {
            duration = zm_packet->in_frame->pkt_duration ?
              zm_packet->in_frame->pkt_duration :
              av_rescale_q(1, video_in_stream->time_base, video_out_stream->time_base);
          }
        }  // end if in_frmae->pkt_duration
        video_last_pts = zm_packet->in_frame->pts;
      } else {
        //duration = av_rescale_q(zm_packet->out_frame->pts - video_last_pts, video_in_stream->time_base, video_out_stream->time_base);
      }  // end if in_frmae
      opkt->duration = duration;
    }
  } else { // Passthrough
    AVPacket *ipkt = zm_packet->packet.get();
    ZM_DUMP_STREAM_PACKET(video_in_stream, ipkt, "Doing passthrough, just copy packet");
    // Just copy it because the codec is the same
    av_packet_ref(opkt.get(), ipkt);
    pkt_guard.acquire(opkt);

    if (ipkt->dts != AV_NOPTS_VALUE) {
      if (video_first_dts == AV_NOPTS_VALUE) {
        Debug(2, "Starting video first_dts will become %" PRId64, ipkt->dts);
        video_first_dts = ipkt->dts;
      }
      opkt->dts = ipkt->dts - video_first_dts;
    //} else {
      //opkt.dts = next_dts[video_out_stream->index] ? av_rescale_q(next_dts[video_out_stream->index], video_out_stream->time_base, video_in_stream->time_base) : 0;
      //Debug(3, "Setting dts to video_next_dts %" PRId64 " from %" PRId64, opkt.dts, next_dts[video_out_stream->index]);
    }
    if ((ipkt->pts != AV_NOPTS_VALUE) and (video_first_dts != AV_NOPTS_VALUE)) {
      opkt->pts = ipkt->pts - video_first_dts;
      av_packet_rescale_ts(opkt.get(), video_in_stream->time_base, video_out_stream->time_base);
    }
  }  // end if codec matches

  write_packet(opkt.get(), video_out_stream);
  if (hw_frame) av_frame_free(&hw_frame);

  return 1;
}  // end int VideoStore::writeVideoFramePacket( AVPacket *ipkt )

int VideoStore::writeAudioFramePacket(const std::shared_ptr<ZMPacket> zm_packet) {
  AVPacket *ipkt = zm_packet->packet.get();
  int ret;
  ZM_DUMP_STREAM_PACKET(audio_in_stream, ipkt, "input packet");

  if (audio_first_dts == AV_NOPTS_VALUE) {
    audio_first_dts = ipkt->dts;
    audio_next_pts = audio_out_ctx->frame_size;
    Debug(3, "audio first_dts to %" PRId64, audio_first_dts);
  }

  // Need to adjust pts before feeding to decoder.... should really copy the pkt instead of modifying it

  if (audio_out_codec) {
    // I wonder if we can get multiple frames per packet? Probably
    ret = zm_send_packet_receive_frame(audio_in_ctx, in_frame, *ipkt);
    if (ret < 0) {
      Debug(3, "failed to receive frame code: %d", ret);
      return 0;
    }
    zm_dump_frame(in_frame, "In frame from decode");

    AVFrame *input_frame = in_frame;

    while (zm_resample_audio(resample_ctx, input_frame, out_frame)) {
      //out_frame->pkt_duration = in_frame->pkt_duration; // resampling doesn't alter duration
      if (zm_add_samples_to_fifo(fifo, out_frame) <= 0)
        break;

      // We put the samples into the fifo so we are basically resetting the frame
      out_frame->nb_samples = audio_out_ctx->frame_size;
      
      if (zm_get_samples_from_fifo(fifo, out_frame) <= 0)
        break;

      out_frame->pts = audio_next_pts;
      audio_next_pts += out_frame->nb_samples;

      zm_dump_frame(out_frame, "Out frame after resample");

      if (zm_send_frame_receive_packet(audio_out_ctx, out_frame, *opkt) <= 0)
        break;

      // Scale the PTS of the outgoing packet to be the correct time base
      av_packet_rescale_ts(opkt.get(),
          audio_out_ctx->time_base,
          audio_out_stream->time_base);

      write_packet(opkt.get(), audio_out_stream);
      zm_av_packet_unref(opkt.get());

      if (zm_resample_get_delay(resample_ctx, out_frame->sample_rate) < out_frame->nb_samples)
        break;
      // This will send a null frame, emptying out the resample buffer
      input_frame = nullptr;
    }  // end while there is data in the resampler
  } else {
    opkt->data = ipkt->data;
    opkt->size = ipkt->size;
    opkt->flags = ipkt->flags;
    opkt->duration = ipkt->duration;
    if (audio_first_dts != AV_NOPTS_VALUE) {
      opkt->pts = ipkt->pts - audio_first_dts;
      opkt->dts = ipkt->dts - audio_first_dts;
    } else {
      opkt->pts = ipkt->pts;
      opkt->dts = ipkt->dts;
    }

    ZM_DUMP_STREAM_PACKET(audio_in_stream, ipkt, "after pts adjustment");
    av_packet_rescale_ts(opkt.get(), audio_in_stream->time_base, audio_out_stream->time_base);
    ZM_DUMP_STREAM_PACKET(audio_out_stream, opkt, "after stream pts adjustment");
    write_packet(opkt.get(), audio_out_stream);

    zm_av_packet_unref(opkt.get());
  }  // end if encoding or copying

  return 0;
}  // end int VideoStore::writeAudioFramePacket(AVPacket *ipkt)

int VideoStore::write_packet(AVPacket *pkt, AVStream *stream) {
  pkt->pos = -1;
  pkt->stream_index = stream->index;

  if (pkt->dts == AV_NOPTS_VALUE) {
    if (last_dts[stream->index] == AV_NOPTS_VALUE) {
      last_dts[stream->index] = -1;
    } 
    pkt->dts = last_dts[stream->index];
  } else {
    if (last_dts[stream->index] != AV_NOPTS_VALUE) {
      if (pkt->dts < last_dts[stream->index]) {
        Warning("non increasing dts, fixing. our dts %" PRId64 " stream %d last_dts %" PRId64 " difference %" PRId64 " last_duration %" PRId64 ". reorder_queue_size=%zu",
            pkt->dts, stream->index, last_dts[stream->index], (last_dts[stream->index]-pkt->dts), last_duration[stream->index], reorder_queue_size);
        pkt->dts = last_dts[stream->index]+last_duration[stream->index];
        if (pkt->dts > pkt->pts) pkt->pts = pkt->dts; // Do it here to avoid warning below
      } else if (pkt->dts == last_dts[stream->index]) {
        // Commonly seen
        Debug(1, "non increasing dts, fixing. our dts %" PRId64 " stream %d last_dts %" PRId64 ". reorder_queue_size=%zu",
            pkt->dts, stream->index, last_dts[stream->index], reorder_queue_size);
        // dts MUST monotonically increase, so add 1 which should be a small enough time difference to not matter.
        pkt->dts = last_dts[stream->index]+last_duration[stream->index]-1;
        if (pkt->dts > pkt->pts) pkt->pts = pkt->dts; // Do it here to avoid warning below
      }
    }
    next_dts[stream->index] = pkt->dts + pkt->duration;
    last_dts[stream->index] = pkt->dts;
    last_duration[stream->index] = pkt->duration;
  }

  if (pkt->pts == AV_NOPTS_VALUE) {
    pkt->pts = pkt->dts;
  } else if (pkt->dts > pkt->pts) {
    Warning("pkt.dts(%" PRId64 ") must be <= pkt.pts(%" PRId64 ")."
            "Decompression must happen before presentation.",
            pkt->dts, pkt->pts);
/*    Debug(1,
          "pkt.dts(%" PRId64 ") must be <= pkt.pts(%" PRId64 ")."
          "Decompression must happen before presentation.",
          pkt->dts, pkt->pts);*/
    pkt->pts = pkt->dts;
  }

  ZM_DUMP_STREAM_PACKET(stream, pkt, "finished pkt");
  Debug(3, "next_dts for stream %d has become %" PRId64 " last_dts %" PRId64,
      stream->index, next_dts[stream->index], last_dts[stream->index]);

  int ret = av_interleaved_write_frame(oc, pkt);
  if (ret != 0) {
    Error("Error writing packet: %s", av_make_error_string(ret).c_str());
  } else {
    Debug(4, "Success writing packet");
  }
  return ret;
}  // end int VideoStore::write_packet(AVPacket *pkt, AVStream *stream)
