//
// ZoneMinder Capture Daemon, $Date$, $Revision$
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
// Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
//

/*

=head1 NAME

zmc - The ZoneMinder Capture daemon

=head1 SYNOPSIS

 zmc -d <device_path>
 zmc --device <device_path>
 zmc -f <file_path>
 zmc --file <file_path>
 zmc -m <monitor_id>
 zmc --monitor <monitor_id>
 zmc -h
 zmc --help
 zmc -v
 zmc --version

=head1 DESCRIPTION

This binary's job is to sit on a video device and suck frames off it as fast as
possible, this should run at more or less constant speed.

=head1 OPTIONS

 -d, --device <device_path>         - For local cameras, device to access. e.g /dev/video0 etc
 -f, --file <file_path>           - For local images, jpg file to access.
 -m, --monitor_id             - ID of the monitor to analyse
 -h, --help                 - Display usage information
 -v, --version              - Print the installed version of ZoneMinder

=cut

*/

#include <getopt.h>
#include <signal.h>
#if defined(__FreeBSD__)
#include <limits.h>
#else
#include <values.h>
#endif

#if !defined(MAXINT)
#define MAXINT INT_MAX
#endif

#include "zm.h"
#include "zm_db.h"
#include "zm_time.h"
#include "zm_signal.h"
#include "zm_monitor.h"

void Usage() {
  fprintf(stderr, "zmc -d <device_path> or -r <proto> -H <host> -P <port> -p <path> or -f <file_path> or -m <monitor_id>\n");

  fprintf(stderr, "Options:\n");
#if defined(BSD)
  fprintf(stderr, "  -d, --device <device_path>         : For local cameras, device to access. E.g /dev/bktr0 etc\n");
#else
  fprintf(stderr, "  -d, --device <device_path>         : For local cameras, device to access. E.g /dev/video0 etc\n");
#endif
  fprintf(stderr, "  -f, --file <file_path>           : For local images, jpg file to access.\n");
  fprintf(stderr, "  -m, --monitor <monitor_id>         : For sources associated with a single monitor\n");
  fprintf(stderr, "  -h, --help                 : This screen\n");
  fprintf(stderr, "  -v, --version              : Report the installed version of ZoneMinder\n");
  exit(0);
}

