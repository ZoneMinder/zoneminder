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

#include <stdlib.h>
#include <string.h>
#include <inttypes.h>

#include "zm.h"
#include "zm_videostore.h"

extern "C" {
  #include "libavutil/time.h"
}

VideoStore::VideoStore(const char *filename_in, const char *format_in,
    AVStream *p_video_input_stream,
    AVStream *p_audio_input_stream,
    int64_t nStartTime,
    Monitor * monitor
    ) {

  video_input_stream = p_video_input_stream;
  audio_input_stream = p_audio_input_stream;

#if LIBAVCODEC_VERSION_CHECK(57, 64, 0, 64, 0)
  video_input_context = avcodec_alloc_context3( NULL );
  avcodec_parameters_to_context( video_input_context, video_input_stream->codecpar );
  zm_dump_codecpar( video_input_stream->codecpar );
#else
  video_input_context = video_input_stream->codec;
#endif

  //store inputs in variables local to class
  filename = filename_in;
  format = format_in;

  Info("Opening video storage stream %s format: %s\n", filename, format);

  ret = avformat_alloc_output_context2(&oc, NULL, NULL, filename);
  if ( ret < 0 ) {
    Warning("Could not create video storage stream %s as no output context"
        " could be assigned based on filename: %s",
        filename,
        av_make_error_string(ret).c_str()
        );
  } else {
    Debug(2, "Success allocating output context");
  }

  //Couldn't deduce format from filename, trying from format name
  if ( ! oc ) {
    avformat_alloc_output_context2(&oc, NULL, format, filename);
    if (!oc) {
      Fatal("Could not create video storage stream %s as no output context"
          " could not be assigned based on filename or format %s",
          filename, format);
    }
  } else {
    Debug(2, "Success alocateing output context");
  }

  AVDictionary *pmetadata = NULL;
  int dsr = av_dict_set(&pmetadata, "title", "Zoneminder Security Recording", 0);
  if (dsr < 0) Warning("%s:%d: title set failed", __FILE__, __LINE__ );

  oc->metadata = pmetadata;
  output_format = oc->oformat;

#if LIBAVCODEC_VERSION_CHECK(57, 64, 0, 64, 0)

  // Since we are not re-encoding, all we have to do is copy the parameters
  video_output_context = avcodec_alloc_context3( NULL );

  // Copy params from inputstream to context
  ret = avcodec_parameters_to_context( video_output_context, video_input_stream->codecpar );
  if ( ret < 0 ) {
    Error( "Could not initialize context parameteres");
    return;
  } else {
    zm_dump_codec( video_output_context );
  }
  
  video_output_stream = avformat_new_stream( oc, NULL );
  if ( ! video_output_stream ) {
    Fatal("Unable to create video out stream\n");
  } else {
    Debug(2, "Success creating video out stream" );
  }

  if ( ! video_output_context->codec_tag ) {
    video_output_context->codec_tag = av_codec_get_tag(oc->oformat->codec_tag, video_input_context->codec_id);
    Debug(2, "No codec_tag, setting to %d", video_output_context->codec_tag );
  }

  // Now copy them to the output stream
  ret = avcodec_parameters_from_context( video_output_stream->codecpar, video_output_context );
  if ( ret < 0 ) {
    Error( "Could not initialize stream parameteres");
    return;
  } else {
    Debug(2, "Success setting parameters");
  }
  zm_dump_codecpar( video_output_stream->codecpar );

#else
  video_output_stream = avformat_new_stream(oc, (AVCodec*)video_input_context->codec );
  if ( ! video_output_stream ) {
    Fatal("Unable to create video out stream\n");
  } else {
    Debug(2, "Success creating video out stream" );
  }
  video_output_context = video_output_stream->codec;
  ret = avcodec_copy_context( video_output_context, video_input_context );
  if (ret < 0) { 
    Fatal("Unable to copy input video context to output video context %s\n", 
        av_make_error_string(ret).c_str());
  } else {
    Debug(3, "Success copying context" );
  }
  if ( ! video_output_context->codec_tag ) {
    Debug(2, "No codec_tag");
    if (! oc->oformat->codec_tag
        || av_codec_get_id (oc->oformat->codec_tag, video_input_context->codec_tag) == video_output_context->codec_id
        || av_codec_get_tag(oc->oformat->codec_tag, video_input_context->codec_id) <= 0) {
      Warning("Setting codec tag");
      video_output_context->codec_tag = video_input_context->codec_tag;
    }
  }
#endif

  // Just copy them from the input, no reason to choose different
  video_output_context->time_base = video_input_context->time_base;
  video_output_stream->time_base = video_input_stream->time_base;

  Debug(3, "Time bases: VIDEO input stream (%d/%d) input codec: (%d/%d) output stream: (%d/%d) output codec (%d/%d)", 
        video_input_stream->time_base.num,
        video_input_stream->time_base.den,
        video_input_context->time_base.num,
        video_input_context->time_base.den,
        video_output_stream->time_base.num,
        video_output_stream->time_base.den,
        video_output_context->time_base.num,
        video_output_context->time_base.den
        );


  if (oc->oformat->flags & AVFMT_GLOBALHEADER) {
    video_output_context->flags |= CODEC_FLAG_GLOBAL_HEADER;
  }

	Monitor::Orientation orientation = monitor->getOrientation();
    Debug(3, "Have orientation" );
  if ( orientation ) {
    if ( orientation == Monitor::ROTATE_0 ) {

    } else if ( orientation == Monitor::ROTATE_90 ) {
      dsr = av_dict_set( &video_output_stream->metadata, "rotate", "90", 0);
      if (dsr < 0) Warning("%s:%d: title set failed", __FILE__, __LINE__ );
    } else if ( orientation == Monitor::ROTATE_180 ) {
      dsr = av_dict_set( &video_output_stream->metadata, "rotate", "180", 0);
      if (dsr < 0) Warning("%s:%d: title set failed", __FILE__, __LINE__ );
    } else if ( orientation == Monitor::ROTATE_270 ) {
      dsr = av_dict_set( &video_output_stream->metadata, "rotate", "270", 0);
      if (dsr < 0) Warning("%s:%d: title set failed", __FILE__, __LINE__ );
    } else {
      Warning( "Unsupported Orientation(%d)", orientation );
    }
  }

  audio_output_codec = NULL;
  audio_input_context = NULL;
  audio_output_stream = NULL;
#ifdef HAVE_LIBAVRESAMPLE
  resample_context = NULL;
#endif

  if ( audio_input_stream ) {
    Debug(3, "Have audio stream" );
#if LIBAVCODEC_VERSION_CHECK(57, 64, 0, 64, 0)

    audio_input_context = avcodec_alloc_context3( NULL );
    ret = avcodec_parameters_to_context( audio_input_context, audio_input_stream->codecpar );
#else
    audio_input_context = audio_input_stream->codec;
#endif

    if ( audio_input_context->codec_id != AV_CODEC_ID_AAC ) {
			static char error_buffer[256];
      avcodec_string(error_buffer, sizeof(error_buffer), audio_input_context, 0 );
      Debug(2, "Got something other than AAC (%s)", error_buffer );

    
      if ( ! setup_resampler() ) {
        return;
      }
    } else {
      Debug(3, "Got AAC" );

      audio_output_stream = avformat_new_stream(oc, (AVCodec*)audio_input_context->codec);
      if ( ! audio_output_stream ) {
        Error("Unable to create audio out stream\n");
        audio_output_stream = NULL;
      } else {
        Debug(2, "setting parameters");

#if LIBAVCODEC_VERSION_CHECK(57, 64, 0, 64, 0)
        audio_output_context = avcodec_alloc_context3( audio_output_codec );
        // Copy params from inputstream to context
        ret = avcodec_parameters_to_context( audio_output_context, audio_input_stream->codecpar );
        if (ret < 0) {
          Error("Unable to copy audio params to context %s\n", av_make_error_string(ret).c_str());
        }
        ret = avcodec_parameters_from_context( audio_output_stream->codecpar, audio_output_context );
        if (ret < 0) {
          Error("Unable to copy audio params to stream %s\n", av_make_error_string(ret).c_str());
        }

        if ( ! audio_output_context->codec_tag ) {
          audio_output_context->codec_tag = av_codec_get_tag(oc->oformat->codec_tag, audio_input_context->codec_id);
          Debug(2, "Setting audio codec tag to %d", audio_output_context->codec_tag );
        }

#else
        audio_output_context = audio_output_stream->codec;
        ret = avcodec_copy_context(audio_output_context, audio_input_context);
        audio_output_context->codec_tag = 0;
#endif
        if (ret < 0) {
          Error("Unable to copy audio context %s\n", av_make_error_string(ret).c_str());
          audio_output_stream = NULL;
        } else {
          if ( audio_output_context->channels > 1 ) {
            Warning("Audio isn't mono, changing it.");
            audio_output_context->channels = 1;
          } else {
            Debug(3, "Audio is mono");
          }
        }   
      } // end if audio_output_stream
    } // end if is AAC

    if ( audio_output_stream ) {
      if (oc->oformat->flags & AVFMT_GLOBALHEADER) {
        audio_output_context->flags |= CODEC_FLAG_GLOBAL_HEADER;
      }
    }

  } // end if audio_input_stream

  /* open the output file, if needed */
  if (!(output_format->flags & AVFMT_NOFILE)) {
    ret = avio_open2(&oc->pb, filename, AVIO_FLAG_WRITE,NULL,NULL);
    if (ret < 0) {
      Fatal("Could not open output file '%s': %s\n", filename,
          av_make_error_string(ret).c_str());
    }
  }

  //os->ctx_inited = 1;
  //avio_flush(ctx->pb);
  //av_dict_free(&opts);
  zm_dump_stream_format( oc, 0, 0, 1 );
  if ( audio_output_stream ) 
    zm_dump_stream_format( oc, 1, 0, 1 );

  AVDictionary * opts = NULL;
  av_dict_set(&opts, "movflags", "frag_custom+dash+delay_moov", 0);
  //av_dict_set(&opts, "movflags", "frag_custom+dash+delay_moov", 0);
  //av_dict_set(&opts, "movflags", "frag_keyframe+empty_moov+default_base_moof", 0);
  if ((ret = avformat_write_header( oc, &opts )) < 0) {
  //if ((ret = avformat_write_header(oc, &opts)) < 0) {
    Warning("Unable to set movflags to frag_custom+dash+delay_moov");
    /* Write the stream header, if any. */
    ret = avformat_write_header(oc, NULL);
  } else if (av_dict_count(opts) != 0) {
    Warning("some options not set\n");
  }
  if (ret < 0) {
    Error("Error occurred when writing output file header to %s: %s\n",
        filename,
        av_make_error_string(ret).c_str());
  }
  if ( opts )
    av_dict_free(&opts);

  video_last_pts = 0;
  video_last_dts = 0;
  audio_last_pts = 0;
  audio_last_dts = 0;
  video_previous_pts = 0;
  video_previous_dts = 0;
  audio_previous_pts = 0;
  audio_previous_dts = 0;

} // VideoStore::VideoStore


