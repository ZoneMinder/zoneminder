//
// ZoneMinder SDP Class Interface, $Date: 2009-02-16 18:21:50 +0000 (Mon, 16 Feb 2009) $, $Revision: 2765 $
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

#ifndef ZM_SDP_H
#define ZM_SDP_H

#include "zm.h"

#include "zm_utils.h"
#include "zm_exception.h"
#include "zm_ffmpeg.h"

#include <stdlib.h>

#include <string>
#include <vector>

class SessionDescriptor
{
protected:
    enum { PAYLOAD_TYPE_DYNAMIC=96 };

    struct StaticPayloadDesc
    {
        int payloadType;
        const char payloadName[6];
        enum CodecType codecType;
        enum CodecID codecId;
        int clockRate;
        int autoChannels;
    };

    struct DynamicPayloadDesc
    {
        const char payloadName[32];
        enum CodecType codecType;
        enum CodecID codecId;
        //int clockRate;
        //int autoChannels;
    };

public:
    class ConnInfo
    {
    protected:
        std::string mNetworkType;
        std::string mAddressType;
        std::string mAddress;
        int mTtl;
        int mNoAddresses;

    public:
        ConnInfo( const std::string &connInfo );
    };

    class BandInfo
    {
    protected:
        std::string mType;
        int mValue;

    public:
        BandInfo( const std::string &bandInfo );
    };

    class MediaDescriptor
    {
    protected:
        std::string mType;
        int mPort;
        int mNumPorts;
        std::string mTransport;
        int mPayloadType;

        std::string mPayloadDesc;
        std::string mControlUrl;
        double mFrameRate;
        int mClock;
        int mWidth;
        int mHeight;

        ConnInfo *mConnInfo;

    public:
        MediaDescriptor( const std::string &type, int port, int numPorts, const std::string &transport, int payloadType );

        const std::string &getType() const
        {
            return( mType );
        }
        int getPort() const
        {
            return( mPort );
        }
        int getNumPorts() const
        {
            return( mNumPorts );
        }
        const std::string &getTransport() const
        {
            return( mTransport );
        }
        const int getPayloadType() const
        {
            return( mPayloadType );
        }

        const std::string &getPayloadDesc() const
        {
            return( mPayloadDesc );
        }
        void setPayloadDesc( const std::string &payloadDesc )
        {
            mPayloadDesc = payloadDesc;
        }

        const std::string &getControlUrl() const
        {
            return( mControlUrl );
        }
        void setControlUrl( const std::string &controlUrl )
        {
            mControlUrl = controlUrl;
        }

        const int getClock() const
        {
            return( mClock );
        }
        void setClock( int clock )
        {
            mClock = clock;
        }

        void setFrameSize( int width, int height )
        {
            mWidth = width;
            mHeight = height;
        }
        int getWidth() const
        {
            return( mWidth );
        }
        int getHeight() const
        {
            return( mHeight );
        }

        const double getFrameRate() const
        {
            return( mFrameRate );
        }
        void setFrameRate( double frameRate )
        {
            mFrameRate = frameRate;
        }
    };

    typedef std::vector<MediaDescriptor *> MediaList;

protected:
    static StaticPayloadDesc smStaticPayloads[];
    static DynamicPayloadDesc smDynamicPayloads[];

protected:
    std::string mUrl;

    std::string mVersion;
    std::string mOwner;
    std::string mName;
    std::string mInfo;

    ConnInfo *mConnInfo;
    BandInfo *mBandInfo;
    std::string mTimeInfo;
    StringVector mAttributes;

    MediaList mMediaList;

public:
    SessionDescriptor( const std::string &url, const std::string &sdp );

    const std::string &getUrl() const
    {
        return( mUrl );
    }

    int getNumStreams() const
    {
        return( mMediaList.size() );
    }
    MediaDescriptor *getStream( int index )
    {
        if ( index < 0 || index >= mMediaList.size() )
            return( 0 );
        return( mMediaList[index] );
    }

    AVFormatContext *generateFormatContext() const;
};
#if 0
v=0
o=- 1239719297054659 1239719297054674 IN IP4 192.168.1.11
s=Media Presentation
e=NONE
c=IN IP4 0.0.0.0
b=AS:174
t=0 0
a=control:*
a=range:npt=now-
a=mpeg4-iod: "data:application/mpeg4-iod;base64,AoEAAE8BAf73AQOAkwABQHRkYXRhOmFwcGxpY2F0aW9uL21wZWc0LW9kLWF1O2Jhc2U2NCxBVGdCR3dVZkF4Y0F5U1FBWlFRTklCRUVrK0FBQWEyd0FBR3RzQVlCQkFFWkFwOERGUUJsQlFRTlFCVUFDN2dBQVBvQUFBRDZBQVlCQXc9PQQNAQUABAAAAAAAAAAAAAYJAQAAAAAAAAAAA0IAAkA+ZGF0YTphcHBsaWNhdGlvbi9tcGVnNC1iaWZzLWF1O2Jhc2U2NCx3QkFTZ1RBcUJYSmhCSWhRUlFVL0FBPT0EEgINAAACAAAAAAAAAAAFAwAAQAYJAQAAAAAAAAAA"
m=video 0 RTP/AVP 96
b=AS:110
a=framerate:5.0
a=control:trackID=1
a=rtpmap:96 MP4V-ES/90000
a=fmtp:96 profile-level-id=247; config=000001B0F7000001B509000001000000012008D48D8803250F042D14440F
a=mpeg4-esid:201
m=audio 0 RTP/AVP 0
b=AS:64
a=control:trackID=2
    
#endif

#endif // ZM_SDP_H
