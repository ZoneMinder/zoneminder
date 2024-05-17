#ifndef ZM_FFMPEG_INPUT_H
#define ZM_FFMPEG_INPUT_H

#include "zm_define.h"
#include "zm_ffmpeg.h"

extern "C" {
#include <libavformat/avformat.h>
#include <libavformat/avio.h>
#include <libavcodec/avcodec.h>
}

class FFmpeg_Input {

 public:
  FFmpeg_Input();
  ~FFmpeg_Input();

  int Open(const char *filename );
  int Open(
    const AVStream *,
    const AVCodecContext *,
    const AVStream *,
    const AVCodecContext *);
  int Close();
  AVFrame *get_frame(int stream_id=-1);
  AVFrame *get_frame(int stream_id, double at);
  int get_video_stream_id() const {
    return video_stream_id;
  }
  int get_audio_stream_id() const {
    return audio_stream_id;
  }
  AVStream *get_video_stream() {
    return ( video_stream_id >= 0 ) ? input_format_context->streams[video_stream_id] : nullptr;
  }
  AVStream *get_audio_stream() {
    return ( audio_stream_id >= 0 ) ? input_format_context->streams[audio_stream_id] : nullptr;
  }
  AVFormatContext *get_format_context() { return input_format_context; };
  AVCodecContext *get_video_codec_context() { return ( video_stream_id >= 0 ) ? streams[video_stream_id].context : nullptr; };
  AVCodecContext *get_audio_codec_context() { return ( audio_stream_id >= 0 ) ? streams[audio_stream_id].context : nullptr; };

 private:
  typedef struct {
    AVCodecContext *context;
    const AVCodec *codec;
    int frame_count;
  } stream;

  stream *streams;
  int video_stream_id;
  int audio_stream_id;
  AVFormatContext *input_format_context;
  av_frame_ptr frame;
  int64_t last_seek_request;
};

#endif
