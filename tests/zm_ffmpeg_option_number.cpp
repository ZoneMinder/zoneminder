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

#include "zm_ffmpeg.h"

static bool number(const char *text, uint64_t *out = nullptr) {
  uint64_t value = 7;
  bool ok = zm_parse_option_number(text, value);
  if (out) *out = value;
  return ok;
}

TEST_CASE("zm_parse_option_number accepts plain and SI suffixed numbers", "[ffmpeg]") {
  uint64_t v = 0;
  REQUIRE(number("30", &v));
  REQUIRE(v == 30);
  REQUIRE(number("2M", &v));
  REQUIRE(v == 2000000);
  REQUIRE(number("500k", &v));
  REQUIRE(v == 500000);
  REQUIRE(number("4.1", &v));
  REQUIRE(v == 4);
}

TEST_CASE("zm_parse_option_number rejects what is not a number", "[ffmpeg]") {
  uint64_t v = 0;
  REQUIRE_FALSE(number("fast", &v));
  REQUIRE(v == 7);
  REQUIRE_FALSE(number(""));
  REQUIRE_FALSE(number("2Mbps"));
  REQUIRE_FALSE(number("-1"));
  REQUIRE_FALSE(number("nan"));
  REQUIRE_FALSE(number("inf"));
}
