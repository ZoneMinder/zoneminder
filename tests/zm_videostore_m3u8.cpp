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

#include "zm_videostore.h"

#include <algorithm>
#include <string>
#include <vector>

namespace {

std::vector<VideoStore::Fragment> MakeFragments(size_t n, double duration = 1.2) {
  std::vector<VideoStore::Fragment> frags;
  int64_t offset = 1024;
  for (size_t i = 0; i < n; i++) {
    VideoStore::Fragment f;
    f.offset = offset;
    f.size = 40000 + static_cast<int64_t>(i);
    f.duration = duration;
    frags.push_back(f);
    offset += f.size;
  }
  return frags;
}

// What writeM3U8 writes when it rewrites the file from scratch.
std::string WholeManifest(const std::vector<VideoStore::Fragment> &frags,
                          const std::string &url, int64_t init_end, bool complete) {
  std::string out = VideoStore::m3u8Header(
      VideoStore::m3u8TargetDuration(frags), url, init_end, complete);
  for (const auto &f : frags) out += VideoStore::m3u8Fragment(f, url);
  if (complete) out += "#EXT-X-ENDLIST\n";
  return out;
}

}  // namespace

TEST_CASE("m3u8 appending produces the same manifest as rewriting") {
  // This is the whole point of appending: a reader must not be able to tell
  // which path wrote the file. Rewriting the manifest for every fragment is
  // O(fragments) per fragment and so O(fragments^2) over an event, which is
  // what let a monitor whose events stopped closing saturate its disk.
  const std::string url = "index.php?view=view_video&eid=6477854&file=incomplete.h264.mp4";
  const int64_t init_end = 1024;

  SECTION("growing one fragment at a time matches a single full write") {
    const auto all = MakeFragments(50);

    // Appending: header once, then each fragment's lines as it arrives.
    std::string appended = VideoStore::m3u8Header(
        VideoStore::m3u8TargetDuration(all), url, init_end, false);
    for (const auto &f : all) appended += VideoStore::m3u8Fragment(f, url);

    REQUIRE(appended == WholeManifest(all, url, init_end, false));
  }

  SECTION("a fragment's lines never depend on what came before it") {
    // The property that makes appending sound at all.
    const auto few = MakeFragments(3);
    const auto many = MakeFragments(500);
    REQUIRE(VideoStore::m3u8Fragment(few[2], url) == VideoStore::m3u8Fragment(many[2], url));
  }
}

TEST_CASE("m3u8 target duration") {
  const std::string url = "v.mp4";

  SECTION("is the longest fragment, rounded up") {
    std::vector<VideoStore::Fragment> frags = MakeFragments(3, 1.2);
    frags[1].duration = 7.3;
    REQUIRE(VideoStore::m3u8TargetDuration(frags) == 8);
  }

  SECTION("is never below 1, which the spec requires") {
    // Sub-second fragments would otherwise round down to 0 and make the
    // manifest invalid.
    REQUIRE(VideoStore::m3u8TargetDuration(MakeFragments(3, 0.4)) == 1);
    REQUIRE(VideoStore::m3u8TargetDuration({}) == 1);
  }

  SECTION("a longer fragment changes it, which is what forces a rewrite") {
    // writeM3U8 may only append while the header it already wrote still
    // stands. The header carries the target duration, so this is the case it
    // has to notice.
    auto frags = MakeFragments(10, 1.2);
    const int before = VideoStore::m3u8TargetDuration(frags);
    frags.push_back({999999, 40000, 9.1});
    REQUIRE(VideoStore::m3u8TargetDuration(frags) != before);
    REQUIRE(VideoStore::m3u8Header(before, url, 1024, false)
            != VideoStore::m3u8Header(VideoStore::m3u8TargetDuration(frags), url, 1024, false));
  }
}

TEST_CASE("m3u8 header") {
  const auto frags = MakeFragments(2);
  const std::string url = "index.php?view=view_video&eid=1&file=incomplete.h264.mp4";

  SECTION("an in-progress event is EVENT, a finished one is VOD") {
    REQUIRE(VideoStore::m3u8Header(8, url, 1024, false).find("#EXT-X-PLAYLIST-TYPE:EVENT")
            != std::string::npos);
    REQUIRE(VideoStore::m3u8Header(8, url, 1024, true).find("#EXT-X-PLAYLIST-TYPE:VOD")
            != std::string::npos);
  }

  SECTION("the init segment byte range comes from the init segment end") {
    REQUIRE(VideoStore::m3u8Header(8, url, 4242, false).find("BYTERANGE=\"4242@0\"")
            != std::string::npos);
  }

  SECTION("the url is repeated on every fragment, so changing it needs a rewrite") {
    // Which is why writeM3U8 remembers the url it wrote with: the close path
    // passes a different one, pointing at the final file rather than
    // incomplete.h264.mp4.
    REQUIRE(VideoStore::m3u8Fragment(frags[0], "a.mp4")
            != VideoStore::m3u8Fragment(frags[0], "b.mp4"));
  }
}

TEST_CASE("m3u8 fragment lines") {
  VideoStore::Fragment f;
  f.offset = 1024;
  f.size = 40960;
  f.duration = 1.234;

  SECTION("carry the duration, byte range and url a player needs") {
    const std::string line = VideoStore::m3u8Fragment(f, "v.mp4");
    REQUIRE(line == "#EXTINF:1.234,\n#EXT-X-BYTERANGE:40960@1024\nv.mp4\n");
  }

  SECTION("are exactly three lines, so the append offset is predictable") {
    const std::string line = VideoStore::m3u8Fragment(f, "v.mp4");
    REQUIRE(std::count(line.begin(), line.end(), '\n') == 3);
  }
}
