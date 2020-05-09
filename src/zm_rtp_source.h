//
// ZoneMinder RTP Source Class Interface, $Date$, $Revision$
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

#ifndef ZM_RTP_SOURCE_H
#define ZM_RTP_SOURCE_H

#include "zm_buffer.h"
#include "zm_ffmpeg.h"
#include "zm_thread.h"

#include <sys/time.h>
#include <stdint.h>
#include <string>

#if HAVE_LIBAVCODEC

struct RtpDataHeader;

class RtpSource
{
public: 
  typedef enum { EMPTY, FILLING, READY } FrameState;
private:
  static const int RTP_SEQ_MOD = 1<<16;
  static const int MAX_DROPOUT = 3000;
  static const int MAX_MISORDER = 100;
  static const int MIN_SEQUENTIAL = 2;

private:
  // Identity
  int mId;         // General id (usually monitor id)
  std::string mCname;    // Canonical name, for SDES

  // RTP/RTCP fields
  uint32_t mSsrc;
  uint16_t mMaxSeq;       // highest seq. number seen
  uint32_t mCycles;       // shifted count of seq. number cycles
  uint32_t mBaseSeq;      // base seq number
  uint32_t mBadSeq;       // last 'bad' seq number + 1
  uint32_t mProbation;      // sequ. packets till source is valid
  uint32_t mReceivedPackets;  // packets received
  uint32_t mExpectedPrior;    // packet expected at last interval
  uint32_t mReceivedPrior;    // packet received at last interval
  uint32_t mTransit;      // relative trans time for prev pkt
  uint32_t mJitter;       // estimated jitter
  
  // Ports/Channels
  std::string mLocalHost;
  int mLocalPortChans[2]; 
  std::string mRemoteHost;
  int mRemotePortChans[2]; 

  // Time keys
  uint32_t mRtpClock;
  uint32_t mRtpFactor;
  struct timeval mBaseTimeReal;
  struct timeval mBaseTimeNtp;
  uint32_t mBaseTimeRtp;

  struct timeval mLastSrTimeReal;
  uint32_t mLastSrTimeNtpSecs;
  uint32_t mLastSrTimeNtpFrac;
  struct timeval mLastSrTimeNtp;
  uint32_t mLastSrTimeRtp;

  // Stats, intermittently updated
  uint32_t mExpectedPackets;
  uint32_t mLostPackets;
  uint8_t  mLostFraction;

  _AVCODECID mCodecId;

  Buffer mFrame;
  int mFrameCount;
  bool mFrameGood;
  bool prevM;
  ThreadData<bool> mFrameReady;
  ThreadData<bool> mFrameProcessed;

private:
  void init(uint16_t seq);

public:
  RtpSource( int id, const std::string &localHost, int localPortBase, const std::string &remoteHost, int remotePortBase, uint32_t ssrc, uint16_t seq, uint32_t rtpClock, uint32_t rtpTime, _AVCODECID codecId );
  
  bool updateSeq( uint16_t seq );
  void updateJitter( const RtpDataHeader *header );
  void updateRtcpData( uint32_t ntpTimeSecs, uint32_t ntpTimeFrac, uint32_t rtpTime );
  void updateRtcpStats();

  bool handlePacket( const unsigned char *packet, size_t packetLen );

  uint32_t getSsrc() const
  {
    return( mSsrc );
  }
  void setSsrc( uint32_t ssrc )
  {
    mSsrc = ssrc;
  }

  bool getFrame( Buffer &buffer );

  const std::string &getCname() const
  {
    return( mCname );
  }

  const std::string &getLocalHost() const
  {
    return( mLocalHost );
  }

  int getLocalDataPort() const
  {
    return( mLocalPortChans[0] );
  }

  int getLocalCtrlPort() const
  {
    return( mLocalPortChans[1] );
  }

  const std::string &getRemoteHost() const
  {
    return( mRemoteHost );
  }

  int getRemoteDataPort() const
  {
    return( mRemotePortChans[0] );
  }

  int getRemoteCtrlPort() const
  {
    return( mRemotePortChans[1] );
  }

  uint32_t getMaxSeq() const
  {
    return( mCycles + mMaxSeq );
  }

  uint32_t getExpectedPackets() const
  {
    return( mExpectedPackets );
  }
  
  uint32_t getLostPackets() const
  {
    return( mLostPackets );
  }

  uint8_t getLostFraction() const
  {
    return( mLostFraction );
  }

  uint32_t getJitter() const
  {
    return( mJitter >> 4 );
  }

  uint32_t getLastSrTimestamp() const
  {
    return( ((mLastSrTimeNtpSecs&0xffff)<<16)|(mLastSrTimeNtpFrac>>16) );
  }
};

#endif // HAVE_LIBAVCODEC

#endif // ZM_RTP_SOURCE_H
