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

#include "zm_config.h"
#include "zm_monitor.h"

#include <chrono>
#include <string>

namespace {

class LabelMonitor : public Monitor {
 public:
  LabelMonitor() : Monitor() {
    if (!config.font_file_location) config.font_file_location = "";
    if (!config.timestamp_code_char) config.timestamp_code_char = "%";
    name = "Cam";
  }
};

SystemTimePoint At(int64_t seconds, int64_t millis) {
  return SystemTimePoint(std::chrono::duration_cast<SystemTimePoint::duration>(
      std::chrono::seconds(seconds) + std::chrono::milliseconds(millis)));
}

}  // namespace

// Substitute() builds the label text; the returned string must carry its own length so
// callers using size(), empty() or comparisons see the text, not just c_str().
TEST_CASE("Monitor::Substitute returns the expanded label as a sized string", "[Monitor][Label]") {
  LabelMonitor monitor;
  const std::string text = monitor.Substitute("%N|%f|%", At(1000, 567));
  REQUIRE(text == "Cam|56|%");
  REQUIRE(text.size() == 8);
}

TEST_CASE("Monitor::Substitute passes plain text and strftime output through", "[Monitor][Label]") {
  LabelMonitor monitor;
  const std::string text = monitor.Substitute("%Y %N", At(0, 0));
  REQUIRE(text.size() >= 8);
  REQUIRE(text.substr(text.size() - 3) == "Cam");
}

TEST_CASE("Monitor::Substitute keeps a long strftime expansion", "[Monitor][Label]") {
  LabelMonitor monitor;
  std::string format;
  for (int i = 0; i < 20; i++) format += "%c ";
  const std::string text = monitor.Substitute(format, At(1000, 0));
  REQUIRE(text.size() > 300);
}
