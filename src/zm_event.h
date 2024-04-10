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

#ifndef ZM_EVENT_H
#define ZM_EVENT_H

#include "zm_config.h"
#include "zm_define.h"
#include "zm_packet.h"
#include "zm_storage.h"
#include "zm_time.h"
#include "zm_utils.h"
#include "zm_zone.h"

#include <atomic>
#include <condition_variable>
#include <map>
#include <memory>
#include <mutex>
#include <queue>
#include <set>
#include <thread>


class EventStream;
class Frame;
class Image;
class Monitor;
class VideoStore;
class ZMPacket;
class Zone;

// Maximum number of prealarm frames that can be stored
#define MAX_PRE_ALARM_FRAMES  16

typedef uint64_t event_id_t;

//
// Class describing events, i.e. captured periods of activity.
//
class Event {
  friend class EventStream;

 public:
  typedef std::set<std::string> StringSet;
  typedef std::map<std::string,StringSet> StringSetMap;

 protected:
  static const char * frame_type_names[3];

  struct PreAlarmData {
    Image *image;
    struct timeval timestamp;
    unsigned int score;
    Image *alarm_frame;
  };
  std::queue<Frame*> frame_data;

  static int pre_alarm_count;
  static PreAlarmData pre_alarm_data[MAX_PRE_ALARM_FRAMES];

  uint64_t  id;
  Monitor      *monitor;
  SystemTimePoint start_time;
  SystemTimePoint end_time;
  std::string     cause;
  StringSetMap    noteSetMap;
  int        frames;
  int        alarm_frames;
  bool alarm_frame_written;
  int  tot_score;
  int  max_score;
  std::string path;
  std::string snapshot_file;
  bool snapshot_file_written;
  std::string alarm_file;
  VideoStore *videoStore;

  AVCodecContext      *mJpegCodecContext;
  SwsContext          *mJpegSwsContext;

  std::string container;
  std::string codec;
  std::string video_file;
  std::string video_path;
  std::string video_incomplete_file;
  std::string video_incomplete_path;

  int        last_db_frame;
  bool have_video_keyframe; // a flag to tell us if we have had a video keyframe when writing an mp4.  The first frame SHOULD be a video keyframe.
  Storage::Schemes  scheme;
  int save_jpegs;

  void createNotes(std::string &notes);

  std::queue<std::shared_ptr<ZMPacket>> packet_queue;
  std::mutex packet_queue_mutex;
  std::condition_variable packet_queue_condition;

  void Run();

  std::atomic<bool> terminate_;
  std::thread thread_;

 public:
  static bool OpenFrameSocket(int);
  static bool ValidateFrameSocket(int);

  Event(Monitor *p_monitor,
        SystemTimePoint p_start_time,
        const std::string &p_cause,
        const StringSetMap &p_noteSetMap);
  ~Event();

  uint64_t Id() const { return id; }
  const std::string &Cause() const { return cause; }
  void addNote(const char *cause, const std::string &note);
  int Frames() const { return frames; }
  int AlarmFrames() const { return alarm_frames; }

  SystemTimePoint StartTime() const { return start_time; }
  SystemTimePoint EndTime() const { return end_time; }
  TimePoint::duration Duration() const { return end_time - start_time; };

  void AddPacket(const std::shared_ptr<ZMPacket> &p);
  void AddPacket_(const std::shared_ptr<ZMPacket> &p);
  bool WritePacket(const std::shared_ptr<ZMPacket> &p);
  bool SendFrameImage(const Image *image, bool alarm_frame=false);
  bool WriteFrameImage(Image *image, SystemTimePoint timestamp, const char *event_file, bool alarm_frame = false) const;

  void updateNotes(const StringSetMap &stringSetMap);

  void AddFrame(const std::shared_ptr<ZMPacket>&packet);

  void Stop() {
    {
      std::unique_lock<std::mutex> lck(packet_queue_mutex);
      terminate_ = true;
    }
    packet_queue_condition.notify_all();
  }
  bool Stopped() const { return terminate_; }

 private:
  void WriteDbFrames();
  bool SetPath(Storage *storage);

 public:
  static std::string getSubPath(tm time) {
    std::string subpath = stringtf("%02d/%02d/%02d/%02d/%02d/%02d",
                                   time.tm_year - 100, time.tm_mon + 1, time.tm_mday,
                                   time.tm_hour, time.tm_min, time.tm_sec);
    return subpath;
  }
  static std::string getSubPath(time_t *time) {
    tm time_tm = {};
    localtime_r(time, &time_tm);
    return Event::getSubPath(time_tm);
  }

  const char* getEventFile() const {
    return video_file.c_str();
  }

  static int PreAlarmCount() {
    return pre_alarm_count;
  }
  static void EmptyPreAlarmFrames() {
    pre_alarm_count = 0;
  }
  static void AddPreAlarmFrame(
    Image *image,
    SystemTimePoint timestamp,
    int score=0,
    Image *alarm_frame=nullptr
  ) {
    pre_alarm_count++;
  }
  void SavePreAlarmFrames() {
    EmptyPreAlarmFrames();
  }
  int MonitorId() const;
};
#endif // ZM_EVENT_H
