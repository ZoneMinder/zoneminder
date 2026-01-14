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
#include <vector>
#include "url.hpp"

// ONVIF configuration constants
#ifdef WITH_GSOAP
namespace {
  const int ONVIF_MAX_RETRIES_LIMIT = 100;  // Upper limit for max_retries option
  const int ONVIF_RETRY_DELAY_CAP = 300;    // Cap retry delay at 5 minutes
  const int ONVIF_RETRY_EXPONENT_LIMIT = 9; // 2^9 = 512, cap before overflow
  const int ONVIF_RENEWAL_ADVANCE_SECONDS = 10;  // Renew subscription N seconds before expiration

  // Parse ISO 8601 duration string to seconds
  // Supports formats like "PT20S", "PT1M", "PT1H30M45S"
  // Returns -1 on parse error
  int parse_iso8601_duration_seconds(const std::string& duration) {
    if (duration.empty() || duration.size() < 3) {
      return -1;
    }
    
    // Must start with "PT" (Period of Time)
    if (duration[0] != 'P' || duration[1] != 'T') {
      return -1;
    }
    
    int total_seconds = 0;
    int current_value = 0;
    bool has_digit = false;
    
    // Parse from position 2 onwards (after "PT")
    for (size_t i = 2; i < duration.size(); i++) {
      char c = duration[i];
      
      if (c >= '0' && c <= '9') {
        current_value = current_value * 10 + (c - '0');
        has_digit = true;
      } else if (c == 'H' && has_digit) {
        // Hours
        total_seconds += current_value * 3600;
        current_value = 0;
        has_digit = false;
      } else if (c == 'M' && has_digit) {
        // Minutes
        total_seconds += current_value * 60;
        current_value = 0;
        has_digit = false;
      } else if (c == 'S' && has_digit) {
        // Seconds
        total_seconds += current_value;
        current_value = 0;
        has_digit = false;
      } else {
        // Invalid character
        return -1;
      }
    }
    
    // If we still have unparsed digits, format is invalid
    if (has_digit) {
      return -1;
    }
    
    return total_seconds;
  }
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
  ,closes_event(false)
#ifdef WITH_GSOAP
  ,soap(nullptr)
  ,try_usernametoken_auth(false)
  ,retry_count(0)
  ,max_retries(5)
  ,warned_initialized_repeat(false)
  ,pull_timeout("PT5S")
  ,subscription_timeout("PT300S")
  ,soap_log_fd(nullptr)
  ,subscription_termination_time()
  ,next_renewal_time()
  ,use_absolute_time_for_renewal(false)
#endif
{
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
    set_credentials(soap);
    
    bool use_wsa = parent->soap_wsa_compl;
    if (!use_wsa || do_wsa_request(response.SubscriptionReference.Address, "UnsubscribeRequest")) {
      int result = proxyEvent.Unsubscribe(response.SubscriptionReference.Address, nullptr, 
          &wsnt__Unsubscribe, wsnt__UnsubscribeResponse);
      // Check result and log warnings if unsubscribe failed
      if (result != SOAP_OK) {
        Warning("ONVIF: Unsubscribe failed in destructor. Error %i %s, %s. Subscription may remain on camera.", 
            soap->error, soap_fault_string(soap), 
            soap_fault_detail(soap) ? soap_fault_detail(soap) : "null");
      } else {
        Debug(1, "ONVIF: Successfully unsubscribed in destructor");
      }
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
  parse_onvif_options();
  last_retry_time = std::chrono::system_clock::now();
  // Check if soap context already exists from a previous failed attempt
  // If so, clean up the stale subscription before creating a new one
  if (soap != nullptr) {
    Debug(1, "ONVIF: Existing soap context found, cleaning up stale subscription before restart");
    cleanup_subscription();
    
    // Clean up the old soap context
    disable_soap_logging();
    soap_destroy(soap);
    soap_end(soap);
    soap_free(soap);
    soap = nullptr;
  }
  
  // Validate pull_timeout before creating subscription
  int pull_timeout_seconds = parse_iso8601_duration_seconds(pull_timeout);
  if (pull_timeout_seconds < 0) {
    Error("ONVIF: Invalid pull_timeout format: %s, adjusting to PT8S", pull_timeout.c_str());
    pull_timeout = "PT8S";
    pull_timeout_seconds = 8;
  }
  
  if (pull_timeout_seconds >= ONVIF_RENEWAL_ADVANCE_SECONDS) {
    Warning("ONVIF: pull_timeout %ds must be less than renewal advance time (%ds) to ensure timely renewals. Adjusting to PT8S",
            pull_timeout_seconds, ONVIF_RENEWAL_ADVANCE_SECONDS);
    pull_timeout = "PT8S";
  }
  
  soap = soap_new();
  soap->connect_timeout = 0;
  soap->recv_timeout = 0;
  soap->send_timeout = 0;
  //soap->bind_flags |= SO_REUSEADDR;
  soap_register_plugin(soap, soap_wsse);
  // Always register WS-Addressing plugin to handle mustUnderstand headers in responses,
  // even if we don't send WS-Addressing headers in requests
  soap_register_plugin(soap, soap_wsa);
  if (parent->soap_wsa_compl) {
    Debug(2, "ONVIF: WS-Addressing enabled for requests");
  } else {
    Debug(2, "ONVIF: WS-Addressing disabled for requests (plugin still registered for responses)");
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
  
  bool use_wsa = parent->soap_wsa_compl;
  
  if (use_wsa && !do_wsa_request(proxyEvent.soap_endpoint, "CreatePullPointSubscriptionRequest")) {
    soap_destroy(soap);
    soap_end(soap);
    soap_free(soap);
    soap = nullptr;
    return;
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
      
      if (use_wsa && !do_wsa_request(proxyEvent.soap_endpoint, "CreatePullPointSubscriptionRequest")) {
        soap_free(soap);
        soap = nullptr;
        return;
      }
      
      rc = proxyEvent.CreatePullPointSubscription(&request, response);
      
      if (rc != SOAP_OK) {
        retry_count++;
        Error("ONVIF: Plain authentication also failed (retry %d/%d). Error %d: %s", 
              retry_count, max_retries, rc, soap_fault_string(soap));
        
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

    // Update renewal tracking times from initial subscription response
    if (response.wsnt__TerminationTime != 0) {
      update_renewal_times(response.wsnt__TerminationTime);
      log_subscription_timing("subscription_created");
    } else {
      Debug(1, "ONVIF: Initial subscription response has no TerminationTime, renewal tracking not set");
    }

    // Empty the stored messages
    // Clear any stale SOAP headers from previous requests/responses
    soap->header = nullptr;
    set_credentials(soap);

    if (use_wsa && !do_wsa_request(response.SubscriptionReference.Address, "PullPointSubscription/PullMessagesRequest")) {
      healthy = false;
      return;
    }

    _tev__PullMessages tev__PullMessages;
    _tev__PullMessagesResponse tev__PullMessagesResponse;
    tev__PullMessages.Timeout = pull_timeout.c_str();
    tev__PullMessages.MessageLimit = 10;

    Debug(2, "ONVIF: Using pull_timeout=%s, subscription_timeout=%s at %s", 
        pull_timeout.c_str(), subscription_timeout.c_str(), response.SubscriptionReference.Address);
    if ((proxyEvent.PullMessages(response.SubscriptionReference.Address, nullptr, &tev__PullMessages, tev__PullMessagesResponse) != SOAP_OK) &&
        (soap->error != SOAP_EOF)
       ) { //SOAP_EOF could indicate no messages to pull.
      Error("ONVIF: Couldn't do initial event pull! Error %i %s, %s", soap->error, soap_fault_string(soap), soap_fault_detail(soap));
      healthy = false;
    } else {
      Debug(1, "ONVIF: Good Initial Pull %i %s, %s", soap->error, soap_fault_string(soap), soap_fault_detail(soap));
      healthy = true;
    }

    // Perform initial renewal of the subscription
    if (use_wsa) {  // Only if WS-Addressing is enabled
      if (!Renew()) {
        Debug(1, "ONVIF: Initial renewal failed, but continuing");
      }
    }
  } // end else (success block)
#else
  Error("zmc not compiled with GSOAP. ONVIF support not built in!");
#endif
}

void ONVIF::WaitForMessage() {
#ifdef WITH_GSOAP
  // Clear any stale SOAP headers from previous requests/responses
  soap->header = nullptr;
  set_credentials(soap);
  
  bool use_wsa = parent->soap_wsa_compl;
  
  if (use_wsa) {
    if (!do_wsa_request(response.SubscriptionReference.Address, "PullMessageRequest")) {
      return;
    }
  } else {
    Debug(2, "ONVIF: WS-Addressing disabled, not sending addressing headers");
  }
  
  _tev__PullMessages tev__PullMessages;
  _tev__PullMessagesResponse tev__PullMessagesResponse;
  tev__PullMessages.Timeout = pull_timeout.c_str();
  tev__PullMessages.MessageLimit = 10;
  Debug(1, "ONVIF: Starting PullMessageRequest with Timeout=%s, MessageLimit=%d ...",
        pull_timeout.c_str(), tev__PullMessages.MessageLimit);
  int result = proxyEvent.PullMessages(response.SubscriptionReference.Address, nullptr, &tev__PullMessages, tev__PullMessagesResponse);
    if (result != SOAP_OK) {
      const char *detail = soap_fault_detail(soap);

      if (soap->error != SOAP_EOF) { //Ignore the timeout error
        Error("Failed to get ONVIF messages! result=%d soap->error %d, soap_fault_string=%s detail=%s",
            result, soap->error, soap_fault_string(soap), (detail ? detail : "null"));

        retry_count++;
        if (retry_count >= max_retries) {
          Error("ONVIF: Max retries (%d) reached for PullMessages, subscription may be lost", max_retries);
        } else {
          Info("ONVIF: PullMessages failed (attempt %d/%d), will continue trying", retry_count, max_retries);
        }
        healthy = false;
      } else {
        // SOAP_EOF - this is just a timeout, not an error
        Debug(2, "ONVIF PullMessage timeout (SOAP_EOF) - no new messages. result=%d soap_fault_string=%s detail=%s",
            result, soap_fault_string(soap), detail ? detail : "null");
        
        // Don't clear alarms on timeout - they should remain active until explicitly cleared
        // Only clear if Closes_Event is false (camera doesn't send close events)
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

        int msg_index = 0;
        for (auto msg : tev__PullMessagesResponse.wsnt__NotificationMessage) {
          msg_index++;
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

          Info("ONVIF Got Event [msg %d/%zu]! topic:%s value:%s operation:%s",
               msg_index, tev__PullMessagesResponse.wsnt__NotificationMessage.size(),
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
            if (!closes_event) {
              closes_event = true;
              Info("Setting ClosesEvent (detected Deleted operation)");
            }
          } else if (operation == "Initialized") {
            // PropertyOperation="Initialized" means this is the current state at subscription time,
            // NOT a new alarm trigger. We should sync our state with the camera's current state,
            // but not trigger a new event.

            // Track repeated Initialized messages (non-compliant camera behavior)
            initialized_count[last_topic]++;
            if (!warned_initialized_repeat && initialized_count[last_topic] > 1) {
              Warning("ONVIF: Camera is sending repeated PropertyOperation='Initialized' messages (count=%d for topic=%s).",
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

            // Set Closes_Event if we see the camera can send state updates
            if (!closes_event) {
              closes_event = true;
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
              if (!closes_event) {
                closes_event = true;
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

      // Log subscription timing before checking if renewal is needed
      log_subscription_timing("before_renewal_check");

      // Renew subscription if needed
      if (IsRenewalNeeded()) {
        if (!Renew()) {
          Warning("ONVIF: Failed to renew subscription");
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

// Clean up existing subscription properly
// This helper method ensures proper unsubscribe is called before cleanup or retry
void ONVIF::cleanup_subscription() {
  if (!soap) {
    Debug(3, "ONVIF: cleanup_subscription called but soap is null, nothing to clean");
    return;
  }

  Debug(2, "ONVIF: Cleaning up existing subscription");
  
  _wsnt__Unsubscribe wsnt__Unsubscribe;
  _wsnt__UnsubscribeResponse wsnt__UnsubscribeResponse;
  
  bool use_wsa = parent->soap_wsa_compl;
  int result = SOAP_OK;
  
  // Attempt to unsubscribe from the existing subscription
  if (use_wsa) {
    if (do_wsa_request(response.SubscriptionReference.Address, "UnsubscribeRequest")) {
      result = proxyEvent.Unsubscribe(response.SubscriptionReference.Address, nullptr, 
                                       &wsnt__Unsubscribe, wsnt__UnsubscribeResponse);
    } else {
      // WS-Addressing setup failed - log the error details from soap_wsa_request
      Warning("ONVIF: Failed to set WS-Addressing headers for unsubscribe during cleanup. Error %i %s, %s", 
              soap->error, soap_fault_string(soap), 
              soap_fault_detail(soap) ? soap_fault_detail(soap) : "null");
      // Don't attempt unsubscribe if WS-Addressing setup failed
      // Note: This is a limitation - subscription may remain active on camera
      // However, attempting unsubscribe with invalid WS-Addressing state would fail anyway
      return;
    }
  } else {
    Debug(2, "ONVIF: Unsubscribing without WS-Addressing during cleanup");
    result = proxyEvent.Unsubscribe(response.SubscriptionReference.Address, nullptr, 
                                     &wsnt__Unsubscribe, wsnt__UnsubscribeResponse);
  }
  
  if (result != SOAP_OK) {
    Warning("ONVIF: Unsubscribe failed during cleanup. Error %i %s, %s", 
            soap->error, soap_fault_string(soap), 
            soap_fault_detail(soap) ? soap_fault_detail(soap) : "null");
  } else {
    Debug(2, "ONVIF: Successfully unsubscribed during cleanup");
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
        
        // Validate pull_timeout immediately
        int pull_timeout_seconds = parse_iso8601_duration_seconds(pull_timeout);
        if (pull_timeout_seconds < 0) {
          Error("ONVIF: Invalid pull_timeout format: %s, adjusting to PT8S", pull_timeout.c_str());
          pull_timeout = "PT8S";
          pull_timeout_seconds = 8;
        }
        
        if (pull_timeout_seconds >= ONVIF_RENEWAL_ADVANCE_SECONDS) {
          Warning("ONVIF: pull_timeout (%ds) must be less than renewal advance time (%ds) to ensure timely renewals. Adjusting to PT8S",
                  pull_timeout_seconds, ONVIF_RENEWAL_ADVANCE_SECONDS);
          pull_timeout = "PT8S";
        }
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
      } else if (option.find("closes_event") != std::string::npos) {
        // Option to indicate that ONVIF will send a close event message
        closes_event = true;
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
  
  // Final validation of pull_timeout (in case it was not set in options and we're using default)
  int pull_timeout_seconds = parse_iso8601_duration_seconds(pull_timeout);
  if (pull_timeout_seconds < 0) {
    Error("ONVIF: Invalid pull_timeout format: %s, adjusting to PT8S", pull_timeout.c_str());
    pull_timeout = "PT8S";
    pull_timeout_seconds = 8;
  }
  
  if (pull_timeout_seconds >= ONVIF_RENEWAL_ADVANCE_SECONDS) {
    Warning("ONVIF: pull_timeout (%ds) must be less than renewal advance time (%ds) to ensure timely renewals. Adjusting to PT8S",
            pull_timeout_seconds, ONVIF_RENEWAL_ADVANCE_SECONDS);
    pull_timeout = "PT8S";
    pull_timeout_seconds = 8;
  }
  
  Info("ONVIF: Using pull_timeout=%s (%d seconds), subscription_timeout=%s", 
       pull_timeout.c_str(), pull_timeout_seconds, subscription_timeout.c_str());
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

// Update subscription renewal tracking times based on TerminationTime from ONVIF response
// termination_time: Unix timestamp (time_t) indicating when subscription expires
void ONVIF::update_renewal_times(time_t termination_time) {
  if (termination_time <= 0) {
    Warning("ONVIF: Received invalid TerminationTime (%ld), not updating renewal tracking", 
            static_cast<long>(termination_time));
    return;
  }
  
  // Convert time_t to SystemTimePoint
  subscription_termination_time = std::chrono::system_clock::from_time_t(termination_time);
  
  // Validate that termination time is in the future
  auto now = std::chrono::system_clock::now();
  if (subscription_termination_time <= now) {
    if (!use_absolute_time_for_renewal) {
      Warning("ONVIF: Received TerminationTime in the past %ld %s < %s, switching to absolute time for future renewals",
          static_cast<long>(termination_time),
          SystemTimePointToString(subscription_termination_time).c_str(),
          SystemTimePointToString(now).c_str());
      use_absolute_time_for_renewal = true;
    } else {
      Warning("ONVIF: Received TerminationTime in the past %ld %s < %s, despite using absolute time for renewals.",
          static_cast<long>(termination_time),
          SystemTimePointToString(subscription_termination_time).c_str(),
          SystemTimePointToString(now).c_str());
    }
    return;
  }
  
  // Calculate renewal time: N seconds before termination
  next_renewal_time = subscription_termination_time - std::chrono::seconds(ONVIF_RENEWAL_ADVANCE_SECONDS);
  
  // Log the renewal schedule
  auto seconds_until_renewal = std::chrono::duration_cast<std::chrono::seconds>(
    next_renewal_time - now).count();
  auto seconds_until_termination = std::chrono::duration_cast<std::chrono::seconds>(
    subscription_termination_time - now).count();
  
  Debug(2, "ONVIF: Updated subscription times - will renew in %ld seconds at %s, terminates in %ld seconds at %s",
        seconds_until_renewal,
        SystemTimePointToString(next_renewal_time).c_str(),
        seconds_until_termination,
        SystemTimePointToString(subscription_termination_time).c_str());
}  // end void ONVIF::update_renewal_times(time_t termination_time)

// Check if renewal tracking has been initialized
// Returns false if tracking times are at epoch (uninitialized), true otherwise
bool ONVIF::is_renewal_tracking_initialized() const {
  return next_renewal_time.time_since_epoch().count() != 0;
}

// Log subscription timing information for debugging
// Shows current time, termination time, renewal time, and remaining time
void ONVIF::log_subscription_timing(const char* context) {
#ifdef WITH_GSOAP
  if (!is_renewal_tracking_initialized()) {
    Debug(1, "ONVIF [%s]: Subscription timing not initialized", context);
    return;
  }
  
  auto now = std::chrono::system_clock::now();
  auto time_until_termination = std::chrono::duration_cast<std::chrono::seconds>(
    subscription_termination_time - now).count();
  auto time_until_renewal = std::chrono::duration_cast<std::chrono::seconds>(
    next_renewal_time - now).count();
  
  time_t term_time = std::chrono::system_clock::to_time_t(subscription_termination_time);
  time_t renew_time = std::chrono::system_clock::to_time_t(next_renewal_time);
  
  char term_buf[64], renew_buf[64];
  strftime(term_buf, sizeof(term_buf), "%Y-%m-%d %H:%M:%S", localtime(&term_time));
  strftime(renew_buf, sizeof(renew_buf), "%Y-%m-%d %H:%M:%S", localtime(&renew_time));
  
  Info("ONVIF [%s]: Subscription terminates at %s (in %lds), renewal at %s (in %lds)",
       context, term_buf, time_until_termination, renew_buf, time_until_renewal);
  
  // Warn if we're getting close to termination
  if (time_until_termination < ONVIF_RENEWAL_ADVANCE_SECONDS && time_until_termination > 0) {
    Warning("ONVIF: Subscription terminating soon! Only %ld seconds remaining", time_until_termination);
  }
#endif
}


// Format an absolute time as ISO 8601 string for ONVIF RenewRequest
// Returns a string like "2026-01-13T15:30:45.000Z"
std::string format_absolute_time_iso8601(time_t time) {
  struct tm *tm_utc = gmtime(&time);
  if (!tm_utc) {
    return "";
  }
  
  char buffer[32];
  strftime(buffer, sizeof(buffer), "%Y-%m-%dT%H:%M:%S.000Z", tm_utc);
  return std::string(buffer);
}

// Perform ONVIF subscription renewal
// Returns true if renewal succeeded or is not supported, false on error
bool ONVIF::Renew() {
#ifdef WITH_GSOAP
  set_credentials(soap);
  _wsnt__Renew wsnt__Renew;
  _wsnt__RenewResponse wsnt__RenewResponse;
  
  std::string absolute_time_str;
  
  if (use_absolute_time_for_renewal) {
    // Calculate absolute termination time: current time + subscription duration
    int subscription_timeout_seconds = parse_iso8601_duration_seconds(subscription_timeout);
    if (subscription_timeout_seconds < 0) {
      Warning("ONVIF: Invalid subscription_timeout format: %s, using default 60 seconds", subscription_timeout.c_str());
      subscription_timeout_seconds = 60;
    } else {
      Debug(3, "Have subscription timeout duration %dseconds", subscription_timeout_seconds);
    }
    
    time_t now = time(nullptr);
    time_t absolute_termination = now + subscription_timeout_seconds;
    absolute_time_str = format_absolute_time_iso8601(absolute_termination);
    
    if (absolute_time_str.empty()) {
      Error("ONVIF: Failed to format absolute time for renewal");
      return false;
    }
    
    wsnt__Renew.TerminationTime = &absolute_time_str;
    Debug(1, "ONVIF: Setting renew termination time to absolute time: %s (camera requires absolute time format)", 
          absolute_time_str.c_str());
  } else {
    // Use duration format (default behavior)
    wsnt__Renew.TerminationTime = &subscription_timeout;
    Debug(1, "ONVIF: Setting renew termination time to duration: %s", subscription_timeout.c_str());
  }
  
  bool use_wsa = parent->soap_wsa_compl;
  
  if (use_wsa && !do_wsa_request(response.SubscriptionReference.Address, "RenewRequest")) {
    Debug(1, "ONVIF: WS-Addressing setup failed for renewal, cleaning up subscription");
    cleanup_subscription();
    healthy = false;
    return false;
  }
  
  if (proxyEvent.Renew(response.SubscriptionReference.Address, nullptr, &wsnt__Renew, wsnt__RenewResponse) != SOAP_OK) {
    Error("ONVIF: Couldn't do Renew! Error %i %s, %s", soap->error, soap_fault_string(soap), soap_fault_detail(soap));
    if (soap->error == 12) {  // ActionNotSupported
      Debug(2, "ONVIF: Renew not supported by device, continuing without renewal");
      healthy = true;
      return true;  // Not a fatal error
    } else {
      // Renewal failed - clean up the subscription to prevent leaks
      Debug(1, "ONVIF: Renewal failed, cleaning up subscription to prevent leak");
      cleanup_subscription();
      healthy = false;
      return false;
    }
  }
  
  Debug(2, "ONVIF: Subscription renewed successfully");
  healthy = true;
  
  // Update renewal times from renew response
  if (wsnt__RenewResponse.TerminationTime != 0) {
    update_renewal_times(wsnt__RenewResponse.TerminationTime);
    log_subscription_timing("renewed");
  } else {
    Debug(1, "No TerminationTime in RenewResponse");
  }
  
  return true;
#else
  return false;
#endif
}  // bool ONVIF::Renew()

// Check if subscription renewal is needed
// Returns true if renewal should be performed now, false if not yet needed
bool ONVIF::IsRenewalNeeded() const {
#ifdef WITH_GSOAP
  // Check if we have valid renewal times set
  if (!is_renewal_tracking_initialized()) {
    // No renewal tracking set up yet, always renew (backward compatibility)
    Debug(2, "ONVIF: No renewal tracking initialized, performing renewal");
    return true;
  }
  
  SystemTimePoint now = std::chrono::system_clock::now();
  if (now >= next_renewal_time) {
    // Time to renew
    auto seconds_overdue = std::chrono::duration_cast<std::chrono::seconds>(
      now - next_renewal_time).count();
    Info("ONVIF: Subscription renewal needed (overdue by %ld seconds)", seconds_overdue);
    return true;
  }
  
  // Calculate time remaining
  auto time_until_termination = std::chrono::duration_cast<std::chrono::seconds>(
    subscription_termination_time - now).count();
  auto time_until_renewal = std::chrono::duration_cast<std::chrono::seconds>(
    next_renewal_time - now).count();
  
  // Log current timing status
  time_t term_time = std::chrono::system_clock::to_time_t(subscription_termination_time);
  time_t renew_time = std::chrono::system_clock::to_time_t(next_renewal_time);
  
  char term_buf[64], renew_buf[64];
  strftime(term_buf, sizeof(term_buf), "%Y-%m-%d %H:%M:%S", localtime(&term_time));
  strftime(renew_buf, sizeof(renew_buf), "%Y-%m-%d %H:%M:%S", localtime(&renew_time));
  
  Debug(2, "ONVIF [renewal_check]: Subscription terminates at %s (in %lds), renewal at %s (in %lds)",
        SystemTimePointToString(subscription_termination_time).c_str(),
        time_until_termination,
        SystemTimePointToString(next_renewal_time).c_str(),
        time_until_renewal);
  return false;
#else
  return false;
#endif
}

// Setup WS-Addressing headers for SOAP request
// This helper method encapsulates the common pattern of setting up WS-Addressing
// headers for SOAP requests, eliminating code duplication across the class.
//
// Parameters:
//   address - The target endpoint address (TO header)
//   action  - The SOAP action name for the request
//
// Returns:
//   true  - WS-Addressing headers were successfully set
//   false - Failed to set headers (error logged), or invalid parameters
//
// Note: This method assumes the soap context is already initialized.
bool ONVIF::do_wsa_request(const char* address, const char* action) {
#ifdef WITH_GSOAP
  if (!soap || !address || !action) {
    Error("ONVIF: Invalid parameters for WS-Addressing request");
    return false;
  }
  
  const char* RequestMessageID = soap_wsa_rand_uuid(soap);
  if (soap_wsa_request(soap, RequestMessageID, address, action) != SOAP_OK) {
    Error("ONVIF: Couldn't set WS-Addressing headers. RequestMessageID=%s; TO=%s; Request=%s. Error %i %s, %s",
        RequestMessageID, address, action, soap->error, soap_fault_string(soap), soap_fault_detail(soap));
    return false;
  }
  
  Debug(2, "ONVIF: WS-Addressing headers set for %s", action);
  return true;
#else
  return false;
#endif
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

// Helper function to get local element name (strip namespace prefix)
static const char* get_local_name(const char* name) {
  if (!name) return nullptr;
  const char *colon = std::strrchr(name, ':');
  return colon ? colon + 1 : name;
}

// Helper function to extract SimpleItem Name and Value attributes
static bool extract_simple_item_attrs(struct soap_dom_element *elt,
                                       std::string &item_name,
                                       std::string &item_value) {
  item_name.clear();
  item_value.clear();

  if (!elt || !elt->atts) return false;

  struct soap_dom_attribute *att = elt->atts;
  while (att) {
    if (att->name && att->text) {
      const char *attr_name = get_local_name(att->name);
      if (std::strcmp(attr_name, "Name") == 0) {
        item_name = att->text;
      } else if (std::strcmp(attr_name, "Value") == 0) {
        item_value = att->text;
      }
    }
    att = att->next;
  }

  return !item_name.empty();
}

// Check if SimpleItem Name is a data field (not a source identifier)
static bool is_data_simple_item(const std::string &item_name) {
  // Known data field names
  if (item_name == "State" ||
      item_name == "IsMotion" ||
      item_name == "IsTamper" ||
      item_name == "IsInside" ||
      item_name == "IsCrossing" ||
      item_name == "Value") {
    return true;
  }
  // Items starting with "Is" are typically boolean state fields
  if (item_name.length() >= 2 && item_name[0] == 'I' && item_name[1] == 's') {
    return true;
  }
  return false;
}

// Helper function to parse event messages with flexible XML structure handling
// Handles Reolink RLC-811A, RLC-822A, and similar cameras
// XML structure:
//   wsnt:Message > tt:Message[PropertyOperation] > tt:Source + tt:Data > tt:SimpleItem[Name,Value]
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
  operation = "Initialized";  // Default operation per ONVIF spec

  if (!msg->Message.__any.elts) {
    Debug(3, "ONVIF: Message has no elements");
    return false;
  }

  // The structure is: wsnt:Message > tt:Message > tt:Source + tt:Data
  // msg->Message.__any.elts points to the tt:Message element
  struct soap_dom_element *tt_message = msg->Message.__any.elts;

  if (!tt_message) {
    Debug(3, "ONVIF: No tt:Message element found");
    return false;
  }

  Debug(4, "ONVIF: Found element: %s", tt_message->name ? tt_message->name : "unnamed");

  // Step 1: Extract PropertyOperation attribute from tt:Message
  if (tt_message->atts) {
    struct soap_dom_attribute *att = tt_message->atts;
    while (att) {
      if (att->name && att->text) {
        const char *attr_name = get_local_name(att->name);
        Debug(4, "ONVIF: tt:Message attribute: %s = %s", att->name, att->text);
        if (std::strcmp(attr_name, "PropertyOperation") == 0) {
          operation = att->text;
          Debug(3, "ONVIF: Found PropertyOperation: %s", operation.c_str());
        }
      }
      att = att->next;
    }
  }

  // Step 2: Find tt:Data element among tt:Message's children
  // Children are: tt:Source, tt:Data (order may vary)
  struct soap_dom_element *data_elt = nullptr;
  struct soap_dom_element *child = tt_message->elts;

  while (child) {
    if (child->name) {
      const char *child_name = get_local_name(child->name);
      Debug(4, "ONVIF: tt:Message child: %s", child->name);

      if (std::strcmp(child_name, "Data") == 0) {
        data_elt = child;
        Debug(4, "ONVIF: Found tt:Data element");
        break;
      }
    }
    child = child->next;
  }

  // Step 3: If we found Data, look for SimpleItem within it
  if (data_elt && data_elt->elts) {
    struct soap_dom_element *item = data_elt->elts;
    while (item) {
      if (item->name) {
        const char *item_elem_name = get_local_name(item->name);
        Debug(4, "ONVIF: Data child: %s", item->name);

        if (std::strcmp(item_elem_name, "SimpleItem") == 0) {
          std::string item_name, item_value;
          if (extract_simple_item_attrs(item, item_name, item_value)) {
            Debug(4, "ONVIF: SimpleItem in Data: Name=%s Value=%s",
                  item_name.c_str(), item_value.c_str());

            if (is_data_simple_item(item_name)) {
              value = item_value;
              Debug(3, "ONVIF: Extracted data value: %s=%s (operation=%s)",
                    item_name.c_str(), value.c_str(), operation.c_str());
              return true;
            }
          }
        } else if (std::strcmp(item_elem_name, "ElementItem") == 0) {
          // ElementItem might have child elements with text values
          if (item->elts && item->elts->text) {
            value = item->elts->text;
            Debug(3, "ONVIF: Found ElementItem value: %s", value.c_str());
            return true;
          }
        }
      }
      item = item->next;
    }
  }

  // Step 4: Fallback - some cameras may have a different structure
  // Try to find SimpleItem anywhere in the message using depth-first search
  if (value.empty()) {
    Debug(4, "ONVIF: Data element not found or empty, trying fallback search");

    // Stack for iterative depth-first search (avoid recursion)
    std::vector<struct soap_dom_element*> stack;
    stack.push_back(tt_message);

    while (!stack.empty() && value.empty()) {
      struct soap_dom_element *elt = stack.back();
      stack.pop_back();

      if (!elt) continue;

      // Check if this element has PropertyOperation (we might have missed it)
      if (operation == "Initialized" && elt->atts) {
        struct soap_dom_attribute *att = elt->atts;
        while (att) {
          if (att->name && att->text) {
            const char *attr_name = get_local_name(att->name);
            if (std::strcmp(attr_name, "PropertyOperation") == 0) {
              operation = att->text;
              Debug(3, "ONVIF: Found PropertyOperation in fallback: %s", operation.c_str());
            }
          }
          att = att->next;
        }
      }

      // Check if this is a SimpleItem in a Data context
      if (elt->name) {
        const char *elem_name = get_local_name(elt->name);

        if (std::strcmp(elem_name, "SimpleItem") == 0) {
          std::string item_name, item_value;
          if (extract_simple_item_attrs(elt, item_name, item_value)) {
            if (is_data_simple_item(item_name)) {
              value = item_value;
              Debug(3, "ONVIF: Fallback found data value: %s=%s",
                    item_name.c_str(), value.c_str());
              return true;
            }
          }
        }
      }

      // Add siblings and children to stack (process children first)
      if (elt->next) {
        stack.push_back(elt->next);
      }
      if (elt->elts) {
        stack.push_back(elt->elts);
      }
    }
  }

  // Step 5: Legacy fallback for old cameras
  // This preserves compatibility with cameras that worked with the original code
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

  Debug(2, "ONVIF: Could not parse event message value for topic: %s", topic.c_str());
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
