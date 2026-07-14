/*
 * This file is part of the ZoneMinder Project. See AUTHORS file for Copyright information
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

#include "zm_catch2.h"

#include "zm_secondary_sync.h"
#include "zm_time.h"

#include <chrono>

// An arbitrary steady_clock origin for the fabricated capture times below.
namespace {
const TimePoint kBase = TimePoint() + Seconds(1000);
const Seconds kThreshold = Seconds(10);
}  // namespace

TEST_CASE("SecondaryFrameStalled: frame captured with the packet is not stalled") {
  REQUIRE_FALSE(SecondaryFrameStalled(kBase, kBase, kThreshold));
  // Frame slightly older than the packet, within the threshold.
  REQUIRE_FALSE(SecondaryFrameStalled(kBase + Seconds(5), kBase, kThreshold));
}

TEST_CASE("SecondaryFrameStalled: analysis backlog (frame newer than packet) must still score") {
  // The analysis thread lags capture: the packet under analysis is old, but the
  // substream is healthy and its newest frame is 15s NEWER than the packet.
  // The one-sided test must not report a stall, no matter how large the lag.
  TimePoint packet = kBase;
  REQUIRE_FALSE(SecondaryFrameStalled(packet, kBase + Seconds(15), kThreshold));
  REQUIRE_FALSE(SecondaryFrameStalled(packet, kBase + Minutes(10), kThreshold));
}

TEST_CASE("SecondaryFrameStalled: dead substream (packet far ahead of frozen frame) is stalled") {
  // The sidecar stopped producing: the newest frame is frozen 15s behind the
  // packet under analysis.
  TimePoint frozen_frame = kBase;
  REQUIRE(SecondaryFrameStalled(kBase + Seconds(15), frozen_frame, kThreshold));
  REQUIRE(SecondaryFrameStalled(kBase + Minutes(10), frozen_frame, kThreshold));
}

TEST_CASE("SecondaryFrameStalled: threshold boundary on the stalled side") {
  // Packet exactly the threshold ahead of the frame: not yet stalled (strict >).
  REQUIRE_FALSE(SecondaryFrameStalled(kBase + Seconds(10), kBase, kThreshold));
  // One millisecond past the threshold: stalled.
  REQUIRE(SecondaryFrameStalled(kBase + Seconds(10) + Milliseconds(1), kBase, kThreshold));
}

TEST_CASE("SecondaryFrameStalled: threshold boundary on the backlog side") {
  // Any frame newer than the packet is never a stall, including exactly at and
  // beyond the threshold distance.
  TimePoint packet = kBase;
  REQUIRE_FALSE(SecondaryFrameStalled(packet, kBase + Seconds(10), kThreshold));
  REQUIRE_FALSE(SecondaryFrameStalled(packet, kBase + Seconds(10) + Milliseconds(1), kThreshold));
}
