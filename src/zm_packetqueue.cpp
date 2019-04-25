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

zm_packetqueue::zm_packetqueue( int video_image_count, int p_video_stream_id, int p_audio_stream_id ) {
  video_stream_id = p_video_stream_id;
  max_video_packet_count = video_image_count-1;
  video_packet_count = 0;
  analysis_it = pktQueue.begin();
  first_video_packet_index = -1;
  Debug(4, "packetqueue init, first_video_packet_index is %d", first_video_packet_index);

  max_stream_id = p_video_stream_id > p_audio_stream_id ? p_video_stream_id : p_audio_stream_id;
  packet_counts = new int[max_stream_id+1];
  for ( int i=0; i <= max_stream_id; ++i )
    packet_counts[i] = 0;
  condition = new Condition(mutex);
}

zm_packetqueue::~zm_packetqueue() {
  clearQueue();
  delete[] packet_counts;
  packet_counts = NULL;
  delete condition;
  condition = NULL;
}

/* Enqueues the given packet.  Will maintain the analysis_it pointer and image packet counts.
 * If we have reached our max image packet count, it will pop off as many packets as are needed.
 * Thus it will ensure that the same packet never gets queued twice.
 */

bool zm_packetqueue::queuePacket( ZMPacket* zm_packet ) {
  Debug(4, "packetqueue queuepacket, first_video_packet_index is %d", first_video_packet_index);
  mutex.lock();

#if 0
  if ( zm_packet->image_index != -1 ) {
    // It's a video packet

    // If we can never queue the same packet, then they can never go past
    if ( zm_packet->image_index == first_video_packet_index ) {
      Debug(2, "queuing packet that is already on the queue(%d)", zm_packet->image_index);
      while ( pktQueue.size() ) {
        mutex.unlock();
        ZMPacket *p = popPacket();
        Debug(2,"Front packet index: %d", p->image_index);
        mutex.lock();

        if ( p->codec_type != AVMEDIA_TYPE_VIDEO ) {
          Debug(2, "Deleting audio frame(%d)", p->image_index);
          delete p;
          p = NULL;
        }
        Debug(2,"pktQueue.size(%d)", pktQueue.size());
        if ( p->image_index == zm_packet->image_index )
          break;
      } // end while there are packets at the head of the queue that are not this one

      if ( analysis_it == pktQueue.end() ) {
        // Analsys_it should only point to end when queue is empty
        Debug(2,"pointing analysis_it to begining");
        analysis_it = pktQueue.begin();
      }
    } 
    if ( first_video_packet_index == -1 ) {
      // Initialize the first_video_packet indicator
      first_video_packet_index = zm_packet->image_index;
    } // end if
    video_packet_count += 1;
  } // end if queuing a video packet
#endif
  if ( zm_packet->packet.stream_index == video_stream_id ) {
    video_packet_count += 1;
  }
	pktQueue.push_back(zm_packet);
  packet_counts[zm_packet->packet.stream_index] += 1;
  if ( analysis_it == pktQueue.end() ) {
    // Analsys_it should only point to end when queue is empty
    Debug(2,"pointing analysis_it to begining");
    analysis_it = pktQueue.begin();
  }

#if 0
  // This code should not be neccessary. Taken care of by the above code that ensure that no packet appears twice
  if ( zm_packet->codec_type == AVMEDIA_TYPE_VIDEO ) {
    video_packet_count += 1;
    if ( video_packet_count >= max_video_packet_count ) 
      clearQueue(max_video_packet_count, video_stream_id);
  }
#endif

  // We signal on every packet because someday we may analyze sound
  Debug(2,"Signalling");
  condition->signal();
  mutex.unlock();

	return true;
} // end bool zm_packetqueue::queuePacket(ZMPacket* zm_packet)

ZMPacket* zm_packetqueue::popPacket( ) {
	if ( pktQueue.empty() ) {
		return NULL;
	}
  Debug(2,"Mutex locking");
  mutex.lock();
  Debug(2,"Have Mutex lock");

	ZMPacket *packet = pktQueue.front();
  if ( *analysis_it == packet )
    ++analysis_it;

	pktQueue.pop_front();
  if ( packet->codec_type == AVMEDIA_TYPE_VIDEO ) {
    video_packet_count -= 1;
    if ( video_packet_count ) {
      // There is another video packet, so it must be the next one
      Debug(2,"Incrementing first video packet index was (%d)", first_video_packet_index);
      first_video_packet_index += 1;
      first_video_packet_index %= max_video_packet_count;
    } else {
      first_video_packet_index = -1;
    }
  }
  packet_counts[packet->packet.stream_index] -= 1;
  mutex.unlock();

  // Should we lock the packet?
	return packet;
}

