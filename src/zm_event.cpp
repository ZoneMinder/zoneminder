//
// ZoneMinder Event Class Implementation
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

#include "zm_event.h"

#include "zm_camera.h"
#include "zm_db.h"
#include "zm_frame.h"
#include "zm_logger.h"
#include "zm_monitor.h"
#include "zm_signal.h"
#include "zm_videostore.h"

#include <cstring>
#include <list>
#include <sys/stat.h>
#include <unistd.h>

//#define USE_PREPARED_SQL 1

const char * Event::frame_type_names[3] = { "Normal", "Bulk", "Alarm" };
#define MAX_DB_FRAMES 100

int Event::pre_alarm_count = 0;

Event::PreAlarmData Event::pre_alarm_data[MAX_PRE_ALARM_FRAMES] = {};

Event::Event(
    Monitor *p_monitor,
    packetqueue_iterator *p_packetqueue_it,
    struct timeval p_start_time,
    const std::string &p_cause,
    const StringSetMap &p_noteSetMap
    ) :
  id(0),
  monitor(p_monitor),
  packetqueue_it(p_packetqueue_it),
  start_time(p_start_time),
  end_time(p_start_time),
  cause(p_cause),
  noteSetMap(p_noteSetMap),
  frames(0),
  alarm_frames(0),
  alarm_frame_written(false),
  tot_score(0),
  max_score(0),
  //path(""),
  //snapshit_file(),
  snapshot_file_written(false),
  //alarm_file(""),
  videoStore(nullptr),
  //video_name(""),
  //video_file(""),
  last_db_frame(0),
  have_video_keyframe(false),
  //scheme
  save_jpegs(0),
  terminate_(false)
{
  std::string notes;
  createNotes(notes);

  timeval now = {};
  gettimeofday(&now, nullptr);

  packetqueue = monitor->GetPacketQueue();
  if ( !start_time.tv_sec ) {
    Warning("Event has zero time, setting to now");
    start_time = now;
  } else if ( start_time.tv_sec > now.tv_sec ) {
    char buffer[26];
    char buffer_now[26];
    tm tm_info = {};

    localtime_r(&start_time.tv_sec, &tm_info);
    strftime(buffer, 26, "%Y:%m:%d %H:%M:%S", &tm_info);
    localtime_r(&now.tv_sec, &tm_info);
    strftime(buffer_now, 26, "%Y:%m:%d %H:%M:%S", &tm_info);

    Error("StartDateTime in the future starttime %ld.%06ld >? now %ld.%06ld difference %" PRIi64 "\nstarttime: %s\nnow: %s",
          start_time.tv_sec, start_time.tv_usec, now.tv_sec, now.tv_usec,
          static_cast<int64>(now.tv_sec - start_time.tv_sec),
          buffer, buffer_now);
    start_time = now;
  }

  unsigned int state_id = 0;
  {
    zmDbRow dbrow;
    if (dbrow.fetch("SELECT Id FROM States WHERE IsActive=1")) {
      state_id = atoi(dbrow[0]);
    }
  }

  // Copy it in case opening the mp4 doesn't work we can set it to another value
  save_jpegs = monitor->GetOptSaveJPEGs();
  Storage *storage = monitor->getStorage();

  std::string sql = stringtf(
      "INSERT INTO `Events` "
      "( `MonitorId`, `StorageId`, `Name`, `StartDateTime`, `Width`, `Height`, `Cause`, `Notes`, `StateId`, `Orientation`, `Videoed`, `DefaultVideo`, `SaveJPEGs`, `Scheme` )"
      " VALUES "
      "( %d, %d, 'New Event', from_unixtime( %ld ), %d, %d, '%s', '%s', %d, %d, %d, '%s', %d, '%s' )",
      monitor->Id(), 
      storage->Id(),
      start_time.tv_sec,
      monitor->Width(),
      monitor->Height(),
      cause.c_str(),
      notes.c_str(), 
      state_id,
      monitor->getOrientation(),
      0,
			"",
      save_jpegs,
      storage->SchemeString().c_str()
      );

  id = zmDbDoInsert(sql.c_str());

  thread_ = std::thread(&Event::Run, this);
} // Event::Event( Monitor *p_monitor, struct timeval p_start_time, const std::string &p_cause, const StringSetMap &p_noteSetMap, bool p_videoEvent )

