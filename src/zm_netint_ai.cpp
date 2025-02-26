
#include "zm_monitor.h"
#include "zm_signal.h"
#include "zm_time.h"

Monitor::Quadra::Quadra(Monitor *p_monitor) :
  api_ctx({}),
  network({}),
  monitor(p_monitor)
{
}

Monitor::Quadra::~Quadra() {
  ni_retcode_t retval = ni_device_session_close(&api_ctx, 1, NI_DEVICE_TYPE_AI);
  if (retval != NI_RETCODE_SUCCESS) {
    Warning("retval from close %d", retval);
  }
}

bool Monitor::Quadra::setup() {
  std::string nb_file = "/usr/share/zoneminder/yolov4-tiny-416_darknet.nb";
  int devid = 0;
  ni_retcode_t retval = ni_device_session_context_init(&api_ctx);
  api_ctx.hw_id = devid; // -1?
  api_ctx.device_type = NI_DEVICE_TYPE_AI;

  retval = ni_device_session_open(&api_ctx, NI_DEVICE_TYPE_AI);
  if (retval != NI_RETCODE_SUCCESS) {
    Error("Quadra: Failed opening session");
    return false;
  } else {
    Debug(1, "Quadra success opening session");
  }
  retval = ni_ai_config_network_binary(&api_ctx, &network, nb_file.c_str());
  if (retval != NI_RETCODE_SUCCESS) {
    Error("failed to configure npu session. retval %d\n", retval);
    return false;
  } else {
    Debug(1, "Quadra success config network binary %s", nb_file.c_str());
  }
  int pool_size =20;
  int options;

  options = NI_AI_FLAG_IO |  NI_AI_FLAG_PC;
  /* Allocate a pool of frames by the scaler */
  retval = ni_device_alloc_frame( &api_ctx,
      monitor->Width(),
      monitor->Height(),
      GC620_I420,
      options,
      0, // rec width
      0, // rec height
      0, // rec X pos
      0, // rec Y pos
      pool_size, // rgba color/pool size
      0, // frame index
      NI_DEVICE_TYPE_AI);

  return true;
}

bool Monitor::Quadra::detect(AVFrame *in_frame) {

#if 0 
  ni_retcode_t retval;
  retval = ni_ai_frame_buffer_alloc(&api_src_frame.data.frame, &network);
  retval = ni_ai_packet_buffer_alloc(&api_dst_packet.data.packet, &network);

  int input_layer_idx = 0;
  int32_t offset = network.inset[input_layer_idx].offset;
  //uint8_t *p_data = (uint8_t *)api_src_frame.data.frame.p_data[0] + offset;


  uint8_t *img_buffer = in_frame->data;
  int linesize = in_frame->linesize;
  for (int h = 0; h < in_frame->height; h++) {
    memcpy(p_data, img_buffer, in_frame->width);
    p_data += in_frame->width;
    img_buffer += linesize;
  }

  // if using tensor files
  p_data = (uint8_t *)api_src_frame.data.frame.p_data[0] + offset;
  int input_size = ni_ai_network_layer_size(&network.linfo.in_param[input_layer_index]);
  Debug(1, "Input size: %d", input_size);
  retval = ni_network_layer_convert_tensor(p_data, input_size, tensor_file, &network.linfo.in_param[input_layer_index]);

  int ret;
  do {
    ret = ni_device_session_write(&api_ctx, &api_src_frame, NI_DEVICE_TYPE_AI);
    if (ret < 0) {
      /* error occurs */
      break;
    } else if (ret >= 0) {
      /* write complete */
      break;
    }
    /* can’t write anything. Choose to keep polling the buffer to be written
       here. */
  } while (ret == 0);

  do {
    ret = ni_device_session_read(&api_ctx, &api_dst_packet, NI_DEVICE_TYPE_AI);
    if (ret < 0) {
      /* error occurs */
      break;
    } else if (ret >= 0) {
      /* write complete */
      break;
    }
    /* can’t read anything. Choose to keep polling the buffer to read here. */
  } while (ret == 0);

#endif
  /*
  int output_layer_index = 0;
  int output_buffer_size = ni_ai_network_layer_dims(&network.linfo.out_param[output_layer_index]) * sizeof(float);
  float output_buffer[output_buffer_size];

  retval = ni_network_layer_convert_output(output_buffer, output_buffer_size, &api_dst_packet.data.packet, &network, output_layer_index);
  retval = ni_device_session_close(&api_ctx, 1, NI_DEVICE_TYPE_AI);
  */

  return true;
}
