//
// ZoneMinder Event Class Implementation, $Date$, $Revision$
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

#include <fcntl.h>
#include <sys/socket.h>
#include <arpa/inet.h>
#include <sys/un.h>
#include <sys/uio.h>
#include <sys/ipc.h>
#include <sys/msg.h>
#include <getopt.h>
#include <arpa/inet.h>
#include <glob.h>

#include "zm.h"
#include "zm_db.h"
#include "zm_time.h"
#include "zm_mpeg.h"
#include "zm_signal.h"
#include "zm_event.h"
#include "zm_monitor.h"

// sendfile tricks
extern "C"
{
#include "zm_sendfile.h"
}

#include "zmf.h"

#if HAVE_SYS_SENDFILE_H
#include <sys/sendfile.h>
#endif

//#define USE_PREPARED_SQL 1

bool Event::initialised = false;
char Event::capture_file_format[PATH_MAX];
char Event::analyse_file_format[PATH_MAX];
char Event::general_file_format[PATH_MAX];

int Event::pre_alarm_count = 0;
Event::PreAlarmData Event::pre_alarm_data[MAX_PRE_ALARM_FRAMES] = { { 0 } };

Event::Event( Monitor *p_monitor, struct timeval p_start_time, const std::string &p_cause, const StringSetMap &p_noteSetMap ) :
    monitor( p_monitor ),
    start_time( p_start_time ),
    cause( p_cause ),
    noteSetMap( p_noteSetMap )
{
    if ( !initialised )
        Initialise();

    std::string notes;
    createNotes( notes );

    bool untimedEvent = false;
    if ( !start_time.tv_sec )
    {
        untimedEvent = true;
        gettimeofday( &start_time, 0 );
    }

    static char sql[ZM_SQL_MED_BUFSIZ];

    struct tm *stime = localtime( &start_time.tv_sec );
    snprintf( sql, sizeof(sql), "insert into Events ( MonitorId, Name, StartTime, Width, Height, Cause, Notes ) values ( %d, 'New Event', from_unixtime( %ld ), %d, %d, '%s', '%s' )", monitor->Id(), start_time.tv_sec, monitor->Width(), monitor->Height(), cause.c_str(), notes.c_str() );
    if ( mysql_query( &dbconn, sql ) )
    {
        Error( "Can't insert event: %s", mysql_error( &dbconn ) );
        exit( mysql_errno( &dbconn ) );
    }
    id = mysql_insert_id( &dbconn );
    if ( untimedEvent )
    {
        Warning( "Event %d has zero time, setting to current", id );
    }
    end_time.tv_sec = 0;
    frames = 0;
    alarm_frames = 0;
    tot_score = 0;
    max_score = 0;

    if ( config.use_deep_storage )
    {
        char *path_ptr = path;
        path_ptr += snprintf( path_ptr, sizeof(path), "%s/%d", config.dir_events, monitor->Id() );

        int dt_parts[6];
        dt_parts[0] = stime->tm_year-100;
        dt_parts[1] = stime->tm_mon+1;
        dt_parts[2] = stime->tm_mday;
        dt_parts[3] = stime->tm_hour;
        dt_parts[4] = stime->tm_min;
        dt_parts[5] = stime->tm_sec;

        char date_path[PATH_MAX] = "";
        char time_path[PATH_MAX] = "";
        char *time_path_ptr = time_path;
        for ( unsigned int i = 0; i < sizeof(dt_parts)/sizeof(*dt_parts); i++ )
        {
            path_ptr += snprintf( path_ptr, sizeof(path)-(path_ptr-path), "/%02d", dt_parts[i] );

            struct stat statbuf;
            errno = 0;
            stat( path, &statbuf );
            if ( errno == ENOENT || errno == ENOTDIR )
            {
                if ( mkdir( path, 0755 ) )
                {
                    Fatal( "Can't mkdir %s: %s", path, strerror(errno));
                }
            }
            if ( i == 2 )
                strncpy( date_path, path, sizeof(date_path) );
            else if ( i >= 3 )
                time_path_ptr += snprintf( time_path_ptr, sizeof(time_path)-(time_path_ptr-time_path), "%s%02d", i>3?"/":"", dt_parts[i] );
        }
        char id_file[PATH_MAX];
        // Create event id symlink
        snprintf( id_file, sizeof(id_file), "%s/.%d", date_path, id );
        if ( symlink( time_path, id_file ) < 0 )
            Fatal( "Can't symlink %s -> %s: %s", id_file, path, strerror(errno));
        // Create empty id tag file
        snprintf( id_file, sizeof(id_file), "%s/.%d", path, id );
        if ( FILE *id_fp = fopen( id_file, "w" ) )
            fclose( id_fp );
        else
            Fatal( "Can't fopen %s: %s", id_file, strerror(errno));
    }
    else
    {
        snprintf( path, sizeof(path), "%s/%d/%d", config.dir_events, monitor->Id(), id );
        
        struct stat statbuf;
        errno = 0;
        stat( path, &statbuf );
        if ( errno == ENOENT || errno == ENOTDIR )
        {
            if ( mkdir( path, 0755 ) )
            {
                Error( "Can't mkdir %s: %s", path, strerror(errno));
            }
        }
        char id_file[PATH_MAX];
        // Create empty id tag file
        snprintf( id_file, sizeof(id_file), "%s/.%d", path, id );
        if ( FILE *id_fp = fopen( id_file, "w" ) )
            fclose( id_fp );
        else
            Fatal( "Can't fopen %s: %s", id_file, strerror(errno));
    }
    last_db_frame = 0;
}

