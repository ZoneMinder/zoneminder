#include "zm_catch2.h"

#include "zm_config.h"
#include "zm_image.h"
#include "zm_monitor.h"
#include "zm_rgb.h"
#include "zm_zone.h"

#include <memory>
#include <vector>

// AlarmedPixels and FilteredPixels zones left the alarm box at (0,0)-(0,0)
// with a centre of (-1,-1) or (0,0); only the Blobs path computed it. The box
// feeds the Stats rows and the motion tracking centre.
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

TEST_CASE("Zone: alarmed and filtered pixel zones report the alarm box", "[Zone]") {
  if (!config.font_file_location) config.font_file_location = "";
  if (!config.event_close_mode) config.event_close_mode = "idle";
  auto monitor = std::static_pointer_cast<Monitor>(std::make_shared<TestMonitor>(64, 48));
  Polygon polygon(std::vector<Vector2>{Vector2(0, 0), Vector2(63, 0), Vector2(63, 47), Vector2(0, 47)});

  Image delta(64, 48, ZM_COLOUR_GRAY8, ZM_SUBPIX_ORDER_NONE);
  for (unsigned int y = 0; y < 48; y++)
    for (unsigned int x = 0; x < 64; x++)
      *delta.Buffer(x, y) = 0;
  for (unsigned int y = 20; y < 24; y++)
    for (unsigned int x = 10; x < 14; x++)
      *delta.Buffer(x, y) = 255;

  SECTION("a lone pixel outside the object widens the alarmed box but not the filtered one") {
    *delta.Buffer(50, 5) = 255;

    Zone alarmed(monitor, 1, "a", Zone::ACTIVE, polygon, 0, Zone::ALARMED_PIXELS,
                 15, 0, 1, 0, Vector2(3, 3), 1, 0, 1, 0, 0, 0);
    REQUIRE(alarmed.CheckAlarms(&delta));
    REQUIRE(alarmed.GetStats().alarm_box_.Lo() == Vector2(10, 5));
    REQUIRE(alarmed.GetStats().alarm_box_.Hi() == Vector2(50, 23));

    Zone filtered(monitor, 2, "f", Zone::ACTIVE, polygon, 0, Zone::FILTERED_PIXELS,
                  15, 0, 1, 0, Vector2(3, 3), 1, 0, 1, 0, 0, 0);
    REQUIRE(filtered.CheckAlarms(&delta));
    REQUIRE(filtered.GetStats().alarm_box_.Lo() == Vector2(10, 20));
    REQUIRE(filtered.GetStats().alarm_box_.Hi() == Vector2(13, 23));
  }

  SECTION("a 4x4 object gives the same box and centre on every check method") {
    for (Zone::CheckMethod method : {Zone::ALARMED_PIXELS, Zone::FILTERED_PIXELS, Zone::BLOBS}) {
      CAPTURE(method);
      Zone zone(monitor, 1, "z", Zone::ACTIVE, polygon, 0, method,
                15, 0, 1, 0, Vector2(3, 3), 1, 0, 1, 0, 0, 0);
      REQUIRE(zone.CheckAlarms(&delta));
      REQUIRE(zone.GetStats().alarm_box_.Lo() == Vector2(10, 20));
      REQUIRE(zone.GetStats().alarm_box_.Hi() == Vector2(13, 23));
      REQUIRE(zone.GetAlarmCentre() == Vector2(12, 22));
    }
  }
}
