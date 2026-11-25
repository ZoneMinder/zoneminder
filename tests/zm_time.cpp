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

#include "zm_time.h"
#include "zm_utils.h"

#include <chrono>
#include <cstdlib>
#include <ctime>
#include <string>

// Build the local-time "%Y-%m-%d %H:%M:%S" string for a whole-second time_t the
// same way SetPath() derives the on-disk event directory, so the test stays
// timezone-independent (it compares against the same localtime conversion the
// production code uses rather than a hard-coded TZ-specific string).
static std::string LocalSecondString(time_t sec) {
  tm tm_local = {};
  localtime_r(&sec, &tm_local);
  char buffer[32];
  strftime(buffer, sizeof(buffer), "%Y-%m-%d %H:%M:%S", &tm_local);
  return std::string(buffer);
}

TEST_CASE("SystemTimePointToMysqlString: whole second has no fractional part") {
  // 1764623999 == 2025-12-01 21:19:59 UTC; exact-second timepoints must format
  // without a fractional component because the Events.StartDateTime column is
  // datetime (second precision).
  SystemTimePoint tp = std::chrono::system_clock::from_time_t(1764623999);
  REQUIRE(SystemTimePointToMysqlString(tp) == LocalSecondString(1764623999));
}

TEST_CASE("SystemTimePointToMysqlString: sub-second part is floored, never rounded up") {
  // refs #4870: a continuous event whose backdated start keyframe lands in the
  // last fraction of a second before local midnight must not be promoted to the
  // next second. MySQL 8 rounds fractional seconds when storing into a
  // datetime(0) column, while Event::SetPath() truncates via to_time_t(); if the
  // string we hand MySQL carries a roundable fractional, the DB row and the
  // on-disk day folder diverge by a day. The string must already be floored to
  // the whole second that SetPath() (to_time_t) uses.
  const time_t base_sec = 1764623999;  // floored second SetPath would use

  // Worst case: 999999us before the next second boundary - MySQL 8 would round
  // this up to base_sec + 1 if we emitted ".999999".
  SystemTimePoint tp_high =
    std::chrono::system_clock::from_time_t(base_sec) + Microseconds(999999);
  REQUIRE(SystemTimePointToMysqlString(tp_high) == LocalSecondString(base_sec));

  // Half-second: the classic round-vs-truncate divergence point.
  SystemTimePoint tp_half =
    std::chrono::system_clock::from_time_t(base_sec) + Microseconds(500000);
  REQUIRE(SystemTimePointToMysqlString(tp_half) == LocalSecondString(base_sec));

  // Small fraction: trivially floors with either policy, included for coverage.
  SystemTimePoint tp_low =
    std::chrono::system_clock::from_time_t(base_sec) + Microseconds(1);
  REQUIRE(SystemTimePointToMysqlString(tp_low) == LocalSecondString(base_sec));
}

TEST_CASE("SystemTimePointToMysqlString: matches the second used to build the event path") {
  // The DB StartDateTime and Event::SetPath() must resolve to the same calendar
  // day. SetPath() derives the directory from to_time_t(start_time); the Mysql
  // string must use that identical floored second so the day folder and the
  // StartDateTime row never disagree (refs #4870).
  const time_t base_sec = 1764623999;
  SystemTimePoint tp =
    std::chrono::system_clock::from_time_t(base_sec) + Microseconds(999999);

  time_t path_sec = std::chrono::system_clock::to_time_t(tp);
  REQUIRE(SystemTimePointToMysqlString(tp) == LocalSecondString(path_sec));
}

struct ScopedTimezone {
  std::string old_tz;
  bool had_tz;
  explicit ScopedTimezone(const char *tz) {
    const char *old = getenv("TZ");
    had_tz = (old != nullptr);
    if (had_tz) old_tz = old;
    setenv("TZ", tz, 1);
    tzset();
  }
  ~ScopedTimezone() {
    if (had_tz) setenv("TZ", old_tz.c_str(), 1);
    else unsetenv("TZ");
    tzset();
  }
};

