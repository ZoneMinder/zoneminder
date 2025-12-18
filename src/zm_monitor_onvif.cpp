//
// ZoneMinder Monitor::ONVIF Class Implementation
// Copyright (C) 2024 ZoneMinder Inc
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

#include <cstring>
#include <sstream>
#include "url.hpp"

std::string SOAP_STRINGS[] = {
  "SOAP_OK", // 0
  "SOAP_CLI_FAULT", // 1
  "SOAP_SVR_FAULT",//                  2
  "SOAP_TAG_MISMATCH",  //             3
  "SOAP_TYPE",          //             4
  "SOAP_SYNTAX_ERROR",  //             5
  "SOAP_NO_TAG",//                     6
  "SOAP_IOB",//                        7
  "SOAP_MUSTUNDERSTAND",//             8
  "SOAP_NAMESPACE", //                  9
  "SOAP_USER_ERROR", //                 10
  "SOAP_FATAL_ERROR", //                11
  "SOAP_FAULT", //                      12
};

Monitor::ONVIF::ONVIF(Monitor *parent_) :
  parent(parent_)
  , subscribed(false)
  , healthy(false)
  , alarmed(false)
#ifdef WITH_GSOAP
  , soap(nullptr)
#endif
{
}

Monitor::ONVIF::~ONVIF() {
#ifdef WITH_GSOAP
  if (soap) {
    stop();
    soap_destroy(soap);
    soap_end(soap);
    soap_free(soap);
    soap = nullptr;
  }
#endif
}

void Monitor::ONVIF::stop() {
  if (!(soap and subscribed)) return;

  Debug(1, "Tearing Down ONVIF");
  //We have lost ONVIF clear previous alarm topics
  alarms.clear();
  //Set alarmed to false so we don't get stuck recording
  alarmed = false;
  Debug(1, "ONVIF Alarms Cleared: Alarms count is %zu, alarmed is %s", alarms.size(), alarmed ? "true": "false");

#ifdef WITH_GSOAP
  _wsnt__Unsubscribe wsnt__Unsubscribe;
  _wsnt__UnsubscribeResponse wsnt__UnsubscribeResponse;

  if (parent->soap_wsa_compl) add_wsa_request("UnsubscribeRequest");

  int rc = proxyEvent.Unsubscribe(response.SubscriptionReference.Address,
      nullptr, &wsnt__Unsubscribe, wsnt__UnsubscribeResponse);
  if (rc != SOAP_OK) {
    const char *detail = soap_fault_detail(soap);
    if (rc > 8) {
      Error("ONVIF Couldn't unsubscribe at %s! %d, fault:%s, detail:%s", response.SubscriptionReference.Address,
          rc, soap_fault_string(soap), detail ? detail : "null");
    } else {
      Error("ONVIF Couldn't unsubscribe at %s! %d %s, fault:%s, detail:%s", response.SubscriptionReference.Address,
          rc, SOAP_STRINGS[rc].c_str(),
          soap_fault_string(soap), detail ? detail : "null");
    }
  }
#endif
  subscribed = false;
}

