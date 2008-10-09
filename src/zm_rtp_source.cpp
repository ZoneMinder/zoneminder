//
// ZoneMinder RTP Source Class Implementation, $Date$, $Revision$
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

#include "zm_rtp_source.h"

#include "zm_time.h"
#include "zm_rtp_data.h"

#include <arpa/inet.h>

RtpSource::RtpSource( int id, const std::string &localHost, int localPortBase, const std::string &remoteHost, int remotePortBase, U32 ssrc, U16 seq, U32 rtpClock, U32 rtpTime ) :
    mId( id ),
    mSsrc( ssrc ),
    mLocalHost( localHost ),
    mRemoteHost( remoteHost ),
    mRtpClock( rtpClock ),
    mFrame( 65536 ),
    mFrameCount( 0 ),
    mFrameGood( true ),
    mFrameReady( false ),
    mFrameProcessed( false )
{
    char hostname[256] = "";
    gethostname( hostname, sizeof(hostname) );

    mCname = stringtf( "zm-%d@%s", mId, hostname );
    Debug( 3, "RTP CName = %s", mCname.c_str() );

    init( seq );
    mMaxSeq = seq - 1;
    mProbation = MIN_SEQUENTIAL;

    mLocalPortChans[0] = localPortBase;
    mLocalPortChans[1] = localPortBase+1;

    mRemotePortChans[0] = remotePortBase;
    mRemotePortChans[1] = remotePortBase+1;

    mRtpFactor = mRtpClock;

    mBaseTimeReal = tvNow();
    mBaseTimeNtp = tvZero();
    mBaseTimeRtp = rtpTime;

    mLastSrTimeReal = tvZero();
    mLastSrTimeNtp = tvZero();
    mLastSrTimeRtp = 0;
}

void RtpSource::init( U16 seq )
{
    Debug( 3, "Initialising sequence" );
    mBaseSeq = seq;
    mMaxSeq = seq;
    mBadSeq = RTP_SEQ_MOD + 1;  // so seq == mBadSeq is false
    mCycles = 0;
    mReceivedPackets = 0;
    mReceivedPrior = 0;
    mExpectedPrior = 0;
    // other initialization
    mJitter = 0;
    mTransit = 0;
}

bool RtpSource::updateSeq( U16 seq )
{
    U16 uDelta = seq - mMaxSeq;

    // Source is not valid until MIN_SEQUENTIAL packets with
    // sequential sequence numbers have been received.
    Debug( 5, "Seq: %d", seq );

    if ( mProbation)
    {
        // packet is in sequence
        if ( seq == mMaxSeq + 1)
        {
            Debug( 3, "Sequence in probation %ld, in sequence", mProbation );
            mProbation--;
            mMaxSeq = seq;
            if ( mProbation == 0 )
            {
                init( seq );
                mReceivedPackets++;
                return( true );
            }
        }
        else
        {
            Warning( "Sequence in probation %ld, out of sequence", mProbation );
            mProbation = MIN_SEQUENTIAL - 1;
            mMaxSeq = seq;
            return( false );
        }
        return( true );
    }
    else if ( uDelta < MAX_DROPOUT )
    {
        if ( uDelta == 1 )
        {
            Debug( 3, "Packet in sequence, gap %d", uDelta );
        }
        else
        {
            Warning( "Packet in sequence, gap %d", uDelta );
        }

        // in order, with permissible gap
        if ( seq < mMaxSeq )
        {
            // Sequence number wrapped - count another 64K cycle.
            mCycles += RTP_SEQ_MOD;
        }
        mMaxSeq = seq;
    }
    else if ( uDelta <= RTP_SEQ_MOD - MAX_MISORDER )
    {
        Warning( "Packet out of sequence, gap %d", uDelta );
        // the sequence number made a very large jump
        if ( seq == mBadSeq )
        {
            Debug( 3, "Restarting sequence" );
            // Two sequential packets -- assume that the other side
            // restarted without telling us so just re-sync
            // (i.e., pretend this was the first packet).
            init( seq );
        }
        else
        {
            mBadSeq = (seq + 1) & (RTP_SEQ_MOD-1);
            return( false );
        }
    }
    else
    {
        Warning( "Packet duplicate or reordered, gap %d", uDelta );
        // duplicate or reordered packet
        return( false );
    }
    mReceivedPackets++;
    return( uDelta==1?true:false );
}

void RtpSource::updateJitter( const RtpDataHeader *header )
{
    if ( mRtpFactor > 0 )
    {
        Debug( 5, "Delta rtp = %.6f", tvDiffSec( mBaseTimeReal ) );
        U32 localTimeRtp = mBaseTimeRtp + U32( tvDiffSec( mBaseTimeReal ) * mRtpFactor );
        Debug( 5, "Local RTP time = %lx", localTimeRtp );
        Debug( 5, "Packet RTP time = %x", ntohl(header->timestampN) );
        U32 packetTransit = localTimeRtp - ntohl(header->timestampN);
        Debug( 5, "Packet transit RTP time = %lx", packetTransit );

        if ( mTransit > 0 )
        {
            // Jitter
            int d = packetTransit - mTransit;
            Debug( 5, "Jitter D = %d", d );
            if ( d < 0 )
                d = -d;
            //mJitter += (1./16.) * ((double)d - mJitter);
            mJitter += d - ((mJitter + 8) >> 4);
        }
        mTransit = packetTransit;
    }
    else
    {
        mJitter = 0;
    }
    Debug( 5, "RTP Jitter: %ld", mJitter );
}

