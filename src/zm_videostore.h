#ifndef ZM_VIDEOSTORE_H
#define ZM_VIDEOSTORE_H

#include "zm_config.h"
#include "zm_define.h"
#include "zm_ffmpeg.h"
#include "zm_swscale.h"

#include <list>
#include <memory>
#include <map>

extern "C"  {
#ifdef HAVE_LIBSWRESAMPLE
  #include "libswresample/swresample.h"
#else
  #ifdef HAVE_LIBAVRESAMPLE
    #include "libavresample/avresample.h"
  #endif
#endif
#include "libavutil/audio_fifo.h"
#if HAVE_LIBAVUTIL_HWCONTEXT_H
  #include "libavutil/hwcontext.h"
#endif
#include "libavutil/buffer.h"
}

#if HAVE_LIBAVCODEC

class Monitor;
class ZMPacket;
class PacketQueue;

class VideoStore {
  private:

    struct CodecData {
      const AVCodecID codec_id;
      const char *codec_codec;
      const char *codec_name;
      const enum AVPixelFormat sw_pix_fmt;
      const enum AVPixelFormat hw_pix_fmt;
#if HAVE_LIBAVUTIL_HWCONTEXT_H && LIBAVCODEC_VERSION_CHECK(57, 107, 0, 107, 0)
      const AVHWDeviceType hwdevice_type;
#endif
    };

    static struct CodecData codec_data[];
    CodecData *chosen_codec_data;

    Monitor *monitor;
    AVOutputFormat *out_format;
    AVFormatContext *oc;
    AVStream *video_out_stream;
    AVStream *audio_out_stream;

    const AVCodec *video_out_codec;
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

    AVFrame *video_in_frame;
    AVFrame *in_frame;
    AVFrame *out_frame;
    AVFrame *hw_frame;

    SWScale swscale;
    unsigned int packets_written;
    unsigned int frame_count;

    AVBufferRef *hw_device_ctx;

#ifdef HAVE_LIBSWRESAMPLE
    SwrContext *resample_ctx;
    AVAudioFifo *fifo;
#else
#ifdef HAVE_LIBAVRESAMPLE
    AVAudioResampleContext* resample_ctx;
#endif
#endif
    uint8_t *converted_in_samples;

    const char *filename;
    const char *format;

    // These are for in
    int64_t video_first_pts;
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

    void write_video_packet(AVPacket &pkt);
    void write_audio_packet(AVPacket &pkt);
    int writeVideoFramePacket(const std::shared_ptr<ZMPacket> pkt);
    int writeAudioFramePacket(const std::shared_ptr<ZMPacket> pkt);
    int writePacket(const std::shared_ptr<ZMPacket> pkt);
    int write_packets(PacketQueue &queue);
    void flush_codecs();
};

#endif //havelibav
#endif //zm_videostore_h

