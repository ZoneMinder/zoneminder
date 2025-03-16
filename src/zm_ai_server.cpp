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
#include <thread>
#include <vector>

#include <nlohmann/json.hpp>
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

void SpeedAIDetect(std::shared_ptr<Monitor> monitor);
int draw_boxes( Quadra::filter_worker *drawbox_filter, AVFilterContext *drawbox_filter_ctx, Image *in_image, Image *out_image, const nlohmann::json &coco_object, int font_size);
int draw_box( Quadra::filter_worker * drawbox_filter, AVFilterContext *drawbox_filter_ctx, AVFrame *inframe, AVFrame **outframe, int x, int y, int w, int h);

#ifdef HAVE_UNTETHER_H
  SpeedAI *speedai;
#endif

Quadra quadra;

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

  std::string where = "`Deleted` = 0 AND `Capturing` != 'None' AND `ObjectDetection` = 'SpeedAI'";
  if (staticConfig.SERVER_ID)
    where += stringtf(" AND `ServerId`=%d", staticConfig.SERVER_ID);
  if (monitor_id > 0)
    where += stringtf(" AND `Id`=%d", monitor_id);

  Info("Starting AI Server version %s", ZM_VERSION);
  zmSetDefaultHupHandler();
  zmSetDefaultTermHandler();
  zmSetDefaultDieHandler();

  std::unordered_map<unsigned int, std::shared_ptr<Monitor>> monitors;

  quadra.setup(-1);

#ifdef HAVE_UNTETHER_H
  speedai = new SpeedAI();
  if (!speedai->setup( "yolov5", "/var/cache/zoneminder/models/speedai_yolo.uxf")) {
    delete speedai;
    speedai = nullptr;
  }
#endif

  std::unordered_map<unsigned int, std::thread> threads;
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
      threads[monitor->Id()] = std::thread(&SpeedAIDetect, monitor);
    }
  }
  // Remove monitors that are no longer doing ai
  for (auto it = old_monitors.begin(); it != old_monitors.end(); ++it) {
    auto mid = it->first;
    auto &monitor = it->second;
    Debug(1, "Removing %d %s from monitors", monitor->Id(), monitor->Name());
    monitors.erase(mid);
  }

  while (!zm_terminate) {
    //for (auto it = monitors.begin(); it != monitors.end(); ++it) {
    //auto &monitor = it->second;
    if (zm_reload) {
      logTerm();
      logInit(log_id_string);
      zm_reload = false;
    }  // end if zm_reload
    sleep(1);
  } // end while !zm_terminate

  Info("AI Server shutting down");

  for (const std::pair<const unsigned int, std::shared_ptr<Monitor>> &mon_pair : monitors) {
    unsigned int i = mon_pair.first;
    if (threads[i].joinable()) threads[i].join();

    auto monitor = mon_pair.second;
    monitor->disconnect();
  }  // end foreach monitor

#ifdef HAVE_UNTETHER_H
  delete speedai;
  speedai = nullptr;
#endif

  Image::Deinitialise();
  logTerm();
  zmDbClose();

  return 0;
}

