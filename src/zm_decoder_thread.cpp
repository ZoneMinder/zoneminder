#include "zm_decoder_thread.h"

#include "zm_monitor.h"
#include "zm_signal.h"

DecoderThread::DecoderThread(Monitor *monitor) : monitor_(monitor), terminate_(false) {
  thread_ = std::thread(&DecoderThread::Run, this);
}

DecoderThread::~DecoderThread() { Stop(); }

void DecoderThread::Start() {
  if (thread_.joinable()) thread_.join();
  terminate_ = false;
  thread_ = std::thread(&DecoderThread::Run, this);
}

void DecoderThread::Stop() {
  terminate_ = true;
  if (thread_.joinable()) thread_.join();
}

void DecoderThread::Run() {
  Debug(2, "DecoderThread::Run() for %d", monitor_->Id());

  while (!(terminate_ or zm_terminate)) {
    if (!monitor_->Decode()) {
      if (!(terminate_ or zm_terminate)) {
        // We only sleep when Decode returns false because it is an error condition and we will spin
        // like mad if it persists.
        Microseconds sleep_for =
            monitor_->Active() ? Microseconds(ZM_SAMPLE_RATE) : Microseconds(ZM_SUSPENDED_RATE);
        Debug(2, "Sleeping for %" PRId64 "us", int64(sleep_for.count()));
        std::this_thread::sleep_for(sleep_for);
      }
    }
  }
}
