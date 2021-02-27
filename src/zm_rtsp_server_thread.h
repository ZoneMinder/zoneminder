#ifndef ZM_RTSP_SERVER_THREAD_H
#define ZM_RTSP_SERVER_THREAD_H

#include "zm_config.h"
#include "zm_ffmpeg.h"
#include "zm_thread.h"
#include "zm_rtsp_server_server_media_subsession.h"
#include "zm_rtsp_server_fifo_source.h"
#include <list>
#include <memory>

#if HAVE_RTSP_SERVER
#include <BasicUsageEnvironment.hh>
#include <RTSPServer.hh>

class Monitor;

class RTSPServerThread : public Thread {
  private:
    std::shared_ptr<Monitor> monitor_;
    char terminate;

    TaskScheduler* scheduler;
    UsageEnvironment* env;
    UserAuthenticationDatabase* authDB;

    RTSPServer* rtspServer;
    std::list<FramedSource *> sources;

  public:
    explicit RTSPServerThread(std::shared_ptr<Monitor> monitor);
    ~RTSPServerThread();
    ServerMediaSession *addSession(std::string &streamname);
    void addStream(std::string &streamname, AVStream *, AVStream *);
    FramedSource *addFifo(ServerMediaSession *sms, std::string fifo);
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
#endif // HAVE_RTSP_SERVER

#endif // ZM_RTSP_SERVER_THREAD_H
