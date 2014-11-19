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
// Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
// 

#ifndef ZM_UTILS_H
#define ZM_UTILS_H

#include <time.h>
#include <sys/time.h>
#include <string>
#include <sstream>
#include <vector>

typedef std::vector<std::string> StringVector;

std::string trimSpaces(std::string str);
std::string trimSet(std::string str, std::string trimset);
std::string replaceAll(std::string str, std::string from, std::string to);

const std::string stringtf( const char *format, ... );
const std::string stringtf( const std::string &format, ... );

bool startsWith( const std::string &haystack, const std::string &needle );
StringVector split( const std::string &string, const std::string chars, int limit=0 );
const std::string join( const StringVector, const char * );

const std::string base64Encode( const std::string &inString );

int split(const char* string, const char delim, std::vector<std::string>& items);
int pairsplit(const char* string, const char delim, std::string& name, std::string& value);

inline int max( int a, int b )
{
    return( a>=b?a:b );
}

inline int min( int a, int b )
{
    return( a<=b?a:b );
}

void ssedetect();
void* sse2_aligned_memcpy(void* dest, const void* src, size_t bytes);
void timespec_diff(struct timespec *start, struct timespec *end, struct timespec *diff);

extern unsigned int sseversion;

#endif // ZM_UTILS_H
