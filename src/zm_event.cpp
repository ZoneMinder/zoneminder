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

#include "zm_event_tag.h"
#include "zm_frame.h"
#include "zm_monitor.h"
#include "zm_signal.h"
#include "zm_tag.h"
#include "zm_videostore.h"

#include <cstring>
#include <fcntl.h>
#include <dirent.h>
#include <sys/stat.h>
#include <unistd.h>
const char * Event::frame_type_names[3] = { "Normal", "Bulk", "Alarm" };
#define MAX_DB_FRAMES 100

int Event::pre_alarm_count = 0;

Event::PreAlarmData Event::pre_alarm_data[MAX_PRE_ALARM_FRAMES] = {};

Event::Event(
  Monitor *p_monitor,
  packetqueue_iterator *p_packetqueue_it,
  SystemTimePoint p_start_time,
  const std::string &p_cause,
  const StringSetMap &p_noteSetMap
) :
  id(0),
  monitor(p_monitor),
  storage(nullptr),
  packetqueue_it(p_packetqueue_it),
  start_time(p_start_time),
  end_time(p_start_time),
  cause(p_cause),
  noteSetMap(p_noteSetMap),
  frames(0),
  alarm_frames(0),
  alarm_frame_written(false),
  tot_score(0),
  max_score(-1),
  max_score_frame_id(0),
  //path(""),
  //snapshit_file(),
  snapshot_file_written(false),
  //alarm_file(""),
  videoStore(nullptr),
  mJpegCodecContext(nullptr),
  mJpegSwsContext(nullptr),
  hw_device_ctx(nullptr),
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

  packetqueue = monitor->GetPacketQueue();

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
    zmDbRow dbrow;
    if (dbrow.fetch("SELECT Id FROM States WHERE IsActive=1")) {
      state_id = atoi(dbrow[0]);
    }
  }

  // Copy it in case opening the mp4 doesn't work we can set it to another value
  save_jpegs = monitor->GetOptSaveJPEGs();
  storage = monitor->getStorage();
  if (monitor->GetOptVideoWriter() != 0) {
    container = monitor->OutputContainer();
    if (container == "auto" || container == "") {
      container = "mp4";
    }
    video_incomplete_file = "incomplete."+container;
  }

  std::string now_str = SystemTimePointToMysqlString(start_time);

  std::string sql = stringtf(
                      "INSERT INTO `Events` "
                      "( `MonitorId`, `StorageId`, `Name`, `StartDateTime`, `Width`, `Height`, `Cause`, `Notes`, `StateId`, `Orientation`, `Videoed`, `DefaultVideo`, `SaveJPEGs`, `Scheme`, `Latitude`, `Longitude` )"
                      " VALUES "
                      "( %d, %d, 'New Event', '%s', %u, %u, '%s', '%s', %d, %d, %d, '%s', %d, '%s', '%f', '%f' )",
                      monitor->Id(),
                      storage->Id(),
                      now_str.c_str(),
                      monitor->Width(),
                      monitor->Height(),
                      cause.c_str(),
                      notes.c_str(),
                      state_id,
                      monitor->getOrientation(),
                      0,
                      video_incomplete_file.c_str(),
                      save_jpegs,
                      storage->SchemeString().c_str(),
                      monitor->Latitude(),
                      monitor->Longitude()
                    );
  do {
    id = zmDbDoInsert(sql);
  } while (!id and !zm_terminate);

  /* None of these are crucial, and are simple when done as individual transactions */
  sql = stringtf("INSERT INTO `Events_Hour` (EventId,MonitorId,StartDateTime,DiskSpace)"
      " VALUES (%" PRId64 ",%u,'%s',NULL)",
      id, monitor->Id(), now_str.c_str());
  dbQueue.push(std::move(sql));
  sql = stringtf("INSERT INTO `Events_Day` (EventId,MonitorId,StartDateTime,DiskSpace)"
      " VALUES (%" PRId64 ",%u,'%s',NULL)",
      id, monitor->Id(), now_str.c_str());
  dbQueue.push(std::move(sql));
  sql = stringtf("INSERT INTO `Events_Week` (EventId,MonitorId,StartDateTime,DiskSpace)"
      " VALUES (%" PRId64 ",%u,'%s',NULL)",
      id, monitor->Id(), now_str.c_str());
  dbQueue.push(std::move(sql));
  sql = stringtf("INSERT INTO `Events_Month` (EventId,MonitorId,StartDateTime,DiskSpace)"
      " VALUES (%" PRId64 ",%u,'%s',NULL)",
      id, monitor->Id(), now_str.c_str());
  dbQueue.push(std::move(sql));
  /*
  sql = stringtf("INSERT INTO `Events_Year` (EventId,MonitorId,StartDateTime,DiskSpace)"
      " VALUES (%" PRId64 ",%u,'%s',NULL)",
      id, monitor->Id(), now_str.c_str());
  dbQueue.push(std::move(sql));
  */
  sql = stringtf("INSERT INTO Event_Summaries "
      "(MonitorId,HourEvents,DayEvents,WeekEvents,MonthEvents,TotalEvents)"
      " VALUES (%u,1,1,1,1,1) ON DUPLICATE KEY UPDATE"
      " HourEvents = COALESCE(HourEvents,0)+1,"
      " DayEvents = COALESCE(DayEvents,0)+1,"
      " WeekEvents = COALESCE(WeekEvents,0)+1,"
      " MonthEvents = COALESCE(MonthEvents,0)+1,"
      " TotalEvents = COALESCE(TotalEvents,0)+1",
      monitor->Id());
  dbQueue.push(std::move(sql));

  thread_ = std::thread(&Event::Run, this);
  set_cpu_affinity(thread_);
}

