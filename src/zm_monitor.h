//
// ZoneMinder Monitor Class Interfaces, $Date$, $Revision$
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

#ifndef ZM_MONITOR_H
#define ZM_MONITOR_H

#include "zm.h"
#include "zm_coord.h"
#include "zm_image.h"
#include "zm_zone.h"
#include "zm_event.h"
#include "zm_camera.h"

#include <sys/time.h>

#define SIGNAL_CAUSE "Signal"
#define MOTION_CAUSE "Motion"
#define LINKED_CAUSE "Linked"

//
// This is the main class for monitors. Each monitor is associated
// with a camera and is effectively a collector for events.
//
class Monitor
{
friend class MonitorStream;

public:
	typedef enum
	{
		QUERY=0,
		CAPTURE,
		ANALYSIS
	} Purpose;

	typedef enum
	{
		NONE=1,
		MONITOR,
		MODECT,
		RECORD,
		MOCORD,
		NODECT
	} Function;

	typedef enum
	{ 
		ROTATE_0=1,
		ROTATE_90,
		ROTATE_180,
		ROTATE_270,
		FLIP_HORI,
		FLIP_VERT
	} Orientation;

	typedef enum
	{
		IDLE,
		PREALARM,
		ALARM,
		ALERT,
		TAPE
	} State;

protected:
    typedef std::set<Zone *> ZoneSet;

	typedef enum { GET_SETTINGS=0x1, SET_SETTINGS=0x2, RELOAD=0x4, SUSPEND=0x10, RESUME=0x20 } Action;

	typedef enum { CLOSE_TIME, CLOSE_IDLE, CLOSE_ALARM } EventCloseMode;

	typedef struct
	{
		int size;
		bool valid;
		bool active;
		bool signal;
		State state;
		int last_write_index;
		int last_read_index;
		time_t last_write_time;
		time_t last_read_time;
		int last_event;
		int action;
		int brightness;
		int hue;
		int colour;
		int contrast;
		int alarm_x;
		int alarm_y;
		char control_state[256];
	} SharedData;

	typedef enum { TRIGGER_CANCEL, TRIGGER_ON, TRIGGER_OFF } TriggerState;
	typedef struct
	{
		int size;
		TriggerState trigger_state;
		int trigger_score;
		char trigger_cause[32];
		char trigger_text[256];
		char trigger_showtext[256];
	} TriggerData;

	struct Snapshot
	{
		struct timeval	*timestamp;
		Image	*image;
	};

	class MonitorLink
	{
	protected:
		unsigned int	id;
		char			name[64];

		bool			connected;
		time_t			last_connect_time;

#if ZM_MEM_MAPPED
		int				map_fd;
        char            mem_file[PATH_MAX];
#else // ZM_MEM_MAPPED
        int             shm_id;
#endif // ZM_MEM_MAPPED
		int				mem_size;
		unsigned char	*mem_ptr;

		SharedData		*shared_data;
		TriggerData		*trigger_data;

		int				last_state;
		int				last_event;

	public:
		MonitorLink( int p_id, const char *p_name );
		~MonitorLink();

		inline int Id() const
		{
			return( id );
		}
		inline const char *Name() const
		{
			return( name );
		}

		inline bool isConnected() const
		{   
			return( connected );
		}
		inline time_t getLastConnectTime() const
		{
			return( last_connect_time );
		}

		bool connect();
		bool disconnect();

