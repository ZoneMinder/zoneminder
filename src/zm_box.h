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

#include "zm_vector2.h"
#include <cmath>

//
// Class used for storing a box, which is defined as a region
// defined by two coordinates
//
class Box {
 private:
  Vector2 lo, hi;
  Vector2 size;

 public:
  inline Box() : lo(0, 0), hi(0, 0), size(0, 0) {}
  explicit inline Box(unsigned int p_size) : lo(0, 0), hi(p_size - 1, p_size - 1), size(Vector2::Range(hi, lo)) {}
  inline Box(int p_x_size, int p_y_size) : lo(0, 0), hi(p_x_size - 1, p_y_size - 1), size(Vector2::Range(hi, lo)) {}
  inline Box(int lo_x, int lo_y, int hi_x, int hi_y) : lo(lo_x, lo_y), hi(hi_x, hi_y), size(Vector2::Range(hi, lo)) {}
  inline Box(const Vector2 &p_lo, const Vector2 &p_hi) : lo(p_lo), hi(p_hi), size(Vector2::Range(hi, lo)) {}

  inline const Vector2 &Lo() const { return lo; }
  inline int LoX() const { return lo.X(); }
  inline int LoX(int p_lo_x) { return lo.X(p_lo_x); }
  inline int LoY() const { return lo.Y(); }
  inline int LoY(int p_lo_y) { return lo.Y(p_lo_y); }
  inline const Vector2 &Hi() const { return hi; }
  inline int HiX() const { return hi.X(); }
  inline int HiX(int p_hi_x) { return hi.X(p_hi_x); }
  inline int HiY() const { return hi.Y(); }
  inline int HiY(int p_hi_y) { return hi.Y(p_hi_y); }
  inline const Vector2 &Size() const { return size; }
  inline int Width() const { return size.X(); }
  inline int Height() const { return size.Y(); }
  inline int Area() const { return size.X() * size.Y(); }

  inline const Vector2 Centre() const {
    int mid_x = int(std::round(lo.X() + (size.X() / 2.0)));
    int mid_y = int(std::round(lo.Y() + (size.Y() / 2.0)));
    return Vector2(mid_x, mid_y);
  }
  inline bool Inside(const Vector2 &coord) const {
    return (coord.X() >= lo.X() && coord.X() <= hi.X() && coord.Y() >= lo.Y() && coord.Y() <= hi.Y());
  }
};

#endif // ZM_BOX_H
