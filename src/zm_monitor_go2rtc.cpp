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
      rtsp_restream_path = "rtsp://"+server.Hostname();
    } else {
      rtsp_restream_path = "rtsp://127.0.0.1";
    }
    rtsp_restream_path += ":" + std::to_string(config.min_rtsp_port) + "/" + parent->rtsp_streamname;
    if (ZM_OPT_USE_AUTH) {
      if (parent->janus_rtsp_user) {
        User *rtsp_user = User::find(parent->janus_rtsp_user);
        std::string auth_key = rtsp_user->getAuthHash();
        rtsp_restream_path += "?auth=" + auth_key;
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
    if (rtsp_second_path.find("rtsp://") == 0) {
      rtsp_second_path = "rtsp://" + rtsp_username + ":" + rtsp_password + "@" + rtsp_second_path.substr(7, std::string::npos);
    } else {
      rtsp_second_path = "rtsp://" + rtsp_username + ":" + rtsp_password + "@" + rtsp_second_path;
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
  curl_easy_setopt(curl, CURLOPT_SSL_VERIFYHOST, 0);
  curl_easy_setopt(curl, CURLOPT_SSL_VERIFYPEER, 0);
  CURLcode res = curl_easy_perform(curl);
  curl_easy_cleanup(curl);

  response = remove_newlines(response);
  if (res != CURLE_OK) {
    Warning("Go2RTC: Check failed - attempted %s and got %s", endpoint.c_str(), curl_easy_strerror(res));
    return -1;
  }

  Debug(2, "Go2RTC: Check response: %s", response.c_str());
  Debug(2, "Go2RTC: Looking for RTSP path: %s", rtsp_path.c_str());

  if (response.find(rtsp_path) == std::string::npos) {
    Debug(1, "Go2RTC: Stream not found in Go2RTC (expected path '%s' not in response)", rtsp_path.c_str());
    return 0;
  }

  Debug(1, "Go2RTC: Stream found in Go2RTC");
  return 1;
}

int Monitor::Go2RTCManager::add_to_Go2RTC() {
  Debug(1, "Go2RTC: add_to_Go2RTC called for monitor %s (%d)", parent->Name(), parent->Id());

  CURL *curl = curl_easy_init();
  if (!curl) {
    Error("Go2RTC: Failed to init curl");
    return -1;
  }

  Debug(1, "Go2RTC: Adding primary stream (monitor ID) - RTSP path: %s", rtsp_path.c_str());
  std::string endpoint = Go2RTC_endpoint + "/streams?name="+std::to_string(parent->Id())+"&src="+UriEncode(rtsp_path);
  std::string postData = "{\"name\" : \"" + std::string(parent->Name()) + " channel 0\", \"src\": \""+rtsp_path+"\" }";
  Debug(2, "Go2RTC: PUT to %s with data: %s", endpoint.c_str(), postData.c_str());
  std::pair<CURLcode, std::string> response = CURL_PUT(endpoint, postData);
  if (response.first != CURLE_OK) {
    Warning("Go2RTC: Failed to add primary stream (monitor ID)");
    return -1;
  }
  Debug(1, "Go2RTC: Successfully added primary stream (monitor ID), response: %s", response.second.c_str());

  Debug(1, "Go2RTC: Adding stream with ID_0 format");
  endpoint = Go2RTC_endpoint + "/streams?name="+stringtf("%d_0", parent->Id())+"&src="+UriEncode(rtsp_path);
  postData = "{\"name\" : \"" + std::string(parent->Name()) + " channel 0\", \"src\": \""+rtsp_path+"\" }";
  Debug(2, "Go2RTC: PUT to %s", endpoint.c_str());
  response = CURL_PUT(endpoint, postData);
  if (response.first == CURLE_OK) {
    Debug(1, "Go2RTC: Successfully added stream ID_0, response: %s", response.second.c_str());
  }

  if (!rtsp_second_path.empty()) {
    Debug(1, "Go2RTC: Adding secondary stream (channel 1) - RTSP path: %s", rtsp_second_path.c_str());
    endpoint = Go2RTC_endpoint + "/streams?name="+stringtf("%d_1", parent->Id())+"&src="+UriEncode(rtsp_second_path);
    postData = "{\"name\" : \"" + std::string(parent->Name()) + " channel 1\", \"src\": \""+rtsp_second_path+"\" }";
    Debug(2, "Go2RTC: PUT to %s", endpoint.c_str());
    response = CURL_PUT(endpoint, postData);
    if (response.first == CURLE_OK) {
      Debug(1, "Go2RTC: Successfully added secondary stream, response: %s", response.second.c_str());
    }
  } else {
    Debug(2, "Go2RTC: No secondary stream configured");
  }

  if (!rtsp_restream_path.empty()) {
    Debug(1, "Go2RTC: Adding ZM RTSP restream (channel 2) - path: %s", rtsp_restream_path.c_str());
    endpoint = Go2RTC_endpoint + "/streams?name="+stringtf("%d_2", parent->Id())+"&src="+UriEncode(rtsp_restream_path);
    postData = "{\"name\" : \"" + std::string(parent->Name()) + " channel 2\", \"src\": \""+rtsp_restream_path+"\" }";
    Debug(2, "Go2RTC: PUT to %s", endpoint.c_str());
    response = CURL_PUT(endpoint, postData);
    if (response.first == CURLE_OK) {
      Debug(1, "Go2RTC: Successfully added RTSP restream, response: %s", response.second.c_str());
    }
  } else {
    Debug(2, "Go2RTC: No RTSP restream configured");
  }

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
  curl_easy_setopt(curl, CURLOPT_SSL_VERIFYHOST, 0);
  curl_easy_setopt(curl, CURLOPT_SSL_VERIFYPEER, 0);

  CURLcode res = curl_easy_perform(curl);
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
  curl_easy_setopt(curl, CURLOPT_SSL_VERIFYHOST, 0);
  curl_easy_setopt(curl, CURLOPT_SSL_VERIFYPEER, 0);
  CURLcode res = curl_easy_perform(curl);
  curl_easy_cleanup(curl);

  if (res != CURLE_OK) {
    Error("Go2RTC Failed to curl_easy_perform adding rtsp stream %s", curl_easy_strerror(res));
  } else {
    Debug(1, "Go2RTC Added stream response: %s", response.c_str());
  }
  return std::make_pair(res, response);
}


