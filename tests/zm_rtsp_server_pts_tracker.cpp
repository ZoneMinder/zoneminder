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

#include "zm_rtsp_server_pts_tracker.h"

TEST_CASE("RtspPtsTracker passes an unbroken timeline through unchanged", "[rtsp_pts_tracker]") {
  RtspPtsTracker tracker;
  CHECK(tracker.Map(1000000) == 1000000);
  CHECK(tracker.Map(1100000) == 1100000);
  CHECK(tracker.Map(1050000) == 1050000);  // reordered, not a discontinuity
}

TEST_CASE("RtspPtsTracker continues after a timeline that restarts lower", "[rtsp_pts_tracker]") {
  RtspPtsTracker tracker;
  CHECK(tracker.Map(15000000) == 15000000);
  CHECK(tracker.Map(15100000) == 15100000);

  tracker.Discontinuity();
  int64_t first = tracker.Map(1400000);  // the camera started over
  CHECK(first == 15100000 + RtspPtsTracker::kGapUs);
  // the shift sticks, so the spacing within the new timeline is kept
  CHECK(tracker.Map(1500000) == first + 100000);
  CHECK(tracker.Map(1600000) == first + 200000);
}

TEST_CASE("RtspPtsTracker leaves a timeline that resumes ahead alone", "[rtsp_pts_tracker]") {
  RtspPtsTracker tracker;
  CHECK(tracker.Map(1000000) == 1000000);
  tracker.Discontinuity();
  CHECK(tracker.Map(9000000) == 9000000);
  CHECK(tracker.Map(9100000) == 9100000);
}

TEST_CASE("RtspPtsTracker does nothing without a discontinuity", "[rtsp_pts_tracker]") {
  RtspPtsTracker tracker;
  CHECK(tracker.Map(15000000) == 15000000);
  CHECK(tracker.Map(1400000) == 1400000);
}

TEST_CASE("RtspPtsTracker shares one shift between streams", "[rtsp_pts_tracker]") {
  RtspPtsTracker tracker;
  tracker.Map(10000000);  // video
  tracker.Map(10020000);  // audio
  tracker.Discontinuity();
  int64_t audio = tracker.Map(500000);
  int64_t video = tracker.Map(480000);
  CHECK(audio == 10020000 + RtspPtsTracker::kGapUs);
  CHECK(video == audio - 20000);
}
