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
  inputBuf({}),
  outputBuf({}),
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
  UaiDataStreamInfo infos[numStreams];
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
  uai_module_data_buffer_attach(module, &inputBuf, infos[0].name, inSize);
  uai_module_data_buffer_attach(module, &outputBuf, infos[1].name, outSize);
  Debug(1, "Done attaching");
  return true;
}

int SpeedAI::detect(const Image &image) {
  auto inDataPtr = static_cast<uint8_t*>(inputBuf.buffer);
  av_frame_ptr avframe{zm_av_frame_alloc()};
  image.PopulateFrame(avframe.get());

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

  memcpy(inputBuf.buffer, avframe->buf[0], image_size);

  // Attach buffers to event, so runtime knows where to find input and output buffers to
  // read/write data. All buffers are chained together linearly. Here we again assume that we only
  // have one input stream and one output stream, and that one buffer per stream is big enough to
  // hold all data for one batch.
  UaiEvent event;
  event.buffers = &inputBuf;
  
  inputBuf.next_buffer = &outputBuf;

  // Enqueue event, inference will start asynchronously.
  uai_module_enqueue(module, &event);

  // Block execution until the inference job associate to our event has finished. Alternatively,
  // we could repeatedly poll the status of the job using `uai_module_wait`.
  uai_module_wait(module, &event, -1);
  Debug(1, "Completed inference");

  // Now print out the result of the inference job. Note again that the designated memory address
  // on the host side is UaiDataBuffer::buffer.
  auto outDataPtr = static_cast<uint8_t*>(outputBuf.buffer);
  for (size_t i(0); i < outSize; i++) {
    std::cout << int(*(outDataPtr + i)) << std::endl;
  }
  return true;
}

//Image &SpeedAI::preprocess_image(const Image &image) {
//}
#endif