Event::~Event()
{
    if ( frames > last_db_frame )
    {
        struct DeltaTimeval delta_time;
        DELTA_TIMEVAL( delta_time, end_time, start_time, DT_PREC_2 );

        Debug( 1, "Adding closing frame %d to DB", frames );
        static char sql[ZM_SQL_SML_BUFSIZ];
        snprintf( sql, sizeof(sql), "insert into Frames ( EventId, FrameId, TimeStamp, Delta ) values ( %d, %d, from_unixtime( %ld ), %s%ld.%02ld )", id, frames, end_time.tv_sec, delta_time.positive?"":"-", delta_time.sec, delta_time.fsec );
        if ( mysql_query( &dbconn, sql ) )
        {
            Error( "Can't insert frame: %s", mysql_error( &dbconn ) );
            exit( mysql_errno( &dbconn ) );
        }
    }

    static char sql[ZM_SQL_MED_BUFSIZ];

    struct DeltaTimeval delta_time;
    DELTA_TIMEVAL( delta_time, end_time, start_time, DT_PREC_2 );

    snprintf( sql, sizeof(sql), "update Events set Name='%s%d', EndTime = from_unixtime( %ld ), Length = %s%ld.%02ld, Frames = %d, AlarmFrames = %d, TotScore = %d, AvgScore = %d, MaxScore = %d where Id = %d", monitor->EventPrefix(), id, end_time.tv_sec, delta_time.positive?"":"-", delta_time.sec, delta_time.fsec, frames, alarm_frames, tot_score, (int)(alarm_frames?(tot_score/alarm_frames):0), max_score, id );
    if ( mysql_query( &dbconn, sql ) )
    {
        Error( "Can't update event: %s", mysql_error( &dbconn ) );
        exit( mysql_errno( &dbconn ) );
    }
}

void Event::createNotes( std::string &notes )
{
    notes.clear();
    for ( StringSetMap::const_iterator mapIter = noteSetMap.begin(); mapIter != noteSetMap.end(); mapIter++ )
    {
        notes += mapIter->first;
        notes += ": ";
        const StringSet &stringSet = mapIter->second;
        for ( StringSet::const_iterator setIter = stringSet.begin(); setIter != stringSet.end(); setIter++ )
        {
            if ( setIter != stringSet.begin() )
                notes += ", ";
            notes += *setIter;
        }
    }
}

int Event::sd = -1;

bool Event::OpenFrameSocket( int monitor_id )
{
    if ( sd > 0 )
    {
        close( sd );
    }

    sd = socket( AF_UNIX, SOCK_STREAM, 0);
    if ( sd < 0 )
    {
        Error( "Can't create socket: %s", strerror(errno) );
        return( false );
    }

    int socket_buffer_size = config.frame_socket_size;
    if ( socket_buffer_size > 0 )
    {
        if ( setsockopt( sd, SOL_SOCKET, SO_SNDBUF, &socket_buffer_size, sizeof(socket_buffer_size) ) < 0 )
        {
            Error( "Can't get socket buffer size to %d, error = %s", socket_buffer_size, strerror(errno) );
            close( sd );
            sd = -1;
            return( false );
        }
    }

    int flags;
    if ( (flags = fcntl( sd, F_GETFL )) < 0 )
    {
        Error( "Can't get socket flags, error = %s", strerror(errno) );
        close( sd );
        sd = -1;
        return( false );
    }
    flags |= O_NONBLOCK;
    if ( fcntl( sd, F_SETFL, flags ) < 0 )
    {
        Error( "Can't set socket flags, error = %s", strerror(errno) );
        close( sd );
        sd = -1;
        return( false );
    }

    char sock_path[PATH_MAX] = "";
    snprintf( sock_path, sizeof(sock_path), "%s/zmf-%d.sock", config.path_socks, monitor_id );

    struct sockaddr_un addr;

    strncpy( addr.sun_path, sock_path, sizeof(addr.sun_path) );
    addr.sun_family = AF_UNIX;

    if ( connect( sd, (struct sockaddr *)&addr, strlen(addr.sun_path)+sizeof(addr.sun_family)) < 0 )
    {
        Warning( "Can't connect to frame server: %s", strerror(errno) );
        close( sd );
        sd = -1;
        return( false );
    }

    Debug( 1, "Opened connection to frame server" );
    return( true );
}

bool Event::ValidateFrameSocket( int monitor_id )
{
    if ( sd < 0 )
    {
        return( OpenFrameSocket( monitor_id ) );
    }
    return( true );
}

bool Event::SendFrameImage( const Image *image, bool alarm_frame )
{
    if ( !ValidateFrameSocket( monitor->Id() ) )
    {
        return( false );
    }

    static int jpg_buffer_size = 0;
    static unsigned char jpg_buffer[ZM_MAX_IMAGE_SIZE];

    image->EncodeJpeg( jpg_buffer, &jpg_buffer_size, (alarm_frame&&(config.jpeg_alarm_file_quality>config.jpeg_file_quality))?config.jpeg_alarm_file_quality:config.jpeg_file_quality );

    static FrameHeader frame_header;

    frame_header.event_id = id;
    if ( config.use_deep_storage )
        frame_header.event_time = start_time.tv_sec;
    frame_header.frame_id = frames;
    frame_header.alarm_frame = alarm_frame;
    frame_header.image_length = jpg_buffer_size;

    struct iovec iovecs[2];
    iovecs[0].iov_base = &frame_header;
    iovecs[0].iov_len = sizeof(frame_header);
    iovecs[1].iov_base = jpg_buffer;
    iovecs[1].iov_len = jpg_buffer_size;

    ssize_t writev_size = sizeof(frame_header)+jpg_buffer_size;
    ssize_t writev_result = writev( sd, iovecs, sizeof(iovecs)/sizeof(*iovecs));
    if ( writev_result != writev_size )
    {
        if ( writev_result < 0 )
        {
            if ( errno == EAGAIN )
            {
                Warning( "Blocking write detected" );
            }
            else
            {
                Error( "Can't write frame: %s", strerror(errno) );
                close( sd );
                sd = -1;
            }
        }
        else
        {
            Error( "Incomplete frame write: %zd of %zd bytes written", writev_result, writev_size );
            close( sd );
            sd = -1;
        }
        return( false );
    }
    Debug( 1, "Wrote frame image, %d bytes", jpg_buffer_size );

    return( true );
}

