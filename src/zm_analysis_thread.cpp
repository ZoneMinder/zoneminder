#include "zm_analysis_thread.h"

#include "zm_monitor.h"
#include "zm_signal.h"
#include "zm_time.h"

AnalysisThread::AnalysisThread(Monitor *monitor) :
    monitor_(monitor), terminate_(false) {
  thread_ = std::thread(&AnalysisThread::Run, this);
}

AnalysisThread::~AnalysisThread() {
  Stop();
  if (thread_.joinable()) thread_.join();
}

void AnalysisThread::Start() {
  if (thread_.joinable()) thread_.join();
  terminate_ = false;
  Debug(3, "Starting analysis thread");
  thread_ = std::thread(&AnalysisThread::Run, this);
}

void AnalysisThread::Run() {
  while (!(terminate_ or zm_terminate)) {
    monitor_->Analyse();
  }
}
