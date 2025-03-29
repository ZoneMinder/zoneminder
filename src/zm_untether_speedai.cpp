//
// ZoneMinder Untether::SpeedAI Class Implementation
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

#include "zm_untether_speedai.h"

#include <cstring>
#include <algorithm>
#include <filesystem>
#include <iostream>
#include <memory>
#include <random>
#include <vector>


static const char * coco_classes[] = {
  "person", "bicycle", "car", "motorcycle", "airplane", "bus", "train", "truck", "boat",
  "traffic light", "fire hydrant", "stop sign", "parking meter", "bench", "bird", "cat",
  "dog", "horse", "sheep", "cow", "elephant", "bear", "zebra", "giraffe", "backpack",
  "umbrella", "handbag", "tie", "suitcase", "frisbee", "skis", "snowboard", "sports ball",
  "kite", "baseball bat", "baseball glove", "skateboard", "surfboard", "tennis racket",
  "bottle", "wine glass", "cup", "fork", "knife", "spoon", "bowl", "banana", "apple",
  "sandwich", "orange", "broccoli", "carrot", "hot dog", "pizza", "donut", "cake", "chair",
  "couch", "potted plant", "bed", "dining table", "toilet", "tv", "laptop", "mouse", "remote",
  "keyboard", "cell phone", "microwave", "oven", "toaster", "sink", "refrigerator", "book",
  "clock", "vase", "scissors", "teddy bear", "hair drier", "toothbrush"
};

#define USE_THREAD 0
#define USE_LOCK 1

#ifdef HAVE_UNTETHER_H
SpeedAI::SpeedAI() :
  module_(nullptr),
  terminate_(false),
  batchSize(0),
  inSize(0),
  outSize(0),
  infos(nullptr),
  count(0),
  quadra(nullptr),
  drawbox_filter(nullptr),
  drawbox_filter_ctx(nullptr)
{
  image_size = av_image_get_buffer_size(AV_PIX_FMT_RGB24, MODEL_WIDTH, MODEL_HEIGHT, 32);

  // Populate mapping with all unsorted quantVal/floatVal pairs
  for (int quantVal=0; quantVal < 256; quantVal++) {
	  float floatVal = dequantize((uint8_t)quantVal, quantization_fp8p_bias);
	  m_quant_bounds[quantVal] = std::make_pair(quantVal, floatVal);
  }
  // Sort all pairs according to float value
  std::sort(m_quant_bounds.begin(), m_quant_bounds.end(), comparator);

  // Convert float values to upper bounds of associated bins
  for (size_t i=0; i < m_quant_bounds.size() - 1; i++) {
	  m_quant_bounds[i].second = (m_quant_bounds[i].second + m_quant_bounds[i + 1].second) / 2;
  }
  // Last value's upper bin boundary gets mapped to infinity
  m_quant_bounds[m_quant_bounds.size() - 1].second = std::numeric_limits<float>::max();
  for (int imgPixelVal=0; imgPixelVal <= 255; imgPixelVal++) {
	  m_fast_map[imgPixelVal] = quantize(static_cast<float>(imgPixelVal));
  }

  m_out_buf.resize(NUM_NMS_PREDICTIONS*6);
  outputBuffer = m_out_buf.data();
#if USE_THREAD
  thread_ = std::thread(&SpeedAI::Run, this);
#endif
}

SpeedAI::~SpeedAI() {
  terminate_ = true;
  if (thread_.joinable()) thread_.join();
  while (jobs.size()) {
    delete jobs.front();
    jobs.pop_front();
  }
  // Clean up
  if (module_) {
    Debug(1, "Freeing module_");
    uai_module_free(module_);
  }
  //av_frame_unref(&scaled_frame);
  if (infos) {
    delete [] infos;
    infos = nullptr;
  }
  if (drawbox_filter) {
    avfilter_graph_free(&drawbox_filter->filter_graph);
    delete drawbox_filter;
    drawbox_filter = nullptr;
  }
}