VideoStore::~VideoStore(){
  if ( audio_output_codec ) {
    // Do we need to flush the outputs?  I have no idea.
    AVPacket pkt;
    int got_packet = 0;
    av_init_packet(&pkt);
    pkt.data = NULL;
    pkt.size = 0;
    int64_t size;

    while(1) {
#if LIBAVCODEC_VERSION_CHECK(57, 64, 0, 64, 0)
      ret = avcodec_receive_packet( audio_output_context, &pkt );
#else
      ret = avcodec_encode_audio2( audio_output_context, &pkt, NULL, &got_packet );
#endif
      if (ret < 0) {
        Error("ERror encoding audio while flushing");
        break;
      }
Debug(1, "Have audio encoder, need to flush it's output" );
      size += pkt.size;
      if (!got_packet) {
        break;
      }
Debug(2, "writing flushed packet pts(%d) dts(%d) duration(%d)", pkt.pts, pkt.dts, pkt.duration );
      if (pkt.pts != AV_NOPTS_VALUE)
        pkt.pts = av_rescale_q(pkt.pts, audio_output_context->time_base, audio_output_stream->time_base);
      if (pkt.dts != AV_NOPTS_VALUE)
        pkt.dts = av_rescale_q(pkt.dts, audio_output_context->time_base, audio_output_stream->time_base);
      if (pkt.duration > 0)
        pkt.duration = av_rescale_q(pkt.duration, audio_output_context->time_base, audio_output_stream->time_base);
Debug(2, "writing flushed packet pts(%d) dts(%d) duration(%d)", pkt.pts, pkt.dts, pkt.duration );
      pkt.stream_index = audio_output_stream->index;
      av_interleaved_write_frame( oc, &pkt );
      zm_av_packet_unref( &pkt );
    } // while 1
  }

  // Flush Queues
  av_interleaved_write_frame( oc, NULL );

  /* Write the trailer before close */
  if ( int rc = av_write_trailer(oc) ) {
    Error("Error writing trailer %s",  av_err2str( rc ) );
  } else {
    Debug(3, "Sucess Writing trailer");
  }

  // I wonder if we should be closing the file first.
  // I also wonder if we really need to be doing all the context allocation/de-allocation constantly, or whether we can just re-use it.  Just do a file open/close/writeheader/etc.
  // What if we were only doing audio recording?
  if ( video_output_stream ) {
    avcodec_close(video_output_context);
  }
  if (audio_output_stream) {
    avcodec_close(audio_output_context);
#ifdef HAVE_LIBAVRESAMPLE
    if ( resample_context ) {
      avresample_close( resample_context );
      avresample_free( &resample_context );
    }
#endif
  }

  // WHen will be not using a file ?
  if (!(output_format->flags & AVFMT_NOFILE)) {
    /* Close the output file. */
    if ( int rc = avio_close(oc->pb) ) {
      Error("Error closing avio %s",  av_err2str( rc ) );
    }
  } else {
    Debug(3, "Not closing avio because we are not writing to a file.");
  }

  /* free the stream */
  avformat_free_context(oc);
}

