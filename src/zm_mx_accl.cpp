//
// ZoneMinder MxAccl::MxAccl Class Implementation
// Copyright (C) 2024 ZoneMinder Inc
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


#include "zm_logger.h"
#include "zm_ffmpeg.h"
#include "zm_monitor.h"
#include "zm_object_classes.h"
#include "zm_signal.h"
#include "zm_vector2.h"

#include "zm_mx_accl.h"

#include <cstring>
#include <algorithm>
#include <filesystem>
#include <iostream>
#include <memory>
#include <random>
#include <vector>


#define USE_THREAD 0
#define BATCH_SIZE 10

#ifdef HAVE_MX_ACCL_H

// Shared object class names for detection results
static ObjectClasses object_classes;

MxAccl::MxAccl() :
  accl(),
  confidence(0.5),
  terminate_(false),
  inSize(0),
  outSize(0),
  count(0)
{
}

MxAccl::~MxAccl() {
  terminate_ = true;
  if (thread_.joinable()) thread_.join();
  while (jobs.size()) {
    delete jobs.front();
    jobs.pop_front();
  }

  if (accl) {
    accl->stop();
    delete accl;
    accl = nullptr;
  }
}

bool MxAccl::setup(
    const std::string &model_type,
    const std::string &model_file,
    float p_confidence
    ) {
  confidence = p_confidence;

  // Load and launch module_
  Debug(1, "MxAccl: Loading model %s", model_file.c_str());

  std::vector<int> device_ids = {-1};
  std::array<bool, 2> use_model_shape = {false, false};

  std::string model_file_lower = model_file;
  std::transform(model_file_lower.begin(), model_file_lower.end(), model_file_lower.begin(), ::tolower);
  if (model_file_lower.find("yolov8") == std::string::npos 
      &&
      model_file_lower.find("yolo_v8") == std::string::npos 
      ) {
    Error("We have no implemented support for anything other than yolov8");
    return false;
  }

  // Create the accelerator object and load the DFP model
  accl = new MX::Runtime::MxAccl(filesystem::path(model_file), device_ids, use_model_shape);
  if (!accl) {
    Error("Failed allocating MxAcc for %s", model_file.c_str());
    return false;
  }

  model_info = accl->get_model_info(0);
  Debug(1, "model_info in_featermaps: %d out_featuremaps: %d",
      model_info.num_in_featuremaps,
      model_info.num_out_featuremaps
      );
  input_height = model_info.in_featuremap_shapes[0][0];
  input_width = model_info.in_featuremap_shapes[0][1];
  input_depth = model_info.in_featuremap_shapes[0][2];
  input_channels = model_info.in_featuremap_shapes[0][3];

  yolov8 = new YOLOv8( input_width, input_height, input_channels, 0.5, 0.5);

  /*
  output_height = model_info.out_featuremap_shapes[0][0];
  output_width = model_info.out_featuremap_shapes[0][1];
  output_depth = model_info.out_featuremap_shapes[0][2];
  output_channels = model_info.out_featuremap_shapes[0][3];
  */

  for (int i=0; i<model_info.num_out_featuremaps; i++) {
    Debug(4, "ofmap shape = (%" PRId64 ", %" PRId64 ", %" PRId64 ", %" PRId64 ")",
        model_info.out_featuremap_shapes[i][0],
        model_info.out_featuremap_shapes[i][1],
        model_info.out_featuremap_shapes[i][2],
        model_info.out_featuremap_shapes[i][3]
        );
  }

  Debug(4, "ifmap shape = (%d, %d, %d, %d)", input_height, input_width, input_depth, input_channels);
  image_size = av_image_get_buffer_size(AV_PIX_FMT_RGB24, input_width, input_height, 0);


  auto in_cb = std::bind(&MxAccl::in_callback_func, this, std::placeholders::_1, std::placeholders::_2);
  auto out_cb = std::bind(&MxAccl::out_callback_func, this, std::placeholders::_1, std::placeholders::_2);
  accl->connect_stream(in_cb, out_cb, 21 /* channel_idx */, 0 /*model id*/);

  // Start the accelerator after connecting streams
  accl->start();

  return true;
}

