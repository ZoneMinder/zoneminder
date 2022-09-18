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

std::string SystemTimePointToString(SystemTimePoint tp) {
  time_t tp_sec = std::chrono::system_clock::to_time_t(tp);
  Microseconds now_frac = std::chrono::duration_cast<Microseconds>(
      tp.time_since_epoch() - std::chrono::duration_cast<Seconds>(tp.time_since_epoch()));

  std::string timeString;
  timeString.reserve(64);
  char *timePtr = &*(timeString.begin());
  tm tp_tm = {};
  timePtr += strftime(timePtr, timeString.capacity(), "%x %H:%M:%S", localtime_r(&tp_sec, &tp_tm));
  snprintf(timePtr,
           timeString.capacity() - (timePtr - timeString.data()),
           ".%06" PRIi64,
           static_cast<int64_t>(now_frac.count()));
  return timeString;
}

std::string TimePointToString(TimePoint tp) {
  const auto tp_dur = std::chrono::duration_cast<std::chrono::system_clock::duration>(
      tp - std::chrono::steady_clock::now());
  time_t tp_sec = std::chrono::system_clock::to_time_t(std::chrono::system_clock::now() + tp_dur);

  Microseconds now_frac = std::chrono::duration_cast<Microseconds>(
      tp.time_since_epoch() - std::chrono::duration_cast<Seconds>(tp.time_since_epoch()));

  std::string timeString;
  timeString.reserve(64);
  char *timePtr = &*(timeString.begin());
  tm tp_tm = {};
  timePtr += strftime(timePtr, timeString.capacity(), "%x %H:%M:%S", localtime_r(&tp_sec, &tp_tm));
  snprintf(timePtr,
           timeString.capacity() - (timePtr - timeString.data()),
           ".%06" PRIi64,
           static_cast<int64_t>(now_frac.count()));
  return timeString;
}