bool VideoStore::setup_resampler() {
#ifdef HAVE_LIBAVRESAMPLE
  static char error_buffer[256];

#if LIBAVCODEC_VERSION_CHECK(57, 64, 0, 64, 0)
  // Newer ffmpeg wants to keep everything separate... so have to lookup our own decoder, can't reuse the one from the camera.
  AVCodec *audio_input_codec = avcodec_find_decoder(audio_input_stream->codecpar->codec_id);
#else
  AVCodec *audio_input_codec = avcodec_find_decoder(audio_input_context->codec_id);
#endif
  ret = avcodec_open2( audio_input_context, audio_input_codec, NULL );
  if ( ret < 0 ) {
    Error("Can't open input codec!");
    return false;
  }

  audio_output_codec = avcodec_find_encoder(AV_CODEC_ID_AAC);
  if ( ! audio_output_codec ) {
    Error("Could not find codec for AAC");
    return false;
  }
  Debug(2, "Have audio output codec");

  //audio_output_context = audio_output_stream->codec;
  audio_output_context = avcodec_alloc_context3( audio_output_codec );

  if ( ! audio_output_context ) {
    Error( "could not allocate codec context for AAC\n");
    audio_output_stream = NULL;
    return false;
  }

  Debug(2, "Have audio_output_context");

  /* put sample parameters */
  audio_output_context->bit_rate = audio_input_context->bit_rate;
  audio_output_context->sample_rate = audio_input_context->sample_rate;
  audio_output_context->channels = audio_input_context->channels;
  audio_output_context->channel_layout = audio_input_context->channel_layout;
  audio_output_context->sample_fmt = audio_input_context->sample_fmt;
  audio_output_context->refcounted_frames = 1;

  if ( audio_output_codec->supported_samplerates ) {
    int found = 0;
    for ( unsigned int i = 0; audio_output_codec->supported_samplerates[i]; i++) {
      if ( audio_output_context->sample_rate == audio_output_codec->supported_samplerates[i] ) {
        found = 1;
        break;
      }
    }
    if ( found ) {
      Debug(3, "Sample rate is good");
    } else {
      audio_output_context->sample_rate = audio_output_codec->supported_samplerates[0];
      Debug(1, "Sampel rate is no good, setting to (%d)", audio_output_codec->supported_samplerates[0] );
    }
  }

  /* check that the encoder supports s16 pcm input */
  if ( ! check_sample_fmt( audio_output_codec, audio_output_context->sample_fmt ) ) {
    Debug( 3, "Encoder does not support sample format %s, setting to FLTP",
        av_get_sample_fmt_name( audio_output_context->sample_fmt));
    audio_output_context->sample_fmt = AV_SAMPLE_FMT_FLTP;
  }

  audio_output_context->time_base = (AVRational){ 1, audio_output_context->sample_rate };


  Debug(1, "Audio output bit_rate (%d) sample_rate(%d) channels(%d) fmt(%d) layout(%d) frame_size(%d)", 
      audio_output_context->bit_rate,
      audio_output_context->sample_rate,
      audio_output_context->channels,
      audio_output_context->sample_fmt,
      audio_output_context->channel_layout,
      audio_output_context->frame_size
      );

  // Now copy them to the output stream
  audio_output_stream = avformat_new_stream( oc, audio_output_codec );

#if LIBAVCODEC_VERSION_CHECK(57, 64, 0, 64, 0)
  ret = avcodec_parameters_from_context( audio_output_stream->codecpar, audio_output_context );
  if ( ret < 0 ) {
    Error( "Could not initialize stream parameteres");
    return false;
  } 
#endif

  AVDictionary *opts = NULL;
  av_dict_set( &opts, "strict", "experimental", 0);
  ret = avcodec_open2( audio_output_context, audio_output_codec, &opts );
  av_dict_free(&opts);
  if ( ret < 0 ) {
    av_strerror(ret, error_buffer, sizeof(error_buffer));
    Fatal( "could not open codec (%d) (%s)\n", ret, error_buffer );
    audio_output_codec = NULL;
    audio_output_context = NULL;
    audio_output_stream = NULL;
    return false;
  } 

  /** Create a new frame to store the audio samples. */
  if (!(input_frame = zm_av_frame_alloc())) {
    Error("Could not allocate input frame");
    return false;
  }

  /** Create a new frame to store the audio samples. */
  if (!(output_frame = zm_av_frame_alloc())) {
    Error("Could not allocate output frame");
    av_frame_free( &input_frame );
    return false;
  }

  // Setup the audio resampler
  resample_context = avresample_alloc_context();
  if ( ! resample_context ) {
    Error( "Could not allocate resample context\n");
    return false;
  }

  // Some formats (i.e. WAV) do not produce the proper channel layout
  if ( audio_input_context->channel_layout == 0 ) {
    Error( "Bad channel layout. Need to set it to mono.\n");
    av_opt_set_int( resample_context, "in_channel_layout",  av_get_channel_layout( "mono" ), 0 );
  } else {
    av_opt_set_int( resample_context, "in_channel_layout",  audio_input_context->channel_layout, 0 );
  }

  av_opt_set_int( resample_context, "in_sample_fmt",     audio_input_context->sample_fmt, 0);
  av_opt_set_int( resample_context, "in_sample_rate",    audio_input_context->sample_rate, 0);
  av_opt_set_int( resample_context, "in_channels",       audio_input_context->channels,0);
  //av_opt_set_int( resample_context, "out_channel_layout", audio_output_context->channel_layout, 0);
  av_opt_set_int( resample_context, "out_channel_layout",  av_get_channel_layout( "mono" ), 0 );
  av_opt_set_int( resample_context, "out_sample_fmt",     audio_output_context->sample_fmt, 0);
  av_opt_set_int( resample_context, "out_sample_rate",    audio_output_context->sample_rate, 0);
  av_opt_set_int( resample_context, "out_channels",       audio_output_context->channels, 0);

  ret = avresample_open( resample_context );
  if ( ret < 0 ) {
    Error( "Could not open resample context\n");
    return false;
  }

#if 0
    /**
     * Allocate as many pointers as there are audio channels.
     * Each pointer will later point to the audio samples of the corresponding
     * channels (although it may be NULL for interleaved formats).
     */
    if (!( converted_input_samples = (uint8_t *)calloc( audio_output_context->channels, sizeof(*converted_input_samples))) ) {
      Error( "Could not allocate converted input sample pointers\n");
      return;
    }
    /**
     * Allocate memory for the samples of all channels in one consecutive
     * block for convenience.
     */
    if ((ret = av_samples_alloc( &converted_input_samples, NULL,
            audio_output_context->channels,
            audio_output_context->frame_size,
            audio_output_context->sample_fmt, 0)) < 0) {
      Error( "Could not allocate converted input samples (error '%s')\n",
          av_make_error_string(ret).c_str() );

      av_freep(converted_input_samples);
      free(converted_input_samples);
      return;
    }
#endif

    output_frame->nb_samples = audio_output_context->frame_size;
    output_frame->format = audio_output_context->sample_fmt;
    output_frame->channel_layout = audio_output_context->channel_layout;

    // The codec gives us the frame size, in samples, we calculate the size of the samples buffer in bytes
    unsigned int audioSampleBuffer_size = av_samples_get_buffer_size( NULL, audio_output_context->channels, audio_output_context->frame_size, audio_output_context->sample_fmt, 0 );
    converted_input_samples = (uint8_t*) av_malloc( audioSampleBuffer_size );

    if ( !converted_input_samples ) { 
      Error( "Could not allocate converted input sample pointers\n");
      return false;
    }

    // Setup the data pointers in the AVFrame
    if ( avcodec_fill_audio_frame( 
          output_frame, 
          audio_output_context->channels, 
          audio_output_context->sample_fmt, 
          (const uint8_t*) converted_input_samples, 
          audioSampleBuffer_size, 0 ) < 0 ) {
      Error( "Could not allocate converted input sample pointers\n");
      return false;
    }

    return true;
#else
    Error("Not built with libavresample library. Cannot do audio conversion to AAC");
    return false;
#endif
}


