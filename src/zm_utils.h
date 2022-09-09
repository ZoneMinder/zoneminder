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

#include "zm_define.h"
#include <algorithm>
#include <chrono>
#include <ctime>
#include <functional>
#include <map>
#include <memory>
#include "span.hpp"
#include <stdexcept>
#include <string>
#include <sys/time.h>
#include <vector>


#ifdef NDEBUG
#define ASSERT(x) do { (void) sizeof(x); } while (0)
#else
#include <cassert>
#define ASSERT(x) assert(x)
#endif

typedef std::vector<std::string> StringVector;

std::string Trim(const std::string &str, const std::string &char_set);
inline std::string TrimSpaces(const std::string &str) { return Trim(str, " \t"); }
std::string ReplaceAll(std::string str, const std::string &old_value, const std::string &new_value);
inline std::string StringToUpper(std::string str) {
  std::transform(str.begin(), str.end(), str.begin(), ::toupper);
  return str;
}
inline std::string StringToLower(std::string str) {
  std::transform(str.begin(), str.end(), str.begin(), ::tolower);
  return str;
}

StringVector Split(const std::string &str, char delim);
StringVector Split(const std::string &str, const std::string &delim, size_t limit = 0);
std::pair<std::string, std::string> PairSplit(const std::string &str, char delim);

std::string Join(const StringVector &values, const std::string &delim = ",");

inline bool StartsWith(const std::string &haystack, const std::string &needle) {
  return (haystack.substr(0, needle.length()) == needle);
}

__attribute__((format(printf, 1, 2)))
std::string stringtf(const char* format, ...);

void frexp10(double arg, int& exp, double& mantissa );

std::string ByteArrayToHexString(nonstd::span<const uint8> bytes);

std::string Base64Encode(const std::string &str);

std::string TimevalToString(timeval tv);

extern unsigned int sse_version;
extern unsigned int neonversion;
void HwCapsDetect();
void *sse2_aligned_memcpy(void *dest, const void *src, size_t bytes);

void touch(const char *pathname);

namespace zm {
// C++14 std::make_unique (TODO: remove this once C++14 is supported)
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

// C++17 std::clamp (TODO: remove this once C++17 is supported)
template<class T, class Compare>
constexpr const T &clamp(const T &v, const T &lo, const T &hi, Compare comp) {
  return comp(v, lo) ? lo : comp(hi, v) ? hi : v;
}
template<class T>
constexpr const T &clamp(const T &v, const T &lo, const T &hi) {
  return zm::clamp(v, lo, hi, std::less<T>{});
}

// C++17 std::data (TODO: remove this once C++17 is supported)
template<typename C>
constexpr auto data(C &c) -> decltype(c.data()) { return c.data(); }

template<typename C>
constexpr auto data(C const &c) -> decltype(c.data()) { return c.data(); }

template<typename T, std::size_t N>
constexpr T *data(T(&a)[N]) noexcept { return a; }

template<typename T, std::size_t N>
constexpr T const *data(const T(&a)[N]) noexcept { return a; }

template<typename T>
constexpr T const *data(std::initializer_list<T> l) noexcept { return l.begin(); }

// C++17 std::size (TODO: remove this once C++17 is supported)
template<typename C>
constexpr auto size(const C &c) -> decltype(c.size()) { return c.size(); }

template<typename T, std::size_t N>
constexpr std::size_t size(const T(&)[N]) noexcept { return N; }
}

std::string mask_authentication(const std::string &url);
std::string remove_authentication(const std::string &url);

std::string UriEncode(const std::string &value);
std::string UriDecode(const std::string &encoded);

class QueryParameter {
 public:
  explicit QueryParameter(std::string name) : name_(std::move(name)) {}

  const std::string &name() const { return name_; }
  const std::string &firstValue() const { return values_[0]; }

  const std::vector<std::string> &values() const { return values_; }
  size_t size() const { return values_.size(); }

  template<class T>
  void addValue(T &&value) { values_.emplace_back(std::forward<T>(value)); }
 private:
  std::string name_;
  std::vector<std::string> values_;
};

class QueryString {
 public:
  explicit QueryString(std::istream &input);

  size_t size() const { return parameters_.size(); }
  bool has(const char *name) const { return parameters_.find(std::string(name)) != parameters_.end(); }

  std::vector<std::string> names() const;

  const QueryParameter *get(const std::string &name) const;
  const QueryParameter *get(const char *name) const { return get(std::string(name)); };

 private:
  static std::string parseName(std::istream &input);
  static std::string parseValue(std::istream &input);

  std::map<std::string, std::unique_ptr<QueryParameter>> parameters_;
};

namespace utils {
/**
 * Finds the first element in the range [first, last) for
 * which the predicate returns true.
 *
 * @param first Beginning of the range
 * @param last  End of the range
 * @param p     Unary predicate which returns true for the required element
 *
 * @return Iterator to the first element satisfying the condition or
 *         last if no such element is found.
 */
template< typename InputIt, typename UnaryPredicate >
[[ nodiscard ]] constexpr InputIt find_if( InputIt first, InputIt last, UnaryPredicate p ) noexcept
{
    for ( ; first != last; ++first )
    {
        if ( p( *first ) ) { return first; }
    }
    return last;
}
};
#endif // ZM_UTILS_H
