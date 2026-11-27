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
#include "zm_image.h"
#include "zm_packet.h"
#include "zm_remote_camera.h"
#include "zm_remote_camera_http.h"
#include "zm_signal.h"
#include <arpa/inet.h>
#include <netinet/in.h>
#include <sys/socket.h>
#include <unistd.h>
#include <atomic>
#include <chrono>
#include <memory>
#include <string>
#include <thread>
#include <vector>
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

// An HTTP reply carrying a w x h JPEG.
std::string JpegReply(unsigned int w, unsigned int h) {
  Image source(w, h, ZM_COLOUR_RGB24, ZM_SUBPIX_ORDER_RGB);
  source.Fill(0x00808080);
  std::vector<JOCTET> jpeg(1 << 20);
  size_t jpeg_size = 0;
  REQUIRE(source.EncodeJpeg(jpeg.data(), &jpeg_size));
  std::string reply = "HTTP/1.0 200 OK\r\nContent-Type: image/jpeg\r\nContent-Length: " +
                      std::to_string(jpeg_size) + "\r\n\r\n";
  reply.append(reinterpret_cast<const char *>(jpeg.data()), jpeg_size);
  return reply;
}

// Capture() result for a 4x4 monitor served the reply; the captured image's size goes to *w, *h.
int HttpCaptureFor(const std::string &reply, unsigned int *w = nullptr, unsigned int *h = nullptr) {
  config.http_timeout = 300;
  config.watch_max_delay = 1;
  config.http_version = "1.0";
  config.http_ua = "test";
  OneShotServer server(reply);
  RemoteCameraHttp camera(nullptr, "simple", "127.0.0.1", server.Port(), "/", "", "",
                          4, 4, 3, 0, 0, 0, 0, false, false);
  camera.Initialise();
  REQUIRE(camera.Connect() >= 0);
  REQUIRE(camera.SendRequest() == 0);
  std::shared_ptr<ZMPacket> packet = std::make_shared<ZMPacket>();
  int result = camera.Capture(packet);
  if (w) *w = packet->image ? packet->image->Width() : 0;
  if (h) *h = packet->image ? packet->image->Height() : 0;
  camera.Disconnect();
  return result;
}

}  // namespace

TEST_CASE("HTTP camera: a JPEG of a different size than the monitor is scaled to it", "[RemoteCamera]") {
  unsigned int w = 0, h = 0;
  SECTION("same size is kept") {
    REQUIRE(HttpCaptureFor(JpegReply(4, 4), &w, &h) == 1);
    REQUIRE(w == 4);
    REQUIRE(h == 4);
  }
  SECTION("larger than the monitor") {
    REQUIRE(HttpCaptureFor(JpegReply(8, 8), &w, &h) == 1);
    REQUIRE(w == 4);
    REQUIRE(h == 4);
  }
  SECTION("smaller than the monitor") {
    REQUIRE(HttpCaptureFor(JpegReply(2, 2), &w, &h) == 1);
    REQUIRE(w == 4);
    REQUIRE(h == 4);
  }
}

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
