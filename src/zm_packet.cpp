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
#include "zm_image.h"
#include "zm_logger.h"
#include <sys/time.h>

using namespace std;
AVPixelFormat target_format = AV_PIX_FMT_NONE;

ZMPacket::ZMPacket() :
  keyframe(0),
  stream(nullptr),
  in_frame(nullptr),
  out_frame(nullptr),
  timestamp({}),
  buffer(nullptr),
  image(nullptr),
  analysis_image(nullptr),
  score(-1),
  codec_type(AVMEDIA_TYPE_UNKNOWN),
  image_index(-1),
  codec_imgsize(0),
  pts(0),
  decoded(false)
{
  packet = av_packet_ptr{av_packet_alloc()};
}

ZMPacket::ZMPacket(Image *i, const timeval &tv) :
  keyframe(0),
  stream(nullptr),
  in_frame(nullptr),
  out_frame(nullptr),
  timestamp(tv),
  buffer(nullptr),
  image(i),
  analysis_image(nullptr),
  score(-1),
  codec_type(AVMEDIA_TYPE_UNKNOWN),
  image_index(-1),
  codec_imgsize(0),
  pts(0),
  decoded(false)
{
  packet = av_packet_ptr{av_packet_alloc()};
}

ZMPacket::ZMPacket(ZMPacket &p) :
  keyframe(0),
  stream(nullptr),
  in_frame(nullptr),
  out_frame(nullptr),
  timestamp(p.timestamp),
  buffer(nullptr),
  image(nullptr),
  analysis_image(nullptr),
  score(-1),
  codec_type(AVMEDIA_TYPE_UNKNOWN),
  image_index(-1),
  codec_imgsize(0),
  pts(0),
  decoded(false)
{
  packet = av_packet_ptr{av_packet_alloc()};

  if (zm_av_packet_ref(packet.get(), p.packet.get()) < 0) {
    Error("error refing packet");
  }
}

ZMPacket::~ZMPacket() {
  if (in_frame) av_frame_free(&in_frame);
  if (out_frame) av_frame_free(&out_frame);
  if (buffer) av_freep(&buffer);
  delete analysis_image;
  delete image;
}

/* returns < 0 on error, 0 on not ready, int bytes consumed on success 
 * This functions job is to populate in_frame with the image in an appropriate
 * format. It MAY also populate image if able to.  In this case in_frame is populated
 * by the image buffer.  
 */
