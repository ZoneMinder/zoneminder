//
// ZoneMinder RTP Data Class Interface, $Date$, $Revision$
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

#ifndef ZM_RTP_DATA_H
#define ZM_RTP_DATA_H

#include "zm_thread.h"
#include "zm_buffer.h"

#include <stdint.h>

class RtspThread;
class RtpSource;

struct RtpDataHeader
{
  uint8_t cc:4;     // CSRC count
  uint8_t x:1;      // header extension flag
  uint8_t p:1;      // padding flag
  uint8_t version:2;  // protocol version
  uint8_t pt:7;     // payload type
  uint8_t m:1;      // marker bit
  uint16_t seqN;    // sequence number, network order
  uint32_t timestampN;  // timestamp, network order
  uint32_t ssrcN;     // synchronization source, network order
  uint32_t csrc[];    // optional CSRC list
};

class RtpDataThread : public Thread
{
friend class RtspThread;

private:
  RtspThread &mRtspThread;
  RtpSource &mRtpSource;
  bool mStop;

private:
  bool recvPacket( const unsigned char *packet, size_t packetLen );
  int run();

public:
  RtpDataThread( RtspThread &rtspThread, RtpSource &rtpSource );

  void stop()
  {
    mStop = true;
  }
};

#endif // ZM_RTP_DATA_H
