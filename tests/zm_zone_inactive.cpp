#include "zm_catch2.h"

#include "zm_config.h"
#include "zm_image.h"
#include "zm_monitor.h"
#include "zm_rgb.h"
#include "zm_zone.h"

#include <memory>
#include <vector>

// An Inactive zone is blanked out of the delta image before the other zones are
// scored, but only with Fill(), which leaves the polygon's bottom edge. Zones
// rasterise their mask with Fill() plus Outline(), so the edge pixels of an
// Inactive zone still counted as motion.
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

  void AddZone(Zone::ZoneType type, const Polygon &polygon) {
    zones.emplace_back(shared_from_this(), zones.size() + 1, "zone", type, polygon, 0,
                       Zone::ALARMED_PIXELS, 15, 0, 1, 0);
  }

  unsigned int ActiveAlarmPixels() const { return zones[0].GetStats().alarm_pixels_; }
};

unsigned int RasterCount(const std::shared_ptr<Monitor> &monitor, const Polygon &polygon) {
  Zone zone(monitor, 99, "mask", Zone::ACTIVE, polygon, 0, Zone::ALARMED_PIXELS, 15, 0, 1, 0);
  const Image *pg = zone.getPgImage();
  unsigned int n = 0;
  for (unsigned int y = 0; y < pg->Height(); y++)
    for (unsigned int x = 0; x < pg->Width(); x++)
      if (*pg->Buffer(x, y)) n++;
  return n;
}

}  // namespace

TEST_CASE("Monitor: an Inactive zone blanks its whole raster, edges included", "[Zone]") {
  if (!config.font_file_location) config.font_file_location = "";
  if (!config.event_close_mode) config.event_close_mode = "idle";

  Polygon frame(std::vector<Vector2>{Vector2(0, 0), Vector2(63, 0), Vector2(63, 47), Vector2(0, 47)});
  Polygon rect(std::vector<Vector2>{Vector2(10, 10), Vector2(20, 10), Vector2(20, 20), Vector2(10, 20)});
  Polygon triangle(std::vector<Vector2>{Vector2(10, 10), Vector2(40, 10), Vector2(10, 40)});

  for (const Polygon &inactive : {rect, triangle}) {
    auto monitor = std::make_shared<TestMonitor>(64, 48);
    monitor->AddZone(Zone::ACTIVE, frame);
    monitor->AddZone(Zone::INACTIVE, inactive);

    Image comp(64, 48, ZM_COLOUR_GRAY8, ZM_SUBPIX_ORDER_NONE);
    for (unsigned int y = 0; y < 48; y++)
      for (unsigned int x = 0; x < 64; x++)
        *comp.Buffer(x, y) = 255;

    Event::StringSet zoneSet;
    monitor->DetectMotion(comp, zoneSet);

    REQUIRE(monitor->ActiveAlarmPixels() == 64 * 48 - RasterCount(monitor, inactive));
  }
}
