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

RemoteCameraRtsp::RemoteCameraRtsp( int p_id, const std::string &p_method, const std::string &p_host, const std::string &p_port, const std::string &p_path, int p_width, int p_height, bool p_rtsp_describe, int p_colours, int p_brightness, int p_contrast, int p_hue, int p_colour, bool p_capture, bool p_record_audio ) :
	RemoteCamera( p_id, "rtsp", p_host, p_port, p_path, p_width, p_height, p_colours, p_brightness, p_contrast, p_hue, p_colour, p_capture, p_record_audio ),
	rtsp_describe( p_rtsp_describe ),
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
	mAudioStreamId = -1;
	mCodecContext = NULL;
	mCodec = NULL;
	mRawFrame = NULL;
	mFrame = NULL;
	frameCount = 0;
	wasRecording = false;
	startTime=0;
	
#if HAVE_LIBSWSCALE
	mConvertContext = NULL;
#endif
	/* Has to be located inside the constructor so other components such as zma will receive correct colours and subpixel order */
	if(colours == ZM_COLOUR_RGB32) {
		subpixelorder = ZM_SUBPIX_ORDER_RGBA;
		imagePixFormat = AV_PIX_FMT_RGBA;
	} else if(colours == ZM_COLOUR_RGB24) {
		subpixelorder = ZM_SUBPIX_ORDER_RGB;
		imagePixFormat = AV_PIX_FMT_RGB24;
	} else if(colours == ZM_COLOUR_GRAY8) {
		subpixelorder = ZM_SUBPIX_ORDER_NONE;
		imagePixFormat = AV_PIX_FMT_GRAY8;
	} else {
		Panic("Unexpected colours: %d",colours);
	}
	
}

