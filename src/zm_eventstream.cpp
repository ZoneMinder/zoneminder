//
// ZoneMinder Event Stream Class Implementation
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
//
#include "zm_eventstream.h"

#include "zm_db.h"
#include "zm_image.h"
#include "zm_logger.h"
#include "zm_sendfile.h"
#include "zm_signal.h"
#include "zm_storage.h"
#include <arpa/inet.h>
#include <sys/stat.h>

#ifdef __FreeBSD__
#include <netinet/in.h>
#endif

const std::string EventStream::StreamMode_Strings[4] = {
  "None",
  "Single",
  "All",
  "Gapless"
};

constexpr Milliseconds EventStream::STREAM_PAUSE_WAIT;

bool EventStream::loadInitialEventData(int monitor_id, SystemTimePoint event_time) {
  std::string sql = stringtf("SELECT `Id` FROM `Events` WHERE "
                             "`MonitorId` = %d AND unix_timestamp(`EndDateTime`) > %ld "
                             "ORDER BY `Id` ASC LIMIT 1", monitor_id, std::chrono::system_clock::to_time_t(event_time));

  MYSQL_RES *result = zmDbFetch(sql);
  if (!result)
    exit(-1);

  MYSQL_ROW dbrow = mysql_fetch_row(result);

  if ( mysql_errno(&dbconn) ) {
    Error("Can't fetch row: %s", mysql_error(&dbconn));
    exit(mysql_errno(&dbconn));
  }

  uint64_t init_event_id = atoll(dbrow[0]);

  mysql_free_result(result);

  loadEventData(init_event_id);

  if (event_time.time_since_epoch() == Seconds(0)) {
    curr_stream_time = event_time;
    curr_frame_id = 1; // curr_frame_id is 1-based
    if (event_time >= event_data->start_time) {
      Debug(2, "event time is after event start");
      for (unsigned int i = 0; i < event_data->frame_count; i++) {
        //Info( "eft %d > et %d", event_data->frames[i].timestamp, event_time );
        if (event_data->frames[i].timestamp >= event_time) {
          curr_frame_id = i + 1;
          Debug(3, "Set curr_stream_time: %.2f, curr_frame_id: %ld",
                FPSeconds(curr_stream_time.time_since_epoch()).count(),
                curr_frame_id);
          break;
        }
      } // end foreach frame
      Debug(3, "Skipping %ld frames", event_data->frame_count);
    } else {
      Warning("Requested an event time less than the start of the event. event_time %" PRIi64 " < start_time %" PRIi64,
              static_cast<int64>(std::chrono::duration_cast<Seconds>(event_time.time_since_epoch()).count()),
              static_cast<int64>(std::chrono::duration_cast<Seconds>(event_data->start_time.time_since_epoch()).count()));
    }
  } // end if have a start time
  return true;
} // bool EventStream::loadInitialEventData( int monitor_id, time_t event_time )

bool EventStream::loadInitialEventData(
    uint64_t init_event_id,
    unsigned int init_frame_id
    ) {
  loadEventData(init_event_id);

  if ( init_frame_id ) {
    if ( init_frame_id >= event_data->frame_count ) {
      Error("Invalid frame id specified. %d > %lu", init_frame_id, event_data->frame_count);
      curr_stream_time = event_data->start_time;
      curr_frame_id = 1;
    } else {
      curr_stream_time = event_data->frames[init_frame_id-1].timestamp;
      curr_frame_id = init_frame_id;
    }
  } else {
    curr_stream_time = event_data->start_time;
  }

  return true;
}

