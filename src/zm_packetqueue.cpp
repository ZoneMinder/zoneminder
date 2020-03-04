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
#include <sys/time.h>
#include "zm_time.h"

zm_packetqueue::zm_packetqueue( int p_max_stream_id ) {
  max_stream_id = p_max_stream_id;
  packet_counts = new int[max_stream_id+1];
  for ( int i=0; i <= max_stream_id; ++i )
    packet_counts[i] = 0;
}

zm_packetqueue::~zm_packetqueue() {
  clearQueue();
  delete[] packet_counts;
  packet_counts = NULL;
}

bool zm_packetqueue::queuePacket(ZMPacket* zm_packet) {

  if (
      ( zm_packet->packet.dts == AV_NOPTS_VALUE )
      ||
      ( packet_counts[zm_packet->packet.stream_index] <= 0 ) 
    ) {
    Debug(2,"Inserting packet with dts %" PRId64 " because queue %d is empty (queue size: %d) or invalid dts",
        zm_packet->packet.dts, zm_packet->packet.stream_index, packet_counts[zm_packet->packet.stream_index]
        );
    // No dts value, can't so much with it
    pktQueue.push_back(zm_packet);
    packet_counts[zm_packet->packet.stream_index] += 1;
    return true;
  }

#if 0
  std::list<ZMPacket *>::reverse_iterator it = pktQueue.rbegin();

  // Scan through the queue looking for a packet for our stream with a dts <= ours.
  while ( it != pktQueue.rend() ) {
    AVPacket *av_packet = &((*it)->packet);

    Debug(2, "Looking at packet with stream index (%d) with dts %" PRId64,
        av_packet->stream_index, av_packet->dts);
    if ( av_packet->stream_index == zm_packet->packet.stream_index ) {
      if (
          ( av_packet->dts != AV_NOPTS_VALUE )
          &&
          ( av_packet->dts <= zm_packet->packet.dts) 
         ) {
        Debug(2, "break packet with stream index (%d) with dts %" PRId64,
            (*it)->packet.stream_index, (*it)->packet.dts);
        break;
      }
    } else { // Not same stream, compare timestamps
      if ( tvDiffUsec(((*it)->timestamp, zm_packet->timestamp) ) <= 0 ) {
        Debug(2, "break packet with stream index (%d) with dts %" PRId64,
            (*it)->packet.stream_index, (*it)->packet.dts);
        break;
      }
    }
    it++;
  } // end while not the end of the queue

  if ( it != pktQueue.rend() ) {
    Debug(2, "Found packet with stream index (%d) with dts %" PRId64 " <= %" PRId64,
        (*it)->packet.stream_index, (*it)->packet.dts, zm_packet->packet.dts);
    if ( it == pktQueue.rbegin() ) {
       Debug(2,"Inserting packet with dts %" PRId64 " at end", zm_packet->packet.dts);
      // No dts value, can't so much with it
      pktQueue.push_back(zm_packet);
      packet_counts[zm_packet->packet.stream_index] += 1;
      return true;
    }
    // Convert to a forward iterator so that we can insert at end
    std::list<ZMPacket *>::iterator f_it = it.base();

    Debug(2, "Insert packet before packet with stream index (%d) with dts %" PRId64 " for dts %" PRId64,
        (*f_it)->packet.stream_index, (*f_it)->packet.dts, zm_packet->packet.dts);

    pktQueue.insert(f_it, zm_packet);

    packet_counts[zm_packet->packet.stream_index] += 1;
    return true;
  }
  Debug(1,"Unable to insert packet for stream %d with dts %" PRId64 " into queue.",
      zm_packet->packet.stream_index, zm_packet->packet.dts);
#endif
  pktQueue.push_back(zm_packet);
  packet_counts[zm_packet->packet.stream_index] += 1;
  return true;
} // end bool zm_packetqueue::queuePacket(ZMPacket* zm_packet)

