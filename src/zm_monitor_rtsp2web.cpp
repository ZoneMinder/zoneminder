//
// ZoneMinder Monitor::RTSP2WebManager Class Implementation, $Date$, $Revision$
// Copyright (C) 2022 Jonathan Bennett
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

#include "zm_crypt.h"
#include "zm_monitor.h"
#include "zm_server.h"
#include "zm_time.h"

#include <algorithm>
#include <regex>

std::string remove_newlines(std::string input);
std::string escape_json_string(std::string input);

Monitor::RTSP2WebManager::RTSP2WebManager(Monitor *parent_) :
  parent(parent_),
  RTSP2Web_Healthy(false) {
  Use_RTSP_Restream = false;
  if ((config.rtsp2web_path != nullptr) && (config.rtsp2web_path[0] != '\0')) {
    RTSP2Web_endpoint = config.rtsp2web_path;
    //remove the trailing slash if present
    if (RTSP2Web_endpoint.back() == '/') RTSP2Web_endpoint.pop_back();
  } else {
    RTSP2Web_endpoint = "demo:demo@127.0.0.1:8083";
  }

  rtsp_path = parent->path;
  if (!parent->user.empty()) {
    rtsp_username = escape_json_string(parent->user);
    rtsp_password = escape_json_string(parent->pass);
    if (rtsp_path.find("rtsp://") == 0) {
      rtsp_path = "rtsp://" + rtsp_username + ":" + rtsp_password + "@" + rtsp_path.substr(7, std::string::npos);
    } else {
      rtsp_path = "rtsp://" + rtsp_username + ":" + rtsp_password + "@" + rtsp_path;
    }
  }
  Debug(1, "Monitor %u rtsp url is %s", parent->id, rtsp_path.c_str());
}

Monitor::RTSP2WebManager::~RTSP2WebManager() {
  remove_from_RTSP2Web();
}

int Monitor::RTSP2WebManager::check_RTSP2Web() {
  curl = curl_easy_init();
  if (!curl) {
    Error("Failed to init curl");
    return -1;
  }

  //Assemble our actual request
  std::string response;
  std::string endpoint = RTSP2Web_endpoint+"/stream/"+std::to_string(parent->id)+"/info";
  curl_easy_setopt(curl, CURLOPT_URL, endpoint.c_str());
  curl_easy_setopt(curl, CURLOPT_WRITEFUNCTION, WriteCallback);
  curl_easy_setopt(curl, CURLOPT_WRITEDATA, &response);
  curl_easy_setopt(curl, CURLOPT_SSL_VERIFYHOST, 0);
  curl_easy_setopt(curl, CURLOPT_SSL_VERIFYPEER, 0);
  //curl_easy_setopt(curl, CURLOPT_VERBOSE, 1);
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
      Warning("unknown RTSP2Web error");
      return 0;
    }
  }

  return 1;
}

int Monitor::RTSP2WebManager::add_to_RTSP2Web() {
  Debug(1, "Adding stream to RTSP2Web");
  curl = curl_easy_init();
  if (!curl) {
    Error("Failed to init curl");
    return -1;
  }

  std::string endpoint = RTSP2Web_endpoint+"/stream/"+std::to_string(parent->id)+"/add";

  //Assemble our actual request
  std::string postData = "{\"name\" : \"";
  postData += std::string(parent->Name());
  postData +=  "\", \"channels\" : { \"0\" : {";
  postData +=  "\"name\" : \"ch1\", \"url\" : \"";
  postData += rtsp_path;
  postData += "\", \"on_demand\": true, \"debug\": false, \"status\": 0}}}";

  Debug(1, "Sending %s to %s", postData.c_str(), endpoint.c_str());

  CURLcode res;
  std::string response;

  curl_easy_setopt(curl, CURLOPT_URL, endpoint.c_str());
  curl_easy_setopt(curl, CURLOPT_WRITEFUNCTION, WriteCallback);
  curl_easy_setopt(curl, CURLOPT_WRITEDATA, &response);
  curl_easy_setopt(curl, CURLOPT_POSTFIELDS, postData.c_str());
  curl_easy_setopt(curl, CURLOPT_SSL_VERIFYHOST, 0);
  curl_easy_setopt(curl, CURLOPT_SSL_VERIFYPEER, 0);
  res = curl_easy_perform(curl);
  curl_easy_cleanup(curl);

  if (res != CURLE_OK) {
    Error("Failed to curl_easy_perform adding rtsp stream %s", curl_easy_strerror(res));
    return -1;
  }

  response = remove_newlines(response);
  Debug(1, "Adding stream response: %s", response.c_str());
  //scan for missing session or handle id "No such session" "no such handle"
  if (response.find("\"status\": 1") == std::string::npos) {
    if (response == "{    \"status\": 0,    \"payload\": \"stream already exists\"}") {
      Debug(1, "RTSP2Web failed adding stream, response: %s", response.c_str());
    } else {
      Warning("RTSP2Web failed adding stream, response: %s", response.c_str());
      return -2;
    }
  } else {
    Debug(1, "Added stream to RTSP2Web: %s", response.c_str());
  }

  return 0;
}

int Monitor::RTSP2WebManager::remove_from_RTSP2Web() {
  curl = curl_easy_init();
  if (!curl) return -1;

  std::string endpoint = RTSP2Web_endpoint+"/stream/"+std::to_string(parent->id)+"/delete";
  std::string response;

  curl_easy_setopt(curl, CURLOPT_URL, endpoint.c_str());
  curl_easy_setopt(curl, CURLOPT_WRITEFUNCTION, WriteCallback);
  curl_easy_setopt(curl, CURLOPT_WRITEDATA, &response);
  curl_easy_setopt(curl, CURLOPT_SSL_VERIFYHOST, 0);
  curl_easy_setopt(curl, CURLOPT_SSL_VERIFYPEER, 0);

  CURLcode res = curl_easy_perform(curl);
  if (res != CURLE_OK) {
    Warning("Libcurl attempted %s got %s", endpoint.c_str(), curl_easy_strerror(res));
  } else {
    Debug(1, "Removed stream from RTSP2Web: %s", remove_newlines(response).c_str());
  }

  curl_easy_cleanup(curl);
  return 0;
}

size_t Monitor::RTSP2WebManager::WriteCallback(void *contents, size_t size, size_t nmemb, void *userp) {
  ((std::string*)userp)->append((char*)contents, size * nmemb);
  return size * nmemb;
}

std::string remove_newlines( std::string str ) {
  while (!str.empty() && str.find("\n") != std::string::npos)
    str.erase(std::remove(str.begin(), str.end(), '\n'), str.cend());
  return str;
}

/*
std::string escape_json_string( std::string input ) {
  std::string tmp;
  tmp = regex_replace(input, std::regex("\n"), "\\n");
  tmp = regex_replace(tmp,   std::regex("\b"), "\\b");
  tmp = regex_replace(tmp,   std::regex("\f"), "\\f");
  tmp = regex_replace(tmp,   std::regex("\r"), "\\r");
  tmp = regex_replace(tmp,   std::regex("\t"), "\\t");
  tmp = regex_replace(tmp,   std::regex("\""), "\\\"");
  tmp = regex_replace(tmp,   std::regex("[\\\\]"), "\\\\");
  return tmp;
}
*/
