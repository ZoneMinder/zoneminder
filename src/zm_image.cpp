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
#include "zm_utils.h"
#include "zm_rgb.h"

#include <sys/stat.h>
#include <errno.h>

bool Image::initialised = false;
static unsigned char *y_table;
static signed char *uv_table;
static short *r_v_table;
static short *g_v_table;
static short *g_u_table;
static short *b_u_table;
__attribute__((aligned(16))) static const uint8_t movemask[16] = {0,4,8,12,0xFF,0xFF,0xFF,0xFF,0xFF,0xFF,0xFF,0xFF,0xFF,0xFF,0xFF,0xFF};

jpeg_compress_struct *Image::jpg_ccinfo[101] = { 0 };
jpeg_decompress_struct *Image::jpg_dcinfo = 0;
struct zm_error_mgr Image::jpg_err;

/* Pointer to blend function. */
static blend_fptr_t fptr_blend;

/* Pointer to delta8 functions */
static delta_fptr_t fptr_delta8_rgb;
static delta_fptr_t fptr_delta8_bgr;
static delta_fptr_t fptr_delta8_rgba;
static delta_fptr_t fptr_delta8_bgra;
static delta_fptr_t fptr_delta8_argb;
static delta_fptr_t fptr_delta8_abgr;
static delta_fptr_t fptr_delta8_gray8;

/* Pointers to deinterlace_4field functions */
static deinterlace_4field_fptr_t fptr_deinterlace_4field_rgba;
static deinterlace_4field_fptr_t fptr_deinterlace_4field_bgra;
static deinterlace_4field_fptr_t fptr_deinterlace_4field_argb;
static deinterlace_4field_fptr_t fptr_deinterlace_4field_abgr;
static deinterlace_4field_fptr_t fptr_deinterlace_4field_gray8;

/* Pointer to image buffer memory copy function */
imgbufcpy_fptr_t fptr_imgbufcpy;

Image::Image()
{
    if ( !initialised )
        Initialise();
    width = 0;
    height = 0;
    pixels = 0;
    colours = 0;
    subpixelorder = 0;
    size = 0;
    allocation = 0;
    buffer = 0;
    buffertype = 0;
    holdbuffer = 0;
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
    subpixelorder = 0;    
    size = 0;
    allocation = 0;
    buffer = 0;
    buffertype = 0;
    holdbuffer = 0;
    ReadJpeg( filename, ZM_COLOUR_RGB24, ZM_SUBPIX_ORDER_RGB);
    text[0] = '\0';
}

Image::Image( int p_width, int p_height, int p_colours, int p_subpixelorder, uint8_t *p_buffer )
{
    if ( !initialised )
        Initialise();
    width = p_width;
    height = p_height;
    pixels = width*height;
    colours = p_colours;
    subpixelorder = p_subpixelorder;
    size = (width*height)*colours;
    buffer = 0;
    holdbuffer = 0;
    if ( p_buffer )
    {
	allocation = size;
	buffertype = ZM_BUFTYPE_DONTFREE;
        buffer = p_buffer;
    }
    else
    {
        AllocImgBuffer(size);
    }
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
    subpixelorder = p_image.subpixelorder;
    size = allocation = p_image.size;
    buffer = 0;
    holdbuffer = 0;
    AllocImgBuffer(allocation);
    (*fptr_imgbufcpy)(buffer, p_image.buffer, size);
    strncpy( text, p_image.text, sizeof(text) );
}

Image::~Image()
{
	DumpImgBuffer();
	if ( initialised )
	{
		delete[] y_table;
		delete[] uv_table;
		delete[] r_v_table;
		delete[] g_v_table;
		delete[] g_u_table;
		delete[] b_u_table;
		initialised = false;
	}
	if ( jpg_dcinfo )
	{
		jpeg_destroy_decompress( jpg_dcinfo );
		delete jpg_dcinfo;
		jpg_dcinfo = 0;
	}
}

void Image::Initialise()
{
	/* Assign the blend pointer to function */
	if(config.fast_image_blends) {
		if(config.cpu_extensions && sseversion >= 20) {
			fptr_blend = &sse2_fastblend; /* SSE2 fast blend */
			Debug(2,"Blend: Using SSE2 fast blend function");
		} else {
			fptr_blend = &std_fastblend;  /* standard fast blend */
			Debug(2,"Blend: Using fast blend function");
		}
	} else {
		fptr_blend = &std_blend;
		Debug(2,"Blend: Using standard blend function");
	}
	
	__attribute__((aligned(16))) uint8_t blend1[16] = {142,255,159,91,88,227,0,52,37,80,152,97,104,252,90,82};
	__attribute__((aligned(16))) uint8_t blend2[16] = {129,56,136,96,119,149,94,29,96,176,1,144,230,203,111,172};
	__attribute__((aligned(16))) uint8_t blendres[16];
	__attribute__((aligned(16))) uint8_t blendexp[16] = {141,231,157,92,91,217,11,49,45,92,133,103,119,246,92,93}; /* Expected results for 12.5% blend */
	
	(*fptr_blend)(blend1,blend2,blendres,16,12.5);
	
	/* Compare results with expected results */
	for(int i=0;i<16;i++) {
		if(abs(blendexp[i] - blendres[i]) > 3) {
			Panic("Blend function failed self-test: Results differ from the expected results");
		}
	}
	
	fptr_delta8_rgb = &std_delta8_rgb;
	fptr_delta8_bgr = &std_delta8_bgr;
	
	/* Assign the delta functions */
	if(config.cpu_extensions) {
		if(sseversion >= 35) {
			/* SSSE3 available */
			fptr_delta8_rgba = &ssse3_delta8_rgba;
			fptr_delta8_bgra = &ssse3_delta8_bgra;
			fptr_delta8_argb = &ssse3_delta8_argb;
			fptr_delta8_abgr = &ssse3_delta8_abgr;
			fptr_delta8_gray8 = &sse2_delta8_gray8;
			Debug(2,"Delta: Using SSSE3 delta functions");
		} else if(sseversion >= 20) {
			/* SSE2 available */
			fptr_delta8_rgba = &sse2_delta8_rgba;
			fptr_delta8_bgra = &sse2_delta8_bgra;
			fptr_delta8_argb = &sse2_delta8_argb;
			fptr_delta8_abgr = &sse2_delta8_abgr;
			/*
			** On some systems, the 4 SSE2 algorithms above might be a little slower than
			** the standard algorithms, especially on early Pentium 4 processors.
			** In that case, comment out the 4 lines above and uncomment the 4 lines below
			*/
			// fptr_delta8_rgba = &std_delta8_rgba;
			// fptr_delta8_bgra = &std_delta8_bgra;
			// fptr_delta8_argb = &std_delta8_argb;
			// fptr_delta8_abgr = &std_delta8_abgr;
			fptr_delta8_gray8 = &sse2_delta8_gray8;
			Debug(2,"Delta: Using SSE2 delta functions");
		} else {
			/* No suitable SSE version available */
			fptr_delta8_rgba = &std_delta8_rgba;
			fptr_delta8_bgra = &std_delta8_bgra;
			fptr_delta8_argb = &std_delta8_argb;
			fptr_delta8_abgr = &std_delta8_abgr;
			fptr_delta8_gray8 = &std_delta8_gray8;
			Debug(2,"Delta: Using standard delta functions");
		}
	} else {
		/* CPU extensions disabled */
		fptr_delta8_rgba = &std_delta8_rgba;
		fptr_delta8_bgra = &std_delta8_bgra;
		fptr_delta8_argb = &std_delta8_argb;
		fptr_delta8_abgr = &std_delta8_abgr;
		fptr_delta8_gray8 = &std_delta8_gray8;
		Debug(2,"Delta: CPU extensions disabled, using standard delta functions");
	}
	
	/* Use SSSE3 deinterlace functions? */
	if(config.cpu_extensions && sseversion >= 35) {
		fptr_deinterlace_4field_rgba = &ssse3_deinterlace_4field_rgba;
		fptr_deinterlace_4field_bgra = &ssse3_deinterlace_4field_bgra;
		fptr_deinterlace_4field_argb = &ssse3_deinterlace_4field_argb;
		fptr_deinterlace_4field_abgr = &ssse3_deinterlace_4field_abgr;
		fptr_deinterlace_4field_gray8 = &ssse3_deinterlace_4field_gray8;
		Debug(2,"Deinterlace: Using SSSE3 delta functions");
	} else {
		fptr_deinterlace_4field_rgba = &std_deinterlace_4field_rgba;
		fptr_deinterlace_4field_bgra = &std_deinterlace_4field_bgra;
		fptr_deinterlace_4field_argb = &std_deinterlace_4field_argb;
		fptr_deinterlace_4field_abgr = &std_deinterlace_4field_abgr;
		fptr_deinterlace_4field_gray8 = &std_deinterlace_4field_gray8;
		Debug(2,"Deinterlace: Using standard delta functions");
	}
	
	/* Use SSE2 aligned memory copy? */
	if(config.cpu_extensions && sseversion >= 20) {
		fptr_imgbufcpy = &sse2_aligned_memcpy;
		Debug(2,"Image buffer copy: Using SSE2 aligned memcpy");
	} else {
		fptr_imgbufcpy = &memcpy;
		Debug(2,"Image buffer copy: Using standard memcpy");
	}
	
	/* Code below relocated from zm_local_camera */
	Debug( 3, "Setting up static colour tables" );
	
	y_table = new unsigned char[256];
	for ( int i = 0; i <= 255; i++ )
	{
		unsigned char c = i;
		if ( c <= 16 )
			y_table[c] = 0;
		else if ( c >= 235 )
			y_table[c] = 255;
		else
			y_table[c] = (255*(c-16))/219;
	}

	uv_table = new signed char[256];
	for ( int i = 0; i <= 255; i++ )
	{
		unsigned char c = i;
		if ( c <= 16 )
			uv_table[c] = -127;
		else if ( c >= 240 )
			uv_table[c] = 127;
		else
			uv_table[c] = (127*(c-128))/112;
	}

	r_v_table = new short[255];
	g_v_table = new short[255];
	g_u_table = new short[255];
	b_u_table = new short[255];
	for ( int i = 0; i < 255; i++ )
	{
		r_v_table[i] = (1402*(i-128))/1000;
		g_u_table[i] = (344*(i-128))/1000;
		g_v_table[i] = (714*(i-128))/1000;
		b_u_table[i] = (1772*(i-128))/1000;
	}
	
	initialised = true;
}

/* Requests a writeable buffer to the image. This is safer than buffer() because this way we can gurantee that a buffer of required size exists */
uint8_t* Image::WriteBuffer(const unsigned int p_width, const unsigned int p_height, const unsigned int p_colours, const unsigned int p_subpixelorder) {
	unsigned int newsize;
  
	if(p_colours != ZM_COLOUR_GRAY8 && p_colours != ZM_COLOUR_RGB24 && p_colours != ZM_COLOUR_RGB32) {
		Error("WriteBuffer called with unexpected colours: %d",p_colours);
		return NULL;
	}
	
	if(!p_height || !p_width) {
		Error("WriteBuffer called with invaid width or height: %d %d",p_width,p_height);
		return NULL;
	}
	
	if(p_width != width || p_height != height || p_colours != colours || p_subpixelorder != subpixelorder) {
		newsize = (p_width * p_height) * p_colours;
		
		if(buffer == NULL) {
			AllocImgBuffer(newsize);
		} else {
			if(allocation < newsize) {
				if(holdbuffer) {
					Error("Held buffer is undersized for requested buffer");
					return NULL;
				} else {
					/* Replace buffer with a bigger one */
					DumpImgBuffer();
					AllocImgBuffer(newsize);
				}
			}
		}
		
		width = p_width;
		height = p_height;
		colours = p_colours;
		subpixelorder = p_subpixelorder;
		pixels = height*width;
		size = newsize; 
	}
	
	return buffer; 
  
}

/* Assign an existing buffer to the image instead of copying from a source buffer. The goal is to reduce the amount of memory copying and increase efficiency and buffer reusing. */
void Image::AssignDirect( const unsigned int p_width, const unsigned int p_height, const unsigned int p_colours, const unsigned int p_subpixelorder, uint8_t *new_buffer, const size_t buffer_size, const int p_buffertype) {
	if(new_buffer == NULL) {
		Error("Attempt to directly assign buffer from a NULL pointer");
		return;
	}

	if(!p_height || !p_width) {
		Error("Attempt to directly assign buffer with invalid width or height: %d %d",p_width,p_height);
		return;
	}

	if(p_colours != ZM_COLOUR_GRAY8 && p_colours != ZM_COLOUR_RGB24 && p_colours != ZM_COLOUR_RGB32) {
		Error("Attempt to directly assign buffer with unexpected colours per pixel: %d",p_colours);
		return;
	}

	unsigned int new_buffer_size = ((p_width*p_height)*p_colours);
	
	if(buffer_size < new_buffer_size) {
		Error("Attempt to directly assign buffer from an undersized buffer of size: %zu, needed %dx%d*%d colours = %zu",buffer_size, p_width, p_height, p_colours );
		return;
	}
	
	if(holdbuffer && buffer) {
		if((unsigned int)((p_height*p_width)*p_colours) > allocation) {
			Error("Held buffer is undersized for assigned buffer");
			return;
		} else {
			width = p_width;
			height = p_height;
			colours = p_colours;
			subpixelorder = p_subpixelorder;
			pixels = height*width;
			size = new_buffer_size; // was pixels*colours, but we already calculated it above as new_buffer_size
			
			/* Copy into the held buffer */
			if(new_buffer != buffer)
				(*fptr_imgbufcpy)(buffer, new_buffer, size);
			
			/* Free the new buffer */
			DumpBuffer(new_buffer, p_buffertype);
		}
	} else {
		/* Free an existing buffer if any */
		DumpImgBuffer();
	  
		width = p_width;
		height = p_height;
		colours = p_colours;
		subpixelorder = p_subpixelorder;
		pixels = height*width;
		size = new_buffer_size; // was pixels*colours, but we already calculated it above as new_buffer_size
	
		allocation = buffer_size;
		buffertype = p_buffertype;
		buffer = new_buffer;
	}
	
}

void Image::Assign(const unsigned int p_width, const unsigned int p_height, const unsigned int p_colours, const unsigned int p_subpixelorder, const uint8_t* new_buffer, const size_t buffer_size) {
	unsigned int new_size = (p_width * p_height) * p_colours;
  
	if(new_buffer == NULL) {
		Error("Attempt to assign buffer from a NULL pointer");
		return;
	}
	
	if(buffer_size < new_size) {
		Error("Attempt to assign buffer from an undersized buffer of size: %zu",buffer_size);
		return;
	}
	
	if(!p_height || !p_width) {
		Error("Attempt to assign buffer with invalid width or height: %d %d",p_width,p_height);
		return;
	}
	
	if(p_colours != ZM_COLOUR_GRAY8 && p_colours != ZM_COLOUR_RGB24 && p_colours != ZM_COLOUR_RGB32) {
		Error("Attempt to assign buffer with unexpected colours per pixel: %d",p_colours);
		return;
	}
	
	if ( !buffer || p_width != width || p_height != height || p_colours != colours || p_subpixelorder != subpixelorder) {

		if (holdbuffer && buffer) {
			if (new_size > allocation) {
				Error("Held buffer is undersized for assigned buffer");
				return;
			}
		} else {
			if(new_size > allocation || !buffer) { 
				DumpImgBuffer();
				AllocImgBuffer(new_size);
			}
		}
		
		width = p_width;
		height = p_height;
		pixels = width*height;
		colours = p_colours;
		subpixelorder = p_subpixelorder;
		size = new_size;
	}
	
	if(new_buffer != buffer)
		(*fptr_imgbufcpy)(buffer, new_buffer, size);
	
}

void Image::Assign( const Image &image ) {
	unsigned int new_size = (image.width * image.height) * image.colours;
	
	if(image.buffer == NULL) {
		Error("Attempt to assign image with an empty buffer");
		return;
	}
  
	if(image.colours != ZM_COLOUR_GRAY8 && image.colours != ZM_COLOUR_RGB24 && image.colours != ZM_COLOUR_RGB32) {
		Error("Attempt to assign image with unexpected colours per pixel: %d",image.colours);
		return;
	}
	
	if ( !buffer || image.width != width || image.height != height || image.colours != colours || image.subpixelorder != subpixelorder) {

		if (holdbuffer && buffer) {
			if (new_size > allocation) {
				Error("Held buffer is undersized for assigned buffer");
				return;
			}
		} else {
			if(new_size > allocation || !buffer) { 
				DumpImgBuffer();
				AllocImgBuffer(new_size);
			}
		}
		
		width = image.width;
		height = image.height;
		pixels = width*height;
		colours = image.colours;
		subpixelorder = image.subpixelorder;
		size = new_size;
	}
	
	if(image.buffer != buffer)
		(*fptr_imgbufcpy)(buffer, image.buffer, size);
}

