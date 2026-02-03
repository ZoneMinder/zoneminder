//
// ZoneMinder Monitor::Go2RTCManager Class Implementation
// Copyright (C) 2025 Ben Dailey
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

#include <algorithm>

#include "zm_crypt.h"
#include "zm_monitor.h"
#include "zm_server.h"
#include "zm_time.h"
#include "zm_user.h"
#include "zm_utils.h"

Monitor::Go2RTCManager::Go2RTCManager(Monitor *parent_)
    : parent(parent_), Go2RTC_Healthy(false) {

  Debug(1, "Go2RTC: Initializing Go2RTCManager for monitor %s (%d)", parent->Name(), parent->Id());

  if ((config.go2rtc_path != nullptr) && (config.go2rtc_path[0] != '\0')) {
    Go2RTC_endpoint = config.go2rtc_path;
    // remove the trailing slash if present
    if (Go2RTC_endpoint.back() == '/') Go2RTC_endpoint.pop_back();
    Debug(1, "Go2RTC: Using configured endpoint: %s", Go2RTC_endpoint.c_str());
  } else {
    Go2RTC_endpoint = "demo:demo@127.0.0.1:1984";
    Warning("Go2RTC: No endpoint configured in ZM_GO2RTC_PATH, using default: %s", Go2RTC_endpoint.c_str());
  }

  Use_RTSP_Restream = parent->RTSPServer();
  if (Use_RTSP_Restream) {
    if (parent->server_id) {
      Server server(parent->server_id);
      rtsp_restream_base_path = "rtsp://"+server.Hostname();
    } else {
      rtsp_restream_base_path = "rtsp://127.0.0.1";
    }
    rtsp_restream_base_path += ":" + std::to_string(config.min_rtsp_port) + "/" + parent->rtsp_streamname;
    rtsp_restream_path = rtsp_restream_base_path;
    if (ZM_OPT_USE_AUTH) {
      if (parent->rtsp_user) {
        User *rtsp_user = User::find(parent->rtsp_user);
        std::string auth_key = rtsp_user->getAuthHash();
        rtsp_restream_path += "?auth=" + auth_key;
        last_auth_refresh = std::chrono::system_clock::now();
      } else {
        Warning("No user selected for RTSP_Server authentication!");
      }
    }  // end if ZM_OPT_USE_AUTH
  }  // end if User_RTSP_REstream

  rtsp_path = parent->path;
  rtsp_second_path = parent->GetSecondPath();

  if (!parent->user.empty()) {
    rtsp_username = escape_json_string(parent->user);
    rtsp_password = escape_json_string(parent->pass);
    if (rtsp_path.find("rtsp://") == 0) {
      rtsp_path = "rtsp://" + rtsp_username + ":" + rtsp_password + "@" + rtsp_path.substr(7, std::string::npos);
    } else {
      rtsp_path = "rtsp://" + rtsp_username + ":" + rtsp_password + "@" + rtsp_path;
    }
    if (!rtsp_second_path.empty()) {
      if (rtsp_second_path.find("rtsp://") == 0) {
        rtsp_second_path = "rtsp://" + rtsp_username + ":" + rtsp_password + "@" + rtsp_second_path.substr(7, std::string::npos);
      } else {
        rtsp_second_path = "rtsp://" + rtsp_username + ":" + rtsp_password + "@" + rtsp_second_path;
      }
    }
  }  // end if !user.empty

  Debug(1, "Go2RTC: Primary RTSP path: %s", rtsp_path.c_str());
  if (!rtsp_second_path.empty()) {
    Debug(1, "Go2RTC: Secondary RTSP path: %s", rtsp_second_path.c_str());
  }
  if (!rtsp_restream_path.empty()) {
    Debug(1, "Go2RTC: ZM RTSP restream path: %s", rtsp_restream_path.c_str());
  }
}

Monitor::Go2RTCManager::~Go2RTCManager() { remove_from_Go2RTC(); }

