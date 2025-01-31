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
#include "zm_time.h"

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
  { AV_CODEC_ID_H265, "h265", "hevc_qsv", AV_PIX_FMT_YUV420P, AV_PIX_FMT_QSV, AV_HWDEVICE_TYPE_QSV },
  { AV_CODEC_ID_H265, "h265", "hevc_nvenc", AV_PIX_FMT_NV12, AV_PIX_FMT_NV12, AV_HWDEVICE_TYPE_NONE },
  { AV_CODEC_ID_H265, "h265", "libx265", AV_PIX_FMT_YUV420P, AV_PIX_FMT_YUV420P, AV_HWDEVICE_TYPE_NONE },

  { AV_CODEC_ID_H264, "h264", "h264_vaapi", AV_PIX_FMT_NV12, AV_PIX_FMT_VAAPI, AV_HWDEVICE_TYPE_VAAPI },
  { AV_CODEC_ID_H264, "h264", "h264_qsv", AV_PIX_FMT_YUV420P, AV_PIX_FMT_QSV, AV_HWDEVICE_TYPE_QSV },
  { AV_CODEC_ID_H264, "h264", "h264_nvenc", AV_PIX_FMT_NV12, AV_PIX_FMT_NV12, AV_HWDEVICE_TYPE_NONE },
  { AV_CODEC_ID_H264, "h264", "h264_omx", AV_PIX_FMT_YUV420P, AV_PIX_FMT_YUV420P,  AV_HWDEVICE_TYPE_NONE },
  { AV_CODEC_ID_H264, "h264", "h264_v4l2m2m", AV_PIX_FMT_YUV420P, AV_PIX_FMT_YUV420P,  AV_HWDEVICE_TYPE_NONE },
  { AV_CODEC_ID_H264, "h264", "h264", AV_PIX_FMT_YUV420P, AV_PIX_FMT_YUV420P,  AV_HWDEVICE_TYPE_NONE },
  { AV_CODEC_ID_H264, "h264", "libx264", AV_PIX_FMT_YUV420P, AV_PIX_FMT_YUV420P, AV_HWDEVICE_TYPE_NONE },
  { AV_CODEC_ID_MJPEG, "mjpeg", "mjpeg", AV_PIX_FMT_YUVJ422P, AV_PIX_FMT_YUVJ422P, AV_HWDEVICE_TYPE_NONE },
  { AV_CODEC_ID_VP9, "vp9", "libvpx-vp9", AV_PIX_FMT_YUV420P, AV_PIX_FMT_YUV420P, AV_HWDEVICE_TYPE_NONE },
  { AV_CODEC_ID_AV1, "av1", "libsvtav1", AV_PIX_FMT_YUV420P, AV_PIX_FMT_YUV420P, AV_HWDEVICE_TYPE_NONE },
  { AV_CODEC_ID_AV1, "av1", "libaom-av1", AV_PIX_FMT_YUV420P, AV_PIX_FMT_YUV420P, AV_HWDEVICE_TYPE_NONE },
  { AV_CODEC_ID_AV1, "av1", "av1_qsv", AV_PIX_FMT_YUV420P, AV_PIX_FMT_QSV, AV_HWDEVICE_TYPE_QSV },
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
  video_in_ctx(p_video_in_ctx),
  video_out_ctx(nullptr),
  video_in_stream(p_video_in_stream),
  audio_in_stream(p_audio_in_stream),
  audio_in_codec(nullptr),
  audio_in_ctx(nullptr),
  audio_out_codec(nullptr),
  audio_out_ctx(nullptr),
  packets_written(0),
  frame_count(0),
  hw_device_ctx(nullptr),
  resample_ctx(nullptr),
  fifo(nullptr),
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
  reorder_queue_size(0) {
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

  std::string options = monitor->GetEncoderOptions();
  AVDictionary *opts = nullptr;
  ret = av_dict_parse_string(&opts, options.c_str(), "=", "#,\n", 0);
  if (ret < 0) {
    Warning("Could not parse ffmpeg output options '%s'", options.c_str());
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
    } else if (monitor->has_out_of_order_packets()
        and !monitor->WallClockTimestamps()
        // Only sort packets for passthrough. Encoding uses wallclock by default
        and monitor->GetOptVideoWriter() == Monitor::PASSTHROUGH
        ) {
      reorder_queue_size = 2*monitor->get_max_keyframe_interval();
      Debug(1, "reorder_queue_size set to %zu", reorder_queue_size);
    }
  }

  oc->metadata = pmetadata;
  // Dirty hack to allow us to set flags. Needed for ffmpeg5
  out_format = const_cast<AVOutputFormat *>(oc->oformat);
  // ffmpeg 5 crashes if we do this
