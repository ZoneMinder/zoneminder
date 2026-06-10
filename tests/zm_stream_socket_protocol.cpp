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

#include "zm_catch2.h"

#include "zm_stream_socket_protocol.h"

#include <cstring>
#include <limits>
#include <memory>

using namespace zm::stream_socket;

namespace {

struct AVCodecParametersDeleter {
  void operator()(AVCodecParameters *par) const { avcodec_parameters_free(&par); }
};
using codec_parameters_ptr = std::unique_ptr<AVCodecParameters, AVCodecParametersDeleter>;

codec_parameters_ptr make_parameters() {
  return codec_parameters_ptr{avcodec_parameters_alloc()};
}

void set_extradata(AVCodecParameters *par, const std::vector<uint8_t> &extradata) {
  par->extradata = static_cast<uint8_t *>(av_mallocz(extradata.size() + AV_INPUT_BUFFER_PADDING_SIZE));
  memcpy(par->extradata, extradata.data(), extradata.size());
  par->extradata_size = extradata.size();
}

}  // namespace

TEST_CASE("stream_socket::Header roundtrip") {
  Header in = {};
  in.length = kHeaderLengthBytes + 1234;
  in.version = kProtocolVersion;
  in.type = static_cast<uint8_t>(MessageType::Media);
  in.stream = static_cast<uint8_t>(StreamId::Video);
  in.flags = kFlagKeyframe;
  in.sequence = 0xDEADBEEF;
  in.generation = 42;
  in.pts_us = 0x0123456789ABCDEFULL;

  uint8_t wire[kHeaderSize];
  SerializeHeader(in, wire);

  // little-endian length on the wire
  REQUIRE(wire[0] == ((kHeaderLengthBytes + 1234) & 0xff));
  REQUIRE(wire[4] == kProtocolVersion);

  Header out = {};
  REQUIRE(ParseHeader(wire, out));
  REQUIRE(out.length == in.length);
  REQUIRE(out.version == in.version);
  REQUIRE(out.type == in.type);
  REQUIRE(out.stream == in.stream);
  REQUIRE(out.flags == in.flags);
  REQUIRE(out.sequence == in.sequence);
  REQUIRE(out.generation == in.generation);
  REQUIRE(out.pts_us == in.pts_us);
  REQUIRE(out.payload_size() == 1234);
}

TEST_CASE("stream_socket::Header boundary values") {
  Header in = {};
  in.version = kProtocolVersion;
  in.type = static_cast<uint8_t>(MessageType::Bye);
  uint8_t wire[kHeaderSize];

  SECTION("zero payload") {
    in.length = kHeaderLengthBytes;
    SerializeHeader(in, wire);
    Header out = {};
    REQUIRE(ParseHeader(wire, out));
    REQUIRE(out.payload_size() == 0);
  }

  SECTION("max sequence/generation/pts") {
    in.length = kHeaderLengthBytes;
    in.sequence = std::numeric_limits<uint32_t>::max();
    in.generation = std::numeric_limits<uint32_t>::max();
    in.pts_us = std::numeric_limits<uint64_t>::max();
    SerializeHeader(in, wire);
    Header out = {};
    REQUIRE(ParseHeader(wire, out));
    REQUIRE(out.sequence == in.sequence);
    REQUIRE(out.generation == in.generation);
    REQUIRE(out.pts_us == in.pts_us);
  }

  SECTION("length below fixed header is rejected") {
    in.length = kHeaderLengthBytes - 1;
    SerializeHeader(in, wire);
    Header out = {};
    REQUIRE_FALSE(ParseHeader(wire, out));
  }

  SECTION("length above cap is rejected") {
    in.length = kMaxMessageLength + 1;
    SerializeHeader(in, wire);
    Header out = {};
    REQUIRE_FALSE(ParseHeader(wire, out));
  }

  SECTION("unsupported version is rejected") {
    in.length = kHeaderLengthBytes;
    in.version = kProtocolVersion + 1;
    SerializeHeader(in, wire);
    Header out = {};
    REQUIRE_FALSE(ParseHeader(wire, out));
  }
}

TEST_CASE("stream_socket::Hello video roundtrip") {
  codec_parameters_ptr par = make_parameters();
  par->codec_type = AVMEDIA_TYPE_VIDEO;
  par->codec_id = AV_CODEC_ID_H264;
  par->width = 1920;
  par->height = 1080;
  par->profile = 100;  // High
  par->level = 41;
  std::vector<uint8_t> extradata = {0x01, 0x64, 0x00, 0x29, 0xff, 0xe1, 0x00, 0x05};
  set_extradata(par.get(), extradata);

  std::vector<uint8_t> payload = BuildHello(par.get(), {30000, 1001});

  HelloInfo info;
  REQUIRE(ParseHello(payload.data(), payload.size(), info));
  REQUIRE(info.codec_id == AV_CODEC_ID_H264);
  REQUIRE(info.extradata == extradata);
  REQUIRE(info.width == 1920);
  REQUIRE(info.height == 1080);
  REQUIRE(info.fps_num == 30000);
  REQUIRE(info.fps_den == 1001);
  REQUIRE(info.profile == 100);
  REQUIRE(info.level == 41);
  REQUIRE(info.sample_rate == 0);
  REQUIRE(info.channels == 0);
}

