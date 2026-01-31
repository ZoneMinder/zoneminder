/* ---------------------------------------------------------------------------
** This software is in the public domain, furnished "as is", without technical
** support, and with no warranty, express or implied, as to its usefulness for
** any purpose.
**
** zm_rtsp_server_fifo_av1_source.h
**
** AV1 ZoneMinder RTSP source
**
** -------------------------------------------------------------------------*/

#ifndef ZM_RTSP_AV1_FIFO_SOURCE_H
#define ZM_RTSP_AV1_FIFO_SOURCE_H

#include "zm_config.h"
#include "zm_rtsp_server_fifo_video_source.h"

// Forward declaration
namespace xop {
  class AV1Source;
}

#if HAVE_RTSP_SERVER

// AV1 OBU Types (from AV1 specification)
enum AV1_OBU_Type {
  AV1_OBU_SEQUENCE_HEADER = 1,
  AV1_OBU_TEMPORAL_DELIMITER = 2,
  AV1_OBU_FRAME_HEADER = 3,
  AV1_OBU_TILE_GROUP = 4,
  AV1_OBU_METADATA = 5,
  AV1_OBU_FRAME = 6,
  AV1_OBU_REDUNDANT_FRAME_HEADER = 7,
  AV1_OBU_TILE_LIST = 8,
  AV1_OBU_PADDING = 15
};

class AV1_ZoneMinderFifoSource : public ZoneMinderFifoVideoSource {
 public:
  AV1_ZoneMinderFifoSource(
    std::shared_ptr<xop::RtspServer>& rtspServer,
    xop::MediaSessionId sessionId,
    xop::MediaChannelId channelId,
    const std::string &fifo
  );

  virtual ~AV1_ZoneMinderFifoSource() {}

  // Override ZoneMinderFifoSource
  virtual std::list<std::pair<unsigned char*, size_t>>
      splitFrames(unsigned char* frame, size_t &frameSize) override;
  virtual unsigned char* extractFrame(unsigned char* frame,
      size_t& size, size_t& outsize) override;

  void setAV1Source(xop::AV1Source* source) { m_av1Source = source; }

 protected:
  // Parse OBU header and return header size
  // obu_type: output - the OBU type
  // has_size: output - whether OBU has size field
  // Returns: header size in bytes, 0 on error
  size_t parseOBUHeader(unsigned char* data, size_t size,
      uint8_t& obu_type, bool& has_size);

  // Parse LEB128 variable-length integer
  // value: output - the parsed value
  // Returns: number of bytes consumed, 0 on error
  size_t parseLEB128(unsigned char* data, size_t max_size, uint32_t& value);

  std::string m_sequenceHeader;
  xop::AV1Source* m_av1Source = nullptr;
};

#endif // HAVE_RTSP_SERVER

#endif // ZM_RTSP_AV1_FIFO_SOURCE_H
