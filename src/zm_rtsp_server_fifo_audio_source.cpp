/* ---------------------------------------------------------------------------
**
** ADTS_FifoSource.cpp
**
** ADTS Live555 source
**
** -------------------------------------------------------------------------*/

#include "zm_rtsp_server_fifo_audio_source.h"

#if HAVE_RTSP_SERVER

static const int samplingFrequencyTable[16] = {
  96000, 88200, 64000, 48000,
  44100, 32000, 24000, 22050,
  16000, 12000, 11025, 8000,
  7350, 0, 0, 0
};
// ---------------------------------
// ADTS ZoneMinder FramedSource
// ---------------------------------
//
ZoneMinderFifoAudioSource::ZoneMinderFifoAudioSource(
  std::shared_ptr<xop::RtspServer>& rtspServer,
  xop::MediaSessionId sessionId,
  xop::MediaChannelId channelId,
  const std::string &fifo
)
  :
  ZoneMinderFifoSource(rtspServer, sessionId, channelId, fifo),
  samplingFrequencyIndex(-1),
  frequency(-1),
  channels(1) {
}
int ZoneMinderFifoAudioSource::getFrequencyIndex() {
  for (int i=0; i<16; i++)
    if (samplingFrequencyTable[i] == frequency) return i;
  return -1;
}

void ZoneMinderFifoAudioSource::PushFrame(const uint8_t *data, size_t size, int64_t pts) {
  xop::AVFrame frame(data, size);
  frame.type = xop::AUDIO_FRAME;
  frame.timestamp = av_rescale_q(pts, AV_TIME_BASE_Q, m_timeBase);
  m_rtspServer->PushFrame(m_sessionId, m_channelId, frame);
}
#endif // HAVE_RTSP_SERVER
