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

#include "zm_utils.h"

#include "zm_config.h"
#include "zm_logger.h"
#include <array>
#include <cstdarg>
#include <cstring>
#include <fcntl.h> /* Definition of AT_* constants */
#include <sstream>
#include <sys/stat.h>

#if defined(__arm__)
#include <sys/auxv.h>
#endif

unsigned int sse_version = 0;
unsigned int neonversion = 0;

// Trim Both leading and trailing sets
std::string Trim(const std::string &str, const std::string &char_set) {
  size_t start_pos = str.find_first_not_of(char_set);
  size_t end_pos = str.find_last_not_of(char_set);

  // if all spaces or empty return an empty string
  if ((start_pos == std::string::npos) || (end_pos == std::string::npos))
    return "";
  return str.substr(start_pos, end_pos - start_pos + 1);
}

std::string ReplaceAll(std::string str, const std::string &old_value, const std::string &new_value) {
  if (old_value.empty())
    return str;
  size_t start_pos = 0;
  while ((start_pos = str.find(old_value, start_pos)) != std::string::npos) {
    str.replace(start_pos, old_value.length(), new_value);
    start_pos += new_value.length(); // In case 'new_value' contains 'old_value', like replacing 'x' with 'yx'
  }
  return str;
}

StringVector Split(const std::string &str, char delim) {
  std::vector<std::string> tokens;

  size_t start = 0;
  for (size_t end = str.find(delim); end != std::string::npos; end = str.find(delim, start)) {
    tokens.push_back(str.substr(start, end - start));
    start = end + 1;
  }

  tokens.push_back(str.substr(start));

  return tokens;
}

StringVector Split(const std::string &str, const std::string &delim, size_t limit) {
  StringVector tokens;
  size_t start = 0;

  do {
    size_t end = str.find_first_of(delim, start);
    if (end > 0) {
      tokens.push_back(str.substr(start, end - start));
    }
    if (end == std::string::npos) {
      break;
    }
    // Find non-delimiters
    start = str.find_first_not_of(delim, end);
    if (limit && (tokens.size() == limit - 1)) {
      tokens.push_back(str.substr(start));
      break;
    }
  } while (start != std::string::npos);

  return tokens;
}

std::pair<std::string, std::string> PairSplit(const std::string &str, char delim) {
  if (str.empty())
    return std::make_pair("", "");

  size_t pos = str.find(delim);

  if (pos == std::string::npos)
    return std::make_pair("", "");

  return std::make_pair(str.substr(0, pos), str.substr(pos + 1, std::string::npos));
}

std::string Join(const StringVector &values, const std::string &delim) {
  std::stringstream ss;

  for (size_t i = 0; i < values.size(); ++i) {
    if (i != 0)
      ss << delim;
    ss << values[i];
  }
  return ss.str();
}

std::string stringtf(const char* format, ...) {
  va_list args;
  va_start(args, format);
  va_list args2;
  va_copy(args2, args);
  int size = vsnprintf(nullptr, 0, format, args);
  va_end(args);

  if (size < 0) {
    va_end(args2);
    throw std::runtime_error("Error during formatting.");
  }
  size += 1; // Extra space for '\0'

  std::unique_ptr<char[]> buf(new char[size]);
  vsnprintf(buf.get(), size, format, args2);
  va_end(args2);

  return std::string(buf.get(), buf.get() + size - 1); // We don't want the '\0' inside
}

std::string ByteArrayToHexString(nonstd::span<const uint8> bytes) {
  static constexpr char lowercase_table[] = "0123456789abcdef";
  std::string buf;
  buf.resize(2 * bytes.size());

  const uint8 *srcPtr = bytes.data();
  char *dstPtr = &buf[0];

  for (size_t i = 0; i < bytes.size(); ++i) {
    uint8 c = *srcPtr++;
    *dstPtr++ = lowercase_table[c >> 4];
    *dstPtr++ = lowercase_table[c & 0x0f];
  }

  return buf;
}

std::string Base64Encode(const std::string &str) {
  static char base64_table[64] = {'\0'};

  if (!base64_table[0]) {
    int i = 0;
    for (char c = 'A'; c <= 'Z'; c++)
      base64_table[i++] = c;
    for (char c = 'a'; c <= 'z'; c++)
      base64_table[i++] = c;
    for (char c = '0'; c <= '9'; c++)
      base64_table[i++] = c;
    base64_table[i++] = '+';
    base64_table[i++] = '/';
  }

  std::string outString;
  outString.reserve(2 * str.size());

  const char *inPtr = str.c_str();
  while (*inPtr) {
    unsigned char selection = *inPtr >> 2;
    unsigned char remainder = (*inPtr++ & 0x03) << 4;
    outString += base64_table[selection];

    if (*inPtr) {
      selection = remainder | (*inPtr >> 4);
      remainder = (*inPtr++ & 0x0f) << 2;
      outString += base64_table[selection];

      if (*inPtr) {
        selection = remainder | (*inPtr >> 6);
        outString += base64_table[selection];
        selection = (*inPtr++ & 0x3f);
        outString += base64_table[selection];
      } else {
        outString += base64_table[remainder];
        outString += '=';
      }
    } else {
      outString += base64_table[remainder];
      outString += '=';
      outString += '=';
    }
  }
  return outString;
}

