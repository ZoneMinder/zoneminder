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

#include "zm_mpeg.h"

#include "zm_logger.h"
#include "zm_rgb.h"
#include "zm_time.h"

bool VideoStream::initialised = false;

VideoStream::MimeData VideoStream::mime_data[] = {
  { "asf", "video/x-ms-asf" },
  { "swf", "application/x-shockwave-flash" },
  { "flv", "video/x-flv" },
  { "mov", "video/quicktime" }
};

void VideoStream::Initialise( ) {
  FFMPEGInit();
  initialised = true;
}

void VideoStream::SetupFormat( ) {
  /* allocate the output media context */
  ofc = nullptr;
  avformat_alloc_output_context2(&ofc, nullptr, format, filename);

  if (!ofc) {
    Fatal("avformat_alloc_..._context failed");
  }

  of = ofc->oformat;
  Debug(1, "Using output format: %s (%s)", of->name, of->long_name);
}

int VideoStream::SetupCodec(
  int colours,
  int subpixelorder,
  int width,
  int height,
  int bitrate,
  double frame_rate
) {
  /* ffmpeg format matching */
  switch (colours) {
  case ZM_COLOUR_RGB24:
    if (subpixelorder == ZM_SUBPIX_ORDER_BGR) {
      /* BGR subpixel order */
      pf = AV_PIX_FMT_BGR24;
    } else {
      /* Assume RGB subpixel order */
      pf = AV_PIX_FMT_RGB24;
    }
    break;
  case ZM_COLOUR_RGB32:
    if (subpixelorder == ZM_SUBPIX_ORDER_ARGB) {
      /* ARGB subpixel order */
      pf = AV_PIX_FMT_ARGB;
    } else if (subpixelorder == ZM_SUBPIX_ORDER_ABGR) {
      /* ABGR subpixel order */
      pf = AV_PIX_FMT_ABGR;
    } else if (subpixelorder == ZM_SUBPIX_ORDER_BGRA) {
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

  if (strcmp("rtp", of->name) == 0) {
    // RTP must have a packet_size.
    // Not sure what this value should be really...
    ofc->packet_size = width*height;
    Debug(1,"Setting packet_size to %d", ofc->packet_size);
  }

  _AVCODECID codec_id = of->video_codec;
  if (codec_name) {
    const AVCodec *a = avcodec_find_encoder_by_name(codec_name);
    if (a) {
      codec_id = a->id;
      Debug(1, "Using codec \"%s\"", codec_name);
    } else {
      Debug(1, "Could not find codec \"%s\". Using default \"%s\"", codec_name, avcodec_get_name(codec_id));
    }
  }

  /* add the video streams using the default format codecs
     and initialize the codecs */
  ost = nullptr;
  if (codec_id != AV_CODEC_ID_NONE) {
    codec = avcodec_find_encoder(codec_id);
    if (!codec) {
      Error("Could not find encoder for '%s'", avcodec_get_name(codec_id));
      return -1;
    }

    Debug(1, "Found encoder for '%s'", avcodec_get_name(codec_id));
    ost = avformat_new_stream(ofc, codec);
    if (!ost) {
      Error("Could not alloc stream");
      return -1;
    }
    Debug(1, "Allocated stream (%d) !=? (%d)", ost->id, ofc->nb_streams - 1);
    ost->id = ofc->nb_streams - 1;

    codec_context = avcodec_alloc_context3(nullptr);
    //avcodec_parameters_to_context(codec_context, ost->codecpar);
    codec_context->codec_id = codec->id;
    codec_context->codec_type = codec->type;

    codec_context->pix_fmt = strcmp("mjpeg", ofc->oformat->name) == 0 ? AV_PIX_FMT_YUVJ422P : AV_PIX_FMT_YUV420P;
    if (bitrate <= 100) {
      // Quality based bitrate control (VBR). Scale is 1..31 where 1 is best.
      // This gets rid of artifacts in the beginning of the movie; and well, even quality.
      codec_context->flags |= AV_CODEC_FLAG_QSCALE;
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
    if (of->flags & AVFMT_GLOBALHEADER)
      codec_context->flags |= AV_CODEC_FLAG_GLOBAL_HEADER;

    avcodec_parameters_from_context(ost->codecpar, codec_context);
    zm_dump_codecpar(ost->codecpar);
  } else {
    Error("of->video_codec == AV_CODEC_ID_NONE");
    return -1;
  }
  return 0;
}

void VideoStream::SetParameters( ) {
}

const char *VideoStream::MimeType() const {
  for ( unsigned int i = 0; i < sizeof (mime_data) / sizeof (*mime_data); i++ ) {
    if ( strcmp(format, mime_data[i].format) == 0 ) {
      Debug(1, "MimeType is \"%s\"", mime_data[i].mime_type);
      return mime_data[i].mime_type;
    }
  }
  const char *mime_type = of->mime_type;
  if ( !mime_type ) {
    std::string mime = std::string("video/") + format;
    mime_type = strdup(mime.c_str()); // mem leak
    Warning( "Unable to determine mime type for '%s' format, using '%s' as default", format, mime_type);
  }

  Debug(1, "MimeType is \"%s\"", mime_type);

  return mime_type;
}

bool VideoStream::OpenStream( ) {
  int ret;

  /* now that all the parameters are set, we can open the
     video codecs and allocate the necessary encode buffers */
  if ( ost ) {
    Debug(1,"Opening codec");

    /* open the codec */

    if ((ret = avcodec_open2(codec_context, codec, nullptr)) < 0) {
      Error("Could not open codec. Error code %d \"%s\"", ret, av_err2str(ret));
      return false;
    }

    Debug( 1, "Opened codec" );

    /* allocate the encoded raw picture */
    opicture = av_frame_ptr{zm_av_frame_alloc()};
    if (!opicture) {
      Error("Could not allocate opicture");
      return false;
    }
    opicture->width = codec_context->width;
    opicture->height = codec_context->height;
    opicture->format = codec_context->pix_fmt;

    int size = av_image_get_buffer_size(codec_context->pix_fmt, codec_context->width, codec_context->height, 1);

    opicture->buf[0] = av_buffer_alloc(size);
    if (!opicture->buf[0]) {
      Error( "Could not allocate opicture buffer" );
      return false;
    }
    av_image_fill_arrays(opicture->data, opicture->linesize,
                         opicture->buf[0]->data, codec_context->pix_fmt, codec_context->width, codec_context->height, 1);

    /* if the output format is not identical to the input format, then a temporary
       picture is needed too. It is then converted to the required
       output format */
    if ( codec_context->pix_fmt != pf ) {
      tmp_opicture = av_frame_ptr{av_frame_alloc()};

      if (!tmp_opicture) {
        Error("Could not allocate tmp_opicture");
        return false;
      }
      size = av_image_get_buffer_size(pf, codec_context->width, codec_context->height, 1);
      tmp_opicture->buf[0] = av_buffer_alloc(size);
      if (!tmp_opicture->buf[0]) {
        Error( "Could not allocate tmp_opicture buffer" );
        return false;
      }

      av_image_fill_arrays(tmp_opicture->data,
                           tmp_opicture->linesize, tmp_opicture->buf[0]->data, pf,
                           codec_context->width, codec_context->height, 1);
    }
  } // end if ost

  /* open the output file, if needed */
  if ( !(of->flags & AVFMT_NOFILE) ) {
    ret = avio_open2( &ofc->pb, filename, AVIO_FLAG_WRITE, nullptr, nullptr );

    if ( ret < 0 ) {
      Error("Could not open '%s'", filename);
      return false;
    }

    Debug(1, "Opened output \"%s\"", filename);
  } else {
    Error( "of->flags & AVFMT_NOFILE" );
    return false;
  }

  video_outbuf = nullptr;
  if (codec_context->codec_type == AVMEDIA_TYPE_VIDEO &&
      codec_context->codec_id == AV_CODEC_ID_RAWVIDEO) {
    /* allocate output buffer */
    /* XXX: API change will be done */
    // TODO: Make buffer dynamic.
    video_outbuf_size = 4000000;
    video_outbuf = (uint8_t *)malloc( video_outbuf_size );
    if ( video_outbuf == nullptr ) {
      Fatal("Unable to malloc memory for outbuf");
    }
  }

  av_dump_format(ofc, 0, filename, 1);

  ret = avformat_write_header(ofc, nullptr);

  if ( ret < 0 ) {
    Error("?_write_header failed with error %d \"%s\"", ret, av_err2str(ret));
    return false;
  }
  return true;
}

VideoStream::VideoStream( const char *in_filename, const char *in_format, int bitrate, double frame_rate, int colours, int subpixelorder, int width, int height ) :
  filename(in_filename),
  format(in_format),
  video_outbuf(nullptr),
  video_outbuf_size(0),
  last_pts( -1 ),
  streaming_thread(0),
  do_streaming(true),
  add_timestamp(false),
  timestamp(0),
  buffer_copy(nullptr),
  buffer_copy_lock(new pthread_mutex_t),
  buffer_copy_size(0),
  buffer_copy_used(0),
  packet_index(0) {
  if ( !initialised ) {
    Initialise( );
  }

  if ( format ) {
    int length = strlen(format);
    codec_and_format = new char[length+1];;
    strcpy( codec_and_format, format );
    format = codec_and_format;
    codec_name = nullptr;
    char *f = strchr(codec_and_format, '/');
    if (f != nullptr) {
      *f = 0;
      codec_name = f+1;
    }
  }

  codec_context = nullptr;
  SetupFormat( );
  SetupCodec( colours, subpixelorder, width, height, bitrate, frame_rate );
  SetParameters( );

  // Allocate buffered packets.
  for (auto &pkt : packet_buffers)
    pkt = av_packet_ptr{av_packet_alloc()};

  // Initialize mutex used by streaming thread.
  if ( pthread_mutex_init( buffer_copy_lock, nullptr ) != 0 ) {
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

  if ( buffer_copy != nullptr ) {
    av_free( buffer_copy );
  }

  if ( buffer_copy_lock ) {
    if ( pthread_mutex_destroy( buffer_copy_lock ) != 0 ) {
      Error( "pthread_mutex_destroy failed" );
    }
    delete buffer_copy_lock;
  }

  /* close each codec */
  if ( ost ) {
    //avcodec_close( codec_context );
    avcodec_free_context(&codec_context);
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
    avio_close( ofc->pb );
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
    if (pthread_create( &streaming_thread, nullptr, StreamingThreadCallback, (void*) this) != 0) {
      // Log a fatal error and exit the process.
      Fatal( "VideoStream failed to create streaming thread." );
    }
  }

  //return ActuallyEncodeFrame( buffer, buffer_size, add_timestamp, timestamp);

  return _timestamp;
}

double VideoStream::ActuallyEncodeFrame( const uint8_t *buffer, int buffer_size, bool add_timestamp, unsigned int timestamp ) {
  if ( codec_context->pix_fmt != pf ) {
    static struct SwsContext *img_convert_ctx = nullptr;
    memcpy( tmp_opicture->data[0], buffer, buffer_size );
    if ( !img_convert_ctx ) {
      img_convert_ctx = sws_getCachedContext( nullptr, codec_context->width, codec_context->height, pf, codec_context->width, codec_context->height, codec_context->pix_fmt, SWS_BICUBIC, nullptr, nullptr, nullptr );
      if ( !img_convert_ctx )
        Panic( "Unable to initialise image scaling context" );
    }
    sws_scale( img_convert_ctx, tmp_opicture->data, tmp_opicture->linesize, 0, codec_context->height, opicture->data, opicture->linesize );
  } else {
    memcpy( opicture->data[0], buffer, buffer_size );
  }

  AVFrame *opicture_ptr = opicture.get();
  AVPacket *pkt = packet_buffers[packet_index].get();

  if (codec_context->codec_type == AVMEDIA_TYPE_VIDEO &&
      codec_context->codec_id == AV_CODEC_ID_RAWVIDEO) {
    pkt->flags |= AV_PKT_FLAG_KEY;
    pkt->stream_index = ost->index;
    pkt->data = (uint8_t *)opicture_ptr;
    pkt->size = buffer_size;
  } else {
#if LIBAVCODEC_VERSION_CHECK(60, 2, 0, 2, 100)
    opicture_ptr->pts = codec_context->frame_num;
#else
    opicture_ptr->pts = codec_context->frame_number;
#endif
    opicture_ptr->quality = codec_context->global_quality;

    avcodec_send_frame(codec_context, opicture_ptr);
    int ret = avcodec_receive_packet(codec_context, pkt);
    if (ret < 0) {
      if (AVERROR_EOF != ret) {
        Error("Error encoding video (%d) (%s)", ret, av_err2str(ret));
      }
    } else {
      //      if ( c->coded_frame->key_frame )
      //      {
      //        pkt->flags |= AV_PKT_FLAG_KEY;
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

  return opicture_ptr->pts;
}

int VideoStream::SendPacket(AVPacket *packet) {
  int ret = av_write_frame(ofc, packet);
  if (ret < 0) {
    Error("Error %d while writing video frame: %s", ret, av_err2str(errno));
  }
  av_packet_unref(packet);
  return ret;
}

void *VideoStream::StreamingThreadCallback(void *ctx) {
  Debug(1, "StreamingThreadCallback started");

  if (ctx == nullptr) {
    return nullptr;
  }

  VideoStream *videoStream = reinterpret_cast<VideoStream *>(ctx);

  TimePoint::duration target_interval = std::chrono::duration_cast<TimePoint::duration>(FPSeconds(
                                          videoStream->codec_context->time_base.num / static_cast<double>(videoStream->codec_context->time_base.den)));

  uint64_t frame_count = 0;
  TimePoint start_time = std::chrono::steady_clock::now();

  while (videoStream->do_streaming) {
    TimePoint current_time = std::chrono::steady_clock::now();
    TimePoint target = start_time + (target_interval * frame_count);

    if (current_time < target) {
      // It's not time to render a frame yet.
      std::this_thread::sleep_for(target - current_time);
    }

    // By sending the last rendered frame we deliver frames to the client more accurate.
    // If we're encoding the frame before sending it there will be lag.
    // Since this lag is not constant the client may skip frames.

    // Get the last rendered packet.
    AVPacket *packet = videoStream->packet_buffers[videoStream->packet_index].get();
    if (packet->size) {
      videoStream->SendPacket(packet);
    }
    av_packet_unref(packet);

    videoStream->packet_index = videoStream->packet_index ? 0 : 1;

    // Lock buffer and render next frame.
    if (pthread_mutex_lock(videoStream->buffer_copy_lock) != 0) {
      Fatal("StreamingThreadCallback: pthread_mutex_lock failed.");
    }

    if (videoStream->buffer_copy) {
      // Encode next frame.
      videoStream->ActuallyEncodeFrame(videoStream->buffer_copy,
                                       videoStream->buffer_copy_used,
                                       videoStream->add_timestamp,
                                       videoStream->timestamp);
    }

    if (pthread_mutex_unlock(videoStream->buffer_copy_lock) != 0) {
      Fatal("StreamingThreadCallback: pthread_mutex_unlock failed.");
    }

    frame_count++;
  }

  return nullptr;
}
