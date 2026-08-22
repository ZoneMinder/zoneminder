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

#include <array>
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

  // LibvlcCamera splits the monitor Options field this way. The field is a
  // textarea, so an option may be on its own line rather than after a comma,
  // and runs of separators (a blank line, or crlf) must not become entries.
  items = Split("--rate=1\n--no-audio", kOptionSeparators);
  REQUIRE(items == std::vector<std::string>{"--rate=1", "--no-audio"});

  items = Split("--rate=1,--no-audio", kOptionSeparators);
  REQUIRE(items == std::vector<std::string>{"--rate=1", "--no-audio"});

  items = Split("--rate=1\r\n\r\n--no-audio\n", kOptionSeparators);
  REQUIRE(items == std::vector<std::string>{"--rate=1", "--no-audio"});
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

  const std::array<uint8, 3> binary = {{0x00, 0xff, 0x10}};
  REQUIRE(Base64Encode(nonstd::span<const uint8>(binary.data(), binary.size())) == "AP8Q");
}

TEST_CASE("JsonExtractQuotedField") {
  std::string value;
  REQUIRE(JsonExtractQuotedField("{\"command\":\"stream\",\"codec\":\"h264\"}", "command", &value));
  REQUIRE(value == "stream");
  REQUIRE(JsonExtractQuotedField("{\"message\":\"hello \\\"zm\\\"\"}", "message", &value));
  REQUIRE(value == "hello \"zm\"");
  REQUIRE_FALSE(JsonExtractQuotedField("{\"command\":123}", "command", &value));
}

TEST_CASE("JsonExtractIntegerField") {
  int value = 0;
  REQUIRE(JsonExtractIntegerField("{\"interval_ms\":1000}", "interval_ms", &value));
  REQUIRE(value == 1000);
  REQUIRE(JsonExtractIntegerField("{\"interval_ms\":-25}", "interval_ms", &value));
  REQUIRE(value == -25);
  REQUIRE_FALSE(JsonExtractIntegerField("{\"interval_ms\":\"fast\"}", "interval_ms", &value));
}

