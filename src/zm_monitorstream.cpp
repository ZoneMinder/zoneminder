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

const int MAX_SLEEP_USEC=1000000; // 1 sec

bool MonitorStream::checkSwapPath(const char *path, bool create_path) {

  struct stat stat_buf;
  if ( stat(path, &stat_buf) < 0 ) {
    if ( create_path && errno == ENOENT ) {
      Debug(3, "Swap path '%s' missing, creating", path);
      if ( mkdir(path, 0755) ) {
        Error("Can't mkdir %s: %s", path, strerror(errno));
        return false;
      }
      if ( stat(path, &stat_buf) < 0 ) {
        Error("Can't stat '%s': %s", path, strerror(errno));
        return false;
      }
    } else {
      Error("Can't stat '%s': %s", path, strerror(errno));
      return false;
    }
  }
  if ( !S_ISDIR(stat_buf.st_mode) ) {
    Error("Swap image path '%s' is not a directory", path);
    return false;
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
    Error("Insufficient permissions on swap image path '%s'", path);
    return false;
  }
  return true;
} // end bool MonitorStream::checkSwapPath( const char *path, bool create_path )

void MonitorStream::processCommand(const CmdMsg *msg) {
  Debug( 2, "Got message, type %d, msg %d", msg->msg_type, msg->msg_data[0] );
  // Check for incoming command
  switch( (MsgCommand)msg->msg_data[0] ) {
    case CMD_PAUSE :
      Debug(1, "Got PAUSE command");
      paused = true;
      delayed = true;
      last_frame_sent = TV_2_FLOAT(now);
      break;
    case CMD_PLAY :
      Debug(1, "Got PLAY command");
      if ( paused ) {
        paused = false;
        delayed = true;
      }
      replay_rate = ZM_RATE_BASE;
      break;
    case CMD_VARPLAY :
      Debug(1, "Got VARPLAY command");
      if ( paused ) {
        paused = false;
        delayed = true;
      }
      replay_rate = ntohs(((unsigned char)msg->msg_data[2]<<8)|(unsigned char)msg->msg_data[1])-32768;
      break;
    case CMD_STOP :
      Debug(1, "Got STOP command");
      paused = false;
      delayed = false;
      break;
    case CMD_FASTFWD :
      Debug(1, "Got FAST FWD command");
      if ( paused ) {
        paused = false;
        delayed = true;
      }
      // Set play rate
      switch ( replay_rate ) {
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
    case CMD_SLOWFWD :
      Debug( 1, "Got SLOW FWD command" );
      paused = true;
      delayed = true;
      replay_rate = ZM_RATE_BASE;
      step = 1;
      break;
    case CMD_SLOWREV :
      Debug( 1, "Got SLOW REV command" );
      paused = true;
      delayed = true;
      replay_rate = ZM_RATE_BASE;
      step = -1;
      break;
    case CMD_FASTREV :
      Debug( 1, "Got FAST REV command" );
      if ( paused ) {
        paused = false;
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
    case CMD_ZOOMIN :
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
    case CMD_ZOOMOUT :
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
    case CMD_PAN :
      x = ((unsigned char)msg->msg_data[1]<<8)|(unsigned char)msg->msg_data[2];
      y = ((unsigned char)msg->msg_data[3]<<8)|(unsigned char)msg->msg_data[4];
      Debug(1, "Got PAN command, to %d,%d", x, y);
      break;
    case CMD_SCALE :
      scale = ((unsigned char)msg->msg_data[1]<<8)|(unsigned char)msg->msg_data[2];
      Debug(1, "Got SCALE command, to %d", scale);
      break;
    case CMD_QUIT :
      Info("User initiated exit - CMD_QUIT");
      break;
    case CMD_QUERY :
      Debug(1, "Got QUERY command, sending STATUS");
      break;
    default :
      Error("Got unexpected command %d", msg->msg_data[0]);
      break;
  } // end switch command

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
  Debug(2, "Buffer Level:%d, Delayed:%d, Paused:%d, Rate:%d, delay:%.3f, Zoom:%d, Enabled:%d Forced:%d",
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
  memcpy(&status_msg.msg_data, &status_data, sizeof(status_data));
  int nbytes = 0;
  if ( (nbytes = sendto(sd, &status_msg, sizeof(status_msg), MSG_DONTWAIT, (sockaddr *)&rem_addr, sizeof(rem_addr))) < 0 ) {
    //if ( errno != EAGAIN )
    {
      Error( "Can't sendto on sd %d: %s", sd, strerror(errno) );
      //exit( -1 );
    }
  }
  Debug(2, "Number of bytes sent to (%s): (%d)", rem_addr.sun_path, nbytes);

  // quit after sending a status, if this was a quit request
  if ( (MsgCommand)msg->msg_data[0]==CMD_QUIT ) {
    Debug(2,"Quitting");
    exit(0);
  }

  Debug(2,"Updating framerate");
  updateFrameRate(monitor->GetFPS());
} // end void MonitorStream::processCommand(const CmdMsg *msg)

bool MonitorStream::sendFrame(const char *filepath, struct timeval *timestamp) {
  bool send_raw = ((scale>=ZM_SCALE_BASE)&&(zoom==ZM_SCALE_BASE));

  if ( type != STREAM_JPEG )
    send_raw = false;
  if ( !config.timestamp_on_capture && timestamp )
    send_raw = false;

  if ( !send_raw ) {
    Image temp_image(filepath);

    return sendFrame(&temp_image, timestamp);
  } else {
    int img_buffer_size = 0;
    static unsigned char img_buffer[ZM_MAX_IMAGE_SIZE];

    FILE *fdj = NULL;
    if ( (fdj = fopen(filepath, "r")) ) {
      img_buffer_size = fread(img_buffer, 1, sizeof(img_buffer), fdj);
      fclose(fdj);
    } else {
      Error("Can't open %s: %s", filepath, strerror(errno));
      return false;
    }

    // Calculate how long it takes to actually send the frame
    struct timeval frameStartTime;
    gettimeofday(&frameStartTime, NULL);

    fputs("--ZoneMinderFrame\r\nContent-Type: image/jpeg\r\n", stdout);
    fprintf(stdout, "Content-Length: %d\r\n\r\n", img_buffer_size);
    if ( fwrite(img_buffer, img_buffer_size, 1, stdout) != 1 ) {
      if ( !zm_terminate )
        Warning("Unable to send stream frame: %s", strerror(errno));
      return false;
    }
    fputs("\r\n\r\n", stdout);
    fflush(stdout);

    struct timeval frameEndTime;
    gettimeofday(&frameEndTime, NULL);

    int frameSendTime = tvDiffMsec(frameStartTime, frameEndTime);
    if ( frameSendTime > 1000/maxfps ) {
      maxfps /= 2;
      Info("Frame send time %d msec too slow, throttling maxfps to %.2f", frameSendTime, maxfps);
    }

    last_frame_sent = TV_2_FLOAT(now);

    return true;
  }
  return false;
} // end bool MonitorStream::sendFrame(const char *filepath, struct timeval *timestamp)

bool MonitorStream::sendFrame(Image *image, struct timeval *timestamp) {
  Image *send_image = prepareImage(image);
  if ( !config.timestamp_on_capture && timestamp )
    monitor->TimestampImage(send_image, timestamp);

#if HAVE_LIBAVCODEC
  if ( type == STREAM_MPEG ) {
    if ( !vid_stream ) {
      vid_stream = new VideoStream("pipe:", format, bitrate, effective_fps, send_image->Colours(), send_image->SubpixelOrder(), send_image->Width(), send_image->Height());
      fprintf(stdout, "Content-type: %s\r\n\r\n", vid_stream->MimeType());
      vid_stream->OpenStream();
    }
    static struct timeval base_time;
    struct DeltaTimeval delta_time;
    if ( !frame_count )
      base_time = *timestamp;
    DELTA_TIMEVAL(delta_time, *timestamp, base_time, DT_PREC_3);
    /* double pts = */ vid_stream->EncodeFrame(send_image->Buffer(), send_image->Size(), config.mpeg_timed_frames, delta_time.delta);
  } else
#endif // HAVE_LIBAVCODEC
  {
    static unsigned char temp_img_buffer[ZM_MAX_IMAGE_SIZE];

    int img_buffer_size = 0;
    unsigned char *img_buffer = temp_img_buffer;

    // Calculate how long it takes to actually send the frame
    struct timeval frameStartTime;
    gettimeofday(&frameStartTime, NULL);

    fputs("--ZoneMinderFrame\r\n", stdout);
    switch( type ) {
      case STREAM_JPEG :
        send_image->EncodeJpeg(img_buffer, &img_buffer_size);
        fputs("Content-Type: image/jpeg\r\n", stdout);
        break;
      case STREAM_RAW :
        fputs("Content-Type: image/x-rgb\r\n", stdout);
        img_buffer = (uint8_t*)send_image->Buffer();
        img_buffer_size = send_image->Size();
        break;
      case STREAM_ZIP :
#if HAVE_ZLIB_H
        fputs("Content-Type: image/x-rgbz\r\n", stdout);
        unsigned long zip_buffer_size;
        send_image->Zip(img_buffer, &zip_buffer_size);
        img_buffer_size = zip_buffer_size;
#else
          Error("zlib is required for zipped images. Falling back to raw image");
          type = STREAM_RAW;
#endif // HAVE_ZLIB_H
        break;
      default :
        Error("Unexpected frame type %d", type);
        return false;
    }
    fprintf(stdout, "Content-Length: %d\r\n\r\n", img_buffer_size);
    if ( fwrite(img_buffer, img_buffer_size, 1, stdout) != 1 ) {
      if ( !zm_terminate ) {
        // If the pipe was closed, we will get signalled SIGPIPE to exit, which will set zm_terminate
        Warning("Unable to send stream frame: %s", strerror(errno));
      }
      return false;
    }
    fputs("\r\n\r\n", stdout);
    fflush(stdout);

    struct timeval frameEndTime;
    gettimeofday(&frameEndTime, NULL);

    int frameSendTime = tvDiffMsec(frameStartTime, frameEndTime);
    if ( frameSendTime > 1000/maxfps ) {
      maxfps /= 1.5;
      Warning("Frame send time %d msec too slow, throttling maxfps to %.2f",
          frameSendTime, maxfps);
    }
  }  // Not mpeg
  last_frame_sent = TV_2_FLOAT(now);
  return true;
} // end bool MonitorStream::sendFrame( Image *image, struct timeval *timestamp )

void MonitorStream::runStream() {
  if ( type == STREAM_SINGLE ) {
    // Not yet migrated over to stream class
    SingleImage(scale);
    return;
  }

  openComms();

  if ( !checkInitialised() ) {
    Error("Not initialized");
    return;
  }

  updateFrameRate(monitor->GetFPS());

  if ( type == STREAM_JPEG )
    fputs("Content-Type: multipart/x-mixed-replace;boundary=ZoneMinderFrame\r\n\r\n", stdout);

  // point to end which is theoretically not a valid value because all indexes are % image_buffer_count
  unsigned int last_read_index = monitor->image_buffer_count;

  time_t stream_start_time;
  time(&stream_start_time);

  frame_count = 0;

  temp_image_buffer = 0;
  temp_image_buffer_count = playback_buffer;
  temp_read_index = temp_image_buffer_count;
  temp_write_index = temp_image_buffer_count;

  std::string swap_path;
  bool buffered_playback = false;

  // Last image and timestamp when paused, will be resent occasionally to prevent timeout
  Image *paused_image = NULL;
  struct timeval paused_timestamp;

  if ( connkey && ( playback_buffer > 0 ) ) {
    // 15 is the max length for the swap path suffix, /zmswap-whatever, assuming max 6 digits for monitor id
    const int max_swap_len_suffix = 15;

    int swap_path_length = staticConfig.PATH_SWAP.length() + 1; // +1 for NULL terminator
    int subfolder1_length = snprintf(NULL, 0, "/zmswap-m%d", monitor->Id()) + 1;
    int subfolder2_length = snprintf(NULL, 0, "/zmswap-q%06d", connkey) + 1;
    int total_swap_path_length = swap_path_length + subfolder1_length + subfolder2_length;

    if ( total_swap_path_length + max_swap_len_suffix > PATH_MAX ) {
      Error("Swap Path is too long. %d > %d ", total_swap_path_length+max_swap_len_suffix, PATH_MAX);
    } else {
      swap_path = staticConfig.PATH_SWAP;

      Debug(3, "Checking swap path folder: %s", swap_path.c_str());
      if ( checkSwapPath(swap_path.c_str(), true) ) {
        swap_path += stringtf("/zmswap-m%d", monitor->Id());

        Debug(4, "Checking swap path subfolder: %s", swap_path.c_str());
        if ( checkSwapPath(swap_path.c_str(), true) ) {
          swap_path += stringtf("/zmswap-q%06d", connkey);

          Debug(4, "Checking swap path subfolder: %s", swap_path.c_str());
          if ( checkSwapPath(swap_path.c_str(), true) ) {
            buffered_playback = true;
          }
        }
      }

      if ( !buffered_playback ) {
        Error("Unable to validate swap image path, disabling buffered playback");
      } else {
        Debug(2, "Assigning temporary buffer");
        temp_image_buffer = new SwapImage[temp_image_buffer_count];
        memset(temp_image_buffer, 0, sizeof(*temp_image_buffer)*temp_image_buffer_count);
        Debug(2, "Assigned temporary buffer");
      }
    }
  } else {
    Debug(2, "Not using playback_buffer");
  } // end if connkey  & playback_buffer

  float max_secs_since_last_sent_frame = 10.0; //should be > keep alive amount (5 secs)
  // if MaxFPS < 0 as in 1/60 = 0.017 then we won't get another frame for 60 sec.
  double capture_fps = monitor->GetFPS();
  double capture_max_fps = monitor->GetCaptureMaxFPS();
  if ( capture_max_fps && ( capture_fps > capture_max_fps ) ) {
    Debug(1, "Using %.3f for fps instead of current fps %.3f", capture_max_fps, capture_fps);
    capture_fps = capture_max_fps;
  }

  if ( capture_fps < 1 ) {
    max_secs_since_last_sent_frame = 10/capture_fps;
    Debug(1, "Adjusting max_secs_since_last_sent_frame to %.2f from current fps %.2f",
        max_secs_since_last_sent_frame, monitor->GetFPS());
  } else {
    Debug(1, "Not Adjusting max_secs_since_last_sent_frame to %.2f from current fps %.2f",
        max_secs_since_last_sent_frame, monitor->GetFPS());
  }

  while ( !zm_terminate ) {
    bool got_command = false;
    if ( feof(stdout) ) {
      Debug(2, "feof stdout");
      break;
    } else if ( ferror(stdout) ) {
      Debug(2, "ferror stdout");
      break;
    } else if ( !monitor->ShmValid() ) {
      Debug(2, "monitor not valid.... maybe we should wait until it comes back.");
      break;
    }

    gettimeofday(&now, NULL);

    bool was_paused = paused;
    if ( connkey ) {
      while ( checkCommandQueue() && !zm_terminate ) {
        // Loop in here until all commands are processed.
        Debug(2, "Have checking command Queue for connkey: %d", connkey);
        got_command = true;
      }
      // Update modified time of the socket .lock file so that we can tell which ones are stale.
      if ( now.tv_sec - last_comm_update.tv_sec > 3600 ) {
        touch(sock_path_lock);
        last_comm_update = now;
      }
    }  // end if connkey

    if ( paused ) {
      if ( !was_paused ) {
        int index = monitor->shared_data->last_write_index % monitor->image_buffer_count;
        Debug(1, "Saving paused image from index %d",index);
        paused_image = new Image(*monitor->image_buffer[index].image);
        paused_timestamp = *(monitor->image_buffer[index].timestamp);
      }
    } else if ( paused_image ) {
      Debug(1, "Clearing paused_image");
      delete paused_image;
      paused_image = NULL;
    }

    if ( buffered_playback && delayed ) {
      if ( temp_read_index == temp_write_index ) {
        // Go back to live viewing
        Debug(1, "Exceeded temporary streaming buffer");
        // Clear paused flag
        paused = false;
        // Clear delayed_play flag
        delayed = false;
        replay_rate = ZM_RATE_BASE;
      } else {
        if ( !paused ) {
          int temp_index = MOD_ADD(temp_read_index, 0, temp_image_buffer_count);
          // Debug( 3, "tri: %d, ti: %d", temp_read_index, temp_index );
          SwapImage *swap_image = &temp_image_buffer[temp_index];

          if ( !swap_image->valid ) {
            paused = true;
            delayed = true;
            temp_read_index = MOD_ADD(temp_read_index, (replay_rate>=0?-1:1), temp_image_buffer_count);
          } else {
            // Debug( 3, "siT: %f, lfT: %f", TV_2_FLOAT( swap_image->timestamp ), TV_2_FLOAT( last_frame_timestamp ) );
            double expected_delta_time = ((TV_2_FLOAT(swap_image->timestamp) - TV_2_FLOAT(last_frame_timestamp)) * ZM_RATE_BASE)/replay_rate;
            double actual_delta_time = TV_2_FLOAT(now) - last_frame_sent;

            // Debug( 3, "eDT: %.3lf, aDT: %.3f, lFS:%.3f, NOW:%.3f", expected_delta_time, actual_delta_time, last_frame_sent, TV_2_FLOAT( now ) );
            // If the next frame is due
            if ( actual_delta_time > expected_delta_time ) {
              // Debug( 2, "eDT: %.3lf, aDT: %.3f", expected_delta_time, actual_delta_time );
              if ( temp_index%frame_mod == 0 ) {
                Debug(2, "Sending delayed frame %d", temp_index);
                // Send the next frame
                if ( ! sendFrame(temp_image_buffer[temp_index].file_name, &temp_image_buffer[temp_index].timestamp) ) {
                  zm_terminate = true;
                }
                memcpy(&last_frame_timestamp, &(swap_image->timestamp), sizeof(last_frame_timestamp));
                // frame_sent = true;
              }
              temp_read_index = MOD_ADD(temp_read_index, (replay_rate>0?1:-1), temp_image_buffer_count);
            }
          }
        } else if ( step != 0 ) {
          temp_read_index = MOD_ADD(temp_read_index, (step>0?1:-1), temp_image_buffer_count);

          SwapImage *swap_image = &temp_image_buffer[temp_read_index];

          // Send the next frame
          if ( !sendFrame(
                temp_image_buffer[temp_read_index].file_name,
                &temp_image_buffer[temp_read_index].timestamp
                ) ) {
            zm_terminate = true;
          }
          memcpy(
              &last_frame_timestamp,
              &(swap_image->timestamp),
              sizeof(last_frame_timestamp)
              );
          // frame_sent = true;
          step = 0;
        } else {
          //paused?
          int temp_index = MOD_ADD(temp_read_index, 0, temp_image_buffer_count);

          double actual_delta_time = TV_2_FLOAT(now) - last_frame_sent;
          if ( got_command || (actual_delta_time > 5) ) {
            // Send keepalive
            Debug(2, "Sending keepalive frame %d", temp_index);
            // Send the next frame
            if ( !sendFrame(temp_image_buffer[temp_index].file_name, &temp_image_buffer[temp_index].timestamp) ) {
              zm_terminate = true;
            }
            // frame_sent = true;
          }
        }  // end if (!paused) or step or paused
      }  // end if have exceeded buffer or not

      if ( temp_read_index == temp_write_index ) {
        // Go back to live viewing
        Warning("Rewound over write index, resuming live play");
        // Clear paused flag
        paused = false;
        // Clear delayed_play flag
        delayed = false;
        replay_rate = ZM_RATE_BASE;
      }
    }  // end if ( buffered_playback && delayed )

    if ( last_read_index != monitor->shared_data->last_write_index ) {
      // have a new image to send
      int index = monitor->shared_data->last_write_index % monitor->image_buffer_count;  // % shouldn't be neccessary
      if ( (frame_mod == 1) || ((frame_count%frame_mod) == 0) ) {
        if ( !paused && !delayed ) {
          last_read_index = monitor->shared_data->last_write_index;
          Debug(2, "index: %d: frame_mod: %d frame count: %d paused(%d) delayed(%d)",
              index, frame_mod, frame_count, paused, delayed);
          // Send the next frame
          Monitor::Snapshot *snap = &monitor->image_buffer[index];

          Debug(2, "sending Frame.");
          if ( !sendFrame(snap->image, snap->timestamp) ) {
            Debug(2, "sendFrame failed, quiting.");
            zm_terminate = true;
          }
          // Perhaps we should use NOW instead.
          memcpy(
              &last_frame_timestamp,
              snap->timestamp,
              sizeof(last_frame_timestamp)
              );
          // frame_sent = true;

          temp_read_index = temp_write_index;
        } else {
          if ( delayed && !buffered_playback ) {
            Debug(2, "Can't delay when not buffering.");
            delayed = false;
          }
          if ( last_zoom != zoom ) {
            Debug(2, "Sending 2 frames because change in zoom %d ?= %d", last_zoom, zoom);
            if ( !sendFrame(paused_image, &paused_timestamp) )
              zm_terminate = true;
            if ( !sendFrame(paused_image, &paused_timestamp) )
              zm_terminate = true;
          } else {
            double actual_delta_time = TV_2_FLOAT(now) - last_frame_sent;
            if ( actual_delta_time > 5 ) {
              if ( paused_image ) {
                // Send keepalive
                Debug(2, "Sending keepalive frame because delta time %.2f > 5",
                    actual_delta_time);
                // Send the next frame
                if ( !sendFrame(paused_image, &paused_timestamp) )
                  zm_terminate = true;
              } else {
                Debug(2, "Would have sent keepalive frame, but had no paused_image");
              }
            } // end if actual_delta_time > 5
          } // end if change in zoom
        } // end if paused or not
      } // end if should send frame

      if ( buffered_playback && !paused ) {
        if ( monitor->shared_data->valid ) {
          if ( monitor->image_buffer[index].timestamp->tv_sec ) {
            int temp_index = temp_write_index%temp_image_buffer_count;
            Debug(2, "Storing frame %d", temp_index);
            if ( !temp_image_buffer[temp_index].valid ) {
              snprintf(
                  temp_image_buffer[temp_index].file_name,
                  sizeof(temp_image_buffer[0].file_name),
                  "%s/zmswap-i%05d.jpg",
                  swap_path.c_str(),
                  temp_index);
              temp_image_buffer[temp_index].valid = true;
            }
            memcpy(&(temp_image_buffer[temp_index].timestamp), monitor->image_buffer[index].timestamp, sizeof(temp_image_buffer[0].timestamp));
            monitor->image_buffer[index].image->WriteJpeg(temp_image_buffer[temp_index].file_name, config.jpeg_file_quality);
            temp_write_index = MOD_ADD(temp_write_index, 1, temp_image_buffer_count);
            if ( temp_write_index == temp_read_index ) {
              // Go back to live viewing
              Warning("Exceeded temporary buffer, resuming live play");
              paused = false;
              delayed = false;
              replay_rate = ZM_RATE_BASE;
            }
          } else {
            Warning("Unable to store frame as timestamp invalid");
          }
        } else {
          Warning("Unable to store frame as shared memory invalid");
        }
      } // end if buffered playback
      frame_count++;
    } else {
      Debug(3, "Waiting for capture last_write_index=%u", monitor->shared_data->last_write_index);
    } // end if ( (unsigned int)last_read_index != monitor->shared_data->last_write_index )

    unsigned long sleep_time = (unsigned long)((1000000 * ZM_RATE_BASE)/((base_fps?base_fps:1)*abs(replay_rate*2)));
    Debug(3, "Sleeping for (%d)", sleep_time);

    if ( sleep_time > MAX_SLEEP_USEC ) {
      // Shouldn't sleep for long because we need to check command queue, etc.
      sleep_time = MAX_SLEEP_USEC;
    }
    Debug(3, "Sleeping for %dus", sleep_time);
    usleep(sleep_time);
    if ( ttl ) {
      if ( (now.tv_sec - stream_start_time) > ttl ) {
        Debug(2, "now(%d) - start(%d) > ttl(%d) break", now.tv_sec, stream_start_time, ttl);
        break;
      }
    }
    if ( !last_frame_sent ) {
      // If we didn't capture above, because frame_mod was bad? Then last_frame_sent will not have a value.
      last_frame_sent = now.tv_sec;
      Warning("no last_frame_sent.  Shouldn't happen. frame_mod was (%d) frame_count (%d)",
          frame_mod, frame_count);
    } else if (
        (!paused)
        &&
        ( (TV_2_FLOAT(now) - last_frame_sent) > max_secs_since_last_sent_frame )
        ) {
      Error("Terminating, last frame sent time %f secs more than maximum of %f",
          TV_2_FLOAT(now) - last_frame_sent, max_secs_since_last_sent_frame);
      break;
    }
  } // end while

  if ( buffered_playback ) {
    Debug(1, "Cleaning swap files from %s", swap_path.c_str());
    struct stat stat_buf;
    if ( stat(swap_path.c_str(), &stat_buf) < 0 ) {
      if ( errno != ENOENT ) {
        Error("Can't stat '%s': %s", swap_path.c_str(), strerror(errno));
      }
    } else if ( !S_ISDIR(stat_buf.st_mode) ) {
      Error("Swap image path '%s' is not a directory", swap_path.c_str());
    } else {
      char glob_pattern[PATH_MAX] = "";

      snprintf(glob_pattern, sizeof(glob_pattern), "%s/*.*", swap_path.c_str());
      glob_t pglob;
      int glob_status = glob(glob_pattern, 0, 0, &pglob);
      if ( glob_status != 0 ) {
        if ( glob_status < 0 ) {
          Error("Can't glob '%s': %s", glob_pattern, strerror(errno));
        } else {
          Debug(1, "Can't glob '%s': %d", glob_pattern, glob_status);
        }
      } else {
        for ( unsigned int i = 0; i < pglob.gl_pathc; i++ ) {
          if ( unlink(pglob.gl_pathv[i]) < 0 ) {
            Error("Can't unlink '%s': %s", pglob.gl_pathv[i], strerror(errno));
          }
        }
      }
      globfree(&pglob);
      if ( rmdir(swap_path.c_str()) < 0 ) {
        Error("Can't rmdir '%s': %s", swap_path.c_str(), strerror(errno));
      }
    } // end if checking for swap_path
  } // end if buffered_playback

  closeComms();
} // end MonitorStream::runStream

void MonitorStream::SingleImage(int scale) {
  int img_buffer_size = 0;
  static JOCTET img_buffer[ZM_MAX_IMAGE_SIZE];
  Image scaled_image;
  Monitor::Snapshot *snap = monitor->getSnapshot();
  Image *snap_image = snap->image;

  if ( scale != ZM_SCALE_BASE ) {
    scaled_image.Assign(*snap_image);
    scaled_image.Scale(scale);
    snap_image = &scaled_image;
  }
  if ( !config.timestamp_on_capture ) {
    monitor->TimestampImage(snap_image, snap->timestamp);
  }
  snap_image->EncodeJpeg(img_buffer, &img_buffer_size);

  fprintf(stdout,
      "Content-Length: %d\r\n"
      "Content-Type: image/jpeg\r\n\r\n",
      img_buffer_size);
  fwrite(img_buffer, img_buffer_size, 1, stdout);
}

void MonitorStream::SingleImageRaw(int scale) {
  Image scaled_image;
  Monitor::Snapshot *snap = monitor->getSnapshot();
  Image *snap_image = snap->image;

  if ( scale != ZM_SCALE_BASE ) {
    scaled_image.Assign(*snap_image);
    scaled_image.Scale(scale);
    snap_image = &scaled_image;
  }
  if ( !config.timestamp_on_capture ) {
    monitor->TimestampImage(snap_image, snap->timestamp);
  }

  fprintf(stdout,
      "Content-Length: %d\r\n"
      "Content-Type: image/x-rgb\r\n\r\n",
      snap_image->Size());
  fwrite(snap_image->Buffer(), snap_image->Size(), 1, stdout);
}

#ifdef HAVE_ZLIB_H
void MonitorStream::SingleImageZip(int scale) {
  unsigned long img_buffer_size = 0;
  static Bytef img_buffer[ZM_MAX_IMAGE_SIZE];
  Image scaled_image;

  Monitor::Snapshot *snap = monitor->getSnapshot();
  Image *snap_image = snap->image;

  if ( scale != ZM_SCALE_BASE ) {
    scaled_image.Assign(*snap_image);
    scaled_image.Scale(scale);
    snap_image = &scaled_image;
  }
  if ( !config.timestamp_on_capture ) {
    monitor->TimestampImage(snap_image, snap->timestamp);
  }
  snap_image->Zip(img_buffer, &img_buffer_size);

  fprintf(stdout,
      "Content-Length: %ld\r\n"
      "Content-Type: image/x-rgbz\r\n\r\n",
      img_buffer_size);
  fwrite(img_buffer, img_buffer_size, 1, stdout);
}
#endif // HAVE_ZLIB_H
