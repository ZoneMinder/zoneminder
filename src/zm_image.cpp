//
// ZoneMinder Image Class Implementation, $Date$, $Revision$
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
#include "zm.h"
#include "zm_font.h"
#include "zm_image.h"

#include <sys/stat.h>
#include <errno.h>

#define ABSDIFF(a,b) 	(((a)<(b))?((b)-(a)):((a)-(b)))

bool Image::initialised = false;
unsigned char *Image::abs_table;
unsigned char *Image::y_r_table;
unsigned char *Image::y_g_table;
unsigned char *Image::y_b_table;
Image::BlendTablePtr Image::blend_tables[101];

jpeg_compress_struct *Image::jpg_ccinfo[100] = { 0 };
jpeg_decompress_struct *Image::jpg_dcinfo = 0;
struct zm_error_mgr Image::jpg_err;

Image::Image()
{
    if ( !initialised )
        Initialise();
    width = 0;
    height = 0;
    pixels = 0;
    colours = 0;
    size = 0;
    allocation = 0;
    buffer = 0;
    blend_buffer = 0;
    text[0] = '\0';
}

Image::Image( const char *filename )
{
    if ( !initialised )
        Initialise();
    width = 0;
    height = 0;
    pixels = 0;
    colours = 0;
    size = 0;
    allocation = 0;
    buffer = 0;
    ReadJpeg( filename );
    blend_buffer = 0;
    text[0] = '\0';
}

Image::Image( int p_width, int p_height, int p_colours, JSAMPLE *p_buffer )
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
        allocation = 0;
        buffer = p_buffer;
    }
    else
    {
        allocation = size;
        buffer = new JSAMPLE[allocation];
        memset( buffer, 0, size );
    }
    blend_buffer = 0;
    text[0] = '\0';
}

Image::Image( const Image &p_image )
{
    if ( !initialised )
        Initialise();
    width = p_image.width;
    height = p_image.height;
    pixels = p_image.pixels;
    colours = p_image.colours;
    size = allocation = p_image.size;
    buffer = new JSAMPLE[allocation];
    memcpy( buffer, p_image.buffer, size );
    blend_buffer = 0;
    strncpy( text, p_image.text, sizeof(text) );
}

Image::~Image()
{
    if ( allocation )
    {
        delete[] buffer;
    }
    delete[] blend_buffer;
}

void Image::Initialise()
{
	initialised = true;

	abs_table = new unsigned char[(6*255)+1];
	abs_table += (3*255);
	y_r_table = new unsigned char[511];
	y_r_table += 255;
	y_g_table = new unsigned char[511];
	y_g_table += 255;
	y_b_table = new unsigned char[511];
	y_b_table += 255;
	for ( int i = -(3*255); i <= (3*255); i++ )
	{
		abs_table[i] = abs(i);
	}
	for ( int i = -255; i <= 255; i++ )
	{
		y_r_table[i] = (2990*abs(i))/10000;
		y_g_table[i] = (5670*abs(i))/10000;
		y_b_table[i] = (1140*abs(i))/10000;
		//Info( "I:%d, R:%d, G:%d, B:%d", i, y_r_table[i], y_g_table[i], y_b_table[i] );
	}
	for ( int i = 0; i <= 100; i++ )
	{
		blend_tables[i] = 0;
	}
}

Image::BlendTablePtr Image::GetBlendTable( int transparency )
{
	BlendTablePtr blend_ptr = blend_tables[transparency];
	if ( !blend_ptr )
	{
		blend_ptr = blend_tables[transparency] = new BlendTable[1];
		//Info( "Generating blend table for transparency %d", transparency );
		int opacity = 100-transparency;
		//int round_up = 50/transparency;
		for ( int i = 0; i < 256; i++ )
		{
			for ( int j = 0; j < 256; j++ )
			{
				//(*blend_ptr)[i][j] = (JSAMPLE)((((i + round_up) * opacity)+((j + round_up) * transparency))/100);
				(*blend_ptr)[i][j] = (JSAMPLE)(((i * opacity)+(j * transparency))/100);
				//printf( "I:%d, J:%d, B:%d\n", i, j, (*blend_ptr)[i][j] );
			}
		}
	}
	return( blend_ptr );
}

void Image::Empty()
{
    if ( allocation )
    {
        delete[] buffer;
        buffer = 0;
        allocation = 0;
    }
    width = height = colours = size = 0;
}

void Image::Assign( int p_width, int p_height, int p_colours, unsigned char *new_buffer )
{
    if ( !buffer || p_width != width || p_height != height || p_colours != colours )
    {
        width = p_width;
        height = p_height;
        pixels = width*height;
        colours = p_colours;
        size = width*height*colours;
        if ( allocation < size )
        {
            allocation = size;
            delete[] buffer;
            buffer = new JSAMPLE[allocation];
            memset( buffer, 0, size );
        }
    }
    memcpy( buffer, new_buffer, size );
}

void Image::Assign( const Image &image )
{
    if ( !buffer || image.width != width || image.height != height || image.colours != colours )
    {
        width = image.width;
        height = image.height;
        pixels = width*height;
        colours = image.colours;
        size = width*height*colours;
        if ( allocation < size )
        {
            allocation = size;
            delete[] buffer;
            buffer = new JSAMPLE[allocation];
            memset( buffer, 0, size );
        }
    }
    memcpy( buffer, image.buffer, size );
}

Image *Image::HighlightEdges( Rgb colour, const Box *limits )
{
    if ( colours != 1 )
    {
        Panic( "Attempt to highlight image edges when colours = %d", colours );
    }
	Image *high_image = new Image( width, height, 3 );
	int lo_x = limits?limits->Lo().X():0;
	int lo_y = limits?limits->Lo().Y():0;
	int hi_x = limits?limits->Hi().X():width-1;
	int hi_y = limits?limits->Hi().Y():height-1;
	for ( int y = lo_y; y <= hi_y; y++ )
	{
		unsigned char *p = &buffer[(y*width)+lo_x];
		unsigned char *phigh = high_image->Buffer( lo_x, y );
		for ( int x = lo_x; x <= hi_x; x++, p++, phigh += 3 )
		{
			bool edge = false;
			if ( *p )
			{
				if ( !edge && x > 0 && !*(p-1) ) edge = true;
				if ( !edge && x < (width-1) && !*(p+1) ) edge = true;
				if ( !edge && y > 0 && !*(p-width) ) edge = true;
				if ( !edge && y < (height-1) && !*(p+width) ) edge = true;
			}
			if ( edge )
			{
				RED(phigh) = RGB_RED_VAL(colour);
				GREEN(phigh) = RGB_GREEN_VAL(colour);
				BLUE(phigh) = RGB_BLUE_VAL(colour);
			}
		}
	}
	return( high_image );
}

