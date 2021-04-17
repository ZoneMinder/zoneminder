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
#include <sys/time.h>

PacketQueue::PacketQueue():
  video_stream_id(-1),
  max_video_packet_count(-1),
  pre_event_video_packet_count(-1),
  max_stream_id(-1),
  packet_counts(nullptr),
  deleting(false),
  keep_keyframes(false)
{
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

bool PacketQueue::queuePacket(ZMPacket* add_packet) {
  Debug(4, "packetqueue queuepacket %p %d", add_packet, add_packet->image_index);
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

    if (add_packet->packet.stream_index == video_stream_id) {
      if ((max_video_packet_count > 0) and (packet_counts[video_stream_id] > max_video_packet_count)) {
        Warning("You have set the max video packets in the queue to %u."
            " The queue is full. Either Analysis is not keeping up or"
            " your camera's keyframe interval is larger than this setting."
            " We are dropping packets.", max_video_packet_count);
        if (add_packet->keyframe) {
          // Have a new keyframe, so delete everything
          while ((*pktQueue.begin() != add_packet) and (packet_counts[video_stream_id] > max_video_packet_count)) {
            ZMPacket *zm_packet = *pktQueue.begin();
            ZMLockedPacket *lp = new ZMLockedPacket(zm_packet);
            if (!lp->trylock()) {
              Debug(1, "Found locked packet when trying to free up video packets. Can't continue");
              delete lp;
              break;
            }
            delete lp;

            for (
                std::list<packetqueue_iterator *>::iterator iterators_it = iterators.begin();
                iterators_it != iterators.end();
                ++iterators_it
                ) {
              packetqueue_iterator *iterator_it = *iterators_it;
              // Have to check each iterator and make sure it doesn't point to the packet we are about to delete
              if ( *(*iterator_it) == zm_packet ) {
                Debug(1, "Bumping IT because it is at the front that we are deleting");
                ++(*iterators_it);
              }
            }  // end foreach iterator

            pktQueue.pop_front();
            packet_counts[zm_packet->packet.stream_index] -= 1;
            Debug(1, "Deleting a packet with stream index:%d image_index:%d with keyframe:%d, video frames in queue:%d max: %d, queuesize:%d",
                zm_packet->packet.stream_index, zm_packet->image_index, zm_packet->keyframe, packet_counts[video_stream_id], max_video_packet_count, pktQueue.size());
            delete zm_packet;
          } // end while
        }
      }  // end if too many video packets
      if ((max_video_packet_count > 0) and (packet_counts[video_stream_id] > max_video_packet_count)) {
        Error("Unable to free up older packets.  Not queueing this video packet.");
        return false;
      }
    }  // end if this packet is a video packet

    pktQueue.push_back(add_packet);
    packet_counts[add_packet->packet.stream_index] += 1;
    Debug(2, "packet counts for %d is %d",
        add_packet->packet.stream_index,
        packet_counts[add_packet->packet.stream_index]);

    for (
        std::list<packetqueue_iterator *>::iterator iterators_it = iterators.begin();
        iterators_it != iterators.end();
        ++iterators_it
        ) {
      packetqueue_iterator *iterator_it = *iterators_it;
      if (*iterator_it == pktQueue.end()) {
        Debug(4, "pointing it %p to back", iterator_it);
        --(*iterator_it);
      } else {
        Debug(4, "it %p not at end", iterator_it);
      }
    }  // end foreach iterator
  }  // end lock scope
  // We signal on every packet because someday we may analyze sound
  Debug(4, "packetqueue queuepacket, unlocked signalling");
  condition.notify_all();

	return true;
} // end bool PacketQueue::queuePacket(ZMPacket* zm_packet)

