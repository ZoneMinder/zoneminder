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
  void SetSectionLength(Seconds length) { section_length = length; }
  // What openEvent() records for the packet that opens an event.
  void EventOpenedAt(int64_t sec) { event_section_sec_ = sec; }
};

SystemTimePoint At(int64_t sec) { return SystemTimePoint(Seconds(sec)); }

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

// Analyse only acts on CLOSE_TIME once the event is min_section_length long.
// An event opened a few seconds before a boundary is shorter than that when
// the boundary packet arrives, so the boundary must still count on the later
// packets instead of only on the one that stepped over it, or the event runs
// on to the next boundary. refs #5532
TEST_CASE("Monitor::TimeSectionEnded holds until the event is replaced", "[Monitor][analyse]") {
  EnsureConfig();
  StateMonitor::Data d = {};
  StateMonitor m(&d);
  m.SetSectionLength(Seconds(600));

  m.EventOpenedAt(595);
  REQUIRE_FALSE(m.TimeSectionEnded(At(599)));
  REQUIRE(m.TimeSectionEnded(At(600)));
  // past min_section_length, no longer the packet that crossed
  REQUIRE(m.TimeSectionEnded(At(605)));
  REQUIRE(m.TimeSectionEnded(At(611)));

  // The replacement opens on the packet at 611. Passthrough starts the event
  // itself on an earlier keyframe, which must not make it look ended.
  m.EventOpenedAt(611);
  REQUIRE_FALSE(m.TimeSectionEnded(At(620)));
  REQUIRE_FALSE(m.TimeSectionEnded(At(1199)));
  REQUIRE(m.TimeSectionEnded(At(1203)));

  // no event opened yet
  m.EventOpenedAt(-1);
  REQUIRE_FALSE(m.TimeSectionEnded(At(1203)));
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
