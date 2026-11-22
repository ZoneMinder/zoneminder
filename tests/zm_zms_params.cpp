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

// zms_split_param lives in zms.cpp; build it here with its main() renamed.
#define main zms_main
#include "zms.cpp"
#undef main
#include <cstring>

TEST_CASE("zms: query parameter split", "[zms]") {
  SECTION("name and value") {
    char param[] = "user=admin";
    const char *value = nullptr;
    char *name = zms_split_param(param, value);
    REQUIRE(name != nullptr);
    REQUIRE(std::string(name) == "user");
    REQUIRE(std::string(value) == "admin");
  }

  SECTION("a later = belongs to the value") {
    char param[] = "pass=a=b==";
    const char *value = nullptr;
    char *name = zms_split_param(param, value);
    REQUIRE(name != nullptr);
    REQUIRE(std::string(name) == "pass");
    REQUIRE(std::string(value) == "a=b==");
  }

  SECTION("no = gives an empty value") {
    char param[] = "paused";
    const char *value = nullptr;
    char *name = zms_split_param(param, value);
    REQUIRE(name != nullptr);
    REQUIRE(std::string(name) == "paused");
    REQUIRE(std::string(value).empty());
  }

  SECTION("empty name is skipped") {
    char param[] = "=x";
    const char *value = nullptr;
    REQUIRE(zms_split_param(param, value) == nullptr);
  }
}
