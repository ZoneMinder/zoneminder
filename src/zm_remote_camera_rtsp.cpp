//
// ZoneMinder Remote Camera Class Implementation, $Date: 2007-08-29 18:34:33 +0100 (Wed, 29 Aug 2007) $, $Revision: 2168 $
// Copyright (C) 2003, 2004, 2005, 2006  Philip Coombes
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

#include "zm_remote_camera_rtsp.h"

#include "zm_mem_utils.h"
#include "zm_sdp.h"

#include <sys/types.h>
#include <sys/socket.h>

RemoteCameraRtsp::RemoteCameraRtsp( int p_id, const std::string &p_method, const std::string &p_host, const std::string &p_port, const std::string &p_path, const std::string &p_subpath, int p_width, int p_height, int p_palette, int p_brightness, int p_contrast, int p_hue, int p_colour, bool p_capture ) :
    RemoteCamera( p_id, "rtsp", p_host, p_port, p_path, p_subpath, p_width, p_height, p_palette, p_brightness, p_contrast, p_hue, p_colour, p_capture ),
    rtspThread( 0 )
{
    if ( p_method == "rtpUni" )
        method = RtspThread::RTP_UNICAST;
    else if ( p_method == "rtpMulti" )
        method = RtspThread::RTP_MULTICAST;
    else if ( p_method == "rtpRtsp" )
        method = RtspThread::RTP_RTSP;
    else if ( p_method == "rtRtspHttp" )
        method = RtspThread::RTP_RTSP_HTTP;
    else
        Fatal( "Unrecognised method '%s' when creating RTSP camera %d", p_method.c_str(), id );

	if ( capture )
	{
		Initialise();
	}
}

RemoteCameraRtsp::~RemoteCameraRtsp()
{
	if ( capture )
	{
		Terminate();
	}
}

void RemoteCameraRtsp::Initialise()
{
    RemoteCamera::Initialise();

	int max_size = width*height*colours;

	buffer.Size( max_size );

    if ( zm_dbg_level > ZM_DBG_INF )
        av_log_set_level(99); 

    /* must be called before using avcodec lib */
    avcodec_init();

    /* register all the codecs */
    avcodec_register_all();

    av_register_rtp_dynamic_payload_handlers();

    frameCount = 0;

    Connect();
}

void RemoteCameraRtsp::Terminate()
{
    avcodec_close( codecContext );
    av_free( codecContext );
    av_free( picture );

    Disconnect();
}

int RemoteCameraRtsp::Connect()
{
    rtspThread = new RtspThread( id, method, protocol, host, port, path, subpath, auth64 );

    rtspThread->start();

    return( 0 );
}

int RemoteCameraRtsp::Disconnect()
{
    if ( rtspThread )
    {
        rtspThread->stop();
        rtspThread->join();
        delete rtspThread;
        rtspThread = 0;
    }
    return( 0 );
}

int RemoteCameraRtsp::PrimeCapture()
{
    Debug( 2, "Waiting for sources" );
    while( !rtspThread->hasSources() )
    {
        usleep( 10000 );
    }
    Debug( 2, "Got sources" );

    formatContext = rtspThread->getFormatContext();

    // Find the first video stream
    int videoStream=-1;
    for ( int i = 0; i < formatContext->nb_streams; i++ )
        if ( formatContext->streams[i]->codec->codec_type == CODEC_TYPE_VIDEO )
        {
            videoStream = i;
            break;
        }
    if ( videoStream == -1 )
        Fatal( "Unable to locate video stream" );

    // Get a pointer to the codec context for the video stream
    codecContext = formatContext->streams[videoStream]->codec;

    // Find the decoder for the video stream
    codec = avcodec_find_decoder( codecContext->codec_id );
    if ( codec == NULL )
        Fatal( "Unable to locate codec %d decoder", codecContext->codec_id );

    // Open codec
    if ( avcodec_open( codecContext, codec ) < 0 )
        Fatal( "Can't open codec" );

    picture = avcodec_alloc_frame();

    return( 0 );
}

int RemoteCameraRtsp::PreCapture()
{
    if ( !rtspThread->hasSources() )
    {
        Error( "Cannot precapture, no RTP sources" );
        return( -1 );
    }
    return( 0 );
}

int RemoteCameraRtsp::PostCapture( Image &image )
{
    buffer.Empty();
    if ( rtspThread->getFrame( buffer ) )
    {
        Debug( 3, "Read frame %d bytes", buffer.Size() );
        Debug( 4, "Address %p", buffer.Head() );
        Hexdump( 4, buffer.Head(), 16 );

        static AVFrame *tmp_picture = NULL;

        if ( !tmp_picture )
        {
            //if ( c->pix_fmt != pf )
            //{
                tmp_picture = avcodec_alloc_frame();
                if ( !tmp_picture )
                {
                    Fatal( "Could not allocate temporary opicture" );
                }
                int size = avpicture_get_size( PIX_FMT_RGB24, width, height);
                uint8_t *tmp_picture_buf = (uint8_t *)malloc(size);
                if (!tmp_picture_buf)
                {
                    av_free( tmp_picture );
                    Fatal( "Could not allocate temporary opicture" );
                }
                avpicture_fill( (AVPicture *)tmp_picture, tmp_picture_buf, PIX_FMT_RGB24, width, height );
            //}
        }

        if ( buffer.Size() )
        {
            int initialFrameCount = frameCount;
            while ( buffer.Size() > 0 )
            {
                int got_picture = false;
                int len = avcodec_decode_video( codecContext, picture, &got_picture, buffer.Head(), buffer.Size() );
                if ( len < 0 )
                {
                    if ( frameCount > initialFrameCount )
                    {
                        // Decoded at least one frame
                        return( 0 );
                    }
                    Error( "Error while decoding frame %d", frameCount );
                    Hexdump( 0, buffer.Head(), buffer.Size() );
                    return( -1 );
                }
                Debug( 2, "Frame: %d: %d/%d", frameCount, len, buffer.Size() );
                //if ( buffer.Size() < 400 )
                    //Hexdump( 0, buffer.Head(), buffer.Size() );

                if ( got_picture )
                {
                    /* the picture is allocated by the decoder. no need to free it */
                    Debug( 1, "Got picture %d", frameCount );

                    static struct SwsContext *img_convert_ctx = 0;

                    if ( !img_convert_ctx )
                    {
                        img_convert_ctx = sws_getContext( codecContext->width, codecContext->height, codecContext->pix_fmt, width, height, PIX_FMT_RGB24, SWS_BICUBIC, NULL, NULL, NULL );
                        if ( !img_convert_ctx )
                            Fatal( "Unable to initialise image scaling context" );
                    }

                    sws_scale( img_convert_ctx, picture->data, picture->linesize, 0, height, tmp_picture->data, tmp_picture->linesize );

			        image.Assign( width, height, colours, tmp_picture->data[0] );

                    frameCount++;
                }
                else
                {
                    Warning( "Unable to get picture from frame" );
                }
                buffer -= len;
            }
        }
        else
        {
            return( -1 );
        }
    }
    return( 0 );
}

