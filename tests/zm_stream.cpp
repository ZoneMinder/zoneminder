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

#include <atomic>
#include <cerrno>
#include <chrono>
#include <cstdlib>
#include <cstring>
#include <fcntl.h>
#include <limits>
#include <memory>
#include <sys/socket.h>
#include <sys/stat.h>
#include <sys/un.h>
#include <thread>
#include <unistd.h>
#include <vector>
#include <zlib.h>

#include <cstdlib>
#include <string>
#include <vector>

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
  size_t zip(const Image &image) { return zipImageToTempBuffer(image); }
  const uint8_t *tempBuffer() const { return temp_img_buffer; }
  int preScale(int base_width, int base_height, int &width, int &height) {
    return preScaleDimensions(base_width, base_height, width, height);
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
  int replayRate() const { return replay_rate; }
  void commandLoop() {
    checkCommandQueue();
  }
  void updateRate(double fps) { updateFrameRate(fps); }
  int frameMod() const { return frame_mod; }

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
  const std::string sock_path = std::string(dir) + "/zms-123457-0s.sock";

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
  unlink((std::string(dir) + "/zms-123457-0.lock").c_str());
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

#if HAVE_ZLIB_H
TEST_CASE("StreamBase zips an image that does not compress into a large enough buffer") {
  // A random picture is larger once compressed than raw, so a buffer of Size() bytes is too small.
  if (!config.font_file_location) config.font_file_location = "";
  Image image(64, 64, ZM_COLOUR_GRAY8, ZM_SUBPIX_ORDER_NONE);
  unsigned int seed = 12345;
  for (unsigned int i = 0; i < image.Size(); i++) {
    seed = seed * 1103515245u + 12345u;
    image.Buffer()[i] = static_cast<uint8_t>(seed >> 16);
  }
  TestStream stream;
  size_t zipped = stream.zip(image);
  REQUIRE(zipped > 0);
  REQUIRE(zipped <= compressBound(image.Size()));
  std::vector<uint8_t> out(image.Size());
  uLongf out_len = out.size();
  REQUIRE(uncompress(out.data(), &out_len, stream.tempBuffer(), zipped) == Z_OK);
  REQUIRE(out_len == image.Size());
  REQUIRE(std::memcmp(out.data(), image.Buffer(), image.Size()) == 0);
}
#endif

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

TEST_CASE("StreamBase::prepareImage keeps a zoom across a change of resolution") {
  // Event playback can move on to another monitor's event. The crop is kept in
  // the previous frame's pixels; a 1280x960 crop does not fit a 640x480 frame,
  // so Crop refused it on every frame and the zoom was lost.
  EnsureImageInit();
  TestStream stream;
  stream.setView(ZM_SCALE_BASE, 150);
  stream.setClick(640, 480);
  Image large(1280, 960, ZM_COLOUR_RGB32, ZM_SUBPIX_ORDER_RGBA);
  stream.prepare(&large);
  const Box first = stream.crop();
  REQUIRE(first.Hi().x_ > 639);

  stream.setClick(320, 240);
  Image small(640, 480, ZM_COLOUR_RGB32, ZM_SUBPIX_ORDER_RGBA);
  Image *sent = stream.prepare(&small);

  const Box &second = stream.crop();
  CHECK(second.Hi().x_ <= 639);
  CHECK(second.Hi().y_ <= 479);
  // The same part of the picture: half the coordinates, give or take rounding.
  CHECK(std::abs(second.Lo().x_ - first.Lo().x_ / 2) <= 2);
  CHECK(std::abs(second.Lo().y_ - first.Lo().y_ / 2) <= 2);
  CHECK(sent->Width() == 640);
  CHECK(sent->Height() == 480);
}

TEST_CASE("StreamBase::prepareImage zooms out within a smaller frame after a change of resolution", "[StreamBase]") {
  // Zoom out sends no click, so the last zoom-in point, given in the old
  // frame's pixels, can be past the edge of the new frame.
  EnsureImageInit();
  TestStream stream;
  stream.setClick(1880, 210);
  Image large(1920, 1080, ZM_COLOUR_RGB32, ZM_SUBPIX_ORDER_RGBA);
  for (int zoom = 110; zoom <= 140; zoom += 10) {
    stream.setView(ZM_SCALE_BASE, zoom);
    stream.prepare(&large);
  }

  Image small(352, 240, ZM_COLOUR_RGB32, ZM_SUBPIX_ORDER_RGBA);
  stream.prepare(&small);
  for (int zoom = 130; zoom >= 110; zoom -= 10) {
    stream.setView(ZM_SCALE_BASE, zoom);
    stream.prepare(&small);
    const Box &crop = stream.crop();
    CAPTURE(zoom, crop.Lo().x_, crop.Lo().y_, crop.Hi().x_, crop.Hi().y_);
    CHECK(crop.Lo().x_ < crop.Hi().x_);
    CHECK(crop.Lo().y_ < crop.Hi().y_);
    CHECK(crop.Hi().x_ <= 351);
    CHECK(crop.Hi().y_ <= 239);
    // Still the right-hand side of the picture, where the click was.
    CHECK(crop.Hi().x_ > 176);
  }
}

TEST_CASE("StreamBase::prepareImage starts a new zoom from the whole frame", "[StreamBase]") {
  // Zooming out to 100% shows the whole frame again. A later zoom-in click is
  // in that whole-frame view, so it must not be mapped through the crop left
  // behind by the earlier zoom.
  EnsureImageInit();
  Image frame(640, 480, ZM_COLOUR_RGB32, ZM_SUBPIX_ORDER_RGBA);

  TestStream fresh;
  fresh.setView(ZM_SCALE_BASE, 200);
  fresh.setClick(320, 240);
  fresh.prepare(&frame);
  const Box expected = fresh.crop();

  TestStream used;
  used.setView(ZM_SCALE_BASE, 200);
  used.setClick(600, 440);
  used.prepare(&frame);
  used.setView(ZM_SCALE_BASE, 100);
  used.prepare(&frame);
  used.setView(ZM_SCALE_BASE, 200);
  used.setClick(320, 240);
  used.prepare(&frame);

  CHECK(used.crop().Lo().x_ == expected.Lo().x_);
  CHECK(used.crop().Lo().y_ == expected.Lo().y_);
  CHECK(used.crop().Hi().x_ == expected.Hi().x_);
  CHECK(used.crop().Hi().y_ == expected.Hi().y_);
}

TEST_CASE("StreamBase::prepareImage starts a new zoom from the whole frame after a pre-scaled frame", "[StreamBase]") {
  // Event playback at a scale other than 100% sends unzoomed frames pre-scaled,
  // so zooming out there must also drop the crop of the earlier zoom.
  EnsureImageInit();
  Image frame(640, 480, ZM_COLOUR_RGB32, ZM_SUBPIX_ORDER_RGBA);
  Image half(320, 240, ZM_COLOUR_RGB32, ZM_SUBPIX_ORDER_RGBA);

  TestStream fresh;
  fresh.setView(50, 200);
  fresh.setClick(320, 240);
  fresh.prepare(&frame);
  const Box expected = fresh.crop();

  TestStream used;
  used.setView(50, 200);
  used.setClick(600, 440);
  used.prepare(&frame);
  used.setView(50, 100);
  used.prepare(&half, 50);
  used.setView(50, 200);
  used.setClick(320, 240);
  used.prepare(&frame);

  CHECK(used.crop().Lo().x_ == expected.Lo().x_);
  CHECK(used.crop().Lo().y_ == expected.Lo().y_);
  CHECK(used.crop().Hi().x_ == expected.Hi().x_);
  CHECK(used.crop().Hi().y_ == expected.Hi().y_);
}

TEST_CASE("StreamBase::prepareImage keeps a zoomed view still from frame to frame", "[StreamBase]") {
  EnsureImageInit();
  Image frame(640, 480, ZM_COLOUR_RGB32, ZM_SUBPIX_ORDER_RGBA);
  TestStream stream;
  stream.setView(ZM_SCALE_BASE, 150);
  stream.setClick(100, 100);
  stream.prepare(&frame);
  const Box first = stream.crop();

  for (int i = 0; i < 30; i++) stream.prepare(&frame);

  CHECK(stream.crop().Lo().x_ == first.Lo().x_);
  CHECK(stream.crop().Lo().y_ == first.Lo().y_);
  CHECK(stream.crop().Hi().x_ == first.Hi().x_);
  CHECK(stream.crop().Hi().y_ == first.Hi().y_);
}

TEST_CASE("StreamBase::prepareImage keeps the crop inside the frame when zooming in and out at a corner", "[StreamBase]") {
  // Each zoom maps the click through the previous crop, and at an edge the
  // crop shrinks a little every time. Alternating at the bottom right corner
  // turned it inside out, and Crop refused it until a zoom stop.
  EnsureImageInit();
  Image frame(352, 240, ZM_COLOUR_RGB32, ZM_SUBPIX_ORDER_RGBA);
  TestStream stream;
  stream.setClick(351, 239);
  for (int i = 0; i < 100; i++) {
    stream.setView(ZM_SCALE_BASE, i % 2 ? 120 : 110);
    stream.prepare(&frame);
    const Box &crop = stream.crop();
    CAPTURE(i, crop.Lo().x_, crop.Lo().y_, crop.Hi().x_, crop.Hi().y_);
    REQUIRE(crop.Lo().x_ >= 0);
    REQUIRE(crop.Lo().y_ >= 0);
    REQUIRE(crop.Lo().x_ < crop.Hi().x_);
    REQUIRE(crop.Lo().y_ < crop.Hi().y_);
    REQUIRE(crop.Hi().x_ <= 351);
    REQUIRE(crop.Hi().y_ <= 239);
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

TEST_CASE("StreamBase scale validation") {
  EnsureImageInit();

  SECTION("clampScale bounds the scale a client can ask for") {
    REQUIRE(StreamBase::clampScale(0) == ZM_SCALE_BASE);
    REQUIRE(StreamBase::clampScale(-5) == ZM_SCALE_BASE);
    REQUIRE(StreamBase::clampScale(1) == 1);
    REQUIRE(StreamBase::clampScale(50) == 50);
    REQUIRE(StreamBase::clampScale(1600) == 1600);
    REQUIRE(StreamBase::clampScale(3000) == 1600);
    REQUIRE(StreamBase::clampScale(65535) == 1600);
  }

  SECTION("prepareImage sends the unscaled image when the scaled size is invalid") {
    // 1600% of 1920x1080 is 30720x17280, which ffmpeg rejects, and a scale of
    // 0 gives 0x0. Both used to leave the scratch image empty and encode it.
    for (int scale : {0, 1600, 3000, 65535}) {
      TestStream stream;
      stream.setView(scale, ZM_SCALE_BASE);
      Image image(1920, 1080, ZM_COLOUR_RGB32, ZM_SUBPIX_ORDER_RGBA);

      Image *sent = stream.prepare(&image);

      REQUIRE(sent != nullptr);
      REQUIRE(sent->Width() == 1920);
      REQUIRE(sent->Height() == 1080);
    }
  }

  SECTION("preScaleDimensions declines a scaled size ffmpeg rejects") {
    TestStream stream;
    stream.setView(1600, ZM_SCALE_BASE);
    int width = 1920;
    int height = 1080;

    REQUIRE(stream.preScale(1920, 1080, width, height) == 0);
    REQUIRE(width == 1920);
    REQUIRE(height == 1080);
  }

  SECTION("a normal scale still scales") {
    TestStream stream;
    stream.setView(200, ZM_SCALE_BASE);
    Image image(640, 480, ZM_COLOUR_RGB32, ZM_SUBPIX_ORDER_RGBA);

    Image *sent = stream.prepare(&image);

    REQUIRE(sent->Width() == 1280);
    REQUIRE(sent->Height() == 960);
  }
}

TEST_CASE("StreamBase::setStreamReplayRate treats 0 as normal speed", "[StreamBase]") {
  TestStream stream;
  stream.setStreamReplayRate(0);
  CHECK(stream.replayRate() == ZM_RATE_BASE);
  stream.setStreamReplayRate(-200);
  CHECK(stream.replayRate() == -200);
}

TEST_CASE("StreamBase: the quality= parameter of zms", "[StreamBase]") {
  REQUIRE(StreamBase::ParseQualityParam("50") == 50);
  REQUIRE(StreamBase::ParseQualityParam("1") == 1);
  REQUIRE(StreamBase::ParseQualityParam("100") == 100);
  // Out of range or not a number keeps the configured quality.
  REQUIRE(StreamBase::ParseQualityParam("0") == 0);
  REQUIRE(StreamBase::ParseQualityParam("101") == 0);
  REQUIRE(StreamBase::ParseQualityParam("-5") == 0);
  REQUIRE(StreamBase::ParseQualityParam("high") == 0);
}

TEST_CASE("StreamBase: a stream's JPEGs are encoded at its quality", "[StreamBase]") {
  // A client on a slow link asks for quality=10 to cut the bandwidth of a
  // stream; without it every stream used ZM_JPEG_STREAM_QUALITY.
  EnsureImageInit();
  const unsigned int kWidth = 320;
  const unsigned int kHeight = 240;
  Image image(kWidth, kHeight, ZM_COLOUR_RGB24, ZM_SUBPIX_ORDER_RGB);
  // Detail for the encoder to spend bits on.
  uint8_t *buf = image.WriteBuffer(kWidth, kHeight, ZM_COLOUR_RGB24, ZM_SUBPIX_ORDER_RGB);
  for (unsigned int i = 0; i < kWidth * kHeight * 3; i++) buf[i] = static_cast<uint8_t>((i * 7919u) ^ (i >> 5));

  auto encoded_size = [&image](int quality) {
    TestStream stream;
    stream.setStreamQuality(quality);
    std::vector<unsigned char> out(1 << 20);
    size_t size = out.size();
    REQUIRE(stream.encodeJpeg(image, out.data(), &size));
    return size;
  };

  const int saved = config.jpeg_stream_quality;
  config.jpeg_stream_quality = 90;
  const size_t low = encoded_size(10);
  const size_t high = encoded_size(90);
  const size_t unset = encoded_size(0);
  config.jpeg_stream_quality = saved;

  REQUIRE(low < high / 2);
  // No quality given means the configured one.
  REQUIRE(unset == high);

  TestStream stream;
  stream.setStreamQuality(250);
  REQUIRE(stream.streamQuality() == 0);
}

TEST_CASE("StreamBase::updateFrameRate with an unbounded maxfps", "[StreamBase]") {
  // updateFrameRate compared (int)effective_fps with (int)max_fps. Casting an
  // infinite or huge maxfps to int is undefined and gives INT_MIN, so the loop
  // halving the rate never finished and the stream thread spun forever. Run
  // the update on its own thread and give up after two seconds.
  auto run_update_rate = [](double max_fps, double fps) {
    auto stream = std::make_shared<TestStream>();
    stream->setStreamMaxFPS(max_fps);
    auto done = std::make_shared<std::atomic<bool>>(false);
    std::thread thread([stream, done, fps]() {
      stream->updateRate(fps);
      done->store(true);
    });

    for (int waited = 0; waited < 200 && !done->load(); waited++) {
      std::this_thread::sleep_for(std::chrono::milliseconds(10));
    }

    if (!done->load()) {
      // The captured shared_ptrs keep the stream and the flag alive, so a hung
      // thread cannot touch the stack of this test after it fails.
      thread.detach();
    }
    REQUIRE(done->load());
    if (thread.joinable()) thread.join();
    return stream->frameMod();
  };

  for (double max : {std::numeric_limits<double>::infinity(), 3e9, 1e300}) {
    CAPTURE(max);
    CHECK(run_update_rate(max, 30.0) == 1);
  }

  SECTION("half the frame rate halves the frames actually sent") {
    CHECK(run_update_rate(15.0, 30.0) == 2);
  }

  SECTION("a maxfps at the frame rate sends every frame") {
    CHECK(run_update_rate(30.0, 30.0) == 1);
  }
}
