#ifndef ZM_ANALYSIS_THREAD_H
#define ZM_ANALYSIS_THREAD_H

#include "zm_monitor.h"
#include "zm_thread.h"

class AnalysisThread : public Thread {
  private:
    bool terminate;
    Monitor *monitor;

  public:
    explicit AnalysisThread(Monitor *);
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
