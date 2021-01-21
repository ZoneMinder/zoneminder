#include "zm_rtsp_server_thread.h"
#include "zm_rtsp_server_device_source.h"
#include "zm_rtsp_server_h264_device_source.h"
#include "zm_rtsp_server_unicast_server_media_subsession.h"
#include <StreamReplicator.hh>

#if HAVE_RTSP_SERVER
#define ZM_RTSP_SERVER_BASE 10000

RTSPServerThread::RTSPServerThread(Monitor *p_monitor) : 
  monitor(p_monitor),
  terminate(0)
{
  //unsigned short rtsp_over_http_port = 0;
  //const char *realm = "ZoneMinder";
  //unsigned int timeout = 65;
  OutPacketBuffer::maxSize = 2000000;

  scheduler = BasicTaskScheduler::createNew();
  env = BasicUsageEnvironment::createNew(*scheduler);
  authDB = nullptr;
  //authDB = new UserAuthenticationDatabase("ZoneMinder");
  //authDB->addUserRecord("username1", "password1"); // replace these with real strings

  portNumBits rtspServerPortNum = ZM_RTSP_SERVER_BASE + monitor->Id();
  rtspServer = RTSPServer::createNew(*env, rtspServerPortNum, authDB);

  if ( rtspServer == nullptr ) {
    Fatal("Failed to create rtspServer at port %d", rtspServerPortNum);
    return;
  }
  const char *prefix = rtspServer->rtspURLPrefix();
  Debug(1, "RTSP prefix is %s", prefix);
  delete[] prefix;
}  // end RTSPServerThread::RTSPServerThread

RTSPServerThread::~RTSPServerThread() {
  if ( rtspServer ) {
    Medium::close(rtspServer);
  } // end if rtsp_server
  while ( sources.size() ) {
    FramedSource *source = sources.front();
    sources.pop_front();
    Medium::close(source);
  }
  env->reclaim();
  delete scheduler;
}

int RTSPServerThread::run() {
  Debug(1, "RTSPServerThread::run()");
  if ( rtspServer )
    env->taskScheduler().doEventLoop(&terminate); // does not return
  Debug(1, "RTSPServerThread::done()");
  return 0;
}  // end in RTSPServerThread::run()

void RTSPServerThread::stop() {
  Debug(1, "RTSPServerThread::stop()");
  terminate = 1;
}  // end RTSPServerThread::stop()

bool RTSPServerThread::stopped() const {
  return terminate ? true : false;
}  // end RTSPServerThread::stopped()

void RTSPServerThread::addStream(AVStream *stream, AVStream *audio_stream) {
  if ( !rtspServer )
    return;

  AVCodecID codec_id = stream->codecpar->codec_id;
  std::string rtpFormat = getRtpFormat(codec_id, false);
  Debug(1, "RTSP: format %s", rtpFormat.c_str());
  if ( rtpFormat.empty() ) {
    Error("No streaming format");
    return;
  } 

  int queueSize = 30;
  bool repeatConfig = true;
  bool muxTS = false;
  ServerMediaSession *sms = nullptr;

  if ( stream ) {
    StreamReplicator* videoReplicator = nullptr;
    FramedSource *source = nullptr;
    if ( rtpFormat == "video/H264" ) {
      source = H264_ZoneMinderDeviceSource::createNew(*env, monitor, stream, queueSize, repeatConfig, muxTS);
    } else if ( rtpFormat == "video/H265" ) {
      source = H265_ZoneMinderDeviceSource::createNew(*env, monitor, stream, queueSize, repeatConfig, muxTS);
    }
    if ( source == nullptr ) {
      //LOG(ERROR) << "Unable to create source for device " << camera_name.c_str() << std::endl;
      Error("Unable to create source");
    } else {
      videoReplicator = StreamReplicator::createNew(*env, source, false);
    }
    sources.push_back(source);

    // Create Unicast Session
    //std::list<ServerMediaSubsession*> subSessions;
    if ( videoReplicator ) {
      if ( !sms )
        sms = ServerMediaSession::createNew(*env, "streamname");
      //sms->addSubsession(ServerMediaSubsession::createNew(*env, videoReplicator, rtpFormat));
      sms->addSubsession(UnicastServerMediaSubsession::createNew(*env, videoReplicator, rtpFormat));
    }
  }
  if ( audio_stream ) {
    StreamReplicator* replicator = nullptr;
    FramedSource *source = nullptr;
    rtpFormat = getRtpFormat(audio_stream->codecpar->codec_id, false);
    if ( rtpFormat == "audio/AAC" ) {
      //source = AAC_ZoneMinderDeviceSource::createNew(*env, monitor, stream->index, queueSize, repeatConfig, muxTS);
    }
    if ( source ) {
      replicator = StreamReplicator::createNew(*env, source, false);
      sources.push_back(source);
    }
    if ( replicator ) {
      if ( !sms )
        sms = ServerMediaSession::createNew(*env, "streamname");
      sms->addSubsession(UnicastServerMediaSubsession::createNew(*env, replicator, rtpFormat));
    }
  }
  rtspServer->addServerMediaSession(sms);
  char *url = rtspServer->rtspURL(sms);
  Debug(1, "url is %s", url);
  *env << "ZoneMinder Media Server at " << url << "\n";
  delete[] url;
}  // end void addStream

int RTSPServerThread::addSession(
    const std::string & sessionName,
    const std::list<ServerMediaSubsession*> & subSession
    ) {
  int nbSubsession = 0;
  if ( subSession.empty() == false ) {
    UsageEnvironment& env(rtspServer->envir());
    ServerMediaSession* sms = ServerMediaSession::createNew(env, sessionName.c_str());
    if ( sms != nullptr ) {
      std::list<ServerMediaSubsession*>::const_iterator subIt;
      for ( subIt = subSession.begin(); subIt != subSession.end(); ++subIt ) {
        sms->addSubsession(*subIt);
        nbSubsession ++;
      }

      rtspServer->addServerMediaSession(sms);

      char* url = rtspServer->rtspURL(sms);
      if ( url != nullptr ) {
        Info("Play this stream using the URL %s", url);
        delete[] url;
        url = nullptr;
      }
    }  // end if sms
  }  // end if subSession
  return nbSubsession;
}

// -----------------------------------------
//    convert V4L2 pix format to RTP mime
// -----------------------------------------
const std::string RTSPServerThread::getRtpFormat(AVCodecID codec_id, bool muxTS) {
  if ( muxTS ) {
    return "video/MP2T";
  } else {
    switch ( codec_id ) {
      case AV_CODEC_ID_H265 : return "video/H265";
      case AV_CODEC_ID_H264 : return "video/H264";
      //case PIX_FMT_MJPEG: rtpFormat = "video/JPEG"; break;
      //case PIX_FMT_JPEG : rtpFormat = "video/JPEG"; break;
      //case AV_PIX_FMT_VP8  : rtpFormat = "video/VP8" ; break;
      //case AV_PIX_FMT_VP9  : rtpFormat = "video/VP9" ; break;
      case AV_CODEC_ID_AAC : return "audio/AAC";
      default: break;
    }
  }

  return "";
}
#endif
