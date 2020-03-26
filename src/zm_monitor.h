//
// ZoneMinder Monitor Class Interfaces, $Date$, $Revision$
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

#ifndef ZM_MONITOR_H
#define ZM_MONITOR_H

#include <vector>
#include <sstream>
#include <thread>

#include "zm.h"
#include "zm_coord.h"
#include "zm_image.h"
#include "zm_rgb.h"
#include "zm_zone.h"
#include "zm_event.h"
class Monitor;
#include "zm_group.h"
#include "zm_camera.h"
#include "zm_storage.h"
#include "zm_utils.h"

#include "zm_image_analyser.h"

#include <sys/time.h>
#include <stdint.h>

#define SIGNAL_CAUSE "Signal"
#define MOTION_CAUSE "Motion"
#define LINKED_CAUSE "Linked"

//
// This is the main class for monitors. Each monitor is associated
// with a camera and is effectively a collector for events.
//
class Monitor {
  friend class MonitorStream;

public:
  typedef enum {
    QUERY=0,
    CAPTURE,
    ANALYSIS
  } Purpose;

  typedef enum {
    NONE=1,
    MONITOR,
    MODECT,
    RECORD,
    MOCORD,
    NODECT
  } Function;

  typedef enum {
    LOCAL,
    REMOTE,
    FILE,
    FFMPEG,
    LIBVLC,
    CURL,
    VNC,
  } CameraType;

  typedef enum { 
    ROTATE_0=1,
    ROTATE_90,
    ROTATE_180,
    ROTATE_270,
    FLIP_HORI,
    FLIP_VERT
  } Orientation;

  typedef enum {
    IDLE,
    PREALARM,
    ALARM,
    ALERT,
    TAPE
  } State;

  typedef enum {
    DISABLED,
    X264ENCODE,
    H264PASSTHROUGH,
  } VideoWriter;

protected:
  typedef std::set<Zone *> ZoneSet;

  typedef enum { GET_SETTINGS=0x1, SET_SETTINGS=0x2, RELOAD=0x4, SUSPEND=0x10, RESUME=0x20 } Action;

  typedef enum { CLOSE_TIME, CLOSE_IDLE, CLOSE_ALARM } EventCloseMode;

  /* sizeof(SharedData) expected to be 340 bytes on 32bit and 64bit */
  typedef struct {
    uint32_t size;              /* +0    */
    uint32_t last_write_index;  /* +4    */ 
    uint32_t last_read_index;   /* +8    */
    uint32_t state;             /* +12   */
    uint64_t last_event;        /* +16   */
    uint32_t action;            /* +24   */
    int32_t brightness;         /* +28   */
    int32_t hue;                /* +32   */
    int32_t colour;             /* +36   */
    int32_t contrast;           /* +40   */
    int32_t alarm_x;            /* +44   */
    int32_t alarm_y;            /* +48   */
    uint8_t valid;              /* +52   */
    uint8_t active;             /* +53   */
    uint8_t signal;             /* +54   */
    uint8_t format;             /* +55   */
    uint32_t imagesize;         /* +56   */
    uint32_t epadding1;         /* +60   */
    /* 
     ** This keeps 32bit time_t and 64bit time_t identical and compatible as long as time is before 2038.
     ** Shared memory layout should be identical for both 32bit and 64bit and is multiples of 16.
     ** Because startup_time is 64bit it may be aligned to a 64bit boundary.  So it's offset SHOULD be a multiple 
     ** of 8. Add or delete epadding's to achieve this.
     */  
    union {                     /* +64   */
      time_t startup_time;			/* When the zmc process started.  zmwatch uses this to see how long the process has been running without getting any images */
      uint64_t extrapad1;
    };
    union {                     /* +72  */
      time_t last_write_time;
      uint64_t extrapad2;
    };
    union {            /* +80   */
      time_t last_read_time;
      uint64_t extrapad3;
    };
    uint8_t control_state[256];  /* +88   */

    char alarm_cause[256];
    
  } SharedData;

  typedef enum { TRIGGER_CANCEL, TRIGGER_ON, TRIGGER_OFF } TriggerState;

  /* sizeof(TriggerData) expected to be 560 on 32bit & and 64bit */
  typedef struct {
    uint32_t size;
    uint32_t trigger_state;
    uint32_t trigger_score;
    uint32_t padding;
    char trigger_cause[32];
    char trigger_text[256];
    char trigger_showtext[256];
  } TriggerData;

  /* sizeof(Snapshot) expected to be 16 bytes on 32bit and 32 bytes on 64bit */
  struct Snapshot {
    struct timeval  *timestamp;
    Image  *image;
    void* padding;
  };

