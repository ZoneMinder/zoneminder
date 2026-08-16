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

#include "zm_config.h"
#include "zm_image.h"

#include <cstdarg>
#include <cstdlib>
#include <string>
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

// Regression for the nph-zms apache error.log flood (issue #5037): handing a
// deprecated YUVJ* format to swscale makes libswscale log "deprecated pixel
// format used, make sure you did set range correctly" for every context it
// builds. The input side was already mapped; the OUTPUT side was not, so any
// monitor passing a full-range h264 stream through as YUVJ420P logged once per
// scaled frame (montage/console scale, watch view at 100% does not).
namespace {
std::string av_log_capture;

void capture_av_log(void *, int level, const char *fmt, va_list vl) {
  if (level > AV_LOG_WARNING) return;
  char buf[1024];
  vsnprintf(buf, sizeof(buf), fmt, vl);
  av_log_capture += buf;
}

// RAII so a REQUIRE failure mid-test still restores ffmpeg's logger.
struct AvLogCapture {
  AvLogCapture() {
    av_log_capture.clear();
    av_log_set_callback(capture_av_log);
  }
  ~AvLogCapture() { av_log_set_callback(av_log_default_callback); }
};

bool logged_deprecated() {
  return av_log_capture.find("deprecated pixel format") != std::string::npos;
}
}  // namespace

TEST_CASE("SWScale does not log deprecated pixel format for YUVJ output", "[swscale]") {
  const int w = 32, h = 32;
  std::vector<uint8_t> in(SWScale::GetBufferSize(AV_PIX_FMT_YUVJ420P, w, h, 1), 128);
  std::vector<uint8_t> out(SWScale::GetBufferSize(AV_PIX_FMT_YUVJ420P, w / 2, h / 2, 1), 0);

  SWScale scaler;
  REQUIRE(scaler.init());

  AvLogCapture capture;
  int r = scaler.Convert(in.data(), in.size(), out.data(), out.size(),
                         AV_PIX_FMT_YUVJ420P, AV_PIX_FMT_YUVJ420P,
                         w, h, w / 2, h / 2, 1, 1);
  REQUIRE(r == 0);
  REQUIRE_FALSE(logged_deprecated());
}

TEST_CASE("Image::Scale of a YUVJ420P image is silent and range preserving", "[swscale]") {
  const unsigned int w = 64, h = 64;
  const uint8_t Y = 235;  // full range keeps 235; limited-range dst would clip it down

  // Image::Initialise() dereferences config.font_file_location, which is null
  // in the test binary.
  if (!config.font_file_location) config.font_file_location = "";

  Image image(w, h, ZM_COLOUR_GRAY8, ZM_SUBPIX_ORDER_YUVJ420P);
  memset(image.Buffer(), 128, image.Size());
  for (unsigned int y = 0; y < h; y++) {
    memset(image.Buffer() + y * image.LineSize(), Y, w);
  }

  AvLogCapture capture;
  image.Scale(w / 2, h / 2);
  REQUIRE_FALSE(logged_deprecated());

  REQUIRE(image.Width() == w / 2);
  REQUIRE(image.Height() == h / 2);
  REQUIRE(image.PixFormat() == AV_PIX_FMT_YUVJ420P);
  // A full-range -> limited-range conversion would pull 235 down to ~219.
  REQUIRE(image.Buffer()[0] >= 230);
}
