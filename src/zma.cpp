//
// ZoneMinder Event Re-Analysis Utility
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

/*

=head1 NAME

zma - The ZoneMinder Event Re-Analysis utility

=head1 SYNOPSIS

 zma -e <event_id>
 zma --event <event_id>
 zma -e <event_id> -m <monitor_id>
 zma -e <event_id> --create-events
 zma -e <event_id> --save-analysis
 zma -h
 zma --help
 zma -v
 zma --version

=head1 DESCRIPTION

This utility re-analyses a recorded event using the current zone settings
for the monitor. It decodes each frame from the event's stored video or
JPEG files and runs the full motion detection pipeline.

By default, it updates the existing event's motion statistics (AlarmFrames,
TotScore, AvgScore, MaxScore) in the database. With --create-events, it
instead creates new events from the detected motion regions.

This enables tuning zone settings and re-running analysis on existing footage
without needing live cameras.

=head1 OPTIONS

 -e, --event <event_id>           - Event ID to re-analyse (required)
 -m, --monitor <monitor_id>       - Override monitor ID (use a different monitor's zone config)
 -c, --create-events              - Create new events instead of updating the original
 -a, --save-analysis              - Write analysis JPEGs showing zone alarm overlays
 -v, --verbose                    - Increase verbosity
 -h, --help                       - Display usage information
 -V, --version                    - Print the installed version of ZoneMinder

=cut

*/

#include "zm.h"
#include "zm_config.h"
#include "zm_db.h"
#include "zm_define.h"
#include "zm_event.h"
#include "zm_ffmpeg_input.h"
#include "zm_image.h"
#include "zm_monitor.h"
#include "zm_signal.h"
#include "zm_storage.h"
#include "zm_time.h"
#include "zm_utils.h"

#include <filesystem>
#include <getopt.h>
#include <iostream>

void Usage() {
  fprintf(stderr, "zma -e <event_id> [-m <monitor_id>] [-c] [-a]\n");
  fprintf(stderr, "\nOptions:\n");
  fprintf(stderr, "  -e, --event <event_id>       : Event ID to re-analyse (required)\n");
  fprintf(stderr, "  -m, --monitor <monitor_id>   : Override monitor ID for zone config\n");
  fprintf(stderr, "  -c, --create-events          : Create new events instead of updating the original\n");
  fprintf(stderr, "  -a, --save-analysis          : Write analysis JPEGs showing zone alarm overlays\n");
  fprintf(stderr, "  -v, --verbose                : Increase debug verbosity\n");
  fprintf(stderr, "  -h, --help                   : This screen\n");
  fprintf(stderr, "  -V, --version                : Report the installed version of ZoneMinder\n");
  exit(0);
}

struct ReanalysisEvent {
  uint64_t db_event_id;
  SystemTimePoint start_time;
  int frames;
  int alarm_frames;
  unsigned int tot_score;
  unsigned int max_score;

  ReanalysisEvent() : db_event_id(0), frames(0), alarm_frames(0), tot_score(0), max_score(0) {}
};

// Finalize a created event by updating its end time and score statistics
static void FinalizeCreatedEvent(ReanalysisEvent &ev, SystemTimePoint end_time, const std::string &scheme_str) {
  if (!ev.db_event_id) return;
  unsigned int avg_score = ev.alarm_frames > 0 ? ev.tot_score / ev.alarm_frames : 0;
  Microseconds duration = std::chrono::duration_cast<Microseconds>(end_time - ev.start_time);

  std::string sql = stringtf(
    "UPDATE `Events` SET "
    "`EndDateTime` = from_unixtime(%" PRIi64 "), "
    "`Length` = %f, "
    "`Frames` = %d, "
    "`AlarmFrames` = %d, "
    "`TotScore` = %u, "
    "`AvgScore` = %u, "
    "`MaxScore` = %u "
    "WHERE `Id` = %" PRIu64,
    static_cast<int64>(std::chrono::duration_cast<Seconds>(end_time.time_since_epoch()).count()),
    FPSeconds(duration).count(),
    ev.frames,
    ev.alarm_frames,
    ev.tot_score,
    avg_score,
    ev.max_score,
    ev.db_event_id);
  zmDbDo(sql);
  Info("Finalized re-analysis event %" PRIu64 ": frames=%d, alarm_frames=%d, max_score=%u",
       ev.db_event_id, ev.frames, ev.alarm_frames, ev.max_score);
}

