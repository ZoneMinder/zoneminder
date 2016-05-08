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

#include <boost/interprocess/managed_shared_memory.hpp>
#include <boost/interprocess/containers/map.hpp>
#include <boost/interprocess/allocators/allocator.hpp>
#include <queue>

extern "C" {
#include <libavformat/avformat.h>
}

typedef queue<AVPacket, deque<AVPacket, QueueShmemAllocator> > QueueType;

class zm_packetqueue {
public:
    zm_packetqueue();
    zm_packetqueue(const zm_packetqueue& orig);
    virtual ~zm_packetqueue();
    bool queuePacket(std::queue<AVPacket>& pktQueue, AVPacket* packet);
    bool queueVideoPacket(AVPacket* packet);
    bool queueAudioPacket(AVPacket* packet);
    bool popPacket(std::queue<AVPacket>& pktQueue, AVPacket* packet);
    bool popVideoPacket(AVPacket* packet);
    bool popAudioPacket(AVPacket* packet);
    void clearQueues();
    void clearQueue(std::queue<AVPacket>& pktQueue);
private:
    int                     MaxVideoQueueSize;
    int                     MaxAudioQueueSize;
    std::queue<AVPacket>    VideoQueue;
    std::queue<AVPacket>    AudioQueue;

};



#endif /* ZM_PACKETQUEUE_H */

