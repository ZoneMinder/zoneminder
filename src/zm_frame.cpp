#include "zm_frame.h"

Frame::Frame(
    event_id_t              p_event_id,
    int                     p_frame_id,
    FrameType               p_type,
    struct timeval          p_timestamp,
    struct DeltaTimeval    &p_delta,
    int                     p_score,
    const std::list<ZoneStats>   &p_stats
    ) : 
      event_id(p_event_id),
      frame_id(p_frame_id),
      type(p_type),
      timestamp(p_timestamp),
      delta(p_delta),
      score(p_score),
      zone_stats(p_stats)
{
}
