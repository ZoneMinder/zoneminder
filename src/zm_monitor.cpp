//
// ZoneMinder Monitor Class Implementation, $Date$, $Revision$
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

#include "zm_monitor.h"

#include "zm_group.h"
#include "zm_eventstream.h"
#include "zm_fifo.h"
#include "zm_file_camera.h"
#include "zm_remote_camera.h"
#include "zm_remote_camera_http.h"
#include "zm_remote_camera_nvsocket.h"
#include "zm_signal.h"
#include "zm_time.h"
#include "zm_utils.h"
#include "zm_zone.h"

#if ZM_HAS_V4L
#include "zm_local_camera.h"
#endif // ZM_HAS_V4L

#if HAVE_LIBAVFORMAT
#include "zm_remote_camera_rtsp.h"
#endif // HAVE_LIBAVFORMAT

#if HAVE_LIBAVFORMAT
#include "zm_ffmpeg_camera.h"
#endif // HAVE_LIBAVFORMAT

#if HAVE_LIBVLC
#include "zm_libvlc_camera.h"
#endif // HAVE_LIBVLC

#if HAVE_LIBCURL
#include "zm_curl_camera.h"
#endif // HAVE_LIBCURL

#if HAVE_LIBVNC
#include "zm_libvnc_camera.h"
#endif // HAVE_LIBVNC

#include <algorithm>
#include <sys/types.h>
#include <sys/stat.h>

#if ZM_MEM_MAPPED
#include <sys/mman.h>
#include <fcntl.h>
#else // ZM_MEM_MAPPED
#include <sys/ipc.h>
#include <sys/shm.h>
#endif // ZM_MEM_MAPPED

// SOLARIS - we don't have MAP_LOCKED on openSolaris/illumos
#ifndef MAP_LOCKED
#define MAP_LOCKED 0
#endif

// This is the official SQL (and ordering of the fields) to load a Monitor.
// It will be used whereever a Monitor dbrow is needed. WHERE conditions can be appended
std::string load_monitor_sql =
"SELECT `Id`, `Name`, `ServerId`, `StorageId`, `Type`, `Function`+0, `Enabled`, `DecodingEnabled`, "
"`LinkedMonitors`, `AnalysisFPSLimit`, `AnalysisUpdateDelay`, `MaxFPS`, `AlarmMaxFPS`,"
"`Device`, `Channel`, `Format`, `V4LMultiBuffer`, `V4LCapturesPerFrame`, " // V4L Settings
"`Protocol`, `Method`, `Options`, `User`, `Pass`, `Host`, `Port`, `Path`, `SecondPath`, `Width`, `Height`, `Colours`, `Palette`, `Orientation`+0, `Deinterlacing`, "
"`DecoderHWAccelName`, `DecoderHWAccelDevice`, `RTSPDescribe`, "
"`SaveJPEGs`, `VideoWriter`, `EncoderParameters`, "
"`OutputCodec`, `Encoder`, `OutputContainer`, "
"`RecordAudio`, "
"`Brightness`, `Contrast`, `Hue`, `Colour`, "
"`EventPrefix`, `LabelFormat`, `LabelX`, `LabelY`, `LabelSize`,"
"`ImageBufferCount`, `MaxImageBufferCount`, `WarmupCount`, `PreEventCount`, `PostEventCount`, `StreamReplayBuffer`, `AlarmFrameCount`, "
"`SectionLength`, `MinSectionLength`, `FrameSkip`, `MotionFrameSkip`, "
"`FPSReportInterval`, `RefBlendPerc`, `AlarmRefBlendPerc`, `TrackMotion`, `Exif`,"
"`RTSPServer`, `RTSPStreamName`,"
"`SignalCheckPoints`, `SignalCheckColour`, `Importance`-1 FROM `Monitors`";

std::string CameraType_Strings[] = {
  "Unknown",
  "Local",
  "Remote",
  "File",
  "Ffmpeg",
  "LibVLC",
  "NVSOCKET",
  "CURL",
  "VNC"
};

std::string Function_Strings[] = {
  "Unknown",
  "None",
  "Monitor",
  "Modect",
  "Record",
  "Mocord",
  "Nodect"
};

std::string State_Strings[] = {
  "Unknown",
  "IDLE",
  "PREALARM",
  "ALARM",
  "ALERT",
  "TAPE"
};

std::string TriggerState_Strings[] = {
  "Cancel", "On", "Off"
};

Monitor::MonitorLink::MonitorLink(unsigned int p_id, const char *p_name) :
  id(p_id),
  shared_data(nullptr),
  trigger_data(nullptr),
  video_store_data(nullptr)
{
  strncpy(name, p_name, sizeof(name)-1);

#if ZM_MEM_MAPPED
  map_fd = -1;
  mem_file = stringtf("%s/zm.mmap.%u", staticConfig.PATH_MAP.c_str(), id);
#else // ZM_MEM_MAPPED
  shm_id = 0;
#endif // ZM_MEM_MAPPED
  mem_size = 0;
  mem_ptr = nullptr;

  last_event_id = 0;
  last_state = IDLE;

  last_connect_time = 0;
  connected = false;
}

Monitor::MonitorLink::~MonitorLink() {
  disconnect();
}

bool Monitor::MonitorLink::connect() {
  if ( !last_connect_time || (time(nullptr) - last_connect_time) > 60 ) {
    last_connect_time = time(nullptr);

    mem_size = sizeof(SharedData) + sizeof(TriggerData);

    Debug(1, "link.mem.size=%jd", mem_size);
#if ZM_MEM_MAPPED
    map_fd = open(mem_file.c_str(), O_RDWR, (mode_t)0600);
    if (map_fd < 0) {
      Debug(3, "Can't open linked memory map file %s: %s", mem_file.c_str(), strerror(errno));
      disconnect();
      return false;
    }
    while (map_fd <= 2) {
      int new_map_fd = dup(map_fd);
      Warning("Got one of the stdio fds for our mmap handle. map_fd was %d, new one is %d", map_fd, new_map_fd);
      close(map_fd);
      map_fd = new_map_fd;
    }

    struct stat map_stat;
    if (fstat(map_fd, &map_stat) < 0) {
      Error("Can't stat linked memory map file %s: %s", mem_file.c_str(), strerror(errno));
      disconnect();
      return false;
    }

    if (map_stat.st_size == 0) {
      Error("Linked memory map file %s is empty: %s", mem_file.c_str(), strerror(errno));
      disconnect();
      return false;
    } else if (map_stat.st_size < mem_size) {
      Error("Got unexpected memory map file size %ld, expected %jd", map_stat.st_size, mem_size);
      disconnect();
      return false;
    }

    mem_ptr = (unsigned char *)mmap(nullptr, mem_size, PROT_READ|PROT_WRITE, MAP_SHARED, map_fd, 0);
    if (mem_ptr == MAP_FAILED) {
      Error("Can't map file %s (%jd bytes) to memory: %s", mem_file.c_str(), mem_size, strerror(errno));
      disconnect();
      return false;
    }
#else // ZM_MEM_MAPPED
    shm_id = shmget((config.shm_key&0xffff0000)|id, mem_size, 0700);
    if (shm_id < 0) {
      Debug(3, "Can't shmget link memory: %s", strerror(errno));
      connected = false;
      return false;
    }
    mem_ptr = (unsigned char *)shmat(shm_id, 0, 0);
    if ((int)mem_ptr == -1) {
      Debug(3, "Can't shmat link memory: %s", strerror(errno));
      connected = false;
      return false;
    }
#endif // ZM_MEM_MAPPED

    shared_data = (SharedData *)mem_ptr;
    trigger_data = (TriggerData *)((char *)shared_data + sizeof(SharedData));

    if (!shared_data->valid) {
      Debug(3, "Linked memory not initialised by capture daemon");
      disconnect();
      return false;
    }

    last_state = shared_data->state;
    last_event_id = shared_data->last_event_id;
    connected = true;

    return true;
  }
  return false;
} // end bool Monitor::MonitorLink::connect()

bool Monitor::MonitorLink::disconnect() {
  if (connected) {
    connected = false;

#if ZM_MEM_MAPPED
    if (mem_ptr > (void *)0) {
      msync(mem_ptr, mem_size, MS_ASYNC);
      munmap(mem_ptr, mem_size);
    }
    if (map_fd >= 0)
      close(map_fd);

    map_fd = -1;
#else // ZM_MEM_MAPPED
    struct shmid_ds shm_data;
    if (shmctl(shm_id, IPC_STAT, &shm_data) < 0) {
      Debug(3, "Can't shmctl: %s", strerror(errno));
      return false;
    }

    shm_id = 0;

    if (shm_data.shm_nattch <= 1) {
      if (shmctl(shm_id, IPC_RMID, 0) < 0) {
        Debug(3, "Can't shmctl: %s", strerror(errno));
        return false;
      }
    }

    if (shmdt(mem_ptr) < 0) {
      Debug(3, "Can't shmdt: %s", strerror(errno));
      return false;
    }
#endif // ZM_MEM_MAPPED
    mem_size = 0;
    mem_ptr = nullptr;
  }
  return true;
}

bool Monitor::MonitorLink::isAlarmed() {
  if (!connected) {
    return false;
  }
  return( shared_data->state == ALARM );
}

bool Monitor::MonitorLink::inAlarm() {
  if (!connected) {
    return false;
  }
  return( shared_data->state == ALARM || shared_data->state == ALERT );
}

bool Monitor::MonitorLink::hasAlarmed() {
  if (shared_data->state == ALARM) {
    return true;
  }
  last_event_id = shared_data->last_event_id;
  return false;
}

Monitor::Monitor() 
 : id(0),
  name(""),
  server_id(0),
  storage_id(0),
  type(LOCAL),
  function(NONE),
  enabled(0),
  decoding_enabled(0),
  //protocol
  //method
  //options
  //host
  //port
  //user
  //pass
  //path
  //device 
  palette(0),
  channel(0),
  format(0),

  width(0),
  height(0),
  //v4l_multi_buffer
  //v4l_captures_per_frame
  orientation(ROTATE_0),
  deinterlacing(0),
  deinterlacing_value(0),
  decoder_hwaccel_name(""),
  decoder_hwaccel_device(""),
  videoRecording(0),
  rtsp_describe(0),

  savejpegs(0),
  colours(0),
  videowriter(DISABLED),
  encoderparams(""),
  output_codec(0),
  encoder(""),
  output_container(""),
  imagePixFormat(AV_PIX_FMT_NONE),
  record_audio(0),
//event_prefix
//label_format
  label_coord(Vector2(0,0)),
  label_size(0),
  image_buffer_count(0),
  max_image_buffer_count(0),
  warmup_count(0),
  pre_event_count(0),
  post_event_count(0),
  stream_replay_buffer(0),
  section_length(0),
  min_section_length(0),
  adaptive_skip(false),
  frame_skip(0),
  motion_frame_skip(0),
  analysis_fps_limit(0),
  analysis_update_delay(0),
  capture_delay(0),
  alarm_capture_delay(0),
  alarm_frame_count(0),
  alert_to_alarm_frame_count(0),
  fps_report_interval(0),
  ref_blend_perc(0),
  alarm_ref_blend_perc(0),
  track_motion(0),
  signal_check_points(0),
  signal_check_colour(0),
  embed_exif(0),
  rtsp_server(0),
  rtsp_streamname(""),
  importance(0),
  capture_max_fps(0),
  purpose(QUERY),
  last_camera_bytes(0),
  event_count(0),
  //image_count(0),
  last_capture_image_count(0),
  analysis_image_count(0),
  motion_frame_count(0),
  last_motion_frame_count(0),
  ready_count(0),
  first_alarm_count(0),
  last_alarm_count(0),
  last_signal(false),
  last_section_mod(0),
  buffer_count(0),
  state(IDLE),
  start_time(0),
  last_fps_time(0),
  last_analysis_fps_time(0),
  auto_resume_time(0),
  last_motion_score(0),
  event_close_mode(CLOSE_IDLE),
#if ZM_MEM_MAPPED
  map_fd(-1),
  mem_file(""),
#else // ZM_MEM_MAPPED
  shm_id(-1),
#endif // ZM_MEM_MAPPED
  mem_size(0),
  mem_ptr(nullptr),
  shared_data(nullptr),
  trigger_data(nullptr),
  video_store_data(nullptr),
  shared_timestamps(nullptr),
  shared_images(nullptr),
  video_stream_id(-1),
  audio_stream_id(-1),
  video_fifo(nullptr),
  audio_fifo(nullptr),
  camera(nullptr),
  event(nullptr),
  storage(nullptr),
  videoStore(nullptr),
  analysis_it(nullptr),
  analysis_thread(nullptr),
  decoder_it(nullptr),
  decoder(nullptr),
  dest_frame(nullptr),
  convert_context(nullptr),
  //zones(nullptr),
  privacy_bitmask(nullptr),
  n_linked_monitors(0),
  linked_monitors(nullptr),
  red_val(0),
  green_val(0),
  blue_val(0),
  grayscale_val(0),
  colour_val(0)
{

  if ( strcmp(config.event_close_mode, "time") == 0 )
    event_close_mode = CLOSE_TIME;
  else if ( strcmp(config.event_close_mode, "alarm") == 0 )
    event_close_mode = CLOSE_ALARM;
  else
    event_close_mode = CLOSE_IDLE;

  event = 0;
  last_section_mod = 0;

  adaptive_skip = true;

  videoStore = nullptr;
}  // Monitor::Monitor

/*
  std::string load_monitor_sql =
 "SELECT Id, Name, ServerId, StorageId, Type, Function+0, Enabled, DecodingEnabled, LinkedMonitors, "
 "AnalysisFPSLimit, AnalysisUpdateDelay, MaxFPS, AlarmMaxFPS,"
 "Device, Channel, Format, V4LMultiBuffer, V4LCapturesPerFrame, " // V4L Settings
 "Protocol, Method, Options, User, Pass, Host, Port, Path, SecondPath, Width, Height, Colours, Palette, Orientation+0, Deinterlacing, RTSPDescribe, "
 "SaveJPEGs, VideoWriter, EncoderParameters,
 "OutputCodec, Encoder, OutputContainer, RecordAudio, "
 "Brightness, Contrast, Hue, Colour, "
 "EventPrefix, LabelFormat, LabelX, LabelY, LabelSize,"
 "ImageBufferCount, `MaxImageBufferCount`, WarmupCount, PreEventCount, PostEventCount, StreamReplayBuffer, AlarmFrameCount, "
 "SectionLength, MinSectionLength, FrameSkip, MotionFrameSkip, "
 "FPSReportInterval, RefBlendPerc, AlarmRefBlendPerc, TrackMotion, Exif,"
 "`RTSPServer`,`RTSPStreamName`,
 "SignalCheckPoints, SignalCheckColour, Importance-1 FROM Monitors";
*/

