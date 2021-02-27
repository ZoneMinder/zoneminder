//
// ZoneMinder RTSP Daemon
// Copyright (C) 2021 Isaac Connor
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

zm_rtsp_server - The ZoneMinder Server

=head1 SYNOPSIS

 zmc -m <monitor_id>
 zmc --monitor <monitor_id>
 zmc -h
 zmc --help
 zmc -v
 zmc --version

=head1 DESCRIPTION

This binary's job is to connect to fifo's provided by local zmc processes
and provide that stream over rtsp

=head1 OPTIONS

 -m, --monitor_id           - ID of a monitor to stream
 -h, --help                 - Display usage information
 -v, --version              - Print the installed version of ZoneMinder

=cut

*/

#include "zm.h"
#include "zm_db.h"
#include "zm_define.h"
#include "zm_monitor.h"
#include "zm_rtsp_server_thread.h"
#include "zm_rtsp_server_fifo_video_source.h"
#include "zm_signal.h"
#include "zm_time.h"
#include "zm_utils.h"
#include <getopt.h>
#include <iostream>

void Usage() {
  fprintf(stderr, "zm_rtsp_server -m <monitor_id>\n");

  fprintf(stderr, "Options:\n");
  fprintf(stderr, "  -m, --monitor <monitor_id> : We default to all monitors use this to specify just one\n");
  fprintf(stderr, "  -h, --help                 : This screen\n");
  fprintf(stderr, "  -v, --version              : Report the installed version of ZoneMinder\n");
  exit(0);
}

int main(int argc, char *argv[]) {
  self = argv[0];

  srand(getpid() * time(nullptr));

  int monitor_id = -1;

  static struct option long_options[] = {
    {"monitor", 1, nullptr, 'm'},
    {"help", 0, nullptr, 'h'},
    {"version", 0, nullptr, 'v'},
    {nullptr, 0, nullptr, 0}
  };

  while (1) {
    int option_index = 0;

    int c = getopt_long(argc, argv, "m:h:v", long_options, &option_index);
    if ( c == -1 ) {
      break;
    }

    switch (c) {
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

  if (optind < argc) {
    fprintf(stderr, "Extraneous options, ");
    while (optind < argc)
      printf("%s ", argv[optind++]);
    printf("\n");
    Usage();
  }

  const char *log_id_string = "zm_rtsp_server";
  ///std::string log_id_string = std::string("zm_rtsp_server");
  ///if ( monitor_id > 0 ) log_id_string += stringtf("_m%d", monitor_id);

  logInit(log_id_string);
  zmLoadStaticConfig();
  zmDbConnect();
  zmLoadDBConfig();
  logInit(log_id_string);

  hwcaps_detect();

  std::string where = "`Function` != 'None' AND `RTSPServer` != false";
  if (staticConfig.SERVER_ID)
    where += stringtf(" AND `ServerId`=%d", staticConfig.SERVER_ID);
  if (monitor_id > 0)
    where += stringtf(" AND `Id`=%d", monitor_id);

  std::vector<std::shared_ptr<Monitor>> monitors = Monitor::LoadMonitors(where, Monitor::QUERY);

  if (monitors.empty()) {
    Error("No monitors found");
    exit(-1);
  } else {
	  Debug(2, "%d monitors loaded", monitors.size());
  }

  Info("Starting RTSP Server version %s", ZM_VERSION);
  zmSetDefaultHupHandler();
  zmSetDefaultTermHandler();
  zmSetDefaultDieHandler();

  sigset_t block_set;
  sigemptyset(&block_set);

  sigaddset(&block_set, SIGHUP);
  sigaddset(&block_set, SIGUSR1);
  sigaddset(&block_set, SIGUSR2);

  int result = 0;

  while (!zm_terminate) {
    result = 0;

    for (const std::shared_ptr<Monitor> &monitor : monitors) {
      monitor->LoadCamera();

      while (!monitor->connect()) {
        Warning("Couldn't connect to monitor %d", monitor->Id());
        sleep(1);
      }
    }  // end foreach monitor

    RTSPServerThread ** rtsp_server_threads = nullptr;
    if (config.min_rtsp_port) {
      rtsp_server_threads = new RTSPServerThread *[monitors.size()];
      Debug(1, "Starting RTSP server because min_rtsp_port is set");
    } else {
      Debug(1, "Not starting RTSP server because min_rtsp_port not set");
      exit(-1);
    }

    for (size_t i = 0; i < monitors.size(); i++) {
        rtsp_server_threads[i] = new RTSPServerThread(monitors[i]);
        std::string streamname = monitors[i]->GetRTSPStreamName();
        ServerMediaSession *sms = rtsp_server_threads[i]->addSession(streamname);
        ZoneMinderFifoVideoSource *video_source = static_cast<ZoneMinderFifoVideoSource *>(rtsp_server_threads[i]->addFifo(sms, monitors[i]->GetVideoFifo()));
        video_source->setWidth(monitors[i]->Width());
        video_source->setHeight(monitors[i]->Height());
        FramedSource *audio_source = rtsp_server_threads[i]->addFifo(sms, monitors[i]->GetAudioFifo());
        rtsp_server_threads[i]->start();
    }

    while (!zm_terminate) {
      sleep(1);
      // What to do in here? Sleep mostly.  Wait for reload commands maybe watch for dead monitors.
      if ((result < 0) or zm_reload) {
        // Failure, try reconnecting
        break;
      }
    }  // end while ! zm_terminate and connected

    for (size_t i = 0; i < monitors.size(); i++) {
      rtsp_server_threads[i]->stop();
      rtsp_server_threads[i]->join();
      delete rtsp_server_threads[i];
      rtsp_server_threads[i] = nullptr;
    }

    delete[] rtsp_server_threads;
    rtsp_server_threads = nullptr;

    if (zm_reload) {
      for (std::shared_ptr<Monitor> &monitor : monitors) {
        monitor->Reload();
      }
      logTerm();
      logInit(log_id_string);
      zm_reload = false;
    }  // end if zm_reload
  }  // end while ! zm_terminate outer connection loop

  Image::Deinitialise();
  logTerm();
  zmDbClose();

	return zm_terminate ? 0 : result;
}
