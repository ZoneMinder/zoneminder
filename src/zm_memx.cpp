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
#define BATCH_SIZE 1

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
  model_id(0),
  input_port(0),
  output_port(0),
  obj_thresh(0.25),
  nms_thresh(0.45),
  terminate_(false),
  batchSize(0),
  inSize(0),
  outSize(0),
  count(0)
{
}

MemX::~MemX() {
  terminate_ = true;
  if (thread_.joinable()) thread_.join();
  while (jobs.size()) {
    delete jobs.front();
    jobs.pop_front();
  }
  memx_close(model_id);
}

bool MemX::setup(
    const std::string &model_type,
    const std::string &model_file
    ) {
  // Load and launch module_
  Debug(1, "MemX: Loading model %s", model_file.c_str());

  //std::vector<int> device_ids = {0};
  //std::array<bool, 2> use_model_shape = {false, false};

  uint8_t model_id = 0;
  // Create the accelerator object and load the DFP model
  // bind MPU device group 0 as MX3 to model 0
  memx_status status = memx_open(model_id, 0, MEMX_DEVICE_CASCADE_PLUS);
  if (status == 0) {
    Debug(1, "Success opening MX3");
  } else {
    Error("Failure openping MX3");
    return false;
  }

  // download weight memory and 1st model within DFP file to device
  status = memx_download_model(model_id, model_file.c_str(), 0, MEMX_DOWNLOAD_TYPE_WTMEM_AND_MODEL);

  if (status == 0) {
    // read back flow 0 (port 0) input feature map shape for debug information
    memx_get_ifmap_size(0, 0, &input_height, &input_width, &input_depth, &input_channels, &input_format);
    // for example: shape = (224, 224, 1, 3), format = 1
    Debug(1, "ifmap shape = (%d, %d, %d, %d), format = %d", input_height, input_width, input_depth, input_channels, input_format);
    memx_get_ofmap_size(0, 0, &output_height, &output_width, &output_depth, &output_channels, &output_format);
    Debug(1, "ofmap shape = (%d, %d, %d, %d), format = %d", output_height, output_width, output_depth, output_channels, output_format);
    image_size = av_image_get_buffer_size(AV_PIX_FMT_RGB24, input_width, input_height, 0);
  }
   // Set confidence threshold and process detection results
   yolov8_handle = std::make_unique<YOLOv8>(input_width, input_height, input_channels, 0, 0);
   yolov8_handle->SetConfidenceThreshold(obj_thresh);
#if USE_THREAD
  thread_ = std::thread(&MemX::Run, this);
#endif
  return true;
}

void MemX::Run() {

  memx_status status = memx_set_stream_enable(model_id, 0);

  while (!(terminate_ or zm_terminate)) {

    Job *job = nullptr;
    {
      Debug(1, "MemX locking, queue size %zu", send_queue.size());
      std::unique_lock<std::mutex> lck(mutex_);
      while (send_queue.size() < BATCH_SIZE and ! zm_terminate) {
        Debug(1, "MemX waiting, queue size %zu", send_queue.size());
        condition_.wait(lck);
      }
      if (send_queue.size() >= BATCH_SIZE) {
        Debug(1, " batch");
        job = send_queue.front();
        send_queue.pop_front();
      } else {
        Debug(1, "Not batch");
      }
    }  // end scope for lock

    if (job) {
      job->lock();
      inference(job);

      Debug(1, "notifying");
      job->notify();
      Debug(1, "unlocking");
      job->unlock();
      Debug(1, "Done notifying");
      job = nullptr;
    } // end if job
  }  // end while forever

  status = memx_set_stream_disable(model_id, 0);
}