bool zm_packetqueue::queuePacket(AVPacket* av_packet) {
  ZMPacket *zm_packet = new ZMPacket(av_packet);
  return queuePacket(zm_packet);
}

ZMPacket* zm_packetqueue::popPacket( ) {
	if ( pktQueue.empty() ) {
		return NULL;
	}

	ZMPacket *packet = pktQueue.front();
	pktQueue.pop_front();
  packet_counts[packet->packet.stream_index] -= 1;

	return packet;
}

unsigned int zm_packetqueue::clearQueue(unsigned int frames_to_keep, int stream_id) {
  
  Debug(3, "Clearing all but %d frames, queue has %d", frames_to_keep, pktQueue.size());
  frames_to_keep += 1;

	if ( pktQueue.empty() ) {
    Debug(3, "Queue is empty");
    return 0;
  }

  std::list<ZMPacket *>::reverse_iterator it;
  ZMPacket *packet = NULL;

  for ( it = pktQueue.rbegin(); it != pktQueue.rend() && frames_to_keep; ++it ) {
    ZMPacket *zm_packet = *it;
    AVPacket *av_packet = &(zm_packet->packet);
       
    Debug(4, "Looking at packet with stream index (%d) with keyframe (%d), frames_to_keep is (%d)",
        av_packet->stream_index, ( av_packet->flags & AV_PKT_FLAG_KEY ), frames_to_keep );
    
    // Want frames_to_keep video keyframes.  Otherwise, we may not have enough
    if ( ( av_packet->stream_index == stream_id) ) {
      //&& ( av_packet->flags & AV_PKT_FLAG_KEY ) ) {
      frames_to_keep --;
    }
  }

  // Make sure we start on a keyframe
  for ( ; it != pktQueue.rend(); ++it ) {
    ZMPacket *zm_packet = *it;
    AVPacket *av_packet = &(zm_packet->packet);
       
    Debug(5, "Looking for keyframe at packet with stream index (%d) with keyframe (%d), frames_to_keep is (%d)",
        av_packet->stream_index, ( av_packet->flags & AV_PKT_FLAG_KEY ), frames_to_keep);
    
    // Want frames_to_keep video keyframes.  Otherwise, we may not have enough
    if ( (av_packet->stream_index == stream_id) && (av_packet->flags & AV_PKT_FLAG_KEY) ) {
      Debug(4, "Found keyframe at packet with stream index (%d) with keyframe (%d), frames_to_keep is (%d)",
          av_packet->stream_index, ( av_packet->flags & AV_PKT_FLAG_KEY ), frames_to_keep);
      break;
    }
  }
  if ( frames_to_keep ) {
    Debug(3, "Hit end of queue, still need (%d) video frames", frames_to_keep);
  }
  if ( it != pktQueue.rend() ) {
    // We want to keep this packet, so advance to the next
    ++it;
  }
  unsigned int delete_count = 0;
  while ( it != pktQueue.rend() ) {
    Debug(4, "Deleting a packet from the front, count is (%d)", delete_count);

    packet = pktQueue.front();
    pktQueue.pop_front();
    packet_counts[packet->packet.stream_index] -= 1;
    delete packet;

    delete_count += 1;
  }    
  packet = NULL; // tidy up for valgrind
  Debug(3, "Deleted %d packets, %d remaining", delete_count, pktQueue.size());
  return delete_count; 
} // end unsigned int zm_packetqueue::clearQueue( unsigned int frames_to_keep, int stream_id )

void zm_packetqueue::clearQueue() {
  ZMPacket *packet = NULL;
  int delete_count = 0;
	while ( !pktQueue.empty() ) {
    packet = pktQueue.front();
    packet_counts[packet->packet.stream_index] -= 1;
    pktQueue.pop_front();
    delete packet;
    delete_count += 1;
	}
  Debug(3, "Deleted (%d) packets", delete_count );
}

