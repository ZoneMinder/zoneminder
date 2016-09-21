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
    AVStream *input_video_stream,
    AVStream *input_audio_stream,
    int64_t nStartTime,
    Monitor::Orientation orientation
    ) {

  //store inputs in variables local to class
  filename = filename_in;
  format = format_in;

  keyframeMessage = false;
  keyframeSkipNumber = 0;

  Info("Opening video storage stream %s format: %s\n", filename, format);

  int ret;
  static char error_buffer[255];
  //Init everything we need, shouldn't have to do this, ffmpeg_camera or something else will call it.
  //av_register_all();

  ret = avformat_alloc_output_context2(&oc, NULL, NULL, filename);
  if ( ret < 0 ) {
    Warning("Could not create video storage stream %s as no output context"
        " could be assigned based on filename: %s",
        filename,
        av_make_error_string(ret).c_str()
        );
  }

  //Couldn't deduce format from filename, trying from format name
  if (!oc) {
    avformat_alloc_output_context2(&oc, NULL, format, filename);
    if (!oc) {
      Fatal("Could not create video storage stream %s as no output context"
          " could not be assigned based on filename or format %s",
          filename, format);
    }
  }

  AVDictionary *pmetadata = NULL;
  int dsr = av_dict_set(&pmetadata, "title", "Zoneminder Security Recording", 0);
  if (dsr < 0) Warning("%s:%d: title set failed", __FILE__, __LINE__ );

  oc->metadata = pmetadata;

  output_format = oc->oformat;

  video_stream = avformat_new_stream(oc, input_video_stream->codec->codec);
  if (!video_stream) {
    Fatal("Unable to create video out stream\n");
  } else {
    Debug(3, "Success creating video out stream" );
  }

  ret = avcodec_copy_context(video_stream->codec, input_video_stream->codec);
  if (ret < 0) { 
    Fatal("Unable to copy input video context to output video context %s\n", 
        av_make_error_string(ret).c_str());
  } else {
    Debug(3, "Success copying context" );
  }

 Debug(3, "Time bases input stream time base(%d/%d) input codec tb: (%d/%d) video_stream->time-base(%d/%d) output codec tb (%d/%d)", 
        input_video_stream->time_base.num,
        input_video_stream->time_base.den,
        input_video_stream->codec->time_base.num,
        input_video_stream->codec->time_base.den,
        video_stream->time_base.num,
        video_stream->time_base.den,
        video_stream->codec->time_base.num,
        video_stream->codec->time_base.den
        );

  if ( input_video_stream->codec->sample_aspect_ratio.den && ( video_stream->sample_aspect_ratio.den != input_video_stream->codec->sample_aspect_ratio.den ) ) {
	  Warning("Fixing sample_aspect_ratio.den from (%d) to (%d)", video_stream->sample_aspect_ratio.den, input_video_stream->codec->sample_aspect_ratio.den );
	  video_stream->sample_aspect_ratio.den = input_video_stream->codec->sample_aspect_ratio.den;
  } else {
    Debug(3, "aspect ratio denominator is (%d)", video_stream->sample_aspect_ratio.den  );
  }
  if ( input_video_stream->codec->sample_aspect_ratio.num && ( video_stream->sample_aspect_ratio.num != input_video_stream->codec->sample_aspect_ratio.num ) ) {
	  Warning("Fixing sample_aspect_ratio.num from video_stream(%d) to input_video_stream(%d)", video_stream->sample_aspect_ratio.num, input_video_stream->codec->sample_aspect_ratio.num );
	  video_stream->sample_aspect_ratio.num = input_video_stream->codec->sample_aspect_ratio.num;
  } else {
    Debug(3, "aspect ratio numerator is (%d)", video_stream->sample_aspect_ratio.num  );
  }
  if ( video_stream->codec->codec_id != input_video_stream->codec->codec_id ) {
	  Warning("Fixing video_stream->codec->codec_id");
	  video_stream->codec->codec_id = input_video_stream->codec->codec_id;
  }
  if ( ! video_stream->codec->time_base.num ) {
	  Warning("video_stream->codec->time_base.num is not set%d/%d. Fixing by setting it to 1", video_stream->codec->time_base.num, video_stream->codec->time_base.den);	
	  Warning("video_stream->codec->time_base.num is not set%d/%d. Fixing by setting it to 1", video_stream->time_base.num, video_stream->time_base.den);	
	  video_stream->codec->time_base.num = video_stream->time_base.num;
	  video_stream->codec->time_base.den = video_stream->time_base.den;
  }

  if ( video_stream->sample_aspect_ratio.den != video_stream->codec->sample_aspect_ratio.den ) {
         Warning("Fixingample_aspect_ratio.den");
         video_stream->sample_aspect_ratio.den = video_stream->codec->sample_aspect_ratio.den;
  }
  if ( video_stream->sample_aspect_ratio.num != input_video_stream->codec->sample_aspect_ratio.num ) {
         Warning("Fixingample_aspect_ratio.num");
         video_stream->sample_aspect_ratio.num = input_video_stream->codec->sample_aspect_ratio.num;
  }
  if ( video_stream->codec->codec_id != input_video_stream->codec->codec_id ) {
         Warning("Fixing video_stream->codec->codec_id");
         video_stream->codec->codec_id = input_video_stream->codec->codec_id;
  }
  if ( ! video_stream->codec->time_base.num ) {
         Warning("video_stream->codec->time_base.num is not set%d/%d. Fixing by setting it to 1", video_stream->codec->time_base.num, video_stream->codec->time_base.den); 
         Warning("video_stream->codec->time_base.num is not set%d/%d. Fixing by setting it to 1", video_stream->time_base.num, video_stream->time_base.den);       
         video_stream->codec->time_base.num = video_stream->time_base.num;
         video_stream->codec->time_base.den = video_stream->time_base.den;
  }

       // WHY?
  //video_stream->codec->codec_tag = 0;
  if (!video_stream->codec->codec_tag) {
         if (! oc->oformat->codec_tag
                         || av_codec_get_id (oc->oformat->codec_tag, input_video_stream->codec->codec_tag) == video_stream->codec->codec_id
                         || av_codec_get_tag(oc->oformat->codec_tag, input_video_stream->codec->codec_id) <= 0) {
                 Warning("Setting codec tag");
                 video_stream->codec->codec_tag = input_video_stream->codec->codec_tag;
         }
  }


  if (oc->oformat->flags & AVFMT_GLOBALHEADER) {
    video_stream->codec->flags |= CODEC_FLAG_GLOBAL_HEADER;
  }

  if ( orientation ) {
    if ( orientation == Monitor::ROTATE_0 ) {

    } else if ( orientation == Monitor::ROTATE_90 ) {
      dsr = av_dict_set( &video_stream->metadata, "rotate", "90", 0);
      if (dsr < 0) Warning("%s:%d: title set failed", __FILE__, __LINE__ );
    } else if ( orientation == Monitor::ROTATE_180 ) {
      dsr = av_dict_set( &video_stream->metadata, "rotate", "180", 0);
      if (dsr < 0) Warning("%s:%d: title set failed", __FILE__, __LINE__ );
    } else if ( orientation == Monitor::ROTATE_270 ) {
      dsr = av_dict_set( &video_stream->metadata, "rotate", "270", 0);
      if (dsr < 0) Warning("%s:%d: title set failed", __FILE__, __LINE__ );
    } else {
      Warning( "Unsupported Orientation(%d)", orientation );
    }
  }
audio_output_codec = NULL;

  if (input_audio_stream) {

    if ( input_audio_stream->codec->codec_id != AV_CODEC_ID_AAC ) {
      Warning("Can't transcode to AAC at this time");
      audio_stream = NULL;

      audio_output_codec = avcodec_find_encoder(AV_CODEC_ID_AAC);
      if ( audio_output_codec ) {
        audio_stream = avformat_new_stream(oc, audio_output_codec );

        audio_output_context = audio_stream->codec;

        //audio_output_context = avcodec_alloc_context3( audio_output_codec );
        if ( audio_output_context ) {

Debug(2, "Have audio_output_context");
          AVDictionary *opts = NULL;
          av_dict_set(&opts, "strict", "experimental", 0);

          /* put sample parameters */
          audio_output_context->bit_rate = input_audio_stream->codec->bit_rate;
          audio_output_context->sample_rate = input_audio_stream->codec->sample_rate;
          audio_output_context->channels = input_audio_stream->codec->channels;
          audio_output_context->channel_layout = input_audio_stream->codec->channel_layout;
          audio_output_context->sample_fmt = input_audio_stream->codec->sample_fmt;

          /* check that the encoder supports s16 pcm input */
          if (!check_sample_fmt( audio_output_codec, audio_output_context->sample_fmt)) {
            Error( "Encoder does not support sample format %s, setting to FLTP",
                av_get_sample_fmt_name( audio_output_context->sample_fmt));
            audio_output_context->sample_fmt = AV_SAMPLE_FMT_FLTP;
          }
  
          Debug(1, "Audio output bit_rate (%d) sample_rate(%d) channels(%d) fmt(%d) layout(%d)", 
              audio_output_context->bit_rate,
              audio_output_context->sample_rate,
              audio_output_context->channels,
              audio_output_context->sample_fmt,
              audio_output_context->channel_layout
              );

          /** Set the sample rate for the container. */
          audio_stream->time_base.den = input_audio_stream->codec->sample_rate;
          audio_stream->time_base.num = 1;

          ret = avcodec_open2(audio_output_context, audio_output_codec, &opts );
          if ( ret < 0 ) {
            av_strerror(ret, error_buffer, sizeof(error_buffer));
            Fatal( "could not open codec (%d) (%s)\n", ret, error_buffer );
          } else {
            Debug(2, "Success opening AAC codec");
          } 
          av_dict_free(&opts);
        } else {
          Error( "could not allocate codec context for AAC\n");
        }
      } else {
         Error( "could not find codec for AAC\n");
      }

    } else {
      Debug(3, "Got something other than AAC (%d)", input_audio_stream->codec->codec_id );

      audio_stream = avformat_new_stream(oc, (AVCodec *)input_audio_stream->codec->codec);
      if (!audio_stream) {
        Error("Unable to create audio out stream\n");
        audio_stream = NULL;
      }
      ret = avcodec_copy_context(audio_stream->codec, input_audio_stream->codec);
      if (ret < 0) {
        Fatal("Unable to copy audio context %s\n", av_make_error_string(ret).c_str());
      }   
      audio_stream->codec->codec_tag = 0;
      if ( audio_stream->codec->channels > 1 ) {
        Warning("Audio isn't mono, changing it.");
        audio_stream->codec->channels = 1;
      }
      if (oc->oformat->flags & AVFMT_GLOBALHEADER) {
        audio_stream->codec->flags |= CODEC_FLAG_GLOBAL_HEADER;
      }
    } // end if is AAC
  } else {
    Debug(3, "No Audio output stream");
    audio_stream = NULL;
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

  /* Write the stream header, if any. */
  ret = avformat_write_header(oc, NULL);
  if (ret < 0) {
    zm_dump_stream_format( oc, 0, 0, 1 );
    if ( audio_stream ) 
    zm_dump_stream_format( oc, 1, 0, 1 );
    Error("Error occurred when writing output file header to %s: %s\n",
        filename,
        av_make_error_string(ret).c_str());
  }

  prevDts = 0;
  startPts = 0;
  startDts = 0;
  filter_in_rescale_delta_last = AV_NOPTS_VALUE;

  // now - when streaming started
  startTime=av_gettime()-nStartTime;//oc->start_time;
  Info("VideoStore startTime=%d\n",startTime);
} // VideoStore::VideoStore


VideoStore::~VideoStore(){
  /* Write the trailer before close */
  if ( int rc = av_write_trailer(oc) ) {
    Error("Error writing trailer %s",  av_err2str( rc ) );
  } else {
    Debug(3, "Sucess Writing trailer");
  }

  // I wonder if we should be closing the file first.
  // I also wonder if we really need to be doing all the context allocation/de-allocation constantly, or whether we can just re-use it.  Just do a file open/close/writeheader/etc.
  // What if we were only doing audio recording?
  if ( video_stream ) {
    avcodec_close(video_stream->codec);
  }
  if (audio_stream) {
    avcodec_close(audio_stream->codec);
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
      , pkt->convergence_duration
      );
  Debug(1, "%s:%d:DEBUG: %s", __FILE__, __LINE__, b);
}

int VideoStore::writeVideoFramePacket(AVPacket *ipkt, AVStream *input_video_stream){

  Debug(4, "writeVideoFrame");
  //Debug(3, "before ost_tbcket starttime %d, timebase%d", startTime, video_stream->time_base );
  //zm_dump_stream_format( oc, ipkt->stream_index, 0, 1 );
  int64_t ost_tb_start_time = av_rescale_q(startTime, AV_TIME_BASE_Q, video_stream->time_base);
  //Debug(2, "before ost_tbcket starttime %d, ost_tbcket %d", startTime, ost_tb_start_time );

  AVPacket opkt;
  AVPicture pict;

  Debug(4, "writeVideoFrame init_packet");
  av_init_packet(&opkt);

if ( 1 ) {
  //Scale the PTS of the outgoing packet to be the correct time base
  if (ipkt->pts != AV_NOPTS_VALUE) {
    if ( ! startPts ) {
      //never gets set, so the first packet can set it.
      startPts = ipkt->pts;
    }
    opkt.pts = av_rescale_q(ipkt->pts-startPts, input_video_stream->time_base, video_stream->time_base);
 //- ost_tb_start_time;
    Debug(3, "opkt.pts = %d from ipkt->pts(%d) - startPts(%d)", opkt.pts, ipkt->pts, startPts );
  } else {
    Debug(3, "opkt.pts = undef");
    opkt.pts = AV_NOPTS_VALUE;
  }

  //Scale the DTS of the outgoing packet to be the correct time base
  if(ipkt->dts == AV_NOPTS_VALUE) {
    if ( ! startDts ) startDts = input_video_stream->cur_dts;
    opkt.dts = av_rescale_q(input_video_stream->cur_dts-startDts, AV_TIME_BASE_Q, video_stream->time_base);
    Debug(3, "opkt.dts = %d from input_video_stream->cur_dts(%d) - startDts(%d)", 
        opkt.dts, input_video_stream->cur_dts, startDts
        );
  } else {
    if ( ! startDts ) startDts = ipkt->dts;
    opkt.dts = av_rescale_q(ipkt->dts - startDts, input_video_stream->time_base, video_stream->time_base);
    Debug(3, "opkt.dts = %d from ipkt->dts(%d) - startDts(%d)", opkt.dts, ipkt->dts, startDts );
  }
  if ( opkt.dts > opkt.pts ) {
    Warning("opkt.dts(%d) must be <= opkt.pts(%d). Decompression must happen before presentation.", opkt.dts, opkt.pts );
    opkt.dts = opkt.pts;
  }

  //opkt.dts -= ost_tb_start_time;

  opkt.duration = av_rescale_q(ipkt->duration, input_video_stream->time_base, video_stream->time_base);
} else {
  av_packet_rescale_ts( &opkt, input_video_stream->time_base, video_stream->time_base );
}
  opkt.flags = ipkt->flags;
  opkt.pos=-1;

  opkt.data = ipkt->data;
  opkt.size = ipkt->size;
  if ( ipkt->stream_index > 0 and ! audio_stream ) {
Warning("Setting stream index to 0 instead of %d", ipkt->stream_index );
  opkt.stream_index = 0;
  } else {
  opkt.stream_index = ipkt->stream_index;
  }
  
  /*opkt.flags |= AV_PKT_FLAG_KEY;*/

  if (video_stream->codec->codec_type == AVMEDIA_TYPE_VIDEO && (output_format->flags & AVFMT_RAWPICTURE)) {
Debug(3, "video and RAWPICTURE");
    /* store AVPicture in AVPacket, as expected by the output format */
    avpicture_fill(&pict, opkt.data, video_stream->codec->pix_fmt, video_stream->codec->width, video_stream->codec->height);
    opkt.data = (uint8_t *)&pict;
    opkt.size = sizeof(AVPicture);
    opkt.flags |= AV_PKT_FLAG_KEY;
   } else {
Debug(4, "Not video and RAWPICTURE");
  }

  //memcpy(&safepkt, &opkt, sizeof(AVPacket));

  if ((opkt.data == NULL)||(opkt.size < 1)) {
    Warning("%s:%d: Mangled AVPacket: discarding frame", __FILE__, __LINE__ ); 
    dumpPacket( ipkt);
    dumpPacket(&opkt);

  } else if ((prevDts > 0) && (prevDts > opkt.dts)) {
    Warning("%s:%d: DTS out of order: %lld \u226E %lld; discarding frame", __FILE__, __LINE__, prevDts, opkt.dts); 
    prevDts = opkt.dts; 
    dumpPacket(&opkt);

  } else {
    int ret;

    prevDts = opkt.dts; // Unsure if av_interleaved_write_frame() clobbers opkt.dts when out of order, so storing in advance
    dumpPacket(&opkt);
    ret = av_interleaved_write_frame(oc, &opkt);
    if(ret<0){
      // There's nothing we can really do if the frame is rejected, just drop it and get on with the next
      Warning("%s:%d: Writing frame [av_interleaved_write_frame()] failed: %s(%d)  ", __FILE__, __LINE__,  av_make_error_string(ret).c_str(), (ret));
      dumpPacket(&opkt);
    }
  }

  zm_av_unref_packet(&opkt); 

  return 0;

}

int VideoStore::writeAudioFramePacket(AVPacket *ipkt, AVStream *input_audio_stream){
  Debug(2, "writeAudioFrame");

  if(!audio_stream) {
    Error("Called writeAudioFramePacket when no audio_stream");
    return 0;//FIXME -ve return codes do not free packet in ffmpeg_camera at the moment
  }
  /*if(!keyframeMessage)
    return -1;*/
  //zm_dump_stream_format( oc, ipkt->stream_index, 0, 1 );

  int ret;
  // What is this doing?  Getting the time of the start of this video chunk? Does that actually make sense?
  int64_t ost_tb_start_time = av_rescale_q(startTime, AV_TIME_BASE_Q, audio_stream->time_base);

  AVPacket opkt;

  av_init_packet(&opkt);
  Debug(3, "after init packet" );

  //Scale the PTS of the outgoing packet to be the correct time base
  if (ipkt->pts != AV_NOPTS_VALUE) {
    Debug(2, "Rescaling output pts");
    opkt.pts = av_rescale_q(ipkt->pts-startPts, input_audio_stream->time_base, audio_stream->time_base) - ost_tb_start_time;
  } else {
    Debug(2, "Setting output pts to AV_NOPTS_VALUE");
    opkt.pts = AV_NOPTS_VALUE;
  }

  //Scale the DTS of the outgoing packet to be the correct time base
  if(ipkt->dts == AV_NOPTS_VALUE) {
    Debug(2, "ipkt->dts == AV_NOPTS_VALUE %d to %d",  AV_NOPTS_VALUE, opkt.dts );
    opkt.dts = av_rescale_q(input_audio_stream->cur_dts-startDts, AV_TIME_BASE_Q, audio_stream->time_base);
    Debug(2, "ipkt->dts == AV_NOPTS_VALUE %d to %d",  AV_NOPTS_VALUE, opkt.dts );
  } else {
    Debug(2, "ipkt->dts != AV_NOPTS_VALUE %d to %d",  AV_NOPTS_VALUE, opkt.dts );
    opkt.dts = av_rescale_q(ipkt->dts-startDts, input_audio_stream->time_base, audio_stream->time_base);
    Debug(2, "ipkt->dts != AV_NOPTS_VALUE %d to %d",  AV_NOPTS_VALUE, opkt.dts );
  }
  Debug(2, "Not sure what ost_tb_start_time is (%d) - (%d)", opkt.dts, ost_tb_start_time );
  opkt.dts -= ost_tb_start_time;

  // Seems like it would be really weird for the codec type to NOT be audiu
  if (audio_stream->codec->codec_type == AVMEDIA_TYPE_AUDIO && ipkt->dts != AV_NOPTS_VALUE) {
    int duration = av_get_audio_frame_duration(input_audio_stream->codec, ipkt->size);
    Debug( 1, "code is audio, dts != AV_NOPTS_VALUE got duration(%d)", duration );
    if ( ! duration ) {
      duration = input_audio_stream->codec->frame_size;
      Warning( "got no duration from av_get_audio_frame_duration.  Using frame size(%d)", duration );
    }

    //FIXME where to get filter_in_rescale_delta_last
    //FIXME av_rescale_delta doesn't exist in ubuntu vivid libavtools
    opkt.dts = opkt.pts = av_rescale_delta(input_audio_stream->time_base, ipkt->dts,
        (AVRational){1, input_audio_stream->codec->sample_rate}, duration, &filter_in_rescale_delta_last,
        audio_stream->time_base) - ost_tb_start_time;
    Debug(2, "rescaled dts is: (%d)", opkt.dts );
  }

  opkt.duration = av_rescale_q(ipkt->duration, input_audio_stream->time_base, audio_stream->time_base);
  opkt.pos=-1;
  opkt.flags = ipkt->flags;
  opkt.stream_index = ipkt->stream_index;

  if ( audio_output_codec ) {

  

    AVFrame *input_frame;
    AVFrame *output_frame;
  // Need to re-encode
if ( 0 ) {
  //avcodec_send_packet( input_audio_stream->codec, ipkt);
  //avcodec_receive_frame( input_audio_stream->codec, input_frame );
  //avcodec_send_frame( audio_stream->codec, input_frame );
//
  ////avcodec_receive_packet( audio_stream->codec, &opkt );
} else {

    /** Create a new frame to store the audio samples. */
    if (!(input_frame = av_frame_alloc())) {
        Error("Could not allocate input frame");
        zm_av_unref_packet(&opkt);
        return 0;
    } else {
      Debug(2, "Got input frame alloc");
    }

    /**
     * Decode the audio frame stored in the packet.
     * The input audio stream decoder is used to do this.
     * If we are at the end of the file, pass an empty packet to the decoder
     * to flush it.
     */
    if ((ret = avcodec_decode_audio4(input_audio_stream->codec, input_frame,
                                       &data_present, ipkt)) < 0) {
        Error( "Could not decode frame (error '%s')\n",
                av_make_error_string(ret).c_str());
        dumpPacket( ipkt);
        av_frame_free(&input_frame);
        zm_av_unref_packet(&opkt);
        return 0;
    }
    
    /** Create a new frame to store the audio samples. */
    if (!(output_frame = av_frame_alloc())) {
        Error("Could not allocate output frame");
        av_frame_free(&input_frame);
        zm_av_unref_packet(&opkt);
        return 0;
    } else {
      Debug(2, "Got output frame alloc");
    }
    /**
     * Set the frame's parameters, especially its size and format.
     * av_frame_get_buffer needs this to allocate memory for the
     * audio samples of the frame.
     * Default channel layouts based on the number of channels
     * are assumed for simplicity.
     */
    output_frame->nb_samples     = audio_stream->codec->frame_size;
    output_frame->channel_layout = audio_output_context->channel_layout;
    output_frame->channels = audio_output_context->channels;
    output_frame->format         = audio_output_context->sample_fmt;
    output_frame->sample_rate    = audio_output_context->sample_rate;
    /**
     * Allocate the samples of the created frame. This call will make
     * sure that the audio frame can hold as many samples as specified.
     */
    Debug(2, "getting buffer");
    if (( ret = av_frame_get_buffer( output_frame, 0)) < 0) {
        Error( "Couldnt allocate output frame buffer samples (error '%s')",
                av_make_error_string(ret).c_str() );
        Error("Frame: samples(%d) layout (%d) format(%d) rate(%d)", output_frame->nb_samples,
output_frame->channel_layout, output_frame->format , output_frame->sample_rate 
 );
        av_frame_free(&input_frame);
        av_frame_free(&output_frame);
        zm_av_unref_packet(&opkt);
        return 0;
    }

    /** Set a timestamp based on the sample rate for the container. */
    if (output_frame) {
        output_frame->pts = opkt.pts;
    }
    /**
     * Encode the audio frame and store it in the temporary packet.
     * The output audio stream encoder is used to do this.
     */
    if (( ret = avcodec_encode_audio2( audio_output_context, &opkt,
                                       input_frame, &data_present )) < 0) {
        Error( "Could not encode frame (error '%s')",
                av_make_error_string(ret).c_str());
        zm_av_unref_packet(&opkt);
        return 0;
    }
}
  } else {
    opkt.data = ipkt->data;
    opkt.size = ipkt->size;
  }
  ret = av_interleaved_write_frame(oc, &opkt);
  if(ret!=0){
    Fatal("Error encoding audio frame packet: %s\n", av_make_error_string(ret).c_str());
  }
  Debug(4,"Success writing audio frame" ); 
  zm_av_unref_packet(&opkt);
  return 0;
}
