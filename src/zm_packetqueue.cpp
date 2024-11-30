//ZoneMinder Packet Queue Implementation Class
//Copyright 2016 Steve Gilvarry
//
//This file is part of ZoneMinder.
//
//ZoneMinder is free software: you can redistribute it and/or modify
//it under the terms of the GNU General Public License as published by
//the Free Software Foundation, either version 3 of the License, or
//(at your option) any later version.
//
//ZoneMinder is distributed in the hope that it will be useful,
//but WITHOUT ANY WARRANTY; without even the implied warranty of
//MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//GNU General Public License for more details.
//
//You should have received a copy of the GNU General Public License
//along with ZoneMinder.  If not, see <http://www.gnu.org/licenses/>.


// PacketQueue must know about all iterators and manage them

#include "zm_packetqueue.h"

#include "zm_ffmpeg.h"
#include "zm_packet.h"
#include "zm_signal.h"

PacketQueue::PacketQueue():
  video_stream_id(-1),
  max_video_packet_count(-1),
  pre_event_video_packet_count(-1),
  max_stream_id(-1),
  packet_counts(nullptr),
  deleting(false),
  keep_keyframes(false),
  warned_count(0),
  has_out_of_order_packets_(false),
  max_keyframe_interval_(0) {
}

/* Assumes queue is empty when adding streams
 * Assumes first stream added will be the video stream
 */
int PacketQueue::addStream() {
  deleting = false;
  if (max_stream_id == -1) {
    video_stream_id = 0;
    max_stream_id = 0;
  } else {
    max_stream_id ++;
  }

  if (packet_counts) delete[] packet_counts;
  packet_counts = new int[max_stream_id+1];
  for (int i=0; i <= max_stream_id; ++i)
    packet_counts[i] = 0;
  return max_stream_id;
}

PacketQueue::~PacketQueue() {
  clear();
  if (packet_counts) {
    delete[] packet_counts;
    packet_counts = nullptr;
  }
  while (!iterators.empty()) {
    packetqueue_iterator *it = iterators.front();
    iterators.pop_front();
    delete it;
  }
  Debug(4, "Done in destructor");
}

/* Enqueues the given packet.  Will maintain the it pointer and image packet counts.
 * If we have reached our max image packet count, it will pop off as many packets as are needed.
 * Thus it will ensure that the same packet never gets queued twice.
 */