void Monitor::Load(MYSQL_ROW dbrow, bool load_zones=true, Purpose p = QUERY) {
  purpose = p;
  int col = 0;

  id = atoi(dbrow[col]); col++;
  name = dbrow[col]; col++;
  server_id = dbrow[col] ? atoi(dbrow[col]) : 0; col++;

  storage_id = atoi(dbrow[col]); col++;
  if (storage) delete storage;
  storage = new Storage(storage_id);

  if ( ! strcmp(dbrow[col], "Local") ) {
    type = LOCAL;
  } else if ( ! strcmp(dbrow[col], "Ffmpeg") ) {
    type = FFMPEG;
  } else if ( ! strcmp(dbrow[col], "Remote") ) {
    type = REMOTE;
  } else if ( ! strcmp(dbrow[col], "File") ) {
    type = FILE;
  } else if ( ! strcmp(dbrow[col], "NVSocket") ) {
    type = NVSOCKET;
  } else if ( ! strcmp(dbrow[col], "Libvlc") ) {
    type = LIBVLC;
  } else if ( ! strcmp(dbrow[col], "cURL") ) {
    type = CURL;
  } else if ( ! strcmp(dbrow[col], "VNC") ) {
    type = VNC;
  } else {
    Fatal("Bogus monitor type '%s' for monitor %d", dbrow[col], id);
  }
  Debug(1, "Have camera type %s", CameraType_Strings[type].c_str());
  col++;
  function = (Function)atoi(dbrow[col]); col++;
  enabled = dbrow[col] ? atoi(dbrow[col]) : false; col++;
  decoding_enabled = dbrow[col] ? atoi(dbrow[col]) : false; col++;
  // See below after save_jpegs for a recalculation of decoding_enabled

  ReloadLinkedMonitors(dbrow[col]); col++;

  /* "AnalysisFPSLimit, AnalysisUpdateDelay, MaxFPS, AlarmMaxFPS," */
  analysis_fps_limit = dbrow[col] ? strtod(dbrow[col], nullptr) : 0.0; col++;
  analysis_update_delay = strtoul(dbrow[col++], nullptr, 0);
  capture_delay = (dbrow[col] && atof(dbrow[col])>0.0)?int(DT_PREC_6/atof(dbrow[col])):0; col++;
  alarm_capture_delay = (dbrow[col] && atof(dbrow[col])>0.0)?int(DT_PREC_6/atof(dbrow[col])):0; col++;

  /* "Device, Channel, Format, V4LMultiBuffer, V4LCapturesPerFrame, " // V4L Settings */
  device = dbrow[col] ? dbrow[col] : ""; col++;
  channel = atoi(dbrow[col]); col++;
  format = atoi(dbrow[col]); col++;
  v4l_multi_buffer = config.v4l_multi_buffer;
  if ( dbrow[col] ) {
    if (*dbrow[col] == '0' ) {
      v4l_multi_buffer = false;
    } else if ( *dbrow[col] == '1' ) {
      v4l_multi_buffer = true;
    }
  }
  col++;

  v4l_captures_per_frame = 0;
  if ( dbrow[col] ) {
    v4l_captures_per_frame = atoi(dbrow[col]);
  } else {
    v4l_captures_per_frame = config.captures_per_frame;
  }
  col++;

  /* "Protocol, Method, Options, User, Pass, Host, Port, Path, SecondPath, Width, Height, Colours, Palette, Orientation+0, Deinterlacing, " */
  protocol = dbrow[col] ? dbrow[col] : ""; col++;
  method = dbrow[col] ? dbrow[col] : ""; col++;
  options = dbrow[col] ? dbrow[col] : ""; col++;
  user = dbrow[col] ? dbrow[col] : ""; col++;
  pass = dbrow[col] ? dbrow[col] : ""; col++;
  host = dbrow[col] ? dbrow[col] : ""; col++;
  port = dbrow[col] ? dbrow[col] : ""; col++;
  path = dbrow[col] ? dbrow[col] : ""; col++;
  second_path = dbrow[col] ? dbrow[col] : ""; col++;
  camera_width = atoi(dbrow[col]); col++;
  camera_height = atoi(dbrow[col]); col++;
  colours = atoi(dbrow[col]); col++;
  palette = atoi(dbrow[col]); col++;
  orientation = (Orientation)atoi(dbrow[col]); col++;
  width = (orientation==ROTATE_90||orientation==ROTATE_270) ? camera_height : camera_width;
  height = (orientation==ROTATE_90||orientation==ROTATE_270) ? camera_width : camera_height;
  deinterlacing = atoi(dbrow[col]); col++;
  deinterlacing_value = deinterlacing & 0xff;

/*"`DecoderHWAccelName`, `DecoderHWAccelDevice`, `RTSPDescribe`, " */
  decoder_hwaccel_name = dbrow[col] ? dbrow[col] : ""; col++;
  decoder_hwaccel_device = dbrow[col] ? dbrow[col] : ""; col++;
  rtsp_describe = (dbrow[col] && *dbrow[col] != '0'); col++;


/* "`SaveJPEGs`, `VideoWriter`, `EncoderParameters`, " */
  savejpegs = atoi(dbrow[col]); col++;
  videowriter = (VideoWriter)atoi(dbrow[col]); col++;
  encoderparams = dbrow[col] ? dbrow[col] : ""; col++;

  decoding_enabled = !(
      ( function == RECORD or function == NODECT )
      and
      ( savejpegs == 0 )
      and
      ( videowriter == PASSTHROUGH )
      and
      !decoding_enabled
      );
  Debug(3, "Decoding enabled: %d function %d %s savejpegs %d videowriter %d", decoding_enabled, function, Function_Strings[function].c_str(), savejpegs, videowriter);

/*"`OutputCodec`, `Encoder`, `OutputContainer`, " */
  output_codec = dbrow[col] ? atoi(dbrow[col]) : 0; col++;
  encoder = dbrow[col] ? dbrow[col] : ""; col++;
  output_container = dbrow[col] ? dbrow[col] : ""; col++;
  record_audio = (*dbrow[col] != '0'); col++;

 /* "Brightness, Contrast, Hue, Colour, " */
  brightness = atoi(dbrow[col]); col++;
  contrast = atoi(dbrow[col]); col++;
  hue = atoi(dbrow[col]); col++;
  colour = atoi(dbrow[col]); col++;

  /* "EventPrefix, LabelFormat, LabelX, LabelY, LabelSize," */
  event_prefix = dbrow[col] ? dbrow[col] : ""; col++;
  label_format = dbrow[col] ? ReplaceAll(dbrow[col], "\\n", "\n") : ""; col++;
  label_coord = Vector2(atoi(dbrow[col]), atoi(dbrow[col + 1])); col += 2;
  label_size = atoi(dbrow[col]); col++;

  /* "ImageBufferCount, `MaxImageBufferCount`, WarmupCount, PreEventCount, PostEventCount, StreamReplayBuffer, AlarmFrameCount, " */
  image_buffer_count = atoi(dbrow[col]); col++;
  max_image_buffer_count = atoi(dbrow[col]); col++;
  warmup_count = atoi(dbrow[col]); col++;
  pre_event_count = atoi(dbrow[col]); col++;
  packetqueue.setPreEventVideoPackets(pre_event_count);
  packetqueue.setMaxVideoPackets(max_image_buffer_count);
  packetqueue.setKeepKeyframes(videowriter == PASSTHROUGH);
  post_event_count = atoi(dbrow[col]); col++;
  stream_replay_buffer = atoi(dbrow[col]); col++;
  alarm_frame_count = atoi(dbrow[col]); col++;
  if (alarm_frame_count < 1) alarm_frame_count = 1;
  else if (alarm_frame_count > MAX_PRE_ALARM_FRAMES) alarm_frame_count = MAX_PRE_ALARM_FRAMES;

 /* "SectionLength, MinSectionLength, FrameSkip, MotionFrameSkip, " */
  section_length = atoi(dbrow[col]); col++;
  min_section_length = atoi(dbrow[col]); col++;
  frame_skip = atoi(dbrow[col]); col++;
  motion_frame_skip = atoi(dbrow[col]); col++;

 /* "FPSReportInterval, RefBlendPerc, AlarmRefBlendPerc, TrackMotion, Exif," */
  fps_report_interval = atoi(dbrow[col]); col++;
  ref_blend_perc = atoi(dbrow[col]); col++;
  alarm_ref_blend_perc = atoi(dbrow[col]); col++;
  track_motion = atoi(dbrow[col]); col++;
  embed_exif = (*dbrow[col] != '0'); col++;

 /* "`RTSPServer`,`RTSPStreamName`, */
  rtsp_server = (*dbrow[col] != '0'); col++;
  rtsp_streamname = dbrow[col]; col++;

 /*"SignalCheckPoints, SignalCheckColour, Importance-1 FROM Monitors"; */
  signal_check_points = atoi(dbrow[col]); col++;
  signal_check_colour = strtol(dbrow[col][0] == '#' ? dbrow[col]+1 : dbrow[col], 0, 16); col++;

  colour_val = rgb_convert(signal_check_colour, ZM_SUBPIX_ORDER_BGR); /* HTML colour code is actually BGR in memory, we want RGB */
  colour_val = rgb_convert(colour_val, palette);
  red_val = RED_VAL_BGRA(signal_check_colour);
  green_val = GREEN_VAL_BGRA(signal_check_colour);
  blue_val = BLUE_VAL_BGRA(signal_check_colour);
  grayscale_val = signal_check_colour & 0xff; /* Clear all bytes but lowest byte */

  importance = dbrow[col] ? atoi(dbrow[col]) : 0;// col++;
  if (importance < 0) importance = 0; // Should only be >= 0

  // How many frames we need to have before we start analysing
  ready_count = std::max(warmup_count, pre_event_count);

  last_alarm_count = 0;
  state = IDLE;
  last_signal = true;   // Defaulting to having signal so that we don't get a signal change on the first frame.
                        // Instead initial failure to capture will cause a loss of signal change which I think makes more sense.
  uint64_t image_size = width * height * colours;

  if ( strcmp(config.event_close_mode, "time") == 0 )
    event_close_mode = CLOSE_TIME;
  else if ( strcmp(config.event_close_mode, "alarm") == 0 )
    event_close_mode = CLOSE_ALARM;
  else
    event_close_mode = CLOSE_IDLE;

  mem_size = sizeof(SharedData)
       + sizeof(TriggerData)
       + sizeof(VideoStoreData) //Information to pass back to the capture process
       + (image_buffer_count * sizeof(struct timeval))
       + (image_buffer_count * image_size)
       + 64; /* Padding used to permit aligning the images buffer to 64 byte boundary */

  Debug(1,
        "mem.size(%zu) SharedData=%zu TriggerData=%zu VideoStoreData=%zu timestamps=%zu images=%dx%" PRIi64 " = %" PRId64 " total=%jd",
        sizeof(mem_size),
        sizeof(SharedData),
        sizeof(TriggerData),
        sizeof(VideoStoreData),
        (image_buffer_count * sizeof(struct timeval)),
        image_buffer_count,
        image_size,
        (image_buffer_count * image_size),
        mem_size);

  // Should maybe store this for later use
  std::string monitor_dir = stringtf("%s/%u", storage->Path(), id);

  if ( purpose != QUERY ) {
    LoadCamera();
    ReloadZones();

    if ( mkdir(monitor_dir.c_str(), 0755) && ( errno != EEXIST ) ) {
      Error("Can't mkdir %s: %s", monitor_dir.c_str(), strerror(errno));
    }

    if ( config.record_diag_images ) {
      if ( config.record_diag_images_fifo ) {
        diag_path_ref = stringtf("%s/diagpipe-r-%d.jpg", staticConfig.PATH_SOCKS.c_str(), id);
        diag_path_delta = stringtf("%s/diagpipe-d-%d.jpg", staticConfig.PATH_SOCKS.c_str(), id);
        Fifo::fifo_create_if_missing(diag_path_ref.c_str());
        Fifo::fifo_create_if_missing(diag_path_delta.c_str());
      } else {
        diag_path_ref = stringtf("%s/%d/diag-r.jpg", storage->Path(), id);
        diag_path_delta = stringtf("%s/%d/diag-d.jpg", storage->Path(), id);
      }
    }
  }  // end if purpose

  Debug(1, "Loaded monitor %d(%s), %zu zones", id, name.c_str(), zones.size());
} // Monitor::Load(MYSQL_ROW dbrow, bool load_zones=true, Purpose p = QUERY)

void Monitor::LoadCamera() {
  if (camera)
    return;

  switch (type) {
    case LOCAL: {
#if ZM_HAS_V4L
      int extras = (deinterlacing >> 24) & 0xff;

      camera = ZM::make_unique<LocalCamera>(this,
                                            device,
                                            channel,
                                            format,
                                            v4l_multi_buffer,
                                            v4l_captures_per_frame,
                                            method,
                                            camera_width,
                                            camera_height,
                                            colours,
                                            palette,
                                            brightness,
                                            contrast,
                                            hue,
                                            colour,
                                            purpose == CAPTURE,
                                            record_audio,
                                            extras
      );
#else
      Fatal("Not compiled with local v4l camera support");
#endif
      break;
    }
    case REMOTE: {
      if (protocol == "http") {
        camera = ZM::make_unique<RemoteCameraHttp>(this,
                                                   method,
                                                   host,
                                                   port,
                                                   path,
                                                   camera_width,
                                                   camera_height,
                                                   colours,
                                                   brightness,
                                                   contrast,
                                                   hue,
                                                   colour,
                                                   purpose == CAPTURE,
                                                   record_audio
        );
      }
#if HAVE_LIBAVFORMAT
      else if (protocol == "rtsp") {
        camera = ZM::make_unique<RemoteCameraRtsp>(this,
                                                   method,
                                                   host, // Host
                                                   port, // Port
                                                   path, // Path
                                                   camera_width,
                                                   camera_height,
                                                   rtsp_describe,
                                                   colours,
                                                   brightness,
                                                   contrast,
                                                   hue,
                                                   colour,
                                                   purpose == CAPTURE,
                                                   record_audio
        );
      }
#endif // HAVE_LIBAVFORMAT
      else {
        Error("Unexpected remote camera protocol '%s'", protocol.c_str());
      }
      break;
    }
    case FILE: {
      camera = ZM::make_unique<FileCamera>(this,
                                           path.c_str(),
                                           camera_width,
                                           camera_height,
                                           colours,
                                           brightness,
                                           contrast,
                                           hue,
                                           colour,
                                           purpose == CAPTURE,
                                           record_audio
      );
      break;
    }
#if HAVE_LIBAVFORMAT
    case FFMPEG: {
      camera = ZM::make_unique<FfmpegCamera>(this,
                                             path,
                                             second_path,
                                             method,
                                             options,
                                             camera_width,
                                             camera_height,
                                             colours,
                                             brightness,
                                             contrast,
                                             hue,
                                             colour,
                                             purpose == CAPTURE,
                                             record_audio,
                                             decoder_hwaccel_name,
                                             decoder_hwaccel_device
      );
      break;
    }
#endif // HAVE_LIBAVFORMAT
    case NVSOCKET: {
      camera = ZM::make_unique<RemoteCameraNVSocket>(this,
                                                     host.c_str(),
                                                     port.c_str(),
                                                     path.c_str(),
                                                     camera_width,
                                                     camera_height,
                                                     colours,
                                                     brightness,
                                                     contrast,
                                                     hue,
                                                     colour,
                                                     purpose == CAPTURE,
                                                     record_audio
      );
      break;
    }
    case LIBVLC: {
#if HAVE_LIBVLC
      camera = ZM::make_unique<LibvlcCamera>(this,
                                             path.c_str(),
                                             method,
                                             options,
                                             camera_width,
                                             camera_height,
                                             colours,
                                             brightness,
                                             contrast,
                                             hue,
                                             colour,
                                             purpose == CAPTURE,
                                             record_audio
      );
#else // HAVE_LIBVLC
      Error("You must have vlc libraries installed to use vlc cameras for monitor %d", id);
#endif // HAVE_LIBVLC
      break;
    }
    case CURL: {
#if HAVE_LIBCURL
      camera = ZM::make_unique<cURLCamera>(this,
                                           path.c_str(),
                                           user.c_str(),
                                           pass.c_str(),
                                           camera_width,
                                           camera_height,
                                           colours,
                                           brightness,
                                           contrast,
                                           hue,
                                           colour,
                                           purpose == CAPTURE,
                                           record_audio
      );
#else // HAVE_LIBCURL
      Error("You must have libcurl installed to use ffmpeg cameras for monitor %d", id);
#endif // HAVE_LIBCURL
      break;
    }
    case VNC: {
#if HAVE_LIBVNC
      camera = ZM::make_unique<VncCamera>(this,
                                          host.c_str(),
                                          port.c_str(),
                                          user.c_str(),
                                          pass.c_str(),
                                          width,
                                          height,
                                          colours,
                                          brightness,
                                          contrast,
                                          hue,
                                          colour,
                                          purpose == CAPTURE,
                                          record_audio
      );
#else // HAVE_LIBVNC
      Fatal("You must have libvnc installed to use VNC cameras for monitor id %d", id);
#endif // HAVE_LIBVNC
      break;
    }
    default: {
      Fatal("Tried to load unsupported camera type %d for monitor %u", int(type), id);
      break;
    }
  }
}

