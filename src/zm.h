//
// ZoneMinder Core Interfaces, $Date$, $Revision$
// $Copyright$
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
// Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
// 

#ifndef ZM_H
#define ZM_H

extern "C"
{
#include "zm_debug.h"
}

#include "zm_config.h"

extern "C"
{
#if !HAVE_DECL_ROUND
double round(double);
#endif
}

typedef unsigned char       U8;
typedef unsigned short      U16;
typedef unsigned long       U32;
typedef unsigned long long  U64;

typedef signed char         S8;
typedef signed short        S16;
typedef signed long         S32;
typedef signed long long    S64;

#endif // ZM_H