void PacketQueue::clearPackets(ZMPacket *add_packet) {
  // Only do queueCleaning if we are adding a video keyframe, so that we guarantee that there is one.
  // No good.  Have to satisfy two conditions: 
  // 1. packetqueue starts with a video keyframe
  // 2. Have minimum # of video packets
  // 3. No packets can be locked
  // 4. No iterator can point to one of the packets
  //
  // So start at the beginning, counting video packets until the next keyframe.  
  // Then if deleting those packets doesn't break 1 and 2, then go ahead and delete them.
  if (keep_keyframes and ! (
        add_packet->packet.stream_index == video_stream_id
        and
        add_packet->keyframe
        and
        (packet_counts[video_stream_id] > pre_event_video_packet_count)
        and 
        *(pktQueue.begin()) != add_packet
        )
     ) {
    Debug(3, "stream index %d ?= video_stream_id %d, keyframe %d, keep_keyframes %d,  counts %d > pre_event_count %d at begin %d",
        add_packet->packet.stream_index, video_stream_id, add_packet->keyframe, keep_keyframes, packet_counts[video_stream_id], pre_event_video_packet_count, 
        ( *(pktQueue.begin()) != add_packet )
        );
    return;
  }
  std::unique_lock<std::mutex> lck(mutex);

  // If ananlysis_it isn't at the end, we need to keep that many additional packets
  int tail_count = 0;
  if (pktQueue.back() != add_packet) {
    packetqueue_iterator it = pktQueue.end();
    --it;
    while (*it != add_packet) {
      if ((*it)->packet.stream_index == video_stream_id)
        ++tail_count;
      --it;
    }
  }
  Debug(1, "Tail count is %d", tail_count);

  if (!keep_keyframes) {
    // If not doing passthrough, we don't care about starting with a keyframe so logic is simpler
    while ((*pktQueue.begin() != add_packet) and (packet_counts[video_stream_id] > pre_event_video_packet_count + tail_count)) {
      ZMPacket *zm_packet = *pktQueue.begin();
      ZMLockedPacket *lp = new ZMLockedPacket(zm_packet);
      if (!lp->trylock()) break;
      delete lp;

      if (is_there_an_iterator_pointing_to_packet(zm_packet)) {
        Warning("Found iterator at beginning of queue. Some thread isn't keeping up");
        break;
      }

      pktQueue.pop_front();
      packet_counts[zm_packet->packet.stream_index] -= 1;
      Debug(1, "Deleting a packet with stream index:%d image_index:%d with keyframe:%d, video frames in queue:%d max: %d, queuesize:%d",
          zm_packet->packet.stream_index, zm_packet->image_index, zm_packet->keyframe, packet_counts[video_stream_id], pre_event_video_packet_count, pktQueue.size());
      delete zm_packet;
    } // end while
    return;
  }

  packetqueue_iterator it = pktQueue.begin();
  packetqueue_iterator next_front = pktQueue.begin();
  int video_packets_to_delete = 0;    // This is a count of how many packets we will delete so we know when to stop looking

  // First packet is special because we know it is a video keyframe and only need to check for lock
  ZMPacket *zm_packet = *it;
  ZMLockedPacket *lp = new ZMLockedPacket(zm_packet);
  if (lp->trylock()) {
    ++it;
    delete lp;

    // Since we have many packets in the queue, we should NOT be pointing at end so don't need to test for that
    while (*it != add_packet) {
      zm_packet = *it;
      lp = new ZMLockedPacket(zm_packet);
      if (!lp->trylock()) {
        delete lp;
        break;
      }
      delete lp;

      if (is_there_an_iterator_pointing_to_packet(zm_packet) and (pktQueue.begin() == next_front)) {
        Warning("Found iterator at beginning of queue. Some thread isn't keeping up");
        break;
      }

      if (zm_packet->packet.stream_index == video_stream_id) {
        if (zm_packet->keyframe) {
          Debug(3, "Have a video keyframe so setting next front to it");
          next_front = it;
        }
        ++video_packets_to_delete;
          Debug(4, "Counted %d video packets. Which would leave %d in packetqueue tail count is %d",
              video_packets_to_delete, packet_counts[video_stream_id]-video_packets_to_delete, tail_count);
        if (packet_counts[video_stream_id] - video_packets_to_delete <= pre_event_video_packet_count + tail_count) {
          break;
        }
      }
      it++;
    } // end while
  }  // end if first packet not locked
  Debug(1, "Resulting pointing at latest packet? %d, next front points to begin? %d",
      ( *it == add_packet ),
      ( next_front == pktQueue.begin() )
      );
  if ( next_front != pktQueue.begin() ) {
    while ( pktQueue.begin() != next_front ) {
      ZMPacket *zm_packet = *pktQueue.begin();
      if ( !zm_packet ) {
        Error("NULL zm_packet in queue");
        continue;
      }

      Debug(1, "Deleting a packet with stream index:%d image_index:%d with keyframe:%d, video frames in queue:%d max: %d, queuesize:%d",
          zm_packet->packet.stream_index, zm_packet->image_index, zm_packet->keyframe, packet_counts[video_stream_id], pre_event_video_packet_count, pktQueue.size());
      pktQueue.pop_front();
      packet_counts[zm_packet->packet.stream_index] -= 1;
      delete zm_packet;
    }
  }  // end if have at least max_video_packet_count video packets remaining
  // We signal on every packet because someday we may analyze sound

	return;
} // end voidPacketQueue::clearPackets(ZMPacket* zm_packet)

