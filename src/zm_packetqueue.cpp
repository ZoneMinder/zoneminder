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

bool zm_packetqueue::queuePacket( AVPacket* packet ) {
    
  //AVPacket *input_ref = (AVPacket *)av_malloc(sizeof(AVPacket));
  //if ( av_packet_ref( input_ref, packet ) < 0 ) {
    //free(input_ref);
		//return false;
	//}
	pktQueue.push( packet );

	return true;
}

AVPacket* zm_packetqueue::popPacket( ) {
	if ( pktQueue.empty() ) {
		return NULL;
	}

	AVPacket *packet = pktQueue.front();
	pktQueue.pop();

	return packet;
}

void zm_packetqueue::clearQueue() {
  AVPacket *packet = NULL;
	while(!pktQueue.empty()) {
  
    packet = pktQueue.front();
    pktQueue.pop();
    // If we clear it, then no freeing gets done, whereas when we pop off, we assume that the packet was freed somewhere else.
    zm_av_unref_packet( packet );
	}
}
