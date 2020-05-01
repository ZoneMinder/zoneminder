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

#include "zm_image.h"
#include "zm_stream.h"
#include "zm_video.h"
#include "zm_ffmpeg_input.h"
#include "zm_monitor.h"
#include "zm_storage.h"

#ifdef __cplusplus
extern "C" {
#endif
#include "libavformat/avformat.h"
#include "libavformat/avio.h"
#include "libavcodec/avcodec.h"
#ifdef __cplusplus
}
#endif

class EventStream : public StreamBase {
  public:
    typedef enum { MODE_NONE, MODE_SINGLE, MODE_ALL, MODE_ALL_GAPLESS } StreamMode;

  protected:
    struct FrameData {
      //unsigned long   id;
      double          timestamp;
      double          offset;
      double          delta;
      bool            in_db;
    };

    struct EventData {
      uint64_t  event_id;
      unsigned long   monitor_id;
      unsigned long   storage_id;
      unsigned long   frame_count;
      time_t          start_time;
      double          duration;
      char            path[PATH_MAX];
      int             n_frames;
      FrameData       *frames;
      char            video_file[PATH_MAX];
      Storage::Schemes  scheme;
      int             SaveJPEGs;
      Monitor::Orientation Orientation;
    };

  protected:
    static const int STREAM_PAUSE_WAIT = 250000; // Microseconds

    static const StreamMode DEFAULT_MODE = MODE_SINGLE;

    StreamMode mode;
    bool forceEventChange;

    int curr_frame_id;
    double curr_stream_time;
    bool  send_frame;
    struct timeval start;     // clock time when started the event

    EventData *event_data;

  protected:
    bool loadEventData( uint64_t event_id );
    bool loadInitialEventData( uint64_t init_event_id, unsigned int init_frame_id );
    bool loadInitialEventData( int monitor_id, time_t event_time );

    bool checkEventLoaded();
    void processCommand( const CmdMsg *msg );
    bool sendFrame( int delta_us );

  public:
    EventStream() :
      mode(DEFAULT_MODE),
      forceEventChange(false),
      curr_frame_id(0),
      curr_stream_time(0.0),
      send_frame(false),
      event_data(0),
      storage(NULL),
      ffmpeg_input(NULL),
      // Used when loading frames from an mp4
      input_codec_context(0),
      input_codec(0)
    {}
    ~EventStream() {
        if ( event_data ) {
          if ( event_data->frames ) {
            delete[] event_data->frames;
            event_data->frames = NULL;
          }
          delete event_data;
          event_data = NULL;
        }
        if ( monitor ) {
          delete monitor;
          monitor = NULL;
        }
        if ( storage ) {
          delete storage;
          storage = NULL;
        }
        if ( ffmpeg_input ) {
          delete ffmpeg_input;
          ffmpeg_input = NULL;
        }
    }
    void setStreamStart( uint64_t init_event_id, unsigned int init_frame_id );
    void setStreamStart( int monitor_id, time_t event_time );
    void setStreamMode( StreamMode p_mode ) {
      mode = p_mode;
    }
    void runStream();
    Image *getImage();
  private:
    Storage *storage;
    FFmpeg_Input  *ffmpeg_input;
    AVCodecContext *input_codec_context;
    AVCodec *input_codec;
};

#endif // ZM_EVENTSTREAM_H
