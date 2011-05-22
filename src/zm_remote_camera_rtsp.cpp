//
// ZoneMinder Remote Camera Class Implementation, $Date$, $Revision$
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

#include "zm_remote_camera_rtsp.h"
#include "zm_ffmpeg.h"
#include "zm_mem_utils.h"

#include <sys/types.h>
#include <sys/socket.h>

RemoteCameraRtsp::RemoteCameraRtsp( int p_id, const std::string &p_method, const std::string &p_host, const std::string &p_port, const std::string &p_path, int p_width, int p_height, int p_colours, int p_brightness, int p_contrast, int p_hue, int p_colour, bool p_capture ) :
    RemoteCamera( p_id, "rtsp", p_host, p_port, p_path, p_width, p_height, p_colours, p_brightness, p_contrast, p_hue, p_colour, p_capture ),
    rtspThread( 0 )
{
    if ( p_method == "rtpUni" )
        method = RtspThread::RTP_UNICAST;
    else if ( p_method == "rtpMulti" )
        method = RtspThread::RTP_MULTICAST;
    else if ( p_method == "rtpRtsp" )
        method = RtspThread::RTP_RTSP;
    else if ( p_method == "rtpRtspHttp" )
        method = RtspThread::RTP_RTSP_HTTP;
    else
        Fatal( "Unrecognised method '%s' when creating RTSP camera %d", p_method.c_str(), id );

	if ( capture )
	{
		Initialise();
	}
	
	mFormatContext = NULL;
	mVideoStreamId = -1;
	mCodecContext = NULL;
	mCodec = NULL;
	mRawFrame = NULL;
	mFrame = NULL;
	frameCount = 0;
	
#if HAVE_LIBSWSCALE    
	mConvertContext = NULL;
#endif
	/* Has to be located inside the constructor so other components such as zma will receive correct colours and subpixel order */
	if(colours == ZM_COLOUR_RGB32) {
		subpixelorder = ZM_SUBPIX_ORDER_RGBA;
		imagePixFormat = PIX_FMT_RGBA;
	} else if(colours == ZM_COLOUR_RGB24) {
		subpixelorder = ZM_SUBPIX_ORDER_RGB;
		imagePixFormat = PIX_FMT_RGB24;
	} else if(colours == ZM_COLOUR_GRAY8) {
		subpixelorder = ZM_SUBPIX_ORDER_NONE;
		imagePixFormat = PIX_FMT_GRAY8;
	} else {
		Panic("Unexpected colours: %d",colours);
	}
	
}

RemoteCameraRtsp::~RemoteCameraRtsp()
{
    av_freep( &mFrame );
    av_freep( &mRawFrame );
    
#if HAVE_LIBSWSCALE
    if ( mConvertContext )
    {
        sws_freeContext( mConvertContext );
        mConvertContext = NULL;
    }
#endif

    if ( mCodecContext )
    {
       avcodec_close( mCodecContext );
       mCodecContext = NULL; // Freed by av_close_input_file
    }

	if ( capture )
	{
		Terminate();
	}
}

void RemoteCameraRtsp::Initialise()
{
    RemoteCamera::Initialise();

	int max_size = width*height*colours;

	buffer.size( max_size );

    if ( logDebugging() )
        av_log_set_level( AV_LOG_DEBUG ); 
    else
        av_log_set_level( AV_LOG_QUIET ); 

    av_register_all();

    Connect();
}

void RemoteCameraRtsp::Terminate()
{
    Disconnect();
}

