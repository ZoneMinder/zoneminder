//
// ZoneMinder Remote Camera Class Implementation, $Date$, $Revision$
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

#include <stdio.h>
#include <stdlib.h>
#include <string.h>
#include <unistd.h>
#include <time.h>
#include <sys/time.h>
#include <syslog.h>
#include <signal.h>
#include <stdarg.h>
#include <errno.h>
#include <netdb.h>
#include <unistd.h>
#include <netinet/in.h>
#include <sys/types.h>
#include <sys/time.h>
#include <sys/socket.h>
#include <sys/ioctl.h>

#include "zm.h"
#include "zm_remote_camera.h"

RemoteCamera::RemoteCamera( const char *p_host, const char *p_port, const char *p_path, int p_width, int p_height, int p_palette, bool p_capture ) : Camera( REMOTE, p_width, p_height, p_palette, p_capture ), host( p_host ), port( p_port ), path( p_path )
{
	sd = -1;
	hp = 0;
	request[0] = '\0';
	timeout.tv_sec = 0;
	timeout.tv_usec = 0;

	if ( capture )
	{
		Initialise();
	}
}

RemoteCamera::~RemoteCamera()
{
	if ( capture )
	{
		Terminate();
	}
}

void RemoteCamera::Initialise()
{
	// Cache as much as we can to speed things up
	if ( !hp )
	{
		if ( !(hp = gethostbyname(host)) )
		{
			Error(( "Can't gethostbyname(%s): %s", host, strerror(h_errno) ));
			exit( -1 );
		}
		memcpy((char *)&sa.sin_addr, (char *)hp->h_addr, hp->h_length);
		sa.sin_family = hp->h_addrtype;
		sa.sin_port = htons(atoi(port));
	}

	if ( !request[0] )
	{
		if( !host )
		{
			Error(( "No host specified for remote get" ));
			exit( -1 );
		}

		if( !port )
		{
			Error(( "No port specified for remote get" ));
			exit( -1 );
		}

		if( !path )
		{
			Error(( "No path specified for remote get" ));
			exit( -1 );
		}

		sprintf( request, "GET %s HTTP/%s\n", path, (const char *)config.Item( ZM_HTTP_VERSION ) );
		sprintf( &(request[strlen(request)]), "Host: %s\n", host );
		sprintf( &(request[strlen(request)]), "User-Agent: %s/%s\n", (const char *)config.Item( ZM_HTTP_UA ), ZM_VERSION );
		sprintf( &(request[strlen(request)]), "Connection: Keep-Alive\n\n" );
		Debug( 2, ( "Request: %s", request ));
	}
	if ( !timeout.tv_sec )
	{
		timeout.tv_sec = (int)config.Item( ZM_HTTP_TIMEOUT )/1000; 
		timeout.tv_usec = (int)config.Item( ZM_HTTP_TIMEOUT )%1000;
	}
}

int RemoteCamera::Connect()
{
	if ( sd < 0 )
	{
		sd = socket(hp->h_addrtype, SOCK_STREAM, 0);
		if ( sd < 0 )
		{
			Error(( "Can't create socket: %s", strerror(errno) ));
			return( -1 );
		}

		if ( connect( sd, (struct sockaddr *)&sa, sizeof(sa) ) < 0 )
		{
			Error(( "Can't connect: %s", strerror(errno) ));
			return( -1 );
		}
	}
	Debug( 3, ( "Connected to host, socket = %d", sd ));
	return( sd );
}

int RemoteCamera::Disconnect()
{
	close( sd );
	sd = -1;
	return( 0 );
}

int RemoteCamera::SendRequest()
{
	if ( write( sd, request, strlen(request) ) < 0 )
	{
		Error(( "Can't write: %s", strerror(errno) ));
		Disconnect();
		return( -1 );
	}
	Debug( 3, ( "Request sent" ));
	return( 0 );
}

int RemoteCamera::GetHeader( const char *content, const char *header, char *value )
{
	//char *header_string = (char *)malloc( strlen(header)+8 );
	static char header_string[4096];
	strcpy( header_string, header );
	strcat( header_string, ":" );

	char *header_ptr = strstr( content, header_string );
	if ( !header_ptr )
	{
		return( -1 );
	}

	strcat( header_string, " %s" );
	int result = sscanf( header_ptr, header_string, value );
	//Debug( 3, ( "R:%d, %s\n", result, value );
	return( result );
}

