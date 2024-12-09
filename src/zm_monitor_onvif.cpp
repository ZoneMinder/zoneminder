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

#include <cstring>

Monitor::ONVIF::ONVIF(Monitor *parent_) :
  parent(parent_)
  ,alarmed(false)
  ,healthy(false)
#ifdef WITH_GSOAP
  ,soap(nullptr)
#endif
{
}

Monitor::ONVIF::~ONVIF() {
#ifdef WITH_GSOAP
  if (soap != nullptr) {
    Debug(1, "Tearing Down Onvif");
    //We have lost ONVIF clear previous alarm topics
    alarms.clear();
    //Set alarmed to false so we don't get stuck recording
    alarmed = false;
    Debug(1, "ONVIF Alarms Cleared: Alarms count is %zu, alarmed is %s", alarms.size(), alarmed ? "true": "false");
    _wsnt__Unsubscribe wsnt__Unsubscribe;
    _wsnt__UnsubscribeResponse wsnt__UnsubscribeResponse;
    const char *RequestMessageID = parent->soap_wsa_compl ? soap_wsa_rand_uuid(soap) : "RequestMessageID";
    if ((!parent->soap_wsa_compl) || (soap_wsa_request(soap, RequestMessageID, response.SubscriptionReference.Address, "UnsubscribeRequest") == SOAP_OK)) {
      proxyEvent.Unsubscribe(response.SubscriptionReference.Address, nullptr, &wsnt__Unsubscribe, wsnt__UnsubscribeResponse);
    } else {
      Error("Couldn't set wsa headers RequestMessageID=%s; TO= %s; Request=UnsubscribeRequest .... ! Error %i %s, %s",
            RequestMessageID, response.SubscriptionReference.Address, soap->error, soap_fault_string(soap), soap_fault_detail(soap));
    }

    soap_destroy(soap);
    soap_end(soap);
    soap_free(soap);
    soap = nullptr;
  }  // end if soap
#endif
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
  if (parent->soap_wsa_compl) {soap_register_plugin(soap, soap_wsa);};
  proxyEvent = PullPointSubscriptionBindingProxy(soap);

  if (!parent->onvif_url.empty()) {
    std::string full_url = parent->onvif_url + parent->onvif_events_path;
    proxyEvent.soap_endpoint = full_url.c_str();
    set_credentials(soap);
    const char *RequestMessageID = parent->soap_wsa_compl ? soap_wsa_rand_uuid(soap) : "RequestMessageID";
    if ((!parent->soap_wsa_compl) || (soap_wsa_request(soap, RequestMessageID,  proxyEvent.soap_endpoint, "CreatePullPointSubscriptionRequest") == SOAP_OK)) {
      Debug(1, "ONVIF Endpoint: %s", proxyEvent.soap_endpoint);
      int rc = proxyEvent.CreatePullPointSubscription(&request, response);

      if (rc != SOAP_OK) {
        const char *detail = soap_fault_detail(soap);
        Error("ONVIF Couldn't create subscription! %d, fault:%s, detail:%s", rc, soap_fault_string(soap), detail ? detail : "null");
        _wsnt__Unsubscribe wsnt__Unsubscribe;
        _wsnt__UnsubscribeResponse wsnt__UnsubscribeResponse;
        proxyEvent.Unsubscribe(response.SubscriptionReference.Address, nullptr, &wsnt__Unsubscribe, wsnt__UnsubscribeResponse);
        soap_destroy(soap);
        soap_end(soap);
        soap_free(soap);
        soap = nullptr;
      } else {
        //Empty the stored messages
        set_credentials(soap);

        RequestMessageID = parent->soap_wsa_compl ? soap_wsa_rand_uuid(soap):nullptr;
        if ((!parent->soap_wsa_compl) || (soap_wsa_request(soap, RequestMessageID,  response.SubscriptionReference.Address, "PullMessageRequest") == SOAP_OK)) {
          Debug(1, "ONVIF :soap_wsa_request  OK ");
          if ((proxyEvent.PullMessages(response.SubscriptionReference.Address, nullptr, &tev__PullMessages, tev__PullMessagesResponse) != SOAP_OK) &&
              (soap->error != SOAP_EOF)
             ) { //SOAP_EOF could indicate no messages to pull.
            Error("Couldn't do initial event pull! Error %i %s, %s", soap->error, soap_fault_string(soap), soap_fault_detail(soap));
            healthy = false;
          } else {
            Debug(1, "Good Initial ONVIF Pull%i %s, %s", soap->error, soap_fault_string(soap), soap_fault_detail(soap));
            healthy = true;
          }
        } else {
          Error("ONVIF Couldn't set wsa headers   RequestMessageID= %s ; TO= %s ; Request=  PullMessageRequest .... ! Error %i %s, %s",RequestMessageID, response.SubscriptionReference.Address, soap->error, soap_fault_string(soap), soap_fault_detail(soap));
          healthy = false;
        }

        // we renew the current subscription .........
        if (parent->soap_wsa_compl) {
          set_credentials(soap);
          RequestMessageID = soap_wsa_rand_uuid(soap);
          if (soap_wsa_request(soap, RequestMessageID, response.SubscriptionReference.Address, "RenewRequest") == SOAP_OK) {
            Debug(1, "ONVIF :soap_wsa_request OK");
            if (proxyEvent.Renew(response.SubscriptionReference.Address, nullptr, &wsnt__Renew, wsnt__RenewResponse) != SOAP_OK)  {
              Error("ONVIF Couldn't do initial Renew ! Error %i %s, %s", soap->error, soap_fault_string(soap), soap_fault_detail(soap));
              if (soap->error==12) {//ActionNotSupported
                healthy = true;
              } else {
                healthy = false;
              }
            } else {
              Debug(1, "Good Initial ONVIF Renew %i %s, %s", soap->error, soap_fault_string(soap), soap_fault_detail(soap));
              healthy = true;
            }
          } else {
            Error("ONVIF Couldn't set wsa headers RequestMessageID=%s; TO=%s; Request=RenewRequest Error %i %s, %s",
                RequestMessageID,
                response.SubscriptionReference.Address,
                soap->error,
                soap_fault_string(soap),
                soap_fault_detail(soap));
            healthy = false;
          } // end renew
        }
      }
    } else {
      Error("ONVIF Couldn't set wsa headers RequestMessageID=%s; TO=%s; Request=CreatePullPointSubscriptionRequest Error %i %s, %s",
          RequestMessageID, proxyEvent.soap_endpoint, soap->error, soap_fault_string(soap), soap_fault_detail(soap));
    }
  } else {
    Warning("You must specify the url to the ONVIF endpoint");
  }
#else
  Error("zmc not compiled with GSOAP. ONVIF support not built in!");
#endif
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
      Debug(1, "Result of getting ONVIF result=%d soap_fault_string=%s detail=%s",
          result, soap_fault_string(soap), detail ? detail : "null");
      if (result != SOAP_EOF) { //Ignore the timeout error
        Error("Failed to get ONVIF messages! %d %s", result, soap_fault_string(soap));
        // healthy = false;
      }
    } else {
      Debug(1, "ONVIF polling : Got Good Response! %i", result);
      for (auto msg : tev__PullMessagesResponse.wsnt__NotificationMessage) {
        if ((msg->Topic != nullptr) &&
            (msg->Topic->__any.text != nullptr) &&
            (parent->onvif_alarm_txt.empty() || std::strstr(msg->Topic->__any.text, parent->onvif_alarm_txt.c_str())) &&
            (msg->Message.__any.elts != nullptr) &&
            (msg->Message.__any.elts->next != nullptr) &&
            (msg->Message.__any.elts->next->elts != nullptr) &&
            (msg->Message.__any.elts->next->elts->atts != nullptr) &&
            (msg->Message.__any.elts->next->elts->atts->next != nullptr) &&
            (msg->Message.__any.elts->next->elts->atts->next->text != nullptr)
           ) {
          last_topic = msg->Topic->__any.text;
          last_value = msg->Message.__any.elts->next->elts->atts->next->text;
          Info("ONVIF Got Motion Alarm! %s %s", last_topic.c_str(), last_value.c_str());
          // Apparently simple motion events, the value is boolean, but for people detection can be things like isMotion, isPeople
          if (last_value.find("false") == 0) {
            Info("Triggered off ONVIF");
            {
              std::unique_lock<std::mutex> lck(alarms_mutex);
              alarms.erase(last_topic);
            }
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
            Info("Triggered Start on ONVIF");
            if (alarms.count(last_topic) == 0) {
              alarms[last_topic] = last_value;
              if (!alarmed) {
                Info("Triggered Start Event on ONVIF");
                alarmed = true;
                // Why sleep?
                std::this_thread::sleep_for(std::chrono::seconds(1)); //thread sleep
              }
            }
          }
          Debug(1, "ONVIF Alarms count is %zu, alarmed is %s", alarms.size(), alarmed ? "true": "false");
        } else {
          Debug(1, "ONVIF Got a message that we couldn't parse");
          if ((msg->Topic != nullptr) && (msg->Topic->__any.text != nullptr)) {
            Debug(1, "text was %s", msg->Topic->__any.text);
          }
        }
      }  // end foreach msg

      // we renew the current subscription .........
      if (parent->soap_wsa_compl) {
        set_credentials(soap);
        std::string Termination_time = "PT60S";
        wsnt__Renew.TerminationTime = &Termination_time;
        RequestMessageID = parent->soap_wsa_compl ? soap_wsa_rand_uuid(soap) : "RequestMessageID";
        if ((!parent->soap_wsa_compl) || (soap_wsa_request(soap, RequestMessageID, response.SubscriptionReference.Address, "RenewRequest") == SOAP_OK)) {
          Debug(1, ":soap_wsa_request OK");
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
        } else {
          Error("Couldn't set wsa headers RequestMessageID=%s; TO=%s; Request=  RenewRequest .... ! Error %i %s, %s",
              RequestMessageID, response.SubscriptionReference.Address, soap->error, soap_fault_string(soap), soap_fault_detail(soap));
          healthy = false;
        } // end renew
      }
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
  soap_wsse_add_UsernameTokenDigest(soap, "Auth", parent->onvif_username.c_str(), parent->onvif_password.c_str());
}

//GSOAP boilerplate
int SOAP_ENV__Fault(struct soap *soap, char *faultcode, char *faultstring, char *faultactor, struct SOAP_ENV__Detail *detail, struct SOAP_ENV__Code *SOAP_ENV__Code, struct SOAP_ENV__Reason *SOAP_ENV__Reason, char *SOAP_ENV__Node, char *SOAP_ENV__Role, struct SOAP_ENV__Detail *SOAP_ENV__Detail) {
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
  #ifdef WITH_GSOAP
    std::unique_lock<std::mutex> lck(alarms_mutex);
    if (alarms.empty()) return;

    std::string note = "";
    for (auto it = alarms.begin(); it != alarms.end(); ++it) {
      note = it->first + "/" + it->second;
      noteSet.insert(note);
    }
  #endif
  return;
}

