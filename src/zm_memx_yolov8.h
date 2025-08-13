#pragma once

#include "zm_memx_yolo_core.h"
#include <queue>
#include <mutex>

class YOLOv8 {
public:
    /** @brief Constructor for using official 80 classes COCO dataset. */
    YOLOv8(size_t input_width, size_t input_height, size_t input_channel,
           float confidence_thresh, float iou_thresh);

    /** @brief Constructor for using a customized dataset. */
    YOLOv8(size_t input_width, size_t input_height, size_t input_channel,
           float confidence_thresh, float iou_thresh, size_t class_count, const char **class_labels);

    ~YOLOv8();

    /**
     * @brief Pre-process the input image data for YOLOv8 model inference.
     * @param rgb_data      Pointer to the input image data in RGB format.
     * @param image_width   Width of the display image.
     * @param image_height  Height of the display image.
     * @param input_buffers Vector of pointers to the input buffers used by the accelerator for processing. Should be zero-initialized.
     */
    void PreProcess(uint8_t *rgb_data, int image_width, int image_height, std::vector<float *> input_buffers);

    /**
     * @brief Post-process the output data from the YOLOv8 model.
     * @param output_buffers   Vector of pointers to the output buffers from the accelerator.
     * @param result           Reference to the structure where the decoded bounding box results will be stored.
     */
    void PostProcess(std::vector<float *> output_buffers, YOLOv8Result &result);

    /** @brief Clear the detection results. */
    void ClearDetectionResults(YOLOv8Result &result);

    /** @brief Setters and getters for the confidence threshold. */
    void SetConfidenceThreshold(float confidence);
    float GetConfidenceThreshold();

    /** @brief Compute padding values for letterboxing from the display image. */
    void ComputePadding(int disp_width, int disp_height);

    /** @brief Ensure the input dimensions are valid for horizontal display images only.  */
    bool IsHorizontalInput(int disp_width, int disp_height);

private:
    /** @brief Structure representing per-layer information of YOLOv8 output. */
    struct LayerParams
    {
        uint8_t coordinate_ofmap_flow_id;
        uint8_t confidence_ofmap_flow_id;
        size_t width;
        size_t height;
        size_t ratio;
        size_t coordinate_fmap_size;
    };

    /** @brief Initialization method to set up YOLOv8 model parameters. */
    void Init(size_t accl_input_width, size_t accl_input_height, size_t accl_input_channel,
              float confidence_thresh, float iou_thresh, size_t class_count, const char **class_labels);

    /** @brief Helper methods for building detections from model output. */
    void GetDetection(std::queue<BBox> &bounding_boxes, int layer_id, float *confidence_buffer,
                        float *coordinate_buffer, int row, int col, float *confs_tmp);

    /** @brief Helper methods for calculating bounding box parameters from feature values. */
    void CalculateBboxParams(const float *feature_values, int layer_id, int row, int col,
                             float &center_x, float &center_y, float &box_width, float &box_height);
    void GetFeatureValues(const float *coordinate_buffer, float *feature_values);

    static constexpr size_t kNumPostProcessLayers = 3;
    struct LayerParams yolo_post_layers_[kNumPostProcessLayers];

    // Model-specific parameters.
    const char **class_labels_;
    size_t class_count_;
    size_t accl_input_width_;   // Input width to accelerator, obtained by dfp.
    size_t accl_input_height_;  // Input height to accelerator, obtained by dfp.
    size_t accl_input_channel_; // Input channel to accelerator, obtained by dfp.

    // Confidence and IOU thresholds.
    std::mutex confidence_mutex_;
    float confidence_thresh_;
    float iou_thresh_;

    // Letterbox ratio and padding.
    float letterbox_ratio_;
    int letterbox_width_;
    int letterbox_height_;
    int padding_height_;
    int padding_width_;
    bool valid_input_;
};