  //TODO: Technically we can't exclude this struct when people don't have avformat as the Memory.pm module doesn't know about avformat
#if 1
  //sizeOf(VideoStoreData) expected to be 4104 bytes on 32bit and 64bit
  typedef struct {
    uint32_t size;
    uint64_t current_event;
    char event_file[4096];
    timeval recording;      // used as both bool and a pointer to the timestamp when recording should begin
    //uint32_t frameNumber;
  } VideoStoreData;

#endif // HAVE_LIBAVFORMAT

  class MonitorLink {
  protected:
    unsigned int  id;
    char      name[64];

    bool      connected;
    time_t    last_connect_time;

#if ZM_MEM_MAPPED
    int       map_fd;
    char      mem_file[PATH_MAX];
#else // ZM_MEM_MAPPED
    int       shm_id;
#endif // ZM_MEM_MAPPED
    off_t     mem_size;
    unsigned char  *mem_ptr;

    volatile SharedData  *shared_data;
    volatile TriggerData  *trigger_data;
    volatile VideoStoreData *video_store_data;

    int        last_state;
    uint64_t   last_event;

    public:
      MonitorLink( int p_id, const char *p_name );
      ~MonitorLink();

      inline int Id() const {
        return id;
      }
      inline const char *Name() const {
        return( name );
      }

      inline bool isConnected() const {   
        return( connected );
      }
      inline time_t getLastConnectTime() const {
        return( last_connect_time );
      }

      bool connect();
      bool disconnect();

      bool isAlarmed();
      bool inAlarm();
      bool hasAlarmed();
  };

  protected:
  // These are read from the DB and thereafter remain unchanged
  unsigned int    id;
  char            name[64];
  unsigned int    server_id;          // Id of the Server object
  unsigned int    storage_id;         // Id of the Storage Object, which currently will just provide a path, but in future may do more.
  CameraType      type;
  Function        function;           // What the monitor is doing
  bool            enabled;            // Whether the monitor is enabled or asleep
  unsigned int    width;              // Normally the same as the camera, but not if partly rotated
  unsigned int    height;             // Normally the same as the camera, but not if partly rotated
  bool            v4l_multi_buffer;
  unsigned int    v4l_captures_per_frame;
  Orientation     orientation;        // Whether the image has to be rotated at all
  unsigned int    deinterlacing;
  bool            videoRecording;
  std::string     decoder_hwaccel_name;
  std::string     decoder_hwaccel_device;

  int savejpegs;
  VideoWriter videowriter;
  std::string encoderparams;
  std::vector<EncoderParameter_t> encoderparamsvec;
  bool          record_audio;      // Whether to store the audio that we receive

  int           brightness;        // The statically saved brightness of the camera
  int           contrast;        // The statically saved contrast of the camera
  int           hue;          // The statically saved hue of the camera
  int           colour;          // The statically saved colour of the camera
  char          event_prefix[64];    // The prefix applied to event names as they are created
  char          label_format[64];    // The format of the timestamp on the images
  Coord         label_coord;      // The coordinates of the timestamp on the images
  int           label_size;         // Size of the timestamp on the images
  int           image_buffer_count;   // Size of circular image buffer, at least twice the size of the pre_event_count
  int           pre_event_buffer_count;   // Size of dedicated circular pre event buffer used when analysis is not performed at capturing framerate,
  // value is pre_event_count + alarm_frame_count - 1
  int           warmup_count;      // How many images to process before looking for events
  int           pre_event_count;    // How many images to hold and prepend to an alarm event
  int           post_event_count;    // How many unalarmed images must occur before the alarm state is reset
  struct timeval video_buffer_duration; // How long a video segment to keep in buffer (set only if analysis fps != 0 )
  int           stream_replay_buffer;   // How many frames to store to support DVR functions, IGNORED from this object, passed directly into zms now
  int           section_length;      // How long events should last in continuous modes
  int           min_section_length;   // Minimum event length when using event_close_mode == ALARM
  bool          adaptive_skip;        // Whether to use the newer adaptive algorithm for this monitor
  int           frame_skip;        // How many frames to skip in continuous modes
  int           motion_frame_skip;      // How many frames to skip in motion detection
  double        capture_max_fps;       // Target Capture FPS
  double        analysis_fps;  // Target framerate for video analysis
  unsigned int  analysis_update_delay;  //  How long we wait before updating analysis parameters
  int           capture_delay;      // How long we wait between capture frames
  int           alarm_capture_delay;  // How long we wait between capture frames when in alarm state
  int           alarm_frame_count;    // How many alarm frames are required before an event is triggered
  int           fps_report_interval;  // How many images should be captured/processed between reporting the current FPS
  int           ref_blend_perc;      // Percentage of new image going into reference image.
  int           alarm_ref_blend_perc;      // Percentage of new image going into reference image during alarm.
  bool          track_motion;      // Whether this monitor tries to track detected motion 
  int           signal_check_points;  // Number of points in the image to check for signal
  Rgb           signal_check_colour;  // The colour that the camera will emit when no video signal detected
  bool          embed_exif; // Whether to embed Exif data into each image frame or not

