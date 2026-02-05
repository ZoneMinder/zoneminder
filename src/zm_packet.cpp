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

extern "C" {
#include <libavutil/pixdesc.h>
}

using namespace std;
AVPixelFormat target_format = AV_PIX_FMT_NONE;

ZMPacket::ZMPacket() :
  locked(false),
  keyframe(0),
  stream(nullptr),
  image(nullptr),
  y_image(nullptr),
  analysis_image(nullptr),
  score(-1),
  codec_type(AVMEDIA_TYPE_UNKNOWN),
  image_index(-1),
  codec_imgsize(0),
  pts(0),
  decoded(false),
  analyzed(false) {
  packet = av_packet_ptr{av_packet_alloc()};
}

ZMPacket::ZMPacket(Image *i, SystemTimePoint tv) :
  locked(false),
  keyframe(0),
  stream(nullptr),
  timestamp(tv),
  image(i),
  y_image(nullptr),
  analysis_image(nullptr),
  score(-1),
  codec_type(AVMEDIA_TYPE_UNKNOWN),
  image_index(-1),
  codec_imgsize(0),
  pts(0),
  decoded(false),
  analyzed(false) {
  packet = av_packet_ptr{av_packet_alloc()};
}

ZMPacket::ZMPacket(ZMPacket &p) :
  locked(false),
  keyframe(p.keyframe),
  stream(p.stream),
  timestamp(p.timestamp),
  image(p.image),
  y_image(p.y_image),
  analysis_image(p.analysis_image),
  score(p.score),
  codec_type(AVMEDIA_TYPE_UNKNOWN),
  image_index(p.image_index),
  codec_imgsize(0),
  pts(p.pts),
  decoded(p.decoded),
  analyzed(p.analyzed) {
  packet = av_packet_ptr{av_packet_alloc()};

  if (av_packet_ref(packet.get(), p.packet.get()) < 0) {
    Error("error refing packet");
  }
}

ZMPacket::~ZMPacket() {
  delete analysis_image;
  delete image;
  delete y_image;
}

ssize_t ZMPacket::ram() {
  return packet->size +
         (in_frame ? in_frame->linesize[0] * in_frame->height : 0) +
         (out_frame ? out_frame->linesize[0] * out_frame->height : 0) +
         (image ? image->Size() : 0) +
         (analysis_image ? analysis_image->Size() : 0);
}

int ZMPacket::send_packet(AVCodecContext *ctx) {
  // ret == 0 means EAGAIN
  int ret = avcodec_send_packet(ctx, packet.get());
  if (ret < 0) {
    if (ret == AVERROR(EAGAIN)) {
      Debug(1, "Unable to send packet %d %s, packet %d", ret, av_make_error_string(ret).c_str(), image_index);
      //ret = avcodec_send_packet(ctx, packet.get());
      return 0;
    } else {
      Error("Unable to send packet %d %s, packet %d", ret, av_make_error_string(ret).c_str(), image_index);
      return ret;
    }
  }
  Debug(3, "Ret from send_packet %d %s, packet %d", ret, av_make_error_string(ret).c_str(), image_index);
  return 1;
}

/* Returns < 0 on error, 0 for go again, > 0 for success */
int ZMPacket::receive_frame(AVCodecContext *ctx) {
  av_frame_ptr receive_frame{av_frame_alloc()};
  if (!receive_frame) {
    Error("Error allocating frame");
    return -1;
  }
  int ret = avcodec_receive_frame(ctx, receive_frame.get());
  if (ret < 0) {
    if (ret == AVERROR(EAGAIN)) {
      Debug(1, "Ret from receive_frame ret: %d %s, packet %d", ret, av_make_error_string(ret).c_str(), image_index);
      return 0;
    } else {
      Error("Ret from receive_frame ret: %d %s, packet %d", ret, av_make_error_string(ret).c_str(), image_index);
      return ret;
    }
  }

  // For hardware frames, do the transfer immediately while the context is valid
  // The nvidia-vaapi-driver can have issues if there's a delay between decode and transfer
#if HAVE_LIBAVUTIL_HWCONTEXT_H
#if LIBAVCODEC_VERSION_CHECK(57, 89, 0, 89, 0)
  if (receive_frame->hw_frames_ctx) {
    Debug(2, "Hardware frame received, transferring immediately");
    av_frame_ptr sw_frame{av_frame_alloc()};
    ret = av_hwframe_transfer_data(sw_frame.get(), receive_frame.get(), 0);
    if (ret < 0) {
      Error("Immediate hw transfer failed: %s, packet %d", av_make_error_string(ret).c_str(), image_index);
      return ret;
    }
    ret = av_frame_copy_props(sw_frame.get(), receive_frame.get());
    if (ret < 0) {
      Warning("Failed to copy frame props: %s", av_make_error_string(ret).c_str());
    }
    // Release GPU surface immediately - we have the software frame now
    // Keeping hw_frame would hold GPU memory and exhaust the surface pool
    // receive_frame goes out of scope here and releases the surface
    in_frame = std::move(sw_frame);
    zm_dump_video_frame(in_frame.get(), "After immediate hwtransfer");
  } else {
    in_frame = std::move(receive_frame);
  }
#else
  in_frame = std::move(receive_frame);
#endif
#else
  in_frame = std::move(receive_frame);
#endif

  return 1;
}  // end int ZMPacket::receive_frame(AVCodecContext *ctx)

