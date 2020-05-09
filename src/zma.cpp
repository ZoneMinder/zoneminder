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
// Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
//

/*

=head1 NAME

zma - The ZoneMinder Analysis daemon

=head1 SYNOPSIS

 zma -m <monitor_id>
 zma --monitor <monitor_id>
 zma -h
 zma --help
 zma -v
 zma --version

=head1 DESCRIPTION

This is the component that goes through the captured frames and checks them
for motion which might generate an alarm or event. It generally keeps up with
the Capture daemon but if very busy may skip some frames to prevent it falling
behind.

=head1 OPTIONS

 -m, --monitor_id         - ID of the monitor to analyse
 -h, --help             - Display usage information
 -v, --version          - Print the installed version of ZoneMinder

=cut

*/

#include <getopt.h>
#include <signal.h>

#include "zm.h"
#include "zm_db.h"
#include "zm_signal.h"
#include "zm_monitor.h"
#include "zm_fifo.h"

void Usage() {
  fprintf(stderr, "zma -m <monitor_id>\n");
  fprintf(stderr, "Options:\n");
  fprintf(stderr, "  -m, --monitor <monitor_id>   : Specify which monitor to use\n");
  fprintf(stderr, "  -h, --help           : This screen\n");
  fprintf(stderr, "  -v, --version        : Report the installed version of ZoneMinder\n");
  exit(0);
}

int main( int argc, char *argv[] ) {
  self = argv[0];

  srand(getpid() * time(0));

  int id = -1;

  static struct option long_options[] = {
    {"monitor", 1, 0, 'm'},
    {"help", 0, 0, 'h'},
    {"version", 0, 0, 'v'},
    {0, 0, 0, 0}
  };

  while (1) {
    int option_index = 0;

    int c = getopt_long(argc, argv, "m:h:v", long_options, &option_index);
    if ( c == -1 ) {
      break;
    }

    switch (c) {
      case 'm':
        id = atoi(optarg);
        break;
      case 'h':
      case '?':
        Usage();
        break;
      case 'v':
        std::cout << ZM_VERSION << "\n";
        exit(0);
      default:
        //fprintf( stderr, "?? getopt returned character code 0%o ??\n", c );
        break;
    }
  }

  if (optind < argc) {
    fprintf(stderr, "Extraneous options, ");
    while (optind < argc)
      printf("%s ", argv[optind++]);
    printf("\n");
    Usage();
  }

  if ( id < 0 ) {
    fprintf(stderr, "Bogus monitor %d\n", id);
    Usage();
    exit(0);
  }

  char log_id_string[16];
  snprintf(log_id_string, sizeof(log_id_string), "zma_m%d", id);

  zmLoadConfig();

  logInit(log_id_string);

  hwcaps_detect();

  Monitor *monitor = Monitor::Load(id, true, Monitor::ANALYSIS);
  zmFifoDbgInit( monitor );  

  if ( monitor ) {
    Info("In mode %d/%d, warming up", monitor->GetFunction(), monitor->Enabled());

    zmSetDefaultHupHandler();
    zmSetDefaultTermHandler();
    zmSetDefaultDieHandler();

    sigset_t block_set;
    sigemptyset(&block_set);

    useconds_t analysis_rate = monitor->GetAnalysisRate();
    unsigned int analysis_update_delay = monitor->GetAnalysisUpdateDelay();
    time_t last_analysis_update_time, cur_time;
    monitor->UpdateAdaptiveSkip();
    last_analysis_update_time = time(0);

    while( (!zm_terminate) && monitor->ShmValid() ) {
      // Process the next image
      sigprocmask(SIG_BLOCK, &block_set, 0);

      // Some periodic updates are required for variable capturing framerate
      if ( analysis_update_delay ) {
        cur_time = time(0);
        if ( (unsigned int)( cur_time - last_analysis_update_time ) > analysis_update_delay ) {
          analysis_rate = monitor->GetAnalysisRate();
          monitor->UpdateAdaptiveSkip();
          last_analysis_update_time = cur_time;
        }
      }

      if ( !monitor->Analyse() ) {
        usleep(monitor->Active()?ZM_SAMPLE_RATE:ZM_SUSPENDED_RATE);
      } else if ( analysis_rate ) {
        usleep(analysis_rate);
      }

      if ( zm_reload ) {
        monitor->Reload();
        logTerm();
        logInit(log_id_string);
        zm_reload = false;
      }
      sigprocmask(SIG_UNBLOCK, &block_set, 0);
    } // end while ! zm_terminate
    delete monitor;
  } else {
    fprintf(stderr, "Can't find monitor with id of %d\n", id);
  }
  Image::Deinitialise();
  logTerm();
  zmDbClose();
  return 0;
}
