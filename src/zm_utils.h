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

#include <string>
#include <vector>

typedef std::vector<std::string> StringVector;

const std::string stringtf( const char *format, ... );
const std::string stringtf( const std::string &format, ... );

bool startsWith( const std::string &haystack, const std::string &needle );
StringVector split( const std::string &string, const std::string chars, int limit=0 );

const std::string base64Encode( const std::string &inString );

inline int max( int a, int b )
{
    return( a>=b?a:b );
}

inline int min( int a, int b )
{
    return( a<=b?a:b );
}

#endif // ZM_UTILS_H
