/*
 * This file is part of the ZoneMinder Project. See AUTHORS file for contributors.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

#include "zm_catch2.h"

#include "zm_monitorstream.h"

#include "zm_config.h"
#include "zm_monitor.h"
#include "zm_zone.h"

#include <memory>
#include <vector>

TEST_CASE("MonitorStreamBufferLevel") {
  SECTION("zero buffer count does not divide by zero") {
    // Regression for zoneminder/zoneminder#4936: processCommand() could run on
    // the command thread before runStream() assigned temp_image_buffer_count,
    // leaving it 0 while playback_buffer was already > 0, causing a SIGFPE.
    REQUIRE(MonitorStreamBufferLevel(0, 0, 0) == 0);
    REQUIRE(MonitorStreamBufferLevel(5, 2, 0) == 0);
    REQUIRE(MonitorStreamBufferLevel(0, 5, 0) == 0);
  }

  SECTION("negative buffer count is treated as empty") {
    REQUIRE(MonitorStreamBufferLevel(3, 1, -10) == 0);
  }

  SECTION("empty buffer reports 0%") {
    REQUIRE(MonitorStreamBufferLevel(0, 0, 100) == 0);
  }

  SECTION("full buffer reports nearly 100%") {
    // write wraps around to one behind read => count-1 of count slots used
    REQUIRE(MonitorStreamBufferLevel(99, 0, 100) == 99);
  }

  SECTION("half full buffer reports ~50%") {
    REQUIRE(MonitorStreamBufferLevel(50, 0, 100) == 50);
  }

  SECTION("wrap-around (write behind read) is handled modularly") {
    // write_index - read_index is negative; modular arithmetic keeps it in range
    REQUIRE(MonitorStreamBufferLevel(2, 7, 10) == 50);
  }

  SECTION("a read that straddles an update still reports in range") {
    // zoneminder/zoneminder#4939: the command thread reads the three values as
    // separate atomics, so it can pick up a write index from one moment and a
    // read index from another. That stays harmless for this percentage as long
    // as each index is individually within [0, count): the +count before the
    // modulo covers the whole (-count, count) span the difference can take.
    const int count = 64;
    for (int write_index = 0; write_index < count; write_index++) {
      for (int read_index = 0; read_index < count; read_index++) {
        const int level = MonitorStreamBufferLevel(write_index, read_index, count);
        REQUIRE(level >= 0);
        REQUIRE(level <= 100);
      }
    }
  }
}

namespace {

// No zm.conf is loaded under the test harness, so config strings are null.
// Image::Initialise() dereferences config.font_file_location and the Monitor
// constructor strcmp()s config.event_close_mode; both segfault on null.
void EnsureConfig() {
  if (!config.font_file_location) config.font_file_location = "";
  if (!config.event_close_mode) config.event_close_mode = "idle";
}

// Monitor's dimensions and zones are protected and normally come from the
// database.
class TestMonitor : public Monitor {
 public:
  TestMonitor(unsigned int w, unsigned int h) : Monitor() {
    width = w;
    height = h;
    colours = ZM_COLOUR_GRAY8;
  }
  // A full-frame zone. Like every Zone, it holds a shared_ptr to this monitor.
  static void AddZone(const std::shared_ptr<TestMonitor> &monitor) {
    const int w = monitor->width, h = monitor->height;
    std::vector<Vector2> vertices = {
        Vector2(0, 0), Vector2(w - 1, 0), Vector2(w - 1, h - 1), Vector2(0, h - 1)};
    monitor->zones.emplace_back(monitor, 1, "full", Zone::ACTIVE, Polygon(vertices));
  }
};

// The stream's monitor is protected; nph-zms sets it through loadMonitor(),
// which needs the database. Hand one in directly instead.
class TestMonitorStream : public MonitorStream {
 public:
  void setMonitor(const std::shared_ptr<Monitor> &p_monitor) {
    monitor = p_monitor;
  }
};

}  // namespace

TEST_CASE("MonitorStream releases its monitor when destroyed", "[Stream]") {
  // Each Zone holds a shared_ptr back to its Monitor, so a monitor with zones
  // keeps itself alive after the stream drops its reference. nph-zms leaked
  // the monitor, its zones and their full-frame images on every exit until
  // ~StreamBase called disconnect() to break the cycle, as zmc does.
  EnsureConfig();
  std::weak_ptr<Monitor> watched;

  {
    TestMonitorStream stream;
    {
      auto monitor = std::make_shared<TestMonitor>(64, 48);
      TestMonitor::AddZone(monitor);
      watched = monitor;
      stream.setMonitor(monitor);
    }
    REQUIRE_FALSE(watched.expired());
  }

  REQUIRE(watched.expired());
}