void VideoStore::dumpPacket( AVPacket *pkt ){
  char b[10240];

  snprintf(b, sizeof(b), " pts: %" PRId64 ", dts: %" PRId64 ", data: %p, size: %d, sindex: %d, dflags: %04x, s-pos: %" PRId64 ", c-duration: %" PRId64 "\n"
      , pkt->pts
      , pkt->dts
      , pkt->data
      , pkt->size
      , pkt->stream_index
      , pkt->flags
      , pkt->pos
      , pkt->duration
      );
  Debug(1, "%s:%d:DEBUG: %s", __FILE__, __LINE__, b);
}

int VideoStore::writeVideoFramePacket( AVPacket *ipkt ) {
  av_init_packet(&opkt);

  int duration;

  //Scale the PTS of the outgoing packet to be the correct time base
  if ( ipkt->pts != AV_NOPTS_VALUE ) {

    if ( ! video_last_pts ) {
      // This is the first packet.
      opkt.pts = 0;
      Debug(2, "Starting video video_last_pts will become (%d)", ipkt->pts );
    } else {
      if ( ipkt->pts < video_last_pts ) {
        Debug(1, "Resetting video_last_pts from (%d) to (%d)",  video_last_pts, ipkt->pts );
        // wrap around, need to figure out the distance FIXME having this wrong should cause a jump, but then play ok?
        opkt.pts = video_previous_pts + av_rescale_q( ipkt->pts, video_input_stream->time_base, video_output_stream->time_base);
      } else {
        opkt.pts = video_previous_pts + av_rescale_q( ipkt->pts - video_last_pts, video_input_stream->time_base, video_output_stream->time_base);
      }
    }
    Debug(3, "opkt.pts = %d from ipkt->pts(%d) - last_pts(%d)", opkt.pts, ipkt->pts, video_last_pts );
    duration = ipkt->pts - video_last_pts;
    video_last_pts = ipkt->pts;
  } else {
    Debug(3, "opkt.pts = undef");
    opkt.pts = AV_NOPTS_VALUE;
  }

  //Scale the DTS of the outgoing packet to be the correct time base

  // Just because the input stream wraps, doesn't mean the output needs to.  Really, if we are limiting ourselves to 10min segments I can't imagine every wrapping in the output.  So need to handle input wrap, without causing output wrap.
  if ( ! video_last_dts ) {
    // This is the first packet.
    opkt.dts = 0;
    Debug(1, "Starting video video_last_dts will become (%d)", ipkt->dts );
    video_last_dts = ipkt->dts;
  } else {
    if ( ipkt->dts == AV_NOPTS_VALUE ) {
      // why are we using cur_dts instead of packet.dts? I think cur_dts is in AV_TIME_BASE_Q, but ipkt.dts is in video_input_stream->time_base
      if ( video_input_stream->cur_dts < video_last_dts ) {
        Debug(1, "Resetting video_last_dts from (%d) to (%d) p.dts was (%d)",  video_last_dts, video_input_stream->cur_dts, ipkt->dts );
        opkt.dts = video_previous_dts + av_rescale_q(video_input_stream->cur_dts, AV_TIME_BASE_Q, video_output_stream->time_base);
      } else {
        opkt.dts = video_previous_dts + av_rescale_q(video_input_stream->cur_dts - video_last_dts, AV_TIME_BASE_Q, video_output_stream->time_base);
      }
      Debug(3, "opkt.dts = %d from video_input_stream->cur_dts(%d) - previus_dts(%d)", opkt.dts, video_input_stream->cur_dts, video_last_dts );
      video_last_dts = video_input_stream->cur_dts;
    } else {
      if ( ipkt->dts < video_last_dts ) {
        Debug(1, "Resetting video_last_dts from (%d) to (%d)",  video_last_dts, ipkt->dts );
        opkt.dts = video_previous_dts + av_rescale_q( ipkt->dts,  video_input_stream->time_base, video_output_stream->time_base);
      } else {
        opkt.dts = video_previous_dts + av_rescale_q( ipkt->dts - video_last_dts, video_input_stream->time_base, video_output_stream->time_base);
      }
      Debug(3, "opkt.dts = %d from ipkt.dts(%d) - previus_dts(%d)", opkt.dts, ipkt->dts, video_last_dts );
      video_last_dts = ipkt->dts;
    }
  }
  if ( opkt.dts > opkt.pts ) {
    Debug( 1, "opkt.dts(%d) must be <= opkt.pts(%d). Decompression must happen before presentation.", opkt.dts, opkt.pts );
    opkt.dts = opkt.pts;
  }

  if ( ipkt->duration == AV_NOPTS_VALUE ) {
    opkt.duration = av_rescale_q( duration, video_input_stream->time_base, video_output_stream->time_base);
  } else {
    opkt.duration = av_rescale_q(ipkt->duration, video_input_stream->time_base, video_output_stream->time_base);
  }
  opkt.flags = ipkt->flags;
  opkt.pos=-1;

  opkt.data = ipkt->data;
  opkt.size = ipkt->size;

  // Some camera have audio on stream 0 and video on stream 1.  So when we remove the audio, video stream has to go on 0
  if ( ipkt->stream_index > 0 and ! audio_output_stream ) {
    Debug(1,"Setting stream index to 0 instead of %d", ipkt->stream_index );
    opkt.stream_index = 0;
  } else {
    opkt.stream_index = ipkt->stream_index;
  }

  AVPacket safepkt;
  memcpy( &safepkt, &opkt, sizeof(AVPacket) );

  Debug(1, "writing video packet pts(%d) dts(%d) duration(%d)", opkt.pts, opkt.dts, opkt.duration );
  if ((opkt.data == NULL)||(opkt.size < 1)) {
    Warning("%s:%d: Mangled AVPacket: discarding frame", __FILE__, __LINE__ ); 
    dumpPacket( ipkt);
    dumpPacket(&opkt);

  } else if ((video_previous_dts > 0) && (video_previous_dts > opkt.dts)) {
    Warning("%s:%d: DTS out of order: %lld \u226E %lld; discarding frame", __FILE__, __LINE__, video_previous_dts, opkt.dts); 
    video_previous_dts = opkt.dts; 
    dumpPacket(&opkt);

  } else {

    video_previous_dts = opkt.dts; // Unsure if av_interleaved_write_frame() clobbers opkt.dts when out of order, so storing in advance
    video_previous_pts = opkt.pts;
    ret = av_interleaved_write_frame(oc, &opkt);
    if ( ret < 0 ) {
      // There's nothing we can really do if the frame is rejected, just drop it and get on with the next
      Warning("%s:%d: Writing frame [av_interleaved_write_frame()] failed: %s(%d)  ", __FILE__, __LINE__,  av_make_error_string(ret).c_str(), (ret));
      dumpPacket(&safepkt);
#if LIBAVCODEC_VERSION_CHECK(57, 64, 0, 64, 0)
  zm_dump_codecpar( video_input_stream->codecpar );
  zm_dump_codecpar( video_output_stream->codecpar );
#endif
    }
  }

  zm_av_packet_unref(&opkt); 

  return 0;

} // end int VideoStore::writeVideoFramePacket( AVPacket *ipkt )

