//
// ZoneMinder Core Interfaces, $Date$, $Revision$
// Copyright (C) 2002  Philip Coombes
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

#include <stdio.h>
#include <stdlib.h>
#include <string.h>
#include <unistd.h>
#include <errno.h>
#include <fcntl.h>
#include <limits.h>
#include <math.h>
#include <time.h>
#include <signal.h>
#include <assert.h>
#include <sys/stat.h>
#include <sys/types.h>
#include <sys/time.h>
#include <sys/mman.h>
#include <sys/ioctl.h>
#include <sys/shm.h>
#include <linux/videodev.h>
#include <mysql/mysql.h>

#include "font_6x11.h"

extern "C"
{
#include <jpeglib.h>

double round(double);

void jpeg_mem_dest(j_compress_ptr cinfo, JOCTET *outbuffer, int *outbuffer_size );
}

extern "C"
{
#include "zmcfg.h"
#include "zmdbg.h"
}

typedef unsigned int Rgb;

#define RED(byte)	(*(byte))
#define GREEN(byte)	(*(byte+1))
#define BLUE(byte)	(*(byte+2))

#define WHITE	0xff
#define WHITE_R	0xff
#define WHITE_G	0xff
#define WHITE_B	0xff

#define BLACK	0x00
#define BLACK_R	0x00
#define BLACK_G	0x00
#define BLACK_B	0x00

#define RGB_WHITE	(0x00ffffff)
#define RGB_BLACK	(0x00000000)
#define RGB_RED		(0x00ff0000)
#define RGB_GREEN	(0x0000ff00)
#define RGB_BLUE	(0x000000ff)

#define RGB_VAL(v,c)	(((v)>>(16-((c)*8)))&0xff)
#define RGB_RED_VAL(v)		(((v)>>16)&0xff)
#define RGB_GREEN_VAL(v)	(((v)>>8)&0xff)
#define RGB_BLUE_VAL(v)		((v)&0xff)

extern MYSQL dbconn;

class Coord
{
private:
	int x, y;

public:
	inline Coord() : x(0), y(0)
	{
	}
	inline Coord( int p_x, int p_y ) : x(p_x), y(p_y)
	{
	}
	inline Coord( const Coord &p_coord ) : x(p_coord.x), y(p_coord.y)
	{
	}
	inline int &X() { return( x ); }
	inline const int &X() const { return( x ); }
	inline int &Y() { return( y ); }
	inline const int &Y() const { return( y ); }

	inline static Coord Range( const Coord &coord1, const Coord &coord2 )
	{
		Coord result( (coord1.x-coord2.x)+1, (coord1.y-coord2.y)+1 );
		return( result );
	}

	inline bool operator==( const Coord &coord )
	{
		return( x == coord.x && y == coord.y );
	}
	inline bool operator!=( const Coord &coord )
	{
		return( x != coord.x || y != coord.y );
	}
	inline bool operator>( const Coord &coord )
	{
		return( x > coord.x && y > coord.y );
	}
	inline bool operator>=( const Coord &coord )
	{
		return( !(operator<(coord)) );
	}
	inline bool operator<( const Coord &coord )
	{
		return( x < coord.x && y < coord.y );
	}
	inline bool operator<=( const Coord &coord )
	{
		return( !(operator>(coord)) );
	}
	inline Coord &operator+=( const Coord &coord )
	{
		x += coord.x;
		y += coord.y;
		return( *this );
	}
	inline Coord &operator-=( const Coord &coord )
	{
		x -= coord.x;
		y -= coord.y;
		return( *this );
	}

	inline friend Coord operator+( const Coord &coord1, const Coord &coord2 )
	{
		Coord result( coord1 );
		result += coord2;
		return( result );
	}
	inline friend Coord operator-( const Coord &coord1, const Coord &coord2 )
	{
		Coord result( coord1 );
		result -= coord2;
		return( result );
	}
};

