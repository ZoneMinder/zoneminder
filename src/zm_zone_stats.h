//
// ZoneMinder Zone Stats Class Interfaces, $Date$, $Revision$
// Copyright (C) 2021 Isaac Connor
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

#ifndef ZM_ZONE_STATS_H
#define ZM_ZONE_STATS_H

#include "zm_box.h"
#include "zm_coord.h"
#include "zm_logger.h"

class ZoneStats {
 public:
  explicit ZoneStats(int z_id) :
      zone_id(z_id),
      pixel_diff(0),
      alarm_pixels(0),
      alarm_filter_pixels(0),
      alarm_blob_pixels(0),
      alarm_blobs(0),
      min_blob_size(0),
      max_blob_size(0),
      alarm_box({}),
      alarm_centre({}),
      score(0) {};

  void reset() {
    pixel_diff = 0;
    alarm_pixels = 0;
    alarm_filter_pixels = 0;
    alarm_blob_pixels = 0;
    alarm_blobs = 0;
    min_blob_size = 0;
    max_blob_size = 0;
    alarm_box.LoX(0);
    alarm_box.LoY(0);
    alarm_box.HiX(0);
    alarm_box.HiY(0);
    alarm_centre = {};
    score = 0;
  }

  void debug(const char *prefix) const {
    Debug(1,
          "ZoneStat: %s zone_id: %d pixel_diff=%d alarm_pixels=%d alarm_filter_pixels=%d alarm_blob_pixels=%d alarm_blobs=%d min_blob_size=%d max_blob_size=%d alarm_box=(%d,%d=>%d,%d) alarm_center=(%d,%d) score=%d",
          prefix,
          zone_id,
          pixel_diff,
          alarm_pixels,
          alarm_filter_pixels,
          alarm_blob_pixels,
          alarm_blobs,
          min_blob_size,
          max_blob_size,
          alarm_box.LoX(),
          alarm_box.LoY(),
          alarm_box.HiX(),
          alarm_box.HiY(),
          alarm_centre.X(),
          alarm_centre.Y()
    );
  }

 public:
  int zone_id;
  int pixel_diff;
  unsigned int alarm_pixels;
  int alarm_filter_pixels;
  int alarm_blob_pixels;
  int alarm_blobs;
  int min_blob_size;
  int max_blob_size;
  Box alarm_box;
  Coord alarm_centre;
  unsigned int score;
};

#endif // ZM_ZONE_STATS_H
