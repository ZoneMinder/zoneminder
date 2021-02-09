//
// ZoneMinder General Utility Functions, $Date$, $Revision$
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

#ifndef ZM_UTILS_H
#define ZM_UTILS_H

#include <chrono>
#include <ctime>
#include <memory>
#include <sys/time.h>
#include <string>
#include <vector>

typedef std::vector<std::string> StringVector;

std::string trimSpaces(const std::string &str);
std::string trimSet(std::string str, std::string trimset);
std::string replaceAll(std::string str, std::string from, std::string to);

const std::string stringtf( const char *format, ... );
const std::string stringtf( const std::string &format, ... );

bool startsWith( const std::string &haystack, const std::string &needle );
StringVector split( const std::string &string, const std::string &chars, int limit=0 );
const std::string join( const StringVector &, const char * );

const std::string base64Encode( const std::string &inString );
void string_toupper(std::string& str);

int split(const char* string, const char delim, std::vector<std::string>& items);
int pairsplit(const char* string, const char delim, std::string& name, std::string& value);

void* sse2_aligned_memcpy(void* dest, const void* src, size_t bytes);
void timespec_diff(struct timespec *start, struct timespec *end, struct timespec *diff);

void hwcaps_detect();
extern unsigned int sse_version;
extern unsigned int neonversion;

char *timeval_to_string( struct timeval tv );
std::string UriDecode( const std::string &encoded );
void touch( const char *pathname );

namespace ZM {
//! std::make_unique implementation (TODO: remove this once C++14 is supported)
template<typename T, typename ...Args>
inline auto make_unique(Args &&...args) ->
typename std::enable_if<!std::is_array<T>::value, std::unique_ptr<T>>::type {
  return std::unique_ptr<T>(new T(std::forward<Args>(args)...));
}

template<typename T>
inline auto make_unique(std::size_t size) ->
typename std::enable_if<std::is_array<T>::value && std::extent<T>::value == 0, std::unique_ptr<T>>::type {
  return std::unique_ptr<T>(new typename std::remove_extent<T>::type[size]());
}

template<typename T, typename... Args>
inline auto make_unique(Args &&...) ->
typename std::enable_if<std::extent<T>::value != 0, void>::type = delete;
}

typedef std::chrono::microseconds Microseconds;
typedef std::chrono::milliseconds Milliseconds;
typedef std::chrono::seconds Seconds;
typedef std::chrono::minutes Minutes;
typedef std::chrono::hours Hours;

typedef std::chrono::steady_clock::time_point TimePoint;
typedef std::chrono::system_clock::time_point SystemTimePoint;

#endif // ZM_UTILS_H
