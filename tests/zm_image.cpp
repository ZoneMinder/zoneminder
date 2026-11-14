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
#include <jpeglib.h>
}

#include <cstdlib>
#include <cstring>
#include <unistd.h>

namespace {

// Image::Initialise() loads the timestamp font from the DB-backed config,
// which the test binary doesn't have; point it at the test fixture font.
void bootstrap_image_config() {
  config.font_file_location = "data/fonts/04_valid.zmfnt";
}

struct Planes {
  uint8_t *data[4] = {nullptr, nullptr, nullptr, nullptr};
  int stride[4] = {0, 0, 0, 0};
};

// View an Image's buffer with the same layout Image uses internally
// (av_image_fill_arrays, align 32).
Planes plane_view(Image &image, AVPixelFormat fmt, int w, int h) {
  Planes planes;
  REQUIRE(av_image_fill_arrays(planes.data, planes.stride, image.Buffer(),
                               fmt, w, h, 32) > 0);
  return planes;
}

}  // namespace

// Regression: Image::Rotate/Flip computed chroma plane dimensions with
// AV_CEIL_RSHIFT on unsigned operands. The macro's runtime form
// -((-(a)) >> (b)) is only valid for signed types; with unsigned width it
// yields 2^31 + width/2, sending the chroma loops billions of samples out
// of bounds (segfault on every rotated planar image - decoder thread crash
// on any monitor with a rotated orientation).
TEST_CASE("Image::Rotate YUV420P", "[image]") {
  bootstrap_image_config();
  const int w = 1280, h = 720;
  Image image(w, h, ZM_COLOUR_GRAY8, ZM_SUBPIX_ORDER_YUV420P);
  REQUIRE(image.Buffer() != nullptr);

  // Distinct fill per plane + a marker pixel in each
  Planes src = plane_view(image, AV_PIX_FMT_YUV420P, w, h);
  memset(src.data[0], 0x10, static_cast<size_t>(src.stride[0]) * h);
  memset(src.data[1], 0x20, static_cast<size_t>(src.stride[1]) * (h / 2));
  memset(src.data[2], 0x30, static_cast<size_t>(src.stride[2]) * (h / 2));
  src.data[0][20 * src.stride[0] + 10] = 200;        // luma (10, 20)
  src.data[1][30 * src.stride[1] + 40] = 210;        // U (40, 30)
  src.data[2][50 * src.stride[2] + 60] = 220;        // V (60, 50)

  SECTION("rotate 90") {
    image.Rotate(90);
    REQUIRE(image.Width() == static_cast<unsigned int>(h));
    REQUIRE(image.Height() == static_cast<unsigned int>(w));

    Planes dst = plane_view(image, AV_PIX_FMT_YUV420P, h, w);
    // 90deg: (sx, sy) -> (dx, dy) = (src_h-1-sy, sx)
    REQUIRE(dst.data[0][10 * dst.stride[0] + (h - 1 - 20)] == 200);
    // chroma plane is (w/2 x h/2): (40, 30) -> (h/2-1-30, 40)
    REQUIRE(dst.data[1][40 * dst.stride[1] + (h / 2 - 1 - 30)] == 210);
    REQUIRE(dst.data[2][60 * dst.stride[2] + (h / 2 - 1 - 50)] == 220);
  }

  SECTION("rotate 180") {
    image.Rotate(180);
    REQUIRE(image.Width() == static_cast<unsigned int>(w));
    REQUIRE(image.Height() == static_cast<unsigned int>(h));

    Planes dst = plane_view(image, AV_PIX_FMT_YUV420P, w, h);
    // 180deg: (sx, sy) -> (src_w-1-sx, src_h-1-sy)
    REQUIRE(dst.data[0][(h - 1 - 20) * dst.stride[0] + (w - 1 - 10)] == 200);
    REQUIRE(dst.data[1][(h / 2 - 1 - 30) * dst.stride[1] + (w / 2 - 1 - 40)] == 210);
    REQUIRE(dst.data[2][(h / 2 - 1 - 50) * dst.stride[2] + (w / 2 - 1 - 60)] == 220);
  }

  SECTION("rotate 270") {
    image.Rotate(270);
    REQUIRE(image.Width() == static_cast<unsigned int>(h));
    REQUIRE(image.Height() == static_cast<unsigned int>(w));

    Planes dst = plane_view(image, AV_PIX_FMT_YUV420P, h, w);
    // 270deg: (sx, sy) -> (dx, dy) = (sy, src_w-1-sx)
    REQUIRE(dst.data[0][(w - 1 - 10) * dst.stride[0] + 20] == 200);
    REQUIRE(dst.data[1][(w / 2 - 1 - 40) * dst.stride[1] + 30] == 210);
    REQUIRE(dst.data[2][(w / 2 - 1 - 60) * dst.stride[2] + 50] == 220);
  }
}

