#ifndef ZM_MX_ACCL_H
#define ZM_MX_ACCL_H

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

#ifdef HAVE_MX_ACCL_H
#include <memx/accl/MxAccl.h>
#include "zm_memx_yolov8.h"
#include "zm_object_classes.h"

class MxAccl {
  private:
    ObjectClasses object_classes_;  // Class labels (defaults to COCO)
    MX::Runtime::MxAccl *accl;
    MX::Types::MxModelInfo model_info;
    int32_t input_height, input_width, input_depth, input_channels, input_format;
    int32_t output_height, output_width, output_depth, output_channels, output_format;
    YOLOv8 *yolov8;
    float confidence;

    std::mutex  mutex_;
    std::condition_variable condition_;

    std::atomic<bool> terminate_;
    std::thread thread_;


    size_t batchSize;
    size_t inSize;
    size_t outSize;

    int image_size;
    int count;

    bool in_callback_func(vector<const MX::Types::FeatureMap *> dst, int channel_idx);
    bool out_callback_func(vector<const MX::Types::FeatureMap *> src, int channel_idx);
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

        std::mutex  mutex_;
        std::unique_lock<std::mutex> lck_;
        std::condition_variable condition_;

        std::vector<float *> accl_input_data;
        std::vector<float *> accl_output_data;
        YOLOv8Result result;

        Job(MX::Runtime::MxAccl *p_accl) :
          m_accl(p_accl),
          index(0),
          m_width_rescale(1.0),
          m_height_rescale(1.0),
          sw_scale_ctx(nullptr),
          lck_(mutex_, std::defer_lock)
          {
            scaled_frame = av_frame_ptr(av_frame_alloc());
            scaled_frame->format = AV_PIX_FMT_RGB24;
          };
        ~Job() {
          if (sw_scale_ctx) {
            sws_freeContext(sw_scale_ctx);
          }
          for (auto ptr : accl_input_data) {
            if (ptr) delete ptr;
            ptr = nullptr;
          }
          for (auto ptr : accl_output_data) {
            if (ptr) delete ptr;
            ptr = nullptr;
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
        void lock() { lck_.lock(); }
        void unlock() { lck_.unlock(); };

        void wait() { condition_.wait(lck_); }
        void notify() { condition_.notify_all(); }
    };
    std::list<Job *> jobs;
    std::list<Job *> send_queue;
    std::list<Job *> receive_queue;

    explicit MxAccl();
    ~MxAccl();
    bool setup( const std::string &model_type, const std::string &model_file, float confidence=0.5);
    void Run();
    Job * get_job();
    int send_packet(Job *job, std::shared_ptr<ZMPacket>);
    int send_image(Job *job, Image *image);
    int send_frame(Job *job, AVFrame *);


    const nlohmann::json receive_detections(Job *job, float threshold);
};
#endif
#endif
