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

#include <map>

static std::map<std::string, std::string> parse(const std::string &text, int *ret = nullptr) {
  AVDictionary *opts = nullptr;
  int r = zm_parse_options(&opts, text, "#,\n");
  if (ret) *ret = r;
  std::map<std::string, std::string> out;
  const AVDictionaryEntry *e = nullptr;
  while ((e = av_dict_get(opts, "", e, AV_DICT_IGNORE_SUFFIX))) out[e->key] = e->value;
  av_dict_free(&opts);
  return out;
}

TEST_CASE("zm_parse_options reads key=value entries", "[ffmpeg]") {
  int ret = -1;
  auto o = parse("preset=fast\ncrf=23,g=30\r\n  profile = high  ", &ret);
  REQUIRE(ret == 0);
  REQUIRE(o.size() == 4);
  REQUIRE(o["preset"] == "fast");
  REQUIRE(o["crf"] == "23");
  REQUIRE(o["g"] == "30");
  REQUIRE(o["profile"] == "high");
}

TEST_CASE("zm_parse_options keeps '=' and quoted separators in values", "[ffmpeg]") {
  auto o = parse("x264-params=keyint=30:min-keyint=30\nvf='a,b'");
  REQUIRE(o["x264-params"] == "keyint=30:min-keyint=30");
  REQUIRE(o["vf"] == "a,b");
}

TEST_CASE("zm_parse_options skips an entry without '=' instead of merging it into the next", "[ffmpeg]") {
  auto o = parse("preset=fast\n# note\ncrf=45\nfoo\ng=5");
  REQUIRE(o.size() == 3);
  REQUIRE(o["preset"] == "fast");
  REQUIRE(o["crf"] == "45");
  REQUIRE(o["g"] == "5");
}

TEST_CASE("zm_parse_options skips an empty value instead of taking the next line as the value", "[ffmpeg]") {
  auto o = parse("crf=\ng=5\nbf=\r\nrefs=2\nlevel=");
  REQUIRE(o.size() == 2);
  REQUIRE(o["g"] == "5");
  REQUIRE(o["refs"] == "2");
}

TEST_CASE("zm_parse_options skips an entry without a name", "[ffmpeg]") {
  auto o = parse("=5\ng=30");
  REQUIRE(o.size() == 1);
  REQUIRE(o["g"] == "30");
}

TEST_CASE("zm_parse_options ignores a trailing comma and a movflags list", "[ffmpeg]") {
  auto o = parse("movflags=frag_keyframe,empty_moov,");
  REQUIRE(o.size() == 1);
  REQUIRE(o["movflags"] == "frag_keyframe");
}

TEST_CASE("zm_parse_options treats '#' as a comment to the end of the line", "[ffmpeg]") {
  int ret = -1;
  auto o = parse("# Lines beginning with # are a comment\n"
                 "# For changing quality, use the crf option\n"
                 "  # 1 is best, 51 is worst quality\n"
                 "#g=5\n"
                 "# set crf=23 here\n"
                 "crf=30 # balanced\n"
                 "preset=fast", &ret);
  REQUIRE(ret == 0);
  REQUIRE(o.size() == 2);
  REQUIRE(o.count("g") == 0);
  REQUIRE(o.count("set crf") == 0);
  REQUIRE(o["crf"] == "30");
  REQUIRE(o["preset"] == "fast");
}

TEST_CASE("zm_parse_options on empty text gives nothing", "[ffmpeg]") {
  REQUIRE(parse("").empty());
  REQUIRE(parse("\n\n,#").empty());
}

TEST_CASE("zm_parse_options drops passlogfile", "[ffmpeg]") {
  auto o = parse("passlogfile=/tmp/x\npreset=fast");
  REQUIRE(o.count("passlogfile") == 0);
  REQUIRE(o["preset"] == "fast");
}

TEST_CASE("zm_parse_options drops stats", "[ffmpeg]") {
  auto o = parse("stats=/tmp/s\ncrf=23");
  REQUIRE(o.count("stats") == 0);
  REQUIRE(o["crf"] == "23");
}

TEST_CASE("zm_parse_options drops pass flags but keeps other flags", "[ffmpeg]") {
  auto o = parse("flags=+pass1\ng=30");
  REQUIRE(o.count("flags") == 0);
  REQUIRE(o["g"] == "30");

  auto o2 = parse("flags=+pass2");
  REQUIRE(o2.count("flags") == 0);

  auto o3 = parse("flags=+global_header");
  REQUIRE(o3["flags"] == "+global_header");
}

TEST_CASE("zm_parse_options drops x264-params file-writing entries", "[ffmpeg]") {
  auto o = parse("x264-params=stats=/tmp/s:pass=1:keyint=30");
  REQUIRE(o["x264-params"] == "keyint=30");
}

TEST_CASE("zm_parse_options drops x265-params csv entries", "[ffmpeg]") {
  auto o = parse("x265-params=csv=/tmp/c:csv-log-level=1:crf=20");
  REQUIRE(o["x265-params"] == "crf=20");
}

TEST_CASE("zm_parse_options drops x265-params when only csv is left", "[ffmpeg]") {
  auto o = parse("x265-params=csv=/tmp/c");
  REQUIRE(o.count("x265-params") == 0);
}

TEST_CASE("zm_parse_options keeps x264-params without file-writing entries", "[ffmpeg]") {
  auto o = parse("x264-params=keyint=30:min-keyint=30");
  REQUIRE(o["x264-params"] == "keyint=30:min-keyint=30");
}

TEST_CASE("zm_parse_options drops x265-params file-writing entries", "[ffmpeg]") {
  auto o = parse("x265-params=analysis-save=/tmp/a:analysis-load=/tmp/b:scaling-list=/tmp/l:recon=/tmp/r:no-open-gop=1");
  REQUIRE(o["x265-params"] == "no-open-gop=1");
}

TEST_CASE("zm_parse_options drops file-writing entries spelled with _ or a leading --", "[ffmpeg]") {
  auto o = parse("x264-params=dump_yuv=/tmp/d:keyint=30");
  REQUIRE(o["x264-params"] == "keyint=30");
  auto o2 = parse("x265-params=analysis_save=/tmp/a:--csv=/tmp/c:crf=20");
  REQUIRE(o2["x265-params"] == "crf=20");
}

TEST_CASE("zm_parse_options keeps a built-in scaling-list preset and drops a scaling-list file", "[ffmpeg]") {
  auto o = parse("x265-params=scaling-list=default:crf=20");
  REQUIRE(o["x265-params"] == "scaling-list=default:crf=20");
  o = parse("x264-params=scaling-list=flat:keyint=30");
  REQUIRE(o["x264-params"] == "scaling-list=flat:keyint=30");
  o = parse("x264-params=scaling-list=/etc/passwd:keyint=30");
  REQUIRE(o["x264-params"] == "keyint=30");
}
