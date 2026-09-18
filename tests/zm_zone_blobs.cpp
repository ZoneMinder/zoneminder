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

#include "zm_monitor.h"
#include "zm_zone.h"

#include <memory>
#include <vector>

// Zone::CheckAlarms labels blobs with a table of 254 tags. When the table runs
// out it recycles a tag from a blob that has finished and failed its size
// limits, and -- only when the monitor is saving analysis jpegs -- erases that
// blob's pixels from the mask on the way out. These cover what a frame busy
// enough to exhaust the table does to the mask and to the score.

namespace {

constexpr int kWidth = 720;
constexpr int kHeight = 480;

void EnsureConfig() {
  if (!config.font_file_location) config.font_file_location = "";
  if (!config.event_close_mode) config.event_close_mode = "idle";
}

class TestMonitor : public Monitor {
 public:
  TestMonitor(unsigned int w, unsigned int h, int p_savejpegs) : Monitor() {
    width = w;
    height = h;
    colours = ZM_COLOUR_GRAY8;
    savejpegs = p_savejpegs;
  }
};

std::shared_ptr<Monitor> MakeMonitor(int savejpegs) {
  return std::static_pointer_cast<Monitor>(
      std::make_shared<TestMonitor>(kWidth, kHeight, savejpegs));
}

Polygon FullFramePolygon() {
  std::vector<Vector2> vertices = {
      Vector2(0, 0), Vector2(kWidth - 1, 0),
      Vector2(kWidth - 1, kHeight - 1), Vector2(0, kHeight - 1),
  };
  return Polygon(vertices);
}

// A delta image holding many small separated squares: every one is its own
// blob, so the count is what drives the tag table.
std::unique_ptr<Image> MakeSpeckledDelta(int squares_per_row, int rows, int square) {
  auto img = std::unique_ptr<Image>(
      new Image(kWidth, kHeight, ZM_COLOUR_GRAY8, ZM_SUBPIX_ORDER_NONE));
  img->Clear();
  const int step_x = kWidth / (squares_per_row + 1);
  const int step_y = kHeight / (rows + 1);
  for (int r = 0; r < rows; r++) {
    for (int c = 0; c < squares_per_row; c++) {
      const int x0 = step_x / 2 + c * step_x;
      const int y0 = step_y / 2 + r * step_y;
      for (int y = y0; y < y0 + square && y < kHeight; y++)
        for (int x = x0; x < x0 + square && x < kWidth; x++) *img->Buffer(x, y) = 255;
    }
  }
  return img;
}

std::unique_ptr<Zone> MakeBlobZone(const std::shared_ptr<Monitor> &monitor,
                                   int min_blob_pixels) {
  return std::unique_ptr<Zone>(new Zone(
      monitor, 1, "blobs", Zone::ACTIVE, FullFramePolygon(), kRGBRed, Zone::BLOBS,
      /*min_pixel_threshold*/ 20, /*max_pixel_threshold*/ 0,
      /*min_alarm_pixels*/ 1, /*max_alarm_pixels*/ 0,
      /*filter_box*/ Vector2(1, 1), /*min_filter_pixels*/ 1, /*max_filter_pixels*/ 0,
      min_blob_pixels, /*max_blob_pixels*/ 0,
      /*min_blobs*/ 1, /*max_blobs*/ 0, /*overload*/ 0, /*extend*/ 0));
}

}  // namespace

