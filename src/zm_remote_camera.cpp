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
	auth = 0;
	auth64[0] = '\0';

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

	// Cache as much as we can to speed things up
	char *auth_ptr = strchr( host, '@' );

	if ( auth_ptr )
	{
		auth = host;
		host = auth_ptr+1;
		*auth_ptr = '\0';
		Base64Encode( auth, auth64 );
	}

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
		sprintf( request, "GET %s HTTP/%s\n", path, (const char *)config.Item( ZM_HTTP_VERSION ) );
		sprintf( &(request[strlen(request)]), "User-Agent: %s/%s\n", (const char *)config.Item( ZM_HTTP_UA ), ZM_VERSION );
		sprintf( &(request[strlen(request)]), "Host: %s\n", host );
		sprintf( &(request[strlen(request)]), "Connection: Keep-Alive\n" );
		if ( auth )
		{
			sprintf( &(request[strlen(request)]), "Authorization: Basic %s\n", auth64 );
		}
		sprintf( &(request[strlen(request)]), "\n" );
		Debug( 2, ( "Request: %s", request ));
	}
	if ( !timeout.tv_sec )
	{
		timeout.tv_sec = (int)config.Item( ZM_HTTP_TIMEOUT )/1000; 
		timeout.tv_usec = (int)config.Item( ZM_HTTP_TIMEOUT )%1000;
	}

	int max_size = width*height*colours;

	buffer.Size( max_size );

	mode = SINGLE_JPEG;
	state = HEADER;
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
	state = HEADER;
	Debug( 3, ( "Request sent" ));
	return( 0 );
}

int RemoteCamera::ReadData( Buffer &buffer )
{
	fd_set rfds;
	FD_ZERO(&rfds);
	FD_SET(sd, &rfds);

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

	int total_bytes_to_read = 0;
	if ( ioctl( sd, FIONREAD, &total_bytes_to_read ) < 0 )
	{
		Error(( "Can't ioctl(): %s", strerror(errno) ));
		return( -1 );
	}
	Debug( 3, ( "Expecting %d bytes", total_bytes_to_read ));

	if ( total_bytes_to_read == 0 )
	{
		Debug( 3, ( "Socket closed" ));
		Disconnect();
		return( 0 );
	}

	int total_bytes_read = 0;
	do
	{
		static unsigned char temp_buffer[BUFSIZ];
		int bytes_to_read = total_bytes_to_read>sizeof(temp_buffer)?sizeof(temp_buffer):total_bytes_to_read;
		int bytes_read = read( sd, temp_buffer, bytes_to_read );

		if ( bytes_read < 0)
		{
			Error(( "Read error: %s", strerror(errno) ));
			return( -1 );
		}
		else if ( bytes_read < bytes_to_read )
		{
			Error(( "Incomplete read, expected %d, got %d", bytes_to_read, bytes_read ));
			return( -1 );
		}
		Debug( 3, ( "Read %d bytes", bytes_read ));
		buffer.Append( temp_buffer, bytes_read );
		total_bytes_read += bytes_read;
		total_bytes_to_read -= bytes_read;
	}
	while ( total_bytes_to_read );

	return( total_bytes_read );
}