int ZMPacket::decode(AVCodecContext *ctx) {
  Debug(4, "about to decode video, image_index is (%d)", image_index);

  if (in_frame) {
    Error("Already have a frame?");
  } else {
    in_frame = zm_av_frame_alloc();
    if (!in_frame) {
      Error("Failed to allocate a frame!");
      return 0;
    }
  }

  // packets are always stored in AV_TIME_BASE_Q so need to convert to codec time base
  //av_packet_rescale_ts(&packet, AV_TIME_BASE_Q, ctx->time_base);

  int ret = zm_send_packet_receive_frame(ctx, in_frame, *packet);
  if (ret < 0) {
    if (AVERROR(EAGAIN) != ret) {
      Warning("Unable to receive frame : code %d %s.",
          ret, av_make_error_string(ret).c_str());
    }
    av_frame_free(&in_frame);
    return 0;
  }
  int bytes_consumed = ret;
  if (ret > 0) {
    zm_dump_video_frame(in_frame, "got frame");

#if HAVE_LIBAVUTIL_HWCONTEXT_H
#if LIBAVCODEC_VERSION_CHECK(57, 89, 0, 89, 0)

    if (fix_deprecated_pix_fmt(ctx->sw_pix_fmt) != fix_deprecated_pix_fmt(static_cast<AVPixelFormat>(in_frame->format))) {
      Debug(3, "Have different format ctx->pix_fmt %s ?= ctx->sw_pix_fmt %s in_frame->format %s.",
          av_get_pix_fmt_name(ctx->pix_fmt),
          av_get_pix_fmt_name(ctx->sw_pix_fmt),
          av_get_pix_fmt_name(static_cast<AVPixelFormat>(in_frame->format))
          );
#if 0
      if ( target_format == AV_PIX_FMT_NONE and ctx->hw_frames_ctx and (image->Colours() == 4) ) {
        // Look for rgb0 in list of supported formats
        enum AVPixelFormat *formats;
        if ( 0 <= av_hwframe_transfer_get_formats(
              ctx->hw_frames_ctx,
              AV_HWFRAME_TRANSFER_DIRECTION_FROM,
              &formats,
              0
              )	) {
          for (int i = 0; formats[i] != AV_PIX_FMT_NONE; i++) {
            Debug(1, "Available dest formats %d %s", 
                formats[i],
                av_get_pix_fmt_name(formats[i])
                );
            if ( formats[i] == AV_PIX_FMT_RGB0 ) {
              target_format = formats[i];
              break;
            }  // endif RGB0
          }  // end foreach support format
          av_freep(&formats);
        }  // endif success getting list of formats
      }  // end if target_format not set
#endif

      AVFrame *new_frame = zm_av_frame_alloc();
#if 0
      if ( target_format == AV_PIX_FMT_RGB0 ) {
        if ( image ) {
          if ( 0 > image->PopulateFrame(new_frame) ) {
            delete new_frame;
            new_frame = zm_av_frame_alloc();
            delete image;
            image = nullptr;
            new_frame->format = target_format;
          }
        }
      }
#endif
      /* retrieve data from GPU to CPU */
      zm_dump_video_frame(in_frame, "Before hwtransfer");
      ret = av_hwframe_transfer_data(new_frame, in_frame, 0);
      if (ret < 0) {
        Error("Unable to transfer frame: %s, continuing",
            av_make_error_string(ret).c_str());
        av_frame_free(&in_frame);
        av_frame_free(&new_frame);
        return 0;
      }
      ret = av_frame_copy_props(new_frame, in_frame);
      if (ret < 0) {
        Error("Unable to copy props: %s, continuing",
            av_make_error_string(ret).c_str());
      }

      zm_dump_video_frame(new_frame, "After hwtransfer");
#if 0
      if ( new_frame->format == AV_PIX_FMT_RGB0 ) {
        new_frame->format = AV_PIX_FMT_RGBA;
        zm_dump_video_frame(new_frame, "After hwtransfer setting to rgba");
      }
#endif
      av_frame_free(&in_frame);
      in_frame = new_frame;
    } else
#endif
#endif
      Debug(3, "Same pix format %s so not hwtransferring. sw_pix_fmt is %s",
          av_get_pix_fmt_name(ctx->pix_fmt),
          av_get_pix_fmt_name(ctx->sw_pix_fmt)
          );
#if 0
    if ( image ) {
      image->Assign(in_frame);
    }
#endif
  } // end if if ( ret > 0 ) {
  return bytes_consumed;
} // end ZMPacket::decode

Image *ZMPacket::get_image(Image *i) {
  if (!in_frame) {
    Error("Can't get image without frame.. maybe need to decode first");
    return nullptr;
  }
  if (!image) {
    if (!i) {
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
  if (zm_av_packet_ref(packet.get(), p) < 0) {
    Error("error refing packet");
  }
  //ZM_DUMP_PACKET(packet, "zmpacket:");
  gettimeofday(&timestamp, nullptr);
  keyframe = p->flags & AV_PKT_FLAG_KEY;
  return packet.get();
}

AVFrame *ZMPacket::get_out_frame(int width, int height, AVPixelFormat format) {
  if (!out_frame) {
    out_frame = zm_av_frame_alloc();
    if (!out_frame) {
      Error("Unable to allocate a frame");
      return nullptr;
    }

#if LIBAVUTIL_VERSION_CHECK(54, 6, 0, 6, 0)
    int alignment = 32;
    if (width%alignment) alignment = 1;
    
    codec_imgsize = av_image_get_buffer_size(
        format, width, height, alignment);
    Debug(1, "buffer size %u from %s %dx%d", codec_imgsize, av_get_pix_fmt_name(format), width, height);
    buffer = (uint8_t *)av_malloc(codec_imgsize);
    int ret;
    if ((ret=av_image_fill_arrays(
        out_frame->data,
        out_frame->linesize,
        buffer,
        format,
        width,
        height,
        alignment))<0) {
      Error("Failed to fill_arrays %s", av_make_error_string(ret).c_str());
      av_frame_free(&out_frame);
      return nullptr;
    }
#else
    codec_imgsize = avpicture_get_size(
        format,
        width,
        height);
    buffer = (uint8_t *)av_malloc(codec_imgsize);
    avpicture_fill(
        (AVPicture *)out_frame,
        buffer,
        format,
        width,
        height
        );
#endif
    out_frame->width = width;
    out_frame->height = height;
    out_frame->format = format;
  }
  return out_frame;
} // end AVFrame *ZMPacket::get_out_frame( AVCodecContext *ctx );
