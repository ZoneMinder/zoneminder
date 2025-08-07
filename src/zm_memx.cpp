//
// ZoneMinder MemX::MemX Class Implementation
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
#include "zm_signal.h"
#include "zm_vector2.h"

#include "zm_memx.h"

#include <cstring>
#include <algorithm>
#include <filesystem>
#include <iostream>
#include <memory>
#include <random>
#include <vector>


#define USE_THREAD 1
#define BATCH_SIZE 10

#ifdef HAVE_MEMX_H
static const char *coco_classes[] = {"person", "bicycle", "car", "motorcycle", "airplane", "bus", "train", "truck", "boat",
  "traffic light", "fire hydrant", "stop sign", "parking meter", "bench", "bird", "cat",
  "dog", "horse", "sheep", "cow", "elephant", "bear", "zebra", "giraffe", "backpack",
  "umbrella", "handbag", "tie", "suitcase", "frisbee", "skis", "snowboard", "sports ball",
  "kite", "baseball bat", "baseball glove", "skateboard", "surfboard", "tennis racket",
  "bottle", "wine glass", "cup", "fork", "knife", "spoon", "bowl", "banana", "apple",
  "sandwich", "orange", "broccoli", "carrot", "hot dog", "pizza", "donut", "cake", "chair",
  "couch", "potted plant", "bed", "dining table", "toilet", "tv", "laptop", "mouse", "remote",
  "keyboard", "cell phone", "microwave", "oven", "toaster", "sink", "refrigerator", "book",
  "clock", "vase", "scissors", "teddy bear", "hair drier", "toothbrush"};

MemX::MemX() :
  accl(),
  terminate_(false),
  batchSize(0),
  inSize(0),
  outSize(0),
  count(0)
{
  image_size = av_image_get_buffer_size(AV_PIX_FMT_RGB24, MODEL_WIDTH, MODEL_HEIGHT, 32);
}

MemX::~MemX() {
  terminate_ = true;
  if (thread_.joinable()) thread_.join();
  while (jobs.size()) {
    delete jobs.front();
    jobs.pop_front();
  }
}

bool MemX::setup(
    const std::string &model_type,
    const std::string &model_file
    ) {
  // Load and launch module_
  Debug(1, "MemX: Loading model %s", model_file.c_str());

  std::vector<int> device_ids = {0};
  std::array<bool, 2> use_model_shape = {false, false};

  // Create the accelerator object and load the DFP model
  accl = new MX::Runtime::MxAccl(filesystem::path(model_file), device_ids, use_model_shape);

  //int dfp_tag = accl.connect_dfp(model_file.c_str());
  model_info = accl->get_model_info(0);
  batchSize = 1; // only 1 issupported
#if USE_THREAD
  thread_ = std::thread(&MemX::Run, this);
#endif
  return true;
}

void MemX::Run() {
  while (!(terminate_ or zm_terminate)) {

    Debug(1, "MemX locking, queue size %zu", send_queue.size());
    std::unique_lock<std::mutex> lck(mutex_);
    while (send_queue.size() < BATCH_SIZE and ! zm_terminate) {
      Debug(1, "MemX waiting, queue size %zu", send_queue.size());
      condition_.wait(lck);
    }
    if (send_queue.size() >= BATCH_SIZE) {
      Debug(1, " batch");
      for (int i = BATCH_SIZE; i > 0 ; i--) {
        Job *job = send_queue.front();
        send_queue.pop_front();
        std::unique_lock<std::mutex> job_lck(job->mutex_);

        Debug(3, "MemX::enqueue");
        SystemTimePoint starttime = std::chrono::system_clock::now();
        int err = 0;
        if (err) {
//Error("Failed enqueue %s", uai_err_string(err));
        } else {
          SystemTimePoint endtime = std::chrono::system_clock::now();
          if (endtime - starttime > Milliseconds(40)) {
            Warning("MemX enqueue is too slow: %.3f seconds", FPSeconds(endtime - starttime).count());
          } else {
            Debug(3, "MemX enqueue took: %.3f seconds", FPSeconds(endtime - starttime).count());
          }
        }
        job->notify();
      }  // end foreach job in batch
    } else {
      Debug(1, "Not batch");
    } // end if BATCH
  }  // end while forever
}

