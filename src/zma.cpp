//
// ZoneMinder Analysis Daemon, $Date$, $Revision$
// Copyright (C) 2002  Philip Coombes
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

bool reload = false;

void die_handler( int signal )
{
	Info(( "Got signal %d, crashing", signal ));
	exit( signal );
}

void hup_handler( int signal )
{
	Info(( "Got HUP signal, reloading" ));
	reload = true;
}

void term_handler( int signal )
{
	Info(( "Got TERM signal, exiting" ));
	exit( 0 );
}

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
	int id = -1;

	static struct option long_options[] = {
		{"monitor", 1, 0, 'm'},
		{"help", 0, 0, 'h'},
		{0, 0, 0, 0}
	};

	while (1)
	{
		int this_option_optind = optind ? optind : 1;
		int option_index = 0;
		int opterr = 1;

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

	char dbg_name_string[16];
	sprintf( dbg_name_string, "zma-m%d", id );
	dbg_name = dbg_name_string;

	DbgInit();

	if ( !mysql_init( &dbconn ) )
	{
		fprintf( stderr, "Can't initialise structure: %s\n", mysql_error( &dbconn ) );
		exit( mysql_errno( &dbconn ) );
	}
	if ( !mysql_connect( &dbconn, ZM_DB_SERVER, ZM_DB_USERA, ZM_DB_PASSA ) )
	{
		fprintf( stderr, "Can't connect to server: %s\n", mysql_error( &dbconn ) );
		exit( mysql_errno( &dbconn ) );
	}
	if ( mysql_select_db( &dbconn, ZM_DB_NAME ) )
	{
		fprintf( stderr, "Can't select database: %s\n", mysql_error( &dbconn ) );
		exit( mysql_errno( &dbconn ) );
	}

	Monitor *monitor = Monitor::Load( id, true );

	if ( monitor )
	{
		Info(( "Warming up" ));

		sigset_t block_set;
		sigemptyset( &block_set );
		struct sigaction action, old_action;

		action.sa_handler = term_handler;
		action.sa_mask = block_set;
		action.sa_flags = 0;
		sigaction( SIGTERM, &action, &old_action );

		action.sa_handler = die_handler;
		action.sa_mask = block_set;
		action.sa_flags = 0;
		sigaction( SIGBUS, &action, &old_action );
		sigaction( SIGSEGV, &action, &old_action );

		while( 1 )
		{
			// Process the next image
			bool result = false;
			sigprocmask( SIG_BLOCK, &block_set, 0 );
			if ( !monitor->Analyse() )
			{
				usleep( 10000 );
			}
			sigprocmask( SIG_UNBLOCK, &block_set, 0 );
		}
	}
	else
	{
		fprintf( stderr, "Can't find monitor with id of %d\n", id );
	}
	return( 0 );
}
