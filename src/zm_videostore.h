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

class VideoStore;
#include "zm_monitor.h"
#include "zm_packet.h"
#include "zm_packetqueue.h"

class VideoStore {
private:
  unsigned int packets_written;
  unsigned int frame_count;

	AVOutputFormat *out_format;
	AVFormatContext *oc;
	AVStream *video_out_stream;
	AVStream *audio_out_stream;
int video_in_stream_index;
int audio_in_stream_index;

  AVCodec *video_out_codec;
  AVCodecContext *video_out_ctx;

	AVStream *video_in_stream;
	AVStream *audio_in_stream;

  // Move this into the object so that we aren't constantly allocating/deallocating it on the stack
  AVPacket opkt;
  // we are transcoding
  AVFrame *video_in_frame;
  AVFrame *in_frame;
  AVFrame *out_frame;

  AVCodecContext *video_in_ctx;
  AVCodecContext *audio_in_ctx;
  int ret;

  SWScale swscale;

  // The following are used when encoding the audio stream to AAC
  AVCodec *audio_out_codec;
  AVCodecContext *audio_out_ctx;
  AVAudioFifo *fifo;
  int out_frame_size;
#ifdef HAVE_LIBAVRESAMPLE
AVAudioResampleContext* resample_ctx;
#endif
  uint8_t *converted_in_samples;
    
	const char *filename;
	const char *format;
    
  bool keyframeMessage;
  int keyframeSkipNumber;
    
  // These are for in
  int64_t video_last_pts;
  int64_t video_last_dts;
  int64_t video_last_duration;
  int64_t audio_last_pts;
  int64_t audio_last_dts;

  // These are for out, should start at zero.  We assume they do not wrap because we just aren't going to save files that big.
  int64_t video_next_pts;
  int64_t video_next_dts;
;
  int64_t audio_next_pts;
  int64_t audio_next_dts;

  int64_t filter_in_rescale_delta_last;

  bool setup_resampler();

public:
	VideoStore(
      const char *filename_in,
      const char *format_in,
      AVStream *video_in_stream,
      AVStream *audio_in_stream,
      int64_t starttime,
      Monitor * p_monitor
      );
	~VideoStore();
  bool  open();

  void write_video_packet( AVPacket &pkt );
  void write_audio_packet( AVPacket &pkt );
  int writeVideoFramePacket( ZMPacket *pkt );
  int writeAudioFramePacket( ZMPacket *pkt );
  int writePacket( ZMPacket *pkt );
	void dumpPacket( AVPacket *pkt );
  int write_packets( zm_packetqueue &queue );
};

#endif //havelibav
#endif //zm_videostore_h

