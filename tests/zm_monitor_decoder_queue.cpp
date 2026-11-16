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

// Monitor::MatchDecodedFrame(): pairing a decoded frame with the packet it came
// from when the codec consumed some packets without returning a frame (RTP loss).

namespace {

void EnsureConfig() {
  if (!config.font_file_location) config.font_file_location = "";
  if (!config.event_close_mode) config.event_close_mode = "idle";
}

class DecoderQueue : public Monitor {
 public:
  DecoderQueue() : Monitor() {}

  // Packets sent to the codec, in decode order, by pts.
  std::vector<std::shared_ptr<ZMPacket>> Send(const std::vector<int64_t> &pts) {
    std::vector<std::shared_ptr<ZMPacket>> sent;
    for (int64_t p : pts) {
      auto packet = std::make_shared<ZMPacket>();
      packet->packet->pts = p;
      packet->codec_type = AVMEDIA_TYPE_VIDEO;
      decoder_queue.emplace_back(packet);
      sent.push_back(packet);
    }
    return sent;
  }

  // The pts of the matched entry (or -1 for end(), -2 for begin() on an empty
  // queue) and what stays queued after it is taken out, as Decode() does.
  int64_t Receive(int64_t frame_pts) {
    auto match = MatchDecodedFrame(frame_pts);
    if (match == decoder_queue.end()) return -1;
    int64_t pts = match->packet_->packet->pts;
    decoder_queue.erase(match);
    return pts;
  }

  std::vector<int64_t> Queued() const {
    std::vector<int64_t> pts;
    for (const auto &lock : decoder_queue) pts.push_back(lock.packet_->packet->pts);
    return pts;
  }
};

}  // namespace

TEST_CASE("Monitor decoder queue: one frame per packet pairs in order", "[Monitor][decoder]") {
  EnsureConfig();
  DecoderQueue q;
  q.Send({0, 3600, 7200});
  CHECK(q.Receive(0) == 0);
  CHECK(q.Receive(3600) == 3600);
  CHECK(q.Queued() == std::vector<int64_t>{7200});
}

TEST_CASE("Monitor decoder queue: packets the codec dropped are released", "[Monitor][decoder]") {
  EnsureConfig();
  DecoderQueue q;
  auto sent = q.Send({0, 3600, 7200, 10800, 14400});
  // Corrupt slices: no frame for 0, 3600 and 7200, the next one is 10800.
  CHECK(q.Receive(10800) == 10800);
  CHECK(q.Queued() == std::vector<int64_t>{14400});
  for (int i = 0; i < 3; i++) {
    CHECK(sent[i]->decoded);
    CHECK(sent[i]->image == nullptr);
  }
  CHECK_FALSE(sent[4]->decoded);
}

TEST_CASE("Monitor decoder queue: the queue does not grow over many losses", "[Monitor][decoder]") {
  EnsureConfig();
  DecoderQueue q;
  int64_t pts = 0;
  for (int burst = 0; burst < 50; burst++) {
    // 15 packets in, the codec returns frames for the last 11 only
    std::vector<int64_t> in;
    for (int i = 0; i < 15; i++) in.push_back(pts + i * 3600);
    q.Send(in);
    for (int i = 4; i < 15; i++) CHECK(q.Receive(in[i]) == in[i]);
    pts += 15 * 3600;
  }
  CHECK(q.Queued().empty());
}

TEST_CASE("Monitor decoder queue: reordered frames keep the references still to come", "[Monitor][decoder]") {
  EnsureConfig();
  DecoderQueue q;
  // Decode order I P B B (pts 0, 3, 1, 2): output order 0 1 2 3.
  q.Send({0, 3, 1, 2});
  CHECK(q.Receive(0) == 0);
  CHECK(q.Receive(1) == 1);
  CHECK(q.Queued() == std::vector<int64_t>{3, 2});
  CHECK(q.Receive(2) == 2);
  CHECK(q.Receive(3) == 3);
  CHECK(q.Queued().empty());
}

TEST_CASE("Monitor decoder queue: a frame older than every packet is dropped", "[Monitor][decoder]") {
  EnsureConfig();
  DecoderQueue q;
  q.Send({7200, 10800});
  CHECK(q.Receive(3600) == -1);
  CHECK(q.Queued() == std::vector<int64_t>{7200, 10800});
}

TEST_CASE("Monitor decoder queue: without a pts the oldest entry is used", "[Monitor][decoder]") {
  EnsureConfig();
  DecoderQueue q;
  q.Send({0, 3600});
  CHECK(q.Receive(AV_NOPTS_VALUE) == 0);
  // A pts no packet has, newer than one of them: also the oldest entry.
  CHECK(q.Receive(99999) == 3600);
  CHECK(q.Queued().empty());
}
