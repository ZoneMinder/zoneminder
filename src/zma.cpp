#include "zm.h"

bool reload = false;

void hup_handler( int signal )
{
	reload = true;
}

void main( int argc, const char *argv[] )
{
	int device = argv[1]?atoi( argv[1] ):0;

	char dbg_name_string[16];
	sprintf( dbg_name_string, "zma-%d", device );
	dbg_name = dbg_name_string;

	DbgInit();

	//umask( 0 );

	if ( !mysql_init( &dbconn ) )
	{
		fprintf( stderr, "Can't initialise structure: %s\n", mysql_error( &dbconn ) );
		exit( mysql_errno( &dbconn ) );
	}
	if ( !mysql_connect( &dbconn, "", ZM_DB_USERA, ZM_DB_PASSA ) )
	{
		fprintf( stderr, "Can't connect to server: %s\n", mysql_error( &dbconn ) );
		exit( mysql_errno( &dbconn ) );
	}
	if ( mysql_select_db( &dbconn, ZM_DATABASE ) )
	{
		fprintf( stderr, "Can't select database: %s\n", mysql_error( &dbconn ) );
		exit( mysql_errno( &dbconn ) );
	}

	Monitor **monitors = 0;
	int n_monitors = Monitor::Load( device, monitors, false );

	Info(( "Warming up" ));
	sigset_t block_set;
	sigemptyset( &block_set );
	struct sigaction action, old_action;
	action.sa_handler = hup_handler;
	action.sa_mask = block_set;
	action.sa_flags = 0;
	sigaction( SIGHUP, &action, &old_action );
	sigaddset( &block_set, SIGHUP );
	while( 1 )
	{
		// Process the next image
		sigprocmask( SIG_BLOCK, &block_set, 0 );
		for ( int i = 0; i < n_monitors; i++ )
		{
			monitors[i]->Analyse();
		}
		sigprocmask( SIG_UNBLOCK, &block_set, 0 );
		if ( reload )
		{
			for ( int i = 0; i < n_monitors; i++ )
			{
				monitors[i]->ReloadZones();
				monitors[i]->CheckFunction();
			}
			reload = false;
		}
	}
}