#if 0
// Function to process model output and get bounding boxes
nlohmann::json MemX::get_detections(float *ofmap, int num_boxes, const cv::Mat &inframe,
    std::vector<Box> &filtered_boxes,
    std::vector<std::vector<float>> &filtered_mask_coefs)
{

  nlohmann::json coco_predictions = nlohmann::json::array();

  std::vector<Box> all_boxes;
  std::vector<float> all_scores;
  std::vector<std::vector<float>> mask_coefs;

  // Iterate through the model outputs
  for (int i = 0; i < num_boxes; ++i)
  {
    float *feature_ptr = ofmap_t_ptr + i * num_features;

    // get best class information
    float confidence;
    int class_id;
    get_best_class_info(feature_ptr, confidence, class_id);

    if (confidence > conf_thresh)
    {
      // Extract and scale the bounding box coordinates
      float x0 = feature_ptr[0];
      float y0 = feature_ptr[1];
      float w = feature_ptr[2];
      float h = feature_ptr[3];
      // Convert boxes from center format (cxcywh) to corner format (xyxy)
      int x1 = static_cast<int>(x0 - 0.5f * w);
      int y1 = static_cast<int>(y0 - 0.5f * h);
      int x2 = x1 + w;
      int y2 = y1 + h;

      // Rescale boxes to original image size
      x1 = (x1 - pad_w) / scale_ratio;
      x2 = (x2 - pad_w) / scale_ratio;
      y1 = (y1 - pad_h) / scale_ratio;
      y2 = (y2 - pad_h) / scale_ratio;

      // Clamp box boundaries to image dimensions
      x1 = std::max(0, std::min(x1, ori_image_width - 1));
      x2 = std::max(0, std::min(x2, ori_image_width - 1));
      y1 = std::max(0, std::min(y1, ori_image_height - 1));
      y2 = std::max(0, std::min(y2, ori_image_height - 1));

      // Add detected box to the list
      all_boxes.emplace_back(x1, y1, x2, y2, class_id, confidence);
      all_scores.emplace_back(confidence);
      cv_boxes.emplace_back(x1, y1, x2 - x1, y2 - y1);

      // Add detected mask coefficient to the list
      std::vector<float> mask_coef(feature_ptr + 4 + num_class, feature_ptr + num_features);
      mask_coefs.push_back(mask_coef);
    }
  }
  o // Apply Non-Maximum Suppression (NMS) to remove overlapping boxes
    std::vector<int> nms_result;
  if (!cv_boxes.empty()) {
    cv::dnn::NMSBoxes(cv_boxes, all_scores, conf_thresh, nms_thresh, nms_result);
    for (int idx : nms_result)
    {
      filtered_boxes.push_back(all_boxes[idx]);
      filtered_mask_coefs.push_back(mask_coefs[idx]);
    }
  }
}

#endif



