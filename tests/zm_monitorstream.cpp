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

#include "zm_monitorstream.h"

#include "zm_config.h"
#include "zm_monitor.h"
#include "zm_zone.h"

#include <climits>
#include <cstdio>
#include <fstream>
#include <iterator>
#include <memory>
#include <string>
#include <vector>

#include <unistd.h>

TEST_CASE("MonitorStreamBufferLevel") {
  SECTION("zero buffer count does not divide by zero") {
    // Regression for zoneminder/zoneminder#4936: processCommand() could run on
    // the command thread before runStream() assigned temp_image_buffer_count,
    // leaving it 0 while playback_buffer was already > 0, causing a SIGFPE.
    REQUIRE(MonitorStreamBufferLevel(0, 0, 0) == 0);
    REQUIRE(MonitorStreamBufferLevel(5, 2, 0) == 0);
    REQUIRE(MonitorStreamBufferLevel(0, 5, 0) == 0);
  }

  SECTION("negative buffer count is treated as empty") {
    REQUIRE(MonitorStreamBufferLevel(3, 1, -10) == 0);
  }

  SECTION("empty buffer reports 0%") {
    REQUIRE(MonitorStreamBufferLevel(0, 0, 100) == 0);
  }

  SECTION("full buffer reports nearly 100%") {
    // write wraps around to one behind read => count-1 of count slots used
    REQUIRE(MonitorStreamBufferLevel(99, 0, 100) == 99);
  }

  SECTION("half full buffer reports ~50%") {
    REQUIRE(MonitorStreamBufferLevel(50, 0, 100) == 50);
  }

  SECTION("wrap-around (write behind read) is handled modularly") {
    // write_index - read_index is negative; modular arithmetic keeps it in range
    REQUIRE(MonitorStreamBufferLevel(2, 7, 10) == 50);
  }

  SECTION("a read that straddles an update still reports in range") {
    // zoneminder/zoneminder#4939: the command thread reads the three values as
    // separate atomics, so it can pick up a write index from one moment and a
    // read index from another. That stays harmless for this percentage as long
    // as each index is individually within [0, count): the +count before the
    // modulo covers the whole (-count, count) span the difference can take.
    const int count = 64;
    for (int write_index = 0; write_index < count; write_index++) {
      for (int read_index = 0; read_index < count; read_index++) {
        const int level = MonitorStreamBufferLevel(write_index, read_index, count);
        REQUIRE(level >= 0);
        REQUIRE(level <= 100);
      }
    }
  }
}

namespace {

// No zm.conf is loaded under the test harness, so config strings are null.
// Image::Initialise() dereferences config.font_file_location and the Monitor
// constructor strcmp()s config.event_close_mode; both segfault on null.
void EnsureConfig() {
  if (!config.font_file_location) config.font_file_location = "";
  if (!config.event_close_mode) config.event_close_mode = "idle";
}

// Monitor's dimensions and zones are protected and normally come from the
// database.
class TestMonitor : public Monitor {
 public:
  TestMonitor(unsigned int w, unsigned int h) : Monitor() {
    width = w;
    height = h;
    colours = ZM_COLOUR_GRAY8;
  }
  // A full-frame zone. Like every Zone, it holds a shared_ptr to this monitor.
  static void AddZone(const std::shared_ptr<TestMonitor> &monitor) {
    const int w = monitor->width, h = monitor->height;
    std::vector<Vector2> vertices = {
        Vector2(0, 0), Vector2(w - 1, 0), Vector2(w - 1, h - 1), Vector2(0, h - 1)};
    monitor->zones.emplace_back(monitor, 1, "full", Zone::ACTIVE, Polygon(vertices));
  }
};

// The stream's monitor is protected; nph-zms sets it through loadMonitor(),
// which needs the database. Hand one in directly instead.
class TestMonitorStream : public MonitorStream {
 public:
  using MonitorStream::sendFrame;
  void setMonitor(const std::shared_ptr<Monitor> &p_monitor) {
    monitor = p_monitor;
  }
  void setView(int p_scale, int p_zoom) {
    scale = p_scale;
    zoom = p_zoom;
  }
  void setClick(unsigned short p_x, unsigned short p_y) {
    x = p_x;
    y = p_y;
  }
  const Box &crop() const { return last_crop; }
};

// What a call writes to stdout, through a temporary file.
template <typename F>
std::string CaptureStdout(F f) {
  char path[] = "/tmp/zm_test_stdout_XXXXXX";
  int fd = mkstemp(path);
  REQUIRE(fd >= 0);
  fflush(stdout);
  int saved = dup(fileno(stdout));
  dup2(fd, fileno(stdout));
  f();
  fflush(stdout);
  dup2(saved, fileno(stdout));
  close(saved);
  close(fd);
  std::ifstream in(path, std::ios::binary);
  std::string out((std::istreambuf_iterator<char>(in)), std::istreambuf_iterator<char>());
  unlink(path);
  return out;
}

}  // namespace

