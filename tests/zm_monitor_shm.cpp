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

#include <chrono>
#include <cstdlib>
#include <string>
#include <sys/stat.h>
#include <unistd.h>

// Monitor::connect() returns false with shared_data still null on every one of
// its failure paths: the mmap file cannot be opened (wrong ownership after a
// package upgrade), fstat fails, ftruncate cannot grow it (/dev/shm out of
// space, which a container with the default 64MB tmpfs hits quickly -- one
// 720x480 monitor with 10 buffers already asks for ~20MB), or mmap itself
// fails.
//
// zmc's startup loop reacts to that by retrying:
//
//     while (!monitor->connect() and !zm_terminate) {
//       Warning("Couldn't connect to monitor %d", monitor->Id());
//       monitor->SetHeartbeatTime(std::chrono::system_clock::now());
//       sleep(1);
//     }
//
// so the very first thing it does after a failed connect is write through the
// null pointer, and zmc dies with SIGSEGV at address 0x80 instead of retrying.
// The surrounding accessors in zm_monitor.h already guard with
// `if (shared_data && shared_data->valid)`; these did not.
namespace {

void EnsureConfig() {
  if (!config.font_file_location) config.font_file_location = "";
  if (!config.event_close_mode) config.event_close_mode = "idle";
}

// A Monitor that has never connected, so shared_data is null -- exactly the
// state zmc is in while its retry loop spins.
class UnconnectedMonitor : public Monitor {
 public:
  UnconnectedMonitor() : Monitor() {}
};

}  // namespace

TEST_CASE("Monitor shm time accessors are safe before connect()", "[Monitor]") {
  EnsureConfig();
  UnconnectedMonitor monitor;

  REQUIRE_FALSE(monitor.isConnected());

  SECTION("SetHeartbeatTime does not fault (zmc's connect retry loop)") {
    REQUIRE_NOTHROW(monitor.SetHeartbeatTime(std::chrono::system_clock::now()));
  }

  SECTION("SetStartupTime does not fault") {
    REQUIRE_NOTHROW(monitor.SetStartupTime(std::chrono::system_clock::now()));
  }

  SECTION("GetStartupTime does not fault") {
    REQUIRE_NOTHROW(monitor.GetStartupTime());
  }
}

// zmc used to call umask(0) before creating its map file and never put it
// back, so every file it made afterwards (logs, event videos) was world
// writable.
TEST_CASE("Creating the monitor map file leaves the umask as it was", "[Monitor]") {
  char dir[] = "/tmp/zm_mapfile_XXXXXX";
  REQUIRE(mkdtemp(dir) != nullptr);
  std::string path = std::string(dir) + "/zm.mmap.1";

  mode_t old_umask = umask(022);
  int fd = OpenMonitorMapFile(path.c_str(), true);
  mode_t after = umask(old_umask);
  REQUIRE(fd >= 0);
  close(fd);

  REQUIRE(after == 022);

  struct stat st;
  REQUIRE(stat(path.c_str(), &st) == 0);
  REQUIRE((st.st_mode & 0777) == 0660);

  unlink(path.c_str());
  rmdir(dir);
}
