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
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
 */

#include <stdlib.h>
#include <string.h>

#include "zm.h"
#include "zm_rgb.h"
#include "zm_mpeg.h"

#if HAVE_LIBAVCODEC
extern "C" {
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

void VideoStream::Initialise( ) {
	if ( logDebugging() ) {
    av_log_set_level( AV_LOG_DEBUG );
  } else {
    av_log_set_level( AV_LOG_QUIET );
  }

	av_register_all( );
#if LIBAVFORMAT_VERSION_CHECK(53, 13, 0, 19, 0)
	avformat_network_init();
#endif
	initialised = true;
}

void VideoStream::SetupFormat( ) {
	/* allocate the output media context */
	ofc = NULL;
#if (LIBAVFORMAT_VERSION_CHECK(53, 2, 0, 2, 0) && (LIBAVFORMAT_VERSION_MICRO >= 100))
	avformat_alloc_output_context2( &ofc, NULL, format, filename );
#else
	AVFormatContext *s= avformat_alloc_context();
	if(!s) {
		Fatal( "avformat_alloc_context failed %d \"%s\"", (size_t)ofc, av_err2str((size_t)ofc) );
    return;
	}

	AVOutputFormat *oformat;
	if (format) {
#if LIBAVFORMAT_VERSION_CHECK(52, 45, 0, 45, 0)
		oformat = av_guess_format(format, NULL, NULL);
#else
		oformat = guess_format(format, NULL, NULL);
#endif
		if (!oformat) {
			Fatal( "Requested output format '%s' is not a suitable output format", format );
		}
	} else {
#if LIBAVFORMAT_VERSION_CHECK(52, 45, 0, 45, 0)
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
		if ( !(s->priv_data) ) {
			Fatal( "Could not allocate private data for output format." );
		}
#if LIBAVFORMAT_VERSION_CHECK(52, 92, 0, 92, 0)
		if (s->oformat->priv_class) {
			*(const AVClass**)s->priv_data = s->oformat->priv_class;
			av_opt_set_defaults(s->priv_data);
		}
#endif
	} else {
    Debug(1,"No allocating priv_data");
		s->priv_data = NULL;
	}
	
	if ( filename ) {
		snprintf( s->filename, sizeof(s->filename), "%s", filename );
	}
	
	ofc = s;
#endif
	if ( !ofc ) {
		Fatal( "avformat_alloc_..._context failed: %d", ofc );
	}

	of = ofc->oformat;
	Debug( 1, "Using output format: %s (%s)", of->name, of->long_name );
}

void VideoStream::SetupCodec( int colours, int subpixelorder, int width, int height, int bitrate, double frame_rate ) {
	/* ffmpeg format matching */
	switch(colours) {
	  case ZM_COLOUR_RGB24:
	    if(subpixelorder == ZM_SUBPIX_ORDER_BGR) {
	      /* BGR subpixel order */
	      pf = AV_PIX_FMT_BGR24;
	    } else {
	      /* Assume RGB subpixel order */
	      pf = AV_PIX_FMT_RGB24;
	    }
	    break;
	  case ZM_COLOUR_RGB32:
	    if(subpixelorder == ZM_SUBPIX_ORDER_ARGB) {
	      /* ARGB subpixel order */
	      pf = AV_PIX_FMT_ARGB;
	    } else if(subpixelorder == ZM_SUBPIX_ORDER_ABGR) {
	      /* ABGR subpixel order */
	      pf = AV_PIX_FMT_ABGR;
	    } else if(subpixelorder == ZM_SUBPIX_ORDER_BGRA) {
	      /* BGRA subpixel order */
	      pf = AV_PIX_FMT_BGRA;
	    } else {
	      /* Assume RGBA subpixel order */
	      pf = AV_PIX_FMT_RGBA;
	    }
	    break;
	  case ZM_COLOUR_GRAY8:
	    pf = AV_PIX_FMT_GRAY8;
	    break;
	  default:
	    Panic("Unexpected colours: %d",colours);
	    break;
	}

	if ( strcmp( "rtp", of->name ) == 0 ) {
		// RTP must have a packet_size.
		// Not sure what this value should be really...
		ofc->packet_size = width*height;
    Debug(1,"Setting packet_size to %d", ofc->packet_size);
		
		if ( of->video_codec ==  AV_CODEC_ID_NONE ) {
			// RTP does not have a default codec in ffmpeg <= 0.8.
			of->video_codec = AV_CODEC_ID_MPEG4;
		}
	}
	
	_AVCODECID codec_id = of->video_codec;
	if ( codec_name ) {
    AVCodec *a = avcodec_find_encoder_by_name(codec_name);
    if ( a ) {
      codec_id = a->id;
      Debug( 1, "Using codec \"%s\"", codec_name );
    } else {
#if (LIBAVFORMAT_VERSION_CHECK(53, 8, 0, 11, 0) && (LIBAVFORMAT_VERSION_MICRO >= 100))
      Debug( 1, "Could not find codec \"%s\". Using default \"%s\"", codec_name, avcodec_get_name( codec_id ) );
#else
      Debug( 1, "Could not find codec \"%s\". Using default \"%d\"", codec_name, codec_id );
#endif
    }
	}

	/* add the video streams using the default format codecs
	   and initialize the codecs */
	ost = NULL;
	if ( codec_id != AV_CODEC_ID_NONE ) {
		codec = avcodec_find_encoder( codec_id );
		if ( !codec ) {
#if (LIBAVFORMAT_VERSION_CHECK(53, 8, 0, 11, 0) && (LIBAVFORMAT_VERSION_MICRO >= 100))
			Fatal( "Could not find encoder for '%s'", avcodec_get_name( codec_id ) );
#else
			Fatal( "Could not find encoder for '%d'", codec_id );
#endif
		}

#if (LIBAVFORMAT_VERSION_CHECK(53, 8, 0, 11, 0) && (LIBAVFORMAT_VERSION_MICRO >= 100))
		Debug( 1, "Found encoder for '%s'", avcodec_get_name( codec_id ) );
#else
		Debug( 1, "Found encoder for '%d'", codec_id );
#endif

#if LIBAVFORMAT_VERSION_CHECK(53, 10, 0, 17, 0)
		ost = avformat_new_stream( ofc, codec );
#else
		ost = av_new_stream( ofc, 0 );
#endif
		
		if ( !ost ) {
			Fatal( "Could not alloc stream" );
      return;
		}
		Debug( 1, "Allocated stream (%d) !=? (%d)", ost->id , ofc->nb_streams - 1 );
		ost->id = ofc->nb_streams - 1;

#if LIBAVCODEC_VERSION_CHECK(57, 64, 0, 64, 0)
    
    codec_context = avcodec_alloc_context3(NULL);
    //avcodec_parameters_to_context(codec_context, ost->codecpar);
#else
		codec_context = ost->codec;
#endif

		codec_context->codec_id = codec->id;
		codec_context->codec_type = codec->type;

		codec_context->pix_fmt = strcmp("mjpeg", ofc->oformat->name) == 0 ? AV_PIX_FMT_YUVJ422P : AV_PIX_FMT_YUV420P;
		if ( bitrate <= 100 ) {
			// Quality based bitrate control (VBR). Scale is 1..31 where 1 is best.
			// This gets rid of artifacts in the beginning of the movie; and well, even quality.
#if LIBAVCODEC_VERSION_CHECK(57, 64, 0, 64, 0)
			codec_context->flags |= AV_CODEC_FLAG_QSCALE;
#else
			codec_context->flags |= CODEC_FLAG_QSCALE;
#endif
			codec_context->global_quality = FF_QP2LAMBDA * (31 - (31 * (bitrate / 100.0)));
		} else {
			codec_context->bit_rate = bitrate;
		}

		/* resolution must be a multiple of two */
		codec_context->width = width;
		codec_context->height = height;
		/* time base: this is the fundamental unit of time (in seconds) in terms
		   of which frame timestamps are represented. for fixed-fps content,
		   timebase should be 1/framerate and timestamp increments should be
		   identically 1. */
		codec_context->time_base.den = frame_rate;
		codec_context->time_base.num = 1;
    ost->time_base.den = frame_rate;
		ost->time_base.num = 1;

		
		Debug( 1, "Will encode in %d fps. %dx%d", codec_context->time_base.den, width, height );
		
		/* emit one intra frame every second */
		codec_context->gop_size = frame_rate;

		// some formats want stream headers to be separate
		if ( of->flags & AVFMT_GLOBALHEADER )
#if LIBAVCODEC_VERSION_CHECK(56, 35, 0, 64, 0)
			codec_context->flags |= AV_CODEC_FLAG_GLOBAL_HEADER;
#else
			codec_context->flags |= CODEC_FLAG_GLOBAL_HEADER;
#endif
#if LIBAVCODEC_VERSION_CHECK(57, 64, 0, 64, 0)
    avcodec_parameters_from_context(ost->codecpar, codec_context);
    zm_dump_codecpar(ost->codecpar);
#endif
	} else {
		Fatal( "of->video_codec == AV_CODEC_ID_NONE" );
	}
}

void VideoStream::SetParameters( ) {
}

const char *VideoStream::MimeType( ) const {
	for ( unsigned int i = 0; i < sizeof (mime_data) / sizeof (*mime_data); i++ ) {
		if ( strcmp( format, mime_data[i].format ) == 0 ) {
			Debug( 1, "MimeType is \"%s\"", mime_data[i].mime_type );
			return mime_data[i].mime_type;
		}
	}
	const char *mime_type = of->mime_type;
	if ( !mime_type ) {
		std::string mime = "video/";
		mime = mime.append( format );
		mime_type = mime.c_str( );
		Warning( "Unable to determine mime type for '%s' format, using '%s' as default", format, mime_type );
	}

	Debug(1, "MimeType is \"%s\"", mime_type );

	return mime_type;
}

bool VideoStream::OpenStream( ) {
	int ret;

	/* now that all the parameters are set, we can open the 
	   video codecs and allocate the necessary encode buffers */
	if ( ost ) {
    Debug(1,"Opening codec");
    
		/* open the codec */
#if !LIBAVFORMAT_VERSION_CHECK(53, 8, 0, 8, 0)
		if ( (ret = avcodec_open(codec_context, codec)) < 0 )
#else
		if ( (ret = avcodec_open2(codec_context, codec, 0)) < 0 )
#endif
		{
			Error("Could not open codec. Error code %d \"%s\"", ret, av_err2str(ret));
      return false;
		}

		Debug( 1, "Opened codec" );

		/* allocate the encoded raw picture */
		opicture = zm_av_frame_alloc( );
    if ( !opicture ) {
      Error("Could not allocate opicture");
      return false;
    }
    opicture->width = codec_context->width;
    opicture->height = codec_context->height;
    opicture->format = codec_context->pix_fmt;

#if LIBAVUTIL_VERSION_CHECK(54, 6, 0, 6, 0)
    int size = av_image_get_buffer_size(codec_context->pix_fmt, codec_context->width, codec_context->height, 1);
#else
    int size = avpicture_get_size(codec_context->pix_fmt, codec_context->width, codec_context->height);
#endif

    uint8_t *opicture_buf = (uint8_t *)av_malloc(size);
    if ( !opicture_buf ) {
      av_frame_free( &opicture );
      Error( "Could not allocate opicture_buf" );
      return false;
    }
#if LIBAVUTIL_VERSION_CHECK(54, 6, 0, 6, 0)
    av_image_fill_arrays(opicture->data, opicture->linesize,
      opicture_buf, codec_context->pix_fmt, codec_context->width, codec_context->height, 1);
#else
    avpicture_fill( (AVPicture *)opicture, opicture_buf, codec_context->pix_fmt,
      codec_context->width, codec_context->height );
#endif

    /* if the output format is not identical to the input format, then a temporary
       picture is needed too. It is then converted to the required
       output format */
    tmp_opicture = NULL;
    if ( codec_context->pix_fmt != pf ) {
#if LIBAVCODEC_VERSION_CHECK(55, 28, 1, 45, 101)
			tmp_opicture = av_frame_alloc( );
#else
			tmp_opicture = avcodec_alloc_frame( );
#endif
      if ( !tmp_opicture ) {
        Error( "Could not allocate tmp_opicture" );
        return false;
      }
#if LIBAVUTIL_VERSION_CHECK(54, 6, 0, 6, 0)
      int size = av_image_get_buffer_size( pf, codec_context->width, codec_context->height,1 );
#else
      int size = avpicture_get_size( pf, codec_context->width, codec_context->height );
#endif
      uint8_t *tmp_opicture_buf = (uint8_t *)av_malloc( size );
      if ( !tmp_opicture_buf ) {
        av_frame_free( &tmp_opicture );
        Error( "Could not allocate tmp_opicture_buf" );
        return false;
      }
#if LIBAVUTIL_VERSION_CHECK(54, 6, 0, 6, 0)
      av_image_fill_arrays(tmp_opicture->data,
        tmp_opicture->linesize, tmp_opicture_buf, pf,
        codec_context->width, codec_context->height, 1);
#else
      avpicture_fill( (AVPicture *)tmp_opicture,
        tmp_opicture_buf, pf, codec_context->width, codec_context->height );
#endif
    }
  } // end if ost

  /* open the output file, if needed */
  if ( !(of->flags & AVFMT_NOFILE) ) {
#if LIBAVFORMAT_VERSION_CHECK(53, 15, 0, 21, 0)
		ret = avio_open2( &ofc->pb, filename, AVIO_FLAG_WRITE, NULL, NULL );
#elif LIBAVFORMAT_VERSION_CHECK(52, 102, 0, 102, 0)
		ret = avio_open( &ofc->pb, filename, AVIO_FLAG_WRITE );
#else
		ret = url_fopen( &ofc->pb, filename, AVIO_FLAG_WRITE );
#endif
		if ( ret < 0 ) {
			Error("Could not open '%s'", filename);
      return false;
		}

		Debug(1, "Opened output \"%s\"", filename);
	} else {
		Error( "of->flags & AVFMT_NOFILE" );
    return false;
	}

	video_outbuf = NULL;
#if LIBAVFORMAT_VERSION_CHECK(57, 0, 0, 0, 0)
  if (codec_context->codec_type == AVMEDIA_TYPE_VIDEO &&
      codec_context->codec_id == AV_CODEC_ID_RAWVIDEO) {
#else
	if ( !(of->flags & AVFMT_RAWPICTURE) ) {
#endif
		/* allocate output buffer */
		/* XXX: API change will be done */
		// TODO: Make buffer dynamic.
		video_outbuf_size = 4000000;
		video_outbuf = (uint8_t *)malloc( video_outbuf_size );
		if ( video_outbuf == NULL ) {
			Fatal("Unable to malloc memory for outbuf");
		}
	}

#if LIBAVFORMAT_VERSION_CHECK(52, 101, 0, 101, 0)
	av_dump_format(ofc, 0, filename, 1);
#else
	dump_format(ofc, 0, filename, 1);
#endif

#if !LIBAVFORMAT_VERSION_CHECK(53, 2, 0, 4, 0)
  ret = av_write_header(ofc);
#else
  ret = avformat_write_header(ofc, NULL);
#endif

  if ( ret < 0 ) {
    Error("?_write_header failed with error %d \"%s\"", ret, av_err2str(ret));
    return false;
  }
  return true;
}

VideoStream::VideoStream( const char *in_filename, const char *in_format, int bitrate, double frame_rate, int colours, int subpixelorder, int width, int height ) :
		filename(in_filename),
		format(in_format),
    opicture(NULL),
    tmp_opicture(NULL),
    video_outbuf(NULL),
    video_outbuf_size(0),
		last_pts( -1 ),
		streaming_thread(0),
		do_streaming(true),
    add_timestamp(false),
    timestamp(0),
		buffer_copy(NULL),
		buffer_copy_lock(new pthread_mutex_t),
		buffer_copy_size(0),
		buffer_copy_used(0),
    packet_index(0)
{
	if ( !initialised ) {
		Initialise( );
	}
	
	if ( format ) {
		int length = strlen(format);
		codec_and_format = new char[length+1];;
		strcpy( codec_and_format, format );
		format = codec_and_format;
		codec_name = NULL;
		char *f = strchr(codec_and_format, '/');
		if (f != NULL) {
			*f = 0;
			codec_name = f+1;
		}
	}

  codec_context = NULL;
	SetupFormat( );
	SetupCodec( colours, subpixelorder, width, height, bitrate, frame_rate );
	SetParameters( );
    
  // Allocate buffered packets.
  packet_buffers = new AVPacket*[2];
  packet_buffers[0] = new AVPacket();
  packet_buffers[1] = new AVPacket();
  packet_index = 0;
	
	// Initialize mutex used by streaming thread.
	if ( pthread_mutex_init( buffer_copy_lock, NULL ) != 0 ) {
		Fatal("pthread_mutex_init failed");
	}

}

VideoStream::~VideoStream( ) {
	Debug( 1, "VideoStream destructor." );
	
	// Stop streaming thread.
	if ( streaming_thread ) {
		do_streaming = false;
		void* thread_exit_code;
		
		Debug( 1, "Asking streaming thread to exit." );
		
		// Wait for thread to exit.
		pthread_join(streaming_thread, &thread_exit_code);
	}
	
	if ( buffer_copy != NULL ) {
		av_free( buffer_copy );
	}
    
	if ( buffer_copy_lock ) {
		if ( pthread_mutex_destroy( buffer_copy_lock ) != 0 ) {
			Error( "pthread_mutex_destroy failed" );
		}
		delete buffer_copy_lock;
	}
    
  if (packet_buffers) {
    delete packet_buffers[0];
    delete packet_buffers[1];
    delete[] packet_buffers;
  }
	
	/* close each codec */
	if ( ost ) {
		avcodec_close( codec_context );
		av_free( opicture->data[0] );
		av_frame_free( &opicture );
		if ( tmp_opicture ) {
			av_free( tmp_opicture->data[0] );
			av_frame_free( &tmp_opicture );
		}
		av_free( video_outbuf );
	}

	/* write the trailer, if any */
	av_write_trailer( ofc );

	/* free the streams */
	for ( unsigned int i = 0; i < ofc->nb_streams; i++ ) {
		av_freep( &ofc->streams[i] );
	}

	if ( !(of->flags & AVFMT_NOFILE) ) {
		/* close the output file */
#if LIBAVFORMAT_VERSION_CHECK(52, 105, 0, 105, 0)
		avio_close( ofc->pb );
#else
		url_fclose( ofc->pb );
#endif
	}

	/* free the stream */
	av_free( ofc );
	
	/* free format and codec_name data. */
	if ( codec_and_format ) {
		delete codec_and_format;
	}
}

double VideoStream::EncodeFrame( const uint8_t *buffer, int buffer_size, bool _add_timestamp, unsigned int _timestamp ) {
	if ( pthread_mutex_lock(buffer_copy_lock) != 0 ) {
		Fatal( "EncodeFrame: pthread_mutex_lock failed." );
	}
	
	if (buffer_copy_size < buffer_size) {
		if ( buffer_copy ) {
			av_free(buffer_copy);
		}
		
		// Allocate a buffer to store source images for the streaming thread to encode.
		buffer_copy = (uint8_t *)av_malloc(buffer_size);
		if ( !buffer_copy ) {
			Error( "Could not allocate buffer_copy" );
      pthread_mutex_unlock(buffer_copy_lock);
      return 0;
		}
		buffer_copy_size = buffer_size;
	}
	
	add_timestamp = _add_timestamp;
	timestamp = _timestamp;
	buffer_copy_used = buffer_size;
	memcpy(buffer_copy, buffer, buffer_size);
	
	if ( pthread_mutex_unlock(buffer_copy_lock) != 0 ) {
		Fatal( "EncodeFrame: pthread_mutex_unlock failed." );
	}
	
	if ( streaming_thread == 0 ) {
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

double VideoStream::ActuallyEncodeFrame( const uint8_t *buffer, int buffer_size, bool add_timestamp, unsigned int timestamp ) {

	if ( codec_context->pix_fmt != pf ) {
#ifdef HAVE_LIBSWSCALE
	static struct SwsContext *img_convert_ctx = 0;
#endif // HAVE_LIBSWSCALE
		memcpy( tmp_opicture->data[0], buffer, buffer_size );
#ifdef HAVE_LIBSWSCALE
		if ( !img_convert_ctx ) {
			img_convert_ctx = sws_getCachedContext( NULL, codec_context->width, codec_context->height, pf, codec_context->width, codec_context->height, codec_context->pix_fmt, SWS_BICUBIC, NULL, NULL, NULL );
			if ( !img_convert_ctx )
				Panic( "Unable to initialise image scaling context" );
		}
		sws_scale( img_convert_ctx, tmp_opicture->data, tmp_opicture->linesize, 0, codec_context->height, opicture->data, opicture->linesize );
#else // HAVE_LIBSWSCALE
		Fatal( "swscale is required for MPEG mode" );
#endif // HAVE_LIBSWSCALE
	} else {
		memcpy( opicture->data[0], buffer, buffer_size );
	}
	AVFrame *opicture_ptr = opicture;
	
	AVPacket *pkt = packet_buffers[packet_index];
	av_init_packet( pkt );
  int got_packet = 0;
#if LIBAVFORMAT_VERSION_CHECK(57, 0, 0, 0, 0)
    if (codec_context->codec_type == AVMEDIA_TYPE_VIDEO &&
       codec_context->codec_id == AV_CODEC_ID_RAWVIDEO) {
#else
	if ( of->flags & AVFMT_RAWPICTURE ) {
#endif

#if LIBAVCODEC_VERSION_CHECK(52, 30, 2, 30, 2)
		pkt->flags |= AV_PKT_FLAG_KEY;
#else
		pkt->flags |= PKT_FLAG_KEY;
#endif
		pkt->stream_index = ost->index;
		pkt->data = (uint8_t *)opicture_ptr;
		pkt->size = sizeof (AVPicture);
    got_packet = 1;
	} else {
		opicture_ptr->pts = codec_context->frame_number;
		opicture_ptr->quality = codec_context->global_quality;

#if LIBAVCODEC_VERSION_CHECK(57, 64, 0, 64, 0)
      avcodec_send_frame(codec_context, opicture_ptr);
      int ret = avcodec_receive_packet(codec_context, pkt);
      if ( ret < 0 ) {
        if ( AVERROR_EOF != ret ) {
          Error("ERror encoding video (%d) (%s)", ret,
                av_err2str(ret));
        }
      } else {
        got_packet = 1;
      }
#else

#if LIBAVFORMAT_VERSION_CHECK(54, 1, 0, 2, 100)
		int ret = avcodec_encode_video2( codec_context, pkt, opicture_ptr, &got_packet );
		if ( ret != 0 ) {
			Fatal( "avcodec_encode_video2 failed with errorcode %d \"%s\"", ret, av_err2str( ret ) );
		}
#else
		int out_size = avcodec_encode_video( codec_context, video_outbuf, video_outbuf_size, opicture_ptr );
		got_packet = out_size > 0 ? 1 : 0;
		pkt->data = got_packet ? video_outbuf : NULL;
		pkt->size = got_packet ? out_size : 0;
#endif
#endif
    if ( got_packet ) {
//      if ( c->coded_frame->key_frame )
//      {
//#if LIBAVCODEC_VERSION_CHECK(52, 30, 2, 30, 2)
//        pkt->flags |= AV_PKT_FLAG_KEY;
//#else
//        pkt->flags |= PKT_FLAG_KEY;
//#endif
//      }

      if ( pkt->pts != (int64_t)AV_NOPTS_VALUE ) {
        pkt->pts = av_rescale_q( pkt->pts, codec_context->time_base, ost->time_base );
      }
      if ( pkt->dts != (int64_t)AV_NOPTS_VALUE ) {
        pkt->dts = av_rescale_q( pkt->dts, codec_context->time_base, ost->time_base );
      }
      pkt->duration = av_rescale_q( pkt->duration, codec_context->time_base, ost->time_base );
      pkt->stream_index = ost->index;
    }
  }
  
  return ( opicture_ptr->pts);
}

int VideoStream::SendPacket(AVPacket *packet) {
    
    int ret = av_write_frame( ofc, packet );
    if ( ret != 0 ) {
        Fatal( "Error %d while writing video frame: %s", ret, av_err2str( errno ) );
    }
#if LIBAVCODEC_VERSION_CHECK(57, 8, 0, 12, 100)
    av_packet_unref( packet );
#else
    av_free_packet( packet );
#endif
    return ret;
}

void *VideoStream::StreamingThreadCallback(void *ctx){
	
	Debug( 1, "StreamingThreadCallback started" );
	
  if (ctx == NULL) return NULL;

  VideoStream* videoStream = reinterpret_cast<VideoStream*>(ctx);

	const uint64_t nanosecond_multiplier = 1000000000;

#if LIBAVCODEC_VERSION_CHECK(57, 64, 0, 64, 0)
	uint64_t target_interval_ns = nanosecond_multiplier * ( ((double)videoStream->codec_context->time_base.num) / (videoStream->codec_context->time_base.den) );
#else
	uint64_t target_interval_ns = nanosecond_multiplier * ( ((double)videoStream->codec_context->time_base.num) / (videoStream->codec_context->time_base.den) );
#endif
	uint64_t frame_count = 0;
	timespec start_time;
	clock_gettime(CLOCK_MONOTONIC, &start_time);
	uint64_t start_time_ns = (start_time.tv_sec*nanosecond_multiplier) + start_time.tv_nsec;
	while(videoStream->do_streaming) {
		timespec current_time;
		clock_gettime(CLOCK_MONOTONIC, &current_time);
		uint64_t current_time_ns = (current_time.tv_sec*nanosecond_multiplier) + current_time.tv_nsec;
		uint64_t target_ns = start_time_ns + (target_interval_ns * frame_count);
		
		if ( current_time_ns < target_ns ) {
			// It's not time to render a frame yet.
			usleep( (target_ns - current_time_ns) * 0.001 );
		}

    // By sending the last rendered frame we deliver frames to the client more accurate.
    // If we're encoding the frame before sending it there will be lag.
    // Since this lag is not constant the client may skip frames.

    // Get the last rendered packet.
    AVPacket *packet = videoStream->packet_buffers[videoStream->packet_index];
    if (packet->size) {
      videoStream->SendPacket(packet);
    }
#if LIBAVCODEC_VERSION_CHECK(57, 8, 0, 12, 100)
    av_packet_unref( packet);
#else
    av_free_packet( packet );
#endif
    videoStream->packet_index = videoStream->packet_index ? 0 : 1;

    // Lock buffer and render next frame.
        
		if ( pthread_mutex_lock( videoStream->buffer_copy_lock ) != 0 ) {
			Fatal( "StreamingThreadCallback: pthread_mutex_lock failed." );
		}
		
		if ( videoStream->buffer_copy ) {
			// Encode next frame.
			videoStream->ActuallyEncodeFrame( videoStream->buffer_copy, videoStream->buffer_copy_used, videoStream->add_timestamp, videoStream->timestamp );
		}
	
		if ( pthread_mutex_unlock( videoStream->buffer_copy_lock ) != 0 ) {
			Fatal( "StreamingThreadCallback: pthread_mutex_unlock failed." );
		}
		
		frame_count++;
	}
	
	return 0;
}

#endif // HAVE_LIBAVCODEC
