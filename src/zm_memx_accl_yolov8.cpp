#include "zm_memx_yolov8.h"
#include "zm_logger.h"
#include <stdexcept>

YOLOv8::YOLOv8(size_t input_width, size_t input_height, size_t input_channel, float confidence_thresh, float iou_thresh) {
  Init(input_width, input_height, input_channel, confidence_thresh, iou_thresh, COCO_CLASS_NUMBER, COCO_NAMES);
}

YOLOv8::YOLOv8(size_t input_width, size_t input_height, size_t input_channel,
    float confidence_thresh, float iou_thresh, size_t class_count, const char **class_labels)
{
  Init(input_width, input_height, input_channel, confidence_thresh, iou_thresh, class_count, class_labels);
}

void YOLOv8::Init(size_t accl_input_width, size_t accl_input_height, size_t accl_input_channel,
    float confidence_thresh, float iou_thresh, size_t class_count, const char **class_labels)
{
  if (confidence_thresh < 0.0f || confidence_thresh > 1.0f || iou_thresh < 0.0f || iou_thresh > 1.0f) {
    throw std::invalid_argument("Confidence and IOU threshold must be between 0.0 and 1.0.");
  }

  if (accl_input_width != accl_input_height) {
    throw std::invalid_argument("The YOLOv8 api requires model input to have equal width and height dimensions."
        "Currently, inconsistent input dimensions are not supported.");
  }

  accl_input_width_ = accl_input_width;
  accl_input_height_ = accl_input_height;
  accl_input_channel_ = accl_input_channel;
  confidence_thresh_ = confidence_thresh;
  iou_thresh_ = iou_thresh;
  class_labels_ = class_labels;
  class_count_ = class_count;
  valid_input_ = false;

  yolo_post_layers_[0] = {
    .coordinate_ofmap_flow_id = 0,
    .confidence_ofmap_flow_id = 1,
    .width = accl_input_width_ / 8,   // L0_HW, 640 / 8 = 80
    .height = accl_input_height_ / 8, // L0_HW, 640 / 8 = 80
    .ratio = 8,
    .coordinate_fmap_size = 64,
  };

  yolo_post_layers_[1] = {
    .coordinate_ofmap_flow_id = 2,
    .confidence_ofmap_flow_id = 3,
    .width = accl_input_width_ / 16,   // L1_HW, 640 / 16 = 40
    .height = accl_input_height_ / 16, // L1_HW, 640 / 16 = 40
    .ratio = 16,
    .coordinate_fmap_size = 64,
  };

  yolo_post_layers_[2] = {
    .coordinate_ofmap_flow_id = 4,
    .confidence_ofmap_flow_id = 5,
    .width = accl_input_width_ / 32,   // L2_HW, 640 / 32 = 20
    .height = accl_input_height_ / 32, // L2_HW, 640 / 32 = 20
    .ratio = 32,
    .coordinate_fmap_size = 64,
  };
}

YOLOv8::~YOLOv8()
{
}

float YOLOv8::GetConfidenceThreshold() {
  std::lock_guard<std::mutex> guard(confidence_mutex_);
  return confidence_thresh_;
}

void YOLOv8::SetConfidenceThreshold(float confidence) {
  if (confidence < 0.0f || confidence > 1.0f) {
    throw std::invalid_argument("Confidence threshold must be between 0.0 and 1.0.");
  }

  std::lock_guard<std::mutex> guard(confidence_mutex_);
  confidence_thresh_ = confidence;
}

bool YOLOv8::IsHorizontalInput(int disp_width, int disp_height) {
  if (disp_height > disp_width) {
    Error("Invalid display image: only horizontal images are supported.");
    return false;
  }
  return true;
}

void YOLOv8::ComputePadding(int disp_width, int disp_height) {
  if (!IsHorizontalInput(disp_width, disp_height))
    return;

  //  accl_input_height_ is equal to accl_input_width_
  letterbox_ratio_ = (float)accl_input_height_ / mxutil_max(disp_width, disp_height);

  letterbox_width_ = disp_width * letterbox_ratio_;
  letterbox_height_ = disp_height * letterbox_ratio_;

  padding_width_ = (accl_input_width_ - letterbox_width_) / 2;
  padding_height_ = (accl_input_height_ - letterbox_height_) / 2;

  valid_input_ = true;
}

