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
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

#include "zm_catch2.h"

#include "zm_buffer.h"
#include "zm_remote_camera_http.h"

#include <climits>
#include <cstring>
#include <unistd.h>

// A Remote/HTTP camera's Content-Length decides how much zmc reads into a Buffer. Buffer did
// its size arithmetic in int, so a length near 2^31 wrapped the allocation to a small block
// and the read or the copy ran past it. refs GHSA-x959-xrc9-89p8

namespace {

struct Pipe {
  int fds[2] = {-1, -1};
  Pipe() { REQUIRE(pipe(fds) == 0); }
  ~Pipe() {
    if (fds[0] >= 0) close(fds[0]);
    if (fds[1] >= 0) close(fds[1]);
  }
  void Write(const std::string &s) { REQUIRE(write(fds[1], s.data(), s.size()) == static_cast<ssize_t>(s.size())); }
};

}  // namespace

TEST_CASE("Buffer::read_into reads what arrives", "[Buffer]") {
  Pipe p;
  p.Write("hello");
  Buffer buffer(4);
  REQUIRE(buffer.read_into(p.fds[0], 16) == 5);
  REQUIRE(buffer.size() == 5);
  REQUIRE(std::memcmp(buffer.head(), "hello", 5) == 0);
}

TEST_CASE("Buffer::read_into refuses a read the buffer cannot hold", "[Buffer]") {
  Pipe p;
  p.Write("hello");
  Buffer buffer(16);
  // More than an int can count: before the fix this allocated about 2 GiB and then read.
  REQUIRE(buffer.read_into(p.fds[0], static_cast<unsigned int>(INT_MAX) + 1u) < 0);
  REQUIRE(buffer.size() == 0);
  // The data is still there for a sensible read.
  REQUIRE(buffer.read_into(p.fds[0], 16) == 5);
}

TEST_CASE("Buffer::expand keeps the data when it moves or grows", "[Buffer]") {
  Buffer buffer(8);
  buffer.append("abcdefgh", 8);
  buffer.consume(6);  // "gh" left, with 6 bytes of head space

  // Fits once the data is moved to the front of the allocation.
  buffer.expand(5);
  REQUIRE(buffer.size() == 2);
  REQUIRE(std::memcmp(buffer.head(), "gh", 2) == 0);
  REQUIRE(buffer.tail() == buffer.head() + 2);

  // Needs a bigger allocation.
  buffer.expand(100);
  REQUIRE(buffer.size() == 2);
  REQUIRE(std::memcmp(buffer.head(), "gh", 2) == 0);
  buffer.append(std::string(100, 'x').c_str(), 100);
  REQUIRE(buffer.size() == 102);
}

TEST_CASE("RemoteCameraHttp::ParseContentLength bounds the announced length", "[RemoteCameraHttp]") {
  const unsigned long long image_size = 640 * 480 * 4;

  REQUIRE(RemoteCameraHttp::ParseContentLength("1000", image_size) == 1000);
  REQUIRE(RemoteCameraHttp::ParseContentLength("0", image_size) == 0);
  REQUIRE(RemoteCameraHttp::ParseContentLength("1000\r\n", image_size) == 1000);
  // A frame can't be bigger than twice its raw size.
  REQUIRE(RemoteCameraHttp::ParseContentLength("2457600", image_size) == 2457600);
  REQUIRE(RemoteCameraHttp::ParseContentLength("50000000", image_size) < 0);
  REQUIRE(RemoteCameraHttp::ParseContentLength("2147483647", image_size) < 0);
  REQUIRE(RemoteCameraHttp::ParseContentLength("99999999999999999999", image_size) < 0);
  REQUIRE(RemoteCameraHttp::ParseContentLength("-5", image_size) < 0);
  REQUIRE(RemoteCameraHttp::ParseContentLength("abc", image_size) < 0);
  REQUIRE(RemoteCameraHttp::ParseContentLength("", image_size) < 0);
  // A huge configured frame still yields a length an int can hold.
  REQUIRE(RemoteCameraHttp::ParseContentLength("2147483647", 4000000000ull) < 0);
}
