#include "zm_catch2.h"

#include "zm_config.h"
#include "zm_image.h"
#include "zm_monitor.h"
#include "zm_rgb.h"
#include "zm_zone.h"

#include <memory>
#include <vector>

// A filter width or height below 1 turns the filter box off. It used to run the
// box test with a zero dimension, which no pixel can satisfy, so every pixel
// was filtered out and the zone never alarmed.
namespace {

class TestMonitor : public Monitor {
 public:
  TestMonitor(unsigned int w, unsigned int h) : Monitor() {
    width = w;
    height = h;
    colours = ZM_COLOUR_GRAY8;
    savejpegs = 0;
  }
};

}  // namespace

TEST_CASE("Zone: a filter box dimension below 1 disables the filter", "[Zone]") {
  if (!config.font_file_location) config.font_file_location = "";
  if (!config.event_close_mode) config.event_close_mode = "idle";
  auto monitor = std::static_pointer_cast<Monitor>(std::make_shared<TestMonitor>(64, 48));
  Polygon polygon(std::vector<Vector2>{Vector2(0, 0), Vector2(63, 0), Vector2(63, 47), Vector2(0, 47)});
  Image delta(64, 48, ZM_COLOUR_GRAY8, ZM_SUBPIX_ORDER_NONE);
  for (unsigned int y = 0; y < 48; y++)
    for (unsigned int x = 0; x < 64; x++)
      *delta.Buffer(x, y) = 255;

  for (Zone::CheckMethod method : {Zone::FILTERED_PIXELS, Zone::BLOBS}) {
    for (const Vector2 &box : {Vector2(0, 0), Vector2(1, 1), Vector2(0, 3), Vector2(3, 0), Vector2(-1, 3), Vector2(3, 3)}) {
      CAPTURE(method, box.x_, box.y_);
      Zone zone(monitor, 1, "filter", Zone::ACTIVE, polygon, 0, method,
                15, 0, 1, 0, box, 0, 0, 0, 0, 0, 0);
      REQUIRE(zone.CheckAlarms(&delta));
      REQUIRE(zone.GetStats().alarm_filter_pixels_ == 64 * 48);
    }
  }
}

TEST_CASE("Zone: a 3x3 filter box still removes isolated pixels", "[Zone]") {
  if (!config.font_file_location) config.font_file_location = "";
  if (!config.event_close_mode) config.event_close_mode = "idle";
  auto monitor = std::static_pointer_cast<Monitor>(std::make_shared<TestMonitor>(64, 48));
  Polygon polygon(std::vector<Vector2>{Vector2(0, 0), Vector2(63, 0), Vector2(63, 47), Vector2(0, 47)});
  Image delta(64, 48, ZM_COLOUR_GRAY8, ZM_SUBPIX_ORDER_NONE);
  for (unsigned int y = 0; y < 48; y++)
    for (unsigned int x = 0; x < 64; x++)
      *delta.Buffer(x, y) = 0;
  *delta.Buffer(5, 5) = 255;
  for (unsigned int y = 20; y < 23; y++)
    for (unsigned int x = 30; x < 33; x++)
      *delta.Buffer(x, y) = 255;

  Zone zone(monitor, 1, "filter", Zone::ACTIVE, polygon, 0, Zone::FILTERED_PIXELS,
            15, 0, 1, 0, Vector2(3, 3), 0, 0, 0, 0, 0, 0);
  REQUIRE(zone.CheckAlarms(&delta));
  REQUIRE(zone.GetStats().alarm_pixels_ == 10);
  REQUIRE(zone.GetStats().alarm_filter_pixels_ == 9);
}
