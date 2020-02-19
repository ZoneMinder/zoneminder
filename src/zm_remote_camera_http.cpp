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

#include "zm_remote_camera_http.h"
#include "zm_rtsp_auth.h"

#include "zm_mem_utils.h"
#include "zm_signal.h"

#include <sys/types.h>
#include <sys/socket.h>
#include <errno.h>
#include <netdb.h>

#ifdef SOLARIS
#include <sys/filio.h> // FIONREAD and friends
#endif
#ifdef __FreeBSD__
#include <netinet/in.h>
#endif

#if HAVE_LIBPCRE
static RegExpr *header_expr = 0;
static RegExpr *status_expr = 0;
static RegExpr *connection_expr = 0;
static RegExpr *content_length_expr = 0;
static RegExpr *content_type_expr = 0;
#endif

RemoteCameraHttp::RemoteCameraHttp(
  unsigned int p_monitor_id,
  const std::string &p_method,
  const std::string &p_host,
  const std::string &p_port,
  const std::string &p_path,
  int p_width, int p_height,
  int p_colours,
  int p_brightness,
  int p_contrast,
  int p_hue,
  int p_colour,
  bool p_capture,
  bool p_record_audio ) :
  RemoteCamera(
    p_monitor_id,
    "http",
    p_host,
    p_port,
    p_path,
    p_width,
    p_height,
    p_colours,
    p_brightness,
    p_contrast,
    p_hue,
    p_colour,
    p_capture,
    p_record_audio )
{
  sd = -1;

  timeout.tv_sec = 0;
  timeout.tv_usec = 0;

  if ( p_method == "simple" )
    method = SIMPLE;
  else if ( p_method == "regexp" ) {
    method = REGEXP;
  } else
    Fatal( "Unrecognised method '%s' when creating HTTP camera %d", p_method.c_str(), monitor_id );
  if ( capture ) {
    Initialise();
  }
}

RemoteCameraHttp::~RemoteCameraHttp() {
  if ( capture ) {
    Terminate();
  }
}

void RemoteCameraHttp::Initialise() {
  RemoteCamera::Initialise();

  if ( request.empty() ) {
    request = stringtf( "GET %s HTTP/%s\r\n", path.c_str(), config.http_version );
    request += stringtf( "User-Agent: %s/%s\r\n", config.http_ua, ZM_VERSION );
    request += stringtf( "Host: %s\r\n", host.c_str());
    if ( strcmp( config.http_version, "1.0" ) == 0 )
      request += stringtf( "Connection: Keep-Alive\r\n" );
    if ( !auth.empty() )
      request += stringtf( "Authorization: Basic %s\r\n", auth64.c_str() );
    request += "\r\n";
    Debug( 2, "Request: %s", request.c_str() );
  }

  if ( !timeout.tv_sec ) {
    timeout.tv_sec = config.http_timeout/1000; 
    timeout.tv_usec = (config.http_timeout%1000)*1000;
  }

  int max_size = width*height*colours;

  buffer.size( max_size );

  mode = SINGLE_IMAGE;
  format = UNDEF;
  state = HEADER;

#if HAVE_LIBPCRE
    if ( method == REGEXP ) {
			if ( !header_expr )
				header_expr = new RegExpr("^(.+?\r?\n\r?\n)", PCRE_DOTALL);
			if ( !status_expr )
				status_expr = new RegExpr("^HTTP/(1\\.[01]) +([0-9]+) +(.+?)\r?\n", PCRE_CASELESS);
			if ( !connection_expr )
				connection_expr = new RegExpr("Connection: ?(.+?)\r?\n", PCRE_CASELESS);
			if ( !content_length_expr )
				content_length_expr = new RegExpr("Content-length: ?([0-9]+)\r?\n", PCRE_CASELESS);
			if ( !content_type_expr )
				content_type_expr = new RegExpr("Content-type: ?(.+?)(?:; ?boundary=\x22?(.+?)\x22?)?\r?\n", PCRE_CASELESS);
		}
#endif
} // end void RemoteCameraHttp::Initialise()

int RemoteCameraHttp::Connect() {
  struct addrinfo *p = NULL;

  for ( p = hp; p != NULL; p = p->ai_next ) {
    sd = socket( p->ai_family, p->ai_socktype, p->ai_protocol );
    if ( sd < 0 ) {
      Warning("Can't create socket: %s", strerror(errno) );
      continue;
    }

    if ( connect( sd, p->ai_addr, p->ai_addrlen ) < 0 ) {
      close(sd);
      sd = -1;
      char buf[sizeof(struct in6_addr)];
      struct sockaddr_in *addr;
      addr = (struct sockaddr_in *)p->ai_addr; 
      inet_ntop( AF_INET, &(addr->sin_addr), buf, INET6_ADDRSTRLEN );

      Warning("Can't connect to remote camera mid: %d at %s: %s", monitor_id, buf, strerror(errno) );
      continue;
    }

    /* If we got here, we must have connected successfully */
    break;
  }

  if ( p == NULL ) {
    Error("Unable to connect to the remote camera, aborting");
    return -1;
  }

  Debug(3, "Connected to host, socket = %d", sd);
  return sd;
} // end int RemoteCameraHttp::Connect()

