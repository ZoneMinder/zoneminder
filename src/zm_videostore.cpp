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


#include "zm.h"
#include "zm_videostore.h"

#include <stdlib.h>
#include <string.h>
#include <cinttypes>

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

  int ret = avformat_alloc_output_context2(&oc, NULL, NULL, filename);
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
  max_stream_index = video_out_stream->index;

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
#if LIBAVCODEC_VERSION_CHECK(57, 64, 0, 64, 0)
  if ( video_in_stream->r_frame_rate.num ) {
    Debug(3,"Copying r_frame_rate (%d/%d) to out (%d/%d)",
        video_in_stream->r_frame_rate.num,
        video_in_stream->r_frame_rate.den ,
        video_out_stream->r_frame_rate.num,
        video_out_stream->r_frame_rate.den
        );
    video_out_stream->r_frame_rate = video_in_stream->r_frame_rate;
  }
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
  /* I'm not entirely sure that this is a good idea.  We may have to do it someday but really only when transcoding
   * * think what I was trying to achieve here was to have zm_dump_codecpar output nice info
   * */
#if 0
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
  ret = avcodec_parameters_from_context(video_out_stream->codecpar, video_out_ctx);
  if ( ret < 0 ) {
    Error("Could not initialize video_out_ctx parameters");
    return;
  } else {
    zm_dump_codec(video_out_ctx);
  }
#else
  ret = avcodec_parameters_from_context(video_out_stream->codecpar, video_in_ctx);
  if ( ret < 0 ) {
    Error("Could not initialize video_out_ctx parameters");
    return;
  } else {
    zm_dump_codec(video_out_ctx);
  }
#endif
  zm_dump_codecpar(video_in_stream->codecpar);
  zm_dump_codecpar(video_out_stream->codecpar);
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
  fifo = NULL;
#endif
  video_first_pts = 0;
  video_first_dts = 0;

  audio_first_pts = 0;
  audio_first_dts = 0;

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

    // We will assume that subsequent stream allocations will increase the index
    max_stream_index = audio_out_stream->index;
  }  // end if audio_in_stream

  //max_stream_index is 0-based, so add 1
  next_dts = new int64_t[max_stream_index+1];
  for ( int i = 0; i <= max_stream_index; i++ ) {
    next_dts[i] = 0;
  }
}  // VideoStore::VideoStore

bool VideoStore::open() {
  int ret;
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
  av_dict_set(&opts, "movflags", "frag_keyframe+empty_moov", 0);
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

      int frame_size = audio_out_ctx->frame_size;
      /*
       * At the end of the file, we pass the remaining samples to
       * the encoder. */
      while ( zm_resample_get_delay(resample_ctx, audio_out_ctx->sample_rate) ) {
        zm_resample_audio(resample_ctx, NULL, out_frame);

        if ( zm_add_samples_to_fifo(fifo, out_frame) ) {
          // Should probably set the frame size to what is reported FIXME
          if ( zm_get_samples_from_fifo(fifo, out_frame) ) {
            if ( zm_send_frame_receive_packet(audio_out_ctx, out_frame, pkt) ) {
              pkt.stream_index = audio_out_stream->index;

              av_packet_rescale_ts(&pkt,
                  audio_out_ctx->time_base,
                  audio_out_stream->time_base);
              write_packet(&pkt, audio_out_stream);
            }
          }  // end if data returned from fifo
        }

      } // end if have buffered samples in the resampler

      Debug(2, "av_audio_fifo_size = %d", av_audio_fifo_size(fifo));
      while ( av_audio_fifo_size(fifo) > 0 ) {
        /* Take one frame worth of audio samples from the FIFO buffer,
         * encode it and write it to the output file. */

        Debug(1, "Remaining samples in fifo for AAC codec frame_size %d > fifo size %d",
            frame_size, av_audio_fifo_size(fifo));

        // SHould probably set the frame size to what is reported FIXME
        if ( av_audio_fifo_read(fifo, (void **)out_frame->data, frame_size) ) {
          if ( zm_send_frame_receive_packet(audio_out_ctx, out_frame, pkt) ) {
            pkt.stream_index = audio_out_stream->index;

            av_packet_rescale_ts(&pkt,
                audio_out_ctx->time_base,
                audio_out_stream->time_base);
            write_packet(&pkt, audio_out_stream);
          }
        }  // end if data returned from fifo
      }  // end while still data in the fifo

#if LIBAVCODEC_VERSION_CHECK(57, 64, 0, 64, 0)
      // Put encoder into flushing mode
      avcodec_send_frame(audio_out_ctx, NULL);
#endif

      while (1) {
        if ( ! zm_receive_packet(audio_out_ctx, pkt) ) {
          Debug(1, "No more packets");
          break;
        }

        dumpPacket(&pkt, "raw from encoder");
        av_packet_rescale_ts(&pkt, audio_out_ctx->time_base, audio_out_stream->time_base);
        dumpPacket(audio_out_stream, &pkt, "writing flushed packet");
        write_packet(&pkt, audio_out_stream);
        zm_av_packet_unref(&pkt);
      }  // while have buffered frames
    }  // end if audio_out_codec

    // Flush Queues
    Debug(1, "Flushing interleaved queues");
    av_interleaved_write_frame(oc, NULL);

    Debug(1, "Writing trailer");
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
      if ( fifo ) {
        av_audio_fifo_free(fifo);
        fifo = NULL;
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
  delete[] next_dts;
} // VideoStore::~VideoStore()

