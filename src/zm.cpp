#include "zm.h"

MYSQL dbconn;

void Zone::Setup( int p_id, const char *p_label, ZoneType p_type, const Box &p_limits, const Rgb p_alarm_rgb, int p_alarm_threshold, int p_min_alarm_pixels, int p_max_alarm_pixels, const Coord &p_filter_box, int p_min_filter_pixels, int p_max_filter_pixels, int p_min_blob_pixels, int p_max_blob_pixels, int p_min_blobs, int p_max_blobs )
{
	id = p_id;
	label = new char[strlen(p_label)+1];
	strcpy( label, p_label );
	type = p_type;
	limits = p_limits;
	alarm_rgb = p_alarm_rgb;
	alarm_threshold = p_alarm_threshold;
	min_alarm_pixels = p_min_alarm_pixels;
	max_alarm_pixels = p_max_alarm_pixels;
	filter_box = p_filter_box;
	min_filter_pixels = p_min_filter_pixels;
	max_filter_pixels = p_max_filter_pixels;
	min_blob_pixels = p_min_blob_pixels;
	max_blob_pixels = p_max_blob_pixels;
	min_blobs = p_min_blobs;
	max_blobs = p_max_blobs;

	Info(( "Initialised zone %d/%s - %d - %dx%d - Rgb:%06x, AT:%d, MnAP:%d, MxAP:%d, FB:%dx%d, MnFP:%d, MxFP:%d, MnBS:%d, MxBS:%d, MnB:%d, MxB:%d\n", id, label, type, limits.Width(), limits.Height(), alarm_rgb, alarm_threshold, min_alarm_pixels, max_alarm_pixels, filter_box.X(), filter_box.Y(), min_filter_pixels, max_filter_pixels, min_blob_pixels, max_blob_pixels, min_blobs, max_blobs ));

	alarmed = false;
	alarm_pixels = 0;
	alarm_filter_pixels = 0;
	alarm_blobs = 0;
	image = 0;
	score = 0;
}

Zone::~Zone()
{
	delete[] label;
	delete image;
}

