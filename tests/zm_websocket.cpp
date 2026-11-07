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

#include "zm_websocket.h"

#include "zm_config.h"
#include "zm_image.h"

extern "C" {
#include <libavutil/imgutils.h>
}

#include <cerrno>
#include <csignal>
#include <cstring>
#include <memory>
#include <sys/socket.h>
#include <thread>
#include <unistd.h>

TEST_CASE("Websocket accept key uses RFC6455 example") {
  REQUIRE(
      zm::websocket::ComputeAcceptKey("dGhlIHNhbXBsZSBub25jZQ==") ==
      "s3pPLMBiTxaQ9kYGzzhZRbK+xOo=");
}

TEST_CASE("Websocket handshake extracts client key") {
  const std::string request =
      "GET / HTTP/1.1\r\n"
      "Host: localhost:30001\r\n"
      "Upgrade: websocket\r\n"
      "Connection: Upgrade\r\n"
      "Sec-WebSocket-Key: dGhlIHNhbXBsZSBub25jZQ==\r\n"
      "Sec-WebSocket-Version: 13\r\n\r\n";

  std::string client_key;
  REQUIRE(zm::websocket::ExtractHandshakeKey(request, &client_key));
  REQUIRE(client_key == "dGhlIHNhbXBsZSBub25jZQ==");
}

TEST_CASE("Websocket handshake extracts request target") {
  const std::string request =
      "GET /socket?token=abc123 HTTP/1.1\r\n"
      "Host: localhost:30001\r\n\r\n";

  std::string target;
  REQUIRE(zm::websocket::ExtractHandshakeRequestTarget(request, &target));
  REQUIRE(target == "/socket?token=abc123");
}

TEST_CASE("Websocket handshake rejects non-GET request lines") {
  std::string target;
  REQUIRE_FALSE(
      zm::websocket::ExtractHandshakeRequestTarget(
          "POST /socket HTTP/1.1\r\nHost: localhost\r\n\r\n",
          &target));
  REQUIRE_FALSE(
      zm::websocket::ExtractHandshakeRequestTarget(
          "GET /socket\r\nHost: localhost\r\n\r\n",
          &target));
}

TEST_CASE("Websocket handshake accepts case-insensitive header names") {
  const std::string request =
      "GET / HTTP/1.1\r\n"
      "Host: localhost:30001\r\n"
      "upgrade: websocket\r\n"
      "connection: Upgrade\r\n"
      "sec-websocket-key: dGhlIHNhbXBsZSBub25jZQ==\r\n"
      "sec-websocket-version: 13\r\n\r\n";

  std::string client_key;
  REQUIRE(zm::websocket::ExtractHandshakeKey(request, &client_key));
  REQUIRE(client_key == "dGhlIHNhbXBsZSBub25jZQ==");
}

TEST_CASE("Websocket handshake extracts bearer token from authorization header") {
  const std::string request =
      "GET /socket HTTP/1.1\r\n"
      "Host: localhost:30001\r\n"
      "Authorization: Bearer abc.def.ghi\r\n\r\n";

  std::string token;
  REQUIRE(zm::websocket::ExtractAuthorizationBearerToken(request, &token));
  REQUIRE(token == "abc.def.ghi");
}

TEST_CASE("Websocket encodes server text frames") {
  const std::string frame = zm::websocket::EncodeFrame(zm::websocket::Opcode::TEXT, "hello");

  REQUIRE(frame.size() == 7);
  REQUIRE(static_cast<unsigned char>(frame[0]) == 0x81);
  REQUIRE(static_cast<unsigned char>(frame[1]) == 0x05);
  REQUIRE(frame.substr(2) == "hello");
}

TEST_CASE("Websocket encodes extended 16-bit payload length") {
  const std::string payload(126, 'a');
  const std::string frame = zm::websocket::EncodeFrame(zm::websocket::Opcode::TEXT, payload);

  REQUIRE(frame.size() == payload.size() + 4);
  REQUIRE(static_cast<unsigned char>(frame[0]) == 0x81);
  REQUIRE(static_cast<unsigned char>(frame[1]) == 126);
  REQUIRE(static_cast<unsigned char>(frame[2]) == 0x00);
  REQUIRE(static_cast<unsigned char>(frame[3]) == 126);
  REQUIRE(frame.substr(4) == payload);
}

TEST_CASE("Websocket encodes extended 64-bit payload length") {
  const std::string payload(65536, 'b');
  const std::string frame = zm::websocket::EncodeFrame(zm::websocket::Opcode::TEXT, payload);

  REQUIRE(frame.size() == payload.size() + 10);
  REQUIRE(static_cast<unsigned char>(frame[0]) == 0x81);
  REQUIRE(static_cast<unsigned char>(frame[1]) == 127);
  for (int i = 2; i < 7; ++i) {
    REQUIRE(static_cast<unsigned char>(frame[i]) == 0x00);
  }
  REQUIRE(static_cast<unsigned char>(frame[7]) == 0x01);
  REQUIRE(static_cast<unsigned char>(frame[8]) == 0x00);
  REQUIRE(static_cast<unsigned char>(frame[9]) == 0x00);
  REQUIRE(frame.substr(10) == payload);
}