RemoteCameraRtsp::~RemoteCameraRtsp()
{
#if LIBAVCODEC_VERSION_CHECK(55, 28, 1, 45, 101)
	av_frame_free( &mFrame );
	av_frame_free( &mRawFrame );
#else
	av_freep( &mFrame );
	av_freep( &mRawFrame );
#endif
	
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
	   mCodecContext = NULL; // Freed by avformat_free_context in the destructor of RtspThread class
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

	// This allocates a buffer able to hold a raw fframe, which is a little artbitrary.  Might be nice to get some
    // decent data on how large a buffer is really needed.  I think in ffmpeg there are now some functions to do that.
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
	rtspThread = new RtspThread( id, method, protocol, host, port, path, auth, rtsp_describe );

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
    mAudioStreamId = -1;
	
	// Find the first video stream. 
	for ( unsigned int i = 0; i < mFormatContext->nb_streams; i++ ) {
#if (LIBAVCODEC_VERSION_CHECK(52, 64, 0, 64, 0) || LIBAVUTIL_VERSION_CHECK(50, 14, 0, 14, 0))
        if ( mFormatContext->streams[i]->codec->codec_type == AVMEDIA_TYPE_VIDEO )
#else
        if ( mFormatContext->streams[i]->codec->codec_type == CODEC_TYPE_VIDEO )
#endif
		{
            if ( mVideoStreamId == -1 ) {
                mVideoStreamId = i;
                continue;
            } else {
                Debug(2, "Have another video stream." );
            }
		}
#if (LIBAVCODEC_VERSION_CHECK(52, 64, 0, 64, 0) || LIBAVUTIL_VERSION_CHECK(50, 14, 0, 14, 0))
        if ( mFormatContext->streams[i]->codec->codec_type == AVMEDIA_TYPE_AUDIO )
#else
        if ( mFormatContext->streams[i]->codec->codec_type == CODEC_TYPE_AUDIO )
#endif
        {
            if ( mAudioStreamId == -1 ) {
                mAudioStreamId = i;
            } else {
                Debug(2, "Have another audio stream." );
            }
        }

    }
	if ( mVideoStreamId == -1 )
		Fatal( "Unable to locate video stream" );
    if ( mAudioStreamId == -1 )
        Debug( 3, "Unable to locate audio stream" );

	// Get a pointer to the codec context for the video stream
	mCodecContext = mFormatContext->streams[mVideoStreamId]->codec;

	// Find the decoder for the video stream
	mCodec = avcodec_find_decoder( mCodecContext->codec_id );
	if ( mCodec == NULL )
		Panic( "Unable to locate codec %d decoder", mCodecContext->codec_id );

	// Open codec
#if !LIBAVFORMAT_VERSION_CHECK(53, 8, 0, 8, 0)
	if ( avcodec_open( mCodecContext, mCodec ) < 0 )
#else
	if ( avcodec_open2( mCodecContext, mCodec, 0 ) < 0 )
#endif
		Panic( "Can't open codec" );

	// Allocate space for the native video frame
#if LIBAVCODEC_VERSION_CHECK(55, 28, 1, 45, 101)
	mRawFrame = av_frame_alloc();
#else
	mRawFrame = avcodec_alloc_frame();
#endif

	// Allocate space for the converted video frame
#if LIBAVCODEC_VERSION_CHECK(55, 28, 1, 45, 101)
	mFrame = av_frame_alloc();
#else
	mFrame = avcodec_alloc_frame();
#endif

	if(mRawFrame == NULL || mFrame == NULL)
		Fatal( "Unable to allocate frame(s)");
	
#if LIBAVUTIL_VERSION_CHECK(54, 6, 0, 6, 0)
    int pSize = av_image_get_buffer_size( imagePixFormat, width, height, 1 );
#else
    int pSize = avpicture_get_size( imagePixFormat, width, height );
#endif

	if( (unsigned int)pSize != imagesize) {
		Fatal("Image size mismatch. Required: %d Available: %d",pSize,imagesize);
	}
/*	
#if HAVE_LIBSWSCALE
	if(!sws_isSupportedInput(mCodecContext->pix_fmt)) {
		Fatal("swscale does not support the codec format: %c%c%c%c",(mCodecContext->pix_fmt)&0xff,((mCodecContext->pix_fmt>>8)&0xff),((mCodecContext->pix_fmt>>16)&0xff),((mCodecContext->pix_fmt>>24)&0xff));
	}

	if(!sws_isSupportedOutput(imagePixFormat)) {
		Fatal("swscale does not support the target format: %c%c%c%c",(imagePixFormat)&0xff,((imagePixFormat>>8)&0xff),((imagePixFormat>>16)&0xff),((imagePixFormat>>24)&0xff));
	}
	
#else // HAVE_LIBSWSCALE
	Fatal( "You must compile ffmpeg with the --enable-swscale option to use RTSP cameras" );
#endif // HAVE_LIBSWSCALE
*/

	return( 0 );
}

int RemoteCameraRtsp::PreCapture() {
	if ( !rtspThread->isRunning() )
		return( -1 );
	if ( !rtspThread->hasSources() )
	{
		Error( "Cannot precapture, no RTP sources" );
		return( -1 );
	}
	return( 0 );
}

