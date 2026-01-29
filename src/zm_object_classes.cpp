//
// ZoneMinder Object Classes Implementation
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

#include "zm_object_classes.h"
#include "zm_logger.h"

#include <fstream>

// Default COCO dataset class names (80 classes)
const std::vector<std::string> ObjectClasses::kCocoClassNames = {
  "person", "bicycle", "car", "motorcycle", "airplane", "bus", "train", "truck", "boat",
  "traffic light", "fire hydrant", "stop sign", "parking meter", "bench", "bird", "cat",
  "dog", "horse", "sheep", "cow", "elephant", "bear", "zebra", "giraffe", "backpack",
  "umbrella", "handbag", "tie", "suitcase", "frisbee", "skis", "snowboard", "sports ball",
  "kite", "baseball bat", "baseball glove", "skateboard", "surfboard", "tennis racket",
  "bottle", "wine glass", "cup", "fork", "knife", "spoon", "bowl", "banana", "apple",
  "sandwich", "orange", "broccoli", "carrot", "hot dog", "pizza", "donut", "cake", "chair",
  "couch", "potted plant", "bed", "dining table", "toilet", "tv", "laptop", "mouse", "remote",
  "keyboard", "cell phone", "microwave", "oven", "toaster", "sink", "refrigerator", "book",
  "clock", "vase", "scissors", "teddy bear", "hair drier", "toothbrush"
};

const std::string ObjectClasses::kUnknownClass = "unknown";

ObjectClasses::ObjectClasses() : class_names_(kCocoClassNames) {
}

bool ObjectClasses::loadFromFile(const std::string &model_file) {
  // Try to find a .names file alongside the model file
  // e.g., /path/to/model.nb -> /path/to/model.names
  std::string names_file = model_file;
  size_t dot_pos = names_file.rfind('.');
  if (dot_pos != std::string::npos) {
    names_file = names_file.substr(0, dot_pos) + ".names";
  } else {
    names_file += ".names";
  }

  std::ifstream file(names_file);
  if (!file.is_open()) {
    // Try looking for coco.names in the same directory
    size_t slash_pos = model_file.rfind('/');
    if (slash_pos != std::string::npos) {
      names_file = model_file.substr(0, slash_pos + 1) + "coco.names";
      file.open(names_file);
    }
  }

  if (!file.is_open()) {
    Debug(1, "No .names file found for %s, using COCO defaults", model_file.c_str());
    class_names_ = kCocoClassNames;
    return false;
  }

  class_names_.clear();
  std::string line;
  while (std::getline(file, line)) {
    // Trim whitespace
    size_t start = line.find_first_not_of(" \t\r\n");
    size_t end = line.find_last_not_of(" \t\r\n");
    if (start != std::string::npos && end != std::string::npos) {
      class_names_.push_back(line.substr(start, end - start + 1));
    } else if (line.empty() || start == std::string::npos) {
      // Skip empty lines but preserve index
      class_names_.push_back("");
    }
  }

  Debug(1, "Loaded %zu class names from %s", class_names_.size(), names_file.c_str());
  return true;
}

const std::string& ObjectClasses::getClassName(int class_id) const {
  if (class_id >= 0 && static_cast<size_t>(class_id) < class_names_.size()) {
    return class_names_[class_id];
  }
  Warning("Class ID %d out of range (0-%zu)", class_id, class_names_.size() - 1);
  return kUnknownClass;
}

Rgb ObjectClasses::getDetectionBoxColor(int class_id) {
  if (class_id == 0) {
    return kRGBBlue;    // Person
  } else if (class_id >= 1 && class_id <= 8) {
    return kRGBGreen;   // Vehicles: bicycle, car, motorcycle, airplane, bus, train, truck, boat
  } else if (class_id >= 14 && class_id <= 23) {
    return kRGBOrange;  // Animals: bird, cat, dog, horse, sheep, cow, elephant, bear, zebra, giraffe
  }
  return kRGBRed;       // Everything else
}

const char* ObjectClasses::getDetectionColorString(int class_id) {
  if (class_id == 0) {
    return "blue";
  } else if (class_id >= 1 && class_id <= 8) {
    return "green";
  } else if (class_id >= 14 && class_id <= 23) {
    return "orange";
  }
  return "red";
}
