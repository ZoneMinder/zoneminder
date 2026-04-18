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

#include "zm_pixformat.h"

TEST_CASE("zm_pixformat_from_colours: GRAY8 family", "[pixformat]") {
  REQUIRE(zm_pixformat_from_colours(ZM_COLOUR_GRAY8, ZM_SUBPIX_ORDER_NONE) == AV_PIX_FMT_GRAY8);
  REQUIRE(zm_pixformat_from_colours(ZM_COLOUR_GRAY8, ZM_SUBPIX_ORDER_YUV420P) == AV_PIX_FMT_YUV420P);
  REQUIRE(zm_pixformat_from_colours(ZM_COLOUR_GRAY8, ZM_SUBPIX_ORDER_YUVJ420P) == AV_PIX_FMT_YUVJ420P);
}

TEST_CASE("zm_pixformat_from_colours: RGB24 family", "[pixformat]") {
  REQUIRE(zm_pixformat_from_colours(ZM_COLOUR_RGB24, ZM_SUBPIX_ORDER_RGB) == AV_PIX_FMT_RGB24);
  REQUIRE(zm_pixformat_from_colours(ZM_COLOUR_RGB24, ZM_SUBPIX_ORDER_BGR) == AV_PIX_FMT_BGR24);
}

TEST_CASE("zm_pixformat_from_colours: RGB32 family", "[pixformat]") {
  REQUIRE(zm_pixformat_from_colours(ZM_COLOUR_RGB32, ZM_SUBPIX_ORDER_RGBA) == AV_PIX_FMT_RGBA);
  REQUIRE(zm_pixformat_from_colours(ZM_COLOUR_RGB32, ZM_SUBPIX_ORDER_BGRA) == AV_PIX_FMT_BGRA);
  REQUIRE(zm_pixformat_from_colours(ZM_COLOUR_RGB32, ZM_SUBPIX_ORDER_ARGB) == AV_PIX_FMT_ARGB);
  REQUIRE(zm_pixformat_from_colours(ZM_COLOUR_RGB32, ZM_SUBPIX_ORDER_ABGR) == AV_PIX_FMT_ABGR);
}

TEST_CASE("zm_pixformat_from_colours: unknown returns NONE", "[pixformat]") {
  REQUIRE(zm_pixformat_from_colours(0, 0) == AV_PIX_FMT_NONE);
  REQUIRE(zm_pixformat_from_colours(2, 0) == AV_PIX_FMT_NONE);
  REQUIRE(zm_pixformat_from_colours(5, 0) == AV_PIX_FMT_NONE);
}

TEST_CASE("zm_colours_from_pixformat: maps each supported format", "[pixformat]") {
  unsigned int c = 0, s = 0;

  REQUIRE(zm_colours_from_pixformat(AV_PIX_FMT_GRAY8, c, s));
  REQUIRE(c == ZM_COLOUR_GRAY8);
  REQUIRE(s == ZM_SUBPIX_ORDER_NONE);

  REQUIRE(zm_colours_from_pixformat(AV_PIX_FMT_YUV420P, c, s));
  REQUIRE(c == ZM_COLOUR_GRAY8);  // collision: same value as ZM_COLOUR_YUV420P
  REQUIRE(s == ZM_SUBPIX_ORDER_YUV420P);

  REQUIRE(zm_colours_from_pixformat(AV_PIX_FMT_YUVJ420P, c, s));
  REQUIRE(c == ZM_COLOUR_GRAY8);  // collision
  REQUIRE(s == ZM_SUBPIX_ORDER_YUVJ420P);

  REQUIRE(zm_colours_from_pixformat(AV_PIX_FMT_RGB24, c, s));
  REQUIRE(c == ZM_COLOUR_RGB24);
  REQUIRE(s == ZM_SUBPIX_ORDER_RGB);

  REQUIRE(zm_colours_from_pixformat(AV_PIX_FMT_BGR24, c, s));
  REQUIRE(c == ZM_COLOUR_RGB24);
  REQUIRE(s == ZM_SUBPIX_ORDER_BGR);

  REQUIRE(zm_colours_from_pixformat(AV_PIX_FMT_RGBA, c, s));
  REQUIRE(c == ZM_COLOUR_RGB32);
  REQUIRE(s == ZM_SUBPIX_ORDER_RGBA);

  REQUIRE(zm_colours_from_pixformat(AV_PIX_FMT_BGRA, c, s));
  REQUIRE(c == ZM_COLOUR_RGB32);
  REQUIRE(s == ZM_SUBPIX_ORDER_BGRA);

  REQUIRE(zm_colours_from_pixformat(AV_PIX_FMT_ARGB, c, s));
  REQUIRE(c == ZM_COLOUR_RGB32);
  REQUIRE(s == ZM_SUBPIX_ORDER_ARGB);

  REQUIRE(zm_colours_from_pixformat(AV_PIX_FMT_ABGR, c, s));
  REQUIRE(c == ZM_COLOUR_RGB32);
  REQUIRE(s == ZM_SUBPIX_ORDER_ABGR);
}

