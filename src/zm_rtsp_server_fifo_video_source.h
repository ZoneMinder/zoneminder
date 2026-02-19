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

// Forward declarations
namespace xop {
  class H264Source;
  class H265Source;
  class AV1Source;
}

#if HAVE_RTSP_SERVER

class ZoneMinderFifoVideoSource: public ZoneMinderFifoSource {

 public:
  int getWidth() { return m_width; };
  int getHeight() { return m_height; };
  int setWidth(int width) { return m_width=width; };
  int setHeight(int height) { return m_height=height; };

  // Set xop sources for SPS/PPS/sequence header updates
  void setH264Source(xop::H264Source *source) { m_h264Source = source; }
  void setH265Source(xop::H265Source *source) { m_h265Source = source; }
  void setAV1Source(xop::AV1Source *source) { m_av1Source = source; }

  ZoneMinderFifoVideoSource(
    std::shared_ptr<xop::RtspServer>& rtspServer,
    xop::MediaSessionId sessionId,
    xop::MediaChannelId channelId,
    const std::string &fifo
  );
 protected:
  void PushFrame(const uint8_t *data, size_t size, int64_t pts, uint8_t last = 1) override;

 protected:
  int m_width;
  int m_height;
  xop::H264Source *m_h264Source = nullptr;
  xop::H265Source *m_h265Source = nullptr;
  xop::AV1Source *m_av1Source = nullptr;
};
#endif // HAVE_RTSP_SERVER

#endif // ZM_RTSP_SERVER_FIFO_VIDEO_SOURCE_H
