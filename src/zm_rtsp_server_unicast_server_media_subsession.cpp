/* ---------------------------------------------------------------------------
** This software is in the public domain, furnished "as is", without technical
** support, and with no warranty, express or implied, as to its usefulness for
** any purpose.
**
** ServerMediaSubsession.cpp
**
** -------------------------------------------------------------------------*/


#include "zm_rtsp_server_unicast_server_media_subsession.h"
#include "zm_rtsp_server_device_source.h"

// -----------------------------------------
//    ServerMediaSubsession for Unicast
// -----------------------------------------
UnicastServerMediaSubsession* UnicastServerMediaSubsession::createNew(
    UsageEnvironment& env, StreamReplicator* replicator, const std::string& format)
{
	return new UnicastServerMediaSubsession(env, replicator, format);
}

FramedSource* UnicastServerMediaSubsession::createNewStreamSource(unsigned clientSessionId, unsigned& estBitrate)
{
	FramedSource* source = m_replicator->createStreamReplica();
	return createSource(envir(), source, m_format);
}

RTPSink* UnicastServerMediaSubsession::createNewRTPSink(
    Groupsock* rtpGroupsock,  unsigned char rtpPayloadTypeIfDynamic, FramedSource* inputSource)
{
	return createSink(envir(), rtpGroupsock, rtpPayloadTypeIfDynamic, m_format);
}

char const* UnicastServerMediaSubsession::getAuxSDPLine(RTPSink* rtpSink, FramedSource* inputSource)
{
	return this->getAuxLine(dynamic_cast<ZoneMinderDeviceSource*>(m_replicator->inputSource()), rtpSink->rtpPayloadType());
}