int VideoStore::writeAudioFramePacket( AVPacket *ipkt ) {
  Debug(4, "writeAudioFrame");

  if ( ! audio_output_stream ) {
    Debug(1, "Called writeAudioFramePacket when no audio_output_stream");
    return 0;//FIXME -ve return codes do not free packet in ffmpeg_camera at the moment
  }

  if ( audio_output_codec ) {
#ifdef HAVE_LIBAVRESAMPLE

#if LIBAVCODEC_VERSION_CHECK(57, 64, 0, 64, 0)
    ret = avcodec_send_packet( audio_input_context, ipkt );
    if ( ret < 0 ) {
      Error("avcodec_send_packet fail %s", av_make_error_string(ret).c_str());
      return 0;
    }

    ret = avcodec_receive_frame( audio_input_context, input_frame );
    if ( ret < 0 ) {
      Error("avcodec_receive_frame fail %s", av_make_error_string(ret).c_str());
      return 0;
    }
    Debug(2, "Frame: samples(%d), format(%d), sample_rate(%d), channel layout(%d) refd(%d)", 
        input_frame->nb_samples,
        input_frame->format,
        input_frame->sample_rate,
        input_frame->channel_layout,
        audio_output_context->refcounted_frames
        );
#else
    /**
     * Decode the audio frame stored in the packet.
     * The input audio stream decoder is used to do this.
     * If we are at the end of the file, pass an empty packet to the decoder
     * to flush it.
     */
    if ((ret = avcodec_decode_audio4(audio_input_context, input_frame,
            &data_present, ipkt)) < 0) {
      Error( "Could not decode frame (error '%s')\n",
          av_make_error_string(ret).c_str());
      dumpPacket( ipkt );
      av_frame_free( &input_frame );
      return 0;
    }
    if ( ! data_present ) {
      Debug(2, "Not ready to transcode a frame yet.");
      return 0;
    }
#endif
    int frame_size = input_frame->nb_samples;
    Debug(4, "Frame size: %d", frame_size );

    // Resample the input into the audioSampleBuffer until we proceed the whole decoded data
    if ( (ret = avresample_convert( resample_context,
            NULL,
            0,
            0,
            input_frame->data,
            0,
            input_frame->nb_samples )) < 0 ) {
      Error( "Could not resample frame (error '%s')\n",
          av_make_error_string(ret).c_str());
      return 0;
    }

    if ( avresample_available( resample_context ) < output_frame->nb_samples ) {
      Debug(1, "No enough samples yet");
      return 0;
    }

    // Read a frame audio data from the resample fifo
    if ( avresample_read( resample_context, output_frame->data, output_frame->nb_samples ) != output_frame->nb_samples ) {
      Warning( "Error reading resampled audio: " );
      return 0;
    }

    av_init_packet(&opkt);
    Debug(5, "after init packet" );

    /** Set a timestamp based on the sample rate for the container. */
    //output_frame->pts = av_rescale_q( opkt.pts, audio_output_context->time_base, audio_output_stream->time_base );

    // convert the packet to the codec timebase from the stream timebase
    //Debug(3, "output_frame->pts(%d) best effort(%d)", output_frame->pts, 
        //av_frame_get_best_effort_timestamp(output_frame)
        //);
    /**
     * Encode the audio frame and store it in the temporary packet.
     * The output audio stream encoder is used to do this.
     */
#if LIBAVCODEC_VERSION_CHECK(57, 64, 0, 64, 0)
    if (( ret = avcodec_send_frame( audio_output_context, output_frame ) ) < 0 ) {
      Error( "Could not send frame (error '%s')",
          av_make_error_string(ret).c_str());
      zm_av_packet_unref(&opkt);
      return 0;
    }

    if (( ret = avcodec_receive_packet( audio_output_context, &opkt )) < 0 ) {
      Error( "Could not recieve packet (error '%s')",
          av_make_error_string(ret).c_str());
      zm_av_packet_unref(&opkt);
      return 0;
    }
#else
    if (( ret = avcodec_encode_audio2( audio_output_context, &opkt, output_frame, &data_present )) < 0) {
      Error( "Could not encode frame (error '%s')",
          av_make_error_string(ret).c_str());
      zm_av_packet_unref(&opkt);
      return 0;
    }
    if ( ! data_present ) {
      Debug(2, "Not ready to output a frame yet.");
      zm_av_packet_unref(&opkt);
      return 0;
    }
#endif

#endif
  } else {
    av_init_packet(&opkt);
    Debug(5, "after init packet" );
    opkt.data = ipkt->data;
    opkt.size = ipkt->size;
  }

  // PTS is difficult, because of the buffering of the audio packets in the resampler.  So we have to do it once we actually have a packet... 

  //Scale the PTS of the outgoing packet to be the correct time base
  if ( ipkt->pts != AV_NOPTS_VALUE ) {
    if ( ! audio_last_pts ) {
      opkt.pts = 0;
      Debug(1, "No audio_last_pts");
    } else {
      if ( audio_last_pts > ipkt->pts ) {
        Debug(1, "Resetting audeo_start_pts from (%d) to (%d)",  audio_last_pts, ipkt->pts );
        opkt.pts = audio_previous_pts + av_rescale_q(ipkt->pts, audio_input_stream->time_base, audio_output_stream->time_base);
      } else {
        opkt.pts = audio_previous_pts + av_rescale_q(ipkt->pts - audio_last_pts, audio_input_stream->time_base, audio_output_stream->time_base);
      }
      Debug(2, "audio opkt.pts = %d from ipkt->pts(%d) - last_pts(%d)", opkt.pts, ipkt->pts, audio_last_pts );
    }
    audio_last_pts = ipkt->pts;
  } else {
    Debug(2, "opkt.pts = undef");
    opkt.pts = AV_NOPTS_VALUE;
  }

  //Scale the DTS of the outgoing packet to be the correct time base
  if ( ! audio_last_dts ) {
    opkt.dts = 0;
    
  } else {
    if( ipkt->dts == AV_NOPTS_VALUE ) {
      // So if the input has no dts assigned... still need an output dts... so we use cur_dts?

      if ( audio_last_dts > audio_input_stream->cur_dts ) {
        Debug(1, "Resetting audio_last_dts from (%d) to cur_dts (%d)", audio_last_dts, audio_input_stream->cur_dts );
        opkt.dts = audio_previous_dts + av_rescale_q( audio_input_stream->cur_dts,  AV_TIME_BASE_Q, audio_output_stream->time_base);
      } else {
        opkt.dts = audio_previous_dts + av_rescale_q( audio_input_stream->cur_dts - audio_last_dts, AV_TIME_BASE_Q, audio_output_stream->time_base);
      }
      audio_last_dts = audio_input_stream->cur_dts;
      Debug(2, "opkt.dts = %d from video_input_stream->cur_dts(%d) - last_dts(%d)", opkt.dts, audio_input_stream->cur_dts, audio_last_dts );
    } else {
      if ( audio_last_dts > ipkt->dts ) {
        Debug(1, "Resetting audio_last_dts from (%d) to (%d)",  audio_last_dts, ipkt->dts );
        opkt.dts = audio_previous_dts + av_rescale_q(ipkt->dts, audio_input_stream->time_base, audio_output_stream->time_base);
      } else {
        opkt.dts = audio_previous_dts + av_rescale_q(ipkt->dts - audio_last_dts, audio_input_stream->time_base, audio_output_stream->time_base);
      }
      Debug(2, "opkt.dts = %d from ipkt->dts(%d) - last_dts(%d)", opkt.dts, ipkt->dts, audio_last_dts );
    }
  }
  audio_last_dts = ipkt->dts;
  if ( opkt.dts > opkt.pts ) {
    Debug(1,"opkt.dts(%d) must be <= opkt.pts(%d). Decompression must happen before presentation.", opkt.dts, opkt.pts );
    opkt.dts = opkt.pts;
  }

  // I wonder if we could just use duration instead of all the hoop jumping above?
  opkt.duration = av_rescale_q(ipkt->duration, audio_input_stream->time_base, audio_output_stream->time_base);
  Debug( 2, "opkt.pts (%d), opkt.dts(%d) opkt.duration = (%d)", opkt.pts, opkt.dts, opkt.duration );

  // pkt.pos:  byte position in stream, -1 if unknown 
  opkt.pos = -1;
  opkt.stream_index = ipkt->stream_index;
  Debug(2, "Stream index is %d", opkt.stream_index );

  AVPacket safepkt;
  memcpy(&safepkt, &opkt, sizeof(AVPacket));
  audio_previous_dts = opkt.dts; // Unsure if av_interleaved_write_frame() clobbers opkt.dts when out of order, so storing in advance
  audio_previous_pts = opkt.pts;
  ret = av_interleaved_write_frame(oc, &opkt);
  if(ret!=0){
    Error("Error writing audio frame packet: %s\n", av_make_error_string(ret).c_str());
    dumpPacket(&safepkt);
  } else {
    Debug(2,"Success writing audio frame" ); 
  }
  zm_av_packet_unref(&opkt);
  return 0;
} // end int VideoStore::writeAudioFramePacket( AVPacket *ipkt )

