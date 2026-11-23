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
#include "zm_ffmpeg_input.h"

#include <filesystem>
#include <string>

namespace {

std::filesystem::path fixture_path(const std::string &name) {
  return std::filesystem::path(ZM_SOURCE_DIR) / "tests" / "data" / "mp4" / name;
}

}  // namespace

TEST_CASE("FFmpeg_Input: frame lookup selects correct frame across boundary", "[ffmpeg]") {
  // video-only.mp4 has 5 frames at 5 fps (duration = 0.2s = 2048 pts units each, time_base = 1/10240):
  // Frame 0: pts=0 (0.0s), duration=2048, interval [0.0s, 0.2s)
  // Frame 1: pts=2048 (0.2s), duration=2048, interval [0.2s, 0.4s)
  // Frame 2: pts=4096 (0.4s), duration=2048, interval [0.4s, 0.6s)
  // Frame 3: pts=6144 (0.6s), duration=2048, interval [0.6s, 0.8s)
  // Frame 4: pts=8192 (0.8s), duration=2048, interval [0.8s, 1.0s)
  std::filesystem::path mp4 = fixture_path("video-only.mp4");
  if (!std::filesystem::exists(mp4)) {
    WARN("video-only.mp4 fixture missing, skipping");
    return;
  }

  FFmpeg_Input input;
  int ret = input.Open(mp4.c_str());
  REQUIRE(ret > 0);

  int video_stream_id = input.get_video_stream_id();
  REQUIRE(video_stream_id >= 0);

  // At 0.0s, Frame 0 is active
  AVFrame *f0 = input.get_frame(video_stream_id, 0.0);
  REQUIRE(f0 != nullptr);
  REQUIRE(f0->pts == 0);

  // At 0.2s, Frame 0 has ended; Frame 1 starts.
  // The off-by-one bug (< instead of <=) returned Frame 0 (pts 0).
  AVFrame *f1 = input.get_frame(video_stream_id, 0.2);
  REQUIRE(f1 != nullptr);
  REQUIRE(f1->pts == 2048);

  // At 0.4s, Frame 2 starts (pts 4096)
  AVFrame *f2 = input.get_frame(video_stream_id, 0.4);
  REQUIRE(f2 != nullptr);
  REQUIRE(f2->pts == 4096);

  input.Close();
}

// sidx-abs.mp4: 30 frames, no B-frames, keyframes at pts 0, 10478 and 20718,
// then one frame every 1024 (time base 1/10240, so about 0.1s apart).
TEST_CASE("FFmpeg_Input: the first request can start part-way through", "[ffmpeg]") {
  std::filesystem::path mp4 = fixture_path("sidx-abs.mp4");
  if (!std::filesystem::exists(mp4)) {
    WARN("sidx-abs.mp4 fixture missing, skipping");
    return;
  }

  FFmpeg_Input input;
  REQUIRE(input.Open(mp4.c_str()) > 0);
  int video_stream_id = input.get_video_stream_id();
  REQUIRE(video_stream_id >= 0);

  // The first seek is by time, not frame number: 1.5s (pts 15360) is shown by pts 14574.
  AVFrame *f = input.get_frame(video_stream_id, 1.5);
  REQUIRE(f != nullptr);
  REQUIRE(f->pts == 14574);
  input.Close();
}

TEST_CASE("FFmpeg_Input: seeking back returns the frame at the new time", "[ffmpeg]") {
  std::filesystem::path mp4 = fixture_path("sidx-abs.mp4");
  if (!std::filesystem::exists(mp4)) {
    WARN("sidx-abs.mp4 fixture missing, skipping");
    return;
  }

  FFmpeg_Input input;
  REQUIRE(input.Open(mp4.c_str()) > 0);
  int video_stream_id = input.get_video_stream_id();
  REQUIRE(video_stream_id >= 0);

  AVFrame *f = input.get_frame(video_stream_id, 0.5);
  REQUIRE(f != nullptr);
  REQUIRE(f->pts == 4334);
  f = input.get_frame(video_stream_id, 2.5);
  REQUIRE(f != nullptr);
  REQUIRE(f->pts == 24814);
  // Back across two keyframes; frames the decoder still held from 2.5s must not come back.
  f = input.get_frame(video_stream_id, 0.5);
  REQUIRE(f != nullptr);
  REQUIRE(f->pts == 4334);
  input.Close();
}

// bframes.mp4: 30 frames, H.264 with B-frames, keyframes every 10 frames,
// time base 1/10240, pts 0, 1024, ... 29696.
TEST_CASE("FFmpeg_Input: seeking to the last frame of a B-frame file returns it", "[ffmpeg]") {
  std::filesystem::path mp4 = fixture_path("bframes.mp4");
  if (!std::filesystem::exists(mp4)) {
    WARN("bframes.mp4 fixture missing, skipping");
    return;
  }

  FFmpeg_Input input;
  REQUIRE(input.Open(mp4.c_str()) > 0);
  int video_stream_id = input.get_video_stream_id();
  REQUIRE(video_stream_id >= 0);

  AVFrame *f = input.get_frame(video_stream_id, 2.9);
  REQUIRE(f != nullptr);
  REQUIRE(f->pts == 29696);
  input.Close();
}

TEST_CASE("FFmpeg_Input: sequential decode returns every frame before EOF", "[ffmpeg]") {
  std::filesystem::path mp4 = fixture_path("bframes.mp4");
  if (!std::filesystem::exists(mp4)) {
    WARN("bframes.mp4 fixture missing, skipping");
    return;
  }

  FFmpeg_Input input;
  REQUIRE(input.Open(mp4.c_str()) > 0);
  int video_stream_id = input.get_video_stream_id();
  REQUIRE(video_stream_id >= 0);

  int count = 0;
  int64_t max_pts = -1;
  for (int i = 0; i < 100; i++) {
    AVFrame *f = input.get_frame(video_stream_id);
    if (!f) break;
    count++;
    if (f->pts > max_pts) max_pts = f->pts;
  }
  REQUIRE(count == 30);
  REQUIRE(max_pts == 29696);
  REQUIRE(input.get_frame(video_stream_id) == nullptr);
  input.Close();
}
