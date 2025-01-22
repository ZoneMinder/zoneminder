#ifndef _YOLO_MODEL
#define _YOLO_MODEL

#include "yolo_postprocess.h"

typedef struct yolo_model {
  int (*create_model)(YoloModelCtx *ctx, ni_network_data_t *network_data, float obj_thresh, float nms_thresh, unsigned int model_width, unsigned int model_height);
  void (*destroy_model)(YoloModelCtx *ctx);
  int (*ni_get_boxes)(YoloModelCtx *ctx, uint32_t img_width, uint32_t img_height, struct roi_box **roi_box, int *roi_num);
}YoloModel;


extern YoloModel yolov4;
extern YoloModel yolov5;
#endif
