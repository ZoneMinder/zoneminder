/*
 * This file is part of the ZoneMinder Project. See AUTHORS file for Copyright information
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

#include "zm_catch2.h"

#include "zm_config.h"

#include <cstdio>
#include <cstdlib>
#include <fstream>
#include <string>
#include <unistd.h>

namespace {

// Writes `body` to a fresh /tmp file and returns its path; caller unlinks.
std::string WriteTempConf(const std::string &body) {
  char path[] = "/tmp/zm_conf_test_XXXXXX";
  int fd = mkstemp(path);
  REQUIRE(fd >= 0);
  ssize_t n = write(fd, body.data(), body.size());
  REQUIRE(n == static_cast<ssize_t>(body.size()));
  close(fd);
  return std::string(path);
}

}  // namespace

TEST_CASE("process_configfile: backslash continuation joins lines") {
  const std::string body =
      "ZM_SERVER_NAME = first\\\n"
      "    second\\\n"
      "    third\n";
  std::string path = WriteTempConf(body);

  staticConfig.SERVER_NAME.clear();
  process_configfile(path.c_str());
  unlink(path.c_str());

  // Leading whitespace on continuation lines is trimmed.
  REQUIRE(staticConfig.SERVER_NAME == "firstsecondthird");
}

TEST_CASE("process_configfile: bare backslash inside value is preserved") {
  // No newline after the backslash, so it is not a continuation marker.
  const std::string body = "ZM_DIR_EXPORTS = C:\\Users\\zm\n";
  std::string path = WriteTempConf(body);

  staticConfig.DIR_EXPORTS.clear();
  process_configfile(path.c_str());
  unlink(path.c_str());

  REQUIRE(staticConfig.DIR_EXPORTS == "C:\\Users\\zm");
}

TEST_CASE("process_configfile: lines longer than the legacy 512-byte cap survive") {
  // A 1500-byte value would have been truncated by the old fgets() buffer.
  std::string long_value(1500, 'a');
  const std::string body = "ZM_DB_HOST = " + long_value + "\n";
  std::string path = WriteTempConf(body);

  staticConfig.DB_HOST.clear();
  process_configfile(path.c_str());
  unlink(path.c_str());

  REQUIRE(staticConfig.DB_HOST == long_value);
}

TEST_CASE("ConfigItem converts each declared type") {
  SECTION("boolean") {
    REQUIRE(ConfigItem("ZM_TEST", "1", "boolean").BooleanValue() == true);
    REQUIRE(ConfigItem("ZM_TEST", "0", "boolean").BooleanValue() == false);
  }
  SECTION("integer") {
    REQUIRE(ConfigItem("ZM_TEST", "42", "integer").IntegerValue() == 42);
  }
  SECTION("hexadecimal is parsed base 16") {
    REQUIRE(ConfigItem("ZM_TEST", "7a000000", "hexadecimal").IntegerValue() == 0x7a000000);
  }
  SECTION("decimal") {
    REQUIRE(ConfigItem("ZM_TEST", "1.5", "decimal").DecimalValue() == Catch::Approx(1.5));
  }
  SECTION("anything else is a string") {
    REQUIRE(std::string(ConfigItem("ZM_TEST", "hello", "text").StringValue()) == "hello");
  }
}

TEST_CASE("ConfigItem type mismatch is non-fatal and converts best-effort") {
  // A stale Config row can carry the wrong Type for the accessor the daemon
  // uses. That used to exit(-1); now it warns and converts from the raw value.
  SECTION("boolean accessor on a string item") {
    REQUIRE(ConfigItem("ZM_TEST", "yes", "text").BooleanValue() == true);
    REQUIRE(ConfigItem("ZM_TEST", "0", "text").BooleanValue() == false);
    REQUIRE(ConfigItem("ZM_TEST", "", "text").BooleanValue() == false);
  }
  SECTION("integer accessor on a string item") {
    REQUIRE(ConfigItem("ZM_TEST", "42", "text").IntegerValue() == 42);
  }
  SECTION("decimal accessor on a string item") {
    REQUIRE(ConfigItem("ZM_TEST", "1.5", "text").DecimalValue() == Catch::Approx(1.5));
  }
  SECTION("string accessor on an integer item returns the raw value") {
    REQUIRE(std::string(ConfigItem("ZM_TEST", "42", "integer").StringValue()) == "42");
  }
}

TEST_CASE("Config members hold compiled-in defaults before any database load") {
  // The whole point of name-based lookup: a daemon that never reaches the
  // Config table still has every member populated, so adding or removing rows
  // cannot shift anything. Values mirror ConfigData.pm.in.
  Config fresh;

  REQUIRE(fresh.opt_use_auth == false);
  REQUIRE(fresh.auth_hash_ttl == 2);
  REQUIRE(fresh.watch_max_delay == Catch::Approx(45));
  REQUIRE(fresh.font_file_location != nullptr);
  REQUIRE(std::string(fresh.font_file_location) != "");
}
