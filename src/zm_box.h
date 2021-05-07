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
  Box(Vector2 lo, Vector2 hi) : lo_(lo), hi_(hi), size_(Vector2::Range(hi, lo)) {}

  const Vector2 &Lo() const { return lo_; }
  int32 LoX(int lo_x) { return lo_.x_ = lo_x; }
  int32 LoY(int lo_y) { return lo_.y_ = lo_y; }
  const Vector2 &Hi() const { return hi_; }
  int32 HiX(int hi_x) { return hi_.x_ = hi_x; }
  int32 HiY(int hi_y) { return hi_.y_ = hi_y; }

  const Vector2 &Size() const { return size_; }
  int32 Area() const { return size_.x_ * size_.y_; }

  Vector2 Centre() const {
    int32 mid_x = static_cast<int32>(std::lround(lo_.x_ + (size_.x_ / 2.0)));
    int32 mid_y = static_cast<int32>(std::lround(lo_.y_ + (size_.y_ / 2.0)));
    return {mid_x, mid_y};
  }

  bool Contains(const Vector2 &coord) const  {
    return (coord.x_ >= lo_.x_ && coord.x_ <= hi_.x_ && coord.y_ >= lo_.y_ && coord.y_ <= hi_.y_);
  }

 private:
  Vector2 lo_;
  Vector2 hi_;
  Vector2 size_;
};

#endif // ZM_BOX_H
