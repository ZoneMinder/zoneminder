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

#include "zm_rgb.h"

TEST_CASE("rgb_to_luma") {
  SECTION("limited range, as YUV420P/YUV422P hold it") {
    REQUIRE(rgb_to_luma(0, 0, 0, false) == 16);
    REQUIRE(rgb_to_luma(255, 255, 255, false) == 235);
    // The default SignalCheckColour #0000BE; a decoded solid #0000BE picture
    // holds Y=35 in every pixel.
    REQUIRE(rgb_to_luma(0x00, 0x00, 0xBE, false) == 35);
  }

  SECTION("full range, as the J formats and GRAY8 hold it") {
    REQUIRE(rgb_to_luma(0, 0, 0, true) == 0);
    REQUIRE(rgb_to_luma(255, 255, 255, true) == 255);
    REQUIRE(rgb_to_luma(0x00, 0x00, 0xBE, true) == 22);
    REQUIRE(rgb_to_luma(255, 0, 0, true) == 76);
    REQUIRE(rgb_to_luma(0, 255, 0, true) == 150);
  }
}
