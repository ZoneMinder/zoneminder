//
// ZoneMinder Core Interfaces, $Date$, $Revision$
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

#ifndef ZM_EVENTSTREAM_H
#define ZM_EVENTSTREAM_H

#include "zm_define.h"
#include "zm_ffmpeg_input.h"
#include "zm_monitor.h"
#include "zm_storage.h"
#include "zm_stream.h"

extern "C" {
#include <libavformat/avformat.h>
#include <libavformat/avio.h>
#include <libavcodec/avcodec.h>
}

#include <mutex>

class EventStream : public StreamBase {
 public:
  typedef enum { MODE_NONE, MODE_SINGLE, MODE_ALL, MODE_ALL_GAPLESS } StreamMode;
  static const std::string StreamMode_Strings[4];

 protected:
  struct FrameData {
    unsigned int id;
    SystemTimePoint timestamp;
    Microseconds offset;        // distance from event->starttime
    Microseconds delta;         // distance from last frame
    bool in_db;
   public:
    FrameData(unsigned int p_id, SystemTimePoint p_timestamp, Microseconds p_offset, Microseconds p_delta, bool p_in_db) :
      id(p_id),
      timestamp(p_timestamp),
      offset(p_offset),
      delta(p_delta),
      in_db(p_in_db) {
    }
  };

  struct EventData {
    uint64_t  event_id;
    unsigned int    monitor_id;
    unsigned int    storage_id;
    int             frame_count;    // Value of Frames column in Event
    int             last_frame_id;  // Highest frame id known about. Can be < frame_count in incomplete events
    SystemTimePoint start_time;
    SystemTimePoint end_time;
    Microseconds duration;
    std::string path;
    int             n_frames;       // # of frame rows returned from database
    std::vector<FrameData> frames;
    std::string video_file;
    Storage::Schemes  scheme;
    int             SaveJPEGs;
    Monitor::Orientation Orientation;
  };

 protected:
  static constexpr Milliseconds STREAM_PAUSE_WAIT = Milliseconds(250);

  static const StreamMode DEFAULT_MODE = MODE_SINGLE;

  StreamMode mode;
  bool forceEventChange;

  // A neighbour-event hint the UI pushes via CMD_SET_EVENT. The UI computes
  // next/prev from the filter and sort the user is viewing and sends them here,
  // so gapless advance follows that order across monitors instead of our own
  // same-monitor query (refs #4186). Each hint is bound to the event it was
  // computed for (source_event_id); checkEventLoaded honours it only while that
  // source is still the event playing, so a hint that arrives after we have left
  // that event is ignored rather than mis-steering the next transition. `have`
  // separates "the UI says there is no neighbour here" (event_id 0 -> stop at
  // the filter boundary) from "the UI has not told us" (fall back to the
  // same-monitor query). Read and written only under mutex.
  //
  // Known limitation: in a multi-server install with non-shared storage a
  // cross-monitor neighbour can live on another server, whose recording this
  // zms cannot read (loadEventData builds local paths only). Such an advance
  // degrades to failed frames; it is not re-routed to the serving server the
  // way the video.js player's reload path is. zms self-advance is effectively
  // same-server (or shared-storage) only; routing it is a separate change.
  struct EventHint {
    bool     have = false;
    uint64_t event_id = 0;
    uint64_t source_event_id = 0;
  };
  EventHint ui_next_hint;
  EventHint ui_prev_hint;

  std::mutex  mutex;
  int curr_frame_id;
  SystemTimePoint curr_stream_time;
  bool  send_frame;
  TimePoint start;     // clock time when started the event

  EventData *event_data;

 protected:
  bool loadEventData(uint64_t event_id);
  // Cheap existence check so a UI-provided neighbour that retention (or another
  // user) has deleted since the UI fetched it does not reach loadEventData's
  // fatal "not found" path and kill the stream (refs #4186).
  bool eventExists(uint64_t event_id);
  bool loadInitialEventData(uint64_t init_event_id, int init_frame_id);
  bool loadInitialEventData(int monitor_id, SystemTimePoint event_time);

  bool checkEventLoaded();
  void processCommand(const CmdMsg *msg) override;
  bool sendFrame(Microseconds delta);

 public:
  EventStream() :
    mode(DEFAULT_MODE),
    forceEventChange(false),
    curr_frame_id(0),
    send_frame(false),
    event_data(nullptr),
    storage(nullptr),
    ffmpeg_input(nullptr)
  {}

  ~EventStream() {
    delete event_data;
    delete storage;
    delete ffmpeg_input;
  }
  void setStreamStart(uint64_t init_event_id, int init_frame_id);
  void setStreamStart(uint64_t init_event_id, SystemTimePoint event_time);
  void setStreamStart(int monitor_id, SystemTimePoint event_time);
  void setStreamMode(StreamMode p_mode) { mode = p_mode; }
  bool seek(SystemTimePoint event_time);
  void runStream() override;
  Image *getImage();
 private:
  bool send_file(const std::string &filepath);
  bool send_buffer(uint8_t * buffer, int size);
  Storage *storage;
  FFmpeg_Input  *ffmpeg_input;
  std::string reuse_filepath_;  // reused across sendFrame calls to avoid per-frame heap alloc
  Image reuse_image_;  // reused JPEG decode target; ReadJpeg reuses its buffer when dimensions match
};

#endif // ZM_EVENTSTREAM_H
