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
#include "zm_db.h"
#include "zm_monitor.h"
#include "zm_local_camera.h"

void Usage( int status=-1 )
{
	fprintf( stderr, "zmu <-d device_no> [-v] [function] [-U<username> -P<password>]\n" );
	fprintf( stderr, "zmu <-m monitor_id> [-v] [function] [-U<username> -P<password>]\n" );
	fprintf( stderr, "General options:\n" );
	fprintf( stderr, "  -h, --help                     : This screen\n" );
	fprintf( stderr, "  -v, --verbose                  : Produce more verbose output\n" );
	fprintf( stderr, "Options for use with devices:\n" );
	fprintf( stderr, "  -d, --device <device_no>       : Get the current video device settings for /dev/video<device_no>\n" );
	fprintf( stderr, "  -q, --query                    : Query the current settings for the device\n" );
	fprintf( stderr, "Options for use with monitors:\n" );
	fprintf( stderr, "  -m, --monitor <monitor_id>     : Specify which monitor to address, default 1 if absent\n" );
	fprintf( stderr, "  -q, --query                    : Query the current settings for the monitor\n" );
	fprintf( stderr, "  -s, --state                    : Output the current monitor state, 0 = idle, 1 = alarm, 2 = alert\n" );
	fprintf( stderr, "  -B, --brightness [value]       : Output the current brightness, set to value if given \n" );
	fprintf( stderr, "  -C, --contrast [value]         : Output the current contrast, set to value if given \n" );
	fprintf( stderr, "  -H, --hue [value]              : Output the current hue, set to value if given \n" );
	fprintf( stderr, "  -O, --colour [value]           : Output the current colour, set to value if given \n" );
	fprintf( stderr, "  -i, --image [image_index]      : Write captured image to disk as <monitor_name>.jpg, last image captured\n" );
	fprintf( stderr, "                                   or specified ring buffer index if given.\n" );
	fprintf( stderr, "  -S, --scale <scale_%%ge>        : With --image specify any scaling (in %%) to be applied to the image\n" );
	fprintf( stderr, "  -t, --timestamp [image_index]  : Output captured image timestamp, last image captured or specified\n" );
	fprintf( stderr, "                                   ring buffer index if given\n" );
	fprintf( stderr, "  -r, --read_index               : Output ring buffer read index\n" );
	fprintf( stderr, "  -w, --write_index              : Output ring buffer write index\n" );
	fprintf( stderr, "  -e, --event                    : Output last event index\n" );
	fprintf( stderr, "  -f, --fps                      : Output last Frames Per Second captured reading\n" );
	fprintf( stderr, "  -z, --zones                    : Write last captured image overlaid with zones to <monitor_name>-Zones.jpg\n" );
	fprintf( stderr, "  -a, --alarm                    : Force alarm in monitor, this will trigger recording until cancelled with -c\n" );
	fprintf( stderr, "  -n, --noalarm                  : Force no alarms in monitor, this will prevent alarms until cancelled with -c\n" );
	fprintf( stderr, "  -c, --cancel                   : Cancel a forced alarm/noalarm in monitor, required after being enabled with -a or -n\n" );
	fprintf( stderr, "  -U, --username <username>      : When running in authenticated mode the username and\n" );
	fprintf( stderr, "  -P, --password <password>      : and password combination of the given user\n" );

	exit( status );
}

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
	NOALARM=0x0200,
	CANCEL=0x0400,
	QUERY=0x0800,
	BRIGHTNESS=0x1000,
	CONTRAST=0x2000,
	HUE=0x4000,
	COLOUR=0x8000
} Function;