bool Event::WriteFrameImage( Image *image, struct timeval timestamp, const char *event_file, bool alarm_frame )
{
    if ( config.timestamp_on_capture )
    {
        if ( !config.opt_frame_server || !SendFrameImage( image, alarm_frame) )
        {
            if ( alarm_frame && (config.jpeg_alarm_file_quality > config.jpeg_file_quality) )
                image->WriteJpeg( event_file, config.jpeg_alarm_file_quality );
            else
                image->WriteJpeg( event_file );
        }
    }
    else
    {
        Image ts_image( *image );
        monitor->TimestampImage( &ts_image, &timestamp );
        if ( !config.opt_frame_server || !SendFrameImage( &ts_image, alarm_frame) )
        {
            if ( alarm_frame && (config.jpeg_alarm_file_quality > config.jpeg_file_quality) )
                ts_image.WriteJpeg( event_file, config.jpeg_alarm_file_quality );
            else
                ts_image.WriteJpeg( event_file );
        }
    }
    return( true );
}

void Event::updateNotes( const StringSetMap &newNoteSetMap )
{
    bool update = false;

    //Info( "Checking notes, %d <> %d", noteSetMap.size(), newNoteSetMap.size() );
    if ( newNoteSetMap.size() > 0 )
    {
        if ( noteSetMap.size() == 0 )
        {
            noteSetMap = newNoteSetMap;
            update = true;
        }
        else
        {
            for ( StringSetMap::const_iterator newNoteSetMapIter = newNoteSetMap.begin(); newNoteSetMapIter != newNoteSetMap.end(); newNoteSetMapIter++ )
            {
                const std::string &newNoteGroup = newNoteSetMapIter->first;
                const StringSet &newNoteSet = newNoteSetMapIter->second;
                //Info( "Got %d new strings", newNoteSet.size() );
                if ( newNoteSet.size() > 0 )
                {
                    StringSetMap::iterator noteSetMapIter = noteSetMap.find( newNoteGroup );
                    if ( noteSetMapIter == noteSetMap.end() )
                    {
                        //Info( "Can't find note group %s, copying %d strings", newNoteGroup.c_str(), newNoteSet.size() );
                        noteSetMap.insert( StringSetMap::value_type( newNoteGroup, newNoteSet ) );
                        update = true;
                    }
                    else
                    {
                        StringSet &noteSet = noteSetMapIter->second;
                        //Info( "Found note group %s, got %d strings", newNoteGroup.c_str(), newNoteSet.size() );
                        for ( StringSet::const_iterator newNoteSetIter = newNoteSet.begin(); newNoteSetIter != newNoteSet.end(); newNoteSetIter++ )
                        {
                            const std::string &newNote = *newNoteSetIter;
                            StringSet::iterator noteSetIter = noteSet.find( newNote );
                            if ( noteSetIter == noteSet.end() )
                            {
                                noteSet.insert( newNote );
                                update = true;
                            }
                        }
                    }
                }
            }
        }
    }

    if ( update )
    {
        std::string notes;
        createNotes( notes );

        Debug( 2, "Updating notes for event %d, '%s'", id, notes.c_str() );
        static char sql[ZM_SQL_MED_BUFSIZ];
#if USE_PREPARED_SQL
        static MYSQL_STMT *stmt = 0;

        char notesStr[ZM_SQL_MED_BUFSIZ] = "";
        unsigned long notesLen = 0;

        if ( !stmt )
        {
            const char *sql = "update Events set Notes = ? where Id = ?";

            stmt = mysql_stmt_init( &dbconn );
            if ( mysql_stmt_prepare( stmt, sql, strlen(sql) ) )
            {
                Fatal( "Unable to prepare sql '%s': %s", sql, mysql_stmt_error(stmt) );
            }

            /* Get the parameter count from the statement */
            if ( mysql_stmt_param_count( stmt ) != 2 )
            {
                Fatal( "Unexpected parameter count %ld in sql '%s'", mysql_stmt_param_count( stmt ), sql );
            }

            MYSQL_BIND  bind[2];
            memset(bind, 0, sizeof(bind));

            /* STRING PARAM */
            bind[0].buffer_type = MYSQL_TYPE_STRING;
            bind[0].buffer = (char *)notesStr;
            bind[0].buffer_length = sizeof(notesStr);
            bind[0].is_null = 0;
            bind[0].length = &notesLen;

            bind[1].buffer_type= MYSQL_TYPE_LONG;
            bind[1].buffer= (char *)&id;
            bind[1].is_null= 0;
            bind[1].length= 0;

            /* Bind the buffers */
            if ( mysql_stmt_bind_param( stmt, bind ) )
            {
                Fatal( "Unable to bind sql '%s': %s", sql, mysql_stmt_error(stmt) );
            }
        }

        strncpy( notesStr, notes.c_str(), sizeof(notesStr) );
        notesLen = notes.length();

        if ( mysql_stmt_execute( stmt ) )
        {
            Fatal( "Unable to execute sql '%s': %s", sql, mysql_stmt_error(stmt) );
        }
#else
        static char escapedNotes[ZM_SQL_MED_BUFSIZ];

        mysql_real_escape_string( &dbconn, escapedNotes, notes.c_str(), notes.length() );

        snprintf( sql, sizeof(sql), "update Events set Notes = '%s' where Id = %d", escapedNotes, id );
        if ( mysql_query( &dbconn, sql ) )
        {
            Error( "Can't insert event: %s", mysql_error( &dbconn ) );
        }
#endif
    }
}

