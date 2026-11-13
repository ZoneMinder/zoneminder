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

// The cap on log rows waiting in zmDbQueue. The queue thread is kept off the
// connection by holding db_mutex, so no database is needed.

#include "zm_catch2.h"
#include "zm_db.h"

#include <mutex>
#include <string>

TEST_CASE("zmDbQueue drops log rows past the cap and keeps other writes", "[db_log_queue]") {
  constexpr size_t kCap = 10;
  zmDbQueue queue(kCap);
  {
    std::lock_guard<std::mutex> hold(db_mutex);

    // The first row may already be in the queue thread's hands, so count
    // from what the queue reports.
    for (int i = 0; i < 100; i++) queue.push("DO 1", true);
    CHECK(queue.logRowsQueued() == kCap);
    CHECK(queue.logRowsDropped() >= 100 - kCap - 1);

    const size_t dropped = queue.logRowsDropped();
    queue.push("DO 2");
    queue.push("DO 3", false);
    CHECK(queue.logRowsQueued() == kCap);
    CHECK(queue.logRowsDropped() == dropped);

    // A row marked to keep goes in past the cap.
    queue.push("DO 1", true, true);
    CHECK(queue.logRowsQueued() == kCap + 1);
    CHECK(queue.logRowsDropped() == dropped);
  }
  queue.stop();
}

TEST_CASE("zmDbQueue under the cap drops nothing", "[db_log_queue]") {
  zmDbQueue queue(10);
  {
    std::lock_guard<std::mutex> hold(db_mutex);
    for (int i = 0; i < 9; i++) queue.push("DO 1", true);
    CHECK(queue.logRowsDropped() == 0);
  }
  queue.stop();
  CHECK(queue.logRowsDropped() == 0);
}
