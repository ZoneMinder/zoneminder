/*
 * This file is part of the ZoneMinder Project. See AUTHORS file for Copyright information
 *
 * This program is free software; you can redistribute it and/or modify it
 * under the terms of the GNU General Public License as published by the
 * Free Software Foundation; either version 2 of the License, or (at your
 * option) any later version.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or
 * FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for
 * more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program. If not, see <http://www.gnu.org/licenses/>.
 */

#ifndef ZONEMINDER_SRC_ZM_LINE_H_
#define ZONEMINDER_SRC_ZM_LINE_H_

#include "zm_vector2.h"

// Represents a part of a line bounded by two end points
class LineSegment {
 public:
  LineSegment(Vector2 start, Vector2 end) : start_(start), end_(end) {}

 public:
  Vector2 start_;
  Vector2 end_;
};

// Represents an infinite line
class Line {
 public:
  Line(Vector2 p1, Vector2 p2) : position_(p1), direction_(p2 - p1) {}
  explicit Line(LineSegment segment) : Line(segment.start_, segment.end_){};

  bool IsPointLeftOfOrColinear(Vector2 p) const {
    int32 det = direction_.Determinant(p - position_);

    return det >= 0;
  }

  Vector2 Intersection(Line const &line) const {
    int32 det = direction_.Determinant(line.direction_);

    if (det == 0) {
      // lines are parallel or overlap, no intersection
      return Vector2::Inf();
    }

    Vector2 c = line.position_ - position_;
    double t = c.Determinant(line.direction_) / static_cast<double>(det);

    return position_ + direction_ * t;
  }

 private:
  Vector2 position_;
  Vector2 direction_;
};

#endif  // ZONEMINDER_SRC_ZM_LINE_H_
