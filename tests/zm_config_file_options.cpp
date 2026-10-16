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

#include "zm_config.h"

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

// zm.conf and conf.d belong to the owner of the box, the Config table to the
// ZoneMinder admin, and the owner must be able to limit the admin. PHP and Perl
// already let a file value win; the daemons dropped every file option outside
// a fixed list and read it from the database. Config::Load() now uses
// file_options in place of the database value.
TEST_CASE("Config files: every option is kept, later files winning") {
  std::string first = WriteTempConf("ZM_OPT_USE_AUTH=0\nZM_AUTH_HASH_TTL=5\n");
  std::string second = WriteTempConf("ZM_OPT_USE_AUTH=1\nZM_SERVER_NAME=box1\n");
  staticConfig.file_options.clear();
  staticConfig.SERVER_NAME.clear();
  process_configfile(first.c_str());
  process_configfile(second.c_str());
  unlink(first.c_str());
  unlink(second.c_str());

  REQUIRE(staticConfig.file_options["ZM_OPT_USE_AUTH"] == "1");
  REQUIRE(staticConfig.file_options["ZM_AUTH_HASH_TTL"] == "5");
  // Static settings still apply as before.
  REQUIRE(staticConfig.SERVER_NAME == "box1");
  staticConfig.file_options.clear();
}

TEST_CASE("Config files: a boolean may be written as a word") {
  // The Config table stores 1/0, but a person editing a file writes yes or true.
  for (const char *value : {"1", "yes", "Yes", "true", "on"}) {
    CAPTURE(value);
    REQUIRE(ConfigFileValue(value, "boolean") == "1");
  }
  for (const char *value : {"0", "no", "false", "off"}) {
    CAPTURE(value);
    REQUIRE(ConfigFileValue(value, "boolean") == "0");
  }
  // Other types keep the value as written.
  REQUIRE(ConfigFileValue("yes", "string") == "yes");
  REQUIRE(ConfigFileValue("5", "integer") == "5");
}
