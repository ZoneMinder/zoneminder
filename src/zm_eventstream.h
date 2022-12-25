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

#include "soci/soci.h"

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
        in_db(p_in_db)
      {
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
      Microseconds frames_duration;
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

    std::mutex  mutex;
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
      delete event_data;
      delete storage;
      delete ffmpeg_input;
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

namespace soci {

template<> struct type_conversion<EventStream::EventData*>
{
    typedef values base_type;
    static void from_base(const values & v, indicator & ind, EventStream::EventData* event_data)
    {
      event_data->event_id = v.get<uint64_t>("Id");
      event_data->monitor_id = v.get<unsigned int>("MonitorId");
      event_data->storage_id = v.get<unsigned int>("StorageId", 0);
      event_data->frame_count = v.get<int>("Frames", 0);
      event_data->start_time = v.get<SystemTimePoint>("StartTimestamp");
      event_data->end_time = v.get<SystemTimePoint>("EndTimestamp", std::chrono::system_clock::now());
      event_data->duration = std::chrono::duration_cast<Microseconds>(event_data->end_time - event_data->start_time);
      event_data->frames_duration = std::chrono::duration_cast<Microseconds>( 
        FPSeconds(v.get("FramesDuration", 0.0))
      );
      event_data->video_file = v.get<std::string>("DefaultVideo");
      std::string scheme_str = v.get<std::string>("Scheme");
      if ( scheme_str.compare("Deep") == 0 ) {
        event_data->scheme = Storage::DEEP;
      } else if ( scheme_str.compare("Medium") == 0 ) {
        event_data->scheme = Storage::MEDIUM;
      } else {
        event_data->scheme = Storage::SHALLOW;
      }
      event_data->SaveJPEGs = v.get("SaveJPEGs", 0);
      event_data->Orientation = v.get<Monitor::Orientation>("Orientation");
    }
    static void to_base(const EventStream::EventData* event_data, values & v, indicator & ind)
    {
      v.set("Id", event_data->event_id);
      v.set("MonitorId", event_data->monitor_id);
      v.set("StorageId", event_data->storage_id);
      v.set("Frames", event_data->frame_count);
      v.set("StartTimestamp", event_data->start_time);
      v.set("EndTimestamp", event_data->end_time);
      v.set("DefaultVideo", event_data->video_file);
      v.set("SaveJPEGs", event_data->SaveJPEGs);
      v.set<int>("Orientation", static_cast<int>(event_data->Orientation));
      
      switch( event_data->scheme ) {
        case Storage::DEEP:
          v.set("Scheme", std::string("Deep"));
          break;

        case Storage::MEDIUM:
          v.set("Scheme", std::string("Medium"));
          break;

        case Storage::SHALLOW:
        default:
          v.set("Scheme", std::string("Shallow"));
          break;
      }
      ind = i_ok;
    }
};


};

#endif // ZM_EVENTSTREAM_H
