#pragma once

#include <queue>
#include <cstdlib>
#include <cstdint>
#include <cmath>

#define mxutil_prepost_sigmoid(_x_) (1.0 / (1.0 + expf(-1.0 * (_x_))))                                   // sigmoid: f(x) = 1 / (1 + e^(-x))
#define mxutil_prepost_sigmoid_fast_sigmoid(_x_) ((_x_) / (((_x_) < 0) ? (1.0 - (_x_)) : (1.0 + (_x_)))) // fast-sigmoid: f(x) = x / (1 + abs(x))
#define mxutil_max(_x_, _y_) (((_x_) > (_y_)) ? (_x_) : (_y_))
#define mxutil_min(_x_, _y_) (((_x_) < (_y_)) ? (_x_) : (_y_))

#define COCO_CLASS_NUMBER (80)

/**
 * @brief Labels of COCO dataset, COCO 2014 and 2017 uses the same images but
 * different train/val/test splits. Also, COCO defines 91 classes but the data
 * only uses 80 classes.
 */
extern const char* COCO_NAMES[COCO_CLASS_NUMBER];

struct BBox {
    int class_index;   // class index with maximum confident
    float class_score; // class confident(score)
    float x_min;       // global top-left x relates to model's input feature map size width
    float y_min;       // global top-left y relates to model's input feature map size height
    float x_max;       // global bottom-right x relates to model's input feature map size width
    float y_max;       // global bottom-right y relates to model's input feature map size height

    // Default constructor
    BBox() : class_index(-1), class_score(-1), x_min(-1), y_min(-1), x_max(-1), y_max(-1) {}
    // Parameterized constructor
    BBox(int _class_index, float _class_socre, float _x_min, float _y_min, float _x_max, float _y_max)
        : class_index(_class_index), class_score(_class_socre), x_min(_x_min), y_min(_y_min), x_max(_x_max), y_max(_y_max) {}
};

struct YOLOv8Result {
    std::queue<BBox> bboxes;
    std::queue<std::vector<std::pair<float, float>>> keypoints;
    std::queue<std::vector<float>> mask_features;
    //std::queue<cv::Rect> final_rois;
    //std::queue<cv::Mat> final_masks;
};

struct YOLOv8result {
    BBox bbox;
    std::vector<std::pair<float, float>> keypoint;
    std::vector<float> mask_feature;
};

/**
 * @brief Calculate the area overlap between two bounding box for NMS algo. to
 * combine or drop bounding boxes.
 *
 * @param bbox_0            bounding box 0
 * @param bbox_1            bounding box 1
 * @param class_chk         set zero to ignore class type
 *
 * @return overlap percentage
 */
float intersection_over_union(BBox &bbox_0, BBox &bbox_1, int class_chk);

/**
 * @brief Post-process to calculate detection overlaps to combine the same
 * object with duplicated detections together as only one detection. Bounding
 * box will be added to list if IOU is smaller then given threshold, otherwise
 * the bounding box with higher score will be kept.
 *
 * @param bboxes_detected   linked-list which stores bounding boxes detected
 * @param bbox              bounding box to be added to list
 * @param iou               IOU threshold to combine bounding box
 *
 * @return none
 */
void non_maximum_suppression(std::queue<BBox> &bboxes, BBox &bbox, float iou);
void BF16ToFP32(const uint16_t *bf16_buffer, float *fp32_buffer, int length);
