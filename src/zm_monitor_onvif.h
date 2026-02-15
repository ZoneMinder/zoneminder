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
#include <string>
#include "zm_event.h"

#ifdef WITH_GSOAP
#include <mutex>
#include <thread>
#include <unordered_map>
#include "zm_time.h"
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

 public:
  explicit ONVIF(Monitor *parent_);
  ~ONVIF();
  void start();
  bool isAlarmed() const { return alarmed_.load(std::memory_order_acquire); }
  void setAlarmed(bool p_alarmed) { alarmed_.store(p_alarmed, std::memory_order_release); }
  bool isHealthy() const { return healthy_.load(std::memory_order_acquire); }
  void setHealthy(bool p_healthy) { healthy_.store(p_healthy, std::memory_order_release); }
#ifdef WITH_GSOAP
  void setNotes(Event::StringSet &noteSet) { SetNoteSet(noteSet); }
#else
  void setNotes(Event::StringSet &) {}  // No-op without GSOAP
#endif

 protected:
  Monitor *parent;
  std::atomic<bool> alarmed_;
  std::atomic<bool> healthy_;
  bool closes_event;
  std::string last_topic;
  std::string last_value;

#ifdef WITH_GSOAP
  // SOAP context and proxies
  struct soap *soap = nullptr;
  _tev__CreatePullPointSubscription request;
  _tev__CreatePullPointSubscriptionResponse response;
  PullPointSubscriptionBindingProxy proxyEvent;
  std::string subscription_address_;  // Cached copy of response.SubscriptionReference.Address

  // Authentication
  void set_credentials(struct soap *soap);
  bool try_usernametoken_auth;

  // Retry handling
  int retry_count;
  int max_retries;
  bool warned_pull_auth_failure;

  // Subscription state
  std::string event_endpoint_url_;
  bool has_valid_subscription_;
  bool warned_initialized_repeat;
  std::unordered_map<std::string, int> initialized_count;

  // Configurable timeout values
  int pull_timeout_seconds;
  int subscription_timeout_seconds;
  std::string soap_log_file;
  FILE *soap_log_fd;

  // Subscription renewal tracking
  SystemTimePoint subscription_termination_time;
  SystemTimePoint next_renewal_time;
  bool use_absolute_time_for_renewal;
  bool renewal_enabled;
  time_t camera_clock_offset;  // Offset in seconds: our_time - camera_time

  // Alarm tracking
  struct AlarmEntry {
    std::string value;
    SystemTimePoint termination_time;
  };
  std::unordered_map<std::string, AlarmEntry> alarms;
  bool expire_alarms_enabled;
  int timestamp_validity_seconds;  // WS-Security timestamp validity window
  std::mutex alarms_mutex;

  // Thread management
  std::thread thread_;
  std::atomic<bool> terminate_;

  // Private methods
  void Run();
  bool InitSoapContext();
  void Subscribe();
  void WaitForMessage();
  void SetNoteSet(Event::StringSet &noteSet);
  void enable_soap_logging(const std::string &log_path);
  void disable_soap_logging();
  void cleanup_subscription();
  bool interpret_alarm_value(const std::string &value);
  bool parse_event_message(wsnt__NotificationMessageHolderType *msg, std::string &topic, std::string &value, std::string &operation);
  bool matches_topic_filter(const std::string &topic, const std::string &filter);
  void parse_onvif_options();
  int get_retry_delay();
  void update_renewal_times(time_t camera_current_time, time_t termination_time);
  bool is_renewal_tracking_initialized() const;
  void log_subscription_timing(const char* context);
  bool Renew();
  bool IsRenewalNeeded();
  bool do_wsa_request(const char* address, const char* action);
  void expire_stale_alarms(const SystemTimePoint &now);
#endif  // WITH_GSOAP
};

#endif // ZM_MONITOR_ONVIF_H
