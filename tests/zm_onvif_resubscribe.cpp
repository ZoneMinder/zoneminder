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

#ifdef WITH_GSOAP

#include "zm_monitor.h"
#include "zm_monitor_onvif.h"

#include <arpa/inet.h>
#include <netinet/in.h>
#include <poll.h>
#include <sys/socket.h>
#include <unistd.h>

#include <atomic>
#include <functional>
#include <string>
#include <thread>

namespace {

// A just-enough ONVIF event service on 127.0.0.1. Each request is classified as
// "create", "pull" or "unsubscribe" and the handler says what to answer, given
// how many of that kind it has seen. Not a camera, no real one is contacted.
class FakeCamera {
 public:
  struct Reply {
    int status;
    std::string body;
  };
  using Handler = std::function<Reply(const std::string &kind, int count)>;

  explicit FakeCamera(Handler h) : handler_(std::move(h)) {
    fd_ = socket(AF_INET, SOCK_STREAM, 0);
    int one = 1;
    setsockopt(fd_, SOL_SOCKET, SO_REUSEADDR, &one, sizeof(one));
    sockaddr_in addr{};
    addr.sin_family = AF_INET;
    addr.sin_addr.s_addr = htonl(INADDR_LOOPBACK);
    addr.sin_port = 0;
    bind(fd_, reinterpret_cast<sockaddr *>(&addr), sizeof(addr));
    socklen_t len = sizeof(addr);
    getsockname(fd_, reinterpret_cast<sockaddr *>(&addr), &len);
    port_ = ntohs(addr.sin_port);
    listen(fd_, 8);
    thread_ = std::thread(&FakeCamera::serve, this);
  }
  ~FakeCamera() {
    stop_ = true;
    thread_.join();
    close(fd_);
  }

  int port() const { return port_; }
  int count(const std::string &kind) const {
    return kind == "create" ? creates_ : kind == "pull" ? pulls_ : kind == "renew" ? renews_ : unsubscribes_;
  }

  static std::string envelope(const std::string &body) {
    return "<?xml version=\"1.0\" encoding=\"UTF-8\"?>"
           "<SOAP-ENV:Envelope xmlns:SOAP-ENV=\"http://www.w3.org/2003/05/soap-envelope\""
           " xmlns:tev=\"http://www.onvif.org/ver10/events/wsdl\""
           " xmlns:wsnt=\"http://docs.oasis-open.org/wsn/b-2\""
           " xmlns:wsa5=\"http://www.w3.org/2005/08/addressing\""
           " xmlns:tt=\"http://www.onvif.org/ver10/schema\""
           " xmlns:tns1=\"http://www.onvif.org/ver10/topics\">"
           "<SOAP-ENV:Body>" + body + "</SOAP-ENV:Body></SOAP-ENV:Envelope>";
  }
  Reply create_ok() const {
    return {200, envelope("<tev:CreatePullPointSubscriptionResponse><tev:SubscriptionReference>"
                          "<wsa5:Address>http://127.0.0.1:" + std::to_string(port_) + "/sub</wsa5:Address>"
                          "</tev:SubscriptionReference></tev:CreatePullPointSubscriptionResponse>")};
  }
  static Reply unsubscribe_ok() { return {200, envelope("<wsnt:UnsubscribeResponse/>")}; }
  static Reply renew_ok() { return {200, envelope("<wsnt:RenewResponse/>")}; }
  static Reply pull_empty() { return {200, envelope("<tev:PullMessagesResponse/>")}; }
  static Reply fault() {
    return {500, envelope("<SOAP-ENV:Fault><SOAP-ENV:Code><SOAP-ENV:Value>SOAP-ENV:Receiver</SOAP-ENV:Value>"
                          "</SOAP-ENV:Code><SOAP-ENV:Reason><SOAP-ENV:Text xml:lang=\"en\">ResourceUnknown"
                          "</SOAP-ENV:Text></SOAP-ENV:Reason></SOAP-ENV:Fault>")};
  }
  // One notification for the motion topic. operation: Initialized/Changed/Deleted.
  static Reply pull_motion(const std::string &operation, const std::string &state) {
    return {200, envelope(
        "<tev:PullMessagesResponse><wsnt:NotificationMessage>"
        "<wsnt:Topic Dialect=\"http://www.onvif.org/ver10/tev/topicExpression/ConcreteSet\">"
        "tns1:VideoSource/MotionAlarm</wsnt:Topic><wsnt:Message>"
        "<tt:Message UtcTime=\"2026-11-16T10:00:00Z\" PropertyOperation=\"" + operation + "\">"
        "<tt:Source><tt:SimpleItem Name=\"Source\" Value=\"VS\"/></tt:Source>"
        "<tt:Data><tt:SimpleItem Name=\"State\" Value=\"" + state + "\"/></tt:Data>"
        "</tt:Message></wsnt:Message></wsnt:NotificationMessage></tev:PullMessagesResponse>")};
  }

