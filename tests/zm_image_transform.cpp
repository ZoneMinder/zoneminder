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

TEST_CASE("Image::Rotate matches a reference for every format and odd size", "[image][transform]") {
  bootstrap();
  for (const Fmt &f : kFormats) {
    for (unsigned int w : kDims) {
      for (unsigned int h : kDims) {
        for (int angle : {90, 180, 270}) {
          INFO(f.name << " " << w << "x" << h << " rot " << angle);
          Image image(w, h, f.colours, f.subpix);
          fill_pattern(image, f, w, h);
          image.Rotate(angle);
          const unsigned int nw = angle == 180 ? w : h;
          const unsigned int nh = angle == 180 ? h : w;
          REQUIRE(image.Width() == nw);
          REQUIRE(image.Height() == nh);
          Planes d = planes_of(image, f, nw, nh);
          const AVPixFmtDescriptor *desc = av_pix_fmt_desc_get(f.pix);
          const unsigned int bpp = (desc->flags & AV_PIX_FMT_FLAG_PLANAR) ? 1 : av_get_bits_per_pixel(desc) / 8;
          Image src(w, h, f.colours, f.subpix);
          fill_pattern(src, f, w, h);
          Planes s = planes_of(src, f, w, h);
          for (int i = 0; i < d.n; i++) {
            const unsigned int sw = s.w[i] / bpp, sh = s.h[i];
            for (unsigned int sy = 0; sy < sh; sy++) {
              for (unsigned int sx = 0; sx < sw; sx++) {
                unsigned int dx, dy;
                if (angle == 90) { dx = sh - 1 - sy; dy = sx; }
                else if (angle == 180) { dx = sw - 1 - sx; dy = sh - 1 - sy; }
                else { dx = sy; dy = sw - 1 - sx; }
                for (unsigned int b = 0; b < bpp; b++) {
                  REQUIRE(d.data[i][dy * d.stride[i] + dx * bpp + b]
                          == s.data[i][sy * s.stride[i] + sx * bpp + b]);
                }
              }
            }
          }
        }
      }
    }
  }
}

TEST_CASE("Image::Flip matches a reference for every format and odd size", "[image][transform]") {
  bootstrap();
  for (const Fmt &f : kFormats) {
    for (unsigned int w : kDims) {
      for (unsigned int h : kDims) {
        for (bool lr : {true, false}) {
          INFO(f.name << " " << w << "x" << h << (lr ? " lr" : " ud"));
          Image image(w, h, f.colours, f.subpix);
          fill_pattern(image, f, w, h);
          image.Flip(lr);
          REQUIRE(image.Width() == w);
          REQUIRE(image.Height() == h);
          Planes d = planes_of(image, f, w, h);
          Image src(w, h, f.colours, f.subpix);
          fill_pattern(src, f, w, h);
          Planes s = planes_of(src, f, w, h);
          const AVPixFmtDescriptor *desc = av_pix_fmt_desc_get(f.pix);
          const unsigned int bpp = (desc->flags & AV_PIX_FMT_FLAG_PLANAR) ? 1 : av_get_bits_per_pixel(desc) / 8;
          for (int i = 0; i < d.n; i++) {
            const unsigned int sw = s.w[i] / bpp, sh = s.h[i];
            for (unsigned int y = 0; y < sh; y++)
              for (unsigned int x = 0; x < sw; x++)
                for (unsigned int b = 0; b < bpp; b++) {
                  const unsigned int dx = lr ? sw - 1 - x : x;
                  const unsigned int dy = lr ? y : sh - 1 - y;
                  REQUIRE(d.data[i][dy * d.stride[i] + dx * bpp + b]
                          == s.data[i][y * s.stride[i] + x * bpp + b]);
                }
          }
        }
      }
    }
  }
}

