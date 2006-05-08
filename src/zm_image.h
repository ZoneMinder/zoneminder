//
// ZoneMinder Image Class Interface, $Date$, $Revision$
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

#ifndef ZM_IMAGE_H
#define ZM_IMAGE_H

#include <stdio.h>
#include <stdlib.h>
#include <string.h>
#include <errno.h>
#include <time.h>
#include <math.h>
#include <zlib.h>

extern "C"
{
#include "zm_jpeg.h"
}

#include "zm_rgb.h"
#include "zm_coord.h"
#include "zm_box.h"
#include "zm_poly.h"

//
// This is image class, and represents a frame captured from a 
// camera in raw form.
//
class Image
{
protected:
	enum { CHAR_HEIGHT=11, CHAR_WIDTH=6 };
	typedef unsigned char BlendTable[256][256];
	typedef BlendTable *BlendTablePtr;

	struct Edge
	{
		int min_y;
		int max_y;
		double min_x;
		double _1_m;

		static int CompareYX( const void *p1, const void *p2 )
		{
			const Edge *e1 = (const Edge *)p1, *e2 = (const Edge *)p2;
			if ( e1->min_y == e2->min_y )
				return( int(e1->min_x - e2->min_x) );
			else
				return( int(e1->min_y - e2->min_y) );
		}
		static int CompareX( const void *p1, const void *p2 )
		{
			const Edge *e1 = (const Edge *)p1, *e2 = (const Edge *)p2;
			return( int(e1->min_x - e2->min_x) );
		}
	};

protected:
	static bool initialised;
	static unsigned char *abs_table;
	static unsigned char *y_r_table;
	static unsigned char *y_g_table;
	static unsigned char *y_b_table;
	static BlendTablePtr blend_tables[101];
	static jpeg_compress_struct *jpg_ccinfo[100];
	static jpeg_decompress_struct *jpg_dcinfo;
	static struct zm_error_mgr jpg_err;

protected:
	int	width;
	int height;
	int pixels;
	int colours;
	int size;
	JSAMPLE *buffer;
	bool our_buffer;
	char text[256];

protected:
	mutable unsigned int *blend_buffer;

protected:
	static void Initialise();
	static BlendTablePtr GetBlendTable( int );

public:
	Image()
	{
		if ( !initialised )
			Initialise();
		width = 0;
		height = 0;
		pixels = 0;
		colours = 0;
		size = 0;
		our_buffer = true;
		buffer = 0;
		blend_buffer = 0;
		text[0] = '\0';
	}
	Image( const char *filename )
	{
		if ( !initialised )
			Initialise();
		width = 0;
		height = 0;
		pixels = 0;
		colours = 0;
		size = 0;
		buffer = 0;
		ReadJpeg( filename );
		our_buffer = true;
		blend_buffer = 0;
		text[0] = '\0';
	}
	Image( int p_width, int p_height, int p_colours, JSAMPLE *p_buffer=0 )
	{
		if ( !initialised )
			Initialise();
		width = p_width;
		height = p_height;
		pixels = width*height;
		colours = p_colours;
		size = width*height*colours;
		if ( p_buffer )
		{
			our_buffer = false;
			buffer = p_buffer;
		}
		else
		{
			our_buffer = true;
			buffer = new JSAMPLE[size];
			memset( buffer, 0, size );
		}
		blend_buffer = 0;
		text[0] = '\0';
	}
	Image( const Image &p_image )
	{
		if ( !initialised )
			Initialise();
		width = p_image.width;
		height = p_image.height;
		pixels = p_image.pixels;
		colours = p_image.colours;
		size = p_image.size;
		buffer = new JSAMPLE[size];
		memcpy( buffer, p_image.buffer, size );
		our_buffer = true;
		blend_buffer = 0;
		strncpy( text, p_image.text, sizeof(text) );
	}
	~Image()
	{
		if ( our_buffer )
		{
			delete[] buffer;
		}
		delete[] blend_buffer;
	}

