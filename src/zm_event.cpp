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
#include <functional>

const char * Event::frame_type_names[3] = { "Normal", "Bulk", "Alarm" };
#define MAX_DB_FRAMES 100

int Event::pre_alarm_count = 0;

Event::PreAlarmData Event::pre_alarm_data[MAX_PRE_ALARM_FRAMES] = {};

Event::Event(
    Monitor *p_monitor,
    SystemTimePoint p_start_time,
    const std::string &p_cause,
    const StringSetMap &p_noteSetMap
    ) :
  id(0),
  monitor(p_monitor),
  start_time(p_start_time),
  end_time(p_start_time),
  cause(p_cause),
  noteSetMap(p_noteSetMap),
  frames(0),
  alarm_frames(0),
  alarm_frame_written(false),
  tot_score(0),
  max_score(-1),
  //path(""),
  //snapshit_file(),
  snapshot_file_written(false),
  //alarm_file(""),
  videoStore(nullptr),
  //video_file(""),
  //video_path(""),
  last_db_frame(0),
  have_video_keyframe(false),
  //scheme
  save_jpegs(0),
  terminate_(false)
{
  std::string notes;
  createNotes(notes);

  SystemTimePoint now = std::chrono::system_clock::now();

  if (start_time.time_since_epoch() == Seconds(0)) {
    Warning("Event has zero time, setting to now");
    end_time = start_time = now;
  } else if (start_time > now) {
    char buffer[26];
    char buffer_now[26];
    tm tm_info = {};
    time_t start_time_t = std::chrono::system_clock::to_time_t(start_time);
    time_t now_t = std::chrono::system_clock::to_time_t(now);

    localtime_r(&start_time_t, &tm_info);
    strftime(buffer, 26, "%Y:%m:%d %H:%M:%S", &tm_info);
    localtime_r(&now_t, &tm_info);
    strftime(buffer_now, 26, "%Y:%m:%d %H:%M:%S", &tm_info);

    Error("StartDateTime in the future. Difference: %" PRIi64 " s\nstarttime: %s\nnow: %s",
          static_cast<int64>(std::chrono::duration_cast<Seconds>(now - start_time).count()),
          buffer, buffer_now);
    end_time = start_time = now;
  }

  unsigned int state_id = 0;
  {
    zmDbQuery q = zmDbQuery( SELECT_ALL_ACTIVE_STATES_ID );
    q.fetchOne();
    state_id = q.get<long long>( "Id" );
  }

  // Copy it in case opening the mp4 doesn't work we can set it to another value
  save_jpegs = monitor->GetOptSaveJPEGs();
  Storage *storage = monitor->getStorage();
  if (monitor->GetOptVideoWriter() != 0) {
    container = monitor->OutputContainer();
    if (container == "auto" || container == "") {
      container = "mp4";
    }
    video_incomplete_file = "incomplete."+container;
  }

    zmDbQuery q = zmDbQuery( INSERT_EVENTS );
    q.bind( "monitor_id", monitor->Id() );
    q.bind( "storage_id", storage->Id() );
    q.bind( "start_datetime", static_cast<int64>(std::chrono::system_clock::to_time_t(start_time)) );
    q.bind( "width", monitor->Width() );
    q.bind( "height", monitor->Height() );
    q.bind( "cause", cause );
    q.bind( "notes", notes );
    q.bind( "state_id", state_id );
    q.bind( "orientation", monitor->getOrientation() );
    q.bind( "videoed", 0 );
    q.bind( "default_video", video_incomplete_file );
    q.bind( "save_jpegs", save_jpegs );
    q.bind( "scheme", storage->SchemeString() );;

    id = q.insert();

  thread_ = std::thread(&Event::Run, this);
}

