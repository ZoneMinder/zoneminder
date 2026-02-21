#include "zm_ffmpeg_input.h"

#include "zm_signal.h"

FFmpeg_Input::FFmpeg_Input() :
  streams(nullptr),
  video_stream_id(-1),
  audio_stream_id(-1),
  input_format_context(nullptr),
  last_seek_request(-1),
  hw_device_ctx(nullptr)
{
  FFMPEGInit();
}

FFmpeg_Input::~FFmpeg_Input() {
  Close();
}  // end ~FFmpeg_Input()

/* Takes streams provided from elsewhere.  They might not come from the same source
 * but we will treat them as if they are.  */
int FFmpeg_Input::Open(
  const AVStream * video_in_stream,
  const AVCodecContext * video_in_ctx,
  const AVStream * audio_in_stream,
  const AVCodecContext * audio_in_ctx
) {
  // Clean up any previous state
  if (streams) {
    delete[] streams;
    streams = nullptr;
  }

  int max_stream_index = video_stream_id = video_in_stream->index;

  if (audio_in_stream) {
    max_stream_index = video_in_stream->index > audio_in_stream->index ? video_in_stream->index : audio_in_stream->index;
    audio_stream_id = audio_in_stream->index;
  }
  streams = new stream[max_stream_index+1];
  // Initialize all stream structs to safe values
  for (int i = 0; i <= max_stream_index; i++) {
    streams[i].context = nullptr;
    streams[i].codec = nullptr;
    streams[i].frame_count = 0;
  }
  return 1;
}

int FFmpeg_Input::Open(const char *filepath) {
  int error;

  // Clean up any previous state
  Close();

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
    //av_seek_frame(input_format_context, i, 0, AVSEEK_FLAG_FRAME);

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
        continue;
      }
    } else {
      Debug(1, "Unknown stream type");
      continue;
    }

    streams[i].frame_count = 0;

    streams[i].context = nullptr;
    std::list<const CodecData *>codec_data = get_decoder_data(input_format_context->streams[i]->codecpar->codec_id, "auto");
    for (auto it = codec_data.begin(); it != codec_data.end(); it ++) {
      const CodecData *chosen_codec_data = *it;
      Debug(1, "Found codec %s", chosen_codec_data->codec_name);

      streams[i].codec = avcodec_find_decoder_by_name(chosen_codec_data->codec_name);

      streams[i].context = avcodec_alloc_context3(streams[i].codec);
      avcodec_parameters_to_context(streams[i].context, input_format_context->streams[i]->codecpar);

      if (setup_hwaccel(streams[i].context,
            chosen_codec_data, hw_device_ctx, "",
            input_format_context->streams[i]->codecpar->width,
            input_format_context->streams[i]->codecpar->height
            )) {
        Warning("Failed to setup hw_accel");
        continue;
      }

      zm_dump_codec(streams[i].context);

      error = avcodec_open2(streams[i].context, streams[i].codec, nullptr);
      if (error < 0) {
        Error("Could not open input codec (error '%s')",
            av_make_error_string(error).c_str());

        avcodec_free_context(&(streams[i].context));
        streams[i].context = nullptr;
        continue;
      }
      break;
    } // end foreach codec_data
 
    if (!streams[i].context) {
      Debug(1, "Failed with known codecs, trying harder");
      if ((streams[i].codec = avcodec_find_decoder(input_format_context->streams[i]->codecpar->codec_id))) {
        Debug(1, "Using codec (%s) for stream %d", streams[i].codec->name, i);
        streams[i].context = avcodec_alloc_context3(streams[i].codec);
        avcodec_parameters_to_context(streams[i].context, input_format_context->streams[i]->codecpar);

        zm_dump_codec(streams[i].context);

        error = avcodec_open2(streams[i].context, streams[i].codec, nullptr);
        if (error < 0) {
          Error("Could not open input codec (error '%s')", av_make_error_string(error).c_str());
          avcodec_free_context(&streams[i].context);
          streams[i].context = nullptr;
        }
      }
    }

    if (!streams[i].context) {
      // Failed to open codec for this stream, skip it
      continue;
    }
    zm_dump_codec(streams[i].context);
    if (0 and !(streams[i].context->time_base.num && streams[i].context->time_base.den)) {
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
  if (streams && input_format_context) {
    for (unsigned int i = 0; i < input_format_context->nb_streams; i += 1) {
      avcodec_free_context(&streams[i].context);
      streams[i].context = nullptr;
    }
  }

  // Free streams array
  if (streams) {
    delete[] streams;
    streams = nullptr;
  }

  // Free format context
  if (input_format_context) {
    avformat_close_input(&input_format_context);
    input_format_context = nullptr;
  }

  // Free hardware device context
  if (hw_device_ctx) {
    av_buffer_unref(&hw_device_ctx);
    hw_device_ctx = nullptr;
  }

  // Reset stream IDs
  video_stream_id = -1;
  audio_stream_id = -1;
  last_seek_request = -1;

  return 1;
} // end int FFmpeg_Input::Close()

