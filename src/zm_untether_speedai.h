#ifndef ZM_UNTETHER_SPEEDAI_H
#define ZM_UNTETHER_SPEEDAI_H
#ifdef HAVE_UNTETHER_H
// Untether runtime API header
#include "uai_untether.h"
#endif
#include <nlohmann/json.hpp>

#include <list>
#include <mutex>
#include <atomic>
#include <memory>
#include <thread>

#include "zm_quadra.h"

extern "C" {
#include <libavcodec/avcodec.h>
#include <libavformat/avformat.h>

#include <libavfilter/buffersink.h>
#include <libavfilter/buffersrc.h>

#include <libavutil/opt.h>

#include <libswscale/swscale.h>
}

#ifdef HAVE_UNTETHER_H
#define MODEL_WIDTH 640
#define MODEL_HEIGHT 640

class SpeedAI {
  private:
    UaiModule* module_;
    std::mutex  mutex_;
    std::atomic<bool> terminate_;
    std::thread thread_;


//    unsigned MODEL_WIDTH = 640, MODEL_HEIGHT = 640;
    size_t batchSize;
    size_t inSize;
    size_t outSize;

    float obj_threshold = 0.25;
    //float nms_threshold = 0.45;

    UaiDataStreamInfo *infos;

    // SpeedAI Yolo params
    int quantization_fp8p_bias = -3;
    int dequantization_uint16_bias = 4;
    int dequantization_fp8p_bias = -12;

    //Image &preprocess_image(const Image &image);

    int image_size;

    // Fast map via indexing, for integer values in range [0, 255]
    std::array<uint8_t, 256> m_fast_map;
    const int NUM_NMS_PREDICTIONS = 256;// * 6; // 256 boxes, each with 6 elements [l, t, r, b, class, score]
    std::vector<float> m_out_buf;
    float* outputBuffer;
    // m_quant_bounds[i].second is uppper limit of bin in floating point domain
    // that gets mapped to m_quant_bounds[i].first.
    std::array<std::pair<uint8_t, float>, 256> m_quant_bounds;
    static bool comparator(const decltype(m_quant_bounds)::value_type& a, const decltype(m_quant_bounds)::value_type& b) {
      return a.second < b.second;
    }
    int count;

  public:
    class Job {
      public:

        UaiModule *m_module;
        int index;
        UaiDataBuffer *inputBuf;
        UaiDataBuffer *outputBuf;
        UaiEvent event;
        av_frame_ptr scaled_frame;
        float m_width_rescale;
        float m_height_rescale;
        SwsContext *sw_scale_ctx;

        Job(UaiModule *p_module) :
          m_module(p_module),
          index(0),
          inputBuf(nullptr),
          outputBuf(nullptr),
          event({}),
          m_width_rescale(1.0),
          m_height_rescale(1.0),
          sw_scale_ctx(nullptr)
          {
            inputBuf = new UaiDataBuffer();
            outputBuf = new UaiDataBuffer();
            scaled_frame = av_frame_ptr(zm_av_frame_alloc());
            scaled_frame->width = MODEL_WIDTH;
            scaled_frame->height = MODEL_HEIGHT;
            scaled_frame->format = AV_PIX_FMT_RGB24;
            //m_width_rescale = ((float)MODEL_WIDTH / (float)input->width);
            //m_height_rescale = ((float)MODEL_HEIGHT / (float)input->height);
          };
        ~Job() {
          Debug(1, "In Job destructor");
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
          if (sw_scale_ctx) {
            sws_freeContext(sw_scale_ctx);
          }
        };
        void setFrame(AVFrame *frame) {
        }
        Job(Job &&in) :
          m_module(in.m_module),
          index(in.index),
          inputBuf(in.inputBuf),
          outputBuf(in.outputBuf),
          event(in.event),
          scaled_frame(std::move(in.scaled_frame)),
          m_width_rescale(in.m_width_rescale),
          m_height_rescale(in.m_height_rescale)
        {
          Debug(1, "In move");
          in.inputBuf = nullptr;
          in.outputBuf = nullptr;
          //in.scaled_frame = nullptr;
        }
        Job(const Job &in) :
          m_module(in.m_module),
          index(in.index),
          inputBuf(in.inputBuf),
          outputBuf(in.outputBuf),
          event(std::move(in.event)),
          scaled_frame(in.scaled_frame.get()),
          m_width_rescale(in.m_width_rescale),
          m_height_rescale(in.m_height_rescale)
        {
          Debug(1, "In copy");
        }
        Job& operator=(Job &&in) {
          m_module = in.m_module;
          index = in.index;
          inputBuf = in.inputBuf;
          outputBuf = in.outputBuf;
          event = std::move(in.event);
          scaled_frame = std::move(in.scaled_frame);
          m_width_rescale = in.m_width_rescale;
          m_height_rescale = in.m_height_rescale;
          return *this;
        };

    };
    std::list<Job *> jobs;
    std::list<Job *> send_queue;
    float dequantize(uint8_t val, int bias);
    uint8_t quantize(float val) const;
    int draw_box( AVFrame *inframe, AVFrame **outframe, int x, int y, int w, int h);

    Quadra *quadra;
    Quadra::filter_worker *drawbox_filter;
    AVFilterContext *drawbox_filter_ctx;

    explicit SpeedAI();
    ~SpeedAI();
    bool setup(
        const std::string &model_type,
        const std::string &model_file
        );
    void Run();
    Job * get_job();
    Job * send_packet(Job *job, std::shared_ptr<ZMPacket>);
    Job * send_image(Job *job, Image *image);
    Job * send_frame(Job *job, AVFrame *);
    //Job * send_job(Job *);

    const nlohmann::json receive_detections(Job *job);
    nlohmann::json convert_predictions_to_coco_format(const std::vector<float>& predictions, float, float);
    Quadra *getQuadra() const { return quadra; };
    bool setQuadra(Quadra *quadra, int width, int height);
};
#endif
#endif