void YOLOv8::PreProcess(uint8_t *rgb_data, int image_width, int image_height, std::vector<float *> input_buffers) {
  if (!valid_input_) {
    throw std::runtime_error("Make sure to call ComputePadding() before further processing.");
  }

  int offset = padding_height_ * accl_input_width_ * 3;
  float *buffer_ptr = input_buffers[0] + offset; // YOLOv8 has 1 input

#if 0
  cv::Mat src_img = cv::Mat(image_height, image_width, CV_8UC3, rgb_data);
  cv::Mat resized_img;
  cv::resize(src_img, resized_img, cv::Size(letterbox_width_, letterbox_height_), 0, 0, cv::INTER_LINEAR);


  for (int row = 0; row < resized_img.rows; ++row) {
    const cv::Vec3b *pixel_row = resized_img.ptr<cv::Vec3b>(row);

    for (int col = 0; col < resized_img.cols; ++col) {
      const cv::Vec3b &pixelValue = pixel_row[col];

#pragma omp simd
      for(int i=0; i < 3; i++) {
        buffer_ptr[0] = pixelValue[0] / (float)255.0; // red
        buffer_ptr[1] = pixelValue[1] / (float)255.0; // green
        buffer_ptr[2] = pixelValue[2] / (float)255.0; // blue
      }
      buffer_ptr += 3;
    }
  }
#else

  uint8_t *input_buffer_ptr = rgb_data;

  for (int row = 0; row < image_height; ++row) {
    for (int col = 0; col < image_width; ++col) {
#pragma omp simd
      for(int i=0; i < 3; i++) {
        buffer_ptr[0] = input_buffer_ptr[0] / (float)255.0; // red
        buffer_ptr[1] = input_buffer_ptr[1] / (float)255.0; // green
        buffer_ptr[2] = input_buffer_ptr[2] / (float)255.0; // blue
      }
      buffer_ptr += 3;
      input_buffer_ptr += 3;
    }
  }
#endif
}


void YOLOv8::GetDetection(
    std::queue<BBox> &bboxes,
    int layer_id,
    float *confidence_cell_buf,
    float *coordinate_cell_buf,
    int row, int col, float *confs_tmp)
{
  // process confidence score
  float best_label_score = -1.0;
  int best_label;

  // Force use of SIMD to calculate sigmoid.
  //
  // The space tradeoff of the temp result buffer
  // is more than worth the speedup / CPU usage reduction
  // (~75% CPU [down from 90% due to other changes] down to 59% CPU on my laptop)
#pragma omp simd
  for (size_t label = 0; label < class_count_; label++) {
    confs_tmp[label] = mxutil_prepost_sigmoid(confidence_cell_buf[label]);
  }
  // no way to avoid O(n) here, though
  for (size_t label = 0; label < class_count_; label++) {
    if (confs_tmp[label] > best_label_score) {
      best_label_score = confs_tmp[label];
      best_label = label;
    }
  }

  if (best_label_score < confidence_thresh_)
    return;

  std::vector<float> feature_value{};

  for (int channel = 0; channel < 4; channel++) { // split 64 into 4*16
    float value = 0.0;
    float *feature_buf = coordinate_cell_buf + channel * 16;
    float softmax_sum = 0.0;
    float local_max = feature_buf[0];

    // apply softmax and weighted sum
    for (int i = 1; i < 16; i++) {
      if (feature_buf[i] > local_max)
        local_max = feature_buf[i];
    }

    // more SIMD hints
#pragma omp simd reduction(+:softmax_sum)
    for (int i = 0; i < 16; i++) {
      softmax_sum += expf(feature_buf[i] - local_max);
    }

    // more SIMD hints
#pragma omp simd reduction(+:value)
    for (int i = 0; i < 16; i++) {
      value += ((float)i * (float)(expf(feature_buf[i] - local_max) / softmax_sum));
    }
    feature_value.push_back(value);
  }

  float center_x, center_y, w, h;

  center_x = (feature_value[2] - feature_value[0] + 2 * (0.5 + ((float)col))) * 0.5 * yolo_post_layers_[layer_id].ratio;
  center_y = (feature_value[3] - feature_value[1] + 2 * (0.5 + ((float)row))) * 0.5 * yolo_post_layers_[layer_id].ratio;
  w = (feature_value[2] + feature_value[0]) * yolo_post_layers_[layer_id].ratio;
  h = (feature_value[3] + feature_value[1]) * yolo_post_layers_[layer_id].ratio;

  // printf("[Layer%d] (%0.3f) %s\t: %.2f\t,%.2f\t,%.2f\t,%.2f\t]\n", layer_id, best_label_score, class_labels_[best_label], center_x, center_y, w, h);

  float min_x = mxutil_max(center_x - 0.5 * w, .0);
  float min_y = mxutil_max(center_y - 0.5 * h, .0);
  float max_x = mxutil_min(center_x + 0.5 * w, accl_input_width_);
  float max_y = mxutil_min(center_y + 0.5 * h, accl_input_height_);

  BBox bbox(best_label, best_label_score, min_x, min_y, max_x, max_y);

  non_maximum_suppression(bboxes, bbox, iou_thresh_);
}