TEST_CASE("Image::Rotate YUV420P odd dimensions", "[image]") {
  bootstrap_image_config();
  // Odd luma dims exercise the ceiling in the chroma plane size: 101x57
  // luma -> 51x29 chroma.
  const int w = 101, h = 57;
  Image image(w, h, ZM_COLOUR_GRAY8, ZM_SUBPIX_ORDER_YUV420P);

  Planes src = plane_view(image, AV_PIX_FMT_YUV420P, w, h);
  const int cw = (w + 1) / 2, ch = (h + 1) / 2;
  memset(src.data[0], 0x10, static_cast<size_t>(src.stride[0]) * h);
  memset(src.data[1], 0x20, static_cast<size_t>(src.stride[1]) * ch);
  memset(src.data[2], 0x30, static_cast<size_t>(src.stride[2]) * ch);
  // Marker in the LAST chroma column/row - the part flooring would drop
  src.data[1][(ch - 1) * src.stride[1] + (cw - 1)] = 211;

  image.Rotate(90);
  REQUIRE(image.Width() == static_cast<unsigned int>(h));
  REQUIRE(image.Height() == static_cast<unsigned int>(w));

  Planes dst = plane_view(image, AV_PIX_FMT_YUV420P, h, w);
  // (cw-1, ch-1) -> (ch-1-(ch-1), cw-1) = (0, cw-1)
  REQUIRE(dst.data[1][(cw - 1) * dst.stride[1] + 0] == 211);
}

// Regression: SWScale::Convert guessed each buffer's row alignment from
// `width % 32 ? 1 : 32`. Image buffers are ALWAYS laid out align-32
// (av_image_fill_arrays/av_image_get_buffer_size with align=32), so for any
// width not divisible by 32 the converter read luma rows 16 bytes short and
// chroma planes from packed (wrong) offsets. Rotating a 1280x720 monitor
// yields exactly such a width (720 % 32 == 16): every Scale() of a rotated
// frame came out sheared with garbage chroma, while unrotated monitors
// (width % 32 == 0) were untouched.
TEST_CASE("Image::Scale YUV420P with non-32-multiple width", "[image]") {
  bootstrap_image_config();
  const int w = 720, h = 1280;  // rotated 1280x720, as ROTATE_90/270 produces
  Image image(w, h, ZM_COLOUR_GRAY8, ZM_SUBPIX_ORDER_YUV420P);
  REQUIRE(image.Buffer() != nullptr);

  // Column-banded luma (every row identical: left half 50, right half 200)
  // and uniform chroma. Any per-row drift or plane-offset error shows up as
  // rows that differ from each other or chroma that isn't the fill value.
  Planes src = plane_view(image, AV_PIX_FMT_YUV420P, w, h);
  for (int y = 0; y < h; y++) {
    uint8_t *row = src.data[0] + static_cast<size_t>(y) * src.stride[0];
    memset(row, 50, w / 2);
    memset(row + w / 2, 200, w - w / 2);
  }
  memset(src.data[1], 100, static_cast<size_t>(src.stride[1]) * (h / 2));
  memset(src.data[2], 160, static_cast<size_t>(src.stride[2]) * (h / 2));

  image.Scale(w / 2, h / 2);
  REQUIRE(image.Width() == static_cast<unsigned int>(w / 2));
  REQUIRE(image.Height() == static_cast<unsigned int>(h / 2));

  Planes dst = plane_view(image, AV_PIX_FMT_YUV420P, w / 2, h / 2);
  // Identical input rows must produce identical output rows. Sample the
  // middle of each band, away from the edge where bilinear blends.
  for (int y : {0, h / 8, h / 4, h / 2 - 1}) {
    const uint8_t *row = dst.data[0] + static_cast<size_t>(y) * dst.stride[0];
    INFO("output luma row " << y);
    CHECK(std::abs(static_cast<int>(row[w / 8]) - 50) <= 4);
    CHECK(std::abs(static_cast<int>(row[w / 4 + w / 8]) - 200) <= 4);
  }
  for (int y : {0, h / 8, h / 4 - 1}) {
    INFO("output chroma row " << y);
    CHECK(std::abs(static_cast<int>(dst.data[1][static_cast<size_t>(y) * dst.stride[1] + w / 8]) - 100) <= 4);
    CHECK(std::abs(static_cast<int>(dst.data[2][static_cast<size_t>(y) * dst.stride[2] + w / 8]) - 160) <= 4);
  }
}

