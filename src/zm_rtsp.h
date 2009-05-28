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
// Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
// 

#ifndef ZM_RTSP_H
#define ZM_RTSP_H

#include "zm.h"
#include "zm_ffmpeg.h"
#include "zm_comms.h"
#include "zm_thread.h"
#include "zm_rtp_source.h"

#include <set>
#include <map>

class RtspThread : public Thread
{
public:
    typedef enum { RTP_UNICAST, RTP_MULTICAST, RTP_RTSP, RTP_RTSP_HTTP } RtspMethod;
    typedef enum { UNDEFINED, UNICAST, MULTICAST } RtspDist;

private:
    typedef std::set<int>    PortSet;
    typedef std::set<U32>    SsrcSet;
    typedef std::map<U32,RtpSource *>    SourceMap;

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
    std::string mUrl;
    std::string mAuth;
    std::string mAuth64;

    std::string mHttpSession;           ///< Only for RTSP over HTTP sessions

    TcpInetClient mRtspSocket;
    TcpInetClient mRtspSocket2;

    SourceMap mSources;

    AVFormatContext *mFormatContext;

    U16 mSeq;
    U32 mSession;
    U32 mSsrc;

    int mRemotePorts[2];
    int mRemoteChannels[2];
    RtspDist mDist;

    unsigned long mRtpTime; 

    bool mStop;

private:
    bool sendCommand( std::string message );
    bool recvResponse( std::string &response );

public:
    RtspThread( int id, RtspMethod method, const std::string &protocol, const std::string &host, const std::string &port, const std::string &path, const std::string &auth );
    ~RtspThread();

public:
    int requestPorts();
    void releasePorts( int port );

    bool isValidSsrc( U32 ssrc );
    bool updateSsrc( U32 ssrc, const RtpDataHeader *header );

    U32 getSsrc() const
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
};

#endif // ZM_RTSP_H