int RemoteCameraHttp::Disconnect() {
  close(sd);
  sd = -1;
  Debug(3, "Disconnected from host");
  return 0;
}

int RemoteCameraHttp::SendRequest() {
  Debug(2, "Sending request: %s", request.c_str());
  if ( write(sd, request.data(), request.length()) < 0 ) {
    Error("Can't write: %s", strerror(errno));
    Disconnect();
    return -1;
  }
  format = UNDEF;
  state = HEADER;
  Debug(3, "Request sent");
  return 0;
}

/* Return codes are as follows:
 * -1 means there was an error
 * 0 means no bytes were returned but there wasn't actually an error.
 * > 0 is the # of bytes read.
 */

int RemoteCameraHttp::ReadData( Buffer &buffer, unsigned int bytes_expected ) {
  fd_set rfds;
  FD_ZERO(&rfds);
  FD_SET(sd, &rfds);

  struct timeval temp_timeout = timeout;

  int n_found = select(sd+1, &rfds, NULL, NULL, &temp_timeout);
  if( n_found == 0 ) {
    Debug( 1, "Select timed out timeout was %d secs %d usecs", temp_timeout.tv_sec, temp_timeout.tv_usec );
    int error = 0;
    socklen_t len = sizeof (error);
    int retval = getsockopt (sd, SOL_SOCKET, SO_ERROR, &error, &len);
    if(retval != 0 ) {
      Debug( 1, "error getting socket error code %s", strerror(retval) );
    }
    if (error != 0 ) {
      return -1;
    }
    // Why are we disconnecting?  It's just a timeout, meaning that data wasn't available.
    //Disconnect();
    return 0;
  } else if ( n_found < 0) {
    Error("Select error: %s", strerror(errno));
    return -1;
  }

  unsigned int total_bytes_to_read = 0;

  if ( bytes_expected ) {
    total_bytes_to_read = bytes_expected;
  } else {
    if ( ioctl( sd, FIONREAD, &total_bytes_to_read ) < 0 ) {
      Error( "Can't ioctl(): %s", strerror(errno) );
      return -1;
    }

    if ( total_bytes_to_read == 0 ) {
      if ( mode == SINGLE_IMAGE ) {
        int error = 0;
        socklen_t len = sizeof (error);
        int retval = getsockopt( sd, SOL_SOCKET, SO_ERROR, &error, &len );
        if ( retval != 0 ) {
          Debug( 1, "error getting socket error code %s", strerror(retval) );
        }
        if ( error != 0 ) {
          return -1;
        }
        // Case where we are grabbing a single jpg, but no content-length was given, so the expectation is that we read until close.
		    return 0;
      }
      // If socket is closed locally, then select will fail, but if it is closed remotely
      // then we have an exception on our socket.. but no data.
      Debug(3, "Socket closed remotely");
      //Disconnect(); // Disconnect is done outside of ReadData now.
      return -1;
    }

    // There can be lots of bytes available.  I've seen 4MB or more. This will vastly inflate our buffer size unnecessarily.
    if ( total_bytes_to_read > ZM_NETWORK_BUFSIZ ) {
      total_bytes_to_read = ZM_NETWORK_BUFSIZ;
      Debug(3, "Just getting 32K" );
    } else {
      Debug(3, "Just getting %d", total_bytes_to_read );
    }
  } // end if bytes_expected or not
  Debug( 3, "Expecting %d bytes", total_bytes_to_read );

  int total_bytes_read = 0;
  do {
    int bytes_read = buffer.read_into( sd, total_bytes_to_read );
    if ( bytes_read < 0 ) {
      Error( "Read error: %s", strerror(errno) );
      return( -1 );
    } else if ( bytes_read == 0 ) {
      Debug( 2, "Socket closed" );
      //Disconnect(); // Disconnect is done outside of ReadData now.
      return( -1 );
    } else if ( (unsigned int)bytes_read < total_bytes_to_read ) {
      Error( "Incomplete read, expected %d, got %d", total_bytes_to_read, bytes_read );
      return( -1 );
    }
    Debug( 3, "Read %d bytes", bytes_read );
    total_bytes_read += bytes_read;
    total_bytes_to_read -= bytes_read;
  } while ( total_bytes_to_read );

  Debug(4, buffer);

  return total_bytes_read;
}

