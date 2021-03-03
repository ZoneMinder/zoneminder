#ifndef ZM_RTSP_SERVER_THREAD_H
#define ZM_RTSP_SERVER_THREAD_H

#include "zm_config.h"
#include "zm_ffmpeg.h"
#include "zm_thread.h"
#include "zm_rtsp_server_server_media_subsession.h"
#include "zm_rtsp_server_fifo_source.h"
#include <atomic>
#include <list>
#include <memory>

#if HAVE_RTSP_SERVER
#include <BasicUsageEnvironment.hh>
#include <RTSPServer.hh>

class Monitor;

class RTSPServerThread {
  private:
    std::shared_ptr<Monitor> monitor_;

    std::thread thread_;
    std::atomic<bool> terminate_;
    std::mutex scheduler_watch_var_mutex_;
    char scheduler_watch_var_;

    TaskScheduler* scheduler;
    UsageEnvironment* env;
    UserAuthenticationDatabase* authDB;

    RTSPServer* rtspServer;
    std::list<FramedSource *> sources;

  public:
    explicit RTSPServerThread(int port);
    ~RTSPServerThread();
    ServerMediaSession *addSession(std::string &streamname);
    void removeSession(ServerMediaSession *sms);
    void addStream(std::string &streamname, AVStream *, AVStream *);
    FramedSource *addFifo(ServerMediaSession *sms, std::string fifo);
    void Run();
    void Stop();
    bool IsStopped() const { return terminate_; };
  private:
    const std::string getRtpFormat(AVCodecID codec, bool muxTS);
    int addSession(
        const std::string & sessionName,
        const std::list<ServerMediaSubsession*> & subSession
    );
};
#endif // HAVE_RTSP_SERVER

#endif // ZM_RTSP_SERVER_THREAD_H
