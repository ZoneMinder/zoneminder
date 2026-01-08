//
// ZoneMinder ONVIF Class Implementation
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

#include "zm_monitor_onvif.h"
#include "zm_monitor.h"

#include <cstring>
#include <sstream>
#include "url.hpp"

// ONVIF configuration constants
#ifdef WITH_GSOAP
namespace {
  const int ONVIF_MAX_RETRIES_LIMIT = 100;  // Upper limit for max_retries option
  const int ONVIF_RETRY_DELAY_CAP = 300;    // Cap retry delay at 5 minutes
  const int ONVIF_RETRY_EXPONENT_LIMIT = 9; // 2^9 = 512, cap before overflow
}
#endif

std::string SOAP_STRINGS[] = {
  "SOAP_OK", // 0
  "SOAP_CLI_FAULT", // 1
  "SOAP_SVR_FAULT",//                  2
  "SOAP_TAG_MISMATCH",//               3
  "SOAP_TYPE",//                       4
  "SOAP_SYNTAX_ERROR",//               5
  "SOAP_NO_TAG",//                     6
  "SOAP_IOB",//                        7
  "SOAP_MUSTUNDERSTAND",//             8
  "SOAP_NAMESPACE", //                  9
  "SOAP_USER_ERROR", //                 10
  "SOAP_FATAL_ERROR", //                11 
  "SOAP_FAULT", //                      12
};

ONVIF::ONVIF(Monitor *parent_) :
  parent(parent_)
  ,alarmed(false)
  ,healthy(false)
#ifdef WITH_GSOAP
  ,soap(nullptr)
  ,try_usernametoken_auth(false)
  ,retry_count(0)
  ,max_retries(5)
  ,warned_initialized_repeat(false)
  ,pull_timeout("PT20S")
  ,subscription_timeout("PT60S")
  ,soap_log_fd(nullptr)
#endif
{
#ifdef WITH_GSOAP
  parse_onvif_options();
  last_retry_time = std::chrono::system_clock::now();
#endif
}

ONVIF::~ONVIF() {
#ifdef WITH_GSOAP
  if (soap != nullptr) {
    Debug(1, "ONVIF: Tearing Down");
    //We have lost ONVIF clear previous alarm topics
    alarms.clear();
    //Set alarmed to false so we don't get stuck recording
    alarmed = false;
    Debug(1, "ONVIF: Alarms Cleared: Alarms count is %zu, alarmed is %s", alarms.size(), alarmed ? "true": "false");
    _wsnt__Unsubscribe wsnt__Unsubscribe;
    _wsnt__UnsubscribeResponse wsnt__UnsubscribeResponse;
    
    bool use_wsa = parent->soap_wsa_compl;
    const char *RequestMessageID = nullptr;
    
    if (use_wsa) {
      RequestMessageID = soap_wsa_rand_uuid(soap);
      if (soap_wsa_request(soap, RequestMessageID, response.SubscriptionReference.Address, "UnsubscribeRequest") == SOAP_OK) {
        Debug(2, "ONVIF: WS-Addressing headers set for Unsubscribe");
        proxyEvent.Unsubscribe(response.SubscriptionReference.Address, nullptr, &wsnt__Unsubscribe, wsnt__UnsubscribeResponse);
      } else {
        Error("ONVIF: Couldn't set WS-Addressing headers for Unsubscribe. RequestMessageID=%s; TO=%s; Request=UnsubscribeRequest. Error %i %s, %s",
              RequestMessageID, response.SubscriptionReference.Address, soap->error, soap_fault_string(soap), soap_fault_detail(soap));
      }
    } else {
      // No WS-Addressing, just unsubscribe
      Debug(2, "ONVIF: Unsubscribing without WS-Addressing");
      proxyEvent.Unsubscribe(response.SubscriptionReference.Address, nullptr, &wsnt__Unsubscribe, wsnt__UnsubscribeResponse);
    }

    disable_soap_logging();
    soap_destroy(soap);
    soap_end(soap);
    soap_free(soap);
    soap = nullptr;
  }  // end if soap
#endif
}

