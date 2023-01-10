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

#include "zm_crypt.h"
#include "zm_monitor.h"
#include "zm_server.h"
#include "zm_time.h"
#include <regex>

std::string escape_json_string(std::string input);

Monitor::JanusManager::JanusManager(Monitor *parent_) :
  parent(parent_),
  Janus_Healthy(false)
{
  load_from_monitor();
}

Monitor::JanusManager::~JanusManager() {
  remove_from_janus();
}

void Monitor::JanusManager::load_from_monitor() {
  //constructor takes care of init and calls add_to
  Use_RTSP_Restream = parent->janus_use_rtsp_restream;
  profile_override = parent->janus_profile_override;
  if ((config.janus_path != nullptr) && (config.janus_path[0] != '\0')) {
    janus_endpoint = config.janus_path;
    //remove the trailing slash if present
    if (janus_endpoint.back() == '/') janus_endpoint.pop_back();
  } else {
    janus_endpoint = "127.0.0.1:8088/janus";
  }

  rtsp_auth_time = std::chrono::steady_clock::now();

  if (Use_RTSP_Restream) {
    if (parent->server_id) {
      Server server(parent->server_id);
      rtsp_path = "rtsp://"+server.Hostname();
    } else {
      rtsp_path = "rtsp://127.0.0.1";
    }
    rtsp_path += ":" + std::to_string(config.min_rtsp_port) + "/" + parent->rtsp_streamname;
    if (ZM_OPT_USE_AUTH) {
      SystemTimePoint now = std::chrono::system_clock::now();
      time_t now_t = std::chrono::system_clock::to_time_t(now);
      tm now_tm = {};
      localtime_r(&now_t, &now_tm);
      if (parent->janus_rtsp_user) {
        zmDbQuery authQuery( SELECT_USER_AND_DATA_WITH_USERID_ENABLED );
        authQuery.bind<int>("id", parent->janus_rtsp_user);
        authQuery.fetchOne();

        if(authQuery.affectedRows()!=0) {
          std::string auth_key = stringtf("%s%s%s%s%d%d%d%d",
              config.auth_hash_secret,
              authQuery.get<std::string>("Username").c_str(),  // username
              authQuery.get<std::string>("Password").c_str(),  // password
              (config.auth_hash_ips ? "127.0.0.1" : ""),
              now_tm.tm_hour,
              now_tm.tm_mday,
              now_tm.tm_mon,
              now_tm.tm_year);
          Debug(1, "Creating auth_key '%s'", auth_key.c_str());

          zm::crypto::MD5::Digest md5_digest = zm::crypto::MD5::GetDigestOf(auth_key);
          rtsp_path += "?auth=" + ByteArrayToHexString(md5_digest);
        } else {
          Warning("No user selected for RTSP_Server authentication!");
        }
      }
    }
  } else {
    rtsp_path = parent->path;
    if (!parent->user.empty()) {
      rtsp_username = escape_json_string(parent->user);
      rtsp_password = escape_json_string(parent->pass);
    }
    rtsp_path = parent->path;
  }
  parent->janus_pin = generateKey(16);
  Debug(1, "Monitor %u assigned secret %s, rtsp url is %s", parent->id, parent->janus_pin.c_str(), rtsp_path.c_str());
  strncpy(parent->shared_data->janus_pin, parent->janus_pin.c_str(), 17); //copy the null termination, as we're in C land
}