// zms scales the shared memory frame into a scratch image with ScaleFrom
// instead of copying it first; the result must match copying then scaling.
TEST_CASE("Image::ScaleFrom matches Assign then Scale", "[image]") {
  bootstrap_image_config();
  struct Case { int w, h; unsigned int colours, subpix; } cases[] = {
    {1920, 1080, ZM_COLOUR_RGB32, ZM_SUBPIX_ORDER_RGBA},
    {1920, 1080, ZM_COLOUR_GRAY8, ZM_SUBPIX_ORDER_YUV420P},
    {1000, 562, ZM_COLOUR_RGB24, ZM_SUBPIX_ORDER_RGB},
  };
  for (const Case &c : cases) {
    INFO(c.w << "x" << c.h << " colours " << c.colours);
    Image src(c.w, c.h, c.colours, c.subpix);
    REQUIRE(src.Buffer() != nullptr);
    uint8_t *p = src.WriteBuffer(c.w, c.h, c.colours, c.subpix);
    for (unsigned int i = 0; i < src.Size(); i++) p[i] = static_cast<uint8_t>((i * 31 + i / 997) % 251);

    Image copied;
    copied.Assign(src);
    copied.Scale(c.w / 2, c.h / 2);

    Image scaled;
    scaled.ScaleFrom(src, c.w / 2, c.h / 2);
    REQUIRE(scaled.Width() == copied.Width());
    REQUIRE(scaled.Height() == copied.Height());
    REQUIRE(scaled.LineSize() == copied.LineSize());
    REQUIRE(scaled.Size() == copied.Size());
    REQUIRE(scaled.PixFormat() == copied.PixFormat());
    // Compare the visible bytes of each row; sws_scale leaves row padding unwritten.
    const AVPixelFormat fmt = copied.PixFormat();
    const int ow = copied.Width(), oh = copied.Height();
    Planes a = plane_view(copied, fmt, ow, oh);
    Planes b = plane_view(scaled, fmt, ow, oh);
    const AVPixFmtDescriptor *desc = av_pix_fmt_desc_get(fmt);
    for (int plane = 0; plane < 4 && a.data[plane]; plane++) {
      const int rows = plane ? AV_CEIL_RSHIFT(oh, static_cast<int>(desc->log2_chroma_h)) : oh;
      const int bytes = av_image_get_linesize(fmt, ow, plane);
      for (int y = 0; y < rows; y++) {
        REQUIRE(memcmp(a.data[plane] + static_cast<size_t>(y) * a.stride[plane],
                       b.data[plane] + static_cast<size_t>(y) * b.stride[plane], bytes) == 0);
      }
    }

    const uint8_t *buffer = scaled.Buffer();
    scaled.ScaleFrom(src, c.w / 2, c.h / 2);
    CHECK(scaled.Buffer() == buffer);
  }
}

TEST_CASE("Image::Flip YUV420P", "[image]") {
  bootstrap_image_config();
  const int w = 640, h = 480;
  Image image(w, h, ZM_COLOUR_GRAY8, ZM_SUBPIX_ORDER_YUV420P);

  Planes src = plane_view(image, AV_PIX_FMT_YUV420P, w, h);
  memset(src.data[0], 0x10, static_cast<size_t>(src.stride[0]) * h);
  memset(src.data[1], 0x20, static_cast<size_t>(src.stride[1]) * (h / 2));
  memset(src.data[2], 0x30, static_cast<size_t>(src.stride[2]) * (h / 2));
  src.data[0][20 * src.stride[0] + 10] = 200;
  src.data[1][30 * src.stride[1] + 40] = 210;

  SECTION("horizontal") {
    image.Flip(true);
    Planes dst = plane_view(image, AV_PIX_FMT_YUV420P, w, h);
    REQUIRE(dst.data[0][20 * dst.stride[0] + (w - 1 - 10)] == 200);
    REQUIRE(dst.data[1][30 * dst.stride[1] + (w / 2 - 1 - 40)] == 210);
  }

  SECTION("vertical") {
    image.Flip(false);
    Planes dst = plane_view(image, AV_PIX_FMT_YUV420P, w, h);
    REQUIRE(dst.data[0][(h - 1 - 20) * dst.stride[0] + 10] == 200);
    REQUIRE(dst.data[1][(h / 2 - 1 - 30) * dst.stride[1] + 40] == 210);
  }
}

