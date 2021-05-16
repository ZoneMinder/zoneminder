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

#include "zm_poly.h"

TEST_CASE("Polygon: default constructor") {
  Polygon p;

  REQUIRE(p.Area() == 0);
  REQUIRE(p.Centre() == Vector2(0, 0));
}

TEST_CASE("Polygon: construct from vertices") {
  std::vector<Vector2> vertices{{{0, 0}, {6, 0}, {0, 6}}};
  Polygon p(vertices);

  REQUIRE(p.Area() == 18);
  REQUIRE(p.Extent().Size() == Vector2(6, 6));
}

TEST_CASE("Polygon: clipping") {
  // This a concave polygon in a shape resembling a "W"
  std::vector<Vector2> v = {
      {3, 1},
      {5, 1},
      {6, 3},
      {7, 1},
      {9, 1},
      {10, 8},
      {8, 8},
      {7, 5},
      {5, 5},
      {4, 8},
      {2, 8}
  };

  Polygon p(v);

  REQUIRE(p.GetVertices().size() == 11);
  REQUIRE(p.Extent().Size() == Vector2(8, 7));

  SECTION("boundary box larger than polygon") {
    p.Clip(Box({1, 0}, {11, 9}));

    REQUIRE(p.GetVertices().size() == 11);
    REQUIRE(p.Extent().Size() == Vector2(8, 7));
  }

  SECTION("boundary box smaller than polygon") {
    p.Clip(Box({2, 4}, {10, 7}));

    REQUIRE(p.GetVertices().size() == 8);
    REQUIRE(p.Extent().Size() == Vector2(8, 3));
  }
}