std::shared_ptr<Monitor> Monitor::Load(unsigned int p_id, bool load_zones, Purpose purpose) {
  std::string sql = load_monitor_sql + stringtf(" WHERE Id=%d", p_id);

  zmDbRow dbrow;
  if ( !dbrow.fetch(sql.c_str()) ) {
    Error("Can't use query result: %s", mysql_error(&dbconn));
    return nullptr;
  }

  std::shared_ptr<Monitor> monitor = std::make_shared<Monitor>();
  monitor->Load(dbrow.mysql_row(), load_zones, purpose);

  return monitor;
}

bool Monitor::connect() {
  if (mem_ptr != nullptr) {
    Warning("Already connected. Please call disconnect first.");
  }
  Debug(3, "Connecting to monitor.  Purpose is %d", purpose);
#if ZM_MEM_MAPPED
  mem_file = stringtf("%s/zm.mmap.%u", staticConfig.PATH_MAP.c_str(), id);
  if (purpose != CAPTURE) {
    map_fd = open(mem_file.c_str(), O_RDWR);
  } else {
    map_fd = open(mem_file.c_str(), O_RDWR|O_CREAT, (mode_t)0660);
  }

  if (map_fd < 0) {
    Error("Can't open memory map file %s: %s", mem_file.c_str(), strerror(errno));
    return false;
  } else {
    Debug(3, "Success opening mmap file at (%s)", mem_file.c_str());
  }

  struct stat map_stat;
  if (fstat(map_fd, &map_stat) < 0) {
    Error("Can't stat memory map file %s: %s, is the zmc process for this monitor running?", mem_file.c_str(), strerror(errno));
    close(map_fd);
    map_fd = -1;
    return false;
  }

  if (map_stat.st_size != mem_size) {
    if (purpose == CAPTURE) {
      // Allocate the size
      if (ftruncate(map_fd, mem_size) < 0) {
        Error("Can't extend memory map file %s to %jd bytes: %s", mem_file.c_str(), mem_size, strerror(errno));
        close(map_fd);
        map_fd = -1;
        return false;
      }
    } else if (map_stat.st_size == 0) {
      Error("Got empty memory map file size %ld, is the zmc process for this monitor running?", map_stat.st_size);
      close(map_fd);
      map_fd = -1;
      return false;
    } else {
      Error("Got unexpected memory map file size %ld, expected %jd", map_stat.st_size, mem_size);
      close(map_fd);
      map_fd = -1;
      return false;
    }
  }  // end if map_stat.st_size != mem_size

  Debug(3, "MMap file size is %ld", map_stat.st_size);
#ifdef MAP_LOCKED
  mem_ptr = (unsigned char *)mmap(nullptr, mem_size, PROT_READ|PROT_WRITE, MAP_SHARED|MAP_LOCKED, map_fd, 0);
  if (mem_ptr == MAP_FAILED) {
    if (errno == EAGAIN) {
      Debug(1, "Unable to map file %s (%jd bytes) to locked memory, trying unlocked", mem_file.c_str(), mem_size);
#endif
      mem_ptr = (unsigned char *)mmap(nullptr, mem_size, PROT_READ|PROT_WRITE, MAP_SHARED, map_fd, 0);
      Debug(1, "Mapped file %s (%jd bytes) to unlocked memory", mem_file.c_str(), mem_size);
#ifdef MAP_LOCKED
    } else {
      Error("Unable to map file %s (%jd bytes) to locked memory (%s)", mem_file.c_str(), mem_size, strerror(errno));
    }
  }
#endif
  if ((mem_ptr == MAP_FAILED) or (mem_ptr == nullptr)) {
    Error("Can't map file %s (%jd bytes) to memory: %s(%d)", mem_file.c_str(), mem_size, strerror(errno), errno);
    close(map_fd);
    map_fd = -1;
    mem_ptr = nullptr;
    return false;
  }
#else // ZM_MEM_MAPPED
  shm_id = shmget((config.shm_key&0xffff0000)|id, mem_size, IPC_CREAT|0700);
  if (shm_id < 0) {
    Fatal("Can't shmget, probably not enough shared memory space free: %s", strerror(errno));
  }
  mem_ptr = (unsigned char *)shmat(shm_id, 0, 0);
  if ((int)mem_ptr == -1) {
    Fatal("Can't shmat: %s", strerror(errno));
  }
#endif // ZM_MEM_MAPPED

  shared_data = (SharedData *)mem_ptr;
  trigger_data = (TriggerData *)((char *)shared_data + sizeof(SharedData));
  video_store_data = (VideoStoreData *)((char *)trigger_data + sizeof(TriggerData));
  shared_timestamps = (struct timeval *)((char *)video_store_data + sizeof(VideoStoreData));
  shared_images = (unsigned char *)((char *)shared_timestamps + (image_buffer_count*sizeof(struct timeval)));

  if (((unsigned long)shared_images % 64) != 0) {
    /* Align images buffer to nearest 64 byte boundary */
    Debug(3, "Aligning shared memory images to the next 64 byte boundary");
    shared_images = (uint8_t*)((unsigned long)shared_images + (64 - ((unsigned long)shared_images % 64)));
  }
  if (!camera) LoadCamera();

  image_buffer.resize(image_buffer_count);
  for (int32_t i = 0; i < image_buffer_count; i++) {
    image_buffer[i] = new Image(width, height, camera->Colours(), camera->SubpixelOrder(), &(shared_images[i*camera->ImageSize()]));
    image_buffer[i]->HoldBuffer(true); /* Don't release the internal buffer or replace it with another */
  }
  Debug(3, "Allocated %zu %zu image buffers", image_buffer.capacity(), image_buffer.size());

  if (purpose == CAPTURE) {
    memset(mem_ptr, 0, mem_size);
    shared_data->size = sizeof(SharedData);
    shared_data->active = enabled;
    shared_data->signal = false;
    shared_data->capture_fps = 0.0;
    shared_data->analysis_fps = 0.0;
    shared_data->state = state = IDLE;
    shared_data->last_write_index = image_buffer_count;
    shared_data->last_read_index = image_buffer_count;
    shared_data->last_write_time = 0;
    shared_data->last_event_id = 0;
    shared_data->action = (Action)0;
    shared_data->brightness = -1;
    shared_data->hue = -1;
    shared_data->colour = -1;
    shared_data->contrast = -1;
    shared_data->alarm_x = -1;
    shared_data->alarm_y = -1;
    shared_data->format = camera->SubpixelOrder();
    shared_data->imagesize = camera->ImageSize();
    shared_data->alarm_cause[0] = 0;
    shared_data->video_fifo_path[0] = 0;
    shared_data->audio_fifo_path[0] = 0;
    shared_data->last_frame_score = 0;
    shared_data->audio_frequency = -1;
    shared_data->audio_channels = -1;
    trigger_data->size = sizeof(TriggerData);
    trigger_data->trigger_state = TriggerState::TRIGGER_CANCEL;
    trigger_data->trigger_score = 0;
    trigger_data->trigger_cause[0] = 0;
    trigger_data->trigger_text[0] = 0;
    trigger_data->trigger_showtext[0] = 0;
    video_store_data->recording = {};
    // Uh, why nothing?  Why not nullptr?
    snprintf(video_store_data->event_file, sizeof(video_store_data->event_file), "nothing");
    video_store_data->size = sizeof(VideoStoreData);
    usedsubpixorder = camera->SubpixelOrder();  // Used in CheckSignal
    shared_data->valid = true;
  } else if (!shared_data->valid) {
    Error("Shared data not initialised by capture daemon for monitor %s", name.c_str());
    return false;
  }

  // We set these here because otherwise the first fps calc is meaningless
  struct timeval now;
  gettimeofday(&now, nullptr);
  double now_double = (double)now.tv_sec + (0.000001f * now.tv_usec);
  last_fps_time = now_double;
  last_analysis_fps_time = now_double;
  last_capture_image_count = 0;

  Debug(3, "Success connecting");
  return true;
} // Monitor::connect

bool Monitor::disconnect() {
  if (mem_ptr == nullptr) {
    Debug(1, "Already disconnected");
    return true;
  }

  if (purpose == CAPTURE) {
    if (unlink(mem_file.c_str()) < 0) {
      Warning("Can't unlink '%s': %s", mem_file.c_str(), strerror(errno));
    }
    Debug(1, "Setting shared_data->valid = false");
    shared_data->valid = false;
  }
#if ZM_MEM_MAPPED
  msync(mem_ptr, mem_size, MS_ASYNC);
  munmap(mem_ptr, mem_size);
  if (map_fd >= 0) close(map_fd);

  map_fd = -1;
  mem_ptr = nullptr;
  shared_data = nullptr;

#else // ZM_MEM_MAPPED
  struct shmid_ds shm_data;
  if (shmctl(shm_id, IPC_STAT, &shm_data) < 0) {
    Debug(3, "Can't shmctl: %s", strerror(errno));
    return false;
  }

  shm_id = 0;

  if ((shm_data.shm_nattch <= 1) and (shmctl(shm_id, IPC_RMID, 0) < 0)) {
    Debug(3, "Can't shmctl: %s", strerror(errno));
    return false;
  }

  if (shmdt(mem_ptr) < 0) {
    Debug(3, "Can't shmdt: %s", strerror(errno));
    return false;
  }
#endif // ZM_MEM_MAPPED

  for (int32_t i = 0; i < image_buffer_count; i++) {
    // We delete the image because it is an object pointing to space that won't be free'd.
    delete image_buffer[i];
    image_buffer[i] = nullptr;
  }

  return true;
}  // end bool Monitor::disconnect()

Monitor::~Monitor() {
  Close();

  if (mem_ptr != nullptr) {
    if (purpose != QUERY) {
      memset(mem_ptr, 0, mem_size);
    }  // end if purpose != query
    disconnect();
  }  // end if mem_ptr

  // Will be free by packetqueue destructor
  analysis_it = nullptr;
  decoder_it = nullptr;

  delete storage;
  if (n_linked_monitors) {
    for ( int i=0; i < n_linked_monitors; i++ ) {
      delete linked_monitors[i];
    }
    delete[] linked_monitors;
    linked_monitors = nullptr;
  }

  if (video_fifo) delete video_fifo;
  if (audio_fifo) delete audio_fifo;
  if (dest_frame) av_frame_free(&dest_frame);
  if (convert_context) {
    sws_freeContext(convert_context);
    convert_context = nullptr;
  }
}  // end Monitor::~Monitor()

void Monitor::AddPrivacyBitmask() {
  if (privacy_bitmask) {
    delete[] privacy_bitmask;
    privacy_bitmask = nullptr;
  }
  Image *privacy_image = nullptr;

  for (const Zone &zone : zones) {
  //for (int i=0; i < zones.size(); i++) {
    if (zone.IsPrivacy()) {
      if (!privacy_image) {
        privacy_image = new Image(width, height, 1, ZM_SUBPIX_ORDER_NONE);
        privacy_image->Clear();
      }
      privacy_image->Fill(0xff, zone.GetPolygon());
      privacy_image->Outline(0xff, zone.GetPolygon());
    }
  } // end foreach zone
  if (privacy_image)
    privacy_bitmask = privacy_image->Buffer();
}

int Monitor::GetImage(int32_t index, int scale) {
  if (index < 0 || index > image_buffer_count) {
    Debug(1, "Invalid index %d passed. image_buffer_count = %d", index, image_buffer_count);
    index = shared_data->last_write_index;
  }
  if (!image_buffer.size() or static_cast<size_t>(index) >= image_buffer.size()) {
    Error("Image Buffer has not been allocated");
    return -1;
  }
  if ( index == image_buffer_count ) {
    Error("Unable to generate image, no images in buffer");
    return 0;
  }

  Image *image;
  // If we are going to be modifying the snapshot before writing, then we need to copy it
  if ((scale != ZM_SCALE_BASE) || (!config.timestamp_on_capture)) {
    alarm_image.Assign(*image_buffer[index]);

    if (scale != ZM_SCALE_BASE) {
      alarm_image.Scale(scale);
    }

    if (!config.timestamp_on_capture) {
      TimestampImage(&alarm_image, shared_timestamps[index]);
    }
    image = &alarm_image;
  } else {
    image = image_buffer[index];
  }

  static char filename[PATH_MAX];
  snprintf(filename, sizeof(filename), "Monitor%u.jpg", id);
  image->WriteJpeg(filename);
  return 1;
}

ZMPacket *Monitor::getSnapshot(int index) const {
  if ((index < 0) || (index >= image_buffer_count)) {
    index = shared_data->last_write_index;
  }
  if (!image_buffer.size() or static_cast<size_t>(index) >= image_buffer.size()) {
    Error("Image Buffer has not been allocated");
    return nullptr;
  }
  if (index != image_buffer_count) {
    return new ZMPacket(image_buffer[index], shared_timestamps[index]);
  } else {
    Error("Unable to generate image, no images in buffer");
  }
  return nullptr;
}

struct timeval Monitor::GetTimestamp(int index) const {
  ZMPacket *packet = getSnapshot(index);
  if (packet) 
    return packet->timestamp;

  static struct timeval null_tv = { 0, 0 };
  return null_tv;
}

unsigned int Monitor::GetLastReadIndex() const {
  return ( shared_data->last_read_index != image_buffer_count ? shared_data->last_read_index : -1 );
}

unsigned int Monitor::GetLastWriteIndex() const {
  return ( shared_data->last_write_index != image_buffer_count ? shared_data->last_write_index : -1 );
}

