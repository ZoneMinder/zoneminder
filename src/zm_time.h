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

#ifndef ZM_TIME_H
#define ZM_TIME_H

#include <chrono>
#include <sys/time.h>

typedef std::chrono::microseconds Microseconds;
typedef std::chrono::milliseconds Milliseconds;
typedef std::chrono::seconds Seconds;
typedef std::chrono::minutes Minutes;
typedef std::chrono::hours Hours;

// floating point seconds
typedef std::chrono::duration<double> FPSeconds;

typedef std::chrono::steady_clock::time_point TimePoint;
typedef std::chrono::system_clock::time_point SystemTimePoint;

namespace zm {
namespace chrono {
namespace impl {

template<typename From, typename To>
struct posix_duration_cast;

// chrono -> timeval caster
template<typename Rep, typename Period>
struct posix_duration_cast<std::chrono::duration<Rep, Period>, timeval> {
  static timeval cast(std::chrono::duration<Rep, Period> const &d) {
    timeval tv = {};

    Seconds const sec = std::chrono::duration_cast<Seconds>(d);

    tv.tv_sec = sec.count();
    tv.tv_usec = std::chrono::duration_cast<Microseconds>(d - sec).count();

    return tv;
  }
};

// timeval -> chrono caster
template<typename Rep, typename Period>
struct posix_duration_cast<timeval, std::chrono::duration<Rep, Period>> {
  static std::chrono::duration<Rep, Period> cast(timeval const &tv) {
    return std::chrono::duration_cast<std::chrono::duration<Rep, Period>>(
        Seconds(tv.tv_sec) + Microseconds(tv.tv_usec)
    );
  }
};
}

// chrono -> timeval
template<typename T, typename Rep, typename Period>
auto duration_cast(std::chrono::duration<Rep, Period> const &d)
-> typename std::enable_if<std::is_same<T, timeval>::value, timeval>::type {
  return impl::posix_duration_cast<std::chrono::duration<Rep, Period>, timeval>::cast(d);
}

// timeval -> chrono
template<typename Duration>
Duration duration_cast(timeval const &tv) {
  return impl::posix_duration_cast<timeval, Duration>::cast(tv);
}
}
}

#endif // ZM_TIME_H
