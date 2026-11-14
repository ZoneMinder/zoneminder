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
#include "zm_poly.h"
#include "zm_rgb.h"

extern "C" {
#include <libavutil/imgutils.h>
#include <libavutil/pixdesc.h>
}

#include <cstring>
#include <vector>

namespace {

struct Fmt {
  const char *name;
  unsigned int colours;
  unsigned int subpix;
  AVPixelFormat pix;
};

const Fmt kFormats[] = {
  {"GRAY8", ZM_COLOUR_GRAY8, ZM_SUBPIX_ORDER_NONE, AV_PIX_FMT_GRAY8},
  {"RGB24", ZM_COLOUR_RGB24, ZM_SUBPIX_ORDER_RGB, AV_PIX_FMT_RGB24},
  {"BGR24", ZM_COLOUR_RGB24, ZM_SUBPIX_ORDER_BGR, AV_PIX_FMT_BGR24},
  {"RGBA", ZM_COLOUR_RGB32, ZM_SUBPIX_ORDER_RGBA, AV_PIX_FMT_RGBA},
  {"BGRA", ZM_COLOUR_RGB32, ZM_SUBPIX_ORDER_BGRA, AV_PIX_FMT_BGRA},
  {"ARGB", ZM_COLOUR_RGB32, ZM_SUBPIX_ORDER_ARGB, AV_PIX_FMT_ARGB},
  {"ABGR", ZM_COLOUR_RGB32, ZM_SUBPIX_ORDER_ABGR, AV_PIX_FMT_ABGR},
  {"YUV420P", ZM_COLOUR_GRAY8, ZM_SUBPIX_ORDER_YUV420P, AV_PIX_FMT_YUV420P},
};

const unsigned int kDims[] = {1, 2, 3, 4, 5, 7, 15, 17, 31, 33};

void bootstrap() {
  config.font_file_location = "data/fonts/04_valid.zmfnt";
}

// Plane geometry of an Image in the layout Image uses internally.
struct Planes {
  uint8_t *data[4] = {};
  int stride[4] = {};
  unsigned int w[3] = {};  // plane width in bytes
  unsigned int h[3] = {};
  int n = 0;
};

Planes planes_of(Image &image, const Fmt &f, unsigned int w, unsigned int h) {
  Planes p;
  REQUIRE(av_image_fill_arrays(p.data, p.stride, image.Buffer(), f.pix, w, h, 32) > 0);
  const AVPixFmtDescriptor *d = av_pix_fmt_desc_get(f.pix);
  if (d->flags & AV_PIX_FMT_FLAG_PLANAR) {
    p.n = 3;
    for (int i = 0; i < 3; i++) {
      p.w[i] = i ? (w + 1) >> d->log2_chroma_w : w;
      p.h[i] = i ? (h + 1) >> d->log2_chroma_h : h;
    }
  } else {
    p.n = 1;
    p.w[0] = w * av_get_bits_per_pixel(d) / 8;
    p.h[0] = h;
  }
  return p;
}

uint8_t pat(int plane, unsigned int x, unsigned int y) {
  return static_cast<uint8_t>(1 + ((plane * 61 + x * 7 + y * 13) % 250));
}

void fill_pattern(Image &image, const Fmt &f, unsigned int w, unsigned int h) {
  Planes p = planes_of(image, f, w, h);
  memset(image.Buffer(), 0xEE, image.Size());
  for (int i = 0; i < p.n; i++)
    for (unsigned int y = 0; y < p.h[i]; y++)
      for (unsigned int x = 0; x < p.w[i]; x++)
        p.data[i][y * p.stride[i] + x] = pat(i, x, y);
}

}  // namespace

TEST_CASE("Image::Overlay at an offset copies a smaller image", "[image][transform]") {
  bootstrap();
  for (const Fmt &f : kFormats) {
    if (f.pix == AV_PIX_FMT_YUV420P) continue;
    INFO(f.name);
    Image dest(8, 6, f.colours, f.subpix);
    fill_pattern(dest, f, 8, 6);
    Image src(3, 2, f.colours, f.subpix);
    fill_pattern(src, f, 3, 2);
    dest.Overlay(src, 2, 3);
    const unsigned int bpp = f.colours;
    for (unsigned int y = 0; y < 2; y++)
      for (unsigned int x = 0; x < 3 * bpp; x++)
        REQUIRE(dest.Buffer()[(3 + y) * dest.LineSize() + 2 * bpp + x]
                == src.Buffer()[y * src.LineSize() + x]);
  }
}
