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
// Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
// 

#include "zm.h"

#if HAVE_LIBAVFORMAT

#include "zm_rtsp.h"

#include "zm_rtp_data.h"
#include "zm_rtp_ctrl.h"
#include "zm_db.h"
#include "zm_sdp.h"

#include <sys/time.h>
#include <signal.h>
#include <stdlib.h>
#include <errno.h>

int RtspThread::smMinDataPort = 0;
int RtspThread::smMaxDataPort = 0;
RtspThread::PortSet  RtspThread::smAssignedPorts;

bool RtspThread::sendCommand( std::string message )
{
    if ( !mAuth.empty() )
        message += stringtf( "Authorization: Basic %s\r\n", mAuth64.c_str() );
    message += stringtf( "User-Agent: ZoneMinder/%s\r\n", ZM_VERSION );
    message += stringtf( "CSeq: %d\r\n\r\n", ++mSeq );
    Debug( 2, "Sending RTSP message: %s", message.c_str() );
    if ( mMethod == RTP_RTSP_HTTP )
    {
        message = base64Encode( message );
        Debug( 2, "Sending encoded RTSP message: %s", message.c_str() );
        if ( mRtspSocket2.send( message.c_str(), message.size() ) != (int)message.length() )
        {
            Error( "Unable to send message '%s': %s", message.c_str(), strerror(errno) );
            return( false );
        }
    }
    else
    {
        if ( mRtspSocket.send( message.c_str(), message.size() ) != (int)message.length() )
        {
            Error( "Unable to send message '%s': %s", message.c_str(), strerror(errno) );
            return( false );
        }
    }
    return( true );
}

bool RtspThread::recvResponse( std::string &response )
{
    if ( mRtspSocket.recv( response ) < 0 )
        Error( "Recv failed; %s", strerror(errno) );
    Debug( 2, "Received RTSP response: %s (%zd bytes)", response.c_str(), response.size() );
    float respVer = 0;
    int respCode = -1;
    char respText[ZM_NETWORK_BUFSIZ];
    if ( sscanf( response.c_str(), "RTSP/%f %3d %[^\r\n]\r\n", &respVer, &respCode, respText ) != 3 )
    {
        if ( isalnum(response[0]) )
        {
            Error( "Response parse failure in '%s'", response.c_str() );
        }
        else
        {
            Error( "Response parse failure, %zd bytes follow", response.size() );
            if ( response.size() )
                Hexdump( ZM_DBG_ERR, response.data(), min(response.size(),16) );
        }
        return( false );
    }
    if ( respCode != 200 )
    {
        Error( "Unexpected response code %d, text is '%s'", respCode, respText );
        return( false );
    }
    return( true );
}

int RtspThread::requestPorts()
{
    if ( !smMinDataPort )
    {
        char sql[ZM_SQL_SML_BUFSIZ];
        strncpy( sql, "select Id from Monitors where Function != 'None' and Type = 'Remote' and Protocol = 'rtsp' and Method = 'rtpUni' order by Id asc", sizeof(sql) );
        if ( mysql_query( &dbconn, sql ) )
        {
            Error( "Can't run query: %s", mysql_error( &dbconn ) );
            exit( mysql_errno( &dbconn ) );
        }

        MYSQL_RES *result = mysql_store_result( &dbconn );
        if ( !result )
        {
            Error( "Can't use query result: %s", mysql_error( &dbconn ) );
            exit( mysql_errno( &dbconn ) );
        }
        int nMonitors = mysql_num_rows( result );
        int position = 0;
        if ( nMonitors )
        {
            for( int i = 0; MYSQL_ROW dbrow = mysql_fetch_row( result ); i++ )
            {
                int id = atoi(dbrow[0]);
                if ( mId == id )
                {
                    position = i;
                    break;
                }
            }
        }
        else
        {
            // Minor hack for testing when not strictly enabled
            nMonitors = 1;
            position = 0;
        }
        int portRange = int(((config.max_rtp_port-config.min_rtp_port)+1)/nMonitors);
        smMinDataPort = config.min_rtp_port + (position * portRange);
        smMaxDataPort = smMinDataPort + portRange - 1;
        Debug( 2, "Assigned RTP port range is %d-%d", smMinDataPort, smMaxDataPort );
    }
    for ( int i = smMinDataPort; i <= smMaxDataPort; i++ )
    {
        PortSet::const_iterator iter = smAssignedPorts.find( i );
        if ( iter == smAssignedPorts.end() )
        {
            smAssignedPorts.insert( i );
            return( i );
        }
    }
    Panic( "Can assign RTP port, no ports left in pool" );
    return( -1 );
}

