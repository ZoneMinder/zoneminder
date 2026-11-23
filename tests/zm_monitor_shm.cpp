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
#include <sys/mman.h>

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

#if ZM_MEM_MAPPED
namespace {

// A Monitor whose shared memory is an anonymous mapping, so disconnect() can unmap it.
class MappedMonitor : public Monitor {
 public:
  MappedMonitor() : Monitor() {}
  bool MapAnonymous() {
    mem_size = 4096;
    void *p = mmap(nullptr, mem_size, PROT_READ | PROT_WRITE, MAP_SHARED | MAP_ANONYMOUS, -1, 0);
    if (p == MAP_FAILED) return false;
    mem_ptr = static_cast<unsigned char *>(p);
    shared_data = reinterpret_cast<SharedData *>(mem_ptr);
    trigger_data = reinterpret_cast<TriggerData *>(mem_ptr + 1024);
    trigger_data->trigger_state = TRIGGER_ON;
    return true;
  }
  bool HasTriggerData() const { return trigger_data != nullptr; }
  bool TriggerIsOn() const { return GetTriggerState() == TRIGGER_ON; }
  bool TriggerIsCancel() const { return GetTriggerState() == TRIGGER_CANCEL; }
};

}  // namespace

// disconnect() unmapped the segment but left trigger_data (and the other pointers into it)
// set, so GetTriggerState() read unmapped memory afterwards.
TEST_CASE("Monitor::disconnect clears the pointers into the unmapped shared memory", "[Monitor]") {
  EnsureConfig();
  MappedMonitor monitor;
  REQUIRE(monitor.MapAnonymous());
  REQUIRE(monitor.TriggerIsOn());
  REQUIRE(monitor.disconnect());
  REQUIRE_FALSE(monitor.HasTriggerData());
  REQUIRE(monitor.TriggerIsCancel());
}
#endif
