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
#include "zm_monitor.h"

#include <cstdio>
#include <cstdlib>
#include <ctime>
#include <memory>
#include <unistd.h>
#include <vector>

namespace {

void EnsureConfig() {
  if (!config.font_file_location) config.font_file_location = "";
  if (!config.event_close_mode) config.event_close_mode = "idle";
}

// The linked (source) monitor. Its shared data is a local struct here.
class SourceMonitor : public Monitor {
 public:
  using Data = SharedData;
  using Trigger = TriggerData;
};

class TestLink : public Monitor::MonitorLink {
 public:
  explicit TestLink(const std::shared_ptr<Monitor> &source) : MonitorLink(source, 0) {}
  void Attach(SourceMonitor::Data *data) {
    shared_data = data;
    connected = true;
  }
};

class TestZoneLink : public Monitor::MonitorLink {
 public:
  TestZoneLink(const std::shared_ptr<Monitor> &source, unsigned int zid, int zindex)
    : MonitorLink(source, zid) {
    zone_index = zindex;
  }
  void Attach(SourceMonitor::Data *data, volatile int *scores = nullptr) {
    shared_data = data;
    zone_scores = scores;
    connected = true;
  }
};

#if ZM_MEM_MAPPED
class FileZoneLink : public Monitor::MonitorLink {
 public:
  FileZoneLink(const std::shared_ptr<Monitor> &source, int zindex, const std::string &file)
    : MonitorLink(source, 0) {
    zone_index = zindex;
    mem_file = file;
  }
  bool Connected() const { return connected; }
};

// Writes a mapped file the size of the shared data, trigger data and `scores` ints, marked valid with a fresh heartbeat. Returns its path.
std::string WriteLinkFile(size_t scores) {
  char path[] = "/tmp/zm_link_test_XXXXXX";
  int fd = mkstemp(path);
  REQUIRE(fd >= 0);
  std::vector<char> buf(sizeof(SourceMonitor::Data) + sizeof(SourceMonitor::Trigger) + scores * sizeof(int), 0);
  auto *data = reinterpret_cast<SourceMonitor::Data *>(buf.data());
  data->valid = 1;
  data->heartbeat_time = time(nullptr);
  REQUIRE(write(fd, buf.data(), buf.size()) == static_cast<ssize_t>(buf.size()));
  close(fd);
  return path;
}
#endif

}  // namespace

// A link reports the source monitor's alarm status: its frame score while it
// is in ALARM, and 1 in ALERT so that a linked event stays open through the
// source's post-event frames. A new event on the source is not an alarm: one
// recording continuously starts a new event at every section boundary.
TEST_CASE("MonitorLink score follows the source's alarm state", "[MonitorLink]") {
  EnsureConfig();
  SourceMonitor::Data data = {};
  data.valid = 1;
  data.state = Monitor::IDLE;
  TestLink link(std::make_shared<SourceMonitor>());
  link.Attach(&data);

  SECTION("idle") {
    REQUIRE(link.score() == 0);
    REQUIRE_FALSE(link.hasAlarmed());
  }
  SECTION("alarm: the source's frame score") {
    data.state = Monitor::ALARM;
    data.last_frame_score = 42;
    REQUIRE(link.score() == 42);
    REQUIRE(link.hasAlarmed());
  }
  SECTION("alarm: a packet with no score still counts as alarmed") {
    // Audio packets and frames skipped by motion detection leave
    // last_frame_score at 0 while the source stays in ALARM.
    data.state = Monitor::ALARM;
    data.last_frame_score = 0;
    REQUIRE(link.score() == 1);
    REQUIRE(link.hasAlarmed());
  }
  SECTION("alert: keeps the linked event open") {
    data.state = Monitor::ALERT;
    data.last_frame_score = 0;
    REQUIRE(link.score() == 1);
  }
  SECTION("a new event while idle is not an alarm") {
    data.last_event_id = 1234;
    REQUIRE(link.score() == 0);
    data.last_frame_score = 17;  // left over from the source's last alarm
    data.last_event_id = 1235;
    REQUIRE(link.score() == 0);
  }
}

// A zmc that dies without cleaning up leaves valid set in the mapping. The link
// must see the stale heartbeat as disconnected so that it reconnects instead of
// reporting the source's last state forever.
TEST_CASE("MonitorLink treats a stale heartbeat as disconnected", "[MonitorLink]") {
  EnsureConfig();
  SourceMonitor::Data data = {};
  data.valid = 1;
  data.state = Monitor::ALARM;
  TestLink link(std::make_shared<SourceMonitor>());
  link.Attach(&data);

  data.heartbeat_time = time(nullptr);
  REQUIRE(link.isConnected());

  data.heartbeat_time = time(nullptr) - 3600;
  REQUIRE_FALSE(link.isConnected());
}

TEST_CASE("MonitorLink zone score follows source monitor state and zone id", "[MonitorLink]") {
  EnsureConfig();
  SourceMonitor::Data data = {};
  data.valid = 1;
  data.state = Monitor::IDLE;

  int mock_zone_scores[4] = { 0, 85, 0, 0 };

  SECTION("non-existent zone in ALARM returns 0 and does not fall back to whole monitor") {
    TestZoneLink link(std::make_shared<SourceMonitor>(), 999, -1);
    link.Attach(&data, mock_zone_scores);

    data.state = Monitor::ALARM;
    data.last_frame_score = 42;
    REQUIRE(link.score() == 0);
  }

  SECTION("valid zone in IDLE returns 0 even with leftover zone score") {
    TestZoneLink link(std::make_shared<SourceMonitor>(), 2, 1);
    link.Attach(&data, mock_zone_scores);

    // Verify zone score is reported while in ALARM
    data.state = Monitor::ALARM;
    data.last_frame_score = 99;
    CHECK(link.score() == 85);

    // Transitioning to IDLE must return 0 rather than stale zone score
    data.state = Monitor::IDLE;
    REQUIRE(link.score() == 0);
  }

  SECTION("valid zone in ALERT returns sentinel 1") {
    TestZoneLink link(std::make_shared<SourceMonitor>(), 2, 1);
    link.Attach(&data, mock_zone_scores);

    data.state = Monitor::ALERT;
    REQUIRE(link.score() == 1);
  }

  SECTION("zone that can't be found in ALERT returns 0 like ALARM") {
    TestZoneLink link(std::make_shared<SourceMonitor>(), 999, -1);
    link.Attach(&data, mock_zone_scores);

    data.state = Monitor::ALERT;
    REQUIRE(link.score() == 0);
  }
}

#if ZM_MEM_MAPPED
TEST_CASE("MonitorLink does not connect to memory too small for its zone score", "[MonitorLink]") {
  EnsureConfig();
  auto source = std::make_shared<SourceMonitor>();
  std::string small = WriteLinkFile(0);
  std::string large = WriteLinkFile(3);
  {
    FileZoneLink link(source, 2, small);
    REQUIRE_FALSE(link.connect());
  }
  {
    FileZoneLink link(source, 2, large);
    REQUIRE(link.connect());
  }
  unlink(small.c_str());
  unlink(large.c_str());
}
#endif