void ONVIF::start() {
#ifdef WITH_GSOAP
  tev__PullMessages.Timeout = pull_timeout.c_str();
  tev__PullMessages.MessageLimit = 10;
  wsnt__Renew.TerminationTime = &subscription_timeout;
  
  Debug(2, "ONVIF: Using pull_timeout=%s, subscription_timeout=%s", 
        pull_timeout.c_str(), subscription_timeout.c_str());
  
  soap = soap_new();
  soap->connect_timeout = 0;
  soap->recv_timeout = 0;
  soap->send_timeout = 0;
  //soap->bind_flags |= SO_REUSEADDR;
  soap_register_plugin(soap, soap_wsse);
  if (parent->soap_wsa_compl) {
    soap_register_plugin(soap, soap_wsa);
    Debug(2, "ONVIF: WS-Addressing plugin registered");
  } else {
    Debug(2, "ONVIF: WS-Addressing disabled");
  }

  // Enable SOAP logging if configured
  if (!soap_log_file.empty()) {
    enable_soap_logging(soap_log_file);
  }

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
  
  // Try to create subscription with digest authentication first
  set_credentials(soap);
  
  const char *RequestMessageID = nullptr;
  bool use_wsa = parent->soap_wsa_compl;
  
  if (use_wsa) {
    RequestMessageID = soap_wsa_rand_uuid(soap);
    if (soap_wsa_request(soap, RequestMessageID, proxyEvent.soap_endpoint, "CreatePullPointSubscriptionRequest") != SOAP_OK) {
      Error("ONVIF: Couldn't set WS-Addressing headers. RequestMessageID=%s; TO=%s; Request=CreatePullPointSubscriptionRequest. Error %i %s, %s",
          RequestMessageID, proxyEvent.soap_endpoint, soap->error, soap_fault_string(soap), soap_fault_detail(soap));
      soap_destroy(soap);
      soap_end(soap);
      soap_free(soap);
      soap = nullptr;
      return;
    }
  }
  
  Debug(1, "ONVIF: Creating PullPoint subscription at endpoint: %s", proxyEvent.soap_endpoint);
  int rc = proxyEvent.CreatePullPointSubscription(&request, response);

  if (rc != SOAP_OK) {
    const char *detail = soap_fault_detail(soap);
    bool auth_error = (rc == 401 || (detail && std::strstr(detail, "NotAuthorized")));
    
    if (rc > 8) {
      Error("ONVIF: Couldn't create subscription at %s! %d, fault:%s, detail:%s", full_url.c_str(),
          rc, soap_fault_string(soap), detail ? detail : "null");
    } else {
      Error("ONVIF: Couldn't create subscription at %s! %d %s, fault:%s, detail:%s", full_url.c_str(),
          rc, SOAP_STRINGS[rc].c_str(),
          soap_fault_string(soap), detail ? detail : "null");
    }

    // If authentication failed and we were using digest, try plain authentication
    if (auth_error && !try_usernametoken_auth) {
      Info("ONVIF: Digest authentication failed, trying plain UsernameToken authentication");
      try_usernametoken_auth = true;
      
      // Clean up and retry
      soap_destroy(soap);
      soap_end(soap);
      
      // Set credentials with plain auth
      set_credentials(soap);
      
      if (use_wsa) {
        RequestMessageID = soap_wsa_rand_uuid(soap);
        if (soap_wsa_request(soap, RequestMessageID, proxyEvent.soap_endpoint, "CreatePullPointSubscriptionRequest") != SOAP_OK) {
          Error("ONVIF: Couldn't set WS-Addressing headers on retry. RequestMessageID=%s; TO=%s", 
                RequestMessageID, proxyEvent.soap_endpoint);
          soap_free(soap);
          soap = nullptr;
          return;
        }
      }
      
      rc = proxyEvent.CreatePullPointSubscription(&request, response);
      
      if (rc != SOAP_OK) {
        retry_count++;
        Error("ONVIF: Plain authentication also failed (retry %d/%d). Error %d: %s", 
              retry_count, max_retries, rc, soap_fault_string(soap));
        if (Logger::fetch()->level() >= Logger::DEBUG3) {
          std::stringstream ss;
          std::ostream *old_stream = soap->os;
          soap->os = &ss;
          proxyEvent.CreatePullPointSubscription(&request, response);
          soap_write__tev__CreatePullPointSubscriptionResponse(soap, &response);
          soap->os = old_stream;
          Debug(3, "ONVIF: Response was %s", ss.str().c_str());
        }
        
        if (retry_count >= max_retries) {
          Error("ONVIF: Max retries (%d) reached, giving up on subscription", max_retries);
        } else {
          int delay = get_retry_delay();
          Info("ONVIF: Will retry subscription in %d seconds (attempt %d/%d)", 
               delay, retry_count + 1, max_retries);
        }
        
        soap_destroy(soap);
        soap_end(soap);
        soap_free(soap);
        soap = nullptr;
        healthy = false;
        return;
      }
      
      Info("ONVIF: Plain authentication succeeded");
      retry_count = 0;  // Reset retry count on success
    } else {
      // Not an auth error or already tried plain auth
      retry_count++;
      if (Logger::fetch()->level() >= Logger::DEBUG3) {
        std::stringstream ss;
        std::ostream *old_stream = soap->os;
        soap->os = &ss;
        proxyEvent.CreatePullPointSubscription(&request, response);
        soap_write__tev__CreatePullPointSubscriptionResponse(soap, &response);
        soap->os = old_stream;
        Debug(3, "ONVIF: Response was %s", ss.str().c_str());
      }
      
      if (retry_count >= max_retries) {
        Error("ONVIF: Max retries (%d) reached, giving up on subscription", max_retries);
      } else {
        int delay = get_retry_delay();
        Info("ONVIF: Will retry subscription in %d seconds (attempt %d/%d)", 
             delay, retry_count + 1, max_retries);
      }
      
      soap_destroy(soap);
      soap_end(soap);
      soap_free(soap);
      soap = nullptr;
      healthy = false;
      return;
    }
  } else {
    // Success - reset retry count
    retry_count = 0;
  
  Debug(1, "ONVIF: Successfully created PullPoint subscription");
  
  //Empty the stored messages
  set_credentials(soap);

  if (use_wsa) {
    RequestMessageID = soap_wsa_rand_uuid(soap);
    if (soap_wsa_request(soap, RequestMessageID, response.SubscriptionReference.Address, "PullMessageRequest") != SOAP_OK) {
      Error("ONVIF: Couldn't set WS-Addressing headers for initial pull. RequestMessageID=%s; TO=%s; Request=PullMessageRequest. Error %i %s, %s",
          RequestMessageID, response.SubscriptionReference.Address, soap->error, soap_fault_string(soap), soap_fault_detail(soap));
      healthy = false;
      return;
    }
    Debug(2, "ONVIF: WS-Addressing headers set for initial pull");
  }
  
  if ((proxyEvent.PullMessages(response.SubscriptionReference.Address, nullptr, &tev__PullMessages, tev__PullMessagesResponse) != SOAP_OK) &&
      (soap->error != SOAP_EOF)
     ) { //SOAP_EOF could indicate no messages to pull.
    Error("ONVIF: Couldn't do initial event pull! Error %i %s, %s", soap->error, soap_fault_string(soap), soap_fault_detail(soap));
    healthy = false;
  } else {
    Debug(1, "ONVIF: Good Initial Pull %i %s, %s", soap->error, soap_fault_string(soap), soap_fault_detail(soap));
    healthy = true;
  }

  // we renew the current subscription .........
  if (use_wsa) {
    set_credentials(soap);
    RequestMessageID = soap_wsa_rand_uuid(soap);
    if (soap_wsa_request(soap, RequestMessageID, response.SubscriptionReference.Address, "RenewRequest") == SOAP_OK) {
      Debug(2, "ONVIF: WS-Addressing headers set for Renew");
      if (proxyEvent.Renew(response.SubscriptionReference.Address, nullptr, &wsnt__Renew, wsnt__RenewResponse) != SOAP_OK)  {
        Error("ONVIF: Couldn't do initial Renew ! Error %i %s, %s", soap->error, soap_fault_string(soap), soap_fault_detail(soap));
        if (soap->error==12) {//ActionNotSupported
          healthy = true;
        } else {
          healthy = false;
        }
      } else {
        Debug(2, "ONVIF: Good Initial Renew %i %s, %s", soap->error, soap_fault_string(soap), soap_fault_detail(soap));
        healthy = true;
      }
    } else {
      Error("ONVIF: Couldn't set WS-Addressing headers for Renew. RequestMessageID=%s; TO=%s; Request=RenewRequest Error %i %s, %s",
          RequestMessageID,
          response.SubscriptionReference.Address,
          soap->error,
          soap_fault_string(soap),
          soap_fault_detail(soap));
      healthy = false;
    } // end renew
  }
  } // end else (success block)
#else
  Error("zmc not compiled with GSOAP. ONVIF support not built in!");
#endif
}

