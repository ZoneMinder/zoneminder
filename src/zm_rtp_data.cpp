//
// ZoneMinder RTP Data Class Implementation, $Date$, $Revision$
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

#include "zm_rtp_data.h"

#include "zm_config.h"
#include "zm_rtsp.h"
#include "zm_signal.h"

RtpDataThread::RtpDataThread(RtspThread &rtspThread, RtpSource &rtpSource) :
  mRtspThread(rtspThread), mRtpSource(rtpSource), mTerminate(false) {
  mThread = std::thread(&RtpDataThread::Run, this);
}

RtpDataThread::~RtpDataThread() {
  Stop();
  if (mThread.joinable())
    mThread.join();
}

bool RtpDataThread::recvPacket(const unsigned char *packet, size_t packetLen) {
  const RtpDataHeader *rtpHeader;
  rtpHeader = (RtpDataHeader *)packet;

  Debug(5, "Ver: %d P: %d Pt: %d Mk: %d Seq: %d T/S: %x SSRC: %x",
        rtpHeader->version,
        rtpHeader->p,
        rtpHeader->pt,
        rtpHeader->m,
        ntohs(rtpHeader->seqN),
        ntohl(rtpHeader->timestampN),
        ntohl(rtpHeader->ssrcN));

  //unsigned short seq = ntohs(rtpHeader->seqN);
  unsigned long ssrc = ntohl(rtpHeader->ssrcN);

  if ( mRtpSource.getSsrc() && (ssrc != mRtpSource.getSsrc()) ) {
    Warning("Discarding packet for unrecognised ssrc %lx", ssrc);
    return false;
  }

  return mRtpSource.handlePacket(packet, packetLen);
}

void RtpDataThread::Run() {
  if (mRtpSource.getLocalDataPort()<=1024) {
    Debug(2, "Not starting data thread");
    return;
  }
  Debug(2, "Starting data thread %d on port %d",
        mRtpSource.getSsrc(), mRtpSource.getLocalDataPort());

  zm::SockAddrInet localAddr;
  zm::UdpInetServer rtpDataSocket;
  if ( mRtpSource.getLocalHost() != "" ) {
    if ( !rtpDataSocket.bind(mRtpSource.getLocalHost().c_str(), mRtpSource.getLocalDataPort()) )
      Fatal("Failed to bind RTP server at %s:%d",  mRtpSource.getLocalHost().c_str(), mRtpSource.getLocalDataPort());
  } else {
    if ( !rtpDataSocket.bind(
           mRtspThread.getAddressFamily() == AF_INET6 ? "::" : "0.0.0.0",
           mRtpSource.getLocalDataPort() ) )
      Fatal("Failed to bind RTP server at %s:%d",
          (mRtspThread.getAddressFamily() == AF_INET6 ? "::" : "0.0.0.0"), mRtpSource.getLocalDataPort());
  }
  Debug(3, "Bound to %s:%d",  mRtpSource.getLocalHost().c_str(), mRtpSource.getLocalDataPort());

  zm::Select select(Seconds(3));
  select.addReader(&rtpDataSocket);

  unsigned char buffer[ZM_NETWORK_BUFSIZ];
  while ( !zm_terminate && !mTerminate && (select.wait() >= 0) ) {
    zm::Select::CommsList readable = select.getReadable();
    if ( readable.size() == 0 ) {
      Error("RTP timed out");
      Stop();
      break;
    }
    for (zm::Select::CommsList::iterator iter = readable.begin(); iter != readable.end(); ++iter ) {
      if ( zm::UdpInetServer *socket = dynamic_cast<zm::UdpInetServer *>(*iter) ) {
        int nBytes = socket->recv(buffer, sizeof(buffer));
        Debug(4, "Got %d bytes on sd %d", nBytes, socket->getReadDesc());
        if ( nBytes ) {
          recvPacket(buffer, nBytes);
        } else {
          Stop();
          break;
        }
      } else {
        Panic("Barfed");
      }
    }  // end foreach commsList
  }
  rtpDataSocket.close();
  mRtspThread.Stop();
}
