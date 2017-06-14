//
// ZoneMinder Monitor Class Implementation, $Date$, $Revision$
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

#include "zm.h"
#include "zm_db.h"
#include "zm_time.h"
#include "zm_mpeg.h"
#include "zm_signal.h"
#include "zm_monitor.h"
#include "zm_monitorstream.h"
#include <arpa/inet.h>
#include <glob.h>

bool MonitorStream::checkSwapPath( const char *path, bool create_path ) {

  struct stat stat_buf;
  if ( stat( path, &stat_buf ) < 0 ) {
    if ( create_path && errno == ENOENT ) {
      Debug( 3, "Swap path '%s' missing, creating", path );
      if ( mkdir( path, 0755 ) ) {
        Error( "Can't mkdir %s: %s", path, strerror(errno));
        return( false );
      }
      if ( stat( path, &stat_buf ) < 0 ) {
        Error( "Can't stat '%s': %s", path, strerror(errno) );
        return( false );
      }
    } else {
      Error( "Can't stat '%s': %s", path, strerror(errno) );
      return( false );
    }
  }
  if ( !S_ISDIR(stat_buf.st_mode) ) {
    Error( "Swap image path '%s' is not a directory", path );
    return( false );
  }

  uid_t uid = getuid();
  gid_t gid = getgid();

  mode_t mask = 0;
  if ( uid == stat_buf.st_uid ) {
    // If we are the owner
    mask = 00700;
  } else if ( gid == stat_buf.st_gid ) {
    // If we are in the owner group
    mask = 00070;
  } else {
    // We are neither the owner nor in the group
    mask = 00007;
  }

  if ( (stat_buf.st_mode & mask) != mask ) {
    Error( "Insufficient permissions on swap image path '%s'", path );
    return( false );
  }
  return( true );
} // end bool MonitorStream::checkSwapPath( const char *path, bool create_path ) 