// clear queue keeping only specified duration of video -- return number of pkts removed
unsigned int zm_packetqueue::clearQueue(struct timeval *duration, int streamId) {

  if (pktQueue.empty()) {
    return 0;
  }
  struct timeval keep_from;
  std::list<ZMPacket *>::reverse_iterator it;
  it = pktQueue.rbegin();

  timersub(&(*it)->timestamp, duration, &keep_from);
  ++it;

  Debug(3, "Looking for frame before queue keep time with  stream id (%d), queue has %d packets",
        streamId, pktQueue.size());
  for ( ; it != pktQueue.rend(); ++it) {
    ZMPacket *zm_packet = *it;
    AVPacket *av_packet = &(zm_packet->packet);
    if (av_packet->stream_index == streamId
        && timercmp( &zm_packet->timestamp, &keep_from, <= )) {
        Debug(3, "Found frame before keep time with stream index %d at %d.%d",
                 av_packet->stream_index,
                 zm_packet->timestamp.tv_sec,
                 zm_packet->timestamp.tv_usec);
        break;
    }
  }

  if (it == pktQueue.rend()) {
    Debug(1, "Didn't find a frame before queue preserve time. keeping all");
    return 0;
  }

  Debug(3, "Looking for keyframe");
  for ( ; it != pktQueue.rend(); ++it) {
    ZMPacket *zm_packet = *it;
    AVPacket *av_packet = &(zm_packet->packet);
    if (av_packet->flags & AV_PKT_FLAG_KEY
        && av_packet->stream_index == streamId) {
      Debug(3, "Found keyframe before start with stream index %d at %d.%d",
               av_packet->stream_index,
               zm_packet->timestamp.tv_sec,
               zm_packet->timestamp.tv_usec );
      break;
    }
  }
  if ( it == pktQueue.rend() ) {
    Debug(1, "Didn't find a keyframe before event starttime. keeping all" );
    return 0;
  }

  unsigned int deleted_frames = 0;
  ZMPacket *zm_packet = NULL;
  while (distance(it, pktQueue.rend()) > 1) {
    zm_packet = pktQueue.front();
    pktQueue.pop_front();
    packet_counts[zm_packet->packet.stream_index] -= 1;
    delete zm_packet;
    deleted_frames += 1;
  }
  zm_packet = NULL;
  Debug(3, "Deleted %d frames", deleted_frames);

  return deleted_frames;
}

unsigned int zm_packetqueue::size() {
  return pktQueue.size();
}

int zm_packetqueue::packet_count( int stream_id ) {
  return packet_counts[stream_id];
} // end int zm_packetqueue::packet_count( int stream_id )

