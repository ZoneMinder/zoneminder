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

#include "zm_define.h"
#include "zm_camera.h"
#include "zm_analysis_thread.h"
#include "zm_poll_thread.h"
#include "zm_decoder_thread.h"
#include "zm_event.h"
#include "zm_fifo.h"
#include "zm_image.h"
#include "zm_packet.h"
#include "zm_packetqueue.h"
#include "zm_utils.h"
#include <memory>
#include <sys/time.h>
#include <vector>
#include <curl/curl.h>

#ifdef WITH_GSOAP
#include "soapPullPointSubscriptionBindingProxy.h"
#include "plugin/wsseapi.h"
#include <openssl/err.h>
#endif

class Group;

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
    LOCAL=1,
    REMOTE,
    FILE,
    FFMPEG,
    LIBVLC,
    LIBCURL,
    NVSOCKET,
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
    DEINTERLACE_DISABLED = 0x00000000,
    DEINTERLACE_FOUR_FIELD_SOFT = 0x00001E04,
    DEINTERLACE_FOUR_FIELD_MEDIUM = 0x00001404,
    DEINTERLACE_FOUR_FIELD_HARD = 0x00000A04,
    DEINTERLACE_DISCARD = 0x00000001,
    DEINTERLACE_LINEAR = 0x00000002,
    DEINTERLACE_BLEND = 0x00000003,
    DEINTERLACE_BLEND_25 = 0x00000205,
    DEINTERLACE_V4L2_TOP = 0x02000000,
    DEINTERLACE_V4L2_BOTTOM = 0x03000000,
    DEINTERLACE_V4L2_ALTERNATE = 0x07000000,
    DEINTERLACE_V4L2_PROGRESSIVE = 0x01000000,
    DEINTERLACE_V4L2_INTERLACED = 0x04000000
  } Deinterlace;

  typedef enum {
    UNKNOWN,
    IDLE,
    PREALARM,
    ALARM,
    ALERT,
    TAPE
  } State;

  typedef enum {
    DISABLED,
    ENCODE,
    PASSTHROUGH,
  } VideoWriter;

