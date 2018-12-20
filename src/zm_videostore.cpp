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

#define __STDC_FORMAT_MACROS 1

#include <cinttypes>
#include <stdlib.h>
#include <string.h>

#include "zm.h"
#include "zm_videostore.h"

extern "C" {
#include "libavutil/time.h"
}

VideoStore::VideoStore(const char *filename_in, const char *format_in,
                       AVStream *p_video_in_stream,
                       AVStream *p_audio_in_stream, int64_t nStartTime,
                       Monitor *monitor) {
  video_in_stream = p_video_in_stream;
  audio_in_stream = p_audio_in_stream;

#if LIBAVCODEC_VERSION_CHECK(57, 64, 0, 64, 0)
  video_in_ctx = avcodec_alloc_context3(NULL);
  avcodec_parameters_to_context(video_in_ctx,
                                video_in_stream->codecpar);
// zm_dump_codecpar( video_in_stream->codecpar );
#else
  video_in_ctx = video_in_stream->codec;
#endif

  // store ins in variables local to class
  filename = filename_in;
  format = format_in;

  Info("Opening video storage stream %s format: %s", filename, format);

  ret = avformat_alloc_output_context2(&oc, NULL, NULL, filename);
  if (ret < 0) {
    Warning(
        "Could not create video storage stream %s as no out ctx"
        " could be assigned based on filename: %s",
        filename, av_make_error_string(ret).c_str());
  } else {
    Debug(4, "Success allocating out format ctx");
  }

  // Couldn't deduce format from filename, trying from format name
  if ( !oc ) {
    avformat_alloc_output_context2(&oc, NULL, format, filename);
    if (!oc) {
      Error(
          "Could not create video storage stream %s as no out ctx"
          " could not be assigned based on filename or format %s",
          filename, format);
      return;
    } else {
      Debug(4, "Success alocateing out ctx");
    }
  }  // end if ! oc

  AVDictionary *pmetadata = NULL;
  int dsr =
      av_dict_set(&pmetadata, "title", "Zoneminder Security Recording", 0);
  if (dsr < 0) Warning("%s:%d: title set failed", __FILE__, __LINE__);

  oc->metadata = pmetadata;
  out_format = oc->oformat;

#if LIBAVCODEC_VERSION_CHECK(57, 64, 0, 64, 0)

  // Since we are not re-encoding, all we have to do is copy the parameters
  video_out_ctx = avcodec_alloc_context3(NULL);

  // Copy params from instream to ctx
  ret = avcodec_parameters_to_context(video_out_ctx,
                                      video_in_stream->codecpar);
  if (ret < 0) {
    Error("Could not initialize ctx parameteres");
    return;
  } else {
    zm_dump_codec(video_out_ctx);
  }

  video_out_stream = avformat_new_stream(oc, NULL);
  if (!video_out_stream) {
    Error("Unable to create video out stream\n");
    return;
  } else {
    Debug(2, "Success creating video out stream");
  }

  if ( !video_out_ctx->codec_tag ) {
    video_out_ctx->codec_tag =
        av_codec_get_tag(oc->oformat->codec_tag, video_in_ctx->codec_id);
    Debug(2, "No codec_tag, setting to %d", video_out_ctx->codec_tag);
  }

  // Now copy them to the out stream
  ret = avcodec_parameters_from_context(video_out_stream->codecpar,
                                        video_out_ctx);
  if (ret < 0) {
    Error("Could not initialize stream parameteres");
    return;
  } else {
    Debug(2, "Success setting parameters");
  }
  zm_dump_codecpar(video_out_stream->codecpar);

#else
  video_out_stream =
      avformat_new_stream(oc, NULL);
//(AVCodec *)(video_in_ctx->codec));
      //avformat_new_stream(oc,(const AVCodec *)(video_in_ctx->codec));
  if ( !video_out_stream ) {
    Fatal("Unable to create video out stream\n");
  } else {
    Debug(2, "Success creating video out stream");
  }
  video_out_ctx = video_out_stream->codec;
  ret = avcodec_copy_context(video_out_ctx, video_in_ctx);
  if (ret < 0) {
    Fatal("Unable to copy in video ctx to out video ctx %s\n",
          av_make_error_string(ret).c_str());
  } else {
    Debug(3, "Success copying ctx");
  }
  if (!video_out_ctx->codec_tag) {
    Debug(2, "No codec_tag");
    if (!oc->oformat->codec_tag ||
        av_codec_get_id(oc->oformat->codec_tag,
                        video_in_ctx->codec_tag) ==
            video_out_ctx->codec_id ||
        av_codec_get_tag(oc->oformat->codec_tag,
                         video_in_ctx->codec_id) <= 0) {
      Warning("Setting codec tag");
      video_out_ctx->codec_tag = video_in_ctx->codec_tag;
    }
  }
#endif

  // Just copy them from the in, no reason to choose different
  video_out_ctx->time_base = video_in_ctx->time_base;
  if ( ! (video_out_ctx->time_base.num && video_out_ctx->time_base.den) ) {
	  video_out_ctx->time_base = AV_TIME_BASE_Q;
  }	
  video_out_stream->time_base = video_in_stream->time_base;

  Debug(3,
        "Time bases: VIDEO in stream (%d/%d) in codec: (%d/%d) out "
        "stream: (%d/%d) out codec (%d/%d)",
        video_in_stream->time_base.num, video_in_stream->time_base.den,
        video_in_ctx->time_base.num, video_in_ctx->time_base.den,
        video_out_stream->time_base.num, video_out_stream->time_base.den,
        video_out_ctx->time_base.num,
        video_out_ctx->time_base.den);

  if (oc->oformat->flags & AVFMT_GLOBALHEADER) {
#if LIBAVCODEC_VERSION_CHECK(56, 35, 0, 64, 0)
    video_out_ctx->flags |= AV_CODEC_FLAG_GLOBAL_HEADER;
#else
    video_out_ctx->flags |= CODEC_FLAG_GLOBAL_HEADER;
#endif
  }

  Monitor::Orientation orientation = monitor->getOrientation();
  if (orientation) {
    if (orientation == Monitor::ROTATE_0) {
    } else if (orientation == Monitor::ROTATE_90) {
      dsr = av_dict_set(&video_out_stream->metadata, "rotate", "90", 0);
      if (dsr < 0) Warning("%s:%d: title set failed", __FILE__, __LINE__);
    } else if (orientation == Monitor::ROTATE_180) {
      dsr = av_dict_set(&video_out_stream->metadata, "rotate", "180", 0);
      if (dsr < 0) Warning("%s:%d: title set failed", __FILE__, __LINE__);
    } else if (orientation == Monitor::ROTATE_270) {
      dsr = av_dict_set(&video_out_stream->metadata, "rotate", "270", 0);
      if (dsr < 0) Warning("%s:%d: title set failed", __FILE__, __LINE__);
    } else {
      Warning("Unsupported Orientation(%d)", orientation);
    }
  }

  converted_in_samples = NULL;
  audio_out_codec = NULL;
  audio_in_codec = NULL;
  audio_in_ctx = NULL;
  audio_out_stream = NULL;
  in_frame = NULL;
  out_frame = NULL;
#if defined(HAVE_LIBSWRESAMPLE) || defined(HAVE_LIBAVRESAMPLE)
  resample_ctx = NULL;
#endif

  if (audio_in_stream) {
    Debug(3, "Have audio stream");
#if LIBAVCODEC_VERSION_CHECK(57, 64, 0, 64, 0)

    audio_in_ctx = avcodec_alloc_context3(NULL);
    ret = avcodec_parameters_to_context(audio_in_ctx,
                                        audio_in_stream->codecpar);
#else
    audio_in_ctx = audio_in_stream->codec;
#endif

    if ( audio_in_ctx->codec_id != AV_CODEC_ID_AAC ) {
      static char error_buffer[256];
      avcodec_string(error_buffer, sizeof(error_buffer), audio_in_ctx, 0);
      Debug(2, "Got something other than AAC (%s)", error_buffer);

      if (!setup_resampler()) {
        return;
      }
    } else {
      Debug(3, "Got AAC");

      audio_out_stream =
#if LIBAVCODEC_VERSION_CHECK(57, 64, 0, 64, 0)
          avformat_new_stream(oc, (const AVCodec *)(audio_in_ctx->codec));
#else
          avformat_new_stream(oc, (AVCodec *)audio_in_ctx->codec);
#endif
      if ( !audio_out_stream ) {
        Error("Unable to create audio out stream");
        audio_out_stream = NULL;
      } else {
#if LIBAVCODEC_VERSION_CHECK(57, 64, 0, 64, 0)
        audio_out_ctx = avcodec_alloc_context3(audio_out_codec);
        // Copy params from instream to ctx
        ret = avcodec_parameters_to_context(audio_out_ctx,
                                            audio_in_stream->codecpar);
        if ( ret < 0 ) {
          Error("Unable to copy audio params to ctx %s",
                av_make_error_string(ret).c_str());
        }
        ret = avcodec_parameters_from_context(audio_out_stream->codecpar,
                                              audio_out_ctx);
        if ( ret < 0 ) {
          Error("Unable to copy audio params to stream %s",
                av_make_error_string(ret).c_str());
        }

        if ( !audio_out_ctx->codec_tag ) {
          audio_out_ctx->codec_tag = av_codec_get_tag(
              oc->oformat->codec_tag, audio_in_ctx->codec_id);
          Debug(2, "Setting audio codec tag to %d",
                audio_out_ctx->codec_tag);
        }
#else
        audio_out_ctx = audio_out_stream->codec;
        ret = avcodec_copy_context(audio_out_ctx, audio_in_ctx);
        audio_out_ctx->codec_tag = 0;
#endif
        if ( ret < 0 ) {
          Error("Unable to copy audio ctx %s",
                av_make_error_string(ret).c_str());
          audio_out_stream = NULL;
        } else {
          if ( audio_out_ctx->channels > 1 ) {
            Warning("Audio isn't mono, changing it.");
            audio_out_ctx->channels = 1;
          } else {
            Debug(3, "Audio is mono");
          }
        }
      }  // end if audio_out_stream
    }    // end if is AAC

    if ( audio_out_stream ) {
      if ( oc->oformat->flags & AVFMT_GLOBALHEADER ) {
#if LIBAVCODEC_VERSION_CHECK(56, 35, 0, 64, 0)
    audio_out_ctx->flags |= AV_CODEC_FLAG_GLOBAL_HEADER;
#else
    audio_out_ctx->flags |= CODEC_FLAG_GLOBAL_HEADER;
#endif
      }
    }
  }  // end if audio_in_stream

  video_last_pts = 0;
  video_last_dts = 0;
  audio_last_pts = 0;
  audio_last_dts = 0;
  video_next_pts = 0;
  video_next_dts = 0;
  audio_next_pts = 0;
  audio_next_dts = 0;
}  // VideoStore::VideoStore