Event::~Event() {
  Stop();

  if (thread_.joinable()) {
    Debug(1, "Joining event thread");
    // Should be.  Issuing the stop and then getting the lock
    thread_.join();
  }
  packetqueue->free_it(packetqueue_it);
  delete packetqueue_it;

  /* Close the video file */
  // We close the videowriter first, because if we finish the event, we might try to view the file, but we aren't done writing it yet.
  if (videoStore != nullptr) {
    Debug(4, "Deleting video store");
    delete videoStore;
    videoStore = nullptr;
  }

  // endtime is set in AddFrame, so SHOULD be set to the value of the last frame timestamp.
  if ( !end_time.tv_sec ) {
    Warning("Empty endtime for event.  Should not happen.  Setting to now.");
    gettimeofday(&end_time, nullptr);
  }
  struct DeltaTimeval delta_time;
  DELTA_TIMEVAL(delta_time, end_time, start_time, DT_PREC_2);
  Debug(2, "start_time: %" PRIi64 ".% " PRIi64 " end_time: %" PRIi64 ".%" PRIi64,
        static_cast<int64>(start_time.tv_sec),
        static_cast<int64>(start_time.tv_usec),
        static_cast<int64>(end_time.tv_sec),
        static_cast<int64>(end_time.tv_usec));

  if (frame_data.size()) WriteDbFrames();

  // Should not be static because we might be multi-threaded
  char sql[ZM_SQL_LGE_BUFSIZ];
  snprintf(sql, sizeof(sql),
      "UPDATE Events SET Name='%s%" PRIu64 "', EndDateTime = from_unixtime(%ld), Length = %s%ld.%02ld, Frames = %d, AlarmFrames = %d, TotScore = %d, AvgScore = %d, MaxScore = %d WHERE Id = %" PRIu64 " AND Name='New Event'",
      monitor->EventPrefix(), id, end_time.tv_sec,
      delta_time.positive?"":"-", delta_time.sec, delta_time.fsec,
      frames, alarm_frames,
      tot_score, (int)(alarm_frames?(tot_score/alarm_frames):0), max_score,
      id);
  if (!zmDbDoUpdate(sql)) {
    // Name might have been changed during recording, so just do the update without changing the name.
    snprintf(sql, sizeof(sql),
        "UPDATE Events SET EndDateTime = from_unixtime(%ld), Length = %s%ld.%02ld, Frames = %d, AlarmFrames = %d, TotScore = %d, AvgScore = %d, MaxScore = %d WHERE Id = %" PRIu64,
        end_time.tv_sec,
        delta_time.positive?"":"-", delta_time.sec, delta_time.fsec,
        frames, alarm_frames,
        tot_score, (int)(alarm_frames?(tot_score/alarm_frames):0), max_score,
        id);
    zmDbDoUpdate(sql);
  }  // end if no changed rows due to Name change during recording
}  // Event::~Event()

void Event::createNotes(std::string &notes) {
  notes.clear();
  for (StringSetMap::const_iterator mapIter = noteSetMap.begin(); mapIter != noteSetMap.end(); ++mapIter) {
    notes += mapIter->first;
    notes += ": ";
    const StringSet &stringSet = mapIter->second;
    for (StringSet::const_iterator setIter = stringSet.begin(); setIter != stringSet.end(); ++setIter) {
      if (setIter != stringSet.begin())
        notes += ", ";
      notes += *setIter;
    }
  }
}  // void Event::createNotes(std::string &notes)

