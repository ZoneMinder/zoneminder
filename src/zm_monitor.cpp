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

Monitor::Monitor( int p_id, char *p_name, int p_function, int p_device, int p_channel, int p_format, int p_width, int p_height, int p_colours, bool p_capture, char *p_label_format, const Coord &p_label_coord, int p_image_buffer_count, int p_warmup_count, int p_pre_event_count, int p_post_event_count, int p_capture_delay, int p_fps_report_interval, int p_ref_blend_perc, int p_n_zones, Zone *p_zones[] ) : id( p_id ), function( (Function)p_function ), label_coord( p_label_coord ), image_buffer_count( p_image_buffer_count ), warmup_count( p_warmup_count ), pre_event_count( p_pre_event_count ), post_event_count( p_post_event_count ), capture_delay( p_capture_delay ), fps_report_interval( p_fps_report_interval ), ref_blend_perc( p_ref_blend_perc ), image( p_width, p_height, p_colours ), ref_image( p_width, p_height, p_colours ), n_zones( p_n_zones ), zones( p_zones )
{
	name = new char[strlen(p_name)+1];
	strcpy( name, p_name );

    strcpy( label_format, p_label_format );

	camera = new LocalCamera( p_device, p_channel, p_format, p_width, p_height, p_colours, p_capture );

	fps = 0.0;
	event_count = 0;
	image_count = 0;
	first_alarm_count = 0;
	last_alarm_count = 0;
	state = IDLE;

	int shared_images_size = sizeof(SharedImages)+(image_buffer_count*sizeof(time_t))+(image_buffer_count*camera->ImageSize());
	Info(( "shm.size=%d", shared_images_size ));
	shmid = shmget( ZM_SHM_KEY|id, shared_images_size, IPC_CREAT|0777 );
	if ( shmid < 0 )
	{
		Error(( "Can't shmget: %s", strerror(errno)));
		exit( -1 );
	}
	unsigned char *shm_ptr = (unsigned char *)shmat( shmid, 0, 0 );
	shared_images = (SharedImages *)shm_ptr;
	if ( shared_images < 0 )
	{
		Error(( "Can't shmat: %s", strerror(errno)));
		exit( -1 );
	}

	if ( p_capture )
	{
		memset( shared_images, 0, shared_images_size );
		shared_images->state = IDLE;
		shared_images->last_write_index = image_buffer_count;
		shared_images->last_read_index = image_buffer_count;
		shared_images->last_event = 0;
		shared_images->force_state = FORCE_NEUTRAL;
	}
	shared_images->timestamps = (struct timeval *)(shm_ptr+sizeof(SharedImages));
	shared_images->images = (unsigned char *)(shm_ptr+sizeof(SharedImages)+(image_buffer_count*sizeof(struct timeval)));

	image_buffer = new Snapshot[image_buffer_count];
	for ( int i = 0; i < image_buffer_count; i++ )
	{
		image_buffer[i].timestamp = &(shared_images->timestamps[i]);
		image_buffer[i].image = new Image( camera->Width(), camera->Height(), camera->Colours(), &(shared_images->images[i*camera->ImageSize()]) );
	}
	if ( !n_zones )
	{
		n_zones = 1;
		zones = new Zone *[1];
		zones[0] = new Zone( this, 0, "All", Zone::ACTIVE, Box( camera->Width(), camera->Height() ), RGB_RED );
	}
	start_time = last_fps_time = time( 0 );

	event = 0;

	Info(( "Monitor %s has function %d", name, function ));
	Info(( "Monitor %s LBF = '%s', LBX = %d, LBY = %d", name, label_format, label_coord.X(), label_coord.Y() ));
	Info(( "Monitor %s IBC = %d, WUC = %d, pEC = %d, PEC = %d, FRI = %d, RBP = %d", name, image_buffer_count, warmup_count, pre_event_count, post_event_count, fps_report_interval, ref_blend_perc ));

	if ( !p_capture )
	{
		ref_image.Assign( camera->Width(), camera->Height(), camera->Colours(), image_buffer[shared_images->last_write_index].image->Buffer() );
	}
	else
	{
		static char	path[PATH_MAX];

		sprintf( path, ZM_DIR_EVENTS );

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

		sprintf( path, ZM_DIR_EVENTS "/%s", name );

		errno = 0;
		stat( path, &statbuf );
		if ( errno == ENOENT || errno == ENOTDIR )
		{
			if ( mkdir( path, 0755 ) )
			{
				Error(( "Can't make %s: %s", path, strerror(errno)));
			}
		}
	}

	record_event_stats = ZM_RECORD_EVENT_STATS;
}

