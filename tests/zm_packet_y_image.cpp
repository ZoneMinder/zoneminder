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

#include "zm_config.h"
#include "zm_image.h"
#include "zm_packet.h"

// 720 is not a multiple of 32, so a stride mix-up shows up as shifted pixels
static const unsigned int width = 720, height = 4;

static Image *half_white_image() {
  if (!config.font_file_location) config.font_file_location = "";
  Image *image = new Image(width, height, ZM_COLOUR_RGB24, ZM_SUBPIX_ORDER_RGB);
  for (unsigned int y = 0; y < height; y++) {
    for (unsigned int x = 0; x < width; x++) {
      uint8_t *p = image->Buffer(x, y);
      p[0] = p[1] = p[2] = (x < width / 2) ? 255 : 0;
    }
  }
  return image;
}

static void check_y_image(ZMPacket &packet) {
  Image *y_image = packet.get_y_image();
  REQUIRE(y_image != nullptr);
  REQUIRE(y_image->Width() == width);
  REQUIRE(y_image->Height() == height);
  REQUIRE(y_image->Colours() == ZM_COLOUR_GRAY8);
  for (unsigned int y = 0; y < height; y++) {
    CHECK(*y_image->Buffer(0, y) > 200);
    CHECK(*y_image->Buffer(width / 2 - 1, y) > 200);
    CHECK(*y_image->Buffer(width / 2 + 1, y) < 50);
    CHECK(*y_image->Buffer(width - 1, y) < 50);
  }
  CHECK(packet.get_y_image() == y_image);
}

TEST_CASE("get_y_image converts the packet image when there is no decoded frame", "[Packet]") {
  ZMPacket packet(half_white_image(), SystemTimePoint());
  check_y_image(packet);
}

TEST_CASE("get_y_image converts the packet image when the frame is RGB", "[Packet]") {
  ZMPacket packet(half_white_image(), SystemTimePoint());
  packet.in_frame = av_frame_ptr{av_frame_alloc()};
  packet.in_frame->format = AV_PIX_FMT_RGBA;
  packet.in_frame->width = width;
  packet.in_frame->height = height;
  check_y_image(packet);
}

extern "C" {
#include <libavutil/frame.h>
#include <libavutil/imgutils.h>
}

#include <cstdint>
#include <cstring>

// ZMPacket::get_y_image() feeds motion detection with an 8-bit gray image. Only a
// planar 8-bit YUV frame has that in data[0]; for every other format the luma has
// to come from a conversion.

namespace {
constexpr int kW = 33;
constexpr int kH = 17;

void EnsureImageInit() {
  if (!config.font_file_location) config.font_file_location = "";
}

av_frame_ptr MakeFrame(AVPixelFormat fmt) {
  av_frame_ptr frame{av_frame_alloc()};
  frame->format = fmt;
  frame->width = kW;
  frame->height = kH;
  REQUIRE(av_frame_get_buffer(frame.get(), 32) == 0);
  for (int p = 0; p < AV_NUM_DATA_POINTERS && frame->buf[p]; p++)
    memset(frame->buf[p]->data, 0, frame->buf[p]->size);
  return frame;
}

// Luma at (x, y) of the image y_image points at.
uint8_t Luma(const Image *img, unsigned int x, unsigned int y) {
  return img->Buffer(x, y)[0];
}
}  // namespace

TEST_CASE("ZMPacket::get_y_image wraps the Y plane of an 8-bit planar frame", "[ZMPacket]") {
  EnsureImageInit();
  ZMPacket packet;
  packet.in_frame = MakeFrame(AV_PIX_FMT_YUV420P);
  packet.in_frame->data[0][5 * packet.in_frame->linesize[0] + 7] = 200;

  Image *y = packet.get_y_image();
  REQUIRE(y != nullptr);
  CHECK(y->Width() == kW);
  CHECK(y->Height() == kH);
  CHECK(y->Buffer() == packet.in_frame->data[0]);
  CHECK(Luma(y, 7, 5) == 200);
}

TEST_CASE("ZMPacket::get_y_image converts a 10-bit frame to 8-bit luma", "[ZMPacket]") {
  EnsureImageInit();
  ZMPacket packet;
  packet.in_frame = MakeFrame(AV_PIX_FMT_YUV420P10LE);
  for (int y = 0; y < kH; y++) {
    uint16_t *row = reinterpret_cast<uint16_t *>(packet.in_frame->data[0] + y * packet.in_frame->linesize[0]);
    for (int x = 0; x < kW; x++) row[x] = 64 << 2;
  }
  uint16_t *bright = reinterpret_cast<uint16_t *>(packet.in_frame->data[0] + 3 * packet.in_frame->linesize[0]);
  bright[kW - 1] = 235 << 2;

  Image *y = packet.get_y_image();
  REQUIRE(y != nullptr);
  CHECK(y->Width() == kW);
  CHECK(y->Height() == kH);
  CHECK(y->PixFormat() == AV_PIX_FMT_GRAY8);
  CHECK(Luma(y, 0, 0) < 70);
  CHECK(Luma(y, kW - 1, 3) > 200);
  CHECK(Luma(y, kW - 1, 4) < 70);
}

TEST_CASE("ZMPacket::get_y_image converts RGB and gray frames", "[ZMPacket]") {
  EnsureImageInit();
  SECTION("rgb24") {
    ZMPacket packet;
    packet.in_frame = MakeFrame(AV_PIX_FMT_RGB24);
    uint8_t *px = packet.in_frame->data[0] + 2 * packet.in_frame->linesize[0] + 3 * 10;
    px[0] = px[1] = px[2] = 255;

    Image *y = packet.get_y_image();
    REQUIRE(y != nullptr);
    CHECK(y->PixFormat() == AV_PIX_FMT_GRAY8);
    CHECK(Luma(y, 10, 2) > 200);
    CHECK(Luma(y, 11, 2) < 40);
  }
  SECTION("gray") {
    ZMPacket packet;
    packet.in_frame = MakeFrame(AV_PIX_FMT_GRAY8);
    packet.in_frame->data[0][4 * packet.in_frame->linesize[0] + 9] = 123;

    Image *y = packet.get_y_image();
    REQUIRE(y != nullptr);
    CHECK(y->PixFormat() == AV_PIX_FMT_GRAY8);
    CHECK(Luma(y, 9, 4) == 123);
    CHECK(Luma(y, 8, 4) == 0);
  }
}
