/*
 * ZoneMinder Jpeg Interface, $Date$, $Revision$
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

#include <setjmp.h>

#include "jinclude.h"
#include "jpeglib.h"
#include "jerror.h"

// Stop complaints about deuplicate definitions
#undef HAVE_STDLIB_H
#undef HAVE_STDDEF_H

extern "C"
{
/* Stuff for overridden error handlers */
struct zm_error_mgr
{
  struct jpeg_error_mgr pub;
  jmp_buf setjmp_buffer;
};

typedef struct zm_error_mgr *zm_error_ptr;

void zm_jpeg_error_silent( j_common_ptr cinfo );
void zm_jpeg_emit_silence( j_common_ptr cinfo, int msg_level );
void zm_jpeg_error_exit( j_common_ptr cinfo );
void zm_jpeg_emit_message( j_common_ptr cinfo, int msg_level );

// Prototypes for memory compress/decompression object */
void zm_jpeg_mem_src(j_decompress_ptr cinfo, const JOCTET *inbuffer, int inbuffer_size );
void zm_jpeg_mem_dest(j_compress_ptr cinfo, JOCTET *outbuffer, int *outbuffer_size );

void zm_use_std_huff_tables( j_decompress_ptr cinfo );
}
