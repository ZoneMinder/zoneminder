/*
 * ZoneMinder MPEG class implementation, $Date$, $Revision$
 * Copyright (C) 2001-2008 Philip Coombes
 * 
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 */

#include <stdlib.h>
#include <string.h>

#include "zm.h"
#include "zm_rgb.h"
#include "zm_mpeg.h"

#if HAVE_LIBAVCODEC
extern "C"
{
#include <libavutil/mathematics.h>
#include <libavcodec/avcodec.h>
}

bool VideoStream::initialised = false;

VideoStream::MimeData VideoStream::mime_data[] = {
	{ "asf", "video/x-ms-asf" },
	{ "swf", "application/x-shockwave-flash" },
	{ "flv", "video/x-flv" },
	{ "mov", "video/quicktime" }
};

void VideoStream::Initialise( )
{
	if ( logDebugging() )
        av_log_set_level( AV_LOG_DEBUG ); 
    else
        av_log_set_level( AV_LOG_QUIET ); 
	
	av_register_all( );
#if LIBAVFORMAT_VERSION_INT >= AV_VERSION_INT(53, 13, 0)
	avformat_network_init();
#endif
	initialised = true;
}

void VideoStream::SetupFormat( )
{
	/* allocate the output media context */
	ofc = NULL;
#if LIBAVFORMAT_VERSION_INT >= AV_VERSION_INT(53, 5, 0)
	avformat_alloc_output_context2( &ofc, NULL, format, filename );
#else
	AVFormatContext *s= avformat_alloc_context();
	if(!s) 
	{
		Fatal( "avformat_alloc_context failed %d \"%s\"", (size_t)ofc, av_err2str((size_t)ofc) );
	}
	
	AVOutputFormat *oformat;
	if (format) {
#if LIBAVFORMAT_VERSION_INT >= AV_VERSION_INT(52, 45, 0)
		oformat = av_guess_format(format, NULL, NULL);
#else
		oformat = guess_format(format, NULL, NULL);
#endif
		if (!oformat) {
			Fatal( "Requested output format '%s' is not a suitable output format", format );
		}
	} else {
#if LIBAVFORMAT_VERSION_INT >= AV_VERSION_INT(52, 45, 0)
		oformat = av_guess_format(NULL, filename, NULL);
#else
		oformat = guess_format(NULL, filename, NULL);
#endif
		if (!oformat) {
			Fatal( "Unable to find a suitable output format for '%s'", format );
		}
	}
	s->oformat = oformat;
	
	if (s->oformat->priv_data_size > 0) {
		s->priv_data = av_mallocz(s->oformat->priv_data_size);
		if (!s->priv_data)
		{
			Fatal( "Could not allocate private data for output format." );
		}
#if LIBAVUTIL_VERSION_INT >= AV_VERSION_INT(51, 10, 0)
		if (s->oformat->priv_class) {
			*(const AVClass**)s->priv_data = s->oformat->priv_class;
			av_opt_set_defaults(s->priv_data);
		}
#endif
	} 
	else
	{
		s->priv_data = NULL;
	}
	
	if(filename)
	{
		snprintf( s->filename, sizeof(s->filename), filename );
	}
	
	ofc = s;
#endif
	if ( !ofc )
	{
		Fatal( "avformat_alloc_..._context failed: %d", ofc );
	}

	of = ofc->oformat;
	Debug( 1, "Using output format: %s (%s)", of->name, of->long_name );
}

