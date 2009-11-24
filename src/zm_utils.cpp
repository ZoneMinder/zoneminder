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

//#include "zm_debug.h"
#include "zm_utils.h"

#include <stdio.h>
#include <stdarg.h>

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

const std::string stringtf( const std::string &format, ... )
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

bool startsWith( const std::string &haystack, const std::string &needle )
{
    return( haystack.substr( 0, needle.length() ) == needle );
}

StringVector split( const std::string &string, const std::string chars, int limit )
{
    StringVector stringVector;
    std::string tempString = string;
    std::string::size_type startIndex = 0;
    std::string::size_type endIndex = 0;

    //Info( "Looking for '%s' in '%s', limit %d", chars.c_str(), string.c_str(), limit );
    do
    {
        // Find delimiters
        endIndex = string.find_first_of( chars, startIndex );
        //Info( "Got endIndex at %d", endIndex );
        if ( endIndex > 0 )
        {
            //Info( "Adding '%s'", string.substr( startIndex, endIndex-startIndex ).c_str() );
            stringVector.push_back( string.substr( startIndex, endIndex-startIndex ) );
        }
        if ( endIndex == std::string::npos )
            break;
        // Find non-delimiters
        startIndex = tempString.find_first_not_of( chars, endIndex );
        if ( limit && (stringVector.size() == (limit-1)) )
        {
            stringVector.push_back( string.substr( startIndex ) );
            break;
        }
        //Info( "Got new startIndex at %d", startIndex );
    } while ( startIndex != std::string::npos );
    //Info( "Finished with %d strings", stringVector.size() );

    return( stringVector );
}

const std::string base64Encode( const std::string &inString )
{
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
