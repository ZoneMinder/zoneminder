#include <getopt.h>
#include "zm.h"

void main( int argc, char *argv[] )
{
	static struct option long_options[] = {
		{"monitor", 1, 0, 'm'},
		{"image", 2, 0, 'i'},
		{"timestamp", 2, 0, 't'},
		{"state", 0, 0, 's'},
		{"read_index", 0, 0, 'r'},
		{"write_index", 0, 0, 'w'},
		{"fps", 0, 0, 'f'},
		{"zones", 0, 0, 'z'},
		{0, 0, 0, 0}
	};

	int id = 1;
	enum { STATE, IMAGE, TIME, READ_IDX, WRITE_IDX, FPS, ZONES } function = STATE;
	int image_idx = -1;
	while (1)
	{
		int this_option_optind = optind ? optind : 1;
		int option_index = 0;

		int c = getopt_long (argc, argv, "m:srwi::t::fz", long_options, &option_index);
		if (c == -1)
			break;

		switch (c)
		{
			case 'm':
				id = atoi(optarg);
				break;
			case 's':
				function = STATE;
				break;
			case 'i':
				function = IMAGE;
				if ( optarg )
				{
					image_idx = atoi( optarg );
				}
				break;
			case 't':
				function = TIME;
				if ( optarg )
				{
					image_idx = atoi( optarg );
				}
				break;
			case 'r':
				function = READ_IDX;
				break;
			case 'w':
				function = WRITE_IDX;
				break;
			case 'f':
				function = FPS;
				break;
			case 'z':
				function = ZONES;
				break;
			case '?':
				//fprintf( stderr, "What?\n" );
				break;
			default:
				//fprintf( stderr, "?? getopt returned character code 0%o ??\n", c );
				break;
		}
	}

#if 0
	if (optind < argc)
	{
		printf ("non-option ARGV-elements: ");
		while (optind < argc)
			printf ("%s ", argv[optind++]);
		printf ("\n");
	}

#endif
	//printf( "Monitor %d, Function %d, ImageIdx %d\n", id, function, image_idx );

	dbg_name = "zmu";

	DbgInit();

	if ( !mysql_init( &dbconn ) )
	{
		fprintf( stderr, "Can't initialise structure: %s\n", mysql_error( &dbconn ) );
		exit( mysql_errno( &dbconn ) );
	}
	if ( !mysql_connect( &dbconn, "", ZM_DB_USERB, ZM_DB_PASSB ) )
	{
		fprintf( stderr, "Can't connect to server: %s\n", mysql_error( &dbconn ) );
		exit( mysql_errno( &dbconn ) );
	}
	if ( mysql_select_db( &dbconn, ZM_DATABASE ) )
	{
		fprintf( stderr, "Can't select database: %s\n", mysql_error( &dbconn ) );
		exit( mysql_errno( &dbconn ) );
	}

	Monitor *monitor = Monitor::Load( id );

	if ( monitor )
	{
		if ( function == STATE )
		{
			printf( "%d\n", monitor->GetState() );
		}
		else if ( function == IMAGE )
		{
			monitor->GetImage( image_idx );
		}
		else if ( function == TIME )
		{
			printf( "%d\n", monitor->GetTimestamp( image_idx ) );
		}
		else if ( function == READ_IDX )
		{
			printf( "%d\n", monitor->GetLastReadIndex() );
		}
		else if ( function == WRITE_IDX )
		{
			printf( "%d\n", monitor->GetLastWriteIndex() );
		}
		else if ( function == FPS )
		{
			printf( "%.2f\n", monitor->GetFPS() );
		}
		else if ( function == ZONES )
		{
			monitor->ReloadZones();
		}
	}
}