// A frame with more blobs than the table can hold is the case the recycling
// path exists for. What it must not do is depend on whether jpegs are being
// saved: that is a storage setting, and it has no business changing how many
// blobs the zone reports or what it scores.
TEST_CASE("Zone blobs: the score does not depend on saving jpegs", "[Zone][blobs]") {
  EnsureConfig();

  // 24 x 14 = 336 squares, comfortably past the 254 tags available.
  auto delta_a = MakeSpeckledDelta(24, 14, 3);
  auto delta_b = MakeSpeckledDelta(24, 14, 3);

  auto monitor_plain = MakeMonitor(1);   // no analysis jpegs
  auto monitor_jpegs = MakeMonitor(2);   // analysis jpegs, so the erase runs

  auto zone_plain = MakeBlobZone(monitor_plain, 20);
  auto zone_jpegs = MakeBlobZone(monitor_jpegs, 20);

  zone_plain->CheckAlarms(delta_a.get());
  zone_jpegs->CheckAlarms(delta_b.get());

  const ZoneStats &plain = zone_plain->GetStats();
  const ZoneStats &jpegs = zone_jpegs->GetStats();

  WARN("savejpegs=1: blobs=" << plain.alarm_blobs_ << " blob_pixels=" << plain.alarm_blob_pixels_
       << " score=" << plain.score_);
  WARN("savejpegs=2: blobs=" << jpegs.alarm_blobs_ << " blob_pixels=" << jpegs.alarm_blob_pixels_
       << " score=" << jpegs.score_);

  CHECK(jpegs.alarm_blobs_ == plain.alarm_blobs_);
  CHECK(jpegs.alarm_blob_pixels_ == plain.alarm_blob_pixels_);
  CHECK(jpegs.score_ == plain.score_);
}

// Every square here is identical and above the minimum, so none of them may be
// eliminated: whatever the tag table does, a blob that passes its size limits
// has to survive.
TEST_CASE("Zone blobs: blobs above the minimum all survive", "[Zone][blobs]") {
  EnsureConfig();

  auto delta = MakeSpeckledDelta(24, 14, 4);  // 336 blobs of 16 pixels each
  auto monitor = MakeMonitor(2);
  auto zone = MakeBlobZone(monitor, 4);       // 16 >= 4, so all qualify

  zone->CheckAlarms(delta.get());
  const ZoneStats &stats = zone->GetStats();

  WARN("336 qualifying blobs -> reported " << stats.alarm_blobs_
       << " blobs, " << stats.alarm_blob_pixels_ << " pixels");

  CHECK(stats.alarm_blob_pixels_ == 336 * 16);
  CHECK(stats.alarm_blobs_ == 336);
}

// The overlay draws whatever the mask holds, so the mask has to agree with the
// blobs the zone counted. When the label table ran out mid-frame the scan was
// abandoned, leaving the rows below it marked but unaccounted for, and the
// eliminated blobs above it erased only when jpegs were being written: the
// alarm image came out striped.
TEST_CASE("Zone blobs: the mask marks exactly the blobs that were counted", "[Zone][blobs]") {
  EnsureConfig();

  // Enough blobs to exhaust a 254 entry table well before the frame ends.
  auto delta = MakeSpeckledDelta(24, 14, 4);
  // savejpegs=1 so AlarmImage() is still the mask: above 1 CheckAlarms replaces
  // it with the RGB highlight built from it.
  auto monitor = MakeMonitor(1);
  auto zone = MakeBlobZone(monitor, 20);  // 16 pixel blobs, so all are eliminated

  REQUIRE_FALSE(zone->CheckAlarms(delta.get()));
  const ZoneStats &stats = zone->GetStats();

  // AlarmImage() is the mask once CheckAlarms has finished with it. Count what
  // it actually marks.
  const Image *mask = zone->AlarmImage();
  REQUIRE(mask != nullptr);
  REQUIRE(mask->Colours() == 1);

  unsigned int marked = 0;
  for (unsigned int y = 0; y < mask->Height(); y++) {
    const uint8_t *row = mask->Buffer(0, y);
    for (unsigned int x = 0; x < mask->Width(); x++) if (row[x]) marked++;
  }

  // Every blob failed the minimum, so the zone counted none and the mask must
  // show none. The old code only erased them when it was writing jpegs, so the
  // overlay kept drawing blobs the zone had already discounted.
  WARN("mask marks " << marked << " pixels, zone counted " << stats.alarm_blob_pixels_);
  CHECK(stats.alarm_blob_pixels_ == 0);
  CHECK(marked == 0);
}
