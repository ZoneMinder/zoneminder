/* ---------------------------------------------------------------------------
**
** zm_rtsp_server_fifo_av1_source.cpp
**
** AV1 ZoneMinder RTSP source
**
** -------------------------------------------------------------------------*/

#include "zm_rtsp_server_fifo_av1_source.h"

#include "zm_config.h"
#include "zm_logger.h"
#include "xop/AV1Source.h"

#if HAVE_RTSP_SERVER

AV1_ZoneMinderFifoSource::AV1_ZoneMinderFifoSource(
  std::shared_ptr<xop::RtspServer>& rtspServer,
  xop::MediaSessionId sessionId,
  xop::MediaChannelId channelId,
  const std::string &fifo
)
  : ZoneMinderFifoVideoSource(rtspServer, sessionId, channelId, fifo) {
  m_hType = 1;  // AV1 identifier
}

// Parse LEB128 variable-length integer (used for OBU sizes in AV1)
size_t AV1_ZoneMinderFifoSource::parseLEB128(
    unsigned char* data, size_t max_size, uint32_t& value) {
  value = 0;
  size_t bytes_read = 0;

  for (size_t i = 0; i < max_size && i < 8; i++) {
    uint8_t byte = data[i];
    value |= ((byte & 0x7F) << (7 * i));
    bytes_read++;

    if ((byte & 0x80) == 0) {
      // Last byte of LEB128
      break;
    }
  }

  return bytes_read;
}

// Parse OBU header
// Returns header size (1 or 2 bytes), 0 on error
size_t AV1_ZoneMinderFifoSource::parseOBUHeader(
    unsigned char* data, size_t size, uint8_t& obu_type, bool& has_size) {
  if (size < 1) {
    return 0;
  }

  // OBU header byte:
  // - obu_forbidden_bit (1 bit) - must be 0
  // - obu_type (4 bits)
  // - obu_extension_flag (1 bit)
  // - obu_has_size_field (1 bit)
  // - obu_reserved_1bit (1 bit)

  uint8_t header = data[0];

  if (header & 0x80) {
    // Forbidden bit is set - invalid
    Debug(1, "AV1: OBU forbidden bit set");
    return 0;
  }

  obu_type = (header >> 3) & 0x0F;
  bool has_extension = (header >> 2) & 0x01;
  has_size = (header >> 1) & 0x01;

  size_t header_size = 1;

  if (has_extension) {
    if (size < 2) {
      return 0;
    }
    // Extension byte contains temporal_id and spatial_id
    header_size = 2;
  }

  return header_size;
}

// Extract a single OBU from the buffer
unsigned char* AV1_ZoneMinderFifoSource::extractFrame(
    unsigned char* frame, size_t& size, size_t& outsize) {
  outsize = 0;

  if (size < 1) {
    return nullptr;
  }

  uint8_t obu_type;
  bool has_size;

  size_t header_size = parseOBUHeader(frame, size, obu_type, has_size);
  if (header_size == 0) {
    Debug(1, "AV1: Failed to parse OBU header");
    return nullptr;
  }

  size_t obu_size;

  if (has_size) {
    // Parse LEB128 size field after header
    if (size < header_size + 1) {
      return nullptr;
    }

    uint32_t leb_value;
    size_t leb_size = parseLEB128(frame + header_size, size - header_size, leb_value);
    if (leb_size == 0) {
      Debug(1, "AV1: Failed to parse LEB128 size");
      return nullptr;
    }

    obu_size = header_size + leb_size + leb_value;
  } else {
    // No size field - OBU extends to end of buffer
    // This is typically only used for the last OBU in a temporal unit
    obu_size = size;
  }

  if (obu_size > size) {
    Debug(1, "AV1: OBU size %zu exceeds buffer size %zu", obu_size, size);
    return nullptr;
  }

  outsize = obu_size;
  size -= obu_size;

  Debug(4, "AV1: Extracted OBU type=%u size=%zu remaining=%zu", obu_type, outsize, size);

  return frame;
}

// Split buffer into individual OBUs
std::list<std::pair<unsigned char*, size_t>>
AV1_ZoneMinderFifoSource::splitFrames(unsigned char* frame, size_t &frameSize) {
  std::list<std::pair<unsigned char*, size_t>> frameList;

  size_t bufSize = frameSize;
  size_t size = 0;
  unsigned char* buffer = this->extractFrame(frame, bufSize, size);

  while (buffer != nullptr && size > 0) {
    // Parse OBU type from this OBU
    uint8_t obu_type;
    bool has_size;
    size_t header_size = parseOBUHeader(buffer, size, obu_type, has_size);

    if (header_size > 0) {
      switch (obu_type) {
        case AV1_OBU_SEQUENCE_HEADER:
          Debug(4, "AV1 Sequence Header: size=%zu", size);
          m_sequenceHeader.assign((char*)buffer, size);
          if (m_av1Source && !m_sequenceHeader.empty()) {
            // Extract just the sequence header payload (skip OBU header and size)
            size_t payload_offset = header_size;
            if (has_size) {
              uint32_t obu_payload_size;
              size_t leb_size = parseLEB128(buffer + header_size, size - header_size, obu_payload_size);
              payload_offset += leb_size;
            }
            if (payload_offset < size) {
              m_av1Source->SetSequenceHeader(
                  (const uint8_t*)(buffer + payload_offset),
                  size - payload_offset);
              Debug(2, "AV1: Set sequence header on xop source (%zu bytes)",
                    size - payload_offset);
            }
          }
          break;

        case AV1_OBU_TEMPORAL_DELIMITER:
          Debug(4, "AV1 Temporal Delimiter");
          break;

        case AV1_OBU_FRAME_HEADER:
          Debug(4, "AV1 Frame Header: size=%zu", size);
          break;

        case AV1_OBU_FRAME:
          Debug(4, "AV1 Frame: size=%zu", size);
          break;

        case AV1_OBU_TILE_GROUP:
          Debug(4, "AV1 Tile Group: size=%zu", size);
          break;

        case AV1_OBU_METADATA:
          Debug(4, "AV1 Metadata: size=%zu", size);
          break;

        default:
          Debug(4, "AV1 OBU type=%u size=%zu", obu_type, size);
          break;
      }
    }

    frameList.push_back(std::pair<unsigned char*, size_t>(buffer, size));

    if (bufSize == 0) break;

    buffer = this->extractFrame(buffer + size, bufSize, size);
  }

  frameSize = bufSize;
  return frameList;
}

#endif // HAVE_RTSP_SERVER
