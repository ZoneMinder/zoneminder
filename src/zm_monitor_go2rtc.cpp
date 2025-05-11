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
#include "zm_utils.h"


Monitor::Go2RTCManager::Go2RTCManager(Monitor *parent_)
    : parent(parent_), Go2RTC_Healthy(false) {
  Use_RTSP_Restream = false;
  if ((config.go2rtc_api_path != nullptr) && (config.go2rtc_api_path[0] != '\0')) {
    Go2RTC_endpoint = config.go2rtc_api_path;
    // remove the trailing slash if present
    if (Go2RTC_endpoint.back() == '/') Go2RTC_endpoint.pop_back();
  } else {
    Go2RTC_endpoint = "demo:demo@127.0.0.1:1984";
  }

  rtsp_path = parent->path;
  rtsp_second_path = parent->GetSecondPath();

  if (!parent->user.empty()) {
    rtsp_username = escape_json_string(parent->user);
    rtsp_password = escape_json_string(parent->pass);
    if (rtsp_path.find("rtsp://") == 0) {
      rtsp_path = "rtsp://" + rtsp_username + ":" + rtsp_password + "@" +
                  rtsp_path.substr(7, std::string::npos);
    } else {
      rtsp_path =
          "rtsp://" + rtsp_username + ":" + rtsp_password + "@" + rtsp_path;
    }
    if (rtsp_second_path.find("rtsp://") == 0) {
      rtsp_second_path = "rtsp://" + rtsp_username + ":" + rtsp_password + "@" +
                         rtsp_second_path.substr(7, std::string::npos);
    } else {
      rtsp_second_path = "rtsp://" + rtsp_username + ":" + rtsp_password + "@" +
                         rtsp_second_path;
    }
  }  // end if !user.empty
  Debug(1, "Monitor %u rtsp url is %s", parent->id, rtsp_path.c_str());
}

Monitor::Go2RTCManager::~Go2RTCManager() { remove_from_Go2RTC(); }

int Monitor::Go2RTCManager::check_Go2RTC() {
  curl = curl_easy_init();
  if (!curl) {
    Error("Failed to init curl");
    return -1;
  }

  // Assemble our actual request
  std::string response;
  std::string endpoint = Go2RTC_endpoint + "/stream/" + std::to_string(parent->id) + "/info";
  curl_easy_setopt(curl, CURLOPT_URL, endpoint.c_str());
  curl_easy_setopt(curl, CURLOPT_WRITEFUNCTION, WriteCallback);
  curl_easy_setopt(curl, CURLOPT_WRITEDATA, &response);
  curl_easy_setopt(curl, CURLOPT_SSL_VERIFYHOST, 0);
  curl_easy_setopt(curl, CURLOPT_SSL_VERIFYPEER, 0);
  curl_easy_setopt(curl, CURLOPT_VERBOSE, 1);
  CURLcode res = curl_easy_perform(curl);
  curl_easy_cleanup(curl);

  if (res != CURLE_OK) {
    Warning("Attempted to send to %s and got %s", endpoint.c_str(), curl_easy_strerror(res));
    return -1;
  }

  response = remove_newlines(response);
  Debug(1, "Queried for stream status: %s", response.c_str());
  if (response.find("\"status\": 0") != std::string::npos) {
    if (response.find("stream not found") != std::string::npos) {
      Debug(1, "Mountpoint Missing");
      return 0;
    } else {
      Warning("unknown Go2RTC error");
      return 0;
    }
  }

  return 1;
}

