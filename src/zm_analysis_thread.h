#ifndef ZM_ANALYSIS_THREAD_H
#define ZM_ANALYSIS_THREAD_H

class Monitor;
#include <atomic>
#include <memory>
#include <thread>

class AnalysisThread {
 public:
  explicit AnalysisThread(Monitor* monitor);
  //explicit AnalysisThread(std::shared_ptr<Monitor> monitor);
  ~AnalysisThread();
  AnalysisThread(AnalysisThread &rhs) = delete;
  AnalysisThread(AnalysisThread &&rhs) = delete;

  void Stop() { terminate_ = true; }

 private:
  void Run();

  Monitor* monitor_;
  //std::shared_ptr<Monitor> monitor_;
  std::atomic<bool> terminate_;
  std::thread thread_;
};

#endif
