#ifndef ZM_POLL_THREAD_H
#define ZM_POLL_THREAD_H

#include <atomic>
#include <memory>
#include <thread>

class Monitor;

class PollThread {
 public:
  explicit PollThread(Monitor *monitor);
  ~PollThread();
  PollThread(PollThread &rhs) = delete;
  PollThread(PollThread &&rhs) = delete;

  void Start();
  void Stop();
  bool Stopped() const { return terminate_; }

 private:
  void Run();

  Monitor *monitor_;
  std::atomic<bool> terminate_;
  std::thread thread_;
};

#endif