TEST_CASE("StringToSystemTimePoint: reads daylight saving time wall clock times correctly") {
  // 2026-07-15 10:00:00 America/New_York is EDT (UTC-4), so 14:00:00 UTC.
  ScopedTimezone tz("America/New_York");
  SystemTimePoint summer = StringToSystemTimePoint("2026-07-15 10:00:00");
  REQUIRE(std::chrono::system_clock::to_time_t(summer) == 1784124000);
  REQUIRE(SystemTimePointToMysqlString(summer) == "2026-07-15 10:00:00");

  // 2026-01-15 10:00:00 America/New_York is EST (UTC-5), so 15:00:00 UTC.
  SystemTimePoint winter = StringToSystemTimePoint("2026-01-15 10:00:00");
  REQUIRE(std::chrono::system_clock::to_time_t(winter) == 1768489200);
}

TEST_CASE("StringToSystemTimePoint: an empty or unparseable string is the epoch") {
  // Tag and Event_Tag read nullable DATETIME columns through this and test the
  // result against Seconds(0) to decide whether a date was ever set.
  REQUIRE(StringToSystemTimePoint("") == SystemTimePoint());
  REQUIRE(StringToSystemTimePoint("garbage") == SystemTimePoint());
  REQUIRE(StringToSystemTimePoint("0000-00-00 00:00:00") == SystemTimePoint());
}

TEST_CASE("SystemTimePointToString: returns the formatted text, not an empty string") {
  ScopedTimezone tz("UTC");
  SystemTimePoint tp = std::chrono::system_clock::from_time_t(1764623999) + Microseconds(123456);
  std::string s = SystemTimePointToString(tp);
  REQUIRE_FALSE(s.empty());
  REQUIRE(s.size() == strlen(s.c_str()));
  REQUIRE(s.find("21:19:59.123456") != std::string::npos);
}

TEST_CASE("TimePointToString: the fraction belongs to the printed time", "[time]") {
  // TimePointToString() translates the steady clock point into wall clock time
  // for the seconds, so the printed microseconds must be taken from that same
  // wall clock value. Reading them from time_since_epoch() instead gives the
  // fraction of the time since boot, which has nothing to do with the seconds
  // printed next to it.
  TimePoint tp = std::chrono::steady_clock::now();
  std::string a = TimePointToString(tp);
  std::string b = SystemTimePointToString(std::chrono::system_clock::now());

  size_t a_dot = a.rfind('.');
  size_t b_dot = b.rfind('.');
  REQUIRE(a_dot != std::string::npos);
  REQUIRE(b_dot != std::string::npos);

  int64_t a_frac = std::stoll(a.substr(a_dot + 1, 6));
  int64_t b_frac = std::stoll(b.substr(b_dot + 1, 6));

  // Circular difference: the two calls straddle a second boundary when one
  // reads e.g. .999998 and the other .000001.
  int64_t diff = ((a_frac - b_frac) % 1000000 + 1000000) % 1000000;
  if (diff > 500000) diff = 1000000 - diff;
  REQUIRE(diff < 200000);
}

TEST_CASE("StringToSystemTimePoint: reads a URL decoded zms datetime parameter") {
  // zms decodes datetime=2026-07-15%2010:00:00 (or +) before parsing it.
  ScopedTimezone tz("America/New_York");
  REQUIRE(std::chrono::system_clock::to_time_t(StringToSystemTimePoint(UriDecode("2026-07-15%2010:00:00"))) == 1784124000);
  REQUIRE(std::chrono::system_clock::to_time_t(StringToSystemTimePoint(UriDecode("2026-07-15+10:00:00"))) == 1784124000);
  REQUIRE(StringToSystemTimePoint(UriDecode("2026-07-15")).time_since_epoch().count() == 0);
}
