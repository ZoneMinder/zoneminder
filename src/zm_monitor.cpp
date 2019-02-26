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

#include <sys/types.h>
#include <sys/stat.h>
#include <arpa/inet.h>
#include <glob.h>
#include <cinttypes>

#include "zm.h"
#include "zm_db.h"
#include "zm_time.h"
#include "zm_mpeg.h"
#include "zm_signal.h"
#include "zm_monitor.h"
#include "zm_video.h"
#include "zm_eventstream.h"
#if ZM_HAS_V4L
#include "zm_local_camera.h"
#endif // ZM_HAS_V4L
#include "zm_remote_camera.h"
#include "zm_remote_camera_http.h"
#include "zm_remote_camera_nvsocket.h"
#if HAVE_LIBAVFORMAT
#include "zm_remote_camera_rtsp.h"
#endif // HAVE_LIBAVFORMAT
#include "zm_file_camera.h"
#if HAVE_LIBAVFORMAT
#include "zm_ffmpeg_camera.h"
#endif // HAVE_LIBAVFORMAT
#if HAVE_LIBVLC
#include "zm_libvlc_camera.h"
#endif // HAVE_LIBVLC
#if HAVE_LIBCURL
#include "zm_curl_camera.h"
#endif // HAVE_LIBCURL

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
"SELECT Id, Name, ServerId, StorageId, Type, Function+0, Enabled, LinkedMonitors, "
"AnalysisFPSLimit, AnalysisUpdateDelay, MaxFPS, AlarmMaxFPS,"
"Device, Channel, Format, V4LMultiBuffer, V4LCapturesPerFrame, " // V4L Settings
"Protocol, Method, Options, User, Pass, Host, Port, Path, Width, Height, Colours, Palette, Orientation+0, Deinterlacing, RTSPDescribe, "
"SaveJPEGs, VideoWriter, EncoderParameters, "
"OutputCodec, Encoder, OutputContainer, "
"RecordAudio, "
"Brightness, Contrast, Hue, Colour, "
"EventPrefix, LabelFormat, LabelX, LabelY, LabelSize,"
"ImageBufferCount, WarmupCount, PreEventCount, PostEventCount, StreamReplayBuffer, AlarmFrameCount, "
"SectionLength, FrameSkip, MotionFrameSkip, "
"FPSReportInterval, RefBlendPerc, AlarmRefBlendPerc, TrackMotion, Exif, SignalCheckPoints, SignalCheckColour FROM Monitors";

std::string CameraType_Strings[] = {
  "Local",
  "Remote",
  "File",
  "Ffmpeg",
  "LibVLC",
  "cURL",
  "NVSOCKET",
};

Monitor::MonitorLink::MonitorLink(int p_id, const char *p_name) :
  id(p_id),
  shared_data(NULL),
  trigger_data(NULL),
  video_store_data(NULL)
{
  strncpy(name, p_name, sizeof(name)-1);

#if ZM_MEM_MAPPED
  map_fd = -1;
  snprintf(mem_file, sizeof(mem_file), "%s/zm.mmap.%d", staticConfig.PATH_MAP.c_str(), id);
#else // ZM_MEM_MAPPED
  shm_id = 0;
#endif // ZM_MEM_MAPPED
  mem_size = 0;
  mem_ptr = 0;

  last_event_id = 0;
  last_state = IDLE;

  last_connect_time = 0;
  connected = false;
}

Monitor::MonitorLink::~MonitorLink() {
  disconnect();
}

bool Monitor::MonitorLink::connect() {
  if ( !last_connect_time || (time( 0 ) - last_connect_time) > 60 ) {
    last_connect_time = time( 0 );

    mem_size = sizeof(SharedData) + sizeof(TriggerData);

    Debug( 1, "link.mem.size=%d", mem_size );
#if ZM_MEM_MAPPED
    map_fd = open( mem_file, O_RDWR, (mode_t)0600 );
    if ( map_fd < 0 ) {
      Debug( 3, "Can't open linked memory map file %s: %s", mem_file, strerror(errno) );
      disconnect();
      return( false );
    }
    while ( map_fd <= 2 ) {
      int new_map_fd = dup(map_fd);
      Warning( "Got one of the stdio fds for our mmap handle. map_fd was %d, new one is %d", map_fd, new_map_fd );
      close(map_fd);
      map_fd = new_map_fd;
    }

    struct stat map_stat;
    if ( fstat( map_fd, &map_stat ) < 0 ) {
      Error( "Can't stat linked memory map file %s: %s", mem_file, strerror(errno) );
      disconnect();
      return( false );
    }

    if ( map_stat.st_size == 0 ) {
      Error( "Linked memory map file %s is empty: %s", mem_file, strerror(errno) );
      disconnect();
      return( false );
    } else if ( map_stat.st_size < mem_size ) {
      Error( "Got unexpected memory map file size %ld, expected %d", map_stat.st_size, mem_size );
      disconnect();
      return( false );
    }

    mem_ptr = (unsigned char *)mmap( NULL, mem_size, PROT_READ|PROT_WRITE, MAP_SHARED, map_fd, 0 );
    if ( mem_ptr == MAP_FAILED ) {
      Error( "Can't map file %s (%d bytes) to memory: %s", mem_file, mem_size, strerror(errno) );
      disconnect();
      return( false );
    }
#else // ZM_MEM_MAPPED
    shm_id = shmget( (config.shm_key&0xffff0000)|id, mem_size, 0700 );
    if ( shm_id < 0 ) {
      Debug( 3, "Can't shmget link memory: %s", strerror(errno) );
      connected = false;
      return( false );
    }
    mem_ptr = (unsigned char *)shmat( shm_id, 0, 0 );
    if ( mem_ptr < (void *)0 ) {
      Debug( 3, "Can't shmat link memory: %s", strerror(errno) );
      connected = false;
      return( false );
    }
#endif // ZM_MEM_MAPPED

    shared_data = (SharedData *)mem_ptr;
    trigger_data = (TriggerData *)((char *)shared_data + sizeof(SharedData));

    if ( !shared_data->valid ) {
      Debug( 3, "Linked memory not initialised by capture daemon" );
      disconnect();
      return( false );
    }

    last_state = shared_data->state;
    last_event_id = shared_data->last_event_id;
    connected = true;

    return( true );
  }
  return( false );
} // end bool Monitor::MonitorLink::connect()

bool Monitor::MonitorLink::disconnect() {
  if ( connected ) {
    connected = false;

#if ZM_MEM_MAPPED
    if ( mem_ptr > (void *)0 ) {
      msync( mem_ptr, mem_size, MS_ASYNC );
      munmap( mem_ptr, mem_size );
    }
    if ( map_fd >= 0 )
      close( map_fd );

    map_fd = -1;
#else // ZM_MEM_MAPPED
    struct shmid_ds shm_data;
    if ( shmctl( shm_id, IPC_STAT, &shm_data ) < 0 ) {
      Debug( 3, "Can't shmctl: %s", strerror(errno) );
      return( false );
    }

    shm_id = 0;

    if ( shm_data.shm_nattch <= 1 ) {
      if ( shmctl( shm_id, IPC_RMID, 0 ) < 0 ) {
        Debug( 3, "Can't shmctl: %s", strerror(errno) );
        return( false );
      }
    }

    if ( shmdt( mem_ptr ) < 0 ) {
      Debug( 3, "Can't shmdt: %s", strerror(errno) );
      return( false );
    }

#endif // ZM_MEM_MAPPED
    mem_size = 0;
    mem_ptr = 0;
  }
  return( true );
}

bool Monitor::MonitorLink::isAlarmed() {
  if ( !connected ) {
    return( false );
  }
  return( shared_data->state == ALARM );
}

bool Monitor::MonitorLink::inAlarm() {
  if ( !connected ) {
    return( false );
  }
  return( shared_data->state == ALARM || shared_data->state == ALERT );
}

