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

#include "zm_image.h"

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
		Error(( "Can't open %s: %s\n", filename, strerror(errno)));
		exit(1);
	}
	jpeg_stdio_src(&cinfo, infile);

	jpeg_read_header(&cinfo, TRUE);

	width = cinfo.image_width;
	height = cinfo.image_height;
	colours = cinfo.num_components;
	size = width*height*colours;

	assert( colours == 1 || colours == 3 );
	delete buffer;
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
	struct jpeg_compress_struct cinfo;
	struct jpeg_error_mgr jerr;
	cinfo.err = jpeg_std_error(&jerr);
	jpeg_create_compress(&cinfo);

	FILE *outfile;
	if ((outfile = fopen(filename, "wb" )) == NULL)
	{
		Error(( "Can't open %s: %s\n", filename, strerror(errno)));
		exit(1);
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
	jpeg_set_quality(&cinfo, ZM_JPEG_FILE_QUALITY, false);
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
	delete buffer;
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
	jpeg_set_quality(&cinfo, ZM_JPEG_IMAGE_QUALITY, false);
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

	JSAMPLE *psrc = image.buffer;
	JSAMPLE *pdest = buffer;

	while( pdest < (buffer+size) )
	{
		*pdest++ = (JSAMPLE)(((*pdest * (100-transparency))+(*psrc++ * transparency))/100);
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

	const Image *reference = Merge( n_images, images );

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

				if ( abs((*psrc)-RGB_VAL(ref_colour,c)) >= RGB_VAL(threshold,c) )
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

Image *Image::Delta( const Image &image, bool absolute ) const
{
	assert( width == image.width && height == image.height && colours == image.colours );

	Image *result = new Image( width, height, 1 );

	typedef JSAMPLE IMAGE[width][height][colours];
	IMAGE &data = reinterpret_cast<IMAGE &>(*buffer);
	IMAGE &image_data = reinterpret_cast<IMAGE &>(*image.buffer);
	IMAGE &diff_data = reinterpret_cast<IMAGE &>(*result->buffer);

	unsigned char *psrc = buffer;
	unsigned char *pref = image.buffer;
	unsigned char *pdiff = result->buffer;

	if ( colours == 1 )
	{
		if ( absolute )
		{
			while( psrc < (buffer+size) )
			{
				*pdiff++ = abs( *psrc++ - *pref++ );
			}
		}
		else
		{
			while( psrc < (buffer+size) )
			{
				*pdiff++ = *psrc++ - *pref++;
			}
		}
	}
	else
	{
		if ( absolute )
		{
			while( psrc < (buffer+size) )
			{
				int red = abs(*psrc++ - *pref++);
				int green = abs(*psrc++ - *pref++);
				int blue = abs(*psrc++ - *pref++);
				//*pdiff++ = (JSAMPLE)sqrt((red*red + green*green + blue*blue)/3);
				*pdiff++ = (JSAMPLE)((red + green + blue)/3);
			}
		}
		else
		{
			while( psrc < (buffer+size) )
			{
				int red = *psrc++ - *pref++;
				int green = *psrc++ - *pref++;
				int blue = *psrc++ - *pref++;
				*pdiff++ = 127+((int(red+green+blue))/(3*2));
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
	unsigned char *p = buffer;
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