bool EventStream::loadEventData(uint64_t event_id) {
  std::string sql = stringtf(
      "SELECT `MonitorId`, `StorageId`, `Frames`, unix_timestamp( `StartDateTime` ) AS StartTimestamp, "
      "unix_timestamp( `EndDateTime` ) AS EndTimestamp, "
      "(SELECT max(`Delta`)-min(`Delta`) FROM `Frames` WHERE `EventId`=`Events`.`Id`) AS FramesDuration, "
      "`DefaultVideo`, `Scheme`, `SaveJPEGs`, `Orientation`+0 FROM `Events` WHERE `Id` = %" PRIu64, event_id);

  MYSQL_RES *result = zmDbFetch(sql);
  if (!result) {
    exit(-1);
  }

  if ( !mysql_num_rows(result) ) {
    Fatal("Unable to load event %" PRIu64 ", not found in DB", event_id);
  }

  MYSQL_ROW dbrow = mysql_fetch_row(result);

  if ( mysql_errno(&dbconn) ) {
    Error("Can't fetch row: %s", mysql_error(&dbconn));
    exit(mysql_errno(&dbconn));
  }

  delete event_data;
  event_data = new EventData;
  event_data->event_id = event_id;

  event_data->monitor_id = atoi(dbrow[0]);
  event_data->storage_id = dbrow[1] ? atoi(dbrow[1]) : 0;
  event_data->frame_count = dbrow[2] == nullptr ? 0 : atoi(dbrow[2]);
  event_data->start_time = SystemTimePoint(Seconds(atoi(dbrow[3])));
  event_data->end_time = dbrow[4] ? SystemTimePoint(Seconds(atoi(dbrow[4]))) : std::chrono::system_clock::now();
  event_data->duration = std::chrono::duration_cast<Microseconds>(event_data->end_time - event_data->start_time);
  event_data->frames_duration =
      std::chrono::duration_cast<Microseconds>(dbrow[5] ? FPSeconds(atof(dbrow[5])) : FPSeconds(0.0));
  event_data->video_file = std::string(dbrow[6]);
  std::string scheme_str = std::string(dbrow[7]);
  if ( scheme_str == "Deep" ) {
    event_data->scheme = Storage::DEEP;
  } else if ( scheme_str == "Medium" ) {
    event_data->scheme = Storage::MEDIUM;
  } else {
    event_data->scheme = Storage::SHALLOW;
  }
  event_data->SaveJPEGs = dbrow[8] == nullptr ? 0 : atoi(dbrow[8]);
  event_data->Orientation = (Monitor::Orientation)(dbrow[9] == nullptr ? 0 : atoi(dbrow[9]));
  mysql_free_result(result);

  if ( !monitor ) {
    monitor = Monitor::Load(event_data->monitor_id, false, Monitor::QUERY);
  } else if ( monitor->Id() != event_data->monitor_id ) {
    monitor = Monitor::Load(event_data->monitor_id, false, Monitor::QUERY);
  }
  if ( !monitor ) {
    Fatal("Unable to load monitor id %d for streaming", event_data->monitor_id);
  }

  if ( !storage ) {
    storage = new Storage(event_data->storage_id);
  } else if ( storage->Id() != event_data->storage_id ) {
    delete storage;
    storage = new Storage(event_data->storage_id);
  }
  const char *storage_path = storage->Path();

  if ( event_data->scheme == Storage::DEEP ) {
    tm event_time = {};
    time_t start_time_t = std::chrono::system_clock::to_time_t(event_data->start_time);
    localtime_r(&start_time_t, &event_time);

    if (storage_path[0] == '/') {
      event_data->path = stringtf("%s/%u/%02d/%02d/%02d/%02d/%02d/%02d",
                                  storage_path, event_data->monitor_id,
                                  event_time.tm_year - 100, event_time.tm_mon + 1, event_time.tm_mday,
                                  event_time.tm_hour, event_time.tm_min, event_time.tm_sec);
    } else {
      event_data->path = stringtf("%s/%s/%u/%02d/%02d/%02d/%02d/%02d/%02d",
                                  staticConfig.PATH_WEB.c_str(), storage_path, event_data->monitor_id,
                                  event_time.tm_year - 100, event_time.tm_mon + 1, event_time.tm_mday,
                                  event_time.tm_hour, event_time.tm_min, event_time.tm_sec);
    }
  } else if (event_data->scheme == Storage::MEDIUM) {
    tm event_time = {};
    time_t start_time_t = std::chrono::system_clock::to_time_t(event_data->start_time);
    localtime_r(&start_time_t, &event_time);

    if (storage_path[0] == '/') {
      event_data->path = stringtf("%s/%u/%04d-%02d-%02d/%" PRIu64,
                                  storage_path, event_data->monitor_id,
                                  event_time.tm_year + 1900, event_time.tm_mon + 1, event_time.tm_mday,
                                  event_data->event_id);
    } else {
      event_data->path = stringtf("%s/%s/%u/%04d-%02d-%02d/%" PRIu64,
                                  staticConfig.PATH_WEB.c_str(), storage_path, event_data->monitor_id,
                                  event_time.tm_year + 1900, event_time.tm_mon + 1, event_time.tm_mday,
                                  event_data->event_id);
    }
  } else {
    if (storage_path[0] == '/') {
      event_data->path = stringtf("%s/%u/%" PRIu64, storage_path, event_data->monitor_id, event_data->event_id);
    } else {
      event_data->path = stringtf("%s/%s/%u/%" PRIu64,
                                  staticConfig.PATH_WEB.c_str(), storage_path, event_data->monitor_id,
                                  event_data->event_id);
    }
  }

  double fps = 1.0;
  if ((event_data->frame_count and event_data->duration != Seconds(0))) {
    fps = static_cast<double>(event_data->frame_count) / FPSeconds(event_data->duration).count();
  }
  updateFrameRate(fps);

  sql = stringtf("SELECT `FrameId`, unix_timestamp(`TimeStamp`), `Delta` "
                 "FROM `Frames` WHERE `EventId` = %" PRIu64 " ORDER BY `FrameId` ASC", event_id);

  result = zmDbFetch(sql);
  if (!result) {
    exit(-1);
  }

  event_data->n_frames = mysql_num_rows(result);

  event_data->frames = new FrameData[event_data->frame_count];
  int last_id = 0;
  SystemTimePoint last_timestamp = event_data->start_time;
  Microseconds last_delta = Seconds(0);

  while ( ( dbrow = mysql_fetch_row(result) ) ) {
    int id = atoi(dbrow[0]);
    //timestamp = atof(dbrow[1]);
    Microseconds delta = std::chrono::duration_cast<Microseconds>(FPSeconds(atof(dbrow[2])));
    int id_diff = id - last_id;
    Microseconds frame_delta =
        std::chrono::duration_cast<Microseconds>(id_diff ? (delta - last_delta) / id_diff : (delta - last_delta));

    // Fill in data between bulk frames
    if (id_diff > 1) {
      for (int i = last_id + 1; i < id; i++) {
        // Delta is the time since last frame, no since beginning of Event
        event_data->frames[i - 1].delta = frame_delta;
        event_data->frames[i - 1].timestamp = last_timestamp + ((i - last_id) * frame_delta);
        event_data->frames[i - 1].offset =
            std::chrono::duration_cast<Microseconds>(event_data->frames[i - 1].timestamp - event_data->start_time);
        event_data->frames[i - 1].in_db = false;
        Debug(3, "Frame %d timestamp (%f s), offset (%f s) delta (%f s), in_db (%d)",
              i,
              FPSeconds(event_data->frames[i - 1].timestamp.time_since_epoch()).count(),
              FPSeconds(event_data->frames[i - 1].offset).count(),
              FPSeconds(event_data->frames[i - 1].delta).count(),
              event_data->frames[i - 1].in_db);
      }
    }
    event_data->frames[id - 1].timestamp = event_data->start_time + delta;
    event_data->frames[id - 1].offset = delta;
    event_data->frames[id - 1].delta = frame_delta;
    event_data->frames[id - 1].in_db = true;
    last_id = id;
    last_delta = delta;
    last_timestamp = event_data->frames[id-1].timestamp;
    Debug(3, "Frame %d timestamp (%f s), offset (%f s), delta(%f s), in_db(%d)",
          id,
          FPSeconds(event_data->frames[id - 1].timestamp.time_since_epoch()).count(),
          FPSeconds(event_data->frames[id - 1].offset).count(),
          FPSeconds(event_data->frames[id - 1].delta).count(),
          event_data->frames[id - 1].in_db);
  }
  // Incomplete events might not have any frame data
  event_data->last_frame_id = last_id;

  if ( mysql_errno(&dbconn) ) {
    Error("Can't fetch row: %s", mysql_error(&dbconn));
    exit(mysql_errno(&dbconn));
  }
  mysql_free_result(result);

  if (!event_data->video_file.empty() || (monitor->GetOptVideoWriter() > 0)) {
    if (event_data->video_file.empty()) {
      event_data->video_file = stringtf("%" PRIu64 "-%s", event_data->event_id, "video.mp4");
    }

    std::string filepath = event_data->path + "/" + event_data->video_file;
    Debug(1, "Loading video file from %s", filepath.c_str());
    delete ffmpeg_input;

    ffmpeg_input = new FFmpeg_Input();
    if (ffmpeg_input->Open(filepath.c_str()) < 0) {
      Warning("Unable to open ffmpeg_input %s", filepath.c_str());
      delete ffmpeg_input;
      ffmpeg_input = nullptr;
    }
  }

  // Not sure about this
  if ( forceEventChange || mode == MODE_ALL_GAPLESS ) {
    if ( replay_rate > 0 )
      curr_stream_time = event_data->frames[0].timestamp;
    else
      curr_stream_time = event_data->frames[event_data->last_frame_id-1].timestamp;
  }
  Debug(2, "Event: %" PRIu64 ", Frames: %ld, Last Frame ID (%ld, Duration: %.2f s Frames Duration: %.2f s",
        event_data->event_id,
        event_data->frame_count,
        event_data->last_frame_id,
        FPSeconds(event_data->duration).count(),
        FPSeconds(event_data->frames_duration).count());

  return true;
} // bool EventStream::loadEventData( int event_id )