int RemoteCameraRtsp::Capture( Image &image ) {
	AVPacket packet;
	uint8_t* directbuffer;
	int frameComplete = false;
	
	/* Request a writeable buffer of the target image */
	directbuffer = image.WriteBuffer(width, height, colours, subpixelorder);
	if(directbuffer == NULL) {
		Error("Failed requesting writeable buffer for the captured image.");
		return (-1);
	}
	
	while ( true ) {
		buffer.clear();
		if ( !rtspThread->isRunning() )
			return (-1);

		if ( rtspThread->getFrame( buffer ) ) {
			Debug( 3, "Read frame %d bytes", buffer.size() );
			Debug( 4, "Address %p", buffer.head() );
			Hexdump( 4, buffer.head(), 16 );

			if ( !buffer.size() )
				return( -1 );

int avResult = av_read_frame( mFormatContext, &packet );
        if ( avResult < 0 ) {
            char errbuf[AV_ERROR_MAX_STRING_SIZE];
            av_strerror(avResult, errbuf, AV_ERROR_MAX_STRING_SIZE);
            if (
                // Check if EOF.
                (avResult == AVERROR_EOF || (mFormatContext->pb && mFormatContext->pb->eof_reached)) ||
                // Check for Connection failure.
                (avResult == -110)
            ) {
                Info( "av_read_frame returned \"%s\". Reopening stream.", errbuf);
                //ReopenFfmpeg();
            }

            Error( "Unable to read packet from stream %d: error %d \"%s\".", packet.stream_index, avResult, errbuf );
            return( -1 );
        }

			if(mCodecContext->codec_id == AV_CODEC_ID_H264) {
				// SPS and PPS frames should be saved and appended to IDR frames
				int nalType = (buffer.head()[3] & 0x1f);
				
				// SPS The SPS NAL unit contains parameters that apply to a series of consecutive coded video pictures
				if(nalType == 7)
				{
					lastSps = buffer;
					continue;
				}
				// PPS The PPS NAL unit contains parameters that apply to the decoding of one or more individual pictures inside a coded video sequence
				else if(nalType == 8)
				{
					lastPps = buffer;
					continue;
				}
				// IDR
				else if(nalType == 5)
				{
					buffer += lastSps;
					buffer += lastPps;
				}
			}

			av_init_packet( &packet );
			
			while ( !frameComplete && buffer.size() > 0 ) {
				packet.data = buffer.head();
				packet.size = buffer.size();

				// So I think this is the magic decode step. Result is a raw image?
		#if LIBAVCODEC_VERSION_CHECK(52, 23, 0, 23, 0)
				int len = avcodec_decode_video2( mCodecContext, mRawFrame, &frameComplete, &packet );
		#else
				int len = avcodec_decode_video( mCodecContext, mRawFrame, &frameComplete, packet.data, packet.size );
		#endif
				if ( len < 0 ) {
					Error( "Error while decoding frame %d", frameCount );
					Hexdump( Logger::ERROR, buffer.head(), buffer.size()>256?256:buffer.size() );
					buffer.clear();
					continue;
				}
				Debug( 2, "Frame: %d - %d/%d", frameCount, len, buffer.size() );
				//if ( buffer.size() < 400 )
			   //Hexdump( 0, buffer.head(), buffer.size() );
				   
				buffer -= len;
			}
			// At this point, we either have a frame or ran out of buffer. What happens if we run out of buffer?
			if ( frameComplete ) {
			   
				Debug( 3, "Got frame %d", frameCount );
						
				avpicture_fill( (AVPicture *)mFrame, directbuffer, imagePixFormat, width, height );
					
		#if HAVE_LIBSWSCALE
				if(mConvertContext == NULL) {
					mConvertContext = sws_getContext( mCodecContext->width, mCodecContext->height, mCodecContext->pix_fmt, width, height, imagePixFormat, SWS_BICUBIC, NULL, NULL, NULL );

					if(mConvertContext == NULL)
						Fatal( "Unable to create conversion context");
				}
			
				if ( sws_scale( mConvertContext, mRawFrame->data, mRawFrame->linesize, 0, mCodecContext->height, mFrame->data, mFrame->linesize ) < 0 )
					Fatal( "Unable to convert raw format %u to target format %u at frame %d", mCodecContext->pix_fmt, imagePixFormat, frameCount );
		#else // HAVE_LIBSWSCALE
				Fatal( "You must compile ffmpeg with the --enable-swscale option to use RTSP cameras" );
		#endif // HAVE_LIBSWSCALE
			
				frameCount++;

			} /* frame complete */
			 
	#if LIBAVCODEC_VERSION_CHECK(57, 8, 0, 12, 100)
			av_packet_unref( &packet );
	#else
			av_free_packet( &packet );
	#endif
		} /* getFrame() */
	 
		if(frameComplete)
			return (0);
	
	} // end while true

	// can never get here.
	return (0) ;
}