int main(int argc, char *argv[]) {
  self = argv[0];

  srand(getpid() * time(0));

  const char *device = "";
  const char *protocol = "";
  const char *host = "";
  const char *port = "";
  const char *path = "";
  const char *file = "";
  int monitor_id = -1;

  static struct option long_options[] = {
    {"device", 1, 0, 'd'},
    {"protocol", 1, 0, 'r'},
    {"host", 1, 0, 'H'},
    {"port", 1, 0, 'P'},
    {"path", 1, 0, 'p'},
    {"file", 1, 0, 'f'},
    {"monitor", 1, 0, 'm'},
    {"help", 0, 0, 'h'},
    {"version", 0, 0, 'v'},
    {0, 0, 0, 0}
  };

  while (1) {
    int option_index = 0;

    int c = getopt_long(argc, argv, "d:H:P:p:f:m:h:v", long_options, &option_index);
    if ( c == -1 ) {
      break;
    }

    switch (c) {
      case 'd':
        device = optarg;
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
      case 'f':
        file = optarg;
        break;
      case 'm':
        monitor_id = atoi(optarg);
        break;
      case 'h':
      case '?':
        Usage();
        break;
      case 'v':
        std::cout << ZM_VERSION << "\n";
        exit(0);
      default:
        // fprintf(stderr, "?? getopt returned character code 0%o ??\n", c);
        break;
    }
  }

  if ( optind < argc ) {
    fprintf(stderr, "Extraneous options, ");
    while ( optind < argc )
      printf("%s ", argv[optind++]);
    printf("\n");
    Usage();
  }

  int modes = ( (device[0]?1:0) + (host[0]?1:0) + (file[0]?1:0) + (monitor_id > 0 ? 1 : 0) );
  if ( modes > 1 ) {
    fprintf(stderr, "Only one of device, host/port/path, file or monitor id allowed\n");
    Usage();
    exit(0);
  }

  if ( modes < 1 ) {
    fprintf(stderr, "One of device, host/port/path, file or monitor id must be specified\n");
    Usage();
    exit(0);
  }

  char log_id_string[32] = "";
  if ( device[0] ) {
    const char *slash_ptr = strrchr(device, '/');
    snprintf(log_id_string, sizeof(log_id_string), "zmc_d%s", slash_ptr?slash_ptr+1:device);
  } else if ( host[0] ) {
    snprintf(log_id_string, sizeof(log_id_string), "zmc_h%s", host);
  } else if ( file[0] ) {
    const char *slash_ptr = strrchr(file, '/');
    snprintf(log_id_string, sizeof(log_id_string), "zmc_f%s", slash_ptr?slash_ptr+1:file);
  } else {
    snprintf(log_id_string, sizeof(log_id_string), "zmc_m%d", monitor_id);
  }

  logInit(log_id_string);
  zmLoadConfig();
  logInit(log_id_string);

  hwcaps_detect();

  Monitor **monitors = 0;
  int n_monitors = 0;
#if ZM_HAS_V4L
  if ( device[0] ) {
    n_monitors = Monitor::LoadLocalMonitors(device, monitors, Monitor::CAPTURE);
  } else
#endif  // ZM_HAS_V4L
  if ( host[0] ) {
    if ( !port )
      port = "80";
    n_monitors = Monitor::LoadRemoteMonitors(protocol, host, port, path, monitors, Monitor::CAPTURE);
  } else if ( file[0] ) {
    n_monitors = Monitor::LoadFileMonitors(file, monitors, Monitor::CAPTURE);
  } else {
    Monitor *monitor = Monitor::Load(monitor_id, true, Monitor::CAPTURE);
    if ( monitor ) {
      monitors = new Monitor *[1];
      monitors[0] = monitor;
      n_monitors = 1;
    }
  }

  if ( !n_monitors ) {
    Error("No monitors found");
    exit(-1);
  }

  Info("Starting Capture version %s", ZM_VERSION);
  zmSetDefaultHupHandler();
  zmSetDefaultTermHandler();
  zmSetDefaultDieHandler();

  sigset_t block_set;
  sigemptyset(&block_set);

  sigaddset(&block_set, SIGHUP);
  sigaddset(&block_set, SIGUSR1);
  sigaddset(&block_set, SIGUSR2);

  int result = 0;

  int prime_capture_log_count = 0;

  while ( !zm_terminate ) {
    result = 0;
    static char sql[ZM_SQL_SML_BUFSIZ];
    for ( int i = 0; i < n_monitors; i++ ) {
      time_t now = (time_t)time(NULL);
      monitors[i]->setStartupTime(now);

      snprintf(sql, sizeof(sql),
          "REPLACE INTO Monitor_Status (MonitorId, Status) VALUES ('%d','Running')",
          monitors[i]->Id());
      if ( mysql_query(&dbconn, sql) ) {
        Error("Can't run query: %s", mysql_error(&dbconn));
      }
    }  // end foreach monitor

    // Outer primary loop, handles connection to camera
    if ( monitors[0]->PrimeCapture() < 0 ) {
      if ( prime_capture_log_count % 60 ) {
        Error("Failed to prime capture of initial monitor");
      } else {
        Debug(1, "Failed to prime capture of initial monitor");
      }
      prime_capture_log_count ++;
      sleep(10);
      continue;
    }

    int *capture_delays = new int[n_monitors];
    int *alarm_capture_delays = new int[n_monitors];
    int *next_delays = new int[n_monitors];
    struct timeval * last_capture_times = new struct timeval[n_monitors];
    for ( int i = 0; i < n_monitors; i++ ) {
      last_capture_times[i].tv_sec = last_capture_times[i].tv_usec = 0;
      capture_delays[i] = monitors[i]->GetCaptureDelay();
      alarm_capture_delays[i] = monitors[i]->GetAlarmCaptureDelay();
      snprintf(sql, sizeof(sql),
          "REPLACE INTO Monitor_Status (MonitorId, Status) VALUES ('%d','Connected')",
          monitors[i]->Id());
      if ( mysql_query(&dbconn, sql) ) {
        Error("Can't run query: %s", mysql_error(&dbconn));
      }
    } // end foreach monitor

    struct timeval now;
    struct DeltaTimeval delta_time;
    while ( !zm_terminate ) {
      //sigprocmask(SIG_BLOCK, &block_set, 0);
      for ( int i = 0; i < n_monitors; i++ ) {
        long min_delay = MAXINT;

        gettimeofday(&now, NULL);
        for ( int j = 0; j < n_monitors; j++ ) {
          if ( last_capture_times[j].tv_sec ) {
            DELTA_TIMEVAL(delta_time, now, last_capture_times[j], DT_PREC_3);
            if ( monitors[i]->GetState() == Monitor::ALARM )
              next_delays[j] = alarm_capture_delays[j]-delta_time.delta;
            else
              next_delays[j] = capture_delays[j]-delta_time.delta;
            if ( next_delays[j] < 0 )
              next_delays[j] = 0;
          } else {
            next_delays[j] = 0;
          }
          if ( next_delays[j] <= min_delay ) {
            min_delay = next_delays[j];
          }
        }  // end foreach monitor

        if ( next_delays[i] <= min_delay || next_delays[i] <= 0 ) {
          if ( monitors[i]->PreCapture() < 0 ) {
            Error("Failed to pre-capture monitor %d %s (%d/%d)",
                monitors[i]->Id(), monitors[i]->Name(), i+1, n_monitors);
            monitors[i]->Close();
            result = -1;
            break;
          }
          if ( monitors[i]->Capture() < 0 ) {
            Info("Failed to capture image from monitor %d %s (%d/%d)",
                monitors[i]->Id(), monitors[i]->Name(), i+1, n_monitors);
            monitors[i]->Close();
            result = -1;
            break;
          }
          if ( monitors[i]->PostCapture() < 0 ) {
            Error("Failed to post-capture monitor %d %s (%d/%d)",
                monitors[i]->Id(), monitors[i]->Name(), i+1, n_monitors);
            monitors[i]->Close();
            result = -1;
            break;
          }

          if ( next_delays[i] > 0 ) {
            gettimeofday(&now, NULL);
            DELTA_TIMEVAL(delta_time, now, last_capture_times[i], DT_PREC_3);
            long sleep_time = next_delays[i]-delta_time.delta;
            if ( sleep_time > 0 ) {
              usleep(sleep_time*(DT_MAXGRAN/DT_PREC_3));
            }
          }
          gettimeofday(&(last_capture_times[i]), NULL);
        }  // end if next_delay <= min_delay || next_delays[i] <= 0 )

      }  // end foreach n_monitors
      //sigprocmask(SIG_UNBLOCK, &block_set, 0);
      if ( zm_reload ) {
        for ( int i = 0; i < n_monitors; i++ ) {
          monitors[i]->Reload();
        }
        logTerm();
        logInit(log_id_string);
        zm_reload = false;
      }
      if ( result < 0 ) {
        // Failure, try reconnecting
				sleep(5);
        break;
      }
    }  // end while ! zm_terminate
    delete [] alarm_capture_delays;
    delete [] capture_delays;
    delete [] next_delays;
    delete [] last_capture_times;
  } // end while ! zm_terminate outer connection loop

  for ( int i = 0; i < n_monitors; i++ ) {
    static char sql[ZM_SQL_SML_BUFSIZ];
    snprintf(sql, sizeof(sql),
        "REPLACE INTO Monitor_Status (MonitorId, Status) VALUES ('%d','NotRunning')",
        monitors[i]->Id());
    if ( mysql_query(&dbconn, sql) ) {
      Error("Can't run query: %s", mysql_error(&dbconn));
    }
    delete monitors[i];
  }
  delete [] monitors;

  Image::Deinitialise();
  logTerm();
  zmDbClose();

	return zm_terminate ? 0 : result;
}
