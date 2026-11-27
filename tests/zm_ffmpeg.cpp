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

#include "zm_ffmpeg.h"

#include <string>

TEST_CASE("av_log_should_print", "[ffmpeg]") {
  constexpr int64_t kMinute = 60 * 1000 * 1000;
  AvLogRepeat state;
  uint64_t suppressed = 0;

  SECTION("the first message of its kind prints") {
    REQUIRE(av_log_should_print(state, "SEI type 764 truncated", 1000, kMinute, &suppressed));
    REQUIRE(suppressed == 0);
  }

  // The case that filled 40MB of one monitor's log: libavcodec reports this
  // per frame at AV_LOG_ERROR, which ZM maps to Warning, which is a database
  // row each time.
  SECTION("repeats inside the interval are counted, not printed") {
    REQUIRE(av_log_should_print(state, "SEI truncated", 0, kMinute, &suppressed));
    for (int i = 1; i <= 500; i++) {
      REQUIRE_FALSE(av_log_should_print(state, "SEI truncated", i * 1000, kMinute, &suppressed));
    }
    REQUIRE(av_log_should_print(state, "SEI truncated", kMinute, kMinute, &suppressed));
    REQUIRE(suppressed == 500);
  }

  SECTION("a different message prints immediately rather than waiting") {
    REQUIRE(av_log_should_print(state, "first", 0, kMinute, &suppressed));
    REQUIRE(av_log_should_print(state, "second", 1000, kMinute, &suppressed));
  }

  // The failure the first version shipped with. The camera's SEI complaints
  // cycle through three sizes, so every message differs from the one before,
  // and a scheme that only remembers the previous message suppresses nothing.
  SECTION("messages that alternate are each limited on their own") {
    const char *distinct[] = {"size 66", "size 50", "size 34"};
    for (const char *m : distinct) REQUIRE(av_log_should_print(state, m, 0, kMinute, &suppressed));

    // Now the real traffic, which repeats some of them within a round.
    const char *cycle[] = {"size 66", "size 66", "size 50", "size 50", "size 34"};
    // Still inside the interval: every one of them is now known.
    for (int round = 1; round < 200; round++) {
      for (const char *m : cycle) {
        REQUIRE_FALSE(av_log_should_print(state, m, round * 1000, kMinute, &suppressed));
      }
    }

    // Each reports its own count: 199 rounds, and "size 66" appears twice a round.
    REQUIRE(av_log_should_print(state, "size 66", kMinute, kMinute, &suppressed));
    REQUIRE(suppressed == 2 * 199);
    REQUIRE(av_log_should_print(state, "size 34", kMinute, kMinute, &suppressed));
    REQUIRE(suppressed == 199);
  }

  SECTION("an endlessly varying source costs a bounded amount") {
    for (int i = 0; i < 10 * static_cast<int>(kAvLogRepeatTracked); i++) {
      REQUIRE(av_log_should_print(state, "msg " + std::to_string(i), i, kMinute, &suppressed));
    }
    REQUIRE(state.recent.size() == kAvLogRepeatTracked);
  }

  SECTION("the count resets once reported, so it is per interval not cumulative") {
    REQUIRE(av_log_should_print(state, "x", 0, kMinute, &suppressed));
    REQUIRE_FALSE(av_log_should_print(state, "x", 1, kMinute, &suppressed));
    REQUIRE(av_log_should_print(state, "x", kMinute, kMinute, &suppressed));
    REQUIRE(suppressed == 1);
    REQUIRE(av_log_should_print(state, "x", 2 * kMinute, kMinute, &suppressed));
    REQUIRE(suppressed == 0);
  }
}
