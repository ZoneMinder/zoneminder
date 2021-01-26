/* ---------------------------------------------------------------------------
** This software is in the public domain, furnished "as is", without technical
** support, and with no warranty, express or implied, as to its usefulness for
** any purpose.
**
** ServerMediaSubsession.h
** 
** -------------------------------------------------------------------------*/

#pragma once

#include <sys/stat.h>

#include <string>
#include <iomanip>
#include <iostream>
#include <fstream>

#include <liveMedia.hh>

class ZoneMinderDeviceSource;

class BaseServerMediaSubsession {
	public:
    BaseServerMediaSubsession(StreamReplicator* replicator):
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
        ZoneMinderDeviceSource* source,
        unsigned char rtpPayloadType);
		
	protected:
		StreamReplicator* m_replicator;
};
