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

#include <memory>

#if HAVE_LIBCURL

namespace {

class JsonMonitor : public Monitor {
 public:
  JsonMonitor() : Monitor() {
    id = 7;
    name = "Fr\"ont\\door";
    path = "rtsp://192.168.1.10/stream?x=\"1\"";
    shared_data = &data;
  }
  ~JsonMonitor() { shared_data = nullptr; }
  void SetCredentials() {
    user = "admin";
    pass = "p\"w";
  }
  void CreateRTSP2Web() { rtsp2web.reset(new RTSP2WebManager(this)); }
  int AddRTSP2Web() { return rtsp2web->add_to_RTSP2Web(); }
  void CreateJanus() { janus.reset(new JanusManager(this)); }
  int AddJanus() { return janus->add_to_janus(); }
  void Destroy() {
    rtsp2web.reset();
    janus.reset();
  }

 private:
  SharedData data = {};
  std::unique_ptr<RTSP2WebManager> rtsp2web;
  std::unique_ptr<JanusManager> janus;
};

std::string g_endpoint;

std::string JanusReply(const std::string &id) {
  return "{\n   \"janus\": \"success\",\n   \"transaction\": \"randomString\",\n   \"data\": {\n      \"id\": " +
         id + "\n   }\n}";
}

std::string BodyOf(FakeHttpServer &server, const std::string &method, const std::string &needle) {
  for (const FakeHttpServer::Request &r : server.Requests()) {
    if (r.method == method && r.body.find(needle) != std::string::npos) return r.body;
  }
  return "";
}

}  // namespace

TEST_CASE("RTSP2WebManager: the stream name and URLs are escaped in the request", "[RTSP2Web]") {
  FakeHttpServer server([](const FakeHttpServer::Request &) { return std::string("{\"status\": 1}"); });
  g_endpoint = server.Url();
  config.rtsp2web_path = g_endpoint.c_str();

  JsonMonitor monitor;
  monitor.SetCredentials();
  monitor.CreateRTSP2Web();
  REQUIRE(monitor.AddRTSP2Web() == 0);
  monitor.Destroy();

  std::string body = BodyOf(server, "POST", "\"channels\"");
  INFO("body: " << body);
  // Each of these is the JSON form of the raw value, escaped exactly once.
  REQUIRE(body.find("\"name\" : \"Fr\\\"ont\\\\door\"") != std::string::npos);
  REQUIRE(body.find("\"url\" : \"rtsp://admin:p\\\"w@192.168.1.10/stream?x=\\\"1\\\"\"") != std::string::npos);
}

TEST_CASE("JanusManager: the camera URL is escaped in the create request", "[Janus]") {
  FakeHttpServer server([](const FakeHttpServer::Request &request) {
    if (request.target == "/janus") return JanusReply("1234567890123456");
    if (request.target == "/janus/1234567890123456") return JanusReply("6543210987654321");
    return std::string("{\"janus\": \"success\"}");
  });
  g_endpoint = server.Url() + "/janus";
  config.janus_path = g_endpoint.c_str();
  config.janus_secret = "secret";

  JsonMonitor monitor;
  monitor.CreateJanus();
  REQUIRE(monitor.AddJanus() == 0);
  monitor.Destroy();

  std::string body = BodyOf(server, "POST", "\"request\" : \"create\"");
  INFO("body: " << body);
  REQUIRE(body.find("\"url\" : \"rtsp://192.168.1.10/stream?x=\\\"1\\\"\"") != std::string::npos);
}

#endif  // HAVE_LIBCURL