TEST_CASE("Websocket decodes masked client text frames") {
  const std::string frame(
      "\x81\x82\x37\xfa\x21\x3d\x7f\x93",
      8);

  zm::websocket::Frame decoded;
  size_t consumed = 0;
  REQUIRE(zm::websocket::DecodeFrame(frame, &decoded, &consumed) == zm::websocket::DecodeResult::OK);
  REQUIRE(consumed == frame.size());
  REQUIRE(decoded.opcode == zm::websocket::Opcode::TEXT);
  REQUIRE(decoded.masked == true);
  REQUIRE(decoded.payload == "Hi");
}

TEST_CASE("Websocket decodes 16-bit masked client text frames") {
  std::string frame;
  const std::string payload(126, 'x');
  frame.push_back(static_cast<char>(0x81));
  frame.push_back(static_cast<char>(0x80 | 126));
  frame.push_back(static_cast<char>(0x00));
  frame.push_back(static_cast<char>(126));
  frame.append("\x01\x02\x03\x04", 4);
  for (size_t i = 0; i < payload.size(); ++i) {
    frame.push_back(static_cast<char>(payload[i] ^ "\x01\x02\x03\x04"[i % 4]));
  }

  zm::websocket::Frame decoded;
  size_t consumed = 0;
  REQUIRE(zm::websocket::DecodeFrame(frame, &decoded, &consumed) == zm::websocket::DecodeResult::OK);
  REQUIRE(consumed == frame.size());
  REQUIRE(decoded.payload == payload);
}

TEST_CASE("Websocket decode reports bytes consumed for concatenated frames") {
  const std::string frame_a(
      "\x81\x82\x37\xfa\x21\x3d\x7f\x93",
      8);
  const std::string frame_b(
      "\x81\x82\x37\xfa\x21\x3d\x7e\x92",
      8);
  const std::string combined = frame_a + frame_b;

  zm::websocket::Frame decoded;
  size_t consumed = 0;
  REQUIRE(zm::websocket::DecodeFrame(combined, &decoded, &consumed) == zm::websocket::DecodeResult::OK);
  REQUIRE(consumed == frame_a.size());
  REQUIRE(decoded.payload == "Hi");
}

TEST_CASE("Websocket rejects unmasked client frames") {
  zm::websocket::Frame decoded;
  size_t consumed = 0;
  REQUIRE(
      zm::websocket::DecodeFrame("\x81\x02Hi", &decoded, &consumed) ==
      zm::websocket::DecodeResult::ERROR);
}

TEST_CASE("Websocket rejects oversized control frames") {
  std::string frame;
  frame.push_back(static_cast<char>(0x89));
  frame.push_back(static_cast<char>(0x80 | 126));
  frame.push_back(static_cast<char>(0x00));
  frame.push_back(static_cast<char>(126));
  frame.append("\x01\x02\x03\x04", 4);
  frame.append(126, 'p');

  zm::websocket::Frame decoded;
  size_t consumed = 0;
  REQUIRE(zm::websocket::DecodeFrame(frame, &decoded, &consumed) == zm::websocket::DecodeResult::ERROR);
}

TEST_CASE("Websocket decoder reports incomplete frames") {
  zm::websocket::Frame decoded;
  size_t consumed = 0;
  REQUIRE(
      zm::websocket::DecodeFrame("\x81", &decoded, &consumed) ==
      zm::websocket::DecodeResult::INCOMPLETE);
}

TEST_CASE("Websocket monitor streaming port uses configured base port") {
  REQUIRE(zm::websocket::MonitorWebSocketPort(30000, 5) == 30005);
  REQUIRE(zm::websocket::MonitorWebSocketPort(0, 5) == 0);
}

TEST_CASE("Websocket monitor port stays inside the TCP port range") {
  REQUIRE(zm::websocket::MonitorWebSocketPort(65000, 535) == 65535);
  // Past 65535 the service string would wrap or name an ephemeral port.
  REQUIRE(zm::websocket::MonitorWebSocketPort(65000, 536) == 0);
  REQUIRE(zm::websocket::MonitorWebSocketPort(30000, 100000) == 0);
  REQUIRE(zm::websocket::MonitorWebSocketPort(70000, 0) == 0);
  REQUIRE(zm::websocket::MonitorWebSocketPort(-1, 5) == 0);
  REQUIRE(zm::websocket::MonitorWebSocketPort(65535, 0) == 65535);
}

TEST_CASE("Websocket worker thread survives writing to a closed peer") {
  // The TLS backends write to the socket themselves, without MSG_NOSIGNAL, so a
  // client that goes away during TLS I/O raised SIGPIPE and killed zmc.
  int fds[2];
  REQUIRE(socketpair(AF_UNIX, SOCK_STREAM, 0, fds) == 0);
  close(fds[1]);
  ssize_t written = 0;
  int write_errno = 0;
  bool still_blocked = false;
  std::thread worker([&] {
    zm::websocket::BlockSigpipeInThisThread();
    const char byte = 'x';
    written = ::write(fds[0], &byte, 1);
    write_errno = errno;
    sigset_t current;
    pthread_sigmask(SIG_BLOCK, nullptr, &current);
    still_blocked = sigismember(&current, SIGPIPE) == 1;
  });
  worker.join();
  close(fds[0]);
  REQUIRE(written == -1);
  REQUIRE(write_errno == EPIPE);
  REQUIRE(still_blocked);
  // Other threads keep their own mask.
  sigset_t main_mask;
  pthread_sigmask(SIG_BLOCK, nullptr, &main_mask);
  REQUIRE(sigismember(&main_mask, SIGPIPE) == 0);
}