int RemoteCamera::GetResponse()
{
	const char *header = 0;
	int header_len = 0;
	const char *http_version = 0;
	int status_code = 0;
	const char *status_mesg = 0;
	const char *connection_type = "";
	int content_length = 0;
	const char *content_type = "";
	const char *content_boundary = "";
	const char *subheader = 0;
	int subheader_len = 0;
	//int subcontent_length = 0;
	//const char *subcontent_type = "";

#if HAVE_LIBPCRE
	while ( true )
	{
		switch( state )
		{
			case HEADER :
			{
				static RegExpr *header_expr = 0;
				static RegExpr *status_expr = 0;
				static RegExpr *connection_expr = 0;
				static RegExpr *content_length_expr = 0;
				static RegExpr *content_type_expr = 0;

				int buffer_len = ReadData( buffer );
				if ( buffer_len < 0 )
				{
					return( -1 );
				}
				if ( !header_expr )
					header_expr = new RegExpr( "^(.+?\r?\n)(?=\r?\n)", PCRE_DOTALL );
				if ( header_expr->Match( (char*)buffer, buffer.Size() ) == 2 )
				{
					header = header_expr->MatchString( 1 );
					header_len = header_expr->MatchLength( 1 );
					Debug( 4, ( "Captured header (%d bytes):\n'%s'", header_len, header ));

					if ( !status_expr )
						status_expr = new RegExpr( "^HTTP/(1\\.[01]) +([0-9]+) +(.+?)\r?\n", PCRE_MULTILINE|PCRE_CASELESS );
					if ( status_expr->Match( header, header_len ) < 4 )
					{
						Error(( "Unable to extract HTTP status from header" ));
						return( -1 );
					}
					http_version = status_expr->MatchString( 1 );
					status_code = atoi( status_expr->MatchString( 2 ) );
					status_mesg = status_expr->MatchString( 3 );

					if ( status_code < 200 || status_code > 299 )
					{
						Error(( "Invalid response status %d: %s", status_code, status_mesg ));
						return( -1 );
					}
					Debug( 3, ( "Got status '%d' (%s), http version %s", status_code, status_mesg, http_version ));

					if ( !connection_expr )
						connection_expr = new RegExpr( "Connection: ?(.+?)\r?\n", PCRE_CASELESS );
					if ( connection_expr->Match( header, header_len ) == 2 )
					{
						connection_type = connection_expr->MatchString( 1 );
						Debug( 3, ( "Got connection '%s'", connection_type ));
					}

					if ( !content_length_expr )
						content_length_expr = new RegExpr( "Content-length: ?([0-9]+)\r?\n", PCRE_CASELESS );
					if ( content_length_expr->Match( header, header_len ) == 2 )
					{
						content_length = atoi( content_length_expr->MatchString( 1 ) );
						Debug( 3, ( "Got content length '%d'", content_length ));
					}

					if ( !content_type_expr )
						content_type_expr = new RegExpr( "Content-type: ?(.+?)(?:; ?boundary=(.+?))?\r?\n", PCRE_CASELESS );
					if ( content_type_expr->Match( header, header_len ) >= 2 )
					{
						content_type = content_type_expr->MatchString( 1 );
						Debug( 3, ( "Got content type '%s'\n", content_type ));
						if ( content_type_expr->MatchCount() > 2 )
						{
							content_boundary = content_type_expr->MatchString( 2 );
							Debug( 3, ( "Got content boundary '%s'", content_boundary ));
						}
					}

					if ( !strcasecmp( content_type, "image/jpeg" ) || !strcasecmp( content_type, "image/jpg" ) )
					{
						// Single image
						mode = SINGLE_JPEG;
						state = CONTENT;
					}
					else if ( !strcasecmp( content_type, "multipart/x-mixed-replace" ) )
					{
						// Image stream, so start processing
						if ( !content_boundary[0] )
						{
							Error(( "No content boundary found in header '%s'", header ));
							exit( -1 );
						}
						mode = MULTI_JPEG;
						state = SUBHEADER;
					}
					//else if ( !strcasecmp( content_type, "video/mpeg" ) || !strcasecmp( content_type, "video/mpg" ) )
					//{
						//// MPEG stream, coming soon!
					//}
					else
					{
						Error(( "Unrecognised content type '%s'", content_type ));
						return( -1 );
					}
					buffer.Consume( header_len );
				}
				else
				{
					Debug( 3, ( "Unable to extract header from stream, retrying" ));
					//return( -1 );
				}
				break;
			}
			case SUBHEADER :
			{
				static RegExpr *subheader_expr = 0;
				static RegExpr *subcontent_length_expr = 0;
				static RegExpr *subcontent_type_expr = 0;

				if ( !subheader_expr )
				{
					char subheader_pattern[256] = "";
					sprintf( subheader_pattern, "^((?:\r?\n)?\r?\n(?:--)?%s\r?\n.+?\r?\n\r?\n)", content_boundary );
					subheader_expr = new RegExpr( subheader_pattern, PCRE_MULTILINE|PCRE_DOTALL );
				}
				if ( subheader_expr->Match( (char *)buffer, (int)buffer ) == 2 )
				{
					subheader = subheader_expr->MatchString( 1 );
					subheader_len = subheader_expr->MatchLength( 1 );
					Debug( 4, ( "Captured subheader (%d bytes):'%s'", subheader_len, subheader ));

					if ( !subcontent_length_expr )
						subcontent_length_expr = new RegExpr( "Content-length: ?([0-9]+)\r?\n", PCRE_CASELESS );
					if ( subcontent_length_expr->Match( subheader, subheader_len ) == 2 )
					{
						content_length = atoi( subcontent_length_expr->MatchString( 1 ) );
						Debug( 3, ( "Got subcontent length '%d'", content_length ));
					}

					if ( !subcontent_type_expr )
						subcontent_type_expr = new RegExpr( "Content-type: ?(.+?)\r?\n", PCRE_CASELESS );
					if ( subcontent_type_expr->Match( subheader, subheader_len ) == 2 )
					{
						content_type = subcontent_type_expr->MatchString( 1 );
						Debug( 3, ( "Got subcontent type '%s'", content_type ));
					}

					buffer.Consume( subheader_len );
					state = CONTENT;
				}
				else
				{
					Debug( 3, ( "Unable to extract subheader from stream, retrying" ));
					int buffer_len = ReadData( buffer );
					if ( buffer_len < 0 )
					{
						return( -1 );
					}
				}
				break;
			}
			case CONTENT :
			{
				if ( strcasecmp( content_type, "image/jpeg" ) && strcasecmp( content_type, "image/jpg" ) )
				{
					Error(( "Found unsupported content type '%s'", content_type ));
					return( -1 );
				}

				while ( buffer.Size() < content_length )
				{
					int buffer_len = ReadData( buffer );
					if ( buffer_len < 0 )
					{
						return( -1 );
					}
				}

				if ( mode == SINGLE_JPEG )
				{
					state = HEADER;
					Disconnect();
				}
				else
				{
					state = SUBHEADER;
				}
				Debug( 3, ( "Returning %d bytes of captured content", content_length ));
				return( content_length );
			}
		}
	}
	return( 0 );
#else
	Fatal(( "You must have libpcre.a installed to use remote cameras" ));
#endif // HAVE_LIBPCRE
}

