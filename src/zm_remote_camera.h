//
// ZoneMinder Remote Camera Class Interface, $Date$, $Revision$
// Copyright (C) 2003, 2004, 2005, 2006  Philip Coombes
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

#include <netinet/in.h>

#include "zm_camera.h"
#include "zm_buffer.h"
#include "zm_regexp.h"

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
	Buffer buffer;
	enum { SINGLE_IMAGE, MULTI_IMAGE } mode;
	enum { UNDEF, JPEG, X_RGB, X_RGBZ } format;
	enum { HEADER, HEADERCONT, SUBHEADER, SUBHEADERCONT, CONTENT } state;

protected:
	static void Base64Encode( const char *in_string, char *out_string );
	inline static char *mempbrk( register const char *s, const char *accept, size_t limit )
	{
		if ( limit <= 0 || !s || !accept || !*accept )
			return( 0 );

		register int i,j;
		size_t acc_len = strlen( accept );

		for ( i = 0; i < limit; s++, i++ )
		{
			for ( j = 0; j < acc_len; j++ )
			{
				if ( *s == accept[j] )
				{
					return( (char *)s );
				}
			}
		}
		return( 0 );
	}
	inline static char *memstr( register const char *s, const char *n, size_t limit )
	{
		if ( limit <= 0 || !s || !n )
			return( 0 );

		if ( !*n )
			return( (char *)s );

		register int i,j,k;
		size_t n_len = strlen( n );

		for ( i = 0; i < limit; i++, s++ )
		{
			if ( *s != *n )
				continue;
			j = 1;
			k = 1;
			while ( true )
			{
				if ( k >= n_len )
					return( (char *)s );
				if ( s[j++] != n[k++] )
					break;
			}
		}
		return( 0 );
	}
	inline static size_t memspn( register const char *s, const char *accept, size_t limit )
	{
		if ( limit <= 0 || !s || !accept || !*accept )
			return( 0 );

		register int i,j;
		size_t acc_len = strlen( accept );

		for ( i = 0; i < limit; s++, i++ )
		{
			register bool found = false;
			for ( j = 0; j < acc_len; j++ )
			{
				if ( *s == accept[j] )
				{
					found = true;
					break;
				}
			}
			if ( !found )
			{
				return( i );
			}
		}
		return( limit );
	}
	inline static size_t memcspn( register const char *s, const char *reject, size_t limit )
	{
		if ( limit <= 0 || !s || !reject )
			return( 0 );

		if ( !*reject )
			return( limit );

		register int i,j;
		size_t rej_len = strlen( reject );

		for ( i = 0; i < limit; s++, i++ )
		{
			for ( j = 0; j < rej_len; j++ )
			{
				if ( *s == reject[j] )
				{
					return( i );
				}
			}
		}
		return( limit );
	}

public:
	RemoteCamera( const char *p_host, const char *p_port, const char *p_path, int p_width, int p_height, int p_palette, int p_brightness, int p_contrast, int p_hue, int p_colour, bool p_capture=true );
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
	int ReadData( Buffer &buffer, int bytes_expected=0 );
	int GetResponse();
	int PreCapture();
	int PostCapture( Image &image );
};

#endif // ZM_REMOTE_CAMERA_H
