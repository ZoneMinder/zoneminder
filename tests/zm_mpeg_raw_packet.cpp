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

#include "zm_mpeg.h"

#include <cstring>
#include <vector>

TEST_CASE("VideoStream::SetRawPacket points a raw packet at the picture", "[VideoStream]") {
  const int width = 64;
  const int height = 48;
  const size_t yuv_size = width * height * 3 / 2;

  std::vector<uint8_t> pic(yuv_size);
  for (size_t i = 0; i < pic.size(); i++) pic[i] = static_cast<uint8_t>(i % 251);

  AVFrame *frame = av_frame_alloc();
  REQUIRE(frame != nullptr);
  frame->format = AV_PIX_FMT_YUV420P;
  frame->width = width;
  frame->height = height;
  frame->data[0] = pic.data();

  AVPacket *pkt = av_packet_alloc();
  REQUIRE(pkt != nullptr);

  VideoStream::SetRawPacket(pkt, frame);

  // The packet is the picture bytes, not the AVFrame struct that describes them.
  REQUIRE(pkt->data == pic.data());
  REQUIRE(static_cast<const void *>(pkt->data) != static_cast<const void *>(frame));
  // Sized for the frame's own format. The RGB24 buffer a caller passes in is
  // larger than the YUV420 picture, so taking its size would read past it.
  REQUIRE(static_cast<size_t>(pkt->size) == yuv_size);
  REQUIRE(pkt->size < width * height * 3);
  REQUIRE(std::memcmp(pkt->data, pic.data(), pic.size()) == 0);

  frame->data[0] = nullptr;  // not owned by the frame
  av_frame_free(&frame);
  pkt->data = nullptr;
  av_packet_free(&pkt);
}
