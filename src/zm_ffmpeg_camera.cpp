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

FfmpegCamera::FfmpegCamera( int p_id, const std::string &p_path, int p_width, int p_height, int p_colours, int p_brightness, int p_contrast, int p_hue, int p_colour, bool p_capture ) :
    Camera( p_id, FFMPEG_SRC, p_width, p_height, p_colours, ZM_SUBPIX_ORDER_DEFAULT_FOR_COLOUR(p_colours), p_brightness, p_contrast, p_hue, p_colour, p_capture ),
    mPath( p_path )
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
    
    wasRecording = false;
	
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
    if(videoStore)
        delete videoStore;
    
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

    // Open the input, not necessarily a file
#if LIBAVFORMAT_VERSION_INT < AV_VERSION_INT(53, 4, 0)
    if ( av_open_input_file( &mFormatContext, mPath.c_str(), NULL, 0, NULL ) !=0 )
#else
    if ( avformat_open_input( &mFormatContext, mPath.c_str(), NULL, NULL ) !=0 )
#endif
        Fatal( "Unable to open input %s due to: %s", mPath.c_str(), strerror(errno) );

    // Locate stream info from input
#if LIBAVFORMAT_VERSION_INT < AV_VERSION_INT(53, 4, 0)
    if ( av_find_stream_info( mFormatContext ) < 0 )
#else
    if ( avformat_find_stream_info( mFormatContext, 0 ) < 0 )
#endif
        Fatal( "Unable to find stream info from %s due to: %s", mPath.c_str(), strerror(errno) );
    
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

    mCodecContext = mFormatContext->streams[mVideoStreamId]->codec;

    // Try and get the codec from the codec context
    if ( (mCodec = avcodec_find_decoder( mCodecContext->codec_id )) == NULL )
        Fatal( "Can't find codec for video stream from %s", mPath.c_str() );

    // Open the codec
#if LIBAVFORMAT_VERSION_INT < AV_VERSION_INT(53, 7, 0)
    if ( avcodec_open( mCodecContext, mCodec ) < 0 )
#else
    if ( avcodec_open2( mCodecContext, mCodec, 0 ) < 0 )
#endif
        Fatal( "Unable to open codec for video stream from %s", mPath.c_str() );

    // Allocate space for the native video frame
    mRawFrame = avcodec_alloc_frame();

    // Allocate space for the converted video frame
    mFrame = avcodec_alloc_frame();
    
	if(mRawFrame == NULL || mFrame == NULL)
		Fatal( "Unable to allocate frame for %s", mPath.c_str() );
	
	int pSize = avpicture_get_size( imagePixFormat, width, height );
	if( (unsigned int)pSize != imagesize) {
		Fatal("Image size mismatch. Required: %d Available: %d",pSize,imagesize);
	}
	
#if HAVE_LIBSWSCALE
	if(!sws_isSupportedInput(mCodecContext->pix_fmt)) {
		Fatal("swscale does not support the codec format: %c%c%c%c",(mCodecContext->pix_fmt)&0xff,((mCodecContext->pix_fmt>>8)&0xff),((mCodecContext->pix_fmt>>16)&0xff),((mCodecContext->pix_fmt>>24)&0xff));
	}
	
	if(!sws_isSupportedOutput(imagePixFormat)) {
		Fatal("swscale does not support the target format: %c%c%c%c",(imagePixFormat)&0xff,((imagePixFormat>>8)&0xff),((imagePixFormat>>16)&0xff),((imagePixFormat>>24)&0xff));
	}
	
#else // HAVE_LIBSWSCALE
    Fatal( "You must compile ffmpeg with the --enable-swscale option to use ffmpeg cameras" );
#endif // HAVE_LIBSWSCALE

    return( 0 );
}

int FfmpegCamera::PreCapture()
{
    // Nothing to do here
    return( 0 );
}

int FfmpegCamera::Capture( Image &image )
{
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
            Error( "Unable to read packet from stream %d: error %d", packet.stream_index, avResult );
            return( -1 );
        }
        Debug( 5, "Got packet from stream %d", packet.stream_index );
        if ( packet.stream_index == mVideoStreamId )
        {
            if ( avcodec_decode_video2( mCodecContext, mRawFrame, &frameComplete, &packet ) < 0 )
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


//Function to handle capture and store
int FfmpegCamera::CaptureAndRecord( Image &image, bool recording, char* event_directory )
{
	AVPacket packet;
	uint8_t* directbuffer;
    
    //Warning("Recording: %d", (int)recording);

    
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
            Error( "Unable to read packet from stream %d: error %d", packet.stream_index, avResult );
            return( -1 );
        }
        Debug( 5, "Got packet from stream %d", packet.stream_index );
        if ( packet.stream_index == mVideoStreamId )
        {
            if ( avcodec_decode_video2( mCodecContext, mRawFrame, &frameComplete, &packet ) < 0 )
                Fatal( "Unable to decode frame at frame %d", frameCount );
            
            Debug( 4, "Decoded video packet at frame %d", frameCount );
            
            if ( frameComplete )
            {
                Debug( 3, "Got frame %d", frameCount );
                
                avpicture_fill( (AVPicture *)mFrame, directbuffer, imagePixFormat, width, height);
                
                
                
                
                
                if(recording && !wasRecording){
                    //Instanciate the video storage module
                    char fileName[4096];
                    snprintf(fileName, sizeof(fileName), "%s/event.mp4", event_directory);
                    videoStore = new VideoStore((const char *)fileName, "matroska", mFormatContext->streams[mVideoStreamId], false, &packet);
                    wasRecording = true;
                    strcpy(oldDirectory, event_directory);
                    
                }else if(!recording && wasRecording){
                    Warning("Deleting videoStore instance");
                    delete videoStore;
                }
                
                //The directory we are recording to is no longer tied to the current event. Need to re-init the videostore with the correct directory and start recording again
                if(recording && wasRecording && (strcmp(oldDirectory, event_directory)!=0) ){
                    Info("Re-starting video storage module");
                    delete videoStore;
                    char fileName[4096];
                    snprintf(fileName, sizeof(fileName), "%s/event.mp4", event_directory);
                    videoStore = new VideoStore((const char *)fileName, "matroska", mFormatContext->streams[mVideoStreamId], true, &packet);
                    strcpy(oldDirectory, event_directory);
                }
                
                if(videoStore){
                    //Write the packet to our video store
                    int ret = videoStore->writeVideoFramePacket(&packet, mFormatContext->streams[mVideoStreamId]);
                    if(ret<0)//Less than zero and we skipped a frame
                        return 0;
                }
                
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
    return (frameCount);
}










int FfmpegCamera::PostCapture()
{
    // Nothing to do here
    return( 0 );
}

#endif // HAVE_LIBAVFORMAT
