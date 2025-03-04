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

#ifdef HAVE_UNTETHER_H
SpeedAI::SpeedAI(Monitor *monitor_) :
  monitor(monitor_),
  module(nullptr),
  //inputBuf({}),
  //outputBuf({}),
  batchSize(0),
  inSize(0),
  outSize(0),
  //scaled_frame({}),
  sw_scale_ctx(nullptr),
  //image_size(0)
  drawbox_filter(nullptr),
  drawbox_filter_ctx(nullptr)
{
  image_size = av_image_get_buffer_size(AV_PIX_FMT_RGB24, MODEL_WIDTH, MODEL_HEIGHT, 32);

  // Populate mapping with all unsorted quantVal/floatVal pairs
  for (int quantVal(0); quantVal < 256; quantVal++) {
	  float floatVal = dequantize((uint8_t)quantVal, m_bias);
	  m_quant_bounds[quantVal] = std::make_pair(quantVal, floatVal);
  }
  // Sort all pairs according to float value
  std::sort(m_quant_bounds.begin(), m_quant_bounds.end(), comparator);

  // Convert float values to upper bounds of associated bins
  for (size_t i(0); i < m_quant_bounds.size() - 1; i++) {
	  m_quant_bounds[i].second = (m_quant_bounds[i].second + m_quant_bounds[i + 1].second) / 2;
  }
  // Last value's upper bin boundary gets mapped to infinity
  m_quant_bounds[m_quant_bounds.size() - 1].second = std::numeric_limits<float>::max();
  for (int imgPixelVal(0); imgPixelVal <= 255; imgPixelVal++) {
	  m_fast_map[imgPixelVal] = quantize(static_cast<float>(imgPixelVal));
  }

}

SpeedAI::~SpeedAI() {
  // Clean up
  if (module) {
    Debug(1, "Freeing module");
    uai_module_free(module);
  }
  //av_frame_unref(&scaled_frame);
  if (sw_scale_ctx) {
    Debug(1, "Freeing sw_scale_Ctx");
    sws_freeContext(sw_scale_ctx);
  }
  if (infos) {
    delete infos;
    infos = nullptr;
  }
  if (drawbox_filter) {
    avfilter_graph_free(&drawbox_filter->filter_graph);
    free(drawbox_filter);
  }

}

bool SpeedAI::setup(
    const std::string &model_type,
    const std::string &model_file
    ) {
  // Load and launch module
  Debug(1, "SpeedAI: Loading model %s", model_file.c_str());
  UaiErr err = uai_module_load(model_file.c_str(), &module);
  if (err != UAI_SUCCESS) {
    Error("Failed loading model %s", uai_err_string(err));
    //return false;
  }
  Debug(1, "SpeedAI: launching");
  err = uai_module_launch(module);
  if (err != UAI_SUCCESS) {
    Error("Failed launching model %s", uai_err_string(err));
    return false;
  }
  // Get info on input/output streams. We assume a simple model (like Resnet50) with one input
  // stream and one output stream from here onwards. To see how larger input/output sizes are
  // handled, please refer to the other demos.
  size_t numStreams;
  err = uai_module_get_num_streams(module, &numStreams);
  if (err != UAI_SUCCESS) {
    Error("Failed getting num streams %s", uai_err_string(err));
    return false;
  }
  Debug(1, "Num streams %zu", numStreams);
  infos = new UaiDataStreamInfo[numStreams];
  uai_module_get_stream_info(module, infos, numStreams);
  assert(infos[0].io_type == UAI_DATA_STREAM_HOST_TO_DEVICE);
  assert(infos[1].io_type == UAI_DATA_STREAM_DEVICE_TO_HOST);
  // Allocate input and output buffers, and attach them to the module. There is a size limitation
  // that applies to the IO buffers and in the most general case one may have to utilize mutiple
  // buffers per stream. Here we just assert that one buffer per input/output stream is sufficient
  // for one whole batch of IO data.
  batchSize = 1; // only 1 issupported
  inSize = infos[0].framesize_hint * batchSize;
  outSize = infos[1].framesize_hint * batchSize;
  Debug(1, "inSize %zu outSize %zu, max %d", inSize, outSize, UAI_MODULE_MAX_DATA_BUFFER_SIZE);
  Debug(1, "SpeedAI inSize hint inname %s outname %s", infos[0].name, infos[1].name);

  int ret;
  Quadra_Yolo *quadra = monitor->getQuadra();
  if (quadra/*draw_box*/) {
    drawbox_filter = (Quadra_Yolo::filter_worker*)malloc(sizeof(Quadra_Yolo::filter_worker));
    drawbox_filter->buffersink_ctx = nullptr;
    drawbox_filter->buffersrc_ctx = nullptr;
    drawbox_filter->filter_graph = nullptr;
    if ((ret = quadra->init_filter("drawbox", drawbox_filter, false)) < 0) {
      Error("cannot initialize drawbox filter");
      return false;
    }

    for (unsigned int i = 0; i < drawbox_filter->filter_graph->nb_filters; i++) {
      if (strstr(drawbox_filter->filter_graph->filters[i]->name, "drawbox") != nullptr) {
        drawbox_filter_ctx = drawbox_filter->filter_graph->filters[i];
        break;
      }
    }

    if (drawbox_filter_ctx == nullptr) {
      Error( "cannot find valid drawbox filter");
      return false;
    }
  } // end if draw_box

  return true;
}

