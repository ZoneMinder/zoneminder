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
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

#include "zm_catch2.h"

#include "zm_rtp_source.h"

#include <cstring>

// RtpSource::handlePacket() trusted packetLen. The RTP header is 12 bytes plus the
// 4-bit CSRC count times 4 (so 12-72 bytes). A packet shorter than its own header made
// packetLen - rtpHeaderSize - extraHeader wrap as size_t, which drove an out-of-bounds
// read of packet[rtpHeaderSize+1] and a heap overflow in mFrame.append(). These short
// packets must be refused. Under ASAN the pre-fix code faults on the read below.
// refs GHSA-4p3h-p7rr-838r

static RtpSource makeSource() {
  // Ports 0 so no socket work; H264 so the vulnerable NAL-header reads are exercised.
  return RtpSource(1, "127.0.0.1", 0, "127.0.0.1", 0, 0x12345678, 1, 90000, 0, AV_CODEC_ID_H264);
}

TEST_CASE("RtpSource::handlePacket refuses a packet shorter than its RTP header", "[RtpSource]") {
  RtpSource src = makeSource();

  SECTION("maximum CSRC count with a tiny payload (the exploit shape)") {
    // cc = 15 -> rtpHeaderSize = 72, but only 14 bytes are present.
    unsigned char packet[14];
    std::memset(packet, 0, sizeof(packet));
    packet[0] = 0xff;  // low nibble is the CSRC count: 15
    REQUIRE(src.handlePacket(packet, sizeof(packet)) == false);
  }

  SECTION("no CSRC but too short for the header plus the inspected payload bytes") {
    // cc = 0 -> rtpHeaderSize = 12; the H264 path reads packet[12] and packet[13].
    unsigned char packet[13];
    std::memset(packet, 0, sizeof(packet));
    REQUIRE(src.handlePacket(packet, sizeof(packet)) == false);
  }

  SECTION("shorter than even the fixed 12 byte header") {
    unsigned char packet[4] = {0};
    REQUIRE(src.handlePacket(packet, sizeof(packet)) == false);
  }
}
