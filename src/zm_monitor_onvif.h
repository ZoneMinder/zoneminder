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

// How long to wait before the next PullMessages when one came back with no
// messages after elapsed, having asked the camera to hold it for up to
// pull_timeout_seconds. Some cameras (Beward) answer at once instead of holding
// the long-poll, and polling again immediately loops at tens of requests a
// second.
std::chrono::milliseconds ONVIFEarlyPollWait(std::chrono::steady_clock::duration elapsed, int pull_timeout_seconds);

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

// Termination to assume after a Renew whose response carries no
// TerminationTime: the deadline we asked for (requested_termination), but no
// later than request_time plus what the camera last granted (last_granted,
// zero when it never said). Counted from when the request was sent, not when
// the response arrived, so a slow response cannot move the deadline later.
SystemTimePoint ONVIFAssumedTermination(const SystemTimePoint &request_time,
                                        const SystemTimePoint &requested_termination,
                                        std::chrono::seconds last_granted);

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

// A level alarm raised by one thread and sampled by a slower one.
//
// The ONVIF poll thread raises and clears the alarm as the camera reports a
// topic active or inactive; the analysis thread samples it once per frame. A
// momentary alarm -- a DetectedSound pulse that goes active then inactive
// between two analysis frames -- was raised and cleared on the raw level before
// any frame sampled it, so the event recorded no alarm frames (refs #5301). A
// motion alarm stays active for seconds and so was always sampled.
//
// raise() marks the level active and arms a hold of minimum_hold_ observations.
// clear() drops the level. observe() is the analysis thread saying it has
// sampled the alarm, which spends one unit of the hold. alarmed() stays true
// from a raise() until the level is down AND the hold is spent, so a pulse is
// reported on at least minimum_hold_ frames however short it was. observe()
// while the level is still up does not end the alarm -- it stays alarmed until
// clear().
//
// minimum_hold_ defaults to one frame. Holding for more (set_minimum_hold)
// keeps a momentary pulse alarmed across an event split: with close-on-alarm
// and continuous recording the running event is closed and a new one opened
// when the alarm begins, and a one-frame pulse otherwise leaves all its scored
// frames in the event that is closing, so the new event records none (refs
// #5301).
class AlarmLatch {
 public:
  // Report a momentary pulse for at least this many observe() calls. Clamped to
  // a minimum of one so a raise is always seen on at least one frame.
  void set_minimum_hold(unsigned int frames) {
    minimum_hold_.store(frames < 1 ? 1 : frames, std::memory_order_release);
  }
  void raise() {
    active_.store(true, std::memory_order_release);
    hold_.store(minimum_hold_.load(std::memory_order_acquire), std::memory_order_release);
  }
  void clear() { active_.store(false, std::memory_order_release); }
  void observe() {
    unsigned int remaining = hold_.load(std::memory_order_acquire);
    while (remaining > 0 &&
           !hold_.compare_exchange_weak(remaining, remaining - 1,
                                        std::memory_order_acq_rel, std::memory_order_acquire)) {
    }
  }
  bool alarmed() const {
    return active_.load(std::memory_order_acquire) ||
           hold_.load(std::memory_order_acquire) > 0;
  }
  // The raw level, ignoring an unspent hold: whether a topic is active now.
  bool active() const { return active_.load(std::memory_order_acquire); }

 private:
  std::atomic<bool> active_{false};          // a topic is currently active
  std::atomic<unsigned int> hold_{0};        // observe() calls still owed to a pulse
  std::atomic<unsigned int> minimum_hold_{1};  // frames a pulse is held for
};

class ONVIF {
  friend class Monitor;

 public:
  explicit ONVIF(Monitor *parent_);
  ~ONVIF();
  void start();
  bool isAlarmed() const { return alarm_.alarmed(); }
  void setAlarmed(bool p_alarmed) { if (p_alarmed) alarm_.raise(); else alarm_.clear(); }
  // The analysis thread has sampled isAlarmed(); spend one unit of the hold so a
  // momentary pulse clears once it has been reported for long enough (refs #5301).
  void observeAlarm() { alarm_.observe(); }
  // Hold a momentary alarm for one frame beyond alarm_frame_count observations.
  // Reaching ALARM takes alarm_frame_count scored frames, which is where a
  // continuous event is split on close-on-alarm; holding one frame longer leaves
  // at least one scored frame for the new event rather than all of them in the
  // event that is closing (refs #5301).
  void setMinimumAlarmFrames(int alarm_frame_count) {
    alarm_.set_minimum_hold(alarm_frame_count > 0 ? alarm_frame_count + 1 : 2);
  }
  bool isHealthy() const { return healthy_.load(std::memory_order_acquire); }
  void setHealthy(bool p_healthy) { healthy_.store(p_healthy, std::memory_order_release); }
#ifdef WITH_GSOAP
  void setNotes(Event::StringSet &noteSet) { SetNoteSet(noteSet); }
#else
  void setNotes(Event::StringSet &) {}  // No-op without GSOAP
#endif

 protected:
  Monitor *parent;
  AlarmLatch alarm_;
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
  void assume_renewal_times(const SystemTimePoint &request_time, const SystemTimePoint &requested_termination);
  bool is_renewal_tracking_initialized() const;
  void log_subscription_timing(const char* context);
  bool Renew();
  bool IsRenewalNeeded();
  bool do_wsa_request(const char* address, const char* action);
  void expire_stale_alarms(const SystemTimePoint &now);
#endif  // WITH_GSOAP
};

#endif // ZM_MONITOR_ONVIF_H
