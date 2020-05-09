//
// ZoneMinder RTCP Class Implementation, $Date$, $Revision$
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

#include "zm.h"

#if HAVE_LIBAVFORMAT

#include "zm_rtp_ctrl.h"

#include "zm_time.h"
#include "zm_rtsp.h"

#include <errno.h>

RtpCtrlThread::RtpCtrlThread( RtspThread &rtspThread, RtpSource &rtpSource )
  : mRtspThread( rtspThread ), mRtpSource( rtpSource ), mStop( false )
{
}

int RtpCtrlThread::recvPacket( const unsigned char *packet, ssize_t packetLen ) {
  const RtcpPacket *rtcpPacket;
  rtcpPacket = (RtcpPacket *)packet;

  int consumed = 0;

  //printf( "C: " );
  //for ( int i = 0; i < packetLen; i++ )
    //printf( "%02x ", (unsigned char)packet[i] );
  //printf( "\n" );
  int ver = rtcpPacket->header.version;
  int count = rtcpPacket->header.count;
  int pt = rtcpPacket->header.pt;
  int len = ntohs(rtcpPacket->header.lenN);

  Debug( 5, "RTCP Ver: %d Count: %d Pt: %d len: %d", ver, count, pt, len);

  switch( pt ) {
    case RTCP_SR :
    {
      uint32_t ssrc = ntohl(rtcpPacket->body.sr.ssrcN);

      Debug( 5, "RTCP Got SR (%x)", ssrc );
      if ( mRtpSource.getSsrc() ) {
        if ( ssrc != mRtpSource.getSsrc() ) {
          Warning( "Discarding packet for unrecognised ssrc %x", ssrc );
          return( -1 );
        }
      } else if ( ssrc ) {
        mRtpSource.setSsrc( ssrc );
      }

      if ( len > 1 ) {
        //printf( "NTPts:%d.%d, RTPts:%d\n", $ntptsmsb, $ntptslsb, $rtpts );
        uint16_t ntptsmsb = ntohl(rtcpPacket->body.sr.ntpSecN);
        uint16_t ntptslsb = ntohl(rtcpPacket->body.sr.ntpFracN);
        //printf( "NTPts:%x.%04x, RTPts:%x\n", $ntptsmsb, $ntptslsb, $rtpts );
        //printf( "Pkts:$sendpkts, Octs:$sendocts\n" );
        uint32_t rtpTime = ntohl(rtcpPacket->body.sr.rtpTsN);

        mRtpSource.updateRtcpData( ntptsmsb, ntptslsb, rtpTime );
      }
      break;
    }
    case RTCP_SDES :
    {
      ssize_t contentLen = packetLen - sizeof(rtcpPacket->header);
      while ( contentLen ) {
        Debug( 5, "RTCP CL: %zd", contentLen );
        uint32_t ssrc = ntohl(rtcpPacket->body.sdes.srcN);

        Debug( 5, "RTCP Got SDES (%x), %d items", ssrc, count );
        if ( mRtpSource.getSsrc() && (ssrc != mRtpSource.getSsrc()) ) {
          Warning( "Discarding packet for unrecognised ssrc %x", ssrc );
          return( -1 );
        }

        unsigned char *sdesPtr = (unsigned char *)&rtcpPacket->body.sdes.item;
        for ( int i = 0; i < count; i++ ) {
          RtcpSdesItem *item = (RtcpSdesItem *)sdesPtr;
          Debug( 5, "RTCP Item length %d", item->len );
          switch( item->type ) {
            case RTCP_SDES_CNAME :
            {
              std::string cname( item->data, item->len );
              Debug( 5, "RTCP Got CNAME %s", cname.c_str() );
              break;
            }
            case RTCP_SDES_END :
            case RTCP_SDES_NAME :
            case RTCP_SDES_EMAIL :
            case RTCP_SDES_PHONE :
            case RTCP_SDES_LOC :
            case RTCP_SDES_TOOL :
            case RTCP_SDES_NOTE :
            case RTCP_SDES_PRIV :
            default :
              Error( "Received unexpected SDES item type %d, ignoring", item->type );
              return -1;
          }
          int paddedLen = 4+2+item->len+1; // Add null byte
          paddedLen = (((paddedLen-1)/4)+1)*4; // Round to nearest multiple of 4
          Debug(5, "RTCP PL:%d", paddedLen);
          sdesPtr += paddedLen;
          contentLen = ( paddedLen <= contentLen ) ? ( contentLen - paddedLen ) : 0;
        }
      } // end whiel contentLen
      break;
    }
    case RTCP_BYE :
      Debug(5, "RTCP Got BYE");
      mStop = true;
      break;
    case RTCP_APP :
      // Ignoring as per RFC 3550
      Debug(5, "Received RTCP_APP packet, ignoring.");
      break;
    case RTCP_RR :
      Error("Received RTCP_RR packet.");
      return -1;
    default :
      // Ignore unknown packet types. Some cameras do this by design.
      Debug(5, "Received unexpected packet type %d, ignoring", pt);
      break;
  }
  consumed = sizeof(uint32_t)*(len+1);
  return consumed;
}

