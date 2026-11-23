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

namespace {

class PrivacyMonitor : public Monitor {
 public:
  PrivacyMonitor() : Monitor() {
    width = 32;
    height = 16;
  }
  void AddPrivacyZone(const std::shared_ptr<Monitor> &self) {
    Polygon polygon(std::vector<Vector2>{Vector2(2, 2), Vector2(8, 2), Vector2(8, 8), Vector2(2, 8)});
    zones.emplace_back(self, 1, "privacy", Zone::PRIVACY, polygon);
  }
  void ClearZones() { zones.clear(); }
  const unsigned char *Mask() const { return privacy_bitmask; }
  uint32_t ImageMaskGeneration() const { return websocket_latest_image.MaskGeneration(); }
};

}  // namespace

TEST_CASE("Monitor::AddPrivacyBitmask: repeated calls rebuild the mask", "[Monitor]") {
  auto monitor = std::make_shared<PrivacyMonitor>();
  monitor->AddPrivacyZone(monitor);

  for (int i = 0; i < 2; i++) {
    monitor->AddPrivacyBitmask();
    const unsigned char *mask = monitor->Mask();
    REQUIRE(mask != nullptr);
    REQUIRE(mask[5 * 32 + 5] == 0xff);
    REQUIRE(mask[12 * 32 + 12] == 0);
  }
  // The zone holds the monitor; drop it so neither leaks.
  monitor->ClearZones();
}

// Zones are reloaded on every connect(). A reload that leaves the mask as it
// was must not hold back WebSocket images until the decoder publishes one
// (refs #2875).
TEST_CASE("Monitor::AddPrivacyBitmask: WebSocket images wait only when the mask changes", "[Monitor]") {
  auto monitor = std::make_shared<PrivacyMonitor>();

  SECTION("no privacy zones, reloaded") {
    monitor->AddPrivacyBitmask();
    monitor->AddPrivacyBitmask();
    REQUIRE(monitor->ImageMaskGeneration() == 0);
  }

  SECTION("the same privacy zone, reloaded") {
    monitor->AddPrivacyZone(monitor);
    monitor->AddPrivacyBitmask();
    const uint32_t generation = monitor->ImageMaskGeneration();
    REQUIRE(generation == 1);
    monitor->AddPrivacyBitmask();
    REQUIRE(monitor->ImageMaskGeneration() == generation);
  }

  SECTION("a privacy zone added, then removed") {
    monitor->AddPrivacyBitmask();
    monitor->AddPrivacyZone(monitor);
    monitor->AddPrivacyBitmask();
    REQUIRE(monitor->ImageMaskGeneration() == 1);
    monitor->ClearZones();
    monitor->AddPrivacyBitmask();
    REQUIRE(monitor->ImageMaskGeneration() == 2);
    REQUIRE(monitor->Mask() == nullptr);
  }

  monitor->ClearZones();
}
