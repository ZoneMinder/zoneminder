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

// AlarmLatch is defined outside the WITH_GSOAP guard, so these run regardless
// of whether gSOAP is available.

TEST_CASE("AlarmLatch starts clear", "[onvif]") {
  AlarmLatch latch;
  REQUIRE_FALSE(latch.alarmed());
  REQUIRE_FALSE(latch.active());
}

TEST_CASE("AlarmLatch level follows raise and clear", "[onvif]") {
  AlarmLatch latch;

  latch.raise();
  REQUIRE(latch.alarmed());
  REQUIRE(latch.active());

  latch.observe();  // a long alarm, observed while still up
  REQUIRE(latch.alarmed());
  REQUIRE(latch.active());

  latch.clear();
  REQUIRE_FALSE(latch.active());
  REQUIRE_FALSE(latch.alarmed());
}

TEST_CASE("AlarmLatch holds a pulse cleared before it is observed", "[onvif]") {
  // The #5301 case: a DetectedSound pulse goes active then inactive on the poll
  // thread between two analysis frames. Without the latch the raw level was
  // already down by the time a frame sampled it and no alarm frame was recorded.
  AlarmLatch latch;

  latch.raise();
  latch.clear();  // ON then OFF, no frame sampled it in between

  REQUIRE_FALSE(latch.active());  // raw level is down
  REQUIRE(latch.alarmed());       // but the pulse is still reported

  latch.observe();                // the next analysis frame samples it
  REQUIRE_FALSE(latch.alarmed()); // and it is released
}

TEST_CASE("AlarmLatch reports a pulse on exactly one sample", "[onvif]") {
  AlarmLatch latch;

  latch.raise();
  latch.clear();

  REQUIRE(latch.alarmed());       // frame N sees the pulse
  latch.observe();
  REQUIRE_FALSE(latch.alarmed()); // frame N+1 does not see it again
}

TEST_CASE("AlarmLatch observe without an alarm is a no-op", "[onvif]") {
  AlarmLatch latch;
  latch.observe();
  REQUIRE_FALSE(latch.alarmed());
  REQUIRE_FALSE(latch.active());
}

TEST_CASE("AlarmLatch reports a second pulse after the first is released", "[onvif]") {
  AlarmLatch latch;

  latch.raise();
  latch.clear();
  latch.observe();
  REQUIRE_FALSE(latch.alarmed());

  latch.raise();
  latch.clear();
  REQUIRE(latch.alarmed());        // the second pulse is latched too
  latch.observe();
  REQUIRE_FALSE(latch.alarmed());
}

TEST_CASE("AlarmLatch stays alarmed while the level is up even after observe", "[onvif]") {
  // A still-active alarm (e.g. ongoing motion) must not be released by a sample;
  // only clear() ends it.
  AlarmLatch latch;

  latch.raise();
  latch.observe();
  latch.observe();
  REQUIRE(latch.alarmed());
  REQUIRE(latch.active());

  latch.clear();
  REQUIRE_FALSE(latch.alarmed());
}

TEST_CASE("AlarmLatch re-raise before observe is still reported", "[onvif]") {
  // Two pulses arrive before a frame samples: the latch is still set, and one
  // observe releases it (the two momentary pulses merge into one alarm frame).
  AlarmLatch latch;

  latch.raise();
  latch.clear();
  latch.raise();
  latch.clear();
  REQUIRE(latch.alarmed());

  latch.observe();
  REQUIRE_FALSE(latch.alarmed());
}