int RtpCtrlThread::generateRr( const unsigned char *packet, ssize_t packetLen ) {
  RtcpPacket *rtcpPacket = (RtcpPacket *)packet;

  int byteLen = sizeof(rtcpPacket->header)+sizeof(rtcpPacket->body.rr)+sizeof(rtcpPacket->body.rr.rr[0]);
  int wordLen = ((byteLen-1)/sizeof(uint32_t))+1;

  rtcpPacket->header.version = RTP_VERSION;
  rtcpPacket->header.p = 0;
  rtcpPacket->header.pt = RTCP_RR;
  rtcpPacket->header.count = 1;
  rtcpPacket->header.lenN = htons(wordLen-1);

  mRtpSource.updateRtcpStats();

  Debug(5, "Ssrc = %d Ssrc_1 = %d Last Seq = %d Jitter = %d Last SR = %d",
      mRtspThread.getSsrc()+1,
      mRtpSource.getSsrc(),
      mRtpSource.getMaxSeq(),
      mRtpSource.getJitter(),
      mRtpSource.getLastSrTimestamp()
      );

  rtcpPacket->body.rr.ssrcN = htonl(mRtspThread.getSsrc()+1);
  rtcpPacket->body.rr.rr[0].ssrcN = htonl(mRtpSource.getSsrc());
  rtcpPacket->body.rr.rr[0].lost = mRtpSource.getLostPackets();
  rtcpPacket->body.rr.rr[0].fraction = mRtpSource.getLostFraction();
  rtcpPacket->body.rr.rr[0].lastSeqN = htonl(mRtpSource.getMaxSeq());
  rtcpPacket->body.rr.rr[0].jitterN = htonl(mRtpSource.getJitter());
  rtcpPacket->body.rr.rr[0].lsrN = htonl(mRtpSource.getLastSrTimestamp());
  rtcpPacket->body.rr.rr[0].dlsrN = 0;

  return wordLen*sizeof(uint32_t);
} // end RtpCtrlThread::generateRr

int RtpCtrlThread::generateSdes( const unsigned char *packet, ssize_t packetLen ) {
  RtcpPacket *rtcpPacket = (RtcpPacket *)packet;

  const std::string &cname = mRtpSource.getCname();

  int byteLen = sizeof(rtcpPacket->header)+sizeof(rtcpPacket->body.sdes)+sizeof(rtcpPacket->body.sdes.item[0])+cname.size();
  int wordLen = ((byteLen-1)/sizeof(uint32_t))+1;

  rtcpPacket->header.version = RTP_VERSION;
  rtcpPacket->header.p = 0;
  rtcpPacket->header.pt = RTCP_SDES;
  rtcpPacket->header.count = 1;
  rtcpPacket->header.lenN = htons(wordLen-1);

  rtcpPacket->body.sdes.srcN = htonl(mRtpSource.getSsrc()+1);
  rtcpPacket->body.sdes.item[0].type = RTCP_SDES_CNAME;
  rtcpPacket->body.sdes.item[0].len = cname.size();
  memcpy( rtcpPacket->body.sdes.item[0].data, cname.data(), cname.size() );

  return wordLen*sizeof(uint32_t);
} // end RtpCtrlThread::generateSdes

int RtpCtrlThread::generateBye( const unsigned char *packet, ssize_t packetLen ) {
  RtcpPacket *rtcpPacket = (RtcpPacket *)packet;

  int byteLen = sizeof(rtcpPacket->header)+sizeof(rtcpPacket->body.bye)+sizeof(rtcpPacket->body.bye.srcN[0]);
  int wordLen = ((byteLen-1)/sizeof(uint32_t))+1;

  rtcpPacket->header.version = RTP_VERSION;
  rtcpPacket->header.p = 0;
  rtcpPacket->header.pt = RTCP_BYE;
  rtcpPacket->header.count = 1;
  rtcpPacket->header.lenN = htons(wordLen-1);

  rtcpPacket->body.bye.srcN[0] = htonl(mRtpSource.getSsrc());

  return wordLen*sizeof(uint32_t);
} // end RtpCtrolThread::generateBye

int RtpCtrlThread::recvPackets( unsigned char *buffer, ssize_t nBytes ) {
  unsigned char *bufferPtr = buffer;

  // u_int32 len;    /* length of compound RTCP packet in words */
  // rtcp_t *r;      /* RTCP header */
  // rtcp_t *end;    /* end of compound RTCP packet */

  // if ((*(u_int16 *)r & RTCP_VALID_MASK) != RTCP_VALID_VALUE) {
    // /* something wrong with packet format */
  // }
  // end = (rtcp_t *)((u_int32 *)r + len);

  // do r = (rtcp_t *)((u_int32 *)r + r->common.length + 1);
  // while (r < end && r->common.version == 2);

  // if (r != end) {
    // /* something wrong with packet format */
  // }

  while ( nBytes > 0 ) {
    int consumed = recvPacket( bufferPtr, nBytes );
    if ( consumed <= 0 )
      break;
    bufferPtr += consumed;
    nBytes -= consumed;
  }
  return nBytes;
}