bool PacketQueue::queuePacket(std::shared_ptr<ZMPacket> add_packet) {
  if (iterators.empty()) {
    Debug(4, "No iterators so no one needs us to queue packets.");
    return false;
  }
  if (!packet_counts[video_stream_id] and !add_packet->keyframe) {
    Debug(4, "No video keyframe so no one needs us to queue packets.");
    return false;
  }
  {
    std::unique_lock<std::mutex> lck(mutex);
    if (deleting or zm_terminate) return false;

    if (!has_out_of_order_packets_ and (add_packet->packet->dts != AV_NOPTS_VALUE)) {
      auto rit = pktQueue.rbegin();
      // Find the previous packet for the stream, and check dts
      while (rit != pktQueue.rend()) {
        std::shared_ptr<ZMPacket> prev_packet = *rit;

        if (prev_packet->packet->stream_index == add_packet->packet->stream_index) {
          if (prev_packet->packet->dts > add_packet->packet->dts) {
            Debug(1, "Have out of order packets");
            ZM_DUMP_PACKET(prev_packet->packet, "queued_packet");
            ZM_DUMP_PACKET(add_packet->packet, "add_packet");
            has_out_of_order_packets_ = true;
          }
          break;
        }
        rit++;
      }  // end while
    }

    if (!max_keyframe_interval_ and add_packet->keyframe and (video_stream_id==add_packet->packet->stream_index)) {
      auto rit = pktQueue.rbegin();
      int packet_count = 0;
      while (rit != pktQueue.rend()) {
        std::shared_ptr<ZMPacket> prev_packet = *rit;
        if (prev_packet->packet->stream_index == add_packet->packet->stream_index) {
          packet_count ++;
          if (prev_packet->keyframe) break;
        }
        ++rit;
      }
      Debug(1, "Have keyframe interval: %d", packet_count);
      max_keyframe_interval_ = packet_count;
    }

    pktQueue.push_back(add_packet);

    /* Any iterators that are pointing to the end will now point to the newly pushed packet */
    for (
      auto iterators_it = iterators.begin();
      iterators_it != iterators.end();
      ++iterators_it
    ) {
      packetqueue_iterator *iterator_it = *iterators_it;
      if (*iterator_it == pktQueue.end()) {
        --(*iterator_it);
      }
    }  // end foreach iterator

    packet_counts[add_packet->packet->stream_index] += 1;
    Debug(2, "packet counts for %d is %d",
          add_packet->packet->stream_index,
          packet_counts[add_packet->packet->stream_index]);

    if (
      (add_packet->packet->stream_index == video_stream_id)
      and
      (max_video_packet_count > 0)
      and
      (packet_counts[video_stream_id] > max_video_packet_count)
    ) {
      if (!warned_count) {
        warned_count++;
        Warning("You have set the max video packets in the queue to %u."
                " The queue is full. Either Analysis is not keeping up or"
                " your camera's keyframe interval %d is larger than this setting."
                , max_video_packet_count, max_keyframe_interval_);
      }

      for (
        // Start at second packet because the first is always a keyframe
        auto it = ++pktQueue.begin();
        //it != pktQueue.end() and  // can't hit end because we added our packet
        (*it != add_packet) && !(deleting or zm_terminate);
        // iterator is incremented by erase
      ) {
        std::shared_ptr <ZMPacket>zm_packet = *it;

        ZMLockedPacket lp(zm_packet);
        if (!lp.trylock()) {
          if (warned_count < 2) {
            warned_count++;
            // Can't delete a locked packet, but can delete one after it.
            Warning("Found locked packet when trying to free up video packets. This means that decoding is not keeping up.");
          }
          ++it;
          continue;
        }

        if (zm_packet->packet->stream_index == video_stream_id and zm_packet->keyframe) {
          for ( it = pktQueue.begin(); *it !=zm_packet; ) {
            it = this->deletePacket(it);
          }
          break;
        } else {
          this->deletePacket(it);

          if (zm_packet->packet->stream_index == video_stream_id)
            break;
        } // end if erasing a whole gop
      }  // end while
    } else if (warned_count > 0) {
      warned_count--;
    }  // end if not able catch up
  }  // end lock scope
  // We signal on every packet because someday we may analyze sound
  Debug(4, "packetqueue queuepacket, unlocked signalling");
  condition.notify_all();

  return true;
}  // end bool PacketQueue::queuePacket(ZMPacket* zm_packet)

packetqueue_iterator PacketQueue::deletePacket(packetqueue_iterator it) {
  auto zm_packet = *it;
  for (
      auto iterators_it = iterators.begin();
      iterators_it != iterators.end();
      ++iterators_it
      ) {
    auto iterator_it = *iterators_it;
    // Have to check each iterator and make sure it doesn't point to the packet we are about to delete
    if (*(*iterator_it) == zm_packet) {
      Debug(1, "Bumping IT because it is at the front that we are deleting");
      ++(*iterator_it);
    } else {
      Debug(1, "Not Bumping IT because it is pointing at %d and we are %d", (*(*iterator_it))->image_index, zm_packet->image_index);
    }
  }  // end foreach iterator
  zm_packet->decoded = true;
  zm_packet->notify_all();

  packet_counts[zm_packet->packet->stream_index] -= 1;
  Debug(1,
      "Deleting a packet with stream index:%d image_index:%d with keyframe:%d, video frames in queue:%d max: %d, queuesize:%zu",
      zm_packet->packet->stream_index,
      zm_packet->image_index,
      zm_packet->keyframe,
      packet_counts[video_stream_id],
      max_video_packet_count,
      pktQueue.size());
  return pktQueue.erase(it);
}

