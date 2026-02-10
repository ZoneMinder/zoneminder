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

#include "zm_zone.h"

TEST_CASE("Zone::ParsePercentagePolygon: full-frame zone at 1920x1080", "[Zone]") {
  Polygon polygon;
  bool result = Zone::ParsePercentagePolygon(
      "0.00,0.00 100.00,0.00 100.00,100.00 0.00,100.00",
      1920, 1080, polygon);

  REQUIRE(result == true);
  REQUIRE(polygon.GetVertices().size() == 4);
  REQUIRE(polygon.GetVertices()[0] == Vector2(0, 0));
  REQUIRE(polygon.GetVertices()[1] == Vector2(1920, 0));
  REQUIRE(polygon.GetVertices()[2] == Vector2(1920, 1080));
  REQUIRE(polygon.GetVertices()[3] == Vector2(0, 1080));
}

TEST_CASE("Zone::ParsePercentagePolygon: center 50% zone", "[Zone]") {
  Polygon polygon;
  bool result = Zone::ParsePercentagePolygon(
      "25.00,25.00 75.00,25.00 75.00,75.00 25.00,75.00",
      1920, 1080, polygon);

  REQUIRE(result == true);
  REQUIRE(polygon.GetVertices().size() == 4);
  REQUIRE(polygon.GetVertices()[0] == Vector2(480, 270));
  REQUIRE(polygon.GetVertices()[1] == Vector2(1440, 270));
  REQUIRE(polygon.GetVertices()[2] == Vector2(1440, 810));
  REQUIRE(polygon.GetVertices()[3] == Vector2(480, 810));
}

TEST_CASE("Zone::ParsePercentagePolygon: fractional percentages", "[Zone]") {
  Polygon polygon;
  bool result = Zone::ParsePercentagePolygon(
      "50.25,75.50 60.00,75.50 60.00,85.00 50.25,85.00",
      1920, 1080, polygon);

  REQUIRE(result == true);
  REQUIRE(polygon.GetVertices().size() == 4);
  // 50.25% of 1920 = 964.8 -> 965
  REQUIRE(polygon.GetVertices()[0].x_ == 965);
  // 75.50% of 1080 = 815.4 -> 815
  REQUIRE(polygon.GetVertices()[0].y_ == 815);
}

TEST_CASE("Zone::ParsePercentagePolygon: different resolution", "[Zone]") {
  Polygon polygon;
  bool result = Zone::ParsePercentagePolygon(
      "0.00,0.00 100.00,0.00 100.00,100.00 0.00,100.00",
      640, 480, polygon);

  REQUIRE(result == true);
  REQUIRE(polygon.GetVertices()[1] == Vector2(640, 0));
  REQUIRE(polygon.GetVertices()[2] == Vector2(640, 480));
}

TEST_CASE("Zone::ParsePercentagePolygon: clamping beyond 100%", "[Zone]") {
  Polygon polygon;
  bool result = Zone::ParsePercentagePolygon(
      "0.00,0.00 110.00,0.00 100.00,100.00 0.00,100.00",
      1920, 1080, polygon);

  REQUIRE(result == true);
  // 110% should be clamped to monitor width
  REQUIRE(polygon.GetVertices()[1].x_ == 1920);
}

TEST_CASE("Zone::ParsePercentagePolygon: triangle", "[Zone]") {
  Polygon polygon;
  bool result = Zone::ParsePercentagePolygon(
      "50.00,10.00 90.00,90.00 10.00,90.00",
      1000, 1000, polygon);

  REQUIRE(result == true);
  REQUIRE(polygon.GetVertices().size() == 3);
  REQUIRE(polygon.GetVertices()[0] == Vector2(500, 100));
  REQUIRE(polygon.GetVertices()[1] == Vector2(900, 900));
  REQUIRE(polygon.GetVertices()[2] == Vector2(100, 900));
}

TEST_CASE("Zone::ParsePercentagePolygon: too few points", "[Zone]") {
  Polygon polygon;
  bool result = Zone::ParsePercentagePolygon(
      "0.00,0.00 100.00,0.00",
      1920, 1080, polygon);

  REQUIRE(result == false);
}

TEST_CASE("Zone::ParsePercentagePolygon: integer coords still work", "[Zone]") {
  // strtod handles integers fine
  Polygon polygon;
  bool result = Zone::ParsePercentagePolygon(
      "0,0 100,0 100,100 0,100",
      1920, 1080, polygon);

  REQUIRE(result == true);
  REQUIRE(polygon.GetVertices()[0] == Vector2(0, 0));
  REQUIRE(polygon.GetVertices()[1] == Vector2(1920, 0));
  REQUIRE(polygon.GetVertices()[2] == Vector2(1920, 1080));
  REQUIRE(polygon.GetVertices()[3] == Vector2(0, 1080));
}
