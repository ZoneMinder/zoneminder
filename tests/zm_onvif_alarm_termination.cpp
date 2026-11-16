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
#include "zm_monitor_onvif.h"

#ifdef WITH_GSOAP

TEST_CASE("ONVIFAlarmTermination", "[onvif]") {
  // 2026-10-01 18:52:32.917 UTC, the moment of the first alarm in the #5165 log
  const time_t now_t = 1790880752;
  const SystemTimePoint now = std::chrono::system_clock::from_time_t(now_t) + std::chrono::milliseconds(917);
  time_t clock_offset = 0;
  SystemTimePoint termination;

  SECTION("TerminationTime equal to CurrentTime is not usable") {
    // Beward cameras put their CurrentTime in TerminationTime on every
    // PullMessagesResponse. Using it expired each alarm in the same pass that
    // raised it, so the analysis thread never saw the monitor alarmed.
    REQUIRE_FALSE(ONVIFAlarmTermination(now_t, now_t, now, clock_offset, termination));
    REQUIRE(clock_offset == 0);
  }

  SECTION("TerminationTime before CurrentTime is not usable") {
    REQUIRE_FALSE(ONVIFAlarmTermination(now_t - 5, now_t, now, clock_offset, termination));
  }

  SECTION("TerminationTime in the future is used") {
    REQUIRE(ONVIFAlarmTermination(now_t + 60, now_t, now, clock_offset, termination));
    REQUIRE(clock_offset == 0);
    REQUIRE(termination == std::chrono::system_clock::from_time_t(now_t + 60));
  }

  SECTION("Camera clock offset is applied") {
    // Camera clock is an hour behind ours
    REQUIRE(ONVIFAlarmTermination(now_t - 3600 + 60, now_t - 3600, now, clock_offset, termination));
    REQUIRE(clock_offset == 3600);
    REQUIRE(termination == std::chrono::system_clock::from_time_t(now_t + 60));
  }

  SECTION("TerminationTime equal to a skewed CurrentTime is not usable") {
    REQUIRE_FALSE(ONVIFAlarmTermination(now_t - 3600, now_t - 3600, now, clock_offset, termination));
    REQUIRE(clock_offset == 3600);
  }

  SECTION("Without CurrentTime the previous offset is kept") {
    clock_offset = 3600;
    REQUIRE(ONVIFAlarmTermination(now_t - 3600 + 60, 0, now, clock_offset, termination));
    REQUIRE(clock_offset == 3600);
    REQUIRE(termination == std::chrono::system_clock::from_time_t(now_t + 60));

    REQUIRE_FALSE(ONVIFAlarmTermination(now_t - 3600, 0, now, clock_offset, termination));
  }

  SECTION("No TerminationTime is not usable") {
    REQUIRE_FALSE(ONVIFAlarmTermination(0, now_t, now, clock_offset, termination));
  }
}

#endif  // WITH_GSOAP