bool Monitor::MonitorLink::hasAlarmed() {
  if ( shared_data->state == ALARM ) {
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
  videoRecording(0),
  rtsp_describe(0),

  savejpegs(0),
  colours(0),
  videowriter(DISABLED),
  encoderparams(""),
  output_codec(0),
  output_container(""),
  record_audio(0),
//event_prefix
//label_format
  label_coord(Coord(0,0)),
  label_size(0),
  image_buffer_count(0),
  warmup_count(0),
  pre_event_count(0),
  post_event_count(0),
  stream_replay_buffer(0),
  section_length(0),
  frame_skip(0),
  motion_frame_skip(0),
  analysis_fps_limit(0),
  analysis_update_delay(0),
  capture_delay(0),
  alarm_capture_delay(0),
  alarm_frame_count(0),
  fps_report_interval(0),
  ref_blend_perc(0),
  alarm_ref_blend_perc(0),
  track_motion(0),
  signal_check_points(0),
  signal_check_colour(0),
  embed_exif(0),
  purpose(QUERY),
  auto_resume_time(0),
  last_motion_score(0),
  camera(0),
  n_zones(0),
  zones(NULL),
  timestamps(0),
  images(0),
  privacy_bitmask( NULL ),
  event_delete_thread(NULL),
  n_linked_monitors(0),
  linked_monitors(NULL)
{

  if ( strcmp(config.event_close_mode, "time") == 0 )
    event_close_mode = CLOSE_TIME;
  else if ( strcmp(config.event_close_mode, "alarm") == 0 )
    event_close_mode = CLOSE_ALARM;
  else
    event_close_mode = CLOSE_IDLE;

  start_time = last_fps_time = time( 0 );
  event = 0;
  last_section_mod = 0;

  adaptive_skip = true;

  videoStore = NULL;
} // Monitor::Monitor

/*
  std::string load_monitor_sql =
 "SELECT Id, Name, ServerId, StorageId, Type, Function+0, Enabled, LinkedMonitors, "
 "AnalysisFPSLimit, AnalysisUpdateDelay, MaxFPS, AlarmMaxFPS,"
 "Device, Channel, Format, V4LMultiBuffer, V4LCapturesPerFrame, " // V4L Settings
 "Protocol, Method, Options, User, Pass, Host, Port, Path, Width, Height, Colours, Palette, Orientation+0, Deinterlacing, RTSPDescribe, "
 "SaveJPEGs, VideoWriter, EncoderParameters,
"OutputCodec, Encoder, OutputContainer,"
" RecordAudio, "
 "Brightness, Contrast, Hue, Colour, "
 "EventPrefix, LabelFormat, LabelX, LabelY, LabelSize,"
 "ImageBufferCount, WarmupCount, PreEventCount, PostEventCount, StreamReplayBuffer, AlarmFrameCount, "
 "SectionLength, FrameSkip, MotionFrameSkip, "
 "FPSReportInterval, RefBlendPerc, AlarmRefBlendPerc, TrackMotion, Exif, SignalCheckColour FROM Monitors";
*/

void Monitor::Load(MYSQL_ROW dbrow, bool load_zones=true, Purpose p = QUERY) {
  purpose = p;
  int col = 0;

  id = atoi(dbrow[col]); col++;
  strncpy(name, dbrow[col], sizeof(name)-1); col++;
  server_id = dbrow[col] ? atoi(dbrow[col]) : 0; col++;

  storage_id = atoi(dbrow[col]); col++;
  storage = new Storage(storage_id);
  Debug(1, "Storage path: %s", storage->Path() );

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
  } else {
    Fatal("Bogus monitor type '%s' for monitor %d", dbrow[col], id);
  }
  Debug(1,"Have camera type %s", CameraType_Strings[type].c_str());
  col++;
  function = (Function)atoi(dbrow[col]); col++;
  enabled = dbrow[col] ? atoi(dbrow[col]) : 0; col++;

  ReloadLinkedMonitors(dbrow[col]); col++;

  analysis_fps = dbrow[col] ? strtod(dbrow[col], NULL) : 0; col++;
  analysis_update_delay = strtoul(dbrow[col++], NULL, 0);
  capture_delay = (dbrow[col]&&atof(dbrow[col])>0.0)?int(DT_PREC_3/atof(dbrow[col])):0; col++;
  alarm_capture_delay = (dbrow[col]&&atof(dbrow[col])>0.0)?int(DT_PREC_3/atof(dbrow[col])):0; col++;

  if ( dbrow[col] )
    strncpy(device, dbrow[col], sizeof(device)-1);
  else
    device[0] = 0;
  col++;

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

  protocol = dbrow[col] ? dbrow[col] : ""; col++;
  method = dbrow[col] ? dbrow[col] : ""; col++;
  options = dbrow[col] ? dbrow[col] : ""; col++;
  user = dbrow[col] ? dbrow[col] : ""; col++;
  pass = dbrow[col] ? dbrow[col] : ""; col++;
  host = dbrow[col] ? dbrow[col] : ""; col++;
  port = dbrow[col] ? dbrow[col] : ""; col++;
  path = dbrow[col] ? dbrow[col] : ""; col++;

  camera_width = atoi(dbrow[col]); col++;
  camera_height = atoi(dbrow[col]); col++;

  colours = atoi(dbrow[col]); col++;
  palette = atoi(dbrow[col]); col++;
  orientation = (Orientation)atoi(dbrow[col]); col++;
  width = (orientation==ROTATE_90||orientation==ROTATE_270) ? camera_height : camera_width;
  height = (orientation==ROTATE_90||orientation==ROTATE_270) ? camera_width : camera_height;

  deinterlacing = atoi(dbrow[col]); col++;
  deinterlacing_value = deinterlacing & 0xff;

  rtsp_describe = (dbrow[col] && *dbrow[col] != '0'); col++;

  savejpegs = atoi(dbrow[col]); col++;
  videowriter = (VideoWriter)atoi(dbrow[col]); col++;
  encoderparams = dbrow[col] ? dbrow[col] : ""; col++;
  /* Parse encoder parameters */
  ParseEncoderParameters(encoderparams.c_str(), &encoderparamsvec);

  output_codec = dbrow[col] ? atoi(dbrow[col]) : 0; col++;
  encoder = dbrow[col] ? dbrow[col] : ""; col++;
  output_container = dbrow[col] ? dbrow[col] : ""; col++;
  record_audio = (*dbrow[col] != '0'); col++;

  brightness = atoi(dbrow[col]); col++;
  contrast = atoi(dbrow[col]); col++;
  hue = atoi(dbrow[col]); col++;
  colour = atoi(dbrow[col]); col++;

  if ( dbrow[col] )
    strncpy(event_prefix, dbrow[col], sizeof(event_prefix)-1);
  else
    event_prefix[0] = 0;
  col++;

  if ( dbrow[col] )
    strncpy(label_format, dbrow[col], sizeof(label_format)-1);
  else
    label_format[0] = 0;
  col++;

  // Change \n to actual line feeds
  char *token_ptr = label_format;
  const char *token_string = "\n";
  while( ( token_ptr = strstr(token_ptr, token_string) ) ) {
    if ( *(token_ptr+1) ) {
      *token_ptr = '\n';
      token_ptr++;
      strcpy(token_ptr, token_ptr+1);
    } else {
      *token_ptr = '\0';
      break;
    }
  }

  label_coord = Coord(atoi(dbrow[col]), atoi(dbrow[col+1])); col += 2;
  label_size = atoi(dbrow[col]); col++;

  image_buffer_count = atoi(dbrow[col]); col++;
  warmup_count = atoi(dbrow[col]); col++;
  pre_event_count = atoi(dbrow[col]); col++;
  post_event_count = atoi(dbrow[col]); col++;
  stream_replay_buffer = atoi(dbrow[col]); col++;
  alarm_frame_count = atoi(dbrow[col]); col++;
  if ( alarm_frame_count < 1 )
    alarm_frame_count = 1;
  else if ( alarm_frame_count > MAX_PRE_ALARM_FRAMES )
    alarm_frame_count = MAX_PRE_ALARM_FRAMES;
  pre_event_buffer_count = pre_event_count + alarm_frame_count + warmup_count - 1;

  section_length = atoi(dbrow[col]); col++;
  frame_skip = atoi(dbrow[col]); col++;
  motion_frame_skip = atoi(dbrow[col]); col++;
  fps_report_interval = atoi(dbrow[col]); col++;
  ref_blend_perc = atoi(dbrow[col]); col++;
  alarm_ref_blend_perc = atoi(dbrow[col]); col++;
  track_motion = atoi(dbrow[col]); col++;

  signal_check_colour = strtol(dbrow[col][0] == '#' ? dbrow[col]+1 : dbrow[col], 0, 16); col++;
  embed_exif = (*dbrow[col] != '0'); col++;

  capture_fps = 0.0;
  analysis_fps = 0.0;
  last_camera_bytes = 0;
  event_count = 0;
  image_count = 0;
  analysis_image_count = 0;

  // How many frames we need to have before we start analysing
  ready_count = warmup_count;

  last_alarm_count = 0;
  state = IDLE;
  last_signal = false;

  camera = NULL;
  mem_size = sizeof(SharedData)
       + sizeof(TriggerData)
       + sizeof(VideoStoreData) //Information to pass back to the capture process
       + (image_buffer_count * sizeof(struct timeval))
       + (image_buffer_count * width * height * colours)
       + 64; /* Padding used to permit aligning the images buffer to 64 byte boundary */

  Debug(1, "mem.size SharedData=%d TriggerData=%d VideoStoreData=%d total=%" PRId64,
     sizeof(SharedData), sizeof(TriggerData), sizeof(VideoStoreData), mem_size);
  mem_ptr = NULL;

  Zone **zones = 0;
  int n_zones = Zone::Load(this, zones);
  this->AddZones(n_zones, zones);
  this->AddPrivacyBitmask(zones);

// maybe unneeded
  // Should maybe store this for later use
  char monitor_dir[PATH_MAX] = "";
  snprintf(monitor_dir, sizeof(monitor_dir), "%s/%d", storage->Path(), id);
  if ( purpose == CAPTURE ) {
    if ( mkdir(monitor_dir, 0755) ) {
      if ( errno != EEXIST ) {
        Error("Can't mkdir %s: %s", monitor_dir, strerror(errno));
      }
    }
  }

  //this0>delta_image( width, height, ZM_COLOUR_GRAY8, ZM_SUBPIX_ORDER_NONE ),
  //ref_image( width, height, p_camera->Colours(), p_camera->SubpixelOrder() ),
  Debug(1, "Loaded monitor %d(%s), %d zones", id, name, n_zones);
  getCamera();
} // Monitor::Load

Camera * Monitor::getCamera() {
  if ( camera )
    return camera;

  if ( type == LOCAL ) {

    int extras = (deinterlacing>>24)&0xff;

    camera = new LocalCamera(
        id,
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
        purpose==CAPTURE,
        record_audio,
        extras
        );
  } else if ( type == REMOTE ) {
    if ( protocol == "http" ) {
      camera = new RemoteCameraHttp(
        id,
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
        purpose==CAPTURE,
        record_audio
      );
    }
#if HAVE_LIBAVFORMAT
    else if ( protocol == "rtsp" ) {
      camera = new RemoteCameraRtsp(
        id,
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
        purpose==CAPTURE,
        record_audio
      );
    }
#endif // HAVE_LIBAVFORMAT
    else {
      Error( "Unexpected remote camera protocol '%s'", protocol.c_str() );
    }
  } else if ( type == FILE ) {
    camera = new FileCamera(
      id,
      path.c_str(),
      camera_width,
      camera_height,
      colours,
      brightness,
      contrast,
      hue,
      colour,
      purpose==CAPTURE,
      record_audio
    );
  } else if ( type == FFMPEG ) {
#if HAVE_LIBAVFORMAT
    camera = new FfmpegCamera(
      id,
      path,
      method,
      options,
      camera_width,
      camera_height,
      colours,
      brightness,
      contrast,
      hue,
      colour,
      purpose==CAPTURE,
      record_audio
    );
#endif // HAVE_LIBAVFORMAT
  } else if ( type == NVSOCKET ) {
      camera = new RemoteCameraNVSocket(
        id,
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
        purpose==CAPTURE,
        record_audio
      );
  } else if ( type == LIBVLC ) {
#if HAVE_LIBVLC
    camera = new LibvlcCamera(
      id,
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
      purpose==CAPTURE,
      record_audio
    );
#else // HAVE_LIBVLC
    Error( "You must have vlc libraries installed to use vlc cameras for monitor %d", id );
#endif // HAVE_LIBVLC
  } else if ( type == CURL ) {
#if HAVE_LIBCURL
    camera = new cURLCamera(
      id,
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
      purpose==CAPTURE,
      record_audio
    );
#else // HAVE_LIBCURL
    Error("You must have libcurl installed to use ffmpeg cameras for monitor %d", id);
#endif // HAVE_LIBCURL
  } // end if type

  camera->setMonitor(this);
  return camera;
} // end Monitor::getCamera

Monitor *Monitor::Load(unsigned int p_id, bool load_zones, Purpose purpose) {
  std::string sql = load_monitor_sql + stringtf(" WHERE Id=%d", p_id);

  zmDbRow dbrow;
  if ( !dbrow.fetch(sql.c_str()) ) {
    Error("Can't use query result: %s", mysql_error(&dbconn));
    return NULL;
  }
  Monitor *monitor = new Monitor();
  monitor->Load(dbrow.mysql_row(), load_zones, purpose);
#if 0
  // We are explicitly connecting now
  if ( purpose == CAPTURE ) {
    Debug(1,"Connecting");
    if ( ! (
          monitor->getCamera()
          &&
          monitor->connect()
          ) ) {
        delete monitor;
        return NULL;
    }
  }
#endif

  return monitor;
} // end Monitor *Monitor::Load(unsigned int p_id, bool load_zones, Purpose purpose)

bool Monitor::connect() {
  Debug(3, "Connecting to monitor.  Purpose is %d", purpose );
#if ZM_MEM_MAPPED
  snprintf(mem_file, sizeof(mem_file), "%s/zm.mmap.%d", staticConfig.PATH_MAP.c_str(), id);
  map_fd = open(mem_file, O_RDWR|O_CREAT, (mode_t)0600);
  if ( map_fd < 0 ) {
    Fatal("Can't open memory map file %s, probably not enough space free: %s", mem_file, strerror(errno));
  } else {
    Debug(3, "Success opening mmap file at (%s)", mem_file);
  }

  struct stat map_stat;
  if ( fstat(map_fd, &map_stat) < 0 )
    Fatal("Can't stat memory map file %s: %s, is the zmc process for this monitor running?", mem_file, strerror(errno));

  if ( map_stat.st_size != mem_size ) {
    if ( purpose == CAPTURE ) {
      // Allocate the size
      if ( ftruncate(map_fd, mem_size) < 0 ) {
        Fatal("Can't extend memory map file %s to %d bytes: %s", mem_file, mem_size, strerror(errno));
      }
    } else if ( map_stat.st_size == 0 ) {
      Error("Got empty memory map file size %ld, is the zmc process for this monitor running?", map_stat.st_size, mem_size);
      close(map_fd);
      map_fd = -1;
      return false;
    } else {
      Error("Got unexpected memory map file size %ld, expected %d", map_stat.st_size, mem_size);
      close(map_fd);
      map_fd = -1;
      return false;
    }
  }

  Debug(3, "MMap file size is %ld", map_stat.st_size);
#ifdef MAP_LOCKED
  mem_ptr = (unsigned char *)mmap(NULL, mem_size, PROT_READ|PROT_WRITE, MAP_SHARED|MAP_LOCKED, map_fd, 0);
  if ( mem_ptr == MAP_FAILED ) {
    if ( errno == EAGAIN ) {
      Debug(1, "Unable to map file %s (%d bytes) to locked memory, trying unlocked", mem_file, mem_size);
#endif
      mem_ptr = (unsigned char *)mmap(NULL, mem_size, PROT_READ|PROT_WRITE, MAP_SHARED, map_fd, 0);
      Debug(1, "Mapped file %s (%d bytes) to unlocked memory", mem_file, mem_size);
#ifdef MAP_LOCKED
    } else {
      Error("Unable to map file %s (%d bytes) to locked memory (%s)", mem_file, mem_size, strerror(errno));
    }
  }
#endif
  if ( mem_ptr == MAP_FAILED )
    Fatal("Can't map file %s (%d bytes) to memory: %s(%d)", mem_file, mem_size, strerror(errno), errno);
  if ( mem_ptr == NULL ) {
    Error("mmap gave a null address:");
  } else {
    Debug(3, "mmapped to %p", mem_ptr);
  }
#else // ZM_MEM_MAPPED
  shm_id = shmget((config.shm_key&0xffff0000)|id, mem_size, IPC_CREAT|0700);
  if ( shm_id < 0 ) {
    Fatal("Can't shmget, probably not enough shared memory space free: %s", strerror(errno));
  }
  mem_ptr = (unsigned char *)shmat( shm_id, 0, 0 );
  if ( mem_ptr < (void *)0 ) {
    Fatal("Can't shmat: %s", strerror(errno));
  }
#endif // ZM_MEM_MAPPED
  shared_data = (SharedData *)mem_ptr;
  trigger_data = (TriggerData *)((char *)shared_data + sizeof(SharedData));
  video_store_data = (VideoStoreData *)((char *)trigger_data + sizeof(TriggerData));
  shared_timestamps = (struct timeval *)((char *)video_store_data + sizeof(VideoStoreData));
  shared_images = (unsigned char *)((char *)shared_timestamps + (image_buffer_count*sizeof(struct timeval)));

  packetqueue = NULL;

  if ( ((unsigned long)shared_images % 64) != 0 ) {
    /* Align images buffer to nearest 64 byte boundary */
    Debug(3,"Aligning shared memory images to the next 64 byte boundary");
    shared_images = (uint8_t*)((unsigned long)shared_images + (64 - ((unsigned long)shared_images % 64)));
  }

  Debug(3, "Allocating %d image buffers", image_buffer_count);
  image_buffer = new ZMPacket[image_buffer_count];
  for ( int i = 0; i < image_buffer_count; i++ ) {
    image_buffer[i].image_index = i;
    image_buffer[i].timestamp = &(shared_timestamps[i]);
    image_buffer[i].image = new Image( width, height, camera->Colours(), camera->SubpixelOrder(), &(shared_images[i*camera->ImageSize()]) );
    image_buffer[i].image->HoldBuffer(true); /* Don't release the internal buffer or replace it with another */
  }
  if ( deinterlacing_value == 4 ) {
    /* Four field motion adaptive deinterlacing in use */
    /* Allocate a buffer for the next image */
    next_buffer.image = new Image( width, height, camera->Colours(), camera->SubpixelOrder());
  }

  if ( purpose == CAPTURE ) {
    memset(mem_ptr, 0, mem_size);
    shared_data->size = sizeof(SharedData);
    Debug( 1, "shared.size=%d", shared_data->size );
    shared_data->active = enabled;
    shared_data->signal = false;
    shared_data->state = IDLE;
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
    trigger_data->size = sizeof(TriggerData);
    trigger_data->trigger_state = TRIGGER_CANCEL;
    trigger_data->trigger_score = 0;
    trigger_data->trigger_cause[0] = 0;
    trigger_data->trigger_text[0] = 0;
    trigger_data->trigger_showtext[0] = 0;
    shared_data->valid = true;
    video_store_data->recording = (struct timeval){0};
    // Uh, why nothing?  Why not NULL?
    snprintf(video_store_data->event_file, sizeof(video_store_data->event_file), "nothing");
    video_store_data->size = sizeof(VideoStoreData);
    //video_store_data->frameNumber = 0;
  }
  if ( ( ! mem_ptr ) || ! shared_data->valid ) {
    if ( purpose != QUERY ) {
      Error("Shared data not initialised by capture daemon for monitor %s", name);
      exit(-1);
    }
  }
  if ( purpose == ANALYSIS ) {
		if ( analysis_fps ) {
			// Size of pre event buffer must be greater than pre_event_count
			// if alarm_frame_count > 1, because in this case the buffer contains
			// alarmed images that must be discarded when event is created
			pre_event_buffer_count = pre_event_count + alarm_frame_count - 1;
		}

    timestamps = new struct timeval *[pre_event_count];
    images = new Image *[pre_event_count];
    last_signal = shared_data->signal;
  }

  Debug(3, "Success connecting");
  return true;
} // Monitor::connect

Monitor::~Monitor() {
  if ( mem_ptr ) {
    if ( event ) {
      Info( "%s: image_count:%d - Closing event %" PRIu64 ", shutting down", name, image_count, event->Id() );
      closeEvent();

      // closeEvent may start another thread to close the event, so wait for it to finish
      if ( event_delete_thread ) {
        event_delete_thread->join();
        delete event_delete_thread;
        event_delete_thread = NULL;
      }
    }
    if ( event_delete_thread ) {
      event_delete_thread->join();
      delete event_delete_thread;
      event_delete_thread = NULL;
    }

    if ( deinterlacing_value == 4 ) {
      delete next_buffer.image;
    }
#if 1
    for ( int i=0; i < image_buffer_count; i++ ) {
      delete image_buffer[i].image;
    }
#endif
    delete[] image_buffer;

    if ( purpose == ANALYSIS ) {
      shared_data->state = state = IDLE;
      // I think we set it to the count so that it is technically 1 behind capture, which starts at 0
      shared_data->last_read_index = image_buffer_count;
      shared_data->last_read_time = 0;
    } else if ( purpose == CAPTURE ) {
      shared_data->valid = false;
      memset(mem_ptr, 0, mem_size);
    }

#if ZM_MEM_MAPPED
    if ( msync(mem_ptr, mem_size, MS_SYNC) < 0 )
      Error("Can't msync: %s", strerror(errno));
    if ( munmap(mem_ptr, mem_size) < 0 )
      Fatal("Can't munmap: %s", strerror(errno));
    close(map_fd);

    if ( purpose == CAPTURE ) {
      // How about we store this in the object on instantiation so that we don't have to do this again.
      char mmap_path[PATH_MAX] = "";
      snprintf(mmap_path, sizeof(mmap_path), "%s/zm.mmap.%d", staticConfig.PATH_MAP.c_str(), id);

      if ( unlink(mmap_path) < 0 ) {
        Warning("Can't unlink '%s': %s", mmap_path, strerror(errno));
      }
    }
#else // ZM_MEM_MAPPED
    struct shmid_ds shm_data;
    if ( shmctl(shm_id, IPC_STAT, &shm_data) < 0 ) {
      Fatal("Can't shmctl: %s", strerror(errno));
    }
    if ( shm_data.shm_nattch <= 1 ) {
      if ( shmctl(shm_id, IPC_RMID, 0) < 0 ) {
        Fatal("Can't shmctl: %s", strerror(errno));
      }
    }
#endif // ZM_MEM_MAPPED
  } // end if mem_ptr

  if ( videoStore ) {
    delete videoStore;
    videoStore = NULL;
  }

  if ( n_linked_monitors ) {
    for( int i = 0; i < n_linked_monitors; i++ ) {
      delete linked_monitors[i];
    }
    delete[] linked_monitors;
    linked_monitors = 0;
  }

  if ( timestamps ) {
    delete[] timestamps;
    timestamps = 0;
  }
  if ( images ) {
    delete[] images;
    images = 0;
  }

  delete packetqueue;
  packetqueue = NULL;

  if ( privacy_bitmask ) {
    delete[] privacy_bitmask;
    privacy_bitmask = NULL;
  }
  for ( int i=0; i < n_zones; i++ ) {
    delete zones[i];
  }
  delete[] zones;

  delete camera;
  delete storage;
}

void Monitor::AddZones(int p_n_zones, Zone *p_zones[]) {
  for ( int i=0; i < n_zones; i++ )
    delete zones[i];
  delete[] zones;
  n_zones = p_n_zones;
  zones = p_zones;
}

void Monitor::AddPrivacyBitmask(Zone *p_zones[]) {
  if ( privacy_bitmask ) {
    delete[] privacy_bitmask;
    privacy_bitmask = NULL;
  }
  Image *privacy_image = NULL;

  for ( int i=0; i < n_zones; i++ ) {
    if ( p_zones[i]->IsPrivacy() ) {
      if ( !privacy_image ) {
        privacy_image = new Image(width, height, 1, ZM_SUBPIX_ORDER_NONE);
        privacy_image->Clear();
      }
      privacy_image->Fill(0xff, p_zones[i]->GetPolygon());
      privacy_image->Outline(0xff, p_zones[i]->GetPolygon());
    }
  } // end foreach zone
  if ( privacy_image )
    privacy_bitmask = privacy_image->Buffer();
}

Monitor::State Monitor::GetState() const {
  return (State)shared_data->state;
}

int Monitor::GetImage( int index, int scale ) {
  if ( index < 0 || index > image_buffer_count ) {
    index = shared_data->last_write_index;
  }
Debug(3, "GetImage");
  if ( index != image_buffer_count ) {
    Image *image;
    // If we are going to be modifying the snapshot before writing, then we need to copy it
    if ( ( scale != ZM_SCALE_BASE ) || ( !config.timestamp_on_capture ) ) {
      ZMPacket *snap = &image_buffer[index];
      Image *snap_image = snap->image;

      alarm_image.Assign( *snap_image );

      //write_image.Assign( *snap_image );

      if ( scale != ZM_SCALE_BASE ) {
        alarm_image.Scale( scale );
      }

      if ( !config.timestamp_on_capture ) {
        TimestampImage( &alarm_image, snap->timestamp );
      }
      image = &alarm_image;
    } else {
      image = image_buffer[index].image;
    }

    static char filename[PATH_MAX];
    snprintf( filename, sizeof(filename), "Monitor%d.jpg", id );
    image->WriteJpeg(filename);
  } else {
    Error( "Unable to generate image, no images in buffer" );
  }
  return 0;
}

ZMPacket *Monitor::getSnapshot(int index) const {

  if ( (index < 0) || (index > image_buffer_count) ) {
    index = shared_data->last_write_index;
  }
  return &image_buffer[index];

  return NULL;
}

struct timeval Monitor::GetTimestamp(int index) const {
  ZMPacket *packet = getSnapshot(index);
  if ( packet ) 
    return *packet->timestamp;

  static struct timeval null_tv = { 0, 0 };
  return null_tv;
}

unsigned int Monitor::GetLastReadIndex() const {
  return( shared_data->last_read_index!=(unsigned int)image_buffer_count?shared_data->last_read_index:-1 );
}

unsigned int Monitor::GetLastWriteIndex() const {
  return( shared_data->last_write_index!=(unsigned int)image_buffer_count?shared_data->last_write_index:-1 );
}

uint64_t Monitor::GetLastEventId() const {
  Debug(2, "mem_ptr(%x), size(%d) State(%d) last_read_index(%d) last_read_time(%d) last_event(%" PRIu64 ")",
      mem_ptr,
      shared_data->size,
      shared_data->state,
      shared_data->last_read_index,
      shared_data->last_read_time,
      shared_data->last_event_id
      );
  return shared_data->last_event_id;
}

// This function is crap.
double Monitor::GetFPS() const {
  return capture_fps;
  // last_write_index is the last capture index.  It starts as == image_buffer_count so that the first asignment % image_buffer_count = 0;
  int index1 = shared_data->last_write_index;
  if ( index1 >= image_buffer_count ) {
    // last_write_index only has this value on startup before capturing anything.
    return 0.0;
  }
  Debug(2, "index1(%d)", index1);
  ZMPacket *snap1 = &image_buffer[index1];
  if ( !snap1->timestamp->tv_sec ) {
    // This should be impossible
    Warning("Impossible situation.  No timestamp on captured image index was %d, image-buffer_count was (%d)", index1, image_buffer_count);
    return 0.0;
  }
  struct timeval time1 = *snap1->timestamp;

  int fps_image_count = image_buffer_count;

  int index2 = (index1+1)%image_buffer_count;
  Debug(2, "index2(%d)", index2);
  ZMPacket *snap2 = &image_buffer[index2];
  // the timestamp pointers are initialized on connection, so that's redundant
  // tv_sec is probably only zero during the first loop of capturing, so this basically just counts the unused images.
  // The problem is that there is no locking, and we set the timestamp before we set last_write_index,
  // so there is a small window where the next image can have a timestamp in the future
  while ( !snap2->timestamp->tv_sec || tvDiffSec(*snap2->timestamp, *snap1->timestamp) < 0 ) {
    if ( index1 == index2 ) {
      // All images are uncaptured
      return 0.0;
    }
    index2 = (index2+1)%image_buffer_count;
    snap2 = &image_buffer[ index2 ];
    fps_image_count--;
  }
  struct timeval time2 = *snap2->timestamp;

  double time_diff = tvDiffSec( time2, time1 );
  if ( ! time_diff ) {
    Error( "No diff between time_diff = %lf (%d:%ld.%ld - %d:%ld.%ld), ibc: %d", time_diff, index2, time2.tv_sec, time2.tv_usec, index1, time1.tv_sec, time1.tv_usec, image_buffer_count );
    return 0.0;
  }
  double curr_fps = fps_image_count/time_diff;

  if ( curr_fps < 0.0 ) {
    Error( "Negative FPS %f, time_diff = %lf (%d:%ld.%ld - %d:%ld.%ld), ibc: %d",
        curr_fps, time_diff, index2, time2.tv_sec, time2.tv_usec, index1, time1.tv_sec, time1.tv_usec, image_buffer_count );
    return 0.0;
  } else {
    Debug( 2, "GetFPS %f, time_diff = %lf (%d:%ld.%ld - %d:%ld.%ld), ibc: %d", curr_fps, time_diff, index2, time2.tv_sec, time2.tv_usec, index1, time1.tv_sec, time1.tv_usec, image_buffer_count );
  }
  return curr_fps;
}

/* I think this returns the # of micro seconds that we should sleep in order to maintain the desired analysis rate */
useconds_t Monitor::GetAnalysisRate() {
  capture_fps = GetFPS();
  if ( !analysis_fps_limit ) {
    return 0;
  } else if ( analysis_fps_limit > capture_fps ) {
    Warning( "Analysis fps (%.2f) is greater than capturing fps (%.2f)", analysis_fps_limit, capture_fps );
    return 0;
  } else {
    return( ( 1000000 / analysis_fps_limit ) - ( 1000000 / capture_fps ) );
  }
}

void Monitor::UpdateAdaptiveSkip() {
  if ( config.opt_adaptive_skip ) {
    double capturing_fps = GetFPS();
    if ( adaptive_skip && analysis_fps && ( analysis_fps < capturing_fps ) ) {
      Info( "Analysis fps (%.2f) is lower than capturing fps (%.2f), disabling adaptive skip feature", analysis_fps, capturing_fps );
      adaptive_skip = false;
    } else if ( !adaptive_skip && ( !analysis_fps || ( analysis_fps >= capturing_fps ) ) ) {
      Info( "Enabling adaptive skip feature" );
      adaptive_skip = true;
    }
  } else {
    adaptive_skip = false;
  }
}

void Monitor::ForceAlarmOn( int force_score, const char *force_cause, const char *force_text ) {
  trigger_data->trigger_state = TRIGGER_ON;
  trigger_data->trigger_score = force_score;
  strncpy( trigger_data->trigger_cause, force_cause, sizeof(trigger_data->trigger_cause)-1 );
  strncpy( trigger_data->trigger_text, force_text, sizeof(trigger_data->trigger_text)-1 );
}

void Monitor::ForceAlarmOff() {
  trigger_data->trigger_state = TRIGGER_OFF;
}

void Monitor::CancelForced() {
  trigger_data->trigger_state = TRIGGER_CANCEL;
}

void Monitor::actionReload() {
  shared_data->action |= RELOAD;
}

void Monitor::actionEnable() {
  shared_data->action |= RELOAD;

  db_mutex.lock();
  static char sql[ZM_SQL_SML_BUFSIZ];
  snprintf(sql, sizeof(sql), "UPDATE Monitors SET Enabled = 1 WHERE Id = %d", id);
  if ( mysql_query(&dbconn, sql) ) {
    Error("Can't run query: %s", mysql_error(&dbconn));
  }
  db_mutex.unlock();
}

void Monitor::actionDisable() {
  shared_data->action |= RELOAD;

  static char sql[ZM_SQL_SML_BUFSIZ];
  snprintf(sql, sizeof(sql), "update Monitors set Enabled = 0 where Id = %d", id);
  db_mutex.lock();
  if ( mysql_query(&dbconn, sql) ) {
    Error("Can't run query: %s", mysql_error(&dbconn));
  }
  db_mutex.unlock();
}

void Monitor::actionSuspend() {
  shared_data->action |= SUSPEND;
}

void Monitor::actionResume() {
  shared_data->action |= RESUME;
}

int Monitor::actionBrightness( int p_brightness ) {
  if ( purpose != CAPTURE ) {
    if ( p_brightness >= 0 ) {
      shared_data->brightness = p_brightness;
      shared_data->action |= SET_SETTINGS;
      int wait_loops = 10;
      while ( shared_data->action & SET_SETTINGS ) {
        if ( wait_loops-- ) {
          usleep( 100000 );
        } else {
          Warning( "Timed out waiting to set brightness" );
          return( -1 );
        }
      }
    } else {
      shared_data->action |= GET_SETTINGS;
      int wait_loops = 10;
      while ( shared_data->action & GET_SETTINGS ) {
        if ( wait_loops-- ) {
          usleep( 100000 );
        } else {
          Warning( "Timed out waiting to get brightness" );
          return( -1 );
        }
      }
    }
    return( shared_data->brightness );
  }
  return( camera->Brightness( p_brightness ) );
}

int Monitor::actionContrast( int p_contrast ) {
  if ( purpose != CAPTURE ) {
    if ( p_contrast >= 0 ) {
      shared_data->contrast = p_contrast;
      shared_data->action |= SET_SETTINGS;
      int wait_loops = 10;
      while ( shared_data->action & SET_SETTINGS ) {
        if ( wait_loops-- ) {
          usleep( 100000 );
        } else {
          Warning( "Timed out waiting to set contrast" );
          return( -1 );
        }
      }
    } else {
      shared_data->action |= GET_SETTINGS;
      int wait_loops = 10;
      while ( shared_data->action & GET_SETTINGS ) {
        if ( wait_loops-- ) {
          usleep( 100000 );
        } else {
          Warning( "Timed out waiting to get contrast" );
          return( -1 );
        }
      }
    }
    return( shared_data->contrast );
  }
  return( camera->Contrast( p_contrast ) );
}

int Monitor::actionHue( int p_hue ) {
  if ( purpose != CAPTURE ) {
    if ( p_hue >= 0 ) {
      shared_data->hue = p_hue;
      shared_data->action |= SET_SETTINGS;
      int wait_loops = 10;
      while ( shared_data->action & SET_SETTINGS ) {
        if ( wait_loops-- ) {
          usleep( 100000 );
        } else {
          Warning( "Timed out waiting to set hue" );
          return( -1 );
        }
      }
    } else {
      shared_data->action |= GET_SETTINGS;
      int wait_loops = 10;
      while ( shared_data->action & GET_SETTINGS ) {
        if ( wait_loops-- ) {
          usleep( 100000 );
        } else {
          Warning( "Timed out waiting to get hue" );
          return( -1 );
        }
      }
    }
    return( shared_data->hue );
  }
  return( camera->Hue( p_hue ) );
}

int Monitor::actionColour( int p_colour ) {
  if ( purpose != CAPTURE ) {
    if ( p_colour >= 0 ) {
      shared_data->colour = p_colour;
      shared_data->action |= SET_SETTINGS;
      int wait_loops = 10;
      while ( shared_data->action & SET_SETTINGS ) {
        if ( wait_loops-- ) {
          usleep( 100000 );
        } else {
          Warning( "Timed out waiting to set colour" );
          return( -1 );
        }
      }
    } else {
      shared_data->action |= GET_SETTINGS;
      int wait_loops = 10;
      while ( shared_data->action & GET_SETTINGS ) {
        if ( wait_loops-- ) {
          usleep( 100000 );
        } else {
          Warning( "Timed out waiting to get colour" );
          return( -1 );
        }
      }
    }
    return( shared_data->colour );
  }
  return( camera->Colour( p_colour ) );
}

void Monitor::DumpZoneImage( const char *zone_string ) {
  int exclude_id = 0;
  int extra_colour = 0;
  Polygon extra_zone;

  if ( zone_string ) {
    if ( !Zone::ParseZoneString(zone_string, exclude_id, extra_colour, extra_zone) ) {
      Error("Failed to parse zone string, ignoring");
    }
  }

  Image *zone_image = NULL;
  if ( ( (!staticConfig.SERVER_ID) || ( staticConfig.SERVER_ID == server_id ) ) && mem_ptr ) {
    Debug(3, "Trying to load from local zmc");
    int index = shared_data->last_write_index;
    ZMPacket *snap = getSnapshot(index);
    zone_image = new Image(*snap->image);
  } else {
    Debug(3, "Trying to load from event");
    // Grab the most revent event image
    std::string sql = stringtf("SELECT MAX(Id) FROM Events WHERE MonitorId=%d AND Frames > 0", id);
    zmDbRow eventid_row;
    if ( eventid_row.fetch(sql.c_str()) ) {
      uint64_t event_id = atoll(eventid_row[0]);

      Debug(3, "Got event %" PRIu64, event_id);
      EventStream *stream = new EventStream();
      stream->setStreamStart(event_id, (unsigned int)1);
      zone_image = stream->getImage();
      delete stream;
      stream = NULL;
    } else {
      Error("Unable to load an event for monitor %d", id);
      return;
    }
  }

  if ( zone_image->Colours() == ZM_COLOUR_GRAY8 ) {
    zone_image->Colourise(ZM_COLOUR_RGB24, ZM_SUBPIX_ORDER_RGB);
  }

  for( int i = 0; i < n_zones; i++ ) {
    if ( exclude_id && (!extra_colour || extra_zone.getNumCoords()) && zones[i]->Id() == exclude_id )
      continue;

    Rgb colour;
    if ( exclude_id && !extra_zone.getNumCoords() && zones[i]->Id() == exclude_id ) {
      colour = extra_colour;
    } else {
      if ( zones[i]->IsActive() ) {
        colour = RGB_RED;
      } else if ( zones[i]->IsInclusive() ) {
        colour = RGB_ORANGE;
      } else if ( zones[i]->IsExclusive() ) {
        colour = RGB_PURPLE;
      } else if ( zones[i]->IsPreclusive() ) {
        colour = RGB_BLUE;
      } else {
        colour = RGB_WHITE;
      }
    }
    zone_image->Fill( colour, 2, zones[i]->GetPolygon() );
    zone_image->Outline( colour, zones[i]->GetPolygon() );
  }

  if ( extra_zone.getNumCoords() ) {
    zone_image->Fill( extra_colour, 2, extra_zone );
    zone_image->Outline( extra_colour, extra_zone );
  }

  static char filename[PATH_MAX];
  snprintf( filename, sizeof(filename), "Zones%d.jpg", id );
  zone_image->WriteJpeg( filename );
  delete zone_image;
}

void Monitor::DumpImage( Image *dump_image ) const {
  if ( image_count && !(image_count%10) ) {
    static char filename[PATH_MAX];
    static char new_filename[PATH_MAX];
    snprintf( filename, sizeof(filename), "Monitor%d.jpg", id );
    snprintf( new_filename, sizeof(new_filename), "Monitor%d-new.jpg", id );
    if ( dump_image->WriteJpeg( new_filename ) )
      rename( new_filename, filename );
  }
}

bool Monitor::CheckSignal( const Image *image ) {
  static bool static_undef = true;
  /* RGB24 colors */
  static uint8_t red_val;
  static uint8_t green_val;
  static uint8_t blue_val;
  static uint8_t grayscale_val; /* 8bit grayscale color */
  static Rgb colour_val; /* RGB32 color */
  static int usedsubpixorder;

  if ( signal_check_points > 0 ) {
    if ( static_undef ) {
      static_undef = false;
      usedsubpixorder = camera->SubpixelOrder();
      colour_val = rgb_convert(signal_check_colour, ZM_SUBPIX_ORDER_BGR); /* HTML colour code is actually BGR in memory, we want RGB */
      colour_val = rgb_convert(colour_val, usedsubpixorder);
      red_val = RED_VAL_BGRA(signal_check_colour);
      green_val = GREEN_VAL_BGRA(signal_check_colour);
      blue_val = BLUE_VAL_BGRA(signal_check_colour);
      grayscale_val = signal_check_colour & 0xff; /* Clear all bytes but lowest byte */
    }

    const uint8_t *buffer = image->Buffer();
    int pixels = image->Pixels();
    int width = image->Width();
    int colours = image->Colours();

    int index = 0;
    for ( int i = 0; i < signal_check_points; i++ ) {
      while( true ) {
        // Why the casting to long long? also note that on a 64bit cpu, long long is 128bits
        index = (int)(((long long)rand()*(long long)(pixels-1))/RAND_MAX);
        if ( !config.timestamp_on_capture || !label_format[0] )
          break;
        // Avoid sampling the rows with timestamp in
        if ( index < (label_coord.Y()*width) || index >= (label_coord.Y()+Image::LINE_HEIGHT)*width )
          break;
      }

      if ( colours == ZM_COLOUR_GRAY8 ) {
        if ( *(buffer+index) != grayscale_val )
          return true;

      } else if ( colours == ZM_COLOUR_RGB24 ) {
        const uint8_t *ptr = buffer+(index*colours);

        if ( usedsubpixorder == ZM_SUBPIX_ORDER_BGR ) {
          if ( (RED_PTR_BGRA(ptr) != red_val) || (GREEN_PTR_BGRA(ptr) != green_val) || (BLUE_PTR_BGRA(ptr) != blue_val) )
            return true;
        } else {
          /* Assume RGB */
          if ( (RED_PTR_RGBA(ptr) != red_val) || (GREEN_PTR_RGBA(ptr) != green_val) || (BLUE_PTR_RGBA(ptr) != blue_val) )
            return true;
        }

      } else if ( colours == ZM_COLOUR_RGB32 ) {
        if ( usedsubpixorder == ZM_SUBPIX_ORDER_ARGB || usedsubpixorder == ZM_SUBPIX_ORDER_ABGR ) {
          if ( ARGB_ABGR_ZEROALPHA(*(((const Rgb*)buffer)+index)) != ARGB_ABGR_ZEROALPHA(colour_val) )
            return true;
        } else {
          /* Assume RGBA or BGRA */
          if ( RGBA_BGRA_ZEROALPHA(*(((const Rgb*)buffer)+index)) != RGBA_BGRA_ZEROALPHA(colour_val) )
            return true;
        }
      }
    } // end for < signal_check_points
    Debug(1,"SignalCheck: %d points, colour_val(%d)", signal_check_points, colour_val);
    return false;
  } // end if signal_check_points
  return true;
}

void Monitor::CheckAction() {
  struct timeval now;
  gettimeofday(&now, NULL);
  if ( shared_data->action ) {
    // Can there be more than 1 bit set in the action?  Shouldn't these be elseifs?
    if ( shared_data->action & RELOAD ) {
      Info("Received reload indication at count %d", image_count);
      shared_data->action &= ~RELOAD;
      Reload();
    }
    if ( shared_data->action & SUSPEND ) {
      if ( Active() ) {
        Info("Received suspend indication at count %d", image_count);
        shared_data->active = false;
        //closeEvent();
      } else {
        Info("Received suspend indication at count %d, but wasn't active", image_count);
      }
      if ( config.max_suspend_time ) {
        auto_resume_time = now.tv_sec + config.max_suspend_time;
      }
      shared_data->action &= ~SUSPEND;
    } else if ( shared_data->action & RESUME ) {
      if ( Enabled() && !Active() ) {
        Info("Received resume indication at count %d", image_count);
        shared_data->active = true;
        ref_image = *(image_buffer[shared_data->last_write_index].image);
        ready_count = image_count+(warmup_count/2);
        shared_data->alarm_x = shared_data->alarm_y = -1;
      }
      shared_data->action &= ~RESUME;
    }
  } // end if shared_data->action

  if ( auto_resume_time && (now.tv_sec >= auto_resume_time) ) {
    Info("Auto resuming at count %d", image_count);
    shared_data->active = true;
    ref_image = *(image_buffer[shared_data->last_write_index].image);
    ready_count = image_count+(warmup_count/2);
    auto_resume_time = 0;
  }
}

void Monitor::UpdateAnalysisFPS() {
  if ( analysis_image_count && fps_report_interval && !(analysis_image_count%fps_report_interval) ) {
    struct timeval now;
    gettimeofday(&now, NULL);
    double new_analysis_fps = double(fps_report_interval)/(now.tv_sec - last_analysis_fps_time);
    Info("%s: %d - Analysing at %.2f fps", name, analysis_image_count, new_analysis_fps);
    if ( new_analysis_fps != analysis_fps ) {
      analysis_fps = new_analysis_fps;

      char sql[ZM_SQL_SML_BUFSIZ];
      snprintf(sql, sizeof(sql),
          "INSERT INTO Monitor_Status (MonitorId,AnalysisFPS) VALUES (%d, %.2lf)"
          " ON DUPLICATE KEY UPDATE AnalysisFPS = %.2lf",
          id, analysis_fps, analysis_fps);
      db_mutex.lock();
      if ( mysql_query(&dbconn, sql) ) {
        Error("Can't run query: %s", mysql_error(&dbconn));
      }
      db_mutex.unlock();
      last_analysis_fps_time = now.tv_sec;
    }
  }
}

// Would be nice if this JUST did analysis
// This idea is that we should be analysing as close to the capture frame as possible.
// This function should process as much as possible before returning
//
// If there is an event, the we should do our best to empty the queue.
// If there isn't then we keep pre-event + alarm frames. = pre_event_count
bool Monitor::Analyse() {
  // last_write_index is the last capture
  // last_read_index is the last analysis
  
  if ( !Enabled() ) {
    Warning("Shouldn't be doing Analyze when not Enabled");
    return false;
  }

  // if  have event, send frames until we find a video packet, at which point do analysis. Adaptive skip should only affect which frames we do analysis on.

  int packets_processed = 0;

  ZMPacket *snap;
  // Is it possible for snap->score to be ! -1?
  // get_analysis_packet will lock the packet
  while ( ( snap = packetqueue->get_analysis_packet() ) && ( snap->score == -1 ) ) {
    unsigned int index = snap->image_index;

    packets_processed += 1;

    if ( snap->packet.stream_index != video_stream_id ) {
      snap->unlock();
      Debug(2, "skipping because audio");
      // We want to skip, but if we return, we may sleep.
      if ( !packetqueue->increment_analysis_it() ) {
        Debug(2, "No more packets to analyse");
        return false;
      }
      continue;
    }
    struct timeval *timestamp = snap->timestamp;
    Image *snap_image = snap->image;

    // signal is set by capture
    bool signal = shared_data->signal;
    bool signal_change = (signal != last_signal);
    Debug(3, "Motion detection is enabled signal(%d) signal_change(%d)", signal, signal_change);

    // if we have been told to be OFF, then we are off and don't do any processing.
    if ( trigger_data->trigger_state != TRIGGER_OFF ) {
      Debug(4, "Trigger not oFF state is (%d)", trigger_data->trigger_state);
      unsigned int score = 0;
      // Ready means that we have captured the warmpup # of frames
      if ( Ready() ) {
        Debug(4, "Ready");
        std::string cause;
        Event::StringSetMap noteSetMap;

        // Specifically told to be on.  Setting the score here will trigger the alarm.
        if ( trigger_data->trigger_state == TRIGGER_ON ) {
          score += trigger_data->trigger_score;
          if ( !event ) {
            if ( cause.length() )
              cause += ", ";
            cause += trigger_data->trigger_cause;
          }
          Event::StringSet noteSet;
          noteSet.insert( trigger_data->trigger_text );
          noteSetMap[trigger_data->trigger_cause] = noteSet;
        } // end if trigger_on

        if ( signal_change ) {
          const char *signalText;
          if ( !signal ) {
            signalText = "Lost";
            if ( event ) {
              Info("%s: %03d - Closing event %" PRIu64 ", signal loss", name, analysis_image_count, event->Id());
              closeEvent();
              last_section_mod = 0;
            }
          } else {
            signalText = "Reacquired";
            score += 100;
          }
          Warning("%s: %s", SIGNAL_CAUSE, signalText);
          if ( !event ) {
            if ( cause.length() )
              cause += ", ";
            cause += SIGNAL_CAUSE;
          }
          Event::StringSet noteSet;
          noteSet.insert( signalText );
          noteSetMap[SIGNAL_CAUSE] = noteSet;
          shared_data->state = state = IDLE;
          shared_data->active = signal;
          ref_image = *snap_image;

        }// else 

        if ( signal ) {
          if ( Active() && (function == MODECT || function == MOCORD) ) {
            Debug(3, "signal and active and modect");
            Event::StringSet zoneSet;
            int motion_score = last_motion_score;
            if ( !(analysis_image_count % (motion_frame_skip+1) ) ) {
              if ( snap->image ) {

                // Get new score.
                Debug(3,"before DetectMotion packet index is %d", snap->image_index);
                motion_score = DetectMotion( *snap_image, zoneSet );

                Debug(3, "After motion detection, last_motion_score(%d), new motion score(%d)", last_motion_score, motion_score);
              } else {
                Warning("No image in snap");
              }
              // Why are we updating the last_motion_score too?
              last_motion_score = motion_score;
            }
            if ( motion_score ) {
              score += motion_score;
              if ( !event ) {
                if ( cause.length() )
                  cause += ", ";
                cause += MOTION_CAUSE;
              }
              noteSetMap[MOTION_CAUSE] = zoneSet;
            } // end if motion_score
            shared_data->active = signal;
          } // if ( Active() && (function == MODECT || function == MOCORD) )

          // If we aren't recording, check linked monitors to see if we should be.
          if ( n_linked_monitors > 0 ) {
            Debug(2, "Checking linked monitors");
            Event::StringSet noteSet;
            for ( int i=0; i < n_linked_monitors; i++ ) {
              if ( ! linked_monitors[i]->isConnected() )
                linked_monitors[i]->connect();

              if ( linked_monitors[i]->isConnected() && linked_monitors[i]->hasAlarmed() ) {
                if ( !event ) {
                  if ( cause.length() )
                    cause += ", ";
                  cause += LINKED_CAUSE;
                }
                noteSet.insert(linked_monitors[i]->Name());
                score += 50;
              }
            } // end foreach linked_monitor
            if ( noteSet.size() > 0 )
              noteSetMap[LINKED_CAUSE] = noteSet;
          } // end if linked_monitors

          if ( function == RECORD || function == MOCORD ) {
            // If doing record, check to see if we need to close the event or not.

            if ( event ) {
              Debug(2,"Have event in mocard");
              if ( section_length ) {
                // TODO: Wouldn't this be clearer if we just did something like if now - event->start > section_length ?
                int section_mod = timestamp->tv_sec % section_length;
                Debug(3, "Section length (%d) Last Section Mod(%d), tv_sec(%d) new section mod(%d)",
                    section_length, last_section_mod, timestamp->tv_sec, section_mod);
                // This is not clear, but basically due to pauses, etc we might not get section_mod == 0
                if ( ( section_mod < last_section_mod ) && ( timestamp->tv_sec >= 10 ) ) {
                  Info("%s: %03d - Closing event %llu, section end forced ", name, analysis_image_count, event->Id());
                  closeEvent();
                  last_section_mod = 0;
                } else {
                  last_section_mod = section_mod;
                }
              } // end if section_length
            } // end if event

            if ( !event ) {
              Debug(2,"Creating continuous event");
              // Create event
              event = new Event(this, *timestamp, "Continuous", noteSetMap);
              shared_data->last_event_id = event->Id();

              // lets construct alarm cause. It will contain cause + names of zones alarmed
              std::string alarm_cause = "Continuous";
              for ( int i=0; i < n_zones; i++ ) {
                if (zones[i]->Alarmed()) {
                  alarm_cause += std::string(zones[i]->Label());
                  if ( i < n_zones-1 ) {
                    alarm_cause +=",";
                  }
                }
              } 
              alarm_cause = cause+" "+alarm_cause;
              strncpy(shared_data->alarm_cause,alarm_cause.c_str() , sizeof(shared_data->alarm_cause));
              video_store_data->recording = event->StartTime();
              Info("%s: %03d - Opening new event %" PRIu64 ", section start",
                  name, analysis_image_count, event->Id());
              /* To prevent cancelling out an existing alert\prealarm\alarm state */
              if ( state == IDLE ) {
                shared_data->state = state = TAPE;
              }
            } // end if ! event
          } // end if RECORDING

          if ( score ) {
            Debug(9, "Score: (%d)", score);
            if ( state == IDLE || state == TAPE || state == PREALARM ) {
              if ( Event::PreAlarmCount() >= (alarm_frame_count-1) ) {
                Info("%s: %03d - Gone into alarm state", name, analysis_image_count);
                shared_data->state = state = ALARM;
                if ( !event ) {
                  // lets construct alarm cause. It will contain cause + names of zones alarmed
                  std::string alarm_cause = "";
                  for ( int i=0; i < n_zones; i++) {
                    if (zones[i]->Alarmed()) {
                      alarm_cause += std::string(zones[i]->Label());
                      if (i < n_zones-1) {
                        alarm_cause +=",";
                      }
                    }
                  } 
                  alarm_cause = cause+" "+alarm_cause;
                  strncpy(shared_data->alarm_cause,alarm_cause.c_str() , sizeof(shared_data->alarm_cause));
                  event = new Event(this, *timestamp, cause, noteSetMap);
                  shared_data->last_event_id = event->Id();
                }
              } else if ( state != PREALARM ) {
                Info("%s: %03d - Gone into prealarm state", name, analysis_image_count);
                shared_data->state = state = PREALARM;
              }
            } else if ( state == ALERT ) {
              Info("%s: %03d - Gone back into alarm state", name, analysis_image_count);
              shared_data->state = state = ALARM;
            }
            last_alarm_count = analysis_image_count;
          } else { // no score?
            if ( state == ALARM ) {
              Info("%s: %03d - Gone into alert state", name, analysis_image_count);
              shared_data->state = state = ALERT;
            } else if ( state == ALERT ) {
              if ( analysis_image_count-last_alarm_count > post_event_count ) {
                Info("%s: %03d - Left alarm state (%" PRIu64 ") - %d(%d) images",
                    name, analysis_image_count, event->Id(), event->Frames(), event->AlarmFrames());
                //if ( function != MOCORD || event_close_mode == CLOSE_ALARM || event->Cause() == SIGNAL_CAUSE )
                if ( (function != RECORD && function != MOCORD ) || event_close_mode == CLOSE_ALARM ) {
                  shared_data->state = state = IDLE;
                  Info("%s: %03d - Closing event %" PRIu64 ", alarm end%s",
                      name, analysis_image_count, event->Id(), (function==MOCORD)?", section truncated":"" );
                  closeEvent();
                } else {
                  shared_data->state = state = TAPE;
                }
              }
            } else if ( state == PREALARM ) {
              // Back to IDLE
              shared_data->state = state =  function != MOCORD ? IDLE : TAPE;
            }
            if ( Event::PreAlarmCount() )
              Event::EmptyPreAlarmFrames();
          } // end if score or not

          // Flag the packet so we don't analyse it again
          snap->score = score;

          if ( state == PREALARM || state == ALARM ) {
            if ( config.create_analysis_images ) {
              bool got_anal_image = false;
              Image *anal_image = new Image( *snap_image );
              //alarm_image.Assign( *snap_image );
              for( int i = 0; i < n_zones; i++ ) {
                if ( zones[i]->Alarmed() ) {
                  if ( zones[i]->AlarmImage() ) {
                    anal_image->Overlay( *(zones[i]->AlarmImage()) );
                    got_anal_image = true;
                  }
                  if ( config.record_event_stats || state == ALARM ) {
                    zones[i]->RecordStats( event );
                  }
                }
              } // end foreach zone
              if ( got_anal_image ) {
                snap->analysis_image = anal_image;
              } else {
                delete anal_image;
              }
            } else if ( config.record_event_stats && state == ALARM ) {
              for ( int i = 0; i < n_zones; i++ ) {
                if ( zones[i]->Alarmed() ) {
                  zones[i]->RecordStats( event );
                }
              } // end foreach zone
            } // analsys_images or record stats

            if ( noteSetMap.size() > 0 )
              event->updateNotes( noteSetMap );
          } else if ( state == ALERT ) {
            // Alert means this frame has no motion, but we were alarmed and are still recording.
            if ( noteSetMap.size() > 0 )
              event->updateNotes( noteSetMap );
          //} else if ( state == TAPE ) {
            //if ( !(analysis_image_count%(frame_skip+1)) ) {
              //if ( config.bulk_frame_interval > 1 ) {
                //event->AddFrame( snap_image, *timestamp, (event->Frames()<pre_event_count?0:-1) );
              //} else {
                //event->AddFrame( snap_image, *timestamp );
              //}
            //}
          }
          if ( function == MODECT || function == MOCORD ) {
            ref_image.Blend( *snap_image, ( state==ALARM ? alarm_ref_blend_perc : ref_blend_perc ) );
          }
          last_signal = signal;
        } // end if signal

      } else {
        Debug(3,"Not ready?");
        snap->unlock();
        return false;
      }
    } else {
      Debug(3, "trigger == off");
      if ( event ) {
        Info("%s: %03d - Closing event %" PRIu64 ", trigger off", name, analysis_image_count, event->Id());
        closeEvent();
      }
      shared_data->state = state = IDLE;
      last_section_mod = 0;
      trigger_data->trigger_state = TRIGGER_CANCEL;
    } // end if ( trigger_data->trigger_state != TRIGGER_OFF )

    if ( event ) {
      int last_write = shared_data->last_write_index;
      int written = 0;
      ZMPacket *queued_packet;
      //popPacket will increment analysis_it if neccessary, so this will write out all packets in queue
      // We can't just loop here forever, because we may be capturing just as fast, and never leave the loop.
      // Only loop until we hit the analysis index
      while ( ( queued_packet = packetqueue->popPacket() ) ) {
        if ( snap == queued_packet ) {
          Debug(2,"adding snap packet (%d) qp last_write_index(%d), written(%d)", queued_packet->image_index, last_write, written );
          event->AddPacket(queued_packet);
          // Pop may have already incrememented it
          //packetqueue->increment_analysis_it();
          break;
        } else {
          queued_packet->lock();
          Debug(2,"adding queued packet (%d) qp last_write_index(%d), written(%d)", queued_packet->image_index, last_write, written );
          event->AddPacket(queued_packet);
          queued_packet->unlock();
        }
        written ++;
        if ( queued_packet->image_index == -1 ) {
          delete queued_packet;
        } 
        // encoding can take a long time, so 
        shared_data->last_read_time = time(NULL);
      } // end while write out queued_packets
      queued_packet = NULL;
    } else {
      packetqueue->increment_analysis_it();
    }

    shared_data->last_read_index = snap->image_index;
    shared_data->last_read_time = time(NULL);
    analysis_image_count++;
    snap->unlock();
  } // end while not at end of packetqueue
  UpdateAnalysisFPS();
  if ( packets_processed > 0 )
    return true;
  return false;
} // end Monitor::Analyze

void Monitor::Reload() {
  Debug(1, "Reloading monitor %s", name);

  if ( event ) {
    Info( "%s: %03d - Closing event %" PRIu64 ", reloading", name, image_count, event->Id() );
    closeEvent();
  }

  std::string sql = load_monitor_sql + stringtf(" WHERE Id=%d", id);
  zmDbRow *row = zmDbFetchOne(sql.c_str());
  if ( !row ) {
    Error("Can't run query: %s", mysql_error(&dbconn));
  } else if ( MYSQL_ROW dbrow = row->mysql_row() ) {
    Load(dbrow,1,purpose);

    shared_data->state = state = IDLE;
    shared_data->alarm_x = shared_data->alarm_y = -1;
    if ( enabled )
      shared_data->active = true;
    ready_count = image_count+warmup_count;

    delete row;
  } // end if row

  ReloadZones();
} // void Monitor::Reload()

void Monitor::ReloadZones() {
  Debug(1, "Reloading zones for monitor %s", name);
  for( int i = 0; i < n_zones; i++ ) {
    delete zones[i];
  }
  delete[] zones;
  zones = 0;
  n_zones = Zone::Load(this, zones);
  //DumpZoneImage();
} // end void Monitor::ReloadZones()

void Monitor::ReloadLinkedMonitors(const char *p_linked_monitors) {
  Debug(1, "Reloading linked monitors for monitor %s, '%s'", name, p_linked_monitors);
  if ( n_linked_monitors ) {
    for( int i=0; i < n_linked_monitors; i++ ) {
      delete linked_monitors[i];
    }
    delete[] linked_monitors;
    linked_monitors = 0;
  }

  n_linked_monitors = 0;
  if ( p_linked_monitors ) {
    int n_link_ids = 0;
    unsigned int link_ids[256];

    // This nasty code picks out strings of digits from p_linked_monitors and tries to load them. 
    char link_id_str[8];
    char *dest_ptr = link_id_str;
    const char *src_ptr = p_linked_monitors;
    while( 1 ) {
      dest_ptr = link_id_str;
      while( *src_ptr >= '0' && *src_ptr <= '9' ) {
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

        db_mutex.lock();
        static char sql[ZM_SQL_SML_BUFSIZ];
        snprintf(sql, sizeof(sql), "SELECT Id, Name FROM Monitors WHERE Id = %d AND Function != 'None' AND Function != 'Monitor' AND Enabled = 1", link_ids[i] );
        if ( mysql_query(&dbconn, sql) ) {
					db_mutex.unlock();
          Error("Can't run query: %s", mysql_error(&dbconn));
          continue;
        }

        MYSQL_RES *result = mysql_store_result(&dbconn);
        db_mutex.unlock();
        if ( !result ) {
          Error("Can't use query result: %s", mysql_error(&dbconn));
          continue;
        }
        int n_monitors = mysql_num_rows(result);
        if ( n_monitors == 1 ) {
          MYSQL_ROW dbrow = mysql_fetch_row(result);
          Debug(1, "Linking to monitor %d", link_ids[i]);
          linked_monitors[count++] = new MonitorLink(link_ids[i], dbrow[1]);
        } else {
          Warning("Can't link to monitor %d, invalid id, function or not enabled", link_ids[i]);
        }
        mysql_free_result(result);
      } // end foreach link_id
      n_linked_monitors = count;
    } // end if has link_ids
  } // end if p_linked_monitors
} // end void Monitor::ReloadLinkedMonitors(const char *p_linked_monitors)

int Monitor::LoadMonitors(std::string sql, Monitor **&monitors, Purpose purpose) {

  Debug(1, "Loading Monitors with %s", sql.c_str());

  MYSQL_RES *result = zmDbFetch(sql.c_str());
  if ( !result ) {
    Error("Can't load local monitors: %s", mysql_error(&dbconn));
    return 0;
  }
  int n_monitors = mysql_num_rows(result);
  Debug( 1, "Got %d monitors", n_monitors );
  delete[] monitors;
  monitors = new Monitor *[n_monitors];
  for( int i=0; MYSQL_ROW dbrow = mysql_fetch_row(result); i++ ) {
    monitors[i] = new Monitor();
    monitors[i]->Load(dbrow, true, purpose);
    // need to load zones and set Purpose, 1, purpose);
  }
  if ( mysql_errno(&dbconn) ) {
    Error("Can't fetch row: %s", mysql_error(&dbconn));
    return 0;
  }
  mysql_free_result(result);

  return n_monitors;
}

#if ZM_HAS_V4L
int Monitor::LoadLocalMonitors(const char *device, Monitor **&monitors, Purpose purpose) {

  std::string sql = load_monitor_sql + " WHERE Function != 'None' AND Type = 'Local'";

  if ( device[0] )
    sql += " AND Device='" + std::string(device) + "'";
  if ( staticConfig.SERVER_ID )
    sql += stringtf(" AND ServerId=%d", staticConfig.SERVER_ID);
  return LoadMonitors(sql, monitors, purpose);
}
#endif // ZM_HAS_V4L

int Monitor::LoadRemoteMonitors(const char *protocol, const char *host, const char *port, const char *path, Monitor **&monitors, Purpose purpose) {
  std::string sql = load_monitor_sql + " WHERE Function != 'None' AND Type = 'Remote'";
  if ( staticConfig.SERVER_ID )
    sql += stringtf(" AND ServerId=%d", staticConfig.SERVER_ID);

  if ( protocol )
    sql += stringtf(" AND Protocol = '%s' and Host = '%s' and Port = '%s' and Path = '%s'", protocol, host, port, path);
  return LoadMonitors(sql, monitors, purpose);
}

int Monitor::LoadFileMonitors( const char *file, Monitor **&monitors, Purpose purpose ) {
  std::string sql = load_monitor_sql + " WHERE Function != 'None' AND Type = 'File'";
  if ( file[0] )
    sql += " AND Path='" + std::string(file) + "'";
  if ( staticConfig.SERVER_ID ) {
    sql += stringtf( " AND ServerId=%d", staticConfig.SERVER_ID );
  }
  return LoadMonitors(sql, monitors, purpose);
}

#if HAVE_LIBAVFORMAT
int Monitor::LoadFfmpegMonitors(const char *file, Monitor **&monitors, Purpose purpose) {
  std::string sql = load_monitor_sql + " WHERE Function != 'None' AND Type = 'Ffmpeg'";
  if ( file[0] )
    sql += " AND Path = '" + std::string(file) + "'";

  if ( staticConfig.SERVER_ID ) {
    sql += stringtf(" AND ServerId=%d", staticConfig.SERVER_ID);
  }
  return LoadMonitors(sql, monitors, purpose);
}
#endif // HAVE_LIBAVFORMAT

/* Returns 0 on success, even if no new images are available (transient error)
 * Returns -1 on failure.
 */
int Monitor::Capture() {
  static int FirstCapture = 1; // Used in de-interlacing to indicate whether this is the even or odd image

  unsigned int index = 0;
  //image_count % image_buffer_count;

  ZMPacket *packet = new ZMPacket();
  packet->timestamp = image_buffer[index].timestamp;
  packet->image_index = image_count;

  //&image_buffer[index];
  packet->lock();
  packet->reset();
  Image* capture_image = packet->image;
  int captureResult = 0;

  if ( deinterlacing_value == 4 ) {
    if ( FirstCapture != 1 ) {
      /* Copy the next image into the shared memory */
      capture_image->CopyBuffer(*(next_buffer.image));
    }
    /* Capture a new next image */
    captureResult = camera->Capture(*packet);
    gettimeofday(packet->timestamp, NULL);

    if ( FirstCapture ) {
      packet->unlock();
      FirstCapture = 0;
      return 0;
    }
  } else {
    Debug(4, "Capturing");
    captureResult = camera->Capture(*packet);
    gettimeofday(packet->timestamp, NULL);
    if ( captureResult < 0 ) {
      Debug(2,"failed capture");
      // Unable to capture image for temporary reason
      // Fake a signal loss image
      Rgb signalcolor;
      /* HTML colour code is actually BGR in memory, we want RGB */
      signalcolor = rgb_convert(signal_check_colour, ZM_SUBPIX_ORDER_BGR);
      capture_image->Fill(signalcolor);
      shared_data->signal = false;
      shared_data->last_write_index = index;
      shared_data->last_write_time = image_buffer[index].timestamp->tv_sec;
      image_count++;
      packet->unlock();
      // What about timestamping it?
      // Don't want to do analysis on it, but we won't due to signal
      return -1;
    } else if ( captureResult > 0 ) {

      // Analysis thread will take care of consuming and emptying the packets.
      Debug(2, "Have packet (%d) ?= videostream_id:(%d) q.vpktcount(%d) event?(%d) ",
          packet->packet.stream_index, video_stream_id, packetqueue->video_packet_count, ( event ? 1 : 0 ) );

      if ( packet->packet.stream_index != video_stream_id ) {
        Debug(2, "Have audio packet (%d) != videostream_id:(%d) q.vpktcount(%d) event?(%d) ",
            packet->packet.stream_index, video_stream_id, packetqueue->video_packet_count, ( event ? 1 : 0 ) );
        // Only queue if we have some video packets in there. Should push this logic into packetqueue
        //mutex.lock();
        if ( packetqueue->video_packet_count || event ) {
          // Need to copy it into another ZMPacket.
          //ZMPacket *audio_packet = new ZMPacket(*packet);
#if LIBAVCODEC_VERSION_CHECK(57, 64, 0, 64, 0)
          packet->codec_type = camera->get_AudioStream()->codecpar->codec_type;
#else
          packet->codec_type = camera->get_AudioStream()->codec->codec_type;
#endif
          Debug(2, "Queueing packet");
          packetqueue->queuePacket(packet);
        }
        // Don't update last_write_index because that is used for live streaming
        //shared_data->last_write_time = image_buffer[index].timestamp->tv_sec;
        
        //mutex.unlock();
        packet->unlock();
        return 1;
      }

#if LIBAVCODEC_VERSION_CHECK(57, 64, 0, 64, 0)
      packet->codec_type = camera->get_VideoStream()->codecpar->codec_type;
#else
      packet->codec_type = camera->get_VideoStream()->codec->codec_type;
#endif

      if ( packet->packet.size && !packet->in_frame ) {
        //Debug(2,"About to decode");
        if ( packet->decode(camera->get_VideoCodecContext()) ) {
          //Debug(2,"Getimage");
          packet->get_image(image_buffer[index].image);
        }
        // Have an av_packet, 
      }

      //Debug(2,"Before mutex lock");
      // FIXME this mutex is useless, packetqueue has it's own
      //mutex.lock();
      if ( packetqueue->video_packet_count || packet->keyframe || event ) {
        Debug(2, "Have video packet for index (%d)", index);
        packetqueue->queuePacket(packet);
      } else {
        Debug(2, "Not queuing video packet for index (%d) packet count %d", index, packetqueue->video_packet_count);
      }
      //mutex.unlock();

      /* Deinterlacing */
      if ( deinterlacing_value ) {
        if ( deinterlacing_value == 1 ) {
          capture_image->Deinterlace_Discard();
        } else if ( deinterlacing_value == 2 ) {
          capture_image->Deinterlace_Linear();
        } else if ( deinterlacing_value == 3 ) {
          capture_image->Deinterlace_Blend();
        } else if ( deinterlacing_value == 4 ) {
          capture_image->Deinterlace_4Field(next_buffer.image, (deinterlacing>>8)&0xff);
        } else if ( deinterlacing_value == 5 ) {
          capture_image->Deinterlace_Blend_CustomRatio((deinterlacing>>8)&0xff);
        }
      }

      if ( orientation != ROTATE_0 ) {
        switch ( orientation ) {
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

      if ( privacy_bitmask )
        capture_image->MaskPrivacy(privacy_bitmask);

      if ( config.timestamp_on_capture ) {
        TimestampImage(capture_image, packet->timestamp);
      }

      shared_data->signal = signal_check_points ? CheckSignal(capture_image) : true;
      shared_data->last_write_index = index;
      shared_data->last_write_time = image_buffer[index].timestamp->tv_sec;
      image_count++;
      packet->unlock();

      if ( fps_report_interval && ( !(image_count%fps_report_interval) || image_count == 5 ) ) {
        time_t now = image_buffer[index].timestamp->tv_sec;
        // If we are too fast, we get div by zero. This seems to happen in the case of audio packets.
        if ( now != last_fps_time ) {
          // # of images per interval / the amount of time it took
          double new_capture_fps = double((image_count < fps_report_interval ? image_count : fps_report_interval))/(now-last_fps_time);
          unsigned int new_camera_bytes = camera->Bytes();
          unsigned int new_capture_bandwidth = (new_camera_bytes - last_camera_bytes)/(now-last_fps_time);
          last_camera_bytes = new_camera_bytes;
          //Info( "%d -> %d -> %d", fps_report_interval, now, last_fps_time );
          //Info( "%d -> %d -> %lf -> %lf", now-last_fps_time, fps_report_interval/(now-last_fps_time), double(fps_report_interval)/(now-last_fps_time), fps );
          Info("%s: images:%d - Capturing at %.2lf fps, capturing bandwidth %ubytes/sec", name, image_count, new_capture_fps, new_capture_bandwidth);
          capture_fps = new_capture_fps;
          last_fps_time = now;
          db_mutex.lock();
          static char sql[ZM_SQL_SML_BUFSIZ];
          // The reason we update the Status as well is because if mysql restarts, the Monitor_Status table is lost,
          // and nothing else will update the status until zmc restarts. Since we are successfully capturing we can
          // assume that we are connected
          snprintf(sql, sizeof(sql),
            "INSERT INTO Monitor_Status (MonitorId,CaptureFPS,CaptureBandwidth,Status) "
            "VALUES (%d, %.2lf, %u, 'Connected') ON DUPLICATE KEY UPDATE "
            "CaptureFPS = %.2lf, CaptureBandwidth=%u, Status='Connected'",
            id, capture_fps, new_capture_bandwidth, capture_fps, new_capture_bandwidth);
          if ( mysql_query(&dbconn, sql) ) {
            Error("Can't run query: %s", mysql_error(&dbconn));
          }
          db_mutex.unlock();
        } // now != last_fps_time
      } // end if report fps
    } else { // result == 0
      // Question is, do we update last_write_index etc?
      packet->unlock();
      return 0;

    } // end if result
  } // end if deinterlacing


  // Icon: I'm not sure these should be here. They have nothing to do with capturing
  if ( shared_data->action & GET_SETTINGS ) {
    shared_data->brightness = camera->Brightness();
    shared_data->hue = camera->Hue();
    shared_data->colour = camera->Colour();
    shared_data->contrast = camera->Contrast();
    shared_data->action &= ~GET_SETTINGS;
  }
  if ( shared_data->action & SET_SETTINGS ) {
    camera->Brightness( shared_data->brightness );
    camera->Hue( shared_data->hue );
    camera->Colour( shared_data->colour );
    camera->Contrast( shared_data->contrast );
    shared_data->action &= ~SET_SETTINGS;
  }
  return captureResult;
} // end Monitor::Capture

void Monitor::TimestampImage( Image *ts_image, const struct timeval *ts_time ) const {
  if ( label_format[0] ) {
    // Expand the strftime macros first
    char label_time_text[256];
    strftime( label_time_text, sizeof(label_time_text), label_format, localtime( &ts_time->tv_sec ) );

    char label_text[1024];
    const char *s_ptr = label_time_text;
    char *d_ptr = label_text;
    while ( *s_ptr && ((d_ptr-label_text) < (unsigned int)sizeof(label_text)) ) {
      if ( *s_ptr == config.timestamp_code_char[0] ) {
        bool found_macro = false;
        switch ( *(s_ptr+1) ) {
          case 'N' :
            d_ptr += snprintf( d_ptr, sizeof(label_text)-(d_ptr-label_text), "%s", name );
            found_macro = true;
            break;
          case 'Q' :
            d_ptr += snprintf( d_ptr, sizeof(label_text)-(d_ptr-label_text), "%s", trigger_data->trigger_showtext );
            found_macro = true;
            break;
          case 'f' :
            d_ptr += snprintf( d_ptr, sizeof(label_text)-(d_ptr-label_text), "%02ld", ts_time->tv_usec/10000 );
            found_macro = true;
            break;
        }
        if ( found_macro ) {
          s_ptr += 2;
          continue;
        }
      }
      *d_ptr++ = *s_ptr++;
    }
    *d_ptr = '\0';
    ts_image->Annotate( label_text, label_coord, label_size );
  }
}

bool Monitor::closeEvent() {
  if ( event ) {
    if ( function == RECORD || function == MOCORD ) {
      gettimeofday(&(event->EndTime()), NULL);
    }
#if 0
    if ( event_delete_thread ) {
      event_delete_thread->join();
      delete event_delete_thread;
      event_delete_thread = NULL;
    }
    event_delete_thread = new std::thread([](Event *event) {
      Event * e = event;
      event = NULL;
      delete e;
      e = NULL;
    }, event);
#else
    delete event;
    event = NULL;
#endif
    video_store_data->recording = (struct timeval){0};
    return true;
  }
  return false;
}

unsigned int Monitor::DetectMotion( const Image &comp_image, Event::StringSet &zoneSet ) {
  bool alarm = false;
  unsigned int score = 0;

  if ( n_zones <= 0 ) return alarm;

  Storage *storage = this->getStorage();

  if ( config.record_diag_images ) {
    static char diag_path[PATH_MAX] = "";
    if ( !diag_path[0] ) {
      snprintf(diag_path, sizeof(diag_path), "%s/%d/diag-r.jpg", storage->Path(), id);
    }
    ref_image.WriteJpeg( diag_path );
  }

  ref_image.Delta(comp_image, &delta_image);

  if ( config.record_diag_images ) {
    static char diag_path[PATH_MAX] = "";
    if ( !diag_path[0] ) {
      snprintf( diag_path, sizeof(diag_path), "%s/%d/diag-d.jpg", storage->Path(), id );
    }
    delta_image.WriteJpeg(diag_path);
  }

  // Blank out all exclusion zones
  for ( int n_zone = 0; n_zone < n_zones; n_zone++ ) {
    Zone *zone = zones[n_zone];
    // need previous alarmed state for preclusive zone, so don't clear just yet
    if (!zone->IsPreclusive())
      zone->ClearAlarm();
    if ( !zone->IsInactive() ) {
      continue;
    }
    Debug(3, "Blanking inactive zone %s", zone->Label());
    delta_image.Fill(RGB_BLACK, zone->GetPolygon());
  }

  // Check preclusive zones first
  for ( int n_zone = 0; n_zone < n_zones; n_zone++ ) {
    Zone *zone = zones[n_zone];
    if ( !zone->IsPreclusive() ) {
      continue;
    }
    int old_zone_score = zone->Score();
    bool old_zone_alarmed = zone->Alarmed();
    Debug( 3, "Checking preclusive zone %s - old score: %d, state: %s", zone->Label(),old_zone_score, zone->Alarmed()?"alarmed":"quiet" );
    if ( zone->CheckAlarms( &delta_image ) ) {
      alarm = true;
      score += zone->Score();
      zone->SetAlarm();
      Debug( 3, "Zone is alarmed, zone score = %d", zone->Score() );
      zoneSet.insert( zone->Label() );
      //zone->ResetStats();
    } else {
      // check if end of alarm
      if (old_zone_alarmed) {
        Debug(3, "Preclusive Zone %s alarm Ends. Previous score: %d", zone->Label(), old_zone_score);
        if (old_zone_score > 0) {
          zone->SetExtendAlarmCount(zone->GetExtendAlarmFrames());
        }
        if (zone->CheckExtendAlarmCount()) {
          alarm=true;
          zone->SetAlarm();
        } else {
          zone->ClearAlarm();
        }
      }
    }
  }

  Coord alarm_centre;
  int top_score = -1;

  if ( alarm ) {
    alarm = false;
    score = 0;
  } else {
    // Find all alarm pixels in active zones
    for ( int n_zone = 0; n_zone < n_zones; n_zone++ ) {
      Zone *zone = zones[n_zone];
      if ( !zone->IsActive() || zone->IsPreclusive()) {
        continue;
      }
      Debug( 3, "Checking active zone %s", zone->Label() );
      if ( zone->CheckAlarms( &delta_image ) ) {
        alarm = true;
        score += zone->Score();
        zone->SetAlarm();
        Debug( 3, "Zone is alarmed, zone score = %d", zone->Score() );
        zoneSet.insert( zone->Label() );
        if ( config.opt_control && track_motion ) {
          if ( (int)zone->Score() > top_score ) {
            top_score = zone->Score();
            alarm_centre = zone->GetAlarmCentre();
          }
        }
      }
    }

    if ( alarm ) {
      for ( int n_zone = 0; n_zone < n_zones; n_zone++ ) {
        Zone *zone = zones[n_zone];
        // Wasn't this zone already checked above?
        if ( !zone->IsInclusive() ) {
          continue;
        }
        Debug( 3, "Checking inclusive zone %s", zone->Label() );
        if ( zone->CheckAlarms( &delta_image ) ) {
          alarm = true;
          score += zone->Score();
          zone->SetAlarm();
          Debug( 3, "Zone is alarmed, zone score = %d", zone->Score() );
          zoneSet.insert( zone->Label() );
          if ( config.opt_control && track_motion ) {
            if ( zone->Score() > (unsigned int)top_score ) {
              top_score = zone->Score();
              alarm_centre = zone->GetAlarmCentre();
            }
          }
        }
      }
    } else {
      // Find all alarm pixels in exclusive zones
      for ( int n_zone = 0; n_zone < n_zones; n_zone++ ) {
        Zone *zone = zones[n_zone];
        if ( !zone->IsExclusive() ) {
          continue;
        }
        Debug( 3, "Checking exclusive zone %s", zone->Label() );
        if ( zone->CheckAlarms( &delta_image ) ) {
          alarm = true;
          score += zone->Score();
          zone->SetAlarm();
          Debug( 3, "Zone is alarmed, zone score = %d", zone->Score() );
          zoneSet.insert( zone->Label() );
        }
      }
    } // end if alarm or not
  }

  if ( top_score > 0 ) {
    shared_data->alarm_x = alarm_centre.X();
    shared_data->alarm_y = alarm_centre.Y();

    Info( "Got alarm centre at %d,%d, at count %d", shared_data->alarm_x, shared_data->alarm_y, analysis_image_count );
  } else {
    shared_data->alarm_x = shared_data->alarm_y = -1;
  }

  // This is a small and innocent hack to prevent scores of 0 being returned in alarm state
  return score?score:alarm;
}

bool Monitor::DumpSettings( char *output, bool verbose ) {
  output[0] = 0;

  sprintf( output+strlen(output), "Id : %d\n", id );
  sprintf( output+strlen(output), "Name : %s\n", name );
  sprintf( output+strlen(output), "Type : %s\n", camera->IsLocal()?"Local":(camera->IsRemote()?"Remote":"File") );
#if ZM_HAS_V4L
  if ( camera->IsLocal() ) {
    sprintf( output+strlen(output), "Device : %s\n", ((LocalCamera *)camera)->Device().c_str() );
    sprintf( output+strlen(output), "Channel : %d\n", ((LocalCamera *)camera)->Channel() );
    sprintf( output+strlen(output), "Standard : %d\n", ((LocalCamera *)camera)->Standard() );
  } else
#endif // ZM_HAS_V4L
  if ( camera->IsRemote() ) {
    sprintf( output+strlen(output), "Protocol : %s\n", ((RemoteCamera *)camera)->Protocol().c_str() );
    sprintf( output+strlen(output), "Host : %s\n", ((RemoteCamera *)camera)->Host().c_str() );
    sprintf( output+strlen(output), "Port : %s\n", ((RemoteCamera *)camera)->Port().c_str() );
    sprintf( output+strlen(output), "Path : %s\n", ((RemoteCamera *)camera)->Path().c_str() );
  } else if ( camera->IsFile() ) {
    sprintf( output+strlen(output), "Path : %s\n", ((FileCamera *)camera)->Path() );
  }
#if HAVE_LIBAVFORMAT
  else if ( camera->IsFfmpeg() ) {
    sprintf( output+strlen(output), "Path : %s\n", ((FfmpegCamera *)camera)->Path().c_str() );
  }
#endif // HAVE_LIBAVFORMAT
  sprintf( output+strlen(output), "Width : %d\n", camera->Width() );
  sprintf( output+strlen(output), "Height : %d\n", camera->Height() );
#if ZM_HAS_V4L
  if ( camera->IsLocal() ) {
    sprintf( output+strlen(output), "Palette : %d\n", ((LocalCamera *)camera)->Palette() );
  }
#endif // ZM_HAS_V4L
  sprintf(output+strlen(output), "Colours : %d\n", camera->Colours() );
  sprintf(output+strlen(output), "Subpixel Order : %d\n", camera->SubpixelOrder() );
  sprintf(output+strlen(output), "Event Prefix : %s\n", event_prefix );
  sprintf(output+strlen(output), "Label Format : %s\n", label_format );
  sprintf(output+strlen(output), "Label Coord : %d,%d\n", label_coord.X(), label_coord.Y() );
  sprintf(output+strlen(output), "Label Size : %d\n", label_size );
  sprintf(output+strlen(output), "Image Buffer Count : %d\n", image_buffer_count );
  sprintf(output+strlen(output), "Warmup Count : %d\n", warmup_count );
  sprintf(output+strlen(output), "Pre Event Count : %d\n", pre_event_count );
  sprintf(output+strlen(output), "Post Event Count : %d\n", post_event_count );
  sprintf(output+strlen(output), "Stream Replay Buffer : %d\n", stream_replay_buffer );
  sprintf(output+strlen(output), "Alarm Frame Count : %d\n", alarm_frame_count );
  sprintf(output+strlen(output), "Section Length : %d\n", section_length);
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
  sprintf(output+strlen(output), "Zones : %d\n", n_zones );
  for ( int i = 0; i < n_zones; i++ ) {
    zones[i]->DumpSettings(output+strlen(output), verbose);
  }
  return true;
} // bool Monitor::DumpSettings(char *output, bool verbose)

unsigned int Monitor::Colours() const { return camera->Colours(); }
unsigned int Monitor::SubpixelOrder() const { return camera->SubpixelOrder(); }

int Monitor::PrimeCapture() {
  int ret = camera->PrimeCapture();
  if ( ret == 0 ) {
    if ( packetqueue ) 
      delete packetqueue;
    video_stream_id = camera->get_VideoStreamId();
    audio_stream_id = camera->get_AudioStreamId();
    packetqueue = new zm_packetqueue(image_buffer_count, video_stream_id, audio_stream_id);
  }
  Debug(2, "Video stream id is %d, audio is %d, minimum_packets to keep in buffer %d",
      video_stream_id, audio_stream_id, pre_event_buffer_count);
  return ret;
}

int Monitor::PreCapture() const { return camera->PreCapture(); }
int Monitor::PostCapture() const { return camera->PostCapture() ; }
int Monitor::Close() { return camera->Close(); };
Monitor::Orientation Monitor::getOrientation() const { return orientation; }

// Wait for camera to get an image, and then assign it as the base reference image. So this should be done as the first task in the analysis thread startup.
void Monitor::get_ref_image() {
  ZMPacket * snap;
  while ( ( (!( snap = packetqueue->get_analysis_packet()))
    || ( snap->packet.stream_index != video_stream_id ) )
    && !zm_terminate) {
    Debug(1, "Waiting for capture daemon lastwriteindex(%d) lastwritetime(%d)",
        shared_data->last_write_index, shared_data->last_write_time);
    //usleep(10000);
  }
  if ( zm_terminate )
    return;

  ref_image.Assign(width, height, camera->Colours(), camera->SubpixelOrder(), snap->image->Buffer(), camera->ImageSize());
  Debug(2,"Have image about to unlock");
  snap->unlock();
}

std::vector<Group *>  Monitor::Groups() {
  // At the moment, only load groups once.
  if ( ! groups.size() ) {
    std::string sql = stringtf(
        "SELECT Id,ParentId,Name FROM Groups WHERE Groups.Id IN "
        "(SELECT GroupId FROM Groups_Monitors WHERE MonitorId=%d)",id);
    MYSQL_RES *result = zmDbFetch(sql.c_str());
    if ( !result ) {
      Error("Can't load groups: %s", mysql_error(&dbconn));
      return groups;
    }
    int n_groups = mysql_num_rows(result);
    Debug(1, "Got %d groups", n_groups);
    while ( MYSQL_ROW dbrow = mysql_fetch_row(result) ) {
      groups.push_back(new Group(dbrow));
    }
    if ( mysql_errno(&dbconn) ) {
      Error("Can't fetch row: %s", mysql_error(&dbconn));
    }
    mysql_free_result(result);
  }
  return groups;
}

StringVector Monitor::GroupNames() {
  StringVector groupnames;
  for(Group * g: Groups()) {
    groupnames.push_back(std::string(g->Name()));
Debug(1,"Groups: %s", g->Name());
  }
  return groupnames;
} 
  
