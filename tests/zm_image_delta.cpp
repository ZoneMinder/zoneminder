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

// Image::Delta drives the delta8_* helpers row by row at the image's own
// linesize (so per-row padding is not read as image data). Those helpers
// consume whole blocks per iteration (16 gray8 pixels and 4 rgb32 pixels for
// SSE2, 64 and 16 for NEON); the SSE2/NEON variants require the pixel count to be an exact multiple of the
// block or they wrap the loop counter past zero and run off the end of memory
// (SIGSEGV), and the unrolled C variants overrun by up to a block. A per-row
// width that is not block-aligned therefore crashes/overruns even though
// update_function_pointers() only guards on total pixels % 4. These cases pick
// widths that are NOT block-aligned while keeping pixels % 4 == 0 (so the fast
// path is selected) and assert Delta produces the correct per-pixel result.

#include "zm_catch2.h"

#include "zm_config.h"
#include "zm_image.h"
#include "zm_rgb.h"
#include "zm_utils.h"

#include <cstdint>
#include <cstdlib>
#include <vector>

namespace {
// Image::Initialise() dereferences config.font_file_location; also select the
// SIMD delta helpers so these cases exercise the block-aligned fast path (and
// reproduce the crash on the unfixed code) rather than the always-safe scalar
// fallback. Initialise() runs again so that holds whichever test constructed
// the first Image.
void EnsureImageInit() {
  if (!config.font_file_location) config.font_file_location = "";
  config.cpu_extensions = 1;
  HwCapsDetect();  // sets the global sse_version / neonversion
  Image::Initialise();
}

// Fill a freshly allocated (self-owned, aligned buffer + aligned linesize)
// image with a reproducible per-pixel pattern across `channels` bytes/pixel.
void FillPattern(Image &img, unsigned int channels, int seed) {
  for (unsigned int y = 0; y < img.Height(); y++) {
    for (unsigned int x = 0; x < img.Width(); x++) {
      uint8_t *p = img.Buffer(x, y);
      for (unsigned int c = 0; c < channels; c++)
        p[c] = static_cast<uint8_t>((x * 7 + y * 13 + c * 29 + seed) & 0xff);
    }
  }
}
}  // namespace

TEST_CASE("Image::Delta gray8 with a non-16-aligned width", "[Image][Delta]") {
  EnsureImageInit();

  // 17x12: width % 16 == 1 (not block-aligned) but pixels == 204, % 4 == 0, so
  // update_function_pointers() selects the 16-pixel SIMD gray8 helper.
  constexpr unsigned int kW = 17;
  constexpr unsigned int kH = 12;

  Image a(kW, kH, ZM_COLOUR_GRAY8, ZM_SUBPIX_ORDER_NONE);
  Image b(kW, kH, ZM_COLOUR_GRAY8, ZM_SUBPIX_ORDER_NONE);
  FillPattern(a, 1, 0);
  FillPattern(b, 1, 97);

  Image delta;
  REQUIRE(a.Delta(b, &delta));  // unfixed code SIGSEGVs here

  REQUIRE(delta.Width() == kW);
  REQUIRE(delta.Height() == kH);
  // gray8 delta is an exact abs difference (pmaxub/pminub/psubb == |a-b|).
  for (unsigned int y = 0; y < kH; y++) {
    for (unsigned int x = 0; x < kW; x++) {
      const int expected = std::abs(static_cast<int>(*a.Buffer(x, y)) -
                                    static_cast<int>(*b.Buffer(x, y)));
      REQUIRE(static_cast<int>(*delta.Buffer(x, y)) == expected);
    }
  }
}