bool VideoStore::open() {
  /* open the out file, if needed */
  if ( !(out_format->flags & AVFMT_NOFILE) ) {
    ret = avio_open2(&oc->pb, filename, AVIO_FLAG_WRITE, NULL, NULL);
    if ( ret < 0 ) {
      Error("Could not open out file '%s': %s\n", filename,
            av_make_error_string(ret).c_str());
      return false;
    }
  }

  // os->ctx_inited = 1;
  // avio_flush(ctx->pb);
  // av_dict_free(&opts);
  zm_dump_stream_format(oc, 0, 0, 1);
  if (audio_out_stream) zm_dump_stream_format(oc, 1, 0, 1);

  AVDictionary *opts = NULL;
  // av_dict_set(&opts, "movflags", "frag_custom+dash+delay_moov", 0);
  // av_dict_set(&opts, "movflags", "frag_custom+dash+delay_moov", 0);
  // av_dict_set(&opts, "movflags",
  // "frag_keyframe+empty_moov+default_base_moof", 0);
  if ((ret = avformat_write_header(oc, &opts)) < 0) {
    // if ((ret = avformat_write_header(oc, &opts)) < 0) {
    Warning("Unable to set movflags to frag_custom+dash+delay_moov");
    /* Write the stream header, if any. */
    ret = avformat_write_header(oc, NULL);
  } else if (av_dict_count(opts) != 0) {
    Warning("some options not set\n");
  }
  if ( opts ) av_dict_free(&opts);
  if ( ret < 0 ) {
    Error("Error occurred when writing out file header to %s: %s",
          filename, av_make_error_string(ret).c_str());
    /* free the stream */
    avio_closep(&oc->pb);
    //avformat_free_context(oc);
    return false;
  }
  return true;
} // end VideoStore::open()

