//
// Zone Monitor Analysis Daemon, $Date$, $Revision$
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

int main( int argc, const char *argv[] )
{
	int device = argv[1]?atoi( argv[1] ):0;

	char dbg_name_string[16];
	sprintf( dbg_name_string, "zma-%d", device );
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
	int n_monitors = Monitor::Load( device, monitors, false );

	Info(( "Warming up" ));
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
	//sigaddset( &block_set, SIGTERM );
	while( 1 )
	{
		// Process the next image
		bool result = false;
		sigprocmask( SIG_BLOCK, &block_set, 0 );
		for ( int i = 0; i < n_monitors; i++ )
		{
			if ( monitors[i]->Analyse() )
			{
				result = true;
			}
		}
		if ( !result )
		{
			usleep( 10000 );
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
	return( 0 );
}