int Event::OpenJpegCodec(const Image *image) {
  av_frame_ptr frame(av_frame_alloc());
  image->PopulateFrame(frame.get());
  return OpenJpegCodec(frame.get());
}

int Event::OpenJpegCodec(AVFrame *frame) {
  if (!frame) return -1;
  if (mJpegCodecContext) {
    avcodec_free_context(&mJpegCodecContext);
    mJpegCodecContext = nullptr;
  }

  std::list<const CodecData *>codec_data = get_encoder_data("mjpeg", "");
  if (!codec_data.size()) {
    Error("No codecs for mjpeg found");
    return -1;
  }
  for (auto it = codec_data.begin(); it != codec_data.end(); it ++) {
    auto chosen_codec_data = *it;
    Debug(1, "Found video codec for %s", chosen_codec_data->codec_name);

    const AVCodec *mJpegCodec = avcodec_find_encoder_by_name(chosen_codec_data->codec_name);
    if (!mJpegCodec) {
      Error("MJPEG codec not found");
      continue;
    }
    // We allocate and copy in newer ffmpeg, so need to free it
    mJpegCodecContext = avcodec_alloc_context3(mJpegCodec);
    if (!mJpegCodecContext) {
      Error("Could not allocate jpeg codec context");
      continue;
    }

    mJpegCodecContext->bit_rate = 2000000;
    mJpegCodecContext->width = monitor->Width();
    mJpegCodecContext->height = monitor->Height();
    mJpegCodecContext->time_base= (AVRational) {1, 25};
    //mJpegCodecContext->time_base= (AVRational) {1, static_cast<int>(monitor->GetFPS())};
    mJpegCodecContext->pix_fmt = chosen_codec_data->sw_pix_fmt;
    mJpegCodecContext->sw_pix_fmt = chosen_codec_data->sw_pix_fmt;

    // Should be able to just set quality with the q setting.  Need to convert the old quality to 2-31
    int quality = libjpeg_to_ffmpeg_qv(config.jpeg_file_quality);

      //(alarm_frame && (config.jpeg_alarm_file_quality > config.jpeg_file_quality)) ?
      //config.jpeg_alarm_file_quality : 0;   // quality to use, zero is default
    //mJpegCodecContext->qcompress = quality/100.0; // 0-1
    //mJpegCodecContext->qmax = 1;
    //mJpegCodecContext->qmin = 1; //quality/100.0; // 0-1
    mJpegCodecContext->global_quality = quality;//100.0; // 0-1

    Debug(1, "Setting pix fmt to %d %s, sw_pix_fmt %d %s", 
        chosen_codec_data->sw_pix_fmt, av_get_pix_fmt_name(chosen_codec_data->sw_pix_fmt),
        chosen_codec_data->sw_pix_fmt, av_get_pix_fmt_name(chosen_codec_data->sw_pix_fmt));

    if (0 && setup_hwaccel(mJpegCodecContext,
          chosen_codec_data, hw_device_ctx, monitor->EncoderHWAccelDevice(), monitor->Width(), monitor->Height())) {
        avcodec_free_context(&mJpegCodecContext);
      continue;
    }

    if (avcodec_open2(mJpegCodecContext, mJpegCodec, NULL) < 0) {
      Error("Could not open mjpeg codec");
      avcodec_free_context(&mJpegCodecContext);
      av_buffer_unref(&hw_device_ctx);
      continue;
    }
    break;
  }
  if (!mJpegCodecContext) {
    return -1;
  }

  Debug(1, "Done opening codec");
  if (mJpegSwsContext) {
        //mJpegSwsContext->src_format != image->AVPixFormat())) {
    Debug(1, "Need to re-open swsContext. %d %s != %d %s",
        mJpegCodecContext->sw_pix_fmt,
        //mJpegSwsContext->src_format, 
        //av_get_pix_fmt_name(mJpegSwsContext->src_format), 
        av_get_pix_fmt_name(mJpegCodecContext->sw_pix_fmt),
        frame->format,
        av_get_pix_fmt_name(static_cast<AVPixelFormat>(frame->format))
        );
    sws_freeContext(mJpegSwsContext);
    mJpegSwsContext = nullptr;
  }

  if (!mJpegSwsContext) {
    Debug(1, "Getting swsContext for %dx%d %s to %dx%d %s", 
        frame->width, frame->height, av_get_pix_fmt_name(static_cast<AVPixelFormat>(frame->format)),
        mJpegCodecContext->width, mJpegCodecContext->height, av_get_pix_fmt_name(AV_PIX_FMT_YUVJ420P)
        );
    mJpegSwsContext = sws_getContext(
        frame->width, frame->height, static_cast<AVPixelFormat>(frame->format),
        mJpegCodecContext->width, mJpegCodecContext->height, AV_PIX_FMT_YUVJ420P,
        SWS_BICUBIC, nullptr, nullptr, nullptr);
    if (!mJpegSwsContext) {
      Error("Failure to get swscontext");
      return -1;
    }
  }
#if 1
  output_frame = av_frame_ptr{av_frame_alloc()}; // The assignment here will destruct any previous allocation
  output_frame->width  = mJpegCodecContext->width;
  output_frame->height = mJpegCodecContext->height;
  output_frame->format = AV_PIX_FMT_YUVJ420P;
  //av_image_fill_linesizes(frame->linesize, AV_PIX_FMT_YUVJ420P, p_jpegcodeccontext->width);
  av_frame_get_buffer(output_frame.get(), 0);
  zm_dump_video_frame(output_frame, "OpenCodec(output_frame)");
#endif

  return 0;
}

