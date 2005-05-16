//
// ZoneMinder Capture Daemon, $Date$, $Revision$
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
#include <values.h>

#include "zm.h"
#include "zm_db.h"
#include "zm_monitor.h"

bool zmc_terminate = false;

void zmc_term_handler( int /* signal */ )
{
	Info(( "Got TERM signal, exiting" ));
	zmc_terminate = true;
}

void Usage()
{
	fprintf( stderr, "zmc -d <device_id> or -m <monitor_id>\n" );

	fprintf( stderr, "Options:\n" );
	fprintf( stderr, "  -d, --device <device_id>      : For local cameras, device to access 0=>/dev/video0 etc\n" );
	fprintf( stderr, "  -H <host> -P <port> -p <path> : For remote cameras\n" );
	fprintf( stderr, "  -m, --monitor <monitor_id>    : For sources associated with a single monitor\n" );
	fprintf( stderr, "  -h, --help                    : This screen\n" );
	exit( 0 );
}

int main( int argc, char *argv[] )
{
	int device = -1;
	const char *host = "";
	const char *port = "";
	const char *path = "";
	int monitor_id = -1;

	static struct option long_options[] = {
		{"device", 1, 0, 'd'},
		{"host", 1, 0, 'H'},
		{"port", 1, 0, 'P'},
	 	{"path", 1, 0, 'p'},
		{"monitor", 1, 0, 'm'},
		{"help", 0, 0, 'h'},
		{0, 0, 0, 0}
	};

	while (1)
	{
		int option_index = 0;

		int c = getopt_long (argc, argv, "d:H:P:p:m:h", long_options, &option_index);
		if (c == -1)
		{
			break;
		}

		switch (c)
		{
			case 'd':
				device = atoi(optarg);
				break;
			case 'H':
				host = optarg;
				break;
			case 'P':
				port = optarg;
				break;
			case 'p':
				path = optarg;
				break;
			case 'm':
				monitor_id = atoi(optarg);
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

	if (( device >= 0 && host[0] )
	|| ( device >= 0 && monitor_id > 0 )
	|| ( monitor_id > 0 && host[0] ))
	{
		fprintf( stderr, "Only one of device or host/port/path or monitor id allowed\n" );
		Usage();
		exit( 0 );
	}

	if ( device < 0 && !host[0] && monitor_id <= 0 )
	{
		fprintf( stderr, "One of device or host/port/path or monitor id must be specified\n" );
		Usage();
		exit( 0 );
	}

	char dbg_id_string[16];
	if ( device >= 0 )
	{
		snprintf( dbg_id_string, sizeof(dbg_id_string), "d%d", device );
	}
	else if ( host[0] )
	{
		snprintf( dbg_id_string, sizeof(dbg_id_string), "h%s", host );
	}
	else
	{
		snprintf( dbg_id_string, sizeof(dbg_id_string), "m%d", monitor_id );
	}

	zmDbgInit( "zmc", dbg_id_string, 0 );

	zmLoadConfig();

	Monitor **monitors = 0;
	int n_monitors = 0;
	if ( device >= 0 )
	{
		n_monitors = Monitor::Load( device, monitors, Monitor::CAPTURE );
	}
	else if ( host[0] )
	{
		if ( !port )
			port = "80";
		n_monitors = Monitor::Load( host, port, path, monitors, Monitor::CAPTURE );
	}
	else
	{
		Monitor *monitor = Monitor::Load( monitor_id, true, Monitor::CAPTURE );
		if ( monitor )
		{
			monitors = new Monitor *[1];
			monitors[0] = monitor;
			n_monitors = 1;
		}
	}

	if ( !n_monitors )
	{
		Error(( "No monitors found" ));
		exit ( -1 );
	}

	Info(( "Starting Capture" ));
	sigset_t block_set;
	sigemptyset( &block_set );
	struct sigaction action, old_action;

	action.sa_handler = zmc_term_handler;
	action.sa_mask = block_set;
	action.sa_flags = 0;
	sigaction( SIGTERM, &action, &old_action );

	sigaddset( &block_set, SIGUSR1 );
	sigaddset( &block_set, SIGUSR2 );
	if ( device >= 0 && n_monitors == 1 )
	{
		monitors[0]->PreCapture();
	}

	long *capture_delays = new long[n_monitors];
	long *next_delays = new long[n_monitors];
	struct timeval * last_capture_times = new struct timeval[n_monitors];
	for ( int i = 0; i < n_monitors; i++ )
	{
		last_capture_times[i].tv_sec = last_capture_times[i].tv_usec = 0;
		capture_delays[i] = monitors[i]->GetCaptureDelay();
	}

	struct timeval now;
	struct DeltaTimeval delta_time;
	while( !zmc_terminate )
	{
		/* grab a new one */
		sigprocmask( SIG_BLOCK, &block_set, 0 );
		for ( int i = 0; i < n_monitors; i++ )
		{
			long min_delay = MAXINT;
			if ( config.no_max_fps_on_alarm && (monitors[i]->GetState() == Monitor::ALARM) )
			{
				next_delays[i] = 0;
			}
			else
			{
				gettimeofday( &now, &dummy_tz );
				for ( int j = 0; j < n_monitors; j++ )
				{
					if ( last_capture_times[j].tv_sec )
					{
						DELTA_TIMEVAL( delta_time, now, last_capture_times[j], DT_PREC_3 );
						next_delays[j] = capture_delays[j]-delta_time.delta;
						if ( next_delays[j] < 0 )
						{
							next_delays[j] = 0;
						}
					}
					else
					{
						next_delays[j] = 0;
					}
					if ( next_delays[j] <= min_delay )
					{
						min_delay = next_delays[j];
					}
				}
			}
			if ( next_delays[i] <= min_delay || next_delays[i] <= 0 )
			{
				monitors[i]->PreCapture();
				monitors[i]->PostCapture();

				if ( next_delays[i] > 0 )
				{
					gettimeofday( &now, &dummy_tz );
					DELTA_TIMEVAL( delta_time, now, last_capture_times[i], DT_PREC_3 );
					long sleep_time = next_delays[i]-delta_time.delta;
					if ( sleep_time > 0 )
					{
						usleep( sleep_time*(DT_MAXGRAN/DT_PREC_3) );
					}
				}
				gettimeofday( &(last_capture_times[i]), &dummy_tz );
			}
		}
		sigprocmask( SIG_UNBLOCK, &block_set, 0 );
	}
	for ( int i = 0; i < n_monitors; i++ )
	{
		delete monitors[i];
	}
	delete [] capture_delays;
	delete [] next_delays;
	delete [] last_capture_times;

	return( 0 );
}
