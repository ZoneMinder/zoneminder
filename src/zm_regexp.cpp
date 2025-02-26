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

RegExpr::RegExpr( const char *pattern, uint32_t flags, int p_max_matches ) : max_matches( p_max_matches ), match_buffers( nullptr ), match_lengths( nullptr ), match_valid( nullptr ) {
  char errstr[120];
  int err;
  PCRE2_SIZE erroffset;
  if ( !(regex = pcre2_compile( (PCRE2_SPTR)pattern, strlen( pattern ), flags, &err, &erroffset, NULL )) ) {
    pcre2_get_error_message( err, (PCRE2_UCHAR *)errstr, sizeof(errstr) );
    Panic( "pcre2_compile(%s): %s at %zu", pattern, errstr, erroffset );
  }

  if ( (ok = (bool)regex) ) {
    match_data = pcre2_match_data_create( 3*max_matches, NULL );
    match_buffers = new char *[max_matches];
    memset( match_buffers, 0, sizeof(*match_buffers)*max_matches );
    match_lengths = new int[max_matches];
    memset( match_lengths, 0, sizeof(*match_lengths)*max_matches );
    match_valid = new bool[max_matches];
    memset( match_valid, 0, sizeof(*match_valid)*max_matches );
  } else {
    match_vectors = nullptr;
  }
  match_string = "";
  n_matches = 0;
}

RegExpr::~RegExpr() {
  for ( int i = 0; i < max_matches; i++ ) {
    if ( match_buffers[i] ) {
      delete[] match_buffers[i];
    }
  }
  delete[] match_valid;
  delete[] match_lengths;
  delete[] match_buffers;
  pcre2_match_data_free( match_data );
  pcre2_code_free( regex );
}

int RegExpr::Match( const char *subject_string, PCRE2_SIZE subject_length, uint32_t flags ) {
  match_string = subject_string;

  n_matches = pcre2_match( regex, (PCRE2_SPTR)subject_string, subject_length, 0, flags, match_data, NULL );
  match_vectors = pcre2_get_ovector_pointer( match_data );

  if ( n_matches <= 0 ) {
    if ( n_matches != PCRE2_ERROR_NOMATCH ) {
      Error( "Error %d executing regular expression", n_matches );
    }
    return( n_matches = 0 );
  }

  for( int i = 0; i < max_matches; i++ ) {
    match_valid[i] = false;
  }
  return( n_matches );
}

const char *RegExpr::MatchString( int match_index ) const {
  if ( match_index > n_matches ) {
    return( 0 );
  }
  if ( !match_valid[match_index] ) {
    int match_len = (int)(match_vectors[(2*match_index)+1]-match_vectors[2*match_index]);
    if ( match_lengths[match_index] < (match_len+1) ) {
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

int RegExpr::MatchLength( int match_index ) const {
  if ( match_index > n_matches ) {
    return( 0 );
  }
  return( (int)(match_vectors[(2*match_index)+1]-match_vectors[2*match_index]) );
}

#endif // HAVE_LIBPCRE
