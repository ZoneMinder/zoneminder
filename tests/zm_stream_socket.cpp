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

#include "zm_comms.h"
#include "zm_stream_socket.h"
#include "zm_stream_socket_protocol.h"

#include <chrono>
#include <cstring>
#include <memory>
#include <sys/stat.h>
#include <sys/time.h>
#include <thread>
#include <unistd.h>

using namespace zm::stream_socket;

namespace {

constexpr char kSockPath[] = "/tmp/zm.stream_socket.unittest.sock";

struct AVCodecParametersDeleter {
  void operator()(AVCodecParameters *par) const { avcodec_parameters_free(&par); }
};
using codec_parameters_ptr = std::unique_ptr<AVCodecParameters, AVCodecParametersDeleter>;

struct ReceivedMessage {
  Header header;
  std::vector<uint8_t> payload;
};

// Blocking exact-size reads against the non-blocking-server socket
class TestClient {
 public:
  bool Connect() {
    return sock_.connect(kSockPath);
  }

  // Returns false on timeout/close instead of blocking forever
  bool ReadMessage(ReceivedMessage &message,
                   std::chrono::milliseconds timeout = std::chrono::seconds(5)) {
    uint8_t header_bytes[kHeaderSize];
    if (!ReadExact(header_bytes, kHeaderSize, timeout))
      return false;
    if (!ParseHeader(header_bytes, message.header))
      return false;
    message.payload.resize(message.header.payload_size());
    if (message.payload.empty())
      return true;
    return ReadExact(message.payload.data(), message.payload.size(), timeout);
  }

  zm::TcpUnixClient sock_;

 private:
  bool ReadExact(uint8_t *out, size_t len, std::chrono::milliseconds timeout) {
    timeval tv = {};
    tv.tv_sec = timeout.count() / 1000;
    tv.tv_usec = (timeout.count() % 1000) * 1000;
    setsockopt(sock_.getDesc(), SOL_SOCKET, SO_RCVTIMEO, &tv, sizeof(tv));
    size_t done = 0;
    while (done < len) {
      ssize_t bytes = ::recv(sock_.getDesc(), out + done, len - done, 0);
      if (bytes <= 0)
        return false;
      done += bytes;
    }
    return true;
  }
};

av_packet_ptr make_packet(size_t size, uint8_t fill) {
  av_packet_ptr packet{av_packet_alloc()};
  REQUIRE(av_new_packet(packet.get(), size) == 0);
  memset(packet->data, fill, size);
  return packet;
}

codec_parameters_ptr make_h264_parameters() {
  codec_parameters_ptr par{avcodec_parameters_alloc()};
  par->codec_type = AVMEDIA_TYPE_VIDEO;
  par->codec_id = AV_CODEC_ID_H264;
  par->width = 640;
  par->height = 480;
  return par;
}

}  // namespace

TEST_CASE("StreamSocket lifecycle", "[stream_socket]") {
  StreamSocket server(1, kSockPath);
  REQUIRE_FALSE(server.IsRunning());

  REQUIRE(server.Start());
  REQUIRE(server.IsRunning());

  SECTION("socket file exists with 0660 permissions") {
    struct stat st = {};
    REQUIRE(stat(kSockPath, &st) == 0);
    REQUIRE(S_ISSOCK(st.st_mode));
    REQUIRE((st.st_mode & 0777) == 0660);
  }

  SECTION("client can connect") {
    TestClient client;
    REQUIRE(client.Connect());
  }

  server.Stop();
  REQUIRE_FALSE(server.IsRunning());
  // socket file removed on Stop
  struct stat st = {};
  REQUIRE(stat(kSockPath, &st) != 0);
}

TEST_CASE("StreamSocket sends HELLO first, then media", "[stream_socket]") {
  StreamSocket server(1, kSockPath);
  REQUIRE(server.Start());

  codec_parameters_ptr par = make_h264_parameters();
  server.SetVideoParams(par.get(), {30, 1});

  TestClient client;
  REQUIRE(client.Connect());

  ReceivedMessage hello;
  REQUIRE(client.ReadMessage(hello));
  REQUIRE(hello.header.type == static_cast<uint8_t>(MessageType::Hello));
  REQUIRE(hello.header.stream == static_cast<uint8_t>(StreamId::Video));

  HelloInfo info;
  REQUIRE(ParseHello(hello.payload.data(), hello.payload.size(), info));
  REQUIRE(info.codec_id == AV_CODEC_ID_H264);
  REQUIRE(info.width == 640);
  REQUIRE(info.height == 480);
  REQUIRE(info.fps_num == 30);

  // wait for the server to register the client before sending
  std::this_thread::sleep_for(std::chrono::milliseconds(50));

  av_packet_ptr packet = make_packet(1000, 0xAB);
  server.SendMedia(packet.get(), StreamId::Video, true, 123456);

  ReceivedMessage media;
  REQUIRE(client.ReadMessage(media));
  REQUIRE(media.header.type == static_cast<uint8_t>(MessageType::Media));
  REQUIRE(media.header.flags == kFlagKeyframe);
  REQUIRE(media.header.pts_us == 123456);
  REQUIRE(media.payload.size() == 1000);
  REQUIRE(media.payload[0] == 0xAB);
  REQUIRE(media.payload[999] == 0xAB);

  server.Stop();
}

