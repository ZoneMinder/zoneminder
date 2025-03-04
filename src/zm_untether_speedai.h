#ifndef ZM_UNTETHER_SPEEDAI_H
#define ZM_UNTETHER_SPEEDAI_H
#ifdef HAVE_UNTETHER_H
// Untether runtime API header
#include "uai_untether.h"
#endif
#include <nlohmann/json.hpp>

#include <list>

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
#define MODEL_WIDTH 640
#define MODEL_HEIGHT 640

class SpeedAI {
  private:
    Monitor *monitor;
    UaiModule* module;

//    unsigned MODEL_WIDTH = 640, MODEL_HEIGHT = 640;
    size_t batchSize;
    size_t inSize;
    size_t outSize;

    //int model_width = 640;
    //int model_height = 640;
    //int model_format;

    float obj_threshold = 0.25;
    float nms_threshold = 0.45;

    //DMABuffers m_dma_bufs;
    //SWScale swscale;
    SwsContext *sw_scale_ctx;
    UaiDataStreamInfo *infos;

    // SpeedAI Yolo params
    int quantization_fp8p_bias = -3, dequantization_uint16_bias = 4, dequantization_fp8p_bias = -12;

    //Image &preprocess_image(const Image &image);

    int image_size;

    // Quantization bias
    int m_bias;

    // Fast map via indexing, for integer values in range [0, 255]
    std::array<uint8_t, 256> m_fast_map;

    // m_quant_bounds[i].second is uppper limit of bin in floating point domain
    // that gets mapped to m_quant_bounds[i].first.
    std::array<std::pair<uint8_t, float>, 256> m_quant_bounds;
    static bool comparator(const decltype(m_quant_bounds)::value_type& a, const decltype(m_quant_bounds)::value_type& b)
    {
        return a.second < b.second;
    }

    class Job {
      public:
        Job(UaiModule *p_module, AVFrame *input) :
          m_module(p_module),
          //inputBuf(nullptr),
          //outputBuf(nullptr),
          event({})
          //scaled_frame(0)
          {
            inputBuf = new UaiDataBuffer();
            outputBuf = new UaiDataBuffer();
            scaled_frame = new AVFrame();
            scaled_frame->width = MODEL_WIDTH;
            scaled_frame->height = MODEL_HEIGHT;
            scaled_frame->format = AV_PIX_FMT_RGB24;
            m_width_rescale = ((float)MODEL_WIDTH / (float)input->width);
            m_height_rescale = ((float)MODEL_HEIGHT / (float)input->height);
          };
        ~Job() {
          //Debug(1, "In Job destructor");
          if (inputBuf) {
            uai_module_data_buffer_detach(m_module, inputBuf);
            delete inputBuf;
            inputBuf = nullptr;
          }
          if (outputBuf) {
            uai_module_data_buffer_detach(m_module, outputBuf);
            delete outputBuf;
            outputBuf = nullptr;
          }
          if (scaled_frame)
            av_frame_unref(scaled_frame);
        };
        Job(Job &&in) :
          m_module(in.m_module),
          inputBuf(in.inputBuf),
          outputBuf(in.outputBuf),
          event(in.event),
          scaled_frame(in.scaled_frame),
          m_width_rescale(in.m_width_rescale),
          m_height_rescale(in.m_height_rescale)
        {
          //Debug(1, "In move");
          in.inputBuf = nullptr;
          in.outputBuf = nullptr;
          in.scaled_frame = nullptr;
        }
        Job(const Job &in) :
          m_module(in.m_module),
          inputBuf(in.inputBuf),
          outputBuf(in.outputBuf),
          event(std::move(in.event)),
          scaled_frame(in.scaled_frame),
          m_width_rescale(in.m_width_rescale),
          m_height_rescale(in.m_height_rescale)
        {
          Debug(1, "In copy");
        }
        Job& operator=(Job &&in) {
          m_module = in.m_module;
          inputBuf = in.inputBuf;
          outputBuf = in.outputBuf;
          event = std::move(in.event);
          scaled_frame = in.scaled_frame;
          m_width_rescale = in.m_width_rescale;
          m_height_rescale = in.m_height_rescale;
          return *this;
        };

      UaiModule *m_module;
      UaiDataBuffer *inputBuf;
      UaiDataBuffer *outputBuf;
      UaiEvent event;
      AVFrame *scaled_frame;
      float m_width_rescale;
      float m_height_rescale;
    };
    std::list<Job> jobs;
    float dequantize(uint8_t val, int bias);
    uint8_t quantize(float val) const;

  public:
    explicit SpeedAI(Monitor *parent_);
    ~SpeedAI();
    bool setup(
        const std::string &model_type,
        const std::string &model_file
        );
    int send_image(std::shared_ptr<ZMPacket>);
    int receive_detections(std::shared_ptr<ZMPacket>);
    nlohmann::json convert_predictions_to_coco_format(const std::vector<float>& predictions, float, float);
};
#endif
#endif
