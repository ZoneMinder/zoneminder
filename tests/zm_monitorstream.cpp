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

#include "zm_monitorstream.h"

TEST_CASE("MonitorStreamBufferLevel") {
  SECTION("zero buffer count does not divide by zero") {
    // Regression for zoneminder/zoneminder#4936: processCommand() could run on
    // the command thread before runStream() assigned temp_image_buffer_count,
    // leaving it 0 while playback_buffer was already > 0, causing a SIGFPE.
    REQUIRE(MonitorStreamBufferLevel(0, 0, 0) == 0);
    REQUIRE(MonitorStreamBufferLevel(5, 2, 0) == 0);
    REQUIRE(MonitorStreamBufferLevel(0, 5, 0) == 0);
  }

  SECTION("negative buffer count is treated as empty") {
    REQUIRE(MonitorStreamBufferLevel(3, 1, -10) == 0);
  }

  SECTION("empty buffer reports 0%") {
    REQUIRE(MonitorStreamBufferLevel(0, 0, 100) == 0);
  }

  SECTION("full buffer reports nearly 100%") {
    // write wraps around to one behind read => count-1 of count slots used
    REQUIRE(MonitorStreamBufferLevel(99, 0, 100) == 99);
  }

  SECTION("half full buffer reports ~50%") {
    REQUIRE(MonitorStreamBufferLevel(50, 0, 100) == 50);
  }

  SECTION("wrap-around (write behind read) is handled modularly") {
    // write_index - read_index is negative; modular arithmetic keeps it in range
    REQUIRE(MonitorStreamBufferLevel(2, 7, 10) == 50);
  }
}
