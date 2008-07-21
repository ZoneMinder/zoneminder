/*
 * ZoneMinder Flexible Memory Interface, $Date$, $Revision$
 * Copyright (C) 2003, 2004, 2005, 2006  Philip Coombes
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

#ifndef ZM_BUFFER_H
#define ZM_BUFFER_H

#include "zm.h"

#include <string.h>

class Buffer
{
protected:
    unsigned char *storage;
    unsigned int allocation;
    unsigned int size;
    unsigned char *head;
    unsigned char *tail;

public:
    Buffer() : storage( 0 ), allocation( 0 ), size( 0 ), head( 0 ), tail( 0 )
    {
    }
    Buffer( unsigned int p_size ) : allocation( p_size ), size( 0 )
    {
        head = storage = new unsigned char[allocation];
        tail = head;
    }
    Buffer( const unsigned char *p_storage, unsigned int p_size ) : allocation( p_size ), size( p_size )
    {
        head = storage = new unsigned char[size];
        memcpy( storage, p_storage, size );
        tail = head + size;
    }
    Buffer( const Buffer &buffer ) : allocation( buffer.size ), size( buffer.size )
    {
        head = storage = new unsigned char[size];
        memcpy( storage, buffer.head, size );
        tail = head + size;
    }
    ~Buffer()
    {
        delete[] storage;
    }
    unsigned char *Head() const { return( head ); }
    unsigned char *Tail() const { return( tail ); }
    unsigned int Size() const { return( size ); }
    unsigned int Size( unsigned int p_size )
    {
        if ( size < p_size )
        {
            Expand( p_size-size );
        }
        return( size );
    }
    //unsigned int Allocation() const { return( allocation ); }

    void Empty()
    {
        size = 0;
        head = tail = storage;
    }

    unsigned int Assign( const unsigned char *p_storage, unsigned int p_size );
    unsigned int Assign( const Buffer &buffer )
    {
        return( Assign( buffer.head, buffer.size ) );
    }

    unsigned int Consume( unsigned int count )
    {
        if ( count > size )
        {
            Warning( "Attempt to consume %d bytes of buffer, size is only %d bytes", count, size );
            count = size;
        }
        head += count;
        size -= count;
        return( count );
    }
    unsigned int Shrink( unsigned int count )
    {
        if ( count > size )
        {
            Warning( "Attempt to shrink buffer by %d bytes, size is only %d bytes", count, size );
            count = size;
        }
        size -= count;
        if ( tail > head + size )
            tail = head + size;
        return( count );
    }
    unsigned int Expand( unsigned int count );
    unsigned char *Extract( unsigned int p_size )
    {
        if ( p_size > size )
        {
            Warning( "Attempt to extract %d bytes of buffer, size is only %d bytes", p_size, size );
            p_size = size;
        }
        unsigned char *old_head = head;
        head += p_size;
        size -= p_size;
        return( old_head );
    }
    unsigned int Append( const unsigned char *p_storage, unsigned int p_size )
    {
        Expand( p_size );
        memcpy( tail, p_storage, p_size );
        tail += p_size;
        size += p_size;
        return( size );
    }
    unsigned int Append( const Buffer &buffer )
    {
        return( Append( buffer.head, buffer.size ) );
    }

    Buffer &operator=( const Buffer &buffer )
    {
        Assign( buffer );
        return( *this );
    }
    Buffer &operator+=( const Buffer &buffer )
    {
        Append( buffer );
        return( *this );
    }
    Buffer &operator+=( unsigned int count )
    {
        Expand( count );
        return( *this );
    }
    Buffer &operator-=( unsigned int count )
    {
        Consume( count );
        return( *this );
    }
    operator unsigned char *() const
    {
        return( head );
    }
    operator char *() const
    {
        return( (char *)head );
    }
    unsigned char operator[](int index) const
    {
        return( *(head+index) );
    }
    operator int () const
    {
        return( (int)size );
    }
};

#endif // ZM_BUFFER_H
