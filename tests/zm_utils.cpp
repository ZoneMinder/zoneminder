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

TEST_CASE("Split (char delimiter)") {
  std::vector<std::string> items = Split("", ' ');
  REQUIRE(items == std::vector<std::string>{""});

  items = Split("abc def ghi", ' ');
  REQUIRE(items == std::vector<std::string>{"abc", "def", "ghi"});

  items = Split("abc,def,,ghi", ',');
  REQUIRE(items == std::vector<std::string>{"abc", "def", "", "ghi"});
}

TEST_CASE("Split (string delimiter)") {
  std::vector<std::string> items;

  items = Split("", "");
  REQUIRE(items == std::vector<std::string>{""});

  items = Split("", " ");
  REQUIRE(items == std::vector<std::string>{""});

  items = Split("", " \t");
  REQUIRE(items == std::vector<std::string>{""});

  items = Split("", " \t");
  REQUIRE(items == std::vector<std::string>{""});

  items = Split(" ", " ");
  REQUIRE(items.size() == 0);

  items = Split("  ", " ");
  REQUIRE(items.size() == 0);

  items = Split(" ", " \t");
  REQUIRE(items.size() == 0);

  items = Split("a b", "");
  REQUIRE(items == std::vector<std::string>{"a b"});

  items = Split("a b", " ");
  REQUIRE(items == std::vector<std::string>{"a", "b"});

  items = Split("a \tb", " \t");
  REQUIRE(items == std::vector<std::string>{"a", "b"});

  items = Split(" a \tb ", " \t");
  REQUIRE(items == std::vector<std::string>{"a", "b"});

  items = Split(" a=b ", "=");
  REQUIRE(items == std::vector<std::string>{" a", "b "});

  items = Split(" a=b ", " =");
  REQUIRE(items == std::vector<std::string>{"a", "b"});

  items = Split("a b c", " ", 2);
  REQUIRE(items == std::vector<std::string>{"a", "b c"});
}

TEST_CASE("Join") {
  REQUIRE(Join({}, "") == "");
  REQUIRE(Join({}, " ") == "");
  REQUIRE(Join({""}, "") == "");
  REQUIRE(Join({"a"}, "") == "a");
  REQUIRE(Join({"a"}, ",") == "a");
  REQUIRE(Join({"a", "b"}, ",") == "a,b");
  REQUIRE(Join({"a", "b"}, "") == "ab");
}

TEST_CASE("ByteArrayToHexString") {
  std::vector<uint8> bytes;

  REQUIRE(ByteArrayToHexString(bytes) == "");

  bytes = {0x00};
  REQUIRE(ByteArrayToHexString(bytes) == "00");

  bytes = {0x00, 0x01, 0x02, 0xff};
  REQUIRE(ByteArrayToHexString(bytes) == "000102ff");
}

TEST_CASE("Base64Encode") {
  REQUIRE(Base64Encode("") == "");
  REQUIRE(Base64Encode("f") == "Zg==");
  REQUIRE(Base64Encode("fo") == "Zm8=");
  REQUIRE(Base64Encode("foo") == "Zm9v");
  REQUIRE(Base64Encode("foob") == "Zm9vYg==");
  REQUIRE(Base64Encode("fooba") == "Zm9vYmE=");
  REQUIRE(Base64Encode("foobar") == "Zm9vYmFy");
}

TEST_CASE("ZM::clamp") {
  REQUIRE(zm::clamp(1, 0, 2) == 1);
  REQUIRE(zm::clamp(3, 0, 2) == 2);
  REQUIRE(zm::clamp(-1, 0, 2) == 0);
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

TEST_CASE("mask_authentication") {
  SECTION("no authentication") {
    std::string url("http://192.168.1.1");
    std::string result = mask_authentication(url);
    REQUIRE(url == result);
  }
  SECTION("has username no password has scheme") {
    std::string url("http://username@192.168.1.1");
    std::string result = mask_authentication(url);
    REQUIRE(result == "http://********@192.168.1.1");
  }
  SECTION("has username no password no scheme") {
    std::string url("username@192.168.1.1");
    std::string result = mask_authentication(url);
    REQUIRE(result == "********@192.168.1.1");
  }
  SECTION("has username has password no scheme") {
    std::string url("username:password@192.168.1.1");
    std::string result = mask_authentication(url);
    REQUIRE(result == "********:********@192.168.1.1");
  }
  SECTION("has username has password has scheme") {
    std::string url("http://username:password@192.168.1.1");
    std::string result = mask_authentication(url);
    REQUIRE(result == "http://********:********@192.168.1.1");
  }
}

TEST_CASE("remove_authentication") {
  SECTION("no authentication") {
    std::string url("http://192.168.1.1");
    std::string result = remove_authentication(url);
    REQUIRE(url == result);
  }
  SECTION("has username no password has scheme") {
    std::string url("http://username@192.168.1.1");
    std::string result = remove_authentication(url);
    REQUIRE(result == "http://192.168.1.1");
  }
  SECTION("has username no password no scheme") {
    std::string url("username@192.168.1.1");
    std::string result = remove_authentication(url);
    REQUIRE(result == "192.168.1.1");
  }
  SECTION("has username has password no scheme") {
    std::string url("username:password@192.168.1.1");
    std::string result = remove_authentication(url);
    REQUIRE(result == "192.168.1.1");
  }
  SECTION("has username has password has scheme") {
    std::string url("http://username:password@192.168.1.1");
    std::string result = remove_authentication(url);
    REQUIRE(result == "http://192.168.1.1");
  }
}
