//
// ZoneMinder Monitor::AmcrestAPI Class Implementation, $Date$, $Revision$
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


Monitor::AmcrestAPI::AmcrestAPI(Monitor *parent_) {
  parent = parent_;
  curl_multi = curl_multi_init();
  start_Amcrest();
}

Monitor::AmcrestAPI::~AmcrestAPI() {
  if (Amcrest_handle != nullptr) {  //potentially clean up the old handle
    curl_multi_remove_handle(curl_multi, Amcrest_handle);
    curl_easy_cleanup(Amcrest_handle);
  }
  if (curl_multi != nullptr) curl_multi_cleanup(curl_multi);
}

int Monitor::AmcrestAPI::start_Amcrest() {
  //init the transfer and start it in multi-handle
  int running_handles;
  long response_code;
  struct CURLMsg *m;
  CURLMcode curl_error;
  if (Amcrest_handle != nullptr) {  //potentially clean up the old handle
    curl_multi_remove_handle(curl_multi, Amcrest_handle);
    curl_easy_cleanup(Amcrest_handle);
  }

  std::string full_url = parent->onvif_url;
  if (full_url.back() != '/') full_url += '/';
  full_url += "eventManager.cgi?action=attach&codes=[VideoMotion]";
  Amcrest_handle = curl_easy_init();
  if (!Amcrest_handle){
    Warning("Handle is null!");
    return -1;
  }
  curl_easy_setopt(Amcrest_handle, CURLOPT_URL, full_url.c_str());
  curl_easy_setopt(Amcrest_handle, CURLOPT_WRITEFUNCTION, WriteCallback);
  curl_easy_setopt(Amcrest_handle, CURLOPT_WRITEDATA, &amcrest_response);
  curl_easy_setopt(Amcrest_handle, CURLOPT_USERNAME, parent->onvif_username.c_str());
  curl_easy_setopt(Amcrest_handle, CURLOPT_PASSWORD, parent->onvif_password.c_str());
  curl_easy_setopt(Amcrest_handle, CURLOPT_HTTPAUTH, CURLAUTH_DIGEST);
  curl_error = curl_multi_add_handle(curl_multi, Amcrest_handle);
    if (curl_error != CURLM_OK) {
      Warning("error of %i", curl_error);
    }
  curl_error = curl_multi_perform(curl_multi, &running_handles);
  if (curl_error == CURLM_OK) {
    curl_easy_getinfo(Amcrest_handle, CURLINFO_RESPONSE_CODE, &response_code);
    int msgq = 0;
    m = curl_multi_info_read(curl_multi, &msgq);
    if (m && (m->msg == CURLMSG_DONE)) {
      Warning("Libcurl exited Early: %i", m->data.result);
    }

    curl_multi_wait(curl_multi, NULL, 0, 300, NULL);
    curl_error = curl_multi_perform(curl_multi, &running_handles);
  }

  if ((curl_error == CURLM_OK) && (running_handles > 0)) {
    parent->Event_Poller_Healthy = TRUE;
  } else {
    Warning("Response: %s", amcrest_response.c_str());
    Warning("Seeing %i streams, and error of %i, url: %s", running_handles, curl_error, full_url.c_str());
    curl_easy_getinfo(Amcrest_handle, CURLINFO_OS_ERRNO, &response_code);
    Warning("Response code: %lu", response_code);
  }

return 0;
}

void Monitor::AmcrestAPI::WaitForMessage() {
  int open_handles;
  int transfers;
  curl_multi_perform(curl_multi, &open_handles);
  if (open_handles == 0) {
    start_Amcrest(); //http transfer ended, need to restart.
  } else {
    curl_multi_wait(curl_multi, NULL, 0, 5000, &transfers); //wait for max 5 seconds for event.
    if (transfers > 0) { //have data to deal with
      curl_multi_perform(curl_multi, &open_handles); //actually grabs the data, populates amcrest_response
      if (amcrest_response.find("action=Start") != std::string::npos) {
      //Event Start
      Debug(1,"Triggered on ONVIF");
        if (!parent->Poll_Trigger_State) {
          Debug(1,"Triggered Event");
          parent->Poll_Trigger_State = TRUE;
        }
      } else if (amcrest_response.find("action=Stop") != std::string::npos){
        Debug(1, "Triggered off ONVIF");
        parent->Poll_Trigger_State = FALSE;
        if (!parent->Event_Poller_Closes_Event) { //If we get a close event, then we know to expect them.
          parent->Event_Poller_Closes_Event = TRUE;
          Debug(1,"Setting ClosesEvent");
        }
      }
      amcrest_response.clear(); //We've dealt with the message, need to clear the queue
    }
  }
  return;
}

size_t Monitor::AmcrestAPI::WriteCallback(void *contents, size_t size, size_t nmemb, void *userp)
{
    ((std::string*)userp)->append((char*)contents, size * nmemb);
    return size * nmemb;
}