ZMLockedPacket* PacketQueue::popPacket( ) {
  Debug(4, "pktQueue size %d", pktQueue.size());
	if ( pktQueue.empty() ) {
		return nullptr;
	}
  Debug(4, "poPacket Mutex locking");
  std::unique_lock<std::mutex> lck(mutex);

	ZMPacket *zm_packet = pktQueue.front();
  for (
      std::list<packetqueue_iterator *>::iterator iterators_it = iterators.begin();
      iterators_it != iterators.end();
      ++iterators_it
      ) {
    packetqueue_iterator *iterator_it = *iterators_it;
    // Have to check each iterator and make sure it doesn't point to the packet we are about to delete
    if ( *(*iterator_it) == zm_packet ) {
      Debug(4, "Bumping it because it is at the front that we are deleting");
      ++(*iterators_it);
    }
  }  // end foreach iterator

  ZMLockedPacket *lp = new ZMLockedPacket (zm_packet);
  lp->lock();

	pktQueue.pop_front();
  packet_counts[zm_packet->packet.stream_index] -= 1;

	return lp;
}  // popPacket


/* Keeps frames_to_keep frames of the provided stream, which theoretically is the video stream
 * Basically it starts at the end, moving backwards until it finds the minimum video frame.
 * Then it should probably move forward to find a keyframe.  The first video frame must always be a keyframe.
 * So really frames_to_keep is a maximum which isn't so awesome.. maybe we should go back  farther to find the keyframe in which case
 * frames_to_keep in a minimum
 */

unsigned int PacketQueue::clear(unsigned int frames_to_keep, int stream_id) {
  Debug(3, "Clearing all but %d frames, queue has %d", frames_to_keep, pktQueue.size());

	if ( pktQueue.empty() ) {
    return 0;
  }

  // If size is <= frames_to_keep since it could contain audio, we can't possibly do anything
  if ( pktQueue.size() <= frames_to_keep ) {
    return 0;
  }
  Debug(5, "Locking in clear");
  std::unique_lock<std::mutex> lck(mutex);

  packetqueue_iterator it = pktQueue.end()--;  // point to last element instead of end
  ZMPacket *zm_packet = nullptr;

  while ( (it != pktQueue.begin()) and frames_to_keep ) {
    zm_packet = *it;
    AVPacket *av_packet = &(zm_packet->packet);
       
    Debug(3, "Looking at packet with stream index (%d) with keyframe(%d), Image_index(%d) frames_to_keep is (%d)",
        av_packet->stream_index, zm_packet->keyframe, zm_packet->image_index, frames_to_keep );
    
    // Want frames_to_keep video keyframes.  Otherwise, we may not have enough
    if ( av_packet->stream_index == stream_id ) {
      frames_to_keep --;
    }
    it --;
  }

  // Either at beginning or frames_to_keep == 0

  if ( it == pktQueue.begin() ) {
    if ( frames_to_keep ) {
      Warning("Couldn't remove any packets, needed %d", frames_to_keep);
    }
    mutex.unlock();
    return 0;
  }

  int delete_count = 0;

  // Else not at beginning, are pointing at packet before the last video packet
  while ( pktQueue.begin() != it ) {
    Debug(4, "Deleting a packet from the front, count is (%d), queue size is %d",
        delete_count, pktQueue.size());
    zm_packet = pktQueue.front();
    for (
        std::list<packetqueue_iterator *>::iterator iterators_it = iterators.begin();
        iterators_it != iterators.end();
        ++iterators_it
       ) {
      packetqueue_iterator *iterator_it = *iterators_it;
      // Have to check each iterator and make sure it doesn't point to the packet we are about to delete
      if ( *(*iterator_it) == zm_packet ) {
        Debug(4, "Bumping it because it is at the front that we are deleting");
        ++(*iterators_it);
      }
    }  // end foreach iterator
    packet_counts[zm_packet->packet.stream_index] --;
    pktQueue.pop_front();
    //if ( zm_packet->image_index == -1 )
      delete zm_packet;

    delete_count += 1;
  } // while our iterator is not the first packet
  Debug(3, "Deleted %d packets, %d remaining", delete_count, pktQueue.size());
  return delete_count; 
} // end unsigned int PacketQueue::clear( unsigned int frames_to_keep, int stream_id )