TEST_CASE("Image::Crop copies the requested rectangle for every format", "[image][transform]") {
  bootstrap();
  for (const Fmt &f : kFormats) {
    for (unsigned int w : {1u, 2u, 3u, 5u, 17u, 33u}) {
      for (unsigned int h : {1u, 2u, 3u, 5u, 17u}) {
        for (unsigned int lo_x : {0u, 1u, w / 2, w - 1}) {
          for (unsigned int lo_y : {0u, 1u, h / 2, h - 1}) {
            if (lo_x >= w || lo_y >= h) continue;
            INFO(f.name << " " << w << "x" << h << " crop from " << lo_x << "," << lo_y);
            Image image(w, h, f.colours, f.subpix);
            fill_pattern(image, f, w, h);
            REQUIRE(image.Crop(lo_x, lo_y, w - 1, h - 1));
            const unsigned int nw = w - lo_x, nh = h - lo_y;
            REQUIRE(image.Width() == nw);
            REQUIRE(image.Height() == nh);
            REQUIRE(image.Size() >= static_cast<unsigned int>(av_image_get_buffer_size(f.pix, nw, nh, 32)));
            if (f.pix == AV_PIX_FMT_YUV420P) continue;  // chroma grid is realigned, covered in zm_image.cpp
            Planes d = planes_of(image, f, nw, nh);
            Image src(w, h, f.colours, f.subpix);
            fill_pattern(src, f, w, h);
            Planes s = planes_of(src, f, w, h);
            const unsigned int bpp = d.w[0] / nw;
            for (unsigned int y = 0; y < nh; y++)
              for (unsigned int x = 0; x < nw * bpp; x++)
                REQUIRE(d.data[0][y * d.stride[0] + x]
                        == s.data[0][(lo_y + y) * s.stride[0] + lo_x * bpp + x]);
          }
        }
      }
    }
  }
}

TEST_CASE("Image::Crop rejects rectangles that leave the image", "[image][transform]") {
  bootstrap();
  for (const Fmt &f : kFormats) {
    Image image(5, 3, f.colours, f.subpix);
    fill_pattern(image, f, 5, 3);
    REQUIRE_FALSE(image.Crop(0, 0, 5, 2));
    REQUIRE_FALSE(image.Crop(0, 0, 4, 3));
    REQUIRE_FALSE(image.Crop(3, 0, 2, 2));
    REQUIRE_FALSE(image.Crop(0, 2, 4, 1));
    REQUIRE_FALSE(image.Crop(0xFFFFFFFFu, 0, 0xFFFFFFFFu, 2));
    REQUIRE(image.Width() == 5);
    REQUIRE(image.Height() == 3);
  }
}

TEST_CASE("Image::Scale handles odd and tiny sizes for every format", "[image][transform]") {
  bootstrap();
  for (const Fmt &f : kFormats) {
    for (unsigned int w : {1u, 3u, 17u, 33u}) {
      for (unsigned int h : {1u, 3u, 17u}) {
        for (unsigned int nw : {1u, 2u, 5u, 40u}) {
          for (unsigned int nh : {1u, 3u, 40u}) {
            INFO(f.name << " " << w << "x" << h << " -> " << nw << "x" << nh);
            Image image(w, h, f.colours, f.subpix);
            fill_pattern(image, f, w, h);
            image.Scale(nw, nh);
            REQUIRE(image.Width() == nw);
            REQUIRE(image.Height() == nh);
            REQUIRE(image.Size() >= static_cast<unsigned int>(av_image_get_buffer_size(f.pix, nw, nh, 32)));
          }
        }
      }
    }
  }
}

TEST_CASE("Image::Scale by factor handles odd sizes", "[image][transform]") {
  bootstrap();
  for (const Fmt &f : kFormats) {
    for (unsigned int w : {1u, 3u, 17u}) {
      for (unsigned int h : {1u, 3u, 17u}) {
        for (unsigned int factor : {25u, 50u, 200u, 400u}) {
          INFO(f.name << " " << w << "x" << h << " factor " << factor);
          Image image(w, h, f.colours, f.subpix);
          fill_pattern(image, f, w, h);
          image.Scale(factor);
        }
      }
    }
  }
}

TEST_CASE("Image::Deinterlace_Discard/Blend/Linear stay in bounds for odd sizes", "[image][transform]") {
  bootstrap();
  for (const Fmt &f : kFormats) {
    for (unsigned int w : kDims) {
      for (unsigned int h : kDims) {
        INFO(f.name << " " << w << "x" << h);
        {
          Image image(w, h, f.colours, f.subpix);
          fill_pattern(image, f, w, h);
          image.Deinterlace_Discard();
        }
        {
          Image image(w, h, f.colours, f.subpix);
          fill_pattern(image, f, w, h);
          image.Deinterlace_Blend();
        }
        for (int divider = 1; divider <= 4; divider++) {
          Image image(w, h, f.colours, f.subpix);
          fill_pattern(image, f, w, h);
          image.Deinterlace_Blend_CustomRatio(divider);
        }
      }
    }
  }
}

