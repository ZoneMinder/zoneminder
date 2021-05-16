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

#include "zm_vector2.h"

TEST_CASE("Vector2: default constructor") {
  Vector2 c;
  REQUIRE(c.x_ == 0);
  REQUIRE(c.y_ == 0);
}

TEST_CASE("Vector2: x/y constructor") {
  Vector2 c(1, 2);

  REQUIRE(c.x_ == 1);
  REQUIRE(c.y_ == 2);
}

TEST_CASE("Vector2: assignment/copy") {
  Vector2 c;
  Vector2 c2(1, 2);

  REQUIRE(c.x_ == 0);
  REQUIRE(c.y_ == 0);

  SECTION("assignment operator") {
    c = c2;
    REQUIRE(c.x_ == 1);
    REQUIRE(c.y_ == 2);
  }

  SECTION("copy constructor") {
    Vector2 c3(c2); // NOLINT(performance-unnecessary-copy-initialization)
    REQUIRE(c3.x_ == 1);
    REQUIRE(c3.y_ == 2);
  }
}

TEST_CASE("Vector2: comparison operators") {
  Vector2 c1(1, 2);
  Vector2 c2(1, 2);
  Vector2 c3(1, 3);

  REQUIRE((c1 == c2) == true);
  REQUIRE((c1 != c3) == true);
}

TEST_CASE("Vector2: arithmetic operators") {
  Vector2 c(1, 1);

  SECTION("addition") {
    Vector2 c1 = c + Vector2(1, 1);
    REQUIRE(c1 == Vector2(2, 2));

    c += {1, 2};
    REQUIRE(c == Vector2(2, 3));
  }

  SECTION("subtraction") {
    Vector2 c1 = c - Vector2(1, 1);
    REQUIRE(c1 == Vector2(0, 0));

    c -= {1, 2};
    REQUIRE(c == Vector2(0, -1));
  }

  SECTION("scalar multiplication") {
    c = c * 2;
    REQUIRE(c == Vector2(2, 2));
  }
}

TEST_CASE("Vector2: determinate") {
  Vector2 v(1, 1);
  REQUIRE(v.Determinant({0, 0}) == 0);
  REQUIRE(v.Determinant({1, 1}) == 0);
  REQUIRE(v.Determinant({1, 2}) == 1);
}