int RtpCtrlThread::run() {
  Debug( 2, "Starting control thread %x on port %d", mRtpSource.getSsrc(), mRtpSource.getLocalCtrlPort() );
  SockAddrInet localAddr, remoteAddr;

  bool sendReports;
  UdpInetSocket rtpCtrlServer;
  if ( mRtpSource.getLocalHost() != "" ) {
    if ( !rtpCtrlServer.bind( mRtpSource.getLocalHost().c_str(), mRtpSource.getLocalCtrlPort() ) )
      Fatal( "Failed to bind RTCP server" );
    sendReports = false;
    Debug( 3, "Bound to %s:%d",  mRtpSource.getLocalHost().c_str(), mRtpSource.getLocalCtrlPort() );
  } else {
    if ( !rtpCtrlServer.bind( mRtspThread.getAddressFamily() == AF_INET6 ? "::" : "0.0.0.0", mRtpSource.getLocalCtrlPort() ) )
      Fatal( "Failed to bind RTCP server" );
    Debug( 3, "Bound to %s:%d",  mRtpSource.getLocalHost().c_str(), mRtpSource.getLocalCtrlPort() );
    if ( !rtpCtrlServer.connect( mRtpSource.getRemoteHost().c_str(), mRtpSource.getRemoteCtrlPort() ) )
      Fatal( "Failed to connect RTCP server" );
    Debug( 3, "Connected to %s:%d",  mRtpSource.getRemoteHost().c_str(), mRtpSource.getRemoteCtrlPort() );
    sendReports = true;
  }

  // The only reason I can think of why we would have a timeout period is so that we can regularly send RR packets.
  // Why 10 seconds? If anything I think this should be whatever timeout value was given in the DESCRIBE response
  Select select( 10 );
  select.addReader( &rtpCtrlServer );

  unsigned char buffer[ZM_NETWORK_BUFSIZ];

  time_t  last_receive = time(NULL);
  bool  timeout = false; // used as a flag that we had a timeout, and then sent an RR to see if we wake back up. Real timeout will happen when this is true.

  while ( !mStop && select.wait() >= 0 ) {

    time_t now = time(NULL);
    Select::CommsList readable = select.getReadable();
    if ( readable.size() == 0 ) {
      if ( ! timeout ) {
        // With this code here, we will send an SDES and RR packet every 10 seconds
        ssize_t nBytes;
        unsigned char *bufferPtr = buffer;
        bufferPtr += generateRr( bufferPtr, sizeof(buffer)-(bufferPtr-buffer) );
        bufferPtr += generateSdes( bufferPtr, sizeof(buffer)-(bufferPtr-buffer) );
        Debug( 3, "Preventing timeout by sending %zd bytes on sd %d. Time since last receive: %d",
            bufferPtr-buffer, rtpCtrlServer.getWriteDesc(), ( now-last_receive) );
        if ( (nBytes = rtpCtrlServer.send(buffer, bufferPtr-buffer)) < 0 )
          Error("Unable to send: %s", strerror(errno));
        timeout = true;
        continue;
      } else {
        //Error( "RTCP timed out" );
        Debug(1, "RTCP timed out. Time since last receive: %d", ( now-last_receive) );
        continue;
        //break;
      }
    } else {
      timeout = false;
      last_receive = time(NULL);
    }
    for ( Select::CommsList::iterator iter = readable.begin(); iter != readable.end(); ++iter ) {
      if ( UdpInetSocket *socket = dynamic_cast<UdpInetSocket *>(*iter) ) {
        ssize_t nBytes = socket->recv( buffer, sizeof(buffer) );
        Debug( 4, "Read %zd bytes on sd %d", nBytes, socket->getReadDesc() );

        if ( nBytes ) {
          recvPackets( buffer, nBytes );

          if ( sendReports ) {
            unsigned char *bufferPtr = buffer;
            bufferPtr += generateRr( bufferPtr, sizeof(buffer)-(bufferPtr-buffer) );
            bufferPtr += generateSdes( bufferPtr, sizeof(buffer)-(bufferPtr-buffer) );
            Debug(3, "Sending %zd bytes on sd %d", bufferPtr-buffer, rtpCtrlServer.getWriteDesc());
            if ( (nBytes = rtpCtrlServer.send( buffer, bufferPtr-buffer )) < 0 )
              Error("Unable to send: %s", strerror(errno));
            //Debug( 4, "Sent %d bytes on sd %d", nBytes, rtpCtrlServer.getWriteDesc() );
          }
        } else {
          // Here is another case of not receiving some data causing us to terminate... why?  Sometimes there are pauses in the interwebs.
          mStop = true;
          break;
        }
      } else {
        Panic("Barfed");
      } // end if socket
    } // end foeach comms iterator
  }
  rtpCtrlServer.close();
  mRtspThread.stop();
  return 0;
}

#endif // HAVE_LIBAVFORMAT