void Event::AddFrames( int n_frames, Image **images, struct timeval **timestamps )
{
    for (int i = 0; i < n_frames; i += ZM_SQL_BATCH_SIZE) {
        AddFramesInternal(n_frames, i, images, timestamps);
    }
}

void Event::AddFramesInternal( int n_frames, int start_frame, Image **images, struct timeval **timestamps )
{
    static char sql[ZM_SQL_LGE_BUFSIZ];
    strncpy( sql, "insert into Frames ( EventId, FrameId, TimeStamp, Delta ) values ", sizeof(sql) );
    int frameCount = 0;
    for ( int i = start_frame; i < n_frames && i - start_frame < ZM_SQL_BATCH_SIZE; i++ )
    {
        if ( !timestamps[i]->tv_sec )
        {
            Debug( 1, "Not adding pre-capture frame %d, zero timestamp", i );
            continue;
        }

        frames++;

        static char event_file[PATH_MAX];
        snprintf( event_file, sizeof(event_file), capture_file_format, path, frames );

        Debug( 1, "Writing pre-capture frame %d", frames );
        WriteFrameImage( images[i], *(timestamps[i]), event_file );

        struct DeltaTimeval delta_time;
        DELTA_TIMEVAL( delta_time, *(timestamps[i]), start_time, DT_PREC_2 );

        int sql_len = strlen(sql);
        snprintf( sql+sql_len, sizeof(sql)-sql_len, "( %d, %d, from_unixtime(%ld), %s%ld.%02ld ), ", id, frames, timestamps[i]->tv_sec, delta_time.positive?"":"-", delta_time.sec, delta_time.fsec );

        frameCount++;
    }

    if ( frameCount )
    {
        Debug( 1, "Adding %d/%d frames to DB", frameCount, n_frames );
        *(sql+strlen(sql)-2) = '\0';
        if ( mysql_query( &dbconn, sql ) )
        {
            Error( "Can't insert frames: %s", mysql_error( &dbconn ) );
            exit( mysql_errno( &dbconn ) );
        }
        last_db_frame = frames;
    }
    else
    {
        Debug( 1, "No valid pre-capture frames to add" );
    }
}

void Event::AddFrame( Image *image, struct timeval timestamp, int score, Image *alarm_image )
{
    if ( !timestamp.tv_sec )
    {
        Debug( 1, "Not adding new frame, zero timestamp" );
        return;
    }

    frames++;

    static char event_file[PATH_MAX];
    snprintf( event_file, sizeof(event_file), capture_file_format, path, frames );

    Debug( 1, "Writing capture frame %d", frames );
    WriteFrameImage( image, timestamp, event_file );

    struct DeltaTimeval delta_time;
    DELTA_TIMEVAL( delta_time, timestamp, start_time, DT_PREC_2 );

    bool db_frame = (score>=0) || ((frames%config.bulk_frame_interval)==0) || !frames;

    if ( db_frame )
    {
        const char *frame_type = score>0?"Alarm":(score<0?"Bulk":"Normal");

        Debug( 1, "Adding frame %d to DB", frames );
        static char sql[ZM_SQL_MED_BUFSIZ];
        snprintf( sql, sizeof(sql), "insert into Frames ( EventId, FrameId, Type, TimeStamp, Delta, Score ) values ( %d, %d, '%s', from_unixtime( %ld ), %s%ld.%02ld, %d )", id, frames, frame_type, timestamp.tv_sec, delta_time.positive?"":"-", delta_time.sec, delta_time.fsec, score );
        if ( mysql_query( &dbconn, sql ) )
        {
            Error( "Can't insert frame: %s", mysql_error( &dbconn ) );
            exit( mysql_errno( &dbconn ) );
        }
        last_db_frame = frames;

        // We are writing a bulk frame
        if ( score < 0 )
        {
            snprintf( sql, sizeof(sql), "update Events set Length = %s%ld.%02ld, Frames = %d, AlarmFrames = %d, TotScore = %d, AvgScore = %d, MaxScore = %d where Id = %d", delta_time.positive?"":"-", delta_time.sec, delta_time.fsec, frames, alarm_frames, tot_score, (int)(alarm_frames?(tot_score/alarm_frames):0), max_score, id );
            if ( mysql_query( &dbconn, sql ) )
            {
                Error( "Can't update event: %s", mysql_error( &dbconn ) );
                exit( mysql_errno( &dbconn ) );
            }
        }
    }

    end_time = timestamp;

    if ( score > 0 )
    {
        alarm_frames++;

        tot_score += score;
        if ( score > (int)max_score )
            max_score = score;

        if ( alarm_image )
        {
            snprintf( event_file, sizeof(event_file), analyse_file_format, path, frames );

            Debug( 1, "Writing analysis frame %d", frames );
            WriteFrameImage( alarm_image, timestamp, event_file, true );
        }
    }
    
    /* This makes viewing the diagnostic images impossible because it keeps deleting them
    if ( config.record_diag_images )
    {
        char diag_glob[PATH_MAX] = "";

        snprintf( diag_glob, sizeof(diag_glob), "%s/%d/diag-*.jpg", config.dir_events, monitor->Id() );
        glob_t pglob;
        int glob_status = glob( diag_glob, 0, 0, &pglob );
        if ( glob_status != 0 )
        {
            if ( glob_status < 0 )
            {
                Error( "Can't glob '%s': %s", diag_glob, strerror(errno) );
            }
            else
            {
                Debug( 1, "Can't glob '%s': %d", diag_glob, glob_status );
            }
        }
        else
        {
            char new_diag_path[PATH_MAX] = "";
            for ( int i = 0; i < pglob.gl_pathc; i++ )
            {
                char *diag_path = pglob.gl_pathv[i];

                char *diag_file = strstr( diag_path, "diag-" );

                if ( diag_file )
                {
                    snprintf( new_diag_path, sizeof(new_diag_path), general_file_format, path, frames, diag_file );

                    if ( rename( diag_path, new_diag_path ) < 0 )
                    {
                        Error( "Can't rename '%s' to '%s': %s", diag_path, new_diag_path, strerror(errno) );
                    }
                }
            }
        }
        globfree( &pglob );
    }
    */
}

