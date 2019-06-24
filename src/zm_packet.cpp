//ZoneMinder Packet Implementation Class
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


#include "zm_packet.h"
#include "zm_ffmpeg.h"

#include <sys/time.h>

using namespace std;

ZMPacket::ZMPacket( AVPacket *p ) {
  frame = NULL;
  image = NULL;
  av_init_packet( &packet );
  if ( zm_av_packet_ref( &packet, p ) < 0 ) {
    Error("error refing packet");
	}
  gettimeofday( &timestamp, NULL );
}

ZMPacket::ZMPacket( AVPacket *p, struct timeval *t ) {
  frame = NULL;
  image = NULL;
  av_init_packet( &packet );
  if ( zm_av_packet_ref( &packet, p ) < 0 ) {
    Error("error refing packet");
	}
  timestamp = *t;
}

ZMPacket::~ZMPacket() {
  zm_av_packet_unref( &packet );
}

