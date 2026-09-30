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
#include "zm_rgb.h"

#include <cstdint>
#include <vector>

// get_y_image() wraps a decoder Y plane whose real stride (linesize[0]) can be
// smaller than FFALIGN(width,32). Image::Rotate/Flip must use the source
// Image's own linesize, not a re-derived 32-aligned stride, or they read past
// the end of the borrowed plane (crash) and skew the output. Width 15 is
// deliberately not a multiple of 32 (FFALIGN(15,32)=32) and the source is
// packed tight (linesize == width), exactly like the decoder's Y plane.
// width*height=150 keeps every pixel value distinct in a byte so any stride
// drift is detectable.
namespace {
constexpr unsigned int kW = 15;
constexpr unsigned int kH = 10;

// Image::Initialise() dereferences config.font_file_location, which is null in
// the unit-test harness (no zm.conf loaded). Give it a non-null value so the
// first Image construction doesn't throw before exercising the rotate/flip code.
void EnsureImageInit() {
  if (!config.font_file_location) config.font_file_location = "";
}

// Backing allocation larger than the tight 15x10 plane so that a regression
// (an over-read using stride FFALIGN(15,32)=32) stays inside our allocation and
// corrupts the result rather than segfaulting the test runner.
std::vector<uint8_t> MakeTightGray8Plane() {
  std::vector<uint8_t> backing(32 * kH + 64, 0);
  for (unsigned int y = 0; y < kH; y++)
    for (unsigned int x = 0; x < kW; x++)
      backing[y * kW + x] = static_cast<uint8_t>(y * kW + x);
  return backing;
}
}  // namespace

TEST_CASE("Image::Rotate 180 honors a non-32-aligned source linesize", "[Image]") {
  EnsureImageInit();
  std::vector<uint8_t> backing = MakeTightGray8Plane();
  Image img(kW, /*linesize*/ kW, kH, /*colours*/ 1, ZM_SUBPIX_ORDER_NONE, backing.data(), /*padding*/ 0);

  img.Rotate(180);

  REQUIRE(img.Width() == kW);
  REQUIRE(img.Height() == kH);
  for (unsigned int y = 0; y < kH; y++) {
    for (unsigned int x = 0; x < kW; x++) {
      const uint8_t expected = static_cast<uint8_t>((kH - 1 - y) * kW + (kW - 1 - x));
      REQUIRE(*img.Buffer(x, y) == expected);
    }
  }
}

TEST_CASE("Image::Flip horizontal honors a non-32-aligned source linesize", "[Image]") {
  EnsureImageInit();
  std::vector<uint8_t> backing = MakeTightGray8Plane();
  Image img(kW, /*linesize*/ kW, kH, /*colours*/ 1, ZM_SUBPIX_ORDER_NONE, backing.data(), /*padding*/ 0);

  img.Flip(true);

  REQUIRE(img.Width() == kW);
  REQUIRE(img.Height() == kH);
  for (unsigned int y = 0; y < kH; y++) {
    for (unsigned int x = 0; x < kW; x++) {
      const uint8_t expected = static_cast<uint8_t>(y * kW + (kW - 1 - x));
      REQUIRE(*img.Buffer(x, y) == expected);
    }
  }
}

TEST_CASE("Image::Flip vertical honors a non-32-aligned source linesize", "[Image]") {
  EnsureImageInit();
  std::vector<uint8_t> backing = MakeTightGray8Plane();
  Image img(kW, /*linesize*/ kW, kH, /*colours*/ 1, ZM_SUBPIX_ORDER_NONE, backing.data(), /*padding*/ 0);

  img.Flip(false);

  REQUIRE(img.Width() == kW);
  REQUIRE(img.Height() == kH);
  for (unsigned int y = 0; y < kH; y++) {
    for (unsigned int x = 0; x < kW; x++) {
      const uint8_t expected = static_cast<uint8_t>((kH - 1 - y) * kW + x);
      REQUIRE(*img.Buffer(x, y) == expected);
    }
  }
}

// 90/270 also swap dimensions (dst is kH wide x kW tall), exercising both the
// source-stride fix and the destination dimension/stride handling.
TEST_CASE("Image::Rotate 90 honors a non-32-aligned source linesize", "[Image]") {
  EnsureImageInit();
  std::vector<uint8_t> backing = MakeTightGray8Plane();
  Image img(kW, /*linesize*/ kW, kH, /*colours*/ 1, ZM_SUBPIX_ORDER_NONE, backing.data(), /*padding*/ 0);

  img.Rotate(90);

  REQUIRE(img.Width() == kH);
  REQUIRE(img.Height() == kW);
  // 90deg: dst(X,Y) == src(x=Y, y=kH-1-X)
  for (unsigned int Y = 0; Y < kW; Y++) {
    for (unsigned int X = 0; X < kH; X++) {
      const uint8_t expected = static_cast<uint8_t>((kH - 1 - X) * kW + Y);
      REQUIRE(*img.Buffer(X, Y) == expected);
    }
  }
}

TEST_CASE("Image::Rotate 270 honors a non-32-aligned source linesize", "[Image]") {
  EnsureImageInit();
  std::vector<uint8_t> backing = MakeTightGray8Plane();
  Image img(kW, /*linesize*/ kW, kH, /*colours*/ 1, ZM_SUBPIX_ORDER_NONE, backing.data(), /*padding*/ 0);

  img.Rotate(270);

  REQUIRE(img.Width() == kH);
  REQUIRE(img.Height() == kW);
  // 270deg: dst(X,Y) == src(x=kW-1-Y, y=X)
  for (unsigned int Y = 0; Y < kW; Y++) {
    for (unsigned int X = 0; X < kH; X++) {
      const uint8_t expected = static_cast<uint8_t>(X * kW + (kW - 1 - Y));
      REQUIRE(*img.Buffer(X, Y) == expected);
    }
  }
}
