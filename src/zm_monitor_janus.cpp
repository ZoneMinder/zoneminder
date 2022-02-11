//
// ZoneMinder Monitor::JanusManager Class Implementation, $Date$, $Revision$
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


Monitor::JanusManager::JanusManager(Monitor *parent_) { //constructor takes care of init and calls add_to
  std::string response;
  std::size_t pos;
  parent = parent_;
  if ((config.janus_path != nullptr) && (config.janus_path[0] != '\0')) {
    janus_endpoint = config.janus_path; //TODO: strip trailing /
  } else {
    janus_endpoint = "127.0.0.1:8088/janus";
  }
  if (janus_endpoint.back() == '/') janus_endpoint.pop_back(); //remove the trailing slash if present
  std::size_t pos2 = parent->path.find("@", pos);
  if (pos2 != std::string::npos) { //If we find an @ symbol, we have a username/password. Otherwise, passwordless login.

    std::size_t pos = parent->path.find(":", 7); //Search for the colon, but only after the RTSP:// text.
    if (pos == std::string::npos) throw std::runtime_error("Cannot Parse URL for Janus."); //Looks like an invalid url
    rtsp_username = parent->path.substr(7, pos-7);

    rtsp_password = parent->path.substr(pos+1, pos2 - pos - 1);
    rtsp_path = "RTSP://";
    rtsp_path += parent->path.substr(pos2 + 1);

  } else {
    rtsp_username = "";
    rtsp_password = "";
    rtsp_path = parent->path;
  }
}

Monitor::JanusManager::~JanusManager() {
  if (janus_session.empty()) get_janus_session();
  if (janus_handle.empty()) get_janus_handle();

  std::string response;
  std::string endpoint;

  std::string postData = "{\"janus\" : \"create\", \"transaction\" : \"randomString\"}";
  //std::size_t pos;
  CURLcode res;

  curl = curl_easy_init();
  if(!curl) return;

  endpoint = janus_endpoint;
  endpoint += "/";
  endpoint += janus_session;
  endpoint += "/";
  endpoint += janus_handle;

  //Assemble our actual request
  postData = "{\"janus\" : \"message\", \"transaction\" : \"randomString\", \"body\" : {";
  postData +=  "\"request\" : \"destroy\", \"admin_key\" : \"";
  postData += config.janus_secret;
  postData += "\", \"id\" : ";
  postData += std::to_string(parent->id);
  postData += "}}";

  curl_easy_setopt(curl, CURLOPT_URL,endpoint.c_str());
  curl_easy_setopt(curl, CURLOPT_WRITEFUNCTION, WriteCallback);
  curl_easy_setopt(curl, CURLOPT_WRITEDATA, &response);
  curl_easy_setopt(curl, CURLOPT_POSTFIELDS, postData.c_str());
  res = curl_easy_perform(curl);
  if (res != CURLE_OK) {
    Warning("Libcurl attempted %s got %s", endpoint.c_str(), curl_easy_strerror(res));
    curl_easy_cleanup(curl);
    return;
  }

  Debug(1, "Removed stream from Janus: %s", response.c_str());
  curl_easy_cleanup(curl);
  return;
}



int Monitor::JanusManager::check_janus() {
  if (janus_session.empty()) get_janus_session();
  if (janus_handle.empty()) get_janus_handle();

  std::string response;
  std::string endpoint = janus_endpoint;
  std::string postData;
  //std::size_t pos;
  CURLcode res;

  curl = curl_easy_init();
  if(!curl) return -1;

  endpoint += "/";
  endpoint += janus_session;
  endpoint += "/";
  endpoint += janus_handle;

  //Assemble our actual request
  postData = "{\"janus\" : \"message\", \"transaction\" : \"randomString\", \"body\" : {";
  postData +=  "\"request\" : \"info\", \"id\" : ";
  postData += std::to_string(parent->id);
  postData += "}}";

  curl_easy_setopt(curl, CURLOPT_URL,endpoint.c_str());
  curl_easy_setopt(curl, CURLOPT_WRITEFUNCTION, WriteCallback);
  curl_easy_setopt(curl, CURLOPT_WRITEDATA, &response);
  curl_easy_setopt(curl, CURLOPT_POSTFIELDS, postData.c_str());
  res = curl_easy_perform(curl);
  if (res != CURLE_OK) { //may mean an error code thrown by Janus, because of a bad session
    Warning("Attempted %s got %s", endpoint.c_str(), curl_easy_strerror(res));
    curl_easy_cleanup(curl);
    janus_session = "";
    janus_handle = "";
    return -1;
  }

  curl_easy_cleanup(curl);
  Debug(1, "Queried for stream status: %s", response.c_str());
  if (response.find("\"janus\": \"error\"") != std::string::npos) {
    if (response.find("No such session") != std::string::npos) {
      Warning("Janus Session timed out");
      janus_session = "";
      return -2;
    } else if (response.find("No such handle") != std::string::npos) {
      Warning("Janus Handle timed out");
      janus_handle = "";
      return -2;
    }
  } else if (response.find("No such mountpoint") != std::string::npos) {
    Warning("Mountpoint Missing");
    return 0;
  }
  return 1;
}

