#ifndef ZM_VIDEOSTORE_H
#define ZM_VIDEOSTORE_H

#include "zm_ffmpeg.h"
extern "C"  {
#include "libavutil/audio_fifo.h"

#ifdef HAVE_LIBAVRESAMPLE
#include "libavresample/avresample.h"
#endif
}

#if HAVE_LIBAVCODEC

#include "zm_monitor.h"

class VideoStore {
private:
  unsigned int packets_written;

	AVOutputFormat *output_format;
	AVFormatContext *oc;
	AVStream *video_output_stream;
	AVStream *audio_output_stream;
  AVCodecContext *video_output_context;

	AVStream *video_input_stream;
	AVStream *audio_input_stream;

  // Move this into the object so that we aren't constantly allocating/deallocating it on the stack
  AVPacket opkt;
  // we are transcoding
  AVFrame *input_frame;
  AVFrame *output_frame;

  AVCodecContext *video_input_context;
  AVCodecContext *audio_input_context;
  int ret;

  // The following are used when encoding the audio stream to AAC
  AVCodec *audio_output_codec;
  AVCodecContext *audio_output_context;
  int data_present;
  AVAudioFifo *fifo;
  int output_frame_size;
#ifdef HAVE_LIBAVRESAMPLE
AVAudioResampleContext* resample_context;
#endif
  uint8_t *converted_input_samples;
    
	const char *filename;
	const char *format;
    
  bool keyframeMessage;
  int keyframeSkipNumber;
    
  // These are for input
  int64_t video_last_pts;
  int64_t video_last_dts;
  int64_t audio_last_pts;
  int64_t audio_last_dts;

  // These are for output, should start at zero.  We assume they do not wrap because we just aren't going to save files that big.
  int64_t video_previous_pts;
  int64_t video_previous_dts;
  int64_t audio_previous_pts;
  int64_t audio_previous_dts;

  int64_t filter_in_rescale_delta_last;

  bool setup_resampler();

public:
	VideoStore(const char *filename_in, const char *format_in, AVStream *video_input_stream, AVStream *audio_input_stream, int64_t nStartTime, Monitor * p_monitor );
	~VideoStore();

  int writeVideoFramePacket( AVPacket *pkt );
  int writeAudioFramePacket( AVPacket *pkt );
	void dumpPacket( AVPacket *pkt );
};

#endif //havelibav
#endif //zm_videostore_h