unsigned int zm_packetqueue::clearQueue(unsigned int frames_to_keep, int stream_id) {
  
  Debug(3, "Clearing all but %d frames, queue has %d", frames_to_keep, pktQueue.size());

	if ( pktQueue.empty() ) {
    return 0;
  }
  frames_to_keep += 1;
  if ( pktQueue.size() <= frames_to_keep ) {
    return 0;
  }
  mutex.lock();
  int packets_to_delete = pktQueue.size();

  std::list<ZMPacket *>::reverse_iterator it;
  ZMPacket *packet = NULL;

  for ( it = pktQueue.rbegin(); frames_to_keep && (it != pktQueue.rend()); ++it ) {
    ZMPacket *zm_packet = *it;
    AVPacket *av_packet = &(zm_packet->packet);
       
    Debug(4, "Looking at packet with stream index (%d) with keyframe(%d), Image_index(%d) frames_to_keep is (%d)",
        av_packet->stream_index, zm_packet->keyframe, zm_packet->image_index, frames_to_keep );
    
    // Want frames_to_keep video keyframes.  Otherwise, we may not have enough
    if ( av_packet->stream_index == stream_id ) {
      frames_to_keep --;
      packets_to_delete --;
    }
  }

  // Make sure we start on a keyframe
  for ( ; it != pktQueue.rend(); ++it ) {
    ZMPacket *zm_packet = *it;
    AVPacket *av_packet = &(zm_packet->packet);
       
    Debug(5, "Looking for keyframe at packet with stream index (%d) with keyframe (%d), image_index(%d) frames_to_keep is (%d)",
        av_packet->stream_index, ( av_packet->flags & AV_PKT_FLAG_KEY ), zm_packet->image_index, frames_to_keep );
    
    // Want frames_to_keep video keyframes.  Otherwise, we may not have enough
    if ( ( av_packet->stream_index == stream_id) && ( av_packet->flags & AV_PKT_FLAG_KEY ) ) {
      Debug(4, "Found keyframe at packet with stream index (%d) with keyframe (%d), frames_to_keep is (%d)",
          av_packet->stream_index, ( av_packet->flags & AV_PKT_FLAG_KEY ), frames_to_keep );
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

      packet = pktQueue.front();
      if ( *analysis_it == packet )
        ++analysis_it;
      if ( packet->codec_type == AVMEDIA_TYPE_VIDEO ) {
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
      packet_counts[packet->packet.stream_index] -= 1;
      pktQueue.pop_front();
      //if ( packet->image_index == -1 )
        delete packet;

      delete_count += 1;
    } // while our iterator is not the first packet
  } // end if have packet_delete_count 
  packet = NULL; // tidy up for valgrind
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
} // end unsigned int zm_packetqueue::clearQueue( unsigned int frames_to_keep, int stream_id )

void zm_packetqueue::clearQueue() {
  mutex.lock();
  ZMPacket *packet = NULL;
  int delete_count = 0;
	while ( !pktQueue.empty() ) {
    packet = pktQueue.front();
    packet_counts[packet->packet.stream_index] -= 1;
    pktQueue.pop_front();
    //if ( packet->image_index == -1 )
      delete packet;
    delete_count += 1;
	}
  Debug(3, "Deleted (%d) packets", delete_count );
  video_packet_count = 0;
  first_video_packet_index = -1;
  analysis_it = pktQueue.begin();
  mutex.unlock();
}

unsigned int zm_packetqueue::size() {
  return pktQueue.size();
}

unsigned int zm_packetqueue::get_video_packet_count() {
  return video_packet_count;
}

int zm_packetqueue::packet_count( int stream_id ) {
  return packet_counts[stream_id];
} // end int zm_packetqueue::packet_count( int stream_id )

// Returns a packet to analyse or NULL
ZMPacket *zm_packetqueue::get_analysis_packet() {

  mutex.lock();
  while ( ((! pktQueue.size()) || ( analysis_it == pktQueue.end() )) && !zm_terminate ) {
    Debug(2,"waiting.  Queue size %d analysis_it == end? %d", pktQueue.size(), ( analysis_it == pktQueue.end() ) );
    condition->wait();
  }

//Debug(2, "Distance from head: (%d)", std::distance( pktQueue.begin(), analysis_it ) );
  //Debug(2, "Distance from end: (%d)", std::distance( analysis_it, pktQueue.end() ) );
  ZMPacket *p = *analysis_it;
  Debug(2,"analysis_packet image_index: %d, about to lock packet", p->image_index);
  p->lock();
  Debug(2, "Locked packet, unlocking mutex");
  mutex.unlock();

  return p;
} // end ZMPacket *zm_packetqueue::get_analysis_packet()

// The idea is that analsys_it will only be == end() if the queue is empty
// probvlem here is that we don't want to analyse a packet twice. Maybe we can flag the packet analysed
bool zm_packetqueue::increment_analysis_it( ) {
  // We do this instead of distance becuase distance will traverse the entire list in the worst case
  std::list<ZMPacket *>::iterator next_it = analysis_it;
  ++ next_it;
  if ( next_it == pktQueue.end() ) {
    return false;
  }
  analysis_it = next_it;
  return true;
} // end bool zm_packetqueue::increment_analysis_it( )
