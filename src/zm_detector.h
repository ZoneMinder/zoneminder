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

#ifndef ZM_DETECTOR_H
#define ZM_DETECTOR_H

#include "zm_config.h"
#include "zm_rgb.h"

#include <cstdint>
#include <map>
#include <memory>
#include <string>
#include <vector>

#if ZM_HAS_NLOHMANN_JSON
#include <nlohmann/json.hpp>
#endif

class Image;
class ZMPacket;

/* Object detection for zmc.
 *
 * A monitor whose ObjectDetection column names a backend gets one Detector,
 * used only by its analysis thread. Monitor does everything around inference
 * the same way for every backend: which frames to run on, per-class filtering
 * from AI_Detection_Settings, drawing the boxes, storing the detections with
 * the event, and deciding whether they raise an alarm. A backend only turns a
 * frame into detections.
 *
 * Adding a backend: subclass Detector, then add it to MakeDetector() and
 * DetectorNames() in zm_detector.cpp under its build option.
 *
 * Detections and their stored JSON follow zm-next's detection event
 * ({label, confidence, bbox:[x,y,w,h], class_id}), so tools reading events can
 * treat both the same way.
 */
namespace zm {
namespace ai {

struct Detection {
  std::string label;
  float confidence = 0;            // 0 to 1
  float x = 0, y = 0, w = 0, h = 0;  // top left and size, in pixels of the frame given to Detect()
  int class_id = -1;               // the model's class index, or -1 when the backend has none
};

struct DetectorConfig {
  unsigned int monitor_id = 0;
  // Backend specific: a model file, or for the http backend the URL to post to.
  std::string model;
  float object_threshold = 0.4f;   // 0 to 1; the floor for classes without a setting
  float nms_threshold = 0.25f;
  unsigned int width = 0;          // frame size
  unsigned int height = 0;
};

enum class DetectStatus {
  Done,     // out holds the result of the latest completed inference, possibly empty
  Pending,  // no new result this call: submitted to queued hardware whose result
            // comes back later, or the packet had nothing to run on
  Failed    // the backend is unusable; the monitor drops it and sets up a new one later
};

class Detector {
 public:
  virtual ~Detector() = default;
  virtual const char *Name() const = 0;
  // Called before the first Detect(). False when the backend cannot run with
  // this configuration; the reason is logged by the backend.
  virtual bool Setup(const DetectorConfig &config) = 0;
  // Runs inference on the packet's frame. The whole packet is passed so a
  // backend can use whichever frame it was set up for: the decoded image, the
  // software frame, or the device frame (hw_frame).
  virtual DetectStatus Detect(ZMPacket &packet, std::vector<Detection> &out) = 0;
  // A backend that draws on frames itself (for example with hardware filters)
  // does so here and returns true. Otherwise Monitor draws the boxes on the
  // analysis image.
  virtual bool Annotate(ZMPacket &packet, const std::vector<Detection> &detections) {
    return false;
  }
};

// The backend called name, or nullptr when it isn't compiled in.
std::unique_ptr<Detector> MakeDetector(const std::string &name);
// The backends compiled into this build.
std::vector<std::string> DetectorNames();

// One AI_Detection_Settings row.
struct ClassSetting {
  bool enabled = true;
  int confidence_threshold = 50;  // percent
  bool report = true;             // ReportDetection: the class raises alarms
  Rgb box_color = kRGBRed;
};
using ClassSettings = std::map<std::string, ClassSetting>;  // by class name

// "#RRGGBB" (the # is optional) as an Rgb. Anything else is red.
Rgb ParseBoxColor(const std::string &hex);

// The detections whose class is enabled and at or over its threshold. Classes
// without a setting are kept at or over object_threshold (0 to 1).
std::vector<Detection> FilterDetections(const std::vector<Detection> &detections,
                                        const ClassSettings &settings,
                                        float object_threshold);

// The alarm score for detections: the highest confidence of a class that
// reports, as a percentage, or 0 when none does.
int AlarmScore(const std::vector<Detection> &detections, const ClassSettings &settings);
// The classes that report, each once, in the order first seen.
std::vector<std::string> ReportedLabels(const std::vector<Detection> &detections, const ClassSettings &settings);
// ReportedLabels() as the alarm cause: "person, dog".
std::string AlarmCause(const std::vector<Detection> &detections, const ClassSettings &settings);

// Whether inference should be skipped because it has fallen lag_seconds
// behind real time. Falling behind happens at behind_seconds and catching up
// only under half of that, so a lag sitting near the threshold doesn't flap.
bool InferenceBehind(bool was_behind, double lag_seconds, double behind_seconds = 2.0);

// Outlines each detection on image in its class's colour, labelled with the
// class and confidence.
void DrawDetections(Image &image, const std::vector<Detection> &detections,
                    const ClassSettings &settings, int label_size);

#if ZM_HAS_NLOHMANN_JSON
// zm-next's detection event for detections found on the frame at pts_usec.
nlohmann::json DetectionsToJson(const std::vector<Detection> &detections, int64_t pts_usec);
// The class of each detection in a document from DetectionsToJson(), once
// each, in the order first seen. Used to tag the event.
std::vector<std::string> DetectionLabels(const nlohmann::json &doc);
// Detections from a backend's response, scaled by scale_x and scale_y back to
// the frame. Reads {"detections":[{label, confidence, bbox:[x,y,w,h], class_id}]}
// and the {"predictions":[{class, score, box:[x1,y1,x2,y2]}]} that ai_server's
// remote API returned. Entries that can't be read are skipped.
std::vector<Detection> DetectionsFromJson(const nlohmann::json &doc, float scale_x, float scale_y);
#endif

}  // namespace ai
}  // namespace zm

#endif  // ZM_DETECTOR_H