bool ValidateAccess( const char *username, const char *password, int mon_id, Function function )
{
	if ( mon_id > 0 && (bool)config.Item( ZM_OPT_USE_AUTH ) )
	{
		if ( !username || !password )
		{
			fprintf( stderr, "Error, username and password must be supplied\n" );
			exit( -1 );
		}

		char sql[BUFSIZ] = "";
		snprintf( sql, sizeof(sql), "select Username, Stream+0, Events+0, Monitors+0, System+0, MonitorIds from Users where Username = '%s' and Password = password('%s') and Enabled = 1", username, password );

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
		int n_users = mysql_num_rows( result );

		if ( n_users < 1 )
		{
			fprintf( stderr, "Error, invalid username and/or password\n" );
			exit( -1 );
		}

		MYSQL_ROW dbrow = mysql_fetch_row( result );

		bool allowed = true;
		int stream = atoi(dbrow[1]);
		int events = atoi(dbrow[2]);
		int monitors = atoi(dbrow[3]);
		//int system = atoi(dbrow[4]);
		const char *monitor_ids = dbrow[5];
		if ( function & (STATE|IMAGE|TIME|READ_IDX|WRITE_IDX|FPS) )
		{
			if ( stream < 1 )
				allowed = false;
		}
		if ( function & EVENT )
		{
			if ( events < 1 )
				allowed = false;
		}
		if ( function & (ZONES|QUERY) )
		{
			if ( monitors < 1 )
				allowed = false;
		}
		if ( function & (ALARM|NOALARM|CANCEL|BRIGHTNESS|CONTRAST|HUE|COLOUR) )
		{
			if ( monitors < 2 )
				allowed = false;
		}
		if ( monitor_ids && monitor_ids[0] )
		{
			char mon_id_str[256] = "";
			strncpy( mon_id_str, monitor_ids, sizeof(mon_id_str) );
			char *mon_id_str_ptr = mon_id_str;
			char *mon_id_ptr = 0;
			bool found_mon_id = false;
			while( (mon_id_ptr = strtok( mon_id_str_ptr, "," )) )
			{
				mon_id_str_ptr = 0;
				if ( mon_id == atoi( mon_id_ptr ) )
				{
					found_mon_id = true;
					break;
				}
			}
			if ( !found_mon_id )
				allowed = false;
		}
		if ( !allowed )
		{
			fprintf( stderr, "Error, insufficient privileges for requested action\n" );
			exit( -1 );
		}
	}
	return( true );
}

