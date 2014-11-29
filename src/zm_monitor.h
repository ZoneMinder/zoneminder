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

#include <vector>
#include <sstream>

#include "zm.h"
#include "zm_coord.h"
#include "zm_image.h"
#include "zm_rgb.h"
#include "zm_zone.h"
#include "zm_event.h"
#include "zm_camera.h"
#include "zm_utils.h"

#include "zm_image_analyser.h"

#include <sys/time.h>
#include <stdint.h>

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

	/* sizeof(SharedData) expected to be 336 bytes on 32bit and 64bit */
	typedef struct
	{
		uint32_t size;             	/* +0    */
		uint32_t last_write_index; 	/* +4    */ 
		uint32_t last_read_index;  	/* +8    */
		uint32_t state;            	/* +12   */
		uint32_t last_event;       	/* +16   */
		uint32_t action;           	/* +20   */
		int32_t brightness;        	/* +24   */
		int32_t hue;               	/* +28   */
		int32_t colour;            	/* +32   */
		int32_t contrast;          	/* +36   */
		int32_t alarm_x;           	/* +40   */
		int32_t alarm_y;           	/* +44   */
		uint8_t valid;             	/* +48   */
		uint8_t active;            	/* +49   */
		uint8_t signal;            	/* +50   */
		uint8_t format;            	/* +51   */
		uint32_t imagesize;        	/* +52   */
		uint32_t epadding1;        	/* +56   */
		uint32_t epadding2;        	/* +60   */
		/* 
		** This keeps 32bit time_t and 64bit time_t identical and compatible as long as time is before 2038.
		** Shared memory layout should be identical for both 32bit and 64bit and is multiples of 16.
		*/	
		union {                    	/* +64    */
		      time_t last_write_time;
		      uint64_t extrapad1;
		};
		union {                    	/* +72   */
		      time_t last_read_time;
		      uint64_t extrapad2;
		};
		uint8_t control_state[256];	/* +80   */
		
	} SharedData;

	typedef enum { TRIGGER_CANCEL, TRIGGER_ON, TRIGGER_OFF } TriggerState;
	
	/* sizeof(TriggerData) expected to be 560 on 32bit & and 64bit */
	typedef struct
	{
		uint32_t size;
		uint32_t trigger_state;
		uint32_t trigger_score;
		uint32_t padding;
		char trigger_cause[32];
		char trigger_text[256];
		char trigger_showtext[256];
	} TriggerData;

	/* sizeof(Snapshot) expected to be 16 bytes on 32bit and 32 bytes on 64bit */
	struct Snapshot
	{
		struct timeval	*timestamp;
		Image	*image;
		void* padding;
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
		unsigned long	mem_size;
		unsigned char	*mem_ptr;

		volatile SharedData	*shared_data;
		volatile TriggerData	*trigger_data;

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
	bool			v4l_multi_buffer;
	unsigned int	v4l_captures_per_frame;
	Orientation		orientation;		    // Whether the image has to be rotated at all
	unsigned int	deinterlacing;
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
	int				motion_frame_skip;		    // How many frames to skip in motion detection
	int				capture_delay;		    // How long we wait between capture frames
	int				alarm_capture_delay;    // How long we wait between capture frames when in alarm state
	int				alarm_frame_count;	    // How many alarm frames are required before an event is triggered
	int				fps_report_interval;    // How many images should be captured/processed between reporting the current FPS
	int				ref_blend_perc;		    // Percentage of new image going into reference image.
	int				alarm_ref_blend_perc;		    // Percentage of new image going into reference image during alarm.
	bool			track_motion;		    // Whether this monitor tries to track detected motion 
    Rgb             signal_check_colour;    // The colour that the camera will emit when no video signal detected

	double			fps;
	Image			delta_image;
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
        unsigned int            last_motion_score;

    EventCloseMode  event_close_mode;

#if ZM_MEM_MAPPED
	int				map_fd;
    char            mem_file[PATH_MAX];
#else // ZM_MEM_MAPPED
    int             shm_id;
#endif // ZM_MEM_MAPPED
	unsigned long				mem_size;
	unsigned char	*mem_ptr;

	SharedData		*shared_data;
	TriggerData		*trigger_data;

	Snapshot		*image_buffer;
	Snapshot		next_buffer; /* Used by four field deinterlacing */

	Camera			*camera;

	Event			*event;

	int			n_zones;
	Zone			**zones;

	struct timeval		**timestamps;
	Image			**images;

	int			iDoNativeMotDet;

	int			n_linked_monitors;
	MonitorLink		**linked_monitors;

public:
// OurCheckAlarms seems to be unused. Check it on zm_monitor.cpp for more info.
//bool OurCheckAlarms( Zone *zone, const Image *pImage );
	Monitor( int p_id, const char *p_name, int p_function, bool p_enabled, const char *p_linked_monitors, Camera *p_camera, int p_orientation, unsigned int p_deinterlacing, const char *p_event_prefix, const char *p_label_format, const Coord &p_label_coord, int p_image_buffer_count, int p_warmup_count, int p_pre_event_count, int p_post_event_count, int p_stream_replay_buffer, int p_alarm_frame_count, int p_section_length, int p_frame_skip, int p_motion_frame_skip, int p_capture_delay, int p_alarm_capture_delay, int p_fps_report_interval, int p_ref_blend_perc, int p_alarm_ref_blend_perc, bool p_track_motion, Rgb p_signal_check_colour, Purpose p_purpose, int p_n_zones=0, Zone *p_zones[]=0 );
	~Monitor();

	void AddZones( int p_n_zones, Zone *p_zones[] );

	bool connect();
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
	unsigned int SubpixelOrder() const { return( camera->SubpixelOrder() ); }
      
 
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
	TriggerState GetTriggerState() const { return( (TriggerState)(trigger_data?trigger_data->trigger_state:TRIGGER_CANCEL )); }

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
   // DetectBlack seems to be unused. Check it on zm_monitor.cpp for more info.
   //unsigned int DetectBlack( const Image &comp_image, Event::StringSet &zoneSet );
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