		bool isAlarmed();
		bool inAlarm();
		bool hasAlarmed();
	};

protected:
	// These are read from the DB and thereafter remain unchanged
	unsigned int	id;
	char			name[64];
	Function		function;			    // What the monitor is doing
	bool			enabled;			    // Whether the monitor is enabled or asleep
	unsigned int    width;				    // Normally the same as the camera, but not if partly rotated
	unsigned int    height;				    // Normally the same as the camera, but not if partly rotated
	Orientation		orientation;		    // Whether the image has to be rotated at all
	int				brightness;			    // The statically saved brightness of the camera
	int				contrast;			    // The statically saved contrast of the camera
	int				hue;				    // The statically saved hue of the camera
	int				colour;				    // The statically saved colour of the camera
	char			event_prefix[64];	    // The prefix applied to event names as they are created
	char			label_format[64];	    // The format of the timestamp on the images
	Coord			label_coord;		    // The coordinates of the timestamp on the images
	int				image_buffer_count;     // Size of circular image buffer, at least twice the size of the pre_event_count
	int				warmup_count;		    // How many images to process before looking for events
	int				pre_event_count;	    // How many images to hold and prepend to an alarm event
	int				post_event_count;	    // How many unalarmed images must occur before the alarm state is reset
	int				stream_replay_buffer;   // How many frames to store to support DVR functions, IGNORED from this object, passed directly into zms now
	int				section_length;		    // How long events should last in continuous modes
	int				frame_skip;			    // How many frames to skip in continuous modes
	int				capture_delay;		    // How long we wait between capture frames
	int				alarm_capture_delay;    // How long we wait between capture frames when in alarm state
	int				alarm_frame_count;	    // How many alarm frames are required before an event is triggered
	int				fps_report_interval;    // How many images should be captured/processed between reporting the current FPS
	int				ref_blend_perc;		    // Percentage of new image going into reference image.
	bool			track_motion;		    // Whether this monitor tries to track detected motion 
    Rgb             signal_check_colour;    // The colour that the camera will emit when no video signal detected

	double			fps;
	Image			image;
	Image			ref_image;

	Purpose			purpose;			    // What this monitor has been created to do
	int				event_count;
	int				image_count;
	int				ready_count;
	int				first_alarm_count;
	int				last_alarm_count;
	int				buffer_count;
	int				prealarm_count;
	State			state;
	time_t			start_time;
	time_t			last_fps_time;
	time_t			auto_resume_time;

    EventCloseMode  event_close_mode;

#if ZM_MEM_MAPPED
	int				map_fd;
    char            mem_file[PATH_MAX];
#else // ZM_MEM_MAPPED
    int             shm_id;
#endif // ZM_MEM_MAPPED
	int				mem_size;
	unsigned char	*mem_ptr;

	SharedData		*shared_data;
	TriggerData		*trigger_data;

	Snapshot		*image_buffer;

	Camera			*camera;

	Event			*event;

	int				n_zones;
	Zone			**zones;

	int				n_linked_monitors;
	MonitorLink		**linked_monitors;

public:
	Monitor( int p_id, const char *p_name, int p_function, bool p_enabled, const char *p_linked_monitors, Camera *p_camera, int p_orientation, const char *p_event_prefix, const char *p_label_format, const Coord &p_label_coord, int p_image_buffer_count, int p_warmup_count, int p_pre_event_count, int p_post_event_count, int p_stream_replay_buffer, int p_alarm_frame_count, int p_section_length, int p_frame_skip, int p_capture_delay, int p_alarm_capture_delay, int p_fps_report_interval, int p_ref_blend_perc, bool p_track_motion, Rgb p_signal_check_colour, Purpose p_purpose, int p_n_zones=0, Zone *p_zones[]=0 );
	~Monitor();

	void AddZones( int p_n_zones, Zone *p_zones[] );

	inline int ShmValid() const
	{
		return( shared_data->valid );
	}

	inline int Id() const
	{
		return( id );
	}
	inline const char *Name() const
	{
		return( name );
	}
	inline Function GetFunction() const
	{
		return( function );
	}
	inline bool Enabled()
	{
		if ( function <= MONITOR )
			return( false );
		return( enabled );
	}
	inline const char *EventPrefix() const
	{
		return( event_prefix );
	}
	inline bool Ready()
	{
		if ( function <= MONITOR )
			return( false );
		return( image_count > ready_count );
	}
	inline bool Active()
	{
		if ( function <= MONITOR )
			return( false );
		return( enabled && shared_data->active );
	}

	unsigned int Width() const { return( width ); }
	unsigned int Height() const { return( height ); }
    unsigned int Colours() const { return( camera->Colours() ); }
 
