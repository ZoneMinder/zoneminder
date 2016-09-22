#ifndef ZM_VIDEOSTORE_H
#define ZM_VIDEOSTORE_H

#include "zm_ffmpeg.h"

#if HAVE_LIBAVCODEC

#include "zm_monitor.h"

class VideoStore {
private:

	AVOutputFormat *output_format;
	AVFormatContext *oc;
	AVStream *video_stream;
	AVStream *audio_stream;

	AVStream *input_video_stream;
	AVStream *input_audio_stream;

  // The following are used when encoding the audio stream to AAC
  AVCodec *audio_output_codec;
  AVCodecContext *audio_output_context;
  int data_present;
    
	const char *filename;
	const char *format;
    
  bool keyframeMessage;
  int keyframeSkipNumber;
    
  int64_t video_start_pts;
  int64_t video_start_dts;
  int64_t audio_start_pts;
  int64_t audio_start_dts;

	int64_t prevDts;
  int64_t filter_in_rescale_delta_last;

public:
	VideoStore(const char *filename_in, const char *format_in, AVStream *input_video_stream, AVStream *input_audio_stream, int64_t nStartTime, Monitor::Orientation p_orientation );
	~VideoStore();

  int writeVideoFramePacket( AVPacket *pkt );
  int writeAudioFramePacket( AVPacket *pkt );
	void dumpPacket( AVPacket *pkt );
};

#endif //havelibav
#endif //zm_videostore_h

