#ifndef ZM_VIDEOSTORE_H
#define ZM_VIDEOSTORE_H

#include "zm_ffmpeg.h"
#include "libavutil/audio_fifo.h"

#if HAVE_LIBAVCODEC

#include "zm_monitor.h"

class VideoStore {
private:

	AVOutputFormat *output_format;
	AVFormatContext *oc;
	AVStream *video_output_stream;
	AVStream *audio_output_stream;
  AVCodecContext *video_output_context;

	AVStream *video_input_stream;
	AVStream *audio_input_stream;

  AVCodecContext *video_input_context;
  AVCodecContext *audio_input_context;

  // The following are used when encoding the audio stream to AAC
  AVCodec *audio_output_codec;
  AVCodecContext *audio_output_context;
  int data_present;
  AVAudioFifo *fifo;
  int output_frame_size;
    
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
	VideoStore(const char *filename_in, const char *format_in, AVStream *video_input_stream, AVStream *audio_input_stream, int64_t nStartTime, Monitor::Orientation p_orientation );
	~VideoStore();

  int writeVideoFramePacket( AVPacket *pkt );
  int writeAudioFramePacket( AVPacket *pkt );
	void dumpPacket( AVPacket *pkt );
};

#endif //havelibav
#endif //zm_videostore_h

