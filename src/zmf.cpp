//
// ZoneMinder Image File Writer Implementation, $Date$, $Revision$
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

#include <stdio.h>
#include <stdlib.h>
#include <unistd.h>
#include <time.h>
#include <sys/time.h>
#include <errno.h>
#include <unistd.h>
#include <sys/types.h>
#include <sys/time.h>
#include <sys/socket.h>
#include <sys/un.h>
#include <getopt.h>
#include <signal.h>

#include "zm.h"
#include "zm_db.h"
//#include "zm_debug.h"
#include "zm_monitor.h"

#include "zmf.h"

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
	exit( 0 );
}

int OpenSocket( int monitor_id )
{
	int sd = socket( AF_UNIX, SOCK_STREAM, 0);
	if ( sd < 0 )
	{
		Error(( "Can't create socket: %s", strerror(errno) ));
		return( -1 );
	}

	char sock_path[PATH_MAX] = "";
	sprintf( sock_path, "%s/zmf-%d.sock", (const char *)config.Item( ZM_PATH_SOCKS ), monitor_id );
	if ( unlink( sock_path ) < 0 )
	{
		Warning(( "Can't unlink '%s': %s", sock_path, strerror(errno) ));
	}

	struct sockaddr_un addr;

	strcpy( addr.sun_path, sock_path );
	addr.sun_family = AF_UNIX;

	if ( bind( sd, (struct sockaddr *)&addr, strlen(addr.sun_path)+sizeof(addr.sun_family)) < 0 )
	{
		Error(( "Can't bind: %s", strerror(errno) ));
		exit( -1 );
	}

	if ( listen( sd, SOMAXCONN ) < 0 )
	{
		Error(( "Can't listen: %s", strerror(errno) ));
		return( -1 );
	}

	struct sockaddr_un rem_addr;
	socklen_t rem_addr_len = sizeof(rem_addr);
	int new_sd = -1;
	if ( (new_sd = accept( sd, (struct sockaddr *)&rem_addr, &rem_addr_len )) < 0 )
	{
		Error(( "Can't accept: %s", strerror(errno) ));
		exit( -1 );
	}
	close( sd );

	sd = new_sd;

	Info(( "Frame server socket open, awaiting images" ));
	return( sd );
}

int ReopenSocket( int &sd, int monitor_id )
{
	close( sd );
	return( sd = OpenSocket( monitor_id ) );
}

void Usage()
{
	fprintf( stderr, "zmf -m <monitor_id>\n" );
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

	char dbg_name_string[16];
	sprintf( dbg_name_string, "zmf-m%d", id );
	zm_dbg_name = dbg_name_string;
	//sprintf( zm_dbg_log, "/tmp/zmf-%d.log", id );
	//zm_dbg_level = 1;

	zmDbgInit();

	zmDbConnect( ZM_DB_USERB, ZM_DB_PASSB );

	Monitor *monitor = Monitor::Load( id, false );

	if ( !monitor )
	{
		fprintf( stderr, "Can't find monitor with id of %d\n", id );
		exit( -1 );
	}

	sigset_t block_set;
	sigemptyset( &block_set );
	struct sigaction action, old_action;

	action.sa_handler = zm_term_handler;
	action.sa_mask = block_set;
	action.sa_flags = 0;
	sigaction( SIGTERM, &action, &old_action );

	action.sa_handler = zm_die_handler;
	action.sa_mask = block_set;
	action.sa_flags = 0;
	sigaction( SIGBUS, &action, &old_action );
	sigaction( SIGSEGV, &action, &old_action );

	int sd = OpenSocket( monitor->Id() );

	FrameHeader frame_header = { 0, 0, false, 0 };
	//unsigned char *image_data = 0;

	fd_set rfds;

	struct timeval timeout;
	timeout.tv_sec = 1;
	timeout.tv_usec = 0;
	while( 1 )
	{
		struct timeval temp_timeout = timeout;

		FD_ZERO(&rfds);
		FD_SET(sd, &rfds);
		int n_found = select( sd+1, &rfds, NULL, NULL, &temp_timeout );
		if( n_found == 0 )
		{
			Debug( 1, ( "Select timed out" ));
			continue;
		}
		else if ( n_found < 0)
		{
			Error(( "Select error: %s", strerror(errno) ));
			ReopenSocket( sd, monitor->Id() );
			continue;
		}

		sigprocmask( SIG_BLOCK, &block_set, 0 );

		int n_bytes = read( sd, &frame_header, sizeof(frame_header) );
		if ( n_bytes != sizeof(frame_header) )
		{
			if ( n_bytes < 0 )
			{
				Error(( "Can't read frame header: %s", strerror(errno) ));
			}
			else if ( n_bytes > 0 )
			{
				Error(( "Incomplete read of frame header, %d bytes only", n_bytes ));
			}
			else
			{
				Warning(( "Socket closed at remote end", n_bytes ));
			}
			ReopenSocket( sd, monitor->Id() );
			continue;
		}
		Debug( 1, ( "Read frame header, expecting %ld bytes of image", frame_header.image_length ));
		static unsigned char image_data[ZM_MAX_IMAGE_SIZE];
		n_bytes = read( sd, image_data, frame_header.image_length );
		if ( n_bytes != (ssize_t)frame_header.image_length )
		{
			if ( n_bytes < 0 )
			{
				Error(( "Can't read frame image data: %s", strerror(errno) ));
			}
			else if ( n_bytes > 0 )
			{
				Error(( "Incomplete read of frame image data, %d bytes only", n_bytes ));
			}
			else
			{
				Warning(( "Socket closed at remote end", n_bytes ));
			}
			ReopenSocket( sd, monitor->Id() );
			continue;
		}
		static char path[PATH_MAX] = "";
		sprintf( path, "%s/%s/%ld/%03ld-%s.jpg", (const char *)config.Item( ZM_DIR_EVENTS ), monitor->Name(), frame_header.event_id, frame_header.frame_id, frame_header.alarm_frame?"analyse":"capture" );
		Debug( 1, ( "Got image, writing to %s", path ));

		FILE *fd = 0;
		if ( (fd = fopen( path, "w" )) < 0 )
		{
			Error(( "Can't fopen '%s': %s", path, strerror(errno) ));
			exit( -1 );
		}
		if ( 0 == fwrite( image_data, frame_header.image_length, 1, fd ) )
		{
			Error(( "Can't fwrite image data: %s", strerror(errno) ));
			exit( -1 );
		}
		fclose( fd );

		sigprocmask( SIG_UNBLOCK, &block_set, 0 );
	}
}
