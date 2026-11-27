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

using std::chrono::milliseconds;

TEST_CASE("ONVIFEarlyPollWait", "[onvif]") {
  SECTION("Camera that answers at once is held to one poll per Timeout") {
    // The #5180 Beward answered a 1s PullMessages in 10-30ms, ~34 polls a second.
    REQUIRE(ONVIFEarlyPollWait(milliseconds(10), 1) == milliseconds(990));
    REQUIRE(ONVIFEarlyPollWait(milliseconds(0), 5) == milliseconds(5000));
  }

  SECTION("Camera that holds the long-poll for the full Timeout is polled again at once") {
    REQUIRE(ONVIFEarlyPollWait(milliseconds(1000), 1) == milliseconds(0));
    REQUIRE(ONVIFEarlyPollWait(milliseconds(1003), 1) == milliseconds(0));
  }

  SECTION("Partial hold waits only for the rest of the Timeout") {
    REQUIRE(ONVIFEarlyPollWait(milliseconds(400), 1) == milliseconds(600));
  }

  SECTION("Sub-millisecond elapsed time is not rounded into a longer wait") {
    REQUIRE(ONVIFEarlyPollWait(std::chrono::microseconds(10500), 1) == milliseconds(989));
  }
}

#endif  // WITH_GSOAP
