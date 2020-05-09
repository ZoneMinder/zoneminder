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
// Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
// 

#include "zm_rtp_source.h"

#include "zm_time.h"
#include "zm_rtp_data.h"

#include <arpa/inet.h>

#if HAVE_LIBAVCODEC

RtpSource::RtpSource(
    int id,
    const std::string &localHost,
    int localPortBase,
    const std::string &remoteHost,
    int remotePortBase,
    uint32_t ssrc,
    uint16_t seq,
    uint32_t rtpClock,
    uint32_t rtpTime,
    _AVCODECID codecId ) :
  mId(id),
  mSsrc(ssrc),
  mLocalHost(localHost),
  mRemoteHost(remoteHost),
  mRtpClock(rtpClock),
  mCodecId(codecId),
  mFrame(65536),
  mFrameCount(0),
  mFrameGood(true),
  mFrameReady(false),
  mFrameProcessed(false)
{
  char hostname[256] = "";
  gethostname(hostname, sizeof(hostname));

  mCname = stringtf("zm-%d@%s", mId, hostname);
  Debug(3, "RTP CName = %s", mCname.c_str());

  init(seq);
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

  if ( mCodecId != AV_CODEC_ID_H264 && mCodecId != AV_CODEC_ID_MPEG4 )
    Warning("The device is using a codec (%d) that may not be supported. Do not be surprised if things don't work.", mCodecId);
}