bool Image::ReadRaw( const char *filename )
{
	FILE *infile;
	if ( (infile = fopen( filename, "rb" )) == NULL )
	{
		Error( "Can't open %s: %s", filename, strerror(errno) );
		return( false );
	}

	struct stat statbuf;
	if ( fstat( fileno(infile), &statbuf ) < 0 )
	{
		Error( "Can't fstat %s: %s", filename, strerror(errno) );
		return( false );
	}

	if ( statbuf.st_size != size )
	{
		Error( "Raw file size mismatch, expected %d bytes, found %ld", size, statbuf.st_size );
		return( false );
	}

	if ( fread( buffer, size, 1, infile ) < 1 )
    {
        Fatal( "Unable to read from '%s': %s", filename, strerror(errno) );
        return( false );
    }

	fclose( infile );

	return( true );
}

bool Image::WriteRaw( const char *filename ) const
{
	FILE *outfile;
	if ( (outfile = fopen( filename, "wb" )) == NULL )
	{
		Error( "Can't open %s: %s", filename, strerror(errno) );
		return( false );
	}

	if ( fwrite( buffer, size, 1, outfile ) != 1 )
    {
        Error( "Unable to write to '%s': %s", filename, strerror(errno) );
        return( false );
    }

	fclose( outfile );

	return( true );
}

bool Image::ReadJpeg( const char *filename )
{
	struct jpeg_decompress_struct *cinfo = jpg_dcinfo;

	if ( !cinfo )
	{
		cinfo = jpg_dcinfo = new jpeg_decompress_struct;
		cinfo->err = jpeg_std_error( &jpg_err.pub );
	    jpg_err.pub.error_exit = zm_jpeg_error_exit;
	    jpg_err.pub.emit_message = zm_jpeg_emit_message;
		jpeg_create_decompress( cinfo );
	}

	FILE *infile;
	if ( (infile = fopen( filename, "rb" )) == NULL )
	{
		Error( "Can't open %s: %s", filename, strerror(errno) );
		return( false );
	}

	if ( setjmp( jpg_err.setjmp_buffer ) )
	{
		jpeg_abort_decompress( cinfo );
		fclose( infile );
		return( false );
	}

	jpeg_stdio_src( cinfo, infile );

	jpeg_read_header( cinfo, TRUE );

	if ( cinfo->image_width != width || cinfo->image_height != height || cinfo->num_components != colours )
	{
		width = cinfo->image_width;
		height = cinfo->image_height;
		pixels = width*height;
		colours = cinfo->num_components;
		if ( !(colours == 1 || colours == 3) )
        {
            Error( "Unexpected colours (%d) when reading jpeg image", colours );
		    jpeg_abort_decompress( cinfo );
		    fclose( infile );
            return( false );
        }
		size = width*height*colours;
		if ( !buffer || allocation < size )
		{
			allocation = size;
			delete[] buffer;
			buffer = new JSAMPLE[allocation];
		}
	}

	jpeg_start_decompress( cinfo );

	JSAMPROW row_pointer;	/* pointer to a single row */
	int row_stride = width * colours;	/* physical row width in buffer */
	while ( cinfo->output_scanline < cinfo->output_height )
	{
		row_pointer = &buffer[cinfo->output_scanline * row_stride];
		jpeg_read_scanlines( cinfo, &row_pointer, 1 );
	}

	jpeg_finish_decompress( cinfo );

	fclose( infile );

	return( true );
}

bool Image::WriteJpeg( const char *filename, int quality_override ) const
{
	if ( config.colour_jpeg_files && colours == 1 )
	{
		Image temp_image( *this );
		temp_image.Colourise();
		return( temp_image.WriteJpeg( filename, quality_override ) );
	}

	int quality = quality_override?quality_override:config.jpeg_file_quality;

	struct jpeg_compress_struct *cinfo = jpg_ccinfo[quality];

	if ( !cinfo )
	{
		cinfo = jpg_ccinfo[quality] = new jpeg_compress_struct;
		cinfo->err = jpeg_std_error( &jpg_err.pub );
	    jpg_err.pub.error_exit = zm_jpeg_error_exit;
	    jpg_err.pub.emit_message = zm_jpeg_emit_message;
		jpeg_create_compress( cinfo );
	}

	FILE *outfile;
	if ( (outfile = fopen( filename, "wb" )) == NULL )
	{
		Error( "Can't open %s: %s", filename, strerror(errno) );
		return( false );
	}
	jpeg_stdio_dest( cinfo, outfile );

	cinfo->image_width = width; 	/* image width and height, in pixels */
	cinfo->image_height = height;
	cinfo->input_components = colours;	/* # of color components per pixel */
	if ( colours == 1 )
	{
		cinfo->in_color_space = JCS_GRAYSCALE; /* colorspace of input image */
	}
	else
	{
		cinfo->in_color_space = JCS_RGB; /* colorspace of input image */
	}
	jpeg_set_defaults( cinfo );
	jpeg_set_quality( cinfo, quality, false );
	cinfo->dct_method = JDCT_FASTEST;

	jpeg_start_compress( cinfo, TRUE );
	if ( config.add_jpeg_comments && text[0] )
	{
		jpeg_write_marker( cinfo, JPEG_COM, (const JOCTET *)text, strlen(text) );
	}

	JSAMPROW row_pointer;	/* pointer to a single row */
	int row_stride = cinfo->image_width * cinfo->input_components;	/* physical row width in buffer */
	while ( cinfo->next_scanline < cinfo->image_height )
	{
		row_pointer = &buffer[cinfo->next_scanline * row_stride];
		jpeg_write_scanlines( cinfo, &row_pointer, 1 );
	}

	jpeg_finish_compress( cinfo );

	fclose( outfile );

	return( true );
}

