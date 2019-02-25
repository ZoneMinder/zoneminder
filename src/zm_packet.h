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

extern "C" {
#include <libavformat/avformat.h>
}

#ifdef __FreeBSD__
#include <sys/time.h>
#endif // __FreeBSD__

#include "zm_image.h"
#include "zm_thread.h"

class ZMPacket {
  public:
  
    Mutex mutex;
    int keyframe;
    AVPacket  packet;   // Input packet, undecoded
    AVFrame   *in_frame;    // Input image, decoded Theoretically only filled if needed.
    AVFrame   *out_frame;    // Input image, decoded Theoretically only filled if needed.
    uint8_t   *buffer;
    Image     *image;            // Our internal image object representing this frame
    Image     *analysis_image;   // Our internal image object representing this frame
    int       score;
    struct timeval *timestamp;
    AVMediaType codec_type;
    int image_index;
    int codec_imgsize;

  public:
    AVPacket *av_packet() { return &packet; }
    AVPacket *set_packet( AVPacket *p ) ;
    AVFrame *av_frame() { return out_frame; }
    Image *get_image( Image *i=NULL );
    Image *set_image( Image * );

    int is_keyframe() { return keyframe; };
    int decode( AVCodecContext *ctx );
    void reset();
    explicit ZMPacket( Image *image );
    explicit ZMPacket( ZMPacket &packet );
    ZMPacket();
    ~ZMPacket();
    void lock() {
      Debug(2,"Locking packet %d", this->image_index);
      mutex.lock();
      Debug(2,"packet %d locked", this->image_index);
    };
    void unlock() { Debug(2,"packet %d unlocked", this->image_index);mutex.unlock(); };
    AVFrame *get_out_frame( const AVCodecContext *ctx );
    int get_codec_imgsize() { return codec_imgsize; };
};

#endif /* ZM_PACKET_H */
