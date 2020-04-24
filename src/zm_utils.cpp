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

//#include "zm_logger.h"
#include "zm.h"
#include "zm_utils.h"

#include <string.h>
#include <algorithm>
#include <stdio.h>
#include <stdarg.h>
#include <fcntl.h> /* Definition of AT_* constants */
#include <sys/stat.h>
#if defined(__arm__)
#include <sys/auxv.h>
#endif

#ifdef HAVE_CURL_CURL_H
#include <curl/curl.h>
#endif

unsigned int sseversion = 0;
unsigned int neonversion = 0;

std::string trimSet(std::string str, std::string trimset) {
  // Trim Both leading and trailing sets
  size_t startpos = str.find_first_not_of(trimset); // Find the first character position after excluding leading blank spaces
  size_t endpos = str.find_last_not_of(trimset); // Find the first character position from reverse af
 
  // if all spaces or empty return an empty string
  if(( std::string::npos == startpos ) || ( std::string::npos == endpos))
  {
    return std::string("");
  }
  else
    return str.substr( startpos, endpos-startpos+1 );
}

std::string trimSpaces(const std::string &str) {
  return trimSet(str, " \t");
}

std::string replaceAll(std::string str, std::string from, std::string to) {
  if(from.empty())
    return str;
  size_t start_pos = 0;
  while((start_pos = str.find(from, start_pos)) != std::string::npos) {
    str.replace(start_pos, from.length(), to);
    start_pos += to.length(); // In case 'to' contains 'from', like replacing 'x' with 'yx'
  }
  return str;
}

const std::string stringtf( const char *format, ... )
{
  va_list ap;
  char tempBuffer[8192];
  std::string tempString;

  va_start(ap, format );
  vsnprintf( tempBuffer, sizeof(tempBuffer), format , ap );
  va_end(ap);

  tempString = tempBuffer;

  return( tempString );
}

const std::string stringtf( const std::string format, ... )
{
  va_list ap;
  char tempBuffer[8192];
  std::string tempString;

  va_start(ap, format );
  vsnprintf( tempBuffer, sizeof(tempBuffer), format.c_str() , ap );
  va_end(ap);

  tempString = tempBuffer;

  return( tempString );
}

bool startsWith(const std::string &haystack, const std::string &needle) {
  return( haystack.substr(0, needle.length()) == needle );
}

StringVector split(const std::string &string, const std::string &chars, int limit) {
  StringVector stringVector;
  std::string tempString = string;
  std::string::size_type startIndex = 0;
  std::string::size_type endIndex = 0;

  //Info( "Looking for '%s' in '%s', limit %d", chars.c_str(), string.c_str(), limit );
  do {
    // Find delimiters
    endIndex = string.find_first_of( chars, startIndex );
    //Info( "Got endIndex at %d", endIndex );
    if ( endIndex > 0 ) {
      //Info( "Adding '%s'", string.substr( startIndex, endIndex-startIndex ).c_str() );
      stringVector.push_back( string.substr( startIndex, endIndex-startIndex ) );
    }
    if ( endIndex == std::string::npos )
      break;
    // Find non-delimiters
    startIndex = tempString.find_first_not_of( chars, endIndex );
    if ( limit && (stringVector.size() == (unsigned int)(limit-1)) ) {
      stringVector.push_back( string.substr( startIndex ) );
      break;
    }
    //Info( "Got new startIndex at %d", startIndex );
  } while ( startIndex != std::string::npos );
  //Info( "Finished with %d strings", stringVector.size() );

  return stringVector;
}

const std::string join(const StringVector &v, const char * delim=",") {
  std::stringstream ss;

  for (size_t i = 0; i < v.size(); ++i) {
    if ( i != 0 )
      ss << delim;
    ss << v[i];
  }
  return ss.str();
}

