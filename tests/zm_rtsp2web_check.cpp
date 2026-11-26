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

class RTSP2WebMonitor : public Monitor {
 public:
  RTSP2WebMonitor() : Monitor() {
    id = 7;
    name = "Front";
    path = "rtsp://192.168.1.10/stream";
  }
  void Create() { manager.reset(new RTSP2WebManager(this)); }
  void Destroy() { manager.reset(); }
  int Add() { return manager->add_to_RTSP2Web(); }
  int Check() { return manager->check_RTSP2Web(); }

 private:
  std::unique_ptr<RTSP2WebManager> manager;
};

std::string g_endpoint;

}  // namespace

// An on-demand channel that nobody is watching reports "status": 0 inside the
// stream info, after the top level "status": 1. That is not a missing stream.
TEST_CASE("RTSP2WebManager: an idle registered stream is not added again", "[RTSP2Web]") {
  std::atomic<bool> added(false);
  FakeHttpServer server([&added](const FakeHttpServer::Request &request) {
    if (request.target.find("/add") != std::string::npos) {
      added = true;
      return std::string("{\n    \"status\": 1,\n    \"payload\": \"success\"\n}");
    }
    if (request.target.find("/info") != std::string::npos) {
      if (added) {
        return std::string(
            "{\n    \"status\": 1,\n    \"payload\": {\n        \"name\": \"Front\",\n"
            "        \"channels\": {\n            \"0\": {\n                \"name\": \"ch1\",\n"
            "                \"url\": \"rtsp://192.168.1.10/stream\",\n"
            "                \"on_demand\": true,\n                \"debug\": false,\n"
            "                \"status\": 0\n            }\n        }\n    }\n}");
      }
      return std::string("{\n    \"status\": 0,\n    \"payload\": \"stream not found\"\n}");
    }
    return std::string("{}");
  });
  g_endpoint = "http://" + server.Url();
  config.rtsp2web_path = g_endpoint.c_str();

  RTSP2WebMonitor monitor;
  monitor.Create();

  // What Monitor::Poll does every 10 seconds.
  for (int poll = 0; poll < 3; poll++) {
    if (monitor.Check() == 0) monitor.Add();
  }
  monitor.Destroy();

  INFO("add requests: " << server.Count("POST", "/stream/7/add"));
  REQUIRE(server.Count("POST", "/stream/7/add") == 1);
}

// A reply that is not RTSP2Web's JSON (an auth proxy's error page, an empty
// body) says nothing about the stream, so it is not "registered".
TEST_CASE("RTSP2WebManager: a reply without a status is not a registered stream", "[RTSP2Web]") {
  FakeHttpServer server([](const FakeHttpServer::Request &) { return std::string(""); });
  g_endpoint = "http://" + server.Url();
  config.rtsp2web_path = g_endpoint.c_str();

  RTSP2WebMonitor monitor;
  monitor.Create();
  int result = monitor.Check();
  monitor.Destroy();

  REQUIRE(result != 1);
}

#endif  // HAVE_LIBCURL
