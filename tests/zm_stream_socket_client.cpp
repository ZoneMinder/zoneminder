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

#include "zm_stream_socket.h"
#include "zm_stream_socket_client.h"
#include "zm_stream_socket_protocol.h"

#include <atomic>
#include <chrono>
#include <condition_variable>
#include <cstring>
#include <memory>
#include <mutex>
#include <sys/socket.h>
#include <thread>
#include <vector>

using namespace zm::stream_socket;

namespace {

constexpr char kSockPath[] = "/tmp/zm.stream_socket_client.unittest.sock";

struct AVCodecParametersDeleter {
  void operator()(AVCodecParameters *par) const { avcodec_parameters_free(&par); }
};
using codec_parameters_ptr = std::unique_ptr<AVCodecParameters, AVCodecParametersDeleter>;

// Collects callback invocations with waiting helpers
struct Collector {
  std::mutex mutex;
  std::condition_variable cv;
  std::vector<std::pair<StreamId, HelloInfo>> hellos;
  std::vector<std::pair<Header, std::vector<uint8_t>>> media;
  int byes = 0;
  int disconnects = 0;

  StreamSocketClient::Callbacks MakeCallbacks() {
    StreamSocketClient::Callbacks callbacks;
    callbacks.on_hello = [this](StreamId stream, const HelloInfo &info, uint32_t) {
      std::lock_guard<std::mutex> lock(mutex);
      hellos.emplace_back(stream, info);
      cv.notify_all();
    };
    callbacks.on_media = [this](const Header &header, const uint8_t *data, size_t size) {
      std::lock_guard<std::mutex> lock(mutex);
      media.emplace_back(header, std::vector<uint8_t>(data, data + size));
      cv.notify_all();
    };
    callbacks.on_bye = [this]() {
      std::lock_guard<std::mutex> lock(mutex);
      ++byes;
      cv.notify_all();
    };
    callbacks.on_disconnect = [this]() {
      std::lock_guard<std::mutex> lock(mutex);
      ++disconnects;
      cv.notify_all();
    };
    return callbacks;
  }

  template <typename Pred>
  bool WaitFor(Pred pred, std::chrono::milliseconds timeout = std::chrono::seconds(5)) {
    std::unique_lock<std::mutex> lock(mutex);
    return cv.wait_for(lock, timeout, pred);
  }
};

av_packet_ptr make_packet(size_t size, uint8_t fill) {
  av_packet_ptr packet{av_packet_alloc()};
  REQUIRE(av_new_packet(packet.get(), size) == 0);
  memset(packet->data, fill, size);
  return packet;
}

}  // namespace

TEST_CASE("StreamSocketClient receives HELLO and media end to end", "[stream_socket_client]") {
  StreamSocket server(7, kSockPath);
  REQUIRE(server.Start());

  codec_parameters_ptr par{avcodec_parameters_alloc()};
  par->codec_type = AVMEDIA_TYPE_VIDEO;
  par->codec_id = AV_CODEC_ID_HEVC;
  par->width = 2560;
  par->height = 1440;
  server.SetVideoParams(par.get(), {20, 1});

  Collector collector;
  StreamSocketClient client(std::string(kSockPath), collector.MakeCallbacks());

  REQUIRE(collector.WaitFor([&] { return !collector.hellos.empty(); }));
  {
    std::lock_guard<std::mutex> lock(collector.mutex);
    REQUIRE(collector.hellos[0].first == StreamId::Video);
    REQUIRE(collector.hellos[0].second.codec_id == AV_CODEC_ID_HEVC);
    REQUIRE(collector.hellos[0].second.width == 2560);
    REQUIRE(collector.hellos[0].second.fps_num == 20);
  }

  // wait until the server has the client registered, then send media
  std::this_thread::sleep_for(std::chrono::milliseconds(100));
  av_packet_ptr packet = make_packet(4096, 0xC3);
  server.SendMedia(packet.get(), StreamId::Video, true, 777000);

  REQUIRE(collector.WaitFor([&] { return !collector.media.empty(); }));
  {
    std::lock_guard<std::mutex> lock(collector.mutex);
    const Header &header = collector.media[0].first;
    REQUIRE(header.type == static_cast<uint8_t>(MessageType::Media));
    REQUIRE(header.flags == kFlagKeyframe);
    REQUIRE(header.pts_us == 777000);
    REQUIRE(collector.media[0].second.size() == 4096);
    REQUIRE(collector.media[0].second[100] == 0xC3);
  }

  client.Stop();
  server.Stop();
}