void PacketQueue::clearPackets(const std::shared_ptr<ZMPacket> &add_packet) {
  // Only do queueCleaning if we are adding a video keyframe, so that we guarantee that there is one.
  // No good.  Have to satisfy two conditions:
  // 1. packetqueue starts with a video keyframe
  // 2. Have minimum # of video packets
  // 3. No packets can be locked
  // 4. No iterator can point to one of the packets
  //
  // So start at the beginning, counting video packets until the next keyframe.
  // Then if deleting those packets doesn't break 1 and 2, then go ahead and delete them.
  //
  // One assumption that we can make is that there will be packets in the queue. Because we call it while holding a locked packet
  if (deleting) return;

  if (keep_keyframes and ! (
        add_packet->packet->stream_index == video_stream_id
        and
        add_packet->keyframe
        and
        (packet_counts[video_stream_id] > pre_event_video_packet_count)
        and
        *(pktQueue.begin()) != add_packet
      )
     ) {
    Debug(3, "stream index %d ?= video_stream_id %d, keyframe %d, keep_keyframes %d,  counts %d > pre_event_count %d at begin %d",
          add_packet->packet->stream_index, video_stream_id, add_packet->keyframe, keep_keyframes, packet_counts[video_stream_id], pre_event_video_packet_count,
          ( *(pktQueue.begin()) != add_packet )
         );
    return;
  }
  std::unique_lock<std::mutex> lck(mutex);

  // If analysis_it isn't at the end, we need to keep that many additional packets
  int tail_count = 0;
  for (auto it = pktQueue.rbegin(); it != pktQueue.rend() && (*it != add_packet); ++it) {
    if ((*it)->packet->stream_index == video_stream_id)
      ++tail_count;
  }
  Debug(1, "Tail count is %d, queue size is %zu", tail_count, pktQueue.size());

  if (!keep_keyframes) {
    // If not doing passthrough, we don't care about starting with a keyframe so logic is simpler
    while ((*pktQueue.begin() != add_packet) and (packet_counts[video_stream_id] > pre_event_video_packet_count + tail_count)) {
      std::shared_ptr<ZMPacket> zm_packet = *pktQueue.begin();
      ZMLockedPacket lp(zm_packet);
      if (!lp.trylock()) break;

      if (is_there_an_iterator_pointing_to_packet(zm_packet)) {
        Warning("Found iterator at beginning of queue. Some thread isn't keeping up");
        break;
      }

      pktQueue.pop_front();
      int stream_index = zm_packet->packet ? zm_packet->packet->stream_index : 0;
      packet_counts[stream_index] -= 1;
      Debug(1,
            "Deleting a packet with stream index:%d image_index:%d with keyframe:%d, video frames in queue:%d max: %d, queuesize:%zu",
            stream_index,
            zm_packet->image_index,
            zm_packet->keyframe,
            packet_counts[video_stream_id],
            pre_event_video_packet_count,
            pktQueue.size());
    } // end while
    return;
  }

  auto it = pktQueue.begin();
  auto next_front = pktQueue.begin();

  // First packet is special because we know it is a video keyframe and only need to check for lock
  std::shared_ptr<ZMPacket> zm_packet = *it;
  if (zm_packet == add_packet) {
    Debug(1, "First packet in queue is the analysis packet.");
    return;
  }

  int keyframe_interval_count = 0;
  int video_packets_to_delete = 0;    // This is a count of how many packets we will delete so we know when to stop looking

  ZMLockedPacket *lp = new ZMLockedPacket(zm_packet);
  if (!lp->trylock()) {
    Debug(4, "Failed getting lock on first packet");
    delete lp;
    return;
  }  // end if first packet not locked
 
  if (is_there_an_iterator_pointing_to_packet(zm_packet)) {
    Debug(3, "Found iterator Counted %d video packets. Which would leave %d in packetqueue tail count is %d",
        video_packets_to_delete, packet_counts[video_stream_id]-video_packets_to_delete, tail_count);
    delete lp;
    return;
  }

  ++it;
  delete lp;

  // Since we have many packets in the queue, we should NOT be pointing at end so don't need to test for that
  while (*it != add_packet) {
    zm_packet = *it;
    lp = new ZMLockedPacket(zm_packet);
    if (!lp->trylock()) {
      Debug(3, "Failed locking packet %d", zm_packet->image_index);
      delete lp;
      break;
    }
    delete lp;

    if (is_there_an_iterator_pointing_to_packet(zm_packet)) {
      Debug(3, "Found iterator Counted %d video packets. Which would leave %d in packetqueue tail count is %d",
          video_packets_to_delete, packet_counts[video_stream_id]-video_packets_to_delete, tail_count);
      break;
    }

    if (zm_packet->packet->stream_index == video_stream_id) {
      keyframe_interval_count++;
      if (zm_packet->keyframe) {
        Debug(3, "Have a video keyframe so setting next front to it. Keyframe interval so far is %d", keyframe_interval_count);
        if (keyframe_interval_count > max_keyframe_interval_)
          max_keyframe_interval_ = keyframe_interval_count;
        keyframe_interval_count = 1;
        next_front = it;
      }
      ++video_packets_to_delete;
      if (packet_counts[video_stream_id] - video_packets_to_delete <= pre_event_video_packet_count + tail_count) {
        Debug(3, "Counted %d video packets. Which would leave %d in packetqueue tail count is %d",
              video_packets_to_delete, packet_counts[video_stream_id]-video_packets_to_delete, tail_count);
        break;
      }
    }
    ++it;
  } // end while

  Debug(1, "Resulting it pointing at latest packet? %d, next front points to begin? %d, Keyframe interval %d",
        ( *it == add_packet ),
        ( next_front == pktQueue.begin() ),
        keyframe_interval_count
       );
  if (next_front != pktQueue.begin()) {
    while (pktQueue.begin() != next_front) {
      zm_packet = *pktQueue.begin();
      if (!zm_packet) {
        Error("NULL zm_packet in queue");
        continue;
      }

      Debug(1,
            "Deleting a packet with stream index:%d image_index:%d with keyframe:%d, video frames in queue:%d max: %d, queuesize:%zu",
            zm_packet->packet->stream_index,
            zm_packet->image_index,
            zm_packet->keyframe,
            packet_counts[video_stream_id],
            pre_event_video_packet_count,
            pktQueue.size());
      pktQueue.pop_front();
      packet_counts[zm_packet->packet->stream_index] -= 1;
    }
  }  // end if have at least max_video_packet_count video packets remaining

  return;
} // end voidPacketQueue::clearPackets(ZMPacket* zm_packet)