protected:
  typedef std::set<Zone *> ZoneSet;

  typedef enum { GET_SETTINGS=0x1, SET_SETTINGS=0x2, RELOAD=0x4, SUSPEND=0x10, RESUME=0x20 } Action;

  typedef enum { CLOSE_TIME, CLOSE_IDLE, CLOSE_ALARM } EventCloseMode;

  /* sizeof(SharedData) expected to be 472 bytes on 32bit and 64bit */
  typedef struct {
    uint32_t size;              /* +0    */
    int32_t last_write_index;  /* +4    */
    int32_t last_read_index;   /* +8    */
    uint32_t state;             /* +12   */
    double      capture_fps;       // Current capturing fps
    double      analysis_fps;      // Current analysis fps
    uint64_t last_event_id;     /* +16   */
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
    uint32_t last_frame_score;  /* +60   */
    uint32_t  audio_frequency;  /* +64   */
    uint32_t  audio_channels;   /* +68   */
    /*
     ** This keeps 32bit time_t and 64bit time_t identical and compatible as long as time is before 2038.
     ** Shared memory layout should be identical for both 32bit and 64bit and is multiples of 16.
     ** Because startup_time is 64bit it may be aligned to a 64bit boundary.  So it's offset SHOULD be a multiple
     ** of 8. Add or delete epadding's to achieve this.
     */
    union {                     /* +72   */
      time_t startup_time;			/* When the zmc process started.  zmwatch uses this to see how long the process has been running without getting any images */
      uint64_t extrapad1;
    };
    union {                     /* +80   */
      time_t zmc_heartbeat_time;			/* Constantly updated by zmc.  Used to determine if the process is alive or hung or dead */
      uint64_t extrapad2;
    };
    union {                     /* +88  */
      time_t last_write_time;
      uint64_t extrapad3;
    };
    union {                     /* +96  */
      time_t last_read_time;
      uint64_t extrapad4;
    };
    uint8_t control_state[256];  /* +104   */

    char alarm_cause[256];
    char video_fifo_path[64];
    char audio_fifo_path[64];

  } SharedData;

  enum TriggerState : uint32 {
    TRIGGER_CANCEL,
    TRIGGER_ON,
    TRIGGER_OFF
  };

  /* sizeof(TriggerData) expected to be 560 on 32bit & and 64bit */
  typedef struct {
    uint32_t size;
    TriggerState trigger_state;
    uint32_t trigger_score;
    uint32_t padding;
    char trigger_cause[32];
    char trigger_text[256];
    char trigger_showtext[256];
  } TriggerData;

  //TODO: Technically we can't exclude this struct when people don't have avformat as the Memory.pm module doesn't know about avformat
  //sizeOf(VideoStoreData) expected to be 4104 bytes on 32bit and 64bit
  typedef struct {
    uint32_t size;
    uint64_t current_event;
    char event_file[4096];
    timeval recording;      // used as both bool and a pointer to the timestamp when recording should begin
  } VideoStoreData;

  class MonitorLink {
  protected:
    unsigned int  id;
    char      name[64];

    bool      connected;
    time_t    last_connect_time;

#if ZM_MEM_MAPPED
    int       map_fd;
    std::string      mem_file;
#else // ZM_MEM_MAPPED
    int       shm_id;
#endif // ZM_MEM_MAPPED
    off_t     mem_size;
    unsigned char  *mem_ptr;

    volatile SharedData  *shared_data;
    volatile TriggerData  *trigger_data;
    volatile VideoStoreData *video_store_data;

    int        last_state;
    uint64_t   last_event_id;

    public:
      MonitorLink(unsigned int p_id, const char *p_name);
      ~MonitorLink();

      inline unsigned int Id() const { return id; }
      inline const char *Name() const { return name; }

      inline bool isConnected() const { return connected && shared_data->valid; }
      inline time_t getLastConnectTime() const { return last_connect_time; }

      inline uint32_t lastFrameScore() {
        return shared_data->last_frame_score;
      }

      bool connect();
      bool disconnect();

      bool isAlarmed();
      bool inAlarm();
      bool hasAlarmed();
  };

  class AmcrestAPI {
  protected:
    Monitor *parent;
    std::string amcrest_response;
    CURLM *curl_multi = nullptr;
    CURL *Amcrest_handle = nullptr;
    static size_t WriteCallback(void *contents, size_t size, size_t nmemb, void *userp);

  public:
    AmcrestAPI( Monitor *parent_);
    ~AmcrestAPI();
    int API_Connect();
    void WaitForMessage();
    bool Amcrest_Alarmed;
    int start_Amcrest();
  };

  class JanusManager {
  protected:
    Monitor *parent;
    CURL *curl = nullptr;
    //helper class for CURL
    static size_t WriteCallback(void *contents, size_t size, size_t nmemb, void *userp);
    bool Janus_Healthy;
    std::string janus_session;
    std::string janus_handle;
    std::string janus_endpoint;
    std::string stream_key;
    std::string rtsp_username;
    std::string rtsp_password;
    std::string rtsp_path;

  public:
    JanusManager(Monitor *parent_);
    ~JanusManager();
    int add_to_janus();
    int check_janus();
    int remove_from_janus();
    int get_janus_session();
    int get_janus_handle();
    int get_janus_plugin();
    std::string get_stream_key();
  };


  // These are read from the DB and thereafter remain unchanged
  unsigned int    id;
  std::string     name;
  unsigned int    server_id;          // Id of the Server object
  unsigned int    storage_id;         // Id of the Storage Object, which currently will just provide a path, but in future may do more.
  CameraType      type;
  Function        function;           // What the monitor is doing
  bool            enabled;            // Whether the monitor is enabled or asleep
  bool            decoding_enabled;   // Whether the monitor will decode h264/h265 packets
  bool            janus_enabled;      // Whether we set the h264/h265 stream up on janus
  bool            janus_audio_enabled;      // Whether we tell Janus to try to include audio.

  std::string protocol;
  std::string method;
  std::string options;
  std::string host;
  std::string port;
  std::string user;
  std::string pass;
  std::string path;
  std::string second_path;

  std::string onvif_url;
  std::string onvif_username;
  std::string onvif_password;
  std::string onvif_options;
  bool        onvif_event_listener;
  bool        use_Amcrest_API;

  std::string     device;
  int             palette;
  int             channel;
  int             format;

  unsigned int    camera_width;
  unsigned int    camera_height;
  unsigned int    width;              // Normally the same as the camera, but not if partly rotated
  unsigned int    height;             // Normally the same as the camera, but not if partly rotated
  bool            v4l_multi_buffer;
  unsigned int    v4l_captures_per_frame;
  Orientation     orientation;        // Whether the image has to be rotated at all
  unsigned int    deinterlacing;
  unsigned int    deinterlacing_value;
  std::string     decoder_hwaccel_name;
  std::string     decoder_hwaccel_device;
  bool            videoRecording;
  bool            rtsp_describe;

  int             savejpegs;
  int             colours;
  VideoWriter     videowriter;
  std::string     encoderparams;
  int             output_codec;
  std::string     encoder;
  std::string     output_container;
  _AVPIXELFORMAT  imagePixFormat;
  bool            record_audio;      // Whether to store the audio that we receive


  int        brightness;        // The statically saved brightness of the camera
  int        contrast;        // The statically saved contrast of the camera
  int        hue;          // The statically saved hue of the camera
  int        colour;          // The statically saved colour of the camera

  std::string     event_prefix;    // The prefix applied to event names as they are created
  std::string     label_format;    // The format of the timestamp on the images
  Vector2      label_coord;      // The coordinates of the timestamp on the images
  int        label_size;         // Size of the timestamp on the images
  int32_t    image_buffer_count;        // Size of circular image buffer, kept in /dev/shm
  int32_t    max_image_buffer_count;    // Max # of video packets to keep in packet queue
  int        warmup_count;              // How many images to process before looking for events
  int        pre_event_count;    // How many images to hold and prepend to an alarm event
  int        post_event_count;    // How many unalarmed images must occur before the alarm state is reset
  int        stream_replay_buffer;   // How many frames to store to support DVR functions, IGNORED from this object, passed directly into zms now
  Seconds section_length;      // How long events should last in continuous modes
  Seconds min_section_length;   // Minimum event length when using event_close_mode == ALARM
  bool       adaptive_skip;        // Whether to use the newer adaptive algorithm for this monitor
  int        frame_skip;        // How many frames to skip in continuous modes
  int        motion_frame_skip;      // How many frames to skip in motion detection
  double     analysis_fps_limit;     // Target framerate for video analysis
  Microseconds analysis_update_delay;  //  How long we wait before updating analysis parameters
  Microseconds capture_delay;      // How long we wait between capture frames
  Microseconds alarm_capture_delay;  // How long we wait between capture frames when in alarm state
  int        alarm_frame_count;    // How many alarm frames are required before an event is triggered
  int        alert_to_alarm_frame_count;    // How many alarm frames (consecutive score frames) are required to return alarm from alert
  // value for now is the same number configured in alarm_frame_count, maybe getting his own parameter some day
  int        fps_report_interval;  // How many images should be captured/processed between reporting the current FPS
  int        ref_blend_perc;      // Percentage of new image going into reference image.
  int        alarm_ref_blend_perc;      // Percentage of new image going into reference image during alarm.
  bool       track_motion;      // Whether this monitor tries to track detected motion 
  int         signal_check_points;  // Number of points in the image to check for signal
  Rgb         signal_check_colour;  // The colour that the camera will emit when no video signal detected
  bool        embed_exif; // Whether to embed Exif data into each image frame or not
  bool        rtsp_server; // Whether to include this monitor as an rtsp server stream
  std::string rtsp_streamname;      // path in the rtsp url for this monitor
  int         importance;           // Importance of this monitor, affects Connection logging errors.

  int capture_max_fps;

  Purpose      purpose;        // What this monitor has been created to do
  unsigned int  last_camera_bytes;

  int        event_count;
  int        image_count;
  int         last_capture_image_count; // last value of image_count when calculating capture fps
  int        analysis_image_count;    // How many frames have been processed by analysis thread.
  int        motion_frame_count;      // How many frames have had motion detection performed on them.
  int         last_motion_frame_count; // last value of motion_frame_count when calculating fps
  int        ready_count;
  int        first_alarm_count;
  int        last_alarm_count;
  bool       last_signal;
  int        buffer_count;
  State      state;
  SystemTimePoint start_time;
  SystemTimePoint last_fps_time;
  SystemTimePoint last_analysis_fps_time;
  SystemTimePoint auto_resume_time;
  unsigned int      last_motion_score;

  EventCloseMode  event_close_mode;

