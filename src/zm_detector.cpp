/*
 * ZoneMinder object detection interface
 *
 * This program is free software; you can redistribute it and/or modify it
 * under the terms of the GNU General Public License as published by the
 * Free Software Foundation; either version 2 of the License, or (at your
 * option) any later version.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or
 * FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for
 * more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program. If not, see <http://www.gnu.org/licenses/>.
 */

#include "zm_detector.h"

#include "zm_detector_http.h"

#include "zm_image.h"
#include "zm_utils.h"
#include "zm_vector2.h"

#include <algorithm>
#include <cmath>
#include <cstdlib>

namespace zm {
namespace ai {

std::unique_ptr<Detector> MakeDetector(const std::string &name) {
#if HAVE_LIBCURL && ZM_HAS_NLOHMANN_JSON
  if (name == "http") return std::make_unique<HttpDetector>();
#endif
  return nullptr;
}

std::vector<std::string> DetectorNames() {
  std::vector<std::string> names;
#if HAVE_LIBCURL && ZM_HAS_NLOHMANN_JSON
  names.push_back("http");
#endif
  return names;
}

Rgb ParseBoxColor(const std::string &hex) {
  std::string digits = (!hex.empty() and hex[0] == '#') ? hex.substr(1) : hex;
  if (digits.size() != 6 or digits.find_first_not_of("0123456789abcdefABCDEF") != std::string::npos)
    return kRGBRed;
  const unsigned long rgb = std::strtoul(digits.c_str(), nullptr, 16);
  const Rgb r = (rgb >> 16) & 0xff;
  const Rgb g = (rgb >> 8) & 0xff;
  const Rgb b = rgb & 0xff;
  return (b << 16) | (g << 8) | r;  // Rgb holds red in the low byte
}

namespace {

const ClassSetting *SettingFor(const ClassSettings &settings, const std::string &label) {
  auto it = settings.find(label);
  return it == settings.end() ? nullptr : &it->second;
}

bool Reports(const ClassSettings &settings, const std::string &label) {
  const ClassSetting *setting = SettingFor(settings, label);
  return !setting or setting->report;
}

}  // namespace

std::vector<Detection> FilterDetections(const std::vector<Detection> &detections,
                                        const ClassSettings &settings,
                                        float object_threshold) {
  std::vector<Detection> kept;
  for (const Detection &detection : detections) {
    const ClassSetting *setting = SettingFor(settings, detection.label);
    if (setting) {
      if (!setting->enabled) continue;
      // Thresholds are whole percentages; compare in percent so 0.6 meets 60.
      if (std::lround(detection.confidence * 1000) < setting->confidence_threshold * 10) continue;
    } else if (detection.confidence < object_threshold) {
      continue;
    }
    kept.push_back(detection);
  }
  return kept;
}

int AlarmScore(const std::vector<Detection> &detections, const ClassSettings &settings) {
  float best = 0;
  for (const Detection &detection : detections) {
    if (Reports(settings, detection.label)) best = std::max(best, detection.confidence);
  }
  return static_cast<int>(std::lround(std::clamp(best, 0.0f, 1.0f) * 100));
}

std::vector<std::string> ReportedLabels(const std::vector<Detection> &detections, const ClassSettings &settings) {
  std::vector<std::string> labels;
  for (const Detection &detection : detections) {
    if (detection.label.empty() or !Reports(settings, detection.label)) continue;
    if (std::find(labels.begin(), labels.end(), detection.label) == labels.end())
      labels.push_back(detection.label);
  }
  return labels;
}

std::string AlarmCause(const std::vector<Detection> &detections, const ClassSettings &settings) {
  return Join(ReportedLabels(detections, settings), ", ");
}

bool InferenceBehind(bool was_behind, double lag_seconds, double behind_seconds) {
  return lag_seconds > (was_behind ? behind_seconds / 2 : behind_seconds);
}

void DrawDetections(Image &image, const std::vector<Detection> &detections,
                    const ClassSettings &settings, int label_size) {
  const unsigned int thickness = std::max(1, label_size);
  for (const Detection &detection : detections) {
    const ClassSetting *setting = SettingFor(settings, detection.label);
    const Rgb colour = setting ? setting->box_color : kRGBRed;
    const int x1 = static_cast<int>(std::lround(detection.x));
    const int y1 = static_cast<int>(std::lround(detection.y));
    const int x2 = static_cast<int>(std::lround(detection.x + detection.w)) - 1;
    const int y2 = static_cast<int>(std::lround(detection.y + detection.h)) - 1;
    image.DrawBox(x1, y1, x2, y2, colour, thickness);

    const std::string label = stringtf("%s %d%%", detection.label.c_str(),
                                       static_cast<int>(std::lround(detection.confidence * 100)));
    const int text_x = std::clamp(x1 + static_cast<int>(thickness), 0, std::max(0, static_cast<int>(image.Width()) - 1));
    const int text_y = std::clamp(y1 + static_cast<int>(thickness), 0, std::max(0, static_cast<int>(image.Height()) - 1));
    image.Annotate(label, Vector2(text_x, text_y), static_cast<uint8>(std::clamp(label_size, 1, 255)),
                   kRGBWhite, kRGBTransparent);
  }
}

#if ZM_HAS_NLOHMANN_JSON
nlohmann::json DetectionsToJson(const std::vector<Detection> &detections, int64_t pts_usec) {
  nlohmann::json list = nlohmann::json::array();
  for (const Detection &detection : detections) {
    list.push_back({
        {"label", detection.label},
        {"confidence", detection.confidence},
        {"bbox", {detection.x, detection.y, detection.w, detection.h}},
        {"class_id", detection.class_id},
    });
  }
  return {{"type", "detection"}, {"pts_usec", pts_usec}, {"detections", std::move(list)}};
}

namespace {

const nlohmann::json *Field(const nlohmann::json &object, std::initializer_list<const char *> names) {
  for (const char *name : names) {
    auto it = object.find(name);
    if (it != object.end() and !it->is_null()) return &*it;
  }
  return nullptr;
}

bool FourNumbers(const nlohmann::json *value, float out[4]) {
  if (!value or !value->is_array() or value->size() != 4) return false;
  for (size_t i = 0; i < 4; i++) {
    if (!(*value)[i].is_number()) return false;
    out[i] = (*value)[i].get<float>();
  }
  return true;
}

}  // namespace

std::vector<std::string> DetectionLabels(const nlohmann::json &doc) {
  std::vector<std::string> labels;
  if (!doc.is_object()) return labels;
  const nlohmann::json *list = Field(doc, {"detections"});
  if (!list or !list->is_array()) return labels;
  for (const nlohmann::json &entry : *list) {
    if (!entry.is_object()) continue;
    const nlohmann::json *label = Field(entry, {"label"});
    if (!label or !label->is_string() or label->get_ref<const std::string &>().empty()) continue;
    const std::string &name = label->get_ref<const std::string &>();
    if (std::find(labels.begin(), labels.end(), name) == labels.end()) labels.push_back(name);
  }
  return labels;
}

std::vector<Detection> DetectionsFromJson(const nlohmann::json &doc, float scale_x, float scale_y) {
  std::vector<Detection> found;
  if (!doc.is_object()) return found;
  const nlohmann::json *list = Field(doc, {"detections", "predictions"});
  if (!list or !list->is_array()) return found;

  for (const nlohmann::json &entry : *list) {
    if (!entry.is_object()) continue;
    const nlohmann::json *label = Field(entry, {"label", "class", "name"});
    const nlohmann::json *confidence = Field(entry, {"confidence", "score"});
    if (!label or !label->is_string() or !confidence or !confidence->is_number()) continue;

    float box[4];
    Detection detection;
    if (FourNumbers(Field(entry, {"bbox"}), box)) {          // x, y, w, h
      detection.x = box[0];
      detection.y = box[1];
      detection.w = box[2];
      detection.h = box[3];
    } else if (FourNumbers(Field(entry, {"box"}), box)) {    // x1, y1, x2, y2
      detection.x = box[0];
      detection.y = box[1];
      detection.w = box[2] - box[0];
      detection.h = box[3] - box[1];
    } else {
      continue;
    }
    detection.label = label->get<std::string>();
    detection.confidence = confidence->get<float>();
    const nlohmann::json *class_id = Field(entry, {"class_id"});
    if (class_id and class_id->is_number_integer()) detection.class_id = class_id->get<int>();

    detection.x *= scale_x;
    detection.w *= scale_x;
    detection.y *= scale_y;
    detection.h *= scale_y;
    found.push_back(std::move(detection));
  }
  return found;
}
#endif

}  // namespace ai
}  // namespace zm
