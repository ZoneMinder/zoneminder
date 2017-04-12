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

#define VIDEO_QUEUESIZE 200
#define AUDIO_QUEUESIZE 50

using namespace std;

zm_packetqueue::zm_packetqueue(){

}

zm_packetqueue::~zm_packetqueue() {

}

bool zm_packetqueue::queuePacket( ZMPacket* zm_packet ) {
	pktQueue.push_back( zm_packet );

	return true;
}
bool zm_packetqueue::queuePacket( AVPacket* av_packet ) {
    
  ZMPacket *zm_packet = new ZMPacket( av_packet );
 
	pktQueue.push_back( zm_packet );

	return true;
}

ZMPacket* zm_packetqueue::popPacket( ) {
	if ( pktQueue.empty() ) {
		return NULL;
	}

	ZMPacket *packet = pktQueue.front();
	pktQueue.pop_front();

	return packet;
}

unsigned int zm_packetqueue::clearQueue( unsigned int frames_to_keep, int stream_id ) {
  
  Debug(3, "Clearing all but %d frames", frames_to_keep );
  frames_to_keep += 1;

	if ( pktQueue.empty() ) {
    Debug(3, "Queue is empty");
    return 0;
  } else {
    Debug(3, "Queue has (%d)", pktQueue.size() );
  }

  list<ZMPacket *>::reverse_iterator it;
  ZMPacket *packet = NULL;

  for ( it = pktQueue.rbegin(); it != pktQueue.rend() && frames_to_keep; ++it ) {
    ZMPacket *zm_packet = *it;
    AVPacket *av_packet = &(zm_packet->packet);
       
    Debug(3, "Looking at packet with stream index (%d) with keyframe (%d), frames_to_keep is (%d)", av_packet->stream_index, ( av_packet->flags & AV_PKT_FLAG_KEY ), frames_to_keep );
    
    // Want frames_to_keep video keyframes.  Otherwise, we may not have enough
    if ( ( av_packet->stream_index == stream_id) && ( av_packet->flags & AV_PKT_FLAG_KEY ) ) {
      if (!frames_to_keep)
        break;
      frames_to_keep --;
    }
  }
  unsigned int delete_count = 0;
  while ( it != pktQueue.rend() ) {
    Debug(3, "Deleting a packet from the front, count is (%d)", delete_count );

    packet = pktQueue.front();
    pktQueue.pop_front();
    delete packet;

    delete_count += 1;
  }    
  return delete_count; 
} // end unsigned int zm_packetqueue::clearQueue( unsigned int frames_to_keep, int stream_id )

void zm_packetqueue::clearQueue() {
  ZMPacket *packet = NULL;
	while(!pktQueue.empty()) {
    packet = pktQueue.front();
    pktQueue.pop_front();
    delete packet;
	}
}

unsigned int zm_packetqueue::size() {
  return pktQueue.size();
}


void zm_packetqueue::clear_unwanted_packets( timeval *recording_started, int mVideoStreamId ) {
  // Need to find the keyframe <= recording_started.  Can get rid of audio packets.
	if ( pktQueue.empty() ) {
		return;
	}

  // Step 1 - find keyframe < recording_started.
  // Step 2 - pop packets until we get to the packet in step 2
  list<ZMPacket *>::reverse_iterator it;

  for ( it = pktQueue.rbegin(); it != pktQueue.rend(); ++ it ) {
    ZMPacket *zm_packet = *it;
    AVPacket *av_packet = &(zm_packet->packet);
Debug(1, "Looking for keyframe after start" );
    if ( 
        ( av_packet->flags & AV_PKT_FLAG_KEY ) 
        && 
        ( av_packet->stream_index == mVideoStreamId )
        && 
        timercmp( &(zm_packet->timestamp), recording_started, < )
       ) {
Debug(1, "Found keyframe before start" );
      break;
    }
  }
  if ( it == pktQueue.rend() ) {
    Debug(1, "Didn't find a keyframe packet keeping all" );
    return;
  }

  ZMPacket *packet = NULL;
  while ( pktQueue.rend() != it ) {
    packet = pktQueue.front();
    pktQueue.pop_front();
    delete packet;
  }
}