 private:
  void serve() {
    while (!stop_) {
      pollfd p{fd_, POLLIN, 0};
      if (poll(&p, 1, 50) <= 0) continue;
      int c = accept(fd_, nullptr, nullptr);
      if (c < 0) continue;
      std::string req;
      char buf[4096];
      size_t header_end = std::string::npos;
      size_t want = 0;
      while (true) {
        pollfd cp{c, POLLIN, 0};
        if (poll(&cp, 1, 2000) <= 0) break;
        ssize_t n = read(c, buf, sizeof(buf));
        if (n <= 0) break;
        req.append(buf, n);
        if (header_end == std::string::npos) {
          header_end = req.find("\r\n\r\n");
          if (header_end != std::string::npos) {
            size_t cl = req.find("Content-Length: ");
            if (cl == std::string::npos) cl = req.find("content-length: ");
            want = header_end + 4 + (cl == std::string::npos ? 0 : std::stoul(req.substr(cl + 16)));
          }
        }
        if (header_end != std::string::npos && req.size() >= want) break;
      }
      std::string kind = req.find("Unsubscribe") != std::string::npos ? "unsubscribe"
                       : req.find("PullMessages") != std::string::npos ? "pull"
                       : req.find("Renew") != std::string::npos ? "renew" : "create";
      int n = kind == "create" ? ++creates_ : kind == "pull" ? ++pulls_
            : kind == "renew" ? ++renews_ : ++unsubscribes_;
      Reply r = handler_(kind, n);
      std::string out = "HTTP/1.1 " + std::to_string(r.status) + " X\r\n"
                        "Content-Type: application/soap+xml; charset=utf-8\r\n"
                        "Content-Length: " + std::to_string(r.body.size()) + "\r\n"
                        "Connection: close\r\n\r\n" + r.body;
      size_t off = 0;
      while (off < out.size()) {
        ssize_t w = write(c, out.data() + off, out.size() - off);
        if (w <= 0) break;
        off += w;
      }
      close(c);
    }
  }

  Handler handler_;
  int fd_;
  int port_;
  std::thread thread_;
  std::atomic<bool> stop_{false};
  std::atomic<int> creates_{0}, pulls_{0}, renews_{0}, unsubscribes_{0};
};

// Drives the poll thread's steps by hand, against a FakeCamera.
class TestONVIF : public ONVIF {
 public:
  TestONVIF(Monitor *m, int port) : ONVIF(m) {
    event_endpoint_url_ = "http://127.0.0.1:" + std::to_string(port) + "/Events";
    proxyEvent.soap_endpoint = event_endpoint_url_.c_str();
  }
  void subscribe() { Subscribe(); }
  void wait() { WaitForMessage(); }
  int retries() const { return retry_count; }
  int retry_delay() { return get_retry_delay(); }
  void set_max_retries(int n) { max_retries = n; }
};

}  // namespace

