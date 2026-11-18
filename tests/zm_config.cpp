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

  REQUIRE(fresh.opt_use_auth == true);
  REQUIRE(fresh.auth_hash_ttl == 2);
  REQUIRE(fresh.watch_max_delay == Catch::Approx(45));
  REQUIRE(fresh.font_file_location != nullptr);
  REQUIRE(std::string(fresh.font_file_location) != "");
}

// zm.conf and conf.d belong to the owner of the box, the Config table to the
// ZoneMinder admin, and the owner must be able to limit the admin. PHP and Perl
// already let a file value win; the daemons ignored every file option outside
// a fixed list and read it from the database, so an admin could turn off, say,
// ZM_OPT_USE_AUTH for zms while the web UI kept it on.
TEST_CASE("Config: an option set in a config file wins over the database") {
  std::string first = WriteTempConf("ZM_OPT_USE_AUTH=0\nZM_AUTH_HASH_TTL=5\n");
  std::string second = WriteTempConf("ZM_OPT_USE_AUTH=1\nZM_NOT_AN_OPTION=x\n");
  staticConfig.file_options.clear();
  process_configfile(first.c_str());
  process_configfile(second.c_str());
  unlink(first.c_str());
  unlink(second.c_str());

  // conf.d files are read in order and a later one wins.
  REQUIRE(staticConfig.file_options["ZM_OPT_USE_AUTH"] == "1");

  Config cfg;
  cfg.opt_use_auth = false;  // as loaded from the Config table
  cfg.auth_hash_ttl = 2;
  cfg.ApplyFileOptions();
  REQUIRE(cfg.opt_use_auth == true);
  REQUIRE(cfg.auth_hash_ttl == 5);
  staticConfig.file_options.clear();
}

TEST_CASE("Config: a boolean in a config file may be written as a word") {
  // The Config table stores 1/0, but a person editing a file writes yes or true.
  const char *on[] = {"1", "yes", "Yes", "true", "on"};
  const char *off[] = {"0", "no", "false", "off", ""};
  for (const char *value : on) {
    CAPTURE(value);
    staticConfig.file_options = {{"ZM_OPT_USE_AUTH", value}};
    Config cfg;
    cfg.ApplyFileOptions();
    REQUIRE(cfg.opt_use_auth == true);
  }
  for (const char *value : off) {
    CAPTURE(value);
    staticConfig.file_options = {{"ZM_OPT_USE_AUTH", value}};
    Config cfg;
    cfg.opt_use_auth = true;
    cfg.ApplyFileOptions();
    REQUIRE(cfg.opt_use_auth == false);
  }
  staticConfig.file_options.clear();
}

TEST_CASE("Config: static settings in a config file still apply") {
  std::string path = WriteTempConf("ZM_SERVER_NAME=box1\n");
  staticConfig.file_options.clear();
  staticConfig.SERVER_NAME.clear();
  process_configfile(path.c_str());
  unlink(path.c_str());
  REQUIRE(staticConfig.SERVER_NAME == "box1");
  staticConfig.file_options.clear();
}

TEST_CASE("process_configfile: quote-only and space-only lines do not underflow buffer") {
  // Lines consisting only of quotes and spaces have no '='. They must not
  // cause a buffer underflow when trimming trailing quotes/whitespace.
  const std::string body =
      "\"\n"
      "\"\"\n"
      "\'\n"
      "\'\'\n"
      "   \"   \n"
      "   \'   \n";
  std::string path = WriteTempConf(body);

  staticConfig.file_options.clear();
  process_configfile(path.c_str());
  unlink(path.c_str());

  REQUIRE(staticConfig.file_options.empty());
}

TEST_CASE("process_configfile: leading quotes stripped even with whitespace after '='") {
  const std::string body =
      "ZM_DB_HOST = \"localhost\"\n"
      "ZM_DB_NAME = 'zm'\n"
      "ZM_DB_USER = \"\"\n"
      "ZM_DB_PASS = ''\n"
      "ZM_PATH_WEB =   \"/usr/share/zoneminder/www\"\n";
  std::string path = WriteTempConf(body);

  staticConfig.DB_HOST.clear();
  staticConfig.DB_NAME.clear();
  staticConfig.DB_USER.clear();
  staticConfig.DB_PASS.clear();
  staticConfig.PATH_WEB.clear();
  staticConfig.file_options.clear();

  process_configfile(path.c_str());
  unlink(path.c_str());

  REQUIRE(staticConfig.DB_HOST == "localhost");
  REQUIRE(staticConfig.DB_NAME == "zm");
  REQUIRE(staticConfig.DB_USER == "");
  REQUIRE(staticConfig.DB_PASS == "");
  REQUIRE(staticConfig.PATH_WEB == "/usr/share/zoneminder/www");

  REQUIRE(staticConfig.file_options["ZM_DB_HOST"] == "localhost");
  REQUIRE(staticConfig.file_options["ZM_DB_NAME"] == "zm");
  REQUIRE(staticConfig.file_options["ZM_DB_USER"] == "");
  REQUIRE(staticConfig.file_options["ZM_DB_PASS"] == "");
  REQUIRE(staticConfig.file_options["ZM_PATH_WEB"] == "/usr/share/zoneminder/www");

  staticConfig.file_options.clear();
}