int Monitor::JanusManager::check_janus() {
  if (janus_session.empty()) get_janus_session();
  if (janus_handle.empty()) get_janus_handle();

  if (Use_RTSP_Restream) {
    Hours hours = Hours(config.auth_hash_ttl);
    if (std::chrono::steady_clock::now()-rtsp_auth_time > hours/2) {
      remove_from_janus();
      load_from_monitor();
      return add_to_janus();
    }
  }

  curl = curl_easy_init();
  if (!curl) return -1;

  //Assemble our actual request
  std::string postData = "{\"janus\" : \"message\", \"transaction\" : \"randomString\", \"body\" : {";
  postData +=  "\"request\" : \"info\", \"id\" : ";
  postData += std::to_string(parent->id);
  postData += ", \"secret\" : \"";
  postData += config.janus_secret;
  postData += "\"}}";

  std::string response;
  std::string endpoint = janus_endpoint+"/"+janus_session+"/"+janus_handle;
  curl_easy_setopt(curl, CURLOPT_URL,endpoint.c_str());
  curl_easy_setopt(curl, CURLOPT_WRITEFUNCTION, WriteCallback);
  curl_easy_setopt(curl, CURLOPT_WRITEDATA, &response);
  curl_easy_setopt(curl, CURLOPT_POSTFIELDS, postData.c_str());
  CURLcode res = curl_easy_perform(curl);
  curl_easy_cleanup(curl);

  if (res != CURLE_OK) { //may mean an error code thrown by Janus, because of a bad session
    Warning("Attempted to send %s to %s and got %s", postData.c_str(), endpoint.c_str(), curl_easy_strerror(res));
    janus_session = "";
    janus_handle = "";
    return -1;
  }

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


  //check for changed PIN
  if (response.find(parent->janus_pin) == std::string::npos){
    Warning("PIN changed, remounting.");
    return remove_from_janus();
  }
  return 1;
}

int Monitor::JanusManager::add_to_janus() {
  if (janus_session.empty()) get_janus_session();
  if (janus_handle.empty()) get_janus_handle();

  curl = curl_easy_init();
  if (!curl) {
    Error("Failed to init curl");
    return -1;
  }

  std::string endpoint = janus_endpoint+"/"+janus_session+"/"+janus_handle;

  //Assemble our actual request
  std::string postData = "{\"janus\" : \"message\", \"transaction\" : \"randomString\", \"body\" : {";
  postData +=  "\"request\" : \"create\", \"admin_key\" : \"";
  postData += config.janus_secret;
  postData += "\", \"type\" : \"rtsp\", \"rtsp_quirk\" : true, ";
  postData += "\"url\" : \"";
  postData += rtsp_path;
  //secret prevents querying the mount for info, which leaks the camera's secrets.
  postData += "\", \"secret\" : \"";
  postData += config.janus_secret;
  //pin prevents viewing the video.
  postData += "\", \"pin\" : \"";
  postData += parent->janus_pin;
  if (profile_override[0] != '\0') {
    postData += "\", \"videofmtp\" : \"";
    postData += profile_override;
  }
  if (!rtsp_username.empty()) {
    postData += "\", \"rtsp_user\" : \"";
    postData += rtsp_username;
    postData += "\", \"rtsp_pwd\" : \"";
    postData += rtsp_password;
  }
  postData += "\", \"id\" : ";
  postData += std::to_string(parent->id);
  if (parent->janus_audio_enabled)  postData += ", \"audio\" : true";
  postData += ", \"video\" : true}}";
  Debug(1, "Sending %s to %s", postData.c_str(), endpoint.c_str());

  CURLcode res;
  std::string response;

  curl_easy_setopt(curl, CURLOPT_URL, endpoint.c_str());
  curl_easy_setopt(curl, CURLOPT_WRITEFUNCTION, WriteCallback);
  curl_easy_setopt(curl, CURLOPT_WRITEDATA, &response);
  curl_easy_setopt(curl, CURLOPT_POSTFIELDS, postData.c_str());
  res = curl_easy_perform(curl);
  curl_easy_cleanup(curl);

  if (res != CURLE_OK) {
    Error("Failed to curl_easy_perform adding rtsp stream");
    return -1;
  }

  //scan for missing session or handle id "No such session" "no such handle"
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

  Debug(1,"Added stream to Janus: %s", response.c_str());
  return 0;
}

