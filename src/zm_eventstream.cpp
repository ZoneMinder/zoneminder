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
#include <algorithm>
#include <arpa/inet.h>
#include <sys/stat.h>

#include <filesystem>

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
                             "`MonitorId` = %d AND unix_timestamp(`EndDateTime`) > %jd "
                             "ORDER BY `Id` ASC LIMIT 1", monitor_id, std::chrono::system_clock::to_time_t(event_time));

  MYSQL_RES *result = zmDbFetch(sql);
  if (!result) exit(-1);

  MYSQL_ROW dbrow = mysql_fetch_row(result);

  if ( mysql_errno(&dbconn) ) {
    Error("Can't fetch row: %s", mysql_error(&dbconn));
    mysql_free_result(result);
    return false;
  }
  if (!mysql_num_rows(result)) {
    Error("Unable to load event using %s", sql.c_str());
    mysql_free_result(result);
    return false;
  }

  uint64_t init_event_id = atoll(dbrow[0]);

  mysql_free_result(result);

  loadEventData(init_event_id);

  // Default to starting at beginning
  curr_stream_time = event_data->start_time;
  curr_frame_id = 1; // curr_frame_id is 1-based

  if (event_time.time_since_epoch() != Seconds(0)) {
    seek(event_time);
  } // end if have a start time
  return true;
} // bool EventStream::loadInitialEventData( int monitor_id, time_t event_time )

bool EventStream::seek(SystemTimePoint event_time) {
  if (event_time < event_data->start_time || event_time > event_data->end_time) {
    Warning("Requested an event time less than the start of the event. event_time %" PRIi64 " < start_time %" PRIi64,
        static_cast<int64>(std::chrono::duration_cast<Seconds>(event_time.time_since_epoch()).count()),
        static_cast<int64>(std::chrono::duration_cast<Seconds>(event_data->start_time.time_since_epoch()).count()));
    return false;
  }

  // Binary search: frames are sorted by timestamp (ascending).
  // Find the first frame whose timestamp >= event_time.
  auto it = std::lower_bound(
    event_data->frames.begin(),
    event_data->frames.end(),
    event_time,
    [](const FrameData &frame, const SystemTimePoint &t) {
      return frame.timestamp < t;
    });

  // If event_time is past all frame timestamps (e.g. at event end_time),
  // step back to the last frame so the delta check below can evaluate it.
  if (it == event_data->frames.end() && it != event_data->frames.begin()) {
    --it;
  }

  // Check if the previous frame's time window covers event_time.
  // Original logic: timestamp + delta >= event_time allows a frame whose
  // timestamp is before the target to match if its display window extends
  // past it.
  if (it != event_data->frames.begin()) {
    auto prev = std::prev(it);
    if (prev->timestamp + prev->delta >= event_time) {
      it = prev;
    }
  }

  if (it != event_data->frames.end() &&
      (it->timestamp >= event_time || it->timestamp + it->delta >= event_time)) {
    curr_frame_id = static_cast<int>(std::distance(event_data->frames.begin(), it)) + 1;
    curr_stream_time = event_time;
    Debug(3, "Set curr_stream_time: %.2f, curr_frame_id: %d",
        FPSeconds(curr_stream_time.time_since_epoch()).count(),
        curr_frame_id);
    return true;
  }
    Warning("Requested an event time less than the start of the event. event_time %" PRIi64 " < start_time %" PRIi64,
        static_cast<int64>(std::chrono::duration_cast<Seconds>(event_time.time_since_epoch()).count()),
        static_cast<int64>(std::chrono::duration_cast<Seconds>(event_data->start_time.time_since_epoch()).count()));
    return false;
}  // end bool EventStream::seek(SystemTimePoint event_time)

