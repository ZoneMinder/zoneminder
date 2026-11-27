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

TEST_CASE("ONVIFAlarmExceedsMaxDuration", "[onvif]") {
  const SystemTimePoint started = std::chrono::system_clock::from_time_t(1790880752);

  SECTION("No limit never expires an alarm") {
    REQUIRE_FALSE(ONVIFAlarmExceedsMaxDuration(started, started + std::chrono::hours(48), 0));
  }

  SECTION("Alarm younger than the limit is kept") {
    REQUIRE_FALSE(ONVIFAlarmExceedsMaxDuration(started, started + std::chrono::seconds(299), 300));
  }

  SECTION("Alarm at or past the limit is cleared") {
    REQUIRE(ONVIFAlarmExceedsMaxDuration(started, started + std::chrono::seconds(300), 300));
    REQUIRE(ONVIFAlarmExceedsMaxDuration(started, started + std::chrono::hours(1), 300));
  }

  SECTION("Unset start time is not cleared") {
    REQUIRE_FALSE(ONVIFAlarmExceedsMaxDuration(SystemTimePoint{}, started, 300));
  }
}

#endif  // WITH_GSOAP