bool Image::DecodeJpeg( const JOCTET *inbuffer, int inbuffer_size )
{
	struct jpeg_decompress_struct *cinfo = jpg_dcinfo;

	if ( !cinfo )
	{
		cinfo = jpg_dcinfo = new jpeg_decompress_struct;
		cinfo->err = jpeg_std_error( &jpg_err.pub );
	    jpg_err.pub.error_exit = zm_jpeg_error_exit;
	    jpg_err.pub.emit_message = zm_jpeg_emit_message;
		jpeg_create_decompress( cinfo );
	}

	if ( setjmp( jpg_err.setjmp_buffer ) )
	{
		jpeg_abort_decompress( cinfo );
		return( false );
	}

	zm_jpeg_mem_src( cinfo, inbuffer, inbuffer_size );

	jpeg_read_header( cinfo, TRUE );

	if ( cinfo->image_width != width || cinfo->image_height != height || cinfo->num_components != colours )
	{
		width = cinfo->image_width;
		height = cinfo->image_height;
		pixels = width*height;
		colours = cinfo->num_components;
		if ( !(colours == 1 || colours == 3) )
        {
            Error( "Unexpected colours (%d) when decoding jpeg image", colours );
		    jpeg_abort_decompress( cinfo );
            return( false );
        }
		size = width*height*colours;
		if ( !buffer || allocation < size )
		{
			allocation = size;
			delete[] buffer;
			buffer = new JSAMPLE[allocation];
		}
	}

	jpeg_start_decompress( cinfo );

	JSAMPROW row_pointer;	/* pointer to a single row */
	int row_stride = width * colours;	/* physical row width in buffer */
	while ( cinfo->output_scanline < cinfo->output_height )
	{
		row_pointer = &buffer[cinfo->output_scanline * row_stride];
		jpeg_read_scanlines( cinfo, &row_pointer, 1 );
	}

	jpeg_finish_decompress( cinfo );

	return( true );
}

bool Image::EncodeJpeg( JOCTET *outbuffer, int *outbuffer_size, int quality_override ) const
{
	if ( config.colour_jpeg_files && colours == 1 )
	{
		Image temp_image( *this );
		temp_image.Colourise();
		return( temp_image.EncodeJpeg( outbuffer, outbuffer_size, quality_override ) );
	}

	int quality = quality_override?quality_override:config.jpeg_stream_quality;

	struct jpeg_compress_struct *cinfo = jpg_ccinfo[quality];

	if ( !cinfo )
	{
		cinfo = jpg_ccinfo[quality] = new jpeg_compress_struct;
		cinfo->err = jpeg_std_error( &jpg_err.pub );
	    jpg_err.pub.error_exit = zm_jpeg_error_exit;
	    jpg_err.pub.emit_message = zm_jpeg_emit_message;
		jpeg_create_compress( cinfo );
	}

	zm_jpeg_mem_dest( cinfo, outbuffer, outbuffer_size );

	cinfo->image_width = width; 	/* image width and height, in pixels */
	cinfo->image_height = height;
	cinfo->input_components = colours;	/* # of color components per pixel */
	if ( colours == 1 )
	{
		cinfo->in_color_space = JCS_GRAYSCALE; /* colorspace of input image */
	}
	else
	{
		cinfo->in_color_space = JCS_RGB; /* colorspace of input image */
	}
	jpeg_set_defaults( cinfo );
	jpeg_set_quality( cinfo, quality, false );
	cinfo->dct_method = JDCT_FASTEST;

	jpeg_start_compress( cinfo, TRUE );

	JSAMPROW row_pointer;	/* pointer to a single row */
	int row_stride = cinfo->image_width * cinfo->input_components;	/* physical row width in buffer */
	while ( cinfo->next_scanline < cinfo->image_height )
	{
		row_pointer = &buffer[cinfo->next_scanline * row_stride];
		jpeg_write_scanlines( cinfo, &row_pointer, 1 );
	}

	jpeg_finish_compress( cinfo );

	return( true );
}

#if HAVE_ZLIB_H
bool Image::Unzip( const Bytef *inbuffer, unsigned long inbuffer_size )
{
	unsigned long zip_size = size;
	int result = uncompress( buffer, &zip_size, inbuffer, inbuffer_size );
	if ( result != Z_OK )
	{
		Error( "Unzip failed, result = %d", result );
		return( false );
	}
	if ( zip_size != size )
	{
		Error( "Unzip failed, size mismatch, expected %d bytes, got %ld", size, zip_size );
		return( false );
	}
	return( true );
}

bool Image::Zip( Bytef *outbuffer, unsigned long *outbuffer_size, int compression_level ) const
{
	int result = compress2( outbuffer, outbuffer_size, buffer, size, compression_level );
	if ( result != Z_OK )
	{
		Error( "Zip failed, result = %d", result );
		return( false );
	}
	return( true );
}
#endif // HAVE_ZLIB_H

bool Image::Crop( int lo_x, int lo_y, int hi_x, int hi_y )
{
	int new_width = (hi_x-lo_x)+1;
	int new_height = (hi_y-lo_y)+1;

	if ( lo_x > hi_x || lo_y > hi_y )
	{
		Error( "Invalid or reversed crop region %d,%d -> %d,%d", lo_x, lo_y, hi_x, hi_y );
		return( false );
	}
	if ( lo_x < 0 || hi_x > (width-1) || ( lo_y < 0 || hi_y > (height-1) ) )
	{
		Error( "Attempting to crop outside image, %d,%d -> %d,%d not in %d,%d", lo_x, lo_y, hi_x, hi_y, width-1, height-1 );
		return( false );
	}

	if ( new_width == width && new_height == height )
	{
		return( true );
	}

	int new_size = new_width*new_height*colours;
	JSAMPLE *new_buffer = new JSAMPLE[new_size];

	int new_stride = new_width*colours;
	for ( int y = lo_y, ny = 0; y <= hi_y; y++, ny++ )
	{
		unsigned char *pbuf = &buffer[((y*width)+lo_x)*colours];
		unsigned char *pnbuf = &new_buffer[(ny*new_width)*colours];
		memcpy( pnbuf, pbuf, new_stride );
	}

	if ( allocation )
	{
		delete[] buffer;
	}
	width = new_width;
	height = new_height;
	pixels = width*height;
	size = allocation = new_size;
	buffer = new_buffer;
	if ( blend_buffer )
	{
		delete[] blend_buffer;
		blend_buffer = 0;
	}

	return( true );
}

bool Image::Crop( const Box &limits )
{
    return( Crop( limits.LoX(), limits.LoY(), limits.HiX(), limits.HiY() ) );
}

