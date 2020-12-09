#include "zm.h"
#if HAVE_RTSP_SERVER

#ifndef ZM_RTSP_SERVER_THREAD_H
#define ZM_RTSP_SERVER_THREAD_H

#include "zm_thread.h"
#include <signal.h>

#include "zm_monitor.h"

#include <BasicUsageEnvironment.hh>
#include <RTSPServer.hh>

class RTSPServerThread : public Thread {
  private:
    Monitor *monitor;
    bool terminate;

    TaskScheduler* scheduler;
    UsageEnvironment* env;
    UserAuthenticationDatabase* authDB;

    RTSPServer* rtspServer;


  public:
    explicit RTSPServerThread(Monitor *);
    ~RTSPServerThread();
    int run();

    void stop() {
      terminate = true;
    }
    bool stopped() const {
      return terminate;
    }
};

#endif
#endif
