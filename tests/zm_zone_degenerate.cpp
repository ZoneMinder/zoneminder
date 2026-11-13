#include "zm_catch2.h"

#include "zm_config.h"
#include "zm_image.h"
#include "zm_monitor.h"
#include "zm_rgb.h"
#include "zm_zone.h"

#include <memory>
#include <vector>

// A zone with no polygon area (collinear points, one pixel wide) still
// rasterises pixels. With a Max of 0 the score divided by the zero area.
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

std::unique_ptr<Image> ChangedFrame(unsigned int w, unsigned int h) {
  auto img = std::unique_ptr<Image>(new Image(w, h, ZM_COLOUR_GRAY8, ZM_SUBPIX_ORDER_NONE));
  for (unsigned int y = 0; y < h; y++)
    for (unsigned int x = 0; x < w; x++)
      *img->Buffer(x, y) = 255;
  return img;
}

}  // namespace

TEST_CASE("Zone: a zero-area polygon scores without dividing by zero", "[Zone]") {
  if (!config.font_file_location) config.font_file_location = "";
  if (!config.event_close_mode) config.event_close_mode = "idle";
  auto monitor = std::static_pointer_cast<Monitor>(std::make_shared<TestMonitor>(64, 48));
  auto delta = ChangedFrame(64, 48);

  Polygon collinear(std::vector<Vector2>{Vector2(10, 10), Vector2(30, 10), Vector2(20, 10)});
  Polygon thin(std::vector<Vector2>{Vector2(10, 10), Vector2(10, 30), Vector2(10, 20)});
  REQUIRE(collinear.Area() == 0);
  REQUIRE(thin.Area() == 0);

  for (Zone::CheckMethod method : {Zone::ALARMED_PIXELS, Zone::FILTERED_PIXELS, Zone::BLOBS}) {
    for (const Polygon &polygon : {collinear, thin}) {
      Zone zone(monitor, 1, "degenerate", Zone::ACTIVE, polygon, 0, method,
                15, 0, 1, 0, Vector2(1, 1), 1, 0, 1, 0, 0, 0);
      REQUIRE(zone.CheckAlarms(delta.get()));
      REQUIRE(zone.Score() >= 1);
    }
  }
}