VideoStore::~VideoStore() {

  if ( oc->pb ) {

    if (audio_out_codec) {
      // The codec queues data.  We need to send a flush command and out
      // whatever we get. Failures are not fatal.
      AVPacket pkt;
      // Without these we seg fault I don't know why.
      pkt.data = NULL;
      pkt.size = 0;
      av_init_packet(&pkt);

      while (1) {
#if LIBAVCODEC_VERSION_CHECK(57, 64, 0, 64, 0)
        // Put encoder into flushing mode
        avcodec_send_frame(audio_out_ctx, NULL);
        ret = avcodec_receive_packet(audio_out_ctx, &pkt);
        if ( ret < 0 ) {
          if ( AVERROR_EOF != ret ) {
            Error("ERror encoding audio while flushing (%d) (%s)", ret,
                av_err2str(ret));
          }
          break;
        }
#else
        int got_packet = 0;
        ret =
          avcodec_encode_audio2(audio_out_ctx, &pkt, NULL, &got_packet);
        if ( ret < 0 ) {
          Error("ERror encoding audio while flushing (%d) (%s)", ret,
              av_err2str(ret));
          break;
        }
        Debug(1, "Have audio encoder, need to flush it's out");
        if ( !got_packet ) {
          break;
        }
#endif
        Debug(2, "writing flushed packet pts(%d) dts(%d) duration(%d)", pkt.pts,
            pkt.dts, pkt.duration);
        pkt.pts = audio_next_pts;
        pkt.dts = audio_next_dts;

        if ( pkt.duration > 0 )
          pkt.duration =
            av_rescale_q(pkt.duration, audio_out_ctx->time_base,
                audio_out_stream->time_base);
        audio_next_pts += pkt.duration;
        audio_next_dts += pkt.duration;

        Debug(2, "writing flushed packet pts(%d) dts(%d) duration(%d)", pkt.pts,
            pkt.dts, pkt.duration);
        pkt.stream_index = audio_out_stream->index;
        av_interleaved_write_frame(oc, &pkt);
        zm_av_packet_unref(&pkt);
      } // while have buffered frames
    } // end if audio_out_codec

    // Flush Queues
    Debug(1,"Flushing interleaved queues");
    av_interleaved_write_frame(oc, NULL);

    Debug(1,"Writing trailer");
    /* Write the trailer before close */
    if (int rc = av_write_trailer(oc)) {
      Error("Error writing trailer %s", av_err2str(rc));
    } else {
      Debug(3, "Success Writing trailer");
    }

    // When will we not be using a file ?
    if ( !(out_format->flags & AVFMT_NOFILE) ) {
      /* Close the out file. */
      Debug(2, "Closing");
      if (int rc = avio_close(oc->pb)) {
        oc->pb = NULL;
        Error("Error closing avio %s", av_err2str(rc));
      }
    } else {
      Debug(3, "Not closing avio because we are not writing to a file.");
    }
  } // end if ( oc->pb )
  // I wonder if we should be closing the file first.
  // I also wonder if we really need to be doing all the ctx
  // allocation/de-allocation constantly, or whether we can just re-use it.
  // Just do a file open/close/writeheader/etc.
  // What if we were only doing audio recording?
  if ( video_out_stream ) {
#if LIBAVCODEC_VERSION_CHECK(57, 64, 0, 64, 0)
    // We allocate and copy in newer ffmpeg, so need to free it
    avcodec_free_context(&video_in_ctx);
#endif
    video_in_ctx = NULL;

    avcodec_close(video_out_ctx);
#if LIBAVCODEC_VERSION_CHECK(57, 64, 0, 64, 0)
    avcodec_free_context(&video_out_ctx);
#endif
    video_out_ctx = NULL;
    Debug(4, "Success freeing video_out_ctx");
  }
  if ( audio_out_stream ) {
    if ( audio_in_codec ) {
      avcodec_close(audio_in_ctx);
#if LIBAVCODEC_VERSION_CHECK(57, 64, 0, 64, 0)
      // We allocate and copy in newer ffmpeg, so need to free it
      avcodec_free_context(&audio_in_ctx);
#endif
      audio_in_ctx = NULL;
      audio_in_codec = NULL;
    } // end if audio_in_codec

    avcodec_close(audio_out_ctx);
#if LIBAVCODEC_VERSION_CHECK(57, 64, 0, 64, 0)
    avcodec_free_context(&audio_out_ctx);
#endif
    audio_out_ctx = NULL;
#if defined(HAVE_LIBAVRESAMPLE) || defined(HAVE_LIBSWRESAMPLE)
    if ( resample_ctx ) {
#if defined(HAVE_LIBSWRESAMPLE)
      swr_free(&resample_ctx);
#else
#if defined(HAVE_LIBAVRESAMPLE)
      avresample_close(resample_ctx);
      avresample_free(&resample_ctx);
#endif
#endif
    }
    if ( in_frame ) {
      av_frame_free(&in_frame);
      in_frame = NULL;
    }
    if ( out_frame ) {
      av_frame_free(&out_frame);
      out_frame = NULL;
    }
    if ( converted_in_samples ) {
      av_free(converted_in_samples);
      converted_in_samples = NULL;
    }
#endif
  }

  /* free the stream */
  avformat_free_context(oc);
} // VideoStore::~VideoStore()

