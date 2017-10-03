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

#include "zm_remote_camera_nvsocket.h"

#include "zm_mem_utils.h"

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

RemoteCameraNVSocket::RemoteCameraNVSocket(
  unsigned int p_monitor_id,
  const std::string &p_host,
  const std::string &p_port,
  const std::string &p_path,
  int p_width,
  int p_height,
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

  if ( capture ) {
    Initialise();
  }
}

RemoteCameraNVSocket::~RemoteCameraNVSocket() {
  if ( capture ) {
    Terminate();
  }
}

void RemoteCameraNVSocket::Initialise() {
  RemoteCamera::Initialise();

  if ( !timeout.tv_sec ) {
    timeout.tv_sec = config.http_timeout/1000; 
    timeout.tv_usec = (config.http_timeout%1000)*1000;
  }

  int max_size = width*height*colours;

  buffer.size( max_size );

  mode = SINGLE_IMAGE;
  format = UNDEF;
  state = HEADER;
}

int RemoteCameraNVSocket::Connect() {
  //struct addrinfo *p;
struct sockaddr_in servaddr;
    bzero( &servaddr, sizeof(servaddr));
    servaddr.sin_family      = AF_INET;
    servaddr.sin_addr.s_addr = htons(INADDR_ANY);
    servaddr.sin_port        = htons(atoi(port.c_str()));


    sd = socket(AF_INET, SOCK_STREAM, 0);
  //for(p = hp; p != NULL; p = p->ai_next) {
    //sd = socket( p->ai_family, p->ai_socktype, p->ai_protocol );
    if ( sd < 0 ) {
      Warning("Can't create socket: %s", strerror(errno) );
      //continue;
	return -1;
    }

    //if ( connect( sd, p->ai_addr, p->ai_addrlen ) < 0 ) {
    if ( connect( sd, (struct sockaddr *)&servaddr , sizeof(servaddr) ) < 0 ) {
      close(sd);
      sd = -1;

      Warning("Can't connect to socket mid: %d : %s", monitor_id, strerror(errno) );
	return -1;
      //continue;
    //}
    /* If we got here, we must have connected successfully */
    //break;
  }

  //if ( p == NULL ) {
    //Error("Unable to connect to the remote camera, aborting");
    //return( -1 );
  //}

  Debug( 3, "Connected to host, socket = %d", sd );
  return( sd );
}

int RemoteCameraNVSocket::Disconnect() {
  close( sd );
  sd = -1;
  Debug( 3, "Disconnected from host" );
  return( 0 );
}

int RemoteCameraNVSocket::SendRequest( std::string request ) {
  Debug( 2, "Sending request: %s", request.c_str() );
  if ( write( sd, request.data(), request.length() ) < 0 ) {
    Error( "Can't write: %s", strerror(errno) );
    Disconnect();
    return( -1 );
  }
  Debug( 3, "Request sent" );
  return( 0 );
}

/* Return codes are as follows:
 * -1 means there was an error
 * 0 means no bytes were returned but there wasn't actually an error.
 * > 0 is the # of bytes read.
 */

int RemoteCameraNVSocket::ReadData( Buffer &buffer, unsigned int bytes_expected ) {
  fd_set rfds;
  FD_ZERO(&rfds);
  FD_SET(sd, &rfds);

  struct timeval temp_timeout = timeout;

  int n_found = select(sd+1, &rfds, NULL, NULL, &temp_timeout);
  if ( n_found == 0 ) {
    Debug( 4, "Select timed out timeout was %d secs %d usecs", temp_timeout.tv_sec, temp_timeout.tv_usec );
    int error = 0;
    socklen_t len = sizeof(error);
    int retval = getsockopt(sd, SOL_SOCKET, SO_ERROR, &error, &len);
    if ( retval != 0 ) {
      Debug(1, "error getting socket error code %s", strerror(retval));
    }
    if ( error != 0 ) {
      return -1;
    }
    // Why are we disconnecting?  It's just a timeout, meaning that data wasn't available.
    //Disconnect();
    return 0;
  } else if ( n_found < 0 ) {
    Error("Select error: %s", strerror(errno));
    return -1;
  }

  unsigned int total_bytes_to_read = 0;

  if ( bytes_expected ) {
    total_bytes_to_read = bytes_expected;
  } else {
    if ( ioctl( sd, FIONREAD, &total_bytes_to_read ) < 0 ) {
      Error( "Can't ioctl(): %s", strerror(errno) );
      return( -1 );
    }

    if ( total_bytes_to_read == 0 ) {
      if ( mode == SINGLE_IMAGE ) {
        int error = 0;
        socklen_t len = sizeof (error);
        int retval = getsockopt( sd, SOL_SOCKET, SO_ERROR, &error, &len );
        if(retval != 0 ) {
          Debug( 1, "error getting socket error code %s", strerror(retval) );
        }
        if (error != 0) {
          return -1;
        }
        // Case where we are grabbing a single jpg, but no content-length was given, so the expectation is that we read until close.
		    return( 0 );
      }
      // If socket is closed locally, then select will fail, but if it is closed remotely
      // then we have an exception on our socket.. but no data.
      Debug( 3, "Socket closed remotely" );
      //Disconnect(); // Disconnect is done outside of ReadData now.
      return( -1 );
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

  Debug( 4, buffer );

  return( total_bytes_read );
}

int RemoteCameraNVSocket::PreCapture() {
  if ( sd < 0 ) {
    Connect();
    if ( sd < 0 ) {
      Error( "Unable to connect to camera" );
      return( -1 );
    }
    mode = SINGLE_IMAGE;
    buffer.clear();
  }
struct image_def {
           uint16_t  width;
           uint16_t height;
           uint16_t   type;
};
struct image_def image_def;

  if ( SendRequest("GetImageParams") < 0 ) {
    Error( "Unable to send request" );
    Disconnect();
    return -1;
  }
  if ( Read(sd, (char *) &image_def, sizeof(image_def)) != sizeof(image_def) ) {
    Error("Unable to GetImageParams");
    Disconnect();
    return -1;
  }
  if ( image_def.width != width || image_def.height != height ) {
    Error("Incorrect width and height set.  %dx%d != %dx%d", width, height, image_def.width, image_def.height );
    Disconnect();
    return -1;
  }

  return 0;
}

int RemoteCameraNVSocket::Capture( Image &image ) {
  if ( SendRequest("GetNextImage") < 0 ) {
    Warning( "Unable to capture image, retrying" );
    return( 1 );
  }
  if ( Read( sd, buffer, imagesize ) < imagesize ) {
    Warning( "Unable to capture image, retrying" );
    return( 1 );
  }

  image.Assign( width, height, colours, subpixelorder, buffer, imagesize );
  return( 0 );
}

int RemoteCameraNVSocket::PostCapture()
{
  return( 0 );
}
