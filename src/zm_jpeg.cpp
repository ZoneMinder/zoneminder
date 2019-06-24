/*
 * ZoneMinder JPEG memory encoding/decoding, $Date$, $Revision$
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

#include "zm_jpeg.h"
#include "zm_logger.h"

#include <unistd.h>

/* Overridden error handlers, mostly for decompression */
extern "C"
{

#define MAX_JPEG_ERRS 25

static int jpeg_err_count = 0;

void zm_jpeg_error_silent( j_common_ptr cinfo ){
  zm_error_ptr zmerr = (zm_error_ptr)cinfo->err;
  longjmp( zmerr->setjmp_buffer, 1 );
}
void zm_jpeg_emit_silence( j_common_ptr cinfo, int msg_level ){
}

void zm_jpeg_error_exit( j_common_ptr cinfo )
{
  static char buffer[JMSG_LENGTH_MAX];
  zm_error_ptr zmerr = (zm_error_ptr)cinfo->err;

  (zmerr->pub.format_message)( cinfo, buffer ); 

  Error( "%s", buffer );
  if ( ++jpeg_err_count == MAX_JPEG_ERRS )
  {
    Fatal( "Maximum number (%d) of JPEG errors reached, exiting", jpeg_err_count );
  }

  longjmp( zmerr->setjmp_buffer, 1 );
}

void zm_jpeg_emit_message( j_common_ptr cinfo, int msg_level )
{
  static char buffer[JMSG_LENGTH_MAX];
  zm_error_ptr zmerr = (zm_error_ptr)cinfo->err;

  if ( msg_level < 0 )
  {
    /* It's a warning message.  Since corrupt files may generate many warnings,
     * the policy implemented here is to show only the first warning,
     * unless trace_level >= 3.
     */
    if ( zmerr->pub.num_warnings == 0 || zmerr->pub.trace_level >= 3 )
    {
      (zmerr->pub.format_message)( cinfo, buffer );
      if (!strstr(buffer, "Corrupt JPEG data:")) 
      Warning( "%s", buffer );
    }
    /* Always count warnings in num_warnings. */
    zmerr->pub.num_warnings++;
  }
  else
  {
    /* It's a trace message.  Show it if trace_level >= msg_level. */
    if ( zmerr->pub.trace_level >= msg_level )
    {
      (zmerr->pub.format_message)( cinfo, buffer ); 
      Debug( msg_level, "%s", buffer );
    }
  }
}

/* Expanded data destination object for memory */

typedef struct
{
  struct jpeg_destination_mgr pub; /* public fields */

  JOCTET *outbuffer;    /* target buffer */
  int   *outbuffer_size;
  JOCTET *buffer;    /* start of buffer */
} mem_destination_mgr;

typedef mem_destination_mgr * mem_dest_ptr;

#define OUTPUT_BUF_SIZE  4096  /* choose an efficiently fwrite'able size */

/*
 * Initialize destination --- called by jpeg_start_compress
 * before any data is actually written.
 */

static void init_destination (j_compress_ptr cinfo)
{
  mem_dest_ptr dest = (mem_dest_ptr) cinfo->dest;

  /* Allocate the output buffer --- it will be released when done with image */
  dest->buffer = (JOCTET *)(*cinfo->mem->alloc_small) ((j_common_ptr) cinfo, JPOOL_IMAGE, OUTPUT_BUF_SIZE * SIZEOF(JOCTET));

  dest->pub.next_output_byte = dest->buffer;
  dest->pub.free_in_buffer = OUTPUT_BUF_SIZE;

  *(dest->outbuffer_size) = 0;
}


/*
 * Empty the output buffer --- called whenever buffer fills up.
 *
 * In typical applications, this should write the entire output buffer
 * (ignoring the current state of next_output_byte & free_in_buffer),
 * reset the pointer & count to the start of the buffer, and return TRUE
 * indicating that the buffer has been dumped.
 *
 * In applications that need to be able to suspend compression due to output
 * overrun, a FALSE return indicates that the buffer cannot be emptied now.
 * In this situation, the compressor will return to its caller (possibly with
 * an indication that it has not accepted all the supplied scanlines).  The
 * application should resume compression after it has made more room in the
 * output buffer.  Note that there are substantial restrictions on the use of
 * suspension --- see the documentation.
 *
 * When suspending, the compressor will back up to a convenient restart point
 * (typically the start of the current MCU). next_output_byte & free_in_buffer
 * indicate where the restart point will be if the current call returns FALSE.
 * Data beyond this point will be regenerated after resumption, so do not
 * write it out when emptying the buffer externally.
 */

static boolean empty_output_buffer (j_compress_ptr cinfo)
{
  mem_dest_ptr dest = (mem_dest_ptr) cinfo->dest;

  memcpy( dest->outbuffer+*(dest->outbuffer_size), dest->buffer, OUTPUT_BUF_SIZE );
  *(dest->outbuffer_size) += OUTPUT_BUF_SIZE;

  dest->pub.next_output_byte = dest->buffer;
  dest->pub.free_in_buffer = OUTPUT_BUF_SIZE;

  return( TRUE );
}

/*
 * Terminate destination --- called by jpeg_finish_compress
 * after all data has been written.  Usually needs to flush buffer.
 *
 * NB: *not* called by jpeg_abort or jpeg_destroy; surrounding
 * application must deal with any cleanup that should happen even
 * for error exit.
 */

static void term_destination (j_compress_ptr cinfo)
{
  mem_dest_ptr dest = (mem_dest_ptr) cinfo->dest;
  size_t datacount = OUTPUT_BUF_SIZE - dest->pub.free_in_buffer;

  if ( datacount > 0 )
  {
    memcpy( dest->outbuffer+*(dest->outbuffer_size), dest->buffer, datacount );
    *(dest->outbuffer_size) += datacount;
  }
}


/*
 * Prepare for output to a stdio stream.
 * The caller must have already opened the stream, and is responsible
 * for closing it after finishing compression.
 */

void zm_jpeg_mem_dest (j_compress_ptr cinfo, JOCTET *outbuffer, int *outbuffer_size )
{
  mem_dest_ptr dest;

  /* The destination object is made permanent so that multiple JPEG images
   * can be written to the same file without re-executing jpeg_stdio_dest.
   * This makes it dangerous to use this manager and a different destination
   * manager serially with the same JPEG object, because their private object
   * sizes may be different.  Caveat programmer.
   */
  if ( cinfo->dest == NULL )
  {
    /* first time for this JPEG object? */
    cinfo->dest = (struct jpeg_destination_mgr *)(*cinfo->mem->alloc_small) ((j_common_ptr) cinfo, JPOOL_PERMANENT, SIZEOF(mem_destination_mgr));
  }

  dest = (mem_dest_ptr) cinfo->dest;
  dest->pub.init_destination = init_destination;
  dest->pub.empty_output_buffer = empty_output_buffer;
  dest->pub.term_destination = term_destination;
  dest->outbuffer = outbuffer;
  dest->outbuffer_size = outbuffer_size;
}

/* Expanded data source object for memory input */

typedef struct
{
  struct jpeg_source_mgr pub;  /* public fields */

  JOCTET * inbuffer;    /* source stream */
  int  inbuffer_size;
  int  inbuffer_size_hwm; /* High water mark */

  JOCTET * buffer;    /* start of buffer */
  boolean start_of_data;  /* have we gotten any data yet? */
} mem_source_mgr;

typedef mem_source_mgr * mem_src_ptr;

#define INPUT_BUF_SIZE  4096  /* choose an efficiently fread'able size */

/*
 * Initialize source --- called by jpeg_read_header
 * before any data is actually read.
 */

static void init_source (j_decompress_ptr cinfo)
{
  mem_src_ptr src = (mem_src_ptr) cinfo->src;

  /* We reset the empty-input-file flag for each image,
   * but we don't clear the input buffer.
   * This is correct behavior for reading a series of images from one source.
   */
  src->start_of_data = TRUE;
  src->pub.bytes_in_buffer = 0;
}


/*
 * Fill the input buffer --- called whenever buffer is emptied.
 *
 * In typical applications, this should read fresh data into the buffer
 * (ignoring the current state of next_input_byte & bytes_in_buffer),
 * reset the pointer & count to the start of the buffer, and return TRUE
 * indicating that the buffer has been reloaded.  It is not necessary to
 * fill the buffer entirely, only to obtain at least one more byte.
 *
 * There is no such thing as an EOF return.  If the end of the file has been
 * reached, the routine has a choice of ERREXIT() or inserting fake data into
 * the buffer.  In most cases, generating a warning message and inserting a
 * fake EOI marker is the best course of action --- this will allow the
 * decompressor to output however much of the image is there.  However,
 * the resulting error message is misleading if the real problem is an empty
 * input file, so we handle that case specially.
 *
 * In applications that need to be able to suspend compression due to input
 * not being available yet, a FALSE return indicates that no more data can be
 * obtained right now, but more may be forthcoming later.  In this situation,
 * the decompressor will return to its caller (with an indication of the
 * number of scanlines it has read, if any).  The application should resume
 * decompression after it has loaded more data into the input buffer.  Note
 * that there are substantial restrictions on the use of suspension --- see
 * the documentation.
 *
 * When suspending, the decompressor will back up to a convenient restart point
 * (typically the start of the current MCU). next_input_byte & bytes_in_buffer
 * indicate where the restart point will be if the current call returns FALSE.
 * Data beyond this point must be rescanned after resumption, so move it to
 * the front of the buffer rather than discarding it.
 */

static boolean fill_input_buffer (j_decompress_ptr cinfo)
{
  mem_src_ptr src = (mem_src_ptr) cinfo->src;
  size_t nbytes;

  memcpy( src->buffer, src->inbuffer, (size_t) src->inbuffer_size );
  nbytes = src->inbuffer_size;

  if ( nbytes == 0 ) {
    if ( src->start_of_data )  /* Treat empty input file as fatal error */
      ERREXIT(cinfo, JERR_INPUT_EMPTY);
    WARNMS(cinfo, JWRN_JPEG_EOF);
    /* Insert a fake EOI marker */
    src->buffer[0] = (JOCTET) 0xFF;
    src->buffer[1] = (JOCTET) JPEG_EOI;
    nbytes = 2;
  }

  src->pub.next_input_byte = src->buffer;
  src->pub.bytes_in_buffer = nbytes;
  src->start_of_data = FALSE;

  return( TRUE );
}


/*
 * Skip data --- used to skip over a potentially large amount of
 * uninteresting data (such as an APPn marker).
 *
 * Writers of suspendable-input applications must note that skip_input_data
 * is not granted the right to give a suspension return.  If the skip extends
 * beyond the data currently in the buffer, the buffer can be marked empty so
 * that the next read will cause a fill_input_buffer call that can suspend.
 * Arranging for additional bytes to be discarded before reloading the input
 * buffer is the application writer's problem.
 */

static void skip_input_data (j_decompress_ptr cinfo, long num_bytes)
{
  mem_src_ptr src = (mem_src_ptr) cinfo->src;

  /* Just a dumb implementation for now.  Could use fseek() except
   * it doesn't work on pipes.  Not clear that being smart is worth
   * any trouble anyway --- large skips are infrequent.
   */
  if ( num_bytes > 0 )
  {
    while ( num_bytes > (long) src->pub.bytes_in_buffer )
    {
      num_bytes -= (long) src->pub.bytes_in_buffer;
      (void) fill_input_buffer(cinfo);
      /* note we assume that fill_input_buffer will never return FALSE,
       * so suspension need not be handled.
       */
    }
    src->pub.next_input_byte += (size_t) num_bytes;
    src->pub.bytes_in_buffer -= (size_t) num_bytes;
  }
}


/*
 * Terminate source --- called by jpeg_finish_decompress
 * after all data has been read.  Often a no-op.
 *
 * NB: *not* called by jpeg_abort or jpeg_destroy; surrounding
 * application must deal with any cleanup that should happen even
 * for error exit.
 */

static void term_source (j_decompress_ptr cinfo)
{
  /* no work necessary here */
}


/*
 * Prepare for input from a memory stream.
 * The caller must have already opened the stream, and is responsible
 * for closing it after finishing decompression.
 */

void zm_jpeg_mem_src( j_decompress_ptr cinfo, const JOCTET *inbuffer, int inbuffer_size )
{
  mem_src_ptr src;

  /* The source object and input buffer are made permanent so that a series
   * of JPEG images can be read from the same file by calling zm_jpeg_mem_src
   * only before the first one.  (If we discarded the buffer at the end of
   * one image, we'd likely lose the start of the next one.)
   * This makes it unsafe to use this manager and a different source
   * manager serially with the same JPEG object.  Caveat programmer.
   */
  if ( cinfo->src == NULL )
  {
      /* first time for this JPEG object? */
    cinfo->src = (struct jpeg_source_mgr *)(*cinfo->mem->alloc_small) ((j_common_ptr) cinfo, JPOOL_PERMANENT, SIZEOF(mem_source_mgr));
    src = (mem_src_ptr) cinfo->src;
    src->buffer = (JOCTET *)(*cinfo->mem->alloc_small) ((j_common_ptr) cinfo, JPOOL_PERMANENT, inbuffer_size * SIZEOF(JOCTET));
    src->inbuffer_size_hwm = inbuffer_size;
  }
  else
  {
    src = (mem_src_ptr) cinfo->src;
    if ( src->inbuffer_size_hwm < inbuffer_size )
    {
      src->buffer = (JOCTET *)(*cinfo->mem->alloc_small) ((j_common_ptr) cinfo, JPOOL_PERMANENT, inbuffer_size * SIZEOF(JOCTET));
      src->inbuffer_size_hwm = inbuffer_size;
    }
  }

  src = (mem_src_ptr) cinfo->src;
  src->pub.init_source = init_source;
  src->pub.fill_input_buffer = fill_input_buffer;
  src->pub.skip_input_data = skip_input_data;
  src->pub.resync_to_restart = jpeg_resync_to_restart; /* use default method */
  src->pub.term_source = term_source;
  src->inbuffer = (JOCTET *)inbuffer;
  src->inbuffer_size = inbuffer_size;
  src->pub.bytes_in_buffer = 0; /* forces fill_input_buffer on first read */
  src->pub.next_input_byte = NULL; /* until buffer loaded */
}

void zm_use_std_huff_tables( j_decompress_ptr cinfo ) {
  /* JPEG standard Huffman tables (cf. JPEG standard section K.3) */
  /* IMPORTANT: these are only valid for 8-bit data precision! */
  static const JHUFF_TBL dclumin = {
    { /* 0-base */ 0, 0, 1, 5, 1, 1, 1, 1, 1, 1, 0, 0, 0, 0, 0, 0, 0 },
    { 0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11 },
    FALSE
  };
  static const JHUFF_TBL dcchrome = {
    { /* 0-base */ 0, 0, 3, 1, 1, 1, 1, 1, 1, 1, 1, 1, 0, 0, 0, 0, 0 },
    { 0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11 },
    FALSE
  };
  static const JHUFF_TBL aclumin = {
    { /* 0-base */ 0, 0, 2, 1, 3, 3, 2, 4, 3, 5, 5, 4, 4, 0, 0, 1, 0x7d },
    { 0x01, 0x02, 0x03, 0x00, 0x04, 0x11, 0x05, 0x12,
    0x21, 0x31, 0x41, 0x06, 0x13, 0x51, 0x61, 0x07,
    0x22, 0x71, 0x14, 0x32, 0x81, 0x91, 0xa1, 0x08,
    0x23, 0x42, 0xb1, 0xc1, 0x15, 0x52, 0xd1, 0xf0,
    0x24, 0x33, 0x62, 0x72, 0x82, 0x09, 0x0a, 0x16,
    0x17, 0x18, 0x19, 0x1a, 0x25, 0x26, 0x27, 0x28,
    0x29, 0x2a, 0x34, 0x35, 0x36, 0x37, 0x38, 0x39,
    0x3a, 0x43, 0x44, 0x45, 0x46, 0x47, 0x48, 0x49,
    0x4a, 0x53, 0x54, 0x55, 0x56, 0x57, 0x58, 0x59,
    0x5a, 0x63, 0x64, 0x65, 0x66, 0x67, 0x68, 0x69,
    0x6a, 0x73, 0x74, 0x75, 0x76, 0x77, 0x78, 0x79,
    0x7a, 0x83, 0x84, 0x85, 0x86, 0x87, 0x88, 0x89,
    0x8a, 0x92, 0x93, 0x94, 0x95, 0x96, 0x97, 0x98,
    0x99, 0x9a, 0xa2, 0xa3, 0xa4, 0xa5, 0xa6, 0xa7,
    0xa8, 0xa9, 0xaa, 0xb2, 0xb3, 0xb4, 0xb5, 0xb6,
    0xb7, 0xb8, 0xb9, 0xba, 0xc2, 0xc3, 0xc4, 0xc5,
    0xc6, 0xc7, 0xc8, 0xc9, 0xca, 0xd2, 0xd3, 0xd4,
    0xd5, 0xd6, 0xd7, 0xd8, 0xd9, 0xda, 0xe1, 0xe2,
    0xe3, 0xe4, 0xe5, 0xe6, 0xe7, 0xe8, 0xe9, 0xea,
    0xf1, 0xf2, 0xf3, 0xf4, 0xf5, 0xf6, 0xf7, 0xf8,
    0xf9, 0xfa },
    FALSE
  };
  static const JHUFF_TBL acchrome = {
    { /* 0-base */ 0, 0, 2, 1, 2, 4, 4, 3, 4, 7, 5, 4, 4, 0, 1, 2, 0x77 },
    { 0x00, 0x01, 0x02, 0x03, 0x11, 0x04, 0x05, 0x21,
    0x31, 0x06, 0x12, 0x41, 0x51, 0x07, 0x61, 0x71,
    0x13, 0x22, 0x32, 0x81, 0x08, 0x14, 0x42, 0x91,
    0xa1, 0xb1, 0xc1, 0x09, 0x23, 0x33, 0x52, 0xf0,
    0x15, 0x62, 0x72, 0xd1, 0x0a, 0x16, 0x24, 0x34,
    0xe1, 0x25, 0xf1, 0x17, 0x18, 0x19, 0x1a, 0x26,
    0x27, 0x28, 0x29, 0x2a, 0x35, 0x36, 0x37, 0x38,
    0x39, 0x3a, 0x43, 0x44, 0x45, 0x46, 0x47, 0x48,
    0x49, 0x4a, 0x53, 0x54, 0x55, 0x56, 0x57, 0x58,
    0x59, 0x5a, 0x63, 0x64, 0x65, 0x66, 0x67, 0x68,
    0x69, 0x6a, 0x73, 0x74, 0x75, 0x76, 0x77, 0x78,
    0x79, 0x7a, 0x82, 0x83, 0x84, 0x85, 0x86, 0x87,
    0x88, 0x89, 0x8a, 0x92, 0x93, 0x94, 0x95, 0x96,
    0x97, 0x98, 0x99, 0x9a, 0xa2, 0xa3, 0xa4, 0xa5,
    0xa6, 0xa7, 0xa8, 0xa9, 0xaa, 0xb2, 0xb3, 0xb4,
    0xb5, 0xb6, 0xb7, 0xb8, 0xb9, 0xba, 0xc2, 0xc3,
    0xc4, 0xc5, 0xc6, 0xc7, 0xc8, 0xc9, 0xca, 0xd2,
    0xd3, 0xd4, 0xd5, 0xd6, 0xd7, 0xd8, 0xd9, 0xda,
    0xe2, 0xe3, 0xe4, 0xe5, 0xe6, 0xe7, 0xe8, 0xe9,
    0xea, 0xf2, 0xf3, 0xf4, 0xf5, 0xf6, 0xf7, 0xf8,
    0xf9, 0xfa },
    FALSE
  };
  
  cinfo->dc_huff_tbl_ptrs[0] = (JHUFF_TBL*)&dclumin;
  cinfo->dc_huff_tbl_ptrs[1] = (JHUFF_TBL*)&dcchrome;
  cinfo->ac_huff_tbl_ptrs[0] = (JHUFF_TBL*)&aclumin;
  cinfo->ac_huff_tbl_ptrs[1] = (JHUFF_TBL*)&acchrome;
  
}

}
