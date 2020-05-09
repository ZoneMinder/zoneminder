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
#include "zm_fifo.h"
#if HAVE_LIBVLC
#include "zm_libvlc_camera.h"
#endif // HAVE_LIBVLC
#if HAVE_LIBCURL
#include "zm_curl_camera.h"
#endif // HAVE_LIBCURL
#if HAVE_LIBVNC
#include "zm_libvnc_camera.h"
#endif // HAVE_LIBVNC

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
"SELECT `Id`, `Name`, `ServerId`, `StorageId`, `Type`, `Function`+0, `Enabled`, `LinkedMonitors`, "
"`AnalysisFPSLimit`, `AnalysisUpdateDelay`, `MaxFPS`, `AlarmMaxFPS`,"
"`Device`, `Channel`, `Format`, `V4LMultiBuffer`, `V4LCapturesPerFrame`, " // V4L Settings
"`Protocol`, `Method`, `Options`, `User`, `Pass`, `Host`, `Port`, `Path`, `Width`, `Height`, `Colours`, `Palette`, `Orientation`+0, `Deinterlacing`, "
"`DecoderHWAccelName`, `DecoderHWAccelDevice`, `RTSPDescribe`, "
"`SaveJPEGs`, `VideoWriter`, `EncoderParameters`, "
//" OutputCodec, Encoder, OutputContainer, "
"`RecordAudio`, "
"`Brightness`, `Contrast`, `Hue`, `Colour`, "
"`EventPrefix`, `LabelFormat`, `LabelX`, `LabelY`, `LabelSize`,"
"`ImageBufferCount`, `WarmupCount`, `PreEventCount`, `PostEventCount`, `StreamReplayBuffer`, `AlarmFrameCount`, "
"`SectionLength`, `MinSectionLength`, `FrameSkip`, `MotionFrameSkip`, "
"`FPSReportInterval`, `RefBlendPerc`, `AlarmRefBlendPerc`, `TrackMotion`, `Exif`, `SignalCheckPoints`, `SignalCheckColour` FROM `Monitors`";

std::string CameraType_Strings[] = {
  "Local",
  "Remote",
  "File",
  "Ffmpeg",
  "LibVLC",
  "CURL",
  "VNC",
};


std::vector<std::string> split(const std::string &s, char delim) {
  std::vector<std::string> elems;
  std::stringstream ss(s);
  std::string item;
  while(std::getline(ss, item, delim)) {
    elems.push_back(trimSpaces(item));
  }
  return elems;
}

Monitor::MonitorLink::MonitorLink( int p_id, const char *p_name ) :
  id( p_id ),
  shared_data(NULL),
  trigger_data(NULL),
  video_store_data(NULL)
{
  strncpy( name, p_name, sizeof(name)-1 );

#if ZM_MEM_MAPPED
  map_fd = -1;
  snprintf( mem_file, sizeof(mem_file), "%s/zm.mmap.%d", staticConfig.PATH_MAP.c_str(), id );
#else // ZM_MEM_MAPPED
  shm_id = 0;
#endif // ZM_MEM_MAPPED
  mem_size = 0;
  mem_ptr = 0;

  last_event = 0;
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
    last_event = shared_data->last_event;
    connected = true;

    return( true );
  }
  return( false );
}

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
  } else if ( shared_data->last_event != last_event ) {
    last_event = shared_data->last_event;
  }
  return false;
}

Monitor::Monitor(
  int p_id,
  const char *p_name,
  const unsigned int p_server_id,
  const unsigned int p_storage_id,
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
  bool p_record_audio,
  const char *p_event_prefix,
  const char *p_label_format,
  const Coord &p_label_coord,
  int p_label_size,
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
  int p_n_zones,
  Zone *p_zones[]
) : id( p_id ),
  server_id( p_server_id ),
  storage_id( p_storage_id ),
  function( (Function)p_function ),
  enabled( p_enabled ),
    width( (p_orientation==ROTATE_90||p_orientation==ROTATE_270)?p_camera->Height():p_camera->Width() ),
    height( (p_orientation==ROTATE_90||p_orientation==ROTATE_270)?p_camera->Width():p_camera->Height() ),
  orientation( (Orientation)p_orientation ),
  deinterlacing( p_deinterlacing ),
  decoder_hwaccel_name(p_decoder_hwaccel_name),
  decoder_hwaccel_device(p_decoder_hwaccel_device),
  savejpegs( p_savejpegs ),
  videowriter( p_videowriter ),
  encoderparams( p_encoderparams ),
  record_audio( p_record_audio ),
  label_coord( p_label_coord ),
  label_size( p_label_size ),
  image_buffer_count( p_image_buffer_count ),
  warmup_count( p_warmup_count ),
  pre_event_count( p_pre_event_count ),
  post_event_count( p_post_event_count ),
  video_buffer_duration({0}),
  stream_replay_buffer( p_stream_replay_buffer ),
  section_length( p_section_length ),
  min_section_length( p_min_section_length ),
  frame_skip( p_frame_skip ),
  motion_frame_skip( p_motion_frame_skip ),
  capture_max_fps( p_capture_max_fps ),
  analysis_fps( p_analysis_fps ),
  analysis_update_delay( p_analysis_update_delay ),
  capture_delay( p_capture_delay ),
  alarm_capture_delay( p_alarm_capture_delay ),
  alarm_frame_count( p_alarm_frame_count ),
  fps_report_interval( p_fps_report_interval ),
  ref_blend_perc( p_ref_blend_perc ),
  alarm_ref_blend_perc( p_alarm_ref_blend_perc ),
  track_motion( p_track_motion ),
  signal_check_points(p_signal_check_points),
  signal_check_colour( p_signal_check_colour ),
  embed_exif( p_embed_exif ),
  delta_image( width, height, ZM_COLOUR_GRAY8, ZM_SUBPIX_ORDER_NONE ),
  ref_image( width, height, p_camera->Colours(), p_camera->SubpixelOrder() ),
  purpose( p_purpose ),
  last_motion_score(0),
  camera( p_camera ),
  n_zones( p_n_zones ),
  zones( p_zones ),
  timestamps( 0 ),
  images( 0 ),
  privacy_bitmask( NULL ),
  event_delete_thread(NULL)
{
  if (analysis_fps > 0.0) {
      uint64_t usec = round(1000000*pre_event_count/analysis_fps);
      video_buffer_duration.tv_sec = usec/1000000;
      video_buffer_duration.tv_usec = usec % 1000000;
  }

  strncpy(name, p_name, sizeof(name)-1);

  strncpy(event_prefix, p_event_prefix, sizeof(event_prefix)-1);
  strncpy(label_format, p_label_format, sizeof(label_format)-1);

  // Change \n to actual line feeds
  char *token_ptr = label_format;
  const char *token_string = "\n";
  while ( ( token_ptr = strstr(token_ptr, token_string) ) ) {
    if ( *(token_ptr+1) ) {
      *token_ptr = '\n';
      token_ptr++;
      strcpy(token_ptr, token_ptr+1);
    } else {
      *token_ptr = '\0';
      break;
    }
  }

  /* Parse encoder parameters */
  ParseEncoderParameters(encoderparams.c_str(), &encoderparamsvec);

  fps = 0.0;
  last_camera_bytes = 0;
  event_count = 0;
  image_count = 0;
  ready_count = warmup_count;
  first_alarm_count = 0;
  last_alarm_count = 0;
  state = IDLE;

  if ( alarm_frame_count < 1 )
    alarm_frame_count = 1;
  else if ( alarm_frame_count > MAX_PRE_ALARM_FRAMES )
    alarm_frame_count = MAX_PRE_ALARM_FRAMES;

  auto_resume_time = 0;

  if ( strcmp(config.event_close_mode, "time") == 0 )
    event_close_mode = CLOSE_TIME;
  else if ( strcmp(config.event_close_mode, "alarm") == 0 )
    event_close_mode = CLOSE_ALARM;
  else
    event_close_mode = CLOSE_IDLE;

  Debug(1, "monitor purpose=%d", purpose);

  mem_size = sizeof(SharedData)
       + sizeof(TriggerData)
       + sizeof(VideoStoreData) //Information to pass back to the capture process
       + (image_buffer_count*sizeof(struct timeval))
       + (image_buffer_count*camera->ImageSize())
       + 64; /* Padding used to permit aligning the images buffer to 64 byte boundary */

  Debug(1, "mem.size(%d) SharedData=%d TriggerData=%d VideoStoreData=%d timestamps=%d images=%dx%d = %" PRId64 " total=%" PRId64,
      sizeof(mem_size),
      sizeof(SharedData), sizeof(TriggerData), sizeof(VideoStoreData),
      (image_buffer_count*sizeof(struct timeval)),
      image_buffer_count, camera->ImageSize(), (image_buffer_count*camera->ImageSize()),
     mem_size);
  mem_ptr = NULL;

  storage = new Storage(storage_id);
  Debug(1, "Storage path: %s", storage->Path());
  // Should maybe store this for later use
  char monitor_dir[PATH_MAX];
  snprintf(monitor_dir, sizeof(monitor_dir), "%s/%d", storage->Path(), id);

  if ( purpose == CAPTURE ) {
    if ( mkdir(monitor_dir, 0755) && ( errno != EEXIST ) ) {
      Error("Can't mkdir %s: %s", monitor_dir, strerror(errno));
    }

    if ( !this->connect() ) {
      Error("unable to connect, but doing capture");
      exit(-1);
    }

    memset(mem_ptr, 0, mem_size);
    shared_data->size = sizeof(SharedData);
    shared_data->active = enabled;
    shared_data->signal = false;
    shared_data->state = IDLE;
    shared_data->last_write_index = image_buffer_count;
    shared_data->last_read_index = image_buffer_count;
    shared_data->last_write_time = 0;
    shared_data->last_event = 0;
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
    snprintf(video_store_data->event_file, sizeof(video_store_data->event_file), "nothing");
    video_store_data->size = sizeof(VideoStoreData);
    //video_store_data->frameNumber = 0;
  } else if ( purpose == ANALYSIS ) {
    if ( ! (this->connect() && mem_ptr && shared_data->valid) ) {
      Error("Shared data not initialised by capture daemon for monitor %s", name);
      exit(-1);
    }
    shared_data->state = IDLE;
    shared_data->last_read_time = 0;
    shared_data->alarm_x = -1;
    shared_data->alarm_y = -1;
  }

  start_time = last_fps_time = time( 0 );

  event = 0;

  Debug(1, "Monitor %s has function %d,\n"
      "label format = '%s', label X = %d, label Y = %d, label size = %d,\n"
      "image buffer count = %d, warmup count = %d, pre-event count = %d, post-event count = %d, alarm frame count = %d,\n"
      "fps report interval = %d, ref blend percentage = %d, alarm ref blend percentage = %d, track motion = %d",
      name, function,
      label_format, label_coord.X(), label_coord.Y(), label_size,
      image_buffer_count, warmup_count, pre_event_count, post_event_count, alarm_frame_count,
      fps_report_interval, ref_blend_perc, alarm_ref_blend_perc, track_motion );

  //Set video recording flag for event start constructor and easy reference in code
  videoRecording = ((GetOptVideoWriter() == H264PASSTHROUGH) && camera->SupportsNativeVideo());

  n_linked_monitors = 0;
  linked_monitors = 0;

  if ( purpose == ANALYSIS ) {
    while(
        ( shared_data->last_write_index == (unsigned int)image_buffer_count )
         &&
        ( shared_data->last_write_time == 0 )
        &&
        ( !zm_terminate )
        ) {
      Debug(1, "Waiting for capture daemon last_write_index(%d), last_write_time(%d)",
          shared_data->last_write_index, shared_data->last_write_time );
      sleep(1);
    }
    ref_image.Assign( width, height, camera->Colours(), camera->SubpixelOrder(),
        image_buffer[shared_data->last_write_index].image->Buffer(), camera->ImageSize());
    adaptive_skip = true;

    ReloadLinkedMonitors(p_linked_monitors);

    if ( config.record_diag_images ) {
      diag_path_r = stringtf(config.record_diag_images_fifo ? "%s/%d/diagpipe-r.jpg" : "%s/%d/diag-r.jpg", storage->Path(), id);
      diag_path_d = stringtf(config.record_diag_images_fifo ? "%s/%d/diagpipe-d.jpg" : "%s/%d/diag-d.jpg", storage->Path(), id);
      if (config.record_diag_images_fifo){
        FifoStream::fifo_create_if_missing(diag_path_r.c_str());
        FifoStream::fifo_create_if_missing(diag_path_d.c_str());
      }
    }
  }  // end if purpose == ANALYSIS
}  // Monitor::Monitor

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
  mem_ptr = (unsigned char *)shmat(shm_id, 0, 0);
  if ( mem_ptr < (void *)0 ) {
    Fatal("Can't shmat: %s", strerror(errno));
  }
