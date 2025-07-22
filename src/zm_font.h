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

#ifndef ZM_FONT_H
#define ZM_FONT_H

#include "zm_define.h"

#include "span.hpp"
#include <array>
#include <string>
#include <vector>

constexpr uint8 kNumFontSizes = 4;

enum class FontLoadError { kOk, kFileNotFound, kInvalidFile };

#pragma pack(push, 1)
struct FontBitmapHeader {
  uint16 char_height;            // height of every character
  uint16 char_width;             // width of every character
  uint32 number_of_code_points;  // number of codepoints; max. 255 for now
  uint32 idx;                    // offset in data where data for the bitmap starts; not used
  uint8 char_padding;            // padding around characters
  uint8 pad[3];                  // struct padding
};
#pragma pack(pop)

#pragma pack(push, 1)
struct FontFileHeader {
  char magic[6];  // "ZMFNT\0"
  uint8 version;
  uint8 pad;
  std::array<FontBitmapHeader, kNumFontSizes> bitmap_header;
};
#pragma pack(pop)

class FontVariant {
 public:
  static constexpr uint8 kMaxNumCodePoints = 255;
  // height cannot be greater than 200 (arbitrary number; shouldn't need more than this)
  static constexpr uint8 kMaxCharHeight = 200;
  // character width can't be greater than 64 as a row is represented as an uint64
  static constexpr uint8 kMaxCharWidth = 64;

  FontVariant();
  FontVariant(uint16 char_height,
              uint16 char_width,
              uint8 char_padding,
              std::vector<uint64> bitmap);

  uint16 GetCharHeight() const { return char_height_; }
  uint16 GetCharWidth() const { return char_width_; }
  uint8 GetCharPadding() const { return char_padding_; }
  uint8 GetCodepointsCount() const { return codepoint_count_; }

  // Returns the bitmap of the codepoint `idx`. If `idx` is greater than `GetCodepointsCount`
  // a all-zero bitmap with `GetCharHeight` elements is returned.
  nonstd::span<const uint64> GetCodepoint(uint8 idx) const;

 private:
  uint16 char_height_;
  uint16 char_width_;
  uint8 char_padding_;
  uint8 codepoint_count_;
  std::vector<uint64> bitmap_;
};

class ZmFont {
 public:
  FontLoadError LoadFontFile(const std::string &loc);
  const FontVariant &GetFontVariant(uint8 idx) const;

 private:
  std::array<FontVariant, kNumFontSizes> variants_;
};

#endif
