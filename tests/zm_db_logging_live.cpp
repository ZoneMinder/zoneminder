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

// Lines logged while a database query runs, and whether they reach the Logs
// table. The insert into Logs is asynchronous (dbQueue) and has to be read back
// from the server, so this needs a live database.
//
// Tagged [notCI] because it needs a reachable database and credentials in
// /etc/zm/zm.conf. It only deletes the Logs rows it wrote itself.

#include "zm_catch2.h"
#include "zm_config.h"
#include "zm_db.h"
#include "zm_logger.h"
#include "zm_utils.h"

#include <atomic>
#include <chrono>
#include <cstdio>
#include <cstdlib>
#include <string>
#include <thread>
#include <vector>
#include <unistd.h>

namespace {

constexpr const char *kComponent = "zm_test_dblog";

std::string Marker(const char *what) {
  return stringtf("dblog-probe-%d-%s", getpid(), what);
}

int CountLogs(const std::string &like) {
  zmDbRow row;
  MYSQL_RES *res = row.fetch(stringtf(
      "SELECT COUNT(*) FROM `Logs` WHERE `Component`='%s' AND `Message` LIKE '%s'",
      kComponent, zmDbEscapeString(like).c_str()));
  REQUIRE(res != nullptr);
  return atoi(row[0]);
}

// The insert goes through dbQueue on its own thread, so poll for it.
bool WaitForLog(const std::string &like, std::chrono::milliseconds budget) {
  auto deadline = std::chrono::steady_clock::now() + budget;
  while (std::chrono::steady_clock::now() < deadline) {
    if (CountLogs(like) > 0) return true;
    std::this_thread::sleep_for(std::chrono::milliseconds(50));
  }
  return false;
}

struct LiveLogger {
  explicit LiveLogger(Logger::Level db_level) {
    zmLoadStaticConfig();
    REQUIRE(zmDbConnect());
    // The database level only sticks while connected.
    logInit(kComponent, Logger::Options(Logger::NOLOG, db_level, Logger::NOLOG, Logger::NOLOG));
    // Without ZM_LOG_DEBUG the overall level is capped at Info.
    Logger::fetch()->level(db_level);
    REQUIRE(Logger::fetch()->databaseLevel() == db_level);
  }
  ~LiveLogger() {
    zmDbDo(stringtf("DELETE FROM `Logs` WHERE `Component`='%s'", kComponent));
    logTerm();
  }
};

}  // namespace

TEST_CASE("a line logged while another thread runs a query reaches Logs", "[notCI][dblive]") {
  LiveLogger live(Logger::INFO);

  Info("%s", Marker("before").c_str());
  REQUIRE(WaitForLog(Marker("before"), std::chrono::seconds(5)));

  // Holds db_mutex and the connection for two seconds.
  std::thread query([] { zmDbDo("DO SLEEP(2)"); });
  std::this_thread::sleep_for(std::chrono::milliseconds(500));

  // Another thread, same process, nothing to do with the query.
  INFO("database level seen by this thread: " << Logger::fetch()->databaseLevel());
  CHECK(Logger::fetch()->databaseLevel() == Logger::INFO);
  Info("%s", Marker("during").c_str());

  query.join();
  CHECK(WaitForLog(Marker("during"), std::chrono::seconds(5)));
}

TEST_CASE("the queue thread's inserts into Logs do not log themselves", "[notCI][dblive]") {
  // With the database level at debug, every insert into Logs would log its own
  // "Success running sql query INSERT INTO `Logs` ..." line, which would be
  // inserted in turn, and so on without end. The queue thread keeps its lines
  // out; a query on any other thread still logs to the database.
  LiveLogger live(Logger::DEBUG9);

  REQUIRE(zmDbDo("DO 1") == 1);
  Debug(1, "%s", Marker("debug").c_str());
  REQUIRE(WaitForLog(Marker("debug"), std::chrono::seconds(5)));
  CHECK(WaitForLog("Success running sql query DO 1%", std::chrono::seconds(5)));

  // Give a loop, if there were one, time to show.
  std::this_thread::sleep_for(std::chrono::milliseconds(500));
  CHECK(CountLogs("Success running sql query INSERT INTO `Logs`%") == 0);
  const int rows = CountLogs("%");
  std::this_thread::sleep_for(std::chrono::milliseconds(500));
  CHECK(CountLogs("%") == rows);
}