#if !LIBAVFORMAT_VERSION_CHECK(59, 16,100, 9, 0)
  out_format->flags |= AVFMT_TS_NONSTRICT; // allow non increasing dts
#endif

  const AVCodec *video_out_codec = nullptr;

  if (video_in_stream) {
    zm_dump_codecpar(video_in_stream->codecpar);

    if (monitor->GetOptVideoWriter() == Monitor::PASSTHROUGH) {
      video_out_stream = avformat_new_stream(oc, nullptr);
      if (!video_out_stream) {
        Error("Unable to create video out stream");
        return false;
      }
      avcodec_parameters_copy(video_out_stream->codecpar, video_in_stream->codecpar);
      zm_dump_codecpar(video_out_stream->codecpar);

      video_out_stream->avg_frame_rate = video_in_stream->avg_frame_rate;
      // Only set orientation if doing passthrough, otherwise the frame image will be rotated
      Monitor::Orientation orientation = monitor->getOrientation();
      if (orientation > 1) { // 1 is ROTATE_0
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

      if (av_dict_get(opts, "new_extradata", nullptr, AV_DICT_MATCH_CASE)) {
        av_dict_set(&opts, "new_extradata", nullptr, 0);
        // Special flag to tell us to open a codec to get new extraflags to fix weird h265
        video_out_codec = avcodec_find_encoder(video_in_stream->codecpar->codec_id);
        if (video_out_codec) {
          video_out_ctx = avcodec_alloc_context3(video_out_codec);
          ret = avcodec_parameters_to_context(video_out_ctx, video_in_stream->codecpar);

          if (ret < 0) {
            Error("Could not initialize ctx parameters");
            return false;
          }
          //video_out_ctx->pix_fmt = fix_deprecated_pix_fmt(video_out_ctx->pix_fmt);
          if (oc->oformat->flags & AVFMT_GLOBALHEADER) {
            video_out_ctx->flags |= AV_CODEC_FLAG_GLOBAL_HEADER;
          }
          video_out_ctx->time_base = video_in_ctx->time_base;
          if (!(video_out_ctx->time_base.num && video_out_ctx->time_base.den)) {
            Debug(2,"No timebase found in video in context, defaulting to Q");
            video_out_ctx->time_base = AV_TIME_BASE_Q;
          }
          video_out_ctx->bit_rate = video_in_ctx->bit_rate;
          video_out_ctx->gop_size = video_in_ctx->gop_size;
          video_out_ctx->has_b_frames = video_in_ctx->has_b_frames;
          video_out_ctx->max_b_frames = video_in_ctx->max_b_frames;
          video_out_ctx->qmin = video_in_ctx->qmin;
          video_out_ctx->qmax = video_in_ctx->qmax;

          if (!av_dict_get(opts, "crf", nullptr, AV_DICT_MATCH_CASE)) {
            if (av_dict_set(&opts, "crf", "23", 0)<0)
              Warning("Can't set crf to 23");
          }

          if ((ret = avcodec_open2(video_out_ctx, video_out_codec, &opts)) < 0) {
            Warning("Can't open video codec (%s) %s",
                    video_out_codec->name,
                    av_make_error_string(ret).c_str()
                   );
            video_out_codec = nullptr;
          }
        }  // end if video_out_codec

        ret = avcodec_parameters_from_context(video_out_stream->codecpar, video_out_ctx);
        if (ret < 0) {
          Error("Could not initialize stream parameters");
        }
        av_dict_free(&opts);
        // Reload it for next attempt and/or avformat open
        ret = av_dict_parse_string(&opts, options.c_str(), "=", "#,\n", 0);
        if (ret < 0) {
          Warning("Could not parse ffmpeg output options '%s'", options.c_str());
        } else {
          if (reorder_queue_size) {
            av_dict_set(&opts, "reorder_queue_size", nullptr, AV_DICT_MATCH_CASE);
          }
        }
      }  // end if extradata_entry
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
          video_out_ctx->flags |= AV_CODEC_FLAG_GLOBAL_HEADER;
        }

        // We have to re-parse the options because each attempt to open destroys the dictionary
        AVDictionary *opts = 0;
        ret = av_dict_parse_string(&opts, options.c_str(), "=", ",#\n", 0);
        if (ret < 0) {
          Warning("Could not parse ffmpeg encoder options list '%s'", options.c_str());
        } else {
          const AVDictionaryEntry *entry = av_dict_get(opts, "reorder_queue_size", nullptr, AV_DICT_MATCH_CASE);
          if (entry) {
            reorder_queue_size = std::stoul(entry->value);
            Debug(1, "reorder_queue_size set to %zu", reorder_queue_size);
            // remove it to prevent complaining later.
            av_dict_set(&opts, "reorder_queue_size", nullptr, AV_DICT_MATCH_CASE);
          }
        }
        // When encoding, we are going to use the timestamp values instead of packet pts/dts
        video_out_ctx->time_base = AV_TIME_BASE_Q;
        video_out_ctx->codec_id = codec_data[i].codec_id;
        video_out_ctx->pix_fmt = codec_data[i].hw_pix_fmt;
        Debug(1, "Setting pix fmt to %d %s", codec_data[i].hw_pix_fmt, av_get_pix_fmt_name(codec_data[i].hw_pix_fmt));
        const AVDictionaryEntry *opts_level = av_dict_get(opts, "level", nullptr, AV_DICT_MATCH_CASE);
        if (opts_level) {
          video_out_ctx->level = std::stoul(opts_level->value);
        } else {
          video_out_ctx->level = 32;
        }
        const AVDictionaryEntry *opts_gop_size = av_dict_get(opts, "gop_size", nullptr, AV_DICT_MATCH_CASE);
        if (opts_gop_size) {
          video_out_ctx->gop_size = std::stoul(opts_gop_size->value);
        } else {
          video_out_ctx->gop_size = 12;
        }

        // Don't have an input stream, so need to tell it what we are sending it, or are transcoding
        video_out_ctx->width = monitor->Width();
        video_out_ctx->height = monitor->Height();
        video_out_ctx->codec_type = AVMEDIA_TYPE_VIDEO;

        if (video_out_ctx->codec_id == AV_CODEC_ID_H264) {
          video_out_ctx->bit_rate = 2000000;
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
          ret = av_hwdevice_ctx_create(&hw_device_ctx,
                                       codec_data[i].hwdevice_type,
                                       nullptr, nullptr, 0);
          if (0>ret) {
            Error("Failed to create hwdevice_ctx %s", av_make_error_string(ret).c_str());
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
        av_dict_free(&opts);

        // Reload it for next attempt and/or avformat open
        ret = av_dict_parse_string(&opts, options.c_str(), "=", "#,\n", 0);
        if (ret < 0) {
          Warning("Could not parse ffmpeg output options '%s'", options.c_str());
        } else {
          if (reorder_queue_size) {
            av_dict_set(&opts, "reorder_queue_size", nullptr, AV_DICT_MATCH_CASE);
          }
        }

        if (video_out_codec) {
          break;
        }
        // We allocate and copy in newer ffmpeg, so need to free it
        avcodec_free_context(&video_out_ctx);
        if (hw_device_ctx) {
          av_buffer_unref(&hw_device_ctx);
        }
      }  // end foreach codec

      if (!video_out_codec) {
        Error("Can't open video codec!");
        return false;
      }  // end if can't open codec
      Debug(2, "Success opening codec");

      video_out_stream = avformat_new_stream(oc, nullptr);
      ret = avcodec_parameters_from_context(video_out_stream->codecpar, video_out_ctx);
      if (ret < 0) {
        Error("Could not initialize stream parameters");
        return false;
      }
    }  // end if copying or transcoding
  }  // end if video_in_stream

  max_stream_index = video_out_stream->index;
  last_dts[video_out_stream->index] = AV_NOPTS_VALUE;

  reorder_queues[video_out_stream->index] = {};

  video_out_stream->time_base = video_in_stream ? video_in_stream->time_base : AV_TIME_BASE_Q;

  if (audio_in_stream) {
    Debug(2, "Have audio_in_stream %p", audio_in_stream);

    if (CODEC(audio_in_stream)->codec_id != AV_CODEC_ID_AAC) {
      audio_out_codec = avcodec_find_encoder(AV_CODEC_ID_AAC);
      if (!audio_out_codec) {
        Error("Could not find codec for AAC");
      } else {
        // Newer ffmpeg wants to keep everything separate... so have to lookup our own
        // decoder, can't reuse the one from the camera.
        audio_in_codec = avcodec_find_decoder(audio_in_stream->codecpar->codec_id);
        audio_in_ctx = avcodec_alloc_context3(audio_in_codec);
        // Copy params from instream to ctx
        ret = avcodec_parameters_to_context(audio_in_ctx, audio_in_stream->codecpar);
        if (ret < 0) {
          Error("Unable to copy audio params to ctx %s", av_make_error_string(ret).c_str());
        }
        audio_in_ctx->time_base = audio_in_stream->time_base;

        // if the codec is already open, nothing is done.
        if ((ret = avcodec_open2(audio_in_ctx, audio_in_codec, nullptr)) < 0) {
          Error("Can't open audio in codec!");
          return false;
        }

        audio_out_ctx = avcodec_alloc_context3(audio_out_codec);
        if (!audio_out_ctx) {
          Error("could not allocate codec ctx for AAC");
          return false;
        }

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

      // Just use the ctx to copy the parameters over
      audio_out_ctx = avcodec_alloc_context3(audio_out_codec);
      if (!audio_out_ctx) {
        Error("Could not allocate new output_context");
        return false;
      }

      // Copy params from instream to ctx
      ret = avcodec_parameters_to_context(audio_out_ctx, audio_in_stream->codecpar);
      if (ret < 0) {
        Error("Unable to copy audio params to ctx %s", av_make_error_string(ret).c_str());
      }
      ret = avcodec_parameters_from_context(audio_out_stream->codecpar, audio_out_ctx);
      if (ret < 0) {
        Error("Unable to copy audio params to stream %s", av_make_error_string(ret).c_str());
      }

#if LIBAVUTIL_VERSION_CHECK(57, 28, 100, 28, 0)
      /* Seems like technically we could have multiple channels, so let's not implement this for ffmpeg 5 */
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
      audio_out_ctx->flags |= AV_CODEC_FLAG_GLOBAL_HEADER;
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
      Error("Could not open out file '%s': %s", filename, av_make_error_string(ret).c_str());
      return false;
    }
  }

  zm_dump_stream_format(oc, 0, 0, 1);
  if (audio_out_stream) zm_dump_stream_format(oc, 1, 0, 1);

  const AVDictionaryEntry *movflags_entry = av_dict_get(opts, "movflags", nullptr, AV_DICT_MATCH_CASE);
  if (!movflags_entry) {
    Debug(1, "setting movflags to frag_keyframe+empty_moov+faststart");
    // Shiboleth reports that this may break seeking in mp4 before it downloads
    av_dict_set(&opts, "movflags", "frag_keyframe+empty_moov+faststart", 0);
  } else {
    Debug(1, "using movflags %s", movflags_entry->value);
  }
  if ((ret = avformat_write_header(oc, &opts)) < 0) {
    // we crash if we try again
    if (ENOSPC != ret) {
      Warning("Unable to set movflags trying with defaults.%d %s",
              ret, av_make_error_string(ret).c_str());

      ret = avformat_write_header(oc, nullptr);
      Debug(1, "Done %d", ret);
    } else {
      Error("ENOSPC. fail");
    }
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
  av_dict_free(&opts);
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
  if (video_out_ctx && video_out_ctx->codec && (video_out_ctx->codec->capabilities & AV_CODEC_CAP_DELAY)) {
    // Put encoder into flushing mode
    while ((zm_send_frame_receive_packet(video_out_ctx, nullptr, *pkt)) > 0) {
      av_packet_guard pkt_guard{pkt};
      av_packet_rescale_ts(pkt.get(),
                           video_out_ctx->time_base,
                           video_out_stream->time_base);
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
      zm_resample_audio(resample_ctx, nullptr, out_frame.get());

      if (zm_add_samples_to_fifo(fifo, out_frame.get())) {
        // Should probably set the frame size to what is reported FIXME
        if (zm_get_samples_from_fifo(fifo, out_frame.get())) {
          if (zm_send_frame_receive_packet(audio_out_ctx, out_frame.get(), *pkt) > 0) {
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
        if (zm_send_frame_receive_packet(audio_out_ctx, out_frame.get(), *pkt)) {
          av_packet_guard pkt_guard{pkt};
          pkt->stream_index = audio_out_stream->index;

          av_packet_rescale_ts(pkt.get(),
                               audio_out_ctx->time_base,
                               audio_out_stream->time_base);
          write_packet(pkt.get(), audio_out_stream);
        }
      }  // end if data returned from fifo
    }  // end while still data in the fifo

    // Put encoder into flushing mode
    avcodec_send_frame(audio_out_ctx, nullptr);

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

  video_in_ctx = nullptr;

  if (video_out_ctx) {
    avcodec_close(video_out_ctx);
    Debug(3, "Freeing video_out_ctx");
    avcodec_free_context(&video_out_ctx);
    if (hw_device_ctx) {
      Debug(3, "Freeing hw_device_ctx");
      av_buffer_unref(&hw_device_ctx);
    }
  }

  if (audio_out_stream) {
    audio_in_codec = nullptr;
    if (audio_in_ctx) {
      avcodec_close(audio_in_ctx);
      avcodec_free_context(&audio_in_ctx);
    }

    if (audio_out_ctx) {
      Debug(4, "Success closing audio_out_ctx");
      avcodec_close(audio_out_ctx);
      avcodec_free_context(&audio_out_ctx);
    }

    if (resample_ctx) {
      if (fifo) {
        av_audio_fifo_free(fifo);
        fifo = nullptr;
      }
      swr_free(&resample_ctx);
    }
    if (converted_in_samples) {
      av_free(converted_in_samples);
      converted_in_samples = nullptr;
    }
  } // end if audio_out_stream

  Debug(4, "free context");
  /* free the streams */
  avformat_free_context(oc);
  delete[] next_dts;
  next_dts = nullptr;
} // VideoStore::~VideoStore()

bool VideoStore::setup_resampler() {
  int ret;


  Debug(2, "Got something other than AAC (%s)", audio_in_codec->name);

#if LIBAVUTIL_VERSION_CHECK(57, 28, 100, 28, 0)
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
#if LIBAVUTIL_VERSION_CHECK(57, 28, 100, 28, 0)
  av_channel_layout_copy(&audio_out_ctx->ch_layout, &audio_in_ctx->ch_layout);
#else
  audio_out_ctx->channels = audio_in_ctx->channels;
  audio_out_ctx->channel_layout = audio_in_ctx->channel_layout;
  if (!audio_out_ctx->channel_layout) {
    Debug(3, "Correcting channel layout from (%" PRIi64 ") to (%" PRIi64 ")",
          audio_out_ctx->channel_layout,
          av_get_default_channel_layout(audio_out_ctx->channels)
         );
    audio_out_ctx->channel_layout = av_get_default_channel_layout(audio_out_ctx->channels);
  }
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

  audio_out_stream->time_base = (AVRational) {1, audio_out_ctx->sample_rate};
  if ((ret = avcodec_parameters_from_context(audio_out_stream->codecpar, audio_out_ctx)) < 0) {
    Error("Could not initialize stream parameters");
    return false;
  }
  zm_dump_codecpar(audio_out_stream->codecpar);

  Debug(3,
        "Time bases: AUDIO in stream (%d/%d) in codec: (%d/%d) out "
        "stream: (%d/%d) out codec (%d/%d)",
        audio_in_stream->time_base.num, audio_in_stream->time_base.den,
        audio_in_ctx->time_base.num, audio_in_ctx->time_base.den,
        audio_out_stream->time_base.num, audio_out_stream->time_base.den,
        audio_out_ctx->time_base.num, audio_out_ctx->time_base.den);

#if LIBAVUTIL_VERSION_CHECK(57, 28, 100, 28, 0)
  Debug(1,
        "Audio in bit_rate (%" AV_PACKET_DURATION_FMT ") sample_rate(%d) channels(%d) fmt(%d) frame_size(%d)",
        audio_in_ctx->bit_rate, audio_in_ctx->sample_rate,
        audio_in_ctx->ch_layout.nb_channels,
        audio_in_ctx->sample_fmt,
        audio_in_ctx->frame_size);
#else
  Debug(1,
        "Audio in bit_rate (%" AV_PACKET_DURATION_FMT ") sample_rate(%d) channels(%d) fmt(%d) frame_size(%d)",
        audio_in_ctx->bit_rate, audio_in_ctx->sample_rate,
        audio_in_ctx->channels,
        audio_in_ctx->sample_fmt,
        audio_in_ctx->frame_size);
#endif
  Debug(1,
        "Audio out context bit_rate (%" AV_PACKET_DURATION_FMT ") sample_rate(%d) channels(%d) fmt(%d) frame_size(%d)",
        audio_out_ctx->bit_rate, audio_out_ctx->sample_rate,
#if LIBAVUTIL_VERSION_CHECK(57, 28, 100, 28, 0)
        audio_out_ctx->ch_layout.nb_channels,
#else
        audio_out_ctx->channels,
#endif
        audio_out_ctx->sample_fmt,
        audio_out_ctx->frame_size);

  Debug(1,
        "Audio out stream bit_rate (%" PRIi64 ") sample_rate(%d) channels(%d) fmt(%d) frame_size(%d)",
        audio_out_stream->codecpar->bit_rate, audio_out_stream->codecpar->sample_rate,
#if LIBAVUTIL_VERSION_CHECK(57, 28, 100, 28, 0)
        audio_out_stream->codecpar->ch_layout.nb_channels,
#else
        audio_out_stream->codecpar->channels,
#endif
        audio_out_stream->codecpar->format,
        audio_out_stream->codecpar->frame_size);

  /** Create a new frame to store the audio samples. */
  if (!in_frame) {
    if (!(in_frame = av_frame_ptr{zm_av_frame_alloc()})) {
      Error("Could not allocate in frame");
      return false;
    }
  }

  /** Create a new frame to store the audio samples. */
  if (!(out_frame = av_frame_ptr{zm_av_frame_alloc()})) {
    Error("Could not allocate out frame");
    return false;
  }
  out_frame->sample_rate = audio_out_ctx->sample_rate;

  if (!(fifo = av_audio_fifo_alloc(
                 audio_out_ctx->sample_fmt,
#if LIBAVUTIL_VERSION_CHECK(57, 28, 100, 28, 0)
                 audio_out_ctx->ch_layout.nb_channels
#else
                 audio_out_ctx->channels
#endif
                 , 1))) {
    Error("Could not allocate FIFO");
    return false;
  }
#if LIBAVUTIL_VERSION_CHECK(57, 28, 100, 28, 0)
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
    return false;
  }
#endif
  if ((ret = swr_init(resample_ctx)) < 0) {
    Error("Could not open resampler %d", ret);
    swr_free(&resample_ctx);
    return false;
  }
  Debug(1,"Success setting up SWRESAMPLE");

  out_frame->nb_samples = audio_out_ctx->frame_size;
  out_frame->format = audio_out_ctx->sample_fmt;
#if LIBAVUTIL_VERSION_CHECK(57, 28, 100, 28, 0)
  out_frame->ch_layout = audio_out_ctx->ch_layout,
#else
  out_frame->channels = audio_out_ctx->channels;
  out_frame->channel_layout = audio_out_ctx->channel_layout;
#endif
             out_frame->sample_rate = audio_out_ctx->sample_rate;

  // The codec gives us the frame size, in samples, we calculate the size of the
  // samples buffer in bytes
  unsigned int audioSampleBuffer_size = av_samples_get_buffer_size(
                                          nullptr,
#if LIBAVUTIL_VERSION_CHECK(57, 28, 100, 28, 0)
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
        out_frame.get(),
#if LIBAVUTIL_VERSION_CHECK(57, 28, 100, 28, 0)
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
    if (rit == queue.rend()) {
      Debug(1, "Unable to re-order packet, packet dts is %" PRId64, av_pkt->dts);
    } else {
      AVPacket *p = ((*rit)->packet).get();
      Debug(1, "Found out of order packet, inserting after %" PRId64, p->dts);
    }
    queue.insert(rit.base(), zm_pkt);
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
#if HAVE_LIBAVUTIL_HWCONTEXT_H
  av_frame_ptr hw_frame;
#endif

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
          zm_packet->out_frame->buf[0]->data,
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
          swscale.Convert(zm_packet->in_frame.get(), out_frame);
        } else {
          Error("Have neither in_frame or image in packet %d!",
                zm_packet->image_index);
          return 0;
        } // end if has packet or image
      } else {
        // Have in_frame.... may need to convert it to out_frame
        swscale.Convert(zm_packet->in_frame.get(), zm_packet->out_frame.get());
      } // end if no in_frame
    } // end if no out_frame

    AVFrame *frame = zm_packet->out_frame.get();

#if HAVE_LIBAVUTIL_HWCONTEXT_H
    if (video_out_ctx->hw_frames_ctx) {
      int ret;
      hw_frame = av_frame_ptr{zm_av_frame_alloc()};
      if (!hw_frame) {
        return AVERROR(ENOMEM);
      }
      if ((ret = av_hwframe_get_buffer(video_out_ctx->hw_frames_ctx, hw_frame.get(), 0)) < 0) {
        Error("Error code: %s", av_err2str(ret));
        return ret;
      }
      if (!hw_frame->hw_frames_ctx) {
        Error("Outof ram!");
        return 0;
      }
      if ((ret = av_hwframe_transfer_data(hw_frame.get(), zm_packet->out_frame.get(), 0)) < 0) {
        Error("Error while transferring frame data to surface: %s.", av_err2str(ret));
        return ret;
      }

      frame = hw_frame.get();
    }  // end if hwaccel
#endif

    //zm_packet->out_frame->coded_picture_number = frame_count;
    //zm_packet->out_frame->display_picture_number = frame_count;
    //zm_packet->out_frame->sample_aspect_ratio = (AVRational){ 0, 1 };
    // Do this to allow the encoder to choose whether to use I/P/B frame
    //zm_packet->out_frame->pict_type = AV_PICTURE_TYPE_NONE;
    //zm_packet->out_frame->key_frame = zm_packet->keyframe;
    //frame->pkt_duration = 0;

    if (video_first_pts == AV_NOPTS_VALUE) {
      video_first_pts = static_cast<int64>(std::chrono::duration_cast<Microseconds>(zm_packet->timestamp.time_since_epoch()).count());
      Debug(2, "No video_first_pts, set to (%" PRId64 ") secs(%.2f)",
            video_first_pts,
            FPSeconds(zm_packet->timestamp.time_since_epoch()).count());

      frame->pts = 0;
    } else {

      Microseconds useconds = std::chrono::duration_cast<Microseconds>(
                                zm_packet->timestamp - SystemTimePoint(Microseconds(video_first_pts)));
      frame->pts = av_rescale_q(useconds.count(), AV_TIME_BASE_Q, video_out_ctx->time_base);
      Debug(2,
            "Setting pts for frame(%d) to (%" PRId64 ") from (zm_packet->timestamp(%" PRIi64 " - first %" PRId64 " us %" PRId64 " ) @ %d/%d",
            frame_count,
            frame->pts,
            static_cast<int64>(std::chrono::duration_cast<Microseconds>(zm_packet->timestamp.time_since_epoch()).count()),
            video_first_pts,
            static_cast<int64>(std::chrono::duration_cast<Microseconds>(useconds).count()),
            video_out_ctx->time_base.num,
            video_out_ctx->time_base.den);
    }

    int ret = zm_send_frame_receive_packet(video_out_ctx, frame, *opkt);
    if (ret <= 0) {
      if (ret < 0) {
        Error("Could not send frame (error '%s')", av_make_error_string(ret).c_str());
      }
      return ret;
    }
    pkt_guard.acquire(opkt);
    ZM_DUMP_PACKET(opkt, "packet returned by codec");

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

      if (
#if LIBAVCODEC_VERSION_CHECK(60, 3, 0, 3, 0)
        zm_packet->in_frame->duration
#else
        zm_packet->in_frame->pkt_duration
#endif
      ) {
        duration = av_rescale_q(
#if LIBAVCODEC_VERSION_CHECK(60, 3, 0, 3, 0)
                     zm_packet->in_frame->duration,
#else
                     zm_packet->in_frame->pkt_duration,
#endif
                     video_in_stream->time_base,
                     video_out_stream->time_base);
        Debug(1, "duration from ipkt: = duration(%" PRId64 ") => (%" PRId64 ") (%d/%d) (%d/%d)",
#if LIBAVCODEC_VERSION_CHECK(60, 3, 0, 3, 0)
              zm_packet->in_frame->duration,
#else
              zm_packet->in_frame->pkt_duration,
#endif
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
#if LIBAVCODEC_VERSION_CHECK(60, 3, 0, 3, 0)
          duration = zm_packet->in_frame->duration ?
                     zm_packet->in_frame->duration :
                     av_rescale_q(1, video_in_stream->time_base, video_out_stream->time_base);
#else
          duration = zm_packet->in_frame->pkt_duration ?
                     zm_packet->in_frame->pkt_duration :
                     av_rescale_q(1, video_in_stream->time_base, video_out_stream->time_base);
#endif
        }
      }  // end if in_frmae->pkt_duration
      video_last_pts = zm_packet->in_frame->pts;
    } else {
      //duration = av_rescale_q(zm_packet->out_frame->pts - video_last_pts, video_in_stream->time_base, video_out_stream->time_base);
    }  // end if in_frmae
    opkt->duration = duration;
  } else { // Passthrough
    AVPacket *ipkt = zm_packet->packet.get();
    ZM_DUMP_STREAM_PACKET(video_in_stream, ipkt, "Doing passthrough, just copy packet");
    // Just copy it because the codec is the same
    av_packet_ref(opkt.get(), ipkt);
    pkt_guard.acquire(opkt);

    if (monitor->WallClockTimestamps()) {
      int64_t ts = static_cast<int64>(std::chrono::duration_cast<Microseconds>(zm_packet->timestamp.time_since_epoch()).count());
      if (video_first_dts == AV_NOPTS_VALUE) {
        Debug(2, "Starting video first_dts will become %" PRId64, ts);
        video_first_dts = ts;
      }
      opkt->pts = opkt->dts = av_rescale_q(ts-video_first_dts, AV_TIME_BASE_Q, video_out_stream->time_base);

      Debug(2, "dts from timestamp, set to (%" PRId64 ") secs(%.2f), minus first_dts %" PRId64 " = %" PRId64,
          ts,
          FPSeconds(zm_packet->timestamp.time_since_epoch()).count(),
          video_first_dts, ts - video_first_dts);
    } else {
      if (ipkt->dts != AV_NOPTS_VALUE) {
        if (video_first_dts == AV_NOPTS_VALUE) {
          Debug(2, "Starting video first_dts will become %" PRId64, ipkt->dts);
          video_first_dts = ipkt->dts;
        }
        opkt->dts = ipkt->dts - video_first_dts;
      }
      if (ipkt->pts != AV_NOPTS_VALUE) {
        opkt->pts = ipkt->pts - video_first_dts;
      }
      if ((ipkt->pts != AV_NOPTS_VALUE) and (ipkt->dts != AV_NOPTS_VALUE)) {
        av_packet_rescale_ts(opkt.get(), video_in_stream->time_base, video_out_stream->time_base);
      }
    }  // end if wallclock or not
  }  // end if codec matches

  write_packet(opkt.get(), video_out_stream);

  return 1;
}  // end int VideoStore::writeVideoFramePacket( AVPacket *ipkt )

