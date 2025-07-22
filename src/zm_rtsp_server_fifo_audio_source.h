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
  ZoneMinderFifoAudioSource(
    std::shared_ptr<xop::RtspServer>& rtspServer,
    xop::MediaSessionId sessionId,
    xop::MediaChannelId channelId,
    const std::string &fifo
  );

  virtual ~ZoneMinderFifoAudioSource() {}

  void setFrequency(int p_frequency) {
    frequency = p_frequency;
    samplingFrequencyIndex = getFrequencyIndex();
    m_timeBase = {1, frequency};
  };
  int getFrequency() { return frequency; };
  int getFrequencyIndex();
  const char *configStr() const { return config.c_str(); };
  void setChannels(int p_channels) { channels = p_channels; };
  int getChannels() const { return channels; };

 protected:
  void PushFrame(const uint8_t *data, size_t size, int64_t pts) override;

 protected:
  std::string config;
  int samplingFrequencyIndex;
  int frequency;
  int channels;
};
#endif // HAVE_RTSP_SERVER

#endif // ZM_RTSP_SERVER_FIFO_AUDIO_SOURCE_H