std::string TimevalToString(timeval tv) {
  tm now = {};
  std::array<char, 26> tm_buf = {};

  localtime_r(&tv.tv_sec, &now);
  size_t tm_buf_len = strftime(tm_buf.data(), tm_buf.size(), "%Y-%m-%d %H:%M:%S", &now);
  if (tm_buf_len == 0) {
    return "";
  }

  return stringtf("%s.%06ld", tm_buf.data(), tv.tv_usec);
}

/* Detect special hardware features, such as SIMD instruction sets */
void HwCapsDetect() {
  neonversion = 0;
  sse_version = 0;
#if (defined(__i386__) || defined(__x86_64__))
  __builtin_cpu_init();

  if (__builtin_cpu_supports("avx2")) {
    sse_version = 52; /* AVX2 */
    Debug(1, "Detected a x86\\x86-64 processor with AVX2");
  } else if (__builtin_cpu_supports("avx")) {
    sse_version = 51; /* AVX */
    Debug(1, "Detected a x86\\x86-64 processor with AVX");
  } else if (__builtin_cpu_supports("sse4.2")) {
    sse_version = 42; /* SSE4.2 */
    Debug(1, "Detected a x86\\x86-64 processor with SSE4.2");
  } else if (__builtin_cpu_supports("sse4.1")) {
    sse_version = 41; /* SSE4.1 */
    Debug(1, "Detected a x86\\x86-64 processor with SSE4.1");
  } else if (__builtin_cpu_supports("ssse3")) {
    sse_version = 35; /* SSSE3 */
    Debug(1, "Detected a x86\\x86-64 processor with SSSE3");
  } else if (__builtin_cpu_supports("sse3")) {
    sse_version = 30; /* SSE3 */
    Debug(1, "Detected a x86\\x86-64 processor with SSE3");
  } else if (__builtin_cpu_supports("sse2")) {
    sse_version = 20; /* SSE2 */
    Debug(1, "Detected a x86\\x86-64 processor with SSE2");
  } else if (__builtin_cpu_supports("sse")) {
    sse_version = 10; /* SSE */
    Debug(1, "Detected a x86\\x86-64 processor with SSE");
  } else {
    sse_version = 0;
    Debug(1, "Detected a x86\\x86-64 processor");
  }
#elif defined(__arm__)
  // ARM processor in 32bit mode
  // To see if it supports NEON, we need to get that information from the kernel
  #ifdef __linux__
  unsigned long auxval = getauxval(AT_HWCAP);
  if (auxval & HWCAP_ARM_NEON) {
  #elif defined(__FreeBSD__)
  unsigned long auxval = 0;
  elf_aux_info(AT_HWCAP, &auxval, sizeof(auxval));
  if (auxval & HWCAP_NEON) {
  #else
  {
  #error Unsupported OS.
  #endif
    Debug(1,"Detected ARM (AArch32) processor with Neon");
    neonversion = 1;
  } else {
    Debug(1,"Detected ARM (AArch32) processor");
  }
#elif defined(__aarch64__)
  // ARM processor in 64bit mode
  // Neon is mandatory, no need to check for it
  neonversion = 1;
  Debug(1,"Detected ARM (AArch64) processor with Neon");
#else
  // Unknown processor
  Debug(1,"Detected unknown processor architecture");
#endif
}

/* SSE2 aligned memory copy. Useful for big copying of aligned memory like image buffers in ZM */
/* For platforms without SSE2 we will use standard x86 asm memcpy or glibc's memcpy() */
#if defined(__i386__) || defined(__x86_64__)
__attribute__((noinline, __target__("sse2")))
#endif
void *sse2_aligned_memcpy(void *dest, const void *src, size_t bytes) {
#if ((defined(__i386__) || defined(__x86_64__) || defined(ZM_KEEP_SSE)) && !defined(ZM_STRIP_SSE))
  if (bytes > 128) {
    unsigned int remainder = bytes % 128;
    const uint8_t *lastsrc = (uint8_t *) src + (bytes - remainder);

    __asm__ __volatile__(
    "sse2_copy_iter:\n\t"
    "movdqa (%0),%%xmm0\n\t"
    "movdqa 0x10(%0),%%xmm1\n\t"
    "movdqa 0x20(%0),%%xmm2\n\t"
    "movdqa 0x30(%0),%%xmm3\n\t"
    "movdqa 0x40(%0),%%xmm4\n\t"
    "movdqa 0x50(%0),%%xmm5\n\t"
    "movdqa 0x60(%0),%%xmm6\n\t"
    "movdqa 0x70(%0),%%xmm7\n\t"
    "movntdq %%xmm0,(%1)\n\t"
    "movntdq %%xmm1,0x10(%1)\n\t"
    "movntdq %%xmm2,0x20(%1)\n\t"
    "movntdq %%xmm3,0x30(%1)\n\t"
    "movntdq %%xmm4,0x40(%1)\n\t"
    "movntdq %%xmm5,0x50(%1)\n\t"
    "movntdq %%xmm6,0x60(%1)\n\t"
    "movntdq %%xmm7,0x70(%1)\n\t"
    "add $0x80, %0\n\t"
    "add $0x80, %1\n\t"
    "cmp %2, %0\n\t"
    "jb sse2_copy_iter\n\t"
    "test %3, %3\n\t"
    "jz sse2_copy_finish\n\t"
    "cld\n\t"
    "rep movsb\n\t"
    "sse2_copy_finish:\n\t"
    :
    : "S" (src), "D" (dest), "r" (lastsrc), "c" (remainder)
    : "%xmm0", "%xmm1", "%xmm2", "%xmm3", "%xmm4", "%xmm5", "%xmm6", "%xmm7", "cc", "memory"
    );

  } else {
    /* Standard memcpy */
    __asm__ __volatile__("cld; rep movsb"::"S"(src), "D"(dest), "c"(bytes) : "cc", "memory");
  }
#else
  /* Non x86\x86-64 platform, use memcpy */
  memcpy(dest,src,bytes);
#endif
  return dest;
}

void touch(const char *pathname) {
  int fd = open(pathname, O_WRONLY | O_CREAT | O_NOCTTY | O_NONBLOCK, 0666);
  if (fd < 0) {
    // Couldn't open that path.
    Error("Couldn't open() path %s in touch", pathname);
    return;
  }
  int rc = utimensat(AT_FDCWD, pathname, nullptr, 0);
  if (rc) {
    Error("Couldn't utimensat() path %s in touch", pathname);
    return;
  }
}

std::string UriDecode(const std::string &encoded) {
  const char *src = encoded.c_str();
  std::string retbuf;
  retbuf.reserve(encoded.length());
  while (*src) {
    char a, b;
    if ((*src == '%') && ((a = src[1]) && (b = src[2])) && (isxdigit(a) && isxdigit(b))) {
      if (a >= 'a')
        a -= 'a' - 'A';
      if (a >= 'A')
        a -= ('A' - 10);
      else
        a -= '0';
      if (b >= 'a')
        b -= 'a' - 'A';
      if (b >= 'A')
        b -= ('A' - 10);
      else
        b -= '0';
      retbuf.push_back(16 * a + b);
      src += 3;
    } else if (*src == '+') {
      retbuf.push_back(' ');
      src++;
    } else {
      retbuf.push_back(*src++);
    }
  }
  return retbuf;
}

QueryString::QueryString(std::istream &input) {
  while (!input.eof() && input.peek() > 0) {
    //Should eat "param1="
    auto name = parseName(input);
    //Should eat value1&
    std::string value = parseValue(input);

    auto foundItr = parameters_.find(name);
    if (foundItr == parameters_.end()) {
      std::unique_ptr<QueryParameter> newParam = zm::make_unique<QueryParameter>(name);
      if (!value.empty()) {
        newParam->addValue(value);
      }
      parameters_.emplace(name, std::move(newParam));
    } else {
      foundItr->second->addValue(value);
    }
  }
}

std::vector<std::string> QueryString::names() const {
  std::vector<std::string> names;
  for (auto const &pair : parameters_)
    names.push_back(pair.second->name());

  return names;
}

const QueryParameter *QueryString::get(const std::string &name) const {
  auto itr = parameters_.find(name);
  return itr == parameters_.end() ? nullptr : itr->second.get();
}

std::string QueryString::parseName(std::istream &input) {
  std::string name;

  while (!input.eof() && input.peek() != '=') {
    name.push_back(input.get());
  }

  //Eat the '='
  if (!input.eof()) {
    input.get();
  }

  return name;
}

std::string QueryString::parseValue(std::istream &input) {
  std::string url_encoded_value;

  int c = input.get();
  while (c > 0 && c != '&') {
    url_encoded_value.push_back(c);
    c = input.get();
  }

  if (url_encoded_value.empty()) {
    return "";
  }

  return UriDecode(url_encoded_value);
}
