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