uint64_t Monitor::GetLastEventId() const {
  return shared_data->last_event_id;
}

// This function is crap.
double Monitor::GetFPS() const {
  return get_capture_fps();
}

/* I think this returns the # of micro seconds that we should sleep in order to maintain the desired analysis rate */
useconds_t Monitor::GetAnalysisRate() {
  double capture_fps = get_capture_fps();
  if ( !analysis_fps_limit ) {
    return 0;
  } else if ( analysis_fps_limit > capture_fps ) {
    if ( last_fps_time != last_analysis_fps_time ) {
      // At startup they are equal, should never be equal again
      Warning("Analysis fps (%.2f) is greater than capturing fps (%.2f)", analysis_fps_limit, capture_fps);
    }
    return 0;
  } else if ( capture_fps ) {
    return( ( 1000000 / analysis_fps_limit ) - ( 1000000 / capture_fps ) );
  }
  return 0;
}

void Monitor::UpdateAdaptiveSkip() {
  if ( config.opt_adaptive_skip ) {
    double capturing_fps = get_capture_fps();
    double analysis_fps = get_analysis_fps();
    if ( adaptive_skip && analysis_fps && ( analysis_fps < capturing_fps ) ) {
      Info("Analysis fps (%.2f) is lower than capturing fps (%.2f), disabling adaptive skip feature", analysis_fps, capturing_fps);
      adaptive_skip = false;
    } else if ( !adaptive_skip && ( !analysis_fps || ( analysis_fps >= capturing_fps ) ) ) {
      Info("Enabling adaptive skip feature");
      adaptive_skip = true;
    }
  } else {
    adaptive_skip = false;
  }
}

void Monitor::ForceAlarmOn( int force_score, const char *force_cause, const char *force_text ) {
  trigger_data->trigger_state = TriggerState::TRIGGER_ON;
  trigger_data->trigger_score = force_score;
  strncpy(trigger_data->trigger_cause, force_cause, sizeof(trigger_data->trigger_cause)-1);
  strncpy(trigger_data->trigger_text, force_text, sizeof(trigger_data->trigger_text)-1);
}

void Monitor::ForceAlarmOff() {
  trigger_data->trigger_state = TriggerState::TRIGGER_OFF;
}

void Monitor::CancelForced() {
  trigger_data->trigger_state = TriggerState::TRIGGER_CANCEL;
}

void Monitor::actionReload() {
  shared_data->action |= RELOAD;
}

void Monitor::actionEnable() {
  shared_data->action |= RELOAD;

  char sql[ZM_SQL_SML_BUFSIZ];
  snprintf(sql, sizeof(sql), "UPDATE `Monitors` SET `Enabled` = 1 WHERE `Id` = %u", id);
  zmDbDo(sql);
}

void Monitor::actionDisable() {
  shared_data->action |= RELOAD;

  char sql[ZM_SQL_SML_BUFSIZ];
  snprintf(sql, sizeof(sql), "UPDATE `Monitors` SET `Enabled` = 0 WHERE `Id` = %u", id);
  zmDbDo(sql);
}

void Monitor::actionSuspend() {
  shared_data->action |= SUSPEND;
}

void Monitor::actionResume() {
  shared_data->action |= RESUME;
}

int Monitor::actionBrightness(int p_brightness) {
  if ( purpose != CAPTURE ) {
    if ( p_brightness >= 0 ) {
      shared_data->brightness = p_brightness;
      shared_data->action |= SET_SETTINGS;
      int wait_loops = 10;
      while ( shared_data->action & SET_SETTINGS ) {
        if ( wait_loops-- ) {
          usleep(100000);
        } else {
          Warning("Timed out waiting to set brightness");
          return -1;
        }
      }
    } else {
      shared_data->action |= GET_SETTINGS;
      int wait_loops = 10;
      while ( shared_data->action & GET_SETTINGS ) {
        if ( wait_loops-- ) {
          usleep(100000);
        } else {
          Warning("Timed out waiting to get brightness");
          return -1;
        }
      }
    }
    return shared_data->brightness;
  }
  return camera->Brightness(p_brightness);
} // end int Monitor::actionBrightness(int p_brightness)

int Monitor::actionContrast(int p_contrast) {
  if ( purpose != CAPTURE ) {
    if ( p_contrast >= 0 ) {
      shared_data->contrast = p_contrast;
      shared_data->action |= SET_SETTINGS;
      int wait_loops = 10;
      while ( shared_data->action & SET_SETTINGS ) {
        if ( wait_loops-- ) {
          usleep(100000);
        } else {
          Warning("Timed out waiting to set contrast");
          return -1;
        }
      }
    } else {
      shared_data->action |= GET_SETTINGS;
      int wait_loops = 10;
      while ( shared_data->action & GET_SETTINGS ) {
        if ( wait_loops-- ) {
          usleep(100000);
        } else {
          Warning("Timed out waiting to get contrast");
          return -1;
        }
      }
    }
    return shared_data->contrast;
  }
  return camera->Contrast(p_contrast);
} // end int Monitor::actionContrast(int p_contrast)

int Monitor::actionHue(int p_hue) {
  if ( purpose != CAPTURE ) {
    if ( p_hue >= 0 ) {
      shared_data->hue = p_hue;
      shared_data->action |= SET_SETTINGS;
      int wait_loops = 10;
      while ( shared_data->action & SET_SETTINGS ) {
        if ( wait_loops-- ) {
          usleep(100000);
        } else {
          Warning("Timed out waiting to set hue");
          return -1;
        }
      }
    } else {
      shared_data->action |= GET_SETTINGS;
      int wait_loops = 10;
      while ( shared_data->action & GET_SETTINGS ) {
        if ( wait_loops-- ) {
          usleep(100000);
        } else {
          Warning("Timed out waiting to get hue");
          return -1;
        }
      }
    }
    return shared_data->hue;
  }
  return camera->Hue(p_hue);
} // end int Monitor::actionHue(int p_hue)

int Monitor::actionColour(int p_colour) {
  if ( purpose != CAPTURE ) {
    if ( p_colour >= 0 ) {
      shared_data->colour = p_colour;
      shared_data->action |= SET_SETTINGS;
      int wait_loops = 10;
      while ( shared_data->action & SET_SETTINGS ) {
        if ( wait_loops-- ) {
          usleep(100000);
        } else {
          Warning("Timed out waiting to set colour");
          return -1;
        }
      }
    } else {
      shared_data->action |= GET_SETTINGS;
      int wait_loops = 10;
      while ( shared_data->action & GET_SETTINGS ) {
        if ( wait_loops-- ) {
          usleep(100000);
        } else {
          Warning("Timed out waiting to get colour");
          return -1;
        }
      }
    }
    return shared_data->colour;
  }
  return camera->Colour(p_colour);
} // end int Monitor::actionColour(int p_colour)

void Monitor::DumpZoneImage(const char *zone_string) {
  int exclude_id = 0;
  int extra_colour = 0;
  Polygon extra_zone;

  if ( zone_string ) {
    if ( !Zone::ParseZoneString(zone_string, exclude_id, extra_colour, extra_zone) ) {
      Error("Failed to parse zone string, ignoring");
    }
  }

  Image *zone_image = nullptr;
  if ( ( (!staticConfig.SERVER_ID) || ( staticConfig.SERVER_ID == server_id ) ) && mem_ptr ) {
    Debug(3, "Trying to load from local zmc");
    int index = shared_data->last_write_index;
    ZMPacket *snap = getSnapshot(index);
    zone_image = new Image(*snap->image);
  } else {
    Debug(3, "Trying to load from event");
    // Grab the most revent event image
    std::string sql = stringtf("SELECT MAX(`Id`) FROM `Events` WHERE `MonitorId`=%d AND `Frames` > 0", id);
    zmDbRow eventid_row;
    if ( eventid_row.fetch(sql.c_str()) ) {
      uint64_t event_id = atoll(eventid_row[0]);

      Debug(3, "Got event %" PRIu64, event_id);
      EventStream *stream = new EventStream();
      stream->setStreamStart(event_id, (unsigned int)1);
      zone_image = stream->getImage();
      delete stream;
      stream = nullptr;
    } else {
      Error("Unable to load an event for monitor %d", id);
      return;
    }
  }

  if ( zone_image->Colours() == ZM_COLOUR_GRAY8 ) {
    zone_image->Colourise(ZM_COLOUR_RGB24, ZM_SUBPIX_ORDER_RGB);
  }

  extra_zone.Clip(Box(
      {0, 0},
      {static_cast<int32>(zone_image->Width()), static_cast<int32>(zone_image->Height())}
  ));

  for (const Zone &zone : zones) {
    if (exclude_id && (!extra_colour || !extra_zone.GetVertices().empty()) && zone.Id() == exclude_id) {
      continue;
    }

    Rgb colour;
    if (exclude_id && extra_zone.GetVertices().empty() && zone.Id() == exclude_id) {
      colour = extra_colour;
    } else {
      if (zone.IsActive()) {
        colour = kRGBRed;
      } else if (zone.IsInclusive()) {
        colour = kRGBOrange;
      } else if (zone.IsExclusive()) {
        colour = kRGBPurple;
      } else if (zone.IsPreclusive()) {
        colour = kRGBBlue;
      } else {
        colour = kRGBWhite;
      }
    }
    zone_image->Fill(colour, 2, zone.GetPolygon());
    zone_image->Outline(colour, zone.GetPolygon());
  }

  if (!extra_zone.GetVertices().empty()) {
    zone_image->Fill(extra_colour, 2, extra_zone);
    zone_image->Outline(extra_colour, extra_zone);
  }

  static char filename[PATH_MAX];
  snprintf(filename, sizeof(filename), "Zones%u.jpg", id);
  zone_image->WriteJpeg(filename);
  delete zone_image;
} // end void Monitor::DumpZoneImage(const char *zone_string)

void Monitor::DumpImage(Image *dump_image) const {
  if (shared_data->image_count && !(shared_data->image_count % 10)) {
    static char filename[PATH_MAX];
    static char new_filename[PATH_MAX];
    snprintf(filename, sizeof(filename), "Monitor%u.jpg", id);
    snprintf(new_filename, sizeof(new_filename), "Monitor%u-new.jpg", id);
    if ( dump_image->WriteJpeg(new_filename) )
      rename(new_filename, filename);
  }
} // end void Monitor::DumpImage(Image *dump_image)

bool Monitor::CheckSignal(const Image *image) {
  if (signal_check_points <= 0)
    return true;

  const uint8_t *buffer = image->Buffer();
  int pixels = image->Pixels();
  int width = image->Width();
  int colours = image->Colours();

  int index = 0;
  for (int i = 0; i < signal_check_points; i++) {
    while (true) {
      // Why the casting to long long? also note that on a 64bit cpu, long long is 128bits
      index = (int)(((long long)rand()*(long long)(pixels-1))/RAND_MAX);
      if (!config.timestamp_on_capture || !label_format[0])
        break;
      // Avoid sampling the rows with timestamp in
      if (
          index < (label_coord.y_ * width)
          ||
          index >= (label_coord.y_ + Image::LINE_HEIGHT) * width
          ) {
        break;
      }
    }

    if (colours == ZM_COLOUR_GRAY8) {
      if (*(buffer+index) != grayscale_val)
        return true;

    } else if (colours == ZM_COLOUR_RGB24) {
      const uint8_t *ptr = buffer+(index*colours);

      if (usedsubpixorder == ZM_SUBPIX_ORDER_BGR) {
        if ((RED_PTR_BGRA(ptr) != red_val) || (GREEN_PTR_BGRA(ptr) != green_val) || (BLUE_PTR_BGRA(ptr) != blue_val))
          return true;
      } else {
        /* Assume RGB */
        if ((RED_PTR_RGBA(ptr) != red_val) || (GREEN_PTR_RGBA(ptr) != green_val) || (BLUE_PTR_RGBA(ptr) != blue_val))
          return true;
      }

    } else if (colours == ZM_COLOUR_RGB32) {
      if (usedsubpixorder == ZM_SUBPIX_ORDER_ARGB || usedsubpixorder == ZM_SUBPIX_ORDER_ABGR) {
        if (ARGB_ABGR_ZEROALPHA(*(((const Rgb*)buffer)+index)) != ARGB_ABGR_ZEROALPHA(colour_val))
          return true;
      } else {
        /* Assume RGBA or BGRA */
        if (RGBA_BGRA_ZEROALPHA(*(((const Rgb*)buffer)+index)) != RGBA_BGRA_ZEROALPHA(colour_val))
          return true;
      }
    }
  } // end for < signal_check_points
  Debug(1, "SignalCheck: %d points, colour_val(%d)", signal_check_points, colour_val);
  return false;
} // end bool Monitor::CheckSignal(const Image *image)

void Monitor::CheckAction() {
  struct timeval now;
  gettimeofday(&now, nullptr);

  if ( shared_data->action ) {
    // Can there be more than 1 bit set in the action?  Shouldn't these be elseifs?
    if (shared_data->action & RELOAD) {
      Info("Received reload indication at count %d", shared_data->image_count);
      shared_data->action &= ~RELOAD;
      Reload();
    }
    if (shared_data->action & SUSPEND) {
      if (Active()) {
        Info("Received suspend indication at count %d", shared_data->image_count);
        shared_data->active = false;
      } else {
        Info("Received suspend indication at count %d, but wasn't active", shared_data->image_count);
      }
      if ( config.max_suspend_time ) {
        auto_resume_time = now.tv_sec + config.max_suspend_time;
      }
      shared_data->action &= ~SUSPEND;
    } else if ( shared_data->action & RESUME ) {
      if ( Enabled() && !Active() ) {
        Info("Received resume indication at count %d", shared_data->image_count);
        shared_data->active = true;
        ref_image.DumpImgBuffer(); // Will get re-assigned by analysis thread
        shared_data->alarm_x = shared_data->alarm_y = -1;
      }
      shared_data->action &= ~RESUME;
    }
  } // end if shared_data->action

  if ( auto_resume_time && (now.tv_sec >= auto_resume_time) ) {
    Info("Auto resuming at count %d", shared_data->image_count);
    shared_data->active = true;
    ref_image.DumpImgBuffer(); // Will get re-assigned by analysis thread
    auto_resume_time = 0;
  }
}