  bool last_signal;

  double       fps;
  unsigned int last_camera_bytes;
  
  Image        delta_image;
  Image        ref_image;
  Image        alarm_image;  // Used in creating analysis images, will be initialized in Analysis
  Image        write_image;    // Used when creating snapshot images
  std::string diag_path_r;
  std::string diag_path_d;

  Purpose      purpose;        // What this monitor has been created to do
  int          event_count;
  int          image_count;
  int          ready_count;
  int          first_alarm_count;
  int          last_alarm_count;
  int          buffer_count;
  int          prealarm_count;
  State        state;
  time_t       start_time;
  time_t       last_fps_time;
  time_t       auto_resume_time;
  unsigned int      last_motion_score;

  EventCloseMode  event_close_mode;

#if ZM_MEM_MAPPED
  int             map_fd;
  char            mem_file[PATH_MAX];
#else // ZM_MEM_MAPPED
  int             shm_id;
#endif // ZM_MEM_MAPPED
  off_t           mem_size;
  unsigned char   *mem_ptr;
  SharedData      *shared_data;
  TriggerData     *trigger_data;
  VideoStoreData  *video_store_data;

  Snapshot    *image_buffer;
  Snapshot    next_buffer; /* Used by four field deinterlacing */
  Snapshot    *pre_event_buffer;

  Camera      *camera;
  Event       *event;
  Storage     *storage;

  int      n_zones;
  Zone      **zones;

  struct timeval    **timestamps;
  Image      **images;

  const unsigned char  *privacy_bitmask;
  std::thread   *event_delete_thread; // Used to close events, but continue processing.

  int      n_linked_monitors;
  MonitorLink    **linked_monitors;

  std::vector<Group *> groups;

public:
  explicit Monitor( int p_id );

// OurCheckAlarms seems to be unused. Check it on zm_monitor.cpp for more info.
//bool OurCheckAlarms( Zone *zone, const Image *pImage );
  Monitor( 
    int p_id,
    const char *p_name,
    unsigned int p_server_id,
    unsigned int p_storage_id,
    int p_function,
    bool p_enabled,
    const char *p_linked_monitors,
    Camera *p_camera,
    int p_orientation,
    unsigned int p_deinterlacing,
    const std::string &p_decoder_hwaccel_name,
    const std::string &p_decoder_hwaccel_device,
    int p_savejpegs,
    VideoWriter p_videowriter,
    std::string p_encoderparams,
    bool  p_record_audio,
    const char *p_event_prefix,
    const char *p_label_format,
    const Coord &p_label_coord,
    int label_size,
    int p_image_buffer_count,
    int p_warmup_count,
    int p_pre_event_count,
    int p_post_event_count,
    int p_stream_replay_buffer,
    int p_alarm_frame_count,
    int p_section_length,
    int p_min_section_length,
    int p_frame_skip,
    int p_motion_frame_skip,
    double p_capture_max_fps,
    double p_analysis_fps,
    unsigned int p_analysis_update_delay,
    int p_capture_delay,
    int p_alarm_capture_delay,
    int p_fps_report_interval,
    int p_ref_blend_perc,
    int p_alarm_ref_blend_perc,
    bool p_track_motion,
    int p_signal_check_points,
    Rgb p_signal_check_colour,
    bool p_embed_exif,
    Purpose p_purpose,
    int p_n_zones=0,
    Zone *p_zones[]=0
  );
  ~Monitor();

  void AddZones( int p_n_zones, Zone *p_zones[] );
  void AddPrivacyBitmask( Zone *p_zones[] );

  bool connect();
  inline int ShmValid() const {
    return( shared_data->valid );
  }

  inline int Id() const {
    return id;
  }
  inline const char *Name() const {
    return name;
  }
  inline Storage *getStorage() {
    if ( ! storage ) {
      storage = new Storage( storage_id );
    }
    return storage;
  }
  inline Function GetFunction() const {
    return( function );
  }
  inline bool Enabled() {
    if ( function <= MONITOR )
      return false;
    return enabled;
  }
  inline const char *EventPrefix() const {
    return event_prefix;
  }
  inline bool Ready() {
    if ( function <= MONITOR )
      return false;
    return( image_count > ready_count );
  }
  inline bool Active() {
    if ( function <= MONITOR )
      return false;
    return( enabled && shared_data->active );
  }
  inline bool Exif() {
    return embed_exif;
  }
  Orientation getOrientation() const;

  unsigned int Width() const { return width; }
  unsigned int Height() const { return height; }
  unsigned int Colours() const;
  unsigned int SubpixelOrder() const;
    