	inline int Width() const { return( width ); }
	inline int Height() const { return( height ); }
	inline int Pixels() const { return( pixels ); }
	inline int Colours() const { return( colours ); }
	inline int Size() const { return( size ); }
	inline JSAMPLE *Buffer() const { return( buffer ); }
	inline JSAMPLE *Buffer( unsigned int x, unsigned int y= 0 ) const { return( &buffer[colours*((y*width)+x)] ); }
	
	inline void Assign( int p_width, int p_height, int p_colours, unsigned char *new_buffer )
	{
		if ( p_width != width || p_height != height || p_colours != colours )
		{
			width = p_width;
			height = p_height;
			pixels = width*height;
			colours = p_colours;
			int new_size = width*height*colours;
			if ( size < new_size )
			{
				size = new_size;
				delete[] buffer;
				buffer = new JSAMPLE[size];
				memset( buffer, 0, size );
			}
		}
		memcpy( buffer, new_buffer, size );
	}
	inline void Assign( const Image &image )
	{
		if ( image.width != width || image.height != height || image.colours != colours )
		{
			width = image.width;
			height = image.height;
			pixels = width*height;
			colours = image.colours;
			int new_size = width*height*colours;
			if ( size < new_size )
			{
				size = new_size;
				delete[] buffer;
				buffer = new JSAMPLE[size];
				memset( buffer, 0, size );
			}
		}
		memcpy( buffer, image.buffer, size );
	}

	inline void CopyBuffer( const Image &image )
	{
		if ( image.size != size )
        {
            Fatal(( "Attempt to copy different size image buffers, expected %d, got %d", size, image.size ));
        }
		memcpy( buffer, image.buffer, size );
	}
	inline Image &operator=( const unsigned char *new_buffer )
	{
		memcpy( buffer, new_buffer, size );
		return( *this );
	}

	bool ReadRaw( const char *filename );
	bool WriteRaw( const char *filename ) const;

	bool ReadJpeg( const char *filename );
	bool WriteJpeg( const char *filename, int quality_override=0 ) const;
	bool DecodeJpeg( const JOCTET *inbuffer, int inbuffer_size );
	bool EncodeJpeg( JOCTET *outbuffer, int *outbuffer_size, int quality_override=0 ) const;

	bool Unzip( const Bytef *inbuffer, unsigned long inbuffer_size );
	bool Zip( Bytef *outbuffer, unsigned long *outbuffer_size, int compression_level=Z_BEST_SPEED ) const;

	bool Crop( int lo_x, int lo_y, int hi_y, int hi_y );

	void Overlay( const Image &image );
	void Blend( const Image &image, int transparency=10 ) const;
	static Image *Merge( int n_images, Image *images[] );
	static Image *Merge( int n_images, Image *images[], double weight );
	static Image *Highlight( int n_images, Image *images[], const Rgb threshold=RGB_BLACK, const Rgb ref_colour=RGB_RED );
	Image *Delta( const Image &image ) const;

	void Annotate( const char *p_text, const Coord &coord, const Rgb colour );
	void Annotate( const char *p_text, const Coord &coord );
	Image *HighlightEdges( Rgb colour, const Box *limits=0 );
	//Image *HighlightEdges( Rgb colour, const Polygon &polygon );
	void Timestamp( const char *label, const time_t when, const Coord &coord );
	void Colourise();
	void DeColourise();

	void Clear() { memset( buffer, 0, size ); }
	void Fill( Rgb colour, const Box *limits=0 );
	void Fill( Rgb colour, int density, const Box *limits=0 );
	void Outline( Rgb colour, const Polygon &polygon );
	void Fill( Rgb colour, const Polygon &polygon );
	void Fill( Rgb colour, int density, const Polygon &polygon );

	void Rotate( int angle );
	void Flip( bool leftright );

	void Scale( unsigned int factor );
};

#endif // ZM_IMAGE_H
