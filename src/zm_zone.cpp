//
// ZoneMinder Zone Class Implementation, $Date$, $Revision$
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
#include "zm_db.h"
#include "zm_zone.h"
#include "zm_image.h"
#include "zm_monitor.h"

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

	Info(( "Initialised zone %d/%s - %d - %dx%d - Rgb:%06x, AT:%d, MnAP:%d, MxAP:%d, FB:%dx%d, MnFP:%d, MxFP:%d, MnBS:%d, MxBS:%d, MnB:%d, MxB:%d", id, label, type, limits.Width(), limits.Height(), alarm_rgb, alarm_threshold, min_alarm_pixels, max_alarm_pixels, filter_box.X(), filter_box.Y(), min_filter_pixels, max_filter_pixels, min_blob_pixels, max_blob_pixels, min_blobs, max_blobs ));

	alarmed = false;
	alarm_pixels = 0;
	alarm_filter_pixels = 0;
	alarm_blob_pixels = 0;
	alarm_blobs = 0;
	min_blob_size = 0;
	max_blob_size = 0;
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
	static char sql[BUFSIZ];
	sprintf( sql, "insert into Stats set MonitorId=%d, ZoneId=%d, EventId=%d, FrameId=%d, AlarmPixels=%d, FilterPixels=%d, BlobPixels=%d, Blobs=%d, MinBlobSize=%d, MaxBlobSize=%d, MinX=%d, MinY=%d, MaxX=%d, MaxY=%d, Score=%d", monitor->Id(), id, event->Id(), event->Frames()+1, alarm_pixels, alarm_filter_pixels, alarm_blob_pixels, alarm_blobs, min_blob_size, max_blob_size, alarm_box.LoX(), alarm_box.LoY(), alarm_box.HiX(), alarm_box.HiY(), score );
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
	Image *diff_image = image = new Image( *delta_image );

	alarm_pixels = 0;

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

	if ( !alarm_pixels ) return( false );
	if ( min_alarm_pixels && alarm_pixels < min_alarm_pixels ) return( false );
	if ( max_alarm_pixels && alarm_pixels > max_alarm_pixels ) return( false );

	alarm_filter_pixels = 0;

	int bx = filter_box.X();
	int by = filter_box.Y();
	int bx1 = bx-1;
	int by1 = by-1;

	// Now remove any pixels smaller than our filter size
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
				alarm_filter_pixels++;
			}
		}
	}

	if ( !alarm_filter_pixels ) return( false );
	if ( min_filter_pixels && alarm_filter_pixels < min_filter_pixels ) return( false );
	if ( max_filter_pixels && alarm_filter_pixels > max_filter_pixels ) return( false );

	alarm_blobs = 0;

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
							// Aggregate blobs
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

							alarm_blobs--;
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
								alarm_blobs++;
								break;
							}
						}
					}
				}
			}
		}
	}


	if ( !alarm_blobs ) return( false );
	alarm_blob_pixels = alarm_filter_pixels;

	min_blob_size = 0;
	max_blob_size = 0;
	// Now eliminate blobs under the alarm_threshold
	for ( int i = 1; i < WHITE; i++ )
	{
		BlobStats *bs = &blob_stats[i];
		if ( bs->count && ((min_blob_pixels && bs->count < min_blob_pixels) || (max_blob_pixels && bs->count > max_blob_pixels)) )
		{
			//Info(( "Eliminating blob %d, %d pixels (%d,%d - %d,%d)", i, bs->count, bs->lo_x, bs->lo_y, bs->hi_x, bs->hi_y ));
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

	if ( !alarm_blobs ) return( false );
	if ( min_blobs && alarm_blobs < min_blobs ) return( false );
	if ( max_blobs && alarm_blobs > max_blobs ) return( false );

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

	alarm_box = Box( Coord( alarm_lo_x, alarm_lo_y ), Coord( alarm_hi_x, alarm_hi_y ) );
	score = ((100*alarm_blob_pixels)/int(sqrt((double)alarm_blobs)))/(limits.Size().X()*limits.Size().Y());
	if ( type == INCLUSIVE )
	{
		score /= 2;
	}
	else if ( type == EXCLUSIVE )
	{
		score *= 2;
	}

	// Now outline the changed region
	if ( alarm_blobs )
	{
		alarm = true;

		if ( (type < PRECLUSIVE) && (bool)config.Item( ZM_CREATE_ANALYSIS_IMAGES ) )
		{
			image = diff_image->HighlightEdges( alarm_rgb, &limits );

			// Only need to delete this when 'image' becomes detached and points somewhere else
			delete diff_image;
		}

		Info(( "%s: Alarm Pixels: %d, Filter Pixels: %d, Blob Pixels: %d, Blobs: %d, Score: %d", Label(), alarm_pixels, alarm_filter_pixels, alarm_blob_pixels, alarm_blobs, score ));
	}
	return( true );
}

int Zone::Load( Monitor *monitor, Zone **&zones )
{
	static char sql[BUFSIZ];
	sprintf( sql, "select Id,Name,Type+0,Units,LoX,LoY,HiX,HiY,AlarmRGB,AlarmThreshold,MinAlarmPixels,MaxAlarmPixels,FilterX,FilterY,MinFilterPixels,MaxFilterPixels,MinBlobPixels,MaxBlobPixels,MinBlobs,MaxBlobs from Zones where MonitorId = %d order by Type, Id", monitor->Id() );
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
	Info(( "Got %d zones for monitor %s", n_zones, monitor->Name() ));
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
			LoX = (LoX*(monitor->Width()-1))/100;
			LoY = (LoY*(monitor->Height()-1))/100;
			HiX = (HiX*(monitor->Width()-1))/100;
			HiY = (HiY*(monitor->Height()-1))/100;
			MinAlarmPixels = (MinAlarmPixels*monitor->Width()*monitor->Height())/100;
			MaxAlarmPixels = (MaxAlarmPixels*monitor->Width()*monitor->Height())/100;
			MinFilterPixels = (MinFilterPixels*monitor->Width()*monitor->Height())/100;
			MaxFilterPixels = (MaxFilterPixels*monitor->Width()*monitor->Height())/100;
			MinBlobPixels = (MinBlobPixels*monitor->Width()*monitor->Height())/100;
			MaxBlobPixels = (MaxBlobPixels*monitor->Width()*monitor->Height())/100;
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