int RemoteCameraHttp::GetData() {
	time_t start_time = time(NULL);
	int buffer_len = 0;
	while ( !( buffer_len = ReadData(buffer) ) ) {
			if ( zm_terminate ||  ( start_time - time(NULL) < ZM_WATCH_MAX_DELAY ))
				return -1;
		Debug(4, "Timeout waiting for REGEXP HEADER");
		usleep(100000);
	}
	return buffer_len;
}

int RemoteCameraHttp::GetResponse() {
  int buffer_len;
#if HAVE_LIBPCRE
  if ( method == REGEXP ) {
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

    while ( !zm_terminate ) {
      switch( state ) {
        case HEADER :
          {
						buffer_len = GetData();
            if ( buffer_len < 0 ) {
              Error("Unable to read header data");
              return -1;
            }
						bytes += buffer_len;
            if ( header_expr->Match( (char*)buffer, buffer.size() ) == 2 ) {
              header = header_expr->MatchString( 1 );
              header_len = header_expr->MatchLength( 1 );
              Debug(4, "Captured header (%d bytes):\n'%s'", header_len, header);

              if ( status_expr->Match( header, header_len ) < 4 )
              {
                Error( "Unable to extract HTTP status from header" );
                return( -1 );
              }
              http_version = status_expr->MatchString( 1 );
              status_code = atoi( status_expr->MatchString( 2 ) );
              status_mesg = status_expr->MatchString( 3 );

              if ( status_code == 401 ) {
                if ( mNeedAuth ) {
                  Error( "Failed authentication: " );
                  return( -1 );
                }
                mNeedAuth = true;
                std::string Header = header;

                mAuthenticator->checkAuthResponse(Header);
                if ( mAuthenticator->auth_method() == zm::AUTH_DIGEST ) {
                  Debug( 2, "Need Digest Authentication" );
                  request = stringtf( "GET %s HTTP/%s\r\n", path.c_str(), config.http_version );
                  request += stringtf( "User-Agent: %s/%s\r\n", config.http_ua, ZM_VERSION );
                  request += stringtf( "Host: %s\r\n", host.c_str());
                  if ( strcmp( config.http_version, "1.0" ) == 0 )
                    request += stringtf( "Connection: Keep-Alive\r\n" );
                  request += mAuthenticator->getAuthHeader( "GET", path.c_str() );
                  request += "\r\n";

                  Debug( 2, "New request header: %s", request.c_str() );
                  return( 0 );
                } 

              } else if ( status_code < 200 || status_code > 299 ) {
                Error( "Invalid response status %d: %s\n%s", status_code, status_mesg, (char *)buffer );
                return( -1 );
              }
              Debug( 3, "Got status '%d' (%s), http version %s", status_code, status_mesg, http_version );

              if ( connection_expr->Match( header, header_len ) == 2 )
              {
                connection_type = connection_expr->MatchString( 1 );
                Debug( 3, "Got connection '%s'", connection_type );
              }

              if ( content_length_expr->Match( header, header_len ) == 2 )
              {
                content_length = atoi( content_length_expr->MatchString( 1 ) );
                Debug( 3, "Got content length '%d'", content_length );
              }

              if ( content_type_expr->Match( header, header_len ) >= 2 )
              {
                content_type = content_type_expr->MatchString( 1 );
                Debug( 3, "Got content type '%s'\n", content_type );
                if ( content_type_expr->MatchCount() > 2 )
                {
                  content_boundary = content_type_expr->MatchString( 2 );
                  Debug( 3, "Got content boundary '%s'", content_boundary );
                }
              }

              if ( !strcasecmp( content_type, "image/jpeg" ) || !strcasecmp( content_type, "image/jpg" ) )
              {
                // Single image
                mode = SINGLE_IMAGE;
                format = JPEG;
                state = CONTENT;
              }
              else if ( !strcasecmp( content_type, "image/x-rgb" ) )
              {
                // Single image
                mode = SINGLE_IMAGE;
                format = X_RGB;
                state = CONTENT;
              }
              else if ( !strcasecmp( content_type, "image/x-rgbz" ) )
              {
                // Single image
                mode = SINGLE_IMAGE;
                format = X_RGBZ;
                state = CONTENT;
              }
              else if ( !strcasecmp( content_type, "multipart/x-mixed-replace" ) )
              {
                // Image stream, so start processing
                if ( !content_boundary[0] )
                {
                  Error( "No content boundary found in header '%s'", header );
                  return( -1 );
                }
                mode = MULTI_IMAGE;
                state = SUBHEADER;
              }
              //else if ( !strcasecmp( content_type, "video/mpeg" ) || !strcasecmp( content_type, "video/mpg" ) )
              //{
              //// MPEG stream, coming soon!
              //}
              else
              {
                Error( "Unrecognised content type '%s'", content_type );
                return( -1 );
              }
              buffer.consume( header_len );
            }
            else
            {
              Debug( 3, "Unable to extract header from stream, retrying" );
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
              Debug( 4, "Captured subheader (%d bytes):'%s'", subheader_len, subheader );

              if ( !subcontent_length_expr )
                subcontent_length_expr = new RegExpr( "Content-length: ?([0-9]+)\r?\n", PCRE_CASELESS );
              if ( subcontent_length_expr->Match( subheader, subheader_len ) == 2 )
              {
                content_length = atoi( subcontent_length_expr->MatchString( 1 ) );
                Debug( 3, "Got subcontent length '%d'", content_length );
              }

              if ( !subcontent_type_expr )
                subcontent_type_expr = new RegExpr( "Content-type: ?(.+?)\r?\n", PCRE_CASELESS );
              if ( subcontent_type_expr->Match( subheader, subheader_len ) == 2 )
              {
                content_type = subcontent_type_expr->MatchString( 1 );
                Debug( 3, "Got subcontent type '%s'", content_type );
              }

              buffer.consume( subheader_len );
              state = CONTENT;
            }
            else
            {
              Debug( 3, "Unable to extract subheader from stream, retrying" );
							buffer_len = GetData();
              if ( buffer_len < 0 ) {
                Error( "Unable to extract subheader data" );
                return( -1 );
              }
							bytes += buffer_len;
            }
            break;
          }
        case CONTENT :
          {

            // if content_type is something like image/jpeg;size=, this will strip the ;size=
            char * semicolon = strchr( (char *)content_type, ';' );
            if ( semicolon ) {
              *semicolon = '\0';
            }

            if ( !strcasecmp( content_type, "image/jpeg" ) || !strcasecmp( content_type, "image/jpg" ) )
            {
              format = JPEG;
            }
            else if ( !strcasecmp( content_type, "image/x-rgb" ) )
            {
              format = X_RGB;
            }
            else if ( !strcasecmp( content_type, "image/x-rgbz" ) )
            {
              format = X_RGBZ;
            }
            else
            {
              Error( "Found unsupported content type '%s'", content_type );
              return( -1 );
            }

            if ( content_length )
            {
              while ( ((long)buffer.size() < content_length ) && ! zm_terminate )
              {
                Debug(3, "Need more data buffer %d < content length %d", buffer.size(), content_length );
								int bytes_read = GetData();

                if ( bytes_read < 0 ) {
                  Error( "Unable to read content" );
                  return( -1 );
                }
								bytes += bytes_read;
              }
              Debug( 3, "Got end of image by length, content-length = %d", content_length );
            }
            else
            {
              while ( !content_length )
              {
								buffer_len = GetData();
                if ( buffer_len < 0 ) {
                  Error( "Unable to read content" );
                  return( -1 );
                }
								bytes += buffer_len;
                static RegExpr *content_expr = 0;
                if ( mode == MULTI_IMAGE )
                {
                  if ( !content_expr )
                  {
                    char content_pattern[256] = "";
                    snprintf( content_pattern, sizeof(content_pattern), "^(.+?)(?:\r?\n)*(?:--)?%s\r?\n", content_boundary );
                    content_expr = new RegExpr( content_pattern, PCRE_DOTALL );
                  }
                  if ( content_expr->Match( buffer, buffer.size() ) == 2 )
                  {
                    content_length = content_expr->MatchLength( 1 );
                    Debug( 3, "Got end of image by pattern, content-length = %d", content_length );
                  }
                }
              }
            }
            if ( mode == SINGLE_IMAGE ) {
              state = HEADER;
              Disconnect();
            } else {
              state = SUBHEADER;
            }
            Debug( 3, "Returning %d (%d) bytes of captured content", content_length, buffer.size() );
            return content_length;
          }
        case HEADERCONT :
        case SUBHEADERCONT :
          {
            // Ignore
            break;
          }
      }
    }
  } else
#endif // HAVE_LIBPCRE
  {
    static const char *http_match = "HTTP/";
    static const char *connection_match = "Connection:";
    static const char *content_length_match = "Content-length:";
    static const char *content_type_match = "Content-type:";
    static const char *boundary_match = "boundary=";
    static const char *authenticate_match = "WWW-Authenticate:";
    static int http_match_len = 0;
    static int connection_match_len = 0;
    static int content_length_match_len = 0;
    static int content_type_match_len = 0;
    static int boundary_match_len = 0;
    static int authenticate_match_len = 0;

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
    if ( !authenticate_match_len )
      authenticate_match_len = strlen( authenticate_match );

    static int n_headers;
    //static char *headers[32];

    static int n_subheaders;
    //static char *subheaders[32];

    static char *http_header;
    static char *connection_header;
    static char *content_length_header;
    static char *content_type_header;
    static char *boundary_header;
    static char *authenticate_header;
    static char subcontent_length_header[32];
    static char subcontent_type_header[64];

    static char http_version[16];
    static char status_code[16];
    static char status_mesg[256];
    static char connection_type[32];
    static int content_length;
    static char content_type[32];
    static char content_boundary[64];
    static int content_boundary_len;

    while ( !zm_terminate ) {
      switch( state ) {
        case HEADER :
          {
            n_headers = 0;
            http_header = 0;
            connection_header = 0;
            content_length_header = 0;
            content_type_header = 0;
            authenticate_header = 0;

            http_version[0] = '\0';
            status_code [0]= '\0';
            status_mesg [0]= '\0';
            connection_type [0]= '\0';
            content_length = 0;
            content_type[0] = '\0';
            content_boundary[0] = '\0';
            content_boundary_len = 0;
          }
        case HEADERCONT :
          {
						buffer_len = GetData();
            if ( buffer_len < 0 ) {
              Error("Unable to read header");
              return -1;
            }
						bytes += buffer_len;

            char *crlf = 0;
            char *header_ptr = (char *)buffer;
            int header_len = buffer.size();
            bool all_headers = false;

            while( true ) {
              int crlf_len = memspn(header_ptr, "\r\n", header_len);
              if ( n_headers ) {
                if ( (crlf_len == 2 && !strncmp( header_ptr, "\n\n", crlf_len )) || (crlf_len == 4 && !strncmp( header_ptr, "\r\n\r\n", crlf_len )) ) {
									Debug(3, "Have double linefeed, done headers");
                  *header_ptr = '\0';
                  header_ptr += crlf_len;
                  header_len -= buffer.consume( header_ptr-(char *)buffer );
                  all_headers = true;
                  break;
                }
              }
              if ( crlf_len ) {
                if ( header_len == crlf_len ) {
                  break;
                } else {
                  *header_ptr = '\0';
                  header_ptr += crlf_len;
                  header_len -= buffer.consume( header_ptr-(char *)buffer );
                }
              }

              Debug( 6, "%s", header_ptr );
              if ( (crlf = mempbrk( header_ptr, "\r\n", header_len )) ) {
                //headers[n_headers++] = header_ptr;
                n_headers++;

                if ( !http_header && (strncasecmp( header_ptr, http_match, http_match_len ) == 0) ) {
                  http_header = header_ptr+http_match_len;
                  Debug( 6, "Got http header '%s'", header_ptr );
                } else if ( !connection_header && (strncasecmp( header_ptr, connection_match, connection_match_len) == 0) ) {
                  connection_header = header_ptr+connection_match_len;
                  Debug( 6, "Got connection header '%s'", header_ptr );
                } else if ( !content_length_header && (strncasecmp( header_ptr, content_length_match, content_length_match_len) == 0) ) {
                  content_length_header = header_ptr+content_length_match_len;
                  Debug( 6, "Got content length header '%s'", header_ptr );
                } else if ( !authenticate_header && (strncasecmp( header_ptr, authenticate_match, authenticate_match_len) == 0) ) {
                  authenticate_header = header_ptr;
                  Debug( 6, "Got authenticate header '%s'", header_ptr );
                } else if ( !content_type_header && (strncasecmp( header_ptr, content_type_match, content_type_match_len) == 0) ) {
                  content_type_header = header_ptr+content_type_match_len;
                  Debug( 6, "Got content type header '%s'", header_ptr );
                } else {
                  Debug( 6, "Got ignored header '%s'", header_ptr );
                }
                header_ptr = crlf;
                header_len -= buffer.consume( header_ptr-(char *)buffer );
              } else {
                // No end of line found
                break;
              }
            } // end while search for headers

            if ( all_headers ) {
              char *start_ptr, *end_ptr;

              if ( !http_header ) {
                Error( "Unable to extract HTTP status from header" );
                return( -1 );
              }

              start_ptr = http_header;
              end_ptr = start_ptr+strspn( start_ptr, "10." );

              // FIXME Why are we memsetting every time?  Can we not do it once?
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

              if ( status == 401 ) {
                if ( mNeedAuth ) {
                  Error( "Failed authentication: " );
                  return( -1 );
                }
                if ( ! authenticate_header ) {
                  Error( "Failed authentication, but don't have an authentication header: " );
                  return( -1 );
                }
                mNeedAuth = true;
                std::string Header = authenticate_header;
                Debug(2, "Checking for digest auth in %s", authenticate_header );

                mAuthenticator->checkAuthResponse(Header);
                if ( mAuthenticator->auth_method() == zm::AUTH_DIGEST ) {
                  Debug( 2, "Need Digest Authentication" );
                  request = stringtf( "GET %s HTTP/%s\r\n", path.c_str(), config.http_version );
                  request += stringtf( "User-Agent: %s/%s\r\n", config.http_ua, ZM_VERSION );
                  request += stringtf( "Host: %s\r\n", host.c_str());
                  if ( strcmp( config.http_version, "1.0" ) == 0 )
                    request += stringtf( "Connection: Keep-Alive\r\n" );
                  request += mAuthenticator->getAuthHeader( "GET", path.c_str() );
                  request += "\r\n";

                  Debug( 2, "New request header: %s", request.c_str() );
                  return( 0 );
                } else {
                  Debug( 2, "Need some other kind of Authentication" );
                }
              } else if ( status < 200 || status > 299 ) {
                Error( "Invalid response status %s: %s", status_code, status_mesg );
                return( -1 );
              }
              Debug( 3, "Got status '%d' (%s), http version %s", status, status_mesg, http_version );

              if ( connection_header ) {
                memset( connection_type, 0, sizeof(connection_type) );
                start_ptr = connection_header + strspn( connection_header, " " );
                // FIXME Should we not use strncpy?
                strcpy( connection_type, start_ptr );
                Debug( 3, "Got connection '%s'", connection_type );
              }
              if ( content_length_header ) {
                start_ptr = content_length_header + strspn( content_length_header, " " );
                content_length = atoi( start_ptr );
                Debug( 3, "Got content length '%d'", content_length );
              }
              if ( content_type_header ) {
                memset( content_type, 0, sizeof(content_type) );
                start_ptr = content_type_header + strspn( content_type_header, " " );
                if ( (end_ptr = strchr( start_ptr, ';' )) ) {
                  strncpy( content_type, start_ptr, end_ptr-start_ptr );
                  Debug( 3, "Got content type '%s'", content_type );

                  start_ptr = end_ptr + strspn( end_ptr, "; " );

                  if ( strncasecmp( start_ptr, boundary_match, boundary_match_len ) == 0 ) {
                    start_ptr += boundary_match_len;
                    start_ptr += strspn( start_ptr, "-" );
                    content_boundary_len = sprintf( content_boundary, "--%s", start_ptr );
                    Debug( 3, "Got content boundary '%s'", content_boundary );
                  } else {
                    Error( "No content boundary found in header '%s'", content_type_header );
                  }
                } else {
                  strcpy( content_type, start_ptr );
                  Debug( 3, "Got content type '%s'", content_type );
                }
              } // end if content_type_header

              if ( !strcasecmp( content_type, "image/jpeg" ) || !strcasecmp( content_type, "image/jpg" ) ) {
                // Single image
                mode = SINGLE_IMAGE;
                format = JPEG;
                state = CONTENT;
              } else if ( !strcasecmp( content_type, "image/x-rgb" ) ) {
                // Single image
                mode = SINGLE_IMAGE;
                format = X_RGB;
                state = CONTENT;
              } else if ( !strcasecmp( content_type, "image/x-rgbz" ) ) {
                // Single image
                mode = SINGLE_IMAGE;
                format = X_RGBZ;
                state = CONTENT;
              } else if ( !strcasecmp( content_type, "multipart/x-mixed-replace" ) ) {
                // Image stream, so start processing
                if ( !content_boundary[0] ) {
                  Error( "No content boundary found in header '%s'", content_type_header );
                  return( -1 );
                }
                mode = MULTI_IMAGE;
                state = SUBHEADER;
              }
              //else if ( !strcasecmp( content_type, "video/mpeg" ) || !strcasecmp( content_type, "video/mpg" ) )
              //{
              //// MPEG stream, coming soon!
              //}
              else {
                Error( "Unrecognised content type '%s'", content_type );
                return( -1 );
              }
            } else {
              Debug(3, "Unable to extract entire header from stream, continuing");
              state = HEADERCONT;
              //return( -1 );
            } // end if all_headers
            break;
          }
        case SUBHEADER :
          {
            n_subheaders = 0;
            boundary_header = 0;
            subcontent_length_header[0] = '\0';
            subcontent_type_header[0] = '\0';
            content_length = 0;
            content_type[0] = '\0';
          }
        case SUBHEADERCONT :
          {
            char *crlf = 0;
            char *subheader_ptr = (char *)buffer;
            int subheader_len = buffer.size();
            bool all_headers = false;

            while( true ) {
              int crlf_len = memspn( subheader_ptr, "\r\n", subheader_len );
              if ( n_subheaders ) {
                if ( (crlf_len == 2 && !strncmp( subheader_ptr, "\n\n", crlf_len )) || (crlf_len == 4 && !strncmp( subheader_ptr, "\r\n\r\n", crlf_len )) ) {
                  *subheader_ptr = '\0';
                  subheader_ptr += crlf_len;
                  subheader_len -= buffer.consume( subheader_ptr-(char *)buffer );
                  all_headers = true;
                  break;
                }
              }
              if ( crlf_len ) {
                if ( subheader_len == crlf_len ) {
                  break;
                } else {
                  *subheader_ptr = '\0';
                  subheader_ptr += crlf_len;
                  subheader_len -= buffer.consume( subheader_ptr-(char *)buffer );
                }
              }

              Debug( 6, "%d: %s", subheader_len, subheader_ptr );

              if ( (crlf = mempbrk( subheader_ptr, "\r\n", subheader_len )) ) {
                //subheaders[n_subheaders++] = subheader_ptr;
                n_subheaders++;

                if ( !boundary_header && (strncasecmp( subheader_ptr, content_boundary, content_boundary_len ) == 0) ) {
                  boundary_header = subheader_ptr;
                  Debug( 4, "Got boundary subheader '%s'", subheader_ptr );
                } else if ( !subcontent_length_header[0] && (strncasecmp( subheader_ptr, content_length_match, content_length_match_len) == 0) ) {
                  strncpy( subcontent_length_header, subheader_ptr+content_length_match_len, sizeof(subcontent_length_header) );
                  *(subcontent_length_header+strcspn( subcontent_length_header, "\r\n" )) = '\0';
                  Debug( 4, "Got content length subheader '%s'", subcontent_length_header );
                } else if ( !subcontent_type_header[0] && (strncasecmp( subheader_ptr, content_type_match, content_type_match_len) == 0) ) {
                  strncpy( subcontent_type_header, subheader_ptr+content_type_match_len, sizeof(subcontent_type_header) );
                  *(subcontent_type_header+strcspn( subcontent_type_header, "\r\n" )) = '\0';
                  Debug( 4, "Got content type subheader '%s'", subcontent_type_header );
                } else {
                  Debug( 6, "Got ignored subheader '%s' found", subheader_ptr );
                }
                subheader_ptr = crlf;
                subheader_len -= buffer.consume( subheader_ptr-(char *)buffer );
              } else {
                // No line end found
                break;
              }
            }

            if ( all_headers && boundary_header ) {
              char *start_ptr/*, *end_ptr*/;

              Debug( 3, "Got boundary '%s'", boundary_header );

              if ( subcontent_length_header[0] ) {
                start_ptr = subcontent_length_header + strspn( subcontent_length_header, " " );
                content_length = atoi( start_ptr );
                Debug( 3, "Got subcontent length '%d'", content_length );
              }
              if ( subcontent_type_header[0] ) {
                memset( content_type, 0, sizeof(content_type) );
                start_ptr = subcontent_type_header + strspn( subcontent_type_header, " " );
                strcpy( content_type, start_ptr );
                Debug( 3, "Got subcontent type '%s'", content_type );
              }
              state = CONTENT;
            } else {
              Debug( 3, "Unable to extract subheader from stream, retrying" );
							buffer_len = GetData();
              if ( buffer_len < 0 ) {
                Error( "Unable to read subheader" );
                return( -1 );
              }
							bytes += buffer_len;
              state = SUBHEADERCONT;
            }
            break;
          }
        case CONTENT : {

            // if content_type is something like image/jpeg;size=, this will strip the ;size=
            char * semicolon = strchr( content_type, ';' );
            if ( semicolon ) {
              *semicolon = '\0';
            }

            if ( !strcasecmp( content_type, "image/jpeg" ) || !strcasecmp( content_type, "image/jpg" ) ) {
              format = JPEG;
            } else if ( !strcasecmp( content_type, "image/x-rgb" ) ) {
              format = X_RGB;
            } else if ( !strcasecmp( content_type, "image/x-rgbz" ) ) {
              format = X_RGBZ;
            } else {
              Error( "Found unsupported content type '%s'", content_type );
              return( -1 );
            }

            // This is an early test for jpeg content, so we can bail early
            if ( format == JPEG && buffer.size() >= 2 ) {
              if ( buffer[0] != 0xff || buffer[1] != 0xd8 ) {
                Error( "Found bogus jpeg header '%02x%02x'", buffer[0], buffer[1] );
                return( -1 );
              }
            }

            if ( content_length ) {
              while ( ( (long)buffer.size() < content_length ) && ! zm_terminate ) {
								Debug(4, "getting more data");
								int bytes_read = GetData();
                if ( bytes_read < 0 ) {
                  Error("Unable to read content");
                  return -1;
                }
								bytes += bytes_read;
              }
              Debug( 3, "Got end of image by length, content-length = %d", content_length );
            } else {
              // Read until we find the end of image or the stream closes.
              while ( !content_length && !zm_terminate ) {
								Debug(4, "!content_length, ReadData");
                buffer_len = ReadData( buffer );
                if ( buffer_len < 0 ) {
                  Error( "Unable to read content" );
                  return( -1 );
                }
								bytes += buffer_len;
                int buffer_size = buffer.size();
                if ( buffer_len ) {
                  // Got some data

                  if ( mode == MULTI_IMAGE ) {
                    // Look for the boundary marker, determine content length using it's position
                    if ( char *start_ptr = (char *)memstr( (char *)buffer, "\r\n--", buffer_size ) ) {
                      content_length = start_ptr - (char *)buffer;
                      Debug( 2, "Got end of image by pattern (crlf--), content-length = %d", content_length );
                    } else {
                      Debug( 2, "Did not find end of image by patten (crlf--) yet, content-length = %d", content_length );
                    }
                  } // end if MULTI_IMAGE
                } else {
                  content_length = buffer_size;
                  Debug( 2, "Got end of image by closure, content-length = %d", content_length );
                  if ( mode == SINGLE_IMAGE ) {
                    char *end_ptr = (char *)buffer+buffer_size;

                    // strip off any last line feeds
                    while( *end_ptr == '\r' || *end_ptr == '\n' ) {
                      content_length--;
                      end_ptr--;
                    }

                    if ( end_ptr != ((char *)buffer+buffer_size) ) {
                      Debug( 2, "Trimmed end of image, new content-length = %d", content_length );
                    }
                  } // end if SINGLE_IMAGE
                } // end if read some data
              } // end while ! content_length
            } // end if content_length

            if ( mode == SINGLE_IMAGE ) {
              state = HEADER;
              Disconnect();
            } else {
              state = SUBHEADER;
            }

            if ( format == JPEG && buffer.size() >= 2 ) {
              if ( buffer[0] != 0xff || buffer[1] != 0xd8 ) {
                Error( "Found bogus jpeg header '%02x%02x'", buffer[0], buffer[1] );
                return( -1 );
              }
            }

            Debug( 3, "Returning %d bytes, buffer size: (%d) bytes of captured content", content_length, buffer.size() );
            return( content_length );
          } // end cast CONTENT
      } // end switch
    }
  }
  return( 0 );
}

