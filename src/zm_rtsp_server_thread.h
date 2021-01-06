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
    char terminate;

    TaskScheduler* scheduler;
    UsageEnvironment* env;
    UserAuthenticationDatabase* authDB;

    RTSPServer* rtspServer;

  public:
    explicit RTSPServerThread(Monitor *);
    ~RTSPServerThread();
    void addStream();
    int run();
    void stop();
    bool stopped() const;
  private:
    std::string getRtpFormat(int format, bool muxTS);
    int addSession(
        const std::string & sessionName,
        const std::list<ServerMediaSubsession*> & subSession
    );
};

#endif
#endif
