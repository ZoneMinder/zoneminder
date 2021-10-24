#include "zm_rtsp_server_thread.h"

#include "zm_config.h"
#include "zm_logger.h"

#if HAVE_RTSP_SERVER

RTSPServerThread::RTSPServerThread(int p_port) :
    terminate_(false), scheduler_watch_var_(0), port(p_port)
{
  eventLoop = std::make_shared<xop::EventLoop>();
  rtspServer = xop::RtspServer::Create(eventLoop.get());

  if ( rtspServer == nullptr ) {
    Fatal("Failed to create rtspServer");
    return;
  }

  thread_ = std::thread(&RTSPServerThread::Run, this);
}

RTSPServerThread::~RTSPServerThread() {
  Stop();
  if (thread_.joinable())
    thread_.join();

}

void RTSPServerThread::Run() {
  Debug(1, "RTSPServerThread::Run()");
  if (rtspServer) {
    while (!scheduler_watch_var_) {
      //if (clients > 0) {
       sleep(1); 
      //}
    }
  }
  Debug(1, "RTSPServerThread::done()");
}

int RTSPServerThread::Start() {
  return rtspServer->Start(std::string("0.0.0.0"), port);
}

void RTSPServerThread::Stop() {
  Debug(1, "RTSPServerThread::stop()");
  terminate_ = true;

  {
    std::lock_guard<std::mutex> lck(scheduler_watch_var_mutex_);
    scheduler_watch_var_ = 1;
  }

  for ( std::list<ZoneMinderFifoSource *>::iterator it = sources.begin(); it != sources.end(); ++it ) {
    Debug(1, "RTSPServerThread::stopping source");
    (*it)->Stop();
  }
  while ( sources.size() ) {
    Debug(1, "RTSPServerThread::stop closing source");
    ZoneMinderFifoSource *source = sources.front();
    sources.pop_front();
    delete source;
  }
}

xop::MediaSession *RTSPServerThread::addSession(std::string &streamname) {
  
  xop::MediaSession *session = xop::MediaSession::CreateNew(streamname);
  if (session) {
    session->AddNotifyConnectedCallback([] (xop::MediaSessionId sessionId, std::string peer_ip, uint16_t peer_port) {
        Debug(1, "RTSP client connect, ip=%s, port=%hu \n", peer_ip.c_str(), peer_port);
        });

    session->AddNotifyDisconnectedCallback([](xop::MediaSessionId sessionId, std::string peer_ip, uint16_t peer_port) {
  Debug(1, "RTSP client disconnect, ip=%s, port=%hu \n", peer_ip.c_str(), peer_port);
});

    rtspServer->AddSession(session);
    //char *url = rtspServer->rtspURL(session);
    //Debug(1, "url is %s for stream %s", url, streamname.c_str());
    //delete[] url;
  }
  return session;
}

void RTSPServerThread::removeSession(xop::MediaSession *session) {
  //rtspServer->removeServerMediaSession(session);
}

ZoneMinderFifoSource *RTSPServerThread::addFifo(
    xop::MediaSession *session,
    std::string fifo) {
  if (!rtspServer) return nullptr;

  ZoneMinderFifoSource *source = nullptr;

  if (!fifo.empty()) {
    std::string rtpFormat;
    if (std::string::npos != fifo.find("h264")) {
      rtpFormat = "video/H264";
      session->AddSource(xop::channel_0, xop::H264Source::CreateNew());
      source = new ZoneMinderFifoSource(rtspServer, session->GetMediaSessionId(), xop::channel_0, fifo);
    } else if (
        std::string::npos != fifo.find("hevc")
        or
        std::string::npos != fifo.find("h265")) {
      rtpFormat = "video/H265";
      session->AddSource(xop::channel_0, xop::H265Source::CreateNew());
      source = new ZoneMinderFifoSource(rtspServer, session->GetMediaSessionId(), xop::channel_0, fifo);
    } else if (std::string::npos != fifo.find("aac")) {
      Debug(1, "ADTS source %p", source);
    } else {
      Warning("Unknown format in %s", fifo.c_str());
    }
    if (source == nullptr) {
      Error("Unable to create source");
    }
    sources.push_back(source);
  } else {
    Debug(1, "Not Adding stream as fifo was empty");
  }
  return source;
}  // end void addFifo

#endif // HAVE_RTSP_SERVER
