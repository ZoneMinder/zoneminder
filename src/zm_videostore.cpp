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

VideoStore::CodecData VideoStore::codec_data[] = {
  { AV_CODEC_ID_H264, "h264", "h264_omx", AV_PIX_FMT_YUV420P },
  { AV_CODEC_ID_H264, "h264", "h264", AV_PIX_FMT_YUV420P },
  { AV_CODEC_ID_H264, "h264", "libx264", AV_PIX_FMT_YUV420P },
  { AV_CODEC_ID_MJPEG, "mjpeg", "mjpeg", AV_PIX_FMT_YUVJ422P },
};

VideoStore::VideoStore(
    const char *filename_in,
    const char *format_in,
    AVStream *p_video_in_stream,
    AVStream *p_audio_in_stream,
    Monitor *p_monitor
    ) {

  monitor = p_monitor;
  video_in_stream = p_video_in_stream;
  video_in_stream_index = -1;
  audio_in_stream = p_audio_in_stream;
  audio_in_stream_index = -1;
  filename = filename_in;
  format = format_in;

  packets_written = 0;
  frame_count = 0;
  in_frame = NULL;

  video_in_frame = NULL;
  video_in_ctx = NULL;
  // In future, we should just pass in the codec context instead of the stream.  Don't really need the stream.
  video_in_ctx = video_in_stream->codec;

  video_out_ctx = NULL;
  video_out_codec = NULL;
  video_out_stream = NULL;

  converted_in_samples = NULL;
  audio_out_codec = NULL;
  audio_in_codec = NULL;
  audio_in_ctx = NULL;
  audio_out_stream = NULL;
  audio_out_ctx = NULL;

  out_frame = NULL;
#if defined(HAVE_LIBSWRESAMPLE) || defined(HAVE_LIBAVRESAMPLE)
  resample_ctx = NULL;
#if defined(HAVE_LIBSWRESAMPLE)
  fifo = NULL;
#endif
#endif
  FFMPEGInit();

  video_start_pts = 0;
  audio_next_pts = 0;
  out_format = NULL;
  oc = NULL;
}  // VideoStore::VideoStore

