//
// ZoneMinder RTSP Class Implementation, $Date$, $Revision$
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

#include "zm_rtsp.h"

#include "zm_config.h"
#include "zm_rtp_data.h"
#include "zm_rtp_ctrl.h"
#include "zm_db.h"

#include <algorithm>

int RtspThread::smMinDataPort = 0;
int RtspThread::smMaxDataPort = 0;
RtspThread::PortSet RtspThread::smAssignedPorts;

bool RtspThread::sendCommand(std::string message) {
  if ( mNeedAuth ) {
    StringVector parts = Split(message, " ");
    if ( parts.size() > 1 )
      message += mAuthenticator->getAuthHeader(parts[0], parts[1]);
  }
  message += stringtf("User-Agent: ZoneMinder/%s\r\n", ZM_VERSION);
  message += stringtf("CSeq: %d\r\n\r\n", ++mSeq);
  Debug(2, "Sending RTSP message: %s", message.c_str());
  if ( mMethod == RTP_RTSP_HTTP ) {
    message = Base64Encode(message);
    Debug(2, "Sending encoded RTSP message: %s", message.c_str());
    if ( mRtspSocket2.send(message.c_str(), message.size()) != (int)message.length() ) {
      Error("Unable to send message '%s': %s", message.c_str(), strerror(errno));
      return false;
    }
  } else {
    if ( mRtspSocket.send(message.c_str(), message.size()) != (int)message.length() ) {
      Error("Unable to send message '%s': %s", message.c_str(), strerror(errno));
      return false;
    }
  }
  return true;
}

bool RtspThread::recvResponse(std::string &response) {
  if ( mRtspSocket.recv(response) < 0 )
    Error("Recv failed; %s", strerror(errno));
  Debug(2, "Received RTSP response: %s (%zd bytes)", response.c_str(), response.size());
  float respVer = 0;
  respCode = -1;
  char respText[ZM_NETWORK_BUFSIZ];
  if ( sscanf(response.c_str(), "RTSP/%f %3d %[^\r\n]\r\n", &respVer, &respCode, respText) != 3 ) {
    if ( isalnum(response[0]) ) {
      Error("Response parse failure in '%s'", response.c_str());
    } else {
      Error("Response parse failure, %zd bytes follow", response.size());
      if ( response.size() )
        Hexdump(Logger::ERROR, response.data(), std::min(int(response.size()), 16));
    }
    return false;
  }
  if ( respCode == 401 ) {
    Debug(2, "Got 401 access denied response code, check WWW-Authenticate header and retry");
    mAuthenticator->checkAuthResponse(response);
    mNeedAuth = true;
    return false;
  } else if ( respCode != 200 ) {
    Error("Unexpected response code %d, text is '%s'", respCode, respText);
    return false;
  }
  return true;
}  // end RtspThread::recvResponse

int RtspThread::requestPorts() {
  if ( !smMinDataPort ) {
    //FIXME Why not load specifically by Id?  This will get ineffeicient with a lot of monitors
    std::string sql = "SELECT `Id` FROM `Monitors` WHERE `Function` != 'None' AND `Type` = 'Remote' AND `Protocol` = 'rtsp' AND `Method` = 'rtpUni' ORDER BY `Id` ASC";

    MYSQL_RES *result = zmDbFetch(sql);

    int nMonitors = mysql_num_rows(result);
    int position = 0;
    if ( nMonitors ) {
      for ( int i = 0; MYSQL_ROW dbrow = mysql_fetch_row(result); i++ ) {
        int id = atoi(dbrow[0]);
        if ( mId == id ) {
          position = i;
          break;
        }
      }
    } else {
      // Minor hack for testing when not strictly enabled
      nMonitors = 1;
      position = 0;
    }
    mysql_free_result(result);
    int portRange = int(((config.max_rtp_port-config.min_rtp_port)+1)/nMonitors);
    smMinDataPort = config.min_rtp_port + (position * portRange);
    smMaxDataPort = smMinDataPort + portRange - 1;
    Debug(2, "Assigned RTP port range is %d-%d", smMinDataPort, smMaxDataPort);
  }
  for ( int i = smMinDataPort; i <= smMaxDataPort; i++ ) {
    PortSet::const_iterator iter = smAssignedPorts.find(i);
    if ( iter == smAssignedPorts.end() ) {
      smAssignedPorts.insert(i);
      return i;
    }
  }
  Panic("Can assign RTP port, no ports left in pool");
  return -1;
}