// Answers create/unsubscribe like a healthy camera and leaves pulls to `pull`,
// which is given the 1-based pull count.
static FakeCamera::Handler camera_script(FakeCamera **self,
                                         std::function<FakeCamera::Reply(int)> pull,
                                         bool renew_fails = false) {
  return [self, pull, renew_fails](const std::string &kind, int n) -> FakeCamera::Reply {
    if (kind == "create") return (*self)->create_ok();
    if (kind == "unsubscribe") return FakeCamera::unsubscribe_ok();
    if (kind == "renew") return renew_fails ? FakeCamera::fault() : FakeCamera::renew_ok();
    return pull(n);
  };
}

TEST_CASE("ONVIF does not keep an alarm across a lost subscription", "[onvif]") {
  // The camera reports motion, then loses the subscription (reboot, network
  // drop). While the poll thread is reconnecting nothing can say the motion
  // ended, and the camera's new subscription does not repeat an "off" it never
  // sent, so the old alarm stayed up and kept the event recording.
  FakeCamera *cp = nullptr;
  FakeCamera cam(camera_script(&cp, [](int n) -> FakeCamera::Reply {
    if (n == 2) return FakeCamera::pull_motion("Changed", "true");
    if (n == 3) return FakeCamera::fault();
    return FakeCamera::pull_empty();  // 1: initial pull, 4: initial pull of the new subscription
  }));
  cp = &cam;
  Monitor monitor;
  TestONVIF onvif(&monitor, cam.port());

  onvif.subscribe();
  REQUIRE(onvif.isHealthy());
  onvif.wait();
  REQUIRE(onvif.isAlarmed());

  onvif.wait();  // the subscription is gone
  REQUIRE_FALSE(onvif.isHealthy());
  onvif.observeAlarm();
  REQUIRE_FALSE(onvif.isAlarmed());

  onvif.subscribe();  // the camera is back and is not in alarm
  REQUIRE(onvif.isHealthy());
  onvif.observeAlarm();
  REQUIRE_FALSE(onvif.isAlarmed());
}

TEST_CASE("ONVIF does not keep an alarm when the renewal fails", "[onvif]") {
  // The camera reports motion and then refuses the Renew that follows the pull,
  // so the subscription is dropped. Until a new one is created nothing can say
  // the motion ended, and a camera that stays unreachable left the alarm up.
  FakeCamera *cp = nullptr;
  FakeCamera cam(camera_script(&cp, [](int n) -> FakeCamera::Reply {
    if (n == 2) return FakeCamera::pull_motion("Changed", "true");
    return FakeCamera::pull_empty();
  }, true));
  cp = &cam;
  Monitor monitor;
  TestONVIF onvif(&monitor, cam.port());

  onvif.subscribe();
  REQUIRE(onvif.isHealthy());
  onvif.wait();  // the motion arrives, then the renewal is refused
  REQUIRE(cam.count("renew") >= 1);
  REQUIRE_FALSE(onvif.isHealthy());
  onvif.observeAlarm();
  REQUIRE_FALSE(onvif.isAlarmed());
}

TEST_CASE("ONVIF uses the state in the first pull of a subscription", "[onvif]") {
  // A camera that is already in alarm when ZoneMinder (re)subscribes reports it
  // as Initialized in the first PullMessages. That pull used to be discarded, so
  // the alarm was missed until the camera next changed state.
  FakeCamera *cp = nullptr;
  FakeCamera cam(camera_script(&cp, [](int n) -> FakeCamera::Reply {
    if (n == 1) return FakeCamera::pull_motion("Initialized", "true");
    return FakeCamera::pull_empty();
  }));
  cp = &cam;
  Monitor monitor;
  TestONVIF onvif(&monitor, cam.port());

  onvif.subscribe();
  REQUIRE(onvif.isHealthy());
  REQUIRE(onvif.isAlarmed());
}