void PacketQueue::clear() {
  deleting = true;
  condition.notify_all();

  std::unique_lock<std::mutex> lck(mutex);

  while (!pktQueue.empty()) {
    ZMPacket *packet = pktQueue.front();
    // Someone might have this packet, but not for very long and since we have locked the queue they won't be able to get another one
    ZMLockedPacket *lp = new ZMLockedPacket(packet);
    lp->lock();
    pktQueue.pop_front();
    delete lp;
    delete packet;
  }

  for (
      std::list<packetqueue_iterator *>::iterator iterators_it = iterators.begin();
      iterators_it != iterators.end();
      ++iterators_it
      ) {
    packetqueue_iterator *iterator_it = *iterators_it;
    *iterator_it = pktQueue.begin();
  }  // end foreach iterator

  if ( packet_counts ) delete[] packet_counts;
  packet_counts = nullptr;
  max_stream_id = -1;

  condition.notify_all();
}

// clear queue keeping only specified duration of video -- return number of pkts removed
unsigned int PacketQueue::clear(struct timeval *duration, int streamId) {

  if ( pktQueue.empty() ) {
    return 0;
  }
  Debug(4, "Locking in clear");
  std::unique_lock<std::mutex> lck(mutex);

  struct timeval keep_from;
  std::list<ZMPacket *>::reverse_iterator it = pktQueue.rbegin();

  struct timeval *t = (*it)->timestamp;
  timersub(t, duration, &keep_from);
  ++it;

  Debug(3, "Looking for frame before queue keep time with stream id (%d), queue has %d packets",
        streamId, pktQueue.size());
  for ( ; it != pktQueue.rend(); ++it) {
    ZMPacket *zm_packet = *it;
    AVPacket *av_packet = &(zm_packet->packet);
    if (
        (av_packet->stream_index == streamId)
        and
        timercmp(zm_packet->timestamp, &keep_from, <=)
        ) {
        Debug(3, "Found frame before keep time with stream index %d at %d.%d",
                 av_packet->stream_index,
                 zm_packet->timestamp->tv_sec,
                 zm_packet->timestamp->tv_usec);
        break;
    }
  }

  if ( it == pktQueue.rend() ) {
    Debug(1, "Didn't find a frame before queue preserve time. keeping all");
    mutex.unlock();
    return 0;
  }

  Debug(3, "Looking for keyframe");
  for ( ; it != pktQueue.rend(); ++it) {
    ZMPacket *zm_packet = *it;
    AVPacket *av_packet = &(zm_packet->packet);
    if (
        (av_packet->flags & AV_PKT_FLAG_KEY)
        and
       (av_packet->stream_index == streamId)
       ) {
      Debug(3, "Found keyframe before start with stream index %d at %d.%d",
               av_packet->stream_index,
               zm_packet->timestamp->tv_sec,
               zm_packet->timestamp->tv_usec );
      break;
    }
  }
  if ( it == pktQueue.rend() ) {
    Debug(1, "Didn't find a keyframe before event starttime. keeping all" );
    return 0;
  }

  unsigned int deleted_frames = 0;
  ZMPacket *zm_packet = nullptr;
  while ( distance(it, pktQueue.rend()) > 1 ) {
    zm_packet = pktQueue.front();
    for (
        std::list<packetqueue_iterator *>::iterator iterators_it = iterators.begin();
        iterators_it != iterators.end();
        ++iterators_it
       ) {
      packetqueue_iterator *iterator_it = *iterators_it;
      // Have to check each iterator and make sure it doesn't point to the packet we are about to delete
      if ( *(*iterator_it) == zm_packet ) {
        Debug(4, "Bumping it because it is at the front that we are deleting");
        ++(*iterators_it);
      }
    }  // end foreach iterator
    pktQueue.pop_front();
    packet_counts[zm_packet->packet.stream_index] -= 1;
    delete zm_packet;
    deleted_frames += 1;
  }
  Debug(3, "Deleted %d frames", deleted_frames);
  return deleted_frames;
}

