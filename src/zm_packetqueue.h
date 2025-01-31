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
#include <memory>

class ZMPacket;
class ZMLockedPacket;

typedef std::list<std::shared_ptr<ZMPacket>>::iterator packetqueue_iterator;

class PacketQueue {
 private: // For now just to ease development
  std::list<std::shared_ptr<ZMPacket>>    pktQueue;
  std::list<std::shared_ptr<ZMPacket>>::iterator analysis_it;

  int video_stream_id;
  int max_video_packet_count; // allow a negative value to someday mean unlimited
  // This is now a hard limit on the # of video packets to keep in the queue so that we can limit ram
  int pre_event_video_packet_count; // Was max_video_packet_count
  int max_stream_id;
  int *packet_counts;     /* packet count for each stream_id, to keep track of how many video vs audio packets are in the queue */
  bool deleting;
  bool keep_keyframes;
  std::list<packetqueue_iterator *> iterators;

  std::mutex mutex;
  std::condition_variable condition;
  int warned_count;
  bool has_out_of_order_packets_;
  int max_keyframe_interval_;

 public:
  PacketQueue();
  virtual ~PacketQueue();
  std::list<std::shared_ptr<ZMPacket>>::const_iterator end() const { return pktQueue.end(); }
  std::list<std::shared_ptr<ZMPacket>>::const_iterator begin() const { return pktQueue.begin(); }

  int addStream();
  void setMaxVideoPackets(int p);
  void setPreEventVideoPackets(int p);
  void setKeepKeyframes(bool k) { keep_keyframes = k; };

  bool queuePacket(std::shared_ptr<ZMPacket> packet);
  void stop();
  bool stopping() const { return deleting; };
  void clear();
  void dumpQueue();
  unsigned int size();
  unsigned int get_packet_count(int stream_id) const { return packet_counts[stream_id]; };
  bool has_out_of_order_packets() const { return has_out_of_order_packets_; };
  int get_max_keyframe_interval() const { return max_keyframe_interval_; };

  void clearPackets(const std::shared_ptr<ZMPacket> &packet);
  int packet_count(int stream_id);

  bool increment_it(packetqueue_iterator *it);
  bool increment_it(packetqueue_iterator *it, int stream_id);
  ZMLockedPacket *get_packet(packetqueue_iterator *);
  ZMLockedPacket *get_packet_no_wait(packetqueue_iterator *);
  ZMLockedPacket *get_packet_and_increment_it(packetqueue_iterator *);
  packetqueue_iterator *get_video_it(bool wait);
  packetqueue_iterator *get_stream_it(int stream_id);
  void free_it(packetqueue_iterator *);

  packetqueue_iterator *get_event_start_packet_it(
    packetqueue_iterator snapshot_it,
    unsigned int pre_event_count
  );
  bool is_there_an_iterator_pointing_to_packet(const std::shared_ptr<ZMPacket> zm_packet);
  void unlock(ZMLockedPacket *lp);
  void notify_all();
  void wait();
 private:
  packetqueue_iterator deletePacket(packetqueue_iterator it);
};

#endif /* ZM_PACKETQUEUE_H */