void Monitor::ONVIF::start() {
#ifdef WITH_GSOAP
  tev__PullMessages.Timeout = "PT20S";
  tev__PullMessages.MessageLimit = 10;
  std::string Termination_time = "PT60S";
  wsnt__Renew.TerminationTime = &Termination_time;
  soap = soap_new();
  soap->connect_timeout = 0;
  soap->recv_timeout = 0;
  soap->send_timeout = 0;
  //soap->bind_flags |= SO_REUSEADDR;
  soap_register_plugin(soap, soap_wsse);
  if (parent->soap_wsa_compl) soap_register_plugin(soap, soap_wsa);

  proxyEvent = PullPointSubscriptionBindingProxy(soap);

  Url url(parent->onvif_url);
  if (parent->onvif_url.empty()) {
    url = Url(parent->path);
    url.scheme("http");
    url.path("/onvif/device_service");
    Debug(1, "ONVIF defaulting url to %s", url.str().c_str());
  }
  std::string full_url = url.str() + parent->onvif_events_path;
  proxyEvent.soap_endpoint = full_url.c_str();
  Debug(1, "ONVIF Endpoint: %s", proxyEvent.soap_endpoint);
  set_credentials(soap);

  int rc = SOAP_OK;

  if (parent->soap_wsa_compl) {
    rc = add_wsa_request("CreatePullPointSubscriptionRequest");
  }

  rc = proxyEvent.CreatePullPointSubscription(&request, response);
#if 0
  std::stringstream ss;
  soap->os = &ss; // assign a stringstream to write output to
  soap_write__tev__CreatePullPointSubscriptionResponse(soap, &response);
  soap->os = NULL; // no longer writing to the stream
  Debug(1, "Response was %s", ss.str().c_str());
#endif

  if (rc != SOAP_OK) {
    const char *detail = soap_fault_detail(soap);
    if (rc > 8) {
      Error("ONVIF Couldn't create subscription at %s! %d, fault:%s, detail:%s", full_url.c_str(),
          rc, soap_fault_string(soap), detail ? detail : "null");
    } else {
      Error("ONVIF Couldn't create subscription at %s! %d %s, fault:%s, detail:%s", full_url.c_str(),
          rc, SOAP_STRINGS[rc].c_str(),
          soap_fault_string(soap), detail ? detail : "null");
    }

    std::stringstream ss;
    std::ostream *old_stream = soap->os;
    soap->os = &ss; // assign a stringstream to write output to
    proxyEvent.CreatePullPointSubscription(&request, response);
    soap_write__tev__CreatePullPointSubscriptionResponse(soap, &response);
    soap->os = old_stream; // no longer writing to the stream
    Debug(1, "ONVIF Response was %s", ss.str().c_str());
  } else {
    subscribed = true;
#if 0
    std::stringstream ss;
    soap->os = &ss; // assign a stringstream to write output to
    int rc = proxyEvent.CreatePullPointSubscription(&request, response);
    soap_write__tev__CreatePullPointSubscriptionResponse(soap, &response);
    soap->os = NULL; // no longer writing to the stream
    Debug(1, "Response was %s", ss.str().c_str());
#endif
  }
#else
  Error("zmc not compiled with GSOAP. ONVIF support not built in!");
#endif
}

void Monitor::ONVIF::PullMessages() {
  //Empty the stored messages
  set_credentials(soap);
  if (parent->soap_wsa_compl) add_wsa_request("PullMessageRequest");

  if ((proxyEvent.PullMessages(response.SubscriptionReference.Address, nullptr, &tev__PullMessages, tev__PullMessagesResponse) != SOAP_OK) &&
      (soap->error != SOAP_EOF)
     ) { //SOAP_EOF could indicate no messages to pull.
    Error("ONVIF Couldn't do initial event pull! Error %i %s, %s", soap->error, soap_fault_string(soap), soap_fault_detail(soap));
    healthy = false;
  } else {
    Debug(1, "Good Initial ONVIF Pull%i %s, %s", soap->error, soap_fault_string(soap), soap_fault_detail(soap));
    healthy = true;
  }
}

void Monitor::ONVIF::Renew() {
  // we renew the current subscription .........
  set_credentials(soap);
  std::string Termination_time = "PT60S";
  wsnt__Renew.TerminationTime = &Termination_time;

  if (parent->soap_wsa_compl) add_wsa_request("RenewRequest");

  if (proxyEvent.Renew(response.SubscriptionReference.Address, nullptr, &wsnt__Renew, wsnt__RenewResponse) != SOAP_OK)  {
    Error("Couldn't do Renew! Error %i %s, %s", soap->error, soap_fault_string(soap), soap_fault_detail(soap));
    if (soap->error==12) {//ActionNotSupported
      healthy = true;
    } else {
      healthy = false;
    }
  } else {
    Debug(1, "Good Renew ONVIF Renew %i %s, %s", soap->error, soap_fault_string(soap), soap_fault_detail(soap));
    healthy = true;
  }
}

