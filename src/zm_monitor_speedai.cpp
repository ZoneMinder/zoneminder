//
// ZoneMinder Monitor::SpeedAI Class Implementation
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

#include "zm_monitor.h"
// Untether runtime API header
#include "uai_untether.h"


#include <cstring>

Monitor::SpeedAI::SpeedAI(Monitor *monitor_) :
  monitor(monitor_)
{
}

Monitor::SpeedAI::~SpeedAI() {
  // Clean up
  uai_module_free(module);
}

bool Monitor::SpeedAI::setup() {
  // Load and launch module
  std::string model = "/usr/share/zoneminder/u_speedai_yolo_model_creator.uxf";
  Debug(1, "SpeedAI: Loading model %s", model.c_str());
  uai_module_load(model.c_str(), &module);
  Debug(1, "SpeedAI: launching");
  uai_module_launch(module);
  // Get info on input/output streams. We assume a simple model (like Resnet50) with one input
  // stream and one output stream from here onwards. To see how larger input/output sizes are
  // handled, please refer to the other demos.
  size_t numStreams;
  uai_module_get_num_streams(module, &numStreams);
  assert(numStreams == 2);
  Debug(1, "Num streams %zu", numStreams);
  UaiDataStreamInfo infos[numStreams];
  uai_module_get_stream_info(module, infos, numStreams);
  assert(infos[0].io_type == UAI_DATA_STREAM_HOST_TO_DEVICE);
  assert(infos[1].io_type == UAI_DATA_STREAM_DEVICE_TO_HOST);
  // Allocate input and output buffers, and attach them to the module. There is a size limitation
  // that applies to the IO buffers and in the most general case one may have to utilize mutiple
  // buffers per stream. Here we just assert that one buffer per input/output stream is sufficient
  // for one whole batch of IO data.
  batchSize = 10;
  inSize = infos[0].framesize_hint * batchSize;
  outSize = infos[1].framesize_hint * batchSize;
  assert(inSize <= UAI_MODULE_MAX_DATA_BUFFER_SIZE && outSize <= UAI_MODULE_MAX_DATA_BUFFER_SIZE);
  uai_module_data_buffer_attach(module, &inputBuf, infos[0].name, inSize);
  Debug(1, "SpeedAI inSize hint %zu, outSize hint %zu", inSize, outSize);
  uai_module_data_buffer_attach(module, &outputBuf, infos[1].name, outSize);
  return true;
}

bool Monitor::SpeedAI::detect(const Image &image) {
  auto inDataPtr = static_cast<uint8_t*>(inputBuf.buffer);
  for (size_t i(0); i < inSize; i++) {
    *(inDataPtr + i) = 42;
  }

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

  // Now print out the result of the inference job. Note again that the designated memory address
  // on the host side is UaiDataBuffer::buffer.
  auto outDataPtr = static_cast<uint8_t*>(outputBuf.buffer);
  for (size_t i(0); i < outSize; i++) {
    std::cout << int(*(outDataPtr + i)) << std::endl;
  }
  return true;
}
