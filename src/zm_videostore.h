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
    
    int64_t startPts;
    int64_t startDts;
    
public:
	VideoStore(const char *filename_in, const char *format_in, AVStream *input_st);
	~VideoStore();

    int writeVideoFramePacket(AVPacket *pkt, AVStream *input_st, AVPacket *lastKeyframePkt);
};

#endif //havelibav
#endif //zm_videostore_h