void Image::Overlay( const Image &image )
{
	if ( !(width == image.width && height == image.height) )
    {
        Panic( "Attempt to overlay different sized images, expected %dx%d, got %dx%d", width, height, image.width, image.height );
    }

	unsigned char *pdest = buffer;
	unsigned char *psrc = image.buffer;

	if ( colours == 1 )
	{
		if ( image.colours == 1 )
		{
			while( pdest < (buffer+size) )
			{
				if ( *psrc )
				{
					*pdest = *psrc;
				}
				pdest++;
				psrc++;
			}
		}
		else
		{
			Colourise();
			pdest = buffer;
			while( pdest < (buffer+size) )
			{
				if ( RED(psrc) || GREEN(psrc) || BLUE(psrc) )
				{
					RED(pdest) = RED(psrc);
					GREEN(pdest) = GREEN(psrc);
					BLUE(pdest) = BLUE(psrc);
				}
				psrc += 3;
				pdest += 3;
			}
		}
	}
	else
	{
		if ( image.colours == 1 )
		{
			while( pdest < (buffer+size) )
			{
				if ( *psrc )
				{
					RED(pdest) = GREEN(pdest) = BLUE(pdest) = *psrc++;
				}
				pdest += 3;
			}
		}
		else
		{
			while( pdest < (buffer+size) )
			{
				if ( RED(psrc) || GREEN(psrc) || BLUE(psrc) )
				{
					RED(pdest) = RED(psrc);
					GREEN(pdest) = GREEN(psrc);
					BLUE(pdest) = BLUE(psrc);
				}
				psrc += 3;
				pdest += 3;
			}
		}
	}
}

void Image::Overlay( const Image &image, int x, int y )
{
	if ( !(width < image.width || height < image.height) )
    {
        Panic( "Attempt to overlay image too big for destination, %dx%d > %dx%d", image.width, image.height, width, height );
    }

	if ( !(width < (x+image.width) || height < (y+image.height)) )
    {
        Panic( "Attempt to overlay image outside of destination bounds, %dx%d @ %dx%d > %dx%d", image.width, image.height, x, y, width, height );
    }

	if ( !(colours == image.colours) )
    {
        Panic( "Attempt to partial overlay differently coloured images, expected %d, got %d", colours, image.colours );
    }

	int lo_x = x;
	int lo_y = y;
	int hi_x = (x+image.width)-1;
	int hi_y = (y+image.height-1);
	if ( colours == 1 )
	{
	    unsigned char *psrc = image.buffer;
		for ( int y = lo_y; y <= hi_y; y++ )
		{
			unsigned char *pdest = &buffer[(y*width)+lo_x];
			for ( int x = lo_x; x <= hi_x; x++ )
			{
				*pdest++ = *psrc++;
			}
		}
	}
	else if ( colours == 3 )
	{
	    unsigned char *psrc = image.buffer;
		for ( int y = lo_y; y <= hi_y; y++ )
		{
			unsigned char *pdest = &buffer[colours*((y*width)+lo_x)];
			for ( int x = lo_x; x <= hi_x; x++ )
			{
				*pdest++ = *psrc++;
				*pdest++ = *psrc++;
				*pdest++ = *psrc++;
			}
		}
	}
}

void Image::Blend( const Image &image, int transparency ) const
{
	if ( !(width == image.width && height == image.height && colours == image.colours) )
    {
        Panic( "Attempt to blend different sized images, expected %dx%dx%d, got %dx%dx%d", width, height, colours, image.width, image.height, image.colours );
    }

	if ( config.fast_image_blends )
	{
		BlendTablePtr blend_ptr = GetBlendTable( transparency );

		JSAMPLE *psrc = image.buffer;
		JSAMPLE *pdest = buffer;

		while( pdest < (buffer+size) )
		{
			*pdest++ = (*blend_ptr)[*pdest][*psrc++];
		}
	}
	else
	{
		if ( !blend_buffer )
		{
			blend_buffer = new unsigned int[size];

			unsigned int *pb = blend_buffer;
			JSAMPLE *p = buffer;
			
			while( p < (buffer+size) )
			{
				*pb++ = (unsigned int)((*p++)<<8);
			}
		}

		JSAMPLE *psrc = image.buffer;
		JSAMPLE *pdest = buffer;
		unsigned int *pblend = blend_buffer;
		int opacity = 100-transparency;

		while( pdest < (buffer+size) )
		{
			*pblend = (unsigned int)(((*pblend * opacity)+(((*psrc++)<<8) * transparency))/100);
			*pdest++ = (JSAMPLE)((*pblend++)>>8);
		}
	}
}

Image *Image::Merge( int n_images, Image *images[] )
{
	if ( n_images <= 0 ) return( 0 );
	if ( n_images == 1 ) return( new Image( *images[0] ) );

	int width = images[0]->width;
	int height = images[0]->height;
	int colours = images[0]->colours;
	for ( int i = 1; i < n_images; i++ )
	{
	    if ( !(width == images[i]->width && height == images[i]->height && colours == images[i]->colours) )
        {
            Panic( "Attempt to merge different sized images, expected %dx%dx%d, got %dx%dx%d, for image %d", width, height, colours, images[i]->width, images[i]->height, images[i]->colours, i );
        }
	}

	Image *result = new Image( width, height, images[0]->colours );
	int size = result->size;
	for ( int i = 0; i < size; i++ )
	{
		int total = 0;
		JSAMPLE *pdest = result->buffer;
		for ( int j = 0; j < n_images; j++ )
		{
			JSAMPLE *psrc = images[j]->buffer;
			total += *psrc;
			psrc++;
		}
		*pdest = total/n_images;
		pdest++;
	}
	return( result );
}

Image *Image::Merge( int n_images, Image *images[], double weight )
{
	if ( n_images <= 0 ) return( 0 );
	if ( n_images == 1 ) return( new Image( *images[0] ) );

	int width = images[0]->width;
	int height = images[0]->height;
	int colours = images[0]->colours;
	for ( int i = 1; i < n_images; i++ )
	{
	    if ( !(width == images[i]->width && height == images[i]->height && colours == images[i]->colours) )
        {
            Panic( "Attempt to merge different sized images, expected %dx%dx%d, got %dx%dx%d, for image %d", width, height, colours, images[i]->width, images[i]->height, images[i]->colours, i );
        }
	}

	Image *result = new Image( *images[0] );
	int size = result->size;
	double factor = 1.0*weight;
	for ( int i = 1; i < n_images; i++ )
	{
		JSAMPLE *pdest = result->buffer;
		JSAMPLE *psrc = images[i]->buffer;
		for ( int j = 0; j < size; j++ )
		{
			*pdest = (JSAMPLE)(((*pdest)*(1.0-factor))+((*psrc)*factor));
			pdest++;
			psrc++;
		}
		factor *= weight;
	}
	return( result );
}