unsigned int PacketQueue::size() {
  return pktQueue.size();
}

int PacketQueue::packet_count(int stream_id) {
  if ( stream_id < 0 or stream_id > max_stream_id ) {
    Error("Invalid stream_id %d max is %d", stream_id, max_stream_id);
    return -1;
  }
  return packet_counts[stream_id];
}  // end int PacketQueue::packet_count(int stream_id)


// Returns a packet. Packet will be locked
ZMLockedPacket *PacketQueue::get_packet(packetqueue_iterator *it) {
  if (deleting or zm_terminate)
    return nullptr;

  Debug(4, "Locking in get_packet using it %p queue end? %d, packet %p",
      *it, (*it == pktQueue.end()), *(*it));
  std::unique_lock<std::mutex> lck(mutex);
  Debug(4, "Have Lock in get_packet");

  ZMLockedPacket *lp = nullptr;
  while (!lp) {
    while (*it == pktQueue.end()) {
      if (deleting or zm_terminate)
        return nullptr;
      Debug(2, "waiting.  Queue size %d it == end? %d", pktQueue.size(), (*it == pktQueue.end()));
      condition.wait(lck);
    }
    if (deleting or zm_terminate)
      return nullptr;

    ZMPacket *p = *(*it);
    if (!p) {
      Error("Null p?!");
      return nullptr;
    }
    Debug(4, "get_packet using it %p locking index %d, packet %p",
        *it, p->image_index, p);
    // Packets are only deleted by packetqueue, so lock must be held.
    // We shouldn't have to trylock. Someone else might hold the lock but not for long

    lp = new ZMLockedPacket(p);
    if (lp->trylock()) {
      Debug(2, "Locked packet %d, unlocking packetqueue mutex", p->image_index);

      return lp;
    }
    delete lp;
    lp = nullptr;
    condition.wait(lck);
  }  // end while !lp
  return nullptr;
}  // end ZMLockedPacket *PacketQueue::get_packet(it)

void PacketQueue::unlock(ZMLockedPacket *lp) {
  delete lp;
  condition.notify_all();
}

bool PacketQueue::increment_it(packetqueue_iterator *it) {
  Debug(2, "Incrementing %p, queue size %d, end? %d", it, pktQueue.size(), ((*it) == pktQueue.end()));
  if ((*it) == pktQueue.end() or deleting) {
    return false;
  }
  std::unique_lock<std::mutex> lck(mutex);
  ++(*it);
  if (*it != pktQueue.end()) {
    Debug(2, "Incrementing %p, %p still not at end %p, so returning true", it, *it, pktQueue.end());
    return true;
  }
  Debug(2, "At end");
  return false;
}  // end bool PacketQueue::increment_it(packetqueue_iterator *it)