  int GetOptSaveJPEGs() const { return savejpegs; }
  VideoWriter GetOptVideoWriter() const { return videowriter; }
  const std::vector<EncoderParameter_t>* GetOptEncoderParams() const { return &encoderparamsvec; }
  uint64_t GetVideoWriterEventId() const { return video_store_data->current_event; }
  void SetVideoWriterEventId( unsigned long long p_event_id ) { video_store_data->current_event = p_event_id; }
  struct timeval GetVideoWriterStartTime() const { return video_store_data->recording; }
  void SetVideoWriterStartTime(struct timeval &t) { video_store_data->recording = t; }
 
  unsigned int GetPreEventCount() const { return pre_event_count; };
  struct timeval GetVideoBufferDuration() const { return video_buffer_duration; };
  int GetImageBufferCount() const { return image_buffer_count; };
  State GetState() const;
  int GetImage( int index=-1, int scale=100 );
  Snapshot *getSnapshot() const;
  struct timeval GetTimestamp( int index=-1 ) const;
  void UpdateAdaptiveSkip();
  useconds_t GetAnalysisRate();
  unsigned int GetAnalysisUpdateDelay() const { return analysis_update_delay; }
  unsigned int GetCaptureMaxFPS() const { return capture_max_fps; }
  int GetCaptureDelay() const { return capture_delay; }
  int GetAlarmCaptureDelay() const { return alarm_capture_delay; }
  unsigned int GetLastReadIndex() const;
  unsigned int GetLastWriteIndex() const;
  uint64_t GetLastEventId() const;
  double GetFPS() const;
  void ForceAlarmOn( int force_score, const char *force_case, const char *force_text="" );
  void ForceAlarmOff();
  void CancelForced();
  TriggerState GetTriggerState() const { return (TriggerState)(trigger_data?trigger_data->trigger_state:TRIGGER_CANCEL); }
	inline time_t getStartupTime() const { return shared_data->startup_time; }
	inline void setStartupTime( time_t p_time ) { shared_data->startup_time = p_time; }

  void actionReload();
  void actionEnable();
  void actionDisable();
  void actionSuspend();
  void actionResume();

  int actionBrightness( int p_brightness=-1 );
  int actionHue( int p_hue=-1 );
  int actionColour( int p_colour=-1 );
  int actionContrast( int p_contrast=-1 );

  int PrimeCapture() const;
  int PreCapture() const;
  int Capture();
  int PostCapture() const;
  int Close();

  unsigned int DetectMotion( const Image &comp_image, Event::StringSet &zoneSet );
   // DetectBlack seems to be unused. Check it on zm_monitor.cpp for more info.
   //unsigned int DetectBlack( const Image &comp_image, Event::StringSet &zoneSet );
  bool CheckSignal( const Image *image );
  bool Analyse();
  void DumpImage( Image *dump_image ) const;
  void TimestampImage( Image *ts_image, const struct timeval *ts_time ) const;
  bool closeEvent();

  void Reload();
  void ReloadZones();
  void ReloadLinkedMonitors( const char * );

  bool DumpSettings( char *output, bool verbose );
  void DumpZoneImage( const char *zone_string=0 );
  std::vector<Group *>  Groups();
  StringVector GroupNames();

  static int LoadMonitors(std::string sql, Monitor **&monitors, Purpose purpose);  // Returns # of Monitors loaded, 0 on failure.
#if ZM_HAS_V4L
  static int LoadLocalMonitors(const char *device, Monitor **&monitors, Purpose purpose);
#endif // ZM_HAS_V4L
  static int LoadRemoteMonitors(const char *protocol, const char *host, const char*port, const char*path, Monitor **&monitors, Purpose purpose);
  static int LoadFileMonitors(const char *file, Monitor **&monitors, Purpose purpose);
#if HAVE_LIBAVFORMAT
  static int LoadFfmpegMonitors(const char *file, Monitor **&monitors, Purpose purpose);
#endif // HAVE_LIBAVFORMAT
  static Monitor *Load(unsigned int id, bool load_zones, Purpose purpose);
  static Monitor *Load(MYSQL_ROW dbrow, bool load_zones, Purpose purpose);
  //void writeStreamImage( Image *image, struct timeval *timestamp, int scale, int mag, int x, int y );
  //void StreamImages( int scale=100, int maxfps=10, time_t ttl=0, int msq_id=0 );
  //void StreamImagesRaw( int scale=100, int maxfps=10, time_t ttl=0 );
  //void StreamImagesZip( int scale=100, int maxfps=10, time_t ttl=0 );
#if HAVE_LIBAVCODEC
  //void StreamMpeg( const char *format, int scale=100, int maxfps=10, int bitrate=100000 );
#endif // HAVE_LIBAVCODEC
};

#define MOD_ADD( var, delta, limit ) (((var)+(limit)+(delta))%(limit))

#endif // ZM_MONITOR_H
