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
#include "zm_utils.h"

#include <memory>

TEST_CASE("UrlWithCredentials: credentials go after the scheme of any URL", "[IntegrationUrls]") {
  REQUIRE(UrlWithCredentials("rtsp://cam/s", "u", "p") == "rtsp://u:p@cam/s");
  REQUIRE(UrlWithCredentials("rtsps://cam:322/s", "u", "p") == "rtsps://u:p@cam:322/s");
  REQUIRE(UrlWithCredentials("http://cam/s.mjpg", "u", "p") == "http://u:p@cam/s.mjpg");
  REQUIRE(UrlWithCredentials("cam/s", "u", "p") == "rtsp://u:p@cam/s");
}

TEST_CASE("UrlWithCredentials: encodes reserved characters and keeps existing userinfo", "[IntegrationUrls]") {
  REQUIRE(UrlWithCredentials("rtsp://cam/s", "u", "p@ss:w/rd#1") == "rtsp://u:p%40ss%3Aw%2Frd%231@cam/s");
  REQUIRE(UrlWithCredentials("rtsp://old:pw@cam/s", "u", "p") == "rtsp://old:pw@cam/s");
  REQUIRE(UrlWithCredentials("rtsp://cam/s?x=a@b", "u", "p") == "rtsp://u:p@cam/s?x=a@b");
}

#if HAVE_LIBCURL

namespace {

class IntegrationMonitor : public Monitor {
 public:
  IntegrationMonitor() : Monitor() {
    id = 7;
    name = "Front";
    path = "rtsp://192.168.1.10/stream";
    device_class = "Camera";
  }
  void SetPath(const std::string &p) { path = p; }
  void SetCredentials(const std::string &u, const std::string &p) { user = u; pass = p; }
  void CreateGo2RTC() { go2rtc.reset(new Go2RTCManager(this)); }
  int AddGo2RTC() { return go2rtc->add_to_Go2RTC(); }
  void DestroyGo2RTC() { go2rtc.reset(); }
  void CreateRTSP2Web() { rtsp2web.reset(new RTSP2WebManager(this)); }
  int AddRTSP2Web() { return rtsp2web->add_to_RTSP2Web(); }
  void DestroyRTSP2Web() { rtsp2web.reset(); }

 private:
  std::unique_ptr<Go2RTCManager> go2rtc;
  std::unique_ptr<RTSP2WebManager> rtsp2web;
};

std::string g_endpoint;

bool AnyBodyContains(FakeHttpServer &server, const std::string &method, const std::string &needle) {
  for (const FakeHttpServer::Request &r : server.Requests()) {
    if (r.method == method && r.body.find(needle) != std::string::npos) return true;
  }
  return false;
}

}  // namespace

TEST_CASE("Go2RTCManager: an rtsps camera keeps its scheme when credentials are added", "[IntegrationUrls]") {
  FakeHttpServer server([](const FakeHttpServer::Request &) { return std::string("{}"); });
  g_endpoint = server.Url();
  config.go2rtc_path = g_endpoint.c_str();

  IntegrationMonitor monitor;
  monitor.SetPath("rtsps://192.168.1.10:322/stream");
  monitor.SetCredentials("admin", "secret");
  monitor.CreateGo2RTC();
  monitor.AddGo2RTC();
  monitor.DestroyGo2RTC();

  REQUIRE(AnyBodyContains(server, "PUT", "\"src\": \"rtsps://admin:secret@192.168.1.10:322/stream#backchannel=0\""));
  REQUIRE_FALSE(AnyBodyContains(server, "PUT", "rtsp://admin:secret@rtsps://"));
}

TEST_CASE("Go2RTCManager: the backchannel is disabled when the credentials are in the path", "[IntegrationUrls]") {
  FakeHttpServer server([](const FakeHttpServer::Request &) { return std::string("{}"); });
  g_endpoint = server.Url();
  config.go2rtc_path = g_endpoint.c_str();

  IntegrationMonitor monitor;
  monitor.SetPath("rtsp://admin:secret@192.168.1.10/stream");
  monitor.CreateGo2RTC();
  monitor.AddGo2RTC();
  monitor.DestroyGo2RTC();

  REQUIRE(AnyBodyContains(server, "PUT",
                          "\"src\": \"rtsp://admin:secret@192.168.1.10/stream#backchannel=0\""));
}

TEST_CASE("Go2RTCManager: removing a monitor removes every stream it registered", "[IntegrationUrls]") {
  FakeHttpServer server([](const FakeHttpServer::Request &) { return std::string("{}"); });
  g_endpoint = server.Url();
  config.go2rtc_path = g_endpoint.c_str();

  IntegrationMonitor monitor;
  monitor.SetPath("rtsp://192.168.1.10/stream");
  monitor.CreateGo2RTC();
  monitor.AddGo2RTC();
  monitor.DestroyGo2RTC();

  REQUIRE(server.Count("DELETE", "/api/streams?src=7") >= 1);
  REQUIRE(server.Count("DELETE", "/api/streams?src=7_h264") == 1);
  REQUIRE(server.Count("DELETE", "/api/streams?src=7_CameraDirectPrimary") == 1);
}

TEST_CASE("RTSP2WebManager: an rtsps camera keeps its scheme when credentials are added", "[IntegrationUrls]") {
  FakeHttpServer server([](const FakeHttpServer::Request &) {
    return std::string("{\"status\": 1, \"payload\": \"success\"}");
  });
  g_endpoint = "http://" + server.Url();
  config.rtsp2web_path = g_endpoint.c_str();

  IntegrationMonitor monitor;
  monitor.SetPath("rtsps://192.168.1.10:322/stream");
  monitor.SetCredentials("admin", "secret");
  monitor.CreateRTSP2Web();
  monitor.AddRTSP2Web();
  monitor.DestroyRTSP2Web();

  REQUIRE(AnyBodyContains(server, "POST", "\"url\" : \"rtsps://admin:secret@192.168.1.10:322/stream\""));
}

#endif  // HAVE_LIBCURL
