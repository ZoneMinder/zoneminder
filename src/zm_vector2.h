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

#ifndef ZM_VECTOR2_H
#define ZM_VECTOR2_H

#include "zm_define.h"

//
// Class used for storing an x,y pair, i.e. a coordinate/vector
//
class Vector2 {
 public:
  inline Vector2() : x(0), y(0) {}
  inline Vector2(int p_x, int p_y) : x(p_x), y(p_y) {}
  inline Vector2(const Vector2 &p_coord) : x(p_coord.x), y(p_coord.y) {}

  inline Vector2 &operator=(const Vector2 &coord) {
    x = coord.x;
    y = coord.y;
    return *this;
  }

  inline int &X(int p_x) {
    x = p_x;
    return x;
  }
  inline const int &X() const { return x; }

  inline int &Y(int p_y) {
    y = p_y;
    return y;
  }
  inline const int &Y() const { return y; }

  inline static Vector2 Range(const Vector2 &coord1, const Vector2 &coord2) {
    Vector2 result((coord1.x - coord2.x) + 1, (coord1.y - coord2.y) + 1);
    return result;
  }

  inline bool operator==(const Vector2 &coord) const { return (x == coord.x && y == coord.y); }
  inline bool operator!=(const Vector2 &coord) const { return (x != coord.x || y != coord.y); }

  // These operators are not idiomatic. If lexicographic comparison is needed, it should be implemented separately.
  inline bool operator>(const Vector2 &coord) const = delete;
  inline bool operator>=(const Vector2 &coord) const = delete;
  inline bool operator<(const Vector2 &coord) const = delete;
  inline bool operator<=(const Vector2 &coord) const = delete;

  inline Vector2 &operator+=(const Vector2 &coord) {
    x += coord.x;
    y += coord.y;
    return *this;
  }
  inline Vector2 &operator-=(const Vector2 &coord) {
    x -= coord.x;
    y -= coord.y;
    return *this;
  }

  inline friend Vector2 operator+(const Vector2 &coord1, const Vector2 &coord2) {
    Vector2 result(coord1);
    result += coord2;
    return result;
  }
  inline friend Vector2 operator-(const Vector2 &coord1, const Vector2 &coord2) {
    Vector2 result(coord1);
    result -= coord2;
    return result;
  }

 private:
  int x;
  int y;
};

#endif // ZM_VECTOR2_H
