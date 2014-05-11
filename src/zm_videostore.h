#ifndef ZM_VIDEOSTORE_H
#define ZM_VIDEOSTORE_H

#include "zm_ffmpeg.h"

#if HAVE_LIBAVCODEC

class VideoStore {
private:
	AVOutputFormat *fmt;
	AVFormatContext *oc;
	AVStream *video_st;
	AVStream *audio_st;
    
	const char *filename;
	const char *format;
    
    bool keyframeMessage;
    int keyframeSkipNumber;
    
    int64_t startTime;
    int64_t startPts;
    int64_t startDts;
    int64_t filter_in_rescale_delta_last;
    
public:
	VideoStore(const char *filename_in, const char *format_in, AVStream *input_st, AVStream *inpaud_st, int64_t nStartTime);
	~VideoStore();

    int writeVideoFramePacket(AVPacket *pkt, AVStream *input_st);//, AVPacket *lastKeyframePkt);
    int writeAudioFramePacket(AVPacket *pkt, AVStream *input_st);
};

/*
class VideoEvent {
public:
	VideoEvent(unsigned int	eid);
	~VideoEvent();

    int createEventImage(unsigned int fid, char *&pBuff);
    
private:
    unsigned int	m_eid;
};*/

#endif //havelibav
#endif //zm_videostore_h

