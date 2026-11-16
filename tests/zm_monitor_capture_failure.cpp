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

#include "zm_camera.h"
#include "zm_config.h"
#include "zm_image.h"
#include "zm_monitor.h"

#include <memory>
#include <vector>

// When the camera fails, Monitor::Capture() painted a signal-colour frame into
// the shared image ring. With a decoder thread running that made two writers
// of the ring: capture picked its slot from image_count, the decoder from
// last_write_index, so both could write one slot and last_write_index could
// step backwards (#5237). The decoder is now the only writer while it runs.

namespace {

void EnsureConfig() {
  if (!config.font_file_location) config.font_file_location = "";
  if (!config.event_close_mode) config.event_close_mode = "idle";
}

constexpr int kWidth = 4;
constexpr int kHeight = 2;
constexpr int kSlots = 3;

class FailingCamera : public Camera {
 public:
  explicit FailingCamera(const Monitor *monitor)
    : Camera(monitor, LOCAL_SRC, kWidth, kHeight, ZM_COLOUR_RGB24, ZM_SUBPIX_ORDER_RGB,
             -1, -1, -1, -1, true, false) {}
  int PreCapture() override { return 0; }
  int Capture(std::shared_ptr<ZMPacket> &) override { return -1; }
  int PostCapture() override { return 0; }
  int Close() override { return 0; }
};

// A Monitor with a local SharedData and image ring instead of the mapping, so
// Capture() can be driven without connect().
class RingMonitor : public Monitor {
 public:
  using Data = SharedData;
  static constexpr DecodingOption kDecodingNone = DECODING_NONE;
  static constexpr DecodingOption kDecodingAlways = DECODING_ALWAYS;

  RingMonitor(SharedData *data, DecodingOption p_decoding) : Monitor() {
    shared_data = data;
    decoding = p_decoding;
    width = kWidth;
    height = kHeight;
    image_buffer_count = kSlots;
    for (int i = 0; i < kSlots; i++) {
      image_buffer.push_back(new Image(kWidth, kHeight, ZM_COLOUR_RGB24, ZM_SUBPIX_ORDER_RGB));
      image_buffer.back()->Fill(Rgb(0x0a0a0a));
    }
    shared_timestamps = timestamps_;
    image_pixelformats = pixelformats_;
    camera = std::make_shared<FailingCamera>(this);
  }
  ~RingMonitor() {
    for (Image *image : image_buffer) delete image;
    image_buffer.clear();
    shared_timestamps = nullptr;
    image_pixelformats = nullptr;
    shared_data = nullptr;
    camera = nullptr;
  }
  const Image *Slot(int index) const { return image_buffer[index]; }

 private:
  struct timeval timestamps_[kSlots] = {};
  AVPixelFormat pixelformats_[kSlots] = {};
};

// Rows are padded to a 32-byte stride, so only the pixel bytes are compared.
bool SlotIsUntouched(const Image *image) {
  for (int y = 0; y < kHeight; y++) {
    const uint8_t *row = image->Buffer() + y * image->LineSize();
    for (int x = 0; x < kWidth * 3; x++) {
      if (row[x] != 0x0a) return false;
    }
  }
  return true;
}

}  // namespace

TEST_CASE("Capture failure leaves the image ring to a running decoder", "[Monitor]") {
  EnsureConfig();
  RingMonitor::Data data = {};
  data.capturing = 1;
  data.signal = 1;
  data.image_count = 7;       // capture's slot would be 7 % 3 = 1
  data.last_write_index = 2;  // the decoder's next slot is 0
  RingMonitor monitor(&data, RingMonitor::kDecodingAlways);

  REQUIRE(monitor.Capture() == -1);

  CHECK(data.signal == 0);
  CHECK(data.last_write_index == 2);
  CHECK(data.image_count == 7);
  for (int i = 0; i < kSlots; i++) {
    CHECK(SlotIsUntouched(monitor.Slot(i)));
  }
}

TEST_CASE("Capture failure without a decoder still shows the signal colour", "[Monitor]") {
  EnsureConfig();
  RingMonitor::Data data = {};
  data.capturing = 1;
  data.signal = 1;
  data.image_count = 7;
  data.last_write_index = 2;
  RingMonitor monitor(&data, RingMonitor::kDecodingNone);

  REQUIRE(monitor.Capture() == -1);

  CHECK(data.signal == 0);
  CHECK(data.last_write_index == 1);
  CHECK(data.image_count == 8);
  CHECK_FALSE(SlotIsUntouched(monitor.Slot(1)));
  CHECK(SlotIsUntouched(monitor.Slot(0)));
  CHECK(SlotIsUntouched(monitor.Slot(2)));
}
