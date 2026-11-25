#include "zm_catch2.h"

#include "zm_config.h"
#include "zm_image.h"
#include "zm_monitor.h"
#include "zm_rgb.h"
#include "zm_zone.h"

#include <climits>
#include <memory>
#include <vector>

namespace {

class TestMonitor : public Monitor {
 public:
  TestMonitor(unsigned int w, unsigned int h) : Monitor() {
    width = w;
    height = h;
    colours = ZM_COLOUR_GRAY8;
    savejpegs = 0;
    ref_image = Image(w, h, ZM_COLOUR_GRAY8, ZM_SUBPIX_ORDER_NONE);
    ref_image.Clear();
  }
};

}  // namespace

// Thresholds of a percentage-coordinate zone are percentages of the zone area.
// The area must be the pixel count of the mask, or 100% is less than the zone.
TEST_CASE("Zone::RasterArea counts the pixels the zone mask covers", "[Zone]") {
  Polygon frame(std::vector<Vector2>{Vector2(0, 0), Vector2(63, 0), Vector2(63, 47), Vector2(0, 47)});
  REQUIRE(frame.Area() == 63 * 47);
  CHECK(Zone::RasterArea(frame, 64, 48) == 64 * 48);

  Polygon collinear(std::vector<Vector2>{Vector2(10, 10), Vector2(30, 10), Vector2(20, 10)});
  CHECK(Zone::RasterArea(collinear, 64, 48) == 21);
}

TEST_CASE("Zone::PercentToPixels converts a percentage and keeps a set value set", "[Zone]") {
  CHECK(Zone::PercentToPixels(0, 3072) == 0);
  CHECK(Zone::PercentToPixels(-1, 3072) == 0);
  CHECK(Zone::PercentToPixels(100, 3072) == 3072);
  CHECK(Zone::PercentToPixels(50, 3072) == 1536);
  // 0.1% of 81 pixels is 0.08, which used to round to 0, meaning no limit
  CHECK(Zone::PercentToPixels(0.1, 81) == 1);
  CHECK(Zone::PercentToPixels(1e12, 3072) == INT_MAX);
}

TEST_CASE("Zone: a 100 percent maximum does not reject a fully changed zone", "[Zone]") {
  if (!config.font_file_location) config.font_file_location = "";
  if (!config.event_close_mode) config.event_close_mode = "idle";
  auto monitor = std::static_pointer_cast<Monitor>(std::make_shared<TestMonitor>(64, 48));
  Polygon frame(std::vector<Vector2>{Vector2(0, 0), Vector2(63, 0), Vector2(63, 47), Vector2(0, 47)});

  Image delta(64, 48, ZM_COLOUR_GRAY8, ZM_SUBPIX_ORDER_NONE);
  for (unsigned int y = 0; y < 48; y++)
    for (unsigned int x = 0; x < 64; x++) *delta.Buffer(x, y) = 255;

  const int max_pixels = Zone::PercentToPixels(100, Zone::RasterArea(frame, 64, 48));
  Zone zone(monitor, 1, "full", Zone::ACTIVE, frame, 0, Zone::ALARMED_PIXELS, 15, 0, 1, max_pixels);
  CHECK(zone.CheckAlarms(&delta));
}

// A zone's score is a percentage of its area. The geometric polygon area leaves
// out the edge pixels the mask includes, so a fully changed full-frame zone
// scored 103.
TEST_CASE("Zone: a fully alarmed zone scores 100, not more", "[Zone]") {
  if (!config.font_file_location) config.font_file_location = "";
  if (!config.event_close_mode) config.event_close_mode = "idle";
  auto monitor = std::static_pointer_cast<Monitor>(std::make_shared<TestMonitor>(64, 48));
  Polygon frame(std::vector<Vector2>{Vector2(0, 0), Vector2(63, 0), Vector2(63, 47), Vector2(0, 47)});
  Image delta(64, 48, ZM_COLOUR_GRAY8, ZM_SUBPIX_ORDER_NONE);
  for (unsigned int y = 0; y < 48; y++)
    for (unsigned int x = 0; x < 64; x++) *delta.Buffer(x, y) = 255;

  Zone zone(monitor, 1, "full", Zone::ACTIVE, frame, 0, Zone::ALARMED_PIXELS, 15, 0, 1, 0);
  REQUIRE(zone.CheckAlarms(&delta));
  REQUIRE(zone.GetStats().alarm_pixels_ == 64 * 48);
  CHECK(zone.Score() == 100);
}