int Monitor::JanusManager::remove_from_janus() {
  if (janus_session.empty()) get_janus_session();
  if (janus_handle.empty()) get_janus_handle();

  curl = curl_easy_init();
  if (!curl) return -1;

  //Assemble our actual request
  std::string postData = "{\"janus\" : \"message\", \"transaction\" : \"randomString\", \"body\" : {";
  postData +=  "\"request\" : \"destroy\", \"admin_key\" : \"";
  postData += config.janus_secret;
  postData += "\", \"secret\" : \"";
  postData += config.janus_secret;
  postData += "\", \"id\" : ";
  postData += std::to_string(parent->id);
  postData += "}}";

  std::string endpoint = janus_endpoint+"/"+janus_session+"/"+janus_handle;
  std::string response;

  curl_easy_setopt(curl, CURLOPT_URL,endpoint.c_str());
  curl_easy_setopt(curl, CURLOPT_WRITEFUNCTION, WriteCallback);
  curl_easy_setopt(curl, CURLOPT_WRITEDATA, &response);
  curl_easy_setopt(curl, CURLOPT_POSTFIELDS, postData.c_str());
  CURLcode res = curl_easy_perform(curl);
  if (res != CURLE_OK) {
    Warning("Libcurl attempted %s got %s", endpoint.c_str(), curl_easy_strerror(res));
  } else {
    Debug(1, "Removed stream from Janus: %s", response.c_str());
  }

  curl_easy_cleanup(curl);
  return 0;
}

size_t Monitor::JanusManager::WriteCallback(void *contents, size_t size, size_t nmemb, void *userp)
{
  ((std::string*)userp)->append((char*)contents, size * nmemb);
  return size * nmemb;
}

int Monitor::JanusManager::get_janus_session() {
  janus_session = "";
  curl = curl_easy_init();
  if (!curl) return -1;

  std::string endpoint = janus_endpoint;
  std::string response;
  std::string postData = "{\"janus\" : \"create\", \"transaction\" : \"randomString\"}";
  CURLcode res;

  curl_easy_setopt(curl, CURLOPT_URL, endpoint.c_str());
  curl_easy_setopt(curl, CURLOPT_WRITEFUNCTION, WriteCallback);
  curl_easy_setopt(curl, CURLOPT_WRITEDATA, &response);
  curl_easy_setopt(curl, CURLOPT_POSTFIELDS, postData.c_str());
  res = curl_easy_perform(curl);
  curl_easy_cleanup(curl);

  if (res != CURLE_OK) {
    Warning("Libcurl attempted %s got %s", endpoint.c_str(), curl_easy_strerror(res));
    return -1;
  }

  std::size_t pos = response.find("\"id\": ");
  if (pos == std::string::npos) {
    return -1;
  }
  janus_session = response.substr(pos + 6, 16);
  return 1;
} //get_janus_session

int Monitor::JanusManager::get_janus_handle() {
  curl = curl_easy_init();
  if (!curl) return -1;

  CURLcode res;
  std::string response = "";

  std::string endpoint = janus_endpoint+"/"+janus_session;
  std::string postData = "{\"janus\" : \"attach\", \"plugin\" : \"janus.plugin.streaming\", \"transaction\" : \"randomString\"}";
  curl_easy_setopt(curl, CURLOPT_URL, endpoint.c_str());
  curl_easy_setopt(curl, CURLOPT_WRITEFUNCTION, WriteCallback);
  curl_easy_setopt(curl, CURLOPT_WRITEDATA, &response);
  curl_easy_setopt(curl, CURLOPT_POSTFIELDS, postData.c_str());
  res = curl_easy_perform(curl);
  curl_easy_cleanup(curl);

  if (res != CURLE_OK) {
    Warning("Libcurl attempted %s got %s", endpoint.c_str(), curl_easy_strerror(res));
    return -1;
  }

  std::size_t pos = response.find("\"id\": ");
  if (pos == std::string::npos) {
    return -1;
  }
  janus_handle = response.substr(pos + 6, 16);
  return 1;
} //get_janus_handle

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