void VideoStream::SetupCodec( int colours, int subpixelorder, int width, int height, int bitrate, double frame_rate )
{
	/* ffmpeg format matching */
	switch(colours) {
	  case ZM_COLOUR_RGB24:
	  {
	    if(subpixelorder == ZM_SUBPIX_ORDER_BGR) {
	      /* BGR subpixel order */
	      pf = PIX_FMT_BGR24;
	    } else {
	      /* Assume RGB subpixel order */
	      pf = PIX_FMT_RGB24;
	    }
	    break;
	  }
	  case ZM_COLOUR_RGB32:
	  {
	    if(subpixelorder == ZM_SUBPIX_ORDER_ARGB) {
	      /* ARGB subpixel order */
	      pf = PIX_FMT_ARGB;
	    } else if(subpixelorder == ZM_SUBPIX_ORDER_ABGR) {
	      /* ABGR subpixel order */
	      pf = PIX_FMT_ABGR;
	    } else if(subpixelorder == ZM_SUBPIX_ORDER_BGRA) {
	      /* BGRA subpixel order */
	      pf = PIX_FMT_BGRA;
	    } else {
	      /* Assume RGBA subpixel order */
	      pf = PIX_FMT_RGBA;
	    }
	    break;
	  }
	  case ZM_COLOUR_GRAY8:
	    pf = PIX_FMT_GRAY8;
	    break;
	  default:
	    Panic("Unexpected colours: %d",colours);
	    break;
	}

	if ( strcmp( "rtp", of->name ) == 0 )
	{
		// RTP must have a packet_size.
		// Not sure what this value should be really...
		ofc->packet_size = width*height;
		
		if ( of->video_codec ==  CODEC_ID_NONE)
		{
			// RTP does not have a default codec in ffmpeg <= 0.8.
			of->video_codec = CODEC_ID_MPEG4;
		}
	}
	
	_AVCODECID codec_id = of->video_codec;
	if ( codec_name )
	{
            AVCodec *a = avcodec_find_encoder_by_name(codec_name);
            if ( a )
            {
                codec_id = a->id;
            }
            else
            {
#if LIBAVFORMAT_VERSION_INT >= AV_VERSION_INT(53, 11, 0)
                    Debug( 1, "Could not find codec \"%s\". Using default \"%s\"", codec_name, avcodec_get_name( codec_id ) );
#else
                    Debug( 1, "Could not find codec \"%s\". Using default \"%d\"", codec_name, codec_id );
#endif
            }
	}

	/* add the video streams using the default format codecs
	   and initialize the codecs */
	ost = NULL;
	if ( codec_id != _AVCODECID_NONE )
	{
		codec = avcodec_find_encoder( codec_id );
		if ( !codec )
		{
#if LIBAVFORMAT_VERSION_INT >= AV_VERSION_INT(53, 11, 0)
			Fatal( "Could not find encoder for '%s'", avcodec_get_name( codec_id ) );
#else
			Fatal( "Could not find encoder for '%d'", codec_id );
#endif
		}

#if LIBAVFORMAT_VERSION_INT >= AV_VERSION_INT(53, 11, 0)
		Debug( 1, "Found encoder for '%s'", avcodec_get_name( codec_id ) );
#else
		Debug( 1, "Found encoder for '%d'", codec_id );
#endif

#if LIBAVFORMAT_VERSION_INT >= AV_VERSION_INT(53, 10, 0)		
		ost = avformat_new_stream( ofc, codec );
#else
		ost = av_new_stream( ofc, 0 );
#endif
		
		if ( !ost )
		{
			Fatal( "Could not alloc stream" );
		}
		ost->id = ofc->nb_streams - 1;

		Debug( 1, "Allocated stream" );

		AVCodecContext *c = ost->codec;

		c->codec_id = codec->id;
		c->codec_type = codec->type;

		c->pix_fmt = strcmp( "mjpeg", ofc->oformat->name ) == 0 ? PIX_FMT_YUVJ422P : PIX_FMT_YUV420P;
		if ( bitrate <= 100 )
		{
			// Quality based bitrate control (VBR). Scale is 1..31 where 1 is best.
			// This gets rid of artifacts in the beginning of the movie; and well, even quality.
			c->flags |= CODEC_FLAG_QSCALE;
			c->global_quality = FF_QP2LAMBDA * (31 - (31 * (bitrate / 100.0)));
		}
		else
		{
			c->bit_rate = bitrate;
		}

		/* resolution must be a multiple of two */
		c->width = width;
		c->height = height;
		/* time base: this is the fundamental unit of time (in seconds) in terms
		   of which frame timestamps are represented. for fixed-fps content,
		   timebase should be 1/framerate and timestamp increments should be
		   identically 1. */
		c->time_base.den = frame_rate;
		c->time_base.num = 1;
		
		Debug( 1, "Will encode in %d fps.", c->time_base.den );
		
		/* emit one intra frame every second */
		c->gop_size = frame_rate;

		// some formats want stream headers to be seperate
		if ( of->flags & AVFMT_GLOBALHEADER )
			c->flags |= CODEC_FLAG_GLOBAL_HEADER;
	}
	else
	{
		Fatal( "of->video_codec == CODEC_ID_NONE" );
	}
}

void VideoStream::SetParameters( )
{
}

