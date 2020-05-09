/*
 * ZoneMinder regular expression class implementation, $Date$, $Revision$
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

#include <string.h>

#include "zm.h"
#include "zm_regexp.h"

#if HAVE_LIBPCRE

RegExpr::RegExpr( const char *pattern, int flags, int p_max_matches ) : max_matches( p_max_matches ), match_buffers( 0 ), match_lengths( 0 ), match_valid( 0 )
{
  const char *errstr;
  int erroffset = 0;
  if ( !(regex = pcre_compile( pattern, flags, &errstr, &erroffset, 0 )) )
  {
    Panic( "pcre_compile(%s): %s at %d", pattern, errstr, erroffset );
  }

  regextra = pcre_study( regex, 0, &errstr );
  if ( errstr )
  {
    Panic( "pcre_study(%s): %s", pattern, errstr );
  }

  if ( (ok = (bool)regex) )
  {
    match_vectors = new int[3*max_matches];
    memset( match_vectors, 0, sizeof(*match_vectors)*3*max_matches );
    match_buffers = new char *[max_matches];
    memset( match_buffers, 0, sizeof(*match_buffers)*max_matches );
    match_lengths = new int[max_matches];
    memset( match_lengths, 0, sizeof(*match_lengths)*max_matches );
    match_valid = new bool[max_matches];
    memset( match_valid, 0, sizeof(*match_valid)*max_matches );
  } else {
    match_vectors = NULL;
  }
  match_string = "";
  n_matches = 0;
}

RegExpr::~RegExpr()
{
  for ( int i = 0; i < max_matches; i++ )
  {
    if ( match_buffers[i] )
    {
      delete[] match_buffers[i];
    }
  }
  delete[] match_valid;
  delete[] match_lengths;
  delete[] match_buffers;
  delete[] match_vectors;
}

int RegExpr::Match( const char *subject_string, int subject_length, int flags )
{
  match_string = subject_string;

  n_matches = pcre_exec( regex, regextra, subject_string, subject_length, 0, flags, match_vectors, 2*max_matches );

  if ( n_matches <= 0 )
  {
    if ( n_matches < PCRE_ERROR_NOMATCH )
    {
      Error( "Error %d executing regular expression", n_matches );
    }
    return( n_matches = 0 );
  }

  for( int i = 0; i < max_matches; i++ )
  {
    match_valid[i] = false;
  }
  return( n_matches );
}

const char *RegExpr::MatchString( int match_index ) const
{
  if ( match_index > n_matches )
  {
    return( 0 );
  }
  if ( !match_valid[match_index] )
  {
    int match_len = match_vectors[(2*match_index)+1]-match_vectors[2*match_index];
    if ( match_lengths[match_index] < (match_len+1) )
    {
      delete[] match_buffers[match_index];
      match_buffers[match_index] = new char[match_len+1];
      match_lengths[match_index] = match_len+1;
    }
    memcpy( match_buffers[match_index], match_string+match_vectors[2*match_index], match_len );
    match_buffers[match_index][match_len] = '\0';
    match_valid[match_index] = true;
  }
  return( match_buffers[match_index] );
}

int RegExpr::MatchLength( int match_index ) const
{
  if ( match_index > n_matches )
  {
    return( 0 );
  }
  return( match_vectors[(2*match_index)+1]-match_vectors[2*match_index] );
}

#endif // HAVE_LIBPCRE
