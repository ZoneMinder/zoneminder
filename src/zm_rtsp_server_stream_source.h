/* ---------------------------------------------------------------------------
**
** StreamSource.h
**
** RTSP server media source fed from the monitor stream socket
**
** -------------------------------------------------------------------------*/

#ifndef ZM_RTSP_SERVER_STREAM_SOURCE_H
#define ZM_RTSP_SERVER_STREAM_SOURCE_H

#include "zm_config.h"
#include "zm_ffmpeg.h"
#include "zm_define.h"
#include "zm_rtsp_server_frame.h"
#include <list>
#include <string>
#include <thread>
#include <utility>

#if HAVE_RTSP_SERVER
#include "xop/RtspServer.h"

// Packs codec payloads (NAL/OBU split, FU fragmentation) and pushes them to
// the xop RTSP layer. Fed externally via OnPacket() - one complete access
// unit or audio packet per call, as delivered by the monitor stream socket.
class ZoneMinderStreamSource {

 public:

  void Stop() {
    stop_ = true;
    condition_.notify_all();
  };

  ZoneMinderStreamSource(
    std::shared_ptr<xop::RtspServer>& rtspServer,
    xop::MediaSessionId sessionId,
    xop::MediaChannelId channelId
  );
  virtual ~ZoneMinderStreamSource();

  // Split into frames (NALs/OBUs), queue them for the write thread.
  // pts is in AV_TIME_BASE_Q. The data is copied; the caller's buffer is not
  // referenced after return.
  void OnPacket(const uint8_t *data, size_t size, int64_t pts);

 protected:
  void WriteRun();

  virtual void PushFrame(const uint8_t *data, size_t size, int64_t pts) = 0;
  // split packet in frames
  virtual std::list< std::pair<unsigned char*, size_t> > splitFrames(unsigned char* frame, size_t &frameSize);
  virtual unsigned char *extractFrame(unsigned char *data, size_t& size, size_t& outsize);

 protected:

  std::mutex  mutex_;
  std::condition_variable condition_;

  std::thread write_thread_;
  std::atomic<bool> stop_;

  std::shared_ptr<xop::RtspServer> m_rtspServer;
  xop::MediaSessionId m_sessionId;
  xop::MediaChannelId m_channelId;
  AVRational m_timeBase;
  std::queue<NAL_Frame *> m_nalQueue;
  int m_hType;
};
#endif // HAVE_RTSP_SERVER

#endif // ZM_RTSP_SERVER_STREAM_SOURCE_H
