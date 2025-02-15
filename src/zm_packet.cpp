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
#include "zm_signal.h"

using namespace std;
AVPixelFormat target_format = AV_PIX_FMT_NONE;

ZMPacket::ZMPacket() :
  //lck_(mutex_, std::defer_lock),
  locked(false),
  keyframe(0),
  stream(nullptr),
  //packet({}),
  /*
  in_frame({}),
  out_frame({}),
  ai_frame({}),
  hw_frame({}),
  */
  //timestamp({}),
  image(nullptr),
  y_image(nullptr),
  analysis_image(nullptr),
  ai_image(nullptr),
  score(-1),
  codec_type(AVMEDIA_TYPE_UNKNOWN),
  image_index(-1),
  codec_imgsize(0),
  pts(0),
  decoded(false) {
  packet = av_packet_ptr{av_packet_alloc()};
}

ZMPacket::ZMPacket(Image *i, SystemTimePoint tv) :
  //lck_(mutex_, std::defer_lock),
  locked(false),
  keyframe(0),
  stream(nullptr),
  /*
  in_frame({}),
  out_frame({}),
  ai_frame({}),
  hw_frame({}),
  */
  timestamp(tv),
  image(i),
  y_image(nullptr),
  analysis_image(nullptr),
  ai_image(nullptr),
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
  //lck_(mutex_, std::defer_lock),
  locked(false),
  keyframe(p.keyframe),
  stream(p.stream),
  timestamp(p.timestamp),
  image(p.image),
  y_image(p.y_image),
  analysis_image(p.analysis_image),
  ai_image(p.ai_image),
  score(p.score),
  codec_type(AVMEDIA_TYPE_UNKNOWN),
  image_index(p.image_index),
  codec_imgsize(0),
  pts(p.pts),
  decoded(p.decoded) {
  packet = av_packet_ptr{av_packet_alloc()};

  Error("Packet copy");
  if (zm_av_packet_ref(packet.get(), p.packet.get()) < 0) {
    Error("error refing packet");
  }
}

ZMPacket::~ZMPacket() {
  delete analysis_image;
  delete image;
  delete y_image;
  delete ai_image;
  // We don't want to av_free the hw_frame
  //hw_frame = nullptr;
}

ssize_t ZMPacket::ram() {
  return packet->size +
         (in_frame ? in_frame->linesize[0] * in_frame->height : 0) +
         (out_frame ? out_frame->linesize[0] * out_frame->height : 0) +
         (image ? image->Size() : 0) +
         (analysis_image ? analysis_image->Size() : 0);
}

int ZMPacket::receive_frame(AVCodecContext *ctx) {
  AVFrame *receive_frame = zm_av_frame_alloc();
  int ret = avcodec_receive_frame(ctx, receive_frame);
  Debug(1, "Ret from receive_frame ret: %d %s, packet %d", ret, av_make_error_string(ret).c_str(), image_index);
  if (ret == AVERROR(EAGAIN)) {
    av_frame_free(&receive_frame);
    return 0;
  } else if (ret < 0) {
    av_frame_free(&receive_frame);
    return ret;
  }

  in_frame = av_frame_ptr{receive_frame};
  zm_dump_video_frame(in_frame.get(), "got frame");

  get_hwframe(ctx);
  return 1;
}

int ZMPacket::send_packet(AVCodecContext *ctx) {
  // ret == 0 means EAGAIN
  // We only send a packet if we have a delayed_packet, otherwise packet is the delayed_packet
  int ret = avcodec_send_packet(ctx, packet.get());
  if (ret < 0) {
    Error("Unable to send packet %d %s, packet %d", ret, av_make_error_string(ret).c_str(), image_index);
    //return ret;
  } else {
    Debug(1, "Ret from send_packet %d %s, packet %d", ret, av_make_error_string(ret).c_str(), image_index);
  }

  if (ret == AVERROR(EAGAIN)) {
    return 0;
  } else if (ret < 0) {
    return ret;
  }
  return 1;
}

/* returns < 0 on error, 0 on not ready, int bytes consumed on success
 * This functions job is to populate in_frame with the image in an appropriate
 * format. It MAY also populate image if able to.  In this case in_frame is populated
 * by the image buffer.
 */
int ZMPacket::decode(AVCodecContext *ctx, std::shared_ptr<ZMPacket> delayed_packet) {
  Debug(4, "about to decode video, image_index is (%d)", image_index);

  int ret = send_packet(ctx);

  AVFrame *receive_frame = zm_av_frame_alloc();
  ret = avcodec_receive_frame(ctx, receive_frame);
  Debug(1, "Ret from receive_frame %d %s", ret, av_make_error_string(ret).c_str());
  if (ret == AVERROR(EAGAIN)) {
    av_frame_free(&receive_frame);
    return 0;
  } else if (ret < 0) {
    av_frame_free(&receive_frame);
    return ret;
  }

  delayed_packet->in_frame = av_frame_ptr{receive_frame};
  zm_dump_video_frame(delayed_packet->in_frame.get(), "got frame");
  //delayed_packet->get_hwframe(ctx);
  return 1;
}

