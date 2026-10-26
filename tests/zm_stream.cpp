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
#include "zm_image.h"
#include "zm_signal.h"

#include <cerrno>
#include <cstdlib>
#include <cstring>
#include <fcntl.h>
#include <sys/socket.h>
#include <sys/un.h>
#include <thread>
#include <unistd.h>

namespace {

// Minimal concrete StreamBase so the base constructor and destructor can be
// exercised without a monitor, a database or shared memory.
class TestStream : public StreamBase {
 public:
  void runStream() override {}

  // prepareImage and the view state it reads are protected; expose just enough
  // to drive it from a test.
  Image *prepare(Image *image, int pre_scaled_by = 0) {
    return prepareImage(image, pre_scaled_by);
  }
  int preScale(int base_width, int base_height, int &width, int &height) {
    return preScaleDimensions(base_width, base_height, width, height);
  }
  void setView(int p_scale, int p_zoom) {
    scale = p_scale;
    zoom = p_zoom;
  }
  void commandLoop() {
    checkCommandQueue();
  }

 protected:
  void processCommand(const CmdMsg *) override {}
};

// Image::Initialise() dereferences config.font_file_location, which is null in
// the unit-test harness (no zm.conf loaded). Give it a non-null value so the
// first Image construction doesn't throw. Same guard as zm_image_linesize.cpp.
void EnsureImageInit() {
  if (!config.font_file_location) config.font_file_location = "";
}

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

// Send one datagram to path and return 0, or the errno sendto() failed with.
int send_to_socket(const std::string &path) {
  int fd = socket(AF_UNIX, SOCK_DGRAM, 0);
  REQUIRE(fd >= 0);
  sockaddr_un addr = {};
  addr.sun_family = AF_UNIX;
  strncpy(addr.sun_path, path.c_str(), sizeof(addr.sun_path) - 1);
  char byte = 0;
  int rc = sendto(fd, &byte, 1, MSG_DONTWAIT, reinterpret_cast<sockaddr *>(&addr), sizeof(addr));
  int err = rc < 0 ? errno : 0;
  close(fd);
  return err;
}

}  // namespace

TEST_CASE("StreamBase closes its command socket when the command thread stops") {
  // Regression: the command socket stayed bound until ~StreamBase(), which runs
  // only after the stream thread has finished. A zms that was shutting down but
  // still blocked in the stream thread therefore accepted commands it would
  // never answer, and web/ajax/stream.php waited out its full 5 second
  // MSG_TIMEOUT on each one ("Timed out waiting for msg").
  char dir[] = "/tmp/zm_stream_test_XXXXXX";
  REQUIRE(mkdtemp(dir) != nullptr);
  const std::string saved_socks = staticConfig.PATH_SOCKS;
  staticConfig.PATH_SOCKS = dir;
  const std::string sock_path = std::string(dir) + "/zms-123457s.sock";

  {
    TestStream stream;
    stream.setStreamQueue(123457);
    stream.openComms();
    REQUIRE(access(sock_path.c_str(), F_OK) == 0);
    REQUIRE(send_to_socket(sock_path) == 0);

    zm_terminate = false;
    std::thread command_thread(&TestStream::commandLoop, &stream);
    zm_terminate = true;
    command_thread.join();

    // The stream object, and so its destructor's closeComms(), is still alive.
    CHECK(access(sock_path.c_str(), F_OK) != 0);
    CHECK(send_to_socket(sock_path) != 0);
  }

  zm_terminate = false;
  staticConfig.PATH_SOCKS = saved_socks;
  unlink((std::string(dir) + "/zms-123457.lock").c_str());
  unlink(sock_path.c_str());
  rmdir(dir);
}

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


