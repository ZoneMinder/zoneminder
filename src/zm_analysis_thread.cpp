#include "zm_analysis_thread.h"

#include "zm_signal.h"

AnalysisThread::AnalysisThread(std::shared_ptr<Monitor> monitor) :
    monitor_(std::move(monitor)), terminate_(false) {
  thread_ = std::thread(&AnalysisThread::Run, this);
}

AnalysisThread::~AnalysisThread() {
  terminate_ = true;
  if (thread_.joinable())
    thread_.join();
}

AnalysisThread::AnalysisThread(AnalysisThread &&rhs) noexcept
    : monitor_(std::move(rhs.monitor_)), terminate_(rhs.terminate_.load()), thread_(std::move(rhs.thread_)) {}

void AnalysisThread::Run() {
  Debug(2, "AnalysisThread::Run()");

  useconds_t analysis_rate = monitor_->GetAnalysisRate();
  unsigned int analysis_update_delay = monitor_->GetAnalysisUpdateDelay();
  time_t last_analysis_update_time, cur_time;
  monitor_->UpdateAdaptiveSkip();
  last_analysis_update_time = time(nullptr);

  while (!(terminate_ or zm_terminate)) {
    // Some periodic updates are required for variable capturing framerate
    if (analysis_update_delay) {
      cur_time = time(nullptr);
      if ((unsigned int) (cur_time - last_analysis_update_time) > analysis_update_delay) {
        analysis_rate = monitor_->GetAnalysisRate();
        monitor_->UpdateAdaptiveSkip();
        last_analysis_update_time = cur_time;
      }
    }

    Debug(2, "Analyzing");
    if (!monitor_->Analyse()) {
      Debug(2, "uSleeping for %d", (monitor_->Active() ? ZM_SAMPLE_RATE : ZM_SUSPENDED_RATE));
      usleep(monitor_->Active() ? ZM_SAMPLE_RATE : ZM_SUSPENDED_RATE);
    } else if (analysis_rate) {
      Debug(2, "uSleeping for %d", analysis_rate);
      usleep(analysis_rate);
    } else {
      Debug(2, "Not Sleeping");
    }
  }
}
