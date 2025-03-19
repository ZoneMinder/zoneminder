#ifndef ZM_AI_SERVER_H
#define ZM_AI_SERVER_H

#include <atomic>
#include <memory>
#include <thread>

#include "zm_untether_speedai.h"

class Monitor;

class AIThread {
 public:
  explicit AIThread(const std::shared_ptr<Monitor> monitor, SpeedAI *speedai);
  ~AIThread();
  AIThread(AIThread &rhs) = delete;
  AIThread(AIThread &&rhs) = delete;

  void Start();
  void Stop();
  void Join();
  bool Stopped() const { return terminate_; }

 private:
  void Run();

  std::shared_ptr<Monitor> monitor_;
  std::atomic<bool> terminate_;
  std::thread thread_;
  SpeedAI *speedai;
};


//void SpeedAIDetect(std::shared_ptr<Monitor> monitor);
int draw_boxes( Quadra::filter_worker *drawbox_filter, AVFilterContext *drawbox_filter_ctx, Image *in_image, Image *out_image, const nlohmann::json &coco_object, int font_size);
int draw_box( Quadra::filter_worker * drawbox_filter, AVFilterContext *drawbox_filter_ctx, AVFrame *inframe, AVFrame **outframe, int x, int y, int w, int h);

#endif
