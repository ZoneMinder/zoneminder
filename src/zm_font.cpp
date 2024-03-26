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

#include "zm_font.h"

#include <cstring>
#include <fstream>

constexpr uint8 kRequiredZmFntVersion = 1;

constexpr uint8 FontVariant::kMaxNumCodePoints;
constexpr uint8 FontVariant::kMaxCharHeight;
constexpr uint8 FontVariant::kMaxCharWidth;

FontVariant::FontVariant() : char_height_(0), char_width_(0), char_padding_(0), codepoint_count_(0) {}

FontVariant::FontVariant(uint16 char_height, uint16 char_width, uint8 char_padding, std::vector<uint64> bitmap)
  : char_height_(char_height), char_width_(char_width), char_padding_(char_padding), bitmap_(std::move(bitmap)) {
  if (char_height_ > kMaxCharHeight) {
    throw std::invalid_argument("char_height > kMaxCharHeight");
  }

  if (char_width_ > kMaxCharWidth) {
    throw std::invalid_argument("char_width > kMaxCharWidth");
  }

  if (bitmap_.size() % char_height_ != 0) {
    throw std::invalid_argument("bitmap has wrong length");
  }

  codepoint_count_ = bitmap_.size() / char_height;
}

nonstd::span<const uint64> FontVariant::GetCodepoint(uint8 idx) const {
  static constexpr std::array<uint64, kMaxCharHeight> empty_bitmap = {};

  if (idx >= GetCodepointsCount()) {
    return {empty_bitmap.begin(), GetCharHeight()};
  }

  return {bitmap_.begin() + (idx * GetCharHeight()), GetCharHeight()};
}

std::ifstream &operator>>(std::ifstream &stream, FontBitmapHeader &bm_header) {
  stream.read(reinterpret_cast<char *>(&bm_header), sizeof(bm_header));

  return stream;
}

std::ifstream &operator>>(std::ifstream &stream, FontFileHeader &header) {
  stream.read(header.magic, sizeof(header.magic));
  stream.read(reinterpret_cast<char *>(&header.version), sizeof(header.version));
  stream.seekg(sizeof(header.pad), std::ifstream::cur);

  for (FontBitmapHeader &bm_header : header.bitmap_header)
    stream >> bm_header;

  return stream;
}

FontLoadError ZmFont::LoadFontFile(const std::string &loc) {
  std::ifstream font_file(loc, std::ifstream::binary);
  font_file.exceptions(std::ifstream::badbit);

  if (!font_file.is_open()) {
    return FontLoadError::kFileNotFound;
  }

  FontFileHeader file_header = {};
  font_file >> file_header;

  if (font_file.fail()) {
    return FontLoadError::kInvalidFile;
  }

  if (memcmp(file_header.magic, "ZMFNT", 5) != 0 || file_header.version != kRequiredZmFntVersion) {
    return FontLoadError::kInvalidFile;
  }

  for (int i = 0; i < kNumFontSizes; i++) {
    FontBitmapHeader bitmap_header = file_header.bitmap_header[i];

    if (bitmap_header.char_width > FontVariant::kMaxCharWidth
        || bitmap_header.char_height > FontVariant::kMaxCharHeight
        || bitmap_header.number_of_code_points > FontVariant::kMaxNumCodePoints) {
      return FontLoadError::kInvalidFile;
    }

    std::vector<uint64> bitmap;
    bitmap.resize(static_cast<std::size_t>(bitmap_header.number_of_code_points) * bitmap_header.char_height);

    std::size_t bitmap_bytes = bitmap.size() * sizeof(uint64);
    font_file.read(reinterpret_cast<char *>(bitmap.data()), static_cast<std::streamsize>(bitmap_bytes));

    variants_[i] =
    {bitmap_header.char_height, bitmap_header.char_width, bitmap_header.char_padding, std::move(bitmap)};
  }

  if (font_file.fail()) {
    return FontLoadError::kInvalidFile;
  }

  return FontLoadError::kOk;
}

const FontVariant &ZmFont::GetFontVariant(uint8 idx) const {
  return variants_.at(idx);
}
