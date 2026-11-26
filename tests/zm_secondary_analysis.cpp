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
#include <vector>

// AnalysisSource=Secondary runs motion detection on the substream at its native
// size, which is smaller than the frame. Zones are rebuilt at that size while
// what masks or is drawn on the captured frame stays at frame size.
namespace {

class AnalysisMonitor : public Monitor {
 public:
  AnalysisMonitor(unsigned int w, unsigned int h) : Monitor() {
    width = w;
    height = h;
    colours = ZM_COLOUR_GRAY8;
    savejpegs = 0;
  }
  void SetAnalysisSize(unsigned int w, unsigned int h) {
    analysis_width = w;
    analysis_height = h;
  }
  bool LoadZone(const std::vector<const char *> &row) {
    return Zone::LoadRow(shared_from_this(), row.data(), zones);
  }
  const unsigned char *PrivacyMask() const { return privacy_bitmask; }
  void SetSecondaryAnalysis(bool on) { secondary_analysis = on; }
  void SeedReference(unsigned int w, unsigned int h) {
    ref_image = Image(w, h, ZM_COLOUR_GRAY8, ZM_SUBPIX_ORDER_NONE);
    ref_image.Clear();
  }
  // The zones hold the monitor; drop them so neither leaks.
  void ClearZones() { zones.clear(); }
};

std::shared_ptr<AnalysisMonitor> MakeMonitor(unsigned int w, unsigned int h) {
  if (!config.font_file_location) config.font_file_location = "";
  if (!config.event_close_mode) config.event_close_mode = "idle";
  return std::make_shared<AnalysisMonitor>(w, h);
}

}  // namespace

TEST_CASE("Monitor: the analysis size is the frame size until one is set", "[Monitor][SecondaryAnalysis]") {
  auto monitor = MakeMonitor(64, 48);
  REQUIRE(monitor->AnalysisWidth() == 64);
  REQUIRE(monitor->AnalysisHeight() == 48);

  monitor->SetAnalysisSize(32, 24);
  REQUIRE(monitor->AnalysisWidth() == 32);
  REQUIRE(monitor->AnalysisHeight() == 24);
}

namespace {

// A row in Zone::kLoadColumns order: ALARMED_PIXELS with the given thresholds.
std::vector<const char *> Row(const char *type, const char *coords, const char *min_alarm,
                              const char *min_filter, const char *min_blob) {
  return {"7", "Test", type, "Pixels", coords, "16711680", "1",
          "25", "0", min_alarm, "0",
          "3", "3", min_filter, "0",
          min_blob, "0", "0", "0",
          "0", "0"};
}

void FillBox(Image &image, unsigned int x0, unsigned int y0, unsigned int x1, unsigned int y1) {
  for (unsigned int y = y0; y <= y1; y++)
    for (unsigned int x = x0; x <= x1; x++)
      *image.Buffer(x, y) = 255;
}

bool Within(const Polygon &polygon, unsigned int w, unsigned int h) {
  for (const Vector2 &v : polygon.GetVertices()) {
    if (v.x_ < 0 || v.y_ < 0 || v.x_ >= static_cast<int32>(w) || v.y_ >= static_cast<int32>(h))
      return false;
  }
  return true;
}

}  // namespace

// Legacy zones store integer frame pixels. Rasterised unscaled into the smaller
// analysis-sized mask they wrote past its end (Image::Fill does not clip).
TEST_CASE("Zone::LoadRow: pixel coords are scaled to the analysis size", "[Zone][SecondaryAnalysis]") {
  auto monitor = MakeMonitor(64, 48);
  monitor->SetAnalysisSize(32, 24);
  std::vector<Zone> zones;

  SECTION("a whole-frame zone covers the whole analysis image") {
    auto row = Row("1", "0,0 63,0 63,47 0,47", "100", "50", "20");
    REQUIRE(Zone::LoadRow(monitor, row.data(), zones));
    const Zone &zone = zones[0];
    REQUIRE(Within(zone.GetPolygon(), 32, 24));
    REQUIRE(zone.getPgImage()->Width() == 32);
    REQUIRE(zone.getPgImage()->Height() == 24);
    REQUIRE(Zone::RasterArea(zone.GetPolygon(), 32, 24) == 32 * 24);
    // The frame copy keeps the stored coordinates.
    REQUIRE(zone.GetFramePolygon().Extent().Hi().x_ == 63);
    REQUIRE(zone.GetFramePolygon().Extent().Hi().y_ == 47);
  }

  SECTION("pixel-count thresholds shrink with the area") {
    auto row = Row("1", "0,0 63,0 63,47 0,47", "100", "50", "2");
    REQUIRE(Zone::LoadRow(monitor, row.data(), zones));
    REQUIRE(zones[0].MinAlarmPixels() == 25);
    REQUIRE(zones[0].MinFilterPixels() == 13);
    // A threshold that was set does not round down to 0, which means unset.
    REQUIRE(zones[0].MinBlobPixels() == 1);
  }

  SECTION("coords outside the frame are clamped, then scaled") {
    auto row = Row("1", "0,0 200,0 200,100 0,100", "0", "0", "0");
    REQUIRE(Zone::LoadRow(monitor, row.data(), zones));
    REQUIRE(Within(zones[0].GetPolygon(), 32, 24));
    REQUIRE(Within(zones[0].GetFramePolygon(), 64, 48));
  }

  SECTION("the scaled zone covers the same part of the picture") {
    // The right half of the frame is the right half of the analysis image.
    auto row = Row("1", "32,0 63,0 63,47 32,47", "4", "0", "0");
    REQUIRE(Zone::LoadRow(monitor, row.data(), zones));
    Zone &zone = zones[0];

    Image left(32, 24, ZM_COLOUR_GRAY8, ZM_SUBPIX_ORDER_NONE);
    left.Clear();
    FillBox(left, 4, 10, 7, 13);
    REQUIRE_FALSE(zone.CheckAlarms(&left));

    zone.ResetStats();
    Image right(32, 24, ZM_COLOUR_GRAY8, ZM_SUBPIX_ORDER_NONE);
    right.Clear();
    FillBox(right, 24, 10, 27, 13);
    REQUIRE(zone.CheckAlarms(&right));
    REQUIRE(zone.GetStats().alarm_pixels_ == 16);
  }
}