void Monitor::UpdateFPS() {
  struct timeval now;
  gettimeofday(&now, nullptr);
  double now_double = (double)now.tv_sec + (0.000001f * now.tv_usec);
  double elapsed = now_double - last_fps_time;

  // If we are too fast, we get div by zero. This seems to happen in the case of audio packets.
  // Also only do the update at most 1/sec
  if ( elapsed > 1.0 ) {
    // # of images per interval / the amount of time it took
    double new_capture_fps = (shared_data->image_count - last_capture_image_count) / elapsed;
    uint32 new_camera_bytes = camera->Bytes();
    uint32 new_capture_bandwidth =
      static_cast<uint32>((new_camera_bytes - last_camera_bytes) / elapsed);
    double new_analysis_fps = (motion_frame_count - last_motion_frame_count) / elapsed;

    Debug(4, "FPS: capture count %d - last capture count %d = %d now:%lf, last %lf, elapsed %lf = capture: %lf fps analysis: %lf fps",
        shared_data->image_count,
        last_capture_image_count,
        shared_data->image_count - last_capture_image_count,
        now_double,
        last_fps_time,
        elapsed,
        new_capture_fps,
        new_analysis_fps);

    if ( fps_report_interval and
        (
         !(shared_data->image_count%fps_report_interval)
         or
         ( (shared_data->image_count < fps_report_interval) and !(shared_data->image_count%10) )
        )
       ) {
      Info("%s: %d - Capturing at %.2lf fps, capturing bandwidth %ubytes/sec Analysing at %.2lf fps",
          name.c_str(), shared_data->image_count, new_capture_fps, new_capture_bandwidth, new_analysis_fps);
    } // end if report fps

    shared_data->capture_fps = new_capture_fps;
    last_capture_image_count = shared_data->image_count;
    shared_data->analysis_fps = new_analysis_fps;
    last_motion_frame_count = motion_frame_count;
    last_camera_bytes = new_camera_bytes;

    std::string sql = stringtf(
        "UPDATE LOW_PRIORITY Monitor_Status SET CaptureFPS = %.2lf, CaptureBandwidth=%u, AnalysisFPS = %.2lf, UpdatedOn=NOW() WHERE MonitorId=%u",
        new_capture_fps, new_capture_bandwidth, new_analysis_fps, id);
    dbQueue.push(std::move(sql));
    last_fps_time = now_double;
  } // now != last_fps_time
}  // void Monitor::UpdateFPS()

// Would be nice if this JUST did analysis
// This idea is that we should be analysing as close to the capture frame as possible.
// This function should process as much as possible before returning
//
// If there is an event, the we should do our best to empty the queue.
// If there isn't then we keep pre-event + alarm frames. = pre_event_count
bool Monitor::Analyse() {
  // if have event, send frames until we find a video packet, at which point do analysis. Adaptive skip should only affect which frames we do analysis on.

  // get_analysis_packet will lock the packet and may wait if analysis_it is at the end
  ZMLockedPacket *packet_lock = packetqueue.get_packet(analysis_it);
  if (!packet_lock) return false;
  std::shared_ptr<ZMPacket> snap = packet_lock->packet_;

  // Is it possible for snap->score to be ! -1 ? Not if everything is working correctly
  if (snap->score != -1) {
    Error("skipping because score was %d at index %d", snap->score, snap->image_index);
    delete packet_lock;
    packetqueue.increment_it(analysis_it);
    return false;
  }

  // signal is set by capture
  bool signal = shared_data->signal;
  bool signal_change = (signal != last_signal);

  Debug(3, "Motion detection is enabled signal(%d) signal_change(%d) trigger state(%s) image index %d",
      signal, signal_change, TriggerState_Strings[trigger_data->trigger_state].c_str(), snap->image_index);

  {
    // Need to guard around event creation/deletion from Reload()
    std::lock_guard<std::mutex> lck(event_mutex);
    Debug(3, "Have event lock");

    // if we have been told to be OFF, then we are off and don't do any processing.
    if (trigger_data->trigger_state != TriggerState::TRIGGER_OFF) {
      Debug(4, "Trigger not OFF state is (%d)", int(trigger_data->trigger_state));
      int score = 0;
      // Ready means that we have captured the warmup # of frames
      if (!Ready()) {
        Debug(3, "Not ready?");
        delete packet_lock;
        return false;
      }

      std::string cause;
      Event::StringSetMap noteSetMap;

      // Specifically told to be on.  Setting the score here will trigger the alarm.
      if (trigger_data->trigger_state == TriggerState::TRIGGER_ON) {
        score += trigger_data->trigger_score;
        Debug(1, "Triggered on score += %d => %d", trigger_data->trigger_score, score);
        if (!event) {
          cause += trigger_data->trigger_cause;
        }
        Event::StringSet noteSet;
        noteSet.insert(trigger_data->trigger_text);
        noteSetMap[trigger_data->trigger_cause] = noteSet;
      }  // end if trigger_on

      // FIXME this snap might not be the one that caused the signal change.  Need to store that in the packet.
      if (signal_change) {
        Debug(2, "Signal change, new signal is %d", signal);
        const char *signalText = "Unknown";
        if (!signal) {
          signalText = "Lost";
          if (event) {
            Info("%s: %03d - Closing event %" PRIu64 ", signal loss", name.c_str(), analysis_image_count, event->Id());
            closeEvent();
            last_section_mod = 0;
          }
        } else {
          signalText = "Reacquired";
          score += 100;
        }
        if (!event) {
          if (cause.length()) cause += ", ";
          cause += SIGNAL_CAUSE;
        }
        Event::StringSet noteSet;
        noteSet.insert(signalText);
        noteSetMap[SIGNAL_CAUSE] = noteSet;
        shared_data->state = state = IDLE;
        shared_data->active = signal;
        if ((function == MODECT or function == MOCORD) and snap->image)
          ref_image.Assign(*(snap->image));
      }  // end if signal change

      if (signal) {
        if (snap->codec_type == AVMEDIA_TYPE_VIDEO) {
          // Check to see if linked monitors are triggering.
          if (n_linked_monitors > 0) {
            Debug(1, "Checking linked monitors");
            // FIXME improve logic here
            bool first_link = true;
            Event::StringSet noteSet;
            for (int i = 0; i < n_linked_monitors; i++) {
              // TODO: Shouldn't we try to connect?
              if (linked_monitors[i]->isConnected()) {
                Debug(1, "Linked monitor %d %s is connected",
                    linked_monitors[i]->Id(), linked_monitors[i]->Name());
                if (linked_monitors[i]->hasAlarmed()) {
                  Debug(1, "Linked monitor %d %s is alarmed",
                      linked_monitors[i]->Id(), linked_monitors[i]->Name());
                  if (!event) {
                    if (first_link) {
                      if (cause.length())
                        cause += ", ";
                      cause += LINKED_CAUSE;
                      first_link = false;
                    }
                  }
                  noteSet.insert(linked_monitors[i]->Name());
                  score += linked_monitors[i]->lastFrameScore(); // 50;
                } else {
                  Debug(1, "Linked monitor %d %s is not alarmed",
                      linked_monitors[i]->Id(), linked_monitors[i]->Name());
                }
              } else {
                Debug(1, "Linked monitor %d %d is not connected. Connecting.", i, linked_monitors[i]->Id());
                linked_monitors[i]->connect();
              }
            } // end foreach linked_monitor
            if (noteSet.size() > 0)
              noteSetMap[LINKED_CAUSE] = noteSet;
          } // end if linked_monitors

          /* try to stay behind the decoder. */
          if (decoding_enabled) {
            if (!snap->decoded) {
              // We no longer wait because we need to be checking the triggers and other inputs.
              // Also the logic is too hairy.  capture process can delete the packet that we have here.
              delete packet_lock;
              return false;
            }
          }  // end if decoding enabled

          struct timeval *timestamp = &snap->timestamp;

          if (Active() and (function == MODECT or function == MOCORD)) {
            Debug(3, "signal and active and modect");
            Event::StringSet zoneSet;

            int motion_score = last_motion_score;

            if (analysis_fps_limit) {
              double capture_fps = get_capture_fps();
              motion_frame_skip = capture_fps / analysis_fps_limit;
              Debug(1, "Recalculating motion_frame_skip (%d) = capture_fps(%f) / analysis_fps(%f)",
                  motion_frame_skip, capture_fps, analysis_fps_limit);
            }

            if (!(analysis_image_count % (motion_frame_skip+1))) {
              if (snap->image) {
                // decoder may not have been able to provide an image
                if (!ref_image.Buffer()) {
                  Debug(1, "Assigning instead of Detecting");
                  ref_image.Assign(*(snap->image));
                } else {
                  Debug(1, "Detecting motion on image %d, image %p", snap->image_index, snap->image);
                  // Get new score.
                  motion_score = DetectMotion(*(snap->image), zoneSet);

                  snap->zone_stats.reserve(zones.size());
                  for (const Zone &zone : zones) {
                    const ZoneStats &stats = zone.GetStats();
                    stats.DumpToLog("After detect motion");
                    snap->zone_stats.push_back(stats);
                  }

                  Debug(3, "After motion detection, score:%d last_motion_score(%d), new motion score(%d)",
                      score, last_motion_score, motion_score);
                  motion_frame_count += 1;
                  // Why are we updating the last_motion_score too?
                  last_motion_score = motion_score;
                }
              } else {
                Debug(1, "no image so skipping motion detection");
              }  // end if has image
            } else {
              Debug(1, "Skipped motion detection last motion score was %d", motion_score);
            }
            if (motion_score) {
              score += motion_score;
              if (cause.length()) cause += ", ";
              cause += MOTION_CAUSE;
              noteSetMap[MOTION_CAUSE] = zoneSet;
            } // end if motion_score
          } else {
            Debug(1, "Not Active(%d) enabled %d active %d doing motion detection: %d",
                Active(), enabled, shared_data->active,
                (function == MODECT or function == MOCORD)
                );
          } // end if active and doing motion detection

          if (function == RECORD or function == MOCORD) {
            // If doing record, check to see if we need to close the event or not.
            if (event) {
              Debug(2, "Have event %" PRIu64 " in record", event->Id());

              if (section_length && 
                  (( timestamp->tv_sec - video_store_data->recording.tv_sec ) >= section_length)
                  && ( 
                    ((function == MOCORD) && (event_close_mode != CLOSE_TIME))
                    ||
                    ( (function == RECORD) && (event_close_mode == CLOSE_TIME) )  
                    || ! ( timestamp->tv_sec % section_length )
                    )
                 ) {
                Info("%s: %03d - Closing event %" PRIu64 ", section end forced %" PRIi64 " - %" PRIi64 " = %" PRIi64 " >= %d",
                    name.c_str(),
                    shared_data->image_count,
                    event->Id(),
                    static_cast<int64>(timestamp->tv_sec),
                    static_cast<int64>(video_store_data->recording.tv_sec),
                    static_cast<int64>(timestamp->tv_sec - video_store_data->recording.tv_sec),
                    section_length);
                closeEvent();
              }  // end if section_length
            }  // end if event

            if (!event) {
              Debug(2, "Creating continuous event");
              if ((event = openEvent(snap, cause.empty() ? "Continuous" : cause, noteSetMap)) != nullptr) {
                // lets construct alarm cause. It will contain cause + names of zones alarmed
                std::string alarm_cause;
                for (const Zone &zone : zones) {
                  if (zone.Alarmed()) {
                    if (!alarm_cause.empty()) alarm_cause += ",";
                    alarm_cause += std::string(zone.Label());
                  }
                }
                alarm_cause = cause+" Continuous "+alarm_cause;
                strncpy(shared_data->alarm_cause, alarm_cause.c_str(), sizeof(shared_data->alarm_cause)-1);
                video_store_data->recording = event->StartTime();
                Info("%s: %03d - Opened new event %" PRIu64 ", section start",
                    name.c_str(), analysis_image_count, event->Id());
                /* To prevent cancelling out an existing alert\prealarm\alarm state */
                if (state == IDLE) {
                  shared_data->state = state = TAPE;
                }
              } // end if event success
            } // end if ! event
          } // end if RECORDING

          if (score and (function != MONITOR)) {
            if ((state == IDLE) || (state == TAPE) || (state == PREALARM)) {
              // If we should end then previous continuous event and start a new non-continuous event
              if (event && event->Frames()
                  && (!event->AlarmFrames())
                  && (event_close_mode == CLOSE_ALARM)
                  && ( ( timestamp->tv_sec - video_store_data->recording.tv_sec ) >= min_section_length )
                  && ( (!pre_event_count) || (Event::PreAlarmCount() >= alarm_frame_count-1) )
                 ) {
                Info("%s: %03d - Closing event %" PRIu64 ", continuous end, alarm begins",
                    name.c_str(), shared_data->image_count, event->Id());
                closeEvent();
              } else if (event) {
                // This is so if we need more than 1 alarm frame before going into alarm, so it is basically if we have enough alarm frames
                Debug(3,
                    "pre_alarm_count in event %d, event frames %d, alarm frames %d event length %" PRIi64 " >=? %d min",
                    Event::PreAlarmCount(),
                    event->Frames(),
                    event->AlarmFrames(),
                    static_cast<int64>(timestamp->tv_sec - video_store_data->recording.tv_sec),
                    min_section_length);
              }
              if ((!pre_event_count) || (Event::PreAlarmCount() >= alarm_frame_count-1)) {
                // lets construct alarm cause. It will contain cause + names of zones alarmed
                std::string alarm_cause = "";
                for (const Zone &zone : zones) {
                  if (zone.Alarmed()) {
                    alarm_cause = alarm_cause + "," + std::string(zone.Label());
                  }
                }
                if (!alarm_cause.empty()) alarm_cause[0] = ' ';
                alarm_cause = cause + alarm_cause;
                strncpy(shared_data->alarm_cause, alarm_cause.c_str(), sizeof(shared_data->alarm_cause)-1);
                Info("%s: %03d - Gone into alarm state PreAlarmCount: %u > AlarmFrameCount:%u Cause:%s",
                    name.c_str(), shared_data->image_count, Event::PreAlarmCount(), alarm_frame_count, shared_data->alarm_cause);

                if (!event) {
                  if ((event = openEvent(snap, cause, noteSetMap)) != nullptr) {
                    snprintf(video_store_data->event_file, sizeof(video_store_data->event_file), "%s", event->getEventFile());
                    video_store_data->recording = event->StartTime();
                    Info("%s: %03d - Opening new event %" PRIu64 ", alarm start", name.c_str(), analysis_image_count, event->Id());
                  }
                }  // end if no event, so start it
                shared_data->state = state = ALARM;
                if ( alarm_frame_count ) {
                  Debug(1, "alarm frame count so SavePreAlarmFrames");
                  event->SavePreAlarmFrames();
                }
              } else if (state != PREALARM) {
                Info("%s: %03d - Gone into prealarm state", name.c_str(), analysis_image_count);
                shared_data->state = state = PREALARM;
              }
            } else if (state == ALERT) {
              alert_to_alarm_frame_count--;
              Debug(1, "%s: %03d - Alarmed frame while in alert state. Consecutive alarmed frames left to return to alarm state: %03d",
                  name.c_str(), analysis_image_count, alert_to_alarm_frame_count);
              if (alert_to_alarm_frame_count == 0) {
                Info("%s: %03d - Gone back into alarm state", name.c_str(), analysis_image_count);
                shared_data->state = state = ALARM;
              }
            } else if (state == TAPE) {
              // Already recording, but IDLE so switch to ALARM
              shared_data->state = state = ALARM;
              Debug(1, "Was in TAPE, going into ALARM");
            } else {
              Debug(1, "Staying in %s", State_Strings[state].c_str());
            }
            if (state == ALARM) {
              last_alarm_count = analysis_image_count; 
            } // This is needed so post_event_count counts after last alarmed frames while in ALARM not single alarmed frames while ALERT
          } else { // no score?
            alert_to_alarm_frame_count = alarm_frame_count; // load same value configured for alarm_frame_count 
            if (state == ALARM) {
              Info("%s: %03d - Gone into alert state", name.c_str(), analysis_image_count);
              shared_data->state = state = ALERT;
            } else if (state == ALERT) {
              if ( 
                  ( analysis_image_count-last_alarm_count > post_event_count ) 
                  &&
                  ( ( timestamp->tv_sec - video_store_data->recording.tv_sec ) >= min_section_length )
                 ) {
                Info("%s: %03d - Left alarm state (%" PRIu64 ") - %d(%d) images",
                    name.c_str(), analysis_image_count, event->Id(), event->Frames(), event->AlarmFrames());
                //if ( function != MOCORD || event_close_mode == CLOSE_ALARM || event->Cause() == SIGNAL_CAUSE )
                if ( (function != RECORD && function != MOCORD ) || event_close_mode == CLOSE_ALARM ) {
                  shared_data->state = state = IDLE;
                  Info("%s: %03d - Closing event %" PRIu64 ", alarm end%s",
                      name.c_str(), analysis_image_count, event->Id(), (function==MOCORD)?", section truncated":"" );
                  closeEvent();
                } else {
                  shared_data->state = state = TAPE;
                }
              }
            } else if (state == PREALARM) {
              // Back to IDLE
              shared_data->state = state = ((function != MOCORD) ? IDLE : TAPE);
            } else {
              Debug(1,
                  "State %s because image_count(%d)-last_alarm_count(%d) > post_event_count(%d) and timestamp.tv_sec(%" PRIi64 ") - recording.tv_src(%" PRIi64 ") >= min_section_length(%d)",
                  State_Strings[state].c_str(),
                  analysis_image_count,
                  last_alarm_count,
                  post_event_count,
                  static_cast<int64>(timestamp->tv_sec),
                  static_cast<int64>(video_store_data->recording.tv_sec),
                  min_section_length);
            }
            if (Event::PreAlarmCount())
              Event::EmptyPreAlarmFrames();
          } // end if score or not

          snap->score = score;

          if (state == PREALARM) {
            // Generate analysis images if necessary
            if ((savejpegs > 1) and snap->image) {
              for (const Zone &zone : zones) {
                if (zone.Alarmed()) {
                  if (zone.AlarmImage()) {
                    if (!snap->analysis_image)
                      snap->analysis_image = new Image(*(snap->image));
                    snap->analysis_image->Overlay(*(zone.AlarmImage()));
                  }
                } // end if zone is alarmed
              } // end foreach zone
            } // end if savejpegs

            // incremement pre alarm image count
            //have_pre_alarmed_frames ++;
            Event::AddPreAlarmFrame(snap->image, *timestamp, score, nullptr);
          } else if (state == ALARM) {
            for (const Zone &zone : zones) {
              if (zone.Alarmed()) {
                if (zone.AlarmImage() and (savejpegs > 1) and snap->image) {
                  if (!snap->analysis_image)
                    snap->analysis_image = new Image(*(snap->image));
                  snap->analysis_image->Overlay(*(zone.AlarmImage()));
                }
              }  // end if zone is alarmed
            }  // end foreach zone
            if (event) {
              if (noteSetMap.size() > 0)
                event->updateNotes(noteSetMap);
              if ( section_length
                  && ( ( timestamp->tv_sec - video_store_data->recording.tv_sec ) >= section_length )
                 ) {
                if (event_close_mode != CLOSE_TIME) {
                  Warning("%s: %03d - event %" PRIu64 ", has exceeded desired section length. %" PRIi64 " - %" PRIi64 " = %" PRIi64 " >= %d",
                      name.c_str(), analysis_image_count, event->Id(),
                      static_cast<int64>(timestamp->tv_sec), static_cast<int64>(video_store_data->recording.tv_sec),
                      static_cast<int64>(timestamp->tv_sec - video_store_data->recording.tv_sec),
                      section_length);
                }
                closeEvent();
                event = openEvent(snap, cause, noteSetMap);
                shared_data->last_event_id = event->Id();
                //set up video store data
                snprintf(video_store_data->event_file, sizeof(video_store_data->event_file), "%s", event->getEventFile());
                video_store_data->recording = event->StartTime();
              }
            } else {
              Error("ALARM but no event");
            }
          } else if ( state == ALERT ) {
            // Alert means this frame has no motion, but we were alarmed and are still recording.
            if ((noteSetMap.size() > 0) and event)
              event->updateNotes(noteSetMap);
          } else if ( state == TAPE ) {
            // bulk frame code moved to event.
          } // end if state machine

          if ( (function == MODECT or function == MOCORD) and snap->image ) {
            if (!ref_image.Buffer()) {
              Debug(1, "Assigning");
              ref_image.Assign(*(snap->image));
            } else {
              Debug(1, "Blending");
              ref_image.Blend(*(snap->image), ( state==ALARM ? alarm_ref_blend_perc : ref_blend_perc ));
              Debug(1, "Done Blending");
            }
          }
          last_signal = signal;
        } // end if videostream
      } // end if signal
      shared_data->last_frame_score = score;
    } else {
      Debug(3, "trigger == off");
      if (event) {
        Info("%s: %03d - Closing event %" PRIu64 ", trigger off", name.c_str(), analysis_image_count, event->Id());
        closeEvent();
      }
      shared_data->state = state = IDLE;
    } // end if ( trigger_data->trigger_state != TRIGGER_OFF )

    packetqueue.clearPackets(snap);

    if (snap->codec_type == AVMEDIA_TYPE_VIDEO) {
      // Only do these if it's a video packet.
      shared_data->last_read_index = snap->image_index;
      analysis_image_count++;
    }

    if (!event) {
      // In the case where people have pre-alarm frames, the web ui will generate the frame images
      // from the mp4. So no one will notice anyways.
      if (snap->image) {
        if (videowriter == PASSTHROUGH) {
          if (!savejpegs) {
            Debug(1, "Deleting image data for %d", snap->image_index);
            // Don't need raw images anymore
            delete snap->image;
            snap->image = nullptr;
          }
        }
        if (snap->analysis_image and !(savejpegs & 2)) {
          Debug(1, "Deleting analysis image data for %d", snap->image_index);
          delete snap->analysis_image;
          snap->analysis_image = nullptr;
        }
      }
    }
    // Free up the decoded frame as well, we won't be using it for anything at this time.
    if (snap->out_frame) av_frame_free(&snap->out_frame);
    if (snap->buffer) av_freep(&snap->buffer);

    packetqueue.unlock(packet_lock);
  } // end scope for event_lock

  packetqueue.increment_it(analysis_it);
  shared_data->last_read_time = std::chrono::system_clock::to_time_t(std::chrono::system_clock::now());

  return true;
} // end Monitor::Analyse

