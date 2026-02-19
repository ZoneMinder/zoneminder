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
#include "zm_signal.h"
#include "zm_time.h"
#include "zm_utils.h"

#include <cstdint>
#include <cstring>

#ifdef WITH_GSOAP
#include "url.hpp"

// ONVIF configuration constants
namespace {
  const int ONVIF_MAX_RETRIES_LIMIT = 100;  // Upper limit for max_retries option
  const int ONVIF_RETRY_DELAY_CAP = 300;    // Cap retry delay at 5 minutes
  const int ONVIF_RETRY_EXPONENT_LIMIT = 9; // 2^9 = 512, cap before overflow
  const int ONVIF_RENEWAL_ADVANCE_SECONDS = 60;  // Renew subscription N seconds before expiration
  const int ONVIF_COOLDOWN_RESET_SECONDS = 300;  // Reset retry_count after 5 minutes of failure
  const int ONVIF_DEFAULT_TIMESTAMP_VALIDITY = 60;  // WS-Security timestamp validity in seconds

  // Format seconds as ISO 8601 duration string (e.g., 5 -> "PT5S")
  inline std::string FormatDurationSeconds(int seconds) {
    return "PT" + std::to_string(seconds) + "S";
  }
}

std::string SOAP_STRINGS[] = {
    "SOAP_OK",              // 0
    "SOAP_CLI_FAULT",       // 1
    "SOAP_SVR_FAULT",       // 2
    "SOAP_TAG_MISMATCH",    // 3
    "SOAP_TYPE",            // 4
    "SOAP_SYNTAX_ERROR",    // 5
    "SOAP_NO_TAG",          // 6
    "SOAP_IOB",             // 7
    "SOAP_MUSTUNDERSTAND",  // 8
    "SOAP_NAMESPACE",       // 9
    "SOAP_USER_ERROR",      // 10
    "SOAP_FATAL_ERROR",     // 11
    "SOAP_FAULT",           // 12
};

ONVIF::ONVIF(Monitor *parent_) :
  parent(parent_)
  ,alarmed_(false)
  ,healthy_(false)
  ,closes_event(false)
  ,soap(nullptr)
  ,try_usernametoken_auth(false)
  ,retry_count(0)
  ,max_retries(10)
  ,warned_pull_auth_failure(false)
  ,has_valid_subscription_(false)
  ,warned_initialized_repeat(false)
  ,pull_timeout_seconds(1)
  ,subscription_timeout_seconds(300)
  ,soap_log_fd(nullptr)
  ,subscription_termination_time()
  ,next_renewal_time()
  ,use_absolute_time_for_renewal(false)
  ,renewal_enabled(true)
  ,camera_clock_offset(0)
  ,expire_alarms_enabled(true)
  ,timestamp_validity_seconds(ONVIF_DEFAULT_TIMESTAMP_VALIDITY)
  ,terminate_(false)
{
  parse_onvif_options();

  // Clamp pull_timeout_seconds to be less than renewal advance time
  if (pull_timeout_seconds >= ONVIF_RENEWAL_ADVANCE_SECONDS) {
    Warning("ONVIF: pull_timeout %ds must be less than renewal advance time (%ds). Adjusting.",
            pull_timeout_seconds, ONVIF_RENEWAL_ADVANCE_SECONDS);
    pull_timeout_seconds = ONVIF_RENEWAL_ADVANCE_SECONDS - 1;
  }

  // Build endpoint URL before initializing soap context (InitSoapContext needs it)
  Url url(parent->onvif_url);
  if (parent->onvif_url.empty()) {
    url = Url(parent->path);
    url.scheme("http");
    url.path("/onvif/device_service");
    Debug(1, "ONVIF defaulting url to %s", url.str().c_str());
  }
  event_endpoint_url_ = url.str() + parent->onvif_events_path;

  if (!InitSoapContext()) {
    Error("ONVIF: Failed to initialize SOAP context in constructor");
    return;
  }

  if (parent->soap_wsa_compl) {
    Debug(2, "ONVIF: WS-Addressing enabled for requests");
  } else {
    Debug(2, "ONVIF: WS-Addressing disabled for requests (plugin still registered for responses)");
  }
}