Event::~Event() {
  Debug(1, "~Event %" PRIu64 ": calling Stop", id);
  Stop();

  if (thread_.joinable()) {
    Debug(1, "~Event %" PRIu64 ": joining Run thread", id);
    thread_.join();
    Debug(1, "~Event %" PRIu64 ": Run thread joined", id);
  }
  Debug(1, "~Event %" PRIu64 ": freeing packetqueue iterator", id);
  packetqueue->free_it(packetqueue_it);

  /* Close the video file */
  // We close the videowriter first, because if we finish the event, we might try to view the file, but we aren't done writing it yet.
  if (videoStore != nullptr) {
    Debug(1, "~Event %" PRIu64 ": deleting video store", id);
    delete videoStore;
    videoStore = nullptr;
    int result = rename(video_incomplete_path.c_str(), video_path.c_str());
    if (result != 0) {
      Error("Failed renaming %s to %s, reason: %s", video_incomplete_path.c_str(), video_path.c_str(), strerror(errno));
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
  Debug(2, "start_time: %.2f end_time: %.2f, duration: %.2f",
        std::chrono::duration_cast<FPSeconds>(start_time.time_since_epoch()).count(),
        std::chrono::duration_cast<FPSeconds>(end_time.time_since_epoch()).count(),
        std::chrono::duration_cast<FPSeconds>(end_time.time_since_epoch() - start_time.time_since_epoch()).count()
        );

  if (frame_data.size()) WriteDbFrames();

  uint64_t video_size = 0;
  DIR *video_dir;
  if ((video_dir = opendir(path.c_str())) != NULL) {
    struct dirent *dir_entry;
    while ((dir_entry = readdir(video_dir)) != NULL) {
      struct stat vf_stat;
      if (stat((path + "/" + dir_entry->d_name).c_str(), &vf_stat) == 0 &&
          S_ISREG(vf_stat.st_mode))
        video_size += vf_stat.st_size;
    }
    closedir(video_dir);
  }

  std::string notes;
  createNotes(notes);
  // Use async dbQueue instead of synchronous zmDbDoUpdate to avoid blocking
  // the close_event_thread (which blocks the analysis thread on the next closeEvent).
  // Conditionally update Name only if it hasn't been changed by the user during recording.
  std::string sql = stringtf(
      "UPDATE Events SET"
      " Notes='%s',"
      " Name = IF(Name='New Event', '%s%" PRIu64 "', Name),"
      " EndDateTime = from_unixtime(%jd), Length = %.2f, Frames = %d,"
      " AlarmFrames = %d, TotScore = %d, AvgScore = %d, MaxScore = %d,"
      " MaxScoreFrameId=%d, DefaultVideo='%s', DiskSpace=%" PRIu64
      " WHERE Id = %" PRIu64,
      zmDbEscapeString(notes).c_str(),
      monitor->Substitute(monitor->EventPrefix(), start_time).c_str(), id,
      static_cast<intmax_t>(std::chrono::system_clock::to_time_t(end_time)),
      delta_time.count(),
      frames, alarm_frames,
      tot_score, static_cast<uint32>(alarm_frames ? (tot_score / alarm_frames) : 0),
      max_score, max_score_frame_id,
      video_file.c_str(), // defaults to ""
      video_size,
      id);
  dbQueue.push(std::move(sql));

  if (storage && storage->Id()) {
    sql = stringtf("UPDATE Storage SET DiskSpace = DiskSpace + %" PRIu64 " WHERE Id=%u", video_size, storage->Id());
    dbQueue.push(std::move(sql));
  }

  if (mJpegCodecContext) {
    avcodec_free_context(&mJpegCodecContext);
    mJpegCodecContext = nullptr;
  }

  if (mJpegSwsContext) {
    sws_freeContext(mJpegSwsContext);
  }
  av_buffer_unref(&hw_device_ctx);

}  // Event::~Event()

void Event::createNotes(std::string &notes) {
  notes.clear();
  for (StringSetMap::const_iterator mapIter = noteSetMap.begin(); mapIter != noteSetMap.end(); ++mapIter) {
    if (mapIter != noteSetMap.begin())
      notes += ", ";
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

/* written jpeg will be thewidthxheight in the codec context, not ours. */
bool Event::WriteJpeg(AVFrame *in_frame, const std::string &filename) {

  if (!mJpegCodecContext || !mJpegSwsContext
     // ||
      //(mJpegSwsContext->src_format != in_frame->format)
      // Apparently swsScale ignores src wxh anyways
      ) {
    Debug(1, "Need to open codec.  ctx %p", mJpegCodecContext);
    //OpenJpegCodec(image);
    OpenJpegCodec(in_frame);
  }

  if (!mJpegCodecContext) return false;

  int raw_fd = open(filename.c_str(), O_WRONLY | O_CREAT | O_TRUNC, S_IRUSR | S_IWUSR | S_IRGRP | S_IROTH);
  if (raw_fd < 0) {
    Error("Fail to open %s: %s", filename.c_str(), strerror(raw_fd));
    return false;
  }
  FILE *outfile = fdopen(raw_fd, "wb");
  if (outfile == nullptr) {
    close(raw_fd);
    return false;
  }

  struct flock fl = { F_WRLCK, SEEK_SET, 0,       0,     0 };
  if (fcntl(raw_fd, F_SETLKW, &fl) == -1) {
    Error("Couldn't get lock on %s, continuing", filename.c_str());
  }

  Debug(1, "Have sws context, converting from %dx%d %s to %dx%d %s",
      in_frame->width, in_frame->height, av_get_pix_fmt_name(static_cast<AVPixelFormat>(in_frame->format)),
      mJpegCodecContext->width, mJpegCodecContext->width, av_get_pix_fmt_name(AV_PIX_FMT_YUVJ420P)
      );

#if 0
  av_frame_ptr out_frame = av_frame_ptr{av_frame_alloc()};
  out_frame->width  = mJpegCodecContext->width;
  out_frame->height = mJpegCodecContext->height;
  out_frame->format = AV_PIX_FMT_YUVJ420P;
  //av_image_fill_linesizes(frame->linesize, AV_PIX_FMT_YUVJ420P, p_jpegcodeccontext->width);
  av_frame_get_buffer(out_frame.get(), 0);
  zm_dump_video_frame(out_frame, "OpenCodec(output_frame)");
  zm_dump_video_frame(in_frame, "OpenCodec(in_frame)");
#endif

  int ret = sws_scale(mJpegSwsContext, in_frame->data, in_frame->linesize, 0, in_frame->height, output_frame->data, output_frame->linesize);
  if (ret < 0) {
    Error("cannot do sw scale: inframe data 0x%lx, linesize %d/%d/%d/%d, height %d to %d linesize",
        (unsigned long)in_frame->data, in_frame->linesize[0], in_frame->linesize[1],
        in_frame->linesize[2], in_frame->linesize[3], in_frame->height, output_frame->linesize[0]);
    return ret;
  }

  zm_dump_video_frame(in_frame, "Image.WriteJpeg(frame)");

  ret = avcodec_send_frame(mJpegCodecContext, output_frame.get());
  while (ret == AVERROR(EAGAIN) and !zm_terminate)
    ret = avcodec_send_frame(mJpegCodecContext, output_frame.get());
  Debug(1, "Retcode from avcodec_send_frame, %d", ret);
  if (ret == 0) {
    Debug(1, "After send frame");
    AVPacket *pkt = av_packet_alloc();
    while (!zm_terminate) {
      Debug(1, "Getting packet");
      ret = avcodec_receive_packet(mJpegCodecContext, pkt);
      if (ret == 0) {
       // or ret == AVERROR(EOF)) {  // EOF is ok because it is jpeg:
        Debug(1, "Got good packet, writing %d bytes to %s", pkt->size, filename.c_str());
        fwrite(pkt->data, 1, pkt->size, outfile);
        break;
      } else if (ret == AVERROR(EAGAIN)) {
        Debug(1, "EAGAIN");
      } else if (ret < 0) {
        Warning("Error getting packet %d %s", ret, av_make_error_string(ret).c_str());
        if (pkt->size) {
          Debug(1, "Got good packet, writing %d bytes to %s", pkt->size, filename.c_str());
          fwrite(pkt->data, 1, pkt->size, outfile);
        }
        avcodec_free_context(&mJpegCodecContext);
        mJpegCodecContext = nullptr;
        break;
      }
    }  // end while
    av_packet_free(&pkt);
  } else {
    Error("Ret from send_frame %d", ret);
  }

  fl.l_type = F_UNLCK;  /* set to unlock same region */
  if (fcntl(raw_fd, F_SETLK, &fl) == -1) {
    Error("Failed to unlock %s", filename.c_str());
  }

  fclose(outfile);

  return true;
} // end bool Event::WriteJpeg(const std::string &filename, AVCodecContext *p_jpegcodeccontext, SwsContext *p_jpegswscontext)

bool Event::WriteFrameImage(Image *image, SystemTimePoint timestamp, const char *event_file, bool alarm_frame) {
  /*
  int thisquality =
    (alarm_frame && (config.jpeg_alarm_file_quality > config.jpeg_file_quality)) ?
    config.jpeg_alarm_file_quality : 0;   // quality to use, zero is default

  SystemTimePoint jpeg_timestamp = monitor->Exif() ? timestamp : SystemTimePoint();
  */
  if (!mJpegCodecContext || (mJpegSwsContext && (mJpegCodecContext->sw_pix_fmt != image->AVPixFormat()))) {
    Debug(1, "Need to open codec.  ctx %p", mJpegCodecContext);
    OpenJpegCodec(image);
  }
  if (!mJpegCodecContext) return false;

  if (!config.timestamp_on_capture) {
    // stash the image we plan to use in another pointer regardless if timestamped.
    // exif is only timestamp at present this switches on or off for write
    Image ts_image(*image);
    monitor->TimestampImage(&ts_image, timestamp);
    return ts_image.WriteJpeg(event_file, mJpegCodecContext, mJpegSwsContext);
  }
  return image->WriteJpeg(event_file, mJpegCodecContext, mJpegSwsContext);
}

bool Event::WritePacket(const std::shared_ptr<ZMPacket>packet) {
  if (videoStore->writePacket(packet) < 0)
    return false;
  return true;
}  // bool Event::WritePacket

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

  if (0 and update) {
    std::string notes;
    createNotes(notes);

    Debug(2, "Updating notes for event %" PRIu64 ", '%s'", id, notes.c_str());

    std::string sql = stringtf("UPDATE `Events` SET `Notes` = '%s' WHERE `Id` = %" PRIu64,
                               zmDbEscapeString(notes).c_str(), id);
    dbQueue.push(std::move(sql));
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
#if ZM_HAS_NLOHMANN_JSON
  if (packet->detections.size()) {
    std::string sql = stringtf("INSERT INTO Event_Data (EventId,MonitorId,FrameId,Timestamp,Data) VALUES (%" PRId64 ", %d, %d, NOW(), '%s')", id, monitor->Id(), frames, packet->detections.dump().c_str());
    dbQueue.push(std::move(sql));

    for (auto it = packet->detections.begin(); it != packet->detections.end(); ++it) {
      auto detection = *it;
      Debug(1, "detection %s", detection.dump().c_str());
      std::string cls = detection["class"];
      Tag *tag = nullptr;
      auto tag_it = tags.find(cls);
      if (tag_it == tags.end()) {
        tag = Tag::find(cls);
        if (!tag) {
          tag = new Tag();
          tag->Name(cls);
          tag->save();
          Debug(1, "Created new Tag %s", cls.c_str());
        }
        tags.emplace(std::make_pair(cls, *tag));
        int tag_id = tag->Id();
        delete tag;  // Delete after copying into map
        tag = nullptr;

        if (tag_id) {
          // Store
          Event_Tag event_tag(tag_id, id, packet->timestamp);
          event_tag.save();
        }
      } else {
        Debug(1, "Already have tag %s", cls.c_str());
        tag = &(tag_it->second);
      }
    }  // end foreach detection
  } else {
    Debug(3, "Detections is empty.");
  } // end if detections
#endif

  end_time = packet->timestamp;
} // end void Event::AddPacket_(const std::shared_ptr<ZMPacket>packet) {

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
    frame_insert_sql += stringtf("\n( %" PRIu64 ", %d, '%s', from_unixtime( %jd ), %.2f, %d ),",
                                 id, frame->frame_id,
                                 frame_type_names[frame->type],
                                 static_cast<intmax_t>(std::chrono::system_clock::to_time_t(frame->timestamp)),
                                 std::chrono::duration_cast<FPSeconds>(frame->delta).count(),
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
                               ( monitor_state == Monitor::IDLE )
                               and
                               ( config.bulk_frame_interval > 1 )
                               and
                               ( ! (frames % config.bulk_frame_interval) )
                             ) ? BULK : NORMAL
                           ) );

  if (frame_type == ALARM) alarm_frames++;

  Debug(1, "Have frame type %s from score(%d) state %d frames %d bulk frame interval %d and mod%d",
        frame_type_names[frame_type], score, monitor_state, frames, config.bulk_frame_interval, (frames % config.bulk_frame_interval));

  if (score < 0) score = 0;
  tot_score += score;

  if (save_jpegs & 1) {
    std::string event_file = stringtf(staticConfig.capture_file_format.c_str(), path.c_str(), frames);
    Debug(1, "Writing capture frame %d to %s", frames, event_file.c_str());
    if (
        (packet->ai_frame and WriteJpeg(packet->ai_frame.get(), event_file.c_str()))
        or
        (packet->in_frame and WriteJpeg(packet->in_frame.get(), event_file.c_str()))
        or
        (packet->image and WriteFrameImage(packet->image, packet->timestamp, event_file.c_str()))
       ) {
      Debug(1, "Wrote capture frame %d to %s", frames, event_file.c_str());
      // Success
    } else {
      Error("Failed to write frame image");
    }
  }  // end if save_jpegs

  Debug(1, "frames %d, score %d max_score %d", frames, score, max_score);
  // If this is the first frame, we should add a thumbnail to the event directory
  if ((frames == 1) || (score > max_score) || (!snapshot_file_written)) {
    write_to_db = true; // web ui might show this as thumbnail, so db needs to know about it.
    Debug(1, "Writing snapshot to %s", snapshot_file.c_str());
    if (
        (packet->ai_frame and WriteJpeg(packet->ai_frame.get(), snapshot_file.c_str()))
        or
        (packet->in_frame and WriteJpeg(packet->in_frame.get(), snapshot_file.c_str()))
        or
        (packet->image and WriteFrameImage(packet->image, packet->timestamp, snapshot_file.c_str()))
       ) {
      snapshot_file_written = true;
    } else if (packet->ai_frame or packet->in_frame or packet->image) {
      Warning("Fail to write snapshot");
    }
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
      if (packet->ai_frame) {
        WriteJpeg(packet->ai_frame.get(), alarm_file.c_str());
      } else if (packet->in_frame) {
        WriteJpeg(packet->in_frame.get(), alarm_file.c_str());
      } else if (packet->image) {
        WriteFrameImage(packet->image, packet->timestamp, alarm_file.c_str());
      }
#if 0
      if (!WriteFrameImage(packet->image, packet->timestamp, alarm_file.c_str())) {
        Error("Failed to write alarm frame image to %s", alarm_file.c_str());
      }
#endif
    } else {
      Debug(3, "Not Writing alarm image because alarm frame already written");
    }
  } // end if is an alarm frame

  if (save_jpegs & 2) {
    if (packet->analysis_image) {
      std::string event_file = stringtf(staticConfig.analyse_file_format.c_str(), path.c_str(), frames);
      Debug(1, "Writing analysis frame %d to %s", frames, event_file.c_str());
      if (!WriteFrameImage(packet->analysis_image, packet->timestamp, event_file.c_str(), true)) {
        Error("Failed to write analysis frame image to %s", event_file.c_str());
      }
    } else {
      Debug(1, "Wanted to save analysis frame, but packet has no analysis_image");
    }  // end if is an alarm frame
  }  // end if has analysis images turned on

  bool db_frame = ( frame_type == BULK )
                  or ( frame_type == ALARM )
                  or ( frames == 1 )
                  or ( score > max_score )
                  or ( monitor_state == Monitor::ALERT )
                  or ( monitor_state == Monitor::ALARM )
                  or ( monitor_state == Monitor::PREALARM );

  if (score > max_score) {
    max_score = score;
    max_score_frame_id = frames;
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

      std::string notes;
      createNotes(notes);
      std::string sql = stringtf(
                          "UPDATE Events SET Notes='%s', Length = %.2f, Frames = %d, AlarmFrames = %d, TotScore = %d, AvgScore = %d, MaxScore = %d, MaxScoreFrameId=%d WHERE Id = %" PRIu64,
                          zmDbEscapeString(notes).c_str(),
                          FPSeconds(delta_time).count(),
                          frames,
                          alarm_frames,
                          tot_score,
                          static_cast<uint32>(alarm_frames ? (tot_score / alarm_frames) : 0),
                          max_score,
                          max_score_frame_id,
                          id);
      dbQueue.push(std::move(sql));
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
  Debug(1, "Event::Run %" PRIu64 ": starting setup", id);
  Storage *storage = monitor->getStorage();
  if (!SetPath(storage)) {
    // Try another
    Warning("Failed creating event dir at %s", storage->Path());

    std::string sql = stringtf("SELECT `Id` FROM `Storage` WHERE `Id` != %u AND `Enabled`=true", storage->Id());
    if (monitor->ServerId())
      sql += stringtf(" AND ServerId=%u", monitor->ServerId());

    storage = nullptr;

    MYSQL_RES *result = zmDbFetch(sql);
    if (result) {
       while(MYSQL_ROW dbrow = mysql_fetch_row(result)) {
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

      result = zmDbFetch(sql);
      if (result) {
        while (MYSQL_ROW dbrow = mysql_fetch_row(result)) {
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
    zmDbDo(sql);
  }  // end if ! setPath(Storage)
  Debug(1, "Using storage area at %s", path.c_str());

  snapshot_file = path + "/snapshot.jpg";
  alarm_file = path + "/alarm.jpg";

  video_incomplete_path = path + "/" + video_incomplete_file;

  if (monitor->GetOptVideoWriter() != 0) {
    AVCodecContext *video_ctx = monitor->GetVideoCodecContext();

    /* Save as video */
    videoStore = new VideoStore(
      video_incomplete_path.c_str(),
      container.c_str(),
      monitor->GetVideoStream(),
      video_ctx,
      ( monitor->RecordAudio() ? monitor->GetAudioStream() : nullptr ),
      ( monitor->RecordAudio() ? monitor->GetAudioCodecContext() : nullptr ),
      monitor );

    if (!videoStore->open()) {
      Warning("Failed to open videostore, turning on jpegs");
      delete videoStore;
      videoStore = nullptr;
      if (!(save_jpegs & 1)) {
        save_jpegs |= 1; // Turn on jpeg storage
        zmDbDo(stringtf("UPDATE Events SET SaveJpegs=%d, DefaultVideo='' WHERE Id=%" PRIu64, save_jpegs, id));
      }
    } else {
      const AVCodec *encoder = videoStore->get_video_encoder();
      if (encoder) {
        noteSetMap["encoder"].insert(encoder->name);
      }

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
  Debug(1, "Event::Run %" PRIu64 ": entering packet loop", id);
  while (!terminate_ and !zm_terminate) {
    // I don't exactly remember why the no_wait
    ZMPacketLock packet_lock = packetqueue->get_packet_no_wait(packetqueue_it);
    std::shared_ptr<ZMPacket> packet = packet_lock.packet_;
    if (packet) {
      if (!packet->decoded) {
        Debug(1, "Not decoded");
        packet_lock.unlock();
        packetqueue->wait_for(Microseconds(ZM_SAMPLE_RATE));
        continue;
      }
      if (!packet->analyzed) {
        Debug(1, "Not analyzed");
        packet_lock.unlock();
        packetqueue->wait_for(Microseconds(ZM_SAMPLE_RATE));
        continue;
      }

      Debug(1, "Adding packet %d", packet->image_index);
      this->AddPacket_(packet);

      // Use wait=false: deletePacket may have advanced our iterator to end()
      // while we were in AddPacket_ without the queue lock.
      packetqueue->increment_it(packetqueue_it, false);
    } else {
      if (terminate_ or zm_terminate) return;
      packetqueue->wait_for(Microseconds(10000));
    }
  }  // end while
}  // end Run()

int Event::MonitorId() const {
  return monitor->Id();
}