TEST_CASE("Image::Delta gray8 with a realistic non-16-aligned width", "[Image][Delta]") {
  EnsureImageInit();

  // 1366x4 is a real-world odd width: 1366 % 16 == 6, pixels == 5464, % 4 == 0.
  constexpr unsigned int kW = 1366;
  constexpr unsigned int kH = 4;

  Image a(kW, kH, ZM_COLOUR_GRAY8, ZM_SUBPIX_ORDER_NONE);
  Image b(kW, kH, ZM_COLOUR_GRAY8, ZM_SUBPIX_ORDER_NONE);
  FillPattern(a, 1, 5);
  FillPattern(b, 1, 211);

  Image delta;
  REQUIRE(a.Delta(b, &delta));

  for (unsigned int y = 0; y < kH; y++) {
    for (unsigned int x = 0; x < kW; x++) {
      const int expected = std::abs(static_cast<int>(*a.Buffer(x, y)) -
                                    static_cast<int>(*b.Buffer(x, y)));
      REQUIRE(static_cast<int>(*delta.Buffer(x, y)) == expected);
    }
  }
}

TEST_CASE("Image::Delta rgb32 with a non-4-aligned width", "[Image][Delta]") {
  EnsureImageInit();

  // 6x4: width % 4 == 2 (not block-aligned), pixels == 24, % 4 == 0, so the
  // 4-pixel SIMD rgba helper is selected.
  constexpr unsigned int kW = 6;
  constexpr unsigned int kH = 4;

  Image a(kW, kH, ZM_COLOUR_RGB32, ZM_SUBPIX_ORDER_RGBA);
  Image b(kW, kH, ZM_COLOUR_RGB32, ZM_SUBPIX_ORDER_RGBA);
  FillPattern(a, 4, 0);
  FillPattern(b, 4, 131);

  Image delta;
  REQUIRE(a.Delta(b, &delta));  // unfixed code SIGSEGVs / overruns here

  REQUIRE(delta.Width() == kW);
  REQUIRE(delta.Height() == kH);
  // Reference: the format's own scalar helper on tightly packed rows. The SIMD
  // bulk may round slightly differently from the scalar tail, so allow the same
  // tolerance the delta self-test uses (see Image::Initialise).
  for (unsigned int y = 0; y < kH; y++) {
    std::vector<uint8_t> ra(kW * 4), rb(kW * 4), rref(kW);
    for (unsigned int x = 0; x < kW; x++)
      for (unsigned int c = 0; c < 4; c++) {
        ra[x * 4 + c] = a.Buffer(x, y)[c];
        rb[x * 4 + c] = b.Buffer(x, y)[c];
      }
    std_delta8_rgba(ra.data(), rb.data(), rref.data(), kW);
    for (unsigned int x = 0; x < kW; x++)
      REQUIRE(std::abs(static_cast<int>(*delta.Buffer(x, y)) -
                       static_cast<int>(rref[x])) <= 8);
  }
}

TEST_CASE("Image::Delta rgb32 with a width past one NEON block", "[Image][Delta]") {
  EnsureImageInit();

  // 22x4: width % 16 == 6, so on NEON (16 rgb32 pixels per iteration) the row
  // runs one block through the vector helper and six through the scalar tail;
  // a 4-pixel block would have handed NEON 20 pixels and sent its loop off the
  // end of memory. pixels == 88, % 4 == 0, so the SIMD helper is selected.
  constexpr unsigned int kW = 22;
  constexpr unsigned int kH = 4;

  Image a(kW, kH, ZM_COLOUR_RGB32, ZM_SUBPIX_ORDER_RGBA);
  Image b(kW, kH, ZM_COLOUR_RGB32, ZM_SUBPIX_ORDER_RGBA);
  FillPattern(a, 4, 3);
  FillPattern(b, 4, 77);

  Image delta;
  REQUIRE(a.Delta(b, &delta));

  for (unsigned int y = 0; y < kH; y++) {
    std::vector<uint8_t> ra(kW * 4), rb(kW * 4), rref(kW);
    for (unsigned int x = 0; x < kW; x++)
      for (unsigned int c = 0; c < 4; c++) {
        ra[x * 4 + c] = a.Buffer(x, y)[c];
        rb[x * 4 + c] = b.Buffer(x, y)[c];
      }
    std_delta8_rgba(ra.data(), rb.data(), rref.data(), kW);
    for (unsigned int x = 0; x < kW; x++)
      REQUIRE(std::abs(static_cast<int>(*delta.Buffer(x, y)) -
                       static_cast<int>(rref[x])) <= 8);
  }
}