void EventStream::processCommand(const CmdMsg *msg) {
  Debug(2, "Got message, type %d, msg %d", msg->msg_type, msg->msg_data[0]);
  // Check for incoming command
  switch ( (MsgCommand)msg->msg_data[0] ) {
    case CMD_PAUSE :
        Debug(1, "Got PAUSE command");

        // Set paused flag
        paused = true;
        replay_rate = ZM_RATE_BASE;
        last_frame_sent = now;
        break;
    case CMD_PLAY :
        Debug(1, "Got PLAY command");
        if ( paused ) {
          paused = false;
        }

        // If we are in single event mode and at the last frame, replay the current event
        if (
            (mode == MODE_SINGLE || mode == MODE_NONE)
            &&
            ((unsigned int)curr_frame_id == event_data->last_frame_id)
            ) {
          Debug(1, "Was in single or no replay mode, and at last frame, so jumping to 1st frame");
          curr_frame_id = 1;
        } else {
          Debug(1, "mode is %s, current frame is %ld, frame count is %ld, last frame id is %ld",
                StreamMode_Strings[(int) mode].c_str(),
                curr_frame_id,
                event_data->frame_count,
                event_data->last_frame_id);
        }

        replay_rate = ZM_RATE_BASE;
        break;
    case CMD_VARPLAY :
        Debug(1, "Got VARPLAY command");
        if ( paused ) {
          paused = false;
        }
        replay_rate = ntohs(((unsigned char)msg->msg_data[2]<<8)|(unsigned char)msg->msg_data[1])-32768;
        if ( replay_rate > 50 * ZM_RATE_BASE ) {
          Warning("requested replay rate (%d) is too high. We only support up to 50x", replay_rate);
          replay_rate = 50 * ZM_RATE_BASE;
        } else if ( replay_rate < -50*ZM_RATE_BASE ) {
          Warning("requested replay rate (%d) is too low. We only support up to -50x", replay_rate);
          replay_rate = -50 * ZM_RATE_BASE;
        }
        break;
    case CMD_STOP :
        Debug(1, "Got STOP command");
        paused = false;
        break;
    case CMD_FASTFWD :
        Debug(1, "Got FAST FWD command");
        if ( paused ) {
          paused = false;
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
            Debug(1,"Defaulting replay_rate to 2*ZM_RATE_BASE because it is %d", replay_rate);
            replay_rate = 2 * ZM_RATE_BASE;
            break;
        }
        break;
    case CMD_SLOWFWD :
        paused = true;
        replay_rate = ZM_RATE_BASE;
        step = 1;
        if ( (unsigned int)curr_frame_id < event_data->last_frame_id )
          curr_frame_id += 1;
        Debug(1, "Got SLOWFWD command new frame id %ld", curr_frame_id);
        break;
    case CMD_SLOWREV :
        paused = true;
        replay_rate = ZM_RATE_BASE;
        step = -1;
        curr_frame_id -= 1;
        if ( curr_frame_id < 1 ) curr_frame_id = 1;
        Debug(1, "Got SLOWREV command new frame id %ld", curr_frame_id);
        break;
    case CMD_FASTREV :
        Debug(1, "Got FAST REV command");
        paused = false;
        // Set play rate
        switch ( replay_rate ) {
          case -1 * ZM_RATE_BASE :
            replay_rate = -2 * ZM_RATE_BASE;
            break;
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
            replay_rate = -1 * ZM_RATE_BASE;
            break;
        }
        break;
    case CMD_ZOOMIN :
        x = ((unsigned char)msg->msg_data[1]<<8)|(unsigned char)msg->msg_data[2];
        y = ((unsigned char)msg->msg_data[3]<<8)|(unsigned char)msg->msg_data[4];
        Debug(1, "Got ZOOM IN command, to %d,%d", x, y);
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
        send_frame = true;
        break;
    case CMD_ZOOMOUT :
        Debug(1, "Got ZOOM OUT command");
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
        send_frame = true;
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
    case CMD_PREV :
        Debug(1, "Got PREV command");
        if ( replay_rate >= 0 )
          curr_frame_id = 0;
        else
          curr_frame_id = event_data->last_frame_id+1;
        paused = false;
        forceEventChange = true;
        break;
    case CMD_NEXT :
        Debug(1, "Got NEXT command");
        if ( replay_rate >= 0 )
          curr_frame_id = event_data->last_frame_id+1;
        else
          curr_frame_id = 0;
        paused = false;
        forceEventChange = true;
        break;
    case CMD_SEEK :
      {
        double int_part = ((unsigned char) msg->msg_data[1] << 24) | ((unsigned char) msg->msg_data[2] << 16)
            | ((unsigned char) msg->msg_data[3] << 8) | (unsigned char) msg->msg_data[4];
        double dec_part = ((unsigned char) msg->msg_data[5] << 24) | ((unsigned char) msg->msg_data[6] << 16)
            | ((unsigned char) msg->msg_data[7] << 8) | (unsigned char) msg->msg_data[8];

        FPSeconds offset = FPSeconds(int_part + dec_part / 1000000.0);
        if (offset < Seconds(0)) {
          Warning("Invalid offset, not seeking");
          break;
        }

        // This should get us close, but not all frames will have the same duration
        curr_frame_id = (int) (event_data->frame_count * offset / event_data->duration) + 1;
        if (event_data->frames[curr_frame_id - 1].offset > offset) {
          while ((curr_frame_id--) && (event_data->frames[curr_frame_id - 1].offset > offset)) {}
        } else if (event_data->frames[curr_frame_id - 1].offset < offset) {
          while ((curr_frame_id++) && (event_data->frames[curr_frame_id - 1].offset > offset)) {}
        }

        if ( curr_frame_id < 1 ) {
          curr_frame_id = 1;
        } else if ( (unsigned long)curr_frame_id > event_data->last_frame_id ) {
          curr_frame_id = event_data->last_frame_id;
        }

        curr_stream_time = event_data->frames[curr_frame_id-1].timestamp;
        Debug(1, "Got SEEK command, to %f s (new current frame id: %ld offset %f s)",
              FPSeconds(offset).count(),
              curr_frame_id,
              FPSeconds(event_data->frames[curr_frame_id - 1].offset).count());
        send_frame = true;
        break;
      }
    case CMD_QUERY :
        Debug(1, "Got QUERY command, sending STATUS");
        break;
    case CMD_QUIT :
        Info("User initiated exit - CMD_QUIT");
        break;
    default :
        // Do nothing, for now
        break;
  }

  struct {
    uint64_t event_id;
    Microseconds duration;
    Microseconds progress;
    int rate;
    int zoom;
    bool paused;
  } status_data = {};

  status_data.event_id = event_data->event_id;
  status_data.duration = event_data->duration;
  status_data.progress = event_data->frames[curr_frame_id-1].offset;
  status_data.rate = replay_rate;
  status_data.zoom = zoom;
  status_data.paused = paused;
  Debug(2, "Event:%" PRIu64 ", Duration %f, Paused:%d, progress:%f Rate:%d, Zoom:%d",
        status_data.event_id,
        FPSeconds(status_data.duration).count(),
        status_data.paused,
        FPSeconds(status_data.progress).count(),
        status_data.rate,
        status_data.zoom);

  DataMsg status_msg;
  status_msg.msg_type = MSG_DATA_EVENT;
  memcpy(&status_msg.msg_data, &status_data, sizeof(status_data));
  Debug(1, "Size of msg %zu", sizeof(status_data));
  if ( sendto(sd, &status_msg, sizeof(status_msg), MSG_DONTWAIT, (sockaddr *)&rem_addr, sizeof(rem_addr)) < 0 ) {
    //if ( errno != EAGAIN )
    {
      Error("Can't sendto on sd %d: %s", sd, strerror(errno));
      //exit(-1);
    }
  }

  // quit after sending a status, if this was a quit request
  if (static_cast<MsgCommand>(msg->msg_data[0]) == CMD_QUIT) {
    exit(0);
  }

  double fps = 1.0;
  if ((event_data->frame_count and event_data->duration != Seconds(0))) {
    fps = static_cast<double>(event_data->frame_count) / FPSeconds(event_data->duration).count();
  }
  updateFrameRate(fps);
}  // void EventStream::processCommand(const CmdMsg *msg)

