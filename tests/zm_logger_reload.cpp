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

#include <atomic>
#include <fstream>
#include <sstream>
#include <string>
#include <thread>
#include <vector>
#include <unistd.h>

static std::string slurp(const std::string &path) {
  std::ifstream in(path);
  std::stringstream ss;
  ss << in.rdbuf();
  return ss.str();
}

// zmc and zm_rtsp_server reload their configuration on SIGHUP and call
// logReload() from their main loop. Other threads keep logging through that,
// the dbQueue thread and the stream socket threads among them, each through
// the instance Logger::fetch() gave it. The reload must leave that instance
// valid. When the reload deleted and recreated the logger
// (logTerm(); logInit()), AddressSanitizer reported a heap-use-after-free here.
TEST_CASE("logReload while other threads log keeps the logger they hold") {
  std::string path = stringtf("/tmp/zm_test_logreload_%d.log", getpid());
  unlink(path.c_str());

  setenv("LOG_FLUSH", "1", 1);
  Logger::Options options(Logger::NOLOG, Logger::NOLOG, Logger::INFO, Logger::NOLOG);
  options.mLogFile = path;
  logInit("zm_test_logreload", options);

  Logger *before = Logger::fetch();
  std::atomic<bool> stop(false);
  std::atomic<int> lines(0);
  std::vector<std::thread> workers;
  for (int t = 0; t < 4; t++) {
    workers.emplace_back([&stop, &lines, t]() {
      while (!stop) {
        Info("worker %d line %d", t, lines++);
      }
    });
  }

  for (int i = 0; i < 300; i++) {
    logReload("zm_test_logreload", options);
  }
  stop = true;
  for (std::thread &worker : workers) worker.join();

  CHECK(Logger::fetch() == before);
  Info("after the reloads");
  CHECK(slurp(path).find("after the reloads") != std::string::npos);
  CHECK(lines > 0);

  logTerm();
  unlink(path.c_str());
}