int RemoteCameraHttp::PreCapture() {
  if ( sd < 0 ) {
    Connect();
    if ( sd < 0 ) {
      return -1;
    }
    mode = SINGLE_IMAGE;
    buffer.clear();
  }
  if ( mode == SINGLE_IMAGE ) {
    if ( SendRequest() < 0 ) {
      Error("Unable to send request");
      Disconnect();
      return -1;
    }
  }
  return 0;
}

int RemoteCameraHttp::Capture( Image &image ) {
  int content_length = GetResponse();
  if ( content_length == 0 ) {
    Warning( "Unable to capture image, retrying" );
    return 0;
  }
  if ( content_length < 0 ) {
    Error( "Unable to get response, disconnecting" );
    Disconnect();
    return -1;
  }
  switch( format ) {
    case JPEG :
      {
        if ( !image.DecodeJpeg( buffer.extract( content_length ), content_length, colours, subpixelorder ) ) {
          Error( "Unable to decode jpeg" );
          Disconnect();
          return -1;
        }
        break;
      }
    case X_RGB :
      {
        if ( content_length != (long)image.Size() ) {
          Error( "Image length mismatch, expected %d bytes, content length was %d", image.Size(), content_length );
          Disconnect();
          return -1;
        }
        image.Assign( width, height, colours, subpixelorder, buffer, imagesize );
        break;
      }
    case X_RGBZ :
      {
        if ( !image.Unzip( buffer.extract( content_length ), content_length ) ) {
          Error( "Unable to unzip RGB image" );
          Disconnect();
          return -1;
        }
        image.Assign( width, height, colours, subpixelorder, buffer, imagesize );
        break;
      }
    default :
      {
        Error( "Unexpected image format encountered" );
        Disconnect();
        return -1;
      }
  }
  return 1;
}

int RemoteCameraHttp::PostCapture() {
  return 0;
}
