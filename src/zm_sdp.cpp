//
// ZoneMinder SDP Class Implementation, $Date: 2009-04-14 21:20:02 +0100 (Tue, 14 Apr 2009) $, $Revision: 2850 $
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

#include "zm_sdp.h"

#if (LIBAVCODEC_VERSION_CHECK(52, 64, 0, 64, 0) || LIBAVUTIL_VERSION_CHECK(50, 14, 0, 14, 0))
SessionDescriptor::StaticPayloadDesc SessionDescriptor::smStaticPayloads[] = {
  { 0, "PCMU",   AVMEDIA_TYPE_AUDIO,   AV_CODEC_ID_PCM_MULAW,  8000,  1 },
  { 3, "GSM",    AVMEDIA_TYPE_AUDIO,   AV_CODEC_ID_NONE,     8000,  1 },
  { 4, "G723",   AVMEDIA_TYPE_AUDIO,   AV_CODEC_ID_NONE,     8000,  1 },
  { 5, "DVI4",   AVMEDIA_TYPE_AUDIO,   AV_CODEC_ID_NONE,     8000,  1 },
  { 6, "DVI4",   AVMEDIA_TYPE_AUDIO,   AV_CODEC_ID_NONE,     16000, 1 },
  { 7, "LPC",    AVMEDIA_TYPE_AUDIO,   AV_CODEC_ID_NONE,     8000,  1 },
  { 8, "PCMA",   AVMEDIA_TYPE_AUDIO,   AV_CODEC_ID_PCM_ALAW,   8000,  1 },
  { 9, "G722",   AVMEDIA_TYPE_AUDIO,   AV_CODEC_ID_NONE,     8000,  1 },
  { 10, "L16",   AVMEDIA_TYPE_AUDIO,   AV_CODEC_ID_PCM_S16BE,  44100, 2 },
  { 11, "L16",   AVMEDIA_TYPE_AUDIO,   AV_CODEC_ID_PCM_S16BE,  44100, 1 },
  { 12, "QCELP", AVMEDIA_TYPE_AUDIO,   AV_CODEC_ID_QCELP,    8000,  1 },
  { 13, "CN",    AVMEDIA_TYPE_AUDIO,   AV_CODEC_ID_NONE,     8000,  1 },
  { 14, "MPA",   AVMEDIA_TYPE_AUDIO,   AV_CODEC_ID_MP2,    -1,  -1 },
  { 14, "MPA",   AVMEDIA_TYPE_AUDIO,   AV_CODEC_ID_MP3,    -1,  -1 },
  { 15, "G728",  AVMEDIA_TYPE_AUDIO,   AV_CODEC_ID_NONE,     8000,  1 },
  { 16, "DVI4",  AVMEDIA_TYPE_AUDIO,   AV_CODEC_ID_NONE,     11025, 1 },
  { 17, "DVI4",  AVMEDIA_TYPE_AUDIO,   AV_CODEC_ID_NONE,     22050, 1 },
  { 18, "G729",  AVMEDIA_TYPE_AUDIO,   AV_CODEC_ID_NONE,     8000,  1 },
  { 25, "CelB",  AVMEDIA_TYPE_VIDEO,   AV_CODEC_ID_NONE,     90000, -1 },
  { 26, "JPEG",  AVMEDIA_TYPE_VIDEO,   AV_CODEC_ID_MJPEG,    90000, -1 },
  { 28, "nv",    AVMEDIA_TYPE_VIDEO,   AV_CODEC_ID_NONE,     90000, -1 },
  { 31, "H261",  AVMEDIA_TYPE_VIDEO,   AV_CODEC_ID_H261,     90000, -1 },
  { 32, "MPV",   AVMEDIA_TYPE_VIDEO,   AV_CODEC_ID_MPEG1VIDEO, 90000, -1 },
  { 32, "MPV",   AVMEDIA_TYPE_VIDEO,   AV_CODEC_ID_MPEG2VIDEO, 90000, -1 },
  { 33, "MP2T",  AVMEDIA_TYPE_DATA,  AV_CODEC_ID_MPEG2TS,  90000, -1 },
  { 34, "H263",  AVMEDIA_TYPE_VIDEO,   AV_CODEC_ID_H263,     90000, -1 },
  { -1, "",      AVMEDIA_TYPE_UNKNOWN, AV_CODEC_ID_NONE,     -1,  -1 }
};

