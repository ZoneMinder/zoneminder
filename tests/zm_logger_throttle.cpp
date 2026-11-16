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

#include "zm_logger.h"

namespace {
// Synthetic steady time, so the decision is exercised without waiting.
TimePoint at(Microseconds offset) {
  return TimePoint{} + offset;
}
}  // namespace

TEST_CASE("Logger::throttle rate limits per file, line and key", "[Logger]") {
  Logger *log = Logger::fetch();
  const char *file = __FILE__;
  uint64 suppressed = 0;

  SECTION("the first call is admitted with nothing suppressed") {
    REQUIRE(log->throttle(file, 1001, 1, Seconds(1), suppressed, at(Seconds(0))));
    REQUIRE(suppressed == 0);
  }

  SECTION("calls inside the interval are rejected") {
    REQUIRE(log->throttle(file, 1002, 1, Seconds(1), suppressed, at(Seconds(0))));
    for (int i = 1; i <= 20; i++) {
      REQUIRE_FALSE(log->throttle(file, 1002, 1, Seconds(1), suppressed, at(Milliseconds(i * 10))));
    }
  }

  SECTION("the next admitted call reports how many were suppressed") {
    REQUIRE(log->throttle(file, 1003, 1, Seconds(1), suppressed, at(Seconds(0))));
    for (int i = 1; i <= 7; i++) {
      REQUIRE_FALSE(log->throttle(file, 1003, 1, Seconds(1), suppressed, at(Milliseconds(i * 100))));
    }
    REQUIRE(log->throttle(file, 1003, 1, Seconds(1), suppressed, at(Seconds(1))));
    REQUIRE(suppressed == 7);
    // The count starts again for the next interval.
    REQUIRE(log->throttle(file, 1003, 1, Seconds(1), suppressed, at(Seconds(2))));
    REQUIRE(suppressed == 0);
  }

  SECTION("different keys and different lines are independent") {
    REQUIRE(log->throttle(file, 1004, 1, Seconds(1), suppressed, at(Seconds(0))));
    REQUIRE_FALSE(log->throttle(file, 1004, 1, Seconds(1), suppressed, at(Milliseconds(500))));

    // Neither of these has been seen before, so the rejection above is theirs alone.
    REQUIRE(log->throttle(file, 1004, 2, Seconds(1), suppressed, at(Milliseconds(500))));
    REQUIRE(log->throttle(file, 1005, 1, Seconds(1), suppressed, at(Milliseconds(500))));

    REQUIRE_FALSE(log->throttle(file, 1004, 2, Seconds(1), suppressed, at(Milliseconds(750))));
    REQUIRE_FALSE(log->throttle(file, 1005, 1, Seconds(1), suppressed, at(Milliseconds(750))));
  }

  SECTION("a call exactly at the interval is admitted") {
    REQUIRE(log->throttle(file, 1006, 1, Seconds(1), suppressed, at(Seconds(0))));
    REQUIRE_FALSE(log->throttle(file, 1006, 1, Seconds(1), suppressed, at(Milliseconds(999))));
    REQUIRE(log->throttle(file, 1006, 1, Seconds(1), suppressed, at(Seconds(1))));
    REQUIRE(suppressed == 1);
  }
}
