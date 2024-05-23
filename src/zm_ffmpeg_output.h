#ifndef ZM_FFMPEG_INPUT_H
#define ZM_FFMPEG_INPUT_H

extern "C" {
#include <libavformat/avformat.h>
#include <libavformat/avio.h>
#include <libavcodec/avcodec.h>
}

class FFmpeg_Output {

 public:
  FFmpeg_Output();
  ~FFmpeg_Output();

  int Open( const char *filename );
  int Close();
  AVFrame *put_frame( int stream_id=-1 );
  AVFrame *put_packet( int stream_id=-1 );
  int get_video_stream_id() {
    return video_stream_id;
  }
  int get_audio_stream_id() {
    return audio_stream_id;
  }

 private:
  typedef struct {
    AVCodecContext *context;
    AVCodec *codec;
    int frame_count;
  } stream;

  stream streams[2];
  int video_stream_id;
  int audio_stream_id;
  AVFormatContext *input_format_context;
  av_frame_ptr frame;
};

#endif