TEST_CASE("StreamSocket late joiner receives cached keyframe", "[stream_socket]") {
  StreamSocket server(1, kSockPath);
  REQUIRE(server.Start());

  codec_parameters_ptr par = make_h264_parameters();
  server.SetVideoParams(par.get(), {0, 0});

  av_packet_ptr keyframe = make_packet(2000, 0x5A);
  server.SendMedia(keyframe.get(), StreamId::Video, true, 1000);
  av_packet_ptr delta = make_packet(100, 0x11);
  server.SendMedia(delta.get(), StreamId::Video, false, 2000);

  TestClient client;
  REQUIRE(client.Connect());

  ReceivedMessage hello;
  REQUIRE(client.ReadMessage(hello));
  REQUIRE(hello.header.type == static_cast<uint8_t>(MessageType::Hello));

  ReceivedMessage cached;
  REQUIRE(client.ReadMessage(cached));
  REQUIRE(cached.header.type == static_cast<uint8_t>(MessageType::Keyframe));
  REQUIRE(cached.header.pts_us == 1000);
  REQUIRE(cached.payload.size() == 2000);
  REQUIRE(cached.payload[0] == 0x5A);

  server.Stop();
}

TEST_CASE("StreamSocket queue overflow drops media but never HELLO", "[stream_socket]") {
  StreamSocket::Config config;
  config.queue_max_bytes = 64 * 1024;
  config.queue_max_msgs = 16;
  config.stats_interval = std::chrono::milliseconds(200);
  config.stall_timeout = std::chrono::seconds(60);  // not under test here
  StreamSocket server(1, kSockPath, config);
  REQUIRE(server.Start());

  codec_parameters_ptr par = make_h264_parameters();
  server.SetVideoParams(par.get(), {0, 0});

  TestClient client;
  REQUIRE(client.Connect());
  std::this_thread::sleep_for(std::chrono::milliseconds(100));

  // Push much more than the queue + kernel buffers can hold while the client
  // is not reading.
  constexpr size_t kPacketSize = 16 * 1024;
  constexpr size_t kPackets = 200;
  av_packet_ptr packet = make_packet(kPacketSize, 0x42);
  for (size_t i = 0; i < kPackets; ++i) {
    server.SendMedia(packet.get(), StreamId::Video, false, i);
  }

  // Now drain everything; track sequence gaps and stats
  uint64_t received_media = 0;
  uint64_t reported_dropped = 0;
  bool got_stats_with_drops = false;
  uint32_t last_sequence = 0;
  bool first_media = true;
  uint64_t gap_total = 0;

  // The server emits STATS continuously, so bound the drain by a streak of
  // non-media messages rather than a read timeout.
  int non_media_streak = 0;
  ReceivedMessage message;
  while (non_media_streak < 5
         and client.ReadMessage(message, std::chrono::milliseconds(1000))) {
    ++non_media_streak;
    if (message.header.type == static_cast<uint8_t>(MessageType::Hello))
      continue;
    if (message.header.type == static_cast<uint8_t>(MessageType::Stats)) {
      uint64_t sent = 0;
      REQUIRE(ParseStats(message.payload.data(), message.payload.size(), sent, reported_dropped));
      if (reported_dropped > 0)
        got_stats_with_drops = true;
      continue;
    }
    if (message.header.type == static_cast<uint8_t>(MessageType::Media)) {
      non_media_streak = 0;
      if (!first_media) {
        REQUIRE(message.header.sequence > last_sequence);  // monotonic
        gap_total += message.header.sequence - last_sequence - 1;
      } else {
        gap_total += message.header.sequence;  // drops before the first received
        first_media = false;
      }
      last_sequence = message.header.sequence;
      ++received_media;
    }
  }

  // The client never saw some packets, and the loss is observable both ways
  REQUIRE(received_media < kPackets);
  REQUIRE(gap_total > 0);
  REQUIRE(got_stats_with_drops);
  REQUIRE(gap_total + received_media == kPackets);

  server.Stop();
}

