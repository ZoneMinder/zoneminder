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

#include "zm_ffmpeg_camera.h"
#include "zm_time.h"
#include "zm_utils.h"

#include <string>

// ComputeRealtimePace() is the pure decision behind FfmpegCamera's "realtime=1"
// (ffmpeg -re style) pacing: given the current packet timestamp, the active
// anchor timestamp, the wall-clock time elapsed since that anchor, and the
// discontinuity cap, it decides whether to re-anchor and how long to sleep.
static const Microseconds kCap = std::chrono::duration_cast<Microseconds>(Seconds(10));

TEST_CASE("ComputeRealtimePace: sleeps the full interval when no time has elapsed") {
  // 40ms into the stream with zero wall-clock elapsed -> wait the whole 40ms.
  RealtimePaceDecision d = ComputeRealtimePace(40000, 0, Microseconds(0), kCap);
  REQUIRE_FALSE(d.reanchor);
  REQUIRE(d.sleep == Microseconds(40000));
}

TEST_CASE("ComputeRealtimePace: sleeps only the remaining interval when partially elapsed") {
  // Target is 40ms ahead of the anchor, 15ms has already passed -> sleep 25ms.
  RealtimePaceDecision d = ComputeRealtimePace(40000, 0, Microseconds(15000), kCap);
  REQUIRE_FALSE(d.reanchor);
  REQUIRE(d.sleep == Microseconds(25000));
}

TEST_CASE("ComputeRealtimePace: non-zero anchor only the delta matters") {
  // Anchor at 1s, packet at 1.040s, 10ms elapsed -> 30ms remaining.
  RealtimePaceDecision d = ComputeRealtimePace(1040000, 1000000, Microseconds(10000), kCap);
  REQUIRE_FALSE(d.reanchor);
  REQUIRE(d.sleep == Microseconds(30000));
}

TEST_CASE("ComputeRealtimePace: behind schedule delivers immediately without re-anchoring") {
  // Only 40ms into the stream but 100ms of wall-clock has passed: we are behind,
  // so deliver now (no sleep) and keep the anchor so we can catch back up.
  RealtimePaceDecision d = ComputeRealtimePace(40000, 0, Microseconds(100000), kCap);
  REQUIRE_FALSE(d.reanchor);
  REQUIRE(d.sleep == Microseconds(0));
}

TEST_CASE("ComputeRealtimePace: exactly on schedule does not sleep") {
  RealtimePaceDecision d = ComputeRealtimePace(40000, 0, Microseconds(40000), kCap);
  REQUIRE_FALSE(d.reanchor);
  REQUIRE(d.sleep == Microseconds(0));
}

TEST_CASE("ComputeRealtimePace: backward timestamp re-anchors instead of sleeping") {
  // A timestamp before the anchor (discontinuity/reset) must never produce a
  // negative sleep; it re-anchors so pacing restarts from the new position.
  RealtimePaceDecision d = ComputeRealtimePace(500000, 1000000, Microseconds(0), kCap);
  REQUIRE(d.reanchor);
  REQUIRE(d.sleep == Microseconds(0));
}

TEST_CASE("ComputeRealtimePace: gap beyond the cap re-anchors instead of stalling") {
  // 30s ahead of schedule with a 10s cap is treated as a discontinuity, not a
  // genuine 30s frame interval, so we re-anchor rather than sleep 30s.
  RealtimePaceDecision d =
    ComputeRealtimePace(30 * 1000000LL, 0, Microseconds(0), kCap);
  REQUIRE(d.reanchor);
  REQUIRE(d.sleep == Microseconds(0));
}

TEST_CASE("ComputeRealtimePace: a delay right at the cap still sleeps") {
  // Boundary: delay == cap is allowed (only delays strictly greater re-anchor).
  RealtimePaceDecision d = ComputeRealtimePace(10 * 1000000LL, 0, Microseconds(0), kCap);
  REQUIRE_FALSE(d.reanchor);
  REQUIRE(d.sleep == kCap);
}

// The Options field is entered as a textarea so an option can go on its own
// line, but ffmpeg gets it as one string. av_dict_parse_string() takes a set of
// pair separator characters, so this needs no parsing of our own - but only if
// the newlines are actually in that set. With just "," a newline is swallowed
// into the preceding value and every option after the first is lost. These
// exercise kOptionSeparators itself, the value FfmpegCamera passes.
static AVDictionary *parse_options(const char *options, const char *pairs_sep) {
  AVDictionary *opts = nullptr;
  REQUIRE(av_dict_parse_string(&opts, options, "=", pairs_sep, 0) == 0);
  return opts;
}

static std::string dict_get(AVDictionary *opts, const char *key) {
  const AVDictionaryEntry *e = av_dict_get(opts, key, nullptr, AV_DICT_MATCH_CASE);
  return e ? e->value : "";
}

TEST_CASE("ffmpeg options separated by newlines parse as separate entries") {
  SECTION("newline separated") {
    AVDictionary *opts = parse_options("rtsp_transport=tcp\nstimeout=5000000", kOptionSeparators);
    REQUIRE(av_dict_count(opts) == 2);
    REQUIRE(dict_get(opts, "rtsp_transport") == "tcp");
    REQUIRE(dict_get(opts, "stimeout") == "5000000");
    av_dict_free(&opts);
  }

  SECTION("comma separated still works") {
    AVDictionary *opts = parse_options("rtsp_transport=tcp,stimeout=5000000", kOptionSeparators);
    REQUIRE(av_dict_count(opts) == 2);
    REQUIRE(dict_get(opts, "rtsp_transport") == "tcp");
    av_dict_free(&opts);
  }

  SECTION("crlf, blank lines and a trailing newline are not empty entries") {
    AVDictionary *opts = parse_options("rtsp_transport=tcp\r\n\r\nstimeout=5000000\n", kOptionSeparators);
    REQUIRE(av_dict_count(opts) == 2);
    REQUIRE(dict_get(opts, "stimeout") == "5000000");
    av_dict_free(&opts);
  }

  SECTION("a comma-only separator would lose everything after the first newline") {
    AVDictionary *opts = parse_options("rtsp_transport=tcp\nstimeout=5000000", ",");
    REQUIRE(av_dict_count(opts) == 1);
    REQUIRE(dict_get(opts, "rtsp_transport") == "tcp\nstimeout=5000000");
    av_dict_free(&opts);
  }
}