bool Event::WriteFrameImage(
    Image *image,
    timeval timestamp,
    const char *event_file,
    bool alarm_frame) const {

  int thisquality = 
    (alarm_frame && (config.jpeg_alarm_file_quality > config.jpeg_file_quality)) ?
    config.jpeg_alarm_file_quality : 0;   // quality to use, zero is default

  bool rc;

  if ( !config.timestamp_on_capture ) {
    // stash the image we plan to use in another pointer regardless if timestamped.
    // exif is only timestamp at present this switches on or off for write
    Image *ts_image = new Image(*image);
    monitor->TimestampImage(ts_image, timestamp);
    rc = ts_image->WriteJpeg(event_file, thisquality,
        (monitor->Exif() ? timestamp : (timeval){0,0}));
    delete(ts_image);
  } else {
    rc = image->WriteJpeg(event_file, thisquality,
        (monitor->Exif() ? timestamp : (timeval){0,0}));
  }

  return rc;
}  // end Event::WriteFrameImage( Image *image, struct timeval timestamp, const char *event_file, bool alarm_frame )

bool Event::WritePacket(const std::shared_ptr<ZMPacket>packet) {
  if (videoStore->writePacket(packet) < 0)
    return false;
  return true;
}  // bool Event::WriteFrameVideo

void Event::updateNotes(const StringSetMap &newNoteSetMap) {
  bool update = false;

  //Info( "Checking notes, %d <> %d", noteSetMap.size(), newNoteSetMap.size() );
  if ( newNoteSetMap.size() > 0 ) {
    if ( noteSetMap.size() == 0 ) {
      noteSetMap = newNoteSetMap;
      update = true;
    } else {
      for ( StringSetMap::const_iterator newNoteSetMapIter = newNoteSetMap.begin();
          newNoteSetMapIter != newNoteSetMap.end();
          ++newNoteSetMapIter ) {
        const std::string &newNoteGroup = newNoteSetMapIter->first;
        const StringSet &newNoteSet = newNoteSetMapIter->second;
        //Info( "Got %d new strings", newNoteSet.size() );
        if ( newNoteSet.size() > 0 ) {
          StringSetMap::iterator noteSetMapIter = noteSetMap.find(newNoteGroup);
          if ( noteSetMapIter == noteSetMap.end() ) {
            //Info( "Can't find note group %s, copying %d strings", newNoteGroup.c_str(), newNoteSet.size() );
            noteSetMap.insert(StringSetMap::value_type(newNoteGroup, newNoteSet));
            update = true;
          } else {
            StringSet &noteSet = noteSetMapIter->second;
            //Info( "Found note group %s, got %d strings", newNoteGroup.c_str(), newNoteSet.size() );
            for ( StringSet::const_iterator newNoteSetIter = newNoteSet.begin();
                newNoteSetIter != newNoteSet.end();
                ++newNoteSetIter ) {
              const std::string &newNote = *newNoteSetIter;
              StringSet::iterator noteSetIter = noteSet.find(newNote);
              if ( noteSetIter == noteSet.end() ) {
                noteSet.insert(newNote);
                update = true;
              }
            } // end for
          } // end if ( noteSetMap.size() == 0
        } // end if newNoteSetupMap.size() > 0
      } // end foreach newNoteSetMap
    } // end if have old notes
  } // end if have new notes

  if ( update ) {
    std::string notes;
    createNotes(notes);

    Debug(2, "Updating notes for event %" PRIu64 ", '%s'", id, notes.c_str());
#if USE_PREPARED_SQL
    static MYSQL_STMT *stmt = 0;

    char notesStr[ZM_SQL_MED_BUFSIZ] = "";
    unsigned long notesLen = 0;

    if ( !stmt ) {
      const char *sql = "UPDATE `Events` SET `Notes` = ? WHERE `Id` = ?";

      stmt = mysql_stmt_init(&dbconn);
      if ( mysql_stmt_prepare(stmt, sql, strlen(sql)) ) {
        Fatal("Unable to prepare sql '%s': %s", sql, mysql_stmt_error(stmt));
      }

      /* Get the parameter count from the statement */
      if ( mysql_stmt_param_count(stmt) != 2 ) {
        Error("Unexpected parameter count %ld in sql '%s'", mysql_stmt_param_count(stmt), sql);
      }

      MYSQL_BIND  bind[2];
      memset(bind, 0, sizeof(bind));

      /* STRING PARAM */
      bind[0].buffer_type = MYSQL_TYPE_STRING;
      bind[0].buffer = (char *)notesStr;
      bind[0].buffer_length = sizeof(notesStr);
      bind[0].is_null = 0;
      bind[0].length = &notesLen;

      bind[1].buffer_type= MYSQL_TYPE_LONG;
      bind[1].buffer= (char *)&id;
      bind[1].is_null= 0;
      bind[1].length= 0;

      /* Bind the buffers */
      if ( mysql_stmt_bind_param(stmt, bind) ) {
        Error("Unable to bind sql '%s': %s", sql, mysql_stmt_error(stmt));
      }
    } // end if ! stmt

    strncpy(notesStr, notes.c_str(), sizeof(notesStr));

    if ( mysql_stmt_execute(stmt) ) {
      Error("Unable to execute sql '%s': %s", sql, mysql_stmt_error(stmt));
    }
#else
    std::string escaped_notes = zmDbEscapeString(notes);

    std::string sql = stringtf("UPDATE `Events` SET `Notes` = '%s' WHERE `Id` = %" PRIu64, escaped_notes.c_str(), id);
    dbQueue.push(std::move(sql));
#endif
  }  // end if update
}  // void Event::updateNotes(const StringSetMap &newNoteSetMap)

