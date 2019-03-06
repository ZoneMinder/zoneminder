
#include "zm_ffmpeg_input.h"
#include "zm_logger.h"
#include "zm_ffmpeg.h"

FFmpeg_Input::FFmpeg_Input() {
  input_format_context = NULL;
  video_stream_id = -1;
  audio_stream_id = -1;
  av_register_all();
  avcodec_register_all();
  streams = NULL;
  frame = NULL;
}

FFmpeg_Input::~FFmpeg_Input() {
  if ( streams ) {
    delete streams;
    streams = NULL;
  }
}

int FFmpeg_Input::Open( const char *filepath ) {

  int error;

  /** Open the input file to read from it. */
  if ( (error = avformat_open_input( &input_format_context, filepath, NULL, NULL)) < 0 ) {

    Error("Could not open input file '%s' (error '%s')\n",
        filepath, av_make_error_string(error).c_str() );
    input_format_context = NULL;
    return error;
  } 

  /** Get information on the input file (number of streams etc.). */
  if ( (error = avformat_find_stream_info(input_format_context, NULL)) < 0 ) {
    Error(
        "Could not open find stream info (error '%s')",
        av_make_error_string(error).c_str()
        );
    avformat_close_input(&input_format_context);
    return error;
  }

  streams = new stream[input_format_context->nb_streams];
  Debug(2,"Have %d streams", input_format_context->nb_streams);

  for ( unsigned int i = 0; i < input_format_context->nb_streams; i += 1 ) {
    if ( is_video_stream( input_format_context->streams[i] ) ) {
      zm_dump_stream_format(input_format_context, i, 0, 0);
      if ( video_stream_id == -1 ) {
        video_stream_id = i;
        // if we break, then we won't find the audio stream
      } else {
        Warning( "Have another video stream." );
      }
    } else if ( is_audio_stream( input_format_context->streams[i] ) ) {
      if ( audio_stream_id == -1 ) {
        Debug(2,"Audio stream is %d", i);
        audio_stream_id = i;
      } else {
        Warning( "Have another audio stream." );
      }
    } else {
      Warning("Unknown stream type");
    }

    streams[i].frame_count = 0;
#if LIBAVCODEC_VERSION_CHECK(57, 64, 0, 64, 0)
    streams[i].context = avcodec_alloc_context3( NULL );
    avcodec_parameters_to_context( streams[i].context, input_format_context->streams[i]->codecpar );
#else
    streams[i].context = input_format_context->streams[i]->codec;
#endif

    if ( !(streams[i].codec = avcodec_find_decoder(streams[i].context->codec_id)) ) {
      Error( "Could not find input codec\n");
      avformat_close_input(&input_format_context);
      return AVERROR_EXIT;
    } else {
      Debug(1, "Using codec (%s) for stream %d", streams[i].codec->name, i );
    }

    if ((error = avcodec_open2( streams[i].context, streams[i].codec, NULL)) < 0) {
      Error( "Could not open input codec (error '%s')\n",
          av_make_error_string(error).c_str() );
#if LIBAVCODEC_VERSION_CHECK(57, 64, 0, 64, 0)
      avcodec_free_context( &streams[i].context );
#endif
      avformat_close_input(&input_format_context);
      return error;
    }
  } // end foreach stream

  if ( video_stream_id == -1 )
    Error( "Unable to locate video stream in %s", filepath );
  if ( audio_stream_id == -1 )
    Debug( 3, "Unable to locate audio stream in %s", filepath );

  return 0;
} // end int FFmpeg_Input::Open( const char * filepath )

