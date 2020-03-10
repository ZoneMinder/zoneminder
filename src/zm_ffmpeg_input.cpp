
#include "zm_ffmpeg_input.h"
#include "zm_logger.h"
#include "zm_ffmpeg.h"

FFmpeg_Input::FFmpeg_Input() {
  input_format_context = NULL;
  video_stream_id = -1;
  audio_stream_id = -1;
  FFMPEGInit();
  streams = NULL;
  frame = NULL;
	last_seek_request = -1;
}

FFmpeg_Input::~FFmpeg_Input() {
  if ( streams ) {
    delete streams;
    streams = NULL;
  }
}

int FFmpeg_Input::Open(const char *filepath) {

  int error;

  /** Open the input file to read from it. */
  error = avformat_open_input(&input_format_context, filepath, NULL, NULL);
  if ( error < 0 ) {
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
  Debug(2, "Have %d streams", input_format_context->nb_streams);

  for ( unsigned int i = 0; i < input_format_context->nb_streams; i += 1 ) {
    if ( is_video_stream(input_format_context->streams[i]) ) {
      zm_dump_stream_format(input_format_context, i, 0, 0);
      if ( video_stream_id == -1 ) {
        video_stream_id = i;
        // if we break, then we won't find the audio stream
      } else {
        Warning("Have another video stream.");
      }
    } else if ( is_audio_stream(input_format_context->streams[i]) ) {
      if ( audio_stream_id == -1 ) {
        Debug(2, "Audio stream is %d", i);
        audio_stream_id = i;
      } else {
        Warning("Have another audio stream.");
      }
    } else {
      Warning("Unknown stream type");
    }

    streams[i].frame_count = 0;
#if LIBAVCODEC_VERSION_CHECK(57, 64, 0, 64, 0)
    streams[i].context = avcodec_alloc_context3(NULL);
    avcodec_parameters_to_context(streams[i].context, input_format_context->streams[i]->codecpar);
#else
    streams[i].context = input_format_context->streams[i]->codec;
#endif

    if ( !(streams[i].codec = avcodec_find_decoder(streams[i].context->codec_id)) ) {
      Error("Could not find input codec");
      avformat_close_input(&input_format_context);
      return AVERROR_EXIT;
    } else {
      Debug(1, "Using codec (%s) for stream %d", streams[i].codec->name, i);
    }

    error = avcodec_open2(streams[i].context, streams[i].codec, NULL);
    if ( error < 0 ) {
      Error("Could not open input codec (error '%s')",
          av_make_error_string(error).c_str());
#if LIBAVCODEC_VERSION_CHECK(57, 64, 0, 64, 0)
      avcodec_free_context(&streams[i].context);
#endif
      avformat_close_input(&input_format_context);
      return error;
    }
  } // end foreach stream

  if ( video_stream_id == -1 )
    Error("Unable to locate video stream in %s", filepath);
  if ( audio_stream_id == -1 )
    Debug(3, "Unable to locate audio stream in %s", filepath);

  return 0;
} // end int FFmpeg_Input::Open( const char * filepath )

AVFrame *FFmpeg_Input::get_frame(int stream_id) {

  int frameComplete = false;
  AVPacket packet;
  av_init_packet(&packet);

  while ( !frameComplete ) {
    int ret = av_read_frame(input_format_context, &packet);
    if ( ret < 0 ) {
      if (
          // Check if EOF.
          (ret == AVERROR_EOF || (input_format_context->pb && input_format_context->pb->eof_reached)) ||
          // Check for Connection failure.
          (ret == -110)
         ) {
        Info("av_read_frame returned %s.", av_make_error_string(ret).c_str());
        return NULL;
      }
      Error("Unable to read packet from stream %d: error %d \"%s\".",
          packet.stream_index, ret, av_make_error_string(ret).c_str());
      return NULL;
    }
    dumpPacket(input_format_context->streams[packet.stream_index], &packet, "Received packet");

    if ( (stream_id < 0) || (packet.stream_index == stream_id) ) {
      Debug(3, "Packet is for our stream (%d)", packet.stream_index);

      AVCodecContext *context = streams[packet.stream_index].context;

      if ( frame ) {
        av_frame_free(&frame);
        frame = zm_av_frame_alloc();
      } else {
        frame = zm_av_frame_alloc();
      }
      ret = zm_send_packet_receive_frame(context, frame, packet);
      if ( ret < 0 ) {
        Error("Unable to decode frame at frame %d: %s, continuing",
            streams[packet.stream_index].frame_count, av_make_error_string(ret).c_str());
        zm_av_packet_unref(&packet);
        av_frame_free(&frame);
        continue;
			} else {
        if ( is_video_stream(input_format_context->streams[packet.stream_index]) ) {
          zm_dump_video_frame(frame, "resulting video frame");
        } else {
          zm_dump_frame(frame, "resulting frame");
        }
      }

      frameComplete = 1;
    } // end if it's the right stream

    zm_av_packet_unref(&packet);

  } // end while ! frameComplete
  return frame;

} //  end AVFrame *FFmpeg_Input::get_frame

AVFrame *FFmpeg_Input::get_frame(int stream_id, double at) {
  Debug(1, "Getting frame from stream %d at %f", stream_id, at);

  int64_t seek_target = (int64_t)(at * AV_TIME_BASE);
  Debug(1, "Getting frame from stream %d at seektarget: %" PRId64, stream_id, seek_target);
  seek_target = av_rescale_q(seek_target, AV_TIME_BASE_Q, input_format_context->streams[stream_id]->time_base);
  Debug(1, "Getting frame from stream %d at %" PRId64, stream_id, seek_target);

  int ret;

  if ( !frame ) {
    // Don't have a frame yet, so get a keyframe before the timestamp
    ret = av_seek_frame(input_format_context, stream_id, seek_target, AVSEEK_FLAG_FRAME);
    if ( ret < 0 ) {
      Error("Unable to seek in stream");
      return NULL;
    }
    // Have to grab a frame to update our current frame to know where we are
    get_frame(stream_id);
  }  // end if ! frame

	if ( !frame ) {
		Warning("Unable to get frame.");
		return NULL;
	}

  if ( 
			(last_seek_request >= 0)
			&&
			(last_seek_request > seek_target ) 
			&&
			(frame->pts > seek_target)
		 ) {
    zm_dump_frame(frame, "frame->pts > seek_target, seek backwards");
  // our frame must be beyond our seek target. so go backwards to before it
    if ( ( ret = av_seek_frame(input_format_context, stream_id, seek_target, 
            AVSEEK_FLAG_BACKWARD | AVSEEK_FLAG_FRAME
            ) < 0 ) ) {
      Error("Unable to seek in stream");
      return NULL;
    }
    // Have to grab a frame to update our current frame to know where we are
    get_frame(stream_id);
    zm_dump_frame(frame, "frame->pts > seek_target, got");
  } // end if frame->pts > seek_target

	last_seek_request = seek_target;

  // Seeking seems to typically seek to a keyframe, so then we have to decode until we get the frame we want.
  if ( frame->pts <= seek_target  ) {
    zm_dump_frame(frame, "pts <= seek_target");
    while ( frame && (frame->pts < seek_target) ) {
      if ( !get_frame(stream_id) ) 
        return frame;
    }
    return frame;
  }

  return get_frame(stream_id);

} // end AVFrame *FFmpeg_Input::get_frame( int stream_id, struct timeval at)