void MonitorStream::processCommand( const CmdMsg *msg ) {
  Debug( 2, "Got message, type %d, msg %d", msg->msg_type, msg->msg_data[0] );
  // Check for incoming command
  switch( (MsgCommand)msg->msg_data[0] ) {
    case CMD_PAUSE :
    {
      Debug( 1, "Got PAUSE command" );

      // Set paused flag
      paused = true;
      // Set delayed flag
      delayed = true;
      last_frame_sent = TV_2_FLOAT( now );
      break;
    }
    case CMD_PLAY :
    {
      Debug( 1, "Got PLAY command" );
      if ( paused ) {
        // Clear paused flag
        paused = false;
        // Set delayed_play flag
        delayed = true;
      }
      replay_rate = ZM_RATE_BASE;
      break;
    }
    case CMD_VARPLAY :
    {
      Debug( 1, "Got VARPLAY command" );
      if ( paused ) {
        // Clear paused flag
        paused = false;
        // Set delayed_play flag
        delayed = true;
      }
      replay_rate = ntohs(((unsigned char)msg->msg_data[2]<<8)|(unsigned char)msg->msg_data[1])-32768;
      break;
    }
    case CMD_STOP :
    {
      Debug( 1, "Got STOP command" );

      // Clear paused flag
      paused = false;
      // Clear delayed_play flag
      delayed = false;
      break;
    }
    case CMD_FASTFWD :
    {
      Debug( 1, "Got FAST FWD command" );
      if ( paused ) {
        // Clear paused flag
        paused = false;
        // Set delayed_play flag
        delayed = true;
      }
      // Set play rate
      switch ( replay_rate )
      {
        case 2 * ZM_RATE_BASE :
          replay_rate = 5 * ZM_RATE_BASE;
          break;
        case 5 * ZM_RATE_BASE :
          replay_rate = 10 * ZM_RATE_BASE;
          break;
        case 10 * ZM_RATE_BASE :
          replay_rate = 25 * ZM_RATE_BASE;
          break;
        case 25 * ZM_RATE_BASE :
        case 50 * ZM_RATE_BASE :
          replay_rate = 50 * ZM_RATE_BASE;
          break;
        default :
          replay_rate = 2 * ZM_RATE_BASE;
          break;
      }
      break;
    }
    case CMD_SLOWFWD :
    {
      Debug( 1, "Got SLOW FWD command" );
      // Set paused flag
      paused = true;
      // Set delayed flag
      delayed = true;
      // Set play rate
      replay_rate = ZM_RATE_BASE;
      // Set step
      step = 1;
      break;
    }
    case CMD_SLOWREV :
    {
      Debug( 1, "Got SLOW REV command" );
      // Set paused flag
      paused = true;
      // Set delayed flag
      delayed = true;
      // Set play rate
      replay_rate = ZM_RATE_BASE;
      // Set step
      step = -1;
      break;
    }
    case CMD_FASTREV :
    {
      Debug( 1, "Got FAST REV command" );
      if ( paused ) {
        // Clear paused flag
        paused = false;
        // Set delayed_play flag
        delayed = true;
      }
      // Set play rate
      switch ( replay_rate ) {
        case -2 * ZM_RATE_BASE :
          replay_rate = -5 * ZM_RATE_BASE;
          break;
        case -5 * ZM_RATE_BASE :
          replay_rate = -10 * ZM_RATE_BASE;
          break;
        case -10 * ZM_RATE_BASE :
          replay_rate = -25 * ZM_RATE_BASE;
          break;
        case -25 * ZM_RATE_BASE :
        case -50 * ZM_RATE_BASE :
          replay_rate = -50 * ZM_RATE_BASE;
          break;
        default :
          replay_rate = -2 * ZM_RATE_BASE;
          break;
      }
      break;
    }
    case CMD_ZOOMIN :
    {
      x = ((unsigned char)msg->msg_data[1]<<8)|(unsigned char)msg->msg_data[2];
      y = ((unsigned char)msg->msg_data[3]<<8)|(unsigned char)msg->msg_data[4];
      Debug( 1, "Got ZOOM IN command, to %d,%d", x, y );
      switch ( zoom ) {
        case 100:
          zoom = 150;
          break;
        case 150:
          zoom = 200;
          break;
        case 200:
          zoom = 300;
          break;
        case 300:
          zoom = 400;
          break;
        case 400:
        default :
          zoom = 500;
          break;
      }
      break;
    }
    case CMD_ZOOMOUT :
    {
      Debug( 1, "Got ZOOM OUT command" );
      switch ( zoom ) {
        case 500:
          zoom = 400;
          break;
        case 400:
          zoom = 300;
          break;
        case 300:
          zoom = 200;
          break;
        case 200:
          zoom = 150;
          break;
        case 150:
        default :
          zoom = 100;
          break;
      }
      break;
    }
    case CMD_PAN :
    {
      x = ((unsigned char)msg->msg_data[1]<<8)|(unsigned char)msg->msg_data[2];
      y = ((unsigned char)msg->msg_data[3]<<8)|(unsigned char)msg->msg_data[4];
      Debug( 1, "Got PAN command, to %d,%d", x, y );
      break;
    }
    case CMD_SCALE :
    {
      scale = ((unsigned char)msg->msg_data[1]<<8)|(unsigned char)msg->msg_data[2];
      Debug( 1, "Got SCALE command, to %d", scale );
      break;
    }
  case CMD_QUIT :
    {
      Info ("User initiated exit - CMD_QUIT");
      break;
    }
    case CMD_QUERY :
    {
      Debug( 1, "Got QUERY command, sending STATUS" );
      break;
    }
    default :
    {
      Error( "Got unexpected command %d", msg->msg_data[0] );
      break;
    }
  }

  struct {
    int id;
    int state;
    double fps;
    int buffer_level;
    int rate;
    double delay;
    int zoom;
    bool delayed;
    bool paused;
    bool enabled;
    bool forced;
  } status_data;

  status_data.id = monitor->Id();
  status_data.fps = monitor->GetFPS();
  status_data.state = monitor->shared_data->state;
  if ( playback_buffer > 0 )
    status_data.buffer_level = (MOD_ADD( (temp_write_index-temp_read_index), 0, temp_image_buffer_count )*100)/temp_image_buffer_count;
  else
    status_data.buffer_level = 0;
  status_data.delayed = delayed;
  status_data.paused = paused;
  status_data.rate = replay_rate;
  status_data.delay = TV_2_FLOAT( now ) - TV_2_FLOAT( last_frame_timestamp );
  status_data.zoom = zoom;
  //status_data.enabled = monitor->shared_data->active;
  status_data.enabled = monitor->trigger_data->trigger_state!=Monitor::TRIGGER_OFF;
  status_data.forced = monitor->trigger_data->trigger_state==Monitor::TRIGGER_ON;
  Debug( 2, "L:%d, D:%d, P:%d, R:%d, d:%.3f, Z:%d, E:%d F:%d", 
    status_data.buffer_level,
    status_data.delayed,
    status_data.paused,
    status_data.rate,
    status_data.delay,
    status_data.zoom,
    status_data.enabled,
    status_data.forced
  );

  DataMsg status_msg;
  status_msg.msg_type = MSG_DATA_WATCH;
  memcpy( &status_msg.msg_data, &status_data, sizeof(status_data) );
  int nbytes = 0;
  if ( (nbytes = sendto( sd, &status_msg, sizeof(status_msg), MSG_DONTWAIT, (sockaddr *)&rem_addr, sizeof(rem_addr) )) < 0 ) {
    //if ( errno != EAGAIN )
    {
      Error( "Can't sendto on sd %d: %s", sd, strerror(errno) );
      //exit( -1 );
    }
  }

  // quit after sending a status, if this was a quit request
  if ((MsgCommand)msg->msg_data[0]==CMD_QUIT)
  exit(0);

  updateFrameRate( monitor->GetFPS() );
} // end void MonitorStream::processCommand( const CmdMsg *msg )