int Monitor::Go2RTCManager::check_Go2RTC() {
  Debug(2, "Go2RTC: check_Go2RTC called for monitor %s (%d)", parent->Name(), parent->id);

  // Check if auth_hash needs refreshing (called periodically)
  if (refresh_auth_if_needed()) {
    // Auth was refreshed and streams were re-registered
    return 1;
  }

  CURL *curl = curl_easy_init();
  if (!curl) {
    Error("Go2RTC: Failed to init curl in check_Go2RTC");
    return -1;
  }

  // Assemble our actual request
  std::string endpoint = Go2RTC_endpoint + "/streams?src=" + std::to_string(parent->id);
  std::string response;

  Debug(2, "Go2RTC: Checking endpoint: %s", endpoint.c_str());

  curl_easy_setopt(curl, CURLOPT_URL, endpoint.c_str());
  curl_easy_setopt(curl, CURLOPT_WRITEFUNCTION, WriteCallback);
  curl_easy_setopt(curl, CURLOPT_WRITEDATA, &response);
  // Try with SSL verification enabled first
  curl_easy_setopt(curl, CURLOPT_SSL_VERIFYHOST, 2);
  curl_easy_setopt(curl, CURLOPT_SSL_VERIFYPEER, 1);
  CURLcode res = curl_easy_perform(curl);
  
  // If SSL verification failed, retry without verification
  if (res == CURLE_SSL_CACERT || res == CURLE_SSL_PEER_CERTIFICATE || res == CURLE_SSL_CACERT_BADFILE || 
      res == CURLE_SSL_CERTPROBLEM || res == CURLE_PEER_FAILED_VERIFICATION) {
    Warning("Go2RTC: SSL certificate verification failed for %s (%s), retrying without verification", 
            endpoint.c_str(), curl_easy_strerror(res));
    response.clear();
    curl_easy_reset(curl);
    curl_easy_setopt(curl, CURLOPT_URL, endpoint.c_str());
    curl_easy_setopt(curl, CURLOPT_WRITEFUNCTION, WriteCallback);
    curl_easy_setopt(curl, CURLOPT_WRITEDATA, &response);
    curl_easy_setopt(curl, CURLOPT_SSL_VERIFYHOST, 0);
    curl_easy_setopt(curl, CURLOPT_SSL_VERIFYPEER, 0);
    res = curl_easy_perform(curl);
  }
  curl_easy_cleanup(curl);

  response = remove_newlines(response);
  if (res != CURLE_OK) {
    Warning("Go2RTC: Check failed - attempted %s and got %s", endpoint.c_str(), curl_easy_strerror(res));
    return -1;
  }

  // Check for the primary path (restream if enabled, otherwise direct camera path)
  const std::string &primary_path = Use_RTSP_Restream ? rtsp_restream_path : rtsp_path;

  Debug(2, "Go2RTC: Check response: %s", response.c_str());
  Debug(2, "Go2RTC: Looking for primary path: %s", primary_path.c_str());

  if (response.find(primary_path) == std::string::npos) {
    Debug(1, "Go2RTC: Stream not found in Go2RTC (expected path '%s' not in response)", primary_path.c_str());
    return 0;
  }

  Debug(1, "Go2RTC: Stream found in Go2RTC");
  return 1;
}

