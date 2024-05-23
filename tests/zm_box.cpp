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

#include "zm_box.h"

TEST_CASE("Box: default constructor") {
  Box b;

  REQUIRE(b.Lo() == Vector2(0, 0));
  REQUIRE(b.Hi() == Vector2(0, 0));
  REQUIRE(b.Size() == Vector2(0, 0));
  REQUIRE(b.Area() == 0);
}

TEST_CASE("Box: construct from lo and hi") {
  Box b({1, 1}, {5, 5});

  SECTION("basic properties") {
  REQUIRE(b.Lo() == Vector2(1, 1));
  REQUIRE(b.Hi() == Vector2(5, 5));

  REQUIRE(b.Size() == Vector2(4 ,4));
  REQUIRE(b.Area() == 16);
  REQUIRE(b.Centre() == Vector2(3, 3));

  REQUIRE(b.Vertices() == std::vector<Vector2>{{1, 1}, {5, 1}, {5, 5}, {1, 5}});
  }

  SECTION("contains") {
    REQUIRE(b.Contains({0, 0}) == false);
    REQUIRE(b.Contains({1, 1}) == true);
    REQUIRE(b.Contains({3, 3}) == true);
    REQUIRE(b.Contains({5, 5}) == true);
    REQUIRE(b.Contains({6, 6}) == false);
  }
}