Image *Image::Highlight( int n_images, Image *images[], const Rgb threshold, const Rgb ref_colour )
{
	if ( n_images <= 0 ) return( 0 );
	if ( n_images == 1 ) return( new Image( *images[0] ) );

	int width = images[0]->width;
	int height = images[0]->height;
	int colours = images[0]->colours;
	for ( int i = 1; i < n_images; i++ )
	{
	    if ( !(width == images[i]->width && height == images[i]->height && colours == images[i]->colours) )
        {
            Panic( "Attempt to highlight different sized images, expected %dx%dx%d, got %dx%dx%d, for image %d", width, height, colours, images[i]->width, images[i]->height, images[i]->colours, i );
        }
	}

	Image *result = new Image( width, height, images[0]->colours );
	int size = result->size;
	for ( int c = 0; c < 3; c++ )
	{
		for ( int i = 0; i < size; i++ )
		{
			int count = 0;
			JSAMPLE *pdest = result->buffer+c;
			for ( int j = 0; j < n_images; j++ )
			{
				JSAMPLE *psrc = images[j]->buffer+c;

				if ( (unsigned)abs((*psrc)-RGB_VAL(ref_colour,c)) >= RGB_VAL(threshold,c) )
				{
					count++;
				}
				psrc += 3;
			}
			*pdest = (count*255)/n_images;
			pdest += 3;
		}
	}
	return( result );
}

Image *Image::Delta( const Image &image ) const
{
	if ( !(width == image.width && height == image.height && colours == image.colours) )
    {
        Panic( "Attempt to get delta of different sized images, expected %dx%dx%d, got %dx%dx%d", width, height, colours, image.width, image.height, image.colours );
    }

	Image *result = new Image( width, height, 1 );

	unsigned char *psrc = buffer;
	unsigned char *pref = image.buffer;
	unsigned char *pdiff = result->buffer;

	if ( colours == 1 )
	{
		while( psrc < (buffer+size) )
		{
			//*pdiff++ = abs( *psrc++ - *pref++ );
			//*pdiff++ = ABSDIFF( *psrc, *pref );
			*pdiff++ = abs_table[*psrc++ - *pref++];
			//psrc++;
			//pref++;
		}
	}
	else
	{
		register int red, green, blue;
		while( psrc < (buffer+size) )
		{
			if ( config.y_image_deltas )
			{
				//Info( "RS:%d, RR: %d", *psrc, *pref );
				red = y_r_table[*psrc++ - *pref++];
				//Info( "GS:%d, GR: %d", *psrc, *pref );
				green = y_g_table[*psrc++ - *pref++];
				//Info( "BS:%d, BR: %d", *psrc, *pref );
				blue = y_b_table[*psrc++ - *pref++];

				//Info( "R:%d, G:%d, B:%d, D:%d", red, green, blue, abs_table[red + green + blue] );
				*pdiff++ = abs_table[red + green + blue];
			}
			else
			{
				red = abs_table[*psrc++ - *pref++];
				green = abs_table[*psrc++ - *pref++];
				blue = abs_table[*psrc++ - *pref++];

				// This is uses an RMS function, all floating point and 
				// rather too slow
				//*pdiff++ = (JSAMPLE)sqrt((red*red + green*green + blue*blue)/3);

				// This just uses the average difference, much faster
				*pdiff++ = (JSAMPLE)((red + green + blue)/3);
			}
		}
	}
	return( result );
}

const Coord Image::centreCoord( const char *text )
{
    int index = 0;
    int line_no = 0;
	int text_len = strlen( text );
    int line_len = 0;
    int max_line_len = 0;
    const char *line = text;

    while ( (index < text_len) && (line_len = strcspn( line, "\n" )) )
    {
        if ( line_len > max_line_len )
            max_line_len = line_len;

        index += line_len;
        while ( text[index] == '\n' )
        {
            index++;
        }
        line = text+index;
        line_no++;
    }
    int x = (width - (max_line_len * CHAR_WIDTH) ) / 2;
    int y = (height - (line_no * LINE_HEIGHT) ) / 2;
    return( Coord( x, y ) );
}

void Image::Annotate( const char *p_text, const Coord &coord, const Rgb fg_colour, const Rgb bg_colour )
{
	strncpy( text, p_text, sizeof(text) );

    int index = 0;
    int line_no = 0;
	int text_len = strlen( text );
    int line_len = 0;
    const char *line = text;

    char fg_r_col = RGB_RED_VAL(fg_colour);
    char fg_g_col = RGB_GREEN_VAL(fg_colour);
    char fg_b_col = RGB_BLUE_VAL(fg_colour);
    char fg_bw_col = (fg_r_col+fg_g_col+fg_b_col)/3;
    bool fg_trans = (fg_colour == RGB_TRANSPARENT);
    char bg_r_col = RGB_RED_VAL(bg_colour);
    char bg_g_col = RGB_GREEN_VAL(bg_colour);
    char bg_b_col = RGB_BLUE_VAL(bg_colour);
    char bg_bw_col = (bg_r_col+bg_g_col+bg_b_col)/3;
    bool bg_trans = (bg_colour == RGB_TRANSPARENT);

    while ( (index < text_len) && (line_len = strcspn( line, "\n" )) )
    {
        int line_width = line_len * CHAR_WIDTH;

        int lo_line_x = coord.X();
        int lo_line_y = coord.Y() + (line_no * LINE_HEIGHT);

        int min_line_x = 0;
        int max_line_x = width - line_width;
        int min_line_y = 0;
        int max_line_y = height - LINE_HEIGHT;

        if ( lo_line_x > max_line_x )
            lo_line_x = max_line_x;
        if ( lo_line_x < min_line_x )
            lo_line_x = min_line_x;
        if ( lo_line_y > max_line_y )
            lo_line_y = max_line_y;
        if ( lo_line_y < min_line_y )
            lo_line_y = min_line_y;

        int hi_line_x = lo_line_x + line_width;
        int hi_line_y = lo_line_y + LINE_HEIGHT;

        // Clip anything that runs off the right of the screen
        if ( hi_line_x > width )
            hi_line_x = width;
        if ( hi_line_y > height )
            hi_line_y = height;

        if ( colours == 1 )
        {
            unsigned char *ptr = &buffer[(lo_line_y*width)+lo_line_x];
            for ( int y = lo_line_y, r = 0; y < hi_line_y && r < CHAR_HEIGHT; y++, r++, ptr += width )
            {
                unsigned char *temp_ptr = ptr;
                for ( int x = lo_line_x, c = 0; x < hi_line_x && c < line_len; c++ )
                {
                    int f = fontdata[(line[c] * CHAR_HEIGHT) + r];
                    for ( int i = 0; i < CHAR_WIDTH && x < hi_line_x; i++, x++, temp_ptr++ )
                    {
                        if ( f & (0x80 >> i) )
                        {
                            if ( !fg_trans )
                                *temp_ptr = fg_bw_col;
                        }
                        else if ( !bg_trans )
                        {
                            *temp_ptr = bg_bw_col;
                        }
                    }
                }
            }
        }
        else
        {
            int wc = width * colours;

            unsigned char *ptr = &buffer[((lo_line_y*width)+lo_line_x)*colours];
            for ( int y = lo_line_y, r = 0; y < hi_line_y && r < CHAR_HEIGHT; y++, r++, ptr += wc )
            {
                unsigned char *temp_ptr = ptr;
                for ( int x = lo_line_x, c = 0; x < hi_line_x && c < line_len; c++ )
                {
                    int f = fontdata[(line[c] * CHAR_HEIGHT) + r];
                    for ( int i = 0; i < CHAR_WIDTH && x < hi_line_x; i++, x++, temp_ptr += colours )
                    {
                        if ( f & (0x80 >> i) )
                        {
                            if ( !fg_trans )
                            {
					            RED(temp_ptr) = fg_r_col;
					            GREEN(temp_ptr) = fg_g_col;
					            BLUE(temp_ptr) = fg_b_col;
                            }
                        }
                        else if ( !bg_trans )
                        {
					        RED(temp_ptr) = bg_r_col;
					        GREEN(temp_ptr) = bg_g_col;
					        BLUE(temp_ptr) = bg_b_col;
                        }
                    }
                }
            }
        }
        index += line_len;
        while ( text[index] == '\n' )
        {
            index++;
        }
        line = text+index;
        line_no++;
    }
}

