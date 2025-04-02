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

#include "zm_ai_server.h"

void Usage() {
  fprintf(stderr, "zm_ai_server -m <monitor_id>\n");

  fprintf(stderr, "Options:\n");
  fprintf(stderr, "  -m, --monitor <monitor_id> : We default to all monitors use this to specify just one\n");
  fprintf(stderr, "  -h, --help                 : This screen\n");
  fprintf(stderr, "  -v, --version              : Report the installed version of ZoneMinder\n");
  exit(0);
}

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
    return 0;
  }
#endif

  std::unordered_map<unsigned int, AIThread *> threads;

  while (!zm_terminate) {

    std::unordered_map<unsigned int, std::shared_ptr<Monitor>> old_monitors = monitors;
    std::vector<std::shared_ptr<Monitor>> new_monitors = Monitor::LoadMonitors(where, Monitor::QUERY);

    for (const auto &monitor : new_monitors) {
      auto old_monitor_it = old_monitors.find(monitor->Id());
      if (old_monitor_it != old_monitors.end()) {
        //Debug(1, "Found monitor %d in oldmonitors, clearing it", monitor->Id());
        old_monitors.erase(old_monitor_it);
      } else {
        Debug(1, "Adding monitor %d to monitors", monitor->Id());
        monitors[monitor->Id()] = monitor;
        threads[monitor->Id()] = new AIThread(monitor
#if HAVE_UNTETHER_H
            , speedai
#endif
            );
        //threads[monitor->Id()]->Start();
      }
    }

    // Remove monitors that are no longer doing ai
    for (auto it = old_monitors.begin(); it != old_monitors.end(); ++it) {
      auto mid = it->first;
      auto &monitor = it->second;
      threads[monitor->Id()]->Stop();
      threads[monitor->Id()]->Join();
      Debug(1, "Removing %d %s from monitors", monitor->Id(), monitor->Name());
      monitors.erase(mid);
    }

    //for (auto it = monitors.begin(); it != monitors.end(); ++it) {
    //auto &monitor = it->second;
    if (0 and zm_reload) {
      logTerm();
      logInit(log_id_string);
      zm_reload = false;
    }  // end if zm_reload
    sleep(10);
  } // end while !zm_terminate

  Info("AI Server shutting down");

  for (const std::pair<const unsigned int, std::shared_ptr<Monitor>> &mon_pair : monitors) {
    unsigned int i = mon_pair.first;
    threads[i]->Stop();
    threads[i]->Join();

    auto monitor = mon_pair.second;
    monitor->disconnect();
  }  // end foreach monitor
  
  monitors.clear();
  threads.clear();

#ifdef HAVE_UNTETHER_H
  delete speedai;
  speedai = nullptr;
#endif

  Image::Deinitialise();
  dbQueue.stop();
  zmDbClose();
  logTerm();

  return 0;
}

void AIThread::Inference() {
  job = speedai->get_job();

  int ret;
  drawbox_filter = new Quadra::filter_worker();
  if ((ret = quadra.init_filter("drawbox", drawbox_filter, false, monitor_->Width(), monitor_->Height(), AV_PIX_FMT_YUV420P)) < 0) {
    Error("cannot initialize drawbox filter");
    return;
  }
  drawbox_filter_ctx = drawbox_filter->find_filter_ctx("drawbox");
  if (drawbox_filter_ctx == nullptr) {
    Error( "cannot find valid drawbox filter");
    return;
  }

  while (!(terminate_ or zm_terminate)) {
    std::shared_ptr<ZMPacket> packet = nullptr;

    {
      //Debug(1, "locking, queue size %zu", send_queue.size());
      std::unique_lock<std::mutex> lck(mutex_);
      while (!send_queue.size() and !terminate_) {
        condition_.wait(lck);
      }
      if (terminate_) break;
      packet = send_queue.front();
    }

    if (packet) {
      Monitor::SharedData *shared_data = monitor_->getSharedData();
      Debug(1, "Sending image %d", packet->image_index);
#ifdef HAVE_UNTETHER_H
      speedai->send_image(job, packet->image);

      Image *ai_image = monitor_->GetAnalysisImage(packet->image_index);
      nlohmann::json detections = speedai->receive_detections(job, monitor_->ObjectDetection_Object_Threshold());
      //Debug(1, "detections %s", detections.dump().c_str());

      if (detections.size()) {
        draw_boxes(drawbox_filter, drawbox_filter_ctx, packet->image, ai_image, detections, monitor_->LabelSize());
      } else {
        ai_image->Assign(*packet->image);
      }
#endif

      shared_data->last_analysis_index = packet->image_index;

      std::unique_lock<std::mutex> lck(mutex_);
      send_queue.pop_front();
      packet = nullptr;
    }  // end if job
  }  // end while forever
  
  if (drawbox_filter) {
    delete drawbox_filter;
    drawbox_filter = nullptr;
    drawbox_filter_ctx = nullptr;
  }
}  // end AIThread::Inference

