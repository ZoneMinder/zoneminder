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

#if HAVE_RTSP_SERVER
#include "zm_rtsp_server_stream_video_source.h"

namespace {
bool Starts(int hType, std::initializer_list<uint8_t> bytes) {
  std::vector<uint8_t> data(bytes);
  return ZoneMinderStreamVideoSource::StartsKeyframe(hType, data.data(), data.size());
}
}  // namespace

TEST_CASE("RTSP packets a client may start decoding from, H.264", "[rtsp_keyframe]") {
  CHECK(Starts(264, {0x67, 0x42}));        // SPS
  CHECK(Starts(264, {0x68, 0xCE}));        // PPS
  CHECK(Starts(264, {0x65, 0x88}));        // IDR slice
  CHECK(Starts(264, {0x7C, 0x85, 0x00}));  // FU-A start of an IDR
  CHECK_FALSE(Starts(264, {0x7C, 0x05}));  // FU-A continuation of an IDR
  CHECK_FALSE(Starts(264, {0x5C, 0x81}));  // FU-A start of a non-IDR slice
  CHECK_FALSE(Starts(264, {0x41, 0x9A}));  // non-IDR slice
  CHECK_FALSE(Starts(264, {0x09, 0xF0}));  // access unit delimiter
  CHECK_FALSE(Starts(264, {0x7C}));        // truncated FU-A
  CHECK_FALSE(Starts(264, {}));
}

TEST_CASE("RTSP packets a client may start decoding from, H.265", "[rtsp_keyframe]") {
  CHECK(Starts(265, {0x40, 0x01}));        // VPS
  CHECK(Starts(265, {0x42, 0x01}));        // SPS
  CHECK(Starts(265, {0x44, 0x01}));        // PPS
  CHECK(Starts(265, {0x26, 0x01}));        // IDR_W_RADL
  CHECK(Starts(265, {0x2A, 0x01}));        // CRA
  CHECK(Starts(265, {0x62, 0x01, 0x93}));  // FU start of an IDR_W_RADL
  CHECK_FALSE(Starts(265, {0x62, 0x01, 0x13}));  // FU continuation
  CHECK_FALSE(Starts(265, {0x62, 0x01, 0x81}));  // FU start of a trailing slice
  CHECK_FALSE(Starts(265, {0x02, 0x01}));  // TRAIL_R slice
  CHECK_FALSE(Starts(265, {0x62, 0x01}));  // truncated FU
}

TEST_CASE("RTSP keyframe detection is H.26x only", "[rtsp_keyframe]") {
  CHECK_FALSE(Starts(1, {0x67, 0x42}));
}
#endif  // HAVE_RTSP_SERVER
