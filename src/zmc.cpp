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

#include "zm.h"
#include "zm_camera.h"
#include "zm_db.h"
#include "zm_define.h"
#include "zm_fifo.h"
#include "zm_monitor.h"
#include "zm_rtsp_server_thread.h"
#include "zm_signal.h"
#include "zm_time.h"
#include "zm_utils.h"
#include <getopt.h>
#include <iostream>
#include <unistd.h>

void Usage() {
  fprintf(stderr, "zmc -d <device_path> or -r <proto> -H <host> -P <port> -p <path> or -f <file_path> or -m <monitor_id>\n");

  fprintf(stderr, "Options:\n");
#if defined(BSD)
  fprintf(stderr, "  -d, --device <device_path> : For local cameras, device to access. E.g /dev/bktr0 etc\n");
#else
  fprintf(stderr, "  -d, --device <device_path> : For local cameras, device to access. E.g /dev/video0 etc\n");
#endif
  fprintf(stderr, "  -f, --file <file_path>     : For local images, jpg file to access.\n");
  fprintf(stderr, "  -m, --monitor <monitor_id> : For sources associated with a single monitor\n");
  fprintf(stderr, "  -h, --help                 : This screen\n");
  fprintf(stderr, "  -v, --version              : Report the installed version of ZoneMinder\n");
  exit(0);
}