TEST_CASE("StreamSocket stalled client does not affect a live one", "[stream_socket]") {
  StreamSocket::Config config;
  config.queue_max_bytes = 32 * 1024;
  config.queue_max_msgs = 8;
  StreamSocket server(1, kSockPath, config);
  REQUIRE(server.Start());

  codec_parameters_ptr par = make_h264_parameters();
  server.SetVideoParams(par.get(), {0, 0});

  TestClient stalled;
  REQUIRE(stalled.Connect());
  TestClient live;
  REQUIRE(live.Connect());
  std::this_thread::sleep_for(std::chrono::milliseconds(100));

  ReceivedMessage message;
  REQUIRE(live.ReadMessage(message));  // HELLO
  REQUIRE(message.header.type == static_cast<uint8_t>(MessageType::Hello));

  constexpr size_t kPackets = 50;
  av_packet_ptr packet = make_packet(8 * 1024, 0x99);
  uint64_t live_received = 0;
  bool live_gap = false;
  uint32_t expected_sequence = 0;
  for (size_t i = 0; i < kPackets; ++i) {
    server.SendMedia(packet.get(), StreamId::Video, false, i);
    // live client reads continuously; stalled never reads
    if (live.ReadMessage(message)) {
      if (message.header.type == static_cast<uint8_t>(MessageType::Media)) {
        if (message.header.sequence != expected_sequence)
          live_gap = true;
        expected_sequence = message.header.sequence + 1;
        ++live_received;
      }
    }
  }

  REQUIRE(live_received == kPackets);
  REQUIRE_FALSE(live_gap);

  server.Stop();
}

TEST_CASE("StreamSocket parameter change bumps generation and resends HELLO", "[stream_socket]") {
  StreamSocket server(1, kSockPath);
  REQUIRE(server.Start());

  codec_parameters_ptr par = make_h264_parameters();
  server.SetVideoParams(par.get(), {0, 0});

  TestClient client;
  REQUIRE(client.Connect());

  ReceivedMessage hello;
  REQUIRE(client.ReadMessage(hello));
  REQUIRE(hello.header.generation == 0);

  std::this_thread::sleep_for(std::chrono::milliseconds(50));

  // Same parameters: no new HELLO, no generation bump
  server.SetVideoParams(par.get(), {0, 0});

  // Changed parameters: generation bump + new HELLO
  par->width = 1920;
  par->height = 1080;
  server.SetVideoParams(par.get(), {0, 0});

  ReceivedMessage hello2;
  REQUIRE(client.ReadMessage(hello2));
  REQUIRE(hello2.header.type == static_cast<uint8_t>(MessageType::Hello));
  REQUIRE(hello2.header.generation == 1);

  HelloInfo info;
  REQUIRE(ParseHello(hello2.payload.data(), hello2.payload.size(), info));
  REQUIRE(info.width == 1920);

  // Media now carries the new generation, sequence restarted
  std::this_thread::sleep_for(std::chrono::milliseconds(50));
  av_packet_ptr packet = make_packet(100, 0x77);
  server.SendMedia(packet.get(), StreamId::Video, false, 5000);

  ReceivedMessage media;
  REQUIRE(client.ReadMessage(media));
  REQUIRE(media.header.type == static_cast<uint8_t>(MessageType::Media));
  REQUIRE(media.header.generation == 1);
  REQUIRE(media.header.sequence == 0);

  server.Stop();
}

TEST_CASE("StreamSocket sends BYE on stop", "[stream_socket]") {
  StreamSocket server(1, kSockPath);
  REQUIRE(server.Start());

  TestClient client;
  REQUIRE(client.Connect());
  std::this_thread::sleep_for(std::chrono::milliseconds(100));

  server.Stop();

  ReceivedMessage message;
  REQUIRE(client.ReadMessage(message));
  REQUIRE(message.header.type == static_cast<uint8_t>(MessageType::Bye));
}

TEST_CASE("StreamSocket::ParseAllowedUids", "[stream_socket]") {
  REQUIRE(StreamSocket::ParseAllowedUids("").empty());
  REQUIRE(StreamSocket::ParseAllowedUids("33") == std::vector<uid_t>{33});
  REQUIRE(StreamSocket::ParseAllowedUids("33,1000") == std::vector<uid_t>{33, 1000});
  REQUIRE(StreamSocket::ParseAllowedUids(" 33 , 1000 ") == std::vector<uid_t>{33, 1000});
  REQUIRE(StreamSocket::ParseAllowedUids("33,,1000") == std::vector<uid_t>{33, 1000});
  REQUIRE(StreamSocket::ParseAllowedUids("33,bogus,1000") == std::vector<uid_t>{33, 1000});
}
