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

#include "zm_monitorlink_token.h"

#include <string>

// LinkedMonitors is stored as typed, so a token can be anything that isn't a
// delimiter. One that isn't a monitor id or monitor id:zone id must not throw
// out of the noexcept constructor (std::terminate, before the fix); it is
// ignored and scores 0. None of these reach Monitor::Load, so no database.
TEST_CASE("Token ignores a monitor link that isn't a number", "[MonitorLink]") {
  const std::string value = GENERATE(
      std::string("abc"),
      std::string("1:x"),
      std::string(":3"),
      std::string(" "),
      std::string("99999999999999999999"));
  CAPTURE(value);

  Token token(value);
  REQUIRE(token.is(Token::TokenType::monitorlink));
  REQUIRE(token.value() == value);
  REQUIRE(token.score() == 0);
  REQUIRE_FALSE(token.hasAlarmed());
}

TEST_CASE("Token::parse_monitor_link", "[MonitorLink]") {
  unsigned int monitor_id = 0, zone_id = 0;

  REQUIRE(Token::parse_monitor_link("3", monitor_id, zone_id));
  REQUIRE(monitor_id == 3);
  REQUIRE(zone_id == 0);

  REQUIRE(Token::parse_monitor_link("3:7", monitor_id, zone_id));
  REQUIRE(monitor_id == 3);
  REQUIRE(zone_id == 7);

  REQUIRE(Token::parse_monitor_link("4294967295", monitor_id, zone_id));
  REQUIRE(monitor_id == 4294967295u);

  // Fit an unsigned long but not an unsigned int: truncated, they named some
  // other monitor or zone.
  REQUIRE_FALSE(Token::parse_monitor_link("4294967296", monitor_id, zone_id));
  REQUIRE_FALSE(Token::parse_monitor_link("99999999999", monitor_id, zone_id));
  REQUIRE_FALSE(Token::parse_monitor_link("1:99999999999", monitor_id, zone_id));

  REQUIRE_FALSE(Token::parse_monitor_link("abc", monitor_id, zone_id));
  REQUIRE_FALSE(Token::parse_monitor_link("1:x", monitor_id, zone_id));
}