void RtspThread::releasePorts( int port )
{
    if ( port > 0 )
        smAssignedPorts.erase( port );
}

RtspThread::RtspThread( int id, RtspMethod method, const std::string &protocol, const std::string &host, const std::string &port, const std::string &path, const std::string &auth ) :
    mId( id ),
    mMethod( method ),
    mProtocol( protocol ),
    mHost( host ),
    mPort( port ),
    mPath( path ),
    mAuth( auth ),
    mFormatContext( 0 ),
    mSeq( 0 ),
    mSession( 0 ),
    mSsrc( 0 ),
    mDist( UNDEFINED ),
    mRtpTime( 0 ),
    mStop( false )
{
    mUrl = mProtocol+"://"+mHost+":"+mPort;
    if ( !mPath.empty() )
    {
        if ( mPath[0] == '/' )
            mUrl += mPath;
        else
            mUrl += '/'+mPath;
    }

    mSsrc = rand();

    Debug( 2, "RTSP Local SSRC is %x", mSsrc );

    if ( mMethod == RTP_RTSP_HTTP )
        mHttpSession = stringtf( "%d", rand() );

    if ( !mAuth.empty() )
        mAuth64 = base64Encode( mAuth );
}

RtspThread::~RtspThread()
{
}

int RtspThread::run()
{
    std::string message;
    std::string response;

    response.reserve( ZM_NETWORK_BUFSIZ );

    if ( !mRtspSocket.connect( mHost.c_str(), strtol( mPort.c_str(), NULL, 10 ) ) )
        Fatal( "Unable to connect RTSP socket" );
    //Select select( 0.25 );
    //select.addReader( &mRtspSocket );
    //while ( select.wait() )
    //{
        //mRtspSocket.recv( response );
        //Debug( 4, "Drained %d bytes from RTSP socket", response.size() );
    //}

    if ( mMethod == RTP_RTSP_HTTP )
    {
        if ( !mRtspSocket2.connect( mHost.c_str(), strtol( mPort.c_str(), NULL, 10 ) ) )
            Fatal( "Unable to connect auxiliary RTSP/HTTP socket" );
        //Select select( 0.25 );
        //select.addReader( &mRtspSocket2 );
        //while ( select.wait() )
        //{
            //mRtspSocket2.recv( response );
            //Debug( 4, "Drained %d bytes from HTTP socket", response.size() );
        //}

        message = "GET "+mPath+" HTTP/1.0\r\n";
        message += "X-SessionCookie: "+mHttpSession+"\r\n";
        if ( !mAuth.empty() )
            message += stringtf( "Authorization: Basic %s\r\n", mAuth64.c_str() );
        message += "\r\n";
        Debug( 2, "Sending HTTP message: %s", message.c_str() );
        if ( mRtspSocket.send( message.c_str(), message.size() ) != (int)message.length() )
        {
            Error( "Unable to send message '%s': %s", message.c_str(), strerror(errno) );
            return( -1 );
        }
        if ( mRtspSocket.recv( response ) < 0 )
        {
            Error( "Recv failed; %s", strerror(errno) );
            return( -1 );
        }

        Debug( 2, "Received HTTP response: %s (%zd bytes)", response.c_str(), response.size() );
        float respVer = 0;
        int respCode = -1;
        char respText[256];
        if ( sscanf( response.c_str(), "HTTP/%f %3d %[^\r\n]\r\n", &respVer, &respCode, respText ) != 3 )
        {
            if ( isalnum(response[0]) )
            {
                Error( "Response parse failure in '%s'", response.c_str() );
            }
            else
            {
                Error( "Response parse failure, %zd bytes follow", response.size() );
                if ( response.size() )
                    Hexdump( ZM_DBG_ERR, response.data(), min(response.size(),16) );
            }
            return( -1 );
        }
        if ( respCode != 200 )
        {
            Error( "Unexpected response code %d, text is '%s'", respCode, respText );
            return( -1 );
        }

        message = "POST "+mPath+" HTTP/1.0\r\n";
        message += "X-SessionCookie: "+mHttpSession+"\r\n";
        if ( !mAuth.empty() )
            message += stringtf( "Authorization: Basic %s\r\n", mAuth64.c_str() );
        message += "Content-Length: 32767\r\n";
        message += "Content-Type: application/x-rtsp-tunnelled\r\n";
        message += "\r\n";
        Debug( 2, "Sending HTTP message: %s", message.c_str() );
        if ( mRtspSocket2.send( message.c_str(), message.size() ) != (int)message.length() )
        {
            Error( "Unable to send message '%s': %s", message.c_str(), strerror(errno) );
            return( -1 );
        }
    }

    std::string localHost = "";
    int localPorts[2] = { 0, 0 };

    //message = "OPTIONS * RTSP/1.0\r\n";
    //sendCommand( message );
    //recvResponse( response );

    message = "DESCRIBE "+mUrl+" RTSP/1.0\r\n";
    sendCommand( message );
    sleep( 1 );
    recvResponse( response );

    const std::string endOfHeaders = "\r\n\r\n";
    size_t sdpStart = response.find( endOfHeaders );
    if( sdpStart == std::string::npos )
        return( -1 );
    sdpStart += endOfHeaders.length();

    std::string sdp = response.substr( sdpStart );
    Debug( 1, "Processing SDP '%s'", sdp.c_str() );

    SessionDescriptor *sessDesc = 0;
    try
    {
        sessDesc = new SessionDescriptor( mUrl, sdp );
        mFormatContext = sessDesc->generateFormatContext();
    }
    catch( const Exception &e )
    {
        Error( e.getMessage().c_str() );
        return( -1 );
    }

#if 0
    // New method using ffmpeg native functions
    std::string authUrl = mUrl;
    if ( !mAuth.empty() )
        authUrl.insert( authUrl.find( "://" )+3, mAuth+"@" );

    if ( av_open_input_file( &mFormatContext, authUrl.c_str(), NULL, 0, NULL ) != 0 )
    {
        Error( "Unable to open input '%s'", authUrl.c_str() );
        return( -1 );
    }
#endif

    uint32_t rtpClock = 0;
    std::string trackUrl = mUrl;
    if ( mFormatContext->nb_streams >= 1 )
    {
        for ( int i = 0; i < mFormatContext->nb_streams; i++ )
        {
            SessionDescriptor::MediaDescriptor *mediaDesc = sessDesc->getStream( i );
            if ( mFormatContext->streams[i]->codec->codec_type == CODEC_TYPE_VIDEO )
            {
                trackUrl += "/"+mediaDesc->getControlUrl();
                rtpClock = mediaDesc->getClock();
                // Hackery pokery
                //rtpClock = mFormatContext->streams[i]->codec->sample_rate;
                break;
            }
        }
    }

    switch( mMethod )
    {
        case RTP_UNICAST :
        {
            localPorts[0] = requestPorts();
            localPorts[1] = localPorts[0]+1;

            message = "SETUP "+trackUrl+" RTSP/1.0\r\nTransport: RTP/AVP;unicast;client_port="+stringtf( "%d", localPorts[0] )+"-"+stringtf( "%d", localPorts[1] )+"\r\n";
            break;
        }
        case RTP_MULTICAST :
        {
            message = "SETUP "+trackUrl+" RTSP/1.0\r\nTransport: RTP/AVP;multicast\r\n";
            break;
        }
        case RTP_RTSP :
        case RTP_RTSP_HTTP :
        {
            message = "SETUP "+trackUrl+" RTSP/1.0\r\nTransport: RTP/AVP/TCP;unicast\r\n";
            break;
        }
        default:
        {
            Panic( "Got unexpected method %d", mMethod );
            break;
        }
    }

    if ( !sendCommand( message ) )
        return( -1 );
    if ( !recvResponse( response ) )
        return( -1 );

    StringVector lines = split( response, "\r\n" );
    char *session = 0;
    int timeout = 0;
    char transport[256] = "";

    for ( size_t i = 0; i < lines.size(); i++ )
    {
        sscanf( lines[i].c_str(), "Session: %a[0-9a-fA-F]; timeout=%d", &session, &timeout );
        sscanf( lines[i].c_str(), "Transport: %s", transport );
    }

    if ( !session )
        Fatal( "Unable to get session identifier from response '%s'", response.c_str() );

    Debug( 2, "Got RTSP session %s, timeout %d secs", session, timeout );

    if ( !transport[0] )
        Fatal( "Unable to get transport details from response '%s'", response.c_str() );

    Debug( 2, "Got RTSP transport %s", transport );

    std::string method = "";
    int remotePorts[2] = { 0, 0 };
    int remoteChannels[2] = { 0, 0 };
    std::string distribution = "";
    unsigned long ssrc = 0;
    StringVector parts = split( transport, ";" );
    for ( size_t i = 0; i < parts.size(); i++ )
    {
        if ( parts[i] == "unicast" || parts[i] == "multicast" )
            distribution = parts[i];
        else if ( startsWith( parts[i], "server_port=" ) )
        {
            method = "RTP/UNICAST";
            StringVector subparts = split( parts[i], "=" );
            StringVector ports = split( subparts[1], "-" );
            remotePorts[0] = strtol( ports[0].c_str(), NULL, 10 );
            remotePorts[1] = strtol( ports[1].c_str(), NULL, 10 );
        }
        else if ( startsWith( parts[i], "interleaved=" ) )
        {
            method = "RTP/RTSP";
            StringVector subparts = split( parts[i], "=" );
            StringVector channels = split( subparts[1], "-" );
            remoteChannels[0] = strtol( channels[0].c_str(), NULL, 10 );
            remoteChannels[1] = strtol( channels[1].c_str(), NULL, 10 );
        }
        else if ( startsWith( parts[i], "port=" ) )
        {
            method = "RTP/MULTICAST";
            StringVector subparts = split( parts[i], "=" );
            StringVector ports = split( subparts[1], "-" );
            localPorts[0] = strtol( ports[0].c_str(), NULL, 10 );
            localPorts[1] = strtol( ports[1].c_str(), NULL, 10 );
        }
        else if ( startsWith( parts[i], "destination=" ) )
        {
            StringVector subparts = split( parts[i], "=" );
            localHost = subparts[1];
        }
        else if ( startsWith( parts[i], "ssrc=" ) )
        {
            StringVector subparts = split( parts[i], "=" );
            ssrc = strtoll( subparts[1].c_str(), NULL, 16 );
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
    if ( !sendCommand( message ) )
        return( -1 );
    if ( !recvResponse( response ) )
        return( -1 );

    lines = split( response, "\r\n" );
    char *rtpInfo = 0;
    for ( size_t i = 0; i < lines.size(); i++ )
    {
        sscanf( lines[i].c_str(), "RTP-Info: %as", &rtpInfo );
    }

    if ( !rtpInfo )
        Fatal( "Unable to get RTP Info identifier from response '%s'", response.c_str() );

    Debug( 2, "Got RTP Info %s", rtpInfo );

    int seq = 0;
    unsigned long rtpTime = 0;
    parts = split( rtpInfo, ";" );
    for ( size_t i = 0; i < parts.size(); i++ )
    {
        if ( startsWith( parts[i], "seq=" ) )
        {
            StringVector subparts = split( parts[i], "=" );
            seq = strtol( subparts[1].c_str(), NULL, 10 );
        }
        else if ( startsWith( parts[i], "rtptime=" ) )
        {
            StringVector subparts = split( parts[i], "=" );
            rtpTime = strtol( subparts[1].c_str(), NULL, 10 );
        }
    }

    Debug( 2, "RTSP Seq is %d", seq );
    Debug( 2, "RTSP Rtptime is %ld", rtpTime );

    switch( mMethod )
    {
        case RTP_UNICAST :
        {
            RtpSource *source = new RtpSource( mId, "", localPorts[0], mHost, remotePorts[0], ssrc, seq, rtpClock, rtpTime );
            mSources[ssrc] = source;
            RtpDataThread rtpDataThread( *this, *source );
            RtpCtrlThread rtpCtrlThread( *this, *source );

            rtpDataThread.start();
            rtpCtrlThread.start();

            while( !mStop )
            {
                usleep( 100000 );
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
                return( -1 );
            if ( !recvResponse( response ) )
                return( -1 );

            rtpDataThread.stop();
            rtpCtrlThread.stop();

            //rtpDataThread.kill( SIGTERM );
            //rtpCtrlThread.kill( SIGTERM );

            rtpDataThread.join();
            rtpCtrlThread.join();
         
            delete mSources[ssrc];
            mSources.clear();

            releasePorts( localPorts[0] );

            break;
        }
        case RTP_RTSP :
        case RTP_RTSP_HTTP :
        {
            RtpSource *source = new RtpSource( mId, "", remoteChannels[0], mHost, remoteChannels[0], ssrc, seq, rtpClock, rtpTime );
            mSources[ssrc] = source;
            // These never actually run
            RtpDataThread rtpDataThread( *this, *source );
            RtpCtrlThread rtpCtrlThread( *this, *source );

            Select select( double(config.http_timeout)/1000.0 );
            select.addReader( &mRtspSocket );

            Buffer buffer( ZM_NETWORK_BUFSIZ );
            time_t lastKeepalive = time(NULL);
            std::string keepaliveMessage = "OPTIONS * RTSP/1.0\r\n";
            std::string keepaliveResponse = "RTSP/1.0 200 OK\r\n";
            while ( !mStop && select.wait() >= 0 )
            {
                Select::CommsList readable = select.getReadable();
                if ( readable.size() == 0 )
                {
                    Error( "RTSP timed out" );
                    break;
                }

                static char tempBuffer[ZM_NETWORK_BUFSIZ];
                ssize_t nBytes = mRtspSocket.recv( tempBuffer, sizeof(tempBuffer) );
                buffer.append( tempBuffer, nBytes );
                Debug( 4, "Read %zd bytes on sd %d, %d total", nBytes, mRtspSocket.getReadDesc(), buffer.size() );

                while( buffer.size() > 0 )
                {
                    if ( buffer[0] == '$' )
                    {
                        unsigned char channel = buffer[1];
                        unsigned short len = ntohs( *((unsigned short *)(buffer+2)) );

                        Debug( 4, "Got %d bytes left, expecting %d byte packet on channel %d", buffer.size(), len, channel );
                        if ( buffer.size() < (len+4) )
                        {
                            Debug( 4, "Missing %zd bytes, rereading", (len+4)-nBytes );
                            break;
                        }
                        if ( channel == remoteChannels[0] )
                        {
                            Debug( 4, "Got %d bytes on data channel %d, packet length is %d", buffer.size(), channel, len );
                            Hexdump( 4, (char *)buffer, 16 );
                            rtpDataThread.recvPacket( buffer+4, len );
                            Debug( 4, "Received" );
                        }
                        else if ( channel == remoteChannels[1] )
                        {
                            len = ntohs( *((unsigned short *)(buffer+2)) );
                            Debug( 4, "Got %zd bytes on control channel %d", nBytes, channel );
                            rtpCtrlThread.recvPackets( buffer+4, len );
                        }
                        else
                        {
                            Error( "Unexpected channel selector %d in RTSP interleaved data", buffer[1] );
                            buffer.clear();
                            break;
                        }
                        buffer.consume( len+4 );
                        nBytes -= len+4;
                    }
                    else
                    {
                        if ( keepaliveResponse.compare( 0, keepaliveResponse.size(), (char *)buffer, keepaliveResponse.size() ) == 0 )
                        {
                            Debug( 4, "Got keepalive response '%s'", (char *)buffer );
                            //buffer.consume( keepaliveResponse.size() );
                            if ( char *charPtr = (char *)memchr( (char *)buffer, '$', buffer.size() ) )
                            {
                                int discardBytes = charPtr-(char *)buffer;
                                buffer -= discardBytes;
                            }
                            else
                            {
                                buffer.clear();
                            }
                        }
                        else
                        {
                            if ( char *charPtr = (char *)memchr( (char *)buffer, '$', buffer.size() ) )
                            {
                                int discardBytes = charPtr-(char *)buffer;
                                Warning( "Unexpected format RTSP interleaved data, resyncing by %d bytes", discardBytes );
                                Hexdump( -1, (char *)buffer, discardBytes );
                                buffer -= discardBytes;
                            }
                            else
                            {
                                Warning( "Unexpected format RTSP interleaved data, dumping %d bytes", buffer.size() );
                                Hexdump( -1, (char *)buffer, 32 );
                                buffer.clear();
                            }
                        }
                    }
                }
                if ( (timeout > 0) && ((time(NULL)-lastKeepalive) > (timeout-5)) )
                {
                    if ( !sendCommand( message ) )
                        return( -1 );
                    lastKeepalive = time(NULL);
                }
                buffer.tidy( 1 );
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
                return( -1 );
            if ( !recvResponse( response ) )
                return( -1 );

            delete mSources[ssrc];
            mSources.clear();

            break;
        }
        case RTP_MULTICAST :
        {
            RtpSource *source = new RtpSource( mId, localHost, localPorts[0], mHost, remotePorts[0], ssrc, seq, rtpClock, rtpTime );
            mSources[ssrc] = source;
            RtpDataThread rtpDataThread( *this, *source );
            RtpCtrlThread rtpCtrlThread( *this, *source );

            rtpDataThread.start();
            rtpCtrlThread.start();

            while( !mStop )
            {
                usleep( 100000 );
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
                return( -1 );
            if ( !recvResponse( response ) )
                return( -1 );

            rtpDataThread.stop();
            rtpCtrlThread.stop();

            rtpDataThread.join();
            rtpCtrlThread.join();
         
            delete mSources[ssrc];
            mSources.clear();

            releasePorts( localPorts[0] );
            break;
        }
        default:
        {
            Panic( "Got unexpected method %d", mMethod );
            break;
        }
    }

    return( 0 );
}

#endif // HAVE_LIBAVFORMAT