void AIThread::Run() {
#ifdef HAVE_UNTETHER_H
  if (!speedai) {
    Error("No speedai");
    return;
  }


  while (!monitor_->ShmValid() and !zm_terminate and !terminate_) {
    if (monitor_->isConnected()) {
      Debug(1, "!ShmValid");
      monitor_->disconnect();
    }
    if (!monitor_->connect()) {
      Warning("Couldn't connect to monitor %d", monitor_->Id());
      monitor_->Reload();  // This is to pickup change of colours, width, height, etc
      sleep(1);
      continue;
    }  // end if failed to connect
  }  // end if !ShmValid

  Monitor::SharedData *shared_data = monitor_->getSharedData();
  int image_buffer_count = monitor_->GetImageBufferCount();
  shared_data->analysis_image_count = 0;
  // Start at latest decoded image
  while (shared_data->decoder_image_count <= 0 and !(zm_terminate or terminate_)) {
    int capture_fps = static_cast<int>(monitor_->GetFPS());
    Microseconds delay = Microseconds(1000*capture_fps);
    //delay = Microseconds(3000);
    //Debug(1, "Sleeping for %ld microseconds waiting for decoder", delay.count());
    std::this_thread::sleep_for(delay);
  }
  analysis_image_count = shared_data->decoder_image_count;
  if (analysis_image_count <0) analysis_image_count = 0;
  int32_t decoder_image_count = shared_data->decoder_image_count;
  int32_t image_index = shared_data->last_analysis_index;

  while (!zm_terminate and !terminate_) {
    if (!monitor_->ShmValid()) {
      Debug(1, "!ShmValid");
      monitor_->disconnect();
      if (!monitor_->connect()) {
        Warning("Couldn't connect to monitor %d", monitor_->Id());
        monitor_->Reload();  // This is to pickup change of colours, width, height, etc
        sleep(1);
        continue;
      }  // end if failed to connect
      shared_data = monitor_->getSharedData();
      image_buffer_count = monitor_->GetImageBufferCount();
      shared_data->analysis_image_count = 0;
    }  // end if !ShmValid

    decoder_image_count = shared_data->decoder_image_count;
    while ((shared_data->last_decoder_index == image_buffer_count) and !(zm_terminate or terminate_)) {
      Microseconds delay = Microseconds(30000);
      Debug(1, "Sleeping for %ld microseconds waiting for decoder", delay.count());
      std::this_thread::sleep_for(delay);
    }

    if (decoder_image_count - analysis_image_count > image_buffer_count) {
      Debug(1,"Falling behind %d - %d > %d", decoder_image_count, analysis_image_count, image_buffer_count);
      analysis_image_count = decoder_image_count;
    }

    if (
        (shared_data->last_decoder_index != image_index)
        and
        (send_queue.size() <= static_cast<unsigned int>(image_buffer_count))
        ) {
      image_index = shared_data->last_decoder_index % image_buffer_count;
      Debug(3, "Doing SpeedAI on monitor %d.  Decoder index is %d=%d Our index is %d=%d, queue %zu",
          monitor_->Id(),
          decoder_image_count, shared_data->last_decoder_index,
          analysis_image_count, image_index, send_queue.size());

      Image *unsafe_image = monitor_->GetDecodedImage(image_index);
      // Have to copy it in case it gets overwritten
      Image *in_image = new Image(*unsafe_image);
      std::shared_ptr<ZMPacket> packet = std::make_shared<ZMPacket>();
      packet->image = in_image;
      packet->image_index = image_index;

      std::unique_lock<std::mutex> lck(mutex_);
      send_queue.push_back(packet);
      condition_.notify_all();
      analysis_image_count++;
      shared_data->analysis_image_count = analysis_image_count;

      if (!zm_terminate and !terminate_) {
        if (shared_data->decoder_image_count <= analysis_image_count) {
          float capture_fps = monitor_->GetFPS();
          Microseconds delay = std::chrono::duration_cast<Microseconds>(FPSeconds(1 / capture_fps));
          if (delay < Microseconds(30000)) delay = Microseconds(30000);
          if (delay > Microseconds(300000)) delay = Microseconds(300000);
          Debug(4, "Sleeping for %ld microseconds after queuing", delay.count());
          std::this_thread::sleep_for(delay);
        }
      }

    } else {
      Debug(4, "Not Doing SpeedAI on monitor %d.  Decoder count is %d index %d Our count is %d, last_index is %d, index %d",
          monitor_->Id(), decoder_image_count, shared_data->last_decoder_index,
          shared_data->analysis_image_count, shared_data->last_analysis_index, image_index);

      if (!zm_terminate and !terminate_) {
        float capture_fps = monitor_->GetFPS();
        Microseconds delay = std::chrono::duration_cast<Microseconds>(FPSeconds(1 / capture_fps));
        if (delay < Microseconds(30000)) delay = Microseconds(30000);
        if (delay > Microseconds(300000)) delay = Microseconds(300000);
        Debug(4, "Sleeping for %ld microseconds waiting for image", delay.count());
        std::this_thread::sleep_for(delay);
      }
    }  // end if have a new image
  }  // end while !zm_terminate
  if (monitor_->ShmValid()) shared_data->analysis_image_count = 0;
#endif
} // end SpeedAIDetect   

