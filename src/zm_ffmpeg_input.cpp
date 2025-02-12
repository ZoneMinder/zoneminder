#include "zm_ffmpeg_input.h"

#include "zm_ffmpeg.h"
#include "zm_logger.h"

FFmpeg_Input::FFmpeg_Input() {
  input_format_context = nullptr;
  video_stream_id = -1;
  audio_stream_id = -1;
  FFMPEGInit();
  streams = nullptr;
  last_seek_request = -1;
}

FFmpeg_Input::~FFmpeg_Input() {
  if (input_format_context) {
    Close();
  }
}  // end ~FFmpeg_Input()

/* Takes streams provided from elsewhere.  They might not come from the same source
 * but we will treat them as if they are.  */
int FFmpeg_Input::Open(
  const AVStream * video_in_stream,
  const AVCodecContext * video_in_ctx,
  const AVStream * audio_in_stream,
  const AVCodecContext * audio_in_ctx
) {
  int max_stream_index = video_stream_id = video_in_stream->index;

  if (audio_in_stream) {
    max_stream_index = video_in_stream->index > audio_in_stream->index ? video_in_stream->index : audio_in_stream->index;
    audio_stream_id = audio_in_stream->index;
  }
  streams = new stream[max_stream_index+1];
  return 1;
}

int FFmpeg_Input::Open(const char *filepath) {
  int error;

  /** Open the input file to read from it. */
  error = avformat_open_input(&input_format_context, filepath, nullptr, nullptr);
  if ( error < 0 ) {
    Error("Could not open input file '%s' (error '%s')",
          filepath, av_make_error_string(error).c_str());
    input_format_context = nullptr;
    return error;
  }

  /** Get information on the input file (number of streams etc.). */
  if ( (error = avformat_find_stream_info(input_format_context, nullptr)) < 0 ) {
    Error(
      "Could not open find stream info (error '%s')",
      av_make_error_string(error).c_str()
    );
    avformat_close_input(&input_format_context);
    return error;
  }

  streams = new stream[input_format_context->nb_streams];
  Debug(2, "Have %d streams", input_format_context->nb_streams);

  for (unsigned int i = 0; i < input_format_context->nb_streams; i += 1) {
    if (is_video_stream(input_format_context->streams[i])) {
      zm_dump_stream_format(input_format_context, i, 0, 0);
      if (video_stream_id == -1) {
        video_stream_id = i;
        // if we break, then we won't find the audio stream
      } else {
        Warning("Have another video stream.");
      }
    } else if (is_audio_stream(input_format_context->streams[i])) {
      if (audio_stream_id == -1) {
        Debug(2, "Audio stream is %d", i);
        audio_stream_id = i;
      } else {
        Warning("Have another audio stream.");
      }
    } else {
      Warning("Unknown stream type");
    }

    streams[i].frame_count = 0;

    if (!(streams[i].codec = avcodec_find_decoder(input_format_context->streams[i]->codecpar->codec_id))) {
      Error("Could not find input codec");
      avformat_close_input(&input_format_context);
      return AVERROR_EXIT;
    } else {
      Debug(1, "Using codec (%s) for stream %d", streams[i].codec->name, i);
    }

    streams[i].context = avcodec_alloc_context3(streams[i].codec);
    avcodec_parameters_to_context(streams[i].context, input_format_context->streams[i]->codecpar);
    zm_dump_codec(streams[i].context);

    error = avcodec_open2(streams[i].context, streams[i].codec, nullptr);
    if (error < 0) {
      Error("Could not open input codec (error '%s')",
            av_make_error_string(error).c_str());
      avcodec_free_context(&streams[i].context);
      avformat_close_input(&input_format_context);
      input_format_context = nullptr;
      return error;
    }
    zm_dump_codec(streams[i].context);
    if (!(streams[i].context->time_base.num && streams[i].context->time_base.den)) {
      Debug(1, "Setting to default time base");
      streams[i].context->time_base.num = 1;
      streams[i].context->time_base.den = 90000;
    }
  } // end foreach stream

  if (video_stream_id == -1)
    Debug(1, "Unable to locate video stream in %s", filepath);
  if (audio_stream_id == -1)
    Debug(3, "Unable to locate audio stream in %s", filepath);

  return 1;
} // end int FFmpeg_Input::Open( const char * filepath )