int RemoteCamera::PreCapture()
{
	if ( sd < 0 )
	{
		Connect();
		if ( sd < 0 )
		{
			return( -1 );
		}
		mode = SINGLE_JPEG;
		buffer.Empty();
	}
	if ( mode == SINGLE_JPEG )
	{
		if ( SendRequest() < 0 )
		{
			Disconnect();
			return( -1 );
		}
	}
	return( 0 );
}

int RemoteCamera::PostCapture( Image &image )
{
	int content_length = GetResponse();
	if ( content_length < 0 )
	{
		Disconnect();
		return( -1 );
	}
	image.DecodeJpeg( buffer.Extract( content_length ), content_length );
	return( 0 );
}

void RemoteCamera::Base64Encode( const char *in_string, char *out_string )
{
	static char base64_table[64] = { '\0' };

	if ( !base64_table[0] )
	{
		int i = 0;
		for ( char c = 'A'; c <= 'Z'; c++ )
			base64_table[i++] = c;
		for ( char c = 'a'; c <= 'z'; c++ )
			base64_table[i++] = c;
		for ( char c = '0'; c <= '9'; c++ )
			base64_table[i++] = c;
		base64_table[i++] = '+';
		base64_table[i++] = '/';
	}

	int in_len = strlen( in_string );
	const char *in_ptr = in_string;
	char *out_ptr = out_string;
	while( *in_ptr )
	{
		unsigned char selection = *in_ptr >> 2;
		unsigned char remainder = (*in_ptr++ & 0x03) << 4;
		*out_ptr++ = base64_table[selection];

		if ( *in_ptr )
		{
			selection = remainder | (*in_ptr >> 4);
			remainder = (*in_ptr++ & 0x0f) << 2;
			*out_ptr++ = base64_table[selection];
		
			if ( *in_ptr )
			{
				selection = remainder | (*in_ptr >> 6);
				*out_ptr++ = base64_table[selection];
				selection = (*in_ptr++ & 0x3f);
				*out_ptr++ = base64_table[selection];
			}
			else
			{
				*out_ptr++ = base64_table[remainder];
				*out_ptr++ = '=';
			}
		}
		else
		{
			*out_ptr++ = base64_table[remainder];
			*out_ptr++ = '=';
			*out_ptr++ = '=';
		}
	}
	*out_ptr = '\0';
}
