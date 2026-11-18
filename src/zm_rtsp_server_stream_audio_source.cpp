/* ---------------------------------------------------------------------------
**
** ADTS_FifoSource.cpp
**
** ADTS Live555 source
**
** -------------------------------------------------------------------------*/

#include "zm_rtsp_server_stream_audio_source.h"

#include <chrono>

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
ZoneMinderStreamAudioSource::ZoneMinderStreamAudioSource(
  std::shared_ptr<xop::RtspServer>& rtspServer,
  xop::MediaSessionId sessionId,
  xop::MediaChannelId channelId
)
  :
  ZoneMinderStreamSource(rtspServer, sessionId, channelId),
  samplingFrequencyIndex(-1),
  frequency(-1),
  channels(1) {
}
int ZoneMinderStreamAudioSource::getFrequencyIndex() {
  for (int i=0; i<16; i++)
    if (samplingFrequencyTable[i] == frequency) return i;
  return -1;
}

int64_t ZoneMinderStreamAudioSource::RtpTimestamp(int64_t pts, AVRational timeBase) {
  if (pts == AV_NOPTS_VALUE) {
    // No source timestamp: follow the wall clock instead of sending the same
    // RTP timestamp for every packet
    pts = std::chrono::duration_cast<std::chrono::microseconds>(
            std::chrono::steady_clock::now().time_since_epoch()).count();
  }
  return av_rescale_q(pts, AV_TIME_BASE_Q, timeBase);
}

void ZoneMinderStreamAudioSource::PushFrame(const uint8_t *data, size_t size, int64_t pts, bool last) {
  xop::AVFrame frame(data, size);
  frame.type = xop::AUDIO_FRAME;
  frame.timestamp = RtpTimestamp(pts, m_timeBase);
  m_rtspServer->PushFrame(m_sessionId, m_channelId, frame);
}
#endif // HAVE_RTSP_SERVER