bool EventStream::checkEventLoaded() {
  std::string sql;

  if ( curr_frame_id <= 0 ) {
    sql = stringtf(
        "SELECT `Id` FROM `Events` WHERE `MonitorId` = %d AND `Id` < %" PRIu64 " ORDER BY `Id` DESC LIMIT 1",
        event_data->monitor_id, event_data->event_id);
  } else if ( (unsigned int)curr_frame_id > event_data->last_frame_id ) {
    if (event_data->end_time.time_since_epoch() == Seconds(0)) {
      // We are viewing an in-process event, so just reload it.
      loadEventData(event_data->event_id);
      if ( (unsigned int)curr_frame_id > event_data->last_frame_id )
        curr_frame_id = event_data->last_frame_id;
      return false;
    }
    sql = stringtf(
        "SELECT `Id` FROM `Events` WHERE `MonitorId` = %d AND `Id` > %" PRIu64 " ORDER BY `Id` ASC LIMIT 1",
        event_data->monitor_id, event_data->event_id);
  } else {
    // No event change required
    Debug(3, "No event change required, as curr frame %ld <=> event frames %lu",
        curr_frame_id, event_data->frame_count);
    return false;
  }

  // Event change required.
  if ( forceEventChange || ( (mode != MODE_SINGLE) && (mode != MODE_NONE) ) ) {
    Debug(1, "Checking for next event %s", sql.c_str());

    MYSQL_RES *result = zmDbFetch(sql);
    if (!result) {
      exit(-1);
    }

    if ( mysql_num_rows(result) != 1 ) {
      Debug(1, "No rows returned for %s", sql.c_str());
    }
    MYSQL_ROW dbrow = mysql_fetch_row(result);

    if ( mysql_errno(&dbconn)) {
      Error("Can't fetch row: %s", mysql_error(&dbconn));
      exit(mysql_errno(&dbconn));
    }

    if ( dbrow ) {
      uint64_t event_id = atoll(dbrow[0]);
      Debug(1, "Loading new event %" PRIu64, event_id);

      loadEventData(event_id);

      if ( replay_rate < 0 )  // rewind
        curr_frame_id = event_data->last_frame_id;
      else
        curr_frame_id = 1;
      Debug(2, "New frame id = %ld", curr_frame_id);
      start = std::chrono::steady_clock::now();
      return true;
    } else {
      Debug(2, "No next event loaded using %s. Pausing", sql.c_str());
      if ( curr_frame_id <= 0 )
        curr_frame_id = 1;
      else
        curr_frame_id = event_data->frame_count;
      paused = true;
      sendTextFrame("No more event data found");
    }  // end if found a new event or not
    mysql_free_result(result);
    forceEventChange = false;
  } else {
    Debug(2, "Pausing because mode is %s", StreamMode_Strings[mode].c_str());
    if ( curr_frame_id <= 0 )
      curr_frame_id = 1;
    else
      curr_frame_id = event_data->last_frame_id;
    paused = true;
  }
  return false;
}  // void EventStream::checkEventLoaded()

