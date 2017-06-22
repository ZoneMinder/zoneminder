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

  private:
    typedef struct {
        AVCodecContext *context;
        AVCodec *codec;
    } stream;

    stream streams[2];
    int video_stream_id;
    int audio_stream_id;
    AVFormatContext *input_format_context;
}

#endif