TEST_CASE("Zone::LoadRow: percent coords are parsed at the analysis size", "[Zone][SecondaryAnalysis]") {
  auto monitor = MakeMonitor(64, 48);
  monitor->SetAnalysisSize(32, 24);
  std::vector<Zone> zones;
  auto row = Row("1", "50.00,0.00 100.00,0.00 100.00,100.00 50.00,100.00", "10", "0", "0");
  REQUIRE(Zone::LoadRow(monitor, row.data(), zones));
  const Zone &zone = zones[0];
  REQUIRE(Within(zone.GetPolygon(), 32, 24));
  REQUIRE(zone.GetPolygon().Extent().Lo().x_ == 16);
  REQUIRE(zone.GetFramePolygon().Extent().Lo().x_ == 32);
  // Percent thresholds are of the area the zone covers at the analysis size.
  const int zpa = Zone::RasterArea(zone.GetPolygon(), 32, 24);
  REQUIRE(zone.MinAlarmPixels() == Zone::PercentToPixels(10, zpa));
}

TEST_CASE("Zone::LoadRow: at the frame size nothing is scaled", "[Zone][SecondaryAnalysis]") {
  auto monitor = MakeMonitor(64, 48);
  std::vector<Zone> zones;
  auto row = Row("1", "32,0 63,0 63,47 32,47", "100", "50", "20");
  REQUIRE(Zone::LoadRow(monitor, row.data(), zones));
  REQUIRE(zones[0].GetPolygon().Extent().Lo().x_ == 32);
  REQUIRE(zones[0].GetPolygon().Extent().Hi().x_ == 63);
  REQUIRE(zones[0].MinAlarmPixels() == 100);
  REQUIRE(zones[0].MinFilterPixels() == 50);
  REQUIRE(zones[0].MinBlobPixels() == 20);
}

TEST_CASE("Zone::ScalePolygon and ScalePixelCount", "[Zone][SecondaryAnalysis]") {
  Polygon frame(std::vector<Vector2>{Vector2(0, 0), Vector2(1279, 0), Vector2(1279, 719), Vector2(0, 719)});
  Polygon scaled = Zone::ScalePolygon(frame, 1280, 720, 640, 360);
  REQUIRE(scaled.Extent().Hi().x_ == 639);
  REQUIRE(scaled.Extent().Hi().y_ == 359);
  REQUIRE(scaled.Extent().Lo().x_ == 0);

  REQUIRE(Zone::ScalePixelCount(0, 0.25) == 0);
  REQUIRE(Zone::ScalePixelCount(1000, 0.25) == 250);
  REQUIRE(Zone::ScalePixelCount(1, 0.25) == 1);
}

// MaskPrivacy applies the mask to the captured frame, so it is built from the
// zones' frame coordinates even when they were loaded at the analysis size.
TEST_CASE("Monitor::AddPrivacyBitmask: zones at the analysis size mask the frame", "[Monitor][SecondaryAnalysis]") {
  auto monitor = MakeMonitor(64, 48);
  monitor->SetAnalysisSize(32, 24);
  REQUIRE(monitor->LoadZone(Row("6", "32,0 63,0 63,47 32,47", "0", "0", "0")));
  monitor->AddPrivacyBitmask();
  const unsigned char *mask = monitor->PrivacyMask();
  REQUIRE(mask != nullptr);
  REQUIRE(mask[20 * 64 + 40] == 0xff);
  REQUIRE(mask[47 * 64 + 63] == 0xff);
  REQUIRE(mask[20 * 64 + 20] == 0);
  REQUIRE(mask[20 * 64 + 31] == 0);
  monitor->ClearZones();
}

// The frame is privacy-masked at capture, before analysis sees it; the substream
// is not, so its privacy areas must not score.
TEST_CASE("Monitor::DetectMotion: privacy zones do not score on the substream", "[Monitor][SecondaryAnalysis]") {
  auto monitor = MakeMonitor(64, 48);
  monitor->SetAnalysisSize(32, 24);
  monitor->SetSecondaryAnalysis(true);
  REQUIRE(monitor->LoadZone(Row("1", "0,0 63,0 63,47 0,47", "1", "0", "0")));
  REQUIRE(monitor->LoadZone(Row("6", "32,0 63,0 63,47 32,47", "0", "0", "0")));
  monitor->SeedReference(32, 24);

  Event::StringSet zoneSet;
  Image comp(32, 24, ZM_COLOUR_GRAY8, ZM_SUBPIX_ORDER_NONE);
  comp.Clear();
  FillBox(comp, 24, 10, 27, 13);
  REQUIRE(monitor->DetectMotion(comp, zoneSet) == 0);

  comp.Clear();
  FillBox(comp, 4, 10, 7, 13);
  REQUIRE(monitor->DetectMotion(comp, zoneSet) > 0);
  monitor->ClearZones();
}