#endif // ZM_MEM_MAPPED
  shared_data = (SharedData *)mem_ptr;
  trigger_data = (TriggerData *)((char *)shared_data + sizeof(SharedData));
  video_store_data = (VideoStoreData *)((char *)trigger_data + sizeof(TriggerData));
  struct timeval *shared_timestamps = (struct timeval *)((char *)video_store_data + sizeof(VideoStoreData));
  unsigned char *shared_images = (unsigned char *)((char *)shared_timestamps + (image_buffer_count*sizeof(struct timeval)));

  if ( ((unsigned long)shared_images % 64) != 0 ) {
    /* Align images buffer to nearest 64 byte boundary */
    Debug(3,"Aligning shared memory images to the next 64 byte boundary");
    shared_images = (uint8_t*)((unsigned long)shared_images + (64 - ((unsigned long)shared_images % 64)));
  }
  Debug(3, "Allocating %d image buffers", image_buffer_count);
  image_buffer = new Snapshot[image_buffer_count];
  for ( int i = 0; i < image_buffer_count; i++ ) {
    image_buffer[i].timestamp = &(shared_timestamps[i]);
    image_buffer[i].image = new Image( width, height, camera->Colours(), camera->SubpixelOrder(), &(shared_images[i*camera->ImageSize()]) );
    image_buffer[i].image->HoldBuffer(true); /* Don't release the internal buffer or replace it with another */
  }
  if ( (deinterlacing & 0xff) == 4) {
    /* Four field motion adaptive deinterlacing in use */
    /* Allocate a buffer for the next image */
    next_buffer.image = new Image( width, height, camera->Colours(), camera->SubpixelOrder());
    next_buffer.timestamp = new struct timeval;
  }
  if ( purpose == ANALYSIS ) {
		if ( analysis_fps ) {
			// Size of pre event buffer must be greater than pre_event_count
			// if alarm_frame_count > 1, because in this case the buffer contains
			// alarmed images that must be discarded when event is created
			pre_event_buffer_count = pre_event_count + alarm_frame_count - 1;
			pre_event_buffer = new Snapshot[pre_event_buffer_count];
			for ( int i = 0; i < pre_event_buffer_count; i++ ) {
				pre_event_buffer[i].timestamp = new struct timeval;
				*pre_event_buffer[i].timestamp = {0,0};
				pre_event_buffer[i].image = new Image( width, height, camera->Colours(), camera->SubpixelOrder());
			}
		} // end if max_analysis_fps

    timestamps = new struct timeval *[pre_event_count];
    images = new Image *[pre_event_count];
    last_signal = shared_data->signal;
  } // end if purpose == ANALYSIS
Debug(3, "Success connecting");
  return true;
} // end Monitor::connect

Monitor::~Monitor() {
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
  if ( privacy_bitmask ) {
    delete[] privacy_bitmask;
    privacy_bitmask = NULL;
  }
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

    if ( (deinterlacing & 0xff) == 4) {
      delete next_buffer.image;
      delete next_buffer.timestamp;
    }
    for ( int i = 0; i < image_buffer_count; i++ ) {
      delete image_buffer[i].image;
    }
    delete[] image_buffer;
  } // end if mem_ptr

  for ( int i = 0; i < n_zones; i++ ) {
    delete zones[i];
  }
  delete[] zones;

  delete camera;
  delete storage;

  if ( mem_ptr ) {
    if ( purpose == ANALYSIS ) {
      shared_data->state = state = IDLE;
      shared_data->last_read_index = image_buffer_count;
      shared_data->last_read_time = 0;

      if ( analysis_fps ) {
        for ( int i = 0; i < pre_event_buffer_count; i++ ) {
          delete pre_event_buffer[i].image;
          delete pre_event_buffer[i].timestamp;
        }
        delete[] pre_event_buffer;
      }
    } else if ( purpose == CAPTURE ) {
      shared_data->valid = false;
      memset( mem_ptr, 0, mem_size );
    }

#if ZM_MEM_MAPPED
    if ( msync(mem_ptr, mem_size, MS_SYNC) < 0 )
      Error("Can't msync: %s", strerror(errno));
    if ( munmap(mem_ptr, mem_size) < 0 )
      Fatal("Can't munmap: %s", strerror(errno));
    close( map_fd );

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
}

void Monitor::AddZones( int p_n_zones, Zone *p_zones[] ) {
  for ( int i = 0; i < n_zones; i++ )
    delete zones[i];
  delete[] zones;
  n_zones = p_n_zones;
  zones = p_zones;
}

