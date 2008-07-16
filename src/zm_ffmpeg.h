/*
 * ZoneMinder FFMPEG Interface, $Date: 2007-12-17 13:17:09 +0000 (Mon, 17 Dec 2007) $, $Revision: 2256 $
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

#ifndef ZM_FFMPEG_H
#define ZM_FFMPEG_H

extern "C" {
#define __STDC_CONSTANT_MACROS
#include <ffmpeg/avcodec.h>
#include <ffmpeg/avformat.h>
#if HAVE_LIBSWSCALE
#include <ffmpeg/swscale.h>
#endif // HAVE_LIBSWSCALE
}

#if FFMPEG_VERSION_INT == 0x000408
#define ZM_FFMPEG_048	1
#elif FFMPEG_VERSION_INT == 0x000409
#if LIBAVCODEC_VERSION_INT < ((50<<16)+(0<<8)+0)
#define ZM_FFMPEG_049	1
#else // LIBAVCODEC_VERSION_INT
#define ZM_FFMPEG_SVN	1
#endif // LIBAVCODEC_VERSION_INT
#else // FFMPEG_VERSION_INT
#define ZM_FFMPEG_SVN	1
#endif // FFMPEG_VERSION_INT

#endif // ZM_FFMPEG_H
                               
#endif // HAVE_LIBAVCODEC