void ONVIF::WaitForMessage() {
#ifdef WITH_GSOAP
  set_credentials(soap);
  
  const char *RequestMessageID = nullptr;
  bool use_wsa = parent->soap_wsa_compl;
  
  if (use_wsa) {
    RequestMessageID = soap_wsa_rand_uuid(soap);
    if (soap_wsa_request(soap, RequestMessageID, response.SubscriptionReference.Address, "PullMessageRequest") != SOAP_OK) {
      Error("ONVIF: Couldn't set WS-Addressing headers. RequestMessageID=%s; TO=%s; Request=PullMessageRequest. Error %i %s, %s",
          RequestMessageID, response.SubscriptionReference.Address, soap->error, soap_fault_string(soap), soap_fault_detail(soap));
      return;
    }
    Debug(2, "ONVIF: WS-Addressing headers set successfully");
  } else {
    Debug(2, "ONVIF: WS-Addressing disabled, not sending addressing headers");
  }
  
  Debug(1, "ONVIF: Starting PullMessageRequest ...");
  int result = proxyEvent.PullMessages(response.SubscriptionReference.Address, nullptr, &tev__PullMessages, tev__PullMessagesResponse);
    if (result != SOAP_OK) {
      const char *detail = soap_fault_detail(soap);

      if (result != SOAP_EOF) { //Ignore the timeout error
        Error("Failed to get ONVIF messages! result=%d soap_fault_string=%s detail=%s",
            result, soap_fault_string(soap), (detail ? detail : "null"));

        if (Logger::fetch()->level() >= Logger::DEBUG3) {
          std::ostream *old_stream = soap->os;
          std::stringstream ss;
          soap->os = &ss; // assign a stringstream to write output to
          set_credentials(soap);
          proxyEvent.PullMessages(response.SubscriptionReference.Address, nullptr, &tev__PullMessages, tev__PullMessagesResponse);
          soap_write__tev__PullMessagesResponse(soap, &tev__PullMessagesResponse);
          soap->os = old_stream; // no longer writing to the stream
          Debug(3, "ONVIF: Response was %s", ss.str().c_str());
        }

        retry_count++;
        if (retry_count >= max_retries) {
          Error("ONVIF: Max retries (%d) reached for PullMessages, subscription may be lost", max_retries);
        } else {
          Info("ONVIF: PullMessages failed (attempt %d/%d), will continue trying", 
               retry_count, max_retries);
        }
        healthy = false;
      } else {
        // SOAP_EOF - this is just a timeout, not an error
        Debug(2, "ONVIF PullMessage timeout (SOAP_EOF) - no new messages. result=%d soap_fault_string=%s detail=%s",
            result, soap_fault_string(soap), detail ? detail : "null");
        
        // Don't clear alarms on timeout - they should remain active until explicitly cleared
        // Only clear if Event_Poller_Closes_Event is false (camera doesn't send close events)
        // and we haven't received any messages for a long time
        // For now, just leave alarms as-is on timeout
        Debug(3, "ONVIF: Timeout - keeping existing alarms. Current alarm count: %zu, alarmed: %s",
              alarms.size(), alarmed ? "true" : "false");
        
        // Timeout is not an error, don't increment retry_count
      }
    } else {
      // Success - reset retry count
      if (retry_count > 0) {
        Info("ONVIF: PullMessages succeeded after %d failed attempts", retry_count);
        retry_count = 0;
      }
      Debug(1, "ONVIF polling : Got Good Response! %i, # of messages %zu", result, tev__PullMessagesResponse.wsnt__NotificationMessage.size());
      {  // Scope for lock
        std::unique_lock<std::mutex> lck(alarms_mutex);

        // Note: We do NOT clear alarms on empty PullMessages response.
        // According to ONVIF spec, alarms should only be cleared based on explicit
        // PropertyOperation="Deleted" or PropertyOperation="Changed" with inactive value.
        // The old code cleared on empty messages because it wasn't properly handling
        // PropertyOperation - cameras DO send proper "Deleted" or value changes, we just
        // weren't interpreting them correctly.

        for (auto msg : tev__PullMessagesResponse.wsnt__NotificationMessage) {
          std::string topic, value, operation;
          
          // Use improved parsing that handles different message structures
          if (!parse_event_message(msg, topic, value, operation)) {
            Debug(1, "ONVIF Got a message that we couldn't parse. Topic: %s", 
                  ((msg->Topic && msg->Topic->__any.text) ? msg->Topic->__any.text : "null"));
            continue;
          }
          
          Debug(2, "ONVIF parsed message: topic=%s value=%s operation=%s", 
                topic.c_str(), value.c_str(), operation.c_str());
          
          // Use improved topic filtering with wildcard support
          if (!matches_topic_filter(topic, parent->onvif_alarm_txt)) {
            Debug(2, "ONVIF Got a message that didn't match onvif_alarm_txt filter. %s doesn't match %s", 
                  topic.c_str(), parent->onvif_alarm_txt.c_str());
            continue;
          }
          
          last_topic = topic;
          last_value = value;
          
          Info("ONVIF Got Event! topic:%s value:%s operation:%s",
               last_topic.c_str(), last_value.c_str(), operation.c_str());

          // Handle PropertyOperation according to ONVIF spec:
          // - "Deleted" = property no longer exists (alarm ended)
          // - "Initialized" = current state at subscription time (not a new alarm)
          // - "Changed" = property value changed (actual alarm state transition)

          if (operation == "Deleted") {
            // PropertyOperation="Deleted" means the alarm has ended
            Info("ONVIF Alarm Deleted for topic: %s", last_topic.c_str());
            alarms.erase(last_topic);
            Debug(1, "ONVIF Alarms count after delete: %zu, alarmed is %s",
                  alarms.size(), alarmed ? "true" : "false");
            if (alarms.empty()) {
              alarmed = false;
            }
            if (!parent->Event_Poller_Closes_Event) {
              parent->Event_Poller_Closes_Event = true;
              Info("Setting ClosesEvent (detected Deleted operation)");
            }
          } else if (operation == "Initialized") {
            // PropertyOperation="Initialized" means this is the current state at subscription time,
            // NOT a new alarm trigger. We should sync our state with the camera's current state,
            // but not trigger a new event.

            // Track repeated Initialized messages (non-compliant camera behavior)
            initialized_count[last_topic]++;
            if (!warned_initialized_repeat && initialized_count[last_topic] > 1) {
              Warning("ONVIF: Camera is sending repeated PropertyOperation='Initialized' messages (count=%d for topic=%s). "
                      "According to ONVIF spec, 'Initialized' should only be sent once at subscription time. "
                      "This is non-compliant camera behavior but ZoneMinder handles it correctly by not triggering false alarms.",
                      initialized_count[last_topic], last_topic.c_str());
              warned_initialized_repeat = true;
            }

            bool state_is_active = interpret_alarm_value(last_value);
            Debug(2, "ONVIF Property Initialized: topic=%s value=%s active=%s",
                  last_topic.c_str(), last_value.c_str(), state_is_active ? "true" : "false");

            if (state_is_active && alarms.count(last_topic) == 0) {
              // Camera reports an existing alarm we didn't know about
              Debug(2, "ONVIF Syncing with camera: alarm is already active for topic: %s", last_topic.c_str());
              alarms[last_topic] = last_value;
              if (!alarmed) {
                alarmed = true;
                Info("ONVIF Alarm already active on subscription (Initialized): %s", last_topic.c_str());
              }
            } else if (!state_is_active && alarms.count(last_topic) > 0) {
              // We thought there was an alarm, but camera says it's not active
              Debug(2, "ONVIF Syncing with camera: clearing stale alarm for topic: %s", last_topic.c_str());
              alarms.erase(last_topic);
              if (alarms.empty()) {
                alarmed = false;
              }
            }

            // Set Event_Poller_Closes_Event if we see the camera can send state updates
            if (!parent->Event_Poller_Closes_Event) {
              parent->Event_Poller_Closes_Event = true;
              Info("Setting ClosesEvent (camera supports PropertyOperation)");
            }
          } else if (operation == "Changed") {
            // PropertyOperation="Changed" means the alarm state actually changed
            bool state_is_active = interpret_alarm_value(last_value);

            if (!state_is_active) {
              // Alarm turned off
              Info("ONVIF Alarm Off (Changed to inactive): topic=%s value=%s", last_topic.c_str(), last_value.c_str());
              alarms.erase(last_topic);
              Debug(1, "ONVIF Alarms count after off: %zu, alarmed is %s",
                    alarms.size(), alarmed ? "true" : "false");
              if (alarms.empty()) {
                alarmed = false;
              }
              if (!parent->Event_Poller_Closes_Event) {
                parent->Event_Poller_Closes_Event = true;
                Info("Setting ClosesEvent (detected Changed to inactive)");
              }
            } else {
              // Alarm turned on
              Info("ONVIF Alarm On (Changed to active): topic=%s value=%s", last_topic.c_str(), last_value.c_str());
              if (alarms.count(last_topic) == 0) {
                alarms[last_topic] = last_value;
                if (!alarmed) {
                  Info("ONVIF Triggered Start Event on topic: %s", last_topic.c_str());
                  alarmed = true;
                }
              } else {
                // Update existing alarm value
                alarms[last_topic] = last_value;
              }
            }
          } else {
            // Unknown operation (shouldn't happen with spec-compliant cameras)
            // Treat as legacy behavior for backwards compatibility
            Warning("ONVIF Unknown PropertyOperation '%s', treating as legacy event. topic=%s value=%s",
                    operation.c_str(), last_topic.c_str(), last_value.c_str());
            bool state_is_active = interpret_alarm_value(last_value);

            if (!state_is_active) {
              alarms.erase(last_topic);
              if (alarms.empty()) {
                alarmed = false;
              }
            } else {
              if (alarms.count(last_topic) == 0) {
                alarms[last_topic] = last_value;
                if (!alarmed) {
                  alarmed = true;
                }
              } else {
                alarms[last_topic] = last_value;
              }
            }
          }
          Debug(1, "ONVIF Alarms count is %zu, alarmed is %s", alarms.size(), alarmed ? "true" : "false");
        }  // end foreach msg
      } // end scope for lock

      // we renew the current subscription .........
      set_credentials(soap);
      wsnt__Renew.TerminationTime = &subscription_timeout;
      if (use_wsa) {
        RequestMessageID = soap_wsa_rand_uuid(soap);
        if (soap_wsa_request(soap, RequestMessageID, response.SubscriptionReference.Address, "RenewRequest") == SOAP_OK) {
          Debug(2, "ONVIF: WS-Addressing headers set for Renew");
          if (proxyEvent.Renew(response.SubscriptionReference.Address, nullptr, &wsnt__Renew, wsnt__RenewResponse) != SOAP_OK)  {
            Error("ONVIF: Couldn't do Renew! Error %i %s, %s", soap->error, soap_fault_string(soap), soap_fault_detail(soap));
            if (soap->error==12) {//ActionNotSupported
              healthy = true;
            } else {
              healthy = false;
            }
          } else {
            Debug(2, "ONVIF: Good Renew %i %s, %s", soap->error, soap_fault_string(soap), soap_fault_detail(soap));
            healthy = true;
          }
        } else {
          Error("ONVIF: Couldn't set WS-Addressing headers for Renew. RequestMessageID=%s; TO=%s; Request=RenewRequest. Error %i %s, %s",
              RequestMessageID, response.SubscriptionReference.Address, soap->error, soap_fault_string(soap), soap_fault_detail(soap));
          healthy = false;
        } // end renew
      } else { 
          if (proxyEvent.Renew(response.SubscriptionReference.Address, nullptr, &wsnt__Renew, wsnt__RenewResponse) != SOAP_OK)  {
            Error("ONVIF: Couldn't do Renew! Error %i %s, %s", soap->error, soap_fault_string(soap), soap_fault_detail(soap));
            if (soap->error==12) {//ActionNotSupported
              healthy = true;
            } else {
              healthy = false;
            }
          } else {
            Debug(2, "ONVIF: Good Renew %i %s, %s", soap->error, soap_fault_string(soap), soap_fault_detail(soap));
            healthy = true;
          }
      }
    }  // end if SOAP OK/NOT OK
#endif
  return;
}