TEST_CASE("an error from a query on another thread reaches Logs", "[notCI][dblive]") {
  // zmDbDo used to keep the calling thread out of the database for the whole
  // query, so "Can't run query" errors were only in the log files.
  LiveLogger live(Logger::INFO);

  const std::string table = stringtf("zm_test_dblog_missing_%d", getpid());
  // zmDbDo returns mysql_query's error code on failure, which is also 1, so
  // the log line is the only sign of the failure.
  zmDbDo(stringtf("SELECT 1 FROM `%s`", table.c_str()));
  CHECK(WaitForLog("Can't run query%" + table + "%", std::chrono::seconds(5)));
}

TEST_CASE("an error from a queued write reaches Logs", "[notCI][dblive]") {
  // Events, frames and stats are written through dbQueue too. Only the queue's
  // inserts into Logs are kept from logging; its other writes report errors.
  LiveLogger live(Logger::INFO);

  const std::string table = stringtf("zm_test_dblog_queued_missing_%d", getpid());
  dbQueue.push(stringtf("UPDATE `%s` SET x=1", table.c_str()));
  CHECK(WaitForLog("Can't run query%" + table + "%", std::chrono::seconds(5)));
}

TEST_CASE("a backlog in dbQueue does not deadlock threads that log", "[notCI][dblive]") {
  // With more than 40 rows waiting, the queue thread warns about the backlog.
  // It used to do that while holding the queue's mutex, and logPrint takes the
  // logger's mutex and then the queue's mutex to push a row: two threads taking
  // the same two locks in opposite order.
  LiveLogger live(Logger::INFO);

  // Keep the queue thread off the connection for a second so rows pile up.
  std::thread holder([] {
    std::lock_guard<std::mutex> lck(db_mutex);
    std::this_thread::sleep_for(std::chrono::seconds(1));
  });

  constexpr int kThreads = 4;
  std::atomic<int> done(0);
  std::atomic<int> logged(0);
  auto stop_at = std::chrono::steady_clock::now() + std::chrono::seconds(2);
  std::vector<std::thread> loggers;
  for (int t = 0; t < kThreads; t++) {
    loggers.emplace_back([t, stop_at, &done, &logged] {
      for (int i = 0; std::chrono::steady_clock::now() < stop_at; i++) {
        Info("%s %d %d", Marker("flood").c_str(), t, i);
        logged++;
        std::this_thread::sleep_for(std::chrono::microseconds(200));
      }
      done++;
    });
  }

  // A deadlock leaves these threads blocked for good; nothing can unblock them
  // to let the test fail cleanly, so give up on the process.
  auto deadline = std::chrono::steady_clock::now() + std::chrono::seconds(30);
  while (done < kThreads and std::chrono::steady_clock::now() < deadline)
    std::this_thread::sleep_for(std::chrono::milliseconds(100));
  if (done < kThreads) {
    fprintf(stderr, "dbQueue deadlock: %d of %d logging threads finished\n", done.load(), kThreads);
    std::_Exit(2);
  }
  for (std::thread &t : loggers) t.join();
  holder.join();

  // Every line reaches the table once the queue drains. That can take a while
  // on a busy host: one insert at a time.
  int rows = 0;
  auto drain = std::chrono::steady_clock::now() + std::chrono::seconds(120);
  while (std::chrono::steady_clock::now() < drain) {
    rows = CountLogs(Marker("flood") + "%");
    if (rows >= logged) break;
    std::this_thread::sleep_for(std::chrono::milliseconds(200));
  }
  INFO("lines logged: " << logged.load());
  CHECK(rows == logged);
}