// Regression: Image::Crop built a tightly packed width * colours buffer.
// AssignDirect wants 32-byte aligned rows and, for YUV420P, the chroma planes
// as well, so it refused that buffer whenever the cropped row wasn't a
// multiple of 32 bytes or the image was planar: "Attempt to directly assign
// buffer from an undersized buffer", the image stayed uncropped and the new
// buffer leaked. nph-zms crops every frame of a zoomed live stream.
TEST_CASE("Image::Crop YUV420P", "[image]") {
  bootstrap_image_config();
  const int w = 1280, h = 720;
  Image image(w, h, ZM_COLOUR_GRAY8, ZM_SUBPIX_ORDER_YUV420P);

  Planes src = plane_view(image, AV_PIX_FMT_YUV420P, w, h);
  memset(src.data[0], 0x10, static_cast<size_t>(src.stride[0]) * h);
  memset(src.data[1], 0x20, static_cast<size_t>(src.stride[1]) * (h / 2));
  memset(src.data[2], 0x30, static_cast<size_t>(src.stride[2]) * (h / 2));
  src.data[0][120 * src.stride[0] + 100] = 200;  // luma (100, 120)
  src.data[1][61 * src.stride[1] + 51] = 210;    // U (51, 61)
  src.data[2][62 * src.stride[2] + 52] = 220;    // V (52, 62)

  // 1164x656 from (58, 32): the crop nph-zms makes at the first zoom step.
  const unsigned int lo_x = 58, lo_y = 32, cw = 1164, ch = 656;
  REQUIRE(image.Crop(lo_x, lo_y, lo_x + cw - 1, lo_y + ch - 1));
  REQUIRE(image.Width() == cw);
  REQUIRE(image.Height() == ch);
  REQUIRE(image.Size() == static_cast<unsigned int>(
            av_image_get_buffer_size(AV_PIX_FMT_YUV420P, cw, ch, 32)));

  Planes dst = plane_view(image, AV_PIX_FMT_YUV420P, cw, ch);
  REQUIRE(dst.data[0][(120 - lo_y) * dst.stride[0] + (100 - lo_x)] == 200);
  REQUIRE(dst.data[1][(61 - lo_y / 2) * dst.stride[1] + (51 - lo_x / 2)] == 210);
  REQUIRE(dst.data[2][(62 - lo_y / 2) * dst.stride[2] + (52 - lo_x / 2)] == 220);
  REQUIRE(dst.data[1][0] == 0x20);
  REQUIRE(dst.data[2][0] == 0x30);
}

// A YUV420P chroma sample covers 2x2 luma pixels, so a crop starting on an odd
// luma column or row can't start its chroma with it: the chroma would be half a
// pixel off. Crop moves the window to the even position instead, keeping its
// size.
TEST_CASE("Image::Crop YUV420P from an odd position keeps chroma aligned", "[image]") {
  bootstrap_image_config();
  const int w = 128, h = 64;
  Image image(w, h, ZM_COLOUR_GRAY8, ZM_SUBPIX_ORDER_YUV420P);

  Planes src = plane_view(image, AV_PIX_FMT_YUV420P, w, h);
  for (int y = 0; y < h; y++)
    for (int x = 0; x < w; x++)
      src.data[0][y * src.stride[0] + x] = static_cast<uint8_t>(x + y);
  for (int y = 0; y < h / 2; y++)
    for (int x = 0; x < w / 2; x++)
      src.data[1][y * src.stride[1] + x] = static_cast<uint8_t>(x * 2 + y);

  const unsigned int lo_x = 11, lo_y = 7, cw = 40, ch = 20;
  REQUIRE(image.Crop(lo_x, lo_y, lo_x + cw - 1, lo_y + ch - 1));
  REQUIRE(image.Width() == cw);
  REQUIRE(image.Height() == ch);

  // Starts at (10, 6), whose chroma sample is (5, 3).
  Planes dst = plane_view(image, AV_PIX_FMT_YUV420P, cw, ch);
  REQUIRE(dst.data[0][0] == static_cast<uint8_t>(10 + 6));
  REQUIRE(dst.data[1][0] == static_cast<uint8_t>(5 * 2 + 3));
}

TEST_CASE("Image::Crop RGBA with a row that is not a multiple of 32 bytes", "[image]") {
  bootstrap_image_config();
  const int w = 64, h = 16;
  Image image(w, h, ZM_COLOUR_RGB32, ZM_SUBPIX_ORDER_RGBA);
  for (int y = 0; y < h; y++)
    for (int x = 0; x < w; x++)
      for (int c = 0; c < 4; c++)
        image.Buffer()[y * image.LineSize() + x * 4 + c] = static_cast<uint8_t>(y * 16 + x + c);

  // 13 pixels = 52 bytes per row; AssignDirect wants 64.
  REQUIRE(image.Crop(5, 3, 17, 10));
  REQUIRE(image.Width() == 13u);
  REQUIRE(image.Height() == 8u);
  REQUIRE(image.LineSize() == 64u);
  for (int y = 0; y < 8; y++)
    for (int x = 0; x < 13; x++)
      REQUIRE(image.Buffer()[y * image.LineSize() + x * 4] == static_cast<uint8_t>((y + 3) * 16 + (x + 5)));
}