SessionDescriptor::DynamicPayloadDesc SessionDescriptor::smDynamicPayloads[] = {
  { "MP4V-ES",            AVMEDIA_TYPE_VIDEO, AV_CODEC_ID_MPEG4 },
  { "mpeg4-generic",      AVMEDIA_TYPE_AUDIO, AV_CODEC_ID_AAC },
  { "H264",               AVMEDIA_TYPE_VIDEO, AV_CODEC_ID_H264 },
  { "AMR",                AVMEDIA_TYPE_AUDIO, AV_CODEC_ID_AMR_NB },
  { "vnd.onvif.metadata", AVMEDIA_TYPE_DATA, AV_CODEC_ID_NONE }
};
#else
SessionDescriptor::StaticPayloadDesc SessionDescriptor::smStaticPayloads[] = {
  { 0, "PCMU",   CODEC_TYPE_AUDIO,   CODEC_ID_PCM_MULAW,  8001,  1 },
  { 3, "GSM",    CODEC_TYPE_AUDIO,   CODEC_ID_NONE,     8000,  1 },
  { 4, "G723",   CODEC_TYPE_AUDIO,   CODEC_ID_NONE,     8000,  1 },
  { 5, "DVI4",   CODEC_TYPE_AUDIO,   CODEC_ID_NONE,     8000,  1 },
  { 6, "DVI4",   CODEC_TYPE_AUDIO,   CODEC_ID_NONE,     16000, 1 },
  { 7, "LPC",    CODEC_TYPE_AUDIO,   CODEC_ID_NONE,     8000,  1 },
  { 8, "PCMA",   CODEC_TYPE_AUDIO,   CODEC_ID_PCM_ALAW,   8000,  1 },
  { 9, "G722",   CODEC_TYPE_AUDIO,   CODEC_ID_NONE,     8000,  1 },
  { 10, "L16",   CODEC_TYPE_AUDIO,   CODEC_ID_PCM_S16BE,  44100, 2 },
  { 11, "L16",   CODEC_TYPE_AUDIO,   CODEC_ID_PCM_S16BE,  44100, 1 },
  { 12, "QCELP", CODEC_TYPE_AUDIO,   CODEC_ID_QCELP,    8000,  1 },
  { 13, "CN",    CODEC_TYPE_AUDIO,   CODEC_ID_NONE,     8000,  1 },
  { 14, "MPA",   CODEC_TYPE_AUDIO,   CODEC_ID_MP2,    -1,  -1 },
  { 14, "MPA",   CODEC_TYPE_AUDIO,   CODEC_ID_MP3,    -1,  -1 },
  { 15, "G728",  CODEC_TYPE_AUDIO,   CODEC_ID_NONE,     8000,  1 },
  { 16, "DVI4",  CODEC_TYPE_AUDIO,   CODEC_ID_NONE,     11025, 1 },
  { 17, "DVI4",  CODEC_TYPE_AUDIO,   CODEC_ID_NONE,     22050, 1 },
  { 18, "G729",  CODEC_TYPE_AUDIO,   CODEC_ID_NONE,     8000,  1 },
  { 25, "CelB",  CODEC_TYPE_VIDEO,   CODEC_ID_NONE,     90000, -1 },
  { 26, "JPEG",  CODEC_TYPE_VIDEO,   CODEC_ID_MJPEG,    90000, -1 },
  { 28, "nv",    CODEC_TYPE_VIDEO,   CODEC_ID_NONE,     90000, -1 },
  { 31, "H261",  CODEC_TYPE_VIDEO,   CODEC_ID_H261,     90000, -1 },
  { 32, "MPV",   CODEC_TYPE_VIDEO,   CODEC_ID_MPEG1VIDEO, 90000, -1 },
  { 32, "MPV",   CODEC_TYPE_VIDEO,   CODEC_ID_MPEG2VIDEO, 90000, -1 },
  { 33, "MP2T",  CODEC_TYPE_DATA,  CODEC_ID_MPEG2TS,  90000, -1 },
  { 34, "H263",  CODEC_TYPE_VIDEO,   CODEC_ID_H263,     90000, -1 },
  { -1, "",    CODEC_TYPE_UNKNOWN, CODEC_ID_NONE,     -1,  -1 }
};

