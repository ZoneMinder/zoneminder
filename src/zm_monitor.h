//
// ZoneMinder Monitor Class Interfaces, $Date$, $Revision$
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

#ifndef ZM_MONITOR_H
#define ZM_MONITOR_H

#include <sys/time.h>

static struct timezone dummy_tz; // To avoid declaring pointless one each time we use gettimeofday

#include "zm_coord.h"
#include "zm_image.h"
#include "zm_zone.h"
#include "zm_camera.h"

//
// This is the main class for monitors. Each monitor is associated
// with a camera and is effectivaly a collector for events.
//
class Monitor
{
public:
	typedef enum
	{
		QUERY=0,
		CAPTURE,
		ANALYSIS
	} Purpose;

	typedef enum
	{
		CONTINUOUS=0,
		TRIGGERED
	} RunMode;

	typedef enum
	{
		OFF=1,
		MONITOR,
		MODECT,
		RECORD,
		MOCORD
	} Function;

	typedef enum { ROTATE_0=1, ROTATE_90, ROTATE_180, ROTATE_270 } Orientation;

	typedef enum { IDLE, ALARM, ALERT, TAPE } State;

protected:  
	static bool initialised;
	static bool record_event_stats;
	static bool record_diag_images;
	static bool opt_adaptive_skip;
	static bool create_analysis_images;
	static bool blend_alarmed_images;
	static bool timestamp_on_capture;

protected:
	// These are read from the DB and thereafter remain unchanged
	int		id;
	char	*name;
	Function	function;		// What the monitor is doing
	unsigned int    width;		// Normally the same as the camera, but not if partly rotated
	unsigned int    height;		// Normally the same as the camera, but not if partly rotated
	RunMode	run_mode;			// Whether the monitor is running continuously or is triggered	
	Orientation	orientation;	// Whether the image has to be rotated at all
	char	label_format[64];	// The format of the timestamp on the images
	Coord	label_coord;		// The coordinates of the timestamp on the images
	int		image_buffer_count; // Size of circular image buffer, at least twice the size of the pre_event_count
	int		warmup_count;		// How many images to process before looking for events
	int		pre_event_count;	// How many images to hold and prepend to an alarm event
	int		post_event_count;	// How many unalarmed images must occur before the alarm state is reset
	int		section_length;		// How long events should last in continuous modes
	int		frame_skip;			// How many frames to skip in continuous modes
	int 	capture_delay;		// How long we wait between capture frames
	int		fps_report_interval;// How many images should be captured/processed between reporting the current FPS
	int		ref_blend_perc;		// Percentage of new image going into reference image.

	double	fps;
	Image	image;
	Image	ref_image;

	Purpose	purpose;			// What this monitor has been created to do
	int		event_count;
	int		image_count;
	int		first_alarm_count;
	int		last_alarm_count;
	int		buffer_count;
	State state;
	int		n_zones;
	Zone	**zones;
	Event	*event;
	time_t	start_time;
	time_t	last_fps_time;
	int		shmid;

	typedef struct Snapshot
	{
		struct timeval	*timestamp;
		Image	*image;
	};

	Snapshot *image_buffer;

	typedef enum { FORCE_NEUTRAL, FORCE_ON, FORCE_OFF } ForceState;
	typedef enum { GET_SETTINGS=0x0001, SET_SETTINGS=0x0002 } Action;

	typedef struct
	{
		bool valid;
		State state;
		ForceState force_state;
		int last_write_index;
		int last_read_index;
		time_t last_image_time;
		int last_event;
		int action;
		int brightness;
		int hue;
		int colour;
		int contrast;
	} SharedData;

	SharedData *shared_data;