void Event::AddPacket_(const std::shared_ptr<ZMPacket>packet) {
  have_video_keyframe = have_video_keyframe || 
    ( ( packet->codec_type == AVMEDIA_TYPE_VIDEO ) && 
      ( packet->keyframe || monitor->GetOptVideoWriter() == Monitor::ENCODE) );
  Debug(2, "have_video_keyframe %d codec_type %d == video? %d packet keyframe %d",
      have_video_keyframe, packet->codec_type, (packet->codec_type == AVMEDIA_TYPE_VIDEO), packet->keyframe);
  ZM_DUMP_PACKET(packet->packet, "Adding to event");
  if (videoStore) {
    if (have_video_keyframe) {
      videoStore->writePacket(packet);
    } else {
      Debug(2, "No video keyframe yet, not writing");
    }
    //FIXME if it fails, we should write a jpeg
  }

  if ((packet->codec_type == AVMEDIA_TYPE_VIDEO) or packet->image) {
    AddFrame(packet);
  }
  end_time = packet->timestamp;
  return;
}

void Event::WriteDbFrames() {
  std::string frame_insert_sql = "INSERT INTO `Frames` (`EventId`, `FrameId`, `Type`, `TimeStamp`, `Delta`, `Score`) VALUES ";
  std::string stats_insert_sql = "INSERT INTO `Stats` (`EventId`, `FrameId`, `MonitorId`, `ZoneId`, "
                                              "`PixelDiff`, `AlarmPixels`, `FilterPixels`, `BlobPixels`,"
                                              "`Blobs`,`MinBlobSize`, `MaxBlobSize`, "
                                              "`MinX`, `MinY`, `MaxX`, `MaxY`,`Score`) VALUES ";

  Debug(1, "Inserting %zu frames", frame_data.size());
  while (frame_data.size()) {
    Frame *frame = frame_data.front();
    frame_data.pop();
    frame_insert_sql += stringtf("\n( %" PRIu64 ", %d, '%s', from_unixtime( %ld ), %s%ld.%02ld, %d ),",
        id, frame->frame_id,
        frame_type_names[frame->type],
        frame->timestamp.tv_sec,
        frame->delta.positive ? "" : "-",
        frame->delta.sec,
        frame->delta.fsec,
        frame->score);
    if (config.record_event_stats and frame->zone_stats.size()) {
      for (ZoneStats &stats : frame->zone_stats) {
        stats_insert_sql += stringtf("\n(%" PRIu64 ",%d,%u,%d,%d,%d,%d,%d,%d,%d,%d,%d,%d,%d,%d,%u),",
            id, frame->frame_id,
            monitor->Id(),
            stats.zone_id_,
            stats.pixel_diff_,
            stats.alarm_pixels_,
            stats.alarm_filter_pixels_,
            stats.alarm_blob_pixels_,
            stats.alarm_blobs_,
            stats.min_blob_size_,
            stats.max_blob_size_,
            stats.alarm_box_.Lo().x_,
            stats.alarm_box_.Lo().y_,
            stats.alarm_box_.Hi().x_,
            stats.alarm_box_.Hi().y_,
            stats.score_);
      }  // end foreach zone stats
    }  // end if recording stats
    delete frame;
  }  // end while frames
  // The -1 is for the extra , added for values above
  frame_insert_sql.erase(frame_insert_sql.size()-1);
  dbQueue.push(std::move(frame_insert_sql));
  if (stats_insert_sql.size() > 208) {
    // The -1 is for the extra , added for values above
    stats_insert_sql.erase(stats_insert_sql.size()-1);
    dbQueue.push(std::move(stats_insert_sql));
  }
}  // end void Event::WriteDbFrames()