bool MonitorStream::sendFrame( const char *filepath, struct timeval *timestamp ) {
  bool send_raw = ((scale>=ZM_SCALE_BASE)&&(zoom==ZM_SCALE_BASE));

  if ( type != STREAM_JPEG )
    send_raw = false;
  if ( !config.timestamp_on_capture && timestamp )
    send_raw = false;

  if ( !send_raw ) {
    Image temp_image( filepath );

    return( sendFrame( &temp_image, timestamp ) );
  } else {
    int img_buffer_size = 0;
    static unsigned char img_buffer[ZM_MAX_IMAGE_SIZE];

    FILE *fdj = NULL;
    if ( (fdj = fopen( filepath, "r" )) ) {
      img_buffer_size = fread( img_buffer, 1, sizeof(img_buffer), fdj );
      fclose( fdj );
    } else {
      Error( "Can't open %s: %s", filepath, strerror(errno) );
      return( false );
    }

    // Calculate how long it takes to actually send the frame
    struct timeval frameStartTime;
    gettimeofday( &frameStartTime, NULL );
    
    fprintf( stdout, "--ZoneMinderFrame\r\n" );
    fprintf( stdout, "Content-Length: %d\r\n", img_buffer_size );
    fprintf( stdout, "Content-Type: image/jpeg\r\n\r\n" );
    if ( fwrite( img_buffer, img_buffer_size, 1, stdout ) != 1 ) {
      if ( ! zm_terminate )
        Error( "Unable to send stream frame: %s", strerror(errno) );
      return( false );
    }
    fprintf( stdout, "\r\n\r\n" );
    fflush( stdout );

    struct timeval frameEndTime;
    gettimeofday( &frameEndTime, NULL );

    int frameSendTime = tvDiffMsec( frameStartTime, frameEndTime );
    if ( frameSendTime > 1000/maxfps ) {
      maxfps /= 2;
      Error( "Frame send time %d msec too slow, throttling maxfps to %.2f", frameSendTime, maxfps );
    }

    last_frame_sent = TV_2_FLOAT( now );

    return( true );
  }
  return( false );
}