int main( int argc, char *argv[] )
{
	static struct option long_options[] = {
		{"device", 1, 0, 'd'},
		{"monitor", 1, 0, 'm'},
		{"verbose", 0, 0, 'v'},
		{"image", 2, 0, 'i'},
		{"scale", 1, 0, 'S'},
		{"timestamp", 2, 0, 't'},
		{"state", 0, 0, 's'},
		{"brightness", 2, 0, 'B'},
		{"contrast", 2, 0, 'C'},
		{"hue", 2, 0, 'H'},
		{"contrast", 2, 0, 'O'},
		{"read_index", 0, 0, 'r'},
		{"write_index", 0, 0, 'w'},
		{"event", 0, 0, 'e'},
		{"fps", 0, 0, 'f'},
		{"zones", 0, 0, 'z'},
		{"alarm", 0, 0, 'a'},
		{"noalarm", 0, 0, 'n'},
		{"cancel", 0, 0, 'c'},
		{"query", 0, 0, 'q'},
		{"username", 1, 0, 'U'},
		{"password", 1, 0, 'P'},
		{"help", 0, 0, 'h'},
		{0, 0, 0, 0}
	};

	int dev_id = -1;
	int mon_id = 0;
	bool verbose = false;
	Function function = BOGUS;

	int image_idx = -1;
	int scale = -1;
	int brightness = -1;
	int contrast = -1;
	int hue = -1;
	int colour = -1;
	char *username = 0;
	char *password = 0;
	while (1)
	{
		int option_index = 0;

		int c = getopt_long (argc, argv, "d:m:vsrwei::S:t::fzancqphB::C::H::O::U:P:", long_options, &option_index);
		if (c == -1)
		{
			break;
		}

		switch (c)
		{
			case 'd':
				dev_id = atoi(optarg);
				break;
			case 'm':
				mon_id = atoi(optarg);
				break;
			case 'v':
				verbose = true;
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
			case 'S':
				scale = atoi(optarg);
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
			case 'n':
				function = Function(function | NOALARM);
				break;
			case 'c':
				function = Function(function | CANCEL);
				break;
			case 'q':
				function = Function(function | QUERY);
				break;
			case 'B':
				function = Function(function | BRIGHTNESS);
				if ( optarg )
				{
					brightness = atoi( optarg );
				}
				break;
			case 'C':
				function = Function(function | CONTRAST);
				if ( optarg )
				{
					contrast = atoi( optarg );
				}
				break;
			case 'H':
				function = Function(function | HUE);
				if ( optarg )
				{
					hue = atoi( optarg );
				}
				break;
			case 'O':
				function = Function(function | COLOUR);
				if ( optarg )
				{
					colour = atoi( optarg );
				}
				break;
			case 'U':
				username = optarg;
				break;
			case 'P':
				password = optarg;
				break;
			case 'h':
				Usage( 0 );
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
			fprintf( stderr, "%s ", argv[optind++]);
		fprintf( stderr, "\n");
		Usage();
	}

	if ( dev_id >= 0 && !(function&QUERY) )
	{
		fprintf( stderr, "Error, -d option cannot be used with this option\n" );
		Usage();
	}
	if ( scale != -1 && !(function&IMAGE) )
	{
		fprintf( stderr, "Error, -S option cannot be used with this option\n" );
		Usage();
	}
	//printf( "Monitor %d, Function %d\n", mon_id, function );

	zm_dbg_name = "zmu";
	zm_dbg_level = -1;

	zmDbgInit();

	zmDbConnect( ZM_DB_USERB, ZM_DB_PASSB );

	ValidateAccess( username, password, mon_id, function );

	if ( dev_id >= 0 )
	{
		if ( function & QUERY )
		{
			char vid_string[BUFSIZ] = "";
			bool ok = LocalCamera::GetCurrentSettings( dev_id, vid_string, verbose );
			printf( "%s", vid_string );
			exit( ok?0:-1 );
		}
	}
	else
	{
		Monitor *monitor = Monitor::Load( mon_id, function&(QUERY|ZONES) );

		if ( monitor )
		{
			if ( verbose )
			{
				printf( "Monitor %d(%s)\n", monitor->Id(), monitor->Name() );
			}
			char separator = ' ';
			bool have_output = false;
			if ( function & STATE )
			{
				Monitor::State state = monitor->GetState();
				if ( verbose )
					printf( "Current state: %s\n", state==Monitor::ALARM?"Alarm":(state==Monitor::ALERT?"Alert":"Idle") );
				else
				{
					if ( have_output ) printf( "%c", separator );
					printf( "%d", state );
					have_output = true;
				}
			}
			if ( function & TIME )
			{
				struct timeval timestamp = monitor->GetTimestamp( image_idx );
				if ( verbose )
				{
					char timestamp_str[64] = "None";
					if ( timestamp.tv_sec )
						strftime( timestamp_str, sizeof(timestamp_str), "%Y-%m-%d %H:%M:%S", localtime( &timestamp.tv_sec ) );
					if ( image_idx == -1 )
						printf( "Time of last image capture: %s.%02ld\n", timestamp_str, timestamp.tv_usec/10000 );
					else
						printf( "Time of image %d capture: %s.%02ld\n", image_idx, timestamp_str, timestamp.tv_usec/10000 );
				}
				else
				{
					if ( have_output ) printf( "%c", separator );
					printf( "%ld.%02ld", timestamp.tv_sec, timestamp.tv_usec/10000 );
					have_output = true;
				}
			}
			if ( function & READ_IDX )
			{
				if ( verbose )
					printf( "Last read index: %d\n", monitor->GetLastReadIndex() );
				else
				{
					if ( have_output ) printf( "%c", separator );
					printf( "%d", monitor->GetLastReadIndex() );
					have_output = true;
				}
			}
			if ( function & WRITE_IDX )
			{
				if ( verbose )
					printf( "Last write index: %d\n", monitor->GetLastWriteIndex() );
				else
				{
					if ( have_output ) printf( "%c", separator );
					printf( "%d", monitor->GetLastWriteIndex() );
					have_output = true;
				}
			}
			if ( function & EVENT )
			{
				if ( verbose )
					printf( "Last event id: %d\n", monitor->GetLastEvent() );
				else
				{
					if ( have_output ) printf( "%c", separator );
					printf( "%d", monitor->GetLastEvent() );
					have_output = true;
				}
			}
			if ( function & FPS )
			{
				if ( verbose )
					printf( "Current capture rate: %.2f frames per second\n", monitor->GetFPS() );
				else
				{
					if ( have_output ) printf( "%c", separator );
					printf( "%.2f", monitor->GetFPS() );
					have_output = true;
				}
			}
			if ( function & IMAGE )
			{
				if ( verbose )
				{
					if ( image_idx == -1 )
						printf( "Dumping last image captured to %s.jpg", monitor->Name() );
					else
						printf( "Dumping buffer image %d to %s.jpg", image_idx, monitor->Name() );
					if ( scale != -1 )
						printf( ", scaling by %d%%", scale );
					printf( "\n" );
				}
				monitor->GetImage( image_idx, scale>0?scale:100 );
			}
			if ( function & ZONES )
			{
				if ( verbose )
					printf( "Dumping zone image to %s-Zones.jpg\n", monitor->Name() );
				monitor->DumpZoneImage();
			}
			if ( function & ALARM )
			{
				if ( verbose )
					printf( "Forcing alarm on\n" );
				monitor->ForceAlarmOn();
			}
			if ( function & NOALARM )
			{
				if ( verbose )
					printf( "Forcing alarm off\n" );
				monitor->ForceAlarmOff();
			}
			if ( function & CANCEL )
			{
				if ( verbose )
					printf( "Cancelling forced alarm on/off\n" );
				monitor->CancelForced();
			}
			if ( function & QUERY )
			{
				char mon_string[1024] = "";
				monitor->DumpSettings( mon_string, verbose );
				printf( "%s\n", mon_string );
			}
			if ( function & BRIGHTNESS )
			{
				if ( verbose )
				{
					if ( brightness >= 0 )
						printf( "New brightness: %d\n", monitor->Brightness( brightness ) );
					else
						printf( "Current brightness: %d\n", monitor->Brightness() );
				}
				else
				{
					if ( have_output ) printf( "%c", separator );
					if ( brightness >= 0 )
						printf( "%d", monitor->Brightness( brightness ) );
					else
						printf( "%d", monitor->Brightness() );
					have_output = true;
				}
			}
			if ( function & CONTRAST )
			{
				if ( verbose )
				{
					if ( contrast >= 0 )
						printf( "New brightness: %d\n", monitor->Contrast( contrast ) );
					else
						printf( "Current contrast: %d\n", monitor->Contrast() );
				}
				else
				{
					if ( have_output ) printf( "%c", separator );
					if ( contrast >= 0 )
						printf( "%d", monitor->Contrast( contrast ) );
					else
						printf( "%d", monitor->Contrast() );
					have_output = true;
				}
			}
			if ( function & HUE )
			{
				if ( verbose )
				{
					if ( hue >= 0 )
						printf( "New hue: %d\n", monitor->Hue( hue ) );
					else
						printf( "Current hue: %d\n", monitor->Hue() );
				}
				else
				{
					if ( have_output ) printf( "%c", separator );
					if ( hue >= 0 )
						printf( "%d", monitor->Hue( hue ) );
					else
						printf( "%d", monitor->Hue() );
					have_output = true;
				}
			}
			if ( function & COLOUR )
			{
				if ( verbose )
				{
					if ( colour >= 0 )
						printf( "New colour: %d\n", monitor->Colour( colour ) );
					else
						printf( "Current colour: %d\n", monitor->Colour() );
				}
				else
				{
					if ( have_output ) printf( "%c", separator );
					if ( colour >= 0 )
						printf( "%d", monitor->Colour( colour ) );
					else
						printf( "%d", monitor->Colour() );
					have_output = true;
				}
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
		else
		{
			fprintf( stderr, "Error, invalid monitor id %d\n", mon_id );
			exit( -1 );
		}
	}
	return( 0 );
}