int draw_boxes(
    Quadra::filter_worker *drawbox_filter,
    AVFilterContext *drawbox_filter_ctx,
    Image *in_image, Image *out_image,
    const nlohmann::json &coco_object, int font_size) {
  //Rgb colour = kRGBRed;

  try {
    //Debug(1, "SpeedAI coco: %s", coco_object.dump().c_str());
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
        //zm_dump_video_frame(out_frame, "SpeedAI: boxes");

        std::string coco_class = detection["class_name"];
        float score = detection["score"];
        std::string annotation = stringtf("%s %d%%", coco_class.c_str(), static_cast<int>(100*score));
        Image temp_image(out_frame);
        temp_image.Annotate(annotation.c_str(), Vector2(x1, y1), font_size, kRGBWhite, kRGBTransparent);

        av_frame_free(&in_frame);
        in_frame = out_frame;
      }  // end foreach detection
      out_image->Assign(in_frame);
      av_frame_free(&in_frame);
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

AIThread::AIThread(const std::shared_ptr<Monitor> monitor
#if HAVE_UNTETHER_H
    , SpeedAI *p_speedai
#endif
    ) :
  monitor_(monitor), terminate_(false)
#if HAVE_UNTETHER_H
  , speedai(p_speedai)
#endif
{
  thread_ = std::thread(&AIThread::Run, this);
  inference_thread_ = std::thread(&AIThread::Inference, this);
}

AIThread::~AIThread() {
  Stop();
  Join();
}

void AIThread::Start() {
  Join();
  terminate_ = false;
  Debug(3, "Starting ai thread");
  thread_ = std::thread(&AIThread::Run, this);
  inference_thread_ = std::thread(&AIThread::Inference, this);
}

void AIThread::Stop() {
  terminate_ = true;
}
void AIThread::Join() {
  if (thread_.joinable()) thread_.join();
  if (inference_thread_.joinable()) inference_thread_.join();
}