bool MonitorStream::sendFrame( Image *image, struct timeval *timestamp ) {
  Image *send_image = prepareImage( image );
  if ( !config.timestamp_on_capture && timestamp )
    monitor->TimestampImage( send_image, timestamp );

#if HAVE_LIBAVCODEC
  if ( type == STREAM_MPEG ) {
    if ( !vid_stream ) {
      vid_stream = new VideoStream( "pipe:", format, bitrate, effective_fps, send_image->Colours(), send_image->SubpixelOrder(), send_image->Width(), send_image->Height() );
      fprintf( stdout, "Content-type: %s\r\n\r\n", vid_stream->MimeType() );
      vid_stream->OpenStream();
    }
    static struct timeval base_time;
    struct DeltaTimeval delta_time;
    if ( !frame_count )
      base_time = *timestamp;
    DELTA_TIMEVAL( delta_time, *timestamp, base_time, DT_PREC_3 );
    /* double pts = */ vid_stream->EncodeFrame( send_image->Buffer(), send_image->Size(), config.mpeg_timed_frames, delta_time.delta );
  } else
#endif // HAVE_LIBAVCODEC
  {
    static unsigned char temp_img_buffer[ZM_MAX_IMAGE_SIZE];

    int img_buffer_size = 0;
    unsigned char *img_buffer = temp_img_buffer;

    // Calculate how long it takes to actually send the frame
    struct timeval frameStartTime;
    gettimeofday( &frameStartTime, NULL );
    
    fprintf( stdout, "--ZoneMinderFrame\r\n" );
    switch( type ) {
      case STREAM_JPEG :
        send_image->EncodeJpeg( img_buffer, &img_buffer_size );
        fprintf( stdout, "Content-Type: image/jpeg\r\n" );
        break;
      case STREAM_RAW :
        fprintf( stdout, "Content-Type: image/x-rgb\r\n" );
        img_buffer = (uint8_t*)send_image->Buffer();
        img_buffer_size = send_image->Size();
        break;
      case STREAM_ZIP :
        fprintf( stdout, "Content-Type: image/x-rgbz\r\n" );
        unsigned long zip_buffer_size;
        send_image->Zip( img_buffer, &zip_buffer_size );
        img_buffer_size = zip_buffer_size;
        break;
      default :
        Fatal( "Unexpected frame type %d", type );
        break;
    }
    fprintf( stdout, "Content-Length: %d\r\n\r\n", img_buffer_size );
    if ( fwrite( img_buffer, img_buffer_size, 1, stdout ) != 1 ) {
      if ( !zm_terminate )
        Error( "Unable to send stream frame: %s", strerror(errno) );
      return( false );
    }
    fprintf( stdout, "\r\n\r\n" );
    fflush( stdout );

    struct timeval frameEndTime;
    gettimeofday( &frameEndTime, NULL );

    int frameSendTime = tvDiffMsec( frameStartTime, frameEndTime );
    if ( frameSendTime > 1000/maxfps ) {
      maxfps /= 1.5;
      Error( "Frame send time %d msec too slow, throttling maxfps to %.2f", frameSendTime, maxfps );
    }
  }
  last_frame_sent = TV_2_FLOAT( now );
  return( true );
} // end bool MonitorStream::sendFrame( Image *image, struct timeval *timestamp )

