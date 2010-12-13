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
// Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
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
#include "zm_local_camera.h"
#include "zm_remote_camera.h"
#include "zm_remote_camera_http.h"
#if HAVE_LIBAVFORMAT
#include "zm_remote_camera_rtsp.h"
#endif // HAVE_LIBAVFORMAT
#include "zm_file_camera.h"
#if HAVE_LIBAVFORMAT
#include "zm_ffmpeg_camera.h"
#endif // HAVE_LIBAVFORMAT

#if ZM_MEM_MAPPED
#include <sys/mman.h>
#include <fcntl.h>
#else // ZM_MEM_MAPPED
#include <sys/ipc.h>
#include <sys/shm.h>
#endif // ZM_MEM_MAPPED

Monitor::MonitorLink::MonitorLink( int p_id, const char *p_name ) : id( p_id )
{
    strncpy( name, p_name, sizeof(name) );

#if ZM_MEM_MAPPED
    map_fd = -1;
    snprintf( mem_file, sizeof(mem_file), "%s/zm.mmap.%d", config.path_map, id );
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

Monitor::MonitorLink::~MonitorLink()
{
    disconnect();
}

bool Monitor::MonitorLink::connect()
{
    if ( !last_connect_time || (time( 0 ) - last_connect_time) > 60 )
    {
        last_connect_time = time( 0 );

        mem_size = sizeof(SharedData) + sizeof(TriggerData);

        Debug( 1, "link.mem.size=%d", mem_size );
#if ZM_MEM_MAPPED
        map_fd = open( mem_file, O_RDWR, (mode_t)0600 );
        if ( map_fd < 0 )
        {
            Debug( 3, "Can't open linked memory map file %s: %s", mem_file, strerror(errno) );
            disconnect();
            return( false );
        }

        struct stat map_stat;
        if ( fstat( map_fd, &map_stat ) < 0 )
        {
            Error( "Can't stat linked memory map file %s: %s", mem_file, strerror(errno) );
            disconnect();
            return( false );
        }

        if ( map_stat.st_size == 0 )
        {
            Error( "Linked memory map file %s is empty: %s", mem_file, strerror(errno) );
            disconnect();
            return( false );
        }
        else if ( map_stat.st_size < mem_size )
        {
            Error( "Got unexpected memory map file size %ld, expected %d", map_stat.st_size, mem_size );
            disconnect();
            return( false );
        }

        mem_ptr = (unsigned char *)mmap( NULL, mem_size, PROT_READ|PROT_WRITE, MAP_SHARED, map_fd, 0 );
        if ( mem_ptr == MAP_FAILED )
        {
            Error( "Can't map file %s (%d bytes) to memory: %s", mem_file, mem_size, strerror(errno) );
            disconnect();
            return( false );
        }
#else // ZM_MEM_MAPPED
        shm_id = shmget( (config.shm_key&0xffff0000)|id, mem_size, 0700 );
        if ( shm_id < 0 )
        {
            Debug( 3, "Can't shmget link memory: %s", strerror(errno) );
            connected = false;
            return( false );
        }
        mem_ptr = (unsigned char *)shmat( shm_id, 0, 0 );
        if ( mem_ptr < 0 )
        {
            Debug( 3, "Can't shmat link memory: %s", strerror(errno) );
            connected = false;
            return( false );
        }
#endif // ZM_MEM_MAPPED

        shared_data = (SharedData *)mem_ptr;
        trigger_data = (TriggerData *)((char *)shared_data + sizeof(SharedData));

        if ( !shared_data->valid )
        {
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

bool Monitor::MonitorLink::disconnect()
{
    if ( connected )
    {
        connected = false;

#if ZM_MEM_MAPPED
        if ( mem_ptr > 0 )
        {
            msync( mem_ptr, mem_size, MS_ASYNC );
            munmap( mem_ptr, mem_size );
        }
        if ( map_fd >= 0 )
            close( map_fd );

        map_fd = -1;
#else // ZM_MEM_MAPPED
        struct shmid_ds shm_data;
        if ( shmctl( shm_id, IPC_STAT, &shm_data ) < 0 )
        {
            Debug( 3, "Can't shmctl: %s", strerror(errno) );
            return( false );
        }

        shm_id = 0;

        if ( shm_data.shm_nattch <= 1 )
        {
            if ( shmctl( shm_id, IPC_RMID, 0 ) < 0 )
            {
                Debug( 3, "Can't shmctl: %s", strerror(errno) );
                return( false );
            }
        }

        if ( shmdt( mem_ptr ) < 0 )
        {
            Debug( 3, "Can't shmdt: %s", strerror(errno) );
            return( false );
        }

#endif // ZM_MEM_MAPPED
        mem_size = 0;
        mem_ptr = 0;
    }
    return( true );
}

bool Monitor::MonitorLink::isAlarmed()
{
    if ( !connected )
    {
        return( false );
    }
    return( shared_data->state == ALARM );
}

bool Monitor::MonitorLink::inAlarm()
{
    if ( !connected )
    {
        return( false );
    }
    return( shared_data->state == ALARM || shared_data->state == ALERT );
}

bool Monitor::MonitorLink::hasAlarmed()
{
    if ( shared_data->state == ALARM || shared_data->state == ALERT )
    {
        return( true );
    }
    else if( shared_data->last_event != last_event )
    {
        last_event = shared_data->last_event;
        return( true );
    }
    return( false );
}

Monitor::Monitor(
    int p_id,
    const char *p_name,
    int p_function,
    bool p_enabled,
    const char *p_linked_monitors,
    Camera *p_camera,
    int p_orientation,
    const char *p_event_prefix,
    const char *p_label_format,
    const Coord &p_label_coord,
    int p_image_buffer_count,
    int p_warmup_count,
    int p_pre_event_count,
    int p_post_event_count,
    int p_stream_replay_buffer,
    int p_alarm_frame_count,
    int p_section_length,
    int p_frame_skip,
    int p_capture_delay,
    int p_alarm_capture_delay,
    int p_fps_report_interval,
    int p_ref_blend_perc,
    bool p_track_motion,
    Rgb p_signal_check_colour,
    Purpose p_purpose,
    int p_n_zones,
    Zone *p_zones[]
) : id( p_id ),
    function( (Function)p_function ),
    enabled( p_enabled ),
    width( (p_orientation==ROTATE_90||p_orientation==ROTATE_270)?p_camera->Height():p_camera->Width() ),
    height( (p_orientation==ROTATE_90||p_orientation==ROTATE_270)?p_camera->Width():p_camera->Height() ),
    orientation( (Orientation)p_orientation ),
    label_coord( p_label_coord ),
    image_buffer_count( p_image_buffer_count ),
    warmup_count( p_warmup_count ),
    pre_event_count( p_pre_event_count ),
    post_event_count( p_post_event_count ),
    stream_replay_buffer( p_stream_replay_buffer ),
    section_length( p_section_length ),
    frame_skip( p_frame_skip ),
    capture_delay( p_capture_delay ),
    alarm_capture_delay( p_alarm_capture_delay ),
    alarm_frame_count( p_alarm_frame_count ),
    fps_report_interval( p_fps_report_interval ),
    ref_blend_perc( p_ref_blend_perc ),
    track_motion( p_track_motion ),
    signal_check_colour( p_signal_check_colour ),
    image( width, height, p_camera->Colours() ),
    ref_image( width, height, p_camera->Colours() ),
    purpose( p_purpose ),
    camera( p_camera ),
    n_zones( p_n_zones ),
    zones( p_zones )
{
    strncpy( name, p_name, sizeof(name) );

    strncpy( event_prefix, p_event_prefix, sizeof(event_prefix) );
    strncpy( label_format, p_label_format, sizeof(label_format) );

    // Change \n to actual line feeds
    char *token_ptr = label_format;
    const char *token_string = "\n";
    while( ( token_ptr = strstr( token_ptr, token_string ) ) )
    {
        if ( *(token_ptr+1) )
        {
            *token_ptr = '\n';
            token_ptr++;
            strcpy( token_ptr, token_ptr+1 );
        }
        else
        {
            *token_ptr = '\0';
            break;
        }
    }

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
             + (image_buffer_count*sizeof(struct timeval))
             + (image_buffer_count*camera->ImageSize());

    Debug( 1, "mem.size=%d", mem_size );
#if ZM_MEM_MAPPED
    snprintf( mem_file, sizeof(mem_file), "%s/zm.mmap.%d", config.path_map, id );
    map_fd = open( mem_file, O_RDWR|O_CREAT, (mode_t)0600 );
    if ( map_fd < 0 )
        Fatal( "Can't open memory map file %s, probably not enough space free: %s", mem_file, strerror(errno) );
        struct stat map_stat;
    if ( fstat( map_fd, &map_stat ) < 0 )
        Fatal( "Can't stat memory map file %s: %s", mem_file, strerror(errno) );
    if ( map_stat.st_size == 0 )
    {
        // Allocate the size
        if ( ftruncate( map_fd, mem_size ) < 0 )
            Fatal( "Can't extend memory map file %s to %d bytes: %s", mem_file, mem_size, strerror(errno) );
    }
    else if ( map_stat.st_size != mem_size )
    {
        Error( "Got unexpected memory map file size %ld, expected %d", map_stat.st_size, mem_size );
    }

    mem_ptr = (unsigned char *)mmap( NULL, mem_size, PROT_READ|PROT_WRITE, MAP_SHARED|MAP_LOCKED, map_fd, 0 );
    if ( mem_ptr == MAP_FAILED )
        if ( errno == EAGAIN )
        {
            Debug( 1, "Unable to map file %s (%d bytes) to locked memory, trying unlocked", mem_file, mem_size );
            mem_ptr = (unsigned char *)mmap( NULL, mem_size, PROT_READ|PROT_WRITE, MAP_SHARED, map_fd, 0 );
        }
    if ( mem_ptr == MAP_FAILED )
        Fatal( "Can't map file %s (%d bytes) to memory: %s(%d)", mem_file, mem_size, strerror(errno), errno );
#else // ZM_MEM_MAPPED
    shm_id = shmget( (config.shm_key&0xffff0000)|id, mem_size, IPC_CREAT|0700 );
    if ( shm_id < 0 )
    {
        Error( "Can't shmget, probably not enough shared memory space free: %s", strerror(errno));
        exit( -1 );
    }
    mem_ptr = (unsigned char *)shmat( shm_id, 0, 0 );
    if ( mem_ptr < 0 )
    {
        Error( "Can't shmat: %s", strerror(errno));
        exit( -1 );
    }
#endif // ZM_MEM_MAPPED

    shared_data = (SharedData *)mem_ptr;
    trigger_data = (TriggerData *)((char *)shared_data + sizeof(SharedData));
    struct timeval *shared_timestamps = (struct timeval *)((char *)trigger_data + sizeof(TriggerData));
    unsigned char *shared_images = (unsigned char *)((char *)shared_timestamps + (image_buffer_count*sizeof(struct timeval)));

    if ( purpose == CAPTURE )
    {
        memset( mem_ptr, 0, mem_size );
        shared_data->size = sizeof(SharedData);
        shared_data->valid = true;
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
        trigger_data->size = sizeof(TriggerData);
        trigger_data->trigger_state = TRIGGER_CANCEL;
        trigger_data->trigger_score = 0;
        trigger_data->trigger_cause[0] = 0;
        trigger_data->trigger_text[0] = 0;
        trigger_data->trigger_showtext[0] = 0;
    }
    else if ( purpose == ANALYSIS )
    {
        shared_data->state = IDLE;
        shared_data->last_read_time = 0;
        shared_data->alarm_x = -1;
        shared_data->alarm_y = -1;
    }

    if ( !shared_data->valid )
    {
        if ( purpose != QUERY )
        {
            Error( "Shared data not initialised by capture daemon" );
            exit( -1 );
        }
        else
        {
            Warning( "Shared data not initialised by capture daemon, some query functions may not be available or produce invalid results" );
        }
    }

    image_buffer = new Snapshot[image_buffer_count];
    for ( int i = 0; i < image_buffer_count; i++ )
    {
        image_buffer[i].timestamp = &(shared_timestamps[i]);
        image_buffer[i].image = new Image( width, height, camera->Colours(), &(shared_images[i*camera->ImageSize()]) );
    }
    if ( !n_zones )
    {
        n_zones = 1;
        zones = new Zone *[1];
        Coord coords[4] = { Coord( 0, 0 ), Coord( width-1, 0 ), Coord( width-1, height-1 ), Coord( 0, height-1 ) };
        zones[0] = new Zone( this, 0, "All", Zone::ACTIVE, Polygon( sizeof(coords)/sizeof(*coords), coords ), RGB_RED, Zone::BLOBS );
    }
    start_time = last_fps_time = time( 0 );

    event = 0;

    Debug( 1, "Monitor %s has function %d", name, function );
    Debug( 1, "Monitor %s LBF = '%s', LBX = %d, LBY = %d", name, label_format, label_coord.X(), label_coord.Y() );
    Debug( 1, "Monitor %s IBC = %d, WUC = %d, pEC = %d, PEC = %d, EAF = %d, FRI = %d, RBP = %d, FM = %d", name, image_buffer_count, warmup_count, pre_event_count, post_event_count, alarm_frame_count, fps_report_interval, ref_blend_perc, track_motion );

    if ( purpose == ANALYSIS )
    {
        static char path[PATH_MAX];

        strncpy( path, config.dir_events, sizeof(path) );

        struct stat statbuf;
        errno = 0;
        stat( path, &statbuf );
        if ( errno == ENOENT || errno == ENOTDIR )
        {
            if ( mkdir( path, 0755 ) )
            {
                Error( "Can't make %s: %s", path, strerror(errno));
            }
        }

        snprintf( path, sizeof(path), "%s/%d", config.dir_events, id );

        errno = 0;
        stat( path, &statbuf );
        if ( errno == ENOENT || errno == ENOTDIR )
        {
            if ( mkdir( path, 0755 ) )
            {
                Error( "Can't make %s: %s", path, strerror(errno));
            }
            char temp_path[PATH_MAX];
            snprintf( temp_path, sizeof(temp_path), "%d", id );
            if ( chdir( config.dir_events ) < 0 )
                Fatal( "Can't change directory to '%s': %s", config.dir_events, strerror(errno) );
            if ( symlink( temp_path, name ) < 0 )
                Fatal( "Can't symlink '%s' to '%s': %s", temp_path, name, strerror(errno) );
            if ( chdir( ".." ) < 0 )
                Fatal( "Can't change to parent directory: %s", strerror(errno) );
        }

        while( shared_data->last_write_index == image_buffer_count )
        {
            Warning( "Waiting for capture daemon" );
            sleep( 1 );
        }
        ref_image.Assign( width, height, camera->Colours(), image_buffer[shared_data->last_write_index].image->Buffer() );

        n_linked_monitors = 0;
        linked_monitors = 0;
        ReloadLinkedMonitors( p_linked_monitors );
    }
}

Monitor::~Monitor()
{
    if ( event )
        Info( "%s: %03d - Closing event %d, shutting down", name, image_count, event->Id() );
    closeEvent();

    for ( int i = 0; i < image_buffer_count; i++ )
    {
        delete image_buffer[i].image;
    }
    delete[] image_buffer;

    for ( int i = 0; i < n_zones; i++ )
    {
        delete zones[i];
    }
    delete[] zones;

    delete camera;

    if ( purpose == ANALYSIS )
    {
        shared_data->state = state = IDLE;
        shared_data->last_read_index = image_buffer_count;
        shared_data->last_read_time = 0;
    }
    else if ( purpose == CAPTURE )
    {
        shared_data->valid = false;
        memset( mem_ptr, 0, mem_size );
    }

#if ZM_MEM_MAPPED
    if ( msync( mem_ptr, mem_size, MS_SYNC ) < 0 )
        Error( "Can't msync: %s", strerror(errno) );
    if ( munmap( mem_ptr, mem_size ) < 0 )
        Fatal( "Can't munmap: %s", strerror(errno) );
    close( map_fd );
#else // ZM_MEM_MAPPED
    struct shmid_ds shm_data;
    if ( shmctl( shm_id, IPC_STAT, &shm_data ) < 0 )
    {
        Error( "Can't shmctl: %s", strerror(errno) );
        exit( -1 );
    }
    if ( shm_data.shm_nattch <= 1 )
    {
        if ( shmctl( shm_id, IPC_RMID, 0 ) < 0 )
        {
            Error( "Can't shmctl: %s", strerror(errno) );
            exit( -1 );
        }
    }
#endif // ZM_MEM_MAPPED
}

void Monitor::AddZones( int p_n_zones, Zone *p_zones[] )
{
    for ( int i = 0; i < n_zones; i++ )
        delete zones[i];
    delete[] zones;
    n_zones = p_n_zones;
    zones = p_zones;
}

Monitor::State Monitor::GetState() const
{
    return( shared_data->state );
}

int Monitor::GetImage( int index, int scale ) const
{
    if ( index < 0 || index > image_buffer_count )
    {
        index = shared_data->last_write_index;
    }

    if ( index != image_buffer_count )
    {
        Snapshot *snap = &image_buffer[index];
        Image snap_image( *(snap->image) );

        if ( scale != ZM_SCALE_BASE )
        {
            snap_image.Scale( scale );
        }

        static char filename[PATH_MAX];
        snprintf( filename, sizeof(filename), "Monitor%d.jpg", id );
        if ( !config.timestamp_on_capture )
        {
            TimestampImage( &snap_image, snap->timestamp );
        }
        snap_image.WriteJpeg( filename );
    }
    else
    {
        Error( "Unable to generate image, no images in buffer" );
    }
    return( 0 );
}

struct timeval Monitor::GetTimestamp( int index ) const
{
    if ( index < 0 || index > image_buffer_count )
    {
        index = shared_data->last_write_index;
    }

    if ( index != image_buffer_count )
    {
        Snapshot *snap = &image_buffer[index];

        return( *(snap->timestamp) );
    }
    else
    {
        static struct timeval null_tv = { 0, 0 };

        return( null_tv );
    }
}

unsigned int Monitor::GetLastReadIndex() const
{
    return( shared_data->last_read_index!=image_buffer_count?shared_data->last_read_index:-1 );
}

unsigned int Monitor::GetLastWriteIndex() const
{
    return( shared_data->last_write_index!=image_buffer_count?shared_data->last_write_index:-1 );
}

unsigned int Monitor::GetLastEvent() const
{
    return( shared_data->last_event );
}

double Monitor::GetFPS() const
{
    int index1 = shared_data->last_write_index;
    if ( index1 == image_buffer_count )
    {
        return( 0.0 );
    }
    Snapshot *snap1 = &image_buffer[index1];
    if ( !snap1->timestamp || !snap1->timestamp->tv_sec )
    {
        return( 0.0 );
    }
    struct timeval time1 = *snap1->timestamp;

    int image_count = image_buffer_count;
    int index2 = (index1+1)%image_buffer_count;
    if ( index2 == image_buffer_count )
    {
        return( 0.0 );
    }
    Snapshot *snap2 = &image_buffer[index2];
    while ( !snap2->timestamp || !snap2->timestamp->tv_sec )
    {
        if ( index1 == index2 )
        {
            return( 0.0 );
        }
        index2 = (index2+1)%image_buffer_count;
        snap2 = &image_buffer[index2];
        image_count--;
    }
    struct timeval time2 = *snap2->timestamp;

    double time_diff = tvDiffSec( time2, time1 );

    double curr_fps = image_count/time_diff;

    if ( curr_fps < 0.0 )
    {
        //Error( "Negative FPS %f, time_diff = %lf (%d:%ld.%ld - %d:%ld.%ld), ibc: %d", curr_fps, time_diff, index2, time2.tv_sec, time2.tv_usec, index1, time1.tv_sec, time1.tv_usec, image_buffer_count );
        return( 0.0 );
    }
    return( curr_fps );
}

void Monitor::ForceAlarmOn( int force_score, const char *force_cause, const char *force_text )
{
    trigger_data->trigger_state = TRIGGER_ON;
    trigger_data->trigger_score = force_score;
    strncpy( trigger_data->trigger_cause, force_cause, sizeof(trigger_data->trigger_cause) );
    strncpy( trigger_data->trigger_text, force_text, sizeof(trigger_data->trigger_text) );
}

void Monitor::ForceAlarmOff()
{
    trigger_data->trigger_state = TRIGGER_OFF;
}

void Monitor::CancelForced()
{
    trigger_data->trigger_state = TRIGGER_CANCEL;
}

void Monitor::actionReload()
{
    shared_data->action |= RELOAD;
}

void Monitor::actionEnable()
{
    shared_data->action |= RELOAD;

    static char sql[ZM_SQL_SML_BUFSIZ];
    snprintf( sql, sizeof(sql), "update Monitors set Enabled = 1 where Id = '%d'", id );
    if ( mysql_query( &dbconn, sql ) )
    {
        Error( "Can't run query: %s", mysql_error( &dbconn ) );
        exit( mysql_errno( &dbconn ) );
    }
}

void Monitor::actionDisable()
{
    shared_data->action |= RELOAD;

    static char sql[ZM_SQL_SML_BUFSIZ];
    snprintf( sql, sizeof(sql), "update Monitors set Enabled = 0 where Id = '%d'", id );
    if ( mysql_query( &dbconn, sql ) )
    {
        Error( "Can't run query: %s", mysql_error( &dbconn ) );
        exit( mysql_errno( &dbconn ) );
    }
}

void Monitor::actionSuspend()
{
    shared_data->action |= SUSPEND;
}

void Monitor::actionResume()
{
    shared_data->action |= RESUME;
}

int Monitor::actionBrightness( int p_brightness )
{
    if ( purpose != CAPTURE )
    {
        if ( p_brightness >= 0 )
        {
            shared_data->brightness = p_brightness;
            shared_data->action |= SET_SETTINGS;
            int wait_loops = 10;
            while ( shared_data->action & SET_SETTINGS )
            {
                if ( wait_loops-- )
                    usleep( 100000 );
                else
                {
                    Warning( "Timed out waiting to set brightness" );
                    return( -1 );
                }
            }
        }
        else
        {
            shared_data->action |= GET_SETTINGS;
            int wait_loops = 10;
            while ( shared_data->action & GET_SETTINGS )
            {
                if ( wait_loops-- )
                    usleep( 100000 );
                else
                {
                    Warning( "Timed out waiting to get brightness" );
                    return( -1 );
                }
            }
        }
        return( shared_data->brightness );
    }
    return( camera->Brightness( p_brightness ) );
}

int Monitor::actionContrast( int p_contrast )
{
    if ( purpose != CAPTURE )
    {
        if ( p_contrast >= 0 )
        {
            shared_data->contrast = p_contrast;
            shared_data->action |= SET_SETTINGS;
            int wait_loops = 10;
            while ( shared_data->action & SET_SETTINGS )
            {
                if ( wait_loops-- )
                    usleep( 100000 );
                else
                {
                    Warning( "Timed out waiting to set contrast" );
                    return( -1 );
                }
            }
        }
        else
        {
            shared_data->action |= GET_SETTINGS;
            int wait_loops = 10;
            while ( shared_data->action & GET_SETTINGS )
            {
                if ( wait_loops-- )
                    usleep( 100000 );
                else
                {
                    Warning( "Timed out waiting to get contrast" );
                    return( -1 );
                }
            }
        }
        return( shared_data->contrast );
    }
    return( camera->Contrast( p_contrast ) );
}

int Monitor::actionHue( int p_hue )
{
    if ( purpose != CAPTURE )
    {
        if ( p_hue >= 0 )
        {
            shared_data->hue = p_hue;
            shared_data->action |= SET_SETTINGS;
            int wait_loops = 10;
            while ( shared_data->action & SET_SETTINGS )
            {
                if ( wait_loops-- )
                    usleep( 100000 );
                else
                {
                    Warning( "Timed out waiting to set hue" );
                    return( -1 );
                }
            }
        }
        else
        {
            shared_data->action |= GET_SETTINGS;
            int wait_loops = 10;
            while ( shared_data->action & GET_SETTINGS )
            {
                if ( wait_loops-- )
                    usleep( 100000 );
                else
                {
                    Warning( "Timed out waiting to get hue" );
                    return( -1 );
                }
            }
        }
        return( shared_data->hue );
    }
    return( camera->Hue( p_hue ) );
}

int Monitor::actionColour( int p_colour )
{
    if ( purpose != CAPTURE )
    {
        if ( p_colour >= 0 )
        {
            shared_data->colour = p_colour;
            shared_data->action |= SET_SETTINGS;
            int wait_loops = 10;
            while ( shared_data->action & SET_SETTINGS )
            {
                if ( wait_loops-- )
                    usleep( 100000 );
                else
                {
                    Warning( "Timed out waiting to set colour" );
                    return( -1 );
                }
            }
        }
        else
        {
            shared_data->action |= GET_SETTINGS;
            int wait_loops = 10;
            while ( shared_data->action & GET_SETTINGS )
            {
                if ( wait_loops-- )
                    usleep( 100000 );
                else
                {
                    Warning( "Timed out waiting to get colour" );
                    return( -1 );
                }
            }
        }
        return( shared_data->colour );
    }
    return( camera->Colour( p_colour ) );
}

void Monitor::DumpZoneImage( const char *zone_string )
{
    int exclude_id = 0;
    int extra_colour = 0;
    Polygon extra_zone;

    if ( zone_string )
    {
        if ( !Zone::ParseZoneString( zone_string, exclude_id, extra_colour, extra_zone ) )
        {
            Error( "Failed to parse zone string, ignoring" );
        }
    }

    int index = shared_data->last_write_index;
    Snapshot *snap = &image_buffer[index];
    Image *snap_image = snap->image;

    Image zone_image( *snap_image );
    zone_image.Colourise();
    for( int i = 0; i < n_zones; i++ )
    {
        if ( exclude_id && (!extra_colour || extra_zone.getNumCoords()) && zones[i]->Id() == exclude_id )
            continue;

        Rgb colour;
        if ( exclude_id && !extra_zone.getNumCoords() && zones[i]->Id() == exclude_id )
        {
            colour = extra_colour;
        }
        else
        {
            if ( zones[i]->IsActive() )
            {
                colour = RGB_RED;
            }
            else if ( zones[i]->IsInclusive() )
            {
                colour = RGB_ORANGE;
            }
            else if ( zones[i]->IsExclusive() )
            {
                colour = RGB_PURPLE;
            }
            else if ( zones[i]->IsPreclusive() )
            {
                colour = RGB_BLUE;
            }
            else
            {
                colour = RGB_WHITE;
            }
        }
        zone_image.Fill( colour, 2, zones[i]->GetPolygon() );
        zone_image.Outline( colour, zones[i]->GetPolygon() );
    }

    if ( extra_zone.getNumCoords() )
    {
        zone_image.Fill( extra_colour, 2, extra_zone );
        zone_image.Outline( extra_colour, extra_zone );
    }

    static char filename[PATH_MAX];
    snprintf( filename, sizeof(filename), "Zones%d.jpg", id );
    zone_image.WriteJpeg( filename );
}

void Monitor::DumpImage( Image *dump_image ) const
{
    if ( image_count && !(image_count%10) )
    {
        static char filename[PATH_MAX];
        static char new_filename[PATH_MAX];
        snprintf( filename, sizeof(filename), "Monitor%d.jpg", id );
        snprintf( new_filename, sizeof(new_filename), "Monitor%d-new.jpg", id );
        dump_image->WriteJpeg( new_filename );
        rename( new_filename, filename );
    }
}

bool Monitor::CheckSignal( const Image *image )
{
    static bool static_undef = true;
    static unsigned char red_val;
    static unsigned char green_val;
    static unsigned char blue_val;

    if ( config.signal_check_points > 0 )
    {
        if ( static_undef )
        {
            static_undef = false;
            red_val = RGB_RED_VAL(signal_check_colour);
            green_val = RGB_GREEN_VAL(signal_check_colour);
            blue_val = RGB_BLUE_VAL(signal_check_colour);
        }

        const unsigned char *buffer = image->Buffer();
        int pixels = image->Pixels();
        int width = image->Width();
        int colours = image->Colours();

        int index = 0;
        for ( int i = 0; i < config.signal_check_points; i++ )
        {
            while( true )
            {
                index = (int)(((long long)rand()*(long long)(pixels-1))/RAND_MAX);
                if ( !config.timestamp_on_capture || !label_format[0] )
                    break;
                // Avoid sampling the rows with timestamp in
                int y = index / (width * colours);
                if ( y < label_coord.Y() || y <= label_coord.Y()+Image::LINE_HEIGHT )
                    break;
            }
            const unsigned char *ptr = buffer+(index*colours);
            if ( (RED(ptr) != red_val) || (GREEN(ptr) != green_val) || (BLUE(ptr) != blue_val) )
            {
                return( true );
            }
        }
        return( false );
    }
    return( true );
}

bool Monitor::Analyse()
{
    if ( shared_data->last_read_index == shared_data->last_write_index )
    {
        return( false );
    }

    struct timeval now;
    gettimeofday( &now, NULL );

    if ( image_count && !(image_count%fps_report_interval) )
    {
        fps = double(fps_report_interval)/(now.tv_sec-last_fps_time);
        Info( "%s: %d - Processing at %.2f fps", name, image_count, fps );
        last_fps_time = now.tv_sec;
    }

    int index;
    if ( config.opt_adaptive_skip )
    {
        int read_margin = shared_data->last_read_index - shared_data->last_write_index;
        if ( read_margin < 0 ) read_margin += image_buffer_count;

        int step = 1;
        if ( read_margin > 0 )
        {
            step = (9*image_buffer_count)/(5*read_margin);
        }

        int pending_frames = shared_data->last_write_index - shared_data->last_read_index;
        if ( pending_frames < 0 ) pending_frames += image_buffer_count;

        Debug( 4, "RI:%d, WI: %d, PF = %d, RM = %d, Step = %d", shared_data->last_read_index, shared_data->last_write_index, pending_frames, read_margin, step );
        if ( step <= pending_frames )
        {
            index = (shared_data->last_read_index+step)%image_buffer_count;
        }
        else
        {
            if ( pending_frames )
            {
                Warning( "Approaching buffer overrun, consider slowing capture, simplifying analysis or increasing ring buffer size" );
            }
            index = shared_data->last_write_index%image_buffer_count;
        }
    }
    else
    {
        index = shared_data->last_write_index%image_buffer_count;
    }

    Snapshot *snap = &image_buffer[index];
    struct timeval *timestamp = snap->timestamp;
    Image *snap_image = snap->image;

    if ( shared_data->action )
    {
        if ( shared_data->action & RELOAD )
        {
            Info( "Received reload indication at count %d", image_count );
            shared_data->action &= ~RELOAD;
            Reload();
        }
        if ( shared_data->action & SUSPEND )
        {
            if ( Active() )
            {
                Info( "Received suspend indication at count %d", image_count );
                shared_data->active = false;
                //closeEvent();
            }
            if ( config.max_suspend_time )
            {
                auto_resume_time = now.tv_sec + config.max_suspend_time;
            }
            shared_data->action &= ~SUSPEND;
        }
        if ( shared_data->action & RESUME )
        {
            if ( Enabled() && !Active() )
            {
                Info( "Received resume indication at count %d", image_count );
                shared_data->active = true;
                ref_image = *snap_image;
                ready_count = image_count+(warmup_count/2);
                shared_data->alarm_x = shared_data->alarm_y = -1;
            }
            shared_data->action &= ~RESUME;
        }
    }
    if ( auto_resume_time && (now.tv_sec >= auto_resume_time) )
    {
        Info( "Auto resuming at count %d", image_count );
        shared_data->active = true;
        ref_image = *snap_image;
        ready_count = image_count+(warmup_count/2);
        auto_resume_time = 0;
    }

    static bool static_undef = true;
    static struct timeval **timestamps;
    static Image **images;
    static int last_section_mod = 0;
    static bool last_signal;

    if ( static_undef )
    {
        static_undef = false;
        timestamps = new struct timeval *[pre_event_count];
        images = new Image *[pre_event_count];
        last_signal = shared_data->signal;
    }

    if ( Enabled() )
    {
        bool signal = shared_data->signal;
        bool signal_change = (signal != last_signal);
        if ( trigger_data->trigger_state != TRIGGER_OFF )
        {
            unsigned int score = 0;
            if ( Ready() )
            {
                std::string cause;
                Event::StringSetMap noteSetMap;

                if ( trigger_data->trigger_state == TRIGGER_ON )
                {
                    score += trigger_data->trigger_score;
                    if ( !event )
                    {
                        if ( cause.length() )
                            cause += ", ";
                        cause += trigger_data->trigger_cause;
                    }
                    Event::StringSet noteSet;
                    noteSet.insert( trigger_data->trigger_text );
                    noteSetMap[trigger_data->trigger_cause] = noteSet;
                }
                if ( signal_change )
                {
                    const char *signalText;
                    if ( !signal )
                        signalText = "Lost";
                    else
                    {
                        signalText = "Reacquired";
                        score += 100;
                    }
                    Warning( "%s: %s", SIGNAL_CAUSE, signalText );
                    if ( event && !signal )
                    {
                        Info( "%s: %03d - Closing event %d, signal loss", name, image_count, event->Id() );
                        closeEvent();
                        shared_data->state = state = IDLE;
                        last_section_mod = 0;
                    }
                    if ( !event )
                    {
                        if ( cause.length() )
                            cause += ", ";
                        cause += SIGNAL_CAUSE;
                    }
                    Event::StringSet noteSet;
                    noteSet.insert( signalText );
                    noteSetMap[SIGNAL_CAUSE] = noteSet;
                    shared_data->active = signal;
                    ref_image = *snap_image;
                }
                else if ( signal && Active() && function != RECORD && function != NODECT )
                {
                    Event::StringSet zoneSet;
                    int motion_score = DetectMotion( *snap_image, zoneSet );
                    if ( motion_score )
                    {
                        if ( !event )
                        {
                            score += motion_score;
                            if ( cause.length() )
                                cause += ", ";
                            cause += MOTION_CAUSE;
                        }
                        else
                        {
                            score += motion_score;
                        }
                        noteSetMap[MOTION_CAUSE] = zoneSet;
                    }
                    shared_data->active = signal;
                }
                if ( n_linked_monitors > 0 )
                {
                    bool first_link = true;
                    Event::StringSet noteSet;
                    for ( int i = 0; i < n_linked_monitors; i++ )
                    {
                        if ( linked_monitors[i]->isConnected() )
                        {
                            if ( linked_monitors[i]->hasAlarmed() )
                            {
                                if ( !event )
                                {
                                    if ( first_link )
                                    {
                                        if ( cause.length() )
                                            cause += ", ";
                                        cause += LINKED_CAUSE;
                                        first_link = false;
                                    }
                                }
                                noteSet.insert( linked_monitors[i]->Name() );
                                score += 50;
                            }
                        }
                        else
                        {
                            linked_monitors[i]->connect();
                        }
                    }
                    if ( noteSet.size() > 0 )
                        noteSetMap[LINKED_CAUSE] = noteSet;
                }
                if ( (!signal_change && signal) && (function == RECORD || function == MOCORD) )
                {
                    if ( event )
                    {
                        int section_mod = timestamp->tv_sec%section_length;
                        if ( section_mod < last_section_mod )
                        {
                            if ( state == IDLE || state == TAPE || event_close_mode == CLOSE_TIME )
                            {
                                if ( state == IDLE || state == TAPE )
                                    Info( "%s: %03d - Closing event %d, section end", name, image_count, event->Id() )
                                else
                                    Info( "%s: %03d - Closing event %d, section end forced ", name, image_count, event->Id() );
                                closeEvent();
                                last_section_mod = 0;
                            }
                        }
                        else
                        {
                            last_section_mod = section_mod;
                        }
                    }
                    if ( !event )
                    {
                        shared_data->state = state = TAPE;

                        // Create event
                        event = new Event( this, *timestamp, "Continuous", noteSetMap );
                        shared_data->last_event = event->Id();

                        Info( "%s: %03d - Opening new event %d, section start", name, image_count, event->Id() );

                        //if ( config.overlap_timed_events )
                        if ( false )
                        {
                            int pre_index = ((index+image_buffer_count)-pre_event_count)%image_buffer_count;
                            int pre_event_images = pre_event_count;
                            while ( pre_event_images && !image_buffer[pre_index].timestamp->tv_sec )
                            {
                                pre_index = (pre_index+1)%image_buffer_count;
                                pre_event_images--;
                            }

                            if ( pre_event_images )
                            {
                                for ( int i = 0; i < pre_event_images; i++ )
                                {
                                    timestamps[i] = image_buffer[pre_index].timestamp;
                                    images[i] = image_buffer[pre_index].image;

                                    pre_index = (pre_index+1)%image_buffer_count;
                                }
                                event->AddFrames( pre_event_images, images, timestamps );
                            }
                        }
                    }
                }
                if ( score )
                {
                    if ( (state == IDLE || state == TAPE || state == PREALARM ) )
                    {
                        if ( Event::PreAlarmCount() >= (alarm_frame_count-1) )
                        {
                            Info( "%s: %03d - Gone into alarm state", name, image_count );
                            shared_data->state = state = ALARM;
                            if ( signal_change || (function != MOCORD && state != ALERT) )
                            {
                                int pre_index;
                                if ( alarm_frame_count > 1 )
                                    pre_index = ((index+image_buffer_count)-((alarm_frame_count-1)+pre_event_count))%image_buffer_count;
                                else
                                    pre_index = ((index+image_buffer_count)-pre_event_count)%image_buffer_count;

                                int pre_event_images = pre_event_count;
                                while ( pre_event_images && !image_buffer[pre_index].timestamp->tv_sec )
                                {
                                    pre_index = (pre_index+1)%image_buffer_count;
                                    pre_event_images--;
                                }

                                event = new Event( this, *(image_buffer[pre_index].timestamp), cause, noteSetMap );
                                shared_data->last_event = event->Id();

                                Info( "%s: %03d - Opening new event %d, alarm start", name, image_count, event->Id() );

                                if ( pre_event_images )
                                {
                                    for ( int i = 0; i < pre_event_images; i++ )
                                    {
                                        timestamps[i] = image_buffer[pre_index].timestamp;
                                        images[i] = image_buffer[pre_index].image;

                                        pre_index = (pre_index+1)%image_buffer_count;
                                    }
                                    event->AddFrames( pre_event_images, images, timestamps );
                                }
                                if ( alarm_frame_count )
                                {
                                    event->SavePreAlarmFrames();
                                }
                            }
                        }
                        else if ( state != PREALARM )
                        {
                            Info( "%s: %03d - Gone into prealarm state", name, image_count );
                            shared_data->state = state = PREALARM;
                        }
                    }
                    else if ( state == ALERT )
                    {
                        Info( "%s: %03d - Gone back into alarm state", name, image_count );
                        shared_data->state = state = ALARM;
                    }
                    last_alarm_count = image_count;
                }
                else
                {
                    if ( state == ALARM )
                    {
                        Info( "%s: %03d - Gone into alert state", name, image_count );
                        shared_data->state = state = ALERT;
                    }
                    else if ( state == ALERT )
                    {
                        if ( image_count-last_alarm_count > post_event_count )
                        {
                            Info( "%s: %03d - Left alarm state (%d) - %d(%d) images", name, image_count, event->Id(), event->Frames(), event->AlarmFrames() );
                            //if ( function != MOCORD || event_close_mode == CLOSE_ALARM || event->Cause() == SIGNAL_CAUSE )
                            if ( function != MOCORD || event_close_mode == CLOSE_ALARM )
                            {
                                shared_data->state = state = IDLE;
                                Info( "%s: %03d - Closing event %d, alarm end%s", name, image_count, event->Id(), (function==MOCORD)?", section truncated":"" );
                                closeEvent();
                            }
                            else
                            {
                                shared_data->state = state = TAPE;
                            }
                        }
                    }
                    if ( state == PREALARM )
                    {
                        if ( function != MOCORD )
                        {
                            shared_data->state = state = IDLE;
                        }
                        else
                        {
                            shared_data->state = state = TAPE;
                        }
                    }
                    if ( Event::PreAlarmCount() )
                        Event::EmptyPreAlarmFrames();
                }
                if ( state != IDLE )
                {
                    if ( state == PREALARM || state == ALARM )
                    {
                        if ( config.create_analysis_images )
                        {
                            bool got_anal_image = false;
                            Image alarm_image( *snap_image );
                            for( int i = 0; i < n_zones; i++ )
                            {
                                if ( zones[i]->Alarmed() )
                                {
                                    if ( zones[i]->AlarmImage() )
                                    {
                                        alarm_image.Overlay( *(zones[i]->AlarmImage()) );
                                        got_anal_image = true;
                                    }
                                    if ( config.record_event_stats && state == ALARM )
                                    {
                                        zones[i]->RecordStats( event );
                                    }
                                }
                            }
                            if ( got_anal_image )
                            {
                                if ( state == PREALARM )
                                    Event::AddPreAlarmFrame( snap_image, *timestamp, score, &alarm_image );
                                else
                                    event->AddFrame( snap_image, *timestamp, score, &alarm_image );
                            }
                            else
                            {
                                if ( state == PREALARM )
                                    Event::AddPreAlarmFrame( snap_image, *timestamp, score );
                                else
                                    event->AddFrame( snap_image, *timestamp, score );
                            }
                        }
                        else
                        {
                            for( int i = 0; i < n_zones; i++ )
                            {
                                if ( zones[i]->Alarmed() )
                                {
                                    if ( config.record_event_stats && state == ALARM )
                                    {
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
                    }
                    else if ( state == ALERT )
                    {
                        event->AddFrame( snap_image, *timestamp );
                        if ( noteSetMap.size() > 0 )
                            event->updateNotes( noteSetMap );
                    }
                    else if ( state == TAPE )
                    {
                        if ( !(image_count%(frame_skip+1)) )
                        {
                            if ( config.bulk_frame_interval > 1 )
                            {
                                event->AddFrame( snap_image, *timestamp, (event->Frames()<pre_event_count?0:-1) );
                            }
                            else
                            {
                                event->AddFrame( snap_image, *timestamp );
                            }
                        }
                    }
                }
                if ( function == RECORD || function == MOCORD )
                {
                    if ( state == IDLE || state == TAPE )
                    {
                        int section_mod = timestamp->tv_sec%section_length;
                        if ( section_mod < last_section_mod )
                        {
                            Info( "%s: %03d - Closing event %d, section end2", name, image_count, event->Id() );
                            closeEvent();
                            last_section_mod = 0;
                        }
                        else
                        {
                            last_section_mod = section_mod;
                        }
                    }
                }
            }
        }
        else
        {
            if ( event )
            {
                Info( "%s: %03d - Closing event %d, trigger off", name, image_count, event->Id() );
                closeEvent();
            }
            shared_data->state = state = IDLE;
            last_section_mod = 0;
        }
        if ( signal && (function == MODECT || function == MOCORD) && (config.blend_alarmed_images || state != ALARM) )
        {
            ref_image.Blend( *snap_image, ref_blend_perc );
        }
        last_signal = signal;
    }

    shared_data->last_read_index = index%image_buffer_count;
    //shared_data->last_read_time = image_buffer[index].timestamp->tv_sec;
    shared_data->last_read_time = now.tv_sec;
    image_count++;

    return( true );
}

void Monitor::Reload()
{
    Debug( 1, "Reloading monitor %s", name );

    if ( event )
        Info( "%s: %03d - Closing event %d, reloading", name, image_count, event->Id() );

    closeEvent();

    static char sql[ZM_SQL_MED_BUFSIZ];
    snprintf( sql, sizeof(sql), "select Function+0, Enabled, LinkedMonitors, EventPrefix, LabelFormat, LabelX, LabelY, WarmupCount, PreEventCount, PostEventCount, AlarmFrameCount, SectionLength, FrameSkip, MaxFPS, AlarmMaxFPS, FPSReportInterval, RefBlendPerc, TrackMotion, SignalCheckColour from Monitors where Id = '%d'", id );
    if ( mysql_query( &dbconn, sql ) )
    {
        Error( "Can't run query: %s", mysql_error( &dbconn ) );
        exit( mysql_errno( &dbconn ) );
    }

    MYSQL_RES *result = mysql_store_result( &dbconn );
    if ( !result )
    {
        Error( "Can't use query result: %s", mysql_error( &dbconn ) );
        exit( mysql_errno( &dbconn ) );
    }
    int n_monitors = mysql_num_rows( result );
    if ( n_monitors != 1 )
    {
        Error( "Bogus number of monitors, %d, returned. Can't reload", n_monitors ); 
        return;
    }

    if ( MYSQL_ROW dbrow = mysql_fetch_row( result ) )
    {
        int index = 0;
        function = (Function)atoi(dbrow[index++]);
        enabled = atoi(dbrow[index++]);
        const char *p_linked_monitors = dbrow[index++];
        strncpy( event_prefix, dbrow[index++], sizeof(event_prefix) );
        strncpy( label_format, dbrow[index++], sizeof(label_format) );
        label_coord = Coord( atoi(dbrow[index]), atoi(dbrow[index+1]) ); index += 2;
        warmup_count = atoi(dbrow[index++]);
        pre_event_count = atoi(dbrow[index++]);
        post_event_count = atoi(dbrow[index++]);
        alarm_frame_count = atoi(dbrow[index++]);
        section_length = atoi(dbrow[index++]);
        frame_skip = atoi(dbrow[index++]);
        capture_delay = (dbrow[index]&&atof(dbrow[index])>0.0)?int(DT_PREC_3/atof(dbrow[index])):0; index++;
        alarm_capture_delay = (dbrow[index]&&atof(dbrow[index])>0.0)?int(DT_PREC_3/atof(dbrow[index])):0; index++;
        fps_report_interval = atoi(dbrow[index++]);
        ref_blend_perc = atoi(dbrow[index++]);
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
    if ( mysql_errno( &dbconn ) )
    {
        Error( "Can't fetch row: %s", mysql_error( &dbconn ) );
        exit( mysql_errno( &dbconn ) );
    }
    mysql_free_result( result );

    ReloadZones();
}

void Monitor::ReloadZones()
{
    Debug( 1, "Reloading zones for monitor %s", name );
    for( int i = 0; i < n_zones; i++ )
    {
        delete zones[i];
    }
    delete[] zones;
    zones = 0;
    n_zones = Zone::Load( this, zones );
    //DumpZoneImage();
}

void Monitor::ReloadLinkedMonitors( const char *p_linked_monitors )
{
    Debug( 1, "Reloading linked monitors for monitor %s, '%s'", name, p_linked_monitors );
    if ( n_linked_monitors )
    {
        for( int i = 0; i < n_linked_monitors; i++ )
        {
            delete linked_monitors[i];
        }
        delete[] linked_monitors;
        linked_monitors = 0;
    }

    n_linked_monitors = 0;
    if ( p_linked_monitors )
    {
        int n_link_ids = 0;
        int link_ids[256];

        char link_id_str[8];
        char *dest_ptr = link_id_str;
        const char *src_ptr = p_linked_monitors;
        while( 1 )
        {
            dest_ptr = link_id_str;
            while( *src_ptr >= '0' && *src_ptr <= '9' )
            {
                if ( (dest_ptr-link_id_str) < (sizeof(link_id_str)-1) )
                {
                    *dest_ptr++ = *src_ptr++;
                }
                else
                {
                    break;
                }
            }
            // Add the link monitor
            if ( dest_ptr != link_id_str )
            {
                *dest_ptr = '\0';
                int link_id = atoi(link_id_str);
                if ( link_id > 0 )
                {
                    Debug( 3, "Found linked monitor id %d", link_id );
                    int j;
                    for ( j = 0; j < n_link_ids; j++ )
                    {
                        if ( link_ids[j] == link_id )
                            break;
                    }
                    if ( j == n_link_ids ) // Not already found
                    {
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
        if ( n_link_ids > 0 )
        {
            Debug( 1, "Linking to %d monitors", n_link_ids );
            n_linked_monitors = n_link_ids;
            linked_monitors = new MonitorLink *[n_linked_monitors];
            for ( int i = 0; i < n_linked_monitors; i++ )
            {
                Debug( 1, "Checking linked monitor %d", link_ids[i] );

                static char sql[ZM_SQL_SML_BUFSIZ];
                snprintf( sql, sizeof(sql), "select Id, Name from Monitors where Id = %d and Function != 'None' and Function != 'Monitor' and Enabled = 1", link_ids[i] );
                if ( mysql_query( &dbconn, sql ) )
                {
                    Error( "Can't run query: %s", mysql_error( &dbconn ) );
                    exit( mysql_errno( &dbconn ) );
                }

                MYSQL_RES *result = mysql_store_result( &dbconn );
                if ( !result )
                {
                    Error( "Can't use query result: %s", mysql_error( &dbconn ) );
                    exit( mysql_errno( &dbconn ) );
                }
                int n_monitors = mysql_num_rows( result );
                if ( n_monitors == 1 )
                {
                    MYSQL_ROW dbrow = mysql_fetch_row( result );
                    Debug( 1, "Linking to monitor %d", link_ids[i] );
                    linked_monitors[i] = new MonitorLink( link_ids[i], dbrow[1] );
                }
                else
                {
                    Debug( 1, "Can't link to monitor %d, invalid id, function or not enabled", link_ids[i] );
                }
                mysql_free_result( result );
            }
        }
    }
}

int Monitor::LoadLocalMonitors( const char *device, Monitor **&monitors, Purpose purpose )
{
    static char sql[ZM_SQL_MED_BUFSIZ];
    if ( !device[0] )
    {
        strncpy( sql, "select Id, Name, Function+0, Enabled, LinkedMonitors, Device, Channel, Format, Method, Width, Height, Palette, Orientation+0, Brightness, Contrast, Hue, Colour, EventPrefix, LabelFormat, LabelX, LabelY, ImageBufferCount, WarmupCount, PreEventCount, PostEventCount, StreamReplayBuffer, AlarmFrameCount, SectionLength, FrameSkip, MaxFPS, AlarmMaxFPS, FPSReportInterval, RefBlendPerc, TrackMotion, SignalCheckColour from Monitors where Function != 'None' and Type = 'Local' order by Device, Channel", sizeof(sql) );
    }
    else
    {
        snprintf( sql, sizeof(sql), "select Id, Name, Function+0, Enabled, LinkedMonitors, Device, Channel, Format, Method, Width, Height, Palette, Orientation+0, Brightness, Contrast, Hue, Colour, EventPrefix, LabelFormat, LabelX, LabelY, ImageBufferCount, WarmupCount, PreEventCount, PostEventCount, StreamReplayBuffer, AlarmFrameCount, SectionLength, FrameSkip, MaxFPS, AlarmMaxFPS, FPSReportInterval, RefBlendPerc, TrackMotion, SignalCheckColour from Monitors where Function != 'None' and Type = 'Local' and Device = '%s' order by Channel", device );
    }
    if ( mysql_query( &dbconn, sql ) )
    {
        Error( "Can't run query: %s", mysql_error( &dbconn ) );
        exit( mysql_errno( &dbconn ) );
    }

    MYSQL_RES *result = mysql_store_result( &dbconn );
    if ( !result )
    {
        Error( "Can't use query result: %s", mysql_error( &dbconn ) );
        exit( mysql_errno( &dbconn ) );
    }
    int n_monitors = mysql_num_rows( result );
    Debug( 1, "Got %d monitors", n_monitors );
    delete[] monitors;
    monitors = new Monitor *[n_monitors];
    for( int i = 0; MYSQL_ROW dbrow = mysql_fetch_row( result ); i++ )
    {
        int col = 0;

        int id = atoi(dbrow[col]); col++;
        const char *name = dbrow[col]; col++;
        int function = atoi(dbrow[col]); col++;
        int enabled = atoi(dbrow[col]); col++;
        const char *linked_monitors = dbrow[col]; col++;

        const char *device = dbrow[col]; col++;
        int channel = atoi(dbrow[col]); col++;
        int format = atoi(dbrow[col]); col++;
        const char *method = dbrow[col]; col++;

        int width = atoi(dbrow[col]); col++;
        int height = atoi(dbrow[col]); col++;
        int palette = atoi(dbrow[col]); col++;
        Orientation orientation = (Orientation)atoi(dbrow[col]); col++;
        int brightness = atoi(dbrow[col]); col++;
        int contrast = atoi(dbrow[col]); col++;
        int hue = atoi(dbrow[col]); col++;
        int colour = atoi(dbrow[col]); col++;

        const char *event_prefix = dbrow[col]; col++;
        const char *label_format = dbrow[col]; col++;

        int label_x = atoi(dbrow[col]); col++;
        int label_y = atoi(dbrow[col]); col++;

        int image_buffer_count = atoi(dbrow[col]); col++;
        int warmup_count = atoi(dbrow[col]); col++;
        int pre_event_count = atoi(dbrow[col]); col++;
        int post_event_count = atoi(dbrow[col]); col++;
        int stream_replay_buffer = atoi(dbrow[col]); col++;
        int alarm_frame_count = atoi(dbrow[col]); col++;
        int section_length = atoi(dbrow[col]); col++;
        int frame_skip = atoi(dbrow[col]); col++;
        int capture_delay = (dbrow[col]&&atof(dbrow[col])>0.0)?int(DT_PREC_3/atof(dbrow[col])):0; col++;
        int alarm_capture_delay = (dbrow[col]&&atof(dbrow[col])>0.0)?int(DT_PREC_3/atof(dbrow[col])):0; col++;
        int fps_report_interval = atoi(dbrow[col]); col++;
        int ref_blend_perc = atoi(dbrow[col]); col++;
        int track_motion = atoi(dbrow[col]); col++;
        int signal_check_colour;
        if ( dbrow[col][0] == '#' )
            signal_check_colour = strtol(dbrow[col]+1,0,16);
        else
            signal_check_colour = strtol(dbrow[col],0,16);
        col++;

        int cam_width = ((orientation==ROTATE_90||orientation==ROTATE_270)?height:width);
        int cam_height = ((orientation==ROTATE_90||orientation==ROTATE_270)?width:height);

        Camera *camera = new LocalCamera(
            id,
            device,
            channel,
            format,
            method,
            cam_width,
            cam_height,
            palette,
            brightness,
            contrast,
            hue,
            colour,
            purpose==CAPTURE
        );

        monitors[i] = new Monitor(
            id,
            name,
            function,
            enabled,
            linked_monitors,
            camera,
            orientation,
            event_prefix,
            label_format,
            Coord( label_x, label_y ),
            image_buffer_count,
            warmup_count,
            pre_event_count,
            post_event_count,
            stream_replay_buffer,
            alarm_frame_count,
            section_length,
            frame_skip,
            capture_delay,
            alarm_capture_delay,
            fps_report_interval,
            ref_blend_perc,
            track_motion,
            signal_check_colour,
            purpose
        );
        Zone **zones = 0;
        int n_zones = Zone::Load( monitors[i], zones );
        monitors[i]->AddZones( n_zones, zones );
        Debug( 1, "Loaded monitor %d(%s), %d zones", id, name, n_zones );
    }
    if ( mysql_errno( &dbconn ) )
    {
        Error( "Can't fetch row: %s", mysql_error( &dbconn ) );
        exit( mysql_errno( &dbconn ) );
    }
    // Yadda yadda
    mysql_free_result( result );

    return( n_monitors );
}

int Monitor::LoadRemoteMonitors( const char *protocol, const char *host, const char *port, const char *path, Monitor **&monitors, Purpose purpose )
{
    static char sql[ZM_SQL_MED_BUFSIZ];
    if ( !protocol )
    {
        strncpy( sql, "select Id, Name, Function+0, Enabled, LinkedMonitors, Protocol, Method, Host, Port, Path, Width, Height, Palette, Orientation+0, Brightness, Contrast, Hue, Colour, EventPrefix, LabelFormat, LabelX, LabelY, ImageBufferCount, WarmupCount, PreEventCount, PostEventCount, StreamReplayBuffer, AlarmFrameCount, SectionLength, FrameSkip, MaxFPS, AlarmMaxFPS, FPSReportInterval, RefBlendPerc, TrackMotion from Monitors where Function != 'None' and Type = 'Remote'", sizeof(sql) );
    }
    else
    {
        snprintf( sql, sizeof(sql), "select Id, Name, Function+0, Enabled, LinkedMonitors, Protocol, Method, Host, Port, Path, Width, Height, Palette, Orientation+0, Brightness, Contrast, Hue, Colour, EventPrefix, LabelFormat, LabelX, LabelY, ImageBufferCount, WarmupCount, PreEventCount, PostEventCount, StreamReplayBuffer, AlarmFrameCount, SectionLength, FrameSkip, MaxFPS, AlarmMaxFPS, FPSReportInterval, RefBlendPerc, TrackMotion from Monitors where Function != 'None' and Type = 'Remote' and Protocol = '%s' and Host = '%s' and Port = '%s' and Path = '%s'", protocol, host, port, path );
    }
    if ( mysql_query( &dbconn, sql ) )
    {
        Error( "Can't run query: %s", mysql_error( &dbconn ) );
        exit( mysql_errno( &dbconn ) );
    }

    MYSQL_RES *result = mysql_store_result( &dbconn );
    if ( !result )
    {
        Error( "Can't use query result: %s", mysql_error( &dbconn ) );
        exit( mysql_errno( &dbconn ) );
    }
    int n_monitors = mysql_num_rows( result );
    Debug( 1, "Got %d monitors", n_monitors );
    delete[] monitors;
    monitors = new Monitor *[n_monitors];
    for( int i = 0; MYSQL_ROW dbrow = mysql_fetch_row( result ); i++ )
    {
        int col = 0;

        int id = atoi(dbrow[col]); col++;
        std::string name = dbrow[col]; col++;
        int function = atoi(dbrow[col]); col++;
        int enabled = atoi(dbrow[col]); col++;
        const char *linked_monitors = dbrow[col]; col++;

        std::string protocol = dbrow[col]; col++;
        std::string method = dbrow[col]; col++;
        std::string host = dbrow[col]; col++;
        std::string port = dbrow[col]; col++;
        std::string path = dbrow[col]; col++;

        int width = atoi(dbrow[col]); col++;
        int height = atoi(dbrow[col]); col++;
        int palette = atoi(dbrow[col]); col++;
        Orientation orientation = (Orientation)atoi(dbrow[col]); col++;
        int brightness = atoi(dbrow[col]); col++;
        int contrast = atoi(dbrow[col]); col++;
        int hue = atoi(dbrow[col]); col++;
        int colour = atoi(dbrow[col]); col++;

        std::string event_prefix = dbrow[col]; col++;
        std::string label_format = dbrow[col]; col++;

        int label_x = atoi(dbrow[col]); col++;
        int label_y = atoi(dbrow[col]); col++;

        int image_buffer_count = atoi(dbrow[col]); col++;
        int warmup_count = atoi(dbrow[col]); col++;
        int pre_event_count = atoi(dbrow[col]); col++;
        int post_event_count = atoi(dbrow[col]); col++;
        int stream_replay_buffer = atoi(dbrow[col]); col++;
        int alarm_frame_count = atoi(dbrow[col]); col++;
        int section_length = atoi(dbrow[col]); col++;
        int frame_skip = atoi(dbrow[col]); col++;
        int capture_delay = (dbrow[col]&&atof(dbrow[col])>0.0)?int(DT_PREC_3/atof(dbrow[col])):0; col++;
        int alarm_capture_delay = (dbrow[col]&&atof(dbrow[col])>0.0)?int(DT_PREC_3/atof(dbrow[col])):0; col++;
        int fps_report_interval = atoi(dbrow[col]); col++;
        int ref_blend_perc = atoi(dbrow[col]); col++;
        int track_motion = atoi(dbrow[col]); col++;

        int cam_width = ((orientation==ROTATE_90||orientation==ROTATE_270)?height:width);
        int cam_height = ((orientation==ROTATE_90||orientation==ROTATE_270)?width:height);

        Camera *camera = 0;
        if ( protocol == "http" )
        {
            camera = new RemoteCameraHttp(
                id,
                method,
                host, // Host
                port, // Port
                path, // Path
                cam_width,
                cam_height,
                palette,
                brightness,
                contrast,
                hue,
                colour,
                purpose==CAPTURE
            );
        }
#if HAVE_LIBAVFORMAT
        else if ( protocol == "rtsp" )
        {
            camera = new RemoteCameraRtsp(
                id,
                method,
                host, // Host
                port, // Port
                path, // Path
                cam_width,
                cam_height,
                palette,
                brightness,
                contrast,
                hue,
                colour,
                purpose==CAPTURE
            );
        }
#endif // HAVE_LIBAVFORMAT
        else
        {
            Fatal( "Unexpected remote camera protocol '%s'", protocol.c_str() );
        }

        monitors[i] = new Monitor(
            id,
            name.c_str(),
            function,
            enabled,
            linked_monitors,
            camera,
            orientation,
            event_prefix.c_str(),
            label_format.c_str(),
            Coord( label_x, label_y ),
            image_buffer_count,
            warmup_count,
            pre_event_count,
            post_event_count,
            stream_replay_buffer,
            alarm_frame_count,
            section_length,
            frame_skip,
            capture_delay,
            alarm_capture_delay,
            fps_report_interval,
            ref_blend_perc,
            track_motion,
            RGB_WHITE,
            purpose
        );
        Zone **zones = 0;
        int n_zones = Zone::Load( monitors[i], zones );
        monitors[i]->AddZones( n_zones, zones );
        Debug( 1, "Loaded monitor %d(%s), %d zones", id, name.c_str(), n_zones );
    }
    if ( mysql_errno( &dbconn ) )
    {
        Error( "Can't fetch row: %s", mysql_error( &dbconn ) );
        exit( mysql_errno( &dbconn ) );
    }
    // Yadda yadda
    mysql_free_result( result );

    return( n_monitors );
}

int Monitor::LoadFileMonitors( const char *file, Monitor **&monitors, Purpose purpose )
{
    static char sql[ZM_SQL_MED_BUFSIZ];
    if ( !file[0] )
    {
        strncpy( sql, "select Id, Name, Function+0, Enabled, LinkedMonitors, Path, Width, Height, Palette, Orientation+0, Brightness, Contrast, Hue, Colour, EventPrefix, LabelFormat, LabelX, LabelY, ImageBufferCount, WarmupCount, PreEventCount, PostEventCount, StreamReplayBuffer, AlarmFrameCount, SectionLength, FrameSkip, MaxFPS, AlarmMaxFPS, FPSReportInterval, RefBlendPerc, TrackMotion from Monitors where Function != 'None' and Type = 'File'", sizeof(sql) );
    }
    else
    {
        snprintf( sql, sizeof(sql), "select Id, Name, Function+0, Enabled, LinkedMonitors, Path, Width, Height, Palette, Orientation+0, Brightness, Contrast, Hue, Colour, EventPrefix, LabelFormat, LabelX, LabelY, ImageBufferCount, WarmupCount, PreEventCount, PostEventCount, StreamReplayBuffer, AlarmFrameCount, SectionLength, FrameSkip, MaxFPS, AlarmMaxFPS, FPSReportInterval, RefBlendPerc, TrackMotion from Monitors where Function != 'None' and Type = 'File' and Path = '%s'", file );
    }
    if ( mysql_query( &dbconn, sql ) )
    {
        Error( "Can't run query: %s", mysql_error( &dbconn ) );
        exit( mysql_errno( &dbconn ) );
    }

    MYSQL_RES *result = mysql_store_result( &dbconn );
    if ( !result )
    {
        Error( "Can't use query result: %s", mysql_error( &dbconn ) );
        exit( mysql_errno( &dbconn ) );
    }
    int n_monitors = mysql_num_rows( result );
    Debug( 1, "Got %d monitors", n_monitors );
    delete[] monitors;
    monitors = new Monitor *[n_monitors];
    for( int i = 0; MYSQL_ROW dbrow = mysql_fetch_row( result ); i++ )
    {
        int col = 0;

        int id = atoi(dbrow[col]); col++;
        const char *name = dbrow[col]; col++;
        int function = atoi(dbrow[col]); col++;
        int enabled = atoi(dbrow[col]); col++;
        const char *linked_monitors = dbrow[col]; col++;

        const char *path = dbrow[col]; col++;

        int width = atoi(dbrow[col]); col++;
        int height = atoi(dbrow[col]); col++;
        int palette = atoi(dbrow[col]); col++;
        Orientation orientation = (Orientation)atoi(dbrow[col]); col++;
        int brightness = atoi(dbrow[col]); col++;
        int contrast = atoi(dbrow[col]); col++;
        int hue = atoi(dbrow[col]); col++;
        int colour = atoi(dbrow[col]); col++;

        const char *event_prefix = dbrow[col]; col++;
        const char *label_format = dbrow[col]; col++;

        int label_x = atoi(dbrow[col]); col++;
        int label_y = atoi(dbrow[col]); col++;

        int image_buffer_count = atoi(dbrow[col]); col++;
        int warmup_count = atoi(dbrow[col]); col++;
        int pre_event_count = atoi(dbrow[col]); col++;
        int post_event_count = atoi(dbrow[col]); col++;
        int stream_replay_buffer = atoi(dbrow[col]); col++;
        int alarm_frame_count = atoi(dbrow[col]); col++;
        int section_length = atoi(dbrow[col]); col++;
        int frame_skip = atoi(dbrow[col]); col++;
        int capture_delay = (dbrow[col]&&atof(dbrow[col])>0.0)?int(DT_PREC_3/atof(dbrow[col])):0; col++;
        int alarm_capture_delay = (dbrow[col]&&atof(dbrow[col])>0.0)?int(DT_PREC_3/atof(dbrow[col])):0; col++;
        int fps_report_interval = atoi(dbrow[col]); col++;
        int ref_blend_perc = atoi(dbrow[col]); col++;
        int track_motion = atoi(dbrow[col]); col++;

        int cam_width = ((orientation==ROTATE_90||orientation==ROTATE_270)?height:width);
        int cam_height = ((orientation==ROTATE_90||orientation==ROTATE_270)?width:height);

        Camera *camera = new FileCamera(
            id,
            path, // File
            cam_width,
            cam_height,
            palette,
            brightness,
            contrast,
            hue,
            colour,
            purpose==CAPTURE
        );

        monitors[i] = new Monitor(
            id,
            name,
            function,
            enabled,
            linked_monitors,
            camera,
            orientation,
            event_prefix,
            label_format,
            Coord( label_x, label_y ),
            image_buffer_count,
            warmup_count,
            pre_event_count,
            post_event_count,
            stream_replay_buffer,
            alarm_frame_count,
            section_length,
            frame_skip,
            capture_delay,
            alarm_capture_delay,
            fps_report_interval,
            ref_blend_perc,
            track_motion,
            RGB_WHITE,
            purpose
        );
        Zone **zones = 0;
        int n_zones = Zone::Load( monitors[i], zones );
        monitors[i]->AddZones( n_zones, zones );
        Debug( 1, "Loaded monitor %d(%s), %d zones", id, name, n_zones );
    }
    if ( mysql_errno( &dbconn ) )
    {
        Error( "Can't fetch row: %s", mysql_error( &dbconn ) );
        exit( mysql_errno( &dbconn ) );
    }
    // Yadda yadda
    mysql_free_result( result );

    return( n_monitors );
}

#if HAVE_LIBAVFORMAT
int Monitor::LoadFfmpegMonitors( const char *file, Monitor **&monitors, Purpose purpose )
{
    static char sql[ZM_SQL_MED_BUFSIZ];
    if ( !file[0] )
    {
        strncpy( sql, "select Id, Name, Function+0, Enabled, LinkedMonitors, Path, Width, Height, Palette, Orientation+0, Brightness, Contrast, Hue, Colour, EventPrefix, LabelFormat, LabelX, LabelY, ImageBufferCount, WarmupCount, PreEventCount, PostEventCount, StreamReplayBuffer, AlarmFrameCount, SectionLength, FrameSkip, MaxFPS, AlarmMaxFPS, FPSReportInterval, RefBlendPerc, TrackMotion from Monitors where Function != 'None' and Type = 'File'", sizeof(sql) );
    }
    else
    {
        snprintf( sql, sizeof(sql), "select Id, Name, Function+0, Enabled, LinkedMonitors, Path, Width, Height, Palette, Orientation+0, Brightness, Contrast, Hue, Colour, EventPrefix, LabelFormat, LabelX, LabelY, ImageBufferCount, WarmupCount, PreEventCount, PostEventCount, StreamReplayBuffer, AlarmFrameCount, SectionLength, FrameSkip, MaxFPS, AlarmMaxFPS, FPSReportInterval, RefBlendPerc, TrackMotion from Monitors where Function != 'None' and Type = 'File' and Path = '%s'", file );
    }
    if ( mysql_query( &dbconn, sql ) )
    {
        Error( "Can't run query: %s", mysql_error( &dbconn ) );
        exit( mysql_errno( &dbconn ) );
    }

    MYSQL_RES *result = mysql_store_result( &dbconn );
    if ( !result )
    {
        Error( "Can't use query result: %s", mysql_error( &dbconn ) );
        exit( mysql_errno( &dbconn ) );
    }
    int n_monitors = mysql_num_rows( result );
    Debug( 1, "Got %d monitors", n_monitors );
    delete[] monitors;
    monitors = new Monitor *[n_monitors];
    for( int i = 0; MYSQL_ROW dbrow = mysql_fetch_row( result ); i++ )
    {
        int col = 0;

        int id = atoi(dbrow[col]); col++;
        const char *name = dbrow[col]; col++;
        int function = atoi(dbrow[col]); col++;
        int enabled = atoi(dbrow[col]); col++;
        const char *linked_monitors = dbrow[col]; col++;

        const char *path = dbrow[col]; col++;

        int width = atoi(dbrow[col]); col++;
        int height = atoi(dbrow[col]); col++;
        int palette = atoi(dbrow[col]); col++;
        Orientation orientation = (Orientation)atoi(dbrow[col]); col++;
        int brightness = atoi(dbrow[col]); col++;
        int contrast = atoi(dbrow[col]); col++;
        int hue = atoi(dbrow[col]); col++;
        int colour = atoi(dbrow[col]); col++;

        const char *event_prefix = dbrow[col]; col++;
        const char *label_format = dbrow[col]; col++;

        int label_x = atoi(dbrow[col]); col++;
        int label_y = atoi(dbrow[col]); col++;

        int image_buffer_count = atoi(dbrow[col]); col++;
        int warmup_count = atoi(dbrow[col]); col++;
        int pre_event_count = atoi(dbrow[col]); col++;
        int post_event_count = atoi(dbrow[col]); col++;
        int stream_replay_buffer = atoi(dbrow[col]); col++;
        int alarm_frame_count = atoi(dbrow[col]); col++;
        int section_length = atoi(dbrow[col]); col++;
        int frame_skip = atoi(dbrow[col]); col++;
        int capture_delay = (dbrow[col]&&atof(dbrow[col])>0.0)?int(DT_PREC_3/atof(dbrow[col])):0; col++;
        int alarm_capture_delay = (dbrow[col]&&atof(dbrow[col])>0.0)?int(DT_PREC_3/atof(dbrow[col])):0; col++;
        int fps_report_interval = atoi(dbrow[col]); col++;
        int ref_blend_perc = atoi(dbrow[col]); col++;
        int track_motion = atoi(dbrow[col]); col++;

        int cam_width = ((orientation==ROTATE_90||orientation==ROTATE_270)?height:width);
        int cam_height = ((orientation==ROTATE_90||orientation==ROTATE_270)?width:height);

        Camera *camera = new FfmpegCamera(
            id,
            path, // File
            cam_width,
            cam_height,
            palette,
            brightness,
            contrast,
            hue,
            colour,
            purpose==CAPTURE
        );

        monitors[i] = new Monitor(
            id,
            name,
            function,
            enabled,
            linked_monitors,
            camera,
            orientation,
            event_prefix,
            label_format,
            Coord( label_x, label_y ),
            image_buffer_count,
            warmup_count,
            pre_event_count,
            post_event_count,
            stream_replay_buffer,
            alarm_frame_count,
            section_length,
            frame_skip,
            capture_delay,
            alarm_capture_delay,
            fps_report_interval,
            ref_blend_perc,
            track_motion,
            RGB_WHITE,
            purpose
        );
        Zone **zones = 0;
        int n_zones = Zone::Load( monitors[i], zones );
        monitors[i]->AddZones( n_zones, zones );
        Debug( 1, "Loaded monitor %d(%s), %d zones", id, name, n_zones );
    }
    if ( mysql_errno( &dbconn ) )
    {
        Error( "Can't fetch row: %s", mysql_error( &dbconn ) );
        exit( mysql_errno( &dbconn ) );
    }
    // Yadda yadda
    mysql_free_result( result );

    return( n_monitors );
}
#endif // HAVE_LIBAVFORMAT

Monitor *Monitor::Load( int id, bool load_zones, Purpose purpose )
{
    static char sql[ZM_SQL_MED_BUFSIZ];
    snprintf( sql, sizeof(sql), "select Id, Name, Type, Function+0, Enabled, LinkedMonitors, Device, Channel, Format, Protocol, Method, Host, Port, Path, Width, Height, Palette, Orientation+0, Brightness, Contrast, Hue, Colour, EventPrefix, LabelFormat, LabelX, LabelY, ImageBufferCount, WarmupCount, PreEventCount, PostEventCount, StreamReplayBuffer, AlarmFrameCount, SectionLength, FrameSkip, MaxFPS, AlarmMaxFPS, FPSReportInterval, RefBlendPerc, TrackMotion, SignalCheckColour from Monitors where Id = %d", id );
    if ( mysql_query( &dbconn, sql ) )
    {
        Error( "Can't run query: %s", mysql_error( &dbconn ) );
        exit( mysql_errno( &dbconn ) );
    }

    MYSQL_RES *result = mysql_store_result( &dbconn );
    if ( !result )
    {
        Error( "Can't use query result: %s", mysql_error( &dbconn ) );
        exit( mysql_errno( &dbconn ) );
    }
    int n_monitors = mysql_num_rows( result );
    Debug( 1, "Got %d monitors", n_monitors );
    Monitor *monitor = 0;
    for( int i = 0; MYSQL_ROW dbrow = mysql_fetch_row( result ); i++ )
    {
        int col = 0;

        int id = atoi(dbrow[col]); col++;
        std::string name = dbrow[col]; col++;
        std::string type = dbrow[col]; col++;
        int function = atoi(dbrow[col]); col++;
        int enabled = atoi(dbrow[col]); col++;
        std::string linked_monitors = dbrow[col]; col++;

        std::string device = dbrow[col]; col++;
        int channel = atoi(dbrow[col]); col++;
        int format = atoi(dbrow[col]); col++;

        std::string protocol = dbrow[col]; col++;
        std::string method = dbrow[col]; col++;
        std::string host = dbrow[col]; col++;
        std::string port = dbrow[col]; col++;
        std::string path = dbrow[col]; col++;

        int width = atoi(dbrow[col]); col++;
        int height = atoi(dbrow[col]); col++;
        int palette = atoi(dbrow[col]); col++;
        Orientation orientation = (Orientation)atoi(dbrow[col]); col++;
        int brightness = atoi(dbrow[col]); col++;
        int contrast = atoi(dbrow[col]); col++;
        int hue = atoi(dbrow[col]); col++;
        int colour = atoi(dbrow[col]); col++;

        std::string event_prefix = dbrow[col]; col++;
        std::string label_format = dbrow[col]; col++;

        int label_x = atoi(dbrow[col]); col++;
        int label_y = atoi(dbrow[col]); col++;

        int image_buffer_count = atoi(dbrow[col]); col++;
        int warmup_count = atoi(dbrow[col]); col++;
        int pre_event_count = atoi(dbrow[col]); col++;
        int post_event_count = atoi(dbrow[col]); col++;
        int stream_replay_buffer = atoi(dbrow[col]); col++;
        int alarm_frame_count = atoi(dbrow[col]); col++;
        int section_length = atoi(dbrow[col]); col++;
        int frame_skip = atoi(dbrow[col]); col++;
        int capture_delay = (dbrow[col]&&atof(dbrow[col])>0.0)?int(DT_PREC_3/atof(dbrow[col])):0; col++;
        int alarm_capture_delay = (dbrow[col]&&atof(dbrow[col])>0.0)?int(DT_PREC_3/atof(dbrow[col])):0; col++;
        int fps_report_interval = atoi(dbrow[col]); col++;
        int ref_blend_perc = atoi(dbrow[col]); col++;
        int track_motion = atoi(dbrow[col]); col++;
        int signal_check_colour;
        if ( dbrow[col][0] == '#' )
            signal_check_colour = strtol(dbrow[col]+1,0,16);
        else
            signal_check_colour = strtol(dbrow[col],0,16);

        int cam_width = ((orientation==ROTATE_90||orientation==ROTATE_270)?height:width);
        int cam_height = ((orientation==ROTATE_90||orientation==ROTATE_270)?width:height);

        Camera *camera = 0;
        if ( type == "Local" )
        {
            camera = new LocalCamera(
                id,
                device.c_str(),
                channel,
                format,
                method,
                cam_width,
                cam_height,
                palette,
                brightness,
                contrast,
                hue,
                colour,
                purpose==CAPTURE
            );
        }
        else if ( type == "Remote" )
        {
            if ( protocol == "http" )
            {
                camera = new RemoteCameraHttp(
                    id,
                    method.c_str(),
                    host.c_str(),
                    port.c_str(),
                    path.c_str(),
                    cam_width,
                    cam_height,
                    palette,
                    brightness,
                    contrast,
                    hue,
                    colour,
                    purpose==CAPTURE
                );
            }
            else if ( protocol == "rtsp" )
            {
#if HAVE_LIBAVFORMAT
                camera = new RemoteCameraRtsp(
                    id,
                    method.c_str(),
                    host.c_str(),
                    port.c_str(),
                    path.c_str(),
                    cam_width,
                    cam_height,
                    palette,
                    brightness,
                    contrast,
                    hue,
                    colour,
                    purpose==CAPTURE
                );
#else // HAVE_LIBAVFORMAT
                Fatal( "You must have ffmpeg libraries installed to use remote camera protocol '%s' for monitor %d", protocol.c_str(), id );
#endif // HAVE_LIBAVFORMAT
            }
            else
            {
                Fatal( "Unexpected remote camera protocol '%s' for monitor %d", protocol.c_str(), id );
            }
        }
        else if ( type == "File" )
        {
            camera = new FileCamera(
                id,
                path.c_str(),
                cam_width,
                cam_height,
                palette,
                brightness,
                contrast,
                hue,
                colour,
                purpose==CAPTURE
            );
        }
#if HAVE_LIBAVFORMAT
        else if ( type == "Ffmpeg" )
        {
            camera = new FfmpegCamera(
                id,
                path.c_str(),
                cam_width,
                cam_height,
                palette,
                brightness,
                contrast,
                hue,
                colour,
                purpose==CAPTURE
            );
        }
#endif // HAVE_LIBAVFORMAT
        else
        {
            Fatal( "Bogus monitor type '%s' for monitor %d", type.c_str(), id );
        }
        monitor = new Monitor(
            id,
            name.c_str(),
            function,
            enabled,
            linked_monitors.c_str(),
            camera,
            orientation,
            event_prefix.c_str(),
            label_format.c_str(),
            Coord( label_x, label_y ),
            image_buffer_count,
            warmup_count,
            pre_event_count,
            post_event_count,
            stream_replay_buffer,
            alarm_frame_count,
            section_length,
            frame_skip,
            capture_delay,
            alarm_capture_delay,
            fps_report_interval,
            ref_blend_perc,
            track_motion,
            signal_check_colour,
            purpose
        );

        int n_zones = 0;
        if ( load_zones )
        {
            Zone **zones = 0;
            n_zones = Zone::Load( monitor, zones );
            monitor->AddZones( n_zones, zones );
        }
        Debug( 1, "Loaded monitor %d(%s), %d zones", id, name.c_str(), n_zones );
    }
    if ( mysql_errno( &dbconn ) )
    {
        Error( "Can't fetch row: %s", mysql_error( &dbconn ) );
        exit( mysql_errno( &dbconn ) );
    }
    // Yadda yadda
    mysql_free_result( result );

    return( monitor );
}

int Monitor::Capture()
{
    int captureResult = camera->Capture( image );
    if ( captureResult == 1 )
    {
        // Unable to capture image for temporary reason
        // Fake a signal loss image
        image.Fill( signal_check_colour );
        captureResult = 0;
    }
    if ( captureResult == 0 )
    {
        if ( orientation != ROTATE_0 )
        {
            switch ( orientation )
            {
                case ROTATE_0 :
                {
                    // No action required
                    break;
                }
                case ROTATE_90 :
                case ROTATE_180 :
                case ROTATE_270 :
                {
                    image.Rotate( (orientation-1)*90 );
                    break;
                }
                case FLIP_HORI :
                case FLIP_VERT :
                {
                    image.Flip( orientation==FLIP_HORI );
                    break;
                }
            }
        }

        if ( image.Size() != camera->ImageSize() )
        {
            Error( "Captured image does not match expected size, check width, height and colour depth" );
            return( -1 );
        }

        int index = image_count%image_buffer_count;
        if ( (index == shared_data->last_read_index) && (function > MONITOR) )
        {
            Warning( "Buffer overrun at index %d, image %d, slow down capture, speed up analysis or increase ring buffer size", index, image_count );
            time_t now = time(0);
            double approxFps = double(image_buffer_count)/double(now-image_buffer[index].timestamp->tv_sec);
            time_t last_read_delta = now - shared_data->last_read_time;
            if ( last_read_delta > (image_buffer_count/approxFps) )
            {
                Warning( "Last image read from shared memory %ld seconds ago, zma may have gone away", last_read_delta )
                shared_data->last_read_index = image_buffer_count;
            }
        }

        gettimeofday( image_buffer[index].timestamp, NULL );
        if ( config.timestamp_on_capture )
        {
            TimestampImage( &image, image_buffer[index].timestamp );
        }
        image_buffer[index].image->CopyBuffer( image );

        shared_data->signal = CheckSignal( &image );
        shared_data->last_write_index = index;
        shared_data->last_write_time = image_buffer[index].timestamp->tv_sec;

        image_count++;

        if ( image_count && !(image_count%fps_report_interval) )
        {
            time_t now = image_buffer[index].timestamp->tv_sec;
            fps = double(fps_report_interval)/(now-last_fps_time);
            //Info( "%d -> %d -> %d", fps_report_interval, now, last_fps_time );
            //Info( "%d -> %d -> %lf -> %lf", now-last_fps_time, fps_report_interval/(now-last_fps_time), double(fps_report_interval)/(now-last_fps_time), fps );
            Info( "%s: %d - Capturing at %.2lf fps", name, image_count, fps );
            last_fps_time = now;
        }

        if ( shared_data->action & GET_SETTINGS )
        {
            shared_data->brightness = camera->Brightness();
            shared_data->hue = camera->Hue();
            shared_data->colour = camera->Colour();
            shared_data->contrast = camera->Contrast();
            shared_data->action &= ~GET_SETTINGS;
        }
        if ( shared_data->action & SET_SETTINGS )
        {
            camera->Brightness( shared_data->brightness );
            camera->Hue( shared_data->hue );
            camera->Colour( shared_data->colour );
            camera->Contrast( shared_data->contrast );
            shared_data->action &= ~SET_SETTINGS;
        }
        return( 0 );
    }
    shared_data->signal = false;
    return( -1 );
}

void Monitor::TimestampImage( Image *ts_image, const struct timeval *ts_time ) const
{
    if ( label_format[0] )
    {
        // Expand the strftime macros first
        char label_time_text[256];
        strftime( label_time_text, sizeof(label_time_text), label_format, localtime( &ts_time->tv_sec ) );

        char label_text[1024];
        const char *s_ptr = label_time_text;
        char *d_ptr = label_text;
        while ( *s_ptr && ((d_ptr-label_text) < sizeof(label_text)) )
        {
            if ( *s_ptr == '%' )
            {
                bool found_macro = false;
                switch ( *(s_ptr+1) )
                {
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
                if ( found_macro )
                {
                    s_ptr += 2;
                    continue;
                }
            }
            *d_ptr++ = *s_ptr++;
        }
        *d_ptr = '\0';
        ts_image->Annotate( label_text, label_coord );
    }
}

bool Monitor::closeEvent()
{
    if ( event )
    {
        if ( function == RECORD || function == MOCORD )
        {
            gettimeofday( &(event->EndTime()), NULL );
        }
        delete event;
        event = 0;
        return( true );
    }
    return( false );
}

unsigned int Monitor::DetectMotion( const Image &comp_image, Event::StringSet &zoneSet )
{
    bool alarm = false;
    unsigned int score = 0;

    if ( n_zones <= 0 ) return( alarm );

    if ( config.record_diag_images )
    {
        static char diag_path[PATH_MAX] = "";
        if ( !diag_path[0] )
        {
            snprintf( diag_path, sizeof(diag_path), "%s/%d/diag-r.jpg", config.dir_events, id );
        }
        ref_image.WriteJpeg( diag_path );
    }

    Image *delta_image = ref_image.Delta( comp_image );

    if ( config.record_diag_images )
    {
        static char diag_path[PATH_MAX] = "";
        if ( !diag_path[0] )
        {
            snprintf( diag_path, sizeof(diag_path), "%s/%d/diag-d.jpg", config.dir_events, id );
        }
        delta_image->WriteJpeg( diag_path );
    }

    // Blank out all exclusion zones
    for ( int n_zone = 0; n_zone < n_zones; n_zone++ )
    {
        Zone *zone = zones[n_zone];
        zone->ClearAlarm();
        if ( !zone->IsInactive() )
        {
            continue;
        }
        Debug( 3, "Blanking inactive zone %s", zone->Label() );
        delta_image->Fill( RGB_BLACK, zone->GetPolygon() );
    }

    // Check preclusive zones first
    for ( int n_zone = 0; n_zone < n_zones; n_zone++ )
    {
        Zone *zone = zones[n_zone];
        if ( !zone->IsPreclusive() )
        {
            continue;
        }
        Debug( 3, "Checking preclusive zone %s", zone->Label() );
        if ( zone->CheckAlarms( delta_image ) )
        {
            alarm = true;
            score += zone->Score();
            Debug( 3, "Zone is alarmed, zone score = %d", zone->Score() );
            zoneSet.insert( zone->Label() );
            //zone->ResetStats();
        }
    }

    Coord alarm_centre;
    int top_score = -1;

    if ( alarm )
    {
        alarm = false;
        score = 0;
    }
    else
    {
        // Find all alarm pixels in active zones
        for ( int n_zone = 0; n_zone < n_zones; n_zone++ )
        {
            Zone *zone = zones[n_zone];
            if ( !zone->IsActive() )
            {
                continue;
            }
            Debug( 3, "Checking active zone %s", zone->Label() );
            if ( zone->CheckAlarms( delta_image ) )
            {
                alarm = true;
                score += zone->Score();
                zone->SetAlarm();
                Debug( 3, "Zone is alarmed, zone score = %d", zone->Score() );
                zoneSet.insert( zone->Label() );
                if ( config.opt_control && track_motion )
                {
                    if ( (int)zone->Score() > top_score )
                    {
                        top_score = zone->Score();
                        alarm_centre = zone->GetAlarmCentre();
                    }
                }
            }
        }

        if ( alarm )
        {
            for ( int n_zone = 0; n_zone < n_zones; n_zone++ )
            {
                Zone *zone = zones[n_zone];
                if ( !zone->IsInclusive() )
                {
                    continue;
                }
                Debug( 3, "Checking inclusive zone %s", zone->Label() );
                if ( zone->CheckAlarms( delta_image ) )
                {
                    alarm = true;
                    score += zone->Score();
                    zone->SetAlarm();
                    Debug( 3, "Zone is alarmed, zone score = %d", zone->Score() );
                    zoneSet.insert( zone->Label() );
                    if ( config.opt_control && track_motion )
                    {
                        if ( zone->Score() > top_score )
                        {
                            top_score = zone->Score();
                            alarm_centre = zone->GetAlarmCentre();
                        }
                    }
                }
            }
        }
        else
        {
            // Find all alarm pixels in exclusive zones
            for ( int n_zone = 0; n_zone < n_zones; n_zone++ )
            {
                Zone *zone = zones[n_zone];
                if ( !zone->IsExclusive() )
                {
                    continue;
                }
                Debug( 3, "Checking exclusive zone %s", zone->Label() );
                if ( zone->CheckAlarms( delta_image ) )
                {
                    alarm = true;
                    score += zone->Score();
                    zone->SetAlarm();
                    Debug( 3, "Zone is alarmed, zone score = %d", zone->Score() );
                    zoneSet.insert( zone->Label() );
                }
            }
        }
    }

    if ( top_score > 0 )
    {
        shared_data->alarm_x = alarm_centre.X();
        shared_data->alarm_y = alarm_centre.Y();

        Info( "Got alarm centre at %d,%d, at count %d", shared_data->alarm_x, shared_data->alarm_y, image_count );
    }
    else
    {
        shared_data->alarm_x = shared_data->alarm_y = -1;
    }

    delete delta_image;
    // This is a small and innocent hack to prevent scores of 0 being returned in alarm state
    return( score?score:alarm );
} 

bool Monitor::DumpSettings( char *output, bool verbose )
{
    output[0] = 0;

    sprintf( output+strlen(output), "Id : %d\n", id );
    sprintf( output+strlen(output), "Name : %s\n", name );
    sprintf( output+strlen(output), "Type : %s\n", camera->IsLocal()?"Local":(camera->IsRemote()?"Remote":"File") );
    if ( camera->IsLocal() )
    {
        sprintf( output+strlen(output), "Device : %s\n", ((LocalCamera *)camera)->Device().c_str() );
        sprintf( output+strlen(output), "Channel : %d\n", ((LocalCamera *)camera)->Channel() );
        sprintf( output+strlen(output), "Standard : %d\n", ((LocalCamera *)camera)->Standard() );
    }
    else if ( camera->IsRemote() )
    {
        sprintf( output+strlen(output), "Protocol : %s\n", ((RemoteCamera *)camera)->Protocol().c_str() );
        sprintf( output+strlen(output), "Host : %s\n", ((RemoteCamera *)camera)->Host().c_str() );
        sprintf( output+strlen(output), "Port : %s\n", ((RemoteCamera *)camera)->Port().c_str() );
        sprintf( output+strlen(output), "Path : %s\n", ((RemoteCamera *)camera)->Path().c_str() );
    }
    else if ( camera->IsFile() )
    {
        sprintf( output+strlen(output), "Path : %s\n", ((FileCamera *)camera)->Path() );
    }
#if HAVE_LIBAVFORMAT
    else if ( camera->IsFfmpeg() )
    {
        sprintf( output+strlen(output), "Path : %s\n", ((FfmpegCamera *)camera)->Path().c_str() );
    }
#endif // HAVE_LIBAVFORMAT
    sprintf( output+strlen(output), "Width : %d\n", camera->Width() );
    sprintf( output+strlen(output), "Height : %d\n", camera->Height() );
    if ( camera->IsLocal() )
    {
        sprintf( output+strlen(output), "Palette : %d\n", ((LocalCamera *)camera)->Palette() );
    }
    sprintf( output+strlen(output), "Colours : %d\n", camera->Colours() );
    sprintf( output+strlen(output), "Event Prefix : %s\n", event_prefix );
    sprintf( output+strlen(output), "Label Format : %s\n", label_format );
    sprintf( output+strlen(output), "Label Coord : %d,%d\n", label_coord.X(), label_coord.Y() );
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
    for ( int i = 0; i < n_zones; i++ )
    {
        zones[i]->DumpSettings( output+strlen(output), verbose );
    }
    return( true );
}

bool MonitorStream::checkSwapPath( const char *path, bool create_path )
{
    uid_t uid = getuid();
    gid_t gid = getgid();

    struct stat stat_buf;
    if ( stat( path, &stat_buf ) < 0 )
    {
        if ( create_path && errno == ENOENT )
        {
            Debug( 3, "Swap path '%s' missing, creating", path );
            if ( mkdir( path, 0755 ) )
            {
                Error( "Can't mkdir %s: %s", path, strerror(errno));
                return( false );
            }
            if ( stat( path, &stat_buf ) < 0 )
            {
                Error( "Can't stat '%s': %s", path, strerror(errno) );
                return( false );
            }
        }
        else
        {
            Error( "Can't stat '%s': %s", path, strerror(errno) );
            return( false );
        }
    }
    if ( !S_ISDIR(stat_buf.st_mode) )
    {
        Error( "Swap image path '%s' is not a directory", path );
        return( false );
    }

    mode_t mask = 0;
    if ( uid == stat_buf.st_uid )
    {
        // If we are the owner
        mask = 00700;
    }
    else if ( gid == stat_buf.st_gid )
    {
        // If we are in the owner group
        mask = 00070;
    }
    else
    {
        // We are neither the owner nor in the group
        mask = 00007;
    }

    if ( (stat_buf.st_mode & mask) != mask )
    {
        Error( "Insufficient permissions on swap image path '%s'", path );
        return( false );
    }
    return( true );
}

void MonitorStream::processCommand( const CmdMsg *msg )
{
    Debug( 2, "Got message, type %d, msg %d", msg->msg_type, msg->msg_data[0] );
    // Check for incoming command
    switch( (MsgCommand)msg->msg_data[0] )
    {
        case CMD_PAUSE :
        {
            Debug( 1, "Got PAUSE command" );

            // Set paused flag
            paused = true;
            // Set delayed flag
            delayed = true;
            last_frame_sent = TV_2_FLOAT( now );
            break;
        }
        case CMD_PLAY :
        {
            Debug( 1, "Got PLAY command" );
            if ( paused )
            {
                // Clear paused flag
                paused = false;
                // Set delayed_play flag
                delayed = true;
            }
            replay_rate = ZM_RATE_BASE;
            break;
        }
        case CMD_VARPLAY :
        {
            Debug( 1, "Got VARPLAY command" );
            if ( paused )
            {
                // Clear paused flag
                paused = false;
                // Set delayed_play flag
                delayed = true;
            }
            replay_rate = ntohs(((unsigned char)msg->msg_data[2]<<8)|(unsigned char)msg->msg_data[1])-32768;
            break;
        }
        case CMD_STOP :
        {
            Debug( 1, "Got STOP command" );

            // Clear paused flag
            paused = false;
            // Clear delayed_play flag
            delayed = false;
            break;
        }
        case CMD_FASTFWD :
        {
            Debug( 1, "Got FAST FWD command" );
            if ( paused )
            {
                // Clear paused flag
                paused = false;
                // Set delayed_play flag
                delayed = true;
            }
            // Set play rate
            switch ( replay_rate )
            {
                case 2 * ZM_RATE_BASE :
                    replay_rate = 5 * ZM_RATE_BASE;
                    break;
                case 5 * ZM_RATE_BASE :
                    replay_rate = 10 * ZM_RATE_BASE;
                    break;
                case 10 * ZM_RATE_BASE :
                    replay_rate = 25 * ZM_RATE_BASE;
                    break;
                case 25 * ZM_RATE_BASE :
                case 50 * ZM_RATE_BASE :
                    replay_rate = 50 * ZM_RATE_BASE;
                    break;
                default :
                    replay_rate = 2 * ZM_RATE_BASE;
                    break;
            }
            break;
        }
        case CMD_SLOWFWD :
        {
            Debug( 1, "Got SLOW FWD command" );
            // Set paused flag
            paused = true;
            // Set delayed flag
            delayed = true;
            // Set play rate
            replay_rate = ZM_RATE_BASE;
            // Set step
            step = 1;
            break;
        }
        case CMD_SLOWREV :
        {
            Debug( 1, "Got SLOW REV command" );
            // Set paused flag
            paused = true;
            // Set delayed flag
            delayed = true;
            // Set play rate
            replay_rate = ZM_RATE_BASE;
            // Set step
            step = -1;
            break;
        }
        case CMD_FASTREV :
        {
            Debug( 1, "Got FAST REV command" );
            if ( paused )
            {
                // Clear paused flag
                paused = false;
                // Set delayed_play flag
                delayed = true;
            }
            // Set play rate
            switch ( replay_rate )
            {
                case -2 * ZM_RATE_BASE :
                    replay_rate = -5 * ZM_RATE_BASE;
                    break;
                case -5 * ZM_RATE_BASE :
                    replay_rate = -10 * ZM_RATE_BASE;
                    break;
                case -10 * ZM_RATE_BASE :
                    replay_rate = -25 * ZM_RATE_BASE;
                    break;
                case -25 * ZM_RATE_BASE :
                case -50 * ZM_RATE_BASE :
                    replay_rate = -50 * ZM_RATE_BASE;
                    break;
                default :
                    replay_rate = -2 * ZM_RATE_BASE;
                    break;
            }
            break;
        }
        case CMD_ZOOMIN :
        {
            x = ((unsigned char)msg->msg_data[1]<<8)|(unsigned char)msg->msg_data[2];
            y = ((unsigned char)msg->msg_data[3]<<8)|(unsigned char)msg->msg_data[4];
            Debug( 1, "Got ZOOM IN command, to %d,%d", x, y );
            switch ( zoom )
            {
                case 100:
                    zoom = 150;
                    break;
                case 150:
                    zoom = 200;
                    break;
                case 200:
                    zoom = 300;
                    break;
                case 300:
                    zoom = 400;
                    break;
                case 400:
                default :
                    zoom = 500;
                    break;
            }
            break;
        }
        case CMD_ZOOMOUT :
        {
            Debug( 1, "Got ZOOM OUT command" );
            switch ( zoom )
            {
                case 500:
                    zoom = 400;
                    break;
                case 400:
                    zoom = 300;
                    break;
                case 300:
                    zoom = 200;
                    break;
                case 200:
                    zoom = 150;
                    break;
                case 150:
                default :
                    zoom = 100;
                    break;
            }
            break;
        }
        case CMD_PAN :
        {
            x = ((unsigned char)msg->msg_data[1]<<8)|(unsigned char)msg->msg_data[2];
            y = ((unsigned char)msg->msg_data[3]<<8)|(unsigned char)msg->msg_data[4];
            Debug( 1, "Got PAN command, to %d,%d", x, y );
            break;
        }
        case CMD_SCALE :
        {
            scale = ((unsigned char)msg->msg_data[1]<<8)|(unsigned char)msg->msg_data[2];
            Debug( 1, "Got SCALE command, to %d", scale );
            break;
        }
        case CMD_QUERY :
        {
            Debug( 1, "Got QUERY command, sending STATUS" );
            break;
        }
        default :
        {
            Error( "Got unexpected command %d", msg->msg_data[0] );
            break;
        }
    }

    struct {
        int id;
        int state;
        double fps;
        int buffer_level;
        int rate;
        double delay;
        int zoom;
        bool delayed;
        bool paused;
        bool enabled;
        bool forced;
    } status_data;

    status_data.id = monitor->Id();
    status_data.fps = monitor->GetFPS();
    status_data.state = monitor->shared_data->state;
    if ( playback_buffer > 0 )
        status_data.buffer_level = (MOD_ADD( (temp_write_index-temp_read_index), 0, temp_image_buffer_count )*100)/temp_image_buffer_count;
    else
        status_data.buffer_level = 0;
    status_data.delayed = delayed;
    status_data.paused = paused;
    status_data.rate = replay_rate;
    status_data.delay = TV_2_FLOAT( now ) - TV_2_FLOAT( last_frame_timestamp );
    status_data.zoom = zoom;
    //status_data.enabled = monitor->shared_data->active;
    status_data.enabled = monitor->trigger_data->trigger_state!=Monitor::TRIGGER_OFF;
    status_data.forced = monitor->trigger_data->trigger_state==Monitor::TRIGGER_ON;
    Debug( 2, "L:%d, D:%d, P:%d, R:%d, d:%.3f, Z:%d, E:%d F:%d", 
        status_data.buffer_level,
        status_data.delayed,
        status_data.paused,
        status_data.rate,
        status_data.delay,
        status_data.zoom,
        status_data.enabled,
        status_data.forced
    );

    DataMsg status_msg;
    status_msg.msg_type = MSG_DATA_WATCH;
    memcpy( &status_msg.msg_data, &status_data, sizeof(status_msg.msg_data) );
    int nbytes = 0;
    if ( (nbytes = sendto( sd, &status_msg, sizeof(status_msg), MSG_DONTWAIT, (sockaddr *)&rem_addr, sizeof(rem_addr) )) < 0 )
    {
        //if ( errno != EAGAIN )
        {
            Error( "Can't sendto on sd %d: %s", sd, strerror(errno) );
            //exit( -1 );
        }
    }

    updateFrameRate( monitor->GetFPS() );
}

bool MonitorStream::sendFrame( const char *filepath, struct timeval *timestamp )
{
    bool send_raw = ((scale>=ZM_SCALE_BASE)&&(zoom==ZM_SCALE_BASE));

    if ( type != STREAM_JPEG )
        send_raw = false;
    if ( !config.timestamp_on_capture && timestamp )
        send_raw = false;

    if ( !send_raw )
    {
        Image temp_image( filepath );

        return( sendFrame( &temp_image, timestamp ) );
    }
    else
    {
        int img_buffer_size = 0;
        static unsigned char img_buffer[ZM_MAX_IMAGE_SIZE];

        FILE *fdj = NULL;
        if ( (fdj = fopen( filepath, "r" )) )
        {
            img_buffer_size = fread( img_buffer, 1, sizeof(img_buffer), fdj );
            fclose( fdj );
        }
        else
        {
            Error( "Can't open %s: %s", filepath, strerror(errno) );
            return( false );
        }

        // Calculate how long it takes to actually send the frame
        struct timeval frameStartTime;
        gettimeofday( &frameStartTime, NULL );
        
        fprintf( stdout, "--ZoneMinderFrame\r\n" );
        fprintf( stdout, "Content-Length: %d\r\n", img_buffer_size );
        fprintf( stdout, "Content-Type: image/jpeg\r\n\r\n" );
        if ( fwrite( img_buffer, img_buffer_size, 1, stdout ) != 1 )
        {
            if ( !zm_terminate )
                Error( "Unable to send stream frame: %s", strerror(errno) );
            return( false );
        }
        fprintf( stdout, "\r\n\r\n" );
        fflush( stdout );

        struct timeval frameEndTime;
        gettimeofday( &frameEndTime, NULL );

        int frameSendTime = tvDiffMsec( frameStartTime, frameEndTime );
        if ( frameSendTime > 1000/maxfps )
        {
            maxfps /= 2;
            Error( "Frame send time %d msec too slow, throttling maxfps to %.2f", frameSendTime, maxfps );
        }

        last_frame_sent = TV_2_FLOAT( now );

        return( true );
    }
    return( false );
}

bool MonitorStream::sendFrame( Image *image, struct timeval *timestamp )
{
    Image *send_image = prepareImage( image );
    if ( !config.timestamp_on_capture && timestamp )
        monitor->TimestampImage( send_image, timestamp );

#if HAVE_LIBAVCODEC
    if ( type == STREAM_MPEG )
    {
        if ( !vid_stream )
        {
            vid_stream = new VideoStream( "pipe:", format, bitrate, effective_fps, send_image->Colours(), send_image->Width(), send_image->Height() );
            fprintf( stdout, "Content-type: %s\r\n\r\n", vid_stream->MimeType() );
            vid_stream->OpenStream();
        }
        static struct timeval base_time;
        struct DeltaTimeval delta_time;
        if ( !frame_count )
            base_time = *timestamp;
        DELTA_TIMEVAL( delta_time, *timestamp, base_time, DT_PREC_3 );
        /* double pts = */ vid_stream->EncodeFrame( send_image->Buffer(), send_image->Size(), config.mpeg_timed_frames, delta_time.delta );
    }
    else
#endif // HAVE_LIBAVCODEC
    {
        static unsigned char temp_img_buffer[ZM_MAX_IMAGE_SIZE];

        int img_buffer_size = 0;
        unsigned char *img_buffer = temp_img_buffer;

        // Calculate how long it takes to actually send the frame
        struct timeval frameStartTime;
        gettimeofday( &frameStartTime, NULL );
        
        fprintf( stdout, "--ZoneMinderFrame\r\n" );
        switch( type )
        {
            case STREAM_JPEG :
                send_image->EncodeJpeg( img_buffer, &img_buffer_size );
                fprintf( stdout, "Content-Type: image/jpeg\r\n" );
                break;
            case STREAM_RAW :
                fprintf( stdout, "Content-Type: image/x-rgb\r\n" );
                img_buffer = send_image->Buffer();
                img_buffer_size = send_image->Size();
                break;
            case STREAM_ZIP :
                fprintf( stdout, "Content-Type: image/x-rgbz\r\n" );
                unsigned long zip_buffer_size;
                send_image->Zip( img_buffer, &zip_buffer_size );
                img_buffer_size = zip_buffer_size;
                break;
            default :
                Fatal( "Unexpected frame type %d", type );
                break;
        }
        fprintf( stdout, "Content-Length: %d\r\n\r\n", img_buffer_size );
        if ( fwrite( img_buffer, img_buffer_size, 1, stdout ) != 1 )
        {
            if ( !zm_terminate )
                Error( "Unable to send stream frame: %s", strerror(errno) );
            return( false );
        }
        fprintf( stdout, "\r\n\r\n" );
        fflush( stdout );

        struct timeval frameEndTime;
        gettimeofday( &frameEndTime, NULL );

        int frameSendTime = tvDiffMsec( frameStartTime, frameEndTime );
        if ( frameSendTime > 1000/maxfps )
        {
            maxfps /= 1.5;
            Error( "Frame send time %d msec too slow, throttling maxfps to %.2f", frameSendTime, maxfps );
        }
    }
    last_frame_sent = TV_2_FLOAT( now );
    return( true );
}

void MonitorStream::runStream()
{
    if ( type == STREAM_SINGLE )
    {
        // Not yet migrated over to stream class
        monitor->SingleImage( scale );
        return;
    }

    openComms();

    checkInitialised();

    updateFrameRate( monitor->GetFPS() );

    if ( type == STREAM_JPEG )
        fprintf( stdout, "Content-Type: multipart/x-mixed-replace;boundary=ZoneMinderFrame\r\n\r\n" );

    int last_read_index = monitor->image_buffer_count;

    time_t stream_start_time;
    time( &stream_start_time );

    frame_count = 0;

    temp_image_buffer = 0;
    temp_image_buffer_count = playback_buffer;
    temp_read_index = temp_image_buffer_count;
    temp_write_index = temp_image_buffer_count;

    char swap_path[PATH_MAX] = "";
    bool buffered_playback = false;

    if ( connkey && playback_buffer > 0 )
    {
        Debug( 2, "Checking swap image location" );
        Debug( 3, "Checking swap image path" );
        strncpy( swap_path, config.path_swap, sizeof(swap_path) );
        if ( checkSwapPath( swap_path, false ) )
        {
            snprintf( &(swap_path[strlen(swap_path)]), sizeof(swap_path)-strlen(swap_path), "/zmswap-m%d", monitor->Id() );
            if ( checkSwapPath( swap_path, true ) )
            {
                snprintf( &(swap_path[strlen(swap_path)]), sizeof(swap_path)-strlen(swap_path), "/zmswap-q%06d", connkey );
                if ( checkSwapPath( swap_path, true ) )
                {
                    buffered_playback = true;
                }
            }
        }

        if ( !buffered_playback )
        {
            Error( "Unable to validate swap image path, disabling buffered playback" );
        }
        else
        {
            Debug( 2, "Assigning temporary buffer" );
            temp_image_buffer = new SwapImage[temp_image_buffer_count];
            memset( temp_image_buffer, 0, sizeof(*temp_image_buffer)*temp_image_buffer_count );
            Debug( 2, "Assigned temporary buffer" );
        }
    }

    float max_secs_since_last_sent_frame = 10.0; //should be > keep alive amount (5 secs)
    while ( !zm_terminate )
    {
        bool got_command = false;
        if ( feof( stdout ) || ferror( stdout ) )
        {
            break;
        }

        gettimeofday( &now, NULL );

        if ( connkey )
        {
            got_command = checkCommandQueue();
        }

        bool frame_sent = false;
        if ( buffered_playback && delayed )
        {
            if ( temp_read_index == temp_write_index )
            {
                // Go back to live viewing
                Debug( 1, "Exceeded temporary streaming buffer" );
                // Clear paused flag
                paused = false;
                // Clear delayed_play flag
                delayed = false;
                replay_rate = ZM_RATE_BASE;
            }
            else
            {
                if ( !paused )
                {
                    int temp_index = MOD_ADD( temp_read_index, 0, temp_image_buffer_count );
                    //Debug( 3, "tri: %d, ti: %d", temp_read_index, temp_index );
                    SwapImage *swap_image = &temp_image_buffer[temp_index];

                    if ( !swap_image->valid )
                    {
                        paused = true;
                        delayed = true;
                        temp_read_index = MOD_ADD( temp_read_index, (replay_rate>=0?-1:1), temp_image_buffer_count );
                    }
                    else
                    {
                        //Debug( 3, "siT: %f, lfT: %f", TV_2_FLOAT( swap_image->timestamp ), TV_2_FLOAT( last_frame_timestamp ) );
                        double expected_delta_time = ((TV_2_FLOAT( swap_image->timestamp ) - TV_2_FLOAT( last_frame_timestamp )) * ZM_RATE_BASE)/replay_rate;
                        double actual_delta_time = TV_2_FLOAT( now ) - last_frame_sent;

                        //Debug( 3, "eDT: %.3lf, aDT: %.3f, lFS:%.3f, NOW:%.3f", expected_delta_time, actual_delta_time, last_frame_sent, TV_2_FLOAT( now ) );
                        // If the next frame is due
                        if ( actual_delta_time > expected_delta_time )
                        {
                            //Debug( 2, "eDT: %.3lf, aDT: %.3f", expected_delta_time, actual_delta_time );
                            if ( temp_index%frame_mod == 0 )
                            {
                                Debug( 2, "Sending delayed frame %d", temp_index );
                                // Send the next frame
                                if ( !sendFrame( temp_image_buffer[temp_index].file_name, &temp_image_buffer[temp_index].timestamp ) )
                                    zm_terminate = true;
                                memcpy( &last_frame_timestamp, &(swap_image->timestamp), sizeof(last_frame_timestamp) );
                                frame_sent = true;
                            }
                            temp_read_index = MOD_ADD( temp_read_index, (replay_rate>0?1:-1), temp_image_buffer_count );
                        }
                    }
                }
                else if ( step != 0 )
                {
                    temp_read_index = MOD_ADD( temp_read_index, (step>0?1:-1), temp_image_buffer_count );

                    SwapImage *swap_image = &temp_image_buffer[temp_read_index];

                    // Send the next frame
                    if ( !sendFrame( temp_image_buffer[temp_read_index].file_name, &temp_image_buffer[temp_read_index].timestamp ) )
                        zm_terminate = true;
                    memcpy( &last_frame_timestamp, &(swap_image->timestamp), sizeof(last_frame_timestamp) );
                    frame_sent = true;
                    step = 0;
                }
                else
                {
                    int temp_index = MOD_ADD( temp_read_index, 0, temp_image_buffer_count );

                     double actual_delta_time = TV_2_FLOAT( now ) - last_frame_sent;
                     if ( got_command || actual_delta_time > 5 )
                     {
                        // Send keepalive
                        Debug( 2, "Sending keepalive frame %d", temp_index );
                        // Send the next frame
                        if ( !sendFrame( temp_image_buffer[temp_index].file_name, &temp_image_buffer[temp_index].timestamp ) )
                            zm_terminate = true;
                        frame_sent = true;
                    }
                }
            }
            if ( temp_read_index == temp_write_index )
            {
                // Go back to live viewing
                Warning( "Rewound over write index, resuming live play" );
                // Clear paused flag
                paused = false;
                // Clear delayed_play flag
                delayed = false;
                replay_rate = ZM_RATE_BASE;
            }
        }
        if ( last_read_index != monitor->shared_data->last_write_index )
        {
            int index = monitor->shared_data->last_write_index%monitor->image_buffer_count;
            last_read_index = monitor->shared_data->last_write_index;
            //Debug( 1, "%d: %x - %x", index, image_buffer[index].image, image_buffer[index].image->buffer );
            if ( (frame_mod == 1) || ((frame_count%frame_mod) == 0) )
            {
                if ( !paused && !delayed )
                {
                    // Send the next frame
                    Monitor::Snapshot *snap = &monitor->image_buffer[index];

                    if ( !sendFrame( snap->image, snap->timestamp ) )
                        zm_terminate = true;
                    memcpy( &last_frame_timestamp, snap->timestamp, sizeof(last_frame_timestamp) );
                    frame_sent = true;

                    temp_read_index = temp_write_index;
                }
            }
            if ( buffered_playback )
            {
                if ( monitor->shared_data->valid )
                {
                    if ( monitor->image_buffer[index].timestamp->tv_sec )
                    {
                        int temp_index = temp_write_index%temp_image_buffer_count;
                        Debug( 2, "Storing frame %d", temp_index );
                        if ( !temp_image_buffer[temp_index].valid )
                        {
                            snprintf( temp_image_buffer[temp_index].file_name, sizeof(temp_image_buffer[0].file_name), "%s/zmswap-i%05d.jpg", swap_path, temp_index );
                            temp_image_buffer[temp_index].valid = true;
                        }
                        memcpy( &(temp_image_buffer[temp_index].timestamp), monitor->image_buffer[index].timestamp, sizeof(temp_image_buffer[0].timestamp) );
                        monitor->image_buffer[index].image->WriteJpeg( temp_image_buffer[temp_index].file_name, config.jpeg_file_quality );
                        temp_write_index = MOD_ADD( temp_write_index, 1, temp_image_buffer_count );
                        if ( temp_write_index == temp_read_index )
                        {
                            // Go back to live viewing
                            Warning( "Exceeded temporary buffer, resuming live play" );
                            // Clear paused flag
                            paused = false;
                            // Clear delayed_play flag
                            delayed = false;
                            replay_rate = ZM_RATE_BASE;
                        }
                    }
                    else
                    {
                        Warning( "Unable to store frame as timestamp invalid" );
                    }
                }
                else
                {
                    Warning( "Unable to store frame as shared memory invalid" );
                }
            }
            frame_count++;
        }
        usleep( (unsigned long)((1000000 * ZM_RATE_BASE)/((base_fps?base_fps:1)*abs(replay_rate*2))) );
        if ( ttl )
        {
            if ( (now.tv_sec - stream_start_time) > ttl )
            {
                break;
            }
        }
        if ( (TV_2_FLOAT( now ) - last_frame_sent) > max_secs_since_last_sent_frame )
        {
            Error( "Terminating, last frame sent time %f secs more than maximum of %f", TV_2_FLOAT( now ) - last_frame_sent, max_secs_since_last_sent_frame );
            break;
        }
    }
    if ( buffered_playback )
    {
        char swap_path[PATH_MAX] = "";

        snprintf( swap_path, sizeof(swap_path), "%s/zmswap-m%d/zmswap-q%06d", config.path_swap, monitor->Id(), connkey );
        Debug( 1, "Cleaning swap files from %s", swap_path );
        struct stat stat_buf;
        if ( stat( swap_path, &stat_buf ) < 0 )
        {
            if ( errno != ENOENT )
            {
                Error( "Can't stat '%s': %s", swap_path, strerror(errno) );
            }
        }
        else if ( !S_ISDIR(stat_buf.st_mode) )
        {
            Error( "Swap image path '%s' is not a directory", swap_path );
        }
        else
        {
            char glob_pattern[PATH_MAX] = "";

            snprintf( glob_pattern, sizeof(glob_pattern), "%s/*.*", swap_path );
            glob_t pglob;
            int glob_status = glob( glob_pattern, 0, 0, &pglob );
            if ( glob_status != 0 )
            {
                if ( glob_status < 0 )
                {
                    Error( "Can't glob '%s': %s", glob_pattern, strerror(errno) );
                }
                else
                {
                    Debug( 1, "Can't glob '%s': %d", glob_pattern, glob_status );
                }
            }
            else
            {
                for ( int i = 0; i < pglob.gl_pathc; i++ )
                {
                    if ( unlink( pglob.gl_pathv[i] ) < 0 )
                    {
                        Error( "Can't unlink '%s': %s", pglob.gl_pathv[i], strerror(errno) );
                    }
                }
            }
            globfree( &pglob );
            if ( rmdir( swap_path ) < 0 )
            {
                Error( "Can't rmdir '%s': %s", swap_path, strerror(errno) );
            }
        }
    }
    closeComms();
}

void Monitor::SingleImage( int scale)
{
    int img_buffer_size = 0;
    static JOCTET img_buffer[ZM_MAX_IMAGE_SIZE];
    Image scaled_image;
    int index = shared_data->last_write_index%image_buffer_count;
    Snapshot *snap = &image_buffer[index];
    Image *snap_image = snap->image;

    if ( scale != ZM_SCALE_BASE )
    {
        scaled_image.Assign( *snap_image );
        scaled_image.Scale( scale );
        snap_image = &scaled_image;
    }
    if ( !config.timestamp_on_capture )
    {
        TimestampImage( snap_image, snap->timestamp );
    }
    snap_image->EncodeJpeg( img_buffer, &img_buffer_size );
    
    fprintf( stdout, "Content-Length: %d\r\n", img_buffer_size );
    fprintf( stdout, "Content-Type: image/jpeg\r\n\r\n" );
    fwrite( img_buffer, img_buffer_size, 1, stdout );
}

void Monitor::SingleImageRaw( int scale)
{
    Image scaled_image;
    int index = shared_data->last_write_index%image_buffer_count;
    Snapshot *snap = &image_buffer[index];
    Image *snap_image = snap->image;

    if ( scale != ZM_SCALE_BASE )
    {
        scaled_image.Assign( *snap_image );
        scaled_image.Scale( scale );
        snap_image = &scaled_image;
    }
    if ( !config.timestamp_on_capture )
    {
        TimestampImage( snap_image, snap->timestamp );
    }
    
    fprintf( stdout, "Content-Length: %d\r\n", snap_image->Size() );
    fprintf( stdout, "Content-Type: image/x-rgb\r\n\r\n" );
    fwrite( snap_image->Buffer(), snap_image->Size(), 1, stdout );
}

void Monitor::SingleImageZip( int scale)
{
    unsigned long img_buffer_size = 0;
    static Bytef img_buffer[ZM_MAX_IMAGE_SIZE];
    Image scaled_image;
    int index = shared_data->last_write_index%image_buffer_count;
    Snapshot *snap = &image_buffer[index];
    Image *snap_image = snap->image;

    if ( scale != ZM_SCALE_BASE )
    {
        scaled_image.Assign( *snap_image );
        scaled_image.Scale( scale );
        snap_image = &scaled_image;
    }
    if ( !config.timestamp_on_capture )
    {
        TimestampImage( snap_image, snap->timestamp );
    }
    snap_image->Zip( img_buffer, &img_buffer_size );
    
    fprintf( stdout, "Content-Length: %ld\r\n", img_buffer_size );
    fprintf( stdout, "Content-Type: image/x-rgbz\r\n\r\n" );
    fwrite( img_buffer, img_buffer_size, 1, stdout );
}
