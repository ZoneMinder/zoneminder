//
// ZoneMinder Core Implementation, $Date$, $Revision$
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

MYSQL dbconn;

void Zone::Setup( Monitor *p_monitor, int p_id, const char *p_label, ZoneType p_type, const Box &p_limits, const Rgb p_alarm_rgb, int p_alarm_threshold, int p_min_alarm_pixels, int p_max_alarm_pixels, const Coord &p_filter_box, int p_min_filter_pixels, int p_max_filter_pixels, int p_min_blob_pixels, int p_max_blob_pixels, int p_min_blobs, int p_max_blobs )
{
	monitor = p_monitor;

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
	alarm_blob_pixels = 0;
	alarm_blobs = 0;
	image = 0;
	score = 0;
}

Zone::~Zone()
{
	delete[] label;
	delete image;
}

void Zone::RecordStats( const Event *event )
{
	static char sql[256];
	sprintf( sql, "insert into Stats set MonitorId=%d, ZoneId=%d, EventId=%d, FrameId=%d, AlarmPixels=%d, FilterPixels=%d, BlobPixels=%d, Blobs=%d, MinBlobSize=%d, MaxBlobSize=%d, MinX=%d, MinY=%d, MaxX=%d, MaxY=%d, Score=%d", monitor->Id(), id, event->Id(), event->Frames()+1, alarm_pixels, alarm_filter_pixels, alarm_blob_pixels, alarm_blobs, min_blob_size, max_blob_size, alarm_box.LoX(), alarm_box.LoY(), alarm_box.HiX(), alarm_box.HiY(), score );
	if ( mysql_query( &dbconn, sql ) )
	{
		Error(( "Can't insert event: %s\n", mysql_error( &dbconn ) ));
		exit( mysql_errno( &dbconn ) );
	}
}