#ifdef WITH_GSOAP
// Enable SOAP message logging to a file using the gSOAP logging plugin
// This logs all sent and received SOAP messages for debugging
void ONVIF::enable_soap_logging(const std::string &log_path) {
  if (!soap) {
    Warning("ONVIF: Cannot enable SOAP logging, soap context not initialized");
    return;
  }

  // Close existing log file if open
  disable_soap_logging();

  // Open new log file
  soap_log_fd = fopen(log_path.c_str(), "a");
  if (!soap_log_fd) {
    Error("ONVIF: Failed to open SOAP log file: %s", log_path.c_str());
    return;
  }

  // Register the logging plugin
  if (soap_register_plugin(soap, logging) != SOAP_OK) {
    Error("ONVIF: Failed to register logging plugin: %s", soap_fault_string(soap));
    fclose(soap_log_fd);
    soap_log_fd = nullptr;
    return;
  }

  // Get the logging plugin data and configure it
  struct logging_data *log_data = (struct logging_data*)soap_lookup_plugin(soap, LOGGING_ID);
  if (log_data) {
    log_data->inbound = soap_log_fd;   // Log received messages
    log_data->outbound = soap_log_fd;  // Log sent messages
    Info("ONVIF: SOAP message logging enabled to: %s", log_path.c_str());
  } else {
    Error("ONVIF: Failed to get logging plugin data");
    fclose(soap_log_fd);
    soap_log_fd = nullptr;
  }
}

