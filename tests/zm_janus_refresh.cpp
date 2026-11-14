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

class JanusMonitor : public Monitor {
 public:
  JanusMonitor() : Monitor() {
    id = 7;
    name = "Front";
    path = "rtsp://192.168.1.10/stream";
    restream = true;
    rtsp_streamname = "front";
    shared_data = &data;
  }
  ~JanusMonitor() { shared_data = nullptr; }
  void Create() { manager.reset(new JanusManager(this)); }
  void Destroy() { manager.reset(); }
  int Check() { return manager->check_janus(); }
  int Add() { return manager->add_to_janus(); }

 private:
  SharedData data = {};
  std::unique_ptr<JanusManager> manager;
};

std::string g_endpoint;

std::string JanusReply(const std::string &id) {
  return "{\n   \"janus\": \"success\",\n   \"transaction\": \"randomString\",\n   \"data\": {\n      \"id\": " +
         id + "\n   }\n}";
}

}  // namespace

// Monitor::Poll adds the stream again whenever check_janus() returns 0, and
// add_to_janus() returns 0 when it succeeded.
TEST_CASE("JanusManager: refreshing a restream mountpoint creates it once", "[Janus]") {
  FakeHttpServer server([](const FakeHttpServer::Request &request) {
    if (request.target == "/janus") return JanusReply("1234567890123456");
    if (request.target == "/janus/1234567890123456") return JanusReply("6543210987654321");
    return std::string("{\"janus\": \"success\"}");
  });
  g_endpoint = server.Url() + "/janus";
  config.janus_path = g_endpoint.c_str();
  config.janus_secret = "secret";
  config.min_rtsp_port = 8554;
  config.opt_use_auth = false;
  config.auth_hash_ttl = 0;  // every check is due for a refresh

  JanusMonitor monitor;
  monitor.Create();

  // What Monitor::Poll does.
  if (monitor.Check() == 0) monitor.Add();

  size_t creates = 0;
  for (const FakeHttpServer::Request &r : server.Requests()) {
    if (r.body.find("\"request\" : \"create\"") != std::string::npos) creates++;
  }
  monitor.Destroy();
  INFO("create requests: " << creates);
  REQUIRE(creates == 1);
}

#endif  // HAVE_LIBCURL
