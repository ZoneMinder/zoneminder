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

#include <sys/types.h>
#include <sys/ioctl.h>

#include "zm_image.h"

class Camera;

#include "zm_monitor.h"

//
// Abstract base class for cameras. This is intended just to express
// common attributes
//
class Camera {
protected:
  typedef enum { LOCAL_SRC, REMOTE_SRC, FILE_SRC, FFMPEG_SRC, LIBVLC_SRC, CURL_SRC, VNC_SRC } SourceType;

  unsigned int  monitor_id;
  Monitor *     monitor; // Null on instantiation, set as soon as possible.
  SourceType    type;
  unsigned int  width;
  unsigned int  height;
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
  unsigned int  bytes;

public:
  Camera( unsigned int p_monitor_id, SourceType p_type, unsigned int p_width, unsigned int p_height, int p_colours, int p_subpixelorder, int p_brightness, int p_contrast, int p_hue, int p_colour, bool p_capture, bool p_record_audio );
  virtual ~Camera();

  unsigned int getId() const { return monitor_id; }
  Monitor *getMonitor();
  void  setMonitor( Monitor *p_monitor );
  SourceType Type() const { return type; }
  bool IsLocal() const { return type == LOCAL_SRC; }
  bool IsRemote() const { return type == REMOTE_SRC; }
  bool IsFile() const { return type == FILE_SRC; }
  bool IsFfmpeg() const { return type == FFMPEG_SRC; }
  bool IsLibvlc() const { return type == LIBVLC_SRC; }
  bool IscURL() const { return type == CURL_SRC; }
  bool IsVNC() const { return type == VNC_SRC; }
  unsigned int Width() const { return width; }
  unsigned int Height() const { return height; }
  unsigned int Colours() const { return colours; }
  unsigned int SubpixelOrder() const { return subpixelorder; }
  unsigned int Pixels() const { return pixels; }
  unsigned long long ImageSize() const { return imagesize; }
  unsigned int Bytes() const { return bytes; };

  virtual int Brightness( int/*p_brightness*/=-1 ) { return -1; }
  virtual int Hue( int/*p_hue*/=-1 ) { return -1; }
  virtual int Colour( int/*p_colour*/=-1 ) { return -1; }
  virtual int Contrast( int/*p_contrast*/=-1 ) { return -1; }

  bool CanCapture() const { return capture; }

  bool SupportsNativeVideo() const {
    return (type == FFMPEG_SRC);
    //return (type == FFMPEG_SRC )||(type == REMOTE_SRC);
  }

  virtual int PrimeCapture() { return 0; }
  virtual int PreCapture() = 0;
  virtual int Capture(Image &image) = 0;
  virtual int PostCapture() = 0;
  virtual int CaptureAndRecord(Image &image, timeval recording, char* event_directory) = 0;
  virtual int Close() = 0;
};

#endif // ZM_CAMERA_H
