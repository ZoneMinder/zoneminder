/* ---------------------------------------------------------------------------
**
** ServerMediaSubsession.cpp
** 
** -------------------------------------------------------------------------*/

#include <sstream>

#include "zm_rtsp_server_server_media_subsession.h"
#include "zm_rtsp_server_device_source.h"

// ---------------------------------
//   BaseServerMediaSubsession
// ---------------------------------
FramedSource* BaseServerMediaSubsession::createSource(
    UsageEnvironment& env, FramedSource* videoES, const std::string& format)
{
	FramedSource* source = nullptr;
	if ( format == "video/MP2T" ) {
		source = MPEG2TransportStreamFramer::createNew(env, videoES); 
	} else if ( format == "video/H264" ) {
		source = H264VideoStreamDiscreteFramer::createNew(env, videoES);
	}
#if LIVEMEDIA_LIBRARY_VERSION_INT > 1414454400
	else if ( format == "video/H265" ) {
		source = H265VideoStreamDiscreteFramer::createNew(env, videoES);
	}
#endif
#if 0
	else if (format == "video/JPEG") {
		source = MJPEGVideoSource::createNew(env, videoES);
	}
#endif
	else {
		source = videoES;
	}
  Error("Source %p %s", source, format.c_str());
	return source;
}

RTPSink*  BaseServerMediaSubsession::createSink(
    UsageEnvironment& env,
    Groupsock* rtpGroupsock,
    unsigned char rtpPayloadTypeIfDynamic,
    const std::string& format
    ) {
	RTPSink* videoSink = nullptr;
	if ( format == "video/MP2T" ) {
		videoSink = SimpleRTPSink::createNew(env, rtpGroupsock, rtpPayloadTypeIfDynamic, 90000, "video", "MP2T", 1, True, False); 
	} else if ( format == "video/H264" ) {
		videoSink = H264VideoRTPSink::createNew(env, rtpGroupsock,rtpPayloadTypeIfDynamic);
	} else if ( format == "video/VP8" ) {
		videoSink = VP8VideoRTPSink::createNew (env, rtpGroupsock,rtpPayloadTypeIfDynamic); 
	}
#if LIVEMEDIA_LIBRARY_VERSION_INT > 1414454400
	else if ( format == "video/VP9" ) {
		videoSink = VP9VideoRTPSink::createNew (env, rtpGroupsock,rtpPayloadTypeIfDynamic); 
	} else if ( format == "video/H265" ) {
		videoSink = H265VideoRTPSink::createNew(env, rtpGroupsock,rtpPayloadTypeIfDynamic);
#endif	
  } else {
    std::cerr << "unknown format\n";
	}
#if 0
	else if (format == "video/JPEG") {
		videoSink = JPEGVideoRTPSink::createNew (env, rtpGroupsock); 
	}
#endif
  Error("Sinkce %p %s", videoSink, format.c_str());
	return videoSink;
}

char const* BaseServerMediaSubsession::getAuxLine(
    ZoneMinderDeviceSource* source,
    unsigned char rtpPayloadType
    ) {
	const char* auxLine = nullptr;
	if ( source ) {
		std::ostringstream os; 
		os << "a=fmtp:" << int(rtpPayloadType) << " ";				
		os << source->getAuxLine();				
		os << "\r\n";		
		int width = source->getWidth();
		int height = source->getHeight();
		if ( (width > 0) && (height>0) ) {
			os << "a=x-dimensions:" << width << "," <<  height  << "\r\n";				
		}
		auxLine = strdup(os.str().c_str());
    Error( "auxLine: %s", auxLine);
  } else {
  Error( "No source auxLine: ");
	} 
	return auxLine;
}