bool VideoStore::setup_resampler() {
#if !defined(HAVE_LIBSWRESAMPLE) && !defined(HAVE_LIBAVRESAMPLE)
  Error(
     "Not built with resample library. "
     "Cannot do audio conversion to AAC");
  return false;
#else

#if LIBAVCODEC_VERSION_CHECK(57, 64, 0, 64, 0)
  // Newer ffmpeg wants to keep everything separate... so have to lookup our own
  // decoder, can't reuse the one from the camera.
  audio_in_codec =
      avcodec_find_decoder(audio_in_stream->codecpar->codec_id);
#else
  audio_in_codec = avcodec_find_decoder(audio_in_ctx->codec_id);
#endif
  ret = avcodec_open2(audio_in_ctx, audio_in_codec, NULL);
  if ( ret < 0 ) {
    Error("Can't open in codec!");
    return false;
  }

  audio_out_codec = avcodec_find_encoder(AV_CODEC_ID_AAC);
  if ( !audio_out_codec ) {
    Error("Could not find codec for AAC");
    return false;
  }
  Debug(2, "Have audio out codec");

#if LIBAVCODEC_VERSION_CHECK(57, 64, 0, 64, 0)
  // audio_out_ctx = audio_out_stream->codec;
  audio_out_ctx = avcodec_alloc_context3(audio_out_codec);
  if ( !audio_out_ctx ) {
    Error("could not allocate codec ctx for AAC");
    audio_out_stream = NULL;
    return false;
  }

  Debug(2, "Have audio_out_ctx");
  // Now copy them to the out stream
  audio_out_stream = avformat_new_stream(oc, audio_out_codec);
#else 
  audio_out_stream = avformat_new_stream(oc, NULL);
  audio_out_ctx = audio_out_stream->codec;
#endif
  // Some formats (i.e. WAV) do not produce the proper channel layout
  if ( audio_in_ctx->channel_layout == 0 )
    audio_in_ctx->channel_layout = av_get_channel_layout("mono");

  /* put sample parameters */
  audio_out_ctx->bit_rate = audio_in_ctx->bit_rate <= 96000 ? audio_in_ctx->bit_rate : 96000;
  audio_out_ctx->sample_rate = audio_in_ctx->sample_rate;
  audio_out_ctx->channels = audio_in_ctx->channels;
  audio_out_ctx->channel_layout = audio_in_ctx->channel_layout;
  audio_out_ctx->sample_fmt = audio_in_ctx->sample_fmt;
#if LIBAVCODEC_VERSION_CHECK(57, 64, 0, 64, 0)
#else
  audio_out_ctx->refcounted_frames = 1;
#endif
  if ( ! audio_out_ctx->channel_layout ) {
    Debug(3, "Correcting channel layout from (%d) to (%d)",
        audio_out_ctx->channel_layout,
        av_get_default_channel_layout(audio_out_ctx->channels)
        );
      audio_out_ctx->channel_layout = av_get_default_channel_layout(audio_out_ctx->channels);
  }

  if ( audio_out_codec->supported_samplerates ) {
    int found = 0;
    for ( unsigned int i = 0; audio_out_codec->supported_samplerates[i]; i++) {
      if ( audio_out_ctx->sample_rate ==
          audio_out_codec->supported_samplerates[i] ) {
        found = 1;
        break;
      }
    }
    if ( found ) {
      Debug(3, "Sample rate is good");
    } else {
      audio_out_ctx->sample_rate =
          audio_out_codec->supported_samplerates[0];
      Debug(1, "Sample rate is no good, setting to (%d)",
            audio_out_codec->supported_samplerates[0]);
    }
  }

  /* check that the encoder supports s16 pcm in */
  if ( !check_sample_fmt(audio_out_codec, audio_out_ctx->sample_fmt) ) {
    Debug(3, "Encoder does not support sample format %s, setting to FLTP",
          av_get_sample_fmt_name(audio_out_ctx->sample_fmt));
    audio_out_ctx->sample_fmt = AV_SAMPLE_FMT_FLTP;
  }

  audio_out_ctx->time_base =
      (AVRational){1, audio_out_ctx->sample_rate};

  AVDictionary *opts = NULL;
  if ( (ret = av_dict_set(&opts, "strict", "experimental", 0)) < 0 ) {
    Error("Couldn't set experimental");
  }
  ret = avcodec_open2(audio_out_ctx, audio_out_codec, &opts);
  av_dict_free(&opts);
  if ( ret < 0 ) {
    Error("could not open codec (%d) (%s)\n", ret, av_make_error_string(ret).c_str());
    audio_out_codec = NULL;
    audio_out_ctx = NULL;
    audio_out_stream = NULL;
    return false;
  }

#if LIBAVCODEC_VERSION_CHECK(57, 64, 0, 64, 0)
  ret = avcodec_parameters_from_context(
      audio_out_stream->codecpar, audio_out_ctx);
  if ( ret < 0 ) {
    Error("Could not initialize stream parameteres");
    return false;
  }
#endif

  Debug(1,
        "Audio out bit_rate (%d) sample_rate(%d) channels(%d) fmt(%d) "
        "layout(%d) frame_size(%d)",
        audio_out_ctx->bit_rate, audio_out_ctx->sample_rate,
        audio_out_ctx->channels, audio_out_ctx->sample_fmt,
        audio_out_ctx->channel_layout, audio_out_ctx->frame_size);

  /** Create a new frame to store the audio samples. */
  if ( !(in_frame = zm_av_frame_alloc()) ) {
    Error("Could not allocate in frame");
    return false;
  }

  /** Create a new frame to store the audio samples. */
  if ( !(out_frame = zm_av_frame_alloc()) ) {
    Error("Could not allocate out frame");
    av_frame_free(&in_frame);
    return false;
  }

#if defined(HAVE_LIBSWRESAMPLE)
  resample_ctx = swr_alloc_set_opts(NULL,
      av_get_default_channel_layout(audio_out_ctx->channels),
      audio_out_ctx->sample_fmt,
      audio_out_ctx->sample_rate,
      av_get_default_channel_layout(audio_in_ctx->channels),
      audio_in_ctx->sample_fmt,
      audio_in_ctx->sample_rate,
      0, NULL);
  if ( !resample_ctx ) {
    Error("Could not allocate resample context");
    av_frame_free(&in_frame);
    av_frame_free(&out_frame);
    return false;
  }
  if ( (ret = swr_init(resample_ctx)) < 0 ) {
    Error("Could not open resampler");
    av_frame_free(&in_frame);
    av_frame_free(&out_frame);
    swr_free(&resample_ctx);
    return false;
  }
#else
#if defined(HAVE_LIBAVRESAMPLE)
  // Setup the audio resampler
  resample_ctx = avresample_alloc_context();
  if ( !resample_ctx ) {
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

  ret = avresample_open(resample_ctx);
  if ( ret < 0 ) {
    Error("Could not open resample ctx");
    return false;
  } else {
    Debug(2, "Success opening resampler");
  }
#endif
#endif

  out_frame->nb_samples = audio_out_ctx->frame_size;
  out_frame->format = audio_out_ctx->sample_fmt;
  out_frame->channel_layout = audio_out_ctx->channel_layout;

  // The codec gives us the frame size, in samples, we calculate the size of the
  // samples buffer in bytes
  unsigned int audioSampleBuffer_size = av_samples_get_buffer_size(
      NULL, audio_out_ctx->channels,
      audio_out_ctx->frame_size,
      audio_out_ctx->sample_fmt, 0);
  converted_in_samples = (uint8_t *)av_malloc(audioSampleBuffer_size);

  if ( !converted_in_samples ) {
    Error("Could not allocate converted in sample pointers");
    return false;
  } else {
    Debug(2, "Frame Size %d, sample buffer size %d", audio_out_ctx->frame_size, audioSampleBuffer_size);
  }

  // Setup the data pointers in the AVFrame
  if ( avcodec_fill_audio_frame(out_frame, audio_out_ctx->channels,
                               audio_out_ctx->sample_fmt,
                               (const uint8_t *)converted_in_samples,
                               audioSampleBuffer_size, 0) < 0) {
    Error("Could not allocate converted in sample pointers");
    return false;
  }

  return true;
#endif
}  // end bool VideoStore::setup_resampler()

int VideoStore::writeVideoFramePacket(AVPacket *ipkt) {
  av_init_packet(&opkt);

  opkt.pts = video_next_pts;
  opkt.dts = video_next_dts;
  opkt.duration = 0;

  int64_t duration;
  if ( !video_last_pts ) {
    duration = 0;
  } else {
    duration =
        av_rescale_q(ipkt->pts - video_last_pts, video_in_stream->time_base,
                     video_out_stream->time_base);
    Debug(1, "duration calc: pts(%" PRId64 ") - last_pts(% " PRId64 ") = (%" PRId64 ")",
        ipkt->pts,
        video_last_pts,
        duration);
    if (duration <= 0) {
      duration = ipkt->duration ? ipkt->duration : av_rescale_q(1,video_in_stream->time_base, video_out_stream->time_base);
    }
  }

  //#if ( 0 && video_last_pts && ( ipkt->duration == AV_NOPTS_VALUE || !
  //ipkt->duration ) ) {
  // Video packets don't really have a duration. Audio does.
  // opkt.duration = av_rescale_q(duration, video_in_stream->time_base,
  // video_out_stream->time_base);
  // opkt.duration = 0;
  //} else {
  // duration = opkt.duration = av_rescale_q(ipkt->duration,
  // video_in_stream->time_base, video_out_stream->time_base);
  //}
  video_last_pts = ipkt->pts;
  video_last_dts = ipkt->dts;

#if 0
  //Scale the PTS of the outgoing packet to be the correct time base
  if ( ipkt->pts != AV_NOPTS_VALUE ) {

    if ( ! video_last_pts ) {
      // This is the first packet.
      opkt.pts = 0;
      Debug(2, "Starting video video_last_pts will become (%d)", ipkt->pts);
    } else {
      if ( ipkt->pts < video_last_pts ) {
        Debug(1, "Resetting video_last_pts from (%d) to (%d)",  video_last_pts, ipkt->pts);
        // wrap around, need to figure out the distance FIXME having this wrong should cause a jump, but then play ok?
        opkt.pts = video_next_pts + av_rescale_q( ipkt->pts, video_in_stream->time_base, video_out_stream->time_base);
      } else {
        opkt.pts = video_next_pts + av_rescale_q( ipkt->pts - video_last_pts, video_in_stream->time_base, video_out_stream->time_base);
      }
    }
    Debug(3, "opkt.pts = %d from ipkt->pts(%d) - last_pts(%d)", opkt.pts, ipkt->pts, video_last_pts);
    video_last_pts = ipkt->pts;
  } else {
    Debug(3, "opkt.pts = undef");
    opkt.pts = AV_NOPTS_VALUE;
  }
  // Just because the in stream wraps, doesn't mean the out needs to.  Really, if we are limiting ourselves to 10min segments I can't imagine every wrapping in the out.  So need to handle in wrap, without causing out wrap.
  if ( !video_last_dts ) {
    // This is the first packet.
    opkt.dts = 0;
    Debug(1, "Starting video video_last_dts will become (%lu)", ipkt->dts);
    video_last_dts = ipkt->dts;
  } else {
    // Scale the DTS of the outgoing packet to be the correct time base

    if ( ipkt->dts == AV_NOPTS_VALUE ) {
      // why are we using cur_dts instead of packet.dts? I think cur_dts is in AV_TIME_BASE_Q, but ipkt.dts is in video_in_stream->time_base
      if ( video_in_stream->cur_dts < video_last_dts ) {
        Debug(1, "Resetting video_last_dts from (%d) to (%d) p.dts was (%d)",  video_last_dts, video_in_stream->cur_dts, ipkt->dts);
        opkt.dts = video_next_dts + av_rescale_q(video_in_stream->cur_dts, AV_TIME_BASE_Q, video_out_stream->time_base);
      } else {
        opkt.dts = video_next_dts + av_rescale_q(video_in_stream->cur_dts - video_last_dts, AV_TIME_BASE_Q, video_out_stream->time_base);
      }
      Debug(3, "opkt.dts = %d from video_in_stream->cur_dts(%d) - previus_dts(%d)", opkt.dts, video_in_stream->cur_dts, video_last_dts);
      video_last_dts = video_in_stream->cur_dts;
    } else {
      if ( ipkt->dts < video_last_dts ) {
        Debug(1, "Resetting video_last_dts from (%d) to (%d)",  video_last_dts, ipkt->dts);
        opkt.dts = video_next_dts + av_rescale_q( ipkt->dts,  video_in_stream->time_base, video_out_stream->time_base);
      } else {
        opkt.dts = video_next_dts + av_rescale_q( ipkt->dts - video_last_dts, video_in_stream->time_base, video_out_stream->time_base);
      }
      Debug(3, "opkt.dts = %d from ipkt.dts(%d) - previus_dts(%d)", opkt.dts, ipkt->dts, video_last_dts);
      video_last_dts = ipkt->dts;
    }
  }
#endif
  if (opkt.dts > opkt.pts) {
    Debug(1,
          "opkt.dts(%d) must be <= opkt.pts(%d). Decompression must happen "
          "before presentation.",
          opkt.dts, opkt.pts);
    opkt.dts = opkt.pts;
  }

  opkt.flags = ipkt->flags;
  opkt.pos = -1;

  opkt.data = ipkt->data;
  opkt.size = ipkt->size;

  opkt.stream_index = video_out_stream->index;

  AVPacket safepkt;
  memcpy(&safepkt, &opkt, sizeof(AVPacket));

  dumpPacket( &opkt, "writing video packet" );
  if ((opkt.data == NULL) || (opkt.size < 1)) {
    Warning("%s:%d: Mangled AVPacket: discarding frame", __FILE__, __LINE__);
    dumpPacket(ipkt);
    dumpPacket(&opkt);

  } else if ((video_next_dts > 0) && (video_next_dts > opkt.dts)) {
    Warning("%s:%d: DTS out of order: %lld \u226E %lld; discarding frame",
            __FILE__, __LINE__, video_next_dts, opkt.dts);
    video_next_dts = opkt.dts;
    dumpPacket(&opkt);

  } else {
    video_next_dts = opkt.dts + duration;
    video_next_pts = opkt.pts + duration;
    ret = av_interleaved_write_frame(oc, &opkt);
    if (ret < 0) {
      // There's nothing we can really do if the frame is rejected, just drop it
      // and get on with the next
      Warning(
          "%s:%d: Writing frame [av_interleaved_write_frame()] failed: %s(%d) "
          " ",
          __FILE__, __LINE__, av_make_error_string(ret).c_str(), ret);
      dumpPacket(&safepkt);
#if LIBAVCODEC_VERSION_CHECK(57, 64, 0, 64, 0)
      zm_dump_codecpar(video_in_stream->codecpar);
      zm_dump_codecpar(video_out_stream->codecpar);
#endif
    }
  }

  zm_av_packet_unref(&opkt);

  return 0;
}  // end int VideoStore::writeVideoFramePacket( AVPacket *ipkt )

int VideoStore::writeAudioFramePacket(AVPacket *ipkt) {
  Debug(4, "writeAudioFrame");

  if ( !audio_out_stream ) {
    Debug(1, "Called writeAudioFramePacket when no audio_out_stream");
    return 0;  // FIXME -ve return codes do not free packet in ffmpeg_camera at
               // the moment
  }

  if ( audio_out_codec ) {
    Debug(3, "Have audio codec");
#if defined(HAVE_LIBSWRESAMPLE) || defined(HAVE_LIBAVRESAMPLE)

  #if LIBAVCODEC_VERSION_CHECK(57, 64, 0, 64, 0)
    ret = avcodec_send_packet(audio_in_ctx, ipkt);
    if ( ret < 0 ) {
      Error("avcodec_send_packet fail %s", av_make_error_string(ret).c_str());
      return 0;
    }

    ret = avcodec_receive_frame(audio_in_ctx, in_frame);
    if (ret < 0) {
      Error("avcodec_receive_frame fail %s", av_make_error_string(ret).c_str());
      return 0;
    }
    Debug(2,
          "Input Frame: samples(%d), format(%d), sample_rate(%d), channel "
          "layout(%d)",
          in_frame->nb_samples, in_frame->format,
          in_frame->sample_rate, in_frame->channel_layout);
  #else
    /**
     * Decode the audio frame stored in the packet.
     * The in audio stream decoder is used to do this.
     * If we are at the end of the file, pass an empty packet to the decoder
     * to flush it.
     */
    int data_present;
    if ((ret = avcodec_decode_audio4(audio_in_ctx, in_frame,
                                     &data_present, ipkt)) < 0) {
      Error("Could not decode frame (error '%s')\n",
            av_make_error_string(ret).c_str());
      dumpPacket(ipkt);
      av_frame_free(&in_frame);
      return 0;
    }
    if (!data_present) {
      Debug(2, "Not ready to transcode a frame yet.");
      return 0;
    }
  #endif
    int frame_size = out_frame->nb_samples;

    // Resample the in into the audioSampleBuffer until we proceed the whole
    // decoded data
  #if defined(HAVE_LIBSWRESAMPLE)
    Debug(2, "Converting  %d to %d samples", in_frame->nb_samples, out_frame->nb_samples);
    if ((ret = swr_convert(resample_ctx,
            out_frame->data, frame_size,
            (const uint8_t**)in_frame->data,
            in_frame->nb_samples)) < 0) {
      Error("Could not resample frame (error '%s')\n",
            av_make_error_string(ret).c_str());
      av_frame_unref(in_frame);
      return 0;
    }
  #else
    #if defined(HAVE_LIBAVRESAMPLE)
    if ((ret =
             avresample_convert(resample_ctx, NULL, 0, 0, in_frame->data,
                                0, in_frame->nb_samples)) < 0) {
      Error("Could not resample frame (error '%s')\n",
            av_make_error_string(ret).c_str());
      av_frame_unref(in_frame);
      return 0;
    }
  #endif
  #endif
    av_frame_unref(in_frame);

  #if defined(HAVE_LIBAVRESAMPLE)
    int samples_available = avresample_available(resample_ctx);

    if (samples_available < frame_size) {
      Debug(1, "Not enough samples yet (%d)", samples_available);
      return 0;
    }

    Debug(3, "Output_frame samples (%d)", out_frame->nb_samples);
    // Read a frame audio data from the resample fifo
    if (avresample_read(resample_ctx, out_frame->data, frame_size) !=
        frame_size) {
      Warning("Error reading resampled audio: ");
      return 0;
    }
  #endif
    Debug(2,
          "Frame: samples(%d), format(%d), sample_rate(%d), channel layout(%d)",
          out_frame->nb_samples, out_frame->format,
          out_frame->sample_rate, out_frame->channel_layout);

    av_init_packet(&opkt);
    Debug(5, "after init packet");

  #if LIBAVCODEC_VERSION_CHECK(57, 64, 0, 64, 0)
    if ((ret = avcodec_send_frame(audio_out_ctx, out_frame)) < 0) {
      Error("Could not send frame (error '%s')",
            av_make_error_string(ret).c_str());
      zm_av_packet_unref(&opkt);
      return 0;
    }

    // av_frame_unref( out_frame );

    if ((ret = avcodec_receive_packet(audio_out_ctx, &opkt)) < 0) {
      if (AVERROR(EAGAIN) == ret) {
        // THe codec may need more samples than it has, perfectly valid
        Debug(3, "Could not recieve packet (error '%s')",
              av_make_error_string(ret).c_str());
      } else {
        Error("Could not recieve packet (error %d = '%s')", ret,
              av_make_error_string(ret).c_str());
      }
      zm_av_packet_unref(&opkt);
      av_frame_unref(in_frame);
      // av_frame_unref( out_frame );
      return 0;
    }
  #else
    if ((ret = avcodec_encode_audio2(audio_out_ctx, &opkt, out_frame,
                                     &data_present)) < 0) {
      Error("Could not encode frame (error '%s')",
            av_make_error_string(ret).c_str());
      zm_av_packet_unref(&opkt);
      return 0;
    }
    if (!data_present) {
      Debug(2, "Not ready to out a frame yet.");
      zm_av_packet_unref(&opkt);
      return 0;
    }
  #endif
#else
    Error("Have audio codec but no resampler?!");
#endif
  } else {
    av_init_packet(&opkt);
    Debug(5, "after init packet");
    opkt.data = ipkt->data;
    opkt.size = ipkt->size;
  }

// PTS is difficult, because of the buffering of the audio packets in the
// resampler.  So we have to do it once we actually have a packet...
// audio_last_pts is the pts of ipkt, audio_next_pts is the last pts of the
// out

// Scale the PTS of the outgoing packet to be the correct time base
#if 0
  if ( ipkt->pts != AV_NOPTS_VALUE ) {
    if ( !audio_last_pts ) {
      opkt.pts = 0;
      Debug(1, "No audio_last_pts");
    } else {
      if ( audio_last_pts > ipkt->pts ) {
        Debug(1, "Resetting audio_start_pts from (%d) to (%d)",  audio_last_pts, ipkt->pts);
        opkt.pts = audio_next_pts + av_rescale_q(ipkt->pts, audio_in_stream->time_base, audio_out_stream->time_base);
      } else {
        opkt.pts = audio_next_pts + av_rescale_q(ipkt->pts - audio_last_pts, audio_in_stream->time_base, audio_out_stream->time_base);
      }
      Debug(2, "audio opkt.pts = %d from ipkt->pts(%d) - last_pts(%d)", opkt.pts, ipkt->pts, audio_last_pts);
    }
    audio_last_pts = ipkt->pts;
  } else {
    Debug(2, "opkt.pts = undef");
    opkt.pts = AV_NOPTS_VALUE;
  }
#else
  opkt.pts = audio_next_pts;
  opkt.dts = audio_next_dts;
#endif

#if 0
    if ( ipkt->dts == AV_NOPTS_VALUE ) {
      // So if the in has no dts assigned... still need an out dts... so we use cur_dts?

      if ( audio_last_dts >= audio_in_stream->cur_dts ) {
        Debug(1, "Resetting audio_last_dts from (%d) to cur_dts (%d)", audio_last_dts, audio_in_stream->cur_dts);
        opkt.dts = audio_next_dts + av_rescale_q( audio_in_stream->cur_dts,  AV_TIME_BASE_Q, audio_out_stream->time_base);
      } else {
        opkt.dts = audio_next_dts + av_rescale_q( audio_in_stream->cur_dts - audio_last_dts, AV_TIME_BASE_Q, audio_out_stream->time_base);
      }
      audio_last_dts = audio_in_stream->cur_dts;
      Debug(2, "opkt.dts = %d from video_in_stream->cur_dts(%d) - last_dts(%d)", opkt.dts, audio_in_stream->cur_dts, audio_last_dts);
    } else {
      if ( audio_last_dts >= ipkt->dts ) {
        Debug(1, "Resetting audio_last_dts from (%d) to (%d)",  audio_last_dts, ipkt->dts );
        opkt.dts = audio_next_dts + av_rescale_q(ipkt->dts, audio_in_stream->time_base, audio_out_stream->time_base);
      } else {
        opkt.dts = audio_next_dts + av_rescale_q(ipkt->dts - audio_last_dts, audio_in_stream->time_base, audio_out_stream->time_base);
        Debug(2, "opkt.dts = %d from previous(%d) + ( ipkt->dts(%d) - last_dts(%d) )", opkt.dts, audio_next_dts, ipkt->dts, audio_last_dts );
      }
    }
  }
#endif
  // audio_last_dts = ipkt->dts;
  if (opkt.dts > opkt.pts) {
    Debug(1,
          "opkt.dts(%d) must be <= opkt.pts(%d). Decompression must happen "
          "before presentation.",
          opkt.dts, opkt.pts);
    opkt.dts = opkt.pts;
  }

  // I wonder if we could just use duration instead of all the hoop jumping
  // above?
  //
  if (out_frame) {
    opkt.duration = out_frame->nb_samples;
  } else {
    opkt.duration = ipkt->duration;
  }
  // opkt.duration = av_rescale_q(ipkt->duration, audio_in_stream->time_base,
  // audio_out_stream->time_base);
  Debug(2, "opkt.pts (%d), opkt.dts(%d) opkt.duration = (%d)", opkt.pts,
        opkt.dts, opkt.duration);

  // pkt.pos:  byte position in stream, -1 if unknown
  opkt.pos = -1;
  opkt.stream_index = audio_out_stream->index;
  audio_next_dts = opkt.dts + opkt.duration;
  audio_next_pts = opkt.pts + opkt.duration;

  AVPacket safepkt;
  memcpy(&safepkt, &opkt, sizeof(AVPacket));
  ret = av_interleaved_write_frame(oc, &opkt);
  if (ret != 0) {
    Error("Error writing audio frame packet: %s\n",
          av_make_error_string(ret).c_str());
    dumpPacket(&safepkt);
  } else {
    Debug(2, "Success writing audio frame");
  }
  zm_av_packet_unref(&opkt);
  return 0;
}  // end int VideoStore::writeAudioFramePacket( AVPacket *ipkt )
