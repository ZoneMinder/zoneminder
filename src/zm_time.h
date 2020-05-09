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

#include "zm.h"

#include <sys/time.h>

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

#define DT_GRAN_1000000  1000000
#define DT_PREC_6    DT_GRAN_1000000
#define DT_GRAN_100000  100000
#define DT_PREC_5    DT_GRAN_100000
#define DT_GRAN_10000  10000
#define DT_PREC_4    DT_GRAN_10000
#define DT_GRAN_1000  1000
#define DT_PREC_3    DT_GRAN_1000
#define DT_GRAN_100    100
#define DT_PREC_2    DT_GRAN_100
#define DT_GRAN_10    10
#define DT_PREC_1    DT_GRAN_10

#define DT_MAXGRAN    DT_GRAN_1000000

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

#define TIMEVAL_INTERVAL( result, time1, time2, precision ) \
{ \
  int delta = (((time1).tv_sec-(time2).tv_sec)*(precision))+(((time1).tv_usec-(time2).tv_usec)/(DT_MAXGRAN/(precision))); \
  result.positive = (delta>=0); \
  result.delta = abs(delta); \
  result.sec = result.delta/(precision); \
  result.fsec = result.delta%(precision); \
  result.prec = (precision); \
}

#define USEC_PER_SEC 1000000
#define MSEC_PER_SEC 1000

extern struct timeval tv;

inline int tvDiffUsec( struct timeval first, struct timeval last )
{
  return( (last.tv_sec - first.tv_sec) * USEC_PER_SEC) + ((USEC_PER_SEC + last.tv_usec - first.tv_usec) - USEC_PER_SEC );
}

inline int tvDiffUsec( struct timeval first )
{
  struct timeval now;
  gettimeofday( &now, NULL );
  return( tvDiffUsec( first, now ) );
}

inline int tvDiffMsec( struct timeval first, struct timeval last )
{
  return( (last.tv_sec - first.tv_sec) * MSEC_PER_SEC) + (((MSEC_PER_SEC + last.tv_usec - first.tv_usec) / MSEC_PER_SEC) - MSEC_PER_SEC );
}

inline int tvDiffMsec( struct timeval first )
{
  struct timeval now;
  gettimeofday( &now, NULL );
  return( tvDiffMsec( first, now ) );
}

inline double tvDiffSec( struct timeval first, struct timeval last )
{
  return( double(last.tv_sec - first.tv_sec) + double(((USEC_PER_SEC + last.tv_usec - first.tv_usec) - USEC_PER_SEC) / (1.0*USEC_PER_SEC) ) );
}

inline double tvDiffSec( struct timeval first )
{
  struct timeval now;
  gettimeofday( &now, NULL );
  return( tvDiffSec( first, now ) );
}

inline struct timeval tvZero()
{
  struct timeval t = { 0, 0 };
  return( t );
}

inline int tvIsZero( const struct timeval t )
{
  return( t.tv_sec == 0 && t.tv_usec == 0 );
}

inline int tvCmp( struct timeval t1, struct timeval t2 )
{
  if ( t1.tv_sec < t2.tv_sec )
    return( -1 );
  if ( t1.tv_sec > t2.tv_sec )
    return( 1 );
  if ( t1.tv_usec < t2.tv_usec )
    return( -1 );
  if ( t1.tv_usec > t2.tv_usec )
    return( 1 );
  return( 0 );
}

inline int tvEq( struct timeval t1, struct timeval t2 )
{
  return( t1.tv_sec == t2.tv_sec && t1.tv_usec == t2.tv_usec );
}

inline struct timeval tvNow( void )
{
  struct timeval t;
  gettimeofday( &t, NULL );
  return( t );
}

inline struct timeval tvCheck( struct timeval &t )
{
  if ( t.tv_usec >= USEC_PER_SEC )
  {
    Warning( "Timestamp too large %ld.%ld\n", t.tv_sec, (long int) t.tv_usec );
    t.tv_sec += t.tv_usec / USEC_PER_SEC;
    t.tv_usec %= USEC_PER_SEC;
  }
  else if ( t.tv_usec < 0 )
  {
    Warning( "Got negative timestamp %ld.%ld\n", t.tv_sec, (long int)t.tv_usec );
    t.tv_usec = 0;
  }
  return( t );
}

// Add t2 to t1
inline struct timeval tvAdd( struct timeval t1, struct timeval t2 )
{
  tvCheck(t1);
  tvCheck(t2);
  t1.tv_sec += t2.tv_sec;
  t1.tv_usec += t2.tv_usec;
  if ( t1.tv_usec >= USEC_PER_SEC )
  {
    t1.tv_sec++;
    t1.tv_usec -= USEC_PER_SEC;
  }
  return( t1 );
}

// Subtract t2 from t1
inline struct timeval tvSub( struct timeval t1, struct timeval t2 )
{
  tvCheck(t1);
  tvCheck(t2);
  t1.tv_sec -= t2.tv_sec;
  t1.tv_usec -= t2.tv_usec;
  if ( t1.tv_usec < 0 )
  {
    t1.tv_sec--;
    t1.tv_usec += USEC_PER_SEC;
  }
  return( t1 ) ;
}

inline struct timeval tvMake( time_t sec, suseconds_t usec )
{
  struct timeval t;
  t.tv_sec = sec;
  t.tv_usec = usec;
  return( t );
}

#endif // ZM_TIME_H
