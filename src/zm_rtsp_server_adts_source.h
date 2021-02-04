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

#include "zm_config.h"
#include "zm_rtsp_server_device_source.h"

#if HAVE_RTSP_SERVER

#ifndef ADTS_ZoneMinder_DEVICE_SOURCE
#define ADTS_ZoneMinder_DEVICE_SOURCE

// ---------------------------------
// ADTS(AAC) ZoneMinder FramedSource
// ---------------------------------

class ADTS_ZoneMinderDeviceSource : public ZoneMinderDeviceSource {
  public:
		static ADTS_ZoneMinderDeviceSource* createNew(
        UsageEnvironment& env,
        Monitor* monitor,
        AVStream * stream,
        unsigned int queueSize
        ) {
      Debug(1, "m_stream %p codecpar %p channels %d", 
          stream, stream->codecpar, stream->codecpar->channels);
			return new ADTS_ZoneMinderDeviceSource(env, monitor, stream, queueSize);
    };
	protected:
		ADTS_ZoneMinderDeviceSource(
        UsageEnvironment& env,
        Monitor *monitor,
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
      Debug(1, "this %p m_stream %p channels %d", 
          this, m_stream, channels);
      Debug(1, "m_stream %p codecpar %p channels %d => %d", 
          m_stream, m_stream->codecpar, m_stream->codecpar->channels, channels);
      return channels;
      return m_stream->codecpar->channels;
    }

	protected:
    std::string config;
    int samplingFrequencyIndex;
    int channels;
};
#endif
#endif
