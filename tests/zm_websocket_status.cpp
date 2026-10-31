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

#include "zm_websocket_status.h"

using zm::websocket::StatusSnapshot;
using zm::websocket::StatusSignificantlyChanged;
using zm::websocket::StatusFpsChanged;
using zm::websocket::StatusBandwidthChanged;

TEST_CASE("Status change: identical snapshots do not push") {
  StatusSnapshot a;
  a.connected = true;
  a.shm_valid = true;
  a.state = 2;
  a.analysing = 1;
  a.capturing = 1;
  a.recording = 0;
  a.signal = 1;
  a.last_event_id = 42;
  a.capture_fps = 15.0;
  a.analysis_fps = 15.0;
  a.capture_bandwidth = 100000;
  REQUIRE_FALSE(StatusSignificantlyChanged(a, a));
}

TEST_CASE("Status change: each discrete transition pushes") {
  StatusSnapshot base;
  base.connected = true;
  base.shm_valid = true;
  base.state = 1;
  base.signal = 1;
  base.last_event_id = 10;

  SECTION("connect/disconnect") {
    StatusSnapshot b = base;
    b.connected = false;
    REQUIRE(StatusSignificantlyChanged(base, b));
  }
  SECTION("shm validity") {
    StatusSnapshot b = base;
    b.shm_valid = false;
    REQUIRE(StatusSignificantlyChanged(base, b));
  }
  SECTION("state") {
    StatusSnapshot b = base;
    b.state = 3;
    REQUIRE(StatusSignificantlyChanged(base, b));
  }
  SECTION("analysing transition") {
    StatusSnapshot b = base;
    b.analysing = 1;
    REQUIRE(StatusSignificantlyChanged(base, b));
  }
  SECTION("recording transition") {
    StatusSnapshot b = base;
    b.recording = 1;
    REQUIRE(StatusSignificantlyChanged(base, b));
  }
  SECTION("signal loss") {
    StatusSnapshot b = base;
    b.signal = 0;
    REQUIRE(StatusSignificantlyChanged(base, b));
  }
  SECTION("new event") {
    StatusSnapshot b = base;
    b.last_event_id = 11;
    REQUIRE(StatusSignificantlyChanged(base, b));
  }
}

TEST_CASE("Status change: fps jitter is coalesced, real change pushes") {
  // Small jitter below one frame per second does not push.
  REQUIRE_FALSE(StatusFpsChanged(15.0, 15.4));
  REQUIRE_FALSE(StatusFpsChanged(15.0, 14.2));
  // A move of at least one fps does.
  REQUIRE(StatusFpsChanged(15.0, 16.0));
  REQUIRE(StatusFpsChanged(15.0, 12.0));
  // Crossing to/from zero (stream stop/start) always pushes.
  REQUIRE(StatusFpsChanged(0.0, 0.2));
  REQUIRE(StatusFpsChanged(0.5, 0.0));
}

TEST_CASE("Status change: bandwidth drift is coalesced, real shift pushes") {
  // Within 1 KiB: never a change regardless of ratio.
  REQUIRE_FALSE(StatusBandwidthChanged(500, 1200));
  // Large absolute move but under 10% of the value: coalesced.
  REQUIRE_FALSE(StatusBandwidthChanged(1000000, 1050000));
  // Over 1 KiB and over 10%: a real shift.
  REQUIRE(StatusBandwidthChanged(100000, 120000));
  REQUIRE(StatusBandwidthChanged(120000, 100000));
  // From zero to streaming.
  REQUIRE(StatusBandwidthChanged(0, 50000));
}
