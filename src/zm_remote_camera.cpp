//
// ZoneMinder Remote Camera Class Implementation, $Date$, $Revision$
// Copyright (C) 2003, 2004, 2005  Philip Coombes
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

bool RemoteCamera::netcam_regexps = false;

RemoteCamera::RemoteCamera( const char *p_host, const char *p_port, const char *p_path, int p_width, int p_height, int p_palette, int p_brightness, int p_contrast, int p_hue, int p_colour, bool p_capture ) : Camera( REMOTE, p_width, p_height, p_palette, p_brightness, p_contrast, p_hue, p_colour, p_capture ), host( p_host ), port( p_port ), path( p_path )
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
    netcam_regexps = (bool)config.Item( ZM_NETCAM_REGEXPS );

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
		snprintf( request, sizeof(request), "GET %s HTTP/%s\n", path, (const char *)config.Item( ZM_HTTP_VERSION ) );
		snprintf( &(request[strlen(request)]), sizeof(request)-strlen(request), "User-Agent: %s/%s\n", (const char *)config.Item( ZM_HTTP_UA ), ZM_VERSION );
		snprintf( &(request[strlen(request)]), sizeof(request)-strlen(request), "Host: %s\n", host );
		snprintf( &(request[strlen(request)]), sizeof(request)-strlen(request), "Connection: Keep-Alive\n" );
		if ( auth )
		{
			snprintf( &(request[strlen(request)]), sizeof(request)-strlen(request), "Authorization: Basic %s\n", auth64 );
		}
		snprintf( &(request[strlen(request)]), sizeof(request)-strlen(request), "\n" );
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
		sd = socket( hp->h_addrtype, SOCK_STREAM, 0 );
		if ( sd < 0 )
		{
			Error(( "Can't create socket: %s", strerror(errno) ));
			return( -1 );
		}

		if ( connect( sd, (struct sockaddr *)&sa, sizeof(sa) ) < 0 )
		{
			Error(( "Can't connect to remote camera: %s", strerror(errno) ));
			Disconnect();
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

int RemoteCamera::ReadData( Buffer &buffer, int bytes_expected )
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

	if ( bytes_expected )
	{
		total_bytes_to_read = bytes_expected;
	}
	else
	{
		if ( ioctl( sd, FIONREAD, &total_bytes_to_read ) < 0 )
		{
			Error(( "Can't ioctl(): %s", strerror(errno) ));
			return( -1 );
		}

		if ( total_bytes_to_read == 0 )
		{
			Debug( 3, ( "Socket closed" ));
			Disconnect();
			return( 0 );
		}
	}
	Debug( 3, ( "Expecting %d bytes", total_bytes_to_read ));

	int total_bytes_read = 0;
	do
	{
		static unsigned char temp_buffer[5*BUFSIZ];
		int bytes_to_read = total_bytes_to_read>sizeof(temp_buffer)?sizeof(temp_buffer):total_bytes_to_read;
		int bytes_read = read( sd, temp_buffer, bytes_to_read );

		if ( bytes_read < 0)
		{
			Error(( "Read error: %s", strerror(errno) ));
			return( -1 );
		}
		else if ( bytes_read == 0)
		{
			Debug( 3, ( "Socket closed" ));
			Disconnect();
			return( 0 );
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
#if HAVE_LIBPCRE
	if ( netcam_regexps )
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
						header_expr = new RegExpr( "^(.+?\r?\n\r?\n)", PCRE_DOTALL );
					if ( header_expr->Match( (char*)buffer, buffer.Size() ) == 2 )
					{
						header = header_expr->MatchString( 1 );
						header_len = header_expr->MatchLength( 1 );
						Debug( 4, ( "Captured header (%d bytes):\n'%s'", header_len, header ));

						if ( !status_expr )
							status_expr = new RegExpr( "^HTTP/(1\\.[01]) +([0-9]+) +(.+?)\r?\n", PCRE_CASELESS );
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
						snprintf( subheader_pattern, sizeof(subheader_pattern), "^((?:\r?\n){0,2}?(?:--)?%s\r?\n.+?\r?\n\r?\n)", content_boundary );
						subheader_expr = new RegExpr( subheader_pattern, PCRE_DOTALL );
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

					if ( content_length )
					{
						while ( buffer.Size() < content_length )
						{
							int buffer_len = ReadData( buffer );
							if ( buffer_len < 0 )
							{
								return( -1 );
							}
						}
						Debug( 3, ( "Got end of image by length, content-length = %d", content_length ));
					}
					else
					{
						while ( !content_length )
						{
							int buffer_len = ReadData( buffer );
							if ( buffer_len < 0 )
							{
								return( -1 );
							}
							static RegExpr *content_expr = 0;
							if ( buffer_len )
							{
								if ( mode == MULTI_JPEG )
								{
									if ( !content_expr )
									{
										char content_pattern[256] = "";
										snprintf( content_pattern, sizeof(content_pattern), "^(.+?)(?:\r?\n)*(?:--)?%s\r?\n", content_boundary );
										content_expr = new RegExpr( content_pattern, PCRE_DOTALL );
									}
									if ( content_expr->Match( buffer, buffer.Size() ) == 2 )
									{
										content_length = content_expr->MatchLength( 1 );
										Debug( 3, ( "Got end of image by pattern, content-length = %d", content_length ));
									}
								}
							}
							else
							{
								content_length = buffer.Size();
								Debug( 3, ( "Got end of image by closure, content-length = %d", content_length ));
								if ( mode == SINGLE_JPEG )
								{
									if ( !content_expr )
									{
										content_expr = new RegExpr( "^(.+?)(?:\r?\n){1,2}?$", PCRE_DOTALL );
									}
									if ( content_expr->Match( buffer, buffer.Size() ) == 2 )
									{
										content_length = content_expr->MatchLength( 1 );
										Debug( 3, ( "Trimmed end of image, new content-length = %d", content_length ));
									}
								}
							}
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
					Debug( 3, ( "Returning %d (%d) bytes of captured content", content_length, buffer.Size() ));
					return( content_length );
				}
			}
		}
	}
	else
#endif // HAVE_LIBPCRE
	{
		static const char *http_match = "HTTP/";
		static const char *connection_match = "Connection:";
		static const char *content_length_match = "Content-length:";
		static const char *content_type_match = "Content-type:";
		static const char *boundary_match = "boundary=";
		static int http_match_len = 0;
		static int connection_match_len = 0;
		static int content_length_match_len = 0;
		static int content_type_match_len = 0;
		static int boundary_match_len = 0;

		if ( !http_match_len )
			http_match_len = strlen( http_match );
		if ( !connection_match_len )
			connection_match_len = strlen( connection_match );
		if ( !content_length_match_len )
			content_length_match_len = strlen( content_length_match );
		if ( !content_type_match_len )
			content_type_match_len = strlen( content_type_match );
		if ( !boundary_match_len )
			boundary_match_len = strlen( boundary_match );

		static int n_headers;
		static char *headers[32];

		static int n_subheaders;
		static char *subheaders[32];

		static char *http_header;
		static char *connection_header;
		static char *content_length_header;
		static char *content_type_header;
		static char *boundary_header;
		static char *subcontent_length_header;
		static char *subcontent_type_header;
	
		static char http_version[16];
		static char status_code[16];
		static int status;
		static char status_mesg[256];
		static char connection_type[32];
		static int content_length;
		static char content_type[32];
		static char content_boundary[64];
		int content_boundary_len;

		while ( true )
		{
			switch( state )
			{
				case HEADER :
				{
					n_headers = 0;
					http_header = 0;
					connection_header = 0;
					content_length_header = 0;
					content_type_header = 0;

					http_version[0] = '\0';
					status_code [0]= '\0';
					status = 0;
					status_mesg [0]= '\0';
					connection_type [0]= '\0';
					content_length = 0;
					content_type[0] = '\0';
					content_boundary[0] = '\0';
				}
				case HEADERCONT :
				{
					int buffer_len = ReadData( buffer );
					if ( buffer_len < 0 )
					{
						return( -1 );
					}

					char *crlf = 0;
					char *header_ptr = (char *)buffer;
					int header_len = buffer.Size();
					bool all_headers = false;

					while( true )
					{
						int crlf_len = memspn( header_ptr, "\r\n", header_len );
						if ( n_headers )
						{
							if ( (crlf_len == 2 && !strncmp( header_ptr, "\n\n", crlf_len )) || (crlf_len == 4 && !strncmp( header_ptr, "\r\n\r\n", crlf_len )) )
							{
								*header_ptr = '\0';
								header_ptr += crlf_len;
								header_len -= buffer.Consume( header_ptr-(char *)buffer );
								all_headers = true;
								break;
							}
						}
						if ( crlf_len )
						{
							if ( header_len == crlf_len )
							{
								break;
							}
							else
							{
								*header_ptr = '\0';
								header_ptr += crlf_len;
								header_len -= buffer.Consume( header_ptr-(char *)buffer );
							}
						}

						Debug( 6, ( header_ptr ));
						if ( crlf = mempbrk( header_ptr, "\r\n", header_len ) )
						{
							headers[n_headers++] = header_ptr;

							if ( !http_header && (strncasecmp( header_ptr, http_match, http_match_len ) == 0) )
							{
								http_header = header_ptr+http_match_len;
								Debug( 6, ( "Got http header '%s'", header_ptr ));
							}
							else if ( !connection_header && (strncasecmp( header_ptr, connection_match, connection_match_len) == 0) )
							{
								connection_header = header_ptr+connection_match_len;
								Debug( 6, ( "Got connection header '%s'", header_ptr ));
							}
							else if ( !content_length_header && (strncasecmp( header_ptr, content_length_match, content_length_match_len) == 0) )
							{
								content_length_header = header_ptr+content_length_match_len;
								Debug( 6, ( "Got content length header '%s'", header_ptr ));
							}
							else if ( !content_type_header && (strncasecmp( header_ptr, content_type_match, content_type_match_len) == 0) )
							{
								content_type_header = header_ptr+content_type_match_len;
								Debug( 6, ( "Got content type header '%s'", header_ptr ));
							}
							else
							{
								Debug( 6, ( "Got ignored header '%s'", header_ptr ));
							}
							header_ptr = crlf;
							header_len -= buffer.Consume( header_ptr-(char *)buffer );
						}
						else
						{
							// No end of line found
							break;
						}
					}

					if ( all_headers )
					{
						char *start_ptr, *end_ptr;

						if ( !http_header )
						{
							Error(( "Unable to extract HTTP status from header" ));
							return( -1 );
						}

						start_ptr = http_header;
						end_ptr = start_ptr+strspn( start_ptr, "10." );

						memset( http_version, 0, sizeof(http_version) );
						strncpy( http_version, start_ptr, end_ptr-start_ptr );

						start_ptr = end_ptr;
						start_ptr += strspn( start_ptr, " " );
						end_ptr = start_ptr+strspn( start_ptr, "0123456789" );

						memset( status_code, 0, sizeof(status_code) );
						strncpy( status_code, start_ptr, end_ptr-start_ptr );
						int status = atoi( status_code );

						start_ptr = end_ptr;
						start_ptr += strspn( start_ptr, " " );
						strcpy( status_mesg, start_ptr );

						if ( status < 200 || status > 299 )
						{
							Error(( "Invalid response status %d: %s", status_code, status_mesg ));
							return( -1 );
						}
						Debug( 3, ( "Got status '%d' (%s), http version %s", status, status_mesg, http_version ));

						if ( connection_header )
						{
							memset( connection_type, 0, sizeof(connection_type) );
							start_ptr = connection_header + strspn( connection_header, " " );
							strcpy( connection_type, start_ptr );
							Debug( 3, ( "Got connection '%s'", connection_type ));
						}
						if ( content_length_header )
						{
							start_ptr = content_length_header + strspn( content_length_header, " " );
							content_length = atoi( start_ptr );
							Debug( 3, ( "Got content length '%d'", content_length ));
						}
						if ( content_type_header )
						{
							memset( content_type, 0, sizeof(content_type) );
							start_ptr = content_type_header + strspn( content_type_header, " " );
							if ( end_ptr = strchr( start_ptr, ';' ) )
							{
								strncpy( content_type, start_ptr, end_ptr-start_ptr );
								Debug( 3, ( "Got content type '%s'", content_type ));

								start_ptr = end_ptr + strspn( end_ptr, "; " );

								if ( strncasecmp( start_ptr, boundary_match, boundary_match_len ) == 0 )
								{
									start_ptr += boundary_match_len;
									start_ptr += strspn( start_ptr, "-" );
									content_boundary_len = sprintf( content_boundary, "--%s", start_ptr );
									Debug( 3, ( "Got content boundary '%s'", content_boundary ));
								}
								else
								{
									Error(( "No content boundary found in header '%s'", content_type_header ));
								}
							}
							else
							{
								strcpy( content_type, start_ptr );
								Debug( 3, ( "Got content type '%s'", content_type ));
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
							if ( !content_boundary )
							{
								Error(( "No content boundary found in header '%s'", content_type_header ));
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
					}
					else
					{
						Debug( 3, ( "Unable to extract entire header from stream, continuing" ));
						state = HEADERCONT;
						//return( -1 );
					}
					break;
				}
				case SUBHEADER :
				{
					n_subheaders = 0;
					boundary_header = 0;
					subcontent_length_header = 0;
					subcontent_type_header = 0;
					content_length = 0;
					content_type[0] = '\0';
				}
				case SUBHEADERCONT :
				{
					char *crlf = 0;
					char *subheader_ptr = (char *)buffer;
					int subheader_len = buffer.Size();
					bool all_headers = false;

					while( true )
					{
						int crlf_len = memspn( subheader_ptr, "\r\n", subheader_len );
						if ( n_subheaders )
						{
							if ( (crlf_len == 2 && !strncmp( subheader_ptr, "\n\n", crlf_len )) || (crlf_len == 4 && !strncmp( subheader_ptr, "\r\n\r\n", crlf_len )) )
							{
								*subheader_ptr = '\0';
								subheader_ptr += crlf_len;
								subheader_len -= buffer.Consume( subheader_ptr-(char *)buffer );
								all_headers = true;
								break;
							}
						}
						if ( crlf_len )
						{
							if ( subheader_len == crlf_len )
							{
								break;
							}
							else
							{
								*subheader_ptr = '\0';
								subheader_ptr += crlf_len;
								subheader_len -= buffer.Consume( subheader_ptr-(char *)buffer );
							}
						}

						Debug( 6, ( "%d = %d", buffer.Size(), (subheader_ptr-(char *)buffer) ));
						Debug( 6, ( "%d: %s", subheader_len, subheader_ptr ));

						if ( crlf = mempbrk( subheader_ptr, "\r\n", subheader_len ) )
						{
							subheaders[n_subheaders++] = subheader_ptr;

							if ( !boundary_header && (strncasecmp( subheader_ptr, content_boundary, content_boundary_len ) == 0) )
							{
								boundary_header = subheader_ptr;
								Debug( 4, ( "Got boundary subheader '%s'", subheader_ptr ));
							}
							else if ( !subcontent_length_header && (strncasecmp( subheader_ptr, content_length_match, content_length_match_len) == 0) )
							{
								subcontent_length_header = subheader_ptr+content_length_match_len;
								Debug( 4, ( "Got content length subheader '%s'", subheader_ptr ));
							}
							else if ( !subcontent_type_header && (strncasecmp( subheader_ptr, content_type_match, content_type_match_len) == 0) )
							{
								subcontent_type_header = subheader_ptr+content_type_match_len;
								Debug( 4, ( "Got content type subheader '%s'", subheader_ptr ));
							}
							else
							{
								Debug( 6, ( "Got ignored subheader '%s' found", subheader_ptr ));
							}
							subheader_ptr = crlf;
							subheader_len -= buffer.Consume( subheader_ptr-(char *)buffer );
						}
						else
						{
							// No line end found
							break;
						}
					}
					
					if ( all_headers && boundary_header )
					{
						char *start_ptr, *end_ptr;

						Debug( 3, ( "Got boundary '%s'", boundary_header ));

						if ( subcontent_length_header )
						{
							start_ptr = subcontent_length_header + strspn( subcontent_length_header, " " );
							content_length = atoi( start_ptr );
							Debug( 3, ( "Got subcontent length '%d'", content_length ));
						}
						if ( subcontent_type_header )
						{
							memset( content_type, 0, sizeof(content_type) );
							start_ptr = subcontent_type_header + strspn( subcontent_type_header, " " );
							strcpy( content_type, start_ptr );
							Debug( 3, ( "Got subcontent type '%s'", content_type ));
						}
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
						state = SUBHEADERCONT;
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

					if ( buffer.Size() >= 2 )
					{
						if ( buffer[0] != 0xff || buffer[1] != 0xd8 )
						{
							Error(( "Found bogus jpeg header '%02x%02x'", buffer[0], buffer[1] ));
							return( -1 );
						}
					}

					if ( content_length )
					{
						while ( buffer.Size() < content_length )
						{
							//int buffer_len = ReadData( buffer, content_length-buffer.Size() );
							int buffer_len = ReadData( buffer );
							if ( buffer_len < 0 )
							{
								return( -1 );
							}
						}
						Debug( 3, ( "Got end of image by length, content-length = %d", content_length ));
					}
					else
					{
						while ( !content_length )
						{
							int buffer_len = ReadData( buffer );
							if ( buffer_len < 0 )
							{
								return( -1 );
							}
							if ( buffer_len )
							{
								if ( mode == MULTI_JPEG )
								{
									char *start_ptr = buffer;
									int offset = 0;
									while ( offset = memspn( start_ptr, "\r\n", buffer.Size()-(start_ptr-(char *)buffer) ) )
									{
										start_ptr += offset;
										if ( *start_ptr == '\r' )
										{
											start_ptr++;
											if ( *start_ptr == '\n' )
												start_ptr++;
											content_length = start_ptr - (char *)buffer;
											Debug( 3, ( "Got end of image by pattern, content-length = %d", content_length ));
										}
									}
								}
							}
							else
							{
								content_length = buffer.Size();
								Debug( 3, ( "Got end of image by closure, content-length = %d", content_length ));
								if ( mode == SINGLE_JPEG )
								{
									char *end_ptr = (char *)buffer+buffer.Size();

									while( *end_ptr == '\r' || *end_ptr == '\n' )
									{
										content_length--;
										end_ptr--;
									}

									if ( end_ptr != ((char *)buffer+buffer.Size()) )
									{
										Debug( 3, ( "Trimmed end of image, new content-length = %d", content_length ));
									}
								}
							}
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
					if ( buffer.Size() >= 2 )
					{
						if ( buffer[0] != 0xff || buffer[1] != 0xd8 )
						{
							Error(( "Found bogus jpeg header '%02x%02x'", buffer[0], buffer[1] ));
							return( -1 );
						}
					}

					Debug( 3, ( "Returning %d (%d) bytes of captured content", content_length, buffer.Size() ));
					return( content_length );
				}
			}
		}
	}
	return( 0 );
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
