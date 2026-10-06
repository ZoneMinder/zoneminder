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

#include "zm_config.h"
#include "zm_image.h"
#include "zm_packet.h"
#include "zm_packetqueue.h"

#include <memory>
#include <vector>

namespace {

std::shared_ptr<ZMPacket> video_packet(int keyframe) {
  auto packet = std::make_shared<ZMPacket>();
  packet->packet->stream_index = 0;
  packet->keyframe = keyframe;
  packet->codec_type = AVMEDIA_TYPE_VIDEO;
  packet->timestamp = std::chrono::system_clock::now();
  return packet;
}

// A video packet holding a decoded image, so a test can see releaseDecoded()
// free it. Image::Initialise() needs config.font_file_location, which is null
// without a zm.conf.
std::shared_ptr<ZMPacket> decoded_video_packet(int keyframe) {
  if (!config.font_file_location) config.font_file_location = "";
  auto packet = video_packet(keyframe);
  packet->image = new Image(16, 16, ZM_COLOUR_GRAY8, ZM_SUBPIX_ORDER_NONE);
  return packet;
}

}  // namespace

// Monitor::openEvent() takes an iterator from get_event_start_packet_it() and
// normally hands it to the Event, which frees it in ~Event. The error path that
// fails to lock the starting packet used to return without freeing it. This
// covers what that leak does to the queue: a registered iterator sitting on the
// front packet stops clearPackets() removing anything, permanently, because
// deletePacket() drags registered iterators onto each new front packet.
TEST_CASE("PacketQueue: an abandoned iterator blocks trimming until it is freed") {
  PacketQueue queue;
  queue.addStream();
  queue.setKeepKeyframes(false);
  queue.setMaxVideoPackets(10);
  queue.setPreEventVideoPackets(2);

  // queuePacket() drops everything when no iterator is registered, so stand in
  // for the analysis thread and keep one registered for the whole test.
  packetqueue_iterator *analysis_it = queue.get_video_it(false);
  REQUIRE(analysis_it != nullptr);

  for (int i = 0; i < 6; i++) {
    REQUIRE(queue.queuePacket(video_packet(i == 0 ? 1 : 0)));
  }
  REQUIRE(queue.size() == 6);

  SECTION("the queue trims once nothing points at the front") {
    // Analysis has consumed the queue, so its iterator sits at the tail.
    while (queue.increment_it(analysis_it, false)) {}

    auto trigger = video_packet(1);
    REQUIRE(queue.queuePacket(trigger));
    REQUIRE(queue.clearPackets(trigger));

    REQUIRE(queue.size() < 7);
  }

  SECTION("an abandoned iterator on the front packet stops every removal") {
    // What openEvent() used to leave behind: a registered iterator that nothing
    // owns any more, pointing at the front of the queue.
    packetqueue_iterator *leaked_it = queue.get_video_it(false);
    REQUIRE(leaked_it != nullptr);
    while (queue.increment_it(analysis_it, false)) {}

    auto trigger = video_packet(1);
    REQUIRE(queue.queuePacket(trigger));
    queue.clearPackets(trigger);

    REQUIRE(queue.size() == 7);

    // Freeing it is all the fix does, and the queue drains on the next attempt.
    queue.free_it(leaked_it);

    auto next = video_packet(1);
    REQUIRE(queue.queuePacket(next));
    REQUIRE(queue.clearPackets(next));

    REQUIRE(queue.size() < 8);
  }

  queue.stop();
  queue.clear();
}

// The pair of calls the fix in Monitor::openEvent() now makes on its error path:
// an iterator from get_event_start_packet_it() is registered with the queue, and
// free_it() is what takes it back out again.
TEST_CASE("PacketQueue: free_it unregisters an event start iterator") {
  PacketQueue queue;
  queue.addStream();
  queue.setKeepKeyframes(false);
  queue.setMaxVideoPackets(0);
  queue.setPreEventVideoPackets(2);

  packetqueue_iterator *analysis_it = queue.get_video_it(false);
  REQUIRE(analysis_it != nullptr);

  for (int i = 0; i < 6; i++) {
    REQUIRE(queue.queuePacket(video_packet(i == 0 ? 1 : 0)));
  }

  std::shared_ptr<ZMPacket> front = *queue.begin();
  while (queue.increment_it(analysis_it, false)) {}
  REQUIRE_FALSE(queue.is_there_an_iterator_pointing_to_packet(front));

  packetqueue_iterator *start_it = queue.get_event_start_packet_it(*analysis_it, 10);
  REQUIRE(start_it != nullptr);
  REQUIRE(queue.is_there_an_iterator_pointing_to_packet(front));

  queue.free_it(start_it);
  REQUIRE_FALSE(queue.is_there_an_iterator_pointing_to_packet(front));

  queue.stop();
  queue.clear();
}

