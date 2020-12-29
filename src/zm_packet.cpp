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

ZMPacket::ZMPacket() {
  keyframe = 0;
  // frame from decoded packet, to be used in generating image
  in_frame = nullptr;
  out_frame = nullptr;
  image = nullptr;
  buffer = nullptr;
  av_init_packet(&packet);
  packet.size = 0; // So we can detect whether it has been filled.
  timestamp = nullptr;
  analysis_image = nullptr;
  image_index = -1;
  score = -1;
  codec_imgsize = 0;
}

ZMPacket::ZMPacket(ZMPacket &p) {
  keyframe = 0;
  // frame from decoded packet, to be used in generating image
  in_frame = nullptr;
  out_frame = nullptr;
  image = nullptr;
  buffer = nullptr;
  av_init_packet(&packet);
  if ( zm_av_packet_ref(&packet, &p.packet) < 0 ) {
    Error("error refing packet");
  }
  timestamp = new struct timeval;
  *timestamp = *p.timestamp;
  analysis_image = nullptr;
  image_index = -1;
  score = -1;
}

ZMPacket::~ZMPacket() {
  zm_av_packet_unref(&packet);
  if ( in_frame ) {
    //av_free(frame->data);
    av_frame_free(&in_frame);
  }
  if ( out_frame ) {
    //av_free(frame->data);
    av_frame_free(&out_frame);
  }
  if ( buffer ) {
    av_freep(&buffer);
  }
  if ( analysis_image ) {
    delete analysis_image;
    analysis_image = nullptr;
  }
  // We assume the image was allocated elsewhere, so we just unref it.
  if ( image_index == -1 ) {
    delete image;
    delete timestamp;
  }
  image = nullptr;
  timestamp = nullptr;
}

void ZMPacket::reset() {
  //Debug(2,"reset");
  zm_av_packet_unref(&packet);
  if ( in_frame ) {
  //Debug(4,"reset frame");
    av_frame_free(&in_frame);
  }
  if ( out_frame ) {
  //Debug(4,"reset frame");
    av_frame_free(&out_frame);
  }
  if ( buffer ) {
  //Debug(4,"freeing buffer");
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

#if LIBAVCODEC_VERSION_CHECK(57, 64, 0, 64, 0)
  Debug(4, "send_packet");
  int ret = avcodec_send_packet(ctx, &packet);
  if ( ret < 0 ) {
    Error("Unable to send packet: %s", av_make_error_string(ret).c_str());
    av_frame_free(&in_frame);
    return 0;
  }

#if HAVE_AVUTIL_HWCONTEXT_H
  if ( hwaccel ) {
    ret = avcodec_receive_frame(ctx, hwFrame);
    if ( ret < 0 ) {
      Error("Unable to receive frame: %s", av_make_error_string(ret).c_str());
      av_frame_free(&in_frame);
      return 0;
    }
    ret = av_hwframe_transfer_data(frame, hwFrame, 0);
    if ( ret < 0 ) {
      Error("Unable to transfer frame: %s", av_make_error_string(ret).c_str());
      av_frame_free(&in_frame);
      return 0;
    }
  } else {
#endif
    Debug(4, "receive_frame");
    ret = avcodec_receive_frame(ctx, in_frame);
    if ( ret < 0 ) {
      Error("Unable to receive frame: %s", av_make_error_string(ret).c_str());
      av_frame_free(&in_frame);
      Error("Unable to receive frame: %s %p", av_make_error_string(ret).c_str(), in_frame);

      return 0;
    }

#if HAVE_AVUTIL_HWCONTEXT_H
  }
#endif

# else
  int frameComplete = 0;
  int ret = zm_avcodec_decode_video(ctx, in_frame, &frameComplete, &packet);
  if ( ret < 0 ) {
    Error("Unable to decode frame at frame %s", av_make_error_string(ret).c_str());
    av_frame_free(&in_frame);
    return 0;
  }
  if ( !frameComplete ) {
    Debug(1, "incomplete frame?");
    av_frame_free(&in_frame);
    return 0;
  }
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
