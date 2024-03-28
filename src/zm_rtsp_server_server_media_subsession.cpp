/* ---------------------------------------------------------------------------
**
** ServerMediaSubsession.cpp
**
** -------------------------------------------------------------------------*/

#include "zm_rtsp_server_server_media_subsession.h"

#include "zm_config.h"
#include "zm_rtsp_server_adts_source.h"
#include "zm_rtsp_server_adts_fifo_source.h"
#include <sstream>

#if HAVE_RTSP_SERVER
// ---------------------------------
//   BaseServerMediaSubsession
// ---------------------------------
FramedSource* BaseServerMediaSubsession::createSource(
  UsageEnvironment& env,
  FramedSource* inputSource,
  const std::string& format) {
  FramedSource* source = nullptr;
  if (format == "video/MP2T") {
    source = MPEG2TransportStreamFramer::createNew(env, inputSource);
  } else if (format == "video/H264") {
    source = H264VideoStreamDiscreteFramer::createNew(env, inputSource
             /*Boolean includeStartCodeInOutput, Boolean insertAccessUnitDelimiters*/
                                                     );
  }
#if LIVEMEDIA_LIBRARY_VERSION_INT > 1414454400
  else if (format == "video/H265") {
    source = H265VideoStreamDiscreteFramer::createNew(env, inputSource);
  }
#endif
#if 0
  else if (format == "video/JPEG") {
    source = MJPEGVideoSource::createNew(env, inputSource);
  }
#endif
  else {
    source = inputSource;
  }
  return source;
}

/* source is generally a replica */
RTPSink*  BaseServerMediaSubsession::createSink(
  UsageEnvironment& env,
  Groupsock* rtpGroupsock,
  unsigned char rtpPayloadTypeIfDynamic,
  const std::string& format,
  FramedSource *source
) {

  RTPSink* sink = nullptr;
  if (format == "video/MP2T") {
    sink = SimpleRTPSink::createNew(env, rtpGroupsock, rtpPayloadTypeIfDynamic, 90000, "video", "MP2T", 1, true, false);
  } else if (format == "video/H264") {
    sink = H264VideoRTPSink::createNew(env, rtpGroupsock, rtpPayloadTypeIfDynamic);
  } else if (format == "video/VP8") {
    sink = VP8VideoRTPSink::createNew(env, rtpGroupsock, rtpPayloadTypeIfDynamic);
  }
#if LIVEMEDIA_LIBRARY_VERSION_INT > 1414454400
  else if (format == "video/VP9") {
    sink = VP9VideoRTPSink::createNew(env, rtpGroupsock, rtpPayloadTypeIfDynamic);
  } else if (format == "video/H265") {
    sink = H265VideoRTPSink::createNew(env, rtpGroupsock, rtpPayloadTypeIfDynamic);
  }
#endif
  else if (format == "audio/AAC") {
    ADTS_ZoneMinderFifoSource *adts_source = (ADTS_ZoneMinderFifoSource *)(m_replicator->inputSource());
    sink = MPEG4GenericRTPSink::createNew(env, rtpGroupsock,
                                          rtpPayloadTypeIfDynamic,
                                          adts_source->getFrequency(),
                                          "audio", "AAC-hbr",
                                          adts_source->configStr(),
                                          adts_source->getChannels()
                                         );
  } else {
    Error("unknown format");
  }
#if 0
  else if (format == "video/JPEG") {
    sink = JPEGVideoRTPSink::createNew (env, rtpGroupsock);
  }
#endif
  return sink;
}

char const* BaseServerMediaSubsession::getAuxLine(
  ZoneMinderFifoSource* source,
  unsigned char rtpPayloadType
) {
  const char* auxLine = nullptr;
  if (source) {
    std::ostringstream os;
    os << "a=fmtp:" << int(rtpPayloadType) << " ";
    os << source->getAuxLine();
    //os << "\r\n";
    auxLine = strdup(os.str().c_str());
    Debug(1, "BaseServerMediaSubsession::auxLine: %s", auxLine);
  } else {
    Error("No source auxLine:");
    return "";
  }
  return auxLine;
}
#endif // HAVE_RTSP_SERVER
