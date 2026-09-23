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
#include <string>
#include <sys/stat.h>
#include <sys/time.h>
#include <thread>
#include <unistd.h>

using namespace zm::stream_socket;

namespace {

// Per-process path so concurrent test runs do not share a socket file
const std::string kSockPathStr = "/tmp/zm.stream_socket.unittest." + std::to_string(getpid()) + ".sock";
const char *kSockPath = kSockPathStr.c_str();

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

codec_parameters_ptr make_aac_parameters() {
  codec_parameters_ptr par{avcodec_parameters_alloc()};
  par->codec_type = AVMEDIA_TYPE_AUDIO;
  par->codec_id = AV_CODEC_ID_AAC;
  par->sample_rate = 48000;
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

namespace {

std::vector<uint8_t> make_state_event(uint16_t code, uint32_t state_id,
                                      uint32_t prev_state_id, const char *name) {
  MonitorEvent ev;
  ev.code = code;
  ev.state_id = state_id;       ev.has_state_id = true;
  ev.prev_state_id = prev_state_id; ev.has_prev_state_id = true;
  ev.state_name = name;
  ev.wall_clock_us = 1718355103501000ULL;
  ev.has_wall_clock = true;
  return BuildEvent(ev);
}

}  // namespace

TEST_CASE("StreamSocket broadcasts a monitor EVENT", "[stream_socket]") {
  StreamSocket server(1, kSockPath);
  REQUIRE(server.Start());

  TestClient client;
  REQUIRE(client.Connect());
  std::this_thread::sleep_for(std::chrono::milliseconds(50));

  server.SendMonitorEvent(make_state_event(kEventStateChanged, 2, 0, "Alarm"));

  ReceivedMessage message;
  REQUIRE(client.ReadMessage(message));
  REQUIRE(message.header.type == static_cast<uint8_t>(MessageType::Event));
  REQUIRE(message.header.stream == static_cast<uint8_t>(StreamId::Monitor));
  REQUIRE(message.header.sequence == 0);

  MonitorEvent ev;
  REQUIRE(ParseEvent(message.payload.data(), message.payload.size(), ev));
  REQUIRE(ev.code == kEventStateChanged);
  REQUIRE(ev.state_id == 2);
  REQUIRE(ev.prev_state_id == 0);
  REQUIRE(ev.state_name == "Alarm");
  REQUIRE(ev.has_wall_clock);

  server.Stop();
}

TEST_CASE("StreamSocket event sequence advances without clients", "[stream_socket]") {
  StreamSocket server(1, kSockPath);
  REQUIRE(server.Start());

  // Two events produced before any consumer connects; the sequence still
  // advances so the gap is observable to a later joiner.
  server.SendMonitorEvent(make_state_event(kEventConnectionFailed, 0, 0, ""));
  server.SendMonitorEvent(make_state_event(kEventConnectionRestored, 0, 0, ""));

  TestClient client;
  REQUIRE(client.Connect());
  std::this_thread::sleep_for(std::chrono::milliseconds(50));

  server.SendMonitorEvent(make_state_event(kEventStateChanged, 1, 0, "PreAlarm"));

  ReceivedMessage message;
  REQUIRE(client.ReadMessage(message));
  REQUIRE(message.header.type == static_cast<uint8_t>(MessageType::Event));
  REQUIRE(message.header.sequence == 2);  // 0 and 1 dropped before connect

  server.Stop();
}

TEST_CASE("StreamSocket replays snapshot on connect", "[stream_socket]") {
  StreamSocket server(1, kSockPath);
  REQUIRE(server.Start());

  codec_parameters_ptr par = make_h264_parameters();
  server.SetVideoParams(par.get(), {30, 1});
  server.SetSnapshotEvent(make_state_event(kEventSnapshot, 0, 0, "Idle"));

  TestClient client;
  REQUIRE(client.Connect());

  // HELLO first
  ReceivedMessage hello;
  REQUIRE(client.ReadMessage(hello));
  REQUIRE(hello.header.type == static_cast<uint8_t>(MessageType::Hello));

  // then the snapshot EVENT
  ReceivedMessage snap;
  REQUIRE(client.ReadMessage(snap));
  REQUIRE(snap.header.type == static_cast<uint8_t>(MessageType::Event));
  REQUIRE(snap.header.stream == static_cast<uint8_t>(StreamId::Monitor));

  MonitorEvent ev;
  REQUIRE(ParseEvent(snap.payload.data(), snap.payload.size(), ev));
  REQUIRE(ev.code == kEventSnapshot);
  REQUIRE(ev.state_id == 0);
  REQUIRE(ev.state_name == "Idle");

  server.Stop();
}

TEST_CASE("StreamSocket::ParseAllowedUids", "[stream_socket]") {
  REQUIRE(StreamSocket::ParseAllowedUids("").empty());
  REQUIRE(StreamSocket::ParseAllowedUids("33") == std::vector<uid_t>{33});
  REQUIRE(StreamSocket::ParseAllowedUids("33,1000") == std::vector<uid_t>{33, 1000});
  REQUIRE(StreamSocket::ParseAllowedUids(" 33 , 1000 ") == std::vector<uid_t>{33, 1000});
  REQUIRE(StreamSocket::ParseAllowedUids("33,,1000") == std::vector<uid_t>{33, 1000});
  REQUIRE(StreamSocket::ParseAllowedUids("33,bogus,1000") == std::vector<uid_t>{33, 1000});

  // Values that cannot be a uid are rejected, never wrapped or truncated
  REQUIRE(StreamSocket::ParseAllowedUids("-1").empty());
  REQUIRE(StreamSocket::ParseAllowedUids("99999999999999999999").empty());  // > ULONG_MAX
  if (sizeof(uid_t) < sizeof(unsigned long)) {
    REQUIRE(StreamSocket::ParseAllowedUids("18446744073709551615").empty());  // ULONG_MAX
    REQUIRE(StreamSocket::ParseAllowedUids("4294967296").empty());  // 2^32 would truncate to 0
  }
  REQUIRE(StreamSocket::ParseAllowedUids("33,-1,1000") == std::vector<uid_t>{33, 1000});
}

TEST_CASE("StreamSocket drops media for a stream with no HELLO", "[stream_socket]") {
  StreamSocket server(1, kSockPath);
  REQUIRE(server.Start());

  // Only video is announced; audio never gets a HELLO.
  codec_parameters_ptr par = make_h264_parameters();
  server.SetVideoParams(par.get(), {0, 0});

  TestClient client;
  REQUIRE(client.Connect());
  ReceivedMessage hello;
  REQUIRE(client.ReadMessage(hello));
  REQUIRE(hello.header.type == static_cast<uint8_t>(MessageType::Hello));
  REQUIRE(hello.header.stream == static_cast<uint8_t>(StreamId::Video));
  std::this_thread::sleep_for(std::chrono::milliseconds(50));

  // An audio packet arrives before audio is announced: it must not reach the
  // wire (a consumer could not decode it without a HELLO).
  av_packet_ptr audio = make_packet(200, 0x33);
  server.SendMedia(audio.get(), StreamId::Audio, false, 111);
  // A following video packet does go out; if the audio had leaked we would
  // read it first.
  av_packet_ptr video = make_packet(200, 0x44);
  server.SendMedia(video.get(), StreamId::Video, false, 222);

  ReceivedMessage message;
  REQUIRE(client.ReadMessage(message));
  REQUIRE(message.header.type == static_cast<uint8_t>(MessageType::Media));
  REQUIRE(message.header.stream == static_cast<uint8_t>(StreamId::Video));
  REQUIRE(message.header.pts_us == 222);

  server.Stop();
}

TEST_CASE("StreamSocket::ClearAudioParams stops announcing audio", "[stream_socket]") {
  StreamSocket server(1, kSockPath);
  REQUIRE(server.Start());

  // Both streams announced together, as a monitor prime does: generation 0
  codec_parameters_ptr vpar = make_h264_parameters();
  codec_parameters_ptr apar = make_aac_parameters();
  server.SetStreams(vpar.get(), {0, 0}, apar.get());

  // A keyframe is cached for late joiners under generation 0
  av_packet_ptr keyframe = make_packet(300, 0x5A);
  server.SendMedia(keyframe.get(), StreamId::Video, true, 500);

  // First consumer sees both HELLOs, audio first (the video HELLO completes
  // a generation's parameter set), then the cached keyframe.
  {
    TestClient client;
    REQUIRE(client.Connect());
    ReceivedMessage m;
    REQUIRE(client.ReadMessage(m));
    REQUIRE(m.header.type == static_cast<uint8_t>(MessageType::Hello));
    REQUIRE(m.header.stream == static_cast<uint8_t>(StreamId::Audio));
    REQUIRE(client.ReadMessage(m));
    REQUIRE(m.header.type == static_cast<uint8_t>(MessageType::Hello));
    REQUIRE(m.header.stream == static_cast<uint8_t>(StreamId::Video));
    REQUIRE(client.ReadMessage(m));
    REQUIRE(m.header.type == static_cast<uint8_t>(MessageType::Keyframe));
    REQUIRE(m.header.generation == 0);
  }

  // Audio goes away on a re-prime: a full generation bump, so the video HELLO
  // is re-issued under it and the generation-0 keyframe is dropped.
  server.ClearAudioParams();

  // A fresh consumer is told about video only, at the new generation.
  TestClient client;
  REQUIRE(client.Connect());
  ReceivedMessage video_hello;
  REQUIRE(client.ReadMessage(video_hello));
  REQUIRE(video_hello.header.type == static_cast<uint8_t>(MessageType::Hello));
  REQUIRE(video_hello.header.stream == static_cast<uint8_t>(StreamId::Video));
  REQUIRE(video_hello.header.generation == 1);

  // No audio HELLO and no stale keyframe follow; the next thing on the wire
  // is fresh video media, with its sequence restarted for the generation.
  std::this_thread::sleep_for(std::chrono::milliseconds(50));
  av_packet_ptr video = make_packet(100, 0x66);
  server.SendMedia(video.get(), StreamId::Video, false, 900);
  ReceivedMessage next;
  REQUIRE(client.ReadMessage(next));
  REQUIRE(next.header.type == static_cast<uint8_t>(MessageType::Media));
  REQUIRE(next.header.stream == static_cast<uint8_t>(StreamId::Video));
  REQUIRE(next.header.generation == 1);
  REQUIRE(next.header.sequence == 0);

  server.Stop();
}

TEST_CASE("StreamSocket re-issues HELLOs audio first on a video reconfigure", "[stream_socket]") {
  StreamSocket server(1, kSockPath);
  REQUIRE(server.Start());

  codec_parameters_ptr apar = make_aac_parameters();
  server.SetAudioParams(apar.get());
  codec_parameters_ptr vpar = make_h264_parameters();
  server.SetVideoParams(vpar.get(), {0, 0});

  TestClient client;
  REQUIRE(client.Connect());
  ReceivedMessage m;
  REQUIRE(client.ReadMessage(m));  // audio HELLO
  REQUIRE(client.ReadMessage(m));  // video HELLO
  REQUIRE(m.header.stream == static_cast<uint8_t>(StreamId::Video));
  std::this_thread::sleep_for(std::chrono::milliseconds(50));

  // Video parameters change: both HELLOs are re-issued under generation 1,
  // the unchanged audio one first so the video HELLO completes the set.
  vpar->width = 1920;
  vpar->height = 1080;
  server.SetVideoParams(vpar.get(), {0, 0});

  REQUIRE(client.ReadMessage(m));
  REQUIRE(m.header.type == static_cast<uint8_t>(MessageType::Hello));
  REQUIRE(m.header.stream == static_cast<uint8_t>(StreamId::Audio));
  REQUIRE(m.header.generation == 1);
  REQUIRE(client.ReadMessage(m));
  REQUIRE(m.header.type == static_cast<uint8_t>(MessageType::Hello));
  REQUIRE(m.header.stream == static_cast<uint8_t>(StreamId::Video));
  REQUIRE(m.header.generation == 1);
  HelloInfo info;
  REQUIRE(ParseHello(m.payload.data(), m.payload.size(), info));
  REQUIRE(info.width == 1920);

  server.Stop();
}

TEST_CASE("StreamSocket::InvalidateKeyframe stops replaying a stale keyframe", "[stream_socket]") {
  StreamSocket server(1, kSockPath);
  REQUIRE(server.Start());

  codec_parameters_ptr par = make_h264_parameters();
  server.SetVideoParams(par.get(), {0, 0});

  av_packet_ptr keyframe = make_packet(500, 0x5A);
  server.SendMedia(keyframe.get(), StreamId::Video, true, 1000);

  // The capture source closes: the cached keyframe belongs to the old session
  server.InvalidateKeyframe();

  TestClient client;
  REQUIRE(client.Connect());
  std::this_thread::sleep_for(std::chrono::milliseconds(50));

  ReceivedMessage hello;
  REQUIRE(client.ReadMessage(hello));
  REQUIRE(hello.header.type == static_cast<uint8_t>(MessageType::Hello));

  // Nothing else is queued for the new consumer until fresh media arrives
  av_packet_ptr fresh = make_packet(100, 0x11);
  server.SendMedia(fresh.get(), StreamId::Video, false, 2000);

  ReceivedMessage next;
  REQUIRE(client.ReadMessage(next));
  REQUIRE(next.header.type == static_cast<uint8_t>(MessageType::Media));
  REQUIRE(next.header.pts_us == 2000);

  server.Stop();
}

TEST_CASE("StreamSocket bumps the generation when audio joins an announced video stream", "[stream_socket]") {
  StreamSocket server(1, kSockPath);
  REQUIRE(server.Start());

  // A complete video-only generation 0
  codec_parameters_ptr video = make_h264_parameters();
  server.SetVideoParams(video.get(), {0, 0});

  TestClient client;
  REQUIRE(client.Connect());
  ReceivedMessage message;
  REQUIRE(client.ReadMessage(message));
  REQUIRE(message.header.type == static_cast<uint8_t>(MessageType::Hello));
  REQUIRE(message.header.stream == static_cast<uint8_t>(StreamId::Video));
  REQUIRE(message.header.generation == 0);
  std::this_thread::sleep_for(std::chrono::milliseconds(50));

  // A re-prime finds audio for the first time. The video HELLO already
  // completed generation 0, so this is a new generation: audio HELLO first,
  // then the video HELLO re-issued to complete the new set.
  codec_parameters_ptr audio = make_aac_parameters();
  server.SetAudioParams(audio.get());

  REQUIRE(client.ReadMessage(message));
  REQUIRE(message.header.type == static_cast<uint8_t>(MessageType::Hello));
  REQUIRE(message.header.stream == static_cast<uint8_t>(StreamId::Audio));
  REQUIRE(message.header.generation == 1);

  REQUIRE(client.ReadMessage(message));
  REQUIRE(message.header.type == static_cast<uint8_t>(MessageType::Hello));
  REQUIRE(message.header.stream == static_cast<uint8_t>(StreamId::Video));
  REQUIRE(message.header.generation == 1);

  server.Stop();
}

TEST_CASE("StreamSocket::SetStreams keeps the initial announcement in generation 0", "[stream_socket]") {
  StreamSocket server(1, kSockPath);
  REQUIRE(server.Start());

  codec_parameters_ptr video = make_h264_parameters();
  codec_parameters_ptr audio = make_aac_parameters();
  server.SetStreams(video.get(), {25, 1}, audio.get());
  // Re-applying the same set (a camera reconnect with nothing changed) is a no-op
  server.SetStreams(video.get(), {25, 1}, audio.get());

  TestClient client;
  REQUIRE(client.Connect());
  ReceivedMessage message;
  REQUIRE(client.ReadMessage(message));
  REQUIRE(message.header.stream == static_cast<uint8_t>(StreamId::Audio));
  REQUIRE(message.header.generation == 0);
  REQUIRE(client.ReadMessage(message));
  REQUIRE(message.header.stream == static_cast<uint8_t>(StreamId::Video));
  REQUIRE(message.header.generation == 0);

  server.Stop();
}

TEST_CASE("StreamSocket::SetStreams changes both streams in one generation", "[stream_socket]") {
  StreamSocket server(1, kSockPath);
  REQUIRE(server.Start());

  codec_parameters_ptr video = make_h264_parameters();
  codec_parameters_ptr audio = make_aac_parameters();
  server.SetStreams(video.get(), {0, 0}, audio.get());

  TestClient client;
  REQUIRE(client.Connect());
  ReceivedMessage message;
  REQUIRE(client.ReadMessage(message));  // audio HELLO, generation 0
  REQUIRE(client.ReadMessage(message));  // video HELLO, generation 0
  REQUIRE(message.header.generation == 0);
  std::this_thread::sleep_for(std::chrono::milliseconds(50));

  // The camera comes back with a different resolution and sample rate
  video->width = 1920;
  video->height = 1080;
  audio->sample_rate = 8000;
  server.SetStreams(video.get(), {0, 0}, audio.get());

  // Exactly one new generation, audio first, and the pairing is consistent:
  // no intermediate generation that mixes new audio with old video.
  REQUIRE(client.ReadMessage(message));
  REQUIRE(message.header.stream == static_cast<uint8_t>(StreamId::Audio));
  REQUIRE(message.header.generation == 1);
  HelloInfo info;
  REQUIRE(ParseHello(message.payload.data(), message.payload.size(), info));
  REQUIRE(info.sample_rate == 8000);

  REQUIRE(client.ReadMessage(message));
  REQUIRE(message.header.stream == static_cast<uint8_t>(StreamId::Video));
  REQUIRE(message.header.generation == 1);
  REQUIRE(ParseHello(message.payload.data(), message.payload.size(), info));
  REQUIRE(info.width == 1920);

  // The next thing on the wire is media in generation 1, not another HELLO
  av_packet_ptr packet = make_packet(100, 0x33);
  server.SendMedia(packet.get(), StreamId::Video, false, 1);
  REQUIRE(client.ReadMessage(message));
  REQUIRE(message.header.type == static_cast<uint8_t>(MessageType::Media));
  REQUIRE(message.header.generation == 1);
  REQUIRE(message.header.sequence == 0);

  server.Stop();
}

TEST_CASE("StreamSocket::SetStreams drops a video stream the source no longer has", "[stream_socket]") {
  StreamSocket server(1, kSockPath);
  REQUIRE(server.Start());

  codec_parameters_ptr video = make_h264_parameters();
  codec_parameters_ptr audio = make_aac_parameters();
  server.SetStreams(video.get(), {0, 0}, audio.get());
  av_packet_ptr keyframe = make_packet(500, 0x5A);
  server.SendMedia(keyframe.get(), StreamId::Video, true, 1000);

  // Re-prime without video (null, or a stream with no codec id)
  codec_parameters_ptr none{avcodec_parameters_alloc()};
  none->codec_type = AVMEDIA_TYPE_VIDEO;
  none->codec_id = AV_CODEC_ID_NONE;
  server.SetStreams(none.get(), {0, 0}, audio.get());

  // A new consumer is told about audio only: no stale video HELLO, no keyframe
  TestClient client;
  REQUIRE(client.Connect());
  std::this_thread::sleep_for(std::chrono::milliseconds(50));
  ReceivedMessage message;
  REQUIRE(client.ReadMessage(message));
  REQUIRE(message.header.type == static_cast<uint8_t>(MessageType::Hello));
  REQUIRE(message.header.stream == static_cast<uint8_t>(StreamId::Audio));
  REQUIRE(message.header.generation == 1);

  // Video packets are no longer forwarded; audio still is
  av_packet_ptr packet = make_packet(100, 0x44);
  server.SendMedia(packet.get(), StreamId::Video, true, 2000);
  server.SendMedia(packet.get(), StreamId::Audio, false, 3000);
  REQUIRE(client.ReadMessage(message));
  REQUIRE(message.header.type == static_cast<uint8_t>(MessageType::Media));
  REQUIRE(message.header.stream == static_cast<uint8_t>(StreamId::Audio));
  REQUIRE(message.header.pts_us == 3000);

  server.Stop();
}

TEST_CASE("StreamSocket frames the snapshot with the generation and event sequence at connect", "[stream_socket]") {
  StreamSocket server(1, kSockPath);
  REQUIRE(server.Start());

  // Snapshot cached while generation 0 and no events produced yet
  server.SetSnapshotEvent(make_state_event(kEventSnapshot, 0, 0, "IDLE"));

  // Then the stream is reconfigured and two events go by with nobody listening
  codec_parameters_ptr video = make_h264_parameters();
  server.SetVideoParams(video.get(), {0, 0});
  video->width = 1280;
  server.SetVideoParams(video.get(), {0, 0});
  server.SendMonitorEvent(make_state_event(kEventConnectionFailed, 0, 0, ""));
  server.SendMonitorEvent(make_state_event(kEventConnectionRestored, 0, 0, ""));

  TestClient client;
  REQUIRE(client.Connect());
  ReceivedMessage message;
  REQUIRE(client.ReadMessage(message));  // video HELLO
  REQUIRE(message.header.generation == 1);

  REQUIRE(client.ReadMessage(message));
  REQUIRE(message.header.type == static_cast<uint8_t>(MessageType::Event));
  // Header reflects the moment of connect, not the moment the status last moved
  REQUIRE(message.header.generation == 1);
  REQUIRE(message.header.sequence == 2);

  server.Stop();
}
