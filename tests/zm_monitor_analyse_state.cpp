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

namespace {

void EnsureConfig() {
  if (!config.font_file_location) config.font_file_location = "";
  if (!config.event_close_mode) config.event_close_mode = "idle";
}

class StateMonitor : public Monitor {
 public:
  using Data = SharedData;
  explicit StateMonitor(SharedData *data) : Monitor() { shared_data = data; }
  ~StateMonitor() { shared_data = nullptr; }
};

}  // namespace

// MotionFrameSkip is the number of frames skipped between detections, so
// analysis runs on every (skip+1)th frame. Using capture/limit as the skip
// analysed 30fps at 7.5fps for a 10fps limit.
TEST_CASE("Monitor::AnalysisFrameSkip analyses at the requested rate", "[Monitor][analyse]") {
  REQUIRE(Monitor::AnalysisFrameSkip(30.0, 10.0) == 2);
  REQUIRE(Monitor::AnalysisFrameSkip(10.0, 10.0) == 0);
  REQUIRE(Monitor::AnalysisFrameSkip(25.0, 5.0) == 4);
  // measured fps jitters around the nominal rate
  REQUIRE(Monitor::AnalysisFrameSkip(29.97, 10.0) == 2);
  REQUIRE(Monitor::AnalysisFrameSkip(30.4, 10.0) == 2);
  // asking for more than is captured, or no limit, never skips
  REQUIRE(Monitor::AnalysisFrameSkip(5.0, 10.0) == 0);
  REQUIRE(Monitor::AnalysisFrameSkip(0.0, 10.0) == 0);
  REQUIRE(Monitor::AnalysisFrameSkip(30.0, 0.0) == 0);
}

// The time based close fired for every frame in the boundary second, and not
// at all when no frame landed on that exact second (slow cameras).
TEST_CASE("Monitor::TimeSectionCrossed fires once per boundary", "[Monitor][analyse]") {
  REQUIRE(Monitor::TimeSectionCrossed(599, 600, 600));
  REQUIRE_FALSE(Monitor::TimeSectionCrossed(600, 600, 600));
  REQUIRE_FALSE(Monitor::TimeSectionCrossed(600, 601, 600));
  REQUIRE_FALSE(Monitor::TimeSectionCrossed(-1, 600, 600));
  // one frame every 5s skips over the boundary second
  REQUIRE(Monitor::TimeSectionCrossed(595, 605, 600));
  REQUIRE_FALSE(Monitor::TimeSectionCrossed(605, 610, 600));
}

// The pre-alarm count was a static of Event, shared by every monitor of a zmc
// process (a multi-input capture card runs several analysis threads).
TEST_CASE("Monitor pre-alarm count is per monitor", "[Monitor][analyse]") {
  EnsureConfig();
  StateMonitor::Data da = {}, db = {};
  StateMonitor a(&da), b(&db);
  a.EmptyPreAlarmFrames();
  b.EmptyPreAlarmFrames();

  a.AddPreAlarmFrame();
  a.AddPreAlarmFrame();
  REQUIRE(a.PreAlarmCount() == 2);
  REQUIRE(b.PreAlarmCount() == 0);

  b.EmptyPreAlarmFrames();
  REQUIRE(a.PreAlarmCount() == 2);
  a.EmptyPreAlarmFrames();
}

// A trigger turned off or a signal loss leaves PREALARM without emptying the
// count, so the first alarmed frame afterwards went straight to ALARM.
TEST_CASE("Monitor::ClearAlarmTracking drops partial alarm progress", "[Monitor][analyse]") {
  EnsureConfig();
  StateMonitor::Data d = {};
  StateMonitor m(&d);
  m.EmptyPreAlarmFrames();
  m.AddPreAlarmFrame();
  m.AddPreAlarmFrame();
  m.ClearAlarmTracking();
  REQUIRE(m.PreAlarmCount() == 0);
}

// An event that rolls over on a packet carrying no score (an audio packet, or
// a video frame skipped by the analysis fps limit) reopened as "Continuous"
// even though the alarm was still in progress.
TEST_CASE("Monitor::EventCauseForOpen keeps the alarm cause on a no-score packet", "[Monitor][analyse]") {
  REQUIRE(Monitor::EventCauseForOpen("Motion: Z1", "Motion: Z1", Monitor::ALARM) == "Motion: Z1");
  REQUIRE(Monitor::EventCauseForOpen("", "Motion: Z1", Monitor::ALARM) == "Motion: Z1");
  REQUIRE(Monitor::EventCauseForOpen("", "Motion: Z1", Monitor::IDLE) == "Continuous");
  REQUIRE(Monitor::EventCauseForOpen("", "", Monitor::ALARM) == "Continuous");
  REQUIRE(Monitor::EventCauseForOpen("Signal: Reacquired", "Motion: Z1", Monitor::ALARM) == "Signal: Reacquired");
}

// After signal loss the reference image is stale. It must be re-seeded on
// reacquire for every recording mode that analyses, not only Record.
TEST_CASE("Monitor::ReacquireNeedsRefReset follows analysing, not recording", "[Monitor][analyse]") {
  REQUIRE(Monitor::ReacquireNeedsRefReset(Monitor::ANALYSING_ALWAYS));
  REQUIRE_FALSE(Monitor::ReacquireNeedsRefReset(Monitor::ANALYSING_NONE));
}