TEST_CASE("ExtractHeaderValue and HeaderContainsToken") {
  const std::string request =
      "GET / HTTP/1.1\r\n"
      "Upgrade: websocket\r\n"
      "Connection: keep-alive, Upgrade\r\n"
      "Sec-WebSocket-Key: abc\r\n\r\n";

  std::string value;
  REQUIRE(ExtractHeaderValue(request, "upgrade", &value));
  REQUIRE(value == "websocket");
  REQUIRE(ExtractHeaderValue(request, "connection", &value));
  REQUIRE(HeaderContainsToken(value, "upgrade"));
  REQUIRE_FALSE(HeaderContainsToken(value, "close"));
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

TEST_CASE("UriEncode") {
  // Basic alphanumeric and safe characters should not be encoded
  REQUIRE(UriEncode("abcABC123") == "abcABC123");
  REQUIRE(UriEncode("-_.~") == "-_.~");

  // Space should be encoded as %20
  REQUIRE(UriEncode(" ") == "%20");
  REQUIRE(UriEncode("hello world") == "hello%20world");
  REQUIRE(UriEncode("a b c") == "a%20b%20c");

  // Special characters should be percent-encoded
  REQUIRE(UriEncode("!") == "%21");
  REQUIRE(UriEncode("&") == "%26");
  REQUIRE(UriEncode("=") == "%3D");
  REQUIRE(UriEncode("?") == "%3F");

  // Empty string
  REQUIRE(UriEncode("") == "");

  // Round-trip test: encode then decode should return original
  std::string original = "hello world!";
  REQUIRE(UriDecode(UriEncode(original)) == original);

  // Non-ASCII bytes must each be escaped as a single %XX pair. A signed char
  // sign-extends here and yields "%FF" (truncated from "FFFFFFD0") instead.
  REQUIRE(UriEncode("\xD0\xB2") == "%D0%B2");
  REQUIRE(UriEncode("\xFF") == "%FF");
  REQUIRE(UriEncode("\x80") == "%80");

  // A Cyrillic name (U+0432 U+0445 U+043E U+0434) - the kind of name the
  // utf8mb4 switch made storable. Written as bytes so this file stays ASCII.
  REQUIRE(UriEncode("\xD0\xB2\xD1\x85\xD0\xBE\xD0\xB4") == "%D0%B2%D1%85%D0%BE%D0%B4");

  // Mixed ASCII and UTF-8, and a multi-byte round trip.
  REQUIRE(UriEncode("Cam \xC3\xA9") == "Cam%20%C3%A9");
  REQUIRE(UriDecode(UriEncode("\xD0\xB2\xD1\x85")) == "\xD0\xB2\xD1\x85");
}

TEST_CASE("escape_json_string") {
  // Nothing to do.
  REQUIRE(escape_json_string("") == "");
  REQUIRE(escape_json_string("Front Door") == "Front Door");

  // A quote must come out as one backslash then a quote. The previous
  // implementation escaped quotes before backslashes, so it emitted \\" here:
  // an escaped backslash followed by a bare quote, which closed the JSON
  // string it was embedded in.
  REQUIRE(escape_json_string("\"") == "\\\"");
  REQUIRE(escape_json_string("Front \"Door\"") == "Front \\\"Door\\\"");

  // A backslash doubles, and that doubling must not itself be re-doubled.
  REQUIRE(escape_json_string("\\") == "\\\\");
  REQUIRE(escape_json_string("back\\slash") == "back\\\\slash");

  // A backslash adjacent to a quote is where ordering errors surface.
  REQUIRE(escape_json_string("\\\"") == "\\\\\\\"");

  // Named control character escapes.
  REQUIRE(escape_json_string("\n") == "\\n");
  REQUIRE(escape_json_string("\r") == "\\r");
  REQUIRE(escape_json_string("\t") == "\\t");
  REQUIRE(escape_json_string("\b") == "\\b");
  REQUIRE(escape_json_string("\f") == "\\f");
  REQUIRE(escape_json_string("line\nbreak") == "line\\nbreak");

  // Control characters without a short form must still be escaped.
  REQUIRE(escape_json_string(std::string(1, '\a')) == "\\u0007");
  REQUIRE(escape_json_string(std::string(1, '\x01')) == "\\u0001");
  REQUIRE(escape_json_string(std::string(1, '\x1f')) == "\\u001f");

  // An embedded NUL is a control character like any other and must survive.
  REQUIRE(escape_json_string(std::string("a\0b", 3)) == "a\\u0000b");

  // 0x7f is not a JSON control character, and UTF-8 passes through intact.
  REQUIRE(escape_json_string(std::string(1, '\x7f')) == std::string(1, '\x7f'));
  REQUIRE(escape_json_string("\xD0\xB2\xD1\x85") == "\xD0\xB2\xD1\x85");

  // Escaping twice is not the same as escaping once. This is the double
  // escape the go2rtc credential path was hitting.
  const std::string once = escape_json_string("pa\\ss");
  REQUIRE(once == "pa\\\\ss");
  REQUIRE(escape_json_string(once) == "pa\\\\\\\\ss");
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

TEST_CASE("zm_strncpy") {
  char buf[8];

  SECTION("fits, null-terminated") {
    zm_strncpy(buf, "abc", sizeof(buf));
    REQUIRE(std::string(buf) == "abc");
  }
  SECTION("oversize truncated to size-1 and terminated") {
    zm_strncpy(buf, "abcdefghijkl", sizeof(buf));
    REQUIRE(std::string(buf) == "abcdefg");   // 7 chars + '\0'
    REQUIRE(buf[7] == '\0');
  }
  SECTION("delimiter length n limits copy") {
    zm_strncpy(buf, "abcdef", sizeof(buf), 3); // src not null-terminated at field end
    REQUIRE(std::string(buf) == "abc");
  }
  SECTION("delimiter length also capped to buffer") {
    zm_strncpy(buf, "abcdefghij", sizeof(buf), 20);
    REQUIRE(std::string(buf) == "abcdefg");
    REQUIRE(buf[7] == '\0');
  }
  SECTION("empty source") {
    zm_strncpy(buf, "", sizeof(buf));
    REQUIRE(buf[0] == '\0');
  }
}
