//
// ZoneMinder Monitor Class Implementation, $Date$, $Revision$
// Copyright (C) 2003  Philip Coombes
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
#include "zm_monitor.h"
#include "zm_local_camera.h"
#include "zm_remote_camera.h"

Monitor::Monitor(
	int p_id,
	char *p_name,
	int p_function,
	int p_device,
	int p_channel,
	int p_format,
	int p_width,
	int p_height,
	int p_palette,
	int p_orientation,
	char *p_label_format,
	const Coord &p_label_coord,
	int p_image_buffer_count,
	int p_warmup_count,
	int p_pre_event_count,
	int p_post_event_count,
	int p_section_length,
	int p_frame_skip,
	int p_capture_delay,
	int p_fps_report_interval,
	int p_ref_blend_perc,
	Purpose p_purpose,
	int p_n_zones,
	Zone *p_zones[]
) : id( p_id ),
	function( (Function)p_function ),
	width( p_width ),
	height( p_height ),
	orientation( (Orientation)p_orientation ),
	label_coord( p_label_coord ),
	image_buffer_count( p_image_buffer_count ),
	warmup_count( p_warmup_count ),
	pre_event_count( p_pre_event_count ),
	post_event_count( p_post_event_count ),
	section_length( p_section_length ),
	frame_skip( p_frame_skip ),
	capture_delay( p_capture_delay ),
	fps_report_interval( p_fps_report_interval ),
	ref_blend_perc( p_ref_blend_perc ),
	image( width, height, (p_palette==VIDEO_PALETTE_GREY?1:3) ),
	ref_image( width, height, (p_palette==VIDEO_PALETTE_GREY?1:3) ),
	purpose( p_purpose ),
	n_zones( p_n_zones ),
	zones( p_zones )
{
	name = new char[strlen(p_name)+1];
	strcpy( name, p_name );

    strcpy( label_format, p_label_format );

	camera = new LocalCamera( p_device, p_channel, p_format, (p_orientation%2)?width:height, (orientation%2)?height:width, p_palette, purpose==CAPTURE );

	Initialise();
}

Monitor::Monitor(
	int p_id,
	char *p_name,
	int p_function,
	const char *p_host,
	const char *p_port,
	const char *p_path,
	int p_width,
	int p_height,
	int p_palette,
	int p_orientation,
	char *p_label_format,
	const Coord &p_label_coord,
	int p_image_buffer_count,
	int p_warmup_count,
	int p_pre_event_count,
	int p_post_event_count,
	int p_section_length,
	int p_frame_skip,
	int p_capture_delay,
	int p_fps_report_interval,
	int p_ref_blend_perc,
	Purpose p_purpose,
	int p_n_zones,
	Zone *p_zones[]
) : id( p_id ),
	function( (Function)p_function ),
	width( p_width ),
	height( p_height ),
	orientation( (Orientation)p_orientation ),
	label_coord( p_label_coord ),
	image_buffer_count( p_image_buffer_count ),
	warmup_count( p_warmup_count ),
	pre_event_count( p_pre_event_count ),
	post_event_count( p_post_event_count ),
	section_length( p_section_length ),
	frame_skip( p_frame_skip ),
	capture_delay( p_capture_delay ),
	fps_report_interval( p_fps_report_interval ),
	ref_blend_perc( p_ref_blend_perc ),
	image( width, height, (p_palette==VIDEO_PALETTE_GREY?1:3) ),
	ref_image( width, height, (p_palette==VIDEO_PALETTE_GREY?1:3) ),
	purpose( p_purpose ),
	n_zones( p_n_zones ),
	zones( p_zones )
{
	name = new char[strlen(p_name)+1];
	strcpy( name, p_name );

    strcpy( label_format, p_label_format );

	camera = new RemoteCamera( p_host, p_port, p_path, (p_orientation%2)?width:height, (orientation%2)?height:width, p_palette, purpose==CAPTURE );

	Initialise();
}