// Regression: a zone alarm highlight (RGB) overlaid onto a planar YUV420P
// analysis image used to call Colourise() — which only handles GRAY8, so it
// bailed with "already colourised, colours: 1" (the GRAY8/YUV420P alias) and
// then walked the 1.5x planar buffer as 3x packed RGB (OOB write). Highlights
// are now built in the target's YUV420P format and Overlay() copies luma plus
// the shared chroma sample, keyed on a non-zero source luma marker.
TEST_CASE("Image::Overlay YUV420P highlight", "[image]") {
  bootstrap_image_config();
  const int w = 64, h = 48;

  Image target(w, h, ZM_COLOUR_GRAY8, ZM_SUBPIX_ORDER_YUV420P);
  Planes tgt = plane_view(target, AV_PIX_FMT_YUV420P, w, h);
  memset(tgt.data[0], 16, static_cast<size_t>(tgt.stride[0]) * h);     // dim luma
  memset(tgt.data[1], 128, static_cast<size_t>(tgt.stride[1]) * (h / 2));  // neutral chroma
  memset(tgt.data[2], 128, static_cast<size_t>(tgt.stride[2]) * (h / 2));

  // Transparent (Y=0) highlight with a 2x2 luma marker fully covering chroma
  // sample (5,5), plus a single-pixel marker partially covering sample (10,10).
  Image high(w, h, ZM_COLOUR_GRAY8, ZM_SUBPIX_ORDER_YUV420P);
  Planes hi = plane_view(high, AV_PIX_FMT_YUV420P, w, h);
  memset(hi.data[0], 0, static_cast<size_t>(hi.stride[0]) * h);
  memset(hi.data[1], 0, static_cast<size_t>(hi.stride[1]) * (h / 2));
  memset(hi.data[2], 0, static_cast<size_t>(hi.stride[2]) * (h / 2));
  for (int y : {10, 11}) for (int x : {10, 11}) hi.data[0][y * hi.stride[0] + x] = 200;
  hi.data[1][5 * hi.stride[1] + 5] = 84;   // red-ish chroma
  hi.data[2][5 * hi.stride[2] + 5] = 255;
  hi.data[0][20 * hi.stride[0] + 20] = 150;  // single covering pixel of sample (10,10)
  hi.data[1][10 * hi.stride[1] + 10] = 43;   // green-ish chroma
  hi.data[2][10 * hi.stride[2] + 10] = 21;

  target.Overlay(high);

  Planes out = plane_view(target, AV_PIX_FMT_YUV420P, w, h);
  // Marked luma copied; unmarked luma untouched.
  for (int y : {10, 11}) for (int x : {10, 11})
    CHECK(out.data[0][y * out.stride[0] + x] == 200);
  CHECK(out.data[0][20 * out.stride[0] + 20] == 150);
  CHECK(out.data[0][0] == 16);
  CHECK(out.data[0][5 * out.stride[0] + 5] == 16);
  // Chroma copied for any covered sample; untouched elsewhere.
  CHECK(out.data[1][5 * out.stride[1] + 5] == 84);
  CHECK(out.data[2][5 * out.stride[2] + 5] == 255);
  CHECK(out.data[1][10 * out.stride[1] + 10] == 43);
  CHECK(out.data[2][10 * out.stride[2] + 10] == 21);
  CHECK(out.data[1][0] == 128);
  CHECK(out.data[2][0] == 128);
}

// Regression: monitors whose decoder emits native YUV420P (the passthrough
// default for H.264/H.265) get an analysis image in YUV420P even when the
// configured Colours is RGB, so the zone alarm highlight arrives as
// RGB24/RGB32. Overlay() used to funnel that combination into Colourise() —
// which warns "Target image is already colourised, colours: 1" and returns —
// then walked the planar YUV buffer as packed RGB, corrupting the saved
// analysis jpegs. The marked (non-black) highlight pixels must instead be
// converted to luma + the shared chroma sample.
TEST_CASE("Image::Overlay RGB highlight onto YUV420P", "[image]") {
  bootstrap_image_config();
  const int w = 64, h = 48;

  Image target(w, h, ZM_COLOUR_GRAY8, ZM_SUBPIX_ORDER_YUV420P);
  Planes tgt = plane_view(target, AV_PIX_FMT_YUV420P, w, h);
  memset(tgt.data[0], 16, static_cast<size_t>(tgt.stride[0]) * h);      // dim luma
  memset(tgt.data[1], 128, static_cast<size_t>(tgt.stride[1]) * (h / 2));  // neutral chroma
  memset(tgt.data[2], 128, static_cast<size_t>(tgt.stride[2]) * (h / 2));

  // Alarm red as it should land in the YUV target.
  const YUV red_yuv = brg_to_yuv(kRGBRed);
  const uint8_t exp_y = Y_VAL(red_yuv), exp_u = U_VAL(red_yuv), exp_v = V_VAL(red_yuv);

  auto check_target = [&]() {
    Planes out = plane_view(target, AV_PIX_FMT_YUV420P, w, h);
    // Marked pixel converted; luma elsewhere untouched.
    CHECK(out.data[0][10 * out.stride[0] + 10] == exp_y);
    CHECK(out.data[0][0] == 16);
    CHECK(out.data[0][10 * out.stride[0] + 11] == 16);
    // Shared chroma sample (5,5) carries the alarm colour; untouched elsewhere.
    CHECK(out.data[1][5 * out.stride[1] + 5] == exp_u);
    CHECK(out.data[2][5 * out.stride[2] + 5] == exp_v);
    CHECK(out.data[1][0] == 128);
    CHECK(out.data[2][0] == 128);
  };

  SECTION("RGB24 highlight") {
    Image high(w, h, ZM_COLOUR_RGB24, ZM_SUBPIX_ORDER_RGB);
    high.Clear();
    uint8_t *p = high.Buffer() + 10 * high.LineSize() + 10 * 3;
    p[0] = 0xff;  // R
    target.Overlay(high);
    check_target();
  }

  SECTION("RGB32 RGBA highlight") {
    Image high(w, h, ZM_COLOUR_RGB32, ZM_SUBPIX_ORDER_RGBA);
    high.Clear();
    uint8_t *p = high.Buffer() + 10 * high.LineSize() + 10 * 4;
    p[0] = 0xff;  // R
    target.Overlay(high);
    check_target();
  }

  SECTION("RGB32 BGRA highlight") {
    Image high(w, h, ZM_COLOUR_RGB32, ZM_SUBPIX_ORDER_BGRA);
    high.Clear();
    uint8_t *p = high.Buffer() + 10 * high.LineSize() + 10 * 4;
    p[2] = 0xff;  // R sits at byte 2 in BGRA
    target.Overlay(high);
    check_target();
  }
}

