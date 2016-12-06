//
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

extern "C"{
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

  video_input_context = video_input_stream->codec;

  //store inputs in variables local to class
  filename = filename_in;
  format = format_in;

  keyframeMessage = false;
  keyframeSkipNumber = 0;

  Info("Opening video storage stream %s format: %s\n", filename, format);

  //Init everything we need, shouldn't have to do this, ffmpeg_camera or something else will call it.
  //av_register_all();

  ret = avformat_alloc_output_context2(&oc, NULL, NULL, filename);
  if ( ret < 0 ) {
    Warning("Could not create video storage stream %s as no output context"
        " could be assigned based on filename: %s",
        filename,
        av_make_error_string(ret).c_str()
        );
  } else {
    Debug(2, "Success alocateing output context");
  }

  //Couldn't deduce format from filename, trying from format name
  if (!oc) {
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

  video_output_stream = avformat_new_stream(oc, (AVCodec*)video_input_context->codec);
  if (!video_output_stream) {
    Fatal("Unable to create video out stream\n");
  } else {
    Debug(2, "Success creating video out stream" );
  }

  video_output_context = video_output_stream->codec;

#if LIBAVCODEC_VERSION_CHECK(57, 0, 0, 0, 0)
  Debug(2, "setting parameters");
  ret = avcodec_parameters_to_context( video_output_context, video_input_stream->codecpar );
  if ( ret < 0 ) {
    Error( "Could not initialize stream parameteres");
    return;
  } else {
    Debug(2, "Success getting parameters");
  }
#else
  ret = avcodec_copy_context(video_output_context, video_input_context );
  if (ret < 0) { 
    Fatal("Unable to copy input video context to output video context %s\n", 
        av_make_error_string(ret).c_str());
  } else {
    Debug(3, "Success copying context" );
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

#if 0
  if ( video_input_context->sample_aspect_ratio.den && ( video_output_stream->sample_aspect_ratio.den != video_input_context->sample_aspect_ratio.den ) ) {
	  Warning("Fixing sample_aspect_ratio.den from (%d) to (%d)", video_output_stream->sample_aspect_ratio.den, video_input_context->sample_aspect_ratio.den );
	  video_output_stream->sample_aspect_ratio.den = video_input_context->sample_aspect_ratio.den;
  } else {
    Debug(3, "aspect ratio denominator is (%d)", video_output_stream->sample_aspect_ratio.den  );
  }
  if ( video_input_context->sample_aspect_ratio.num && ( video_output_stream->sample_aspect_ratio.num != video_input_context->sample_aspect_ratio.num ) ) {
	  Warning("Fixing sample_aspect_ratio.num from video_output_stream(%d) to video_input_stream(%d)", video_output_stream->sample_aspect_ratio.num, video_input_context->sample_aspect_ratio.num );
	  video_output_stream->sample_aspect_ratio.num = video_input_context->sample_aspect_ratio.num;
  } else {
    Debug(3, "aspect ratio numerator is (%d)", video_output_stream->sample_aspect_ratio.num  );
  }
  if ( video_output_context->codec_id != video_input_context->codec_id ) {
	  Warning("Fixing video_output_context->codec_id");
	  video_output_context->codec_id = video_input_context->codec_id;
  }
  if ( ! video_output_context->time_base.num ) {
	  Warning("video_output_context->time_base.num is not set%d/%d. Fixing by setting it to 1", video_output_context->time_base.num, video_output_context->time_base.den);	
	  Warning("video_output_context->time_base.num is not set%d/%d. Fixing by setting it to 1", video_output_stream->time_base.num, video_output_stream->time_base.den);	
	  video_output_context->time_base.num = video_output_stream->time_base.num;
	  video_output_context->time_base.den = video_output_stream->time_base.den;
  }

  if ( video_output_stream->sample_aspect_ratio.den != video_output_context->sample_aspect_ratio.den ) {
         Warning("Fixingample_aspect_ratio.den");
         video_output_stream->sample_aspect_ratio.den = video_output_context->sample_aspect_ratio.den;
  }
  if ( video_output_stream->sample_aspect_ratio.num != video_input_context->sample_aspect_ratio.num ) {
         Warning("Fixingample_aspect_ratio.num");
         video_output_stream->sample_aspect_ratio.num = video_input_context->sample_aspect_ratio.num;
  }
  if ( video_output_context->codec_id != video_input_context->codec_id ) {
         Warning("Fixing video_output_context->codec_id");
         video_output_context->codec_id = video_input_context->codec_id;
  }
  if ( ! video_output_context->time_base.num ) {
         Warning("video_output_context->time_base.num is not set%d/%d. Fixing by setting it to 1", video_output_context->time_base.num, video_output_context->time_base.den); 
         Warning("video_output_context->time_base.num is not set%d/%d. Fixing by setting it to 1", video_output_stream->time_base.num, video_output_stream->time_base.den);       
         video_output_context->time_base.num = video_output_stream->time_base.num;
         video_output_context->time_base.den = video_output_stream->time_base.den;
  }
#endif

       // WHY?
  //video_output_context->codec_tag = 0;
  if (!video_output_context->codec_tag) {
    Debug(2, "No codec_tag");
    if (! oc->oformat->codec_tag
        || av_codec_get_id (oc->oformat->codec_tag, video_input_context->codec_tag) == video_output_context->codec_id
        || av_codec_get_tag(oc->oformat->codec_tag, video_input_context->codec_id) <= 0) {
      Warning("Setting codec tag");
      video_output_context->codec_tag = video_input_context->codec_tag;
    }
  }

  if (oc->oformat->flags & AVFMT_GLOBALHEADER) {
    video_output_context->flags |= CODEC_FLAG_GLOBAL_HEADER;
  }

	Monitor::Orientation orientation = monitor->getOrientation();
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

  if (audio_input_stream) {
    audio_input_context = audio_input_stream->codec;

    if ( audio_input_context->codec_id != AV_CODEC_ID_AAC ) {
#ifdef HAVE_LIBSWRESAMPLE
      resample_context = NULL;
      avcodec_string(error_buffer, sizeof(error_buffer), audio_input_context, 0 );
      Debug(3, "Got something other than AAC (%s)", error_buffer );
      audio_output_stream = NULL;

      audio_output_codec = avcodec_find_encoder(AV_CODEC_ID_AAC);
      if ( audio_output_codec ) {
Debug(2, "Have audio output codec");
        audio_output_stream = avformat_new_stream( oc, audio_output_codec );

        audio_output_context = audio_output_stream->codec;

        if ( audio_output_context ) {

Debug(2, "Have audio_output_context");
          AVDictionary *opts = NULL;
          av_dict_set(&opts, "strict", "experimental", 0);

          /* put sample parameters */
          audio_output_context->bit_rate = audio_input_context->bit_rate;
          audio_output_context->sample_rate = audio_input_context->sample_rate;
          audio_output_context->channels = audio_input_context->channels;
          audio_output_context->channel_layout = audio_input_context->channel_layout;
          audio_output_context->sample_fmt = audio_input_context->sample_fmt;
          //audio_output_context->refcounted_frames = 1;

          if (audio_output_codec->supported_samplerates) {
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
        if (!check_sample_fmt( audio_output_codec, audio_output_context->sample_fmt)) {
          Error( "Encoder does not support sample format %s, setting to FLTP",
              av_get_sample_fmt_name( audio_output_context->sample_fmt));
          audio_output_context->sample_fmt = AV_SAMPLE_FMT_FLTP;
        }

        //audio_output_stream->time_base = audio_input_stream->time_base;
        audio_output_context->time_base = (AVRational){ 1, audio_output_context->sample_rate };

        Debug(3, "Audio Time bases input stream (%d/%d) input codec: (%d/%d) output_stream (%d/%d) output codec (%d/%d)", 
            audio_input_stream->time_base.num,
            audio_input_stream->time_base.den,
            audio_input_context->time_base.num,
            audio_input_context->time_base.den,
            audio_output_stream->time_base.num,
            audio_output_stream->time_base.den,
            audio_output_context->time_base.num,
            audio_output_context->time_base.den
            );

        ret = avcodec_open2(audio_output_context, audio_output_codec, &opts );
        if ( ret < 0 ) {
          av_strerror(ret, error_buffer, sizeof(error_buffer));
          Fatal( "could not open codec (%d) (%s)\n", ret, error_buffer );
        } else {

          Debug(1, "Audio output bit_rate (%d) sample_rate(%d) channels(%d) fmt(%d) layout(%d) frame_size(%d), refcounted_frames(%d)", 
              audio_output_context->bit_rate,
              audio_output_context->sample_rate,
              audio_output_context->channels,
              audio_output_context->sample_fmt,
              audio_output_context->channel_layout,
              audio_output_context->frame_size,
              audio_output_context->refcounted_frames
              );
#if 1
          /** Create the FIFO buffer based on the specified output sample format. */
          if (!(fifo = av_audio_fifo_alloc(audio_output_context->sample_fmt,
                  audio_output_context->channels, 1))) {
            Error("Could not allocate FIFO\n");
            return;
          }
#endif
          output_frame_size = audio_output_context->frame_size;
          /** Create a new frame to store the audio samples. */
          if (!(input_frame = zm_av_frame_alloc())) {
            Error("Could not allocate input frame");
            return;
          }

          /** Create a new frame to store the audio samples. */
          if (!(output_frame = zm_av_frame_alloc())) {
            Error("Could not allocate output frame");
            av_frame_free(&input_frame);
            return;
          }
          /**
         * Create a resampler context for the conversion.
         * Set the conversion parameters.
         * Default channel layouts based on the number of channels
         * are assumed for simplicity (they are sometimes not detected
         * properly by the demuxer and/or decoder).
         */
        resample_context = swr_alloc_set_opts(NULL,
                                              av_get_default_channel_layout(audio_output_context->channels),
                                              audio_output_context->sample_fmt,
                                              audio_output_context->sample_rate,
                                              av_get_default_channel_layout( audio_input_context->channels),
                                              audio_input_context->sample_fmt,
                                              audio_input_context->sample_rate,
                                              0, NULL);
        if (!resample_context) {
            Error( "Could not allocate resample context\n");
            return;
        }
        /**
        * Perform a sanity check so that the number of converted samples is
        * not greater than the number of samples to be converted.
        * If the sample rates differ, this case has to be handled differently
        */
        av_assert0(audio_output_context->sample_rate == audio_input_context->sample_rate);
        /** Open the resampler with the specified parameters. */
        if ((ret = swr_init(resample_context)) < 0) {
            Error( "Could not open resample context\n");
            swr_free(&resample_context);
            return;
        }
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
        Debug(2, "Success opening AAC codec");
        } 
        av_dict_free(&opts);
        } else {
          Error( "could not allocate codec context for AAC\n");
        }
      } else {
        Error( "could not find codec for AAC\n");
      }
#else
      Error("Not built with libswresample library. Cannot do audio conversion to AAC");
      audio_output_stream = NULL;
#endif
    } else {
      Debug(3, "Got AAC" );

      audio_output_stream = avformat_new_stream(oc, (AVCodec*)audio_input_context->codec);
      if ( ! audio_output_stream ) {
        Error("Unable to create audio out stream\n");
        audio_output_stream = NULL;
      }
      audio_output_context = audio_output_stream->codec;

      ret = avcodec_copy_context(audio_output_context, audio_input_context);
      if (ret < 0) {
        Fatal("Unable to copy audio context %s\n", av_make_error_string(ret).c_str());
      }   
      audio_output_context->codec_tag = 0;
      if ( audio_output_context->channels > 1 ) {
        Warning("Audio isn't mono, changing it.");
        audio_output_context->channels = 1;
      } else {
        Debug(3, "Audio is mono");
      }
    } // end if is AAC

if ( audio_output_stream ) {
    if (oc->oformat->flags & AVFMT_GLOBALHEADER) {
      audio_output_context->flags |= CODEC_FLAG_GLOBAL_HEADER;
    }
    }

  } else {
    Debug(3, "No Audio output stream");
    audio_output_stream = NULL;
  }    

  /* open the output file, if needed */
  if (!(output_format->flags & AVFMT_NOFILE)) {
    ret = avio_open2(&oc->pb, filename, AVIO_FLAG_WRITE,NULL,NULL);
    if (ret < 0) {
      Fatal("Could not open output file '%s': %s\n", filename,
          av_make_error_string(ret).c_str());
    }
  }

  //av_dict_set(&opts, "movflags", "frag_custom+dash+delay_moov", 0);
  //if ((ret = avformat_write_header(ctx, &opts)) < 0) {
  //}
  //os->ctx_inited = 1;
  //avio_flush(ctx->pb);
  //av_dict_free(&opts);
  zm_dump_stream_format( oc, 0, 0, 1 );
  if ( audio_output_stream ) 
    zm_dump_stream_format( oc, 1, 0, 1 );

  /* Write the stream header, if any. */
  ret = avformat_write_header(oc, NULL);
  if (ret < 0) {
    Error("Error occurred when writing output file header to %s: %s\n",
        filename,
        av_make_error_string(ret).c_str());
  }

  prevDts = 0;
  video_start_pts = 0;
  video_start_dts = 0;
  audio_start_pts = 0;
  audio_start_dts = 0;

  filter_in_rescale_delta_last = AV_NOPTS_VALUE;

  // now - when streaming started
  //startTime=av_gettime()-nStartTime;//oc->start_time;
  //Info("VideoStore startTime=%d\n",startTime);
} // VideoStore::VideoStore


VideoStore::~VideoStore(){
  if ( audio_output_codec ) {
Debug(1, "Have audio encoder, need to flush it's output" );
    // Do we need to flush the outputs?  I have no idea.
    AVPacket pkt;
    int got_packet;
    av_init_packet(&pkt);
    pkt.data = NULL;
    pkt.size = 0;
    int64_t size;

    while(1) {
      ret = avcodec_encode_audio2( audio_output_context, &pkt, NULL, &got_packet );
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

#ifdef HAVE_LIBSWRESAMPLE
  if ( resample_context )
    swr_free( &resample_context );
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


  Debug(4, "writeVideoFrame init_packet");
  av_init_packet(&opkt);

if ( 1 ) {
  //Scale the PTS of the outgoing packet to be the correct time base
  if (ipkt->pts != AV_NOPTS_VALUE) {
    if ( (!video_start_pts) || (video_start_pts > ipkt->pts) ) {
      Debug(1, "Resetting video_start_pts from (%d) to (%d)",  video_start_pts, ipkt->pts );
      //never gets set, so the first packet can set it.
      video_start_pts = ipkt->pts;
    }
    opkt.pts = av_rescale_q(ipkt->pts - video_start_pts, video_input_stream->time_base, video_output_stream->time_base);
 //- ost_tb_start_time;
    Debug(3, "opkt.pts = %d from ipkt->pts(%d) - startPts(%d)", opkt.pts, ipkt->pts, video_start_pts );
  } else {
    Debug(3, "opkt.pts = undef");
    opkt.pts = AV_NOPTS_VALUE;
  }

  //Scale the DTS of the outgoing packet to be the correct time base
  if(ipkt->dts == AV_NOPTS_VALUE) {
    // why are we using cur_dts instead of packet.dts?
    if ( (!video_start_dts) || (video_start_dts > video_input_stream->cur_dts) ) {
      Debug(1, "Resetting video_start_dts from (%d) to (%d) p.dts was (%d)",  video_start_dts, video_input_stream->cur_dts, ipkt->dts );
      video_start_dts = video_input_stream->cur_dts;
    }
    opkt.dts = av_rescale_q(video_input_stream->cur_dts - video_start_dts, AV_TIME_BASE_Q, video_output_stream->time_base);
    Debug(3, "opkt.dts = %d from video_input_stream->cur_dts(%d) - startDts(%d)", 
        opkt.dts, video_input_stream->cur_dts, video_start_dts
        );
  } else {
    if ( (!video_start_dts) || (video_start_dts > ipkt->dts) ) {
      Debug(1, "Resetting video_start_dts from (%d) to (%d)",  video_start_dts, ipkt->dts );
      video_start_dts = ipkt->dts;
    }
    opkt.dts = av_rescale_q(ipkt->dts - video_start_dts, video_input_stream->time_base, video_output_stream->time_base);
    Debug(3, "opkt.dts = %d from ipkt->dts(%d) - startDts(%d)", opkt.dts, ipkt->dts, video_start_dts );
  }
  if ( opkt.dts > opkt.pts ) {
    Debug( 1, "opkt.dts(%d) must be <= opkt.pts(%d). Decompression must happen before presentation.", opkt.dts, opkt.pts );
    opkt.dts = opkt.pts;
  }

  opkt.duration = av_rescale_q(ipkt->duration, video_input_stream->time_base, video_output_stream->time_base);
} else {
  // Using this results in super fast video output, might be because it should be using the codec time base instead of stream tb
  //av_packet_rescale_ts( &opkt, video_input_stream->time_base, video_output_stream->time_base );
}

if ( opkt.dts != AV_NOPTS_VALUE ) {
  int64_t max = video_output_stream->cur_dts + !(oc->oformat->flags & AVFMT_TS_NONSTRICT);
  if ( video_output_stream->cur_dts && ( video_output_stream->cur_dts != AV_NOPTS_VALUE ) && ( max > opkt.dts ) ) {
    Warning("st:%d PTS: %"PRId64" DTS: %"PRId64" < %"PRId64" invalid, clipping", opkt.stream_index, opkt.pts, opkt.dts, max);
    if( opkt.pts >= opkt.dts)
      opkt.pts = FFMAX(opkt.pts, max);
    opkt.dts = max;
  }
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

  /*opkt.flags |= AV_PKT_FLAG_KEY;*/

#if 0
  if (video_output_context->codec_type == AVMEDIA_TYPE_VIDEO && (output_format->flags & AVFMT_RAWPICTURE)) {
  AVPicture pict;
Debug(3, "video and RAWPICTURE");
    /* store AVPicture in AVPacket, as expected by the output format */
    avpicture_fill(&pict, opkt.data, video_output_context->pix_fmt, video_output_context->width, video_output_context->height, 0);
  av_image_fill_arrays( 
    opkt.data = (uint8_t *)&pict;
    opkt.size = sizeof(AVPicture);
    opkt.flags |= AV_PKT_FLAG_KEY;
   } else {
Debug(4, "Not video and RAWPICTURE");
  }
#endif

  AVPacket safepkt;
  memcpy(&safepkt, &opkt, sizeof(AVPacket));

  if ((opkt.data == NULL)||(opkt.size < 1)) {
    Warning("%s:%d: Mangled AVPacket: discarding frame", __FILE__, __LINE__ ); 
    dumpPacket( ipkt);
    dumpPacket(&opkt);

  } else if ((prevDts > 0) && (prevDts > opkt.dts)) {
    Warning("%s:%d: DTS out of order: %lld \u226E %lld; discarding frame", __FILE__, __LINE__, prevDts, opkt.dts); 
    prevDts = opkt.dts; 
    dumpPacket(&opkt);

  } else {

    prevDts = opkt.dts; // Unsure if av_interleaved_write_frame() clobbers opkt.dts when out of order, so storing in advance
    ret = av_interleaved_write_frame(oc, &opkt);
    if(ret<0){
      // There's nothing we can really do if the frame is rejected, just drop it and get on with the next
      Warning("%s:%d: Writing frame [av_interleaved_write_frame()] failed: %s(%d)  ", __FILE__, __LINE__,  av_make_error_string(ret).c_str(), (ret));
      dumpPacket(&safepkt);
    }
  }

  zm_av_packet_unref(&opkt); 

  return 0;

}

int VideoStore::writeAudioFramePacket( AVPacket *ipkt ) {
  Debug(4, "writeAudioFrame");

  if(!audio_output_stream) {
    Debug(1, "Called writeAudioFramePacket when no audio_output_stream");
    return 0;//FIXME -ve return codes do not free packet in ffmpeg_camera at the moment
  }
  /*if(!keyframeMessage)
    return -1;*/
  //zm_dump_stream_format( oc, ipkt->stream_index, 0, 1 );

  av_init_packet(&opkt);
  Debug(5, "after init packet" );

#if 1
 //Scale the PTS of the outgoing packet to be the correct time base
  if ( ipkt->pts != AV_NOPTS_VALUE ) {
    if ( (!audio_start_pts) || ( audio_start_pts > ipkt->pts ) ) {
      Debug(1, "Resetting audeo_start_pts from (%d) to (%d)",  audio_start_pts, ipkt->pts );
      //never gets set, so the first packet can set it.
      audio_start_pts = ipkt->pts;
    }
    opkt.pts = av_rescale_q(ipkt->pts - audio_start_pts, audio_input_stream->time_base, audio_output_stream->time_base);
    Debug(2, "opkt.pts = %d from ipkt->pts(%d) - startPts(%d)", opkt.pts, ipkt->pts, audio_start_pts );
  } else {
    Debug(2, "opkt.pts = undef");
  }

  //Scale the DTS of the outgoing packet to be the correct time base
  if(ipkt->dts == AV_NOPTS_VALUE) {
    if ( (!audio_start_dts) || (audio_start_dts > audio_input_stream->cur_dts ) ) {
      Debug(1, "Resetting audio_start_pts from (%d) to cur_dts (%d)",  audio_start_dts, audio_input_stream->cur_dts );
      audio_start_dts = audio_input_stream->cur_dts;
    }
    opkt.dts = av_rescale_q(audio_input_stream->cur_dts - audio_start_dts, AV_TIME_BASE_Q, audio_output_stream->time_base);
    Debug(2, "opkt.dts = %d from video_input_stream->cur_dts(%d) - startDts(%d)",
        opkt.dts, audio_input_stream->cur_dts, audio_start_dts
        );
  } else {
    if ( ( ! audio_start_dts ) || ( audio_start_dts > ipkt->dts ) ) {
      Debug(1, "Resetting audeo_start_dts from (%d) to (%d)",  audio_start_dts, ipkt->dts );
      audio_start_dts = ipkt->dts;
    }
    opkt.dts = av_rescale_q(ipkt->dts - audio_start_dts, audio_input_stream->time_base, audio_output_stream->time_base);
    Debug(2, "opkt.dts = %d from ipkt->dts(%d) - startDts(%d)", opkt.dts, ipkt->dts, audio_start_dts );
  }
  if ( opkt.dts > opkt.pts ) {
    Debug(1,"opkt.dts(%d) must be <= opkt.pts(%d). Decompression must happen before presentation.", opkt.dts, opkt.pts );
    opkt.dts = opkt.pts;
  }
    //opkt.pts = AV_NOPTS_VALUE;
    //opkt.dts = AV_NOPTS_VALUE;

  opkt.duration = av_rescale_q(ipkt->duration, audio_input_stream->time_base, audio_output_stream->time_base);
#else
#endif

  // pkt.pos:  byte position in stream, -1 if unknown 
  opkt.pos = -1;
  opkt.flags = ipkt->flags;
  opkt.stream_index = ipkt->stream_index;
Debug(2, "Stream index is %d", opkt.stream_index );

  if ( audio_output_codec ) {

#ifdef HAVE_LIBSWRESAMPLE
  // Need to re-encode
#if 0
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

  ret = avcodec_send_frame( audio_output_context, input_frame );
  if ( ret < 0 ) {
    av_frame_unref( input_frame );
    Error("avcodec_send_frame fail(%d),  %s codec is open(%d) is_encoder(%d)", ret, av_make_error_string(ret).c_str(),
avcodec_is_open( audio_output_context ),
av_codec_is_encoder( audio_output_context->codec)
);
    return 0;
  }
  ret = avcodec_receive_packet( audio_output_context, &opkt );
  if ( ret < 0 ) {
    av_frame_unref( input_frame );
    Error("avcodec_receive_packet fail %s", av_make_error_string(ret).c_str());
    return 0;
  }
  av_frame_unref( input_frame );
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
        av_frame_free(&input_frame);
        zm_av_packet_unref(&opkt);
        return 0;
    }
    if ( ! data_present ) {
      Debug(2, "Not ready to transcode a frame yet.");
      zm_av_packet_unref(&opkt);
      return 0;
    }

    int frame_size = input_frame->nb_samples;
    Debug(4, "Frame size: %d", frame_size );


    Debug(4, "About to convert");

    /** Convert the samples using the resampler. */
    if ((ret = swr_convert(resample_context,
            &converted_input_samples, frame_size,
            (const uint8_t **)input_frame->extended_data    , frame_size)) < 0) {
      Error( "Could not convert input samples (error '%s')\n",
          av_make_error_string(ret).c_str()
          );
      return 0;
    }

    Debug(4, "About to realloc");
    if ((ret = av_audio_fifo_realloc(fifo, av_audio_fifo_size(fifo) + frame_size)) < 0) {
      Error( "Could not reallocate FIFO to %d\n", av_audio_fifo_size(fifo) + frame_size );
      return 0;
    }
    /** Store the new samples in the FIFO buffer. */
    Debug(4, "About to write");
    if (av_audio_fifo_write(fifo, (void **)&converted_input_samples, frame_size) < frame_size) {
      Error( "Could not write data to FIFO\n");
      return 0;
    }

    /**
     * Set the frame's parameters, especially its size and format.
     * av_frame_get_buffer needs this to allocate memory for the
     * audio samples of the frame.
     * Default channel layouts based on the number of channels
     * are assumed for simplicity.
     */
    output_frame->nb_samples     = audio_output_context->frame_size;
    output_frame->channel_layout = audio_output_context->channel_layout;
    output_frame->channels       = audio_output_context->channels;
    output_frame->format         = audio_output_context->sample_fmt;
    output_frame->sample_rate    = audio_output_context->sample_rate;
    /**
     * Allocate the samples of the created frame. This call will make
     * sure that the audio frame can hold as many samples as specified.
     */
    Debug(4, "getting buffer");
    if (( ret = av_frame_get_buffer( output_frame, 0)) < 0) {
      Error( "Couldnt allocate output frame buffer samples (error '%s')",
          av_make_error_string(ret).c_str() );
      Error("Frame: samples(%d) layout (%d) format(%d) rate(%d)", output_frame->nb_samples,
          output_frame->channel_layout, output_frame->format , output_frame->sample_rate 
          );
      zm_av_packet_unref(&opkt);
      return 0;
    }

    Debug(4, "About to read");
    if (av_audio_fifo_read(fifo, (void **)output_frame->data, frame_size) < frame_size) {
      Error( "Could not read data from FIFO\n");
      return 0;
    }

    /** Set a timestamp based on the sample rate for the container. */
    output_frame->pts = av_rescale_q( opkt.pts, audio_output_context->time_base, audio_output_stream->time_base );

  // convert the packet to the codec timebase from the stream timebase
Debug(3, "output_frame->pts(%d) best effort(%d)", output_frame->pts, 
av_frame_get_best_effort_timestamp(output_frame)
 );
    /**
     * Encode the audio frame and store it in the temporary packet.
     * The output audio stream encoder is used to do this.
     */
    if (( ret = avcodec_encode_audio2( audio_output_context, &opkt,
            output_frame, &data_present )) < 0) {
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
  

Debug(2, "opkt dts (%d) pts(%d) duration:(%d)", opkt.dts, opkt.pts, opkt.duration );

    // Convert tb from code back to stream
    //av_packet_rescale_ts(&opkt, audio_output_context->time_base, audio_output_stream->time_base);
if (opkt.pts != AV_NOPTS_VALUE)  {
             opkt.pts      = av_rescale_q( opkt.pts,      audio_output_context->time_base, audio_output_stream->time_base);
}
         if ( opkt.dts != AV_NOPTS_VALUE)
             opkt.dts      = av_rescale_q( opkt.dts,      audio_output_context->time_base, audio_output_stream->time_base);
         if ( opkt.duration > 0)
             opkt.duration = av_rescale_q( opkt.duration, audio_output_context->time_base, audio_output_stream->time_base);
Debug(2, "opkt dts (%d) pts(%d) duration:(%d) pos(%d) ", opkt.dts, opkt.pts, opkt.duration, opkt.pos );


//opkt.dts = AV_NOPTS_VALUE;
 

#endif
#endif
  } else {
    opkt.data = ipkt->data;
    opkt.size = ipkt->size;
  }

  AVPacket safepkt;
  memcpy(&safepkt, &opkt, sizeof(AVPacket));
  ret = av_interleaved_write_frame(oc, &opkt);
  if(ret!=0){
    Error("Error writing audio frame packet: %s\n", av_make_error_string(ret).c_str());
    dumpPacket(&safepkt);
  } else {
    Debug(2,"Success writing audio frame" ); 
  }
  zm_av_packet_unref(&opkt);
  return 0;
}