void Monitor::ONVIF::WaitForMessage() {
#ifdef WITH_GSOAP
  set_credentials(soap);
  const char *RequestMessageID = parent->soap_wsa_compl ? soap_wsa_rand_uuid(soap) : "RequestMessageID";
  if ((!parent->soap_wsa_compl) || (soap_wsa_request(soap, RequestMessageID, response.SubscriptionReference.Address, "PullMessageRequest") == SOAP_OK)) {
    Debug(1, ":soap_wsa_request OK; starting ONVIF PullMessageRequest ...");
    int result = proxyEvent.PullMessages(response.SubscriptionReference.Address, nullptr, &tev__PullMessages, tev__PullMessagesResponse);
    if (result != SOAP_OK) {
      const char *detail = soap_fault_detail(soap);

      if (result != SOAP_EOF) { //Ignore the timeout error
        Error("Failed to get ONVIF messages! result=%d soap_fault_string=%s detail=%s",
            result, soap_fault_string(soap), (detail ? detail : "null"));

        std::ostream *old_stream = soap->os;
        std::stringstream ss;
        soap->os = &ss; // assign a stringstream to write output to
        set_credentials(soap);
        proxyEvent.PullMessages(response.SubscriptionReference.Address, nullptr, &tev__PullMessages, tev__PullMessagesResponse);
        soap_write__tev__PullMessagesResponse(soap, &tev__PullMessagesResponse);
        soap->os = old_stream; // no longer writing to the stream
        Debug(1, "ONVIF Response was %s", ss.str().c_str());

        healthy = false;
      } else {
        Debug(1, "Result of getting ONVIF PullMessageRequest result=%d soap_fault_string=%s detail=%s",
            result, soap_fault_string(soap), detail ? detail : "null");
        // EOF
        std::unique_lock<std::mutex> lck(alarms_mutex);

        if (!tev__PullMessagesResponse.wsnt__NotificationMessage.size()) {
          if (!parent->Event_Poller_Closes_Event and alarmed) {
            alarmed = false;
            alarms.clear();
          }
        }
      }
    } else {
      Debug(1, "ONVIF polling : Got Good Response! %i, # of messages %zu", result, tev__PullMessagesResponse.wsnt__NotificationMessage.size());
      {  // Scope for lock
        std::unique_lock<std::mutex> lck(alarms_mutex);

        if (!tev__PullMessagesResponse.wsnt__NotificationMessage.size()) {
          if (!parent->Event_Poller_Closes_Event and alarmed) {
            alarmed = false;
            alarms.clear();
          }
        }

        for (auto msg : tev__PullMessagesResponse.wsnt__NotificationMessage) {
          if ((msg->Topic != nullptr) && (msg->Topic->__any.text != nullptr) &&
              (msg->Message.__any.elts != nullptr) &&
              (msg->Message.__any.elts->next != nullptr) &&
              (msg->Message.__any.elts->next->elts != nullptr) &&
              (msg->Message.__any.elts->next->elts->atts != nullptr) &&
              (msg->Message.__any.elts->next->elts->atts->next != nullptr) &&
              (msg->Message.__any.elts->next->elts->atts->next->text != nullptr)
             ) {
            std::string topic = msg->Topic->__any.text;
            std::string value = msg->Message.__any.elts->next->elts->atts->next->text;

            Debug(1, "ONVIF Got Motion Alarm! %s %s", last_topic.c_str(), last_value.c_str());
            if (parent->onvif_alarm_txt.empty() || std::strstr(topic.c_str(), parent->onvif_alarm_txt.c_str())) {
              last_topic = topic;
              last_value = value;

              Info("ONVIF Got Motion Alarm! topic:%s value:%s", last_topic.c_str(), last_value.c_str());
              // Apparently simple motion events, the value is boolean, but for people detection can be things like isMotion, isPeople
              if (last_value.find("false") == 0 || last_value == "0") {
                Info("Triggered off ONVIF");
                alarms.erase(last_topic);
                Debug(1, "ONVIF Alarms Empty: Alarms count is %zu, alarmed is %s, empty is %d ", alarms.size(), alarmed ? "true": "false", alarms.empty());
                if (alarms.empty()) {
                  alarmed = false;
                }
                if (!parent->Event_Poller_Closes_Event) { //If we get a close event, then we know to expect them.
                  parent->Event_Poller_Closes_Event = true;
                  Info("Setting ClosesEvent");
                }
              } else {
                // Event Start
                Debug(1, "Triggered Start on ONVIF");
                if (alarms.count(last_topic) == 0) {
                  alarms[last_topic] = last_value;
                  if (!alarmed) {
                    Info("Triggered Start Event on ONVIF");
                    alarmed = true;
                  }
                }
              }
              Debug(1, "ONVIF Alarms count is %zu, alarmed is %s", alarms.size(), alarmed ? "true": "false");
            } else {
              Debug(1, "ONVIF Got a message that didn't match onvif_alarm_txt. %s != %s", topic.c_str(), parent->onvif_alarm_txt.c_str());
            }
          } else {
            Debug(1, "ONVIF Got a message that we couldn't parse.  %s", ((msg->Topic && msg->Topic->__any.text) ? msg->Topic->__any.text : "null"));
          }
        }  // end foreach msg
      } // end scope for lock

      if (zm_terminate) return;

      Renew();
    }  // end if SOAP OK/NOT OK
  } else {
    Error("Couldn't set wsa headers   RequestMessageID= %s ; TO= %s ; Request=  PullMessageRequest .... ! Error %i %s, %s",
        RequestMessageID, response.SubscriptionReference.Address, soap->error, soap_fault_string(soap), soap_fault_detail(soap));
  }  // end if soap == OK
#endif
  return;
}