ONVIF::~ONVIF() {
  // Stop the polling thread first
  terminate_ = true;
  if (thread_.joinable()) {
    thread_.join();
  }

  if (soap != nullptr) {
    Debug(1, "ONVIF: Tearing Down");
    //We have lost ONVIF clear previous alarm topics
    alarms.clear();
    //Set alarmed to false so we don't get stuck recording
    setAlarmed(false);
    Debug(1, "ONVIF: Alarms Cleared: Alarms count is %zu, alarmed is %s", alarms.size(), isAlarmed() ? "true": "false");

    // Only attempt unsubscribe if we have a valid subscription
    if (has_valid_subscription_) {
      _wsnt__Unsubscribe wsnt__Unsubscribe;
      _wsnt__UnsubscribeResponse wsnt__UnsubscribeResponse;
      set_credentials(soap);

      bool use_wsa = parent->soap_wsa_compl;
      if (!use_wsa || do_wsa_request(subscription_address_.c_str(), "UnsubscribeRequest")) {
        int result = proxyEvent.Unsubscribe(subscription_address_.c_str(), nullptr,
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
      has_valid_subscription_ = false;
    } else {
      Debug(2, "ONVIF: No valid subscription to unsubscribe in destructor");
    }

    disable_soap_logging();
    soap_destroy(soap);
    soap_end(soap);
    soap_free(soap);
    soap = nullptr;
  }  // end if soap
}

void ONVIF::start() {
  // Start the polling thread if not already running
  // Thread will handle subscription setup and reconnection attempts
  if (!thread_.joinable()) {
    Debug(1, "ONVIF: Starting polling thread");
    terminate_ = false;
    thread_ = std::thread(&ONVIF::Run, this);
  } else {
    Debug(1, "ONVIF: Polling thread already running");
  }
}

void ONVIF::Run() {

  Debug(1, "ONVIF: Polling thread started");
  while (!terminate_ && !zm_terminate) {
    if (isHealthy()) {
      WaitForMessage();
    } else {
      // Check if we've exceeded max_retries and need a longer cool-down
      if (retry_count >= max_retries) {
        Warning("ONVIF: Max retries (%d) exceeded, entering cool-down period of %d seconds before reset",
                max_retries, ONVIF_COOLDOWN_RESET_SECONDS);

        // Sleep for cool-down period in 1-second increments to remain responsive
        for (int i = 0; i < ONVIF_COOLDOWN_RESET_SECONDS && !terminate_ && !zm_terminate; i++) {
          std::this_thread::sleep_for(std::chrono::seconds(1));
        }

        // Reset retry count for fresh attempt
        retry_count = 0;
        Info("ONVIF: Cool-down complete, resetting retry count for fresh attempt");
      }

      // Attempt to re-establish connection
      Debug(1, "ONVIF: Unhealthy, attempting to restart subscription (attempt %d/%d)",
            retry_count + 1, max_retries);
      Subscribe();

      // If still unhealthy after start attempt, use exponential backoff
      if (!isHealthy()) {
        int delay_seconds = get_retry_delay();
        Info("ONVIF: Reconnection failed, waiting %d seconds before next attempt (retry %d/%d)",
             delay_seconds, retry_count, max_retries);

        // Sleep in 1-second increments to remain responsive to termination signals
        for (int i = 0; i < delay_seconds && !terminate_ && !zm_terminate; i++) {
          std::this_thread::sleep_for(std::chrono::seconds(1));
        }
      }
    }
  }
  Debug(1, "ONVIF: Polling thread exiting");
}

bool ONVIF::InitSoapContext() {
  if (soap != nullptr) {
    return true;
  }

  Debug(1, "ONVIF: Initializing SOAP context");

  soap = soap_new();
  if (soap == nullptr) {
    Error("ONVIF: Failed to allocate SOAP context");
    return false;
  }

  soap->connect_timeout = 0;
  soap->recv_timeout = 0;
  soap->send_timeout = 0;
  soap_register_plugin(soap, soap_wsse);
  soap_register_plugin(soap, soap_wsa);

  if (!soap_log_file.empty()) {
    enable_soap_logging(soap_log_file);
  }

  proxyEvent = PullPointSubscriptionBindingProxy(soap);
  proxyEvent.soap_endpoint = event_endpoint_url_.c_str();

  return true;
}

void ONVIF::Subscribe() {
  if (!InitSoapContext()) {
    setHealthy(false);
    return;
  }

  set_credentials(soap);

  bool use_wsa = parent->soap_wsa_compl;
  int rc = SOAP_OK;

  if (use_wsa && !do_wsa_request(proxyEvent.soap_endpoint, "CreatePullPointSubscriptionRequest")) {
    soap_destroy(soap);
    soap_end(soap);
    soap_free(soap);
    soap = nullptr;
    return;
  }

  Debug(1, "ONVIF: Creating PullPoint subscription at endpoint: %s", proxyEvent.soap_endpoint);
  rc = proxyEvent.CreatePullPointSubscription(&request, response);

  if (rc != SOAP_OK) {
    const char *detail = soap_fault_detail(soap);
    bool auth_error = (rc == 401 || (detail && std::strstr(detail, "NotAuthorized")));

    if (rc > 8) {
      Error("ONVIF: Couldn't create subscription at %s! %d, fault:%s, detail:%s", event_endpoint_url_.c_str(),
          rc, soap_fault_string(soap), detail ? detail : "null");
    } else {
      Error("ONVIF: Couldn't create subscription at %s! %d %s, fault:%s, detail:%s", event_endpoint_url_.c_str(),
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
        setHealthy(false);
        return;
      }

      Info("ONVIF: Plain authentication succeeded");
      // Fall through to success handling below
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
      setHealthy(false);
      return;
    }
  }

  // Success handling - reached by either:
  // 1. First attempt succeeded (original rc == SOAP_OK)
  // 2. Plain auth retry succeeded (rc == SOAP_OK after retry)
  retry_count = 0;
  has_valid_subscription_ = true;

  // Clear tracking state from previous subscription
  initialized_count.clear();
  warned_initialized_repeat = false;

  Debug(1, "ONVIF: Successfully created PullPoint subscription");

  // Cache the subscription address before any soap_end() calls free the response memory
  subscription_address_ = response.SubscriptionReference.Address;
  Debug(2, "ONVIF: Cached subscription address: %s", subscription_address_.c_str());

  // Update renewal tracking times from initial subscription response
  if (response.wsnt__TerminationTime != 0) {
    update_renewal_times(response.wsnt__CurrentTime, response.wsnt__TerminationTime);
    log_subscription_timing("subscription_created");
  } else {
    Debug(1, "ONVIF: Initial subscription response has no TerminationTime, renewal tracking not set");
  }

  // Clear any stale SOAP headers from previous requests/responses
  soap->header = nullptr;
  set_credentials(soap);

  if (use_wsa && !do_wsa_request(subscription_address_.c_str(), "PullPointSubscription/PullMessagesRequest")) {
    setHealthy(false);
    return;
  }

  _tev__PullMessages tev__PullMessages;
  _tev__PullMessagesResponse tev__PullMessagesResponse;
  std::string pull_timeout_str = FormatDurationSeconds(pull_timeout_seconds);
  tev__PullMessages.Timeout = pull_timeout_str.c_str();
  tev__PullMessages.MessageLimit = 10;

  Debug(2, "ONVIF: Using pull_timeout=%ds, subscription_timeout=%ds at %s",
      pull_timeout_seconds, subscription_timeout_seconds, subscription_address_.c_str());
  if ((proxyEvent.PullMessages(subscription_address_.c_str(), nullptr, &tev__PullMessages, tev__PullMessagesResponse) != SOAP_OK) &&
      (soap->error != SOAP_EOF)
     ) { //SOAP_EOF could indicate no messages to pull.
    Error("ONVIF: Couldn't do initial event pull! Error %i %s, %s", soap->error, soap_fault_string(soap), soap_fault_detail(soap));
    setHealthy(false);
  } else {
    Debug(1, "ONVIF: Good Initial Pull %i %s, %s", soap->error, soap_fault_string(soap), soap_fault_detail(soap));
    setHealthy(true);
  }

  // Clean up gSOAP allocated memory from initial PullMessages
  soap_destroy(soap);
  soap_end(soap);

  // Perform initial renewal of the subscription
  if (use_wsa) {  // Only if WS-Addressing is enabled
    if (!Renew()) {
      Debug(1, "ONVIF: Initial renewal failed, but continuing");
    }
  }
} // end ONVIF::Subscribe

void ONVIF::WaitForMessage() {
  // Clear any stale SOAP headers from previous requests/responses
  soap->header = nullptr;
  set_credentials(soap);

  bool use_wsa = parent->soap_wsa_compl;

  if (use_wsa) {
    if (!do_wsa_request(subscription_address_.c_str(), "PullPointSubscription/PullMessagesRequest")) {
      return;
    }
  } else {
    Debug(2, "ONVIF: WS-Addressing disabled, not sending addressing headers");
  }

  _tev__PullMessages tev__PullMessages;
  _tev__PullMessagesResponse tev__PullMessagesResponse;
  std::string pull_timeout_str = FormatDurationSeconds(pull_timeout_seconds);
  tev__PullMessages.Timeout = pull_timeout_str.c_str();
  tev__PullMessages.MessageLimit = 10;
  Debug(1, "ONVIF: Starting PullMessageRequest with Timeout=%ds, MessageLimit=%d ...",
        pull_timeout_seconds, tev__PullMessages.MessageLimit);
  int result = proxyEvent.PullMessages(subscription_address_.c_str(), nullptr, &tev__PullMessages, tev__PullMessagesResponse);
    if (result != SOAP_OK) {
      const char *detail = soap_fault_detail(soap);
      const char *fault_string = soap_fault_string(soap);

      if (soap->error != SOAP_EOF) { //Ignore the timeout error
        // Check if this is an authorization failure (SOAP_FAULT with auth-related message)
        bool is_auth_error = (result == SOAP_FAULT &&
            fault_string && (std::strstr(fault_string, "authorization") ||
                            std::strstr(fault_string, "authorized") ||
                            std::strstr(fault_string, "NotAuthorized")));

        if (is_auth_error) {
          // Authorization failure - likely due to clock drift or expired credentials
          // Only log as Error the first time, then demote to Debug to avoid flooding logs
          if (!warned_pull_auth_failure) {
            Error("ONVIF: Authorization failed for PullMessages! This may be caused by clock drift "
                  "between ZoneMinder and camera. result=%d soap->error=%d fault=%s detail=%s "
                  "(timestamp_validity=%ds, camera_clock_offset=%lds)",
                result, soap->error, fault_string, (detail ? detail : "null"),
                timestamp_validity_seconds, static_cast<long>(camera_clock_offset));
            warned_pull_auth_failure = true;
          } else {
            Debug(1, "ONVIF: Authorization failed for PullMessages (repeated). result=%d soap->error=%d "
                  "(timestamp_validity=%ds, camera_clock_offset=%lds)",
                result, soap->error,
                timestamp_validity_seconds, static_cast<long>(camera_clock_offset));
          }
        } else {
          Error("Failed to get ONVIF messages! result=%d soap->error %d, soap_fault_string=%s detail=%s",
              result, soap->error, fault_string, (detail ? detail : "null"));
        }

        retry_count++;
        if (retry_count >= max_retries) {
          Error("ONVIF: Max retries (%d) reached for PullMessages, subscription may be lost", max_retries);
        } else {
          Info("ONVIF: PullMessages failed (attempt %d/%d), will continue trying", retry_count, max_retries);
        }
        setHealthy(false);
      } else {
        // SOAP_EOF - this is just a timeout, not an error
        Debug(2, "ONVIF PullMessage timeout (SOAP_EOF) - no new messages. result=%d soap_fault_string=%s detail=%s",
            result, soap_fault_string(soap), detail ? detail : "null");

        // Don't clear alarms on timeout - they should remain active until explicitly cleared
        // Timeout is not an error, don't increment retry_count

        // Still sweep for expired alarms on timeout - stuck alarms may have expired
        if (expire_alarms_enabled) {
          std::unique_lock<std::mutex> lck(alarms_mutex);
          expire_stale_alarms(std::chrono::system_clock::now());
        }
      }
    } else {
      // Success - reset retry count and warning flags
      if (retry_count > 0) {
        Info("ONVIF: PullMessages succeeded after %d failed attempts", retry_count);
        retry_count = 0;
      }
      warned_pull_auth_failure = false;
      Debug(1, "ONVIF polling : Got Good Response! %i, # of messages %zu", result, tev__PullMessagesResponse.wsnt__NotificationMessage.size());

      // Extract TerminationTime from PullMessagesResponse for per-topic alarm expiry.
      // This is the camera's indication of how long the current subscription/response is valid.
      // Apply the camera clock offset to account for timezone/clock differences.
      SystemTimePoint response_termination;
      bool have_response_termination = false;
      if (tev__PullMessagesResponse.TerminationTime != 0) {
        // Update clock offset from CurrentTime if available
        if (tev__PullMessagesResponse.CurrentTime != 0) {
          time_t our_current_time = std::chrono::system_clock::to_time_t(std::chrono::system_clock::now());
          camera_clock_offset = our_current_time - tev__PullMessagesResponse.CurrentTime;
        }
        // Apply offset to termination time
        time_t adjusted_termination = tev__PullMessagesResponse.TerminationTime + camera_clock_offset;
        response_termination = std::chrono::system_clock::from_time_t(adjusted_termination);
        have_response_termination = true;
        Debug(2, "ONVIF: PullMessagesResponse TerminationTime=%ld adjusted=%ld (offset=%ld) (%s)",
              static_cast<long>(tev__PullMessagesResponse.TerminationTime),
              static_cast<long>(adjusted_termination),
              static_cast<long>(camera_clock_offset),
              SystemTimePointToString(response_termination).c_str());
      }

      {  // Scope for lock
        std::unique_lock<std::mutex> lck(alarms_mutex);

        // Compute termination time for alarm entries: prefer response termination,
        // fall back to existing alarm's time, or epoch for new alarms.
        auto alarm_termination = [&](const std::string &topic) -> SystemTimePoint {
          if (have_response_termination) return response_termination;
          auto it = alarms.find(topic);
          return (it != alarms.end()) ? it->second.termination_time : SystemTimePoint{};
        };

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
                  alarms.size(), isAlarmed() ? "true" : "false");
            if (alarms.empty()) {
              setAlarmed(false);
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
              alarms[last_topic] = AlarmEntry{last_value, alarm_termination(last_topic)};
              if (!isAlarmed()) {
                setAlarmed(true);
                Info("ONVIF Alarm already active on subscription (Initialized): %s", last_topic.c_str());
              }
            } else if (!state_is_active && alarms.count(last_topic) > 0) {
              // We thought there was an alarm, but camera says it's not active
              Debug(2, "ONVIF Syncing with camera: clearing stale alarm for topic: %s", last_topic.c_str());
              alarms.erase(last_topic);
              if (alarms.empty()) {
                setAlarmed(false);
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
                    alarms.size(), isAlarmed() ? "true" : "false");
              if (alarms.empty()) {
                setAlarmed(false);
              }
              if (!closes_event) {
                closes_event = true;
                Info("Setting ClosesEvent (detected Changed to inactive)");
              }
            } else {
              // Alarm turned on
              Info("ONVIF Alarm On (Changed to active): topic=%s value=%s", last_topic.c_str(), last_value.c_str());
              if (alarms.count(last_topic) == 0) {
                alarms[last_topic] = AlarmEntry{last_value, alarm_termination(last_topic)};
                if (!isAlarmed()) {
                  Info("ONVIF Triggered Start Event on topic: %s", last_topic.c_str());
                  setAlarmed(true);
                }
              } else {
                // Update existing alarm value and refresh termination time
                alarms[last_topic] = AlarmEntry{last_value, alarm_termination(last_topic)};
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
                setAlarmed(false);
              }
            } else {
              if (alarms.count(last_topic) == 0) {
                alarms[last_topic] = AlarmEntry{last_value, alarm_termination(last_topic)};
                if (!isAlarmed()) {
                  setAlarmed(true);
                }
              } else {
                alarms[last_topic] = AlarmEntry{last_value, alarm_termination(last_topic)};
              }
            }
          }
          Debug(1, "ONVIF Alarms count is %zu, alarmed is %s", alarms.size(), isAlarmed() ? "true" : "false");
        }  // end foreach msg

        // Sweep and expire alarms whose per-topic TerminationTime has passed.
        // This handles cameras (e.g., Reolink) that send alarm=true but never
        // send the corresponding false. Alarms that are still being re-triggered
        // will have had their TerminationTime refreshed above.
        if (expire_alarms_enabled && !alarms.empty()) {
          expire_stale_alarms(std::chrono::system_clock::now());
        }
      } // end scope for lock

      if (IsRenewalNeeded()) Renew();
    }  // end if SOAP OK/NOT OK

  // Clean up gSOAP allocated memory from PullMessages response
  // This must be called after every SOAP operation to prevent memory growth
  soap_destroy(soap);
  soap_end(soap);

  return;
}

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

  // Only attempt unsubscribe if we actually have a valid subscription
  if (!has_valid_subscription_) {
    Debug(2, "ONVIF: cleanup_subscription called but no valid subscription exists, skipping unsubscribe");
    return;
  }

  Debug(2, "ONVIF: Cleaning up existing subscription");

  _wsnt__Unsubscribe wsnt__Unsubscribe;
  _wsnt__UnsubscribeResponse wsnt__UnsubscribeResponse;

  bool use_wsa = parent->soap_wsa_compl;
  int result = SOAP_OK;

  // Attempt to unsubscribe from the existing subscription
  if (use_wsa) {
    if (do_wsa_request(subscription_address_.c_str(), "UnsubscribeRequest")) {
      result = proxyEvent.Unsubscribe(subscription_address_.c_str(), nullptr,
                                       &wsnt__Unsubscribe, wsnt__UnsubscribeResponse);
    } else {
      // WS-Addressing setup failed - log the error details from soap_wsa_request
      Warning("ONVIF: Failed to set WS-Addressing headers for unsubscribe during cleanup. Error %i %s, %s",
              soap->error, soap_fault_string(soap),
              soap_fault_detail(soap) ? soap_fault_detail(soap) : "null");
      // Don't attempt unsubscribe if WS-Addressing setup failed
      // Note: This is a limitation - subscription may remain active on camera
      // However, attempting unsubscribe with invalid WS-Addressing state would fail anyway
      has_valid_subscription_ = false;
      return;
    }
  } else {
    Debug(2, "ONVIF: Unsubscribing without WS-Addressing during cleanup");
    result = proxyEvent.Unsubscribe(subscription_address_.c_str(), nullptr,
                                     &wsnt__Unsubscribe, wsnt__UnsubscribeResponse);
  }

  if (result != SOAP_OK) {
    Warning("ONVIF: Unsubscribe failed during cleanup. Error %i %s, %s",
            soap->error, soap_fault_string(soap),
            soap_fault_detail(soap) ? soap_fault_detail(soap) : "null");
  } else {
    Debug(2, "ONVIF: Successfully unsubscribed during cleanup");
  }

  // Mark subscription as invalid regardless of unsubscribe result
  has_valid_subscription_ = false;
}

