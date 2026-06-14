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

TEST_CASE("stream_socket::Event state_changed roundtrip") {
  MonitorEvent in;
  in.code = kEventStateChanged;
  in.wall_clock_us = 0x0001020304050607ULL;
  in.has_wall_clock = true;
  in.state_id = 2;       in.has_state_id = true;
  in.prev_state_id = 0;  in.has_prev_state_id = true;
  in.state_name = "Alarm";

  std::vector<uint8_t> payload = BuildEvent(in);

  MonitorEvent out;
  REQUIRE(ParseEvent(payload.data(), payload.size(), out));
  REQUIRE(out.code == kEventStateChanged);
  REQUIRE(out.has_wall_clock);
  REQUIRE(out.wall_clock_us == in.wall_clock_us);
  REQUIRE(out.has_state_id);
  REQUIRE(out.state_id == 2);
  REQUIRE(out.has_prev_state_id);
  REQUIRE(out.prev_state_id == 0);
  REQUIRE(out.state_name == "Alarm");
  // Unset fields stay absent.
  REQUIRE_FALSE(out.has_detail);
  REQUIRE(out.message.empty());
}

TEST_CASE("stream_socket::Event health roundtrip with detail") {
  MonitorEvent in;
  in.code = kEventConnectionFailed;
  in.wall_clock_us = 1718355103501000ULL;
  in.has_wall_clock = true;
  in.message = "Unable to connect to the capture source";
  in.detail = 110;  // ETIMEDOUT
  in.has_detail = true;

  std::vector<uint8_t> payload = BuildEvent(in);

  MonitorEvent out;
  REQUIRE(ParseEvent(payload.data(), payload.size(), out));
  REQUIRE(out.code == kEventConnectionFailed);
  REQUIRE(out.wall_clock_us == in.wall_clock_us);
  REQUIRE(out.message == in.message);
  REQUIRE(out.has_detail);
  REQUIRE(out.detail == 110);
  REQUIRE_FALSE(out.has_state_id);
  REQUIRE(out.state_name.empty());
}

TEST_CASE("stream_socket::Event minimal snapshot roundtrip") {
  MonitorEvent in;
  in.code = kEventSnapshot;
  in.state_id = 0;  in.has_state_id = true;
  in.state_name = "Idle";
  in.wall_clock_us = 7;  in.has_wall_clock = true;

  std::vector<uint8_t> payload = BuildEvent(in);

  MonitorEvent out;
  REQUIRE(ParseEvent(payload.data(), payload.size(), out));
  REQUIRE(out.code == kEventSnapshot);
  REQUIRE(out.has_state_id);
  REQUIRE(out.state_id == 0);
  REQUIRE(out.state_name == "Idle");
  REQUIRE_FALSE(out.has_health_code);
}

TEST_CASE("stream_socket::Event faulted snapshot carries health code") {
  MonitorEvent in;
  in.code = kEventSnapshot;
  in.state_id = 1;  in.has_state_id = true;
  in.state_name = "IDLE";
  in.message = "Unable to connect to the capture source";
  in.health_code = kEventConnectionFailed;
  in.has_health_code = true;

  std::vector<uint8_t> payload = BuildEvent(in);

  MonitorEvent out;
  REQUIRE(ParseEvent(payload.data(), payload.size(), out));
  REQUIRE(out.code == kEventSnapshot);
  REQUIRE(out.has_health_code);
  REQUIRE(out.health_code == kEventConnectionFailed);
  REQUIRE(out.message == in.message);
}

TEST_CASE("stream_socket::ParseEvent skips unknown tags") {
  MonitorEvent in;
  in.code = kEventCaptureResumed;
  std::vector<uint8_t> payload = BuildEvent(in);
  // Append an unknown TLV (tag 0x7e, 2-byte value)
  payload.insert(payload.end(), {0x7e, 0x02, 0x00, 0xde, 0xad});

  MonitorEvent out;
  REQUIRE(ParseEvent(payload.data(), payload.size(), out));
  REQUIRE(out.code == kEventCaptureResumed);
}

TEST_CASE("stream_socket::ParseEvent rejects malformed input") {
  SECTION("missing fixed code") {
    std::vector<uint8_t> payload = {0x01};  // only 1 byte, need 2
    MonitorEvent out;
    REQUIRE_FALSE(ParseEvent(payload.data(), payload.size(), out));
  }

  SECTION("truncated TLV header") {
    std::vector<uint8_t> payload = {0x01, 0x02, kTlvStateId, 0x04};  // missing length high byte
    MonitorEvent out;
    REQUIRE_FALSE(ParseEvent(payload.data(), payload.size(), out));
  }

  SECTION("value extends past end") {
    std::vector<uint8_t> payload = {0x01, 0x02, kTlvWallClockUs, 0x08, 0x00, 0x00};
    MonitorEvent out;
    REQUIRE_FALSE(ParseEvent(payload.data(), payload.size(), out));
  }

  SECTION("wall clock with wrong size") {
    std::vector<uint8_t> payload = {0x01, 0x02, kTlvWallClockUs, 0x04, 0x00, 0x01, 0x02, 0x03, 0x04};
    MonitorEvent out;
    REQUIRE_FALSE(ParseEvent(payload.data(), payload.size(), out));
  }

  SECTION("code-only payload is valid") {
    std::vector<uint8_t> payload = {0x06, 0x01};  // code 0x0106, no TLVs
    MonitorEvent out;
    REQUIRE(ParseEvent(payload.data(), payload.size(), out));
    REQUIRE(out.code == kEventCaptureResumed);
  }
}
