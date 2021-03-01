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

#ifndef ZM_RTSP_SERVER_FIFO_AUDIO_SOURCE_H
#define ZM_RTSP_SERVER_FIFO_AUDIO_SOURCE_H

#include "zm_config.h"
#include "zm_rtsp_server_fifo_source.h"

#if HAVE_RTSP_SERVER
// ---------------------------------
// ZoneMinder AUDIO FramedSource
// ---------------------------------

class ZoneMinderFifoAudioSource : public ZoneMinderFifoSource {
  public:
		static ZoneMinderFifoAudioSource* createNew(
        UsageEnvironment& env,
        std::string fifo,
        unsigned int queueSize
        ) {
			return new ZoneMinderFifoAudioSource(env, fifo, queueSize);
    };
	protected:
		ZoneMinderFifoAudioSource(
        UsageEnvironment& env,
        std::string fifo,
        unsigned int queueSize
        );

		virtual ~ZoneMinderFifoAudioSource() {}
  public:

    void setFrequency(int p_frequency) { frequency = p_frequency; };
    int getFrequency() { return frequency; };
    const char *configStr() const { return config.c_str(); };
    void setChannels(int p_channels) { channels = p_channels; };
    int getChannels() const { return channels; };

	protected:
    std::string config;
    int samplingFrequencyIndex;
    int frequency;
    int channels;
};
#endif // HAVE_RTSP_SERVER

#endif // ZM_RTSP_SERVER_FIFO_AUDIO_SOURCE_H
