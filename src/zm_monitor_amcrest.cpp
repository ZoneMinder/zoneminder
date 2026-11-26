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
#include "url.hpp"
#include "zm_amcrest_parse.h"
#include "zm_utils.h"

// Parsing of the eventManager.cgi multipart stream. Kept out of the libcurl
// guard so it can be tested without a camera.
namespace zm {
namespace amcrest {

static const std::string kBoundary = "--myboundary";
static const std::string kCrLf = "\r\n";

static bool IsMessage(const std::string &line) {
  return line == "Heartbeat" or line.compare(0, 5, "Code=") == 0;
}

std::string GetCommand(const std::string &content) {
  StringVector lines = Split(content, kCrLf);
  // A part carries Content-Type/Content-Length headers, a blank line, then the message.
  for (const std::string &line : lines) {
    if (IsMessage(line)) return line;
  }
  bool in_body = false;
  for (const std::string &line : lines) {
    if (line.empty()) {
      in_body = true;
    } else if (in_body) {
      return line;
    }
  }
  return "";
}

bool NextEvent(std::string &buffer, std::string &command) {
  command.clear();
  size_t start = buffer.find(kBoundary);
  if (start == std::string::npos) {
    // Nothing to act on; keep only a tail that may be the start of a boundary.
    size_t keep = 0;
    for (size_t len = std::min(buffer.size(), kBoundary.size() - 1); len > 0; len--) {
      if (buffer.compare(buffer.size() - len, len, kBoundary, 0, len) == 0) {
        keep = len;
        break;
      }
    }
    buffer.erase(0, buffer.size() - keep);
    return false;
  }
  buffer.erase(0, start);

  size_t content_start = buffer.find(kCrLf);
  if (content_start == std::string::npos) return false;
  content_start += kCrLf.size();
  size_t next = buffer.find(kBoundary, content_start);
  size_t end = (next == std::string::npos) ? buffer.size() : next;

  // The camera sends the next boundary only with the next part, so act on a
  // part as soon as its message line is complete instead of waiting for it.
  size_t pos = content_start;
  while (pos < end) {
    size_t eol = buffer.find(kCrLf, pos);
    if (eol == std::string::npos or eol > end) break;
    std::string line = buffer.substr(pos, eol - pos);
    if (IsMessage(line)) {
      command = line;
      buffer.erase(0, eol + kCrLf.size());
      return true;
    }
    pos = eol + kCrLf.size();
  }
  if (next == std::string::npos) return false;

  // A complete part with no message we know.
  command = GetCommand(buffer.substr(content_start, next - content_start));
  buffer.erase(0, next);
  return true;
}

}  // namespace amcrest
}  // namespace zm

#if HAVE_LIBCURL

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
    if (curl_multi != nullptr) {
      curl_multi_remove_handle(curl_multi, Amcrest_handle);
    }
    curl_easy_cleanup(Amcrest_handle);
    Amcrest_handle = nullptr;
  }
  if (curl_multi != nullptr) {
    curl_multi_cleanup(curl_multi);
    curl_multi = nullptr;
  }
}

int Monitor::AmcrestAPI::start() {
  healthy = false;
  alarmed = false;
  response.clear();

  // init the transfer and start it in multi-handle
  int running_handles = 0;
  CURLMcode curl_error;
  if (Amcrest_handle != nullptr) {  // potentially clean up the old handle
    if (curl_multi != nullptr) {
      curl_multi_remove_handle(curl_multi, Amcrest_handle);
    }
    curl_easy_cleanup(Amcrest_handle);
    Amcrest_handle = nullptr;
  }

  if (curl_multi == nullptr) {
    Warning("AMCREST curl_multi is null");
    return -1;
  }

  Url full_url(parent->onvif_url.empty() ? parent->path : parent->onvif_url);
  full_url.scheme("http");
  // path() would percent-encode a ? into the path, so the query is set on its own.
  full_url.path("/cgi-bin/eventManager.cgi");
  full_url.set_query({{"action", "attach"}, {"heartbeat", "5"}, {"codes", "[All]"}});
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
    curl_easy_cleanup(Amcrest_handle);
    Amcrest_handle = nullptr;
    return -1;
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
    healthy = false;
    Warning("AMCREST Response: %s", response.c_str());
    Warning("AMCREST Seeing %i streams, and error of %i, url: %s",
            running_handles, curl_error, full_url.str().c_str());
    long response_code;
    curl_easy_getinfo(Amcrest_handle, CURLINFO_OS_ERRNO, &response_code);
    Warning("AMCREST Response code: %lu", response_code);
  }

  return healthy ? 0 : -1;
}

void Monitor::AmcrestAPI::WaitForMessage() {
  int open_handles = 0;
  int transfers = 0;
  CURLMcode curl_error;

  // Tells us how many handles are open
  curl_error = curl_multi_perform(curl_multi, &open_handles);
  if (curl_error != CURLM_OK || open_handles == 0) {
    healthy = false;
    start();  // http transfer ended, need to restart.
    return;
  }

  // wait for max 5 seconds for event.
  //Debug(1, "AMCREST: multi_wait");
  curl_error = curl_multi_wait(curl_multi, nullptr, 0, 1000, &transfers);
  if (curl_error != CURLM_OK) {
    healthy = false;
    Debug(1, "Error code %d", curl_error);
    return;
  }

  Debug(2, "AMCREST: response: %s", response.c_str());

  std::string command;
  while (!zm_terminate && zm::amcrest::NextEvent(response, command)) {
    Debug(1, "AMCREST: command: %s", command.c_str());

    if (command.empty()) {
    } else if (command == "Heartbeat") {
    } else if (command.find("action=Start") != std::string::npos) {
      // Event Start
      Debug(1, "AMCREST Triggered on with %s", command.c_str());
      if (!alarmed) {
        Debug(1, "AMCREST Triggered Event");
        alarmed = true;
      }
    } else if (command.find("action=Stop") != std::string::npos) {
      Debug(1, "AMCREST Triggered off");
      alarmed = false;
    } else {
      Debug(1, "AMCREST unhandled message: %s", command.c_str());
    }
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

void Monitor::AmcrestAPI::SetNoteSet(Event::StringSet &noteSet) {
  std::unique_lock<std::mutex> lck(alarms_mutex);
  if (alarms.empty()) return;

  std::string note = "";
  for (auto it = alarms.begin(); it != alarms.end(); ++it) {
    note = it->first + "/" + it->second;
    noteSet.insert(note);
  }
}

#endif  // HAVE_LIBCURL
