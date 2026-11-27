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

TEST_CASE("Monitor::DetectMotion logs the no-zones warning once, not per frame", "[Monitor]") {
  std::ifstream input(std::filesystem::path(ZM_SOURCE_DIR) / "src" / "zm_monitor.cpp");
  REQUIRE(input.is_open());
  std::ostringstream buffer;
  buffer << input.rdbuf();
  const std::string source = buffer.str();

  const std::string start = "if (zones.empty()) {";
  const size_t block = source.find(start, source.find("unsigned int Monitor::DetectMotion("));
  REQUIRE(block != std::string::npos);
  const std::string body = source.substr(block, source.find("return 0;", block) - block);

  const size_t warning = body.find("Warning(\"No zones to check!\")");
  REQUIRE(warning != std::string::npos);
  // The warning must sit behind a once-per-state guard, not run on every analysed frame.
  REQUIRE(body.find("if (!no_zones_warned)") < warning);
  REQUIRE(body.find("no_zones_warned = true;") != std::string::npos);
}
