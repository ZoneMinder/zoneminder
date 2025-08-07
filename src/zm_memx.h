#ifndef ZM_MEMX_H
#define ZM_MEMX_H

#include <nlohmann/json.hpp>

#include <condition_variable>
#include <list>
#include <mutex>
#include <atomic>
#include <memory>
#include <thread>

extern "C" {
#include <libavcodec/avcodec.h>
#include <libavformat/avformat.h>
#include <libavfilter/buffersink.h>
#include <libavfilter/buffersrc.h>
#include <libavutil/opt.h>
#include <libswscale/swscale.h>
}

#ifdef HAVE_MEMX_H
//#include "memx/memx.h"
#include <memx/accl/MxAccl.h>

// FIXME move model stuff to it's ownfile
#define MODEL_WIDTH 640
#define MODEL_HEIGHT 640
// * 6; // 256 boxes, each with 6 elements [l, t, r, b, class, score]
#define NUM_NMS_PREDICTIONS 256

class MemX {
  private:
    MX::Runtime::MxAccl *accl;
    MX::Types::MxModelInfo model_info;

    std::mutex  mutex_;
    std::condition_variable condition_;

    std::atomic<bool> terminate_;
    std::thread thread_;

    size_t batchSize;
    size_t inSize;
    size_t outSize;

    int image_size;
    int count;

  public:
    class Job {
      private:
         MX::Runtime::MxAccl *m_accl;
      public:

        int index;
        av_frame_ptr scaled_frame;
        float m_width_rescale;
        float m_height_rescale;
        SwsContext *sw_scale_ctx;
        std::vector<float> predictions_buffer;
        std::mutex  mutex_;
        std::unique_lock<std::mutex> lck_;
        std::condition_variable condition_;

        Job(MX::Runtime::MxAccl *p_accl) :
          m_accl(p_accl),
          index(0),
          m_width_rescale(1.0),
          m_height_rescale(1.0),
          sw_scale_ctx(nullptr),
          lck_(mutex_, std::defer_lock)
          {
            scaled_frame = av_frame_ptr(zm_av_frame_alloc());
            scaled_frame->width = MODEL_WIDTH;
            scaled_frame->height = MODEL_HEIGHT;
            scaled_frame->format = AV_PIX_FMT_RGB24;
            predictions_buffer.resize(NUM_NMS_PREDICTIONS*6);
          };
        ~Job() {
          if (sw_scale_ctx) {
            sws_freeContext(sw_scale_ctx);
          }
        };
        void setFrame(AVFrame *frame) {
        }
        Job(Job &&in) :
          m_accl(in.m_accl),
          index(in.index),
          scaled_frame(std::move(in.scaled_frame)),
          m_width_rescale(in.m_width_rescale),
          m_height_rescale(in.m_height_rescale)
        {
          Debug(1, "In move");
          //in.scaled_frame = nullptr;
        }
        void lock() {
          lck_.lock();
        }
        void unlock() { lck_.unlock(); };

        void wait() {
          condition_.wait(lck_);
        }
        void notify() {
          condition_.notify_all();
        }
    };
    std::list<Job *> jobs;
    std::list<Job *> send_queue;

    explicit MemX();
    ~MemX();
    bool setup( const std::string &model_type, const std::string &model_file);
    void Run();
    Job * get_job();
    Job * send_packet(Job *job, std::shared_ptr<ZMPacket>);
    Job * send_image(Job *job, Image *image);
    Job * send_frame(Job *job, AVFrame *);

    const nlohmann::json receive_detections(Job *job, float threshold);
    nlohmann::json convert_predictions_to_coco_format(const std::vector<float>& predictions, float, float, float threshold);
};
#endif
#endif