bool SpeedAI::setup(
    const std::string &model_type,
    const std::string &model_file
    ) {
  // Load and launch module_
  Debug(1, "SpeedAI: Loading model %s", model_file.c_str());
  UaiErr err = uai_module_load(model_file.c_str(), &module_);
  if (err != UAI_SUCCESS) {
    Error("Failed loading model %s", uai_err_string(err));
    return false;
  }
  Debug(1, "SpeedAI: launching");
  err = uai_module_launch(module_);
  if (err != UAI_SUCCESS) {
    Error("Failed launching model %s", uai_err_string(err));
    return false;
  }
  // Get info on input/output streams. We assume a simple model (like Resnet50) with one input
  // stream and one output stream from here onwards. To see how larger input/output sizes are
  // handled, please refer to the other demos.
  size_t numStreams;
  err = uai_module_get_num_streams(module_, &numStreams);
  if (err != UAI_SUCCESS) {
    Error("Failed getting num streams %s", uai_err_string(err));
    return false;
  }
  Debug(1, "Num streams %zu", numStreams);
  infos = new UaiDataStreamInfo[numStreams];
  uai_module_get_stream_info(module_, infos, numStreams);
  //assert(infos[0].io_type == UAI_DATA_STREAM_HOST_TO_DEVICE);
  //assert(infos[1].io_type == UAI_DATA_STREAM_DEVICE_TO_HOST);
  // Allocate input and output buffers, and attach them to the module_. There is a size limitation
  // that applies to the IO buffers and in the most general case one may have to utilize mutiple
  // buffers per stream. Here we just assert that one buffer per input/output stream is sufficient
  // for one whole batch of IO data.
  batchSize = 1; // only 1 issupported
  inSize = infos[0].framesize_hint * batchSize;
  outSize = infos[1].framesize_hint * batchSize;
  Debug(1, "inSize %zu outSize %zu, max %d", inSize, outSize, UAI_MODULE_MAX_DATA_BUFFER_SIZE);
  Debug(1, "SpeedAI inSize hint inname %s outname %s", infos[0].name, infos[1].name);
  return true;
}

void SpeedAI::Run() {
  while (!(terminate_ or zm_terminate)) {
    std::unique_lock<std::mutex> lck(mutex_);
    while (send_queue.size()) {
      Job *job = send_queue.front();
      send_queue.pop_front();
      Debug(3, "SpeedAI::enqueue");
      // Enqueue event, inference will start asynchronously.
      UaiErr err = uai_module_enqueue(module_, &job->event);
      if (err != UAI_SUCCESS) {
        Error("Failed enqueue %s", uai_err_string(err));
      } else {
        Debug(3, "SpeedAI:: success enqueue");
      }
    }
  }
}

SpeedAI::Job * SpeedAI::send_image(Job *job, Image *image) {
  av_frame_ptr frame = av_frame_ptr(av_frame_alloc());
  image->PopulateFrame(frame.get());
  return send_frame(job, frame.get());
}

SpeedAI::Job * SpeedAI::send_packet(Job *job, std::shared_ptr<ZMPacket> packet) {
  AVFrame *avframe = packet->in_frame.get();
  if (!avframe) {
    Error("NO inframe in packet %d, out of mem?", packet->image_index);
    return nullptr;
  }
  return send_frame(job, avframe);
}

SpeedAI::Job * SpeedAI::get_job() {
  Job *job = new Job(module_);
  if (av_frame_get_buffer(job->scaled_frame.get(), 32)) {
    Error("cannot allocate scaled frame buffer");
    return nullptr;
  }

  std::unique_lock<std::mutex> lck(mutex_);

  // TODO, use the inputBuf as the scaled_frame data to avoid a copy
  if (UAI_SUCCESS != uai_module_data_buffer_attach(module_, job->inputBuf, infos[0].name, inSize)) {
    Error("Failed attaching inputbuf");
    return nullptr;
  }
  if (UAI_SUCCESS != uai_module_data_buffer_attach(module_, job->outputBuf, infos[1].name, outSize)) {
    Error("Failed attaching outputbuf");
    return nullptr;
  }
  jobs.push_back(job);
  return job;
}

