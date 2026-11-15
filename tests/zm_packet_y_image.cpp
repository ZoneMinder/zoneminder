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
