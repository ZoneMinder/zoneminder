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

#include "zm_config.h"
#include "zm_image.h"

#include <cstdio>
#include <cstdlib>
#include <vector>

#include <unistd.h>

// JPEG YCbCr is full range. Limited range planar YUV (YUV420P/YUV422P) must be
// expanded when encoded so black decodes to ~0 and white to ~255; the full
// range YUVJ* formats must pass through unchanged.
namespace {
void EnsureImageInit() {
  if (!config.font_file_location) config.font_file_location = "";
}

// Left half luma y_left, right half y_right, neutral chroma.
std::vector<uint8_t> MakeYuv(AVPixelFormat fmt, int w, int h, uint8_t y_left, uint8_t y_right) {
  std::vector<uint8_t> buf(av_image_get_buffer_size(fmt, w, h, 32), 128);
  uint8_t *plane[4] = {};
  int ls[4] = {};
  av_image_fill_arrays(plane, ls, buf.data(), fmt, w, h, 32);
  for (int y = 0; y < h; y++)
    for (int x = 0; x < w; x++)
      plane[0][y * ls[0] + x] = x < w / 2 ? y_left : y_right;
  return buf;
}

// Returns luma-ish (green channel) at the given sample of the decoded JPEG.
struct Decoded {
  int left = -1;
  int right = -1;
};

Decoded EncodeAndDecode(AVPixelFormat fmt, int w, int h, uint8_t y_left, uint8_t y_right) {
  std::vector<uint8_t> buf = MakeYuv(fmt, w, h, y_left, y_right);
  AVFrame *frame = av_frame_alloc();
  frame->format = fmt;
  frame->width = w;
  frame->height = h;
  av_image_fill_arrays(frame->data, frame->linesize, buf.data(), fmt, w, h, 32);
  Image image(frame);
  REQUIRE(image.Width() == static_cast<unsigned int>(w));

  std::vector<JOCTET> out(1 << 20);
  size_t out_size = out.size();
  REQUIRE(image.EncodeJpeg(out.data(), &out_size, 95));

  Image decoded;
  REQUIRE(decoded.DecodeJpeg(out.data(), static_cast<int>(out_size), ZM_COLOUR_GRAY8, ZM_SUBPIX_ORDER_NONE));
  Decoded d;
  d.left = decoded.Buffer()[(h / 2) * decoded.LineSize() + w / 4];
  d.right = decoded.Buffer()[(h / 2) * decoded.LineSize() + (3 * w) / 4];

  // WriteJpeg goes through its own copy of the feed code.
  char path[] = "/tmp/zm_jpeg_range_XXXXXX";
  int fd = mkstemp(path);
  REQUIRE(fd >= 0);
  close(fd);
  REQUIRE(image.WriteJpeg(path, 95));
  Image from_file;
  REQUIRE(from_file.ReadJpeg(path, ZM_COLOUR_GRAY8, ZM_SUBPIX_ORDER_NONE));
  unlink(path);
  CHECK(std::abs(from_file.Buffer()[(h / 2) * from_file.LineSize() + w / 4] - d.left) <= 1);
  CHECK(std::abs(from_file.Buffer()[(h / 2) * from_file.LineSize() + (3 * w) / 4] - d.right) <= 1);
  av_frame_free(&frame);
  return d;
}
}  // namespace

TEST_CASE("EncodeJpeg expands limited range YUV420P", "[Image][jpeg]") {
  EnsureImageInit();
  for (int w : {64, 70}) {  // 70 is not a multiple of 16: padded band
    Decoded d = EncodeAndDecode(AV_PIX_FMT_YUV420P, w, 32, 16, 235);
    CHECK(d.left <= 4);
    CHECK(d.right >= 250);
  }
}

TEST_CASE("EncodeJpeg expands limited range YUV422P", "[Image][jpeg]") {
  EnsureImageInit();
  Decoded d = EncodeAndDecode(AV_PIX_FMT_YUV422P, 64, 32, 16, 235);
  CHECK(d.left <= 4);
  CHECK(d.right >= 250);
}

TEST_CASE("EncodeJpeg leaves full range YUVJ420P unchanged", "[Image][jpeg]") {
  EnsureImageInit();
  for (int w : {64, 70}) {
    // 0 and 255 survive expansion too, so check values it would move.
    Decoded d = EncodeAndDecode(AV_PIX_FMT_YUVJ420P, w, 32, 16, 235);
    CHECK(std::abs(d.left - 16) <= 2);
    CHECK(std::abs(d.right - 235) <= 2);
  }
}
