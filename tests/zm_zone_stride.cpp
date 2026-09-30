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
#include "zm_monitor.h"
#include "zm_rgb.h"
#include "zm_zone.h"

#include <memory>

// Motion analysis on a portrait monitor (refs #4983). ZM allocates images with
// linesize = FFALIGN(width, 32). Every width in the CaptureResolution dropdown
// (1920, 1280, 2560, 1440, 704, 640, ...) is already a multiple of 32, so
// linesize == width and any code that uses width as a row stride works by
// accident. A portrait monitor is 1080 wide; FFALIGN(1080,32) == 1088, so it is
// the first common config where linesize != width and those paths are exercised
// for real. Each case below runs the same assertion at a padded width and at an
// aligned width, so a failure tells us whether the stride is the culprit or
// whether the geometry is wrong independent of it.
namespace {

// No zm.conf is loaded under the test harness, so config strings are null.
// Image::Initialise() dereferences config.font_file_location and the Monitor
// constructor strcmp()s config.event_close_mode; both segfault on null.
void EnsureConfig() {
  if (!config.font_file_location) config.font_file_location = "";
  if (!config.event_close_mode) config.event_close_mode = "idle";
}

// Monitor's width/height are protected and normally come from the database.
// Expose them so a zone can be set up against arbitrary dimensions without a DB.
class TestMonitor : public Monitor {
 public:
  TestMonitor(unsigned int w, unsigned int h) : Monitor() {
    width = w;
    height = h;
    colours = ZM_COLOUR_GRAY8;
    // >1 is what gates the blob/filter masking path in Zone::CheckAlarms.
    savejpegs = 2;
  }
};

std::shared_ptr<Monitor> MakeMonitor(unsigned int w, unsigned int h) {
  return std::static_pointer_cast<Monitor>(std::make_shared<TestMonitor>(w, h));
}

// Rectangle covering the whole frame, in pixel coordinates.
Polygon FullFramePolygon(unsigned int w, unsigned int h) {
  std::vector<Vector2> vertices = {
      Vector2(0, 0),
      Vector2(w - 1, 0),
      Vector2(w - 1, h - 1),
      Vector2(0, h - 1),
  };
  return Polygon(vertices);
}

// A GRAY8 delta image where every pixel reads as maximum difference, written
// through Buffer(x, y) so the fill itself is stride-correct regardless of
// padding.
std::unique_ptr<Image> MakeSaturatedDelta(unsigned int w, unsigned int h) {
  auto img = std::unique_ptr<Image>(new Image(w, h, ZM_COLOUR_GRAY8, ZM_SUBPIX_ORDER_NONE));
  for (unsigned int y = 0; y < h; y++)
    for (unsigned int x = 0; x < w; x++)
      *img->Buffer(x, y) = 255;
  return img;
}

}  // namespace

TEST_CASE("Image: a 1080-wide GRAY8 image is stride-padded, a 1920-wide one is not", "[Zone]") {
  EnsureConfig();

  // This is the premise the rest of the cases rest on. If FFALIGN ever stops
  // padding 1080, these tests stop testing what they claim to.
  Image portrait(1080, 1920, ZM_COLOUR_GRAY8, ZM_SUBPIX_ORDER_NONE);
  REQUIRE(portrait.Width() == 1080);
  REQUIRE(portrait.LineSize() == 1088);

  Image landscape(1920, 1080, ZM_COLOUR_GRAY8, ZM_SUBPIX_ORDER_NONE);
  REQUIRE(landscape.Width() == 1920);
  REQUIRE(landscape.LineSize() == 1920);
}

// refs #4983. DumpImgBuffer() nulls buffer but leaves
// width/height/colours/subpixelorder/linesize intact, so Delta's "different
// sized images" guard still passes and, without a buffer check, it walks the
// delta helpers from a null base: buffer + 0*linesize is address 0 on the first
// row. That reproduced the reported "Fault address: (nil)" inside the gray8
// delta helper. Delta must reject a null buffer on either operand instead of
// dereferencing it. (The originating race — the capture thread dumping
// ref_image under the analysis thread — is fixed separately in Monitor; this is
// the defensive backstop, and the only half unit-testable without a live race.)
TEST_CASE("Image::Delta rejects an operand whose buffer has been dumped", "[Image]") {
  EnsureConfig();

  auto make = []() { return Image(1080, 1920, ZM_COLOUR_GRAY8, ZM_SUBPIX_ORDER_NONE); };

  SECTION("source (this) buffer dumped") {
    Image ref = make();
    Image comp = make();
    Image delta;

    ref.DumpImgBuffer();

    // Dimensions survive the dump, which is exactly why the size check is no
    // protection here.
    REQUIRE(ref.Buffer() == nullptr);
    REQUIRE(ref.Width() == comp.Width());
    REQUIRE(ref.Height() == comp.Height());
    REQUIRE(ref.Colours() == comp.Colours());

    REQUIRE(ref.Delta(comp, &delta) == false);
  }

  SECTION("comparison (other) buffer dumped") {
    Image ref = make();
    Image comp = make();
    Image delta;

    comp.DumpImgBuffer();

    REQUIRE(ref.Delta(comp, &delta) == false);
  }
}

