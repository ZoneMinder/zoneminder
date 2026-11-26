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

#include <filesystem>
#include <fstream>
#include <sstream>
#include <string>
#include <vector>

TEST_CASE("Monitor::DumpSettings labels the camera type from the monitor type", "[Monitor]") {
  std::ifstream input(std::filesystem::path(ZM_SOURCE_DIR) / "src" / "zm_monitor.cpp");
  REQUIRE(input.is_open());
  std::ostringstream buffer;
  buffer << input.rdbuf();
  const std::string source = buffer.str();

  const size_t line = source.find("result += stringtf(\"Type : %s\\n\"");
  REQUIRE(line != std::string::npos);
  const std::string text = source.substr(line, source.find('\n', line) - line);
  // The old Local/Remote/File ternary reported Ffmpeg and VNC monitors as File.
  REQUIRE(text.find("CameraType_Strings[type]") != std::string::npos);
}

// Orientation swaps the monitor's width and height after capture, so a camera has to be
// built with the size it captures at, as every other camera type in LoadCamera is.
TEST_CASE("Monitor::LoadCamera builds the VNC camera with the unrotated size", "[Monitor]") {
  std::ifstream input(std::filesystem::path(ZM_SOURCE_DIR) / "src" / "zm_monitor.cpp");
  REQUIRE(input.is_open());
  std::ostringstream buffer;
  buffer << input.rdbuf();
  const std::string source = buffer.str();

  const size_t call = source.find("make_unique<VncCamera>(");
  REQUIRE(call != std::string::npos);
  const size_t open = source.find('(', call + std::string("make_unique<VncCamera>").size());
  const std::string args = source.substr(open + 1, source.find(");", open) - open - 1);

  std::vector<std::string> names;
  std::istringstream list(args);
  for (std::string arg; std::getline(list, arg, ',');) {
    const size_t first = arg.find_first_not_of(" \t\r\n");
    const size_t last = arg.find_last_not_of(" \t\r\n");
    names.push_back(first == std::string::npos ? "" : arg.substr(first, last - first + 1));
  }
  // this, host, port, user, pass, width, height, colours
  REQUIRE(names.size() > 6);
  REQUIRE(names[5] == "camera_width");
  REQUIRE(names[6] == "camera_height");
}
