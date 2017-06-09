/*
 * ZoneMinder Regular Expression Interface, $Date$, $Revision$
 * Copyright (C) 2001-2008 Philip Coombes
 * 
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
*/  

#include "zm.h"

#ifndef ZM_REGEXP_H
#define ZM_REGEXP_H

#if HAVE_LIBPCRE

#if HAVE_PCRE_H
#include <pcre.h>
#elif HAVE_PCRE_PCRE_H
#include <pcre/pcre.h>
#else
#error Unable to locate pcre.h, please do 'locate pcre.h' and report location to zoneminder.com
#endif

class RegExpr
{
protected:
  pcre *regex;
  pcre_extra *regextra;
  int max_matches;
  int *match_vectors;
  mutable char **match_buffers;
  int *match_lengths;
  bool *match_valid;

protected:
  const char *match_string;
  int n_matches;
  
protected:
  bool ok;

public:
  RegExpr( const char *pattern, int cflags=0, int p_max_matches=32 );
  ~RegExpr();
  bool Ok() const { return( ok ); }
  int MatchCount() const { return( n_matches ); }
  int Match( const char *subject_string, int subject_length, int flags=0 );
  const char *MatchString( int match_index ) const;
  int MatchLength( int match_index ) const;
};

#endif // HAVE_LIBPCRE

#endif // ZM_REGEXP_H
