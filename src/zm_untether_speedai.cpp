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

#ifdef HAVE_UNTETHER_H
SpeedAI::SpeedAI(Monitor *monitor_) :
  monitor(monitor_),
  module(nullptr),
  //inputBuf({}),
  //outputBuf({}),
  batchSize(0),
  inSize(0),
  outSize(0),
  scaled_frame({}),
  sw_scale_ctx(nullptr)
  //image_size(0)
{
  image_size = av_image_get_buffer_size(AV_PIX_FMT_RGB24, MODEL_WIDTH, MODEL_HEIGHT, 32);
}

SpeedAI::~SpeedAI() {
  // Clean up
  if (module)
    uai_module_free(module);
  av_frame_unref(&scaled_frame);
  if (sw_scale_ctx)
    sws_freeContext(sw_scale_ctx);
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
    //return false;
  }
  // Get info on input/output streams. We assume a simple model (like Resnet50) with one input
  // stream and one output stream from here onwards. To see how larger input/output sizes are
  // handled, please refer to the other demos.
  size_t numStreams;
  err = uai_module_get_num_streams(module, &numStreams);
  if (err != UAI_SUCCESS) {
    Error("Failed getting num streams %s", uai_err_string(err));
    //return false;
  }
  //assert(numStreams == 2);
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
  return true;
}

int SpeedAI::send_image(const Image &image) {
  av_frame_ptr avframe{zm_av_frame_alloc()};
  image.PopulateFrame(avframe.get());

  Debug(1, "SpeedAI::detect");
  // Resize, change to RGB, maybe quantize
  //Image processed_image = preprocess(image);

  if (!sw_scale_ctx) {
    scaled_frame.width = MODEL_WIDTH;
    scaled_frame.height = MODEL_HEIGHT;
    scaled_frame.format = AV_PIX_FMT_RGB24;
    if (av_frame_get_buffer(&scaled_frame, 32)) {
      Error("cannot allocate scaled frame buffer");
      return -1;
    }

    sw_scale_ctx = sws_getContext(
        avframe->width, avframe->height, static_cast<AVPixelFormat>(avframe->format),
        scaled_frame.width, scaled_frame.height, static_cast<AVPixelFormat>(scaled_frame.format),
        SWS_BICUBIC, nullptr, nullptr, nullptr);
    if (!sw_scale_ctx) {
      Error("cannot create sw scale context");
      return -1;
    }
  }

  int ret = sws_scale(sw_scale_ctx, (const uint8_t * const *)avframe->data,
      avframe->linesize, 0, avframe->height, scaled_frame.data, scaled_frame.linesize);
  if (ret < 0) {
    Error("cannot do sw scale: inframe data 0x%lx, linesize %d/%d/%d/%d, height %d to %d linesize",
        (unsigned long)avframe->data, avframe->linesize[0], avframe->linesize[1],
        avframe->linesize[2], avframe->linesize[3], avframe->height, scaled_frame.linesize[0]);
    return ret;
  }

  Job job;
  jobs.push_back(job);

  uai_module_data_buffer_attach(module, &job.inputBuf, infos[0].name, inSize);
  uai_module_data_buffer_attach(module, &job.outputBuf, infos[1].name, outSize);
  memcpy(job.inputBuf.buffer, scaled_frame.buf[0], image_size);

  // Attach buffers to event, so runtime knows where to find input and output buffers to
  // read/write data. All buffers are chained together linearly. Here we again assume that we only
  // have one input stream and one output stream, and that one buffer per stream is big enough to
  // hold all data for one batch.
  job.event.buffers = &(job.inputBuf);
  job.inputBuf.next_buffer = &(job.outputBuf);

  Debug(1, "SpeedAI::enqueue");
  // Enqueue event, inference will start asynchronously.
  UaiErr err = uai_module_enqueue(module, &job.event);
  if (err != UAI_SUCCESS) {
    Error("Failed enqueue %s", uai_err_string(err));
    return -1;
    //return false;
  }
  if (0) {
  Debug(1, "SpeedAI::sync");
  err = uai_module_synchronize(module, &job.event);
  if (err != UAI_SUCCESS) {
    Error("SpeedAI Failed sync model %s", uai_err_string(err));
    //return false;
  }
  }

  Debug(1, "SpeedAI::wait");
  // Block execution until the inference job associate to our event has finished. Alternatively,
  // we could repeatedly poll the status of the job using `uai_module_wait`.
  err = uai_module_wait(module, &job.event, 1000);
  if (err != UAI_SUCCESS) {
    Error("SpeedAI Failed wait %s", uai_err_string(err));
    return 0;
  }
  Debug(1, "SpeedAI Completed inference, wait return code is %s", uai_err_string(err));

  // Now print out the result of the inference job. Note again that the designated memory address
  // on the host side is UaiDataBuffer::buffer.
  auto DMAoutput = static_cast<uint8_t*>(job.outputBuf.buffer);
    // Output buffer for one batch of images
  std::vector<float> m_out_buf;
  float* outputBuffer = m_out_buf.data();

  int m_uint16_bias = dequantization_uint16_bias = 4;

  int m_fp8p_bias = dequantization_fp8p_bias = -12;
  std::vector<std::vector<int>> m_index_map;


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
  return 1;
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

//Image &SpeedAI::preprocess_image(const Image &image) {
//}
#endif