memx_status MemX::inference(Job *job) {
  const int timeout = 200; // 200 ms
  Debug(3, "MemX::enqueue");
  SystemTimePoint starttime = std::chrono::system_clock::now();
  memx_status status = memx_stream_ifmap(model_id, input_port, static_cast<void *>(job->ifmap.data()), timeout);

  if (status) {
    Error("Failed enqueue %d", status);
    return status;
  } else {
    SystemTimePoint endtime = std::chrono::system_clock::now();
    if (endtime - starttime > Milliseconds(40)) {
      Warning("MemX enqueue is too slow: %.3f seconds", FPSeconds(endtime - starttime).count());
    } else {
      Debug(3, "MemX enqueue took: %.3f seconds", FPSeconds(endtime - starttime).count());
    }
  }

  // 3. Read output feature map from device after inference
  if (0 == status) {
    Debug(1, "Reading ofmap");
    status = memx_stream_ofmap(model_id, output_port, static_cast<void *>(job->ofmap.data()), timeout);
    Debug(1, "Status from reading ofmap %d", status);
  }
  return status;
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
  Job *job = new Job();
  AVFrame *frame = job->scaled_frame.get();

  job->scaled_frame->width = input_width;
  job->scaled_frame->height = input_height;
  job->scaled_frame->format = AV_PIX_FMT_RGB24;

  Debug(1, "sw scale: scaled frame data 0x%lx, linesize %d/%d/%d/%d, height %d",
      (unsigned long)frame->data, frame->linesize[0], frame->linesize[1],
      frame->linesize[2], frame->linesize[3], frame->height);

#if 1
  if (av_frame_get_buffer(job->scaled_frame.get(), 32)) {
    Error("cannot allocate scaled frame buffer");
    return nullptr;
  }
#endif

  job->ifmap.resize(input_width*input_width*input_depth*input_channels);
  job->ofmap.resize(output_width*output_width*output_depth*output_channels);

#if 0
  AVBufferRef *ref = av_buffer_create(reinterpret_cast<uint8_t *>(&job->ifmap),
      input_width*input_width*input_depth*input_channels,
      dont_free, /* Free callback */
      nullptr, /* opaque */
      0 /* flags */
      );
  if (!ref) {
    Warning("Failed to create av_buffer");
  }
  job->scaled_frame->buf[0] = ref;

  int rc_size = av_image_fill_arrays(
                  job->scaled_frame->data, job->scaled_frame->linesize,
                  reinterpret_cast<uint8_t *>(&job->ifmap), AV_PIX_FMT_RGB24, input_width, input_height,
                  0 //alignment
                );
  if (rc_size < 0) {
    Error("Problem setting up data pointers into image %s", av_make_error_string(rc_size).c_str());
  } else {
    Debug(1, "Image.Populate frame rc/size %d %dx%d", rc_size, job->scaled_frame->width, job->scaled_frame->height);
  }
#endif

  std::unique_lock<std::mutex> lck(mutex_);
  jobs.push_back(job);
  return job;
}  // end MemX::Job * MemX::get_job() 

MemX::Job * MemX::send_frame(Job *job, AVFrame *avframe) {
  count++;
  Debug(1, "MemX::send_frame %d", count);

  SystemTimePoint starttime = std::chrono::system_clock::now();

  job->sw_scale_ctx = sws_getCachedContext(job->sw_scale_ctx,
        avframe->width, avframe->height, static_cast<AVPixelFormat>(avframe->format),
        input_width, input_height, AV_PIX_FMT_RGB24,
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
  job->m_width_rescale = ((float)input_width / (float)avframe->width);
  job->m_height_rescale = ((float)input_height / (float)avframe->height);

  std::vector<float *> accl_input_data;
  accl_input_data.resize(input_width*input_height*input_depth*input_channels);

#if 0
  yolov8_handle->PreProcess(job->scaled_frame->data[0], input_width, input_height, accl_input_data);

#else
  int offset = 0; //padding_height_ * input_width_ * 3;
  float *buffer_ptr = job->ifmap.data() + offset; // YOLOv8 has 1 input
  uint8_t *input_buffer_ptr = job->scaled_frame->data[0]; 

  for (int row = 0; row < input_height; ++row) {
    for (int col = 0; col < input_width; ++col) {
#pragma omp simd
      for(int i=0; i < 3; i++) {
        buffer_ptr[0] = input_buffer_ptr[0] / (float)255.0; // red
        buffer_ptr[1] = input_buffer_ptr[1] / (float)255.0; // green
        buffer_ptr[2] = input_buffer_ptr[2] / (float)255.0; // blue
      }
      buffer_ptr += 3;
      input_buffer_ptr += 3;
    }
  }
#endif

#if 1
  inference(job);
  YOLOv8Result result;
  //yolov8_handle->PostProcess(job->ofmap, result);
#else
  // Prevents processing? 
  job->lock();
  //std::unique_lock<std::mutex> job_lck(job->mutex_);
  {
    Debug(1, "Locking");
    std::unique_lock<std::mutex> lck(mutex_);
    send_queue.push_back(job);
    condition_.notify_all();
  }
  Debug(1, "Waiting for inference");
  job->wait();
  Debug(1, "Done Waiting");
#endif
  starttime = std::chrono::system_clock::now();

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
  coco_object = convert_predictions_to_coco_format(job->ofmap, job->m_width_rescale, job->m_height_rescale, object_threshold);
  return coco_object;
}

#if 0
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

#endif
