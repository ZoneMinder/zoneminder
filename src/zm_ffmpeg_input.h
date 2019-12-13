#ifndef ZM_FFMPEG_INPUT_H
#define ZM_FFMPEG_INPUT_H

#ifdef __cplusplus
extern "C" {
#endif

#include "libavformat/avformat.h"
#include "libavformat/avio.h"
#include "libavcodec/avcodec.h"

#ifdef __cplusplus
}
#endif

class FFmpeg_Input {

  public:
    FFmpeg_Input();
    ~FFmpeg_Input();

    int Open( const char *filename );
    int Close();
    AVFrame *get_frame( int stream_id=-1 );
    AVFrame *get_frame( int stream_id, double at );
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

    stream *streams;
    int video_stream_id;
    int audio_stream_id;
    AVFormatContext *input_format_context;
    AVFrame *frame;
		int64_t last_seek_request;
};

#endif