void PacketQueue::stop() {
  deleting = true;
  condition.notify_all();
  for (const auto &p : pktQueue) {
    p->notify_all();
  }
}

void PacketQueue::clear() {
  deleting = true;
  // Why are we notifying?
  condition.notify_all();
  if (!packet_counts) // special case, not initialised
    return;
  Debug(1, "Clearing packetqueue");
  std::unique_lock<std::mutex> lck(mutex);

  while (!pktQueue.empty()) {
    std::shared_ptr<ZMPacket> packet = pktQueue.front();
    // Someone might have this packet, but not for very long and since we have locked the queue they won't be able to get another one
    ZMLockedPacket *lp = new ZMLockedPacket(packet);
    lp->lock();
    Debug(1,
          "Deleting a packet with stream index:%d image_index:%d with keyframe:%d, video frames in queue:%d max: %d, queuesize:%zu",
          packet->packet->stream_index,
          packet->image_index,
          packet->keyframe,
          packet_counts[video_stream_id],
          pre_event_video_packet_count,
          pktQueue.size());
    packet_counts[packet->packet->stream_index] -= 1;
    pktQueue.pop_front();
    delete lp;
  }
  Debug(1, "Packetqueue is clear, deleting iterators");

  for (
    std::list<packetqueue_iterator *>::iterator iterators_it = iterators.begin();
    iterators_it != iterators.end();
    ++iterators_it
  ) {
    packetqueue_iterator *iterator_it = *iterators_it;
    *iterator_it = pktQueue.begin();
  }  // end foreach iterator

  delete[] packet_counts;
  packet_counts = nullptr;
  max_stream_id = -1;
  max_keyframe_interval_ = 0;

  Debug(1, "Packetqueue is clear, notifying");
  condition.notify_all();
}

unsigned int PacketQueue::size() {
  return pktQueue.size();
}

int PacketQueue::packet_count(int stream_id) {
  if (stream_id < 0 or stream_id > max_stream_id) {
    Error("Invalid stream_id %d max is %d", stream_id, max_stream_id);
    return -1;
  }
  return packet_counts[stream_id];
}  // end int PacketQueue::packet_count(int stream_id)