int RemoteCameraRtsp::Connect()
{
    rtspThread = new RtspThread( id, method, protocol, host, port, path, auth );

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
    for ( int i = 0; i < 100 && !rtspThread->hasSources(); i++ )
    {
        usleep( 100000 );
    }
    if ( !rtspThread->hasSources() )
        Fatal( "No RTSP sources" );

    Debug( 2, "Got sources" );

    mFormatContext = rtspThread->getFormatContext();

    // Find first video stream present
    mVideoStreamId = -1;
    
    for ( int i = 0; i < mFormatContext->nb_streams; i++ )
#if LIBAVUTIL_VERSION_INT >= AV_VERSION_INT(51,2,1)
	if ( mFormatContext->streams[i]->codec->codec_type == AVMEDIA_TYPE_VIDEO )
#else
	if ( mFormatContext->streams[i]->codec->codec_type == CODEC_TYPE_VIDEO )
#endif
        {
            mVideoStreamId = i;
            break;
        }
    if ( mVideoStreamId == -1 )
        Fatal( "Unable to locate video stream" );

    // Get a pointer to the codec context for the video stream
    mCodecContext = mFormatContext->streams[mVideoStreamId]->codec;

    // Find the decoder for the video stream
    mCodec = avcodec_find_decoder( mCodecContext->codec_id );
    if ( mCodec == NULL )
        Panic( "Unable to locate codec %d decoder", mCodecContext->codec_id );

    // Open codec
    if ( avcodec_open( mCodecContext, mCodec ) < 0 )
        Panic( "Can't open codec" );

    // Allocate space for the native video frame
    mRawFrame = avcodec_alloc_frame();

    // Allocate space for the converted video frame
    mFrame = avcodec_alloc_frame();
    
	if(mRawFrame == NULL || mFrame == NULL)
		Fatal( "Unable to allocate frame(s)");
	
	int pSize = avpicture_get_size( imagePixFormat, width, height );
	if( pSize != imagesize) {
		Fatal("Image size mismatch. Required: %d Available: %d",pSize,imagesize);
	}
	
#if HAVE_LIBSWSCALE
	if(!sws_isSupportedOutput(imagePixFormat)) {
		Fatal("swscale does not support the target format");
	}
	
	if(config.cpu_extensions && sseversion >= 20) {
		mConvertContext = sws_getContext( mCodecContext->width, mCodecContext->height, mCodecContext->pix_fmt, width, height, imagePixFormat, SWS_BICUBIC | SWS_CPU_CAPS_SSE2, NULL, NULL, NULL );
	} else {
		mConvertContext = sws_getContext( mCodecContext->width, mCodecContext->height, mCodecContext->pix_fmt, width, height, imagePixFormat, SWS_BICUBIC, NULL, NULL, NULL );
	}
	
	if(mConvertContext == NULL)
		Fatal( "Unable to create conversion context");
#else // HAVE_LIBSWSCALE
    Fatal( "You must compile ffmpeg with the --enable-swscale option to use RTSP cameras" );
#endif // HAVE_LIBSWSCALE


    return( 0 );
}

int RemoteCameraRtsp::PreCapture()
{
    if ( !rtspThread->isRunning() )
        return( -1 );
    if ( !rtspThread->hasSources() )
    {
        Error( "Cannot precapture, no RTP sources" );
        return( -1 );
    }
    return( 0 );
}

int RemoteCameraRtsp::Capture( Image &image )
{
	AVPacket packet;
	uint8_t* directbuffer;
	
	/* Request a writeable buffer of the target image */
	directbuffer = image.WriteBuffer(width, height, colours, subpixelorder);
	if(directbuffer == NULL) {
		Error("Failed requesting writeable buffer for the captured image.");
		return (-1);
	}
	
    while ( true )
    {
        buffer.clear();
        if ( !rtspThread->isRunning() )
            return (-1);

        if ( rtspThread->getFrame( buffer ) )
        {
            Debug( 3, "Read frame %d bytes", buffer.size() );
            Debug( 4, "Address %p", buffer.head() );
            Hexdump( 4, buffer.head(), 16 );

            if ( !buffer.size() )
                return( -1 );

            av_init_packet( &packet );
            int frameComplete = false;
            
            while ( !frameComplete )
            {
                packet.data = buffer.head();
                packet.size = buffer.size();
                int len = avcodec_decode_video2( mCodecContext, mRawFrame, &frameComplete, &packet );
                if ( len < 0 )
                {
                    Error( "Error while decoding frame %d", frameCount );
                    Hexdump( Logger::ERROR, buffer.head(), buffer.size()>256?256:buffer.size() );
                    buffer.clear();
                    continue;
                }
                Debug( 2, "Frame: %d - %d/%d", frameCount, len, buffer.size() );
                //if ( buffer.size() < 400 )
                    //Hexdump( 0, buffer.head(), buffer.size() );

                if ( frameComplete )
                {
			Debug( 3, "Got frame %d", frameCount );
			    
			avpicture_fill( (AVPicture *)mFrame, directbuffer, imagePixFormat, width, height);
			
#if HAVE_LIBSWSCALE
			if ( sws_scale( mConvertContext, mRawFrame->data, mRawFrame->linesize, 0, mCodecContext->height, mFrame->data, mFrame->linesize ) < 0 )
				Fatal( "Unable to convert raw format %u to target format %u at frame %d", mCodecContext->pix_fmt, imagePixFormat, frameCount );
#else // HAVE_LIBSWSCALE
			Fatal( "You must compile ffmpeg with the --enable-swscale option to use RTSP cameras" );
#endif // HAVE_LIBSWSCALE
	
			frameCount++;
                }
                buffer -= len;
            }
            av_free_packet( &packet );
        }
    }
    return (0) ;
}

int RemoteCameraRtsp::PostCapture()
{
    return( 0 );
}
#endif // HAVE_LIBAVFORMAT