int main(int argc, char *argv[]) {
  self = argv[0];

  srand(getpid() * time(nullptr));

  const char *device = "";
  const char *protocol = "";
  const char *host = "";
  const char *port = "";
  const char *path = "";
  const char *file = "";
  int monitor_id = -1;

  static struct option long_options[] = {
    {"device", 1, nullptr, 'd'},
    {"protocol", 1, nullptr, 'r'},
    {"host", 1, nullptr, 'H'},
    {"port", 1, nullptr, 'P'},
    {"path", 1, nullptr, 'p'},
    {"file", 1, nullptr, 'f'},
    {"monitor", 1, nullptr, 'm'},
    {"help", 0, nullptr, 'h'},
    {"version", 0, nullptr, 'v'},
    {nullptr, 0, nullptr, 0}
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
  zmLoadStaticConfig();
  zmDbConnect();
  zmLoadDBConfig();
  logInit(log_id_string);

  HwCapsDetect();

  std::vector<std::shared_ptr<Monitor>> monitors;
#if ZM_HAS_V4L
  if ( device[0] ) {
    monitors = Monitor::LoadLocalMonitors(device, Monitor::CAPTURE);
  } else
#endif  // ZM_HAS_V4L
  if ( host[0] ) {
    if ( !port )
      port = "80";
    monitors = Monitor::LoadRemoteMonitors(protocol, host, port, path, Monitor::CAPTURE);
  } else if ( file[0] ) {
    monitors = Monitor::LoadFileMonitors(file, Monitor::CAPTURE);
  } else {
    std::shared_ptr<Monitor> monitor = Monitor::Load(monitor_id, true, Monitor::CAPTURE);
    if ( monitor ) {
      monitors.push_back(monitor);
    }
  }

  if (monitors.empty()) {
    Error("No monitors found");
    exit(-1);
  } else {
	  Debug(2, "%zu monitors loaded", monitors.size());
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

  while (!zm_terminate) {
    result = 0;
    static char sql[ZM_SQL_SML_BUFSIZ];

    for (const std::shared_ptr<Monitor> &monitor : monitors) {
      monitor->LoadCamera();

      if (!monitor->connect()) {
        Warning("Couldn't connect to monitor %d", monitor->Id());
      }
      time_t now = (time_t)time(nullptr);
      monitor->setStartupTime(now);
      monitor->setHeartbeatTime(now);

      snprintf(sql, sizeof(sql), 
          "INSERT INTO Monitor_Status (MonitorId,Status,CaptureFPS,AnalysisFPS)"
          " VALUES (%u, 'Running',0,0) ON DUPLICATE KEY UPDATE Status='Running',CaptureFPS=0,AnalysisFPS=0,CaptureBandwidth=0",
          monitor->Id());
      zmDbDo(sql);

      int sleep_time = 0;
      while (monitor->PrimeCapture() <= 0) {
        if (prime_capture_log_count % 60) {
          logPrintf(Logger::ERROR+monitor->Importance(),
              "Failed to prime capture of initial monitor");
        } else {
          Debug(1, "Failed to prime capture of initial monitor");
        }
        prime_capture_log_count ++;
        if (zm_terminate) break;
        if (sleep_time < 60) sleep_time++;
        sleep(sleep_time);
      }
      if (zm_terminate) break;

      snprintf(sql, sizeof(sql),
          "INSERT INTO Monitor_Status (MonitorId,Status) VALUES (%u, 'Connected') ON DUPLICATE KEY UPDATE Status='Connected'",
               monitor->Id());
      zmDbDo(sql);
    }  // end foreach monitor
    if (zm_terminate) break;

    int *capture_delays = new int[monitors.size()];
    int *alarm_capture_delays = new int[monitors.size()];
    struct timeval * last_capture_times = new struct timeval[monitors.size()];

    for (size_t i = 0; i < monitors.size(); i++) {
      last_capture_times[i].tv_sec = last_capture_times[i].tv_usec = 0;
      capture_delays[i] = monitors[i]->GetCaptureDelay();
      alarm_capture_delays[i] = monitors[i]->GetAlarmCaptureDelay();
      Debug(2, "capture delay(%u mSecs 1000/capture_fps) alarm delay(%u)",
          capture_delays[i], alarm_capture_delays[i]);
    }

    struct timeval now;
    struct DeltaTimeval delta_time;
    int sleep_time = 0;

    while (!zm_terminate) {
      //sigprocmask(SIG_BLOCK, &block_set, 0);
      for (size_t i = 0; i < monitors.size(); i++) {
        monitors[i]->CheckAction();

        if (monitors[i]->PreCapture() < 0) {
          Error("Failed to pre-capture monitor %d %s (%zu/%zu)",
                monitors[i]->Id(), monitors[i]->Name(), i + 1, monitors.size());
          result = -1;
          break;
        }
        if (monitors[i]->Capture() < 0) {
          Error("Failed to capture image from monitor %d %s (%zu/%zu)",
                monitors[i]->Id(), monitors[i]->Name(), i + 1, monitors.size());
          result = -1;
          break;
        }
        if (monitors[i]->PostCapture() < 0) {
          Error("Failed to post-capture monitor %d %s (%zu/%zu)",
                monitors[i]->Id(), monitors[i]->Name(), i + 1, monitors.size());
          result = -1;
          break;
        }
        monitors[i]->UpdateFPS();

        // capture_delay is the amount of time we should sleep in useconds to achieve the desired framerate.
        int delay = (monitors[i]->GetState() == Monitor::ALARM) ? alarm_capture_delays[i] : capture_delays[i];
        if (delay) {
          gettimeofday(&now, nullptr);
          if (last_capture_times[i].tv_sec) {
            // DT_PREC_3 means that the value will be in thousands of a second
            DELTA_TIMEVAL(delta_time, now, last_capture_times[i], DT_PREC_6);

            // You have to add back in the previous sleep time
            sleep_time = delay - (delta_time.delta - sleep_time);
            Debug(4,
                  "Sleep time is %d from now: %" PRIi64 ".%" PRIi64" last: %" PRIi64 ".% " PRIi64 " delta %lu delay: %d",
                  sleep_time,
                  static_cast<int64>(now.tv_sec),
                  static_cast<int64>(now.tv_usec),
                  static_cast<int64>(last_capture_times[i].tv_sec),
                  static_cast<int64>(last_capture_times[i].tv_usec),
                  delta_time.delta,
                  delay);

            if (sleep_time > 0) {
              Debug(4, "usleeping (%d)", sleep_time);
              usleep(sleep_time);
            }
          }  // end if has a last_capture time
          last_capture_times[i] = now;
        }  // end if delay
      }  // end foreach n_monitors

      if ((result < 0) or zm_reload) {
        // Failure, try reconnecting
        break;
      }
    }  // end while ! zm_terminate and connected

    for (size_t i = 0; i < monitors.size(); i++) {
      monitors[i]->Close();
      monitors[i]->disconnect();
    }

    delete [] alarm_capture_delays;
    delete [] capture_delays;
    delete [] last_capture_times;

    if (zm_reload) {
      for (std::shared_ptr<Monitor> &monitor : monitors) {
        monitor->Reload();
      }
      logTerm();
      logInit(log_id_string);
      
      zm_reload = false;
    }  // end if zm_reload
  }  // end while ! zm_terminate outer connection loop

  for (std::shared_ptr<Monitor> &monitor : monitors) {
    static char sql[ZM_SQL_SML_BUFSIZ];
    snprintf(sql, sizeof(sql),
        "INSERT INTO Monitor_Status (MonitorId,Status) VALUES (%u, 'NotRunning') ON DUPLICATE KEY UPDATE Status='NotRunning',CaptureFPS=0,AnalysisFPS=0,CaptureBandwidth=0", 
        monitor->Id());
    zmDbDo(sql);
  }
  monitors.clear();

  Image::Deinitialise();
  Debug(1, "terminating");
  dbQueue.stop();
  logTerm();
  zmDbClose();

	return zm_terminate ? 0 : result;
}