TEST_CASE("StreamSocketClient gets BYE on server stop and reconnects", "[stream_socket_client]") {
  auto server = std::make_unique<StreamSocket>(7, kSockPath);
  REQUIRE(server->Start());

  codec_parameters_ptr par{avcodec_parameters_alloc()};
  par->codec_type = AVMEDIA_TYPE_VIDEO;
  par->codec_id = AV_CODEC_ID_H264;
  server->SetVideoParams(par.get(), {0, 0});

  Collector collector;
  StreamSocketClient client(std::string(kSockPath), collector.MakeCallbacks());

  REQUIRE(collector.WaitFor([&] { return collector.hellos.size() >= 1; }));

  server->Stop();
  REQUIRE(collector.WaitFor([&] { return collector.byes >= 1 && collector.disconnects >= 1; }));

  // Producer comes back (zmc restart): client reconnects and gets a new HELLO
  server = std::make_unique<StreamSocket>(7, kSockPath);
  REQUIRE(server->Start());
  server->SetVideoParams(par.get(), {0, 0});

  REQUIRE(collector.WaitFor([&] { return collector.hellos.size() >= 2; },
                            std::chrono::seconds(10)));

  client.Stop();
  server->Stop();
}

TEST_CASE("StreamSocketClient handles fragmented delivery", "[stream_socket_client]") {
  int fds[2];
  REQUIRE(socketpair(AF_UNIX, SOCK_STREAM, 0, fds) == 0);

  Collector collector;
  StreamSocketClient client(fds[0], collector.MakeCallbacks());

  // Build one MEDIA message and dribble it through in small fragments
  std::vector<uint8_t> payload(300, 0x5e);
  Header header = {};
  header.length = kHeaderLengthBytes + payload.size();
  header.version = kProtocolVersion;
  header.type = static_cast<uint8_t>(MessageType::Media);
  header.stream = static_cast<uint8_t>(StreamId::Audio);
  header.sequence = 9;
  header.pts_us = 42;
  uint8_t wire[kHeaderSize];
  SerializeHeader(header, wire);

  std::vector<uint8_t> message(wire, wire + kHeaderSize);
  message.insert(message.end(), payload.begin(), payload.end());
  for (size_t pos = 0; pos < message.size(); pos += 7) {
    size_t chunk = std::min<size_t>(7, message.size() - pos);
    REQUIRE(::send(fds[1], message.data() + pos, chunk, 0) == static_cast<ssize_t>(chunk));
    std::this_thread::sleep_for(std::chrono::milliseconds(1));
  }

  REQUIRE(collector.WaitFor([&] { return !collector.media.empty(); }));
  {
    std::lock_guard<std::mutex> lock(collector.mutex);
    REQUIRE(collector.media.size() == 1);
    REQUIRE(collector.media[0].first.sequence == 9);
    REQUIRE(collector.media[0].second == payload);
  }

  ::close(fds[1]);
  REQUIRE(collector.WaitFor([&] { return collector.disconnects >= 1; }));
  client.Stop();
}

TEST_CASE("StreamSocketClient disconnects on malformed input", "[stream_socket_client]") {
  int fds[2];
  REQUIRE(socketpair(AF_UNIX, SOCK_STREAM, 0, fds) == 0);

  Collector collector;
  StreamSocketClient client(fds[0], collector.MakeCallbacks());

  // Garbage: invalid version byte in an otherwise plausible header
  std::vector<uint8_t> garbage(kHeaderSize, 0xFF);
  REQUIRE(::send(fds[1], garbage.data(), garbage.size(), 0)
          == static_cast<ssize_t>(garbage.size()));

  REQUIRE(collector.WaitFor([&] { return collector.disconnects >= 1; }));
  {
    std::lock_guard<std::mutex> lock(collector.mutex);
    REQUIRE(collector.media.empty());
    REQUIRE(collector.hellos.empty());
  }
  ::close(fds[1]);
  client.Stop();
}

TEST_CASE("StreamSocketClient skips unknown message types", "[stream_socket_client]") {
  int fds[2];
  REQUIRE(socketpair(AF_UNIX, SOCK_STREAM, 0, fds) == 0);

  Collector collector;
  StreamSocketClient client(fds[0], collector.MakeCallbacks());

  // Unknown type 0x7f with a small payload, then a valid MEDIA
  Header header = {};
  header.length = kHeaderLengthBytes + 4;
  header.version = kProtocolVersion;
  header.type = 0x7f;
  uint8_t wire[kHeaderSize];
  SerializeHeader(header, wire);
  std::vector<uint8_t> message(wire, wire + kHeaderSize);
  message.insert(message.end(), {1, 2, 3, 4});

  header.type = static_cast<uint8_t>(MessageType::Media);
  header.length = kHeaderLengthBytes + 2;
  SerializeHeader(header, wire);
  message.insert(message.end(), wire, wire + kHeaderSize);
  message.insert(message.end(), {0xAA, 0xBB});

  REQUIRE(::send(fds[1], message.data(), message.size(), 0)
          == static_cast<ssize_t>(message.size()));

  REQUIRE(collector.WaitFor([&] { return !collector.media.empty(); }));
  {
    std::lock_guard<std::mutex> lock(collector.mutex);
    REQUIRE(collector.media.size() == 1);
    REQUIRE(collector.media[0].second == std::vector<uint8_t>({0xAA, 0xBB}));
  }
  ::close(fds[1]);
  client.Stop();
}