bool Monitor::Go2RTCManager::refresh_auth_if_needed() {
  // Only refresh if using RTSP restream with auth
  if (!Use_RTSP_Restream || !ZM_OPT_USE_AUTH || !parent->rtsp_user) {
    return false;
  }

  // Check if auth hash is older than 50 minutes (refresh before 1-hour timeout)
  auto now = std::chrono::system_clock::now();
  auto age = std::chrono::duration_cast<std::chrono::minutes>(now - last_auth_refresh);

  if (age.count() < 50) {
    Debug(3, "Go2RTC: Auth hash is %ld minutes old, no refresh needed", age.count());
    return false;
  }

  Debug(1, "Go2RTC: Auth hash is %ld minutes old, refreshing", age.count());

  User *rtsp_user = User::find(parent->rtsp_user);
  if (!rtsp_user) {
    Warning("Go2RTC: Could not find RTSP user %d for auth refresh", parent->rtsp_user);
    return false;
  }

  std::string auth_key = rtsp_user->getAuthHash();
  std::string old_path = rtsp_restream_path;
  rtsp_restream_path = rtsp_restream_base_path + "?auth=" + auth_key;
  last_auth_refresh = now;

  Debug(1, "Go2RTC: Auth refreshed, new restream path: %s", rtsp_restream_path.c_str());

  // Re-register with go2rtc using the updated auth
  if (add_to_Go2RTC() == 0) {
    Debug(1, "Go2RTC: Successfully updated streams with refreshed auth");
    return true;
  } else {
    Warning("Go2RTC: Failed to update streams with refreshed auth");
    return false;
  }
}

int Monitor::Go2RTCManager::add_to_Go2RTC() {
  Debug(1, "Go2RTC: add_to_Go2RTC called for monitor %s (%d)", parent->Name(), parent->Id());

  CURL *curl = curl_easy_init();
  if (!curl) {
    Error("Go2RTC: Failed to init curl");
    return -1;
  }

  // When RTSP restreamer is enabled, use it as the primary stream source.
  // This avoids go2rtc connecting directly to the camera (which may have
  // connection limits) and provides a single point of access.
  const std::string &primary_path = Use_RTSP_Restream ? rtsp_restream_path : rtsp_path;
  const std::string id_str = std::to_string(parent->Id());

  // Add primary stream using just the monitor ID (for backward compatibility)
  Debug(1, "Go2RTC: Adding primary stream (monitor ID) - path: %s%s",
        primary_path.c_str(), Use_RTSP_Restream ? " (via RTSP restreamer)" : "");
  std::string endpoint = Go2RTC_endpoint + "/streams?name=" + id_str + "&src=" + UriEncode(primary_path);
  std::string postData = "{\"name\" : \"" + std::string(parent->Name()) + "\", \"src\": \"" + primary_path + "\" }";
  Debug(2, "Go2RTC: PUT to %s with data: %s", endpoint.c_str(), postData.c_str());
  std::pair<CURLcode, std::string> response = CURL_PUT(endpoint, postData);
  if (response.first != CURLE_OK) {
    curl_easy_cleanup(curl);
    Warning("Go2RTC: Failed to add primary stream (monitor ID)");
    return -1;
  }
  Debug(1, "Go2RTC: Successfully added primary stream (monitor ID), response: %s", response.second.c_str());

  // Add ZoneMinder restream paths (when RTSP restreamer is enabled)
  if (Use_RTSP_Restream) {
    Debug(1, "Go2RTC: Adding ZoneMinderPrimary stream - path: %s", rtsp_restream_path.c_str());
    endpoint = Go2RTC_endpoint + "/streams?name=" + id_str + "_ZoneMinderPrimary&src=" + UriEncode(rtsp_restream_path);
    postData = "{\"name\" : \"" + std::string(parent->Name()) + " ZoneMinder Primary\", \"src\": \"" + rtsp_restream_path + "\" }";
    Debug(2, "Go2RTC: PUT to %s", endpoint.c_str());
    response = CURL_PUT(endpoint, postData);
    if (response.first == CURLE_OK) {
      Debug(1, "Go2RTC: Successfully added ZoneMinderPrimary, response: %s", response.second.c_str());
    }
  }

  // Add direct camera paths
  if (!rtsp_path.empty()) {
    Debug(1, "Go2RTC: Adding CameraDirectPrimary stream - path: %s", rtsp_path.c_str());
    endpoint = Go2RTC_endpoint + "/streams?name=" + id_str + "_CameraDirectPrimary&src=" + UriEncode(rtsp_path);
    postData = "{\"name\" : \"" + std::string(parent->Name()) + " Camera Direct Primary\", \"src\": \"" + rtsp_path + "\" }";
    Debug(2, "Go2RTC: PUT to %s", endpoint.c_str());
    response = CURL_PUT(endpoint, postData);
    if (response.first == CURLE_OK) {
      Debug(1, "Go2RTC: Successfully added CameraDirectPrimary, response: %s", response.second.c_str());
    }
  }

  if (!rtsp_second_path.empty()) {
    Debug(1, "Go2RTC: Adding CameraDirectSecondary stream - path: %s", rtsp_second_path.c_str());
    endpoint = Go2RTC_endpoint + "/streams?name=" + id_str + "_CameraDirectSecondary&src=" + UriEncode(rtsp_second_path);
    postData = "{\"name\" : \"" + std::string(parent->Name()) + " Camera Direct Secondary\", \"src\": \"" + rtsp_second_path + "\" }";
    Debug(2, "Go2RTC: PUT to %s", endpoint.c_str());
    response = CURL_PUT(endpoint, postData);
    if (response.first == CURLE_OK) {
      Debug(1, "Go2RTC: Successfully added CameraDirectSecondary, response: %s", response.second.c_str());
    }
  } else {
    Debug(2, "Go2RTC: No secondary camera path configured");
  }

  curl_easy_cleanup(curl);
  Debug(1, "Go2RTC: Finished adding streams for monitor %d", parent->Id());
  return 0;
}

