//
// ZoneMinder Zone Class Implementation, $Date$, $Revision$
// Copyright (C) 2003, 2004, 2005  Philip Coombes
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
#include "zm_db.h"
#include "zm_zone.h"
#include "zm_image.h"
#include "zm_monitor.h"

void Zone::Setup( Monitor *p_monitor, int p_id, const char *p_label, ZoneType p_type, const Polygon &p_polygon, const Rgb p_alarm_rgb, CheckMethod p_check_method, int p_min_pixel_threshold, int p_max_pixel_threshold, int p_min_alarm_pixels, int p_max_alarm_pixels, const Coord &p_filter_box, int p_min_filter_pixels, int p_max_filter_pixels, int p_min_blob_pixels, int p_max_blob_pixels, int p_min_blobs, int p_max_blobs )
{
	monitor = p_monitor;

	id = p_id;
	label = new char[strlen(p_label)+1];
	strcpy( label, p_label );
	type = p_type;
	polygon = p_polygon;
	alarm_rgb = p_alarm_rgb;
	check_method = p_check_method;
	min_pixel_threshold = p_min_pixel_threshold;
	max_pixel_threshold = p_max_pixel_threshold;
	min_alarm_pixels = p_min_alarm_pixels;
	max_alarm_pixels = p_max_alarm_pixels;
	filter_box = p_filter_box;
	min_filter_pixels = p_min_filter_pixels;
	max_filter_pixels = p_max_filter_pixels;
	min_blob_pixels = p_min_blob_pixels;
	max_blob_pixels = p_max_blob_pixels;
	min_blobs = p_min_blobs;
	max_blobs = p_max_blobs;

	Debug( 1, ( "Initialised zone %d/%s - %d - %dx%d - Rgb:%06x, CM:%d, MnAT:%d, MxAT:%d, MnAP:%d, MxAP:%d, FB:%dx%d, MnFP:%d, MxFP:%d, MnBS:%d, MxBS:%d, MnB:%d, MxB:%d", id, label, type, polygon.Width(), polygon.Height(), alarm_rgb, check_method, min_pixel_threshold, max_pixel_threshold, min_alarm_pixels, max_alarm_pixels, filter_box.X(), filter_box.Y(), min_filter_pixels, max_filter_pixels, min_blob_pixels, max_blob_pixels, min_blobs, max_blobs ));

	alarmed = false;
	alarm_pixels = 0;
	alarm_filter_pixels = 0;
	alarm_blob_pixels = 0;
	alarm_blobs = 0;
	min_blob_size = 0;
	max_blob_size = 0;
	image = 0;
	score = 0;

	pg_image = new Image( polygon.Width(), polygon.Height(), 1 );
	pg_image->Fill( 0xff, polygon );
	pg_image->Outline( 0xff, polygon );

	ranges = new Range[polygon.Height()];
	int y = polygon.LoY();
	for ( int py = 0; py < polygon.Height(); py++, y++ )
	{
		int x = polygon.LoX();
		ranges[py].lo_x = -1;
		ranges[py].hi_x = -1;
		unsigned char *ppoly = pg_image->Buffer( x, y );
		for ( int px = 0; px < polygon.Width(); px++, x++, ppoly++ )
		{
			if ( *ppoly )
			{
				if ( ranges[y].lo_x == -1 )
					ranges[y].lo_x = x;
			}
			else
			{
				if ( ranges[y].lo_x != -1 && ranges[y].hi_x < x )
					ranges[y].hi_x = x;
			}
		}
	}
}

Zone::~Zone()
{
	delete[] label;
	delete image;
	delete pg_image;
	delete[] ranges;
}

void Zone::RecordStats( const Event *event )
{
	static char sql[BUFSIZ];
	snprintf( sql, sizeof(sql), "insert into Stats set MonitorId=%d, ZoneId=%d, EventId=%d, FrameId=%d, AlarmPixels=%d, FilterPixels=%d, BlobPixels=%d, Blobs=%d, MinBlobSize=%d, MaxBlobSize=%d, MinX=%d, MinY=%d, MaxX=%d, MaxY=%d, Score=%d", monitor->Id(), id, event->Id(), event->Frames()+1, alarm_pixels, alarm_filter_pixels, alarm_blob_pixels, alarm_blobs, min_blob_size, max_blob_size, alarm_box.LoX(), alarm_box.LoY(), alarm_box.HiX(), alarm_box.HiY(), score );
	if ( mysql_query( &dbconn, sql ) )
	{
		Error(( "Can't insert event stats: %s", mysql_error( &dbconn ) ));
		exit( mysql_errno( &dbconn ) );
	}
}

