/* ---------------------------------------------------------------------------
** This software is in the public domain, furnished "as is", without technical
** support, and with no warranty, express or implied, as to its usefulness for
** any purpose.
**
** ServerMediaSubsession.h
** 
** -------------------------------------------------------------------------*/

#ifndef ZM_RTSP_SERVER_SERVER_MEDIA_SUBSESSION_H
#define ZM_RTSP_SERVER_SERVER_MEDIA_SUBSESSION_H

#include "zm_config.h"
#include "zm_rtsp_server_fifo_source.h"
#include <string>

#if HAVE_RTSP_SERVER
#include <liveMedia.hh>

class ZoneMinderDeviceSource;

class BaseServerMediaSubsession {
	public:
    explicit BaseServerMediaSubsession(StreamReplicator* replicator):
      m_replicator(replicator) {};

		FramedSource* createSource(
        UsageEnvironment& env,
        FramedSource * videoES,
        const std::string& format);

		RTPSink * createSink(
        UsageEnvironment& env,
        Groupsock * rtpGroupsock,
        unsigned char rtpPayloadTypeIfDynamic,
        const std::string& format,
        FramedSource *source);

		char const* getAuxLine(
        ZoneMinderFifoSource* source,
        unsigned char rtpPayloadType);
		
	protected:
		StreamReplicator* m_replicator;
};
#endif // HAVE_RTSP_SERVER

#endif // ZM_RTSP_SERVER_SERVER_MEDIA_SUBSESSION_H