void YOLOv8::PostProcess(std::vector<float *> output_buffers, YOLOv8Result &result) {
  if (!valid_input_) {
    throw std::runtime_error("Make sure to call ComputePadding() before further processing.");
  }

  if (output_buffers.empty()) {
    throw std::invalid_argument("output_buffers cannot be null.");
  }

  float *confs_tmp = new float[class_count_];

  for (size_t layer_id = 0; layer_id < kNumPostProcessLayers; ++layer_id) {
    const auto &layer = yolo_post_layers_[layer_id];
    const int confidence_floats_per_row = (layer.width * class_count_);               // 80 x 80
    const int coordinate_floats_per_row = (layer.width * layer.coordinate_fmap_size); // 80 x 64

    const int conf_id = layer.confidence_ofmap_flow_id;
    const int coord_id = layer.coordinate_ofmap_flow_id;

    float *confidence_base = output_buffers[conf_id];
    float *coordinate_base = output_buffers[coord_id];

    if (!confidence_base || !coordinate_base) {
      throw std::invalid_argument("One or more output buffers are null.");
    }

    for (size_t row = 0; row < layer.height; row++) {
      float *confidence_row_buf = confidence_base + row * confidence_floats_per_row;
      float *coordinate_row_buf = coordinate_base + row * coordinate_floats_per_row;

      for (size_t col = 0; col < layer.width; col++) {
        float *confidence_cell_buf = confidence_row_buf + col * class_count_;
        float *coordinate_cell_buf = coordinate_row_buf + col * layer.coordinate_fmap_size;
        GetDetection(result.bboxes, layer_id, confidence_cell_buf, coordinate_cell_buf, row, col, confs_tmp);
      }
    }
  }

  delete [] confs_tmp;
}

void YOLOv8::CalculateBboxParams(const float *feature_values, int layer_id, int row, int col,
    float &center_x, float &center_y, float &box_width, float &box_height)
{
  const float ratio = yolo_post_layers_[layer_id].ratio;

  center_x = (feature_values[2] - feature_values[0] + 2.0f * (0.5f + static_cast<float>(col))) * 0.5f * ratio;
  center_y = (feature_values[3] - feature_values[1] + 2.0f * (0.5f + static_cast<float>(row))) * 0.5f * ratio;
  box_width = (feature_values[2] + feature_values[0]) * ratio;
  box_height = (feature_values[3] + feature_values[1]) * ratio;
}

void YOLOv8::GetFeatureValues(const float *coordinate_buffer, float *feature_values)
{
  constexpr int kNumChannels = 4;
  constexpr int kChannelSize = 16;

  for (int channel = 0; channel < kNumChannels; ++channel) {
    const float *feature_buf = coordinate_buffer + channel * kChannelSize;
    float local_max = feature_buf[0];

    // Find local max
    for (int i = 1; i < kChannelSize; ++i) {
      if (feature_buf[i] > local_max) {
        local_max = feature_buf[i];
      }
    }

    // Compute softmax and weighted sum in a single pass
    float softmax_sum = 0.0f;
    float value = 0.0f;
    float temp[kChannelSize];
    for (int i = 0; i < kChannelSize; ++i) {
      temp[i] = feature_buf[i] - local_max;
      float exp_val = expf(temp[i]);
      softmax_sum += exp_val;
      value += static_cast<float>(i) * exp_val;
    }
    feature_values[channel] = value / softmax_sum;
  }
}
