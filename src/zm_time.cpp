//
// ZoneMinder Time Functions & Definitions, $Date$, $Revision$
// Copyright (C) 2001-2008 Philip Coombes
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

#include "zm_time.h"

#include <cinttypes>
#include <ctime>

std::string SystemTimePointToString(SystemTimePoint tp) {
  time_t tp_sec = std::chrono::system_clock::to_time_t(tp);
  Microseconds now_frac = std::chrono::duration_cast<Microseconds>(
                            tp.time_since_epoch() - std::chrono::duration_cast<Seconds>(tp.time_since_epoch()));

  std::string timeString;
  timeString.reserve(64);
  char *timePtr = &*(timeString.begin());
  tm tp_tm = {};
  timePtr += strftime(timePtr, timeString.capacity(), "%x %H:%M:%S", localtime_r(&tp_sec, &tp_tm));
  snprintf(timePtr, timeString.capacity() - (timePtr - timeString.data()), ".%06" PRIi64, static_cast<int64_t>(now_frac.count()));
  return timeString;
}

std::string TimePointToString(TimePoint tp) {
  const auto tp_dur = std::chrono::duration_cast<std::chrono::system_clock::duration>(tp - std::chrono::steady_clock::now());
  time_t tp_sec = std::chrono::system_clock::to_time_t(
                    std::chrono::system_clock::now() + tp_dur);

  Microseconds now_frac = std::chrono::duration_cast<Microseconds>(
                            tp.time_since_epoch() - std::chrono::duration_cast<Seconds>(tp.time_since_epoch()));

  std::string timeString;
  timeString.reserve(64);
  char *timePtr = &*(timeString.begin());
  tm tp_tm = {};
  timePtr += strftime(timePtr, timeString.capacity(), "%x %H:%M:%S", localtime_r(&tp_sec, &tp_tm));
  snprintf(timePtr, timeString.capacity() - (timePtr - timeString.data()), ".%06" PRIi64, static_cast<int64_t>(now_frac.count()));
  return timeString;
}

SystemTimePoint StringToSystemTimePoint(const std::string &timestamp) {
  std::tm t{};
  strptime(timestamp.c_str(), "%Y-%m-%d %H:%M:%S", &t);
  time_t time_t_val = mktime(&t);
  SystemTimePoint stp = std::chrono::system_clock::from_time_t(time_t_val);
  return stp;
}

// Parse ISO 8601 duration string to seconds
// Supports formats like "PT20S", "PT1M", "PT1H30M45S"
// Returns -1 on parse error
int ParseISO8601Duration(const std::string& duration) {
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

// Format time_t to human-readable string "YYYY-MM-DD HH:MM:SS"
std::string FormatTimestamp(time_t t) {
  char buf[64];
  struct tm tm_val;
  localtime_r(&t, &tm_val);
  strftime(buf, sizeof(buf), "%Y-%m-%d %H:%M:%S", &tm_val);
  return std::string(buf);
}

// Format SystemTimePoint to human-readable string "YYYY-MM-DD HH:MM:SS"
std::string FormatTimestamp(SystemTimePoint tp) {
  time_t t = std::chrono::system_clock::to_time_t(tp);
  return FormatTimestamp(t);
}

// Format seconds to human-readable duration string like "1h 30m 45s" or "45s"
std::string FormatDuration(int64_t seconds) {
  if (seconds < 0) {
    return "invalid";
  }
  
  int64_t hours = seconds / 3600;
  int64_t minutes = (seconds % 3600) / 60;
  int64_t secs = seconds % 60;
  
  std::string result;
  if (hours > 0) {
    result += std::to_string(hours) + "h ";
  }
  if (minutes > 0) {
    result += std::to_string(minutes) + "m ";
  }
  if (secs > 0 || result.empty()) {
    result += std::to_string(secs) + "s";
  }
  
  // Trim trailing space
  if (!result.empty() && result.back() == ' ') {
    result.pop_back();
  }
  
  return result;
}