int Monitor::Go2RTCManager::add_to_Go2RTC() {
  Debug(1, "Adding stream to Go2RTC");
  curl = curl_easy_init();
  if (!curl) {
    Error("Failed to init curl");
    return -1;
  }

  std::string endpoint = Go2RTC_endpoint + "/api/streams";
  endpoint += "?name="+std::to_string(parent->Id())+"&src="+UriEncode(rtsp_path);

  // Assemble our actual request
  std::string postData =
      "{\"name\" : \"" + std::string(parent->Name()) + "\", " +
      "\"src\": \""+rtsp_path+"\" " +
      "}";
      //"\", \"channels\" : {"
      //" \"0\" : {"
      //"  \"name\" : \"ch1\", \"audio\" : true, \"url\" : \"" +
      //rtsp_path + "\", \"on_demand\": true, \"debug\": false, \"status\": 0}";
  //if (!rtsp_second_path.empty()) {
    //postData +=
        //", \"1\" : {"
        //"  \"name\" : \"ch2\", \"audio\" : true, \"url\" : \"" +
        //rtsp_second_path +
        //"\", \"on_demand\": true, \"debug\": false, \"status\": 0}";
  //}
  //postData +=
      //"}"
      //"}";

  Debug(1, "Go2RTC Sending %s to %s", postData.c_str(), endpoint.c_str());

  CURLcode res;
  std::string response;
  //struct transfer tr = {postData.c_str(), postData.size(), 0};

  curl_easy_setopt(curl, CURLOPT_URL, endpoint.c_str());
  curl_easy_setopt(curl, CURLOPT_CUSTOMREQUEST, "PUT"); /* !!! */
  //curl_easy_setopt(curl, CURLOPT_READFUNCTION, ReadCallback);
  //curl_easy_setopt(curl, CURLOPT_READDATA, &tr);
  //curl_easy_setopt(curl, CURLOPT_UPLOAD, 1L);
  curl_easy_setopt(curl, CURLOPT_WRITEFUNCTION, WriteCallback);
  curl_easy_setopt(curl, CURLOPT_WRITEDATA, &response);
  curl_easy_setopt(curl, CURLOPT_POSTFIELDS, postData.c_str());
  curl_easy_setopt(curl, CURLOPT_SSL_VERIFYHOST, 0);
  curl_easy_setopt(curl, CURLOPT_SSL_VERIFYPEER, 0);
  res = curl_easy_perform(curl);
  curl_easy_cleanup(curl);

  if (res != CURLE_OK) {
    Error("Failed to curl_easy_perform adding rtsp stream %s",
          curl_easy_strerror(res));
    return -1;
  }

  response = remove_newlines(response);
  Debug(1, "Adding stream response: %s", response.c_str());
  // scan for missing session or handle id "No such session" "no such handle"
  if (response.find("\"status\": 1") == std::string::npos) {
    if (response ==
        "{    \"status\": 0,    \"payload\": \"stream already exists\"}") {
      Debug(1, "Go2RTC failed adding stream, response: %s", response.c_str());
    } else {
      Warning("Go2RTC failed adding stream, response: %s", response.c_str());
      return -2;
    }
  } else {
    Debug(1, "Added stream to Go2RTC: %s", response.c_str());
  }

  return 0;
}

int Monitor::Go2RTCManager::remove_from_Go2RTC() {
  curl = curl_easy_init();
  if (!curl) return -1;

  std::string endpoint =
      Go2RTC_endpoint + "/stream/" + std::to_string(parent->id) + "/delete";
  std::string response;

  curl_easy_setopt(curl, CURLOPT_URL, endpoint.c_str());
  curl_easy_setopt(curl, CURLOPT_WRITEFUNCTION, WriteCallback);
  curl_easy_setopt(curl, CURLOPT_WRITEDATA, &response);
  curl_easy_setopt(curl, CURLOPT_SSL_VERIFYHOST, 0);
  curl_easy_setopt(curl, CURLOPT_SSL_VERIFYPEER, 0);

  CURLcode res = curl_easy_perform(curl);
  if (res != CURLE_OK) {
    Warning("Libcurl attempted %s got %s", endpoint.c_str(),
            curl_easy_strerror(res));
  } else {
    Debug(1, "Removed stream from Go2RTC: %s",
          remove_newlines(response).c_str());
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
  Debug(1, "Read: %zu", retcode);

  tr->uploaded += retcode;  // <-- save progress
  return retcode;
}
size_t Monitor::Go2RTCManager::WriteCallback(void *contents, size_t size,
                                             size_t nmemb, void *userp) {
  ((std::string *)userp)->append((char *)contents, size * nmemb);
  return size * nmemb;
}
