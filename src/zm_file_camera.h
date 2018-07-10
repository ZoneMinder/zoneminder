//
// ZoneMinder File Camera Class Interface, $Date$, $Revision$
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

#ifndef ZM_FILE_CAMERA_H
#define ZM_FILE_CAMERA_H

#include "zm_camera.h"
#include "zm_buffer.h"
#include "zm_regexp.h"
#include "zm_packetqueue.h"

#include <sys/param.h>

//
// Class representing 'file' cameras, i.e. those which are
// accessed using a single file which contains the latest jpeg data
//
class FileCamera : public Camera {
protected:
  char path[PATH_MAX];

public:
  FileCamera( int p_id, const char *p_path, int p_width, int p_height, int p_colours, int p_brightness, int p_contrast, int p_hue, int p_colour, bool p_capture, bool p_record_audio );
  ~FileCamera();

  const char *Path() const { return( path ); }

  void Initialise();
  void Terminate();
  int PreCapture();
  int Capture( Image &image );
  int PostCapture();
  int CaptureAndRecord( Image &image, timeval recording, char* event_directory ) {return(0);};
int Close() { return 0; };
};

#endif // ZM_FILE_CAMERA_H
