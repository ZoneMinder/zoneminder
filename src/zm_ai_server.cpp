//
// ZoneMinder AI Daemon
// Copyright (C) 2025 ZoneMinder Inc
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

zm_ai_server - The ZoneMinder AI Server

=head1 SYNOPSIS

 zmc -m <monitor_id>
 zmc --monitor <monitor_id>
 zmc -h
 zmc --help
 zmc -v
 zmc --version

=head1 DESCRIPTION

This binary's job is to connect to local running monitors via shm
and perform AI analysis on latest frames

=head1 OPTIONS

 -m, --monitor_id           - ID of a monitor to stream
 -h, --help                 - Display usage information
 -v, --version              - Print the installed version of ZoneMinder

=cut

*/

#include "zm.h"
#include "zm_db.h"
#include "zm_config.h"
#include "zm_define.h"
#include "zm_monitor.h"
#include "zm_signal.h"
#include "zm_time.h"
#include "zm_utils.h"

#include <getopt.h>
#include <iostream>
#include <vector>

#include "zm_quadra.h"
#ifdef HAVE_UNTETHER_H
// Untether runtime API header
#include "zm_untether_speedai.h"
#endif

void Usage() {
  fprintf(stderr, "zm_ai_server -m <monitor_id>\n");

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
    if (c == -1)
      break;

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

  const char *log_id_string = "zm_ai_server";
  ///std::string log_id_string = std::string("zm_ai_server");
  ///if ( monitor_id > 0 ) log_id_string += stringtf("_m%d", monitor_id);

  logInit(log_id_string);
  zmLoadStaticConfig();
  zmDbConnect();
  zmLoadDBConfig();
  logInit(log_id_string);

  HwCapsDetect();

  std::string where = "`Deleted` = 0 AND `Capturing` != 'None' AND `ObjectDetection` != 'None'";
  if (staticConfig.SERVER_ID)
    where += stringtf(" AND `ServerId`=%d", staticConfig.SERVER_ID);
  if (monitor_id > 0)
    where += stringtf(" AND `Id`=%d", monitor_id);

  Info("Starting AI Server version %s", ZM_VERSION);
  zmSetDefaultHupHandler();
  zmSetDefaultTermHandler();
  zmSetDefaultDieHandler();

  std::unordered_map<unsigned int, std::shared_ptr<Monitor>> monitors;

  Quadra quadra;
  quadra.setup(-1);

#ifdef HAVE_UNTETHER_H
  SpeedAI *speedai;
  if (objectdetection == OBJECT_DETECTION_SPEEDAI) {
    speedai = new SpeedAI(this);
    if (!speedai->setup(
          "yolov5", "/var/cache/zoneminder/models/speedai_yolo.uxf"
          )) {
      delete speedai;
      speedai = nullptr;
    }
  }
#endif

  while (!zm_terminate) {
    std::unordered_map<unsigned int, std::shared_ptr<Monitor>> old_monitors = monitors;

    std::vector<std::shared_ptr<Monitor>> new_monitors = Monitor::LoadMonitors(where, Monitor::QUERY);
    for (const auto &monitor : new_monitors) {
      auto old_monitor_it = old_monitors.find(monitor->Id());
      if (old_monitor_it != old_monitors.end()) {
        Debug(1, "Found monitor in oldmonitors, clearing it");
        old_monitors.erase(old_monitor_it);
      } else {
        Debug(1, "Adding monitor %d to monitors", monitor->Id());
        monitors[monitor->Id()] = monitor;
      }
    }
    // Remove monitors that are no longer doing ai
    for (auto it = old_monitors.begin(); it != old_monitors.end(); ++it) {
      auto mid = it->first;
      auto &monitor = it->second;
      Debug(1, "Removing %d %s from monitors", monitor->Id(), monitor->Name());
      monitors.erase(mid);
    }

    for (auto it = monitors.begin(); it != monitors.end(); ++it) {
      auto &monitor = it->second;

      if (!monitor->ShmValid()) {
        Debug(1, "!ShmValid");
        monitor->disconnect();
        if (!monitor->connect()) {
          Warning("Couldn't connect to monitor %d", monitor->Id());
          monitor->Reload();  // This is to pickup change of colours, width, height, etc
          continue;
        }  // end if failed to connect
      }  // end if !ShmValid
      Monitor::SharedData *shared_data = monitor->getSharedData();
      int image_buffer_count = monitor->GetImageBufferCount();

      if (shared_data->decoder_image_count > shared_data->analysis_image_count) {
        int32_t decoder_image_index = shared_data->decoder_image_count % image_buffer_count;
        int32_t our_image_index = (shared_data->analysis_image_count++) % image_buffer_count;

        #ifdef HAVE_UNTETHER_H
          if (speedai) {
            if (!speedai->getQuadra()) {
              Error("Setting quadra in speedai");
              speedai->setQuadra(quadra);
            }

            Image *in_image = monitor->GetImage(decoder_image_index);

            int ret = speedai->send_image(in_image);
            if (ret <= 0) {
              Debug(1, "Can't send_packet %d queue size: %zu", packet->image_index, ai_queue.size());
              return ret;
            }

            Image *ai_image = monitor->GetAnalysisImage(our_image_index);
            ret = speedai->receive_detections(ai_image);
            if (0 < ret) {
              if (packet->ai_frame)
                zm_dump_video_frame(packet->ai_frame.get(), "after detect");
                } else if (0 > ret) {
                  Debug(1, "Failed yolo");
                  delete speedai;
                  // Since packets are still in the queue, they will get re-fed into it..
                  speedai = nullptr;
                  if (packet != delayed_packet) { // Can this be otherwise?
                    ai_queue.push_back(std::move(packet_lock));
                    Debug(1, "Pushing packet on queue, size now %zu", ai_queue.size());
                    packetqueue.increment_it(analysis_it);
                  }
                  return ret;

                } else {
                  // EAGAIN
                  Debug(1, "ret %d EAGAIN", ret);
                  //if (packet == delayed_packet) { // Can this be otherwise?
                  ai_queue.push_back(std::move(packet_lock));
                  //Debug(1, "Pushing packet %d on queue, size now %zu", packet->image_index, ai_queue.size());
                }
//count -= 1;
              //} while (ret == 0 and count > 0);
              //if
            } // end if delayed_packet
          } // edn if speedai
#endif



      }
    }  // end foreach monitor

    sleep(10);

    if (zm_reload) {
      logTerm();
      logInit(log_id_string);
      zm_reload = false;
    }  // end if zm_reload
  } // end while !zm_terminate

  Info("AI Server shutting down");

  for (const std::pair<const unsigned int, std::shared_ptr<Monitor>> &mon_pair : monitors) {
    unsigned int i = mon_pair.first;
    auto monitor = mon_pair.second;
    monitor->disconnect();
  }  // end foreach monitor

  Image::Deinitialise();
  logTerm();
  zmDbClose();

  return 0;
}