// Parse ONVIF options from the onvif_options string
// Format: key1=value1,key2=value2
// Supported options:
//   pull_timeout=5 - Timeout in seconds for PullMessages requests (default: 1)
//   subscription_timeout=300 - Timeout in seconds for subscription renewal (default: 300)
//   max_retries=10 - Maximum retry attempts (default: 10)
//   timestamp_validity=60 - WS-Security timestamp validity in seconds (default: 60, range: 10-600)
//                          Increase if getting auth errors due to clock drift between ZM and camera
//   soap_log=/path/to/logfile - Enable SOAP message logging
//   renewal_enabled=false - Disable subscription renewal
//   expire_alarms=false - Disable per-topic alarm expiry
void ONVIF::parse_onvif_options() {
  if (parent->onvif_options.empty()) {
    Info("ONVIF: Using pull_timeout=%ds, subscription_timeout=%ds, timestamp_validity=%ds, renewal_enabled=%s, expire_alarms=%s",
         pull_timeout_seconds, subscription_timeout_seconds, timestamp_validity_seconds,
         renewal_enabled ? "true" : "false",
         expire_alarms_enabled ? "true" : "false");
    return;
  }

  Debug(2, "ONVIF: Parsing options: %s", parent->onvif_options.c_str());

  for (const std::string &option : Split(parent->onvif_options, ',')) {
    auto [key, value] = PairSplit(option, '=');
    if (key.empty()) continue;

    if (key == "pull_timeout") {
      if (StartsWith(value, "PT")) {
        Warning("ONVIF: ISO 8601 duration format (e.g., 'PT5S') is no longer supported for pull_timeout. "
                "Please use seconds (e.g., '5'). Using default %d seconds.", pull_timeout_seconds);
      } else {
        try {
          int val = std::stoi(value);
          if (val > 0) {
            pull_timeout_seconds = val;
            Debug(2, "ONVIF: Set pull_timeout to %d seconds", pull_timeout_seconds);
          } else {
            Warning("ONVIF: Invalid pull_timeout value '%s', using default %d seconds", value.c_str(), pull_timeout_seconds);
          }
        } catch (const std::exception &e) {
          Warning("ONVIF: Invalid pull_timeout value '%s': %s. Using default %d seconds", value.c_str(), e.what(), pull_timeout_seconds);
        }
      }
    } else if (key == "subscription_timeout") {
      if (StartsWith(value, "PT")) {
        Warning("ONVIF: ISO 8601 duration format (e.g., 'PT300S') is no longer supported for subscription_timeout. "
                "Please use seconds (e.g., '300'). Using default %d seconds.", subscription_timeout_seconds);
      } else {
        try {
          int val = std::stoi(value);
          if (val > 0) {
            subscription_timeout_seconds = val;
            Debug(2, "ONVIF: Set subscription_timeout to %d seconds", subscription_timeout_seconds);
          } else {
            Warning("ONVIF: Invalid subscription_timeout value '%s', using default %d seconds", value.c_str(), subscription_timeout_seconds);
          }
        } catch (const std::exception &e) {
          Warning("ONVIF: Invalid subscription_timeout value '%s': %s. Using default %d seconds", value.c_str(), e.what(), subscription_timeout_seconds);
        }
      }
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
    } else if (key == "closes_event") {
      closes_event = true;
    } else if (key == "renewal_enabled") {
      if (value == "false" || value == "0" || value == "no") {
        renewal_enabled = false;
        Info("ONVIF: Renewal disabled via option - will re-subscribe when subscription expires");
      } else {
        renewal_enabled = true;
      }
    } else if (key == "expire_alarms") {
      if (value == "false" || value == "0" || value == "no") {
        expire_alarms_enabled = false;
        Info("ONVIF: Per-topic alarm expiry disabled via option");
      } else {
        expire_alarms_enabled = true;
      }
    } else if (key == "timestamp_validity") {
      try {
        int val = std::stoi(value);
        if (val >= 10 && val <= 600) {  // Allow 10 seconds to 10 minutes
          timestamp_validity_seconds = val;
          Debug(2, "ONVIF: Set timestamp_validity to %d seconds", timestamp_validity_seconds);
        } else {
          Warning("ONVIF: timestamp_validity %d out of range (10-600), using default %d",
                  val, timestamp_validity_seconds);
        }
      } catch (const std::exception &e) {
        Warning("ONVIF: Invalid timestamp_validity value '%s': %s", value.c_str(), e.what());
      }
    }
  }

  Info("ONVIF: Using pull_timeout=%ds, subscription_timeout=%ds, timestamp_validity=%ds, renewal_enabled=%s, expire_alarms=%s",
       pull_timeout_seconds, subscription_timeout_seconds, timestamp_validity_seconds,
       renewal_enabled ? "true" : "false",
       expire_alarms_enabled ? "true" : "false");
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
// camera_current_time: Camera's current time from the ONVIF response
// termination_time: Unix timestamp (time_t) indicating when subscription expires
void ONVIF::update_renewal_times(time_t camera_current_time, time_t termination_time) {
  if (termination_time <= 0) {
    Warning("ONVIF: Received invalid TerminationTime (%ld), not updating renewal tracking",
            static_cast<long>(termination_time));
    return;
  }

  // Calculate clock offset between camera and our system
  // This handles timezone differences and clock drift
  auto now = std::chrono::system_clock::now();
  time_t our_current_time = std::chrono::system_clock::to_time_t(now);

  if (camera_current_time > 0) {
    camera_clock_offset = our_current_time - camera_current_time;
    if (std::abs(camera_clock_offset) > 5) {  // More than 5 seconds difference
      Debug(1, "ONVIF: Clock offset detected: %ld seconds (camera is %s our clock)",
            static_cast<long>(camera_clock_offset),
            camera_clock_offset > 0 ? "behind" : "ahead of");
    }
  } else {
    Debug(2, "ONVIF: No CurrentTime in response, using previous offset of %ld seconds",
          static_cast<long>(camera_clock_offset));
  }

  // Adjust termination time by the clock offset
  time_t adjusted_termination = termination_time + camera_clock_offset;

  // Convert adjusted time_t to SystemTimePoint
  subscription_termination_time = std::chrono::system_clock::from_time_t(adjusted_termination);

  Debug(2, "ONVIF: TerminationTime raw=%ld adjusted=%ld (offset=%ld)",
        static_cast<long>(termination_time),
        static_cast<long>(adjusted_termination),
        static_cast<long>(camera_clock_offset));

  // Validate that termination time is in the future (now was computed above)
  if (subscription_termination_time <= now) {
    if (!use_absolute_time_for_renewal) {
      Warning("ONVIF: Received TerminationTime in the past %ld %s < %s, switching to absolute time for future renewals",
          static_cast<long>(termination_time),
          SystemTimePointToString(subscription_termination_time).c_str(),
          SystemTimePointToString(now).c_str());
      use_absolute_time_for_renewal = true;
    } else {
      Warning("ONVIF: Received TerminationTime in the past %ld %s < %s, despite using absolute time. "
          "Disabling renewal - will re-subscribe when subscription expires.",
          static_cast<long>(termination_time),
          SystemTimePointToString(subscription_termination_time).c_str(),
          SystemTimePointToString(now).c_str());
      renewal_enabled = false;
    }
    return;
  }

  // Calculate renewal time: N seconds before termination
  next_renewal_time = subscription_termination_time - std::chrono::seconds(ONVIF_RENEWAL_ADVANCE_SECONDS);

  log_subscription_timing("Updated subscription");
}  // end void ONVIF::update_renewal_times(time_t camera_current_time, time_t termination_time)

// Check if renewal tracking has been initialized
// Returns false if tracking times are at epoch (uninitialized), true otherwise
bool ONVIF::is_renewal_tracking_initialized() const {
  return next_renewal_time.time_since_epoch().count() != 0;
}

// Log subscription timing information for debugging
// Shows current time, termination time, renewal time, and remaining time
void ONVIF::log_subscription_timing(const char* context) {
  if (!is_renewal_tracking_initialized()) {
    Debug(1, "ONVIF [%s]: Subscription timing not initialized", context);
    return;
  }

  auto now = std::chrono::system_clock::now();
  auto seconds_until_termination = std::chrono::duration_cast<std::chrono::seconds>(
    subscription_termination_time - now).count();
  auto seconds_until_renewal = std::chrono::duration_cast<std::chrono::seconds>(
    next_renewal_time - now).count();
  
  Debug(1, "ONVIF [%s]: Subscription terminates at %s (in %jds), renewal at %s (in %jds)",
       context, SystemTimePointToString(subscription_termination_time).c_str(),
       static_cast<intmax_t>(seconds_until_termination),
       SystemTimePointToString(next_renewal_time).c_str(),
       static_cast<intmax_t>(seconds_until_renewal));
  // Log at debug level when we're getting close to termination
  // This is informational - actual renewal failures are logged as Error/Warning in Renew()
  // If renewal is enabled, we will renew or re-subscribe. If disabled, we will re-subscribe.
  if (seconds_until_termination < ONVIF_RENEWAL_ADVANCE_SECONDS && seconds_until_termination > 0) {
    Debug(1, "ONVIF: Subscription terminating in %jd seconds%s",
          static_cast<intmax_t>(seconds_until_termination),
          renewal_enabled ? ", will renew" : " (renewal disabled, will re-subscribe)");
  }
}


// Perform ONVIF subscription renewal
// Returns true if renewal succeeded or is not supported, false on error
bool ONVIF::Renew() {
  soap->header = nullptr;
  set_credentials(soap);
  _wsnt__Renew wsnt__Renew;
  _wsnt__RenewResponse wsnt__RenewResponse;

  std::string termination_time_str;

  if (use_absolute_time_for_renewal) {
    // Calculate absolute termination time: current time + subscription duration
    time_t now = time(nullptr);
    time_t absolute_termination = now + subscription_timeout_seconds;
    termination_time_str = format_absolute_time_iso8601(absolute_termination);

    if (termination_time_str.empty()) {
      Error("ONVIF: Failed to format absolute time for renewal");
      return false;
    }

    Debug(1, "ONVIF: Setting renew termination time to absolute time: %s (camera requires absolute time format)",
          termination_time_str.c_str());
  } else {
    // Use duration format (default behavior)
    termination_time_str = FormatDurationSeconds(subscription_timeout_seconds);
    Debug(1, "ONVIF: Setting renew termination time to duration: %s", termination_time_str.c_str());
  }

  wsnt__Renew.TerminationTime = &termination_time_str;

  bool use_wsa = parent->soap_wsa_compl;

  if (use_wsa && !do_wsa_request(subscription_address_.c_str(), "RenewRequest")) {
    Debug(1, "ONVIF: WS-Addressing setup failed for renewal, cleaning up subscription");
    cleanup_subscription();
    setHealthy(false);
    return false;
  }

  if (proxyEvent.Renew(subscription_address_.c_str(), nullptr, &wsnt__Renew, wsnt__RenewResponse) != SOAP_OK) {
    Debug(1, "ONVIF: Couldn't do Renew! Error %i %s, %s", soap->error, soap_fault_string(soap), soap_fault_detail(soap));
    if (soap->error == 12) {  // ActionNotSupported
      Debug(2, "ONVIF: Renew not supported by device, continuing without renewal");
      setHealthy(true);
      soap_destroy(soap);
      soap_end(soap);
      return true;  // Not a fatal error
    } else {
      // Renewal failed - clean up the subscription to prevent leaks
      Warning("ONVIF: Renewal failed, cleaning up subscription to prevent leak");
      soap_destroy(soap);
      soap_end(soap);
      cleanup_subscription();
      setHealthy(false);
      return false;
    }
  }

  Debug(2, "ONVIF: Subscription renewed successfully");
  setHealthy(true);

  // Update renewal times from renew response
  // CurrentTime is optional (time_t*) in RenewResponse
  if (wsnt__RenewResponse.TerminationTime != 0) {
    time_t current_time = wsnt__RenewResponse.CurrentTime ? *wsnt__RenewResponse.CurrentTime : 0;
    update_renewal_times(current_time, wsnt__RenewResponse.TerminationTime);
    log_subscription_timing("renewed");
  } else {
    Debug(1, "No TerminationTime in RenewResponse");
  }

  // Clean up gSOAP allocated memory from Renew response
  soap_destroy(soap);
  soap_end(soap);

  return true;
}  // bool ONVIF::Renew()

// Check if subscription renewal is needed
// Returns true if renewal should be performed now, false if not yet needed
bool ONVIF::IsRenewalNeeded() {
  // Check if renewal is disabled (camera doesn't support it or returns invalid times)
  if (!renewal_enabled) {
    Debug(2, "ONVIF: Renewal disabled, will re-subscribe when subscription expires");
    return false;
  }

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
    Debug(1, "ONVIF: Subscription renewal needed (overdue by %jd seconds)", static_cast<intmax_t>(seconds_overdue));
    return true;
  }

  log_subscription_timing("renewal check");
  return false;
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
}

