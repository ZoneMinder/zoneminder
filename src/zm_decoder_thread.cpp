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
  Stop();  // Signal any running thread to terminate first
  if (thread_.joinable()) thread_.join();
  terminate_ = false;
  thread_ = std::thread(&DecoderThread::Run, this);
}

void DecoderThread::Stop() {
  terminate_ = true;
}

void DecoderThread::Join() {
  if (thread_.joinable()) thread_.join();
}

void DecoderThread::Run() {
  Debug(2, "DecoderThread::Run() for %d", monitor_->Id());

  while (!(terminate_ or zm_terminate)) {
    if (!monitor_->Decode()) {
      if (!(terminate_ or zm_terminate)) {
        // We wait on the packetqueue condition variable instead of sleeping.
        // This allows us to wake up immediately when new packets are queued.
        Microseconds wait_for = monitor_->Active() ? Microseconds(ZM_SAMPLE_RATE) : Microseconds(ZM_SUSPENDED_RATE);
        Debug(2, "Waiting for %" PRId64 "us", int64(wait_for.count()));
        monitor_->GetPacketQueue()->wait_for(wait_for);
      }
    }
  }
}