TEST_CASE("process_configfile: lowercase and mixed-case option names apply to daemon config") {
  // Options written in lowercase or mixed case in zm.conf or conf.d must not be
  // silently ignored by ApplyFileOptions().
  const std::string body =
      "zm_opt_use_auth = 0\n"
      "ZM_auth_HASH_ttl = 9\n";
  std::string path = WriteTempConf(body);

  staticConfig.file_options.clear();
  process_configfile(path.c_str());
  unlink(path.c_str());

  REQUIRE(staticConfig.file_options.count("ZM_OPT_USE_AUTH") == 1);
  REQUIRE(staticConfig.file_options["ZM_OPT_USE_AUTH"] == "0");
  REQUIRE(staticConfig.file_options.count("ZM_AUTH_HASH_TTL") == 1);
  REQUIRE(staticConfig.file_options["ZM_AUTH_HASH_TTL"] == "9");

  Config cfg;
  cfg.opt_use_auth = true;
  cfg.auth_hash_ttl = 2;
  cfg.ApplyFileOptions();

  REQUIRE(cfg.opt_use_auth == false);
  REQUIRE(cfg.auth_hash_ttl == 9);

  staticConfig.file_options.clear();
}

TEST_CASE("process_configfile: lines starting with '=' do not store empty key") {
  const std::string body =
      "=broken\n"
      "   = invalid\n"
      "ZM_SERVER_NAME = valid\n";
  std::string path = WriteTempConf(body);

  staticConfig.file_options.clear();
  staticConfig.SERVER_NAME.clear();

  process_configfile(path.c_str());
  unlink(path.c_str());

  REQUIRE(staticConfig.file_options.count("") == 0);
  REQUIRE(staticConfig.SERVER_NAME == "valid");
  REQUIRE(staticConfig.file_options["ZM_SERVER_NAME"] == "valid");

  staticConfig.file_options.clear();
}

TEST_CASE("ConfigItem: boolean conversions for word values and fallback") {
  SECTION("boolean item converts words properly") {
    REQUIRE(ConfigItem("ZM_TEST", "yes", "boolean").BooleanValue() == true);
    REQUIRE(ConfigItem("ZM_TEST", "Yes", "boolean").BooleanValue() == true);
    REQUIRE(ConfigItem("ZM_TEST", "true", "boolean").BooleanValue() == true);
    REQUIRE(ConfigItem("ZM_TEST", "True", "boolean").BooleanValue() == true);
    REQUIRE(ConfigItem("ZM_TEST", "on", "boolean").BooleanValue() == true);
    REQUIRE(ConfigItem("ZM_TEST", "no", "boolean").BooleanValue() == false);
    REQUIRE(ConfigItem("ZM_TEST", "false", "boolean").BooleanValue() == false);
    REQUIRE(ConfigItem("ZM_TEST", "off", "boolean").BooleanValue() == false);
    REQUIRE(ConfigItem("ZM_TEST", "0", "boolean").BooleanValue() == false);
    REQUIRE(ConfigItem("ZM_TEST", "", "boolean").BooleanValue() == false);
  }
  SECTION("boolean accessor fallback on string item handles false words") {
    REQUIRE(ConfigItem("ZM_TEST", "no", "text").BooleanValue() == false);
    REQUIRE(ConfigItem("ZM_TEST", "No", "text").BooleanValue() == false);
    REQUIRE(ConfigItem("ZM_TEST", "false", "text").BooleanValue() == false);
    REQUIRE(ConfigItem("ZM_TEST", "off", "text").BooleanValue() == false);
    REQUIRE(ConfigItem("ZM_TEST", "0", "text").BooleanValue() == false);
    REQUIRE(ConfigItem("ZM_TEST", "", "text").BooleanValue() == false);
    REQUIRE(ConfigItem("ZM_TEST", "yes", "text").BooleanValue() == true);
    REQUIRE(ConfigItem("ZM_TEST", "true", "text").BooleanValue() == true);
    REQUIRE(ConfigItem("ZM_TEST", "on", "text").BooleanValue() == true);
    REQUIRE(ConfigItem("ZM_TEST", "1", "text").BooleanValue() == true);
  }
}

TEST_CASE("StaticConfig: SERVER_ID is zero-initialized") {
  StaticConfig cfg;
  REQUIRE(cfg.SERVER_ID == 0);
}

// ZM_SHM_KEY is hexadecimal. The Config table value is parsed as hex, but a
// config file value went through the decimal path and "0x7a6e0000" became 0,
// so a second instance given its own key in zm.conf used shm key 0.
TEST_CASE("Config: a hexadecimal option in a config file is read as hex") {
  staticConfig.file_options = {{"ZM_SHM_KEY", "0x7a6e0000"}};
  Config cfg;
  cfg.ApplyFileOptions();
  REQUIRE(cfg.shm_key == 0x7a6e0000);

  staticConfig.file_options = {{"ZM_AUTH_HASH_TTL", "010"}};
  cfg.ApplyFileOptions();
  REQUIRE(cfg.auth_hash_ttl == 10);
  staticConfig.file_options.clear();
}

// Other threads keep reading string options while zmc or zm_rtsp_server
// reloads, so a reload must not free the string an option pointed to.
TEST_CASE("Config: a reload keeps the previous string value readable") {
  const std::string first(40, 'a');
  const std::string second(40, 'b');
  staticConfig.file_options = {{"ZM_AUTH_HASH_SECRET", first}};
  Config cfg;
  cfg.ApplyFileOptions();
  const char *before = cfg.auth_hash_secret;

  staticConfig.file_options = {{"ZM_AUTH_HASH_SECRET", second}};
  cfg.ApplyFileOptions();
  REQUIRE(std::string(cfg.auth_hash_secret) == second);
  REQUIRE(std::string(before) == first);
  staticConfig.file_options.clear();
}
