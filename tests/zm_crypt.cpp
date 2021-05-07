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

#include "catch2/catch.hpp"

#include "zm_crypt.h"

TEST_CASE("JWT validation") {
  std::string key = "testsecret";

  SECTION("Valid token") {
    std::string token =
        "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJab25lTWluZGVyIiwidXNlciI6ImpvaG5kb2UiLCJ0eXBlIjoiYWNjZXNzIiwiaWF0IjoxMjM0fQ.94WPmBAVl_83KCI9B3Jq9sNpoOdi0Hm1dR4sc6MCPUA";
    std::pair<std::string, unsigned int> result = verifyToken(token, key);

    REQUIRE(result.first == "johndoe");
    REQUIRE(result.second == 1234);
  }

  SECTION("Invalid signature") {
    std::string token =
        "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJab25lTWluZGVyIiwidXNlciI6ImpvaG5kb2UiLCJ0eXBlIjoiYWNjZXNzIiwiaWF0IjoxMjM0fQ.DhviT6RkDLmbXh5F9zM4l0VbWNPCuKptF6fORv1lBlA";
    std::pair<std::string, unsigned int> result = verifyToken(token, key);

    REQUIRE(result.first == "");
    REQUIRE(result.second == 0);
  }

  SECTION("Missing user claim") {
    std::string token =
        "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJab25lTWluZGVyIiwidHlwZSI6ImFjY2VzcyIsImlhdCI6MTIzNH0.mfi3ZHnqUAPUh5ECxDIkAM9WW9a8HbKrP73LC3yYJmw";
    std::pair<std::string, unsigned int> result = verifyToken(token, key);

    REQUIRE(result.first == "");
    REQUIRE(result.second == 0);
  }

  SECTION("Missing type claim") {
    std::string token =
        "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJab25lTWluZGVyIiwidXNlciI6ImpvaG5kb2UiLCJpYXQiOjEyMzR9.D4Irs1gHfzO4psRY2xsOdClTg-Sp1kM__mmfNLs7CII";
    std::pair<std::string, unsigned int> result = verifyToken(token, key);

    REQUIRE(result.first == "");
    REQUIRE(result.second == 0);
  }

  SECTION("Wrong type claim") {
    std::string token =
        "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJab25lTWluZGVyIiwidXNlciI6ImpvaG5kb2UiLCJ0eXBlIjoid3JvbmciLCJpYXQiOjEyMzR9.I1Gd50J6mck05vzc_kzjaH4RNjLBaFGpOnie6-PbX28";
    std::pair<std::string, unsigned int> result = verifyToken(token, key);

    REQUIRE(result.first == "");
    REQUIRE(result.second == 0);
  }

  SECTION("Missing iat claim") {
    std::string token =
        "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJab25lTWluZGVyIiwidXNlciI6ImpvaG5kb2UiLCJ0eXBlIjoid3JvbmcifQ.8iUFOUKJAK5vU8JWKm8D0EOEhm1rJoIulCO11O_Tsp0";
    std::pair<std::string, unsigned int> result = verifyToken(token, key);

    REQUIRE(result.first == "");
    REQUIRE(result.second == 0);
  }
}