// Disable SOAP message logging and close log file
void ONVIF::disable_soap_logging() {
  if (soap_log_fd) {
    if (soap) {
      // Unregister the logging plugin
      struct logging_data *log_data = (struct logging_data*)soap_lookup_plugin(soap, LOGGING_ID);
      if (log_data) {
        log_data->inbound = nullptr;
        log_data->outbound = nullptr;
      }
    }
    fclose(soap_log_fd);
    soap_log_fd = nullptr;
    Debug(2, "ONVIF: SOAP message logging disabled");
  }
}

// Parse ONVIF options from the onvif_options string
// Format: key1=value1,key2=value2
// Supported options:
//   pull_timeout=PT20S - Timeout for PullMessages requests
//   subscription_timeout=PT60S - Timeout for subscription renewal
//   max_retries=5 - Maximum retry attempts
//   soap_log=/path/to/logfile - Enable SOAP message logging
void ONVIF::parse_onvif_options() {
  if (parent->onvif_options.empty()) {
    return;
  }
  
  Debug(2, "ONVIF: Parsing options: %s", parent->onvif_options.c_str());
  
  // Helper lambda to parse a single option
  auto parse_option = [this](const std::string &option) {
    size_t eq_pos = option.find('=');
    if (eq_pos != std::string::npos) {
      std::string key = option.substr(0, eq_pos);
      std::string value = option.substr(eq_pos + 1);
      
      if (key == "pull_timeout") {
        pull_timeout = value;
        Debug(2, "ONVIF: Set pull_timeout to %s", pull_timeout.c_str());
      } else if (key == "subscription_timeout") {
        subscription_timeout = value;
        Debug(2, "ONVIF: Set subscription_timeout to %s", subscription_timeout.c_str());
      } else if (key == "max_retries") {
        try {
          max_retries = std::stoi(value);
          if (max_retries < 0) max_retries = 0;
          if (max_retries > ONVIF_MAX_RETRIES_LIMIT) max_retries = ONVIF_MAX_RETRIES_LIMIT;
          Debug(2, "ONVIF: Set max_retries to %d", max_retries);
        } catch (const std::exception &e) {
          Error("ONVIF: Invalid max_retries value '%s': %s", value.c_str(), e.what());
        }
      } else if (key == "soap_log") {
        soap_log_file = value;
        Debug(2, "ONVIF: Will enable SOAP logging to %s", soap_log_file.c_str());
      }
    }
  };
  
  std::string options = parent->onvif_options;
  size_t start = 0;
  size_t pos = 0;
  
  while ((pos = options.find(',', start)) != std::string::npos) {
    std::string option = options.substr(start, pos - start);
    parse_option(option);
    start = pos + 1;
  }
  
  // Handle last option (no trailing comma)
  if (start < options.length()) {
    std::string option = options.substr(start);
    parse_option(option);
  }
}

