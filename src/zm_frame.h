//
// ZoneMinder Frame Class Interfaces, $Date$, $Revision$
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

#ifndef ZM_FRAME_H
#define ZM_FRAME_H

#include <sys/time.h>
#include <sys/types.h>
class Frame;

#include "zm_event.h"
#include "zm_time.h"

//
// This describes a frame record
//
class Frame {

public:
  Frame(
     event_id_t           p_event_id,
     int                  p_frame_id,
     FrameType            p_type,
     struct timeval       p_timestamp,
     struct DeltaTimeval  p_delta,
     int                  p_score
     );

  event_id_t     event_id;
  int            frame_id;
  FrameType      type;
  struct timeval timestamp;
  struct DeltaTimeval  delta;
  int score;

};

#endif // ZM_FRAME_H