void Monitor::Reload() {
  Debug(1, "Reloading monitor %s", name.c_str());

  // Access to the event needs to be protected.  Either thread could call Reload.  Either thread could close the event. 
  // Need a mutex on it I guess.  FIXME
  // Need to guard around event creation/deletion This will prevent event creation until new settings are loaded
  {
    std::lock_guard<std::mutex> lck(event_mutex);
    if (event) {
      Info("%s: %03d - Closing event %" PRIu64 ", reloading", name.c_str(), shared_data->image_count, event->Id());
      closeEvent();
    }
  }

  std::string sql = load_monitor_sql + stringtf(" WHERE Id=%d", id);
  zmDbRow *row = zmDbFetchOne(sql.c_str());
  if (!row) {
    Error("Can't run query: %s", mysql_error(&dbconn));
  } else if (MYSQL_ROW dbrow = row->mysql_row()) {
    Load(dbrow, true /*load zones */, purpose);

    delete row;
  }  // end if row

}  // end void Monitor::Reload()

void Monitor::ReloadZones() {
  Debug(3, "Reloading zones for monitor %s have %zu", name.c_str(), zones.size());
  zones = Zone::Load(this);
  Debug(1, "Reloading zones for monitor %s have %zu", name.c_str(), zones.size());
  this->AddPrivacyBitmask();
  //DumpZoneImage();
} // end void Monitor::ReloadZones()

void Monitor::ReloadLinkedMonitors(const char *p_linked_monitors) {
  Debug(1, "Reloading linked monitors for monitor %s, '%s'", name.c_str(), p_linked_monitors);
  if ( n_linked_monitors ) {
    for ( int i=0; i < n_linked_monitors; i++ ) {
      delete linked_monitors[i];
    }
    delete[] linked_monitors;
    linked_monitors = nullptr;
  }

  n_linked_monitors = 0;
  if ( p_linked_monitors ) {
    int n_link_ids = 0;
    unsigned int link_ids[256];

    // This nasty code picks out strings of digits from p_linked_monitors and tries to load them. 
    char link_id_str[8];
    char *dest_ptr = link_id_str;
    const char *src_ptr = p_linked_monitors;
    while ( 1 ) {
      dest_ptr = link_id_str;
      while ( *src_ptr >= '0' && *src_ptr <= '9' ) {
        if ( (dest_ptr-link_id_str) < (unsigned int)(sizeof(link_id_str)-1) ) {
          *dest_ptr++ = *src_ptr++;
        } else {
          break;
        }
      }
      // Add the link monitor
      if ( dest_ptr != link_id_str ) {
        *dest_ptr = '\0';
        unsigned int link_id = atoi(link_id_str);
        if ( link_id > 0 && link_id != id ) {
          Debug(3, "Found linked monitor id %d", link_id);
          int j;
          for ( j = 0; j < n_link_ids; j++ ) {
            if ( link_ids[j] == link_id )
              break;
          }
          if ( j == n_link_ids ) {
            // Not already found
            link_ids[n_link_ids++] = link_id;
          }
        }
      }
      if ( !*src_ptr )
        break;
      while( *src_ptr && (*src_ptr < '0' || *src_ptr > '9') )
        src_ptr++;
      if ( !*src_ptr )
        break;
    }
    if ( n_link_ids > 0 ) {
      Debug(1, "Linking to %d monitors", n_link_ids);
      linked_monitors = new MonitorLink *[n_link_ids];
      int count = 0;
      for ( int i = 0; i < n_link_ids; i++ ) {
        Debug(1, "Checking linked monitor %d", link_ids[i]);

        std::string sql = stringtf(
            "SELECT `Id`, `Name` FROM `Monitors`"
            "  WHERE `Id` = %d"
            "   AND `Function` != 'None'"
            "   AND `Function` != 'Monitor'"
            "   AND `Enabled`=1",
            link_ids[i]);

        MYSQL_RES *result = zmDbFetch(sql.c_str());
        if (!result) {
          continue;
        }

        int n_monitors = mysql_num_rows(result);
        if ( n_monitors == 1 ) {
          MYSQL_ROW dbrow = mysql_fetch_row(result);
          Debug(1, "Linking to monitor %d %s", atoi(dbrow[0]), dbrow[1]);
          linked_monitors[count++] = new MonitorLink(link_ids[i], dbrow[1]);
        } else {
          Warning("Can't link to monitor %d, invalid id, function or not enabled", link_ids[i]);
        }
        mysql_free_result(result);
      }  // end foreach link_id
      n_linked_monitors = count;
    }  // end if has link_ids
  }  // end if p_linked_monitors
}  // end void Monitor::ReloadLinkedMonitors(const char *p_linked_monitors)

std::vector<std::shared_ptr<Monitor>> Monitor::LoadMonitors(const std::string &where, Purpose purpose) {
  std::string sql = load_monitor_sql + " WHERE " + where;
  Debug(1, "Loading Monitors with %s", sql.c_str());

  MYSQL_RES *result = zmDbFetch(sql.c_str());
  if (!result) {
    Error("Can't load local monitors: %s", mysql_error(&dbconn));
    return {};
  }
  int n_monitors = mysql_num_rows(result);
  Debug(1, "Got %d monitors", n_monitors);

  std::vector<std::shared_ptr<Monitor>> monitors;
  monitors.reserve(n_monitors);

  for (int i = 0; MYSQL_ROW dbrow = mysql_fetch_row(result); i++) {
    monitors.emplace_back(std::make_shared<Monitor>());
    monitors.back()->Load(dbrow, true, purpose);
  }

  if (mysql_errno(&dbconn)) {
    Error("Can't fetch row: %s", mysql_error(&dbconn));
    mysql_free_result(result);
    return {};
  }
  mysql_free_result(result);

  return monitors;
}

#if ZM_HAS_V4L
std::vector<std::shared_ptr<Monitor>> Monitor::LoadLocalMonitors
(const char *device, Purpose purpose) {

  std::string where = "`Function` != 'None' AND `Type` = 'Local'";

  if ( device[0] )
    where += " AND `Device`='" + std::string(device) + "'";
  if (staticConfig.SERVER_ID)
    where += stringtf(" AND `ServerId`=%d", staticConfig.SERVER_ID);
  return LoadMonitors(where, purpose);
}
#endif // ZM_HAS_V4L

std::vector<std::shared_ptr<Monitor>> Monitor::LoadRemoteMonitors
(const char *protocol, const char *host, const char *port, const char *path, Purpose purpose) {
  std::string where = "`Function` != 'None' AND `Type` = 'Remote'";
  if (staticConfig.SERVER_ID)
    where += stringtf(" AND `ServerId`=%d", staticConfig.SERVER_ID);
  if (protocol)
    where += stringtf(" AND `Protocol` = '%s' AND `Host` = '%s' AND `Port` = '%s' AND `Path` = '%s'", protocol, host, port, path);
  return LoadMonitors(where, purpose);
}

std::vector<std::shared_ptr<Monitor>> Monitor::LoadFileMonitors(const char *file, Purpose purpose) {
  std::string where = "`Function` != 'None' AND `Type` = 'File'";
  if (file[0])
    where += " AND `Path`='" + std::string(file) + "'";
  if (staticConfig.SERVER_ID)
    where += stringtf(" AND `ServerId`=%d", staticConfig.SERVER_ID);
  return LoadMonitors(where, purpose);
}

#if HAVE_LIBAVFORMAT
std::vector<std::shared_ptr<Monitor>> Monitor::LoadFfmpegMonitors(const char *file, Purpose purpose) {
  std::string where = "`Function` != 'None' AND `Type` = 'Ffmpeg'";
  if (file[0])
    where += " AND `Path` = '" + std::string(file) + "'";
  if (staticConfig.SERVER_ID)
    where += stringtf(" AND `ServerId`=%d", staticConfig.SERVER_ID);
  return LoadMonitors(where, purpose);
}
#endif // HAVE_LIBAVFORMAT

/* Returns 0 on success, even if no new images are available (transient error)
 * Returns -1 on failure.
 */