bool EventStream::loadInitialEventData( int monitor_id, time_t event_time )
{
    static char sql[ZM_SQL_SML_BUFSIZ];

    snprintf( sql, sizeof(sql), "select Id from Events where MonitorId = %d and unix_timestamp( EndTime ) > %ld order by Id asc limit 1", monitor_id, event_time );

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
    MYSQL_ROW dbrow = mysql_fetch_row( result );

    if ( mysql_errno( &dbconn ) )
    {
        Error( "Can't fetch row: %s", mysql_error( &dbconn ) );
        exit( mysql_errno( &dbconn ) );
    }

    int init_event_id = atoi( dbrow[0] );

    mysql_free_result( result );

    loadEventData( init_event_id );

    if ( event_time )
    {
        curr_stream_time = event_time;
        curr_frame_id = 1;
        if ( event_time >= event_data->start_time )
        {
            for (unsigned int i = 0; i < event_data->frame_count; i++ )
            {
                //Info( "eft %d > et %d", event_data->frames[i].timestamp, event_time );
                if ( event_data->frames[i].timestamp >= event_time )
                {
                    curr_frame_id = i+1;
                    Debug( 3, "Set cst:%.2f", curr_stream_time );
                    Debug( 3, "Set cfid:%d", curr_frame_id );
                    break;
                }
            }
            Debug( 3, "Skipping %ld frames", event_data->frame_count );
        }
    }
    return( true );
}

bool EventStream::loadInitialEventData( int init_event_id, int init_frame_id )
{
    loadEventData( init_event_id );

    if ( init_frame_id )
    {
        curr_stream_time = event_data->frames[init_frame_id-1].timestamp;
        curr_frame_id = init_frame_id;
    }
    else
    {
        curr_stream_time = event_data->start_time;
    }

    return( true );
}

bool EventStream::loadEventData( int event_id )
{
    static char sql[ZM_SQL_MED_BUFSIZ];

    snprintf( sql, sizeof(sql), "select M.Id, M.Name, E.Frames, unix_timestamp( StartTime ) as StartTimestamp, max(F.Delta)-min(F.Delta) as Duration from Events as E inner join Monitors as M on E.MonitorId = M.Id inner join Frames as F on E.Id = F.EventId where E.Id = %d group by E.Id", event_id );

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

    if ( !mysql_num_rows( result ) )
    {
        Fatal( "Unable to load event %d, not found in DB", event_id );
    }

    MYSQL_ROW dbrow = mysql_fetch_row( result );

    if ( mysql_errno( &dbconn ) )
    {
        Error( "Can't fetch row: %s", mysql_error( &dbconn ) );
        exit( mysql_errno( &dbconn ) );
    }

    delete event_data;
    event_data = new EventData;
    event_data->event_id = event_id;
    event_data->monitor_id = atoi( dbrow[0] );
    event_data->start_time = atoi(dbrow[3]);
    if ( config.use_deep_storage )
    {
        struct tm *event_time = localtime( &event_data->start_time );
        if ( config.dir_events[0] == '/' )
            snprintf( event_data->path, sizeof(event_data->path), "%s/%ld/%02d/%02d/%02d/%02d/%02d/%02d", config.dir_events, event_data->monitor_id, event_time->tm_year-100, event_time->tm_mon+1, event_time->tm_mday, event_time->tm_hour, event_time->tm_min, event_time->tm_sec );
        else
            snprintf( event_data->path, sizeof(event_data->path), "%s/%s/%ld/%02d/%02d/%02d/%02d/%02d/%02d", staticConfig.PATH_WEB.c_str(), config.dir_events, event_data->monitor_id, event_time->tm_year-100, event_time->tm_mon+1, event_time->tm_mday, event_time->tm_hour, event_time->tm_min, event_time->tm_sec );
    }
    else
    {
        if ( config.dir_events[0] == '/' )
            snprintf( event_data->path, sizeof(event_data->path), "%s/%ld/%ld", config.dir_events, event_data->monitor_id, event_data->event_id );
        else
            snprintf( event_data->path, sizeof(event_data->path), "%s/%s/%ld/%ld", staticConfig.PATH_WEB.c_str(), config.dir_events, event_data->monitor_id, event_data->event_id );
    }
    event_data->frame_count = dbrow[2] == NULL ? 0 : atoi(dbrow[2]);
    event_data->duration = atof(dbrow[4]);

    updateFrameRate( (double)event_data->frame_count/event_data->duration );

    mysql_free_result( result );

    snprintf( sql, sizeof(sql), "select FrameId, unix_timestamp( `TimeStamp` ), Delta from Frames where EventId = %d order by FrameId asc", event_id );
    if ( mysql_query( &dbconn, sql ) )
    {
        Error( "Can't run query: %s", mysql_error( &dbconn ) );
        exit( mysql_errno( &dbconn ) );
    }

    result = mysql_store_result( &dbconn );
    if ( !result )
    {
        Error( "Can't use query result: %s", mysql_error( &dbconn ) );
        exit( mysql_errno( &dbconn ) );
    }

    event_data->n_frames = mysql_num_rows( result );

    event_data->frames = new FrameData[event_data->frame_count];
    int id, last_id = 0;
    time_t timestamp, last_timestamp = event_data->start_time;
    double delta, last_delta = 0.0;
    while ( ( dbrow = mysql_fetch_row( result ) ) )
    {
        id = atoi(dbrow[0]);
        timestamp = atoi(dbrow[1]);
        delta = atof(dbrow[2]);
        int id_diff = id - last_id;
        double frame_delta = (delta-last_delta)/id_diff;
        if ( id_diff > 1 )
        {
            for ( int i = last_id+1; i < id; i++ )
            {
                event_data->frames[i-1].timestamp = (time_t)(last_timestamp + ((i-last_id)*frame_delta));
                event_data->frames[i-1].offset = (time_t)(event_data->frames[i-1].timestamp-event_data->start_time);
                event_data->frames[i-1].delta = frame_delta;
                event_data->frames[i-1].in_db = false;
            }
        }
        event_data->frames[id-1].timestamp = timestamp;
        event_data->frames[id-1].offset = (time_t)(event_data->frames[id-1].timestamp-event_data->start_time);
        event_data->frames[id-1].delta = id>1?frame_delta:0.0;
        event_data->frames[id-1].in_db = true;
        last_id = id;
        last_delta = delta;
        last_timestamp = timestamp;
    }
    if ( mysql_errno( &dbconn ) )
    {
        Error( "Can't fetch row: %s", mysql_error( &dbconn ) );
        exit( mysql_errno( &dbconn ) );
    }

    //for ( int i = 0; i < 250; i++ )
    //{
        //Info( "%d -> %d @ %f (%d)", i+1, event_data->frames[i].timestamp, event_data->frames[i].delta, event_data->frames[i].in_db );
    //}

    mysql_free_result( result );

    if ( forceEventChange || mode == MODE_ALL_GAPLESS )
    {
        if ( replay_rate > 0 )
            curr_stream_time = event_data->frames[0].timestamp;
        else
            curr_stream_time = event_data->frames[event_data->frame_count-1].timestamp;
    }
    Debug( 2, "Event:%ld, Frames:%ld, Duration: %.2f", event_data->event_id, event_data->frame_count, event_data->duration );

    return( true );
}

