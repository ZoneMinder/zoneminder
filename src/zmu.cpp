//
// ZoneMinder Control Utility, $Date$, $Revision$
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

#include <getopt.h>
#include "zm.h"

void Usage()
{
	fprintf( stderr, "zmu [-m monitor_id] [function]\n" );
	fprintf( stderr, "Options:\n" );
	fprintf( stderr, "  -m, --monitor <monitor_id>     : Specify which monitor to address, default 1 if absent\n" );
	fprintf( stderr, "  -s, --state                    : Output the current monitor state, 0 = idle, 1 = alarm, 2 = alert\n" );
	fprintf( stderr, "  -i, --image [image_index]      : Write captured image to disk as <monitor_name>.jpg, last image captured\n" );
	fprintf( stderr, "                                   or specified ring buffer index if given.\n" );
	fprintf( stderr, "  -t, --timestamp [image_index]  : Output captured image timestamp, last image captured or specified\n" );
	fprintf( stderr, "                                   ring buffer index if given\n" );
	fprintf( stderr, "  -r, --read_index               : Output ring buffer read index\n" );
	fprintf( stderr, "  -w, --write_index              : Output ring buffer write index\n" );
	fprintf( stderr, "  -e, --event                    : Output last event index\n" );
	fprintf( stderr, "  -f, --fps                      : Output last Frames Per Second captured reading\n" );
	fprintf( stderr, "  -z, --zones                    : Write last captured image overlaid with zones to <monitor_name>-Zones.jpg\n" );
	fprintf( stderr, "  -a, --alarm                    : Force alarm in monitor, this will trigger recording until cancelled with -c\n" );
	fprintf( stderr, "  -c, --cancel                   : Cancel a forced alarm in monitor, required after being enabled with -a\n" );
	fprintf( stderr, "  -h, --help - This screen\n" );

	exit( 0 );
}

int main( int argc, char *argv[] )
{
	static struct option long_options[] = {
		{"monitor", 1, 0, 'm'},
		{"image", 2, 0, 'i'},
		{"timestamp", 2, 0, 't'},
		{"state", 0, 0, 's'},
		{"read_index", 0, 0, 'r'},
		{"write_index", 0, 0, 'w'},
		{"event", 0, 0, 'e'},
		{"fps", 0, 0, 'f'},
		{"zones", 0, 0, 'z'},
		{"alarm", 0, 0, 'a'},
		{"cancel", 0, 0, 'c'},
		{"help", 0, 0, 'h'},
		{0, 0, 0, 0}
	};

	int id = 1;
	typedef enum {
		BOGUS=0x0000,
		STATE=0x0001,
		IMAGE=0x0002,
		TIME=0x0004,
		READ_IDX=0x0008,
		WRITE_IDX=0x0010,
		EVENT=0x0020,
		FPS=0x0040,
		ZONES=0x0080,
		ALARM=0x0100,
		CANCEL=0x0200
	} Function;
	Function function = BOGUS;

	int image_idx = -1;
	while (1)
	{
		int this_option_optind = optind ? optind : 1;
		int option_index = 0;
		int opterr = 1;

		int c = getopt_long (argc, argv, "m:srwie::t::fzach", long_options, &option_index);
		if (c == -1)
		{
			break;
		}

		switch (c)
		{
			case 'm':
				id = atoi(optarg);
				break;
			case 's':
				function = Function(function | STATE);
				break;
			case 'i':
				function = Function(function | IMAGE);
				if ( optarg )
				{
					image_idx = atoi( optarg );
				}
				break;
			case 't':
				function = Function(function | TIME);
				if ( optarg )
				{
					image_idx = atoi( optarg );
				}
				break;
			case 'r':
				function = Function(function | READ_IDX);
				break;
			case 'w':
				function = Function(function | WRITE_IDX);
				break;
			case 'e':
				function = Function(function | EVENT);
				break;
			case 'f':
				function = Function(function | FPS);
				break;
			case 'z':
				function = Function(function | ZONES);
				break;
			case 'a':
				function = Function(function | ALARM);
				break;
			case 'c':
				function = Function(function | CANCEL);
				break;
			case 'h':
				Usage();
				break;
			case '?':
				Usage();
				break;
			default:
				//fprintf( stderr, "?? getopt returned character code 0%o ??\n", c );
				break;
		}
	}

	if (optind < argc)
	{
		fprintf( stderr, "Extraneous options, " );
		while (optind < argc)
			printf ("%s ", argv[optind++]);
		printf ("\n");
		Usage();
	}

	//printf( "Monitor %d, Function %d\n", id, function );

	dbg_name = "zmu";
	dbg_level = -1;

	DbgInit();

	if ( !mysql_init( &dbconn ) )
	{
		fprintf( stderr, "Can't initialise structure: %s\n", mysql_error( &dbconn ) );
		exit( mysql_errno( &dbconn ) );
	}
	if ( !mysql_connect( &dbconn, ZM_DB_SERVER, ZM_DB_USERB, ZM_DB_PASSB ) )
	{
		fprintf( stderr, "Can't connect to server: %s\n", mysql_error( &dbconn ) );
		exit( mysql_errno( &dbconn ) );
	}
	if ( mysql_select_db( &dbconn, ZM_DB_NAME ) )
	{
		fprintf( stderr, "Can't select database: %s\n", mysql_error( &dbconn ) );
		exit( mysql_errno( &dbconn ) );
	}

	Monitor *monitor = Monitor::Load( id );

	if ( monitor )
	{
		char separator = ' ';
		bool have_output = false;
		if ( function & STATE )
		{
			if ( have_output ) printf( "%c", separator );
			printf( "%d", monitor->GetState() );
			have_output = true;
		}
		if ( function & TIME )
		{
			if ( have_output ) printf( "%c", separator );
			printf( "%d", monitor->GetTimestamp( image_idx ) );
			have_output = true;
		}
		if ( function & READ_IDX )
		{
			if ( have_output ) printf( "%c", separator );
			printf( "%d", monitor->GetLastReadIndex() );
			have_output = true;
		}
		if ( function & WRITE_IDX )
		{
			if ( have_output ) printf( "%c", separator );
			printf( "%d", monitor->GetLastWriteIndex() );
			have_output = true;
		}
		if ( function & EVENT )
		{
			if ( have_output ) printf( "%c", separator );
			printf( "%d", monitor->GetLastEvent() );
			have_output = true;
		}
		if ( function & FPS )
		{
			if ( have_output ) printf( "%c", separator );
			printf( "%.2f", monitor->GetFPS() );
			have_output = true;
		}
		if ( function & IMAGE )
		{
			monitor->GetImage( image_idx );
		}
		if ( function & ZONES )
		{
			monitor->ReloadZones();
		}
		if ( function & ALARM )
		{
			monitor->ForceAlarm();
		}
		if ( function & CANCEL )
		{
			monitor->CancelAlarm();
		}
		if ( have_output )
		{
			printf( "\n" );
		}
		if ( !function )
		{
			Usage();
		}
	}
	return( 0 );
}
