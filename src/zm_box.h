//
// ZoneMinder Box Class Interfaces, $Date$, $Revision$
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

#ifndef ZM_BOX_H
#define ZM_BOX_H

#include "zm.h"
#include "zm_coord.h"

#ifndef SOLARIS
#include <math.h>
#else
#include <cmath>
#endif

//
// Class used for storing a box, which is defined as a region
// defined by two coordinates
//
class Box {
private:
  Coord lo, hi;
  Coord size;

public:
  inline Box() { }
  explicit inline Box( int p_size ) : lo( 0, 0 ), hi ( p_size-1, p_size-1 ), size( Coord::Range( hi, lo ) ) { }
  inline Box( int p_x_size, int p_y_size ) : lo( 0, 0 ), hi ( p_x_size-1, p_y_size-1 ), size( Coord::Range( hi, lo ) ) { }
  inline Box( int lo_x, int lo_y, int hi_x, int hi_y ) : lo( lo_x, lo_y ), hi( hi_x, hi_y ), size( Coord::Range( hi, lo ) ) { }
  inline Box( const Coord &p_lo, const Coord &p_hi ) : lo( p_lo ), hi( p_hi ), size( Coord::Range( hi, lo ) ) { }

  inline const Coord &Lo() const { return lo; }
  inline int LoX() const { return lo.X(); }
  inline int LoY() const { return lo.Y(); }
  inline const Coord &Hi() const { return hi; }
  inline int HiX() const { return hi.X(); }
  inline int HiY() const { return hi.Y(); }
  inline const Coord &Size() const { return size; }
  inline int Width() const { return size.X(); }
  inline int Height() const { return size.Y(); }
  inline int Area() const { return size.X()*size.Y(); }

  inline const Coord Centre() const {
    int mid_x = int(round(lo.X()+(size.X()/2.0)));
    int mid_y = int(round(lo.Y()+(size.Y()/2.0)));
    return Coord( mid_x, mid_y );
  }
  inline bool Inside( const Coord &coord ) const
  {
    return( coord.X() >= lo.X() && coord.X() <= hi.X() && coord.Y() >= lo.Y() && coord.Y() <= hi.Y() );
  }
};

#endif // ZM_BOX_H
