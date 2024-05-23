//
// ZoneMinder Remote HTTP Camera Class Interface, $Date$, $Revision$
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

#ifndef ZM_REMOTE_CAMERA_HTTP_H
#define ZM_REMOTE_CAMERA_HTTP_H

#include "zm_buffer.h"
#include "zm_remote_camera.h"

//
// Class representing 'http' cameras, i.e. those which are
// accessed over a network connection using http
//
class RemoteCameraHttp : public RemoteCamera {
 protected:
  std::string request;
  struct timeval timeout;
  //struct hostent *hp;
  //struct sockaddr_in sa;
  int sd;
  Buffer buffer;
  enum { SINGLE_IMAGE, MULTI_IMAGE } mode;
  enum { UNDEF, JPEG, X_RGB, X_RGBZ } format;
  enum { HEADER, HEADERCONT, SUBHEADER, SUBHEADERCONT, CONTENT } state;
  enum { SIMPLE, REGEXP } method;

 public:
  RemoteCameraHttp(
    const Monitor *monitor,
    const std::string &method,
    const std::string &host,
    const std::string &port,
    const std::string &path,
    const std::string &p_user,
    const std::string &p_pass,
    int p_width,
    int p_height,
    int p_colours,
    int p_brightness,
    int p_contrast,
    int p_hue,
    int p_colour,
    bool p_capture,
    bool p_record_audio
  );
  ~RemoteCameraHttp();

  void Initialise() override;
  void Terminate() override { Disconnect(); }
  int Connect() override;
  int Disconnect() override;
  int SendRequest();
  int ReadData( Buffer &buffer, unsigned int bytes_expected=0 );
  int GetData();
  int GetResponse();
  int PrimeCapture() override;
  int PreCapture() override;
  int Capture(std::shared_ptr<ZMPacket> &p) override;
  int PostCapture() override;
  int Close() override { Disconnect(); return 0; };
};

#endif // ZM_REMOTE_CAMERA_HTTP_H
