#include "zm.h"

bool reload = false;

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

void main( int argc, const char *argv[] )
{
	int device = argv[1]?atoi( argv[1] ):0;

	char dbg_name_string[16];
	sprintf( dbg_name_string, "zmc-%d", device );
	dbg_name = dbg_name_string;

	DbgInit();

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
	int n_monitors = Monitor::Load( device, monitors );

	Info(( "Starting Capture" ));
	sigset_t block_set;
	sigemptyset( &block_set );
	struct sigaction action, old_action;
	action.sa_handler = hup_handler;
	action.sa_mask = block_set;
	action.sa_flags = 0;
	sigaction( SIGHUP, &action, &old_action );
	action.sa_handler = term_handler;
	action.sa_mask = block_set;
	action.sa_flags = 0;
	sigaction( SIGTERM, &action, &old_action );
	sigaddset( &block_set, SIGHUP );
	sigaddset( &block_set, SIGTERM );
	if ( n_monitors == 1 )
	{
		monitors[0]->PreCapture();
	}
	while( 1 )
	{
		/* grab a new one */
		sigprocmask( SIG_BLOCK, &block_set, 0 );
		for ( int i = 0; i < n_monitors; i++ )
		{
			monitors[i]->PreCapture();
			monitors[i]->PostCapture();
		}
		sigprocmask( SIG_UNBLOCK, &block_set, 0 );
		if ( reload )
		{
			for ( int i = 0; i < n_monitors; i++ )
			{
				delete monitors[i];
			}
			monitors = 0;
			n_monitors = Monitor::Load( device, monitors );
			reload = false;
		}
	}
}