TEST_CASE("stream_socket::Hello audio roundtrip") {
  codec_parameters_ptr par = make_parameters();
  par->codec_type = AVMEDIA_TYPE_AUDIO;
  par->codec_id = AV_CODEC_ID_AAC;
  par->sample_rate = 16000;
#if LIBAVUTIL_VERSION_INT >= AV_VERSION_INT(57, 28, 100)
  av_channel_layout_default(&par->ch_layout, 1);
#else
  par->channels = 1;
#endif
  // AudioSpecificConfig for AAC-LC 16kHz mono
  std::vector<uint8_t> asc = {0x14, 0x08};
  set_extradata(par.get(), asc);

  std::vector<uint8_t> payload = BuildHello(par.get(), {0, 0});

  HelloInfo info;
  REQUIRE(ParseHello(payload.data(), payload.size(), info));
  REQUIRE(info.codec_id == AV_CODEC_ID_AAC);
  REQUIRE(info.extradata == asc);
  REQUIRE(info.sample_rate == 16000);
  REQUIRE(info.channels == 1);
  REQUIRE(info.width == 0);
  REQUIRE(info.fps_num == 0);
}

TEST_CASE("stream_socket::Hello without extradata") {
  codec_parameters_ptr par = make_parameters();
  par->codec_type = AVMEDIA_TYPE_AUDIO;
  par->codec_id = AV_CODEC_ID_PCM_ALAW;
  par->sample_rate = 8000;

  std::vector<uint8_t> payload = BuildHello(par.get(), {0, 0});

  HelloInfo info;
  REQUIRE(ParseHello(payload.data(), payload.size(), info));
  REQUIRE(info.codec_id == AV_CODEC_ID_PCM_ALAW);
  REQUIRE(info.extradata.empty());
  REQUIRE(info.sample_rate == 8000);
}

TEST_CASE("stream_socket::ParseHello skips unknown tags") {
  codec_parameters_ptr par = make_parameters();
  par->codec_type = AVMEDIA_TYPE_VIDEO;
  par->codec_id = AV_CODEC_ID_HEVC;
  std::vector<uint8_t> payload = BuildHello(par.get(), {0, 0});

  // Append an unknown TLV (tag 0x7f, 3-byte value)
  payload.insert(payload.end(), {0x7f, 0x03, 0x00, 0xaa, 0xbb, 0xcc});

  HelloInfo info;
  REQUIRE(ParseHello(payload.data(), payload.size(), info));
  REQUIRE(info.codec_id == AV_CODEC_ID_HEVC);
}

TEST_CASE("stream_socket::ParseHello rejects malformed input") {
  SECTION("truncated TLV header") {
    std::vector<uint8_t> payload = {kTlvCodecId, 0x04};  // missing length byte + value
    HelloInfo info;
    REQUIRE_FALSE(ParseHello(payload.data(), payload.size(), info));
  }

  SECTION("value extends past end") {
    std::vector<uint8_t> payload = {kTlvCodecId, 0x04, 0x00, 0x1b};  // claims 4 bytes, has 1
    HelloInfo info;
    REQUIRE_FALSE(ParseHello(payload.data(), payload.size(), info));
  }

  SECTION("known numeric tag with wrong size") {
    std::vector<uint8_t> payload = {kTlvWidth, 0x02, 0x00, 0x80, 0x07};
    HelloInfo info;
    REQUIRE_FALSE(ParseHello(payload.data(), payload.size(), info));
  }

  SECTION("missing codec id") {
    std::vector<uint8_t> payload = {kTlvWidth, 0x04, 0x00, 0x80, 0x07, 0x00, 0x00};
    HelloInfo info;
    REQUIRE_FALSE(ParseHello(payload.data(), payload.size(), info));
  }

  SECTION("empty payload") {
    HelloInfo info;
    REQUIRE_FALSE(ParseHello(nullptr, 0, info));
  }
}

TEST_CASE("stream_socket::Stats roundtrip") {
  std::vector<uint8_t> payload = BuildStats(123456789ULL, 42ULL);
  REQUIRE(payload.size() == 16);

  uint64_t sent = 0, dropped = 0;
  REQUIRE(ParseStats(payload.data(), payload.size(), sent, dropped));
  REQUIRE(sent == 123456789ULL);
  REQUIRE(dropped == 42ULL);

  SECTION("truncated") {
    REQUIRE_FALSE(ParseStats(payload.data(), 15, sent, dropped));
  }
}
