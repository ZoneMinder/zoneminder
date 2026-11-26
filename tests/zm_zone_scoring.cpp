#include "zm_catch2.h"

#include "zm_config.h"
#include "zm_image.h"
#include "zm_monitor.h"
#include "zm_rgb.h"
#include "zm_zone.h"

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

  void AddZone(Zone::ZoneType type, const Polygon &polygon) {
    zones.emplace_back(shared_from_this(), zones.size() + 1, "zone", type, polygon, 0,
                       Zone::ALARMED_PIXELS, 15, 0, 1, 0);
  }

  const ZoneStats &Stats(size_t i) const { return zones[i].GetStats(); }
};

void EnsureConfig() {
  if (!config.font_file_location) config.font_file_location = "";
  if (!config.event_close_mode) config.event_close_mode = "idle";
}

Polygon Rect(int x0, int y0, int x1, int y1) {
  return Polygon(std::vector<Vector2>{Vector2(x0, y0), Vector2(x1, y0), Vector2(x1, y1), Vector2(x0, y1)});
}

void Paint(Image &img, int x0, int y0, int x1, int y1) {
  for (int y = y0; y <= y1; y++)
    for (int x = x0; x <= x1; x++) *img.Buffer(x, y) = 255;
}

}  // namespace

// Inclusive scores are halved. A score of 1 became 0, which skipped the alarm
// box and centre of a zone that had met every threshold.
TEST_CASE("Zone: a small inclusive alarm keeps a score and an alarm box", "[Zone]") {
  EnsureConfig();
  auto monitor = std::static_pointer_cast<Monitor>(std::make_shared<TestMonitor>(64, 48));
  Image delta(64, 48, ZM_COLOUR_GRAY8, ZM_SUBPIX_ORDER_NONE);
  delta.Clear();
  Paint(delta, 5, 7, 5, 7);

  Zone zone(monitor, 1, "inclusive", Zone::INCLUSIVE, Rect(0, 0, 63, 47), 0, Zone::ALARMED_PIXELS,
            15, 0, 1, 0);
  REQUIRE(zone.CheckAlarms(&delta));
  CHECK(zone.Score() == 1);
  CHECK(zone.GetStats().alarm_box_.Lo().x_ == 5);
  CHECK(zone.GetStats().alarm_box_.Lo().y_ == 7);
  CHECK(zone.GetStats().alarm_box_.Hi().x_ == 5);
  CHECK(zone.GetStats().alarm_box_.Hi().y_ == 7);
  CHECK(zone.GetAlarmCentre().x_ == 5);
  CHECK(zone.GetAlarmCentre().y_ == 7);
}

// The weighted centre is the mean pixel position, rounded. Dividing the integer
// totals first truncated it.
TEST_CASE("Zone: the weighted blob centre rounds the mean position", "[Zone]") {
  EnsureConfig();
  const auto saved = config.weighted_alarm_centres;
  config.weighted_alarm_centres = true;

  auto monitor = std::static_pointer_cast<Monitor>(std::make_shared<TestMonitor>(64, 48));
  Image delta(64, 48, ZM_COLOUR_GRAY8, ZM_SUBPIX_ORDER_NONE);
  delta.Clear();
  // Mean x is 32/3 = 10.67, mean y is 16/3 = 5.33
  Paint(delta, 10, 5, 11, 5);
  Paint(delta, 11, 6, 11, 6);

  Zone zone(monitor, 1, "blob", Zone::ACTIVE, Rect(0, 0, 63, 47), 0, Zone::BLOBS,
            15, 0, 1, 0, Vector2(1, 1), 1, 0, 1, 0, 0, 0);
  const bool alarmed = zone.CheckAlarms(&delta);
  config.weighted_alarm_centres = saved;

  REQUIRE(alarmed);
  REQUIRE(zone.GetStats().alarm_blobs_ == 1);
  CHECK(zone.GetAlarmCentre().x_ == 11);
  CHECK(zone.GetAlarmCentre().y_ == 5);
}

