#include "zm_frame.h"

Frame::Frame(event_id_t p_event_id,
             int p_frame_id,
             FrameType p_type,
             SystemTimePoint p_timestamp,
             Microseconds p_delta,
             int p_score,
             std::vector<ZoneStats> p_stats)
  : event_id(p_event_id),
    frame_id(p_frame_id),
    type(p_type),
    timestamp(p_timestamp),
    delta(p_delta),
    score(p_score),
    zone_stats(std::move(p_stats)) {}