// HighlightEdges must be able to emit a YUV420P highlight (so it can be
// overlaid onto a YUV420P analysis image in the same format). A filled blob's
// border pixels become non-zero luma markers carrying the alarm chroma.
TEST_CASE("Image::HighlightEdges YUV420P output", "[image]") {
  bootstrap_image_config();
  const int w = 64, h = 48;

  Image src(w, h, ZM_COLOUR_GRAY8, ZM_SUBPIX_ORDER_NONE);
  src.Clear();
  // Fill an interior rectangle so it has edges away from the image border.
  for (int y = 10; y < 30; y++)
    for (int x = 10; x < 40; x++)
      src.Buffer()[y * src.LineSize() + x] = 255;

  Rgb red = kRGBRed;  // alarm colour
  Image *high = src.HighlightEdges(red, ZM_COLOUR_GRAY8, ZM_SUBPIX_ORDER_YUV420P, nullptr);
  REQUIRE(high != nullptr);
  REQUIRE(high->PixFormat() == AV_PIX_FMT_YUV420P);

  Planes hi = plane_view(*high, AV_PIX_FMT_YUV420P, w, h);
  // A border pixel of the blob must carry a non-zero luma marker; an interior
  // pixel (not an edge) and an outside pixel must stay transparent (Y=0).
  CHECK(hi.data[0][10 * hi.stride[0] + 20] != 0);  // top edge
  CHECK(hi.data[0][20 * hi.stride[0] + 25] == 0);  // interior, not an edge
  CHECK(hi.data[0][0] == 0);                        // outside the blob
  delete high;
}

// A File monitor reads its source JPEG into a monitor-sized Image on every
// capture. A file with larger dimensions must get a buffer sized for the file,
// not be decoded into the smaller existing one. refs GHSA-rpp4-xmqm-84ff
TEST_CASE("Image::ReadJpeg reallocates for a larger JPEG", "[image]") {
  bootstrap_image_config();
  const unsigned int big_w = 256, big_h = 192;
  const unsigned int small_w = 64, small_h = 48;

  Image big(big_w, big_h, ZM_COLOUR_RGB32, ZM_SUBPIX_ORDER_RGBA);
  big.Clear();
  char path[] = "/tmp/zm_readjpeg_XXXXXX.jpg";
  int fd = mkstemps(path, 4);
  REQUIRE(fd >= 0);
  close(fd);
  REQUIRE(big.WriteJpeg(path, 90));

  Image small(small_w, small_h, ZM_COLOUR_RGB32, ZM_SUBPIX_ORDER_RGBA);
  const unsigned int small_size = small.Size();
  REQUIRE(small.ReadJpeg(path, ZM_COLOUR_RGB32, ZM_SUBPIX_ORDER_RGBA));
  unlink(path);

  CHECK(small.Width() == big_w);
  CHECK(small.Height() == big_h);
  // The buffer must hold every decoded row at the image's stride.
  CHECK(small.LineSize() >= big_w * 4);
  CHECK(small.Size() >= small.LineSize() * big_h);
  CHECK(small.Size() > small_size);
}