ZMLockedPacket *PacketQueue::get_packet_no_wait(packetqueue_iterator *it) {
  if (deleting or zm_terminate)
    return nullptr;

  Debug(4, "Locking in get_packet using it %p queue end? %d",
      std::addressof(*it), (*it == pktQueue.end()));

  // scope for lock
  std::unique_lock<std::mutex> lck(mutex);
  Debug(4, "Have Lock in get_packet");
  if ((*it == pktQueue.end()) and !(deleting or zm_terminate)) {
    Debug(2, "waiting.  Queue size %zu it == end? %d", pktQueue.size(), (*it == pktQueue.end()));
    condition.wait(lck);
  }
  if ((*it == pktQueue.end()) or deleting or zm_terminate) return nullptr;

  std::shared_ptr<ZMPacket> p = *(*it);
  ZMLockedPacket *lp = new ZMLockedPacket(p);
  if (lp->trylock()) {
    Debug(2, "Locked packet %d, unlocking packetqueue mutex", p->image_index);
    return lp;
  }
  delete lp;
  return nullptr;
}

// Returns a packet. Packet will be locked
ZMLockedPacket *PacketQueue::get_packet(packetqueue_iterator *it) {
  if (deleting or zm_terminate)
    return nullptr;

  Debug(4, "Locking in get_packet using it %p queue end? %d",
        std::addressof(*it), (*it == pktQueue.end()));

  ZMLockedPacket *lp = nullptr;
  {
    // scope for lock
    std::unique_lock<std::mutex> lck(mutex);
    Debug(4, "Have Lock in get_packet");
    while (!lp) {
      while ((*it == pktQueue.end()) and !(deleting or zm_terminate)) {
        Debug(2, "waiting.  Queue size %zu it == end? %d", pktQueue.size(), (*it == pktQueue.end()));
        condition.wait(lck);
      }
      if (deleting or zm_terminate) break;

      std::shared_ptr<ZMPacket> p = *(*it);
      if (!p) {
        Error("Null p?!");
        return nullptr;
      }
      Debug(3, "get_packet using it %p locking index %d",
            std::addressof(*it), p->image_index);

      lp = new ZMLockedPacket(p);
      if (lp->trylock()) {
        Debug(2, "Locked packet %d, unlocking packetqueue mutex", p->image_index);
        return lp;
      }
      delete lp;
      lp = nullptr;
      Debug(2, "waiting.  Queue size %zu it == end? %d", pktQueue.size(), (*it == pktQueue.end()));
      condition.wait(lck);
    }  // end while !lp
  }  // end scope for lock

  if (!lp) {
    Debug(1, "terminated, leaving");
    condition.notify_all();
  }
  return lp;
}  // end ZMLockedPacket *PacketQueue::get_packet(it)

// Returns a packet. Packet will be locked
ZMLockedPacket *PacketQueue::get_packet_and_increment_it(packetqueue_iterator *it) {
  if (deleting or zm_terminate)
    return nullptr;

  Debug(4, "Locking in get_packet using it %p queue end? %d",
        std::addressof(*it), (*it == pktQueue.end()));

  ZMLockedPacket *lp = nullptr;
  {
    // scope for lock
    std::unique_lock<std::mutex> lck(mutex);
    Debug(4, "Have Lock in get_packet");
    while (!lp) {
      while ((*it == pktQueue.end()) and !(deleting or zm_terminate)) {
        Debug(2, "waiting.  Queue size %zu it == end? %d", pktQueue.size(), (*it == pktQueue.end()));
        condition.wait(lck);
      }
      if (deleting or zm_terminate) break;

      std::shared_ptr<ZMPacket> p = *(*it);
      if (!p) {
        Error("Null p?!");
        return nullptr;
      }
      Debug(3, "get_packet using it %p locking index %d",
            std::addressof(*it), p->image_index);

      lp = new ZMLockedPacket(p);
      if (lp->trylock()) {
        Debug(2, "Locked packet %d, unlocking packetqueue mutex, incrementing it", p->image_index);
        ++(*it);
        return lp;
      }
      delete lp;
      lp = nullptr;
      Debug(2, "waiting.  Queue size %zu it == end? %d", pktQueue.size(), (*it == pktQueue.end()));
      condition.wait(lck);
    }  // end while !lp
  }  // end scope for lock

  if (!lp) {
    Debug(1, "terminated, leaving");
    condition.notify_all();
  }
  return lp;
}  // end ZMLockedPacket *PacketQueue::get_packet_and_increment_it(it)