TEST_CASE("zm_colours_from_pixformat: unknown returns false and leaves out params untouched", "[pixformat]") {
  unsigned int c = 42, s = 99;
  REQUIRE_FALSE(zm_colours_from_pixformat(AV_PIX_FMT_YUV444P, c, s));
  REQUIRE(c == 42);
  REQUIRE(s == 99);
}

TEST_CASE("round-trip: from_colours(colours_from_pixformat(fmt)) == fmt", "[pixformat]") {
  const AVPixelFormat formats[] = {
    AV_PIX_FMT_GRAY8,
    AV_PIX_FMT_YUV420P,
    AV_PIX_FMT_YUVJ420P,
    AV_PIX_FMT_RGB24,
    AV_PIX_FMT_BGR24,
    AV_PIX_FMT_RGBA,
    AV_PIX_FMT_BGRA,
    AV_PIX_FMT_ARGB,
    AV_PIX_FMT_ABGR,
  };
  for (AVPixelFormat fmt : formats) {
    unsigned int c = 0, s = 0;
    REQUIRE(zm_colours_from_pixformat(fmt, c, s));
    REQUIRE(zm_pixformat_from_colours(c, s) == fmt);
  }
}

TEST_CASE("zm_bytes_per_pixel: primary-buffer stride", "[pixformat]") {
  // GRAY8 and YUV planar Y-plane all have 1-byte stride in the primary buffer
  REQUIRE(zm_bytes_per_pixel(AV_PIX_FMT_GRAY8) == 1);
  REQUIRE(zm_bytes_per_pixel(AV_PIX_FMT_YUV420P) == 1);
  REQUIRE(zm_bytes_per_pixel(AV_PIX_FMT_YUVJ420P) == 1);

  REQUIRE(zm_bytes_per_pixel(AV_PIX_FMT_RGB24) == 3);
  REQUIRE(zm_bytes_per_pixel(AV_PIX_FMT_BGR24) == 3);

  REQUIRE(zm_bytes_per_pixel(AV_PIX_FMT_RGBA) == 4);
  REQUIRE(zm_bytes_per_pixel(AV_PIX_FMT_BGRA) == 4);
  REQUIRE(zm_bytes_per_pixel(AV_PIX_FMT_ARGB) == 4);
  REQUIRE(zm_bytes_per_pixel(AV_PIX_FMT_ABGR) == 4);

  REQUIRE(zm_bytes_per_pixel(AV_PIX_FMT_NONE) == 0);
  REQUIRE(zm_bytes_per_pixel(AV_PIX_FMT_YUV444P) == 0);
}

TEST_CASE("zm_db_colours_to_pixformat: maps DB values 1/3/4", "[pixformat]") {
  REQUIRE(zm_db_colours_to_pixformat(ZM_COLOUR_GRAY8) == AV_PIX_FMT_GRAY8);
  REQUIRE(zm_db_colours_to_pixformat(ZM_COLOUR_RGB24) == AV_PIX_FMT_RGB24);
  REQUIRE(zm_db_colours_to_pixformat(ZM_COLOUR_RGB32) == AV_PIX_FMT_RGBA);
  REQUIRE(zm_db_colours_to_pixformat(0) == AV_PIX_FMT_NONE);
  REQUIRE(zm_db_colours_to_pixformat(2) == AV_PIX_FMT_NONE);
}