TEST_CASE("StreamBase::prepareImage pre-scaled frames") {
  // zms used to colour convert an mp4 frame to full size RGBA and then scale
  // that down to the requested size, so every streamed frame paid for two
  // sws_scale passes and a full frame copy. sws_scale converts and resizes in
  // one pass, so the decode side now produces the image at the size being sent
  // and tells prepareImage it has already been scaled. refs #3681
  EnsureImageInit();
  const unsigned int kWidth = 640;
  const unsigned int kHeight = 480;

  SECTION("a pre-scaled image is passed through untouched") {
    TestStream stream;
    stream.setView(50, ZM_SCALE_BASE);
    Image image(kWidth / 2, kHeight / 2, ZM_COLOUR_RGB32, ZM_SUBPIX_ORDER_RGBA);

    Image *sent = stream.prepare(&image, 50);

    // Same object, not a scaled copy: nothing was resized and nothing copied.
    REQUIRE(sent == &image);
    REQUIRE(sent->Width() == kWidth / 2);
    REQUIRE(sent->Height() == kHeight / 2);
  }

  SECTION("a full size image is still scaled") {
    TestStream stream;
    stream.setView(50, ZM_SCALE_BASE);
    Image image(kWidth, kHeight, ZM_COLOUR_RGB32, ZM_SUBPIX_ORDER_RGBA);

    Image *sent = stream.prepare(&image, 0);

    REQUIRE(sent != &image);  // scaled into the copy, caller's image intact
    REQUIRE(image.Width() == kWidth);
    REQUIRE(sent->Width() == kWidth / 2);
    REQUIRE(sent->Height() == kHeight / 2);
  }

  SECTION("a scale changed since the decode is not applied a second time") {
    // The command thread can change scale after the decode side read it. The
    // image in hand is a real 25% frame; scaling it again by the new 50% would
    // send 80x60 rather than either the 160x120 it is or the 320x240 now
    // wanted. It goes out as built and the next frame uses the new scale.
    TestStream stream;
    stream.setView(50, ZM_SCALE_BASE);
    Image image((kWidth * 25) / 100, (kHeight * 25) / 100,
                ZM_COLOUR_RGB32, ZM_SUBPIX_ORDER_RGBA);

    Image *sent = stream.prepare(&image, 25);

    REQUIRE(sent == &image);
    REQUIRE(sent->Width() == (kWidth * 25) / 100);
    REQUIRE(sent->Height() == (kHeight * 25) / 100);
  }

  SECTION("a zoom turned on since the decode does not crop the reduced image") {
    // Same race, the other field. The zoom branch derives its base geometry and
    // its crop from the image's own dimensions, so handing it a half sized frame
    // would both halve the output again and record last_crop in the wrong
    // coordinate space, which would then be wrong for every later frame.
    TestStream stream;
    stream.setView(50, 150);
    Image image(kWidth / 2, kHeight / 2, ZM_COLOUR_RGB32, ZM_SUBPIX_ORDER_RGBA);

    Image *sent = stream.prepare(&image, 50);

    REQUIRE(sent == &image);
    REQUIRE(sent->Width() == kWidth / 2);
    REQUIRE(sent->Height() == kHeight / 2);
  }

  SECTION("unscaled streaming is unaffected") {
    TestStream stream;
    stream.setView(ZM_SCALE_BASE, ZM_SCALE_BASE);
    Image image(kWidth, kHeight, ZM_COLOUR_RGB32, ZM_SUBPIX_ORDER_RGBA);

    Image *sent = stream.prepare(&image, 0);

    REQUIRE(sent == &image);
    REQUIRE(sent->Width() == kWidth);
  }

  SECTION("converting at the target size lands on the size Scale would have") {
    // The whole optimisation rests on these agreeing: preScaleDimensions
    // computes (dimension * scale) / ZM_SCALE_BASE, which has to match what
    // Image::Scale(scale) produces, or pre-scaled frames would go out at a
    // different size than they used to.
    for (unsigned int scale : {25u, 33u, 50u, 75u, 150u}) {
      Image scaled(kWidth, kHeight, ZM_COLOUR_RGB32, ZM_SUBPIX_ORDER_RGBA);
      scaled.Scale(scale);

      CAPTURE(scale);
      REQUIRE(scaled.Width() == (kWidth * scale) / ZM_SCALE_BASE);
      REQUIRE(scaled.Height() == (kHeight * scale) / ZM_SCALE_BASE);
    }
  }
}

TEST_CASE("StreamBase::preScaleDimensions") {
  const int kWidth = 640;
  const int kHeight = 480;
  int width = -1, height = -1;

  SECTION("reports the scaled size when only a scale is set") {
    TestStream stream;
    stream.setView(50, ZM_SCALE_BASE);

    REQUIRE(stream.preScale(kWidth, kHeight, width, height) == 50);
    REQUIRE(width == 320);
    REQUIRE(height == 240);
  }

  SECTION("declines while a zoom is active") {
    // Zoom crops before scaling and needs the full resolution to crop out of.
    TestStream stream;
    stream.setView(50, 150);

    REQUIRE(stream.preScale(kWidth, kHeight, width, height) == 0);
  }

  SECTION("declines at the base scale, where there is nothing to fold in") {
    TestStream stream;
    stream.setView(ZM_SCALE_BASE, ZM_SCALE_BASE);

    REQUIRE(stream.preScale(kWidth, kHeight, width, height) == 0);
  }

  SECTION("declines a scale that would produce a zero dimension") {
    // CMD_SCALE stores the two bytes the client sent without validating them,
    // so 0 reaches here. Image::Scale(0) refuses the factor; asking swscale for
    // a 0x0 frame would instead take a negative buffer size into an unsigned
    // field and allocate on it.
    TestStream stream;
    stream.setView(0, ZM_SCALE_BASE);

    REQUIRE(stream.preScale(kWidth, kHeight, width, height) == 0);

    // Also when the scale is nonzero but too small to survive the division.
    stream.setView(1, ZM_SCALE_BASE);
    REQUIRE(stream.preScale(4, 4, width, height) == 0);
  }

  SECTION("leaves the out params alone when it declines") {
    TestStream stream;
    stream.setView(0, ZM_SCALE_BASE);
    width = kWidth;
    height = kHeight;

    REQUIRE(stream.preScale(kWidth, kHeight, width, height) == 0);
    REQUIRE(width == kWidth);
    REQUIRE(height == kHeight);
  }
}
