//
// ZoneMinder Image Class Interface, $Date$, $Revision$
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

#ifndef ZM_IMAGE_H
#define ZM_IMAGE_H

#include "zm.h"
extern "C"
{
#include "zm_jpeg.h"
}
#include "zm_rgb.h"
#include "zm_coord.h"
#include "zm_box.h"
#include "zm_poly.h"

#if HAVE_ZLIB_H
#include <zlib.h>
#endif // HAVE_ZLIB_H

//
// This is image class, and represents a frame captured from a 
// camera in raw form.
//
class Image
{
protected:
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

public:
	enum { CHAR_HEIGHT=11, CHAR_WIDTH=6 };
    enum { LINE_HEIGHT=CHAR_HEIGHT+0 };

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
    int allocation;
	JSAMPLE *buffer;
	bool our_buffer;
	char text[1024];

protected:
	mutable unsigned int *blend_buffer;

protected:
	static void Initialise();
	static BlendTablePtr GetBlendTable( int );

public:
	Image();
	Image( const char *filename );
	Image( int p_width, int p_height, int p_colours, JSAMPLE *p_buffer=0 );
	Image( const Image &p_image );
	~Image();

	inline int Width() const { return( width ); }
	inline int Height() const { return( height ); }
	inline int Pixels() const { return( pixels ); }
	inline int Colours() const { return( colours ); }
	inline int Size() const { return( size ); }
	inline JSAMPLE *Buffer() const { return( buffer ); }
	inline JSAMPLE *Buffer( unsigned int x, unsigned int y= 0 ) const { return( &buffer[colours*((y*width)+x)] ); }
	
    void Empty();
	void Assign( int p_width, int p_height, int p_colours, unsigned char *new_buffer );
	void Assign( const Image &image );

	inline void CopyBuffer( const Image &image )
	{
		if ( image.size != size )
        {
            Panic( "Attempt to copy different size image buffers, expected %d, got %d", size, image.size );
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

#if HAVE_ZLIB_H
	bool Unzip( const Bytef *inbuffer, unsigned long inbuffer_size );
	bool Zip( Bytef *outbuffer, unsigned long *outbuffer_size, int compression_level=Z_BEST_SPEED ) const;
#endif // HAVE_ZLIB_H

	bool Crop( int lo_x, int lo_y, int hi_x, int hi_y );
	bool Crop( const Box &limits );

	void Overlay( const Image &image );
	void Overlay( const Image &image, int x, int y );
	void Blend( const Image &image, int transparency=10 ) const;
	static Image *Merge( int n_images, Image *images[] );
	static Image *Merge( int n_images, Image *images[], double weight );
	static Image *Highlight( int n_images, Image *images[], const Rgb threshold=RGB_BLACK, const Rgb ref_colour=RGB_RED );
	Image *Delta( const Image &image ) const;

    const Coord centreCoord( const char *text );
	void Annotate( const char *p_text, const Coord &coord,  const Rgb fg_colour=RGB_WHITE, const Rgb bg_colour=RGB_BLACK );
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
