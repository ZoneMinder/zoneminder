/*
 * This file is part of the ZoneMinder Project. See AUTHORS file for contributors.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

#ifndef ZM_LOG_THROTTLE_H
#define ZM_LOG_THROTTLE_H

#include "zm_define.h"
#include "zm_time.h"

// Rate limit for a message that can arrive on every packet. Keeps the message
// rather than dropping its level, emitting at most one per interval and
// counting the ones it swallowed so the next one through can say how many there
// were.
//
// The caller passes the time in, as a TimePoint, which is steady_clock. That is
// deliberate and load bearing: measuring an interval off the wall clock lets an
// NTP step or a manual correction decide whether a message is emitted, muting
// it until wall time catches up after a backward step and letting an extra
// through early after a forward one. SystemTimePoint is a distinct type, so
// handing this a wall clock reading does not compile. Taking the time as an
// argument also makes the decision testable without waiting on a real clock.
// refs #4242
class LogThrottle {
 public:
  explicit LogThrottle(Microseconds interval = Seconds(1)) : interval_(interval) {}

  // True when the caller should emit the message, setting suppressed to the
  // number folded in since the last emission. On false the occurrence is
  // counted and suppressed is left alone.
  bool Admit(TimePoint now, uint64 &suppressed) {
    if (have_last_ and ((now - last_) < interval_)) {
      suppressed_++;
      return false;
    }
    suppressed = suppressed_;
    suppressed_ = 0;
    last_ = now;
    have_last_ = true;
    return true;
  }

  uint64 suppressed() const { return suppressed_; }

 private:
  Microseconds interval_;
  TimePoint last_ = {};
  bool have_last_ = false;
  uint64 suppressed_ = 0;
};

#endif  // ZM_LOG_THROTTLE_H