	State GetState() const;
	int GetImage( int index=-1, int scale=100 ) const;
	struct timeval GetTimestamp( int index=-1 ) const;
	int GetCaptureDelay() const { return( capture_delay ); }
	int GetAlarmCaptureDelay() const { return( alarm_capture_delay ); }
	unsigned int GetLastReadIndex() const;
	unsigned int GetLastWriteIndex() const;
	unsigned int GetLastEvent() const;
	double GetFPS() const;
	void ForceAlarmOn( int force_score, const char *force_case, const char *force_text="" );
	void ForceAlarmOff();
	void CancelForced();
	TriggerState GetTriggerState() const { return( trigger_data?trigger_data->trigger_state:TRIGGER_CANCEL ); }

	void actionReload();
	void actionEnable();
	void actionDisable();
	void actionSuspend();
	void actionResume();

	int actionBrightness( int p_brightness=-1 );
	int actionHue( int p_hue=-1 );
	int actionColour( int p_colour=-1 );
	int actionContrast( int p_contrast=-1 );

	inline int PrimeCapture()
	{
		return( camera->PrimeCapture() );
	}
	inline int PreCapture()
	{
		return( camera->PreCapture() );
	}
	int Capture();
	int PostCapture()
	{
		return( camera->PostCapture() );
	}

	unsigned int DetectMotion( const Image &comp_image, Event::StringSet &zoneSet );
	bool CheckSignal( const Image *image );
	bool Analyse();
	void DumpImage( Image *dump_image ) const;
	void TimestampImage( Image *ts_image, const struct timeval *ts_time ) const;
	bool closeEvent();

	void Reload();
	void ReloadZones();
	void ReloadLinkedMonitors( const char * );

	bool DumpSettings( char *output, bool verbose );
	void DumpZoneImage( const char *zone_string=0 );

#if ZM_HAS_V4L
	static int LoadLocalMonitors( const char *device, Monitor **&monitors, Purpose purpose );
#endif // ZM_HAS_V4L
	static int LoadRemoteMonitors( const char *protocol, const char *host, const char*port, const char*path, Monitor **&monitors, Purpose purpose );
	static int LoadFileMonitors( const char *file, Monitor **&monitors, Purpose purpose );
#if HAVE_LIBAVFORMAT
	static int LoadFfmpegMonitors( const char *file, Monitor **&monitors, Purpose purpose );
#endif // HAVE_LIBAVFORMAT
	static Monitor *Load( int id, bool load_zones, Purpose purpose );
    //void writeStreamImage( Image *image, struct timeval *timestamp, int scale, int mag, int x, int y );
	//void StreamImages( int scale=100, int maxfps=10, time_t ttl=0, int msq_id=0 );
	//void StreamImagesRaw( int scale=100, int maxfps=10, time_t ttl=0 );
	//void StreamImagesZip( int scale=100, int maxfps=10, time_t ttl=0 );
	void SingleImage( int scale=100 );
	void SingleImageRaw( int scale=100 );
	void SingleImageZip( int scale=100 );
#if HAVE_LIBAVCODEC
	//void StreamMpeg( const char *format, int scale=100, int maxfps=10, int bitrate=100000 );
#endif // HAVE_LIBAVCODEC
};

#define MOD_ADD( var, delta, limit ) (((var)+(limit)+(delta))%(limit))

class MonitorStream : public StreamBase
{
protected:
    typedef struct SwapImage {
        bool            valid;
        struct timeval  timestamp;
        char            file_name[PATH_MAX];
    } SwapImage;

private:
    SwapImage *temp_image_buffer;
    int temp_image_buffer_count;
    int temp_read_index;
    int temp_write_index;

protected:
    time_t ttl;

protected:
    int playback_buffer;
    bool delayed;

    int frame_count;

protected:
    bool checkSwapPath( const char *path, bool create_path );

    bool sendFrame( const char *filepath, struct timeval *timestamp );
    bool sendFrame( Image *image, struct timeval *timestamp );
    void processCommand( const CmdMsg *msg );

public:
    MonitorStream() : playback_buffer( 0 ), delayed( false ), frame_count( 0 )
    {
    }
    void setStreamBuffer( int p_playback_buffer )
    {
        playback_buffer = p_playback_buffer;
    }
    void setStreamTTL( time_t p_ttl )
    {
        ttl = p_ttl;
    }
    void setStreamStart( int monitor_id )
    {
        loadMonitor( monitor_id );
    }
	void runStream();
};

#endif // ZM_MONITOR_H
