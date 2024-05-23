/* ---------------------------------------------------------------------------
** This software is in the public domain, furnished "as is", without technical
** support, and with no warranty, express or implied, as to its usefulness for
** any purpose.
**
** ServerMediaSubsession.cpp
**
** -------------------------------------------------------------------------*/

#include "zm_rtsp_server_unicast_server_media_subsession.h"

#include "zm_config.h"
#include "zm_rtsp_server_fifo_source.h"

#if HAVE_RTSP_SERVER
// -----------------------------------------
//    ServerMediaSubsession for Unicast
// -----------------------------------------
UnicastServerMediaSubsession* UnicastServerMediaSubsession::createNew(
  UsageEnvironment& env,
  StreamReplicator* replicator,
  //FramedSource *source,
  const std::string& format
) {
  return new UnicastServerMediaSubsession(env, replicator, format);
  //return new UnicastServerMediaSubsession(env, replicator, source, format);
}

FramedSource* UnicastServerMediaSubsession::createNewStreamSource(
  unsigned clientSessionId,
  unsigned& estBitrate
) {
  FramedSource* replica = m_replicator->createStreamReplica();
  return createSource(envir(), replica, m_format);
}

RTPSink* UnicastServerMediaSubsession::createNewRTPSink(
  Groupsock* rtpGroupsock,
  unsigned char rtpPayloadTypeIfDynamic,
  FramedSource* inputSource
) {
  return createSink(envir(), rtpGroupsock, rtpPayloadTypeIfDynamic, m_format, inputSource);
}

char const* UnicastServerMediaSubsession::getAuxSDPLine(
  RTPSink* rtpSink, FramedSource* inputSource
) {
  return this->getAuxLine(
           dynamic_cast<ZoneMinderFifoSource*>(m_replicator->inputSource()),
           rtpSink->rtpPayloadType());
}
#endif // HAVE_RTSP_SERVER