Image * EventStream::getImage( ) {
  std::string path = stringtf(staticConfig.capture_file_format.c_str(), event_data->path.c_str(), curr_frame_id);
  Debug(2, "EventStream::getImage path(%s) from %s frame(%ld) ", path.c_str(), event_data->path.c_str(), curr_frame_id);
  Image *image = new Image(path.c_str());
  return image;
}

bool EventStream::sendFrame(Microseconds delta_us) {
  Debug(2, "Sending frame %ld", curr_frame_id);

  std::string filepath;
  struct stat filestat = {};

  // This needs to be abstracted.  If we are saving jpgs, then load the capture file.
  // If we are only saving analysis frames, then send that.
  if ((frame_type == FRAME_ANALYSIS) && (event_data->SaveJPEGs & 2)) {
    filepath = stringtf(staticConfig.analyse_file_format.c_str(), event_data->path.c_str(), curr_frame_id);
    if (stat(filepath.c_str(), &filestat) < 0) {
      Debug(1, "analyze file %s not found will try to stream from other", filepath.c_str());
      filepath = stringtf(staticConfig.capture_file_format.c_str(), event_data->path.c_str(), curr_frame_id);
      if (stat(filepath.c_str(), &filestat) < 0) {
        Debug(1, "capture file %s not found either", filepath.c_str());
        filepath = "";
      }
    }
  } else if (event_data->SaveJPEGs & 1) {
    filepath = stringtf(staticConfig.capture_file_format.c_str(), event_data->path.c_str(), curr_frame_id);
  } else if (!ffmpeg_input) {
    Fatal("JPEGS not saved. zms is not capable of streaming jpegs from mp4 yet");
    return false;
  }

  if ( type == STREAM_MPEG ) {
    Image image(filepath.c_str());

    Image *send_image = prepareImage(&image);

    if ( !vid_stream ) {
      vid_stream = new VideoStream("pipe:", format, bitrate, effective_fps,
          send_image->Colours(), send_image->SubpixelOrder(), send_image->Width(), send_image->Height());
      fprintf(stdout, "Content-type: %s\r\n\r\n", vid_stream->MimeType());
      vid_stream->OpenStream();
    }
    vid_stream->EncodeFrame(send_image->Buffer(),
                            send_image->Size(),
                            config.mpeg_timed_frames,
                            delta_us.count() * 1000);
  } else {
    bool send_raw = (type == STREAM_JPEG) && ((scale >= ZM_SCALE_BASE) && (zoom == ZM_SCALE_BASE)) && !filepath.empty();

    fprintf(stdout, "--" BOUNDARY "\r\n");

    if (send_raw) {
      if (!send_file(filepath)) {
        Error("Can't send %s: %s", filepath.c_str(), strerror(errno));
        return false;
      }
    } else {
      Image *image = nullptr;

      if (!filepath.empty()) {
        image = new Image(filepath.c_str());
      } else if ( ffmpeg_input ) {
        // Get the frame from the mp4 input
        FrameData *frame_data = &event_data->frames[curr_frame_id-1];
        AVFrame *frame =
            ffmpeg_input->get_frame(ffmpeg_input->get_video_stream_id(), FPSeconds(frame_data->offset).count());
        if ( frame ) {
          image = new Image(frame);
          //av_frame_free(&frame);
        } else {
          Error("Failed getting a frame.");
          return false;
        }

        // when stored as an mp4, we just have the rotation as a flag in the headers
        // so we need to rotate it before outputting
        if (
            (monitor->GetOptVideoWriter() == Monitor::PASSTHROUGH)
            and
            (event_data->Orientation != Monitor::ROTATE_0)
            ) {
          Debug(2, "Rotating image %d", event_data->Orientation);
          switch ( event_data->Orientation ) {
            case Monitor::ROTATE_0 :
              // No action required
              break;
            case Monitor::ROTATE_90 :
            case Monitor::ROTATE_180 :
            case Monitor::ROTATE_270 :
              image->Rotate((event_data->Orientation-1)*90);
              break;
            case Monitor::FLIP_HORI :
            case Monitor::FLIP_VERT :
              image->Flip(event_data->Orientation==Monitor::FLIP_HORI);
              break;
            default:
              Error("Invalid Orientation: %d", event_data->Orientation);
          }
        } else {
          Debug(2, "Not Rotating image %d", event_data->Orientation);
        } // end if have rotation
      } else {
        Error("Unable to get a frame");
        return false;
      }

      Image *send_image = prepareImage(image);
      static unsigned char temp_img_buffer[ZM_MAX_IMAGE_SIZE];
      int img_buffer_size = 0;
      uint8_t *img_buffer = temp_img_buffer;

      switch ( type ) {
        case STREAM_JPEG :
          send_image->EncodeJpeg(img_buffer, &img_buffer_size);
          fputs("Content-Type: image/jpeg\r\n", stdout);
          break;
        case STREAM_ZIP :
          unsigned long zip_buffer_size;
          send_image->Zip(img_buffer, &zip_buffer_size);
          img_buffer_size = zip_buffer_size;
          fputs("Content-Type: image/x-rgbz\r\n", stdout);
          break;
        case STREAM_RAW :
          img_buffer = send_image->Buffer();
          img_buffer_size = send_image->Size();
          fputs("Content-Type: image/x-rgb\r\n", stdout);
          break;
        default:
          Fatal("Unexpected frame type %d", type);
          break;
      }
      send_buffer(img_buffer, img_buffer_size);
      delete image;
      image = nullptr;
    }  // end if send_raw or not

  }  // end if stream MPEG or other

  fputs("\r\n", stdout);
  fflush(stdout);
  last_frame_sent = now;
  return true;
}  // bool EventStream::sendFrame( int delta_us )