int Monitor::Capture() {
  unsigned int index = shared_data->image_count % image_buffer_count;
  if (image_buffer.empty() or (index >= image_buffer.size())) {
    Error("Image Buffer is invalid. Check ImageBufferCount. size is %zu", image_buffer.size());
    return -1;
  }

  std::shared_ptr<ZMPacket> packet = std::make_shared<ZMPacket>();
  packet->image_index = shared_data->image_count;
  gettimeofday(&(packet->timestamp), nullptr);
  shared_data->zmc_heartbeat_time = packet->timestamp.tv_sec;
  int captureResult = camera->Capture(packet);
  Debug(4, "Back from capture result=%d image count %d", captureResult, shared_data->image_count);

  if (captureResult < 0) {
    // Unable to capture image
    // Fake a signal loss image
    // Not sure what to do here.  We will close monitor and kill analysis_thread but what about rtsp server?
    Rgb signalcolor;
    /* HTML colour code is actually BGR in memory, we want RGB */
    signalcolor = rgb_convert(signal_check_colour, ZM_SUBPIX_ORDER_BGR);
    Image *capture_image = new Image(width, height, camera->Colours(), camera->SubpixelOrder());
    capture_image->Fill(signalcolor);
    shared_data->signal = false;
    shared_data->last_write_index = index;
    shared_data->last_write_time = shared_timestamps[index].tv_sec;
    image_buffer[index]->Assign(*capture_image);
    shared_timestamps[index] = packet->timestamp;
    delete capture_image;
    shared_data->image_count++;
    // What about timestamping it?
    // Don't want to do analysis on it, but we won't due to signal
    return -1;
  } else if (captureResult > 0) {
    shared_data->signal = true;   // Assume if getting packets that we are getting something useful. CheckSignalPoints can correct this later.
    // If we captured, let's assume signal, Decode will detect further
    if (!decoding_enabled) {
      shared_data->last_write_index = index;
      shared_data->last_write_time = packet->timestamp.tv_sec;
      packet->decoded = true;
    }
    Debug(2, "Have packet stream_index:%d ?= videostream_id: %d q.vpktcount %d event? %d image_count %d",
        packet->packet.stream_index, video_stream_id, packetqueue.packet_count(video_stream_id), ( event ? 1 : 0 ), shared_data->image_count);

    if (packet->codec_type == AVMEDIA_TYPE_VIDEO) {
      packet->packet.stream_index = video_stream_id; // Convert to packetQueue's index
      if (video_fifo) {
        if ( packet->keyframe ) {
          // avcodec strips out important nals that describe the stream and
          // stick them in extradata. Need to send them along with keyframes
#if LIBAVCODEC_VERSION_CHECK(57, 64, 0, 64, 0)
          AVStream *stream = camera->getVideoStream();
          video_fifo->write(
              static_cast<unsigned char *>(stream->codecpar->extradata),
              stream->codecpar->extradata_size,
              packet->pts);
#endif
        }
        video_fifo->writePacket(*packet);
      }
    } else if (packet->codec_type == AVMEDIA_TYPE_AUDIO) {
      if (audio_fifo)
        audio_fifo->writePacket(*packet);

      // Only queue if we have some video packets in there. Should push this logic into packetqueue
      if (record_audio and (packetqueue.packet_count(video_stream_id) or event)) {
        packet->image_index=-1;
        Debug(2, "Queueing audio packet");
        packet->packet.stream_index = audio_stream_id; // Convert to packetQueue's index
        packetqueue.queuePacket(packet);
      } else {
        Debug(4, "Not Queueing audio packet");
      }
      // Don't update last_write_index because that is used for live streaming
      //shared_data->last_write_time = image_buffer[index].timestamp->tv_sec;
      return 1;
    } else {
      Debug(1, "Unknown codec type %d", packet->codec_type);
      return 1;
    } // end if audio

    shared_data->image_count++;

    // Will only be queued if there are iterators allocated in the queue.
    packetqueue.queuePacket(packet);
  } else { // result == 0
    // Question is, do we update last_write_index etc?
    return 0;
  } // end if result

  // Icon: I'm not sure these should be here. They have nothing to do with capturing
  if ( shared_data->action & GET_SETTINGS ) {
    shared_data->brightness = camera->Brightness();
    shared_data->hue = camera->Hue();
    shared_data->colour = camera->Colour();
    shared_data->contrast = camera->Contrast();
    shared_data->action &= ~GET_SETTINGS;
  }
  if ( shared_data->action & SET_SETTINGS ) {
    camera->Brightness(shared_data->brightness);
    camera->Hue(shared_data->hue);
    camera->Colour(shared_data->colour);
    camera->Contrast(shared_data->contrast);
    shared_data->action &= ~SET_SETTINGS;
  }
  return captureResult;
} // end Monitor::Capture

bool Monitor::Decode() {
  ZMLockedPacket *packet_lock = packetqueue.get_packet_and_increment_it(decoder_it);
  if (!packet_lock) return false;
  std::shared_ptr<ZMPacket> packet = packet_lock->packet_;
  if (packet->codec_type != AVMEDIA_TYPE_VIDEO) {
    packet->decoded = true;
    Debug(4, "Not video");
    delete packet_lock;
    return true; // Don't need decode
  }

  if ((!packet->image) and packet->packet.size and !packet->in_frame) {
    // Allocate the image first so that it can be used by hwaccel
    // We don't actually care about camera colours, pixel order etc.  We care about the desired settings
    //
    //capture_image = packet->image = new Image(width, height, camera->Colours(), camera->SubpixelOrder());
    int ret = packet->decode(camera->getVideoCodecContext());
    if (ret > 0 and !zm_terminate) {
      if (packet->in_frame and !packet->image) {
        packet->image = new Image(camera_width, camera_height, camera->Colours(), camera->SubpixelOrder());
        AVFrame *input_frame = packet->in_frame;
        if (!dest_frame) dest_frame = zm_av_frame_alloc();

        if (!convert_context) {
          AVPixelFormat imagePixFormat = (AVPixelFormat)(packet->image->AVPixFormat());

          convert_context = sws_getContext(
              input_frame->width,
              input_frame->height,
              (AVPixelFormat)input_frame->format,
              camera_width, camera_height,
              imagePixFormat, SWS_BICUBIC,
              nullptr, nullptr, nullptr);
          if (convert_context == nullptr) {
            Error("Unable to create conversion context from %s to %s",
                av_get_pix_fmt_name((AVPixelFormat)input_frame->format),
                av_get_pix_fmt_name(imagePixFormat)
                );
            delete packet->image;
            packet->image = nullptr;
          } else {
            Debug(1, "Setup conversion context for %dx%d %s to %dx%d %s",
                input_frame->width, input_frame->height,
                av_get_pix_fmt_name((AVPixelFormat)input_frame->format),
                camera_width, camera_height,
                av_get_pix_fmt_name(imagePixFormat)
                );
          }
        }
        if (convert_context) {
          if (!packet->image->Assign(packet->in_frame, convert_context, dest_frame)) {
            delete packet->image;
            packet->image = nullptr;
          }
          av_frame_unref(dest_frame);
        }  // end if have convert_context
      }  // end if need transfer to image
    } else {
      Debug(1, "No packet.size(%d) or packet->in_frame(%p). Not decoding", packet->packet.size, packet->in_frame);
    }
  }  // end if need_decoding

  Image* capture_image = nullptr;
  unsigned int index = shared_data->image_count % image_buffer_count;

  if (packet->image) {
    capture_image = packet->image;

    /* Deinterlacing */
    if (deinterlacing_value) {
      Debug(1, "Doing deinterlacing");
      if (deinterlacing_value == 1) {
        capture_image->Deinterlace_Discard();
      } else if (deinterlacing_value == 2) {
        capture_image->Deinterlace_Linear();
      } else if (deinterlacing_value == 3) {
        capture_image->Deinterlace_Blend();
      } else if (deinterlacing_value == 4) {
        ZMLockedPacket *deinterlace_packet_lock = nullptr;
        while (!zm_terminate) {
          ZMLockedPacket *second_packet_lock = packetqueue.get_packet(decoder_it);
          if (!second_packet_lock) {
            delete packet_lock;
            return false;
          }
          if (second_packet_lock->packet_->codec_type == packet->codec_type) {
            deinterlace_packet_lock = second_packet_lock;
            break;
          }
          delete second_packet_lock;
          packetqueue.increment_it(decoder_it);
        }
        if (zm_terminate) return false;
        capture_image->Deinterlace_4Field(deinterlace_packet_lock->packet_->image, (deinterlacing>>8)&0xff);
        packetqueue.unlock(deinterlace_packet_lock);
      } else if (deinterlacing_value == 5) {
        capture_image->Deinterlace_Blend_CustomRatio((deinterlacing>>8)&0xff);
      }
    }

    if (orientation != ROTATE_0) {
      Debug(3, "Doing rotation");
      switch (orientation) {
        case ROTATE_0 :
          // No action required
          break;
        case ROTATE_90 :
        case ROTATE_180 :
        case ROTATE_270 : 
          capture_image->Rotate((orientation-1)*90);
          break;
        case FLIP_HORI :
        case FLIP_VERT :
          capture_image->Flip(orientation==FLIP_HORI);
          break;
      }
    } // end if have rotation

    if (privacy_bitmask) {
      Debug(3, "Applying privacy");
      capture_image->MaskPrivacy(privacy_bitmask);
    }

    if (config.timestamp_on_capture) {
      Debug(3, "Timestamping");
      TimestampImage(packet->image, packet->timestamp);
    }

    image_buffer[index]->Assign(*(packet->image));
    shared_timestamps[index] = packet->timestamp;
  }  // end if have image
  packet->decoded = true;
  shared_data->signal = (capture_image and signal_check_points) ? CheckSignal(capture_image) : true;
  shared_data->last_write_index = index;

  struct timeval now;
  gettimeofday(&now, nullptr);
  shared_data->last_write_time = now.tv_sec;
  if (now.tv_sec - packet->timestamp.tv_sec > ZM_WATCH_MAX_DELAY) {
    Warning("Decoding is not keeping up. We are %ld seconds behind capture.",
        now.tv_sec - packet->timestamp.tv_sec);
  }

  packetqueue.unlock(packet_lock);
  return true;
}  // end bool Monitor::Decode()

void Monitor::TimestampImage(Image *ts_image, const timeval &ts_time) const {
  if (!label_format[0])
    return;

  // Expand the strftime macros first
  char label_time_text[256];
  tm ts_tm = {};
  strftime(label_time_text, sizeof(label_time_text), label_format.c_str(), localtime_r(&ts_time.tv_sec, &ts_tm));
  char label_text[1024];
  const char *s_ptr = label_time_text;
  char *d_ptr = label_text;
  while ( *s_ptr && ((d_ptr-label_text) < (unsigned int)sizeof(label_text)) ) {
    if ( *s_ptr == config.timestamp_code_char[0] ) {
      bool found_macro = false;
      switch ( *(s_ptr+1) ) {
        case 'N' :
          d_ptr += snprintf(d_ptr, sizeof(label_text)-(d_ptr-label_text), "%s", name.c_str());
          found_macro = true;
          break;
        case 'Q' :
          d_ptr += snprintf(d_ptr, sizeof(label_text)-(d_ptr-label_text), "%s", trigger_data->trigger_showtext);
          found_macro = true;
          break;
        case 'f' :
          d_ptr += snprintf(d_ptr, sizeof(label_text)-(d_ptr-label_text), "%02ld", ts_time.tv_usec/10000);
          found_macro = true;
          break;
      }
      if ( found_macro ) {
        s_ptr += 2;
        continue;
      }
    }
    *d_ptr++ = *s_ptr++;
  } // end while
  *d_ptr = '\0';
  Debug(2, "annotating %s", label_text);
  ts_image->Annotate(label_text, label_coord, label_size);
  Debug(2, "done annotating %s", label_text);
} // end void Monitor::TimestampImage

Event * Monitor::openEvent(
    const std::shared_ptr<ZMPacket> &snap,
    const std::string &cause,
    const Event::StringSetMap &noteSetMap) {

  // FIXME this iterator is not protected from invalidation
  packetqueue_iterator *start_it = packetqueue.get_event_start_packet_it(
      *analysis_it,
      (cause == "Continuous" ? 0 : (pre_event_count > alarm_frame_count ? pre_event_count : alarm_frame_count))
      );

  if (*start_it != *analysis_it) {
    ZMLockedPacket *starting_packet_lock = packetqueue.get_packet(start_it);
    if (!starting_packet_lock) {
      Warning("Unable to get starting packet lock");
      return nullptr;
    }
    std::shared_ptr<ZMPacket> starting_packet = starting_packet_lock->packet_;
    ZM_DUMP_PACKET(starting_packet->packet, "First packet from start");
    event = new Event(this, start_it, starting_packet->timestamp, cause, noteSetMap);
    SetVideoWriterStartTime(starting_packet->timestamp);
    delete starting_packet_lock;
  } else {
    ZM_DUMP_PACKET(snap->packet, "First packet from alarm");
    event = new Event(this, start_it, snap->timestamp, cause, noteSetMap);
    SetVideoWriterStartTime(snap->timestamp);
  }

  shared_data->last_event_id = event->Id();
  strncpy(shared_data->alarm_cause, cause.c_str(), sizeof(shared_data->alarm_cause)-1);

  return event;
}

/* Caller must hold the event lock */
void Monitor::closeEvent() {
  if (!event) return;

  if (close_event_thread.joinable()) {
    Debug(1, "close event thread is joinable");
    close_event_thread.join();
  } else {
    Debug(1, "close event thread is not joinable");
  }
  Debug(1, "Starting thread to close event");
  close_event_thread = std::thread([](Event *e){ delete e; }, event);
  Debug(1, "Nulling event");
  event = nullptr;
  if (shared_data) video_store_data->recording = {};
} // end bool Monitor::closeEvent()

