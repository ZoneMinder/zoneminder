//
// ZoneMinder Polygon Class Implementation, $Date$, $Revision$
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

#include "zm_poly.h"

#include "zm_line.h"

#include <cmath>

Polygon::Polygon(std::vector<Vector2> vertices) : vertices_(std::move(vertices)), area(0) {
  UpdateExtent();
  UpdateArea();
  UpdateCentre();
}

void Polygon::UpdateExtent() {
  if (vertices_.empty()) return;

  int min_x = vertices_[0].x_;
  int max_x = 0;
  int min_y = vertices_[0].y_;
  int max_y = 0;
  for (const Vector2 &vertex : vertices_) {
    min_x = std::min(min_x, vertex.x_);
    max_x = std::max(max_x, vertex.x_);
    min_y = std::min(min_y, vertex.y_);
    max_y = std::max(max_y, vertex.y_);
  }

  extent = Box({min_x, min_y}, {max_x, max_y});
}

void Polygon::UpdateArea() {
  double float_area = 0.0;
  for (size_t i = 0, j = vertices_.size() - 1; i < vertices_.size(); j = i++) {
    double trap_area =
        ((vertices_[i].x_ - vertices_[j].x_) * ((vertices_[i].y_ + vertices_[j].y_))) / 2.0;
    float_area += trap_area;
  }

  area = static_cast<int32>(std::lround(std::fabs(float_area)));
}

void Polygon::UpdateCentre() {
  if (!area && !vertices_.empty()) UpdateArea();

  double float_x = 0.0;
  double float_y = 0.0;
  for (size_t i = 0, j = vertices_.size() - 1; i < vertices_.size(); j = i++) {
    float_x +=
        ((vertices_[i].y_ - vertices_[j].y_) *
         ((vertices_[i].x_ * 2) + (vertices_[i].x_ * vertices_[j].x_) + (vertices_[j].x_ * 2)));
    float_y +=
        ((vertices_[j].x_ - vertices_[i].x_) *
         ((vertices_[i].y_ * 2) + (vertices_[i].y_ * vertices_[j].y_) + (vertices_[j].y_ * 2)));
  }
  float_x /= (6 * area);
  float_y /= (6 * area);

  centre =
      Vector2(static_cast<int32>(std::lround(float_x)), static_cast<int32>(std::lround(float_y)));
}

bool Polygon::Contains(const Vector2 &coord) const {
  bool inside = false;
  for (size_t i = 0, j = vertices_.size() - 1; i < vertices_.size(); j = i++) {
    if ((((vertices_[i].y_ <= coord.y_) && (coord.y_ < vertices_[j].y_)) ||
         ((vertices_[j].y_ <= coord.y_) && (coord.y_ < vertices_[i].y_))) &&
        (coord.x_ < (vertices_[j].x_ - vertices_[i].x_) * (coord.y_ - vertices_[i].y_) /
                            (vertices_[j].y_ - vertices_[i].y_) +
                        vertices_[i].x_)) {
      inside = !inside;
    }
  }
  return inside;
}

// Clip the polygon to a rectangular boundary box using the Sutherland-Hodgman algorithm
void Polygon::Clip(const Box &boundary) {
  std::vector<Vector2> clipped_vertices = vertices_;

  for (LineSegment const &clip_edge : boundary.Edges()) {
    // convert our line segment to an infinite line
    Line clip_line = Line(clip_edge);

    std::vector<Vector2> to_clip = clipped_vertices;
    clipped_vertices.clear();

    for (size_t i = 0; i < to_clip.size(); ++i) {
      Vector2 vert1 = to_clip[i];
      Vector2 vert2 = to_clip[(i + 1) % to_clip.size()];

      bool vert1_left = clip_line.IsPointLeftOfOrColinear(vert1);
      bool vert2_left = clip_line.IsPointLeftOfOrColinear(vert2);

      if (vert2_left) {
        if (!vert1_left) {
          clipped_vertices.push_back(Line(vert1, vert2).Intersection(clip_line));
        }
        clipped_vertices.push_back(vert2);
      } else if (vert1_left) {
        clipped_vertices.push_back(Line(vert1, vert2).Intersection(clip_line));
      }
    }
  }

  vertices_ = clipped_vertices;
  UpdateExtent();
  UpdateArea();
  UpdateCentre();
}
