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
#include "zm_poll_thread.h"

#include <chrono>
#include <thread>

namespace {

void EnsureConfig() {
  if (!config.font_file_location) config.font_file_location = "";
  if (!config.event_close_mode) config.event_close_mode = "idle";
}

// A Monitor with none of the services the poller talks to, so Poll() returns
// straight away.
class IdleMonitor : public Monitor {
 public:
  IdleMonitor() : Monitor() {
    RTSP2Web_Manager = nullptr;
    Go2RTC_Manager = nullptr;
    Janus_Manager = nullptr;
    Amcrest_Manager = nullptr;
  }
};

}  // namespace

// Monitor::Close() stops the poller on every stop, reconnect and reload of
// zmc. The poller used to sleep out a 10 s interval after each Poll(), so
// Stop() waited for whatever was left of it.
TEST_CASE("PollThread::Stop does not wait out the poll interval", "[Monitor]") {
  EnsureConfig();
  IdleMonitor monitor;
  PollThread poller(&monitor);
  // Let it make its first Poll() and start waiting for the next one.
  std::this_thread::sleep_for(std::chrono::milliseconds(200));

  const std::chrono::steady_clock::time_point start = std::chrono::steady_clock::now();
  poller.Stop();
  REQUIRE(std::chrono::steady_clock::now() - start < std::chrono::seconds(2));
  REQUIRE(poller.Stopped());
}