	Camera *camera;

protected:
	static void Initialise()
	{
		initialised = true;

		record_event_stats = (bool)config.Item( ZM_RECORD_EVENT_STATS );
		record_diag_images = (bool)config.Item( ZM_RECORD_DIAG_IMAGES );
		opt_adaptive_skip = (bool)config.Item( ZM_OPT_ADAPTIVE_SKIP );
		create_analysis_images = (bool)config.Item( ZM_CREATE_ANALYSIS_IMAGES );
		blend_alarmed_images = (bool)config.Item( ZM_BLEND_ALARMED_IMAGES );
		timestamp_on_capture = (bool)config.Item( ZM_TIMESTAMP_ON_CAPTURE );
	}

public:
	Monitor( int p_id, char *p_name, int p_function, int p_device, int p_channel, int p_format, int p_width, int p_height, int p_palette, int p_orientation, char *p_label_format, const Coord &p_label_coord, int p_image_buffer_count, int p_warmup_count, int p_pre_event_count, int p_post_event_count, int p_section_length, int p_frame_skip, int p_capture_delay, int p_fps_report_interval, int p_ref_blend_perc, Purpose p_purpose=QUERY, int p_n_zones=0, Zone *p_zones[]=0 );
	Monitor( int p_id, char *p_name, int p_function, const char *p_host, const char *p_port, const char *p_path, int p_width, int p_height, int p_palette, int p_orientation, char *p_label_format, const Coord &p_label_coord, int p_image_buffer_count, int p_warmup_count, int p_pre_event_count, int p_post_event_count, int p_section_length, int p_frame_skip, int p_capture_delay, int p_fps_report_interval, int p_ref_blend_perc, Purpose p_purpose=QUERY, int p_n_zones=0, Zone *p_zones[]=0 );
	~Monitor();

	void Setup();

	void AddZones( int p_n_zones, Zone *p_zones[] );

	inline int ShmValid() const
	{
		return( shared_data->valid );
	}

	inline int Id() const
	{
		return( id );
	}
	inline char *Name() const
	{
		return( name );
	}
	State GetState() const;
	int GetImage( int index=-1 ) const;
	struct timeval GetTimestamp( int index=-1 ) const;
	int GetCaptureDelay() const { return( capture_delay ); }
	unsigned int GetLastReadIndex() const;
	unsigned int GetLastWriteIndex() const;
	unsigned int GetLastEvent() const;
	double GetFPS() const;
	void ForceAlarmOn();
	void ForceAlarmOff();
	void CancelForced();

	inline void TimestampImage( Image *ts_image, time_t ts_time ) const
	{
		if ( label_format[0] )
		{
			static char label_time_text[256];
			static char label_text[256];

			strftime( label_time_text, sizeof(label_time_text), label_format, localtime( &ts_time ) );
			sprintf( label_text, label_time_text, name );

			ts_image->Annotate( label_text, label_coord );
		}
	}
	int Brightness( int p_brightness=-1 );
	int Hue( int p_hue=-1 );
	int Colour( int p_colour=-1 );
	int Contrast( int p_contrast=-1 );

	bool DumpSettings( char *output, bool verbose );
	void DumpZoneImage();

	unsigned int Width() const { return( width ); }
	unsigned int Height() const { return( height ); }
	inline int PreCapture()
	{
		return( camera->PreCapture() );
	}
	inline int PostCapture()
	{
		if ( camera->PostCapture( image ) == 0 )
		{
			if ( orientation != ROTATE_0 )
			{
				image.Rotate( (orientation-1)*90 );
			}

			int index = image_count%image_buffer_count;

			if ( index == shared_data->last_read_index && function > MONITOR )
			{
				Warning(( "Buffer overrun at index %d\n", index ));
			}

			gettimeofday( image_buffer[index].timestamp, &dummy_tz );
			if ( timestamp_on_capture )
			{
				TimestampImage( &image, image_buffer[index].timestamp->tv_sec );
			}
			image_buffer[index].image->CopyBuffer( image );

			shared_data->last_write_index = index;
			shared_data->last_image_time = image_buffer[index].timestamp->tv_sec;

			image_count++;

			if ( image_count && !(image_count%fps_report_interval) )
			{
				time_t now = image_buffer[index].timestamp->tv_sec;
				fps = double(fps_report_interval)/(now-last_fps_time);
				Info(( "%s: %d - Capturing at %.2f fps", name, image_count, fps ));
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
		return( -1 );
	}

	inline bool Ready()
	{
		return( function > MONITOR && image_count > warmup_count );
	}
 
	void DumpImage( Image *dump_image ) const;
	bool Analyse();

	unsigned int Compare( const Image &comp_image );
	void ReloadZones();
	static int Load( int device, Monitor **&monitors, Purpose purpose=QUERY );
	static int Load( const char *host, const char*port, const char*path, Monitor **&monitors, Purpose purpose=QUERY );
	static Monitor *Load( int id, bool load_zones=false, Purpose purpose=QUERY );
	void StreamImages( unsigned long idle=5000, unsigned long refresh=50, time_t ttl=0, int scale=1, FILE *fd=stdout );
};

#endif // ZM_MONITOR_H
