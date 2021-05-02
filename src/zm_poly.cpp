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

#include <cmath>

void Polygon::calcArea() {
  double float_area = 0.0L;
  for (int i = 0, j = n_coords - 1; i < n_coords; j = i++) {
    double trap_area = ((coords[i].x_ - coords[j].x_) * ((coords[i].y_ + coords[j].y_))) / 2.0L;
    float_area += trap_area;
    //printf( "%.2f (%.2f)\n", float_area, trap_area );
  }
  area = (int) round(fabs(float_area));
}

void Polygon::calcCentre() {
  if (!area && n_coords)
    calcArea();
  double float_x = 0.0L, float_y = 0.0L;
  for (int i = 0, j = n_coords - 1; i < n_coords; j = i++) {
    float_x += ((coords[i].y_ - coords[j].y_) * ((coords[i].x_ * 2) + (coords[i].x_ * coords[j].x_) + (coords[j].x_ * 2)));
    float_y += ((coords[j].x_ - coords[i].x_) * ((coords[i].y_ * 2) + (coords[i].y_ * coords[j].y_) + (coords[j].y_ * 2)));
  }
  float_x /= (6 * area);
  float_y /= (6 * area);
  centre = Vector2((int) round(float_x), (int) round(float_y));
}

Polygon::Polygon(int p_n_coords, const Vector2 *p_coords) : n_coords(p_n_coords) {
  coords = new Vector2[n_coords];

  int min_x = -1;
  int max_x = -1;
  int min_y = -1;
  int max_y = -1;
  for (int i = 0; i < n_coords; i++) {
    coords[i] = p_coords[i];
    if (min_x == -1 || coords[i].x_ < min_x)
      min_x = coords[i].x_;
    if (max_x == -1 || coords[i].x_ > max_x)
      max_x = coords[i].x_;
    if (min_y == -1 || coords[i].y_ < min_y)
      min_y = coords[i].y_;
    if (max_y == -1 || coords[i].y_ > max_y)
      max_y = coords[i].y_;
  }
  extent = Box({min_x, min_y}, {max_x, max_y});
  calcArea();
  calcCentre();
}

Polygon::Polygon(const Polygon &p_polygon) :
    n_coords(p_polygon.n_coords),
    extent(p_polygon.extent),
    area(p_polygon.area),
    centre(p_polygon.centre) {
  coords = new Vector2[n_coords];
  for (int i = 0; i < n_coords; i++) {
    coords[i] = p_polygon.coords[i];
  }
}

Polygon &Polygon::operator=(const Polygon &p_polygon) {
  n_coords = p_polygon.n_coords;

  Vector2 *new_coords = new Vector2[n_coords];
  for (int i = 0; i < n_coords; i++) {
    new_coords[i] = p_polygon.coords[i];
  }
  delete[] coords;
  coords = new_coords;

  extent = p_polygon.extent;
  area = p_polygon.area;
  centre = p_polygon.centre;
  return *this;
}

bool Polygon::isInside(const Vector2 &coord) const {
  bool inside = false;
  for (int i = 0, j = n_coords - 1; i < n_coords; j = i++) {
    if ((((coords[i].y_ <= coord.y_) && (coord.y_ < coords[j].y_)) || ((coords[j].y_ <= coord.y_) && (coord.y_ < coords[i].y_)))
    && (coord.x_ < (coords[j].x_ - coords[i].x_) * (coord.y_ - coords[i].y_) / (coords[j].y_ - coords[i].y_) + coords[i].x_)) {
      inside = !inside;
    }
  }
  return inside;
}