void EventStream::runStream() {
  openComms();

  //checkInitialised();

  if (type == STREAM_JPEG)
    fputs("Content-Type: multipart/x-mixed-replace;boundary=" BOUNDARY "\r\n\r\n", stdout);

  if (!event_data) {
    sendTextFrame("No event data found");
    zm_terminate = true;
    return;
  }

  double fps = 1.0;
  if ((event_data->frame_count and event_data->duration != Seconds(0))) {
    fps = static_cast<double>(event_data->frame_count) / FPSeconds(event_data->duration).count();
  }
  updateFrameRate(fps);

  start = std::chrono::steady_clock::now();

  SystemTimePoint::duration last_frame_offset = Seconds(0);
  SystemTimePoint::duration time_to_event = Seconds(0);

  while ( !zm_terminate ) {
    now = std::chrono::steady_clock::now();

    Microseconds delta = Microseconds(0);
    send_frame = false;

    if ( connkey ) {
      // commands may set send_frame to true
      while ( checkCommandQueue() && !zm_terminate ) {
        // The idea is to loop here processing all commands before proceeding.
      }

      // Update modified time of the socket .lock file so that we can tell which ones are stale.
      if (now - last_comm_update > Hours(1)) {
        touch(sock_path_lock);
        last_comm_update = now;
      }
    }

    // Get current frame data
    FrameData *frame_data = &event_data->frames[curr_frame_id-1];

    if ( !paused ) {
      // Figure out if we should send this frame
      Debug(3, "not paused at cur_frame_id (%ld-1) mod frame_mod(%d)", curr_frame_id, frame_mod);
      // If we are streaming and this frame is due to be sent
      // frame mod defaults to 1 and if we are going faster than max_fps will get multiplied by 2
      // so if it is 2, then we send every other frame, if is it 4 then every fourth frame, etc.

      if ( (frame_mod == 1) || (((curr_frame_id-1)%frame_mod) == 0) ) {
        send_frame = true;
      }
    } else if ( step != 0 ) {
      Debug(2, "Paused with step %d", step);
      // We are paused and are just stepping forward or backward one frame
      step = 0;
      send_frame = true;
    } else if ( !send_frame ) {
      // We are paused, not stepping and doing nothing, meaning that comms didn't set send_frame to true
      if (now - last_frame_sent > MAX_STREAM_DELAY) {
        // Send keepalive
        Debug(2, "Sending keepalive frame");
        send_frame = true;
      }
    }  // end if streaming stepping or doing nothing

    // time_to_event > 0 means that we are not in the event
    if (time_to_event > Seconds(0) and mode == MODE_ALL) {
      TimePoint::duration time_since_last_send = now - last_frame_sent;
      Debug(1, "Time since last send = %.2f s", FPSeconds(time_since_last_send).count());
      if (time_since_last_send > Seconds(1)) {
        char frame_text[64];

        snprintf(frame_text, sizeof(frame_text), "Time to %s event = %f s",
                 (replay_rate > 0 ? "next" : "previous"),
                 FPSeconds(time_to_event).count());

        if (!sendTextFrame(frame_text)) {
          zm_terminate = true;
        }

        send_frame = false; // In case keepalive was set
      }

      // FIXME ICON But we are not paused.  We are somehow still in the event?
      Milliseconds sleep_time = std::chrono::duration_cast<Milliseconds>(
          (replay_rate > 0 ? 1 : -1) * ((1.0L * replay_rate * STREAM_PAUSE_WAIT) / ZM_RATE_BASE));
      //double sleep_time = (replay_rate * STREAM_PAUSE_WAIT)/(ZM_RATE_BASE * 1000000);
      //// ZM_RATE_BASE == 100, and 1x replay_rate is 100
      //double sleep_time = ((replay_rate/ZM_RATE_BASE) * STREAM_PAUSE_WAIT)/1000000;
      if (sleep_time == Seconds(0)) {
        sleep_time += STREAM_PAUSE_WAIT;
      }

      curr_stream_time += sleep_time;
      time_to_event -= sleep_time;
      Debug(2, "Sleeping (%" PRIi64 " ms) because we are not at the next event yet, adding %" PRIi64 " ms",
            static_cast<int64>(Milliseconds(STREAM_PAUSE_WAIT).count()),
            static_cast<int64>(Milliseconds(sleep_time).count()));
      std::this_thread::sleep_for(STREAM_PAUSE_WAIT);

      //curr_stream_time += (1.0L * replay_rate * STREAM_PAUSE_WAIT)/(ZM_RATE_BASE * 1000000);
      //}
      continue;
    } // end if !in_event

    if (send_frame) {
      if (!sendFrame(delta)) {
        zm_terminate = true;
        break;
      }
    }

    curr_stream_time = frame_data->timestamp;

    if (!paused) {
      // delta is since the last frame
      delta = std::chrono::duration_cast<Microseconds>(frame_data->delta);
      Debug(3, "frame delta %" PRIi64 "us ",
            static_cast<int64>(std::chrono::duration_cast<Microseconds>(delta).count()));

      // if effective > base we should speed up frame delivery
      if (base_fps < effective_fps) {
        delta = std::chrono::duration_cast<Microseconds>((delta * base_fps) / effective_fps);
        Debug(3, "delta %" PRIi64 " us = base_fps (%f) / effective_fps (%f)",
            static_cast<int64>(std::chrono::duration_cast<Microseconds>(delta).count()),
            base_fps,
            effective_fps);

        // but must not exceed maxfps
        delta = std::max(delta, Microseconds(lround(Microseconds::period::den / maxfps)));
        Debug(3, "delta %" PRIi64 " us = base_fps (%f) / effective_fps (%f) from 30fps",
            static_cast<int64>(std::chrono::duration_cast<Microseconds>(delta).count()),
            base_fps,
            effective_fps);
      }

      // +/- 1? What if we are skipping frames?
      curr_frame_id += (replay_rate>0) ? frame_mod : -1*frame_mod;
      // sending the frame may have taken some time, so reload now
      now = std::chrono::steady_clock::now();

      // we incremented by replay_rate, so might have jumped past frame_count
      if ( (mode == MODE_SINGLE) && (
            (curr_frame_id < 1 )
            ||
            ((unsigned int)curr_frame_id >= event_data->frame_count)
            )
         ) {
        Debug(2, "Have mode==MODE_SINGLE and at end of event, looping back to start");
        curr_frame_id = 1;
        // Have to reset start to now when replaying
        start = now;
      }

      if ((unsigned int)curr_frame_id <= event_data->frame_count) {
        frame_data = &event_data->frames[curr_frame_id-1];

        // frame_data->delta is the time since last frame as a float in seconds
        // but what if we are skipping frames? We need the distance from the last frame sent
        // Also, what about reverse? needs to be absolute value

        // There are two ways to go about this, not sure which is correct.
        // you can calculate the relationship between now and the start
        // or calc the relationship from the last frame.  I think from the start is better as it self-corrects
        //
        if (last_frame_offset != Seconds(0)) {
          // We assume that we are going forward and the next frame is in the future.
          delta = std::chrono::duration_cast<Microseconds>(frame_data->offset - (now - start));

          Debug(2, "New delta: now - start = %" PRIu64 " us offset %" PRIi64 " us- elapsed = %" PRIu64 " us",
                static_cast<int64>(std::chrono::duration_cast<Microseconds>(now - start).count()),
                static_cast<int64>(std::chrono::duration_cast<Microseconds>(frame_data->offset).count()),
                static_cast<int64>(std::chrono::duration_cast<Microseconds>(delta).count()));
        } else {
          Debug(2, "No last frame_offset, no sleep");
          delta = Seconds(0);
        }
        last_frame_offset = frame_data->offset;

        if (send_frame && type != STREAM_MPEG) {
          if (delta != Seconds(0)) {
            if (delta > MAX_SLEEP) {
              Debug(1, "Limiting sleep to %" PRIi64 " ms because calculated sleep is too long: %" PRIi64" us",
                  static_cast<int64>(std::chrono::duration_cast<Milliseconds>(MAX_SLEEP).count()),
                  static_cast<int64>(std::chrono::duration_cast<Microseconds>(delta).count()));
              delta = MAX_SLEEP;
            }

            std::this_thread::sleep_for(delta);
            Debug(3, "Done sleeping: %" PRIi64 " us",
                static_cast<int64>(std::chrono::duration_cast<Microseconds>(delta).count()));
          }
        } // end if need to sleep
      } else {
        Debug(1, "invalid curr_frame_id %ld !< %lu", curr_frame_id, event_data->frame_count);
      }  // end if not at end of event
    } else {
      // Paused
      delta = std::chrono::duration_cast<Microseconds>(FPSeconds(
          ZM_RATE_BASE / ((base_fps ? base_fps : 1) * (replay_rate ? abs(replay_rate * 2) : 2))));

      Debug(2, "Sleeping %" PRIi64 " us because ZM_RATE_BASE (%d) / ( base_fps (%f) * replay_rate (%d)",
            static_cast<int64>(std::chrono::duration_cast<Microseconds>(delta).count()),
            ZM_RATE_BASE,
            (base_fps ? base_fps : 1),
            (replay_rate ? abs(replay_rate * 2) : 0));

      if (delta != Seconds(0)) {
        if (delta > MAX_SLEEP) {
          Debug(1, "Limiting sleep to %" PRIi64 " ms because calculated sleep is too long %" PRIi64,
                static_cast<int64>(std::chrono::duration_cast<Milliseconds>(MAX_SLEEP).count()),
                static_cast<int64>(std::chrono::duration_cast<Microseconds>(delta).count()));
          delta = MAX_SLEEP;
        }

        std::this_thread::sleep_for(delta);
      }
      // We are paused, so might be stepping
      //if ( step != 0 )// Adding 0 is cheaper than an if 0
      // curr_frame_id starts at 1 though, so we might skip the first frame?
      curr_frame_id += step;
    }  // end if !paused

    // Detects when we hit end of event and will load the next event or previous event
    if ( checkEventLoaded() ) {
      // Have change of event

      // This next bit is to determine if we are in the current event time wise
      // and whether to show an image saying how long until the next event.
      if ( replay_rate > 0 ) {
        // This doesn't make sense unless we have hit the end of the event.
        time_to_event = event_data->frames[0].timestamp - curr_stream_time;
        Debug(1, "replay rate (%d) time_to_event (%f s) = frame timestamp (%f s) - curr_stream_time (%f s)",
              replay_rate,
              FPSeconds(time_to_event).count(),
              FPSeconds(event_data->frames[0].timestamp.time_since_epoch()).count(),
              FPSeconds(curr_stream_time.time_since_epoch()).count());

      } else if ( replay_rate < 0 ) {
        time_to_event = curr_stream_time - event_data->frames[event_data->frame_count-1].timestamp;
        Debug(1, "replay rate (%d), time_to_event(%f s) = curr_stream_time (%f s) - frame timestamp (%f s)",
              replay_rate,
              FPSeconds(time_to_event).count(),
              FPSeconds(curr_stream_time.time_since_epoch()).count(),
              FPSeconds(event_data->frames[event_data->frame_count - 1].timestamp.time_since_epoch()).count());
      }  // end if forward or reverse
    }  // end if checkEventLoaded
  }  // end while ! zm_terminate
  if (type == STREAM_MPEG) {
    delete vid_stream;
  }

  closeComms();
} // end void EventStream::runStream()