int Monitor::Go2RTCManager::remove_from_Go2RTC() {
  Debug(1, "Go2RTC: remove_from_Go2RTC called for monitor %s (%d)", parent->Name(), parent->Id());

  std::string endpoint = Go2RTC_endpoint + "/streams?src="+std::to_string(parent->Id());
  std::string response;

  Debug(2, "Go2RTC: DELETE request to: %s", endpoint.c_str());

  CURL *curl = curl_easy_init();
  if (!curl) {
    Error("Go2RTC: Failed to init curl in remove_from_Go2RTC");
    return -1;
  }
  curl_easy_setopt(curl, CURLOPT_URL, endpoint.c_str());
  curl_easy_setopt(curl, CURLOPT_CUSTOMREQUEST, "DELETE");
  curl_easy_setopt(curl, CURLOPT_WRITEFUNCTION, WriteCallback);
  curl_easy_setopt(curl, CURLOPT_WRITEDATA, &response);
  // Try with SSL verification enabled first
  curl_easy_setopt(curl, CURLOPT_SSL_VERIFYHOST, 2);
  curl_easy_setopt(curl, CURLOPT_SSL_VERIFYPEER, 1);

  CURLcode res = curl_easy_perform(curl);
  
  // If SSL verification failed, retry without verification
  if (res == CURLE_SSL_CACERT || res == CURLE_SSL_PEER_CERTIFICATE || res == CURLE_SSL_CACERT_BADFILE || 
      res == CURLE_SSL_CERTPROBLEM || res == CURLE_PEER_FAILED_VERIFICATION) {
    Warning("Go2RTC: SSL certificate verification failed for %s (%s), retrying without verification", 
            endpoint.c_str(), curl_easy_strerror(res));
    response.clear();
    curl_easy_reset(curl);
    curl_easy_setopt(curl, CURLOPT_URL, endpoint.c_str());
    curl_easy_setopt(curl, CURLOPT_CUSTOMREQUEST, "DELETE");
    curl_easy_setopt(curl, CURLOPT_WRITEFUNCTION, WriteCallback);
    curl_easy_setopt(curl, CURLOPT_WRITEDATA, &response);
    curl_easy_setopt(curl, CURLOPT_SSL_VERIFYHOST, 0);
    curl_easy_setopt(curl, CURLOPT_SSL_VERIFYPEER, 0);
    res = curl_easy_perform(curl);
  }
  
  if (res != CURLE_OK) {
    Warning("Go2RTC: Delete failed - attempted %s got %s", endpoint.c_str(), curl_easy_strerror(res));
  } else {
    Debug(1, "Go2RTC: Successfully removed stream, response: %s", response.c_str());
  }

  curl_easy_cleanup(curl);
  return 0;
}