Monitor::~Monitor()
{
	if ( event )
	{
		if ( function == RECORD || function == MOCORD )
		{
			gettimeofday( &(event->EndTime()), &dummy_tz );
		}
		delete event;
	}
	delete[] image_buffer;

	struct shmid_ds shm_data;
	if ( shmctl( shmid, IPC_STAT, &shm_data ) )
	{
		Error(( "Can't shmctl: %s", strerror(errno)));
		exit( -1 );
	}

	if ( shm_data.shm_nattch <= 1 )
	{
		if ( shmctl( shmid, IPC_RMID, 0 ) )
		{
			Error(( "Can't shmctl: %s", strerror(errno)));
			exit( -1 );
		}
	}
}

void Monitor::Initialise()
{
	fps = 0.0;
	event_count = 0;
	image_count = 0;
	first_alarm_count = 0;
	last_alarm_count = 0;
	state = IDLE;

	Info(( "monitor purpose=%d", purpose ));

	int shared_data_size = sizeof(SharedData)+(image_buffer_count*sizeof(time_t))+(image_buffer_count*camera->ImageSize());
	Info(( "shm.size=%d", shared_data_size ));
	shmid = shmget( (int)config.Item( ZM_SHM_KEY )|id, shared_data_size, IPC_CREAT|0700 );
	if ( shmid < 0 )
	{
		Error(( "Can't shmget: %s", strerror(errno)));
		exit( -1 );
	}
	unsigned char *shm_ptr = (unsigned char *)shmat( shmid, 0, 0 );
	shared_data = (SharedData *)shm_ptr;
	if ( shared_data < 0 )
	{
		Error(( "Can't shmat: %s", strerror(errno)));
		exit( -1 );
	}

	if ( purpose == CAPTURE )
	{
		memset( shared_data, 0, shared_data_size );
		shared_data->valid = true;
		shared_data->state = IDLE;
		shared_data->force_state = FORCE_NEUTRAL;
		shared_data->last_write_index = image_buffer_count;
		shared_data->last_read_index = image_buffer_count;
		shared_data->last_image_time = 0;
		shared_data->last_event = 0;
		shared_data->action = (Action)0;
		shared_data->brightness = -1;
		shared_data->hue = -1;
		shared_data->colour = -1;
		shared_data->contrast = -1;
	}
	if ( !shared_data->valid )
	{
		Error(( "Shared memory not initialised by capture daemon" ));
		exit( -1 );
	}

	struct timeval *shared_timestamps = (struct timeval *)(shm_ptr+sizeof(SharedData));
	unsigned char *shared_images = (unsigned char *)(shm_ptr+sizeof(SharedData)+(image_buffer_count*sizeof(struct timeval)));
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
		zones[0] = new Zone( this, 0, "All", Zone::ACTIVE, Box( width, height ), RGB_RED );
	}
	start_time = last_fps_time = time( 0 );

	event = 0;

	Info(( "Monitor %s has function %d", name, function ));
	Info(( "Monitor %s LBF = '%s', LBX = %d, LBY = %d", name, label_format, label_coord.X(), label_coord.Y() ));
	Info(( "Monitor %s IBC = %d, WUC = %d, pEC = %d, PEC = %d, FRI = %d, RBP = %d", name, image_buffer_count, warmup_count, pre_event_count, post_event_count, fps_report_interval, ref_blend_perc ));

	record_event_stats = (bool)config.Item( ZM_RECORD_EVENT_STATS );

	if ( purpose == ANALYSIS )
	{
		static char	path[PATH_MAX];

		strcpy( path, (const char *)config.Item( ZM_DIR_EVENTS ) );

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

		sprintf( path, "%s/%s", (const char *)config.Item( ZM_DIR_EVENTS ), name );

		errno = 0;
		stat( path, &statbuf );
		if ( errno == ENOENT || errno == ENOTDIR )
		{
			if ( mkdir( path, 0755 ) )
			{
				Error(( "Can't make %s: %s", path, strerror(errno)));
			}
		}

		while( shared_data->last_write_index == image_buffer_count )
		{
			Warning(( "Waiting for capture daemon" ));
			sleep( 1 );
		}
		ref_image.Assign( width, height, camera->Colours(), image_buffer[shared_data->last_write_index].image->Buffer() );
	}
}

