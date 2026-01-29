//
// ZoneMinder Object Classes Header
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

#ifndef ZM_OBJECT_CLASSES_H
#define ZM_OBJECT_CLASSES_H

#include "zm_rgb.h"

#include <string>
#include <vector>

// Manages object class names for detection models.
// Provides default COCO classes and supports loading custom classes from .names files.
class ObjectClasses {
 public:
  // Default constructor uses COCO class names
  ObjectClasses();

  // Load class names from a .names file associated with the model file.
  // If no .names file is found, falls back to COCO defaults.
  // Returns true if custom classes were loaded, false if using defaults.
  bool loadFromFile(const std::string &model_file);

  // Get class name by ID with bounds checking.
  // Returns "unknown" for out-of-range IDs.
  const std::string& getClassName(int class_id) const;

  // Get number of classes
  size_t size() const { return class_names_.size(); }

  // Get detection box color based on class ID.
  // Person (class 0) = Blue, Vehicles (1-8) = Green, Animals (14-23) = Orange, Others = Red
  static Rgb getDetectionBoxColor(int class_id);

  // Get detection color name as string.
  static const char* getDetectionColorString(int class_id);

  // Access to underlying vector for iteration
  const std::vector<std::string>& getClassNames() const { return class_names_; }

 private:
  std::vector<std::string> class_names_;

  // Default COCO class names (80 classes)
  static const std::vector<std::string> kCocoClassNames;
  static const std::string kUnknownClass;
};

#endif  // ZM_OBJECT_CLASSES_H