int Zone::Load( int monitor_id, int width, int height, Zone **&zones )
{
	static char sql[256];
	sprintf( sql, "select Id,Name,Type+0,Units,LoX,LoY,HiX,HiY,AlarmRGB,AlarmThreshold,MinAlarmPixels,MaxAlarmPixels,FilterX,FilterY,MinFilterPixels,MaxFilterPixels,MinBlobPixels,MaxBlobPixels,MinBlobs,MaxBlobs from Zones where MonitorId = %d order by Type, Id", monitor_id );
	if ( mysql_query( &dbconn, sql ) )
	{
		Error(( "Can't run query: %s\n", mysql_error( &dbconn ) ));
		exit( mysql_errno( &dbconn ) );
	}

	MYSQL_RES *result = mysql_store_result( &dbconn );
	if ( !result )
	{
		Error(( "Can't use query result: %s\n", mysql_error( &dbconn ) ));
		exit( mysql_errno( &dbconn ) );
	}
	int n_zones = mysql_num_rows( result );
	Info(( "Got %d zones for monitor %d\n", n_zones, monitor_id ));
	delete[] zones;
	zones = new Zone *[n_zones];
	for( int i = 0; MYSQL_ROW dbrow = mysql_fetch_row( result ); i++ )
	{
		int Id = atoi(dbrow[0]);
		const char *Name = dbrow[1];
		int Type = atoi(dbrow[2]);
		const char *Units = dbrow[3];
		int LoX = atoi(dbrow[4]);
		int LoY = atoi(dbrow[5]);
		int HiX = atoi(dbrow[6]);
		int HiY = atoi(dbrow[7]);
		int AlarmRGB = dbrow[8]?atoi(dbrow[8]):0;
		int AlarmThreshold = dbrow[9]?atoi(dbrow[9]):0;
		int MinAlarmPixels = dbrow[10]?atoi(dbrow[10]):0;
		int MaxAlarmPixels = dbrow[11]?atoi(dbrow[11]):0;
		int FilterX = dbrow[12]?atoi(dbrow[12]):0;
		int FilterY = dbrow[13]?atoi(dbrow[13]):0;
		int MinFilterPixels = dbrow[14]?atoi(dbrow[14]):0;
		int MaxFilterPixels = dbrow[15]?atoi(dbrow[15]):0;
		int MinBlobPixels = dbrow[16]?atoi(dbrow[16]):0;
		int MaxBlobPixels = dbrow[17]?atoi(dbrow[17]):0;
		int MinBlobs = dbrow[18]?atoi(dbrow[18]):0;
		int MaxBlobs = dbrow[19]?atoi(dbrow[19]):0;

		if ( !strcmp( Units, "Percent" ) )
		{
			LoX = (LoX*(width-1))/100;
			LoY = (LoY*(height-1))/100;
			HiX = (HiX*(width-1))/100;
			HiY = (HiY*(height-1))/100;
			MinAlarmPixels = (MinAlarmPixels*width*height)/100;
			MaxAlarmPixels = (MaxAlarmPixels*width*height)/100;
			MinFilterPixels = (MinFilterPixels*width*height)/100;
			MaxFilterPixels = (MaxFilterPixels*width*height)/100;
			MinBlobPixels = (MinBlobPixels*width*height)/100;
			MaxBlobPixels = (MaxBlobPixels*width*height)/100;
		}

		if ( atoi(dbrow[2]) == Zone::INACTIVE )
		{
			zones[i] = new Zone( Id, Name, Box( LoX, LoY, HiX, HiY ) );
		}
		else
		{
			zones[i] = new Zone( Id, Name, (Zone::ZoneType)Type, Box( LoX, LoY, HiX, HiY ), AlarmRGB, AlarmThreshold, MinAlarmPixels, MaxAlarmPixels, Coord( FilterX, FilterY ), MinFilterPixels, MaxFilterPixels, MinBlobPixels, MaxBlobPixels, MinBlobs, MaxBlobs );
		}
	}
	if ( mysql_errno( &dbconn ) )
	{
		Error(( "Can't fetch row: %s\n", mysql_error( &dbconn ) ));
		exit( mysql_errno( &dbconn ) );
	}
	// Yadda yadda
	mysql_free_result( result );
	return( n_zones );
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
	//jpeg_set_quality(&cinfo, 100, false);
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
	//jpeg_set_quality(&cinfo, 100, false);
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

unsigned int Image::CheckAlarms( Zone *zone, const Image *delta_image ) const
{
	bool alarm = false;
	unsigned int score = 0;

	delete zone->image;
	Image *diff_image = zone->image = new Image( *delta_image );

	int alarm_pixels = 0;

	int lo_x = zone->limits.Lo().X();
	int lo_y = zone->limits.Lo().Y();
	int hi_x = zone->limits.Hi().X();
	int hi_y = zone->limits.Hi().Y();
	for ( int y = lo_y; y <= hi_y; y++ )
	{
		unsigned char *pdiff = &diff_image->buffer[(y*diff_image->width)+lo_x];
		for ( int x = lo_x; x <= hi_x; x++, pdiff++ )
		{
			if ( *pdiff > zone->alarm_threshold )
			{
				*pdiff = WHITE;
				alarm_pixels++;
				continue;
			}
			*pdiff = BLACK;
		}
	}

	//diff_image->WriteJpeg( "diff1.jpg" );

	if ( !alarm_pixels ) return( false );
	if ( zone->min_alarm_pixels && alarm_pixels < zone->min_alarm_pixels ) return( false );
	if ( zone->max_alarm_pixels && alarm_pixels > zone->max_alarm_pixels ) return( false );

	int filter_pixels = 0;

	int bx = zone->filter_box.X();
	int by = zone->filter_box.Y();
	int bx1 = bx-1;
	int by1 = by-1;

	// Now eliminate all pixels that don't participate in a blob
	for ( int y = lo_y; y <= hi_y; y++ )
	{
		unsigned char *pdiff = &diff_image->buffer[(y*diff_image->width)+lo_x];

		for ( int x = lo_x; x <= hi_x; x++, pdiff++ )
		{
			if ( *pdiff == WHITE )
			{
				if ( 0 )
				{
				int count;
				int dx;
				// Check participation in an X blob
				int ldx = (x>=(lo_x+bx1))?-bx1:lo_x-x;
				int hdx = (x<=(hi_x-bx1))?bx1:hi_x-x;
				for ( count = 0, dx = ldx; count < bx && dx <= hdx; dx++ )
				{
					count = (*(pdiff+dx) == WHITE)?count+1:0;
				}
				if ( count < bx )
				{
					*pdiff = BLACK;
					continue;
				}
				int dy;
				// Check participation in a Y blob
				int ldy = (y>=(lo_y+by1))?-by1:lo_y-y;
				int hdy = (y<=(hi_y-by1))?by1:hi_y-y;
				for ( count = 0, dy = ldy; count < by && dy <= hdy; dy++ )
				{
					count = (*(pdiff+(diff_image->width*dy)) == WHITE)?count+1:0;
				}
				if ( count < by )
				{
					*pdiff = BLACK;
					continue;
				}
				filter_pixels++;
				}
				else
				{
				// Check participation in an X blob
				int ldx = (x>=(lo_x+bx1))?-bx1:lo_x-x;
				int hdx = (x<=(hi_x-bx1))?0:((hi_x-x)-bx1);
				int ldy = (y>=(lo_y+by1))?-by1:lo_y-y;
				int hdy = (y<=(hi_y-by1))?0:((hi_y-y)-by1);
				bool blob = false;
				for ( int dy = ldy; !blob && dy <= hdy; dy++ )
				{
					for ( int dx = ldx; !blob && dx <= hdx; dx++ )
					{
						blob = true;
						for ( int dy2 = 0; blob && dy2 < by; dy2++ )
						{
							for ( int dx2 = 0; blob && dx2 < bx; dx2++ )
							{
								unsigned char *cpdiff = &diff_image->buffer[((y+dy+dy2)*diff_image->width)+x+dx+dx2];

								if ( !*cpdiff )
								{
									blob = false;
								}
								
							}
						}
					}
				}
				if ( !blob )
				{
					*pdiff = BLACK;
					continue;
				}
				filter_pixels++;
				}
			}
		}
	}

	//diff_image->WriteJpeg( "diff2.jpg" );

	if ( !filter_pixels ) return( false );
	if ( zone->min_filter_pixels && filter_pixels < zone->min_filter_pixels ) return( false );
	if ( zone->max_filter_pixels && filter_pixels > zone->max_filter_pixels ) return( false );

	int blobs = 0;

	typedef struct { unsigned char tag; int count; int lo_x; int hi_x; int lo_y; int hi_y; } BlobStats;
	BlobStats blob_stats[256];
	memset( blob_stats, 0, sizeof(BlobStats)*256 );
	//printf( "%x\n", diff_image->buffer );
	for ( int y = lo_y; y <= hi_y; y++ )
	{
		unsigned char *pdiff = &diff_image->buffer[(y*diff_image->width)+lo_x];
		for ( int x = lo_x; x <= hi_x; x++, pdiff++ )
		{
			if ( *pdiff == WHITE )
			{
				//printf( "Got white pixel at %d,%d (%x)\n", x, y, pdiff );
				int lx = x>lo_x?*(pdiff-1):0;
				int ly = y>lo_y?*(pdiff-diff_image->width):0;

				if ( lx )
				{
					//printf( "Left neighbour is %d\n", lx );
					BlobStats *bsx = &blob_stats[lx];

					if ( ly )
					{
						//printf( "Top neighbour is %d\n", ly );
						BlobStats *bsy = &blob_stats[ly];

						if ( lx == ly )
						{
							//printf( "Matching neighbours, setting to %d\n", lx );
							// Add to the blob from the x side (either side really)
							*pdiff = lx;
							bsx->count++;
							//if ( x < bsx->lo_x ) bsx->lo_x = x;
							//if ( y < bsx->lo_y ) bsx->lo_y = y;
							if ( x > bsx->hi_x ) bsx->hi_x = x;
							if ( y > bsx->hi_y ) bsx->hi_y = y;
						}
						else
						{
							// Amortise blobs
							BlobStats *bsm = bsx->count>=bsy->count?bsx:bsy;
							BlobStats *bss = bsm==bsx?bsy:bsx;

							//printf( "Different neighbours, setting pixels of %d to %d\n", bss->tag, bsm->tag );
							// Now change all those pixels to the other setting
							for ( int sy = bss->lo_y; sy <= bss->hi_y; sy++ )
							{
								unsigned char *spdiff = &diff_image->buffer[(sy*diff_image->width)+bss->lo_x];
								for ( int sx = bss->lo_x; sx <= bss->hi_x; sx++, spdiff++ )
								{
									//printf( "Pixel at %d,%d (%x) is %d", sx, sy, spdiff, *spdiff );
									if ( *spdiff == bss->tag )
									{
										//printf( ", setting" );
										*spdiff = bsm->tag;
									}
									//printf( "\n" );
								}
							}
							*pdiff = bsm->tag;

							// Merge the slave blob into the master
							bsm->count += bss->count+1;
							if ( x > bsm->hi_x ) bsm->hi_x = x;
							if ( y > bsm->hi_y ) bsm->hi_y = y;
							if ( bss->lo_x < bsm->lo_x ) bsm->lo_x = bss->lo_x;
							if ( bss->lo_y < bsm->lo_y ) bsm->lo_y = bss->lo_y;
							if ( bss->hi_x > bsm->hi_x ) bsm->hi_x = bss->hi_x;
							if ( bss->hi_y > bsm->hi_y ) bsm->hi_y = bss->hi_y;

							// Clear out the old blob
							bss->tag = 0;
							bss->count = 0;
							bss->lo_x = 0;
							bss->lo_y = 0;
							bss->hi_x = 0;
							bss->hi_y = 0;

							blobs--;
						}
					}
					else
					{
						//printf( "Setting to left neighbour %d\n", lx );
						// Add to the blob from the x side 
						*pdiff = lx;
						bsx->count++;
						//if ( x < bsx->lo_x ) bsx->lo_x = x;
						//if ( y < bsx->lo_y ) bsx->lo_y = y;
						if ( x > bsx->hi_x ) bsx->hi_x = x;
						if ( y > bsx->hi_y ) bsx->hi_y = y;
					}
				}
				else
				{
					if ( ly )
					{
						//printf( "Setting to top neighbour %d\n", ly );

						// Add to the blob from the y side
						BlobStats *bsy = &blob_stats[ly];

						*pdiff = ly;
						bsy->count++;
						//if ( x < bsy->lo_x ) bsy->lo_x = x;
						//if ( y < bsy->lo_y ) bsy->lo_y = y;
						if ( x > bsy->hi_x ) bsy->hi_x = x;
						if ( y > bsy->hi_y ) bsy->hi_y = y;
					}
					else
					{
						// Create a new blob
						for ( int i = 1; i < WHITE; i++ )
						{
							BlobStats *bs = &blob_stats[i];
							if ( !bs->count )
							{
								//printf( "Creating new blob %d\n", i );
								*pdiff = i;
								bs->tag = i;
								bs->count++;
								bs->lo_x = bs->hi_x = x;
								bs->lo_y = bs->hi_y = y;
								blobs++;
								break;
							}
						}
					}
				}

			}
		}
	}

	//diff_image->WriteJpeg( "diff3.jpg" );

	if ( !blobs ) return( false );
	int blob_pixels = filter_pixels;

	// Now eliminate blobs under the alarm_threshold
	for ( int i = 1; i < WHITE; i++ )
	{
		BlobStats *bs = &blob_stats[i];
		if ( bs->count && ((zone->min_blob_pixels && bs->count < zone->min_blob_pixels) || (zone->max_blob_pixels && bs->count > zone->max_blob_pixels)) )
		{
			//Info(( "Eliminating blob %d, %d pixels (%d,%d - %d,%d)\n", i, bs->count, bs->lo_x, bs->lo_y, bs->hi_x, bs->hi_y ));
			for ( int sy = bs->lo_y; sy <= bs->hi_y; sy++ )
			{
				unsigned char *spdiff = &diff_image->buffer[(sy*diff_image->width)+bs->lo_x];
				for ( int sx = bs->lo_x; sx <= bs->hi_x; sx++, spdiff++ )
				{
					if ( *spdiff == bs->tag )
					{
						*spdiff = BLACK;
					}
				}
			}
			blobs--;
			blob_pixels -= bs->count;
			
			bs->tag = 0;
			bs->count = 0;
			bs->lo_x = 0;
			bs->lo_y = 0;
			bs->hi_x = 0;
			bs->hi_y = 0;
		}
	}

	if ( !blobs ) return( false );
	if ( zone->min_blobs && blobs < zone->min_blobs ) return( false );
	if ( zone->max_blobs && blobs > zone->max_blobs ) return( false );

	int alarm_lo_x = hi_x+1;
	int alarm_hi_x = lo_x-1;
	int alarm_lo_y = hi_y+1;
	int alarm_hi_y = lo_y-1;
	for ( int i = 1; i < WHITE; i++ )
	{
		BlobStats *bs = &blob_stats[i];
		if ( bs->count )
		{
			if ( alarm_lo_x > bs->lo_x ) alarm_lo_x = bs->lo_x;
			if ( alarm_lo_y > bs->lo_y ) alarm_lo_y = bs->lo_y;
			if ( alarm_hi_x < bs->hi_x ) alarm_hi_x = bs->hi_x;
			if ( alarm_hi_y < bs->hi_y ) alarm_hi_y = bs->hi_y;
		}
	}

	zone->alarm_blobs = blobs;
	zone->alarm_pixels = alarm_pixels;
	zone->alarm_filter_pixels = filter_pixels;
	zone->alarm_box = Box( Coord( alarm_lo_x, alarm_lo_y ), Coord( alarm_hi_x, alarm_hi_y ) );
	score = zone->score = ((100*blob_pixels)/blobs)/(zone->limits.Size().X()*zone->limits.Size().Y());
	if ( zone->Type() == Zone::INCLUSIVE )
	{
		zone->score /= 2;
	}
	else if ( zone->Type() == Zone::EXCLUSIVE )
	{
		zone->score *= 2;
	}
	//Info(( "%d - %d - %d - %.2f\n", zone->alarm_blobs, zone->alarm_pixels, zone->alarm_filter_pixels, zone->result ));

	// Now outline the changed region
	if ( zone->alarm_blobs )
	{
		Image *high_image = zone->image = new Image( *diff_image );

		high_image->Colourise();
		alarm = true;
		memset( high_image->buffer, 0, high_image->size );
		for ( int y = lo_y; y <= hi_y; y++ )
		{
			unsigned char *pdiff = &diff_image->buffer[(y*diff_image->width)+lo_x];
			unsigned char *phigh = &high_image->buffer[3*((y*high_image->width)+lo_x)];
			for ( int x = lo_x; x <= hi_x; x++, pdiff++, phigh += 3 )
			{
				bool edge = false;
				if ( *pdiff )
				{
					if ( !edge && x > 0 && !*(pdiff-1) ) edge = true;
					if ( !edge && x < (diff_image->width-1) && !*(pdiff+1) ) edge = true;
					if ( !edge && y > 0 && !*(pdiff-diff_image->width) ) edge = true;
					if ( !edge && y < (diff_image->height-1) && !*(pdiff+diff_image->width) ) edge = true;
				}
				if ( edge )
				{
					RED(phigh) = RGB_RED_VAL(zone->alarm_rgb);
					GREEN(phigh) = RGB_GREEN_VAL(zone->alarm_rgb);
					BLUE(phigh) = RGB_BLUE_VAL(zone->alarm_rgb);
				}
			}
		}
		delete diff_image;
		//high_image->WriteJpeg( "diff4.jpg" );

		Info(( "%s: Alarm Pixels: %d, Filter Pixels: %d, Blobs: %d, Score: %d\n", zone->Label(), alarm_pixels, filter_pixels, blobs, score ));
	}
	return( score );
}

unsigned int Image::Compare( const Image &image, int n_zones, Zone *zones[] ) const
{
	bool alarm = false;
	unsigned int score = 0;

	if ( n_zones <= 0 ) return( alarm );

	const Image *delta_image = Delta( image );

	// Blank out all exclusion zones
	unsigned char *psrc = buffer;
	for ( int n_zone = 0; n_zone < n_zones; n_zone++ )
	{
		Zone *zone = zones[n_zone];
		zone->alarmed = false;
		if ( zone->Type() != Zone::INACTIVE )
		{
			continue;
		}

		int lo_x = zone->limits.Lo().X();
		int lo_y = zone->limits.Lo().Y();
		int hi_x = zone->limits.Hi().X();
		int hi_y = zone->limits.Hi().Y();
		for ( int y = lo_y; y <= hi_y; y++ )
		{
			unsigned char *pdelta = &delta_image->buffer[(y*delta_image->width)];
			for ( int x = lo_x; x <= hi_x; x++ )
			{
				*pdelta++ = BLACK;
			}
		}
	}

	unsigned int zone_score = 0;

	// Find all alarm pixels in active zones
	for ( int n_zone = 0; n_zone < n_zones; n_zone++ )
	{
		Zone *zone = zones[n_zone];
		if ( zone->Type() != Zone::ACTIVE )
		{
			continue;
		}
		if ( zone_score = CheckAlarms( zone, delta_image ) )
		{
			alarm = true;
			score += zone_score;
			zone->alarmed = true;
		}
	}

	if ( alarm )
	{
		// Find all alarm pixels in inclusion zones
		for ( int n_zone = 0; n_zone < n_zones; n_zone++ )
		{
			Zone *zone = zones[n_zone];
			if ( zone->Type() != Zone::INCLUSIVE )
			{
				continue;
			}
			if ( zone_score = CheckAlarms( zone, delta_image ) )
			{
				alarm = true;
				score += zone_score;
				zone->alarmed = true;
			}
		}
	}
	else
	{
		// Find all alarm pixels in exclusion zones
		for ( int n_zone = 0; n_zone < n_zones; n_zone++ )
		{
			Zone *zone = zones[n_zone];
			if ( zone->Type() != Zone::EXCLUSIVE )
			{
				continue;
			}
			if ( zone_score = CheckAlarms( zone, delta_image ) )
			{
				alarm = true;
				score += zone_score;
				zone->alarmed = true;
			}
		}
	}

	delete delta_image;
	return( score );
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

void Image::Timestamp( const char *label, time_t when, const Coord &coord )
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

Camera::Camera( int p_id, char *p_name, int p_device, int p_channel, int p_format, int p_width, int p_height, int p_colours, bool p_capture=true ) : id( p_id ), device( p_device ), channel( p_channel ), format( p_format ), width( p_width), height( p_height ), colours( p_colours ), capture( p_capture )
{
	name = new char[strlen(p_name)+1];
	strcpy( name, p_name );
	if ( !camera_count++ && capture )
	{
		Initialise( device, channel, format, width, height, colours );

	}
}

Camera::~Camera()
{
	if ( !--camera_count && capture )
	{
		Terminate();
	}
}

void Camera::Initialise( int device, int channel, int format, int width, int height, int colours )
{
	int m_ret;
	char device_path[64];

	sprintf( device_path, "/dev/video%d", device );
	if( (m_videohandle=open(device_path, O_RDONLY)) <=0 )
	{
		Error(( "Failed to open video device %s: %s\n", device_path, strerror(errno) ));
		exit(-1);
	}

	struct video_window vid_win;
	if( !ioctl( m_videohandle, VIDIOCGWIN, &vid_win))
	{
		Info(( "X:%d\n", vid_win.x ));
		Info(( "Y:%d\n", vid_win.y ));
		Info(( "W:%d\n", vid_win.width ));
		Info(( "H:%d\n", vid_win.height ));
	}
	else
	{
		Error(( "Failed to get window attributes: %s\n", strerror(errno) ));
		exit(-1);
	}
	vid_win.x = 0;
	vid_win.y = 0;
	vid_win.width = width;
	vid_win.height = height;

	if( ioctl( m_videohandle, VIDIOCSWIN, &vid_win ) )
	{
		Error(( "Failed to set window attributes: %s\n", strerror(errno) ));
		exit(-1);
	}

	struct video_picture vid_pic;
	if( !ioctl( m_videohandle, VIDIOCGPICT, &vid_pic))
	{
		Info(( "P:%d\n", vid_pic.palette ));
		Info(( "D:%d\n", vid_pic.depth ));
		Info(( "B:%d\n", vid_pic.brightness ));
		Info(( "h:%d\n", vid_pic.hue ));
		Info(( "Cl:%d\n", vid_pic.colour ));
		Info(( "Cn:%d\n", vid_pic.contrast ));
	}
	else
	{
		Error(( "Failed to get picture attributes: %s\n", strerror(errno) ));
		exit(-1);
	}

	if ( colours == 1 )
	{
		vid_pic.palette = VIDEO_PALETTE_GREY;
		vid_pic.depth = 8;
	}
	else
	{
		vid_pic.palette = VIDEO_PALETTE_RGB24;
		vid_pic.depth = 24;
	}

	if( ioctl( m_videohandle, VIDIOCSPICT, &vid_pic ) )
	{
		Error(( "Failed to set picture attributes: %s\n", strerror(errno) ));
		exit(-1);
	}
	if(!ioctl(m_videohandle, VIDIOCGMBUF, &m_vmb))
	{
		m_vmm = new video_mmap[m_vmb.frames];
		Info(( "vmb.frames = %d\n", m_vmb.frames ));
		Info(( "vmb.size = %d\n", m_vmb.size ));
	}
	else
	{
		Error(( "Failed to setup memory: %s\n", strerror(errno) ));
		exit(-1);
	}

	for(int loop=0; loop < m_vmb.frames; loop++)
	{
		m_vmm[loop].frame = loop;
		m_vmm[loop].width = width;
		m_vmm[loop].height = height;
		m_vmm[loop].format = (colours==1?VIDEO_PALETTE_GREY:VIDEO_PALETTE_RGB24);
	}

	m_buffer = (unsigned char *)mmap(0, m_vmb.size, PROT_READ, MAP_SHARED, m_videohandle,0);
	if( !((long)m_buffer > 0) )
	{
		Error(( "Could not mmap video: %s", strerror(errno) ));
		exit(-1);
	}

	struct video_channel vs;

	vs.channel = channel;
	//vs.norm = VIDEO_MODE_AUTO;
	vs.norm = format;
	vs.flags = 0;
	vs.type = VIDEO_TYPE_CAMERA;
	if(ioctl(m_videohandle, VIDIOCSCHAN, &vs))
	{
		Error(( "Failed to set camera source %d: %s\n", channel, strerror(errno) ));
		exit(-1);
	}

	if( !ioctl( m_videohandle, VIDIOCGWIN, &vid_win))
	{
		Info(( "X:%d\n", vid_win.x ));
		Info(( "Y:%d\n", vid_win.y ));
		Info(( "W:%d\n", vid_win.width ));
		Info(( "H:%d\n", vid_win.height ));
	}
	else
	{
		Error(( "Failed to get window data: %s\n", strerror(errno) ));
		exit(-1);
	}

	if( !ioctl( m_videohandle, VIDIOCGPICT, &vid_pic))
	{
		Info(( "P:%d\n", vid_pic.palette ));
		Info(( "D:%d\n", vid_pic.depth ));
		Info(( "B:%d\n", vid_pic.brightness ));
		Info(( "h:%d\n", vid_pic.hue ));
		Info(( "Cl:%d\n", vid_pic.colour ));
		Info(( "Cn:%d\n", vid_pic.contrast ));
	}
	else
	{
		Error(( "Failed to get window data: %s\n", strerror(errno) ));
		exit(-1);
	}
}

void Camera::Terminate()
{
	munmap((char*)m_buffer, m_vmb.size);

	delete[] m_vmm;

	close(m_videohandle);
}

int Camera::m_cap_frame = 0;
int Camera::m_sync_frame = 0;
video_mbuf Camera::m_vmb;
video_mmap *Camera::m_vmm;
int Camera::m_videohandle;
unsigned char *Camera::m_buffer=0;
int Camera::camera_count = 0;

Event::Event( Monitor *p_monitor, time_t p_start_time ) : monitor( p_monitor ), start_time( p_start_time )
{
	static char sql[256];
	sprintf( sql, "insert into Events set MonitorId=%d, Name='Event', StartTime=from_unixtime(%d)", monitor->Id(), start_time );
	if ( mysql_query( &dbconn, sql ) )
	{
		Error(( "Can't insert event: %s\n", mysql_error( &dbconn ) ));
		exit( mysql_errno( &dbconn ) );
	}
	id = mysql_insert_id( &dbconn );
	start_frame_id = 0;
	end_frame_id = 0;
	end_time = 0;
	frames = 0;
	alarm_frames = 0;
	tot_score = 0;
	max_score = 0;
	sprintf( path, EVENT_DIR "/%s/%04d", monitor->Name(), id );
	
	struct stat statbuf;
	errno = 0;
	stat( path, &statbuf );
	if ( errno == ENOENT || errno == ENOTDIR )
	{
		if ( mkdir( path, 0755 ) )
		{
			Error(( "Can't make %s: %s\n", path, strerror(errno)));
		}
	}
}

Event::~Event()
{
	static char sql[256];
	sprintf( sql, "update Events set Name='Event-%d', EndTime = now(), Length = %d, Frames = %d, AlarmFrames = %d, AvgScore = %d, MaxScore = %d where Id = %d", id, (end_time-start_time), frames, alarm_frames, (int)(tot_score/alarm_frames), max_score, id );
	if ( mysql_query( &dbconn, sql ) )
	{
		Error(( "Can't update event: %s\n", mysql_error( &dbconn ) ));
		exit( mysql_errno( &dbconn ) );
	}
}

void Event::AddFrame( time_t timestamp, const Image *image, const Image *alarm_image, unsigned int score )
{
	frames++;

	static char event_file[PATH_MAX];
	sprintf( event_file, "%s/capture-%03d.jpg", path, frames );
	image->WriteJpeg( event_file );

	static char sql[256];
	sprintf( sql, "insert into Frames set EventId=%d, FrameId=%d, AlarmFrame=%d, ImagePath='%s', TimeStamp=from_unixtime(%d), Score=%d", id, frames, alarm_image!=0, event_file, timestamp, score );
	if ( mysql_query( &dbconn, sql ) )
	{
		Error(( "Can't insert frame: %s\n", mysql_error( &dbconn ) ));
		exit( mysql_errno( &dbconn ) );
	}
	end_frame_id = mysql_insert_id( &dbconn );
	if ( !start_frame_id ) start_frame_id = end_frame_id;
	end_time = timestamp;
	if ( !start_time ) start_time = end_time;

	if ( alarm_image )
	{
		alarm_frames++;
		sprintf( event_file, "%s/analyse-%03d.jpg", path, frames );
		alarm_image->WriteJpeg( event_file );
		tot_score += score;
		if ( score > max_score )
			max_score = score;
	}
}

void Event::StreamEvent( const char *path, int event_id, unsigned long refresh=100, FILE *fd=stdout )
{
	static char sql[256];
	sprintf( sql, "select Id, EventId, ImagePath, TimeStamp from Frames where EventId = %d order by Id", event_id );
	if ( mysql_query( &dbconn, sql ) )
	{
		Error(( "Can't run query: %s\n", mysql_error( &dbconn ) ));
		exit( mysql_errno( &dbconn ) );
	}

	MYSQL_RES *result = mysql_store_result( &dbconn );
	if ( !result )
	{
		Error(( "Can't use query result: %s\n", mysql_error( &dbconn ) ));
		exit( mysql_errno( &dbconn ) );
	}

	fprintf( fd, "Server: ZoneMinder Stream Server\r\n" );
	fprintf( fd, "Content-Type: multipart/x-mixed-replace;boundary=ZoneMinderFrame\r\n" );
	fprintf( fd, "\r\n" );
	fprintf( fd, "--ZoneMinderFrame\n" );

	int n_frames = mysql_num_rows( result );
	Info(( "Got %d frames\n", n_frames ));
	FILE *fdj = NULL;
	int n_bytes = 0;
	static unsigned char buffer[0x10000];
	for( int i = 0; MYSQL_ROW dbrow = mysql_fetch_row( result ); i++ )
	{
		char filepath[PATH_MAX];
		sprintf( filepath, "%s/%s", path, dbrow[2] );
		if ( fdj = fopen( filepath, "r" ) )
		{
			fprintf( fd, "Content-type: image/jpg\n\n" );
			while ( n_bytes = fread( buffer, 1, sizeof(buffer), fdj ) )
			{
				fwrite( buffer, 1, n_bytes, fd );
			}
			fprintf( fd, "\n--ZoneMinderFrame\n" );
			fflush( fd );
			fclose( fdj );
		}
		else
		{
			Error(( "Can't open %s: %s", filepath, strerror(errno) ));
		}
		usleep( refresh*1000 );
	}
	if ( mysql_errno( &dbconn ) )
	{
		Error(( "Can't fetch row: %s\n", mysql_error( &dbconn ) ));
		exit( mysql_errno( &dbconn ) );
	}
	// Yadda yadda
	mysql_free_result( result );
}

Monitor::Monitor( int p_id, char *p_name, int p_function, int p_device, int p_channel, int p_format, int p_width, int p_height, int p_colours, bool p_capture, int p_n_zones, Zone *p_zones[] ) : Camera( p_id, p_name, p_device, p_channel, p_format, p_width, p_height, p_colours, p_capture ), function( (Function)p_function ), image( p_width, p_height, p_colours ), ref_image( p_width, p_height, p_colours ), n_zones( p_n_zones ), zones( p_zones )
{
	fps = 0.0;
	event_count = 0;
	image_count = 0;
	first_alarm_count = 0;
	last_alarm_count = 0;
	state = IDLE;

	int shared_images_size = sizeof(SharedImages)+(IMAGE_BUFFER_COUNT*sizeof(time_t))+(IMAGE_BUFFER_COUNT*colours*width*height);
	int shmid = shmget( 0xcf00cf00|id, shared_images_size, IPC_CREAT|0777 );
	if ( shmid < 0 )
	{
		Error(( "Can't shmget: %s\n", strerror(errno)));
		exit( -1 );
	}
	unsigned char *shm_ptr = (unsigned char *)shmat( shmid, 0, 0 );
	shared_images = (SharedImages *)shm_ptr;
	if ( shared_images < 0 )
	{
		Error(( "Can't shmat: %s\n", strerror(errno)));
		exit( -1 );
	}

	//if ( shmctl( shmid, IPC_RMID, 0 ) )
	//{
		//Error(( "Can't shmctl: %s\n", strerror(errno)));
		//exit( -1 );
	//}

	if ( capture )
	{
		memset( shared_images, 0, shared_images_size );
		shared_images->state = IDLE;
		shared_images->last_write_index = IMAGE_BUFFER_COUNT;
		shared_images->last_read_index = IMAGE_BUFFER_COUNT;
	}
	shared_images->timestamps = (time_t *)(shm_ptr+sizeof(SharedImages));
	shared_images->images = (unsigned char *)(shm_ptr+sizeof(SharedImages)+(IMAGE_BUFFER_COUNT*sizeof(time_t)));

	image_buffer = new Snapshot[IMAGE_BUFFER_COUNT];
	for ( int i = 0; i < IMAGE_BUFFER_COUNT; i++ )
	{
		image_buffer[i].timestamp = &(shared_images->timestamps[i]);
		image_buffer[i].image = new Image( width, height, colours, &(shared_images->images[i*colours*width*height]) );
		//Info(( "%d: %x - %x", i, image_buffer[i].image, image_buffer[i].image->buffer ));
		//*(image_buffer[i].timestamp) = time( 0 );
		//image_buffer[i].image = new Image( width, height, colours );
		//delete[] image_buffer[i].image->buffer;
		//image_buffer[i].image->buffer = &(shared_images->images[i*colours*width*height]);
	}
	if ( !n_zones )
	{
		n_zones = 1;
		zones = new Zone *[1];
		zones[0] = new Zone( 0, "All", Zone::ACTIVE, Box( width, height ), RGB_RED );
	}
	start_time = last_fps_time = time( 0 );

	event = 0;

	Info(( "Monitor %s has function %d\n", name, function ));

	if ( !capture )
	{
		ref_image.Assign( width, height, colours, image_buffer[shared_images->last_write_index].image->buffer );
	}
	else
	{
		static char	path[PATH_MAX];

		sprintf( path, EVENT_DIR );

		struct stat statbuf;
		errno = 0;
		stat( path, &statbuf );
		if ( errno == ENOENT || errno == ENOTDIR )
		{
			if ( mkdir( path, 0755 ) )
			{
				Error(( "Can't make %s: %s\n", path, strerror(errno)));
			}
		}

		sprintf( path, EVENT_DIR "/%s", name );

		errno = 0;
		stat( path, &statbuf );
		if ( errno == ENOENT || errno == ENOTDIR )
		{
			if ( mkdir( path, 0755 ) )
			{
				Error(( "Can't make %s: %s\n", path, strerror(errno)));
			}
		}
	}

	//if ( capture )
	//{
		//Camera::Capture( ref_image );
	//}
}

Monitor::~Monitor()
{
	delete[] image_buffer;
}

Monitor::State Monitor::GetState() const
{
	return( shared_images->state );
}

int Monitor::GetImage( int index ) const
{
	if ( index < 0 || index > IMAGE_BUFFER_COUNT )
	{
		index = shared_images->last_write_index;
	}
	Snapshot *snap = &image_buffer[index];
	Image *image = snap->image;
	image->WriteJpeg( "zmu.jpg" );
	return( 0 );
}

time_t Monitor::GetTimestamp( int index ) const
{
	if ( index < 0 || index > IMAGE_BUFFER_COUNT )
	{
		index = shared_images->last_write_index;
	}
	Snapshot *snap = &image_buffer[index];
	return( *(snap->timestamp) );
}

unsigned int Monitor::GetLastReadIndex() const
{
	return( shared_images->last_read_index );
}

unsigned int Monitor::GetLastWriteIndex() const
{
	return( shared_images->last_write_index );
}

double Monitor::GetFPS() const
{
	int index1 = shared_images->last_write_index;
	int index2 = (index1+1)%IMAGE_BUFFER_COUNT;;

	Snapshot *snap1 = &image_buffer[index1];
	time_t time1 = *(snap1->timestamp);

	Snapshot *snap2 = &image_buffer[index2];
	time_t time2 = *(snap2->timestamp);

	double fps = double(IMAGE_BUFFER_COUNT)/(time1-time2);

	return( fps );
}

void Monitor::CheckFunction()
{
	static char sql[256];
	sprintf( sql, "select Function+0 from Monitors where Id = %d", id );
	if ( mysql_query( &dbconn, sql ) )
	{
		Error(( "Can't run query: %s\n", mysql_error( &dbconn ) ));
		exit( mysql_errno( &dbconn ) );
	}

	MYSQL_RES *result = mysql_store_result( &dbconn );
	if ( !result )
	{
		Error(( "Can't use query result: %s\n", mysql_error( &dbconn ) ));
		exit( mysql_errno( &dbconn ) );
	}
	for( int i = 0; MYSQL_ROW dbrow = mysql_fetch_row( result ); i++ )
	{
		function = (Function)atoi(dbrow[0]);
	}
	if ( mysql_errno( &dbconn ) )
	{
		Error(( "Can't fetch row: %s\n", mysql_error( &dbconn ) ));
		exit( mysql_errno( &dbconn ) );
	}
	// Yadda yadda
	mysql_free_result( result );

	if ( function != ACTIVE )
	{
		shared_images->state = state = IDLE;
	}
}

void Monitor::DumpZoneImage()
{
	int index = shared_images->last_write_index;
	Snapshot *snap = &image_buffer[index];
	Image *image = snap->image;

	Image zone_image( *image );
	zone_image.Colourise();
	for( int i = 0; i < n_zones; i++ )
	{
		unsigned char *psrc = zone_image.buffer;
		int lo_x = zones[i]->Limits().Lo().X();
		int lo_y = zones[i]->Limits().Lo().Y();
		int hi_x = zones[i]->Limits().Hi().X();
		int hi_y = zones[i]->Limits().Hi().Y();
		for ( int y = 0; y < zone_image.height; y++ )
		{
			for ( int x = 0; x < zone_image.width; x++, psrc += 3 )
			{
				if ( ( (x == lo_x || x == hi_x) && (y >= lo_y && y <= hi_y) )
				|| ( (y == lo_y || y == hi_y) && (x >= lo_x && x <= hi_x) )
				|| ( (x > lo_x && x < hi_x && y > lo_y && y < hi_y) && !(x%2) && !(y%2) ) )
				{
					if ( zones[i]->Type() == Zone::ACTIVE )
					{
						RED(psrc) = RGB_RED_VAL(RGB_RED);
						GREEN(psrc) = RGB_GREEN_VAL(RGB_RED);
						BLUE(psrc) = RGB_BLUE_VAL(RGB_RED);
					}
					else if ( zones[i]->Type() == Zone::INCLUSIVE )
					{
						RED(psrc) = RGB_RED_VAL(RGB_GREEN);
						GREEN(psrc) = RGB_GREEN_VAL(RGB_GREEN);
						BLUE(psrc) = RGB_BLUE_VAL(RGB_GREEN);
					}
					else if ( zones[i]->Type() == Zone::EXCLUSIVE )
					{
						RED(psrc) = RGB_RED_VAL(RGB_BLUE);
						GREEN(psrc) = RGB_GREEN_VAL(RGB_BLUE);
						BLUE(psrc) = RGB_BLUE_VAL(RGB_BLUE);
					}
					else
					{
						RED(psrc) = RGB_RED_VAL(RGB_WHITE);
						GREEN(psrc) = RGB_GREEN_VAL(RGB_WHITE);
						BLUE(psrc) = RGB_BLUE_VAL(RGB_WHITE);
					}
				}
			}
		}
	}
	char filename[64];
	sprintf( filename, "%s-Zones.jpg", name );
	zone_image.WriteJpeg( filename );
}

void Monitor::DumpImage( Image *image ) const
{
	if ( image_count && !(image_count%10) )
	{
		static char new_filename[64];
		static char filename[64];
		//sprintf( filename, "%s%04d.jpg", name, image_count );
		sprintf( filename, "%s.jpg", name );
		sprintf( new_filename, "%s-new.jpg", name );
		image->WriteJpeg( new_filename );
		rename( new_filename, filename );
	}
}

bool Monitor::Analyse()
{
	if ( shared_images->last_read_index == shared_images->last_write_index )
	{
		return( false );
	}

	time_t now = time( 0 );

	if ( image_count && !(image_count%FPS_REPORT_INTERVAL) )
	{
		fps = double(FPS_REPORT_INTERVAL)/(now-last_fps_time);
		Info(( "%s: %d - Processing at %.2f fps\n", name, image_count, fps ));
		last_fps_time = now;
	}

	int index = shared_images->last_write_index%IMAGE_BUFFER_COUNT;
	Snapshot *snap = &image_buffer[index];
	time_t timestamp = *(snap->timestamp);
	Image *image = snap->image;

	unsigned int score = 0;
	if ( Ready() )
	{
		if ( score = ref_image.Compare( *image, n_zones, zones ) )
		{
			if ( state == IDLE )
			{
				event = new Event( this, timestamp );

				Info(( "%s: %03d - Gone into alarm state\n", name, image_count ));
				int pre_index = ((index+IMAGE_BUFFER_COUNT)-PRE_EVENT_COUNT)%IMAGE_BUFFER_COUNT;
				for ( int i = 0; i < PRE_EVENT_COUNT; i++ )
				{
					event->AddFrame( *(image_buffer[pre_index].timestamp), image_buffer[pre_index].image );
					pre_index = (pre_index+1)%IMAGE_BUFFER_COUNT;
				}
				//event->AddFrame( now, &image );
			}
			shared_images->state = state = ALARM;
			last_alarm_count = image_count;
		}
		else
		{
			if ( state == ALARM )
			{
				shared_images->state = state = ALERT;
			}
			else if ( state == ALERT )
			{
				if ( image_count-last_alarm_count > POST_EVENT_COUNT )
				{
					Info(( "%s: %03d - Left alarm state (%d) - %d(%d) images\n", name, image_count, event->id, event->frames, event->alarm_frames ));
					delete event;
					shared_images->state = state = IDLE;
				}
			}
		}
		if ( state != IDLE )
		{
			if ( state == ALARM )
			{
				Image alarm_image( *image );
				for( int i = 0; i < n_zones; i++ )
				{
					if ( zones[i]->Alarmed() )
					{
						alarm_image.Overlay( zones[i]->AlarmImage() );
					}
				}
				event->AddFrame( now, image, &alarm_image, score );
			}
			else
			{
				event->AddFrame( now, image );
			}
		}
	}
	ref_image.Blend( *image, 10 );
	DumpImage( image );

	shared_images->last_read_index = index%IMAGE_BUFFER_COUNT;
	image_count++;

	return( true );
}

void Monitor::ReloadZones()
{
	Info(( "Reloading zones for monitor %s\n", name ));
	for( int i = 0; i < n_zones; i++ )
	{
		delete zones[i];
	}
	//delete[] zones;
	n_zones = Zone::Load( id, width, height, zones );
	DumpZoneImage();
}

int Monitor::Load( int device, Monitor **&monitors, bool capture )
{
	static char sql[256];
	if ( device == -1 )
	{
		sprintf( sql, "select Id, Name, Function+0, Device, Channel, Format, Width, Height, Colours, LabelFormat, LabelX, LabelY, WarmUpCount, PreEventCount, PostEventCount from Monitors where Function != 'None'" );
	}
	else
	{
		sprintf( sql, "select Id, Name, Function+0, Device, Channel, Format, Width, Height, Colours, LabelFormat, LabelX, LabelY, WarmUpCount, PreEventCount, PostEventCount from Monitors where Function != 'None' and Device = %d", device );
	}
	if ( mysql_query( &dbconn, sql ) )
	{
		Error(( "Can't run query: %s\n", mysql_error( &dbconn ) ));
		exit( mysql_errno( &dbconn ) );
	}

	MYSQL_RES *result = mysql_store_result( &dbconn );
	if ( !result )
	{
		Error(( "Can't use query result: %s\n", mysql_error( &dbconn ) ));
		exit( mysql_errno( &dbconn ) );
	}
	int n_monitors = mysql_num_rows( result );
	Info(( "Got %d monitors\n", n_monitors ));
	delete[] monitors;
	monitors = new Monitor *[n_monitors];
	for( int i = 0; MYSQL_ROW dbrow = mysql_fetch_row( result ); i++ )
	{
		Zone **zones = 0;
		int n_zones = Zone::Load( atoi(dbrow[0]), atoi(dbrow[6]), atoi(dbrow[7]), zones );
		monitors[i] = new Monitor( atoi(dbrow[0]), dbrow[1], atoi(dbrow[2]), atoi(dbrow[3]), atoi(dbrow[4]), atoi(dbrow[5]), atoi(dbrow[6]), atoi(dbrow[7]), atoi(dbrow[8]), capture, n_zones, zones );
		Info(( "Loaded monitor %d(%s), %d zones\n", atoi(dbrow[0]), dbrow[1], n_zones ));
	}
	if ( mysql_errno( &dbconn ) )
	{
		Error(( "Can't fetch row: %s\n", mysql_error( &dbconn ) ));
		exit( mysql_errno( &dbconn ) );
	}
	// Yadda yadda
	mysql_free_result( result );

	return( n_monitors );
}

Monitor *Monitor::Load( int id, bool load_zones )
{
	static char sql[256];
	sprintf( sql, "select Id, Name, Function+0, Device, Channel, Format, Width, Height, Colours, LabelFormat, LabelX, LabelY, WarmUpCount, PreEventCount, PostEventCount from Monitors where Id = %d", id );
	if ( mysql_query( &dbconn, sql ) )
	{
		Error(( "Can't run query: %s\n", mysql_error( &dbconn ) ));
		exit( mysql_errno( &dbconn ) );
	}

	MYSQL_RES *result = mysql_store_result( &dbconn );
	if ( !result )
	{
		Error(( "Can't use query result: %s\n", mysql_error( &dbconn ) ));
		exit( mysql_errno( &dbconn ) );
	}
	int n_monitors = mysql_num_rows( result );
	Info(( "Got %d monitors\n", n_monitors ));
	Monitor *monitor = 0;
	for( int i = 0; MYSQL_ROW dbrow = mysql_fetch_row( result ); i++ )
	{
		Zone **zones = 0;
		int n_zones = 0;
		if ( load_zones )
		{
			int n_zones = Zone::Load( atoi(dbrow[0]), atoi(dbrow[6]), atoi(dbrow[7]), zones );
		}
		monitor = new Monitor( atoi(dbrow[0]), dbrow[1], atoi(dbrow[2]), atoi(dbrow[3]), atoi(dbrow[4]), atoi(dbrow[5]), atoi(dbrow[6]), atoi(dbrow[7]), atoi(dbrow[8]), false, n_zones, zones );
		Info(( "Loaded monitor %d(%s), %d zones\n", atoi(dbrow[0]), dbrow[1], n_zones ));
	}
	if ( mysql_errno( &dbconn ) )
	{
		Error(( "Can't fetch row: %s\n", mysql_error( &dbconn ) ));
		exit( mysql_errno( &dbconn ) );
	}
	// Yadda yadda
	mysql_free_result( result );

	return( monitor );
}

void Monitor::StreamImages( unsigned long idle, unsigned long refresh, FILE *fd )
{
	fprintf( fd, "Server: ZoneMinder Stream Server\r\n" );
	fprintf( fd, "Content-Type: multipart/x-mixed-replace;boundary=ZoneMinderFrame\r\n" );
	fprintf( fd, "\r\n" );
	fprintf( fd, "--ZoneMinderFrame\n" );
	int last_read_index = IMAGE_BUFFER_COUNT;
	JOCTET img_buffer[width*height*colours];
	int img_buffer_size = 0;
	int loop_count = (idle/refresh)-1;
	while ( true )
	{
		if ( last_read_index != shared_images->last_write_index )
		{
			// Send the next frame
			last_read_index = shared_images->last_write_index;
			int index = shared_images->last_write_index%IMAGE_BUFFER_COUNT;
			//Info(( "%d: %x - %x", index, image_buffer[index].image, image_buffer[index].image->buffer ));
			Snapshot *snap = &image_buffer[index];
			Image *image = snap->image;
			image->EncodeJpeg( img_buffer, &img_buffer_size );

			fprintf( fd, "Content-type: image/jpg\n\n" );
			fwrite( img_buffer, 1, img_buffer_size, fd );
			fprintf( fd, "\n--ZoneMinderFrame\n" );
			fflush( fd );
		}
		usleep( refresh*1000 );
		for ( int i = 0; shared_images->state == IDLE && i < loop_count; i++ )
		{
			usleep( refresh*1000 );
		}
	}
}