// releaseDecoded() frees the decoded data of packets before the earliest point
// an event could start, computed with the current pre-event window. A RELOAD
// (zmu --reload) can widen that window without clearing the queue; the next
// event must then start at the oldest packet that still has its data, not on
// released ones, which would have no image (no capture JPEGs) and, for
// encoding, nothing to encode. refs #4860
TEST_CASE("PacketQueue: an event never starts on a released packet") {
  PacketQueue queue;
  queue.addStream();
  queue.setMaxVideoPackets(0);
  queue.setPreEventVideoPackets(5);

  // queuePacket() drops everything when no iterator is registered.
  packetqueue_iterator *holder_it = queue.get_video_it(false);
  REQUIRE(holder_it != nullptr);
  packetqueue_iterator *analysis_it = nullptr;

  // Keyframes every 10 packets; analysis on the last one, which is also the
  // event's snapshot packet.
  auto fill = [&](int count) {
    std::vector<std::shared_ptr<ZMPacket>> packets;
    for (int i = 0; i < count; i++) {
      packets.push_back(decoded_video_packet(i % 10 == 0));
      REQUIRE(queue.queuePacket(packets.back()));
    }
    // The holder was moved onto the first packet queued; take it past the
    // last one so only analysis_it bounds what may be released.
    while (queue.increment_it(holder_it, false)) {}
    analysis_it = queue.get_video_it(false);
    for (int i = 1; i < count; i++) REQUIRE(queue.increment_it(analysis_it, false));
    REQUIRE(*(*analysis_it) == packets.back());
    return packets;
  };

  SECTION("passthrough: a widened window stops at the release boundary") {
    queue.setKeepKeyframes(true);
    auto packets = fill(30);
    // 5 back from packet 29 is 24, then back to the keyframe at 20.
    queue.releaseDecoded();
    REQUIRE(packets[19]->image == nullptr);
    REQUIRE(packets[20]->image != nullptr);

    // Same window: the start is the keyframe at 20, as before the change.
    packetqueue_iterator *start = queue.get_event_start_packet_it(*analysis_it, 5);
    REQUIRE(*(*start) == packets[20]);
    queue.free_it(start);

    // Widened to 25 by a reload: unchanged code walked back to the keyframe at
    // 0, onto 20 packets without images.
    queue.setPreEventVideoPackets(25);
    start = queue.get_event_start_packet_it(*analysis_it, 25);
    REQUIRE(*(*start) == packets[20]);
    REQUIRE((*(*start))->image != nullptr);
    queue.free_it(start);
  }

  SECTION("encode: a widened window stops at the release boundary") {
    queue.setKeepKeyframes(false);
    auto packets = fill(30);
    // No keyframe needed: 5 back from packet 29 is 24.
    queue.releaseDecoded();
    REQUIRE(packets[23]->image == nullptr);
    REQUIRE(packets[24]->image != nullptr);

    queue.setPreEventVideoPackets(25);
    packetqueue_iterator *start = queue.get_event_start_packet_it(*analysis_it, 25);
    REQUIRE(*(*start) == packets[24]);
    queue.free_it(start);
  }

  SECTION("encode reloaded to passthrough: forward to the next keyframe") {
    queue.setKeepKeyframes(false);
    auto packets = fill(35);
    // Released before 29, which is no keyframe.
    queue.releaseDecoded();
    REQUIRE(packets[28]->image == nullptr);
    REQUIRE(packets[29]->image != nullptr);

    queue.setKeepKeyframes(true);
    queue.setPreEventVideoPackets(25);
    packetqueue_iterator *start = queue.get_event_start_packet_it(*analysis_it, 25);
    REQUIRE(*(*start) == packets[30]);
    REQUIRE((*(*start))->keyframe);
    queue.free_it(start);
  }

  queue.free_it(analysis_it);
  queue.free_it(holder_it);
  queue.stop();
  queue.clear();
}