MemX::Job * MemX::send_image(Job *job, Image *image) {
  av_frame_ptr frame = av_frame_ptr(av_frame_alloc());
  image->PopulateFrame(frame.get());
  return send_frame(job, frame.get());
}

MemX::Job * MemX::send_packet(Job *job, std::shared_ptr<ZMPacket> packet) {
  AVFrame *avframe = packet->in_frame.get();
  if (!avframe) {
    Error("NO inframe in packet %d, out of mem?", packet->image_index);
    return nullptr;
  }
  return send_frame(job, avframe);
}

MemX::Job * MemX::get_job() {
  Job *job = new Job(accl);
  if (av_frame_get_buffer(job->scaled_frame.get(), 32)) {
    Error("cannot allocate scaled frame buffer");
    return nullptr;
  }

  std::unique_lock<std::mutex> lck(mutex_);

  jobs.push_back(job);
  return job;
}

MemX::Job * MemX::send_frame(Job *job, AVFrame *avframe) {
  count++;
  Debug(1, "MemX::send_frame %d", count);

  SystemTimePoint starttime = std::chrono::system_clock::now();
  job->sw_scale_ctx = sws_getCachedContext(job->sw_scale_ctx,
        avframe->width, avframe->height, static_cast<AVPixelFormat>(avframe->format),
        MODEL_WIDTH, MODEL_HEIGHT, AV_PIX_FMT_RGB24,
        SWS_BICUBIC, nullptr, nullptr, nullptr);

  //Debug(1, "Start scale");
  int ret = sws_scale(job->sw_scale_ctx, (const uint8_t * const *)avframe->data,
      avframe->linesize, 0, avframe->height, job->scaled_frame->data, job->scaled_frame->linesize);
  if (ret < 0) {
    Error("cannot do sw scale: inframe data 0x%lx, linesize %d/%d/%d/%d, height %d to %d linesize",
        (unsigned long)avframe->data, avframe->linesize[0], avframe->linesize[1],
        avframe->linesize[2], avframe->linesize[3], avframe->height, job->scaled_frame->linesize[0]);
    return nullptr;
  }
  job->m_width_rescale = ((float)MODEL_WIDTH / (float)avframe->width);
  job->m_height_rescale = ((float)MODEL_HEIGHT / (float)avframe->height);

  SystemTimePoint endtime = std::chrono::system_clock::now();
  if (endtime - starttime > Milliseconds(60)) {
    Warning("receive_detections is too slow: %.3f seconds", FPSeconds(endtime - starttime).count());
  } else {
    Debug(1, "receive_detections took: %.3f seconds", FPSeconds(endtime - starttime).count());
  }
  return job;
}  // int MemX::send_frame(AVFrame *frame)

const nlohmann::json MemX::receive_detections(Job *job, float object_threshold) {
  nlohmann::json coco_object;
  coco_object = convert_predictions_to_coco_format(job->predictions_buffer, job->m_width_rescale, job->m_height_rescale, object_threshold);
  return coco_object;
}

nlohmann::json MemX::convert_predictions_to_coco_format(
    const std::vector<float>& predictions,
    float m_width_rescale,
    float m_height_rescale,
    float object_threshold
    ) {
  const int num_predictions = predictions.size() / 6;
  nlohmann::json coco_predictions = nlohmann::json::array();

  for (int i = 0; i < num_predictions; i++) {
    float l = predictions[i * 6 + 0];
    float t = predictions[i * 6 + 1];
    float r = predictions[i * 6 + 2];
    float b = predictions[i * 6 + 3];
    int class_id = static_cast<int>(predictions[i * 6 + 4]);
    float score = predictions[i * 6 + 5];

    // if score < m_conf_threshold we break as the predictions
    // are sorted by score
    if (score < object_threshold) {
      break;
    }

    // coordinates must be scaled to true image dimensions
    l = l / m_width_rescale;
    t = t / m_height_rescale;
    r = r / m_width_rescale;
    b = b / m_height_rescale;

    // cv::rectangle() requires LTRB format
    l = std::round(l);
    t = std::round(t);
    r = std::round(r);
    b = std::round(b);

    std::array<float, 4> bbox = {l, t, r, b};

    // map class_id to class name
    std::string class_name = coco_classes[class_id];

    coco_predictions.push_back({{"class_name", class_name}, {"bbox", bbox}, {"score", score}});
  }
  return coco_predictions;
}

#endif
