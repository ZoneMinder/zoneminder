/*
 * This file is part of the ZoneMinder Project. See AUTHORS file for Copyright information
 *
 * This program is free software; you can redistribute it and/or modify it
 * under the terms of the GNU General Public License as published by the
 * Free Software Foundation; either version 2 of the License, or (at your
 * option) any later version.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or
 * FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for
 * more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program. If not, see <http://www.gnu.org/licenses/>.
 */

#ifndef ZM_STREAM_SOCKET_PROTOCOL_H
#define ZM_STREAM_SOCKET_PROTOCOL_H

#include <cstdint>
#include <vector>

extern "C" {
#include <libavcodec/avcodec.h>
#include <libavutil/rational.h>
}

// Wire protocol for the per-monitor media stream socket
// (PATH_SOCKS/stream_{monitor_id}.sock). All integers little-endian.
//
// Every message:
//   u32  length      bytes following this field (fixed header remainder + payload)
//   u8   version     protocol version, currently 1
//   u8   type        MessageType
//   u8   stream      StreamId (0 video, 1 audio)
//   u8   flags       bit 0: keyframe (video); other bits reserved, must be 0
//   u32  sequence    per-stream, counts every message produced (drops appear as gaps)
//   u32  generation  stream epoch; a bump means re-init the decoder from a new HELLO
//   u64  pts_us      microseconds, AV_TIME_BASE_Q, shared clock for both streams
//   [payload]
//
// HELLO payload is a TLV list (u8 tag, u16 length, value); unknown tags must
// be skipped by consumers.

namespace zm {
namespace stream_socket {

constexpr uint8_t kProtocolVersion = 1;

// Total serialized header size, including the leading length field.
constexpr size_t kHeaderSize = 24;
// Bytes of fixed header counted by the length field (kHeaderSize minus the
// length field itself).
constexpr uint32_t kHeaderLengthBytes = kHeaderSize - sizeof(uint32_t);
// Sanity cap on the length field; larger values mean a corrupt or hostile peer.
constexpr uint32_t kMaxMessageLength = 32 * 1024 * 1024;

enum class MessageType : uint8_t {
  Hello    = 0x01,
  Media    = 0x02,
  Keyframe = 0x03,
  Stats    = 0x04,
  Bye      = 0x05,
};

enum class StreamId : uint8_t {
  Video = 0,
  Audio = 1,
};

constexpr uint8_t kFlagKeyframe = 0x01;

// HELLO TLV tags
constexpr uint8_t kTlvCodecId    = 0x01;  // u32, AVCodecID value
constexpr uint8_t kTlvExtradata  = 0x02;  // raw codecpar->extradata
constexpr uint8_t kTlvWidth      = 0x03;  // u32
constexpr uint8_t kTlvHeight     = 0x04;  // u32
constexpr uint8_t kTlvFpsNum     = 0x05;  // u32
constexpr uint8_t kTlvFpsDen     = 0x06;  // u32
constexpr uint8_t kTlvSampleRate = 0x07;  // u32
constexpr uint8_t kTlvChannels   = 0x08;  // u32
constexpr uint8_t kTlvProfile    = 0x09;  // u32
constexpr uint8_t kTlvLevel      = 0x0A;  // u32

struct Header {
  uint32_t length;      // bytes following the length field == kHeaderLengthBytes + payload size
  uint8_t  version;
  uint8_t  type;
  uint8_t  stream;
  uint8_t  flags;
  uint32_t sequence;
  uint32_t generation;
  uint64_t pts_us;

  uint32_t payload_size() const { return length - kHeaderLengthBytes; }
};

void SerializeHeader(const Header &header, uint8_t out[kHeaderSize]);

// Returns false if the version is unsupported or the length field is
// impossible (shorter than the fixed header remainder or above the cap).
bool ParseHeader(const uint8_t in[kHeaderSize], Header &header);

// Decoded HELLO parameters. Zero means "not present on the wire" for every
// field except codec_id, which is required.
struct HelloInfo {
  AVCodecID codec_id = AV_CODEC_ID_NONE;
  std::vector<uint8_t> extradata;
  uint32_t width = 0;
  uint32_t height = 0;
  uint32_t fps_num = 0;
  uint32_t fps_den = 0;
  uint32_t sample_rate = 0;
  uint32_t channels = 0;
  uint32_t profile = 0;
  uint32_t level = 0;
};

// Builds the HELLO payload TLV list from codec parameters. frame_rate is only
// used for video parameters; pass {0, 0} when unknown.
std::vector<uint8_t> BuildHello(const AVCodecParameters *par, AVRational frame_rate);

// Parses a HELLO payload, skipping unknown tags. Returns false on a truncated
// TLV or a missing codec id.
bool ParseHello(const uint8_t *data, size_t len, HelloInfo &info);

// STATS payload: u64 messages_sent, u64 messages_dropped_for_this_client.
std::vector<uint8_t> BuildStats(uint64_t sent, uint64_t dropped);
bool ParseStats(const uint8_t *data, size_t len, uint64_t &sent, uint64_t &dropped);

}  // namespace stream_socket
}  // namespace zm

#endif  // ZM_STREAM_SOCKET_PROTOCOL_H