Monitor::Monitor( int p_id, char *p_name, int p_function, const char *p_host, const char *p_port, const char *p_path, int p_width, int p_height, int p_colours, bool p_capture, char *p_label_format, const Coord &p_label_coord, int p_image_buffer_count, int p_warmup_count, int p_pre_event_count, int p_post_event_count, int p_capture_delay, int p_fps_report_interval, int p_ref_blend_perc, int p_n_zones, Zone *p_zones[] ) : id( p_id ), function( (Function)p_function ), label_coord( p_label_coord ), image_buffer_count( p_image_buffer_count ), warmup_count( p_warmup_count ), pre_event_count( p_pre_event_count ), post_event_count( p_post_event_count ), capture_delay( p_capture_delay ), fps_report_interval( p_fps_report_interval ), ref_blend_perc( p_ref_blend_perc ), image( p_width, p_height, p_colours ), ref_image( p_width, p_height, p_colours ), n_zones( p_n_zones ), zones( p_zones )
{
	name = new char[strlen(p_name)+1];
	strcpy( name, p_name );

    strcpy( label_format, p_label_format );

	camera = new RemoteCamera( p_host, p_port, p_path, p_width, p_height, p_colours, p_capture );

	fps = 0.0;
	event_count = 0;
	image_count = 0;
	first_alarm_count = 0;
	last_alarm_count = 0;
	state = IDLE;

	int shared_images_size = sizeof(SharedImages)+(image_buffer_count*sizeof(time_t))+(image_buffer_count*camera->ImageSize());
	shmid = shmget( ZM_SHM_KEY|id, shared_images_size, IPC_CREAT|0777 );
	if ( shmid < 0 )
	{
		Error(( "Can't shmget: %s", strerror(errno)));
		exit( -1 );
	}
	unsigned char *shm_ptr = (unsigned char *)shmat( shmid, 0, 0 );
	shared_images = (SharedImages *)shm_ptr;
	if ( shared_images < 0 )
	{
		Error(( "Can't shmat: %s", strerror(errno)));
		exit( -1 );
	}

	if ( p_capture )
	{
		memset( shared_images, 0, shared_images_size );
		shared_images->state = IDLE;
		shared_images->last_write_index = image_buffer_count;
		shared_images->last_read_index = image_buffer_count;
		shared_images->last_event = 0;
		shared_images->force_state = FORCE_NEUTRAL;
	}
	shared_images->timestamps = (struct timeval *)(shm_ptr+sizeof(SharedImages));
	shared_images->images = (unsigned char *)(shm_ptr+sizeof(SharedImages)+(image_buffer_count*sizeof(struct timeval)));

	image_buffer = new Snapshot[image_buffer_count];
	for ( int i = 0; i < image_buffer_count; i++ )
	{
		image_buffer[i].timestamp = &(shared_images->timestamps[i]);
		image_buffer[i].image = new Image( camera->Width(), camera->Height(), camera->Colours(), &(shared_images->images[i*camera->ImageSize()]) );
	}
	if ( !n_zones )
	{
		n_zones = 1;
		zones = new Zone *[1];
		zones[0] = new Zone( this, 0, "All", Zone::ACTIVE, Box( camera->Width(), camera->Height() ), RGB_RED );
	}
	start_time = last_fps_time = time( 0 );

	event = 0;

	Info(( "Monitor %s has function %d", name, function ));
	Info(( "Monitor %s LBF = '%s', LBX = %d, LBY = %d", name, label_format, label_coord.X(), label_coord.Y() ));
	Info(( "Monitor %s IBC = %d, WUC = %d, pEC = %d, PEC = %d, FRI = %d, RBP = %d", name, image_buffer_count, warmup_count, pre_event_count, post_event_count, fps_report_interval, ref_blend_perc ));

	if ( !p_capture )
	{
		ref_image.Assign( camera->Width(), camera->Height(), camera->Colours(), image_buffer[shared_images->last_write_index].image->Buffer() );
	}
	else
	{
		static char	path[PATH_MAX];

		sprintf( path, ZM_DIR_EVENTS );

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

		sprintf( path, ZM_DIR_EVENTS "/%s", name );

		errno = 0;
		stat( path, &statbuf );
		if ( errno == ENOENT || errno == ENOTDIR )
		{
			if ( mkdir( path, 0755 ) )
			{
				Error(( "Can't make %s: %s", path, strerror(errno)));
			}
		}
	}

	record_event_stats = ZM_RECORD_EVENT_STATS;
}

