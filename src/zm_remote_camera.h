//
// ZoneMinder Remote Camera Class Interface, $Date$, $Revision$
// Copyright (C) 2003  Philip Coombes
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
// Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
// 

#ifndef ZM_REMOTE_CAMERA_H
#define ZM_REMOTE_CAMERA_H

//#include <stdio.h>
//#include <stdlib.h>
//#include <string.h>
//#include <unistd.h>
//#include <time.h>
//#include <sys/time.h>
//#include <signal.h>
//#include <stdarg.h>
//#include <errno.h>
//#include <netdb.h>
//#include <unistd.h>
#include <netinet/in.h>
//#include <sys/types.h>
//#include <sys/time.h>
//#include <sys/socket.h>
//#include <sys/ioctl.h>

#include "zm_camera.h"

//
// Class representing 'remote' cameras, i.e. those which are
// accessed over a network connection.
//
class RemoteCamera : public Camera
{
protected:
	const char *host;
	const char *port;
	const char *path;
	const char *auth;
	char auth64[256];

protected:
	char request[1024];
	struct timeval timeout;
	struct hostent *hp;
	struct sockaddr_in sa;
	int sd;

protected:
	static void Base64Encode( const char *in_string, char *out_string );

public:
	RemoteCamera( const char *p_host, const char *p_port, const char *p_path, int p_width, int p_height, int p_palette, bool p_capture=true );
	~RemoteCamera();

	const char *Host() const { return( host ); }
	const char *Port() const { return( port ); }
	const char *Path() const { return( path ); }
	const char *Auth() const { return( auth ); }

	void Initialise();
	void Terminate() { Disconnect(); }
	int Connect();
	int Disconnect();
	int SendRequest();
	int GetHeader( const char *content, const char *header, char *value );
	int GetResponse( unsigned char *&buffer, int &max_size );
	int PreCapture();
	int PostCapture( Image &image );
};

#endif // ZM_REMOTE_CAMERA_H