bool ZMPacket::needs_hw_transfer(AVCodecContext *ctx) {
  if (!(ctx && in_frame.get())) {
    Error("No ctx %p or in_frame %p", ctx, in_frame.get());
    return false;
  }
#if HAVE_LIBAVUTIL_HWCONTEXT_H
#if LIBAVCODEC_VERSION_CHECK(57, 89, 0, 89, 0)
  // If frame has no hw_frames_ctx, it's already a software frame
  if (!in_frame->hw_frames_ctx) {
    return false;
  }
  if (
      (ctx->sw_pix_fmt != AV_PIX_FMT_NONE)
      and
      (fix_deprecated_pix_fmt(ctx->sw_pix_fmt) != fix_deprecated_pix_fmt(static_cast<AVPixelFormat>(in_frame->format)))
     ) {
    return true;
  }
#endif
#endif
  return false;
}

int ZMPacket::transfer_hwframe(AVCodecContext *ctx) {
  if (hw_frame) {
    // Already transferred in receive_frame
    Debug(2, "Hardware frame already transferred");
    return 1;
  }
#if HAVE_LIBAVUTIL_HWCONTEXT_H
#if LIBAVCODEC_VERSION_CHECK(57, 89, 0, 89, 0)

  if (needs_hw_transfer(ctx)) {
    Debug(4, "Have different format ctx->pix_fmt %d %s ?= ctx->sw_pix_fmt %d %s in_frame->format %d %s.",
        ctx->pix_fmt,
        av_get_pix_fmt_name(ctx->pix_fmt),
        ctx->sw_pix_fmt,
        av_get_pix_fmt_name(ctx->sw_pix_fmt),
        in_frame->format,
        av_get_pix_fmt_name(static_cast<AVPixelFormat>(in_frame->format))
        );

    // Frame gets moved no matter what
    hw_frame = std::move(in_frame);
    zm_dump_video_frame(hw_frame.get(), "Before hwtransfer");

    // Verify hw_frames_ctx is valid before attempting transfer
    if (!hw_frame->hw_frames_ctx) {
      Error("Hardware frame has no hw_frames_ctx, cannot transfer");
      hw_frame = nullptr;
      in_frame = nullptr;
      return -1;
    }

    av_frame_ptr new_frame{av_frame_alloc()};
    // Don't set format - let FFmpeg use the hw_frames_ctx default format
    // (nvidia-vaapi-driver only supports nv12/p010 transfer, not yuvj420p)
    // The later swscale conversion will handle format conversion

    /* retrieve data from GPU to CPU */
    int ret = av_hwframe_transfer_data(new_frame.get(), hw_frame.get(), 0);
    if (ret < 0) {
      Error("Unable to transfer frame: %s", av_make_error_string(ret).c_str());
      hw_frame = nullptr;
      in_frame = nullptr;
      return ret;
    }

    ret = av_frame_copy_props(new_frame.get(), hw_frame.get());
    if (ret < 0) {
      Error("Unable to copy props: %s, continuing", av_make_error_string(ret).c_str());
    }

    in_frame = std::move(new_frame);
    zm_dump_video_frame(in_frame.get(), "After hwtransfer");
  } else
    Debug(3, "Same pix format %s so not hwtransferring. sw_pix_fmt is %s",
        av_get_pix_fmt_name(ctx->pix_fmt),
        av_get_pix_fmt_name(ctx->sw_pix_fmt)
        );
#endif
#endif
  return 1;
} // end ZMPacket::transfer_hwframe

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
  image->Assign(in_frame.get());
  return image;
}

Image *ZMPacket::set_image(Image *i) {
  image = i;
  return image;
}

