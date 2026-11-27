#include "zm_poll_thread.h"

#include "zm_logger.h"
#include "zm_monitor.h"
#include "zm_signal.h"

#include <chrono>

PollThread::PollThread(Monitor *monitor) :
  monitor_(monitor), terminate_(false) {
  thread_ = std::thread(&PollThread::Run, this);
}

PollThread::~PollThread() {
  Stop();
}

void PollThread::Start() {
  Stop();  // Signal any running thread to terminate first
  if (thread_.joinable()) thread_.join();
  terminate_ = false;
  Debug(3, "Starting polling thread");
  thread_ = std::thread(&PollThread::Run, this);
}
void PollThread::Stop() {
  {
    std::lock_guard<std::mutex> lck(mutex_);
    terminate_ = true;
  }
  cv_.notify_all();
  if (thread_.joinable()) {
    thread_.join();
  }
}
void PollThread::Run() {
  while (!(terminate_ or zm_terminate)) {
    const std::chrono::steady_clock::time_point loop_start = std::chrono::steady_clock::now();
    monitor_->Poll();
    // Poll every 10 seconds. Wait here rather than sleep, so that Stop() (from
    // Monitor::Close(), on every stop, reconnect and reload of zmc) doesn't
    // have to wait for the rest of the 10 seconds.
    std::unique_lock<std::mutex> lck(mutex_);
    cv_.wait_until(lck, loop_start + std::chrono::seconds(10), [this] { return terminate_.load(); });
  }
}
