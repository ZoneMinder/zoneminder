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


#include "zm_packetqueue.h"
#include "zm_ffmpeg.h"
#include "zm_signal.h"
#include <sys/time.h>
#include "zm_time.h"

zm_packetqueue::zm_packetqueue(
    int video_image_count,
    int p_video_stream_id,
    int p_audio_stream_id
    ):
video_stream_id(p_video_stream_id),
  deleting(false)
{
  video_stream_id = p_video_stream_id;
  max_video_packet_count = video_image_count-1;
  analysis_it = pktQueue.begin();

  max_stream_id = p_video_stream_id > p_audio_stream_id ? p_video_stream_id : p_audio_stream_id;
  packet_counts = new int[max_stream_id+1];
  for ( int i=0; i <= max_stream_id; ++i )
    packet_counts[i] = 0;
}

zm_packetqueue::~zm_packetqueue() {
  deleting = true;
  Debug(4, "In destructor");
  /* zma might be waiting. Must have exclusive access */
  while ( ! mutex.try_lock() ) {
    Debug(4, "Waiting for exclusive access");
    condition.notify_all();
  }

  while ( !pktQueue.empty() ) {
    Debug(4, "Fronting packet %d", pktQueue.empty());
    ZMPacket * packet = pktQueue.front();
    Debug(4, "poppng packet %d", packet->image_index);
    pktQueue.pop_front();
    if ( packet->image_index == -1 ) {
      Debug(4, "Deletng packet");
      delete packet;
    }
  }

  delete[] packet_counts;
  Debug(4, "Done in destrcutor");
  packet_counts = nullptr;
  mutex.unlock();
  condition.notify_all();
}

/* Enqueues the given packet.  Will maintain the analysis_it pointer and image packet counts.
 * If we have reached our max image packet count, it will pop off as many packets as are needed.
 * Thus it will ensure that the same packet never gets queued twice.
 */

bool zm_packetqueue::queuePacket(ZMPacket* zm_packet) {
  Debug(4, "packetqueue queuepacket");
  mutex.lock();

	pktQueue.push_back(zm_packet);
  packet_counts[zm_packet->packet.stream_index] += 1;
  if ( analysis_it == pktQueue.end() ) {
    // Analsys_it should only point to end when queue is empty
    Debug(4, "pointing analysis_it to back");
    analysis_it --;
  }

  mutex.unlock();
  // We signal on every packet because someday we may analyze sound
  Debug(4, "packetqueue queuepacket, unlocked signalling");
  condition.notify_all();

  // We have added to the queue and signalled so other processes can now work on the new packet.
  // Now to clean ups mainting the queue size.
  while ( packet_counts[video_stream_id] > max_video_packet_count ) {
    //clearQueue(max_video_packet_count, video_stream_id);
    //clearQueue is rather heavy.  Since this is the only packet injection spot, we can just start at the beginning of the queue and remove packets until we get to the next video keyframe
    Debug(1, "Deleting a packet with stream index (%d) with keyframe(%d), Image_index(%d) video_frames_to_keep is (%d) max: %d",
        zm_packet->packet.stream_index, zm_packet->keyframe, zm_packet->image_index, packet_counts[video_stream_id] , max_video_packet_count);
    ZMPacket *zm_packet = *pktQueue.begin();
    pktQueue.pop_front();
    packet_counts[zm_packet->packet.stream_index] -= 1;
    if ( zm_packet->image_index == -1 )
      delete zm_packet;
  }

	return true;
} // end bool zm_packetqueue::queuePacket(ZMPacket* zm_packet)

ZMPacket* zm_packetqueue::popPacket( ) {
  Debug(4, "pktQueue size %d", pktQueue.size());
	if ( pktQueue.empty() ) {
		return nullptr;
	}
  Debug(4, "poPacket Mutex locking");
  mutex.lock();

	ZMPacket *packet = pktQueue.front();
  if ( *analysis_it == packet ) {
    Debug(4, "not popping analysis_it index %d", packet->image_index);
    mutex.unlock();
    return nullptr;
  }
  packet->lock();

	pktQueue.pop_front();
  packet_counts[packet->packet.stream_index] -= 1;

  mutex.unlock();

	return packet;
}  // popPacket


/* Keeps frames_to_keep frames of the provided stream, which theoretically is the video stream
 * Basically it starts at the end, moving backwards until it finds the minimum video frame.
 * Then it should probably move forward to find a keyframe.  The first video frame must always be a keyframe.
 * So really frames_to_keep is a maximum which isn't so awesome.. maybe we should go back  farther to find the keyframe in which case
 * frames_to_keep in a minimum
 */