void Event::AddFrame(const std::shared_ptr<ZMPacket>&packet) {
  if (!packet->timestamp.tv_sec) {
    Warning("Not adding new frame, zero timestamp");
    return;
  }

  frames++;
  Monitor::State monitor_state = monitor->GetState();
  int score = packet->score;

  bool write_to_db = false;
  FrameType frame_type = ( ( score > 0 ) ? ALARM : (
      (
       ( monitor_state == Monitor::TAPE )
       and
       ( config.bulk_frame_interval > 1 )
       and
       ( ! (frames % config.bulk_frame_interval) )
      ) ? BULK : NORMAL 
      ) );
  Debug(1, "Have frame type %s from score(%d) state %d frames %d bulk frame interval %d and mod%d", 
      frame_type_names[frame_type], score, monitor_state, frames, config.bulk_frame_interval, (frames % config.bulk_frame_interval));

  if (score < 0) score = 0;
  tot_score += score;

  if (packet->image) {
    if (save_jpegs & 1) {
      std::string event_file = stringtf(staticConfig.capture_file_format, path.c_str(), frames);
      Debug(1, "Writing capture frame %d to %s", frames, event_file.c_str());
      if (!WriteFrameImage(packet->image, packet->timestamp, event_file.c_str())) {
        Error("Failed to write frame image");
      }
    }  // end if save_jpegs

    // If this is the first frame, we should add a thumbnail to the event directory
    if ((frames == 1) || (score > max_score) || (!snapshot_file_written)) {
      write_to_db = true; // web ui might show this as thumbnail, so db needs to know about it.
      Debug(1, "Writing snapshot to %s", snapshot_file.c_str());
      WriteFrameImage(packet->image, packet->timestamp, snapshot_file.c_str());
      snapshot_file_written = true;
    } else {
      Debug(1, "Not Writing snapshot because frames %d score %d > max %d", frames, score, max_score);
    }

    // We are writing an Alarm frame
    if (frame_type == ALARM) {
      // The first frame with a score will be the frame that alarmed the event
      if (!alarm_frame_written) {
        write_to_db = true; // OD processing will need it, so the db needs to know about it
        alarm_frame_written = true;
        Debug(1, "Writing alarm image to %s", alarm_file.c_str());
        if (!WriteFrameImage(packet->image, packet->timestamp, alarm_file.c_str())) {
          Error("Failed to write alarm frame image to %s", alarm_file.c_str());
        }
      } else {
        Debug(3, "Not Writing alarm image because alarm frame already written");
      }

      if (packet->analysis_image and (save_jpegs & 2)) {
        std::string event_file = stringtf(staticConfig.analyse_file_format, path.c_str(), frames);
        Debug(1, "Writing analysis frame %d to %s", frames, event_file.c_str());
        if (!WriteFrameImage(packet->analysis_image, packet->timestamp, event_file.c_str(), true)) {
          Error("Failed to write analysis frame image to %s", event_file.c_str());
        }
      }  // end if has analysis images turned on
    }  // end if is an alarm frame
  } else {
    Debug(1, "No image");
  }  // end if has image

  if (frame_type == ALARM) alarm_frames++;

  bool db_frame = ( frame_type == BULK )
    or ( frame_type == ALARM )
    or ( frames == 1 )
    or ( score > max_score )
    or ( monitor_state == Monitor::ALERT )
    or ( monitor_state == Monitor::ALARM )
    or ( monitor_state == Monitor::PREALARM );

  if (score > max_score) {
    max_score = score;
  }

  if (db_frame) {

    struct DeltaTimeval delta_time;
    DELTA_TIMEVAL(delta_time, packet->timestamp, start_time, DT_PREC_2);
    Debug(1, "Frame delta is %" PRIi64 ".%" PRIi64 " - %" PRIi64 ".%" PRIi64 " = %lu.%lu, score %u zone_stats.size %zu",
          static_cast<int64>(start_time.tv_sec), static_cast<int64>(start_time.tv_usec),
          static_cast<int64>(packet->timestamp.tv_sec), static_cast<int64>(packet->timestamp.tv_usec),
          delta_time.sec, delta_time.fsec,
          score,
          packet->zone_stats.size());

    // The idea is to write out 1/sec
    frame_data.push(new Frame(id, frames, frame_type, packet->timestamp, delta_time, score, packet->zone_stats));
    double fps = monitor->get_capture_fps();
    if (write_to_db
        or
        (frame_data.size() >= MAX_DB_FRAMES)
        or
        (frame_type == BULK)
        or
        (fps and (frame_data.size() > 5*fps))) {
      Debug(1, "Adding %zu frames to DB because write_to_db:%d or frames > analysis fps %f or BULK(%d)",
            frame_data.size(), write_to_db, fps, (frame_type == BULK));
      WriteDbFrames();
      last_db_frame = frames;

      char sql[ZM_SQL_MED_BUFSIZ];
      snprintf(sql, sizeof(sql), 
          "UPDATE Events SET Length = %s%ld.%02ld, Frames = %d, AlarmFrames = %d, TotScore = %d, AvgScore = %d, MaxScore = %d WHERE Id = %" PRIu64, 
          ( delta_time.positive?"":"-" ),
          delta_time.sec, delta_time.fsec,
          frames, 
          alarm_frames,
          tot_score,
          (int)(alarm_frames?(tot_score/alarm_frames):0),
          max_score,
          id
          );
      dbQueue.push(std::move(sql));
		} else {
      Debug(1, "Not Adding %zu frames to DB because write_to_db:%d or frames > analysis fps %f or BULK",
          frame_data.size(), write_to_db, fps);
    }  // end if frame_type == BULK
  }  // end if db_frame

  end_time = packet->timestamp;
}  // void Event::AddFrame(const std::shared_ptr<ZMPacket>&packet)

