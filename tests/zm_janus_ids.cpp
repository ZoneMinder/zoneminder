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

#include <algorithm>
#include <memory>

#if HAVE_LIBCURL

namespace {

class JanusMonitor : public Monitor {
 public:
  JanusMonitor() : Monitor() {
    id = 7;
    name = "Front";
    path = "rtsp://192.168.1.10/stream";
    shared_data = &data;
  }
  ~JanusMonitor() { shared_data = nullptr; }
  void Create() { manager.reset(new JanusManager(this)); }
  void Destroy() { manager.reset(); }
  int Check() { return manager->check_janus(); }

 private:
  SharedData data = {};
  std::unique_ptr<JanusManager> manager;
};

std::string g_endpoint;

// Janus pretty-prints its replies.
std::string JanusReply(const std::string &id) {
  return "{\n   \"janus\": \"success\",\n   \"transaction\": \"randomString\",\n   \"data\": {\n      \"id\": " +
         id + "\n   }\n}";
}

}  // namespace

TEST_CASE("JanusManager: session and handle ids are read whole", "[Janus]") {
  FakeHttpServer server([](const FakeHttpServer::Request &request) {
    if (request.target == "/janus") return JanusReply("12345");
    if (request.target == "/janus/12345") return JanusReply("678");
    return std::string("{\"janus\": \"success\"}");
  });
  g_endpoint = server.Url() + "/janus";
  config.janus_path = g_endpoint.c_str();
  config.janus_secret = "secret";

  JanusMonitor monitor;
  monitor.Create();
  monitor.Check();

  std::vector<std::string> targets;
  std::string seen;
  for (const FakeHttpServer::Request &r : server.Requests()) {
    targets.push_back(r.target);
    seen += r.method + " " + r.target + "\n";
  }
  INFO("requests:\n" << seen);
  REQUIRE(std::find(targets.begin(), targets.end(), "/janus/12345/678") != targets.end());
}

#endif  // HAVE_LIBCURL
