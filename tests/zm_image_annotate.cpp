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

#include <algorithm>
#include <string>
#include <vector>

// Image::Annotate() draws the monitor's timestamp label onto every captured frame. The label
// text, position and size come from monitor fields any monitor editor can set, so no
// combination of them may write outside the frame. refs GHSA-j5gf-rfm2-vhw9
//
// The fixture font's glyphs are 10x10 at size 1 and 13x13 at size 4.

namespace {

constexpr uint8_t kFrameFill = 0x55;
constexpr uint8_t kGuardFill = 0xAB;
constexpr unsigned int kGuardBytes = 4096;

void LoadFixtureFont() {
  // Another test may have initialised Image without a usable font; reload with the fixture.
  Image::Deinitialise();
  config.font_file_location = "data/fonts/04_valid.zmfnt";
  Image::Initialise();
}

// A frame drawn in place in a caller-owned buffer, followed by a guard region that must
// never be written.
struct GuardedFrame {
  unsigned int width;
  unsigned int height;
  unsigned int colours;
  std::vector<uint8_t> backing;

  GuardedFrame(unsigned int w, unsigned int h, unsigned int c)
    : width(w), height(h), colours(c), backing(w * h * c + kGuardBytes, kGuardFill) {
    std::fill(backing.begin(), backing.begin() + FrameBytes(), kFrameFill);
  }

  unsigned int FrameBytes() const { return width * height * colours; }

  Image MakeImage() {
    int order = ZM_SUBPIX_ORDER_NONE;
    if (colours == ZM_COLOUR_RGB24) order = ZM_SUBPIX_ORDER_RGB;
    if (colours == ZM_COLOUR_RGB32) order = ZM_SUBPIX_ORDER_RGBA;
    return Image(static_cast<int>(width), static_cast<int>(height), static_cast<int>(colours), order,
                 backing.data(), 0u);
  }

  bool GuardIntact() const {
    return std::all_of(backing.begin() + FrameBytes(), backing.end(),
                       [](uint8_t b) { return b == kGuardFill; });
  }

  bool PixelUntouched(unsigned int x, unsigned int y) const {
    const uint8_t *p = &backing[(y * width + x) * colours];
    return std::all_of(p, p + colours, [](uint8_t b) { return b == kFrameFill; });
  }

  bool AnythingDrawn() const {
    return std::any_of(backing.begin(), backing.begin() + FrameBytes(),
                       [](uint8_t b) { return b != kFrameFill; });
  }
};

}  // namespace

TEST_CASE("Image::Annotate keeps a label wider than the frame inside the buffer", "[Image][Annotate]") {
  LoadFixtureFont();
  const unsigned int colours = GENERATE(ZM_COLOUR_GRAY8, ZM_COLOUR_RGB24, ZM_COLOUR_RGB32);

  // 29 characters at 10 px is 290 px, far wider than the 64 px frame. Before the fix the
  // unsigned clamp bound wrapped and the requested X position was used unchanged.
  GuardedFrame frame(64, 24, colours);
  Image image = frame.MakeImage();
  image.Annotate("Front Door - 26/10/08 12:00:00", Vector2(10000, 10000), 1);

  REQUIRE(frame.GuardIntact());
  REQUIRE(frame.AnythingDrawn());
}

TEST_CASE("Image::Annotate does not draw a glyph that would cross the right edge", "[Image][Annotate]") {
  LoadFixtureFont();
  const unsigned int colours = GENERATE(ZM_COLOUR_GRAY8, ZM_COLOUR_RGB24, ZM_COLOUR_RGB32);

  // 35 px wide: glyphs fit at x = 0, 10 and 20; a fourth at x = 30 would spill into the next
  // row and, on the bottom row, past the end of the frame.
  GuardedFrame frame(35, 12, colours);
  Image image = frame.MakeImage();
  image.Annotate("ABCDEFGH", Vector2(0, 0), 1);

  REQUIRE(frame.GuardIntact());
  for (unsigned int y = 0; y < frame.height; y++) {
    for (unsigned int x = 30; x < frame.width; x++) {
      INFO("x=" << x << " y=" << y);
      REQUIRE(frame.PixelUntouched(x, y));
    }
  }
}

TEST_CASE("Image::Annotate keeps a label taller than the frame inside the buffer", "[Image][Annotate]") {
  LoadFixtureFont();
  const unsigned int colours = GENERATE(ZM_COLOUR_GRAY8, ZM_COLOUR_RGB24, ZM_COLOUR_RGB32);

  // Three 13 px lines in a 16 px frame: y's clamp bound wrapped the same way as x's.
  GuardedFrame frame(64, 16, colours);
  Image image = frame.MakeImage();
  image.Annotate("AB\nCD\nEF", Vector2(10000, 10000), 4);

  REQUIRE(frame.GuardIntact());
  REQUIRE(frame.AnythingDrawn());
}

TEST_CASE("Image::Annotate places a fitting label at the far corner when asked past it", "[Image][Annotate]") {
  LoadFixtureFont();

  GuardedFrame frame(64, 24, ZM_COLOUR_RGB32);
  Image image = frame.MakeImage();
  image.Annotate("AB", Vector2(10000, 10000), 1);

  REQUIRE(frame.GuardIntact());
  // Two 10x10 glyphs pushed into the bottom-right corner cover x 44..63, y 14..23.
  REQUIRE_FALSE(frame.PixelUntouched(44, 14));
  REQUIRE_FALSE(frame.PixelUntouched(63, 23));
  REQUIRE(frame.PixelUntouched(43, 23));
  REQUIRE(frame.PixelUntouched(63, 13));
}

TEST_CASE("Image::Annotate accepts an out-of-range label size", "[Image][Annotate]") {
  LoadFixtureFont();
  const unsigned int size = GENERATE(0u, 5u, 255u);

  GuardedFrame frame(64, 24, ZM_COLOUR_RGB32);
  Image image = frame.MakeImage();
  REQUIRE_NOTHROW(image.Annotate("AB", Vector2(0, 0), static_cast<uint8>(size)));
  REQUIRE_NOTHROW(image.centreCoord("AB", static_cast<int>(size)));
  REQUIRE(frame.GuardIntact());
}