void PacketQueue::unlock(ZMLockedPacket *lp) {
  delete lp;
  condition.notify_all();
}

bool PacketQueue::increment_it(packetqueue_iterator *it) {
  Debug(2, "Incrementing %p, queue size %zu, end? %d, deleting %d", it, pktQueue.size(), ((*it) == pktQueue.end()), deleting);
  if (((*it) == pktQueue.end()) or deleting) {
    return false;
  }
  std::unique_lock<std::mutex> lck(mutex);
  ++(*it);
  if (*it != pktQueue.end()) {
    Debug(2, "Incrementing %p, %p still not at end, so returning true", it, std::addressof(*it));
    return true;
  }
  Debug(2, "At end");
  return false;
}  // end bool PacketQueue::increment_it(packetqueue_iterator *it)

// Increment it only considering packets for a given stream
bool PacketQueue::increment_it(packetqueue_iterator *it, int stream_id) {
  Debug(2, "Incrementing %p, queue size %zu, end? %d", it, pktQueue.size(), (*it == pktQueue.end()));
  if (*it == pktQueue.end()) {
    return false;
  }

  std::unique_lock<std::mutex> lck(mutex);
  do {
    ++(*it);
  } while ( (*it != pktQueue.end()) and ( (*(*it))->packet->stream_index != stream_id) );

  if ( *it != pktQueue.end() ) {
    Debug(2, "Incrementing %p, still not at end, so incrementing", it);
    return true;
  }
  return false;
}  // end bool PacketQueue::increment_it(packetqueue_iterator *it)

packetqueue_iterator *PacketQueue::get_event_start_packet_it(
  packetqueue_iterator snapshot_it,
  unsigned int pre_event_count
) {
  std::unique_lock<std::mutex> lck(mutex);

  packetqueue_iterator *it = new packetqueue_iterator;
  iterators.push_back(it);
  Debug(4, "Have event start iterator %p", std::addressof(*it));

  *it = snapshot_it;
  std::shared_ptr<ZMPacket> packet = *(*it);
  //ZM_DUMP_PACKET(packet->packet, "");
  // Step one count back pre_event_count frames as the minimum
  // Do not assume that snapshot_it is video
  // snapshot it might already point to the beginning
  while (pre_event_count and ((*it) != pktQueue.begin())) {
    /*
    Debug(1, "Previous packet pre_event_count %d stream_index %d keyframe %d score %d",
        pre_event_count, packet->packet->stream_index, packet->keyframe, packet->score);
    ZM_DUMP_PACKET(packet->packet, "");
    */
    if (packet->packet->stream_index == video_stream_id)
      pre_event_count --;
    (*it)--;
    packet = *(*it);
  }

  // it either points to beginning or we have seen pre_event_count video packets.
  if (pre_event_count) {
    if (packet->image_index < (int)pre_event_count) {
      // probably just starting up
      Debug(1, "Hit end of packetqueue before satisfying pre_event_count. Needed %d more video frames", pre_event_count);
    } else {
      Warning("Hit end of packetqueue before satisfying pre_event_count. Needed %d more video frames", pre_event_count);
    }
    //ZM_DUMP_PACKET(packet->packet, "");
    return it;
  } else if (!keep_keyframes) {
    // Are encoding, so don't care about keyframes
    // We could be pointing to a non-video frame though.  Do we care?
    return it;
  }

  while ((*it) != pktQueue.begin()) {
    //ZM_DUMP_PACKET(packet->packet, "No keyframe");
    if ((packet->packet->stream_index == video_stream_id) and packet->keyframe)
      return it; // Success
    --(*it);
    packet = *(*it);
  }
  if (!packet->keyframe) {
    Warning("Hit beginning of packetqueue and packet is not a keyframe. index is %d", packet->image_index);
  }
  return it;
}  // end packetqueue_iterator *PacketQueue::get_event_start_packet_it

