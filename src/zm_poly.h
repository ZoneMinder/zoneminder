//
// ZoneMinder Polygon Class Interfaces, $Date$, $Revision$
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

#ifndef ZM_POLY_H
#define ZM_POLY_H

#include "zm_box.h"

class Coord;

//
// Class used for storing a box, which is defined as a region
// defined by two coordinates
//
class Polygon {
protected:
  struct Edge {
    int min_y;
    int max_y;
    double min_x;
    double _1_m;

    static int CompareYX( const void *p1, const void *p2 ) {
        const Edge *e1 = reinterpret_cast<const Edge *>(p1), *e2 = reinterpret_cast<const Edge *>(p2);
      if ( e1->min_y == e2->min_y )
        return int(e1->min_x - e2->min_x);
      else
        return int(e1->min_y - e2->min_y);
    }
    static int CompareX( const void *p1, const void *p2 ) {
      const Edge *e1 = reinterpret_cast<const Edge *>(p1), *e2 = reinterpret_cast<const Edge *>(p2);
      return int(e1->min_x - e2->min_x);
    }
  };

  struct Slice {
    int min_x;
    int max_x;
    int n_edges;
    int *edges;

    Slice() {
      min_x = 0;
      max_x = 0;
      n_edges = 0;
      edges = nullptr;
    }
    ~Slice() {
      delete edges;
    }
  };

protected:
  int n_coords;
  Vector2 *coords;
  Box extent;
  int area;
  Vector2 centre;

protected:
  void initialiseEdges();
  void calcArea();
  void calcCentre();

public:
  inline Polygon() : n_coords(0), coords(nullptr), area(0) {
  }
  Polygon(int p_n_coords, const Vector2 *p_coords);
  Polygon(const Polygon &p_polygon);
  ~Polygon() {
    delete[] coords;
  }

  Polygon &operator=( const Polygon &p_polygon );

  inline int getNumCoords() const { return n_coords; }
  inline const Vector2 &getCoord( int index ) const {
    return coords[index];
  }

  const Box &Extent() const { return extent; }
  int LoX(int p_lo_x) { return extent.LoX(p_lo_x); }
  int HiX(int p_hi_x) { return extent.HiX(p_hi_x); }
  int LoY(int p_lo_y) { return extent.LoY(p_lo_y); }
  int HiY(int p_hi_y) { return extent.HiY(p_hi_y); }

  inline int Area() const { return area; }
  inline const Vector2 &Centre() const {
    return centre;
  }
  bool isInside(const Vector2 &coord) const;
};

#endif // ZM_POLY_H
