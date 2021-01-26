/* ---------------------------------------------------------------------------
** This software is in the public domain, furnished "as is", without technical
** support, and with no warranty, express or implied, as to its usefulness for
** any purpose.
**
** ServerMediaSubsession.h
**
** -------------------------------------------------------------------------*/

#pragma once

#include "zm_rtsp_server_server_media_subsession.h"

// -----------------------------------------
//    ServerMediaSubsession for Unicast
// -----------------------------------------
class UnicastServerMediaSubsession :
  public OnDemandServerMediaSubsession,
  public BaseServerMediaSubsession
{
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
