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

#ifndef ZM_RTSP_SERVER_PTS_TRACKER_H
#define ZM_RTSP_SERVER_PTS_TRACKER_H

#include <cstdint>

// Keeps the presentation timestamps of one monitor's RTSP session from running
// backwards. When the camera reconnects or zmc restarts, the source timeline
// starts again from its own origin, but the clients of the session are still
// connected and expect RTP timestamps to carry on.
//
// After Discontinuity(), the first timestamp that is not ahead of the last one
// sent is moved to just after it, and the shift is kept for what follows. A
// timeline that resumes ahead of the last one is left alone.
class RtspPtsTracker {
 public:
  static constexpr int64_t kGapUs = 40000;  // spacing used for the first packet after a rewind

  void Discontinuity() { pending_ = true; }

  int64_t Map(int64_t pts_us) {
    pts_us += offset_;
    if (pending_) {
      pending_ = false;
      if (have_last_ and pts_us <= last_) {
        offset_ += last_ + kGapUs - pts_us;
        pts_us = last_ + kGapUs;
      }
    }
    if (!have_last_ or pts_us > last_) {
      last_ = pts_us;
      have_last_ = true;
    }
    return pts_us;
  }

 private:
  int64_t offset_ = 0;
  int64_t last_ = 0;
  bool have_last_ = false;
  bool pending_ = false;
};

#endif  // ZM_RTSP_SERVER_PTS_TRACKER_H