const char *VideoStream::MimeType( ) const
{
	for ( unsigned int i = 0; i < sizeof (mime_data) / sizeof (*mime_data); i++ )
	{
		if ( strcmp( format, mime_data[i].format ) == 0 )
		{
			Debug( 1, "MimeType is \"%s\"", mime_data[i].mime_type );
			return ( mime_data[i].mime_type);
		}
	}
	const char *mime_type = of->mime_type;
	if ( !mime_type )
	{
		std::string mime = "video/";
		mime = mime.append( format );
		mime_type = mime.c_str( );
		Warning( "Unable to determine mime type for '%s' format, using '%s' as default", format, mime_type );
	}

	Debug( 1, "MimeType is \"%s\"", mime_type );

	return ( mime_type);
}

void VideoStream::OpenStream( )
{
	int avRet;

	/* now that all the parameters are set, we can open the 
	   video codecs and allocate the necessary encode buffers */
	if ( ost )
	{
		AVCodecContext *c = ost->codec;
		
		/* open the codec */
#if LIBAVFORMAT_VERSION_INT < AV_VERSION_INT(53, 7, 0)
		if ( (avRet = avcodec_open( c, codec )) < 0 )
#else
		if ( (avRet = avcodec_open2( c, codec, 0 )) < 0 )
#endif
		{
			Fatal( "Could not open codec. Error code %d \"%s\"", avRet, av_err2str( avRet ) );
		}

		Debug( 1, "Opened codec" );

		/* allocate the encoded raw picture */
		opicture = avcodec_alloc_frame( );
		if ( !opicture )
		{
			Panic( "Could not allocate opicture" );
		}
		
		int size = avpicture_get_size( c->pix_fmt, c->width, c->height );
		uint8_t *opicture_buf = (uint8_t *)av_malloc( size );
		if ( !opicture_buf )
		{
			av_free( opicture );
			Panic( "Could not allocate opicture_buf" );
		}
		avpicture_fill( (AVPicture *)opicture, opicture_buf, c->pix_fmt, c->width, c->height );

		/* if the output format is not identical to the input format, then a temporary
		   picture is needed too. It is then converted to the required
		   output format */
		tmp_opicture = NULL;
		if ( c->pix_fmt != pf )
		{
			tmp_opicture = avcodec_alloc_frame( );
			if ( !tmp_opicture )
			{
				Panic( "Could not allocate tmp_opicture" );
			}
			int size = avpicture_get_size( pf, c->width, c->height );
			uint8_t *tmp_opicture_buf = (uint8_t *)av_malloc( size );
			if ( !tmp_opicture_buf )
			{
				av_free( tmp_opicture );
				Panic( "Could not allocate tmp_opicture_buf" );
			}
			avpicture_fill( (AVPicture *)tmp_opicture, tmp_opicture_buf, pf, c->width, c->height );
		}
	}

	/* open the output file, if needed */
	if ( !(of->flags & AVFMT_NOFILE) )
	{
		int ret;
#if LIBAVFORMAT_VERSION_INT >= AV_VERSION_INT(53, 14, 0)
		ret = avio_open2( &ofc->pb, filename, AVIO_FLAG_WRITE, NULL, NULL );
#elif LIBAVFORMAT_VERSION_INT >= AV_VERSION_INT(52, 102, 0)
		ret = avio_open( &ofc->pb, filename, AVIO_FLAG_WRITE );
#else
		ret = url_fopen( &ofc->pb, filename, AVIO_FLAG_WRITE );
#endif
		if ( ret < 0 )
		{
			Fatal( "Could not open '%s'", filename );
		}

		Debug( 1, "Opened output \"%s\"", filename );
	}
	else
	{
		Fatal( "of->flags & AVFMT_NOFILE" );
	}

	video_outbuf = NULL;
	if ( !(of->flags & AVFMT_RAWPICTURE) )
	{
		/* allocate output buffer */
		/* XXX: API change will be done */
		// TODO: Make buffer dynamic.
		video_outbuf_size = 4000000;
		video_outbuf = (uint8_t *)malloc( video_outbuf_size );
	}
	
#if LIBAVFORMAT_VERSION_INT >= AV_VERSION_INT(52, 100, 1)
	av_dump_format(ofc, 0, filename, 1);
#else
	dump_format(ofc, 0, filename, 1);
#endif
}