int VideoStore::writeAudioFramePacket(const std::shared_ptr<ZMPacket> zm_packet) {
  AVPacket *ipkt = zm_packet->packet.get();
  ZM_DUMP_STREAM_PACKET(audio_in_stream, ipkt, "input packet");

  if (monitor->WallClockTimestamps()) {
    int64_t ts = static_cast<int64>(std::chrono::duration_cast<Microseconds>(zm_packet->timestamp.time_since_epoch()).count());
    ipkt->pts = ipkt->dts = av_rescale_q(ts, AV_TIME_BASE_Q, audio_in_stream->time_base);

    Debug(2, "dts %" PRId64 " from timestamp %" PRId64 " secs(%.2f)",
        ipkt->dts, ts, FPSeconds(zm_packet->timestamp.time_since_epoch()).count());
  }

  if (audio_first_dts == AV_NOPTS_VALUE) {
    audio_first_dts = ipkt->dts;
    audio_next_pts = audio_out_ctx->frame_size;
    Debug(3, "audio first_dts to %" PRId64, audio_first_dts);
  }

  // Need to adjust pts before feeding to decoder.... should really copy the pkt instead of modifying it

  if (audio_out_codec) {
    // I wonder if we can get multiple frames per packet? Probably
    int ret = zm_send_packet_receive_frame(audio_in_ctx, in_frame.get(), *ipkt);
    if (ret < 0) {
      Debug(3, "failed to receive frame code: %d", ret);
      return 0;
    }
    zm_dump_frame(in_frame, "In frame from decode");

    AVFrame *input_frame = in_frame.get();

    while (zm_resample_audio(resample_ctx, input_frame, out_frame.get())) {
      //out_frame->pkt_duration = in_frame->pkt_duration; // resampling doesn't alter duration
      if (zm_add_samples_to_fifo(fifo, out_frame.get()) <= 0)
        break;

      // We put the samples into the fifo so we are basically resetting the frame
      out_frame->nb_samples = audio_out_ctx->frame_size;

      if (zm_get_samples_from_fifo(fifo, out_frame.get()) <= 0)
        break;

      out_frame->pts = audio_next_pts;
      audio_next_pts += out_frame->nb_samples;

      zm_dump_frame(out_frame, "Out frame after resample");

      if (zm_send_frame_receive_packet(audio_out_ctx, out_frame.get(), *opkt) <= 0)
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

    ZM_DUMP_STREAM_PACKET(audio_in_stream, opkt, "after pts adjustment");
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
    Debug(1, "undef dts, fixing by setting to stream last_dts %" PRId64, last_dts[stream->index]);
    if (last_dts[stream->index] == AV_NOPTS_VALUE) {
      last_dts[stream->index] = -1;
    } 
    pkt->dts = last_dts[stream->index];
  } else {
    if (last_dts[stream->index] != AV_NOPTS_VALUE) {
      if (pkt->dts < last_dts[stream->index]) {
        Warning("non increasing dts, fixing. our dts %" PRId64 " stream %d last_dts %" PRId64 " last_duration %" PRId64 ". reorder_queue_size=%zu",
            pkt->dts, stream->index, last_dts[stream->index], last_duration[stream->index], reorder_queue_size);
        pkt->dts = last_dts[stream->index]+last_duration[stream->index];
        if (pkt->dts > pkt->pts) pkt->pts = pkt->dts; // Do it here to avoid warning below
      } else if (pkt->dts == last_dts[stream->index]) {
        // Commonly seen
        Debug(1, "non increasing dts, fixing. our dts %" PRId64 " stream %d last_dts %" PRId64 " stream %d. reorder_queue_size=%zu",
            pkt->dts, stream->index, last_dts[stream->index], stream->index, reorder_queue_size);
        // dts MUST monotonically increase, so add 1 which should be a small enough time difference to not matter.
        pkt->dts = last_dts[stream->index]+last_duration[stream->index];
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