void RtspThread::releasePorts(int port) {
  if ( port > 0 )
    smAssignedPorts.erase(port);
}

RtspThread::RtspThread(
  int id,
  RtspMethod method,
  const std::string &protocol,
  const std::string &host,
  const std::string &port,
  const std::string &path,
  const std::string &user,
  const std::string &pass,
  bool rtsp_describe) :
  mId(id),
  mMethod(method),
  mProtocol(protocol),
  mHost(host),
  mPort(port),
  mPath(path),
  mRtspDescribe(rtsp_describe),
  mSessDesc(0),
  mFormatContext(0),
  mSeq(0),
  mSession(0),
  mSsrc(0),
  mDist(UNDEFINED),
  mRtpTime(0),
  mTerminate(false) {
  mUrl = mProtocol+"://"+mHost+":"+mPort;
  if ( !mPath.empty() ) {
    if ( mPath[0] == '/' )
      mUrl += mPath;
    else
      mUrl += '/'+mPath;
  }

  mSsrc = rand();

  Debug(2, "RTSP Local SSRC is %x, url is %s", mSsrc, mUrl.c_str());

  if ( mMethod == RTP_RTSP_HTTP )
    mHttpSession = stringtf("%d", rand());

  mNeedAuth = false;
  if ( user.length() > 0 && pass.length() > 0 ) {
    Debug(2, "# of auth parts 2");
    mAuthenticator = new zm::Authenticator(user, pass);
  } else if( user.length() > 0 ) {
    Debug(2, "# of auth parts 1");
    mAuthenticator = new zm::Authenticator(user, "");
  } else {
    Debug(2, "# of auth parts 0");
    mAuthenticator = new zm::Authenticator("", "");
  }

  mThread = std::thread(&RtspThread::Run, this);
}

RtspThread::~RtspThread() {
  Stop();
  if (mThread.joinable())
    mThread.join();

  if ( mFormatContext ) {
    avformat_free_context(mFormatContext);
    mFormatContext = nullptr;
  }
  if ( mSessDesc ) {
    delete mSessDesc;
    mSessDesc = nullptr;
  }
  delete mAuthenticator;
  mAuthenticator = nullptr;
}

