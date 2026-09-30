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

#include "zm_logger.h"
#include "zm_utils.h"

#include <csignal>
#include <cstdio>
#include <fstream>
#include <sstream>
#include <string>
#include <cstdlib>
#include <unistd.h>

static std::string slurp(const std::string &path) {
  std::ifstream in(path);
  std::stringstream ss;
  ss << in.rdbuf();
  return ss.str();
}

// logrotate renames the file and expects the daemon to let go of the old
// handle. SIGHUP does that but also means reload, which costs zmc a camera
// reconnect - see issue #5063 - so SIGWINCH is what logrotate reaches for.
TEST_CASE("SIGWINCH reopens the log file after rotation") {
  std::string path = stringtf("/tmp/zm_test_logrot_%d.log", getpid());
  std::string rotated = path + ".1";
  unlink(path.c_str());
  unlink(rotated.c_str());

  setenv("LOG_FLUSH", "1", 1);
  Logger::Options options(Logger::NOLOG, Logger::NOLOG, Logger::INFO, Logger::NOLOG);
  options.mLogFile = path;
  logInit("zm_test_logrot", options);

  Info("before rotation");
  REQUIRE(rename(path.c_str(), rotated.c_str()) == 0);
  Info("still holding the old handle");

  REQUIRE(slurp(rotated).find("still holding the old handle") != std::string::npos);
  REQUIRE(slurp(path).empty());

  raise(SIGWINCH);
  Info("after rotation");

  REQUIRE(slurp(path).find("after rotation") != std::string::npos);
  REQUIRE(slurp(rotated).find("after rotation") == std::string::npos);

  logTerm();
  unlink(path.c_str());
  unlink(rotated.c_str());
}