bool ZMPacket::needs_hw_transfer(AVCodecContext *ctx) {
#if HAVE_LIBAVUTIL_HWCONTEXT_H
#if LIBAVCODEC_VERSION_CHECK(57, 89, 0, 89, 0)
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

int ZMPacket::get_hwframe(AVCodecContext *ctx) {
  if (hw_frame) {
    Error("Already have hw_frame in get_hwframe");
    return 0;
  }
#if HAVE_LIBAVUTIL_HWCONTEXT_H
#if LIBAVCODEC_VERSION_CHECK(57, 89, 0, 89, 0)

  if (needs_hw_transfer(ctx)) {
    Debug(3, "Have different format ctx->pix_fmt %d %s ?= ctx->sw_pix_fmt %d %s in_frame->format %d %s.",
        ctx->pix_fmt,
        av_get_pix_fmt_name(ctx->pix_fmt),
        ctx->sw_pix_fmt,
        av_get_pix_fmt_name(ctx->sw_pix_fmt),
        in_frame->format,
        av_get_pix_fmt_name(static_cast<AVPixelFormat>(in_frame->format))
        );

    av_frame_ptr new_frame{zm_av_frame_alloc()};
    /* retrieve data from GPU to CPU */
    hw_frame = std::move(in_frame);
    zm_dump_video_frame(hw_frame.get(), "Before hwtransfer");
    int ret = av_hwframe_transfer_data(new_frame.get(), hw_frame.get(), 0);
    if (ret < 0) {
      Error("Unable to transfer frame: %s, continuing", av_make_error_string(ret).c_str());
      return 0;
    }
    ret = av_frame_copy_props(new_frame.get(), hw_frame.get());
    if (ret < 0) {
      Error("Unable to copy props: %s, continuing", av_make_error_string(ret).c_str());
    }

    zm_dump_video_frame(new_frame.get(), "After hwtransfer");
    in_frame = std::move(new_frame);
  } else
#endif
#endif
    Debug(3, "Same pix format %s so not hwtransferring. sw_pix_fmt is %s",
        av_get_pix_fmt_name(ctx->pix_fmt),
        av_get_pix_fmt_name(ctx->sw_pix_fmt)
        );
  return 1;
} // end ZMPacket::get_hwframe

Image *ZMPacket::get_ai_image() {
  if (!ai_frame) {
    Error("Can't get image without ai frame.. maybe need to decode first");
    return nullptr;
  }
  if (!ai_image) {
    ai_image = new Image(ai_frame.get());
  }
  return ai_image;
}

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

AVPacket *ZMPacket::set_packet(AVPacket *p) {
  if (zm_av_packet_ref(packet.get(), p) < 0) {
    Error("error refing packet");
  }

  timestamp = std::chrono::system_clock::now();
  keyframe = p->flags & AV_PKT_FLAG_KEY;
  return packet.get();
}

void ZMPacket::set_ai_frame(AVFrame *frame) {
  ai_frame = av_frame_ptr{frame};
}

AVFrame *ZMPacket::get_ai_frame() {
  if (!ai_frame) {
    ai_frame = av_frame_ptr{zm_av_frame_alloc()};
    if (!ai_frame) {
      Error("Unable to allocate a frame");
      return nullptr;
    }
  }
  return ai_frame.get();
} // end AVFrame *ZMPacket::get_ai_frame( AVCodecContext *ctx );

AVFrame *ZMPacket::get_out_frame(int width, int height, AVPixelFormat format) {
  if (!out_frame) {
    out_frame = av_frame_ptr{zm_av_frame_alloc()};
    if (!out_frame) {
      Error("Unable to allocate a frame");
      return nullptr;
    }

    int alignment = 32;
    if (width % alignment) {
      Warning("Bad alignment for %d", width);
      alignment = 1;
    }

    codec_imgsize = av_image_get_buffer_size(
                      format, width, height, alignment);
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
  Debug(3, "packet %d unlocked, %p, locked %d, owns %d", image_index, this, locked, lck_.owns_lock());
  locked = false;
  lck_.unlock();
  Debug(3, "packet %d unlocked, %p, locked %d, owns %d", image_index, this, locked, lck_.owns_lock());
  condition_.notify_all();
};

void ZMPacket::unlock() {
  Debug(4, "packet %d unlocked, %p, locked %d, owns %d", image_index, this, locked, our_lck_.owns_lock());
  our_lck_.unlock();
};

