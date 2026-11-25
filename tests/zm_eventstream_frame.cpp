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

#include "zm_config.h"
#include "zm_eventstream.h"
#include "zm_image.h"

#include <cstdio>
#include <cstdlib>
#include <fstream>
#include <iterator>
#include <string>

#include <unistd.h>

namespace {

// Gives a test a loaded event, its frame files and sendFrame without a database.
class FrameEventStream : public EventStream {
 public:
  using EventStream::sendFrame;
  void load(const std::string &dir, int count) {
    event_data = new EventData();
    event_data->start_time = SystemTimePoint(Seconds(1000));
    event_data->end_time = event_data->start_time + Milliseconds(100) * count;
    event_data->duration = Milliseconds(100) * count;
    event_data->frame_count = count;
    for (int i = 0; i < count; i++) {
      event_data->frames.emplace_back(i + 1, event_data->start_time + Milliseconds(100) * i,
                                      Milliseconds(100) * i, Milliseconds(100), true);
    }
    event_data->path = dir;
    event_data->SaveJPEGs = 1;
    setStreamType(STREAM_JPEG);
    scale = 50;
  }
  void at(int id) { curr_frame_id = id; }
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

TEST_CASE("A frame JPEG that will not decode is not sent as the previous frame", "[EventStreamFrame]") {
  // ReadJpeg's result was ignored, so a stored frame with a bad header (a file
  // left full of zeros after a power cut) sent whatever the reused image held:
  // the frame before it, or an empty image for the first.
  config.font_file_location = "data/fonts/04_valid.zmfnt";
  staticConfig.capture_file_format = "%s/%05d-capture.jpg";

  char dir[] = "/tmp/zm_test_event_XXXXXX";
  REQUIRE(mkdtemp(dir) != nullptr);
  const std::string good = std::string(dir) + "/00001-capture.jpg";
  const std::string bad = std::string(dir) + "/00002-capture.jpg";

  Image frame(64, 48, ZM_COLOUR_RGB24, ZM_SUBPIX_ORDER_RGB);
  frame.Clear();
  REQUIRE(frame.WriteJpeg(good.c_str(), 90));
  {
    std::ofstream zeros(bad, std::ios::binary);
    zeros << std::string(64, '\0');
  }

  FrameEventStream stream;
  stream.load(dir, 2);

  stream.at(1);
  const std::string first = CaptureStdout([&] { stream.sendFrame(Microseconds(0)); });
  stream.at(2);
  const std::string second = CaptureStdout([&] { stream.sendFrame(Microseconds(0)); });
  unlink(good.c_str());
  unlink(bad.c_str());
  rmdir(dir);

  CHECK_FALSE(first.empty());
  CHECK(second != first);
}
