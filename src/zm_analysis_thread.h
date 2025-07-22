#ifndef ZM_ANALYSIS_THREAD_H
#define ZM_ANALYSIS_THREAD_H

#include <atomic>
#include <memory>
#include <thread>

class Monitor;

class AnalysisThread {
 public:
  explicit AnalysisThread(Monitor *monitor);
  ~AnalysisThread();
  AnalysisThread(AnalysisThread &rhs) = delete;
  AnalysisThread(AnalysisThread &&rhs) = delete;

  void Start();
  void Stop();
  void Join();
  bool Stopped() const { return terminate_; }

 private:
  void Run();

  Monitor *monitor_;
  std::atomic<bool> terminate_;
  std::thread thread_;
};

#endif