SessionDescriptor::DynamicPayloadDesc SessionDescriptor::smDynamicPayloads[] = {
  { "MP4V-ES", CODEC_TYPE_VIDEO, CODEC_ID_MPEG4 },
  { "mpeg4-generic", CODEC_TYPE_AUDIO, CODEC_ID_AAC },
  { "H264", CODEC_TYPE_VIDEO, CODEC_ID_H264 },
  { "AMR", CODEC_TYPE_AUDIO, CODEC_ID_AMR_NB },
  { "vnd.onvif.metadata", CODEC_TYPE_DATA, CODEC_ID_NONE }
};
#endif

SessionDescriptor::ConnInfo::ConnInfo( const std::string &connInfo ) :
  mTtl( 16 ),
  mNoAddresses( 0 )
{
  StringVector tokens = split(connInfo, " ");
  if ( tokens.size() < 3 )
    throw Exception( "Unable to parse SDP connection info from '"+connInfo+"'" );
  mNetworkType = tokens[0];
  if ( mNetworkType != "IN" )
    throw Exception( "Invalid SDP network type '"+mNetworkType+"' in connection info '"+connInfo+"'" );
  mAddressType = tokens[1];
  if ( mAddressType != "IP4" && mAddressType != "IP6" )
    throw Exception( "Invalid SDP address type '"+mAddressType+"' in connection info '"+connInfo+"'" );
  StringVector addressTokens = split( tokens[2], "/" );
  if ( addressTokens.size() < 1 ) 
    throw Exception( "Invalid SDP address '"+tokens[2]+"' in connection info '"+connInfo+"'" );
  mAddress = addressTokens[0];
  if ( addressTokens.size() >= 2 )
    mTtl = atoi(addressTokens[1].c_str());
  if ( addressTokens.size() >= 3 )
    mNoAddresses = atoi(addressTokens[2].c_str());
}

SessionDescriptor::BandInfo::BandInfo( const std::string &bandInfo ) :
  mValue( 0 )
{
  StringVector tokens = split( bandInfo, ":" );
  if ( tokens.size() < 2 )
    throw Exception( "Unable to parse SDP bandwidth info from '"+bandInfo+"'" );
  mType = tokens[0];
  //if ( mNetworkType != "IN" )
    //throw Exception( "Invalid SDP network type '"+mNetworkType+"' in connection info '"+connInfo+"'" );
  mValue = atoi(tokens[1].c_str());
}

SessionDescriptor::MediaDescriptor::MediaDescriptor(
    const std::string &type,
    int port,
    int numPorts,
    const std::string &transport,
    int payloadType ) :
  mType( type ),
  mPort( port ),
  mNumPorts( numPorts ),
  mTransport( transport ),
  mPayloadType( payloadType ),
  mFrameRate( 0.0 ),
  mClock( 0 ),
  mWidth( 0 ),
  mHeight( 0 ),
  mSprops( "" ),
  mConnInfo( 0 )
{
}