int RemoteCamera::GetResponse( unsigned char *&buffer, int &max_size )
{
	fd_set rfds;
	FD_ZERO(&rfds);
	FD_SET(sd, &rfds);

	char *header = 0;
	int header_length = 0;
	unsigned char *content = buffer;
	int content_length = 0;

	char *content_ptr = 0;

	while( 1 )
	{
		struct timeval temp_timeout = timeout;

		int n_found = select( sd+1, &rfds, NULL, NULL, &temp_timeout );
		if( n_found == 0 )
		{
			Error(( "Select timed out" ));
			return( -1 );
		}
		else if ( n_found < 0)
		{
			Error(( "Select error: %s", strerror(errno) ));
			return( -1 );
		}

		int bytes_to_read = 0;
		if ( ioctl( sd, FIONREAD, &bytes_to_read ) < 0 )
		{
			Error(( "Can't ioctl(): %s", strerror(errno) ));
			return( -1 );
		}
		Debug( 3, ( "Expecting %d bytes", bytes_to_read ));

		if ( bytes_to_read == 0 )
		{
			Debug( 3, ( "Socket closed" ));
			Disconnect();
			break;
		}

		if ( !content_ptr )
		{
			if ( !header )
				header = (char *)malloc( bytes_to_read );
			else
				header = (char *)realloc( header, header_length+bytes_to_read );

			int n_bytes = read( sd, header+header_length, bytes_to_read );
			if ( n_bytes < 0)
			{
				Error(( "Read error: %s", strerror(errno) ));
				free( header );
				return( -1 );
			}
			else if ( n_bytes < bytes_to_read )
			{
				Error(( "Incomplete read, expected %d, got %d", bytes_to_read, n_bytes ));
				free( header );
				return( -1 );
			}
			Debug( 3, ( "Read %d bytes of header/content", bytes_to_read ));

			content_ptr = strstr( header, "\r\n\r\n" );
			if ( !content_ptr )
			{
				header_length += bytes_to_read;
			}
			else
			{
				*(content_ptr+2) = 0;
				content_ptr += 4;

				Debug( 2, ( "Header: %s", header ));

				char version[4];
				int code;
				char message[64] = "";
				int result = sscanf( header, "HTTP/%s %3d %[^\r\n]", version, &code, message );

				if ( result != 3 )
				{
					Error(( "Can't parse HTTP header" ));
					free( header );
					return( -1 );
				}

				//printf( "R:%d, %s - %d - %s\n", result, version, code, message );

				if ( code < 200 || code > 299 )
				{
					Error(( "Invalid response status %d: %s", code, message ));
					free( header );
					return( -1 );
				}

				int expected_content_length = -1;
				char header_string[32] = "";
				if ( GetHeader( header, "Content-Length", header_string ) > 0 )
				{
					expected_content_length = atoi( header_string );
				}

				int excess_length = content_ptr-(header+header_length);
				Debug( 3, ( "Excess length = %d", excess_length ));
				content_length = bytes_to_read-excess_length;
				Debug( 3, ( "Content length = %d", content_length ));

				if ( content_length > max_size )
				{
					if ( expected_content_length > max_size )
					{
						max_size = expected_content_length;
					}
					else
					{
						max_size = 0x10000;
					}
					content = buffer = (unsigned char *)malloc( max_size );
				}
				memcpy( content, content_ptr, content_length );
				content_ptr = (char *)(content + content_length);

				free( header );
			}
		}
		else
		{
			if ( (content_length+bytes_to_read) > max_size )
			{
				max_size += 0x10000;
				content = buffer = (unsigned char *)realloc( buffer, max_size );
				content_ptr = (char *)buffer+content_length;
			}
			int n_bytes = read( sd, content_ptr, bytes_to_read );
			if ( n_bytes < 0)
			{
				Error(( "Read error: %s", strerror(errno) ));
				return( -1 );
			}
			else if ( n_bytes < bytes_to_read )
			{
				Error(( "Incomplete read, expected %d, got %d", bytes_to_read, n_bytes ));
				return( -1 );
			}
			content_length += bytes_to_read;
			content_ptr += bytes_to_read;
			Debug( 3, ( "Read %d bytes of content, total = %d", bytes_to_read, content_length ));
		}
	}
	return( content_length );
}

int RemoteCamera::PreCapture()
{
	Connect();
	if ( sd < 0 )
	{
		return( -1 );
	}

	if ( SendRequest() < 0 )
	{
		Disconnect();
		return( -1 );
	}
	return( 0 );
}

int RemoteCamera::PostCapture( Image &image )
{
	int max_size = width*height*colours;
	unsigned char *buffer = (unsigned char *)malloc( max_size );
	int content_length = GetResponse( buffer, max_size );
	if ( content_length < 0 )
	{
		free( buffer );
		Disconnect();
		return( -1 );
	}

	image.DecodeJpeg( buffer, content_length );

	free( buffer );
	return( 0 );
}
