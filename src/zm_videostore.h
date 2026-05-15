#ifndef ZM_VIDEOSTORE_H
#define ZM_VIDEOSTORE_H

#include "zm_config.h"
#include "zm_define.h"
#include "zm_ffmpeg.h"
#include "zm_swscale.h"

#include <list>
#include <memory>
#include <map>
#include <string>
#include <vector>

extern "C"  {
#include <libswresample/swresample.h>
#include <libavutil/audio_fifo.h>
#if HAVE_LIBAVUTIL_HWCONTEXT_H
#include <libavutil/hwcontext.h>
#endif
#include "libavutil/buffer.h"
}

class Monitor;
class ZMPacket;
class PacketQueue;

class VideoStore {
 public:
  struct Fragment {
    int64_t offset;    // byte offset in file
    int64_t size;      // bytes (moof+mdat)
    double duration;   // seconds
  };

 private:

  const CodecData *chosen_codec_data;

  Monitor *monitor;
  AVOutputFormat *out_format;
  AVFormatContext *oc;
  AVStream *video_out_stream;
  AVStream *audio_out_stream;

  AVCodecContext *video_in_ctx;
  AVCodecContext *video_out_ctx;

  AVStream *video_in_stream;
  AVStream *audio_in_stream;

  const AVCodec *audio_in_codec;
  AVCodecContext *audio_in_ctx;
  // The following are used when encoding the audio stream to AAC
  const AVCodec *audio_out_codec;
  AVCodecContext *audio_out_ctx;
  // Move this into the object so that we aren't constantly allocating/deallocating it on the stack
  av_packet_ptr opkt;

  av_frame_ptr in_frame;
  av_frame_ptr out_frame;

  SWScale swscale;
  unsigned int packets_written;
  unsigned int frame_count;
  bool video_encoded;  // true once at least one frame has been sent to the video encoder

  AVBufferRef *hw_device_ctx;

  SwrContext *resample_ctx;
  AVAudioFifo *fifo;
  uint8_t *converted_in_samples;

  const char *filename;
  const char *format;

  // These are for in
  int64_t video_first_pts; /* starting pts of first in frame/packet */
  int64_t video_first_dts;
  int64_t audio_first_pts;
  int64_t audio_first_dts;
  int64_t video_last_pts;
  int64_t audio_last_pts;

  // These are for out, should start at zero.  We assume they do not wrap because we just aren't going to save files that big.
  int64_t *next_dts;
  std::map<int, int64_t> last_dts;
  std::map<int, int64_t> last_duration;
  int64_t audio_next_pts;

  int max_stream_index;

  size_t reorder_queue_size;
  std::map<int, std::list<std::shared_ptr<ZMPacket>>> reorder_queues;

  // HLS fragment tracking. With movflags=frag_keyframe, FFmpeg's mov muxer
  // doesn't write a fragment to disk until the *next* keyframe arrives (or
  // until av_write_trailer is called). So when keyframe N arrives, fragment
  // N-1 is what just got flushed. We snapshot avio_tell *after*
  // av_interleaved_write_frame() to capture the position past that flush, and
  // record fragment N-1 then.
  std::vector<Fragment> fragments_;
  int64_t last_fragment_offset_;    // byte offset where the current (in-progress) fragment starts
  int64_t last_fragment_start_dts_; // DTS of the keyframe that started the current fragment
  int64_t init_segment_end_;        // byte offset where init segment (ftyp+moov) ends
  bool    finalized_;               // true once finalize() has run trailer + last-fragment recording

  bool setup_resampler();
  int write_packet(AVPacket *pkt, AVStream *stream);

 public:
  VideoStore(
    const char *filename_in,
    const char *format_in,
    AVStream *video_in_stream,
    AVCodecContext  *video_in_ctx,
    AVStream *audio_in_stream,
    AVCodecContext  *audio_in_ctx,
    Monitor * p_monitor);
  ~VideoStore();
  bool  open();

  void write_video_packet(AVPacket pkt);
  void write_audio_packet(AVPacket pkt);
  int writeVideoFramePacket(const std::shared_ptr<ZMPacket> pkt);
  int writeAudioFramePacket(const std::shared_ptr<ZMPacket> pkt);
  int writePacket(const std::shared_ptr<ZMPacket> pkt);
  int write_packets(PacketQueue &queue);
  void flush_codecs();
  const std::vector<Fragment> &fragments() const { return fragments_; }
  int64_t init_segment_end() const { return init_segment_end_; }
  void writeM3U8(const std::string &path, const std::string &video_url, bool is_complete);
  // Flush queues, write trailer, close output, and record the final fragment.
  // Call this before writeM3U8(true) so the manifest contains every fragment.
  // Safe to call once; subsequent calls are no-ops. The destructor will skip
  // the trailer write if finalize() has already run.
  void finalize();

  const char *get_codec() {
    if (chosen_codec_data)
      return chosen_codec_data->codec_codec;
    if (video_out_stream)
      return avcodec_get_name(video_out_stream->codecpar->codec_id);
    return "";
  }
};

#endif // ZM_VIDEOSTORE_H

