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

#include "zm_rtsp.h"

#include "zm_rtp_data.h"
#include "zm_rtp_ctrl.h"
#include "zm_db.h"

#include <sys/time.h>
#include <stdlib.h>
#include <errno.h>

int RtspThread::mMinDataPort = 0;
int RtspThread::mMaxDataPort = 0;
RtspThread::PortSet  RtspThread::mAssignedPorts;

bool RtspThread::sendCommand( std::string message )
{
    if ( !mAuth.empty() )
        message += stringtf( "Authorization: Basic %s\r\n", mAuth.c_str() );
    message += stringtf( "CSeq: %d\r\n\r\n", ++mSeq );
    Debug( 4, "Sending RTSP message: %s", message.c_str() );
    if ( mMethod == RTP_RTSP_HTTP )
    {
        message = base64Encode( message );
        Debug( 4, "Sending encoded RTSP message: %s", message.c_str() );
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
    Debug( 4, "Received RTSP response: %s (%d bytes)", response.c_str(), response.size() );
    float respVer = 0;
    int respCode = -1;
    char respText[256];
    if ( sscanf( response.c_str(), "RTSP/%f %3d %[^\r\n]\r\n", &respVer, &respCode, respText ) != 3 )
    {
        Error( "Response parse failure in '%s'", response.c_str() );
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
    if ( !mMinDataPort )
    {
        char sql[BUFSIZ];
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
        for( int i = 0; MYSQL_ROW dbrow = mysql_fetch_row( result ); i++ )
        {
            int id = atoi(dbrow[0]);
            if ( mId == id )
            {
                position = i;
                break;
            }
        }
        int portRange = int(((config.max_rtp_port-config.min_rtp_port)+1)/nMonitors);
        mMinDataPort = config.min_rtp_port + (position * portRange);
        mMaxDataPort = mMinDataPort + portRange - 1;
        Debug( 2, "Assigned RTP port range is %d-%d", mMinDataPort, mMaxDataPort );
    }
    for ( int i = mMinDataPort; i <= mMaxDataPort; i++ )
    {
        PortSet::const_iterator iter = mAssignedPorts.find( i );
        if ( iter == mAssignedPorts.end() )
        {
            mAssignedPorts.insert( i );
            return( i );
        }
    }
    Fatal( "Can assign RTP port, no ports left in pool" );
    return( -1 );
}

void RtspThread::releasePorts( int port )
{
    if ( port > 0 )
        mAssignedPorts.erase( port );
}

RtspThread::RtspThread( int id, RtspMethod method, const std::string &protocol, const std::string &host, const std::string &port, const std::string &path, const std::string &subpath, const std::string &auth ) :
    mId( id ),
    mMethod( method ),
    mProtocol( protocol ),
    mHost( host ),
    mPort( port ),
    mPath( path ),
    mSubpath( subpath ),
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
    mFormatContext = av_alloc_format_context();

    mSsrc = rand();

    Debug( 2, "RTSP Local SSRC is %lx", mSsrc );

    if ( mMethod == RTP_RTSP_HTTP )
        mHttpSession = stringtf( "%d", rand() );
}

RtspThread::~RtspThread()
{
}

int RtspThread::run()
{
    std::string message;
    std::string response;

    response.reserve( BUFSIZ );

    if ( !mRtspSocket.connect( mHost.c_str(), strtol( mPort.c_str(), NULL, 10 ) ) )
        Fatal( "Unable to connect RTSP socket" );

    if ( mMethod == RTP_RTSP_HTTP )
    {
        if ( !mRtspSocket2.connect( mHost.c_str(), strtol( mPort.c_str(), NULL, 10 ) ) )
            Fatal( "Unable to connect auxiliary RTSP/HTTP socket" );

        message = "GET "+mPath+" HTTP/1.0\r\n";
        message += "x-sessioncookie: "+mHttpSession+"\r\n";
        if ( !mAuth.empty() )
            message += stringtf( "Authorization: Basic %s\r\n", mAuth.c_str() );
        message += "\r\n";
        Debug( 4, "Sending HTTP message: %s", message.c_str() );
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

        Debug( 4, "Received HTTP response: %s (%d bytes)", response.c_str(), response.size() );
        float respVer = 0;
        int respCode = -1;
        char respText[256];
        if ( sscanf( response.c_str(), "HTTP/%f %3d %[^\r\n]\r\n", &respVer, &respCode, respText ) != 3 )
        {
            Error( "Response parse failure in '%s'", response.c_str() );
            return( -1 );
        }
        if ( respCode != 200 )
        {
            Error( "Unexpected response code %d, text is '%s'", respCode, respText );
            return( -1 );
        }

        message = "POST "+mPath+" HTTP/1.0\r\n";
        message += "x-sessioncookie: "+mHttpSession+"\r\n";
        if ( !mAuth.empty() )
            message += stringtf( "Authorization: Basic %s\r\n", mAuth.c_str() );
        message += "Content-Length: 32767\r\n";
        message += "Content-Type: application/x-rtsp-tunnelled\r\n";
        message += "\r\n";
        Debug( 4, "Sending HTTP message: %s", message.c_str() );
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

    RTSPState *rtsp_st = new RTSPState;
    rtsp_st->nb_rtsp_streams = 0;
    rtsp_st->rtsp_streams = NULL;
    mFormatContext->priv_data = rtsp_st;

    // initialize our format context from the sdp description.
    sdp_parse( mFormatContext, response.c_str() );

    U32 rtpClock = 0;
    std::string trackUrl = mUrl;
    if ( mFormatContext->nb_streams >= 1 )
    {
        for ( int i = 0; i < mFormatContext->nb_streams; i++ )
        {
            if ( mFormatContext->streams[i]->codec->codec_type == CODEC_TYPE_VIDEO )
            {
                trackUrl += mSubpath+stringtf( "%d", i+1 );
                // Hackery pokery
                rtpClock = mFormatContext->streams[i]->codec->sample_rate;
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

            message = "SETUP "+trackUrl+" RTSP/1.0\r\nBandwidth: 8000\r\nTransport: RTP/AVP;unicast;client_port="+stringtf( "%d", localPorts[0] )+"-"+stringtf( "%d", localPorts[1] )+"\r\n";
            break;
        }
        case RTP_MULTICAST :
        {
            message = "SETUP "+trackUrl+" RTSP/1.0\r\nBandwidth: 8000\r\nTransport: RTP/AVP;multicast\r\n";
            break;
        }
        case RTP_RTSP :
        case RTP_RTSP_HTTP :
        {
            message = "SETUP "+trackUrl+" RTSP/1.0\r\nBandwidth: 8000\r\nTransport: RTP/AVP/TCP;unicast\r\n";
            break;
        }
        default:
        {
            Fatal( "Got unexpected method %d", mMethod );
            break;
        }
    }

    sendCommand( message );
    recvResponse( response );

    StringVector lines = split( response, "\r\n" );
    char *session = 0;
    int timeout = 0;
    char transport[256] = "";

    for ( size_t i = 0; i < lines.size(); i++ )
    {
        sscanf( lines[i].c_str(), "Session: %a[0-9]; timeout=%d", &session, &timeout );
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
            ssrc = strtol( subparts[1].c_str(), NULL, 16 );
        }
    }

    Debug( 2, "RTSP Method is %s", method.c_str() );
    Debug( 2, "RTSP Distribution is %s", distribution.c_str() );
    Debug( 2, "RTSP SSRC is %lx", ssrc );
    Debug( 2, "RTSP Local Host is %s", localHost.c_str() );
    Debug( 2, "RTSP Local Ports are %d/%d", localPorts[0], localPorts[1] );
    Debug( 2, "RTSP Remote Ports are %d/%d", remotePorts[0], remotePorts[1] );
    Debug( 2, "RTSP Remote Channels are %d/%d", remoteChannels[0], remoteChannels[1] );

    message = "PLAY "+trackUrl+" RTSP/1.0\r\nSession: "+session+"\r\n";
    sendCommand( message );
    recvResponse( response );

    lines = split( response, "\r\n" );
    char *rtpInfo = 0;
    for ( size_t i = 0; i < lines.size(); i++ )
    {
        sscanf( lines[i].c_str(), "RTP-Info: %as", &rtpInfo );
    }

    if ( !rtpInfo )
        Fatal( "Unable to get RTP Info identifier from response '%s'", response.c_str() );

    Debug( 2, "Got RTP Info %s", rtpInfo );

    unsigned short seq = 0;
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

            message = "PAUSE "+mUrl+" RTSP/1.0\r\nSession: "+session+"\r\n";
            sendCommand( message );
            recvResponse( response );

            message = "TEARDOWN "+mUrl+" RTSP/1.0\r\nSession: "+session+"\r\n";
            sendCommand( message );
            recvResponse( response );

            rtpDataThread.stop();
            rtpCtrlThread.stop();

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

            Select select( 1 );
            select.addReader( &mRtspSocket );

            unsigned char buffer[10*BUFSIZ];
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

                ssize_t nBytes = mRtspSocket.recv( buffer, sizeof(buffer) );
                Debug( 4, "Read %d bytes on sd %d", nBytes, mRtspSocket.getReadDesc() );

                unsigned char *bufferPtr = buffer;
                while( nBytes > 0 )
                {
                    if ( bufferPtr[0] == '$' )
                    {
                        unsigned char channel = bufferPtr[1];
                        unsigned short len = ntohs( *((unsigned short *)(bufferPtr+2)) );

                        while ( nBytes < (len+4) )
                        {
                            Warning( "Missing %d bytes, rereading", (len+4)-nBytes );
                            ssize_t oldNbytes = nBytes;
                            nBytes += mRtspSocket.recv( buffer+nBytes, sizeof(buffer)-nBytes );
                            Debug( 4, "Read additional bytes on sd %d, total is %d", mRtspSocket.getReadDesc(), nBytes );
                            Warning( "Read additional %d bytes on sd %d, new total is %d, len is %d", nBytes-oldNbytes, mRtspSocket.getReadDesc(), nBytes, len );
                        }
                        if ( channel == remoteChannels[0] )
                        {
                            Debug( 4, "Got %d bytes on channel %d, %d", nBytes, bufferPtr[1], len );
                            Hexdump( 4, bufferPtr, 16 );
                            rtpDataThread.recvPacket( bufferPtr+4, len );
                        }
                        else if ( channel == remoteChannels[1] )
                        {
                            len = ntohs( *((unsigned short *)(bufferPtr+2)) );
                            Debug( 4, "Got %d bytes on channel %d", nBytes, bufferPtr[1] );
                            rtpCtrlThread.recvPackets( bufferPtr+4, len );
                        }
                        else
                        {
                            Error( "Unexpected channel selector %d in RTSP interleaved data", bufferPtr[1] );
                            break;
                        }
                        bufferPtr += len+4;
                        nBytes -= len+4;
                    }
                    else
                    {
                        if ( keepaliveResponse.compare( 0, keepaliveResponse.size(), (char *)bufferPtr, keepaliveResponse.size() ) != 0 )
                        {
                            Warning( "Unexpected format RTSP interleaved data" );
                            Hexdump( -1, bufferPtr, 32 );
                        }
                        break;
                    }
                }
                if ( (timeout > 0) && ((time(NULL)-lastKeepalive) > (timeout-5)) )
                {
                    sendCommand( keepaliveMessage );
                    lastKeepalive = time(NULL);
                }
            }

            message = "PAUSE "+mUrl+" RTSP/1.0\r\nSession: "+session+"\r\n";
            sendCommand( message );
            recvResponse( response );

            message = "TEARDOWN "+mUrl+" RTSP/1.0\r\nSession: "+session+"\r\n";
            sendCommand( message );
            recvResponse( response );

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

            message = "PAUSE "+mUrl+" RTSP/1.0\r\nSession: "+session+"\r\n";
            sendCommand( message );
            recvResponse( response );

            message = "TEARDOWN "+mUrl+" RTSP/1.0\r\nSession: "+session+"\r\n";
            sendCommand( message );
            recvResponse( response );

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
            Fatal( "Got unexpected method %d", mMethod );
            break;
        }
    }

    return( 0 );
}
