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

#include <cstring>

namespace {

struct Fmt {
  const char *name;
  unsigned int colours;
  unsigned int subpix;
  unsigned int bpp;
};

const Fmt kRgb32[] = {
  {"RGBA", ZM_COLOUR_RGB32, ZM_SUBPIX_ORDER_RGBA, 4},
  {"BGRA", ZM_COLOUR_RGB32, ZM_SUBPIX_ORDER_BGRA, 4},
  {"ARGB", ZM_COLOUR_RGB32, ZM_SUBPIX_ORDER_ARGB, 4},
  {"ABGR", ZM_COLOUR_RGB32, ZM_SUBPIX_ORDER_ABGR, 4},
};

// Offsets of the colour bytes inside one pixel of the format.
void colour_offsets(const Fmt &f, unsigned int &a, unsigned int &b, unsigned int &c) {
  switch (f.subpix) {
    case ZM_SUBPIX_ORDER_ARGB:
    case ZM_SUBPIX_ORDER_ABGR: a = 1; b = 2; c = 3; break;
    default: a = 0; b = 1; c = 2; break;
  }
}

}  // namespace

TEST_CASE("Image::Overlay of a grayscale image onto RGB32 paints a gray pixel", "[Image][Overlay]") {
  for (const Fmt &f : kRgb32) {
    DYNAMIC_SECTION(f.name) {
      Image dest(4, 4, f.colours, f.subpix);
      dest.Clear();
      Image src(4, 4, ZM_COLOUR_GRAY8, ZM_SUBPIX_ORDER_NONE);
      src.Clear();
      src.Buffer()[1 * src.LineSize() + 2] = 200;

      dest.Overlay(src);

      const uint8_t *px = dest.Buffer() + 1 * dest.LineSize() + 2 * 4;
      unsigned int a, b, c;
      colour_offsets(f, a, b, c);
      CHECK(px[a] == 200);
      CHECK(px[b] == 200);
      CHECK(px[c] == 200);
    }
  }
}

TEST_CASE("Image::Rotate with a negative angle rotates the other way", "[Image][Rotate]") {
  for (int angle : {-90, -180, -270}) {
    DYNAMIC_SECTION("angle " << angle) {
      Image a(5, 3, ZM_COLOUR_GRAY8, ZM_SUBPIX_ORDER_NONE);
      Image b(5, 3, ZM_COLOUR_GRAY8, ZM_SUBPIX_ORDER_NONE);
      for (unsigned int y = 0; y < 3; y++)
        for (unsigned int x = 0; x < 5; x++)
          a.Buffer()[y * a.LineSize() + x] = b.Buffer()[y * b.LineSize() + x] = 1 + x + 10 * y;

      a.Rotate(angle);
      b.Rotate(360 + angle);

      REQUIRE(a.Width() == b.Width());
      REQUIRE(a.Height() == b.Height());
      for (unsigned int y = 0; y < a.Height(); y++)
        for (unsigned int x = 0; x < a.Width(); x++)
          CHECK(a.Buffer()[y * a.LineSize() + x] == b.Buffer()[y * b.LineSize() + x]);
    }
  }
}