Monitor::~Monitor()
{
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

void Monitor::AddZones( int p_n_zones, Zone *p_zones[] )
{
	n_zones = p_n_zones;
	zones = p_zones;
}

unsigned long Monitor::GetNextCaptureDelay() const
{
	if ( capture_delay == 0 || shared_images->last_write_index < 0 )
	{
		return( 0 );
	}

	Snapshot *snap = &image_buffer[shared_images->last_write_index];

	if ( !snap || !snap->timestamp || !snap->timestamp->tv_sec )
	{
		return( 0 );
	}

	static struct timeval now;
	gettimeofday( &now, &dummy_tz );

	struct DeltaTimeval delta_time;
	DELTA_TIMEVAL( delta_time, now, (*(snap->timestamp)) );

	return( capture_delay-((delta_time.tv_sec*1000)+(delta_time.tv_usec/1000)) );
}

Monitor::State Monitor::GetState() const
{
	return( shared_images->state );
}

int Monitor::GetImage( int index ) const
{
	if ( index < 0 || index > image_buffer_count )
	{
		index = shared_images->last_write_index;
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
		index = shared_images->last_write_index;
	}
	Snapshot *snap = &image_buffer[index];
	return( *(snap->timestamp) );
}

unsigned int Monitor::GetLastReadIndex() const
{
	return( shared_images->last_read_index );
}

unsigned int Monitor::GetLastWriteIndex() const
{
	return( shared_images->last_write_index );
}

unsigned int Monitor::GetLastEvent() const
{
	return( shared_images->last_event );
}

double Monitor::GetFPS() const
{
	int index1 = shared_images->last_write_index;
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
	shared_images->force_state = FORCE_ON;
}

void Monitor::ForceAlarmOff()
{
	shared_images->force_state = FORCE_OFF;
}

void Monitor::CancelForced()
{
	shared_images->force_state = FORCE_NEUTRAL;
}

void Monitor::DumpZoneImage()
{
	int index = shared_images->last_write_index;
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
	if ( shared_images->last_read_index == shared_images->last_write_index )
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

	int read_margin = shared_images->last_read_index - shared_images->last_write_index;
	if ( read_margin < 0 ) read_margin += image_buffer_count;
	read_margin -= post_event_count;

	int step = 1;
	//int max_margin = image_buffer_count - (pre_event_count+post_event_count);
	int max_margin = image_buffer_count - post_event_count;
	if ( read_margin > 0 )
	{
		step = max_margin/(read_margin-pre_event_count);
	}

	Debug( 1, ( "RI:%d, WI: %d, RM = %d, Step = %d", shared_images->last_read_index, shared_images->last_write_index, read_margin, step ));
	int index;
	if ( step < (read_margin/2) )
	{
		index = (shared_images->last_read_index+step)%image_buffer_count;
	}
	else
	{
		Warning(( "Approaching buffer overrun, consider increasing ring buffer size" ));
		index = shared_images->last_write_index%image_buffer_count;
	}
	Snapshot *snap = &image_buffer[index];
	struct timeval *timestamp = snap->timestamp;
	Image *image = snap->image;

	unsigned int score = 0;
	if ( Ready() )
	{
		if ( shared_images->force_state != FORCE_OFF )
			score = Compare( *image );
		if ( shared_images->force_state == FORCE_ON )
			score = ZM_FORCED_ALARM_SCORE;

		if ( score )
		{
			if ( state == IDLE )
			{
				event = new Event( this, *timestamp );

				Info(( "%s: %03d - Gone into alarm state", name, image_count ));
				int pre_index = ((index+image_buffer_count)-pre_event_count)%image_buffer_count;
				struct timeval *timestamps[pre_event_count];
				const Image *images[pre_event_count];
				for ( int i = 0; i < pre_event_count; i++ )
				{
					pre_index = (pre_index+1)%image_buffer_count;

					timestamps[i] = image_buffer[pre_index].timestamp;
					images[i] = image_buffer[pre_index].image;
				}
				event->AddFrames( pre_event_count, timestamps, images );
				//event->AddFrame( now, &image );
			}
			shared_images->state = state = ALARM;
			last_alarm_count = image_count;
		}
		else
		{
			if ( state == ALARM )
			{
				shared_images->state = state = ALERT;
			}
			else if ( state == ALERT )
			{
				if ( image_count-last_alarm_count > post_event_count )
				{
					Info(( "%s: %03d - Left alarm state (%d) - %d(%d) images", name, image_count, event->Id(), event->Frames(), event->AlarmFrames() ));
					shared_images->last_event = event->Id();
					delete event;
					shared_images->state = state = IDLE;
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
			else
			{
				event->AddFrame( now, image );
			}
		}
	}

	if ( ZM_BLEND_ALARMED_IMAGES || state != ALARM )
	{
		ref_image.Blend( *image, ref_blend_perc );
		//DumpImage( image );
	}

	shared_images->last_read_index = index%image_buffer_count;
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

int Monitor::Load( int device, Monitor **&monitors, bool capture )
{
	static char sql[256];
	if ( device == -1 )
	{
		strcpy( sql, "select Id, Name, Function+0, Device, Channel, Format, Width, Height, Palette, LabelFormat, LabelX, LabelY, ImageBufferCount, WarmupCount, PreEventCount, PostEventCount, MaxFPS, FPSReportInterval, RefBlendPerc from Monitors where Function != 'None'" );
	}
	else
	{
		sprintf( sql, "select Id, Name, Function+0, Device, Channel, Format, Width, Height, Palette, LabelFormat, LabelX, LabelY, ImageBufferCount, WarmupCount, PreEventCount, PostEventCount, MaxFPS, FPSReportInterval, RefBlendPerc from Monitors where Function != 'None' and Device = %d", device );
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
		monitors[i] = new Monitor( atoi(dbrow[0]), dbrow[1], atoi(dbrow[2]), atoi(dbrow[3]), atoi(dbrow[4]), atoi(dbrow[5]), atoi(dbrow[6]), atoi(dbrow[7]), atoi(dbrow[8]), capture, dbrow[9], Coord( atoi(dbrow[10]), atoi(dbrow[11]) ), atoi(dbrow[12]), atoi(dbrow[13]), atoi(dbrow[14]), atoi(dbrow[15]), atof(dbrow[16])>0.0?int(1000.0/atof(dbrow[16])):0, atoi(dbrow[17]), atoi(dbrow[18]) );
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

int Monitor::Load( const char *host, const char*port, const char *path, Monitor **&monitors, bool capture )
{
	static char sql[256];
	if ( !host )
	{
		strcpy( sql, "select Id, Name, Function+0, Host, Port, Path, Width, Height, Palette, LabelFormat, LabelX, LabelY, ImageBufferCount, WarmupCount, PreEventCount, PostEventCount, MaxFPS, FPSReportInterval, RefBlendPerc from Monitors where Function != 'None'" );
	}
	else
	{
		sprintf( sql, "select Id, Name, Function+0, Host, Port, Path, Width, Height, Palette, LabelFormat, LabelX, LabelY, ImageBufferCount, WarmupCount, PreEventCount, PostEventCount, MaxFPS, FPSReportInterval, RefBlendPerc from Monitors where Function != 'None' and Host = '%s' and Port = '%s' and Path = '%s'", host, port, path );
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
		monitors[i] = new Monitor( atoi(dbrow[0]), dbrow[1], atoi(dbrow[2]), dbrow[3], dbrow[4], dbrow[5], atoi(dbrow[6]), atoi(dbrow[7]), atoi(dbrow[8]), capture, dbrow[9], Coord( atoi(dbrow[10]), atoi(dbrow[11]) ), atoi(dbrow[12]), atoi(dbrow[13]), atoi(dbrow[14]), atoi(dbrow[15]), atof(dbrow[16])>0.0?int(1000.0/atof(dbrow[16])):0, atoi(dbrow[17]), atoi(dbrow[18]) );
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

Monitor *Monitor::Load( int id, bool load_zones )
{
	static char sql[256];
	sprintf( sql, "select Id, Name, Type, Function+0, Device, Channel, Format, Host, Port, Path, Width, Height, Palette, LabelFormat, LabelX, LabelY, ImageBufferCount, WarmupCount, PreEventCount, PostEventCount, MaxFPS, FPSReportInterval, RefBlendPerc from Monitors where Id = %d", id );
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
			monitor = new Monitor( atoi(dbrow[0]), dbrow[1], atoi(dbrow[3]), atoi(dbrow[4]), atoi(dbrow[5]), atoi(dbrow[6]), atoi(dbrow[10]), atoi(dbrow[11]), atoi(dbrow[12]), false, dbrow[13], Coord( atoi(dbrow[14]), atoi(dbrow[15]) ), atoi(dbrow[16]), atoi(dbrow[17]), atoi(dbrow[18]), atoi(dbrow[19]), atof(dbrow[20])>0.0?int(1000.0/atof(dbrow[20])):0, atoi(dbrow[21]), atoi(dbrow[22]) );
		}
		else
		{
			monitor = new Monitor( atoi(dbrow[0]), dbrow[1], atoi(dbrow[3]), dbrow[7], dbrow[8], dbrow[9], atoi(dbrow[10]), atoi(dbrow[11]), atoi(dbrow[12]), false, dbrow[13], Coord( atoi(dbrow[14]), atoi(dbrow[15]) ), atoi(dbrow[16]), atoi(dbrow[17]), atoi(dbrow[18]), atoi(dbrow[19]), atof(dbrow[20])>0.0?int(1000.0/atof(dbrow[20])):0, atoi(dbrow[21]), atoi(dbrow[22]) );
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

	setbuf( fd, 0 );
	fprintf( fd, "Server: ZoneMinder Stream Server\r\n" );
	fprintf( fd, "Pragma: no-cache\r\n" );
	fprintf( fd, "Cache-Control: no-cache\r\n" );
	fprintf( fd, "Expires: Thu, 01 Dec 1994 16:00:00 GMT\r\n" );
	fprintf( fd, "Content-Type: multipart/x-mixed-replace;boundary=ZoneMinderFrame\r\n\r\n" );
	fprintf( fd, "--ZoneMinderFrame\r\n" );
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
		if ( last_read_index != shared_images->last_write_index )
		{
			// Send the next frame
			last_read_index = shared_images->last_write_index;
			int index = shared_images->last_write_index%image_buffer_count;
			//Info(( "%d: %x - %x", index, image_buffer[index].image, image_buffer[index].image->buffer ));
			Snapshot *snap = &image_buffer[index];
			Image *image = snap->image;
			image->EncodeJpeg( img_buffer, &img_buffer_size );

			fprintf( fd, "Content-type: image/jpg\r\n\r\n" );
			fwrite( img_buffer, 1, img_buffer_size, fd );
			fprintf( fd, "\r\n--ZoneMinderFrame\r\n" );
		}
		usleep( refresh*1000 );
		for ( int i = 0; shared_images->state == IDLE && i < loop_count; i++ )
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
	sprintf( output+strlen(output), "Width : %d\n", ((LocalCamera *)camera)->Width() );
	sprintf( output+strlen(output), "Height : %d\n", camera->Height() );
	sprintf( output+strlen(output), "Palette : %d\n", camera->Palette() );
	sprintf( output+strlen(output), "Colours : %d\n", camera->Colours() );
	sprintf( output+strlen(output), "Label Format : %s\n", label_format );
	sprintf( output+strlen(output), "Label Coord : %d,%d\n", label_coord.X(), label_coord.Y() );
	sprintf( output+strlen(output), "Image Buffer Count : %d\n", image_buffer_count );
	sprintf( output+strlen(output), "Warmup Count : %d\n", warmup_count );
	sprintf( output+strlen(output), "Pre Event Count : %d\n", pre_event_count );
	sprintf( output+strlen(output), "Post Event Count : %d\n", post_event_count );
	sprintf( output+strlen(output), "Maximum FPS : %.2f\n", capture_delay?1000/capture_delay:0.0 );
	sprintf( output+strlen(output), "Reference Blend %%ge : %d\n", ref_blend_perc );
	sprintf( output+strlen(output), "Function: %d - %s\n", function,
		function==NONE?"None":(
		function==ACTIVE?"Active":(
		function==PASSIVE?"Passive":(
		function==X10?"X10":"Unknown"
	))));
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
