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

#include "zm_utils.h"

TEST_CASE("trimSet") {
  REQUIRE(trimSet("", "") == "");
  REQUIRE(trimSet("test", "") == "test");
  REQUIRE(trimSet(" ", "") == " ");

  REQUIRE(trimSet("\"test", "\"") == "test");
  REQUIRE(trimSet("test\"", "\"") == "test");
  REQUIRE(trimSet("\"test\"", "\"") == "test");

  REQUIRE(trimSet("te\"st", "\"") == "te\"st");
  REQUIRE(trimSet("\"te\"st\"", "\"") == "te\"st");
}

TEST_CASE("trimSpaces") {
  REQUIRE(trimSpaces(" ") == "");

  REQUIRE(trimSpaces("test") == "test");
  REQUIRE(trimSpaces(" test ") == "test");
  REQUIRE(trimSpaces("  test ") == "test");
  REQUIRE(trimSpaces("  test  ") == "test");
  REQUIRE(trimSpaces(" test") == "test");
  REQUIRE(trimSpaces("\ttest") == "test");
  REQUIRE(trimSpaces("test\t") == "test");
  REQUIRE(trimSpaces("\ttest\t") == "test");
  REQUIRE(trimSpaces(" test\t") == "test");
  REQUIRE(trimSpaces("\ttest ") == "test");
  REQUIRE(trimSpaces("\t test \t") == "test");

  REQUIRE(trimSpaces("\t te st \t") == "te st");
}

TEST_CASE("replaceAll") {
  REQUIRE(replaceAll("", "", "") == "");

  REQUIRE(replaceAll("a", "", "b") == "a");
  REQUIRE(replaceAll("a", "a", "b") == "b");
  REQUIRE(replaceAll("a", "b", "c") == "a");

  REQUIRE(replaceAll("aa", "a", "b") == "bb");
  REQUIRE(replaceAll("aba", "a", "c") == "cbc");

  REQUIRE(replaceAll("aTOKENa", "TOKEN", "VAL") == "aVALa");
  REQUIRE(replaceAll("aTOKENaTOKEN", "TOKEN", "VAL") == "aVALaVAL");
}

TEST_CASE("startsWith") {
  REQUIRE(startsWith("", "") == true);

  REQUIRE(startsWith("test", "test") == true);
  REQUIRE(startsWith("test=abc", "test") == true);
  REQUIRE(startsWith(" test=abc", "test") == false);
}

TEST_CASE("split (char delimiter)") {
  std::vector<std::string> items;
  int res;

  res = split(nullptr, ' ', items);
  REQUIRE(res == -1);
  REQUIRE(items.size() == 0);

  res = split("", ' ', items);
  REQUIRE(res == -2);
  REQUIRE(items.size() == 0);

  res = split("abc def ghi", ' ', items);
  REQUIRE(res == 3);
  REQUIRE(items == std::vector<std::string>{"abc", "def", "ghi"});
}

TEST_CASE("split (string delimiter)") {
  std::vector<std::string> items;

  items = split("", "");
  REQUIRE(items == std::vector<std::string>{""});

  items = split("", " ");
  REQUIRE(items == std::vector<std::string>{""});

  items = split("", " \t");
  REQUIRE(items == std::vector<std::string>{""});

  items = split("", " \t");
  REQUIRE(items == std::vector<std::string>{""});

  items = split(" ", " ");
  REQUIRE(items.size() == 0);

  items = split("  ", " ");
  REQUIRE(items.size() == 0);

  items = split(" ", " \t");
  REQUIRE(items.size() == 0);

  items = split("a b", "");
  REQUIRE(items == std::vector<std::string>{"a b"});

  items = split("a b", " ");
  REQUIRE(items == std::vector<std::string>{"a", "b"});

  items = split("a \tb", " \t");
  REQUIRE(items == std::vector<std::string>{"a", "b"});

  items = split(" a \tb ", " \t");
  REQUIRE(items == std::vector<std::string>{"a", "b"});

  items = split(" a=b ", "=");
  REQUIRE(items == std::vector<std::string>{" a", "b "});

  items = split(" a=b ", " =");
  REQUIRE(items == std::vector<std::string>{"a", "b"});

  items = split("a b c", " ", 2);
  REQUIRE(items == std::vector<std::string>{"a", "b c"});
}

TEST_CASE("join") {
  REQUIRE(join({}, "") == "");
  REQUIRE(join({}, " ") == "");
  REQUIRE(join({""}, "") == "");
  REQUIRE(join({"a"}, "") == "a");
  REQUIRE(join({"a"}, ",") == "a");
  REQUIRE(join({"a", "b"}, ",") == "a,b");
  REQUIRE(join({"a", "b"}, "") == "ab");
}

TEST_CASE("base64Encode") {
  REQUIRE(base64Encode("") == "");
  REQUIRE(base64Encode("f") == "Zg==");
  REQUIRE(base64Encode("fo") == "Zm8=");
  REQUIRE(base64Encode("foo") == "Zm9v");
  REQUIRE(base64Encode("foob") == "Zm9vYg==");
  REQUIRE(base64Encode("fooba") == "Zm9vYmE=");
  REQUIRE(base64Encode("foobar") == "Zm9vYmFy");
}
