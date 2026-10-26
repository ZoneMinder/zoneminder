//
// ZoneMinder per-monitor websocket status change detection
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

#ifndef ZM_WEBSOCKET_STATUS_H
#define ZM_WEBSOCKET_STATUS_H

#include <cstdint>

namespace zm {
namespace websocket {

// The subset of a monitor's status that the websocket transport pushes on
// change (refs #2875). Discrete fields are compared for equality; fps and
// bandwidth drift every frame, so they are compared against a threshold. The
// frame-rate churn in the full status JSON (image_count, last_frame_score,
// heartbeat_time) is deliberately absent here so it does not trigger a push.
struct StatusSnapshot {
  bool connected = false;
  bool shm_valid = false;
  uint32_t state = 0;
  uint32_t analysing = 0;
  uint32_t capturing = 0;
  uint32_t recording = 0;
  uint32_t signal = 0;
  uint64_t last_event_id = 0;
  double capture_fps = 0.0;
  double analysis_fps = 0.0;
  uint32_t capture_bandwidth = 0;
};

// Treat fps as changed when it crosses to/from zero (stream stop/start) or
// moves by at least one frame per second; smaller jitter is ignored.
inline bool StatusFpsChanged(double a, double b) {
  const double diff = a > b ? a - b : b - a;
  return ((a == 0.0) != (b == 0.0)) || (diff >= 1.0);
}

// Treat bandwidth as changed when it moves by more than 1 KiB and by at least
// 10% of the larger value, so steady-state drift does not push but a real shift
// does.
inline bool StatusBandwidthChanged(uint32_t a, uint32_t b) {
  const uint32_t hi = a > b ? a : b;
  const uint32_t lo = a > b ? b : a;
  const uint32_t diff = hi - lo;
  return (diff > 1024) && (diff * 10 > hi);
}

inline bool StatusSignificantlyChanged(const StatusSnapshot &prev, const StatusSnapshot &cur) {
  return (prev.connected != cur.connected) ||
         (prev.shm_valid != cur.shm_valid) ||
         (prev.state != cur.state) ||
         (prev.analysing != cur.analysing) ||
         (prev.capturing != cur.capturing) ||
         (prev.recording != cur.recording) ||
         (prev.signal != cur.signal) ||
         (prev.last_event_id != cur.last_event_id) ||
         StatusFpsChanged(prev.capture_fps, cur.capture_fps) ||
         StatusFpsChanged(prev.analysis_fps, cur.analysis_fps) ||
         StatusBandwidthChanged(prev.capture_bandwidth, cur.capture_bandwidth);
}

}  // namespace websocket
}  // namespace zm

#endif  // ZM_WEBSOCKET_STATUS_H