void Monitor::AddPrivacyBitmask( Zone *p_zones[] ) {
  if ( privacy_bitmask ) {
    delete[] privacy_bitmask;
    privacy_bitmask = NULL;
  }
  Image *privacy_image = NULL;

  for ( int i = 0; i < n_zones; i++ ) {
    if ( p_zones[i]->IsPrivacy() ) {
      if ( !privacy_image ) {
        privacy_image = new Image( width, height, 1, ZM_SUBPIX_ORDER_NONE);
        privacy_image->Clear();
      }
      privacy_image->Fill( 0xff, p_zones[i]->GetPolygon() );
      privacy_image->Outline( 0xff, p_zones[i]->GetPolygon() );
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

  if ( index != image_buffer_count ) {
    Image *image;
    // If we are going to be modifying the snapshot before writing, then we need to copy it
    if ( ( scale != ZM_SCALE_BASE ) || ( !config.timestamp_on_capture ) ) {
      Snapshot *snap = &image_buffer[index];
      Image *snap_image = snap->image;

      alarm_image.Assign(*snap_image);


      //write_image.Assign( *snap_image );

      if ( scale != ZM_SCALE_BASE ) {
        alarm_image.Scale(scale);
      }

      if ( !config.timestamp_on_capture ) {
        TimestampImage(&alarm_image, snap->timestamp);
      }
      image = &alarm_image;
    } else {
      image = image_buffer[index].image;
    }

    static char filename[PATH_MAX];
    snprintf(filename, sizeof(filename), "Monitor%d.jpg", id);
    image->WriteJpeg(filename);
  } else {
    Error("Unable to generate image, no images in buffer");
  }
  return 0;
}

struct timeval Monitor::GetTimestamp( int index ) const {
  if ( index < 0 || index > image_buffer_count ) {
    index = shared_data->last_write_index;
  }

  if ( index != image_buffer_count ) {
    Snapshot *snap = &image_buffer[index];

    return *(snap->timestamp);
  } else {
    static struct timeval null_tv = { 0, 0 };

    return null_tv;
  }
}

unsigned int Monitor::GetLastReadIndex() const {
  return( shared_data->last_read_index!=(unsigned int)image_buffer_count?shared_data->last_read_index:-1 );
}

unsigned int Monitor::GetLastWriteIndex() const {
  return( shared_data->last_write_index!=(unsigned int)image_buffer_count?shared_data->last_write_index:-1 );
}

uint64_t Monitor::GetLastEventId() const {
#if 0
  Debug(2, "mem_ptr(%x), State(%d) last_read_index(%d) last_read_time(%d) last_event(%" PRIu64 ")",
      mem_ptr,
      shared_data->state,
      shared_data->last_read_index,
      shared_data->last_read_time,
      shared_data->last_event
      );
#endif
  return shared_data->last_event;
}

// This function is crap.
double Monitor::GetFPS() const {
  // last_write_index is the last capture index.  It starts as == image_buffer_count so that the first asignment % image_buffer_count = 0;
  int index1 = shared_data->last_write_index;
  if ( index1 == image_buffer_count ) {
    // last_write_index only has this value on startup before capturing anything.
    return 0.0;
  }
  Snapshot *snap1 = &image_buffer[index1];
  if ( !snap1->timestamp || !snap1->timestamp->tv_sec ) {
    // This should be impossible
    Warning("Impossible situation.  No timestamp on captured image index was %d, image-buffer_count was (%d)", index1, image_buffer_count);
    return 0.0;
  }
  struct timeval time1 = *snap1->timestamp;

  int image_count = image_buffer_count;
  int index2 = (index1+1)%image_buffer_count;
  Snapshot *snap2 = &image_buffer[index2];
  // the timestamp pointers are initialized on connection, so that's redundant
  // tv_sec is probably only zero during the first loop of capturing, so this basically just counts the unused images.
  // The problem is that there is no locking, and we set the timestamp before we set last_write_index,
  // so there is a small window where the next image can have a timestamp in the future
  while ( !snap2->timestamp || !snap2->timestamp->tv_sec || tvDiffSec(*snap2->timestamp, *snap1->timestamp) < 0 ) {
    if ( index1 == index2 ) {
      // All images are uncaptured
      return 0.0;
    }
    index2 = (index2+1)%image_buffer_count;
    snap2 = &image_buffer[index2];
    image_count--;
  }
  struct timeval time2 = *snap2->timestamp;

  double time_diff = tvDiffSec( time2, time1 );
  if ( ! time_diff ) {
    Error("No diff between time_diff = %lf (%d:%ld.%ld - %d:%ld.%ld), ibc: %d",
        time_diff, index2, time2.tv_sec, time2.tv_usec, index1, time1.tv_sec, time1.tv_usec, image_buffer_count);
    return 0.0;
  }
  double curr_fps = image_count/time_diff;

  if ( curr_fps < 0.0 ) {
    Error("Negative FPS %f, time_diff = %lf (%d:%ld.%ld - %d:%ld.%ld), ibc: %d",
        curr_fps, time_diff, index2, time2.tv_sec, time2.tv_usec, index1, time1.tv_sec, time1.tv_usec, image_buffer_count);
    return 0.0;
  } else {
    Debug(2, "GetFPS %f, time_diff = %lf (%d:%ld.%ld - %d:%ld.%ld), ibc: %d",
        curr_fps, time_diff, index2, time2.tv_sec, time2.tv_usec, index1, time1.tv_sec, time1.tv_usec, image_buffer_count);
  }
  return curr_fps;
}

useconds_t Monitor::GetAnalysisRate() {
  double capturing_fps = GetFPS();
  if ( !analysis_fps ) {
    return 0;
  } else if ( analysis_fps > capturing_fps ) {
    Warning("Analysis fps (%.2f) is greater than capturing fps (%.2f)", analysis_fps, capturing_fps);
    return 0;
  } else {
    return ( ( 1000000 / analysis_fps ) - ( 1000000 / capturing_fps ) );
  }
}

void Monitor::UpdateAdaptiveSkip() {
  if ( config.opt_adaptive_skip ) {
    double capturing_fps = GetFPS();
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
  trigger_data->trigger_state = TRIGGER_ON;
  trigger_data->trigger_score = force_score;
  strncpy(trigger_data->trigger_cause, force_cause, sizeof(trigger_data->trigger_cause)-1);
  strncpy(trigger_data->trigger_text, force_text, sizeof(trigger_data->trigger_text)-1);
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
  snprintf(sql, sizeof(sql), "UPDATE `Monitors` SET `Enabled` = 1 WHERE `Id` = %d", id);
  if ( mysql_query(&dbconn, sql) ) {
    Error("Can't run query: %s", mysql_error(&dbconn));
  }
  db_mutex.unlock();
}

void Monitor::actionDisable() {
  shared_data->action |= RELOAD;

  static char sql[ZM_SQL_SML_BUFSIZ];
  snprintf(sql, sizeof(sql), "UPDATE `Monitors` SET `Enabled` = 0 WHERE `Id` = %d", id);
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

  Image *zone_image = NULL;
  if ( ( (!staticConfig.SERVER_ID) || ( staticConfig.SERVER_ID == server_id ) ) && mem_ptr ) {
    Debug(3, "Trying to load from local zmc");
    int index = shared_data->last_write_index;
    Snapshot *snap = &image_buffer[index];
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
      stream = NULL;
    } else {
      Error("Unable to load an event for monitor %d", id);
      return;
    }
  }

  if ( zone_image->Colours() == ZM_COLOUR_GRAY8 ) {
    zone_image->Colourise(ZM_COLOUR_RGB24, ZM_SUBPIX_ORDER_RGB);
  }

  for ( int i = 0; i < n_zones; i++ ) {
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
    zone_image->Fill(colour, 2, zones[i]->GetPolygon());
    zone_image->Outline(colour, zones[i]->GetPolygon());
  }

  if ( extra_zone.getNumCoords() ) {
    zone_image->Fill(extra_colour, 2, extra_zone);
    zone_image->Outline(extra_colour, extra_zone);
  }

  static char filename[PATH_MAX];
  snprintf(filename, sizeof(filename), "Zones%d.jpg", id);
  zone_image->WriteJpeg(filename);
  delete zone_image;
} // end void Monitor::DumpZoneImage(const char *zone_string)

void Monitor::DumpImage(Image *dump_image) const {
  if ( image_count && !(image_count%10) ) {
    static char filename[PATH_MAX];
    static char new_filename[PATH_MAX];
    snprintf(filename, sizeof(filename), "Monitor%d.jpg", id);
    snprintf(new_filename, sizeof(new_filename), "Monitor%d-new.jpg", id);
    if ( dump_image->WriteJpeg(new_filename) )
      rename(new_filename, filename);
  }
} // end void Monitor::DumpImage(Image *dump_image)

bool Monitor::CheckSignal(const Image *image) {
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
    Debug(1, "SignalCheck: %d points, colour_val(%d)", signal_check_points, colour_val);
    return false;
  } // end if signal_check_points
  return true;
} // end bool Monitor::CheckSignal(const Image *image)

bool Monitor::Analyse() {
  if ( shared_data->last_read_index == shared_data->last_write_index ) {
    // I wonder how often this happens. Maybe if this happens we should sleep or something?
    return false;
  }

  struct timeval now;
  gettimeofday(&now, NULL);

  if ( image_count && fps_report_interval && !(image_count%fps_report_interval) ) {
    if ( now.tv_sec != last_fps_time ) {
      double new_fps = double(fps_report_interval)/(now.tv_sec - last_fps_time);
      Info("%s: %d - Analysing at %.2f fps", name, image_count, new_fps);
      if ( fps != new_fps ) {
        fps = new_fps;
        db_mutex.lock();
        static char sql[ZM_SQL_SML_BUFSIZ];
        snprintf(sql, sizeof(sql), "INSERT INTO Monitor_Status (MonitorId,AnalysisFPS) VALUES (%d, %.2lf) ON DUPLICATE KEY UPDATE AnalysisFPS = %.2lf", id, fps, fps);
        if ( mysql_query(&dbconn, sql) ) {
          Error("Can't run query: %s", mysql_error(&dbconn));
        }
        db_mutex.unlock();
      } // end if fps != new_fps

      last_fps_time = now.tv_sec;
    }
  }

  int index;
  if ( adaptive_skip ) {
    // I think the idea behind adaptive skip is if we are falling behind, then skip a bunch, but not all
    int read_margin = shared_data->last_read_index - shared_data->last_write_index;
    if ( read_margin < 0 ) read_margin += image_buffer_count;

    int step = 1;
    // Isn't read_margin always > 0 here?
    if ( read_margin > 0 ) {
      // TODO explain this so... 90% of image buffer / 50% of read margin?
      step = (9*image_buffer_count)/(5*read_margin);
    }

    int pending_frames = shared_data->last_write_index - shared_data->last_read_index;
    if ( pending_frames < 0 ) pending_frames += image_buffer_count;

    Debug(4,
        "ReadIndex:%d, WriteIndex: %d, PendingFrames = %d, ReadMargin = %d, Step = %d",
        shared_data->last_read_index, shared_data->last_write_index, pending_frames, read_margin, step
        );
    if ( step <= pending_frames ) {
      index = (shared_data->last_read_index+step)%image_buffer_count;
    } else {
      if ( pending_frames ) {
        Warning("Approaching buffer overrun, consider slowing capture, simplifying analysis or increasing ring buffer size");
      }
      index = shared_data->last_write_index%image_buffer_count;
    }
  } else {
    index = shared_data->last_write_index%image_buffer_count;
  }

  Snapshot *snap = &image_buffer[index];
  struct timeval *timestamp = snap->timestamp;
  Image *snap_image = snap->image;

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
    }
    if ( shared_data->action & RESUME ) {
      if ( Enabled() && !Active() ) {
        Info("Received resume indication at count %d", image_count);
        shared_data->active = true;
        ref_image = *snap_image;
        ready_count = image_count+(warmup_count/2);
        shared_data->alarm_x = shared_data->alarm_y = -1;
      }
      shared_data->action &= ~RESUME;
    }
  } // end if shared_data->action

  if ( auto_resume_time && (now.tv_sec >= auto_resume_time) ) {
    Info("Auto resuming at count %d", image_count);
    shared_data->active = true;
    ref_image = *snap_image;
    ready_count = image_count+(warmup_count/2);
    auto_resume_time = 0;
  }

  if ( Enabled() ) {
    bool signal = shared_data->signal;
    bool signal_change = (signal != last_signal);

    Debug(3, "Motion detection is enabled signal(%d) signal_change(%d)", signal, signal_change);

    if ( trigger_data->trigger_state != TRIGGER_OFF ) {
      unsigned int score = 0;
      if ( Ready() ) {
        std::string cause;
        Event::StringSetMap noteSetMap;

        if ( trigger_data->trigger_state == TRIGGER_ON ) {
          score += trigger_data->trigger_score;
          Debug(1, "Triggered on score += %d => %d", trigger_data->trigger_score, score);
          if ( !event ) {
            // How could it have a length already?
            //if ( cause.length() )
              //cause += ", ";
            cause += trigger_data->trigger_cause;
          }
          Event::StringSet noteSet;
          noteSet.insert(trigger_data->trigger_text);
          noteSetMap[trigger_data->trigger_cause] = noteSet;
        }

        if ( signal_change ) {
          const char *signalText;
          if ( !signal ) {
            signalText = "Lost";
          } else {
            signalText = "Reacquired";
            score += 100;
          }
          Warning("%s: %s", SIGNAL_CAUSE, signalText);
          if ( event && !signal ) {
            Info("%s: %03d - Closing event %" PRIu64 ", signal loss", name, image_count, event->Id());
            closeEvent();
          }
          if ( !event ) {
            if ( cause.length() )
              cause += ", ";
            cause += SIGNAL_CAUSE;
          }
          Event::StringSet noteSet;
          noteSet.insert(signalText);
          noteSetMap[SIGNAL_CAUSE] = noteSet;
          shared_data->state = state = IDLE;
          shared_data->active = signal;
          ref_image = *snap_image;

        } else if ( signal ) {
          if ( Active() && (function == MODECT || function == MOCORD) ) {
            // All is good, so add motion detection score.
            Event::StringSet zoneSet;
            if ( (!motion_frame_skip) || !(image_count % (motion_frame_skip+1)) ) {
              // Get new score.
              int new_motion_score = DetectMotion(*snap_image, zoneSet);

              Debug(3,
                  "After motion detection, last_motion_score(%d), new motion score(%d)",
                  last_motion_score, new_motion_score
                  );
              last_motion_score = new_motion_score;
            }
            if ( last_motion_score ) {
              score += last_motion_score;
              if ( !event ) {
                if ( cause.length() )
                  cause += ", ";
                cause += MOTION_CAUSE;
              }
              noteSetMap[MOTION_CAUSE] = zoneSet;
            } // end if motion_score
            //shared_data->active = signal; // unneccessary active gets set on signal change
          } // end if active and doing motion detection

          // Check to see if linked monitors are triggering.
          if ( n_linked_monitors > 0 ) {
            // FIXME improve logic here
            bool first_link = true;
            Event::StringSet noteSet;
            for ( int i = 0; i < n_linked_monitors; i++ ) {
              // TODO: Shouldn't we try to connect?
              if ( linked_monitors[i]->isConnected() ) {
                if ( linked_monitors[i]->hasAlarmed() ) {
                  if ( !event ) {
                    if ( first_link ) {
                      if ( cause.length() )
                        cause += ", ";
                      cause += LINKED_CAUSE;
                      first_link = false;
                    }
                  }
                  noteSet.insert(linked_monitors[i]->Name());
                  score += 50;
                }
              } else {
                linked_monitors[i]->connect();
              }
            } // end foreach linked_monit
            if ( noteSet.size() > 0 )
              noteSetMap[LINKED_CAUSE] = noteSet;
          } // end if linked_monitors 

          //TODO: What happens is the event closes and sets recording to false then recording to true again so quickly that our capture daemon never picks it up. Maybe need a refresh flag?
          if ( function == RECORD || function == MOCORD ) {
            if ( event ) {
              Debug(3, "Have signal and recording with open event at (%d.%d)", timestamp->tv_sec, timestamp->tv_usec);

              if ( section_length
                  && ( ( timestamp->tv_sec - video_store_data->recording.tv_sec ) >= section_length )
                  && ( (event_close_mode != CLOSE_TIME) || ! ( timestamp->tv_sec % section_length ) ) 
                 ) {
                Info("%s: %03d - Closing event %" PRIu64 ", section end forced %d - %d = %d >= %d",
                    name, image_count, event->Id(),
                    timestamp->tv_sec, video_store_data->recording.tv_sec,
                    timestamp->tv_sec - video_store_data->recording.tv_sec,
                    section_length
                    );
                closeEvent();
              } // end if section_length
            } // end if event

            if ( !event ) {

              // Create event
              event = new Event(this, *timestamp, "Continuous", noteSetMap, videoRecording);
              shared_data->last_event = event->Id();
              //set up video store data
              snprintf(video_store_data->event_file, sizeof(video_store_data->event_file), "%s", event->getEventFile());
              video_store_data->recording = event->StartTime();

              Info("%s: %03d - Opening new event %" PRIu64 ", section start", name, image_count, event->Id());

              /* To prevent cancelling out an existing alert\prealarm\alarm state */
              if ( state == IDLE ) {
                shared_data->state = state = TAPE;
              }

            } // end if ! event
          } // end if function == RECORD || function == MOCORD)
        } // end if !signal_change && signal

        if ( score ) {
          if ( (state == IDLE) || (state == TAPE) || (state == PREALARM) ) {
            // If we should end then previous continuous event and start a new non-continuous event
            if ( event && event->Frames()
                && (!event->AlarmFrames())
                && (event_close_mode == CLOSE_ALARM)
                && ( ( timestamp->tv_sec - video_store_data->recording.tv_sec ) >= min_section_length )
               ) {
              Info("%s: %03d - Closing event %" PRIu64 ", continuous end,  alarm begins",
                  name, image_count, event->Id());
              closeEvent();
            } else if ( event ) {
            // This is so if we need more than 1 alarm frame before going into alarm, so it is basically if we have enough alarm frames
            Debug(3, "pre-alarm-count in event %d, event frames %d, alarm frames %d event length %d >=? %d",
                Event::PreAlarmCount(), event->Frames(), event->AlarmFrames(), 
                ( timestamp->tv_sec - video_store_data->recording.tv_sec ), min_section_length
                );
            }
            if ( (!pre_event_count) || (Event::PreAlarmCount() >= alarm_frame_count-1) ) {
              shared_data->state = state = ALARM;
              // lets construct alarm cause. It will contain cause + names of zones alarmed
              std::string alarm_cause = "";
              for ( int i=0; i < n_zones; i++ ) {
                if ( zones[i]->Alarmed() ) {
                  alarm_cause = alarm_cause + "," + std::string(zones[i]->Label());
                }
              }
              if ( !alarm_cause.empty() ) alarm_cause[0] = ' ';
              alarm_cause = cause + alarm_cause;
              strncpy(shared_data->alarm_cause, alarm_cause.c_str(), sizeof(shared_data->alarm_cause)-1);
              Info("%s: %03d - Gone into alarm state PreAlarmCount: %u > AlarmFrameCount:%u Cause:%s",
                  name, image_count, Event::PreAlarmCount(), alarm_frame_count, shared_data->alarm_cause);

              if ( !event ) {
                int pre_index;
                int pre_event_images = pre_event_count;

                if ( analysis_fps && pre_event_count ) {
                  // If analysis fps is set,
                  // compute the index for pre event images in the dedicated buffer
                  pre_index = pre_event_buffer_count ? image_count % pre_event_buffer_count : 0;
                  Debug(3, "pre-index %d = image_count(%d) %% pre_event_buffer_count(%d)",
                     pre_index, image_count, pre_event_buffer_count); 

                  // Seek forward the next filled slot in to the buffer (oldest data)
                  // from the current position
                  while ( pre_event_images && !pre_event_buffer[pre_index].timestamp->tv_sec ) {
                    pre_index = (pre_index + 1)%pre_event_buffer_count;
                    // Slot is empty, removing image from counter
                    pre_event_images--;
                  }
                  Debug(3, "pre-index %d, pre-event_images %d",
                     pre_index, pre_event_images); 

                  event = new Event(this, *(pre_event_buffer[pre_index].timestamp), cause, noteSetMap);
                } else {
                  // If analysis fps is not set (analysis performed at capturing framerate),
                  // compute the index for pre event images in the capturing buffer
                  if ( alarm_frame_count > 1 )
                    pre_index = ((index + image_buffer_count) - ((alarm_frame_count - 1) + pre_event_count))%image_buffer_count;
                  else
                    pre_index = ((index + image_buffer_count) - pre_event_count)%image_buffer_count;

                  Debug(3, "Resulting pre_index(%d) from index(%d) + image_buffer_count(%d) - pre_event_count(%d)",
                      pre_index, index, image_buffer_count, pre_event_count);

                  // Seek forward the next filled slot in to the buffer (oldest data)
                  // from the current position
                  while ( pre_event_images && !image_buffer[pre_index].timestamp->tv_sec ) {
                    pre_index = (pre_index + 1)%image_buffer_count;
                    // Slot is empty, removing image from counter
                    pre_event_images--;
                  }

                  event = new Event(this, *(image_buffer[pre_index].timestamp), cause, noteSetMap);
                } // end if analysis_fps && pre_event_count

                shared_data->last_event = event->Id();
                //set up video store data
                snprintf(video_store_data->event_file, sizeof(video_store_data->event_file), "%s", event->getEventFile());
                video_store_data->recording = event->StartTime();

                Info("%s: %03d - Opening new event %" PRIu64 ", alarm start", name, image_count, event->Id());

                if ( pre_event_images ) {
                  if ( analysis_fps ) {
                    for ( int i = 0; i < pre_event_images; i++ ) {
                      timestamps[i] = pre_event_buffer[pre_index].timestamp;
                      images[i] = pre_event_buffer[pre_index].image;
                      pre_index = (pre_index + 1)%pre_event_buffer_count;
                    }
                  } else {
                    for ( int i = 0; i < pre_event_images; i++ ) {
                      timestamps[i] = image_buffer[pre_index].timestamp;
                      images[i] = image_buffer[pre_index].image;
                      pre_index = (pre_index + 1)%image_buffer_count;
                    }
                  }
                  event->AddFrames(pre_event_images, images, timestamps);
                }
                if ( alarm_frame_count ) {
                  event->SavePreAlarmFrames();
                }
              }
            } else if ( state != PREALARM ) {
              Info("%s: %03d - Gone into prealarm state", name, image_count);
              shared_data->state = state = PREALARM;
            }
          } else if ( state == ALERT ) {
            Info("%s: %03d - Gone back into alarm state", name, image_count);
            shared_data->state = state = ALARM;
          }
          last_alarm_count = image_count;
        } else { // not score
          if ( state == ALARM ) {
            Info("%s: %03d - Gone into alert state", name, image_count);
            shared_data->state = state = ALERT;
          } else if ( state == ALERT ) {
            if ( 
                ( image_count-last_alarm_count > post_event_count )
                && ( ( timestamp->tv_sec - video_store_data->recording.tv_sec ) >= min_section_length )
                ) {
              Info("%s: %03d - Left alarm state (%" PRIu64 ") - %d(%d) images",
                  name, image_count, event->Id(), event->Frames(), event->AlarmFrames());
              //if ( function != MOCORD || event_close_mode == CLOSE_ALARM || event->Cause() == SIGNAL_CAUSE )
              if ( ( function != MOCORD && function != RECORD ) || event_close_mode == CLOSE_ALARM ) {
                shared_data->state = state = IDLE;
                Info("%s: %03d - Closing event %" PRIu64 ", alarm end%s",
                    name, image_count, event->Id(), (function==MOCORD)?", section truncated":"");
                closeEvent();
              } else {
                shared_data->state = state = TAPE;
              }
            }
          } // end if ALARM or ALERT

          if ( state == PREALARM ) {
            if ( function != MOCORD ) {
              shared_data->state = state = IDLE;
            } else {
              shared_data->state = state = TAPE;
            }
          }
          if ( Event::PreAlarmCount() )
            Event::EmptyPreAlarmFrames();
        } // end if score or not

        if ( state != IDLE ) {
          if ( state == PREALARM || state == ALARM ) {
            if ( config.create_analysis_images ) {
              bool got_anal_image = false;
              alarm_image.Assign(*snap_image);
              for ( int i = 0; i < n_zones; i++ ) {
                if ( zones[i]->Alarmed() ) {
                  if ( zones[i]->AlarmImage() ) {
                    alarm_image.Overlay(*(zones[i]->AlarmImage()));
                    got_anal_image = true;
                  }
                  if ( config.record_event_stats && (state == ALARM) )
                    zones[i]->RecordStats(event);
                } // end if zone is alarmed
              } // end foreach zone

              if ( got_anal_image ) {
                if ( state == PREALARM )
                  Event::AddPreAlarmFrame(snap_image, *timestamp, score, &alarm_image);
                else
                  event->AddFrame(snap_image, *timestamp, score, &alarm_image);
              } else {
                if ( state == PREALARM )
                  Event::AddPreAlarmFrame(snap_image, *timestamp, score);
                else
                  event->AddFrame(snap_image, *timestamp, score);
              }
            } else {
              // Not doing alarm frame storage
              if ( state == PREALARM ) {
                Event::AddPreAlarmFrame(snap_image, *timestamp, score);
              } else {
                event->AddFrame(snap_image, *timestamp, score);
                if ( config.record_event_stats ) {
                  for ( int i = 0; i < n_zones; i++ ) {
                    if ( zones[i]->Alarmed() )
                      zones[i]->RecordStats(event);
                  }
                } // end if  config.record_event_stats
              }
            } // end if config.create_analysis_images 

            if ( event ) {
              if ( noteSetMap.size() > 0 )
                event->updateNotes(noteSetMap);

              if ( section_length
                  && ( ( timestamp->tv_sec - video_store_data->recording.tv_sec ) >= section_length )
                  && ! (image_count % fps_report_interval)
                 ) {
                Warning("%s: %03d - event %" PRIu64 ", has exceeded desired section length. %d - %d = %d >= %d",
                    name, image_count, event->Id(),
                    timestamp->tv_sec, video_store_data->recording.tv_sec,
                    timestamp->tv_sec - video_store_data->recording.tv_sec,
                    section_length
                    );
                closeEvent();
                event = new Event(this, *timestamp, cause, noteSetMap);
                shared_data->last_event = event->Id();
                //set up video store data
                snprintf(video_store_data->event_file, sizeof(video_store_data->event_file), "%s", event->getEventFile());
                video_store_data->recording = event->StartTime();

              }
            } // end if event

          } else if ( state == ALERT ) {
            event->AddFrame(snap_image, *timestamp);
            if ( noteSetMap.size() > 0 )
              event->updateNotes(noteSetMap);
          } else if ( state == TAPE ) {
            //Video Storage: activate only for supported cameras. Event::AddFrame knows whether or not we are recording video and saves frames accordingly
            //if((GetOptVideoWriter() == 2) && camera->SupportsNativeVideo()) {
              // I don't think this is required, and causes problems, as the event file hasn't been setup yet.
              //Warning("In state TAPE,
              //video_store_data->recording = event->StartTime();
            //}
            if ( (!frame_skip) || !(image_count%(frame_skip+1)) ) {
              if ( config.bulk_frame_interval > 1 ) {
                event->AddFrame(snap_image, *timestamp, (event->Frames()<pre_event_count?0:-1));
              } else {
                event->AddFrame(snap_image, *timestamp);
              }
            }
          }
        } // end if ! IDLE
      }
    } else {
      if ( event ) {
        Info("%s: %03d - Closing event %" PRIu64 ", trigger off", name, image_count, event->Id());
        closeEvent();
      }
      shared_data->state = state = IDLE;
      trigger_data->trigger_state = TRIGGER_CANCEL;
    } // end if ( trigger_data->trigger_state != TRIGGER_OFF )

    if ( (!signal_change && signal) && (function == MODECT || function == MOCORD) ) {
      if ( state == ALARM ) {
         ref_image.Blend( *snap_image, alarm_ref_blend_perc );
      } else {
         ref_image.Blend( *snap_image, ref_blend_perc );
      }
    }
    last_signal = signal;
  } // end if Enabled()

  shared_data->last_read_index = index % image_buffer_count;
  //shared_data->last_read_time = image_buffer[index].timestamp->tv_sec;
  shared_data->last_read_time = now.tv_sec;

  if ( analysis_fps && pre_event_buffer_count ) {
    // If analysis fps is set, add analysed image to dedicated pre event buffer
    int pre_index = image_count%pre_event_buffer_count;
    pre_event_buffer[pre_index].image->Assign(*snap->image);
    memcpy(pre_event_buffer[pre_index].timestamp, snap->timestamp, sizeof(struct timeval));
  }

  image_count++;

  return true;
} // end Monitor::Analyze

