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

// Drives the real lock-contention retry path against a live server, which is
// the only way to reach it: the errors come from InnoDB deciding a deadlock or
// timing out a lock wait, and nothing in-process can fake that.
//
// Tagged [notCI] because it needs a reachable database and credentials in
// /etc/zm/zm.conf. It creates and drops one scratch table of its own and never
// touches a ZoneMinder table.

#include "zm_catch2.h"
#include "zm_config.h"
#include "zm_db.h"

#include <chrono>
#include <string>
#include <thread>

namespace {

constexpr const char *kTable = "ZM_ContentionProbe";

// A second session, so there is something to contend with. Raw libmysqlclient
// rather than the zmDb* helpers, which share one process-wide connection.
MYSQL *OpenAntagonist() {
  MYSQL *conn = mysql_init(nullptr);
  REQUIRE(conn != nullptr);
  MYSQL *ok = mysql_real_connect(conn,
                                 staticConfig.DB_HOST.c_str(),
                                 staticConfig.DB_USER.c_str(),
                                 staticConfig.DB_PASS.c_str(),
                                 staticConfig.DB_NAME.c_str(),
                                 0, nullptr, 0);
  REQUIRE(ok != nullptr);
  return conn;
}

void Exec(MYSQL *conn, const std::string &sql) {
  REQUIRE(mysql_query(conn, sql.c_str()) == 0);
}

struct Scratch {
  Scratch() {
    zmLoadStaticConfig();
    REQUIRE(zmDbConnect());
    zmDbDo(std::string("DROP TABLE IF EXISTS `") + kTable + "`");
    REQUIRE(zmDbDo(std::string("CREATE TABLE `") + kTable +
                   "` (`Id` INT PRIMARY KEY, `V` INT NOT NULL) ENGINE=InnoDB") == 1);
    std::string rows;
    for (int id = 1; id <= 200; id++) {
      if (!rows.empty()) rows += ",";
      rows += "(" + std::to_string(id) + ",0)";
    }
    REQUIRE(zmDbDo(std::string("INSERT INTO `") + kTable + "` (Id,V) VALUES " + rows) == 1);
    // Keep the waits short; the default is 50 seconds per attempt.
    REQUIRE(zmDbDo("SET SESSION innodb_lock_wait_timeout = 1") == 1);
  }
  ~Scratch() {
    zmDbDo(std::string("DROP TABLE IF EXISTS `") + kTable + "`");
  }
};

int ValueOf(int id) {
  zmDbRow row;
  MYSQL_RES *res = row.fetch(std::string("SELECT V FROM `") + kTable + "` WHERE Id=" + std::to_string(id));
  REQUIRE(res != nullptr);
  return atoi(row[0]);
}

}  // namespace

TEST_CASE("zmDbDoUpdate rides out a lock held by another session", "[notCI][dblive]") {
  Scratch scratch;
  MYSQL *other = OpenAntagonist();

  // Hold row 1 locked for long enough to blow the 1s lock wait more than once,
  // so the update only lands if it is actually being retried.
  Exec(other, "BEGIN");
  Exec(other, std::string("UPDATE `") + kTable + "` SET V=99 WHERE Id=1");

  std::thread releaser([other] {
    std::this_thread::sleep_for(std::chrono::milliseconds(2500));
    mysql_query(other, "ROLLBACK");
  });

  auto started = std::chrono::steady_clock::now();
  int affected = zmDbDoUpdate(std::string("UPDATE `") + kTable + "` SET V=7 WHERE Id=1");
  auto elapsed = std::chrono::steady_clock::now() - started;

  releaser.join();

  INFO("elapsed ms: " << std::chrono::duration_cast<std::chrono::milliseconds>(elapsed).count());
  REQUIRE(affected == 1);
  REQUIRE(ValueOf(1) == 7);
  // It cannot have succeeded on the first try; the lock was held past the
  // first timeout.
  REQUIRE(elapsed > std::chrono::milliseconds(1000));

  mysql_close(other);
}

TEST_CASE("zmDbDoUpdate gives up on a lock that is never released", "[notCI][dblive]") {
  Scratch scratch;
  MYSQL *other = OpenAntagonist();

  Exec(other, "BEGIN");
  Exec(other, std::string("UPDATE `") + kTable + "` SET V=99 WHERE Id=2");

  // Before the fix this looped forever. It must now return, and within the
  // budget: 5 lock waits of 1s plus ~3.1s of backoff.
  auto started = std::chrono::steady_clock::now();
  int rc = zmDbDoUpdate(std::string("UPDATE `") + kTable + "` SET V=7 WHERE Id=2");
  auto elapsed = std::chrono::steady_clock::now() - started;

  INFO("elapsed ms: " << std::chrono::duration_cast<std::chrono::milliseconds>(elapsed).count());
  REQUIRE(rc < 0);                                          // reported failure
  REQUIRE(elapsed > std::chrono::seconds(3));               // did retry
  REQUIRE(elapsed < std::chrono::seconds(20));              // did stop

  mysql_query(other, "ROLLBACK");
  mysql_close(other);
}

TEST_CASE("zmDbDo retries a real InnoDB deadlock", "[notCI][dblive]") {
  Scratch scratch;
  MYSQL *other = OpenAntagonist();

  // Both sides reach for a row the other holds, so InnoDB has to break the
  // cycle by rolling one of them back with ER_LOCK_DEADLOCK.
  //
  // It picks the cheaper transaction as the victim, weighted by rows changed,
  // so the other session deliberately dirties a hundred rows first. Without
  // that the victim is whichever side InnoDB happens to prefer, and the test
  // passes without ever reaching the retry.
  Exec(other, "BEGIN");
  Exec(other, std::string("UPDATE `") + kTable + "` SET V=V+1 WHERE Id BETWEEN 101 AND 200");

  REQUIRE(zmDbDo("BEGIN") == 1);
  REQUIRE(zmDbDo(std::string("UPDATE `") + kTable + "` SET V=V+1 WHERE Id=1") == 1);

  std::thread crosser([other] {
    // Blocks on row 1, held by the ZoneMinder connection, completing the cycle.
    mysql_query(other, "UPDATE `ZM_ContentionProbe` SET V=V+1 WHERE Id=1");
    mysql_query(other, "COMMIT");
  });

  std::this_thread::sleep_for(std::chrono::milliseconds(300));

  // Reaching for a row the heavy transaction holds. This is the statement that
  // gets rolled back, and it only reports success if the retry re-ran it.
  int rc = zmDbDo(std::string("UPDATE `") + kTable + "` SET V=V+1 WHERE Id=150");
  zmDbDo("COMMIT");
  crosser.join();

  REQUIRE(rc == 1);
  REQUIRE(ValueOf(150) == 2);  // the other session's +1, then ours on retry

  mysql_close(other);
}
