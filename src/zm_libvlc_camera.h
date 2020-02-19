/*
 * ZoneMinder Libvlc Camera Class Interface, $Date$, $Revision$
 * Copyright (C) 2001-2008 Philip Coombes
 * 
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
*/  

#ifndef ZM_LIBVLC_CAMERA_H
#define ZM_LIBVLC_CAMERA_H

#include "zm_buffer.h"
#include "zm_camera.h"
#include "zm_thread.h"

#if HAVE_LIBVLC

#if HAVE_VLC_VLC_H
#include "vlc/vlc.h"
#endif

// Used by libvlc callbacks
struct LibvlcPrivateData
{
  uint8_t* buffer;
  uint8_t* prevBuffer;
  time_t prevTime;
  uint32_t bufferSize;
  Mutex mutex;
  ThreadData<bool> newImage;
};

class LibvlcCamera : public Camera {
  private:
  static void log_callback( void *ptr, int level, const libvlc_log_t *ctx, const char *format, va_list vargs );
protected:
  std::string mPath;
  std::string mMethod;
  std::string mOptions;
  char **mOptArgV;
  LibvlcPrivateData mLibvlcData;
  std::string mTargetChroma;
  uint8_t mBpp;

  libvlc_instance_t *mLibvlcInstance;
  libvlc_media_t *mLibvlcMedia;
  libvlc_media_player_t *mLibvlcMediaPlayer;

public:
  LibvlcCamera( int p_id, const std::string &path, const std::string &p_method, const std::string &p_options, int p_width, int p_height, int p_colours, int p_brightness, int p_contrast, int p_hue, int p_colour, bool p_capture, bool p_record_audio );
  ~LibvlcCamera();

  const std::string &Path() const { return mPath; }
  const std::string &Options() const { return mOptions; }
  const std::string &Method() const { return mMethod; }

  void Initialise();
  void Terminate();

  int PrimeCapture();
  int PreCapture();
  int Capture( Image &image );
  int CaptureAndRecord( Image &image, timeval recording, char* event_directory );
  int PostCapture();
  int Close() { return 0; };
};

#endif // HAVE_LIBVLC
#endif // ZM_LIBVLC_CAMERA_H