SessionDescriptor::SessionDescriptor( const std::string &url, const std::string &sdp ) : 
  mUrl( url ),
  mConnInfo( 0 ),
  mBandInfo( 0 )
{
  MediaDescriptor *currMedia = 0;

  StringVector lines = split( sdp, "\r\n" );
  for ( StringVector::const_iterator iter = lines.begin(); iter != lines.end(); ++iter ) {
    std::string line = *iter;
    if ( line.empty() )
      break;

    Debug(3, "Processing SDP line '%s'", line.c_str());
    const char sdpType = line[0];
    if ( line[1] != '=' )
      throw Exception("Invalid SDP format at '"+line+"'");

    line.erase(0, 2);
    switch( sdpType ) {
      case 'v' :
        mVersion = line;
        break;
      case 'o' :
        mOwner = line;
        break;
      case 's' :
        mName = line;
        break;
      case 'i' :
        mInfo = line;
        break;
      case 'c' :
        // This prevent a memory leak if the field appears more than one time
        if ( mConnInfo )
          delete mConnInfo;
        mConnInfo = new ConnInfo( line );
        break;
      case 'b' :
        // This prevent a memory leak if the field appears more than one time
        if ( mBandInfo )
          delete mBandInfo;
        mBandInfo = new BandInfo( line );
        break;
      case 't' :
        mTimeInfo = line;
        break;
      case 'a' :
      {
        mAttributes.push_back( line );
        StringVector tokens = split( line, ":", 2 );
        std::string attrName = tokens[0];
        if ( currMedia ) {
          if ( attrName == "control" ) {
            if ( tokens.size() < 2 )
              throw Exception( "Unable to parse SDP control attribute '"+line+"' for media '"+currMedia->getType()+"'" );
            currMedia->setControlUrl( tokens[1] );
          } else if ( attrName == "range" ) {
          } else if ( attrName == "rtpmap" ) {
            // a=rtpmap:96 MP4V-ES/90000
            if ( tokens.size() < 2 )
              throw Exception( "Unable to parse SDP rtpmap attribute '"+line+"' for media '"+currMedia->getType()+"'" );
            StringVector attrTokens = split( tokens[1], " " );
            int payloadType = atoi(attrTokens[0].c_str());
            if ( payloadType != currMedia->getPayloadType() )
              throw Exception( stringtf( "Payload type mismatch, expected %d, got %d in '%s'", currMedia->getPayloadType(), payloadType, line.c_str() ) );
            std::string payloadDesc = attrTokens[1];
            //currMedia->setPayloadType( payloadType );
            if ( attrTokens.size() > 1 ) {
              StringVector payloadTokens = split( attrTokens[1], "/" );
              std::string payloadDesc = payloadTokens[0];
              int payloadClock = atoi(payloadTokens[1].c_str());
              currMedia->setPayloadDesc( payloadDesc );
              currMedia->setClock( payloadClock );
            }
          } else if ( attrName == "framesize" ) {
            // a=framesize:96 320-240
            if ( tokens.size() < 2 )
              throw Exception("Unable to parse SDP framesize attribute '"+line+"' for media '"+currMedia->getType()+"'");
            StringVector attrTokens = split(tokens[1], " ");
            int payloadType = atoi(attrTokens[0].c_str());
            if ( payloadType != currMedia->getPayloadType() )
              throw Exception( stringtf("Payload type mismatch, expected %d, got %d in '%s'",
                    currMedia->getPayloadType(), payloadType, line.c_str()));
            //currMedia->setPayloadType( payloadType );
            StringVector sizeTokens = split(attrTokens[1], "-");
            int width = atoi(sizeTokens[0].c_str());
            int height = atoi(sizeTokens[1].c_str());
            currMedia->setFrameSize(width, height);
          } else if ( attrName == "framerate" ) {
            // a=framerate:5.0
            if ( tokens.size() < 2 )
              throw Exception("Unable to parse SDP framerate attribute '"+line+"' for media '"+currMedia->getType()+"'");
            double frameRate = atof(tokens[1].c_str());
            currMedia->setFrameRate(frameRate);
          } else if ( attrName == "fmtp" ) {
            // a=fmtp:96 profile-level-id=247; config=000001B0F7000001B509000001000000012008D48D8803250F042D14440F
            if ( tokens.size() < 2 )
              throw Exception("Unable to parse SDP fmtp attribute '"+line+"' for media '"+currMedia->getType()+"'");
            StringVector attrTokens = split(tokens[1], " ", 2);
            int payloadType = atoi(attrTokens[0].c_str());
            if ( payloadType != currMedia->getPayloadType() )
              throw Exception(stringtf("Payload type mismatch, expected %d, got %d in '%s'",
                    currMedia->getPayloadType(), payloadType, line.c_str()));
            //currMedia->setPayloadType( payloadType );
            if ( attrTokens.size() > 1 ) {
              StringVector attr2Tokens = split( attrTokens[1], "; " );
              for ( unsigned int i = 0; i < attr2Tokens.size(); i++ ) {
                StringVector attr3Tokens = split( attr2Tokens[i], "=" );
                //Info( "Name = %s, Value = %s", attr3Tokens[0].c_str(), attr3Tokens[1].c_str() );
                if ( attr3Tokens[0] == "profile-level-id" ) {
                } else if ( attr3Tokens[0] == "config" ) {
                } else if ( attr3Tokens[0] == "sprop-parameter-sets" ) {
                    size_t t = attr2Tokens[i].find("=");
                    char *c = (char *)attr2Tokens[i].c_str() + t + 1;
                    Debug(4, "sprop-parameter-sets value %s", c);
                  currMedia->setSprops(std::string(c));
                } else {
                  Debug( 3, "Ignoring SDP fmtp attribute '%s' for media '%s'", attr3Tokens[0].c_str(), currMedia->getType().c_str() )
                }
              }
            }
          } else if ( attrName == "mpeg4-iod" ) {
            // a=mpeg4-iod: "data:application/mpeg4-iod;base64,AoEAAE8BAf73AQOAkwABQHRkYXRhOmFwcGxpY2F0aW9uL21wZWc0LW9kLWF1O2Jhc2U2NCxBVGdCR3dVZkF4Y0F5U1FBWlFRTklCRUVrK0FBQWEyd0FBR3RzQVlCQkFFWkFwOERGUUJsQlFRTlFCVUFDN2dBQVBvQUFBRDZBQVlCQXc9PQQNAQUABAAAAAAAAAAAAAYJAQAAAAAAAAAAA0IAAkA+ZGF0YTphcHBsaWNhdGlvbi9tcGVnNC1iaWZzLWF1O2Jhc2U2NCx3QkFTZ1RBcUJYSmhCSWhRUlFVL0FBPT0EEgINAAACAAAAAAAAAAAFAwAAQAYJAQAAAAAAAAAA"
          } else if ( attrName == "mpeg4-esid" ) {
            // a=mpeg4-esid:201
          } else {
            Debug(3, "Ignoring SDP attribute '%s' for media '%s'", line.c_str(), currMedia->getType().c_str());
          }
        } else {
          Debug(3, "Ignoring general SDP attribute '%s'", line.c_str());
        }
        break;
      }
      case 'm' :
      {
        StringVector tokens = split(line, " ");
        if ( tokens.size() < 4 )
          throw Exception("Can't parse SDP media description '"+line+"'");
        std::string mediaType = tokens[0];
        if ( mediaType != "audio" && mediaType != "video"  && mediaType != "application" )
          throw Exception("Unsupported media type '"+mediaType+"' in SDP media attribute '"+line+"'");
        StringVector portTokens = split(tokens[1], "/");
        int mediaPort = atoi(portTokens[0].c_str());
        int mediaNumPorts = 1;
        if ( portTokens.size() > 1 )
          mediaNumPorts = atoi(portTokens[1].c_str());
        std::string mediaTransport = tokens[2];
        if ( mediaTransport != "RTP/AVP" )
          throw Exception("Unsupported media transport '"+mediaTransport+"' in SDP media attribute '"+line+"'");
        int payloadType = atoi(tokens[3].c_str());
        currMedia = new MediaDescriptor(mediaType, mediaPort, mediaNumPorts, mediaTransport, payloadType);
        mMediaList.push_back(currMedia);
        break;
      }
    } // end switch
  } // end foreach line
}

