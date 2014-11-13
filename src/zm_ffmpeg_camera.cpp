//
// ZoneMinder Ffmpeg Camera Class Implementation, $Date: 2009-01-16 12:18:50 +0000 (Fri, 16 Jan 2009) $, $Revision: 2713 $
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

#include "zm_ffmpeg_camera.h"

#ifndef AV_ERROR_MAX_STRING_SIZE
#define AV_ERROR_MAX_STRING_SIZE 64
#endif

FfmpegCamera::FfmpegCamera( int p_id, const std::string &p_path, const std::string &p_method, const std::string &p_options, int p_width, int p_height, int p_colours, int p_brightness, int p_contrast, int p_hue, int p_colour, bool p_capture ) :
    Camera( p_id, FFMPEG_SRC, p_width, p_height, p_colours, ZM_SUBPIX_ORDER_DEFAULT_FOR_COLOUR(p_colours), p_brightness, p_contrast, p_hue, p_colour, p_capture ),
    mPath( p_path ),
    mMethod( p_method ),
    mOptions( p_options )
{
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
    mIsOpening = false;
    mCanCapture = false;
    mOpenStart = 0;
    mReopenThread = 0;
	
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

FfmpegCamera::~FfmpegCamera()
{
    CloseFfmpeg();

	if ( capture )
	{
		Terminate();
	}
}

void FfmpegCamera::Initialise()
{
    if ( logDebugging() )
        av_log_set_level( AV_LOG_DEBUG ); 
    else
        av_log_set_level( AV_LOG_QUIET ); 

    av_register_all();
}

void FfmpegCamera::Terminate()
{
}

int FfmpegCamera::PrimeCapture()
{
    Info( "Priming capture from %s", mPath.c_str() );

    if (OpenFfmpeg() != 0){
        ReopenFfmpeg();
    }
    return 0;
}

int FfmpegCamera::PreCapture()
{
    // Nothing to do here
    return( 0 );
}

int FfmpegCamera::Capture( Image &image )
{
    if (!mCanCapture){
        return -1;
    }
    
    // If the reopen thread has a value, but mCanCapture != 0, then we have just reopened the connection to the ffmpeg device, and we can clean up the thread.
    if (mReopenThread != 0) {
        void *retval = 0;
        int ret;
        
        ret = pthread_tryjoin_np(mReopenThread, &retval);
        if (ret != 0){
            Error("Could not join reopen thread.");
        }
        
        Info( "Successfully reopened stream." );
        mReopenThread = 0;
    }

	AVPacket packet;
	uint8_t* directbuffer;
   
	/* Request a writeable buffer of the target image */
	directbuffer = image.WriteBuffer(width, height, colours, subpixelorder);
	if(directbuffer == NULL) {
		Error("Failed requesting writeable buffer for the captured image.");
		return (-1);
	}
    
    int frameComplete = false;
    while ( !frameComplete )
    {
        int avResult = av_read_frame( mFormatContext, &packet );
        if ( avResult < 0 )
        {
            char errbuf[AV_ERROR_MAX_STRING_SIZE];
            av_strerror(avResult, errbuf, AV_ERROR_MAX_STRING_SIZE);
            if (
                // Check if EOF.
                (avResult == AVERROR_EOF || (mFormatContext->pb && mFormatContext->pb->eof_reached)) ||
                // Check for Connection failure.
                (avResult == -110)
            )
            {
                Info( "av_read_frame returned \"%s\". Reopening stream.", errbuf);
                ReopenFfmpeg();
            }

            Error( "Unable to read packet from stream %d: error %d \"%s\".", packet.stream_index, avResult, errbuf );
            return( -1 );
        }
        Debug( 5, "Got packet from stream %d", packet.stream_index );
        if ( packet.stream_index == mVideoStreamId )
        {
#if LIBAVCODEC_VERSION_INT >= AV_VERSION_INT(52, 25, 0)
			if ( avcodec_decode_video2( mCodecContext, mRawFrame, &frameComplete, &packet ) < 0 )
#else
			if ( avcodec_decode_video( mCodecContext, mRawFrame, &frameComplete, packet.data, packet.size ) < 0 )
#endif
                Fatal( "Unable to decode frame at frame %d", frameCount );

            Debug( 4, "Decoded video packet at frame %d", frameCount );

            if ( frameComplete )
            {
                Debug( 3, "Got frame %d", frameCount );

		avpicture_fill( (AVPicture *)mFrame, directbuffer, imagePixFormat, width, height);
		
#if HAVE_LIBSWSCALE
		if(mConvertContext == NULL) {
			if(config.cpu_extensions && sseversion >= 20) {
				mConvertContext = sws_getContext( mCodecContext->width, mCodecContext->height, mCodecContext->pix_fmt, width, height, imagePixFormat, SWS_BICUBIC | SWS_CPU_CAPS_SSE2, NULL, NULL, NULL );
			} else {
				mConvertContext = sws_getContext( mCodecContext->width, mCodecContext->height, mCodecContext->pix_fmt, width, height, imagePixFormat, SWS_BICUBIC, NULL, NULL, NULL );
			}
			if(mConvertContext == NULL)
				Fatal( "Unable to create conversion context for %s", mPath.c_str() );
		}
	
		if ( sws_scale( mConvertContext, mRawFrame->data, mRawFrame->linesize, 0, mCodecContext->height, mFrame->data, mFrame->linesize ) < 0 )
			Fatal( "Unable to convert raw format %u to target format %u at frame %d", mCodecContext->pix_fmt, imagePixFormat, frameCount );
#else // HAVE_LIBSWSCALE
		Fatal( "You must compile ffmpeg with the --enable-swscale option to use ffmpeg cameras" );
#endif // HAVE_LIBSWSCALE
 
                frameCount++;
            }
        }
        av_free_packet( &packet );
    }
    return (0);
}

int FfmpegCamera::PostCapture()
{
    // Nothing to do here
    return( 0 );
}

int FfmpegCamera::OpenFfmpeg() {

    Debug ( 2, "OpenFfmpeg called." );

    mOpenStart = time(NULL);
    mIsOpening = true;

    // Open the input, not necessarily a file
#if LIBAVFORMAT_VERSION_INT < AV_VERSION_INT(53, 4, 0)
    Debug ( 1, "Calling av_open_input_file" );
    if ( av_open_input_file( &mFormatContext, mPath.c_str(), NULL, 0, NULL ) !=0 )
#else
    // Handle options
    AVDictionary *opts = 0;
    StringVector opVect = split(Options(), ",");
    
    // Set transport method as specified by method field, rtpUni is default
    if ( Method() == "rtpMulti" )
    	opVect.push_back("rtsp_transport=udp_multicast");
    else if ( Method() == "rtpRtsp" )
        opVect.push_back("rtsp_transport=tcp");
    else if ( Method() == "rtpRtspHttp" )
        opVect.push_back("rtsp_transport=http");
    
  	Debug(2, "Number of Options: %d",opVect.size());
    for (size_t i=0; i<opVect.size(); i++)
    {
    	StringVector parts = split(opVect[i],"=");
    	if (parts.size() > 1) {
    		parts[0] = trimSpaces(parts[0]);
    		parts[1] = trimSpaces(parts[1]);
    	    if ( av_dict_set(&opts, parts[0].c_str(), parts[1].c_str(), 0) == 0 ) {
    	        Debug(2, "set option %d '%s' to '%s'", i,  parts[0].c_str(), parts[1].c_str());
    	    }
    	    else
    	    {
    	        Warning( "Error trying to set option %d '%s' to '%s'", i, parts[0].c_str(), parts[1].c_str() );
    	    }
    		  
    	}
    }    
	Debug ( 1, "Calling avformat_open_input" );

    mFormatContext = avformat_alloc_context( );
    mFormatContext->interrupt_callback.callback = FfmpegInterruptCallback;
    mFormatContext->interrupt_callback.opaque = this;

    if ( avformat_open_input( &mFormatContext, mPath.c_str(), NULL, &opts ) !=0 )
#endif
    {
        mIsOpening = false;
        Error( "Unable to open input %s due to: %s", mPath.c_str(), strerror(errno) );
        return -1;
    }

    mIsOpening = false;
    Debug ( 1, "Opened input" );

    // Locate stream info from avformat_open_input
#if LIBAVFORMAT_VERSION_INT < AV_VERSION_INT(53, 4, 0)
    Debug ( 1, "Calling av_find_stream_info" );
    if ( av_find_stream_info( mFormatContext ) < 0 )
#else
    Debug ( 1, "Calling avformat_find_stream_info" );
    if ( avformat_find_stream_info( mFormatContext, 0 ) < 0 )
#endif
        Fatal( "Unable to find stream info from %s due to: %s", mPath.c_str(), strerror(errno) );
    
    Debug ( 1, "Got stream info" );

    // Find first video stream present
    mVideoStreamId = -1;
    for (unsigned int i=0; i < mFormatContext->nb_streams; i++ )
    {
#if LIBAVUTIL_VERSION_INT >= AV_VERSION_INT(51,2,1)
        if ( mFormatContext->streams[i]->codec->codec_type == AVMEDIA_TYPE_VIDEO )
#else
        if ( mFormatContext->streams[i]->codec->codec_type == CODEC_TYPE_VIDEO )
#endif
        {
            mVideoStreamId = i;
            break;
        }
    }
    if ( mVideoStreamId == -1 )
        Fatal( "Unable to locate video stream in %s", mPath.c_str() );

    Debug ( 1, "Found video stream" );

    mCodecContext = mFormatContext->streams[mVideoStreamId]->codec;

    // Try and get the codec from the codec context
    if ( (mCodec = avcodec_find_decoder( mCodecContext->codec_id )) == NULL )
        Fatal( "Can't find codec for video stream from %s", mPath.c_str() );

    Debug ( 1, "Found decoder" );

    // Open the codec
#if LIBAVFORMAT_VERSION_INT < AV_VERSION_INT(53, 7, 0)
    Debug ( 1, "Calling avcodec_open" );
    if ( avcodec_open( mCodecContext, mCodec ) < 0 )
#else
    Debug ( 1, "Calling avcodec_open2" );
    if ( avcodec_open2( mCodecContext, mCodec, 0 ) < 0 )
#endif
        Fatal( "Unable to open codec for video stream from %s", mPath.c_str() );

    Debug ( 1, "Opened codec" );

    // Allocate space for the native video frame
    mRawFrame = avcodec_alloc_frame();

    // Allocate space for the converted video frame
    mFrame = avcodec_alloc_frame();
    
    if(mRawFrame == NULL || mFrame == NULL)
        Fatal( "Unable to allocate frame for %s", mPath.c_str() );

    Debug ( 1, "Allocated frames" );
    
    int pSize = avpicture_get_size( imagePixFormat, width, height );
    if( (unsigned int)pSize != imagesize) {
        Fatal("Image size mismatch. Required: %d Available: %d",pSize,imagesize);
    }

    Debug ( 1, "Validated imagesize" );
    
#if HAVE_LIBSWSCALE
    Debug ( 1, "Calling sws_isSupportedInput" );
    if(!sws_isSupportedInput(mCodecContext->pix_fmt)) {
        Fatal("swscale does not support the codec format: %c%c%c%c",(mCodecContext->pix_fmt)&0xff,((mCodecContext->pix_fmt>>8)&0xff),((mCodecContext->pix_fmt>>16)&0xff),((mCodecContext->pix_fmt>>24)&0xff));
    }
    
    if(!sws_isSupportedOutput(imagePixFormat)) {
        Fatal("swscale does not support the target format: %c%c%c%c",(imagePixFormat)&0xff,((imagePixFormat>>8)&0xff),((imagePixFormat>>16)&0xff),((imagePixFormat>>24)&0xff));
    }
    
#else // HAVE_LIBSWSCALE
    Fatal( "You must compile ffmpeg with the --enable-swscale option to use ffmpeg cameras" );
#endif // HAVE_LIBSWSCALE

    mCanCapture = true;

    return 0;
}

int FfmpegCamera::ReopenFfmpeg() {

    Debug(2, "ReopenFfmpeg called.");

    mCanCapture = false;
    if (pthread_create( &mReopenThread, NULL, ReopenFfmpegThreadCallback, (void*) this) != 0){
        // Log a fatal error and exit the process.
        Fatal( "ReopenFfmpeg failed to create worker thread." );
    }

    return 0;
}

int FfmpegCamera::CloseFfmpeg(){

    Debug(2, "CloseFfmpeg called.");

    mCanCapture = false;

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
    if ( mFormatContext )
    {
#if LIBAVFORMAT_VERSION_INT < AV_VERSION_INT(53, 4, 0)
        av_close_input_file( mFormatContext );
#else
        avformat_close_input( &mFormatContext );
#endif
        mFormatContext = NULL;
    }

    return 0;
}

int FfmpegCamera::FfmpegInterruptCallback(void *ctx) 
{ 
    FfmpegCamera* camera = reinterpret_cast<FfmpegCamera*>(ctx);
    if (camera->mIsOpening){
        int now = time(NULL);
        if ((now - camera->mOpenStart) > config.ffmpeg_open_timeout) {
            Error ( "Open video took more than %d seconds.", config.ffmpeg_open_timeout );
            return 1;
        }
    }

    return 0;
}

void *FfmpegCamera::ReopenFfmpegThreadCallback(void *ctx){
    if (ctx == NULL) return NULL;

    FfmpegCamera* camera = reinterpret_cast<FfmpegCamera*>(ctx);

    while (1){
        // Close current stream.
        camera->CloseFfmpeg();

        // Sleep if neccessary to not reconnect too fast.
        int wait = config.ffmpeg_open_timeout - (time(NULL) - camera->mOpenStart);
        wait = wait < 0 ? 0 : wait;
        if (wait > 0){
            Debug( 1, "Sleeping %d seconds before reopening stream.", wait );
            sleep(wait);
        }

        if (camera->OpenFfmpeg() == 0){
            return NULL;
        }
    }
}

#endif // HAVE_LIBAVFORMAT