int SpeedAI::send_image(std::shared_ptr<ZMPacket> packet) {
  AVFrame *avframe = packet->in_frame.get();
  if (!avframe) {
    Error("NO inframe in packet %d, out of mem?", packet->image_index);
    return -1;
  }

  Debug(1, "SpeedAI::detect");
  // Resize, change to RGB, maybe quantize
  //Image processed_image = preprocess(image);

  if (!sw_scale_ctx) {
    /*
    scaled_frame.width = MODEL_WIDTH;
    scaled_frame.height = MODEL_HEIGHT;
    scaled_frame.format = AV_PIX_FMT_RGB24;
    if (av_frame_get_buffer(&scaled_frame, 32)) {
      Error("cannot allocate scaled frame buffer");
      return -1;
    }

    */
    sw_scale_ctx = sws_getContext(
        avframe->width, avframe->height, static_cast<AVPixelFormat>(avframe->format),
        MODEL_WIDTH, MODEL_HEIGHT, AV_PIX_FMT_RGB24,
        //scaled_frame.width, scaled_frame.height, static_cast<AVPixelFormat>(scaled_frame.format),
        SWS_BICUBIC, nullptr, nullptr, nullptr);
    if (!sw_scale_ctx) {
      Error("cannot create sw scale context");
      return -1;
    }
  }
  Job job(module, avframe);

  if (av_frame_get_buffer(job.scaled_frame, 32)) {
    Error("cannot allocate scaled frame buffer");
    //delete job;
    return -1;
  }
  int ret = sws_scale(sw_scale_ctx, (const uint8_t * const *)avframe->data,
      avframe->linesize, 0, avframe->height, job.scaled_frame->data, job.scaled_frame->linesize);
  if (ret < 0) {
    Error("cannot do sw scale: inframe data 0x%lx, linesize %d/%d/%d/%d, height %d to %d linesize",
        (unsigned long)avframe->data, avframe->linesize[0], avframe->linesize[1],
        avframe->linesize[2], avframe->linesize[3], avframe->height, job.scaled_frame->linesize[0]);
    return ret;
  }

  // TODO, use the inputBuf as the scaled_frame data to avoid a copy
  if (UAI_SUCCESS != uai_module_data_buffer_attach(module, job.inputBuf, infos[0].name, inSize)) {
    Error("Failed attaching inputbuf");
    return -1;
  }
  if (UAI_SUCCESS != uai_module_data_buffer_attach(module, job.outputBuf, infos[1].name, outSize)) {
    Error("Failed attaching outputbuf");
    return -1;
  }

  // Fill input buffer with data, applying quantization on the fly via this->operator()(uint8_t)
  uint8_t* uint8Pixel = job.scaled_frame->buf[0]->data;
  auto totalPixels = image_size;
  auto* uai_data = job.inputBuf;

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

  //memcpy(job.inputBuf->buffer, job.scaled_frame->buf[0], image_size);

  Debug(1, "SpeedAI input %p output %p size %d", job.inputBuf->buffer, job.outputBuf->buffer, image_size);
  // Attach buffers to event, so runtime knows where to find input and output buffers to
  // read/write data. All buffers are chained together linearly. Here we again assume that we only
  // have one input stream and one output stream, and that one buffer per stream is big enough to
  // hold all data for one batch.
  job.event.buffers = job.inputBuf;
  job.inputBuf->next_buffer = job.outputBuf;

  Debug(1, "SpeedAI::enqueue");
  // Enqueue event, inference will start asynchronously.
  UaiErr err = uai_module_enqueue(module, &job.event);
  if (err != UAI_SUCCESS) {
    Error("Failed enqueue %s", uai_err_string(err));
    return -1;
  }
  jobs.push_back(std::move(job));
  return 1;
}  // int SpeedAI::send_image(std::shared_ptr<ZMPacket> packet)