void MonitorStream::runStream() {
  if ( type == STREAM_SINGLE ) {
    // Not yet migrated over to stream class
    SingleImage( scale );
    return;
  }

  openComms();

  checkInitialised();

  updateFrameRate( monitor->GetFPS() );

  if ( type == STREAM_JPEG )
    fprintf( stdout, "Content-Type: multipart/x-mixed-replace;boundary=ZoneMinderFrame\r\n\r\n" );

  int last_read_index = monitor->image_buffer_count;

  time_t stream_start_time;
  time( &stream_start_time );

  frame_count = 0;

  temp_image_buffer = 0;
  temp_image_buffer_count = playback_buffer;
  temp_read_index = temp_image_buffer_count;
  temp_write_index = temp_image_buffer_count;

  char *swap_path = 0;
  bool buffered_playback = false;

  // 15 is the max length for the swap path suffix, /zmswap-whatever, assuming max 6 digits for monitor id
  const int max_swap_len_suffix = 15; 

  int swap_path_length = staticConfig.PATH_SWAP.length() + 1; // +1 for NULL terminator
  int subfolder1_length = snprintf(NULL, 0, "/zmswap-m%d", monitor->Id() ) + 1;
  int subfolder2_length = snprintf(NULL, 0, "/zmswap-q%06d", connkey ) + 1;
  int total_swap_path_length = swap_path_length + subfolder1_length + subfolder2_length;

  if ( connkey && playback_buffer > 0 ) {

    if ( total_swap_path_length + max_swap_len_suffix > PATH_MAX ) {
      Error( "Swap Path is too long. %d > %d ", total_swap_path_length+max_swap_len_suffix, PATH_MAX );
    } else {
      swap_path = (char *)malloc( total_swap_path_length+max_swap_len_suffix );
      strncpy( swap_path, staticConfig.PATH_SWAP.c_str(), swap_path_length );

      Debug( 3, "Checking swap path folder: %s", swap_path );
      if ( checkSwapPath( swap_path, false ) ) {
        // Append the subfolder name /zmswap-m{monitor-id} to the end of swap_path
        int ndx = swap_path_length - 1; // Array index of the NULL terminator
        snprintf( &(swap_path[ndx]), subfolder1_length, "/zmswap-m%d", monitor->Id() );

        Debug( 4, "Checking swap path subfolder: %s", swap_path );
        if ( checkSwapPath( swap_path, true ) ) {
          // Append the subfolder name /zmswap-q{connection key} to the end of swap_path
          ndx = swap_path_length+subfolder1_length - 2; // Array index of the NULL terminator
          snprintf( &(swap_path[ndx]), subfolder2_length, "/zmswap-q%06d", connkey );

          Debug( 4, "Checking swap path subfolder: %s", swap_path );
          if ( checkSwapPath( swap_path, true ) ) {
            buffered_playback = true;
          }
        }
      }

      if ( !buffered_playback ) {
        Error( "Unable to validate swap image path, disabling buffered playback" );
      } else {
        Debug( 2, "Assigning temporary buffer" );
        temp_image_buffer = new SwapImage[temp_image_buffer_count];
        memset( temp_image_buffer, 0, sizeof(*temp_image_buffer)*temp_image_buffer_count );
        Debug( 2, "Assigned temporary buffer" );
      }
    }
  }

  float max_secs_since_last_sent_frame = 10.0; //should be > keep alive amount (5 secs)
  while ( !zm_terminate ) {
    bool got_command = false;
    if ( feof( stdout ) || ferror( stdout ) || !monitor->ShmValid() ) {
      break;
    }

    gettimeofday( &now, NULL );

    if ( connkey ) {
      while(checkCommandQueue()) {
        got_command = true;
      }
    }

    //bool frame_sent = false;
    if ( buffered_playback && delayed ) {
      if ( temp_read_index == temp_write_index ) {
        // Go back to live viewing
        Debug( 1, "Exceeded temporary streaming buffer" );
        // Clear paused flag
        paused = false;
        // Clear delayed_play flag
        delayed = false;
        replay_rate = ZM_RATE_BASE;
      } else {
        if ( !paused ) {
          int temp_index = MOD_ADD( temp_read_index, 0, temp_image_buffer_count );
          //Debug( 3, "tri: %d, ti: %d", temp_read_index, temp_index );
          SwapImage *swap_image = &temp_image_buffer[temp_index];

          if ( !swap_image->valid ) {
            paused = true;
            delayed = true;
            temp_read_index = MOD_ADD( temp_read_index, (replay_rate>=0?-1:1), temp_image_buffer_count );
          } else {
            //Debug( 3, "siT: %f, lfT: %f", TV_2_FLOAT( swap_image->timestamp ), TV_2_FLOAT( last_frame_timestamp ) );
            double expected_delta_time = ((TV_2_FLOAT( swap_image->timestamp ) - TV_2_FLOAT( last_frame_timestamp )) * ZM_RATE_BASE)/replay_rate;
            double actual_delta_time = TV_2_FLOAT( now ) - last_frame_sent;

            //Debug( 3, "eDT: %.3lf, aDT: %.3f, lFS:%.3f, NOW:%.3f", expected_delta_time, actual_delta_time, last_frame_sent, TV_2_FLOAT( now ) );
            // If the next frame is due
            if ( actual_delta_time > expected_delta_time ) {
              //Debug( 2, "eDT: %.3lf, aDT: %.3f", expected_delta_time, actual_delta_time );
              if ( temp_index%frame_mod == 0 ) {
                Debug( 2, "Sending delayed frame %d", temp_index );
                // Send the next frame
                if ( ! sendFrame( temp_image_buffer[temp_index].file_name, &temp_image_buffer[temp_index].timestamp ) )
                  zm_terminate = true;
                memcpy( &last_frame_timestamp, &(swap_image->timestamp), sizeof(last_frame_timestamp) );
                //frame_sent = true;
              }
              temp_read_index = MOD_ADD( temp_read_index, (replay_rate>0?1:-1), temp_image_buffer_count );
            }
          }
        } else if ( step != 0 ) {
          temp_read_index = MOD_ADD( temp_read_index, (step>0?1:-1), temp_image_buffer_count );

          SwapImage *swap_image = &temp_image_buffer[temp_read_index];

          // Send the next frame
          if ( !sendFrame( temp_image_buffer[temp_read_index].file_name, &temp_image_buffer[temp_read_index].timestamp ) )
            zm_terminate = true;
          memcpy( &last_frame_timestamp, &(swap_image->timestamp), sizeof(last_frame_timestamp) );
          //frame_sent = true;
          step = 0;
        } else {
          int temp_index = MOD_ADD( temp_read_index, 0, temp_image_buffer_count );

           double actual_delta_time = TV_2_FLOAT( now ) - last_frame_sent;
           if ( got_command || actual_delta_time > 5 ) {
            // Send keepalive
            Debug( 2, "Sending keepalive frame %d", temp_index );
            // Send the next frame
            if ( !sendFrame( temp_image_buffer[temp_index].file_name, &temp_image_buffer[temp_index].timestamp ) )
              zm_terminate = true;
            //frame_sent = true;
          }
        }
      }
      if ( temp_read_index == temp_write_index ) {
        // Go back to live viewing
        Warning( "Rewound over write index, resuming live play" );
        // Clear paused flag
        paused = false;
        // Clear delayed_play flag
        delayed = false;
        replay_rate = ZM_RATE_BASE;
      }
    }
    if ( (unsigned int)last_read_index != monitor->shared_data->last_write_index ) {
      int index = monitor->shared_data->last_write_index%monitor->image_buffer_count;
      last_read_index = monitor->shared_data->last_write_index;
      //Debug( 1, "%d: %x - %x", index, image_buffer[index].image, image_buffer[index].image->buffer );
      if ( (frame_mod == 1) || ((frame_count%frame_mod) == 0) ) {
        if ( !paused && !delayed ) {
          // Send the next frame
          Monitor::Snapshot *snap = &monitor->image_buffer[index];

          if ( !sendFrame( snap->image, snap->timestamp ) )
            zm_terminate = true;
          memcpy( &last_frame_timestamp, snap->timestamp, sizeof(last_frame_timestamp) );
          //frame_sent = true;

          temp_read_index = temp_write_index;
        }
      }
      if ( buffered_playback ) {
        if ( monitor->shared_data->valid ) {
          if ( monitor->image_buffer[index].timestamp->tv_sec ) {
            int temp_index = temp_write_index%temp_image_buffer_count;
            Debug( 2, "Storing frame %d", temp_index );
            if ( !temp_image_buffer[temp_index].valid ) {
              snprintf( temp_image_buffer[temp_index].file_name, sizeof(temp_image_buffer[0].file_name), "%s/zmswap-i%05d.jpg", swap_path, temp_index );
              temp_image_buffer[temp_index].valid = true;
            }
            memcpy( &(temp_image_buffer[temp_index].timestamp), monitor->image_buffer[index].timestamp, sizeof(temp_image_buffer[0].timestamp) );
            monitor->image_buffer[index].image->WriteJpeg( temp_image_buffer[temp_index].file_name, config.jpeg_file_quality );
            temp_write_index = MOD_ADD( temp_write_index, 1, temp_image_buffer_count );
            if ( temp_write_index == temp_read_index ) {
              // Go back to live viewing
              Warning( "Exceeded temporary buffer, resuming live play" );
              // Clear paused flag
              paused = false;
              // Clear delayed_play flag
              delayed = false;
              replay_rate = ZM_RATE_BASE;
            }
          } else {
            Warning( "Unable to store frame as timestamp invalid" );
          }
        } else {
          Warning( "Unable to store frame as shared memory invalid" );
        }
      }
      frame_count++;
    }
    usleep( (unsigned long)((1000000 * ZM_RATE_BASE)/((base_fps?base_fps:1)*abs(replay_rate*2))) );
    if ( ttl ) {
      if ( (now.tv_sec - stream_start_time) > ttl ) {
        break;
      }
    }
    if ( (TV_2_FLOAT( now ) - last_frame_sent) > max_secs_since_last_sent_frame ) {
      Error( "Terminating, last frame sent time %f secs more than maximum of %f", TV_2_FLOAT( now ) - last_frame_sent, max_secs_since_last_sent_frame );
      break;
    }
  }
  if ( buffered_playback ) {
    Debug( 1, "Cleaning swap files from %s", swap_path );
    struct stat stat_buf;
    if ( stat( swap_path, &stat_buf ) < 0 ) {
      if ( errno != ENOENT ) {
        Error( "Can't stat '%s': %s", swap_path, strerror(errno) );
      }
    } else if ( !S_ISDIR(stat_buf.st_mode) ) {
      Error( "Swap image path '%s' is not a directory", swap_path );
    } else {
      char glob_pattern[PATH_MAX] = "";

      snprintf( glob_pattern, sizeof(glob_pattern), "%s/*.*", swap_path );
      glob_t pglob;
      int glob_status = glob( glob_pattern, 0, 0, &pglob );
      if ( glob_status != 0 ) {
        if ( glob_status < 0 ) {
          Error( "Can't glob '%s': %s", glob_pattern, strerror(errno) );
        } else {
          Debug( 1, "Can't glob '%s': %d", glob_pattern, glob_status );
        }
      } else {
        for ( unsigned int i = 0; i < pglob.gl_pathc; i++ ) {
          if ( unlink( pglob.gl_pathv[i] ) < 0 ) {
            Error( "Can't unlink '%s': %s", pglob.gl_pathv[i], strerror(errno) );
          }
        }
      }
      globfree( &pglob );
      if ( rmdir( swap_path ) < 0 ) {
        Error( "Can't rmdir '%s': %s", swap_path, strerror(errno) );
      }
    }
  }
  if ( swap_path ) free( swap_path );
  closeComms();
}

