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

#include <condition_variable>
#include <list>
#include <mutex>

class ZMPacket;

typedef std::list<ZMPacket *>::iterator packetqueue_iterator;

class PacketQueue {
  public: // For now just to ease development
    std::list<ZMPacket *>    pktQueue;
    std::list<ZMPacket *>::iterator analysis_it;

    int video_stream_id;
    int max_video_packet_count; // allow a negative value to someday mean unlimited
    int max_stream_id;
    int *packet_counts;     /* packet count for each stream_id, to keep track of how many video vs audio packets are in the queue */
    bool deleting;
    std::list<packetqueue_iterator *> iterators;

    std::mutex mutex;
    std::condition_variable condition;

  public:
    PacketQueue();
    virtual ~PacketQueue();
    std::list<ZMPacket *>::const_iterator end() const { return pktQueue.end(); }
    std::list<ZMPacket *>::const_iterator begin() const { return pktQueue.begin(); }

    void addStreamId(int p_stream_id);
    void setMaxVideoPackets(int p) {
      max_video_packet_count = p;
      if ( max_video_packet_count < 1 )
        max_video_packet_count = 1 ;
      // We can simplify a lot of logic in queuePacket if we can assume at least 1 packet in queue
    }

    bool queuePacket(ZMPacket* packet);
    ZMPacket * popPacket();
    bool popVideoPacket(ZMPacket* packet);
    bool popAudioPacket(ZMPacket* packet);
    unsigned int clear(unsigned int video_frames_to_keep, int stream_id);
    unsigned int clear(struct timeval *duration, int streamid);
    void clear();
    void dumpQueue();
    unsigned int size();
    unsigned int get_packet_count(int stream_id) const { return packet_counts[stream_id]; };

    void clear_unwanted_packets(timeval *recording, int pre_event_count, int mVideoStreamId);
    int packet_count(int stream_id);

    bool increment_it(packetqueue_iterator *it);
    bool increment_it(packetqueue_iterator *it, int stream_id);
    ZMPacket *get_packet(packetqueue_iterator *);
    packetqueue_iterator *get_video_it(bool wait);
    packetqueue_iterator *get_stream_it(int stream_id);
    void free_it(packetqueue_iterator *);

    packetqueue_iterator *get_event_start_packet_it(
        packetqueue_iterator snapshot_it,
        unsigned int pre_event_count
    );
    bool is_there_an_iterator_pointing_to_packet(ZMPacket *zm_packet);
};

#endif /* ZM_PACKETQUEUE_H */