//ONVIF Set Credentials
void ONVIF::set_credentials(struct soap *soap) {
  if (!soap) {
    Error("ONVIF: set_credentials called with null soap context");
    return;
  }
  soap_wsse_delete_Security(soap);
  // Use configurable timestamp validity (default 60 seconds) to handle clock drift
  // between ZoneMinder and the camera. The old value of 10 seconds was too short
  // and caused "not authorized" errors when clocks were slightly out of sync.
  soap_wsse_add_Timestamp(soap, "Time", timestamp_validity_seconds);

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
// Handles formats from: Hikvision, Dahua, Axis, Reolink, Amcrest, Hanwha, etc.
bool ONVIF::interpret_alarm_value(const std::string &value) {
  if (value.empty()) {
    return false;  // Empty value = no alarm
  }

  // Convert to lowercase once for case-insensitive comparison
  std::string lower_value = StringToLower(value);

  // Check for explicit false/inactive values
  if (lower_value == "false" ||
      lower_value == "inactive" ||
      lower_value == "off" ||
      lower_value == "no" ||
      lower_value == "idle" ||
      lower_value == "normal" ||
      lower_value == "closed") {
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

  // Any other non-zero/non-empty value is considered active
  // This handles "true", "active", "1", "001", direction strings, etc.
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
// Based on ONVIF Event Service Specification and common camera implementations:
// - Hikvision, Dahua, Axis, Reolink, Amcrest, Hanwha/Samsung, etc.
static bool is_data_simple_item(const std::string &item_name) {
  // Convert to lowercase for case-insensitive comparison
  std::string lower_name = StringToLower(item_name);

  // Items starting with "is" are typically boolean state fields
  // Examples: IsMotion, IsTamper, IsInside, IsCrossing, IsActive, IsHandRaised, etc.
  if (lower_name.length() >= 2 && lower_name[0] == 'i' && lower_name[1] == 's') {
    return true;
  }

  // Items ending with "state" or "alarm" are typically state indicators
  if (lower_name.length() >= 5) {
    if (lower_name.compare(lower_name.length() - 5, 5, "state") == 0 ||
        lower_name.compare(lower_name.length() - 5, 5, "alarm") == 0) {
      return true;
    }
  }

  // Known data field names from ONVIF spec and various camera manufacturers
  if (lower_name == "state" ||           // Common: most cameras
      lower_name == "logicalstate" ||    // Digital I/O (Hikvision, Dahua)
      lower_name == "active" ||          // Axis cameras
      lower_name == "value" ||           // Generic value field
      lower_name == "alarm" ||           // Generic alarm field
      lower_name == "motion") {          // Some cameras use just "Motion"
    return true;
  }

  // Analytics/counting fields (these may have non-boolean values)
  if (lower_name == "count" ||           // Object counting
      lower_name == "level" ||           // Audio detection level
      lower_name == "direction" ||       // Line crossing direction
      lower_name == "objectid" ||        // Object tracking ID
      lower_name == "speed" ||           // Speed detection
      lower_name == "region" ||          // Region identifier
      lower_name == "percentage") {      // Fill percentage
    return true;
  }

  return false;
}

// Helper function to parse event messages with flexible XML structure handling
// Supports ONVIF-compliant cameras including:
//   Reolink (RLC-811A, RLC-822A, etc.), Hikvision, Dahua, Axis, Amcrest, Hanwha/Samsung
//
// ONVIF Event Service XML structure per specification:
//   wsnt:Message > tt:Message[UtcTime, PropertyOperation] >
//     tt:Source > tt:SimpleItem[Name,Value]    (identifies the source, e.g., VideoSourceToken)
//     tt:Key > tt:SimpleItem[Name,Value]       (optional, additional identifiers)
//     tt:Data > tt:SimpleItem[Name,Value]      (the actual event data we want)
//            > tt:ElementItem[Name]            (complex data with child elements)
//
// PropertyOperation values per ONVIF spec:
//   "Initialized" - Current state at subscription time
//   "Changed"     - State actually changed (alarm on/off)
//   "Deleted"     - Property no longer exists
//
// gSOAP DOM mapping for msg->Message.__any (which IS the tt:Message element):
//   msg->Message.__any.atts -> attributes of tt:Message (PropertyOperation, UtcTime)
//   msg->Message.__any.elts -> first child element of tt:Message (tt:Source)
//   To find tt:Data, iterate through elts and siblings using ->next
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

  // gSOAP DOM: msg->Message.__any IS the tt:Message element
  // - msg->Message.__any.atts = attributes of tt:Message (PropertyOperation, UtcTime)
  // - msg->Message.__any.elts = first child of tt:Message (tt:Source or tt:Data)

  // Step 1: Extract PropertyOperation attribute from tt:Message
  // The attributes are directly on msg->Message.__any, not on its children
  if (msg->Message.__any.atts) {
    struct soap_dom_attribute *att = msg->Message.__any.atts;
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

  if (!msg->Message.__any.elts) {
    Debug(3, "ONVIF: Message has no child elements");
    return false;
  }

  // Step 2: Find tt:Data element among tt:Message's children
  // msg->Message.__any.elts is the first child (usually tt:Source)
  // Iterate through siblings using ->next to find tt:Data
  struct soap_dom_element *data_elt = nullptr;
  struct soap_dom_element *child = msg->Message.__any.elts;

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
    // Start from the first child of tt:Message
    std::vector<struct soap_dom_element*> stack;
    if (msg->Message.__any.elts) {
      stack.push_back(msg->Message.__any.elts);
    }

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

// Sweep through active alarms and expire any whose per-topic TerminationTime has passed.
// This handles cameras that send alarm=true but never send the corresponding false
// (e.g., Reolink PeopleDetect/VehicleDetect topics).
// Must be called with alarms_mutex held.
void ONVIF::expire_stale_alarms(const SystemTimePoint &now) {
  auto it = alarms.begin();
  while (it != alarms.end()) {
    // Skip entries with no termination time set (epoch = uninitialized)
    if (it->second.termination_time.time_since_epoch().count() == 0) {
      ++it;
      continue;
    }
    if (it->second.termination_time <= now) {
      Info("ONVIF: Auto-expiring stale alarm for topic=%s (TerminationTime %s has passed)",
           it->first.c_str(),
           SystemTimePointToString(it->second.termination_time).c_str());
      it = alarms.erase(it);
    } else {
      ++it;
    }
  }
  if (alarms.empty()) {
    setAlarmed(false);
  }
}

void ONVIF::SetNoteSet(Event::StringSet &noteSet) {
  std::unique_lock<std::mutex> lck(alarms_mutex);
  if (alarms.empty()) return;

  for (const auto &[topic, entry] : alarms) {
    noteSet.insert(topic + "/" + entry.value);
  }
}

#else  // !WITH_GSOAP

// Minimal stub implementations when gSOAP is not available

ONVIF::ONVIF(Monitor *parent_) :
  parent(parent_)
  ,alarmed_(false)
  ,healthy_(false)
  ,closes_event(false)
{
  Error("ONVIF support not available - ZoneMinder was not compiled with gSOAP");
}

ONVIF::~ONVIF() {
}

void ONVIF::start() {
  Error("ONVIF support not available - ZoneMinder was not compiled with gSOAP");
}

#endif  // WITH_GSOAP
