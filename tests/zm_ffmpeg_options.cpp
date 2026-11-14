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

TEST_CASE("zm_parse_options on empty text gives nothing", "[ffmpeg]") {
  REQUIRE(parse("").empty());
  REQUIRE(parse("\n\n,#").empty());
}
