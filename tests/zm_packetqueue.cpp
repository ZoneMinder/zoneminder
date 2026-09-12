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

#include "zm_packet.h"
#include "zm_packetqueue.h"

#include <memory>

namespace {

std::shared_ptr<ZMPacket> video_packet(int keyframe) {
  auto packet = std::make_shared<ZMPacket>();
  packet->packet->stream_index = 0;
  packet->keyframe = keyframe;
  packet->codec_type = AVMEDIA_TYPE_VIDEO;
  packet->timestamp = std::chrono::system_clock::now();
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
