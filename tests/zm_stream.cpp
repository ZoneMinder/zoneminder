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

#include "zm_config.h"

#include <sys/stat.h>
#include <unistd.h>

#include <cstdlib>
#include <string>

namespace {

// Minimal concrete StreamBase so the base constructor, openComms() and the
// destructor can be exercised without a monitor, a database or shared memory.
class TestStream : public StreamBase {
 public:
  void runStream() override {}

 protected:
  void processCommand(const CmdMsg *) override {}
};

}  // namespace

TEST_CASE("StreamBase comms are named after the stream's owner") {
  // ajax/stream.php reaches a zms only through these names
  // (zmsSocketPath() in web/includes/functions.php builds the same ones), so
  // a request from another user, who gets another owner id, finds no socket.
  char dir_template[] = "/tmp/zm.stream_owner.XXXXXX";
  REQUIRE(mkdtemp(dir_template) != nullptr);
  const std::string dir(dir_template);
  const std::string saved_socks = staticConfig.PATH_SOCKS;
  staticConfig.PATH_SOCKS = dir;

  auto exists = [](const std::string &path) {
    struct stat st;
    return stat(path.c_str(), &st) == 0;
  };

  {
    TestStream stream;
    stream.setStreamQueue(4242);
    stream.setStreamOwner(7);
    stream.openComms();
    REQUIRE(exists(dir + "/zms-004242-7s.sock"));
    REQUIRE(exists(dir + "/zms-004242-7.lock"));
    REQUIRE_FALSE(exists(dir + "/zms-004242s.sock"));

    // Another owner on the same connkey gets its own lock and socket instead
    // of waiting on this stream's lock and then taking over its socket.
    TestStream other;
    other.setStreamQueue(4242);
    other.setStreamOwner(8);
    other.openComms();
    REQUIRE(exists(dir + "/zms-004242-8s.sock"));
    REQUIRE(exists(dir + "/zms-004242-7s.sock"));
  }

  {
    TestStream stream;
    stream.setStreamQueue(4242);
    stream.openComms();
    REQUIRE(exists(dir + "/zms-004242-0s.sock"));
  }

  staticConfig.PATH_SOCKS = saved_socks;
  for (const char *name : {"zms-004242-7.lock", "zms-004242-8.lock", "zms-004242-0.lock",
                           "zms-004242-7s.sock", "zms-004242-8s.sock", "zms-004242-0s.sock"}) {
    unlink((dir + "/" + name).c_str());
  }
  rmdir(dir.c_str());
}
