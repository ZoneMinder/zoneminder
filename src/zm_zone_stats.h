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
#include "zm_logger.h"
#include "zm_vector2.h"

class ZoneStats {
 public:
  explicit ZoneStats(int zone_id)
      : zone_id_(zone_id),
        pixel_diff_(0),
        alarm_pixels_(0),
        alarm_filter_pixels_(0),
        alarm_blob_pixels_(0),
        alarm_blobs_(0),
        min_blob_size_(0),
        max_blob_size_(0),
        score_(0){};

  void Reset() {
    pixel_diff_ = 0;
    alarm_pixels_ = 0;
    alarm_filter_pixels_ = 0;
    alarm_blob_pixels_ = 0;
    alarm_blobs_ = 0;
    min_blob_size_ = 0;
    max_blob_size_ = 0;
    alarm_box_ = {};
    alarm_centre_ = {};
    score_ = 0;
  }

  void DumpToLog(const char *prefix) const {
    Debug(1,
          "ZoneStat: %s zone_id: %d pixel_diff=%d alarm_pixels=%d alarm_filter_pixels=%d "
          "alarm_blob_pixels=%d alarm_blobs=%d min_blob_size=%d max_blob_size=%d "
          "alarm_box=(%d,%d=>%d,%d) alarm_center=(%d,%d) score=%d",
          prefix,
          zone_id_,
          pixel_diff_,
          alarm_pixels_,
          alarm_filter_pixels_,
          alarm_blob_pixels_,
          alarm_blobs_,
          min_blob_size_,
          max_blob_size_,
          alarm_box_.Lo().x_,
          alarm_box_.Lo().y_,
          alarm_box_.Hi().x_,
          alarm_box_.Hi().y_,
          alarm_centre_.x_,
          alarm_centre_.y_,
          score_);
  }

 public:
  int zone_id_;
  int pixel_diff_;
  unsigned int alarm_pixels_;
  int alarm_filter_pixels_;
  int alarm_blob_pixels_;
  int alarm_blobs_;
  int min_blob_size_;
  int max_blob_size_;
  Box alarm_box_;
  Vector2 alarm_centre_;
  unsigned int score_;
};

#endif  // ZM_ZONE_STATS_H
