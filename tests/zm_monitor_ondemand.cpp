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

namespace {

using Step = Monitor::OndemandStep;

void EnsureConfig() {
  if (!config.font_file_location) config.font_file_location = "";
  if (!config.event_close_mode) config.event_close_mode = "idle";
}

class WakeMonitor : public Monitor {
 public:
  void WakeUntil(std::chrono::steady_clock::time_point until) {
    linked_wake_until_ =
      std::chrono::duration_cast<std::chrono::nanoseconds>(until.time_since_epoch()).count();
  }
};

}  // namespace

// An on-demand monitor with LinkedMonitors only noticed the link while it was
// capturing, and it stopped capturing 10 s after the last viewer: every later
// alarm on the linked monitor was missed. It now opens the camera when a link
// alarms, and keeps capturing through the alarm it causes.
TEST_CASE("On-demand capture step", "[Monitor]") {
  int link_checks = 0;
  auto link = [&link_checks](bool alarmed) {
    return [&link_checks, alarmed]() { link_checks++; return alarmed; };
  };

  SECTION("watched: capture, opening the camera if it is closed") {
    REQUIRE(Monitor::DecideOndemand(true, true, true, false, link(false)) == Step::CAPTURE);
    REQUIRE(Monitor::DecideOndemand(true, true, false, false, link(false)) == Step::PLAY);
  }
  SECTION("no image yet: capture one for thumbnails") {
    REQUIRE(Monitor::DecideOndemand(false, false, true, false, link(false)) == Step::CAPTURE);
    REQUIRE(Monitor::DecideOndemand(false, false, false, false, link(false)) == Step::PLAY);
  }
  SECTION("unwatched and idle: pause, then stay paused") {
    REQUIRE(Monitor::DecideOndemand(false, true, true, false, link(false)) == Step::PAUSE);
    REQUIRE(Monitor::DecideOndemand(false, true, false, false, link(false)) == Step::SLEEP);
  }
  SECTION("unwatched but alarmed or woken: keep capturing") {
    REQUIRE(Monitor::DecideOndemand(false, true, true, true, link(false)) == Step::CAPTURE);
  }
  SECTION("paused and a linked monitor alarms: open the camera") {
    REQUIRE(Monitor::DecideOndemand(false, true, false, false, link(true)) == Step::PLAY);
  }
  SECTION("links are read only while the camera is closed") {
    // With the camera open the analysis thread evaluates them.
    Monitor::DecideOndemand(false, true, true, false, link(true));
    Monitor::DecideOndemand(true, true, true, false, link(true));
    Monitor::DecideOndemand(true, true, false, false, link(true));
    REQUIRE(link_checks == 0);
  }
}

TEST_CASE("A link wake lasts until acted on or timed out", "[Monitor]") {
  EnsureConfig();
  WakeMonitor monitor;
  REQUIRE_FALSE(monitor.LinkedWakePending());

  monitor.WakeUntil(std::chrono::steady_clock::now() + Monitor::kLinkedWakeTimeout);
  REQUIRE(monitor.LinkedWakePending());

  monitor.WakeUntil(std::chrono::steady_clock::now() - std::chrono::seconds(1));
  REQUIRE_FALSE(monitor.LinkedWakePending());
}
