//
// ZoneMinder Analysis Daemon, $Date$, $Revision$
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

#include <getopt.h>
#include <signal.h>

#include "zm.h"
#include "zm_db.h"
#include "zm_monitor.h"

void zm_die_handler( int signal )
{
#if HAVE_DECL_STRSIGNAL
	char * error = strsignal(signal);
	size_t errorStringSize = strlen(error) + strlen("Got signal (), crashing.");
	char * errorString =(char *) malloc(errorStringSize + 1);  // plus 1 for termination char.
	(void) snprintf(errorString, errorStringSize, "Got signal (%s), crashing.", error);

	Error(( (const char *)errorString ));
	free(errorString);
#else /* HAVE_DECL_STRSIGNAL */
	Error(( "Got signal %d, crashing", signal ));
#endif /* HAVE_DECL_STRSIGNAL */
	exit( signal );
}

bool zma_terminate = false;

void zm_term_handler( int signal )
{
#if HAVE_DECL_STRSIGNAL
	char * error = strsignal(signal);
	size_t errorStringSize = strlen(error) + strlen("Got signal (), exiting.");
	char * errorString =(char *) malloc(errorStringSize + 1);  // plus 1 for termination char.
	(void) snprintf(errorString, errorStringSize, "Got signal (%s), exiting.", error);

	Info(( (const char *)errorString ));
	free(errorString);
#else /* HAVE_DECL_STRSIGNAL */
	Info(( "Got TERM signal, exiting" ));
#endif /* HAVE_DECL_STRSIGNAL */
	zma_terminate = true;
}

bool zma_reload = false;

void  zm_hup_handler( int signal )
{
#if HAVE_DECL_STRSIGNAL
	char * error = strsignal(signal);
	size_t errorStringSize = strlen(error) + strlen("Got signal (), reloading.");
	char * errorString =(char *) malloc(errorStringSize + 1);  // plus 1 for termination char.
	(void) snprintf(errorString, errorStringSize, "Got signal (%s), reloading.", error);

	Info(( (const char *)errorString ));
	free(errorString);
#else /* HAVE_DECL_STRSIGNAL */
	Info(( "Got HUP signal, reloading" ));
#endif /* HAVE_DECL_STRSIGNAL */
	zma_reload = true;
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

	char dbg_id_string[16];
	snprintf( dbg_id_string, sizeof(dbg_id_string), "m%d", id );

	zmDbgInit( "zma", dbg_id_string, 0 );

	zmLoadConfig();

	Monitor *monitor = Monitor::Load( id, true, Monitor::ANALYSIS );

	if ( monitor )
	{
		Info(( "In mode %d/%d, warming up", monitor->GetFunction(), monitor->Enabled() ));

		if ( config.opt_frame_server )
		{
			Event::OpenFrameSocket( monitor->Id() );
		}

		sigset_t block_set;
		sigemptyset( &block_set );
		struct sigaction action, old_action;

		action.sa_handler = zm_hup_handler;
		action.sa_mask = block_set;
		action.sa_flags = 0;
		sigaction( SIGHUP, &action, &old_action );

		action.sa_handler = zm_term_handler;
		action.sa_mask = block_set;
		action.sa_flags = 0;
		sigaction( SIGTERM, &action, &old_action );

		action.sa_handler = zm_die_handler;
		action.sa_mask = block_set;
		action.sa_flags = 0;
		sigaction( SIGBUS, &action, &old_action );
		sigaction( SIGSEGV, &action, &old_action );

		while( !zma_terminate )
		{
			// Process the next image
			sigprocmask( SIG_BLOCK, &block_set, 0 );
			if ( !monitor->Analyse() )
			{
				usleep( monitor->Active()?ZM_SAMPLE_RATE:ZM_SUSPENDED_RATE );
			}
			if ( zma_reload )
			{
				monitor->Reload();
				zma_reload = false;
			}
			sigprocmask( SIG_UNBLOCK, &block_set, 0 );
		}
		delete monitor;
	}
	else
	{
		fprintf( stderr, "Can't find monitor with id of %d\n", id );
	}
	return( 0 );
}
