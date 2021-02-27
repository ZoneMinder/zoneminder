/* ---------------------------------------------------------------------------
** This software is in the public domain, furnished "as is", without technical
** support, and with no warranty, express or implied, as to its usefulness for
** any purpose.
**
** ADTS_ZoneMinderFifoSource.h
**
** ADTS ZoneMinder live555 source
**
** -------------------------------------------------------------------------*/

#ifndef ZM_RTSP_SERVER_ADTS_FIFO_SOURCE_H
#define ZM_RTSP_SERVER_ADTS_FIFO_SOURCE_H

#include "zm_config.h"
#include "zm_rtsp_server_fifo_source.h"

#if HAVE_RTSP_SERVER
// ---------------------------------
// ADTS(AAC) ZoneMinder FramedSource
// ---------------------------------

class ADTS_ZoneMinderFifoSource : public ZoneMinderFifoSource {
  public:
		static ADTS_ZoneMinderFifoSource* createNew(
        UsageEnvironment& env,
        std::string fifo,
        unsigned int queueSize
        ) {
			return new ADTS_ZoneMinderFifoSource(env, fifo, queueSize);
    };
	protected:
		ADTS_ZoneMinderFifoSource(
        UsageEnvironment& env,
        std::string fifo,
        unsigned int queueSize
        );

		virtual ~ADTS_ZoneMinderFifoSource() {}

    /*
		virtual unsigned char* extractFrame(unsigned char* frame, size_t& size, size_t& outsize);
    virtual unsigned char* findMarker(unsigned char *frame, size_t size, size_t &length);
    */
  public:
    int samplingFrequency() { return 8000; //m_stream->codecpar->sample_rate;
    };
    const char *configStr() { return config.c_str(); };
    int numChannels() {
      //Debug(1, "this %p m_stream %p channels %d", 
          //this, m_stream, channels);
      //Debug(1, "m_stream %p codecpar %p channels %d => %d", 
          //m_stream, m_stream->codecpar, m_stream->codecpar->channels, channels);
      return 1;
      //return channels;
      //return m_stream->codecpar->channels;
    }

	protected:
    std::string config;
    int samplingFrequencyIndex;
    int channels;
};
#endif // HAVE_RTSP_SERVER

#endif // ZM_RTSP_SERVER_ADTS_FIFO_SOURCE_H
