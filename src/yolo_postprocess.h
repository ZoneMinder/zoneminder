#ifndef _YOLO_POSTPROCESS_H
#define _YOLO_POSTPROCESS_H

#include <stdint.h>
#include "netint_network.h"

#define YOLOV4  4
#define YOLOV5  5
#define YOLOV8  8

typedef struct _ni_roi_network_layer {
    int32_t index;
    int32_t width;
    int32_t height;
    int32_t channel;
    int32_t classes;
    int32_t component;
    int32_t padding;
    int32_t mask[3];
    float *biases;
    int32_t output_number;
    float *output;
} ni_roi_network_layer_t;

typedef struct box {
    union {
        float x;
        float x1;
    };
    union {
        float y;
        float y1;
    };
    union {
        float w;
        float x2;
    };
    union {
        float h;
        float y2;
    };
} box;

typedef struct detection {
    box bbox;
    float objectness;
    int classes;
    int color;
    float *prob;
    int prob_class;
    float max_prob;
    int sub_idx;
    int layer_idx;
} detection;

typedef struct detection_cache {
    detection *dets;
    int capacity;
    int dets_num;
} detection_cache;

struct roi_box {
    int left;
    int right;
    int top;
    int bottom;
    float prob;
    float objectness;
    int ai_class;
};

struct box_entry_set {
    int obj_entry;
    int class_entry;
    int coods_entry;
};

typedef struct yolo_model_ctx {
    float obj_thresh;
    float nms_thresh;
    int input_width;
    int input_height;
    int output_number;

    uint8_t **out_tensor;
    ni_roi_network_layer_t *layers;
    detection_cache det_cache;

    struct box_entry_set entry_set;
} YoloModelCtx;
#endif