VideoStream::VideoStream( const char *in_filename, const char *in_format, int bitrate, double frame_rate, int colours, int subpixelorder, int width, int height ) :
		filename(in_filename),
		format(in_format),
		last_pts( -1 ),
		streaming_thread(0),
		do_streaming(true),
		buffer_copy(NULL),
		buffer_copy_lock(new pthread_mutex_t),
		buffer_copy_used(0),
		is_buffering(true),
		av_packets(NULL),
		av_packet_index(0),
		buffer_frames_before_start(1)
{
	if ( !initialised )
	{
		Initialise( );
	}
	
	if ( format )
	{
		int length = strlen(format);
		codec_and_format = new char[length+1];;
		strcpy( codec_and_format, format );
		format = codec_and_format;
		char *f = strchr(codec_and_format, '/');
		if (f != NULL)
		{
			*f = 0;
			codec_name = f+1;
		}
	}

	SetupFormat( );
	SetupCodec( colours, subpixelorder, width, height, bitrate, frame_rate );
	SetParameters( );
	
	// Allocate av packets used for buffering frames before starting to send data.
	av_packets = new AVPacket*[buffer_frames_before_start];
	for( int index = 0; index < buffer_frames_before_start; index++)
	{
		av_packets[index] = new AVPacket;
		memset(av_packets[index], 0, sizeof(AVPacket));
	}
	
	// Initialize mutex used by streaming thread.
	if ( pthread_mutex_init( buffer_copy_lock, NULL ) != 0 )
	{
		Fatal("pthread_mutex_init failed");
	}
}

VideoStream::~VideoStream( )
{
	Debug( 1, "VideoStream destructor." );
	
	// Stop streaming thread.
	if ( streaming_thread )
	{
		do_streaming = false;
		void* thread_exit_code;
		
		Debug( 1, "Asking streaming thread to exit." );
		
		// Wait for thread to exit.
		pthread_join(streaming_thread, &thread_exit_code);
	}
	
	if ( buffer_copy != NULL )
	{
		av_free( buffer_copy );
	}
	if ( buffer_copy_lock )
	{
		if ( pthread_mutex_destroy( buffer_copy_lock ) != 0 )
		{
			Error( "pthread_mutex_destroy failed" );
		}
		delete buffer_copy_lock;
	}
	if ( av_packets )
	{
		for( int index = 0; index < buffer_frames_before_start; index++ )
		{
			if ( av_packets[index] ) delete av_packets[index];
		}
		delete av_packets;
	}
	
	/* close each codec */
	if ( ost )
	{
		avcodec_close( ost->codec );
		av_free( opicture->data[0] );
		av_free( opicture );
		if ( tmp_opicture )
		{
			av_free( tmp_opicture->data[0] );
			av_free( tmp_opicture );
		}
		av_free( video_outbuf );
	}

	/* write the trailer, if any */
	av_write_trailer( ofc );

	/* free the streams */
	for ( unsigned int i = 0; i < ofc->nb_streams; i++ )
	{
		av_freep( &ofc->streams[i] );
	}

	if ( !(of->flags & AVFMT_NOFILE) )
	{
		/* close the output file */
#if LIBAVUTIL_VERSION_INT >= AV_VERSION_INT(51,2,1)
		avio_close( ofc->pb );
#else
		url_fclose( ofc->pb );
#endif
	}

	/* free the stream */
	av_free( ofc );
	
	/* free format and codec_name data. */
	if ( codec_and_format )
	{
		delete codec_and_format;
	}
}

double VideoStream::EncodeFrame( const uint8_t *buffer, int buffer_size, bool _add_timestamp, unsigned int _timestamp )
{
	if ( pthread_mutex_lock( buffer_copy_lock ) != 0 )
	{
		Fatal( "EncodeFrame: pthread_mutex_lock failed." );
	}
	
	if (buffer_copy_size < buffer_size)
	{
		if ( buffer_copy )
		{
			av_free( buffer_copy );
		}
		
		// Allocate a buffer to store source images for the streaming thread to encode.
		buffer_copy = (uint8_t *)av_malloc( buffer_size );
		if ( !buffer_copy )
		{
			Panic( "Could not allocate buffer_copy" );
		}
		buffer_copy_size = buffer_size;
	}
	
	add_timestamp = _add_timestamp;
	timestamp = _timestamp;
	buffer_copy_used = buffer_size;
	memcpy(buffer_copy, buffer, buffer_size);
	
	if ( pthread_mutex_unlock( buffer_copy_lock ) != 0 )
	{
		Fatal( "EncodeFrame: pthread_mutex_unlock failed." );
	}
	
	if ( streaming_thread == 0 )
	{
		Debug( 1, "Starting streaming thread" );
		
		// Start a thread for streaming encoded video.
		if (pthread_create( &streaming_thread, NULL, StreamingThreadCallback, (void*) this) != 0){
			// Log a fatal error and exit the process.
			Fatal( "VideoStream failed to create streaming thread." );
		}
	}
	
	//return ActuallyEncodeFrame( buffer, buffer_size, add_timestamp, timestamp);
	
	return _timestamp;
}