int SpeedAI::receive_detections(std::shared_ptr<ZMPacket> packet) {
  if (!jobs.size()) {
    Error("SpeedAI No jobs in receive_detections");
    return 0;
  }

  Debug(1, "SpeedAI::wait");
  // Block execution until the inference job associate to our event has finished. Alternatively,
  // we could repeatedly poll the status of the job using `uai_module_wait`.
  Job *job = &jobs.front();
  UaiErr err = uai_module_wait(module, &job->event, 0);
  if (err != UAI_SUCCESS) {
    Debug(1, "SpeedAI Failed wait %s", uai_err_string(err));
    return 0;
  }
  Debug(1, "SpeedAI Completed inference, wait return code is %s", uai_err_string(err));

  // Now print out the result of the inference job. Note again that the designated memory address
  // on the host side is UaiDataBuffer::buffer.
  Debug(1, "input %p output %p", job->inputBuf->buffer, job->outputBuf->buffer);
  auto DMAoutput = static_cast<uint8_t*>(job->outputBuf->buffer);
  if (!DMAoutput) {
    Error("No DMAOutput");
    return -1;
  }
  // Output buffer for one batch of images

  int m_uint16_bias = dequantization_uint16_bias = 4;
  int m_fp8p_bias = dequantization_fp8p_bias = -12;
  std::vector<std::vector<int>> m_index_map;

  const int NUM_NMS_PREDICTIONS = 256 * 6; // 256 boxes, each with 6 elements [l, t, r, b, class, score]
  std::vector<float> m_out_buf;
  m_out_buf.resize(NUM_NMS_PREDICTIONS);
  float* outputBuffer = m_out_buf.data();

  for (int row = 0; row < 256; row++) {
    uint8_t l_low, l_top, t_low, t_top, r_low, r_top, b_low, b_top, score;
    float object_class;
    // l_low = *(DMAoutput + m_index_map[row][0]);
    // l_top = *(DMAoutput + m_index_map[row][1]);
    // t_low = *(DMAoutput + m_index_map[row][2]);
    // t_top = *(DMAoutput + m_index_map[row][3]);
    // r_low = *(DMAoutput + m_index_map[row][4]);
    // r_top = *(DMAoutput + m_index_map[row][5]);
    // b_low = *(DMAoutput + m_index_map[row][6]);
    // b_top = *(DMAoutput + m_index_map[row][7]);

    int outputDMAIndex = row * 64; // Each row has 6 values to store as per NMS struct
                                   // BUT IS PADDED TO 64 BYTES
    l_low = *(DMAoutput + outputDMAIndex + 0);
    l_top = *(DMAoutput + outputDMAIndex + 1);
    t_low = *(DMAoutput + outputDMAIndex + 2);
    t_top = *(DMAoutput + outputDMAIndex + 3);
    r_low = *(DMAoutput + outputDMAIndex + 4);
    r_top = *(DMAoutput + outputDMAIndex + 5);
    b_low = *(DMAoutput + outputDMAIndex + 6);
    b_top = *(DMAoutput + outputDMAIndex + 7);
    object_class = static_cast<float>(*(DMAoutput + outputDMAIndex + 8));
    score = *(DMAoutput + outputDMAIndex + 9);

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
    outputBuffer[outputIndex] = l_float;
    outputBuffer[outputIndex + 1] = t_float;
    outputBuffer[outputIndex + 2] = r_float;
    outputBuffer[outputIndex + 3] = b_float;
    outputBuffer[outputIndex + 4] = object_class;
    outputBuffer[outputIndex + 5] = score_float;
  }

  try {
    Rgb colour = kRGBRed;
    nlohmann::json coco_object = convert_predictions_to_coco_format(m_out_buf, job->m_width_rescale, job->m_height_rescale);
    Debug(1, "SpeedAI coco: %s", coco_object.dump().c_str());
    if (coco_object.size()) {
      //Image in_image(packet->in_frame.get());
      //Image ai_image();
      //Image ai_image(in_image);
      //ai_image.Assign(in_image);
      //ai_image.PopulateFrame(packet->get_ai_frame());
      AVFrame *in_frame = packet->in_frame.get();

      for (auto it = coco_object.begin(); it != coco_object.end(); ++it) {
        nlohmann::json detection = *it;
        nlohmann::json bbox = detection["bbox"];

        Debug(1, "%s", bbox.dump().c_str());
        std::vector<Vector2> coords;
        nlohmann::json x1 = bbox[0];
        nlohmann::json y1 = bbox[1];
        nlohmann::json x2 = bbox[2];
        nlohmann::json y2 = bbox[3];
# if 0
        
        coords.push_back(Vector2(x1, y1));
        coords.push_back(Vector2(x2, y1));
        coords.push_back(Vector2(x2, y2));
        coords.push_back(Vector2(x1, y2));

        Polygon poly(coords);
        ai_image.Outline(colour, poly);
#endif
        AVFrame *out_frame = av_frame_alloc();
        if (!out_frame) {
          Error("cannot allocate output filter frame");
          return NIERROR(ENOMEM);
        }

        int ret = draw_box(in_frame, &out_frame, x1, y1, x2, y2);
        if (ret < 0) {
          Error("draw box failed");
          return ret;
        }
        zm_dump_video_frame(out_frame, "SpeedAI: boxes");

        std::string coco_class = detection["class_name"];
        float score = detection["score"];
        std::string annotation = stringtf("%s %d%%", coco_class.c_str(), static_cast<int>(100*score));
        Image ai_image(out_frame);
        ai_image.Annotate(annotation.c_str(), Vector2(x1, y1), monitor->LabelSize(), kRGBWhite, kRGBTransparent);

        if (in_frame != packet->in_frame.get())
          av_frame_free(&in_frame);
        in_frame = out_frame;
      }  // ed foreach detection
      packet->ai_frame = av_frame_ptr(in_frame);
      packet->detections = coco_object.dump();
    }  // end if coco
  } catch (std::exception const & ex) {
    Error("SpeedAI Exception: %s", ex.what());
  }

  jobs.pop_front();
  //delete job;
  return 1;
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

nlohmann::json SpeedAI::convert_predictions_to_coco_format(const std::vector<float>& predictions, float m_width_rescale, float m_height_rescale) {
  const int num_predictions = predictions.size() / 6;
  nlohmann::json coco_predictions = nlohmann::json::array();

  Debug(1, "Num Predictions %d", num_predictions);
  for (int i = 0; i < num_predictions; i++) {
    float l = predictions[i * 6 + 0];
    float t = predictions[i * 6 + 1];
    float r = predictions[i * 6 + 2];
    float b = predictions[i * 6 + 3];
    int class_id = static_cast<int>(predictions[i * 6 + 4]);
    float score = predictions[i * 6 + 5];

    // if score < m_conf_threshold we break as the predictions
    // are sorted by score
    if (score < obj_threshold) {
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

    coco_predictions.push_back(
        {{"class_name", class_name}, {"bbox", bbox}, {"score", score}});
  }
  return coco_predictions;
}

int SpeedAI::draw_box(
    AVFrame *inframe,
    AVFrame **outframe,
    int x, int y, int w, int h
    ) {
  if (!drawbox_filter_ctx) {
    Error("No drawbox_filter_ct");
    return -1;
  }

  char drawbox_option[32];
  std::string color;
  int n, ret;

  color = "White";

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

#endif