void Monitor::AddZones( int p_n_zones, Zone *p_zones[] )
{
	n_zones = p_n_zones;
	zones = p_zones;
}

Monitor::State Monitor::GetState() const
{
	return( shared_data->state );
}

int Monitor::GetImage( int index ) const
{
	if ( index < 0 || index > image_buffer_count )
	{
		index = shared_data->last_write_index;
	}
	Snapshot *snap = &image_buffer[index];
	Image *image = snap->image;

	char filename[64];
	sprintf( filename, "%s.jpg", name );
	image->WriteJpeg( filename );
	return( 0 );
}

struct timeval Monitor::GetTimestamp( int index ) const
{
	if ( index < 0 || index > image_buffer_count )
	{
		index = shared_data->last_write_index;
	}

	Info(( "Index %d = %x", index, image_buffer[index].timestamp ));
	Info(( "Timestamp %d.%d", image_buffer[index].timestamp->tv_sec, image_buffer[index].timestamp->tv_usec ));

	Snapshot *snap = &image_buffer[index];
	return( *(snap->timestamp) );
}

unsigned int Monitor::GetLastReadIndex() const
{
	return( shared_data->last_read_index );
}

unsigned int Monitor::GetLastWriteIndex() const
{
	return( shared_data->last_write_index );
}

unsigned int Monitor::GetLastEvent() const
{
	return( shared_data->last_event );
}

double Monitor::GetFPS() const
{
	int index1 = shared_data->last_write_index;
	int index2 = (index1+1)%image_buffer_count;

	//Snapshot *snap1 = &image_buffer[index1];
	//time_t time1 = snap1->timestamp->tv_sec;
	time_t time1 = time( 0 );

	Snapshot *snap2 = &image_buffer[index2];
	time_t time2 = snap2->timestamp->tv_sec;

	double fps = double(image_buffer_count)/(time1-time2);

	return( fps );
}

void Monitor::ForceAlarmOn()
{
	shared_data->force_state = FORCE_ON;
}

void Monitor::ForceAlarmOff()
{
	shared_data->force_state = FORCE_OFF;
}

void Monitor::CancelForced()
{
	shared_data->force_state = FORCE_NEUTRAL;
}

int Monitor::Brightness( int p_brightness )
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

int Monitor::Contrast( int p_contrast )
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

int Monitor::Hue( int p_hue )
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

int Monitor::Colour( int p_colour )
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

void Monitor::DumpZoneImage()
{
	int index = shared_data->last_write_index;
	Snapshot *snap = &image_buffer[index];
	Image *image = snap->image;

	Image zone_image( *image );
	zone_image.Colourise();
	for( int i = 0; i < n_zones; i++ )
	{
		Rgb colour;
		if ( zones[i]->IsActive() )
		{
			colour = RGB_RED;
		}
		else if ( zones[i]->IsInclusive() )
		{
			colour = RGB_GREEN;
		}
		else if ( zones[i]->IsExclusive() )
		{
			colour = RGB_BLUE;
		}
		else if ( zones[i]->IsPreclusive() )
		{
			colour = RGB_BLACK;
		}
		else
		{
			colour = RGB_WHITE;
		}
		zone_image.Hatch( colour, &(zones[i]->Limits()) );
	}
	char filename[64];
	sprintf( filename, "%s-Zones.jpg", name );
	zone_image.WriteJpeg( filename );
}