// Zones that DetectMotion does not check in a frame kept the scores and boxes of
// the last frame they were checked, and those went into the per-zone scores and
// the Stats rows.
TEST_CASE("Monitor: zones skipped in a frame report no stale scores", "[Zone]") {
  EnsureConfig();

  SECTION("a preclusive alarm skips the active zone") {
    auto monitor = std::make_shared<TestMonitor>(64, 48);
    monitor->AddZone(Zone::ACTIVE, Rect(0, 0, 31, 47));
    monitor->AddZone(Zone::PRECLUSIVE, Rect(40, 10, 50, 20));

    Event::StringSet zoneSet;
    Image first(64, 48, ZM_COLOUR_GRAY8, ZM_SUBPIX_ORDER_NONE);
    first.Clear();
    Paint(first, 0, 0, 31, 47);
    monitor->DetectMotion(first, zoneSet);
    REQUIRE(monitor->Stats(0).score_ > 0);

    Image second(64, 48, ZM_COLOUR_GRAY8, ZM_SUBPIX_ORDER_NONE);
    second.Clear();
    Paint(second, 0, 0, 31, 47);
    Paint(second, 40, 10, 50, 20);
    CHECK(monitor->DetectMotion(second, zoneSet) == 0);
    CHECK(monitor->Stats(1).score_ > 0);
    CHECK(monitor->Stats(0).score_ == 0);
    CHECK(monitor->Stats(0).alarm_pixels_ == 0);
  }

  SECTION("no active alarm skips the inclusive zone") {
    auto monitor = std::make_shared<TestMonitor>(64, 48);
    monitor->AddZone(Zone::ACTIVE, Rect(0, 0, 31, 47));
    monitor->AddZone(Zone::INCLUSIVE, Rect(40, 10, 50, 20));

    Event::StringSet zoneSet;
    Image first(64, 48, ZM_COLOUR_GRAY8, ZM_SUBPIX_ORDER_NONE);
    first.Clear();
    Paint(first, 0, 0, 31, 47);
    Paint(first, 40, 10, 50, 20);
    monitor->DetectMotion(first, zoneSet);
    REQUIRE(monitor->Stats(1).score_ > 0);

    Image second(64, 48, ZM_COLOUR_GRAY8, ZM_SUBPIX_ORDER_NONE);
    second.Clear();
    Paint(second, 40, 10, 50, 20);
    CHECK(monitor->DetectMotion(second, zoneSet) == 0);
    CHECK(monitor->Stats(1).score_ == 0);
    CHECK(monitor->Stats(1).alarm_pixels_ == 0);
  }

  SECTION("an active alarm skips the exclusive zone") {
    auto monitor = std::make_shared<TestMonitor>(64, 48);
    monitor->AddZone(Zone::ACTIVE, Rect(0, 0, 31, 47));
    monitor->AddZone(Zone::EXCLUSIVE, Rect(40, 10, 50, 20));

    Event::StringSet zoneSet;
    Image first(64, 48, ZM_COLOUR_GRAY8, ZM_SUBPIX_ORDER_NONE);
    first.Clear();
    Paint(first, 40, 10, 50, 20);
    monitor->DetectMotion(first, zoneSet);
    REQUIRE(monitor->Stats(1).score_ > 0);

    Image second(64, 48, ZM_COLOUR_GRAY8, ZM_SUBPIX_ORDER_NONE);
    second.Clear();
    Paint(second, 0, 0, 31, 47);
    Paint(second, 40, 10, 50, 20);
    monitor->DetectMotion(second, zoneSet);
    CHECK(monitor->Stats(0).score_ > 0);
    CHECK(monitor->Stats(1).score_ == 0);
  }
}

TEST_CASE("Zone: pixel thresholds above 255 do not wrap", "[Zone]") {
  EnsureConfig();
  auto monitor = std::static_pointer_cast<Monitor>(std::make_shared<TestMonitor>(64, 48));
  Image delta(64, 48, ZM_COLOUR_GRAY8, ZM_SUBPIX_ORDER_NONE);
  for (int y = 0; y < 48; y++)
    for (int x = 0; x < 64; x++) *delta.Buffer(x, y) = 100;

  Zone zone1(monitor, 1, "z1", Zone::ACTIVE, Rect(0, 0, 63, 47), 0, Zone::ALARMED_PIXELS,
             15, 256, 1, 0);
  REQUIRE(zone1.CheckAlarms(&delta));

  Zone zone2(monitor, 2, "z2", Zone::ACTIVE, Rect(0, 0, 63, 47), 0, Zone::ALARMED_PIXELS,
             300, 0, 1, 0);
  CHECK_FALSE(zone2.CheckAlarms(&delta));
}
