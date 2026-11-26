#ifndef ZM_SECONDARY_SYNC_H
#define ZM_SECONDARY_SYNC_H

#include "zm_time.h"

#include <chrono>

// Substream stall check for AnalysisSource=Secondary.
//
// Known limitation: this is NOT per-frame sync between the two streams. The
// substream is decoded by a sidecar thread (SecondStreamThread) outside the
// packetqueue, and analysis scores whichever substream frame is newest when it
// reaches a primary packet. Frames are not paired with packets by capture
// time, so the frame scored for a packet can be up to a substream frame
// interval, or the analysis backlog, away from it. That is close enough for
// motion detection. Pairing each analysed packet with the substream frame
// captured with it would need both capture threads to feed the packetqueue,
// ordered by wallclock.
//
// What is checked is that the substream is still producing. The primary
// capture stamps each packet with a steady_clock timestamp
// (ZMPacket::timestamp_steady) taken back-to-back with its system timestamp,
// and the sidecar stamps each decoded frame with a steady_clock time
// (SecondStreamThread::capture_time_). Comparing the two steady stamps never
// touches the system clock, so the check is immune to NTP steps at any time.

// True when the substream has stalled or died: the packet under analysis was
// captured more than max_skew AFTER the newest substream frame, i.e. the
// sidecar has stopped producing frames.  The test is deliberately one-sided: a
// frame NEWER than the packet just means the analysis thread lags capture
// (packetqueue backlog) while the substream is healthy, and analysis pairs
// with the freshest frame as it always has, so such frames must still be
// scored.
inline bool SecondaryFrameStalled(TimePoint packet_steady_ts,
                                  TimePoint frame_steady_ts,
                                  Seconds max_skew) {
  return (packet_steady_ts - frame_steady_ts) >
         std::chrono::duration_cast<TimePoint::duration>(max_skew);
}

#endif  // ZM_SECONDARY_SYNC_H