#if ZM_MEM_MAPPED
  int             map_fd;
  std::string     mem_file;
#else // ZM_MEM_MAPPED
  int             shm_id;
#endif // ZM_MEM_MAPPED
  off_t           mem_size;
  unsigned char   *mem_ptr;
  SharedData      *shared_data;
  TriggerData     *trigger_data;
  VideoStoreData  *video_store_data;

  struct timeval *shared_timestamps;
  unsigned char *shared_images;
  std::vector<Image *> image_buffer;

  int video_stream_id; // will be filled in PrimeCapture
  int audio_stream_id; // will be filled in PrimeCapture
  Fifo *video_fifo;
  Fifo *audio_fifo;

  std::unique_ptr<Camera> camera;
  Event       *event;
  std::mutex   event_mutex;
  Storage     *storage;

  VideoStore          *videoStore;
  PacketQueue      packetqueue;
  std::unique_ptr<PollThread> Poller;
  packetqueue_iterator  *analysis_it;
  std::unique_ptr<AnalysisThread> analysis_thread;
  packetqueue_iterator  *decoder_it;
  std::unique_ptr<DecoderThread> decoder;
  AVFrame *dest_frame;                    // Used by decoding thread doing colorspace conversions
  SwsContext   *convert_context;
  std::thread  close_event_thread;

  std::vector<Zone> zones;

  const unsigned char  *privacy_bitmask;

  int      n_linked_monitors;
  MonitorLink    **linked_monitors;
  std::string   event_start_command;
  std::string   event_end_command;

  std::vector<Group *> groups;

  Image        delta_image;
  Image        ref_image;
  Image        alarm_image;  // Used in creating analysis images, will be initialized in Analysis
  Image        write_image;    // Used when creating snapshot images
  std::string diag_path_ref;
  std::string diag_path_delta;

  //ONVIF
  bool Poll_Trigger_State;
  bool Event_Poller_Healthy;
  bool Event_Poller_Closes_Event;

  JanusManager *Janus_Manager;
  AmcrestAPI *Amcrest_Manager;

