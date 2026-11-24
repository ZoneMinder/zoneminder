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

TEST_CASE("PacketQueue: an event start iterator on an emptied queue stays at the end") {
  PacketQueue queue;
  queue.addStream();
  queue.setKeepKeyframes(false);
  queue.setMaxVideoPackets(0);
  queue.setPreEventVideoPackets(2);

  // On an empty queue the video iterator is at end(), like one taken before the queue was cleared.
  packetqueue_iterator *analysis_it = queue.get_video_it(false);
  REQUIRE(analysis_it != nullptr);

  packetqueue_iterator *start_it = queue.get_event_start_packet_it(*analysis_it, 10);
  REQUIRE(start_it != nullptr);
  REQUIRE(*start_it == *analysis_it);

  queue.free_it(start_it);
  queue.free_it(analysis_it);
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

// With passthrough the queue keeps every packet back to a keyframe. A keyframe
// interval longer than MaxImageBufferCount leaves queuePacket() no later
// keyframe to trim to, so the whole GOP stays queued; releaseDecoded() keeps the
// decoded data of only the newest MaxImageBufferCount video packets, never less
// than the pre-event window, and leaves the compressed packets for the event's
// video. refs #4860
TEST_CASE("PacketQueue: decoded data is bounded by MaxImageBufferCount") {
  PacketQueue queue;
  queue.addStream();
  queue.setKeepKeyframes(true);

  packetqueue_iterator *holder_it = queue.get_video_it(false);
  REQUIRE(holder_it != nullptr);
  packetqueue_iterator *analysis_it = nullptr;

  // One keyframe, then a GOP longer than the queue's limit; analysis on the
  // newest packet.
  auto fill = [&](int count) {
    std::vector<std::shared_ptr<ZMPacket>> packets;
    for (int i = 0; i < count; i++) {
      packets.push_back(decoded_video_packet(i == 0));
      REQUIRE(queue.queuePacket(packets.back()));
    }
    while (queue.increment_it(holder_it, false)) {}
    analysis_it = queue.get_video_it(false);
    for (int i = 1; i < count; i++) REQUIRE(queue.increment_it(analysis_it, false));
    REQUIRE(*(*analysis_it) == packets.back());
    return packets;
  };

  SECTION("the newest MaxImageBufferCount packets keep their images") {
    queue.setMaxVideoPackets(20);
    queue.setPreEventVideoPackets(5);
    auto packets = fill(30);
    // queuePacket() found no second keyframe to trim to: everything is queued.
    REQUIRE(queue.size() == 30);
    queue.releaseDecoded();
    REQUIRE(packets[9]->image == nullptr);
    REQUIRE(packets[10]->image != nullptr);
    REQUIRE(packets[29]->image != nullptr);

    // The event still starts on the keyframe, for the whole of its video.
    packetqueue_iterator *start = queue.get_event_start_packet_it(*analysis_it, 5);
    REQUIRE(*(*start) == packets[0]);
    queue.free_it(start);
  }

  SECTION("the pre-event window keeps its images above the limit") {
    queue.setMaxVideoPackets(10);
    queue.setPreEventVideoPackets(15);
    auto packets = fill(30);
    queue.releaseDecoded();
    // 15 back from packet 29 is 14.
    REQUIRE(packets[13]->image == nullptr);
    REQUIRE(packets[14]->image != nullptr);
  }

  SECTION("no limit: images back to the keyframe stay") {
    queue.setMaxVideoPackets(0);
    queue.setPreEventVideoPackets(5);
    auto packets = fill(30);
    queue.releaseDecoded();
    REQUIRE(packets[0]->image != nullptr);
  }

  queue.free_it(analysis_it);
  queue.free_it(holder_it);
  queue.stop();
  queue.clear();
}

// With passthrough the queue keeps every packet back to a keyframe, so a camera
// keyframe interval longer than MaxImageBufferCount leaves queuePacket() nothing
// to trim to: the queue holds the whole GOP whatever MaxImageBufferCount says.
// That is a misconfiguration, reported once as an Error.
TEST_CASE("PacketQueue: a keyframe interval longer than MaxImageBufferCount is reported") {
  PacketQueue queue;
  queue.addStream();
  queue.setMaxVideoPackets(10);
  packetqueue_iterator *it = queue.get_video_it(false);
  REQUIRE(it != nullptr);

  auto gop = [&](int count) {
    for (int i = 0; i < count; i++) REQUIRE(queue.queuePacket(video_packet(i == 0)));
  };

  SECTION("passthrough, GOP within the limit") {
    queue.setKeepKeyframes(true);
    gop(10);
    gop(10);
    REQUIRE_FALSE(queue.keyframe_interval_over_limit());
  }

  SECTION("passthrough, GOP over the limit") {
    queue.setKeepKeyframes(true);
    gop(11);
    REQUIRE(queue.keyframe_interval_over_limit());
  }

  SECTION("not passthrough: the queue trims to the limit itself") {
    queue.setKeepKeyframes(false);
    gop(30);
    REQUIRE_FALSE(queue.keyframe_interval_over_limit());
  }

  SECTION("raising MaxImageBufferCount clears it") {
    queue.setKeepKeyframes(true);
    gop(11);
    REQUIRE(queue.keyframe_interval_over_limit());
    queue.setMaxVideoPackets(20);
    REQUIRE_FALSE(queue.keyframe_interval_over_limit());
    gop(11);
    REQUIRE_FALSE(queue.keyframe_interval_over_limit());
  }

  queue.free_it(it);
  queue.stop();
  queue.clear();
}


namespace {

std::shared_ptr<ZMPacket> audio_packet() {
  auto packet = std::make_shared<ZMPacket>();
  packet->packet->stream_index = 1;
  packet->keyframe = 1;
  packet->codec_type = AVMEDIA_TYPE_AUDIO;
  packet->timestamp = std::chrono::system_clock::now();
  return packet;
}

}  // namespace

// Video and audio alternate: V0 A V1 A V2 A V3 A V4 A V5, with the event's
// snapshot on V5. The pre-event window is a number of video packets, so the
// start must not depend on where the audio packets sit.
TEST_CASE("PacketQueue: pre-event count is in video packets with audio interleaved") {
  PacketQueue queue;
  queue.addStream();
  queue.addStream();
  queue.setMaxVideoPackets(0);
  queue.setPreEventVideoPackets(3);

  packetqueue_iterator *holder_it = queue.get_video_it(false);
  REQUIRE(holder_it != nullptr);

  std::vector<std::shared_ptr<ZMPacket>> video;
  for (int i = 0; i < 6; i++) {
    video.push_back(decoded_video_packet(i == 0));
    REQUIRE(queue.queuePacket(video.back()));
    if (i < 5) REQUIRE(queue.queuePacket(audio_packet()));
  }
  while (queue.increment_it(holder_it, false)) {}
  packetqueue_iterator *analysis_it = queue.get_video_it(false);
  while (*(*analysis_it) != video[5]) REQUIRE(queue.increment_it(analysis_it, false));

  SECTION("the event starts on the third video packet before the snapshot") {
    packetqueue_iterator *start = queue.get_event_start_packet_it(*analysis_it, 3);
    REQUIRE(*(*start) == video[2]);
    queue.free_it(start);
  }

  SECTION("a window ending on a video packet is unchanged") {
    packetqueue_iterator *start = queue.get_event_start_packet_it(*analysis_it, 2);
    REQUIRE(*(*start) == video[3]);
    queue.free_it(start);
  }

  SECTION("decoded images are kept back to that packet") {
    queue.releaseDecoded();
    REQUIRE(video[1]->image == nullptr);
    REQUIRE(video[2]->image != nullptr);
    REQUIRE(video[5]->image != nullptr);
  }

  queue.stop();
  queue.clear();
}

// Audio packets carry AV_PKT_FLAG_KEY, so keyframe is set on every one of them.
// queuePacket() holds everything back until the first video keyframe, but the
// keyframe test let an audio packet through, which put audio at the front of the queue
// ahead of the first video keyframe and parked a waiting iterator on it.
TEST_CASE("PacketQueue: nothing is queued before the first video keyframe") {
  PacketQueue queue;
  queue.addStream();
  queue.addStream();
  queue.setKeepKeyframes(false);
  queue.setMaxVideoPackets(0);
  queue.setPreEventVideoPackets(2);

  // The analysis thread's iterator, waiting at the end of an empty queue.
  packetqueue_iterator *analysis_it = queue.get_video_it(false);
  REQUIRE(analysis_it != nullptr);

  REQUIRE_FALSE(queue.queuePacket(audio_packet()));
  REQUIRE_FALSE(queue.queuePacket(video_packet(0)));
  REQUIRE(queue.size() == 0);

  auto key = video_packet(1);
  REQUIRE(queue.queuePacket(key));
  REQUIRE(queue.queuePacket(audio_packet()));
  REQUIRE(queue.size() == 2);
  REQUIRE(*(*analysis_it) == key);
  REQUIRE(queue.packet_count(1) == 1);

  queue.free_it(analysis_it);
  queue.stop();
  queue.clear();
}

// The counters are sized by addStream(), so a packet whose stream index is
// outside them must be refused instead of counted out of bounds.
TEST_CASE("PacketQueue: a packet for a stream the queue does not count is refused") {
  PacketQueue queue;
  queue.addStream();
  queue.setKeepKeyframes(false);
  queue.setMaxVideoPackets(0);
  queue.setPreEventVideoPackets(2);

  packetqueue_iterator *analysis_it = queue.get_video_it(false);
  REQUIRE(analysis_it != nullptr);

  REQUIRE(queue.queuePacket(video_packet(1)));
  REQUIRE(queue.size() == 1);

  auto stray = video_packet(0);
  stray->packet->stream_index = 5;
  REQUIRE_FALSE(queue.queuePacket(stray));
  REQUIRE(queue.size() == 1);

  auto negative = video_packet(0);
  negative->packet->stream_index = -1;
  REQUIRE_FALSE(queue.queuePacket(negative));
  REQUIRE(queue.size() == 1);

  REQUIRE(queue.packet_count(0) == 1);

  queue.stop();
  queue.clear();
}
