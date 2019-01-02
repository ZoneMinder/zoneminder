#ifndef ZM_VIDEOSTORE_H
#define ZM_VIDEOSTORE_H

#include "zm_ffmpeg.h"
extern "C"  {
#ifdef HAVE_LIBSWRESAMPLE
  #include "libswresample/swresample.h"
#else
  #ifdef HAVE_LIBAVRESAMPLE
    #include "libavresample/avresample.h"
  #endif
#endif
}

#if HAVE_LIBAVCODEC

#include "zm_monitor.h"

class VideoStore {
private:

	AVOutputFormat *out_format;
	AVFormatContext *oc;
	AVStream *video_out_stream;
	AVStream *audio_out_stream;
  AVCodecContext *video_out_ctx;

	AVStream *video_in_stream;
	AVStream *audio_in_stream;

  // Move this into the object so that we aren't constantly allocating/deallocating it on the stack
  AVPacket opkt;
  // we are transcoding
  AVFrame *in_frame;
  AVFrame *out_frame;

  AVCodecContext *video_in_ctx;
  AVCodec *audio_in_codec;
  AVCodecContext *audio_in_ctx;
  int ret;

  // The following are used when encoding the audio stream to AAC
  AVCodec *audio_out_codec;
  AVCodecContext *audio_out_ctx;
#ifdef HAVE_LIBSWRESAMPLE
  SwrContext *resample_ctx;
#else
#ifdef HAVE_LIBAVRESAMPLE
  AVAudioResampleContext* resample_ctx;
#endif
#endif
  uint8_t *converted_in_samples;
    
	const char *filename;
	const char *format;
    
  // These are for in
  int64_t video_last_pts;
  int64_t video_last_dts;
  int64_t audio_last_pts;
  int64_t audio_last_dts;

  // These are for out, should start at zero.  We assume they do not wrap because we just aren't going to save files that big.
  int64_t video_next_pts;
  int64_t video_next_dts;
  int64_t audio_next_pts;
  int64_t audio_next_dts;

  bool setup_resampler();

public:
	VideoStore(
      const char *filename_in,
      const char *format_in,
      AVStream *video_in_stream,
      AVStream *audio_in_stream,
      int64_t nStartTime,
      Monitor * p_monitor);
  bool  open();
	~VideoStore();

  int writeVideoFramePacket( AVPacket *pkt );
  int writeAudioFramePacket( AVPacket *pkt );
};

#endif //havelibav
#endif //zm_videostore_h