void RtspThread::Run() {
  std::string message;
  std::string response;

  response.reserve(ZM_NETWORK_BUFSIZ);

  if ( !mRtspSocket.connect(mHost.c_str(), mPort.c_str()) )
    Fatal("Unable to connect RTSP socket");
  //Select select( 0.25 );
  //select.addReader( &mRtspSocket );
  //while ( select.wait() )
  //{
  //mRtspSocket.recv( response );
  //Debug( 4, "Drained %d bytes from RTSP socket", response.size() );
  //}

  bool authTried = false;
  if ( mMethod == RTP_RTSP_HTTP ) {
    if ( !mRtspSocket2.connect(mHost.c_str(), mPort.c_str()) )
      Fatal("Unable to connect auxiliary RTSP/HTTP socket");
    //Select select( 0.25 );
    //select.addReader( &mRtspSocket2 );
    //while ( select.wait() )
    //{
    //mRtspSocket2.recv( response );
    //Debug( 4, "Drained %d bytes from HTTP socket", response.size() );
    //}

    //possibly retry sending the message for authentication
    int respCode = -1;
    char respText[256];
    do {
      message = "GET "+mPath+" HTTP/1.0\r\n";
      message += "X-SessionCookie: "+mHttpSession+"\r\n";
      if ( mNeedAuth ) {
        message += mAuthenticator->getAuthHeader("GET", mPath);
        authTried = true;
      }
      message += "Accept: application/x-rtsp-tunnelled\r\n\r\n";
      Debug(2, "Sending HTTP message: %s", message.c_str());
      if ( mRtspSocket.send(message.c_str(), message.size()) != (int)message.length() ) {
        Error("Unable to send message '%s': %s", message.c_str(), strerror(errno));
        return;
      }
      if ( mRtspSocket.recv(response) < 0 ) {
        Error("Recv failed; %s", strerror(errno));
        return;
      }

      Debug(2, "Received HTTP response: %s (%zd bytes)", response.c_str(), response.size());
      float respVer = 0;
      respCode = -1;
      if ( sscanf(response.c_str(), "HTTP/%f %3d %[^\r\n]\r\n", &respVer, &respCode, respText) != 3 ) {
        if ( isalnum(response[0]) ) {
          Error("Response parse failure in '%s'", response.c_str());
        } else {
          Error("Response parse failure, %zd bytes follow", response.size());
          if ( response.size() )
            Hexdump(Logger::ERROR, response.data(), std::min(int(response.size()), 16));
        }
        return;
      }
      // If Server requests authentication, check WWW-Authenticate header and fill required fields
      // for requested authentication method
      if ( respCode == 401 && !authTried ) {
        mNeedAuth = true;
        mAuthenticator->checkAuthResponse(response);
        Debug(2, "Processed 401 response");
        mRtspSocket.close();
        if ( !mRtspSocket.connect(mHost.c_str(), mPort.c_str()) )
          Fatal("Unable to reconnect RTSP socket");
        Debug(2, "connection should be reopened now");
      }

    } while (respCode == 401 && !authTried);

    if ( respCode != 200 ) {
      Error("Unexpected response code %d, text is '%s'", respCode, respText);
      return;
    }

    message = "POST "+mPath+" HTTP/1.0\r\n";
    message += "X-SessionCookie: "+mHttpSession+"\r\n";
    if ( mNeedAuth )
      message += mAuthenticator->getAuthHeader("POST", mPath);
    message += "Content-Length: 32767\r\n";
    message += "Content-Type: application/x-rtsp-tunnelled\r\n";
    message += "\r\n";
    Debug(2, "Sending HTTP message: %s", message.c_str());
    if ( mRtspSocket2.send(message.c_str(), message.size()) != (int)message.length() ) {
      Error("Unable to send message '%s': %s", message.c_str(), strerror(errno));
      return;
    }
  }  // end if ( mMethod == RTP_RTSP_HTTP )

  std::string localHost = "";
  int localPorts[2] = { 0, 0 };

  // Request supported RTSP commands by the server
  message = "OPTIONS "+mUrl+" RTSP/1.0\r\n";
  if ( !sendCommand(message) )
    return;

  // A negative return here may indicate auth failure, but we will have setup the auth mechanisms so we need to retry.
  if ( !recvResponse(response) ) {
    if ( mNeedAuth ) {
      Debug(2, "Resending OPTIONS due to possible auth requirement");
      if ( !sendCommand(message) )
        return;
      if ( !recvResponse(response) )
        return;
    } else {
      return;
    }
  } // end if failed response maybe due to auth

  char publicLine[256] = "";
  StringVector lines = Split(response, "\r\n");
  for ( size_t i = 0; i < lines.size(); i++ )
    sscanf(lines[i].c_str(), "Public: %[^\r\n]\r\n", publicLine);

  // Check if the server supports the GET_PARAMETER command
  // If yes, it is likely that the server will request this command as a keepalive message
  bool sendKeepalive = false;
  if ( publicLine[0] && strstr(publicLine, "GET_PARAMETER") )
    sendKeepalive = true;

  message = "DESCRIBE "+mUrl+" RTSP/1.0\r\n";
  bool res;
  do {
    if ( mNeedAuth )
      authTried = true;
    sendCommand(message);
    // FIXME Why sleep 1?
    std::this_thread::sleep_for(Microseconds(10));

    res = recvResponse(response);
    if ( !res && respCode==401 )
      mNeedAuth = true;
  } while (!res && respCode==401 && !authTried);

  const std::string endOfHeaders = "\r\n\r\n";
  size_t sdpStart = response.find(endOfHeaders);
  if ( sdpStart == std::string::npos )
    return;

  if ( mRtspDescribe ) {
    std::string DescHeader = response.substr(0, sdpStart);
    Debug(1, "Processing DESCRIBE response header '%s'", DescHeader.c_str());

    lines = Split(DescHeader, "\r\n");
    for ( size_t i = 0; i < lines.size(); i++ ) {
      // If the device sends us a url value for Content-Base in the response header, we should use that instead
      if ( ( lines[i].size() > 13 ) && ( lines[i].substr( 0, 13 ) == "Content-Base:" ) ) {
        mUrl = TrimSpaces(lines[i].substr(13));
        Info("Received new Content-Base in DESCRIBE response header. Updated device Url to: '%s'", mUrl.c_str() );
        break;
      }
    } // end foreach line
  } // end if mRtspDescribe

  sdpStart += endOfHeaders.length();

  std::string sdp = response.substr(sdpStart);
  Debug(1, "Processing SDP '%s'", sdp.c_str());

  try {
    mSessDesc = new SessionDescriptor( mUrl, sdp );
    mFormatContext = mSessDesc->generateFormatContext();
  } catch ( const Exception &e ) {
    Error("%s", e.getMessage().c_str());
    return;
  }

#if 0
  // New method using ffmpeg native functions
  std::string authUrl = mUrl;
  if ( !mAuth.empty() )
    authUrl.insert( authUrl.find( "://" )+3, mAuth+"@" );

  if ( av_open_input_file( &mFormatContext, authUrl.c_str(), nullptr, 0, nullptr ) != 0 ) {
    Error( "Unable to open input '%s'", authUrl.c_str() );
    return( -1 );
  }
#endif

  uint32_t rtpClock = 0;
  std::string trackUrl = mUrl;
  std::string controlUrl;

  _AVCODECID codecId = AV_CODEC_ID_NONE;

  if ( mFormatContext->nb_streams >= 1 ) {
    for ( unsigned int i = 0; i < mFormatContext->nb_streams; i++ ) {
      SessionDescriptor::MediaDescriptor *mediaDesc = mSessDesc->getStream(i);
      if (mFormatContext->streams[i]->codecpar->codec_type == AVMEDIA_TYPE_VIDEO) {
        // Check if control Url is absolute or relative
        controlUrl = mediaDesc->getControlUrl();
        if (trackUrl != controlUrl) {
          if ( *trackUrl.rbegin() != '/' ) {
            trackUrl += "/" + controlUrl;
          } else {
            trackUrl += controlUrl;
          }
        }
        rtpClock = mediaDesc->getClock();
        codecId = mFormatContext->streams[i]->codecpar->codec_id;
        break;
      }  // end if is video
    }  // end foreach stream
  }  // end if have stream

  switch ( mMethod ) {
  case RTP_UNICAST :
    localPorts[0] = requestPorts();
    localPorts[1] = localPorts[0]+1;

    message = "SETUP "+trackUrl+" RTSP/1.0\r\nTransport: RTP/AVP;unicast;client_port="
              +stringtf("%d", localPorts[0] )+"-"+stringtf( "%d", localPorts[1])+"\r\n";
    break;
  case RTP_MULTICAST :
    message = "SETUP "+trackUrl+" RTSP/1.0\r\nTransport: RTP/AVP;multicast\r\n";
    break;
  case RTP_RTSP :
  case RTP_RTSP_HTTP :
    message = "SETUP "+trackUrl+" RTSP/1.0\r\nTransport: RTP/AVP/TCP;unicast\r\n";
    break;
  default:
    Panic("Got unexpected method %d", mMethod);
    break;
  }

  if ( !sendCommand(message) )
    return;
  if ( !recvResponse(response) )
    return;

  lines = Split(response, "\r\n");
  std::string session;
  Seconds timeout = Seconds(0);
  char transport[256] = "";

  for ( size_t i = 0; i < lines.size(); i++ ) {
    if ( ( lines[i].size() > 8 ) && ( lines[i].substr(0, 8) == "Session:" ) ) {
      StringVector sessionLine = Split(lines[i].substr(9), ";");
      session = TrimSpaces(sessionLine[0]);
      if ( sessionLine.size() == 2 ) {
        int32 timeout_val = 0;
        sscanf(TrimSpaces(sessionLine[1]).c_str(), "timeout=%d", &timeout_val);
        timeout = Seconds(timeout_val);
      }
    }
    sscanf(lines[i].c_str(), "Transport: %s", transport);
  }

  if ( session.empty() )
    Fatal("Unable to get session identifier from response '%s'", response.c_str());

  Debug(2, "Got RTSP session %s, timeout %" PRIi64 " secs",
        session.c_str(),
        static_cast<int64>(Seconds(timeout).count()));

  if ( !transport[0] )
    Fatal("Unable to get transport details from response '%s'", response.c_str());

  Debug(2, "Got RTSP transport %s", transport);

  std::string method = "";
  int remotePorts[2] = { 0, 0 };
  int remoteChannels[2] = { 0, 0 };
  std::string distribution = "";
  unsigned long ssrc = 0;
  StringVector parts = Split(transport, ";");
  for ( size_t i = 0; i < parts.size(); i++ ) {
    if ( parts[i] == "unicast" || parts[i] == "multicast" )
      distribution = parts[i];
    else if (StartsWith(parts[i], "server_port=") ) {
      method = "RTP/UNICAST";
      StringVector subparts = Split(parts[i], "=");
      StringVector ports = Split(subparts[1], "-");
      remotePorts[0] = strtol( ports[0].c_str(), nullptr, 10 );
      remotePorts[1] = strtol( ports[1].c_str(), nullptr, 10 );
    } else if (StartsWith(parts[i], "interleaved=") ) {
      method = "RTP/RTSP";
      StringVector subparts = Split(parts[i], "=");
      StringVector channels = Split(subparts[1], "-");
      remoteChannels[0] = strtol( channels[0].c_str(), nullptr, 10 );
      remoteChannels[1] = strtol( channels[1].c_str(), nullptr, 10 );
    } else if (StartsWith(parts[i], "port=") ) {
      method = "RTP/MULTICAST";
      StringVector subparts = Split(parts[i], "=");
      StringVector ports = Split(subparts[1], "-");
      localPorts[0] = strtol( ports[0].c_str(), nullptr, 10 );
      localPorts[1] = strtol( ports[1].c_str(), nullptr, 10 );
    } else if (StartsWith(parts[i], "destination=") ) {
      StringVector subparts = Split(parts[i], "=");
      localHost = subparts[1];
    } else if (StartsWith(parts[i], "ssrc=") ) {
      StringVector subparts = Split(parts[i], "=");
      ssrc = strtoll( subparts[1].c_str(), nullptr, 16 );
    }
  }

  Debug( 2, "RTSP Method is %s", method.c_str() );
  Debug( 2, "RTSP Distribution is %s", distribution.c_str() );
  Debug( 2, "RTSP SSRC is %lx", ssrc );
  Debug( 2, "RTSP Local Host is %s", localHost.c_str() );
  Debug( 2, "RTSP Local Ports are %d/%d", localPorts[0], localPorts[1] );
  Debug( 2, "RTSP Remote Ports are %d/%d", remotePorts[0], remotePorts[1] );
  Debug( 2, "RTSP Remote Channels are %d/%d", remoteChannels[0], remoteChannels[1] );

  message = "PLAY "+mUrl+" RTSP/1.0\r\nSession: "+session+"\r\nRange: npt=0.000-\r\n";
  if ( !sendCommand(message) )
    return;
  if ( !recvResponse(response) )
    return;

  lines = Split(response, "\r\n");
  std::string rtpInfo;
  for ( size_t i = 0; i < lines.size(); i++ ) {
    if ( ( lines[i].size() > 9 ) && ( lines[i].substr(0, 9) == "RTP-Info:" ) )
      rtpInfo = TrimSpaces(lines[i].substr(9));
    // Check for a timeout again. Some rtsp devices don't send a timeout until after the PLAY command is sent
    if ((lines[i].size() > 8) && (lines[i].substr(0, 8) == "Session:") && (timeout == Seconds(0))) {
      StringVector sessionLine = Split(lines[i].substr(9), ";");
      if ( sessionLine.size() == 2 ) {
        int32 timeout_val = 0;
        sscanf(TrimSpaces(sessionLine[1]).c_str(), "timeout=%d", &timeout_val);
        timeout = Seconds(timeout_val);
      }

      if ( timeout > Seconds(0) ) {
        Debug(2, "Got timeout %" PRIi64 " secs from PLAY command response",
              static_cast<int64>(Seconds(timeout).count()));
      }
    }
  }

  int seq = 0;
  unsigned long rtpTime = 0;
  StringVector streams;
  if ( rtpInfo.empty() ) {
    Debug( 1, "RTP Info Empty. Starting values for Sequence and Rtptime shall be zero.");
  } else {
    Debug( 2, "Got RTP Info %s", rtpInfo.c_str() );
    // More than one stream can be included in the RTP Info
    streams = Split(rtpInfo, ",");
    for ( size_t i = 0; i < streams.size(); i++ ) {
      // We want the stream that matches the trackUrl we are using
      if ( streams[i].find(controlUrl.c_str()) != std::string::npos ) {
        // Parse the sequence and rtptime values
        parts = Split(streams[i].c_str(), ";");
        for ( size_t j = 0; j < parts.size(); j++ ) {
          if (StartsWith(parts[j], "seq=") ) {
            StringVector subparts = Split(parts[j], "=");
            seq = strtol( subparts[1].c_str(), nullptr, 10 );
          } else if (StartsWith(parts[j], "rtptime=") ) {
            StringVector subparts = Split(parts[j], "=");
            rtpTime = strtol( subparts[1].c_str(), nullptr, 10 );
          }
        }
        break;
      }
    }
  }

  Debug( 2, "RTSP Seq is %d", seq );
  Debug( 2, "RTSP Rtptime is %ld", rtpTime );

  TimePoint lastKeepalive = std::chrono::steady_clock::now();
  TimePoint now;
  message = "GET_PARAMETER "+mUrl+" RTSP/1.0\r\nSession: "+session+"\r\n";

  switch( mMethod ) {
  case RTP_UNICAST : {
    RtpSource *source = new RtpSource( mId, "", localPorts[0], mHost, remotePorts[0], ssrc, seq, rtpClock, rtpTime, codecId );
    mSources[ssrc] = source;
    RtpDataThread rtpDataThread( *this, *source );
    RtpCtrlThread rtpCtrlThread( *this, *source );

    while (!mTerminate) {
      now = std::chrono::steady_clock::now();
      // Send a keepalive message if the server supports this feature and we are close to the timeout expiration
      Debug(5, "sendkeepalive %d, timeout %" PRIi64 " s, now: %" PRIi64 " s last: %" PRIi64 " s since: %" PRIi64 "s ",
            sendKeepalive,
            static_cast<int64>(Seconds(timeout).count()),
            static_cast<int64>(std::chrono::duration_cast<Seconds>(now.time_since_epoch()).count()),
            static_cast<int64>(std::chrono::duration_cast<Seconds>(lastKeepalive.time_since_epoch()).count()),
            static_cast<int64>(std::chrono::duration_cast<Seconds>((now - lastKeepalive)).count()));

      if (sendKeepalive && (timeout > Seconds(0)) && ((now - lastKeepalive) > (timeout - Seconds(5)))) {
        if (!sendCommand(message))
          return;
        lastKeepalive = now;
      }
      std::this_thread::sleep_for(Microseconds(100));
    }
#if 0
    message = "PAUSE "+mUrl+" RTSP/1.0\r\nSession: "+session+"\r\n";
    if ( !sendCommand( message ) )
      return( -1 );
    if ( !recvResponse( response ) )
      return( -1 );
#endif

    message = "TEARDOWN "+mUrl+" RTSP/1.0\r\nSession: "+session+"\r\n";
    if ( !sendCommand( message ) )
      return;
    if ( !recvResponse( response ) )
      return;

    rtpDataThread.Stop();
    rtpCtrlThread.Stop();

    //rtpDataThread.kill( SIGTERM );
    //rtpCtrlThread.kill( SIGTERM );

    delete mSources[ssrc];
    mSources.clear();

    releasePorts( localPorts[0] );

    break;
  }
  case RTP_RTSP :
  case RTP_RTSP_HTTP : {
    RtpSource *source = new RtpSource( mId, "", remoteChannels[0], mHost, remoteChannels[0], ssrc, seq, rtpClock, rtpTime, codecId );
    mSources[ssrc] = source;
    // These never actually run
    RtpDataThread rtpDataThread( *this, *source );
    RtpCtrlThread rtpCtrlThread( *this, *source );

    zm::Select select(Milliseconds(config.http_timeout));
    select.addReader( &mRtspSocket );

    Buffer buffer( ZM_NETWORK_BUFSIZ );
    std::string keepaliveMessage = "OPTIONS "+mUrl+" RTSP/1.0\r\n";
    std::string keepaliveResponse = "RTSP/1.0 200 OK\r\n";
    while (!mTerminate && select.wait() >= 0) {
      zm::Select::CommsList readable = select.getReadable();
      if ( readable.size() == 0 ) {
        Error( "RTSP timed out" );
        break;
      }

      static char tempBuffer[ZM_NETWORK_BUFSIZ];
      ssize_t nBytes = mRtspSocket.recv( tempBuffer, sizeof(tempBuffer) );
      buffer.append( tempBuffer, nBytes );
      Debug( 4, "Read %zd bytes on sd %d, %d total", nBytes, mRtspSocket.getReadDesc(), buffer.size() );

      while( buffer.size() > 0 ) {
        if ( buffer[0] == '$' ) {
          if ( buffer.size() < 4 )
            break;
          unsigned char channel = buffer[1];
          unsigned short len = ntohs( *((unsigned short *)(buffer+2)) );

          Debug( 4, "Got %d bytes left, expecting %d byte packet on channel %d", buffer.size(), len, channel );
          if ( (unsigned short)buffer.size() < (len+4) ) {
            Debug( 4, "Missing %d bytes, rereading", (len+4)-buffer.size() );
            break;
          }
          if ( channel == remoteChannels[0] ) {
            Debug(4, "Got %d bytes on data channel %d, packet length is %d", buffer.size(), channel, len);
            Hexdump(4, (char *)buffer, 16);
            rtpDataThread.recvPacket(buffer+4, len);
          } else if ( channel == remoteChannels[1] ) {
//              len = ntohs( *((unsigned short *)(buffer+2)) );
//              Debug( 4, "Got %d bytes on control channel %d", nBytes, channel );
            Debug(4, "Got %d bytes on control channel %d, packet length is %d", buffer.size(), channel, len);
            Hexdump(4, (char *)buffer, 16);
            rtpCtrlThread.recvPackets(buffer+4, len);
          } else {
            Error("Unexpected channel selector %d in RTSP interleaved data", buffer[1]);
            buffer.clear();
            break;
          }
          buffer.consume(len+4);
          nBytes -= len+4;
        } else {
          if ( keepaliveResponse.compare( 0, keepaliveResponse.size(), (char *)buffer, keepaliveResponse.size() ) == 0 ) {
            Debug( 4, "Got keepalive response '%s'", (char *)buffer );
            //buffer.consume( keepaliveResponse.size() );
            if ( const char *charPtr = (char *)memchr( (char *)buffer, '$', buffer.size() ) ) {
              int discardBytes = charPtr-(char *)buffer;
              buffer -= discardBytes;
            } else {
              buffer.clear();
            }
          } else {
            if ( const char *charPtr = (char *)memchr( (char *)buffer, '$', buffer.size() ) ) {
              int discardBytes = charPtr-(char *)buffer;
              Warning( "Unexpected format RTSP interleaved data, resyncing by %d bytes", discardBytes );
              Hexdump( -1, (char *)buffer, discardBytes );
              buffer -= discardBytes;
            } else {
              Warning( "Unexpected format RTSP interleaved data, dumping %d bytes", buffer.size() );
              Hexdump( -1, (char *)buffer, 32 );
              buffer.clear();
            }
          }
        }
      }
      // Send a keepalive message if the server supports this feature and we are close to the timeout expiration
      // FIXME: Is this really necessary when using tcp ?
      now = std::chrono::steady_clock::now();
      // Send a keepalive message if the server supports this feature and we are close to the timeout expiration
      Debug(5, "sendkeepalive %d, timeout %" PRIi64 " s, now: %" PRIi64 " s last: %" PRIi64 " s since: %" PRIi64 " s",
            sendKeepalive,
            static_cast<int64>(Seconds(timeout).count()),
            static_cast<int64>(std::chrono::duration_cast<Seconds>(now.time_since_epoch()).count()),
            static_cast<int64>(std::chrono::duration_cast<Seconds>(lastKeepalive.time_since_epoch()).count()),
            static_cast<int64>(std::chrono::duration_cast<Seconds>((now - lastKeepalive)).count()));

      if (sendKeepalive && (timeout > Seconds(0)) && ((now - lastKeepalive) > (timeout - Seconds(5)))) {
        if (!sendCommand(message)) {
          return;
        }

        lastKeepalive = now;
      }
      buffer.tidy(true);
    }
#if 0
    message = "PAUSE "+mUrl+" RTSP/1.0\r\nSession: "+session+"\r\n";
    if ( !sendCommand( message ) )
      return( -1 );
    if ( !recvResponse( response ) )
      return( -1 );
#endif
    // Send a teardown message but don't expect a response as this may not be implemented on the server when using TCP
    message = "TEARDOWN "+mUrl+" RTSP/1.0\r\nSession: "+session+"\r\n";
    if ( !sendCommand( message ) )
      return;

    delete mSources[ssrc];
    mSources.clear();

    break;
  }
  case RTP_MULTICAST : {
    RtpSource *source = new RtpSource( mId, localHost, localPorts[0], mHost, remotePorts[0], ssrc, seq, rtpClock, rtpTime, codecId );
    mSources[ssrc] = source;
    RtpDataThread rtpDataThread( *this, *source );
    RtpCtrlThread rtpCtrlThread( *this, *source );


    while (!mTerminate) {
      // Send a keepalive message if the server supports this feature and we are close to the timeout expiration
      if (sendKeepalive && (timeout > Seconds(0))
          && ((std::chrono::steady_clock::now() - lastKeepalive) > (timeout - Seconds(5)))) {
        if (!sendCommand(message)) {
          return;
        }
        lastKeepalive = std::chrono::steady_clock::now();
      }
      std::this_thread::sleep_for(Microseconds(100));
    }
#if 0
    message = "PAUSE "+mUrl+" RTSP/1.0\r\nSession: "+session+"\r\n";
    if ( !sendCommand( message ) )
      return( -1 );
    if ( !recvResponse( response ) )
      return( -1 );
#endif
    message = "TEARDOWN "+mUrl+" RTSP/1.0\r\nSession: "+session+"\r\n";
    if ( !sendCommand(message) )
      return;
    if ( !recvResponse(response) )
      return;

    rtpDataThread.Stop();
    rtpCtrlThread.Stop();

    delete mSources[ssrc];
    mSources.clear();

    releasePorts( localPorts[0] );
    break;
  }
  default:
    Panic("Got unexpected method %d", mMethod);
    break;
  }

  return;
}