Image *Image::HighlightEdges( Rgb colour, unsigned int p_colours, unsigned int p_subpixelorder, const Box *limits )
{
	if ( colours != ZM_COLOUR_GRAY8 )
	{
		Panic( "Attempt to highlight image edges when colours = %d", colours );
	}
	
	/* Convert the colour's RGBA subpixel order into the image's subpixel order */
	colour = rgb_convert(colour,p_subpixelorder);
	
	/* Create a new image of the target format */
	Image *high_image = new Image( width, height, p_colours, p_subpixelorder );
	uint8_t* high_buff = high_image->WriteBuffer(width, height, p_colours, p_subpixelorder);
	
	/* Set image to all black */
	high_image->Clear();

	unsigned int lo_x = limits?limits->Lo().X():0;
	unsigned int lo_y = limits?limits->Lo().Y():0;
	unsigned int hi_x = limits?limits->Hi().X():width-1;
	unsigned int hi_y = limits?limits->Hi().Y():height-1;
	
	if ( p_colours == ZM_COLOUR_GRAY8 )
	{
		for ( unsigned int y = lo_y; y <= hi_y; y++ )
		{
			const uint8_t* p = buffer + (y * width) + lo_x;
			uint8_t* phigh = high_buff + (y * width) + lo_x;
			for ( unsigned int x = lo_x; x <= hi_x; x++, p++, phigh++ )
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
					*phigh = colour;
				}
			}
		}
	}
	else if ( p_colours == ZM_COLOUR_RGB24 )
	{
		for ( unsigned int y = lo_y; y <= hi_y; y++ )
		{
			const uint8_t* p = buffer + (y * width) + lo_x;
			uint8_t* phigh = high_buff + (((y * width) + lo_x) * 3);
			for ( unsigned int x = lo_x; x <= hi_x; x++, p++, phigh += 3 )
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
					RED_PTR_RGBA(phigh) = RED_VAL_RGBA(colour);
					GREEN_PTR_RGBA(phigh) = GREEN_VAL_RGBA(colour);
					BLUE_PTR_RGBA(phigh) = BLUE_VAL_RGBA(colour);
				}
			}
		}
	}
	else if ( p_colours == ZM_COLOUR_RGB32 )
	{
		for ( unsigned int y = lo_y; y <= hi_y; y++ )
		{
			const uint8_t* p = buffer + (y * width) + lo_x;
			Rgb* phigh = (Rgb*)(high_buff + (((y * width) + lo_x) * 4));
			for ( unsigned int x = lo_x; x <= hi_x; x++, p++, phigh++ )
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
					*phigh = colour;
				}
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

bool Image::ReadJpeg( const char *filename, unsigned int p_colours, unsigned int p_subpixelorder)
{
	unsigned int new_width, new_height, new_colours, new_subpixelorder;
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

	if ( cinfo->num_components != 1 && cinfo->num_components != 3 )
	{
		Error( "Unexpected colours when reading jpeg image: %d", colours );
		jpeg_abort_decompress( cinfo );
		fclose( infile );
		return( false );
	}
	
	/* Check if the image has at least one huffman table defined. If not, use the standard ones */
	/* This is required for the MJPEG capture palette of USB devices */
	if(cinfo->dc_huff_tbl_ptrs[0] == NULL) {
		zm_use_std_huff_tables(cinfo);
	}

	new_width = cinfo->image_width;
	new_height = cinfo->image_height;

	if ( width != new_width || height != new_height )
	{
		Debug(9,"Image dimensions differ. Old: %ux%u New: %ux%u",width,height,new_width,new_height);
	}
	
	switch(p_colours) {
	  case ZM_COLOUR_GRAY8:
	  {
	    cinfo->out_color_space = JCS_GRAYSCALE;
	    new_colours = ZM_COLOUR_GRAY8;
	    new_subpixelorder = ZM_SUBPIX_ORDER_NONE;
	    break;
	  }
	  case ZM_COLOUR_RGB32:
	  {
#ifdef JCS_EXTENSIONS
	    new_colours = ZM_COLOUR_RGB32;
	    if(p_subpixelorder == ZM_SUBPIX_ORDER_BGRA) {
	      cinfo->out_color_space = JCS_EXT_BGRX;
	      new_subpixelorder = ZM_SUBPIX_ORDER_BGRA;
	    } else if(p_subpixelorder == ZM_SUBPIX_ORDER_ARGB) {
	      cinfo->out_color_space = JCS_EXT_XRGB;
	      new_subpixelorder = ZM_SUBPIX_ORDER_ARGB;
	    } else if(p_subpixelorder == ZM_SUBPIX_ORDER_ABGR) {
	      cinfo->out_color_space = JCS_EXT_XBGR;
	      new_subpixelorder = ZM_SUBPIX_ORDER_ABGR;
	    } else {
	      /* Assume RGBA */
	      cinfo->out_color_space = JCS_EXT_RGBX;
	      new_subpixelorder = ZM_SUBPIX_ORDER_RGBA;
	    }
	    break;      
#else
	    Warning("libjpeg-turbo is required for reading a JPEG directly into a RGB32 buffer, reading into a RGB24 buffer instead.");
#endif
	  }
	  case ZM_COLOUR_RGB24:
	  default:
	  {
	    new_colours = ZM_COLOUR_RGB24;
	    if(p_subpixelorder == ZM_SUBPIX_ORDER_BGR) {
#ifdef JCS_EXTENSIONS      
	      cinfo->out_color_space = JCS_EXT_BGR;    
	      new_subpixelorder = ZM_SUBPIX_ORDER_BGR;
#else
	      Warning("libjpeg-turbo is required for reading a JPEG directly into a BGR24 buffer, reading into a RGB24 buffer instead.");      
	      cinfo->out_color_space = JCS_RGB;    
	      new_subpixelorder = ZM_SUBPIX_ORDER_RGB;
#endif
	    } else {
	      /* Assume RGB */
/*
#ifdef JCS_EXTENSIONS
	      cinfo->out_color_space = JCS_EXT_RGB;
#else
	      cinfo->out_color_space = JCS_RGB;
#endif
*/
	      cinfo->out_color_space = JCS_RGB;
	      new_subpixelorder = ZM_SUBPIX_ORDER_RGB;
	    }
	    break;
	  }
	}
	
	if(WriteBuffer(new_width, new_height, new_colours, new_subpixelorder) == NULL) {
		Error("Failed requesting writeable buffer for reading JPEG image.");
		jpeg_abort_decompress( cinfo );
		fclose( infile );
		return( false );
	}

	jpeg_start_decompress( cinfo );

	JSAMPROW row_pointer;	/* pointer to a single row */
	int row_stride = width * colours; /* physical row width in buffer */
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
	if ( config.colour_jpeg_files && colours == ZM_COLOUR_GRAY8 )
	{
		Image temp_image( *this );
		temp_image.Colourise( ZM_COLOUR_RGB24, ZM_SUBPIX_ORDER_RGB );
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
	
	switch(colours) {
	  case ZM_COLOUR_GRAY8:
	  {
	    cinfo->input_components = 1;
	    cinfo->in_color_space = JCS_GRAYSCALE;
	    break;
	  }
	  case ZM_COLOUR_RGB32:
	  {
#ifdef JCS_EXTENSIONS
	    cinfo->input_components = 4;
	    if(subpixelorder == ZM_SUBPIX_ORDER_BGRA) {
	      cinfo->in_color_space = JCS_EXT_BGRX;
	    } else if(subpixelorder == ZM_SUBPIX_ORDER_ARGB) {
	      cinfo->in_color_space = JCS_EXT_XRGB;
	    } else if(subpixelorder == ZM_SUBPIX_ORDER_ABGR) {
	      cinfo->in_color_space = JCS_EXT_XBGR;
	    } else {
	      /* Assume RGBA */
	      cinfo->in_color_space = JCS_EXT_RGBX;
	    }  
#else
	    Error("libjpeg-turbo is required for JPEG encoding directly from RGB32 source");
	    jpeg_abort_compress( cinfo );
	    fclose(outfile);
	    return(false);
#endif
	    break;
	  }
	  case ZM_COLOUR_RGB24:
	  default:
	  {
	    cinfo->input_components = 3;
	    if(subpixelorder == ZM_SUBPIX_ORDER_BGR) {
#ifdef JCS_EXTENSIONS   
	      cinfo->in_color_space = JCS_EXT_BGR;
#else
	      Error("libjpeg-turbo is required for JPEG encoding directly from BGR24 source");
	      jpeg_abort_compress( cinfo );
	      fclose(outfile);
	      return(false);                                                                                  
#endif
	    } else {
	      /* Assume RGB */
/*
#ifdef JCS_EXTENSIONS
	      cinfo->out_color_space = JCS_EXT_RGB;
#else
	      cinfo->out_color_space = JCS_RGB;
#endif
*/
	      cinfo->in_color_space = JCS_RGB;
	    }
	    break;
	  }
	}
	
	jpeg_set_defaults( cinfo );
	jpeg_set_quality( cinfo, quality, FALSE );
	cinfo->dct_method = JDCT_FASTEST;

	jpeg_start_compress( cinfo, TRUE );
	if ( config.add_jpeg_comments && text[0] )
	{
		jpeg_write_marker( cinfo, JPEG_COM, (const JOCTET *)text, strlen(text) );
	}

	JSAMPROW row_pointer;	/* pointer to a single row */
	int row_stride = cinfo->image_width * colours; /* physical row width in buffer */
	while ( cinfo->next_scanline < cinfo->image_height )
	{
		row_pointer = &buffer[cinfo->next_scanline * row_stride];
		jpeg_write_scanlines( cinfo, &row_pointer, 1 );
	}

	jpeg_finish_compress( cinfo );

	fclose( outfile );

	return( true );
}

bool Image::DecodeJpeg( const JOCTET *inbuffer, int inbuffer_size, unsigned int p_colours, unsigned int p_subpixelorder)
{
	unsigned int new_width, new_height, new_colours, new_subpixelorder;
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

	if ( cinfo->num_components != 1 && cinfo->num_components != 3 )
	{
		Error( "Unexpected colours when reading jpeg image: %d", colours );
		jpeg_abort_decompress( cinfo );
		return( false );
	}
	
	/* Check if the image has at least one huffman table defined. If not, use the standard ones */
	/* This is required for the MJPEG capture palette of USB devices */
	if(cinfo->dc_huff_tbl_ptrs[0] == NULL) {
		zm_use_std_huff_tables(cinfo);
	}

	new_width = cinfo->image_width;
	new_height = cinfo->image_height;

	if ( width != new_width || height != new_height )
	{
		Debug(9,"Image dimensions differ. Old: %ux%u New: %ux%u",width,height,new_width,new_height);
	}
	
	switch(p_colours) {
	  case ZM_COLOUR_GRAY8:
	  {
	    cinfo->out_color_space = JCS_GRAYSCALE;
	    new_colours = ZM_COLOUR_GRAY8;
	    new_subpixelorder = ZM_SUBPIX_ORDER_NONE;
	    break;
	  }
	  case ZM_COLOUR_RGB32:
	  {
#ifdef JCS_EXTENSIONS
	    new_colours = ZM_COLOUR_RGB32;
	    if(p_subpixelorder == ZM_SUBPIX_ORDER_BGRA) {
	      cinfo->out_color_space = JCS_EXT_BGRX;
	      new_subpixelorder = ZM_SUBPIX_ORDER_BGRA;
	    } else if(p_subpixelorder == ZM_SUBPIX_ORDER_ARGB) {
	      cinfo->out_color_space = JCS_EXT_XRGB;
	      new_subpixelorder = ZM_SUBPIX_ORDER_ARGB;
	    } else if(p_subpixelorder == ZM_SUBPIX_ORDER_ABGR) {
	      cinfo->out_color_space = JCS_EXT_XBGR;
	      new_subpixelorder = ZM_SUBPIX_ORDER_ABGR;
	    } else {
	      /* Assume RGBA */
	      cinfo->out_color_space = JCS_EXT_RGBX;
	      new_subpixelorder = ZM_SUBPIX_ORDER_RGBA;
	    }
	    break;      
#else
	    Warning("libjpeg-turbo is required for reading a JPEG directly into a RGB32 buffer, reading into a RGB24 buffer instead.");
#endif
	  }
	  case ZM_COLOUR_RGB24:
	  default:
	  {
	    new_colours = ZM_COLOUR_RGB24;
	    if(p_subpixelorder == ZM_SUBPIX_ORDER_BGR) {
#ifdef JCS_EXTENSIONS      
	      cinfo->out_color_space = JCS_EXT_BGR;    
	      new_subpixelorder = ZM_SUBPIX_ORDER_BGR;
#else
	      Warning("libjpeg-turbo is required for reading a JPEG directly into a BGR24 buffer, reading into a RGB24 buffer instead.");      
	      cinfo->out_color_space = JCS_RGB;    
	      new_subpixelorder = ZM_SUBPIX_ORDER_RGB;
#endif
	    } else {
	      /* Assume RGB */
/*
#ifdef JCS_EXTENSIONS
	      cinfo->out_color_space = JCS_EXT_RGB;
#else
	      cinfo->out_color_space = JCS_RGB;
#endif
*/
	      cinfo->out_color_space = JCS_RGB;
	      new_subpixelorder = ZM_SUBPIX_ORDER_RGB;
	    }
	    break;
	  }
	}
	
	if(WriteBuffer(new_width, new_height, new_colours, new_subpixelorder) == NULL) {
		Error("Failed requesting writeable buffer for reading JPEG image.");
		jpeg_abort_decompress( cinfo );
		return( false );
	}

	jpeg_start_decompress( cinfo );

	JSAMPROW row_pointer;	/* pointer to a single row */
	int row_stride = width * colours; /* physical row width in buffer */
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
	if ( config.colour_jpeg_files && colours == ZM_COLOUR_GRAY8 )
	{
		Image temp_image( *this );
		temp_image.Colourise(ZM_COLOUR_RGB24, ZM_SUBPIX_ORDER_RGB );
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

	switch(colours) {
	  case ZM_COLOUR_GRAY8:
	  {
	    cinfo->input_components = 1;
	    cinfo->in_color_space = JCS_GRAYSCALE;
	    break;
	  }
	  case ZM_COLOUR_RGB32:
	  {
#ifdef JCS_EXTENSIONS
	    cinfo->input_components = 4;
	    if(subpixelorder == ZM_SUBPIX_ORDER_BGRA) {
	      cinfo->in_color_space = JCS_EXT_BGRX;
	    } else if(subpixelorder == ZM_SUBPIX_ORDER_ARGB) {
	      cinfo->in_color_space = JCS_EXT_XRGB;
	    } else if(subpixelorder == ZM_SUBPIX_ORDER_ABGR) {
	      cinfo->in_color_space = JCS_EXT_XBGR;
	    } else {
	      /* Assume RGBA */
	      cinfo->in_color_space = JCS_EXT_RGBX;
	    } 
#else
	    Error("libjpeg-turbo is required for JPEG encoding directly from RGB32 source");
	    jpeg_abort_compress( cinfo );
	    return(false);
#endif
	    break;
	  }
	  case ZM_COLOUR_RGB24:
	  default:
	  {
	    cinfo->input_components = 3;
	    if(subpixelorder == ZM_SUBPIX_ORDER_BGR) {
#ifdef JCS_EXTENSIONS   
	      cinfo->in_color_space = JCS_EXT_BGR;
#else
	      Error("libjpeg-turbo is required for JPEG encoding directly from BGR24 source");
	      jpeg_abort_compress( cinfo );
	      return(false);                                                                                  
#endif
	    } else {
	      /* Assume RGB */
/*
#ifdef JCS_EXTENSIONS
	      cinfo->out_color_space = JCS_EXT_RGB;
#else
	      cinfo->out_color_space = JCS_RGB;
#endif
*/
	      cinfo->in_color_space = JCS_RGB;
	    }
	    break;
	  }
	}
	
	jpeg_set_defaults( cinfo );
	jpeg_set_quality( cinfo, quality, FALSE );
	cinfo->dct_method = JDCT_FASTEST;

	jpeg_start_compress( cinfo, TRUE );

	JSAMPROW row_pointer;	/* pointer to a single row */
	int row_stride = cinfo->image_width * colours; /* physical row width in buffer */
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
	if ( zip_size != (unsigned int)size )
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

bool Image::Crop( unsigned int lo_x, unsigned int lo_y, unsigned int hi_x, unsigned int hi_y )
{
	unsigned int new_width = (hi_x-lo_x)+1;
	unsigned int new_height = (hi_y-lo_y)+1;

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

	unsigned int new_size = new_width*new_height*colours;
	uint8_t *new_buffer = AllocBuffer(new_size);
	
	unsigned int new_stride = new_width*colours;
	for ( unsigned int y = lo_y, ny = 0; y <= hi_y; y++, ny++ )
	{
		unsigned char *pbuf = &buffer[((y*width)+lo_x)*colours];
		unsigned char *pnbuf = &new_buffer[(ny*new_width)*colours];
		memcpy( pnbuf, pbuf, new_stride );
	}

	AssignDirect(new_width, new_height, colours, subpixelorder, new_buffer, new_size, ZM_BUFTYPE_ZM);

	return( true );
}

bool Image::Crop( const Box &limits )
{
    return( Crop( limits.LoX(), limits.LoY(), limits.HiX(), limits.HiY() ) );
}

/* Far from complete */
/* Need to implement all possible of overlays possible */
void Image::Overlay( const Image &image )
{
	if ( !(width == image.width && height == image.height) )
	{
		Panic( "Attempt to overlay different sized images, expected %dx%d, got %dx%d", width, height, image.width, image.height );
	}
	
	if( colours == image.colours && subpixelorder != image.subpixelorder ) {
		Warning("Attempt to overlay images of same format but with different subpixel order.");
	}
	
	/* Grayscale ontop of grayscale - complete */
	if ( colours == ZM_COLOUR_GRAY8 && image.colours == ZM_COLOUR_GRAY8 ) {
		const uint8_t* const max_ptr = buffer+size;
		const uint8_t* psrc = image.buffer;
		uint8_t* pdest = buffer;
		
		while( pdest < max_ptr )
		{
			if ( *psrc )
			{
				*pdest = *psrc;
			}
			pdest++;
			psrc++;
		}
	
	/* RGB24 ontop of grayscale - convert to same format first - complete */
	} else if ( colours == ZM_COLOUR_GRAY8 && image.colours == ZM_COLOUR_RGB24 ) {
		Colourise(image.colours, image.subpixelorder);
		
		const uint8_t* const max_ptr = buffer+size;
		const uint8_t* psrc = image.buffer;
		uint8_t* pdest = buffer;
		
		while( pdest < max_ptr )
		{
			if ( RED_PTR_RGBA(psrc) || GREEN_PTR_RGBA(psrc) || BLUE_PTR_RGBA(psrc) )
			{
				RED_PTR_RGBA(pdest) = RED_PTR_RGBA(psrc);
				GREEN_PTR_RGBA(pdest) = GREEN_PTR_RGBA(psrc);
				BLUE_PTR_RGBA(pdest) = BLUE_PTR_RGBA(psrc);
			}
			pdest += 3;
			psrc += 3;
		}
	
	/* RGB32 ontop of grayscale - convert to same format first - complete */
	} else if( colours == ZM_COLOUR_GRAY8 && image.colours == ZM_COLOUR_RGB32 ) {
		Colourise(image.colours, image.subpixelorder);
		
		const Rgb* const max_ptr = (Rgb*)(buffer+size);
		const Rgb* prsrc = (Rgb*)image.buffer; 
		Rgb* prdest = (Rgb*)buffer;
		
		if(subpixelorder == ZM_SUBPIX_ORDER_RGBA || subpixelorder == ZM_SUBPIX_ORDER_BGRA) {
			/* RGB\BGR\RGBA\BGRA subpixel order - Alpha byte is last */
			while (prdest < max_ptr) {
				if ( RED_PTR_RGBA(prsrc) || GREEN_PTR_RGBA(prsrc) || BLUE_PTR_RGBA(prsrc) )
				{
					*prdest = *prsrc;
				}
				prdest++;
				prsrc++;
			}
		} else {
			/* ABGR\ARGB subpixel order - Alpha byte is first */
			while (prdest < max_ptr) {
				if ( RED_PTR_ABGR(prsrc) || GREEN_PTR_ABGR(prsrc) || BLUE_PTR_ABGR(prsrc) )
				{
					*prdest = *prsrc;
				}
				prdest++;
				prsrc++;
			}
		}
	
	/* Grayscale ontop of RGB24 - complete */
	} else if ( colours == ZM_COLOUR_RGB24 && image.colours == ZM_COLOUR_GRAY8 ) {
		const uint8_t* const max_ptr = buffer+size;
		const uint8_t* psrc = image.buffer;
		uint8_t* pdest = buffer;
		
		while( pdest < max_ptr )
		{
			if ( *psrc )
			{
				RED_PTR_RGBA(pdest) = GREEN_PTR_RGBA(pdest) = BLUE_PTR_RGBA(pdest) = *psrc;
			}
			pdest += 3;
			psrc++;
		}
	
	/* RGB24 ontop of RGB24 - not complete. need to take care of different subpixel orders */
	} else if ( colours == ZM_COLOUR_RGB24 && image.colours == ZM_COLOUR_RGB24 ) {
		const uint8_t* const max_ptr = buffer+size;
		const uint8_t* psrc = image.buffer;
		uint8_t* pdest = buffer;
		
		while( pdest < max_ptr )
		{
			if ( RED_PTR_RGBA(psrc) || GREEN_PTR_RGBA(psrc) || BLUE_PTR_RGBA(psrc) )
			{
				RED_PTR_RGBA(pdest) = RED_PTR_RGBA(psrc);
				GREEN_PTR_RGBA(pdest) = GREEN_PTR_RGBA(psrc);
				BLUE_PTR_RGBA(pdest) = BLUE_PTR_RGBA(psrc);
			}
			pdest += 3;
			psrc += 3;
		} 
	
	/* RGB32 ontop of RGB24 - TO BE DONE */
	} else if ( colours == ZM_COLOUR_RGB24 && image.colours == ZM_COLOUR_RGB32 ) {
		Error("Overlay of RGB32 ontop of RGB24 is not supported.");
	
	/* Grayscale ontop of RGB32 - complete */
	} else if ( colours == ZM_COLOUR_RGB32 && image.colours == ZM_COLOUR_GRAY8 ) {
		const Rgb* const max_ptr = (Rgb*)(buffer+size);
		Rgb* prdest = (Rgb*)buffer;
		const uint8_t* psrc = image.buffer;
		
		if(subpixelorder == ZM_SUBPIX_ORDER_RGBA || subpixelorder == ZM_SUBPIX_ORDER_BGRA) {
			/* RGBA\BGRA subpixel order - Alpha byte is last */
			while (prdest < max_ptr) {
				if ( *psrc )
				{
					RED_PTR_RGBA(prdest) = GREEN_PTR_RGBA(prdest) = BLUE_PTR_RGBA(prdest) = *psrc;
				}
				prdest++;
				psrc++;
			}
		} else {
			/* ABGR\ARGB subpixel order - Alpha byte is first */
			while (prdest < max_ptr) {
				if ( *psrc )
				{
					RED_PTR_ABGR(prdest) = GREEN_PTR_ABGR(prdest) = BLUE_PTR_ABGR(prdest) = *psrc;
				}
				prdest++;
				psrc++;
			}
		}
	
	/* RGB24 ontop of RGB32 - TO BE DONE */
	} else if ( colours == ZM_COLOUR_RGB32 && image.colours == ZM_COLOUR_RGB24 ) {
		Error("Overlay of RGB24 ontop of RGB32 is not supported.");
	
	/* RGB32 ontop of RGB32 - not complete. need to take care of different subpixel orders */
	} else if ( colours == ZM_COLOUR_RGB32 && image.colours == ZM_COLOUR_RGB32 ) {
		const Rgb* const max_ptr = (Rgb*)(buffer+size);
		Rgb* prdest = (Rgb*)buffer;
		const Rgb* prsrc = (Rgb*)image.buffer; 
		
		if(image.subpixelorder == ZM_SUBPIX_ORDER_RGBA || image.subpixelorder == ZM_SUBPIX_ORDER_BGRA) {
			/* RGB\BGR\RGBA\BGRA subpixel order - Alpha byte is last */
			while (prdest < max_ptr) {
				if ( RED_PTR_RGBA(prsrc) || GREEN_PTR_RGBA(prsrc) || BLUE_PTR_RGBA(prsrc) )
				{
					*prdest = *prsrc;
				}
				prdest++;
				prsrc++;
			}
		} else {
			/* ABGR\ARGB subpixel order - Alpha byte is first */
			while (prdest < max_ptr) {
				if ( RED_PTR_ABGR(prsrc) || GREEN_PTR_ABGR(prsrc) || BLUE_PTR_ABGR(prsrc) )
				{
					*prdest = *prsrc;
				}
				prdest++;
				prsrc++;
			}
		}
	}
	
}

/* RGB32 compatible: complete */
void Image::Overlay( const Image &image, unsigned int x, unsigned int y )
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

	unsigned int lo_x = x;
	unsigned int lo_y = y;
	unsigned int hi_x = (x+image.width)-1;
	unsigned int hi_y = (y+image.height-1);
	if ( colours == ZM_COLOUR_GRAY8 )
	{
		const uint8_t *psrc = image.buffer;
		for ( unsigned int y = lo_y; y <= hi_y; y++ )
		{
			uint8_t *pdest = &buffer[(y*width)+lo_x];
			for ( unsigned int x = lo_x; x <= hi_x; x++ )
			{
				*pdest++ = *psrc++;
			}
		}
	}
	else if ( colours == ZM_COLOUR_RGB24 )
	{
		const uint8_t *psrc = image.buffer;
		for ( unsigned int y = lo_y; y <= hi_y; y++ )
		{
			uint8_t *pdest = &buffer[colours*((y*width)+lo_x)];
			for ( unsigned int x = lo_x; x <= hi_x; x++ )
			{
				*pdest++ = *psrc++;
				*pdest++ = *psrc++;
				*pdest++ = *psrc++;
			}
		}
	}
	else if ( colours == ZM_COLOUR_RGB32 )
	{
		const Rgb *psrc = (Rgb*)(image.buffer);
		for ( unsigned int y = lo_y; y <= hi_y; y++ )
		{
			Rgb *pdest = (Rgb*)&buffer[((y*width)+lo_x)<<2];
			for ( unsigned int x = lo_x; x <= hi_x; x++ )
			{
				*pdest++ = *psrc++;
			}
		}
	} else {
		Error("Overlay called with unexpected colours: %d", colours);
	}
	
}

void Image::Blend( const Image &image, int transparency )
{
#ifdef ZM_IMAGE_PROFILING
	struct timespec start,end,diff;
	unsigned long long executetime;
	unsigned long milpixels;
#endif
	uint8_t* new_buffer;
	
	if ( !(width == image.width && height == image.height && colours == image.colours && subpixelorder == image.subpixelorder) )
	{
		Panic( "Attempt to blend different sized images, expected %dx%dx%d %d, got %dx%dx%d %d", width, height, colours, subpixelorder, image.width, image.height, image.colours, image.subpixelorder );
	}
	
	if(transparency <= 0)
		return;
	
	new_buffer = AllocBuffer(size);
	
#ifdef ZM_IMAGE_PROFILING
	clock_gettime(CLOCK_THREAD_CPUTIME_ID,&start);
#endif
	
	/* Do the blending */
	(*fptr_blend)(buffer, image.buffer, new_buffer, size, transparency);
	
#ifdef ZM_IMAGE_PROFILING
	clock_gettime(CLOCK_THREAD_CPUTIME_ID,&end);
	timespec_diff(&start,&end,&diff);
	
	executetime = (1000000000ull * diff.tv_sec) + diff.tv_nsec;
	milpixels = (unsigned long)((long double)size)/((((long double)executetime)/1000));
	Debug(5, "Blend: %u colours blended in %llu nanoseconds, %lu million colours/s\n",size,executetime,milpixels);
#endif
	
	AssignDirect( width, height, colours, subpixelorder, new_buffer, size, ZM_BUFTYPE_ZM);
}

Image *Image::Merge( unsigned int n_images, Image *images[] )
{
	if ( n_images <= 0 ) return( 0 );
	if ( n_images == 1 ) return( new Image( *images[0] ) );

	unsigned int width = images[0]->width;
	unsigned int height = images[0]->height;
	unsigned int colours = images[0]->colours;
	for ( unsigned int i = 1; i < n_images; i++ )
	{
	    if ( !(width == images[i]->width && height == images[i]->height && colours == images[i]->colours) )
        {
            Panic( "Attempt to merge different sized images, expected %dx%dx%d, got %dx%dx%d, for image %d", width, height, colours, images[i]->width, images[i]->height, images[i]->colours, i );
        }
	}

	Image *result = new Image( width, height, images[0]->colours, images[0]->subpixelorder);
	unsigned int size = result->size;
	for ( unsigned int i = 0; i < size; i++ )
	{
		unsigned int total = 0;
		uint8_t *pdest = result->buffer;
		for ( unsigned int j = 0; j < n_images; j++ )
		{
			uint8_t *psrc = images[j]->buffer;
			total += *psrc;
			psrc++;
		}
		*pdest = total/n_images;
		pdest++;
	}
	return( result );
}

Image *Image::Merge( unsigned int n_images, Image *images[], double weight )
{
	if ( n_images <= 0 ) return( 0 );
	if ( n_images == 1 ) return( new Image( *images[0] ) );

	unsigned int width = images[0]->width;
	unsigned int height = images[0]->height;
	unsigned int colours = images[0]->colours;
	for ( unsigned int i = 1; i < n_images; i++ )
	{
	    if ( !(width == images[i]->width && height == images[i]->height && colours == images[i]->colours) )
        {
            Panic( "Attempt to merge different sized images, expected %dx%dx%d, got %dx%dx%d, for image %d", width, height, colours, images[i]->width, images[i]->height, images[i]->colours, i );
        }
	}

	Image *result = new Image( *images[0] );
	unsigned int size = result->size;
	double factor = 1.0*weight;
	for ( unsigned int i = 1; i < n_images; i++ )
	{
		uint8_t *pdest = result->buffer;
		uint8_t *psrc = images[i]->buffer;
		for ( unsigned int j = 0; j < size; j++ )
		{
			*pdest = (uint8_t)(((*pdest)*(1.0-factor))+((*psrc)*factor));
			pdest++;
			psrc++;
		}
		factor *= weight;
	}
	return( result );
}

Image *Image::Highlight( unsigned int n_images, Image *images[], const Rgb threshold, const Rgb ref_colour )
{
	if ( n_images <= 0 ) return( 0 );
	if ( n_images == 1 ) return( new Image( *images[0] ) );

	unsigned int width = images[0]->width;
	unsigned int height = images[0]->height;
	unsigned int colours = images[0]->colours;
	for ( unsigned int i = 1; i < n_images; i++ )
	{
	    if ( !(width == images[i]->width && height == images[i]->height && colours == images[i]->colours) )
        {
            Panic( "Attempt to highlight different sized images, expected %dx%dx%d, got %dx%dx%d, for image %d", width, height, colours, images[i]->width, images[i]->height, images[i]->colours, i );
        }
	}

	Image *result = new Image( width, height, images[0]->colours, images[0]->subpixelorder );
	unsigned int size = result->size;
	for ( unsigned int c = 0; c < colours; c++ )
	{
		for ( unsigned int i = 0; i < size; i++ )
		{
			unsigned int count = 0;
			uint8_t *pdest = result->buffer+c;
			for ( unsigned int j = 0; j < n_images; j++ )
			{
				uint8_t *psrc = images[j]->buffer+c;

				if ( (unsigned)abs((*psrc)-RGB_VAL(ref_colour,c)) >= RGB_VAL(threshold,c) )
				{
					count++;
				}
				psrc += colours;
			}
			*pdest = (count*255)/n_images;
			pdest += 3;
		}
	}
	return( result );
}

/* New function to allow buffer re-using instead of allocationg memory for the delta image everytime */
void Image::Delta( const Image &image, Image* targetimage) const
{
#ifdef ZM_IMAGE_PROFILING
	struct timespec start,end,diff;
	unsigned long long executetime;
	unsigned long milpixels;
#endif
	
	if ( !(width == image.width && height == image.height && colours == image.colours && subpixelorder == image.subpixelorder) )
	{
		Panic( "Attempt to get delta of different sized images, expected %dx%dx%d %d, got %dx%dx%d %d", width, height, colours, subpixelorder, image.width, image.height, image.colours, image.subpixelorder);
	}
	
	uint8_t *pdiff = targetimage->WriteBuffer(width, height, ZM_COLOUR_GRAY8, ZM_SUBPIX_ORDER_NONE);
	
	if(pdiff == NULL) {
		Panic("Failed requesting writeable buffer for storing the delta image");
	}
	
#ifdef ZM_IMAGE_PROFILING
	clock_gettime(CLOCK_THREAD_CPUTIME_ID,&start);
#endif
	
	switch(colours) {
	  case ZM_COLOUR_RGB24:
	  {
	    if(subpixelorder == ZM_SUBPIX_ORDER_BGR) {
	      /* BGR subpixel order */
	      (*fptr_delta8_bgr)(buffer, image.buffer, pdiff, pixels);
	    } else {
	      /* Assume RGB subpixel order */
	      (*fptr_delta8_rgb)(buffer, image.buffer, pdiff, pixels);
	    }
	    break;
	  }
	  case ZM_COLOUR_RGB32:
	  {
	    if(subpixelorder == ZM_SUBPIX_ORDER_ARGB) {
	      /* ARGB subpixel order */
	      (*fptr_delta8_argb)(buffer, image.buffer, pdiff, pixels);
	    } else if(subpixelorder == ZM_SUBPIX_ORDER_ABGR) {
	      /* ABGR subpixel order */
	      (*fptr_delta8_abgr)(buffer, image.buffer, pdiff, pixels);
	    } else if(subpixelorder == ZM_SUBPIX_ORDER_BGRA) {
	      /* BGRA subpixel order */
	      (*fptr_delta8_bgra)(buffer, image.buffer, pdiff, pixels);
	    } else {
	      /* Assume RGBA subpixel order */
	      (*fptr_delta8_rgba)(buffer, image.buffer, pdiff, pixels);
	    }
	    break;
	  }
	  case ZM_COLOUR_GRAY8:
	    (*fptr_delta8_gray8)(buffer, image.buffer, pdiff, pixels);
	    break;
	  default:
	    Panic("Delta called with unexpected colours: %d",colours);
	    break;
	}
	
#ifdef ZM_IMAGE_PROFILING
	clock_gettime(CLOCK_THREAD_CPUTIME_ID,&end);
	timespec_diff(&start,&end,&diff);
	
	executetime = (1000000000ull * diff.tv_sec) + diff.tv_nsec;
	milpixels = (unsigned long)((long double)pixels)/((((long double)executetime)/1000));
	Debug(5, "Delta: %u delta pixels generated in %llu nanoseconds, %lu million pixels/s\n",pixels,executetime,milpixels);
#endif
}

const Coord Image::centreCoord( const char *text ) const
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

/* RGB32 compatible: complete */
void Image::Annotate( const char *p_text, const Coord &coord, const Rgb fg_colour, const Rgb bg_colour )
{
	strncpy( text, p_text, sizeof(text) );

    unsigned int index = 0;
    unsigned int line_no = 0;
    unsigned int text_len = strlen( text );
    unsigned int line_len = 0;
    const char *line = text;

    const uint8_t fg_r_col = RED_VAL_RGBA(fg_colour);
    const uint8_t fg_g_col = GREEN_VAL_RGBA(fg_colour);
    const uint8_t fg_b_col = BLUE_VAL_RGBA(fg_colour);
    const uint8_t fg_bw_col = fg_colour & 0xff;
    const Rgb fg_rgb_col = rgb_convert(fg_colour,subpixelorder);
    const bool fg_trans = (fg_colour == RGB_TRANSPARENT);
    
    const uint8_t bg_r_col = RED_VAL_RGBA(bg_colour);
    const uint8_t bg_g_col = GREEN_VAL_RGBA(bg_colour);
    const uint8_t bg_b_col = BLUE_VAL_RGBA(bg_colour);
    const uint8_t bg_bw_col = bg_colour & 0xff;
    const Rgb bg_rgb_col = rgb_convert(bg_colour,subpixelorder);
    const bool bg_trans = (bg_colour == RGB_TRANSPARENT);

    while ( (index < text_len) && (line_len = strcspn( line, "\n" )) )
    {

        unsigned int line_width = line_len * CHAR_WIDTH;

        unsigned int lo_line_x = coord.X();
        unsigned int lo_line_y = coord.Y() + (line_no * LINE_HEIGHT);

        unsigned int min_line_x = 0;
        unsigned int max_line_x = width - line_width;
        unsigned  int min_line_y = 0;
        unsigned int max_line_y = height - LINE_HEIGHT;

        if ( lo_line_x > max_line_x )
            lo_line_x = max_line_x;
        if ( lo_line_x < min_line_x )
            lo_line_x = min_line_x;
        if ( lo_line_y > max_line_y )
            lo_line_y = max_line_y;
        if ( lo_line_y < min_line_y )
            lo_line_y = min_line_y;

        unsigned int hi_line_x = lo_line_x + line_width;
        unsigned int hi_line_y = lo_line_y + LINE_HEIGHT;

        // Clip anything that runs off the right of the screen
        if ( hi_line_x > width )
            hi_line_x = width;
        if ( hi_line_y > height )
            hi_line_y = height;

        if ( colours == ZM_COLOUR_GRAY8 )
        {
            unsigned char *ptr = &buffer[(lo_line_y*width)+lo_line_x];
            for ( unsigned int y = lo_line_y, r = 0; y < hi_line_y && r < CHAR_HEIGHT; y++, r++, ptr += width )
            {
                unsigned char *temp_ptr = ptr;
                for ( unsigned int x = lo_line_x, c = 0; x < hi_line_x && c < line_len; c++ )
                {
                    int f = fontdata[(line[c] * CHAR_HEIGHT) + r];
                    for ( unsigned int i = 0; i < CHAR_WIDTH && x < hi_line_x; i++, x++, temp_ptr++ )
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
        else if ( colours == ZM_COLOUR_RGB24 )
        {
            unsigned int wc = width * colours;

            unsigned char *ptr = &buffer[((lo_line_y*width)+lo_line_x)*colours];
            for ( unsigned int y = lo_line_y, r = 0; y < hi_line_y && r < CHAR_HEIGHT; y++, r++, ptr += wc )
            {
                unsigned char *temp_ptr = ptr;
                for ( unsigned int x = lo_line_x, c = 0; x < hi_line_x && c < line_len; c++ )
                {
                    int f = fontdata[(line[c] * CHAR_HEIGHT) + r];
                    for ( unsigned int i = 0; i < CHAR_WIDTH && x < hi_line_x; i++, x++, temp_ptr += colours )
                    {
                        if ( f & (0x80 >> i) )
                        {
                            if ( !fg_trans )
                            {
					            RED_PTR_RGBA(temp_ptr) = fg_r_col;
					            GREEN_PTR_RGBA(temp_ptr) = fg_g_col;
					            BLUE_PTR_RGBA(temp_ptr) = fg_b_col;
                            }
                        }
                        else if ( !bg_trans )
                        {
					        RED_PTR_RGBA(temp_ptr) = bg_r_col;
					        GREEN_PTR_RGBA(temp_ptr) = bg_g_col;
					        BLUE_PTR_RGBA(temp_ptr) = bg_b_col;
                        }
                    }
                }
            }
        } 
        else if ( colours == ZM_COLOUR_RGB32 )
	{
            unsigned int wc = width * colours;

            uint8_t *ptr = &buffer[((lo_line_y*width)+lo_line_x)<<2];
            for ( unsigned int y = lo_line_y, r = 0; y < hi_line_y && r < CHAR_HEIGHT; y++, r++, ptr += wc )
            {
                Rgb* temp_ptr = (Rgb*)ptr;
                for ( unsigned int x = lo_line_x, c = 0; x < hi_line_x && c < line_len; c++ )
                {
                    int f = fontdata[(line[c] * CHAR_HEIGHT) + r];
                    for ( unsigned int i = 0; i < CHAR_WIDTH && x < hi_line_x; i++, x++, temp_ptr++ )
                    {
                        if ( f & (0x80 >> i) )
                        {
                            if ( !fg_trans )
                            {
				*temp_ptr = fg_rgb_col;
                            }
                        }
                        else if ( !bg_trans )
                        {
			    *temp_ptr = bg_rgb_col;
                        }
                    }
                }
            } 
	
	} else {
		Panic("Annontate called with unexpected colours: %d",colours);
		return;
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

/* RGB32 compatible: complete */
void Image::Colourise(const unsigned int p_reqcolours, const unsigned int p_reqsubpixelorder)
{
	Debug(9, "Colourise: Req colours: %u Req subpixel order: %u Current colours: %u Current subpixel order: %u",p_reqcolours,p_reqsubpixelorder,colours,subpixelorder);
	
	if ( colours != ZM_COLOUR_GRAY8) {
		Warning("Target image is already colourised, colours: %u",colours);
		return;
	}
	
	if ( p_reqcolours == ZM_COLOUR_RGB32 ) {
		/* RGB32 */
		Rgb* new_buffer = (Rgb*)AllocBuffer(pixels*sizeof(Rgb));
		
		const uint8_t *psrc = buffer;
		Rgb* pdest = new_buffer;
		Rgb subpixel;
		Rgb newpixel;
		
		if ( p_reqsubpixelorder == ZM_SUBPIX_ORDER_ABGR || p_reqsubpixelorder == ZM_SUBPIX_ORDER_ARGB) {
			/* ARGB\ABGR subpixel order. alpha byte is first (mem+0), so we need to shift the pixel left in the end */
			for(unsigned int i=0;i<pixels;i++) {
				newpixel = subpixel = psrc[i];
				newpixel = (newpixel<<8) | subpixel;
				newpixel = (newpixel<<8) | subpixel;
				pdest[i] = (newpixel<<8);
			}		
		} else {
			/* RGBA\BGRA subpixel order, alpha byte is last (mem+3) */
			for(unsigned int i=0;i<pixels;i++) {
				newpixel = subpixel = psrc[i];
				newpixel = (newpixel<<8) | subpixel;
				newpixel = (newpixel<<8) | subpixel;
				pdest[i] = newpixel;
			}
		}

		/* Directly assign the new buffer and make sure it will be freed when not needed anymore */
		AssignDirect( width, height, p_reqcolours, p_reqsubpixelorder, (uint8_t*)new_buffer, pixels*4, ZM_BUFTYPE_ZM);

	} else if(p_reqcolours == ZM_COLOUR_RGB24 ) {
		/* RGB24 */
		uint8_t *new_buffer = AllocBuffer(pixels*3);
		
		uint8_t *pdest = new_buffer;
		const uint8_t *psrc = buffer;
		
		for(unsigned int i=0;i<(unsigned int)pixels;i++, pdest += 3)
		{
			RED_PTR_RGBA(pdest) = GREEN_PTR_RGBA(pdest) = BLUE_PTR_RGBA(pdest) = psrc[i];
		}
		
		/* Directly assign the new buffer and make sure it will be freed when not needed anymore */
		AssignDirect( width, height, p_reqcolours, p_reqsubpixelorder, new_buffer, pixels*3, ZM_BUFTYPE_ZM);
	} else {
		Error("Colourise called with unexpected colours: %d",colours);
		return;
	}
	
}

/* RGB32 compatible: complete */
void Image::DeColourise()
{
	colours = ZM_COLOUR_GRAY8;
	subpixelorder = ZM_SUBPIX_ORDER_NONE;
	size = width * height;
	
	if ( colours == ZM_COLOUR_RGB32 )
	{
		switch(subpixelorder) {
		  case ZM_SUBPIX_ORDER_BGRA:
		    std_convert_bgra_gray8(buffer,buffer,pixels);
		    break;
		  case ZM_SUBPIX_ORDER_ARGB:
		    std_convert_argb_gray8(buffer,buffer,pixels);
		    break;
		  case ZM_SUBPIX_ORDER_ABGR:
		    std_convert_abgr_gray8(buffer,buffer,pixels);
		    break;
		  case ZM_SUBPIX_ORDER_RGBA:
		  default:
		    std_convert_rgba_gray8(buffer,buffer,pixels);
		    break;
		}
	} else {
		/* Assume RGB24 */
		switch(subpixelorder) {
		  case ZM_SUBPIX_ORDER_BGR:
		    std_convert_bgr_gray8(buffer,buffer,pixels);
		    break;
		  case ZM_SUBPIX_ORDER_RGB:
		  default:
		    std_convert_rgb_gray8(buffer,buffer,pixels);
		    break;
		}
		
	}
}

/* RGB32 compatible: complete */
void Image::Fill( Rgb colour, const Box *limits )
{
	if ( !(colours == ZM_COLOUR_GRAY8 || colours == ZM_COLOUR_RGB24 || colours == ZM_COLOUR_RGB32 ) )
	{
		Panic( "Attempt to fill image with unexpected colours %d", colours );
	}
	
	/* Convert the colour's RGBA subpixel order into the image's subpixel order */
	colour = rgb_convert(colour,subpixelorder);
	
	unsigned int lo_x = limits?limits->Lo().X():0;
	unsigned int lo_y = limits?limits->Lo().Y():0;
	unsigned int hi_x = limits?limits->Hi().X():width-1;
	unsigned int hi_y = limits?limits->Hi().Y():height-1;
	if ( colours == ZM_COLOUR_GRAY8 )
	{
		for ( unsigned int y = lo_y; y <= hi_y; y++ )
		{
			unsigned char *p = &buffer[(y*width)+lo_x];
			for ( unsigned int x = lo_x; x <= hi_x; x++, p++)
			{
				*p = colour;
			}
		}
	}
	else if ( colours == ZM_COLOUR_RGB24 )
	{
		for ( unsigned int y = lo_y; y <= hi_y; y++ )
		{
			unsigned char *p = &buffer[colours*((y*width)+lo_x)];
			for ( unsigned int x = lo_x; x <= hi_x; x++, p += 3)
			{
				RED_PTR_RGBA(p) = RED_VAL_RGBA(colour);
				GREEN_PTR_RGBA(p) = GREEN_VAL_RGBA(colour);
				BLUE_PTR_RGBA(p) = BLUE_VAL_RGBA(colour);
			}
		}
	}
	else if ( colours == ZM_COLOUR_RGB32 ) /* RGB32 */
	{
		for ( unsigned int y = lo_y; y <= (unsigned int)hi_y; y++ )
		{
			Rgb *p = (Rgb*)&buffer[((y*width)+lo_x)<<2];
			
			for ( unsigned int x = lo_x; x <= (unsigned int)hi_x; x++, p++)
			{
				/* Fast, copies the entire pixel in a single pass */ 
				*p = colour;
			}
		}
	}
}

/* RGB32 compatible: complete */
void Image::Fill( Rgb colour, int density, const Box *limits )
{
	/* Allow the faster version to be used if density is not used (density=1) */
	if(density <= 1)
		return Fill(colour,limits);
	
	if ( !(colours == ZM_COLOUR_GRAY8 || colours == ZM_COLOUR_RGB24 || colours == ZM_COLOUR_RGB32  ) )
	{
		Panic( "Attempt to fill image with unexpected colours %d", colours );
	}
	
	/* Convert the colour's RGBA subpixel order into the image's subpixel order */
	colour = rgb_convert(colour,subpixelorder);

	unsigned int lo_x = limits?limits->Lo().X():0;
	unsigned int lo_y = limits?limits->Lo().Y():0;
	unsigned int hi_x = limits?limits->Hi().X():width-1;
	unsigned int hi_y = limits?limits->Hi().Y():height-1;
	if ( colours == ZM_COLOUR_GRAY8 )
	{
		for ( unsigned int y = lo_y; y <= hi_y; y++ )
		{
			unsigned char *p = &buffer[(y*width)+lo_x];
			for ( unsigned int x = lo_x; x <= hi_x; x++, p++)
			{
				if ( ( x == lo_x || x == hi_x || y == lo_y || y == hi_y ) || (!(x%density) && !(y%density) ) )
					*p = colour;
			}
		}
	}
	else if ( colours == ZM_COLOUR_RGB24 )
	{
		for ( unsigned int y = lo_y; y <= hi_y; y++ )
		{
			unsigned char *p = &buffer[colours*((y*width)+lo_x)];
			for ( unsigned int x = lo_x; x <= hi_x; x++, p += 3)
			{
				if ( ( x == lo_x || x == hi_x || y == lo_y || y == hi_y ) || (!(x%density) && !(y%density) ) ) {
					RED_PTR_RGBA(p) = RED_VAL_RGBA(colour);
					GREEN_PTR_RGBA(p) = GREEN_VAL_RGBA(colour);
					BLUE_PTR_RGBA(p) = BLUE_VAL_RGBA(colour);
				}
			}
		}
	}
	else if ( colours == ZM_COLOUR_RGB32 ) /* RGB32 */
	{
		for ( unsigned int y = lo_y; y <= hi_y; y++ )
		{
			Rgb* p = (Rgb*)&buffer[((y*width)+lo_x)<<2];

			for ( unsigned int x = lo_x; x <= hi_x; x++, p++)
			{
				if ( ( x == lo_x || x == hi_x || y == lo_y || y == hi_y ) || (!(x%density) && !(y%density) ) )
					/* Fast, copies the entire pixel in a single pass */
					*p = colour;
			}
		}
	}	
	
}

/* RGB32 compatible: complete */
void Image::Outline( Rgb colour, const Polygon &polygon )
{
	if ( !(colours == ZM_COLOUR_GRAY8 || colours == ZM_COLOUR_RGB24 || colours == ZM_COLOUR_RGB32 ) )
	{
		Panic( "Attempt to outline image with unexpected colours %d", colours );
	}
    
	/* Convert the colour's RGBA subpixel order into the image's subpixel order */
	colour = rgb_convert(colour,subpixelorder);
	
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

		//Debug( 9, "dx: %.2lf, dy: %.2lf", dx, dy );
		if ( fabs(dx) <= fabs(dy) )
		{
			//Debug( 9, "dx <= dy" );
			if ( y1 != y2 )
				grad = dx/dy;
			else
				grad = width;

			double x;
			int y, yinc = (y1<y2)?1:-1;
			grad *= yinc;
			if ( colours == ZM_COLOUR_GRAY8 )
			{
				//Debug( 9, "x1:%d, x2:%d, y1:%d, y2:%d, gr:%.2f", x1, x2, y1, y2, grad );
				for ( x = x1, y = y1; y != y2; y += yinc, x += grad )
				{
					//Debug( 9, "x:%.2f, y:%d", x, y );
					buffer[(y*width)+int(round(x))] = colour;
				}
			}
			else if ( colours == ZM_COLOUR_RGB24 )
			{
				for ( x = x1, y = y1; y != y2; y += yinc, x += grad )
				{
					unsigned char *p = &buffer[colours*((y*width)+int(round(x)))];
					RED_PTR_RGBA(p) = RED_VAL_RGBA(colour);
					GREEN_PTR_RGBA(p) = GREEN_VAL_RGBA(colour);
					BLUE_PTR_RGBA(p) = BLUE_VAL_RGBA(colour);
				}
			}
			else if ( colours == ZM_COLOUR_RGB32 )
			{
				for ( x = x1, y = y1; y != y2; y += yinc, x += grad )
				{
					*(Rgb*)(buffer+(((y*width)+int(round(x)))<<2)) = colour;
				}
			}
		}
		else
		{
			//Debug( 9, "dx > dy" );
			if ( x1 != x2 )
				grad = dy/dx;
			else
				grad = height;
			//Debug( 9, "grad: %.2lf", grad );

			double y;
			int x, xinc = (x1<x2)?1:-1;
			grad *= xinc;
			if ( colours == ZM_COLOUR_GRAY8 )
			{
				//Debug( 9, "x1:%d, x2:%d, y1:%d, y2:%d, gr:%.2lf", x1, x2, y1, y2, grad );
				for ( y = y1, x = x1; x != x2; x += xinc, y += grad )
				{
					//Debug( 9, "x:%d, y:%.2f", x, y );
					buffer[(int(round(y))*width)+x] = colour;
				}
			}
			else if ( colours == ZM_COLOUR_RGB24 )
			{
				for ( y = y1, x = x1; x != x2; x += xinc, y += grad )
				{
					unsigned char *p = &buffer[colours*((int(round(y))*width)+x)];
					RED_PTR_RGBA(p) = RED_VAL_RGBA(colour);
					GREEN_PTR_RGBA(p) = GREEN_VAL_RGBA(colour);
					BLUE_PTR_RGBA(p) = BLUE_VAL_RGBA(colour);
				}
			}
			else if ( colours == ZM_COLOUR_RGB32 )
			{
				for ( y = y1, x = x1; x != x2; x += xinc, y += grad )
				{
					*(Rgb*)(buffer+(((int(round(y))*width)+x)<<2)) = colour;
				}
			}
			
		}
	}
}

/* RGB32 compatible: complete */
void Image::Fill( Rgb colour, int density, const Polygon &polygon )
{
	if ( !(colours == ZM_COLOUR_GRAY8 || colours == ZM_COLOUR_RGB24 || colours == ZM_COLOUR_RGB32 ) )
	{
		Panic( "Attempt to fill image with unexpected colours %d", colours );
	}
	
	/* Convert the colour's RGBA subpixel order into the image's subpixel order */
	colour = rgb_convert(colour,subpixelorder);

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

		//Debug( 9, "x1:%d,y1:%d x2:%d,y2:%d", x1, y1, x2, y2 );
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
	if ( logLevel() >= Logger::DEBUG9 )
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
	    if ( logLevel() >= Logger::DEBUG9 )
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
				if( colours == ZM_COLOUR_GRAY8 ) {
					unsigned char *p = &buffer[(y*width)+lo_x];
					for ( int x = lo_x; x <= hi_x; x++, p++)
					{
						if ( !(x%density) )
						{
							//Debug( 9, " %d", x );
							*p = colour;
						}
					}
				} else if( colours == ZM_COLOUR_RGB24 ) {
					unsigned char *p = &buffer[colours*((y*width)+lo_x)];
					for ( int x = lo_x; x <= hi_x; x++, p += 3)
					{
						if ( !(x%density) )
						{  
							RED_PTR_RGBA(p) = RED_VAL_RGBA(colour);
							GREEN_PTR_RGBA(p) = GREEN_VAL_RGBA(colour);
							BLUE_PTR_RGBA(p) = BLUE_VAL_RGBA(colour);
						}
					}
				} else if( colours == ZM_COLOUR_RGB32 ) {
					Rgb *p = (Rgb*)&buffer[((y*width)+lo_x)<<2];
					for ( int x = lo_x; x <= hi_x; x++, p++)
					{
						if ( !(x%density) )
						{
							/* Fast, copies the entire pixel in a single pass */
							*p = colour;
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

/* RGB32 compatible: complete */
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
	
	unsigned int new_height = height;
	unsigned int new_width = width;
	uint8_t* rotate_buffer = AllocBuffer(size);

	switch( angle )
	{
		case 90 :
		{
			new_height = width;
			new_width = height;

			unsigned int line_bytes = new_width*colours;
			unsigned char *s_ptr = buffer;

			if ( colours == ZM_COLOUR_GRAY8 )
			{
				unsigned char *d_ptr;
				for ( unsigned int i = new_width; i > 0; i-- )
				{
					d_ptr = rotate_buffer+(i-1);
					for ( unsigned int j = new_height; j > 0; j-- )
					{
						*d_ptr = *s_ptr++;
						d_ptr += line_bytes;
					}
				}
			}
			else if ( colours == ZM_COLOUR_RGB32 )
			{
				Rgb* s_rptr = (Rgb*)s_ptr;
				Rgb* d_rptr;
				for ( unsigned int i = new_width; i > 0; i-- )
				{
					d_rptr = (Rgb*)(rotate_buffer+((i-1)<<2));
					for ( unsigned int j = new_height; j > 0; j-- )
					{
						*d_rptr = *s_rptr++;
						d_rptr += new_width;
					}
				}
			}
			else /* Assume RGB24 */
			{
				unsigned char *d_ptr;
				for ( unsigned int i = new_width; i > 0; i-- )
				{
					d_ptr = rotate_buffer+((i-1)*3);
					for ( unsigned int j = new_height; j > 0; j-- )
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

			if ( colours == ZM_COLOUR_GRAY8 )
			{
				while( s_ptr > buffer )
				{
					s_ptr--;
					*d_ptr++ = *s_ptr;
				}
			}
			else if ( colours == ZM_COLOUR_RGB32 )
			{
				Rgb* s_rptr = (Rgb*)s_ptr;
				Rgb* d_rptr = (Rgb*)d_ptr;
				while( s_rptr > (Rgb*)buffer )
				{
					s_rptr--;
					*d_rptr++ = *s_rptr;
				}
			}
			else /* Assume RGB24 */
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
			new_height = width;
			new_width = height;

			unsigned int line_bytes = new_width*colours;
			unsigned char *s_ptr = buffer+size;

			if ( colours == ZM_COLOUR_GRAY8 )
			{
				unsigned char *d_ptr;
				for ( unsigned int i = new_width; i > 0; i-- )
				{
					d_ptr = rotate_buffer+(i-1);
					for ( unsigned int j = new_height; j > 0; j-- )
					{
						s_ptr--;
						*d_ptr = *s_ptr;
						d_ptr += line_bytes;
					}
				}
			}
			else if ( colours == ZM_COLOUR_RGB32 )
			{
				Rgb* s_rptr = (Rgb*)s_ptr;
				Rgb* d_rptr;
				for ( unsigned int i = new_width; i > 0; i-- )
				{
					d_rptr = (Rgb*)(rotate_buffer+((i-1)<<2));
					for ( unsigned int j = new_height; j > 0; j-- )
					{
						s_rptr--;
						*d_rptr = *s_rptr;
						d_rptr += new_width;
					}
				}
			}
			else /* Assume RGB24 */
			{
				unsigned char *d_ptr;
				for ( unsigned int i = new_width; i > 0; i-- )
				{
					d_ptr = rotate_buffer+((i-1)*3);
					for ( unsigned int j = new_height; j > 0; j-- )
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
 
	AssignDirect( new_width, new_height, colours, subpixelorder, rotate_buffer, size, ZM_BUFTYPE_ZM);
	
}

/* RGB32 compatible: complete */
void Image::Flip( bool leftright )
{
	uint8_t* flip_buffer = AllocBuffer(size);
	
	unsigned int line_bytes = width*colours;
	unsigned int line_bytes2 = 2*line_bytes;
	if ( leftright )
	{
		// Horizontal flip, left to right
		unsigned char *s_ptr = buffer+line_bytes;
		unsigned char *d_ptr = flip_buffer;
		unsigned char *max_d_ptr = flip_buffer + size;

		if ( colours == ZM_COLOUR_GRAY8 )
		{
			while( d_ptr < max_d_ptr )
			{
				for ( unsigned int j = 0; j < width; j++ )
				{
					s_ptr--;
					*d_ptr++ = *s_ptr;
				}
				s_ptr += line_bytes2;
			}
		}
		else if ( colours == ZM_COLOUR_RGB32 )
		{
			Rgb* s_rptr = (Rgb*)s_ptr;
			Rgb* d_rptr = (Rgb*)flip_buffer;
			Rgb* max_d_rptr = (Rgb*)max_d_ptr;
			while( d_rptr < max_d_rptr )
			{
				for ( unsigned int j = 0; j < width; j++ )
				{
					s_rptr--;
					*d_rptr++ = *s_rptr;
				}
				s_rptr += width * 2;
			}
		}
		else /* Assume RGB24 */
		{
			while( d_ptr < max_d_ptr )
			{
				for ( unsigned int j = 0; j < width; j++ )
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
	
	AssignDirect( width, height, colours, subpixelorder, flip_buffer, size, ZM_BUFTYPE_ZM);
	
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

	unsigned int new_width = (width*factor)/ZM_SCALE_BASE;
	unsigned int new_height = (height*factor)/ZM_SCALE_BASE;
	
	size_t scale_buffer_size = new_width * new_height * colours;
	
	uint8_t* scale_buffer = AllocBuffer(scale_buffer_size);
	
	if ( factor > ZM_SCALE_BASE )
	{
		unsigned char *pd = scale_buffer;
		unsigned int wc = width*colours;
		unsigned int nwc = new_width*colours;
		unsigned int h_count = ZM_SCALE_BASE/2;
		unsigned int last_h_index = 0;
		unsigned int last_w_index = 0;
		unsigned int h_index;
		for ( unsigned int y = 0; y < height; y++ )
		{
			unsigned char *ps = &buffer[y*wc];
			unsigned int w_count = ZM_SCALE_BASE/2;
			unsigned int w_index;
			last_w_index = 0;
			for ( unsigned int x = 0; x < width; x++ )
			{
				w_count += factor;
				w_index = w_count/ZM_SCALE_BASE;
				for (unsigned int f = last_w_index; f < w_index; f++ )
				{
					for ( unsigned int c = 0; c < colours; c++ )
					{
						*pd++ = *(ps+c);
					}
				}
				ps += colours;
				last_w_index = w_index;
			}
			h_count += factor;
			h_index = h_count/ZM_SCALE_BASE;
			for ( unsigned int f = last_h_index+1; f < h_index; f++ )
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
		for ( unsigned int y = 0; y < (unsigned int)height; y++ )
		{
			h_count += factor;
			h_index = h_count/ZM_SCALE_BASE;
			if ( h_index > last_h_index )
			{
				unsigned int w_count = xstart;
				unsigned int w_index;
				last_w_index = 0;

				unsigned char *ps = &buffer[y*wc];
				for ( unsigned int x = 0; x < (unsigned int)width; x++ )
				{
					w_count += factor;
					w_index = w_count/ZM_SCALE_BASE;
					
					if ( w_index > last_w_index )
					{
						for ( unsigned int c = 0; c < colours; c++ )
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
	
	AssignDirect( new_width, new_height, colours, subpixelorder, scale_buffer, scale_buffer_size, ZM_BUFTYPE_ZM);
	
}

void Image::Deinterlace_Discard()
{
	/* Simple deinterlacing. Copy the even lines into the odd lines */
	
	if ( colours == ZM_COLOUR_GRAY8 )
	{
		const uint8_t *psrc;
		uint8_t *pdest;
		for (unsigned int y = 0; y < (unsigned int)height; y += 2)
		{
			psrc = buffer + (y * width);
			pdest = buffer + ((y+1) * width);
			for (unsigned int x = 0; x < (unsigned int)width; x++) {
				*pdest++ = *psrc++;
			}
		}
	}
	else if ( colours == ZM_COLOUR_RGB24 )
	{
		const uint8_t *psrc;
		uint8_t *pdest;
		for (unsigned int y = 0; y < (unsigned int)height; y += 2)
		{
			psrc = buffer + ((y * width) * 3);
			pdest = buffer + (((y+1) * width) * 3);
			for (unsigned int x = 0; x < (unsigned int)width; x++) {
				*pdest++ = *psrc++;
				*pdest++ = *psrc++;
				*pdest++ = *psrc++;
			}
		}
	}
	else if ( colours == ZM_COLOUR_RGB32 )
	{
		const Rgb *psrc;
		Rgb *pdest;
		for (unsigned int y = 0; y < (unsigned int)height; y += 2)
		{
			psrc = (Rgb*)(buffer + ((y * width) << 2));
			pdest = (Rgb*)(buffer + (((y+1) * width) << 2));
			for (unsigned int x = 0; x < (unsigned int)width; x++) {
				*pdest++ = *psrc++;
			}
		}
	} else {
		Error("Deinterlace called with unexpected colours: %d", colours);
	}
	
}

void Image::Deinterlace_Linear()
{
	/* Simple deinterlacing. The odd lines are average of the line above and line below */
	
	const uint8_t *pbelow, *pabove;
	uint8_t *pcurrent;
	
	if ( colours == ZM_COLOUR_GRAY8 )
	{
		for (unsigned int y = 1; y < (unsigned int)(height-1); y += 2)
		{
			pabove = buffer + ((y-1) * width);
			pbelow = buffer + ((y+1) * width);
			pcurrent = buffer + (y * width);
			for (unsigned int x = 0; x < (unsigned int)width; x++) {
				*pcurrent++ = (*pabove++ + *pbelow++) >> 1;
			}
		}
		/* Special case for the last line */
		pcurrent = buffer + ((height-1) * width);
		pabove = buffer + ((height-2) * width);
		for (unsigned int x = 0; x < (unsigned int)width; x++) {
			*pcurrent++ = *pabove++;
		}
	}
	else if ( colours == ZM_COLOUR_RGB24 )
	{
		for (unsigned int y = 1; y < (unsigned int)(height-1); y += 2)
		{
			pabove = buffer + (((y-1) * width) * 3);
			pbelow = buffer + (((y+1) * width) * 3);
			pcurrent = buffer + ((y * width) * 3);
			for (unsigned int x = 0; x < (unsigned int)width; x++) {
				*pcurrent++ = (*pabove++ + *pbelow++) >> 1;
				*pcurrent++ = (*pabove++ + *pbelow++) >> 1;
				*pcurrent++ = (*pabove++ + *pbelow++) >> 1;
			}
		}
		/* Special case for the last line */
		pcurrent = buffer + (((height-1) * width) * 3);
		pabove = buffer + (((height-2) * width) * 3);
		for (unsigned int x = 0; x < (unsigned int)width; x++) {
			*pcurrent++ = *pabove++;
			*pcurrent++ = *pabove++;
			*pcurrent++ = *pabove++;
		}
	}
	else if ( colours == ZM_COLOUR_RGB32 )
	{
		for (unsigned int y = 1; y < (unsigned int)(height-1); y += 2)
		{
			pabove = buffer + (((y-1) * width) << 2);
			pbelow = buffer + (((y+1) * width) << 2);
			pcurrent = buffer + ((y * width) << 2);
			for (unsigned int x = 0; x < (unsigned int)width; x++) {
				*pcurrent++ = (*pabove++ + *pbelow++) >> 1;
				*pcurrent++ = (*pabove++ + *pbelow++) >> 1;
				*pcurrent++ = (*pabove++ + *pbelow++) >> 1;
				*pcurrent++ = (*pabove++ + *pbelow++) >> 1;
			}
		}
		/* Special case for the last line */
		pcurrent = buffer + (((height-1) * width) << 2);
		pabove = buffer + (((height-2) * width) << 2);
		for (unsigned int x = 0; x < (unsigned int)width; x++) {
			*pcurrent++ = *pabove++;
			*pcurrent++ = *pabove++;
			*pcurrent++ = *pabove++;  
			*pcurrent++ = *pabove++;
		}
	} else {
		Error("Deinterlace called with unexpected colours: %d", colours);
	}
	
}

void Image::Deinterlace_Blend()
{
	/* Simple deinterlacing. Blend the fields together. 50% blend */
	
	uint8_t *pabove, *pcurrent;
	
	if ( colours == ZM_COLOUR_GRAY8 )
	{
		for (unsigned int y = 1; y < (unsigned int)height; y += 2)
		{
			pabove = buffer + ((y-1) * width);
			pcurrent = buffer + (y * width);
			for (unsigned int x = 0; x < (unsigned int)width; x++) {
				*pabove = (*pabove + *pcurrent) >> 1;
				*pcurrent++ = *pabove++;
			}
		}
	}
	else if ( colours == ZM_COLOUR_RGB24 )
	{
		for (unsigned int y = 1; y < (unsigned int)height; y += 2)
		{
			pabove = buffer + (((y-1) * width) * 3);
			pcurrent = buffer + ((y * width) * 3);
			for (unsigned int x = 0; x < (unsigned int)width; x++) {
				*pabove = (*pabove + *pcurrent) >> 1;
				*pcurrent++ = *pabove++;
				*pabove = (*pabove + *pcurrent) >> 1;
				*pcurrent++ = *pabove++;
				*pabove = (*pabove + *pcurrent) >> 1;
				*pcurrent++ = *pabove++;
			}
		}
	}
	else if ( colours == ZM_COLOUR_RGB32 )
	{
		for (unsigned int y = 1; y < (unsigned int)height; y += 2)
		{
			pabove = buffer + (((y-1) * width) << 2);
			pcurrent = buffer + ((y * width) << 2);
			for (unsigned int x = 0; x < (unsigned int)width; x++) {
				*pabove = (*pabove + *pcurrent) >> 1;
				*pcurrent++ = *pabove++;
				*pabove = (*pabove + *pcurrent) >> 1;
				*pcurrent++ = *pabove++;
				*pabove = (*pabove + *pcurrent) >> 1;
				*pcurrent++ = *pabove++;
				*pabove = (*pabove + *pcurrent) >> 1;
				*pcurrent++ = *pabove++;
			}
		}
	} else {
		Error("Deinterlace called with unexpected colours: %d", colours);
	}
	
}

void Image::Deinterlace_Blend_CustomRatio(int divider)
{
	/* Simple deinterlacing. Blend the fields together at a custom ratio. */
	/* 1 = 50% blending   */
	/* 2 = 25% blending   */
	/* 3 = 12.% blending  */
	/* 4 = 6.25% blending */
	
	uint8_t *pabove, *pcurrent;
	uint8_t subpix1, subpix2;
	
	if ( divider < 1 || divider > 4 ) {
		Error("Deinterlace called with invalid blend ratio");
	}
	
	if ( colours == ZM_COLOUR_GRAY8 )
	{
		for (unsigned int y = 1; y < (unsigned int)height; y += 2)
		{
			pabove = buffer + ((y-1) * width);
			pcurrent = buffer + (y * width);
			for (unsigned int x = 0; x < (unsigned int)width; x++) {
				subpix1 = ((*pabove - *pcurrent)>>divider) + *pcurrent;
				subpix2 = ((*pcurrent - *pabove)>>divider) + *pabove;
				*pcurrent++ = subpix1;
				*pabove++ = subpix2;
			}
		}
	}
	else if ( colours == ZM_COLOUR_RGB24 )
	{
		for (unsigned int y = 1; y < (unsigned int)height; y += 2)
		{
			pabove = buffer + (((y-1) * width) * 3);
			pcurrent = buffer + ((y * width) * 3);
			for (unsigned int x = 0; x < (unsigned int)width; x++) {
				subpix1 = ((*pabove - *pcurrent)>>divider) + *pcurrent;
				subpix2 = ((*pcurrent - *pabove)>>divider) + *pabove;
				*pcurrent++ = subpix1;
				*pabove++ = subpix2;
				subpix1 = ((*pabove - *pcurrent)>>divider) + *pcurrent;
				subpix2 = ((*pcurrent - *pabove)>>divider) + *pabove;
				*pcurrent++ = subpix1;
				*pabove++ = subpix2;
				subpix1 = ((*pabove - *pcurrent)>>divider) + *pcurrent;
				subpix2 = ((*pcurrent - *pabove)>>divider) + *pabove;
				*pcurrent++ = subpix1;
				*pabove++ = subpix2;
			}
		}
	}
	else if ( colours == ZM_COLOUR_RGB32 )
	{
		for (unsigned int y = 1; y < (unsigned int)height; y += 2)
		{
			pabove = buffer + (((y-1) * width) << 2);
			pcurrent = buffer + ((y * width) << 2);
			for (unsigned int x = 0; x < (unsigned int)width; x++) {
				subpix1 = ((*pabove - *pcurrent)>>divider) + *pcurrent;
				subpix2 = ((*pcurrent - *pabove)>>divider) + *pabove;
				*pcurrent++ = subpix1;
				*pabove++ = subpix2;
				subpix1 = ((*pabove - *pcurrent)>>divider) + *pcurrent;
				subpix2 = ((*pcurrent - *pabove)>>divider) + *pabove;
				*pcurrent++ = subpix1;
				*pabove++ = subpix2;
				subpix1 = ((*pabove - *pcurrent)>>divider) + *pcurrent;
				subpix2 = ((*pcurrent - *pabove)>>divider) + *pabove;
				*pcurrent++ = subpix1;
				*pabove++ = subpix2;
				subpix1 = ((*pabove - *pcurrent)>>divider) + *pcurrent;
				subpix2 = ((*pcurrent - *pabove)>>divider) + *pabove;
				*pcurrent++ = subpix1;
				*pabove++ = subpix2;
			}
		}
	} else {
		Error("Deinterlace called with unexpected colours: %d", colours);
	}
	
}


void Image::Deinterlace_4Field(const Image* next_image, unsigned int threshold)
{
	if ( !(width == next_image->width && height == next_image->height && colours == next_image->colours && subpixelorder == next_image->subpixelorder) )
	{
		Panic( "Attempt to deinterlace different sized images, expected %dx%dx%d %d, got %dx%dx%d %d", width, height, colours, subpixelorder, next_image->width, next_image->height, next_image->colours, next_image->subpixelorder);
	}
	
	switch(colours) {
	  case ZM_COLOUR_RGB24:
	  {
	    if(subpixelorder == ZM_SUBPIX_ORDER_BGR) {
	      /* BGR subpixel order */
	      std_deinterlace_4field_bgr(buffer, next_image->buffer, threshold, width, height);
	    } else {
	      /* Assume RGB subpixel order */
	      std_deinterlace_4field_rgb(buffer, next_image->buffer, threshold, width, height);
	    }
	    break;
	  }
	  case ZM_COLOUR_RGB32:
	  {
	    if(subpixelorder == ZM_SUBPIX_ORDER_ARGB) {
	      /* ARGB subpixel order */
	      (*fptr_deinterlace_4field_argb)(buffer, next_image->buffer, threshold, width, height);
	    } else if(subpixelorder == ZM_SUBPIX_ORDER_ABGR) {
	      /* ABGR subpixel order */
	      (*fptr_deinterlace_4field_abgr)(buffer, next_image->buffer, threshold, width, height);
	    } else if(subpixelorder == ZM_SUBPIX_ORDER_BGRA) {
	      /* BGRA subpixel order */
	      (*fptr_deinterlace_4field_bgra)(buffer, next_image->buffer, threshold, width, height);
	    } else {
	      /* Assume RGBA subpixel order */
	      (*fptr_deinterlace_4field_rgba)(buffer, next_image->buffer, threshold, width, height);
	    }
	    break;
	  }
	  case ZM_COLOUR_GRAY8:
	    (*fptr_deinterlace_4field_gray8)(buffer, next_image->buffer, threshold, width, height);
	    break;
	  default:
	    Panic("Deinterlace_4Field called with unexpected colours: %d",colours);
	    break;
	}
	
}


/************************************************* BLEND FUNCTIONS *************************************************/


#if defined(__i386__) || defined(__x86_64__)
__attribute__((noinline,__target__("sse2")))
#endif
void sse2_fastblend(const uint8_t* col1, const uint8_t* col2, uint8_t* result, unsigned long count, double blendpercent) {
#if ((defined(__i386__) || defined(__x86_64__) || defined(ZM_KEEP_SSE)) && !defined(ZM_STRIP_SSE))  
	static uint32_t divider = 0;
	static uint32_t clearmask = 0;
	static double current_blendpercent = 0.0;
	
	if(current_blendpercent != blendpercent) {
		/* Attempt to match the blending percent to one of the possible values */
		if(blendpercent < 2.34375) {
			// 1.5625% blending
			divider = 6;
			clearmask = 0x03030303;
		} else if(blendpercent < 4.6875) {
			// 3.125% blending
			divider = 5;
			clearmask = 0x07070707;
		} else if(blendpercent < 9.375) {
			// 6.25% blending
			divider = 4;
			clearmask = 0x0F0F0F0F;
		} else if(blendpercent < 18.75) {
			// 12.5% blending
			divider = 3;
			clearmask = 0x1F1F1F1F;
		} else if(blendpercent < 37.5) {
			// 25% blending
			divider = 2;
			clearmask = 0x3F3F3F3F;
		} else {
			// 50% blending
			divider = 1;
			clearmask = 0x7F7F7F7F;
		}
		current_blendpercent = blendpercent;
	}

	__asm__ __volatile__(
	"movd %4, %%xmm3\n\t"
	"movd %5, %%xmm4\n\t"
	"pshufd $0x0, %%xmm3, %%xmm3\n\t"
	"sub $0x10, %0\n\t"
	"sub $0x10, %1\n\t"
	"sub $0x10, %2\n\t"
	"sse2_fastblend_iter:\n\t"
	"movdqa (%0,%3),%%xmm0\n\t"
	"movdqa %%xmm0,%%xmm2\n\t"
	"movdqa (%1,%3),%%xmm1\n\t"
	"psrlq  %%xmm4,%%xmm0\n\t"
	"psrlq  %%xmm4,%%xmm1\n\t"
	"pand   %%xmm3,%%xmm1\n\t"
	"pand   %%xmm3,%%xmm0\n\t"
	"psubb  %%xmm0,%%xmm1\n\t"
	"paddb  %%xmm2,%%xmm1\n\t"
	"movntdq %%xmm1,(%2,%3)\n\t"
	"sub $0x10, %3\n\t"
	"jnz sse2_fastblend_iter\n\t"
	:
	: "r" (col1), "r" (col2), "r" (result), "r" (count), "m" (clearmask), "m" (divider)
	: "%xmm0", "%xmm1", "%xmm2", "%xmm3", "%xmm4", "cc", "memory"
	);
#else
	Panic("SSE function called on a non x86\\x86-64 platform");
#endif
}

__attribute__((noinline)) void std_fastblend(const uint8_t* col1, const uint8_t* col2, uint8_t* result, unsigned long count, double blendpercent) {
	static int divider = 0;
	static double current_blendpercent = 0.0;
	const uint8_t* const max_ptr = result + count;
	
	if(current_blendpercent != blendpercent) {
		/* Attempt to match the blending percent to one of the possible values */
		if(blendpercent < 2.34375) {
			// 1.5625% blending
			divider = 6;
		} else if(blendpercent < 4.6875) {
			// 3.125% blending
			divider = 5;
		} else if(blendpercent < 9.375) {
			// 6.25% blending
			divider = 4;
		} else if(blendpercent < 18.75) {
			// 12.5% blending
			divider = 3;
		} else if(blendpercent < 37.5) {
			// 25% blending
			divider = 2;
		} else {
			// 50% blending
			divider = 1;
		}
		current_blendpercent = blendpercent;
	}
	

	while(result < max_ptr) {
		result[0] = ((col2[0] - col1[0])>>divider) + col1[0];
		result[1] = ((col2[1] - col1[1])>>divider) + col1[1];
		result[2] = ((col2[2] - col1[2])>>divider) + col1[2];
		result[3] = ((col2[3] - col1[3])>>divider) + col1[3];
		result[4] = ((col2[4] - col1[4])>>divider) + col1[4];
		result[5] = ((col2[5] - col1[5])>>divider) + col1[5];
		result[6] = ((col2[6] - col1[6])>>divider) + col1[6];
		result[7] = ((col2[7] - col1[7])>>divider) + col1[7];
		result[8] = ((col2[8] - col1[8])>>divider) + col1[8];
		result[9] = ((col2[9] - col1[9])>>divider) + col1[9];
		result[10] = ((col2[10] - col1[10])>>divider) + col1[10];
		result[11] = ((col2[11] - col1[11])>>divider) + col1[11];
		result[12] = ((col2[12] - col1[12])>>divider) + col1[12];
		result[13] = ((col2[13] - col1[13])>>divider) + col1[13];
		result[14] = ((col2[14] - col1[14])>>divider) + col1[14];
		result[15] = ((col2[15] - col1[15])>>divider) + col1[15];
		
		col1 += 16;
		col2 += 16;
		result += 16;
	}
}

__attribute__((noinline)) void std_blend(const uint8_t* col1, const uint8_t* col2, uint8_t* result, unsigned long count, double blendpercent) {
	double divide = blendpercent / 100.0;
	double opacity = 1.0 - divide;
	const uint8_t* const max_ptr = result + count;
	
	while(result < max_ptr) {
		*result++ = (*col1++ * opacity) + (*col2++ * divide);
		
	} 
}

/************************************************* DELTA FUNCTIONS *************************************************/

/* Grayscale */
__attribute__((noinline)) void std_delta8_gray8(const uint8_t* col1, const uint8_t* col2, uint8_t* result, unsigned long count) {
	/* Loop unrolling is used to work on 16 bytes (16 grayscale pixels) at a time */  
	const uint8_t* const max_ptr = result + count;
	
	while(result < max_ptr) {
		result[0] = abs(col1[0] - col2[0]);
		result[1] = abs(col1[1] - col2[1]);
		result[2] = abs(col1[2] - col2[2]);
		result[3] = abs(col1[3] - col2[3]);
		result[4] = abs(col1[4] - col2[4]);
		result[5] = abs(col1[5] - col2[5]);
		result[6] = abs(col1[6] - col2[6]);
		result[7] = abs(col1[7] - col2[7]);
		result[8] = abs(col1[8] - col2[8]);
		result[9] = abs(col1[9] - col2[9]);
		result[10] = abs(col1[10] - col2[10]);
		result[11] = abs(col1[11] - col2[11]);
		result[12] = abs(col1[12] - col2[12]);
		result[13] = abs(col1[13] - col2[13]);
		result[14] = abs(col1[14] - col2[14]);
		result[15] = abs(col1[15] - col2[15]);
		
		col1 += 16;
		col2 += 16;
		result += 16;
	}  
}

/* RGB24: RGB */
__attribute__((noinline)) void std_delta8_rgb(const uint8_t* col1, const uint8_t* col2, uint8_t* result, unsigned long count) {
	/* Loop unrolling is used to work on 12 bytes (4 rgb24 pixels) at a time */
	int r,g,b;  
	const uint8_t* const max_ptr = result + count;
	
	while(result < max_ptr) {
		r = abs(col1[0] - col2[0]);
		g = abs(col1[1] - col2[1]);
		b = abs(col1[2] - col2[2]);
		result[0] = (r + r + b + g + g + g + g + g)>>3;
		r = abs(col1[3] - col2[3]);
		g = abs(col1[4] - col2[4]);
		b = abs(col1[5] - col2[5]);
		result[1] = (r + r + b + g + g + g + g + g)>>3;
		r = abs(col1[6] - col2[6]);
		g = abs(col1[7] - col2[7]);
		b = abs(col1[8] - col2[8]);
		result[2] = (r + r + b + g + g + g + g + g)>>3;
		r = abs(col1[9] - col2[9]);
		g = abs(col1[10] - col2[10]);
		b = abs(col1[11] - col2[11]);
		result[3] = (r + r + b + g + g + g + g + g)>>3;
		
		col1 += 12;
		col2 += 12;
		result += 4;
	}
}

/* RGB24: BGR */
__attribute__((noinline)) void std_delta8_bgr(const uint8_t* col1, const uint8_t* col2, uint8_t* result, unsigned long count) {
	/* Loop unrolling is used to work on 12 bytes (4 rgb24 pixels) at a time */
	int r,g,b;  
	const uint8_t* const max_ptr = result + count;
	
	while(result < max_ptr) {
		b = abs(col1[0] - col2[0]);
		g = abs(col1[1] - col2[1]);
		r = abs(col1[2] - col2[2]);
		result[0] = (r + r + b + g + g + g + g + g)>>3;
		b = abs(col1[3] - col2[3]);
		g = abs(col1[4] - col2[4]);
		r = abs(col1[5] - col2[5]);
		result[1] = (r + r + b + g + g + g + g + g)>>3;
		b = abs(col1[6] - col2[6]);
		g = abs(col1[7] - col2[7]);
		r = abs(col1[8] - col2[8]);
		result[2] = (r + r + b + g + g + g + g + g)>>3;
		b = abs(col1[9] - col2[9]);
		g = abs(col1[10] - col2[10]);
		r = abs(col1[11] - col2[11]);
		result[3] = (r + r + b + g + g + g + g + g)>>3;
		
		col1 += 12;
		col2 += 12;
		result += 4;
	}
}

/* RGB32: RGBA */
__attribute__((noinline)) void std_delta8_rgba(const uint8_t* col1, const uint8_t* col2, uint8_t* result, unsigned long count) {
	/* Loop unrolling is used to work on 16 bytes (4 rgb32 pixels) at a time */
	int r,g,b;  
	const uint8_t* const max_ptr = result + count;
	
	while(result < max_ptr) {
		r = abs(col1[0] - col2[0]);
		g = abs(col1[1] - col2[1]);
		b = abs(col1[2] - col2[2]);
		result[0] = (r + r + b + g + g + g + g + g)>>3;
		r = abs(col1[4] - col2[4]);
		g = abs(col1[5] - col2[5]);
		b = abs(col1[6] - col2[6]);
		result[1] = (r + r + b + g + g + g + g + g)>>3;
		r = abs(col1[8] - col2[8]);
		g = abs(col1[9] - col2[9]);
		b = abs(col1[10] - col2[10]);
		result[2] = (r + r + b + g + g + g + g + g)>>3;
		r = abs(col1[12] - col2[12]);
		g = abs(col1[13] - col2[13]);
		b = abs(col1[14] - col2[14]);
		result[3] = (r + r + b + g + g + g + g + g)>>3;
		
		col1 += 16;
		col2 += 16;
		result += 4;
	}
}

/* RGB32: BGRA */
__attribute__((noinline)) void std_delta8_bgra(const uint8_t* col1, const uint8_t* col2, uint8_t* result, unsigned long count) {
	/* Loop unrolling is used to work on 16 bytes (4 rgb32 pixels) at a time */
	int r,g,b;  
	const uint8_t* const max_ptr = result + count;
	
	while(result < max_ptr) {
		b = abs(col1[0] - col2[0]);
		g = abs(col1[1] - col2[1]);
		r = abs(col1[2] - col2[2]);
		result[0] = (r + r + b + g + g + g + g + g)>>3;
		b = abs(col1[4] - col2[4]);
		g = abs(col1[5] - col2[5]);
		r = abs(col1[6] - col2[6]);
		result[1] = (r + r + b + g + g + g + g + g)>>3;
		b = abs(col1[8] - col2[8]);
		g = abs(col1[9] - col2[9]);
		r = abs(col1[10] - col2[10]);
		result[2] = (r + r + b + g + g + g + g + g)>>3;
		b = abs(col1[12] - col2[12]);
		g = abs(col1[13] - col2[13]);
		r = abs(col1[14] - col2[14]);
		result[3] = (r + r + b + g + g + g + g + g)>>3;
		
		col1 += 16;
		col2 += 16;
		result += 4;
	}
}

/* RGB32: ARGB */
__attribute__((noinline)) void std_delta8_argb(const uint8_t* col1, const uint8_t* col2, uint8_t* result, unsigned long count) {
	/* Loop unrolling is used to work on 16 bytes (4 rgb32 pixels) at a time */
	int r,g,b;  
	const uint8_t* const max_ptr = result + count;
	
	while(result < max_ptr) {
		r = abs(col1[1] - col2[1]);
		g = abs(col1[2] - col2[2]);
		b = abs(col1[3] - col2[3]);
		result[0] = (r + r + b + g + g + g + g + g)>>3;
		r = abs(col1[5] - col2[5]);
		g = abs(col1[6] - col2[6]);
		b = abs(col1[7] - col2[7]);
		result[1] = (r + r + b + g + g + g + g + g)>>3;
		r = abs(col1[9] - col2[9]);
		g = abs(col1[10] - col2[10]);
		b = abs(col1[11] - col2[11]);
		result[2] = (r + r + b + g + g + g + g + g)>>3;
		r = abs(col1[13] - col2[13]);
		g = abs(col1[14] - col2[14]);
		b = abs(col1[15] - col2[15]);
		result[3] = (r + r + b + g + g + g + g + g)>>3;
		
		col1 += 16;
		col2 += 16;
		result += 4;
	}
}

/* RGB32: ABGR */
__attribute__((noinline)) void std_delta8_abgr(const uint8_t* col1, const uint8_t* col2, uint8_t* result, unsigned long count) {
	/* Loop unrolling is used to work on 16 bytes (4 rgb32 pixels) at a time */
	int r,g,b;  
	const uint8_t* const max_ptr = result + count;
	
	while(result < max_ptr) {
		b = abs(col1[1] - col2[1]);
		g = abs(col1[2] - col2[2]);
		r = abs(col1[3] - col2[3]);
		result[0] = (r + r + b + g + g + g + g + g)>>3;
		b = abs(col1[5] - col2[5]);
		g = abs(col1[6] - col2[6]);
		r = abs(col1[7] - col2[7]);
		result[1] = (r + r + b + g + g + g + g + g)>>3;
		b = abs(col1[9] - col2[9]);
		g = abs(col1[10] - col2[10]);
		r = abs(col1[11] - col2[11]);
		result[2] = (r + r + b + g + g + g + g + g)>>3;
		b = abs(col1[13] - col2[13]);
		g = abs(col1[14] - col2[14]);
		r = abs(col1[15] - col2[15]);
		result[3] = (r + r + b + g + g + g + g + g)>>3;
		
		col1 += 16;
		col2 += 16;
		result += 4;
	}
}

/* Grayscale SSE2 */
#if defined(__i386__) || defined(__x86_64__)
__attribute__((noinline,__target__("sse2")))
#endif
void sse2_delta8_gray8(const uint8_t* col1, const uint8_t* col2, uint8_t* result, unsigned long count) {
#if ((defined(__i386__) || defined(__x86_64__) || defined(ZM_KEEP_SSE)) && !defined(ZM_STRIP_SSE))  

	__asm__ __volatile__ (
	"sub $0x10, %0\n\t"
	"sub $0x10, %1\n\t"
	"sub $0x10, %2\n\t"
	"sse2_delta8_gray8_iter:\n\t"
	"movdqa (%0,%3), %%xmm1\n\t"
	"movdqa (%1,%3), %%xmm2\n\t"
	"movdqa %%xmm1, %%xmm3\n\t"
	"movdqa %%xmm2, %%xmm4\n\t"
	"pmaxub %%xmm1, %%xmm2\n\t"
	"pminub %%xmm3, %%xmm4\n\t"
	"psubb  %%xmm4, %%xmm2\n\t"
	"movntdq %%xmm2, (%2,%3)\n\t"
	"sub $0x10, %3\n\t"
	"jnz sse2_delta8_gray8_iter\n\t"
	:
	: "r" (col1), "r" (col2), "r" (result), "r" (count)
	: "%xmm1", "%xmm2", "%xmm3", "%xmm4", "cc", "memory"
	);
#else
	Panic("SSE function called on a non x86\\x86-64 platform");
#endif
}

/* RGB32: RGBA SSE2 */
#if defined(__i386__) || defined(__x86_64__)
__attribute__((noinline,__target__("sse2")))
#endif
void sse2_delta8_rgba(const uint8_t* col1, const uint8_t* col2, uint8_t* result, unsigned long count) {
#if ((defined(__i386__) || defined(__x86_64__) || defined(ZM_KEEP_SSE)) && !defined(ZM_STRIP_SSE))  
  
	__asm__ __volatile__ (
	"mov $0x1F1F1F1F, %%eax\n\t"
	"movd %%eax, %%xmm4\n\t"
	"pshufd $0x0, %%xmm4, %%xmm4\n\t"
	"mov $0xff, %%eax\n\t"
	"movd %%eax, %%xmm0\n\t"
	"pshufd $0x0, %%xmm0, %%xmm0\n\t"
	"sub $0x10, %0\n\t"
	"sub $0x10, %1\n\t"
	"sub $0x4, %2\n\t"
	"sse2_delta8_rgba_iter:\n\t"
	"movdqa (%0,%3,4), %%xmm1\n\t"
	"movdqa (%1,%3,4), %%xmm2\n\t"
	"psrlq $0x3, %%xmm1\n\t"
	"psrlq $0x3, %%xmm2\n\t"
	"pand %%xmm4, %%xmm1\n\t"
	"pand %%xmm4, %%xmm2\n\t"
	"movdqa %%xmm1, %%xmm5\n\t"
	"movdqa %%xmm2, %%xmm6\n\t"
	"pmaxub %%xmm1, %%xmm2\n\t"
	"pminub %%xmm5, %%xmm6\n\t"
	"psubb %%xmm6, %%xmm2\n\t"
	"movdqa %%xmm2, %%xmm3\n\t"
	"psrld $0x8, %%xmm2\n\t"
	"pand %%xmm0, %%xmm2\n\t"
	"movdqa %%xmm2, %%xmm1\n\t"
	"pslld $0x2, %%xmm2\n\t"
	"paddd %%xmm1, %%xmm2\n\t"
	"movdqa %%xmm3, %%xmm1\n\t"
	"pand %%xmm0, %%xmm1\n\t"
	"paddd %%xmm1, %%xmm1\n\t"
	"paddd %%xmm2, %%xmm1\n\t"
	"movdqa %%xmm3, %%xmm2\n\t"
	"psrld $0x10, %%xmm2\n\t"
	"pand %%xmm0, %%xmm2\n\t"
	"paddd %%xmm2, %%xmm1\n\t"
	"packssdw %%xmm1, %%xmm1\n\t"
	"packuswb %%xmm1, %%xmm1\n\t"
	"movd %%xmm1, %%eax\n\t"
	"movnti %%eax, (%2,%3)\n\t"
	"sub $0x4, %3\n\t"
	"jnz sse2_delta8_rgba_iter\n\t"
	:
	: "r" (col1), "r" (col2), "r" (result), "r" (count)
	: "%eax", "%xmm0", "%xmm1", "%xmm2", "%xmm3", "%xmm4", "%xmm5", "%xmm6", "cc", "memory"
	);
#else
	Panic("SSE function called on a non x86\\x86-64 platform");
#endif
}

/* RGB32: BGRA SSE2 */
#if defined(__i386__) || defined(__x86_64__)
__attribute__((noinline,__target__("sse2")))
#endif
void sse2_delta8_bgra(const uint8_t* col1, const uint8_t* col2, uint8_t* result, unsigned long count) {
#if ((defined(__i386__) || defined(__x86_64__) || defined(ZM_KEEP_SSE)) && !defined(ZM_STRIP_SSE))  
  
	__asm__ __volatile__ (
	"mov $0x1F1F1F1F, %%eax\n\t"
	"movd %%eax, %%xmm4\n\t"
	"pshufd $0x0, %%xmm4, %%xmm4\n\t"
	"mov $0xff, %%eax\n\t"
	"movd %%eax, %%xmm0\n\t"
	"pshufd $0x0, %%xmm0, %%xmm0\n\t"
	"sub $0x10, %0\n\t"
	"sub $0x10, %1\n\t"
	"sub $0x4, %2\n\t"
	"sse2_delta8_bgra_iter:\n\t"
	"movdqa (%0,%3,4), %%xmm1\n\t"
	"movdqa (%1,%3,4), %%xmm2\n\t"
	"psrlq $0x3, %%xmm1\n\t"
	"psrlq $0x3, %%xmm2\n\t"
	"pand %%xmm4, %%xmm1\n\t"
	"pand %%xmm4, %%xmm2\n\t"
	"movdqa %%xmm1, %%xmm5\n\t"
	"movdqa %%xmm2, %%xmm6\n\t"
	"pmaxub %%xmm1, %%xmm2\n\t"
	"pminub %%xmm5, %%xmm6\n\t"
	"psubb %%xmm6, %%xmm2\n\t"
	"movdqa %%xmm2, %%xmm3\n\t"
	"psrld $0x8, %%xmm2\n\t"
	"pand %%xmm0, %%xmm2\n\t"
	"movdqa %%xmm2, %%xmm1\n\t"
	"pslld $0x2, %%xmm2\n\t"
	"paddd %%xmm1, %%xmm2\n\t"
	"movdqa %%xmm3, %%xmm1\n\t"
	"pand %%xmm0, %%xmm1\n\t"
	"paddd %%xmm2, %%xmm1\n\t"
	"movdqa %%xmm3, %%xmm2\n\t"
	"psrld $0x10, %%xmm2\n\t"
	"pand %%xmm0, %%xmm2\n\t"
	"paddd %%xmm2, %%xmm2\n\t"
	"paddd %%xmm2, %%xmm1\n\t"
	"packssdw %%xmm1, %%xmm1\n\t"
	"packuswb %%xmm1, %%xmm1\n\t"
	"movd %%xmm1, %%eax\n\t"
	"movnti %%eax, (%2,%3)\n\t"
	"sub $0x4, %3\n\t"
	"jnz sse2_delta8_bgra_iter\n\t"
	:
	: "r" (col1), "r" (col2), "r" (result), "r" (count)
	: "%eax", "%xmm0", "%xmm1", "%xmm2", "%xmm3", "%xmm4", "%xmm5", "%xmm6", "cc", "memory"
	);
#else
	Panic("SSE function called on a non x86\\x86-64 platform");
#endif
}

/* RGB32: ARGB SSE2 */
#if defined(__i386__) || defined(__x86_64__)
__attribute__((noinline,__target__("sse2")))
#endif
void sse2_delta8_argb(const uint8_t* col1, const uint8_t* col2, uint8_t* result, unsigned long count) {
#if ((defined(__i386__) || defined(__x86_64__) || defined(ZM_KEEP_SSE)) && !defined(ZM_STRIP_SSE))  
  
	__asm__ __volatile__ (
	"mov $0x1F1F1F1F, %%eax\n\t"
	"movd %%eax, %%xmm4\n\t"
	"pshufd $0x0, %%xmm4, %%xmm4\n\t"
	"mov $0xff, %%eax\n\t"
	"movd %%eax, %%xmm0\n\t"
	"pshufd $0x0, %%xmm0, %%xmm0\n\t"
	"sub $0x10, %0\n\t"
	"sub $0x10, %1\n\t"
	"sub $0x4, %2\n\t"
	"sse2_delta8_argb_iter:\n\t"
	"movdqa (%0,%3,4), %%xmm1\n\t"
	"movdqa (%1,%3,4), %%xmm2\n\t"
	"psrlq $0x3, %%xmm1\n\t"
	"psrlq $0x3, %%xmm2\n\t"
	"pand %%xmm4, %%xmm1\n\t"
	"pand %%xmm4, %%xmm2\n\t"
	"movdqa %%xmm1, %%xmm5\n\t"
	"movdqa %%xmm2, %%xmm6\n\t"
	"pmaxub %%xmm1, %%xmm2\n\t"
	"pminub %%xmm5, %%xmm6\n\t"
	"psubb %%xmm6, %%xmm2\n\t"
	"movdqa %%xmm2, %%xmm3\n\t"
	"psrld $0x10, %%xmm2\n\t"
	"pand %%xmm0, %%xmm2\n\t"
	"movdqa %%xmm2, %%xmm1\n\t"
	"pslld $0x2, %%xmm2\n\t"
	"paddd %%xmm1, %%xmm2\n\t"
	"movdqa %%xmm3, %%xmm1\n\t"
	"psrld $0x8, %%xmm1\n\t"
	"pand %%xmm0, %%xmm1\n\t"
	"paddd %%xmm1, %%xmm1\n\t"
	"paddd %%xmm2, %%xmm1\n\t"
	"movdqa %%xmm3, %%xmm2\n\t"
	"psrld $0x18, %%xmm2\n\t"
	"pand %%xmm0, %%xmm2\n\t"
	"paddd %%xmm2, %%xmm1\n\t"
	"packssdw %%xmm1, %%xmm1\n\t"
	"packuswb %%xmm1, %%xmm1\n\t"
	"movd %%xmm1, %%eax\n\t"
	"movnti %%eax, (%2,%3)\n\t"
	"sub $0x4, %3\n\t"
	"jnz sse2_delta8_argb_iter\n\t"
	:
	: "r" (col1), "r" (col2), "r" (result), "r" (count)
	: "%eax", "%xmm0", "%xmm1", "%xmm2", "%xmm3", "%xmm4", "%xmm5", "%xmm6", "cc", "memory"
	);
#else
	Panic("SSE function called on a non x86\\x86-64 platform");
#endif
}

/* RGB32: ABGR SSE2 */
#if defined(__i386__) || defined(__x86_64__)
__attribute__((noinline,__target__("sse2")))
#endif
void sse2_delta8_abgr(const uint8_t* col1, const uint8_t* col2, uint8_t* result, unsigned long count) {
#if ((defined(__i386__) || defined(__x86_64__) || defined(ZM_KEEP_SSE)) && !defined(ZM_STRIP_SSE))  
  
	__asm__ __volatile__ (
	"mov $0x1F1F1F1F, %%eax\n\t"
	"movd %%eax, %%xmm4\n\t"
	"pshufd $0x0, %%xmm4, %%xmm4\n\t"
	"mov $0xff, %%eax\n\t"
	"movd %%eax, %%xmm0\n\t"
	"pshufd $0x0, %%xmm0, %%xmm0\n\t"
	"sub $0x10, %0\n\t"
	"sub $0x10, %1\n\t"
	"sub $0x4, %2\n\t"
	"sse2_delta8_abgr_iter:\n\t"
	"movdqa (%0,%3,4), %%xmm1\n\t"
	"movdqa (%1,%3,4), %%xmm2\n\t"
	"psrlq $0x3, %%xmm1\n\t"
	"psrlq $0x3, %%xmm2\n\t"
	"pand %%xmm4, %%xmm1\n\t"
	"pand %%xmm4, %%xmm2\n\t"
	"movdqa %%xmm1, %%xmm5\n\t"
	"movdqa %%xmm2, %%xmm6\n\t"
	"pmaxub %%xmm1, %%xmm2\n\t"
	"pminub %%xmm5, %%xmm6\n\t"
	"psubb %%xmm6, %%xmm2\n\t"
	"movdqa %%xmm2, %%xmm3\n\t"
	"psrld $0x10, %%xmm2\n\t"
	"pand %%xmm0, %%xmm2\n\t"
	"movdqa %%xmm2, %%xmm1\n\t"
	"pslld $0x2, %%xmm2\n\t"
	"paddd %%xmm1, %%xmm2\n\t"
	"movdqa %%xmm3, %%xmm1\n\t"
	"psrld $0x8, %%xmm1\n\t"
	"pand %%xmm0, %%xmm1\n\t"
	"paddd %%xmm2, %%xmm1\n\t"
	"movdqa %%xmm3, %%xmm2\n\t"
	"psrld $0x18, %%xmm2\n\t"
	"pand %%xmm0, %%xmm2\n\t"
	"paddd %%xmm2, %%xmm2\n\t"
	"paddd %%xmm2, %%xmm1\n\t"
	"packssdw %%xmm1, %%xmm1\n\t"
	"packuswb %%xmm1, %%xmm1\n\t"
	"movd %%xmm1, %%eax\n\t"
	"movnti %%eax, (%2,%3)\n\t"
	"sub $0x4, %3\n\t"
	"jnz sse2_delta8_abgr_iter\n\t"
	:
	: "r" (col1), "r" (col2), "r" (result), "r" (count)
	: "%eax", "%xmm0", "%xmm1", "%xmm2", "%xmm3", "%xmm4", "%xmm5", "%xmm6", "cc", "memory"
	);
#else
	Panic("SSE function called on a non x86\\x86-64 platform");
#endif
}

/* RGB32: RGBA SSSE3 */
#if defined(__i386__) || defined(__x86_64__)
__attribute__((noinline,__target__("ssse3")))
#endif
void ssse3_delta8_rgba(const uint8_t* col1, const uint8_t* col2, uint8_t* result, unsigned long count) {
#if ((defined(__i386__) || defined(__x86_64__) || defined(ZM_KEEP_SSE)) && !defined(ZM_STRIP_SSE))  
	
	__asm__ __volatile__ (
	"mov $0x1F1F1F1F, %%eax\n\t"
	"movd %%eax, %%xmm4\n\t"
	"pshufd $0x0, %%xmm4, %%xmm4\n\t"
	"mov $0xff, %%eax\n\t"
	"movd %%eax, %%xmm0\n\t"
	"pshufd $0x0, %%xmm0, %%xmm0\n\t"
	"movdqa %4, %%xmm5\n\t"
	"sub $0x10, %0\n\t"
	"sub $0x10, %1\n\t"
	"sub $0x4, %2\n\t"
	"ssse3_delta8_rgba_iter:\n\t"
	"movdqa (%0,%3,4), %%xmm1\n\t"
	"movdqa (%1,%3,4), %%xmm2\n\t"
	"psrlq $0x3, %%xmm1\n\t"
	"psrlq $0x3, %%xmm2\n\t"
	"pand %%xmm4, %%xmm1\n\t"
	"pand %%xmm4, %%xmm2\n\t"
	"psubb %%xmm2, %%xmm1\n\t"
	"pabsb %%xmm1, %%xmm3\n\t"
	"movdqa %%xmm3, %%xmm2\n\t"
	"psrld $0x8, %%xmm2\n\t"
	"pand %%xmm0, %%xmm2\n\t"
	"movdqa %%xmm2, %%xmm1\n\t"
	"pslld $0x2, %%xmm2\n\t"
	"paddd %%xmm1, %%xmm2\n\t"
	"movdqa %%xmm3, %%xmm1\n\t"
	"pand %%xmm0, %%xmm1\n\t"
	"paddd %%xmm1, %%xmm1\n\t"
	"paddd %%xmm2, %%xmm1\n\t"
	"movdqa %%xmm3, %%xmm2\n\t"
	"psrld $0x10, %%xmm2\n\t"
	"pand %%xmm0, %%xmm2\n\t"
	"paddd %%xmm2, %%xmm1\n\t"
	"pshufb %%xmm5, %%xmm1\n\t"
	"movd %%xmm1, %%eax\n\t"
	"movnti %%eax, (%2,%3)\n\t"
	"sub $0x4, %3\n\t"
	"jnz ssse3_delta8_rgba_iter\n\t"
	:
	: "r" (col1), "r" (col2), "r" (result), "r" (count), "m" (*movemask)
	: "%eax", "%xmm0", "%xmm1", "%xmm2", "%xmm3", "%xmm4", "%xmm5", "cc", "memory"
	);
#else
	Panic("SSE function called on a non x86\\x86-64 platform");
#endif
}

/* RGB32: BGRA SSSE3 */
#if defined(__i386__) || defined(__x86_64__)
__attribute__((noinline,__target__("ssse3")))
#endif
void ssse3_delta8_bgra(const uint8_t* col1, const uint8_t* col2, uint8_t* result, unsigned long count) {
#if ((defined(__i386__) || defined(__x86_64__) || defined(ZM_KEEP_SSE)) && !defined(ZM_STRIP_SSE))  
	
	__asm__ __volatile__ (
	"mov $0x1F1F1F1F, %%eax\n\t"
	"movd %%eax, %%xmm4\n\t"
	"pshufd $0x0, %%xmm4, %%xmm4\n\t"
	"mov $0xff, %%eax\n\t"
	"movd %%eax, %%xmm0\n\t"
	"pshufd $0x0, %%xmm0, %%xmm0\n\t"
	"movdqa %4, %%xmm5\n\t"
	"sub $0x10, %0\n\t"
	"sub $0x10, %1\n\t"
	"sub $0x4, %2\n\t"
	"ssse3_delta8_bgra_iter:\n\t"
	"movdqa (%0,%3,4), %%xmm1\n\t"
	"movdqa (%1,%3,4), %%xmm2\n\t"
	"psrlq $0x3, %%xmm1\n\t"
	"psrlq $0x3, %%xmm2\n\t"
	"pand %%xmm4, %%xmm1\n\t"
	"pand %%xmm4, %%xmm2\n\t"
	"psubb %%xmm2, %%xmm1\n\t"
	"pabsb %%xmm1, %%xmm3\n\t"
	"movdqa %%xmm3, %%xmm2\n\t"
	"psrld $0x8, %%xmm2\n\t"
	"pand %%xmm0, %%xmm2\n\t"
	"movdqa %%xmm2, %%xmm1\n\t"
	"pslld $0x2, %%xmm2\n\t"
	"paddd %%xmm1, %%xmm2\n\t"
	"movdqa %%xmm3, %%xmm1\n\t"
	"pand %%xmm0, %%xmm1\n\t"
	"paddd %%xmm2, %%xmm1\n\t"
	"movdqa %%xmm3, %%xmm2\n\t"
	"psrld $0x10, %%xmm2\n\t"
	"pand %%xmm0, %%xmm2\n\t"
	"paddd %%xmm2, %%xmm2\n\t"
	"paddd %%xmm2, %%xmm1\n\t"
	"pshufb %%xmm5, %%xmm1\n\t"
	"movd %%xmm1, %%eax\n\t"
	"movnti %%eax, (%2,%3)\n\t"
	"sub $0x4, %3\n\t"
	"jnz ssse3_delta8_bgra_iter\n\t"
	:
	: "r" (col1), "r" (col2), "r" (result), "r" (count), "m" (*movemask)
	: "%eax", "%xmm0", "%xmm1", "%xmm2", "%xmm3", "%xmm4", "%xmm5", "cc", "memory"
	);
#else
	Panic("SSE function called on a non x86\\x86-64 platform");
#endif
}

/* RGB32: ARGB SSSE3 */
#if defined(__i386__) || defined(__x86_64__)
__attribute__((noinline,__target__("ssse3")))
#endif
void ssse3_delta8_argb(const uint8_t* col1, const uint8_t* col2, uint8_t* result, unsigned long count) {
#if ((defined(__i386__) || defined(__x86_64__) || defined(ZM_KEEP_SSE)) && !defined(ZM_STRIP_SSE))  
	
	__asm__ __volatile__ (
	"mov $0x1F1F1F1F, %%eax\n\t"
	"movd %%eax, %%xmm4\n\t"
	"pshufd $0x0, %%xmm4, %%xmm4\n\t"
	"mov $0xff, %%eax\n\t"
	"movd %%eax, %%xmm0\n\t"
	"pshufd $0x0, %%xmm0, %%xmm0\n\t"
	"movdqa %4, %%xmm5\n\t"
	"sub $0x10, %0\n\t"
	"sub $0x10, %1\n\t"
	"sub $0x4, %2\n\t"
	"ssse3_delta8_argb_iter:\n\t"
	"movdqa (%0,%3,4), %%xmm1\n\t"
	"movdqa (%1,%3,4), %%xmm2\n\t"
	"psrlq $0x3, %%xmm1\n\t"
	"psrlq $0x3, %%xmm2\n\t"
	"pand %%xmm4, %%xmm1\n\t"
	"pand %%xmm4, %%xmm2\n\t"
	"psubb %%xmm2, %%xmm1\n\t"
	"pabsb %%xmm1, %%xmm3\n\t"
	"movdqa %%xmm3, %%xmm2\n\t"
	"psrld $0x10, %%xmm2\n\t"
	"pand %%xmm0, %%xmm2\n\t"
	"movdqa %%xmm2, %%xmm1\n\t"
	"pslld $0x2, %%xmm2\n\t"
	"paddd %%xmm1, %%xmm2\n\t"
	"movdqa %%xmm3, %%xmm1\n\t"
	"psrld $0x8, %%xmm1\n\t"
	"pand %%xmm0, %%xmm1\n\t"
	"paddd %%xmm1, %%xmm1\n\t"
	"paddd %%xmm2, %%xmm1\n\t"
	"movdqa %%xmm3, %%xmm2\n\t"
	"psrld $0x18, %%xmm2\n\t"
	"pand %%xmm0, %%xmm2\n\t"
	"paddd %%xmm2, %%xmm1\n\t"
	"pshufb %%xmm5, %%xmm1\n\t"
	"movd %%xmm1, %%eax\n\t"
	"movnti %%eax, (%2,%3)\n\t"
	"sub $0x4, %3\n\t"
	"jnz ssse3_delta8_argb_iter\n\t"
	:
	: "r" (col1), "r" (col2), "r" (result), "r" (count), "m" (*movemask)
	: "%eax", "%xmm0", "%xmm1", "%xmm2", "%xmm3", "%xmm4", "%xmm5", "cc", "memory"
	);
#else
	Panic("SSE function called on a non x86\\x86-64 platform");
#endif
}

/* RGB32: ABGR SSSE3 */
#if defined(__i386__) || defined(__x86_64__)
__attribute__((noinline,__target__("ssse3")))
#endif
void ssse3_delta8_abgr(const uint8_t* col1, const uint8_t* col2, uint8_t* result, unsigned long count) {
#if ((defined(__i386__) || defined(__x86_64__) || defined(ZM_KEEP_SSE)) && !defined(ZM_STRIP_SSE))  
	
	__asm__ __volatile__ (
	"mov $0x1F1F1F1F, %%eax\n\t"
	"movd %%eax, %%xmm4\n\t"
	"pshufd $0x0, %%xmm4, %%xmm4\n\t"
	"mov $0xff, %%eax\n\t"
	"movd %%eax, %%xmm0\n\t"
	"pshufd $0x0, %%xmm0, %%xmm0\n\t"
	"movdqa %4, %%xmm5\n\t"
	"sub $0x10, %0\n\t"
	"sub $0x10, %1\n\t"
	"sub $0x4, %2\n\t"
	"ssse3_delta8_abgr_iter:\n\t"
	"movdqa (%0,%3,4), %%xmm1\n\t"
	"movdqa (%1,%3,4), %%xmm2\n\t"
	"psrlq $0x3, %%xmm1\n\t"
	"psrlq $0x3, %%xmm2\n\t"
	"pand %%xmm4, %%xmm1\n\t"
	"pand %%xmm4, %%xmm2\n\t"
	"psubb %%xmm2, %%xmm1\n\t"
	"pabsb %%xmm1, %%xmm3\n\t"
	"movdqa %%xmm3, %%xmm2\n\t"
	"psrld $0x10, %%xmm2\n\t"
	"pand %%xmm0, %%xmm2\n\t"
	"movdqa %%xmm2, %%xmm1\n\t"
	"pslld $0x2, %%xmm2\n\t"
	"paddd %%xmm1, %%xmm2\n\t"
	"movdqa %%xmm3, %%xmm1\n\t"
	"psrld $0x8, %%xmm1\n\t"
	"pand %%xmm0, %%xmm1\n\t"
	"paddd %%xmm2, %%xmm1\n\t"
	"movdqa %%xmm3, %%xmm2\n\t"
	"psrld $0x18, %%xmm2\n\t"
	"pand %%xmm0, %%xmm2\n\t"
	"paddd %%xmm2, %%xmm2\n\t"
	"paddd %%xmm2, %%xmm1\n\t"
	"pshufb %%xmm5, %%xmm1\n\t"
	"movd %%xmm1, %%eax\n\t"
	"movnti %%eax, (%2,%3)\n\t"
	"sub $0x4, %3\n\t"
	"jnz ssse3_delta8_abgr_iter\n\t"
	:
	: "r" (col1), "r" (col2), "r" (result), "r" (count), "m" (*movemask)
	: "%eax", "%xmm0", "%xmm1", "%xmm2", "%xmm3", "%xmm4", "%xmm5", "cc", "memory"
	);
#else
	Panic("SSE function called on a non x86\\x86-64 platform");
#endif
}


/************************************************* CONVERT FUNCTIONS *************************************************/

/* RGB24 to grayscale */
__attribute__((noinline)) void std_convert_rgb_gray8(const uint8_t* col1, uint8_t* result, unsigned long count) {
	unsigned int r,g,b;  
	const uint8_t* const max_ptr = result + count;
	
	while(result < max_ptr) {
		r = col1[0];
		g = col1[1];
		b = col1[2];
		result[0] = (r + r + b + g + g + g + g + g)>>3;
		r = col1[3];
		g = col1[4];
		b = col1[5];
		result[1] = (r + r + b + g + g + g + g + g)>>3;
		r = col1[6];
		g = col1[7];
		b = col1[8];
		result[2] = (r + r + b + g + g + g + g + g)>>3;
		r = col1[9];
		g = col1[10];
		b = col1[11];
		result[3] = (r + r + b + g + g + g + g + g)>>3;
		
		col1 += 12;
		result += 4;
	}
}

/* BGR24 to grayscale */
__attribute__((noinline)) void std_convert_bgr_gray8(const uint8_t* col1, uint8_t* result, unsigned long count) {
	unsigned int r,g,b;  
	const uint8_t* const max_ptr = result + count;
	
	while(result < max_ptr) {
		b = col1[0];
		g = col1[1];
		r = col1[2];
		result[0] = (r + r + b + g + g + g + g + g)>>3;
		b = col1[3];
		g = col1[4];
		r = col1[5];
		result[1] = (r + r + b + g + g + g + g + g)>>3;
		b = col1[6];
		g = col1[7];
		r = col1[8];
		result[2] = (r + r + b + g + g + g + g + g)>>3;
		b = col1[9];
		g = col1[10];
		r = col1[11];
		result[3] = (r + r + b + g + g + g + g + g)>>3;
		
		col1 += 12;
		result += 4;
	}
}

/* RGBA to grayscale */
__attribute__((noinline)) void std_convert_rgba_gray8(const uint8_t* col1, uint8_t* result, unsigned long count) {
	unsigned int r,g,b;  
	const uint8_t* const max_ptr = result + count;
	
	while(result < max_ptr) {
		r = col1[0];
		g = col1[1];
		b = col1[2];
		result[0] = (r + r + b + g + g + g + g + g)>>3;
		r = col1[4];
		g = col1[5];
		b = col1[6];
		result[1] = (r + r + b + g + g + g + g + g)>>3;
		r = col1[8];
		g = col1[9];
		b = col1[10];
		result[2] = (r + r + b + g + g + g + g + g)>>3;
		r = col1[12];
		g = col1[13];
		b = col1[14];
		result[3] = (r + r + b + g + g + g + g + g)>>3;
		
		col1 += 16;
		result += 4;
	}
}

/* BGRA to grayscale */
__attribute__((noinline)) void std_convert_bgra_gray8(const uint8_t* col1, uint8_t* result, unsigned long count) {
	unsigned int r,g,b;  
	const uint8_t* const max_ptr = result + count;
	
	while(result < max_ptr) {
		b = col1[0];
		g = col1[1];
		r = col1[2];
		result[0] = (r + r + b + g + g + g + g + g)>>3;
		b = col1[4];
		g = col1[5];
		r = col1[6];
		result[1] = (r + r + b + g + g + g + g + g)>>3;
		b = col1[8];
		g = col1[9];
		r = col1[10];
		result[2] = (r + r + b + g + g + g + g + g)>>3;
		b = col1[12];
		g = col1[13];
		r = col1[14];
		result[3] = (r + r + b + g + g + g + g + g)>>3;
		
		col1 += 16;
		result += 4;
	}
}

/* ARGB to grayscale */
__attribute__((noinline)) void std_convert_argb_gray8(const uint8_t* col1, uint8_t* result, unsigned long count) {
	unsigned int r,g,b;  
	const uint8_t* const max_ptr = result + count;
	
	while(result < max_ptr) {
		r = col1[1];
		g = col1[2];
		b = col1[3];
		result[0] = (r + r + b + g + g + g + g + g)>>3;
		r = col1[5];
		g = col1[6];
		b = col1[7];
		result[1] = (r + r + b + g + g + g + g + g)>>3;
		r = col1[9];
		g = col1[10];
		b = col1[11];
		result[2] = (r + r + b + g + g + g + g + g)>>3;
		r = col1[13];
		g = col1[14];
		b = col1[15];
		result[3] = (r + r + b + g + g + g + g + g)>>3;
		
		col1 += 16;
		result += 4;
	}
}

/* ABGR to grayscale */
__attribute__((noinline)) void std_convert_abgr_gray8(const uint8_t* col1, uint8_t* result, unsigned long count) {
	unsigned int r,g,b;  
	const uint8_t* const max_ptr = result + count;
	
	while(result < max_ptr) {
		b = col1[1];
		g = col1[2];
		r = col1[3];
		result[0] = (r + r + b + g + g + g + g + g)>>3;
		b = col1[5];
		g = col1[6];
		r = col1[7];
		result[1] = (r + r + b + g + g + g + g + g)>>3;
		b = col1[9];
		g = col1[10];
		r = col1[11];
		result[2] = (r + r + b + g + g + g + g + g)>>3;
		b = col1[13];
		g = col1[14];
		r = col1[15];
		result[3] = (r + r + b + g + g + g + g + g)>>3;
		
		col1 += 16;
		result += 4;
	}
}

/* Converts a YUYV image into grayscale by extracting the Y channel */
__attribute__((noinline)) void std_convert_yuyv_gray8(const uint8_t* col1, uint8_t* result, unsigned long count) {
	const uint16_t* yuvbuf = (const uint16_t*)col1;
	const uint8_t* const max_ptr = result + count;
	
	while(result < max_ptr) {
		result[0] = (uint8_t)yuvbuf[0];
		result[1] = (uint8_t)yuvbuf[1];
		result[2] = (uint8_t)yuvbuf[2];
		result[3] = (uint8_t)yuvbuf[3];
		result[4] = (uint8_t)yuvbuf[4];
		result[5] = (uint8_t)yuvbuf[5];
		result[6] = (uint8_t)yuvbuf[6];
		result[7] = (uint8_t)yuvbuf[7];
		result[8] = (uint8_t)yuvbuf[8];
		result[9] = (uint8_t)yuvbuf[9];
		result[10] = (uint8_t)yuvbuf[10];
		result[11] = (uint8_t)yuvbuf[11];
		result[12] = (uint8_t)yuvbuf[12];
		result[13] = (uint8_t)yuvbuf[13];
		result[14] = (uint8_t)yuvbuf[14];
		result[15] = (uint8_t)yuvbuf[15];
		
		yuvbuf += 16;
		result += 16;
	}
}

/* RGBA to grayscale SSSE3 */
#if defined(__i386__) || defined(__x86_64__)
__attribute__((noinline,__target__("ssse3")))
#endif
void ssse3_convert_rgba_gray8(const uint8_t* col1, uint8_t* result, unsigned long count) {
#if ((defined(__i386__) || defined(__x86_64__) || defined(ZM_KEEP_SSE)) && !defined(ZM_STRIP_SSE))  

	__asm__ __volatile__ (
	"mov $0x1F1F1F1F, %%eax\n\t"
	"movd %%eax, %%xmm4\n\t"
	"pshufd $0x0, %%xmm4, %%xmm4\n\t"
	"mov $0xff, %%eax\n\t"
	"movd %%eax, %%xmm0\n\t"
	"pshufd $0x0, %%xmm0, %%xmm0\n\t"
	"movdqa %3, %%xmm5\n\t"
	"sub $0x10, %0\n\t"
	"sub $0x4, %1\n\t"
	"ssse3_convert_rgba_gray8_iter:\n\t"
	"movdqa (%0,%2,4), %%xmm3\n\t"
	"psrlq $0x3, %%xmm3\n\t"
	"pand %%xmm4, %%xmm3\n\t"
	"movdqa %%xmm3, %%xmm2\n\t"
	"psrld $0x8, %%xmm2\n\t"
	"pand %%xmm0, %%xmm2\n\t"
	"movdqa %%xmm2, %%xmm1\n\t"
	"pslld $0x2, %%xmm2\n\t"
	"paddd %%xmm1, %%xmm2\n\t"
	"movdqa %%xmm3, %%xmm1\n\t"
	"pand %%xmm0, %%xmm1\n\t"
	"paddd %%xmm1, %%xmm1\n\t"
	"paddd %%xmm2, %%xmm1\n\t"
	"movdqa %%xmm3, %%xmm2\n\t"
	"psrld $0x10, %%xmm2\n\t"
	"pand %%xmm0, %%xmm2\n\t"
	"paddd %%xmm2, %%xmm1\n\t"
	"pshufb %%xmm5, %%xmm1\n\t"
	"movd %%xmm1, %%eax\n\t"
	"movnti %%eax, (%1,%2)\n\t"
	"sub $0x4, %2\n\t"
	"jnz ssse3_convert_rgba_gray8_iter\n\t"
	:
	: "r" (col1), "r" (result), "r" (count), "m" (*movemask)
	: "%eax", "%xmm0", "%xmm1", "%xmm2", "%xmm3", "%xmm4", "%xmm5", "cc", "memory"
	);
#else
	Panic("SSE function called on a non x86\\x86-64 platform");
#endif
}

/* Converts a YUYV image into grayscale by extracting the Y channel */
#if defined(__i386__) || defined(__x86_64__)
__attribute__((noinline,__target__("ssse3")))
#endif
void ssse3_convert_yuyv_gray8(const uint8_t* col1, uint8_t* result, unsigned long count) {
#if ((defined(__i386__) || defined(__x86_64__) || defined(ZM_KEEP_SSE)) && !defined(ZM_STRIP_SSE))  
	unsigned long i = 0;
  
	__attribute__((aligned(16))) static const uint8_t movemask1[16] = {0,2,4,6,8,10,12,14,0xFF,0xFF,0xFF,0xFF,0xFF,0xFF,0xFF,0xFF};
	__attribute__((aligned(16))) static const uint8_t movemask2[16] = {0xFF,0xFF,0xFF,0xFF,0xFF,0xFF,0xFF,0xFF,0,2,4,6,8,10,12,14};
	
	/* XMM0 - General purpose */
	/* XMM1 - General purpose */
	/* XMM2 - unused */
	/* XMM3 - shift mask 1 */
	/* XMM4 - shift mask 2 */
	/* XMM5 - unused*/
	/* XMM6 - unused */
	/* XMM7 - unused */

	__asm__ __volatile__ (
	"movdqa %4, %%xmm3\n\t"
	"movdqa %5, %%xmm4\n\t"
	"algo_ssse3_convert_yuyv_gray8:\n\t"
	"movdqa (%0), %%xmm0\n\t"
	"pshufb %%xmm3, %%xmm0\n\t"
	"movdqa 0x10(%0), %%xmm1\n\t"
	"pshufb %%xmm4, %%xmm1\n\t"
	"por %%xmm1, %%xmm0\n\t"
	"movntdq %%xmm0, (%1)\n\t"
	"add $0x10, %3\n\t"
	"add $0x10, %1\n\t"
	"add $0x20, %0\n\t"
	"cmp %2, %3\n\t"
	"jb algo_ssse3_convert_yuyv_gray8\n\t"
	:
#if (defined(_DEBUG) && !defined(__x86_64__)) /* Use one less register to allow compilation to success on 32bit with omit frame pointer disabled */
	: "r" (col1), "r" (result), "m" (count), "r" (i), "m" (*movemask1), "m" (*movemask2)
#else
	: "r" (col1), "r" (result), "r" (count), "r" (i), "m" (*movemask1), "m" (*movemask2)
#endif
	: "%xmm3", "%xmm4", "cc", "memory"
	);
#else
	Panic("SSE function called on a non x86\\x86-64 platform");
#endif
}

/* YUYV to RGB24 - relocated from zm_local_camera.cpp */
__attribute__((noinline)) void zm_convert_yuyv_rgb(const uint8_t* col1, uint8_t* result, unsigned long count) {
	unsigned int r,g,b;
	unsigned int y1,y2,u,v;
	for(unsigned int i=0; i < count; i += 2, col1 += 4, result += 6) {
		y1 = col1[0];
		u = col1[1];
		y2 = col1[2];
		v = col1[3];

		r = y1 + r_v_table[v];
		g = y1 - (g_u_table[u]+g_v_table[v]);
		b = y1 + b_u_table[u];
		
		result[0] = r<0?0:(r>255?255:r);
		result[1] = g<0?0:(g>255?255:g);
		result[2] = b<0?0:(b>255?255:b);
		
		r = y2 + r_v_table[v];
		g = y2 - (g_u_table[u]+g_v_table[v]);
		b = y2 + b_u_table[u];

		result[3] = r<0?0:(r>255?255:r);
		result[4] = g<0?0:(g>255?255:g);
		result[5] = b<0?0:(b>255?255:b);
	}
	
}

/* YUYV to RGBA - modified the one above */
__attribute__((noinline)) void zm_convert_yuyv_rgba(const uint8_t* col1, uint8_t* result, unsigned long count) {
	unsigned int r,g,b;
	unsigned int y1,y2,u,v;
	for(unsigned int i=0; i < count; i += 2, col1 += 4, result += 8) {
		y1 = col1[0];
		u = col1[1];
		y2 = col1[2];
		v = col1[3];

		r = y1 + r_v_table[v];
		g = y1 - (g_u_table[u]+g_v_table[v]);
		b = y1 + b_u_table[u];
		
		result[0] = r<0?0:(r>255?255:r);
		result[1] = g<0?0:(g>255?255:g);
		result[2] = b<0?0:(b>255?255:b);
		
		r = y2 + r_v_table[v];
		g = y2 - (g_u_table[u]+g_v_table[v]);
		b = y2 + b_u_table[u];

		result[4] = r<0?0:(r>255?255:r);
		result[5] = g<0?0:(g>255?255:g);
		result[6] = b<0?0:(b>255?255:b);
	}
	
}

/* RGB555 to RGB24 - relocated from zm_local_camera.cpp */
__attribute__((noinline)) void zm_convert_rgb555_rgb(const uint8_t* col1, uint8_t* result, unsigned long count) {
	unsigned int r,g,b;  
	for(unsigned int i=0; i < count; i++, col1 += 2, result += 3) {  
		b = ((*col1)<<3)&0xf8;
		g = (((*(col1+1))<<6)|((*col1)>>2))&0xf8;
		r = ((*(col1+1))<<1)&0xf8;
		result[0] = r;
		result[1] = g;
		result[2] = b;
	}
}

/* RGB555 to RGBA - modified the one above */
__attribute__((noinline)) void zm_convert_rgb555_rgba(const uint8_t* col1, uint8_t* result, unsigned long count) {
	unsigned int r,g,b;  
	for(unsigned int i=0; i < count; i++, col1 += 2, result += 4) {  
		b = ((*col1)<<3)&0xf8;
		g = (((*(col1+1))<<6)|((*col1)>>2))&0xf8;
		r = ((*(col1+1))<<1)&0xf8;
		result[0] = r;
		result[1] = g;
		result[2] = b;
	}
}

/* RGB565 to RGB24 - relocated from zm_local_camera.cpp */
__attribute__((noinline)) void zm_convert_rgb565_rgb(const uint8_t* col1, uint8_t* result, unsigned long count) {
	unsigned int r,g,b;  
	for(unsigned int i=0; i < count; i++, col1 += 2, result += 3) {  
		b = ((*col1)<<3)&0xf8;
		g = (((*(col1+1))<<5)|((*col1)>>3))&0xfc;
		r = (*(col1+1))&0xf8;
		result[0] = r;
		result[1] = g;
		result[2] = b;
	}
}

/* RGB565 to RGBA - modified the one above */
__attribute__((noinline)) void zm_convert_rgb565_rgba(const uint8_t* col1, uint8_t* result, unsigned long count) {
	unsigned int r,g,b;  
	for(unsigned int i=0; i < count; i++, col1 += 2, result += 4) {  
		b = ((*col1)<<3)&0xf8;
		g = (((*(col1+1))<<5)|((*col1)>>3))&0xfc;
		r = (*(col1+1))&0xf8;
		result[0] = r;
		result[1] = g;
		result[2] = b;
	}
}

/************************************************* DEINTERLACE FUNCTIONS *************************************************/

/* Grayscale */
__attribute__((noinline)) void std_deinterlace_4field_gray8(uint8_t* col1, uint8_t* col2, unsigned int threshold, unsigned int width, unsigned int height)
{
	uint8_t *pcurrent, *pabove, *pncurrent, *pnabove, *pbelow;
	const uint8_t* const max_ptr = col1 + (width*(height-1));
	const uint8_t *max_ptr2;

	pcurrent = col1 + width;
	pncurrent = col2 + width;
	pabove = col1;
	pnabove = col2;
	pbelow = col1 + (width*2);
	while(pcurrent < max_ptr)
	{
		max_ptr2 = pcurrent + width;
		while(pcurrent < max_ptr2) {
			if((unsigned int)((abs(*pnabove - *pabove) + abs(*pncurrent - *pcurrent)) >> 1) >= threshold) {
				*pcurrent = (*pabove + *pbelow) >> 1;
			}
			pabove++;
			pnabove++;
			pcurrent++;
			pncurrent++;
			pbelow++;
		}
		pcurrent += width;
		pncurrent += width;
		pabove += width;
		pnabove += width;
		pbelow += width;
		
	}
	
	/* Special case for the last line */
	max_ptr2 = pcurrent + width;
	while(pcurrent < max_ptr2) {
		if((unsigned int)((abs(*pnabove - *pabove) + abs(*pncurrent - *pcurrent)) >> 1) >= threshold) {
			*pcurrent = *pabove;
		}
		pabove++;
		pnabove++;
		pcurrent++;
		pncurrent++;
	}
}

/* RGB */
__attribute__((noinline)) void std_deinterlace_4field_rgb(uint8_t* col1, uint8_t* col2, unsigned int threshold, unsigned int width, unsigned int height)
{
	uint8_t *pcurrent, *pabove, *pncurrent, *pnabove, *pbelow;
	const unsigned int row_width = width*3;
	const uint8_t* const max_ptr = col1 + (row_width * (height-1));
	const uint8_t *max_ptr2;
	unsigned int b, g, r;
	unsigned int delta1, delta2;

	pcurrent = col1 + (width*3);
	pncurrent = col2 + (width*3);
	pabove = col1;
	pnabove = col2;
	pbelow = col1 + ((width*2)*3);
	while(pcurrent < max_ptr)
	{
		max_ptr2 = pcurrent + row_width;
		while(pcurrent < max_ptr2) {
			r = abs(pnabove[0] - pabove[0]);
			g = abs(pnabove[1] - pabove[1]);
			b = abs(pnabove[2] - pabove[2]);
			delta1 = (r + r + b + g + g + g + g + g)>>3;
			r = abs(pncurrent[0] - pcurrent[0]);
			g = abs(pncurrent[1] - pcurrent[1]);
			b = abs(pncurrent[2] - pcurrent[2]);
			delta2 = (r + r + b + g + g + g + g + g)>>3;
			if(((delta1 + delta2) >> 1) >= threshold) {
				pcurrent[0] = (pabove[0] + pbelow[0]) >> 1;
				pcurrent[1] = (pabove[1] + pbelow[1]) >> 1;
				pcurrent[2] = (pabove[2] + pbelow[2]) >> 1;
			}
			pabove += 3;
			pnabove += 3;
			pcurrent += 3;
			pncurrent += 3;
			pbelow += 3;
		}
		pcurrent += row_width;
		pncurrent += row_width;
		pabove += row_width;
		pnabove += row_width;
		pbelow += row_width;
		
	}
	
	/* Special case for the last line */
	max_ptr2 = pcurrent + row_width;
	while(pcurrent < max_ptr2) {
			r = abs(pnabove[0] - pabove[0]);
			g = abs(pnabove[1] - pabove[1]);
			b = abs(pnabove[2] - pabove[2]);
			delta1 = (r + r + b + g + g + g + g + g)>>3;
			r = abs(pncurrent[0] - pcurrent[0]);
			g = abs(pncurrent[1] - pcurrent[1]);
			b = abs(pncurrent[2] - pcurrent[2]);
			delta2 = (r + r + b + g + g + g + g + g)>>3;
			if(((delta1 + delta2) >> 1) >= threshold) {
				pcurrent[0] = pabove[0];
				pcurrent[1] = pabove[1];
				pcurrent[2] = pabove[2];
			}
			pabove += 3;
			pnabove += 3;
			pcurrent += 3;
			pncurrent += 3;
	}
}

/* BGR */
__attribute__((noinline)) void std_deinterlace_4field_bgr(uint8_t* col1, uint8_t* col2, unsigned int threshold, unsigned int width, unsigned int height)
{
	uint8_t *pcurrent, *pabove, *pncurrent, *pnabove, *pbelow;
	const unsigned int row_width = width*3;
	const uint8_t* const max_ptr = col1 + (row_width * (height-1));
	const uint8_t *max_ptr2;
	unsigned int b, g, r;
	unsigned int delta1, delta2;

	pcurrent = col1 + (width*3);
	pncurrent = col2 + (width*3);
	pabove = col1;
	pnabove = col2;
	pbelow = col1 + ((width*2)*3);
	while(pcurrent < max_ptr)
	{
		max_ptr2 = pcurrent + row_width;
		while(pcurrent < max_ptr2) {
			b = abs(pnabove[0] - pabove[0]);
			g = abs(pnabove[1] - pabove[1]);
			r = abs(pnabove[2] - pabove[2]);
			delta1 = (r + r + b + g + g + g + g + g)>>3;
			b = abs(pncurrent[0] - pcurrent[0]);
			g = abs(pncurrent[1] - pcurrent[1]);
			r = abs(pncurrent[2] - pcurrent[2]);
			delta2 = (r + r + b + g + g + g + g + g)>>3;
			if(((delta1 + delta2) >> 1) >= threshold) {
				pcurrent[0] = (pabove[0] + pbelow[0]) >> 1;
				pcurrent[1] = (pabove[1] + pbelow[1]) >> 1;
				pcurrent[2] = (pabove[2] + pbelow[2]) >> 1;
			}
			pabove += 3;
			pnabove += 3;
			pcurrent += 3;
			pncurrent += 3;
			pbelow += 3;
		}
		pcurrent += row_width;
		pncurrent += row_width;
		pabove += row_width;
		pnabove += row_width;
		pbelow += row_width;
		
	}
	
	/* Special case for the last line */
	max_ptr2 = pcurrent + row_width;
	while(pcurrent < max_ptr2) {
			b = abs(pnabove[0] - pabove[0]);
			g = abs(pnabove[1] - pabove[1]);
			r = abs(pnabove[2] - pabove[2]);
			delta1 = (r + r + b + g + g + g + g + g)>>3;
			b = abs(pncurrent[0] - pcurrent[0]);
			g = abs(pncurrent[1] - pcurrent[1]);
			r = abs(pncurrent[2] - pcurrent[2]);
			delta2 = (r + r + b + g + g + g + g + g)>>3;
			if(((delta1 + delta2) >> 1) >= threshold) {
				pcurrent[0] = pabove[0];
				pcurrent[1] = pabove[1];
				pcurrent[2] = pabove[2];
			}
			pabove += 3;
			pnabove += 3;
			pcurrent += 3;
			pncurrent += 3;
	}
}

/* RGBA */
__attribute__((noinline)) void std_deinterlace_4field_rgba(uint8_t* col1, uint8_t* col2, unsigned int threshold, unsigned int width, unsigned int height)
{
	uint8_t *pcurrent, *pabove, *pncurrent, *pnabove, *pbelow;
	const unsigned int row_width = width*4;
	const uint8_t* const max_ptr = col1 + (row_width * (height-1));
	const uint8_t *max_ptr2;
	unsigned int b, g, r;
	unsigned int delta1, delta2;

	pcurrent = col1 + row_width;
	pncurrent = col2 + row_width;
	pabove = col1;
	pnabove = col2;
	pbelow = col1 + (row_width*2);
	while(pcurrent < max_ptr)
	{
		max_ptr2 = pcurrent + row_width;
		while(pcurrent < max_ptr2) {
			r = abs(pnabove[0] - pabove[0]);
			g = abs(pnabove[1] - pabove[1]);
			b = abs(pnabove[2] - pabove[2]);
			delta1 = (r + r + b + g + g + g + g + g)>>3;
			r = abs(pncurrent[0] - pcurrent[0]);
			g = abs(pncurrent[1] - pcurrent[1]);
			b = abs(pncurrent[2] - pcurrent[2]);
			delta2 = (r + r + b + g + g + g + g + g)>>3;
			if(((delta1 + delta2) >> 1) >= threshold) {
				pcurrent[0] = (pabove[0] + pbelow[0]) >> 1;
				pcurrent[1] = (pabove[1] + pbelow[1]) >> 1;
				pcurrent[2] = (pabove[2] + pbelow[2]) >> 1;
			}
			pabove += 4;
			pnabove += 4;
			pcurrent += 4;
			pncurrent += 4;
			pbelow += 4;
		}
		pcurrent += row_width;
		pncurrent += row_width;
		pabove += row_width;
		pnabove += row_width;
		pbelow += row_width;
		
	}
	
	/* Special case for the last line */
	max_ptr2 = pcurrent + row_width;
	while(pcurrent < max_ptr2) {
			r = abs(pnabove[0] - pabove[0]);
			g = abs(pnabove[1] - pabove[1]);
			b = abs(pnabove[2] - pabove[2]);
			delta1 = (r + r + b + g + g + g + g + g)>>3;
			r = abs(pncurrent[0] - pcurrent[0]);
			g = abs(pncurrent[1] - pcurrent[1]);
			b = abs(pncurrent[2] - pcurrent[2]);
			delta2 = (r + r + b + g + g + g + g + g)>>3;
			if(((delta1 + delta2) >> 1) >= threshold) {
				pcurrent[0] = pabove[0];
				pcurrent[1] = pabove[1];
				pcurrent[2] = pabove[2];
			}
			pabove += 4;
			pnabove += 4;
			pcurrent += 4;
			pncurrent += 4;
	}
}

/* BGRA */
__attribute__((noinline)) void std_deinterlace_4field_bgra(uint8_t* col1, uint8_t* col2, unsigned int threshold, unsigned int width, unsigned int height)
{
	uint8_t *pcurrent, *pabove, *pncurrent, *pnabove, *pbelow;
	const unsigned int row_width = width*4;
	const uint8_t* const max_ptr = col1 + (row_width * (height-1));
	const uint8_t *max_ptr2;
	unsigned int b, g, r;
	unsigned int delta1, delta2;

	pcurrent = col1 + row_width;
	pncurrent = col2 + row_width;
	pabove = col1;
	pnabove = col2;
	pbelow = col1 + (row_width*2);
	while(pcurrent < max_ptr)
	{
		max_ptr2 = pcurrent + row_width;
		while(pcurrent < max_ptr2) {
			b = abs(pnabove[0] - pabove[0]);
			g = abs(pnabove[1] - pabove[1]);
			r = abs(pnabove[2] - pabove[2]);
			delta1 = (r + r + b + g + g + g + g + g)>>3;
			b = abs(pncurrent[0] - pcurrent[0]);
			g = abs(pncurrent[1] - pcurrent[1]);
			r = abs(pncurrent[2] - pcurrent[2]);
			delta2 = (r + r + b + g + g + g + g + g)>>3;
			if(((delta1 + delta2) >> 1) >= threshold) {
				pcurrent[0] = (pabove[0] + pbelow[0]) >> 1;
				pcurrent[1] = (pabove[1] + pbelow[1]) >> 1;
				pcurrent[2] = (pabove[2] + pbelow[2]) >> 1;
			}
			pabove += 4;
			pnabove += 4;
			pcurrent += 4;
			pncurrent += 4;
			pbelow += 4;
		}
		pcurrent += row_width;
		pncurrent += row_width;
		pabove += row_width;
		pnabove += row_width;
		pbelow += row_width;
		
	}
	
	/* Special case for the last line */
	max_ptr2 = pcurrent + row_width;
	while(pcurrent < max_ptr2) {
			b = abs(pnabove[0] - pabove[0]);
			g = abs(pnabove[1] - pabove[1]);
			r = abs(pnabove[2] - pabove[2]);
			delta1 = (r + r + b + g + g + g + g + g)>>3;
			b = abs(pncurrent[0] - pcurrent[0]);
			g = abs(pncurrent[1] - pcurrent[1]);
			r = abs(pncurrent[2] - pcurrent[2]);
			delta2 = (r + r + b + g + g + g + g + g)>>3;
			if(((delta1 + delta2) >> 1) >= threshold) {
				pcurrent[0] = pabove[0];
				pcurrent[1] = pabove[1];
				pcurrent[2] = pabove[2];
			}
			pabove += 4;
			pnabove += 4;
			pcurrent += 4;
			pncurrent += 4;
	}
}

/* ARGB */
__attribute__((noinline)) void std_deinterlace_4field_argb(uint8_t* col1, uint8_t* col2, unsigned int threshold, unsigned int width, unsigned int height)
{
	uint8_t *pcurrent, *pabove, *pncurrent, *pnabove, *pbelow;
	const unsigned int row_width = width*4;
	const uint8_t* const max_ptr = col1 + (row_width * (height-1));
	const uint8_t *max_ptr2;
	unsigned int b, g, r;
	unsigned int delta1, delta2;

	pcurrent = col1 + row_width;
	pncurrent = col2 + row_width;
	pabove = col1;
	pnabove = col2;
	pbelow = col1 + (row_width*2);
	while(pcurrent < max_ptr)
	{
		max_ptr2 = pcurrent + row_width;
		while(pcurrent < max_ptr2) {
			r = abs(pnabove[1] - pabove[1]);
			g = abs(pnabove[2] - pabove[2]);
			b = abs(pnabove[3] - pabove[3]);
			delta1 = (r + r + b + g + g + g + g + g)>>3;
			r = abs(pncurrent[1] - pcurrent[1]);
			g = abs(pncurrent[2] - pcurrent[2]);
			b = abs(pncurrent[3] - pcurrent[3]);
			delta2 = (r + r + b + g + g + g + g + g)>>3;
			if(((delta1 + delta2) >> 1) >= threshold) {
				pcurrent[1] = (pabove[1] + pbelow[1]) >> 1;
				pcurrent[2] = (pabove[2] + pbelow[2]) >> 1;
				pcurrent[3] = (pabove[3] + pbelow[3]) >> 1;
			}
			pabove += 4;
			pnabove += 4;
			pcurrent += 4;
			pncurrent += 4;
			pbelow += 4;
		}
		pcurrent += row_width;
		pncurrent += row_width;
		pabove += row_width;
		pnabove += row_width;
		pbelow += row_width;
		
	}
	
	/* Special case for the last line */
	max_ptr2 = pcurrent + row_width;
	while(pcurrent < max_ptr2) {
			r = abs(pnabove[1] - pabove[1]);
			g = abs(pnabove[2] - pabove[2]);
			b = abs(pnabove[3] - pabove[3]);
			delta1 = (r + r + b + g + g + g + g + g)>>3;
			r = abs(pncurrent[1] - pcurrent[1]);
			g = abs(pncurrent[2] - pcurrent[2]);
			b = abs(pncurrent[3] - pcurrent[3]);
			delta2 = (r + r + b + g + g + g + g + g)>>3;
			if(((delta1 + delta2) >> 1) >= threshold) {
				pcurrent[1] = pabove[1];
				pcurrent[2] = pabove[2];
				pcurrent[3] = pabove[3];
			}
			pabove += 4;
			pnabove += 4;
			pcurrent += 4;
			pncurrent += 4;
	}
}

/* ABGR */
__attribute__((noinline)) void std_deinterlace_4field_abgr(uint8_t* col1, uint8_t* col2, unsigned int threshold, unsigned int width, unsigned int height)
{
	uint8_t *pcurrent, *pabove, *pncurrent, *pnabove, *pbelow;
	const unsigned int row_width = width*4;
	const uint8_t* const max_ptr = col1 + (row_width * (height-1));
	const uint8_t *max_ptr2;
	unsigned int b, g, r;
	unsigned int delta1, delta2;

	pcurrent = col1 + row_width;
	pncurrent = col2 + row_width;
	pabove = col1;
	pnabove = col2;
	pbelow = col1 + (row_width*2);
	while(pcurrent < max_ptr)
	{
		max_ptr2 = pcurrent + row_width;
		while(pcurrent < max_ptr2) {
			b = abs(pnabove[1] - pabove[1]);
			g = abs(pnabove[2] - pabove[2]);
			r = abs(pnabove[3] - pabove[3]);
			delta1 = (r + r + b + g + g + g + g + g)>>3;
			b = abs(pncurrent[1] - pcurrent[1]);
			g = abs(pncurrent[2] - pcurrent[2]);
			r = abs(pncurrent[3] - pcurrent[3]);
			delta2 = (r + r + b + g + g + g + g + g)>>3;
			if(((delta1 + delta2) >> 1) >= threshold) {
				pcurrent[1] = (pabove[1] + pbelow[1]) >> 1;
				pcurrent[2] = (pabove[2] + pbelow[2]) >> 1;
				pcurrent[3] = (pabove[3] + pbelow[3]) >> 1;
			}
			pabove += 4;
			pnabove += 4;
			pcurrent += 4;
			pncurrent += 4;
			pbelow += 4;
		}
		pcurrent += row_width;
		pncurrent += row_width;
		pabove += row_width;
		pnabove += row_width;
		pbelow += row_width;
		
	}
	
	/* Special case for the last line */
	max_ptr2 = pcurrent + row_width;
	while(pcurrent < max_ptr2) {
			b = abs(pnabove[1] - pabove[1]);
			g = abs(pnabove[2] - pabove[2]);
			r = abs(pnabove[3] - pabove[3]);
			delta1 = (r + r + b + g + g + g + g + g)>>3;
			b = abs(pncurrent[1] - pcurrent[1]);
			g = abs(pncurrent[2] - pcurrent[2]);
			r = abs(pncurrent[3] - pcurrent[3]);
			delta2 = (r + r + b + g + g + g + g + g)>>3;
			if(((delta1 + delta2) >> 1) >= threshold) {
				pcurrent[1] = pabove[1];
				pcurrent[2] = pabove[2];
				pcurrent[3] = pabove[3];
			}
			pabove += 4;
			pnabove += 4;
			pcurrent += 4;
			pncurrent += 4;
	}
}

/* Grayscale SSSE3 */
#if defined(__i386__) || defined(__x86_64__)
__attribute__((noinline,__target__("ssse3")))
#endif
void ssse3_deinterlace_4field_gray8(uint8_t* col1, uint8_t* col2, unsigned int threshold, unsigned int width, unsigned int height) {

#if ((defined(__i386__) || defined(__x86_64__) || defined(ZM_KEEP_SSE)) && !defined(ZM_STRIP_SSE))
	union {
		uint32_t int32;
		uint8_t int8a[4];
	} threshold_mask;
	threshold_mask.int8a[0] = threshold;
	threshold_mask.int8a[1] = 0;
	threshold_mask.int8a[2] = threshold;
	threshold_mask.int8a[3] = 0;

	unsigned long row_width = width;
	uint8_t* max_ptr = col1 + (row_width * (height-2));
	uint8_t* max_ptr2 = col1 + row_width;

	__asm__ __volatile__ (
	/* Load the threshold */
	"mov %5, %%eax\n\t"
	"movd %%eax, %%xmm4\n\t"
	"pshufd $0x0, %%xmm4, %%xmm4\n\t"
	/* Zero the temporary register */
	"pxor %%xmm0, %%xmm0\n\t"

	"algo_ssse3_deinterlace_4field_gray8:\n\t"

	/* Load pabove into xmm1 and pnabove into xmm2 */
	"movdqa (%0), %%xmm1\n\t"
	"movdqa (%1), %%xmm2\n\t"
	"movdqa %%xmm1, %%xmm5\n\t" /* Keep backup of pabove in xmm5 */
	"pmaxub %%xmm2, %%xmm1\n\t"
	"pminub %%xmm5, %%xmm2\n\t"
	"psubb %%xmm2, %%xmm1\n\t"
	"movdqa %%xmm1, %%xmm7\n\t" /* Backup of delta2 in xmm7 for now */

	/* Next row */
	"add %4, %0\n\t"
	"add %4, %1\n\t"

	/* Load pcurrent into xmm1 and pncurrent into xmm2 */
	"movdqa (%0), %%xmm1\n\t"
	"movdqa (%1), %%xmm2\n\t"
	"movdqa %%xmm1, %%xmm6\n\t" /* Keep backup of pcurrent in xmm6 */
	"pmaxub %%xmm2, %%xmm1\n\t"
	"pminub %%xmm6, %%xmm2\n\t"
	"psubb %%xmm2, %%xmm1\n\t"

	"pavgb %%xmm7, %%xmm1\n\t"                         // Average the two deltas together
	"movdqa %%xmm1, %%xmm2\n\t"

	/* Do the comparison on words instead of bytes because we don't have unsigned comparison */
	"punpcklbw %%xmm0, %%xmm1\n\t"                     // Expand pixels 0-7 into words into xmm1
	"punpckhbw %%xmm0, %%xmm2\n\t"                     // Expand pixels 8-15 into words into xmm2
	"pcmpgtw %%xmm4, %%xmm1\n\t"                       // Compare average delta with threshold for pixels 0-7
	"pcmpgtw %%xmm4, %%xmm2\n\t"                       // Compare average delta with threshold for pixels 8-15
	"packsswb %%xmm2, %%xmm1\n\t"                      // Pack the comparison results into xmm1

	"movdqa (%0,%4), %%xmm2\n\t"                       // Load pbelow
	"pavgb %%xmm5, %%xmm2\n\t"                         // Average pabove and pbelow
	"pand %%xmm1, %%xmm2\n\t"                          // Filter out pixels in avg that shouldn't be copied
	"pandn %%xmm6, %%xmm1\n\t"                         // Filter out pixels in pcurrent that should be replaced

	"por %%xmm2, %%xmm1\n\t"                           // Put the new values in pcurrent
	"movntdq %%xmm1, (%0)\n\t"                         // Write pcurrent

	"sub %4, %0\n\t"                                   // Restore pcurrent to pabove
	"sub %4, %1\n\t"                                   // Restore pncurrent to pnabove

	/* Next pixels */
	"add $0x10, %0\n\t"                                // Add 16 to pcurrent
	"add $0x10, %1\n\t"                                // Add 16 to pncurrent

	/* Check if we reached the row end */
	"cmp %2, %0\n\t"
	"jb algo_ssse3_deinterlace_4field_gray8\n\t"       // Go for another iteration

	/* Next row */
	"add %4, %0\n\t"                                   // Add width to pcurrent
	"add %4, %1\n\t"                                   // Add width to pncurrent
	"mov %0, %2\n\t"
	"add %4, %2\n\t"                                   // Add width to max_ptr2

	/* Check if we reached the end */
	"cmp %3, %0\n\t"
	"jb algo_ssse3_deinterlace_4field_gray8\n\t"       // Go for another iteration

	/* Special case for the last line */
	/* Load pabove into xmm1 and pnabove into xmm2 */
	"movdqa (%0), %%xmm1\n\t"
	"movdqa (%1), %%xmm2\n\t"
	"movdqa %%xmm1, %%xmm5\n\t" /* Keep backup of pabove in xmm5 */
	"pmaxub %%xmm2, %%xmm1\n\t"
	"pminub %%xmm5, %%xmm2\n\t"
	"psubb %%xmm2, %%xmm1\n\t"
	"movdqa %%xmm1, %%xmm7\n\t" /* Backup of delta2 in xmm7 for now */

	/* Next row */
	"add %4, %0\n\t"
	"add %4, %1\n\t"

	/* Load pcurrent into xmm1 and pncurrent into xmm2 */
	"movdqa (%0), %%xmm1\n\t"
	"movdqa (%1), %%xmm2\n\t"
	"movdqa %%xmm1, %%xmm6\n\t" /* Keep backup of pcurrent in xmm6 */
	"pmaxub %%xmm2, %%xmm1\n\t"
	"pminub %%xmm6, %%xmm2\n\t"
	"psubb %%xmm2, %%xmm1\n\t"

	"pavgb %%xmm7, %%xmm1\n\t"                         // Average the two deltas together
	"movdqa %%xmm1, %%xmm2\n\t"

	/* Do the comparison on words instead of bytes because we don't have unsigned comparison */
	"punpcklbw %%xmm0, %%xmm1\n\t"                     // Expand pixels 0-7 into words into xmm1
	"punpckhbw %%xmm0, %%xmm2\n\t"                     // Expand pixels 8-15 into words into xmm2
	"pcmpgtw %%xmm4, %%xmm1\n\t"                       // Compare average delta with threshold for pixels 0-7
	"pcmpgtw %%xmm4, %%xmm2\n\t"                       // Compare average delta with threshold for pixels 8-15
	"packsswb %%xmm2, %%xmm1\n\t"                      // Pack the comparison results into xmm1

	"pand %%xmm1, %%xmm5\n\t"                          // Filter out pixels in pabove that shouldn't be copied
	"pandn %%xmm6, %%xmm1\n\t"                         // Filter out pixels in pcurrent that should be replaced

	"por %%xmm5, %%xmm1\n\t"                           // Put the new values in pcurrent
	"movntdq %%xmm1, (%0)\n\t"                         // Write pcurrent
	:
	: "r" (col1), "r" (col2), "r" (max_ptr2), "r" (max_ptr), "r" (row_width), "m" (threshold_mask.int32)
	: "%eax", "%xmm0", "%xmm1", "%xmm2", "%xmm3", "%xmm4", "%xmm5", "%xmm6", "%xmm7", "cc", "memory"
	);
#else
	Panic("SSE function called on a non x86\\x86-64 platform");
#endif
}

/* RGBA SSSE3 */
#if defined(__i386__) || defined(__x86_64__)
__attribute__((noinline,__target__("ssse3")))
#endif
void ssse3_deinterlace_4field_rgba(uint8_t* col1, uint8_t* col2, unsigned int threshold, unsigned int width, unsigned int height) {
#if ((defined(__i386__) || defined(__x86_64__) || defined(ZM_KEEP_SSE)) && !defined(ZM_STRIP_SSE))
	__attribute__((aligned(16))) static const uint8_t movemask2[16] = {1,1,1,1,1,0,0,2,9,9,9,9,9,8,8,10};

	const uint32_t threshold_val = threshold;

	unsigned long row_width = width*4;
	uint8_t* max_ptr = col1 + (row_width * (height-2));
	uint8_t* max_ptr2 = col1 + row_width;

	__asm__ __volatile__ (
	"mov $0x1F1F1F1F, %%eax\n\t"
	"movd %%eax, %%xmm4\n\t"
	"pshufd $0x0, %%xmm4, %%xmm4\n\t"
	"movdqa %6, %%xmm3\n\t"
	"mov %5, %%eax\n\t"
#if defined(__x86_64__)
	"movd %%eax, %%xmm8\n\t"
	"pshufd $0x0, %%xmm8, %%xmm8\n\t"
#endif
	/* Zero the temporary register */
	"pxor %%xmm0, %%xmm0\n\t"

	"algo_ssse3_deinterlace_4field_rgba:\n\t"

	/* Load pabove into xmm1 and pnabove into xmm2 */
	"movdqa (%0), %%xmm1\n\t"
	"movdqa (%1), %%xmm2\n\t"
	"movdqa %%xmm1, %%xmm5\n\t" /* Keep backup of pabove in xmm5 */
	"psrlq $0x3, %%xmm1\n\t"
	"psrlq $0x3, %%xmm2\n\t"
	"pand %%xmm4, %%xmm1\n\t"
	"pand %%xmm4, %%xmm2\n\t"
	"psubb %%xmm2, %%xmm1\n\t"
	"pabsb %%xmm1, %%xmm2\n\t"
	"movdqa %%xmm2, %%xmm1\n\t"
	"punpckldq %%xmm1, %%xmm1\n\t"
	"pshufb %%xmm3, %%xmm1\n\t"
	"psadbw %%xmm0, %%xmm1\n\t"
	"punpckhdq %%xmm2, %%xmm2\n\t"
	"pshufb %%xmm3, %%xmm2\n\t"
	"psadbw %%xmm0, %%xmm2\n\t"
	"packuswb %%xmm2, %%xmm1\n\t"
	"movdqa %%xmm1, %%xmm7\n\t" /* Backup of delta2 in xmm7 for now */

	/* Next row */
	"add %4, %0\n\t"
	"add %4, %1\n\t"

	/* Load pcurrent into xmm1 and pncurrent into xmm2 */
	"movdqa (%0), %%xmm1\n\t"
	"movdqa (%1), %%xmm2\n\t"
	"movdqa %%xmm1, %%xmm6\n\t" /* Keep backup of pcurrent in xmm6 */
	"psrlq $0x3, %%xmm1\n\t"
	"psrlq $0x3, %%xmm2\n\t"
	"pand %%xmm4, %%xmm1\n\t"
	"pand %%xmm4, %%xmm2\n\t"
	"psubb %%xmm2, %%xmm1\n\t"
	"pabsb %%xmm1, %%xmm2\n\t"
	"movdqa %%xmm2, %%xmm1\n\t"
	"punpckldq %%xmm1, %%xmm1\n\t"
	"pshufb %%xmm3, %%xmm1\n\t"
	"psadbw %%xmm0, %%xmm1\n\t"
	"punpckhdq %%xmm2, %%xmm2\n\t"
	"pshufb %%xmm3, %%xmm2\n\t"
	"psadbw %%xmm0, %%xmm2\n\t"
	"packuswb %%xmm2, %%xmm1\n\t"

	"pavgb %%xmm7, %%xmm1\n\t"                         // Average the two deltas together

#if defined(__x86_64__)
	"pcmpgtd %%xmm8, %%xmm1\n\t"                       // Compare average delta with the threshold
#else
	"movd %%eax, %%xmm7\n\t"                           // Setup the threshold
	"pshufd $0x0, %%xmm7, %%xmm7\n\t"

	"pcmpgtd %%xmm7, %%xmm1\n\t"                       // Compare average delta with the threshold
#endif
	"movdqa (%0,%4), %%xmm2\n\t"                       // Load pbelow
	"pavgb %%xmm5, %%xmm2\n\t"                         // Average pabove and pbelow
	"pand %%xmm1, %%xmm2\n\t"                          // Filter out pixels in avg that shouldn't be copied
	"pandn %%xmm6, %%xmm1\n\t"                         // Filter out pixels in pcurrent that should be replaced

	"por %%xmm2, %%xmm1\n\t"                           // Put the new values in pcurrent
	"movntdq %%xmm1, (%0)\n\t"                         // Write pcurrent

	"sub %4, %0\n\t"                                   // Restore pcurrent to pabove
	"sub %4, %1\n\t"                                   // Restore pncurrent to pnabove

	/* Next pixels */
	"add $0x10, %0\n\t"                                // Add 16 to pcurrent
	"add $0x10, %1\n\t"                                // Add 16 to pncurrent

	/* Check if we reached the row end */
	"cmp %2, %0\n\t"
	"jb algo_ssse3_deinterlace_4field_rgba\n\t"        // Go for another iteration

	/* Next row */
	"add %4, %0\n\t"                                   // Add width to pcurrent
	"add %4, %1\n\t"                                   // Add width to pncurrent
	"mov %0, %2\n\t"
	"add %4, %2\n\t"                                   // Add width to max_ptr2

	/* Check if we reached the end */
	"cmp %3, %0\n\t"
	"jb algo_ssse3_deinterlace_4field_rgba\n\t"        // Go for another iteration

	/* Special case for the last line */
	/* Load pabove into xmm1 and pnabove into xmm2 */
	"movdqa (%0), %%xmm1\n\t"
	"movdqa (%1), %%xmm2\n\t"
	"movdqa %%xmm1, %%xmm5\n\t" /* Keep backup of pabove in xmm5 */
	"psrlq $0x3, %%xmm1\n\t"
	"psrlq $0x3, %%xmm2\n\t"
	"pand %%xmm4, %%xmm1\n\t"
	"pand %%xmm4, %%xmm2\n\t"
	"psubb %%xmm2, %%xmm1\n\t"
	"pabsb %%xmm1, %%xmm2\n\t"
	"movdqa %%xmm2, %%xmm1\n\t"
	"punpckldq %%xmm1, %%xmm1\n\t"
	"pshufb %%xmm3, %%xmm1\n\t"
	"psadbw %%xmm0, %%xmm1\n\t"
	"punpckhdq %%xmm2, %%xmm2\n\t"
	"pshufb %%xmm3, %%xmm2\n\t"
	"psadbw %%xmm0, %%xmm2\n\t"
	"packuswb %%xmm2, %%xmm1\n\t"
	"movdqa %%xmm1, %%xmm7\n\t" /* Backup of delta2 in xmm7 for now */

	/* Next row */
	"add %4, %0\n\t"
	"add %4, %1\n\t"

	/* Load pcurrent into xmm1 and pncurrent into xmm2 */
	"movdqa (%0), %%xmm1\n\t"
	"movdqa (%1), %%xmm2\n\t"
	"movdqa %%xmm1, %%xmm6\n\t" /* Keep backup of pcurrent in xmm6 */
	"psrlq $0x3, %%xmm1\n\t"
	"psrlq $0x3, %%xmm2\n\t"
	"pand %%xmm4, %%xmm1\n\t"
	"pand %%xmm4, %%xmm2\n\t"
	"psubb %%xmm2, %%xmm1\n\t"
	"pabsb %%xmm1, %%xmm2\n\t"
	"movdqa %%xmm2, %%xmm1\n\t"
	"punpckldq %%xmm1, %%xmm1\n\t"
	"pshufb %%xmm3, %%xmm1\n\t"
	"psadbw %%xmm0, %%xmm1\n\t"
	"punpckhdq %%xmm2, %%xmm2\n\t"
	"pshufb %%xmm3, %%xmm2\n\t"
	"psadbw %%xmm0, %%xmm2\n\t"
	"packuswb %%xmm2, %%xmm1\n\t"

	"pavgb %%xmm7, %%xmm1\n\t"                         // Average the two deltas together

#if defined(__x86_64__)
	"pcmpgtd %%xmm8, %%xmm1\n\t"                       // Compare average delta with the threshold
#else
	"movd %%eax, %%xmm7\n\t"                           // Setup the threshold
	"pshufd $0x0, %%xmm7, %%xmm7\n\t"

	"pcmpgtd %%xmm7, %%xmm1\n\t"                       // Compare average delta with the threshold
#endif
	"pand %%xmm1, %%xmm5\n\t"                          // Filter out pixels in pabove that shouldn't be copied
	"pandn %%xmm6, %%xmm1\n\t"                         // Filter out pixels in pcurrent that should be replaced

	"por %%xmm5, %%xmm1\n\t"                           // Put the new values in pcurrent
	"movntdq %%xmm1, (%0)\n\t"                         // Write pcurrent
	:
	: "r" (col1), "r" (col2), "r" (max_ptr2), "r" (max_ptr), "r" (row_width), "m" (threshold_val), "m" (*movemask2)
#if defined(__x86_64__)
	: "%eax", "%xmm0", "%xmm1", "%xmm2", "%xmm3", "%xmm4", "%xmm5", "%xmm6", "%xmm7", "%xmm8", "cc", "memory"
#else
	: "%eax", "%xmm0", "%xmm1", "%xmm2", "%xmm3", "%xmm4", "%xmm5", "%xmm6", "%xmm7", "cc", "memory"
#endif
	);
#else
	Panic("SSE function called on a non x86\\x86-64 platform");
#endif
}

/* BGRA SSSE3 */
#if defined(__i386__) || defined(__x86_64__)
__attribute__((noinline,__target__("ssse3")))
#endif
void ssse3_deinterlace_4field_bgra(uint8_t* col1, uint8_t* col2, unsigned int threshold, unsigned int width, unsigned int height) {
#if ((defined(__i386__) || defined(__x86_64__) || defined(ZM_KEEP_SSE)) && !defined(ZM_STRIP_SSE))
	__attribute__((aligned(16))) static const uint8_t movemask2[16] = {1,1,1,1,1,2,2,0,9,9,9,9,9,10,10,8};

	const uint32_t threshold_val = threshold;

	unsigned long row_width = width*4;
	uint8_t* max_ptr = col1 + (row_width * (height-2));
	uint8_t* max_ptr2 = col1 + row_width;

	__asm__ __volatile__ (
	"mov $0x1F1F1F1F, %%eax\n\t"
	"movd %%eax, %%xmm4\n\t"
	"pshufd $0x0, %%xmm4, %%xmm4\n\t"
	"movdqa %6, %%xmm3\n\t"
	"mov %5, %%eax\n\t"
#if defined(__x86_64__)
	"movd %%eax, %%xmm8\n\t"
	"pshufd $0x0, %%xmm8, %%xmm8\n\t"
#endif
	/* Zero the temporary register */
	"pxor %%xmm0, %%xmm0\n\t"

	"algo_ssse3_deinterlace_4field_bgra:\n\t"

	/* Load pabove into xmm1 and pnabove into xmm2 */
	"movdqa (%0), %%xmm1\n\t"
	"movdqa (%1), %%xmm2\n\t"
	"movdqa %%xmm1, %%xmm5\n\t" /* Keep backup of pabove in xmm5 */
	"psrlq $0x3, %%xmm1\n\t"
	"psrlq $0x3, %%xmm2\n\t"
	"pand %%xmm4, %%xmm1\n\t"
	"pand %%xmm4, %%xmm2\n\t"
	"psubb %%xmm2, %%xmm1\n\t"
	"pabsb %%xmm1, %%xmm2\n\t"
	"movdqa %%xmm2, %%xmm1\n\t"
	"punpckldq %%xmm1, %%xmm1\n\t"
	"pshufb %%xmm3, %%xmm1\n\t"
	"psadbw %%xmm0, %%xmm1\n\t"
	"punpckhdq %%xmm2, %%xmm2\n\t"
	"pshufb %%xmm3, %%xmm2\n\t"
	"psadbw %%xmm0, %%xmm2\n\t"
	"packuswb %%xmm2, %%xmm1\n\t"
	"movdqa %%xmm1, %%xmm7\n\t" /* Backup of delta2 in xmm7 for now */

	/* Next row */
	"add %4, %0\n\t"
	"add %4, %1\n\t"

	/* Load pcurrent into xmm1 and pncurrent into xmm2 */
	"movdqa (%0), %%xmm1\n\t"
	"movdqa (%1), %%xmm2\n\t"
	"movdqa %%xmm1, %%xmm6\n\t" /* Keep backup of pcurrent in xmm6 */
	"psrlq $0x3, %%xmm1\n\t"
	"psrlq $0x3, %%xmm2\n\t"
	"pand %%xmm4, %%xmm1\n\t"
	"pand %%xmm4, %%xmm2\n\t"
	"psubb %%xmm2, %%xmm1\n\t"
	"pabsb %%xmm1, %%xmm2\n\t"
	"movdqa %%xmm2, %%xmm1\n\t"
	"punpckldq %%xmm1, %%xmm1\n\t"
	"pshufb %%xmm3, %%xmm1\n\t"
	"psadbw %%xmm0, %%xmm1\n\t"
	"punpckhdq %%xmm2, %%xmm2\n\t"
	"pshufb %%xmm3, %%xmm2\n\t"
	"psadbw %%xmm0, %%xmm2\n\t"
	"packuswb %%xmm2, %%xmm1\n\t"

	"pavgb %%xmm7, %%xmm1\n\t"                         // Average the two deltas together

#if defined(__x86_64__)
	"pcmpgtd %%xmm8, %%xmm1\n\t"                       // Compare average delta with the threshold
#else
	"movd %%eax, %%xmm7\n\t"                           // Setup the threshold
	"pshufd $0x0, %%xmm7, %%xmm7\n\t"

	"pcmpgtd %%xmm7, %%xmm1\n\t"                       // Compare average delta with the threshold
#endif
	"movdqa (%0,%4), %%xmm2\n\t"                       // Load pbelow
	"pavgb %%xmm5, %%xmm2\n\t"                         // Average pabove and pbelow
	"pand %%xmm1, %%xmm2\n\t"                          // Filter out pixels in avg that shouldn't be copied
	"pandn %%xmm6, %%xmm1\n\t"                         // Filter out pixels in pcurrent that should be replaced

	"por %%xmm2, %%xmm1\n\t"                           // Put the new values in pcurrent
	"movntdq %%xmm1, (%0)\n\t"                         // Write pcurrent

	"sub %4, %0\n\t"                                   // Restore pcurrent to pabove
	"sub %4, %1\n\t"                                   // Restore pncurrent to pnabove

	/* Next pixels */
	"add $0x10, %0\n\t"                                // Add 16 to pcurrent
	"add $0x10, %1\n\t"                                // Add 16 to pncurrent

	/* Check if we reached the row end */
	"cmp %2, %0\n\t"
	"jb algo_ssse3_deinterlace_4field_bgra\n\t"        // Go for another iteration

	/* Next row */
	"add %4, %0\n\t"                                   // Add width to pcurrent
	"add %4, %1\n\t"                                   // Add width to pncurrent
	"mov %0, %2\n\t"
	"add %4, %2\n\t"                                   // Add width to max_ptr2

	/* Check if we reached the end */
	"cmp %3, %0\n\t"
	"jb algo_ssse3_deinterlace_4field_bgra\n\t"        // Go for another iteration

	/* Special case for the last line */
	/* Load pabove into xmm1 and pnabove into xmm2 */
	"movdqa (%0), %%xmm1\n\t"
	"movdqa (%1), %%xmm2\n\t"
	"movdqa %%xmm1, %%xmm5\n\t" /* Keep backup of pabove in xmm5 */
	"psrlq $0x3, %%xmm1\n\t"
	"psrlq $0x3, %%xmm2\n\t"
	"pand %%xmm4, %%xmm1\n\t"
	"pand %%xmm4, %%xmm2\n\t"
	"psubb %%xmm2, %%xmm1\n\t"
	"pabsb %%xmm1, %%xmm2\n\t"
	"movdqa %%xmm2, %%xmm1\n\t"
	"punpckldq %%xmm1, %%xmm1\n\t"
	"pshufb %%xmm3, %%xmm1\n\t"
	"psadbw %%xmm0, %%xmm1\n\t"
	"punpckhdq %%xmm2, %%xmm2\n\t"
	"pshufb %%xmm3, %%xmm2\n\t"
	"psadbw %%xmm0, %%xmm2\n\t"
	"packuswb %%xmm2, %%xmm1\n\t"
	"movdqa %%xmm1, %%xmm7\n\t" /* Backup of delta2 in xmm7 for now */

	/* Next row */
	"add %4, %0\n\t"
	"add %4, %1\n\t"

	/* Load pcurrent into xmm1 and pncurrent into xmm2 */
	"movdqa (%0), %%xmm1\n\t"
	"movdqa (%1), %%xmm2\n\t"
	"movdqa %%xmm1, %%xmm6\n\t" /* Keep backup of pcurrent in xmm6 */
	"psrlq $0x3, %%xmm1\n\t"
	"psrlq $0x3, %%xmm2\n\t"
	"pand %%xmm4, %%xmm1\n\t"
	"pand %%xmm4, %%xmm2\n\t"
	"psubb %%xmm2, %%xmm1\n\t"
	"pabsb %%xmm1, %%xmm2\n\t"
	"movdqa %%xmm2, %%xmm1\n\t"
	"punpckldq %%xmm1, %%xmm1\n\t"
	"pshufb %%xmm3, %%xmm1\n\t"
	"psadbw %%xmm0, %%xmm1\n\t"
	"punpckhdq %%xmm2, %%xmm2\n\t"
	"pshufb %%xmm3, %%xmm2\n\t"
	"psadbw %%xmm0, %%xmm2\n\t"
	"packuswb %%xmm2, %%xmm1\n\t"

	"pavgb %%xmm7, %%xmm1\n\t"                         // Average the two deltas together

#if defined(__x86_64__)
	"pcmpgtd %%xmm8, %%xmm1\n\t"                       // Compare average delta with the threshold
#else
	"movd %%eax, %%xmm7\n\t"                           // Setup the threshold
	"pshufd $0x0, %%xmm7, %%xmm7\n\t"

	"pcmpgtd %%xmm7, %%xmm1\n\t"                       // Compare average delta with the threshold
#endif
	"pand %%xmm1, %%xmm5\n\t"                          // Filter out pixels in pabove that shouldn't be copied
	"pandn %%xmm6, %%xmm1\n\t"                         // Filter out pixels in pcurrent that should be replaced

	"por %%xmm5, %%xmm1\n\t"                           // Put the new values in pcurrent
	"movntdq %%xmm1, (%0)\n\t"                         // Write pcurrent
	:
	: "r" (col1), "r" (col2), "r" (max_ptr2), "r" (max_ptr), "r" (row_width), "m" (threshold_val), "m" (*movemask2)
#if defined(__x86_64__)
	: "%eax", "%xmm0", "%xmm1", "%xmm2", "%xmm3", "%xmm4", "%xmm5", "%xmm6", "%xmm7", "%xmm8", "cc", "memory"
#else
	: "%eax", "%xmm0", "%xmm1", "%xmm2", "%xmm3", "%xmm4", "%xmm5", "%xmm6", "%xmm7", "cc", "memory"
#endif
	);
#else
	Panic("SSE function called on a non x86\\x86-64 platform");
#endif
}

/* ARGB SSSE3 */
#if defined(__i386__) || defined(__x86_64__)
__attribute__((noinline,__target__("ssse3")))
#endif
void ssse3_deinterlace_4field_argb(uint8_t* col1, uint8_t* col2, unsigned int threshold, unsigned int width, unsigned int height) {
#if ((defined(__i386__) || defined(__x86_64__) || defined(ZM_KEEP_SSE)) && !defined(ZM_STRIP_SSE))
	__attribute__((aligned(16))) static const uint8_t movemask2[16] = {2,2,2,2,2,1,1,3,10,10,10,10,10,9,9,11};

	const uint32_t threshold_val = threshold;

	unsigned long row_width = width*4;
	uint8_t* max_ptr = col1 + (row_width * (height-2));
	uint8_t* max_ptr2 = col1 + row_width;

	__asm__ __volatile__ (
	"mov $0x1F1F1F1F, %%eax\n\t"
	"movd %%eax, %%xmm4\n\t"
	"pshufd $0x0, %%xmm4, %%xmm4\n\t"
	"movdqa %6, %%xmm3\n\t"
	"mov %5, %%eax\n\t"
#if defined(__x86_64__)
	"movd %%eax, %%xmm8\n\t"
	"pshufd $0x0, %%xmm8, %%xmm8\n\t"
#endif
	/* Zero the temporary register */
	"pxor %%xmm0, %%xmm0\n\t"

	"algo_ssse3_deinterlace_4field_argb:\n\t"

	/* Load pabove into xmm1 and pnabove into xmm2 */
	"movdqa (%0), %%xmm1\n\t"
	"movdqa (%1), %%xmm2\n\t"
	"movdqa %%xmm1, %%xmm5\n\t" /* Keep backup of pabove in xmm5 */
	"psrlq $0x3, %%xmm1\n\t"
	"psrlq $0x3, %%xmm2\n\t"
	"pand %%xmm4, %%xmm1\n\t"
	"pand %%xmm4, %%xmm2\n\t"
	"psubb %%xmm2, %%xmm1\n\t"
	"pabsb %%xmm1, %%xmm2\n\t"
	"movdqa %%xmm2, %%xmm1\n\t"
	"punpckldq %%xmm1, %%xmm1\n\t"
	"pshufb %%xmm3, %%xmm1\n\t"
	"psadbw %%xmm0, %%xmm1\n\t"
	"punpckhdq %%xmm2, %%xmm2\n\t"
	"pshufb %%xmm3, %%xmm2\n\t"
	"psadbw %%xmm0, %%xmm2\n\t"
	"packuswb %%xmm2, %%xmm1\n\t"
	"movdqa %%xmm1, %%xmm7\n\t" /* Backup of delta2 in xmm7 for now */

	/* Next row */
	"add %4, %0\n\t"
	"add %4, %1\n\t"

	/* Load pcurrent into xmm1 and pncurrent into xmm2 */
	"movdqa (%0), %%xmm1\n\t"
	"movdqa (%1), %%xmm2\n\t"
	"movdqa %%xmm1, %%xmm6\n\t" /* Keep backup of pcurrent in xmm6 */
	"psrlq $0x3, %%xmm1\n\t"
	"psrlq $0x3, %%xmm2\n\t"
	"pand %%xmm4, %%xmm1\n\t"
	"pand %%xmm4, %%xmm2\n\t"
	"psubb %%xmm2, %%xmm1\n\t"
	"pabsb %%xmm1, %%xmm2\n\t"
	"movdqa %%xmm2, %%xmm1\n\t"
	"punpckldq %%xmm1, %%xmm1\n\t"
	"pshufb %%xmm3, %%xmm1\n\t"
	"psadbw %%xmm0, %%xmm1\n\t"
	"punpckhdq %%xmm2, %%xmm2\n\t"
	"pshufb %%xmm3, %%xmm2\n\t"
	"psadbw %%xmm0, %%xmm2\n\t"
	"packuswb %%xmm2, %%xmm1\n\t"

	"pavgb %%xmm7, %%xmm1\n\t"                         // Average the two deltas together

#if defined(__x86_64__)
	"pcmpgtd %%xmm8, %%xmm1\n\t"                       // Compare average delta with the threshold
#else
	"movd %%eax, %%xmm7\n\t"                           // Setup the threshold
	"pshufd $0x0, %%xmm7, %%xmm7\n\t"

	"pcmpgtd %%xmm7, %%xmm1\n\t"                       // Compare average delta with the threshold
#endif
	"movdqa (%0,%4), %%xmm2\n\t"                       // Load pbelow
	"pavgb %%xmm5, %%xmm2\n\t"                         // Average pabove and pbelow
	"pand %%xmm1, %%xmm2\n\t"                          // Filter out pixels in avg that shouldn't be copied
	"pandn %%xmm6, %%xmm1\n\t"                         // Filter out pixels in pcurrent that should be replaced

	"por %%xmm2, %%xmm1\n\t"                           // Put the new values in pcurrent
	"movntdq %%xmm1, (%0)\n\t"                         // Write pcurrent

	"sub %4, %0\n\t"                                   // Restore pcurrent to pabove
	"sub %4, %1\n\t"                                   // Restore pncurrent to pnabove

	/* Next pixels */
	"add $0x10, %0\n\t"                                // Add 16 to pcurrent
	"add $0x10, %1\n\t"                                // Add 16 to pncurrent

	/* Check if we reached the row end */
	"cmp %2, %0\n\t"
	"jb algo_ssse3_deinterlace_4field_argb\n\t"        // Go for another iteration

	/* Next row */
	"add %4, %0\n\t"                                   // Add width to pcurrent
	"add %4, %1\n\t"                                   // Add width to pncurrent
	"mov %0, %2\n\t"
	"add %4, %2\n\t"                                   // Add width to max_ptr2

	/* Check if we reached the end */
	"cmp %3, %0\n\t"
	"jb algo_ssse3_deinterlace_4field_argb\n\t"        // Go for another iteration

	/* Special case for the last line */
	/* Load pabove into xmm1 and pnabove into xmm2 */
	"movdqa (%0), %%xmm1\n\t"
	"movdqa (%1), %%xmm2\n\t"
	"movdqa %%xmm1, %%xmm5\n\t" /* Keep backup of pabove in xmm5 */
	"psrlq $0x3, %%xmm1\n\t"
	"psrlq $0x3, %%xmm2\n\t"
	"pand %%xmm4, %%xmm1\n\t"
	"pand %%xmm4, %%xmm2\n\t"
	"psubb %%xmm2, %%xmm1\n\t"
	"pabsb %%xmm1, %%xmm2\n\t"
	"movdqa %%xmm2, %%xmm1\n\t"
	"punpckldq %%xmm1, %%xmm1\n\t"
	"pshufb %%xmm3, %%xmm1\n\t"
	"psadbw %%xmm0, %%xmm1\n\t"
	"punpckhdq %%xmm2, %%xmm2\n\t"
	"pshufb %%xmm3, %%xmm2\n\t"
	"psadbw %%xmm0, %%xmm2\n\t"
	"packuswb %%xmm2, %%xmm1\n\t"
	"movdqa %%xmm1, %%xmm7\n\t" /* Backup of delta2 in xmm7 for now */

	/* Next row */
	"add %4, %0\n\t"
	"add %4, %1\n\t"

	/* Load pcurrent into xmm1 and pncurrent into xmm2 */
	"movdqa (%0), %%xmm1\n\t"
	"movdqa (%1), %%xmm2\n\t"
	"movdqa %%xmm1, %%xmm6\n\t" /* Keep backup of pcurrent in xmm6 */
	"psrlq $0x3, %%xmm1\n\t"
	"psrlq $0x3, %%xmm2\n\t"
	"pand %%xmm4, %%xmm1\n\t"
	"pand %%xmm4, %%xmm2\n\t"
	"psubb %%xmm2, %%xmm1\n\t"
	"pabsb %%xmm1, %%xmm2\n\t"
	"movdqa %%xmm2, %%xmm1\n\t"
	"punpckldq %%xmm1, %%xmm1\n\t"
	"pshufb %%xmm3, %%xmm1\n\t"
	"psadbw %%xmm0, %%xmm1\n\t"
	"punpckhdq %%xmm2, %%xmm2\n\t"
	"pshufb %%xmm3, %%xmm2\n\t"
	"psadbw %%xmm0, %%xmm2\n\t"
	"packuswb %%xmm2, %%xmm1\n\t"

	"pavgb %%xmm7, %%xmm1\n\t"                         // Average the two deltas together

#if defined(__x86_64__)
	"pcmpgtd %%xmm8, %%xmm1\n\t"                       // Compare average delta with the threshold
#else
	"movd %%eax, %%xmm7\n\t"                           // Setup the threshold
	"pshufd $0x0, %%xmm7, %%xmm7\n\t"

	"pcmpgtd %%xmm7, %%xmm1\n\t"                       // Compare average delta with the threshold
#endif
	"pand %%xmm1, %%xmm5\n\t"                          // Filter out pixels in pabove that shouldn't be copied
	"pandn %%xmm6, %%xmm1\n\t"                         // Filter out pixels in pcurrent that should be replaced

	"por %%xmm5, %%xmm1\n\t"                           // Put the new values in pcurrent
	"movntdq %%xmm1, (%0)\n\t"                         // Write pcurrent
	:
	: "r" (col1), "r" (col2), "r" (max_ptr2), "r" (max_ptr), "r" (row_width), "m" (threshold_val), "m" (*movemask2)
#if defined(__x86_64__)
	: "%eax", "%xmm0", "%xmm1", "%xmm2", "%xmm3", "%xmm4", "%xmm5", "%xmm6", "%xmm7", "%xmm8", "cc", "memory"
#else
	: "%eax", "%xmm0", "%xmm1", "%xmm2", "%xmm3", "%xmm4", "%xmm5", "%xmm6", "%xmm7", "cc", "memory"
#endif
	);
#else
	Panic("SSE function called on a non x86\\x86-64 platform");
#endif
}

/* ABGR SSSE3 */
#if defined(__i386__) || defined(__x86_64__)
__attribute__((noinline,__target__("ssse3")))
#endif
void ssse3_deinterlace_4field_abgr(uint8_t* col1, uint8_t* col2, unsigned int threshold, unsigned int width, unsigned int height) {
#if ((defined(__i386__) || defined(__x86_64__) || defined(ZM_KEEP_SSE)) && !defined(ZM_STRIP_SSE))
	__attribute__((aligned(16))) static const uint8_t movemask2[16] = {2,2,2,2,2,3,3,1,10,10,10,10,10,11,11,9};

	const uint32_t threshold_val = threshold;

	unsigned long row_width = width*4;
	uint8_t* max_ptr = col1 + (row_width * (height-2));
	uint8_t* max_ptr2 = col1 + row_width;

	__asm__ __volatile__ (
	"mov $0x1F1F1F1F, %%eax\n\t"
	"movd %%eax, %%xmm4\n\t"
	"pshufd $0x0, %%xmm4, %%xmm4\n\t"
	"movdqa %6, %%xmm3\n\t"
	"mov %5, %%eax\n\t"
#if defined(__x86_64__)
	"movd %%eax, %%xmm8\n\t"
	"pshufd $0x0, %%xmm8, %%xmm8\n\t"
#endif
	/* Zero the temporary register */
	"pxor %%xmm0, %%xmm0\n\t"

	"algo_ssse3_deinterlace_4field_abgr:\n\t"

	/* Load pabove into xmm1 and pnabove into xmm2 */
	"movdqa (%0), %%xmm1\n\t"
	"movdqa (%1), %%xmm2\n\t"
	"movdqa %%xmm1, %%xmm5\n\t" /* Keep backup of pabove in xmm5 */
	"psrlq $0x3, %%xmm1\n\t"
	"psrlq $0x3, %%xmm2\n\t"
	"pand %%xmm4, %%xmm1\n\t"
	"pand %%xmm4, %%xmm2\n\t"
	"psubb %%xmm2, %%xmm1\n\t"
	"pabsb %%xmm1, %%xmm2\n\t"
	"movdqa %%xmm2, %%xmm1\n\t"
	"punpckldq %%xmm1, %%xmm1\n\t"
	"pshufb %%xmm3, %%xmm1\n\t"
	"psadbw %%xmm0, %%xmm1\n\t"
	"punpckhdq %%xmm2, %%xmm2\n\t"
	"pshufb %%xmm3, %%xmm2\n\t"
	"psadbw %%xmm0, %%xmm2\n\t"
	"packuswb %%xmm2, %%xmm1\n\t"
	"movdqa %%xmm1, %%xmm7\n\t" /* Backup of delta2 in xmm7 for now */

	/* Next row */
	"add %4, %0\n\t"
	"add %4, %1\n\t"

	/* Load pcurrent into xmm1 and pncurrent into xmm2 */
	"movdqa (%0), %%xmm1\n\t"
	"movdqa (%1), %%xmm2\n\t"
	"movdqa %%xmm1, %%xmm6\n\t" /* Keep backup of pcurrent in xmm6 */
	"psrlq $0x3, %%xmm1\n\t"
	"psrlq $0x3, %%xmm2\n\t"
	"pand %%xmm4, %%xmm1\n\t"
	"pand %%xmm4, %%xmm2\n\t"
	"psubb %%xmm2, %%xmm1\n\t"
	"pabsb %%xmm1, %%xmm2\n\t"
	"movdqa %%xmm2, %%xmm1\n\t"
	"punpckldq %%xmm1, %%xmm1\n\t"
	"pshufb %%xmm3, %%xmm1\n\t"
	"psadbw %%xmm0, %%xmm1\n\t"
	"punpckhdq %%xmm2, %%xmm2\n\t"
	"pshufb %%xmm3, %%xmm2\n\t"
	"psadbw %%xmm0, %%xmm2\n\t"
	"packuswb %%xmm2, %%xmm1\n\t"

	"pavgb %%xmm7, %%xmm1\n\t"                         // Average the two deltas together

#if defined(__x86_64__)
	"pcmpgtd %%xmm8, %%xmm1\n\t"                       // Compare average delta with the threshold
#else
	"movd %%eax, %%xmm7\n\t"                           // Setup the threshold
	"pshufd $0x0, %%xmm7, %%xmm7\n\t"

	"pcmpgtd %%xmm7, %%xmm1\n\t"                       // Compare average delta with the threshold
#endif
	"movdqa (%0,%4), %%xmm2\n\t"                       // Load pbelow
	"pavgb %%xmm5, %%xmm2\n\t"                         // Average pabove and pbelow
	"pand %%xmm1, %%xmm2\n\t"                          // Filter out pixels in avg that shouldn't be copied
	"pandn %%xmm6, %%xmm1\n\t"                         // Filter out pixels in pcurrent that should be replaced

	"por %%xmm2, %%xmm1\n\t"                           // Put the new values in pcurrent
	"movntdq %%xmm1, (%0)\n\t"                         // Write pcurrent

	"sub %4, %0\n\t"                                   // Restore pcurrent to pabove
	"sub %4, %1\n\t"                                   // Restore pncurrent to pnabove

	/* Next pixels */
	"add $0x10, %0\n\t"                                // Add 16 to pcurrent
	"add $0x10, %1\n\t"                                // Add 16 to pncurrent

	/* Check if we reached the row end */
	"cmp %2, %0\n\t"
	"jb algo_ssse3_deinterlace_4field_abgr\n\t"        // Go for another iteration

	/* Next row */
	"add %4, %0\n\t"                                   // Add width to pcurrent
	"add %4, %1\n\t"                                   // Add width to pncurrent
	"mov %0, %2\n\t"
	"add %4, %2\n\t"                                   // Add width to max_ptr2

	/* Check if we reached the end */
	"cmp %3, %0\n\t"
	"jb algo_ssse3_deinterlace_4field_abgr\n\t"        // Go for another iteration

	/* Special case for the last line */
	/* Load pabove into xmm1 and pnabove into xmm2 */
	"movdqa (%0), %%xmm1\n\t"
	"movdqa (%1), %%xmm2\n\t"
	"movdqa %%xmm1, %%xmm5\n\t" /* Keep backup of pabove in xmm5 */
	"psrlq $0x3, %%xmm1\n\t"
	"psrlq $0x3, %%xmm2\n\t"
	"pand %%xmm4, %%xmm1\n\t"
	"pand %%xmm4, %%xmm2\n\t"
	"psubb %%xmm2, %%xmm1\n\t"
	"pabsb %%xmm1, %%xmm2\n\t"
	"movdqa %%xmm2, %%xmm1\n\t"
	"punpckldq %%xmm1, %%xmm1\n\t"
	"pshufb %%xmm3, %%xmm1\n\t"
	"psadbw %%xmm0, %%xmm1\n\t"
	"punpckhdq %%xmm2, %%xmm2\n\t"
	"pshufb %%xmm3, %%xmm2\n\t"
	"psadbw %%xmm0, %%xmm2\n\t"
	"packuswb %%xmm2, %%xmm1\n\t"
	"movdqa %%xmm1, %%xmm7\n\t" /* Backup of delta2 in xmm7 for now */

	/* Next row */
	"add %4, %0\n\t"
	"add %4, %1\n\t"

	/* Load pcurrent into xmm1 and pncurrent into xmm2 */
	"movdqa (%0), %%xmm1\n\t"
	"movdqa (%1), %%xmm2\n\t"
	"movdqa %%xmm1, %%xmm6\n\t" /* Keep backup of pcurrent in xmm6 */
	"psrlq $0x3, %%xmm1\n\t"
	"psrlq $0x3, %%xmm2\n\t"
	"pand %%xmm4, %%xmm1\n\t"
	"pand %%xmm4, %%xmm2\n\t"
	"psubb %%xmm2, %%xmm1\n\t"
	"pabsb %%xmm1, %%xmm2\n\t"
	"movdqa %%xmm2, %%xmm1\n\t"
	"punpckldq %%xmm1, %%xmm1\n\t"
	"pshufb %%xmm3, %%xmm1\n\t"
	"psadbw %%xmm0, %%xmm1\n\t"
	"punpckhdq %%xmm2, %%xmm2\n\t"
	"pshufb %%xmm3, %%xmm2\n\t"
	"psadbw %%xmm0, %%xmm2\n\t"
	"packuswb %%xmm2, %%xmm1\n\t"

	"pavgb %%xmm7, %%xmm1\n\t"                         // Average the two deltas together

#if defined(__x86_64__)
	"pcmpgtd %%xmm8, %%xmm1\n\t"                       // Compare average delta with the threshold
#else
	"movd %%eax, %%xmm7\n\t"                           // Setup the threshold
	"pshufd $0x0, %%xmm7, %%xmm7\n\t"

	"pcmpgtd %%xmm7, %%xmm1\n\t"                       // Compare average delta with the threshold
#endif
	"pand %%xmm1, %%xmm5\n\t"                          // Filter out pixels in pabove that shouldn't be copied
	"pandn %%xmm6, %%xmm1\n\t"                         // Filter out pixels in pcurrent that should be replaced

	"por %%xmm5, %%xmm1\n\t"                           // Put the new values in pcurrent
	"movntdq %%xmm1, (%0)\n\t"                         // Write pcurrent
	:
	: "r" (col1), "r" (col2), "r" (max_ptr2), "r" (max_ptr), "r" (row_width), "m" (threshold_val), "m" (*movemask2)
#if defined(__x86_64__)
	: "%eax", "%xmm0", "%xmm1", "%xmm2", "%xmm3", "%xmm4", "%xmm5", "%xmm6", "%xmm7", "%xmm8", "cc", "memory"
#else
	: "%eax", "%xmm0", "%xmm1", "%xmm2", "%xmm3", "%xmm4", "%xmm5", "%xmm6", "%xmm7", "cc", "memory"
#endif
	);
#else
	Panic("SSE function called on a non x86\\x86-64 platform");
#endif
}
