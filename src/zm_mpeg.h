/*
 * ZoneMinder MPEG Interface, $Date$, $Revision$
 * Copyright (C) 2003, 2004, 2005, 2006  Philip Coombes
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

#include "zm.h"

#if HAVE_LIBAVCODEC

#ifndef ZM_MPEG_H
#define ZM_MPEG_H

#include <ffmpeg/avformat.h>

#if FFMPEG_VERSION_INT == 0x000408
#define ZM_FFMPEG_048	1
#elif FFMPEG_VERSION_INT == 0x000409
#if LIBAVCODEC_VERSION_INT < ((50<<16)+(0<<8)+0)
#define ZM_FFMPEG_049	1
#else // LIBAVCODEC_VERSION_INT
#define ZM_FFMPEG_CVS	1
#endif // LIBAVCODEC_VERSION_INT
#endif // FFMPEG_VERSION_INT

class VideoStream
{
protected:
	struct MimeData
	{
		const char *format;
		const char *mime_type;
	};

protected:
	static bool initialised;
	static struct MimeData mime_data[];

protected:
	const char *filename;
	const char *format;
	enum PixelFormat pf;
	AVOutputFormat *of;
	AVFormatContext *ofc;
	AVStream *ost;
	AVFrame *opicture;
	AVFrame *tmp_opicture;
	uint8_t *video_outbuf;
	int video_outbuf_size;
	double pts;

protected:
	static void Initialise();

	void SetupFormat( const char *p_filename, const char *format );
	void SetupCodec( int colours, int width, int height, int bitrate, int frame_rate );
	void SetParameters();

public:
	VideoStream( const char *filename, const char *format, int bitrate, int frame_rate, int colours, int width, int height );
	~VideoStream();
	const char *MimeType() const;
	void OpenStream();
	double EncodeFrame( uint8_t *buffer, int buffer_size, bool add_timestamp=false, unsigned int timestamp=0 );
};

#endif // ZM_MPEG_H
                               
#endif // HAVE_LIBAVCODEC
