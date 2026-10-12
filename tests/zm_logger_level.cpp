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
#include <cstdlib>
#include <fstream>
#include <sstream>
#include <string>
#include <unistd.h>

static std::string slurp(const std::string &path) {
  std::ifstream in(path);
  std::stringstream ss;
  ss << in.rdbuf();
  return ss.str();
}

// Logging takes log_mutex, glibc's syslog and tzset locks and the database
// queue's mutex, so the SIGUSR1/2 handler must not log: a signal landing on a
// thread that holds one of them would wait on itself. The handler only records
// the change; the next log call applies it and logs the new level.
TEST_CASE("SIGUSR1 changes the level at the next log call, not in the handler") {
  std::string path = stringtf("/tmp/zm_test_logusr1_defer_%d.log", getpid());
  unlink(path.c_str());

  setenv("LOG_FLUSH", "1", 1);
  Logger::Options options(Logger::NOLOG, Logger::NOLOG, Logger::DEBUG9, Logger::NOLOG);
  options.mLogFile = path;
  logInit("zm_test_logusr1_defer", options);
  // 1.38 caps each target at Info unless ZM_LOG_DEBUG is on; the Debug line
  // below needs the file target at debug.
  Logger::fetch()->fileLevel(Logger::DEBUG9);

  Info("before signal");
  raise(SIGUSR1);
  REQUIRE(slurp(path).find("Level changed") == std::string::npos);

  Debug(1, "debug after signal");
  std::string contents = slurp(path);
  size_t changed = contents.find("Level changed to 1 DB1");
  size_t debug = contents.find("debug after signal");
  REQUIRE(changed != std::string::npos);
  REQUIRE(debug != std::string::npos);
  REQUIRE(changed < debug);

  // Several signals before the next log call add up to one change.
  raise(SIGUSR1);
  raise(SIGUSR1);
  raise(SIGUSR2);
  REQUIRE(logLevel() == Logger::DEBUG2);

  // Lowering is clamped at NOLOG, as one signal at a time was.
  for (int i = 0; i < 12; i++) raise(SIGUSR2);
  REQUIRE(logLevel() == Logger::NOLOG);
  raise(SIGUSR1);
  REQUIRE(logLevel() == Logger::PANIC);

  logTerm();
  unlink(path.c_str());
}

TEST_CASE("A level change still pending doesn't carry over to a new logger") {
  Logger::Options options(Logger::NOLOG, Logger::NOLOG, Logger::NOLOG, Logger::NOLOG);
  logInit("zm_test_logusr1_pending", options);
  REQUIRE(logLevel() == Logger::INFO);

  raise(SIGUSR1);
  logTerm();
  logInit("zm_test_logusr1_pending", options);
  REQUIRE(logLevel() == Logger::INFO);

  logTerm();
}