// Clear packets before the given timestamp.
// Must also take into account pre_event_count frames
void zm_packetqueue::clear_unwanted_packets(
    timeval *recording_started,
    int pre_event_count,
    int mVideoStreamId) {
  // Need to find the keyframe <= recording_started.  Can get rid of audio packets.
	if ( pktQueue.empty() )
		return;

  // Step 1 - find frame <= recording_started.
  // Step 2 - go back pre_event_count
  // Step 3 - find a keyframe
  // Step 4 - pop packets until we get to the packet in step 3
  std::list<ZMPacket *>::reverse_iterator it;

  // Step 1 - find frame <= recording_started.
  Debug(3, "Looking for frame before start (%d.%d) recording stream id (%d), queue has %d packets",
      recording_started->tv_sec, recording_started->tv_usec, mVideoStreamId, pktQueue.size());
  for ( it = pktQueue.rbegin(); it != pktQueue.rend(); ++ it ) {
    ZMPacket *zm_packet = *it;
    AVPacket *av_packet = &(zm_packet->packet);
    if (
        ( av_packet->stream_index == mVideoStreamId )
        &&
        timercmp( &(zm_packet->timestamp), recording_started, <= )
       ) {
    Debug(3, "Found frame before start with stream index %d at %d.%d",
        av_packet->stream_index,
        zm_packet->timestamp.tv_sec,
        zm_packet->timestamp.tv_usec);
      break;
    }
    Debug(3, "Not Found frame before start with stream index %d at %d.%d",
        av_packet->stream_index,
        zm_packet->timestamp.tv_sec,
        zm_packet->timestamp.tv_usec);
  }

  if ( it == pktQueue.rend() ) {
    Info("Didn't find a frame before event starttime. keeping all");
    return;
  }

  Debug(1, "Seeking back %d frames", pre_event_count);
  for ( ; pre_event_count && (it != pktQueue.rend()); ++ it ) {
    ZMPacket *zm_packet = *it;
    AVPacket *av_packet = &(zm_packet->packet);
    if ( av_packet->stream_index == mVideoStreamId ) {
      --pre_event_count;
    }
  }

  if ( it == pktQueue.rend() ) {
    Debug(1, "ran out of pre_event frames before event starttime. keeping all");
    return;
  }

  Debug(3, "Looking for keyframe");
  for ( ; it != pktQueue.rend(); ++ it ) {
    ZMPacket *zm_packet = *it;
    AVPacket *av_packet = &(zm_packet->packet);
    if ( 
        ( av_packet->flags & AV_PKT_FLAG_KEY ) 
        && 
        ( av_packet->stream_index == mVideoStreamId )
       ) {
    Debug(3, "Found keyframe before start with stream index %d at %d.%d",
        av_packet->stream_index,
        zm_packet->timestamp.tv_sec,
        zm_packet->timestamp.tv_usec );
      break;
    }
  }
  if ( it == pktQueue.rend() ) {
    Debug(1, "Didn't find a keyframe before event starttime. keeping all" );
    return;
  }

  ZMPacket *zm_packet = *it;
  AVPacket *av_packet = &(zm_packet->packet);
  Debug(3, "Found packet before start with stream index (%d) with keyframe (%d), distance(%d), size(%d)", 
      av_packet->stream_index, 
      ( av_packet->flags & AV_PKT_FLAG_KEY ), 
      distance( it, pktQueue.rend() ),
      pktQueue.size() );

  unsigned int deleted_frames = 0;
  ZMPacket *packet = NULL;
  while ( distance(it, pktQueue.rend()) > 1 ) {
  //while ( pktQueue.rend() != it ) {
    packet = pktQueue.front();
    pktQueue.pop_front();
    packet_counts[packet->packet.stream_index] -= 1;
    delete packet;
    deleted_frames += 1;
  }
  packet = NULL; // tidy up for valgrind

  zm_packet = pktQueue.front();
  av_packet = &(zm_packet->packet);
  if ( ( ! ( av_packet->flags & AV_PKT_FLAG_KEY ) ) || ( av_packet->stream_index != mVideoStreamId ) ) {
    Error( "Done looking for keyframe.  Deleted %d frames. Remaining frames in queue: %d stream of head packet is (%d), keyframe (%d), distance(%d), packets(%d)",
        deleted_frames, pktQueue.size(), av_packet->stream_index, ( av_packet->flags & AV_PKT_FLAG_KEY ), distance( it, pktQueue.rend() ), pktQueue.size() );
  } else {
    Debug(1, "Done looking for keyframe.  Deleted %d frames. Remaining frames in queue: %d stream of head packet is (%d), keyframe (%d), distance(%d), packets(%d)",
        deleted_frames, pktQueue.size(), av_packet->stream_index, ( av_packet->flags & AV_PKT_FLAG_KEY ), distance( it, pktQueue.rend() ), pktQueue.size() );
  }
} // end void zm_packetqueue::clear_unwanted_packets( timeval *recording_started, int mVideoStreamId )

void zm_packetqueue::dumpQueue() {
  std::list<ZMPacket *>::reverse_iterator it;
  for ( it = pktQueue.rbegin(); it != pktQueue.rend(); ++ it ) {
    ZMPacket *zm_packet = *it;
    AVPacket *av_packet = &(zm_packet->packet);
    dumpPacket(av_packet);
  }
}
