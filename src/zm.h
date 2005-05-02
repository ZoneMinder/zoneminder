//
// ZoneMinder Core Interfaces, $Date$, $Revision$
// Copyright (C) 2003, 2004, 2005  Philip Coombes
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
#include "zm_debug.h"
}

#include "zm_config.h"

extern "C"
{
#if !HAVE_DECL_ROUND
double round(double);
#endif
}

// Structure used for storing the results of the subtraction
// of one struct timeval from another

struct DeltaTimeval
{
	bool positive;
	unsigned long delta;
	unsigned long sec;
	unsigned long fsec;
	unsigned long prec;
};

#define DT_GRAN_1000000	1000000
#define DT_PREC_6		DT_GRAN_1000000
#define DT_GRAN_100000	100000
#define DT_PREC_5		DT_GRAN_100000
#define DT_GRAN_10000	10000
#define DT_PREC_4		DT_GRAN_10000
#define DT_GRAN_1000	1000
#define DT_PREC_3		DT_GRAN_1000
#define DT_GRAN_100		100
#define DT_PREC_2		DT_GRAN_100
#define DT_GRAN_10		10
#define DT_PREC_1		DT_GRAN_10

#define DT_MAXGRAN		DT_GRAN_1000000

// This obviously wouldn't work for massive deltas but as it's mostly
// for frames it will only usually be a fraction of a second or so
#define DELTA_TIMEVAL( result, time1, time2, precision ) \
{ \
	int delta = (((time1).tv_sec-(time2).tv_sec)*(precision))+(((time1).tv_usec-(time2).tv_usec)/(DT_MAXGRAN/(precision))); \
	result.positive = (delta>=0); \
	result.delta = abs(delta); \
	result.sec = result.delta/(precision); \
	result.fsec = result.delta%(precision); \
	result.prec = (precision); \
}

#endif // ZM_H