MxAccl::Job * MxAccl::get_job() {
  Job *job = new Job(accl);
  job->scaled_frame->width = input_width;
  job->scaled_frame->height = input_height;

  if (av_frame_get_buffer(job->scaled_frame.get(), 32)) {
    Error("cannot allocate scaled frame buffer");
    return nullptr;
  }
  for (int i = 0; i < model_info.num_in_featuremaps; ++i) {
    float *pData = new float[model_info.in_featuremap_sizes[i]];
    memset(pData, 0, model_info.in_featuremap_sizes[i] * sizeof(float));
    job->accl_input_data.push_back(pData);
  }
  for (int i = 0; i < model_info.num_out_featuremaps; ++i) {
    float *pData = new float[model_info.out_featuremap_sizes[i]];
    memset(pData, 0, model_info.out_featuremap_sizes[i] * sizeof(float));
    job->accl_output_data.push_back(pData);
  }

  std::lock_guard<std::mutex> lck(mutex_);
  jobs.push_back(job);
  return job;
}

int MxAccl::send_image(Job *job, Image *image) {
  if (!job) {
    Error("No job");
    return -1;
  }
  if (!image) {
    Error("No image!");
    return -1;
  }

  av_frame_ptr frame = av_frame_ptr(av_frame_alloc());
  image->PopulateFrame(frame.get());
  return send_frame(job, frame.get());
}

int MxAccl::send_packet(Job *job, std::shared_ptr<ZMPacket> packet) {
  AVFrame *avframe = packet->in_frame.get();
  if (!avframe) {
    Error("NO inframe in packet %d, out of mem?", packet->image_index);
    return -1;
  }
  return send_frame(job, avframe);
}

int MxAccl::send_frame(MxAccl::Job *job, AVFrame *avframe) {
  count++;
  Debug(1, "MxAccl::send_frame %d", count);

  SystemTimePoint starttime = std::chrono::system_clock::now();

  job->sw_scale_ctx = sws_getCachedContext(job->sw_scale_ctx,
      avframe->width, avframe->height, static_cast<AVPixelFormat>(avframe->format),
      input_width, input_height, AV_PIX_FMT_RGB24,
      SWS_BICUBIC, nullptr, nullptr, nullptr);
  if (!job->sw_scale_ctx) {
    Error("Can't swscale");
    return -1;
  }

  int ret = sws_scale(job->sw_scale_ctx, (const uint8_t * const *)avframe->data,
      avframe->linesize, 0, avframe->height, job->scaled_frame->data, job->scaled_frame->linesize);
  if (ret < 0) {
    Error("cannot do sw scale: inframe data %p, linesize %d/%d/%d/%d, height %d to %d linesize",
        static_cast<void*>(avframe->data), avframe->linesize[0], avframe->linesize[1],
        avframe->linesize[2], avframe->linesize[3], avframe->height, job->scaled_frame->linesize[0]);
    return -1;
  }
  SystemTimePoint endtime = std::chrono::system_clock::now();
  Debug(1, "scale took: %.3f seconds", FPSeconds(endtime - starttime).count());

  job->m_width_rescale = ((float)input_width / (float)avframe->width);
  job->m_height_rescale = ((float)input_height / (float)avframe->height);
  job->lock();
  {
    std::lock_guard<std::mutex> lck(mutex_);
    send_queue.push_back(job);
    condition_.notify_all();
  }
  job->wait();
  endtime = std::chrono::system_clock::now();
  Debug(1, "waiting took: %.3f seconds", FPSeconds(endtime - starttime).count());
  job->unlock();

  endtime = std::chrono::system_clock::now();
  if (endtime - starttime > Milliseconds(60)) {
    Warning("scale is too slow: %.3f seconds", FPSeconds(endtime - starttime).count());
  } else {
    Debug(1, "scale took: %.3f seconds", FPSeconds(endtime - starttime).count());
  }
  return 1;
}  // int MxAccl::send_frame(AVFrame *frame)