unsigned int zm_packetqueue::clearQueue(unsigned int frames_to_keep, int stream_id) {
  Debug(3, "Clearing all but %d frames, queue has %d", frames_to_keep, pktQueue.size());

	if ( pktQueue.empty() ) {
    return 0;
  }

  // If size is <= frames_to_keep since it could contain audio, we can't possibly do anything
  if ( pktQueue.size() <= frames_to_keep ) {
    return 0;
  }
  Debug(4, "Locking in clearQueue");
  mutex.lock();

  std::list<ZMPacket *>::iterator it = pktQueue.end()--;  // point to last element instead of end
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
    if ( *analysis_it == zm_packet ) {
      Debug(4, "Bumping analysis it because it is at the front that we are deleting");
      ++analysis_it;
    }
    packet_counts[zm_packet->packet.stream_index] --;
    pktQueue.pop_front();
    if ( zm_packet->image_index == -1 )
      delete zm_packet;

    delete_count += 1;
  } // while our iterator is not the first packet
  zm_packet = nullptr; // tidy up for valgrind
  Debug(3, "Deleted %d packets, %d remaining", delete_count, pktQueue.size());
  mutex.unlock();
  return delete_count; 

# if 0
  // I forget why +1
  frames_to_keep += 1;
  int packets_to_delete = pktQueue.size();

  std::list<ZMPacket *>::reverse_iterator it;
  ZMPacket *zm_packet = nullptr;

  for ( it = pktQueue.rbegin(); frames_to_keep && (it != pktQueue.rend()); ++it ) {
    zm_packet = *it;
    AVPacket *av_packet = &(zm_packet->packet);
       
    Debug(3, "Looking at packet with stream index (%d) with keyframe(%d), Image_index(%d) frames_to_keep is (%d)",
        av_packet->stream_index, zm_packet->keyframe, zm_packet->image_index, frames_to_keep );
    
    // Want frames_to_keep video keyframes.  Otherwise, we may not have enough
    if ( av_packet->stream_index == stream_id ) {
      frames_to_keep --;
      packets_to_delete --;
    }
  }

  // Make sure we start on a keyframe
  for ( ; it != pktQueue.rend(); ++it ) {
    zm_packet = *it;
    AVPacket *av_packet = &(zm_packet->packet);
       
    Debug(3, "Looking for keyframe at packet with stream index (%d) with keyframe (%d), image_index(%d) frames_to_keep is (%d)",
        av_packet->stream_index, ( av_packet->flags & AV_PKT_FLAG_KEY ), zm_packet->image_index, frames_to_keep );
    
    // Want frames_to_keep video keyframes.  Otherwise, we may not have enough
    if ( (av_packet->stream_index == stream_id) and (av_packet->flags & AV_PKT_FLAG_KEY) ) {
      Debug(3, "Found keyframe at packet with stream index (%d) with keyframe (%d), frames_to_keep is (%d)",
          av_packet->stream_index, ( av_packet->flags & AV_PKT_FLAG_KEY ), frames_to_keep);
      break;
    }
    packets_to_delete--;
  }
  if ( frames_to_keep ) {
    Debug(3, "Hit end of queue, still need (%d) video frames", frames_to_keep);
  }
  if ( it != pktQueue.rend() ) {
    // We want to keep this packet, so advance to the next
    ++it;
    packets_to_delete--;
  }
  int delete_count = 0;

  if ( packets_to_delete > 0 ) {
    Debug(4, "Deleting packets from the front, count is (%d)", packets_to_delete);
    while ( --packets_to_delete ) {
      Debug(4, "Deleting a packet from the front, count is (%d), queue size is %d",
          delete_count, pktQueue.size());

      zm_packet = pktQueue.front();
      if ( *analysis_it == zm_packet ) {
        Debug(4, "Bumping analysis it because it is at the front that we are deleting");
        ++analysis_it;
      }
      if ( zm_packet->codec_type == AVMEDIA_TYPE_VIDEO ) {
        video_packet_count -= 1;
        if ( video_packet_count ) {
          // There is another video packet, so it must be the next one
          first_video_packet_index += 1;
          first_video_packet_index %= max_video_packet_count;
        } else {
          // Re-init
          first_video_packet_index = -1;
        }
      }
      packet_counts[zm_packet->packet.stream_index] -= 1;
      pktQueue.pop_front();
      if ( zm_packet->image_index == -1 )
        delete zm_packet;

      delete_count += 1;
    } // while our iterator is not the first packet
  } // end if have packet_delete_count 
  zm_packet = nullptr; // tidy up for valgrind
  Debug(3, "Deleted %d packets, %d remaining", delete_count, pktQueue.size());