TEST_CASE("Websocket send queue takes a frame bigger than its limit") {
  const size_t limit = 8 * 1024 * 1024;
  // A 2560x1440 rgba frame is 14.7 MB, queued after its small metadata frame.
  REQUIRE(zm::websocket::SendQueueHasRoom(0, limit));
  REQUIRE(zm::websocket::SendQueueHasRoom(200, limit));
  // A client already this far behind is not keeping up.
  REQUIRE_FALSE(zm::websocket::SendQueueHasRoom(limit, limit));
  REQUIRE_FALSE(zm::websocket::SendQueueHasRoom(200 + 14745600, limit));
}

namespace {

// A YUV420P image of one colour, laid out as Image lays it out (align 32).
Image *solid_yuv420p_image(int w, int h, uint8_t y, uint8_t u, uint8_t v) {
  config.font_file_location = "data/fonts/04_valid.zmfnt";
  Image *image = new Image(w, h, ZM_COLOUR_GRAY8, ZM_SUBPIX_ORDER_YUV420P);
  uint8_t *data[4];
  int stride[4];
  av_image_fill_arrays(data, stride, image->Buffer(), AV_PIX_FMT_YUV420P, w, h, 32);
  memset(data[0], y, static_cast<size_t>(stride[0]) * h);
  memset(data[1], u, static_cast<size_t>(stride[1]) * ((h + 1) / 2));
  memset(data[2], v, static_cast<size_t>(stride[2]) * ((h + 1) / 2));
  return image;
}

}  // namespace

TEST_CASE("Websocket raw payloads keep the colour of a planar image") {
  // Colours() reports a planar YUV image as ZM_COLOUR_GRAY8, so the rgba
  // payload came out grey, and the yuv420p one was converted from the Y plane
  // alone. Red in BT.601 limited range is Y 81, U 90, V 240.
  const int w = 40, h = 30;
  std::unique_ptr<Image> image(solid_yuv420p_image(w, h, 81, 90, 240));

  SECTION("rgba") {
    std::string out;
    uint32_t line_size = 0;
    REQUIRE(zm::websocket::EncodeRawImage(*image, AV_PIX_FMT_RGBA, 32, &out, &line_size));
    REQUIRE(line_size == static_cast<uint32_t>(FFALIGN(w * 4, 32)));
    REQUIRE(out.size() == static_cast<size_t>(line_size) * h);
    const uint8_t *pixel = reinterpret_cast<const uint8_t *>(out.data()) + 15 * line_size + 20 * 4;
    REQUIRE(pixel[0] > 230);
    REQUIRE(pixel[1] < 25);
    REQUIRE(pixel[2] < 25);
    REQUIRE(pixel[3] == 0xff);
  }

  SECTION("yuv420p, packed") {
    std::string out;
    uint32_t line_size = 0;
    REQUIRE(zm::websocket::EncodeRawImage(*image, AV_PIX_FMT_YUV420P, 1, &out, &line_size));
    REQUIRE(line_size == static_cast<uint32_t>(w));
    REQUIRE(out.size() == static_cast<size_t>(w * h + 2 * (w / 2) * (h / 2)));
    const uint8_t *bytes = reinterpret_cast<const uint8_t *>(out.data());
    REQUIRE(bytes[0] == 81);
    REQUIRE(bytes[w * h] == 90);
    REQUIRE(bytes[w * h + (w / 2) * (h / 2)] == 240);
    REQUIRE(bytes[out.size() - 1] == 240);
  }
}

TEST_CASE("Websocket raw payloads read a packed image by its own stride") {
  config.font_file_location = "data/fonts/04_valid.zmfnt";
  const int w = 10, h = 4;
  Image image(w, h, ZM_COLOUR_RGB24, ZM_SUBPIX_ORDER_RGB);
  for (int y = 0; y < h; y++) {
    uint8_t *row = image.Buffer() + y * image.LineSize();
    for (int x = 0; x < w; x++) {
      row[x * 3] = static_cast<uint8_t>(y * 50);
      row[x * 3 + 1] = 0;
      row[x * 3 + 2] = 200;
    }
  }
  std::string out;
  uint32_t line_size = 0;
  REQUIRE(zm::websocket::EncodeRawImage(image, AV_PIX_FMT_RGBA, 32, &out, &line_size));
  for (int y = 0; y < h; y++) {
    const uint8_t *pixel = reinterpret_cast<const uint8_t *>(out.data()) + y * line_size + 7 * 4;
    REQUIRE(pixel[0] == y * 50);
    REQUIRE(pixel[2] == 200);
  }
}