class Box
{
private:
	Coord lo, hi;
	Coord size;

public:
	inline Box()
	{
	}
	inline Box( int p_size ) : lo( 0, 0 ), hi ( p_size-1, p_size-1 ), size( Coord::Range( hi, lo ) )
	{
	}
	inline Box( int p_x_size, int p_y_size ) : lo( 0, 0 ), hi ( p_x_size-1, p_y_size-1 ), size( Coord::Range( hi, lo ) )
	{
	}
	inline Box( int lo_x, int lo_y, int hi_x, int hi_y ) : lo( lo_x, lo_y ), hi( hi_x, hi_y ), size( Coord::Range( hi, lo ) )
	{
	}
	inline Box( const Coord &p_lo, const Coord &p_hi ) : lo( p_lo ), hi( p_hi ), size( Coord::Range( hi, lo ) )
	{
	}
	inline const Coord &Lo() const { return( lo ); }
	inline int LoX() const { return( lo.X() ); }
	inline int LoY() const { return( lo.Y() ); }
	inline const Coord &Hi() const { return( hi ); }
	inline int HiX() const { return( hi.X() ); }
	inline int HiY() const { return( hi.Y() ); }
	inline const Coord &Size() const { return( size ); }
	inline int Width() const
	{
		return( size.X() );
	}
	inline int Height() const
	{
		return( size.Y() );
	}

	inline bool Inside( const Coord &coord ) const
	{
		return( coord.X() >= lo.X() && coord.X() <= hi.X() && coord.Y() >= lo.Y() && coord.Y() <= hi.Y() );
	}
};

class Monitor;
class Event;

class Zone
{
friend class Image;

public:
	typedef enum { ACTIVE=1, INCLUSIVE, EXCLUSIVE, INACTIVE } ZoneType;

protected:
	// Inputs
	Monitor *monitor;

	int id;
	char *label;
	ZoneType type;
	Box limits;
	Rgb alarm_rgb;

	int alarm_threshold;
	int min_alarm_pixels;
	int max_alarm_pixels;

	Coord filter_box;
	int min_filter_pixels;
	int max_filter_pixels;

	int min_blob_pixels;
	int max_blob_pixels;
	int min_blobs;
	int max_blobs;

	// Outputs/Statistics
	bool alarmed;
	int alarm_pixels;
	int alarm_filter_pixels;
	int alarm_blob_pixels;
	int alarm_blobs;
	int min_blob_size;
	int max_blob_size;
	Box alarm_box;
	unsigned int score;
	Image *image;

protected:
	void Setup( Monitor *p_monitor, int p_id, const char *p_label, ZoneType p_type, const Box &p_limits, const Rgb p_alarm_rgb, int p_alarm_threshold, int p_min_alarm_pixels, int p_max_alarm_pixels, const Coord &p_filter_box, int p_min_filter_pixels, int p_max_filter_pixels, int p_min_blob_pixels, int p_max_blob_pixels, int p_min_blobs, int p_max_blobs );

public:
	Zone( Monitor *p_monitor, int p_id, const char *p_label, ZoneType p_type, const Box &p_limits, const Rgb p_alarm_rgb, int p_alarm_threshold=15, int p_min_alarm_pixels=50, int p_max_alarm_pixels=75000, const Coord &p_filter_box=Coord( 3, 3 ), int p_min_filter_pixels=50, int p_max_filter_pixels=50000, int p_min_blob_pixels=10, int p_max_blob_pixels=0, int p_min_blobs=0, int p_max_blobs=0 )
	{
		Setup( p_monitor, p_id, p_label, p_type, p_limits, p_alarm_rgb, p_alarm_threshold, p_min_alarm_pixels, p_max_alarm_pixels, p_filter_box, p_min_filter_pixels, p_max_filter_pixels, p_min_blob_pixels, p_max_blob_pixels, p_min_blobs, p_max_blobs );
	}
	Zone( Monitor *p_monitor, int p_id, const char *p_label, const Box &p_limits, const Rgb p_alarm_rgb, int p_alarm_threshold=15, int p_min_alarm_pixels=50, int p_max_alarm_pixels=75000, const Coord &p_filter_box=Coord( 3, 3 ), int p_min_filter_pixels=50, int p_max_filter_pixels=50000, int p_min_blob_pixels=10, int p_max_blob_pixels=0, int p_min_blobs=0, int p_max_blobs=0 )
	{
		Setup( p_monitor, p_id, p_label, Zone::ACTIVE, p_limits, p_alarm_rgb, p_alarm_threshold, p_min_alarm_pixels, p_max_alarm_pixels, p_filter_box, p_min_filter_pixels, p_max_filter_pixels, p_min_blob_pixels, p_max_blob_pixels, p_min_blobs, p_max_blobs );
	}
	Zone( Monitor *p_monitor, int p_id, const char *p_label, const Box &p_limits )
	{
		Setup( p_monitor, p_id, p_label, Zone::INACTIVE, p_limits, RGB_BLACK, 0, 0, 0, Coord( 0, 0 ), 0, 0, 0, 0, 0, 0 );
	}

public:
	~Zone();
	inline const char *Label() const
	{
		return( label );
	}
	inline ZoneType Type() const
	{
		return( type );
	}
	inline Image &AlarmImage() const
	{
		return( *image );
	}
	inline const Box &Limits() const { return( limits ); }
	inline bool Alarmed() const
	{
		return( alarmed );
	}
	inline void ResetStats()
	{
		alarmed = false;
		alarm_pixels = 0;
		alarm_filter_pixels = 0;
		alarm_blob_pixels = 0;
		alarm_blobs = 0;
		min_blob_size = 0;
		max_blob_size = 0;
		score = 0;
	}
	void RecordStats( const Event *event );
	static int Load( Monitor *monitor, Zone **&zones );
};

