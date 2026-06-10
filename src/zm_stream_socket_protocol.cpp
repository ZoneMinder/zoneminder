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

#include "zm_stream_socket_protocol.h"

#include "zm_ffmpeg.h"

#include <cstring>

namespace zm {
namespace stream_socket {

namespace {

void put_u16(uint8_t *out, uint16_t value) {
  out[0] = value & 0xff;
  out[1] = (value >> 8) & 0xff;
}

void put_u32(uint8_t *out, uint32_t value) {
  out[0] = value & 0xff;
  out[1] = (value >> 8) & 0xff;
  out[2] = (value >> 16) & 0xff;
  out[3] = (value >> 24) & 0xff;
}

void put_u64(uint8_t *out, uint64_t value) {
  put_u32(out, value & 0xffffffff);
  put_u32(out + 4, value >> 32);
}

uint16_t get_u16(const uint8_t *in) {
  return static_cast<uint16_t>(in[0]) | (static_cast<uint16_t>(in[1]) << 8);
}

uint32_t get_u32(const uint8_t *in) {
  return static_cast<uint32_t>(in[0]) |
         (static_cast<uint32_t>(in[1]) << 8) |
         (static_cast<uint32_t>(in[2]) << 16) |
         (static_cast<uint32_t>(in[3]) << 24);
}

uint64_t get_u64(const uint8_t *in) {
  return static_cast<uint64_t>(get_u32(in)) |
         (static_cast<uint64_t>(get_u32(in + 4)) << 32);
}

void append_tlv(std::vector<uint8_t> &out, uint8_t tag, const uint8_t *value, uint16_t len) {
  out.push_back(tag);
  uint8_t len_le[2];
  put_u16(len_le, len);
  out.insert(out.end(), len_le, len_le + 2);
  out.insert(out.end(), value, value + len);
}

void append_tlv_u32(std::vector<uint8_t> &out, uint8_t tag, uint32_t value) {
  uint8_t value_le[4];
  put_u32(value_le, value);
  append_tlv(out, tag, value_le, sizeof(value_le));
}

}  // namespace

void SerializeHeader(const Header &header, uint8_t out[kHeaderSize]) {
  put_u32(out, header.length);
  out[4] = header.version;
  out[5] = header.type;
  out[6] = header.stream;
  out[7] = header.flags;
  put_u32(out + 8, header.sequence);
  put_u32(out + 12, header.generation);
  put_u64(out + 16, header.pts_us);
}

bool ParseHeader(const uint8_t in[kHeaderSize], Header &header) {
  header.length = get_u32(in);
  header.version = in[4];
  header.type = in[5];
  header.stream = in[6];
  header.flags = in[7];
  header.sequence = get_u32(in + 8);
  header.generation = get_u32(in + 12);
  header.pts_us = get_u64(in + 16);

  if (header.version != kProtocolVersion)
    return false;
  if (header.length < kHeaderLengthBytes or header.length > kMaxMessageLength)
    return false;
  return true;
}

std::vector<uint8_t> BuildHello(const AVCodecParameters *par, AVRational frame_rate) {
  std::vector<uint8_t> out;
  out.reserve(64 + (par->extradata ? par->extradata_size : 0));

  append_tlv_u32(out, kTlvCodecId, static_cast<uint32_t>(par->codec_id));
  if (par->extradata and par->extradata_size > 0) {
    append_tlv(out, kTlvExtradata, par->extradata, static_cast<uint16_t>(par->extradata_size));
  }
  if (par->codec_type == AVMEDIA_TYPE_VIDEO) {
    if (par->width > 0) append_tlv_u32(out, kTlvWidth, par->width);
    if (par->height > 0) append_tlv_u32(out, kTlvHeight, par->height);
    if (frame_rate.num > 0 and frame_rate.den > 0) {
      append_tlv_u32(out, kTlvFpsNum, frame_rate.num);
      append_tlv_u32(out, kTlvFpsDen, frame_rate.den);
    }
  } else if (par->codec_type == AVMEDIA_TYPE_AUDIO) {
    if (par->sample_rate > 0) append_tlv_u32(out, kTlvSampleRate, par->sample_rate);
#if LIBAVUTIL_VERSION_CHECK(57, 28, 100, 28, 0)
    int channels = par->ch_layout.nb_channels;
#else
    int channels = par->channels;
#endif
    if (channels > 0) append_tlv_u32(out, kTlvChannels, channels);
  }
  if (par->profile >= 0) append_tlv_u32(out, kTlvProfile, par->profile);
  if (par->level >= 0) append_tlv_u32(out, kTlvLevel, par->level);

  return out;
}

bool ParseHello(const uint8_t *data, size_t len, HelloInfo &info) {
  info = HelloInfo();
  size_t pos = 0;
  while (pos < len) {
    if (len - pos < 3)
      return false;
    uint8_t tag = data[pos];
    uint16_t value_len = get_u16(data + pos + 1);
    pos += 3;
    if (len - pos < value_len)
      return false;
    const uint8_t *value = data + pos;
    pos += value_len;

    bool known_numeric = tag >= kTlvCodecId and tag <= kTlvLevel and tag != kTlvExtradata;
    if (known_numeric and value_len != 4)
      return false;

    switch (tag) {
      case kTlvCodecId:    info.codec_id = static_cast<AVCodecID>(get_u32(value)); break;
      case kTlvExtradata:  info.extradata.assign(value, value + value_len); break;
      case kTlvWidth:      info.width = get_u32(value); break;
      case kTlvHeight:     info.height = get_u32(value); break;
      case kTlvFpsNum:     info.fps_num = get_u32(value); break;
      case kTlvFpsDen:     info.fps_den = get_u32(value); break;
      case kTlvSampleRate: info.sample_rate = get_u32(value); break;
      case kTlvChannels:   info.channels = get_u32(value); break;
      case kTlvProfile:    info.profile = get_u32(value); break;
      case kTlvLevel:      info.level = get_u32(value); break;
      default: break;  // unknown tag: skip
    }
  }
  return info.codec_id != AV_CODEC_ID_NONE;
}

std::vector<uint8_t> BuildStats(uint64_t sent, uint64_t dropped) {
  std::vector<uint8_t> out(16);
  put_u64(out.data(), sent);
  put_u64(out.data() + 8, dropped);
  return out;
}

bool ParseStats(const uint8_t *data, size_t len, uint64_t &sent, uint64_t &dropped) {
  if (len < 16)
    return false;
  sent = get_u64(data);
  dropped = get_u64(data + 8);
  return true;
}

}  // namespace stream_socket
}  // namespace zm