#if 0
  if ( pktQueue.size() ) {
    packet = pktQueue.front();
    first_video_packet_index = packet->image_index;
  } else {
    first_video_packet_index = -1;
  }
#endif

  Debug(3, "Deleted packets, resulting size is %d", pktQueue.size());
  mutex.unlock();
  return delete_count; 
# endif
} // end unsigned int zm_packetqueue::clearQueue( unsigned int frames_to_keep, int stream_id )

void zm_packetqueue::clearQueue() {
  Debug(4, "Clocking in clearQueue");
  mutex.lock();
  ZMPacket *packet = nullptr;
  int delete_count = 0;
	while ( !pktQueue.empty() ) {
    packet = pktQueue.front();
    packet_counts[packet->packet.stream_index] -= 1;
    pktQueue.pop_front();
    if ( packet->image_index == -1 )
      delete packet;
    delete_count += 1;
	}
  Debug(3, "Deleted (%d) packets", delete_count );
  analysis_it = pktQueue.begin();
  mutex.unlock();
}

// clear queue keeping only specified duration of video -- return number of pkts removed
unsigned int zm_packetqueue::clearQueue(struct timeval *duration, int streamId) {

  if ( pktQueue.empty() ) {
    return 0;
  }
  Debug(4, "Locking in clearQueue");
  mutex.lock();

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
    mutex.unlock();
    return 0;
  }

  unsigned int deleted_frames = 0;
  ZMPacket *zm_packet = nullptr;
  while ( distance(it, pktQueue.rend()) > 1 ) {
    zm_packet = pktQueue.front();
    if ( *analysis_it == zm_packet ) {
      ++analysis_it;
    }
    pktQueue.pop_front();
    packet_counts[zm_packet->packet.stream_index] -= 1;
    if ( zm_packet->image_index == -1 )
      delete zm_packet;
    deleted_frames += 1;
  }
  zm_packet = nullptr;
  Debug(3, "Deleted %d frames", deleted_frames);
  mutex.unlock();

  return deleted_frames;
}

unsigned int zm_packetqueue::size() {
  return pktQueue.size();
}

int zm_packetqueue::packet_count( int stream_id ) {
  return packet_counts[stream_id];
} // end int zm_packetqueue::packet_count( int stream_id )


// Returns a packet to analyse or NULL
ZMPacket *zm_packetqueue::get_analysis_packet() {

  Debug(4, "Locking in get_analysis_packet");
  std::unique_lock<std::mutex> lck(mutex);

  while ( ((! pktQueue.size()) or ( analysis_it == pktQueue.end() )) and !zm_terminate and !deleting ) {
    Debug(2, "waiting.  Queue size %d analysis_it == end? %d", pktQueue.size(), ( analysis_it == pktQueue.end() ) );
    condition.wait(lck);
  }
  if ( deleting ) {
    return nullptr;
  }

//Debug(2, "Distance from head: (%d)", std::distance( pktQueue.begin(), analysis_it ) );
  //Debug(2, "Distance from end: (%d)", std::distance( analysis_it, pktQueue.end() ) );
  ZMPacket *p = *analysis_it;
  Debug(3, "get_analysis_packet image_index: %d, about to lock packet", p->image_index);
  while ( !p->trylock() and !zm_terminate ) {
    Debug(3, "waiting.  Queue size %d analysis_it == end? %d", pktQueue.size(), ( analysis_it == pktQueue.end() ) );
    condition.wait(lck);
    if ( deleting ) {
      // packetqueue is being deleted, do not assume we have a lock on the packet
      return nullptr;
    }
  }
  Debug(2, "Locked packet, unlocking packetqueue mutex");
  return p;
} // end ZMPacket *zm_packetqueue::get_analysis_packet()

