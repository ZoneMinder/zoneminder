//
// ZoneMinder Camera Class Implementation, $Date$, $Revision$
// Copyright (C) 2001-2008 Philip Coombes
//
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; either version 2
// of the License, or (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
//

#include "zm_camera.h"

#include "zm_monitor.h"

Camera::Camera(
  const Monitor *monitor,
  SourceType p_type,
  unsigned int p_width,
  unsigned int p_height,
  int p_colours,
  int p_subpixelorder,
  int p_brightness,
  int p_contrast,
  int p_hue,
  int p_colour,
  bool p_capture,
  bool p_record_audio
) :
  monitor(monitor),
  type(p_type),
  width(p_width),
  height(p_height),
  colours(p_colours),
  subpixelorder(p_subpixelorder),
  pixelFormat(zm_pixformat_from_colours(p_colours, p_subpixelorder)),
  brightness(p_brightness),
  hue(p_hue),
  colour(p_colour),
  contrast(p_contrast),
  capture(p_capture),
  record_audio(p_record_audio),
  mVideoStreamId(-1),
  mAudioStreamId(-1),
  mVideoCodecContext(nullptr),
  mAudioCodecContext(nullptr),
  mVideoStream(nullptr),
  mAudioStream(nullptr),
  mFormatContext(nullptr),
  mSecondFormatContext(nullptr),
  mFirstVideoPTS(0),
  mFirstAudioPTS(0),
  mLastVideoPTS(0),
  mLastAudioPTS(0),
  mLastVideoDTS(AV_NOPTS_VALUE),
  mLastAudioDTS(AV_NOPTS_VALUE),
  bytes(0),
  mIsPrimed(false) {
  // Camera::linesize/imagesize describe the *device-side* buffer that
  // capture paths copy from (V4L2 mmap buffers, raw RTP frames, etc).
  // Those buffers are tightly packed at the driver/source stride, not
  // 32-byte aligned, so use align=1 here. ZM's internal Image buffers
  // and SHM slots independently apply their own 32-byte alignment for
  // SIMD performance — that decoupling is the source of the size-mismatch
  // Fatal in LocalCamera::PrimeCapture when widths aren't 32-aligned.
  //
  // Guard against an unknown (colours, subpixelorder) pair that produced
  // pixelFormat == AV_PIX_FMT_NONE, or any case where av_image_get_*
  // returns a negative size — assigning those to unsigned would wrap to a
  // huge value and break SHM sizing and downstream allocations.
  pixels = width * height;
  if (pixelFormat == AV_PIX_FMT_NONE) {
    Error("Camera: unknown pixel format from colours=%u subpixelorder=%u; falling back to width*colours stride",
          p_colours, p_subpixelorder);
    linesize = width * colours;
    imagesize = static_cast<unsigned long long>(height) * linesize;
  } else {
    int raw_linesize = av_image_get_linesize(pixelFormat, width, 0);
    int raw_imagesize = av_image_get_buffer_size(pixelFormat, width, height, 1);
    if (raw_linesize < 0 || raw_imagesize < 0) {
      Error("Camera: av_image_get_* returned %d/%d for pixelFormat=%s; falling back",
            raw_linesize, raw_imagesize, zm_get_pix_fmt_name(pixelFormat));
      linesize = width * colours;
      imagesize = static_cast<unsigned long long>(height) * linesize;
    } else {
      linesize = static_cast<unsigned int>(raw_linesize);
      imagesize = static_cast<unsigned long long>(raw_imagesize);
    }
  }

  Debug(2, "New camera id: %d width: %d line size: %d height: %d colours: %d subpixelorder: %d capture: %d, size: %llu",
        monitor->Id(), width, linesize, height, colours, subpixelorder, capture, imagesize);
}

Camera::~Camera() {
  if ( mFormatContext ) {
    // Should also free streams
    Debug(1, "Freeing mFormatContext");
    //avformat_free_context(mFormatContext);
    avformat_close_input(&mFormatContext);
  }
  if ( mSecondFormatContext ) {
    // Should also free streams
    avformat_free_context(mSecondFormatContext);
  }
  mVideoStream = nullptr;
  mAudioStream = nullptr;
}

AVStream *Camera::getVideoStream() {
  if ( !mVideoStream ) {
    if ( !mFormatContext ) {
      mFormatContext = avformat_alloc_context();
      if ( !mFormatContext ) {
        Error("Failed to allocate AVFormatContext");
        return nullptr;
      }
    }
    Debug(1, "Allocating avstream");
    mVideoStream = avformat_new_stream(mFormatContext, nullptr);
    if ( mVideoStream ) {
      mVideoStream->time_base = (AVRational) {1, 1000000}; // microseconds as base frame rate
      mVideoStream->codecpar->width = width;
      mVideoStream->codecpar->height = height;
      mVideoStream->codecpar->format = pixelFormat;
      mVideoStream->codecpar->codec_type = AVMEDIA_TYPE_VIDEO;
      mVideoStream->codecpar->codec_id = AV_CODEC_ID_NONE;
      Debug(1, "Allocating avstream %p %p %d", mVideoStream, mVideoStream->codecpar, mVideoStream->codecpar->codec_id);
      mVideoStreamId = mVideoStream->index;
    } else {
      Error("Can't create video stream");
    }
  }
  return mVideoStream;
}
