#ifndef ZM_UNTETHER_SPEEDAI_H
#define ZM_UNTETHER_SPEEDAI_H
#ifdef HAVE_UNTETHER_H
// Untether runtime API header
#include "uai_untether.h"
#endif

extern "C" {
#include <libavcodec/avcodec.h>
#include <libavformat/avformat.h>

#include <libavfilter/buffersink.h>
#include <libavfilter/buffersrc.h>

#include <libavutil/opt.h>

#include <libswscale/swscale.h>
}

class Monitor;

#ifdef HAVE_UNTETHER_H
class SpeedAI {
  private:
    Monitor *monitor;
    UaiModule* module;

    unsigned MODEL_WIDTH = 640, MODEL_HEIGHT = 640;
    UaiDataBuffer inputBuf, outputBuf;
    size_t batchSize;
    size_t inSize;
    size_t outSize;

    //DMABuffers m_dma_bufs;
    AVFrame scaled_frame;
    //SWScale swscale;
    SwsContext *sw_scale_ctx;

    // SpeedAI Yolo params
    int quantization_fp8p_bias = -3, dequantization_uint16_bias = 4, dequantization_fp8p_bias = -12;

    //Image &preprocess_image(const Image &image);

    int image_size;

  public:
    explicit SpeedAI(Monitor *parent_);
    ~SpeedAI();
    bool setup(
        const std::string &model_type,
        const std::string &model_file
        );
    int detect(const Image &image);
};
#endif
#endif
