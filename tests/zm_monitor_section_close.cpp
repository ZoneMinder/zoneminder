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

#include "zm_monitor.h"
#include "zm_time.h"

// Build a SystemTimePoint at a whole number of seconds (optionally plus a
// fractional offset) since the epoch, to stand in for a packet timestamp.
static SystemTimePoint at(int64_t secs, double frac = 0.0) {
  return SystemTimePoint(std::chrono::duration_cast<SystemTimePoint::duration>(
      FPSeconds(static_cast<double>(secs) + frac)));
}

TEST_CASE("Monitor::crossedSectionBoundary") {
  const Seconds len(3600);

  SECTION("same section: not crossed") {
    REQUIRE_FALSE(Monitor::crossedSectionBoundary(at(36000), at(36000), len));
    REQUIRE_FALSE(Monitor::crossedSectionBoundary(at(36000), at(37800), len)); // half way
    REQUIRE_FALSE(Monitor::crossedSectionBoundary(at(36000), at(39599), len)); // last second
  }

  SECTION("a frame landing exactly on the boundary crosses") {
    // start 10:00:00, now 11:00:00 -> 39600/3600=11 > 36000/3600=10
    REQUIRE(Monitor::crossedSectionBoundary(at(36000), at(39600), len));
  }

  SECTION("sub-1fps: a frame past the boundary still crosses (the #3197 skip)") {
    // The old check required now % section_length == 0. A camera with a frame
    // every 5s never lands on 39600 exactly: 39598, 39603, ...  39603 % 3600
    // != 0 so the old code kept the event open until the next hour. The
    // boundary test closes on the first frame past the boundary.
    REQUIRE_FALSE(Monitor::crossedSectionBoundary(at(36000), at(39598), len));
    REQUIRE(Monitor::crossedSectionBoundary(at(36000), at(39603), len));
  }

  SECTION("fractional timestamps are truncated to whole seconds like the call site") {
    // 39600.4 truncates to 39600 -> crossed; 39599.9 truncates to 39599 -> not.
    REQUIRE(Monitor::crossedSectionBoundary(at(36000), at(39600, 0.4), len));
    REQUIRE_FALSE(Monitor::crossedSectionBoundary(at(36000), at(39599, 0.9), len));
  }

  SECTION("skipping several sections still counts as crossed (large gap)") {
    REQUIRE(Monitor::crossedSectionBoundary(at(36000), at(50000), len));
  }

  SECTION("an event starting mid-section closes at the next boundary, not a full section later") {
    // start 10:30:00 (37800), next boundary 11:00:00 (39600).
    REQUIRE_FALSE(Monitor::crossedSectionBoundary(at(37800), at(39000), len));
    REQUIRE(Monitor::crossedSectionBoundary(at(37800), at(39600), len));
  }

  SECTION("passthrough rollover: measured from the opening frame, not the backdated keyframe") {
    // Boundary 39600. The frame at 39603 closed the previous event; passthrough
    // starts the replacement on the keyframe at 39595, in the previous section.
    // Measured from that keyframe every later frame looks like a crossing, so the
    // replacement would close again after min_section_length. Measured from the
    // opening frame it stays open until the next boundary.
    REQUIRE(Monitor::crossedSectionBoundary(at(39595), at(39613), len));
    REQUIRE_FALSE(Monitor::crossedSectionBoundary(at(39603), at(39613), len));
    REQUIRE_FALSE(Monitor::crossedSectionBoundary(at(39603), at(43199), len));
    REQUIRE(Monitor::crossedSectionBoundary(at(39603), at(43200), len));
  }

  SECTION("section_length <= 0 never closes on time") {
    REQUIRE_FALSE(Monitor::crossedSectionBoundary(at(0), at(100000), Seconds(0)));
    REQUIRE_FALSE(Monitor::crossedSectionBoundary(at(0), at(100000), Seconds(-5)));
  }

  SECTION("short section lengths cross on the expected multiple") {
    const Seconds ten(10);
    REQUIRE_FALSE(Monitor::crossedSectionBoundary(at(100), at(109), ten));
    REQUIRE(Monitor::crossedSectionBoundary(at(100), at(110), ten));
    REQUIRE(Monitor::crossedSectionBoundary(at(105), at(110), ten)); // mid-section start
  }
}
