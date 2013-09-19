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
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
*/ 

#include <string.h>

#include "zm.h"
#include "zm_buffer.h"

unsigned int Buffer::assign( const unsigned char *pStorage, unsigned int pSize )
{
    if ( mAllocation < pSize )
    {
        delete[] mStorage;
        mAllocation = pSize;
        mHead = mStorage = new unsigned char[pSize];
    }
    mSize = pSize;
    memcpy( mStorage, pStorage, mSize );
    mHead = mStorage;
    mTail = mHead + mSize;
    return( mSize );
}

unsigned int Buffer::expand( unsigned int count )
{
    int spare = mAllocation - mSize;
    int headSpace = mHead - mStorage;
    int tailSpace = spare - headSpace;
    int width = mTail - mHead;
    if ( spare > (int)count )
    {
        if ( tailSpace < (int)count )
        {
            memmove( mStorage, mHead, mSize );
            mHead = mStorage;
            mTail = mHead + width;
        }
    }
    else
    {
        mAllocation += count;
        unsigned char *newStorage = new unsigned char[mAllocation];
        if ( mStorage )
        {
            memcpy( newStorage, mHead, mSize );
            delete[] mStorage;
        }
        mStorage = newStorage;
        mHead = mStorage;
        mTail = mHead + width;
    }
    return( mSize );
}