void Image::Timestamp( const char *label, const time_t when, const Coord &coord )
{
	char time_text[64];
	strftime( time_text, sizeof(time_text), "%y/%m/%d %H:%M:%S", localtime( &when ) );
	char text[64];
	if ( label )
	{
		snprintf( text, sizeof(text), "%s - %s", label, time_text );
		Annotate( text, coord );
	}
	else
	{
		Annotate( time_text, coord );
	}
}

void Image::Colourise()
{
	if ( colours == 1 )
	{
		colours = 3;
		size = width * height * 3;
		JSAMPLE *new_buffer = new JSAMPLE[size];

		JSAMPLE *psrc = buffer;
		JSAMPLE *pdest = new_buffer;
		while( pdest < (new_buffer+size) )
		{
			RED(pdest) = GREEN(pdest) = BLUE(pdest) = *psrc++;
			pdest += 3;
		}
		delete[] buffer;
		buffer = new_buffer;
	}
}

void Image::DeColourise()
{
	if ( colours == 3 )
	{
		colours = 1;
		size = width * height;

		JSAMPLE *psrc = buffer;
		JSAMPLE *pdest = buffer;
		while( pdest < (buffer+size) )
		{
			*pdest++ = (JSAMPLE)sqrt((RED(psrc) + GREEN(psrc) + BLUE(psrc))/3);
			psrc += 3;
		}
	}
}

void Image::Fill( Rgb colour, const Box *limits )
{
	if ( !(colours == 1 || colours == 3 ) )
    {
        Panic( "Attempt to fill image with unexpected colours %d", colours );
    }
	int lo_x = limits?limits->Lo().X():0;
	int lo_y = limits?limits->Lo().Y():0;
	int hi_x = limits?limits->Hi().X():width-1;
	int hi_y = limits?limits->Hi().Y():height-1;
	if ( colours == 1 )
	{
		for ( int y = lo_y; y <= hi_y; y++ )
		{
			unsigned char *p = &buffer[(y*width)+lo_x];
			for ( int x = lo_x; x <= hi_x; x++ )
			{
				*p++ = colour;
			}
		}
	}
	else if ( colours == 3 )
	{
		for ( int y = lo_y; y <= hi_y; y++ )
		{
			unsigned char *p = &buffer[colours*((y*width)+lo_x)];
			for ( int x = lo_x; x <= hi_x; x++ )
			{
				RED(p) = RGB_RED_VAL(colour);
				GREEN(p) = RGB_GREEN_VAL(colour);
				BLUE(p) = RGB_BLUE_VAL(colour);
				p += colours;
			}
		}
	}
}

void Image::Fill( Rgb colour, int density, const Box *limits )
{
	if ( !(colours == 1 || colours == 3 ) )
    {
        Panic( "Attempt to fill image with unexpected colours %d", colours );
    }

	int lo_x = limits?limits->Lo().X():0;
	int lo_y = limits?limits->Lo().Y():0;
	int hi_x = limits?limits->Hi().X():width-1;
	int hi_y = limits?limits->Hi().Y():height-1;
	for ( int y = lo_y; y <= hi_y; y++ )
	{
		unsigned char *p = &buffer[colours*((y*width)+lo_x)];
		for ( int x = lo_x; x <= hi_x; x++, p += colours )
		{
			if ( ( x == lo_x || x == hi_x || y == lo_y || y == hi_y ) || (!(x%density) && !(y%density) ) )
			{
				if ( colours == 1 )
				{
					*p = colour;
				}
				else if ( colours == 3 )
				{
					RED(p) = RGB_RED_VAL(colour);
					GREEN(p) = RGB_GREEN_VAL(colour);
					BLUE(p) = RGB_BLUE_VAL(colour);
				}
			}
		}
	}
}

void Image::Outline( Rgb colour, const Polygon &polygon )
{
	if ( !(colours == 1 || colours == 3 ) )
    {
        Panic( "Attempt to outline image with unexpected colours %d", colours );
    }
	int n_coords = polygon.getNumCoords();
	for ( int j = 0, i = n_coords-1; j < n_coords; i = j++ )
	{
		const Coord &p1 = polygon.getCoord( i );
		const Coord &p2 = polygon.getCoord( j );

		int x1 = p1.X();
		int x2 = p2.X();
		int y1 = p1.Y();
		int y2 = p2.Y();

		double dx = x2 - x1;
		double dy = y2 - y1;

		double grad;

		Debug( 9, "dx: %.2lf, dy: %.2lf", dx, dy );
		if ( fabs(dx) <= fabs(dy) )
		{
			Debug( 9, "dx <= dy" );
			if ( y1 != y2 )
				grad = dx/dy;
			else
				grad = width;

			double x;
			int y, yinc = (y1<y2)?1:-1;
			grad *= yinc;
			if ( colours == 1 )
			{
				Debug( 9, "x1:%d, x2:%d, y1:%d, y2:%d, gr:%.2f", x1, x2, y1, y2, grad );
				for ( x = x1, y = y1; y != y2; y += yinc, x += grad )
				{
					Debug( 9, "x:%.2f, y:%d", x, y );
					buffer[(y*width)+int(round(x))] = colour;
				}
			}
			else if ( colours == 3 )
			{
				for ( x = x1, y = y1; y != y2; y += yinc, x += grad )
				{
					unsigned char *p = &buffer[colours*((y*width)+int(round(x)))];
					RED(p) = RGB_RED_VAL(colour);
					GREEN(p) = RGB_GREEN_VAL(colour);
					BLUE(p) = RGB_BLUE_VAL(colour);
				}
			}
		}
		else
		{
			Debug( 9, "dx > dy" );
			if ( x1 != x2 )
				grad = dy/dx;
			else
				grad = height;
			Debug( 9, "grad: %.2lf", grad );

			double y;
			int x, xinc = (x1<x2)?1:-1;
			grad *= xinc;
			if ( colours == 1 )
			{
				Debug( 9, "x1:%d, x2:%d, y1:%d, y2:%d, gr:%.2lf", x1, x2, y1, y2, grad );
				for ( y = y1, x = x1; x != x2; x += xinc, y += grad )
				{
					Debug( 9, "x:%d, y:%.2f", x, y );
					buffer[(int(round(y))*width)+x] = colour;
				}
			}
			else if ( colours == 3 )
			{
				for ( y = y1, x = x1; x != x2; x += xinc, y += grad )
				{
					unsigned char *p = &buffer[colours*((int(round(y))*width)+x)];
					RED(p) = RGB_RED_VAL(colour);
					GREEN(p) = RGB_GREEN_VAL(colour);
					BLUE(p) = RGB_BLUE_VAL(colour);
				}
			}
		}
	}
}

