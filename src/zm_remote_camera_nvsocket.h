//
// ZoneMinder Remote NVSOCKET Camera Class Interface, $Date$, $Revision$
// Copyright (C) 2017 ZoneMinder LLC
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

#ifndef ZM_REMOTE_CAMERA_NVSOCKET_H
#define ZM_REMOTE_CAMERA_NVSOCKET_H

#include "zm_buffer.h"
#include "zm_remote_camera.h"

class RemoteCameraNVSocket : public RemoteCamera {
 protected:
  std::string request;
  struct timeval timeout;
  int sd;
  Buffer buffer;

 public:
  RemoteCameraNVSocket(
    const Monitor *monitor,
    const std::string &host,
    const std::string &port,
    const std::string &path,
    int p_width,
    int p_height,
    int p_colours,
    int p_brightness,
    int p_contrast,
    int p_hue,
    int p_colour,
    bool p_capture,
    bool p_record_audio );
  ~RemoteCameraNVSocket();

  void Initialise() override;
  void Terminate() override { Disconnect(); }
  int Connect() override;
  int Disconnect() override;
  int SendRequest(const std::string &);
  int GetResponse();
  int PrimeCapture() override;
  int Capture(std::shared_ptr<ZMPacket> &p) override;
  int PostCapture() override;
  int Close() override { return 0; };
};

#endif // ZM_REMOTE_CAMERA_NVSOCKET_H