SpeedAI::Job * SpeedAI::send_frame(Job *job, AVFrame *avframe) {
  count++;
  Debug(1, "SpeedAI::detect %d", count);

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

  // Fill input buffer with data, applying quantization on the fly via this->operator()(uint8_t)
  uint8_t* uint8Pixel = job->scaled_frame->buf[0]->data;
  auto totalPixels = image_size;
  auto* uai_data = job->inputBuf;

  size_t datInd = 0;
  while (uai_data != nullptr) {
	  auto bufSize = uai_data->size;
	  uint8_t* buf = static_cast<uint8_t*>(uai_data->buffer);

	  // Calculate the number of pixels we can safely process
	  size_t numPixelsToProcess = std::min(bufSize, totalPixels - datInd);

	  // Process pixels without the if statement
	  for (size_t k = 0; k < numPixelsToProcess; ++k) {
		  buf[k] = m_fast_map[uint8Pixel[k + datInd]];
	  }

	  datInd += bufSize;
	  uai_data = uai_data->next_buffer;
  }

  // Attach buffers to event, so runtime knows where to find input and output buffers to
  // read/write data. All buffers are chained together linearly. Here we again assume that we only
  // have one input stream and one output stream, and that one buffer per stream is big enough to
  // hold all data for one batch.
  job->event.buffers = job->inputBuf;
  job->inputBuf->next_buffer = job->outputBuf;

#if USE_LOCK
  SystemTimePoint starttime = std::chrono::system_clock::now();
  std::unique_lock<std::mutex> lck(mutex_);
#endif

#if USE_THREAD
  send_queue.push_back(job);
#else
  SystemTimePoint endtime = std::chrono::system_clock::now();
  if (endtime - starttime > Milliseconds(30)) {
    Warning("SpeedAI locking is too slow: %.3f seconds", FPSeconds(endtime - starttime).count());
  } else {
    Debug(3, "SpeedAI locking took: %.3f seconds", FPSeconds(endtime - starttime).count());
  }
  starttime = endtime;
  // Enqueue event, inference will start asynchronously.
  UaiErr err = uai_module_enqueue(module_, &job->event);
  if (err != UAI_SUCCESS) {
    Error("Failed enqueue %s", uai_err_string(err));
    return nullptr;
  }
  endtime = std::chrono::system_clock::now();
  if (endtime - starttime > Milliseconds(30)) {
    Warning("SpeedAI enqueue is too slow: %.3f seconds", FPSeconds(endtime - starttime).count());
  } else {
    Debug(3, "SpeedAI enqueue took: %.3f seconds", FPSeconds(endtime - starttime).count());
  }
#endif
  return job;
}  // int SpeedAI::send_frame(AVFrame *frame)

