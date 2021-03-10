#ifndef ZM_RTSP_SERVER_THREAD_H
#define ZM_RTSP_SERVER_THREAD_H

#include "zm_config.h"
#include "zm_ffmpeg.h"
#include "xop/RtspServer.h"

#include "zm_rtsp_server_fifo_source.h"
#include <atomic>
#include <list>
#include <memory>

#if HAVE_RTSP_SERVER

class Monitor;

class RTSPServerThread {
  private:
    std::shared_ptr<Monitor> monitor_;

    std::thread thread_;
    std::atomic<bool> terminate_;
    std::mutex scheduler_watch_var_mutex_;
    char scheduler_watch_var_;

    std::shared_ptr<xop::EventLoop> eventLoop;
    std::shared_ptr<xop::RtspServer> rtspServer;

    std::list<ZoneMinderFifoSource *> sources;
    int port;

  public:
    explicit RTSPServerThread(int port);
    ~RTSPServerThread();
    xop::MediaSession *addSession(std::string &streamname);
    void removeSession(xop::MediaSession *sms);
    ZoneMinderFifoSource *addFifo(xop::MediaSession *sms, std::string fifo);
    void Run();
    void Stop();
    int Start();
    bool IsStopped() const { return terminate_; };
};
#endif // HAVE_RTSP_SERVER

#endif // ZM_RTSP_SERVER_THREAD_H