// Calculate exponential backoff delay for retries
// Returns delay in seconds: min(2^retry_count, ONVIF_RETRY_DELAY_CAP)
int ONVIF::get_retry_delay() {
  // Use safe approach to avoid integer overflow
  if (retry_count >= ONVIF_RETRY_EXPONENT_LIMIT) {
    return ONVIF_RETRY_DELAY_CAP;  // 2^9 = 512, cap at 5 minutes
  }
  int delay = 1 << retry_count;  // 2^retry_count
  if (delay > ONVIF_RETRY_DELAY_CAP) {
    delay = ONVIF_RETRY_DELAY_CAP;  // Extra safety check
  }
  return delay;
}

//ONVIF Set Credentials
void ONVIF::set_credentials(struct soap *soap) {
  soap_wsse_delete_Security(soap);
  soap_wsse_add_Timestamp(soap, "Time", 10);
  
  const char *username = parent->onvif_username.empty() ? parent->user.c_str() : parent->onvif_username.c_str();
  const char *password = parent->onvif_username.empty() ? parent->pass.c_str() : parent->onvif_password.c_str();
  
  if (try_usernametoken_auth) {
    // Try plain UsernameToken authentication
    Debug(2, "ONVIF: Using UsernameToken (plain) authentication");
    soap_wsse_add_UsernameTokenText(soap, "Auth", username, password);
  } else {
    // Try UsernameTokenDigest authentication (default)
    Debug(2, "ONVIF: Using UsernameTokenDigest authentication");
    soap_wsse_add_UsernameTokenDigest(soap, "Auth", username, password);
  }
}