bool Event::SetPath(Storage *storage) {
  scheme = storage->Scheme();

  path = stringtf("%s/%d", storage->Path(), monitor->Id());
  // Try to make the Monitor Dir.  Normally this would exist, but in odd cases might not.
  if (mkdir(path.c_str(), 0755) and (errno != EEXIST)) {
    Error("Can't mkdir %s: %s", path.c_str(), strerror(errno));
    return false;
  }

  tm stime = {};
  localtime_r(&start_time.tv_sec, &stime);
  if (scheme == Storage::DEEP) {
    int dt_parts[6];
    dt_parts[0] = stime.tm_year-100;
    dt_parts[1] = stime.tm_mon+1;
    dt_parts[2] = stime.tm_mday;
    dt_parts[3] = stime.tm_hour;
    dt_parts[4] = stime.tm_min;
    dt_parts[5] = stime.tm_sec;

    std::string date_path;
    std::string time_path;

    for (unsigned int i = 0; i < sizeof(dt_parts)/sizeof(*dt_parts); i++) {
      path += stringtf("/%02d", dt_parts[i]);

      if (mkdir(path.c_str(), 0755) and (errno != EEXIST)) {
        Error("Can't mkdir %s: %s", path.c_str(), strerror(errno));
        return false;
      }
      if (i == 2)
				date_path = path;
    }
		time_path = stringtf("%02d/%02d/%02d", stime.tm_hour, stime.tm_min, stime.tm_sec);

    // Create event id symlink
    std::string id_file = stringtf("%s/.%" PRIu64, date_path.c_str(), id);
    if (symlink(time_path.c_str(), id_file.c_str()) < 0) {
      Error("Can't symlink %s -> %s: %s", id_file.c_str(), time_path.c_str(), strerror(errno));
      return false;
    }
  } else if (scheme == Storage::MEDIUM) {
    path += stringtf("/%04d-%02d-%02d",
        stime.tm_year+1900, stime.tm_mon+1, stime.tm_mday
        );
    if (mkdir(path.c_str(), 0755) and (errno != EEXIST)) {
      Error("Can't mkdir %s: %s", path.c_str(), strerror(errno));
      return false;
    }
    path += stringtf("/%" PRIu64, id);
    if (mkdir(path.c_str(), 0755) and (errno != EEXIST)) {
      Error("Can't mkdir %s: %s", path.c_str(), strerror(errno));
      return false;
    }
  } else {
    path += stringtf("/%" PRIu64, id);
    if (mkdir(path.c_str(), 0755) and (errno != EEXIST)) {
      Error("Can't mkdir %s: %s", path.c_str(), strerror(errno));
      return false;
    }

    // Create empty id tag file
    std::string id_file = stringtf("%s/.%" PRIu64, path.c_str(), id);
    if ( FILE *id_fp = fopen(id_file.c_str(), "w") ) {
      fclose(id_fp);
    } else {
      Error("Can't fopen %s: %s", id_file.c_str(), strerror(errno));
      return false;
		}
  }  // deep storage or not
  return true;
}  // end bool Event::SetPath

