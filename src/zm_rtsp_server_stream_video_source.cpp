/* ---------------------------------------------------------------------------
** This software is in the public domain, furnished "as is", without technical
** support, and with no warranty, express or implied, as to its usefulness for
** any purpose.
**
**
** ZoneMinder Live555 source
**
** -------------------------------------------------------------------------*/

#include "zm_rtsp_server_stream_video_source.h"

#if HAVE_RTSP_SERVER
ZoneMinderStreamVideoSource::ZoneMinderStreamVideoSource(
  std::shared_ptr<xop::RtspServer>& rtspServer,
  xop::MediaSessionId sessionId,
  xop::MediaChannelId channelId
) :
  ZoneMinderStreamSource(rtspServer, sessionId, channelId),
  m_width(0),
  m_height(0) {
  m_timeBase = {1, 90000};
}

bool ZoneMinderStreamVideoSource::StartsKeyframe(int hType, const uint8_t *data, size_t size) {
  if (hType == 264 and size >= 1) {
    int type = data[0] & 0x1F;
    if (type == 28)  // FU-A: only the first fragment of an IDR starts one
      return size >= 2 and (data[1] & 0x80) and (data[1] & 0x1F) == 5;
    return type == 5 or type == 7 or type == 8;
  }
  if (hType == 265 and size >= 2) {
    int type = (data[0] >> 1) & 0x3F;
    if (type == 49)  // FU: only the first fragment of an IRAP picture starts one
      return size >= 3 and (data[2] & 0x80) and (data[2] & 0x3F) >= 16 and (data[2] & 0x3F) <= 21;
    return (type >= 16 and type <= 21) or (type >= 32 and type <= 34);
  }
  return false;
}

void ZoneMinderStreamVideoSource::PushFrame(const uint8_t *data, size_t size, int64_t pts, bool last) {
  xop::AVFrame frame(data, size);
  frame.last = last;
  // xop starts a client at the first packet typed I (or untyped), so type
  // everything else P or a client that joins mid-GOP is fed undecodable slices
  if (m_hType == 264 or m_hType == 265)
    frame.type = StartsKeyframe(m_hType, data, size) ? xop::VIDEO_FRAME_I : xop::VIDEO_FRAME_P;
  frame.timestamp = av_rescale_q(pts, AV_TIME_BASE_Q, m_timeBase);
  m_rtspServer->PushFrame(m_sessionId, m_channelId, frame);
}

#endif // HAVE_RTSP_SERVER
