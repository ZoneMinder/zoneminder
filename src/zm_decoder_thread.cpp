#include "zm_decoder_thread.h"

#include "zm_monitor.h"
#include "zm_signal.h"

DecoderThread::DecoderThread(Monitor *monitor) :
    monitor_(monitor), terminate_(false) {
  thread_ = std::thread(&DecoderThread::Run, this);
}

DecoderThread::~DecoderThread() {
  Stop();
  if (thread_.joinable()) thread_.join();
}

void DecoderThread::Start() {
  if (thread_.joinable()) thread_.join();
  terminate_ = false;
  thread_ = std::thread(&DecoderThread::Run, this);
}
void DecoderThread::Run() {
  Debug(2, "DecoderThread::Run() for %d", monitor_->Id());

  while (!(terminate_ or zm_terminate)) {
    monitor_->Decode();
  }
}
