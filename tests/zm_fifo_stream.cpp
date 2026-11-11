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

#include "zm_fifo_stream.h"

#include <chrono>
#include <csignal>
#include <cstdlib>
#include <fcntl.h>
#include <poll.h>
#include <string>
#include <sys/stat.h>
#include <sys/wait.h>
#include <thread>
#include <unistd.h>

namespace {

class MjpegFifoStream : public FifoStream {
 public:
  explicit MjpegFifoStream(const std::string &path) {
    setStreamStart(path);
    stream_type = MJPEG;
  }
};

}  // namespace

// The diagnostic stream (zms source=fifo) once ran the command thread's loop
// inline, which never returns without a command socket, so it sent nothing.
TEST_CASE("FifoStream sends each frame written to the fifo", "[FifoStream]") {
  char dir[] = "/tmp/zm_fifo_stream_XXXXXX";
  REQUIRE(mkdtemp(dir));
  const std::string fifo = std::string(dir) + "/diag.jpg";
  REQUIRE(mkfifo(fifo.c_str(), 0600) == 0);

  int out[2];
  REQUIRE(pipe(out) == 0);
  pid_t child = fork();
  REQUIRE(child >= 0);
  if (child == 0) {
    dup2(out[1], STDOUT_FILENO);
    close(out[0]);
    alarm(10);
    MjpegFifoStream stream(fifo);
    stream.runStream();
    _exit(0);
  }
  close(out[1]);

  const auto deadline = std::chrono::steady_clock::now() + std::chrono::seconds(5);
  std::string sent;
  int frames_written = 0;
  while (frames_written < 3 and std::chrono::steady_clock::now() < deadline) {
    // Non blocking, so a stream that never opens the fifo cannot hang the test
    int fd = open(fifo.c_str(), O_WRONLY | O_NONBLOCK);
    if (fd < 0) {
      std::this_thread::sleep_for(std::chrono::milliseconds(10));
      continue;
    }
    const std::string frame = "frame" + std::to_string(frames_written);
    REQUIRE(write(fd, frame.data(), frame.size()) == static_cast<ssize_t>(frame.size()));
    close(fd);
    frames_written++;

    // Wait for the stream to send it before writing the next one
    pollfd pfd = {out[0], POLLIN, 0};
    while (sent.find(frame) == std::string::npos and poll(&pfd, 1, 1000) > 0) {
      char buf[4096];
      ssize_t n = read(out[0], buf, sizeof(buf));
      if (n <= 0) break;
      sent.append(buf, n);
    }
  }

  kill(child, SIGKILL);
  waitpid(child, nullptr, 0);
  close(out[0]);
  unlink(fifo.c_str());
  unlink((fifo + ".rlock").c_str());
  rmdir(dir);

  CHECK(frames_written == 3);
  CHECK(sent.find("Content-Type: image/jpeg\r\nContent-Length: 6\r\n\r\nframe0") != std::string::npos);
  CHECK(sent.find("frame1") != std::string::npos);
  CHECK(sent.find("frame2") != std::string::npos);
}
