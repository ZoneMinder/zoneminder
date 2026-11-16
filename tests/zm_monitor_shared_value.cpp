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

#include <cstddef>
#include <thread>

// zmc's threads, zms, zmu and the web interface share SharedData.
// Its scalar fields were plain members, so ThreadSanitizer reported races on
// capture_fps, state and analysing (#5238), and the action bits that zmu sets
// and zmc clears were read-modify-write sequences that could lose a bit. They
// are now SharedValue, which makes every access a relaxed atomic.
namespace {

// SharedData and the action bits are protected in Monitor.
class SharedDataAccess : public Monitor {
 public:
  using Monitor::SharedData;
  static constexpr uint32_t kReload = RELOAD;
  static constexpr uint32_t kSuspend = SUSPEND;
};
using SharedData = SharedDataAccess::SharedData;
constexpr uint32_t RELOAD = SharedDataAccess::kReload;
constexpr uint32_t SUSPEND = SharedDataAccess::kSuspend;

}  // namespace

TEST_CASE("SharedData offsets still match Monitor.php and Memory.pm", "[Monitor]") {
  // The offsets web/includes/Monitor.php reads. SharedValue must not move any.
  CHECK(sizeof(SharedData) == 888);
  CHECK(offsetof(SharedData, size) == 0);
  CHECK(offsetof(SharedData, last_write_index) == 4);
  CHECK(offsetof(SharedData, last_read_index) == 8);
  CHECK(offsetof(SharedData, image_count) == 12);
  CHECK(offsetof(SharedData, state) == 16);
  CHECK(offsetof(SharedData, capture_fps) == 24);
  CHECK(offsetof(SharedData, analysis_fps) == 32);
  CHECK(offsetof(SharedData, latitude) == 40);
  CHECK(offsetof(SharedData, longitude) == 48);
  CHECK(offsetof(SharedData, last_event_id) == 56);
  CHECK(offsetof(SharedData, action) == 64);
  CHECK(offsetof(SharedData, brightness) == 68);
  CHECK(offsetof(SharedData, hue) == 72);
  CHECK(offsetof(SharedData, colour) == 76);
  CHECK(offsetof(SharedData, contrast) == 80);
  CHECK(offsetof(SharedData, alarm_x) == 84);
  CHECK(offsetof(SharedData, alarm_y) == 88);
  CHECK(offsetof(SharedData, valid) == 92);
  CHECK(offsetof(SharedData, capturing) == 93);
  CHECK(offsetof(SharedData, analysing) == 94);
  CHECK(offsetof(SharedData, recording) == 95);
  CHECK(offsetof(SharedData, signal) == 96);
  CHECK(offsetof(SharedData, format) == 97);
  CHECK(offsetof(SharedData, audio_level) == 98);
  CHECK(offsetof(SharedData, audio_alarm) == 99);
  CHECK(offsetof(SharedData, imagesize) == 100);
  CHECK(offsetof(SharedData, last_frame_score) == 104);
  CHECK(offsetof(SharedData, audio_frequency) == 108);
  CHECK(offsetof(SharedData, audio_channels) == 112);
  CHECK(offsetof(SharedData, startup_time) == 120);
  CHECK(offsetof(SharedData, heartbeat_time) == 128);
  CHECK(offsetof(SharedData, last_write_time) == 136);
  CHECK(offsetof(SharedData, last_read_time) == 144);
  CHECK(offsetof(SharedData, last_viewed_time) == 152);
  CHECK(offsetof(SharedData, last_analysis_viewed_time) == 160);
  CHECK(offsetof(SharedData, control_state) == 168);
  CHECK(offsetof(SharedData, last_analysis_index) == 872);
  CHECK(offsetof(SharedData, analysis_image_count) == 876);
  CHECK(offsetof(SharedData, audio_level_until) == 880);
}

TEST_CASE("SharedValue reads and writes the plain value", "[Monitor]") {
  SharedData data = {};

  data.capture_fps = 12.5;
  CHECK(data.capture_fps == 12.5);
  CHECK(*reinterpret_cast<double *>(reinterpret_cast<char *>(&data) + 24) == 12.5);

  data.state = Monitor::ALARM;
  CHECK(data.state.load() == Monitor::ALARM);

  data.alarm_x = data.alarm_y = -1;
  CHECK(data.alarm_x == -1);
  CHECK(data.alarm_y == -1);

  data.image_count = 7;
  CHECK(data.image_count++ == 7);
  CHECK(data.image_count == 8);

  data.action = RELOAD;
  data.action |= SUSPEND;
  CHECK(data.action == (RELOAD | SUSPEND));
  data.action &= ~RELOAD;
  CHECK(data.action == SUSPEND);
}

TEST_CASE("SharedData action bits set and cleared at once are not lost", "[Monitor]") {
  // zmu sets a bit while zmc clears another. As plain |= and &= each could
  // write back a word read before the other's write, dropping its bit.
  SharedData data = {};
  constexpr int kRounds = 200000;
  int lost = 0;

  std::thread clearer([&data] {
    for (int i = 0; i < kRounds; i++) {
      data.action |= RELOAD;
      data.action &= ~RELOAD;
    }
  });
  for (int i = 0; i < kRounds; i++) {
    data.action |= SUSPEND;
    if (!(data.action & SUSPEND)) lost++;
    data.action &= ~SUSPEND;
  }
  clearer.join();

  CHECK(lost == 0);
  CHECK(data.action == 0);
}
