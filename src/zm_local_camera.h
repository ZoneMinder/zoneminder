//
// ZoneMinder Local Camera Class Interface, $Date$, $Revision$
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

#ifndef ZM_LOCAL_CAMERA_H
#define ZM_LOCAL_CAMERA_H

#include "zm.h"
#include "zm_camera.h"
#include "zm_image.h"
#include "zm_packetqueue.h"

#if ZM_HAS_V4L

#ifdef HAVE_LINUX_VIDEODEV_H
#include <linux/videodev.h>
#endif // HAVE_LINUX_VIDEODEV_H
#ifdef HAVE_LIBV4L1_VIDEODEV_H
#include <libv4l1-videodev.h>
#endif // HAVE_LIB4VL1_VIDEODEV_H
#ifdef HAVE_LINUX_VIDEODEV2_H
#include <linux/videodev2.h>
#endif // HAVE_LINUX_VIDEODEV2_H

// Required on systems with v4l1 but without v4l2 headers
#ifndef VIDEO_MAX_FRAME
#define VIDEO_MAX_FRAME               32
#endif

#include "zm_ffmpeg.h"

//
// Class representing 'local' cameras, i.e. those which are
// directly connect to the host machine and which are accessed
// via a video interface.
//
class LocalCamera : public Camera
{
protected:
#if ZM_HAS_V4L2
    struct V4L2MappedBuffer
    {
        void    *start;
        size_t  length;
    };

    struct V4L2Data
    {
        v4l2_cropcap        cropcap;
        v4l2_crop           crop;
        v4l2_format         fmt;
        v4l2_requestbuffers reqbufs;
        V4L2MappedBuffer    *buffers;
        v4l2_buffer         *bufptr;
    };
#endif // ZM_HAS_V4L2

#if ZM_HAS_V4L1
    struct V4L1Data
    {
        int active_frame;
        video_mbuf frames;
        video_mmap *buffers;
        unsigned char *bufptr;
    };
#endif // ZM_HAS_V4L1

protected:
  std::string device;
  int channel;
  int standard;
  int palette;
  bool device_prime;
  bool channel_prime;
  int channel_index;
  unsigned int extras;
  
  unsigned int conversion_type; /* 0 = no conversion needed, 1 = use libswscale, 2 = zm internal conversion, 3 = jpeg decoding */
  convert_fptr_t conversion_fptr; /* Pointer to conversion function used */
  
  uint32_t AutoSelectFormat(int p_colours);

  static int camera_count;
  static int channel_count;
  static int channels[VIDEO_MAX_FRAME];
  static int standards[VIDEO_MAX_FRAME];
  static int vid_fd;
  static int v4l_version;
  bool  v4l_multi_buffer;
  unsigned int v4l_captures_per_frame;

#if ZM_HAS_V4L2
  static V4L2Data         v4l2_data;
#endif // ZM_HAS_V4L2
#if ZM_HAS_V4L1
  static V4L1Data         v4l1_data;
#endif // ZM_HAS_V4L1

#if HAVE_LIBSWSCALE
  static AVFrame      **capturePictures;
  _AVPIXELFORMAT         imagePixFormat;
  _AVPIXELFORMAT         capturePixFormat;
  struct SwsContext   *imgConversionContext;
  AVFrame             *tmpPicture;    
#endif // HAVE_LIBSWSCALE

  static LocalCamera      *last_camera;

public:
  LocalCamera(
    int p_id,
    const std::string &device,
    int p_channel,
    int p_format,
    bool v4lmultibuffer,
    unsigned int v4lcapturesperframe,
    const std::string &p_method,
    int p_width,
    int p_height,
    int p_colours,
    int p_palette,
    int p_brightness,
    int p_contrast,
    int p_hue,
    int p_colour,
    bool p_capture,
    bool p_record_audio,
    unsigned int p_extras = 0);
  ~LocalCamera();

  void Initialise();
  void Terminate();

  const std::string &Device() const { return( device ); }

  int Channel() const { return( channel ); }
  int Standard() const { return( standard ); }
  int Palette() const { return( palette ); }
  int Extras() const { return( extras ); }

  int Brightness( int p_brightness=-1 );
  int Hue( int p_hue=-1 );
  int Colour( int p_colour=-1 );
  int Contrast( int p_contrast=-1 );

  int PrimeCapture();
  int PreCapture();
  int Capture( Image &image );
  int PostCapture();
  int CaptureAndRecord( Image &image, timeval recording, char* event_directory ) {return(0);};
  int Close() { return 0; };

  static bool GetCurrentSettings( const char *device, char *output, int version, bool verbose );
};

#endif // ZM_HAS_V4L

#endif // ZM_LOCAL_CAMERA_H
