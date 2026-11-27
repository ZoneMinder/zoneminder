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
#include "zm_time.h"

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
    // A user that does not exist (the database is unreachable in these tests).
    rtsp_user = 987654;
    shared_data = &data;
  }
  ~JanusMonitor() { shared_data = nullptr; }
  void Create() { manager.reset(new AgedJanusManager(this)); }
  void Destroy() { manager.reset(); }
  // Make the restream URL old enough that a hash in it would be due for renewal.
  void Age() { manager->Age(Hours(2)); }
  int Check() { return manager->check_janus(); }
  int Add() { return manager->add_to_janus(); }
  const char *Pin() const { return data.janus_pin; }

 private:
  class AgedJanusManager : public JanusManager {
   public:
    using JanusManager::JanusManager;
    void Age(Hours hours) { rtsp_auth_time -= hours; }
  };
  SharedData data = {};
  std::unique_ptr<AgedJanusManager> manager;
};

std::string g_endpoint;
const char *g_pin = "";

std::string JanusReply(const std::string &id) {
  return "{\n   \"janus\": \"success\",\n   \"transaction\": \"randomString\",\n   \"data\": {\n      \"id\": " +
         id + "\n   }\n}";
}

// The info reply for the mountpoint, carrying its current pin.
std::string JanusHandler(const FakeHttpServer::Request &request) {
  if (request.target == "/janus") return JanusReply("1234567890123456");
  if (request.target == "/janus/1234567890123456") return JanusReply("6543210987654321");
  return std::string("{\"janus\": \"success\", \"plugindata\": {\"data\": {\"info\": {\"pin\": \"") +
         g_pin + "\"}}}}";
}

size_t CountRequests(FakeHttpServer &server, const std::string &request) {
  size_t n = 0;
  for (const FakeHttpServer::Request &r : server.Requests()) {
    if (r.body.find("\"request\" : \"" + request + "\"") != std::string::npos) n++;
  }
  return n;
}

}  // namespace

// Monitor::Poll adds the stream again whenever check_janus() returns 0, and
// add_to_janus() returns 0 when it succeeded.
TEST_CASE("JanusManager: refreshing a restream mountpoint creates it once", "[Janus]") {
  FakeHttpServer server(JanusHandler);
  g_endpoint = server.Url() + "/janus";
  config.janus_path = g_endpoint.c_str();
  config.janus_secret = "secret";
  config.min_rtsp_port = 8554;
  config.opt_use_auth = true;
  config.auth_hash_ttl = 2;
  std::string db_host = staticConfig.DB_HOST;
  staticConfig.DB_HOST = "127.0.0.1:1";

  JanusMonitor monitor;
  monitor.Create();
  g_pin = monitor.Pin();
  monitor.Age();

  // What Monitor::Poll does.
  if (monitor.Check() == 0) monitor.Add();

  // The refresh remounts without querying the mountpoint first.
  size_t infos = CountRequests(server, "info");
  size_t destroys = CountRequests(server, "destroy");
  size_t creates = CountRequests(server, "create");
  monitor.Destroy();
  g_pin = "";
  staticConfig.DB_HOST = db_host;
  config.opt_use_auth = false;
  INFO("info: " << infos << ", destroy: " << destroys << ", create: " << creates);
  REQUIRE(infos == 0);
  REQUIRE(destroys == 1);
  REQUIRE(creates == 1);
}

// Without auth the restream URL never changes, and remounting drops every viewer.
TEST_CASE("JanusManager: a restream without auth is not remounted", "[Janus]") {
  FakeHttpServer server(JanusHandler);
  g_endpoint = server.Url() + "/janus";
  config.janus_path = g_endpoint.c_str();
  config.janus_secret = "secret";
  config.min_rtsp_port = 8554;
  config.opt_use_auth = false;
  config.auth_hash_ttl = 2;

  JanusMonitor monitor;
  monitor.Create();
  g_pin = monitor.Pin();
  monitor.Age();

  int result = monitor.Check();

  size_t remounts = CountRequests(server, "destroy") + CountRequests(server, "create");
  monitor.Destroy();
  g_pin = "";
  INFO("destroy and create requests: " << remounts);
  REQUIRE(result == 1);
  REQUIRE(remounts == 0);
}

#endif  // HAVE_LIBCURL
