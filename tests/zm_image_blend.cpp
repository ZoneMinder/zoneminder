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

// Image::Blend hands the image's whole buffer to the fast blend helper, which
// consumes 16 bytes per iteration (unrolled C, SSE2) or 64 (NEON). The SIMD
// variants wrap their loop counter past zero on a count that is not a multiple
// of the block and run off the end of memory. These cases pick image sizes
// that are not, with pixels % 4 == 0 so the fast helper is selected.

#include "zm_catch2.h"

#include "zm_config.h"
#include "zm_image.h"
#include "zm_rgb.h"
#include "zm_utils.h"

#include <cstdint>
#include <cstdlib>
#include <vector>

namespace {
// Select the fast (SIMD where the CPU has it) blend helper for the images a
// test constructs, whatever an earlier test latched in Image::Initialise(),
// and put the previous choice back afterwards.
class FastBlends {
 public:
  FastBlends() : fast_image_blends_(config.fast_image_blends), cpu_extensions_(config.cpu_extensions) {
    if (!config.font_file_location) config.font_file_location = "";
    config.fast_image_blends = true;
    config.cpu_extensions = true;
    HwCapsDetect();
    Image::Initialise();
  }
  ~FastBlends() {
    config.fast_image_blends = fast_image_blends_;
    config.cpu_extensions = cpu_extensions_;
    Image::Initialise();
  }

 private:
  bool fast_image_blends_;
  bool cpu_extensions_;
};

uint8_t Pattern(unsigned int i, int seed) {
  return static_cast<uint8_t>((i * 37 + seed * 11 + (i >> 3)) & 0xff);
}

// The fast helpers move col1 towards col2 by (col2 - col1) >> divider, divider 3
// for 12%; the self-test in Image::Initialise() allows them 3 either way.
void RequireBlended(const std::vector<uint8_t> &col1, const std::vector<uint8_t> &col2, const uint8_t *result) {
  for (size_t i = 0; i < col1.size(); i++) {
    const int expected = ((col2[i] - col1[i]) >> 3) + col1[i];
    INFO("byte " << i);
    REQUIRE(std::abs(static_cast<int>(result[i]) - expected) <= 3);
  }
}
}  // namespace

TEST_CASE("Image::Blend of a caller's buffer whose size is not a multiple of 16", "[Image][Blend]") {
  FastBlends fast;

  // 12x3 RGB24 over a caller's buffer: linesize 36, size 108 (108 % 16 == 12),
  // pixels 36 % 4 == 0.
  constexpr unsigned int kW = 12;
  constexpr unsigned int kH = 3;
  constexpr unsigned int kSize = kW * kH * 3;
  std::vector<uint8_t> col1(kSize), col2(kSize);
  for (unsigned int i = 0; i < kSize; i++) {
    col1[i] = Pattern(i, 1);
    col2[i] = Pattern(i, 7);
  }
  std::vector<uint8_t> buf1(col1), buf2(col2);

  Image a(kW, kH, ZM_COLOUR_RGB24, ZM_SUBPIX_ORDER_RGB, buf1.data());
  Image b(kW, kH, ZM_COLOUR_RGB24, ZM_SUBPIX_ORDER_RGB, buf2.data());
  REQUIRE(a.Size() == kSize);
  a.Blend(b, 12);  // unfixed code runs the SSE2/NEON loop off the end here

  RequireBlended(col1, col2, a.Buffer());
  // The result is written into the caller's buffer, not swapped for one the
  // image would later free with zm_freealigned().
  REQUIRE(a.Buffer() == buf1.data());
}

TEST_CASE("Image::Blend of an aligned image whose size is not a multiple of 64", "[Image][Blend]") {
  FastBlends fast;

  // 20x5 gray8: linesize aligned to 32, size 160 (160 % 64 == 32), pixels 100
  // % 4 == 0. A multiple of the 16-byte SSE2 block but not of the 64-byte NEON one.
  constexpr unsigned int kW = 20;
  constexpr unsigned int kH = 5;

  Image a(kW, kH, ZM_COLOUR_GRAY8, ZM_SUBPIX_ORDER_NONE);
  Image b(kW, kH, ZM_COLOUR_GRAY8, ZM_SUBPIX_ORDER_NONE);
  REQUIRE(a.Size() % 64 != 0);
  std::vector<uint8_t> col1(a.Size()), col2(a.Size());
  for (unsigned int i = 0; i < a.Size(); i++) {
    col1[i] = a.Buffer()[i] = Pattern(i, 3);
    col2[i] = b.Buffer()[i] = Pattern(i, 9);
  }
  a.Blend(b, 12);

  RequireBlended(col1, col2, a.Buffer());
}
