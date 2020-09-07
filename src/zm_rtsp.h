//
// ZoneMinder RTSP Class Interface, $Date$, $Revision$
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

#ifndef ZM_RTSP_H
#define ZM_RTSP_H

#include "zm.h"
#include "zm_ffmpeg.h"
#include "zm_comms.h"
#include "zm_thread.h"
#include "zm_rtp_source.h"
#include "zm_rtsp_auth.h"
#include "zm_sdp.h"

#include <set>
#include <map>

class RtspThread : public Thread {
public:
  typedef enum { RTP_UNICAST, RTP_MULTICAST, RTP_RTSP, RTP_RTSP_HTTP } RtspMethod;
  typedef enum { UNDEFINED, UNICAST, MULTICAST } RtspDist;

private:
  typedef std::set<int>  PortSet;
  typedef std::set<uint32_t>  SsrcSet;
  typedef std::map<uint32_t,RtpSource *>  SourceMap;

private:
  static int  smMinDataPort;
  static int  smMaxDataPort;
  static PortSet  smLocalSsrcs;
  static PortSet  smAssignedPorts;

private:
  int mId;

  RtspMethod mMethod;
  std::string mProtocol;
  std::string mHost;
  std::string mPort;
  std::string mPath;
  bool mRtspDescribe;
  std::string mUrl;
  
  // Reworked authentication system
  // First try without authentication, even if we have a username and password
  // on receiving a 401 response, select authentication method (basic or digest)
  // fill required fields and set needAuth
  // subsequent requests can set the required authentication header.
  bool mNeedAuth;
  int respCode;
  zm::Authenticator* mAuthenticator;


  std::string mHttpSession;       ///< Only for RTSP over HTTP sessions

  TcpInetClient mRtspSocket;
  TcpInetClient mRtspSocket2;

  SourceMap mSources;

  SessionDescriptor *mSessDesc;
  AVFormatContext *mFormatContext;

  uint16_t mSeq;
  uint32_t mSession;
  uint32_t mSsrc;

  int mRemotePorts[2];
  int mRemoteChannels[2];
  RtspDist mDist;

  unsigned long mRtpTime; 

  bool mStop;

private:
  bool sendCommand( std::string message );
  bool recvResponse( std::string &response );
  void checkAuthResponse(std::string &response);  

public:
  RtspThread( int id, RtspMethod method, const std::string &protocol, const std::string &host, const std::string &port, const std::string &path, const std::string &auth, bool rtsp_describe );
  ~RtspThread();

public:
  int requestPorts();
  void releasePorts( int port );

  bool isValidSsrc( uint32_t ssrc );
  bool updateSsrc( uint32_t ssrc, const RtpDataHeader *header );

  uint32_t getSsrc() const
  {
    return( mSsrc );
  }

  bool hasSources() const
  {
    return( !mSources.empty() );
  }

  AVFormatContext *getFormatContext()
  {
    return( mFormatContext );
  }
  
  bool getFrame( Buffer &frame )
  {
    SourceMap::iterator iter = mSources.begin();
    if ( iter == mSources.end() )
      return( false );
    return( iter->second->getFrame( frame ) );
  }
  int run();
  void stop()
  {
    mStop = true;
  }
  bool stopped() const
  {
    return( mStop );
  }
  int getAddressFamily ()
  {
    return mRtspSocket.getDomain();
  }
};

#endif // ZM_RTSP_H