void Monitor::DumpImage( Image *image ) const
{
	if ( image_count && !(image_count%10) )
	{
		static char new_filename[64];
		static char filename[64];
		sprintf( filename, "%s.jpg", name );
		sprintf( new_filename, "%s-new.jpg", name );
		image->WriteJpeg( new_filename );
		rename( new_filename, filename );
	}
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
	if ( (bool)config.Item( ZM_OPT_ADAPTIVE_SKIP ) )
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

		Debug( 1, ( "RI:%d, WI: %d, PF = %d, RM = %d, Step = %d", shared_data->last_read_index, shared_data->last_write_index, pending_frames, read_margin, step ));
		if ( step <= pending_frames )
		{
			index = (shared_data->last_read_index+step)%image_buffer_count;
		}
		else
		{
			if ( pending_frames )
			{
				Warning(( "Approaching buffer overrun, consider increasing ring buffer size" ));
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
	Image *image = snap->image;

	unsigned int score = 0;
	if ( Ready() )
	{
		if ( function != RECORD && shared_data->force_state != FORCE_OFF )
			score = Compare( *image );
		if ( shared_data->force_state == FORCE_ON )
			score = (int)config.Item( ZM_FORCED_ALARM_SCORE );

		if ( function == RECORD || function == MOCORD )
		{
			if ( !event )
			{
				// Create event
				event = new Event( this, *timestamp );

				Info(( "%s: %03d - Starting new event", name, image_count ));

				//if ( (bool)config.Item( ZM_OVERLAP_TIMED_EVENTS ) )
				if ( 1 )
				{
					int pre_index = ((index+image_buffer_count)-pre_event_count)%image_buffer_count;
					struct timeval *timestamps[pre_event_count];
					const Image *images[pre_event_count];
					for ( int i = 0; i < pre_event_count; i++ )
					{
						timestamps[i] = image_buffer[pre_index].timestamp;
						images[i] = image_buffer[pre_index].image;

						pre_index = (pre_index+1)%image_buffer_count;
					}
					event->AddFrames( pre_event_count, timestamps, images );
					//event->AddFrame( now, &image );
				}
				shared_data->state = state = TAPE;
			}
			//last_alarm_count = image_count;
		}
		if ( score )
		{
			if ( state == IDLE )
			{
				Info(( "%s: %03d - Gone into alarm state", name, image_count ));
				if ( function != MOCORD )
				{
					event = new Event( this, *timestamp );

					int pre_index = ((index+image_buffer_count)-pre_event_count)%image_buffer_count;
					struct timeval *timestamps[pre_event_count];
					const Image *images[pre_event_count];
					for ( int i = 0; i < pre_event_count; i++ )
					{
						timestamps[i] = image_buffer[pre_index].timestamp;
						images[i] = image_buffer[pre_index].image;

						pre_index = (pre_index+1)%image_buffer_count;
					}
					event->AddFrames( pre_event_count, timestamps, images );
					//event->AddFrame( now, &image );
				}
			}
			shared_data->state = state = ALARM;
			last_alarm_count = image_count;
		}
		else
		{
			if ( state == ALARM )
			{
				shared_data->state = state = ALERT;
			}
			else if ( state == ALERT )
			{
				if ( image_count-last_alarm_count > post_event_count )
				{
					Info(( "%s: %03d - Left alarm state (%d) - %d(%d) images", name, image_count, event->Id(), event->Frames(), event->AlarmFrames() ));
					shared_data->last_event = event->Id();
					if ( function != MOCORD )
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
		}
		if ( state != IDLE )
		{
			if ( state == ALARM )
			{
				Image alarm_image( *image );
				for( int i = 0; i < n_zones; i++ )
				{
					if ( zones[i]->Alarmed() )
					{
						alarm_image.Overlay( zones[i]->AlarmImage() );
						if ( record_event_stats )
						{
							zones[i]->RecordStats( event );
						}
					}
				}
				event->AddFrame( now, image, &alarm_image, score );
			}
			else if ( state == ALERT )
			{
				event->AddFrame( now, image );
			}
			else if ( state == TAPE )
			{
				if ( !(image_count%(frame_skip+1)) )
				{
					event->AddFrame( now, image );
				}
			}
		}
		if ( function == RECORD || function == MOCORD )
		{
			if ( state == IDLE || state == TAPE )
			{
				if ( ((timestamp->tv_sec%section_length) == 0) && ((timestamp->tv_sec - event->StartTime().tv_sec) > (section_length/2)) )
				{
					Info(( "Ended event" ));
					gettimeofday( &(event->EndTime()), &dummy_tz );
					delete event;
					event = 0;
				}
			}
		}
	}

	if ( (bool)config.Item( ZM_BLEND_ALARMED_IMAGES ) || state != ALARM )
	{
		ref_image.Blend( *image, ref_blend_perc );
		//DumpImage( image );
	}

	shared_data->last_read_index = index%image_buffer_count;
	image_count++;

	return( true );
}

void Monitor::ReloadZones()
{
	Info(( "Reloading zones for monitor %s", name ));
	for( int i = 0; i < n_zones; i++ )
	{
		delete zones[i];
	}
	//delete[] zones;
	n_zones = Zone::Load( this, zones );
	DumpZoneImage();
}

int Monitor::Load( int device, Monitor **&monitors, Purpose purpose )
{
	static char sql[BUFSIZ];
	if ( device == -1 )
	{
		strcpy( sql, "select Id, Name, Function+0, Device, Channel, Format, Width, Height, Palette, Orientation+0, LabelFormat, LabelX, LabelY, ImageBufferCount, WarmupCount, PreEventCount, PostEventCount, SectionLength, FrameSkip, MaxFPS, FPSReportInterval, RefBlendPerc from Monitors where Function != 'None' and Type = 'Local'" );
	}
	else
	{
		sprintf( sql, "select Id, Name, Function+0, Device, Channel, Format, Width, Height, Palette, Orientation+0, LabelFormat, LabelX, LabelY, ImageBufferCount, WarmupCount, PreEventCount, PostEventCount, SectionLength, FrameSkip, MaxFPS, FPSReportInterval, RefBlendPerc from Monitors where Function != 'None' and Type = 'Local' and Device = %d", device );
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
	Info(( "Got %d monitors", n_monitors ));
	delete[] monitors;
	monitors = new Monitor *[n_monitors];
	for( int i = 0; MYSQL_ROW dbrow = mysql_fetch_row( result ); i++ )
	{
		monitors[i] = new Monitor(
			atoi(dbrow[0]), // Id
			dbrow[1], // Name
			atoi(dbrow[2]), // Function
			atoi(dbrow[3]), // Device
			atoi(dbrow[4]), // Channel
			atoi(dbrow[5]), // Format
			atoi(dbrow[6]), // Width
			atoi(dbrow[7]), // Height
			atoi(dbrow[8]), // Palette
			atoi(dbrow[9]), // Orientation
			dbrow[10], // LabelFormat
			Coord( atoi(dbrow[11]), atoi(dbrow[12]) ), // LabelX, LabelY
			atoi(dbrow[13]), // ImageBufferCount
			atoi(dbrow[14]), // WarmupCount
			atoi(dbrow[15]), // PreEventCount
			atoi(dbrow[16]), // PostEventCount
			atoi(dbrow[17]), // SectionLength
			atoi(dbrow[18]), // FrameSkip
			atof(dbrow[19])>0.0?int(DT_PREC_3/atof(dbrow[19])):0, // MaxFPS
			atoi(dbrow[20]), // FPSReportInterval
			atoi(dbrow[21]), // RefBlendPerc
			purpose
		);
		Zone **zones = 0;
		int n_zones = Zone::Load( monitors[i], zones );
		monitors[i]->AddZones( n_zones, zones );
		Info(( "Loaded monitor %d(%s), %d zones", atoi(dbrow[0]), dbrow[1], n_zones ));
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

int Monitor::Load( const char *host, const char*port, const char *path, Monitor **&monitors, Purpose purpose )
{
	static char sql[BUFSIZ];
	if ( !host )
	{
		strcpy( sql, "select Id, Name, Function+0, Host, Port, Path, Width, Height, Palette, Orientation+0, LabelFormat, LabelX, LabelY, ImageBufferCount, WarmupCount, PreEventCount, PostEventCount, SectionLength, FrameSkip, MaxFPS, FPSReportInterval, RefBlendPerc from Monitors where Function != 'None' and Type = 'Remote'" );
	}
	else
	{
		sprintf( sql, "select Id, Name, Function+0, Host, Port, Path, Width, Height, Palette, Orientation+0, LabelFormat, LabelX, LabelY, ImageBufferCount, WarmupCount, PreEventCount, PostEventCount, SectionLength, FrameSkip, MaxFPS, FPSReportInterval, RefBlendPerc from Monitors where Function != 'None' and Type = 'Remote' and Host = '%s' and Port = '%s' and Path = '%s'", host, port, path );
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
	Info(( "Got %d monitors", n_monitors ));
	delete[] monitors;
	monitors = new Monitor *[n_monitors];
	for( int i = 0; MYSQL_ROW dbrow = mysql_fetch_row( result ); i++ )
	{
		monitors[i] = new Monitor(
			atoi(dbrow[0]), // Id
			dbrow[1], // Name
			atoi(dbrow[2]), // Function
			dbrow[3], // Host
			dbrow[4], // Port
			dbrow[5], // Path
			atoi(dbrow[6]), // Width
			atoi(dbrow[7]), // Height
			atoi(dbrow[8]), // Palette
			atoi(dbrow[9]), // Orientation
			dbrow[10], // LabelFormat
			Coord( atoi(dbrow[11]), atoi(dbrow[12]) ), // LabelX, LabelY
			atoi(dbrow[13]), // ImageBufferCount
			atoi(dbrow[14]), // WarmupCount
			atoi(dbrow[15]), // PreEventCount
			atoi(dbrow[16]), // PostEventCount
			atoi(dbrow[17]), // SectionLength
			atoi(dbrow[18]), // FrameSkip
			atof(dbrow[19])>0.0?int(DT_PREC_3/atof(dbrow[19])):0, // MaxFPS
			atoi(dbrow[20]), // FPSReportInterval
			atoi(dbrow[21]), // RefBlendPerc
			purpose
		);
		Zone **zones = 0;
		int n_zones = Zone::Load( monitors[i], zones );
		monitors[i]->AddZones( n_zones, zones );
		Info(( "Loaded monitor %d(%s), %d zones", atoi(dbrow[0]), dbrow[1], n_zones ));
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
	sprintf( sql, "select Id, Name, Type, Function+0, Device, Channel, Format, Host, Port, Path, Width, Height, Palette, Orientation+0, LabelFormat, LabelX, LabelY, ImageBufferCount, WarmupCount, PreEventCount, PostEventCount, SectionLength, FrameSkip, MaxFPS, FPSReportInterval, RefBlendPerc from Monitors where Id = %d", id );
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
	Info(( "Got %d monitors", n_monitors ));
	Monitor *monitor = 0;
	for( int i = 0; MYSQL_ROW dbrow = mysql_fetch_row( result ); i++ )
	{
		if ( !strcmp( dbrow[2], "Local" ) )
		{
			monitor = new Monitor(
				atoi(dbrow[0]), // Id
				dbrow[1], // Name
				atoi(dbrow[3]), // Function
				atoi(dbrow[4]), // Device
				atoi(dbrow[5]), // Channel
				atoi(dbrow[6]), // Format
				atoi(dbrow[10]), // Width
				atoi(dbrow[11]), // Height
				atoi(dbrow[12]), // Palette
				atoi(dbrow[13]), // Orientation
				dbrow[14], // LabelFormat
				Coord( atoi(dbrow[15]), atoi(dbrow[16]) ), // LabelX, LabelY
				atoi(dbrow[17]), // ImageBufferCount
				atoi(dbrow[18]), // WarmupCount
				atoi(dbrow[19]), // PreEventCount
				atoi(dbrow[20]), // PostEventCount
				atoi(dbrow[21]), // SectionLength
				atoi(dbrow[22]), // FrameSkip
				atof(dbrow[23])>0.0?int(DT_PREC_3/atof(dbrow[23])):0, // MaxFPS
				atoi(dbrow[24]), // FPSReportInterval
				atoi(dbrow[25]), // RefBlendPerc
				purpose
			);
		}
		else
		{
			monitor = new Monitor(
				atoi(dbrow[0]), // Id
				dbrow[1], // Name
				atoi(dbrow[3]), // Function
				dbrow[7], // Host
				dbrow[8], // Port
				dbrow[9], // Path
				atoi(dbrow[10]), // Width
				atoi(dbrow[11]), // Height
				atoi(dbrow[12]), // Palette
				atoi(dbrow[13]), // Orientation
				dbrow[14], // LabelFormat
				Coord( atoi(dbrow[15]), atoi(dbrow[16]) ), // LabelX, LabelY
				atoi(dbrow[17]), // ImageBufferCount
				atoi(dbrow[18]), // WarmupCount
				atoi(dbrow[19]), // PreEventCount
				atoi(dbrow[20]), // PostEventCount
				atoi(dbrow[21]), // SectionLength
				atoi(dbrow[22]), // FrameSkip
				atof(dbrow[23])>0.0?int(DT_PREC_3/atof(dbrow[23])):0, // MaxFPS
				atoi(dbrow[24]), // FPSReportInterval
				atoi(dbrow[25]), // RefBlendPerc
				purpose
			);
		}
		int n_zones = 0;
		if ( load_zones )
		{
			Zone **zones = 0;
			n_zones = Zone::Load( monitor, zones );
			monitor->AddZones( n_zones, zones );
		}
		Info(( "Loaded monitor %d(%s), %d zones", atoi(dbrow[0]), dbrow[1], n_zones ));
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

void Monitor::StreamImages( unsigned long idle, unsigned long refresh, FILE *fd, time_t ttl )
{
	time_t start_time, now;

	//setbuf( fd, 0 );
	fprintf( fd, "Server: ZoneMinder Stream Server\r\n" );
	fprintf( fd, "Pragma: no-cache\r\n" );
	fprintf( fd, "Cache-Control: no-cache\r\n" );
	fprintf( fd, "Expires: Thu, 01 Dec 1994 16:00:00 GMT\r\n" );
	fprintf( fd, "Content-Type: multipart/x-mixed-replace;boundary=ZoneMinderFrame\r\n\r\n" );
	fprintf( fd, "--ZoneMinderFrame\n" );
	int last_read_index = image_buffer_count;
	JOCTET img_buffer[camera->ImageSize()];
	int img_buffer_size = 0;
	int loop_count = (idle/refresh)-1;
	time( &start_time );
	while ( true )
	{
		if ( feof( fd ) || ferror( fd ) )
		{
			break;
		}
		if ( last_read_index != shared_data->last_write_index )
		{
			// Send the next frame
			last_read_index = shared_data->last_write_index;
			int index = shared_data->last_write_index%image_buffer_count;
			//Info(( "%d: %x - %x", index, image_buffer[index].image, image_buffer[index].image->buffer ));
			Snapshot *snap = &image_buffer[index];
			Image *image = snap->image;
			image->EncodeJpeg( img_buffer, &img_buffer_size );

			fprintf( fd, "Content-type: image/jpg\n\n" );
			fwrite( img_buffer, 1, img_buffer_size, fd );
			fprintf( fd, "\n--ZoneMinderFrame\n" );
		}
		usleep( refresh*1000 );
		for ( int i = 0; shared_data->state == IDLE && i < loop_count; i++ )
		{
			usleep( refresh*1000 );
		}
		if ( ttl )
		{
			time( &now );
			if ( (now - start_time) > ttl )
			{
				break;
			}
		}
	}
}

bool Monitor::DumpSettings( char *output, bool verbose )
{
	output[0] = 0;

	sprintf( output+strlen(output), "Id : %d\n", id );
	sprintf( output+strlen(output), "Name : %s\n", name );
	sprintf( output+strlen(output), "Type : %s\n", camera->IsLocal()?"Local":"Remote" );
	if ( camera->IsLocal() )
	{
		sprintf( output+strlen(output), "Device : %d\n", ((LocalCamera *)camera)->Device() );
		sprintf( output+strlen(output), "Channel : %d\n", ((LocalCamera *)camera)->Channel() );
		sprintf( output+strlen(output), "Format : %d\n", ((LocalCamera *)camera)->Format() );
	}
	else
	{
		sprintf( output+strlen(output), "Host : %s\n", ((RemoteCamera *)camera)->Host() );
		sprintf( output+strlen(output), "Port : %s\n", ((RemoteCamera *)camera)->Port() );
		sprintf( output+strlen(output), "Path : %s\n", ((RemoteCamera *)camera)->Path() );
	}
	sprintf( output+strlen(output), "Width : %d\n", camera->Width() );
	sprintf( output+strlen(output), "Height : %d\n", camera->Height() );
	sprintf( output+strlen(output), "Palette : %d\n", camera->Palette() );
	sprintf( output+strlen(output), "Colours : %d\n", camera->Colours() );
	sprintf( output+strlen(output), "Label Format : %s\n", label_format );
	sprintf( output+strlen(output), "Label Coord : %d,%d\n", label_coord.X(), label_coord.Y() );
	sprintf( output+strlen(output), "Image Buffer Count : %d\n", image_buffer_count );
	sprintf( output+strlen(output), "Warmup Count : %d\n", warmup_count );
	sprintf( output+strlen(output), "Pre Event Count : %d\n", pre_event_count );
	sprintf( output+strlen(output), "Post Event Count : %d\n", post_event_count );
	sprintf( output+strlen(output), "Section Length : %d\n", section_length );
	sprintf( output+strlen(output), "Maximum FPS : %.2f\n", capture_delay?DT_PREC_3/capture_delay:0.0 );
	sprintf( output+strlen(output), "Reference Blend %%ge : %d\n", ref_blend_perc );
	sprintf( output+strlen(output), "Function: %d - %s\n", function,
		function==OFF?"None":(
		function==MONITOR?"Monitor":(
		function==MODECT?"Motion Detection":(
		function==RECORD?"Continuous Record":(
		function==MOCORD?"Continuous Record with Motion Detection":"Unknown"
	)))));
	sprintf( output+strlen(output), "Zones : %d\n", n_zones );
	for ( int i = 0; i < n_zones; i++ )
	{
		zones[i]->DumpSettings( output+strlen(output), verbose );
    }
	return( true );
}

unsigned int Monitor::Compare( const Image &image )
{
	bool alarm = false;
	unsigned int score = 0;

	if ( n_zones <= 0 ) return( alarm );

	Image *delta_image = ref_image.Delta( image );

	// Blank out all exclusion zones
	for ( int n_zone = 0; n_zone < n_zones; n_zone++ )
	{
		Zone *zone = zones[n_zone];
		zone->ClearAlarm();
		Debug( 3, ( "Blanking inactive zone %s", zone->Label() ));
		if ( !zone->IsInactive() )
		{
			continue;
		}

		delta_image->Fill( RGB_BLACK, &(zone->Limits()) );
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
			zone->ResetStats();
		}
	}

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
				}
			}
		}
		else
		{
			// Find all alarm pixels in exclusion zones
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
				}
			}
		}
	}

	delete delta_image;
	// This is a small and innocent hack to prevent scores of 0 being returned in alarm state
	return( score?score:alarm );
} 