const nlohmann::json SpeedAI::receive_detections(Job *job, float object_threshold) {
  nlohmann::json coco_object;

  // Block execution until the inference job associate to our event has finished. Alternatively,
  // we could repeatedly poll the status of the job using `uai_module_wait`.
  //Debug(3, "Wait input %p output %p", job->inputBuf->buffer, job->outputBuf->buffer);
  SystemTimePoint starttime = std::chrono::system_clock::now();
#if 0
  UaiErr err;
  while (!zm_terminate) {
    err = uai_module_wait(module_, &job->event, 10);
    if (err != UAI_SUCCESS) {
      Debug(1, "SpeedAI Failed wait %d, %s", err, uai_err_string(err));
    } else {
      break;
    }
  }
#else
  UaiErr err = uai_module_synchronize(module_, &job->event);
  //UaiErr err = uai_module_wait(module_, &job->event, 10);
  if (err != UAI_SUCCESS) {
    Warning("SpeedAI Failed wait %d, %s", err, uai_err_string(err));
    return coco_object;
  }
#endif
  SystemTimePoint endtime = std::chrono::system_clock::now();
  if (endtime - starttime > Milliseconds(30)) {
    Warning("receive_detections is too slow: %.3f seconds", FPSeconds(endtime - starttime).count());
  } else {
    Debug(1, "receive_detections took: %.3f seconds", FPSeconds(endtime - starttime).count());
  }

  // Now print out the result of the inference job. Note again that the designated memory address
  // on the host side is UaiDataBuffer::buffer.
  auto DMAoutput = static_cast<uint8_t*>(job->outputBuf->buffer);
  if (!DMAoutput) {
    Error("No DMAOutput");
    return coco_object;
  }
  // Output buffer for one batch of images

  int m_uint16_bias = dequantization_uint16_bias = 4;
  int m_fp8p_bias = dequantization_fp8p_bias = -12;
  //std::vector<std::vector<int>> m_index_map;

  uint8_t l_low, l_top, t_low, t_top, r_low, r_top, b_low, b_top, score;

  for (int row = 0; row < 256; row++) {
    // l_low = *(DMAoutput + m_index_map[row][0]);
    // l_top = *(DMAoutput + m_index_map[row][1]);
    // t_low = *(DMAoutput + m_index_map[row][2]);
    // t_top = *(DMAoutput + m_index_map[row][3]);
    // r_low = *(DMAoutput + m_index_map[row][4]);
    // r_top = *(DMAoutput + m_index_map[row][5]);
    // b_low = *(DMAoutput + m_index_map[row][6]);
    // b_top = *(DMAoutput + m_index_map[row][7]);

    //int outputDMAIndex = row * 64; // Each row has 6 values to store as per NMS struct
                                   // BUT IS PADDED TO 64 BYTES
    l_low = *DMAoutput; DMAoutput++;
    l_top = *DMAoutput; DMAoutput++;
    t_low = *DMAoutput; DMAoutput++;
    t_top = *DMAoutput; DMAoutput++;
    r_low = *DMAoutput; DMAoutput++;
    r_top = *DMAoutput; DMAoutput++;
    b_low = *DMAoutput; DMAoutput++;
    b_top = *DMAoutput; DMAoutput++;
    float  object_class = static_cast<float>(*(DMAoutput)); DMAoutput++;
    score = *DMAoutput; DMAoutput++;
    DMAoutput += 54;

    // Combine the uint8_t pairs into uint16_t values
    uint16_t l = (static_cast<uint16_t>(l_top) << 8) | l_low;
    uint16_t t = (static_cast<uint16_t>(t_top) << 8) | t_low;
    uint16_t r = (static_cast<uint16_t>(r_top) << 8) | r_low;
    uint16_t b = (static_cast<uint16_t>(b_top) << 8) | b_low;

    // dequantize to float
    float l_float = static_cast<float>(l) / std::pow(2, m_uint16_bias); // replace with coord bias == 2
    float t_float = static_cast<float>(t) / std::pow(2, m_uint16_bias);
    float r_float = static_cast<float>(r) / std::pow(2, m_uint16_bias);
    float b_float = static_cast<float>(b) / std::pow(2, m_uint16_bias);
    float score_float = static_cast<float>(dequantize(score, m_fp8p_bias));
    int outputIndex = row * 6; // Assuming each row has 6 values to store

    // Insert the values into the output buffer
    outputBuffer[outputIndex] = l_float; outputIndex++;
    outputBuffer[outputIndex] = t_float; outputIndex++;
    outputBuffer[outputIndex] = r_float; outputIndex++;
    outputBuffer[outputIndex] = b_float; outputIndex++;
    outputBuffer[outputIndex] = object_class; outputIndex++;
    outputBuffer[outputIndex] = score_float; outputIndex++;
  }
  Debug(3, "Done dequantizing");
  coco_object = convert_predictions_to_coco_format(m_out_buf, job->m_width_rescale, job->m_height_rescale, object_threshold);
  Debug(3, "Done convert to coco");
  return coco_object;
}

uint8_t SpeedAI::quantize(float val) const {
	// There are two FP8 representations of zero (+- 0.0) and the search below would pick up the
	// negative one. To stay consistent with the rest of the stack, we want +0.0 though, encoded
	// as uint8_t(0);
	if (val == 0) return 0;
	std::pair<uint8_t, float> dummyQuantPair = std::make_pair(0, val);
	// lower_bound returns fist elem such that (elem<val).
	// Implies (elem>=val) implies (upper bound > val) implies (val in bin)
	return std::lower_bound(m_quant_bounds.begin(), m_quant_bounds.end(), dummyQuantPair, comparator)->first;
}

float SpeedAI::dequantize(uint8_t val, int bias) {
  // Bitmasks for projecting out mantissa, exponent, sign
  static constexpr uint8_t mntmask = 15;  // 0b00001111
  static constexpr uint8_t expmask = 112; // 0b01110000
  static constexpr uint8_t sgnmask = 128; // 0b10000000
                                          // Apply masks and shift to decode components
  uint8_t mnt = val & mntmask;
  uint8_t exp = (val & expmask) >> 4;
  const float sgn = (val & sgnmask) ? -1.0 : +1.0;
  // Leading digit in mantissa is implicit, unless zero exponent
  mnt = exp ? (16 | mnt) : mnt;
  exp = exp ? exp : 1;
  // Calculate and return value
  return sgn * float(mnt) * pow(2, (int)exp + bias);
}

nlohmann::json SpeedAI::convert_predictions_to_coco_format(const std::vector<float>& predictions, float m_width_rescale, float m_height_rescale, float object_threshold) {
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