void SpeedAIDetect(std::shared_ptr<Monitor> monitor) {
#ifdef HAVE_UNTETHER_H
  SpeedAI::Job *job = speedai->get_job();
  Quadra::filter_worker *drawbox_filter;
  AVFilterContext *drawbox_filter_ctx;

  while (!zm_terminate) {
    Debug(1, "Checking monitor %d %s", monitor->Id(), monitor->Name());

    if (!monitor->isConnected()) {
      if (!monitor->connect()) {
        Warning("Couldn't connect to monitor %d", monitor->Id());
        monitor->Reload();  // This is to pickup change of colours, width, height, etc
        continue;
      }  // end if failed to connect
    }

    Monitor::SharedData *shared_data = monitor->getSharedData();
    int image_buffer_count = monitor->GetImageBufferCount();

    int ret;
    drawbox_filter = new Quadra::filter_worker();
    drawbox_filter->buffersink_ctx = nullptr;
    drawbox_filter->buffersrc_ctx = nullptr;
    drawbox_filter->filter_graph = nullptr;
    if ((ret = quadra.init_filter("drawbox", drawbox_filter, false, monitor->Width(), monitor->Height(), AV_PIX_FMT_YUV420P)) < 0) {
      Error("cannot initialize drawbox filter");
      //return false;
    }

    for (unsigned int i = 0; i < drawbox_filter->filter_graph->nb_filters; i++) {
      if (strstr(drawbox_filter->filter_graph->filters[i]->name, "drawbox") != nullptr) {
        drawbox_filter_ctx = drawbox_filter->filter_graph->filters[i];
        break;
      }
    }

    if (drawbox_filter_ctx == nullptr) {
      Error( "cannot find valid drawbox filter");
      //return false;
    }

    // Start at latest decoded image
    shared_data->analysis_image_count = shared_data->decoder_image_count;

    while (!zm_terminate) {
      if (!monitor->ShmValid()) {
        Debug(1, "!ShmValid");
        monitor->disconnect();
        if (!monitor->connect()) {
          Warning("Couldn't connect to monitor %d", monitor->Id());
          monitor->Reload();  // This is to pickup change of colours, width, height, etc
          break;
        }  // end if failed to connect
      }  // end if !ShmValid
      //Debug(1, "Doing monitor %d.  Decoder index is %d Our index is %d",
          //monitor->Id(), shared_data->decoder_image_count, shared_data->analysis_image_count);

      if (shared_data->decoder_image_count > shared_data->analysis_image_count) {
        int32_t decoder_image_index = shared_data->decoder_image_count % image_buffer_count;
        int32_t our_image_index = (shared_data->analysis_image_count+1) % image_buffer_count;

        Image *in_image = monitor->GetDecodedImage(decoder_image_index);

#ifdef HAVE_UNTETHER_H
        if (speedai) {
          Debug(1, "Doing SpeedAI on monitor %d.  Decoder index is %d=%d Our index is %d=%d",
              monitor->Id(), shared_data->decoder_image_count, decoder_image_index,
              shared_data->analysis_image_count, our_image_index);

          do {
            job = speedai->send_image(job, in_image);
            if (!job) {
              Warning("Can't send_packet %d", decoder_image_index);
            }
          } while (!job);

          Image *ai_image = monitor->GetAnalysisImage(our_image_index);
          nlohmann::json detections = speedai->receive_detections(job);
          Debug(1, "detections %s", detections.dump().c_str());
          if (detections.size()) {
            draw_boxes(drawbox_filter, drawbox_filter_ctx, in_image, ai_image, detections, monitor->LabelSize());
          } else {
            ai_image->Assign(*in_image);
          }

          shared_data->analysis_image_count++;
          shared_data->last_analysis_index = our_image_index;
        } // end if speedai
#endif
      } else {
        Debug(1, "Not Doing SpeedAI on monitor %d.  Decoder index is %d Our index is %d",
            monitor->Id(), shared_data->decoder_image_count, shared_data->analysis_image_count);
      }  // end if have a new image
      Microseconds delay = monitor->GetCaptureDelay();
      Debug(1, "Sleeping for %ld microseconds", delay.count());
      std::this_thread::sleep_for(delay);
    }  // end while !zm_terminate
  }  // end while !zm_terminate
  if (drawbox_filter) {
    avfilter_graph_free(&drawbox_filter->filter_graph);
    delete drawbox_filter;
  }

#endif
} // end SpeedAIDetect   

