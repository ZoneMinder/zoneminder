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

jpeg_compress_struct *Image::jpg_ccinfo[100] = { 0 };
jpeg_decompress_struct *Image::jpg_dcinfo = 0;
struct zm_error_mgr Image::jpg_err;

/* Pointer to blend function. */
blend_fptr_t fptr_blend;

/* Pointer to delta8 functions */
delta_fptr_t fptr_delta8_rgb;
delta_fptr_t fptr_delta8_bgr;
delta_fptr_t fptr_delta8_rgba;
delta_fptr_t fptr_delta8_bgra;
delta_fptr_t fptr_delta8_argb;
delta_fptr_t fptr_delta8_abgr;
delta_fptr_t fptr_delta8_gray8;

/* Pointer to big memory copy function */
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
    size = width*height*colours;
    buffer = 0;
    holdbuffer = 0;
    if ( p_buffer )
    {
	/* Don't free the supplied buffer, but don't hold it either */
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
	DumpBuffer();
}

void Image::Initialise()
{
	/* Assign the blend pointer to function */
	if(config.fast_image_blends) {
		if(config.cpu_extensions && sseversion >= 20) {
			fptr_blend = &sse2_fastblend; /* SSE2 fast blend */
			Debug(2,"Blend: using SSE2 fast blending");
		} else {
			fptr_blend = &std_fastblend;  /* standard fast blend */
			Debug(2,"Blend: using standard fast blending");
		}
	} else {
		fptr_blend = &std_blend;
		Debug(2,"Blend: using standard blending");
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
			fptr_delta8_rgba = &std_delta8_rgba;
			fptr_delta8_bgra = &std_delta8_bgra;
			fptr_delta8_argb = &std_delta8_argb;
			fptr_delta8_abgr = &std_delta8_abgr;
			fptr_delta8_gray8 = &sse2_delta8_gray8;
			Debug(2,"Delta: Using standard and SSE2 delta functions");
		} else {
			/* No SSE available */
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
uint8_t* Image::WriteBuffer(const int p_width, const int p_height, const int p_colours, const int p_subpixelorder) {
	unsigned int newsize;
  
	if(p_colours != 1 && p_colours != 3 && p_colours != 4) {
		Error("WriteBuffer called with unexpected colours: %d",p_colours);
		return NULL;
	}
	
	if(!p_height || !p_width) {
		Error("WriteBuffer called with invaid width or height: %d %d",p_width,p_height);
		return NULL;
	}
	
	if(p_width != width || p_height != height || p_colours != colours || p_subpixelorder != subpixelorder) {
		newsize = p_width * p_height * p_colours;
		
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
void Image::AssignDirect( const int p_width, const int p_height, const int p_colours, const int p_subpixelorder, uint8_t *new_buffer, const size_t buffer_size, const int p_buffertype) {
	if(new_buffer == NULL) {
		Error("Attempt to directly assign buffer from a NULL pointer");
		return;
	}
	
	if(buffer_size < (p_width*p_height*p_colours)) {
		Error("Attempt to directly assign buffer from an undersized buffer of size: %u",buffer_size);
		return;
	}
	
	if(!p_height || !p_width) {
		Error("Attempt to directly assign buffer with invalid width or height: %d %d",p_width,p_height);
		return;
	}
	
	if(p_colours != 1 && p_colours != 3 && p_colours != 4) {
		Error("Attempt to directly assign buffer with unexpected colours per pixel: %d",p_colours);
		return;
	}
	
	if(holdbuffer && buffer) {
		if((p_height * p_height * p_colours) > allocation) {
			Error("Held buffer is undersized for assigned buffer");
			return;
		} else {
			width = p_width;
			height = p_height;
			colours = p_colours;
			subpixelorder = p_subpixelorder;
			pixels = height*width;
			size = pixels*colours;
			
			/* Copy into the held buffer */
			if(new_buffer != buffer)
				(*fptr_imgbufcpy)(buffer, new_buffer, size);
			
			/* Free the new buffer */
			DumpBuffer(new_buffer, p_buffertype);
		}
	} else {
		/* Free an existing buffer if any */
		DumpBuffer();  
	  
		width = p_width;
		height = p_height;
		colours = p_colours;
		subpixelorder = p_subpixelorder;
		pixels = height*width;
		size = pixels*colours;
	
		allocation = buffer_size;
		buffertype = p_buffertype;
		buffer = new_buffer;
	}
	
}

void Image::Assign(const int p_width, const int p_height, const int p_colours, const int p_subpixelorder, const uint8_t* new_buffer, const size_t buffer_size) {
	unsigned int new_size = p_width * p_height * p_colours;
  
	if(new_buffer == NULL) {
		Error("Attempt to assign buffer from a NULL pointer");
		return;
	}
	
	if(buffer_size < new_size) {
		Error("Attempt to assign buffer from an undersized buffer of size: %u",buffer_size);
		return;
	}
	
	if(!p_height || !p_width) {
		Error("Attempt to assign buffer with invalid width or height: %d %d",p_width,p_height);
		return;
	}
	
	if(p_colours != 1 && p_colours != 3 && p_colours != 4) {
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
	unsigned int new_size = image.width * image.height * image.colours;
	
	if(image.buffer == NULL) {
		Error("Attempt to assign image with an empty buffer");
		return;
	}
  
	if(image.colours != 1 && image.colours != 3 && image.colours != 4) {
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

Image *Image::HighlightEdges( Rgb colour, const Box *limits )
{
    if ( colours != 1 )
    {
        Panic( "Attempt to highlight image edges when colours = %d", colours );
    }
	Image *high_image = new Image( width, height, 3, ZM_SUBPIX_ORDER_RGB);
	int lo_x = limits?limits->Lo().X():0;
	int lo_y = limits?limits->Lo().Y():0;
	int hi_x = limits?limits->Hi().X():width-1;
	int hi_y = limits?limits->Hi().Y():height-1;
	for ( int y = lo_y; y <= hi_y; y++ )
	{
		unsigned char *p = &buffer[(y*width)+lo_x];
		unsigned char *phigh = (uint8_t*)high_image->Buffer( lo_x, y );
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
				RED_PTR_RGBA(phigh) = RED_VAL_RGBA(colour);
				GREEN_PTR_RGBA(phigh) = GREEN_VAL_RGBA(colour);
				BLUE_PTR_RGBA(phigh) = BLUE_VAL_RGBA(colour);
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

bool Image::ReadJpeg( const char *filename, int p_colours, int p_subpixelorder)
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

	if ( cinfo->image_width != width || cinfo->image_height != height)
	{
		width = cinfo->image_width;
		height = cinfo->image_height;
		pixels = width*height;
	}
	
	if ( cinfo->num_components != 1 && cinfo->num_components != 3 )
	{
		Error( "Unexpected colours when reading jpeg image: %d", colours );
		jpeg_abort_decompress( cinfo );
		fclose( infile );
		return( false );
	}
	
	switch(p_colours) {
	  case ZM_COLOUR_GRAY8:
	  {
	    cinfo->out_color_space = JCS_GRAYSCALE;
	    colours = ZM_COLOUR_GRAY8;
	    subpixelorder = ZM_SUBPIX_ORDER_NONE;
	    break;
	  }
	  case ZM_COLOUR_RGB32:
	  {
#ifdef JCS_EXTENSIONS
	    colours = ZM_COLOUR_RGB32;
	    if(p_subpixelorder == ZM_SUBPIX_ORDER_BGRA) {
	      cinfo->out_color_space = JCS_EXT_BGRX;
	      subpixelorder = ZM_SUBPIX_ORDER_BGRA;
	    } else if(p_subpixelorder == ZM_SUBPIX_ORDER_ARGB) {
	      cinfo->out_color_space = JCS_EXT_XRGB;
	      subpixelorder = ZM_SUBPIX_ORDER_ARGB;
	    } else if(p_subpixelorder == ZM_SUBPIX_ORDER_ABGR) {
	      cinfo->out_color_space = JCS_EXT_XBGR;
	      subpixelorder = ZM_SUBPIX_ORDER_ABGR;
	    } else {
	      /* Assume RGBA */
	      cinfo->out_color_space = JCS_EXT_RGBX;
	      subpixelorder = ZM_SUBPIX_ORDER_RGBA;
	    }
	    break;      
#else
	    Warning("libjpeg-turbo is required for reading a JPEG directly into a RGB32 buffer, reading into a RGB24 buffer instead.");
#endif
	  }
	  case ZM_COLOUR_RGB24:
	  default:
	  {
	    colours = ZM_COLOUR_RGB24;
	    if(p_subpixelorder == ZM_SUBPIX_ORDER_BGR) {
#ifdef JCS_EXTENSIONS      
	      cinfo->out_color_space = JCS_EXT_BGR;    
	      subpixelorder = ZM_SUBPIX_ORDER_BGR;
#else
	      Warning("libjpeg-turbo is required for reading a JPEG directly into a BGR24 buffer, reading into a RGB24 buffer instead.");      
	      cinfo->out_color_space = JCS_EXT_RGB;    
	      subpixelorder = ZM_SUBPIX_ORDER_RGB;
#endif
	    } else {
	      /* Assume RGB */
	      cinfo->out_color_space = JCS_RGB;    
	      subpixelorder = ZM_SUBPIX_ORDER_RGB;
	    }
	    break;
	  }
	}
	
	size = pixels*colours;
	
	if(buffer == NULL) {
		AllocImgBuffer(size);
	} else {
		if(allocation < size) {
			if(holdbuffer) {
				Error("Held buffer is undersized for the requested image");
				return (false);
			} else {
				DumpImgBuffer();
				AllocImgBuffer(size);
			}
		}
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
	if ( config.colour_jpeg_files && colours == 1 )
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
	      cinfo->in_color_space = JCS_RGB;
	    }
	    break;
	  }
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

bool Image::DecodeJpeg( const JOCTET *inbuffer, int inbuffer_size, int p_colours, int p_subpixelorder)
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

	if ( cinfo->image_width != width || cinfo->image_height != height)
	{
		width = cinfo->image_width;
		height = cinfo->image_height;
		pixels = width*height;
	}
	
	if ( cinfo->num_components != 1 && cinfo->num_components != 3 )
	{
		Error( "Unexpected colours when reading jpeg image: %d", colours );
		jpeg_abort_decompress( cinfo );
		return( false );
	}

	switch(p_colours) {
	  case ZM_COLOUR_GRAY8:
	  {
	    cinfo->out_color_space = JCS_GRAYSCALE;
	    colours = ZM_COLOUR_GRAY8;
	    subpixelorder = ZM_SUBPIX_ORDER_NONE;
	    break;
	  }
	  case ZM_COLOUR_RGB32:
	  {
#ifdef JCS_EXTENSIONS
	    colours = ZM_COLOUR_RGB32;
	    if(p_subpixelorder == ZM_SUBPIX_ORDER_BGRA) {
	      cinfo->out_color_space = JCS_EXT_BGRX;
	      subpixelorder = ZM_SUBPIX_ORDER_BGRA;
	    } else if(p_subpixelorder == ZM_SUBPIX_ORDER_ARGB) {
	      cinfo->out_color_space = JCS_EXT_XRGB;
	      subpixelorder = ZM_SUBPIX_ORDER_ARGB;
	    } else if(p_subpixelorder == ZM_SUBPIX_ORDER_ABGR) {
	      cinfo->out_color_space = JCS_EXT_XBGR;
	      subpixelorder = ZM_SUBPIX_ORDER_ABGR;
	    } else {
	      /* Assume RGBA */
	      cinfo->out_color_space = JCS_EXT_RGBX;
	      subpixelorder = ZM_SUBPIX_ORDER_RGBA;
	    }
	    break;      
#else
	    Warning("libjpeg-turbo is required for reading a JPEG directly into a RGB32 buffer, reading into a RGB24 buffer instead.");
#endif
	  }
	  case ZM_COLOUR_RGB24:
	  default:
	  {
	    colours = ZM_COLOUR_RGB24;
	    if(p_subpixelorder == ZM_SUBPIX_ORDER_BGR) {
#ifdef JCS_EXTENSIONS      
	      cinfo->out_color_space = JCS_EXT_BGR;    
	      subpixelorder = ZM_SUBPIX_ORDER_BGR;
#else
	      Warning("libjpeg-turbo is required for reading a JPEG directly into a BGR24 buffer, reading into a RGB24 buffer instead.");      
	      cinfo->out_color_space = JCS_EXT_RGB;    
	      subpixelorder = ZM_SUBPIX_ORDER_RGB;
#endif
	    } else {
	      /* Assume RGB */
	      cinfo->out_color_space = JCS_RGB;    
	      subpixelorder = ZM_SUBPIX_ORDER_RGB;
	    }
	    break;
	  }
	}
	
	size = pixels*colours;
	
	if(buffer == NULL) {
		AllocImgBuffer(size);
	} else {
		if(allocation < size) {
			if(holdbuffer) {
				Error("Held buffer is undersized for the requested image");
				return (false);
			} else {
				DumpImgBuffer();
				AllocImgBuffer(size);
			}
		}
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
	if ( config.colour_jpeg_files && colours == 1 )
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
	      cinfo->in_color_space = JCS_RGB;
	    }
	    break;
	  }
	}
	
	jpeg_set_defaults( cinfo );
	jpeg_set_quality( cinfo, quality, false );
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
	uint8_t *new_buffer = AllocBuffer(new_size);
	
	int new_stride = new_width*colours;
	for ( int y = lo_y, ny = 0; y <= hi_y; y++, ny++ )
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
			Colourise(ZM_COLOUR_RGB24, ZM_SUBPIX_ORDER_RGB );
			pdest = buffer;
			while( pdest < (buffer+size) )
			{
				if ( RED_PTR_RGBA(psrc) || GREEN_PTR_RGBA(psrc) || BLUE_PTR_RGBA(psrc) )
				{
					RED_PTR_RGBA(pdest) = RED_PTR_RGBA(psrc);
					GREEN_PTR_RGBA(pdest) = GREEN_PTR_RGBA(psrc);
					BLUE_PTR_RGBA(pdest) = BLUE_PTR_RGBA(psrc);
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
					RED_PTR_RGBA(pdest) = GREEN_PTR_RGBA(pdest) = BLUE_PTR_RGBA(pdest) = *psrc++;
				}
				pdest += 3;
			}
		}
		else
		{
			while( pdest < (buffer+size) )
			{
				if ( RED_PTR_RGBA(psrc) || GREEN_PTR_RGBA(psrc) || BLUE_PTR_RGBA(psrc) )
				{
					RED_PTR_RGBA(pdest) = RED_PTR_RGBA(psrc);
					GREEN_PTR_RGBA(pdest) = GREEN_PTR_RGBA(psrc);
					BLUE_PTR_RGBA(pdest) = BLUE_PTR_RGBA(psrc);
				}
				psrc += 3;
				pdest += 3;
			}
		}
	}
}

/* RGB32 compatible: complete */
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
	if ( colours == ZM_COLOUR_GRAY8 )
	{
		const uint8_t *psrc = image.buffer;
		for ( int y = lo_y; y <= hi_y; y++ )
		{
			uint8_t *pdest = &buffer[(y*width)+lo_x];
			for ( int x = lo_x; x <= hi_x; x++ )
			{
				*pdest++ = *psrc++;
			}
		}
	}
	else if ( colours == ZM_COLOUR_RGB24 )
	{
		const uint8_t *psrc = image.buffer;
		for ( int y = lo_y; y <= hi_y; y++ )
		{
			uint8_t *pdest = &buffer[colours*((y*width)+lo_x)];
			for ( int x = lo_x; x <= hi_x; x++ )
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
		for ( int y = lo_y; y <= hi_y; y++ )
		{
			Rgb *pdest = (Rgb*)&buffer[((y*width)+lo_x)<<2];
			for ( int x = lo_x; x <= hi_x; x++ )
			{
				*pdest++ = *psrc++;
			}
		}
	} else {
		Error("Overlay called with unexpected colours: %d", colours);
	}
	
}

void Image::Blend( const Image &image, int transparency ) const
{
	if ( !(width == image.width && height == image.height && colours == image.colours && subpixelorder == image.subpixelorder) )
	{
        Panic( "Attempt to blend different sized images, expected %dx%dx%d %d, got %dx%dx%d %d", width, height, colours, subpixelorder, image.width, image.height, image.colours, image.subpixelorder );
	}
	
	if((size % 16) != 0) {
		Warning("Image size is not multiples of 16");
	}
	
	/* Do the blending */
	(*fptr_blend)(buffer, image.buffer, buffer, size, transparency);
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

	Image *result = new Image( width, height, images[0]->colours, images[0]->subpixelorder);
	int size = result->size;
	for ( int i = 0; i < size; i++ )
	{
		int total = 0;
		uint8_t *pdest = result->buffer;
		for ( int j = 0; j < n_images; j++ )
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
		uint8_t *pdest = result->buffer;
		uint8_t *psrc = images[i]->buffer;
		for ( int j = 0; j < size; j++ )
		{
			*pdest = (uint8_t)(((*pdest)*(1.0-factor))+((*psrc)*factor));
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

	Image *result = new Image( width, height, images[0]->colours, images[0]->subpixelorder );
	int size = result->size;
	for ( int c = 0; c < colours; c++ )
	{
		for ( int i = 0; i < size; i++ )
		{
			int count = 0;
			uint8_t *pdest = result->buffer+c;
			for ( int j = 0; j < n_images; j++ )
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
	if ( !(width == image.width && height == image.height && colours == image.colours && subpixelorder == image.subpixelorder) )
	{
		Panic( "Attempt to get delta of different sized images, expected %dx%dx%d %d, got %dx%dx%d %d", width, height, colours, subpixelorder, image.width, image.height, image.colours, image.subpixelorder);
	}
	
	uint8_t *pdiff = targetimage->WriteBuffer(width, height, ZM_COLOUR_GRAY8, ZM_SUBPIX_ORDER_NONE);
	
	if(pdiff == NULL) {
		Panic("Failed requesting writeable buffer for storing the delta image");
	}

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

    int index = 0;
    int line_no = 0;
	int text_len = strlen( text );
    int line_len = 0;
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
        else if ( colours == 3 )
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
        else if ( colours == 4)
	{
            int wc = width * colours;

            uint8_t *ptr = &buffer[((lo_line_y*width)+lo_line_x)<<2];
            for ( int y = lo_line_y, r = 0; y < hi_line_y && r < CHAR_HEIGHT; y++, r++, ptr += wc )
            {
                Rgb* temp_ptr = (Rgb*)ptr;
                for ( int x = lo_line_x, c = 0; x < hi_line_x && c < line_len; c++ )
                {
                    int f = fontdata[(line[c] * CHAR_HEIGHT) + r];
                    for ( int i = 0; i < CHAR_WIDTH && x < hi_line_x; i++, x++, temp_ptr++ )
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

void Image::Colourise(const int p_reqcolours, const int p_reqsubpixelorder)
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
			for(int i=0;i<pixels;i++) {
				newpixel = subpixel = psrc[i];
				newpixel = (newpixel<<8) | subpixel;
				newpixel = (newpixel<<8) | subpixel;
				pdest[i] = (newpixel<<8);
			}		
		} else {
			/* RGBA\BGRA subpixel order, alpha byte is last (mem+3) */
			for(int i=0;i<pixels;i++) {
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
		
		for(unsigned int i=0;i<pixels;i++, pdest += 3)
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


void Image::DeColourise()
{
	colours = 1;
	size = width * height;
	
	uint8_t *pdest = buffer;
	unsigned int r,g,b;
	
	if ( colours == 3 )
	{
		const uint8_t *psrc = buffer;
		while( pdest < (buffer+size) )
		{
			//*pdest++ = (uint8_t)sqrt((RED(psrc) + GREEN(psrc) + BLUE(psrc))/3);
			
			/* Use fast algorithm for almost identical ITU-R BT.709 weighting */
			if(subpixelorder == ZM_SUBPIX_ORDER_BGR) {
				/* BGR subpixel order */
				r = RED_PTR_BGRA(psrc);
				g = GREEN_PTR_BGRA(psrc);
				b = BLUE_PTR_BGRA(psrc);
				*pdest++ = (r + r + b + g + g + g + g + g)>>3;
			} else {
				/* RGB subpixel order */
				r = RED_PTR_RGBA(psrc);
				g = GREEN_PTR_RGBA(psrc);
				b = BLUE_PTR_RGBA(psrc);
				*pdest++ = (r + r + b + g + g + g + g + g)>>3;
			}
			psrc += 3;
		}
	}
}

/* RGB32 compatible: complete */
void Image::Fill( Rgb colour, const Box *limits )
{
	if ( !(colours == 1 || colours == 3 || colours == 4 ) )
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
			for ( int x = lo_x; x <= hi_x; x++, p++)
			{
				*p = colour;
			}
		}
	}
	else if ( colours == 3 )
	{
		for ( int y = lo_y; y <= hi_y; y++ )
		{
			unsigned char *p = &buffer[colours*((y*width)+lo_x)];
			for ( int x = lo_x; x <= hi_x; x++, p += 3)
			{
				RED_PTR_RGBA(p) = RED_VAL_RGBA(colour);
				GREEN_PTR_RGBA(p) = GREEN_VAL_RGBA(colour);
				BLUE_PTR_RGBA(p) = BLUE_VAL_RGBA(colour);
			}
		}
	}
	else if ( colours == 4 ) /* RGB32 [R,G,B,A] */
	{
		for ( unsigned int y = lo_y; y <= hi_y; y++ )
		{
			Rgb *p = (Rgb*)&buffer[((y*width)+lo_x)<<2];
			// unsigned int count = (hi_x+1) - lo_x;
			
			for ( unsigned int x = lo_x; x <= hi_x; x++, p++)
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
	
	if ( !(colours == 1 || colours == 3 || colours == 4 ) )
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
			for ( int x = lo_x; x <= hi_x; x++, p++)
			{
				if ( ( x == lo_x || x == hi_x || y == lo_y || y == hi_y ) || (!(x%density) && !(y%density) ) )
					*p = colour;
			}
		}
	}
	else if ( colours == 3 )
	{
		for ( int y = lo_y; y <= hi_y; y++ )
		{
			unsigned char *p = &buffer[colours*((y*width)+lo_x)];
			for ( int x = lo_x; x <= hi_x; x++, p += 3)
			{
				if ( ( x == lo_x || x == hi_x || y == lo_y || y == hi_y ) || (!(x%density) && !(y%density) ) ) {
					RED_PTR_RGBA(p) = RED_VAL_RGBA(colour);
					GREEN_PTR_RGBA(p) = GREEN_VAL_RGBA(colour);
					BLUE_PTR_RGBA(p) = BLUE_VAL_RGBA(colour);
				}
			}
		}
	}
	else if ( colours == 4 ) /* RGB32 [R,G,B,A] */
	{
		for ( int y = lo_y; y <= hi_y; y++ )
		{
			Rgb* p = (Rgb*)&buffer[((y*width)+lo_x)<<2];

			for ( int x = lo_x; x <= hi_x; x++, p++)
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
	if ( !(colours == 1 || colours == 3 || colours == 4 ) )
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
					RED_PTR_RGBA(p) = RED_VAL_RGBA(colour);
					GREEN_PTR_RGBA(p) = GREEN_VAL_RGBA(colour);
					BLUE_PTR_RGBA(p) = BLUE_VAL_RGBA(colour);
				}
			}
			else if ( colours == 4 )
			{
				for ( x = x1, y = y1; y != y2; y += yinc, x += grad )
				{
					*(Rgb*)(buffer+(((y*width)+int(round(x)))<<2)) = colour;
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
					RED_PTR_RGBA(p) = RED_VAL_RGBA(colour);
					GREEN_PTR_RGBA(p) = GREEN_VAL_RGBA(colour);
					BLUE_PTR_RGBA(p) = BLUE_VAL_RGBA(colour);
				}
			}
			else if ( colours == 4 )
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
	if ( !(colours == 1 || colours == 3 || colours == 4 ) )
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
				if( colours == 1) {
					unsigned char *p = &buffer[(y*width)+lo_x];
					for ( int x = lo_x; x <= hi_x; x++, p++)
					{
						if ( !(x%density) )
						{
							//Debug( 9, " %d", x );
							*p = colour;
						}
					}
				} else if( colours == 3) {
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
				} else if( colours == 4) {
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
	
	uint8_t* rotate_buffer = AllocBuffer(size);

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
 
	AssignDirect( width, height, colours, subpixelorder, rotate_buffer, size, ZM_BUFTYPE_ZM);
	
}

void Image::Flip( bool leftright )
{

	uint8_t* flip_buffer = AllocBuffer(size);
	
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
	
	AssignDirect( new_width, new_height, colours, subpixelorder, scale_buffer, scale_buffer_size, ZM_BUFTYPE_ZM);
	
}


/************************************************* BLEND FUNCTIONS *************************************************/


__attribute__ ((noinline)) void sse2_fastblend(const uint8_t* col1, const uint8_t* col2, uint8_t* result, unsigned long count, double blendpercent) {
#if (defined(__i386__) || defined(__x86_64__))  
	int divider = 0;
	uint32_t clearmask = 0;
	unsigned long i = 0;

	/* Attempt to match the blending percent to one of the possible values */
	if(blendpercent < 2.34375) {
		// 1.5625% blending
		divider = 6;
		clearmask = 0x03030303;
	} else if(blendpercent >= 2.34375 && blendpercent < 4.6875) {
		// 3.125% blending
		divider = 5;
		clearmask = 0x07070707;
	} else if(blendpercent >= 4.6875 && blendpercent < 9.375) {
		// 6.25% blending
		divider = 4;
		clearmask = 0x0F0F0F0F;
	} else if(blendpercent >= 9.375 && blendpercent < 18.75) {
		// 12.5% blending
		divider = 3;
		clearmask = 0x1F1F1F1F;
	} else if(blendpercent >= 18.75 && blendpercent < 37.5) {
		// 25% blending
		divider = 2;
		clearmask = 0x3F3F3F3F;
	} else if(blendpercent >= 37.5) {
		// 50% blending
		divider = 1;
		clearmask = 0x7F7F7F7F;
	}

	__asm__ __volatile__(
	"movd %5, %%xmm3\n\t"
	"movd %6, %%xmm4\n\t"
	"pshufd $0x0, %%xmm3, %%xmm3\n\t"
	"algo_sse2_blend:\n\t"
	"movdqa (%0,%4),%%xmm0\n\t"
	"movdqa (%1,%4),%%xmm1\n\t"
	"movdqa %%xmm0,%%xmm2\n\t"    
	"psrlq  %%xmm4,%%xmm0\n\t"
	"psrlq  %%xmm4,%%xmm1\n\t"
	"pand   %%xmm3,%%xmm1\n\t"
	"pand   %%xmm3,%%xmm0\n\t"
	"psubb  %%xmm0,%%xmm1\n\t"
	"paddb  %%xmm2,%%xmm1\n\t"
	"movntdq %%xmm1,(%2,%4)\n\t"
	"add $0x10,%4\n\t"
	"cmp %3, %4\n\t"
	"jb algo_sse2_blend\n\t"
	:
	: "r" (col1), "r" (col2), "r" (result), "r" (count), "r" (i), "m" (clearmask), "m" (divider)
	: "%xmm0", "%xmm1", "%xmm2", "%xmm3", "%xmm4", "cc", "memory"
	);
#else
	Panic("SSE function called on a non x86\\x86-64 platform");
#endif
}

__attribute__ ((noinline)) void std_fastblend(const uint8_t* col1, const uint8_t* col2, uint8_t* result, unsigned long count, double blendpercent) {
	int divider = 0;

	/* Attempt to match the blending percent to one of the possible values */
	if(blendpercent < 2.34375) {
		// 1.5625% blending
		divider = 6;
	} else if(blendpercent >= 2.34375 && blendpercent < 4.6875) {
		// 3.125% blending
		divider = 5;
	} else if(blendpercent >= 4.6875 && blendpercent < 9.375) {
		// 6.25% blending
		divider = 4;
	} else if(blendpercent >= 9.375 && blendpercent < 18.75) {
		// 12.5% blending
		divider = 3;
	} else if(blendpercent >= 18.75 && blendpercent < 37.5) {
		// 25% blending
		divider = 2;
	} else if(blendpercent >= 37.5) {
		// 50% blending
		divider = 1;
	}

	for(register unsigned long i=0; i < count; i += 16, col1 += 16, col2 += 16, result += 16) {  
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
	}
}

__attribute__ ((noinline)) void std_blend(const uint8_t* col1, const uint8_t* col2, uint8_t* result, unsigned long count, double blendpercent) {
	double divide = blendpercent / 100.0;
	double opacity = 1.0 - divide;
	for(register unsigned long i=0; i < count; i++) {
		result[i] = (col1[i] * opacity) + (col2[i] * divide);
	} 
}




/************************************************* DELTA FUNCTIONS *************************************************/

/* Grayscale */
__attribute__ ((noinline)) void std_delta8_gray8(const uint8_t* col1, const uint8_t* col2, uint8_t* result, unsigned long count) {
	/* Loop unrolling is used to work on 16 bytes (16 grayscale pixels) at a time */  
	for(unsigned int i=0;i<count; i+=16, col1 += 16, col2 += 16, result += 16) {
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
	}  
}

/* RGB24: RGB */
__attribute__ ((noinline)) void std_delta8_rgb(const uint8_t* col1, const uint8_t* col2, uint8_t* result, unsigned long count) {
	/* Loop unrolling is used to work on 12 bytes (4 rgb24 pixels) at a time */
	int r,g,b;  
	for(unsigned int i=0; i<count; i +=4 , col1 += 12, col2 += 12, result += 4) {
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
	}
}

/* RGB24: BGR */
__attribute__ ((noinline)) void std_delta8_bgr(const uint8_t* col1, const uint8_t* col2, uint8_t* result, unsigned long count) {
	/* Loop unrolling is used to work on 12 bytes (4 rgb24 pixels) at a time */
	int r,g,b;  
	for(unsigned int i=0; i<count; i +=4 , col1 += 12, col2 += 12, result += 4) {
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
	}
}

/* RGB32: RGBA */
__attribute__ ((noinline)) void std_delta8_rgba(const uint8_t* col1, const uint8_t* col2, uint8_t* result, unsigned long count) {
	/* Loop unrolling is used to work on 16 bytes (4 rgb32 pixels) at a time */
	int r,g,b;  
	for(unsigned int i=0; i<count; i +=4 , col1 += 16, col2 += 16, result += 4) {
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
	}
}

/* RGB32: BGRA */
__attribute__ ((noinline)) void std_delta8_bgra(const uint8_t* col1, const uint8_t* col2, uint8_t* result, unsigned long count) {
	/* Loop unrolling is used to work on 16 bytes (4 rgb32 pixels) at a time */
	int r,g,b;  
	for(unsigned int i=0; i<count; i +=4 , col1 += 16, col2 += 16, result += 4) {
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
	}
}

/* RGB32: ARGB */
__attribute__ ((noinline)) void std_delta8_argb(const uint8_t* col1, const uint8_t* col2, uint8_t* result, unsigned long count) {
	/* Loop unrolling is used to work on 16 bytes (4 rgb32 pixels) at a time */
	int r,g,b;  
	for(unsigned int i=0; i<count; i +=4 , col1 += 16, col2 += 16, result += 4) {
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
	}
}

/* RGB32: ABGR */
__attribute__ ((noinline)) void std_delta8_abgr(const uint8_t* col1, const uint8_t* col2, uint8_t* result, unsigned long count) {
	/* Loop unrolling is used to work on 16 bytes (4 rgb32 pixels) at a time */
	int r,g,b;  
	for(unsigned int i=0; i<count; i +=4 , col1 += 16, col2 += 16, result += 4) {
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
	}
}

/* Grayscale SSE2 */
__attribute__ ((noinline)) void sse2_delta8_gray8(const uint8_t* col1, const uint8_t* col2, uint8_t* result, unsigned long count) {
#if (defined(__i386__) || defined(__x86_64__))  
	unsigned long i = 0;

	/* Works on 16 grayscale pixels per iteration, similar to the non-SSE version above */
	/* XMM0 - unused */
	/* XMM1,2,3,4 - General purpose */
	/* XMM5 - unused */
	/* XMM6 - unused */
	/* XMM7 - unused */  
  
	__asm__ __volatile__ (
	"algo_sse2_delta8_gray8:\n\t"
	"movdqa (%0,%4), %%xmm1\n\t"
	"movdqa (%1,%4), %%xmm2\n\t"
	"movdqa %%xmm1, %%xmm3\n\t"
	"movdqa %%xmm2, %%xmm4\n\t"
	"pmaxub %%xmm1, %%xmm2\n\t"
	"pminub %%xmm3, %%xmm4\n\t"
	"psubb %%xmm4, %%xmm2\n\t"
	"movntdq %%xmm2, (%2,%4)\n\t"
	"add $0x10, %4\n\t"
	"cmp %3, %4\n\t"
	"jb algo_sse2_delta8_gray8\n\t"
	:
	: "r" (col1), "r" (col2), "r" (result), "r" (count), "r" (i)
	: "%xmm1", "%xmm2", "%xmm3", "%xmm4", "cc", "memory"
	);
#else
	Panic("SSE function called on a non x86\\x86-64 platform");
#endif
}

/* RGB32: RGBA SSSE3 */
__attribute__ ((noinline)) void ssse3_delta8_rgba(const uint8_t* col1, const uint8_t* col2, uint8_t* result, unsigned long count) {
#if (defined(__i386__) || defined(__x86_64__))
	unsigned long i = 0;
	
	/* XMM0 - clear mask - kept */
	/* XMM1,2,3 - General purpose */
	/* XMM4 - divide mask - kept */
	/* XMM5 - shuffle mask - kept */
	/* XMM6 - unused */
	/* XMM7 - unused */
	
	__asm__ __volatile__ (
	"mov $0x1F1F1F1F, %%eax\n\t"
	"movd %%eax, %%xmm4\n\t"
	"pshufd $0x0, %%xmm4, %%xmm4\n\t"
	"mov $0xff, %%eax\n\t"
	"movd %%eax, %%xmm0\n\t"
	"pshufd $0x0, %%xmm0, %%xmm0\n\t"
	"movdqa %5, %%xmm5\n\t"
	"algo_ssse3_delta8_rgba:\n\t"
	"movdqa (%0,%4,4), %%xmm1\n\t"
	"movdqa (%1,%4,4), %%xmm2\n\t"
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
	"movnti %%eax, (%2,%4)\n\t"
	"add $0x4, %4\n\t"
	"cmp %3, %4\n\t"
	"jb algo_ssse3_delta8_rgba\n\t"
	:
	: "r" (col1), "r" (col2), "r" (result), "r" (count), "r" (i), "m" (*movemask)
	: "%eax", "%xmm0", "%xmm1", "%xmm2", "%xmm3", "%xmm4", "%xmm5", "cc", "memory"
	);
#else
	Panic("SSE function called on a non x86\\x86-64 platform");
#endif
}

/* RGB32: BGRA SSSE3 */
__attribute__ ((noinline)) void ssse3_delta8_bgra(const uint8_t* col1, const uint8_t* col2, uint8_t* result, unsigned long count) {
#if (defined(__i386__) || defined(__x86_64__))  
	unsigned long i = 0;
	
	/* XMM0 - clear mask - kept */
	/* XMM1,2,3 - General purpose */
	/* XMM4 - divide mask - kept */
	/* XMM5 - shuffle mask - kept */
	/* XMM6 - unused */
	/* XMM7 - unused */
	
	__asm__ __volatile__ (
	"mov $0x1F1F1F1F, %%eax\n\t"
	"movd %%eax, %%xmm4\n\t"
	"pshufd $0x0, %%xmm4, %%xmm4\n\t"
	"mov $0xff, %%eax\n\t"
	"movd %%eax, %%xmm0\n\t"
	"pshufd $0x0, %%xmm0, %%xmm0\n\t"
	"movdqa %5, %%xmm5\n\t"
	"algo_ssse3_delta8_bgra:\n\t"
	"movdqa (%0,%4,4), %%xmm1\n\t"
	"movdqa (%1,%4,4), %%xmm2\n\t"
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
	"movnti %%eax, (%2,%4)\n\t"
	"add $0x4, %4\n\t"
	"cmp %3, %4\n\t"
	"jb algo_ssse3_delta8_bgra\n\t"
	:
	: "r" (col1), "r" (col2), "r" (result), "r" (count), "r" (i), "m" (*movemask)
	: "%eax", "%xmm0", "%xmm1", "%xmm2", "%xmm3", "%xmm4", "%xmm5", "cc", "memory"
	);
#else
	Panic("SSE function called on a non x86\\x86-64 platform");
#endif
}

/* RGB32: ARGB SSSE3 */
__attribute__ ((noinline)) void ssse3_delta8_argb(const uint8_t* col1, const uint8_t* col2, uint8_t* result, unsigned long count) {
#if (defined(__i386__) || defined(__x86_64__))  
	unsigned long i = 0;
	
	/* XMM0 - clear mask - kept */
	/* XMM1,2,3 - General purpose */
	/* XMM4 - divide mask - kept */
	/* XMM5 - shuffle mask - kept */
	/* XMM6 - unused */
	/* XMM7 - unused */
	
	__asm__ __volatile__ (
	"mov $0x1F1F1F1F, %%eax\n\t"
	"movd %%eax, %%xmm4\n\t"
	"pshufd $0x0, %%xmm4, %%xmm4\n\t"
	"mov $0xff, %%eax\n\t"
	"movd %%eax, %%xmm0\n\t"
	"pshufd $0x0, %%xmm0, %%xmm0\n\t"
	"movdqa %5, %%xmm5\n\t"
	"algo_ssse3_delta8_argb:\n\t"
	"movdqa (%0,%4,4), %%xmm1\n\t"
	"movdqa (%1,%4,4), %%xmm2\n\t"
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
	"movnti %%eax, (%2,%4)\n\t"
	"add $0x4, %4\n\t"
	"cmp %3, %4\n\t"
	"jb algo_ssse3_delta8_argb\n\t"
	:
	: "r" (col1), "r" (col2), "r" (result), "r" (count), "r" (i), "m" (*movemask)
	: "%eax", "%xmm0", "%xmm1", "%xmm2", "%xmm3", "%xmm4", "%xmm5", "cc", "memory"
	);
#else
	Panic("SSE function called on a non x86\\x86-64 platform");
#endif
}

/* RGB32: ABGR SSSE3 */
__attribute__ ((noinline)) void ssse3_delta8_abgr(const uint8_t* col1, const uint8_t* col2, uint8_t* result, unsigned long count) {
#if (defined(__i386__) || defined(__x86_64__))  
	unsigned long i = 0;
	
	/* XMM0 - clear mask - kept */
	/* XMM1,2,3 - General purpose */
	/* XMM4 - divide mask - kept */
	/* XMM5 - shuffle mask - kept */
	/* XMM6 - unused */
	/* XMM7 - unused */
	
	__asm__ __volatile__ (
	"mov $0x1F1F1F1F, %%eax\n\t"
	"movd %%eax, %%xmm4\n\t"
	"pshufd $0x0, %%xmm4, %%xmm4\n\t"
	"mov $0xff, %%eax\n\t"
	"movd %%eax, %%xmm0\n\t"
	"pshufd $0x0, %%xmm0, %%xmm0\n\t"
	"movdqa %5, %%xmm5\n\t"
	"algo_ssse3_delta8_abgr:\n\t"
	"movdqa (%0,%4,4), %%xmm1\n\t"
	"movdqa (%1,%4,4), %%xmm2\n\t"
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
	"movnti %%eax, (%2,%4)\n\t"
	"add $0x4, %4\n\t"
	"cmp %3, %4\n\t"
	"jb algo_ssse3_delta8_abgr\n\t"
	:
	: "r" (col1), "r" (col2), "r" (result), "r" (count), "r" (i), "m" (*movemask)
	: "%eax", "%xmm0", "%xmm1", "%xmm2", "%xmm3", "%xmm4", "%xmm5", "cc", "memory"
	);
#else
	Panic("SSE function called on a non x86\\x86-64 platform");
#endif
}


/************************************************* CONVERT FUNCTIONS *************************************************/

/* RGBA to grayscale */
__attribute__ ((noinline)) void std_convert_rgba_gray8(const uint8_t* col1, uint8_t* result, unsigned long count) {
	unsigned int r,g,b;  
	for(unsigned int i=0; i<count; i += 4, col1 += 16, result += 4) {
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
	}
}

/* BGRA to grayscale */
__attribute__ ((noinline)) void std_convert_bgra_gray8(const uint8_t* col1, uint8_t* result, unsigned long count) {
	unsigned int r,g,b;  
	for(unsigned int i=0; i<count; i += 4, col1 += 16, result += 4) {
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
	}
}

/* ARGB to grayscale */
__attribute__ ((noinline)) void std_convert_argb_gray8(const uint8_t* col1, uint8_t* result, unsigned long count) {
	unsigned int r,g,b;  
	for(unsigned int i=0; i<count; i += 4, col1 += 16, result += 4) {
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
	}
}

/* ABGR to grayscale */
__attribute__ ((noinline)) void std_convert_abgr_gray8(const uint8_t* col1, uint8_t* result, unsigned long count) {
	unsigned int r,g,b;  
	for(unsigned int i=0; i<count; i += 4, col1 += 16, result += 4) {
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
	}
}

/* Converts a YUYV image into grayscale by extracting the Y channel */
__attribute__ ((noinline)) void std_convert_yuyv_gray8(const uint8_t* col1, uint8_t* result, unsigned long count) {
	const uint16_t* yuvbuf = (const uint16_t*)col1;
	
	for(register unsigned long i=0; i < count; i += 16, yuvbuf += 16, result += 16) {  
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
	}  
}

/* RGBA to grayscale SSSE3 */
__attribute__ ((noinline)) void ssse3_convert_rgba_gray8(const uint8_t* col1, uint8_t* result, unsigned long count) {
#if (defined(__i386__) || defined(__x86_64__))  
	unsigned long i = 0;
	
	/* XMM0 - clear mask - kept */
	/* XMM1,2,3 - General purpose */
	/* XMM4 - divide mask - kept */
	/* XMM5 - shuffle mask - kept */
	/* XMM6 - unused */
	/* XMM7 - unused */

	__asm__ __volatile__ (
	"mov $0x1F1F1F1F, %%eax\n\t"
	"movd %%eax, %%xmm4\n\t"
	"pshufd $0x0, %%xmm4, %%xmm4\n\t"
	"mov $0xff, %%eax\n\t"
	"movd %%eax, %%xmm0\n\t"
	"pshufd $0x0, %%xmm0, %%xmm0\n\t"
	"movdqa %4, %%xmm5\n\t"
	"algo_ssse3_convert_rgba_gray8:\n\t"
	"movdqa (%0,%3,4), %%xmm3\n\t"
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
	"movnti %%eax, (%1,%3)\n\t"
	"add $0x4, %3\n\t"
	"cmp %2, %3\n\t"
	"jb algo_ssse3_convert_rgba_gray8\n\t"
	:
	: "r" (col1), "r" (result), "r" (count), "r" (i), "m" (*movemask)
	: "%eax", "%xmm0", "%xmm1", "%xmm2", "%xmm3", "%xmm4", "%xmm5", "cc", "memory"
	);
#else
	Panic("SSE function called on a non x86\\x86-64 platform");
#endif
}

/* Converts a YUYV image into grayscale by extracting the Y channel */
__attribute__ ((noinline)) void ssse3_convert_yuyv_gray8(const uint8_t* col1, uint8_t* result, unsigned long count) {
#if (defined(__i386__) || defined(__x86_64__))  
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
	: "r" (col1), "r" (result), "r" (count), "r" (i), "m" (*movemask1), "m" (*movemask2)
	: "%xmm3", "%xmm4", "cc", "memory"
	);
#else
	Panic("SSE function called on a non x86\\x86-64 platform");
#endif
}

/* YUYV to RGB24 - relocated from zm_local_camera.cpp */
__attribute__ ((noinline)) void zm_convert_yuyv_rgb(const uint8_t* col1, uint8_t* result, unsigned long count) {
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
__attribute__ ((noinline)) void zm_convert_yuyv_rgba(const uint8_t* col1, uint8_t* result, unsigned long count) {
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
__attribute__ ((noinline)) void zm_convert_rgb555_rgb(const uint8_t* col1, uint8_t* result, unsigned long count) {
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
__attribute__ ((noinline)) void zm_convert_rgb555_rgba(const uint8_t* col1, uint8_t* result, unsigned long count) {
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
__attribute__ ((noinline)) void zm_convert_rgb565_rgb(const uint8_t* col1, uint8_t* result, unsigned long count) {
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
__attribute__ ((noinline)) void zm_convert_rgb565_rgba(const uint8_t* col1, uint8_t* result, unsigned long count) {
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