void Image::Fill( Rgb colour, int density, const Polygon &polygon )
{
	if ( !(colours == 1 || colours == 3 ) )
    {
        Panic( "Attempt to fill image with unexpected colours %d", colours );
    }

	int n_coords = polygon.getNumCoords();
	int n_global_edges = 0;
	Edge global_edges[n_coords];
	for ( int j = 0, i = n_coords-1; j < n_coords; i = j++ )
	{
		const Coord &p1 = polygon.getCoord( i );
		const Coord &p2 = polygon.getCoord( j );

		int x1 = p1.X();
		int x2 = p2.X();
		int y1 = p1.Y();
		int y2 = p2.Y();

		Debug( 9, "x1:%d,y1:%d x2:%d,y2:%d", x1, y1, x2, y2 );
		if ( y1 == y2 )
			continue;

		double dx = x2 - x1;
		double dy = y2 - y1;

		global_edges[n_global_edges].min_y = y1<y2?y1:y2;
		global_edges[n_global_edges].max_y = y1<y2?y2:y1;
		global_edges[n_global_edges].min_x = y1<y2?x1:x2;
		global_edges[n_global_edges]._1_m = dx/dy;
		n_global_edges++;
	}
	qsort( global_edges, n_global_edges, sizeof(*global_edges), Edge::CompareYX );

#ifndef ZM_DBG_OFF
	if ( zmDbgLevel >= 9 )
	{
		for ( int i = 0; i < n_global_edges; i++ )
		{
			Debug( 9, "%d: min_y: %d, max_y:%d, min_x:%.2f, 1/m:%.2f", i, global_edges[i].min_y, global_edges[i].max_y, global_edges[i].min_x, global_edges[i]._1_m );
		}
	}
#endif

	int n_active_edges = 0;
	Edge active_edges[n_global_edges];
	int y = global_edges[0].min_y;
	do 
	{
		for ( int i = 0; i < n_global_edges; i++ )
		{
			if ( global_edges[i].min_y == y )
			{
				Debug( 9, "Moving global edge" );
				active_edges[n_active_edges++] = global_edges[i];
				if ( i < (n_global_edges-1) )
				{
					//memcpy( &global_edges[i], &global_edges[i+1], sizeof(*global_edges)*(n_global_edges-i) );
					memmove( &global_edges[i], &global_edges[i+1], sizeof(*global_edges)*(n_global_edges-i) );
					i--;
				}
				n_global_edges--;
			}
			else
			{
				break;
			}
		}
		qsort( active_edges, n_active_edges, sizeof(*active_edges), Edge::CompareX );
#ifndef ZM_DBG_OFF
		if ( zmDbgLevel >= 9 )
		{
			for ( int i = 0; i < n_active_edges; i++ )
			{
				Debug( 9, "%d - %d: min_y: %d, max_y:%d, min_x:%.2f, 1/m:%.2f", y, i, active_edges[i].min_y, active_edges[i].max_y, active_edges[i].min_x, active_edges[i]._1_m );
			}
		}
#endif
		if ( !(y%density) )
		{
			//Debug( 9, "%d", y );
			for ( int i = 0; i < n_active_edges; )
			{
				int lo_x = int(round(active_edges[i++].min_x));
				int hi_x = int(round(active_edges[i++].min_x));
				unsigned char *p = &buffer[colours*((y*width)+lo_x)];
				for ( int x = lo_x; x <= hi_x; x++, p += colours )
				{
					if ( !(x%density) )
					{
						//Debug( 9, " %d", x );
						if ( colours == 1 )
						{
							*p = colour;
						}
						else
						{
							RED(p) = RGB_RED_VAL(colour);
							GREEN(p) = RGB_GREEN_VAL(colour);
							BLUE(p) = RGB_BLUE_VAL(colour);
						}
					}
				}
			}
		}
		y++;
		for ( int i = n_active_edges-1; i >= 0; i-- )
		{
			if ( y >= active_edges[i].max_y ) // Or >= as per sheets
			{
				Debug( 9, "Deleting active_edge" );
				if ( i < (n_active_edges-1) )
				{
					//memcpy( &active_edges[i], &active_edges[i+1], sizeof(*active_edges)*(n_active_edges-i) );
					memmove( &active_edges[i], &active_edges[i+1], sizeof(*active_edges)*(n_active_edges-i) );
				}
				n_active_edges--;
			}
			else
			{
				active_edges[i].min_x += active_edges[i]._1_m;
			}
		}
	} while ( n_global_edges || n_active_edges );
}

void Image::Fill( Rgb colour, const Polygon &polygon )
{
	Fill( colour, 1, polygon );
}

