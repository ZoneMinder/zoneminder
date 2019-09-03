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

VideoStore::VideoStore(
    const char *filename_in,
    const char *format_in,
    AVStream *p_video_in_stream,
    AVStream *p_audio_in_stream,
    Monitor *monitor
    ) {

  video_in_stream = p_video_in_stream;
  audio_in_stream = p_audio_in_stream;

#if LIBAVCODEC_VERSION_CHECK(57, 64, 0, 64, 0)
  //video_in_ctx = avcodec_alloc_context3(NULL);
  //avcodec_parameters_to_context(video_in_ctx,
                                //video_in_stream->codecpar);
  //video_in_ctx->time_base = video_in_stream->time_base;
// zm_dump_codecpar( video_in_stream->codecpar );
#else
#endif

  // In future, we should just pass in the codec context instead of the stream.  Don't really need the stream.
  video_in_ctx = video_in_stream->codec;

  // store ins in variables local to class
  filename = filename_in;
  format = format_in;

  Info("Opening video storage stream %s format: %s", filename, format);

  ret = avformat_alloc_output_context2(&oc, NULL, NULL, filename);
  if ( ret < 0 ) {
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
    if ( !oc ) {
      Error(
          "Could not create video storage stream %s as no out ctx"
          " could not be assigned based on filename or format %s",
          filename, format);
      return;
    } else {
      Debug(4, "Success allocating out ctx");
    }
  }  // end if ! oc

  AVDictionary *pmetadata = NULL;
  int dsr =
      av_dict_set(&pmetadata, "title", "Zoneminder Security Recording", 0);
  if ( dsr < 0 ) Warning("%s:%d: title set failed", __FILE__, __LINE__);

  oc->metadata = pmetadata;
  out_format = oc->oformat;
	out_format->flags |= AVFMT_TS_NONSTRICT; // allow non increasing dts

  video_out_codec = avcodec_find_encoder(video_in_ctx->codec_id);
  if ( !video_out_codec ) {
#if (LIBAVFORMAT_VERSION_CHECK(53, 8, 0, 11, 0) && (LIBAVFORMAT_VERSION_MICRO >= 100))
    Fatal("Could not find encoder for '%s'", avcodec_get_name(video_out_ctx->codec_id));
#else
    Fatal("Could not find encoder for '%d'", video_out_ctx->codec_id);
#endif
  }

  video_out_stream = avformat_new_stream(oc, NULL);
  if ( !video_out_stream ) {
    Error("Unable to create video out stream");
    return;
  } else {
    Debug(2, "Success creating video out stream");
  }

#if LIBAVCODEC_VERSION_CHECK(57, 64, 0, 64, 0)
  // by allocating our own copy, we don't run into the problems when we free the streams
  video_out_ctx = avcodec_alloc_context3(video_out_codec);
  // Since we are not re-encoding, all we have to do is copy the parameters
  // Copy params from instream to ctx
  ret = avcodec_parameters_to_context(video_out_ctx, video_in_stream->codecpar);
  if ( ret < 0 ) {
    Error("Could not initialize video_out_ctx parameters");
    return;
  }
#else
  video_out_ctx = video_out_stream->codec;
  // This will wipe out the codec defaults
  ret = avcodec_copy_context(video_out_ctx, video_in_ctx);
  if ( ret < 0 ) {
    Fatal("Unable to copy in video ctx to out video ctx %s",
          av_make_error_string(ret).c_str());
  } else {
    Debug(3, "Success copying ctx");
  }
#endif

  // Just copy them from the in, no reason to choose different
  video_out_ctx->time_base = video_in_ctx->time_base;
  if ( ! (video_out_ctx->time_base.num && video_out_ctx->time_base.den) ) {
    Debug(2,"No timebase found in video in context, defaulting to Q");
	  video_out_ctx->time_base = AV_TIME_BASE_Q;
  }

  zm_dump_codec(video_out_ctx);

#if LIBAVCODEC_VERSION_CHECK(57, 64, 0, 64, 0)
  //// Fix deprecated formats
  switch ( video_out_ctx->pix_fmt ) {
    case AV_PIX_FMT_YUVJ422P  :
      video_out_ctx->pix_fmt = AV_PIX_FMT_YUV422P;
      break;
    case AV_PIX_FMT_YUVJ444P   :
      video_out_ctx->pix_fmt = AV_PIX_FMT_YUV444P;
      break;
    case AV_PIX_FMT_YUVJ440P :
      video_out_ctx->pix_fmt = AV_PIX_FMT_YUV440P;
      break;
    case AV_PIX_FMT_NONE :
    case AV_PIX_FMT_YUVJ420P :
    default:
      video_out_ctx->pix_fmt = AV_PIX_FMT_YUV420P;
      break;
  }

  if ( !video_out_ctx->codec_tag ) {
    Debug(2, "No codec_tag");
    if ( 
        !oc->oformat->codec_tag
        ||
        av_codec_get_id(oc->oformat->codec_tag, video_in_ctx->codec_tag) == video_out_ctx->codec_id
        ||
        av_codec_get_tag(oc->oformat->codec_tag, video_in_ctx->codec_id) <= 0
        ) {
      Warning("Setting codec tag");
      video_out_ctx->codec_tag = video_in_ctx->codec_tag;
    }
  }
#endif

  video_out_stream->time_base = video_in_stream->time_base;
  if ( video_in_stream->avg_frame_rate.num ) {
    Debug(3,"Copying avg_frame_rate (%d/%d)",
        video_in_stream->avg_frame_rate.num, 
        video_in_stream->avg_frame_rate.den 
        );
    video_out_stream->avg_frame_rate = video_in_stream->avg_frame_rate;
  }
  if ( video_in_stream->r_frame_rate.num ) {
    Debug(3,"Copying r_frame_rate (%d/%d) to out (%d/%d)",
        video_in_stream->r_frame_rate.num, 
        video_in_stream->r_frame_rate.den ,
        video_out_stream->r_frame_rate.num, 
        video_out_stream->r_frame_rate.den 
        );
    video_out_stream->r_frame_rate = video_in_stream->r_frame_rate;
  }
#if LIBAVCODEC_VERSION_CHECK(56, 35, 0, 64, 0)
  ret = avcodec_parameters_from_context(video_out_stream->codecpar, video_out_ctx);
  if ( ret < 0 ) {
    Error("Could not initialize video_out_ctx parameters");
    return;
  } else {
    zm_dump_codec(video_out_ctx);
  }

  zm_dump_codecpar(video_in_stream->codecpar);
  zm_dump_codecpar(video_out_stream->codecpar);
#endif
  Debug(3,
        "Time bases: VIDEO in stream (%d/%d) in codec: (%d/%d) out "
        "stream: (%d/%d) out codec (%d/%d)",
        video_in_stream->time_base.num, video_in_stream->time_base.den,
        video_in_ctx->time_base.num, video_in_ctx->time_base.den,
        video_out_stream->time_base.num, video_out_stream->time_base.den,
        video_out_ctx->time_base.num, video_out_ctx->time_base.den);

  if ( oc->oformat->flags & AVFMT_GLOBALHEADER ) {
#if LIBAVCODEC_VERSION_CHECK(56, 35, 0, 64, 0)
    video_out_ctx->flags |= AV_CODEC_FLAG_GLOBAL_HEADER;
#else
    video_out_ctx->flags |= CODEC_FLAG_GLOBAL_HEADER;
#endif
  }

#if LIBAVCODEC_VERSION_CHECK(56, 35, 0, 64, 0)
  AVDictionary *opts = 0;
  if ( (ret = avcodec_open2(video_out_ctx, video_out_codec, &opts)) < 0 ) {
    Warning("Can't open video codec (%s) %s",
        video_out_codec->name,
        av_make_error_string(ret).c_str()
        );
    video_out_codec = NULL;
  }

  AVDictionaryEntry *e = NULL;
  while ( (e = av_dict_get(opts, "", e, AV_DICT_IGNORE_SUFFIX)) != NULL ) {
    Warning("Encoder Option %s not recognized by ffmpeg codec", e->key);
  }
#endif

  Monitor::Orientation orientation = monitor->getOrientation();
  if ( orientation ) {
    if ( orientation == Monitor::ROTATE_0 ) {
    } else if ( orientation == Monitor::ROTATE_90 ) {
      dsr = av_dict_set(&video_out_stream->metadata, "rotate", "90", 0);
      if ( dsr < 0 ) Warning("%s:%d: title set failed", __FILE__, __LINE__);
    } else if ( orientation == Monitor::ROTATE_180 ) {
      dsr = av_dict_set(&video_out_stream->metadata, "rotate", "180", 0);
      if ( dsr < 0 ) Warning("%s:%d: title set failed", __FILE__, __LINE__);
    } else if ( orientation == Monitor::ROTATE_270 ) {
      dsr = av_dict_set(&video_out_stream->metadata, "rotate", "270", 0);
      if ( dsr < 0 ) Warning("%s:%d: title set failed", __FILE__, __LINE__);
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
#if defined(HAVE_LIBSWRESAMPLE)
  fifo = NULL;
#endif
#endif
  video_first_pts = 0;
  video_first_dts = 0;
  video_last_pts = 0;
  video_last_dts = 0;

  audio_first_pts = 0;
  audio_first_dts = 0;
  audio_next_pts = 0;
  audio_next_dts = 0;

  if ( audio_in_stream ) {
    Debug(3, "Have audio stream");

    if (
#if LIBAVCODEC_VERSION_CHECK(57, 64, 0, 64, 0)
        audio_in_stream->codecpar->codec_id
#else
        audio_in_stream->codec->codec_id
#endif
        != AV_CODEC_ID_AAC ) {

      audio_out_codec = avcodec_find_encoder(AV_CODEC_ID_AAC);
      if ( !audio_out_codec ) {
        Error("Could not find codec for AAC");
        return;
      }

#if LIBAVCODEC_VERSION_CHECK(57, 64, 0, 64, 0)
      audio_out_stream = avformat_new_stream(oc, NULL);
      audio_out_ctx = avcodec_alloc_context3(audio_out_codec);
      if ( !audio_out_ctx ) {
        Error("could not allocate codec ctx for AAC");
        audio_out_stream = NULL;
        return;
      }
#else 
      audio_out_stream = avformat_new_stream(oc, audio_out_codec);
      audio_out_ctx = audio_out_stream->codec;
#endif
      audio_out_stream->time_base = audio_in_stream->time_base;

      if ( !setup_resampler() ) {
        return;
      }
    } else {
      Debug(2, "Got AAC");

      audio_out_stream = avformat_new_stream(oc, NULL);
      if ( !audio_out_stream ) {
        Error("Could not allocate new stream");
        return;
      }
      audio_out_stream->time_base = audio_in_stream->time_base;

#if LIBAVCODEC_VERSION_CHECK(57, 64, 0, 64, 0)
      // Just use the ctx to copy the parameters over
      audio_out_ctx = avcodec_alloc_context3(audio_out_codec);
      if ( !audio_out_ctx ) {
        Error("Could not allocate new output_context");
        return;
      }

      // We don't actually care what the time_base is..
      audio_out_ctx->time_base = audio_in_stream->time_base;

      // Copy params from instream to ctx
      ret = avcodec_parameters_to_context(
          audio_out_ctx, audio_in_stream->codecpar);
      if ( ret < 0 ) {
        Error("Unable to copy audio params to ctx %s",
              av_make_error_string(ret).c_str());
      }
      ret = avcodec_parameters_from_context(
          audio_out_stream->codecpar, audio_out_ctx);
      if ( ret < 0 ) {
        Error("Unable to copy audio params to stream %s",
              av_make_error_string(ret).c_str());
      }
#else
      audio_out_ctx = audio_out_stream->codec;
      ret = avcodec_copy_context(audio_out_ctx, audio_in_stream->codec);
      if ( ret < 0 ) {
        Error("Unable to copy audio ctx %s",
              av_make_error_string(ret).c_str());
        audio_out_stream = NULL;
        return;
      } // end if
      audio_out_ctx->codec_tag = 0;
#endif

      if ( audio_out_ctx->channels > 1 ) {
        Warning("Audio isn't mono, changing it.");
        audio_out_ctx->channels = 1;
      } else {
        Debug(3, "Audio is mono");
      }
    } // end if is AAC

    if ( oc->oformat->flags & AVFMT_GLOBALHEADER ) {
#if LIBAVCODEC_VERSION_CHECK(56, 35, 0, 64, 0)
      audio_out_ctx->flags |= AV_CODEC_FLAG_GLOBAL_HEADER;
#else
      audio_out_ctx->flags |= CODEC_FLAG_GLOBAL_HEADER;
#endif
    }
  }  // end if audio_in_stream
}  // VideoStore::VideoStore

bool VideoStore::open() {
  /* open the out file, if needed */
  if ( !(out_format->flags & AVFMT_NOFILE) ) {
    ret = avio_open2(&oc->pb, filename, AVIO_FLAG_WRITE, NULL, NULL);
    if ( ret < 0 ) {
      Error("Could not open out file '%s': %s", filename,
            av_make_error_string(ret).c_str());
      return false;
    }
  }

  zm_dump_stream_format(oc, 0, 0, 1);
  if (audio_out_stream) zm_dump_stream_format(oc, 1, 0, 1);

  AVDictionary *opts = NULL;
  // av_dict_set(&opts, "movflags", "frag_custom+dash+delay_moov", 0);
  // Shiboleth reports that this may break seeking in mp4 before it downloads
  //av_dict_set(&opts, "movflags", "frag_keyframe+empty_moov", 0);
  // av_dict_set(&opts, "movflags",
  // "frag_keyframe+empty_moov+default_base_moof", 0);
  if ( (ret = avformat_write_header(oc, &opts)) < 0 ) {
    // if ((ret = avformat_write_header(oc, &opts)) < 0) {
    Warning("Unable to set movflags to frag_custom+dash+delay_moov");
    /* Write the stream header, if any. */
    ret = avformat_write_header(oc, NULL);
  } else if (av_dict_count(opts) != 0) {
    Warning("some options not set");
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

    if ( audio_out_codec ) {
      // The codec queues data.  We need to send a flush command and out
      // whatever we get. Failures are not fatal.
      AVPacket pkt;
      // Without these we seg fault I don't know why.
      pkt.data = NULL;
      pkt.size = 0;
      av_init_packet(&pkt);
#if LIBAVCODEC_VERSION_CHECK(57, 64, 0, 64, 0)
      // Put encoder into flushing mode
      avcodec_send_frame(audio_out_ctx, NULL);
#endif

      while (1) {
#if LIBAVCODEC_VERSION_CHECK(57, 64, 0, 64, 0)
        ret = avcodec_receive_packet(audio_out_ctx, &pkt);
        if ( ret < 0 ) {
          if ( AVERROR_EOF != ret ) {
            Error("Error encoding audio while flushing (%d) (%s)", ret,
                av_err2str(ret));
          }
          break;
        }
#else
        int got_packet = 0;
        ret = avcodec_encode_audio2(audio_out_ctx, &pkt, NULL, &got_packet);
        if ( ret < 0 ) {
          Error("Error encoding audio while flushing (%d) (%s)", ret,
              av_err2str(ret));
          break;
        }
        Debug(1, "Have audio encoder, need to flush it's out");
        if ( !got_packet ) {
          break;
        }
#endif

        dumpPacket(&pkt, "raw from encoder");
        // Need to adjust pts and dts and duration

        pkt.stream_index = audio_out_stream->index;

        pkt.duration = av_rescale_q(
            pkt.duration,
            audio_out_ctx->time_base,
            audio_out_stream->time_base);
        // Scale the PTS of the outgoing packet to be the correct time base
        if ( pkt.pts != AV_NOPTS_VALUE ) {
#if 0
            pkt.pts = av_rescale_q(
                pkt.pts,
                audio_out_ctx->time_base,
                audio_in_stream->time_base);
            // audio_first_pts is in audio_in_stream time base
            pkt.pts -= audio_first_pts;
            pkt.pts = av_rescale_q(
                pkt.pts,
                audio_in_stream->time_base,
                audio_out_stream->time_base);
#else
            pkt.pts = av_rescale_q(
                pkt.pts,
                audio_out_ctx->time_base,
                audio_out_stream->time_base);
#endif

            Debug(2, "audio pkt.pts = %" PRId64 " from first_pts(%" PRId64 ")",
                pkt.pts, audio_first_pts);
        } else {
          Debug(2, "pkt.pts = undef");
          pkt.pts = AV_NOPTS_VALUE;
        }

        if ( pkt.dts != AV_NOPTS_VALUE ) {
#if 0
          pkt.dts = av_rescale_q(
              pkt.dts,
              audio_out_ctx->time_base,
              audio_in_stream->time_base);
          pkt.dts -= audio_first_dts;
          pkt.dts = av_rescale_q(
              pkt.dts,
              audio_in_stream->time_base,
              audio_out_stream->time_base);
#else
          pkt.dts = av_rescale_q(
              pkt.dts,
              audio_out_ctx->time_base,
              audio_out_stream->time_base);
#endif
          Debug(2, "pkt.dts = %" PRId64 " - first_dts(%" PRId64 ")",
                pkt.dts, audio_first_dts);
        } else {
          pkt.dts = AV_NOPTS_VALUE;
        }

        dumpPacket(audio_out_stream, &pkt, "writing flushed packet");
        av_interleaved_write_frame(oc, &pkt);
        zm_av_packet_unref(&pkt);
      } // while have buffered frames
    } // end if audio_out_codec

    // Flush Queues
    Debug(1,"Flushing interleaved queues");
    av_interleaved_write_frame(oc, NULL);

    Debug(1,"Writing trailer");
    /* Write the trailer before close */
    if ( int rc = av_write_trailer(oc) ) {
      Error("Error writing trailer %s", av_err2str(rc));
    } else {
      Debug(3, "Success Writing trailer");
    }

    // When will we not be using a file ?
    if ( !(out_format->flags & AVFMT_NOFILE) ) {
      /* Close the out file. */
      Debug(2, "Closing");
      if ( int rc = avio_close(oc->pb) ) {
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
    //avcodec_free_context(&video_in_ctx);
#endif
    video_in_ctx = NULL;

    if ( video_out_codec ) {
      avcodec_close(video_out_ctx);
      Debug(4, "Success closing video_out_ctx");
      video_out_codec = NULL;
    } // end if video_out_codec
#if LIBAVCODEC_VERSION_CHECK(57, 64, 0, 64, 0)
    avcodec_free_context(&video_out_ctx);
#endif
    video_out_ctx = NULL;
  } // end if video_out_stream

  if ( audio_out_stream ) {
    if ( audio_in_codec ) {
      avcodec_close(audio_in_ctx);
      Debug(4, "Success closing audio_in_ctx");
      audio_in_codec = NULL;
    } // end if audio_in_codec

#if LIBAVCODEC_VERSION_CHECK(57, 64, 0, 64, 0)
    // We allocate and copy in newer ffmpeg, so need to free it
    avcodec_free_context(&audio_in_ctx);
#endif
    Debug(4, "Success freeing audio_in_ctx");
    audio_in_ctx = NULL;

    if ( audio_out_ctx ) {
      avcodec_close(audio_out_ctx);
      Debug(4, "Success closing audio_out_ctx");
#if LIBAVCODEC_VERSION_CHECK(57, 64, 0, 64, 0)
      avcodec_free_context(&audio_out_ctx);
#endif
    }
    audio_out_ctx = NULL;

#if defined(HAVE_LIBAVRESAMPLE) || defined(HAVE_LIBSWRESAMPLE)
    if ( resample_ctx ) {
  #if defined(HAVE_LIBSWRESAMPLE)
      if ( fifo ) {
        av_audio_fifo_free(fifo);
        fifo = NULL;
      }
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
  } // end if audio_out_stream

  /* free the streams */
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
  audio_in_ctx = avcodec_alloc_context3(audio_in_codec);
  // Copy params from instream to ctx
  ret = avcodec_parameters_to_context(
      audio_in_ctx, audio_in_stream->codecpar);
  if ( ret < 0 ) {
    Error("Unable to copy audio params to ctx %s",
        av_make_error_string(ret).c_str());
  }

#else
// codec is already open in ffmpeg_camera
  audio_in_ctx = audio_in_stream->codec;
  audio_in_codec = (AVCodec *)audio_in_ctx->codec;
  //audio_in_codec = avcodec_find_decoder(audio_in_stream->codec->codec_id);
#endif

#if LIBAVCODEC_VERSION_CHECK(57, 64, 0, 64, 0)
#else
#if 0
  ret = avcodec_copy_context(audio_in_ctx, audio_in_stream->codec);
  if ( ret < 0 ) {
    Fatal("Unable to copy in video ctx to out video ctx %s",
          av_make_error_string(ret).c_str());
  } else {
    Debug(3, "Success copying ctx");
  }
#endif
#endif

  // if the codec is already open, nothing is done.
  if ( (ret = avcodec_open2(audio_in_ctx, audio_in_codec, NULL)) < 0 ) {
    Error("Can't open audio in codec!");
    return false;
  }

  Debug(2, "Got something other than AAC (%s)", audio_in_codec->name);

  // Some formats (i.e. WAV) do not produce the proper channel layout
  if ( audio_in_ctx->channel_layout == 0 ) {
    Debug(2, "Setting input channel layout to mono");
    // Perhaps we should not be modifying the audio_in_ctx....
    audio_in_ctx->channel_layout = av_get_channel_layout("mono");
  }

  /* put sample parameters */
  audio_out_ctx->bit_rate = audio_in_ctx->bit_rate <= 32768 ? audio_in_ctx->bit_rate : 32768;
  audio_out_ctx->sample_rate = audio_in_ctx->sample_rate;
  audio_out_ctx->channels = audio_in_ctx->channels;
  audio_out_ctx->channel_layout = audio_in_ctx->channel_layout;
  audio_out_ctx->sample_fmt = audio_in_ctx->sample_fmt;
#if LIBAVCODEC_VERSION_CHECK(56, 8, 0, 60, 100)
  if ( !audio_out_ctx->channel_layout ) {
    Debug(3, "Correcting channel layout from (%d) to (%d)",
        audio_out_ctx->channel_layout,
        av_get_default_channel_layout(audio_out_ctx->channels)
        );
      audio_out_ctx->channel_layout = av_get_default_channel_layout(audio_out_ctx->channels);
  }
#endif
  if ( audio_out_codec->supported_samplerates ) {
    int found = 0;
    for ( unsigned int i = 0; audio_out_codec->supported_samplerates[i]; i++ ) {
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
    Debug(2, "Encoder does not support sample format %s, setting to FLTP",
          av_get_sample_fmt_name(audio_out_ctx->sample_fmt));
    audio_out_ctx->sample_fmt = AV_SAMPLE_FMT_FLTP;
  }

  audio_out_ctx->time_base = (AVRational){1, audio_out_ctx->sample_rate};

  AVDictionary *opts = NULL;
  if ( (ret = av_dict_set(&opts, "strict", "experimental", 0)) < 0 ) {
    Error("Couldn't set experimental");
  }
  ret = avcodec_open2(audio_out_ctx, audio_out_codec, &opts);
  av_dict_free(&opts);
  if ( ret < 0 ) {
    Error("could not open codec (%d) (%s)",
        ret, av_make_error_string(ret).c_str());
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

  Debug(3,
        "Time bases: AUDIO in stream (%d/%d) in codec: (%d/%d) out "
        "stream: (%d/%d) out codec (%d/%d)",
        audio_in_stream->time_base.num, audio_in_stream->time_base.den,
        audio_in_ctx->time_base.num, audio_in_ctx->time_base.den,
        audio_out_stream->time_base.num, audio_out_stream->time_base.den,
        audio_out_ctx->time_base.num, audio_out_ctx->time_base.den);

  Debug(1,
        "Audio in bit_rate (%d) sample_rate(%d) channels(%d) fmt(%d) "
        "layout(%d) frame_size(%d)",
        audio_in_ctx->bit_rate, audio_in_ctx->sample_rate,
        audio_in_ctx->channels, audio_in_ctx->sample_fmt,
        audio_in_ctx->channel_layout, audio_in_ctx->frame_size);
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
  if ( !(fifo = av_audio_fifo_alloc(
          audio_out_ctx->sample_fmt,
          audio_out_ctx->channels, 1)) ) {
    Error("Could not allocate FIFO");
    return false;
  }
  resample_ctx = swr_alloc_set_opts(NULL,
      audio_out_ctx->channel_layout,
      audio_out_ctx->sample_fmt,
      audio_out_ctx->sample_rate,
      audio_in_ctx->channel_layout,
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
  Debug(1,"Success setting up SWRESAMPLE");
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
#if LIBAVCODEC_VERSION_CHECK(56, 8, 0, 60, 100)
  out_frame->channels = audio_out_ctx->channels;
#endif
  out_frame->channel_layout = audio_out_ctx->channel_layout;
  out_frame->sample_rate = audio_out_ctx->sample_rate;

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
  if ( avcodec_fill_audio_frame(
        out_frame, audio_out_ctx->channels,
        audio_out_ctx->sample_fmt,
        (const uint8_t *)converted_in_samples,
        audioSampleBuffer_size, 0) < 0 ) {
    Error("Could not allocate converted in sample pointers");
    return false;
  }

  return true;
#endif
}  // end bool VideoStore::setup_resampler()

int VideoStore::writeVideoFramePacket(AVPacket *ipkt) {
  av_init_packet(&opkt);

  dumpPacket(video_in_stream, ipkt, "input packet");

  int64_t duration;
  if ( ipkt->duration != AV_NOPTS_VALUE ) {
    duration = av_rescale_q(
        ipkt->duration,
        video_in_stream->time_base,
        video_out_stream->time_base);
    Debug(1, "duration from ipkt: %" PRId64 ") => (%" PRId64 ") (%d/%d) (%d/%d)",
        ipkt->duration,
        duration,
        video_in_stream->time_base.num,
        video_in_stream->time_base.den,
        video_out_stream->time_base.num,
        video_out_stream->time_base.den
        );
  } else {
    duration = av_rescale_q(
          ipkt->pts - video_last_pts,
          video_in_stream->time_base,
          video_out_stream->time_base);
    Debug(1, "duration calc: pts(%" PRId64 ") - last_pts(%" PRId64 ") = (%" PRId64 ") => (%" PRId64 ")",
        ipkt->pts,
        video_last_pts,
        ipkt->pts - video_last_pts,
        duration
        );
    if ( duration <= 0 ) {
      // Why are we setting the duration to 1? 
      duration = ipkt->duration ? ipkt->duration : av_rescale_q(1,video_in_stream->time_base, video_out_stream->time_base);
    }
  }
  opkt.duration = duration;

  // Scale the PTS of the outgoing packet to be the correct time base
  if ( ipkt->pts != AV_NOPTS_VALUE ) {

    if ( (!video_first_pts) && (ipkt->pts >= 0) ) {
      // This is the first packet.
      opkt.pts = 0;
      Debug(2, "Starting video first_pts will become %" PRId64, ipkt->pts);
      video_first_pts = ipkt->pts;
#if 1
      if ( audio_in_stream ) {
        // Since audio starts after the start of the video, need to set this here.
        audio_first_pts = av_rescale_q(
            ipkt->pts,
            video_in_stream->time_base,
            audio_in_stream->time_base
            );
        Debug(2, "Starting audio first_pts will become %" PRId64, audio_first_pts);
      }
#endif
    } else {
      opkt.pts = av_rescale_q(
          ipkt->pts - video_first_pts,
          video_in_stream->time_base,
          video_out_stream->time_base
          );
    }
    Debug(3, "opkt.pts = %" PRId64 " from ipkt->pts(%" PRId64 ") - first_pts(%" PRId64 ")",
        opkt.pts, ipkt->pts, video_first_pts);
    video_last_pts = ipkt->pts;
  } else {
    Debug(3, "opkt.pts = undef");
    opkt.pts = AV_NOPTS_VALUE;
// can't set 0, it will get rejected
    //AV_NOPTS_VALUE;
  }
  // Just because the in stream wraps, doesn't mean the out needs to.
	// Really, if we are limiting ourselves to 10min segments I can't imagine every wrapping in the out.
	// So need to handle in wrap, without causing out wrap.

  if ( ipkt->dts != AV_NOPTS_VALUE ) {
    if ( !video_first_dts ) {
     // && ( ipkt->dts >= 0 ) ) {
      // This is the first packet.
      opkt.dts = 0;
      Debug(1, "Starting video first_dts will become (%" PRId64 ")", ipkt->dts);
      video_first_dts = ipkt->dts;
#if 1
      if ( audio_in_stream ) {
        // Since audio starts after the start of the video, need to set this here.
        audio_first_dts = av_rescale_q(
            ipkt->dts,
            video_in_stream->time_base,
            audio_in_stream->time_base
            );
        Debug(2, "Starting audio first dts will become %" PRId64, audio_first_dts);
      }
#endif
    } else {
      opkt.dts = av_rescale_q(
          ipkt->dts - video_first_dts,
          video_in_stream->time_base,
          video_out_stream->time_base
          );
      Debug(3, "opkt.dts = %" PRId64 " from ipkt->dts(%" PRId64 ") - first_pts(%" PRId64 ")",
          opkt.dts, ipkt->dts, video_first_dts);
    }
    if ( (opkt.pts != AV_NOPTS_VALUE) && (opkt.dts > opkt.pts) ) {
      Debug(1,
          "opkt.dts(%" PRId64 ") must be <= opkt.pts(%" PRId64 "). Decompression must happen "
          "before presentation.",
          opkt.dts, opkt.pts);
      opkt.dts = opkt.pts;
    }
  } else {
    Debug(3, "opkt.dts = undef");
    opkt.dts = video_out_stream->cur_dts;
  }

	if ( opkt.dts < video_out_stream->cur_dts ) {
		Debug(1, "Fixing non-monotonic dts/pts dts %" PRId64 " pts %" PRId64 " stream %" PRId64,
				opkt.dts, opkt.pts, video_out_stream->cur_dts);
		opkt.dts = video_out_stream->cur_dts;
		if ( opkt.dts > opkt.pts ) {
			opkt.pts = opkt.dts;
		}
	}

  opkt.flags = ipkt->flags;
  opkt.pos = -1;
  opkt.data = ipkt->data;
  opkt.size = ipkt->size;
  write_packet(&opkt, video_out_stream);
  zm_av_packet_unref(&opkt);

  return 0;
}  // end int VideoStore::writeVideoFramePacket( AVPacket *ipkt )

int VideoStore::writeAudioFramePacket(AVPacket *ipkt) {

  if ( !audio_out_stream ) {
    Debug(1, "Called writeAudioFramePacket when no audio_out_stream");
    return 0;  // FIXME -ve return codes do not free packet in ffmpeg_camera at
               // the moment
  }
  dumpPacket(audio_in_stream, ipkt, "input packet");

  if ( audio_out_codec ) {
    if ( ( ret = zm_receive_frame(audio_in_ctx, in_frame, *ipkt) ) < 0 ) {
      Debug(3, "Not ready to receive frame");
      return 0;
    }

    zm_dump_frame(in_frame, "In frame from decode");
    if ( in_frame->pts != AV_NOPTS_VALUE ) {
      if ( !audio_first_pts ) {
        audio_first_pts = in_frame->pts;
        Debug(1, "No audio_first_pts setting to %" PRId64, audio_first_pts);
        in_frame->pts = 0;
      } else {
        // out_frame_pts is in codec->timebase, audio_first_pts is in packet timebase.
        in_frame->pts = in_frame->pts - audio_first_pts;
        zm_dump_frame(in_frame, "in frame after pts adjustment");
      }
    } else {
      // sending AV_NOPTS_VALUE doesn't really work but we seem to get it in ffmpeg 2.8
      in_frame->pts = audio_next_pts;
    }

    if ( !resample_audio() ) {
      //av_frame_unref(in_frame);
      return 0;
    }

    zm_dump_frame(out_frame, "Out frame after resample");
#if 0
    // out_frame pts is in the input pkt pts... needs to be adjusted before sending to the encoder
    if ( out_frame->pts != AV_NOPTS_VALUE ) {
      if ( !audio_first_pts ) {
        audio_first_pts = out_frame->pts;
        Debug(1, "No audio_first_pts setting to %" PRId64, audio_first_pts);
        out_frame->pts = 0;
      } else {
        // out_frame_pts is in codec->timebase, audio_first_pts is in packet timebase.
        out_frame->pts = out_frame->pts - audio_first_pts;
        zm_dump_frame(out_frame, "Out frame after pts adjustment");
      }
      //
    } else {
      // sending AV_NOPTS_VALUE doesn't really work but we seem to get it in ffmpeg 2.8
      out_frame->pts = audio_next_pts;
    }
    audio_next_pts = out_frame->pts + out_frame->nb_samples;
#endif

    av_init_packet(&opkt);
    if ( !zm_send_frame(audio_out_ctx, out_frame, opkt) ) {
      return 0;
    }

    dumpPacket(audio_out_stream, &opkt, "raw opkt");
    Debug(1, "Duration before %d in %d/%d", opkt.duration,
        audio_out_ctx->time_base.num,
        audio_out_ctx->time_base.den);

    opkt.duration = av_rescale_q(
        opkt.duration,
        audio_out_ctx->time_base,
        audio_out_stream->time_base);
    Debug(1, "Duration after %d in %d/%d", opkt.duration,
        audio_out_stream->time_base.num,
        audio_out_stream->time_base.den);
    // Scale the PTS of the outgoing packet to be the correct time base
#if 0
    if ( ipkt->pts != AV_NOPTS_VALUE ) {
      if ( !audio_first_pts ) {
        opkt.pts = 0;
        audio_first_pts = ipkt->pts;
        Debug(1, "No audio_first_pts");
      } else {
        opkt.pts = av_rescale_q(
            opkt.pts,
            audio_out_ctx->time_base,
            audio_out_stream->time_base);
            opkt.pts -= audio_first_pts;
        Debug(2, "audio opkt.pts = %" PRId64 " from first_pts %" PRId64,
            opkt.pts, audio_first_pts);
      }
    } else {
      Debug(2, "opkt.pts = undef");
      opkt.pts = AV_NOPTS_VALUE;
    }

    if ( opkt.dts != AV_NOPTS_VALUE ) {
      if ( !audio_first_dts ) {
        opkt.dts = 0;
        audio_first_dts = opkt.dts;
      } else {
        opkt.dts = av_rescale_q(
            opkt.dts,
            audio_out_ctx->time_base,
            audio_out_stream->time_base);
        opkt.dts -= audio_first_dts;
        Debug(2, "audio opkt.dts = %" PRId64 " from first_dts %" PRId64,
            opkt.dts, audio_first_dts);
      }
      audio_last_dts = opkt.dts;
    } else {
      opkt.dts = AV_NOPTS_VALUE;
    }
#else 
	opkt.pts = av_rescale_q(
            opkt.pts,
            audio_out_ctx->time_base,
            audio_out_stream->time_base);
	opkt.dts = av_rescale_q(
            opkt.dts,
            audio_out_ctx->time_base,
            audio_out_stream->time_base);
#endif

        write_packet(&opkt, audio_out_stream);
    zm_av_packet_unref(&opkt);

#if LIBAVCODEC_VERSION_CHECK(57, 64, 0, 64, 0)
    // While the encoder still has packets for us
    while ( !avcodec_receive_packet(audio_out_ctx, &opkt) ) {
      opkt.pts = av_rescale_q(
          opkt.pts,
          audio_out_ctx->time_base,
          audio_out_stream->time_base);
      opkt.dts = av_rescale_q(
          opkt.dts,
          audio_out_ctx->time_base,
          audio_out_stream->time_base);

      dumpPacket(audio_out_stream, &opkt, "raw opkt");
      Debug(1, "Duration before %d in %d/%d", opkt.duration,
          audio_out_ctx->time_base.num,
          audio_out_ctx->time_base.den);

      opkt.duration = av_rescale_q(
          opkt.duration,
          audio_out_ctx->time_base,
          audio_out_stream->time_base);
      Debug(1, "Duration after %d in %d/%d", opkt.duration,
          audio_out_stream->time_base.num,
          audio_out_stream->time_base.den);
      write_packet(&opkt, audio_out_stream);
    }
#endif
    zm_av_packet_unref(&opkt);

  } else {
    Debug(2,"copying");
    av_init_packet(&opkt);
    opkt.data = ipkt->data;
    opkt.size = ipkt->size;
    opkt.flags = ipkt->flags;

    if ( ipkt->duration && (ipkt->duration != AV_NOPTS_VALUE) ) {
      opkt.duration = av_rescale_q(
          ipkt->duration,
          audio_in_stream->time_base,
          audio_out_stream->time_base);
    }
    // Scale the PTS of the outgoing packet to be the correct time base
    if ( ipkt->pts != AV_NOPTS_VALUE ) {
      if ( !audio_first_pts ) {
        opkt.pts = 0;
        audio_first_pts = ipkt->pts;
        Debug(1, "No audio_first_pts");
      } else {
        opkt.pts = av_rescale_q(
            ipkt->pts - audio_first_pts,
            audio_in_stream->time_base,
            audio_out_stream->time_base);
        Debug(2, "audio opkt.pts = %" PRId64 " from ipkt->pts(%" PRId64 ") - first_pts(%" PRId64 ")",
            opkt.pts, ipkt->pts, audio_first_pts);
      }
    } else {
      Debug(2, "opkt.pts = undef");
      opkt.pts = AV_NOPTS_VALUE;
    }

    if ( ipkt->dts != AV_NOPTS_VALUE ) {
      if ( !audio_first_dts ) {
        opkt.dts = 0;
        audio_first_dts = ipkt->dts;
      } else {
        opkt.dts = av_rescale_q(
            ipkt->dts - audio_first_dts,
            audio_in_stream->time_base,
            audio_out_stream->time_base);
        Debug(2, "opkt.dts = %" PRId64 " from ipkt.dts(%" PRId64 ") - first_dts(%" PRId64 ")",
            opkt.dts, ipkt->dts, audio_first_dts);
      }
      audio_last_dts = ipkt->dts;
    } else {
      opkt.dts = AV_NOPTS_VALUE;
    }
    write_packet(&opkt, audio_out_stream);

    zm_av_packet_unref(&opkt);
  } // end if encoding or copying

  return 0;
} // end int VideoStore::writeAudioFramePacket(AVPacket *ipkt)

int VideoStore::write_packet(AVPacket *pkt, AVStream *stream) {
  pkt->pos = -1;
  pkt->stream_index = stream->index;

  if ( pkt->dts < stream->cur_dts ) {
    Warning("non increasing dts, fixing");
    pkt->dts = stream->cur_dts;
    if ( pkt->dts > pkt->pts ) {
      Debug(1,
          "pkt.dts(%" PRId64 ") must be <= pkt.pts(%" PRId64 ")."
          "Decompression must happen before presentation.",
          pkt->dts, pkt->pts);
      pkt->pts = pkt->dts;
    }
  } else if ( pkt->dts > pkt->pts ) {
    Debug(1,
          "pkt.dts(%" PRId64 ") must be <= pkt.pts(%" PRId64 ")."
          "Decompression must happen before presentation.",
          pkt->dts, pkt->pts);
    pkt->dts = pkt->pts;
  }

  dumpPacket(stream, pkt, "finished pkt");

  ret = av_interleaved_write_frame(oc, pkt);
  if ( ret != 0 ) {
    Error("Error writing packet: %s",
          av_make_error_string(ret).c_str());
  } else {
    Debug(2, "Success writing packet");
  }
} // end int VideoStore::write_packet(AVPacket *pkt, AVStream *stream)

int VideoStore::resample_audio() {
  // Resample the in_frame into the audioSampleBuffer until we process the whole
  // decoded data. Note: pts does not survive resampling or converting
#if defined(HAVE_LIBSWRESAMPLE) || defined(HAVE_LIBAVRESAMPLE)
#if defined(HAVE_LIBSWRESAMPLE)
  Debug(2, "Converting %d to %d samples using swresample",
      in_frame->nb_samples, out_frame->nb_samples);
  ret = swr_convert_frame(resample_ctx, out_frame, in_frame);
  if ( ret < 0 ) {
    Error("Could not resample frame (error '%s')",
        av_make_error_string(ret).c_str());
    return 0;
  }
  zm_dump_frame(out_frame, "Out frame after resample");
  out_frame->pkt_duration = in_frame->pkt_duration; // resampling doesn't alter duration

  ret = av_audio_fifo_realloc(fifo, av_audio_fifo_size(fifo) + out_frame->nb_samples);
  if ( ret < 0 ) {
    Error("Could not reallocate FIFO");
    return 0;
  }
  /** Store the new samples in the FIFO buffer. */
  ret = av_audio_fifo_write(fifo, (void **)out_frame->data, out_frame->nb_samples);
  if ( ret < out_frame->nb_samples ) {
    Error("Could not write data to FIFO. %d written, expecting %d. Reason %s",
        ret, out_frame->nb_samples, av_make_error_string(ret).c_str());
    return 0;
  }

  // Reset frame_size to output_frame_size
  int frame_size = audio_out_ctx->frame_size;

  // AAC requires 1024 samples per encode.  Our input tends to be something else, so need to buffer them.
  if ( frame_size > av_audio_fifo_size(fifo) ) {
    Debug(1, "Not enough samples in fifo for AAC codec frame_size %d > fifo size %d", 
         frame_size, av_audio_fifo_size(fifo));
    return 0;
  }

  if ( av_audio_fifo_read(fifo, (void **)out_frame->data, frame_size) < frame_size ) {
    Error("Could not read data from FIFO");
    return 0;
  }
  out_frame->nb_samples = frame_size;
  zm_dump_frame(out_frame, "Out frame after fifo read");
  // resampling changes the duration because the timebase is 1/samples
  // I think we should be dealing in codec timebases not stream
  if ( in_frame->pts != AV_NOPTS_VALUE ) {
    out_frame->pts = av_rescale_q(
        in_frame->pts,
        audio_in_ctx->time_base,
        audio_out_ctx->time_base);
  }
  zm_dump_frame(out_frame, "Out frame after timestamp conversion");
#else
#if defined(HAVE_LIBAVRESAMPLE)
  ret = avresample_convert(resample_ctx, NULL, 0, 0, in_frame->data,
                            0, in_frame->nb_samples);
  if ( ret < 0 ) {
    Error("Could not resample frame (error '%s')",
        av_make_error_string(ret).c_str());
    return 0;
  }

  int frame_size = audio_out_ctx->frame_size;

  int samples_available = avresample_available(resample_ctx);
  if ( samples_available < frame_size ) {
    Debug(1, "Not enough samples yet (%d)", samples_available);
    return 0;
  }

  // Read a frame audio data from the resample fifo
  if ( avresample_read(resample_ctx, out_frame->data, frame_size) !=
      frame_size) {
    Warning("Error reading resampled audio.");
    return 0;
  }
#endif
#endif
#else
    Error("Have audio codec but no resampler?!");
    return 0;
#endif
  return 1;
} // end int VideoStore::resample_audio
