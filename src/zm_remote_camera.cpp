//
// ZoneMinder Remote Camera Class Implementation, $Date$, $Revision$
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

#include "zm_remote_camera.h"

#include "zm_utils.h"
#include <arpa/inet.h>
#include <netdb.h>
#include <sys/socket.h>

RemoteCamera::RemoteCamera(
  const Monitor *monitor,
  const std::string &p_protocol,
  const std::string &p_host,
  const std::string &p_port,
  const std::string &p_path,
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
) :
  Camera(monitor, REMOTE_SRC, p_width, p_height, p_colours, ZM_SUBPIX_ORDER_DEFAULT_FOR_COLOUR(p_colours), p_brightness, p_contrast, p_hue, p_colour, p_capture, p_record_audio),
  protocol(p_protocol),
  host(p_host),
  port(p_port),
  path(p_path),
  username(p_user),
  password(p_pass),
  hp(nullptr),
  mNeedAuth(false),
  mAuthenticator(nullptr) {
  if (path[0] != '/')
    path = '/'+path;
}

RemoteCamera::~RemoteCamera() {
  if (hp != nullptr) {
    freeaddrinfo(hp);
    hp = nullptr;
  }
  delete mAuthenticator;
}

void RemoteCamera::Initialise() {
  if (protocol.empty())
    Fatal("No protocol specified for remote camera");

  if (host.empty())
    Fatal("No host specified for remote camera");

  if (port.empty())
    Fatal("No port specified for remote camera");


  // Cache as much as we can to speed things up
  std::string::size_type authIndex = host.rfind('@');

  if (authIndex != std::string::npos) {
    auth = host.substr(0, authIndex);
    host.erase(0, authIndex+1);
    auth64 = Base64Encode(auth);

    authIndex = auth.rfind(':');
    username = auth.substr(0,authIndex);
    password = auth.substr(authIndex+1, auth.length());
  } else if (!username.empty()) {
    auth = username;
    if(!password.empty()) auth += ":"+password;
    auth64 = Base64Encode(auth);
  }

  mNeedAuth = false;
  mAuthenticator = new zm::Authenticator(username, password);

  struct addrinfo hints;
  memset(&hints, 0, sizeof(hints));
  hints.ai_family = AF_UNSPEC;
  hints.ai_socktype = SOCK_STREAM;

  int ret = getaddrinfo(host.c_str(), port.c_str(), &hints, &hp);
  if (ret != 0) {
    Error("Can't getaddrinfo(%s port %s): %s", host.c_str(), port.c_str(), gai_strerror(ret));
    return;
  }
  struct addrinfo *p = nullptr;
  int addr_count = 0;
  for (p = hp; p != nullptr; p = p->ai_next) {
    addr_count++;
  }
  Debug(1, "%d addresses returned", addr_count);
}

int RemoteCamera::Read( int fd, char *buf, int size ) {
  int ReceivedBytes = 0;
  while ( ReceivedBytes < size ) {
    // recv blocks until we get data, but it may be of ARBITRARY LENGTH and INCOMPLETE
    int bytes_to_recv = size - ReceivedBytes;
    if ( SOCKET_BUF_SIZE < bytes_to_recv )
      bytes_to_recv = SOCKET_BUF_SIZE;
//Debug(3, "Aiming to receive %d of %d bytes", bytes_to_recv, size );
    int bytes = recv(fd, &buf[ReceivedBytes], bytes_to_recv, 0); //socket, buffer, len, flags
    if ( bytes <= 0 ) {
      Error("RemoteCamera::Read Recv error. Closing Socket\n");
      return -1;
    }
    ReceivedBytes += bytes;
  }
  return ReceivedBytes;
}
