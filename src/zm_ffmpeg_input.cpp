
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
}

FFmpeg_Input::~FFmpeg_Input() {
  if ( input_format_context ) {
    Close();
  }
  if ( streams ) {
    delete streams;
    streams = NULL;
  }
}

int FFmpeg_Input::Open( const char *filepath ) {

  int error;

  /** Open the input file to read from it. */
  if ( (error = avformat_open_input( &input_format_context, filepath, NULL, NULL)) < 0 ) {
    Error("Could not open input file '%s' (error '%s')",
        filepath, av_make_error_string(error).c_str());
    input_format_context = NULL;
    return error;
  } 

  /** Get information on the input file (number of streams etc.). */
  if ( (error = avformat_find_stream_info(input_format_context, NULL)) < 0 ) {
    Error("Could not open find stream info (error '%s')",
        av_make_error_string(error).c_str() );
    avformat_close_input(&input_format_context);
    return error;
  }

  streams = new stream[input_format_context->nb_streams];

  for ( unsigned int i = 0; i < input_format_context->nb_streams; i += 1 ) {
    if ( is_video_stream( input_format_context->streams[i] ) ) {
      zm_dump_stream_format(input_format_context, i, 0, 0);
      if ( video_stream_id == -1 ) {
        video_stream_id = i;
        // if we break, then we won't find the audio stream
      } else {
        Warning("Have another video stream.");
      }
    } else if ( is_audio_stream(input_format_context->streams[i]) ) {
      if ( audio_stream_id == -1 ) {
        audio_stream_id = i;
      } else {
        Warning("Have another audio stream.");
      }
    }

    streams[i].frame_count = 0;
#if LIBAVCODEC_VERSION_CHECK(57, 64, 0, 64, 0)
    streams[i].context = avcodec_alloc_context3(NULL);
    avcodec_parameters_to_context(streams[i].context, input_format_context->streams[i]->codecpar);
#else
    streams[i].context = input_format_context->streams[i]->codec;
#endif

    if ( !(streams[i].codec = avcodec_find_decoder(streams[i].context->codec_id)) ) {
      Error("Could not find input codec\n");
      avformat_close_input(&input_format_context);
      return AVERROR_EXIT;
    } else {
      Debug(1, "Using codec (%s) for stream %d", streams[i].codec->name, i);
    }

    if ((error = avcodec_open2( streams[i].context, streams[i].codec, NULL)) < 0) {
      Error("Could not open input codec (error '%s')\n",
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

int FFmpeg_Input::Close( ) {
  for ( unsigned int i = 0; i < input_format_context->nb_streams; i += 1 ) {
    if ( streams[i].context ) {
      avcodec_close(streams[i].context);
#if LIBAVCODEC_VERSION_CHECK(57, 64, 0, 64, 0)
      avcodec_free_context(&streams[i].context);
#endif
      streams[i].context = NULL;
    }
  }

  if ( input_format_context ) {
#if !LIBAVFORMAT_VERSION_CHECK(53, 17, 0, 25, 0)
    av_close_input_file(input_format_context);
#else
    avformat_close_input(&input_format_context);
#endif
    input_format_context = NULL;
  }
  return 1;
} // end int FFmpeg_Input::Close()

AVFrame *FFmpeg_Input::get_frame(int stream_id, int frame_number) {
  Debug(1, "Getting frame from stream %d, frame_number(%d)", stream_id, frame_number);

  AVPacket packet;
  av_init_packet(&packet);
  AVFrame *frame = zm_av_frame_alloc();

  while ( frame_number >= streams[stream_id].frame_count ) {

    int ret = av_read_frame(input_format_context, &packet);
    if ( ret < 0 ) {
      if (
          // Check if EOF.
          (ret == AVERROR_EOF || (input_format_context->pb && input_format_context->pb->eof_reached)) ||
          // Check for Connection failure.
          (ret == -110)
         ) {
        Info("av_read_frame returned %s.", av_make_error_string(ret).c_str());
      } else {
        Error("Unable to read packet from stream %d: error %d \"%s\".", packet.stream_index, ret,
            av_make_error_string(ret).c_str());
      }
      return NULL;
    }

    if ( ( stream_id < 0 ) || ( packet.stream_index != stream_id ) ) {
      Warning("Packet is not for our stream (%d)", packet.stream_index);
      return NULL;
    }

    if ( ! zm_receive_frame(streams[packet.stream_index].context, frame, packet) ) {
      Error("Unable to get frame %d, continuing", streams[packet.stream_index].frame_count);
      zm_av_packet_unref( &packet );
      continue;
    } else {
      Debug(1, "Success getting a packet at frame (%d)", streams[packet.stream_index].frame_count);
      streams[packet.stream_index].frame_count += 1;
    }

    zm_av_packet_unref(&packet);

    if ( frame_number == -1 )
      break;
  } // end while frame_number > streams.frame_count
  return frame;
} // end AVFrame *FFmpeg_Input::get_frame
