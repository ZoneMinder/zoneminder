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

class ZMPacket {
  public:
  
    AVPacket  packet;   // Input packet, undecoded
    AVFrame   *frame;    // Input image, decoded
    Image     *image;   // Our internal image oject representing this frame
    struct timeval timestamp;
  public:
    AVPacket *av_packet() { return &packet; }
    ZMPacket( AVPacket *packet, struct timeval *timestamp );
    explicit ZMPacket( AVPacket *packet );
    ~ZMPacket();
};

#endif /* ZM_PACKET_H */