TEST_CASE("zm_is_rgb32: matches 4-byte packed RGB formats only", "[pixformat]") {
  REQUIRE(zm_is_rgb32(AV_PIX_FMT_RGBA));
  REQUIRE(zm_is_rgb32(AV_PIX_FMT_BGRA));
  REQUIRE(zm_is_rgb32(AV_PIX_FMT_ARGB));
  REQUIRE(zm_is_rgb32(AV_PIX_FMT_ABGR));

  REQUIRE_FALSE(zm_is_rgb32(AV_PIX_FMT_RGB24));
  REQUIRE_FALSE(zm_is_rgb32(AV_PIX_FMT_BGR24));
  REQUIRE_FALSE(zm_is_rgb32(AV_PIX_FMT_GRAY8));
  REQUIRE_FALSE(zm_is_rgb32(AV_PIX_FMT_YUV420P));
  REQUIRE_FALSE(zm_is_rgb32(AV_PIX_FMT_YUVJ420P));
  REQUIRE_FALSE(zm_is_rgb32(AV_PIX_FMT_NONE));
}

TEST_CASE("zm_is_rgb24: matches 3-byte packed RGB formats only", "[pixformat]") {
  REQUIRE(zm_is_rgb24(AV_PIX_FMT_RGB24));
  REQUIRE(zm_is_rgb24(AV_PIX_FMT_BGR24));

  REQUIRE_FALSE(zm_is_rgb24(AV_PIX_FMT_RGBA));
  REQUIRE_FALSE(zm_is_rgb24(AV_PIX_FMT_GRAY8));
  REQUIRE_FALSE(zm_is_rgb24(AV_PIX_FMT_YUV420P));
}

TEST_CASE("zm_is_yuv420: matches planar YUV 4:2:0 formats only", "[pixformat]") {
  REQUIRE(zm_is_yuv420(AV_PIX_FMT_YUV420P));
  REQUIRE(zm_is_yuv420(AV_PIX_FMT_YUVJ420P));

  REQUIRE_FALSE(zm_is_yuv420(AV_PIX_FMT_GRAY8));
  REQUIRE_FALSE(zm_is_yuv420(AV_PIX_FMT_YUV444P));
  REQUIRE_FALSE(zm_is_yuv420(AV_PIX_FMT_RGBA));
}

// Regression test for the ZM_COLOUR_GRAY8 == ZM_COLOUR_YUV420P collision.
// The collision is real in zm_rgb.h (both defined to 1) but the AVPixelFormat
// path must disambiguate correctly via subpixelorder, and user-selectable DB
// colours must not be mistaken for the internal YUV420P format.
TEST_CASE("regression: GRAY8/YUV420P collision does not confuse the pipeline", "[pixformat]") {
  // The collision itself
  REQUIRE(ZM_COLOUR_GRAY8 == ZM_COLOUR_YUV420P);
  REQUIRE(ZM_COLOUR_GRAY8 == ZM_COLOUR_YUVJ420P);

  // But the format helpers disambiguate by subpixelorder
  REQUIRE(zm_pixformat_from_colours(ZM_COLOUR_GRAY8, ZM_SUBPIX_ORDER_NONE) == AV_PIX_FMT_GRAY8);
  REQUIRE(zm_pixformat_from_colours(ZM_COLOUR_GRAY8, ZM_SUBPIX_ORDER_YUV420P) == AV_PIX_FMT_YUV420P);
  REQUIRE(zm_pixformat_from_colours(ZM_COLOUR_GRAY8, ZM_SUBPIX_ORDER_YUVJ420P) == AV_PIX_FMT_YUVJ420P);

  // DB value 1 (user-selected "8BitGrey") unambiguously maps to GRAY8, not YUV420P
  REQUIRE(zm_db_colours_to_pixformat(1) == AV_PIX_FMT_GRAY8);
  REQUIRE(zm_db_colours_to_pixformat(1) != AV_PIX_FMT_YUV420P);

  // DB value 4 (user-selected "32BitColour") maps to RGBA, not GRAY8
  REQUIRE(zm_db_colours_to_pixformat(4) == AV_PIX_FMT_RGBA);
  REQUIRE(zm_db_colours_to_pixformat(4) != AV_PIX_FMT_GRAY8);
}
