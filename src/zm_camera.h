//
// ZoneMinder Camera Class Interface, $Date$, $Revision$
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

#ifndef ZM_CAMERA_H
#define ZM_CAMERA_H

#include "zm_image.h"
#include <sys/ioctl.h>
#include <sys/types.h>

#include <memory>

class Monitor;
class ZMPacket;

//
// Abstract base class for cameras. This is intended just to express
// common attributes
//
class Camera {
protected:
  typedef enum { LOCAL_SRC, REMOTE_SRC, FILE_SRC, FFMPEG_SRC, LIBVLC_SRC, CURL_SRC, VNC_SRC } SourceType;

  const Monitor *monitor;
  SourceType    type;
  uint16_t  width;
  uint16_t  height;
  unsigned int  linesize;
  unsigned int  colours;
  unsigned int  subpixelorder;
  unsigned int  pixels;
  unsigned long long imagesize;
  int           brightness;
  int           hue;
  int           colour;
  int           contrast;
  bool          capture;
  bool          record_audio;
  int                 mVideoStreamId;
  int                 mAudioStreamId;
  AVCodecContext      *mVideoCodecContext;
  AVCodecContext      *mAudioCodecContext;
  AVStream *mVideoStream;
  AVStream *mAudioStream;
  AVFormatContext *mFormatContext; // One for video, one for audio
  AVFormatContext *mSecondFormatContext; // One for video, one for audio
  int64_t     mFirstVideoPTS;
  int64_t     mFirstAudioPTS;
  int64_t     mLastVideoPTS;
  int64_t     mLastAudioPTS;
  unsigned int  bytes;

public:
  Camera(
      const Monitor* monitor,
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
      );
  virtual ~Camera();

  SourceType Type() const { return type; }
  bool IsLocal() const { return type == LOCAL_SRC; }
  bool IsRemote() const { return type == REMOTE_SRC; }
  bool IsFile() const { return type == FILE_SRC; }
  bool IsFfmpeg() const { return type == FFMPEG_SRC; }
  bool IsLibvlc() const { return type == LIBVLC_SRC; }
  bool IscURL() const { return type == CURL_SRC; }
  bool IsVNC() const { return type == VNC_SRC; }
  unsigned int Width() const { return width; }
  unsigned int LineSize() const { return linesize; }
  unsigned int Height() const { return height; }
  unsigned int Colours() const { return colours; }
  unsigned int SubpixelOrder() const { return subpixelorder; }
  unsigned int Pixels() const { return pixels; }
  unsigned long long ImageSize() const { return imagesize; }
  unsigned int Bytes() const { return bytes; };
  int getFrequency() { return mAudioStream ? mAudioStream->codecpar->sample_rate : -1; }
  int getChannels() {
#if LIBAVUTIL_VERSION_CHECK(57, 28, 100, 28, 0)
    return mAudioStream ? mAudioStream->codecpar->ch_layout.nb_channels : -1; }
#else
    return mAudioStream ? mAudioStream->codecpar->channels : -1; }
#endif

  virtual int Brightness( int/*p_brightness*/=-1 ) { return -1; }
  virtual int Hue( int/*p_hue*/=-1 ) { return -1; }
  virtual int Colour( int/*p_colour*/=-1 ) { return -1; }
  virtual int Contrast( int/*p_contrast*/=-1 ) { return -1; }

  bool CanCapture() const { return capture; }

  bool SupportsNativeVideo() const {
    return (type == FFMPEG_SRC);
    //return (type == FFMPEG_SRC )||(type == REMOTE_SRC);
  }

  virtual AVStream      *getVideoStream();
  virtual AVStream      *getAudioStream() { return mAudioStream; };
  virtual AVCodecContext     *getVideoCodecContext() { return mVideoCodecContext; };
  virtual AVCodecContext     *getAudioCodecContext() { return mAudioCodecContext; };
  int            getVideoStreamId() { return mVideoStreamId; };
  int            getAudioStreamId() { return mAudioStreamId; };

  virtual int PrimeCapture() { return 0; }
  virtual int PreCapture() = 0;
  virtual int Capture(std::shared_ptr<ZMPacket> &p) = 0;
  virtual int PostCapture() = 0;
  virtual int Close() = 0;
};

#endif // ZM_CAMERA_H