// Helper function to interpret alarm values from various formats
// Returns true if the value indicates an active alarm, false otherwise
bool ONVIF::interpret_alarm_value(const std::string &value) {
  if (value.empty()) {
    return false;  // Empty value = no alarm
  }

  // Check for explicit false values
  if (value == "false" || value == "False" || value == "FALSE") {
    return false;
  }

  // Check for numeric zero values (0, 00, 000, etc.)
  bool all_zeros = true;
  for (char c : value) {
    if (c != '0') {
      all_zeros = false;
      break;
    }
  }
  if (all_zeros) {
    return false;  // "0", "00", "000", etc. = no alarm
  }

  // Check for explicit true values
  if (value == "true" || value == "True" || value == "TRUE") {
    return true;
  }

  // Any other non-zero value is considered active
  // This handles "1", "001", custom camera values, etc.
  return true;
}

// Helper function to parse event messages with flexible XML structure handling
bool ONVIF::parse_event_message(wsnt__NotificationMessageHolderType *msg, 
                                          std::string &topic, 
                                          std::string &value, 
                                          std::string &operation) {
  if (!msg || !msg->Topic || !msg->Topic->__any.text) {
    Debug(3, "ONVIF: Message has no topic");
    return false;
  }
  
  topic = msg->Topic->__any.text;
  Debug(3, "ONVIF: Parsing message with topic: %s", topic.c_str());
  
  // Initialize defaults
  value = "";
  operation = "Initialized";  // Default operation
  
  if (!msg->Message.__any.elts) {
    Debug(3, "ONVIF: Message has no elements");
    return false;
  }
  
  // Navigate the DOM structure more flexibly
  // Different cameras structure messages differently, so we need to handle variations
  struct soap_dom_element *elt = msg->Message.__any.elts;
  
  // Look for Message > Message > Data > SimpleItem or ElementItem
  // But also handle variations in structure
  int depth = 0;
  const int max_depth = 10;
  
  while (elt && depth < max_depth) {
    Debug(4, "ONVIF: Examining element at depth %d: %s", depth, (elt->name ? elt->name : "null"));
    
    // Check if this is a PropertyOperation element
    if (elt->atts) {
      struct soap_dom_attribute *att = elt->atts;
      while (att) {
        if (att->name && att->text) {
          Debug(4, "ONVIF: Attribute: %s = %s", att->name, att->text);
          
          // Look for PropertyOperation attribute (may have namespace prefix)
          // Check if attribute name ends with PropertyOperation
          const char *colon = std::strrchr(att->name, ':');
          const char *attr_name = colon ? colon + 1 : att->name;
          if (std::strcmp(attr_name, "PropertyOperation") == 0) {
            operation = att->text;
            Debug(3, "ONVIF: Found PropertyOperation: %s", operation.c_str());
          }
        }
        att = att->next;
      }
    }
    
    // Look for SimpleItem or ElementItem
    // Element names may have namespace prefixes (e.g., "tt:SimpleItem")
    if (elt->name) {
      const char *colon = std::strrchr(elt->name, ':');
      const char *elem_name = colon ? colon + 1 : elt->name;
      
      if (std::strcmp(elem_name, "SimpleItem") == 0) {
        // SimpleItem has Value attribute
        if (elt->atts) {
          struct soap_dom_attribute *att = elt->atts;
          while (att) {
            if (att->name && att->text) {
              const char *att_colon = std::strrchr(att->name, ':');
              const char *att_name = att_colon ? att_colon + 1 : att->name;
              if (std::strcmp(att_name, "Value") == 0) {
                value = att->text;
                Debug(3, "ONVIF: Found SimpleItem Value: %s", value.c_str());
                return true;
              }
            }
            att = att->next;
          }
        }
      } else if (std::strcmp(elem_name, "ElementItem") == 0) {
        // ElementItem might have child elements with values
        if (elt->elts && elt->elts->text) {
          value = elt->elts->text;
          Debug(3, "ONVIF: Found ElementItem value: %s", value.c_str());
          return true;
        }
      } else if (std::strcmp(elem_name, "Data") == 0) {
        // Data element, look in children
        if (elt->elts) {
          elt = elt->elts;
          depth++;
          continue;
        }
      }
    }
    
    // Try to descend into children first
    if (elt->elts) {
      elt = elt->elts;
      depth++;
    } else if (elt->next) {
      // No children, try sibling
      elt = elt->next;
    } else {
      // No children or siblings
      break;
    }
  }
  
  // Fallback: try the old parsing method for backward compatibility
  // This preserves the original deeply nested null-checking pattern
  // to support cameras that worked with the old code
  if (value.empty() &&
      msg->Message.__any.elts &&
      msg->Message.__any.elts->next &&
      msg->Message.__any.elts->next->elts &&
      msg->Message.__any.elts->next->elts->atts &&
      msg->Message.__any.elts->next->elts->atts->next &&
      msg->Message.__any.elts->next->elts->atts->next->text) {
    value = msg->Message.__any.elts->next->elts->atts->next->text;
    Debug(3, "ONVIF: Found value using legacy parsing: %s", value.c_str());
    return true;
  }
  
  Debug(2, "ONVIF: Could not parse event message value");
  return false;
}

