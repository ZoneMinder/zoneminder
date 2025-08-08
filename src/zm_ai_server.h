#ifndef ZM_AI_SERVER_H
#define ZM_AI_SERVER_H

#include <atomic>
#include <memory>
#include <thread>
#include <condition_variable>

extern "C" {
#include <libavfilter/avfilter.h>
}

#include "zm_config.h"
#include "zm_packet.h"

#if HAVE_UNTETHER_H
#include "zm_untether_speedai.h"
#endif

#if HAVE_QUADRA
#include "zm_quadra.h"
#endif

#if HAVE_MEMX_H
  #include "zm_memx.h"
#endif
#if HAVE_MX_ACCL_H
  #include "zm_mx_accl.h"
#endif

class Monitor;

class AIThread {
 public:
#if HAVE_UNTETHER_H
  explicit AIThread(const std::shared_ptr<Monitor> monitor, SpeedAI *speedai);
#endif
#if HAVE_MEMX_H
  explicit AIThread(const std::shared_ptr<Monitor> monitor, MemX *memx);
#endif
#if HAVE_MX_ACCL_H
  explicit AIThread(const std::shared_ptr<Monitor> monitor, MxAccl *memx);
#endif
  ~AIThread();
  AIThread(AIThread &rhs) = delete;
  AIThread(AIThread &&rhs) = delete;

  void Start();
  void Stop();
  void Join();
  bool Stopped() const { return terminate_; }

 private:
  void Run();
  void Inference();

  std::shared_ptr<Monitor> monitor_;
  std::atomic<bool> terminate_;
  std::thread thread_;
  std::thread inference_thread_;
#if HAVE_UNTETHER_H
  SpeedAI *speedai;
  SpeedAI::Job *job;
#endif
#if HAVE_MEMX_H
  MemX *memx;
  MemX::Job *memx_job;
#endif
#if HAVE_MX_ACCL_H
  MxAccl *mx_accl;
  MxAccl::Job *mx_accl_job;
#endif
#if HAVE_QUADRA
  Quadra::filter_worker *drawbox_filter;
#endif
  AVFilterContext *drawbox_filter_ctx;
  int32_t analysis_image_count;

  std::list<std::shared_ptr<ZMPacket>> send_queue;
  std::mutex  mutex_;
  std::condition_variable condition_;
};

int draw_boxes(Image *in_image, Image *out_image,const nlohmann::json &coco_object, int font_size, int line_width);
#if HAVE_QUADRA
int draw_boxes( Quadra::filter_worker *drawbox_filter, AVFilterContext *drawbox_filter_ctx, Image *in_image, Image *out_image, const nlohmann::json &coco_object, int font_size, int line_width);
int draw_box( Quadra::filter_worker * drawbox_filter, AVFilterContext *drawbox_filter_ctx, AVFrame *inframe, AVFrame **outframe, int x, int y, int w, int h);
#endif

#endif
