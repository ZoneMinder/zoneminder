/*
 * ZoneMinder MPEG Interface, $Date$, $Revision$
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

#ifndef ZM_MPEG_H
#define ZM_MPEG_H

#include "zm_ffmpeg.h"

#if HAVE_LIBAVCODEC

class VideoStream {
protected:
  struct MimeData {
    const char *format;
    const char *mime_type;
  };

protected:
  static bool initialised;
  static struct MimeData mime_data[];

protected:
  char *codec_and_format;
  const char *filename;
  const char *format;
  const char *codec_name;
  enum _AVPIXELFORMAT pf;
  AVOutputFormat *of;
  AVFormatContext *ofc;
  AVStream *ost;
  AVCodecContext *codec_context;
  AVCodec *codec;
  AVFrame *opicture;
  AVFrame *tmp_opicture;
  uint8_t *video_outbuf;
  int video_outbuf_size;
  double last_pts;

  pthread_t streaming_thread;
  bool do_streaming;
  bool add_timestamp;
  unsigned int timestamp;
  uint8_t *buffer_copy;
  pthread_mutex_t *buffer_copy_lock;
  int buffer_copy_size;
  int buffer_copy_used;
  AVPacket** packet_buffers;
  int packet_index;
  int SendPacket(AVPacket *packet);
  static void* StreamingThreadCallback(void *ctx);

protected:
  static void Initialise();

  void SetupFormat( );
  void SetupCodec( int colours, int subpixelorder, int width, int height, int bitrate, double frame_rate );
  void SetParameters();
  void ActuallyOpenStream();
  double ActuallyEncodeFrame( const uint8_t *buffer, int buffer_size, bool add_timestamp=false, unsigned int timestamp=0 );

public:
  VideoStream( const char *filename, const char *format, int bitrate, double frame_rate, int colours, int subpixelorder, int width, int height );
  ~VideoStream();
  const char *MimeType() const;
  bool OpenStream();
  double EncodeFrame( const uint8_t *buffer, int buffer_size, bool add_timestamp=false, unsigned int timestamp=0 );
};

#endif // HAVE_LIBAVCODEC

#endif // ZM_MPEG_H
