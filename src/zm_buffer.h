/*
 * ZoneMinder Flexible Memory Interface, $Date$, $Revision$
 * Copyright (C) 2003  Philip Coombes
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

#include "zm.h"

#ifndef ZM_BUFFER_H
#define ZM_BUFFER_H

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
	Buffer( unsigned int p_size ) : allocation( p_size ), size( p_size )
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
		memcpy( storage, buffer.storage, size );
		tail = head + size;
	}
	~Buffer()
	{
		delete[] storage;
	}
	void Dump( const char *s="" )
	{
		Info(( "%s - Size:%d, Allocation:%d, Storage:%p, Head:%p, Tail:%p", s, size, allocation, storage, head, tail ));
	}
	void FullDump( const char *s="" )
	{
		for ( int i = 0; i < size && i < 100; i++ )
		{
			Info(( "%d: %p - %02x\n", i, head+i, *(head+i) ));
		}
	}
	unsigned char *Head() const { return( head ); }
	unsigned char *Tail() const { return( tail ); }
	unsigned int Size() const { return( size ); }
	unsigned int Size( unsigned int p_size )
	{
		if ( size < p_size )
		{
			Expand( p_size );
		}
		return( size );
	}
	//unsigned int Allocation() const { return( allocation ); }

	void Empty()
	{
		size = 0;
		tail = head;
	}

	unsigned int Assign( const unsigned char *p_storage, unsigned int p_size );
	unsigned int Assign( const Buffer &buffer )
	{
		return( Assign( buffer.storage, buffer.size ) );
	}

	unsigned int Consume( unsigned int count )
	{
		head += count;
		size -= count;
		return( size );
	}
	unsigned int Shrink( unsigned int count )
	{
		size -= count;
		if ( tail > head + size )
			tail = head + size;
		return( size );
	}
	unsigned int Expand( unsigned int count );
	unsigned char *Extract( unsigned int p_size )
	{
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
		return( size );
	}
	unsigned int Append( const Buffer &buffer )
	{
		return( Append( buffer.head, buffer.size ) );
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
	operator unsigned int () const
	{
		return( size );
	}
	operator int () const
	{
		return( (int)size );
	}
};

#endif // ZM_BUFFER_H
