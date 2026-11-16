/*
 * ZoneMinder flexible memory class implementation, $Date$, $Revision$
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

#include "zm_buffer.h"

#include <unistd.h>

unsigned int Buffer::assign(const unsigned char *pStorage, unsigned int pSize) {
  if ( mAllocation < pSize ) {
    delete[] mStorage;
    mAllocation = pSize;
    mHead = mStorage = new unsigned char[pSize];
  }
  mSize = pSize;
  memcpy(mStorage, pStorage, mSize);
  mHead = mStorage;
  mTail = mHead + mSize;
  return mSize;
}

unsigned int Buffer::expand(unsigned int count) {
  // Sizes are worked out in size_t: count can come from a network peer (a camera's
  // Content-Length), and the int arithmetic this used to do wrapped once the allocation
  // passed INT_MAX, leaving a small block behind. refs GHSA-x959-xrc9-89p8
  const size_t head_space = mHead - mStorage;
  const size_t tail_space = static_cast<size_t>(mAllocation) - head_space - mSize;
  if (tail_space >= count) return mSize;

  if (static_cast<size_t>(mAllocation) - mSize >= count) {
    // There is enough space in the allocation once the data is shifted to the front
    memmove(mStorage, mHead, mSize);
    mHead = mStorage;
    mTail = mHead + mSize;
    return mSize;
  }

  const size_t needed = static_cast<size_t>(mSize) + count;
  if (needed > kMaxAllocation) {
    Error("Refusing to grow buffer of %u bytes by %u bytes", mSize, count);
    return mSize;
  }
  unsigned char *newStorage = new unsigned char[needed];
  if (mStorage) {
    memcpy(newStorage, mHead, mSize);
    delete[] mStorage;
  } else {
    memset(newStorage, 0, needed);
  }
  mAllocation = static_cast<unsigned int>(needed);
  mStorage = newStorage;
  mHead = mStorage;
  mTail = mHead + mSize;
  return mSize;
}

int Buffer::read_into(int sd, unsigned int bytes) {
  // Make sure there is enough space
  this->expand(bytes);
  if (static_cast<size_t>(mStorage + mAllocation - mTail) < bytes) {
    Error("No room to read %u bytes into buffer of %u bytes", bytes, mSize);
    return -1;
  }
  Debug(3, "Reading %u bytes", bytes);
  int bytes_read = ::read(sd, mTail, bytes);
  if (bytes_read > 0) {
    mTail += bytes_read;
    mSize += bytes_read;
  }
  return bytes_read;
}

int Buffer::read_into(int sd, unsigned int bytes, Microseconds timeout) {
  fd_set set;
  FD_ZERO(&set); /* clear the set */
  FD_SET(sd, &set); /* add our file descriptor to the set */
  timeval timeout_tv = zm::chrono::duration_cast<timeval>(timeout);

  int rv = select(sd + 1, &set, nullptr, nullptr, &timeout_tv);
  if (rv == -1) {
    Error("Error %d %s from select", errno, strerror(errno));
    return rv;
  } else if (rv == 0) {
    Debug(1, "timeout"); /* a timeout occurred */
    return 0;
  }

  return read_into(sd, bytes);
}
