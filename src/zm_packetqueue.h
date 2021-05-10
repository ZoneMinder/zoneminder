//ZoneMinder Packet Queue Interface Class
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


#ifndef ZM_PACKETQUEUE_H
#define ZM_PACKETQUEUE_H

//#include <boost/interprocess/managed_shared_memory.hpp>
//#include <boost/interprocess/containers/map.hpp>
//#include <boost/interprocess/allocators/allocator.hpp>
#include <list>
#include "zm_packet.h"

extern "C" {
#include <libavformat/avformat.h>
}
class zm_packetqueue {
public:
    zm_packetqueue(int max_stream_id);
    virtual ~zm_packetqueue();
    bool queuePacket(AVPacket* packet, struct timeval *timestamp);
    bool queuePacket(ZMPacket* packet);
    bool queuePacket(AVPacket* packet);
    ZMPacket * popPacket();
    bool popVideoPacket(ZMPacket* packet);
    bool popAudioPacket(ZMPacket* packet);
    unsigned int clearQueue(unsigned int video_frames_to_keep, int stream_id);
    unsigned int clearQueue(struct timeval *duration, int streamid);
    void clearQueue();
    void dumpQueue();
    unsigned int size();
    void clear_unwanted_packets(timeval *recording, int pre_event_count, int mVideoStreamId);
    int packet_count(int stream_id);
private:
    std::list<ZMPacket *>    pktQueue;
    int max_stream_id;
    int *packet_counts;     /* packet count for each stream_id, to keep track of how many video vs audio packets are in the queue */

};

#endif /* ZM_PACKETQUEUE_H */