AVFrame *FFmpeg_Input::get_frame( int stream_id ) {
  Debug(1, "Getting frame from stream %d", stream_id);

  int frameComplete = false;
  AVPacket packet;
  av_init_packet(&packet);
  char errbuf[AV_ERROR_MAX_STRING_SIZE];

  while ( !frameComplete ) {
    int ret = av_read_frame(input_format_context, &packet);
    if ( ret < 0 ) {
      av_strerror(ret, errbuf, AV_ERROR_MAX_STRING_SIZE);
      if (
          // Check if EOF.
          (ret == AVERROR_EOF || (input_format_context->pb && input_format_context->pb->eof_reached)) ||
          // Check for Connection failure.
          (ret == -110)
         ) {
        Info( "av_read_frame returned %s.", errbuf );
        return NULL;
      }
      Error( "Unable to read packet from stream %d: error %d \"%s\".", packet.stream_index, ret, errbuf );
      return NULL;
    }
    dumpPacket(input_format_context->streams[packet.stream_index], &packet, "Received packet");

    if ( (stream_id < 0) || (packet.stream_index == stream_id) ) {
      Debug(3,"Packet is for our stream (%d)", packet.stream_index );

      AVCodecContext *context = streams[packet.stream_index].context;

#if LIBAVCODEC_VERSION_CHECK(57, 64, 0, 64, 0)
      ret = avcodec_send_packet(context, &packet);
      if ( ret < 0 ) {
        av_strerror(ret, errbuf, AV_ERROR_MAX_STRING_SIZE);
        Error("Unable to send packet at frame %d: %s, continuing",
            streams[packet.stream_index].frame_count, errbuf);
        zm_av_packet_unref(&packet);
        continue;
      }

#if HAVE_AVUTIL_HWCONTEXT_H
    if ( hwaccel ) {
      ret = avcodec_receive_frame( context, hwFrame );
      if ( ret < 0 ) {
        av_strerror( ret, errbuf, AV_ERROR_MAX_STRING_SIZE );
        Error( "Unable to receive frame %d: %s, continuing", streams[packet.stream_index].frame_count, errbuf );
        zm_av_packet_unref( &packet );
        continue;
      }
      ret = av_hwframe_transfer_data(frame, hwFrame, 0);
      if (ret < 0) {
        av_strerror( ret, errbuf, AV_ERROR_MAX_STRING_SIZE );
        Error( "Unable to transfer frame at frame %d: %s, continuing", streams[packet.stream_index].frame_count, errbuf );
        zm_av_packet_unref(&packet);
        continue;
      }
    } else {
#endif
      if ( frame ) {
        av_frame_free(&frame);
        frame = zm_av_frame_alloc();
      } else {
        frame = zm_av_frame_alloc();
      }
      //Debug(1,"Getting frame %d", streams[packet.stream_index].frame_count);
      ret = avcodec_receive_frame(context, frame);
      if ( ret < 0 ) {
        av_strerror( ret, errbuf, AV_ERROR_MAX_STRING_SIZE );
        Error( "Unable to send packet at frame %d: %s, continuing", streams[packet.stream_index].frame_count, errbuf );
        zm_av_packet_unref( &packet );
        av_frame_free(&frame);
        continue;
      }

#if HAVE_AVUTIL_HWCONTEXT_H
    }
#endif

    frameComplete = 1;
# else
    if ( frame ) {
      av_frame_free(&frame);
      frame = zm_av_frame_alloc();
    } else {
      frame = zm_av_frame_alloc();
    }
    ret = zm_avcodec_decode_video(context, frame, &frameComplete, &packet);
    if ( ret < 0 ) {
      av_strerror(ret, errbuf, AV_ERROR_MAX_STRING_SIZE);
      Error( "Unable to decode frame at frame %d: %s, continuing", streams[packet.stream_index].frame_count, errbuf );
      zm_av_packet_unref( &packet );
        av_frame_free(&frame);
      continue;
    }
#endif
  } // end if it's the right stream

    zm_av_packet_unref(&packet);

  } // end while ! frameComplete
  return frame;

} //  end AVFrame *FFmpeg_Input::get_frame

AVFrame *FFmpeg_Input::get_frame( int stream_id, double at ) {
  Debug(1, "Getting frame from stream %d at %f", stream_id, at);

  int64_t seek_target = (int64_t)(at * AV_TIME_BASE);
  Debug(1, "Getting frame from stream %d at seektarget: %" PRId64, stream_id, seek_target);
  seek_target = av_rescale_q(seek_target, AV_TIME_BASE_Q, input_format_context->streams[stream_id]->time_base);
  Debug(1, "Getting frame from stream %d at %" PRId64, stream_id, seek_target);

  if ( frame ) {
    if ( (frame->pts + frame->pkt_duration) > seek_target ) {
      // The current frame is still the valid picture.
      Debug(2,"Returning previous frame which is still good");
      return frame;
    }
    if ( frame->pts < seek_target  ) {
      Debug(2, "Frame pts %" PRId64 " duration %" PRId64, frame->pts, frame->pkt_duration);
      while ( frame && (frame->pts < seek_target) ) {
        if ( ! get_frame(stream_id) ) 
          return frame;
      }
      return frame;
    }
  }

  int ret;
  if ( frame ) {
    if ( ( ret = av_seek_frame(input_format_context, stream_id, seek_target, AVSEEK_FLAG_ANY) < 0 ) ) {
      Error("Unable to seek in stream");
      return NULL;
    }

  } else {
    // No previous frame... are we asking for first frame?
    // Must go for a keyframe
    if ( ( ret = av_seek_frame(input_format_context, stream_id, seek_target, 
            AVSEEK_FLAG_BACKWARD | AVSEEK_FLAG_FRAME
            ) < 0 ) ) {
      Error("Unable to seek in stream");
      return NULL;
    }
  }
  return get_frame(stream_id);

} // end AVFrame *FFmpeg_Input::get_frame( int stream_id, struct timeval at)
