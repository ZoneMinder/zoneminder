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

#include "zm_regexp.h"
#include "zm_remote_camera.h"
#include "zm_remote_camera_http.h"
#include "zm_signal.h"
#include <arpa/inet.h>
#include <netinet/in.h>
#include <sys/socket.h>
#include <unistd.h>
#include <atomic>
#include <chrono>
#include <thread>
#include <cstring>

TEST_CASE("SplitUserInfo") {
  std::string host = "bob:secret@cam.local";
  std::string user, pass;
  REQUIRE(SplitUserInfo(host, user, pass));
  REQUIRE(host == "cam.local");
  REQUIRE(user == "bob");
  REQUIRE(pass == "secret");

  host = "bob@cam.local";
  user = ""; pass = "";
  REQUIRE(SplitUserInfo(host, user, pass));
  REQUIRE(host == "cam.local");
  REQUIRE(user == "bob");
  REQUIRE(pass.empty());

  host = "bob:pa:ss@cam.local";
  user = ""; pass = "";
  REQUIRE(SplitUserInfo(host, user, pass));
  REQUIRE(host == "cam.local");
  REQUIRE(user == "bob");
  REQUIRE(pass == "pa:ss");

  host = "cam.local";
  user = "u"; pass = "p";
  REQUIRE_FALSE(SplitUserInfo(host, user, pass));
  REQUIRE(host == "cam.local");
  REQUIRE(user == "u");
  REQUIRE(pass == "p");
}

#if HAVE_LIBPCRE
TEST_CASE("RegExpr match indexes") {
  RegExpr re("^(a)(b)?(c)?");
  const char *s = "ab";
  REQUIRE(re.Match(s, strlen(s)) == 3);
  REQUIRE(std::string(re.MatchString(1)) == "a");
  REQUIRE(std::string(re.MatchString(2)) == "b");
  // index equal to the match count is not a participating group
  REQUIRE(re.MatchString(3) == nullptr);
  REQUIRE(re.MatchLength(3) == 0);
  REQUIRE(re.MatchString(-1) == nullptr);
  REQUIRE(re.MatchString(1000) == nullptr);
}

TEST_CASE("RegExpr failed match leaves no stale data") {
  RegExpr re("(x+)");
  const char *s = "axxb";
  REQUIRE(re.Match(s, strlen(s)) == 2);
  REQUIRE(std::string(re.MatchString(0)) == "xx");
  const char *t = "none";
  REQUIRE(re.Match(t, strlen(t)) == 0);
  REQUIRE(re.MatchString(0) == nullptr);
  REQUIRE(re.MatchLength(0) == 0);
  REQUIRE(re.MatchString(1) == nullptr);
}

TEST_CASE("RemoteCameraHttp::QuoteRegex matches a multipart boundary literally", "[RemoteCameraHttp]") {
  REQUIRE(RemoteCameraHttp::QuoteRegex("frame") == "frame");
  REQUIRE(RemoteCameraHttp::QuoteRegex("a(b)+c?") == "a\\(b\\)\\+c\\?");

  // RFC 2046 allows ( ) + . ? and more in a boundary.
  for (const char *boundary : {"frame", "a(b)c", "frame+1", "what?", "x(", "=_Part_(1)+2/3:4,5'6"}) {
    const std::string pattern = "^(?:--)?" + RemoteCameraHttp::QuoteRegex(boundary) + "\r?\n";
    RegExpr re(pattern.c_str());
    const std::string line = std::string("--") + boundary + "\r\n";
    REQUIRE(re.Match(line.c_str(), line.size()) == 1);
  }

  RegExpr dot(("^" + RemoteCameraHttp::QuoteRegex("a.b") + "$").c_str());
  REQUIRE(dot.Match("axb", 3) == 0);
}
#endif

