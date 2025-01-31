#ifndef _NI_YOLO_UTILS
#define _NI_YOLO_UTILS

#include <math.h>
#include "ni_log.h"
#include "yolo_postprocess.h"

extern int anchor_stride[3];

static inline int entry_index(ni_roi_network_layer_t *l, int batch, int component,
                       int location, int entry)
{
    return component * l->width * l->height * (4 + 1 + l->classes + l->padding) +
           entry * l->width * l->height + location;
}

int ni_get_yolov5_detections(YoloModelCtx *ctx, int sequence[3], int normalize_box);

void ni_resize_coords_tiling_mode(detection *det, struct roi_box *roi_box,
        int img_width, int img_height, float gain_x, float gain_y);

void ni_resize_coords_padding_mode(detection *det, struct roi_box *roi_box,
        int img_width, int img_height, float gain, float pad0, float pad1);

#endif
