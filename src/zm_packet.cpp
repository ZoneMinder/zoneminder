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

ZMPacket::ZMPacket() :
  keyframe(0),
  in_frame(nullptr),
  out_frame(nullptr),
  timestamp(nullptr),
  buffer(nullptr),
  image(nullptr),
  analysis_image(nullptr),
  score(-1),
  codec_type(AVMEDIA_TYPE_UNKNOWN),
  image_index(-1),
  codec_imgsize(0)
{
  av_init_packet(&packet);
  packet.size = 0; // So we can detect whether it has been filled.
}

ZMPacket::ZMPacket(ZMPacket &p) :
  keyframe(0),
  in_frame(nullptr),
  out_frame(nullptr),
  timestamp(nullptr),
  buffer(nullptr),
  image(nullptr),
  analysis_image(nullptr),
  score(-1),
  codec_type(AVMEDIA_TYPE_UNKNOWN),
  image_index(-1),
  codec_imgsize(0)
{
  av_init_packet(&packet);
  if ( zm_av_packet_ref(&packet, &p.packet) < 0 ) {
    Error("error refing packet");
  }
  timestamp = new struct timeval;
  *timestamp = *p.timestamp;
}

ZMPacket::~ZMPacket() {
  zm_av_packet_unref(&packet);
  if ( in_frame ) {
    av_frame_free(&in_frame);
  }
  if ( out_frame ) {
    av_frame_free(&out_frame);
  }
  if ( buffer ) {
    av_freep(&buffer);
  }
  if ( analysis_image ) {
    delete analysis_image;
    analysis_image = nullptr;
  }
  if ( image and !image->IsBufferHeld() ) {
    delete image;
  }
  image = nullptr;
  if ( timestamp ) {
    delete timestamp;
    timestamp = nullptr;
  }
}

// deprecated
void ZMPacket::reset() {
  zm_av_packet_unref(&packet);
  if ( in_frame ) {
    av_frame_free(&in_frame);
  }
  if ( out_frame ) {
    av_frame_free(&out_frame);
  }
  if ( buffer ) {
    av_freep(&buffer);
  }
  if ( analysis_image ) {
    delete analysis_image;
    analysis_image = nullptr;
  }
#if 0
  if ( (! image) && timestamp ) {
    delete timestamp;
    timestamp = NULL;
  }
#endif
  score = -1;
  keyframe = 0;
}

int ZMPacket::decode(AVCodecContext *ctx) {
  Debug(4, "about to decode video, image_index is (%d)", image_index);

  if ( in_frame ) {
    Error("Already have a frame?");
  } else {
    in_frame = zm_av_frame_alloc();
  }

  int ret = zm_send_packet_receive_frame(ctx, in_frame, packet);
  if ( ret < 0 ) {
    if ( AVERROR(EAGAIN) != ret ) {
      Warning("Unable to receive frame : code %d %s.",
          ret, av_make_error_string(ret).c_str());
    }
    av_frame_free(&in_frame);
    return 0;
  }

#if HAVE_LIBAVUTIL_HWCONTEXT_H
#if LIBAVCODEC_VERSION_CHECK(57, 89, 0, 89, 0)
  if ( (ctx->pix_fmt != in_frame->format) ) {
    Debug(1, "Have different format.  Assuming hwaccel");
    AVFrame *new_frame = zm_av_frame_alloc();
    /* retrieve data from GPU to CPU */
    ret = av_hwframe_transfer_data(in_frame, new_frame, 0);
    if ( ret < 0 ) {
      Error("Unable to transfer frame: %s, continuing",
          av_make_error_string(ret).c_str());
      av_frame_free(&in_frame);
      av_frame_free(&new_frame);
      return 0;
    }
    zm_dump_video_frame(new_frame, "After hwtransfer");

    new_frame->pts = in_frame->pts;
    av_frame_free(&in_frame);
    in_frame = new_frame;
  }
#endif
#endif
  return 1;
} // end ZMPacket::decode

Image *ZMPacket::get_image(Image *i) {
  if ( !in_frame ) {
    Error("Can't get image without frame.. maybe need to decode first");
    return nullptr;
  }
  if ( !image ) {
    if ( !i ) {
      Error("Need a pre-allocated image buffer");
      return nullptr;
    } 
    image = i;
  }
  image->Assign(in_frame);
  return image;
}

Image *ZMPacket::set_image(Image *i) {
  image = i;
  return image;
}

AVPacket *ZMPacket::set_packet(AVPacket *p) {
  if ( zm_av_packet_ref(&packet, p) < 0 ) {
    Error("error refing packet");
  }
  //dumpPacket(&packet, "zmpacket:");
  gettimeofday(timestamp, nullptr);
  keyframe = p->flags & AV_PKT_FLAG_KEY;
  return &packet;
}

AVFrame *ZMPacket::get_out_frame( const AVCodecContext *ctx ) {
  if ( !out_frame ) {
    out_frame = zm_av_frame_alloc();
    if ( !out_frame ) {
      Error("Unable to allocate a frame");
      return nullptr;
    }

#if LIBAVUTIL_VERSION_CHECK(54, 6, 0, 6, 0)
    codec_imgsize = av_image_get_buffer_size(
        ctx->pix_fmt,
        ctx->width,
        ctx->height, 32);
    buffer = (uint8_t *)av_malloc(codec_imgsize);
    av_image_fill_arrays(
        out_frame->data,
        out_frame->linesize,
        buffer,
        ctx->pix_fmt,
        ctx->width,
        ctx->height,
        32);
#else
    codec_imgsize = avpicture_get_size(
        ctx->pix_fmt,
        ctx->width,
        ctx->height);
    buffer = (uint8_t *)av_malloc(codec_imgsize);
    avpicture_fill(
        (AVPicture *)out_frame,
        buffer,
        ctx->pix_fmt,
        ctx->width,
        ctx->height
        );
#endif
    out_frame->width = ctx->width;
    out_frame->height = ctx->height;
    out_frame->format = ctx->pix_fmt;
  }
  return out_frame;
} // end AVFrame *ZMPacket::get_out_frame( AVCodecContext *ctx );
