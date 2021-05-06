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
 public:
  Box() = default;
  Box(Vector2 p_lo, Vector2 p_hi) : lo(p_lo), hi(p_hi), size(Vector2::Range(hi, lo)) {}

  const Vector2 &Lo() const { return lo; }
  int LoX(int p_lo_x) { return lo.x_ = p_lo_x; }
  int LoY(int p_lo_y) { return lo.y_ = p_lo_y; }
  const Vector2 &Hi() const { return hi; }
  int HiX(int p_hi_x) { return hi.x_ = p_hi_x; }
  int HiY(int p_hi_y) { return hi.y_ = p_hi_y; }
  const Vector2 &Size() const { return size; }
  int Width() const { return size.x_; }
  int Height() const { return size.y_; }
  int Area() const { return size.x_ * size.y_; }

  Vector2 Centre() const {
    int mid_x = int(std::round(lo.x_ + (size.x_ / 2.0)));
    int mid_y = int(std::round(lo.y_ + (size.y_ / 2.0)));
    return Vector2(mid_x, mid_y);
  }
  inline bool Inside(const Vector2 &coord) const  {
    return (coord.x_ >= lo.x_ && coord.x_ <= hi.x_ && coord.y_ >= lo.y_ && coord.y_ <= hi.y_);
  }

 private:
  Vector2 lo;
  Vector2 hi;
  Vector2 size;
};

#endif // ZM_BOX_H
