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

#include "zm_ffmpeg.h"
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
  std::unique_lock<std::mutex> our_lck_;
  // The condition has to be in the packet because it is shared between locks
  std::condition_variable condition_;
  bool locked;

  int keyframe;
  AVStream  *stream;            // Input stream
  av_packet_ptr packet;         // Input packet, undecoded
  av_frame_ptr in_frame;        // Input image, decoded Theoretically only filled if needed.
  av_frame_ptr out_frame;       // output image, Only filled if needed.
  av_frame_ptr ai_frame;
  av_frame_ptr hw_frame;
  SystemTimePoint timestamp;
  Image     *image;
  Image     *y_image;
  Image     *analysis_image;
  Image     *ai_image;
  int       score;
  AVMediaType codec_type;
  int image_index;
  int codec_imgsize;
  int64_t   pts;                // pts in the packet can be in another time base. This MUST be in AV_TIME_BASE_Q
  bool decoded;
  std::vector<ZoneStats> zone_stats;
  std::string  alarm_cause;
  std::string detections;

 public:
  AVPacket *av_packet() { return packet.get(); }
  AVPacket *set_packet(AVPacket *p) ;
  AVFrame *av_frame() { return out_frame.get(); }
  Image *get_image(Image *i = nullptr);
  Image *set_image(Image *);
  ssize_t ram();

  int is_keyframe() { return keyframe; };
  int receive_frame(AVCodecContext *ctx);
  int decode(AVCodecContext *ctx, std::shared_ptr<ZMPacket>delayed_packet);
  int get_hwframe(AVCodecContext *ctx);
  explicit ZMPacket(Image *image, SystemTimePoint tv);
  explicit ZMPacket(ZMPacket &packet);
  ZMPacket();
  ~ZMPacket();

  //AVFrame *get_out_frame(const AVCodecContext *ctx);
  AVFrame *get_out_frame(int width, int height, AVPixelFormat format);
  AVFrame *get_ai_frame();
  Image *get_ai_image();
  void set_ai_frame(AVFrame *);
  int get_codec_imgsize() { return codec_imgsize; };
  void notify_all() {
    this->condition_.notify_all();
  }

  std::unique_lock<std::mutex> lock();
  void lock(std::unique_lock<std::mutex> &);

  bool trylock(std::unique_lock<std::mutex>  &lck_);
  void unlock(std::unique_lock<std::mutex>  &lck_);
  void unlock();
};

class ZMPacketLock {
  public:
    std::shared_ptr<ZMPacket> packet_;
  private:
    std::unique_lock<std::mutex> lck_;
    bool locked;

  public:
    //ZMPacketLock(ZMPacketLock &&in) : packet_(in.packet_), lck_(in.lck_), locked(in.locked) { in.lck_ = nullptr; };
    bool operator!() { return packet_ ? false : true; };

    ZMPacketLock(ZMPacketLock&& in) :
      packet_(in.packet_),
      lck_(std::move(in.lck_)),
      locked(in.locked)
    {
      in.locked = false;
    };
    ZMPacketLock& operator=(ZMPacketLock &&in) {
      packet_ = in.packet_;
      lck_    = std::move(in.lck_);
      //lck_    = in.lck_;
      locked  = in.locked;
      in.locked = false;
      return *this;
    };

    ZMPacketLock() :
      packet_(nullptr),
      locked(false) { Debug(1, "New empty"); };

    explicit ZMPacketLock(std::shared_ptr<ZMPacket> p) :
      packet_(p),
      lck_(p->mutex_, std::defer_lock),
      locked(false)
    {
    };

    ~ZMPacketLock() {
      if (locked) {
        Debug(3, "Unlocking in destructor packet %d %p locked: %d owns: %d", packet_->image_index, this, locked, lck_.owns_lock());
        packet_->unlock(lck_);
      }
    };

    void wait() {
      Debug(4, "packet %d waiting", packet_->image_index);
      packet_->condition_.wait(lck_);
    };
    void notify_all() { packet_->notify_all(); };
    void lock() { packet_->lock(lck_); locked = true; };
    void unlock() { packet_->unlock(lck_); locked = false; };
    bool trylock() { return locked = packet_->trylock(lck_); };
    bool is_locked() { 
      Debug(3, "is_locked packet %d %p locked: %d owns: %d", packet_->image_index, this, locked, lck_.owns_lock());
      return locked;
    };
};
#endif /* ZM_PACKET_H */
