//
// ZoneMinder Memory Utilities, $Date$, $Revision$
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

#ifndef ZM_MEM_UTILS_H
#define ZM_MEM_UTILS_H

#include <stdlib.h>
#include "zm.h"

inline void* zm_mallocaligned(unsigned int reqalignment, size_t reqsize) {
  uint8_t* retptr;
#if HAVE_POSIX_MEMALIGN
  if ( posix_memalign((void**)&retptr,reqalignment,reqsize) != 0 )
    return NULL;
  
  return retptr;
#else
  uint8_t* alloc;
  retptr = (uint8_t*)malloc(reqsize+reqalignment+sizeof(void*));
  
  if ( retptr == NULL )
    return NULL;
  
  alloc = retptr + sizeof(void*);
  
  if(((long)alloc % reqalignment) != 0)
    alloc = alloc + (reqalignment - ((long)alloc % reqalignment));
  
  /* Store a pointer before to the start of the block, just before returned aligned memory */
  *(void**)(alloc - sizeof(void*)) = retptr;
  
  return alloc;
#endif
}

inline void zm_freealigned(void* ptr) {
#if HAVE_POSIX_MEMALIGN
  free(ptr);
#else
  /* Start of block is stored before the block if it was allocated by zm_mallocaligned */
  free(*(void**)((uint8_t*)ptr - sizeof(void*)));
#endif
}

inline char *mempbrk(const char *s, const char *accept, size_t limit) {
  if ( limit == 0 || !s || !accept || !*accept )
    return 0;

  unsigned int i,j;
  size_t acc_len = strlen(accept);

  for ( i = 0; i < limit; s++, i++ ) {
    for ( j = 0; j < acc_len; j++ ) {
      if ( *s == accept[j] ) {
        return (char *)s;
      }
    }
  }
  return 0;
}

inline char *memstr(const char *s, const char *n, size_t limit) {
  if ( limit == 0 || !s || !n )
    return 0;

  if ( !*n )
    return (char *)s;

  unsigned int i,j,k;
  size_t n_len = strlen(n);

  for ( i = 0; i < limit; i++, s++ ) {
    if ( *s != *n )
      continue;
    j = 1;
    k = 1;
    while ( true ) {
      if ( k >= n_len )
        return (char *)s;
      if ( s[j++] != n[k++] )
        break;
    }
  }
  return 0;
}

inline size_t memspn(const char *s, const char *accept, size_t limit) {
  if ( limit == 0 || !s || !accept || !*accept )
    return 0;

  unsigned int i,j;
  size_t acc_len = strlen(accept);

  for ( i = 0; i < limit; s++, i++ ) {
    bool found = false;
    for ( j = 0; j < acc_len; j++ ) {
      if ( *s == accept[j] ) {
        found = true;
        break;
      }
    }
    if ( !found ) {
      return i;
    }
  }
  return limit;
}

inline size_t memcspn(const char *s, const char *reject, size_t limit) {
  if ( limit == 0 || !s || !reject )
    return 0;

  if ( !*reject )
    return limit;

  unsigned int i,j;
  size_t rej_len = strlen( reject );

  for ( i = 0; i < limit; s++, i++ ) {
    for ( j = 0; j < rej_len; j++ ) {
      if ( *s == reject[j] ) {
        return i;
      }
    }
  }
  return limit;
}

#endif // ZM_MEM_UTILS_H