bool VideoStore::open() {
  Info("Opening video storage stream %s format: %s", filename, format);

  int ret = avformat_alloc_output_context2(&oc, NULL, NULL, filename);
  if ( ret < 0 ) {
    Warning(
        "Could not create video storage stream %s as no out ctx"
        " could be assigned based on filename: %s",
        filename, av_make_error_string(ret).c_str());
  }

  // Couldn't deduce format from filename, trying from format name
  if ( !oc ) {
    avformat_alloc_output_context2(&oc, NULL, format, filename);
    if ( !oc ) {
      Error(
          "Could not create video storage stream %s as no out ctx"
          " could not be assigned based on filename or format %s",
          filename, format);
      return false;
    }
  } // end if ! oc

  AVDictionary *pmetadata = NULL;
  ret = av_dict_set(&pmetadata, "title", "Zoneminder Security Recording", 0);
  if ( ret < 0 ) Warning("%s:%d: title set failed", __FILE__, __LINE__);

  oc->metadata = pmetadata;
  out_format = oc->oformat;
  out_format->flags |= AVFMT_TS_NONSTRICT; // allow non increasing dts

  if ( video_in_stream ) {
    video_in_stream_index = video_in_stream->index;
#if LIBAVCODEC_VERSION_CHECK(57, 64, 0, 64, 0)
    video_in_ctx = avcodec_alloc_context3(NULL);
    Debug(2, "copy to video_in_context");
    ret = avcodec_parameters_to_context(video_in_ctx, video_in_stream->codecpar);
    if ( ret < 0 ) {
      Error("Couldn't copy params to context");
      return false;
    } else {
      zm_dump_codecpar(video_in_stream->codecpar);
    }
#else
    video_in_ctx = video_in_stream->codec;
    Debug(2,"Copied video context from input stream");
    zm_dump_codec(video_in_ctx);
#endif
  } else {
    // FIXME delete?
    Debug(2, "No input ctx");
    video_in_ctx = avcodec_alloc_context3(NULL);
    video_in_stream_index = 0;
  }

  video_out_ctx = avcodec_alloc_context3(NULL);
  if ( oc->oformat->flags & AVFMT_GLOBALHEADER ) {
#if LIBAVCODEC_VERSION_CHECK(56, 35, 0, 64, 0)
    video_out_ctx->flags |= AV_CODEC_FLAG_GLOBAL_HEADER;
#else
    video_out_ctx->flags |= CODEC_FLAG_GLOBAL_HEADER;
#endif
  }
  if ( !video_out_ctx->codec_tag ) {
    video_out_ctx->codec_tag =
        av_codec_get_tag(oc->oformat->codec_tag, video_in_ctx->codec_id);
    Debug(2, "No codec_tag, setting to %d", video_out_ctx->codec_tag);
  }
  int wanted_codec = monitor->OutputCodec();
  if ( !wanted_codec ) {
    // default to h264
    wanted_codec = AV_CODEC_ID_H264;
  }
  max_stream_index = video_out_stream->index;

  // FIXME Should check that we are set to passthrough.  Might be same codec, but want privacy overlays
  if ( video_in_stream && ( video_in_ctx->codec_id == wanted_codec ) ) {
    // Copy params from instream to ctx
#if LIBAVCODEC_VERSION_CHECK(57, 64, 0, 64, 0)
    ret = avcodec_parameters_to_context(video_out_ctx, video_in_stream->codecpar);
#else
    ret = avcodec_copy_context(video_out_ctx, video_in_ctx);
#endif
    if ( ret < 0 ) {
      Error("Could not initialize ctx parameteres");
      return false;
    }
    //video_out_ctx->time_base = (AVRational){1, 1000000}; // microseconds as base frame rate
    video_out_ctx->time_base = video_in_ctx->time_base;
    // Fix deprecated formats
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
    // Only set orientation if doing passthrough, otherwise the frame image will be rotated
    Monitor::Orientation orientation = monitor->getOrientation();
    if ( orientation ) {
      Debug(3, "Have orientation");
      if ( orientation == Monitor::ROTATE_0 ) {
      } else if ( orientation == Monitor::ROTATE_90 ) {
        ret = av_dict_set(&video_out_stream->metadata, "rotate", "90", 0);
        if ( ret < 0 ) Warning("%s:%d: title set failed", __FILE__, __LINE__);
      } else if ( orientation == Monitor::ROTATE_180 ) {
        ret = av_dict_set(&video_out_stream->metadata, "rotate", "180", 0);
        if ( ret < 0 ) Warning("%s:%d: title set failed", __FILE__, __LINE__);
      } else if ( orientation == Monitor::ROTATE_270 ) {
        ret = av_dict_set(&video_out_stream->metadata, "rotate", "270", 0);
        if ( ret < 0 ) Warning("%s:%d: title set failed", __FILE__, __LINE__);
      } else {
        Warning("Unsupported Orientation(%d)", orientation);
      }
    } // end if orientation
  } else { // Either no video in or not the desired codec
    for ( unsigned int i = 0; i < sizeof(codec_data) / sizeof(*codec_data); i++ ) {
      if ( codec_data[i].codec_id != monitor->OutputCodec() )
        continue;

      video_out_codec = avcodec_find_encoder_by_name(codec_data[i].codec_name);
      if ( ! video_out_codec ) {
        Debug(1, "Didn't find encoder for %s", codec_data[i].codec_name);
        continue;
      }

      video_out_ctx->pix_fmt = codec_data[i].pix_fmt;
      video_out_ctx->level = 32;

      // Don't have an input stream, so need to tell it what we are sending it, or are transcoding
      video_out_ctx->width = monitor->Width();
      video_out_ctx->height = monitor->Height();
      video_out_ctx->codec_type = AVMEDIA_TYPE_VIDEO;

      // Just copy them from the in, no reason to choose different
      video_out_ctx->time_base = video_in_ctx->time_base;
      if ( ! (video_out_ctx->time_base.num && video_out_ctx->time_base.den) ) {
        Debug(2,"No timebase found in video in context, defaulting to Q");
        video_out_ctx->time_base = AV_TIME_BASE_Q;
      }	
      video_out_stream->time_base =  video_in_stream ? video_in_stream->time_base : AV_TIME_BASE_Q;

      if ( video_out_ctx->codec_id == AV_CODEC_ID_H264 ) {
        video_out_ctx->max_b_frames = 1;
        if ( video_out_ctx->priv_data ) {
          //av_opt_set(video_out_ctx->priv_data, "crf", "1", AV_OPT_SEARCH_CHILDREN);
          //av_opt_set(video_out_ctx->priv_data, "preset", "ultrafast", 0);
        } else {
          Debug(2, "Not setting priv_data");
        }
      } else if ( video_out_ctx->codec_id == AV_CODEC_ID_MPEG2VIDEO ) {
        /* just for testing, we also add B frames */
        video_out_ctx->max_b_frames = 2;
      } else if ( video_out_ctx->codec_id == AV_CODEC_ID_MPEG1VIDEO ) {
        /* Needed to avoid using macroblocks in which some coeffs overflow.
         * This does not happen with normal video, it just happens here as
         * the motion of the chroma plane does not match the luma plane. */
        video_out_ctx->mb_decision = 2;
      }

      AVDictionary *opts = 0;
      std::string Options = monitor->GetEncoderOptions();
      ret = av_dict_parse_string(&opts, Options.c_str(), "=", ",#\n", 0);
      if ( ret < 0 ) {
        Warning("Could not parse ffmpeg encoder options list '%s'\n", Options.c_str());
      } else {
        AVDictionaryEntry *e = NULL;
        while ( (e = av_dict_get(opts, "", e, AV_DICT_IGNORE_SUFFIX)) != NULL ) {
          Debug(3, "Encoder Option %s=%s", e->key, e->value);
        }
      }

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
      av_dict_free(&opts);
      if ( video_out_codec ) break;

    } // end foreach codec

    if ( !video_out_codec ) {
      Error("Can't open video codec!");
#if LIBAVCODEC_VERSION_CHECK(57, 64, 0, 64, 0)
      // We allocate and copy in newer ffmpeg, so need to free it
      avcodec_free_context(&video_out_ctx);
#endif
      video_out_ctx = NULL;

      return false;
    } // end if can't open codec

    Debug(2, "Sucess opening codec");

  } // end if copying or transcoding

  if ( !video_out_ctx->codec_tag ) {
    video_out_ctx->codec_tag =
      av_codec_get_tag(oc->oformat->codec_tag, video_out_ctx->codec_id);
    Debug(2, "No codec_tag, setting to h264 ? ");
  }

  video_out_stream = avformat_new_stream(oc, video_out_codec);
  if ( !video_out_stream ) {
    Error("Unable to create video out stream");
    return false;
  }
#if LIBAVCODEC_VERSION_CHECK(57, 64, 0, 64, 0)
  ret = avcodec_parameters_from_context(video_out_stream->codecpar, video_out_ctx);
  if ( ret < 0 ) {
    Error("Could not initialize stream parameteres");
    return false;
  }
#else
  avcodec_copy_context(video_out_stream->codec, video_out_ctx);
#endif

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
    audio_in_stream_index = audio_in_stream->index;
#if LIBAVCODEC_VERSION_CHECK(57, 64, 0, 64, 0)
    audio_in_ctx = avcodec_alloc_context3(NULL);
    ret = avcodec_parameters_to_context(audio_in_ctx,
        audio_in_stream->codecpar);
    audio_in_ctx->time_base = audio_in_stream->time_base;
#else
    audio_in_ctx = audio_in_stream->codec;
#endif

    if (
#if LIBAVCODEC_VERSION_CHECK(57, 64, 0, 64, 0)
        audio_in_stream->codecpar->codec_id
#else
        audio_in_stream->codec->codec_id
#endif
       ) {

      audio_out_codec = avcodec_find_encoder(AV_CODEC_ID_AAC);
      if ( !audio_out_codec ) {
        Error("Could not find codec for AAC");
        return false;
      }

#if LIBAVCODEC_VERSION_CHECK(57, 64, 0, 64, 0)
      audio_out_stream = avformat_new_stream(oc, NULL);
      audio_out_ctx = avcodec_alloc_context3(audio_out_codec);
      if ( !audio_out_ctx ) {
        Error("could not allocate codec ctx for AAC");
        audio_out_stream = NULL;
        return false;
      }
#else
      audio_out_stream = avformat_new_stream(oc, audio_out_codec);
      audio_out_ctx = audio_out_stream->codec;
#endif
      audio_out_stream->time_base = audio_in_stream->time_base;

      if ( !setup_resampler() ) {
        return false;
      }
    } else {
      Debug(2, "Got AAC");

      audio_out_stream = avformat_new_stream(oc, NULL);
      if ( !audio_out_stream ) {
        Error("Could not allocate new stream");
        return false;
      }
      audio_out_stream->time_base = audio_in_stream->time_base;

#if LIBAVCODEC_VERSION_CHECK(57, 64, 0, 64, 0)
      // Just use the ctx to copy the parameters over
      audio_out_ctx = avcodec_alloc_context3(audio_out_codec);
      if ( !audio_out_ctx ) {
        Error("Could not allocate new output_context");
        return false;
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

  /* open the out file, if needed */
  if ( !(out_format->flags & AVFMT_NOFILE) ) {
    if ( (ret = avio_open2(&oc->pb, filename, AVIO_FLAG_WRITE, NULL, NULL) ) < 0 ) {
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
  } else if ( av_dict_count(opts) != 0 ) {
    Warning("some options not set");
  }
  if ( opts ) av_dict_free(&opts);
  if ( ret < 0 ) {
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
  int ret;
  // The codec queues data.  We need to send a flush command and out
  // whatever we get. Failures are not fatal.

  // I got crashes if the codec didn't do DELAY, so let's test for it.
  if ( video_out_ctx->codec && ( video_out_ctx->codec->capabilities & 
#if LIBAVCODEC_VERSION_CHECK(57, 64, 0, 64, 0)
        AV_CODEC_CAP_DELAY
#else
        CODEC_CAP_DELAY
#endif
        ) ) {

    // Put encoder into flushing mode
    avcodec_send_frame(video_out_ctx, NULL);
    while (1) {
      AVPacket pkt;
      // Without these we seg fault I don't know why.
      pkt.data = NULL;
      pkt.size = 0;
      av_init_packet(&pkt);
#if LIBAVCODEC_VERSION_CHECK(57, 64, 0, 64, 0)
      if ( (ret = avcodec_receive_packet(video_out_ctx, &pkt)) < 0 ) {
        if ( AVERROR_EOF != ret ) {
          Error("Error encoding audio while flushing (%d) (%s)", ret,
              av_err2str(ret));
        }
        break;
      }
#else
      int got_packet = 0;
      if ( (ret = avcodec_encode_video2(video_out_ctx, &pkt, NULL, &got_packet)) < 0 ) {
        Error("ERror encoding video while flushing (%d) (%s)", ret, av_err2str(ret));
        break;
      }
      if ( !got_packet ) {
        break;
      }
#endif
      write_packet(&pkt, video_out_stream);
      zm_av_packet_unref(&pkt);
    } // while have buffered frames
  } // end if have delay capability

  if ( audio_out_codec ) {
    // The codec queues data.  We need to send a flush command and out
    // whatever we get. Failures are not fatal.
    AVPacket pkt;
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
}  // end flush_codecs

VideoStore::~VideoStore() {
  if ( oc->pb ) {
    if ( ( video_out_ctx->codec_id != video_in_ctx->codec_id ) || audio_out_codec ) {
      Debug(2,"Different codecs between in and out");
      flush_codecs();
    } // end if buffers

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
  } // end if oc->pb

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
#if LIBAVCODEC_VERSION_CHECK(57, 64, 0, 64, 0)
  if ( video_in_ctx ) {
    avcodec_free_context(&video_in_ctx);
    video_in_ctx = NULL;
  }
  if ( video_out_ctx ) {
    avcodec_close(video_out_ctx);
    avcodec_free_context(&video_out_ctx);
    video_out_ctx = NULL;
  }
#endif

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
  audio_out_ctx->sample_fmt = audio_in_ctx->sample_fmt;
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
    Debug(3, "Encoder does not support sample format %s, setting to FLTP",
        av_get_sample_fmt_name(audio_out_ctx->sample_fmt));
    audio_out_ctx->sample_fmt = AV_SAMPLE_FMT_FLTP;
  }

  // Example code doesn't set the codec tb.  I think it just uses whatever defaults
  //audio_out_ctx->time_base = (AVRational){1, audio_out_ctx->sample_rate};

  AVDictionary *opts = NULL;
  // Needed to allow AAC
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
        "Audio in bit_rate (%d) sample_rate(%d) channels(%d) fmt(%d) "
        "layout(%d) frame_size(%d)",
        audio_in_ctx->bit_rate, audio_in_ctx->sample_rate,
        audio_in_ctx->channels, audio_in_ctx->sample_fmt,
        audio_in_ctx->channel_layout, audio_in_ctx->frame_size);
  Debug(1,
      "Audio out context bit_rate (%d) sample_rate(%d) channels(%d) fmt(%d) "
      "layout(%d) frame_size(%d)",
      audio_out_ctx->bit_rate, audio_out_ctx->sample_rate,
      audio_out_ctx->channels, audio_out_ctx->sample_fmt,
      audio_out_ctx->channel_layout, audio_out_ctx->frame_size);

#if LIBAVCODEC_VERSION_CHECK(57, 64, 0, 64, 0)
  Debug(1,
      "Audio out stream bit_rate (%d) sample_rate(%d) channels(%d) fmt(%d) "
      "layout(%d) frame_size(%d)",
      audio_out_stream->codecpar->bit_rate, audio_out_stream->codecpar->sample_rate,
      audio_out_stream->codecpar->channels, audio_out_stream->codecpar->format,
      audio_out_stream->codecpar->channel_layout, audio_out_stream->codecpar->frame_size);
#else
  Debug(1,
      "Audio out bit_rate (%d) sample_rate(%d) channels(%d) fmt(%d) "
      "layout(%d) frame_size(%d)",
      audio_out_stream->codec->bit_rate, audio_out_stream->codec->sample_rate,
      audio_out_stream->codec->channels, audio_out_stream->codec->sample_fmt,
      audio_out_stream->codec->channel_layout, audio_out_stream->codec->frame_size);
#endif

  /** Create a new frame to store the audio samples. */
  if ( ! in_frame ) {
    if (!(in_frame = zm_av_frame_alloc())) {
      Error("Could not allocate in frame");
      return false;
    }
  }

  /** Create a new frame to store the audio samples. */
  if ( !(out_frame = zm_av_frame_alloc()) ) {
    Error("Could not allocate out frame");
    av_frame_free(&in_frame);
    return false;
  }
  out_frame->sample_rate = audio_out_ctx->sample_rate;

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

  if ( (ret = avresample_open(resample_ctx)) < 0 ) {
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

int VideoStore::writePacket( ZMPacket *ipkt ) {
  if ( ipkt->packet.stream_index == video_in_stream_index ) {
    return writeVideoFramePacket( ipkt );
  } else if ( ipkt->packet.stream_index == audio_in_stream_index ) {
    return writeAudioFramePacket( ipkt );
  }
  Error("Unknown stream type in packet (%d) out input video stream is (%d) and audio is (%d)",
      ipkt->packet.stream_index, video_in_stream_index, ( audio_in_stream ? audio_in_stream_index : -1 )
      );
  return 0;
}

int VideoStore::writeVideoFramePacket(ZMPacket *zm_packet) {
  int ret;
  frame_count += 1;

  // if we have to transcode
  if ( video_out_ctx->codec_id != video_in_ctx->codec_id ) {
    //Debug(3, "Have encoding video frame count (%d)", frame_count);

    if ( !zm_packet->out_frame ) {
      //Debug(3, "Have no out frame");
      AVFrame *out_frame = zm_packet->get_out_frame(video_out_ctx);
      if ( !out_frame ) {
        Error("Unable to allocate a frame");
        return 0;
      }

      if ( !zm_packet->in_frame ) {
        //Debug(2,"Have no in_frame");
        if ( zm_packet->packet.size ) {
          //Debug(2,"Decoding");
          if ( !zm_packet->decode(video_in_ctx) ) {
            Debug(2, "unable to decode yet.");
            return 0;
          }
          //Go straight to out frame
          swscale.Convert(zm_packet->in_frame, out_frame);
        } else if ( zm_packet->image ) {
          //Debug(2,"Have an image, convert it");
          //Go straight to out frame
          swscale.Convert(
              zm_packet->image, 
              zm_packet->buffer,
              zm_packet->codec_imgsize,
              (AVPixelFormat)zm_packet->image->AVPixFormat(),
              video_out_ctx->pix_fmt,
              video_out_ctx->width,
              video_out_ctx->height
              );

        } else {
          Error("Have neither in_frame or image!");
          return 0;
        } // end if has packet or image
      } else {
        // Have in_frame.... may need to convert it to out_frame
        swscale.Convert(zm_packet->in_frame, zm_packet->out_frame);
      } // end if no in_frame
    } // end if no out_frame

    zm_packet->out_frame->coded_picture_number = frame_count;
    zm_packet->out_frame->display_picture_number = frame_count;
    zm_packet->out_frame->sample_aspect_ratio = (AVRational){ 0, 1 };
#if LIBAVCODEC_VERSION_CHECK(57, 64, 0, 64, 0)
    zm_packet->out_frame->pkt_duration = 0;
#endif

    if ( !video_start_pts ) {
      uint64_t temp = zm_packet->timestamp->tv_sec*(uint64_t)1000000;
      video_start_pts = temp + zm_packet->timestamp->tv_usec;
      Debug(2, "No video_lsat_pts, set to (%" PRId64 ") secs(%d=>%" PRId64 ") usecs(%d)",
          video_start_pts, zm_packet->timestamp->tv_sec, temp, zm_packet->timestamp->tv_usec);
      Debug(2, "No video_lsat_pts, set to (%" PRId64 ") secs(%d) usecs(%d)",
          video_start_pts, zm_packet->timestamp->tv_sec, zm_packet->timestamp->tv_usec);
      zm_packet->out_frame->pts = 0;
      zm_packet->out_frame->coded_picture_number = 0;
    } else {
      uint64_t seconds = ( zm_packet->timestamp->tv_sec*(uint64_t)1000000 + zm_packet->timestamp->tv_usec ) - video_start_pts;
      zm_packet->out_frame->pts = av_rescale_q(seconds, video_in_stream->time_base, video_out_ctx->time_base);

      //zm_packet->out_frame->pkt_duration = zm_packet->out_frame->pts - video_start_pts;
      Debug(2, " Setting pts for frame(%d), set to (%" PRId64 ") from (start %" PRIu64 " - %" PRIu64 " - secs(%d) usecs(%d)",
          frame_count, zm_packet->out_frame->pts, video_start_pts, seconds, zm_packet->timestamp->tv_sec, zm_packet->timestamp->tv_usec);
    }
    if ( zm_packet->keyframe ) {
      //Debug(2, "Setting keyframe was (%d)", zm_packet->out_frame->key_frame );
      zm_packet->out_frame->key_frame = 1;
      //Debug(2, "Setting keyframe (%d)", zm_packet->out_frame->key_frame );
    } else {
      Debug(2, "Not Setting keyframe");
    }

    av_init_packet(&opkt);
    opkt.data = NULL;
    opkt.size = 0;
#if LIBAVCODEC_VERSION_CHECK(57, 64, 0, 64, 0)
    // Do this to allow the encoder to choose whether to use I/P/B frame
    zm_packet->out_frame->pict_type = AV_PICTURE_TYPE_NONE;
    Debug(4, "Sending frame");
    if ( (ret = avcodec_send_frame(video_out_ctx, zm_packet->out_frame)) < 0 ) {
      Error("Could not send frame (error '%s')", av_make_error_string(ret).c_str());
      return -1;
    }

    if ( (ret = avcodec_receive_packet(video_out_ctx, &opkt)) < 0 ) {
      zm_av_packet_unref(&opkt);
      if ( AVERROR(EAGAIN) == ret ) {
        // The codec may need more samples than it has, perfectly valid
        Debug(3, "Could not recieve packet (error '%s')",
            av_make_error_string(ret).c_str());
        return 0;
      } else {
        Error("Could not recieve packet (error %d = '%s')", ret,
            av_make_error_string(ret).c_str());
      }
      return -1;
    }
    //Debug(2, "Got packet using receive_packet, dts:%" PRId64 ", pts:%" PRId64 ", keyframe:%d", opkt.dts, opkt.pts, opkt.flags & AV_PKT_FLAG_KEY );
#else
    int data_present;
    if ( (ret = avcodec_encode_video2(
            video_out_ctx, &opkt, zm_packet->out_frame, &data_present)) < 0) {
      Error("Could not encode frame (error '%s')",
          av_make_error_string(ret).c_str());
      zm_av_packet_unref(&opkt);
      return 0;
    }
    if ( !data_present ) {
      Debug(2, "Not ready to out a frame yet.");
      zm_av_packet_unref(&opkt);
      return 0;
    }
#endif

    // Need to adjust pts/dts values from codec time to stream time
    if ( opkt.pts != AV_NOPTS_VALUE)
      opkt.pts = av_rescale_q(opkt.pts, video_out_ctx->time_base, video_out_stream->time_base);
    if ( opkt.dts != AV_NOPTS_VALUE)
      opkt.dts = av_rescale_q(opkt.dts, video_out_ctx->time_base, video_out_stream->time_base);

    int64_t duration;
    if ( zm_packet->in_frame->pkt_duration ) {
      duration = av_rescale_q(
          zm_packet->in_frame->pkt_duration,
          video_in_stream->time_base,
          video_out_stream->time_base);
      Debug(1, "duration from ipkt: pts(%" PRId64 ") - last_pts(%" PRId64 ") = (%" PRId64 ") => (%" PRId64 ") (%d/%d) (%d/%d)",
          zm_packet->in_frame->pts,
          video_last_pts,
          zm_packet->in_frame->pkt_duration,
          duration,
          video_in_stream->time_base.num,
          video_in_stream->time_base.den,
          video_out_stream->time_base.num,
          video_out_stream->time_base.den
          );
    } else {
      duration =
        av_rescale_q(
            zm_packet->in_frame->pts - video_last_pts,
            video_in_stream->time_base,
            video_out_stream->time_base);
      Debug(1, "duration calc: pts(%" PRId64 ") - last_pts(%" PRId64 ") = (%" PRId64 ") => (%" PRId64 ")",
          zm_packet->in_frame->pts,
          video_last_pts,
          zm_packet->in_frame->pts - video_last_pts,
          duration
          );
      if ( duration <= 0 ) {
        duration = zm_packet->in_frame->pkt_duration ? zm_packet->in_frame->pkt_duration : av_rescale_q(1,video_in_stream->time_base, video_out_stream->time_base);
      }
    }
    opkt.duration = duration;

  } else { // codec matches, we are doing passthrough
    AVPacket *ipkt = &zm_packet->packet;
    Debug(3, "Doing passthrough, just copy packet");
    // Just copy it because the codec is the same
    av_init_packet(&opkt);
    opkt.data = ipkt->data;
    opkt.size = ipkt->size;
    opkt.flags = ipkt->flags;
    opkt.duration = ipkt->duration;

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
  } // end if codec matches

  write_packet(&opkt, video_out_stream);
  zm_av_packet_unref(&opkt);

  return 1;
}  // end int VideoStore::writeVideoFramePacket( AVPacket *ipkt )

int VideoStore::writeAudioFramePacket(ZMPacket *zm_packet) {

  AVPacket *ipkt = &zm_packet->packet;
  int ret;

  if ( !audio_out_stream ) {
    Debug(1, "Called writeAudioFramePacket when no audio_out_stream");
    return 0;
    // FIXME -ve return codes do not free packet in ffmpeg_camera at the moment
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

int VideoStore::write_packets( zm_packetqueue &queue ) {
  // Need to write out all the frames from the last keyframe?
  // No... need to write out all frames from when the event began. Due to PreEventFrames, this could be more than since the last keyframe.
  unsigned int packet_count = 0;
  ZMPacket *queued_packet;

  while ( ( queued_packet = queue.popPacket() ) ) {
    AVPacket *avp = queued_packet->av_packet();

    packet_count += 1;
    //Write the packet to our video store
    Debug(2, "Writing queued packet stream: %d KEY %d, remaining (%d)",
        avp->stream_index, avp->flags & AV_PKT_FLAG_KEY, queue.size() );
    int ret = this->writePacket( queued_packet );
    if ( ret < 0 ) {
      //Less than zero and we skipped a frame
    }
    delete queued_packet;
  } // end while packets in the packetqueue
  Debug(2, "Wrote %d queued packets", packet_count );
  return packet_count;
}  // end int VideoStore::write_packets( PacketQueue &queue ) {

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