const nlohmann::json MxAccl::receive_detections(Job *job, float object_threshold) {
  // Convert yolov8 result to nlohmann::json
  nlohmann::json predictions = nlohmann::json::array();
  std::queue<BBox> bboxes = job->result.bboxes;

  // How bout an interator
  while (!bboxes.empty()) {
    BBox bbox = bboxes.front();
    bboxes.pop();

#if 0
    int l = static_cast<int>((bbox.x_min - padding_width_) / letterbox_ratio_);
    int t = static_cast<int>((bbox.y_min - padding_height_) / letterbox_ratio_);
    int r = static_cast<int>((bbox.x_max - padding_width_) / letterbox_ratio_);
    int b = static_cast<int>((bbox.y_max - padding_height_) / letterbox_ratio_);

#else
    float l = bbox.x_min;
    float t = bbox.y_min;
    float r = bbox.x_max;
    float b = bbox.y_max;
#endif
     // coordinates must be scaled to true image dimensions
    l = l / job->m_width_rescale;
    t = t / job->m_height_rescale;
    r = r / job->m_width_rescale;
    b = b / job->m_height_rescale;

    std::array<float, 4> cv_bbox = {l, t, r, b};

    // map class_id to class name
    const std::string &class_name = object_classes.getClassName(bbox.class_index);
    predictions.push_back({{"class", class_name}, {"bbox", cv_bbox}, {"score", bbox.class_score}});
  }

  return predictions;
}

bool MxAccl::in_callback_func(vector<const MX::Types::FeatureMap *> dst, int channel_idx) {
  Debug(1, "MxAccl locking, queue size %zu channel %d", send_queue.size(), channel_idx);

  Job *job = nullptr;
  {
    std::unique_lock<std::mutex> lck(mutex_);
    while (!send_queue.size() and !zm_terminate) {
      Debug(1, "MxAccl waiting, queue size %zu", send_queue.size());
      condition_.wait(lck);
    }
    if (!send_queue.size()) return false;
    job = send_queue.front();
    send_queue.pop_front();
  }

  SystemTimePoint starttime = std::chrono::system_clock::now();
  // Compute letterbox padding for YOLO model
  yolov8->ComputePadding(job->scaled_frame->width, job->scaled_frame->height);
  yolov8->PreProcess(job->scaled_frame->data[0], job->scaled_frame->width, job->scaled_frame->height, job->accl_input_data);
  // Set preprocessed input data for accelerator
  for (int in_idx = 0; in_idx < model_info.num_in_featuremaps; ++in_idx) {
    dst[in_idx]->set_data(job->accl_input_data[in_idx]);
  }
  SystemTimePoint endtime = std::chrono::system_clock::now();
  if (endtime - starttime > Milliseconds(60)) {
    Warning("in_callback is too slow: %.3f seconds", FPSeconds(endtime - starttime).count());
  } else {
    Debug(1, "in_callback took: %.3f seconds", FPSeconds(endtime - starttime).count());
  }
  {
    std::lock_guard<std::mutex> lck(mutex_);
    receive_queue.push_back(job);
  }
  condition_.notify_all();
  return true;
}

bool MxAccl::out_callback_func(vector<const MX::Types::FeatureMap *> src, int channel_idx) {
  YOLOv8Result result;
  Debug(1, "out_callback,channel  %d", channel_idx);

  Job *job;
  {
    std::unique_lock<std::mutex> lck(mutex_);
    while (!receive_queue.size() and !zm_terminate) {
      Debug(1, "MxAccl waiting, queue size %zu", receive_queue.size());
      condition_.wait(lck);
    }
    if (!receive_queue.size()) return false;
    job = receive_queue.front();
    receive_queue.pop_front();
  }

  // Retrieve output data from accelerator
  for (int out_idx = 0; out_idx < model_info.num_out_featuremaps; ++out_idx) {
    src[out_idx]->get_data(job->accl_output_data[out_idx]);
  }
  // Set confidence threshold and process detection results
  yolov8->SetConfidenceThreshold(confidence);
  yolov8->PostProcess(job->accl_output_data, result);
  job->result = std::move(result);

  job->notify();
  return true;
}

#endif
