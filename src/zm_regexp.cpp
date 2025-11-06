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

#include "zm_regexp.h"

#include "zm_logger.h"
#include <cstring>

#if HAVE_LIBPCRE

RegExpr::RegExpr( const char *pattern, int flags, int p_max_matches ) : max_matches( p_max_matches ), match_buffers( nullptr ), match_lengths( nullptr ), match_valid( nullptr )
{
  int errorcode;
  PCRE2_SIZE erroffset = 0;
  match_data = pcre2_match_data_create(max_matches, nullptr);
  if ( !(regex = pcre2_compile( (PCRE2_SPTR8)pattern, strlen(pattern), flags, &errorcode, &erroffset, nullptr )) )
  {
    PCRE2_UCHAR buffer[256];
    pcre2_get_error_message(errorcode, buffer, sizeof(buffer));
    Panic( "pcre2_compile(%s): %s at %ld", pattern, buffer, erroffset );
  }

  if ( (ok = (bool)regex) )
  {
    match_buffers = new char *[max_matches];
    memset( match_buffers, 0, sizeof(*match_buffers)*max_matches );
    match_lengths = new PCRE2_SIZE[max_matches];
    memset( match_lengths, 0, sizeof(*match_lengths)*max_matches );
    match_valid = new bool[max_matches];
    memset( match_valid, 0, sizeof(*match_valid)*max_matches );
  } else {
    pcre2_match_data_free(match_data);
    match_data = nullptr;
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
  if ( match_data )
  {
    pcre2_match_data_free(match_data);
  }
}

int RegExpr::Match( const char *subject_string, int subject_length, int flags )
{
  match_string = subject_string;

  int rc = pcre2_match( regex, (PCRE2_SPTR)subject_string, subject_length, 0, flags, match_data, nullptr );
  n_matches = pcre2_get_ovector_count(match_data);

  if ( rc < 0 || n_matches <= 0 )
  {
    if ( rc <= PCRE2_ERROR_NOMATCH )
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
    PCRE2_SIZE* ovector = pcre2_get_ovector_pointer(match_data);
    PCRE2_SIZE match_len = ovector[(2*match_index)+1]-ovector[2*match_index];
    if ( match_lengths[match_index] < (match_len+1) )
    {
      delete[] match_buffers[match_index];
      match_buffers[match_index] = new char[match_len+1];
      match_lengths[match_index] = match_len+1;
    }
    memcpy( match_buffers[match_index], match_string+ovector[2*match_index], match_len );
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
  PCRE2_SIZE* ovector = pcre2_get_ovector_pointer(match_data);
  return( ovector[(2*match_index)+1]-ovector[2*match_index] );
}

#endif // HAVE_LIBPCRE
