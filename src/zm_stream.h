//
// ZoneMinder Stream Interfaces, $Date$, $Revision$
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

#ifndef ZM_STREAM_H
#define ZM_STREAM_H

#include <sys/un.h>
#include <sys/socket.h>

#include "zm.h"
#include "zm_mpeg.h"

class Monitor;

#define TV_2_FLOAT( tv ) ( double((tv).tv_sec) + (double((tv).tv_usec) / 1000000.0) )
#define BOUNDARY "ZoneMinderFrame"

class StreamBase {
public:
  typedef enum { STREAM_JPEG, STREAM_RAW, STREAM_ZIP, STREAM_SINGLE, STREAM_MPEG } StreamType;

protected:
  static const int MAX_STREAM_DELAY = 5; // Seconds
  static const int MAX_SLEEP_USEC = 500000; // .5 Seconds

  static const StreamType DEFAULT_TYPE = STREAM_JPEG;
  enum { DEFAULT_RATE=ZM_RATE_BASE };
  enum { DEFAULT_SCALE=ZM_SCALE_BASE };
  enum { DEFAULT_ZOOM=ZM_SCALE_BASE };
  enum { DEFAULT_MAXFPS=10 };
  enum { DEFAULT_BITRATE=100000 };

protected:
  typedef struct {
    int msg_type;
    char msg_data[16];
  } CmdMsg;

  typedef struct {
    int msg_type;
    char msg_data[256];
  } DataMsg;

  typedef enum { MSG_CMD=1, MSG_DATA_WATCH, MSG_DATA_EVENT } MsgType;
  typedef enum { CMD_NONE=0, CMD_PAUSE, CMD_PLAY, CMD_STOP, CMD_FASTFWD, CMD_SLOWFWD, CMD_SLOWREV, CMD_FASTREV, CMD_ZOOMIN, CMD_ZOOMOUT, CMD_PAN, CMD_SCALE, CMD_PREV, CMD_NEXT, CMD_SEEK, CMD_VARPLAY, CMD_GET_IMAGE, CMD_QUIT, CMD_QUERY=99 } MsgCommand;

protected:
  Monitor *monitor;

  StreamType type;
  const char *format;
  int replay_rate;
  int scale;
  int last_scale;
  int zoom;
  int last_zoom;
  double maxfps;
  int bitrate;
  unsigned short last_x, last_y;
  unsigned short x, y;
  bool send_analysis;
  bool send_objdetect;
  int connkey;
  int sd;
  char loc_sock_path[108];
  struct sockaddr_un loc_addr;
  char rem_sock_path[108];
  struct sockaddr_un rem_addr;
  char sock_path_lock[108];
  int lock_fd;
  bool paused;
  int step;

  struct timeval now;
  struct timeval last_comm_update;

  double base_fps;
  double effective_fps;
  int frame_mod;

  double last_frame_sent;
  struct timeval last_frame_timestamp;

#if HAVE_LIBAVCODEC   
  VideoStream *vid_stream;
#endif // HAVE_LIBAVCODEC   

  CmdMsg msg;

protected:
  bool loadMonitor( int monitor_id );
  bool checkInitialised();
  void updateFrameRate( double fps );
  Image *prepareImage( Image *image );
  bool sendTextFrame( const char *text );
  bool checkCommandQueue();
  virtual void processCommand( const CmdMsg *msg )=0;

public:
  StreamBase(): 
    monitor(0),
    type(DEFAULT_TYPE),
    format(""),
    replay_rate(DEFAULT_RATE),
    scale(DEFAULT_SCALE),
    last_scale(DEFAULT_SCALE),
    zoom(DEFAULT_ZOOM),
    last_zoom(DEFAULT_ZOOM),
    maxfps(DEFAULT_MAXFPS),
    bitrate(DEFAULT_BITRATE),
    last_x(0),
    last_y(0),
    x(0),
    y(0),
    send_analysis(false),
    send_objdetect(false),
    connkey(0),
    sd(-1),
    lock_fd(0),
    paused(false),
    step(0)

    {
    memset( &loc_sock_path, 0, sizeof(loc_sock_path) );
    memset( &loc_addr, 0, sizeof(loc_addr) );
    memset( &rem_sock_path, 0, sizeof(rem_sock_path) );
    memset( &rem_addr, 0, sizeof(rem_addr) );
    memset( &sock_path_lock, 0, sizeof(sock_path_lock) );

    base_fps = 0.0;
    effective_fps = 0.0;
    frame_mod = 1;

#if HAVE_LIBAVCODEC   
    vid_stream = 0;
#endif // HAVE_LIBAVCODEC   
    last_frame_sent = 0.0;
    last_frame_timestamp = (struct timeval){0};
    msg = { 0, { 0 } };
  }
  virtual ~StreamBase();

  void setStreamType( StreamType p_type ) {
    type = p_type;
  }
  void setStreamFormat( const char *p_format ) {
    format = p_format;
  }
  void setStreamScale( int p_scale ) {
    scale = p_scale;
    if ( ! scale )
      scale = DEFAULT_SCALE;
  }
  void setStreamReplayRate( int p_rate ) {
    Debug(2,"Setting replay_rate %d", p_rate);
    replay_rate = p_rate;
  }
  void setStreamMaxFPS( double p_maxfps ) {
    maxfps = p_maxfps;
  }
  void setStreamBitrate( int p_bitrate ) {
    bitrate = p_bitrate;
  }
  void setStreamQueue( int p_connkey ) {
    connkey = p_connkey;
  }
  virtual void openComms();
  virtual void closeComms();
  virtual void runStream()=0;
};

#endif // ZM_STREAM_H