class Camera;
class Monitor;

class Image
{
friend class Camera;
friend class Monitor;

protected:
	enum { CHAR_HEIGHT=11, CHAR_WIDTH=6, CHAR_START=4 };

protected:
	int	width;
	int height;
	int colours;
	int size;
	JSAMPLE *buffer;
	bool our_buffer;

public:
	Image( const char *filename )
	{
		ReadJpeg( filename );
		our_buffer = true;
	}
	Image( int p_width, int p_height, int p_colours, JSAMPLE *p_buffer=0 )
	{
		width = p_width;
		height = p_height;
		colours = p_colours;
		size = width*height*colours;
		if ( !p_buffer )
		{
			our_buffer = true;
			buffer = new JSAMPLE[size];
			memset( buffer, 0, size );
		}
		else
		{
			our_buffer = false;
			buffer = p_buffer;
		}
	}
	Image( const Image &p_image )
	{
		width = p_image.width;
		height = p_image.height;
		colours = p_image.colours;
		size = p_image.size;
		buffer = new JSAMPLE[size];
		memcpy( buffer, p_image.buffer, size );
		our_buffer = true;
	}
	~Image()
	{
		if ( our_buffer )
		{
			delete[] buffer;
		}
	}

	inline void Assign( int p_width, int p_height, int p_colours, unsigned char *new_buffer )
	{
		if ( p_width != width || p_height != height || p_colours != colours )
		{
			width = p_width;
			height = p_height;
			colours = p_colours;
			size = width*height*colours;
			delete[] buffer;
			buffer = new JSAMPLE[size];
			memset( buffer, 0, size );
		}
		if ( colours == 1 )
		{
			memcpy( buffer, new_buffer, size );
		}
		else
		{
                	for ( int i = 0; i < size; i += 3 )
                	{
				buffer[i] = new_buffer[i+2];
				buffer[i+1] = new_buffer[i+1];
				buffer[i+2] = new_buffer[i];
			}
                }

	}
	inline Image &operator=( const unsigned char *new_buffer )
	{
		memcpy( buffer, new_buffer, size );
		return( *this );
	}

	inline int Width() { return( width ); }
	inline int Height() { return( height ); }

	void ReadJpeg( const char *filename );
	void WriteJpeg( const char *filename ) const;
	void EncodeJpeg( JOCTET *outbuffer, int *outbuffer_size ) const;
	void Overlay( const Image &image );
	void Blend( const Image &image, double transparency=0.1 ) const;
	void Blend( const Image &image, int transparency=10 ) const;
	static Image *Merge( int n_images, Image *images[] );
	static Image *Merge( int n_images, Image *images[], double weight );
	static Image *Highlight( int n_images, Image *images[], const Rgb threshold=RGB_BLACK, const Rgb ref_colour=RGB_RED );
	Image *Delta( const Image &image, bool absolute=true ) const;
	bool CheckAlarms( Zone *zone, const Image *delta_image ) const;
	unsigned int Compare( const Image &image, int n_zones, Zone *zones[] ) const;
	void Annotate( const char *text, const Coord &coord, const Rgb colour );
	void Annotate( const char *text, const Coord &coord );
	void Timestamp( const char *label, time_t when, const Coord &coord );
	void Colourise();
	void DeColourise();
};

