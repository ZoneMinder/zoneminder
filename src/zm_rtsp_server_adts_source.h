/* ---------------------------------------------------------------------------
** This software is in the public domain, furnished "as is", without technical
** support, and with no warranty, express or implied, as to its usefulness for
** any purpose.
**
** ADTS_ZoneMinderDeviceSource.h
**
** ADTS ZoneMinder live555 source
**
** -------------------------------------------------------------------------*/

#ifndef ZM_RTSP_SERVER_ADTS_SOURCE_H
#define ZM_RTSP_SERVER_ADTS_SOURCE_H

#include "zm_config.h"
#include "zm_rtsp_server_device_source.h"

#if HAVE_RTSP_SERVER
// ---------------------------------
// ADTS(AAC) ZoneMinder FramedSource
// ---------------------------------

class ADTS_ZoneMinderDeviceSource : public ZoneMinderDeviceSource {
 public:
  static ADTS_ZoneMinderDeviceSource* createNew(
    UsageEnvironment& env,
    std::shared_ptr<Monitor> monitor,
    AVStream * stream,
    unsigned int queueSize
  ) {
    return new ADTS_ZoneMinderDeviceSource(env, std::move(monitor), stream, queueSize);
  };
 protected:
  ADTS_ZoneMinderDeviceSource(
    UsageEnvironment& env,
    std::shared_ptr<Monitor> monitor,
    AVStream *stream,
    unsigned int queueSize
  );

  virtual ~ADTS_ZoneMinderDeviceSource() {}

  /*
  virtual unsigned char* extractFrame(unsigned char* frame, size_t& size, size_t& outsize);
  virtual unsigned char* findMarker(unsigned char *frame, size_t size, size_t &length);
  */
 public:
  int samplingFrequency() { return m_stream->codecpar->sample_rate; };
  const char *configStr() { return config.c_str(); };
  int numChannels() {
    return channels;
  }

 protected:
  std::string config;
  int samplingFrequencyIndex;
  int channels;
};
#endif // HAVE_RTSP_SERVER

#endif // ZM_RTSP_SERVER_ADTS_SOURCE_H