void EventStream::processCommand( const CmdMsg *msg )
{
    Debug( 2, "Got message, type %d, msg %d", msg->msg_type, msg->msg_data[0] )
    // Check for incoming command
    switch( (MsgCommand)msg->msg_data[0] )
    {
        case CMD_PAUSE :
        {
            Debug( 1, "Got PAUSE command" );

            // Set paused flag
            paused = true;
            replay_rate = ZM_RATE_BASE;
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
            }

	    // If we are in single event mode and at the last frame, replay the current event
	    if ( (mode == MODE_SINGLE) && (curr_frame_id == event_data->frame_count) )
		curr_frame_id = 1;

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
            }
            replay_rate = ntohs(((unsigned char)msg->msg_data[2]<<8)|(unsigned char)msg->msg_data[1])-32768;
            break;
        }
        case CMD_STOP :
        {
            Debug( 1, "Got STOP command" );

            // Clear paused flag
            paused = false;
            break;
        }
        case CMD_FASTFWD :
        {
            Debug( 1, "Got FAST FWD command" );
            if ( paused )
            {
                // Clear paused flag
                paused = false;
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
        case CMD_PREV :
        {
            Debug( 1, "Got PREV command" );
            if ( replay_rate >= 0 )
                curr_frame_id = 0;
            else
                curr_frame_id = event_data->frame_count+1;
            paused = false;
            forceEventChange = true;
            break;
        }
        case CMD_NEXT :
        {
            Debug( 1, "Got NEXT command" );
            if ( replay_rate >= 0 )
                curr_frame_id = event_data->frame_count+1;
            else
                curr_frame_id = 0;
            paused = false;
            forceEventChange = true;
            break;
        }
        case CMD_SEEK :
        {
            int offset = ((unsigned char)msg->msg_data[1]<<24)|((unsigned char)msg->msg_data[2]<<16)|((unsigned char)msg->msg_data[3]<<8)|(unsigned char)msg->msg_data[4];
            curr_frame_id = (int)(event_data->frame_count*offset/event_data->duration);
            Debug( 1, "Got SEEK command, to %d (new cfid: %d)", offset, curr_frame_id );
            break;
        }
        case CMD_QUERY :
        {
            Debug( 1, "Got QUERY command, sending STATUS" );
            break;
        }
        default :
        {
            // Do nothing, for now
        }
    }
    struct {
        int event;
        int progress;
        int rate;
        int zoom;
        bool paused;
    } status_data;

    status_data.event = event_data->event_id;
    status_data.progress = (int)event_data->frames[curr_frame_id-1].offset;
    status_data.rate = replay_rate;
    status_data.zoom = zoom;
    status_data.paused = paused;
    Debug( 2, "E:%d, P:%d, p:%d R:%d, Z:%d",
        status_data.event,
        status_data.paused,
        status_data.progress,
        status_data.rate,
        status_data.zoom
    );

    DataMsg status_msg;
    status_msg.msg_type = MSG_DATA_EVENT;
    memcpy( &status_msg.msg_data, &status_data, sizeof(status_msg.msg_data) );
    if ( sendto( sd, &status_msg, sizeof(status_msg), MSG_DONTWAIT, (sockaddr *)&rem_addr, sizeof(rem_addr) ) < 0 )
    {
        //if ( errno != EAGAIN )
        {
            Error( "Can't sendto on sd %d: %s", sd, strerror(errno) );
            exit( -1 );
        }
    }

    updateFrameRate( (double)event_data->frame_count/event_data->duration );
}