void RtpSource::init(uint16_t seq) {
  Debug(3, "Initialising sequence");
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

bool RtpSource::updateSeq(uint16_t seq) {
  uint16_t uDelta = seq - mMaxSeq;

  // Source is not valid until MIN_SEQUENTIAL packets with
  // sequential sequence numbers have been received.
  Debug(5, "Seq: %d", seq);

  if ( mProbation) {
    // packet is in sequence
    if ( seq == mMaxSeq + 1 ) {
      Debug(3, "Sequence in probation %d, in sequence", mProbation);
      mProbation--;
      mMaxSeq = seq;
      if ( mProbation == 0 ) {
        init(seq);
        mReceivedPackets++;
        return true;
      }
    } else {
      Warning("Sequence in probation %d, out of sequence", mProbation);
      mProbation = MIN_SEQUENTIAL - 1;
      mMaxSeq = seq;
      return false;
    }
    return true;
  } else if ( uDelta < MAX_DROPOUT ) {
    if ( uDelta == 1 ) {
      Debug(4, "Packet in sequence, gap %d", uDelta);
    } else {
      Warning("Packet in sequence, gap %d", uDelta);
    }

    // in order, with permissible gap
    if ( seq < mMaxSeq ) {
      // Sequence number wrapped - count another 64K cycle.
      mCycles += RTP_SEQ_MOD;
    }
    mMaxSeq = seq;
  } else if ( uDelta <= RTP_SEQ_MOD - MAX_MISORDER ) {
    Warning("Packet out of sequence, gap %d", uDelta);
    // the sequence number made a very large jump
    if ( seq == mBadSeq ) {
      Debug(3, "Restarting sequence");
      // Two sequential packets -- assume that the other side
      // restarted without telling us so just re-sync
      // (i.e., pretend this was the first packet).
      init(seq);
    } else {
      mBadSeq = (seq + 1) & (RTP_SEQ_MOD-1);
      return false;
    }
  } else {
    Warning("Packet duplicate or reordered, gap %d", uDelta);
    // duplicate or reordered packet
    return false;
  }
  mReceivedPackets++;
  return( uDelta==1?true:false );
}

void RtpSource::updateJitter( const RtpDataHeader *header ) {
  if ( mRtpFactor > 0 ) {
    uint32_t localTimeRtp = mBaseTimeRtp + uint32_t(tvDiffSec(mBaseTimeReal) * mRtpFactor);
    uint32_t packetTransit = localTimeRtp - ntohl(header->timestampN);

    Debug(5, "Delta rtp = %.6f\n"
        "Local RTP time = %x",
        "Packet RTP time = %x",
        "Packet transit RTP time = %x",
        tvDiffSec(mBaseTimeReal),
        localTimeRtp,
        ntohl(header->timestampN),
        packetTransit);

    if ( mTransit > 0 ) {
      // Jitter
      int d = packetTransit - mTransit;
      Debug(5, "Jitter D = %d", d);
      if ( d < 0 )
        d = -d;
      //mJitter += (1./16.) * ((double)d - mJitter);
      mJitter += d - ((mJitter + 8) >> 4);
    }
    mTransit = packetTransit;
  } else {
    mJitter = 0;
  }
  Debug(5, "RTP Jitter: %d", mJitter);
}

void RtpSource::updateRtcpData(
    uint32_t ntpTimeSecs,
    uint32_t ntpTimeFrac,
    uint32_t rtpTime) {
  struct timeval ntpTime = tvMake(ntpTimeSecs, suseconds_t((USEC_PER_SEC*(ntpTimeFrac>>16))/(1<<16)));

  Debug(5, "ntpTime: %ld.%06ld, rtpTime: %x", ntpTime.tv_sec, ntpTime.tv_usec, rtpTime);

  if ( mBaseTimeNtp.tv_sec == 0 ) {
    mBaseTimeReal = tvNow();
    mBaseTimeNtp = ntpTime;
    mBaseTimeRtp = rtpTime;
  } else if ( !mRtpClock ) {
    Debug(5, "lastSrNtpTime: %ld.%06ld, rtpTime: %x"
        "ntpTime: %ld.%06ld, rtpTime: %x",
        mLastSrTimeNtp.tv_sec, mLastSrTimeNtp.tv_usec, rtpTime,
        ntpTime.tv_sec, ntpTime.tv_usec, rtpTime);

    double diffNtpTime = tvDiffSec( mBaseTimeNtp, ntpTime );
    uint32_t diffRtpTime = rtpTime - mBaseTimeRtp;
    mRtpFactor = (uint32_t)(diffRtpTime / diffNtpTime);

    Debug( 5, "NTP-diff: %.6f RTP-diff: %d RTPfactor: %d",
        diffNtpTime, diffRtpTime, mRtpFactor);
  }
  mLastSrTimeNtpSecs = ntpTimeSecs;
  mLastSrTimeNtpFrac = ntpTimeFrac;
  mLastSrTimeNtp = ntpTime;
  mLastSrTimeRtp = rtpTime;
}

void RtpSource::updateRtcpStats() {
  uint32_t extendedMax = mCycles + mMaxSeq;
  mExpectedPackets = extendedMax - mBaseSeq + 1;
  // The number of packets lost is defined to be the number of packets
  // expected less the number of packets actually received:
  mLostPackets = mExpectedPackets - mReceivedPackets;
  uint32_t expectedInterval = mExpectedPackets - mExpectedPrior;
  mExpectedPrior = mExpectedPackets;
  uint32_t receivedInterval = mReceivedPackets - mReceivedPrior;
  mReceivedPrior = mReceivedPackets;
  uint32_t lostInterval = expectedInterval - receivedInterval;

  if ( expectedInterval == 0 || lostInterval <= 0 )
    mLostFraction = 0;
  else
    mLostFraction = (lostInterval << 8) / expectedInterval;

  Debug(5,
      "Expected packets = %d\n",
      "Lost packets = %d\n",
      "Expected interval = %d\n",
      "Received interval = %d\n",
      "Lost interval = %d\n",
      "Lost fraction = %d\n",

      mExpectedPackets,
      mLostPackets,
      expectedInterval,
      receivedInterval,
      lostInterval,
      mLostFraction);
}

bool RtpSource::handlePacket(const unsigned char *packet, size_t packetLen) {
  const RtpDataHeader *rtpHeader;
  rtpHeader = (RtpDataHeader *)packet;
  int rtpHeaderSize = 12 + rtpHeader->cc * 4;
  // No need to check for nal type as non fragmented packets already have 001 start sequence appended
  bool h264FragmentEnd = (mCodecId == AV_CODEC_ID_H264) && (packet[rtpHeaderSize+1] & 0x40);
  // M stands for Marker, it is the 8th bit
  // The interpretation of the marker is defined by a profile. It is intended
  // to allow significant events such as frame boundaries to be marked in the
  //  packet stream. A profile may define additional marker bits or specify
  //  that there is no marker bit by changing the number of bits in the payload type field.
  bool thisM = rtpHeader->m || h264FragmentEnd;

  if ( updateSeq(ntohs(rtpHeader->seqN)) ) {
    Hexdump(4, packet+rtpHeaderSize, 16);

    if ( mFrameGood ) {
      int extraHeader = 0;

      if ( mCodecId == AV_CODEC_ID_H264 ) {
        int nalType = (packet[rtpHeaderSize] & 0x1f);
        Debug(3, "Have H264 frame: nal type is %d", nalType);

        switch (nalType) {
          case 24: // STAP-A
              extraHeader = 2;
              break;
          case 25: // STAP-B
          case 26: // MTAP-16
          case 27: // MTAP-24
              extraHeader = 3;
              break;
            // FU-A and FU-B
          case 28: case 29:
              // Is this NAL the first NAL in fragmentation sequence
              if ( packet[rtpHeaderSize+1] & 0x80 ) {
                // Now we will form new header of frame
                mFrame.append( "\x0\x0\x1\x0", 4 );
                // Reconstruct NAL header from FU headers
                *(mFrame+3) = (packet[rtpHeaderSize+1] & 0x1f) |
                  (packet[rtpHeaderSize] & 0xe0);
              }

              extraHeader = 2;
              break;
          default: 
              Debug(3, "Unhandled nalType %d", nalType);
        }

        // Append NAL frame start code
        if ( !mFrame.size() )
          mFrame.append("\x0\x0\x1", 3);
      } // end if H264
      mFrame.append(packet+rtpHeaderSize+extraHeader,
          packetLen-rtpHeaderSize-extraHeader); 
    } else {
      Debug(3, "NOT H264 frame: type is %d", mCodecId);
    }

    Hexdump(4, mFrame.head(), 16);

    if ( thisM ) {
      if ( mFrameGood ) {
        Debug(3, "Got new frame %d, %d bytes", mFrameCount, mFrame.size());

        mFrameProcessed.setValueImmediate(false);
        mFrameReady.updateValueSignal(true);
        if ( !mFrameProcessed.getValueImmediate() ) {
          // What is the point of this for loop? Is it just me, or will it call getUpdatedValue once or twice? Could it not be better written as
          // if ( ! mFrameProcessed.getUpdatedValue( 1 ) && mFrameProcessed.getUpdatedValue( 1 ) ) return false;

          for ( int count = 0; !mFrameProcessed.getUpdatedValue( 1 ); count++ )
            if ( count > 1 )
              return false;
        }
        mFrameCount++;
      } else {
        Warning("Discarding incomplete frame %d, %d bytes", mFrameCount, mFrame.size());
      }
      mFrame.clear();
    }
  } else {
    if ( mFrame.size() ) {
      Warning("Discarding partial frame %d, %d bytes", mFrameCount, mFrame.size());
    } else {
      Warning("Discarding frame %d", mFrameCount);
    }
    mFrameGood = false;
    mFrame.clear();
  }
  if ( thisM ) {
    mFrameGood = true;
    prevM = true;
  } else
    prevM = false;

  updateJitter(rtpHeader);

  return true;
}

bool RtpSource::getFrame(Buffer &buffer) {
  if ( !mFrameReady.getValueImmediate() ) {
    Debug(3, "Getting frame but not ready");
    // Allow for a couple of spurious returns
    for ( int count = 0; !mFrameReady.getUpdatedValue(1); count++ )
      if ( count > 1 )
        return false;
  }
  buffer = mFrame;
  mFrameReady.setValueImmediate(false);
  mFrameProcessed.updateValueSignal(true);
  Debug(4, "Copied %d bytes", buffer.size());
  return true;
}

#endif // HAVE_LIBAVCODEC
