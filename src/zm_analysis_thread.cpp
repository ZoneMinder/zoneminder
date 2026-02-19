#include "zm_analysis_thread.h"

#include "zm_monitor.h"
#include "zm_signal.h"
#include "zm_time.h"

AnalysisThread::AnalysisThread(Monitor *monitor) :
  monitor_(monitor), terminate_(false) {
  thread_ = std::thread(&AnalysisThread::Run, this);
  set_cpu_affinity(thread_);
}

AnalysisThread::~AnalysisThread() {
  Stop();
  if (thread_.joinable()) thread_.join();
}

void AnalysisThread::Start() {
  Stop();  // Signal any running thread to terminate first
  if (thread_.joinable()) thread_.join();
  terminate_ = false;
  Debug(3, "Starting analysis thread");
  thread_ = std::thread(&AnalysisThread::Run, this);
  set_cpu_affinity(thread_);
}

void AnalysisThread::Stop() {
  terminate_ = true;
}
void AnalysisThread::Join() {
  if (thread_.joinable()) thread_.join();
}

void AnalysisThread::Run() {
  while (!(terminate_ or zm_terminate)) {
    // Some periodic updates are required for variable capturing framerate
    int ret = monitor_->Analyse();
    if (ret < 0) {
      if (!(terminate_ or zm_terminate)) {
        // We wait on the packetqueue condition variable instead of sleeping.
        // This allows us to wake up immediately when decoding completes.
        Microseconds wait_for = monitor_->Active() ? Microseconds(ZM_SAMPLE_RATE) : Microseconds(ZM_SUSPENDED_RATE);
        Debug(5, "Waiting for %" PRId64 "us", int64(wait_for.count()));
        monitor_->GetPacketQueue()->wait_for(wait_for);
      }
    }
  }
}