const std::string base64Encode(const std::string &inString) {
  static char base64_table[64] = { '\0' };

  if ( !base64_table[0] )
  {
    int i = 0;
    for ( char c = 'A'; c <= 'Z'; c++ )
      base64_table[i++] = c;
    for ( char c = 'a'; c <= 'z'; c++ )
      base64_table[i++] = c;
    for ( char c = '0'; c <= '9'; c++ )
      base64_table[i++] = c;
    base64_table[i++] = '+';
    base64_table[i++] = '/';
  }

  std::string outString;
  outString.reserve( 2 * inString.size() );

  const char *inPtr = inString.c_str();
  while( *inPtr )
  {
    unsigned char selection = *inPtr >> 2;
    unsigned char remainder = (*inPtr++ & 0x03) << 4;
    outString += base64_table[selection];

    if ( *inPtr )
    {
      selection = remainder | (*inPtr >> 4);
      remainder = (*inPtr++ & 0x0f) << 2;
      outString += base64_table[selection];
    
      if ( *inPtr )
      {
        selection = remainder | (*inPtr >> 6);
        outString += base64_table[selection];
        selection = (*inPtr++ & 0x3f);
        outString += base64_table[selection];
      }
      else
      {
        outString += base64_table[remainder];
        outString += '=';
      }
    }
    else
    {
      outString += base64_table[remainder];
      outString += '=';
      outString += '=';
    }
  }
  return( outString );
}

int split(const char* string, const char delim, std::vector<std::string>& items) {
  if(string == NULL)
    return -1;

  if(string[0] == 0)
    return -2;

  std::string str(string);
  
  while(true) {
    size_t pos = str.find(delim);
    items.push_back(str.substr(0, pos));
    str.erase(0, pos+1);

    if(pos == std::string::npos)
      break;
  }

  return items.size();
}

int pairsplit(const char* string, const char delim, std::string& name, std::string& value) {
  if(string == NULL)
    return -1;

  if(string[0] == 0)
    return -2;

  std::string str(string);
  size_t pos = str.find(delim);

  if(pos == std::string::npos || pos == 0 || pos >= str.length())
    return -3;

  name = str.substr(0, pos);
  value = str.substr(pos+1, std::string::npos);

  return 0;
}

