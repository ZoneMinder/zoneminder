#include "zm_catch2.h"

#include "zm_config.h"
#include "zm_image.h"
#include "zm_monitor.h"
#include "zm_rgb.h"
#include "zm_zone.h"

#include <memory>
#include <vector>

// Zone::LoadRow builds a zone from one Zones row, as Zone::Load and zma --set
// both do, and CheckAlarms records what it saw in Diagnostics() for zma --json.
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

std::shared_ptr<Monitor> MakeMonitor() {
  if (!config.font_file_location) config.font_file_location = "";
  if (!config.event_close_mode) config.event_close_mode = "idle";
  return std::static_pointer_cast<Monitor>(std::make_shared<TestMonitor>(64, 48));
}

// A row in Zone::kLoadColumns order.
std::vector<const char *> Row(const char *type, const char *coords, const char *min_alarm,
                              const char *min_filter, const char *min_blob) {
  return {"7", "Test", type, "Percent", coords, "16711680", "3",
          "25", "0", min_alarm, "0",
          "3", "3", min_filter, "0",
          min_blob, "0", "1", "0",
          "0", "0"};
}

}  // namespace

TEST_CASE("Zone::LoadRow: the row matches kLoadColumns", "[Zone]") {
  REQUIRE(Row("1", "", "", "", "").size() == Zone::kLoadColumns.size());
  REQUIRE(Zone::LoadColumnsSql().rfind("Id,Name,Type+0,", 0) == 0);
}

TEST_CASE("Zone::LoadRow: percent coords turn percent thresholds into pixels", "[Zone]") {
  auto monitor = MakeMonitor();
  std::vector<Zone> zones;
  auto row = Row("1", "0.00,0.00 100.00,0.00 100.00,100.00 0.00,100.00", "10", "5.5", "2");
  REQUIRE(Zone::LoadRow(monitor, row.data(), zones));
  REQUIRE(zones.size() == 1);
  const Zone &zone = zones[0];
  // Thresholds are converted with the same rasterised area Load uses, not the
  // polygon's geometric area.
  const int zpa = Zone::RasterArea(zone.GetPolygon(), monitor->Width(), monitor->Height());
  REQUIRE(zpa > 0);
  REQUIRE(zone.Id() == 7);
  REQUIRE(zone.GetCheckMethod() == Zone::BLOBS);
  REQUIRE(zone.MinAlarmPixels() == Zone::PercentToPixels(10, zpa));
  REQUIRE(zone.MinFilterPixels() == Zone::PercentToPixels(5.5, zpa));
  REQUIRE(zone.MinBlobPixels() == Zone::PercentToPixels(2, zpa));
  REQUIRE(zone.MinBlobs() == 1);
}

TEST_CASE("Zone::LoadRow: pixel coords keep thresholds as pixel counts", "[Zone]") {
  auto monitor = MakeMonitor();
  std::vector<Zone> zones;
  auto row = Row("1", "0,0 63,0 63,47 0,47", "100", "50", "20");
  REQUIRE(Zone::LoadRow(monitor, row.data(), zones));
  REQUIRE(zones[0].MinAlarmPixels() == 100);
  REQUIRE(zones[0].MinFilterPixels() == 50);
  REQUIRE(zones[0].MinBlobPixels() == 20);
}

TEST_CASE("Zone::LoadRow: an inactive row and a bad polygon", "[Zone]") {
  auto monitor = MakeMonitor();
  std::vector<Zone> zones;
  auto inactive = Row("5", "0,0 63,0 63,47 0,47", "0", "0", "0");
  REQUIRE(Zone::LoadRow(monitor, inactive.data(), zones));
  REQUIRE(zones.back().IsInactive());

  auto bad = Row("1", "10.00,10.00", "0", "0", "0");
  REQUIRE_FALSE(Zone::LoadRow(monitor, bad.data(), zones));
  REQUIRE(zones.size() == 1);
}

TEST_CASE("Zone: Diagnostics records the alarmed, filtered and largest blob boxes", "[Zone]") {
  auto monitor = MakeMonitor();
  Polygon polygon(std::vector<Vector2>{Vector2(0, 0), Vector2(63, 0), Vector2(63, 47), Vector2(0, 47)});
  Image delta(64, 48, ZM_COLOUR_GRAY8, ZM_SUBPIX_ORDER_NONE);
  for (unsigned int y = 0; y < 48; y++)
    for (unsigned int x = 0; x < 64; x++)
      *delta.Buffer(x, y) = 0;
  *delta.Buffer(5, 5) = 255;  // removed by the 3x3 filter
  for (unsigned int y = 20; y < 24; y++)
    for (unsigned int x = 30; x < 34; x++)
      *delta.Buffer(x, y) = 255;  // a 4x4 blob

  Zone zone(monitor, 1, "diag", Zone::ACTIVE, polygon, 0, Zone::BLOBS,
            15, 0, 1, 0, Vector2(3, 3), 0, 0, 0, 0, 0, 0);
  REQUIRE(zone.CheckAlarms(&delta));
  const ZoneDiagnostics &diag = zone.Diagnostics();
  REQUIRE(diag.alarmed_box.Lo().x_ == 5);
  REQUIRE(diag.alarmed_box.Hi().x_ == 33);
  REQUIRE(diag.filtered_box.Lo().x_ == 30);
  REQUIRE(diag.filtered_box.Hi().y_ == 23);
  REQUIRE(diag.largest_blob == 16);
  REQUIRE(diag.largest_blob_box.Lo().y_ == 20);

  zone.ResetStats();
  REQUIRE(zone.Diagnostics().largest_blob == 0);
}