TEST_CASE("MonitorStream releases its monitor when destroyed", "[Stream]") {
  // Each Zone holds a shared_ptr back to its Monitor, so a monitor with zones
  // keeps itself alive after the stream drops its reference. nph-zms leaked
  // the monitor, its zones and their full-frame images on every exit until
  // ~StreamBase called disconnect() to break the cycle, as zmc does.
  EnsureConfig();
  std::weak_ptr<Monitor> watched;

  {
    TestMonitorStream stream;
    {
      auto monitor = std::make_shared<TestMonitor>(64, 48);
      TestMonitor::AddZone(monitor);
      watched = monitor;
      stream.setMonitor(monitor);
    }
    REQUIRE_FALSE(watched.expired());
  }

  REQUIRE(watched.expired());
}

TEST_CASE("MonitorStream sends stored JPEGs as multipart parts", "[Stream]") {
  // Buffered playback at scale 100 without zoom sends the swap file as it is
  // (sendFrame(filepath)'s raw path). Each part needs the boundary and its
  // Content-Type like the frames sendFrame(Image *) writes, or the
  // multipart/x-mixed-replace stream breaks during rewind or step.
  EnsureConfig();
  config.timestamp_on_capture = true;

  char jpeg_path[] = "/tmp/zm_test_swap_XXXXXX";
  int fd = mkstemp(jpeg_path);
  REQUIRE(fd >= 0);
  const std::string jpeg = "\xff\xd8not really a jpeg\xff\xd9";
  REQUIRE(write(fd, jpeg.data(), jpeg.size()) == static_cast<ssize_t>(jpeg.size()));
  close(fd);

  TestMonitorStream stream;
  stream.setStreamType(StreamBase::STREAM_JPEG);
  bool sent = false;
  const std::string out = CaptureStdout([&] {
    sent = stream.sendFrame(std::string(jpeg_path), SystemTimePoint(Seconds(1700000000)));
  });
  unlink(jpeg_path);

  REQUIRE(sent);
  const std::string head = "--" BOUNDARY "\r\nContent-Type: image/jpeg\r\nContent-Length: " +
                           std::to_string(jpeg.size()) + "\r\n";
  REQUIRE(out.compare(0, head.size(), head) == 0);
  REQUIRE(out.find(jpeg) != std::string::npos);
}

TEST_CASE("MonitorStream starts a new zoom from the whole frame after stored JPEGs", "[Stream]") {
  // Buffered playback at scale 100 without zoom sends the swap files as they
  // are, without prepareImage. Zooming out there must still drop the crop of
  // the earlier zoom, or the next zoom-in click is mapped through it.
  EnsureConfig();
  config.timestamp_on_capture = true;
  const SystemTimePoint timestamp(Seconds(1700000000));
  Image frame(640, 480, ZM_COLOUR_RGB32, ZM_SUBPIX_ORDER_RGBA);
  frame.Clear();

  char jpeg_path[] = "/tmp/zm_test_swap_XXXXXX";
  int fd = mkstemp(jpeg_path);
  REQUIRE(fd >= 0);
  const std::string jpeg = "\xff\xd8not really a jpeg\xff\xd9";
  REQUIRE(write(fd, jpeg.data(), jpeg.size()) == static_cast<ssize_t>(jpeg.size()));
  close(fd);

  TestMonitorStream fresh;
  fresh.setStreamType(StreamBase::STREAM_JPEG);
  fresh.setView(ZM_SCALE_BASE, 110);
  fresh.setClick(320, 240);
  CaptureStdout([&] { fresh.sendFrame(&frame, timestamp); });

  TestMonitorStream used;
  used.setStreamType(StreamBase::STREAM_JPEG);
  bool sent = false;
  CaptureStdout([&] {
    used.setView(ZM_SCALE_BASE, 110);
    used.setClick(600, 440);
    used.sendFrame(&frame, timestamp);
    used.setView(ZM_SCALE_BASE, ZM_SCALE_BASE);
    sent = used.sendFrame(std::string(jpeg_path), timestamp);
    used.setView(ZM_SCALE_BASE, 110);
    used.setClick(320, 240);
    used.sendFrame(&frame, timestamp);
  });
  unlink(jpeg_path);

  REQUIRE(sent);
  CHECK(used.crop().Lo().x_ == fresh.crop().Lo().x_);
  CHECK(used.crop().Lo().y_ == fresh.crop().Lo().y_);
  CHECK(used.crop().Hi().x_ == fresh.crop().Hi().x_);
  CHECK(used.crop().Hi().y_ == fresh.crop().Hi().y_);
}

TEST_CASE("MonitorStreamClampBuffer") {
  SECTION("a request above the monitor's replay buffer is cut to it") {
    REQUIRE(MonitorStreamClampBuffer(20000000, 100) == 100);
    REQUIRE(MonitorStreamClampBuffer(2000000000, 25) == 25);
  }

  SECTION("a request within the replay buffer is kept") {
    REQUIRE(MonitorStreamClampBuffer(50, 100) == 50);
    REQUIRE(MonitorStreamClampBuffer(100, 100) == 100);
  }

  SECTION("no buffer when either value is not positive") {
    REQUIRE(MonitorStreamClampBuffer(0, 100) == 0);
    REQUIRE(MonitorStreamClampBuffer(-5, 100) == 0);
    REQUIRE(MonitorStreamClampBuffer(INT_MIN, 100) == 0);
    REQUIRE(MonitorStreamClampBuffer(50, 0) == 0);
    REQUIRE(MonitorStreamClampBuffer(50, -1) == 0);
    REQUIRE(MonitorStreamClampBuffer(2000000000, 0) == 0);
  }
}
