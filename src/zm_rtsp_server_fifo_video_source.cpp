/* ---------------------------------------------------------------------------
** This software is in the public domain, furnished "as is", without technical
** support, and with no warranty, express or implied, as to its usefulness for
** any purpose.
**
**
** ZoneMinder Live555 source
**
** -------------------------------------------------------------------------*/

#include "zm_rtsp_server_fifo_video_source.h"

#if HAVE_RTSP_SERVER
ZoneMinderFifoVideoSource::ZoneMinderFifoVideoSource(
  std::shared_ptr<xop::RtspServer>& rtspServer,
  xop::MediaSessionId sessionId,
  xop::MediaChannelId channelId,
  const std::string &fifo
) :
  ZoneMinderFifoSource(rtspServer, sessionId, channelId, fifo),
  m_width(0),
  m_height(0) {
  m_timeBase = {1, 90000};
}

void ZoneMinderFifoVideoSource::PushFrame(const uint8_t *data, size_t size, int64_t pts) {
  xop::AVFrame frame(data, size);
  frame.timestamp = av_rescale_q(pts, AV_TIME_BASE_Q, m_timeBase);
  m_rtspServer->PushFrame(m_sessionId, m_channelId, frame);
}

#endif // HAVE_RTSP_SERVER
