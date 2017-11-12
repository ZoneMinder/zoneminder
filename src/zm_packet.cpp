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

ZMPacket::ZMPacket( ) {
  keyframe = 0;
  image = NULL;
  frame = NULL;
  av_init_packet( &packet );
  packet.size = 0;
  gettimeofday( &timestamp, NULL );
}

ZMPacket::ZMPacket( Image *i ) {
  keyframe = 1;
  image = i;
  frame = NULL;
  av_init_packet( &packet );
  gettimeofday( &timestamp, NULL );
}

ZMPacket::ZMPacket( AVPacket *p ) {
  av_init_packet( &packet );
  set_packet( p );
  keyframe = p->flags & AV_PKT_FLAG_KEY;
}

ZMPacket::ZMPacket( AVPacket *p, struct timeval *t ) {
  av_init_packet( &packet );
  set_packet( p );
  timestamp = *t;
  keyframe = p->flags & AV_PKT_FLAG_KEY;
}
ZMPacket::ZMPacket( AVPacket *p, AVFrame *f, Image *i ) {
  av_init_packet( &packet );
  set_packet( p );
  image = i;
  frame = f;
}

ZMPacket::~ZMPacket() {
  zm_av_packet_unref( &packet );
  if ( frame ) {
    av_frame_free( &frame );
  }
  image = NULL;
  //if ( image ) {
    //delete image;
  //}
}

int ZMPacket::decode( AVCodecContext *ctx ) {
  Debug(4, "about to decode video" );

  if ( frame ) {
      Error("Already have a frame?");
  } else {
      frame = zm_av_frame_alloc();
  }

#if LIBAVCODEC_VERSION_CHECK(57, 64, 0, 64, 0)
  int ret = avcodec_send_packet( ctx, &packet );
  if ( ret < 0 ) {
    Error( "Unable to send packet: %s", av_make_error_string(ret).c_str() );
    av_frame_free( &frame );
    return 0;
  }

#if HAVE_AVUTIL_HWCONTEXT_H
  if ( hwaccel ) {
    ret = avcodec_receive_frame( ctx, hwFrame );
    if ( ret < 0 ) {
      Error( "Unable to receive frame: %s", av_make_error_string(ret).c_str() );
      av_frame_free( &frame );
      return 0;
    }
    ret = av_hwframe_transfer_data(frame, hwFrame, 0);
    if ( ret < 0 ) {
      Error( "Unable to transfer frame: %s", av_make_error_string(ret).c_str() );
      av_frame_free( &frame );
      return 0;
    }
  } else {
#endif
    ret = avcodec_receive_frame( ctx, frame );
    if ( ret < 0 ) {
      Error( "Unable to receive frame: %s", av_make_error_string(ret).c_str() );
      av_frame_free( &frame );
      return 0;
    }

#if HAVE_AVUTIL_HWCONTEXT_H
  }
#endif

# else
  int frameComplete = 0;
  int ret = zm_avcodec_decode_video( ctx, frame, &frameComplete, &packet );
  if ( ret < 0 ) {
    Error( "Unable to decode frame at frame %s", av_make_error_string(ret).c_str() );
    av_frame_free( &frame );
    return 0;
  }
  if ( ! frameComplete ) {
    Debug(1, "incomplete frame?");
    av_frame_free( &frame );
    return 0;
  }
#endif
  return 1;
} // end ZMPacket::decode

Image * ZMPacket::get_image( Image *i = NULL ) {
  if ( ! frame ) {
    Error("Can't get image without frame.. maybe need to decode first");
    return NULL;
  }
  if ( ! image ) {
    if ( ! i ) {
      Error("Need a pre-allocated image buffer");
      return NULL;
    } 
    image = i;
  }
  image->Assign( frame );
  return image;
}

Image *ZMPacket::set_image( Image *i ) {
  image = i;
  return image;
}

AVPacket *ZMPacket::set_packet( AVPacket *p ) {
  if ( zm_av_packet_ref( &packet, p ) < 0 ) {
    Error("error refing packet");
  }
  gettimeofday( &timestamp, NULL );
  keyframe = p->flags & AV_PKT_FLAG_KEY;
  return &packet;
}