bool Zone::CheckAlarms( const Image *delta_image )
{
	bool alarm = false;
	unsigned int score = 0;

	ResetStats();

	delete image;
	Image *diff_image = image = new Image( *delta_image );

	int alarm_pixels = 0;

	int lo_x = limits.Lo().X();
	int lo_y = limits.Lo().Y();
	int hi_x = limits.Hi().X();
	int hi_y = limits.Hi().Y();
	for ( int y = lo_y; y <= hi_y; y++ )
	{
		unsigned char *pdiff = diff_image->Buffer( lo_x, y );
		for ( int x = lo_x; x <= hi_x; x++, pdiff++ )
		{
			if ( *pdiff > alarm_threshold )
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
	if ( min_alarm_pixels && alarm_pixels < min_alarm_pixels ) return( false );
	if ( max_alarm_pixels && alarm_pixels > max_alarm_pixels ) return( false );

	int filter_pixels = 0;

	int bx = filter_box.X();
	int by = filter_box.Y();
	int bx1 = bx-1;
	int by1 = by-1;

	// Now eliminate all pixels that don't participate in a blob
	for ( int y = lo_y; y <= hi_y; y++ )
	{
		unsigned char *pdiff = diff_image->Buffer( lo_x, y );

		for ( int x = lo_x; x <= hi_x; x++, pdiff++ )
		{
			if ( *pdiff == WHITE )
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
								unsigned char *cpdiff = diff_image->Buffer( x+dx+dx2, y+dy+dy2 );

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

	//diff_image->WriteJpeg( "diff2.jpg" );

	if ( !filter_pixels ) return( false );
	if ( min_filter_pixels && filter_pixels < min_filter_pixels ) return( false );
	if ( max_filter_pixels && filter_pixels > max_filter_pixels ) return( false );

	int blobs = 0;

	typedef struct { unsigned char tag; int count; int lo_x; int hi_x; int lo_y; int hi_y; } BlobStats;
	BlobStats blob_stats[256];
	memset( blob_stats, 0, sizeof(BlobStats)*256 );
	for ( int y = lo_y; y <= hi_y; y++ )
	{
		unsigned char *pdiff = diff_image->Buffer( lo_x, y );
		for ( int x = lo_x; x <= hi_x; x++, pdiff++ )
		{
			if ( *pdiff == WHITE )
			{
				//printf( "Got white pixel at %d,%d (%x)\n", x, y, pdiff );
				int lx = x>lo_x?*(pdiff-1):0;
				int ly = y>lo_y?*(pdiff-diff_image->Width()):0;

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
								unsigned char *spdiff = diff_image->Buffer( bss->lo_x, sy );
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

	int min_blob_size = 0;
	int max_blob_size = 0;
	// Now eliminate blobs under the alarm_threshold
	for ( int i = 1; i < WHITE; i++ )
	{
		BlobStats *bs = &blob_stats[i];
		if ( bs->count && ((min_blob_pixels && bs->count < min_blob_pixels) || (max_blob_pixels && bs->count > max_blob_pixels)) )
		{
			//Info(( "Eliminating blob %d, %d pixels (%d,%d - %d,%d)\n", i, bs->count, bs->lo_x, bs->lo_y, bs->hi_x, bs->hi_y ));
			for ( int sy = bs->lo_y; sy <= bs->hi_y; sy++ )
			{
				unsigned char *spdiff = diff_image->Buffer( bs->lo_x, sy );
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
		else
		{
			if ( bs->count )
			{
				if ( !min_blob_size || bs->count < min_blob_size ) min_blob_size = bs->count;
				if ( !max_blob_size || bs->count > max_blob_size ) max_blob_size = bs->count;
			}
		}
	}

	if ( !blobs ) return( false );
	if ( min_blobs && blobs < min_blobs ) return( false );
	if ( max_blobs && blobs > max_blobs ) return( false );

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

	alarm_pixels = alarm_pixels;
	alarm_filter_pixels = filter_pixels;
	alarm_blob_pixels = blob_pixels;
	alarm_blobs = blobs;
	min_blob_size = min_blob_size;
	max_blob_size = max_blob_size;
	alarm_box = Box( Coord( alarm_lo_x, alarm_lo_y ), Coord( alarm_hi_x, alarm_hi_y ) );
	score = ((100*blob_pixels)/blobs)/(limits.Size().X()*limits.Size().Y());
	if ( type == INCLUSIVE )
	{
		score /= 2;
	}
	else if ( type == EXCLUSIVE )
	{
		score *= 2;
	}
	score = score;

	// Now outline the changed region
	if ( alarm_blobs )
	{
		alarm = true;
		Image *high_image = image = diff_image->HighlightEdges( alarm_rgb, &limits );

		delete diff_image;
		//high_image->WriteJpeg( "diff4.jpg" );

		Info(( "%s: Alarm Pixels: %d, Filter Pixels: %d, Blob Pixels: %d, Blobs: %d, Score: %d\n", Label(), alarm_pixels, filter_pixels, blob_pixels, blobs, score ));
	}
	return( true );
}

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

int Zone::Load( Monitor *monitor, Zone **&zones )
{
	static char sql[256];
	sprintf( sql, "select Id,Name,Type+0,Units,LoX,LoY,HiX,HiY,AlarmRGB,AlarmThreshold,MinAlarmPixels,MaxAlarmPixels,FilterX,FilterY,MinFilterPixels,MaxFilterPixels,MinBlobPixels,MaxBlobPixels,MinBlobs,MaxBlobs from Zones where MonitorId = %d order by Type, Id", monitor->Id() );
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
	Info(( "Got %d zones for monitor %s\n", n_zones, monitor->Name() ));
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
			LoX = (LoX*(monitor->CameraWidth()-1))/100;
			LoY = (LoY*(monitor->CameraHeight()-1))/100;
			HiX = (HiX*(monitor->CameraWidth()-1))/100;
			HiY = (HiY*(monitor->CameraHeight()-1))/100;
			MinAlarmPixels = (MinAlarmPixels*monitor->CameraWidth()*monitor->CameraHeight())/100;
			MaxAlarmPixels = (MaxAlarmPixels*monitor->CameraWidth()*monitor->CameraHeight())/100;
			MinFilterPixels = (MinFilterPixels*monitor->CameraWidth()*monitor->CameraHeight())/100;
			MaxFilterPixels = (MaxFilterPixels*monitor->CameraWidth()*monitor->CameraHeight())/100;
			MinBlobPixels = (MinBlobPixels*monitor->CameraWidth()*monitor->CameraHeight())/100;
			MaxBlobPixels = (MaxBlobPixels*monitor->CameraWidth()*monitor->CameraHeight())/100;
		}

		if ( atoi(dbrow[2]) == Zone::INACTIVE )
		{
			zones[i] = new Zone( monitor, Id, Name, Box( LoX, LoY, HiX, HiY ) );
		}
		else
		{
			zones[i] = new Zone( monitor, Id, Name, (Zone::ZoneType)Type, Box( LoX, LoY, HiX, HiY ), AlarmRGB, AlarmThreshold, MinAlarmPixels, MaxAlarmPixels, Coord( FilterX, FilterY ), MinFilterPixels, MaxFilterPixels, MinBlobPixels, MaxBlobPixels, MinBlobs, MaxBlobs );
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

#if 0
bool Image::CheckAlarms( Zone *zone, const Image *delta_image ) const
{
	bool alarm = false;
	unsigned int score = 0;

	zone->ResetStats();

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

	int min_blob_size = 0;
	int max_blob_size = 0;
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
		else
		{
			if ( bs->count )
			{
				if ( !min_blob_size || bs->count < min_blob_size ) min_blob_size = bs->count;
				if ( !max_blob_size || bs->count > max_blob_size ) max_blob_size = bs->count;
			}
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

	zone->alarm_pixels = alarm_pixels;
	zone->alarm_filter_pixels = filter_pixels;
	zone->alarm_blob_pixels = blob_pixels;
	zone->alarm_blobs = blobs;
	zone->min_blob_size = min_blob_size;
	zone->max_blob_size = max_blob_size;
	zone->alarm_box = Box( Coord( alarm_lo_x, alarm_lo_y ), Coord( alarm_hi_x, alarm_hi_y ) );
	zone->score = ((100*blob_pixels)/blobs)/(zone->limits.Size().X()*zone->limits.Size().Y());
	if ( zone->Type() == Zone::INCLUSIVE )
	{
		zone->score /= 2;
	}
	else if ( zone->Type() == Zone::EXCLUSIVE )
	{
		zone->score *= 2;
	}
	score = zone->score;

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

		Info(( "%s: Alarm Pixels: %d, Filter Pixels: %d, Blob Pixels: %d, Blobs: %d, Score: %d\n", zone->Label(), alarm_pixels, filter_pixels, blob_pixels, blobs, score ));
	}
	return( true );
}
#endif

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

Camera::Camera( int p_width, int p_height, int p_colours, bool p_capture ) : width( p_width), height( p_height ), colours( p_colours ), capture( p_capture )
{
}

Camera::~Camera()
{
}

void Camera::Initialise()
{
}

void Camera::Terminate()
{
}

int LocalCamera::camera_count = 0;
int LocalCamera::m_cap_frame = 0;
int LocalCamera::m_sync_frame = 0;
video_mbuf LocalCamera::m_vmb;
video_mmap *LocalCamera::m_vmm;
int LocalCamera::m_videohandle;
unsigned char *LocalCamera::m_buffer=0;

LocalCamera::LocalCamera( int p_device, int p_channel, int p_format, int p_width, int p_height, int p_colours, bool p_capture ) : Camera( p_width, p_height, p_colours, p_capture ), device( p_device ), channel( p_channel ), format( p_format )
{
	if ( !camera_count++ && capture )
	{
		Initialise();
	}
}

LocalCamera::~LocalCamera()
{
	if ( !--camera_count && capture )
	{
		Terminate();
	}
}

void LocalCamera::Initialise()
{
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
		if ( !ZM_STRICT_VIDEO_CONFIG ) exit(-1);
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
		if ( !ZM_STRICT_VIDEO_CONFIG ) exit(-1);
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

	struct video_channel vid_src;
	vid_src.channel = channel;

	if( !ioctl( m_videohandle, VIDIOCGCHAN, &vid_src))
	{
		Info(( "C:%d\n", vid_src.channel ));
		Info(( "F:%d\n", vid_src.norm ));
		Info(( "Fl:%x\n", vid_src.flags ));
		Info(( "T:%d\n", vid_src.type ));
	}
	else
	{
		Error(( "Failed to get camera source: %s\n", strerror(errno) ));
		exit(-1);
	}

	//vid_src.norm = VIDEO_MODE_AUTO;
	vid_src.norm = format;
	vid_src.flags = 0;
	vid_src.type = VIDEO_TYPE_CAMERA;
	if(ioctl(m_videohandle, VIDIOCSCHAN, &vid_src))
	{
		Error(( "Failed to set camera source %d: %s\n", channel, strerror(errno) ));
		if ( !ZM_STRICT_VIDEO_CONFIG ) exit(-1);
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

void LocalCamera::Terminate()
{
	munmap((char*)m_buffer, m_vmb.size);

	delete[] m_vmm;

	close(m_videohandle);
}

bool LocalCamera::GetCurrentSettings( int device, char *output, bool verbose )
{
	char device_path[64];

	output[0] = 0;
	sprintf( device_path, "/dev/video%d", device );
	if ( verbose )
		sprintf( output, output+strlen(output), "Checking Video Device: %s\n", device_path );
	if( (m_videohandle=open(device_path, O_RDONLY)) <=0 )
	{
		Error(( "Failed to open video device %s: %s\n", device_path, strerror(errno) ));
		if ( verbose )
			sprintf( output+strlen(output), "Error, failed to open video device: %s\n", strerror(errno) );
		else
			sprintf( output+strlen(output), "error%d\n", errno );
		return( false );
	}

	struct video_capability vid_cap;
	if( !ioctl( m_videohandle, VIDIOCGCAP, &vid_cap))
	{
		if ( verbose )
		{
			sprintf( output+strlen(output), "Video Capabilities\n" );
			sprintf( output+strlen(output), "  Name: %s\n", vid_cap.name );
			sprintf( output+strlen(output), "  Type: %d\n%s%s%s%s%s%s%s%s%s%s%s%s%s%s", vid_cap.type,
				vid_cap.type&VID_TYPE_CAPTURE?"    Can capture\n":"",
				vid_cap.type&VID_TYPE_TUNER?"    Can tune\n":"",
				vid_cap.type&VID_TYPE_TELETEXT?"    Does teletext\n":"",
				vid_cap.type&VID_TYPE_OVERLAY?"    Overlay onto frame buffer\n":"",
				vid_cap.type&VID_TYPE_CHROMAKEY?"    Overlay by chromakey\n":"",
				vid_cap.type&VID_TYPE_CLIPPING?"    Can clip\n":"",
				vid_cap.type&VID_TYPE_FRAMERAM?"    Uses the frame buffer memory\n":"",
				vid_cap.type&VID_TYPE_SCALES?"    Scalable\n":"",
				vid_cap.type&VID_TYPE_MONOCHROME?"    Monochrome only\n":"",
				vid_cap.type&VID_TYPE_SUBCAPTURE?"    Can capture subareas of the image\n":"",
				vid_cap.type&VID_TYPE_MPEG_DECODER?"    Can decode MPEG streams\n":"",
				vid_cap.type&VID_TYPE_MPEG_ENCODER?"    Can encode MPEG streams\n":"",
				vid_cap.type&VID_TYPE_MJPEG_DECODER?"    Can decode MJPEG streams\n":"",
				vid_cap.type&VID_TYPE_MJPEG_ENCODER?"    Can encode MJPEG streams\n":""
			);
			sprintf( output+strlen(output), "  Video Channels: %d\n", vid_cap.channels );
			sprintf( output+strlen(output), "  Audio Channels: %d\n", vid_cap.audios );
			sprintf( output+strlen(output), "  Maximum Width: %d\n", vid_cap.maxwidth );
			sprintf( output+strlen(output), "  Maximum Height: %d\n", vid_cap.maxheight );
			sprintf( output+strlen(output), "  Minimum Width: %d\n", vid_cap.minwidth );
			sprintf( output+strlen(output), "  Minimum Height: %d\n", vid_cap.minheight );
		}
		else
		{
			sprintf( output+strlen(output), "N:%s,", vid_cap.name );
			sprintf( output+strlen(output), "T:%d,", vid_cap.type );
			sprintf( output+strlen(output), "nC:%d,", vid_cap.channels );
			sprintf( output+strlen(output), "nA:%d,", vid_cap.audios );
			sprintf( output+strlen(output), "mxW:%d,", vid_cap.maxwidth );
			sprintf( output+strlen(output), "mxH:%d,", vid_cap.maxheight );
			sprintf( output+strlen(output), "mnW:%d,", vid_cap.minwidth );
			sprintf( output+strlen(output), "mnH:%d,", vid_cap.minheight );
		}
	}
	else
	{
		Error(( "Failed to get video capabilities: %s", strerror(errno) ));
		if ( verbose )
			sprintf( output, "Error, failed to get video capabilities: %s\n", strerror(errno) );
		else
			sprintf( output, "error%d\n", errno );
		return( false );
	}

	struct video_window vid_win;
	if( !ioctl( m_videohandle, VIDIOCGWIN, &vid_win))
	{
		if ( verbose )
		{
			sprintf( output+strlen(output), "Window Attributes\n" );
			sprintf( output+strlen(output), "  X Offset: %d\n", vid_win.x );
			sprintf( output+strlen(output), "  Y Offset: %d\n", vid_win.y );
			sprintf( output+strlen(output), "  Width: %d\n", vid_win.width );
			sprintf( output+strlen(output), "  Height: %d\n", vid_win.height );
		}
		else
		{
			sprintf( output+strlen(output), "X:%d,", vid_win.x );
			sprintf( output+strlen(output), "Y:%d,", vid_win.y );
			sprintf( output+strlen(output), "W:%d,", vid_win.width );
			sprintf( output+strlen(output), "H:%d,", vid_win.height );
		}
	}
	else
	{
		Error(( "Failed to get window attributes: %s", strerror(errno) ));
		if ( verbose )
			sprintf( output, "Error, failed to get window attributes: %s\n", strerror(errno) );
		else
			sprintf( output, "error%d\n", errno );
		return( false );
	}

	struct video_picture vid_pic;
	if( !ioctl( m_videohandle, VIDIOCGPICT, &vid_pic))
	{
		if ( verbose )
		{
			sprintf( output+strlen(output), "Picture Atributes\n" );
			sprintf( output+strlen(output), "  Palette: %d - %s\n", vid_pic.palette, 
				vid_pic.palette==VIDEO_PALETTE_GREY?"Linear greyscale":(
				vid_pic.palette==VIDEO_PALETTE_HI240?"High 240 cube (BT848)":(
				vid_pic.palette==VIDEO_PALETTE_RGB565?"565 16 bit RGB":(
				vid_pic.palette==VIDEO_PALETTE_RGB24?"24bit RGB":(
				vid_pic.palette==VIDEO_PALETTE_RGB32?"32bit RGB":(
				vid_pic.palette==VIDEO_PALETTE_RGB555?"555 15bit RGB":(
				vid_pic.palette==VIDEO_PALETTE_YUV422?"YUV422 capture":(
				vid_pic.palette==VIDEO_PALETTE_YUYV?"YUYV":(
				vid_pic.palette==VIDEO_PALETTE_UYVY?"UVYV":(
				vid_pic.palette==VIDEO_PALETTE_YUV420?"YUV420":(
				vid_pic.palette==VIDEO_PALETTE_YUV411?"YUV411 capture":(
				vid_pic.palette==VIDEO_PALETTE_RAW?"RAW capture (BT848)":(
				vid_pic.palette==VIDEO_PALETTE_YUV422P?"YUV 4:2:2 Planar":(
				vid_pic.palette==VIDEO_PALETTE_YUV411P?"YUV 4:1:1 Planar":(
				vid_pic.palette==VIDEO_PALETTE_YUV420P?"YUV 4:2:0 Planar":(
				vid_pic.palette==VIDEO_PALETTE_YUV410P?"YUV 4:1:0 Planar":"Unknown"
			))))))))))))))));
			sprintf( output+strlen(output), "  Colour Depth: %d\n", vid_pic.depth );
			sprintf( output+strlen(output), "  Brightness: %d\n", vid_pic.brightness );
			sprintf( output+strlen(output), "  Hue: %d\n", vid_pic.hue );
			sprintf( output+strlen(output), "  Colour :%d\n", vid_pic.colour );
			sprintf( output+strlen(output), "  Contrast: %d\n", vid_pic.contrast );
			sprintf( output+strlen(output), "  Whiteness: %d\n", vid_pic.whiteness );
		}
		else
		{
			sprintf( output+strlen(output), "P:%d,", vid_pic.palette );
			sprintf( output+strlen(output), "D:%d,", vid_pic.depth );
			sprintf( output+strlen(output), "B:%d,", vid_pic.brightness );
			sprintf( output+strlen(output), "h:%d,", vid_pic.hue );
			sprintf( output+strlen(output), "Cl:%d,", vid_pic.colour );
			sprintf( output+strlen(output), "Cn:%d,", vid_pic.contrast );
			sprintf( output+strlen(output), "w:%d,", vid_pic.whiteness );
		}
	}
	else
	{
		Error(( "Failed to get picture attributes: %s", strerror(errno) ));
		if ( verbose )
			sprintf( output, "Error, failed to get picture attributes: %s\n", strerror(errno) );
		else
			sprintf( output, "error%d\n", errno );
		return( false );
	}

	for ( int chan = 0; chan < vid_cap.channels; chan++ )
	{
		struct video_channel vid_src;
		vid_src.channel = chan;
		if( !ioctl( m_videohandle, VIDIOCGCHAN, &vid_src))
		{
			if ( verbose )
			{
				sprintf( output+strlen(output), "Channel %d Attributes\n", chan );
				sprintf( output+strlen(output), "  Name: %s\n", vid_src.name );
				sprintf( output+strlen(output), "  Channel: %d\n", vid_src.channel );
				sprintf( output+strlen(output), "  Flags: %d\n%s%s", vid_src.flags,
					vid_src.flags&VIDEO_VC_TUNER?"    Channel has a tuner\n":"",
					vid_src.flags&VIDEO_VC_AUDIO?"    Channel has audio\n":""
				);
				sprintf( output+strlen(output), "  Type: %d - %s\n", vid_src.type,
					vid_src.type==VIDEO_TYPE_TV?"TV":(
					vid_src.type==VIDEO_TYPE_CAMERA?"Camera":"Unknown"
				));
				sprintf( output+strlen(output), "  Format: %d - %s\n", vid_src.norm,
					vid_src.norm==VIDEO_MODE_PAL?"PAL":(
					vid_src.norm==VIDEO_MODE_NTSC?"NTSC":(
					vid_src.norm==VIDEO_MODE_SECAM?"SECAM":(
					vid_src.norm==VIDEO_MODE_AUTO?"AUTO":"Unknown"
				))));
			}
			else
			{
				sprintf( output+strlen(output), "n%d:%d,", chan, vid_src.name );
				sprintf( output+strlen(output), "C%d:%d,", chan, vid_src.channel );
				sprintf( output+strlen(output), "Fl%d:%x,", chan, vid_src.flags );
				sprintf( output+strlen(output), "T%d:%d", chan, vid_src.type );
				sprintf( output+strlen(output), "F%d:%d%s,", chan, vid_src.norm, chan==(vid_cap.channels-1)?"":"," );
			}
		}
		else
		{
			Error(( "Failed to get channel %d attributes: %s\n", chan, strerror(errno) ));
			if ( verbose )
				sprintf( output, "Error, failed to get channel %d attributes: %s\n", chan, strerror(errno) );
			else
				sprintf( output, "error%d\n", errno );
			return( false );
		}
	}
	return( true );
}

Event::Event( Monitor *p_monitor, struct timeval p_start_time ) : monitor( p_monitor ), start_time( p_start_time )
{
	static char sql[256];
	static char start_time_str[32];

	strftime( start_time_str, sizeof(start_time_str), "%Y-%m-%d %H:%M:%S", localtime( &start_time.tv_sec ) );
	sprintf( sql, "insert into Events set MonitorId=%d, Name='Event', StartTime='%s'", monitor->Id(), start_time_str );
	if ( mysql_query( &dbconn, sql ) )
	{
		Error(( "Can't insert event: %s\n", mysql_error( &dbconn ) ));
		exit( mysql_errno( &dbconn ) );
	}
	id = mysql_insert_id( &dbconn );
	start_frame_id = 0;
	end_frame_id = 0;
	//end_time = 0;
	frames = 0;
	alarm_frames = 0;
	tot_score = 0;
	max_score = 0;
	//sprintf( path, ZM_DIR_EVENTS "/%s/%04d", monitor->Name(), id );
	sprintf( path, ZM_DIR_EVENTS "/%s/%d", monitor->Name(), id );
	
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
	static char end_time_str[32];

	struct DeltaTimeval delta_time;
	DELTA_TIMEVAL( delta_time, end_time, start_time );

	strftime( end_time_str, sizeof(end_time_str), "%Y-%m-%d %H:%M:%S", localtime( &end_time.tv_sec ) );

	sprintf( sql, "update Events set Name='Event-%d', EndTime = '%s', Length = %s%d.%02d, Frames = %d, AlarmFrames = %d, TotScore = %d, AvgScore = %d, MaxScore = %d where Id = %d", id, end_time_str, delta_time.positive?"":"-", delta_time.tv_sec, delta_time.tv_usec/10000, frames, alarm_frames, tot_score, (int)(tot_score/alarm_frames), max_score, id );
	if ( mysql_query( &dbconn, sql ) )
	{
		Error(( "Can't update event: %s\n", mysql_error( &dbconn ) ));
		exit( mysql_errno( &dbconn ) );
	}
}

void Event::AddFrame( struct timeval timestamp, const Image *image, const Image *alarm_image, unsigned int score )
{
	frames++;

	static char event_file[PATH_MAX];
	sprintf( event_file, "%s/capture-%03d.jpg", path, frames );
	image->WriteJpeg( event_file );

	struct DeltaTimeval delta_time;
	DELTA_TIMEVAL( delta_time, timestamp, start_time );

	static char sql[256];
	sprintf( sql, "insert into Frames set EventId=%d, FrameId=%d, AlarmFrame=%d, ImagePath='%s', Delta=%s%d.%02d, Score=%d", id, frames, alarm_image!=0, event_file, delta_time.positive?"":"-", delta_time.tv_sec, delta_time.tv_usec/10000, score );
	if ( mysql_query( &dbconn, sql ) )
	{
		Error(( "Can't insert frame: %s\n", mysql_error( &dbconn ) ));
		exit( mysql_errno( &dbconn ) );
	}
	end_frame_id = mysql_insert_id( &dbconn );
	if ( !start_frame_id ) start_frame_id = end_frame_id;

	if ( alarm_image )
	{
		end_time = timestamp;

		alarm_frames++;
		sprintf( event_file, "%s/analyse-%03d.jpg", path, frames );
		alarm_image->WriteJpeg( event_file );
		tot_score += score;
		if ( score > max_score )
			max_score = score;
	}
	//if ( !start_time ) start_time = end_time;
}

void Event::StreamEvent( const char *path, int event_id, unsigned long refresh, FILE *fd )
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

Monitor::Monitor( int p_id, char *p_name, int p_function, int p_device, int p_channel, int p_format, int p_width, int p_height, int p_colours, bool p_capture, char *p_label_format, const Coord &p_label_coord, int p_image_buffer_count, int p_warmup_count, int p_pre_event_count, int p_post_event_count, int p_alarm_frame_count, int p_fps_report_interval, int p_ref_blend_perc, int p_n_zones, Zone *p_zones[] ) : id( p_id ), function( (Function)p_function ), image( p_width, p_height, p_colours ), ref_image( p_width, p_height, p_colours ), label_coord( p_label_coord ), image_buffer_count( p_image_buffer_count ), warmup_count( p_warmup_count ), pre_event_count( p_pre_event_count ), post_event_count( p_post_event_count ), alarm_frame_count( p_alarm_frame_count ), fps_report_interval( p_fps_report_interval ), ref_blend_perc( p_ref_blend_perc ), n_zones( p_n_zones ), zones( p_zones )
{
	name = new char[strlen(p_name)+1];
	strcpy( name, p_name );

    strcpy( label_format, p_label_format );

	camera = new LocalCamera( p_device, p_channel, p_format, p_width, p_height, p_colours, p_capture );

	fps = 0.0;
	event_count = 0;
	image_count = 0;
	first_alarm_count = 0;
	last_alarm_count = 0;
	state = IDLE;

	int shared_images_size = sizeof(SharedImages)+(image_buffer_count*sizeof(time_t))+(image_buffer_count*camera->ImageSize());
	shmid = shmget( ZM_SHM_KEY|id, shared_images_size, IPC_CREAT|0777 );
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

	if ( p_capture )
	{
		memset( shared_images, 0, shared_images_size );
		shared_images->state = IDLE;
		shared_images->last_write_index = image_buffer_count;
		shared_images->last_read_index = image_buffer_count;
		shared_images->last_event = 0;
		shared_images->forced_alarm = false;
	}
	shared_images->timestamps = (struct timeval *)(shm_ptr+sizeof(SharedImages));
	shared_images->images = (unsigned char *)(shm_ptr+sizeof(SharedImages)+(image_buffer_count*sizeof(struct timeval)));

	image_buffer = new Snapshot[image_buffer_count];
	for ( int i = 0; i < image_buffer_count; i++ )
	{
		image_buffer[i].timestamp = &(shared_images->timestamps[i]);
		image_buffer[i].image = new Image( camera->Width(), camera->Height(), camera->Colours(), &(shared_images->images[i*camera->ImageSize()]) );
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
		zones[0] = new Zone( this, 0, "All", Zone::ACTIVE, Box( camera->Width(), camera->Height() ), RGB_RED );
	}
	start_time = last_fps_time = time( 0 );

	event = 0;

	Info(( "Monitor %s has function %d\n", name, function ));
	Info(( "Monitor %s LBF = '%s', LBX = %d, LBY = %d\n", name, label_format, label_coord.X(), label_coord.Y() ));
	Info(( "Monitor %s IBC = %d, WUC = %d, pEC = %d, PEC = %d, FRI = %d, RBP = %d\n", name, image_buffer_count, warmup_count, pre_event_count, post_event_count, fps_report_interval, ref_blend_perc ));

	if ( !p_capture )
	{
		ref_image.Assign( camera->Width(), camera->Height(), camera->Colours(), image_buffer[shared_images->last_write_index].image->Buffer() );
	}
	else
	{
		static char	path[PATH_MAX];

		sprintf( path, ZM_DIR_EVENTS );

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

		sprintf( path, ZM_DIR_EVENTS "/%s", name );

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

	record_event_stats = ZM_RECORD_EVENT_STATS;
}

Monitor::~Monitor()
{
	delete[] image_buffer;

	struct shmid_ds shm_data;
	if ( shmctl( shmid, IPC_STAT, &shm_data ) )
	{
		Error(( "Can't shmctl: %s\n", strerror(errno)));
		exit( -1 );
	}

	if ( shm_data.shm_nattch <= 1 )
	{
		if ( shmctl( shmid, IPC_RMID, 0 ) )
		{
			Error(( "Can't shmctl: %s\n", strerror(errno)));
			exit( -1 );
		}
	}
}

void Monitor::AddZones( int p_n_zones, Zone *p_zones[] )
{
	n_zones = p_n_zones;
	zones = p_zones;
}

Monitor::State Monitor::GetState() const
{
	return( shared_images->state );
}

int Monitor::GetImage( int index ) const
{
	if ( index < 0 || index > image_buffer_count )
	{
		index = shared_images->last_write_index;
	}
	Snapshot *snap = &image_buffer[index];
	Image *image = snap->image;

	char filename[64];
	sprintf( filename, "%s.jpg", name );
	image->WriteJpeg( filename );
	return( 0 );
}

struct timeval Monitor::GetTimestamp( int index ) const
{
	if ( index < 0 || index > image_buffer_count )
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

unsigned int Monitor::GetLastEvent() const
{
	return( shared_images->last_event );
}

double Monitor::GetFPS() const
{
	int index1 = shared_images->last_write_index;
	int index2 = (index1+1)%image_buffer_count;

	//Snapshot *snap1 = &image_buffer[index1];
	//time_t time1 = snap1->timestamp->tv_sec;
	time_t time1 = time( 0 );

	Snapshot *snap2 = &image_buffer[index2];
	time_t time2 = snap2->timestamp->tv_sec;

	double fps = double(image_buffer_count)/(time1-time2);

	return( fps );
}

void Monitor::ForceAlarm()
{
	shared_images->forced_alarm = true;
}

void Monitor::CancelAlarm()
{
	shared_images->forced_alarm = false;
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
		Rgb colour;
		if ( zones[i]->IsActive() )
		{
			colour = RGB_RED;
		}
		else if ( zones[i]->IsInclusive() )
		{
			colour = RGB_GREEN;
		}
		else if ( zones[i]->IsExclusive() )
		{
			colour = RGB_BLUE;
		}
		else
		{
			colour = RGB_WHITE;
		}
		zone_image.Hatch( colour, &(zones[i]->Limits()) );
	}
	char filename[64];
	sprintf( filename, "%s-Zones.jpg", name );
	zone_image.WriteJpeg( filename );
}
  
void Image::Hatch( Rgb colour, const Box *limits=0 )
{
	assert( colours == 1 || colours == 3 );

	int lo_x = limits?limits->Lo().X():0;
	int lo_y = limits?limits->Lo().Y():0;
	int hi_x = limits?limits->Hi().X():width-1;
	int hi_y = limits?limits->Hi().Y():height-1;
	unsigned char *p = buffer;
	for ( int y = lo_x; y <= hi_x; y++ )
	{
		for ( int x = lo_y; x <= hi_y; x++, p += colours )
		{
			//if ( ( (x == lo_x || x == hi_x) && (y >= lo_y && y <= hi_y) )
			//|| ( (y == lo_y || y == hi_y) && (x >= lo_x && x <= hi_x) )
			//|| ( (x > lo_x && x < hi_x && y > lo_y && y < hi_y) && !(x%2) && !(y%2) ) )
			if ( ( x == lo_x || x == hi_x || y == lo_y || y == hi_y ) || (!(x%2) && !(y%2) ) )
			{
				if ( colours == 1 || colours == 3 )
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

void Monitor::DumpImage( Image *image ) const
{
	if ( image_count && !(image_count%10) )
	{
		static char new_filename[64];
		static char filename[64];
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

	struct timeval now;
	gettimeofday( &now, &dummy_tz );

	if ( image_count && !(image_count%fps_report_interval) )
	{
		fps = double(fps_report_interval)/(now.tv_sec-last_fps_time);
		Info(( "%s: %d - Processing at %.2f fps\n", name, image_count, fps ));
		last_fps_time = now.tv_sec;
	}

	int index = shared_images->last_write_index%image_buffer_count;
	Snapshot *snap = &image_buffer[index];
	struct timeval *timestamp = snap->timestamp;
	Image *image = snap->image;

	unsigned int score = 0;
	if ( Ready() )
	{
		score = Compare( *image );

		if ( shared_images->forced_alarm )
			score = ZM_FORCED_ALARM_SCORE;

		if ( score )
		{
			if ( state == IDLE )
			{
				event = new Event( this, *timestamp );

				Info(( "%s: %03d - Gone into alarm state\n", name, image_count ));
				int pre_index = ((index+image_buffer_count)-pre_event_count)%image_buffer_count;
				for ( int i = 0; i < pre_event_count; i++ )
				{
					event->AddFrame( *(image_buffer[pre_index].timestamp), image_buffer[pre_index].image );
					pre_index = (pre_index+1)%image_buffer_count;
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
				if ( image_count-last_alarm_count > post_event_count )
				{
					Info(( "%s: %03d - Left alarm state (%d) - %d(%d) images\n", name, image_count, event->Id(), event->Frames(), event->AlarmFrames() ));
					shared_images->last_event = event->Id();
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
						if ( record_event_stats )
						{
							zones[i]->RecordStats( event );
						}
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
	ref_image.Blend( *image, ref_blend_perc );
	//DumpImage( image );

	shared_images->last_read_index = index%image_buffer_count;
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
	n_zones = Zone::Load( this, zones );
	DumpZoneImage();
}

int Monitor::Load( int device, Monitor **&monitors, bool capture )
{
	static char sql[256];
	if ( device == -1 )
	{
		strcpy( sql, "select Id, Name, Function+0, Device, Channel, Format, Width, Height, Colours, LabelFormat, LabelX, LabelY, ImageBufferCount, WarmupCount, PreEventCount, PostEventCount, AlarmFrameCount, FPSReportInterval, RefBlendPerc from Monitors where Function != 'None'" );
	}
	else
	{
		sprintf( sql, "select Id, Name, Function+0, Device, Channel, Format, Width, Height, Colours, LabelFormat, LabelX, LabelY, ImageBufferCount, WarmupCount, PreEventCount, PostEventCount, AlarmFrameCount, FPSReportInterval, RefBlendPerc from Monitors where Function != 'None' and Device = %d", device );
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
		monitors[i] = new Monitor( atoi(dbrow[0]), dbrow[1], atoi(dbrow[2]), atoi(dbrow[3]), atoi(dbrow[4]), atoi(dbrow[5]), atoi(dbrow[6]), atoi(dbrow[7]), atoi(dbrow[8])/8, capture, dbrow[9], Coord( atoi(dbrow[10]), atoi(dbrow[11]) ), atoi(dbrow[12]), atoi(dbrow[13]), atoi(dbrow[14]), atoi(dbrow[15]), atoi(dbrow[16]), atoi(dbrow[17]), atoi(dbrow[18]) );
		Zone **zones = 0;
		int n_zones = Zone::Load( monitors[i], zones );
		monitors[i]->AddZones( n_zones, zones );
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
	sprintf( sql, "select Id, Name, Function+0, Device, Channel, Format, Width, Height, Colours, LabelFormat, LabelX, LabelY, ImageBufferCount, WarmupCount, PreEventCount, PostEventCount, AlarmFrameCount, FPSReportInterval, RefBlendPerc from Monitors where Id = %d", id );
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
		monitor = new Monitor( atoi(dbrow[0]), dbrow[1], atoi(dbrow[2]), atoi(dbrow[3]), atoi(dbrow[4]), atoi(dbrow[5]), atoi(dbrow[6]), atoi(dbrow[7]), atoi(dbrow[8])/8, false, dbrow[9], Coord( atoi(dbrow[10]), atoi(dbrow[11]) ), atoi(dbrow[12]), atoi(dbrow[13]), atoi(dbrow[14]), atoi(dbrow[15]), atoi(dbrow[16]), atoi(dbrow[17]), atoi(dbrow[18]) );
		int n_zones = 0;
		if ( load_zones )
		{
			Zone **zones = 0;
			n_zones = Zone::Load( monitor, zones );
			monitor->AddZones( n_zones, zones );
		}
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
	int last_read_index = image_buffer_count;
	JOCTET img_buffer[camera->ImageSize()];
	int img_buffer_size = 0;
	int loop_count = (idle/refresh)-1;
	while ( true )
	{
		if ( last_read_index != shared_images->last_write_index )
		{
			// Send the next frame
			last_read_index = shared_images->last_write_index;
			int index = shared_images->last_write_index%image_buffer_count;
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

bool Monitor::DumpSettings( char *output, bool verbose )
{
	output[0] = 0;

	sprintf( output+strlen(output), "Id : %d\n", id );
	sprintf( output+strlen(output), "Name : %s\n", name );
	sprintf( output+strlen(output), "Device : %d\n", ((LocalCamera *)camera)->Device() );
	sprintf( output+strlen(output), "Channel : %d\n", ((LocalCamera *)camera)->Channel() );
	sprintf( output+strlen(output), "Format : %d\n", ((LocalCamera *)camera)->Format() );
	sprintf( output+strlen(output), "Width : %d\n", ((LocalCamera *)camera)->Width() );
	sprintf( output+strlen(output), "Height : %d\n", camera->Height() );
	sprintf( output+strlen(output), "Colour Depth : %d\n", 8*camera->Colours() );
	sprintf( output+strlen(output), "Label Format : %s\n", label_format );
	sprintf( output+strlen(output), "Label Coord : %d,%d\n", label_coord.X(), label_coord.Y() );
	sprintf( output+strlen(output), "Warmup Count : %d\n", warmup_count );
	sprintf( output+strlen(output), "Pre Event Count : %d\n", pre_event_count );
	sprintf( output+strlen(output), "Post Event Count : %d\n", post_event_count );
	sprintf( output+strlen(output), "Alarm Frame Count : %d\n", alarm_frame_count );
	sprintf( output+strlen(output), "Image Buffer Count : %d\n", image_buffer_count );
	sprintf( output+strlen(output), "Reference Blend %%ge : %d\n", ref_blend_perc );
	sprintf( output+strlen(output), "Function: %d - %s\n", function,
		function==NONE?"None":(
		function==ACTIVE?"Active":(
		function==PASSIVE?"Passive":(
		function==X10?"X10":"Unknown"
	))));
	sprintf( output+strlen(output), "Zones : %d\n", n_zones );
	for ( int i = 0; i < n_zones; i++ )
	{
		zones[i]->DumpSettings( output+strlen(output), verbose );
    }
	return( true );
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

unsigned int Monitor::Compare( const Image &image )
{
	bool alarm = false;
	unsigned int score = 0;

	if ( n_zones <= 0 ) return( alarm );

	Image *delta_image = ref_image.Delta( image );

	// Blank out all exclusion zones
	for ( int n_zone = 0; n_zone < n_zones; n_zone++ )
	{
		Zone *zone = zones[n_zone];
		zone->ClearAlarm();
		Debug( 3, ( "Blanking inactive zone %s", zone->Label() ));
		if ( !zone->IsInactive() )
		{
			continue;
		}

		delta_image->Fill( RGB_BLACK, &(zone->Limits()) );
	}

	// Find all alarm pixels in active zones
	for ( int n_zone = 0; n_zone < n_zones; n_zone++ )
	{
		Zone *zone = zones[n_zone];
		if ( !zone->IsActive() )
		{
			continue;
		}
		Debug( 3, ( "Checking active zone %s", zone->Label() ));
		if ( zone->CheckAlarms( delta_image ) )
		{
			alarm = true;
			score += zone->Score();
			zone->SetAlarm();
			Debug( 3, ( "Zone is alarmed, zone score = %d", zone->Score() ));
		}
	}

	if ( alarm )
	{
		for ( int n_zone = 0; n_zone < n_zones; n_zone++ )
		{
			Zone *zone = zones[n_zone];
			if ( !zone->IsInclusive() )
			{
				continue;
			}
			Debug( 3, ( "Checking inclusive zone %s", zone->Label() ));
			if ( zone->CheckAlarms( delta_image ) )
			{
				alarm = true;
				score += zone->Score();
				zone->SetAlarm();
				Debug( 3, ( "Zone is alarmed, zone score = %d", zone->Score() ));
			}
		}
	}
	else
	{
		// Find all alarm pixels in exclusion zones
		for ( int n_zone = 0; n_zone < n_zones; n_zone++ )
		{
			Zone *zone = zones[n_zone];
			if ( !zone->IsExclusive() )
			{
				continue;
			}
			Debug( 3, ( "Checking exclusive zone %s", zone->Label() ));
			if ( zone->CheckAlarms( delta_image ) )
			{
				alarm = true;
				score += zone->Score();
				zone->SetAlarm();
				Debug( 3, ( "Zone is alarmed, zone score = %d", zone->Score() ));
			}
		}
	}

	delete delta_image;
	// This is a small and innocent hack to prevent scores of 0 being returned in alarm state
	return( score?score:alarm );
} 

bool Zone::DumpSettings( char *output, bool verbose )
{
	output[0] = 0;

	sprintf( output+strlen(output), "  Id : %d\n", id );
	sprintf( output+strlen(output), "  Label : %s\n", label );
	sprintf( output+strlen(output), "  Type: %d - %s\n", type,
		type==ACTIVE?"Active":(
		type==INCLUSIVE?"Inclusive":(
		type==EXCLUSIVE?"Exclusive":(
		type==INACTIVE?"Inactive":"Unknown"
	))));
	sprintf( output+strlen(output), "  Limits : %d,%d - %d,%d\n", limits.LoX(), limits.LoY(), limits.HiX(), limits.HiY() );
	sprintf( output+strlen(output), "  Alarm RGB : %06x\n", alarm_rgb );
	sprintf( output+strlen(output), "  Alarm Threshold : %d\n", alarm_threshold );
	sprintf( output+strlen(output), "  Min Alarm Pixels : %d\n", min_alarm_pixels );
	sprintf( output+strlen(output), "  Max Alarm Pixels : %d\n", max_alarm_pixels );
	sprintf( output+strlen(output), "  Filter Box : %d,%d\n", filter_box.X(), filter_box.Y() );
	sprintf( output+strlen(output), "  Min Filter Pixels : %d\n", min_filter_pixels );
	sprintf( output+strlen(output), "  Max Filter Pixels : %d\n", max_filter_pixels );
	sprintf( output+strlen(output), "  Min Blob Pixels : %d\n", min_blob_pixels );
	sprintf( output+strlen(output), "  Max Blob Pixels : %d\n", max_blob_pixels );
	sprintf( output+strlen(output), "  Min Blobs : %d\n", min_blobs );
	sprintf( output+strlen(output), "  Max Blobs : %d\n", max_blobs );
	return( true );
}