TEST_CASE("Image::Deinterlace_4Field stays in bounds for even heights", "[image][transform]") {
  bootstrap();
  for (const Fmt &f : kFormats) {
    for (unsigned int w : kDims) {
      for (unsigned int h : {2u, 4u, 6u, 16u, 18u, 32u}) {
        INFO(f.name << " " << w << "x" << h);
        Image a(w, h, f.colours, f.subpix);
        Image b(w, h, f.colours, f.subpix);
        fill_pattern(a, f, w, h);
        fill_pattern(b, f, w, h);
        a.Deinterlace_4Field(&b, 10);
      }
    }
  }
}

TEST_CASE("Image::Blend handles every format and odd size", "[image][transform]") {
  bootstrap();
  for (const Fmt &f : kFormats) {
    for (unsigned int w : kDims) {
      for (unsigned int h : kDims) {
        INFO(f.name << " " << w << "x" << h);
        Image a(w, h, f.colours, f.subpix);
        Image b(w, h, f.colours, f.subpix);
        fill_pattern(a, f, w, h);
        memset(b.Buffer(), 0x80, b.Size());
        a.Blend(b, 12);
      }
    }
  }
}

TEST_CASE("Image::Fill with a box covering the whole image stays in bounds", "[image][transform]") {
  bootstrap();
  for (const Fmt &f : kFormats) {
    for (unsigned int w : kDims) {
      for (unsigned int h : kDims) {
        INFO(f.name << " " << w << "x" << h);
        Image image(w, h, f.colours, f.subpix);
        fill_pattern(image, f, w, h);
        const Box all(Vector2(0, 0), Vector2(w - 1, h - 1));
        image.Fill(0x00010203, &all);
        image.Fill(0x00010203, 3, &all);
        image.Fill(0x00010203);
        image.Fill(0x00010203, 2);
      }
    }
  }
}

TEST_CASE("Image::Fill and Outline with a polygon touching every edge stay in bounds", "[image][transform]") {
  bootstrap();
  for (const Fmt &f : kFormats) {
    for (unsigned int w : {1u, 2u, 3u, 5u, 7u, 17u, 33u}) {
      for (unsigned int h : {1u, 2u, 3u, 5u, 17u}) {
        INFO(f.name << " " << w << "x" << h);
        const int32 mx = w - 1, my = h - 1;
        const Polygon box({Vector2(0, 0), Vector2(mx, 0), Vector2(mx, my), Vector2(0, my)});
        const Polygon tri({Vector2(0, 0), Vector2(mx, my / 2), Vector2(0, my)});
        const Polygon tri2({Vector2(mx, 0), Vector2(mx, my), Vector2(0, my / 2)});
        for (const Polygon *poly : {&box, &tri, &tri2}) {
          Image image(w, h, f.colours, f.subpix);
          fill_pattern(image, f, w, h);
          for (int density : {1, 2, 3}) image.Fill(0x00ff0000, density, *poly);
          image.Outline(0x00ff0000, *poly);
        }
      }
    }
  }
}

TEST_CASE("Image::BuildHighlight stays in bounds for odd sizes", "[image][transform]") {
  bootstrap();
  for (const Fmt &f : kFormats) {
    for (unsigned int w : kDims) {
      for (unsigned int h : kDims) {
        INFO(f.name << " " << w << "x" << h);
        Image gray(w, h, ZM_COLOUR_GRAY8, ZM_SUBPIX_ORDER_NONE);
        memset(gray.Buffer(), 0, gray.Size());
        for (unsigned int y = 0; y < h; y++)
          for (unsigned int x = 0; x < w; x++)
            gray.Buffer()[y * gray.LineSize() + x] = ((x + y) % 3) ? 0xff : 0;
        for (bool edges : {true, false}) {
          Image *high = gray.BuildHighlight(0x00ff0000, f.colours, f.subpix, nullptr, edges);
          REQUIRE(high != nullptr);
          const Box all(Vector2(0, 0), Vector2(w - 1, h - 1));
          Image *high2 = gray.BuildHighlight(0x00ff0000, f.colours, f.subpix, &all, edges);
          delete high;
          delete high2;
        }
      }
    }
  }
}

TEST_CASE("Image::Overlay of a highlight works for odd sizes and formats", "[image][transform]") {
  bootstrap();
  for (const Fmt &dst : kFormats) {
    for (const Fmt &src : kFormats) {
      for (unsigned int w : {1u, 3u, 5u, 17u}) {
        for (unsigned int h : {1u, 3u, 5u}) {
          INFO(dst.name << " <- " << src.name << " " << w << "x" << h);
          Image a(w, h, dst.colours, dst.subpix);
          Image b(w, h, src.colours, src.subpix);
          fill_pattern(a, dst, w, h);
          fill_pattern(b, src, w, h);
          a.Overlay(b);
        }
      }
    }
  }
}

