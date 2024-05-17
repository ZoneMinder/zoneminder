/* ---------------------------------------------------------------------------
** This software is in the public domain, furnished "as is", without technical
** support, and with no warranty, express or implied, as to its usefulness for
** any purpose.
**
** ServerMediaSubsession.h
**
** -------------------------------------------------------------------------*/

#ifndef ZM_RTSP_SERVER_UNICAST_SERVER_MEDIA_SUBSESSION_H
#define ZM_RTSP_SERVER_UNICAST_SERVER_MEDIA_SUBSESSION_H

#include "zm_config.h"
#include "zm_rtsp_server_server_media_subsession.h"

// -----------------------------------------
//    ServerMediaSubsession for Unicast
// -----------------------------------------
#if HAVE_RTSP_SERVER
class UnicastServerMediaSubsession :
  public OnDemandServerMediaSubsession,
  public BaseServerMediaSubsession {
 public:
  static UnicastServerMediaSubsession* createNew(
    UsageEnvironment& env,
    StreamReplicator* replicator,
    const std::string& format);

 protected:
  UnicastServerMediaSubsession(
    UsageEnvironment& env,
    StreamReplicator* replicator,
    const std::string& format)
    :
    OnDemandServerMediaSubsession(env, true
                                  /* Boolean reuseFirstSource, portNumBits initialPortNum=6970, Boolean multiplexRTCPWithRTP=False */
                                 ),
    BaseServerMediaSubsession(replicator),
    m_format(format) {};

  virtual FramedSource* createNewStreamSource(unsigned clientSessionId, unsigned& estBitrate);
  virtual RTPSink* createNewRTPSink(Groupsock* rtpGroupsock, unsigned char rtpPayloadTypeIfDynamic, FramedSource* inputSource);
  virtual char const* getAuxSDPLine(RTPSink* rtpSink, FramedSource* inputSource);

 protected:
  const std::string m_format;
};
#endif // HAVE_RTSP_SERVER

#endif // ZM_RTSP_SERVER_UNICAST_SERVER_MEDIA_SUBSESSION_H
