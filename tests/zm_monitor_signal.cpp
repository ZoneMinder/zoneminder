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

#include "zm_monitor.h"

TEST_CASE("Monitor::DecoderChecksSignal") {
  SECTION("without SignalCheckPoints nothing measures the signal") {
    for (auto decoding : {Monitor::DECODING_NONE, Monitor::DECODING_ONDEMAND, Monitor::DECODING_KEYFRAMES,
                          Monitor::DECODING_KEYFRAMESONDEMAND, Monitor::DECODING_ALWAYS}) {
      REQUIRE_FALSE(Monitor::DecoderChecksSignal(decoding, 0, true));
      REQUIRE_FALSE(Monitor::DecoderChecksSignal(decoding, 0, false));
    }
  }

  SECTION("a decoder that always runs measures it") {
    REQUIRE(Monitor::DecoderChecksSignal(Monitor::DECODING_ALWAYS, 10, false));
    REQUIRE(Monitor::DecoderChecksSignal(Monitor::DECODING_KEYFRAMES, 10, false));
    REQUIRE(Monitor::DecoderChecksSignal(Monitor::DECODING_KEYFRAMESONDEMAND, 10, false));
  }

  SECTION("on-demand decoding measures it only while someone watches") {
    REQUIRE(Monitor::DecoderChecksSignal(Monitor::DECODING_ONDEMAND, 10, true));
    REQUIRE_FALSE(Monitor::DecoderChecksSignal(Monitor::DECODING_ONDEMAND, 10, false));
  }

  SECTION("no decoding: capture keeps setting it") {
    REQUIRE_FALSE(Monitor::DecoderChecksSignal(Monitor::DECODING_NONE, 10, true));
  }
}