TEST_CASE("Image::Assign from an Image with a different row stride stays in bounds", "[image][transform]") {
  bootstrap();
  for (const Fmt &f : kFormats) {
    if (f.pix == AV_PIX_FMT_YUV420P) continue;
    for (unsigned int w : {1u, 3u, 5u, 17u, 33u}) {
      INFO(f.name << " width " << w);
      const unsigned int h = 4;
      Image src(w, h, f.colours, f.subpix);
      fill_pattern(src, f, w, h);
      // A held buffer with a tight stride, as the V4L2/legacy constructors build.
      std::vector<uint8_t> tight(w * f.colours * h + 64, 0);
      Image dst(w, w * f.colours, h, f.colours, f.subpix, tight.data(), 0u);
      dst.Assign(src);
      Image copy(dst);
      REQUIRE(copy.Width() == w);
    }
  }
}

TEST_CASE("Image::Assign from a raw buffer rejects an undersized source", "[image][transform]") {
  bootstrap();
  for (const Fmt &f : kFormats) {
    INFO(f.name);
    Image image(7, 5, f.colours, f.subpix);
    fill_pattern(image, f, 7, 5);
    const int packed = av_image_get_buffer_size(f.pix, 7, 5, 1);
    std::vector<uint8_t> small(packed - 1, 0x55);
    image.Assign(7, 5, f.colours, f.subpix, small.data(), small.size());
    // Contents untouched
    Image ref(7, 5, f.colours, f.subpix);
    fill_pattern(ref, f, 7, 5);
    REQUIRE(memcmp(image.Buffer(), ref.Buffer(), image.Size()) == 0);
  }
}

TEST_CASE("Image::Assign from a raw buffer into a held buffer too small is refused", "[image][transform]") {
  bootstrap();
  for (const Fmt &f : kFormats) {
    INFO(f.name);
    const int small_size = av_image_get_buffer_size(f.pix, 4, 4, 32);
    std::vector<uint8_t> held(small_size, 0x11);
    Image image(4, 4, f.colours, f.subpix, held.data(), static_cast<unsigned long>(small_size));
    image.HoldBuffer(1);
    const int packed = av_image_get_buffer_size(f.pix, 9, 9, 1);
    std::vector<uint8_t> big(packed, 0x77);
    image.Assign(9, 9, f.colours, f.subpix, big.data(), big.size());
    for (uint8_t v : held) REQUIRE(v == 0x11);
    image.AssignDirect(9, 9, f.colours, f.subpix, big.data(), big.size(), ZM_BUFTYPE_DONTFREE);
    for (uint8_t v : held) REQUIRE(v == 0x11);
    Image bigger(9, 9, f.colours, f.subpix);
    image.Assign(bigger);
    for (uint8_t v : held) REQUIRE(v == 0x11);
  }
}

TEST_CASE("Image::Assign from an AVFrame converts odd sizes and formats", "[image][transform]") {
  bootstrap();
  const AVPixelFormat src_fmts[] = {AV_PIX_FMT_YUV420P, AV_PIX_FMT_YUVJ420P, AV_PIX_FMT_RGB24, AV_PIX_FMT_GRAY8, AV_PIX_FMT_YUV422P};
  for (AVPixelFormat sf : src_fmts) {
    for (const Fmt &f : kFormats) {
      for (unsigned int w : {1u, 3u, 17u, 33u}) {
        for (unsigned int h : {1u, 3u, 17u}) {
          INFO(av_get_pix_fmt_name(sf) << " -> " << f.name << " " << w << "x" << h);
          av_frame_ptr frame{av_frame_alloc()};
          frame->format = sf;
          frame->width = w;
          frame->height = h;
          REQUIRE(av_frame_get_buffer(frame.get(), 0) == 0);
          for (int p = 0; p < 4 && frame->buf[p]; p++) memset(frame->buf[p]->data, 0x60, frame->buf[p]->size);
          Image image(w, h, f.colours, f.subpix);
          REQUIRE(image.Assign(frame.get()));
          Image grown(w + 2, h + 1, f.colours, f.subpix);
          grown.Assign(frame.get());  // size mismatch goes through swscale
        }
      }
    }
  }
}
