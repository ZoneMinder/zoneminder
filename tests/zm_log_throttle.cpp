/*
 * This file is part of the ZoneMinder Project. See AUTHORS file for contributors.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

#include "zm_catch2.h"

#include "zm_log_throttle.h"

#include <type_traits>

namespace {
// Synthetic steady time, so the decision is exercised without waiting.
TimePoint at(Microseconds offset) {
  return TimePoint{} + offset;
}
}  // namespace

TEST_CASE("LogThrottle admits one per interval") {
  LogThrottle throttle(Seconds(1));
  uint64 suppressed = 0;

  SECTION("the first occurrence always gets through") {
    REQUIRE(throttle.Admit(at(Seconds(0)), suppressed));
    REQUIRE(suppressed == 0);
  }

  SECTION("occurrences inside the interval are counted, not emitted") {
    REQUIRE(throttle.Admit(at(Seconds(0)), suppressed));
    for (int i = 1; i <= 20; i++) {
      REQUIRE_FALSE(throttle.Admit(at(Milliseconds(i * 10)), suppressed));
    }
    REQUIRE(throttle.suppressed() == 20);
  }

  SECTION("the next one through reports how many were folded in") {
    REQUIRE(throttle.Admit(at(Seconds(0)), suppressed));
    for (int i = 1; i <= 5; i++) {
      REQUIRE_FALSE(throttle.Admit(at(Milliseconds(i * 100)), suppressed));
    }
    REQUIRE(throttle.Admit(at(Seconds(1)), suppressed));
    REQUIRE(suppressed == 5);
    // and the count starts again for the next window
    REQUIRE(throttle.suppressed() == 0);
  }

  SECTION("exactly at the interval counts as elapsed") {
    REQUIRE(throttle.Admit(at(Seconds(0)), suppressed));
    REQUIRE(throttle.Admit(at(Seconds(1)), suppressed));
  }

  SECTION("a long quiet gap still only emits once") {
    REQUIRE(throttle.Admit(at(Seconds(0)), suppressed));
    REQUIRE(throttle.Admit(at(Seconds(3600)), suppressed));
    REQUIRE(suppressed == 0);
  }
}

TEST_CASE("LogThrottle is not affected by wall clock corrections") {
  // The reason the interval is measured on steady_clock. Sampling the wall
  // clock instead let an NTP or manual correction decide whether a message was
  // emitted: a backward step muted it until wall time caught up, and a forward
  // step let an extra through before a real second had passed. refs #4242
  LogThrottle throttle(Seconds(1));
  uint64 suppressed = 0;

  SECTION("ten real seconds emit ten times however the wall clock moves") {
    // Real elapsed time advances one second at a time. A wall clock corrected
    // backwards to zero at this point would have suppressed all of these.
    REQUIRE(throttle.Admit(at(Seconds(10)), suppressed));
    int emitted = 0;
    for (int i = 1; i <= 10; i++) {
      if (throttle.Admit(at(Seconds(10 + i)), suppressed)) emitted++;
    }
    REQUIRE(emitted == 10);
  }

  SECTION("a forward jump does not let an extra one through") {
    // Ten milliseconds of real time, which a forward wall clock step of ninety
    // seconds would have turned into a second emission.
    REQUIRE(throttle.Admit(at(Seconds(10)), suppressed));
    REQUIRE_FALSE(throttle.Admit(at(Seconds(10) + Milliseconds(10)), suppressed));
    REQUIRE(throttle.suppressed() == 1);
  }
}

TEST_CASE("LogThrottle cannot be driven by the wall clock") {
  // The parameter is a steady_clock point and SystemTimePoint is a distinct
  // type, so passing std::chrono::system_clock::now() here is a compile error
  // rather than something a reviewer has to catch.
  REQUIRE_FALSE((std::is_same<TimePoint, SystemTimePoint>::value));
  REQUIRE((std::is_same<TimePoint, std::chrono::steady_clock::time_point>::value));
  REQUIRE_FALSE((std::is_convertible<SystemTimePoint, TimePoint>::value));
}
