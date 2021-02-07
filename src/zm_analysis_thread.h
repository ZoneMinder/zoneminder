#ifndef ZM_ANALYSIS_THREAD_H
#define ZM_ANALYSIS_THREAD_H

#include "zm_monitor.h"
#include "zm_thread.h"
#include <memory>

class AnalysisThread : public Thread {
  private:
    std::shared_ptr<Monitor> monitor;
    bool terminate;

  public:
    explicit AnalysisThread(std::shared_ptr<Monitor> monitor);
    ~AnalysisThread();
    int run();

    void stop() {
      terminate = true;
    }
    bool stopped() const {
      return terminate;
    }

};

#endif
