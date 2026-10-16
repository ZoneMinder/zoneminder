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

#include <algorithm>
#include <cstring>
#include <memory>
#include <vector>

// The alarmed/filtered pixel check methods used to hand Overlay() the raw GRAY8
// scoring mask. Overlay keys on a non-zero source pixel and copies it, so the
// highlight took whatever shape that byte had in the destination format: the
// red channel on an RGB32 monitor (which is why alarms were "always red"), all
// three channels on RGB24 (white), luma alone on YUV420. The zone's configured
// Alarm Colour was only ever honoured on the blob path, via HighlightEdges.
//
// The highlight must now carry the zone's alarm colour on every check method
// and colour depth, filled for the pixel methods and outlined for blobs.
namespace {

void EnsureConfig() {
  if (!config.font_file_location) config.font_file_location = "";
  if (!config.event_close_mode) config.event_close_mode = "idle";
}

class TestMonitor : public Monitor {
 public:
  TestMonitor(unsigned int w, unsigned int h, unsigned int c) : Monitor() {
    width = w;
    height = h;
    colours = c;
    // >1 is what gates building the highlight at all.
    savejpegs = 2;
  }
};

std::shared_ptr<Monitor> MakeMonitor(unsigned int w, unsigned int h, unsigned int c) {
  return std::static_pointer_cast<Monitor>(std::make_shared<TestMonitor>(w, h, c));
}

Polygon FullFramePolygon(unsigned int w, unsigned int h) {
  std::vector<Vector2> vertices = {Vector2(0, 0), Vector2(w - 1, 0),
                                   Vector2(w - 1, h - 1), Vector2(0, h - 1)};
  return Polygon(vertices);
}

std::unique_ptr<Image> MakeSaturatedDelta(unsigned int w, unsigned int h) {
  auto img = std::unique_ptr<Image>(
      new Image(w, h, ZM_COLOUR_GRAY8, ZM_SUBPIX_ORDER_NONE));
  for (unsigned int y = 0; y < h; y++)
    for (unsigned int x = 0; x < w; x++) *img->Buffer(x, y) = 255;
  return img;
}

// A colour no channel-smearing bug can produce by accident: it is not white,
// not pure red, and its three channels are all different.
constexpr Rgb kAlarmColour = 0x20c0ff;
constexpr uint8_t kR = 0x20, kG = 0xc0, kB = 0xff;

// Monitor::SubpixelOrder() reads through the camera, which a bare test Monitor
// does not have, so which byte holds which channel is not pinned down here.
// Checking the three bytes as a set still fails for every way the old code got
// this wrong: white is {ff,ff,ff}, red-channel-only is {xx,00,00}.
bool ChannelsAre(const uint8_t *px, uint8_t a, uint8_t b, uint8_t c) {
  std::vector<uint8_t> got = {px[0], px[1], px[2]};
  std::vector<uint8_t> want = {a, b, c};
  std::sort(got.begin(), got.end());
  std::sort(want.begin(), want.end());
  return got == want;
}

}  // namespace