TEST_CASE("Zone::Setup derives per-row ranges correctly at a padded width", "[Zone]") {
  EnsureConfig();

  auto check = [](unsigned int w, unsigned int h) {
    auto monitor = MakeMonitor(w, h);
    Zone zone(monitor, 1, "full", Zone::ACTIVE, FullFramePolygon(w, h), kRGBRed,
              Zone::ALARMED_PIXELS,
              /*min_pixel_threshold*/ 10, /*max_pixel_threshold*/ 0,
              /*min_alarm_pixels*/ 1, /*max_alarm_pixels*/ static_cast<int>(w * h));

    const Image *pg = zone.getPgImage();
    REQUIRE(pg != nullptr);
    REQUIRE(pg->Width() == w);
    REQUIRE(pg->Height() == h);

    // Setup() scans pg_image row by row to find each row's polygon extent. A
    // full-frame polygon must mark every row from column 0 to the last column.
    // A stride bug shows as a wrong extent on the rows past the padding
    // boundary, so sample the first row, the last row, and a spread in between
    // rather than asserting on all ~2000 (which bloats the suite and hides the
    // failing row). Zone::Range is protected; getRanges() hands it out publicly,
    // so let the type be deduced rather than naming it.
    const auto *ranges = zone.getRanges();
    REQUIRE(ranges != nullptr);
    for (unsigned int y : {0u, 1u, h / 2, h - 2, h - 1}) {
      INFO("row y=" << y << " at " << w << "x" << h);
      REQUIRE(ranges[y].lo_x == 0);
      REQUIRE(ranges[y].hi_x == static_cast<int>(w - 1));
    }
  };

  SECTION("1080x1920 portrait (linesize 1088 != width 1080)") {
    check(1080, 1920);
  }
  SECTION("1920x1080 landscape (linesize == width)") {
    check(1920, 1080);
  }
}

// ALARMED_PIXELS returns before the filter and blob stages. Real monitors
// commonly run BLOBS with SaveJPEGs>1, which additionally walks the diff buffer
// for filtering, runs blob detection, and builds an alarm highlight image.
TEST_CASE("Zone::CheckAlarms survives the blob path on a full-frame delta", "[Zone]") {
  EnsureConfig();

  auto check = [](unsigned int w, unsigned int h) {
    const int frame_pixels = static_cast<int>(w * h);
    auto monitor = MakeMonitor(w, h);
    // The filter/blob maxima default to values far below a full frame (50000),
    // which would reject this delta before the code under test runs. Size every
    // limit to the frame so the alarm survives to the blob stage.
    Zone zone(monitor, 1, "full", Zone::ACTIVE, FullFramePolygon(w, h), kRGBRed,
              Zone::BLOBS,
              /*min_pixel_threshold*/ 10, /*max_pixel_threshold*/ 0,
              /*min_alarm_pixels*/ 1, /*max_alarm_pixels*/ frame_pixels,
              /*filter_box*/ Vector2(3, 3),
              /*min_filter_pixels*/ 1, /*max_filter_pixels*/ frame_pixels,
              /*min_blob_pixels*/ 1, /*max_blob_pixels*/ 0,
              /*min_blobs*/ 1, /*max_blobs*/ 0);

    std::unique_ptr<Image> delta = MakeSaturatedDelta(w, h);

    REQUIRE(zone.CheckAlarms(delta.get()) == true);
    REQUIRE(zone.GetStats().alarm_pixels_ == w * h);
  };

  SECTION("1080x1920 portrait (linesize 1088 != width 1080)") {
    check(1080, 1920);
  }
  SECTION("1920x1080 landscape (linesize == width)") {
    check(1920, 1080);
  }
}

TEST_CASE("Zone::CheckAlarms counts every pixel of a saturated full-frame delta", "[Zone]") {
  EnsureConfig();

  auto check = [](unsigned int w, unsigned int h) {
    auto monitor = MakeMonitor(w, h);
    Zone zone(monitor, 1, "full", Zone::ACTIVE, FullFramePolygon(w, h), kRGBRed,
              Zone::ALARMED_PIXELS,
              /*min_pixel_threshold*/ 10, /*max_pixel_threshold*/ 0,
              /*min_alarm_pixels*/ 1, /*max_alarm_pixels*/ static_cast<int>(w * h));

    std::unique_ptr<Image> delta = MakeSaturatedDelta(w, h);

    REQUIRE(zone.CheckAlarms(delta.get()) == true);
    // Every pixel is above threshold and every pixel is inside the polygon, so
    // the alarmed count must be the whole frame. A stride mismatch shows up
    // here as a short count.
    REQUIRE(zone.GetStats().alarm_pixels_ == w * h);
  };

  SECTION("1080x1920 portrait (linesize 1088 != width 1080)") {
    check(1080, 1920);
  }
  SECTION("1920x1080 landscape (linesize == width)") {
    check(1920, 1080);
  }
}