size_t Monitor::Go2RTCManager::ReadCallback(char *ptr, size_t size, size_t nmemb, void *data) {
  struct transfer * tr = static_cast<struct transfer *>(data);
  size_t left = tr->total - tr->uploaded;
  size_t max_chunk = size * nmemb;
  size_t retcode = left < max_chunk ? left : max_chunk;

  memcpy(ptr, tr->buf + tr->uploaded, retcode); // <-- voodoo-mumbo-jumbo :-)

  tr->uploaded += retcode;  // <-- save progress
  return retcode;
}

size_t Monitor::Go2RTCManager::WriteCallback(void *contents, size_t size, size_t nmemb, void *userp) {
  ((std::string *)userp)->append((char *)contents, size * nmemb);
  return size * nmemb;
}

std::pair<CURLcode, std::string> Monitor::Go2RTCManager::CURL_PUT(const std::string &endpoint, const std::string &data) const {
  std::string response;

  CURL *curl = curl_easy_init();
  if (!curl) {
    Error("Go2RTC: Failed to init curl");
    return std::make_pair(CURLE_FAILED_INIT, response);
  }
  Debug(1, "Go2RTC Sending %s to %s", data.c_str(), endpoint.c_str());
  curl_easy_setopt(curl, CURLOPT_URL, endpoint.c_str());
  curl_easy_setopt(curl, CURLOPT_CUSTOMREQUEST, "PUT"); /* !!! */
  curl_easy_setopt(curl, CURLOPT_WRITEFUNCTION, WriteCallback);
  curl_easy_setopt(curl, CURLOPT_WRITEDATA, &response);
  curl_easy_setopt(curl, CURLOPT_POSTFIELDS, data.c_str());
  // Try with SSL verification enabled first
  curl_easy_setopt(curl, CURLOPT_SSL_VERIFYHOST, 2);
  curl_easy_setopt(curl, CURLOPT_SSL_VERIFYPEER, 1);
  CURLcode res = curl_easy_perform(curl);
  
  // If SSL verification failed, retry without verification
  if (res == CURLE_SSL_CACERT || res == CURLE_SSL_PEER_CERTIFICATE || res == CURLE_SSL_CACERT_BADFILE || 
      res == CURLE_SSL_CERTPROBLEM || res == CURLE_PEER_FAILED_VERIFICATION) {
    Warning("Go2RTC: SSL certificate verification failed for %s (%s), retrying without verification", 
            endpoint.c_str(), curl_easy_strerror(res));
    response.clear();
    curl_easy_reset(curl);
    curl_easy_setopt(curl, CURLOPT_URL, endpoint.c_str());
    curl_easy_setopt(curl, CURLOPT_CUSTOMREQUEST, "PUT");
    curl_easy_setopt(curl, CURLOPT_WRITEFUNCTION, WriteCallback);
    curl_easy_setopt(curl, CURLOPT_WRITEDATA, &response);
    curl_easy_setopt(curl, CURLOPT_POSTFIELDS, data.c_str());
    curl_easy_setopt(curl, CURLOPT_SSL_VERIFYHOST, 0);
    curl_easy_setopt(curl, CURLOPT_SSL_VERIFYPEER, 0);
    res = curl_easy_perform(curl);
  }
  
  curl_easy_cleanup(curl);

  if (res != CURLE_OK) {
    Error("Go2RTC Failed to curl_easy_perform adding rtsp stream %s", curl_easy_strerror(res));
  } else {
    Debug(1, "Go2RTC Added stream response: %s", response.c_str());
  }
  return std::make_pair(res, response);
}


