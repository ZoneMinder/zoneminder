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
#include "zm_image.h"
#include "zm_monitor.h"
#include "zm_rgb.h"
#include "zm_zone.h"

#include <memory>
#include <vector>

// AnalysisSource=Secondary runs motion detection on the substream at its native
// size, which is smaller than the frame. Zones are rebuilt at that size while
// what masks or is drawn on the captured frame stays at frame size.
namespace {

class AnalysisMonitor : public Monitor {
 public:
  AnalysisMonitor(unsigned int w, unsigned int h) : Monitor() {
    width = w;
    height = h;
    colours = ZM_COLOUR_GRAY8;
    savejpegs = 0;
  }
  void SetAnalysisSize(unsigned int w, unsigned int h) {
    analysis_width = w;
    analysis_height = h;
  }
  // The zones hold the monitor; drop them so neither leaks.
  void ClearZones() { zones.clear(); }
};

std::shared_ptr<AnalysisMonitor> MakeMonitor(unsigned int w, unsigned int h) {
  if (!config.font_file_location) config.font_file_location = "";
  if (!config.event_close_mode) config.event_close_mode = "idle";
  return std::make_shared<AnalysisMonitor>(w, h);
}

}  // namespace

TEST_CASE("Monitor: the analysis size is the frame size until one is set", "[Monitor][SecondaryAnalysis]") {
  auto monitor = MakeMonitor(64, 48);
  REQUIRE(monitor->AnalysisWidth() == 64);
  REQUIRE(monitor->AnalysisHeight() == 48);

  monitor->SetAnalysisSize(32, 24);
  REQUIRE(monitor->AnalysisWidth() == 32);
  REQUIRE(monitor->AnalysisHeight() == 24);
}