//int RemoteCameraRtsp::ReadData(void *opaque, uint8_t *buf, int bufSize) {
    
    //if ( buffer.size() > bufSize ) {
        //buf = buffer.head();
        //buffer -= bufSize;
    //} else {
        //Error("Implement me");
        //return -1;
    //}
//}

//Function to handle capture and store
int RemoteCameraRtsp::CaptureAndRecord( Image &image, bool recording, char* event_file ) {
	AVPacket packet;
	uint8_t* directbuffer;
	int frameComplete = false;
	
	/* Request a writeable buffer of the target image */
	directbuffer = image.WriteBuffer(width, height, colours, subpixelorder);
	if(directbuffer == NULL) {
		Error("Failed requesting writeable buffer for the captured image.");
		return (-1);
	}
	
	while ( true ) {

		buffer.clear();
		if ( !rtspThread->isRunning() )
			return (-1);

		if ( rtspThread->getFrame( buffer ) ) {
			Debug( 3, "Read frame %d bytes", buffer.size() );
			Debug( 4, "Address %p", buffer.head() );
			Hexdump( 4, buffer.head(), 16 );

			if ( !buffer.size() )
				return( -1 );

			if(mCodecContext->codec_id == AV_CODEC_ID_H264) {
				// SPS and PPS frames should be saved and appended to IDR frames
				int nalType = (buffer.head()[3] & 0x1f);
				
				// SPS
				if(nalType == 7) {
					lastSps = buffer;
					continue;
				}
				// PPS
				else if(nalType == 8) {
					lastPps = buffer;
					continue;
				}
				// IDR
				else if(nalType == 5) {
					buffer += lastSps;
					buffer += lastPps;
				}
			} // end if H264, what about other codecs?

			av_init_packet( &packet );
			
			// Why are we checking for it being the video stream? Because it might be audio or something else.
            // Um... we just initialized packet... we can't be testing for what it is yet....
			if ( packet.stream_index == mVideoStreamId ) {
			
				while ( !frameComplete && buffer.size() > 0 ) {
					packet.data = buffer.head();
					packet.size = buffer.size();

					// So this does the decode
#if LIBAVCODEC_VERSION_CHECK(52, 23, 0, 23, 0)
					int len = avcodec_decode_video2( mCodecContext, mRawFrame, &frameComplete, &packet );
#else
					int len = avcodec_decode_video( mCodecContext, mRawFrame, &frameComplete, packet.data, packet.size );
#endif
					if ( len < 0 ) {
						Error( "Error while decoding frame %d", frameCount );
						Hexdump( Logger::ERROR, buffer.head(), buffer.size()>256?256:buffer.size() );
						buffer.clear();
						continue;
					}
					Debug( 2, "Frame: %d - %d/%d", frameCount, len, buffer.size() );
					//if ( buffer.size() < 400 )
					//Hexdump( 0, buffer.head(), buffer.size() );

					buffer -= len;
				} // end while get & decode a frame

				if ( frameComplete ) {

					Debug( 3, "Got frame %d", frameCount );

#if LIBAVUTIL_VERSION_CHECK(54, 6, 0, 6, 0)
                    av_image_fill_arrays(mFrame->data, mFrame->linesize,
                            directbuffer, imagePixFormat, width, height, 1);
#else
                    avpicture_fill( (AVPicture *)mFrame, directbuffer,
                            imagePixFormat, width, height);
#endif

                    //Video recording
					if ( recording && !wasRecording ) {
						//Instantiate the video storage module

						videoStore = new VideoStore((const char *)event_file, "mp4", mFormatContext->streams[mVideoStreamId],mAudioStreamId==-1?NULL:mFormatContext->streams[mAudioStreamId],startTime);
						wasRecording = true;
						strcpy(oldDirectory, event_file);

					} else if ( !recording && wasRecording && videoStore ) {
						// Why are we deleting the videostore? Becase for soem reason we are no longer recording? How does that happen?
						Info("Deleting videoStore instance");
						delete videoStore;
						videoStore = NULL;
					}

					//The directory we are recording to is no longer tied to the current event. Need to re-init the videostore with the correct directory and start recording again
					if ( recording && wasRecording && (strcmp(oldDirectory, event_file)!=0) && (packet.flags & AV_PKT_FLAG_KEY) ) {
						//don't open new videostore until we're on a key frame..would this require an offset adjustment for the event as a result?...if we store our key frame location with the event will that be enough?
						Info("Re-starting video storage module");
						if ( videoStore ) {
							delete videoStore;
							videoStore = NULL;
						}

						videoStore = new VideoStore((const char *)event_file, "mp4", mFormatContext->streams[mVideoStreamId],mAudioStreamId==-1?NULL:mFormatContext->streams[mAudioStreamId],startTime);
						strcpy( oldDirectory, event_file );
					}

					if ( videoStore && recording ) {
						//Write the packet to our video store
						int ret = videoStore->writeVideoFramePacket(&packet, mFormatContext->streams[mVideoStreamId]);//, &lastKeyframePkt);
						if ( ret < 0 ) {//Less than zero and we skipped a frame
							av_free_packet( &packet );
							return 0;
						}
					}

#if HAVE_LIBSWSCALE
// Why are we re-scaling after writing out the packet?
					if(mConvertContext == NULL) {
							mConvertContext = sws_getContext( mCodecContext->width, mCodecContext->height, mCodecContext->pix_fmt, width, height, imagePixFormat, SWS_BICUBIC, NULL, NULL, NULL );

							if(mConvertContext == NULL)
									Fatal( "Unable to create conversion context");
					}

					if ( sws_scale( mConvertContext, mRawFrame->data, mRawFrame->linesize, 0, mCodecContext->height, mFrame->data, mFrame->linesize ) < 0 )
							Fatal( "Unable to convert raw format %u to target format %u at frame %d", mCodecContext->pix_fmt, imagePixFormat, frameCount );
#else // HAVE_LIBSWSCALE
					Fatal( "You must compile ffmpeg with the --enable-swscale option to use RTSP cameras" );
#endif // HAVE_LIBSWSCALE

					frameCount++;

				 } /* frame complete */
			} else if ( packet.stream_index == mAudioStreamId ) {
				Debug( 4, "Got audio packet" );
				if ( videoStore && recording ) {
					if ( record_audio ) {
						Debug( 4, "Storing Audio packet" );
						//Write the packet to our video store
						int ret = videoStore->writeAudioFramePacket(&packet, mFormatContext->streams[packet.stream_index]); //FIXME no relevance of last key frame
						if ( ret < 0 ) { //Less than zero and we skipped a frame
#if LIBAVCODEC_VERSION_CHECK(57, 8, 0, 12, 100)
							av_packet_unref( &packet );
#else
							av_free_packet( &packet );
#endif
							return 0;	  
						}
					} else {
						Debug( 4, "Not storing audio" );
					}
				}
			} // end if video or audio packet
		 
#if LIBAVCODEC_VERSION_CHECK(57, 8, 0, 12, 100)
			av_packet_unref( &packet );
#else
			av_free_packet( &packet );
#endif
		} /* getFrame() */
	 
		if(frameComplete)
			return (0);
	} // end while true

    // can never get here.
	return (0) ;
} // int RemoteCameraRtsp::CaptureAndRecord( Image &image, bool recording, char* event_file ) 

int RemoteCameraRtsp::PostCapture()
{
	return( 0 );
}
#endif // HAVE_LIBAVFORMAT