int FFmpeg_Input::Close( ) {
  if (streams) {
    for (unsigned int i = 0; i < input_format_context->nb_streams; i += 1) {
      //avcodec_close(streams[i].context);
      avcodec_free_context(&streams[i].context);
      streams[i].context = nullptr;
    }
    delete[] streams;
    streams = nullptr;
  }

  if (input_format_context) {
    avformat_close_input(&input_format_context);
    input_format_context = nullptr;
  }
  return 1;
} // end int FFmpeg_Input::Close()

AVFrame *FFmpeg_Input::get_frame(int stream_id) {
  bool frameComplete = false;
  av_packet_ptr packet{av_packet_alloc()};

  if (!packet) {
    Error("Unable to allocate packet.");
    return nullptr;
  }

  while (!frameComplete) {
    int ret = av_read_frame(input_format_context, packet.get());
    if (ret < 0) {
      if (
        // Check if EOF.
        (ret == AVERROR_EOF || (input_format_context->pb && input_format_context->pb->eof_reached)) ||
        // Check for Connection failure.
        (ret == -110)
      ) {
        Info("av_read_frame returned %s.", av_make_error_string(ret).c_str());
        return nullptr;
      }
      Error("Unable to read packet from stream %d: error %d \"%s\".",
            packet->stream_index, ret, av_make_error_string(ret).c_str());
      return nullptr;
    }
    ZM_DUMP_STREAM_PACKET(input_format_context->streams[packet->stream_index], packet, "Received packet");

    av_packet_guard pkt_guard{packet};

    if ((stream_id >= 0) && (packet->stream_index != stream_id)) {
      Debug(4, "Packet is not for our stream (%d)", packet->stream_index );
      continue;
    }

    AVCodecContext *context = streams[packet->stream_index].context;

    frame = av_frame_ptr{zm_av_frame_alloc()};
    if (!frame) {
      Error("Unable to allocate frame.");
      return nullptr;
    }
    ret = zm_send_packet_receive_frame(context, frame.get(), *packet);
    if ( ret < 0 ) {
      Error("Unable to decode frame at frame %d: %d %s, continuing",
            streams[packet->stream_index].frame_count, ret, av_make_error_string(ret).c_str());
      frame = nullptr;
      continue;
    } else {
      if (is_video_stream(input_format_context->streams[packet->stream_index])) {
        zm_dump_video_frame(frame.get(), "resulting video frame");
      } else {
        zm_dump_frame(frame.get(), "resulting frame");
      }
    }

    frameComplete = true;

    if (is_video_stream(input_format_context->streams[packet->stream_index])) {
      zm_dump_video_frame(frame.get(), "resulting video frame");
    } else {
      zm_dump_frame(frame.get(), "resulting frame");
    }
  } // end while !frameComplete
  return frame.get();
}  // end AVFrame *FFmpeg_Input::get_frame

