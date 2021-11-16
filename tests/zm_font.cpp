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

#include "zm_font.h"

CATCH_REGISTER_ENUM(FontLoadError,
                    FontLoadError::kOk,
                    FontLoadError::kFileNotFound,
                    FontLoadError::kInvalidFile)

TEST_CASE("FontVariant: construction") {
  FontVariant variant;

  SECTION("default construction") {
    REQUIRE(variant.GetCharHeight() == 0);
    REQUIRE(variant.GetCharWidth() == 0);
  }

  SECTION("values in range") {
    constexpr uint8 height = 10;
    constexpr uint8 width = 10;
    constexpr uint8 padding = 2;
    std::vector<uint64> bitmap(FontVariant::kMaxNumCodePoints * height);

    REQUIRE_NOTHROW(variant = FontVariant(height, width, padding, bitmap));

    REQUIRE(variant.GetCharHeight() == height);
    REQUIRE(variant.GetCharWidth() == width);
    REQUIRE(variant.GetCodepointsCount() == FontVariant::kMaxNumCodePoints);
  }

  SECTION("height out of range") {
    constexpr uint8 height = FontVariant::kMaxCharHeight + 1;
    constexpr uint8 width = 10;
    constexpr uint8 padding = 2;
    std::vector<uint64> bitmap(FontVariant::kMaxNumCodePoints * height);

    REQUIRE_THROWS(variant = FontVariant(height, width, padding, bitmap));
  }

  SECTION("width out of range") {
    constexpr uint8 height = 10;
    constexpr uint8 width = FontVariant::kMaxCharWidth + 1;
    constexpr uint8 padding = 2;
    std::vector<uint64> bitmap(FontVariant::kMaxNumCodePoints * height);

    REQUIRE_THROWS(variant = FontVariant(height, width, padding, bitmap));
  }

  SECTION("bitmap of wrong size") {
    constexpr uint8 height = 10;
    constexpr uint8 width = 10;
    constexpr uint8 padding = 2;
    std::vector<uint64> bitmap(FontVariant::kMaxNumCodePoints * height + 1);

    REQUIRE_THROWS(variant = FontVariant(height, width, padding, bitmap));
  }
}

TEST_CASE("FontVariant: GetCodepoint") {
  constexpr uint8 height = 10;
  constexpr uint8 width = 10;
  constexpr uint8 padding = 2;
  std::vector<uint64> bitmap(FontVariant::kMaxNumCodePoints * height);

  // fill bitmap for each codepoint alternating with 1 and std::numeric_limits<uint64>::max()
  // TODO: restore capture initializer when C++14 is supported
  int32 n = 0;
  bool zero = true;
  std::generate(bitmap.begin(), bitmap.end(),
                [n, zero]() mutable {
                  if (n == height) {
                    zero = !zero;
                    n = 0;
                  }
                  n++;
                  if (zero) {
                    return static_cast<uint64>(1);
                  } else {
                    return std::numeric_limits<uint64>::max();
                  }
                });

  FontVariant variant(height, width, padding, bitmap);
  nonstd::span<const uint64> cp;

  SECTION("in bounds") {
    cp = variant.GetCodepoint(0);
    REQUIRE(std::all_of(cp.begin(), cp.end(),
                        [](uint64 l) { return l == 1; }) == true);

    cp = variant.GetCodepoint(1);
    REQUIRE(std::all_of(cp.begin(), cp.end(),
                        [](uint64 l) { return l == std::numeric_limits<uint64>::max(); }) == true);
  }

  SECTION("out-of-bounds: all-zero bitmap") {
    cp = variant.GetCodepoint(FontVariant::kMaxNumCodePoints);
    REQUIRE(std::all_of(cp.begin(), cp.end(),
                        [](uint64 l) { return l == 0; }) == true);
  }
}

TEST_CASE("ZmFont: variants not loaded") {
  ZmFont font;

  SECTION("returns empty variant") {
    FontVariant variant;
    REQUIRE_NOTHROW(variant = font.GetFontVariant(0));

    REQUIRE(variant.GetCharHeight() == 0);
    REQUIRE(variant.GetCharWidth() == 0);
    REQUIRE(variant.GetCodepoint(0).empty() == true);
  }

  SECTION("variant idx out-of-bounds") {
    REQUIRE_THROWS(font.GetFontVariant(kNumFontSizes));
  }
}

TEST_CASE("ZmFont: load font file") {
  ZmFont font;

  SECTION("file not found") {
    REQUIRE(font.LoadFontFile("does_not_exist.zmfnt") == FontLoadError::kFileNotFound);
  }

  SECTION("invalid files") {
    REQUIRE(font.LoadFontFile("data/fonts/01_bad_magic.zmfnt") == FontLoadError::kInvalidFile);
    REQUIRE(font.LoadFontFile("data/fonts/02_variant_invalid.zmfnt") == FontLoadError::kInvalidFile);
    REQUIRE(font.LoadFontFile("data/fonts/03_missing_cps.zmfnt") == FontLoadError::kInvalidFile);
  }

  SECTION("valid file") {
    REQUIRE(font.LoadFontFile("data/fonts/04_valid.zmfnt") == FontLoadError::kOk);

    uint8 var_idx = GENERATE(range(static_cast<std::remove_cv<decltype(kNumFontSizes)>::type>(0), kNumFontSizes));
    FontVariant variant = font.GetFontVariant(var_idx);
    REQUIRE(variant.GetCharHeight() == 10 + var_idx);
    REQUIRE(variant.GetCharWidth() == 10 + var_idx);

    uint8 cp_idx =
        GENERATE_COPY(range(static_cast<decltype(variant.GetCodepointsCount())>(0), variant.GetCodepointsCount()));
    nonstd::span<const uint64> cp = variant.GetCodepoint(cp_idx);
    REQUIRE(std::all_of(cp.begin(), cp.end(),
                        [=](uint64 l) { return l == var_idx; }) == true);
  }
}