Image *ZMPacket::get_y_image() {
  if (!y_image) {
    if (!in_frame) {
      Error("Can't get y_image without frame, maybe need to decode first");
      return nullptr;
    }

    // Check if the pixel format has a Y channel accessible in data[0]
    // This requires a planar YUV format (not RGB, not packed YUV)
    const AVPixFmtDescriptor *desc = av_pix_fmt_desc_get(static_cast<AVPixelFormat>(in_frame->format));
    if (!desc) {
      Error("Unable to get pixel format descriptor for format %d", in_frame->format);
      return nullptr;
    }

    // Must not be RGB (no Y channel) and must be planar (Y is in data[0])
    if (desc->flags & AV_PIX_FMT_FLAG_RGB) {
      Error("Cannot get Y image from RGB format %s", desc->name);
      return nullptr;
    }
    if (!(desc->flags & AV_PIX_FMT_FLAG_PLANAR)) {
      Error("Cannot get Y image from non-planar format %s (Y is interleaved)", desc->name);
      return nullptr;
    }

    y_image = new Image(in_frame->width, in_frame->height, 1, ZM_SUBPIX_ORDER_NONE, in_frame->data[0], 0, 0);
  }
  return y_image;
}

AVPacket *ZMPacket::set_packet(AVPacket *p) {
  if (zm_av_packet_ref(packet.get(), p) < 0) {
    Error("error refing packet");
  }

  timestamp = std::chrono::system_clock::now();
  keyframe = p->flags & AV_PKT_FLAG_KEY;
  return packet.get();
}

AVFrame *ZMPacket::get_out_frame(int width, int height, AVPixelFormat format) {
  if (!out_frame) {
    out_frame = av_frame_ptr{av_frame_alloc()};
    if (!out_frame) {
      Error("Unable to allocate a frame");
      return nullptr;
    }

    int alignment = 32;
    if (width%alignment) alignment = 1;

    codec_imgsize = av_image_get_buffer_size(format, width, height, alignment);
    Debug(1, "buffer size %u from %s %dx%d", codec_imgsize, av_get_pix_fmt_name(format), width, height);
    out_frame->buf[0] = av_buffer_alloc(codec_imgsize);
    if (!out_frame->buf[0]) {
      Error("Unable to allocate a frame buffer");
      out_frame = nullptr;
      return nullptr;
    }
    int ret;
    if ((ret=av_image_fill_arrays(
               out_frame->data,
               out_frame->linesize,
               out_frame->buf[0]->data,
               format,
               width,
               height,
               alignment))<0) {
      Error("Failed to fill_arrays %s", av_make_error_string(ret).c_str());
      out_frame = nullptr;
      return nullptr;
    }

    out_frame->width = width;
    out_frame->height = height;
    out_frame->format = format;
  }
  return out_frame.get();
} // end AVFrame *ZMPacket::get_out_frame( AVCodecContext *ctx );

std::unique_lock<std::mutex> ZMPacket::lock() {
  std::unique_lock<std::mutex> lck_(mutex_, std::defer_lock);
  Debug(4, "locking packet %d %p %d owns %d", image_index, this, locked, lck_.owns_lock());
  lck_.lock();
  locked = true;
  Debug(4, "packet %d locked", image_index);
  return lck_;
};

void ZMPacket::lock(std::unique_lock<std::mutex> &lck_) {
  Debug(4, "locking packet %d %p %d owns %d", image_index, this, locked, lck_.owns_lock());
  lck_.lock();
  locked = true;
  Debug(4, "packet %d locked", image_index);
};

bool ZMPacket::trylock(std::unique_lock<std::mutex> &lck_) {
  Debug(4, "TryLocking packet %d %p locked: %d owns: %d", image_index, this, locked, lck_.owns_lock());
  locked = lck_.try_lock();
  Debug(4, "TryLocking packet %d %p %d, owns: %d", image_index, this, locked, lck_.owns_lock());
  return locked;
};

void ZMPacket::unlock(std::unique_lock<std::mutex> &lck_) {
  Debug(4, "packet %d unlocked, %p, locked %d, owns %d", image_index, this, locked, lck_.owns_lock());
  locked = false;
  lck_.unlock();
  Debug(4, "packet %d unlocked, %p, locked %d, owns %d", image_index, this, locked, lck_.owns_lock());
  condition_.notify_all();
};

void ZMPacket::unlock() {
  if (locked) {
    Debug(4, "packet %d unlocked, %p, locked %d, owns %d", image_index, this, locked, our_lck_.owns_lock());
    our_lck_.unlock();
  } else {
    Error("Attempt to unlock already unlocked packet %d unlocked, %p, locked %d, owns %d", image_index, this, locked, our_lck_.owns_lock());
  }
}