// WARNING: Returns a raw pointer to an internally managed frame.
// The returned pointer becomes invalid after the next call to get_frame().
// Callers must not store this pointer across multiple get_frame() calls.
AVFrame *FFmpeg_Input::get_frame(int stream_id) {
  if (!streams || !streams[stream_id].context) {
    Error("No context for stream %d", stream_id);
    return nullptr;
  }


  AVCodecContext *context = streams[stream_id].context;

  while (!zm_terminate) {
    frame = av_frame_ptr{av_frame_alloc()};
    if (!frame) {
      Error("Unable to allocate frame.");
      return nullptr;
    }

    // Since technically sending a packet can result in multiple frames (or buffered_frames) try receive_frame first.
    int ret = avcodec_receive_frame(context, frame.get());
    Debug(1, "Ret from receive_frame ret: %d %s", ret, av_make_error_string(ret).c_str());
    if (ret == AVERROR(EAGAIN)) {
      // Perfectly normal
    } else if (ret < 0) {
      Error("Ret from receive_frame ret: %d %s", ret, av_make_error_string(ret).c_str());
      return nullptr; // FIXME should likely just continue, but not if we are EOF
    } else {
      zm_dump_frame(frame.get(), "resulting frame");
      return frame.get();
    }

    av_packet_ptr packet{av_packet_alloc()};
    if (!packet) {
      Error("Unable to allocate packet.");
      return nullptr;
    }

    while (!zm_terminate) {
      ret = av_read_frame(input_format_context, packet.get());
      if (ret < 0) {
        if (
            // Check if EOF.
            (ret == AVERROR_EOF || (input_format_context->pb && input_format_context->pb->eof_reached)) ||
            // Check for Connection failure.
            (ret == -110)
           ) {
          // Need to try flushing the codec. Sigh
          Info("av_read_frame returned %s.", av_make_error_string(ret).c_str());
          break;
        }
        Error("Unable to read packet from stream %d: error %d %s.", packet->stream_index, ret, av_make_error_string(ret).c_str());
        return nullptr;
      }
      ZM_DUMP_STREAM_PACKET(input_format_context->streams[packet->stream_index], packet, "Received packet");

      //av_packet_guard pkt_guard{packet};

      if ((stream_id >= 0) && (packet->stream_index != stream_id)) {
        Debug(4, "Packet is not for our stream. Want %d got %d", stream_id, packet->stream_index);
        continue;
      }
      break;
    }  // end while !got_packet

    if (
        // Check if EOF.
        (ret == AVERROR_EOF || (input_format_context->pb && input_format_context->pb->eof_reached)) ||
        // Check for Connection failure.
        (ret == -110)
       ) {
      ret = avcodec_send_packet(context, nullptr); // flush
    } else {
      ret = avcodec_send_packet(context, packet.get());
    }
    if (ret == AVERROR(EAGAIN)) {
      Debug(2, "Unable to send packet %d %s", ret, av_make_error_string(ret).c_str());
      continue;
    }
    if (ret < 0) {
      Error("Unable to send packet %d %s", ret, av_make_error_string(ret).c_str());
      return nullptr;
    }
  } // end while !frameComplete
  return frame.get();
}  // end AVFrame *FFmpeg_Input::get_frame

/* at is FPSeconds */
// WARNING: Returns a raw pointer to an internally managed frame.
// The returned pointer becomes invalid after the next call to get_frame().
AVFrame *FFmpeg_Input::get_frame(int stream_id, double at) {
  if (!input_format_context || !streams) {
    Error("get_frame called without valid input context");
    return nullptr;
  }
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
      //return nullptr;
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

  // If more than 5 seconds behind seek target, use av_seek_frame to jump
  // ahead instead of decoding packets sequentially (which is very slow for
  // large gaps). 5 seconds is well beyond a typical GOP, making seek
  // worthwhile even after decoding forward from the nearest keyframe.
  int64_t seek_threshold = av_rescale_q(5 * AV_TIME_BASE, AV_TIME_BASE_Q,
                                        input_format_context->streams[stream_id]->time_base);
  if (frame->pts + seek_threshold < seek_target)
  {
    Debug(1, "Jumping ahead to %" PRId64, seek_target);
    if (( ret = av_seek_frame(input_format_context, stream_id, seek_target, AVSEEK_FLAG_FRAME) ) < 0) {
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
  if (frame->pts +
#if LIBAVCODEC_VERSION_CHECK(60, 3, 0, 3, 0)
                     frame->duration
#else
                     frame->pkt_duration
#endif
      <= seek_target) {
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
        return nullptr;
      }
    }
  }
  return frame.get();
}