bool VideoStore::setup_resampler() {
#if !defined(HAVE_LIBSWRESAMPLE) && !defined(HAVE_LIBAVRESAMPLE)
  Error(
     "Not built with resample library. "
     "Cannot do audio conversion to AAC");
  return false;
#else
  int ret;

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
  audio_in_codec = reinterpret_cast<const AVCodec *>(audio_in_ctx->codec);
  if ( !audio_in_codec ) {
    audio_in_codec = avcodec_find_decoder(audio_in_stream->codec->codec_id);
  }
  if ( !audio_in_codec ) {
    return false;
  }
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
      Debug(3, "Sample rate is good %d", audio_out_ctx->sample_rate);
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
  zm_dump_codec(audio_out_ctx);

#if LIBAVCODEC_VERSION_CHECK(57, 64, 0, 64, 0)
  ret = avcodec_parameters_from_context(
      audio_out_stream->codecpar, audio_out_ctx);
  if ( ret < 0 ) {
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

  if ( !(fifo = av_audio_fifo_alloc(
          audio_out_ctx->sample_fmt,
          audio_out_ctx->channels, 1)) ) {
    Error("Could not allocate FIFO");
    return false;
  }
#if defined(HAVE_LIBSWRESAMPLE)
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
  converted_in_samples = reinterpret_cast<uint8_t *>(av_malloc(audioSampleBuffer_size));

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

  dumpPacket(video_in_stream, ipkt, "video input packet");

  opkt.flags = ipkt->flags;
  opkt.data = ipkt->data;
  opkt.size = ipkt->size;
  opkt.duration = ipkt->duration;

  // Just because the in stream wraps, doesn't mean the out needs to.
  // Really, if we are limiting ourselves to 10min segments I can't imagine every wrapping in the out.
  // So need to handle in wrap, without causing out wrap.
  // The cameras that Icon has seem to do EOF instead of wrapping

  if ( ipkt->dts != AV_NOPTS_VALUE ) {
    if ( !video_first_dts ) {
      Debug(2, "Starting video first_dts will become %" PRId64, ipkt->dts);
      video_first_dts = ipkt->dts;
    }
    opkt.dts = ipkt->dts - video_first_dts;
  } else {
    opkt.dts = next_dts[video_out_stream->index] ? av_rescale_q(next_dts[video_out_stream->index], video_out_stream->time_base, video_in_stream->time_base) : 0;
    Debug(3, "Setting dts to video_next_dts %" PRId64 " from %" PRId64, opkt.dts, next_dts[video_out_stream->index]);
  }
  if ( ipkt->pts != AV_NOPTS_VALUE ) {
    opkt.pts = ipkt->pts - video_first_dts;
  } else {
    opkt.pts = AV_NOPTS_VALUE;
  }
  av_packet_rescale_ts(&opkt, video_in_stream->time_base, video_out_stream->time_base);

  dumpPacket(video_out_stream, &opkt, "after pts adjustment");
  write_packet(&opkt, video_out_stream);

  zm_av_packet_unref(&opkt);

  return 0;
}  // end int VideoStore::writeVideoFramePacket( AVPacket *ipkt )

int VideoStore::writeAudioFramePacket(AVPacket *ipkt) {
  int ret;

  if ( !audio_out_stream ) {
    Debug(1, "Called writeAudioFramePacket when no audio_out_stream");
    return 0;  // FIXME -ve return codes do not free packet in ffmpeg_camera at
               // the moment
  }
  dumpPacket(audio_in_stream, ipkt, "input packet");

  if ( !audio_first_dts ) {
    audio_first_dts = ipkt->dts;
    audio_next_pts = audio_out_ctx->frame_size;
  }

  // Need to adjust pts before feeding to decoder.... should really copy the pkt instead of modifying it
  ipkt->pts -= audio_first_dts;
  ipkt->dts -= audio_first_dts;
  dumpPacket(audio_in_stream, ipkt, "after pts adjustment");

  if ( audio_out_codec ) {
    // I wonder if we can get multiple frames per packet? Probably
    ret = zm_send_packet_receive_frame(audio_in_ctx, in_frame, *ipkt);
    if ( ret < 0 ) {
      Debug(3, "failed to receive frame code: %d", ret);
      return 0;
    }
    zm_dump_frame(in_frame, "In frame from decode");

    AVFrame *input_frame = in_frame;

    while ( zm_resample_audio(resample_ctx, input_frame, out_frame) ) {
      //out_frame->pkt_duration = in_frame->pkt_duration; // resampling doesn't alter duration
      if ( zm_add_samples_to_fifo(fifo, out_frame) <= 0 )
        break;

      // We put the samples into the fifo so we are basically resetting the frame
      out_frame->nb_samples = audio_out_ctx->frame_size;
      
      if ( zm_get_samples_from_fifo(fifo, out_frame) <= 0 )
        break;

      out_frame->pts = audio_next_pts;
      audio_next_pts += out_frame->nb_samples;

      zm_dump_frame(out_frame, "Out frame after resample");

      av_init_packet(&opkt);
      if ( zm_send_frame_receive_packet(audio_out_ctx, out_frame, opkt) <= 0 )
        break;

      // Scale the PTS of the outgoing packet to be the correct time base
      av_packet_rescale_ts(&opkt,
          audio_out_ctx->time_base,
          audio_out_stream->time_base);

      write_packet(&opkt, audio_out_stream);
      zm_av_packet_unref(&opkt);

      if ( zm_resample_get_delay(resample_ctx, out_frame->sample_rate) < out_frame->nb_samples)
        break;
      // This will send a null frame, emptying out the resample buffer
      input_frame = NULL;
    } // end while there is data in the resampler

  } else {
    Debug(2,"copying");
    av_init_packet(&opkt);
    opkt.data = ipkt->data;
    opkt.size = ipkt->size;
    opkt.flags = ipkt->flags;

    opkt.duration = ipkt->duration;
    opkt.pts = ipkt->pts;
    opkt.dts = ipkt->dts;
    av_packet_rescale_ts(&opkt, audio_in_stream->time_base, audio_out_stream->time_base);
    write_packet(&opkt, audio_out_stream);

    zm_av_packet_unref(&opkt);
  }  // end if encoding or copying

  return 0;
}  // end int VideoStore::writeAudioFramePacket(AVPacket *ipkt)

int VideoStore::write_packet(AVPacket *pkt, AVStream *stream) {
  pkt->pos = -1;
  pkt->stream_index = stream->index;

  if ( pkt->dts == AV_NOPTS_VALUE ) {
    Debug(1, "undef dts, fixing by setting to stream cur_dts %" PRId64, stream->cur_dts);
    pkt->dts = stream->cur_dts;
  } else if ( pkt->dts < stream->cur_dts ) {
    Debug(1, "non increasing dts, fixing. our dts %" PRId64 " stream cur_dts %" PRId64, pkt->dts, stream->cur_dts);
    pkt->dts = stream->cur_dts;
  } 

  if ( pkt->dts > pkt->pts ) {
    Debug(1,
          "pkt.dts(%" PRId64 ") must be <= pkt.pts(%" PRId64 ")."
          "Decompression must happen before presentation.",
          pkt->dts, pkt->pts);
    pkt->pts = pkt->dts;
  }

  dumpPacket(stream, pkt, "finished pkt");
  next_dts[stream->index] = opkt.dts + opkt.duration;
  Debug(3, "video_next_dts has become %" PRId64, next_dts[stream->index]);

  int ret = av_interleaved_write_frame(oc, pkt);
  if ( ret != 0 ) {
    Error("Error writing packet: %s",
          av_make_error_string(ret).c_str());
  } else {
    Debug(2, "Success writing packet");
  }
  return ret;
}  // end int VideoStore::write_packet(AVPacket *pkt, AVStream *stream)
