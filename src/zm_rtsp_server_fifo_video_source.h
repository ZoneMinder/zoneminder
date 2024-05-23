/* ---------------------------------------------------------------------------
**
** FifoSource.h
**
**  live555 source
**
** -------------------------------------------------------------------------*/

#ifndef ZM_RTSP_SERVER_FIFO_VIDEO_SOURCE_H
#define ZM_RTSP_SERVER_FIFO_VIDEO_SOURCE_H

#include "zm_rtsp_server_fifo_source.h"

#if HAVE_RTSP_SERVER

class ZoneMinderFifoVideoSource: public ZoneMinderFifoSource {

 public:
  int getWidth() { return m_width; };
  int getHeight() { return m_height; };
  int setWidth(int width) { return m_width=width; };
  int setHeight(int height) { return m_height=height; };

  ZoneMinderFifoVideoSource(
    std::shared_ptr<xop::RtspServer>& rtspServer,
    xop::MediaSessionId sessionId,
    xop::MediaChannelId channelId,
    const std::string &fifo
  );
 protected:
  void PushFrame(const uint8_t *data, size_t size, int64_t pts) override;

 protected:
  int m_width;
  int m_height;
};
#endif // HAVE_RTSP_SERVER

#endif // ZM_RTSP_SERVER_FIFO_VIDEO_SOURCE_H
