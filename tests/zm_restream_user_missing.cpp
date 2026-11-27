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
#include "zm_fake_http.h"
#include "zm_monitor.h"

#include <memory>

#if HAVE_LIBCURL

namespace {

class RestreamMonitor : public Monitor {
 public:
  RestreamMonitor() : Monitor() {
    id = 7;
    name = "Front";
    path = "rtsp://192.168.1.10/stream";
    restream = true;
    rtsp_server = true;
    rtsp_streamname = "front";
    // A user that no longer exists.
    rtsp_user = 987654;
    shared_data = &data;
  }
  ~RestreamMonitor() { shared_data = nullptr; }
  void CreateGo2RTC() { go2rtc.reset(new Go2RTCManager(this)); }
  void CreateJanus() { janus.reset(new JanusManager(this)); }

 private:
  SharedData data = {};
  std::unique_ptr<Go2RTCManager> go2rtc;
  std::unique_ptr<JanusManager> janus;
};

std::string g_endpoint;

// Restream authentication is on and the monitor's RTSP user cannot be found
// (here the database is unreachable, which looks the same to User::find).
struct Setup {
  FakeHttpServer server{[](const FakeHttpServer::Request &) { return std::string("{}"); }};
  std::string db_host = staticConfig.DB_HOST;
  Setup() {
    g_endpoint = server.Url();
    config.go2rtc_path = g_endpoint.c_str();
    config.janus_path = g_endpoint.c_str();
    config.janus_secret = "secret";
    config.min_rtsp_port = 8554;
    config.opt_use_auth = true;
    staticConfig.DB_HOST = "127.0.0.1:1";
  }
  ~Setup() {
    staticConfig.DB_HOST = db_host;
    config.opt_use_auth = false;
  }
};

}  // namespace

TEST_CASE("Go2RTCManager: a missing RTSP user does not crash the constructor", "[Go2RTC]") {
  Setup setup;
  RestreamMonitor monitor;
  REQUIRE_NOTHROW(monitor.CreateGo2RTC());
}

TEST_CASE("JanusManager: a missing RTSP user does not crash load_from_monitor", "[Janus]") {
  Setup setup;
  RestreamMonitor monitor;
  REQUIRE_NOTHROW(monitor.CreateJanus());
}

#endif  // HAVE_LIBCURL
