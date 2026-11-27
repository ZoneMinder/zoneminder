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

#include <cstdint>
#include <cstring>
#include <vector>

#include "zm_mem_utils.h"

TEST_CASE("memstr: a partial needle at the limit is not a match", "[mem_utils]") {
  // Sized exactly, so a read past the limit is a heap overflow under ASan.
  std::vector<char> exact = {'a', 'b', 'c', 'd', '\r', '\n', '-'};
  REQUIRE(memstr(exact.data(), "\r\n--", 7) == nullptr);

  // The byte past the limit would complete the needle.
  const char *longer = "abcd\r\n--";
  REQUIRE(memstr(longer, "\r\n--", 7) == nullptr);
}

TEST_CASE("memstr: a needle ending exactly at the limit is found", "[mem_utils]") {
  std::vector<char> exact = {'a', 'b', 'c', '\r', '\n', '-', '-'};
  REQUIRE(memstr(exact.data(), "\r\n--", 7) == exact.data() + 3);
}

TEST_CASE("memstr: a needle longer than the limit is not found", "[mem_utils]") {
  std::vector<char> exact = {'\r', '\n', '-'};
  REQUIRE(memstr(exact.data(), "\r\n--", 3) == nullptr);

  // The byte past the limit would complete the needle.
  const char *longer = "\r\n--";
  REQUIRE(memstr(longer, "\r\n--", 3) == nullptr);
}
