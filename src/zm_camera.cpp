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

#include "zm.h"
#include "zm_camera.h"

Camera::Camera(
    unsigned int p_monitor_id,
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
    monitor_id(p_monitor_id),
    monitor(nullptr),
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
    video_stream(nullptr),
    audio_stream(nullptr),
    mFormatContext(nullptr),
    bytes(0)
{
  linesize = width * colours;
  pixels = width * height;
  imagesize = height * linesize;

  Debug(2, "New camera id: %d width: %d line size: %d height: %d colours: %d subpixelorder: %d capture: %d",
      monitor_id, width, linesize, height, colours, subpixelorder, capture);
}

Camera::~Camera() {
  if ( mFormatContext ) {
    // Should also free streams
    avformat_free_context(mFormatContext);
    video_stream = nullptr;
    audio_stream = nullptr;
  }
}

Monitor *Camera::getMonitor() {
  if ( ! monitor )
    monitor = Monitor::Load(monitor_id, false, Monitor::QUERY);
  return monitor;
}

void Camera::setMonitor(Monitor *p_monitor) {
  monitor = p_monitor;
  monitor_id = monitor->Id();
}

AVStream *Camera::get_VideoStream() {
  if ( !video_stream ) {
    if ( !mFormatContext )
      mFormatContext = avformat_alloc_context();
    Debug(1, "Allocating avstream");
    video_stream = avformat_new_stream(mFormatContext, nullptr);
    if ( video_stream ) {
      video_stream->time_base = (AVRational){1, 1000000}; // microseconds as base frame rate
#if LIBAVCODEC_VERSION_CHECK(57, 64, 0, 64, 0)
      video_stream->codecpar->width = width;
      video_stream->codecpar->height = height;
      video_stream->codecpar->format = GetFFMPEGPixelFormat(colours, subpixelorder);
      video_stream->codecpar->codec_type = AVMEDIA_TYPE_VIDEO;
      video_stream->codecpar->codec_id = AV_CODEC_ID_NONE;
    Debug(1, "Allocating avstream %p %p %d", video_stream, video_stream->codecpar, video_stream->codecpar->codec_id);
#else
      video_stream->codec->width = width;
      video_stream->codec->height = height;
      video_stream->codec->pix_fmt = GetFFMPEGPixelFormat(colours, subpixelorder);
      video_stream->codec->codec_type = AVMEDIA_TYPE_VIDEO;
      video_stream->codec->codec_id = AV_CODEC_ID_NONE;
#endif
    } else {
      Error("Can't create video stream");
    }
  }
  return video_stream;
}
