#include "zm.h"
#if HAVE_RTSP_SERVER

#ifndef ZM_RTSP_SERVER_THREAD_H
#define ZM_RTSP_SERVER_THREAD_H

#include "zm_thread.h"
#include <signal.h>

#include "zm_monitor.h"

#include <BasicUsageEnvironment.hh>
#include <RTSPServer.hh>
#include <libavcodec/codec_id.h>
#include <libavformat/avformat.h>

class RTSPServerThread : public Thread {
  private:
    Monitor *monitor;
    char terminate;

    TaskScheduler* scheduler;
    UsageEnvironment* env;
    UserAuthenticationDatabase* authDB;

    RTSPServer* rtspServer;
    std::list<FramedSource *> sources;

  public:
    explicit RTSPServerThread(Monitor *);
    ~RTSPServerThread();
    void addStream(AVStream *, AVStream *);
    int run();
    void stop();
    bool stopped() const;
  private:
    const std::string getRtpFormat(AVCodecID codec, bool muxTS);
    int addSession(
        const std::string & sessionName,
        const std::list<ServerMediaSubsession*> & subSession
    );
};

#endif
#endif