int draw_boxes(
    Quadra::filter_worker *drawbox_filter,
    AVFilterContext *drawbox_filter_ctx,
    Image *in_image, Image *out_image,
    const nlohmann::json &coco_object, int font_size) {
  //Rgb colour = kRGBRed;

  try {
    Debug(1, "SpeedAI coco: %s", coco_object.dump().c_str());
    if (coco_object.size()) {
      AVFrame *in_frame = av_frame_alloc();
      in_image->PopulateFrame(in_frame);

      for (auto it = coco_object.begin(); it != coco_object.end(); ++it) {
        nlohmann::json detection = *it;
        nlohmann::json bbox = detection["bbox"];

        //Debug(1, "%s", bbox.dump().c_str());
        std::vector<Vector2> coords;
        int x1 = bbox[0];
        int y1 = bbox[1];
        int x2 = bbox[2];
        int y2 = bbox[3];
        AVFrame *out_frame = av_frame_alloc();
        if (!out_frame) {
          Error("cannot allocate output filter frame");
          return NIERROR(ENOMEM);
        }

        int ret = draw_box(drawbox_filter, drawbox_filter_ctx, in_frame, &out_frame, x1, y1, x2-x1, y2-y1);
        if (ret < 0) {
          Error("draw box failed");
          return ret;
        }
        zm_dump_video_frame(out_frame, "SpeedAI: boxes");

        std::string coco_class = detection["class_name"];
        float score = detection["score"];
        std::string annotation = stringtf("%s %d%%", coco_class.c_str(), static_cast<int>(100*score));
        Image temp_image(out_frame);
        temp_image.Annotate(annotation.c_str(), Vector2(x1, y1), font_size, kRGBWhite, kRGBTransparent);

        av_frame_free(&in_frame);
        in_frame = out_frame;
      }  // end foreach detection
      out_image->Assign(in_frame);
    } else {
      out_image->Assign(*in_image);
    }  // end if coco
  } catch (std::exception const & ex) {
    Error("draw_box Exception: %s", ex.what());
  }

  return 1;
}

int draw_box(
    Quadra::filter_worker *drawbox_filter,
    AVFilterContext *drawbox_filter_ctx,
    AVFrame *inframe,
    AVFrame **outframe,
    int x, int y, int w, int h
    ) {
  if (!drawbox_filter_ctx) {
    Error("No drawbox_filter_ct");
    return -1;
  }

  char drawbox_option[32];
  std::string color = "green";
  int n, ret;


  n = snprintf(drawbox_option, sizeof(drawbox_option), "%d", x); drawbox_option[n] = '\0';
  av_opt_set(drawbox_filter_ctx->priv, "x", drawbox_option, 0);

  n = snprintf(drawbox_option, sizeof(drawbox_option), "%d", y); drawbox_option[n] = '\0';
  av_opt_set(drawbox_filter_ctx->priv, "y", drawbox_option, 0);

  n = snprintf(drawbox_option, sizeof(drawbox_option), "%d", w); drawbox_option[n] = '\0';
  av_opt_set(drawbox_filter_ctx->priv, "w", drawbox_option, 0);

  n = snprintf(drawbox_option, sizeof(drawbox_option), "%d", h); drawbox_option[n] = '\0';
  av_opt_set(drawbox_filter_ctx->priv, "h", drawbox_option, 0);

  ret = avfilter_graph_send_command(drawbox_filter->filter_graph, "drawbox", "color", color.c_str(), nullptr, 0, 0);
  if (ret < 0) {
    Error("cannot send drawbox filter command, ret %d.", ret);
    return ret;
  }

  ret = av_buffersrc_add_frame_flags(drawbox_filter->buffersrc_ctx, inframe, AV_BUFFERSRC_FLAG_KEEP_REF);
  if (ret < 0) {
    Error("cannot add frame to drawbox buffer src %d", ret);
    return ret;
  }

  do {
    ret = av_buffersink_get_frame(drawbox_filter->buffersink_ctx, *outframe);
    if (ret == AVERROR(EAGAIN)) {
      continue;
    } else if (ret < 0) {
      Error("cannot get frame from drawbox buffer sink %d", ret);
      return ret;
    } else {
      break;
    }
  } while (!zm_terminate);
  return 0;
}
