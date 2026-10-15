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

#include "zm_monitor.h"

#include <vector>

// Monitor::KeepDtsContinuous(): a camera whose clock steps back (refs #4242).
// Once the step is taken out at capture, every packet after it continues from
// where the stream was, for video and audio alike.

namespace {

void EnsureConfig() {
  if (!config.font_file_location) config.font_file_location = "";
  if (!config.event_close_mode) config.event_close_mode = "idle";
}

// Monitor keeps the state; this exposes the method and the offset to the tests.
class DtsContinuity : public Monitor {
 public:
  DtsContinuity() : Monitor() {}
  int64_t Apply(AVPacket *packet, AVRational time_base) { return KeepDtsContinuous(packet, time_base); }
  Microseconds Offset() const { return Microseconds(dts_offset_); }
};

constexpr AVRational kVideoTb = {1, 90000};  // RTP/MPEG-TS video
constexpr AVRational kAudioTb = {1, 8000};
constexpr int kVideo = 0;
constexpr int kAudio = 1;

struct Pkt {
  av_packet_ptr p{av_packet_alloc()};
  Pkt(int stream, int64_t dts, int64_t duration) {
    p->stream_index = stream;
    p->dts = dts;
    p->pts = dts;
    p->duration = duration;
  }
};

// Feed dts values (in the stream's time base) through and return what comes out.
std::vector<int64_t> Run(DtsContinuity &c, int stream, AVRational tb, const std::vector<int64_t> &dts,
                         int64_t duration) {
  std::vector<int64_t> out;
  for (int64_t d : dts) {
    Pkt pkt(stream, d, duration);
    c.Apply(pkt.p.get(), tb);
    REQUIRE(pkt.p->pts == pkt.p->dts);
    out.push_back(pkt.p->dts);
  }
  return out;
}

}  // namespace

TEST_CASE("Monitor dts continuity: a continuous stream is left alone", "[Monitor][dts]") {
  EnsureConfig();
  DtsContinuity c;
  std::vector<int64_t> in = {0, 3000, 6000, 9000, 12000};
  CHECK(Run(c, kVideo, kVideoTb, in, 3000) == in);
  CHECK(c.Offset() == Microseconds(0));
}

TEST_CASE("Monitor dts continuity: jitter under the threshold is left alone", "[Monitor][dts]") {
  EnsureConfig();
  DtsContinuity c;
  // 100 ms back is reordering jitter: the video store's reorder queue sorts it.
  std::vector<int64_t> in = {0, 3000, 6000, 6000 - 9000, 9000};
  CHECK(Run(c, kVideo, kVideoTb, in, 3000) == in);
  CHECK(c.Offset() == Microseconds(0));
}

TEST_CASE("Monitor dts continuity: a step back continues from where the stream was", "[Monitor][dts]") {
  EnsureConfig();
  DtsContinuity c;
  // 8.933 s back (803970 ticks at 90 kHz), the step in the #4242 reproduction.
  std::vector<int64_t> out = Run(c, kVideo, kVideoTb, {900000, 903000, 906000, 906000 - 803970, 906000 - 803970 + 3000}, 3000);
  CHECK(out == std::vector<int64_t>({900000, 903000, 906000, 909000, 912000}));
  CHECK(c.Offset() > Microseconds(8900000));
}

TEST_CASE("Monitor dts continuity: the 1.048 s step from the report is taken out", "[Monitor][dts]") {
  EnsureConfig();
  DtsContinuity c;
  std::vector<int64_t> out = Run(c, kVideo, kVideoTb, {200000, 203000, 203000 - 94320, 203000 - 94320 + 3000}, 3000);
  CHECK(out == std::vector<int64_t>({200000, 203000, 206000, 209000}));
}

TEST_CASE("Monitor dts continuity: audio follows the offset video set", "[Monitor][dts]") {
  EnsureConfig();
  DtsContinuity c;
  // Both clocks step back 8 s together. Video sees it first and moves the
  // shared offset; audio, stepped back by the same amount, carries on.
  Run(c, kVideo, kVideoTb, {900000, 903000}, 3000);
  Run(c, kAudio, kAudioTb, {80000, 80160}, 160);
  std::vector<int64_t> video = Run(c, kVideo, kVideoTb, {903000 - 720000 + 3000}, 3000);
  CHECK(video == std::vector<int64_t>({906000}));
  std::vector<int64_t> audio = Run(c, kAudio, kAudioTb, {80320 - 64000, 80480 - 64000}, 160);
  // Continuous and increasing, within a video frame of where it would have been.
  CHECK(audio[1] - audio[0] == 160);
  CHECK(audio[0] > 80160);
  CHECK(audio[0] < 80320 + 8000 / 30);
}

TEST_CASE("Monitor dts continuity: without a duration the last step stands in", "[Monitor][dts]") {
  EnsureConfig();
  DtsContinuity c;
  std::vector<int64_t> out = Run(c, kVideo, kVideoTb, {900000, 903600, 907200, 107200}, 0);
  CHECK(out == std::vector<int64_t>({900000, 903600, 907200, 910800}));
}

TEST_CASE("Monitor dts continuity: packets without a dts pass through", "[Monitor][dts]") {
  EnsureConfig();
  DtsContinuity c;
  Pkt pkt(kVideo, AV_NOPTS_VALUE, 3000);
  pkt.p->pts = AV_NOPTS_VALUE;
  CHECK(c.Apply(pkt.p.get(), kVideoTb) == 0);
  CHECK(pkt.p->dts == AV_NOPTS_VALUE);
  CHECK(pkt.p->pts == AV_NOPTS_VALUE);
}