#ifdef WITH_GSOAP
  struct soap *soap = nullptr;
  _tev__CreatePullPointSubscription request;
  _tev__CreatePullPointSubscriptionResponse response;
  _tev__PullMessages tev__PullMessages;
  _tev__PullMessagesResponse tev__PullMessagesResponse;
  PullPointSubscriptionBindingProxy proxyEvent;
  void set_credentials(struct soap *soap);
#endif


  // Used in check signal
  uint8_t red_val;
  uint8_t green_val;
  uint8_t blue_val;
  uint8_t grayscale_val; /* 8bit grayscale color */
  Rgb colour_val; /* RGB32 color */
  int usedsubpixorder;

public:
  explicit Monitor();

  ~Monitor();

  void AddPrivacyBitmask();

  void LoadCamera();
  bool connect();
  bool disconnect();
  inline bool isConnected() const { return mem_ptr != nullptr; }

  inline int ShmValid() const {
    if ( shared_data && shared_data->valid ) {
      timeval now = {};
      gettimeofday(&now, nullptr);
      Debug(3, "Shared data is valid, checking heartbeat %" PRIi64 " - %" PRIi64 " = %" PRIi64"  < %f",
            static_cast<int64>(now.tv_sec),
            static_cast<int64>(shared_data->zmc_heartbeat_time),
            static_cast<int64>(now.tv_sec - shared_data->zmc_heartbeat_time),
            config.watch_max_delay);

      if ((now.tv_sec - shared_data->zmc_heartbeat_time) < config.watch_max_delay)
        return true;
    }
    return false;
  }

  inline unsigned int Id() const { return id; }
  inline const char *Name() const { return name.c_str(); }
  inline unsigned int ServerId() const { return server_id; }
  inline Storage *getStorage() {
    if ( ! storage ) {
      storage = new Storage(storage_id);
    }
    return storage;
  }
  inline CameraType GetType() const { return type; }
  inline Function GetFunction() const { return function; }
  inline PacketQueue * GetPacketQueue() { return &packetqueue; }
  inline bool Enabled() const {
    if ( function <= MONITOR )
      return false;
    return enabled;
  }
  inline bool DecodingEnabled() const {
    return decoding_enabled;
  }
  bool JanusEnabled() {
    return janus_enabled;
  }
  bool JanusAudioEnabled() {
    return janus_audio_enabled;
  }
  bool OnvifEnabled() {
    return onvif_event_listener;
  }
  int check_janus(); //returns 1 for healthy, 0 for success but missing stream, negative for error.
  bool EventPollerHealthy() {
    return Event_Poller_Healthy;
  }
  inline const char *EventPrefix() const { return event_prefix.c_str(); }
  inline bool Ready() const {
    if ( image_count >= ready_count ) {
      return true;
    }
    Debug(2, "Not ready because image_count(%d) <= ready_count(%d)", image_count, ready_count);
    return false;
  }
  inline bool Active() const {
    if ( function <= MONITOR )
      return false;
    return( enabled && shared_data->active );
  }
  inline bool Exif() const { return embed_exif; }
  inline bool RTSPServer() const { return rtsp_server; }
  inline bool RecordAudio() const { return record_audio; }

  /*
  inline Purpose Purpose() { return purpose };
  inline Purpose Purpose( Purpose p ) { purpose = p; };
  */

  Orientation getOrientation() const;

  unsigned int Width() const { return width; }
  unsigned int Height() const { return height; }
  unsigned int Colours() const;
  unsigned int SubpixelOrder() const;

  int GetAudioFrequency() const { return shared_data ? shared_data->audio_frequency : -1; }
  int GetAudioChannels() const { return shared_data ? shared_data->audio_channels : -1; }

  int GetOptSaveJPEGs() const { return savejpegs; }
  VideoWriter GetOptVideoWriter() const { return videowriter; }
  const std::string &GetEncoderOptions() const { return encoderparams; }
  int OutputCodec() const { return output_codec; }
  const std::string &Encoder() const { return encoder; }
  const std::string &OutputContainer() const { return output_container; }

  uint64_t GetVideoWriterEventId() const { return video_store_data->current_event; }
  void SetVideoWriterEventId( uint64_t p_event_id ) { video_store_data->current_event = p_event_id; }

  SystemTimePoint GetVideoWriterStartTime() const {
    return SystemTimePoint(zm::chrono::duration_cast<Microseconds>(video_store_data->recording));
  }
  void SetVideoWriterStartTime(SystemTimePoint t) {
    video_store_data->recording = zm::chrono::duration_cast<timeval>(t.time_since_epoch());
  }

  unsigned int GetPreEventCount() const { return pre_event_count; };
  int32_t GetImageBufferCount() const { return image_buffer_count; };
  State GetState() const { return (State)shared_data->state; }

  AVStream *GetAudioStream() const { return camera ? camera->getAudioStream() : nullptr; };
  AVCodecContext *GetAudioCodecContext() const { return camera ? camera->getAudioCodecContext() : nullptr; };
  AVStream *GetVideoStream() const { return camera ? camera->getVideoStream() : nullptr; };
  AVCodecContext *GetVideoCodecContext() const { return camera ? camera->getVideoCodecContext() : nullptr; };

  std::string GetSecondPath() const { return second_path; };
  std::string GetVideoFifoPath() const { return shared_data ? shared_data->video_fifo_path : ""; };
  std::string GetAudioFifoPath() const { return shared_data ? shared_data->audio_fifo_path : ""; };
  std::string GetRTSPStreamName() const { return rtsp_streamname; };

  const std::string &getONVIF_URL() const { return onvif_url; };
  const std::string &getONVIF_Username() const { return onvif_username; };
  const std::string &getONVIF_Password() const { return onvif_password; };
  const std::string &getONVIF_Options() const { return onvif_options; };

  Image *GetAlarmImage();
  int GetImage(int32_t index=-1, int scale=100);
  ZMPacket *getSnapshot( int index=-1 ) const;
  SystemTimePoint GetTimestamp(int index = -1) const;
  void UpdateAdaptiveSkip();
  useconds_t GetAnalysisRate();
  Microseconds GetAnalysisUpdateDelay() const { return analysis_update_delay; }
  unsigned int GetCaptureMaxFPS() const { return capture_max_fps; }
  Microseconds GetCaptureDelay() const { return capture_delay; }
  Microseconds GetAlarmCaptureDelay() const { return alarm_capture_delay; }
  unsigned int GetLastReadIndex() const;
  unsigned int GetLastWriteIndex() const;
  uint64_t GetLastEventId() const;
  double GetFPS() const;
  void UpdateFPS();
  void ForceAlarmOn( int force_score, const char *force_case, const char *force_text="" );
  void ForceAlarmOff();
  void CancelForced();
  TriggerState GetTriggerState() const { return trigger_data ? trigger_data->trigger_state : TRIGGER_CANCEL; }
  SystemTimePoint GetStartupTime() const { return std::chrono::system_clock::from_time_t(shared_data->startup_time); }
  void SetStartupTime(SystemTimePoint time) { shared_data->startup_time = std::chrono::system_clock::to_time_t(time); }
  void SetHeartbeatTime(SystemTimePoint time) {
    shared_data->zmc_heartbeat_time = std::chrono::system_clock::to_time_t(time);
  }
  void get_ref_image();

  int LabelSize() const { return label_size; }

  void actionReload();
  void actionEnable();
  void actionDisable();
  void actionSuspend();
  void actionResume();

  int actionBrightness(int p_brightness);
  int actionBrightness();
  int actionHue(int p_hue);
  int actionHue();
  int actionColour(int p_colour);
  int actionColour();
  int actionContrast(int p_contrast);
  int actionContrast();

  int PrimeCapture();
  int PreCapture() const;
  int Capture();
  int PostCapture() const;
  int Close();

  void CheckAction();

  unsigned int DetectMotion( const Image &comp_image, Event::StringSet &zoneSet );
   // DetectBlack seems to be unused. Check it on zm_monitor.cpp for more info.
   //unsigned int DetectBlack( const Image &comp_image, Event::StringSet &zoneSet );
  bool CheckSignal( const Image *image );
  bool Analyse();
  bool Decode();
  bool Poll();
  void DumpImage( Image *dump_image ) const;
  void TimestampImage(Image *ts_image, SystemTimePoint ts_time) const;
  Event *openEvent(
      const std::shared_ptr<ZMPacket> &snap,
      const std::string &cause,
      const Event::StringSetMap &noteSetMap);
  void closeEvent();

  void Reload();
  void ReloadZones();
  void ReloadLinkedMonitors( const char * );

  bool DumpSettings( char *output, bool verbose );
  void DumpZoneImage( const char *zone_string=0 );
  std::vector<Group *>  Groups();
  StringVector GroupNames();

  static std::vector<std::shared_ptr<Monitor>> LoadMonitors(const std::string &sql, Purpose purpose);  // Returns # of Monitors loaded, 0 on failure.
