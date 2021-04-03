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
#include <sstream>

TEST_CASE("Trim") {
  REQUIRE(Trim("", "") == "");
  REQUIRE(Trim("test", "") == "test");
  REQUIRE(Trim(" ", "") == " ");

  REQUIRE(Trim("\"test", "\"") == "test");
  REQUIRE(Trim("test\"", "\"") == "test");
  REQUIRE(Trim("\"test\"", "\"") == "test");

  REQUIRE(Trim("te\"st", "\"") == "te\"st");
  REQUIRE(Trim("\"te\"st\"", "\"") == "te\"st");
}

TEST_CASE("TrimSpaces") {
  REQUIRE(TrimSpaces(" ") == "");

  REQUIRE(TrimSpaces("test") == "test");
  REQUIRE(TrimSpaces(" test ") == "test");
  REQUIRE(TrimSpaces("  test ") == "test");
  REQUIRE(TrimSpaces("  test  ") == "test");
  REQUIRE(TrimSpaces(" test") == "test");
  REQUIRE(TrimSpaces("\ttest") == "test");
  REQUIRE(TrimSpaces("test\t") == "test");
  REQUIRE(TrimSpaces("\ttest\t") == "test");
  REQUIRE(TrimSpaces(" test\t") == "test");
  REQUIRE(TrimSpaces("\ttest ") == "test");
  REQUIRE(TrimSpaces("\t test \t") == "test");

  REQUIRE(TrimSpaces("\t te st \t") == "te st");
}

TEST_CASE("ReplaceAll") {
  REQUIRE(ReplaceAll("", "", "") == "");

  REQUIRE(ReplaceAll("a", "", "b") == "a");
  REQUIRE(ReplaceAll("a", "a", "b") == "b");
  REQUIRE(ReplaceAll("a", "b", "c") == "a");

  REQUIRE(ReplaceAll("aa", "a", "b") == "bb");
  REQUIRE(ReplaceAll("aba", "a", "c") == "cbc");

  REQUIRE(ReplaceAll("aTOKENa", "TOKEN", "VAL") == "aVALa");
  REQUIRE(ReplaceAll("aTOKENaTOKEN", "TOKEN", "VAL") == "aVALaVAL");
}

TEST_CASE("StartsWith") {
  REQUIRE(StartsWith("", "") == true);

  REQUIRE(StartsWith("test", "test") == true);
  REQUIRE(StartsWith("test=abc", "test") == true);
  REQUIRE(StartsWith(" test=abc", "test") == false);
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

TEST_CASE("UriDecode") {
  REQUIRE(UriDecode("abcABC123-_.~%21%28%29%26%3d%20") == "abcABC123-_.~!()&= ");
  REQUIRE(UriDecode("abcABC123-_.~%21%28%29%26%3d+") == "abcABC123-_.~!()&= ");
}

TEST_CASE("QueryString") {
  SECTION("no value") {
    std::stringstream str("name1=");
    QueryString qs(str);

    REQUIRE(qs.size() == 1);
    REQUIRE(qs.has("name1") == true);

    const QueryParameter *p = qs.get("name1");
    REQUIRE(p != nullptr);
    REQUIRE(p->name() == "name1");
    REQUIRE(p->size() == 0);
  }

  SECTION("no value and ampersand") {
    std::stringstream str("name1=&");
    QueryString qs(str);

    REQUIRE(qs.size() == 1);
    REQUIRE(qs.has("name1") == true);

    const QueryParameter *p = qs.get("name1");
    REQUIRE(p != nullptr);
    REQUIRE(p->name() == "name1");
    REQUIRE(p->size() == 0);
  }

  SECTION("one parameter, one value") {
    std::stringstream str("name1=value1");
    QueryString qs(str);

    REQUIRE(qs.size() == 1);
    REQUIRE(qs.has("name1") == true);

    const QueryParameter *p = qs.get("name1");
    REQUIRE(p != nullptr);
    REQUIRE(p->name() == "name1");
    REQUIRE(p->size() == 1);
    REQUIRE(p->values()[0] == "value1");
  }

  SECTION("one parameter, multiple values") {
    std::stringstream str("name1=value1&name1=value2");
    QueryString qs(str);

    REQUIRE(qs.size() == 1);
    REQUIRE(qs.has("name1") == true);

    const QueryParameter *p = qs.get("name1");
    REQUIRE(p != nullptr);
    REQUIRE(p->name() == "name1");
    REQUIRE(p->size() == 2);
    REQUIRE(p->values()[0] == "value1");
    REQUIRE(p->values()[1] == "value2");
  }

  SECTION("multiple parameters, multiple values") {
    std::stringstream str("name1=value1&name2=value2");
    QueryString qs(str);

    REQUIRE(qs.size() == 2);
    REQUIRE(qs.has("name1") == true);
    REQUIRE(qs.has("name2") == true);

    const QueryParameter *p1 = qs.get("name1");
    REQUIRE(p1 != nullptr);
    REQUIRE(p1->name() == "name1");
    REQUIRE(p1->size() == 1);
    REQUIRE(p1->values()[0] == "value1");

    const QueryParameter *p2 = qs.get("name2");
    REQUIRE(p2 != nullptr);
    REQUIRE(p2->name() == "name2");
    REQUIRE(p2->size() == 1);
    REQUIRE(p2->values()[0] == "value2");
  }
}
