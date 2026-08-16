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

extern "C" {
#include <libavutil/imgutils.h>
}

#include <cstdlib>
#include <cstring>

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
