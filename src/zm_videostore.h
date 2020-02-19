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
#include "libavutil/audio_fifo.h"
}

#if HAVE_LIBAVCODEC

#include "zm_monitor.h"

class VideoStore {
private:

  AVOutputFormat *out_format;
  AVFormatContext *oc;

  AVCodec *video_out_codec;
  AVCodecContext *video_out_ctx;
  AVStream *video_out_stream;

  AVStream *video_in_stream;

  AVStream *audio_in_stream;

  // Move this into the object so that we aren't constantly allocating/deallocating it on the stack
  AVPacket opkt;
  // we are transcoding
  AVFrame *in_frame;
  AVFrame *out_frame;

  AVCodecContext *video_in_ctx;
  const AVCodec *audio_in_codec;
  AVCodecContext *audio_in_ctx;

  // The following are used when encoding the audio stream to AAC
  AVStream *audio_out_stream;
  AVCodec *audio_out_codec;
  AVCodecContext *audio_out_ctx;
#ifdef HAVE_LIBSWRESAMPLE
  SwrContext *resample_ctx;
#else
#ifdef HAVE_LIBAVRESAMPLE
  AVAudioResampleContext* resample_ctx;
#endif
#endif
  AVAudioFifo *fifo;
  uint8_t *converted_in_samples;
    
	const char *filename;
	const char *format;
    
  // These are for in
  int64_t video_last_pts;
  int64_t video_last_dts;
  int64_t audio_last_pts;
  int64_t audio_last_dts;

  int64_t video_first_pts;
  int64_t video_first_dts;
  int64_t audio_first_pts;
  int64_t audio_first_dts;

  // These are for out, should start at zero.  We assume they do not wrap because we just aren't going to save files that big.
  int64_t *next_dts;
  int64_t audio_next_pts;

  int max_stream_index;

  bool setup_resampler();
  int write_packet(AVPacket *pkt, AVStream *stream);

public:
	VideoStore(
      const char *filename_in,
      const char *format_in,
      AVStream *video_in_stream,
      AVStream *audio_in_stream,
      Monitor * p_monitor);
  bool  open();
	~VideoStore();

  int writeVideoFramePacket( AVPacket *pkt );
  int writeAudioFramePacket( AVPacket *pkt );
};

#endif //havelibav
#endif //zm_videostore_h