TEST_CASE("ONVIF backs off when the first pull of a subscription fails", "[onvif]") {
  // The subscription is created but the camera refuses to be pulled. Creating it
  // used to reset the retry count, so every cycle waited the 1 second minimum
  // and a new subscription was created each time, for ever.
  FakeCamera *cp = nullptr;
  FakeCamera cam(camera_script(&cp, [](int) -> FakeCamera::Reply { return FakeCamera::fault(); }));
  cp = &cam;
  Monitor monitor;
  TestONVIF onvif(&monitor, cam.port());

  onvif.subscribe();
  REQUIRE_FALSE(onvif.isHealthy());
  REQUIRE(onvif.retries() == 1);
  onvif.subscribe();
  REQUIRE_FALSE(onvif.isHealthy());
  REQUIRE(onvif.retries() == 2);
  REQUIRE(onvif.retry_delay() == 4);
}

TEST_CASE("ONVIF backs off when the subscription response has no address", "[onvif]") {
  // A CreatePullPointSubscription answer with an empty SubscriptionReference
  // left the address null, and copying it into a std::string crashed zmc.
  FakeCamera cam([](const std::string &kind, int) -> FakeCamera::Reply {
    if (kind == "create") {
      return {200, FakeCamera::envelope("<tev:CreatePullPointSubscriptionResponse>"
                                        "<tev:SubscriptionReference/>"
                                        "</tev:CreatePullPointSubscriptionResponse>")};
    }
    return FakeCamera::pull_empty();
  });
  Monitor monitor;
  TestONVIF onvif(&monitor, cam.port());

  onvif.subscribe();
  REQUIRE_FALSE(onvif.isHealthy());
  REQUIRE(onvif.retries() == 1);
}

TEST_CASE("ONVIF with max_retries 0 still makes the first attempt", "[onvif]") {
  // max_retries=0 means no retries. The poll thread compared the retry count to
  // it before the first attempt, so it sat out the 5 minute cool-down first and
  // the monitor had no ONVIF events for that long after every start.
  FakeCamera *cp = nullptr;
  FakeCamera cam(camera_script(&cp, [](int) -> FakeCamera::Reply { return FakeCamera::pull_empty(); }));
  cp = &cam;
  Monitor monitor;
  TestONVIF onvif(&monitor, cam.port());
  onvif.set_max_retries(0);

  onvif.start();
  for (int i = 0; i < 30 and cam.count("create") == 0; i++)
    std::this_thread::sleep_for(std::chrono::milliseconds(100));
  REQUIRE(cam.count("create") >= 1);
}

namespace {

class OptionsMonitor : public Monitor {
 public:
  explicit OptionsMonitor(const std::string &options) { onvif_options = options; }
};

class OptionsONVIF : public ONVIF {
 public:
  explicit OptionsONVIF(Monitor *m) : ONVIF(m) {}
  int pull_timeout() const { return pull_timeout_seconds; }
  int subscription_timeout() const { return subscription_timeout_seconds; }
  int retries() const { return max_retries; }
};

}  // namespace

TEST_CASE("ONVIF options separated by a comma and a space are all applied", "[onvif]") {
  OptionsMonitor monitor("pull_timeout=5, subscription_timeout=120, max_retries=5");
  OptionsONVIF onvif(&monitor);

  REQUIRE(onvif.pull_timeout() == 5);
  REQUIRE(onvif.subscription_timeout() == 120);
  REQUIRE(onvif.retries() == 5);
}

TEST_CASE("ONVIF option names may have spaces around them", "[onvif]") {
  OptionsMonitor monitor("pull_timeout = 5 ,subscription_timeout =120");
  OptionsONVIF onvif(&monitor);

  REQUIRE(onvif.pull_timeout() == 5);
  REQUIRE(onvif.subscription_timeout() == 120);
}

#endif  // WITH_GSOAP