/* Detect special hardware features, such as SIMD instruction sets */
void hwcaps_detect() {
  neonversion = 0;
  sseversion = 0;
#if (defined(__i386__) || defined(__x86_64__))
  /* x86 or x86-64 processor */
  uint32_t r_edx, r_ecx, r_ebx;

#ifdef __x86_64__
  __asm__ __volatile__(
  "push %%rbx\n\t"
  "mov $0x0,%%ecx\n\t"
  "mov $0x7,%%eax\n\t"
  "cpuid\n\t"
  "push %%rbx\n\t"
  "mov $0x1,%%eax\n\t"
  "cpuid\n\t"
  "pop %%rax\n\t"
  "pop %%rbx\n\t"
  : "=d" (r_edx), "=c" (r_ecx), "=a" (r_ebx)
  :
  :
  );
#else
  __asm__ __volatile__(
  "push %%ebx\n\t"
  "mov $0x0,%%ecx\n\t"
  "mov $0x7,%%eax\n\t"
  "cpuid\n\t"
  "push %%ebx\n\t"
  "mov $0x1,%%eax\n\t"
  "cpuid\n\t"
  "pop %%eax\n\t"
  "pop %%ebx\n\t"
  : "=d" (r_edx), "=c" (r_ecx), "=a" (r_ebx)
  :
  :
  );
#endif

  if (r_ebx & 0x00000020) {
    sseversion = 52; /* AVX2 */
    Debug(1,"Detected a x86\\x86-64 processor with AVX2");
  } else if (r_ecx & 0x10000000) {
    sseversion = 51; /* AVX */
    Debug(1,"Detected a x86\\x86-64 processor with AVX");
  } else if (r_ecx & 0x00100000) {
    sseversion = 42; /* SSE4.2 */
    Debug(1,"Detected a x86\\x86-64 processor with SSE4.2");
  } else if (r_ecx & 0x00080000) {
    sseversion = 41; /* SSE4.1 */
    Debug(1,"Detected a x86\\x86-64 processor with SSE4.1");
  } else if (r_ecx & 0x00000200) {
    sseversion = 35; /* SSSE3 */
    Debug(1,"Detected a x86\\x86-64 processor with SSSE3");
  } else if (r_ecx & 0x00000001) {
    sseversion = 30; /* SSE3 */
    Debug(1,"Detected a x86\\x86-64 processor with SSE3");
  } else if (r_edx & 0x04000000) {
    sseversion = 20; /* SSE2 */
    Debug(1,"Detected a x86\\x86-64 processor with SSE2");
  } else if (r_edx & 0x02000000) {
    sseversion = 10; /* SSE */
    Debug(1,"Detected a x86\\x86-64 processor with SSE");
  } else {
    sseversion = 0;
    Debug(1,"Detected a x86\\x86-64 processor");
  } 
#elif defined(__arm__)
  // ARM processor in 32bit mode
  // To see if it supports NEON, we need to get that information from the kernel
  unsigned long auxval = getauxval(AT_HWCAP);
  if (auxval & HWCAP_ARM_NEON) {
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
__attribute__((noinline,__target__("sse2")))
#endif
void* sse2_aligned_memcpy(void* dest, const void* src, size_t bytes) {
#if ((defined(__i386__) || defined(__x86_64__) || defined(ZM_KEEP_SSE)) && !defined(ZM_STRIP_SSE))
  if(bytes > 128) {
    unsigned int remainder = bytes % 128;
    const uint8_t* lastsrc = (uint8_t*)src + (bytes - remainder);

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
    __asm__ __volatile__("cld; rep movsb" :: "S"(src), "D"(dest), "c"(bytes) : "cc", "memory");
  }
#else
  /* Non x86\x86-64 platform, use memcpy */
  memcpy(dest,src,bytes);
#endif
  return dest;
}

void timespec_diff(struct timespec *start, struct timespec *end, struct timespec *diff) {
  if (((end->tv_nsec)-(start->tv_nsec))<0) {
    diff->tv_sec = end->tv_sec-start->tv_sec-1;
    diff->tv_nsec = 1000000000+end->tv_nsec-start->tv_nsec;
  } else {
    diff->tv_sec = end->tv_sec-start->tv_sec;
    diff->tv_nsec = end->tv_nsec-start->tv_nsec;
  }
}

char *timeval_to_string( struct timeval tv ) {
  time_t nowtime;
  struct tm *nowtm;
  static char tmbuf[20], buf[28];

  nowtime = tv.tv_sec;
  nowtm = localtime(&nowtime);
  strftime(tmbuf, sizeof tmbuf, "%Y-%m-%d %H:%M:%S", nowtm);
  snprintf(buf, sizeof buf-1, "%s.%06ld", tmbuf, tv.tv_usec);
  return buf;
}

std::string UriDecode( const std::string &encoded ) {
  char a, b;
  const char *src = encoded.c_str();
  std::string retbuf;
  retbuf.resize(encoded.length() + 1);
  char *dst = &retbuf[0];
  while (*src) {
    if ((*src == '%') && ((a = src[1]) && (b = src[2])) && (isxdigit(a) && isxdigit(b))) {
      if (a >= 'a')
        a -= 'a'-'A';
      if (a >= 'A')
        a -= ('A' - 10);
      else
        a -= '0';
      if (b >= 'a')
        b -= 'a'-'A';
      if (b >= 'A')
        b -= ('A' - 10);
      else
        b -= '0';
      *dst++ = 16*a+b;
      src+=3;
    } else if (*src == '+') {
      *dst++ = ' ';
      src++;
    } else {
      *dst++ = *src++;
    }
  }
  *dst++ = '\0';
  return retbuf;
}

void string_toupper( std::string& str) {
  std::transform(str.begin(), str.end(), str.begin(), ::toupper);
}

void touch(const char *pathname) {
  int fd = open(pathname,
      O_WRONLY|O_CREAT|O_NOCTTY|O_NONBLOCK,
      0666);
  if ( fd < 0 ) {
    // Couldn't open that path.
    Error("Couldn't open() path %s in touch", pathname);
    return;
  }
  int rc = utimensat(AT_FDCWD,
      pathname,
      nullptr,
      0);
  if ( rc ) {
    Error("Couldn't utimensat() path %s in touch", pathname);
    return;
  }
}