bool EventStream::loadInitialEventData(
  uint64_t init_event_id,
  int init_frame_id
) {
  loadEventData(init_event_id);

  if ( init_frame_id ) {
    if ( init_frame_id < 1 || init_frame_id > (int)event_data->frames.size() ) {
      Error("Invalid frame id specified. %d not in [1..%zu]", init_frame_id, event_data->frames.size());
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
                      "unix_timestamp( `EndDateTime` ) AS EndTimestamp, `Length`, "
                      "`DefaultVideo`, `Scheme`, `SaveJPEGs`, `Orientation`+0 FROM `Events` WHERE `Id` = %" PRIu64, event_id);

  MYSQL_RES *result = zmDbFetch(sql);
  if (!result) {
    exit(-1);
  }

  if (!mysql_num_rows(result)) {
    Fatal("Unable to load event %" PRIu64 ", not found in DB", event_id);
  }

  MYSQL_ROW dbrow = mysql_fetch_row(result);

  if (mysql_errno(&dbconn)) {
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
  event_data->duration = std::chrono::duration_cast<Microseconds>(dbrow[5] ? FPSeconds(atof(dbrow[5])) : event_data->end_time - event_data->start_time);
  event_data->video_file = dbrow[6] ? std::string(dbrow[6]) : std::string();
  std::string scheme_str = dbrow[7] ? std::string(dbrow[7]) : std::string();
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

  if (!monitor) {
    monitor = Monitor::Load(event_data->monitor_id, false, Monitor::QUERY);
  } else if (monitor->Id() != event_data->monitor_id) {
    monitor = Monitor::Load(event_data->monitor_id, false, Monitor::QUERY);
  }
  if (!monitor) {
    Fatal("Unable to load monitor id %d for streaming", event_data->monitor_id);
  }

  if (!storage) {
    storage = new Storage(event_data->storage_id);
  } else if (storage->Id() != event_data->storage_id) {
    delete storage;
    storage = new Storage(event_data->storage_id);
  }
  const char *storage_path = storage->Path();

  if (event_data->scheme == Storage::DEEP) {
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
  if (event_data->frame_count < event_data->n_frames) {
    Warning("Event %" PRId64 " has more frames in the Frames table (%d) than in the Event record (%d)",
            event_data->event_id, event_data->n_frames, event_data->frame_count);
    event_data->frame_count = event_data->n_frames;
  }
  event_data->frames.clear();
  event_data->frames.reserve(event_data->frame_count);

  int last_id = 0;
  SystemTimePoint last_timestamp = event_data->start_time;
  Microseconds last_offset = Seconds(0);
  int last_frame_idx = -1;

  // Here are the issues: if showing jpegs, need FrameId.
  // Delta is the time since last frame, not since beginning of Event
  while ((dbrow = mysql_fetch_row(result))) {
    int id = atoi(dbrow[0]);
    //timestamp = atof(dbrow[1]); // timestamp is useless because it's just seconds.
    // What is in the Delta column is distance from StartTime.  We will call that offset.
    Microseconds offset = std::chrono::duration_cast<Microseconds>(FPSeconds(atof(dbrow[2])));
    SystemTimePoint timestamp = event_data->start_time + offset;

    int id_diff = id - last_id;
    Microseconds delta =
      std::chrono::duration_cast<Microseconds>(id_diff ? (offset - last_offset) / id_diff : (offset - last_offset));
    Debug(4, "New delta %f from id_diff %d = id %d - last_id %d offset %f - last)_offset %f",
          FPSeconds(delta).count(), id_diff, id, last_id, FPSeconds(offset).count(), FPSeconds(last_offset).count());

    // Fill in data between bulk frames
    if (id_diff > 1) {
      for (int i = last_id + 1; i < id; i++) {
        auto &frame = event_data->frames.emplace_back(
                       i,
                       last_timestamp + ((i - last_id) * delta),
                       std::chrono::duration_cast<Microseconds>((event_data->frames[last_frame_idx].timestamp - event_data->start_time) + delta),
                       delta,
                       false
                     );
        last_frame_idx = event_data->frames.size() - 1;
        Debug(4, "Frame %d %d timestamp (%f s), offset (%f s) delta (%f s), in_db (%d)",
              i, frame.id,
              FPSeconds(frame.timestamp.time_since_epoch()).count(),
              FPSeconds(frame.offset).count(),
              FPSeconds(frame.delta).count(),
              frame.in_db);
      }
    }
    auto &frame = event_data->frames.emplace_back(id, timestamp, offset, delta, true);
    last_frame_idx = event_data->frames.size() - 1;
    last_id = id;
    last_offset = offset;
    last_timestamp = timestamp;
    Debug(4, "Frame %d timestamp (%f s), offset (%f s), delta(%f s), in_db(%d)",
          id,
          FPSeconds(frame.timestamp.time_since_epoch()).count(),
          FPSeconds(frame.offset).count(),
          FPSeconds(frame.delta).count(),
          frame.in_db);
  } // end foreach db row

  if (event_data->end_time.time_since_epoch() != Seconds(0) and event_data->duration != Seconds(0) and event_data->frame_count > last_id) {
    Microseconds delta;
    if (last_frame_idx < 0) {
      // There were no frames in db
      delta = Microseconds( static_cast<int>(1000000 * base_fps / FPSeconds(event_data->duration).count()) );
      event_data->frames.emplace_back(
                     1,
                     event_data->start_time,
                     Microseconds(0),
                     Microseconds(0),
                     false
                   );
      last_frame_idx = event_data->frames.size() - 1;
      last_id ++;
      last_timestamp = event_data->start_time;
      event_data->frame_count ++;
    } else {
      delta = std::chrono::duration_cast<Microseconds>((event_data->end_time - last_timestamp)/(event_data->frame_count-last_id));
      Debug(1, "Setting delta from endtime %f - %f / frame_count %d - last_id %d", 
              FPSeconds(event_data->end_time.time_since_epoch()).count(),
              FPSeconds(last_timestamp.time_since_epoch()).count(),
              event_data->frame_count,
              last_id
              );
    }

    if (delta > Microseconds(0)) {
      while (event_data->end_time > last_timestamp and !zm_terminate) {
        last_timestamp += delta;
        // Prevent final frame where capture.jpg doesn't actually exist.
        if (event_data->end_time < last_timestamp) break;
        last_id ++;

        auto &frame = event_data->frames.emplace_back(
                       last_id,
                       last_timestamp,
                       event_data->frames[last_frame_idx].offset + delta,
                       delta,
                       false
                     );
        last_frame_idx = event_data->frames.size() - 1;
        Debug(3, "Trailing Frame %d timestamp (%f s), offset (%f s), delta(%f s), in_db(%d)",
              last_id,
              FPSeconds(frame.timestamp.time_since_epoch()).count(),
              FPSeconds(frame.offset).count(),
              FPSeconds(frame.delta).count(),
              frame.in_db);
        event_data->frame_count ++;
      } // end while
    }
  } // end if have endtime

  // Incomplete events might not have any frame data
  event_data->last_frame_id = last_id;

  if (mysql_errno(&dbconn)) {
    Error("Can't fetch row: %s", mysql_error(&dbconn));
    exit(mysql_errno(&dbconn));
  }
  mysql_free_result(result);

  if (!event_data->video_file.empty()) {
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
    if (event_data->frames.empty()) {
      curr_stream_time = event_data->start_time;
    } else if ( replay_rate > 0 ) {
      curr_stream_time = event_data->frames[0].timestamp;
    } else {
      curr_stream_time = event_data->frames[event_data->frames.size()-1].timestamp;
    }
  }
  if (logLevel() >= Logger::DEBUG2) {
    // Query actual frame-span duration from the Frames table and compare
    // against Event Length to diagnose DB queue lag, unclean shutdowns,
    // clock jumps, or bulk frame gaps.
    double frames_duration = 0.0;
    std::string fdsql = stringtf(
      "SELECT max(`Delta`)-min(`Delta`) FROM `Frames` WHERE `EventId` = %" PRIu64,
      event_data->event_id);
    MYSQL_RES *fdresult = zmDbFetch(fdsql);
    if (fdresult) {
      MYSQL_ROW fdrow = mysql_fetch_row(fdresult);
      if (fdrow && fdrow[0])
        frames_duration = atof(fdrow[0]);
      mysql_free_result(fdresult);
    }
    Debug(2, "Event: %" PRIu64 ", Frames: %d, Last Frame ID (%d, Duration: %.2f s Frames Duration: %.2f s",
          event_data->event_id,
          event_data->frame_count,
          event_data->last_frame_id,
          FPSeconds(event_data->duration).count(),
          frames_duration);
  }

  return true;
} // bool EventStream::loadEventData( int event_id )

void EventStream::processCommand(const CmdMsg *msg) {
  Debug(2, "Got message, type %d, msg %d", msg->msg_type, msg->msg_data[0]);

  std::scoped_lock lck{mutex};

  // Check for incoming command
  switch ((MsgCommand)msg->msg_data[0]) {
  case CMD_PAUSE :
    Debug(1, "Got PAUSE command");
    paused = true;
    break;
  case CMD_PLAY : {
    Debug(1, "Got PLAY command");
    paused = false;

    // If we are in single event mode and at the last frame, replay the current event
    if (
      (mode == MODE_SINGLE || mode == MODE_NONE)
      &&
      (curr_frame_id >= (int)event_data->frames.size())
    ) {
      Debug(1, "Was in single or no replay mode, and at last frame, so jumping to 1st frame");
      curr_frame_id = 1;
    } else {
      Debug(1, "mode is %s, current frame is %d, frame count is %d, last frame id is %d",
            StreamMode_Strings[(int) mode].c_str(),
            curr_frame_id,
            event_data->frame_count,
            event_data->last_frame_id);
    }

    replay_rate = ZM_RATE_BASE;
    break;
  }
  case CMD_VARPLAY : {
    Debug(1, "Got VARPLAY command");
    paused = false;
    replay_rate = ntohs(((unsigned char)msg->msg_data[2]<<8)|(unsigned char)msg->msg_data[1])-32768;
    if (replay_rate > 50 * ZM_RATE_BASE) {
      Warning("requested replay rate (%d) is too high. We only support up to 50x", replay_rate);
      replay_rate = 50 * ZM_RATE_BASE;
    } else if (replay_rate < -50*ZM_RATE_BASE) {
      Warning("requested replay rate (%d) is too low. We only support up to -50x", replay_rate);
      replay_rate = -50 * ZM_RATE_BASE;
    }
    break;
  }
  case CMD_STOP :
    Debug(1, "Got STOP command");
    paused = false;
    break;
  case CMD_FASTFWD : {
    Debug(1, "Got FAST FWD command");
    paused = false;
    // Set play rate
    switch (replay_rate) {
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
  }
  case CMD_SLOWFWD : {
    paused = true;
    replay_rate = ZM_RATE_BASE;
    step = 1;
    if (curr_frame_id < (int)event_data->frames.size())
      curr_frame_id += 1;
    Debug(1, "Got SLOWFWD command new frame id %d", curr_frame_id);
    break;
  }
  case CMD_SLOWREV : {
    paused = true;
    replay_rate = ZM_RATE_BASE;
    step = -1;
    if (curr_frame_id > 1) curr_frame_id -= 1;
    Debug(1, "Got SLOWREV command new frame id %d", curr_frame_id);
    break;
  }
  case CMD_FASTREV :
    Debug(1, "Got FAST REV command");
    paused = false;
    // Set play rate
    switch (replay_rate) {
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
    zoom += 10;
    send_frame = true;
    if (paused) {
      step = 1;
      send_twice = true;
    }
    break;
  case CMD_ZOOMOUT :
    Debug(1, "Got ZOOM OUT command");
    zoom -= 10;
    if (zoom < 100) zoom = 100;
    send_frame = true;
    if (paused) {
      step = 1;
      send_twice = true;
    }
    break;
  case CMD_ZOOMSTOP :
    Debug(1, "Got ZOOM STOP command");
    zoom = 100;
    send_frame = true;
    if (paused) {
      step = 1;
      send_twice = true;
    }
    break;
  case CMD_PAN :
    x = ((unsigned char)msg->msg_data[1]<<8)|(unsigned char)msg->msg_data[2];
    y = ((unsigned char)msg->msg_data[3]<<8)|(unsigned char)msg->msg_data[4];
    Debug(1, "Got PAN command, to %d,%d", x, y);
    send_frame = true;
    if (paused) {
      step = 1;
      send_twice = true;
    }
    break;
  case CMD_SCALE :
    scale = ((unsigned char)msg->msg_data[1]<<8)|(unsigned char)msg->msg_data[2];
    Debug(1, "Got SCALE command, to %d", scale);
    send_frame = true;
    if (paused) {
      step = 1;
      send_twice = true;
    }
    break;
  case CMD_PREV :
    Debug(1, "Got PREV command");
    curr_frame_id = replay_rate >= 0 ? 0 : (int)event_data->frames.size()+1;
    paused = false;
    forceEventChange = true;
    break;
  case CMD_NEXT :
    Debug(1, "Got NEXT command");
    curr_frame_id = replay_rate >= 0 ? (int)event_data->frames.size()+1 : 0;
    paused = false;
    forceEventChange = true;
    break;
  case CMD_SEEK : {
    double int_part = ((unsigned char) msg->msg_data[1] << 24) | ((unsigned char) msg->msg_data[2] << 16)
                      | ((unsigned char) msg->msg_data[3] << 8) | (unsigned char) msg->msg_data[4];
    double dec_part = ((unsigned char) msg->msg_data[5] << 24) | ((unsigned char) msg->msg_data[6] << 16)
                      | ((unsigned char) msg->msg_data[7] << 8) | (unsigned char) msg->msg_data[8];

    FPSeconds offset = FPSeconds(int_part + dec_part / 1000000.0);
    if (offset < Seconds(0)) {
      Warning("Invalid offset, not seeking");
      break;
    } else if (offset > event_data->duration) {
      Warning("Invalid offset past end of event, seeking to end");
      offset = event_data->duration;
    }

    if (event_data->frames.empty()) {
      Debug(1, "No frames in event, can't seek");
      curr_frame_id = 1;
      curr_stream_time = event_data->start_time;
      break;
    }

    // Binary search: frames are sorted by offset (ascending).
    // Find the last frame whose offset <= the target offset.
    auto it = std::upper_bound(
      event_data->frames.begin(),
      event_data->frames.end(),
      offset,
      [](const FPSeconds &o, const FrameData &frame) {
        return o < frame.offset;
      });

    // upper_bound gives first frame with offset > target; step back one
    if (it != event_data->frames.begin())
      --it;

    curr_frame_id = static_cast<int>(std::distance(event_data->frames.begin(), it)) + 1;

    curr_stream_time = event_data->frames[curr_frame_id-1].timestamp;
    Debug(1, "Got SEEK command, to %f s (new current frame id: %d offset %f s)",
          FPSeconds(offset).count(),
          curr_frame_id,
          FPSeconds(event_data->frames[curr_frame_id - 1].offset).count());
    if (paused) {
      step = 1; // if we are paused, we won't send a frame except a keepalive.
      send_twice = true;
    }
    send_frame = true;
    break;
  }
  case CMD_QUERY :
    Debug(1, "Got QUERY command, sending STATUS");
    break;
  case CMD_QUIT :
    Info("User initiated exit - CMD_QUIT");
    zm_terminate = true;
    break;
  default :
    // Do nothing, for now
    break;
  }

  struct {
    uint64_t event_id;
    //Microseconds duration;
    double duration;
    //Microseconds progress;
    double progress;
    double fps;
    int rate;
    int zoom;
    int scale;
    bool paused;
  } status_data = {};

  {
    status_data.event_id = event_data->event_id;
    //status_data.duration = event_data->duration;
    status_data.duration = FPSeconds(event_data->duration).count();
    //status_data.progress = event_data->frames[curr_frame_id-1].offset;
    status_data.progress = (!event_data->frames.empty() && curr_frame_id >= 1 && curr_frame_id <= (int)event_data->frames.size())
      ? std::chrono::duration<double>(event_data->frames[curr_frame_id-1].offset).count()
      : 0.0;
    status_data.rate = replay_rate;
    status_data.zoom = zoom;
    status_data.scale = scale;
    status_data.paused = paused;

    FPSeconds elapsed = now - last_fps_update;
    if (elapsed.count() > 0) {
      actual_fps = (actual_fps + (frame_count - last_frame_count) / elapsed.count())/2;
      Debug(1, "actual_fps %f = old + frame_count %d - last %d / elapsed %.2f from %.2f - %.2f scale %d", actual_fps, frame_count, last_frame_count,
          elapsed.count(), FPSeconds(now.time_since_epoch()).count(), FPSeconds(last_fps_update.time_since_epoch()).count(), scale);
      last_frame_count = frame_count;
      last_fps_update = now;
    }

    status_data.fps = actual_fps;

    Debug(2, "Event:%" PRIu64 ", Duration %f, Paused:%d, progress:%f Rate:%d, Zoom:%d Scale:%d",
          status_data.event_id,
          FPSeconds(status_data.duration).count(),
          status_data.paused,
          FPSeconds(status_data.progress).count(),
          status_data.rate,
          status_data.zoom,
          status_data.scale
         );
    double fps = 1.0;
    if ((event_data->frame_count and event_data->duration != Seconds(0))) {
      fps = static_cast<double>(event_data->frame_count) / FPSeconds(event_data->duration).count();
    }
    updateFrameRate(fps);
  } // end scope for lock

  DataMsg status_msg;
  status_msg.msg_type = MSG_DATA_EVENT;
  memcpy(&status_msg.msg_data, &status_data, sizeof(status_data));
  if (sendto(sd, &status_msg, sizeof(status_msg), MSG_DONTWAIT, (sockaddr *)&rem_addr, sizeof(rem_addr)) < 0) {
    //if ( errno != EAGAIN )
    {
      Error("Can't sendto on sd %d: %s", sd, strerror(errno));
      //exit(-1);
    }
  }
}  // void EventStream::processCommand(const CmdMsg *msg)

bool EventStream::checkEventLoaded() {
  std::string sql;

  if (curr_frame_id <= 0) {
    sql = stringtf(
            "SELECT `Id` FROM `Events` WHERE `MonitorId` = %d AND `Id` < %" PRIu64 " ORDER BY `Id` DESC LIMIT 1",
            event_data->monitor_id, event_data->event_id);
  } else if (curr_frame_id > (int)event_data->frames.size()) {
    if (event_data->end_time.time_since_epoch() == Seconds(0)) {
      // We are viewing an in-process event, so just reload it.
      loadEventData(event_data->event_id);
      int num_frames = (int)event_data->frames.size();
      if (num_frames == 0 || curr_frame_id > num_frames)
        curr_frame_id = num_frames > 0 ? num_frames : 1;
      return false;
    }
    sql = stringtf(
            "SELECT `Id` FROM `Events` WHERE `MonitorId` = %d AND `Id` > %" PRIu64 " ORDER BY `Id` ASC LIMIT 1",
            event_data->monitor_id, event_data->event_id);
  } else {
    // No event change required
    Debug(4, "No event change required, as curr frame %d <=> event frames %zu",
          curr_frame_id, event_data->frames.size());
    return false;
  }

  // Event change required.
  if (forceEventChange || ((mode != MODE_SINGLE) && (mode != MODE_NONE))) {
    Debug(1, "Checking for next event %s", sql.c_str());

    MYSQL_RES *result = zmDbFetch(sql);
    if (!result) exit(-1);

    if (mysql_num_rows(result) != 1) {
      Debug(1, "No rows returned for %s", sql.c_str());
    }
    MYSQL_ROW dbrow = mysql_fetch_row(result);

    if (mysql_errno(&dbconn)) {
      Error("Can't fetch row: %s", mysql_error(&dbconn));
      exit(mysql_errno(&dbconn));
    }

    if (dbrow) {
      uint64_t event_id = atoll(dbrow[0]);
      Debug(1, "Loading new event %" PRIu64, event_id);

      loadEventData(event_id);

      int num_frames = (int)event_data->frames.size();
      curr_frame_id = replay_rate < 0 ? (num_frames > 0 ? num_frames : 1) : 1;
      Debug(2, "New frame id = %d", curr_frame_id);
      start = std::chrono::steady_clock::now();
      mysql_free_result(result);
      return true;
    } else {
      Debug(2, "No next event loaded using %s. Pausing", sql.c_str());
      int num_frames = (int)event_data->frames.size();
      curr_frame_id = curr_frame_id <= 0 ? 1 : (num_frames > 0 ? num_frames : 1);
      paused = true;
      sendTextFrame("No more event data found");
    }  // end if found a new event or not
    mysql_free_result(result);
    forceEventChange = false;
  } else {
    Debug(2, "Pausing because mode is %s", StreamMode_Strings[mode].c_str());
    int num_frames = (int)event_data->frames.size();
    curr_frame_id = curr_frame_id <= 0 ? 1 : (num_frames > 0 ? num_frames : 1);
    paused = true;
    // Reset last_frame_sent so the next loop iteration sends a keepalive
    // immediately, rather than leaving a 5-second gap (MAX_STREAM_DELAY)
    // during which the HTTP connection can time out and break the pipe.
    last_frame_sent = {};
  }
  return false;
}  // void EventStream::checkEventLoaded()

Image * EventStream::getImage( ) {
  std::string path = stringtf(staticConfig.capture_file_format.c_str(), event_data->path.c_str(), curr_frame_id);
  Debug(2, "EventStream::getImage path(%s) from %s frame(%d) ", path.c_str(), event_data->path.c_str(), curr_frame_id);
  Image *image = new Image(path.c_str());
  return image;
}

bool EventStream::sendFrame(Microseconds delta_us) {
  Debug(2, "Sending frame %d", curr_frame_id);

  if (event_data->frames.empty()) {
    Debug(1, "No frames in event, can't send frame");
    return true;  // Not fatal, just nothing to send yet
  }

  if (curr_frame_id < 1 || curr_frame_id > (int)event_data->frames.size()) {
    Debug(1, "curr_frame_id %d out of range [1..%zu], clamping",
          curr_frame_id, event_data->frames.size());
    curr_frame_id = std::clamp(curr_frame_id, 1, (int)event_data->frames.size());
  }

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
    if (stat(filepath.c_str(), &filestat) < 0) {
      Debug(1, "Capture file %s not found (bulk/interpolated frame %d), trying ffmpeg_input",
            filepath.c_str(), curr_frame_id);
      filepath = "";
      // Fall through — ffmpeg_input will be tried below if available
    }
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

    if (send_raw) {
      fprintf(stdout, "--" BOUNDARY "\r\n");
      if (!send_file(filepath)) {
        Error("Can't send %s: %s", filepath.c_str(), strerror(errno));
        return false;
      }
    } else {
      Image *image = nullptr;

      if (!filepath.empty()) {
        image = new Image(filepath.c_str());
      } else if (ffmpeg_input) {
        // Get the frame from the mp4 input
        const FrameData *frame_data = &event_data->frames[curr_frame_id-1];
        AVFrame *frame = ffmpeg_input->get_frame(
                           ffmpeg_input->get_video_stream_id(),
                           FPSeconds(frame_data->offset).count());
        if (frame) {
          image = new Image(frame, monitor->Width(), monitor->Height());
        } else {
          Error("Failed getting a frame.");
	  sendTextFrame("Failed getting frame");
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
        Debug(1, "Unable to get frame %d (no jpeg file and no ffmpeg_input)", curr_frame_id);
        sendTextFrame("No frame available");
        return true;
      }

      Image *send_image = prepareImage(image);
      reserveTempImgBuffer(send_image->Size());
      size_t img_buffer_size = 0;
      uint8_t *img_buffer = temp_img_buffer;

      if (type != STREAM_SINGLE)
       fprintf(stdout, "--" BOUNDARY "\r\n");
      switch ( type ) {
      case STREAM_SINGLE :
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
      int rc = send_buffer(img_buffer, img_buffer_size);
      delete image;
      image = nullptr;
      if (!rc) return false;
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

  SystemTimePoint::duration time_to_event = Seconds(0);

  std::thread command_processor;
  if (connkey) {
    command_processor = std::thread(&EventStream::checkCommandQueue, this);
  }

  // Has to go here, at the moment, for sendFrame(delta).
  Microseconds delta = Microseconds(0);

  while (!zm_terminate) {
    now = start = std::chrono::steady_clock::now();

    {
      std::scoped_lock lck{mutex};

      send_frame = false;
      TimePoint::duration time_since_last_send = now - last_frame_sent;

      if (!paused) {
        // Figure out if we should send this frame
        Debug(3, "not paused at curr_frame_id (%d-1) mod frame_mod(%d)", curr_frame_id, frame_mod);
        // If we are streaming and this frame is due to be sent
        // frame mod defaults to 1 and if we are going faster than max_fps will get multiplied by 2
        // so if it is 2, then we send every other frame, if is it 4 then every fourth frame, etc.

        //if ( (frame_mod == 1) || (((curr_frame_id-1)%frame_mod) == 0) ) {
        send_frame = true;
        //}
      } else if (step != 0) {
        Debug(2, "Paused with step %d", step);
        // We are paused and are just stepping forward or backward one frame
        step = 0;
        send_frame = true;
      } else if (!send_frame) {
        // We are paused, not stepping and doing nothing, meaning that comms didn't set send_frame to true
        if (time_since_last_send > MAX_STREAM_DELAY) {
          // Send keepalive
          Debug(2, "Sending keepalive frame");
          send_frame = true;
        } else {
          Debug(4, "Not Sending keepalive frame now %.2f - %.2f last = %.2f > Max %.2f",
              FPSeconds(now.time_since_epoch()).count(),
              FPSeconds(last_frame_sent.time_since_epoch()).count(),
              FPSeconds(time_since_last_send).count(),
              FPSeconds(MAX_STREAM_DELAY).count()
              );
        }
      }  // end if streaming stepping or doing nothing

      // time_to_event > 0 means that we are not in the event
      if (time_to_event > Seconds(0) and mode == MODE_ALL) {
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
        if (sleep_time == Seconds(0)) {
          sleep_time += STREAM_PAUSE_WAIT;
        }

        curr_stream_time += sleep_time;
        time_to_event -= sleep_time;
        Debug(2, "Sleeping (%" PRIi64 " ms) because we are not at the next event yet, adding %" PRIi64 " ms",
              static_cast<int64>(Milliseconds(STREAM_PAUSE_WAIT).count()),
              static_cast<int64>(Milliseconds(sleep_time).count()));
        std::this_thread::sleep_for(STREAM_PAUSE_WAIT);

        continue;
      }  // end if !in_event

      if (send_frame) {
        if (!sendFrame(delta)) {
          zm_terminate = true;
          break;
        }
        if (send_twice) {
          send_twice = false;
          if (!sendFrame(delta)) {
            zm_terminate = true;
            break;
          }
        }
        frame_count++;
      }

      if (!paused && !event_data->frames.empty()
          && curr_frame_id >= 1 && curr_frame_id <= (int)event_data->frames.size()) {
        // Get current frame data, curr_frame_id may have changed
        FrameData *last_frame_data = &event_data->frames[curr_frame_id-1];
        curr_stream_time = last_frame_data->timestamp;
        curr_frame_id += (replay_rate > 0 ? frame_mod : -1*frame_mod);

        // we incremented by replay_rate, so might have jumped past frames.size()
        int num_frames = (int)event_data->frames.size();
        if ((mode == MODE_SINGLE) && (
              (curr_frame_id < 1 ) || (curr_frame_id > num_frames)
            )
           ) {
          Debug(2, "Have mode==MODE_SINGLE and at end of event, looping back to start");
          curr_frame_id = 1;
        }

        if (curr_frame_id >= 1 && curr_frame_id <= num_frames) {
          const FrameData *next_frame_data = &event_data->frames[curr_frame_id-1];
          Debug(3, "Have Frame %d %d timestamp (%f s), offset (%f s) delta (%f s), in_db (%d)",
                curr_frame_id, next_frame_data->id,
                FPSeconds(next_frame_data->timestamp.time_since_epoch()).count(),
                FPSeconds(next_frame_data->offset).count(),
                FPSeconds(next_frame_data->delta).count(),
                next_frame_data->in_db);

          // frame_data->delta is the time since last frame as a float in seconds
          // but what if we are skipping frames? We need the distance from the last frame sent
          // Also, what about reverse? needs to be absolute value

          delta = abs(next_frame_data->offset - last_frame_data->offset);
          if (frame_mod) delta /= frame_mod;
          Debug(2, "New delta: %fs from last frame offset %fs - next_frame_offset %fs",
                FPSeconds(delta).count(),
                FPSeconds(last_frame_data->offset).count(),
                FPSeconds(next_frame_data->offset).count());
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
          TimePoint::duration elapsed = std::chrono::steady_clock::now() - start;
          delta -= std::chrono::duration_cast<Microseconds>(elapsed); // sending frames takes time, so remove it from the sleep time
          if (delta<Microseconds(0)) delta = Microseconds(0);

          Debug(2, "New delta: %fs from next frame offset %fs - last_frame_offset %fs - elapsed %fs",
                FPSeconds(delta).count(),
                FPSeconds(next_frame_data->offset).count(),
                FPSeconds(last_frame_data->offset).count(),
                FPSeconds(elapsed).count()
               );
        }  // end if not at end of event
      } else {
        // Paused
        delta = MAX_SLEEP;

        // We are paused, so might be stepping
        //if ( step != 0 )// Adding 0 is cheaper than an if 0
        // curr_frame_id starts at 1 though, so we might skip the first frame?
        curr_frame_id += step;
      }  // end if !paused
    }  // end scope for mutex lock
 
    if (type == STREAM_SINGLE) {
      Debug(1, "Single, exiting.");
      break;
    }

    if (type != STREAM_MPEG) {
      if (delta > Seconds(0)) {
        if (delta > MAX_SLEEP) {
          Debug(1, "Limiting sleep to %" PRIi64 " ms because calculated sleep is too long %" PRIi64,
                static_cast<int64>(std::chrono::duration_cast<Milliseconds>(MAX_SLEEP).count()),
                static_cast<int64>(std::chrono::duration_cast<Microseconds>(delta).count()));
          delta = MAX_SLEEP;
        }

        std::this_thread::sleep_for(delta);
      } // end if need to sleep
    }

    {
      std::scoped_lock lck{mutex};
      // Detects when we hit end of event and will load the next event or previous event
      if (checkEventLoaded()) {
        // Have change of event

        // This next bit is to determine if we are in the current event time wise
        // and whether to show an image saying how long until the next event.
        if (event_data->frames.empty()) {
          Debug(1, "New event has no frames yet, skipping time_to_event calculation");
        } else if (replay_rate > 0) {
          // This doesn't make sense unless we have hit the end of the event.
          time_to_event = event_data->frames[0].timestamp - curr_stream_time;
          Debug(1, "replay rate (%d) time_to_event (%f s) = frame timestamp (%f s) - curr_stream_time (%f s)",
                replay_rate,
                FPSeconds(time_to_event).count(),
                FPSeconds(event_data->frames[0].timestamp.time_since_epoch()).count(),
                FPSeconds(curr_stream_time.time_since_epoch()).count());

        } else if (replay_rate < 0) {
          time_to_event = curr_stream_time - event_data->frames[event_data->frames.size()-1].timestamp;
          Debug(1, "replay rate (%d), time_to_event(%f s) = curr_stream_time (%f s) - frame timestamp (%f s)",
                replay_rate,
                FPSeconds(time_to_event).count(),
                FPSeconds(curr_stream_time.time_since_epoch()).count(),
                FPSeconds(event_data->frames[event_data->frames.size() - 1].timestamp.time_since_epoch()).count());
        }  // end if forward or reverse
      }  // end if checkEventLoaded
    }  // end scope for lock
  }  // end while ! zm_terminate

  if (type == STREAM_MPEG) {
    delete vid_stream;
  }

  if (connkey) {
    if (command_processor.joinable()) {
      Debug(1, "command_processor is joinable");
      command_processor.join();
    } else {
      Debug(1, "command_processor is not joinable");
    }
  }
} // end void EventStream::runStream()

bool EventStream::send_file(const std::string &filepath) {
  FILE *fdj = fopen(filepath.c_str(), "rb");
  if (!fdj) {
    Error("Can't open %s: %s", filepath.c_str(), strerror(errno));
    std::string error_message = stringtf("Can't open %s: %s", filepath.c_str(), strerror(errno));
    return sendTextFrame(error_message.c_str());
  }
  static struct stat filestat;
  if (fstat(fileno(fdj), &filestat) < 0) {
    fclose(fdj); /* Close the file handle */
    Error("Failed getting information about file %s: %s", filepath.c_str(), strerror(errno));
    return false;
  }
  if (!filestat.st_size) {
    fclose(fdj); /* Close the file handle */
    Info("File size is zero. Unable to send raw frame %d: %s", curr_frame_id, strerror(errno));
    return false;
  }
  if (0 > fprintf(stdout, "Content-Length: %jd\r\n\r\n", static_cast<intmax_t>(filestat.st_size))) {
    fclose(fdj); /* Close the file handle */
    Info("Unable to send raw frame %d: %s", curr_frame_id, strerror(errno));
    return false;
  }
  ssize_t remaining = filestat.st_size;

  while (remaining > 0) {
    ssize_t rc = zm_sendfile(fileno(stdout), fileno(fdj), nullptr, remaining);
    if (rc < 0) break;
    if (rc > 0) {
      remaining -= rc;
    }
  }  // end while remaining

  if (!remaining) {
    // Success
    fclose(fdj); /* Close the file handle */
    return true;
  }
  Warning("Unable to send raw frame %d: %s %zu remaining",
          curr_frame_id, strerror(errno), remaining);
  return false;
}  // end bool EventStream::send_file(const std::string &filepath)

bool EventStream::send_buffer(uint8_t* buffer, int size) {
  if ( 0 > fprintf(stdout, "Content-Length: %d\r\n\r\n", size) ) {
    Debug(1, "Unable to send raw frame %d: %s", curr_frame_id, strerror(errno));
    return false;
  }
  int rc = fwrite(buffer, size, 1, stdout);

  if ( 1 != rc ) {
    Debug(1, "Unable to send raw frame %d: %s %d", curr_frame_id, strerror(errno), rc);
    return false;
  }
  return true;
}  // end bool EventStream::send_buffer(uint8_t* buffer, int size)

void EventStream::setStreamStart(
  uint64_t init_event_id,
  int init_frame_id=0) {
  loadInitialEventData(init_event_id, init_frame_id);
}  // end void EventStream::setStreamStart(init_event_id,init_frame_id=0)

void EventStream::setStreamStart(
  uint64_t init_event_id, SystemTimePoint event_time
  ) {
  // Load event data first, then seek to the specified time
  loadEventData(init_event_id);
  if (event_time.time_since_epoch() != Seconds(0)) {
    seek(event_time);
  } else {
    curr_stream_time = event_data->start_time;
    curr_frame_id = 1;
  }
}  // end void EventStream::setStreamStart(init_event_id, event_time)

void EventStream::setStreamStart(int monitor_id, SystemTimePoint event_time) {
  loadInitialEventData(monitor_id, event_time);
}