TEST_CASE("Zone alarm highlight carries the zone's alarm colour", "[Zone]") {
  EnsureConfig();

  // 720 is deliberately not 32-byte aligned in either depth, so this also
  // exercises the padded-stride path the striping fix covers.
  const unsigned int w = 720, h = 480;

  SECTION("RGB24 monitor: filled with the alarm colour, not white") {
    auto monitor = MakeMonitor(w, h, ZM_COLOUR_RGB24);
    Zone zone(monitor, 1, "full", Zone::ACTIVE, FullFramePolygon(w, h),
              kAlarmColour, Zone::ALARMED_PIXELS,
              /*min_pixel_threshold*/ 10, /*max_pixel_threshold*/ 0,
              /*min_alarm_pixels*/ 1, /*max_alarm_pixels*/ static_cast<int>(w * h));

    auto delta = MakeSaturatedDelta(w, h);
    REQUIRE(zone.CheckAlarms(delta.get()));

    const Image *alarm = zone.AlarmImage();
    REQUIRE(alarm != nullptr);
    REQUIRE(alarm->Colours() == ZM_COLOUR_RGB24);

    const uint8_t *px = alarm->Buffer(w / 2, h / 2);
    CHECK(ChannelsAre(px, kR, kG, kB));
    // The old behaviour wrote the mask byte into all three channels.
    CHECK_FALSE((px[0] == 0xff && px[1] == 0xff && px[2] == 0xff));
  }

  SECTION("RGB32 monitor: alarm colour, not just the red channel") {
    auto monitor = MakeMonitor(w, h, ZM_COLOUR_RGB32);
    Zone zone(monitor, 1, "full", Zone::ACTIVE, FullFramePolygon(w, h),
              kAlarmColour, Zone::ALARMED_PIXELS,
              /*min_pixel_threshold*/ 10, /*max_pixel_threshold*/ 0,
              /*min_alarm_pixels*/ 1, /*max_alarm_pixels*/ static_cast<int>(w * h));

    auto delta = MakeSaturatedDelta(w, h);
    REQUIRE(zone.CheckAlarms(delta.get()));

    const Image *alarm = zone.AlarmImage();
    REQUIRE(alarm != nullptr);
    REQUIRE(alarm->Colours() == ZM_COLOUR_RGB32);

    const uint8_t *px = alarm->Buffer(w / 2, h / 2);
    CHECK(ChannelsAre(px, kR, kG, kB));
    // The old behaviour left two of the three channels at zero.
    CHECK_FALSE((px[1] == 0 && px[2] == 0));
  }

  SECTION("the highlight is filled, not just outlined, for alarmed pixels") {
    auto monitor = MakeMonitor(w, h, ZM_COLOUR_RGB24);
    Zone zone(monitor, 1, "full", Zone::ACTIVE, FullFramePolygon(w, h),
              kAlarmColour, Zone::ALARMED_PIXELS,
              /*min_pixel_threshold*/ 10, /*max_pixel_threshold*/ 0,
              /*min_alarm_pixels*/ 1, /*max_alarm_pixels*/ static_cast<int>(w * h));

    auto delta = MakeSaturatedDelta(w, h);
    REQUIRE(zone.CheckAlarms(delta.get()));

    const Image *alarm = zone.AlarmImage();
    REQUIRE(alarm != nullptr);

    // Every pixel alarmed, so every pixel must be painted -- an outline would
    // leave the interior black.
    unsigned int painted = 0;
    for (unsigned int y = 0; y < h; y++) {
      const uint8_t *row = alarm->Buffer(0, y);
      for (unsigned int x = 0; x < w; x++)
        if (row[x * 3] || row[x * 3 + 1] || row[x * 3 + 2]) painted++;
    }
    CHECK(painted == w * h);
  }
}

// The highlight is built from a GRAY8 mask into a wider destination whose row
// stride is padded independently of the source's. Indexing the destination
// with the source's stride sheared the highlight diagonally across the frame
// and ran past the end of the destination on the last rows -- the same
// width-vs-linesize family as the striping fixes, but inside the highlight
// builder, where it stayed latent until the pixel check methods started using
// it. Run the same assertion at a padded width and an aligned one so a failure
// says whether the stride is to blame.
TEST_CASE("Image::BuildHighlight lands the highlight where the mask marked it", "[Image]") {
  EnsureConfig();

  auto check = [](unsigned int w, unsigned int h) {
    Image mask(w, w, h, ZM_COLOUR_GRAY8, ZM_SUBPIX_ORDER_NONE);
    memset(mask.Buffer(), 0, static_cast<size_t>(w) * h);

    // One solid rectangle, well inside the frame.
    const unsigned int r_lo_x = 100, r_hi_x = 199, r_lo_y = 50, r_hi_y = 149;
    for (unsigned int y = r_lo_y; y <= r_hi_y; y++)
      memset(mask.Buffer() + static_cast<size_t>(y) * w + r_lo_x, 0xff,
             r_hi_x - r_lo_x + 1);

    std::unique_ptr<Image> high(mask.BuildHighlight(
        kAlarmColour, ZM_COLOUR_RGB24, ZM_SUBPIX_ORDER_RGB, nullptr, false));
    REQUIRE(high != nullptr);

    unsigned int painted = 0, first_y = h, last_y = 0, first_x = w, last_x = 0;
    for (unsigned int y = 0; y < h; y++) {
      const uint8_t *row = high->Buffer(0, y);
      for (unsigned int x = 0; x < w; x++) {
        if (row[x * 3] || row[x * 3 + 1] || row[x * 3 + 2]) {
          painted++;
          first_y = std::min(first_y, y);
          last_y = std::max(last_y, y);
          first_x = std::min(first_x, x);
          last_x = std::max(last_x, x);
        }
      }
    }

    INFO("width " << w << " mask linesize " << mask.LineSize()
                  << " highlight linesize " << high->LineSize());
    CHECK(painted == (r_hi_x - r_lo_x + 1) * (r_hi_y - r_lo_y + 1));
    CHECK(first_y == r_lo_y);
    CHECK(last_y == r_hi_y);
    CHECK(first_x == r_lo_x);
    CHECK(last_x == r_hi_x);
  };

  SECTION("720 wide, GRAY8 row padded to 736") { check(720, 480); }
  SECTION("640 wide, GRAY8 row already aligned") { check(640, 480); }
}
