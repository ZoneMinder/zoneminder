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

#include "zm.h"
#include "zm_db.h"
#include "zm_time.h"
#include "zm_mpeg.h"
#include "zm_signal.h"
#include "zm_monitor.h"
#include "zm_video.h"
#if ZM_HAS_V4L
#include "zm_local_camera.h"
#endif // ZM_HAS_V4L
#include "zm_remote_camera.h"
#include "zm_remote_camera_http.h"
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

std::vector<std::string> split(const std::string &s, char delim) {
  std::vector<std::string> elems;
  std::stringstream ss(s);
  std::string item;
  while(std::getline(ss, item, delim)) {
    elems.push_back(trimSpaces(item));
  }
  return elems;
}

Monitor::MonitorLink::MonitorLink( int p_id, const char *p_name ) : id( p_id ) {
  strncpy( name, p_name, sizeof(name) );

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
    return( true );
  } else if ( shared_data->last_event != (unsigned int)last_event ) {
    last_event = shared_data->last_event;
  }
  return( false );
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
  int p_frame_skip,
  int p_motion_frame_skip,
  double p_analysis_fps,
  unsigned int p_analysis_update_delay,
  int p_capture_delay,
  int p_alarm_capture_delay,
  int p_fps_report_interval,
  int p_ref_blend_perc,
  int p_alarm_ref_blend_perc,
  bool p_track_motion,
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
  savejpegspref( p_savejpegs ),
  videowriter( p_videowriter ),
  encoderparams( p_encoderparams ),
  record_audio( p_record_audio ),
  label_coord( p_label_coord ),
  label_size( p_label_size ),
  image_buffer_count( p_image_buffer_count ),
  warmup_count( p_warmup_count ),
  pre_event_count( p_pre_event_count ),
  post_event_count( p_post_event_count ),
  stream_replay_buffer( p_stream_replay_buffer ),
  section_length( p_section_length ),
  frame_skip( p_frame_skip ),
  motion_frame_skip( p_motion_frame_skip ),
  analysis_fps( p_analysis_fps ),
  analysis_update_delay( p_analysis_update_delay ),
  capture_delay( p_capture_delay ),
  alarm_capture_delay( p_alarm_capture_delay ),
  alarm_frame_count( p_alarm_frame_count ),
  fps_report_interval( p_fps_report_interval ),
  ref_blend_perc( p_ref_blend_perc ),
  alarm_ref_blend_perc( p_alarm_ref_blend_perc ),
  track_motion( p_track_motion ),
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
  privacy_bitmask( NULL )
{
  strncpy( name, p_name, sizeof(name)-1 );

  strncpy( event_prefix, p_event_prefix, sizeof(event_prefix)-1 );
  strncpy( label_format, p_label_format, sizeof(label_format)-1 );

  // Change \n to actual line feeds
  char *token_ptr = label_format;
  const char *token_string = "\n";
  while( ( token_ptr = strstr( token_ptr, token_string ) ) ) {
    if ( *(token_ptr+1) ) {
      *token_ptr = '\n';
      token_ptr++;
      strcpy( token_ptr, token_ptr+1 );
    } else {
      *token_ptr = '\0';
      break;
    }
  }

  /* Parse encoder parameters */
  ParseEncoderParameters(encoderparams.c_str(), &encoderparamsvec);

  fps = 0.0;
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

  if ( strcmp( config.event_close_mode, "time" ) == 0 )
    event_close_mode = CLOSE_TIME;
  else if ( strcmp( config.event_close_mode, "alarm" ) == 0 )
    event_close_mode = CLOSE_ALARM;
  else
    event_close_mode = CLOSE_IDLE;

  Debug( 1, "monitor purpose=%d", purpose );

  mem_size = sizeof(SharedData)
       + sizeof(TriggerData)
       + sizeof(VideoStoreData) //Information to pass back to the capture process
       + (image_buffer_count*sizeof(struct timeval))
       + (image_buffer_count*camera->ImageSize())
       + 64; /* Padding used to permit aligning the images buffer to 64 byte boundary */

  Debug( 1, "mem.size=%d", mem_size );
  mem_ptr = NULL;

  storage = new Storage( storage_id );
  Debug(1, "Storage path: %s", storage->Path() );
  // Should maybe store this for later use
  char monitor_dir[PATH_MAX] = "";
  snprintf( monitor_dir, sizeof(monitor_dir), "%s/%d", storage->Path(), id );
  struct stat statbuf;

  if ( stat( monitor_dir, &statbuf ) ) {
    if ( errno == ENOENT || errno == ENOTDIR ) {
      if ( mkdir( monitor_dir, 0755 ) ) {
        Error( "Can't mkdir %s: %s", monitor_dir, strerror(errno));
      }
    } else {
      Warning( "Error stat'ing %s, may be fatal. error is %s", monitor_dir, strerror(errno));
    }
  }

  if ( purpose == CAPTURE ) {

    this->connect();
    if ( ! mem_ptr ) exit(-1);
    memset( mem_ptr, 0, mem_size );
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
    this->connect();
    if ( ! mem_ptr ) exit(-1);
    shared_data->state = IDLE;
    shared_data->last_read_time = 0;
    shared_data->alarm_x = -1;
    shared_data->alarm_y = -1;
  }

  if ( ( ! mem_ptr ) || ! shared_data->valid ) {
    if ( purpose != QUERY ) {
      Error( "Shared data not initialised by capture daemon for monitor %s", name );
      exit( -1 );
    }
  }

  // Will this not happen every time a monitor is instantiated?  Seems like all the calls to the Monitor constructor pass a zero for n_zones, then load zones after..
  // In my storage areas branch, I took this out.. and didn't notice any problems.
  if ( false && !n_zones ) {
    Debug( 1, "Monitor %s has no zones, adding one.", name );
    n_zones = 1;
    zones = new Zone *[1];
    Coord coords[4] = { Coord( 0, 0 ), Coord( width-1, 0 ), Coord( width-1, height-1 ), Coord( 0, height-1 ) };
    zones[0] = new Zone( this, 0, "All", Zone::ACTIVE, Polygon( sizeof(coords)/sizeof(*coords), coords ), RGB_RED, Zone::BLOBS );
  }
  start_time = last_fps_time = time( 0 );

  event = 0;

  Debug( 1, "Monitor %s has function %d", name, function );
  Debug( 1, "Monitor %s LBF = '%s', LBX = %d, LBY = %d, LBS = %d", name, label_format, label_coord.X(), label_coord.Y(), label_size );
  Debug( 1, "Monitor %s IBC = %d, WUC = %d, pEC = %d, PEC = %d, EAF = %d, FRI = %d, RBP = %d, ARBP = %d, FM = %d", name, image_buffer_count, warmup_count, pre_event_count, post_event_count, alarm_frame_count, fps_report_interval, ref_blend_perc, alarm_ref_blend_perc, track_motion );

  //Set video recording flag for event start constructor and easy reference in code
  videoRecording = ((GetOptVideoWriter() == H264PASSTHROUGH) && camera->SupportsNativeVideo());

  if ( purpose == ANALYSIS ) {

    while( shared_data->last_write_index == (unsigned int)image_buffer_count 
         && shared_data->last_write_time == 0) {
      Warning( "Waiting for capture daemon" );
      sleep( 1 );
    }
    ref_image.Assign( width, height, camera->Colours(), camera->SubpixelOrder(), image_buffer[shared_data->last_write_index].image->Buffer(), camera->ImageSize());

    n_linked_monitors = 0;
    linked_monitors = 0;

    adaptive_skip = true;

    ReloadLinkedMonitors( p_linked_monitors );
  }
}

bool Monitor::connect() {
  Debug(3, "Connecting to monitor.  Purpose is %d", purpose ); 
#if ZM_MEM_MAPPED
  snprintf( mem_file, sizeof(mem_file), "%s/zm.mmap.%d", staticConfig.PATH_MAP.c_str(), id );
  map_fd = open( mem_file, O_RDWR|O_CREAT, (mode_t)0600 );
  if ( map_fd < 0 )
    Fatal( "Can't open memory map file %s, probably not enough space free: %s", mem_file, strerror(errno) );

  struct stat map_stat;
  if ( fstat( map_fd, &map_stat ) < 0 )
    Fatal( "Can't stat memory map file %s: %s, is the zmc process for this monitor running?", mem_file, strerror(errno) );
  if ( map_stat.st_size != mem_size && purpose == CAPTURE ) {
    // Allocate the size
    if ( ftruncate( map_fd, mem_size ) < 0 ) {
      Fatal( "Can't extend memory map file %s to %d bytes: %s", mem_file, mem_size, strerror(errno) );
    }
  } else if ( map_stat.st_size == 0 ) {
    Error( "Got empty memory map file size %ld, is the zmc process for this monitor running?", map_stat.st_size, mem_size );
    return false;
  } else if ( map_stat.st_size != mem_size ) {
    Error( "Got unexpected memory map file size %ld, expected %d", map_stat.st_size, mem_size );
    return false;
  } else {
#ifdef MAP_LOCKED
    mem_ptr = (unsigned char *)mmap( NULL, mem_size, PROT_READ|PROT_WRITE, MAP_SHARED|MAP_LOCKED, map_fd, 0 );
    if ( mem_ptr == MAP_FAILED ) {
      if ( errno == EAGAIN ) {
        Debug( 1, "Unable to map file %s (%d bytes) to locked memory, trying unlocked", mem_file, mem_size );
#endif
        mem_ptr = (unsigned char *)mmap( NULL, mem_size, PROT_READ|PROT_WRITE, MAP_SHARED, map_fd, 0 );
        Debug( 1, "Mapped file %s (%d bytes) to locked memory, unlocked", mem_file, mem_size );
#ifdef MAP_LOCKED
      }
    }
#endif
    if ( mem_ptr == MAP_FAILED )
      Fatal( "Can't map file %s (%d bytes) to memory: %s(%d)", mem_file, mem_size, strerror(errno), errno );
  }
#else // ZM_MEM_MAPPED
  shm_id = shmget( (config.shm_key&0xffff0000)|id, mem_size, IPC_CREAT|0700 );
  if ( shm_id < 0 ) {
    Error( "Can't shmget, probably not enough shared memory space free: %s", strerror(errno));
    exit( -1 );
  }
  mem_ptr = (unsigned char *)shmat( shm_id, 0, 0 );
  if ( mem_ptr < (void *)0 ) {
    Error( "Can't shmat: %s", strerror(errno));
    exit( -1 );
  }
#endif // ZM_MEM_MAPPED
  shared_data = (SharedData *)mem_ptr;
  trigger_data = (TriggerData *)((char *)shared_data + sizeof(SharedData));
  video_store_data = (VideoStoreData *)((char *)trigger_data + sizeof(TriggerData));
  struct timeval *shared_timestamps = (struct timeval *)((char *)video_store_data + sizeof(VideoStoreData));
  unsigned char *shared_images = (unsigned char *)((char *)shared_timestamps + (image_buffer_count*sizeof(struct timeval)));
  
  if(((unsigned long)shared_images % 64) != 0) {
    /* Align images buffer to nearest 64 byte boundary */
    Debug(3,"Aligning shared memory images to the next 64 byte boundary");
    shared_images = (uint8_t*)((unsigned long)shared_images + (64 - ((unsigned long)shared_images % 64)));
  }
  Debug(3, "Allocating %d image buffers", image_buffer_count );
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

  if ( ( purpose == ANALYSIS ) && analysis_fps ) {
    // Size of pre event buffer must be greater than pre_event_count
    // if alarm_frame_count > 1, because in this case the buffer contains
    // alarmed images that must be discarded when event is created
    pre_event_buffer_count = pre_event_count + alarm_frame_count - 1;
    pre_event_buffer = new Snapshot[pre_event_buffer_count];
    for ( int i = 0; i < pre_event_buffer_count; i++ ) {
      pre_event_buffer[i].timestamp = new struct timeval;
      pre_event_buffer[i].image = new Image( width, height, camera->Colours(), camera->SubpixelOrder());
    }
  }

  return true;
}