SessionDescriptor::~SessionDescriptor() {
  if ( mConnInfo )
    delete mConnInfo;
  if ( mBandInfo )
    delete mBandInfo;
  for ( unsigned int i = 0; i < mMediaList.size(); i++ )
    delete mMediaList[i];
}

AVFormatContext *SessionDescriptor::generateFormatContext() const {
  AVFormatContext *formatContext = avformat_alloc_context();

#if (LIBAVFORMAT_VERSION_CHECK(58, 12, 0, 0, 100))
  formatContext->url = av_strdup(mUrl.c_str());
#else
  strncpy(formatContext->filename, mUrl.c_str(), sizeof(formatContext->filename));
#endif
/*
  if ( mName.length() )
    strncpy( formatContext->title, mName.c_str(), sizeof(formatContext->title) );
  if ( mInfo.length() )
    strncpy( formatContext->comment, mInfo.c_str(), sizeof(formatContext->comment) );
*/
  //formatContext->nb_streams = mMediaList.size();
  for ( unsigned int i = 0; i < mMediaList.size(); i++ ) {
    const MediaDescriptor *mediaDesc = mMediaList[i];
#if !LIBAVFORMAT_VERSION_CHECK(53, 10, 0, 17, 0)
    AVStream *stream = av_new_stream(formatContext, i);
#else
    AVStream *stream = avformat_new_stream(formatContext, NULL);
    stream->id = i;
#endif

#if LIBAVCODEC_VERSION_CHECK(57, 64, 0, 64, 0)
    AVCodecContext *codec_context = avcodec_alloc_context3(NULL);
    avcodec_parameters_to_context(codec_context, stream->codecpar);
    stream->codec = codec_context;
#else
    AVCodecContext *codec_context = stream->codec;
#endif

    std::string type = mediaDesc->getType();
    Debug(1, "Looking for codec for %s payload type %d / %s",
        type.c_str(), mediaDesc->getPayloadType(), mediaDesc->getPayloadDesc().c_str());
#if (LIBAVCODEC_VERSION_CHECK(52, 64, 0, 64, 0) || LIBAVUTIL_VERSION_CHECK(50, 14, 0, 14, 0))
    if ( type == "video" )
      codec_context->codec_type = AVMEDIA_TYPE_VIDEO;
    else if ( type == "audio" )
      codec_context->codec_type = AVMEDIA_TYPE_AUDIO;
    else if ( type == "application" )
      codec_context->codec_type = AVMEDIA_TYPE_DATA;
#else
    if ( type == "video" )
      codec_context->codec_type = CODEC_TYPE_VIDEO;
    else if ( type == "audio" )
      codec_context->codec_type = CODEC_TYPE_AUDIO;
    else if ( type == "application" )
      codec_context->codec_type = CODEC_TYPE_DATA;
#endif
    else
      Warning("Unknown media_type %s", type.c_str());

#if LIBAVCODEC_VERSION_CHECK(55, 50, 3, 60, 103)
    std::string codec_name;
#endif
    if ( mediaDesc->getPayloadType() < PAYLOAD_TYPE_DYNAMIC ) {
      // Look in static table
      for ( unsigned int i = 0; i < (sizeof(smStaticPayloads)/sizeof(*smStaticPayloads)); i++ ) {
        if ( smStaticPayloads[i].payloadType == mediaDesc->getPayloadType() ) {
          Debug( 1, "Got static payload type %d, %s", smStaticPayloads[i].payloadType, smStaticPayloads[i].payloadName );
#if LIBAVCODEC_VERSION_CHECK(55, 50, 3, 60, 103)
          codec_name = std::string(smStaticPayloads[i].payloadName);
#else
          strncpy(codec_context->codec_name, smStaticPayloads[i].payloadName, sizeof(codec_context->codec_name));
#endif
          codec_context->codec_type = smStaticPayloads[i].codecType;
          codec_context->codec_id = smStaticPayloads[i].codecId;
          codec_context->sample_rate = smStaticPayloads[i].clockRate;
          break;
        }
      }
    } else {
      // Look in dynamic table
      for ( unsigned int i = 0; i < (sizeof(smDynamicPayloads)/sizeof(*smDynamicPayloads)); i++ ) {
        if ( smDynamicPayloads[i].payloadName == mediaDesc->getPayloadDesc() ) {
          Debug(1, "Got dynamic payload type %d, %s", mediaDesc->getPayloadType(), smDynamicPayloads[i].payloadName);
#if LIBAVCODEC_VERSION_CHECK(55, 50, 3, 60, 103)
          codec_name = std::string(smStaticPayloads[i].payloadName);
#else
          strncpy(codec_context->codec_name, smDynamicPayloads[i].payloadName, sizeof(codec_context->codec_name));
#endif
          codec_context->codec_type = smDynamicPayloads[i].codecType;
          codec_context->codec_id = smDynamicPayloads[i].codecId;
          codec_context->sample_rate = mediaDesc->getClock();
          break;
        }
      }
    } /// end if static or dynamic

#if LIBAVCODEC_VERSION_CHECK(55, 50, 3, 60, 103)
    if ( codec_name.empty() )
#else
    if ( !stream->codec->codec_name[0] )
#endif
    {
      Warning( "Can't find payload details for %s payload type %d, name %s",
          mediaDesc->getType().c_str(), mediaDesc->getPayloadType(), mediaDesc->getPayloadDesc().c_str() );
      //return( 0 );
    }
    if ( mediaDesc->getWidth() )
      codec_context->width = mediaDesc->getWidth();
    if ( mediaDesc->getHeight() )
      codec_context->height = mediaDesc->getHeight();
    if ( codec_context->codec_id == AV_CODEC_ID_H264 && mediaDesc->getSprops().size()) {
      uint8_t start_sequence[]= { 0, 0, 1 };
      codec_context->extradata_size= 0;
      codec_context->extradata= NULL;
      char pvalue[1024], *value = pvalue;
    
      strcpy(pvalue, mediaDesc->getSprops().c_str());
    
      while (*value) {
        char base64packet[1024];
        uint8_t decoded_packet[1024];
        uint32_t packet_size;
        char *dst = base64packet;

        while (*value && *value != ','
             && (dst - base64packet) < (long)(sizeof(base64packet)) - 1) {
          *dst++ = *value++;
        }
        *dst++ = '\0';

        if ( *value == ',' )
          value++;

        packet_size= av_base64_decode(decoded_packet, (const char *)base64packet, (int)sizeof(decoded_packet));
        Hexdump(4, (char *)decoded_packet, packet_size);
        if (packet_size) {
          uint8_t *dest = 
          (uint8_t *)av_malloc(packet_size + sizeof(start_sequence) +
                       codec_context->extradata_size +
#if LIBAVCODEC_VERSION_CHECK(57, 0, 0, 0, 0)
                       AV_INPUT_BUFFER_PADDING_SIZE
#else
                       FF_INPUT_BUFFER_PADDING_SIZE
#endif
);
          if ( dest ) {
            if ( codec_context->extradata_size ) {
              // av_realloc?
              memcpy(dest, codec_context->extradata, codec_context->extradata_size);
              av_free(codec_context->extradata);
            }

            memcpy(dest+codec_context->extradata_size, start_sequence, sizeof(start_sequence));
            memcpy(dest+codec_context->extradata_size+sizeof(start_sequence), decoded_packet, packet_size);
            memset(dest+codec_context->extradata_size+sizeof(start_sequence)+
                packet_size, 0,
#if LIBAVCODEC_VERSION_CHECK(57, 0, 0, 0, 0)
                AV_INPUT_BUFFER_PADDING_SIZE
#else
                FF_INPUT_BUFFER_PADDING_SIZE
#endif
                );

              codec_context->extradata= dest;
              codec_context->extradata_size+= sizeof(start_sequence)+packet_size;
//          } else {
//            av_log(codec, AV_LOG_ERROR, "Unable to allocate memory for extradata!");
//            return AVERROR(ENOMEM);
          }
        }
      }
    }
  }

  return formatContext;
}

#endif // HAVE_LIBAVFORMAT