static std::vector<JOCTET> reference_encode_yuv(const Image &image, int quality) {
  const int w = static_cast<int>(image.Width());
  const int h = static_cast<int>(image.Height());
  const AVPixelFormat fmt = image.PixFormat();
  const bool is_422 = (fmt == AV_PIX_FMT_YUV422P || fmt == AV_PIX_FMT_YUVJ422P);
  const bool limited = (fmt == AV_PIX_FMT_YUV420P || fmt == AV_PIX_FMT_YUV422P);

  uint8_t *plane[4] = {nullptr, nullptr, nullptr, nullptr};
  int ls[4] = {0, 0, 0, 0};
  REQUIRE(av_image_fill_arrays(plane, ls, image.Buffer(), fmt, w, h, 32) > 0);

  jpeg_compress_struct cinfo;
  jpeg_error_mgr jerr;
  memset(&cinfo, 0, sizeof(cinfo));
  cinfo.err = jpeg_std_error(&jerr);
  jpeg_create_compress(&cinfo);

  unsigned char *outbuf = nullptr;
  unsigned long outsize = 0;
  jpeg_mem_dest(&cinfo, &outbuf, &outsize);

  cinfo.image_width = w;
  cinfo.image_height = h;
  cinfo.input_components = 3;
  cinfo.in_color_space = JCS_YCbCr;

  jpeg_set_defaults(&cinfo);
  jpeg_set_quality(&cinfo, quality, FALSE);
  cinfo.dct_method = JDCT_FASTEST;

  jpeg_start_compress(&cinfo, TRUE);

  const unsigned chroma_v_shift = is_422 ? 0u : 1u;
  std::vector<uint8_t> tmprow(static_cast<size_t>(w) * 3);
  JSAMPROW row_ptr = tmprow.data();

  while (cinfo.next_scanline < cinfo.image_height) {
    const unsigned y = cinfo.next_scanline;
    const unsigned cy = y >> chroma_v_shift;
    const uint8_t *yrow = plane[0] + static_cast<size_t>(y) * ls[0];
    const uint8_t *urow = plane[1] + static_cast<size_t>(cy) * ls[1];
    const uint8_t *vrow = plane[2] + static_cast<size_t>(cy) * ls[2];
    for (int x = 0; x < w; ++x) {
      int yy = yrow[x], uu = urow[x >> 1], vv = vrow[x >> 1];
      if (limited) {  // expand to the full range JPEG expects
        yy = ((std::clamp(yy, 16, 235) - 16) * 298 + 128) >> 8;
        uu = ((std::clamp(uu, 16, 240) - 16) * 291 + 304) >> 8;
        vv = ((std::clamp(vv, 16, 240) - 16) * 291 + 304) >> 8;
      }
      tmprow[static_cast<size_t>(x) * 3 + 0] = yy;
      tmprow[static_cast<size_t>(x) * 3 + 1] = uu;
      tmprow[static_cast<size_t>(x) * 3 + 2] = vv;
    }
    jpeg_write_scanlines(&cinfo, &row_ptr, 1);
  }

  jpeg_finish_compress(&cinfo);
  jpeg_destroy_compress(&cinfo);

  std::vector<JOCTET> result(outbuf, outbuf + outsize);
  free(outbuf);
  return result;
}

static std::vector<JOCTET> reference_encode_rgb(const Image &image, int quality) {
  const int w = static_cast<int>(image.Width());
  const int h = static_cast<int>(image.Height());

  jpeg_compress_struct cinfo;
  jpeg_error_mgr jerr;
  memset(&cinfo, 0, sizeof(cinfo));
  cinfo.err = jpeg_std_error(&jerr);
  jpeg_create_compress(&cinfo);

  unsigned char *outbuf = nullptr;
  unsigned long outsize = 0;
  jpeg_mem_dest(&cinfo, &outbuf, &outsize);

  cinfo.image_width = w;
  cinfo.image_height = h;
  cinfo.input_components = 3;
  cinfo.in_color_space = JCS_RGB;

  jpeg_set_defaults(&cinfo);
  jpeg_set_quality(&cinfo, quality, FALSE);
  cinfo.dct_method = JDCT_FASTEST;

  jpeg_start_compress(&cinfo, TRUE);

  JSAMPROW row_pointer = const_cast<JSAMPROW>(image.Buffer());
  while (cinfo.next_scanline < cinfo.image_height) {
    jpeg_write_scanlines(&cinfo, &row_pointer, 1);
    row_pointer += image.LineSize();
  }

  jpeg_finish_compress(&cinfo);
  jpeg_destroy_compress(&cinfo);

  std::vector<JOCTET> result(outbuf, outbuf + outsize);
  free(outbuf);
  return result;
}