void PacketQueue::dumpQueue() {
  std::list<std::shared_ptr<ZMPacket>>::reverse_iterator it;
  for ( it = pktQueue.rbegin(); it != pktQueue.rend(); ++ it ) {
    std::shared_ptr<ZMPacket> zm_packet = *it;
    ZM_DUMP_PACKET(zm_packet->packet, is_there_an_iterator_pointing_to_packet(zm_packet) ? "*" : "");
  }
}

/* Returns an iterator to the first video keyframe in the queue.
 * nullptr if no keyframe video packet exists.
 */
packetqueue_iterator * PacketQueue::get_video_it(bool wait) {
  packetqueue_iterator *it = new packetqueue_iterator;
  iterators.push_back(it);

  std::unique_lock<std::mutex> lck(mutex);
  *it = pktQueue.begin();

  if ( wait ) {
    while ( ((! pktQueue.size()) or (*it == pktQueue.end())) and !zm_terminate and !deleting ) {
      Debug(2, "waiting for packets in queue. Queue size %zu it == end? %d", pktQueue.size(), (*it == pktQueue.end()));
      condition.wait(lck);
      *it = pktQueue.begin();
    }
    if ( deleting or zm_terminate ) {
      free_it(it);
      delete it;
      return nullptr;
    }
  }

  while (*it != pktQueue.end()) {
    std::shared_ptr<ZMPacket> zm_packet = *(*it);
    if (!zm_packet) {
      Error("Null zmpacket in queue!?");
      free_it(it);
      return nullptr;
    }
    Debug(1, "Packet keyframe %d for stream %d, so returning the it to it",
          zm_packet->keyframe, zm_packet->packet->stream_index);
    if (zm_packet->keyframe and ( zm_packet->packet->stream_index == video_stream_id )) {
      Debug(1, "Found a keyframe for stream %d, so returning the it to it", video_stream_id);
      return it;
    }
    ++(*it);
  }
  Debug(1, "Didn't find a keyframe for stream %d, so returning the it to it", video_stream_id);
  return it;
}  // get video_it

void PacketQueue::free_it(packetqueue_iterator *it) {
  std::unique_lock<std::mutex> lck(mutex);
  for (
    std::list<packetqueue_iterator *>::iterator iterators_it = iterators.begin();
    iterators_it != iterators.end();
    ++iterators_it
  ) {
    if ( *iterators_it == it ) {
      iterators.erase(iterators_it);
      break;
    }
  }
}

bool PacketQueue::is_there_an_iterator_pointing_to_packet(const std::shared_ptr<ZMPacket> zm_packet) {
  for (
    std::list<packetqueue_iterator *>::iterator iterators_it = iterators.begin();
    iterators_it != iterators.end();
    ++iterators_it
  ) {
    packetqueue_iterator *iterator_it = *iterators_it;
    if (*iterator_it == pktQueue.end()) {
      Debug(4, "Checking iterator %p == end", std::addressof(*iterator_it));
      continue;
    }
    Debug(4, "Checking iterator %p == packet ? %d", std::addressof(*iterator_it), ( *(*iterator_it) == zm_packet ));
    // Have to check each iterator and make sure it doesn't point to the packet we are about to delete
    if (*(*iterator_it) == zm_packet) {
      return true;
    }
  }  // end foreach iterator
  return false;
}

void PacketQueue::setMaxVideoPackets(int p) {
  max_video_packet_count = p;
  Debug(1, "Setting max_video_packet_count to %d", p);
  if (max_video_packet_count < 0)
    max_video_packet_count = 0 ;
}
void PacketQueue::setPreEventVideoPackets(int p) {
  pre_event_video_packet_count = p;
  Debug(1, "Setting pre_event_video_packet_count to %d", p);
  if (pre_event_video_packet_count < 1)
    pre_event_video_packet_count = 1;
  // We can simplify a lot of logic in queuePacket if we can assume at least 1 packet in queue
}

void PacketQueue::notify_all() {
  condition.notify_all();
};

void PacketQueue::wait() {
  std::unique_lock<std::mutex> lck(mutex);
  condition.wait(lck);
}