void Event::Run() {
  Debug(1, "Starting event thread");
  Storage *storage = monitor->getStorage();
  if (!SetPath(storage)) {
    // Try another
    Warning("Failed creating event dir at %s", storage->Path());

    std::string sql = stringtf("SELECT `Id` FROM `Storage` WHERE `Id` != %u", storage->Id());
    if (monitor->ServerId())
      sql += stringtf(" AND ServerId=%u", monitor->ServerId());

    storage = nullptr;

    MYSQL_RES *result = zmDbFetch(sql.c_str());
    if (result) {
      for (int i = 0; MYSQL_ROW dbrow = mysql_fetch_row(result); i++) {
        storage = new Storage(atoi(dbrow[0]));
        if (SetPath(storage))
          break;
        delete storage;
        storage = nullptr;
      }  // end foreach row of Storage
      mysql_free_result(result);
      result = nullptr;
    }
    if (!storage) {
      Info("No valid local storage area found.  Trying all other areas.");
      // Try remote
      sql = "SELECT `Id` FROM `Storage` WHERE ServerId IS NULL";
      if (monitor->ServerId())
        sql += stringtf(" OR ServerId != %u", monitor->ServerId());

      result = zmDbFetch(sql.c_str());
      if (result) {
        for (int i = 0; MYSQL_ROW dbrow = mysql_fetch_row(result); i++) {
          storage = new Storage(atoi(dbrow[0]));
          if (SetPath(storage))
            break;
          delete storage;
          storage = nullptr;
        }  // end foreach row of Storage
        mysql_free_result(result);
        result = nullptr;
      }
    }
    if (!storage) {
      storage = new Storage();
      Warning("Failed to find a storage area to save events.");
    }
    sql = stringtf("UPDATE Events SET StorageId = '%d' WHERE Id=%" PRIu64, storage->Id(), id);
    zmDbDo(sql.c_str());
  }  // end if ! setPath(Storage)
  Debug(1, "Using storage area at %s", path.c_str());

  snapshot_file = path + "/snapshot.jpg";
  alarm_file = path + "/alarm.jpg";

  std::string container = monitor->OutputContainer();
  if ( container == "auto" || container == "" ) {
    container = "mp4";
  }

  video_name = stringtf("%" PRIu64 "-%s.%s", id, "video", container.c_str());
  video_file = path + "/" + video_name;
  Debug(1, "Writing video file to %s", video_file.c_str());

  if (monitor->GetOptVideoWriter() != 0) {
    /* Save as video */
    videoStore = new VideoStore(
        video_file.c_str(),
        container.c_str(),
        monitor->GetVideoStream(),
        monitor->GetVideoCodecContext(),
        ( monitor->RecordAudio() ? monitor->GetAudioStream() : nullptr ),
        ( monitor->RecordAudio() ? monitor->GetAudioCodecContext() : nullptr ),
        monitor );

    if (!videoStore->open()) {
      Warning("Failed to open videostore, turning on jpegs");
      delete videoStore;
      videoStore = nullptr;
      if (!(save_jpegs & 1)) {
        save_jpegs |= 1; // Turn on jpeg storage
        zmDbDo(stringtf("UPDATE Events SET SaveJpegs=%d WHERE Id=%" PRIu64, save_jpegs, id).c_str());
      }
    } else {
      std::string sql = stringtf("UPDATE Events SET Videoed=1, DefaultVideo = '%s' WHERE Id=%" PRIu64, video_name.c_str(), id);
      zmDbDo(sql.c_str());
    }
  }  // end if GetOptVideoWriter
  if (storage != monitor->getStorage())
    delete storage;

  // The idea is to process the queue no matter what so that all packets get processed.
  // We only break if the queue is empty
  while (!terminate_ and !zm_terminate) {
    ZMLockedPacket *packet_lock = packetqueue->get_packet_no_wait(packetqueue_it);
    if (packet_lock) {
      std::shared_ptr<ZMPacket> packet = packet_lock->packet_;
      if (!packet->decoded) {
        delete packet_lock;
        // Stay behind decoder
        Microseconds sleep_for = Microseconds(ZM_SAMPLE_RATE);
        Debug(4, "Sleeping for %" PRId64 "us", int64(sleep_for.count()));
        std::this_thread::sleep_for(sleep_for);
        continue;
      }
      packetqueue->increment_it(packetqueue_it);

      Debug(1, "Adding packet %d", packet->image_index);
      this->AddPacket_(packet);

      if (packet->image) {
        if (monitor->GetOptVideoWriter() == Monitor::PASSTHROUGH) {
          if (!save_jpegs) {
            Debug(1, "Deleting image data for %d", packet->image_index);
            // Don't need raw images anymore
            delete packet->image;
            packet->image = nullptr;
          }
        }
        if (packet->analysis_image and !(save_jpegs & 2)) {
          Debug(1, "Deleting analysis image data for %d", packet->image_index);
          delete packet->analysis_image;
          packet->analysis_image = nullptr;
        }
      } // end if packet->image
      Debug(1, "Deleting packet lock");
      delete packet_lock;
    } else {
      if (terminate_ or zm_terminate) return;
      usleep(10000);
    } // end if packet_lock
  }  // end while
}  // end Run()