void Monitor::Reload() {
  Debug(1, "Reloading monitor %s", name);

  if ( event ) {
    Info("%s: %03d - Closing event %" PRIu64 ", reloading", name, image_count, event->Id());
    closeEvent();
  }

  static char sql[ZM_SQL_MED_BUFSIZ];
  // This seems to have fallen out of date.
  snprintf(sql, sizeof(sql), 
      "SELECT `Function`+0, `Enabled`, `LinkedMonitors`, `EventPrefix`, `LabelFormat`, "
      "`LabelX`, `LabelY`, `LabelSize`, `WarmupCount`, `PreEventCount`, `PostEventCount`, "
      "`AlarmFrameCount`, `SectionLength`, `MinSectionLength`, `FrameSkip`, "
      "`MotionFrameSkip`, `AnalysisFPSLimit`, `AnalysisUpdateDelay`, `MaxFPS`, `AlarmMaxFPS`, "
      "`FPSReportInterval`, `RefBlendPerc`, `AlarmRefBlendPerc`, `TrackMotion`, "
      "`SignalCheckPoints`, `SignalCheckColour` FROM `Monitors` WHERE `Id` = '%d'", id);

  zmDbRow *row = zmDbFetchOne(sql);
  if ( !row ) {
    Error("Can't run query: %s", mysql_error(&dbconn));
  } else if ( MYSQL_ROW dbrow = row->mysql_row() ) {
    int index = 0;
    function = (Function)atoi(dbrow[index++]);
    enabled = atoi(dbrow[index++]);
    const char *p_linked_monitors = dbrow[index++];

    if ( dbrow[index] ) {
      strncpy(event_prefix, dbrow[index++], sizeof(event_prefix)-1);
    } else {
      event_prefix[0] = 0;
      index++;
    }
    if ( dbrow[index] ) {
      strncpy(label_format, dbrow[index++], sizeof(label_format)-1);
    } else {
      label_format[0] = 0;
      index++;
    }

    label_coord = Coord( atoi(dbrow[index]), atoi(dbrow[index+1]) ); index += 2;
    label_size = atoi(dbrow[index++]);
    warmup_count = atoi(dbrow[index++]);
    pre_event_count = atoi(dbrow[index++]);
    post_event_count = atoi(dbrow[index++]);
    alarm_frame_count = atoi(dbrow[index++]);
    section_length = atoi(dbrow[index++]);
    min_section_length = atoi(dbrow[index++]);
    frame_skip = atoi(dbrow[index++]);
    motion_frame_skip = atoi(dbrow[index++]);
    analysis_fps = dbrow[index] ? strtod(dbrow[index], NULL) : 0; index++;
    analysis_update_delay = strtoul(dbrow[index++], NULL, 0);

    capture_max_fps = dbrow[index] ? atof(dbrow[index]) : 0.0; index++;
    capture_delay = ( capture_max_fps > 0.0 ) ? int(DT_PREC_3/capture_max_fps) : 0;

    alarm_capture_delay = (dbrow[index]&&atof(dbrow[index])>0.0)?int(DT_PREC_3/atof(dbrow[index])):0; index++;
    fps_report_interval = atoi(dbrow[index++]);
    ref_blend_perc = atoi(dbrow[index++]);
    alarm_ref_blend_perc = atoi(dbrow[index++]);
    track_motion = atoi(dbrow[index++]);

    signal_check_points = dbrow[index]?atoi(dbrow[index]):0; index++;
    signal_check_colour = strtol(dbrow[index][0]=='#'?dbrow[index]+1:dbrow[index], 0, 16); index++;

    shared_data->state = state = IDLE;
    shared_data->alarm_x = shared_data->alarm_y = -1;
    if ( enabled )
      shared_data->active = true;
    ready_count = image_count+warmup_count;

    ReloadLinkedMonitors(p_linked_monitors);
    delete row;
  } // end if row

  ReloadZones();
}  // end void Monitor::Reload()