bool EventStream::send_file(const std::string &filepath) {
  FILE *fdj = nullptr;
  fdj = fopen(filepath.c_str(), "rb");
  if (!fdj) {
    Error("Can't open %s: %s", filepath.c_str(), strerror(errno));
    std::string error_message = stringtf("Can't open %s: %s", filepath.c_str(), strerror(errno));
    return sendTextFrame(error_message.c_str());
  }
#if HAVE_SENDFILE
  static struct stat filestat;
  if (fstat(fileno(fdj), &filestat) < 0) {
    fclose(fdj); /* Close the file handle */
    Error("Failed getting information about file %s: %s", filepath.c_str(), strerror(errno));
    return false;
  }
  if (!filestat.st_size) {
    fclose(fdj); /* Close the file handle */
    Info("File size is zero. Unable to send raw frame %ld: %s", curr_frame_id, strerror(errno));
    return false;
  }
  if (0 > fprintf(stdout, "Content-Length: %d\r\n\r\n", (int)filestat.st_size)) {
    fclose(fdj); /* Close the file handle */
    Info("Unable to send raw frame %ld: %s", curr_frame_id, strerror(errno));
    return false;
  }
  int rc = zm_sendfile(fileno(stdout), fileno(fdj), 0, (int)filestat.st_size);
  if (rc == (int)filestat.st_size) {
    // Success
    fclose(fdj); /* Close the file handle */
    return true;
  }
  Warning("Unable to send raw frame %ld: %s rc %d != %d",
      curr_frame_id, strerror(errno), rc, (int)filestat.st_size);
#endif

  static unsigned char temp_img_buffer[ZM_MAX_IMAGE_SIZE];

  uint8_t *img_buffer = temp_img_buffer;
  int img_buffer_size = fread(img_buffer, 1, sizeof(temp_img_buffer), fdj);
  fclose(fdj); /* Close the file handle */
  if ( !img_buffer_size ) {
    Info("Unable to read raw frame %ld: %s", curr_frame_id, strerror(errno));
    return false;
  }

  return send_buffer(img_buffer, img_buffer_size);
}

bool EventStream::send_buffer(uint8_t* buffer, int size) {
  if ( 0 > fprintf(stdout, "Content-Length: %d\r\n\r\n", size) ) {
    Info("Unable to send raw frame %ld: %s", curr_frame_id, strerror(errno));
    return false;
  }
  int rc = fwrite(buffer, size, 1, stdout);

  if ( 1 != rc ) {
    Error("Unable to send raw frame %ld: %s %d", curr_frame_id, strerror(errno), rc);
    return false;
  }
  return true;
}  // end bool EventStream::send_buffer(uint8_t* buffer, int size)

void EventStream::setStreamStart(
    uint64_t init_event_id,
    unsigned int init_frame_id=0) {
  loadInitialEventData(init_event_id, init_frame_id);
}  // end void EventStream::setStreamStart(init_event_id,init_frame_id=0)

void EventStream::setStreamStart(int monitor_id, time_t event_time) {
  loadInitialEventData(monitor_id, event_time);
}