void RtpSource::updateRtcpData( U32 ntpTimeSecs, U32 ntpTimeFrac, U32 rtpTime )
{
    struct timeval ntpTime = tvMake( ntpTimeSecs, suseconds_t((USEC_PER_SEC*(ntpTimeFrac>>16))/(1<<16)) );

    Debug( 5, "ntpTime: %ld.%06ld, rtpTime: %lx", ntpTime.tv_sec, ntpTime.tv_usec, rtpTime );
                                                     
    if ( mBaseTimeNtp.tv_sec == 0 )
    {
        mBaseTimeReal = tvNow();
        mBaseTimeNtp = ntpTime;
        mBaseTimeRtp = rtpTime;
    }
    else if ( !mRtpClock )
    {
        Debug( 5, "lastSrNtpTime: %ld.%06ld, rtpTime: %lx", mLastSrTimeNtp.tv_sec, mLastSrTimeNtp.tv_usec, rtpTime );
        Debug( 5, "ntpTime: %ld.%06ld, rtpTime: %lx", ntpTime.tv_sec, ntpTime.tv_usec, rtpTime );

        double diffNtpTime = tvDiffSec( mBaseTimeNtp, ntpTime );
        U32 diffRtpTime = rtpTime - mBaseTimeRtp;

        //Debug( 5, "Real-diff: %.6f", diffRealTime );
        Debug( 5, "NTP-diff: %.6f", diffNtpTime );
        Debug( 5, "RTP-diff: %ld", diffRtpTime );

        mRtpFactor = (U32)(diffRtpTime / diffNtpTime);

        Debug( 5, "RTPfactor: %ld", mRtpFactor );
    }
    mLastSrTimeNtpSecs = ntpTimeSecs;
    mLastSrTimeNtpFrac = ntpTimeFrac;
    mLastSrTimeNtp = ntpTime;
    mLastSrTimeRtp = rtpTime;
}

void RtpSource::updateRtcpStats()
{
    U32 extendedMax = mCycles + mMaxSeq;
    mExpectedPackets = extendedMax - mBaseSeq + 1;

    Debug( 5, "Expected packets = %ld", mExpectedPackets );

    // The number of packets lost is defined to be the number of packets
    // expected less the number of packets actually received:
    mLostPackets = mExpectedPackets - mReceivedPackets;
    Debug( 5, "Lost packets = %ld", mLostPackets );

    U32 expectedInterval = mExpectedPackets - mExpectedPrior;
    Debug( 5, "Expected interval = %ld", expectedInterval );
    mExpectedPrior = mExpectedPackets;
    U32 receivedInterval = mReceivedPackets - mReceivedPrior;
    Debug( 5, "Received interval = %ld", receivedInterval );
    mReceivedPrior = mReceivedPackets;
    U32 lostInterval = expectedInterval - receivedInterval;
    Debug( 5, "Lost interval = %ld", lostInterval );

    if ( expectedInterval == 0 || lostInterval <= 0 )
        mLostFraction = 0;
    else
        mLostFraction = (lostInterval << 8) / expectedInterval;
    Debug( 5, "Lost fraction = %d", mLostFraction );
}

bool RtpSource::handlePacket( const unsigned char *packet, size_t packetLen )
{
    const RtpDataHeader *rtpHeader;
    rtpHeader = (RtpDataHeader *)packet;

    if ( updateSeq( ntohs(rtpHeader->seqN) ) )
    {
        Hexdump( 4, packet+sizeof(RtpDataHeader), 16 );
        if ( mFrameGood )
            mFrame.append( packet+sizeof(RtpDataHeader), packetLen-sizeof(RtpDataHeader) ); 
        Hexdump( 4, mFrame.head(), 16 );

        if ( rtpHeader->m )
        {
            if ( mFrameGood )
            {
                Debug( 2, "Got new frame %d, %d bytes", mFrameCount, mFrame.size() );

                mFrameProcessed.setValueImmediate( false );
                mFrameReady.updateValueSignal( true );
                if ( !mFrameProcessed.getValueImmediate() )
                {
                    for ( int count = 0; !mFrameProcessed.getUpdatedValue( 1 ); count++ )
                        if( count > 1 )
                            return( false );
                }
                mFrameCount++;
            }
            else
            {
                Warning( "Discarding incomplete frame %d, %d bytes", mFrameCount, mFrame.size() );
            }
            mFrame.clear();
        }
    }
    else
    {
        if ( mFrame.size() )
        {
            Warning( "Discarding partial frame %d, %d bytes", mFrameCount, mFrame.size() );
        }
        else
        {
            Warning( "Discarding frame %d", mFrameCount );
        }
        mFrameGood = false;
        mFrame.clear();
    }
    if ( rtpHeader->m )
    {
        mFrameGood = true;
    }

    updateJitter( rtpHeader );

    return( true );
}

bool RtpSource::getFrame( Buffer &buffer )
{
    Debug( 3, "Getting frame" );
    if ( !mFrameReady.getValueImmediate() )
    {
        // Allow for a couple of spurious returns
        for ( int count = 0; !mFrameReady.getUpdatedValue( 1 ); count++ )
            if ( count > 1 )
                return( false );
    }
    buffer = mFrame;
    mFrameReady.setValueImmediate( false );
    mFrameProcessed.updateValueSignal( true );
    Debug( 3, "Copied %d bytes", buffer.size() );
    return( true );
}
