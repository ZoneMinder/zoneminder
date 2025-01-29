#ifndef ZM_QUADRA_THREAD_H
#define ZM_QUADRA_THREAD_H

#include <atomic>
#include <memory>
#include <thread>

#include <ni_device_api.h>
#include <ni_av_codec.h>
#include <ni_util.h>

class Monitor;

class Quadra {
 public:
  explicit Quadra(Monitor *monitor);
  ~Quadra();
  Quadra(Quadra &rhs) = delete;
  Quadra(Quadra &&rhs) = delete;

  bool setup();
  bool detect();

 private:
  ni_session_context_t api_ctx;
  ni_network_data_t network;

  Monitor *monitor_;
};

#endif