class Camera
{
friend class Image;

protected:
	int		id;
	char	*name;
	int		device;
	int		channel;
	int		format;
	unsigned int		width;
	unsigned int		height;
	int		colours;
	bool		capture;

protected:
	static int m_cap_frame;
	static int m_sync_frame;
	static video_mbuf m_vmb;
	static video_mmap *m_vmm;
	static int m_videohandle;
	static unsigned char *m_buffer;
	static int camera_count;

public:
	Camera( int p_id, char *p_name, int p_device, int p_channel, int p_format, int p_width, int p_height, int p_colours, bool p_capture=true );
	~Camera();
	inline int Id() const
	{
		return( id );
	}
	inline char *Name() const
	{
		return( name );
	}
	unsigned int Width() const { return( width ); }
	unsigned int Height() const { return( height ); }

	static void Initialise( int device, int channel, int format, int width, int height, int colours );
	void Terminate();

	inline void PreCapture()
	{
		//Info(( "%s: Capturing image\n", id ));

		if ( camera_count > 1 )
		{
			//Info(( "Switching\n" ));
			struct video_channel vs;

			vs.channel = channel;
			//vs.norm = VIDEO_MODE_AUTO;
			vs.norm = format;
			vs.flags = 0;
			vs.type = VIDEO_TYPE_CAMERA;
			if(ioctl(m_videohandle, VIDIOCSCHAN, &vs))
			{
				Error(( "Failed to set camera source %d: %s\n", channel, strerror(errno) ));
			}
		}
		//Info(( "MC:%d\n", m_videohandle ));
		if ( ioctl(m_videohandle, VIDIOCMCAPTURE, &m_vmm[m_cap_frame]) )
		{
			Error(( "Capture failure for frame %d: %s\n", m_cap_frame, strerror(errno)));
		}
		m_cap_frame = (m_cap_frame+1)%m_vmb.frames;
	}
	inline unsigned char *PostCapture()
	{
		//Info(( "%s: Capturing image\n", id ));

		if ( ioctl(m_videohandle, VIDIOCSYNC, &m_sync_frame) )
		{
			Error(( "Sync failure for frame %d: %s\n", m_sync_frame, strerror(errno)));
		}

		unsigned char *buffer = m_buffer+(m_sync_frame*m_vmb.size/m_vmb.frames);
		m_sync_frame = (m_sync_frame+1)%m_vmb.frames;

		return( buffer );
	}
	inline void PostCapture( Image &image )
	{
		//Info(( "%s: Capturing image\n", id ));

		if ( ioctl(m_videohandle, VIDIOCSYNC, &m_sync_frame) )
		{
			Error(( "Sync failure for frame %d: %s\n", m_sync_frame, strerror(errno)));
		}

		unsigned char *buffer = m_buffer+(m_sync_frame*m_vmb.size/m_vmb.frames);
		m_sync_frame = (m_sync_frame+1)%m_vmb.frames;

		image.Assign( width, height, colours, buffer );
	}
	inline unsigned char *Capture()
	{
		PreCapture();
		return( PostCapture() );
	}
	inline void Capture( Image &image )
	{
		PreCapture();
		return( PostCapture( image ) );
	}
};

class Event
{
friend class Monitor;

protected:
	int		id;
	Monitor	*monitor;
	time_t	start_time;
	time_t	end_time;
	int		start_frame_id;
	int		end_frame_id;
	int		frames;
	int		alarm_frames;
	unsigned int	tot_score;
	unsigned int	max_score;
	char	path[PATH_MAX];

public:
	Event( Monitor *p_monitor, time_t p_start_time );
	~Event();

	int Id() const { return( id ); }
	int Frames() const { return( frames ); }
	int AlarmFrames() const { return( alarm_frames ); }

	void AddFrame( time_t timestamp, const Image *image, const Image *alarm_frame=NULL, unsigned int score=0 );

	static void StreamEvent( const char *path, int event_id, unsigned long refresh=100, FILE *fd=stdout );
};

