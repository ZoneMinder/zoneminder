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

// Without ZM_LOG_DEBUG the overall level is capped at INFO, but each target
// keeps its configured level. SIGUSR1 raises the overall level, as if
// ZM_LOG_DEBUG_LEVEL had been bumped, so debug reaches the targets configured
// for it. It used to clamp every target to INFO too, so SIGUSR1 did nothing.
TEST_CASE("SIGUSR1 turns on debug logging for targets configured for debug") {
  std::string path = stringtf("/tmp/zm_test_logusr1_%d.log", getpid());
  unlink(path.c_str());

  setenv("LOG_FLUSH", "1", 1);
  Logger::Options options(Logger::NOLOG, Logger::NOLOG, Logger::DEBUG9, Logger::NOLOG);
  options.mLogFile = path;
  logInit("zm_test_logusr1", options);

  Debug(1, "debug before signal");
  REQUIRE(slurp(path).find("debug before signal") == std::string::npos);

  raise(SIGUSR1);
  Debug(1, "debug after signal");
  Debug(2, "too verbose for one signal");

  std::string contents = slurp(path);
  REQUIRE(contents.find("debug after signal") != std::string::npos);
  REQUIRE(contents.find("too verbose for one signal") == std::string::npos);

  raise(SIGUSR2);
  Debug(1, "debug after lowering");
  Info("info after lowering");

  contents = slurp(path);
  REQUIRE(contents.find("debug after lowering") == std::string::npos);
  REQUIRE(contents.find("info after lowering") != std::string::npos);

  logTerm();
  unlink(path.c_str());
}

TEST_CASE("SIGUSR1 leaves targets configured for INFO at INFO") {
  std::string path = stringtf("/tmp/zm_test_logusr1_info_%d.log", getpid());
  unlink(path.c_str());

  setenv("LOG_FLUSH", "1", 1);
  Logger::Options options(Logger::NOLOG, Logger::NOLOG, Logger::INFO, Logger::NOLOG);
  options.mLogFile = path;
  logInit("zm_test_logusr1_info", options);

  raise(SIGUSR1);
  Debug(1, "debug after signal");
  Info("info after signal");

  std::string contents = slurp(path);
  REQUIRE(contents.find("debug after signal") == std::string::npos);
  REQUIRE(contents.find("info after signal") != std::string::npos);

  raise(SIGUSR2);
  logTerm();
  unlink(path.c_str());
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
  REQUIRE(logLevel() == Logger::AUDIT);

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

// LOG_FILE set but empty (an Environment=LOG_FILE= line) is no file name. It
// used to replace the configured log file with "" and every message printed
// "Called Logger::openFile() without a filename" instead of reaching the file.
TEST_CASE("An empty LOG_FILE falls back to the configured log file") {
  std::string path = stringtf("/tmp/zm_test_logempty_%d.log", getpid());
  unlink(path.c_str());

  setenv("LOG_FLUSH", "1", 1);
  setenv("LOG_FILE", "", 1);
  Logger::Options options(Logger::NOLOG, Logger::NOLOG, Logger::INFO, Logger::NOLOG);
  options.mLogFile = path;
  logInit("zm_test_logempty", options);
  unsetenv("LOG_FILE");

  Info("reaches the file");
  REQUIRE(slurp(path).find("reaches the file") != std::string::npos);

  logTerm();
  unlink(path.c_str());
}

// vsnprintf returns the length it wanted, not what fit. A message longer than
// logString used to leave the syslog end pointer past the buffer, and the
// syslog path then wrote a NUL there, corrupting the heap. A failed bulk
// Frames INSERT is enough to trigger it. Run under ASAN to catch a regression.
TEST_CASE("Logging a message longer than the log buffer stays in bounds") {
  std::string path = stringtf("/tmp/zm_test_loglong_%d.log", getpid());
  unlink(path.c_str());

  setenv("LOG_FLUSH", "1", 1);
  Logger::Options options(Logger::NOLOG, Logger::NOLOG, Logger::INFO, Logger::INFO);
  options.mLogFile = path;
  logInit("zm_test_loglong", options);

  std::string query(20000, 'x');
  Error("Can't run query %s: %d %s", query.c_str(), 1, "Unknown column");
  Info("still logging");

  std::string contents = slurp(path);
  REQUIRE(contents.find("still logging") != std::string::npos);
  size_t first_line_end = contents.find('\n');
  REQUIRE(first_line_end != std::string::npos);
  REQUIRE(first_line_end < 4096);
  REQUIRE(contents.substr(first_line_end - 1, 1) == "]");

  logTerm();
  unlink(path.c_str());
}

// Logged from one place so every line has the same prefix length.
static void LogSized(const std::string &message) {
  Error("%s", message.c_str());
}

// "]\n" and its NUL need three bytes. A message ending at offset 4094 or 4095
// is under the clamp, so strncpy wrote "]\n" or "]" over the NUL without a new
// one, and the line was written out with whatever followed logString.
TEST_CASE("A message ending at the last bytes of the log buffer is terminated") {
  std::string path = stringtf("/tmp/zm_test_logedge_%d.log", getpid());
  unlink(path.c_str());

  setenv("LOG_FLUSH", "1", 1);
  Logger::Options options(Logger::NOLOG, Logger::NOLOG, Logger::INFO, Logger::INFO);
  options.mLogFile = path;
  logInit("zm_test_logedge", options);

  LogSized("probe");
  const std::string probe_line = slurp(path);
  const size_t prefix = probe_line.find("probe");
  REQUIRE(prefix != std::string::npos);
  REQUIRE(prefix < 4000);
  // The date and time at the start of every line, up to the seconds.
  const std::string line_start = probe_line.substr(0, 8);

  for (size_t message_end : {4092u, 4093u, 4094u, 4095u}) {
    CAPTURE(message_end);
    const size_t logged_before = slurp(path).size();

    LogSized(std::string(message_end - prefix, 'x'));
    LogSized("next");

    const std::string contents = slurp(path).substr(logged_before);
    const size_t first_line_end = contents.find('\n');
    REQUIRE(first_line_end != std::string::npos);
    // Room for "]\n" and the NUL: the message is cut to end at 4093 at most.
    REQUIRE(first_line_end <= 4094);
    REQUIRE(contents[first_line_end - 1] == ']');
    // Nothing from past the buffer between the two lines.
    REQUIRE(contents.compare(first_line_end + 1, line_start.size(), line_start) == 0);
    REQUIRE(contents.find("next]") != std::string::npos);
  }

  logTerm();
  unlink(path.c_str());
}