Event::~Event() {
  Stop();
  if (thread_.joinable()) {
    // Should be.  Issuing the stop and then getting the lock
    thread_.join();
  }

  /* Close the video file */
  // We close the videowriter first, because if we finish the event, we might try to view the file, but we aren't done writing it yet.
  if (videoStore != nullptr) {
    Debug(4, "Deleting video store");
    delete videoStore;
    videoStore = nullptr;
    int result = rename(video_incomplete_path.c_str(), video_path.c_str());
    if (result != 0) {
      Error("Failed renaming %s to %s", video_incomplete_path.c_str(), video_path.c_str());
      // So that we don't update the event record
      video_file = video_incomplete_file;
    }
  }

  // endtime is set in AddFrame, so SHOULD be set to the value of the last frame timestamp.
  if (end_time.time_since_epoch() == Seconds(0)) {
    Warning("Empty endtime for event. Should not happen. Setting to now.");
    end_time = std::chrono::system_clock::now();
  }

  FPSeconds delta_time = end_time - start_time;
  Debug(2, "start_time: %.2f end_time: %.2f",
        std::chrono::duration_cast<FPSeconds>(start_time.time_since_epoch()).count(),
        std::chrono::duration_cast<FPSeconds>(end_time.time_since_epoch()).count());

  if (frame_data.size()){
    WriteDbFrames();
  }

  std::string name = stringtf("%s%" PRIu64, monitor->EventPrefix(), id);

  zmDbQuery q = zmDbQuery( UPDATE_NEW_EVENT_WITH_ID );
  q.bind("name", name);
  q.bind("enddatetime", std::chrono::system_clock::to_time_t(end_time));
  q.bind("length", delta_time.count());
  q.bind("frames", frames);
  q.bind("alarm_frames", alarm_frames);
  q.bind("total_score", tot_score);
  q.bind("avg_score", static_cast<uint32>(alarm_frames ? (tot_score / alarm_frames) : 0));
  q.bind("max_score", max_score);
  q.bind("default_video", video_file);
  q.bind("id", id);
  uint64_t res = q.update();

  if (!res) {
    // Name might have been changed during recording, so just do the update without changing the name.
    zmDbQuery q = zmDbQuery( UPDATE_NEW_EVENT_WITH_ID_NO_NAME );
    q.bind("enddatetime", std::chrono::system_clock::to_time_t(end_time));
    q.bind("length", delta_time.count());
    q.bind("frames", frames);
    q.bind("alarm_frames", alarm_frames);
    q.bind("total_score", tot_score);
    q.bind("avg_score", static_cast<uint32>(alarm_frames ? (tot_score / alarm_frames) : 0));
    q.bind("max_score", max_score);
    q.bind("default_video", video_file);
    q.bind("id", id);
    q.update();

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

void Event::addNote(const char *cause, const std::string &note) {
  noteSetMap[cause].insert(note);
}

bool Event::WriteFrameImage(Image *image, SystemTimePoint timestamp, const char *event_file, bool alarm_frame) const {
  int thisquality = 
    (alarm_frame && (config.jpeg_alarm_file_quality > config.jpeg_file_quality)) ?
    config.jpeg_alarm_file_quality : 0;   // quality to use, zero is default

  bool rc;

  SystemTimePoint jpeg_timestamp = monitor->Exif() ? timestamp : SystemTimePoint();

  if (!config.timestamp_on_capture) {
    // stash the image we plan to use in another pointer regardless if timestamped.
    // exif is only timestamp at present this switches on or off for write
    Image *ts_image = new Image(*image);
    monitor->TimestampImage(ts_image, timestamp);
    rc = ts_image->WriteJpeg(event_file, thisquality, jpeg_timestamp);
    delete ts_image;
  } else {
    rc = image->WriteJpeg(event_file, thisquality, jpeg_timestamp);
  }

  return rc;
}

bool Event::WritePacket(const std::shared_ptr<ZMPacket>&packet) {
  if (videoStore->writePacket(packet) < 0)
    return false;
  return true;
}  // bool Event::WriteFrameVideo

void Event::updateNotes(const StringSetMap &newNoteSetMap) {
  bool update = false;

  //Info( "Checking notes, %d <> %d", noteSetMap.size(), newNoteSetMap.size() );
  if (newNoteSetMap.size() > 0) {
    if (noteSetMap.size() == 0) {
      noteSetMap = newNoteSetMap;
      update = true;
    } else {
      for (StringSetMap::const_iterator newNoteSetMapIter = newNoteSetMap.begin();
          newNoteSetMapIter != newNoteSetMap.end();
          ++newNoteSetMapIter) {
        const std::string &newNoteGroup = newNoteSetMapIter->first;
        const StringSet &newNoteSet = newNoteSetMapIter->second;
        //Info( "Got %d new strings", newNoteSet.size() );
        if (newNoteSet.size() > 0) {
          StringSetMap::iterator noteSetMapIter = noteSetMap.find(newNoteGroup);
          if (noteSetMapIter == noteSetMap.end()) {
            //Debug(3, "Can't find note group %s, copying %d strings", newNoteGroup.c_str(), newNoteSet.size());
            noteSetMap.insert(StringSetMap::value_type(newNoteGroup, newNoteSet));
            update = true;
          } else {
            StringSet &noteSet = noteSetMapIter->second;
            //Debug(3, "Found note group %s, got %d strings", newNoteGroup.c_str(), newNoteSet.size());
            for (StringSet::const_iterator newNoteSetIter = newNoteSet.begin();
                newNoteSetIter != newNoteSet.end();
                ++newNoteSetIter) {
              const std::string &newNote = *newNoteSetIter;
              StringSet::iterator noteSetIter = noteSet.find(newNote);
              if (noteSetIter == noteSet.end()) {
                noteSet.insert(newNote);
                update = true;
              }
            } // end for
          } // end if ( noteSetMap.size() == 0
        } // end if newNoteSetupMap.size() > 0
      } // end foreach newNoteSetMap
    } // end if have old notes
  } // end if have new notes

  if (update) {
    std::string notes;
    createNotes(notes);

    Debug(2, "Updating notes for event %" PRIu64 ", '%s'", id, notes.c_str());

    zmDbQuery q = zmDbQuery( UPDATE_EVENT_WITH_ID_SET_NOTES );
    q.bind( "notes", notes );
    q.bind( "id", id );
    q.update();
  }  // end if update
}  // void Event::updateNotes(const StringSetMap &newNoteSetMap)

void Event::AddPacket(ZMLockedPacket *packetlock) {
  {
    std::unique_lock<std::mutex> lck(packet_queue_mutex);
    packet_queue.push(packetlock);
  }
  packet_queue_condition.notify_one();
}

void Event::AddPacket_(const std::shared_ptr<ZMPacket>&packet) {
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
}

void Event::WriteDbFrames() {
  zmDbQuery framesQuery( INSERT_FRAMES );
  zmDbQuery zoneStatsQuery( INSERT_STATS_MULTIPLE );

  std::vector<Frame*> frames;

  Debug(1, "Inserting %zu frames", frame_data.size());
  while (frame_data.size()) {
    Frame *frame = frame_data.front();
    frame_data.pop();

    frames.push_back( frame );

    if (config.record_event_stats and frame->zone_stats.size()) {
      std::vector<uint64_t> event_ids;
      std::vector<int> frame_ids;
      std::vector<unsigned int> monitor_ids;
      std::vector<ZoneStats> zonestats;

      for (ZoneStats &stats : frame->zone_stats) {
        event_ids.push_back( id );
        frame_ids.push_back( frame->frame_id );
        monitor_ids.push_back(monitor->Id());
        zonestats.push_back( stats );
      }  // end foreach zone stats

      zoneStatsQuery.bind( "event_ids", event_ids );
      zoneStatsQuery.bind( "frame_ids", frame_ids );
      zoneStatsQuery.bind( "monitor_id", monitor_ids );
      zoneStatsQuery.bindVec( zonestats ); // see specialization of TypeConversion in zm_db_adapters.h

    }  // end if recording stats
  }  // end while frames

  // see specialization of TypeConversion in zm_db_adapters.h
  framesQuery.bindVec( frames );

  // will happen on the destructor of framesQuery object
  // after execution in the queue to prevent memory leak
  // of the frames
  framesQuery.deferOnClose([&](){
    for (Frame* frame : frames) {
      delete frame;
    }
  });

  framesQuery.insert();
  zoneStatsQuery.insert();

  //if (stats_insert_sql.size() > 208) {
    //zmDbQueue::pushToQueue(stats_insert_sql);
  //}
}  // end void Event::WriteDbFrames()

void Event::AddFrame(const std::shared_ptr<ZMPacket>&packet) {
  if (packet->timestamp.time_since_epoch() == Seconds(0)) {
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
      std::string event_file = stringtf(staticConfig.capture_file_format.c_str(), path.c_str(), frames);
      Debug(1, "Writing capture frame %d to %s", frames, event_file.c_str());
      if (!WriteFrameImage(packet->image, packet->timestamp, event_file.c_str())) {
        Error("Failed to write frame image");
      }
    }  // end if save_jpegs

    Debug(1, "frames %d, score %d max_score %d", frames, score, max_score);
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
        std::string event_file = stringtf(staticConfig.analyse_file_format.c_str(), path.c_str(), frames);
        Debug(1, "Writing analysis frame %d to %s", frames, event_file.c_str());
        if (!WriteFrameImage(packet->analysis_image, packet->timestamp, event_file.c_str(), true)) {
          Error("Failed to write analysis frame image to %s", event_file.c_str());
        }
        if (packet->in_frame &&
            (
             ((AVPixelFormat)packet->in_frame->format == AV_PIX_FMT_YUV420P)
             ||
             ((AVPixelFormat)packet->in_frame->format == AV_PIX_FMT_YUVJ420P)
            )
           ) {
          event_file = stringtf("%s/%d-y.jpg", path.c_str(), frames);
          Image y_image(
              packet->in_frame->width,
              packet->in_frame->height,
              1, ZM_SUBPIX_ORDER_NONE,
              packet->in_frame->data[0], 0);
          if (!WriteFrameImage(&y_image, packet->timestamp, event_file.c_str(), true)) {
            Error("Failed to write y frame image to %s", event_file.c_str());
          }
        }  // end if write y-channel image
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
    Microseconds delta_time = std::chrono::duration_cast<Microseconds>(packet->timestamp - start_time);
    Debug(1, "Frame delta is %.2f s - %.2f s = %.2f s, score %u zone_stats.size %zu",
          FPSeconds(packet->timestamp.time_since_epoch()).count(),
          FPSeconds(start_time.time_since_epoch()).count(),
          FPSeconds(delta_time).count(),
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

      zmDbQuery updateEventQuery( UPDATE_EVENT_WITH_ID_SET_SCORE );
      updateEventQuery.bind("length", FPSeconds(delta_time).count());
      updateEventQuery.bind("frames", frames);
      updateEventQuery.bind("alarm_frames", alarm_frames);
      updateEventQuery.bind("total_score", tot_score);
      updateEventQuery.bind("avg_score", static_cast<uint32>(alarm_frames ? (tot_score / alarm_frames) : 0));
      updateEventQuery.bind("max_score", max_score);
      updateEventQuery.bind("id", id);
      updateEventQuery.update();

    } else {
      Debug(1, "Not Adding %zu frames to DB because write_to_db:%d or frames > analysis fps %f or BULK",
            frame_data.size(), write_to_db, fps);
    }  // end if frame_type == BULK
  }  // end if db_frame
}  // void Event::AddFrame(const std::shared_ptr<ZMPacket>&packet)

bool Event::SetPath(Storage *storage) {
  scheme = storage->Scheme();

  path = stringtf("%s/%d", storage->Path(), monitor->Id());
  // Try to make the Monitor Dir.  Normally this would exist, but in odd cases might not.
  if (mkdir(path.c_str(), 0755) and (errno != EEXIST)) {
    Error("Can't mkdir %s: %s", path.c_str(), strerror(errno));
    return false;
  }

  time_t start_time_t = std::chrono::system_clock::to_time_t(start_time);

  tm stime = {};
  localtime_r(&start_time_t, &stime);
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
  Storage *storage = monitor->getStorage();
  if (!SetPath(storage)) {
    // Try another
    Warning("Failed creating event dir at %s", storage->Path());

    zmDbQuery storageQuery( SELECT_ALL_STORAGE_ID );
    if (monitor->ServerId()) {
      storageQuery = zmDbQuery( SELECT_ALL_STORAGE_ID_AND_SERVER_ID );
      storageQuery.bind("server_id", monitor->ServerId());
    }

    // bind and run
    storageQuery.bind("id", storage->Id());
    storageQuery.run(true);

    storage = nullptr;

    while( storageQuery.next() ) {
      int storageId = storageQuery.get<int>("Id");

      storage = new Storage(storageId);
      if (SetPath(storage))
        break;
      delete storage;
      storage = nullptr;
    }
    storageQuery.reset();

    if (!storage) {
      Info("No valid local storage area found.  Trying all other areas.");

      zmDbQuery storageRemoteQuery( SELECT_ALL_STORAGE_ID_WITH_SERVERID_NULL );
      if (monitor->ServerId()) {
        storageRemoteQuery = zmDbQuery( SELECT_ALL_STORAGE_ID_WITH_SERVERID_NULL_OR_DIFFERENT );
        storageRemoteQuery.bind( "server_id", monitor->ServerId() );
      }

      // Try remote
      while( storageRemoteQuery.next() ) {
        int storageId = storageRemoteQuery.get<int>("Id");

        storage = new Storage(storageId);
        if (SetPath(storage))
          break;
        delete storage;
        storage = nullptr;
      }
      storageRemoteQuery.reset();
    }
    if (!storage) {
      storage = new Storage();
      Warning("Failed to find a storage area to save events.");
    }

    // update the events storage id
    zmDbQuery q = zmDbQuery( UPDATE_EVENT_WITH_ID_SET_STORAGEID );
    q.bind("storage_id", storage->Id());
    q.bind("id", id);
    q.update();

  }  // end if ! setPath(Storage)
  Debug(1, "Using storage area at %s", path.c_str());

  snapshot_file = path + "/snapshot.jpg";
  alarm_file = path + "/alarm.jpg";

  video_incomplete_path = path + "/" + video_incomplete_file;

  if (monitor->GetOptVideoWriter() != 0) {
    /* Save as video */
    videoStore = new VideoStore(
        video_incomplete_path.c_str(),
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

        zmDbQuery q = zmDbQuery( UPDATE_EVENT_WITH_ID_SET_SAVEJPEGS );
        q.bind("save_jpegs", save_jpegs);
        q.bind("id", id);
        q.update();
      }
    } else {
      std::string codec = videoStore->get_codec();
      video_file = stringtf("%" PRIu64 "-%s.%s.%s", id, "video", codec.c_str(), container.c_str());
      video_path = path + "/" + video_file;
      Debug(1, "Video file is %s", video_file.c_str());
    }
  }  // end if GetOptVideoWriter
  if (storage != monitor->getStorage())
    delete storage;


  // The idea is to process the queue no matter what so that all packets get processed.
  // We only break if the queue is empty
  while (true) {
    ZMLockedPacket * packet_lock = nullptr;
    {
      std::unique_lock<std::mutex> lck(packet_queue_mutex);

      if (packet_queue.empty()) {
        if (terminate_ or zm_terminate) break;
        packet_queue_condition.wait(lck);
        // Neccessary because we don't hold the lock in the while condition
      } 
      if (!packet_queue.empty()) {
        // Packets on this queue are locked. They are locked by analysis thread
        packet_lock = packet_queue.front();
        packet_queue.pop();
      }
    }  // end lock scope
    if (packet_lock) {
      this->AddPacket_(packet_lock->packet_);
      delete packet_lock;
    }
  }  // end while
}  // end Run()

int Event::MonitorId() {
  return monitor->Id();
}
