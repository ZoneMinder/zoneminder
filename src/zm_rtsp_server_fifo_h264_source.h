/* ---------------------------------------------------------------------------
** This software is in the public domain, furnished "as is", without technical
** support, and with no warranty, express or implied, as to its usefulness for
** any purpose.
**
** H264_ZoneMinderFifoSource.h
**
** H264 ZoneMinder live555 source
**
** -------------------------------------------------------------------------*/

#ifndef ZM_RTSP_H264_FIFO_SOURCE_H
#define ZM_RTSP_H264_FIFO_SOURCE_H

#include "zm_config.h"
#include "zm_rtsp_server_fifo_video_source.h"

// ---------------------------------
// H264 ZoneMinder FramedSource
// ---------------------------------
#if HAVE_RTSP_SERVER
class H26X_ZoneMinderFifoSource : public ZoneMinderFifoVideoSource {
 public:
  H26X_ZoneMinderFifoSource(
    std::shared_ptr<xop::RtspServer>& rtspServer,
    xop::MediaSessionId sessionId,
    xop::MediaChannelId channelId,
    const std::string &fifo
  )
    :
    ZoneMinderFifoVideoSource(rtspServer, sessionId, channelId, fifo),
    m_keepMarker(false),
    m_frameType(0) { }

  virtual ~H26X_ZoneMinderFifoSource() {}

  virtual unsigned char* extractFrame(unsigned char* frame, size_t& size, size_t& outsize) override;
  virtual unsigned char* findMarker(unsigned char *frame, size_t size, size_t &length);

 protected:
  std::string m_sps;
  std::string m_pps;
  bool        m_keepMarker;
  int         m_frameType;
};

class H264_ZoneMinderFifoSource : public H26X_ZoneMinderFifoSource {
 public:
  H264_ZoneMinderFifoSource(
    std::shared_ptr<xop::RtspServer>& rtspServer,
    xop::MediaSessionId sessionId,
    xop::MediaChannelId channelId,
    const std::string &fifo
  );

  // override ZoneMinderFifoSource
  virtual std::list< std::pair<unsigned char*,size_t> > splitFrames(unsigned char* frame, size_t &frameSize) override;
};

class H265_ZoneMinderFifoSource : public H26X_ZoneMinderFifoSource {
 public:
  H265_ZoneMinderFifoSource(
    std::shared_ptr<xop::RtspServer>& rtspServer,
    xop::MediaSessionId sessionId,
    xop::MediaChannelId channelId,
    const std::string &fifo
  );

  // override ZoneMinderFifoSource
  virtual std::list< std::pair<unsigned char*,size_t> > splitFrames(unsigned char* frame, size_t &frameSize) override;

 protected:
  std::string m_vps;
};
#endif // HAVE_RTSP_SERVER

#endif // ZM_RTSP_H264_FIFO_SOURCE_H