Monitor::~Monitor() {
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
      Info( "%s: %03d - Closing event %d, shutting down", name, image_count, event->Id() );
      closeEvent();
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
    if ( msync( mem_ptr, mem_size, MS_SYNC ) < 0 )
      Error( "Can't msync: %s", strerror(errno) );
    if ( munmap( mem_ptr, mem_size ) < 0 )
      Fatal( "Can't munmap: %s", strerror(errno) );
    close( map_fd );

    if ( purpose == CAPTURE ) {
      // How about we store this in the object on instantiation so that we don't have to do this again.
      char mmap_path[PATH_MAX] = "";
      snprintf( mmap_path, sizeof(mmap_path), "%s/zm.mmap.%d", staticConfig.PATH_MAP.c_str(), id );

      if ( unlink( mmap_path ) < 0 ) {
        Warning( "Can't unlink '%s': %s", mmap_path, strerror(errno) );
      }
    }
#else // ZM_MEM_MAPPED
    struct shmid_ds shm_data;
    if ( shmctl( shm_id, IPC_STAT, &shm_data ) < 0 ) {
      Error( "Can't shmctl: %s", strerror(errno) );
      exit( -1 );
    }
    if ( shm_data.shm_nattch <= 1 ) {
      if ( shmctl( shm_id, IPC_RMID, 0 ) < 0 ) {
        Error( "Can't shmctl: %s", strerror(errno) );
        exit( -1 );
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
  if ( privacy_bitmask )
    delete[] privacy_bitmask;
  privacy_bitmask = NULL;
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
  return( (State)shared_data->state );
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
    image->WriteJpeg( filename );
  } else {
    Error( "Unable to generate image, no images in buffer" );
  }
  return( 0 );
}

struct timeval Monitor::GetTimestamp( int index ) const {
  if ( index < 0 || index > image_buffer_count ) {
    index = shared_data->last_write_index;
  }

  if ( index != image_buffer_count ) {
    Snapshot *snap = &image_buffer[index];

    return( *(snap->timestamp) );
  } else {
    static struct timeval null_tv = { 0, 0 };

    return( null_tv );
  }
}

unsigned int Monitor::GetLastReadIndex() const {
  return( shared_data->last_read_index!=(unsigned int)image_buffer_count?shared_data->last_read_index:-1 );
}

unsigned int Monitor::GetLastWriteIndex() const {
  return( shared_data->last_write_index!=(unsigned int)image_buffer_count?shared_data->last_write_index:-1 );
}

unsigned int Monitor::GetLastEvent() const {
  return( shared_data->last_event );
}

double Monitor::GetFPS() const {
  int index1 = shared_data->last_write_index;
  if ( index1 == image_buffer_count ) {
    return( 0.0 );
  }
  Snapshot *snap1 = &image_buffer[index1];
  if ( !snap1->timestamp || !snap1->timestamp->tv_sec ) {
    return( 0.0 );
  }
  struct timeval time1 = *snap1->timestamp;

  int image_count = image_buffer_count;
  int index2 = (index1+1)%image_buffer_count;
  if ( index2 == image_buffer_count ) {
    return( 0.0 );
  }
  Snapshot *snap2 = &image_buffer[index2];
  while ( !snap2->timestamp || !snap2->timestamp->tv_sec ) {
    if ( index1 == index2 ) {
      return( 0.0 );
    }
    index2 = (index2+1)%image_buffer_count;
    snap2 = &image_buffer[index2];
    image_count--;
  }
  struct timeval time2 = *snap2->timestamp;

  double time_diff = tvDiffSec( time2, time1 );

  double curr_fps = image_count/time_diff;

  if ( curr_fps < 0.0 ) {
    //Error( "Negative FPS %f, time_diff = %lf (%d:%ld.%ld - %d:%ld.%ld), ibc: %d", curr_fps, time_diff, index2, time2.tv_sec, time2.tv_usec, index1, time1.tv_sec, time1.tv_usec, image_buffer_count );
    return( 0.0 );
  }
  return( curr_fps );
}

useconds_t Monitor::GetAnalysisRate() {
  double capturing_fps = GetFPS();
  if ( !analysis_fps ) {
    return( 0 );
  } else if ( analysis_fps > capturing_fps ) {
    Warning( "Analysis fps (%.2f) is greater than capturing fps (%.2f)", analysis_fps, capturing_fps );
    return( 0 );
  } else {
    return( ( 1000000 / analysis_fps ) - ( 1000000 / capturing_fps ) );
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
  strncpy( trigger_data->trigger_cause, force_cause, sizeof(trigger_data->trigger_cause) );
  strncpy( trigger_data->trigger_text, force_text, sizeof(trigger_data->trigger_text) );
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

  static char sql[ZM_SQL_SML_BUFSIZ];
  snprintf( sql, sizeof(sql), "update Monitors set Enabled = 1 where Id = '%d'", id );
  if ( mysql_query( &dbconn, sql ) ) {
    Error( "Can't run query: %s", mysql_error( &dbconn ) );
    exit( mysql_errno( &dbconn ) );
  }
}

void Monitor::actionDisable() {
  shared_data->action |= RELOAD;

  static char sql[ZM_SQL_SML_BUFSIZ];
  snprintf( sql, sizeof(sql), "update Monitors set Enabled = 0 where Id = '%d'", id );
  if ( mysql_query( &dbconn, sql ) ) {
    Error( "Can't run query: %s", mysql_error( &dbconn ) );
    exit( mysql_errno( &dbconn ) );
  }
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
    if ( !Zone::ParseZoneString( zone_string, exclude_id, extra_colour, extra_zone ) ) {
      Error( "Failed to parse zone string, ignoring" );
    }
  }

  Image *zone_image = NULL;
  if ( ( (!staticConfig.SERVER_ID) || ( staticConfig.SERVER_ID == server_id ) ) && mem_ptr ) {
    Debug(3, "Trying to load from local zmc");
    int index = shared_data->last_write_index;
    Snapshot *snap = &image_buffer[index];
    zone_image = new Image( *snap->image );
  } else {
    Debug(3, "Trying to load from event");
    // Grab the most revent event image
    std::string sql = stringtf( "SELECT MAX(Id) FROM Events WHERE MonitorId=%d AND Frames > 0", id );
    zmDbRow eventid_row;
    if ( eventid_row.fetch( sql.c_str() ) ) {
      int event_id = atoi( eventid_row[0] );

      Debug( 3, "Got event %d", event_id );
      EventStream *stream = new EventStream();
      stream->setStreamStart( event_id, (unsigned int)1 );
      zone_image = stream->getImage();
    } else {
      Error("Unable to load an event for monitor %d", id );
      return;
    }
  }

  if(zone_image->Colours() == ZM_COLOUR_GRAY8) {
    zone_image->Colourise(ZM_COLOUR_RGB24, ZM_SUBPIX_ORDER_RGB );
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
    dump_image->WriteJpeg( new_filename );
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

  if ( config.signal_check_points > 0 ) {
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
    for ( int i = 0; i < config.signal_check_points; i++ ) {
      while( true ) {
        index = (int)(((long long)rand()*(long long)(pixels-1))/RAND_MAX);
        if ( !config.timestamp_on_capture || !label_format[0] )
          break;
        // Avoid sampling the rows with timestamp in
        if ( index < (label_coord.Y()*width) || index >= (label_coord.Y()+Image::LINE_HEIGHT)*width )
          break;
      }
      
      if(colours == ZM_COLOUR_GRAY8) {
        if ( *(buffer+index) != grayscale_val )
          return true;

      } else if(colours == ZM_COLOUR_RGB24) {
        const uint8_t *ptr = buffer+(index*colours);

        if ( usedsubpixorder == ZM_SUBPIX_ORDER_BGR) {
          if ( (RED_PTR_BGRA(ptr) != red_val) || (GREEN_PTR_BGRA(ptr) != green_val) || (BLUE_PTR_BGRA(ptr) != blue_val) )
            return true;
        } else {
          /* Assume RGB */
          if ( (RED_PTR_RGBA(ptr) != red_val) || (GREEN_PTR_RGBA(ptr) != green_val) || (BLUE_PTR_RGBA(ptr) != blue_val) )
            return true;
        }

      } else if(colours == ZM_COLOUR_RGB32) {
        if ( usedsubpixorder == ZM_SUBPIX_ORDER_ARGB || usedsubpixorder == ZM_SUBPIX_ORDER_ABGR) {
          if ( ARGB_ABGR_ZEROALPHA(*(((const Rgb*)buffer)+index)) != ARGB_ABGR_ZEROALPHA(colour_val) )
            return true;
        } else {
          /* Assume RGBA or BGRA */
          if ( RGBA_BGRA_ZEROALPHA(*(((const Rgb*)buffer)+index)) != RGBA_BGRA_ZEROALPHA(colour_val) )
            return true;
        }
      }
    }
    return( false );
  }
  return( true );
}

bool Monitor::Analyse() {
  if ( shared_data->last_read_index == shared_data->last_write_index ) {
    // I wonder how often this happens. Maybe if this happens we should sleep or something?
    return( false );
  }

  struct timeval now;
  gettimeofday( &now, NULL );

  if ( image_count && fps_report_interval && !(image_count%fps_report_interval) ) {
    fps = double(fps_report_interval)/(now.tv_sec-last_fps_time);
    Info( "%s: %d - Analysing at %.2f fps", name, image_count, fps );
    last_fps_time = now.tv_sec;
  }

  int index;
  if ( adaptive_skip ) {
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

    Debug( 4, "RI:%d, WI: %d, PF = %d, RM = %d, Step = %d", shared_data->last_read_index, shared_data->last_write_index, pending_frames, read_margin, step );
    if ( step <= pending_frames ) {
      index = (shared_data->last_read_index+step)%image_buffer_count;
    } else {
      if ( pending_frames ) {
        Warning( "Approaching buffer overrun, consider slowing capture, simplifying analysis or increasing ring buffer size" );
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
      Info( "Received reload indication at count %d", image_count );
      shared_data->action &= ~RELOAD;
      Reload();
    }
    if ( shared_data->action & SUSPEND ) {
      if ( Active() ) {
        Info( "Received suspend indication at count %d", image_count );
        shared_data->active = false;
        //closeEvent();
      } else {
        Info( "Received suspend indication at count %d, but wasn't active", image_count );
      }
      if ( config.max_suspend_time ) {
        auto_resume_time = now.tv_sec + config.max_suspend_time;
      }
      shared_data->action &= ~SUSPEND;
    }
    if ( shared_data->action & RESUME ) {
      if ( Enabled() && !Active() ) {
        Info( "Received resume indication at count %d", image_count );
        shared_data->active = true;
        ref_image = *snap_image;
        ready_count = image_count+(warmup_count/2);
        shared_data->alarm_x = shared_data->alarm_y = -1;
      }
      shared_data->action &= ~RESUME;
    }
  } // end if shared_data->action

  if ( auto_resume_time && (now.tv_sec >= auto_resume_time) ) {
    Info( "Auto resuming at count %d", image_count );
    shared_data->active = true;
    ref_image = *snap_image;
    ready_count = image_count+(warmup_count/2);
    auto_resume_time = 0;
  }

  static bool static_undef = true;
  static int last_section_mod = 0;
  static bool last_signal;

  if ( static_undef ) {
// Sure would be nice to be able to assume that these were already initialized.  It's just 1 compare/branch, but really not neccessary.
    static_undef = false;
    timestamps = new struct timeval *[pre_event_count];
    images = new Image *[pre_event_count];
    last_signal = shared_data->signal;
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
          if ( !event ) {
            if ( cause.length() )
              cause += ", ";
            cause += trigger_data->trigger_cause;
          }
          Event::StringSet noteSet;
          noteSet.insert( trigger_data->trigger_text );
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
          Warning( "%s: %s", SIGNAL_CAUSE, signalText );
          if ( event && !signal ) {
            Info( "%s: %03d - Closing event %d, signal loss", name, image_count, event->Id() );
            closeEvent();
            last_section_mod = 0;
          }
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

        } else if ( signal && Active() && (function == MODECT || function == MOCORD) ) {
          Event::StringSet zoneSet;
          int motion_score = last_motion_score;
          if ( !(image_count % (motion_frame_skip+1) ) ) {
            // Get new score.
            motion_score = DetectMotion( *snap_image, zoneSet );

            Debug( 3, "After motion detection, last_motion_score(%d), new motion score(%d)", last_motion_score, motion_score );
            // Why are we updating the last_motion_score too?
            last_motion_score = motion_score;
          }
          //int motion_score = DetectBlack( *snap_image, zoneSet );
          if ( motion_score ) {
            if ( !event ) {
              score += motion_score;
              if ( cause.length() )
                cause += ", ";
              cause += MOTION_CAUSE;
            } else {
              score += motion_score;
            }
            noteSetMap[MOTION_CAUSE] = zoneSet;

          }
          shared_data->active = signal;
        }
        if ( (!signal_change && signal) && n_linked_monitors > 0 ) {
          bool first_link = true;
          Event::StringSet noteSet;
          for ( int i = 0; i < n_linked_monitors; i++ ) {
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
                noteSet.insert( linked_monitors[i]->Name() );
                score += 50;
              }
            } else {
              linked_monitors[i]->connect();
            }
          }
          if ( noteSet.size() > 0 )
            noteSetMap[LINKED_CAUSE] = noteSet;
        }
        
        //TODO: What happens is the event closes and sets recording to false then recording to true again so quickly that our capture daemon never picks it up. Maybe need a refresh flag?
        if ( (!signal_change && signal) && (function == RECORD || function == MOCORD) ) {
          if ( event ) {
            //TODO: We shouldn't have to do this every time. Not sure why it clears itself if this isn't here??
            snprintf(video_store_data->event_file, sizeof(video_store_data->event_file), "%s", event->getEventFile());
              Debug( 3, "Detected new event at (%d.%d)", timestamp->tv_sec,timestamp->tv_usec );
            
            if ( section_length ) {
              int section_mod = timestamp->tv_sec%section_length;
              Debug( 3, "Section length (%d) Last Section Mod(%d), new section mod(%d)", section_length, last_section_mod, section_mod );
              if ( section_mod < last_section_mod ) {
                //if ( state == IDLE || state == TAPE || event_close_mode == CLOSE_TIME ) {
                  //if ( state == TAPE ) {
                    //shared_data->state = state = IDLE;
                    //Info( "%s: %03d - Closing event %d, section end", name, image_count, event->Id() )
                  //} else {
                    Info( "%s: %03d - Closing event %d, section end forced ", name, image_count, event->Id() );
                  //}
                  closeEvent();
                  last_section_mod = 0;
                //} else {
                  //Debug( 2, "Time to close event, but state (%d) is not IDLE or TAPE and event_close_mode is not CLOSE_TIME (%d)", state, event_close_mode );
                //}
              } else {
                last_section_mod = section_mod;
              }
            }
          } // end if section_length

          if ( ! event ) {

            // Create event
            event = new Event( this, *timestamp, "Continuous", noteSetMap, videoRecording );
            shared_data->last_event = event->Id();
            //set up video store data
            snprintf(video_store_data->event_file, sizeof(video_store_data->event_file), "%s", event->getEventFile());
            video_store_data->recording = event->StartTime();

            Info( "%s: %03d - Opening new event %d, section start", name, image_count, event->Id() );

            /* To prevent cancelling out an existing alert\prealarm\alarm state */
            if ( state == IDLE ) {
              shared_data->state = state = TAPE;
            }

            //if ( config.overlap_timed_events )
            if ( false ) {
              int pre_index;
              int pre_event_images = pre_event_count;

              if ( analysis_fps ) {
                // If analysis fps is set,
                // compute the index for pre event images in the dedicated buffer
                pre_index = image_count%pre_event_buffer_count;

                // Seek forward the next filled slot in to the buffer (oldest data)
                // from the current position
                while ( pre_event_images && !pre_event_buffer[pre_index].timestamp->tv_sec ) {
                  pre_index = (pre_index + 1)%pre_event_buffer_count;
                  // Slot is empty, removing image from counter
                  pre_event_images--;
                }
              } else {
                // If analysis fps is not set (analysis performed at capturing framerate),
                // compute the index for pre event images in the capturing buffer
                pre_index = ((index + image_buffer_count) - pre_event_count)%image_buffer_count;

                // Seek forward the next filled slot in to the buffer (oldest data)
                // from the current position
                while ( pre_event_images && !image_buffer[pre_index].timestamp->tv_sec ) {
                  pre_index = (pre_index + 1)%image_buffer_count;
                  // Slot is empty, removing image from counter
                  pre_event_images--;
                }
              }

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

                event->AddFrames( pre_event_images, images, timestamps );
              }
            } // end if false or config.overlap_timed_events
          } // end if ! event
        }
        if ( score ) {
          if ( (state == IDLE || state == TAPE || state == PREALARM ) ) {
            if ( Event::PreAlarmCount() >= (alarm_frame_count-1) ) {
              Info( "%s: %03d - Gone into alarm state", name, image_count );
              shared_data->state = state = ALARM;
              if ( signal_change || (function != MOCORD && state != ALERT) ) {
                int pre_index;
                int pre_event_images = pre_event_count;

                if ( analysis_fps ) {
                  // If analysis fps is set,
                  // compute the index for pre event images in the dedicated buffer
                  pre_index = image_count%pre_event_buffer_count;

                  // Seek forward the next filled slot in to the buffer (oldest data)
                  // from the current position
                  while ( pre_event_images && !pre_event_buffer[pre_index].timestamp->tv_sec ) {
                    pre_index = (pre_index + 1)%pre_event_buffer_count;
                    // Slot is empty, removing image from counter
                    pre_event_images--;
                  }

                  event = new Event( this, *(pre_event_buffer[pre_index].timestamp), cause, noteSetMap );
                } else {
                  // If analysis fps is not set (analysis performed at capturing framerate),
                  // compute the index for pre event images in the capturing buffer
                  if ( alarm_frame_count > 1 )
                    pre_index = ((index + image_buffer_count) - ((alarm_frame_count - 1) + pre_event_count))%image_buffer_count;
                  else
                    pre_index = ((index + image_buffer_count) - pre_event_count)%image_buffer_count;

                  // Seek forward the next filled slot in to the buffer (oldest data)
                  // from the current position
                  while ( pre_event_images && !image_buffer[pre_index].timestamp->tv_sec ) {
                    pre_index = (pre_index + 1)%image_buffer_count;
                    // Slot is empty, removing image from counter
                    pre_event_images--;
                  }

                  event = new Event( this, *(image_buffer[pre_index].timestamp), cause, noteSetMap );
                }
                shared_data->last_event = event->Id();
                //set up video store data
                snprintf(video_store_data->event_file, sizeof(video_store_data->event_file), "%s", event->getEventFile());
                video_store_data->recording = event->StartTime();

                Info( "%s: %03d - Opening new event %d, alarm start", name, image_count, event->Id() );

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

                  event->AddFrames( pre_event_images, images, timestamps );
                }
                if ( alarm_frame_count ) {
                  event->SavePreAlarmFrames();
                }
              }
            } else if ( state != PREALARM ) {
              Info( "%s: %03d - Gone into prealarm state", name, image_count );
              shared_data->state = state = PREALARM;
            }
          } else if ( state == ALERT ) {
            Info( "%s: %03d - Gone back into alarm state", name, image_count );
            shared_data->state = state = ALARM;
          }
          last_alarm_count = image_count;
        } else {
          if ( state == ALARM ) {
            Info( "%s: %03d - Gone into alert state", name, image_count );
            shared_data->state = state = ALERT;
          } else if ( state == ALERT ) {
            if ( image_count-last_alarm_count > post_event_count ) {
              Info( "%s: %03d - Left alarm state (%d) - %d(%d) images", name, image_count, event->Id(), event->Frames(), event->AlarmFrames() );
              //if ( function != MOCORD || event_close_mode == CLOSE_ALARM || event->Cause() == SIGNAL_CAUSE )
              if ( function != MOCORD || event_close_mode == CLOSE_ALARM ) {
                shared_data->state = state = IDLE;
                Info( "%s: %03d - Closing event %d, alarm end%s", name, image_count, event->Id(), (function==MOCORD)?", section truncated":"" );
                closeEvent();
              } else {
                shared_data->state = state = TAPE;
              }
            }
          }
          if ( state == PREALARM ) {
            if ( function != MOCORD ) {
              shared_data->state = state = IDLE;
            } else {
              shared_data->state = state = TAPE;
            }
          }
          if ( Event::PreAlarmCount() )
            Event::EmptyPreAlarmFrames();
        }
        if ( state != IDLE ) {
          if ( state == PREALARM || state == ALARM ) {
            if ( config.create_analysis_images ) {
              bool got_anal_image = false;
              alarm_image.Assign( *snap_image );
              for( int i = 0; i < n_zones; i++ ) {
                if ( zones[i]->Alarmed() ) {
                  if ( zones[i]->AlarmImage() ) {
                    alarm_image.Overlay( *(zones[i]->AlarmImage()) );
                    got_anal_image = true;
                  }
                  if ( config.record_event_stats && state == ALARM ) {
                    zones[i]->RecordStats( event );
                  }
                }
              }
              if ( got_anal_image ) {
                if ( state == PREALARM )
                  Event::AddPreAlarmFrame( snap_image, *timestamp, score, &alarm_image );
                else
                  event->AddFrame( snap_image, *timestamp, score, &alarm_image );
              } else {
                if ( state == PREALARM )
                  Event::AddPreAlarmFrame( snap_image, *timestamp, score );
                else
                  event->AddFrame( snap_image, *timestamp, score );
              }
            } else {
              for( int i = 0; i < n_zones; i++ ) {
                if ( zones[i]->Alarmed() ) {
                  if ( config.record_event_stats && state == ALARM ) {
                    zones[i]->RecordStats( event );
                  }
                }
              }
              if ( state == PREALARM )
                Event::AddPreAlarmFrame( snap_image, *timestamp, score );
              else
                event->AddFrame( snap_image, *timestamp, score );
            }
            if ( event && noteSetMap.size() > 0 )
              event->updateNotes( noteSetMap );
          } else if ( state == ALERT ) {
            event->AddFrame( snap_image, *timestamp );
            if ( noteSetMap.size() > 0 )
              event->updateNotes( noteSetMap );
          } else if ( state == TAPE ) {
            //Video Storage: activate only for supported cameras. Event::AddFrame knows whether or not we are recording video and saves frames accordingly
            //if((GetOptVideoWriter() == 2) && camera->SupportsNativeVideo()) {
              // I don't think this is required, and causes problems, as the event file hasn't been setup yet.
              //Warning("In state TAPE, 
              //video_store_data->recording = event->StartTime();
            //}
            if ( !(image_count%(frame_skip+1)) ) {
              if ( config.bulk_frame_interval > 1 ) {
                event->AddFrame( snap_image, *timestamp, (event->Frames()<pre_event_count?0:-1) );
              } else {
                event->AddFrame( snap_image, *timestamp );
              }
            }
          }
        } // end if ! IDLE
      }
    } else {
      if ( event ) {
        Info( "%s: %03d - Closing event %d, trigger off", name, image_count, event->Id() );
        closeEvent();
      }
      shared_data->state = state = IDLE;
      last_section_mod = 0;
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

  if ( analysis_fps ) {
    // If analysis fps is set, add analysed image to dedicated pre event buffer
    int pre_index = image_count%pre_event_buffer_count;
    pre_event_buffer[pre_index].image->Assign(*snap->image);
    memcpy( pre_event_buffer[pre_index].timestamp, snap->timestamp, sizeof(struct timeval) );
  }

  image_count++;

  return( true );
}

