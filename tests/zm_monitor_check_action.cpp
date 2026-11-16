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
#include "zm_signal.h"

namespace {

void EnsureConfig() {
  if (!config.font_file_location) config.font_file_location = "";
  if (!config.event_close_mode) config.event_close_mode = "idle";
}

// A Monitor whose shared data is a local struct instead of the mapping, so
// CheckAction() can be driven without connect().
class ActionMonitor : public Monitor {
 public:
  using Data = SharedData;
  static constexpr int kReload = RELOAD;
  static constexpr int kSuspend = SUSPEND;
  static constexpr int kResume = RESUME;
  static constexpr uint8_t kAnalysingNone = ANALYSING_NONE;
  static constexpr uint8_t kAnalysingAlways = ANALYSING_ALWAYS;
  explicit ActionMonitor(SharedData *data) : Monitor() {
    shared_data = data;
    analysing = ANALYSING_ALWAYS;
    capturing = CAPTURING_ALWAYS;
  }
  ~ActionMonitor() { shared_data = nullptr; }
};

}  // namespace

// The reload action (zmu --reload) used to run Reload() right there, on zmc's
// capture thread, rewriting the monitor's settings while the decoder and
// analysis threads read them. It now asks for the reload SIGHUP does, which
// zmc carries out after Close() has stopped those threads. refs #5239
TEST_CASE("Monitor reload action defers to zmc's reload", "[Monitor]") {
  EnsureConfig();
  ActionMonitor::Data data = {};
  data.action = ActionMonitor::kReload;
  ActionMonitor monitor(&data);

  zm_reload = false;
  monitor.CheckAction();

  REQUIRE(zm_reload);
  REQUIRE((data.action & ActionMonitor::kReload) == 0);
  zm_reload = false;
}

// ANALYSING_NONE is 1, so Active() was true while analysis was suspended and
// the resume action never restored it; only the max suspend time did.
TEST_CASE("Monitor resume action restores analysing after a suspend", "[Monitor]") {
  EnsureConfig();
  config.max_suspend_time = 30;
  ActionMonitor::Data data = {};
  data.analysing = ActionMonitor::kAnalysingAlways;
  data.capturing = 3;
  ActionMonitor monitor(&data);

  data.action = ActionMonitor::kSuspend;
  monitor.CheckAction();
  REQUIRE(data.analysing == ActionMonitor::kAnalysingNone);
  REQUIRE((data.action & ActionMonitor::kSuspend) == 0);

  data.action = ActionMonitor::kResume;
  monitor.CheckAction();
  REQUIRE(data.analysing == ActionMonitor::kAnalysingAlways);
  REQUIRE((data.action & ActionMonitor::kResume) == 0);
}