double VideoStream::ActuallyEncodeFrame( const uint8_t *buffer, int buffer_size, bool add_timestamp, unsigned int timestamp )
{
#ifdef HAVE_LIBSWSCALE
	static struct SwsContext *img_convert_ctx = 0;
#endif // HAVE_LIBSWSCALE

	AVCodecContext *c = ost->codec;

	if ( c->pix_fmt != pf )
	{
		memcpy( tmp_opicture->data[0], buffer, buffer_size );
#ifdef HAVE_LIBSWSCALE
		if ( !img_convert_ctx )
		{
			img_convert_ctx = sws_getCachedContext( NULL, c->width, c->height, pf, c->width, c->height, c->pix_fmt, SWS_BICUBIC, NULL, NULL, NULL );
			if ( !img_convert_ctx )
				Panic( "Unable to initialise image scaling context" );
		}
		sws_scale( img_convert_ctx, tmp_opicture->data, tmp_opicture->linesize, 0, c->height, opicture->data, opicture->linesize );
#else // HAVE_LIBSWSCALE
		Fatal( "swscale is required for MPEG mode" );
#endif // HAVE_LIBSWSCALE
	}
	else
	{
		memcpy( opicture->data[0], buffer, buffer_size );
	}
	AVFrame *opicture_ptr = opicture;

	int ret = 0;
	
	AVPacket *pkt = av_packets[av_packet_index];
	av_init_packet(pkt);
	if ( of->flags & AVFMT_RAWPICTURE )
	{
#if LIBAVUTIL_VERSION_INT >= AV_VERSION_INT(51, 2, 1)
		pkt->flags |= AV_PKT_FLAG_KEY;
#else
		pkt->flags |= PKT_FLAG_KEY;
#endif
		pkt->stream_index = ost->index;
		pkt->data = (uint8_t *)opicture_ptr;
		pkt->size = sizeof (AVPicture);
	}
	else
	{
		opicture_ptr->pts = c->frame_number;
		opicture_ptr->quality = c->global_quality;
                
                if ( opicture_ptr->pts != 0 )
                {
                    opicture_ptr->pts += buffer_frames_before_start - 1;
                }

#if LIBAVFORMAT_VERSION_INT >= AV_VERSION_INT(54, 0, 0)
		int got_packet;
		ret = avcodec_encode_video2( c, pkt, opicture_ptr, &got_packet );
		if ( ret != 0 )
		{
			Fatal( "avcodec_encode_video2 failed with errorcode %d \"%s\"", av_err2str( ret ) );
		}
#else
		int out_size = avcodec_encode_video( c, video_outbuf, video_outbuf_size, opicture_ptr );
		int got_packet = out_size > 0 ? 1 : 0;
		pkt->data = got_packet ? video_outbuf : NULL;
		pkt->size = got_packet ? out_size : 0;
#endif
		if ( !got_packet )
		{
			// Prevent packet from being sent since its empty.
			pkt = NULL;
		}
		else
		{
			if ( c->coded_frame->key_frame )
			{
#if LIBAVUTIL_VERSION_INT >= AV_VERSION_INT(51,2,1)
				pkt->flags |= AV_PKT_FLAG_KEY;
#else
				pkt->flags |= PKT_FLAG_KEY;
#endif
			}

			if ( pkt->pts != AV_NOPTS_VALUE )
			{
				pkt->pts = av_rescale_q( pkt->pts, c->time_base, ost->time_base );
			}
			if ( pkt->dts != AV_NOPTS_VALUE )
			{
				pkt->dts = av_rescale_q( pkt->dts, c->time_base, ost->time_base );
			}
			pkt->duration = av_rescale_q( pkt->duration, c->time_base, ost->time_base );
			pkt->stream_index = ost->index;
		}
	}

	if ( pkt && is_buffering )
	{
		// We need to copy the data because libav reuses it.
		if (pkt->data)
		{
			uint8_t *data = (uint8_t*)av_mallocz(pkt->size);
			memcpy( data, pkt->data, pkt->size );
			pkt->data = data;
		}
		
		// Increase the packet index to use the next buffered packet to contain the next frame.
		av_packet_index++;

		Debug( 2, "Pre-buffered packet %d: pkt.pts=%d, pkt.dts=%d, pkt.duration=%d", av_packet_index, pkt->pts, pkt->dts, pkt->duration );
	}
	return ( opicture_ptr->pts);
}

