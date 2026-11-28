/*
 * ZoneMinder object detection: http backend
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

#ifndef ZM_DETECTOR_HTTP_H
#define ZM_DETECTOR_HTTP_H

#include "zm_detector.h"

#if HAVE_LIBCURL && ZM_HAS_NLOHMANN_JSON

#include <curl/curl.h>

namespace zm {
namespace ai {

/* Sends each frame to an inference server over HTTP, so detection can run
 * anywhere: another machine, a GPU box, or a container with any framework.
 *
 * Request: POST to the monitor's ObjectDetectionModel URL, multipart/form-data
 * with "file" (the frame as JPEG, at the frame's size) and "monitor_id".
 * Response: JSON {"detections":[{"label","confidence","bbox":[x,y,w,h],"class_id"}]}
 * in pixels of the JPEG sent. The {"predictions":[{"class","score","box":[x1,y1,x2,y2]}]}
 * answer of ai_server's remote API is read as well.
 */
class HttpDetector : public Detector {
 public:
  HttpDetector() = default;
  ~HttpDetector() override;
  HttpDetector(const HttpDetector &) = delete;
  HttpDetector &operator=(const HttpDetector &) = delete;

  const char *Name() const override { return "http"; }
  bool Setup(const DetectorConfig &config) override;
  DetectStatus Detect(ZMPacket &packet, std::vector<Detection> &out) override;

 private:
  DetectorConfig config_;
  CURL *curl_ = nullptr;
};

}  // namespace ai
}  // namespace zm

#endif  // HAVE_LIBCURL && ZM_HAS_NLOHMANN_JSON
#endif  // ZM_DETECTOR_HTTP_H
