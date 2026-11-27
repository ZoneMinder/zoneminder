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
#include "zm_amcrest_parse.h"

// What /cgi-bin/eventManager.cgi?action=attach sends: each part has headers,
// a blank line and the message. The next boundary only comes with the next part.
static const std::string kStart =
  "--myboundary\r\n"
  "Content-Type: text/plain\r\n"
  "Content-Length:37\r\n"
  "\r\n"
  "Code=VideoMotion;action=Start;index=0\r\n"
  "\r\n";
static const std::string kStop =
  "--myboundary\r\n"
  "Content-Type: text/plain\r\n"
  "Content-Length:36\r\n"
  "\r\n"
  "Code=VideoMotion;action=Stop;index=0\r\n"
  "\r\n";
static const std::string kHeartbeat =
  "--myboundary\r\n"
  "Content-Type: text/plain\r\n"
  "Content-Length:9\r\n"
  "\r\n"
  "Heartbeat\r\n"
  "\r\n";

TEST_CASE("Amcrest: the message is found after the part headers", "[Amcrest]") {
  REQUIRE(zm::amcrest::GetCommand(
      "Content-Type: text/plain\r\nContent-Length:37\r\n\r\nCode=VideoMotion;action=Start;index=0\r\n")
      == "Code=VideoMotion;action=Start;index=0");
  REQUIRE(zm::amcrest::GetCommand("Content-Type: text/plain\r\n\r\nHeartbeat\r\n") == "Heartbeat");
  REQUIRE(zm::amcrest::GetCommand("Code=VideoMotion;action=Stop;index=0") == "Code=VideoMotion;action=Stop;index=0");
}

TEST_CASE("Amcrest: an event is reported before the next boundary arrives", "[Amcrest]") {
  std::string buffer = kStart;
  std::string command;
  REQUIRE(zm::amcrest::NextEvent(buffer, command));
  REQUIRE(command == "Code=VideoMotion;action=Start;index=0");
  REQUIRE_FALSE(zm::amcrest::NextEvent(buffer, command));
}

TEST_CASE("Amcrest: several parts in one read come out in order", "[Amcrest]") {
  std::string buffer = kHeartbeat + kStart + kStop;
  std::string command;
  REQUIRE(zm::amcrest::NextEvent(buffer, command));
  REQUIRE(command == "Heartbeat");
  REQUIRE(zm::amcrest::NextEvent(buffer, command));
  REQUIRE(command == "Code=VideoMotion;action=Start;index=0");
  REQUIRE(zm::amcrest::NextEvent(buffer, command));
  REQUIRE(command == "Code=VideoMotion;action=Stop;index=0");
  REQUIRE_FALSE(zm::amcrest::NextEvent(buffer, command));
}

TEST_CASE("Amcrest: a part split across reads is kept until it is complete", "[Amcrest]") {
  std::string stream = kStart + kStop;
  // Split at every position: the two events must come out exactly once each.
  for (size_t cut = 1; cut < stream.size(); cut++) {
    std::string buffer;
    std::string command;
    std::vector<std::string> got;
    buffer += stream.substr(0, cut);
    while (zm::amcrest::NextEvent(buffer, command)) got.push_back(command);
    buffer += stream.substr(cut);
    while (zm::amcrest::NextEvent(buffer, command)) got.push_back(command);
    INFO("cut at " << cut);
    REQUIRE(got.size() == 2);
    REQUIRE(got[0] == "Code=VideoMotion;action=Start;index=0");
    REQUIRE(got[1] == "Code=VideoMotion;action=Stop;index=0");
  }
}

TEST_CASE("Amcrest: data without a boundary is dropped", "[Amcrest]") {
  std::string buffer = "garbage";
  std::string command;
  REQUIRE_FALSE(zm::amcrest::NextEvent(buffer, command));
  REQUIRE(buffer.empty());
}
