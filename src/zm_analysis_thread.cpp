#include "zm_analysis_thread.h"

#include "zm_monitor.h"
#include "zm_signal.h"
#include "zm_time.h"

AnalysisThread::AnalysisThread(Monitor *monitor) : monitor_(monitor), terminate_(false) {
  thread_ = std::thread(&AnalysisThread::Run, this);
}

AnalysisThread::~AnalysisThread() { Stop(); }

void AnalysisThread::Start() {
  if (thread_.joinable()) thread_.join();
  terminate_ = false;
  Debug(3, "Starting analysis thread");
  thread_ = std::thread(&AnalysisThread::Run, this);
}

void AnalysisThread::Stop() {
  terminate_ = true;
  if (thread_.joinable()) thread_.join();
}

void AnalysisThread::Run() {
  while (!(terminate_ or zm_terminate)) {
    // Some periodic updates are required for variable capturing framerate
    if (!monitor_->Analyse()) {
      if (!(terminate_ or zm_terminate)) {
        // We only sleep when Analyse returns false because it is an error condition and we will
        // spin like mad if it persists.
        Microseconds sleep_for =
            monitor_->Active() ? Microseconds(ZM_SAMPLE_RATE) : Microseconds(ZM_SUSPENDED_RATE);
        Debug(2, "Sleeping for %" PRId64 "us", int64(sleep_for.count()));
        std::this_thread::sleep_for(sleep_for);
      }
    }
  }
}
