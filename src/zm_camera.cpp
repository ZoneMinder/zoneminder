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
  bytes(0),
  mIsPrimed(false) {
  linesize = width * colours;
  pixels = width * height;
  imagesize = static_cast<unsigned long long>(height) * linesize;

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
    if ( !mFormatContext )
      mFormatContext = avformat_alloc_context();
    Debug(1, "Allocating avstream");
    mVideoStream = avformat_new_stream(mFormatContext, nullptr);
    if ( mVideoStream ) {
      mVideoStream->time_base = (AVRational) {1, 1000000}; // microseconds as base frame rate
      mVideoStream->codecpar->width = width;
      mVideoStream->codecpar->height = height;
      mVideoStream->codecpar->format = GetFFMPEGPixelFormat(colours, subpixelorder);
      mVideoStream->codecpar->codec_type = AVMEDIA_TYPE_VIDEO;
      mVideoStream->codecpar->codec_id = AV_CODEC_ID_NONE;
      Debug(1, "Allocating avstream %p %p %d", mVideoStream, mVideoStream->codecpar, mVideoStream->codecpar->codec_id);
    } else {
      Error("Can't create video stream");
    }
    mVideoStreamId = mVideoStream->index;
  }
  return mVideoStream;
}
