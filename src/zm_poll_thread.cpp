#include "zm_poll_thread.h"

#include "zm_monitor.h"
#include "zm_signal.h"
#include "zm_time.h"

PollThread::PollThread(Monitor *monitor) : monitor_(monitor), terminate_(false) {
  thread_ = std::thread(&PollThread::Run, this);
}

PollThread::~PollThread() { Stop(); }

void PollThread::Start() {
  if (thread_.joinable()) thread_.join();
  terminate_ = false;
  Debug(3, "Starting polling thread");
  thread_ = std::thread(&PollThread::Run, this);
}
void PollThread::Stop() {
  terminate_ = true;
  if (thread_.joinable()) {
    thread_.join();
  }
}
void PollThread::Run() {
  while (!(terminate_ or zm_terminate)) {
    monitor_->Poll();
  }
}
