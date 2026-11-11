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

#include <memory>
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
    bool            in_progress;    // EndDateTime was NULL when the event was loaded
    Microseconds duration;
    std::string path;
    int             n_frames;       // # of frame rows returned from database
    std::vector<FrameData> frames;
    std::string video_file;
    Storage::Schemes  scheme;
    int             SaveJPEGs;
    Monitor::Orientation Orientation;
  };

 public:
  // A neighbour event the UI computed from the filter and sort the user is
  // viewing, so gapless advance follows that order across monitors instead of
  // our own same-monitor query (refs #4186). source_event_id is the event it was
  // computed for: it applies only while that event is playing. `have` separates
  // "the UI says there is no neighbour" (event_id 0, the filter boundary) from
  // "the UI has not said".
  struct EventHint {
    bool     have = false;
    uint64_t event_id = 0;
    uint64_t source_event_id = 0;
  };

  // A decoded CMD_SET_EVENT payload; see MsgCommand in zm_stream.h for the layout.
  struct SetEventCommand {
    bool     prev;
    uint64_t event_id;
    uint64_t source_event_id;  // only the low 48 bits are sent
  };
  static SetEventCommand DecodeSetEvent(const char *msg_data);
  // Whether a hint was computed for current_event_id, comparing the 48 bits sent.
  static bool HintIsFor(const EventHint &hint, uint64_t current_event_id);

  enum class NeighbourChoice {
    kHint,         // load hint.event_id
    kBoundary,     // the UI says the filter ends here: stop
    kWait,         // the UI has not sent this event's hint yet: hold and wait
    kTimedOut,     // waited kHintWait for the UI with no hint: stop
    kSameMonitor,  // no UI sends hints: use the same-monitor query
  };
  // How long to hold at the end of an event for the UI's hint. The UI learns
  // that we moved on from its next status poll, then asks for the new event's
  // neighbours and sends them, so this covers ZM_WEB_REFRESH_STATUS plus a few
  // requests with room to spare.
  static constexpr Seconds kHintWait = Seconds(10);
  // Chooses where to go at the end of current_event_id. Once a UI has sent hints
  // (ui_managed) we never fall back to the same-monitor query, which ignores the
  // filter: an event that ends before its hint arrives waits for it instead.
  static NeighbourChoice ChooseNeighbour(const EventHint &hint, uint64_t current_event_id,
                                         bool ui_managed, Microseconds waited);
  // Whether a SET_EVENT for the current event restarts a stream that stopped at
  // its end (end_stop) going in direction end_prev: it names an event that way.
  // The UI keeps asking about a boundary, so an event recorded since, or a hint
  // that arrived just too late, still plays.
  static bool HintResumes(const SetEventCommand &command, bool end_stop, bool end_prev);

 protected:
  static constexpr Milliseconds STREAM_PAUSE_WAIT = Milliseconds(250);

  static const StreamMode DEFAULT_MODE = MODE_SINGLE;

  StreamMode mode;
  bool forceEventChange;

  // The neighbour hints the UI pushes via CMD_SET_EVENT, one per direction, and
  // whether this stream has a UI sending them at all. See ChooseNeighbour.
  // Read and written only under mutex.
  //
  // Known limitation: in a multi-server install with non-shared storage a
  // cross-monitor neighbour can live on another server, whose recording this
  // zms cannot read (loadEventData builds local paths only). Such an advance
  // degrades to failed frames; it is not re-routed to the serving server the
  // way the video.js player's reload path is. zms self-advance is effectively
  // same-server (or shared-storage) only; routing it is a separate change.
  EventHint ui_next_hint;
  EventHint ui_prev_hint;
  bool ui_managed;
  // When this stream reached the end of an event and began waiting for the UI
  // to say which event follows it, and in which direction. TimePoint() when
  // not waiting.
  TimePoint hint_wait_start;
  bool hint_wait_prev;
  // Set when we paused at the end of an event because the UI's filter had
  // nothing further, or the UI sent no hint in time. See HintResumes.
  bool end_stop;
  bool end_stop_prev;

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
  bool waitingForHint() const { return hint_wait_start != TimePoint(); }
  void processCommand(const CmdMsg *msg) override;
  bool sendFrame(Microseconds delta);
  // The current frame as an Image: its jpeg when there is one, otherwise decoded
  // from the event's video and rotated as recorded. Returns nullptr when there is
  // neither; failed is then set if the video exists but the frame would not decode.
  Image *loadFrameImage(std::unique_ptr<Image> &owned_image, int &pre_scaled_by, bool &failed);

 public:
  EventStream() :
    mode(DEFAULT_MODE),
    forceEventChange(false),
    ui_managed(false),
    hint_wait_prev(false),
    end_stop(false),
    end_stop_prev(false),
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
