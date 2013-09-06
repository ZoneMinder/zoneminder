#ifndef ZM_VIDEOSTORE_H
#define ZM_VIDEOSTORE_H

#include "zm_ffmpeg.h"

#if HAVE_LIBAVCODEC

class VideoStore {
private:
	AVOutputFormat *fmt;
	AVFormatContext *oc;
	AVStream *video_st;
    
	const char *filename;
	const char *format;
    
    bool keyframeMessage;
    int keyframeSkipNumber;
    
    
public:
	VideoStore(const char *filename_in, const char *format_in, AVStream *input_st);
	~VideoStore();

    void writeVideoFramePacket(AVPacket *pkt, AVStream *input_st, AVFormatContext *input_fmt_ctx);
};

#endif //havelibav
#endif //zm_videostore_h