void Monitor::ReloadZones() {
  Debug(1, "Reloading zones for monitor %s", name);
  for ( int i = 0; i < n_zones; i++ ) {
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
    for ( int i = 0; i < n_linked_monitors; i++ ) {
      delete linked_monitors[i];
    }
    delete[] linked_monitors;
    linked_monitors = 0;
  }

  n_linked_monitors = 0;
  if ( p_linked_monitors ) {
    int n_link_ids = 0;
    unsigned int link_ids[256];

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

        db_mutex.lock();
        static char sql[ZM_SQL_SML_BUFSIZ];
        snprintf(sql, sizeof(sql),
            "SELECT `Id`, `Name` FROM `Monitors`"
            "  WHERE `Id` = %d"
            "   AND `Function` != 'None'"
            "   AND `Function` != 'Monitor'"
            "   AND `Enabled`=1",
            link_ids[i] );
        if ( mysql_query(&dbconn, sql) ) {
					db_mutex.unlock();
          Error("Can't run query: %s", mysql_error(&dbconn));
          continue;
        }

        MYSQL_RES *result = mysql_store_result(&dbconn);
        if ( !result ) {
					db_mutex.unlock();
          Error("Can't use query result: %s", mysql_error(&dbconn));
          continue;
        }
        db_mutex.unlock();
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
  Debug(1, "Got %d monitors", n_monitors);
  delete[] monitors;
  monitors = new Monitor *[n_monitors];
  for ( int i=0; MYSQL_ROW dbrow = mysql_fetch_row(result); i++ ) {
    monitors[i] = Monitor::Load(dbrow, 1, purpose);
  }
  if ( mysql_errno(&dbconn) ) {
    Error("Can't fetch row: %s", mysql_error(&dbconn));
    return 0;
  }
  mysql_free_result(result);

  return n_monitors;
} // end int Monitor::LoadMonitors(std::string sql, Monitor **&monitors, Purpose purpose)

#if ZM_HAS_V4L
int Monitor::LoadLocalMonitors(const char *device, Monitor **&monitors, Purpose purpose) {

  std::string sql = load_monitor_sql + " WHERE `Function` != 'None' AND `Type` = 'Local'";

  if ( device[0] )
    sql += " AND `Device`='" + std::string(device) + "'";
  if ( staticConfig.SERVER_ID )
    sql += stringtf(" AND `ServerId`=%d", staticConfig.SERVER_ID);
  return LoadMonitors(sql, monitors, purpose);
} // end int Monitor::LoadLocalMonitors(const char *device, Monitor **&monitors, Purpose purpose)
#endif // ZM_HAS_V4L

int Monitor::LoadRemoteMonitors(const char *protocol, const char *host, const char *port, const char *path, Monitor **&monitors, Purpose purpose) {
  std::string sql = load_monitor_sql + " WHERE `Function` != 'None' AND `Type` = 'Remote'";
  if ( staticConfig.SERVER_ID )
    sql += stringtf(" AND `ServerId`=%d", staticConfig.SERVER_ID);

  if ( protocol )
    sql += stringtf(" AND `Protocol` = '%s' AND `Host` = '%s' AND `Port` = '%s' AND `Path` = '%s'", protocol, host, port, path);
  return LoadMonitors(sql, monitors, purpose);
} // end int Monitor::LoadRemoteMonitors

int Monitor::LoadFileMonitors(const char *file, Monitor **&monitors, Purpose purpose) {
  std::string sql = load_monitor_sql + " WHERE `Function` != 'None' AND `Type` = 'File'";
  if ( file[0] )
    sql += " AND `Path`='" + std::string(file) + "'";
  if ( staticConfig.SERVER_ID ) {
    sql += stringtf(" AND `ServerId`=%d", staticConfig.SERVER_ID);
  }
  return LoadMonitors(sql, monitors, purpose);
} // end int Monitor::LoadFileMonitors

#if HAVE_LIBAVFORMAT
int Monitor::LoadFfmpegMonitors(const char *file, Monitor **&monitors, Purpose purpose) {
  std::string sql = load_monitor_sql + " WHERE `Function` != 'None' AND `Type` = 'Ffmpeg'";
  if ( file[0] )
    sql += " AND `Path` = '" + std::string(file) + "'";

  if ( staticConfig.SERVER_ID ) {
    sql += stringtf(" AND `ServerId`=%d", staticConfig.SERVER_ID);
  }
  return LoadMonitors(sql, monitors, purpose);
} // end int Monitor::LoadFfmpegMonitors
#endif // HAVE_LIBAVFORMAT

/* For reference
std::string load_monitor_sql =
"SELECT `Id`, `Name`, `ServerId`, `StorageId`, `Type`, `Function`+0, `Enabled`, `LinkedMonitors`, "
"`AnalysisFPSLimit`, `AnalysisUpdateDelay`, `MaxFPS`, `AlarmMaxFPS`,"
"`Device`, `Channel`, `Format`, `V4LMultiBuffer`, `V4LCapturesPerFrame`, " // V4L Settings
"`Protocol`, `Method`, `Options`, `User`, `Pass`, `Host`, `Port`, `Path`, `Width`, `Height`, `Colours`, `Palette`, `Orientation`+0, `Deinterlacing`, "
"`DecoderHWAccelName`, `DecoderHWAccelDevice`, `RTSPDescribe`, "
"`SaveJPEGs`, `VideoWriter`, `EncoderParameters`, "
//" OutputCodec, Encoder, OutputContainer, "
"`RecordAudio`, "
"`Brightness`, `Contrast`, `Hue`, `Colour`, "
"`EventPrefix`, `LabelFormat`, `LabelX`, `LabelY`, `LabelSize`,"
"`ImageBufferCount`, `WarmupCount`, `PreEventCount`, `PostEventCount`, `StreamReplayBuffer`, `AlarmFrameCount`, "
"`SectionLength`, `MinSectionLength`, `FrameSkip`, `MotionFrameSkip`, "
"`FPSReportInterval`, `RefBlendPerc`, `AlarmRefBlendPerc`, `TrackMotion`, `Exif`, `SignalCheckPoints`, `SignalCheckColour` FROM `Monitors`";
*/

Monitor *Monitor::Load(MYSQL_ROW dbrow, bool load_zones, Purpose purpose) {
  int col = 0;

  int id = atoi(dbrow[col]); col++;
  const char *name = dbrow[col]; col++;
  int server_id = dbrow[col] ? atoi(dbrow[col]) : 0; col++;
  int storage_id = atoi(dbrow[col]); col++;
  std::string type = dbrow[col] ? dbrow[col] : ""; col++;
  Function function = (Function)atoi(dbrow[col]); col++;
  int enabled = dbrow[col] ? atoi(dbrow[col]) : 0; col++;
  const char *linked_monitors = dbrow[col];col++;

  double analysis_fps = dbrow[col] ? strtod(dbrow[col], NULL) : 0; col++;
  unsigned int analysis_update_delay = strtoul(dbrow[col++], NULL, 0);

  double capture_max_fps = dbrow[col] ? atof(dbrow[col]) : 0.0; col++;
  double capture_delay = ( capture_max_fps > 0.0 ) ? int(DT_PREC_3/capture_max_fps) : 0;
  unsigned int alarm_capture_delay = (dbrow[col]&&atof(dbrow[col])>0.0)?int(DT_PREC_3/atof(dbrow[col])):0; col++;

  const char *device = dbrow[col]; col++;
  int channel = atoi(dbrow[col]); col++;
  int format = atoi(dbrow[col]); col++;
  bool v4l_multi_buffer = config.v4l_multi_buffer;
  if ( dbrow[col] ) {
    if (*dbrow[col] == '0' ) {
      v4l_multi_buffer = false;
    } else if ( *dbrow[col] == '1' ) {
      v4l_multi_buffer = true;
    }
  }
  col++;

  int v4l_captures_per_frame = 0;
  if ( dbrow[col] ) {
    v4l_captures_per_frame = atoi(dbrow[col]);
  } else {
    v4l_captures_per_frame = config.captures_per_frame;
  }
  Debug(1, "Got %d for v4l_captures_per_frame", v4l_captures_per_frame);
  col++;

  std::string protocol = dbrow[col] ? dbrow[col] : ""; col++;
  std::string method = dbrow[col] ? dbrow[col] : ""; col++;
  std::string options = dbrow[col] ? dbrow[col] : ""; col++;
  std::string user = dbrow[col] ? dbrow[col] : ""; col++;
  std::string pass = dbrow[col] ? dbrow[col] : ""; col++;
  std::string host = dbrow[col] ? dbrow[col] : ""; col++;
  std::string port = dbrow[col] ? dbrow[col] : ""; col++;
  std::string path = dbrow[col] ? dbrow[col] : ""; col++;
  int width = atoi(dbrow[col]); col++;
  int height = atoi(dbrow[col]); col++;
  int colours = atoi(dbrow[col]); col++;
  int palette = atoi(dbrow[col]); col++;
  Orientation orientation = (Orientation)atoi(dbrow[col]); col++;
  int deinterlacing = atoi(dbrow[col]); col++;
  std::string decoder_hwaccel_name = dbrow[col] ? dbrow[col] : ""; col++;
  std::string decoder_hwaccel_device = dbrow[col] ? dbrow[col] : ""; col++;

  bool rtsp_describe = (dbrow[col] && *dbrow[col] != '0'); col++;

  int savejpegs = atoi(dbrow[col]); col++;
  VideoWriter videowriter = (VideoWriter)atoi(dbrow[col]); col++;
  const char *encoderparams = dbrow[col] ? dbrow[col] : ""; col++;
  bool record_audio = (*dbrow[col] != '0'); col++;

  int brightness = atoi(dbrow[col]); col++;
  int contrast = atoi(dbrow[col]); col++;
  int hue = atoi(dbrow[col]); col++;
  int colour = atoi(dbrow[col]); col++;

  const char *event_prefix = dbrow[col]; col ++;
  const char *label_format = dbrow[col] ? dbrow[col] : ""; col ++;
  Coord label_coord = Coord( atoi(dbrow[col]), atoi(dbrow[col+1]) ); col += 2;
  int label_size = atoi(dbrow[col]); col++;

  int image_buffer_count = atoi(dbrow[col]); col++;
  int warmup_count = atoi(dbrow[col]); col++;
  int pre_event_count = atoi(dbrow[col]); col++;
  int post_event_count = atoi(dbrow[col]); col++;
  int stream_replay_buffer = atoi(dbrow[col]); col++;
  int alarm_frame_count = atoi(dbrow[col]); col++;
  int section_length = atoi(dbrow[col]); col++;
  int min_section_length = atoi(dbrow[col]); col++;
  int frame_skip = atoi(dbrow[col]); col++;
  int motion_frame_skip = atoi(dbrow[col]); col++;
  int fps_report_interval = atoi(dbrow[col]); col++;
  int ref_blend_perc = atoi(dbrow[col]); col++;
  int alarm_ref_blend_perc = atoi(dbrow[col]); col++;
  int track_motion = atoi(dbrow[col]); col++;
  bool embed_exif = (*dbrow[col] != '0'); col++;
  int signal_check_points = dbrow[col] ? atoi(dbrow[col]) : 0;col++;
  int signal_check_color = strtol(dbrow[col][0] == '#' ? dbrow[col]+1 : dbrow[col], 0, 16); col++;

  Camera *camera = 0;
  if ( type == "Local" ) {

#if ZM_HAS_V4L
    int extras = (deinterlacing>>24)&0xff;

    camera = new LocalCamera(
        id,
        device,
        channel,
        format,
        v4l_multi_buffer,
        v4l_captures_per_frame,
        method,
        width,
        height,
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
#else
    Fatal("ZoneMinder not built with Local Camera support");
#endif
  } else if ( type == "Remote" ) {
    if ( protocol == "http" ) {
      camera = new RemoteCameraHttp(
        id,
        method,
        host,
        port,
        path,
        width,
        height,
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
        width,
        height,
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
      Fatal("Unexpected remote camera protocol '%s'", protocol.c_str());
    }
  } else if ( type == "File" ) {
    camera = new FileCamera(
      id,
      path.c_str(),
      width,
      height,
      colours,
      brightness,
      contrast,
      hue,
      colour,
      purpose==CAPTURE,
      record_audio
    );
  } else if ( type == "Ffmpeg" ) {
#if HAVE_LIBAVFORMAT
    camera = new FfmpegCamera(
      id,
      path,
      method,
      options,
      width,
      height,
      colours,
      brightness,
      contrast,
      hue,
      colour,
      purpose==CAPTURE,
      record_audio,
      decoder_hwaccel_name,
      decoder_hwaccel_device
      );
#endif // HAVE_LIBAVFORMAT
  } else if ( type == "NVSocket" ) {
      camera = new RemoteCameraNVSocket(
        id,
        host.c_str(),
        port.c_str(),
        path.c_str(),
        width,
        height,
        colours,
        brightness,
        contrast,
        hue,
        colour,
        purpose==CAPTURE,
        record_audio
      );
  } else if ( type == "Libvlc" ) {
#if HAVE_LIBVLC
    camera = new LibvlcCamera(
      id,
      path.c_str(),
      method,
      options,
      width,
      height,
      colours,
      brightness,
      contrast,
      hue,
      colour,
      purpose==CAPTURE,
      record_audio
    );
#else // HAVE_LIBVLC
    Fatal("You must have vlc libraries installed to use vlc cameras for monitor %d", id);
#endif // HAVE_LIBVLC
  } else if ( type == "cURL" ) {
#if HAVE_LIBCURL
    camera = new cURLCamera(
      id,
      path.c_str(),
      user.c_str(),
      pass.c_str(),
      width,
      height,
      colours,
      brightness,
      contrast,
      hue,
      colour,
      purpose==CAPTURE,
      record_audio
    );
#else // HAVE_LIBCURL
    Fatal("You must have libcurl installed to use ffmpeg cameras for monitor %d", id);
#endif // HAVE_LIBCURL
  } else if ( type == "VNC" ) {
#if HAVE_LIBVNC
    camera = new VncCamera(
      id,
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
      purpose==CAPTURE,
      record_audio
    );
#else // HAVE_LIBVNC
    Fatal("You must have libvnc installed to use VNC cameras for monitor id %d", id);
#endif // HAVE_LIBVNC
  } else {
    Fatal("Bogus monitor type '%s' for monitor %d", type.c_str(), id);
  } // end if type

  Monitor *monitor = new Monitor(
      id,
      name,
      server_id,
      storage_id,
      (int)function,
      enabled,
      linked_monitors,
      camera,
      orientation,
      deinterlacing,
      decoder_hwaccel_name,
      decoder_hwaccel_device,
      savejpegs,
      videowriter,
      encoderparams,
      record_audio,
      event_prefix,
      label_format,
      label_coord,
      label_size,
      image_buffer_count,
      warmup_count,
      pre_event_count,
      post_event_count,
      stream_replay_buffer,
      alarm_frame_count,
      section_length,
      min_section_length,
      frame_skip,
      motion_frame_skip,
      capture_max_fps,
      analysis_fps,
      analysis_update_delay,
      capture_delay,
      alarm_capture_delay,
      fps_report_interval,
      ref_blend_perc,
      alarm_ref_blend_perc,
      track_motion,
      signal_check_points,
      signal_check_color,
      embed_exif,
      purpose,
      0,
      0
        );
  camera->setMonitor(monitor);
  Zone **zones = 0;
  int n_zones = Zone::Load(monitor, zones);
  monitor->AddZones(n_zones, zones);
  monitor->AddPrivacyBitmask(zones);
  Debug(1, "Loaded monitor %d(%s), %d zones", id, name, n_zones);
  return monitor;
} // end Monitor *Monitor::Load(MYSQL_ROW dbrow, bool load_zones, Purpose purpose)

Monitor *Monitor::Load(unsigned int p_id, bool load_zones, Purpose purpose) {
  std::string sql = load_monitor_sql + stringtf(" WHERE `Id`=%d", p_id);

  zmDbRow dbrow;
  if ( ! dbrow.fetch(sql.c_str()) ) {
    Error("Can't use query result: %s", mysql_error(&dbconn));
    return NULL;
  }
  Monitor *monitor = Monitor::Load(dbrow.mysql_row(), load_zones, purpose);

  return monitor;
} // end Monitor *Monitor::Load(unsigned int p_id, bool load_zones, Purpose purpose)

/* Returns 0 on success, even if no new images are available (transient error)
 * Returns -1 on failure.
 */
int Monitor::Capture() {
  static int FirstCapture = 1; // Used in de-interlacing to indicate whether this is the even or odd image
  int captureResult;

  unsigned int index = image_count%image_buffer_count;
  Image* capture_image = image_buffer[index].image;

  unsigned int deinterlacing_value = deinterlacing & 0xff;

  if ( deinterlacing_value == 4 ) {
    if ( FirstCapture != 1 ) {
      /* Copy the next image into the shared memory */
      capture_image->CopyBuffer(*(next_buffer.image));
    }

    /* Capture a new next image */

    //Check if FFMPEG camera
    // Icon: I don't think we can support de-interlacing on ffmpeg input.... most of the time it will be h264 or mpeg4
    if ( ( videowriter == H264PASSTHROUGH ) && camera->SupportsNativeVideo() ) {
      captureResult = camera->CaptureAndRecord(*(next_buffer.image),
          video_store_data->recording,
          video_store_data->event_file );
    } else {
      captureResult = camera->Capture(*(next_buffer.image));
    }

    if ( FirstCapture ) {
      FirstCapture = 0;
      return 0;
    }

  } else {
    //Check if FFMPEG camera
    if ( (videowriter == H264PASSTHROUGH) && camera->SupportsNativeVideo() ) {
      //Warning("ZMC: Recording: %d", video_store_data->recording);
      // Should return -1 on error, like loss of signal.  Should return 0 if ok but no video frame. > 0 for received a frame.
      captureResult = camera->CaptureAndRecord(
          *capture_image,
          video_store_data->recording,
          video_store_data->event_file
          );
    } else {
      /* Capture directly into image buffer, avoiding the need to memcpy() */
      captureResult = camera->Capture(*capture_image);
    }
  } // end if deinterlacing or not

  if ( captureResult < 0 ) {
    Info("Return from Capture (%d), signal loss", captureResult);
    // Tell zma to end the event. zma will reset TRIGGER
    trigger_data->trigger_state = TRIGGER_OFF;
    // Unable to capture image for temporary reason
    // Fake a signal loss image
    Rgb signalcolor;
    signalcolor = rgb_convert(signal_check_colour, ZM_SUBPIX_ORDER_BGR); /* HTML colour code is actually BGR in memory, we want RGB */
    capture_image->Fill(signalcolor);
  } else if ( captureResult > 0 ) {
    Debug(4, "Return from Capture (%d)", captureResult);

    /* Deinterlacing */
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

    if ( capture_image->Size() > camera->ImageSize() ) {
      Error("Captured image %d does not match expected size %d check width, height and colour depth",
          capture_image->Size(),camera->ImageSize() );
      return -1;
    }

    if ( (index == shared_data->last_read_index) && (function > MONITOR) ) {
      Warning("Buffer overrun at index %d, image %d, slow down capture, speed up analysis or increase ring buffer size",
          index, image_count );
      time_t now = time(0);
      double approxFps = double(image_buffer_count)/double(now-image_buffer[index].timestamp->tv_sec);
      time_t last_read_delta = now - shared_data->last_read_time;
      if ( last_read_delta > (image_buffer_count/approxFps) ) {
        Warning("Last image read from shared memory %ld seconds ago, zma may have gone away", last_read_delta);
        shared_data->last_read_index = image_buffer_count;
      }
    } // end if overrun

    if ( privacy_bitmask )
      capture_image->MaskPrivacy(privacy_bitmask);

    // Might be able to remove this call, when we start passing around ZMPackets, which will already have a timestamp
    gettimeofday(image_buffer[index].timestamp, NULL);
    if ( config.timestamp_on_capture ) {
      TimestampImage(capture_image, image_buffer[index].timestamp);
    }
    // Maybe we don't need to do this on all camera types
    shared_data->signal = signal_check_points ? CheckSignal(capture_image) : true;
    shared_data->last_write_index = index;
    shared_data->last_write_time = image_buffer[index].timestamp->tv_sec;

    image_count++;

    if ( image_count && fps_report_interval && ( (!(image_count%fps_report_interval)) || image_count < 5 ) ) {
      time_t now = image_buffer[index].timestamp->tv_sec;
      // If we are too fast, we get div by zero. This seems to happen in the case of audio packets.
      if ( now != last_fps_time ) {
        // # of images per interval / the amount of time it took
        double new_fps = double(image_count%fps_report_interval?image_count:fps_report_interval)/(now-last_fps_time);
        unsigned int new_camera_bytes = camera->Bytes();
        unsigned int new_capture_bandwidth = (new_camera_bytes - last_camera_bytes)/(now-last_fps_time);
        last_camera_bytes = new_camera_bytes;
        //Info( "%d -> %d -> %d", fps_report_interval, now, last_fps_time );
        //Info( "%d -> %d -> %lf -> %lf", now-last_fps_time, fps_report_interval/(now-last_fps_time), double(fps_report_interval)/(now-last_fps_time), fps );
        Info("%s: images:%d - Capturing at %.2lf fps, capturing bandwidth %ubytes/sec",
            name, image_count, new_fps, new_capture_bandwidth);
        last_fps_time = now;
        fps = new_fps;
        db_mutex.lock();
        static char sql[ZM_SQL_SML_BUFSIZ];
        // The reason we update the Status as well is because if mysql restarts, the Monitor_Status table is lost,
        // and nothing else will update the status until zmc restarts. Since we are successfully capturing we can
        // assume that we are connected
        snprintf(sql, sizeof(sql),
            "INSERT INTO Monitor_Status (MonitorId,CaptureFPS,CaptureBandwidth,Status) "
           "VALUES (%d, %.2lf, %u, 'Connected') ON DUPLICATE KEY UPDATE "
           "CaptureFPS = %.2lf, CaptureBandwidth=%u, Status='Connected'",
            id, fps, new_capture_bandwidth, fps, new_capture_bandwidth);
        if ( mysql_query(&dbconn, sql) ) {
          Error("Can't run query: %s", mysql_error(&dbconn));
        }
        db_mutex.unlock();
        Debug(4,sql);
      } // end if time has changed since last update
    } // end if it might be time to report the fps
  } // end if captureResult

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
} // end int Monitor::Capture

void Monitor::TimestampImage(Image *ts_image, const struct timeval *ts_time) const {
  if ( !label_format[0] )
    return;

  // Expand the strftime macros first
  char label_time_text[256];
  strftime(label_time_text, sizeof(label_time_text), label_format, localtime(&ts_time->tv_sec));

  char label_text[1024];
  const char *s_ptr = label_time_text;
  char *d_ptr = label_text;
  while ( *s_ptr && ((d_ptr-label_text) < (unsigned int)sizeof(label_text)) ) {
    if ( *s_ptr == config.timestamp_code_char[0] ) {
      bool found_macro = false;
      switch ( *(s_ptr+1) ) {
        case 'N' :
          d_ptr += snprintf(d_ptr, sizeof(label_text)-(d_ptr-label_text), "%s", name);
          found_macro = true;
          break;
        case 'Q' :
          d_ptr += snprintf(d_ptr, sizeof(label_text)-(d_ptr-label_text), "%s", trigger_data->trigger_showtext);
          found_macro = true;
          break;
        case 'f' :
          d_ptr += snprintf(d_ptr, sizeof(label_text)-(d_ptr-label_text), "%02ld", ts_time->tv_usec/10000);
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
  ts_image->Annotate( label_text, label_coord, label_size );
} // end void Monitor::TimestampImage

bool Monitor::closeEvent() {
  if ( !event )
    return false;

  if ( function == RECORD || function == MOCORD ) {
    //FIXME Is this neccessary? ENdTime should be set in the destructor
    gettimeofday(&(event->EndTime()), NULL);
  }
  if ( event_delete_thread ) {
    event_delete_thread->join();
    delete event_delete_thread;
    event_delete_thread = NULL;
  }
#if 0
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
} // end bool Monitor::closeEvent()

unsigned int Monitor::DetectMotion(const Image &comp_image, Event::StringSet &zoneSet) {
  bool alarm = false;
  unsigned int score = 0;

  if ( n_zones <= 0 ) return alarm;

  ref_image.Delta(comp_image, &delta_image);

  if ( config.record_diag_images ) {
    ref_image.WriteJpeg(diag_path_r.c_str(), config.record_diag_images_fifo);
    delta_image.WriteJpeg(diag_path_d.c_str(), config.record_diag_images_fifo);
  }

  // Blank out all exclusion zones
  for ( int n_zone = 0; n_zone < n_zones; n_zone++ ) {
    Zone *zone = zones[n_zone];
    // need previous alarmed state for preclusive zone, so don't clear just yet
    if ( !zone->IsPreclusive() )
      zone->ClearAlarm();
    if ( !zone->IsInactive() ) {
      continue;
    }
    Debug(3, "Blanking inactive zone %s", zone->Label());
    delta_image.Fill(RGB_BLACK, zone->GetPolygon());
  } // end foreach zone

  // Check preclusive zones first
  for ( int n_zone = 0; n_zone < n_zones; n_zone++ ) {
    Zone *zone = zones[n_zone];
    if ( !zone->IsPreclusive() ) {
      continue;
    }
    int old_zone_score = zone->Score();
    bool old_zone_alarmed = zone->Alarmed();
    Debug(3, "Checking preclusive zone %s - old score: %d, state: %s",
        zone->Label(),old_zone_score, zone->Alarmed()?"alarmed":"quiet");
    if ( zone->CheckAlarms(&delta_image) ) {
      alarm = true;
      score += zone->Score();
      zone->SetAlarm();
      Debug(3, "Zone is alarmed, zone score = %d", zone->Score());
      zoneSet.insert(zone->Label());
      //zone->ResetStats();
    } else {
      // check if end of alarm
      if ( old_zone_alarmed ) {
        Debug(3, "Preclusive Zone %s alarm Ends. Previous score: %d",
            zone->Label(), old_zone_score);
        if ( old_zone_score > 0 ) {
          zone->SetExtendAlarmCount(zone->GetExtendAlarmFrames());
        }
        if ( zone->CheckExtendAlarmCount() ) {
          alarm = true;
          zone->SetAlarm();
        } else {
          zone->ClearAlarm();
        }
      }
    } // end if CheckAlarms
  } // end foreach zone

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
      Debug(3, "Checking active zone %s", zone->Label());
      if ( zone->CheckAlarms(&delta_image) ) {
        alarm = true;
        score += zone->Score();
        zone->SetAlarm();
        Debug(3, "Zone is alarmed, zone score = %d", zone->Score());
        zoneSet.insert(zone->Label());
        if ( config.opt_control && track_motion ) {
          if ( (int)zone->Score() > top_score ) {
            top_score = zone->Score();
            alarm_centre = zone->GetAlarmCentre();
          }
        }
      }
    } // end foreach zone

    if ( alarm ) {
      for ( int n_zone = 0; n_zone < n_zones; n_zone++ ) {
        Zone *zone = zones[n_zone];
        // Wasn't this zone already checked above?
        if ( !zone->IsInclusive() ) {
          continue;
        }
        Debug(3, "Checking inclusive zone %s", zone->Label());
        if ( zone->CheckAlarms(&delta_image) ) {
          alarm = true;
          score += zone->Score();
          zone->SetAlarm();
          Debug(3, "Zone is alarmed, zone score = %d", zone->Score());
          zoneSet.insert( zone->Label() );
          if ( config.opt_control && track_motion ) {
            if ( zone->Score() > (unsigned int)top_score ) {
              top_score = zone->Score();
              alarm_centre = zone->GetAlarmCentre();
            }
          }
        } // end if CheckAlarm
      } // end foreach zone
    } else {
      // Find all alarm pixels in exclusive zones
      for ( int n_zone = 0; n_zone < n_zones; n_zone++ ) {
        Zone *zone = zones[n_zone];
        if ( !zone->IsExclusive() ) {
          continue;
        }
        Debug(3, "Checking exclusive zone %s", zone->Label());
        if ( zone->CheckAlarms(&delta_image) ) {
          alarm = true;
          score += zone->Score();
          zone->SetAlarm();
          Debug(3, "Zone is alarmed, zone score = %d", zone->Score());
          zoneSet.insert(zone->Label());
        }
      } // end foreach zone
    } // end if alarm or not
  } // end if alarm

  if ( top_score > 0 ) {
    shared_data->alarm_x = alarm_centre.X();
    shared_data->alarm_y = alarm_centre.Y();

    Info("Got alarm centre at %d,%d, at count %d",
        shared_data->alarm_x, shared_data->alarm_y, image_count);
  } else {
    shared_data->alarm_x = shared_data->alarm_y = -1;
  }

  // This is a small and innocent hack to prevent scores of 0 being returned in alarm state
  return score ? score : alarm;
} // end MotionDetect

bool Monitor::DumpSettings(char *output, bool verbose) {
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
  sprintf(output+strlen(output), "Zones : %d\n", n_zones );
  for ( int i = 0; i < n_zones; i++ ) {
    zones[i]->DumpSettings(output+strlen(output), verbose);
  }
  return true;
} // bool Monitor::DumpSettings(char *output, bool verbose)

unsigned int Monitor::Colours() const { return camera->Colours(); }
unsigned int Monitor::SubpixelOrder() const { return camera->SubpixelOrder(); }
int Monitor::PrimeCapture() const { return camera->PrimeCapture(); }
int Monitor::PreCapture() const { return camera->PreCapture(); }
int Monitor::PostCapture() const { return camera->PostCapture() ; }
int Monitor::Close() { return camera->Close(); };
Monitor::Orientation Monitor::getOrientation() const { return orientation; }

Monitor::Snapshot *Monitor::getSnapshot() const {
  return &image_buffer[ shared_data->last_write_index%image_buffer_count ];
}

std::vector<Group *> Monitor::Groups() {
  // At the moment, only load groups once.
  if ( !groups.size() ) {
    std::string sql = stringtf(
        "SELECT `Id`, `ParentId`, `Name` FROM `Groups` WHERE `Groups.Id` IN "
        "(SELECT `GroupId` FROM `Groups_Monitors` WHERE `MonitorId`=%d)",id);
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
} // end Monitor::Groups()

StringVector Monitor::GroupNames() {
  StringVector groupnames;
  for ( Group * g: Groups() ) {
    groupnames.push_back(std::string(g->Name()));
    Debug(1,"Groups: %s", g->Name());
  }
  return groupnames;
} // end Monitor::GroupNames()
