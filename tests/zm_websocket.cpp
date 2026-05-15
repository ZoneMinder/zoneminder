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
  REQUIRE(zm::websocket::MonitorStreamingPort(30000, 5) == 30005);
  REQUIRE(zm::websocket::MonitorStreamingPort(0, 5) == 0);
}
