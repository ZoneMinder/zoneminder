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

#include <fcntl.h>
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
  void setView(int p_scale, int p_zoom) {
    scale = p_scale;
    zoom = p_zoom;
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

  SECTION("a scale that does not match what the caller produced is redone") {
    // The command thread can change scale after the decode side read it. The
    // frame in hand is then the wrong size and has to be scaled the old way
    // rather than sent at whatever size it happens to be.
    TestStream stream;
    stream.setView(50, ZM_SCALE_BASE);
    Image image(kWidth, kHeight, ZM_COLOUR_RGB32, ZM_SUBPIX_ORDER_RGBA);

    Image *sent = stream.prepare(&image, 25);

    REQUIRE(sent != &image);
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
    // The whole optimisation rests on these agreeing: the decode side computes
    // the target as (dimension * scale) / ZM_SCALE_BASE, which has to match what
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

