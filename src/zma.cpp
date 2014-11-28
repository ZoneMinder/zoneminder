//
// ZoneMinder Analysis Daemon, $Date$, $Revision$
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

#include <getopt.h>
#include <signal.h>

#include "zm.h"
#include "zm_db.h"
#include "zm_signal.h"
#include "zm_monitor.h"

void Usage()
{
	fprintf( stderr, "zma -m <monitor_id>\n" );
	fprintf( stderr, "Options:\n" );
	fprintf( stderr, "  -m, --monitor <monitor_id>   : Specify which monitor to use\n" );
	fprintf( stderr, "  -h, --help                   : This screen\n" );
	exit( 0 );
}

int main( int argc, char *argv[] )
{
	self = argv[0];

	srand( getpid() * time( 0 ) );

	int id = -1;

	static struct option long_options[] = {
		{"monitor", 1, 0, 'm'},
		{"help", 0, 0, 'h'},
		{0, 0, 0, 0}
	};

	while (1)
	{
		int option_index = 0;

		int c = getopt_long (argc, argv, "m:h", long_options, &option_index);
		if (c == -1)
		{
			break;
		}

		switch (c)
		{
			case 'm':
				id = atoi(optarg);
				break;
			case 'h':
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

	if ( id < 0 )
	{
		fprintf( stderr, "Bogus monitor %d\n", id );
		Usage();
		exit( 0 );
	}

	char log_id_string[16];
	snprintf( log_id_string, sizeof(log_id_string), "zma_m%d", id );

	zmLoadConfig();

	logInit( log_id_string );
	
	ssedetect();

	Monitor *monitor = Monitor::Load( id, true, Monitor::ANALYSIS );

	if ( monitor )
	{
		Info( "In mode %d/%d, warming up", monitor->GetFunction(), monitor->Enabled() );

		if ( config.opt_frame_server )
		{
			Event::OpenFrameSocket( monitor->Id() );
		}

		zmSetDefaultHupHandler();
		zmSetDefaultTermHandler();
		zmSetDefaultDieHandler();

		sigset_t block_set;
		sigemptyset( &block_set );

		while( !zm_terminate )
		{
			// Process the next image
			sigprocmask( SIG_BLOCK, &block_set, 0 );
			if ( !monitor->Analyse() )
			{
				usleep( monitor->Active()?ZM_SAMPLE_RATE:ZM_SUSPENDED_RATE );
			}
			if ( zm_reload )
			{
				monitor->Reload();
				zm_reload = false;
			}
			sigprocmask( SIG_UNBLOCK, &block_set, 0 );
		}
		delete monitor;
	}
	else
	{
		fprintf( stderr, "Can't find monitor with id of %d\n", id );
	}
	logTerm();
	zmDbClose();
	return( 0 );
}