void EventStream::checkEventLoaded()
{
    bool reload_event = false;
    static char sql[ZM_SQL_SML_BUFSIZ];

    if ( curr_frame_id <= 0 )
    {
        snprintf( sql, sizeof(sql), "select Id from Events where MonitorId = %ld and Id < %ld order by Id desc limit 1", event_data->monitor_id, event_data->event_id );
        reload_event = true;
    }
    else if ( (unsigned int)curr_frame_id > event_data->frame_count )
    {
        snprintf( sql, sizeof(sql), "select Id from Events where MonitorId = %ld and Id > %ld order by Id asc limit 1", event_data->monitor_id, event_data->event_id );
        reload_event = true;
    }

    if ( reload_event )
    {
        if ( forceEventChange || mode != MODE_SINGLE )
        {
            //Info( "SQL:%s", sql );
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
            MYSQL_ROW dbrow = mysql_fetch_row( result );

            if ( mysql_errno( &dbconn ) )
            {
                Error( "Can't fetch row: %s", mysql_error( &dbconn ) );
                exit( mysql_errno( &dbconn ) );
            }

            if ( dbrow )
            {
                int event_id = atoi(dbrow[0]);
                Debug( 1, "Loading new event %d", event_id );

                loadEventData( event_id );

                Debug( 2, "Current frame id = %d", curr_frame_id );
                if ( replay_rate < 0 )
                    curr_frame_id = event_data->frame_count;
                else
                    curr_frame_id = 1;
                Debug( 2, "New frame id = %d", curr_frame_id );
            }
            else
            {
                if ( curr_frame_id <= 0 )
                    curr_frame_id = 1;
                else
                    curr_frame_id = event_data->frame_count;
                paused = true;
            }
            mysql_free_result( result );
            forceEventChange = false;
        }
        else
        {
            if ( curr_frame_id <= 0 )
                curr_frame_id = 1;
            else
                curr_frame_id = event_data->frame_count;
            paused = true;
        }
    }
}

bool EventStream::sendFrame( int delta_us )
{
    Debug( 2, "Sending frame %d", curr_frame_id );

    static char filepath[PATH_MAX];
    static struct stat filestat;
    FILE *fdj = NULL;
    
    snprintf( filepath, sizeof(filepath), Event::capture_file_format, event_data->path, curr_frame_id );

#if HAVE_LIBAVCODEC
    if ( type == STREAM_MPEG )
    {
        Image image( filepath );

        Image *send_image = prepareImage( &image );

        if ( !vid_stream )
        {
            vid_stream = new VideoStream( "pipe:", format, bitrate, effective_fps, send_image->Colours(), send_image->SubpixelOrder(), send_image->Width(), send_image->Height() );
            fprintf( stdout, "Content-type: %s\r\n\r\n", vid_stream->MimeType() );
            vid_stream->OpenStream();
        }
        /* double pts = */ vid_stream->EncodeFrame( send_image->Buffer(), send_image->Size(), config.mpeg_timed_frames, delta_us*1000 );
    }
    else
#endif // HAVE_LIBAVCODEC
    {
        static unsigned char temp_img_buffer[ZM_MAX_IMAGE_SIZE];

        int img_buffer_size = 0;
        uint8_t *img_buffer = temp_img_buffer;

        bool send_raw = ((scale>=ZM_SCALE_BASE)&&(zoom==ZM_SCALE_BASE));

        fprintf( stdout, "--ZoneMinderFrame\r\n" );

        if ( type != STREAM_JPEG )
            send_raw = false;

        if ( send_raw )
        {
            fdj = fopen( filepath, "rb" );
            if ( !fdj )
            {
                Error( "Can't open %s: %s", filepath, strerror(errno) );
                return( false );
            }
#if HAVE_SENDFILE            
            if( fstat(fileno(fdj),&filestat) < 0 ) {
		Error( "Failed getting information about file %s: %s", filepath, strerror(errno) );
		return( false );
	    }
#else
	    img_buffer_size = fread( img_buffer, 1, sizeof(temp_img_buffer), fdj );
#endif
        }
        else
        {
            Image image( filepath );

            Image *send_image = prepareImage( &image );

            switch( type )
            {
                case STREAM_JPEG :
                    send_image->EncodeJpeg( img_buffer, &img_buffer_size );
                    break;
                case STREAM_ZIP :
#if HAVE_ZLIB_H
                    unsigned long zip_buffer_size;
                    send_image->Zip( img_buffer, &zip_buffer_size );
                    img_buffer_size = zip_buffer_size;
                    break;
#else
                    Error("zlib is required for zipped images. Falling back to raw image");
                    type = STREAM_RAW;
#endif // HAVE_ZLIB_H
                case STREAM_RAW :
                    img_buffer = (uint8_t*)(send_image->Buffer());
                    img_buffer_size = send_image->Size();
                    break;
                default:
                    Fatal( "Unexpected frame type %d", type );
                    break;
            }
        }

        switch( type )
        {
            case STREAM_JPEG :
                fprintf( stdout, "Content-Type: image/jpeg\r\n" );
                break;
            case STREAM_RAW :
                fprintf( stdout, "Content-Type: image/x-rgb\r\n" );
                break;
            case STREAM_ZIP :
                fprintf( stdout, "Content-Type: image/x-rgbz\r\n" );
                break;
            default :
                Fatal( "Unexpected frame type %d", type );
                break;
        }


	if(send_raw) {
#if HAVE_SENDFILE  
		fprintf( stdout, "Content-Length: %d\r\n\r\n", (int)filestat.st_size );
		if(zm_sendfile(fileno(stdout), fileno(fdj), 0, (int)filestat.st_size) != (int)filestat.st_size) {
			/* sendfile() failed, use standard way instead */
			img_buffer_size = fread( img_buffer, 1, sizeof(temp_img_buffer), fdj );
			if ( fwrite( img_buffer, img_buffer_size, 1, stdout ) != 1 ) {
				Error("Unable to send raw frame %u: %s",curr_frame_id,strerror(errno));
				return( false );
			}
		}
#else
		fprintf( stdout, "Content-Length: %d\r\n\r\n", img_buffer_size );
		if ( fwrite( img_buffer, img_buffer_size, 1, stdout ) != 1 ) {
			Error("Unable to send raw frame %u: %s",curr_frame_id,strerror(errno));
			return( false );
		}
#endif		
		fclose(fdj); /* Close the file handle */
	} else {
		fprintf( stdout, "Content-Length: %d\r\n\r\n", img_buffer_size );	  
		if ( fwrite( img_buffer, img_buffer_size, 1, stdout ) != 1 )
		{
			Error( "Unable to send stream frame: %s", strerror(errno) );
			return( false );
		}
	}
	
        fprintf( stdout, "\r\n\r\n" );
        fflush( stdout );
    }
    last_frame_sent = TV_2_FLOAT( now );
    return( true );
}

