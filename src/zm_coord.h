//
// ZoneMinder Coordinate Class Interface, $Date$, $Revision$
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

#ifndef ZM_COORD_H
#define ZM_COORD_H

#include "zm.h"

//
// Class used for storing an x,y pair, i.e. a coordinate
//
class Coord {
private:
  int x, y;

public:
  inline Coord() : x(0), y(0) { }
  inline Coord( int p_x, int p_y ) : x(p_x), y(p_y) { }
  inline Coord( const Coord &p_coord ) : x(p_coord.x), y(p_coord.y) { }
  inline Coord &operator =( const Coord &coord ) {
    x = coord.x;
    y = coord.y;
    return *this;
  }
  inline int &X() { return( x ); }
  inline const int &X() const { return( x ); }
  inline int &Y() { return( y ); }
  inline const int &Y() const { return( y ); }

  inline static Coord Range( const Coord &coord1, const Coord &coord2 ) {
    Coord result( (coord1.x-coord2.x)+1, (coord1.y-coord2.y)+1 );
    return( result );
  }

  inline bool operator==( const Coord &coord ) { return( x == coord.x && y == coord.y ); }
  inline bool operator!=( const Coord &coord ) { return( x != coord.x || y != coord.y ); }
  inline bool operator>( const Coord &coord ) { return( x > coord.x && y > coord.y ); }
  inline bool operator>=( const Coord &coord ) { return( !(operator<(coord)) ); }
  inline bool operator<( const Coord &coord ) { return( x < coord.x && y < coord.y ); }
  inline bool operator<=( const Coord &coord ) { return( !(operator>(coord)) ); }
  inline Coord &operator+=( const Coord &coord ) { x += coord.x; y += coord.y; return( *this ); }
  inline Coord &operator-=( const Coord &coord ) { x -= coord.x; y -= coord.y; return( *this ); }

  inline friend Coord operator+( const Coord &coord1, const Coord &coord2 ) { Coord result( coord1 ); result += coord2; return( result ); }
  inline friend Coord operator-( const Coord &coord1, const Coord &coord2 ) { Coord result( coord1 ); result -= coord2; return( result ); }
};

#endif // ZM_COORD_H