void Image::Rotate( int angle )
{
	angle %= 360;

	if ( !angle )
	{
		return;
	}
	if ( angle%90 )
	{
		return;
	}
	static unsigned char rotate_buffer[ZM_MAX_IMAGE_SIZE];
	switch( angle )
	{
		case 90 :
		{
			int temp = width;
			width = height;
			height = temp;

			int line_bytes = width*colours;
			unsigned char *s_ptr = buffer;

			if ( colours == 1 )
			{
				unsigned char *d_ptr;
				for ( int i = width-1; i >= 0; i-- )
				{
					d_ptr = rotate_buffer+i;
					for ( int j = height-1; j >= 0; j-- )
					{
						*d_ptr = *s_ptr++;
						d_ptr += line_bytes;
					}
				}
			}
			else
			{
				unsigned char *d_ptr;
				for ( int i = width-1; i >= 0; i-- )
				{
					d_ptr = rotate_buffer+(3*i);
					for ( int j = height-1; j >= 0; j-- )
					{
						*d_ptr = *s_ptr++;
						*(d_ptr+1) = *s_ptr++;
						*(d_ptr+2) = *s_ptr++;
						d_ptr += line_bytes;
					}
				}
			}
			break;
		}
		case 180 :
		{
			unsigned char *s_ptr = buffer+size;
			unsigned char *d_ptr = rotate_buffer;

			if ( colours == 1 )
			{
				while( s_ptr > buffer )
				{
					s_ptr--;
					*d_ptr++ = *s_ptr;
				}
			}
			else
			{
				while( s_ptr > buffer )
				{
					s_ptr -= 3;
					*d_ptr++ = *s_ptr;
					*d_ptr++ = *(s_ptr+1);
					*d_ptr++ = *(s_ptr+2);
				}
			}
			break;
		}
		case 270 :
		{
			int temp = width;
			width = height;
			height = temp;

			int line_bytes = width*colours;
			unsigned char *s_ptr = buffer+size;

			if ( colours == 1 )
			{
				unsigned char *d_ptr;
				for ( int i = width-1; i >= 0; i-- )
				{
					d_ptr = rotate_buffer+i;
					for ( int j = height-1; j >= 0; j-- )
					{
						s_ptr--;
						*d_ptr = *s_ptr;
						d_ptr += line_bytes;
					}
				}
			}
			else
			{
				unsigned char *d_ptr;
				for ( int i = width-1; i >= 0; i-- )
				{
					d_ptr = rotate_buffer+(3*i);
					for ( int j = height-1; j >= 0; j-- )
					{
						*(d_ptr+2) = *(--s_ptr);
						*(d_ptr+1) = *(--s_ptr);
						*d_ptr = *(--s_ptr);
						d_ptr += line_bytes;
					}
				}
			}
			break;
		}
	}
	memcpy( buffer, rotate_buffer, size );
}

void Image::Flip( bool leftright )
{
	static unsigned char flip_buffer[ZM_MAX_IMAGE_SIZE];
	int line_bytes = width*colours;
	int line_bytes2 = 2*line_bytes;
	if ( leftright )
	{
		// Horizontal flip, left to right
		unsigned char *s_ptr = buffer+line_bytes;
		unsigned char *d_ptr = flip_buffer;
		unsigned char *max_d_ptr = flip_buffer + size;

		if ( colours == 1 )
		{
			while( d_ptr < max_d_ptr )
			{
				for ( int j = 0; j < width; j++ )
				{
					s_ptr--;
					*d_ptr++ = *s_ptr;
				}
				s_ptr += line_bytes2;
			}
		}
		else
		{
			while( d_ptr < max_d_ptr )
			{
				for ( int j = 0; j < width; j++ )
				{
					s_ptr -= 3;
					*d_ptr++ = *s_ptr;
					*d_ptr++ = *(s_ptr+1);
					*d_ptr++ = *(s_ptr+2);
				}
				s_ptr += line_bytes2;
			}
		}
	}
	else
	{
		// Vertical flip, top to bottom
		unsigned char *s_ptr = buffer+(height*line_bytes);
		unsigned char *d_ptr = flip_buffer;

		while( s_ptr > buffer )
		{
			s_ptr -= line_bytes;
			memcpy( d_ptr, s_ptr, line_bytes );
			d_ptr += line_bytes;
		}
	}
	memcpy( buffer, flip_buffer, size );
}

void Image::Scale( unsigned int factor )
{
	if ( !factor )
	{
		Error( "Bogus scale factor %d found", factor );
		return;
	}
	if ( factor == ZM_SCALE_BASE )
	{
		return;
	}

	static unsigned char scale_buffer[ZM_MAX_IMAGE_SIZE];
	unsigned int new_width = (width*factor)/ZM_SCALE_BASE;
	unsigned int new_height = (height*factor)/ZM_SCALE_BASE;
	if ( factor > ZM_SCALE_BASE )
	{
		unsigned char *pd = scale_buffer;
		unsigned int wc = width*colours;
		unsigned int nwc = new_width*colours;
		unsigned int h_count = ZM_SCALE_BASE/2;
		unsigned int last_h_index = 0;
		unsigned int last_w_index = 0;
		unsigned int h_index;
		for ( int y = 0; y < height; y++ )
		{
			unsigned char *ps = &buffer[y*wc];
			unsigned int w_count = ZM_SCALE_BASE/2;
			unsigned int w_index;
			last_w_index = 0;
			for ( int x = 0; x < width; x++ )
			{
				w_count += factor;
				w_index = w_count/ZM_SCALE_BASE;
				for ( int f = last_w_index; f < w_index; f++ )
				{
					for ( int c = 0; c < colours; c++ )
					{
						*pd++ = *(ps+c);
					}
				}
				ps += colours;
				last_w_index = w_index;
			}
			h_count += factor;
			h_index = h_count/ZM_SCALE_BASE;
			for ( int f = last_h_index+1; f < h_index; f++ )
			{
				memcpy( pd, pd-nwc, nwc );
				pd += nwc;
			}
			last_h_index = h_index;
		}
        new_width = last_w_index;
        new_height = last_h_index;
	}
	else
	{
		unsigned char *pd = scale_buffer;
		unsigned int wc = width*colours;
		unsigned int xstart = factor/2;
		unsigned int ystart = factor/2;
		unsigned int h_count = ystart;
		unsigned int last_h_index = 0;
		unsigned int last_w_index = 0;
		unsigned int h_index;
		for ( unsigned int y = 0; y < height; y++ )
		{
			h_count += factor;
			h_index = h_count/ZM_SCALE_BASE;
			if ( h_index > last_h_index )
			{
				unsigned int w_count = xstart;
				unsigned int w_index;
				last_w_index = 0;

				unsigned char *ps = &buffer[y*wc];
				for ( unsigned int x = 0; x < width; x++ )
				{
					w_count += factor;
					w_index = w_count/ZM_SCALE_BASE;
					
					if ( w_index > last_w_index )
					{
						for ( int c = 0; c < colours; c++ )
						{
							*pd++ = *ps++;
						}
					}
					else
					{
						ps += colours;
					}
					last_w_index = w_index;
				}
			}
			last_h_index = h_index;
		}
        new_width = last_w_index;
        new_height = last_h_index;
	}
    Assign( new_width, new_height, colours, scale_buffer );
}