// Increment it only considering packets for a given stream
bool PacketQueue::increment_it(packetqueue_iterator *it, int stream_id) {
  Debug(2, "Incrementing %p, queue size %d, end? %d", it, pktQueue.size(), (*it == pktQueue.end()));
  if ( *it == pktQueue.end() ) {
    return false;
  }

  std::unique_lock<std::mutex> lck(mutex);
  do {
    ++(*it);
  } while ( (*it != pktQueue.end()) and ( (*(*it))->packet.stream_index != stream_id) );

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

  *it = snapshot_it;
  ZM_DUMP_PACKET((*(*it))->packet, "");
  // Step one count back pre_event_count frames as the minimum
  // Do not assume that snapshot_it is video
  // snapshot it might already point to the beginning
  while (( (*it) != pktQueue.begin() ) and pre_event_count) {
    Debug(1, "Previous packet pre_event_count %d stream_index %d keyframe %d",
        pre_event_count, (*(*it))->packet.stream_index, (*(*it))->keyframe);
    ZM_DUMP_PACKET((*(*it))->packet, "");
    if ( (*(*it))->packet.stream_index == video_stream_id ) {
      pre_event_count --;
      if ( ! pre_event_count )
        break;
    }
    (*it)--;
  }
  // it either points to beginning or we have seen pre_event_count video packets.
  
  if ( (*it) == pktQueue.begin() ) {
    Debug(1, "Hit begin");
    // hit end, the first packet in the queue should ALWAYS be a video keyframe.
    // So we should be able to return it.
    if ( pre_event_count ) {
      if ( (*(*it))->image_index < (int)pre_event_count ) {
        // probably just starting up
        Debug(1, "Hit end of packetqueue before satisfying pre_event_count. Needed %d more video frames", pre_event_count);
      } else {
        Warning("Hit end of packetqueue before satisfying pre_event_count. Needed %d more video frames", pre_event_count);
      }
      ZM_DUMP_PACKET((*(*it))->packet, "");
    }
    return it;
  }

  // Not at beginning, so must be pointing at a video keyframe or maybe pre_event_count == 0
  if ( (*(*it))->keyframe ) {
    ZM_DUMP_PACKET((*(*it))->packet, "Found video keyframe, Returning");
    return it;
  }

  while ( --(*it) != pktQueue.begin() ) {
    ZM_DUMP_PACKET((*(*it))->packet, "No keyframe");
    if ( ((*(*it))->packet.stream_index == video_stream_id) and (*(*it))->keyframe )
      return it; // Success
  }
  if ( !(*(*it))->keyframe ) {
    Warning("Hit end of packetqueue before satisfying pre_event_count. Needed %d more video frames", pre_event_count);
  }
  return it;
}  // end packetqueue_iterator *PacketQueue::get_event_start_packet_it

void PacketQueue::dumpQueue() {
  std::list<ZMPacket *>::reverse_iterator it;
  for ( it = pktQueue.rbegin(); it != pktQueue.rend(); ++ it ) {
    ZMPacket *zm_packet = *it;
    ZM_DUMP_PACKET(zm_packet->packet, "");
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
      Debug(2, "waiting for packets in queue.  Queue size %d it == end? %d", pktQueue.size(), ( *it == pktQueue.end() ) );
      condition.wait(lck);
      *it = pktQueue.begin();
    }
    if ( deleting or zm_terminate ) {
      free_it(it);
      delete it;
      return nullptr;
    }
  }

  while ( *it != pktQueue.end() ) {
    ZMPacket *zm_packet = *(*it);
    if (!zm_packet) {
      Error("Null zmpacket in queue!?");
      free_it(it);
      return nullptr;
    }
    Debug(1, "Packet keyframe %d for stream %d, so returning the it to it",
        zm_packet->keyframe, zm_packet->packet.stream_index);
    if (zm_packet->keyframe and ( zm_packet->packet.stream_index == video_stream_id )) {
      Debug(1, "Found a keyframe for stream %d, so returning the it to it", video_stream_id);
      return it;
    }
    ++(*it);
  }
  Debug(1, "DIdn't Found a keyframe for stream %d, so returning the it to it", video_stream_id);
  return it;
}  // get video_it

void PacketQueue::free_it(packetqueue_iterator *it) {
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

bool PacketQueue::is_there_an_iterator_pointing_to_packet(ZMPacket *zm_packet) {
  for (
      std::list<packetqueue_iterator *>::iterator iterators_it = iterators.begin();
      iterators_it != iterators.end();
      ++iterators_it
      ) {
    packetqueue_iterator *iterator_it = *iterators_it;
    if ( *iterator_it == pktQueue.end() ) {
      continue;
    }
    Debug(4, "Checking iterator %p == packet ? %d", (*iterator_it), ( *(*iterator_it) == zm_packet ));
    // Have to check each iterator and make sure it doesn't point to the packet we are about to delete
    if ( *(*iterator_it) == zm_packet ) {
      return true;
    }
  }  // end foreach iterator
  return false;
}

void PacketQueue::setMaxVideoPackets(int p) {
  max_video_packet_count = p;
  Debug(1, "Setting max_video_packet_count to %d", p);
  if ( max_video_packet_count < 0 )
    max_video_packet_count = 0 ;
}
void PacketQueue::setPreEventVideoPackets(int p) {
  pre_event_video_packet_count = p;
  Debug(1, "Setting pre_event_video_packet_count to %d", p);
  if ( pre_event_video_packet_count < 1 )
    pre_event_video_packet_count = 1;
  // We can simplify a lot of logic in queuePacket if we can assume at least 1 packet in queue
}
