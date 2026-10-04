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

// Whether a failed ONVIF request was refused for authentication reasons.
//
// gSOAP surfaces this two ways. A rejection at the HTTP layer never reaches the
// SOAP envelope, so there is no fault to inspect and the result is the status
// code itself -- 401 for a request the camera wants credentials on. A camera
// that answers at the SOAP layer returns SOAP_FAULT instead, and names the
// reason in the fault string, or, on several models, only in the fault detail.
//
// Free rather than a member so it can be tested without an ONVIF object and a
// live soap context. fault_string and detail may be null.
bool ONVIFIsAuthError(int result, const char *fault_string, const char *detail);

// Turn a PullMessagesResponse TerminationTime into the expiry time for the
// alarms it carries, on our clock.
//
// camera_current_time, when non-zero, refreshes clock_offset (our time minus
// the camera's). Returns false, leaving termination untouched, when there is no
// TerminationTime or it is not in the future. Some cameras (Beward) send their
// CurrentTime as the TerminationTime, which would expire each alarm in the
// same pass that raised it.
bool ONVIFAlarmTermination(time_t termination_time, time_t camera_current_time, const SystemTimePoint &now,
                           time_t &clock_offset, SystemTimePoint &termination);

// When to renew a subscription that ends at termination, given that we learnt
// of it at now: ONVIF_RENEWAL_ADVANCE_SECONDS before the end, or halfway
// through for a subscription shorter than twice that. A fixed advance put the
// renewal of a 60 second subscription at its creation time, so it was always
// due.
SystemTimePoint ONVIFNextRenewalTime(const SystemTimePoint &now, const SystemTimePoint &termination);

// Subscription lifetime to assume after a Renew whose response carries no
// TerminationTime: what we asked for, but no more than the camera last granted
// (last_granted, zero when it never said).
std::chrono::seconds ONVIFAssumedLifetime(int requested_seconds, std::chrono::seconds last_granted);

// Lifetime the camera granted, from a termination after now, in whole seconds
// rounded up. Termination times have one-second precision and now does not,
// so truncating would turn a short grant into zero, which means unknown.
std::chrono::seconds ONVIFGrantedLifetime(const SystemTimePoint &now, const SystemTimePoint &termination);

// Whether a failed Renew means the camera does not support renewal. gSOAP
// reports every SOAP fault as SOAP_FAULT; the reason is in the subcode
// (wsa:ActionNotSupported, ter:ActionNotSupported) or the fault string.
// subcode and fault_string may be null.
bool ONVIFIsActionNotSupported(int result, const char *subcode, const char *fault_string);
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
  std::chrono::seconds granted_lifetime;  // Lifetime the camera last reported granting, 0 if never
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
  void assume_renewal_times();
  bool is_renewal_tracking_initialized() const;
  void log_subscription_timing(const char* context);
  bool Renew();
  bool IsRenewalNeeded();
  bool do_wsa_request(const char* address, const char* action);
  void expire_stale_alarms(const SystemTimePoint &now);
#endif  // WITH_GSOAP
};

#endif // ZM_MONITOR_ONVIF_H
