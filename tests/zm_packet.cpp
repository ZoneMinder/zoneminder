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

#include "zm_packet.h"

#if ZM_HAS_NLOHMANN_JSON
// The build defines ZM_HAS_NLOHMANN_JSON. zm_packet.h and zm_event.cpp tested
// HAS_NLOHMANN_JSON, which nothing defines, so a packet had no detections and
// the Event_Data and tag writer was never compiled.
TEST_CASE("A packet carries detections when the build has nlohmann_json", "[ZMPacket]") {
  ZMPacket packet;
  packet.detections = nlohmann::json::array({{{"class", "person"}, {"confidence", 0.9}}});
  REQUIRE(packet.detections.size() == 1);
  REQUIRE(packet.detections[0]["class"] == "person");
}
#endif