unsigned int Monitor::DetectMotion(const Image &comp_image, Event::StringSet &zoneSet) {
  bool alarm = false;
  unsigned int score = 0;

  if (zones.empty()) {
    Warning("No zones to check!");
    return alarm;
  }

  ref_image.Delta(comp_image, &delta_image);

  if (config.record_diag_images) {
    ref_image.WriteJpeg(diag_path_ref.c_str(), config.record_diag_images_fifo);
    delta_image.WriteJpeg(diag_path_delta.c_str(), config.record_diag_images_fifo);
  }

  // Blank out all exclusion zones
  for (Zone &zone : zones) {
    // need previous alarmed state for preclusive zone, so don't clear just yet
    if (!zone.IsPreclusive())
      zone.ClearAlarm();
    if (!zone.IsInactive())
      continue;
    Debug(3, "Blanking inactive zone %s", zone.Label());
    delta_image.Fill(kRGBBlack, zone.GetPolygon());
  } // end foreach zone

  // Check preclusive zones first
  for (Zone &zone : zones) {
    if (!zone.IsPreclusive())
      continue;
    int old_zone_score = zone.Score();
    bool old_zone_alarmed = zone.Alarmed();
    Debug(3, "Checking preclusive zone %s - old score: %d, state: %s",
        zone.Label(),old_zone_score, zone.Alarmed()?"alarmed":"quiet");
    if (zone.CheckAlarms(&delta_image)) {
      alarm = true;
      score += zone.Score();
      zone.SetAlarm();
      Debug(3, "Zone is alarmed, zone score = %d", zone.Score());
      zoneSet.insert(zone.Label());
    } else {
      // check if end of alarm
      if (old_zone_alarmed) {
        Debug(3, "Preclusive Zone %s alarm Ends. Previous score: %d",
            zone.Label(), old_zone_score);
        if (old_zone_score > 0) {
          zone.SetExtendAlarmCount(zone.GetExtendAlarmFrames());
        }
        if (zone.CheckExtendAlarmCount()) {
          alarm = true;
          zone.SetAlarm();
        } else {
          zone.ClearAlarm();
        }
      }  // end if zone WAS alarmed
    } // end if CheckAlarms
  } // end foreach zone

  Vector2 alarm_centre;
  int top_score = -1;

  if (alarm) {
    alarm = false;
    score = 0;
  } else {
    // Find all alarm pixels in active zones
    for (Zone &zone : zones) {
      if (!zone.IsActive() || zone.IsPreclusive()) {
        continue;
      }
      Debug(3, "Checking active zone %s", zone.Label());
      if (zone.CheckAlarms(&delta_image)) {
        alarm = true;
        score += zone.Score();
        zone.SetAlarm();
        Debug(3, "Zone is alarmed, zone score = %d", zone.Score());
        zoneSet.insert(zone.Label());
        if (config.opt_control && track_motion) {
          if ((int)zone.Score() > top_score) {
            top_score = zone.Score();
            alarm_centre = zone.GetAlarmCentre();
          }
        }
      }
    } // end foreach zone

    if (alarm) {
      for (Zone &zone : zones) {
        if (!zone.IsInclusive()) {
          continue;
        }
        Debug(3, "Checking inclusive zone %s", zone.Label());
        if (zone.CheckAlarms(&delta_image)) {
          score += zone.Score();
          zone.SetAlarm();
          Debug(3, "Zone is alarmed, zone score = %d", zone.Score());
          zoneSet.insert(zone.Label());
          if (config.opt_control && track_motion) {
            if (zone.Score() > (unsigned int)top_score) {
              top_score = zone.Score();
              alarm_centre = zone.GetAlarmCentre();
            }
          }
        } // end if CheckAlarm
      } // end foreach zone
    } else {
      // Find all alarm pixels in exclusive zones
      for (Zone &zone : zones) {
        if (!zone.IsExclusive()) {
          continue;
        }
        Debug(3, "Checking exclusive zone %s", zone.Label());
        if (zone.CheckAlarms(&delta_image)) {
          alarm = true;
          score += zone.Score();
          zone.SetAlarm();
          Debug(3, "Zone is alarmed, zone score = %d", zone.Score());
          zoneSet.insert(zone.Label());
        }
      } // end foreach zone
    } // end if alarm or not
  } // end if alarm

  if (top_score > 0) {
    shared_data->alarm_x = alarm_centre.x_;
    shared_data->alarm_y = alarm_centre.y_;

    Info("Got alarm centre at %d,%d, at count %d",
        shared_data->alarm_x, shared_data->alarm_y, analysis_image_count);
  } else {
    shared_data->alarm_x = shared_data->alarm_y = -1;
  }

  // This is a small and innocent hack to prevent scores of 0 being returned in alarm state
  return score ? score : alarm;
} // end DetectMotion

// TODO: Move the camera specific things to the camera classes and avoid these casts.
bool Monitor::DumpSettings(char *output, bool verbose) {
  output[0] = 0;

  sprintf( output+strlen(output), "Id : %u\n", id );
  sprintf( output+strlen(output), "Name : %s\n", name.c_str() );
  sprintf( output+strlen(output), "Type : %s\n", camera->IsLocal()?"Local":(camera->IsRemote()?"Remote":"File") );
#if ZM_HAS_V4L
  if ( camera->IsLocal() ) {
    LocalCamera* cam = static_cast<LocalCamera*>(camera.get());
    sprintf( output+strlen(output), "Device : %s\n", cam->Device().c_str() );
    sprintf( output+strlen(output), "Channel : %d\n", cam->Channel() );
    sprintf( output+strlen(output), "Standard : %d\n", cam->Standard() );
  } else
#endif // ZM_HAS_V4L
  if ( camera->IsRemote() ) {
    RemoteCamera* cam = static_cast<RemoteCamera*>(camera.get());
    sprintf( output+strlen(output), "Protocol : %s\n", cam->Protocol().c_str() );
    sprintf( output+strlen(output), "Host : %s\n", cam->Host().c_str() );
    sprintf( output+strlen(output), "Port : %s\n", cam->Port().c_str() );
    sprintf( output+strlen(output), "Path : %s\n", cam->Path().c_str() );
  } else if ( camera->IsFile() ) {
    FileCamera* cam = static_cast<FileCamera*>(camera.get());
    sprintf( output+strlen(output), "Path : %s\n", cam->Path() );
  }
#if HAVE_LIBAVFORMAT
  else if ( camera->IsFfmpeg() ) {
    FfmpegCamera* cam = static_cast<FfmpegCamera*>(camera.get());
    sprintf( output+strlen(output), "Path : %s\n", cam->Path().c_str() );
  }
#endif // HAVE_LIBAVFORMAT
  sprintf( output+strlen(output), "Width : %u\n", camera->Width() );
  sprintf( output+strlen(output), "Height : %u\n", camera->Height() );
#if ZM_HAS_V4L
  if ( camera->IsLocal() ) {
    LocalCamera* cam = static_cast<LocalCamera*>(camera.get());
    sprintf( output+strlen(output), "Palette : %d\n", cam->Palette() );
  }
#endif // ZM_HAS_V4L
  sprintf(output+strlen(output), "Colours : %u\n", camera->Colours() );
  sprintf(output+strlen(output), "Subpixel Order : %u\n", camera->SubpixelOrder() );
  sprintf(output+strlen(output), "Event Prefix : %s\n", event_prefix.c_str() );
  sprintf(output+strlen(output), "Label Format : %s\n", label_format.c_str() );
  sprintf(output+strlen(output), "Label Coord : %d,%d\n", label_coord.x_, label_coord.y_ );
  sprintf(output+strlen(output), "Label Size : %d\n", label_size );
  sprintf(output+strlen(output), "Image Buffer Count : %d\n", image_buffer_count );
  sprintf(output+strlen(output), "Warmup Count : %d\n", warmup_count );
  sprintf(output+strlen(output), "Pre Event Count : %d\n", pre_event_count );
  sprintf(output+strlen(output), "Post Event Count : %d\n", post_event_count );
  sprintf(output+strlen(output), "Stream Replay Buffer : %d\n", stream_replay_buffer );
  sprintf(output+strlen(output), "Alarm Frame Count : %d\n", alarm_frame_count );
  sprintf(output+strlen(output), "Section Length : %d\n", section_length);
  sprintf(output+strlen(output), "Min Section Length : %d\n", min_section_length);
  sprintf(output+strlen(output), "Maximum FPS : %.2f\n", capture_delay?(double)DT_PREC_3/capture_delay:0.0);
  sprintf(output+strlen(output), "Alarm Maximum FPS : %.2f\n", alarm_capture_delay?(double)DT_PREC_3/alarm_capture_delay:0.0);
  sprintf(output+strlen(output), "Reference Blend %%ge : %d\n", ref_blend_perc);
  sprintf(output+strlen(output), "Alarm Reference Blend %%ge : %d\n", alarm_ref_blend_perc);
  sprintf(output+strlen(output), "Track Motion : %d\n", track_motion);
  sprintf(output+strlen(output), "Function: %d - %s\n", function,
    function==NONE?"None":(
    function==MONITOR?"Monitor Only":(
    function==MODECT?"Motion Detection":(
    function==RECORD?"Continuous Record":(
    function==MOCORD?"Continuous Record with Motion Detection":(
    function==NODECT?"Externally Triggered only, no Motion Detection":"Unknown"
  ))))));
  sprintf(output+strlen(output), "Zones : %zu\n", zones.size());
  for (const Zone &zone : zones) {
    zone.DumpSettings(output+strlen(output), verbose);
  }
  sprintf(output+strlen(output), "Recording Enabled? %s\n", enabled ? "enabled" : "disabled");
  sprintf(output+strlen(output), "Events Enabled (!TRIGGER_OFF)? %s\n", trigger_data->trigger_state == TRIGGER_OFF ? "disabled" : "enabled");
  sprintf(output+strlen(output), "Motion Detection Enabled? %s\n", shared_data->active ? "enabled" : "disabled");
  return true;
} // bool Monitor::DumpSettings(char *output, bool verbose)

unsigned int Monitor::Colours() const { return camera ? camera->Colours() : colours; }
unsigned int Monitor::SubpixelOrder() const { return camera ? camera->SubpixelOrder() : 0; }

int Monitor::PrimeCapture() {
  int ret = camera->PrimeCapture();
  if (ret <= 0) return ret;

  if ( -1 != camera->getVideoStreamId() ) {
    video_stream_id = packetqueue.addStream();
  }

  if ( -1 != camera->getAudioStreamId() ) {
    audio_stream_id = packetqueue.addStream();
    packetqueue.addStream();
    shared_data->audio_frequency = camera->getFrequency();
    shared_data->audio_channels = camera->getChannels();
  }

  Debug(2, "Video stream id is %d, audio is %d, minimum_packets to keep in buffer %d",
      video_stream_id, audio_stream_id, pre_event_count);

  if (rtsp_server) {
    if (video_stream_id >= 0) {
      AVStream *videoStream = camera->getVideoStream();
      snprintf(shared_data->video_fifo_path, sizeof(shared_data->video_fifo_path)-1, "%s/video_fifo_%u.%s",
          staticConfig.PATH_SOCKS.c_str(),
          id,
#if LIBAVCODEC_VERSION_CHECK(57, 64, 0, 64, 0)
          avcodec_get_name(videoStream->codecpar->codec_id)
#else
          avcodec_get_name(videoStream->codec->codec_id)
#endif
          );
      video_fifo = new Fifo(shared_data->video_fifo_path, true);
    }
    if (record_audio and (audio_stream_id >= 0)) {
      AVStream *audioStream = camera->getAudioStream();
      if (audioStream && CODEC(audioStream)) {
      snprintf(shared_data->audio_fifo_path, sizeof(shared_data->audio_fifo_path)-1, "%s/audio_fifo_%u.%s",
          staticConfig.PATH_SOCKS.c_str(), id,
#if LIBAVCODEC_VERSION_CHECK(57, 64, 0, 64, 0)
          avcodec_get_name(audioStream->codecpar->codec_id)
#else
          avcodec_get_name(audioStream->codec->codec_id)
#endif
          );
      audio_fifo = new Fifo(shared_data->audio_fifo_path, true);
      } else {
        Warning("No audioStream %p or codec?", audioStream);
      }
    }
  }  // end if rtsp_server

  if (decoding_enabled) {
    if (!decoder_it) decoder_it = packetqueue.get_video_it(false);
    if (!decoder) {
      Debug(1, "Creating decoder thread");
      decoder = ZM::make_unique<DecoderThread>(this);
    } else {
      Debug(1, "Restarting decoder thread");
      decoder->Start();
    }
  }
  Debug(1, "Done restarting decoder");
  if (!analysis_it) {
    Debug(1, "getting analysis_it");
    analysis_it = packetqueue.get_video_it(false);
  } else {
    Debug(1, "haveing analysis_it");
  }
  if (!analysis_thread) {
    Debug(1, "Starting an analysis thread for monitor (%d)", id);
    analysis_thread = ZM::make_unique<AnalysisThread>(this);
  } else {
    Debug(1, "Restarting analysis thread for monitor (%d)", id);
    analysis_thread->Start();
  }
  return ret;
}  // end int Monitor::PrimeCapture()

int Monitor::PreCapture() const { return camera->PreCapture(); }
int Monitor::PostCapture() const { return camera->PostCapture(); }
int Monitor::Close() {
  // Wake everyone up
  packetqueue.stop();

  // Because the stream indexes may change we have to clear out the packetqueue
  if (decoder) {
    decoder->Stop();
  }

  if (analysis_thread) {
    analysis_thread->Stop();
  }
  if (audio_fifo) {
    delete audio_fifo;
    audio_fifo = nullptr;
  }
  if (video_fifo) {
    delete video_fifo;
    video_fifo = nullptr;
  }

  if (close_event_thread.joinable()) {
    close_event_thread.join();
  }

  {
    std::lock_guard<std::mutex> lck(event_mutex);
    if (event) {
      Info("%s: image_count:%d - Closing event %" PRIu64 ", shutting down", name.c_str(), shared_data->image_count, event->Id());
      closeEvent();
      close_event_thread.join();
    }
  }
  packetqueue.clear();
  if (camera) camera->Close();
  return 1;
}

Monitor::Orientation Monitor::getOrientation() const { return orientation; }

// Wait for camera to get an image, and then assign it as the base reference image.
// So this should be done as the first task in the analysis thread startup.
// This function is deprecated.
void Monitor::get_ref_image() {
  ZMLockedPacket *snap_lock = nullptr;

  if ( !analysis_it ) 
    analysis_it = packetqueue.get_video_it(true);

  while ( 
      (
       !( snap_lock = packetqueue.get_packet(analysis_it))
       or 
       ( snap_lock->packet_->codec_type != AVMEDIA_TYPE_VIDEO )
       or
       ! snap_lock->packet_->image
      )
    and !zm_terminate) {

    Debug(1, "Waiting for capture daemon lastwriteindex(%d) lastwritetime(%" PRIi64 ")",
          shared_data->last_write_index, static_cast<int64>(shared_data->last_write_time));
    if (snap_lock and ! snap_lock->packet_->image) {
      delete snap_lock;
      // can't analyse it anyways, incremement
      packetqueue.increment_it(analysis_it);
    }
    //usleep(10000);
  }
  if (zm_terminate)
    return;

  std::shared_ptr<ZMPacket> snap = snap_lock->packet_;
  Debug(1, "get_ref_image: packet.stream %d ?= video_stream %d, packet image id %d packet image %p",
      snap->packet.stream_index, video_stream_id, snap->image_index, snap->image );
  // Might not have been decoded yet FIXME
  if (snap->image) {
    ref_image.Assign(width, height, camera->Colours(),
        camera->SubpixelOrder(), snap->image->Buffer(), camera->ImageSize());
    Debug(2, "Have ref image about to unlock");
  } else {
    Debug(2, "Have no ref image about to unlock");
  }
  delete snap_lock;
}  // get_ref_image

std::vector<Group *> Monitor::Groups() {
  // At the moment, only load groups once.
  if (!groups.size()) {
    std::string sql = stringtf(
        "SELECT `Id`, `ParentId`, `Name` FROM `Groups` WHERE `Groups.Id` IN "
        "(SELECT `GroupId` FROM `Groups_Monitors` WHERE `MonitorId`=%d)", id);
    MYSQL_RES *result = zmDbFetch(sql.c_str());
    if (!result) {
      Error("Can't load groups: %s", mysql_error(&dbconn));
      return groups;
    }
    int n_groups = mysql_num_rows(result);
    Debug(1, "Got %d groups", n_groups);
    groups.reserve(n_groups);
    while (MYSQL_ROW dbrow = mysql_fetch_row(result)) {
      groups.push_back(new Group(dbrow));
    }
    if (mysql_errno(&dbconn)) {
      Error("Can't fetch row: %s", mysql_error(&dbconn));
    }
    mysql_free_result(result);
  }
  return groups;
} // end Monitor::Groups()

StringVector Monitor::GroupNames() {
  StringVector groupnames;
  for ( Group * g: Groups() ) {
    groupnames.push_back(std::string(g->Name()));
    Debug(1, "Groups: %s", g->Name());
  }
  return groupnames;
} // end Monitor::GroupNames()