// Helper function for hierarchical topic matching with wildcard support
bool ONVIF::matches_topic_filter(const std::string &topic, const std::string &filter) {
  if (filter.empty()) {
    return true;  // Empty filter matches all
  }
  
  // Simple substring match for backward compatibility
  if (std::strstr(topic.c_str(), filter.c_str())) {
    return true;
  }
  
  // Hierarchical wildcard matching
  // Split both topic and filter by '/'
  std::vector<std::string> topic_parts;
  std::vector<std::string> filter_parts;
  
  // Parse topic
  size_t start = 0;
  size_t pos = 0;
  while ((pos = topic.find('/', start)) != std::string::npos) {
    topic_parts.push_back(topic.substr(start, pos - start));
    start = pos + 1;
  }
  topic_parts.push_back(topic.substr(start));
  
  // Parse filter
  start = 0;
  pos = 0;
  while ((pos = filter.find('/', start)) != std::string::npos) {
    filter_parts.push_back(filter.substr(start, pos - start));
    start = pos + 1;
  }
  filter_parts.push_back(filter.substr(start));
  
  // Match parts
  size_t topic_idx = 0;
  size_t filter_idx = 0;
  
  while (filter_idx < filter_parts.size() && topic_idx < topic_parts.size()) {
    const std::string &filter_part = filter_parts[filter_idx];
    
    if (filter_part == "*") {
      // Single level wildcard - matches one part
      filter_idx++;
      topic_idx++;
    } else if (filter_part == "**") {
      // Multi-level wildcard - matches rest of topic
      return true;
    } else if (!filter_part.empty() && filter_part.back() == '*') {
      // Ends with wildcard like "RuleEngine*" - prefix match
      std::string prefix = filter_part.substr(0, filter_part.length() - 1);
      if (topic_parts[topic_idx].find(prefix) != 0) {
        return false;
      }
      filter_idx++;
      topic_idx++;
    } else {
      // Exact match or substring match required
      if (topic_parts[topic_idx].find(filter_part) == std::string::npos) {
        return false;
      }
      filter_idx++;
      topic_idx++;
    }
  }
  
  // All filter parts must be matched
  return filter_idx >= filter_parts.size();
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

void ONVIF::SetNoteSet(Event::StringSet &noteSet) {
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