#ifdef WITH_GSOAP
//ONVIF Set Credentials
void Monitor::ONVIF::set_credentials(struct soap *soap) {
  soap_wsse_delete_Security(soap);
  soap_wsse_add_Timestamp(soap, "Time", 10);
  soap_wsse_add_UsernameTokenDigest(soap, "Auth",
      (parent->onvif_username.empty() ? parent->user.c_str() : parent->onvif_username.c_str()),
      (parent->onvif_username.empty() ? parent->pass.c_str() : parent->onvif_password.c_str())
      );
}

int Monitor::ONVIF::add_wsa_request(const char *request) {
#ifdef WITH_GSOAP
  const char *RequestMessageID = soap_wsa_rand_uuid(soap);
  int rc = soap_wsa_request(soap, RequestMessageID, proxyEvent.soap_endpoint, request);
  if (rc != SOAP_OK) {
    Error("ONVIF Couldn't do wsa request RequestMessageID=%s; TO=%s; Request=%s Error %i %s, %s",
        RequestMessageID, proxyEvent.soap_endpoint, request, soap->error, soap_fault_string(soap),
        soap_fault_detail(soap));
  } else {
    Debug(1, "ONVIF did wsa request RequestMessageID=%s; TO=%s; Request=%s RC %i %i",
        RequestMessageID, proxyEvent.soap_endpoint, request, rc, soap->error);
  }
  return rc;
#endif
}

//GSOAP boilerplate
int SOAP_ENV__Fault(
    struct soap *soap,
    char *faultcode,
    char *faultstring,
    char *faultactor,
    struct SOAP_ENV__Detail *detail,
    struct SOAP_ENV__Code *SOAP_ENV__Code,
    struct SOAP_ENV__Reason *SOAP_ENV__Reason,
    char *SOAP_ENV__Node,
    char *SOAP_ENV__Role,
    struct SOAP_ENV__Detail *SOAP_ENV__Detail
    ) {
  // populate the fault struct from the operation arguments to print it
  soap_fault(soap);
  // SOAP 1.1
  soap->fault->faultcode = faultcode;
  soap->fault->faultstring = faultstring;
  soap->fault->faultactor = faultactor;
  soap->fault->detail = detail;
  // SOAP 1.2
  soap->fault->SOAP_ENV__Code = SOAP_ENV__Code;
  soap->fault->SOAP_ENV__Reason = SOAP_ENV__Reason;
  soap->fault->SOAP_ENV__Node = SOAP_ENV__Node;
  soap->fault->SOAP_ENV__Role = SOAP_ENV__Role;
  soap->fault->SOAP_ENV__Detail = SOAP_ENV__Detail;
  // set error
  soap->error = SOAP_FAULT;
  // handle or display the fault here with soap_stream_fault(soap, std::cerr);
  // return HTTP 202 Accepted
  return soap_send_empty_response(soap, SOAP_OK);
}
#endif

void Monitor::ONVIF::SetNoteSet(Event::StringSet &noteSet) {
    std::unique_lock<std::mutex> lck(alarms_mutex);
    if (alarms.empty()) return;

    std::string note = "";
    for (auto it = alarms.begin(); it != alarms.end(); ++it) {
      note = it->first + "/" + it->second;
      noteSet.insert(note);
    }
  return;
}

