//
// ZoneMinder Monitor Class Implementation, $Date$, $Revision$
// Copyright (C) 2003, 2004, 2005, 2006  Philip Coombes
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

#include <sys/ipc.h>
#include <sys/shm.h>

#include "zm.h"
#include "zm_db.h"
#include "zm_mpeg.h"
#include "zm_monitor.h"
#include "zm_local_camera.h"
#include "zm_remote_camera.h"
#include "zm_file_camera.h"

Monitor::MonitorLink::MonitorLink( int p_id, const char *p_name ) : id( p_id )
{
	strncpy( name, p_name, sizeof(name) );

	shm_id = 0;
	shm_size = 0;
	shm_ptr = 0;

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

		shm_size = sizeof(SharedData)
				 + sizeof(TriggerData);

		Debug( 1, ( "link.shm.size=%d", shm_size ));
		shm_id = shmget( (config.shm_key&0xffffff00)|id, shm_size, 0700 );
		if ( shm_id < 0 )
		{
			Debug( 3, ( "Can't shmget link memory: %s", strerror(errno)));
			connected = false;
			return( false );
		}
		shm_ptr = (unsigned char *)shmat( shm_id, 0, 0 );
		if ( shm_ptr < 0 )
		{
			Debug( 3, ( "Can't shmat link memory: %s", strerror(errno)));
			connected = false;
			return( false );
		}

		shared_data = (SharedData *)shm_ptr;
		trigger_data = (TriggerData *)((char *)shared_data + sizeof(SharedData));

		if ( !shared_data->valid )
		{
			Debug( 3, ( "Linked memory not initialised by capture daemon" ));
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

		struct shmid_ds shm_data;
		if ( shmctl( shm_id, IPC_STAT, &shm_data ) < 0 )
		{
			Debug( 3, ( "Can't shmctl: %s", strerror(errno)));
			return( false );
		}

		shm_id = 0;

		if ( shm_data.shm_nattch <= 1 )
		{
			if ( shmctl( shm_id, IPC_RMID, 0 ) < 0 )
			{
				Debug( 3, ( "Can't shmctl: %s", strerror(errno)));
				return( false );
			}
		}

		if ( shmdt( shm_ptr ) < 0 )
		{
			Debug( 3, ( "Can't shmdt: %s", strerror(errno)));
			return( false );
		}

		shm_size = 0;
		shm_ptr = 0;
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
	int p_alarm_frame_count,
	int p_section_length,
	int p_frame_skip,
	int p_capture_delay,
	int p_alarm_capture_delay,
	int p_fps_report_interval,
	int p_ref_blend_perc,
	bool p_track_motion,
	Purpose p_purpose,
	int p_n_zones,
	Zone *p_zones[]
) : id( p_id ),
	function( (Function)p_function ),
	enabled( p_enabled ),
	camera( p_camera ),
	orientation( (Orientation)p_orientation ),
	width( (p_orientation==ROTATE_90||p_orientation==ROTATE_270)?p_camera->Height():p_camera->Width() ),
	height( (p_orientation==ROTATE_90||p_orientation==ROTATE_270)?p_camera->Width():p_camera->Height() ),
	label_coord( p_label_coord ),
	image_buffer_count( p_image_buffer_count ),
	warmup_count( p_warmup_count ),
	pre_event_count( p_pre_event_count ),
	post_event_count( p_post_event_count ),
	alarm_frame_count( p_alarm_frame_count ),
	section_length( p_section_length ),
	frame_skip( p_frame_skip ),
	capture_delay( p_capture_delay ),
	alarm_capture_delay( p_alarm_capture_delay ),
	fps_report_interval( p_fps_report_interval ),
	ref_blend_perc( p_ref_blend_perc ),
	track_motion( p_track_motion ),
	image( width, height, p_camera->Colours() ),
	ref_image( width, height, p_camera->Colours() ),
	purpose( p_purpose ),
	n_zones( p_n_zones ),
	zones( p_zones )
{
	strncpy( name, p_name, sizeof(name) );

	strncpy( event_prefix, p_event_prefix, sizeof(event_prefix) );
	strncpy( label_format, p_label_format, sizeof(label_format) );

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

	Debug( 1, ( "monitor purpose=%d", purpose ));

	shm_size = sizeof(SharedData)
			 + sizeof(TriggerData)
			 + (image_buffer_count*sizeof(struct timeval))
			 + (image_buffer_count*camera->ImageSize());

	Debug( 1, ( "shm.size=%d", shm_size ));
	shm_id = shmget( (config.shm_key&0xffffff00)|id, shm_size, IPC_CREAT|0700 );
	if ( shm_id < 0 )
	{
		Error(( "Can't shmget, probably not enough shared memory space free: %s", strerror(errno)));
		exit( -1 );
	}
	shm_ptr = (unsigned char *)shmat( shm_id, 0, 0 );
	if ( shm_ptr < 0 )
	{
		Error(( "Can't shmat: %s", strerror(errno)));
		exit( -1 );
	}

	shared_data = (SharedData *)shm_ptr;
	trigger_data = (TriggerData *)((char *)shared_data + sizeof(SharedData));
	struct timeval *shared_timestamps = (struct timeval *)((char *)trigger_data + sizeof(TriggerData));
	unsigned char *shared_images = (unsigned char *)((char *)shared_timestamps + (image_buffer_count*sizeof(struct timeval)));

	if ( purpose == CAPTURE )
	{
		memset( shm_ptr, 0, shm_size );
		shared_data->size = sizeof(SharedData);
		shared_data->valid = true;
		shared_data->active = enabled;
		shared_data->signal = false;
		shared_data->state = IDLE;
		shared_data->last_write_index = image_buffer_count;
		shared_data->last_read_index = image_buffer_count;
		shared_data->last_image_time = 0;
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

	if ( !shared_data->valid )
	{
		Error(( "Shared memory not initialised by capture daemon" ));
		exit( -1 );
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

	Debug( 1, ( "Monitor %s has function %d", name, function ));
	Debug( 1, ( "Monitor %s LBF = '%s', LBX = %d, LBY = %d", name, label_format, label_coord.X(), label_coord.Y() ));
	Debug( 1, ( "Monitor %s IBC = %d, WUC = %d, pEC = %d, PEC = %d, EAF = %d, FRI = %d, RBP = %d, FM = %d", name, image_buffer_count, warmup_count, pre_event_count, post_event_count, alarm_frame_count, fps_report_interval, ref_blend_perc, track_motion ));

	if ( purpose == ANALYSIS )
	{
		static char	path[PATH_MAX];

		strncpy( path, config.dir_events, sizeof(path) );

		struct stat statbuf;
		errno = 0;
		stat( path, &statbuf );
		if ( errno == ENOENT || errno == ENOTDIR )
		{
			if ( mkdir( path, 0755 ) )
			{
				Error(( "Can't make %s: %s", path, strerror(errno)));
			}
		}

		snprintf( path, sizeof(path), "%s/%d", config.dir_events, id );

		errno = 0;
		stat( path, &statbuf );
		if ( errno == ENOENT || errno == ENOTDIR )
		{
			if ( mkdir( path, 0755 ) )
			{
				Error(( "Can't make %s: %s", path, strerror(errno)));
			}
			char temp_path[PATH_MAX];
			snprintf( temp_path, sizeof(temp_path), "%d", id );
			chdir( config.dir_events );
			symlink( temp_path, name );
			chdir( ".." );
		}

		while( shared_data->last_write_index == image_buffer_count )
		{
			Warning(( "Waiting for capture daemon" ));
			sleep( 1 );
		}
		ref_image.Assign( width, height, camera->Colours(), image_buffer[shared_data->last_write_index].image->Buffer() );

		n_linked_monitors = 0;
		linked_monitors = 0;
		ReloadLinkedMonitors( p_linked_monitors );
	}
	srand( time( 0 ) );
}

Monitor::~Monitor()
{
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
	}
	else if ( purpose == CAPTURE )
	{
		shared_data->valid = false;
		memset( shm_ptr, 0, shm_size );
	}

	struct shmid_ds shm_data;
	if ( shmctl( shm_id, IPC_STAT, &shm_data ) < 0 )
	{
		Error(( "Can't shmctl: %s", strerror(errno)));
		exit( -1 );
	}

	if ( shm_data.shm_nattch <= 1 )
	{
		if ( shmctl( shm_id, IPC_RMID, 0 ) < 0 )
		{
			Error(( "Can't shmctl: %s", strerror(errno)));
			exit( -1 );
		}
	}
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

		if ( scale != 100 )
		{
			snap_image.Scale( scale );
		}

		static char filename[PATH_MAX];
		snprintf( filename, sizeof(filename), "%s.jpg", name );
		if ( !config.timestamp_on_capture )
		{
			TimestampImage( &snap_image, snap->timestamp->tv_sec );
		}
		snap_image.WriteJpeg( filename );
	}
	else
	{
		Error(( "Unable to generate image, no images in buffer" ));
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
	time_t time1 = snap1->timestamp->tv_sec;

	int image_count = image_buffer_count;
	int index2 = (index1+1)%image_buffer_count;
	if ( index2 == image_buffer_count )
	{
		return( 0.0 );
	}
	Snapshot *snap2 = &image_buffer[index2];
	while ( !snap2->timestamp || !snap2->timestamp->tv_sec || time1 == snap2->timestamp->tv_sec )
	{
		if ( index1 == index2 )
		{
			return( 0.0 );
		}
		index2 = (index2+1)%image_buffer_count;
		snap2 = &image_buffer[index2];
		image_count--;
	}
	time_t time2 = snap2->timestamp->tv_sec;

	double curr_fps = double(image_count)/(time1-time2);

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

	static char sql[BUFSIZ];
	snprintf( sql, sizeof(sql), "update Monitors set Enabled = 1 where Id = '%d'", id );
	if ( mysql_query( &dbconn, sql ) )
	{
		Error(( "Can't run query: %s", mysql_error( &dbconn ) ));
		exit( mysql_errno( &dbconn ) );
	}
}

void Monitor::actionDisable()
{
	shared_data->action |= RELOAD;

	static char sql[BUFSIZ];
	snprintf( sql, sizeof(sql), "update Monitors set Enabled = 0 where Id = '%d'", id );
	if ( mysql_query( &dbconn, sql ) )
	{
		Error(( "Can't run query: %s", mysql_error( &dbconn ) ));
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
					return( -1 );
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
					return( -1 );
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
					return( -1 );
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
					return( -1 );
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
					return( -1 );
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
					return( -1 );
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
					return( -1 );
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
					return( -1 );
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
			Error(( "Failed to parse zone string, ignoring" ));
		}
	}

	int index = shared_data->last_write_index;
	Snapshot *snap = &image_buffer[index];
	Image *snap_image = snap->image;

	Image zone_image( *snap_image );
	zone_image.Colourise();
	for( int i = 0; i < n_zones; i++ )
	{
		if ( exclude_id && zones[i]->Id() == exclude_id )
			continue;

		Rgb colour;
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
		zone_image.Fill( colour, 2, zones[i]->GetPolygon() );
		zone_image.Outline( colour, zones[i]->GetPolygon() );
	}

	if ( extra_zone.getNumCoords() )
	{
		zone_image.Fill( extra_colour, 2, extra_zone );
		zone_image.Outline( extra_colour, extra_zone );
	}

	static char filename[PATH_MAX];
	snprintf( filename, sizeof(filename), "%s-Zones.jpg", name );
	zone_image.WriteJpeg( filename );
}

void Monitor::DumpImage( Image *dump_image ) const
{
	if ( image_count && !(image_count%10) )
	{
		static char filename[PATH_MAX];
		static char new_filename[PATH_MAX];
		snprintf( filename, sizeof(filename), "%s.jpg", name );
		snprintf( new_filename, sizeof(new_filename), "%s-new.jpg", name );
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

	if ( camera->IsLocal() && config.signal_check_points > 0 )
	{
		if ( static_undef )
		{
			static_undef = false;
			red_val = RGB_RED_VAL(config.signal_check_colour);
			green_val = RGB_GREEN_VAL(config.signal_check_colour);
			blue_val = RGB_BLUE_VAL(config.signal_check_colour);
		}

		const unsigned char *buffer = image->Buffer();
		int pixels = image->Pixels();
		int colours = image->Colours();

		for ( int i = 0; i < config.signal_check_points; i++ )
		{
			int index = (rand()*pixels)/RAND_MAX;
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
	gettimeofday( &now, &dummy_tz );

	if ( image_count && !(image_count%fps_report_interval) )
	{
		fps = double(fps_report_interval)/(now.tv_sec-last_fps_time);
		Info(( "%s: %d - Processing at %.2f fps", name, image_count, fps ));
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

		Debug( 4, ( "RI:%d, WI: %d, PF = %d, RM = %d, Step = %d", shared_data->last_read_index, shared_data->last_write_index, pending_frames, read_margin, step ));
		if ( step <= pending_frames )
		{
			index = (shared_data->last_read_index+step)%image_buffer_count;
		}
		else
		{
			if ( pending_frames )
			{
				Warning(( "Approaching buffer overrun, consider slowing capture, simplifying analysis or increasing ring buffer size" ));
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
			Info(( "Received reload indication at count %d", image_count ));
			shared_data->action &= ~RELOAD;
			Reload();
		}
		if ( shared_data->action & SUSPEND )
		{
			if ( Active() )
			{
				Info(( "Received suspend indication at count %d", image_count ));
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
				Info(( "Received resume indication at count %d", image_count ));
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
		Info(( "Auto resuming at count %d", image_count ));
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
				static char cause[256];
				static char text[1024];

				char *cause_ptr = cause;
				char *text_ptr = text;

				*cause_ptr = '\0';
				*text_ptr = '\0';

				//Info(( "St:%d, Sc:%d, Ca:%s, Te:%s", trigger_data->trigger_state, trigger_data->trigger_score, trigger_data->trigger_cause, trigger_data->trigger_text ));
				if ( trigger_data->trigger_state == TRIGGER_ON )
				{
					score += trigger_data->trigger_score;
					if ( !event )
					{
						cause_ptr += snprintf( cause_ptr, sizeof(cause)-(cause_ptr-cause), "%s%s", cause[0]?", ":"", trigger_data->trigger_cause );
						text_ptr += snprintf( text_ptr, sizeof(text)-(text_ptr-text), "%s%s", text[0]?"\n":"", trigger_data->trigger_text );
					}
				}
				if ( signal_change )
				{
					score += 100;
					const char *signal_text;
					if ( !signal )
						signal_text = "Signal: Lost";
					else
						signal_text = "Signal: Reacquired";
					Warning(( signal_text ));
					if ( event )
					{
						closeEvent();
						shared_data->state = state = IDLE;
						last_section_mod = 0;
					}
					if ( !event )
					{
						cause_ptr += snprintf( cause_ptr, sizeof(cause)-(cause_ptr-cause), "%s%s", cause[0]?", ":"", "Signal" );
						text_ptr += snprintf( text_ptr, sizeof(text)-(text_ptr-text), "%s%s", text[0]?"\n":"", signal_text );
					}
					shared_data->active = signal;
					ref_image = *snap_image;
				}
				else if ( Active() && function != RECORD && function != NODECT )
				{
					if ( !event )
					{
						char motion_text[256];
						int motion_score = DetectMotion( *snap_image, motion_text, sizeof(motion_text) );
						if ( motion_score )
						{
							score += motion_score;
							cause_ptr += snprintf( cause_ptr, sizeof(cause)-(cause_ptr-cause), "%s%s", cause[0]?", ":"", "Motion" );
							text_ptr += snprintf( text_ptr, sizeof(text)-(text_ptr-text), "%sMotion: %s", text[0]?"\n":"", motion_text );
						}
					}
					else
					{
						score += DetectMotion( *snap_image );
					}
					shared_data->active = signal;
				}
				if ( n_linked_monitors > 0 )
				{
					bool first_link = true;
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
										cause_ptr += snprintf( cause_ptr, sizeof(cause)-(cause_ptr-cause), "%s%s", cause[0]?", ":"", "Linked" );
										text_ptr += snprintf( text_ptr, sizeof(text)-(text_ptr-text), "%sLinked: %s", text[0]?"\n":"", linked_monitors[i]->Name() );
										first_link = false;
									}
									else
									{
										text_ptr += snprintf( text_ptr, sizeof(text)-(text_ptr-text), ", %s", linked_monitors[i]->Name() );
									}
								}
								score += 50;
							}
						}
						else
						{
							linked_monitors[i]->connect();
						}
					}
				}
				if ( (!signal_change && signal) && (function == RECORD || function == MOCORD) )
				{
					if ( event )
					{
						int section_mod = timestamp->tv_sec%section_length;
						if ( section_mod < last_section_mod )
						{
							if ( state == IDLE || state == TAPE || config.force_close_events )
							{
								if ( state == IDLE || state == TAPE )
								{
							        Info(( "Ended event" ));
								}
								else
								{
									Info(( "Force closed event" ));
								}
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
						event = new Event( this, *timestamp, "Continuous" );
						shared_data->last_event = event->Id();

						Info(( "%s: %03d - Starting new event %d", name, image_count, event->Id() ));

						//if ( config.overlap_timed_events )
						if ( true )
						{
							int pre_index = ((index+image_buffer_count)-pre_event_count)%image_buffer_count;
							for ( int i = 0; i < pre_event_count; i++ )
							{
								timestamps[i] = image_buffer[pre_index].timestamp;
								images[i] = image_buffer[pre_index].image;

								pre_index = (pre_index+1)%image_buffer_count;
							}
							event->AddFrames( pre_event_count, images, timestamps );
						}
					}
				}
				if ( score )
				{
					if ( (state == IDLE || state == TAPE || state == PREALARM ) )
					{
						if ( Event::PreAlarmCount() >= (alarm_frame_count-1) )
						{
							Info(( "%s: %03d - Gone into alarm state", name, image_count ));
							shared_data->state = state = ALARM;
							if ( signal_change || (function != MOCORD && state != ALERT) )
							{
								int pre_index;

								if ( alarm_frame_count > 1 )
								{
									int ts_index = ((index+image_buffer_count)-(alarm_frame_count-1))%image_buffer_count;
									event = new Event( this, *(image_buffer[ts_index].timestamp), cause, text );
									pre_index = ((index+image_buffer_count)-((alarm_frame_count-1)+pre_event_count))%image_buffer_count;
								}
								else
								{
									event = new Event( this, *timestamp, cause, text );
									pre_index = ((index+image_buffer_count)-pre_event_count)%image_buffer_count;
								}
								shared_data->last_event = event->Id();

								for ( int i = 0; i < pre_event_count; i++ )
								{
									timestamps[i] = image_buffer[pre_index].timestamp;
									images[i] = image_buffer[pre_index].image;

									pre_index = (pre_index+1)%image_buffer_count;
								}
								event->AddFrames( pre_event_count, images, timestamps );
								if ( alarm_frame_count )
								{
									event->SavePreAlarmFrames();
								}
							}
						}
						else if ( state != PREALARM )
						{
							Info(( "%s: %03d - Gone into prealarm state", name, image_count ));
							shared_data->state = state = PREALARM;
						}
					}
					else if ( state == ALERT )
					{
						Info(( "%s: %03d - Gone back into alarm state", name, image_count ));
						shared_data->state = state = ALARM;
					}
					last_alarm_count = image_count;
				}
				else
				{
					if ( state == ALARM )
					{
						Info(( "%s: %03d - Gone into alert state", name, image_count ));
						shared_data->state = state = ALERT;
					}
					else if ( state == ALERT )
					{
						if ( image_count-last_alarm_count > post_event_count )
						{
							Info(( "%s: %03d - Left alarm state (%d) - %d(%d) images", name, image_count, event->Id(), event->Frames(), event->AlarmFrames() ));
							if ( function != MOCORD || !strcmp( event->Cause(), "Signal" ) )
							{
								shared_data->state = state = IDLE;
								delete event;
								event = 0;
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
					}
					else if ( state == ALERT )
					{
							event->AddFrame( snap_image, *timestamp );
					}
					else if ( state == TAPE )
					{
						if ( !(image_count%(frame_skip+1)) )
						{
							if ( config.bulk_frame_interval > 1 )
							{
								event->AddFrame( snap_image, *timestamp, -1 );
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
							Info(( "Ended event" ));
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
		if ( !signal_change && (function == MODECT || function == MOCORD) && (config.blend_alarmed_images || state != ALARM) )
		{
			ref_image.Blend( *snap_image, ref_blend_perc );
		}
		last_signal = signal;
	}

	shared_data->last_read_index = index%image_buffer_count;
	image_count++;

	return( true );
}

void Monitor::Reload()
{
	Debug( 1, ( "Reloading monitor %s", name ));

	closeEvent();

	static char sql[BUFSIZ];
	snprintf( sql, sizeof(sql), "select Function+0, Enabled, LinkedMonitors, EventPrefix, LabelFormat, LabelX, LabelY, WarmupCount, PreEventCount, PostEventCount, AlarmFrameCount, SectionLength, FrameSkip, MaxFPS, AlarmMaxFPS, FPSReportInterval, RefBlendPerc, TrackMotion from Monitors where Id = '%d'", id );
	if ( mysql_query( &dbconn, sql ) )
	{
		Error(( "Can't run query: %s", mysql_error( &dbconn ) ));
		exit( mysql_errno( &dbconn ) );
	}

	MYSQL_RES *result = mysql_store_result( &dbconn );
	if ( !result )
	{
		Error(( "Can't use query result: %s", mysql_error( &dbconn ) ));
		exit( mysql_errno( &dbconn ) );
	}
	int n_monitors = mysql_num_rows( result );
	if ( n_monitors != 1 )
	{
		Error(( "Bogus number of monitors, %d, returned. Can't reload", n_monitors )); 
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

		shared_data->state = state = IDLE;
		shared_data->alarm_x = shared_data->alarm_y = -1;
		if ( enabled )
			shared_data->active = true;
		ready_count = image_count+warmup_count;

		ReloadLinkedMonitors( p_linked_monitors );
	}
	if ( mysql_errno( &dbconn ) )
	{
		Error(( "Can't fetch row: %s", mysql_error( &dbconn ) ));
		exit( mysql_errno( &dbconn ) );
	}
	mysql_free_result( result );

	ReloadZones();
}

void Monitor::ReloadZones()
{
	Debug( 1, ( "Reloading zones for monitor %s", name ));
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
	Debug( 1, ( "Reloading linked monitors for monitor %s, '%s'", name, p_linked_monitors ));
	if ( n_linked_monitors )
	{
		for( int i = 0; i < n_zones; i++ )
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
					Debug( 3, ( "Found linked monitor id %d", link_id ));
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
			Debug( 1, ( "Linking to %d monitors", n_link_ids ));
			n_linked_monitors = n_link_ids;
			linked_monitors = new MonitorLink *[n_linked_monitors];
			for ( int i = 0; i < n_linked_monitors; i++ )
			{
				static char sql[BUFSIZ];
				Debug( 1, ( "Checking linked monitor %d", link_ids[i] ));

				snprintf( sql, sizeof(sql), "select Id, Name from Monitors where Id = %d and Function != 'None' and Function != 'Monitor' and Enabled = 1", link_ids[i] );
				if ( mysql_query( &dbconn, sql ) )
				{
					Error(( "Can't run query: %s", mysql_error( &dbconn ) ));
					exit( mysql_errno( &dbconn ) );
				}

				MYSQL_RES *result = mysql_store_result( &dbconn );
				if ( !result )
				{
					Error(( "Can't use query result: %s", mysql_error( &dbconn ) ));
					exit( mysql_errno( &dbconn ) );
				}
				int n_monitors = mysql_num_rows( result );
				if ( n_monitors == 1 )
				{
					MYSQL_ROW dbrow = mysql_fetch_row( result );
					Debug( 1, ( "Linking to monitor %d", link_ids[i] ));
					linked_monitors[i] = new MonitorLink( link_ids[i], dbrow[1] );
				}
				else
				{
					Debug( 1, ( "Can't link to monitor %d, invalid id, function or not enabled", link_ids[i] ));
				}
				mysql_free_result( result );
			}
		}
	}
}

int Monitor::LoadLocalMonitors( const char *device, Monitor **&monitors, Purpose purpose )
{
	static char sql[BUFSIZ];
	if ( !device[0] )
	{
		strncpy( sql, "select Id, Name, Function+0, Enabled, LinkedMonitors, Device, Channel, Format, Width, Height, Palette, Orientation+0, Brightness, Contrast, Hue, Colour, EventPrefix, LabelFormat, LabelX, LabelY, ImageBufferCount, WarmupCount, PreEventCount, PostEventCount, AlarmFrameCount, SectionLength, FrameSkip, MaxFPS, AlarmMaxFPS, FPSReportInterval, RefBlendPerc, TrackMotion from Monitors where Function != 'None' and Type = 'Local' order by Device, Channel", sizeof(sql) );
	}
	else
	{
		snprintf( sql, sizeof(sql), "select Id, Name, Function+0, Enabled, LinkedMonitors, Device, Channel, Format, Width, Height, Palette, Orientation+0, Brightness, Contrast, Hue, Colour, EventPrefix, LabelFormat, LabelX, LabelY, ImageBufferCount, WarmupCount, PreEventCount, PostEventCount, AlarmFrameCount, SectionLength, FrameSkip, MaxFPS, AlarmMaxFPS, FPSReportInterval, RefBlendPerc, TrackMotion from Monitors where Function != 'None' and Type = 'Local' and Device = '%s' order by Channel", device );
	}
	if ( mysql_query( &dbconn, sql ) )
	{
		Error(( "Can't run query: %s", mysql_error( &dbconn ) ));
		exit( mysql_errno( &dbconn ) );
	}

	MYSQL_RES *result = mysql_store_result( &dbconn );
	if ( !result )
	{
		Error(( "Can't use query result: %s", mysql_error( &dbconn ) ));
		exit( mysql_errno( &dbconn ) );
	}
	int n_monitors = mysql_num_rows( result );
	Debug( 1, ( "Got %d monitors", n_monitors ));
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

		Camera *camera = new LocalCamera(
			device, // Device
			channel, // Channel
			format, // Format
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
			alarm_frame_count,
			section_length,
			frame_skip,
			capture_delay,
			alarm_capture_delay,
			fps_report_interval,
			ref_blend_perc,
			track_motion,
			purpose
		);
		Zone **zones = 0;
		int n_zones = Zone::Load( monitors[i], zones );
		monitors[i]->AddZones( n_zones, zones );
		Debug( 1, ( "Loaded monitor %d(%s), %d zones", id, name, n_zones ));
	}
	if ( mysql_errno( &dbconn ) )
	{
		Error(( "Can't fetch row: %s", mysql_error( &dbconn ) ));
		exit( mysql_errno( &dbconn ) );
	}
	// Yadda yadda
	mysql_free_result( result );

	return( n_monitors );
}

int Monitor::LoadRemoteMonitors( const char *host, const char*port, const char *path, Monitor **&monitors, Purpose purpose )
{
	static char sql[BUFSIZ];
	if ( !host )
	{
		strncpy( sql, "select Id, Name, Function+0, Enabled, LinkedMonitors, Host, Port, Path, Width, Height, Palette, Orientation+0, Brightness, Contrast, Hue, Colour, EventPrefix, LabelFormat, LabelX, LabelY, ImageBufferCount, WarmupCount, PreEventCount, PostEventCount, AlarmFrameCount, SectionLength, FrameSkip, MaxFPS, AlarmMaxFPS, FPSReportInterval, RefBlendPerc, TrackMotion from Monitors where Function != 'None' and Type = 'Remote'", sizeof(sql) );
	}
	else
	{
		snprintf( sql, sizeof(sql), "select Id, Name, Function+0, Enabled, LinkedMonitors, Host, Port, Path, Width, Height, Palette, Orientation+0, Brightness, Contrast, Hue, Colour, EventPrefix, LabelFormat, LabelX, LabelY, ImageBufferCount, WarmupCount, PreEventCount, PostEventCount, AlarmFrameCount, SectionLength, FrameSkip, MaxFPS, AlarmMaxFPS, FPSReportInterval, RefBlendPerc, TrackMotion from Monitors where Function != 'None' and Type = 'Remote' and Host = '%s' and Port = '%s' and Path = '%s'", host, port, path );
	}
	if ( mysql_query( &dbconn, sql ) )
	{
		Error(( "Can't run query: %s", mysql_error( &dbconn ) ));
		exit( mysql_errno( &dbconn ) );
	}

	MYSQL_RES *result = mysql_store_result( &dbconn );
	if ( !result )
	{
		Error(( "Can't use query result: %s", mysql_error( &dbconn ) ));
		exit( mysql_errno( &dbconn ) );
	}
	int n_monitors = mysql_num_rows( result );
	Debug( 1, ( "Got %d monitors", n_monitors ));
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

		const char *host = dbrow[col]; col++;
		const char *port = dbrow[col]; col++;
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

		Camera *camera = new RemoteCamera(
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
			alarm_frame_count,
			section_length,
			frame_skip,
			capture_delay,
			alarm_capture_delay,
			fps_report_interval,
			ref_blend_perc,
			track_motion,
			purpose
		);
		Zone **zones = 0;
		int n_zones = Zone::Load( monitors[i], zones );
		monitors[i]->AddZones( n_zones, zones );
		Debug( 1, ( "Loaded monitor %d(%s), %d zones", id, name, n_zones ));
	}
	if ( mysql_errno( &dbconn ) )
	{
		Error(( "Can't fetch row: %s", mysql_error( &dbconn ) ));
		exit( mysql_errno( &dbconn ) );
	}
	// Yadda yadda
	mysql_free_result( result );

	return( n_monitors );
}

int Monitor::LoadFileMonitors( const char *file, Monitor **&monitors, Purpose purpose )
{
	static char sql[BUFSIZ];
	if ( !file[0] )
	{
		strncpy( sql, "select Id, Name, Function+0, Enabled, LinkedMonitors, Path, Width, Height, Palette, Orientation+0, Brightness, Contrast, Hue, Colour, EventPrefix, LabelFormat, LabelX, LabelY, ImageBufferCount, WarmupCount, PreEventCount, PostEventCount, AlarmFrameCount, SectionLength, FrameSkip, MaxFPS, AlarmMaxFPS, FPSReportInterval, RefBlendPerc, TrackMotion from Monitors where Function != 'None' and Type = 'File'", sizeof(sql) );
	}
	else
	{
		snprintf( sql, sizeof(sql), "select Id, Name, Function+0, Enabled, LinkedMonitors, Path, Width, Height, Palette, Orientation+0, Brightness, Contrast, Hue, Colour, EventPrefix, LabelFormat, LabelX, LabelY, ImageBufferCount, WarmupCount, PreEventCount, PostEventCount, AlarmFrameCount, SectionLength, FrameSkip, MaxFPS, AlarmMaxFPS, FPSReportInterval, RefBlendPerc, TrackMotion from Monitors where Function != 'None' and Type = 'File' and Path = '%s'", file );
	}
	if ( mysql_query( &dbconn, sql ) )
	{
		Error(( "Can't run query: %s", mysql_error( &dbconn ) ));
		exit( mysql_errno( &dbconn ) );
	}

	MYSQL_RES *result = mysql_store_result( &dbconn );
	if ( !result )
	{
		Error(( "Can't use query result: %s", mysql_error( &dbconn ) ));
		exit( mysql_errno( &dbconn ) );
	}
	int n_monitors = mysql_num_rows( result );
	Debug( 1, ( "Got %d monitors", n_monitors ));
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
			alarm_frame_count,
			section_length,
			frame_skip,
			capture_delay,
			alarm_capture_delay,
			fps_report_interval,
			ref_blend_perc,
			track_motion,
			purpose
		);
		Zone **zones = 0;
		int n_zones = Zone::Load( monitors[i], zones );
		monitors[i]->AddZones( n_zones, zones );
		Debug( 1, ( "Loaded monitor %d(%s), %d zones", id, name, n_zones ));
	}
	if ( mysql_errno( &dbconn ) )
	{
		Error(( "Can't fetch row: %s", mysql_error( &dbconn ) ));
		exit( mysql_errno( &dbconn ) );
	}
	// Yadda yadda
	mysql_free_result( result );

	return( n_monitors );
}

Monitor *Monitor::Load( int id, bool load_zones, Purpose purpose )
{
	static char sql[BUFSIZ];
	snprintf( sql, sizeof(sql), "select Id, Name, Type, Function+0, Enabled, LinkedMonitors, Device, Channel, Format, Host, Port, Path, Width, Height, Palette, Orientation+0, Brightness, Contrast, Hue, Colour, EventPrefix, LabelFormat, LabelX, LabelY, ImageBufferCount, WarmupCount, PreEventCount, PostEventCount, AlarmFrameCount, SectionLength, FrameSkip, MaxFPS, AlarmMaxFPS, FPSReportInterval, RefBlendPerc, TrackMotion from Monitors where Id = %d", id );
	if ( mysql_query( &dbconn, sql ) )
	{
		Error(( "Can't run query: %s", mysql_error( &dbconn ) ));
		exit( mysql_errno( &dbconn ) );
	}

	MYSQL_RES *result = mysql_store_result( &dbconn );
	if ( !result )
	{
		Error(( "Can't use query result: %s", mysql_error( &dbconn ) ));
		exit( mysql_errno( &dbconn ) );
	}
	int n_monitors = mysql_num_rows( result );
	Debug( 1, ( "Got %d monitors", n_monitors ));
	Monitor *monitor = 0;
	for( int i = 0; MYSQL_ROW dbrow = mysql_fetch_row( result ); i++ )
	{
		int col = 0;

		int id = atoi(dbrow[col]); col++;
		const char *name = dbrow[col]; col++;
		const char *type = dbrow[col]; col++;
		int function = atoi(dbrow[col]); col++;
		int enabled = atoi(dbrow[col]); col++;
		const char *linked_monitors = dbrow[col]; col++;

		const char *device = dbrow[col]; col++;
		int channel = atoi(dbrow[col]); col++;
		int format = atoi(dbrow[col]); col++;

		const char *host = dbrow[col]; col++;
		const char *port = dbrow[col]; col++;
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
		if ( !strcmp( type, "Local" ) )
		{
			camera = new LocalCamera(
				device, // Device
				channel, // Channel
				format, // Format
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
		else if ( !strcmp( type, "Remote" ) )
		{
			camera = new RemoteCamera(
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
		else if ( !strcmp( type, "File" ) )
		{
			camera = new FileCamera(
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
		else
		{
			Error(( "Bogus monitor type '%s' for monitor %d", type, id ));
			exit( -1 );
		}
		monitor = new Monitor(
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
			alarm_frame_count,
			section_length,
			frame_skip,
			capture_delay,
			alarm_capture_delay,
			fps_report_interval,
			ref_blend_perc,
			track_motion,
			purpose
		);

		int n_zones = 0;
		if ( load_zones )
		{
			Zone **zones = 0;
			n_zones = Zone::Load( monitor, zones );
			monitor->AddZones( n_zones, zones );
		}
		Debug( 1, ( "Loaded monitor %d(%s), %d zones", id, name, n_zones ));
	}
	if ( mysql_errno( &dbconn ) )
	{
		Error(( "Can't fetch row: %s", mysql_error( &dbconn ) ));
		exit( mysql_errno( &dbconn ) );
	}
	// Yadda yadda
	mysql_free_result( result );

	return( monitor );
}

void Monitor::StreamImages( int scale, int maxfps, time_t ttl )
{
	fprintf( stdout, "Content-Type: multipart/x-mixed-replace;boundary=ZoneMinderFrame\r\n\r\n" );

	int fps = int(GetFPS());
	if ( !fps )
		fps = 5;

	int min_fps = 1;
	int max_fps = maxfps;
	int base_fps = int(GetFPS());
	int effective_fps = base_fps;

	int frame_mod = 1;
	// Min frame repeat?
	while( effective_fps > max_fps )
	{
		effective_fps /= 2;
		frame_mod *= 2;
	}

	Debug( 1, ( "BFPS:%d, EFPS:%d, FM:%d", base_fps, effective_fps, frame_mod ));

	int last_read_index = image_buffer_count;

	time_t stream_start_time;
	time( &stream_start_time );

	int frame_count = 0;
	struct timeval base_time;
	struct DeltaTimeval delta_time;
	int img_buffer_size = 0;
	static JOCTET img_buffer[ZM_MAX_IMAGE_SIZE];
	Image scaled_image;
	while ( true )
	{
		if ( feof( stdout ) || ferror( stdout ) )
		{
			break;
		}
		if ( last_read_index != shared_data->last_write_index )
		{
			last_read_index = shared_data->last_write_index;
			if ( (frame_mod == 1) || ((frame_count%frame_mod) == 0) )
			{
				// Send the next frame
				int index = shared_data->last_write_index%image_buffer_count;
				//Info(( "%d: %x - %x", index, image_buffer[index].image, image_buffer[index].image->buffer ));
				Snapshot *snap = &image_buffer[index];
				Image *snap_image = snap->image;

				if ( scale != 100 )
				{
					scaled_image.Assign( *snap_image );

					scaled_image.Scale( scale );

					snap_image = &scaled_image;
				}
				if ( !config.timestamp_on_capture )
				{
					TimestampImage( snap_image, snap->timestamp->tv_sec );
				}
				snap_image->EncodeJpeg( img_buffer, &img_buffer_size );

				fprintf( stdout, "--ZoneMinderFrame\r\n" );
				fprintf( stdout, "Content-Length: %d\r\n", img_buffer_size );
				fprintf( stdout, "Content-Type: image/jpeg\r\n\r\n" );
				fwrite( img_buffer, img_buffer_size, 1, stdout );
				fprintf( stdout, "\r\n\r\n" );

				if ( ttl )
				{
					time_t now = time ( 0 );
					if ( (now - stream_start_time) > ttl )
					{
						break;
					}
				}
			}
			frame_count++;
		}
		usleep( ZM_SAMPLE_RATE );
	}
}

void Monitor::StreamImagesRaw( int scale, int maxfps, time_t ttl )
{
	fprintf( stdout, "Content-Type: multipart/x-mixed-replace;boundary=ZoneMinderFrame\r\n\r\n" );

	int fps = int(GetFPS());
	if ( !fps )
		fps = 5;

	int min_fps = 1;
	int max_fps = maxfps;
	int base_fps = int(GetFPS());
	int effective_fps = base_fps;

	int frame_mod = 1;
	// Min frame repeat?
	while( effective_fps > max_fps )
	{
		effective_fps /= 2;
		frame_mod *= 2;
	}

	Debug( 1, ( "BFPS:%d, EFPS:%d, FM:%d", base_fps, effective_fps, frame_mod ));

	int last_read_index = image_buffer_count;

	time_t stream_start_time;
	time( &stream_start_time );

	int frame_count = 0;
	struct timeval base_time;
	struct DeltaTimeval delta_time;
	Image scaled_image;
	while ( true )
	{
		if ( feof( stdout ) || ferror( stdout ) )
		{
			break;
		}
		if ( last_read_index != shared_data->last_write_index )
		{
			last_read_index = shared_data->last_write_index;
			if ( (frame_mod == 1) || ((frame_count%frame_mod) == 0) )
			{
				// Send the next frame
				int index = shared_data->last_write_index%image_buffer_count;
				//Info(( "%d: %x - %x", index, image_buffer[index].image, image_buffer[index].image->buffer ));
				Snapshot *snap = &image_buffer[index];
				Image *snap_image = snap->image;

				if ( scale != 100 )
				{
					scaled_image.Assign( *snap_image );

					scaled_image.Scale( scale );

					snap_image = &scaled_image;
				}
				if ( !config.timestamp_on_capture )
				{
					TimestampImage( snap_image, snap->timestamp->tv_sec );
				}

				fprintf( stdout, "--ZoneMinderFrame\r\n" );
				fprintf( stdout, "Content-Length: %d\r\n", snap_image->Size() );
				fprintf( stdout, "Content-Type: image/x-rgb\r\n\r\n" );
				fwrite( snap_image->Buffer(), snap_image->Size(), 1, stdout );
				fprintf( stdout, "\r\n\r\n" );

				if ( ttl )
				{
					if ( (time(0) - stream_start_time) > ttl )
					{
						break;
					}
				}
			}
			frame_count++;
		}
		usleep( ZM_SAMPLE_RATE );
	}
}

void Monitor::StreamImagesZip( int scale, int maxfps, time_t ttl )
{
	fprintf( stdout, "Content-Type: multipart/x-mixed-replace;boundary=ZoneMinderFrame\r\n\r\n" );

	int fps = int(GetFPS());
	if ( !fps )
		fps = 5;

	int min_fps = 1;
	int max_fps = maxfps;
	int base_fps = int(GetFPS());
	int effective_fps = base_fps;

	int frame_mod = 1;
	// Min frame repeat?
	while( effective_fps > max_fps )
	{
		effective_fps /= 2;
		frame_mod *= 2;
	}

	Debug( 1, ( "BFPS:%d, EFPS:%d, FM:%d", base_fps, effective_fps, frame_mod ));

	int last_read_index = image_buffer_count;

	time_t stream_start_time;
	time( &stream_start_time );

	int frame_count = 0;
	struct timeval base_time;
	struct DeltaTimeval delta_time;
	unsigned long img_buffer_size = 0;
	static Bytef img_buffer[ZM_MAX_IMAGE_SIZE];
	Image scaled_image;
	while ( true )
	{
		if ( feof( stdout ) || ferror( stdout ) )
		{
			break;
		}
		if ( last_read_index != shared_data->last_write_index )
		{
			last_read_index = shared_data->last_write_index;
			if ( (frame_mod == 1) || ((frame_count%frame_mod) == 0) )
			{
				// Send the next frame
				int index = shared_data->last_write_index%image_buffer_count;
				//Info(( "%d: %x - %x", index, image_buffer[index].image, image_buffer[index].image->buffer ));
				Snapshot *snap = &image_buffer[index];
				Image *snap_image = snap->image;

				if ( scale != 100 )
				{
					scaled_image.Assign( *snap_image );

					scaled_image.Scale( scale );

					snap_image = &scaled_image;
				}
				if ( !config.timestamp_on_capture )
				{
					TimestampImage( snap_image, snap->timestamp->tv_sec );
				}
				snap_image->Zip( img_buffer, &img_buffer_size );

				fprintf( stdout, "--ZoneMinderFrame\r\n" );
				fprintf( stdout, "Content-Length: %d\r\n", img_buffer_size );
				fprintf( stdout, "Content-Type: image/x-rgbz\r\n\r\n" );
				fwrite( img_buffer, img_buffer_size, 1, stdout );
				fprintf( stdout, "\r\n\r\n" );

				if ( ttl )
				{
					time_t now = time ( 0 );
					if ( (now - stream_start_time) > ttl )
					{
						break;
					}
				}
			}
			frame_count++;
		}
		usleep( ZM_SAMPLE_RATE );
	}
}

void Monitor::SingleImage( int scale)
{
	int last_read_index = shared_data->last_write_index;
	int img_buffer_size = 0;
	static JOCTET img_buffer[ZM_MAX_IMAGE_SIZE];
	Image scaled_image;
	int index = shared_data->last_write_index%image_buffer_count;
	Snapshot *snap = &image_buffer[index];
	Image *snap_image = snap->image;

	if ( scale != 100 )
	{
		scaled_image.Assign( *snap_image );
		scaled_image.Scale( scale );
		snap_image = &scaled_image;
	}
	if ( !config.timestamp_on_capture )
	{
		TimestampImage( snap_image, snap->timestamp->tv_sec );
	}
	snap_image->EncodeJpeg( img_buffer, &img_buffer_size );
	
	fprintf( stdout, "Content-Length: %d\r\n", img_buffer_size );
	fprintf( stdout, "Content-Type: image/jpeg\r\n\r\n" );
	fwrite( img_buffer, img_buffer_size, 1, stdout );
}

void Monitor::SingleImageRaw( int scale)
{
	int last_read_index = shared_data->last_write_index;
	Image scaled_image;
	int index = shared_data->last_write_index%image_buffer_count;
	Snapshot *snap = &image_buffer[index];
	Image *snap_image = snap->image;

	if ( scale != 100 )
	{
		scaled_image.Assign( *snap_image );
		scaled_image.Scale( scale );
		snap_image = &scaled_image;
	}
	if ( !config.timestamp_on_capture )
	{
		TimestampImage( snap_image, snap->timestamp->tv_sec );
	}
	
	fprintf( stdout, "Content-Length: %d\r\n", snap_image->Size() );
	fprintf( stdout, "Content-Type: image/x-rgb\r\n\r\n" );
	fwrite( snap_image->Buffer(), snap_image->Size(), 1, stdout );
}

void Monitor::SingleImageZip( int scale)
{
	int last_read_index = shared_data->last_write_index;
	unsigned long img_buffer_size = 0;
	static Bytef img_buffer[ZM_MAX_IMAGE_SIZE];
	Image scaled_image;
	int index = shared_data->last_write_index%image_buffer_count;
	Snapshot *snap = &image_buffer[index];
	Image *snap_image = snap->image;

	if ( scale != 100 )
	{
		scaled_image.Assign( *snap_image );
		scaled_image.Scale( scale );
		snap_image = &scaled_image;
	}
	if ( !config.timestamp_on_capture )
	{
		TimestampImage( snap_image, snap->timestamp->tv_sec );
	}
	snap_image->Zip( img_buffer, &img_buffer_size );
	
	fprintf( stdout, "Content-Length: %d\r\n", img_buffer_size );
	fprintf( stdout, "Content-Type: image/x-rgbz\r\n\r\n" );
	fwrite( img_buffer, img_buffer_size, 1, stdout );
}

#if HAVE_LIBAVCODEC

void Monitor::StreamMpeg( const char *format, int scale, int maxfps, int bitrate )
{
	int fps = int(GetFPS());
	if ( !fps )
		fps = 5;

	int min_fps = 1;
	int max_fps = maxfps;
	int base_fps = int(GetFPS());
	int effective_fps = base_fps;

	int frame_mod = 1;
	// Min frame repeat?
	while( effective_fps > max_fps )
	{
		effective_fps /= 2;
		frame_mod *= 2;
	}

	Debug( 1, ( "BFPS:%d, EFPS:%d, FM:%d", base_fps, effective_fps, frame_mod ));

	VideoStream vid_stream( "pipe:", format, bitrate, effective_fps, camera->Colours(), (width*scale)/ZM_SCALE_SCALE, (height*scale)/ZM_SCALE_SCALE );

	fprintf( stdout, "Content-type: %s\r\n\r\n", vid_stream.MimeType() );
	vid_stream.OpenStream();

	int last_read_index = image_buffer_count;

	time_t stream_start_time;
	time( &stream_start_time );

	int frame_count = 0;
	struct timeval base_time;
	struct DeltaTimeval delta_time;
	Image scaled_image;
	while ( true )
	{
		if ( feof( stdout ) || ferror( stdout ) )
		{
			break;
		}
		if ( last_read_index != shared_data->last_write_index )
		{
			last_read_index = shared_data->last_write_index;
			if ( (frame_mod == 1) || ((frame_count%frame_mod) == 0) )
			{
				// Send the next frame
				int index = shared_data->last_write_index%image_buffer_count;
				//Info(( "%d: %x - %x", index, image_buffer[index].image, image_buffer[index].image->buffer ));
				Snapshot *snap = &image_buffer[index];
				Image *snap_image = snap->image;

				if ( scale != 100 )
				{
					scaled_image.Assign( *snap_image );

					scaled_image.Scale( scale );

					snap_image = &scaled_image;
				}
				if ( !config.timestamp_on_capture )
				{
					TimestampImage( snap_image, snap->timestamp->tv_sec );
				}

				if ( !frame_count )
				{
					base_time = *(snap->timestamp);
				}
				DELTA_TIMEVAL( delta_time, *(snap->timestamp), base_time, DT_PREC_3 );
				double pts = vid_stream.EncodeFrame( snap_image->Buffer(), snap_image->Size(), config.mpeg_timed_frames, delta_time.delta );
				//Info(( "FC:%d, DTD:%d, PTS:%lf", frame_count, delta_time.delta, pts ));
			}
			frame_count++;
		}
		usleep( ZM_SAMPLE_RATE );
	}
}
#endif // HAVE_LIBAVCODEC

int Monitor::PostCapture()
{
	if ( camera->PostCapture( image ) == 0 )
	{
		if ( orientation != ROTATE_0 )
		{
			switch ( orientation )
			{
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
			Error(( "Captured image does not match expected size, check width, height and colour depth" ));
			return( -1 );
		}

		int index = image_count%image_buffer_count;

		if ( index == shared_data->last_read_index && function > MONITOR )
		{
			Warning(( "Buffer overrun at index %d, slow down capture, speed up analysis or increase ring buffer size", index ));
		}

		gettimeofday( image_buffer[index].timestamp, &dummy_tz );
		if ( config.timestamp_on_capture )
		{
			TimestampImage( &image, image_buffer[index].timestamp->tv_sec );
		}
		image_buffer[index].image->CopyBuffer( image );

		shared_data->signal = CheckSignal( &image );
		shared_data->last_write_index = index;
		shared_data->last_image_time = image_buffer[index].timestamp->tv_sec;

		image_count++;

		if ( image_count && !(image_count%fps_report_interval) )
		{
			time_t now = image_buffer[index].timestamp->tv_sec;
			fps = double(fps_report_interval)/(now-last_fps_time);
			//Info(( "%d -> %d -> %d", fps_report_interval, now, last_fps_time ));
			//Info(( "%d -> %d -> %lf -> %lf", now-last_fps_time, fps_report_interval/(now-last_fps_time), double(fps_report_interval)/(now-last_fps_time), fps ));
			Info(( "%s: %d - Capturing at %.2lf fps", name, image_count, fps ));
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

void Monitor::TimestampImage( Image *ts_image, time_t ts_time ) const
{
	if ( label_format[0] )
	{
		static int token_count = -1;
		static char label_time_text[256];
		static char label_text[256];

		if ( token_count < 0 )
		{
			const char *token_ptr = label_format;
			const char *token_string = "%%s";
			token_count = 0;
			while( token_ptr = strstr( token_ptr, token_string ) )
			{
				token_count++;
				token_ptr += strlen(token_string);
			}
		}
		strftime( label_time_text, sizeof(label_time_text), label_format, localtime( &ts_time ) );
		switch ( token_count )
		{
			case 0:
			{
				strncpy( label_text, label_time_text, sizeof(label_text) );
				break;
			}
			case 1:
			{
				snprintf( label_text, sizeof(label_text), label_time_text, name );
				break;
			}
			case 2:
			{
				snprintf( label_text, sizeof(label_text), label_time_text, name, trigger_data->trigger_showtext );
				break;
			}
		}

		ts_image->Annotate( label_text, label_coord );
	}
}

bool Monitor::closeEvent()
{
	if ( event )
	{
		if ( function == RECORD || function == MOCORD )
		{
			gettimeofday( &(event->EndTime()), &dummy_tz );
		}
		delete event;
		event = 0;
		return( true );
	}
	return( false );
}

unsigned int Monitor::DetectMotion( const Image &comp_image, char *text_ptr, size_t text_size )
{
	bool alarm = false;
	unsigned int score = 0;
	char *orig_text_ptr = text_ptr;

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
		Debug( 3, ( "Blanking inactive zone %s", zone->Label() ));
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
		Debug( 3, ( "Checking preclusive zone %s", zone->Label() ));
		if ( zone->CheckAlarms( delta_image ) )
		{
			alarm = true;
			score += zone->Score();
			Debug( 3, ( "Zone is alarmed, zone score = %d", zone->Score() ));
			if ( text_ptr )
				text_ptr += snprintf( text_ptr, text_size-(text_ptr-orig_text_ptr), "%s%s", text_ptr!=orig_text_ptr?", ":"", zone->Label() );
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
			Debug( 3, ( "Checking active zone %s", zone->Label() ));
			if ( zone->CheckAlarms( delta_image ) )
			{
				alarm = true;
				score += zone->Score();
				zone->SetAlarm();
				Debug( 3, ( "Zone is alarmed, zone score = %d", zone->Score() ));
				if ( text_ptr )
					text_ptr += snprintf( text_ptr, text_size-(text_ptr-orig_text_ptr), "%s%s", text_ptr!=orig_text_ptr?", ":"", zone->Label() );
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
				Debug( 3, ( "Checking inclusive zone %s", zone->Label() ));
				if ( zone->CheckAlarms( delta_image ) )
				{
					alarm = true;
					score += zone->Score();
					zone->SetAlarm();
					Debug( 3, ( "Zone is alarmed, zone score = %d", zone->Score() ));
					if ( text_ptr )
						text_ptr += snprintf( text_ptr, text_size-(text_ptr-orig_text_ptr), "%s%s", text_ptr!=orig_text_ptr?", ":"", zone->Label() );
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
				Debug( 3, ( "Checking exclusive zone %s", zone->Label() ));
				if ( zone->CheckAlarms( delta_image ) )
				{
					alarm = true;
					score += zone->Score();
					zone->SetAlarm();
					Debug( 3, ( "Zone is alarmed, zone score = %d", zone->Score() ));
					if ( text_ptr )
						text_ptr += snprintf( text_ptr, text_size-(text_ptr-orig_text_ptr), "%s%s", text_ptr!=orig_text_ptr?", ":"", zone->Label() );
				}
			}
		}
	}

	if ( top_score > 0 )
	{
		shared_data->alarm_x = alarm_centre.X();
		shared_data->alarm_y = alarm_centre.Y();

		Info(( "Got alarm centre at %d,%d, at count %d", shared_data->alarm_x, shared_data->alarm_y, image_count ));
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
		sprintf( output+strlen(output), "Device : %s\n", ((LocalCamera *)camera)->Device() );
		sprintf( output+strlen(output), "Channel : %d\n", ((LocalCamera *)camera)->Channel() );
		sprintf( output+strlen(output), "Format : %d\n", ((LocalCamera *)camera)->Format() );
	}
	else if ( camera->IsRemote() )
	{
		sprintf( output+strlen(output), "Host : %s\n", ((RemoteCamera *)camera)->Host() );
		sprintf( output+strlen(output), "Port : %s\n", ((RemoteCamera *)camera)->Port() );
		sprintf( output+strlen(output), "Path : %s\n", ((RemoteCamera *)camera)->Path() );
	}
	else if ( camera->IsFile() )
	{
		sprintf( output+strlen(output), "Path : %s\n", ((FileCamera *)camera)->Path() );
	}
	sprintf( output+strlen(output), "Width : %d\n", camera->Width() );
	sprintf( output+strlen(output), "Height : %d\n", camera->Height() );
	sprintf( output+strlen(output), "Palette : %d\n", camera->Palette() );
	sprintf( output+strlen(output), "Colours : %d\n", camera->Colours() );
	sprintf( output+strlen(output), "Event Prefix : %s\n", event_prefix );
	sprintf( output+strlen(output), "Label Format : %s\n", label_format );
	sprintf( output+strlen(output), "Label Coord : %d,%d\n", label_coord.X(), label_coord.Y() );
	sprintf( output+strlen(output), "Image Buffer Count : %d\n", image_buffer_count );
	sprintf( output+strlen(output), "Warmup Count : %d\n", warmup_count );
	sprintf( output+strlen(output), "Pre Event Count : %d\n", pre_event_count );
	sprintf( output+strlen(output), "Post Event Count : %d\n", post_event_count );
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