// The idea is that analsys_it will only be == end() if the queue is empty
// probvlem here is that we don't want to analyse a packet twice. Maybe we can flag the packet analysed
bool zm_packetqueue::increment_analysis_it( ) {
  // We do this instead of distance becuase distance will traverse the entire list in the worst case
  if ( analysis_it != pktQueue.end() ) {
    ++analysis_it;
    if ( (analysis_it == pktQueue.end()) ) {
      Debug(3, "Incrementing analysis it %d", (analysis_it == pktQueue.end()) );
    } else {
      Debug(3, "Incrementing analysis it %d %d", (analysis_it == pktQueue.end()), (*analysis_it)->image_index);
    }
  } else {
    Debug(3, "Not Incrementing analysis it %d", (analysis_it == pktQueue.end()));
  }
  return true;

  std::list<ZMPacket *>::iterator next_it = analysis_it;
  ++ next_it;
  if ( next_it == pktQueue.end() ) {
    return false;
  }
  analysis_it = next_it;
  return true;
} // end bool zm_packetqueue::increment_analysis_it( )


std::list<ZMPacket *>::iterator zm_packetqueue::get_event_start_packet_it(
    std::list<ZMPacket *>::iterator snapshot_it,
    unsigned int pre_event_count
    ) {

  std::list<ZMPacket *>::iterator it = snapshot_it;
  dumpPacket( &((*it)->packet ) );
  // Step one count back pre_event_count frames as the minimum
  // Do not assume that snapshot_it is video
  Debug(1, "Checking for keyframe %p", *it);
  Debug(1, "Checking for keyframe begin %p", *(pktQueue.begin()));
  // snapshot it might already point to the beginning
  while ( ( it != pktQueue.begin() ) and pre_event_count ) {
    Debug(1, "Previous packet pre %d index %d keyframe %d", pre_event_count, (*it)->image_index, (*it)->keyframe);
    dumpPacket( &((*it)->packet ) );
    // Is video, maybe should compare stream_id instead
    if ( (*it)->image_index != -1 ) {
      pre_event_count --;
    }
    it--;
  }
  if ( it == pktQueue.begin() ) {
    Debug(1, "Hit begin");
    // hit end, the first packet in the queue should ALWAYS be a video keyframe.
    // So we should be able to return it.
    if ( pre_event_count )
      Warning("Hit end of packetqueue before satisfying pre_event_count. Needed %d more video frames", pre_event_count);
    return it;
  }
  Debug(1, "Checking for keyframe %p", *it);
  if ( (*it)->keyframe ) {
    Debug(1, "Returning");
    Debug(1, "Previous packet pre %d index %d keyframe %d", pre_event_count, (*it)->image_index, (*it)->keyframe);
    return it;
  }
  Debug(1, "Wasnt Checking for keyframe");

  while ( ( it-- != pktQueue.begin() ) and ! (*it)->keyframe ) {
    Debug(1, "No keyframe");
    dumpPacket( &((*it)->packet ) );
  }
  Debug(1, "Checking for keyframe");
  if ( !(*it)->keyframe ) {
      Warning("Hit end of packetqueue before satisfying pre_event_count. Needed %d more video frames", pre_event_count);
  }
  return it;

#if 0
  std::list<ZMPacket *>::iterator it = snapshot_it.base();
  // Step one count back pre_event_count frames as the minimum
  // Do not assume that snapshot_it is video
  while ( ( it++ != pktQueue.rend() ) and pre_event_count ) {
    // Is video, maybe should compare stream_id instead
    if ( *it->image_index != -1 ) {
      pre_event_count --;
    }
  }
  if ( it == pktQueue.rend() ) {
    // hit end, the first packet in the queue should ALWAYS be a video keyframe.
    // So we should be able to return it.
    if ( pre_event_count )
      Warning("Hit end of packetqueue before satisfying pre_event_count. Needed %d more video frames", pre_event_count);
    return it.base();
  }
  if ( *it->keyframe ) {
    return (it++).base();
  }

  while ( ( it++ != pktQueue.rend() ) and ! (*it)->keyframe ) { }
  if ( it == pktQueue.rend() ) {
    // hit end, the first packet in the queue should ALWAYS be a video keyframe.
    // So we should be able to return it.
    if ( pre_event_count )
      Warning("Hit end of packetqueue before satisfying pre_event_count. Needed %d more video frames", pre_event_count);
    return it.base();
  }
  return (it++).base();
#endif
}

void zm_packetqueue::dumpQueue() {
  std::list<ZMPacket *>::reverse_iterator it;
  for ( it = pktQueue.rbegin(); it != pktQueue.rend(); ++ it ) {
    ZMPacket *zm_packet = *it;
    AVPacket *av_packet = &(zm_packet->packet);
    dumpPacket(av_packet);
  }
}