/* at is FPSeconds */
AVFrame *FFmpeg_Input::get_frame(int stream_id, double at) {
  Debug(1, "Getting frame from stream %d at %f", stream_id, at);

  int64_t seek_target = (int64_t)(at * AV_TIME_BASE);
  Debug(1, "Getting frame from stream %d at seektarget: %" PRId64, stream_id, seek_target);
  seek_target = av_rescale_q(seek_target, AV_TIME_BASE_Q, input_format_context->streams[stream_id]->time_base);
  Debug(1, "Getting frame from stream %d at %" PRId64, stream_id, seek_target);

  int ret;

  if (!frame) {
    // Don't have a frame yet, so get a keyframe before the timestamp
    ret = av_seek_frame(input_format_context, stream_id, seek_target, AVSEEK_FLAG_FRAME);
    if (ret < 0) {
      Error("Unable to seek in stream");
      return nullptr;
    }
    // Have to grab a frame to update our current frame to know where we are
    get_frame(stream_id);

    if (!frame) {
      Warning("Unable to get frame.");
      return nullptr;
    }
  }  // end if ! frame

  if (
    (last_seek_request >= 0)
    &&
    (last_seek_request > seek_target)
    &&
    (frame->pts > seek_target)
  ) {
    zm_dump_frame(frame, "frame->pts > seek_target, seek backwards");
    // our frame must be beyond our seek target. so go backwards to before it
    if (( ret = av_seek_frame(input_format_context, stream_id, seek_target,
                              AVSEEK_FLAG_BACKWARD | AVSEEK_FLAG_FRAME
                             ) ) < 0) {
      Error("Unable to seek in stream %d", ret);
      return nullptr;
    }
    // Have to grab a frame to update our current frame to know where we are
    get_frame(stream_id);
    if ( is_video_stream(input_format_context->streams[stream_id]) ) {
      zm_dump_video_frame(frame, "frame->pts > seek_target, got");
    } else {
      zm_dump_frame(frame, "frame->pts > seek_target, got");
    }
  } else if ( last_seek_request == seek_target ) {
    // paused case, sending keepalives
    return frame.get();
  } // end if frame->pts > seek_target

  last_seek_request = seek_target;

  // Normally it is likely just the next packet. Need a heuristic for seeking, something like duration * keyframe interval
#if LIBAVCODEC_VERSION_CHECK(60, 3, 0, 3, 0)
  if (frame->pts + 10*frame->duration < seek_target)
#else
  if (frame->pts + 10*frame->pkt_duration < seek_target)
#endif
  {
    Debug(1, "Jumping ahead");
    if (( ret = av_seek_frame(input_format_context, stream_id, seek_target,
                              AVSEEK_FLAG_FRAME
                             ) ) < 0) {
      Error("Unable to seek in stream %d", ret);
      return nullptr;
    }
    // Have to grab a frame to update our current frame to know where we are
    get_frame(stream_id);
    zm_dump_frame(frame, "got");
    if (frame->pts > seek_target) {
      if (( ret = av_seek_frame(input_format_context, stream_id, seek_target,
                                AVSEEK_FLAG_FRAME|AVSEEK_FLAG_BACKWARD
                               ) ) < 0) {
        Error("Unable to seek in stream %d", ret);
        return nullptr;
      }
      get_frame(stream_id);
      zm_dump_frame(frame, "Had to seek backwards");
    }
  }
  // Seeking seems to typically seek to a keyframe, so then we have to decode until we get the frame we want.
  if (frame->pts <= seek_target) {
    Debug(1, "Frame pts %" PRId64 " + duration %" PRId64 "= %" PRId64 " <=? %" PRId64,
          frame->pts,
#if LIBAVCODEC_VERSION_CHECK(60, 3, 0, 3, 0)
          frame->duration, frame->pts + frame->duration,
#else
          frame->pkt_duration, frame->pts + frame->pkt_duration,
#endif
          seek_target);
    while (frame && (frame->pts +
#if LIBAVCODEC_VERSION_CHECK(60, 3, 0, 3, 0)
                     frame->duration
#else
                     frame->pkt_duration
#endif
                     < seek_target)) {
      if (is_video_stream(input_format_context->streams[stream_id])) {
        zm_dump_video_frame(frame, "pts <= seek_target");
      } else {
        zm_dump_frame(frame, "pts <= seek_target");
      }
      if (!get_frame(stream_id)) {
        Warning("Got no frame. returning nothing");
        return frame.get();
      }
    }
    return frame.get();
  }

  return get_frame(stream_id);
}