// Build the filesystem path for a new event based on the storage scheme
static std::string BuildNewEventPath(
    const char *storage_path,
    unsigned int monitor_id,
    uint64_t new_event_id,
    SystemTimePoint event_start,
    Storage::Schemes scheme) {
  if (scheme == Storage::DEEP) {
    tm event_time = {};
    time_t start_time_t = std::chrono::system_clock::to_time_t(event_start);
    localtime_r(&start_time_t, &event_time);

    if (storage_path[0] == '/') {
      return stringtf("%s/%u/%02d/%02d/%02d/%02d/%02d/%02d",
                      storage_path, monitor_id,
                      event_time.tm_year - 100, event_time.tm_mon + 1, event_time.tm_mday,
                      event_time.tm_hour, event_time.tm_min, event_time.tm_sec);
    } else {
      return stringtf("%s/%s/%u/%02d/%02d/%02d/%02d/%02d/%02d",
                      staticConfig.PATH_WEB.c_str(), storage_path, monitor_id,
                      event_time.tm_year - 100, event_time.tm_mon + 1, event_time.tm_mday,
                      event_time.tm_hour, event_time.tm_min, event_time.tm_sec);
    }
  } else if (scheme == Storage::MEDIUM) {
    tm event_time = {};
    time_t start_time_t = std::chrono::system_clock::to_time_t(event_start);
    localtime_r(&start_time_t, &event_time);

    if (storage_path[0] == '/') {
      return stringtf("%s/%u/%04d-%02d-%02d/%" PRIu64,
                      storage_path, monitor_id,
                      event_time.tm_year + 1900, event_time.tm_mon + 1, event_time.tm_mday,
                      new_event_id);
    } else {
      return stringtf("%s/%s/%u/%04d-%02d-%02d/%" PRIu64,
                      staticConfig.PATH_WEB.c_str(), storage_path, monitor_id,
                      event_time.tm_year + 1900, event_time.tm_mon + 1, event_time.tm_mday,
                      new_event_id);
    }
  } else {
    if (storage_path[0] == '/') {
      return stringtf("%s/%u/%" PRIu64, storage_path, monitor_id, new_event_id);
    } else {
      return stringtf("%s/%s/%u/%" PRIu64,
                      staticConfig.PATH_WEB.c_str(), storage_path, monitor_id,
                      new_event_id);
    }
  }
}

// Hard link (or copy as fallback) video files from source event to new event directory,
// and set DefaultVideo in DB
static void LinkEventFiles(
    const std::string &source_path,
    const std::string &dest_path,
    const std::string &video_file,
    uint64_t new_event_id) {
  namespace fs = std::filesystem;
  std::error_code ec;

  fs::create_directories(dest_path, ec);
  if (ec) {
    Error("Failed to create directory %s: %s", dest_path.c_str(), ec.message().c_str());
    return;
  }

  if (!video_file.empty()) {
    std::string src = source_path + "/" + video_file;
    std::string dst = dest_path + "/" + video_file;

    if (fs::exists(src, ec)) {
      // Try hard link first (same filesystem, no extra disk usage)
      fs::create_hard_link(src, dst, ec);
      if (ec) {
        Debug(1, "Hard link failed (%s), falling back to copy", ec.message().c_str());
        ec.clear();
        fs::copy_file(src, dst, fs::copy_options::overwrite_existing, ec);
        if (ec) {
          Error("Failed to copy %s to %s: %s", src.c_str(), dst.c_str(), ec.message().c_str());
          return;
        }
        Info("Copied video file to %s", dst.c_str());
      } else {
        Info("Hard linked video file to %s", dst.c_str());
      }
      std::string sql = stringtf(
        "UPDATE `Events` SET `DefaultVideo` = '%s' WHERE `Id` = %" PRIu64,
        video_file.c_str(), new_event_id);
      zmDbDo(sql);
    } else {
      Warning("Source video file %s not found", src.c_str());
    }
  }
}

