//
// ZoneMinder Image Class Implementation, $Date$, $Revision$
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
#include "zm.h"
#include "zm_font.h"
#include "zm_image.h"

#define ABSDIFF(a,b) 	(((a)<(b))?((b)-(a)):((a)-(b)))

Image *Image::HighlightEdges( Rgb colour, const Box *limits )
{
	assert( colours = 1 );
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

void Image::ReadJpeg( const char *filename )
{
	struct jpeg_decompress_struct cinfo;
	struct jpeg_error_mgr jerr;
	cinfo.err = jpeg_std_error(&jerr);
	jpeg_create_decompress(&cinfo);

	FILE * infile;
	if ((infile = fopen(filename, "rb" )) == NULL)
	{
		Error(( "Can't open %s: %s", filename, strerror(errno)));
		exit( -1 );
	}
	jpeg_stdio_src(&cinfo, infile);

	jpeg_read_header(&cinfo, TRUE);

	width = cinfo.image_width;
	height = cinfo.image_height;
	colours = cinfo.num_components;
	size = width*height*colours;

	assert( colours == 1 || colours == 3 );
	delete[] buffer;
	buffer = new JSAMPLE[size];

	jpeg_start_decompress(&cinfo);

	JSAMPROW row_pointer;	/* pointer to a single row */
	int row_stride = width * colours;	/* physical row width in buffer */
	while (cinfo.output_scanline < cinfo.output_height)
	{
		row_pointer = &buffer[cinfo.output_scanline * row_stride];
		jpeg_read_scanlines(&cinfo, &row_pointer, 1);
	}

	jpeg_finish_decompress(&cinfo);

	jpeg_destroy_decompress(&cinfo);

	fclose( infile );
}

void Image::WriteJpeg( const char *filename ) const
{
	if ( (bool)config.Item( ZM_COLOUR_JPEG_FILES ) && colours == 1 )
	{
		Image temp_image( *this );
		temp_image.Colourise();
		temp_image.WriteJpeg( filename );
		return;
	}

	struct jpeg_compress_struct cinfo;
	struct jpeg_error_mgr jerr;
	cinfo.err = jpeg_std_error(&jerr);
	jpeg_create_compress(&cinfo);

	FILE *outfile;
	if ((outfile = fopen(filename, "wb" )) == NULL)
	{
		Error(( "Can't open %s: %s", filename, strerror(errno)));
		exit( -1 );
	}
	jpeg_stdio_dest(&cinfo, outfile);

	cinfo.image_width = width; 	/* image width and height, in pixels */
	cinfo.image_height = height;

	cinfo.input_components = colours;	/* # of color components per pixel */
	if ( colours == 1 )
	{
		cinfo.in_color_space = JCS_GRAYSCALE; /* colorspace of input image */
	}
	else
	{
		cinfo.in_color_space = JCS_RGB; /* colorspace of input image */
	}
	jpeg_set_defaults(&cinfo);
	cinfo.dct_method = JDCT_FASTEST;
	jpeg_set_quality(&cinfo, (int)config.Item( ZM_JPEG_FILE_QUALITY ), false);
	jpeg_start_compress(&cinfo, TRUE);

	JSAMPROW row_pointer;	/* pointer to a single row */
	int row_stride = cinfo.image_width * cinfo.input_components;	/* physical row width in buffer */
	while (cinfo.next_scanline < cinfo.image_height)
	{
		row_pointer = &buffer[cinfo.next_scanline * row_stride];
		jpeg_write_scanlines(&cinfo, &row_pointer, 1);
	}

	jpeg_finish_compress(&cinfo);

	jpeg_destroy_compress(&cinfo);

	fclose( outfile );
}

void Image::DecodeJpeg( JOCTET *inbuffer, int inbuffer_size )
{
	struct jpeg_decompress_struct cinfo;
	struct jpeg_error_mgr jerr;
	cinfo.err = jpeg_std_error(&jerr);
	jpeg_create_decompress(&cinfo);

	jpeg_mem_src(&cinfo, inbuffer, inbuffer_size );

	jpeg_read_header(&cinfo, TRUE);

	width = cinfo.image_width;
	height = cinfo.image_height;
	colours = cinfo.num_components;
	size = width*height*colours;

	assert( colours == 1 || colours == 3 );
	delete[] buffer;
	buffer = new JSAMPLE[size];

	jpeg_start_decompress(&cinfo);

	JSAMPROW row_pointer;	/* pointer to a single row */
	int row_stride = width * colours;	/* physical row width in buffer */
	while (cinfo.output_scanline < cinfo.output_height)
	{
		row_pointer = &buffer[cinfo.output_scanline * row_stride];
		jpeg_read_scanlines(&cinfo, &row_pointer, 1);
	}

	jpeg_finish_decompress(&cinfo);

	jpeg_destroy_decompress(&cinfo);
}

void Image::EncodeJpeg( JOCTET *outbuffer, int *outbuffer_size ) const
{
	if ( (bool)config.Item( ZM_COLOUR_JPEG_FILES ) && colours == 1 )
	{
		Image temp_image( *this );
		temp_image.Colourise();
		temp_image.EncodeJpeg( outbuffer, outbuffer_size );
		return;
	}

	struct jpeg_compress_struct cinfo;
	struct jpeg_error_mgr jerr;
	cinfo.err = jpeg_std_error(&jerr);
	jpeg_create_compress(&cinfo);

	jpeg_mem_dest(&cinfo, outbuffer, outbuffer_size );

	cinfo.image_width = width; 	/* image width and height, in pixels */
	cinfo.image_height = height;
	cinfo.input_components = colours;	/* # of color components per pixel */
	if ( colours == 1 )
	{
		cinfo.in_color_space = JCS_GRAYSCALE; /* colorspace of input image */
	}
	else
	{
		cinfo.in_color_space = JCS_RGB; /* colorspace of input image */
	}
	jpeg_set_defaults(&cinfo);
	cinfo.dct_method = JDCT_FASTEST;
	jpeg_set_quality(&cinfo, (int)config.Item( ZM_JPEG_IMAGE_QUALITY ), false);
	jpeg_start_compress(&cinfo, TRUE);

	JSAMPROW row_pointer;	/* pointer to a single row */
	int row_stride = cinfo.image_width * cinfo.input_components;	/* physical row width in buffer */
	while (cinfo.next_scanline < cinfo.image_height)
	{
		row_pointer = &buffer[cinfo.next_scanline * row_stride];
		jpeg_write_scanlines(&cinfo, &row_pointer, 1);
	}

	jpeg_finish_compress(&cinfo);

	jpeg_destroy_compress(&cinfo);
}

void Image::Overlay( const Image &image )
{
	//assert( width == image.width && height == image.height && colours == image.colours );
	assert( width == image.width && height == image.height );

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

void Image::Blend( const Image &image, double transparency ) const
{
	assert( width == image.width && height == image.height && colours == image.colours );

	JSAMPLE *psrc = image.buffer;
	JSAMPLE *pdest = buffer;

	while( pdest < (buffer+size) )
	{
		*pdest++ = (JSAMPLE)round((*pdest * (1.0-transparency))+(*psrc++ * transparency));
	}
}

void Image::Blend( const Image &image, int transparency ) const
{
	assert( width == image.width && height == image.height && colours == image.colours );

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

	while( pdest < (buffer+size) )
	{
		*pblend = (unsigned int)(((*pblend * (100-transparency))+(((*psrc++)<<8) * transparency))/100);
		*pdest++ = (JSAMPLE)((*pblend++)>>8);
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
		assert( width == images[i]->width && height == images[i]->height && colours == images[i]->colours );
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
		assert( width == images[i]->width && height == images[i]->height && colours == images[i]->colours );
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
		assert( width == images[i]->width && height == images[i]->height && colours == images[i]->colours );
	}

	// Not even sure why this is here!!
	//const Image *reference = Merge( n_images, images );

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
	assert( width == image.width && height == image.height && colours == image.colours );

	Image *result = new Image( width, height, 1 );

	unsigned char *psrc = buffer;
	unsigned char *pref = image.buffer;
	unsigned char *pdiff = result->buffer;

	if ( colours == 1 )
	{
		while( psrc < (buffer+size) )
		{
			//*pdiff++ = abs( *psrc++ - *pref++ );
			*pdiff++ = ABSDIFF( *psrc, *pref );
			psrc++;
			pref++;
		}
	}
	else
	{
		static long red, green, blue;
		while( psrc < (buffer+size) )
		{
			if ( (bool)config.Item( ZM_FAST_RGB_DIFFS ) )
			{
				red = abs(*psrc++ - *pref++);
				green = abs(*psrc++ - *pref++);
				blue = abs(*psrc++ - *pref++);

				// This is uses an RMS function, all floating point and 
				// rather too slow
				//*pdiff++ = (JSAMPLE)sqrt((red*red + green*green + blue*blue)/3);

				// This just uses the average difference, much faster
				*pdiff++ = (JSAMPLE)((red + green + blue)/3);
			}
			else
			{
				red = *psrc++ - *pref++;
				green = *psrc++ - *pref++;
				blue = *psrc++ - *pref++;

				// This is an experimental one which uses the Y part of an RGB
				// to YUV conversion. Should still be integer but a bit slower

				*pdiff++ = abs(((19595*red)>>16) + ((37159*green)>>16) + ((7471*blue)>>16));
				//Info(( "R1:%d, R2:%d, G1:%d, G2:%d, B1:%d, B2:%d, DR: %d, DG: %d, DB: %d, Diff = %d", *(psrc-3), *(pref-3), *(psrc-2), *(pref-2), *(psrc-1), *(pref-1), ((19595*red)>>16), ((37159*green)>>16), ((7471*blue)>>16), *(pdiff-1) ));
			}
		}
	}
	return( result );
}

void Image::Annotate( const char *text, const Coord &coord, const Rgb colour )
{
	int len = strlen( text );
	int text_x = coord.X();
	int text_y = coord.Y();

	if ( text_x > width-(len*CHAR_WIDTH) )
	{
		text_x = width-(len*CHAR_WIDTH);
	}
	if ( text_y > height-CHAR_HEIGHT )
	{
		text_y = height-CHAR_HEIGHT;
	}
	for ( int y = text_y; y < (text_y+CHAR_HEIGHT); y++)
	{
		JSAMPLE *ptr = &buffer[((y*width)+text_x)*3];
		for ( int x = 0; x < len; x++)
		{
			int f = fontdata[text[x] * CHAR_HEIGHT + (y-text_y)];
			for ( int i = CHAR_WIDTH-1; i >= 0; i--)
			{
				if (f & (CHAR_START << i))
				{
					RED(ptr) = RGB_VAL(colour,0);
					GREEN(ptr) = RGB_VAL(colour,1);
					BLUE(ptr) = RGB_VAL(colour,2);
				}
				ptr += colours;
			}
		}
	}
}

void Image::Annotate( const char *text, const Coord &coord )
{
	int len = strlen( text );
	int text_x = coord.X();
	int text_y = coord.Y();

	if ( text_x > width-(len*CHAR_WIDTH) )
	{
		text_x = width-(len*CHAR_WIDTH);
	}
	if ( text_y > height-CHAR_HEIGHT )
	{
		text_y = height-CHAR_HEIGHT;
	}
	for ( int y = text_y; y < (text_y+CHAR_HEIGHT); y++)
	{
		JSAMPLE *ptr = &buffer[((y*width)+text_x)*colours];
		for ( int x = 0; x < len; x++)
		{
			int f = fontdata[text[x] * CHAR_HEIGHT + (y-text_y)];
			for ( int i = CHAR_WIDTH-1; i >= 0; i--)
			{
				if (f & (CHAR_START << i))
				{
					if ( colours == 1 )
					{
						*ptr++ = WHITE;
						continue;
					}
					else
					{
						RED(ptr) = GREEN(ptr) = BLUE(ptr) = WHITE;
						ptr += 3;
						continue;
					}
				}
				else
				{
					if ( colours == 1 )
					{
						*ptr++ = BLACK;
						continue;
					}
					else
					{
						RED(ptr) = GREEN(ptr) = BLUE(ptr) = BLACK;
						ptr += 3;
						continue;
					}
				}
				//ptr += colours;
			}
		}
	}
}

void Image::Timestamp( const char *label, const time_t when, const Coord &coord )
{
	char time_text[64];
	strftime( time_text, sizeof(time_text), "%y/%m/%d %H:%M:%S", localtime( &when ) );
	char text[64];
	if ( label )
	{
		sprintf( text, "%s - %s", label, time_text );
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

void Image::Hatch( Rgb colour, const Box *limits )
{
	assert( colours == 1 || colours == 3 );

	int lo_x = limits?limits->Lo().X():0;
	int lo_y = limits?limits->Lo().Y():0;
	int hi_x = limits?limits->Hi().X():width-1;
	int hi_y = limits?limits->Hi().Y():height-1;
	for ( int y = lo_y; y <= hi_y; y++ )
	{
		unsigned char *p = &buffer[colours*((y*width)+lo_x)];
		for ( int x = lo_x; x <= hi_x; x++, p += colours )
		{

			//if ( ( (x == lo_x || x == hi_x) && (y >= lo_y && y <= hi_y) )
			//|| ( (y == lo_y || y == hi_y) && (x >= lo_x && x <= hi_x) )
			//|| ( (x > lo_x && x < hi_x && y > lo_y && y < hi_y) && !(x%2) && !(y%2) ) )
			if ( ( x == lo_x || x == hi_x || y == lo_y || y == hi_y ) || (!(x%2) && !(y%2) ) )
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

void Image::Fill( Rgb colour, const Box *limits )
{
	assert( colours == 1 || colours == 3 );
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
						d_ptr += width;
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
						d_ptr += (3*width);
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
						d_ptr += width;
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
						d_ptr += (3*width);
					}
				}
			}
			break;
		}
	}
	memcpy( buffer, rotate_buffer, size );
}

void Image::Scale( unsigned int factor )
{
	if ( !factor )
	{
		Error(( "Bogus scale factor %d found", factor ));
		return;
	}
	if ( factor == ZM_SCALE_SCALE )
	{
		return;
	}

	static unsigned char scale_buffer[ZM_MAX_IMAGE_SIZE];
	unsigned int new_width = (width*factor)/ZM_SCALE_SCALE;
	unsigned int new_height = (height*factor)/ZM_SCALE_SCALE;
	if ( factor > ZM_SCALE_SCALE )
	{
		unsigned char *pd = scale_buffer;
		unsigned int wc = width*colours;
		unsigned int nwc = new_width*colours;
		unsigned int h_count = ZM_SCALE_SCALE/2;
		unsigned int last_h_index = 0;
		unsigned int h_index;
		for ( int y = 0; y < height; y++ )
		{
			unsigned char *ps = &buffer[y*wc];
			unsigned int w_count = ZM_SCALE_SCALE/2;
			unsigned int last_w_index = 0;
			unsigned int w_index;
			for ( int x = 0; x < width; x++ )
			{
				w_count += factor;
				w_index = w_count/ZM_SCALE_SCALE;
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
			h_index = h_count/ZM_SCALE_SCALE;
			for ( int f = last_h_index+1; f < h_index; f++ )
			{
				memcpy( pd, pd-nwc, nwc );
				pd += nwc;
			}
			last_h_index = h_index;
		}
	}
	else
	{
		unsigned int inv_factor = (ZM_SCALE_SCALE*ZM_SCALE_SCALE)/factor;
		unsigned char *pd = scale_buffer;
		unsigned int wc = width*colours;
		unsigned int xstart = factor/2;
		unsigned int ystart = factor/2;
		unsigned int h_count = ystart;
		unsigned int last_h_index = 0;
		unsigned int h_index;
		for ( unsigned int y = 0; y < height; y++ )
		{
			h_count += factor;
			h_index = h_count/ZM_SCALE_SCALE;
			if ( h_index > last_h_index )
			{
				unsigned int w_count = xstart;
				unsigned int last_w_index = 0;
				unsigned int w_index;

				unsigned char *ps = &buffer[y*wc];
				for ( unsigned int x = 0; x < width; x++ )
				{
					w_count += factor;
					w_index = w_count/ZM_SCALE_SCALE;
					
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
	}
	width = new_width;
	height = new_height;
	size = width*height*colours;
	delete[] buffer;
	buffer = new JSAMPLE[size];
	memcpy( buffer, scale_buffer, size );
}
