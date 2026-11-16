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

#include "zm_fake_http.h"
#include "zm_monitor.h"

#include <atomic>
#include <memory>

#if HAVE_LIBCURL

namespace {

class Go2RTCMonitor : public Monitor {
 public:
  Go2RTCMonitor() : Monitor() {
    id = 7;
    name = "Front";
    path = "rtsp://192.168.1.10/stream?channel=1&subtype=0";
    device_class = "Camera";
  }
  void Create() { manager.reset(new Go2RTCManager(this)); }
  void Destroy() { manager.reset(); }
  int Add() { return manager->add_to_Go2RTC(); }
  int Check() { return manager->check_Go2RTC(); }

 private:
  std::unique_ptr<Go2RTCManager> manager;
};

std::string g_endpoint;

}  // namespace

// go2rtc writes its JSON with Go's encoding/json, which escapes & < > as \u0026
// \u003c \u003e, so the stream list never holds a camera URL's & verbatim.
TEST_CASE("Go2RTCManager: a registered stream whose URL has an & is found again", "[Go2RTC]") {
  std::atomic<bool> added(false);
  FakeHttpServer server([&added](const FakeHttpServer::Request &request) {
    if (request.method == "PUT") {
      added = true;
      return std::string("{}");
    }
    if (request.method == "GET" && added) {
      return std::string("{\"producers\":[{\"url\":\"rtsp://192.168.1.10/stream?channel=1\\u0026subtype=0\"}]}");
    }
    return std::string("{}");
  });
  g_endpoint = server.Url();
  config.go2rtc_path = g_endpoint.c_str();

  Go2RTCMonitor monitor;
  monitor.Create();

  // What Monitor::Poll does every 10 seconds.
  for (int poll = 0; poll < 3; poll++) {
    if (monitor.Check() == 0) monitor.Add();
  }
  monitor.Destroy();

  INFO("PUT requests with name=7&: " << server.Count("PUT", "/api/streams?name=7&"));
  REQUIRE(server.Count("PUT", "/api/streams?name=7&") == 1);
}

#endif  // HAVE_LIBCURL
