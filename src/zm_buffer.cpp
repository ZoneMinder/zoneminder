/*
 * ZoneMinder flexible memory class implementation, $Date$, $Revision$
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

#include <string.h>

#include "zm.h"
#include "zm_buffer.h"

unsigned int Buffer::Assign( const unsigned char *p_storage, unsigned int p_size )
{
    if ( allocation < p_size )
    {
        delete[] storage;
        allocation = p_size;
        head = storage = new unsigned char[p_size];
    }
    size = p_size;
    memcpy( storage, p_storage, size );
    head = storage;
    tail = head + size;
    return( size );
}

unsigned int Buffer::Expand( unsigned int count )
{
    int spare = allocation - size;
    int head_space = head - storage;
    int tail_space = spare - head_space;
    int width = tail - head;
    if ( spare > count )
    {
        if ( tail_space < count )
        {
            memmove( storage, head, size );
            head = storage;
            tail = head + width;
        }
    }
    else
    {
        allocation += count;
        unsigned char *new_storage = new unsigned char[allocation];
        if ( storage )
        {
            memcpy( new_storage, head, size );
            delete[] storage;
        }
        storage = new_storage;
        head = storage;
        tail = head + width;
    }
    return( size );
}