void MonitorStream::SingleImage( int scale ) {
  int img_buffer_size = 0;
  static JOCTET img_buffer[ZM_MAX_IMAGE_SIZE];
  Image scaled_image;
  Monitor::Snapshot *snap = monitor->getSnapshot();
  Image *snap_image = snap->image;

  if ( scale != ZM_SCALE_BASE ) {
    scaled_image.Assign( *snap_image );
    scaled_image.Scale( scale );
    snap_image = &scaled_image;
  }
  if ( !config.timestamp_on_capture ) {
    monitor->TimestampImage( snap_image, snap->timestamp );
  }
  snap_image->EncodeJpeg( img_buffer, &img_buffer_size );
  
  fprintf( stdout, "Content-Length: %d\r\n", img_buffer_size );
  fprintf( stdout, "Content-Type: image/jpeg\r\n\r\n" );
  fwrite( img_buffer, img_buffer_size, 1, stdout );
}

void MonitorStream::SingleImageRaw( int scale ) {
  Image scaled_image;
  Monitor::Snapshot *snap = monitor->getSnapshot();
  Image *snap_image = snap->image;

  if ( scale != ZM_SCALE_BASE ) {
    scaled_image.Assign( *snap_image );
    scaled_image.Scale( scale );
    snap_image = &scaled_image;
  }
  if ( !config.timestamp_on_capture ) {
    monitor->TimestampImage( snap_image, snap->timestamp );
  }
  
  fprintf( stdout, "Content-Length: %d\r\n", snap_image->Size() );
  fprintf( stdout, "Content-Type: image/x-rgb\r\n\r\n" );
  fwrite( snap_image->Buffer(), snap_image->Size(), 1, stdout );
}

void MonitorStream::SingleImageZip( int scale ) {
  unsigned long img_buffer_size = 0;
  static Bytef img_buffer[ZM_MAX_IMAGE_SIZE];
  Image scaled_image;

  Monitor::Snapshot *snap = monitor->getSnapshot();
  Image *snap_image = snap->image;

  if ( scale != ZM_SCALE_BASE ) {
    scaled_image.Assign( *snap_image );
    scaled_image.Scale( scale );
    snap_image = &scaled_image;
  }
  if ( !config.timestamp_on_capture ) {
    monitor->TimestampImage( snap_image, snap->timestamp );
  }
  snap_image->Zip( img_buffer, &img_buffer_size );
  
  fprintf( stdout, "Content-Length: %ld\r\n", img_buffer_size );
  fprintf( stdout, "Content-Type: image/x-rgbz\r\n\r\n" );
  fwrite( img_buffer, img_buffer_size, 1, stdout );
}
