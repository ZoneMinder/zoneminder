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

#ifdef WITH_GSOAP
#define private public
#define protected public
#include "zm_monitor_onvif.h"
#undef protected
#undef private

TEST_CASE("ONVIF alarm text accepts a comma separated list", "[onvif]") {
  auto match = [&](const char *topic, const char *filter) {
    return ONVIF::matches_topic_filter(topic, filter);
  };
  const char *topic = "tns1:RuleEngine/CellMotionDetector/Motion";

  REQUIRE(match(topic, "Motion"));
  REQUIRE_FALSE(match(topic, "FireAlarm"));
  REQUIRE(match(topic, "MotionAlarm,Motion"));
  REQUIRE(match(topic, "DetectedSound, FireAlarm ,Motion"));
  REQUIRE_FALSE(match(topic, "DetectedSound,FireAlarm"));
  REQUIRE_FALSE(match(topic, ",,"));
}
#endif
