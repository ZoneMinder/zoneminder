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

#include "zm_box.h"
#include "zm_logger.h"
#include "zm_mpeg.h"
#include "zm_time.h"
#include <memory>
#include <sys/un.h>

class Image;
class Monitor;

#define BOUNDARY "ZoneMinderFrame"

class StreamBase {
 public:
  typedef enum {
    STREAM_JPEG,
    STREAM_RAW,
    STREAM_ZIP,
    STREAM_SINGLE,
    STREAM_MPEG
  } StreamType;
  typedef enum { FRAME_NORMAL, FRAME_ANALYSIS } FrameType;

 protected:
  static constexpr Seconds MAX_STREAM_DELAY = Seconds(5);
  static constexpr Milliseconds MAX_SLEEP = Milliseconds(500);

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

  typedef enum {
    MSG_CMD=1,
    MSG_DATA_WATCH,
    MSG_DATA_EVENT
  } MsgType;

  typedef enum {
    CMD_NONE=0,
    CMD_PAUSE,
    CMD_PLAY,
    CMD_STOP,
    CMD_FASTFWD,
    CMD_SLOWFWD,
    CMD_SLOWREV,
    CMD_FASTREV,
    CMD_ZOOMIN,
    CMD_ZOOMOUT,
    CMD_PAN,
    CMD_SCALE,
    CMD_PREV,
    CMD_NEXT,
    CMD_SEEK,
    CMD_VARPLAY,
    CMD_GET_IMAGE,
    CMD_QUIT,
    CMD_MAXFPS,
    CMD_ANALYZE_ON,
    CMD_ANALYZE_OFF,
    CMD_ZOOMSTOP,
    CMD_QUERY=99
  } MsgCommand;

 protected:
  int monitor_id;
  std::shared_ptr<Monitor> monitor;

  StreamType type;
  FrameType   frame_type;
  const char *format;
  int replay_rate;
  int scale;
  int last_scale;
  int zoom;
  int last_zoom;
  Box last_crop;
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
  bool send_twice;        // flag to send the same frame twice

  TimePoint now;
  TimePoint last_comm_update;

  double maxfps;
  double base_fps;        // Should be capturing fps, hence a rough target
  double effective_fps;   // Target fps after taking max_fps into account
  double actual_fps;      // sliding calculated actual streaming fps achieved
  TimePoint last_fps_update;
  int frame_count;      // Count of frames sent
  int last_frame_count; // Used in calculating actual_fps from frame_count - last_frame_count

  int frame_mod;
  int frames_to_send;

  TimePoint last_frame_sent;
  SystemTimePoint last_frame_timestamp;
  TimePoint when_to_send_next_frame;  // When to send next frame so if now < send_next_frame, skip

  VideoStream *vid_stream;

  CmdMsg msg;
  bool got_command = false; // commands like zoom should output a frame even if paused

  uint8_t *temp_img_buffer;     // Used when encoding or sending file data
  size_t temp_img_buffer_size;

  AVCodecContext *mJpegCodecContext;
  SwsContext     *mJpegSwsContext;

 protected:
  bool loadMonitor(int monitor_id);
  bool checkInitialised();
  void updateFrameRate(double fps);
  Image *prepareImage(Image *image);
  void checkCommandQueue();
  virtual void processCommand(const CmdMsg *msg)=0;
  void reserveTempImgBuffer(size_t size);
  bool initContexts(int p_width, int p_height);

 public:
  StreamBase():
    monitor_id(0),
    monitor(nullptr),
    type(DEFAULT_TYPE),
    frame_type(FRAME_NORMAL),
    format(""),
    replay_rate(DEFAULT_RATE),
    scale(DEFAULT_SCALE),
    last_scale(DEFAULT_SCALE),
    zoom(DEFAULT_ZOOM),
    last_zoom(DEFAULT_ZOOM),
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
    step(0),
    maxfps(DEFAULT_MAXFPS),
    base_fps(0.0),
    effective_fps(0.0),
    actual_fps(0.0),
    frame_count(0),
    last_frame_count(0),
    frame_mod(1),
    frames_to_send(-1),
    got_command(false),
    temp_img_buffer(nullptr),
    temp_img_buffer_size(0) {
    memset(&loc_sock_path, 0, sizeof(loc_sock_path));
    memset(&loc_addr, 0, sizeof(loc_addr));
    memset(&rem_sock_path, 0, sizeof(rem_sock_path));
    memset(&rem_addr, 0, sizeof(rem_addr));
    memset(&sock_path_lock, 0, sizeof(sock_path_lock));

    vid_stream = nullptr;
    msg = { 0, { 0 } };
  }
  virtual ~StreamBase();

  void setStreamType(StreamType p_type) {
    type = p_type;
#if ! HAVE_ZLIB_H
    if ( type == STREAM_ZIP ) {
      Error("zlib is required for zipped images. Falling back to raw image");
      type = STREAM_RAW;
    }
#endif
  }
  void setStreamFrameType(FrameType p_type) {
    frame_type = p_type;
  }
  void setStreamFormat(const char *p_format) {
    format = p_format;
  }
  void setStreamScale(int p_scale) {
    scale = p_scale;
    if ( !scale )
      scale = DEFAULT_SCALE;
  }
  void setStreamReplayRate(int p_rate) {
    Debug(1, "Setting replay_rate %d", p_rate);
    replay_rate = p_rate;
  }
  void setStreamMaxFPS(double p_maxfps) {
    Debug(1, "Setting max fps to %f", p_maxfps);
    maxfps = p_maxfps;
  }
  void setStreamBitrate(int p_bitrate) {
    bitrate = p_bitrate;
  }
  void setStreamQueue(int p_connkey) {
    connkey = p_connkey;
  }
  void setFramesToSend(int p_frames_to_send) { frames_to_send = p_frames_to_send; }
  bool sendTextFrame(const char *text);
  virtual void openComms();
  virtual void closeComms();
  virtual void runStream()=0;
};

#endif // ZM_STREAM_H
