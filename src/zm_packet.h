//ZoneMinder Packet Wrapper Class
//Copyright 2017 ZoneMinder LLC
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


#ifndef ZM_PACKET_H
#define ZM_PACKET_H

#include "zm_logger.h"
#include "zm_time.h"
#include "zm_zone.h"

#include <condition_variable>
#include <mutex>
#include <vector>

extern "C" {
#include <libavformat/avformat.h>
}

class Image;

class ZMPacket {
  public:
  
    std::mutex  mutex_;
    // The condition has to be in the packet because it is shared between locks
    std::condition_variable condition_;

    int keyframe;
    AVStream  *stream;            // Input stream
    AVPacket  packet;             // Input packet, undecoded
    AVFrame   *in_frame;          // Input image, decoded Theoretically only filled if needed.
    AVFrame   *out_frame;         // output image, Only filled if needed.
    SystemTimePoint timestamp;
    uint8_t   *buffer;            // buffer used in image
    Image     *image;
    Image     *analysis_image;
    int       score;
    AVMediaType codec_type;
    int image_index;
    int codec_imgsize;
    int64_t   pts;                // pts in the packet can be in another time base. This MUST be in AV_TIME_BASE_Q
    bool decoded;
    std::vector<ZoneStats> zone_stats;
    std::string  alarm_cause;

  public:
    AVPacket *av_packet() { return &packet; }
    AVPacket *set_packet(AVPacket *p) ;
    AVFrame *av_frame() { return out_frame; }
    Image *get_image(Image *i=nullptr);
    Image *set_image(Image *);

    int is_keyframe() { return keyframe; };
    int decode(AVCodecContext *ctx);
    explicit ZMPacket(Image *image, SystemTimePoint tv);
    explicit ZMPacket(ZMPacket &packet);
    ZMPacket();
    ~ZMPacket();

    //AVFrame *get_out_frame(const AVCodecContext *ctx);
    AVFrame *get_out_frame(int width, int height, AVPixelFormat format);
    int get_codec_imgsize() { return codec_imgsize; };
};

class ZMLockedPacket {
  public:
    std::shared_ptr<ZMPacket> packet_;
    std::unique_lock<std::mutex> lck_;
    bool locked;

    explicit ZMLockedPacket(std::shared_ptr<ZMPacket> p) :
      packet_(p),
      lck_(packet_->mutex_, std::defer_lock),
      locked(false) {
    }

    ~ZMLockedPacket() {
      if (locked) unlock();
    }

    void lock() {
      Debug(4, "locking packet %d", packet_->image_index);
      lck_.lock();
      locked = true;
      Debug(4, "packet %d locked", packet_->image_index);
    };

    bool trylock() {
      Debug(4, "TryLocking packet %d", packet_->image_index);
      locked = lck_.try_lock();
      return locked;
    };

    void unlock() {
      Debug(4, "packet %d unlocked", packet_->image_index);
      locked = false;
      lck_.unlock();
      packet_->condition_.notify_all();
    };

    void wait() {
      Debug(4, "packet %d waiting", packet_->image_index);
      packet_->condition_.wait(lck_);
    }
};

#endif /* ZM_PACKET_H */
