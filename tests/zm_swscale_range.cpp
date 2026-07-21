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
#include "zm_swscale.h"

#include <cstdlib>
#include <vector>

TEST_CASE("pix_fmt_is_jpeg_range identifies full-range YUVJ formats", "[swscale]") {
  REQUIRE(pix_fmt_is_jpeg_range(AV_PIX_FMT_YUVJ420P));
  REQUIRE(pix_fmt_is_jpeg_range(AV_PIX_FMT_YUVJ422P));
  REQUIRE(pix_fmt_is_jpeg_range(AV_PIX_FMT_YUVJ444P));
  REQUIRE(pix_fmt_is_jpeg_range(AV_PIX_FMT_YUVJ440P));

  REQUIRE_FALSE(pix_fmt_is_jpeg_range(AV_PIX_FMT_YUV420P));
  REQUIRE_FALSE(pix_fmt_is_jpeg_range(AV_PIX_FMT_YUV422P));
  REQUIRE_FALSE(pix_fmt_is_jpeg_range(AV_PIX_FMT_RGB24));
  REQUIRE_FALSE(pix_fmt_is_jpeg_range(AV_PIX_FMT_GRAY8));
}

// Colorimetric regression: a full-range JPEG (YUVJ420P) frame carries luma in
// 0-255. swscale defaults to limited (16-235) input range, which would crush a
// dark grey Y=16 down to ~0 (black). zm_sws_set_input_range() must mark the
// input full range so Y=16 survives as ~16.
TEST_CASE("SWScale treats YUVJ420P input as full range when converting to RGB", "[swscale]") {
  const int w = 16, h = 16;
  const uint8_t Y = 16;  // full range -> ~16; limited range -> ~0

  // YUVJ420P planar: Y plane (w*h), then U and V (w/2*h/2). Neutral chroma=128.
  std::vector<uint8_t> in(SWScale::GetBufferSize(AV_PIX_FMT_YUVJ420P, w, h, 1), 128);
  std::fill(in.begin(), in.begin() + w * h, Y);

  std::vector<uint8_t> out(SWScale::GetBufferSize(AV_PIX_FMT_RGB24, w, h, 1), 0);

  SWScale scaler;
  REQUIRE(scaler.init());
  int r = scaler.Convert(in.data(), in.size(), out.data(), out.size(),
                         AV_PIX_FMT_YUVJ420P, AV_PIX_FMT_RGB24, w, h, 1, 1);
  REQUIRE(r == 0);

  // Full-range interpretation keeps luma ~16; limited-range would crush to ~0.
  REQUIRE(out[0] >= 12);
  REQUIRE(out[0] <= 20);
  // Neutral chroma -> grey, so the three channels stay equal.
  REQUIRE(std::abs(static_cast<int>(out[0]) - static_cast<int>(out[1])) <= 2);
  REQUIRE(std::abs(static_cast<int>(out[1]) - static_cast<int>(out[2])) <= 2);
}
