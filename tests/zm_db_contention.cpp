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
#include "zm_db.h"

// Covers the retry budget only. The retry loop itself needs a database and two
// sessions contending for the same rows, so it is not exercised here.
TEST_CASE("zmDbContentionBackoff", "[db]") {
  SECTION("the budget is finite") {
    // The point of the schedule: a query that keeps losing lock races is
    // eventually abandoned. Before this, a lock wait timeout re-ran forever.
    REQUIRE(zmDbContentionBackoff(kMaxDbContentionRetries) > 0);
    REQUIRE(zmDbContentionBackoff(kMaxDbContentionRetries + 1) == 0);
    REQUIRE(zmDbContentionBackoff(kMaxDbContentionRetries + 100) == 0);
  }

  SECTION("attempt numbering starts at one") {
    REQUIRE(zmDbContentionBackoff(1) > 0);
    REQUIRE(zmDbContentionBackoff(0) == 0);
    REQUIRE(zmDbContentionBackoff(-1) == 0);
  }

  SECTION("each attempt waits longer than the one before") {
    // Jittered, so compare across the gap rather than sampling twice and
    // assuming the same value comes back.
    for (int attempt = 1; attempt < kMaxDbContentionRetries; attempt++) {
      REQUIRE(zmDbContentionBackoff(attempt) < zmDbContentionBackoff(attempt + 1));
    }
  }

  SECTION("jitter stays inside its band") {
    // Two callers that deadlocked against each other must not wake together and
    // do it again, so the wait is spread, but only within the attempt's slot.
    for (int attempt = 1; attempt <= kMaxDbContentionRetries; attempt++) {
      useconds_t base = 50000u * (1u << attempt);
      for (int sample = 0; sample < 200; sample++) {
        useconds_t backoff = zmDbContentionBackoff(attempt);
        REQUIRE(backoff >= base);
        REQUIRE(backoff < base + 50000u);
      }
    }
  }

  SECTION("the whole budget is a bounded stall") {
    // The sleep happens under db_mutex, which every other database user in the
    // process is waiting on, so the total has to stay small.
    useconds_t total = 0;
    for (int attempt = 1; attempt <= kMaxDbContentionRetries; attempt++)
      total += zmDbContentionBackoff(attempt);
    REQUIRE(total < 4000000u);  // under 4 seconds
  }
}