void Monitor::Reload() {
  Debug( 1, "Reloading monitor %s", name );

  if ( event )
    Info( "%s: %03d - Closing event %d, reloading", name, image_count, event->Id() );

  closeEvent();

  static char sql[ZM_SQL_MED_BUFSIZ];
  // This seems to have fallen out of date.
  snprintf( sql, sizeof(sql), "select Function+0, Enabled, LinkedMonitors, EventPrefix, LabelFormat, LabelX, LabelY, LabelSize, WarmupCount, PreEventCount, PostEventCount, AlarmFrameCount, SectionLength, FrameSkip, MotionFrameSkip, AnalysisFPS, AnalysisUpdateDelay, MaxFPS, AlarmMaxFPS, FPSReportInterval, RefBlendPerc, AlarmRefBlendPerc, TrackMotion, SignalCheckColour from Monitors where Id = '%d'", id );

  if ( mysql_query( &dbconn, sql ) ) {
    Error( "Can't run query: %s", mysql_error( &dbconn ) );
    exit( mysql_errno( &dbconn ) );
  }

  MYSQL_RES *result = mysql_store_result( &dbconn );
  if ( !result ) {
    Error( "Can't use query result: %s", mysql_error( &dbconn ) );
    exit( mysql_errno( &dbconn ) );
  }
  int n_monitors = mysql_num_rows( result );
  if ( n_monitors != 1 ) {
    Error( "Bogus number of monitors, %d, returned. Can't reload", n_monitors ); 
    return;
  }

  if ( MYSQL_ROW dbrow = mysql_fetch_row( result ) ) {
    int index = 0;
    function = (Function)atoi(dbrow[index++]);
    enabled = atoi(dbrow[index++]);
    const char *p_linked_monitors = dbrow[index++];
    strncpy( event_prefix, dbrow[index++], sizeof(event_prefix) );
    strncpy( label_format, dbrow[index++], sizeof(label_format) );
    label_coord = Coord( atoi(dbrow[index]), atoi(dbrow[index+1]) ); index += 2;
    label_size = atoi(dbrow[index++]);
    warmup_count = atoi(dbrow[index++]);
    pre_event_count = atoi(dbrow[index++]);
    post_event_count = atoi(dbrow[index++]);
    alarm_frame_count = atoi(dbrow[index++]);
    section_length = atoi(dbrow[index++]);
    frame_skip = atoi(dbrow[index++]);
    motion_frame_skip = atoi(dbrow[index++]);
    analysis_fps = dbrow[index] ? strtod(dbrow[index], NULL) : 0; index++;
    analysis_update_delay = strtoul(dbrow[index++], NULL, 0);
    capture_delay = (dbrow[index]&&atof(dbrow[index])>0.0)?int(DT_PREC_3/atof(dbrow[index])):0; index++;
    alarm_capture_delay = (dbrow[index]&&atof(dbrow[index])>0.0)?int(DT_PREC_3/atof(dbrow[index])):0; index++;
    fps_report_interval = atoi(dbrow[index++]);
    ref_blend_perc = atoi(dbrow[index++]);
    alarm_ref_blend_perc = atoi(dbrow[index++]);
    track_motion = atoi(dbrow[index++]);
    

    if ( dbrow[index][0] == '#' )
      signal_check_colour = strtol(dbrow[index]+1,0,16);
    else
      signal_check_colour = strtol(dbrow[index],0,16);
    index++;

    shared_data->state = state = IDLE;
    shared_data->alarm_x = shared_data->alarm_y = -1;
    if ( enabled )
      shared_data->active = true;
    ready_count = image_count+warmup_count;

    ReloadLinkedMonitors( p_linked_monitors );
  }
  if ( mysql_errno( &dbconn ) ) {
    Error( "Can't fetch row: %s", mysql_error( &dbconn ) );
    exit( mysql_errno( &dbconn ) );
  }
  mysql_free_result( result );

  ReloadZones();
}

