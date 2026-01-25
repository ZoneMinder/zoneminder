//
// ZoneMinder Monitor ONVIF Class Interface
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

#ifndef ZM_MONITOR_ONVIF_H
#define ZM_MONITOR_ONVIF_H

#include <atomic>
#include <mutex>
#include <string>
#include <thread>
#include <unordered_map>
#include "zm_event.h"
#include "zm_time.h"

#ifdef WITH_GSOAP
#include "soapPullPointSubscriptionBindingProxy.h"
#include "plugin/wsseapi.h"
#include "plugin/wsaapi.h"
#include "plugin/logging.h"
#include <openssl/err.h>
#endif

// Forward declaration
class Monitor;

class ONVIF {
  friend class Monitor;

 protected:
  Monitor *parent;
  std::atomic<bool> alarmed_;
  std::atomic<bool> healthy_;
  bool closes_event;
  std::string last_topic;
  std::string last_value;
  void SetNoteSet(Event::StringSet &noteSet);
#ifdef WITH_GSOAP
  struct soap *soap = nullptr;
  _tev__CreatePullPointSubscription request;
  _tev__CreatePullPointSubscriptionResponse response;
  PullPointSubscriptionBindingProxy proxyEvent;
  void set_credentials(struct soap *soap);
  bool try_usernametoken_auth;  // Track if we should try plain auth
  int retry_count;  // Track retry attempts
  int max_retries;  // Maximum retry attempts before giving up
  std::string event_endpoint_url_;  // Store endpoint URL (must persist for proxyEvent.soap_endpoint)
  bool has_valid_subscription_;  // True only when we have an active subscription to unsubscribe from
  bool warned_initialized_repeat;  // Track if we've warned about repeated Initialized messages
  std::unordered_map<std::string, int> initialized_count;  // Track Initialized message count per topic

  // Configurable timeout values in seconds (can be set via onvif_options)
  int pull_timeout_seconds;  // Default 5 seconds
  int subscription_timeout_seconds;  // Default 300 seconds (5 minutes)
  std::string soap_log_file;  // SOAP message logging file (empty = disabled)
  FILE *soap_log_fd;  // File descriptor for SOAP logging

  // Subscription renewal tracking
  SystemTimePoint subscription_termination_time;  // When subscription expires
  SystemTimePoint next_renewal_time;  // When to perform next renewal (termination - 10s)
  bool use_absolute_time_for_renewal;  // Use absolute time instead of duration for renewals
  bool renewal_enabled;  // Enable renewal attempts (some cameras don't support or return invalid times)

  // Helper methods
  void enable_soap_logging(const std::string &log_path);  // Enable SOAP message logging
  void disable_soap_logging();  // Disable SOAP message logging
  void cleanup_subscription();  // Properly unsubscribe and clean up existing subscription
  bool interpret_alarm_value(const std::string &value);  // Interpret alarm value from various formats
  bool parse_event_message(wsnt__NotificationMessageHolderType *msg, std::string &topic, std::string &value, std::string &operation);
  bool matches_topic_filter(const std::string &topic, const std::string &filter);
  void attempt_subscription();
  void parse_onvif_options();  // Parse options from parent->onvif_options
  int get_retry_delay();  // Calculate exponential backoff delay
  void update_renewal_times(time_t termination_time);  // Update subscription renewal tracking times
  bool is_renewal_tracking_initialized() const;  // Check if renewal tracking has been set up
  void log_subscription_timing(const char* context);  // Log subscription timing information for debugging
  bool Renew();  // Perform subscription renewal, returns true on success
  bool IsRenewalNeeded();  // Check if subscription renewal is needed now
  bool do_wsa_request(const char* address, const char* action);  // Setup WS-Addressing headers for SOAP request
#endif
  std::unordered_map<std::string, std::string> alarms;
  std::mutex   alarms_mutex;

  // Thread management
  std::thread thread_;
  std::atomic<bool> terminate_;
  void Run();
 public:
  explicit ONVIF(Monitor *parent_);
  ~ONVIF();
  void start();
  void WaitForMessage();
  bool isAlarmed() const { return alarmed_.load(std::memory_order_acquire); }
  void setAlarmed(bool p_alarmed) { alarmed_.store(p_alarmed, std::memory_order_release); }
  bool isHealthy() const { return healthy_.load(std::memory_order_acquire); }
  void setHealthy(bool p_healthy) { healthy_.store(p_healthy, std::memory_order_release); }
  void setNotes(Event::StringSet &noteSet) { SetNoteSet(noteSet); };
};

#endif // ZM_MONITOR_ONVIF_H