#if ZM_HAS_V4L2
  static std::vector<std::shared_ptr<Monitor>> LoadLocalMonitors(const char *device, Purpose purpose);
#endif // ZM_HAS_V4L2
  static std::vector<std::shared_ptr<Monitor>> LoadRemoteMonitors(const char *protocol, const char *host, const char*port, const char*path, Purpose purpose);
  static std::vector<std::shared_ptr<Monitor>> LoadFileMonitors(const char *file, Purpose purpose);
  static std::vector<std::shared_ptr<Monitor>> LoadFfmpegMonitors(const char *file, Purpose purpose);
  static std::shared_ptr<Monitor> Load(unsigned int id, bool load_zones, Purpose purpose);
  void Load(MYSQL_ROW dbrow, bool load_zones, Purpose purpose);
  //void writeStreamImage( Image *image, struct timeval *timestamp, int scale, int mag, int x, int y );
  //void StreamImages( int scale=100, int maxfps=10, time_t ttl=0, int msq_id=0 );
  //void StreamImagesRaw( int scale=100, int maxfps=10, time_t ttl=0 );
  //void StreamImagesZip( int scale=100, int maxfps=10, time_t ttl=0 );
  //void StreamMpeg( const char *format, int scale=100, int maxfps=10, int bitrate=100000 );
  double get_capture_fps( ) const {
    return shared_data ? shared_data->capture_fps : 0.0;
  }
  double get_analysis_fps( ) const {
    return shared_data ? shared_data->analysis_fps : 0.0;
  }
  int Importance() const { return importance; }
};

#define MOD_ADD( var, delta, limit ) (((var)+(limit)+(delta))%(limit))

#endif // ZM_MONITOR_H
