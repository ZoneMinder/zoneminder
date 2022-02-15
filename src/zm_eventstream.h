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

class EventStream : public StreamBase {
  public:
    typedef enum { MODE_NONE, MODE_SINGLE, MODE_ALL, MODE_ALL_GAPLESS } StreamMode;
    static const std::string StreamMode_Strings[4];

  protected:
    struct FrameData {
      //unsigned long   id;
      SystemTimePoint timestamp;
      Microseconds offset;
      Microseconds delta;
      bool in_db;
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
      Microseconds frames_duration;
      std::string path;
      int             n_frames;       // # of frame rows returned from database
      FrameData       *frames;
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

    int curr_frame_id;
    SystemTimePoint curr_stream_time;
    bool  send_frame;
    TimePoint start;     // clock time when started the event

    EventData *event_data;

  protected:
    bool loadEventData(uint64_t event_id);
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
        if ( event_data ) {
          if ( event_data->frames ) {
            delete[] event_data->frames;
            event_data->frames = nullptr;
          }
          delete event_data;
          event_data = nullptr;
        }
        if ( storage ) {
          delete storage;
          storage = nullptr;
        }
        if ( ffmpeg_input ) {
          delete ffmpeg_input;
          ffmpeg_input = nullptr;
        }
    }
    void setStreamStart(uint64_t init_event_id, int init_frame_id);
    void setStreamStart(int monitor_id, time_t event_time);
    void setStreamMode(StreamMode p_mode) { mode = p_mode; }
    void runStream() override;
    Image *getImage();
  private:
    bool send_file(const std::string &filepath);
    bool send_buffer(uint8_t * buffer, int size);
    Storage *storage;
    FFmpeg_Input  *ffmpeg_input;
};

#endif // ZM_EVENTSTREAM_H
