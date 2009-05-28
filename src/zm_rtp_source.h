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
// Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
// 

#ifndef ZM_RTP_SOURCE_H
#define ZM_RTP_SOURCE_H

#include "zm_buffer.h"
#include "zm_thread.h"

#include <sys/time.h>
#include <string>

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
    int mId;                 // General id (usually monitor id)
    std::string mCname;      // Canonical name, for SDES

    // RTP/RTCP fields
    U32 mSsrc;
    U16 mMaxSeq;             // highest seq. number seen
    U32 mCycles;             // shifted count of seq. number cycles
    U32 mBaseSeq;            // base seq number
    U32 mBadSeq;             // last 'bad' seq number + 1
    U32 mProbation;          // sequ. packets till source is valid
    U32 mReceivedPackets;    // packets received
    U32 mExpectedPrior;      // packet expected at last interval
    U32 mReceivedPrior;      // packet received at last interval
    U32 mTransit;            // relative trans time for prev pkt
    U32 mJitter;             // estimated jitter
    
    // Ports/Channels
    std::string mLocalHost;
    int mLocalPortChans[2]; 
    std::string mRemoteHost;
    int mRemotePortChans[2]; 

    // Time keys
    U32 mRtpClock;
    U32 mRtpFactor;
    struct timeval mBaseTimeReal;
    struct timeval mBaseTimeNtp;
    U32 mBaseTimeRtp;

    struct timeval mLastSrTimeReal;
    U32 mLastSrTimeNtpSecs;
    U32 mLastSrTimeNtpFrac;
    struct timeval mLastSrTimeNtp;
    U32 mLastSrTimeRtp;

    // Stats, intermittently updated
    U32 mExpectedPackets;
    U32 mLostPackets;
    U8  mLostFraction;

    Buffer mFrame;
    int mFrameCount;
    bool mFrameGood;
    ThreadData<bool> mFrameReady;
    ThreadData<bool> mFrameProcessed;

private:
    void init( U16 seq );

public:
    RtpSource( int id, const std::string &localHost, int localPortBase, const std::string &remoteHost, int remotePortBase, U32 ssrc, U16 seq, U32 rtpClock, U32 rtpTime );
    bool updateSeq( U16 seq );
    void updateJitter( const RtpDataHeader *header );
    void updateRtcpData( U32 ntpTimeSecs, U32 ntpTimeFrac, U32 rtpTime );
    void updateRtcpStats();

    bool handlePacket( const unsigned char *packet, size_t packetLen );

    U32 getSsrc() const
    {
        return( mSsrc );
    }
    void setSsrc( U32 ssrc )
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

    U32 getMaxSeq() const
    {
        return( mCycles + mMaxSeq );
    }

    U32 getExpectedPackets() const
    {
        return( mExpectedPackets );
    }
    
    U32 getLostPackets() const
    {
        return( mLostPackets );
    }

    U8 getLostFraction() const
    {
        return( mLostFraction );
    }

    U32 getJitter() const
    {
        return( mJitter >> 4 );
    }

    U32 getLastSrTimestamp() const
    {
        return( ((mLastSrTimeNtpSecs&0xffff)<<16)|(mLastSrTimeNtpFrac>>16) );
    }
};

#endif // ZM_RTP_SOURCE_H
