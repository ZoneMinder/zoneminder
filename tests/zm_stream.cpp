/*
 * This file is part of the ZoneMinder Project. See AUTHORS file for contributors.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

#include "zm_catch2.h"

#include "zm_stream.h"

#include <fcntl.h>
#include <unistd.h>

namespace {

// Minimal concrete StreamBase so the base constructor and destructor can be
// exercised without a monitor, a database or shared memory.
class TestStream : public StreamBase {
 public:
  void runStream() override {}

 protected:
  void processCommand(const CmdMsg *) override {}
};

bool fd_is_open(int fd) {
  return fcntl(fd, F_GETFD) != -1;
}

// Destruct a TestStream with the given connkey and report whether fd 0
// survived. fd 0 is restored either way, so a failure here can't cascade into
// the rest of the suite.
bool stdin_survives_destruction(int connkey) {
  REQUIRE(fd_is_open(STDIN_FILENO));
  int saved = dup(STDIN_FILENO);
  REQUIRE(saved != -1);

  {
    TestStream stream;
    if (connkey) stream.setStreamQueue(connkey);
  }

  bool survived = fd_is_open(STDIN_FILENO);
  if (!survived) REQUIRE(dup2(saved, STDIN_FILENO) != -1);
  close(saved);
  return survived;
}

}  // namespace

TEST_CASE("StreamBase comms teardown") {
  SECTION("destructing without openComms() leaves stdin alone") {
    // Regression: lock_fd was initialised to 0 rather than -1, so closeComms()
    // -- reached from ~StreamBase() -- saw `lock_fd >= 0` and called close(0),
    // closing stdin. It only needed connkey > 0 and a runStream() that returned
    // before openComms(). MonitorStream::runStream() does exactly that for
    // STREAM_SINGLE and when the monitor fails to load, and mode=single URLs
    // still carry a connkey.
    REQUIRE(stdin_survives_destruction(123456));
  }

  SECTION("destructing with no connkey leaves stdin alone") {
    // closeComms() is a no-op without a connkey, so this held even before the
    // fix. Here to pin the guard down.
    REQUIRE(stdin_survives_destruction(0));
  }
}