class Monitor : public Camera
{
protected:
	// These are read from the DB and thereafter remain unchanged
	char	label_format[64];	// The format of the timestamp on the images
	Coord	label_coord;		// The coordinates of the timestamp on the images
	int		warmup_count;		// How many images to process before looking for events
	int		pre_event_count;	// How many images to hold and prepend to an alarm event
	int		post_event_count;	// How many unalarmed images must occur before the alarm state is reset
	int		alarm_frame_count;	// How many alarm frames we must get before we actually do anything about it.
	int		image_buffer_count; // Size of circular image buffer, at least twice the size of the pre_event_count
	int		fps_report_interval;// How many images should be captured/processed between reporting the current FPS
	int		ref_blend_perc;		// Percentage of new image going into reference image.

	typedef enum
	{
		NONE=1,
		PASSIVE,
		ACTIVE
	} Function;
	Function	function;
	double	fps;
	Image	image;
	Image	ref_image;
	int		event_count;
	int		image_count;
	int		first_alarm_count;
	int		last_alarm_count;
	int		buffer_count;
	typedef enum { IDLE, ALARM, ALERT } State;
	State state;
	int		n_zones;
	Zone	**zones;
	Event	*event;
	time_t	start_time;
	time_t	last_fps_time;
	int		shmid;

	typedef struct Snapshot
	{
		time_t	*timestamp;
		Image	*image;
	};

	Snapshot *image_buffer;

	typedef struct
	{
		State state;
		int last_write_index;
		int last_read_index;
		int last_event;
		time_t *timestamps;
		unsigned char *images;
	} SharedImages;

	SharedImages *shared_images;

	bool record_zone_stats;
	
public:
	Monitor( int p_id, char *p_name, int p_function, int p_device, int p_channel, int p_format, int p_width, int p_height, int p_colours, bool p_capture, char *p_label_format, const Coord &p_label_coord, int p_warmup_count, int p_pre_event_count, int p_post_event_count, int p_alarm_frame_count, int p_image_buffer_count, int p_fps_report_interval, int p_ref_blend_perc, int p_n_zones=0, Zone *p_zones[]=0 );
	~Monitor();

	void AddZones( int p_n_zones, Zone *p_zones[] );

	State GetState() const;
	int GetImage( int index=-1 ) const;
	time_t GetTimestamp( int index=-1 ) const;
	unsigned int GetLastReadIndex() const;
	unsigned int GetLastWriteIndex() const;
	unsigned int GetLastEvent() const;
	double GetFPS() const;

	void CheckFunction();
	void DumpZoneImage();

	inline void Capture()
	{
		PreCapture();
		PostCapture();
	}
	inline void PostCapture()
	{
		char label_time_text[64];
		char label_text[64];

		Camera::PostCapture( image );

		time_t now = time( 0 );

		if ( label_format[0] )
		{
			strftime( label_time_text, sizeof(label_time_text), label_format, localtime( &now ) );
			sprintf( label_text, label_time_text, name );

			image.Annotate( label_text, label_coord );
		}

		int index = image_count%image_buffer_count;

		if ( index == shared_images->last_read_index )
		{
			Error(( "Buffer overrun at index %d\n", index ));
		}
		*(image_buffer[index].timestamp) = now;
		memcpy( image_buffer[index].image->buffer, image.buffer, image.size );
		//Info(( "%d: %x - %x", index, image_buffer[index].image, image_buffer[index].image->buffer ));

		shared_images->last_write_index = index;

		image_count++;

		if ( image_count && !(image_count%fps_report_interval) )
		{
			fps = double(fps_report_interval)/(now-last_fps_time);
			Info(( "%s: %d - Capturing at %.2f fps\n", name, image_count, fps ));
			last_fps_time = now;
		}
	}

	inline bool Ready()
	{
		return( function == ACTIVE && image_count > warmup_count );
	}
 
	void DumpImage( Image *image ) const;
	bool Analyse();

	void Adjust( double ratio )
	{
		ref_image.Blend( image, 0.1 );
	}

	void ReloadZones();
	static int Load( int device, Monitor **&monitors, bool capture=true );
	static Monitor *Load( int id, bool load_zones=false );
	void StreamImages( unsigned long idle=5000, unsigned long refresh=50, FILE *fd=stdout );
};
