//
// ZoneMinder Monitor Class Interfaces, $Date$, $Revision$
// Copyright (C) 2003, 2004, 2005  Philip Coombes
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
		MOCORD,
		NODECT
	} Function;

	typedef enum { ROTATE_0=1, ROTATE_90, ROTATE_180, ROTATE_270, FLIP_HORI, FLIP_VERT } Orientation;

	typedef enum { IDLE, PREALARM, ALARM, ALERT, TAPE } State;

	typedef enum { ACTIVE, SUSPENDED, RESUMING } ActivityState;

protected:
	// These are read from the DB and thereafter remain unchanged
	int				id;
	char			*name;
	Function		function;			// What the monitor is doing
	unsigned int    width;				// Normally the same as the camera, but not if partly rotated
	unsigned int    height;				// Normally the same as the camera, but not if partly rotated
	RunMode			run_mode;			// Whether the monitor is running continuously or is triggered	
	Orientation		orientation;		// Whether the image has to be rotated at all
	int				brightness;			// The statically saved brightness of the camera
	int				contrast;			// The statically saved contrast of the camera
	int				hue;				// The statically saved hue of the camera
	int				colour;				// The statically saved colour of the camera
	char			event_prefix[64];	// The prefix applied to event names as they are created
	char			label_format[64];	// The format of the timestamp on the images
	Coord			label_coord;		// The coordinates of the timestamp on the images
	int				image_buffer_count; // Size of circular image buffer, at least twice the size of the pre_event_count
	int				warmup_count;		// How many images to process before looking for events
	int				pre_event_count;	// How many images to hold and prepend to an alarm event
	int				post_event_count;	// How many unalarmed images must occur before the alarm state is reset
	int				section_length;		// How long events should last in continuous modes
	int				frame_skip;			// How many frames to skip in continuous modes
	int				capture_delay;		// How long we wait between capture frames
	int				alarm_frame_count;	// How many alarm frames are required before an event is triggered
	int				fps_report_interval;// How many images should be captured/processed between reporting the current FPS
	int				ref_blend_perc;		// Percentage of new image going into reference image.
	bool			track_motion;		// Whether this monitor tries to track detected motion 

	double			fps;
	Image			image;
	Image			ref_image;

	Purpose			purpose;			// What this monitor has been created to do
	ActivityState	activity_state;
	int				event_count;
	int				image_count;
	int				resume_image_count;
	int				first_alarm_count;
	int				last_alarm_count;
	int				buffer_count;
	int				prealarm_count;
	State			state;
	int				n_zones;
	Zone			**zones;
	Event			*event;
	time_t			start_time;
	time_t			last_fps_time;
	int				shmid;

	typedef struct Snapshot
	{
		struct timeval	*timestamp;
		Image	*image;
	};

	Snapshot *image_buffer;

	typedef enum { GET_SETTINGS=0x1, SET_SETTINGS=0x2, SUSPEND=0x4, RESUME=0x8 } Action;
	typedef struct
	{
		int size;
		bool valid;
		State state;
		int last_write_index;
		int last_read_index;
		time_t last_image_time;
		int last_event;
		int action;
		int brightness;
		int hue;
		int colour;
		int contrast;
		int alarm_x;
		int alarm_y;
	} SharedData;

	typedef enum { TRIGGER_CANCEL, TRIGGER_ON, TRIGGER_OFF } TriggerState;
	typedef struct
	{
		int size;
		TriggerState trigger_state;
		int trigger_score;
		char trigger_cause[32];
		char trigger_text[256];
		char trigger_showtext[32];
	} TriggerData;

	SharedData *shared_data;
	TriggerData *trigger_data;

	Camera *camera;

public:
	Monitor( int p_id, char *p_name, int p_function, int p_device, int p_channel, int p_format, int p_width, int p_height, int p_palette, int p_orientation, int p_brightness, int p_contrast, int p_hue, int p_colour, char *p_event_prefix, char *p_label_format, const Coord &p_label_coord, int p_image_buffer_count, int p_warmup_count, int p_pre_event_count, int p_post_event_count, int p_alarm_frame_count, int p_section_length, int p_frame_skip, int p_capture_delay, int p_fps_report_interval, int p_ref_blend_perc, bool p_track_motion, Purpose p_purpose=QUERY, int p_n_zones=0, Zone *p_zones[]=0 );
	Monitor( int p_id, char *p_name, int p_function, const char *p_host, const char *p_port, const char *p_path, int p_width, int p_height, int p_palette, int p_orientation, int p_brightness, int p_contrast, int p_hue, int p_colour, char *p_event_prefix, char *p_label_format, const Coord &p_label_coord, int p_image_buffer_count, int p_warmup_count, int p_pre_event_count, int p_post_event_count, int p_alarm_frame_count, int p_section_length, int p_frame_skip, int p_capture_delay, int p_fps_report_interval, int p_ref_blend_perc, bool p_track_motion, Purpose p_purpose=QUERY, int p_n_zones=0, Zone *p_zones[]=0 );
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
	inline const char *EventPrefix() const
	{
		return( event_prefix );
	}
	State GetState() const;
	int GetImage( int index=-1, int scale=100 ) const;
	struct timeval GetTimestamp( int index=-1 ) const;
	int GetCaptureDelay() const { return( capture_delay ); }
	unsigned int GetLastReadIndex() const;
	unsigned int GetLastWriteIndex() const;
	unsigned int GetLastEvent() const;
	double GetFPS() const;
	void ForceAlarmOn( int force_score, const char *force_case, const char *force_text="" );
	void ForceAlarmOff();
	void CancelForced();
	void Suspend();
	void Resume();

	inline void TimestampImage( Image *ts_image, time_t ts_time ) const
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
				Info(( "Found %d tokens, in %s", token_count, label_format ));
			}
			strftime( label_time_text, sizeof(label_time_text), label_format, localtime( &ts_time ) );
			switch ( token_count )
			{
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

			int index = image_count%image_buffer_count;

			if ( index == shared_data->last_read_index && function > MONITOR )
			{
				Warning(( "Buffer overrun at index %d\n", index ));
			}

			gettimeofday( image_buffer[index].timestamp, &dummy_tz );
			if ( config.timestamp_on_capture )
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
		if ( function <= MONITOR )
			return( false );
		if ( image_count <= warmup_count )
			return( false );
		return( true );
	}
 
	void DumpImage( Image *dump_image ) const;
	bool Analyse();

	unsigned int Compare( const Image &comp_image );
	void ReloadZones();
	static int Load( int device, Monitor **&monitors, Purpose purpose=QUERY );
	static int Load( const char *host, const char*port, const char*path, Monitor **&monitors, Purpose purpose=QUERY );
	static Monitor *Load( int id, bool load_zones=false, Purpose purpose=QUERY );
	void StreamImages( int scale=100, int maxfps=10, time_t ttl=0 );
	void SingleImage( int scale=100 );
#if HAVE_LIBAVCODEC
	void StreamMpeg( const char *format, int scale=100, int maxfps=10, int bitrate=100000 );
#endif // HAVE_LIBAVCODEC
};

#endif // ZM_MONITOR_H
