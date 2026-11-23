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

#include "zm_detector_http.h"

#if HAVE_LIBCURL && ZM_HAS_NLOHMANN_JSON

#include "zm_image.h"
#include "zm_logger.h"
#include "zm_packet.h"
#include "zm_utils.h"

#include <mutex>

namespace zm {
namespace ai {

namespace {

// A request is answered within this, or the frame is given up on. The
// analysis thread waits for it, and InferenceBehind() keeps a slow server from
// putting analysis further behind.
constexpr long kRequestTimeoutMs = 5000;
constexpr long kConnectTimeoutMs = 2000;

size_t AppendResponse(char *data, size_t size, size_t count, void *response) {
  static_cast<std::string *>(response)->append(data, size * count);
  return size * count;
}

bool IsHttpUrl(const std::string &url) {
  return StartsWith(url, "http://") or StartsWith(url, "https://");
}

}  // namespace

HttpDetector::~HttpDetector() {
  if (curl_) curl_easy_cleanup(curl_);
}

bool HttpDetector::Setup(const DetectorConfig &config) {
  if (!IsHttpUrl(config.model)) {
    Error("Object detection http backend: the model for monitor %u must be an http:// or https:// URL, not '%s'",
          config.monitor_id, config.model.c_str());
    return false;
  }
  static std::once_flag curl_initialised;
  std::call_once(curl_initialised, [] { curl_global_init(CURL_GLOBAL_ALL); });

  if (curl_) curl_easy_cleanup(curl_);
  curl_ = curl_easy_init();
  if (!curl_) {
    Error("Object detection http backend: curl_easy_init failed");
    return false;
  }
  config_ = config;
  return true;
}

DetectStatus HttpDetector::Detect(ZMPacket &packet, std::vector<Detection> &out) {
  out.clear();
  if (!curl_) return DetectStatus::Failed;
  if (!packet.image) return DetectStatus::Pending;  // nothing decoded to send

  const Image &image = *packet.image;
  // JPEG output is not bounded by the buffer, so size it for the worst case.
  std::vector<JOCTET> jpeg(static_cast<size_t>(image.Width()) * image.Height() * 3 + 65536);
  size_t jpeg_size = 0;
  if (!image.EncodeJpeg(jpeg.data(), &jpeg_size) or !jpeg_size) {
    Warning("Object detection http backend: could not encode the frame for monitor %u", config_.monitor_id);
    return DetectStatus::Pending;
  }

  curl_easy_reset(curl_);
  curl_mime *form = curl_mime_init(curl_);
  curl_mimepart *part = curl_mime_addpart(form);
  curl_mime_name(part, "file");
  curl_mime_filename(part, stringtf("monitor%u.jpg", config_.monitor_id).c_str());
  curl_mime_type(part, "image/jpeg");
  curl_mime_data(part, reinterpret_cast<const char *>(jpeg.data()), jpeg_size);
  part = curl_mime_addpart(form);
  curl_mime_name(part, "monitor_id");
  curl_mime_data(part, std::to_string(config_.monitor_id).c_str(), CURL_ZERO_TERMINATED);

  // Without this curl waits for a 100 Continue that many servers never send.
  curl_slist *headers = curl_slist_append(nullptr, "Expect:");

  std::string response;
  curl_easy_setopt(curl_, CURLOPT_URL, config_.model.c_str());
  curl_easy_setopt(curl_, CURLOPT_MIMEPOST, form);
  curl_easy_setopt(curl_, CURLOPT_HTTPHEADER, headers);
  curl_easy_setopt(curl_, CURLOPT_WRITEFUNCTION, AppendResponse);
  curl_easy_setopt(curl_, CURLOPT_WRITEDATA, &response);
  curl_easy_setopt(curl_, CURLOPT_TIMEOUT_MS, kRequestTimeoutMs);
  curl_easy_setopt(curl_, CURLOPT_CONNECTTIMEOUT_MS, kConnectTimeoutMs);
  curl_easy_setopt(curl_, CURLOPT_NOSIGNAL, 1L);
#if LIBCURL_VERSION_NUM >= 0x075500  // 7.85.0
  curl_easy_setopt(curl_, CURLOPT_PROTOCOLS_STR, "http,https");
#else
  curl_easy_setopt(curl_, CURLOPT_PROTOCOLS, CURLPROTO_HTTP | CURLPROTO_HTTPS);
#endif

  const CURLcode result = curl_easy_perform(curl_);
  long status = 0;
  curl_easy_getinfo(curl_, CURLINFO_RESPONSE_CODE, &status);
  curl_slist_free_all(headers);
  curl_mime_free(form);

  if (result != CURLE_OK) {
    Warning("Object detection http backend: request to %s failed: %s",
            config_.model.c_str(), curl_easy_strerror(result));
    return DetectStatus::Failed;
  }
  if (status < 200 or status > 299) {
    Warning("Object detection http backend: %s answered with status %ld", config_.model.c_str(), status);
    return DetectStatus::Failed;
  }

  const nlohmann::json doc = nlohmann::json::parse(response, nullptr, false);
  if (doc.is_discarded()) {
    Warning("Object detection http backend: %s did not answer with JSON", config_.model.c_str());
    return DetectStatus::Failed;
  }
  out = DetectionsFromJson(doc, 1.0f, 1.0f);
  Debug(2, "Object detection http backend: %zu detections for monitor %u", out.size(), config_.monitor_id);
  return DetectStatus::Done;
}

}  // namespace ai
}  // namespace zm

#endif  // HAVE_LIBCURL && ZM_HAS_NLOHMANN_JSON
