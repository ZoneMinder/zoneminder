//
// ZoneMinder Core Interfaces, $Date$, $Revision$
// Copyright (C) 2003  Philip Coombes
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

#ifndef ZM_H
#define ZM_H

extern "C"
{
#include "zm_config.h"
#include "zm_debug.h"
}

// Structure used for storing the results of the subtraction
// of one struct timeval from another

struct DeltaTimeval
{
	bool positive;
	long tv_sec;
	long tv_usec;
};

// This obviously wouldn't work for massive deltas but as it's mostly
// for frames it will only usually be a fraction of a second or so
#define DELTA_TIMEVAL( result, time1, time2 ) \
{ \
	int delta_usec = (((time1).tv_sec-(time2).tv_sec)*1000000)+((time1).tv_usec-(time2).tv_usec); \
	result.positive = (delta_usec>=0); \
	delta_usec = abs(delta_usec); \
	result.tv_sec = delta_usec/1000000; \
	result.tv_usec = delta_usec%1000000; \
}

#endif // ZM_H
