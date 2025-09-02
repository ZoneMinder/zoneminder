//
// ZoneMinder Monitor::AmcrestAPI Class Implementation
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

#include "zm_monitor.h"
#include "zm_signal.h"
#include "dep/CxxUrl/url.hpp"

Monitor::AmcrestAPI::AmcrestAPI(Monitor *parent_) :
  parent(parent_),
  alarmed(false),
  healthy(false)
{
  curl_multi = curl_multi_init();
  start();
}

Monitor::AmcrestAPI::~AmcrestAPI() {
  if (Amcrest_handle != nullptr) {  // potentially clean up the old handle
    curl_multi_remove_handle(curl_multi, Amcrest_handle);
    curl_easy_cleanup(Amcrest_handle);
  }
  if (curl_multi != nullptr) curl_multi_cleanup(curl_multi);
}

int Monitor::AmcrestAPI::start() {
  // init the transfer and start it in multi-handle
  int running_handles;
  CURLMcode curl_error;
  if (Amcrest_handle != nullptr) {  // potentially clean up the old handle
    curl_multi_remove_handle(curl_multi, Amcrest_handle);
    curl_easy_cleanup(Amcrest_handle);
  }

  Url full_url(parent->onvif_url.empty() ? parent->path : parent->onvif_url);
  full_url.scheme("http");
  full_url.path("/cgi-bin/eventManager.cgi?action=attach&heartbeat=5&codes=[All]");
  Debug(1, "AMCREST url is %s", full_url.str().c_str());
  Amcrest_handle = curl_easy_init();
  if (!Amcrest_handle) {
    Warning("Handle is null!");
    return -1;
  }
  std::string username = parent->onvif_username.empty() ? parent->user : parent->onvif_username;
  std::string password = parent->onvif_password.empty() ? parent->pass : parent->onvif_password;

  curl_easy_setopt(Amcrest_handle, CURLOPT_URL, full_url.str().c_str());
  curl_easy_setopt(Amcrest_handle, CURLOPT_WRITEFUNCTION, WriteCallback);
  curl_easy_setopt(Amcrest_handle, CURLOPT_WRITEDATA, &response);
  curl_easy_setopt(Amcrest_handle, CURLOPT_USERNAME, username.c_str());
  curl_easy_setopt(Amcrest_handle, CURLOPT_PASSWORD, password.c_str());
  curl_easy_setopt(Amcrest_handle, CURLOPT_HTTPAUTH, CURLAUTH_DIGEST);
  curl_error = curl_multi_add_handle(curl_multi, Amcrest_handle);
  if (curl_error != CURLM_OK) {
    Warning("AMCREST error of %i", curl_error);
  }
  curl_error = curl_multi_perform(curl_multi, &running_handles);
  if (curl_error == CURLM_OK) {
    long response_code;
    curl_easy_getinfo(Amcrest_handle, CURLINFO_RESPONSE_CODE, &response_code);
    int msgq = 0;
    struct CURLMsg *m = curl_multi_info_read(curl_multi, &msgq);
    if (m && (m->msg == CURLMSG_DONE)) {
      Warning("AMCREST Libcurl exited Early: %i", m->data.result);
    } else {
      Debug(1, "AMCREST response code %ld, response %s", response_code, response.c_str());
    }

    curl_multi_wait(curl_multi, nullptr, 0, 300, nullptr);
    curl_error = curl_multi_perform(curl_multi, &running_handles);
  } else {
    Debug(1, "Error code %i", curl_error);
  }

  if ((curl_error == CURLM_OK) && (running_handles > 0)) {
    healthy = true;
    Debug(1, "AMCREST Healthy");
  } else {
    Warning("AMCREST Response: %s", response.c_str());
    Warning("AMCREST Seeing %i streams, and error of %i, url: %s",
            running_handles, curl_error, full_url.str().c_str());
    long response_code;
    curl_easy_getinfo(Amcrest_handle, CURLINFO_OS_ERRNO, &response_code);
    Warning("AMCREST Response code: %lu", response_code);
  }

  return 0;
}

std::string get_command(const std::string &content) {
  StringVector lines = Split(content, "\r\n");
  std::string command;

  for (auto line_it = lines.begin(); line_it != lines.end(); line_it++) {
    std::string line = *line_it;

    if (line == "Heartbeat") return line;
    if (line.empty() and !command.empty()) break;
    //if (line.substr(0,4) == "Code") {
    //}
    command += line;
  }
  return command;
}

void Monitor::AmcrestAPI::WaitForMessage() {
  int open_handles;
  int transfers;
  CURLMcode curl_error;

  // Tells us how many handles are open
  curl_error = curl_multi_perform(curl_multi, &open_handles);
  if (open_handles == 0) {
    start();  // http transfer ended, need to restart.
    return;
  }

  // wait for max 5 seconds for event.
  //Debug(1, "AMCREST: multi_wait");
  curl_error = curl_multi_wait(curl_multi, nullptr, 0, 1000, &transfers);
  if (curl_error != CURLM_OK) {
    healthy = false;
    Debug(1, "Error code %d", curl_error);
  }

  Debug(2, "AMCREST: response: %s", response.c_str());
  // FIXME need to implement proper parsing
  std::string boundary = "--myboundary";
  std::string br = "\r\n";

  while (!response.empty() and !zm_terminate) {
    auto boundary_it = response.find(boundary);
    Debug(1, "AMCREST: boundary %zu", boundary_it);
    if (boundary_it == std::string::npos) {
      // Might not be a boundary, might just hit the end
      boundary_it = response.size();
    }

    std::string content = response.substr(0, boundary_it);
    Debug(1, "AMCREST: content (%s) ending at %zu", content.c_str(), boundary_it);
    std::string command = get_command(content);
    Debug(1, "AMCREST: command: %s", command.c_str());

    if (command.empty()) {
    } else if (command == "Heartbeat") {
    } else if (command.find("action=Start") != std::string::npos) {
      // Event Start
      Debug(1, "AMCREST Triggered on with %s", response.c_str());
      if (!alarmed) {
        Debug(1, "AMCREST Triggered Event");
        alarmed = true;
      }
    } else if (command.find("action=Stop") != std::string::npos) {
      Debug(1, "AMCREST Triggered off ONVIF");
      alarmed = false;
      if (!parent->Event_Poller_Closes_Event) {  // If we get a close event, then we know to expect them.
        parent->Event_Poller_Closes_Event = true;
        Debug(1, "AMCREST Setting ClosesEvent");
      }
    } else {
      Debug(1, "AMCREST unhandled message: %s", command.c_str());
    }
    boundary_it = response.find(br, boundary_it);
    Debug(1, "AMCREST Found br at %zu", boundary_it);
    boundary_it += 2;
    Debug(1, "AMCREST Remainder: %s", response.c_str());
    response = response.substr(boundary_it, response.size());
    Debug(1, "AMCREST Remainder: %s", response.c_str());
  } // end while
}

size_t Monitor::AmcrestAPI::WriteCallback(
  void *contents,
  size_t size,
  size_t nmemb,
  void *userp) {

  ((std::string*)userp)->append((char*)contents, size * nmemb);
  //Debug(1, "AMCREST callback %s", (char *)contents);
  return size * nmemb;
}