void EventStream::runStream()
{
    Event::Initialise();

    openComms();

    checkInitialised();

    updateFrameRate( (double)event_data->frame_count/event_data->duration );

    if ( type == STREAM_JPEG )
        fprintf( stdout, "Content-Type: multipart/x-mixed-replace;boundary=ZoneMinderFrame\r\n\r\n" );

    if ( !event_data )
    {
        sendTextFrame( "No event data found" );
        exit( 0 );
    }

    unsigned int delta_us = 0;
    while( !zm_terminate )
    {
        gettimeofday( &now, NULL );

        while(checkCommandQueue());

        if ( step != 0 )
            curr_frame_id += step;

        checkEventLoaded();

        // Get current frame data
        FrameData *frame_data = &event_data->frames[curr_frame_id-1];

        //Info( "cst:%.2f", curr_stream_time );
        //Info( "cfid:%d", curr_frame_id );
        //Info( "fdt:%d", frame_data->timestamp );
        if ( !paused )
        {
            bool in_event = true;
            double time_to_event = 0;
            if ( replay_rate > 0 )
            {
                time_to_event = event_data->frames[0].timestamp - curr_stream_time;
                if ( time_to_event > 0 )
                    in_event = false;
            }
            else if ( replay_rate < 0 )
            {
                time_to_event = curr_stream_time - event_data->frames[event_data->frame_count-1].timestamp;
                if ( time_to_event > 0 )
                    in_event = false;
            }
            if ( !in_event )
            {
                double actual_delta_time = TV_2_FLOAT( now ) - last_frame_sent;
                if ( actual_delta_time > 1 )
                {
                    static char frame_text[64];
                    snprintf( frame_text, sizeof(frame_text), "Time to next event = %d seconds", (int)time_to_event );
                    if ( !sendTextFrame( frame_text ) )
                        zm_terminate = true;
                }
                //else
                //{
                    usleep( STREAM_PAUSE_WAIT );
                    //curr_stream_time += (replay_rate>0?1:-1) * ((1.0L * replay_rate * STREAM_PAUSE_WAIT)/(ZM_RATE_BASE * 1000000));
                    curr_stream_time += (1.0L * replay_rate * STREAM_PAUSE_WAIT)/(ZM_RATE_BASE * 1000000);
                //}
                continue;
            }
        }

        // Figure out if we should send this frame
        bool send_frame = false;
        if ( !paused )
        {
            // If we are streaming and this frame is due to be sent
            if ( ((curr_frame_id-1)%frame_mod) == 0 )
            {
                delta_us = (unsigned int)(frame_data->delta * 1000000);
                // if effective > base we should speed up frame delivery
                delta_us = (unsigned int)((delta_us * base_fps)/effective_fps);
                // but must not exceed maxfps
                delta_us = max(delta_us, 1000000 / maxfps); 
                send_frame = true;
            }
        }
        else if ( step != 0 )
        {
            // We are paused and are just stepping forward or backward one frame
            step = 0;
            send_frame = true;
        }
        else
        {
            // We are paused, and doing nothing
            double actual_delta_time = TV_2_FLOAT( now ) - last_frame_sent;
            if ( actual_delta_time > MAX_STREAM_DELAY )
            {
                // Send keepalive
                Debug( 2, "Sending keepalive frame" );
                send_frame = true;
            }
        }

        if ( send_frame )
            if ( !sendFrame( delta_us ) )
                zm_terminate = true;

        curr_stream_time = frame_data->timestamp;

        if ( !paused )
        {
            curr_frame_id += replay_rate>0?1:-1;
            if ( send_frame && type != STREAM_MPEG )
            {
                Debug( 3, "dUs: %d", delta_us );
                usleep( delta_us );
            }
        }
        else
        {
            usleep( (unsigned long)((1000000 * ZM_RATE_BASE)/((base_fps?base_fps:1)*abs(replay_rate*2))) );
        }
    }
#if HAVE_LIBAVCODEC
    if ( type == STREAM_MPEG )
        delete vid_stream;
#endif // HAVE_LIBAVCODEC

    closeComms();
}