void Monitor::ReloadZones() {
  Debug( 1, "Reloading zones for monitor %s", name );
  for( int i = 0; i < n_zones; i++ ) {
    delete zones[i];
  }
  delete[] zones;
  zones = 0;
  n_zones = Zone::Load( this, zones );
  //DumpZoneImage();
}

void Monitor::ReloadLinkedMonitors( const char *p_linked_monitors ) {
  Debug( 1, "Reloading linked monitors for monitor %s, '%s'", name, p_linked_monitors );
  if ( n_linked_monitors ) {
    for( int i = 0; i < n_linked_monitors; i++ ) {
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
        if ( link_id > 0 && link_id != id) {
          Debug( 3, "Found linked monitor id %d", link_id );
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
      Debug( 1, "Linking to %d monitors", n_link_ids );
      linked_monitors = new MonitorLink *[n_link_ids];
      int count = 0;
      for ( int i = 0; i < n_link_ids; i++ ) {
        Debug( 1, "Checking linked monitor %d", link_ids[i] );

        static char sql[ZM_SQL_SML_BUFSIZ];
        snprintf( sql, sizeof(sql), "select Id, Name from Monitors where Id = %d and Function != 'None' and Function != 'Monitor' and Enabled = 1", link_ids[i] );
        if ( mysql_query( &dbconn, sql ) ) {
          Error( "Can't run query: %s", mysql_error( &dbconn ) );
          exit( mysql_errno( &dbconn ) );
        }

        MYSQL_RES *result = mysql_store_result( &dbconn );
        if ( !result ) {
          Error( "Can't use query result: %s", mysql_error( &dbconn ) );
          exit( mysql_errno( &dbconn ) );
        }
        int n_monitors = mysql_num_rows( result );
        if ( n_monitors == 1 ) {
          MYSQL_ROW dbrow = mysql_fetch_row( result );
          Debug( 1, "Linking to monitor %d", link_ids[i] );
          linked_monitors[count++] = new MonitorLink( link_ids[i], dbrow[1] );
        } else {
          Warning( "Can't link to monitor %d, invalid id, function or not enabled", link_ids[i] );
        }
        mysql_free_result( result );
      }
      n_linked_monitors = count;
    }
  }
}

#if ZM_HAS_V4L
int Monitor::LoadLocalMonitors( const char *device, Monitor **&monitors, Purpose purpose ) {
  std::string sql = "select Id, Name, ServerId, StorageId, Function+0, Enabled, LinkedMonitors, Device, Channel, Format, V4LMultiBuffer, V4LCapturesPerFrame, Method, Width, Height, Colours, Palette, Orientation+0, Deinterlacing, SaveJPEGs, VideoWriter, EncoderParameters, RecordAudio, Brightness, Contrast, Hue, Colour, EventPrefix, LabelFormat, LabelX, LabelY, LabelSize, ImageBufferCount, WarmupCount, PreEventCount, PostEventCount, StreamReplayBuffer, AlarmFrameCount, SectionLength, FrameSkip, MotionFrameSkip, AnalysisFPS, AnalysisUpdateDelay, MaxFPS, AlarmMaxFPS, FPSReportInterval, RefBlendPerc, AlarmRefBlendPerc, TrackMotion, SignalCheckColour, Exif from Monitors where Function != 'None' and Type = 'Local'";
;
  if ( device[0] ) {
    sql += " AND Device='";
    sql += device;
    sql += "'";
  }
  if ( staticConfig.SERVER_ID ) {
    sql += stringtf( " AND ServerId=%d", staticConfig.SERVER_ID );
  }
  Debug( 1, "Loading Local Monitors with %s", sql.c_str() );

  MYSQL_RES *result = zmDbFetch( sql.c_str() );
  if ( !result ) {
    Error( "Can't load local monitors: %s", mysql_error( &dbconn ) );
    exit( mysql_errno( &dbconn ) );
  }
  int n_monitors = mysql_num_rows( result );
  Debug( 1, "Got %d monitors", n_monitors );
  delete[] monitors;
  monitors = new Monitor *[n_monitors];
  for( int i = 0; MYSQL_ROW dbrow = mysql_fetch_row( result ); i++ ) {
    int col = 0;

    int id = atoi(dbrow[col]); col++;
    const char *name = dbrow[col]; col++;
    unsigned int server_id = dbrow[col] ? atoi(dbrow[col]) : 0; col++;
    unsigned int storage_id = atoi(dbrow[col]); col++;
    int function = atoi(dbrow[col]); col++;
    int enabled = atoi(dbrow[col]); col++;
    const char *linked_monitors = dbrow[col]; col++;

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
    Debug( 1, "Got %d for v4l_captures_per_frame", v4l_captures_per_frame );
    col++;
    const char *method = dbrow[col]; col++;

    int width = atoi(dbrow[col]); col++;
    int height = atoi(dbrow[col]); col++;
    int colours = atoi(dbrow[col]); col++;
    int palette = atoi(dbrow[col]); col++;
    Orientation orientation = (Orientation)atoi(dbrow[col]); col++;
    unsigned int deinterlacing = atoi(dbrow[col]); col++;

    int savejpegs = atoi(dbrow[col]); col++;
    VideoWriter videowriter = (VideoWriter)atoi(dbrow[col]); col++;
    std::string encoderparams = dbrow[col] ? dbrow[col] : ""; col++;
    bool record_audio = (*dbrow[col] != '0'); col++;

    int brightness = atoi(dbrow[col]); col++;
    int contrast = atoi(dbrow[col]); col++;
    int hue = atoi(dbrow[col]); col++;
    int colour = atoi(dbrow[col]); col++;

    const char *event_prefix = dbrow[col] ? dbrow[col] : ""; col++;
    const char *label_format = dbrow[col] ? dbrow[col] : ""; col++;

    int label_x = atoi(dbrow[col]); col++;
    int label_y = atoi(dbrow[col]); col++;
    int label_size = atoi(dbrow[col]); col++;

    int image_buffer_count = atoi(dbrow[col]); col++;
    int warmup_count = atoi(dbrow[col]); col++;
    int pre_event_count = atoi(dbrow[col]); col++;
    int post_event_count = atoi(dbrow[col]); col++;
    int stream_replay_buffer = atoi(dbrow[col]); col++;
    int alarm_frame_count = atoi(dbrow[col]); col++;
    int section_length = atoi(dbrow[col]); col++;
    int frame_skip = atoi(dbrow[col]); col++;
    int motion_frame_skip = atoi(dbrow[col]); col++;
    double analysis_fps = dbrow[col] ? strtod(dbrow[col], NULL) : 0; col++;
    unsigned int analysis_update_delay = strtoul(dbrow[col++], NULL, 0);
    int capture_delay = (dbrow[col]&&atof(dbrow[col])>0.0)?int(DT_PREC_3/atof(dbrow[col])):0; col++;
    int alarm_capture_delay = (dbrow[col]&&atof(dbrow[col])>0.0)?int(DT_PREC_3/atof(dbrow[col])):0; col++;
    int fps_report_interval = atoi(dbrow[col]); col++;
    int ref_blend_perc = atoi(dbrow[col]); col++;
    int alarm_ref_blend_perc = atoi(dbrow[col]); col++;
    int track_motion = atoi(dbrow[col]); col++;

    int signal_check_colour;
    if ( dbrow[col][0] == '#' )
      signal_check_colour = strtol(dbrow[col]+1,0,16);
    else
      signal_check_colour = strtol(dbrow[col],0,16);
    col++;
    bool embed_exif = (*dbrow[col] != '0'); col++;

    int extras = (deinterlacing>>24)&0xff;

    Camera *camera = new LocalCamera(
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

    monitors[i] = new Monitor(
      id,
      name,
      server_id,
      storage_id,
      function,
      enabled,
      linked_monitors,
      camera,
      orientation,
      deinterlacing,
      savejpegs,
      videowriter,
      encoderparams,
      record_audio,
      event_prefix,
      label_format,
      Coord( label_x, label_y ),
      label_size,
      image_buffer_count,
      warmup_count,
      pre_event_count,
      post_event_count,
      stream_replay_buffer,
      alarm_frame_count,
      section_length,
      frame_skip,
      motion_frame_skip,
      analysis_fps,
      analysis_update_delay,
      capture_delay,
      alarm_capture_delay,
      fps_report_interval,
      ref_blend_perc,
      alarm_ref_blend_perc,
      track_motion,
      signal_check_colour,
      embed_exif,
      purpose,
      0,
      0
    );
    camera->setMonitor( monitors[i] );
    Zone **zones = 0;
    int n_zones = Zone::Load( monitors[i], zones );
    monitors[i]->AddZones( n_zones, zones );
    monitors[i]->AddPrivacyBitmask( zones );
    Debug( 1, "Loaded monitor %d(%s), %d zones", id, name, n_zones );
  }
  if ( mysql_errno( &dbconn ) ) {
    Error( "Can't fetch row: %s", mysql_error( &dbconn ) );
    exit( mysql_errno( &dbconn ) );
  }
  // Yadda yadda
  mysql_free_result( result );

  return( n_monitors );
}
#endif // ZM_HAS_V4L

int Monitor::LoadRemoteMonitors( const char *protocol, const char *host, const char *port, const char *path, Monitor **&monitors, Purpose purpose ) {
  std::string sql = "select Id, Name, ServerId, StorageId, Function+0, Enabled, LinkedMonitors, Protocol, Method, Host, Port, Path, Width, Height, Colours, Palette, Orientation+0, Deinterlacing, RTSPDescribe, SaveJPEGs, VideoWriter, EncoderParameters, RecordAudio, Brightness, Contrast, Hue, Colour, EventPrefix, LabelFormat, LabelX, LabelY, LabelSize, ImageBufferCount, WarmupCount, PreEventCount, PostEventCount, StreamReplayBuffer, AlarmFrameCount, SectionLength, FrameSkip, MotionFrameSkip, AnalysisFPS, AnalysisUpdateDelay, MaxFPS, AlarmMaxFPS, FPSReportInterval, RefBlendPerc, AlarmRefBlendPerc, TrackMotion, Exif from Monitors where Function != 'None' and Type = 'Remote'";
  if ( staticConfig.SERVER_ID ) {
    sql += stringtf( " AND ServerId=%d", staticConfig.SERVER_ID );
  }

  if ( protocol ) {
    sql += stringtf(" AND Protocol = '%s' and Host = '%s' and Port = '%s' and Path = '%s'", protocol, host, port, path );
  }

  Debug( 1, "Loading Remote Monitors with %s", sql.c_str() );
  MYSQL_RES *result = zmDbFetch( sql.c_str() );
  if ( !result ) {
    Error( "Can't use query result: %s", mysql_error( &dbconn ) );
    exit( mysql_errno( &dbconn ) );
  }
  int n_monitors = mysql_num_rows( result );
  Debug( 1, "Got %d monitors", n_monitors );
  delete[] monitors;
  monitors = new Monitor *[n_monitors];
  for( int i = 0; MYSQL_ROW dbrow = mysql_fetch_row( result ); i++ ) {
    int col = 0;

    int id = atoi(dbrow[col]); col++;
    std::string name = dbrow[col]; col++;
    unsigned int server_id = dbrow[col] ? atoi(dbrow[col]) : 0; col++;
    unsigned int storage_id = atoi(dbrow[col]); col++;
    int function = atoi(dbrow[col]); col++;
    int enabled = atoi(dbrow[col]); col++;
    const char *linked_monitors = dbrow[col]; col++;

    std::string protocol = dbrow[col] ? dbrow[col] : ""; col++;
    std::string method = dbrow[col] ? dbrow[col] : ""; col++;
    std::string host = dbrow[col] ? dbrow[col] : ""; col++;
    std::string port = dbrow[col] ? dbrow[col] : ""; col++;
    std::string path = dbrow[col] ? dbrow[col] : ""; col++;

    int width = atoi(dbrow[col]); col++;
    int height = atoi(dbrow[col]); col++;
    int colours = atoi(dbrow[col]); col++;
    /* int palette = atoi(dbrow[col]); */ col++;
    Orientation orientation = (Orientation)atoi(dbrow[col]); col++;
    unsigned int deinterlacing = atoi(dbrow[col]); col++;
    bool rtsp_describe = (dbrow[col] && *dbrow[col] != '0'); col++;
    int savejpegs = atoi(dbrow[col]); col++;
    VideoWriter videowriter = (VideoWriter)atoi(dbrow[col]); col++;
    std::string encoderparams = dbrow[col] ? dbrow[col] : ""; col++;
    bool record_audio = (*dbrow[col] != '0'); col++;

    int brightness = atoi(dbrow[col]); col++;
    int contrast = atoi(dbrow[col]); col++;
    int hue = atoi(dbrow[col]); col++;
    int colour = atoi(dbrow[col]); col++;

    const char *event_prefix = dbrow[col] ? dbrow[col] : ""; col++;
    const char *label_format = dbrow[col] ? dbrow[col] : ""; col++;

    int label_x = atoi(dbrow[col]); col++;
    int label_y = atoi(dbrow[col]); col++;
    int label_size = atoi(dbrow[col]); col++;

    int image_buffer_count = atoi(dbrow[col]); col++;
    int warmup_count = atoi(dbrow[col]); col++;
    int pre_event_count = atoi(dbrow[col]); col++;
    int post_event_count = atoi(dbrow[col]); col++;
    int stream_replay_buffer = atoi(dbrow[col]); col++;
    int alarm_frame_count = atoi(dbrow[col]); col++;
    int section_length = atoi(dbrow[col]); col++;
    int frame_skip = atoi(dbrow[col]); col++;
    int motion_frame_skip = atoi(dbrow[col]); col++;
    double analysis_fps = dbrow[col] ? strtod(dbrow[col], NULL) : 0; col++;
    unsigned int analysis_update_delay = strtoul(dbrow[col++], NULL, 0);
    int capture_delay = (dbrow[col]&&atof(dbrow[col])>0.0)?int(DT_PREC_3/atof(dbrow[col])):0; col++;
    int alarm_capture_delay = (dbrow[col]&&atof(dbrow[col])>0.0)?int(DT_PREC_3/atof(dbrow[col])):0; col++;
    int fps_report_interval = atoi(dbrow[col]); col++;
    int ref_blend_perc = atoi(dbrow[col]); col++;
    int alarm_ref_blend_perc = atoi(dbrow[col]); col++;
    int track_motion = atoi(dbrow[col]); col++;
    bool embed_exif = (*dbrow[col] != '0'); col++;

    Camera *camera = 0;
    if ( protocol == "http" ) {
      camera = new RemoteCameraHttp(
        id,
        method,
        host, // Host
        port, // Port
        path, // Path
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
      Fatal( "Unexpected remote camera protocol '%s'", protocol.c_str() );
    }

    monitors[i] = new Monitor(
      id,
      name.c_str(),
      server_id,
      storage_id,
      function,
      enabled,
      linked_monitors,
      camera,
      orientation,
      deinterlacing,
      savejpegs,
      videowriter,
      encoderparams,
      record_audio,
      event_prefix,
      label_format,
      Coord( label_x, label_y ),
      label_size,
      image_buffer_count,
      warmup_count,
      pre_event_count,
      post_event_count,
      stream_replay_buffer,
      alarm_frame_count,
      section_length,
      frame_skip,
      motion_frame_skip,
      analysis_fps,
      analysis_update_delay,
      capture_delay,
      alarm_capture_delay,
      fps_report_interval,
      ref_blend_perc,
      alarm_ref_blend_perc,
      track_motion,
      RGB_WHITE,
      embed_exif,
                              purpose,
      0,
      0
    );
    camera->setMonitor( monitors[i] );
    Zone **zones = 0;
    int n_zones = Zone::Load( monitors[i], zones );
    monitors[i]->AddZones( n_zones, zones );
    monitors[i]->AddPrivacyBitmask( zones );
    Debug( 1, "Loaded monitor %d(%s), %d zones", id, name.c_str(), n_zones );
  }
  if ( mysql_errno( &dbconn ) ) {
    Error( "Can't fetch row: %s", mysql_error( &dbconn ) );
    exit( mysql_errno( &dbconn ) );
  }
  // Yadda yadda
  mysql_free_result( result );

  return( n_monitors );
}

int Monitor::LoadFileMonitors( const char *file, Monitor **&monitors, Purpose purpose ) {
  std::string sql = "select Id, Name, ServerId, StorageId, Function+0, Enabled, LinkedMonitors, Path, Width, Height, Colours, Palette, Orientation+0, Deinterlacing, SaveJPEGs, VideoWriter, EncoderParameters, RecordAudio, Brightness, Contrast, Hue, Colour, EventPrefix, LabelFormat, LabelX, LabelY, LabelSize, ImageBufferCount, WarmupCount, PreEventCount, PostEventCount, StreamReplayBuffer, AlarmFrameCount, SectionLength, FrameSkip, MotionFrameSkip, AnalysisFPS, AnalysisUpdateDelay, MaxFPS, AlarmMaxFPS, FPSReportInterval, RefBlendPerc, AlarmRefBlendPerc, TrackMotion, Exif from Monitors where Function != 'None' and Type = 'File'";
  if ( file[0] ) {
    sql += " AND Path='";
    sql += file;
    sql += "'";
  }
  if ( staticConfig.SERVER_ID ) {
    sql += stringtf( " AND ServerId=%d", staticConfig.SERVER_ID );
  }
  Debug( 1, "Loading File Monitors with %s", sql.c_str() );
  MYSQL_RES *result = zmDbFetch( sql.c_str() );
  if ( !result ) {
    Error( "Can't use query result: %s", mysql_error( &dbconn ) );
    exit( mysql_errno( &dbconn ) );
  }
  int n_monitors = mysql_num_rows( result );
  Debug( 1, "Got %d monitors", n_monitors );
  delete[] monitors;
  monitors = new Monitor *[n_monitors];
  for( int i = 0; MYSQL_ROW dbrow = mysql_fetch_row( result ); i++ ) {
    int col = 0;

    int id = atoi(dbrow[col]); col++;
    const char *name = dbrow[col]; col++;
    unsigned int server_id = dbrow[col] ? atoi(dbrow[col]) : 0; col++;
    unsigned int storage_id = atoi(dbrow[col]); col++;
    int function = atoi(dbrow[col]); col++;
    int enabled = atoi(dbrow[col]); col++;
    const char *linked_monitors = dbrow[col]; col++;

    const char *path = dbrow[col]; col++;

    int width = atoi(dbrow[col]); col++;
    int height = atoi(dbrow[col]); col++;
    int colours = atoi(dbrow[col]); col++;
    /* int palette = atoi(dbrow[col]); */ col++;
    Orientation orientation = (Orientation)atoi(dbrow[col]); col++;
    unsigned int deinterlacing = atoi(dbrow[col]); col++;

    int savejpegs = atoi(dbrow[col]); col++;
    VideoWriter videowriter = (VideoWriter)atoi(dbrow[col]); col++;
    std::string encoderparams =  dbrow[col]; col++;
    bool record_audio = (*dbrow[col] != '0'); col++;

    int brightness = atoi(dbrow[col]); col++;
    int contrast = atoi(dbrow[col]); col++;
    int hue = atoi(dbrow[col]); col++;
    int colour = atoi(dbrow[col]); col++;

    const char *event_prefix = dbrow[col] ? dbrow[col] : ""; col++;
    const char *label_format = dbrow[col] ? dbrow[col] : ""; col++;
 
    int label_x = atoi(dbrow[col]); col++;
    int label_y = atoi(dbrow[col]); col++;
    int label_size = atoi(dbrow[col]); col++;

    int image_buffer_count = atoi(dbrow[col]); col++;
    int warmup_count = atoi(dbrow[col]); col++;
    int pre_event_count = atoi(dbrow[col]); col++;
    int post_event_count = atoi(dbrow[col]); col++;
    int stream_replay_buffer = atoi(dbrow[col]); col++;
    int alarm_frame_count = atoi(dbrow[col]); col++;
    int section_length = atoi(dbrow[col]); col++;
    int frame_skip = atoi(dbrow[col]); col++;
    int motion_frame_skip = atoi(dbrow[col]); col++;
    double analysis_fps = dbrow[col] ? strtod(dbrow[col], NULL) : 0; col++;
    unsigned int analysis_update_delay = strtoul(dbrow[col++], NULL, 0);
    int capture_delay = (dbrow[col]&&atof(dbrow[col])>0.0)?int(DT_PREC_3/atof(dbrow[col])):0; col++;
    int alarm_capture_delay = (dbrow[col]&&atof(dbrow[col])>0.0)?int(DT_PREC_3/atof(dbrow[col])):0; col++;
    int fps_report_interval = atoi(dbrow[col]); col++;
    int ref_blend_perc = atoi(dbrow[col]); col++;
    int alarm_ref_blend_perc = atoi(dbrow[col]); col++;
    int track_motion = atoi(dbrow[col]); col++;
    bool embed_exif = (*dbrow[col] != '0'); col++;

    Camera *camera = new FileCamera(
      id,
      path, // File
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

    monitors[i] = new Monitor(
      id,
      name,
      server_id,
      storage_id,
      function,
      enabled,
      linked_monitors,
      camera,
      orientation,
      deinterlacing,
      savejpegs,
      videowriter,
      encoderparams,
      record_audio,
      event_prefix,
      label_format,
      Coord( label_x, label_y ),
      label_size,
      image_buffer_count,
      warmup_count,
      pre_event_count,
      post_event_count,
      stream_replay_buffer,
      alarm_frame_count,
      section_length,
      frame_skip,
      motion_frame_skip,
      analysis_fps,
      analysis_update_delay,
      capture_delay,
      alarm_capture_delay,
      fps_report_interval,
      ref_blend_perc,
      alarm_ref_blend_perc,
      track_motion,
      embed_exif,
      RGB_WHITE,
      purpose,
      0,
      0
    );
    camera->setMonitor( monitors[i] );
    Zone **zones = 0;
    int n_zones = Zone::Load( monitors[i], zones );
    monitors[i]->AddZones( n_zones, zones );
    monitors[i]->AddPrivacyBitmask( zones );
    Debug( 1, "Loaded monitor %d(%s), %d zones", id, name, n_zones );
  }
  if ( mysql_errno( &dbconn ) ) {
    Error( "Can't fetch row: %s", mysql_error( &dbconn ) );
    exit( mysql_errno( &dbconn ) );
  }
  // Yadda yadda
  mysql_free_result( result );

  return( n_monitors );
}

#if HAVE_LIBAVFORMAT
int Monitor::LoadFfmpegMonitors( const char *file, Monitor **&monitors, Purpose purpose ) {
    std::string sql = "select Id, Name, ServerId, StorageId, Function+0, Enabled, LinkedMonitors, Path, Method, Options, Width, Height, Colours, Palette, Orientation+0, Deinterlacing, SaveJPEGs, VideoWriter, EncoderParameters, RecordAudio, Brightness, Contrast, Hue, Colour, EventPrefix, LabelFormat, LabelX, LabelY, LabelSize, ImageBufferCount, WarmupCount, PreEventCount, PostEventCount, StreamReplayBuffer, AlarmFrameCount, SectionLength, FrameSkip, MotionFrameSkip, AnalysisFPS, AnalysisUpdateDelay, MaxFPS, AlarmMaxFPS, FPSReportInterval, RefBlendPerc, AlarmRefBlendPerc, TrackMotion, Exif from Monitors where Function != 'None' and Type = 'Ffmpeg'";
  if ( file[0] ) {
    sql += " AND Path = '";
    sql += file;
    sql += "'";
  }
  if ( staticConfig.SERVER_ID ) {
    sql += stringtf( " AND ServerId=%d", staticConfig.SERVER_ID );
  }
  Debug( 1, "Loading FFMPEG Monitors with %s", sql.c_str() );
  MYSQL_RES *result = zmDbFetch( sql.c_str() );
  if ( ! result ) {
    Error( "Cannot load FfmpegMonitors" );
    exit( mysql_errno( &dbconn ) );
  }

  int n_monitors = mysql_num_rows( result );
  Debug( 1, "Got %d monitors", n_monitors );
  delete[] monitors;
  monitors = new Monitor *[n_monitors];
  for( int i = 0; MYSQL_ROW dbrow = mysql_fetch_row( result ); i++ ) {
    int col = 0;

    int id = atoi(dbrow[col]); col++;
    const char *name = dbrow[col]; col++;
    unsigned int server_id = dbrow[col] ? atoi(dbrow[col]) : 0; col++;
    unsigned int storage_id = atoi(dbrow[col]); col++;
    int function = atoi(dbrow[col]); col++;
    int enabled = atoi(dbrow[col]); col++;
    const char *linked_monitors = dbrow[col] ? dbrow[col] : ""; col++;

    const char *path = dbrow[col]; col++;
    const char *method = dbrow[col]; col++;
    const char *options = dbrow[col] ? dbrow[col] : ""; col++;

    int width = atoi(dbrow[col]); col++;
    int height = atoi(dbrow[col]); col++;
    int colours = atoi(dbrow[col]); col++;
    /* int palette = atoi(dbrow[col]); */ col++;
    Orientation orientation = (Orientation)atoi(dbrow[col]); col++;
    unsigned int deinterlacing = atoi(dbrow[col]); col++;

    int savejpegs = atoi(dbrow[col]); col++;
    VideoWriter videowriter = (VideoWriter)atoi(dbrow[col]); col++;
    std::string encoderparams =  dbrow[col] ? dbrow[col] : ""; col++;
    bool record_audio = (*dbrow[col] != '0'); col++;

    int brightness = atoi(dbrow[col]); col++;
    int contrast = atoi(dbrow[col]); col++;
    int hue = atoi(dbrow[col]); col++;
    int colour = atoi(dbrow[col]); col++;

    const char *event_prefix = dbrow[col] ? dbrow[col] : ""; col++;
    const char *label_format = dbrow[col] ? dbrow[col] : ""; col++;

    int label_x = atoi(dbrow[col]); col++;
    int label_y = atoi(dbrow[col]); col++;
    int label_size = atoi(dbrow[col]); col++;

    int image_buffer_count = atoi(dbrow[col]); col++;
    int warmup_count = atoi(dbrow[col]); col++;
    int pre_event_count = atoi(dbrow[col]); col++;
    int post_event_count = atoi(dbrow[col]); col++;
    int stream_replay_buffer = atoi(dbrow[col]); col++;
    int alarm_frame_count = atoi(dbrow[col]); col++;
    int section_length = atoi(dbrow[col]); col++;
    int frame_skip = atoi(dbrow[col]); col++;
    int motion_frame_skip = atoi(dbrow[col]); col++;

    double analysis_fps = dbrow[col] ? strtod(dbrow[col], NULL) : 0; col++;
    unsigned int analysis_update_delay = strtoul(dbrow[col++], NULL, 0);
    double capture_fps = dbrow[col] ? atof(dbrow[col]) : 0;col++;
    int capture_delay = capture_fps >0.0 ?int(DT_PREC_3/capture_fps):0; 
    double alarm_capture_fps = dbrow[col] ? atof(dbrow[col]) : 0; col++;
    int alarm_capture_delay = alarm_capture_fps > 0.0 ?int(DT_PREC_3/alarm_capture_fps):0;

    int fps_report_interval = atoi(dbrow[col]); col++;
    int ref_blend_perc = atoi(dbrow[col]); col++;
    int alarm_ref_blend_perc = atoi(dbrow[col]); col++;
    int track_motion = atoi(dbrow[col]); col++;
    bool embed_exif = (*dbrow[col] != '0'); col++;

    Camera *camera = new FfmpegCamera(
      id,
      path, // File
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

    monitors[i] = new Monitor(
      id,
      name,
      server_id,
      storage_id,
      function,
      enabled,
      linked_monitors,
      camera,
      orientation,
      deinterlacing,
      savejpegs,
      videowriter,
      encoderparams,
      record_audio,
      event_prefix,
      label_format,
      Coord( label_x, label_y ),
      label_size,
      image_buffer_count,
      warmup_count,
      pre_event_count,
      post_event_count,
      stream_replay_buffer,
      alarm_frame_count,
      section_length,
      frame_skip,
      motion_frame_skip,
      analysis_fps,
      analysis_update_delay,
      capture_delay,
      alarm_capture_delay,
      fps_report_interval,
      ref_blend_perc,
      alarm_ref_blend_perc,
      track_motion,
      embed_exif,
      RGB_WHITE,
      purpose,
      0,
      0
    );

    camera->setMonitor( monitors[i] );
    Zone **zones = 0;
    int n_zones = Zone::Load( monitors[i], zones );
    monitors[i]->AddZones( n_zones, zones );
    monitors[i]->AddPrivacyBitmask( zones );
    Debug( 1, "Loaded monitor %d(%s), %d zones", id, name, n_zones );
  }
  if ( mysql_errno( &dbconn ) ) {
    Error( "Can't fetch row: %s", mysql_error( &dbconn ) );
    exit( mysql_errno( &dbconn ) );
  }
  // Yadda yadda
  mysql_free_result( result );

  return( n_monitors );
}
#endif // HAVE_LIBAVFORMAT

Monitor *Monitor::Load( unsigned int p_id, bool load_zones, Purpose purpose ) {
  std::string sql = stringtf( "select Id, Name, ServerId, StorageId, Type, Function+0, Enabled, LinkedMonitors, Device, Channel, Format, V4LMultiBuffer, V4LCapturesPerFrame, Protocol, Method, Host, Port, Path, Options, User, Pass, Width, Height, Colours, Palette, Orientation+0, Deinterlacing, RTSPDescribe, SaveJPEGs, VideoWriter, EncoderParameters, RecordAudio, Brightness, Contrast, Hue, Colour, EventPrefix, LabelFormat, LabelX, LabelY, LabelSize, ImageBufferCount, WarmupCount, PreEventCount, PostEventCount, StreamReplayBuffer, AlarmFrameCount, SectionLength, FrameSkip, MotionFrameSkip, AnalysisFPS, AnalysisUpdateDelay, MaxFPS, AlarmMaxFPS, FPSReportInterval, RefBlendPerc, AlarmRefBlendPerc, TrackMotion, SignalCheckColour, Exif from Monitors where Id = %d", p_id );

  zmDbRow dbrow;
  if ( ! dbrow.fetch( sql.c_str() ) ) {
    Error( "Can't use query result: %s", mysql_error( &dbconn ) );
    exit( mysql_errno( &dbconn ) );
  }
  Monitor *monitor = 0;
  unsigned int col = 0;

  unsigned int id = atoi(dbrow[col]); col++;
  std::string name = dbrow[col]; col++;
  unsigned int server_id = dbrow[col] ? atoi(dbrow[col]) : 0; col++;
  unsigned int storage_id = atoi(dbrow[col]); col++;
  std::string type = dbrow[col]; col++;
  int function = atoi(dbrow[col]); col++;
  int enabled = atoi(dbrow[col]); col++;
  std::string linked_monitors = dbrow[col] ? dbrow[col] : ""; col++;

  std::string device = dbrow[col]; col++;
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
  Debug( 1, "Got %d for v4l_captures_per_frame", v4l_captures_per_frame );
  col++;

  std::string protocol = dbrow[col] ? dbrow[col] : ""; col++;
  std::string method = dbrow[col] ? dbrow[col] : ""; col++;
  std::string host = dbrow[col] ? dbrow[col] : ""; col++;
  std::string port = dbrow[col] ? dbrow[col] : ""; col++;
  std::string path = dbrow[col] ? dbrow[col] : ""; col++;
  std::string options = dbrow[col] ? dbrow[col] : ""; col++;
  std::string user = dbrow[col] ? dbrow[col] : ""; col++;
  std::string pass = dbrow[col] ? dbrow[col] : ""; col++;

  int width = atoi(dbrow[col]); col++;
  int height = atoi(dbrow[col]); col++;
  int colours = atoi(dbrow[col]); col++;
  int palette = atoi(dbrow[col]); col++;
  Orientation orientation = (Orientation)atoi(dbrow[col]); col++;
  unsigned int deinterlacing = atoi(dbrow[col]); col++;
  bool rtsp_describe = (dbrow[col] && *dbrow[col] != '0'); col++;
  int savejpegs = atoi(dbrow[col]); col++;
  VideoWriter videowriter = (VideoWriter)atoi(dbrow[col]); col++;
  std::string encoderparams =  dbrow[col] ? dbrow[col] : ""; col++;
  bool record_audio = (*dbrow[col] != '0'); col++;

  int brightness = atoi(dbrow[col]); col++;
  int contrast = atoi(dbrow[col]); col++;
  int hue = atoi(dbrow[col]); col++;
  int colour = atoi(dbrow[col]); col++;

  const char * event_prefix = dbrow[col] ? dbrow[col] : ""; col++;
  const char * label_format = dbrow[col] ? dbrow[col] : ""; col++;

  int label_x = atoi(dbrow[col]); col++;
  int label_y = atoi(dbrow[col]); col++;
  int label_size = atoi(dbrow[col]); col++;

  int image_buffer_count = atoi(dbrow[col]); col++;
  int warmup_count = atoi(dbrow[col]); col++;
  int pre_event_count = atoi(dbrow[col]); col++;
  int post_event_count = atoi(dbrow[col]); col++;
  int stream_replay_buffer = atoi(dbrow[col]); col++;
  int alarm_frame_count = atoi(dbrow[col]); col++;
  int section_length = atoi(dbrow[col]); col++;
  int frame_skip = atoi(dbrow[col]); col++;
  int motion_frame_skip = atoi(dbrow[col]); col++;
  double analysis_fps = dbrow[col] ? strtod(dbrow[col], NULL) : 0; col++;
  unsigned int analysis_update_delay = strtoul(dbrow[col++], NULL, 0);
  int capture_delay = (dbrow[col]&&atof(dbrow[col])>0.0)?int(DT_PREC_3/atof(dbrow[col])):0; col++;
  int alarm_capture_delay = (dbrow[col]&&atof(dbrow[col])>0.0)?int(DT_PREC_3/atof(dbrow[col])):0; col++;
  int fps_report_interval = atoi(dbrow[col]); col++;
  int ref_blend_perc = atoi(dbrow[col]); col++;
  int alarm_ref_blend_perc = atoi(dbrow[col]); col++;
  int track_motion = atoi(dbrow[col]); col++;

  int signal_check_colour;
  if ( dbrow[col][0] == '#' )
    signal_check_colour = strtol(dbrow[col]+1,0,16);
  else
    signal_check_colour = strtol(dbrow[col],0,16);
  col++;
  bool embed_exif = (*dbrow[col] != '0'); col++;

  int extras = (deinterlacing>>24)&0xff;

  Camera *camera = 0;
  if ( type == "Local" ) {
#if ZM_HAS_V4L
    camera = new LocalCamera(
      id,
      device.c_str(),
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
#else // ZM_HAS_V4L
    Fatal( "You must have video4linux libraries and headers installed to use local analog or USB cameras for monitor %d", id );
#endif // ZM_HAS_V4L
  } else if ( type == "Remote" ) {
    if ( protocol == "http" ) {
      camera = new RemoteCameraHttp(
        id,
        method.c_str(),
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
    } else if ( protocol == "rtsp" ) {
#if HAVE_LIBAVFORMAT
      camera = new RemoteCameraRtsp(
        id,
        method.c_str(),
        host.c_str(),
        port.c_str(),
        path.c_str(),
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
#else // HAVE_LIBAVFORMAT
      Fatal( "You must have ffmpeg libraries installed to use remote camera protocol '%s' for monitor %d", protocol.c_str(), id );
#endif // HAVE_LIBAVFORMAT
    } else {
      Fatal( "Unexpected remote camera protocol '%s' for monitor %d", protocol.c_str(), id );
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
#else // HAVE_LIBAVFORMAT
    Fatal( "You must have ffmpeg libraries installed to use ffmpeg cameras for monitor %d", id );
#endif // HAVE_LIBAVFORMAT
  } else if (type == "Libvlc") {
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
    Fatal( "You must have vlc libraries installed to use vlc cameras for monitor %d", id );
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
    Fatal( "You must have libcurl installed to use ffmpeg cameras for monitor %d", id );
#endif // HAVE_LIBCURL
  } else {
    Fatal( "Bogus monitor type '%s' for monitor %d", type.c_str(), id );
  }
  monitor = new Monitor(
    id,
    name.c_str(),
    server_id,
    storage_id,
    function,
    enabled,
    linked_monitors.c_str(),
    camera,
    orientation,
    deinterlacing,
    savejpegs,
    videowriter,
    encoderparams,
    record_audio,
    event_prefix,
    label_format,
    Coord( label_x, label_y ),
    label_size,
    image_buffer_count,
    warmup_count,
    pre_event_count,
    post_event_count,
    stream_replay_buffer,
    alarm_frame_count,
    section_length,
    frame_skip,
    motion_frame_skip,
    analysis_fps,
    analysis_update_delay,
    capture_delay,
    alarm_capture_delay,
    fps_report_interval,
    ref_blend_perc,
    alarm_ref_blend_perc,
    track_motion,
    signal_check_colour,
    embed_exif,
    purpose,
    0,
    0

  );

  camera->setMonitor( monitor );

  int n_zones = 0;
  if ( load_zones ) {
    Zone **zones = 0;
    n_zones = Zone::Load( monitor, zones );
    monitor->AddZones( n_zones, zones );
    monitor->AddPrivacyBitmask( zones );
  }
  Debug( 1, "Loaded monitor %d(%s), %d zones", id, name.c_str(), n_zones );
  return( monitor );
}

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
    if ( (videowriter == H264PASSTHROUGH ) && camera->SupportsNativeVideo() ) {
      //Warning("ZMC: Recording: %d", video_store_data->recording);
      captureResult = camera->CaptureAndRecord(*capture_image, video_store_data->recording, video_store_data->event_file);
    }else{
      /* Capture directly into image buffer, avoiding the need to memcpy() */
      captureResult = camera->Capture(*capture_image);
    }
  }
  
  // CaptureAndRecord returns # of frames captured I think
  if ( ( videowriter == H264PASSTHROUGH ) && ( captureResult > 0 ) ) {
    //video_store_data->frameNumber = captureResult;
    captureResult = 0;
  }
 
  if ( captureResult != 0 ) {
    // Unable to capture image for temporary reason
    // Fake a signal loss image
    Rgb signalcolor;
    signalcolor = rgb_convert(signal_check_colour, ZM_SUBPIX_ORDER_BGR); /* HTML colour code is actually BGR in memory, we want RGB */
    capture_image->Fill(signalcolor);
    captureResult = 0;
  } else { 
    captureResult = 1;
  }
  
  if ( captureResult == 1 ) {
    
    /* Deinterlacing */
    if ( deinterlacing_value == 1 ) {
      capture_image->Deinterlace_Discard();
    } else if ( deinterlacing_value == 2 ) {
      capture_image->Deinterlace_Linear();
    } else if ( deinterlacing_value == 3 ) {
      capture_image->Deinterlace_Blend();
    } else if ( deinterlacing_value == 4 ) {
      capture_image->Deinterlace_4Field( next_buffer.image, (deinterlacing>>8)&0xff );
    } else if ( deinterlacing_value == 5 ) {
      capture_image->Deinterlace_Blend_CustomRatio( (deinterlacing>>8)&0xff );
    }
    
    if ( orientation != ROTATE_0 ) {
      switch ( orientation ) {
        case ROTATE_0 : {
          // No action required
          break;
        }
        case ROTATE_90 :
        case ROTATE_180 :
        case ROTATE_270 : {
          capture_image->Rotate( (orientation-1)*90 );
          break;
        }
        case FLIP_HORI :
        case FLIP_VERT : {
          capture_image->Flip( orientation==FLIP_HORI );
          break;
        }
      }
    }

    if ( capture_image->Size() > camera->ImageSize() ) {
      Error( "Captured image %d does not match expected size %d check width, height and colour depth",capture_image->Size(),camera->ImageSize() );
      return( -1 );
    }

    if ( (index == shared_data->last_read_index) && (function > MONITOR) ) {
      Warning( "Buffer overrun at index %d, image %d, slow down capture, speed up analysis or increase ring buffer size", index, image_count );
      time_t now = time(0);
      double approxFps = double(image_buffer_count)/double(now-image_buffer[index].timestamp->tv_sec);
      time_t last_read_delta = now - shared_data->last_read_time;
      if ( last_read_delta > (image_buffer_count/approxFps) ) {
        Warning( "Last image read from shared memory %ld seconds ago, zma may have gone away", last_read_delta )
        shared_data->last_read_index = image_buffer_count;
      }
    }

    if ( privacy_bitmask )
      capture_image->MaskPrivacy( privacy_bitmask );

    gettimeofday( image_buffer[index].timestamp, NULL );
    if ( config.timestamp_on_capture ) {
      TimestampImage( capture_image, image_buffer[index].timestamp );
    }
    shared_data->signal = CheckSignal(capture_image);
    shared_data->last_write_index = index;
    shared_data->last_write_time = image_buffer[index].timestamp->tv_sec;

    image_count++;

    if ( image_count && fps_report_interval && !(image_count%fps_report_interval) ) {
      time_t now = image_buffer[index].timestamp->tv_sec;
      fps = double(fps_report_interval)/(now-last_fps_time);
      //Info( "%d -> %d -> %d", fps_report_interval, now, last_fps_time );
      //Info( "%d -> %d -> %lf -> %lf", now-last_fps_time, fps_report_interval/(now-last_fps_time), double(fps_report_interval)/(now-last_fps_time), fps );
      Info( "%s: %d - Capturing at %.2lf fps", name, image_count, fps );
      last_fps_time = now;
    }

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
    return( 0 );
  } // end if captureResults == 1 which is success I think
  shared_data->signal = false;
  return( -1 );
}

void Monitor::TimestampImage( Image *ts_image, const struct timeval *ts_time ) const {
  if ( label_format[0] ) {
    // Expand the strftime macros first
    char label_time_text[256];
    strftime( label_time_text, sizeof(label_time_text), label_format, localtime( &ts_time->tv_sec ) );

    char label_text[1024];
    const char *s_ptr = label_time_text;
    char *d_ptr = label_text;
    while ( *s_ptr && ((d_ptr-label_text) < (unsigned int)sizeof(label_text)) ) {
      if ( *s_ptr == '%' ) {
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
      gettimeofday( &(event->EndTime()), NULL );
    }
    delete event;
    video_store_data->recording = (struct timeval){0};
    event = 0;
    return( true );
  }
  return( false );
}

unsigned int Monitor::DetectMotion( const Image &comp_image, Event::StringSet &zoneSet ) {
  bool alarm = false;
  unsigned int score = 0;

  if ( n_zones <= 0 ) return( alarm );

  Storage *storage = this->getStorage();

  if ( config.record_diag_images ) {
    static char diag_path[PATH_MAX] = "";
    if ( !diag_path[0] ) {
      snprintf( diag_path, sizeof(diag_path), "%s/%d/diag-r.jpg", storage->Path(), id );
    }
    ref_image.WriteJpeg( diag_path );
  }

  ref_image.Delta( comp_image, &delta_image );

  if ( config.record_diag_images ) {
    static char diag_path[PATH_MAX] = "";
    if ( !diag_path[0] ) {
      snprintf( diag_path, sizeof(diag_path), "%s/%d/diag-d.jpg", storage->Path(), id );
    }
    delta_image.WriteJpeg( diag_path );
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
    Debug( 3, "Blanking inactive zone %s", zone->Label() );
    delta_image.Fill( RGB_BLACK, zone->GetPolygon() );
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
        Debug(3, "Preclusive Zone %s alarm Ends. Prevíous score: %d", zone->Label(), old_zone_score);
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

    Info( "Got alarm centre at %d,%d, at count %d", shared_data->alarm_x, shared_data->alarm_y, image_count );
  } else {
    shared_data->alarm_x = shared_data->alarm_y = -1;
  }

  // This is a small and innocent hack to prevent scores of 0 being returned in alarm state
  return( score?score:alarm );
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
  sprintf( output+strlen(output), "Colours : %d\n", camera->Colours() );
  sprintf( output+strlen(output), "Subpixel Order : %d\n", camera->SubpixelOrder() );
  sprintf( output+strlen(output), "Event Prefix : %s\n", event_prefix );
  sprintf( output+strlen(output), "Label Format : %s\n", label_format );
  sprintf( output+strlen(output), "Label Coord : %d,%d\n", label_coord.X(), label_coord.Y() );
  sprintf( output+strlen(output), "Label Size : %d\n", label_size );
  sprintf( output+strlen(output), "Image Buffer Count : %d\n", image_buffer_count );
  sprintf( output+strlen(output), "Warmup Count : %d\n", warmup_count );
  sprintf( output+strlen(output), "Pre Event Count : %d\n", pre_event_count );
  sprintf( output+strlen(output), "Post Event Count : %d\n", post_event_count );
  sprintf( output+strlen(output), "Stream Replay Buffer : %d\n", stream_replay_buffer );
  sprintf( output+strlen(output), "Alarm Frame Count : %d\n", alarm_frame_count );
  sprintf( output+strlen(output), "Section Length : %d\n", section_length );
  sprintf( output+strlen(output), "Maximum FPS : %.2f\n", capture_delay?DT_PREC_3/capture_delay:0.0 );
  sprintf( output+strlen(output), "Alarm Maximum FPS : %.2f\n", alarm_capture_delay?DT_PREC_3/alarm_capture_delay:0.0 );
  sprintf( output+strlen(output), "Reference Blend %%ge : %d\n", ref_blend_perc );
  sprintf( output+strlen(output), "Alarm Reference Blend %%ge : %d\n", alarm_ref_blend_perc );
  sprintf( output+strlen(output), "Track Motion : %d\n", track_motion );
  sprintf( output+strlen(output), "Function: %d - %s\n", function,
    function==NONE?"None":(
    function==MONITOR?"Monitor Only":(
    function==MODECT?"Motion Detection":(
    function==RECORD?"Continuous Record":(
    function==MOCORD?"Continuous Record with Motion Detection":(
    function==NODECT?"Externally Triggered only, no Motion Detection":"Unknown"
  ))))));
  sprintf( output+strlen(output), "Zones : %d\n", n_zones );
  for ( int i = 0; i < n_zones; i++ ) {
    zones[i]->DumpSettings( output+strlen(output), verbose );
  }
  return( true );
} // bool Monitor::DumpSettings( char *output, bool verbose )

unsigned int Monitor::Colours() const { return( camera->Colours() ); }
unsigned int Monitor::SubpixelOrder() const { return( camera->SubpixelOrder() ); }
int Monitor::PrimeCapture() {
  return( camera->PrimeCapture() );
}
int Monitor::PreCapture() {
  return( camera->PreCapture() );
}
int Monitor::PostCapture() {
  return( camera->PostCapture() );
}
Monitor::Orientation Monitor::getOrientation() const { return orientation; }

Monitor::Snapshot *Monitor::getSnapshot() {
  return &image_buffer[ shared_data->last_write_index%image_buffer_count ];
}
