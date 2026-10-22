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

#include <memory>

namespace {

void EnsureConfig() {
  if (!config.font_file_location) config.font_file_location = "";
  if (!config.event_close_mode) config.event_close_mode = "idle";
}

// The linked (source) monitor. Its shared data is a local struct here.
class SourceMonitor : public Monitor {
 public:
  using Data = SharedData;
};

class TestLink : public Monitor::MonitorLink {
 public:
  explicit TestLink(const std::shared_ptr<Monitor> &source) : MonitorLink(source, 0) {}
  void Attach(SourceMonitor::Data *data) {
    shared_data = data;
    connected = true;
  }
};

}  // namespace

// A link reports the source monitor's alarm status: its frame score while it
// is in ALARM, and 1 in ALERT so that a linked event stays open through the
// source's post-event frames. A new event on the source is not an alarm: one
// recording continuously starts a new event at every section boundary.
TEST_CASE("MonitorLink score follows the source's alarm state", "[MonitorLink]") {
  EnsureConfig();
  SourceMonitor::Data data = {};
  data.valid = 1;
  data.state = Monitor::IDLE;
  TestLink link(std::make_shared<SourceMonitor>());
  link.Attach(&data);

  SECTION("idle") {
    REQUIRE(link.score() == 0);
    REQUIRE_FALSE(link.hasAlarmed());
  }
  SECTION("alarm: the source's frame score") {
    data.state = Monitor::ALARM;
    data.last_frame_score = 42;
    REQUIRE(link.score() == 42);
    REQUIRE(link.hasAlarmed());
  }
  SECTION("alert: keeps the linked event open") {
    data.state = Monitor::ALERT;
    data.last_frame_score = 0;
    REQUIRE(link.score() == 1);
  }
  SECTION("a new event while idle is not an alarm") {
    data.last_event_id = 1234;
    REQUIRE(link.score() == 0);
    data.last_frame_score = 17;  // left over from the source's last alarm
    data.last_event_id = 1235;
    REQUIRE(link.score() == 0);
  }
}