bool Zone::CheckAlarms( const Image *delta_image )
{
	bool alarm = false;

	ResetStats();

	delete image;
	// Get the difference image
	Image *diff_image = image = new Image( *delta_image );

	int alarm_lo_x = 0;
	int alarm_hi_x = 0;
	int alarm_lo_y = 0;
	int alarm_hi_y = 0;

	int alarm_mid_x = -1;
	int alarm_mid_y = -1;

	int lo_y = polygon.LoY();
	int hi_y = polygon.HiY();

	unsigned char *pdiff, *ppoly;
	for ( int y = lo_y, py = 0; y <= hi_y; y++, py++ )
	{
		int lo_x = ranges[py].lo_x;
		int hi_x = ranges[py].hi_x;

		pdiff = diff_image->Buffer( lo_x, y );
		ppoly = pg_image->Buffer( lo_x, y );
		for ( int x = lo_x; x <= hi_x; x++, pdiff++, ppoly++ )
		{
			if ( *ppoly && (*pdiff > min_pixel_threshold) && (!max_pixel_threshold || (*pdiff < max_pixel_threshold)) )
			{
				*pdiff = WHITE;
				alarm_pixels++;
			}
			else
			{
				*pdiff = BLACK;
			}
		}
	}
	if ( config.record_diag_images )
	{
		static char diag_path[PATH_MAX] = "";
		if ( !diag_path[0] )
		{
			snprintf( diag_path, sizeof(diag_path), "%s/%s/diag-%d-%d.jpg", config.dir_events, monitor->Name(), id, 1 );
		}
		diff_image->WriteJpeg( diag_path );
	}

	if ( !alarm_pixels ) return( false );
	if ( min_alarm_pixels && alarm_pixels < min_alarm_pixels ) return( false );
	if ( max_alarm_pixels && alarm_pixels > max_alarm_pixels ) return( false );

	score = (100*alarm_pixels)/polygon.Area();

	if ( check_method >= FILTERED_PIXELS )
	{
		int bx = filter_box.X();
		int by = filter_box.Y();
		int bx1 = bx-1;
		int by1 = by-1;

		if ( bx > 1 || by > 1 )
		{
			// Now remove any pixels smaller than our filter size
			unsigned char *pdiff;
			unsigned char *cpdiff;
			int ldx, hdx, ldy, hdy;
			bool block;
			for ( int y = lo_y, py = 0; y <= hi_y; y++, py++ )
			{
				int lo_x = ranges[py].lo_x;
				int hi_x = ranges[py].hi_x;

				pdiff = diff_image->Buffer( lo_x, y );

				for ( int x = lo_x; x <= hi_x; x++, pdiff++ )
				{
					if ( *pdiff == WHITE )
					{
						// Check participation in an X block
						ldx = (x>=(lo_x+bx1))?-bx1:lo_x-x;
						hdx = (x<=(hi_x-bx1))?0:((hi_x-x)-bx1);
						ldy = (y>=(lo_y+by1))?-by1:lo_y-y;
						hdy = (y<=(hi_y-by1))?0:((hi_y-y)-by1);
						block = false;
						for ( int dy = ldy; !block && dy <= hdy; dy++ )
						{
							for ( int dx = ldx; !block && dx <= hdx; dx++ )
							{
								block = true;
								for ( int dy2 = 0; block && dy2 < by; dy2++ )
								{
									for ( int dx2 = 0; block && dx2 < bx; dx2++ )
									{
										cpdiff = diff_image->Buffer( x+dx+dx2, y+dy+dy2 );
										if ( !*cpdiff )
										{
											block = false;
										}
									}
								}
							}
						}
						if ( !block )
						{
							*pdiff = BLACK;
							continue;
						}
						alarm_filter_pixels++;
					}
				}
			}
		}
		else
		{
			alarm_filter_pixels = alarm_pixels;
		}
		if ( config.record_diag_images )
		{
			static char diag_path[PATH_MAX] = "";
			if ( !diag_path[0] )
			{
				snprintf( diag_path, sizeof(diag_path), "%s/%d/diag-%d-%d.jpg", config.dir_events, monitor->Id(), id, 2 );
			}
			diff_image->WriteJpeg( diag_path );
		}

		if ( !alarm_filter_pixels ) return( false );
		if ( min_filter_pixels && alarm_filter_pixels < min_filter_pixels ) return( false );
		if ( max_filter_pixels && alarm_filter_pixels > max_filter_pixels ) return( false );

		score = (100*alarm_filter_pixels)/(polygon.Area());

		if ( check_method >= BLOBS )
		{
			typedef struct { unsigned char tag; int count; int lo_x; int hi_x; int lo_y; int hi_y; } BlobStats;
			BlobStats blob_stats[256];
			memset( blob_stats, 0, sizeof(BlobStats)*256 );
			unsigned char *pdiff, *spdiff;
			int last_x, last_y;
			BlobStats *bsx, *bsy;
			BlobStats *bsm, *bss;
			int diff_width = diff_image->Width();
			for ( int y = lo_y, py = 0; y <= hi_y; y++, py++ )
			{
				int lo_x = ranges[py].lo_x;
				int hi_x = ranges[py].hi_x;

				pdiff = diff_image->Buffer( lo_x, y );
				for ( int x = lo_x; x <= hi_x; x++, pdiff++ )
				{
					if ( *pdiff == WHITE )
					{
						//printf( "Got white pixel at %d,%d (%x)\n", x, y, pdiff );
						last_x = x>lo_x?*(pdiff-1):0;
						last_y = y>lo_y?*(pdiff-diff_width):0;
						if ( last_x )
						{
							//printf( "Left neighbour is %d\n", last_x );
							bsx = &blob_stats[last_x];
							if ( last_y )
							{
								//printf( "Top neighbour is %d\n", last_y );
								bsy = &blob_stats[last_y];
								if ( last_x == last_y )
								{
									//printf( "Matching neighbours, setting to %d\n", last_x );
									// Add to the blob from the x side (either side really)
									*pdiff = last_x;
									bsx->count++;
									if ( x > bsx->hi_x ) bsx->hi_x = x;
									if ( y > bsx->hi_y ) bsx->hi_y = y;
								}
								else
								{
									// Aggregate blobs
									bsm = bsx->count>=bsy->count?bsx:bsy;
									bss = bsm==bsx?bsy:bsx;

									//printf( "Different neighbours, setting pixels of %d to %d\n", bss->tag, bsm->tag );
									// Now change all those pixels to the other setting
									for ( int sy = bss->lo_y; sy <= bss->hi_y; sy++ )
									{
										spdiff = diff_image->Buffer( bss->lo_x, sy );
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

									alarm_blobs--;

									Debug( 2, ( "Merging blob %d with %d, %d current blobs", bss->tag, bsm->tag, alarm_blobs ));

									// Clear out the old blob
									bss->tag = 0;
									bss->count = 0;
									bss->lo_x = 0;
									bss->lo_y = 0;
									bss->hi_x = 0;
									bss->hi_y = 0;
								}
							}
							else
							{
								//printf( "Setting to left neighbour %d\n", last_x );
								// Add to the blob from the x side 
								*pdiff = last_x;
								bsx->count++;
								if ( x > bsx->hi_x ) bsx->hi_x = x;
								if ( y > bsx->hi_y ) bsx->hi_y = y;
							}
						}
						else
						{
							if ( last_y )
							{
								//printf( "Setting to top neighbour %d\n", last_y );

								// Add to the blob from the y side
								BlobStats *bsy = &blob_stats[last_y];

								*pdiff = last_y;
								bsy->count++;
								if ( x > bsy->hi_x ) bsy->hi_x = x;
								if ( y > bsy->hi_y ) bsy->hi_y = y;
							}
							else
							{
								// Create a new blob
								int i;
								for ( i = (WHITE-1); i > 0; i-- )
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
										alarm_blobs++;

										Debug( 2, ( "Created blob %d, %d current blobs", bs->tag, alarm_blobs ));
										break;
									}
								}
								if ( i == 0 )
								{
									i = 1;
									Warning(( "Max blob count reached. Unable to allocate new blob so extending last one." ));
									BlobStats *bs = &blob_stats[i];
									*pdiff = i;
									if ( x > bs->hi_x ) bs->hi_x = x;
									if ( y > bs->hi_y ) bs->hi_y = y;
								}
							}
						}
					}
				}
			}
			if ( config.record_diag_images )
			{
				static char diag_path[PATH_MAX] = "";
				if ( !diag_path[0] )
				{
					snprintf( diag_path, sizeof(diag_path), "%s/%d/diag-%d-%d.jpg", config.dir_events, monitor->Id(), id, 3 );
				}
				diff_image->WriteJpeg( diag_path );
			}

			if ( !alarm_blobs ) return( false );
			alarm_blob_pixels = alarm_filter_pixels;

			// Now eliminate blobs under the threshold
			for ( int i = 1; i < WHITE; i++ )
			{
				BlobStats *bs = &blob_stats[i];
				if ( bs->count && ((min_blob_pixels && bs->count < min_blob_pixels) || (max_blob_pixels && bs->count > max_blob_pixels)) )
				{
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
					alarm_blobs--;
					alarm_blob_pixels -= bs->count;
					
					Debug( 2, ( "Eliminated blob %d, %d pixels (%d,%d - %d,%d), %d current blobs", i, bs->count, bs->lo_x, bs->lo_y, bs->hi_x, bs->hi_y, alarm_blobs ));

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
			if ( config.record_diag_images )
			{
				static char diag_path[PATH_MAX] = "";
				if ( !diag_path[0] )
				{
					snprintf( diag_path, sizeof(diag_path), "%s/%d/diag-%d-%d.jpg", config.dir_events, monitor->Id(), id, 4 );
				}
				diff_image->WriteJpeg( diag_path );
			}

			if ( !alarm_blobs ) return( false );
			if ( min_blobs && alarm_blobs < min_blobs ) return( false );
			if ( max_blobs && alarm_blobs > max_blobs ) return( false );

			alarm_lo_x = polygon.HiX()+1;
			alarm_hi_x = polygon.LoX()-1;
			alarm_lo_y = polygon.HiY()+1;
			alarm_hi_y = polygon.LoY()-1;
			for ( int i = 1; i < WHITE; i++ )
			{
				BlobStats *bs = &blob_stats[i];
				if ( bs->count )
				{
					if ( bs->count == max_blob_size )
					{
						//if ( monitor->followMotion() )
						if ( true )
						{
							unsigned long x_total = 0;
							unsigned long y_total = 0;

							for ( int sy = bs->lo_y; sy <= bs->hi_y; sy++ )
							{
								unsigned char *spdiff = diff_image->Buffer( bs->lo_x, sy );
								for ( int sx = bs->lo_x; sx <= bs->hi_x; sx++, spdiff++ )
								{
									if ( *spdiff == bs->tag )
									{
										x_total += sx;
										y_total += sy;
									}
								}
							}

							alarm_mid_x = int(round(x_total/bs->count));
							alarm_mid_y = int(round(y_total/bs->count));
						}
					}

					if ( alarm_lo_x > bs->lo_x ) alarm_lo_x = bs->lo_x;
					if ( alarm_lo_y > bs->lo_y ) alarm_lo_y = bs->lo_y;
					if ( alarm_hi_x < bs->hi_x ) alarm_hi_x = bs->hi_x;
					if ( alarm_hi_y < bs->hi_y ) alarm_hi_y = bs->hi_y;
				}
			}
			score = ((100*alarm_blob_pixels)/int(sqrt((double)alarm_blobs)))/(polygon.Area());
		}
	}

	if ( type == INCLUSIVE )
	{
		score /= 2;
	}
	else if ( type == EXCLUSIVE )
	{
		score *= 2;
	}

	// Now outline the changed region
	if ( score )
	{
		alarm = true;

		alarm_box = Box( Coord( alarm_lo_x, alarm_lo_y ), Coord( alarm_hi_x, alarm_hi_y ) );

		//if ( monitor->followMotion() )
		if ( true )
		{
			alarm_centre = Coord( alarm_mid_x, alarm_mid_y );
		}
		else
		{
			alarm_centre = alarm_box.Centre();
		}

		if ( (type < PRECLUSIVE) && check_method >= BLOBS && config.create_analysis_images )
		{
			image = diff_image->HighlightEdges( alarm_rgb, &polygon.Extent() );
			// Only need to delete this when 'image' becomes detached and points somewhere else
			delete diff_image;
		}
		else
		{
			delete image;
			image = 0;
		}

		Debug( 1, ( "%s: Alarm Pixels: %d, Filter Pixels: %d, Blob Pixels: %d, Blobs: %d, Score: %d", Label(), alarm_pixels, alarm_filter_pixels, alarm_blob_pixels, alarm_blobs, score ));
	}
	return( true );
}

bool Zone::ParsePolygonString( const char *poly_string, Polygon &polygon )
{
	Debug( 3, ( "Parsing polygon string '%s'", poly_string ));

	char *str_ptr = new char[strlen(poly_string)+1];
	char *str = str_ptr;
	strcpy( str, poly_string );

	char *ws;
	int n_coords = 0;
	int max_n_coords = strlen(str)/4;
	Coord *coords = new Coord[max_n_coords];
	while( true )
	{
		if ( *str == '\0' )
		{
			break;
		}
		ws = strchr( str, ' ' );
		if ( ws )
		{
			*ws = '\0';
		}
		char *cp = strchr( str, ',' );
		if ( !cp )
		{
			Error(( "Bogus coordinate %s found in polygon string", str ));
			delete[] coords;
			delete[] str_ptr;
			return( false );
		}
		else
		{
			*cp = '\0';
			char *xp = str;
			char *yp = cp+1;

			int x = atoi(xp);
			int y = atoi(yp);

			Debug( 3, ( "Got coordinate %d,%d from polygon string", x, y ));
#if 0
			if ( x < 0 )
				x = 0;
			else if ( x >= width )
				x = width-1;
			if ( y < 0 )
				y = 0;
			else if ( y >= height )
				y = height-1;
#endif
			coords[n_coords++] = Coord( x, y );
		}
		if ( ws )
			str = ws+1;
		else
			break;
	}
	polygon = Polygon( n_coords, coords );

	Debug( 3, ( "Successfully parsed polygon string" ));
	//printf( "Area: %d\n", pg.Area() );
	//printf( "Centre: %d,%d\n", pg.Centre().X(), pg.Centre().Y() );

	delete[] coords;
	delete[] str_ptr;

	return( true );
}

bool Zone::ParseZoneString( const char *zone_string, int &zone_id, int &colour, Polygon &polygon )
{
	Debug( 3, ( "Parsing zone string '%s'", zone_string ));

	char *str_ptr = new char[strlen(zone_string)+1];
	char *str = str_ptr;
	strcpy( str, zone_string );

	char *ws = strchr( str, ' ' );
	if ( !ws )
	{
		Debug( 3, ( "No whitespace found in zone string, finishing", zone_string ));
	}
	zone_id = strtol( str, 0, 10 );
	Debug( 3, ( "Got zone %d from zone string", zone_id ));
	if ( !ws )
	{
		delete str_ptr;
		return( true );
	}

	*ws = '\0';
	str = ws+1;

	ws = strchr( str, ' ' );
	if ( !ws )
	{
		Error(( "No whitespace found in zone string '%s'", zone_string ));
		delete[] str_ptr;
		return( false );
	}
	*ws = '\0';
	colour = strtol( str, 0, 16 );
	Debug( 3, ( "Got colour %06x from zone string", colour ));
	str = ws+1;

	bool result = ParsePolygonString( str, polygon );

	//printf( "Area: %d\n", pg.Area() );
	//printf( "Centre: %d,%d\n", pg.Centre().X(), pg.Centre().Y() );

	delete[] str_ptr;

	return( result );
}

int Zone::Load( Monitor *monitor, Zone **&zones )
{
	static char sql[BUFSIZ];
	snprintf( sql, sizeof(sql), "select Id,Name,Type+0,Units,NumCoords,Coords,AlarmRGB,CheckMethod+0,MinPixelThreshold,MaxPixelThreshold,MinAlarmPixels,MaxAlarmPixels,FilterX,FilterY,MinFilterPixels,MaxFilterPixels,MinBlobPixels,MaxBlobPixels,MinBlobs,MaxBlobs from Zones where MonitorId = %d order by Type, Id", monitor->Id() );
	if ( mysql_query( &dbconn, sql ) )
	{
		Error(( "Can't run query: %s", mysql_error( &dbconn ) ));
		exit( mysql_errno( &dbconn ) );
	}

	MYSQL_RES *result = mysql_store_result( &dbconn );
	if ( !result )
	{
		Error(( "Can't use query result: %s", mysql_error( &dbconn ) ));
		exit( mysql_errno( &dbconn ) );
	}
	int n_zones = mysql_num_rows( result );
	Debug( 1, ( "Got %d zones for monitor %s", n_zones, monitor->Name() ));
	delete[] zones;
	zones = new Zone *[n_zones];
	for( int i = 0; MYSQL_ROW dbrow = mysql_fetch_row( result ); i++ )
	{
		int col = 0;

		int Id = atoi(dbrow[col++]);
		const char *Name = dbrow[col++];
		int Type = atoi(dbrow[col++]);
		const char *Units = dbrow[col++];
		int NumCoords = atoi(dbrow[col++]);
		const char *Coords = dbrow[col++];
		int AlarmRGB = dbrow[col]?atoi(dbrow[col]):0; col++;
		int CheckMethod = atoi(dbrow[col++]);
		int MinPixelThreshold = dbrow[col]?atoi(dbrow[col]):0; col++;
		int MaxPixelThreshold = dbrow[col]?atoi(dbrow[col]):0; col++;
		int MinAlarmPixels = dbrow[col]?atoi(dbrow[col]):0; col++;
		int MaxAlarmPixels = dbrow[col]?atoi(dbrow[col]):0; col++;
		int FilterX = dbrow[col]?atoi(dbrow[col]):0; col++;
		int FilterY = dbrow[col]?atoi(dbrow[col]):0; col++;
		int MinFilterPixels = dbrow[col]?atoi(dbrow[col]):0; col++;
		int MaxFilterPixels = dbrow[col]?atoi(dbrow[col]):0; col++;
		int MinBlobPixels = dbrow[col]?atoi(dbrow[col]):0; col++;
		int MaxBlobPixels = dbrow[col]?atoi(dbrow[col]):0; col++;
		int MinBlobs = dbrow[col]?atoi(dbrow[col]):0; col++;
		int MaxBlobs = dbrow[col]?atoi(dbrow[col]):0; col++;

		Polygon polygon;
		if ( !ParsePolygonString( Coords, polygon ) )
			continue;

		if ( false && !strcmp( Units, "Percent" ) )
		{
			MinAlarmPixels = (MinAlarmPixels*polygon.Area())/100;
			MaxAlarmPixels = (MaxAlarmPixels*polygon.Area())/100;
			MinFilterPixels = (MinFilterPixels*polygon.Area())/100;
			MaxFilterPixels = (MaxFilterPixels*polygon.Area())/100;
			MinBlobPixels = (MinBlobPixels*polygon.Area())/100;
			MaxBlobPixels = (MaxBlobPixels*polygon.Area())/100;
		}

		if ( atoi(dbrow[2]) == Zone::INACTIVE )
		{
			zones[i] = new Zone( monitor, Id, Name, polygon );
		}
		else
		{
			zones[i] = new Zone( monitor, Id, Name, (Zone::ZoneType)Type, polygon, AlarmRGB, (Zone::CheckMethod)CheckMethod, MinPixelThreshold, MaxPixelThreshold, MinAlarmPixels, MaxAlarmPixels, Coord( FilterX, FilterY ), MinFilterPixels, MaxFilterPixels, MinBlobPixels, MaxBlobPixels, MinBlobs, MaxBlobs );
		}
	}
	if ( mysql_errno( &dbconn ) )
	{
		Error(( "Can't fetch row: %s", mysql_error( &dbconn ) ));
		exit( mysql_errno( &dbconn ) );
	}
	// Yadda yadda
	mysql_free_result( result );
	return( n_zones );
}

bool Zone::DumpSettings( char *output, bool /*verbose*/ )
{
	output[0] = 0;

	sprintf( output+strlen(output), "  Id : %d\n", id );
	sprintf( output+strlen(output), "  Label : %s\n", label );
	sprintf( output+strlen(output), "  Type: %d - %s\n", type,
		type==ACTIVE?"Active":(
		type==INCLUSIVE?"Inclusive":(
		type==EXCLUSIVE?"Exclusive":(
		type==PRECLUSIVE?"Preclusive":(
		type==INACTIVE?"Inactive":"Unknown"
	)))));
	//sprintf( output+strlen(output), "  Limits : %d,%d - %d,%d\n", limits.LoX(), limits.LoY(), limits.HiX(), limits.HiY() );
	sprintf( output+strlen(output), "  Alarm RGB : %06x\n", alarm_rgb );
	sprintf( output+strlen(output), "  Check Method: %d - %s\n", check_method,
		check_method==ALARMED_PIXELS?"Alarmed Pixels":(
		check_method==FILTERED_PIXELS?"FilteredPixels":(
		check_method==BLOBS?"Blobs":"Unknown"
	)));
	sprintf( output+strlen(output), "  Min Pixel Threshold : %d\n", min_pixel_threshold );
	sprintf( output+strlen(output), "  Max Pixel Threshold : %d\n", max_pixel_threshold );
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