void VideoStream::SendPackets() {
	int ret = 0;
	if ( is_buffering )
	{
		if ( av_packet_index >= buffer_frames_before_start )
		{
			// We've buffered all frames. Send them.
			// Start by writing the header
			/* write the stream header */
#if LIBAVFORMAT_VERSION_INT < AV_VERSION_INT(53, 4, 0)
			ret = av_write_header( ofc );
#else
			ret = avformat_write_header( ofc, NULL );
#endif
			if ( ret < 0 )
			{
				Fatal( "?_write_header failed with error %d \"%s\"", ret, av_err2str( ret ) );
			}

			Debug( 1, "wrote header." );
			for ( int index = 0; index < buffer_frames_before_start; index++ )
			{
				AVPacket *pkt = av_packets[index];
				ret = av_write_frame( ofc, pkt );
				if ( ret != 0 )
				{
					Fatal( "Error %d while writing pre-buffered video frame: %s", ret, av_err2str( errno ) );
				}

                                Debug( 2, "Sent pre-buffered packet %d: pkt.pts=%d, pkt.dts=%d, pkt.duration=%d", index + 1, pkt->pts, pkt->dts, pkt->duration );

				// Release resources used by the packet.
				av_free_packet( pkt );
				delete pkt;
				av_packets[index] = NULL;
			}

			// Create a new packet to use for future frames.
			av_packets[0] = new AVPacket;
			memset(av_packets[0], 0, sizeof(AVPacket));
			av_packet_index = 0;
			is_buffering = false;
		}
	}
	else
	{
		AVPacket *pkt = av_packets[0];
		ret = av_write_frame( ofc, pkt );
		av_free_packet( pkt );
                
                Debug( 3, "Sent packet: pkt.pts=%d, pkt.dts=%d, pkt.duration=%d", pkt->pts, pkt->dts, pkt->duration );
	}

	if ( ret != 0 )
	{
		Fatal( "Error %d while writing video frame: %s", ret, av_err2str( errno ) );
	}
}

void *VideoStream::StreamingThreadCallback(void *ctx){
	
	Debug( 1, "StreamingThreadCallback started" );
	
    if (ctx == NULL) return NULL;

    VideoStream* videoStream = reinterpret_cast<VideoStream*>(ctx);
	
	const uint64_t nanosecond_multiplier = 1000000000;
	
	uint64_t target_interval_ns = nanosecond_multiplier * ( ((double)videoStream->ost->codec->time_base.num) / (videoStream->ost->codec->time_base.den) );
	uint64_t frame_count = 0;
	timespec start_time;
	clock_gettime(CLOCK_MONOTONIC, &start_time);
	uint64_t start_time_ns = (start_time.tv_sec*nanosecond_multiplier) + start_time.tv_nsec;
	while(videoStream->do_streaming)
	{
		timespec current_time;
		clock_gettime(CLOCK_MONOTONIC, &current_time);
		uint64_t current_time_ns = (current_time.tv_sec*nanosecond_multiplier) + current_time.tv_nsec;
		uint64_t target_ns = start_time_ns + (target_interval_ns * frame_count);
		
		if ( current_time_ns < target_ns )
		{
			// It's not time to render a frame yet.
			usleep( (target_ns - current_time_ns) * 0.001 );
		}
		
		if ( pthread_mutex_lock( videoStream->buffer_copy_lock ) != 0 )
		{
			Fatal( "StreamingThreadCallback: pthread_mutex_lock failed." );
		}
		
		if ( videoStream->buffer_copy )
		{
			// Encode and transmit frame.
			videoStream->ActuallyEncodeFrame( videoStream->buffer_copy, videoStream->buffer_copy_used, videoStream->add_timestamp, videoStream->timestamp );
		}
	
		if ( pthread_mutex_unlock( videoStream->buffer_copy_lock ) != 0 )
		{
			Fatal( "StreamingThreadCallback: pthread_mutex_unlock failed." );
		}
		
		// Send packets outside of the buffer lock because SendPackets blocks.
		videoStream->SendPackets();
		
		frame_count++;
	}
	
	return 0;
}

#endif // HAVE_LIBAVCODEC