int Monitor::JanusManager::add_to_janus() {
  if (janus_session.empty()) get_janus_session();
  if (janus_handle.empty()) get_janus_handle();

  std::string response;
  std::string endpoint = janus_endpoint;

  CURLcode res;

  curl = curl_easy_init();
  if (!curl) {
    Error("Failed to init curl");
    return -1;
  }

  endpoint += "/";
  endpoint += janus_session;
  endpoint += "/";
  endpoint += janus_handle;

  //Assemble our actual request
  std::string postData = "{\"janus\" : \"message\", \"transaction\" : \"randomString\", \"body\" : {";
  postData +=  "\"request\" : \"create\", \"admin_key\" : \"";
  postData += config.janus_secret;
  postData += "\", \"type\" : \"rtsp\", ";
  postData += "\"url\" : \"";
  postData += rtsp_path;
  if (rtsp_username != "") {
    postData += "\", \"rtsp_user\" : \"";
    postData += rtsp_username;
    postData += "\", \"rtsp_pwd\" : \"";
    postData += rtsp_password;
  }
  postData += "\", \"id\" : ";
  postData += std::to_string(parent->id);
  if (parent->janus_audio_enabled)  postData += ", \"audio\" : true";
  postData += ", \"video\" : true}}";
  Warning("Sending %s to %s", postData.c_str(), endpoint.c_str());

  curl_easy_setopt(curl, CURLOPT_URL,endpoint.c_str());
  curl_easy_setopt(curl, CURLOPT_WRITEFUNCTION, WriteCallback);
  curl_easy_setopt(curl, CURLOPT_WRITEDATA, &response);
  curl_easy_setopt(curl, CURLOPT_POSTFIELDS, postData.c_str());
  res = curl_easy_perform(curl);
  if (res != CURLE_OK) {
    Error("Failed to curl_easy_perform adding rtsp stream");
    curl_easy_cleanup(curl);
    return -1;
  }
  if (response.find("\"janus\": \"error\"") != std::string::npos) {
    if (response.find("No such session") != std::string::npos) {
      Warning("Janus Session timed out");
      janus_session = "";
      return -2;
    } else if (response.find("No such handle") != std::string::npos) {
      Warning("Janus Handle timed out");
      janus_handle = "";
      return -2;
    }
  }
  //scan for missing session or handle id "No such session" "no such handle"

  Debug(1,"Added stream to Janus: %s", response.c_str());
  curl_easy_cleanup(curl);
  return 0;
}


size_t Monitor::JanusManager::WriteCallback(void *contents, size_t size, size_t nmemb, void *userp)
{
    ((std::string*)userp)->append((char*)contents, size * nmemb);
    return size * nmemb;
}

/*
void Monitor::JanusManager::generateKey()
{
    const std::string CHARACTERS = "0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz";

    std::random_device random_device;
    std::mt19937 generator(random_device());
    std::uniform_int_distribution<> distribution(0, CHARACTERS.size() - 1);

    std::string random_string;

    for (std::size_t i = 0; i < 16; ++i)
    {
        random_string += CHARACTERS[distribution(generator)];
    }

    stream_key = random_string;
}
*/


int Monitor::JanusManager::get_janus_session() {
  janus_session = "";
  std::string endpoint = janus_endpoint;

  std::string response;

  std::string postData = "{\"janus\" : \"create\", \"transaction\" : \"randomString\"}";
  std::size_t pos;
  CURLcode res;
  curl = curl_easy_init();
  if(!curl) return -1;

  curl_easy_setopt(curl, CURLOPT_URL, endpoint.c_str());
  curl_easy_setopt(curl, CURLOPT_WRITEFUNCTION, WriteCallback);
  curl_easy_setopt(curl, CURLOPT_WRITEDATA, &response);
  curl_easy_setopt(curl, CURLOPT_POSTFIELDS, postData.c_str());
  res = curl_easy_perform(curl);
  if (res != CURLE_OK) {
    Warning("Libcurl attempted %s got %s", endpoint.c_str(), curl_easy_strerror(res));
    curl_easy_cleanup(curl);
    return -1;
  }

  pos = response.find("\"id\": ");
  if (pos == std::string::npos)
  {
    curl_easy_cleanup(curl);
    return -1;
  }
  janus_session = response.substr(pos + 6, 16);
  curl_easy_cleanup(curl);
  return 1;

} //get_janus_session

int Monitor::JanusManager::get_janus_handle() {
  std::string response = "";
  std::string endpoint = janus_endpoint;
  std::size_t pos;

  CURLcode res;
  curl = curl_easy_init();
  if(!curl) return -1;

  endpoint += "/";
  endpoint += janus_session;
  std::string postData = "{\"janus\" : \"attach\", \"plugin\" : \"janus.plugin.streaming\", \"transaction\" : \"randomString\"}";
  curl_easy_setopt(curl, CURLOPT_URL,endpoint.c_str());
  curl_easy_setopt(curl, CURLOPT_WRITEFUNCTION, WriteCallback);
  curl_easy_setopt(curl, CURLOPT_WRITEDATA, &response);
  curl_easy_setopt(curl, CURLOPT_POSTFIELDS, postData.c_str());
  res = curl_easy_perform(curl);
  if (res != CURLE_OK)
  {
    Warning("Libcurl attempted %s got %s", endpoint.c_str(), curl_easy_strerror(res));
    curl_easy_cleanup(curl);
    return -1;
  }

  pos = response.find("\"id\": ");
  if (pos == std::string::npos)
  {
    curl_easy_cleanup(curl);
    return -1;
  }
  janus_handle = response.substr(pos + 6, 16);
  curl_easy_cleanup(curl);
  return 1;
} //get_janus_handle