TEST_CASE("Image::EncodeJpeg raw YUV matches legacy", "[Image]") {
  bootstrap_image_config();
  const int quality = 75;

  struct Case { int w; int h; AVPixelFormat fmt; };
  std::vector<Case> cases = {
    {64, 48, AV_PIX_FMT_YUV420P},
    {64, 37, AV_PIX_FMT_YUV420P},
    {70, 37, AV_PIX_FMT_YUV420P},
    {69, 37, AV_PIX_FMT_YUV420P},
    {72, 40, AV_PIX_FMT_YUV420P},
    {17, 9, AV_PIX_FMT_YUV420P},
    {470, 264, AV_PIX_FMT_YUV420P},
    {633, 356, AV_PIX_FMT_YUV420P},
    {64, 48, AV_PIX_FMT_YUV422P},
  };

  for (auto &c : cases) {
    const bool is_422 = (c.fmt == AV_PIX_FMT_YUV422P || c.fmt == AV_PIX_FMT_YUVJ422P);
    Image img(c.w, c.h, ZM_COLOUR_GRAY8,
              (c.fmt == AV_PIX_FMT_YUV420P || c.fmt == AV_PIX_FMT_YUVJ420P)
                  ? ZM_SUBPIX_ORDER_YUV420P
                  : ZM_SUBPIX_ORDER_YUV422P);

    uint8_t *plane[4] = {nullptr, nullptr, nullptr, nullptr};
    int ls[4] = {0, 0, 0, 0};
    REQUIRE(av_image_fill_arrays(plane, ls, img.Buffer(), c.fmt, c.w, c.h, 32) > 0);
    const int cr = is_422 ? c.h : (c.h + 1) / 2;
    const int cw = (c.w + 1) / 2;
    for (int y = 0; y < c.h; ++y)
      for (int x = 0; x < c.w; ++x)
        plane[0][static_cast<size_t>(y) * ls[0] + x] =
            static_cast<uint8_t>((x * 7 + y * 13) ^ 0x5a);
    for (int y = 0; y < cr; ++y)
      for (int x = 0; x < cw; ++x) {
        plane[1][static_cast<size_t>(y) * ls[1] + x] =
            static_cast<uint8_t>((x * 11 + y * 17) ^ 0x3c);
        plane[2][static_cast<size_t>(y) * ls[2] + x] =
            static_cast<uint8_t>((x * 19 + y * 23) ^ 0x9e);
      }

    std::vector<JOCTET> outbuf(1 << 20);
    size_t outsize = 0;
    REQUIRE(img.EncodeJpeg(outbuf.data(), &outsize, quality));
    std::vector<JOCTET> actual(outbuf.begin(), outbuf.begin() + outsize);

    std::vector<JOCTET> expected = reference_encode_yuv(img, quality);
    INFO("case " << c.w << "x" << c.h);
    REQUIRE(actual == expected);
  }
}

TEST_CASE("Image::EncodeJpeg cached struct no raw leak", "[Image]") {
  bootstrap_image_config();
  const int quality = 75;

  Image yuv(32, 24, ZM_COLOUR_GRAY8, ZM_SUBPIX_ORDER_YUV420P);
  std::vector<JOCTET> buf(1 << 20);
  size_t sz = 0;
  REQUIRE(yuv.EncodeJpeg(buf.data(), &sz, quality));

  Image rgb(32, 24, ZM_COLOUR_RGB24, ZM_SUBPIX_ORDER_RGB);
  for (int y = 0; y < 24; ++y)
    for (int x = 0; x < 32; ++x) {
      uint8_t *p = rgb.Buffer() + static_cast<size_t>(y) * rgb.LineSize() + x * 3;
      p[0] = static_cast<uint8_t>(x * 3 + y);
      p[1] = static_cast<uint8_t>(x + y * 5);
      p[2] = static_cast<uint8_t>(x * 7 ^ y);
    }

  std::vector<JOCTET> outbuf(1 << 20);
  size_t outsize = 0;
  REQUIRE(rgb.EncodeJpeg(outbuf.data(), &outsize, quality));
  std::vector<JOCTET> actual(outbuf.begin(), outbuf.begin() + outsize);
  std::vector<JOCTET> expected = reference_encode_rgb(rgb, quality);
  REQUIRE(actual == expected);
}

TEST_CASE("Image::Fill polygon writes the expected pixels for each density", "[image]") {
  bootstrap_image_config();
  const int w = 16, h = 12;
  // U shape: rows 1-6 have two spans (x 1-4, 8-11), rows 7-9 one span (x 1-11).
  Polygon poly(std::vector<Vector2>{{1, 1}, {4, 1}, {4, 7}, {8, 7}, {8, 1}, {11, 1}, {11, 10}, {1, 10}});

  struct Format { int colours; int order; int bpp; };
  for (Format f : {Format{ZM_COLOUR_GRAY8, ZM_SUBPIX_ORDER_NONE, 1},
                   Format{ZM_COLOUR_RGB24, ZM_SUBPIX_ORDER_RGB, 3},
                   Format{ZM_COLOUR_RGB32, ZM_SUBPIX_ORDER_RGBA, 4}}) {
    for (int density : {1, 2, 3}) {
      Image image(w, h, f.colours, f.order);
      memset(image.Buffer(), 0, image.Size());
      image.Fill(0xFF, density, poly);
      for (int y = 0; y < h; y++) {
        for (int x = 0; x < w; x++) {
          bool inside = y >= 1 && y <= 9 && ((x >= 1 && x <= 4) || (x >= 8 && x <= 11) || (y >= 7 && x >= 1 && x <= 11));
          bool expected = inside && x % density == 0 && y % density == 0;
          REQUIRE((image.Buffer()[y * image.LineSize() + x * f.bpp] != 0) == expected);
        }
      }
    }
  }
}