namespace {

// Answers one request with the given bytes, then closes the connection.
class OneShotServer {
 public:
  explicit OneShotServer(std::string reply) : reply_(std::move(reply)) {
    fd_ = socket(AF_INET, SOCK_STREAM, 0);
    sockaddr_in addr = {};
    addr.sin_family = AF_INET;
    addr.sin_addr.s_addr = htonl(INADDR_LOOPBACK);
    bind(fd_, reinterpret_cast<sockaddr *>(&addr), sizeof(addr));
    socklen_t len = sizeof(addr);
    getsockname(fd_, reinterpret_cast<sockaddr *>(&addr), &len);
    port_ = ntohs(addr.sin_port);
    listen(fd_, 1);
    thread_ = std::thread([this] {
      int c = accept(fd_, nullptr, nullptr);
      if (c < 0) return;
      std::string req;
      char buf[1024];
      while (req.find("\r\n\r\n") == std::string::npos) {
        ssize_t n = recv(c, buf, sizeof(buf), 0);
        if (n <= 0) break;
        req.append(buf, n);
      }
      send(c, reply_.data(), reply_.size(), MSG_NOSIGNAL);
      close(c);
    });
  }
  ~OneShotServer() {
    shutdown(fd_, SHUT_RDWR);
    close(fd_);
    thread_.join();
  }
  std::string Port() const { return std::to_string(port_); }

 private:
  std::string reply_;
  int fd_;
  int port_;
  std::thread thread_;
};

// GetResponse() result for the reply, with a watchdog that sets zm_terminate if it never returns.
int HttpResponseFor(const std::string &reply, bool &watchdog_fired, const char *method = "simple") {
  config.http_timeout = 300;
  config.watch_max_delay = 1;
  config.http_version = "1.0";
  config.http_ua = "test";
  OneShotServer server(reply);
  RemoteCameraHttp camera(nullptr, method, "127.0.0.1", server.Port(), "/", "", "",
                          4, 4, 3, 0, 0, 0, 0, false, false);
  camera.Initialise();
  REQUIRE(camera.Connect() >= 0);
  REQUIRE(camera.SendRequest() == 0);

  std::atomic<bool> done{false};
  std::thread watchdog([&] {
    for (int i = 0; i < 40 && !done; i++) std::this_thread::sleep_for(std::chrono::milliseconds(100));
    if (!done) {
      watchdog_fired = true;
      zm_terminate = true;
    }
  });
  int result = camera.GetResponse();
  done = true;
  watchdog.join();
  zm_terminate = false;
  camera.Disconnect();
  return result;
}

}  // namespace

TEST_CASE("HTTP camera: an empty single image body is an error, not an endless read", "[RemoteCamera]") {
  bool watchdog_fired = false;
  SECTION("Content-Length: 0") {
    int r = HttpResponseFor("HTTP/1.0 200 OK\r\nContent-Type: image/jpeg\r\nContent-Length: 0\r\n\r\n", watchdog_fired);
    REQUIRE_FALSE(watchdog_fired);
    REQUIRE(r < 0);
  }
  SECTION("no Content-Length and no body") {
    int r = HttpResponseFor("HTTP/1.0 200 OK\r\nContent-Type: image/jpeg\r\n\r\n", watchdog_fired);
    REQUIRE_FALSE(watchdog_fired);
    REQUIRE(r < 0);
  }
}

#if HAVE_LIBPCRE
TEST_CASE("HTTP camera regexp parser: a single image without Content-Length ends when the camera closes", "[RemoteCamera]") {
  bool watchdog_fired = false;
  const std::string body = "\xff\xd8JPEGDATA\xff\xd9";
  SECTION("body") {
    int r = HttpResponseFor("HTTP/1.0 200 OK\r\nContent-Type: image/jpeg\r\n\r\n" + body, watchdog_fired, "regexp");
    REQUIRE_FALSE(watchdog_fired);
    REQUIRE(r == static_cast<int>(body.size()));
  }
  SECTION("no body") {
    int r = HttpResponseFor("HTTP/1.0 200 OK\r\nContent-Type: image/jpeg\r\n\r\n", watchdog_fired, "regexp");
    REQUIRE_FALSE(watchdog_fired);
    REQUIRE(r < 0);
  }
}
#endif
