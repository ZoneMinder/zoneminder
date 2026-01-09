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

#include <mutex>
#include <string>
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
  bool alarmed;
  bool healthy;
  std::string last_topic;
  std::string last_value;
  void SetNoteSet(Event::StringSet &noteSet);
#ifdef WITH_GSOAP
  struct soap *soap = nullptr;
  _tev__CreatePullPointSubscription request;
  _tev__CreatePullPointSubscriptionResponse response;
  _tev__PullMessages tev__PullMessages;
  _tev__PullMessagesResponse tev__PullMessagesResponse;
  _wsnt__Renew wsnt__Renew;
  _wsnt__RenewResponse wsnt__RenewResponse;
  PullPointSubscriptionBindingProxy proxyEvent;
  void set_credentials(struct soap *soap);
  bool try_usernametoken_auth;  // Track if we should try plain auth
  int retry_count;  // Track retry attempts
  int max_retries;  // Maximum retry attempts before giving up
  std::string discovered_event_endpoint;  // Store discovered endpoint
  SystemTimePoint last_retry_time;  // Time of last retry attempt
  bool warned_initialized_repeat;  // Track if we've warned about repeated Initialized messages
  std::unordered_map<std::string, int> initialized_count;  // Track Initialized message count per topic

  // Configurable timeout values (can be set via onvif_options)
  std::string pull_timeout;  // Default "PT20S"
  std::string subscription_timeout;  // Default "PT60S"
  std::string soap_log_file;  // SOAP message logging file (empty = disabled)
  FILE *soap_log_fd;  // File descriptor for SOAP logging

  // Subscription renewal tracking
  SystemTimePoint subscription_termination_time;  // When subscription expires
  SystemTimePoint next_renewal_time;  // When to perform next renewal (termination - 10s)

  // Helper methods
  void enable_soap_logging(const std::string &log_path);  // Enable SOAP message logging
  void disable_soap_logging();  // Disable SOAP message logging
  bool interpret_alarm_value(const std::string &value);  // Interpret alarm value from various formats
  bool parse_event_message(wsnt__NotificationMessageHolderType *msg, std::string &topic, std::string &value, std::string &operation);
  bool matches_topic_filter(const std::string &topic, const std::string &filter);
  void parse_onvif_options();  // Parse options from parent->onvif_options
  int get_retry_delay();  // Calculate exponential backoff delay
  void update_renewal_times(time_t termination_time);  // Update subscription renewal tracking times
#endif
  std::unordered_map<std::string, std::string> alarms;
  std::mutex   alarms_mutex;
 public:
  explicit ONVIF(Monitor *parent_);
  ~ONVIF();
  void start();
  void WaitForMessage();
  bool isAlarmed() {
    std::unique_lock<std::mutex> lck(alarms_mutex);
    return alarmed;
  };
  void setAlarmed(bool p_alarmed) { alarmed = p_alarmed; };
  bool isHealthy() const { return healthy; };
  void setNotes(Event::StringSet &noteSet) { SetNoteSet(noteSet); };
};

#endif // ZM_MONITOR_ONVIF_H