int main(int argc, char *argv[]) {
  self = argv[0];

  srand(getpid() * time(nullptr));

  uint64_t event_id = 0;
  int monitor_id = -1;
  int verbose = 0;
  bool create_events = false;
  bool save_analysis = false;

  static struct option long_options[] = {
    {"event",         1, nullptr, 'e'},
    {"monitor",       1, nullptr, 'm'},
    {"create-events", 0, nullptr, 'c'},
    {"save-analysis", 0, nullptr, 'a'},
    {"verbose",       0, nullptr, 'v'},
    {"help",          0, nullptr, 'h'},
    {"version",       0, nullptr, 'V'},
    {nullptr,         0, nullptr, 0}
  };

  while (1) {
    int option_index = 0;
    int c = getopt_long(argc, argv, "e:m:cavhV", long_options, &option_index);
    if (c == -1)
      break;

    switch (c) {
    case 'e':
      event_id = strtoull(optarg, nullptr, 10);
      break;
    case 'm':
      monitor_id = atoi(optarg);
      break;
    case 'c':
      create_events = true;
      break;
    case 'a':
      save_analysis = true;
      break;
    case 'v':
      verbose++;
      break;
    case 'h':
    case '?':
      Usage();
      break;
    case 'V':
      std::cout << ZM_VERSION << "\n";
      exit(0);
    default:
      break;
    }
  }

  if (optind < argc) {
    fprintf(stderr, "Extraneous options, ");
    while (optind < argc)
      printf("%s ", argv[optind++]);
    printf("\n");
    Usage();
  }

  if (event_id == 0) {
    fprintf(stderr, "Event ID is required (-e <event_id>)\n");
    Usage();
  }

  char log_id_string[32] = "";
  snprintf(log_id_string, sizeof(log_id_string), "zma_e%" PRIu64, event_id);

  logInit(log_id_string);
  zmLoadStaticConfig();
  zmDbConnect();
  zmLoadDBConfig();
  logInit(log_id_string);

  if (verbose) {
    Logger::fetch()->level(static_cast<Logger::Level>(Logger::DEBUG1 + verbose - 1));
  }

  HwCapsDetect();

  zmSetDefaultTermHandler();
  zmSetDefaultDieHandler();

  Info("Starting Event Re-Analysis version %s for event %" PRIu64 " (mode: %s%s)",
       ZM_VERSION, event_id, create_events ? "create-events" : "update-existing",
       save_analysis ? ", save-analysis" : "");

  // Step 1: Load event data from DB
  std::string sql = stringtf(
    "SELECT `MonitorId`, `StorageId`, `Frames`, unix_timestamp(`StartDateTime`) AS StartTimestamp, "
    "unix_timestamp(`EndDateTime`) AS EndTimestamp, `Length`, "
    "`DefaultVideo`, `Scheme`, `SaveJPEGs`, `Orientation`+0 "
    "FROM `Events` WHERE `Id` = %" PRIu64, event_id);

  MYSQL_RES *result = zmDbFetch(sql);
  if (!result) {
    Fatal("Failed to fetch event %" PRIu64, event_id);
  }
  if (!mysql_num_rows(result)) {
    mysql_free_result(result);
    Fatal("Event %" PRIu64 " not found in database", event_id);
  }

  MYSQL_ROW dbrow = mysql_fetch_row(result);
  if (!dbrow) {
    mysql_free_result(result);
    Fatal("Failed to fetch row for event %" PRIu64, event_id);
  }

  unsigned int event_monitor_id = atoi(dbrow[0]);
  unsigned int storage_id = dbrow[1] ? atoi(dbrow[1]) : 0;
  int frame_count = dbrow[2] ? atoi(dbrow[2]) : 0;
  SystemTimePoint start_time = SystemTimePoint(Seconds(atoi(dbrow[3])));
  std::string video_file = dbrow[6] ? std::string(dbrow[6]) : std::string();
  std::string scheme_str = dbrow[7] ? std::string(dbrow[7]) : std::string();
  int save_jpegs = dbrow[8] ? atoi(dbrow[8]) : 0;

  Storage::Schemes scheme;
  if (scheme_str == "Deep") {
    scheme = Storage::DEEP;
  } else if (scheme_str == "Medium") {
    scheme = Storage::MEDIUM;
  } else {
    scheme = Storage::SHALLOW;
  }

  mysql_free_result(result);

  Info("Event %" PRIu64 ": monitor=%u, frames=%d, video=%s, scheme=%s, save_jpegs=%d",
       event_id, event_monitor_id, frame_count,
       video_file.empty() ? "(none)" : video_file.c_str(),
       scheme_str.c_str(), save_jpegs);

  // Step 2: Construct event path
  Storage storage(storage_id);
  const char *storage_path = storage.Path();
  std::string event_path;

  if (scheme == Storage::DEEP) {
    tm event_time = {};
    time_t start_time_t = std::chrono::system_clock::to_time_t(start_time);
    localtime_r(&start_time_t, &event_time);

    if (storage_path[0] == '/') {
      event_path = stringtf("%s/%u/%02d/%02d/%02d/%02d/%02d/%02d",
                            storage_path, event_monitor_id,
                            event_time.tm_year - 100, event_time.tm_mon + 1, event_time.tm_mday,
                            event_time.tm_hour, event_time.tm_min, event_time.tm_sec);
    } else {
      event_path = stringtf("%s/%s/%u/%02d/%02d/%02d/%02d/%02d/%02d",
                            staticConfig.PATH_WEB.c_str(), storage_path, event_monitor_id,
                            event_time.tm_year - 100, event_time.tm_mon + 1, event_time.tm_mday,
                            event_time.tm_hour, event_time.tm_min, event_time.tm_sec);
    }
  } else if (scheme == Storage::MEDIUM) {
    tm event_time = {};
    time_t start_time_t = std::chrono::system_clock::to_time_t(start_time);
    localtime_r(&start_time_t, &event_time);

    if (storage_path[0] == '/') {
      event_path = stringtf("%s/%u/%04d-%02d-%02d/%" PRIu64,
                            storage_path, event_monitor_id,
                            event_time.tm_year + 1900, event_time.tm_mon + 1, event_time.tm_mday,
                            event_id);
    } else {
      event_path = stringtf("%s/%s/%u/%04d-%02d-%02d/%" PRIu64,
                            staticConfig.PATH_WEB.c_str(), storage_path, event_monitor_id,
                            event_time.tm_year + 1900, event_time.tm_mon + 1, event_time.tm_mday,
                            event_id);
    }
  } else {
    if (storage_path[0] == '/') {
      event_path = stringtf("%s/%u/%" PRIu64, storage_path, event_monitor_id, event_id);
    } else {
      event_path = stringtf("%s/%s/%u/%" PRIu64,
                            staticConfig.PATH_WEB.c_str(), storage_path, event_monitor_id,
                            event_id);
    }
  }

  Info("Event path: %s", event_path.c_str());

  // Step 3: Load frame list from DB
  sql = stringtf("SELECT `FrameId`, unix_timestamp(`TimeStamp`), `Delta` "
                 "FROM `Frames` WHERE `EventId` = %" PRIu64 " ORDER BY `FrameId` ASC", event_id);
  result = zmDbFetch(sql);
  if (!result) {
    Fatal("Failed to fetch frames for event %" PRIu64, event_id);
  }

  struct FrameData {
    int id;
    SystemTimePoint timestamp;
    Microseconds offset;
    bool in_db;
  };

  std::vector<FrameData> frames;
  int n_db_frames = mysql_num_rows(result);
  frames.reserve(frame_count > n_db_frames ? frame_count : n_db_frames);

  int last_id = 0;
  SystemTimePoint last_timestamp = start_time;
  Microseconds last_offset = Seconds(0);

  while ((dbrow = mysql_fetch_row(result))) {
    int id = atoi(dbrow[0]);
    Microseconds offset = std::chrono::duration_cast<Microseconds>(FPSeconds(atof(dbrow[2])));
    SystemTimePoint timestamp = start_time + offset;

    int id_diff = id - last_id;
    Microseconds delta = id_diff ? (offset - last_offset) / id_diff : (offset - last_offset);

    // Fill gaps between bulk frames
    if (id_diff > 1) {
      for (int i = last_id + 1; i < id; i++) {
        Microseconds gap_offset = last_offset + (i - last_id) * delta;
        frames.push_back({i, start_time + gap_offset, gap_offset, false});
      }
    }

    frames.push_back({id, timestamp, offset, true});
    last_id = id;
    last_offset = offset;
    last_timestamp = timestamp;
  }
  mysql_free_result(result);

  if (frames.empty()) {
    Fatal("No frames found for event %" PRIu64, event_id);
  }

  Info("Loaded %zu frames from database (%d in DB)", frames.size(), n_db_frames);

  // Step 4: Load monitor with zones
  unsigned int analysis_monitor_id = (monitor_id > 0) ? static_cast<unsigned int>(monitor_id) : event_monitor_id;
  std::shared_ptr<Monitor> monitor = Monitor::Load(analysis_monitor_id, true, Monitor::ANALYSIS);
  if (!monitor) {
    Fatal("Failed to load monitor %u", analysis_monitor_id);
  }

  // Zones are loaded in connect() which requires shared memory.
  // For offline analysis we load zones directly.
  monitor->ReloadZones();

  Info("Loaded monitor %u '%s' with zones for analysis", monitor->Id(), monitor->Name());

  // Step 5: Open frame source
  bool use_video = !video_file.empty();
  bool use_jpegs = (save_jpegs & 1);
  FFmpeg_Input *ffmpeg_input = nullptr;

  if (use_video) {
    std::string filepath = event_path + "/" + video_file;
    Info("Opening video file: %s", filepath.c_str());
    ffmpeg_input = new FFmpeg_Input();
    if (ffmpeg_input->Open(filepath.c_str()) < 0) {
      Warning("Failed to open video file %s, trying JPEGs", filepath.c_str());
      delete ffmpeg_input;
      ffmpeg_input = nullptr;
      use_video = false;
    }
  }

  if (!use_video && !use_jpegs) {
    Fatal("Event %" PRIu64 " has no video file and SaveJPEGs is not enabled - no frames to analyse", event_id);
  }

  // State machine variables
  Monitor::State state = Monitor::IDLE;
  int alarm_frame_count = 0;     // consecutive alarm frames counter
  int last_alarm_frame_idx = 0;  // index of last alarmed frame in our loop
  int analysis_count = 0;

  // Score tracking for update-existing mode
  int total_alarm_frames = 0;
  unsigned int total_tot_score = 0;
  unsigned int total_max_score = 0;

  // Event creation tracking for create-events mode
  std::vector<ReanalysisEvent> new_events;
  ReanalysisEvent *current_event = nullptr;

  int monitor_alarm_frame_count = 3;  // default
  int monitor_post_event_count = 0;

  // Read alarm_frame_count and post_event_count from monitor DB config
  sql = stringtf("SELECT `AlarmFrameCount`, `PostEventCount` FROM `Monitors` WHERE `Id` = %u", monitor->Id());
  result = zmDbFetch(sql);
  if (result) {
    dbrow = mysql_fetch_row(result);
    if (dbrow) {
      monitor_alarm_frame_count = dbrow[0] ? atoi(dbrow[0]) : 3;
      monitor_post_event_count = dbrow[1] ? atoi(dbrow[1]) : 0;
    }
    mysql_free_result(result);
  }

  Info("Analysis parameters: alarm_frame_count=%d, post_event_count=%d",
       monitor_alarm_frame_count, monitor_post_event_count);

  int video_stream_id = -1;
  if (ffmpeg_input) {
    video_stream_id = ffmpeg_input->get_video_stream_id();
  }

  // Step 6: Process each frame
  for (size_t frame_idx = 0; frame_idx < frames.size() && !zm_terminate; frame_idx++) {
    const FrameData &fd = frames[frame_idx];
    Image *frame_image = nullptr;

    if (use_video && ffmpeg_input) {
      double offset_secs = FPSeconds(fd.offset).count();
      AVFrame *av_frame = ffmpeg_input->get_frame(video_stream_id, offset_secs);
      if (!av_frame) {
        Debug(1, "Failed to get video frame at offset %.3f for frame %d", offset_secs, fd.id);
        continue;
      }
      // Construct Image from AVFrame - converts from decoder format (e.g. YUV420P) to RGBA
      frame_image = new Image(av_frame, av_frame->width, av_frame->height);
    } else if (use_jpegs) {
      std::string jpeg_path = stringtf(staticConfig.capture_file_format.c_str(),
                                       event_path.c_str(), fd.id);
      frame_image = new Image(jpeg_path);
      if (!frame_image->Buffer()) {
        Debug(1, "Failed to load JPEG %s for frame %d", jpeg_path.c_str(), fd.id);
        delete frame_image;
        continue;
      }
    } else {
      continue;
    }

    if (!frame_image || !frame_image->Buffer()) {
      delete frame_image;
      continue;
    }

    // Run motion detection
    Event::StringSet zoneSet;
    Image analysis_image;
    unsigned int score = monitor->AnalyseFrame(*frame_image, zoneSet,
                                               save_analysis ? &analysis_image : nullptr);
    analysis_count++;

    // Build zone string for logging
    std::string zone_str;
    for (const auto &z : zoneSet) {
      if (!zone_str.empty()) zone_str += ",";
      zone_str += z;
    }

    // Track global scores for update-existing mode
    if (score) {
      total_alarm_frames++;
      total_tot_score += score;
      if (score > total_max_score)
        total_max_score = score;
    }

    // Write analysis JPEG if requested and frame had motion
    if (save_analysis && score && analysis_image.Buffer()) {
      std::string analyse_path = stringtf(staticConfig.analyse_file_format.c_str(),
                                          event_path.c_str(), fd.id);
      if (!analysis_image.WriteJpeg(analyse_path)) {
        Warning("Failed to write analysis image %s", analyse_path.c_str());
      } else {
        Debug(1, "Wrote analysis image %s", analyse_path.c_str());
      }
    }

    // State machine (used for create-events mode logging, and general state tracking)
    if (score) {
      if (state == Monitor::IDLE || state == Monitor::PREALARM) {
        alarm_frame_count++;
        if (alarm_frame_count >= monitor_alarm_frame_count) {
          Info("Frame %d: ALARM (score=%u, zones=%s)", fd.id, score, zone_str.c_str());
          state = Monitor::ALARM;

          if (create_events) {
            new_events.emplace_back();
            current_event = &new_events.back();
            current_event->start_time = fd.timestamp;

            sql = stringtf(
              "INSERT INTO `Events` "
              "(`MonitorId`, `StorageId`, `Name`, `Cause`, `StartDateTime`, `Scheme`, `SaveJPEGs`, `Width`, `Height`) "
              "VALUES (%u, %u, 'Re-analysis %" PRIu64 "', 'Re-analysis', from_unixtime(%" PRIi64 "), '%s', 0, %u, %u)",
              monitor->Id(), storage_id, event_id,
              static_cast<int64>(std::chrono::duration_cast<Seconds>(fd.timestamp.time_since_epoch()).count()),
              scheme_str.c_str(), monitor->Width(), monitor->Height());
            int new_event_id = zmDbDoInsert(sql);
            if (new_event_id > 0) {
              current_event->db_event_id = new_event_id;
              Info("Created new event %d from re-analysis of event %" PRIu64, new_event_id, event_id);

              // Create directory and copy video files to new event
              std::string new_event_path = BuildNewEventPath(
                storage_path, monitor->Id(), new_event_id, fd.timestamp, scheme);
              LinkEventFiles(event_path, new_event_path, video_file, new_event_id);
            } else {
              Error("Failed to create re-analysis event in database");
              current_event = nullptr;
              new_events.pop_back();
            }
          }
        } else if (state != Monitor::PREALARM) {
          Debug(1, "Frame %d: PREALARM (score=%u, alarm_frames=%d/%d)",
                fd.id, score, alarm_frame_count, monitor_alarm_frame_count);
          state = Monitor::PREALARM;
        }
      } else if (state == Monitor::ALERT) {
        Info("Frame %d: ALERT->ALARM (score=%u)", fd.id, score);
        state = Monitor::ALARM;
      }

      if (state == Monitor::ALARM) {
        last_alarm_frame_idx = frame_idx;
      }

      if (current_event) {
        current_event->frames++;
        current_event->alarm_frames++;
        current_event->tot_score += score;
        if (score > current_event->max_score)
          current_event->max_score = score;
      }
    } else {
      // No score
      if (state == Monitor::ALARM) {
        Debug(1, "Frame %d: ALARM->ALERT (score=0)", fd.id);
        state = Monitor::ALERT;
      } else if (state == Monitor::ALERT) {
        int frames_since_alarm = static_cast<int>(frame_idx) - last_alarm_frame_idx;
        if (frames_since_alarm > monitor_post_event_count) {
          Info("Frame %d: ALERT->IDLE (post_event_count exceeded)", fd.id);
          state = Monitor::IDLE;
          alarm_frame_count = 0;

          if (create_events && current_event) {
            FinalizeCreatedEvent(*current_event, fd.timestamp, scheme_str);
            current_event = nullptr;
          }
        } else if (current_event) {
          current_event->frames++;
        }
      } else if (state == Monitor::PREALARM) {
        state = Monitor::IDLE;
        alarm_frame_count = 0;
      }
    }

    if (frame_idx % 100 == 0 || score > 0) {
      Debug(1, "Frame %zu/%zu (id=%d): score=%u, state=%s%s%s",
            frame_idx + 1, frames.size(), fd.id, score,
            state == Monitor::IDLE ? "IDLE" :
            state == Monitor::PREALARM ? "PREALARM" :
            state == Monitor::ALARM ? "ALARM" :
            state == Monitor::ALERT ? "ALERT" : "UNKNOWN",
            zone_str.empty() ? "" : ", zones=",
            zone_str.c_str());
    }

    delete frame_image;
  }  // end frame loop

  if (create_events) {
    // Finalize any open event at end of analysis
    if (current_event) {
      FinalizeCreatedEvent(*current_event, frames.back().timestamp, scheme_str);
    }

    Info("Re-analysis complete: analysed %d frames, created %zu new events", analysis_count, new_events.size());
    for (const auto &ev : new_events) {
      Info("  Event %" PRIu64 ": frames=%d, alarm_frames=%d, max_score=%u",
           ev.db_event_id, ev.frames, ev.alarm_frames, ev.max_score);
    }
  } else {
    // Update the original event with re-analysed scores
    unsigned int avg_score = total_alarm_frames > 0 ? total_tot_score / total_alarm_frames : 0;

    sql = stringtf(
      "UPDATE `Events` SET "
      "`AlarmFrames` = %d, "
      "`TotScore` = %u, "
      "`AvgScore` = %u, "
      "`MaxScore` = %u "
      "WHERE `Id` = %" PRIu64,
      total_alarm_frames,
      total_tot_score,
      avg_score,
      total_max_score,
      event_id);
    zmDbDo(sql);

    Info("Re-analysis complete: analysed %d frames, alarm_frames=%d, tot_score=%u, avg_score=%u, max_score=%u",
         analysis_count, total_alarm_frames, total_tot_score, avg_score, total_max_score);
    Info("Updated event %" PRIu64 " with new scores", event_id);
  }

  // Cleanup
  delete ffmpeg_input;

  Image::Deinitialise();
  Debug(1, "Terminating");
  dbQueue.stop();
  zmDbClose();
  logTerm();

  return 0;
}
