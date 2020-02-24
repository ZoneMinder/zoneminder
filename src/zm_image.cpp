//
// ZoneMinder Image Class Implementation, $Date$, $Revision$
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
// Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
//
#include "zm.h"
#include "zm_font.h"
#include "zm_bigfont.h"
#include "zm_image.h"
#include "zm_utils.h"
#include "zm_rgb.h"
#include "zm_ffmpeg.h"

#include <fcntl.h>
#include <sys/stat.h>
#include <errno.h>

static unsigned char y_table_global[] = {0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 2, 3, 4, 5, 6, 8, 9, 10, 11, 12, 13, 15, 16, 17, 18, 19, 20, 22, 23, 24, 25, 26, 27, 29, 30, 31, 32, 33, 34, 36, 37, 38, 39, 40, 41, 43, 44, 45, 46, 47, 48, 50, 51, 52, 53, 54, 55, 57, 58, 59, 60, 61, 62, 64, 65, 66, 67, 68, 69, 71, 72, 73, 74, 75, 76, 78, 79, 80, 81, 82, 83, 85, 86, 87, 88, 89, 90, 91, 93, 94, 95, 96, 97, 98, 100, 101, 102, 103, 104, 105, 107, 108, 109, 110, 111, 112, 114, 115, 116, 117, 118, 119, 121, 122, 123, 124, 125, 126, 128, 129, 130, 131, 132, 133, 135, 136, 137, 138, 139, 140, 142, 143, 144, 145, 146, 147, 149, 150, 151, 152, 153, 154, 156, 157, 158, 159, 160, 161, 163, 164, 165, 166, 167, 168, 170, 171, 172, 173, 174, 175, 176, 178, 179, 180, 181, 182, 183, 185, 186, 187, 188, 189, 190, 192, 193, 194, 195, 196, 197, 199, 200, 201, 202, 203, 204, 206, 207, 208, 209, 210, 211, 213, 214, 215, 216, 217, 218, 220, 221, 222, 223, 224, 225, 227, 228, 229, 230, 231, 232, 234, 235, 236, 237, 238, 239, 241, 242, 243, 244, 245, 246, 248, 249, 250, 251, 252, 253, 255, 255, 255, 255, 255, 255, 255, 255, 255, 255, 255, 255, 255, 255, 255, 255, 255, 255, 255, 255, 255};

static signed char uv_table_global[] = {-127, -127, -127, -127, -127, -127, -127, -127, -127, -127, -127, -127, -127, -127, -127, -127, -127, -125, -124, -123, -122, -121, -120, -119, -117, -116, -115, -114, -113, -112, -111, -109, -108, -107, -106, -105, -104, -103, -102, -100, -99, -98, -97, -96, -95, -94, -92, -91, -90, -89, -88, -87, -86, -85, -83, -82, -81, -80, -79, -78, -77, -75, -74, -73, -72, -71, -70, -69, -68, -66, -65, -64, -63, -62, -61, -60, -58, -57, -56, -55, -54, -53, -52, -51, -49, -48, -47, -46, -45, -44, -43, -41, -40, -39, -38, -37, -36, -35, -34, -32, -31, -30, -29, -28, -27, -26, -24, -23, -22, -21, -20, -19, -18, -17, -15, -14, -13, -12, -11, -10, -9, -7, -6, -5, -4, -3, -2, -1, 0, 1, 2, 3, 4, 5, 6, 7, 9, 10, 11, 12, 13, 14, 15, 17, 18, 19, 20, 21, 22, 23, 24, 26, 27, 28, 29, 30, 31, 32, 34, 35, 36, 37, 38, 39, 40, 41, 43, 44, 45, 46, 47, 48, 49, 51, 52, 53, 54, 55, 56, 57, 58, 60, 61, 62, 63, 64, 65, 66, 68, 69, 70, 71, 72, 73, 74, 75, 77, 78, 79, 80, 81, 82, 83, 85, 86, 87, 88, 89, 90, 91, 92, 94, 95, 96, 97, 98, 99, 100, 102, 103, 104, 105, 106, 107, 108, 109, 111, 112, 113, 114, 115, 116, 117, 119, 120, 121, 122, 123, 124, 125, 127, 127, 127, 127, 127, 127, 127, 127, 127, 127, 127, 127, 127, 127, 127, 127};

static short r_v_table_global[] = {-179, -178, -176, -175, -173, -172, -171, -169, -168, -166, -165, -164, -162, -161, -159, -158, -157, -155, -154, -152, -151, -150, -148, -147, -145, -144, -143, -141, -140, -138, -137, -135, -134, -133, -131, -130, -128, -127, -126, -124, -123, -121, -120, -119, -117, -116, -114, -113, -112, -110, -109, -107, -106, -105, -103, -102, -100, -99, -98, -96, -95, -93, -92, -91, -89, -88, -86, -85, -84, -82, -81, -79, -78, -77, -75, -74, -72, -71, -70, -68, -67, -65, -64, -63, -61, -60, -58, -57, -56, -54, -53, -51, -50, -49, -47, -46, -44, -43, -42, -40, -39, -37, -36, -35, -33, -32, -30, -29, -28, -26, -25, -23, -22, -21, -19, -18, -16, -15, -14, -12, -11, -9, -8, -7, -5, -4, -2, -1, 0, 1, 2, 4, 5, 7, 8, 9, 11, 12, 14, 15, 16, 18, 19, 21, 22, 23, 25, 26, 28, 29, 30, 32, 33, 35, 36, 37, 39, 40, 42, 43, 44, 46, 47, 49, 50, 51, 53, 54, 56, 57, 58, 60, 61, 63, 64, 65, 67, 68, 70, 71, 72, 74, 75, 77, 78, 79, 81, 82, 84, 85, 86, 88, 89, 91, 92, 93, 95, 96, 98, 99, 100, 102, 103, 105, 106, 107, 109, 110, 112, 113, 114, 116, 117, 119, 120, 121, 123, 124, 126, 127, 128, 130, 131, 133, 134, 135, 137, 138, 140, 141, 143, 144, 145, 147, 148, 150, 151, 152, 154, 155, 157, 158, 159, 161, 162, 164, 165, 166, 168, 169, 171, 172, 173, 175, 176};

static short g_u_table_global[] = {-44, -43, -43, -43, -42, -42, -41, -41, -41, -40, -40, -40, -39, -39, -39, -38, -38, -38, -37, -37, -37, -36, -36, -36, -35, -35, -35, -34, -34, -34, -33, -33, -33, -32, -32, -31, -31, -31, -30, -30, -30, -29, -29, -29, -28, -28, -28, -27, -27, -27, -26, -26, -26, -25, -25, -25, -24, -24, -24, -23, -23, -23, -22, -22, -22, -21, -21, -20, -20, -20, -19, -19, -19, -18, -18, -18, -17, -17, -17, -16, -16, -16, -15, -15, -15, -14, -14, -14, -13, -13, -13, -12, -12, -12, -11, -11, -11, -10, -10, -9, -9, -9, -8, -8, -8, -7, -7, -7, -6, -6, -6, -5, -5, -5, -4, -4, -4, -3, -3, -3, -2, -2, -2, -1, -1, -1, 0, 0, 0, 0, 0, 1, 1, 1, 2, 2, 2, 3, 3, 3, 4, 4, 4, 5, 5, 5, 6, 6, 6, 7, 7, 7, 8, 8, 8, 9, 9, 9, 10, 10, 11, 11, 11, 12, 12, 12, 13, 13, 13, 14, 14, 14, 15, 15, 15, 16, 16, 16, 17, 17, 17, 18, 18, 18, 19, 19, 19, 20, 20, 20, 21, 21, 22, 22, 22, 23, 23, 23, 24, 24, 24, 25, 25, 25, 26, 26, 26, 27, 27, 27, 28, 28, 28, 29, 29, 29, 30, 30, 30, 31, 31, 31, 32, 32, 33, 33, 33, 34, 34, 34, 35, 35, 35, 36, 36, 36, 37, 37, 37, 38, 38, 38, 39, 39, 39, 40, 40, 40, 41, 41, 41, 42, 42, 43, 43};

static short g_v_table_global[] = {-91, -90, -89, -89, -88, -87, -87, -86, -85, -84, -84, -83, -82, -82, -81, -80, -79, -79, -78, -77, -77, -76, -75, -74, -74, -73, -72, -72, -71, -70, -69, -69, -68, -67, -67, -66, -65, -64, -64, -63, -62, -62, -61, -60, -59, -59, -58, -57, -57, -56, -55, -54, -54, -53, -52, -52, -51, -50, -49, -49, -48, -47, -47, -46, -45, -44, -44, -43, -42, -42, -41, -40, -39, -39, -38, -37, -37, -36, -35, -34, -34, -33, -32, -32, -31, -30, -29, -29, -28, -27, -27, -26, -25, -24, -24, -23, -22, -22, -21, -20, -19, -19, -18, -17, -17, -16, -15, -14, -14, -13, -12, -12, -11, -10, -9, -9, -8, -7, -7, -6, -5, -4, -4, -3, -2, -2, -1, 0, 0, 0, 1, 2, 2, 3, 4, 4, 5, 6, 7, 7, 8, 9, 9, 10, 11, 12, 12, 13, 14, 14, 15, 16, 17, 17, 18, 19, 19, 20, 21, 22, 22, 23, 24, 24, 25, 26, 27, 27, 28, 29, 29, 30, 31, 32, 32, 33, 34, 34, 35, 36, 37, 37, 38, 39, 39, 40, 41, 42, 42, 43, 44, 44, 45, 46, 47, 47, 48, 49, 49, 50, 51, 52, 52, 53, 54, 54, 55, 56, 57, 57, 58, 59, 59, 60, 61, 62, 62, 63, 64, 64, 65, 66, 67, 67, 68, 69, 69, 70, 71, 72, 72, 73, 74, 74, 75, 76, 77, 77, 78, 79, 79, 80, 81, 82, 82, 83, 84, 84, 85, 86, 87, 87, 88, 89, 89};

static short b_u_table_global[] = {-226, -225, -223, -221, -219, -217, -216, -214, -212, -210, -209, -207, -205, -203, -202, -200, -198, -196, -194, -193, -191, -189, -187, -186, -184, -182, -180, -178, -177, -175, -173, -171, -170, -168, -166, -164, -163, -161, -159, -157, -155, -154, -152, -150, -148, -147, -145, -143, -141, -139, -138, -136, -134, -132, -131, -129, -127, -125, -124, -122, -120, -118, -116, -115, -113, -111, -109, -108, -106, -104, -102, -101, -99, -97, -95, -93, -92, -90, -88, -86, -85, -83, -81, -79, -77, -76, -74, -72, -70, -69, -67, -65, -63, -62, -60, -58, -56, -54, -53, -51, -49, -47, -46, -44, -42, -40, -38, -37, -35, -33, -31, -30, -28, -26, -24, -23, -21, -19, -17, -15, -14, -12, -10, -8, -7, -5, -3, -1, 0, 1, 3, 5, 7, 8, 10, 12, 14, 15, 17, 19, 21, 23, 24, 26, 28, 30, 31, 33, 35, 37, 38, 40, 42, 44, 46, 47, 49, 51, 53, 54, 56, 58, 60, 62, 63, 65, 67, 69, 70, 72, 74, 76, 77, 79, 81, 83, 85, 86, 88, 90, 92, 93, 95, 97, 99, 101, 102, 104, 106, 108, 109, 111, 113, 115, 116, 118, 120, 122, 124, 125, 127, 129, 131, 132, 134, 136, 138, 139, 141, 143, 145, 147, 148, 150, 152, 154, 155, 157, 159, 161, 163, 164, 166, 168, 170, 171, 173, 175, 177, 178, 180, 182, 184, 186, 187, 189, 191, 193, 194, 196, 198, 200, 202, 203, 205, 207, 209, 210, 212, 214, 216, 217, 219, 221, 223};

bool Image::initialised = false;
static unsigned char *y_table;
static signed char *uv_table;
static short *r_v_table;
static short *g_v_table;
static short *g_u_table;
static short *b_u_table;

jpeg_compress_struct *Image::writejpg_ccinfo[101] = { 0 };
jpeg_compress_struct *Image::encodejpg_ccinfo[101] = { 0 };
jpeg_decompress_struct *Image::readjpg_dcinfo = 0;
jpeg_decompress_struct *Image::decodejpg_dcinfo = 0;
struct zm_error_mgr Image::jpg_err;

/* Pointer to blend function. */
static blend_fptr_t fptr_blend;

/* Pointer to delta8 functions */
static delta_fptr_t fptr_delta8_rgb;
static delta_fptr_t fptr_delta8_bgr;
static delta_fptr_t fptr_delta8_rgba;
static delta_fptr_t fptr_delta8_bgra;
static delta_fptr_t fptr_delta8_argb;
static delta_fptr_t fptr_delta8_abgr;
static delta_fptr_t fptr_delta8_gray8;

/* Pointers to deinterlace_4field functions */
static deinterlace_4field_fptr_t fptr_deinterlace_4field_rgba;
static deinterlace_4field_fptr_t fptr_deinterlace_4field_bgra;
static deinterlace_4field_fptr_t fptr_deinterlace_4field_argb;
static deinterlace_4field_fptr_t fptr_deinterlace_4field_abgr;
static deinterlace_4field_fptr_t fptr_deinterlace_4field_gray8;

/* Pointer to image buffer memory copy function */
imgbufcpy_fptr_t fptr_imgbufcpy;

void Image::update_function_pointers() {
  /* Because many loops are unrolled and work on 16 colours/time or 4 pixels/time, we have to meet requirements */
  if ( pixels % 16 || pixels % 12 ) {
    // have to use non-loop unrolled functions
    delta8_rgb = &std_delta8_rgb;
    delta8_bgr = &std_delta8_bgr;
    delta8_rgba = &std_delta8_rgba;
    delta8_bgra = &std_delta8_bgra;
    delta8_argb = &std_delta8_argb;
    delta8_abgr = &std_delta8_abgr;
    delta8_gray8 = &std_delta8_gray8;
    blend = &std_blend;
  } else {
    // Use either sse or neon, or loop unrolled version
    delta8_rgb = fptr_delta8_rgb;
    delta8_bgr = fptr_delta8_bgr;
    delta8_rgba = fptr_delta8_rgba;
    delta8_bgra = fptr_delta8_bgra;
    delta8_argb = fptr_delta8_argb;
    delta8_abgr = fptr_delta8_abgr;
    delta8_gray8 = fptr_delta8_gray8;
    blend = fptr_blend;
  }
}

// This constructor is not used anywhere
Image::Image() {
  if ( !initialised )
    Initialise();
  width = 0;
  height = 0;
  pixels = 0;
  colours = 0;
  subpixelorder = 0;
  size = 0;
  allocation = 0;
  buffer = 0;
  buffertype = 0;
  holdbuffer = 0;
  text[0] = '\0';
  blend = fptr_blend;
}

Image::Image( const char *filename ) {
  if ( !initialised )
    Initialise();
  width = 0;
  height = 0;
  pixels = 0;
  colours = 0;
  subpixelorder = 0;
  size = 0;
  allocation = 0;
  buffer = 0;
  buffertype = 0;
  holdbuffer = 0;
  ReadJpeg(filename, ZM_COLOUR_RGB24, ZM_SUBPIX_ORDER_RGB);
  text[0] = '\0';
  update_function_pointers();
}

Image::Image( int p_width, int p_height, int p_colours, int p_subpixelorder, uint8_t *p_buffer ) {
  if ( !initialised )
    Initialise();
  width = p_width;
  height = p_height;
  pixels = width*height;
  colours = p_colours;
  subpixelorder = p_subpixelorder;
  size = pixels*colours;
  buffer = 0;
  holdbuffer = 0;
  if ( p_buffer ) {
    allocation = size;
    buffertype = ZM_BUFTYPE_DONTFREE;
    buffer = p_buffer;
  } else {
    AllocImgBuffer(size);
  }
  text[0] = '\0';

  update_function_pointers();
}

Image::Image( const AVFrame *frame ) {
  AVFrame *dest_frame = zm_av_frame_alloc();
  text[0] = '\0';

  width = frame->width;
  height = frame->height;
  pixels = width*height;

  colours = ZM_COLOUR_RGB32;
  subpixelorder = ZM_SUBPIX_ORDER_RGBA;

  size = pixels*colours;
  buffer = 0;
  holdbuffer = 0;
  AllocImgBuffer(size);

#if LIBAVUTIL_VERSION_CHECK(54, 6, 0, 6, 0)
  av_image_fill_arrays(dest_frame->data, dest_frame->linesize,
      buffer, AV_PIX_FMT_RGBA, width, height, 1);
#else
  avpicture_fill( (AVPicture *)dest_frame, buffer,
      AV_PIX_FMT_RGBA, width, height);
#endif

#if HAVE_LIBSWSCALE
  struct SwsContext   *mConvertContext = sws_getContext(
      width,
      height,
      (AVPixelFormat)frame->format,
      width, height,
      AV_PIX_FMT_RGBA, SWS_BICUBIC, NULL,
      NULL, NULL);
  if ( mConvertContext == NULL )
    Fatal( "Unable to create conversion context" );

  if ( sws_scale(mConvertContext, frame->data, frame->linesize, 0, frame->height, dest_frame->data, dest_frame->linesize) < 0 )
    Fatal("Unable to convert raw format %u to target format %u", frame->format, AV_PIX_FMT_RGBA);
#else // HAVE_LIBSWSCALE
  Fatal("You must compile ffmpeg with the --enable-swscale option to use ffmpeg cameras");
#endif // HAVE_LIBSWSCALE
  av_frame_free( &dest_frame );
  update_function_pointers();
}

Image::Image( const Image &p_image ) {
  if ( !initialised )
    Initialise();
  width = p_image.width;
  height = p_image.height;
  pixels = p_image.pixels;
  colours = p_image.colours;
  subpixelorder = p_image.subpixelorder;
  size = p_image.size; // allocation is set in AllocImgBuffer
  buffer = 0;
  holdbuffer = 0;
  AllocImgBuffer(size);
  (*fptr_imgbufcpy)(buffer, p_image.buffer, size);
  strncpy( text, p_image.text, sizeof(text) );
  update_function_pointers();
}

Image::~Image() {
  DumpImgBuffer();
}

/* Should be called as part of program shutdown to free everything */
void Image::Deinitialise() {
  if ( initialised ) {
    /*
       delete[] y_table;
       delete[] uv_table;
       delete[] r_v_table;
       delete[] g_v_table;
       delete[] g_u_table;
       delete[] b_u_table;
     */
    initialised = false;
    if ( readjpg_dcinfo ) {
      jpeg_destroy_decompress( readjpg_dcinfo );
      delete readjpg_dcinfo;
      readjpg_dcinfo = 0;
    }
    if ( decodejpg_dcinfo ) {
      jpeg_destroy_decompress( decodejpg_dcinfo );
      delete decodejpg_dcinfo;
      decodejpg_dcinfo = 0;
    }
    for ( unsigned int quality=0; quality <= 100; quality += 1 ) {
      if ( writejpg_ccinfo[quality] ) {
        jpeg_destroy_compress( writejpg_ccinfo[quality] );
        delete writejpg_ccinfo[quality];
        writejpg_ccinfo[quality] = NULL;
      }
    } // end foreach quality
  }
}

void Image::Initialise() {
  /* Assign the blend pointer to function */
  if ( config.fast_image_blends ) {
    if ( config.cpu_extensions && sseversion >= 20 ) {
      fptr_blend = &sse2_fastblend; /* SSE2 fast blend */
      Debug(4,"Blend: Using SSE2 fast blend function");
    } else if ( config.cpu_extensions && neonversion >= 1 ) {
#if defined(__aarch64__)
      fptr_blend = &neon64_armv8_fastblend;  /* ARM Neon (AArch64) fast blend */
      Debug(4,"Blend: Using ARM Neon (AArch64) fast blend function");
#elif defined(__arm__)
      fptr_blend = &neon32_armv7_fastblend;  /* ARM Neon (AArch32) fast blend */
      Debug(4,"Blend: Using ARM Neon (AArch32) fast blend function");
#else
      Panic("Bug: Non ARM platform but neon present");
#endif
    } else {
      fptr_blend = &std_fastblend;  /* standard fast blend */
      Debug(4,"Blend: Using fast blend function");
    }
  } else {
    fptr_blend = &std_blend;
    Debug(4,"Blend: Using standard blend function");
  }

  __attribute__((aligned(64))) uint8_t blend1[128] = {
    86,58,54,63,149,62,209,34,148,46,186,176,9,236,193,254,113,146,228,220,123,164,92,98,9,72,67,156,63,118,96,167,
    48,224,106,176,201,245,223,219,198,50,100,31,68,77,33,76,166,90,254,128,191,82,84,32,3,171,147,248,14,196,141,179,
    79,237,121,11,132,37,194,225,45,171,169,167,56,64,193,85,147,33,97,221,94,97,90,44,191,248,65,8,17,240,167,207,
    224,23,71,74,81,1,46,110,227,94,163,170,55,155,52,147,224,154,237,35,255,26,229,11,223,242,118,155,82,37,189,2
  };
  __attribute__((aligned(64))) uint8_t blend2[128] = {
    92,188,203,118,121,231,252,218,126,88,80,72,123,16,91,131,109,0,57,56,95,204,74,8,137,94,6,69,18,146,229,194,
    146,230,13,146,95,48,185,65,162,47,152,172,184,111,245,143,247,105,49,42,89,37,145,255,221,200,103,80,98,39,14,227,
    227,46,46,59,248,7,83,20,157,79,36,161,237,55,77,175,232,200,38,170,198,239,89,19,82,88,130,120,203,184,141,117,
    228,140,150,107,103,195,74,130,42,11,150,70,176,204,198,188,38,252,174,104,128,106,31,17,141,231,62,104,179,29,143,130
  };
  __attribute__((aligned(64))) uint8_t blendexp[128] = {
    86,73,71,69,145,82,214,56,145,51,173,163,22,209,180,239,112,128,207,200,119,168,89,87,24,74,59,145,57,121,111,170,
    59,224,94,172,188,221,218,200,193,49,106,47,81,81,58,84,175,91,229,117,178,76,91,58,29,174,141,227,24,177,125,184,
    96,214,112,16,145,33,180,200,58,159,153,166,77,62,179,95,157,53,89,214,106,114,89,41,177,228,72,21,39,233,163,196,
    224,37,80,77,83,24,49,112,204,84,161,158,69,160,69,151,201,165,229,43,239,35,205,11,213,240,111,148,93,36,183,17
  };
  __attribute__((aligned(64))) uint8_t blendres[128];

  /* Run the blend function */
  (*fptr_blend)(blend1,blend2,blendres,128,12.0);

  /* Compare results with expected results */
  for ( int i=0; i < 128; i ++ ) {
    if ( abs(blendexp[i] - blendres[i]) > 3 ) {
      Panic("Blend function failed self-test: Results differ from the expected results. Column %u Expected %u Got %u",i,blendexp[i],blendres[i]);
    }
  }

  fptr_delta8_rgb = &std_delta8_rgb;
  fptr_delta8_bgr = &std_delta8_bgr;

  /* Assign the delta functions */
  if ( config.cpu_extensions ) {
    if ( sseversion >= 35 ) {
      /* SSSE3 available */
      fptr_delta8_rgba = &ssse3_delta8_rgba;
      fptr_delta8_bgra = &ssse3_delta8_bgra;
      fptr_delta8_argb = &ssse3_delta8_argb;
      fptr_delta8_abgr = &ssse3_delta8_abgr;
      fptr_delta8_gray8 = &sse2_delta8_gray8;
      Debug(4,"Delta: Using SSSE3 delta functions");
    } else if ( sseversion >= 20 ) {
      /* SSE2 available */
      fptr_delta8_rgba = &sse2_delta8_rgba;
      fptr_delta8_bgra = &sse2_delta8_bgra;
      fptr_delta8_argb = &sse2_delta8_argb;
      fptr_delta8_abgr = &sse2_delta8_abgr;
      fptr_delta8_gray8 = &sse2_delta8_gray8;
      Debug(4,"Delta: Using SSE2 delta functions");
    } else if ( neonversion >= 1 ) {
      /* ARM Neon available */
#if defined(__aarch64__)
      fptr_delta8_rgba = &neon64_armv8_delta8_rgba;
      fptr_delta8_bgra = &neon64_armv8_delta8_bgra;
      fptr_delta8_argb = &neon64_armv8_delta8_argb;
      fptr_delta8_abgr = &neon64_armv8_delta8_abgr;
      fptr_delta8_gray8 = &neon64_armv8_delta8_gray8;
      Debug(4,"Delta: Using ARM Neon (AArch64) delta functions");
#elif defined(__arm__)
      fptr_delta8_rgba = &neon32_armv7_delta8_rgba;
      fptr_delta8_bgra = &neon32_armv7_delta8_bgra;
      fptr_delta8_argb = &neon32_armv7_delta8_argb;
      fptr_delta8_abgr = &neon32_armv7_delta8_abgr;
      fptr_delta8_gray8 = &neon32_armv7_delta8_gray8;
      Debug(4,"Delta: Using ARM Neon (AArch32) delta functions");
#else
      Panic("Bug: Non ARM platform but neon present");
#endif
    } else {
      /* No suitable SSE version available */
      fptr_delta8_rgba = &fast_delta8_rgba;
      fptr_delta8_bgra = &fast_delta8_bgra;
      fptr_delta8_argb = &fast_delta8_argb;
      fptr_delta8_abgr = &fast_delta8_abgr;
      fptr_delta8_gray8 = &fast_delta8_gray8;
      Debug(4,"Delta: Using standard delta functions");
    }
  } else {
    /* CPU extensions disabled */
    fptr_delta8_rgba = &fast_delta8_rgba;
    fptr_delta8_bgra = &fast_delta8_bgra;
    fptr_delta8_argb = &fast_delta8_argb;
    fptr_delta8_abgr = &fast_delta8_abgr;
    fptr_delta8_gray8 = &fast_delta8_gray8;
    Debug(4,"Delta: CPU extensions disabled, using standard delta functions");
  }

  __attribute__((aligned(64))) uint8_t delta8_1[128] = {
    221,22,234,254,8,140,15,28,166,13,203,56,92,250,79,225,19,59,241,145,253,33,87,204,97,168,229,180,3,108,205,177,
    41,108,65,149,4,87,16,240,56,50,135,64,153,3,219,214,239,55,169,180,167,45,243,56,191,119,145,250,102,145,73,32,
    207,213,189,167,147,83,217,30,113,51,142,125,219,97,60,5,135,195,95,133,21,197,150,82,134,93,198,97,97,49,117,24,
    242,253,242,5,190,71,182,1,0,69,25,181,139,84,242,79,150,158,29,215,98,100,245,16,86,165,18,98,46,100,139,19
  };
  __attribute__((aligned(64))) uint8_t delta8_2[128] = {
    236,22,153,161,50,141,15,130,89,251,33,5,140,201,225,194,138,76,248,89,25,26,29,93,250,251,48,157,41,126,140,152,
    170,177,134,14,234,99,3,105,217,76,38,233,89,30,93,48,234,40,202,80,184,4,250,71,183,249,76,78,184,148,185,120,
    137,214,238,57,50,93,29,60,99,207,40,15,43,28,177,118,60,231,90,47,198,251,250,241,212,114,249,17,95,161,216,218,
    51,178,137,161,213,108,35,72,65,24,5,176,110,15,0,2,137,58,0,133,197,1,122,169,175,33,223,138,37,114,52,186
  };
  __attribute__((aligned(64))) uint8_t delta8_gray8_exp[128] = {
    15,0,81,93,42,1,0,102,77,238,170,51,48,49,146,31,119,17,7,56,228,7,58,111,153,83,181,23,38,18,65,25,
    129,69,69,135,230,12,13,135,161,26,97,169,64,27,126,166,5,15,33,100,17,41,7,15,8,130,69,172,82,3,112,88,
    70,1,49,110,97,10,188,30,14,156,102,110,176,69,117,113,75,36,5,86,177,54,100,159,78,21,51,80,2,112,99,194,
    191,75,105,156,23,37,147,71,65,45,20,5,29,69,242,77,13,100,29,82,99,99,123,153,89,132,205,40,9,14,87,167
  };
  __attribute__((aligned(64))) uint8_t delta8_rgba_exp[32] = {
    13,11,189,60,41,68,112,28,84,66,68,48,14,30,91,36,24,54,113,101,41,90,39,82,107,47,46,80,69,102,130,21
  };
  __attribute__((aligned(64))) uint8_t delta8_gray8_res[128];
  __attribute__((aligned(64))) uint8_t delta8_rgba_res[32];

  /* Run the delta8 grayscale function */
  (*fptr_delta8_gray8)(delta8_1,delta8_2,delta8_gray8_res,128);

  /* Compare results with expected results */
  for ( int i=0; i < 128; i++ ) {
    if ( abs(delta8_gray8_exp[i] - delta8_gray8_res[i]) > 7 ) {
      Panic("Delta grayscale function failed self-test: Results differ from the expected results. Column %u Expected %u Got %u",i,delta8_gray8_exp[i],delta8_gray8_res[i]);
    }
  }

  /* Run the delta8 RGBA function */
  (*fptr_delta8_rgba)(delta8_1,delta8_2,delta8_rgba_res,32);

  /* Compare results with expected results */
  for ( int i=0; i < 32; i++ ) {
    if ( abs(delta8_rgba_exp[i] - delta8_rgba_res[i]) > 7 ) {
      Panic("Delta RGBA function failed self-test: Results differ from the expected results. Column %u Expected %u Got %u",i,delta8_rgba_exp[i],delta8_rgba_res[i]);
    }
  }

  /*
     SSSE3 deinterlacing functions were removed because they were usually equal
     or slower than the standard code (compiled with -O2 or better)
     The function is too complicated to be vectorized efficiently on SSSE3
  */
  fptr_deinterlace_4field_rgba = &std_deinterlace_4field_rgba;
  fptr_deinterlace_4field_bgra = &std_deinterlace_4field_bgra;
  fptr_deinterlace_4field_argb = &std_deinterlace_4field_argb;
  fptr_deinterlace_4field_abgr = &std_deinterlace_4field_abgr;
  fptr_deinterlace_4field_gray8 = &std_deinterlace_4field_gray8;
  Debug(4,"Deinterlace: Using standard functions");

#if defined(__i386__) && !defined(__x86_64__)
  /* Use SSE2 aligned memory copy? */
  if ( config.cpu_extensions && sseversion >= 20 ) {
    fptr_imgbufcpy = &sse2_aligned_memcpy;
    Debug(4,"Image buffer copy: Using SSE2 aligned memcpy");
  } else {
    fptr_imgbufcpy = &memcpy;
    Debug(4,"Image buffer copy: Using standard memcpy");
  }
#else
  fptr_imgbufcpy = &memcpy;
  Debug(4,"Image buffer copy: Using standard memcpy");
#endif

  /* Code below relocated from zm_local_camera */
  Debug( 3, "Setting up static colour tables" );

  y_table = y_table_global;
  uv_table = uv_table_global;
  r_v_table = r_v_table_global;
  g_v_table = g_v_table_global;
  g_u_table = g_u_table_global;
  b_u_table = b_u_table_global;
  /*
     y_table = new unsigned char[256];
     for ( int i = 0; i <= 255; i++ )
     {
     unsigned char c = i;
     if ( c <= 16 )
     y_table[c] = 0;
     else if ( c >= 235 )
     y_table[c] = 255;
     else
     y_table[c] = (255*(c-16))/219;
     }

     uv_table = new signed char[256];
     for ( int i = 0; i <= 255; i++ )
     {
     unsigned char c = i;
     if ( c <= 16 )
     uv_table[c] = -127;
     else if ( c >= 240 )
     uv_table[c] = 127;
     else
     uv_table[c] = (127*(c-128))/112;
     }

     r_v_table = new short[255];
     g_v_table = new short[255];
     g_u_table = new short[255];
     b_u_table = new short[255];
     for ( int i = 0; i < 255; i++ )
     {
     r_v_table[i] = (1402*(i-128))/1000;
     g_u_table[i] = (344*(i-128))/1000;
     g_v_table[i] = (714*(i-128))/1000;
     b_u_table[i] = (1772*(i-128))/1000;
     }
   */

  initialised = true;
}

/* Requests a writeable buffer to the image. This is safer than buffer() because this way we can guarantee that a buffer of required size exists */
uint8_t* Image::WriteBuffer(const unsigned int p_width, const unsigned int p_height, const unsigned int p_colours, const unsigned int p_subpixelorder) {

  if ( p_colours != ZM_COLOUR_GRAY8 && p_colours != ZM_COLOUR_RGB24 && p_colours != ZM_COLOUR_RGB32 ) {
    Error("WriteBuffer called with unexpected colours: %d",p_colours);
    return NULL;
  }

  if ( ! ( p_height > 0 && p_width > 0 ) ) {
    Error("WriteBuffer called with invalid width or height: %d %d", p_width, p_height);
    return NULL;
  }

  if ( p_width != width || p_height != height || p_colours != colours || p_subpixelorder != subpixelorder ) {
    unsigned int newsize = (p_width * p_height) * p_colours;

    if ( buffer == NULL ) {
      AllocImgBuffer(newsize);
    } else {
      if ( allocation < newsize ) {
        if ( holdbuffer ) {
          Error("Held buffer is undersized for requested buffer");
          return NULL;
        } else {
          /* Replace buffer with a bigger one */
          //DumpImgBuffer(); // Done in AllocImgBuffer too
          AllocImgBuffer(newsize);
        }
      }
    }

    width = p_width;
    height = p_height;
    colours = p_colours;
    subpixelorder = p_subpixelorder;
    pixels = height*width;
    size = newsize;
  } // end if need to re-alloc buffer

  return buffer;
}

/* Assign an existing buffer to the image instead of copying from a source buffer. The goal is to reduce the amount of memory copying and increase efficiency and buffer reusing. */
void Image::AssignDirect( const unsigned int p_width, const unsigned int p_height, const unsigned int p_colours, const unsigned int p_subpixelorder, uint8_t *new_buffer, const size_t buffer_size, const int p_buffertype) {
  if ( new_buffer == NULL ) {
    Error("Attempt to directly assign buffer from a NULL pointer");
    return;
  }

  if ( !p_height || !p_width ) {
    Error("Attempt to directly assign buffer with invalid width or height: %d %d",p_width,p_height);
    return;
  }

  if ( p_colours != ZM_COLOUR_GRAY8 && p_colours != ZM_COLOUR_RGB24 && p_colours != ZM_COLOUR_RGB32 ) {
    Error("Attempt to directly assign buffer with unexpected colours per pixel: %d",p_colours);
    return;
  }

  unsigned int new_buffer_size = ((p_width*p_height)*p_colours);

  if ( buffer_size < new_buffer_size ) {
    Error("Attempt to directly assign buffer from an undersized buffer of size: %zu, needed %dx%d*%d colours = %zu",buffer_size, p_width, p_height, p_colours, new_buffer_size );
    return;
  }

  if ( holdbuffer && buffer ) {
    if ( new_buffer_size > allocation ) {
      Error("Held buffer is undersized for assigned buffer");
      return;
    } else {
      width = p_width;
      height = p_height;
      colours = p_colours;
      subpixelorder = p_subpixelorder;
      pixels = height*width;
      size = new_buffer_size; // was pixels*colours, but we already calculated it above as new_buffer_size

      /* Copy into the held buffer */
      if ( new_buffer != buffer )
        (*fptr_imgbufcpy)(buffer, new_buffer, size);

      /* Free the new buffer */
      DumpBuffer(new_buffer, p_buffertype);
    }
  } else {
    /* Free an existing buffer if any */
    DumpImgBuffer();

    width = p_width;
    height = p_height;
    colours = p_colours;
    subpixelorder = p_subpixelorder;
    pixels = height*width;
    size = new_buffer_size; // was pixels*colours, but we already calculated it above as new_buffer_size

    allocation = buffer_size;
    buffertype = p_buffertype;
    buffer = new_buffer;
  }

}

void Image::Assign(const unsigned int p_width, const unsigned int p_height, const unsigned int p_colours, const unsigned int p_subpixelorder, const uint8_t* new_buffer, const size_t buffer_size) {
  unsigned int new_size = (p_width * p_height) * p_colours;

  if ( new_buffer == NULL ) {
    Error("Attempt to assign buffer from a NULL pointer");
    return;
  }

  if ( buffer_size < new_size ) {
    Error("Attempt to assign buffer from an undersized buffer of size: %zu",buffer_size);
    return;
  }

  if ( !p_height || !p_width ) {
    Error("Attempt to assign buffer with invalid width or height: %d %d",p_width,p_height);
    return;
  }

  if ( p_colours != ZM_COLOUR_GRAY8 && p_colours != ZM_COLOUR_RGB24 && p_colours != ZM_COLOUR_RGB32 ) {
    Error("Attempt to assign buffer with unexpected colours per pixel: %d",p_colours);
    return;
  }

  if ( !buffer || p_width != width || p_height != height || p_colours != colours || p_subpixelorder != subpixelorder ) {

    if ( holdbuffer && buffer ) {
      if ( new_size > allocation ) {
        Error("Held buffer is undersized for assigned buffer");
        return;
      }
    } else {
      if ( new_size > allocation || !buffer ) {
        DumpImgBuffer();
        AllocImgBuffer(new_size);
      }
    }

    width = p_width;
    height = p_height;
    pixels = width*height;
    colours = p_colours;
    subpixelorder = p_subpixelorder;
    size = new_size;
  }

  if(new_buffer != buffer)
    (*fptr_imgbufcpy)(buffer, new_buffer, size);

}

void Image::Assign( const Image &image ) {
  unsigned int new_size = (image.width * image.height) * image.colours;

  if ( image.buffer == NULL ) {
    Error("Attempt to assign image with an empty buffer");
    return;
  }

  if ( image.colours != ZM_COLOUR_GRAY8 && image.colours != ZM_COLOUR_RGB24 && image.colours != ZM_COLOUR_RGB32 ) {
    Error("Attempt to assign image with unexpected colours per pixel: %d",image.colours);
    return;
  }

  if ( !buffer || image.width != width || image.height != height || image.colours != colours || image.subpixelorder != subpixelorder) {

    if (holdbuffer && buffer) {
      if (new_size > allocation) {
        Error("Held buffer is undersized for assigned buffer");
        return;
      }
    } else {
      if(new_size > allocation || !buffer) {
        // DumpImgBuffer(); This is also done in AllocImgBuffer
        AllocImgBuffer(new_size);
      }
    }

    width = image.width;
    height = image.height;
    pixels = width*height;
    colours = image.colours;
    subpixelorder = image.subpixelorder;
    size = new_size;
  }

  if(image.buffer != buffer)
    (*fptr_imgbufcpy)(buffer, image.buffer, size);
}

Image *Image::HighlightEdges( Rgb colour, unsigned int p_colours, unsigned int p_subpixelorder, const Box *limits ) {
  if ( colours != ZM_COLOUR_GRAY8 ) {
    Panic("Attempt to highlight image edges when colours = %d", colours);
  }

  /* Convert the colour's RGBA subpixel order into the image's subpixel order */
  colour = rgb_convert(colour,p_subpixelorder);

  /* Create a new image of the target format */
  Image *high_image = new Image( width, height, p_colours, p_subpixelorder );
  uint8_t* high_buff = high_image->WriteBuffer(width, height, p_colours, p_subpixelorder);

  /* Set image to all black */
  high_image->Clear();

  unsigned int lo_x = limits?limits->Lo().X():0;
  unsigned int lo_y = limits?limits->Lo().Y():0;
  unsigned int hi_x = limits?limits->Hi().X():width-1;
  unsigned int hi_y = limits?limits->Hi().Y():height-1;

  if ( p_colours == ZM_COLOUR_GRAY8 )
  {
    for ( unsigned int y = lo_y; y <= hi_y; y++ )
    {
      const uint8_t* p = buffer + (y * width) + lo_x;
      uint8_t* phigh = high_buff + (y * width) + lo_x;
      for ( unsigned int x = lo_x; x <= hi_x; x++, p++, phigh++ )
      {
        bool edge = false;
        if ( *p )
        {
          if ( !edge && x > 0 && !*(p-1) ) edge = true;
          if ( !edge && x < (width-1) && !*(p+1) ) edge = true;
          if ( !edge && y > 0 && !*(p-width) ) edge = true;
          if ( !edge && y < (height-1) && !*(p+width) ) edge = true;
        }
        if ( edge )
        {
          *phigh = colour;
        }
      }
    }
  }
  else if ( p_colours == ZM_COLOUR_RGB24 )
  {
    for ( unsigned int y = lo_y; y <= hi_y; y++ )
    {
      const uint8_t* p = buffer + (y * width) + lo_x;
      uint8_t* phigh = high_buff + (((y * width) + lo_x) * 3);
      for ( unsigned int x = lo_x; x <= hi_x; x++, p++, phigh += 3 )
      {
        bool edge = false;
        if ( *p )
        {
          if ( !edge && x > 0 && !*(p-1) ) edge = true;
          if ( !edge && x < (width-1) && !*(p+1) ) edge = true;
          if ( !edge && y > 0 && !*(p-width) ) edge = true;
          if ( !edge && y < (height-1) && !*(p+width) ) edge = true;
        }
        if ( edge )
        {
          RED_PTR_RGBA(phigh) = RED_VAL_RGBA(colour);
          GREEN_PTR_RGBA(phigh) = GREEN_VAL_RGBA(colour);
          BLUE_PTR_RGBA(phigh) = BLUE_VAL_RGBA(colour);
        }
      }
    }
  }
  else if ( p_colours == ZM_COLOUR_RGB32 )
  {
    for ( unsigned int y = lo_y; y <= hi_y; y++ )
    {
      const uint8_t* p = buffer + (y * width) + lo_x;
      Rgb* phigh = (Rgb*)(high_buff + (((y * width) + lo_x) * 4));
      for ( unsigned int x = lo_x; x <= hi_x; x++, p++, phigh++ )
      {
        bool edge = false;
        if ( *p )
        {
          if ( !edge && x > 0 && !*(p-1) ) edge = true;
          if ( !edge && x < (width-1) && !*(p+1) ) edge = true;
          if ( !edge && y > 0 && !*(p-width) ) edge = true;
          if ( !edge && y < (height-1) && !*(p+width) ) edge = true;
        }
        if ( edge )
        {
          *phigh = colour;
        }
      }
    }
  }

  return( high_image );
}

bool Image::ReadRaw( const char *filename ) {
  FILE *infile;
  if ( (infile = fopen( filename, "rb" )) == NULL ) {
    Error("Can't open %s: %s", filename, strerror(errno));
    return false;
  }

  struct stat statbuf;
  if ( fstat( fileno(infile), &statbuf ) < 0 ) {
    fclose(infile);
    Error("Can't fstat %s: %s", filename, strerror(errno));
    return false;
  }

  if ( (unsigned int)statbuf.st_size != size ) {
    fclose(infile);
    Error("Raw file size mismatch, expected %d bytes, found %ld", size, statbuf.st_size);
    return false;
  }

  if ( fread(buffer, size, 1, infile) < 1 ) {
    fclose(infile);
    Error("Unable to read from '%s': %s", filename, strerror(errno));
    return false;
  }

  fclose(infile);

  return true;
}

bool Image::WriteRaw(const char *filename) const {
  FILE *outfile;
  if ( (outfile = fopen(filename, "wb")) == NULL ) {
    Error("Can't open %s: %s", filename, strerror(errno));
    return false;
  }

  if ( fwrite( buffer, size, 1, outfile ) != 1 ) {
    Error("Unable to write to '%s': %s", filename, strerror(errno));
    fclose(outfile);
    return false;
  }

  fclose(outfile);

  return true;
}

bool Image::ReadJpeg(const char *filename, unsigned int p_colours, unsigned int p_subpixelorder) {
  unsigned int new_width, new_height, new_colours, new_subpixelorder;
  struct jpeg_decompress_struct *cinfo = readjpg_dcinfo;

  if ( !cinfo ) {
    cinfo = readjpg_dcinfo = new jpeg_decompress_struct;
    cinfo->err = jpeg_std_error(&jpg_err.pub);
    jpg_err.pub.error_exit = zm_jpeg_error_exit;
    jpg_err.pub.emit_message = zm_jpeg_emit_message;
    jpeg_create_decompress(cinfo);
  }

  FILE *infile;
  if ( (infile = fopen(filename, "rb")) == NULL ) {
    Error("Can't open %s: %s", filename, strerror(errno));
    return false;
  }

  if ( setjmp(jpg_err.setjmp_buffer) ) {
    jpeg_abort_decompress(cinfo);
    fclose(infile);
    return false;
  }

  jpeg_stdio_src(cinfo, infile);

  jpeg_read_header(cinfo, TRUE);

  if ( cinfo->num_components != 1 && cinfo->num_components != 3 ) {
    Error( "Unexpected colours when reading jpeg image: %d", colours );
    jpeg_abort_decompress(cinfo);
    fclose(infile);
    return false;
  }

  /* Check if the image has at least one huffman table defined. If not, use the standard ones */
  /* This is required for the MJPEG capture palette of USB devices */
  if ( cinfo->dc_huff_tbl_ptrs[0] == NULL ) {
    zm_use_std_huff_tables(cinfo);
  }

  new_width = cinfo->image_width;
  new_height = cinfo->image_height;

  if ( width != new_width || height != new_height ) {
    Debug(9,"Image dimensions differ. Old: %ux%u New: %ux%u",width,height,new_width,new_height);
  }

  switch(p_colours) {
    case ZM_COLOUR_GRAY8:
      {
        cinfo->out_color_space = JCS_GRAYSCALE;
        new_colours = ZM_COLOUR_GRAY8;
        new_subpixelorder = ZM_SUBPIX_ORDER_NONE;
        break;
      }
    case ZM_COLOUR_RGB32:
      {
#ifdef JCS_EXTENSIONS
        new_colours = ZM_COLOUR_RGB32;
        if ( p_subpixelorder == ZM_SUBPIX_ORDER_BGRA ) {
          cinfo->out_color_space = JCS_EXT_BGRX;
          new_subpixelorder = ZM_SUBPIX_ORDER_BGRA;
        } else if ( p_subpixelorder == ZM_SUBPIX_ORDER_ARGB ) {
          cinfo->out_color_space = JCS_EXT_XRGB;
          new_subpixelorder = ZM_SUBPIX_ORDER_ARGB;
        } else if ( p_subpixelorder == ZM_SUBPIX_ORDER_ABGR ) {
          cinfo->out_color_space = JCS_EXT_XBGR;
          new_subpixelorder = ZM_SUBPIX_ORDER_ABGR;
        } else {
          /* Assume RGBA */
          cinfo->out_color_space = JCS_EXT_RGBX;
          new_subpixelorder = ZM_SUBPIX_ORDER_RGBA;
        }
        break;
#else
        Warning("libjpeg-turbo is required for reading a JPEG directly into a RGB32 buffer, reading into a RGB24 buffer instead.");
#endif
      }
    case ZM_COLOUR_RGB24:
    default:
      {
        new_colours = ZM_COLOUR_RGB24;
        if ( p_subpixelorder == ZM_SUBPIX_ORDER_BGR ) {
#ifdef JCS_EXTENSIONS
          cinfo->out_color_space = JCS_EXT_BGR;
          new_subpixelorder = ZM_SUBPIX_ORDER_BGR;
#else
          Warning("libjpeg-turbo is required for reading a JPEG directly into a BGR24 buffer, reading into a RGB24 buffer instead.");
          cinfo->out_color_space = JCS_RGB;
          new_subpixelorder = ZM_SUBPIX_ORDER_RGB;
#endif
        } else {
          /* Assume RGB */
          /*
#ifdef JCS_EXTENSIONS
cinfo->out_color_space = JCS_EXT_RGB;
#else
cinfo->out_color_space = JCS_RGB;
#endif
           */
          cinfo->out_color_space = JCS_RGB;
          new_subpixelorder = ZM_SUBPIX_ORDER_RGB;
        }
        break;
      }
  }

  if ( WriteBuffer(new_width, new_height, new_colours, new_subpixelorder) == NULL ) {
    Error("Failed requesting writeable buffer for reading JPEG image.");
    jpeg_abort_decompress(cinfo);
    fclose(infile);
    return false;
  }

  jpeg_start_decompress(cinfo);

  JSAMPROW row_pointer;  /* pointer to a single row */
  int row_stride = width * colours; /* physical row width in buffer */
  while ( cinfo->output_scanline < cinfo->output_height ) {
    row_pointer = &buffer[cinfo->output_scanline * row_stride];
    jpeg_read_scanlines(cinfo, &row_pointer, 1);
  }

  jpeg_finish_decompress(cinfo);

  fclose(infile);

  return true;
}

// Multiple calling formats to permit inclusion (or not) of non blocking, quality_override and timestamp (exif), with suitable defaults.
// Note quality=zero means default

bool Image::WriteJpeg(const char *filename, int quality_override) const {
  return Image::WriteJpeg(filename, quality_override, (timeval){0,0});
}
bool Image::WriteJpeg(const char *filename) const {
  return Image::WriteJpeg(filename, 0, (timeval){0,0});
}
bool Image::WriteJpeg(const char *filename, bool on_blocking_abort) const {
  return Image::WriteJpeg(filename, 0, (timeval){0,0}, on_blocking_abort);
}
bool Image::WriteJpeg(const char *filename, struct timeval timestamp) const {
  return Image::WriteJpeg(filename, 0, timestamp);
}

bool Image::WriteJpeg(const char *filename, int quality_override, struct timeval timestamp) const {
  return Image::WriteJpeg(filename, quality_override, timestamp, false);
}
bool Image::WriteJpeg(const char *filename, int quality_override, struct timeval timestamp, bool on_blocking_abort) const {
  if ( config.colour_jpeg_files && (colours == ZM_COLOUR_GRAY8) ) {
    Image temp_image(*this);
    temp_image.Colourise(ZM_COLOUR_RGB24, ZM_SUBPIX_ORDER_RGB);
    return temp_image.WriteJpeg(filename, quality_override, timestamp, on_blocking_abort);
  }
  int quality = quality_override?quality_override:config.jpeg_file_quality;

  struct jpeg_compress_struct *cinfo = writejpg_ccinfo[quality];
  FILE *outfile = NULL;
  static int raw_fd = 0;
  bool need_create_comp = false;
  raw_fd = 0;

  if ( !cinfo ) {
    cinfo = writejpg_ccinfo[quality] = new jpeg_compress_struct;
    cinfo->err = jpeg_std_error(&jpg_err.pub);
    jpeg_create_compress(cinfo);
    need_create_comp = true;
  }
  if ( !on_blocking_abort ) {
    jpg_err.pub.error_exit = zm_jpeg_error_exit;
    jpg_err.pub.emit_message = zm_jpeg_emit_message;
  } else {
    jpg_err.pub.error_exit = zm_jpeg_error_silent;
    jpg_err.pub.emit_message = zm_jpeg_emit_silence;
    if ( setjmp(jpg_err.setjmp_buffer) ) {
      jpeg_abort_compress(cinfo);
      Debug(1, "Aborted a write mid-stream and %s and %d", (outfile == NULL) ? "closing file" : "file not opened", raw_fd);
      if ( raw_fd )
        close(raw_fd);
      if ( outfile )
        fclose(outfile);
      return false;
    }
  }
  if ( need_create_comp )
    jpeg_create_compress(cinfo);

  if ( !on_blocking_abort ) {
    if ( (outfile = fopen(filename, "wb")) == NULL ) {
      Error("Can't open %s for writing: %s", filename, strerror(errno));
      return false;
    }
  } else {
    raw_fd = open(filename, O_WRONLY|O_NONBLOCK|O_CREAT|O_TRUNC,S_IRUSR|S_IWUSR|S_IRGRP|S_IROTH);
    if ( raw_fd < 0 )
      return false;
    outfile = fdopen(raw_fd, "wb");
    if ( outfile == NULL ) {
      close(raw_fd);
      return false;
    }
  }

  jpeg_stdio_dest(cinfo, outfile);

  cinfo->image_width = width;   /* image width and height, in pixels */
  cinfo->image_height = height;

  switch (colours) {
    case ZM_COLOUR_GRAY8:
        cinfo->input_components = 1;
        cinfo->in_color_space = JCS_GRAYSCALE;
        break;
    case ZM_COLOUR_RGB32:
#ifdef JCS_EXTENSIONS
        cinfo->input_components = 4;
        if ( subpixelorder == ZM_SUBPIX_ORDER_BGRA ) {
          cinfo->in_color_space = JCS_EXT_BGRX;
        } else if ( subpixelorder == ZM_SUBPIX_ORDER_ARGB ) {
          cinfo->in_color_space = JCS_EXT_XRGB;
        } else if ( subpixelorder == ZM_SUBPIX_ORDER_ABGR ) {
          cinfo->in_color_space = JCS_EXT_XBGR;
        } else {
          /* Assume RGBA */
          cinfo->in_color_space = JCS_EXT_RGBX;
        }
        break;
#else
        Error("libjpeg-turbo is required for JPEG encoding directly from RGB32 source");
        jpeg_abort_compress(cinfo);
        fclose(outfile);
        return false;
#endif
    case ZM_COLOUR_RGB24:
    default:
        cinfo->input_components = 3;
        if ( subpixelorder == ZM_SUBPIX_ORDER_BGR) {
#ifdef JCS_EXTENSIONS
          cinfo->in_color_space = JCS_EXT_BGR;
#else
          Error("libjpeg-turbo is required for JPEG encoding directly from BGR24 source");
          jpeg_abort_compress(cinfo);
          fclose(outfile);
          return false;
#endif
        } else {
          /* Assume RGB */
          /*
#ifdef JCS_EXTENSIONS
cinfo->out_color_space = JCS_EXT_RGB;
#else
cinfo->out_color_space = JCS_RGB;
#endif
           */
          cinfo->in_color_space = JCS_RGB;
        }
        break;
  }  // end switch(colours)

  jpeg_set_defaults(cinfo);
  jpeg_set_quality(cinfo, quality, FALSE);
  cinfo->dct_method = JDCT_FASTEST;

  jpeg_start_compress(cinfo, TRUE);
  if ( config.add_jpeg_comments && text[0] ) {
    jpeg_write_marker(cinfo, JPEG_COM, (const JOCTET *)text, strlen(text));
  }
  // If we have a non-zero time (meaning a parameter was passed in), then form a simple exif segment with that time as DateTimeOriginal and SubsecTimeOriginal
  // No timestamp just leave off the exif section.
  if ( timestamp.tv_sec ) {
#define EXIFTIMES_MS_OFFSET 0x36   // three decimal digits for milliseconds
#define EXIFTIMES_MS_LEN  0x03
#define EXIFTIMES_OFFSET  0x3E   // 19 characters format '2015:07:21 13:14:45' not including quotes
#define EXIFTIMES_LEN     0x13   // = 19
#define EXIF_CODE       0xE1

    // This is a lot of stuff to allocate on the stack.  Recommend char *timebuf[64];
    char timebuf[64], msbuf[64];
    strftime(timebuf, sizeof timebuf, "%Y:%m:%d %H:%M:%S", localtime(&(timestamp.tv_sec)));
    snprintf(msbuf, sizeof msbuf, "%06d",(int)(timestamp.tv_usec));  // we only use milliseconds because that's all defined in exif, but this is the whole microseconds because we have it
    unsigned char exiftimes[82] = {
      0x45, 0x78, 0x69, 0x66, 0x00, 0x00, 0x49, 0x49, 0x2A, 0x00, 0x08, 0x00, 0x00, 0x00, 0x01, 0x00,
      0x69, 0x87, 0x04, 0x00, 0x01, 0x00, 0x00, 0x00, 0x1A, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00,
      0x02, 0x00, 0x03, 0x90, 0x02, 0x00, 0x14, 0x00, 0x00, 0x00, 0x38, 0x00, 0x00, 0x00, 0x91, 0x92,
      0x02, 0x00, 0x04, 0x00, 0x00, 0x00, 0xff, 0xff, 0xff, 0x00, 0x00, 0x00, 0x00, 0x00, 0xff, 0xff,
      0xff, 0xff, 0xff, 0xff, 0xff, 0xff, 0xff, 0xff, 0xff, 0xff, 0xff, 0xff, 0xff, 0xff, 0xff, 0xff,
      0xff, 0x00 };
    memcpy(&exiftimes[EXIFTIMES_OFFSET], timebuf,EXIFTIMES_LEN);
    memcpy(&exiftimes[EXIFTIMES_MS_OFFSET], msbuf, EXIFTIMES_MS_LEN);
    jpeg_write_marker(cinfo, EXIF_CODE, (const JOCTET *)exiftimes, sizeof(exiftimes));
  }

  JSAMPROW row_pointer;  /* pointer to a single row */
  int row_stride = cinfo->image_width * colours; /* physical row width in buffer */
  while ( cinfo->next_scanline < cinfo->image_height ) {
    row_pointer = &buffer[cinfo->next_scanline * row_stride];
    jpeg_write_scanlines(cinfo, &row_pointer, 1);
  }

  jpeg_finish_compress(cinfo);

  fclose(outfile);

  return true;
}

bool Image::DecodeJpeg(
    const JOCTET *inbuffer,
    int inbuffer_size,
    unsigned int p_colours,
    unsigned int p_subpixelorder)
{
  unsigned int new_width, new_height, new_colours, new_subpixelorder;
  struct jpeg_decompress_struct *cinfo = decodejpg_dcinfo;

  if ( !cinfo ) {
    cinfo = decodejpg_dcinfo = new jpeg_decompress_struct;
    cinfo->err = jpeg_std_error( &jpg_err.pub );
    jpg_err.pub.error_exit = zm_jpeg_error_exit;
    jpg_err.pub.emit_message = zm_jpeg_emit_message;
    jpeg_create_decompress( cinfo );
  }

  if ( setjmp(jpg_err.setjmp_buffer) ) {
    jpeg_abort_decompress(cinfo);
    return false;
  }

  zm_jpeg_mem_src(cinfo, inbuffer, inbuffer_size);

  jpeg_read_header(cinfo, TRUE);

  if ( (cinfo->num_components != 1) && (cinfo->num_components != 3) ) {
    Error("Unexpected colours when reading jpeg image: %d", colours);
    jpeg_abort_decompress(cinfo);
    return false;
  }

  /* Check if the image has at least one huffman table defined. If not, use the standard ones */
  /* This is required for the MJPEG capture palette of USB devices */
  if ( cinfo->dc_huff_tbl_ptrs[0] == NULL ) {
    zm_use_std_huff_tables(cinfo);
  }

  new_width = cinfo->image_width;
  new_height = cinfo->image_height;

  if ( width != new_width || height != new_height ) {
    Debug(9, "Image dimensions differ. Old: %ux%u New: %ux%u",
        width, height, new_width, new_height);
  }

  switch (p_colours) {
    case ZM_COLOUR_GRAY8:
        cinfo->out_color_space = JCS_GRAYSCALE;
        new_colours = ZM_COLOUR_GRAY8;
        new_subpixelorder = ZM_SUBPIX_ORDER_NONE;
        break;
    case ZM_COLOUR_RGB32:
#ifdef JCS_EXTENSIONS
        new_colours = ZM_COLOUR_RGB32;
        if ( p_subpixelorder == ZM_SUBPIX_ORDER_BGRA ) {
          cinfo->out_color_space = JCS_EXT_BGRX;
          new_subpixelorder = ZM_SUBPIX_ORDER_BGRA;
        } else if ( p_subpixelorder == ZM_SUBPIX_ORDER_ARGB ) {
          cinfo->out_color_space = JCS_EXT_XRGB;
          new_subpixelorder = ZM_SUBPIX_ORDER_ARGB;
        } else if ( p_subpixelorder == ZM_SUBPIX_ORDER_ABGR ) {
          cinfo->out_color_space = JCS_EXT_XBGR;
          new_subpixelorder = ZM_SUBPIX_ORDER_ABGR;
        } else {
          /* Assume RGBA */
          cinfo->out_color_space = JCS_EXT_RGBX;
          new_subpixelorder = ZM_SUBPIX_ORDER_RGBA;
        }
        break;
#else
        Warning("libjpeg-turbo is required for reading a JPEG directly into a RGB32 buffer, reading into a RGB24 buffer instead.");
#endif
    case ZM_COLOUR_RGB24:
    default:
        new_colours = ZM_COLOUR_RGB24;
        if ( p_subpixelorder == ZM_SUBPIX_ORDER_BGR ) {
#ifdef JCS_EXTENSIONS
          cinfo->out_color_space = JCS_EXT_BGR;
          new_subpixelorder = ZM_SUBPIX_ORDER_BGR;
#else
          Warning("libjpeg-turbo is required for reading a JPEG directly into a BGR24 buffer, reading into a RGB24 buffer instead.");
          cinfo->out_color_space = JCS_RGB;
          new_subpixelorder = ZM_SUBPIX_ORDER_RGB;
#endif
        } else {
          /* Assume RGB */
          /*
#ifdef JCS_EXTENSIONS
cinfo->out_color_space = JCS_EXT_RGB;
#else
cinfo->out_color_space = JCS_RGB;
#endif
           */
          cinfo->out_color_space = JCS_RGB;
          new_subpixelorder = ZM_SUBPIX_ORDER_RGB;
        }
        break;
  } // end switch

  if ( WriteBuffer(new_width, new_height, new_colours, new_subpixelorder) == NULL ) {
    Error("Failed requesting writeable buffer for reading JPEG image.");
    jpeg_abort_decompress(cinfo);
    return false;
  }

  jpeg_start_decompress(cinfo);

  JSAMPROW row_pointer;  /* pointer to a single row */
  int row_stride = width * colours; /* physical row width in buffer */
  while ( cinfo->output_scanline < cinfo->output_height ) {
    row_pointer = &buffer[cinfo->output_scanline * row_stride];
    jpeg_read_scanlines(cinfo, &row_pointer, 1);
  }

  jpeg_finish_decompress(cinfo);

  return true;
}

bool Image::EncodeJpeg(JOCTET *outbuffer, int *outbuffer_size, int quality_override) const {
  if ( config.colour_jpeg_files && (colours == ZM_COLOUR_GRAY8) ) {
    Image temp_image(*this);
    temp_image.Colourise(ZM_COLOUR_RGB24, ZM_SUBPIX_ORDER_RGB);
    return temp_image.EncodeJpeg(outbuffer, outbuffer_size, quality_override);
  }

  int quality = quality_override?quality_override:config.jpeg_stream_quality;

  struct jpeg_compress_struct *cinfo = encodejpg_ccinfo[quality];

  if ( !cinfo ) {
    cinfo = encodejpg_ccinfo[quality] = new jpeg_compress_struct;
    cinfo->err = jpeg_std_error(&jpg_err.pub);
    jpg_err.pub.error_exit = zm_jpeg_error_exit;
    jpg_err.pub.emit_message = zm_jpeg_emit_message;
    jpeg_create_compress(cinfo);
  }

  zm_jpeg_mem_dest(cinfo, outbuffer, outbuffer_size);

  cinfo->image_width = width;   /* image width and height, in pixels */
  cinfo->image_height = height;

  switch (colours) {
    case ZM_COLOUR_GRAY8:
        cinfo->input_components = 1;
        cinfo->in_color_space = JCS_GRAYSCALE;
        break;
    case ZM_COLOUR_RGB32:
#ifdef JCS_EXTENSIONS
        cinfo->input_components = 4;
        if ( subpixelorder == ZM_SUBPIX_ORDER_BGRA ) {
          cinfo->in_color_space = JCS_EXT_BGRX;
        } else if ( subpixelorder == ZM_SUBPIX_ORDER_ARGB ) {
          cinfo->in_color_space = JCS_EXT_XRGB;
        } else if ( subpixelorder == ZM_SUBPIX_ORDER_ABGR ) {
          cinfo->in_color_space = JCS_EXT_XBGR;
        } else {
          /* Assume RGBA */
          cinfo->in_color_space = JCS_EXT_RGBX;
        }
        break;
#else
        Error("libjpeg-turbo is required for JPEG encoding directly from RGB32 source");
        jpeg_abort_compress(cinfo);
        return false;
#endif
    case ZM_COLOUR_RGB24:
    default:
        cinfo->input_components = 3;
        if ( subpixelorder == ZM_SUBPIX_ORDER_BGR ) {
#ifdef JCS_EXTENSIONS
          cinfo->in_color_space = JCS_EXT_BGR;
#else
          Error("libjpeg-turbo is required for JPEG encoding directly from BGR24 source");
          jpeg_abort_compress(cinfo);
          return false;
#endif
        } else {
          /* Assume RGB */
          /*
#ifdef JCS_EXTENSIONS
cinfo->out_color_space = JCS_EXT_RGB;
#else
cinfo->out_color_space = JCS_RGB;
#endif
           */
          cinfo->in_color_space = JCS_RGB;
        }
        break;
  } // end switch

  jpeg_set_defaults(cinfo);
  jpeg_set_quality(cinfo, quality, FALSE);
  cinfo->dct_method = JDCT_FASTEST;

  jpeg_start_compress(cinfo, TRUE);

  JSAMPROW row_pointer;  /* pointer to a single row */
  int row_stride = cinfo->image_width * colours; /* physical row width in buffer */
  while ( cinfo->next_scanline < cinfo->image_height ) {
    row_pointer = &buffer[cinfo->next_scanline * row_stride];
    jpeg_write_scanlines(cinfo, &row_pointer, 1);
  }

  jpeg_finish_compress(cinfo);

  return true;
}

#if HAVE_ZLIB_H
bool Image::Unzip( const Bytef *inbuffer, unsigned long inbuffer_size ) {
  unsigned long zip_size = size;
  int result = uncompress( buffer, &zip_size, inbuffer, inbuffer_size );
  if ( result != Z_OK ) {
    Error("Unzip failed, result = %d", result);
    return false;
  }
  if ( zip_size != (unsigned int)size ) {
    Error("Unzip failed, size mismatch, expected %d bytes, got %ld", size, zip_size);
    return false;
  }
  return true;
}

bool Image::Zip( Bytef *outbuffer, unsigned long *outbuffer_size, int compression_level ) const {
  int result = compress2( outbuffer, outbuffer_size, buffer, size, compression_level );
  if ( result != Z_OK ) {
    Error("Zip failed, result = %d", result);
    return false;
  }
  return true;
}
#endif // HAVE_ZLIB_H

bool Image::Crop( unsigned int lo_x, unsigned int lo_y, unsigned int hi_x, unsigned int hi_y ) {
  unsigned int new_width = (hi_x-lo_x)+1;
  unsigned int new_height = (hi_y-lo_y)+1;

  if ( lo_x > hi_x || lo_y > hi_y ) {
    Error( "Invalid or reversed crop region %d,%d -> %d,%d", lo_x, lo_y, hi_x, hi_y );
    return( false );
  }
  if ( hi_x > (width-1) || ( hi_y > (height-1) ) ) {
    Error( "Attempting to crop outside image, %d,%d -> %d,%d not in %d,%d", lo_x, lo_y, hi_x, hi_y, width-1, height-1 );
    return false;
  }

  if ( new_width == width && new_height == height ) {
    return true;
  }

  unsigned int new_size = new_width*new_height*colours;
  uint8_t *new_buffer = AllocBuffer(new_size);

  unsigned int new_stride = new_width*colours;
  for ( unsigned int y = lo_y, ny = 0; y <= hi_y; y++, ny++ ) {
    unsigned char *pbuf = &buffer[((y*width)+lo_x)*colours];
    unsigned char *pnbuf = &new_buffer[(ny*new_width)*colours];
    memcpy( pnbuf, pbuf, new_stride );
  }

  AssignDirect(new_width, new_height, colours, subpixelorder, new_buffer, new_size, ZM_BUFTYPE_ZM);

  return true;
}

bool Image::Crop( const Box &limits ) {
  return Crop( limits.LoX(), limits.LoY(), limits.HiX(), limits.HiY() );
}

/* Far from complete */
/* Need to implement all possible of overlays possible */
void Image::Overlay( const Image &image ) {
  if ( !(width == image.width && height == image.height) ) {
    Panic("Attempt to overlay different sized images, expected %dx%d, got %dx%d",
        width, height, image.width, image.height);
  }

  if ( colours == image.colours && subpixelorder != image.subpixelorder ) {
    Warning("Attempt to overlay images of same format but with different subpixel order.");
  }

  /* Grayscale ontop of grayscale - complete */
  if ( colours == ZM_COLOUR_GRAY8 && image.colours == ZM_COLOUR_GRAY8 ) {
    const uint8_t* const max_ptr = buffer+size;
    const uint8_t* psrc = image.buffer;
    uint8_t* pdest = buffer;

    while( pdest < max_ptr ) {
      if ( *psrc ) {
        *pdest = *psrc;
      }
      pdest++;
      psrc++;
    }

    /* RGB24 ontop of grayscale - convert to same format first - complete */
  } else if ( colours == ZM_COLOUR_GRAY8 && image.colours == ZM_COLOUR_RGB24 ) {
    Colourise(image.colours, image.subpixelorder);

    const uint8_t* const max_ptr = buffer+size;
    const uint8_t* psrc = image.buffer;
    uint8_t* pdest = buffer;

    while( pdest < max_ptr ) {
      if ( RED_PTR_RGBA(psrc) || GREEN_PTR_RGBA(psrc) || BLUE_PTR_RGBA(psrc) ) {
        RED_PTR_RGBA(pdest) = RED_PTR_RGBA(psrc);
        GREEN_PTR_RGBA(pdest) = GREEN_PTR_RGBA(psrc);
        BLUE_PTR_RGBA(pdest) = BLUE_PTR_RGBA(psrc);
      }
      pdest += 3;
      psrc += 3;
    }

    /* RGB32 ontop of grayscale - convert to same format first - complete */
  } else if( colours == ZM_COLOUR_GRAY8 && image.colours == ZM_COLOUR_RGB32 ) {
    Colourise(image.colours, image.subpixelorder);

    const Rgb* const max_ptr = (Rgb*)(buffer+size);
    const Rgb* prsrc = (Rgb*)image.buffer;
    Rgb* prdest = (Rgb*)buffer;

    if ( subpixelorder == ZM_SUBPIX_ORDER_RGBA || subpixelorder == ZM_SUBPIX_ORDER_BGRA ) {
      /* RGB\BGR\RGBA\BGRA subpixel order - Alpha byte is last */
      while (prdest < max_ptr) {
        if ( RED_PTR_RGBA(prsrc) || GREEN_PTR_RGBA(prsrc) || BLUE_PTR_RGBA(prsrc) ) {
          *prdest = *prsrc;
        }
        prdest++;
        prsrc++;
      }
    } else {
      /* ABGR\ARGB subpixel order - Alpha byte is first */
      while (prdest < max_ptr) {
        if ( RED_PTR_ABGR(prsrc) || GREEN_PTR_ABGR(prsrc) || BLUE_PTR_ABGR(prsrc) ) {
          *prdest = *prsrc;
        }
        prdest++;
        prsrc++;
      }
    }

    /* Grayscale ontop of RGB24 - complete */
  } else if ( colours == ZM_COLOUR_RGB24 && image.colours == ZM_COLOUR_GRAY8 ) {
    const uint8_t* const max_ptr = buffer+size;
    const uint8_t* psrc = image.buffer;
    uint8_t* pdest = buffer;

    while( pdest < max_ptr ) {
      if ( *psrc ) {
        RED_PTR_RGBA(pdest) = GREEN_PTR_RGBA(pdest) = BLUE_PTR_RGBA(pdest) = *psrc;
      }
      pdest += 3;
      psrc++;
    }

    /* RGB24 ontop of RGB24 - not complete. need to take care of different subpixel orders */
  } else if ( colours == ZM_COLOUR_RGB24 && image.colours == ZM_COLOUR_RGB24 ) {
    const uint8_t* const max_ptr = buffer+size;
    const uint8_t* psrc = image.buffer;
    uint8_t* pdest = buffer;

    while( pdest < max_ptr ) {
      if ( RED_PTR_RGBA(psrc) || GREEN_PTR_RGBA(psrc) || BLUE_PTR_RGBA(psrc) ) {
        RED_PTR_RGBA(pdest) = RED_PTR_RGBA(psrc);
        GREEN_PTR_RGBA(pdest) = GREEN_PTR_RGBA(psrc);
        BLUE_PTR_RGBA(pdest) = BLUE_PTR_RGBA(psrc);
      }
      pdest += 3;
      psrc += 3;
    }

    /* RGB32 ontop of RGB24 - TO BE DONE */
  } else if ( colours == ZM_COLOUR_RGB24 && image.colours == ZM_COLOUR_RGB32 ) {
    Error("Overlay of RGB32 ontop of RGB24 is not supported.");

    /* Grayscale ontop of RGB32 - complete */
  } else if ( colours == ZM_COLOUR_RGB32 && image.colours == ZM_COLOUR_GRAY8 ) {
    const Rgb* const max_ptr = (Rgb*)(buffer+size);
    Rgb* prdest = (Rgb*)buffer;
    const uint8_t* psrc = image.buffer;

    if ( subpixelorder == ZM_SUBPIX_ORDER_RGBA || subpixelorder == ZM_SUBPIX_ORDER_BGRA ) {
      /* RGBA\BGRA subpixel order - Alpha byte is last */
      while ( prdest < max_ptr ) {
        if ( *psrc ) {
          RED_PTR_RGBA(prdest) = GREEN_PTR_RGBA(prdest) = BLUE_PTR_RGBA(prdest) = *psrc;
        }
        prdest++;
        psrc++;
      }
    } else {
      /* ABGR\ARGB subpixel order - Alpha byte is first */
      while ( prdest < max_ptr ) {
        if ( *psrc ) {
          RED_PTR_ABGR(prdest) = GREEN_PTR_ABGR(prdest) = BLUE_PTR_ABGR(prdest) = *psrc;
        }
        prdest++;
        psrc++;
      }
    }

    /* RGB24 ontop of RGB32 - TO BE DONE */
  } else if ( colours == ZM_COLOUR_RGB32 && image.colours == ZM_COLOUR_RGB24 ) {
    Error("Overlay of RGB24 ontop of RGB32 is not supported.");

    /* RGB32 ontop of RGB32 - not complete. need to take care of different subpixel orders */
  } else if ( colours == ZM_COLOUR_RGB32 && image.colours == ZM_COLOUR_RGB32 ) {
    const Rgb* const max_ptr = (Rgb*)(buffer+size);
    Rgb* prdest = (Rgb*)buffer;
    const Rgb* prsrc = (Rgb*)image.buffer;

    if ( image.subpixelorder == ZM_SUBPIX_ORDER_RGBA || image.subpixelorder == ZM_SUBPIX_ORDER_BGRA ) {
      /* RGB\BGR\RGBA\BGRA subpixel order - Alpha byte is last */
      while ( prdest < max_ptr ) {
        if ( RED_PTR_RGBA(prsrc) || GREEN_PTR_RGBA(prsrc) || BLUE_PTR_RGBA(prsrc) ) {
          *prdest = *prsrc;
        }
        prdest++;
        prsrc++;
      }
    } else {
      /* ABGR\ARGB subpixel order - Alpha byte is first */
      while ( prdest < max_ptr ) {
        if ( RED_PTR_ABGR(prsrc) || GREEN_PTR_ABGR(prsrc) || BLUE_PTR_ABGR(prsrc) ) {
          *prdest = *prsrc;
        }
        prdest++;
        prsrc++;
      }
    }
  }

}

/* RGB32 compatible: complete */
void Image::Overlay( const Image &image, unsigned int x, unsigned int y ) {
  if ( !(width < image.width || height < image.height) ) {
    Panic("Attempt to overlay image too big for destination, %dx%d > %dx%d",
        image.width, image.height, width, height );
  }

  if ( !(width < (x+image.width) || height < (y+image.height)) ) {
    Panic("Attempt to overlay image outside of destination bounds, %dx%d @ %dx%d > %dx%d",
        image.width, image.height, x, y, width, height );
  }

  if ( !(colours == image.colours) ) {
    Panic("Attempt to partial overlay differently coloured images, expected %d, got %d",
        colours, image.colours);
  }

  unsigned int lo_x = x;
  unsigned int lo_y = y;
  unsigned int hi_x = (x+image.width)-1;
  unsigned int hi_y = (y+image.height-1);
  if ( colours == ZM_COLOUR_GRAY8 ) {
    const uint8_t *psrc = image.buffer;
    for ( unsigned int y = lo_y; y <= hi_y; y++ ) {
      uint8_t *pdest = &buffer[(y*width)+lo_x];
      for ( unsigned int x = lo_x; x <= hi_x; x++ ) {
        *pdest++ = *psrc++;
      }
    }
  } else if ( colours == ZM_COLOUR_RGB24 ) {
    const uint8_t *psrc = image.buffer;
    for ( unsigned int y = lo_y; y <= hi_y; y++ ) {
      uint8_t *pdest = &buffer[colours*((y*width)+lo_x)];
      for ( unsigned int x = lo_x; x <= hi_x; x++ ) {
        *pdest++ = *psrc++;
        *pdest++ = *psrc++;
        *pdest++ = *psrc++;
      }
    }
  } else if ( colours == ZM_COLOUR_RGB32 ) {
    const Rgb *psrc = (Rgb*)(image.buffer);
    for ( unsigned int y = lo_y; y <= hi_y; y++ ) {
      Rgb *pdest = (Rgb*)&buffer[((y*width)+lo_x)<<2];
      for ( unsigned int x = lo_x; x <= hi_x; x++ ) {
        *pdest++ = *psrc++;
      }
    }
  } else {
    Error("Overlay called with unexpected colours: %d", colours);
  }

}

void Image::Blend( const Image &image, int transparency ) {
#ifdef ZM_IMAGE_PROFILING
  struct timespec start,end,diff;
  unsigned long long executetime;
  unsigned long milpixels;
#endif
  uint8_t* new_buffer;

  if ( !(
        width == image.width && height == image.height
        && colours == image.colours
        && subpixelorder == image.subpixelorder
        ) ) {
    Panic("Attempt to blend different sized images, expected %dx%dx%d %d, got %dx%dx%d %d",
        width, height, colours, subpixelorder, image.width, image.height, image.colours, image.subpixelorder );
  }

  if ( transparency <= 0 )
    return;

  new_buffer = AllocBuffer(size);

#ifdef ZM_IMAGE_PROFILING
  clock_gettime(CLOCK_THREAD_CPUTIME_ID,&start);
#endif

  /* Do the blending */
  (*blend)(buffer, image.buffer, new_buffer, size, transparency);

#ifdef ZM_IMAGE_PROFILING
  clock_gettime(CLOCK_THREAD_CPUTIME_ID,&end);
  timespec_diff(&start,&end,&diff);

  executetime = (1000000000ull * diff.tv_sec) + diff.tv_nsec;
  milpixels = (unsigned long)((long double)size)/((((long double)executetime)/1000));
  Debug(5, "Blend: %u colours blended in %llu nanoseconds, %lu million colours/s\n",size,executetime,milpixels);
#endif

  AssignDirect(width, height, colours, subpixelorder, new_buffer, size, ZM_BUFTYPE_ZM);
}

Image *Image::Merge( unsigned int n_images, Image *images[] ) {
  if ( n_images == 1 ) return new Image(*images[0]);

  unsigned int width = images[0]->width;
  unsigned int height = images[0]->height;
  unsigned int colours = images[0]->colours;
  for ( unsigned int i = 1; i < n_images; i++ ) {
    if ( !(width == images[i]->width && height == images[i]->height && colours == images[i]->colours) ) {
      Panic("Attempt to merge different sized images, expected %dx%dx%d, got %dx%dx%d, for image %d",
          width, height, colours, images[i]->width, images[i]->height, images[i]->colours, i );
    }
  }

  Image *result = new Image(width, height, images[0]->colours, images[0]->subpixelorder);
  unsigned int size = result->size;
  for ( unsigned int i = 0; i < size; i++ ) {
    unsigned int total = 0;
    uint8_t *pdest = result->buffer;
    for ( unsigned int j = 0; j < n_images; j++ ) {
      uint8_t *psrc = images[j]->buffer;
      total += *psrc;
      psrc++;
    }
    *pdest = total/n_images;
    pdest++;
  }
  return result;
}

Image *Image::Merge( unsigned int n_images, Image *images[], double weight ) {
  if ( n_images == 1 ) return new Image(*images[0]);

  unsigned int width = images[0]->width;
  unsigned int height = images[0]->height;
  unsigned int colours = images[0]->colours;
  for ( unsigned int i = 1; i < n_images; i++ ) {
    if ( !(width == images[i]->width && height == images[i]->height && colours == images[i]->colours) ) {
      Panic("Attempt to merge different sized images, expected %dx%dx%d, got %dx%dx%d, for image %d",
          width, height, colours, images[i]->width, images[i]->height, images[i]->colours, i );
    }
  }

  Image *result = new Image( *images[0] );
  unsigned int size = result->size;
  double factor = 1.0*weight;
  for ( unsigned int i = 1; i < n_images; i++ ) {
    uint8_t *pdest = result->buffer;
    uint8_t *psrc = images[i]->buffer;
    for ( unsigned int j = 0; j < size; j++ ) {
      *pdest = (uint8_t)(((*pdest)*(1.0-factor))+((*psrc)*factor));
      pdest++;
      psrc++;
    }
    factor *= weight;
  }
  return result;
}

Image *Image::Highlight( unsigned int n_images, Image *images[], const Rgb threshold, const Rgb ref_colour ) {
  if ( n_images == 1 ) return new Image(*images[0]);

  unsigned int width = images[0]->width;
  unsigned int height = images[0]->height;
  unsigned int colours = images[0]->colours;
  for ( unsigned int i = 1; i < n_images; i++ ) {
    if ( !(width == images[i]->width && height == images[i]->height && colours == images[i]->colours) ) {
      Panic( "Attempt to highlight different sized images, expected %dx%dx%d, got %dx%dx%d, for image %d",
          width, height, colours, images[i]->width, images[i]->height, images[i]->colours, i );
    }
  }

  Image *result = new Image( width, height, images[0]->colours, images[0]->subpixelorder );
  unsigned int size = result->size;
  for ( unsigned int c = 0; c < colours; c++ ) {
    unsigned int ref_colour_rgb = RGB_VAL(ref_colour,c);

    for ( unsigned int i = 0; i < size; i++ ) {
      unsigned int count = 0;
      uint8_t *pdest = result->buffer+c;
      for ( unsigned int j = 0; j < n_images; j++ ) {
        uint8_t *psrc = images[j]->buffer+c;

	    unsigned int diff = ((*psrc)-ref_colour_rgb) > 0 ? (*psrc)-ref_colour_rgb : ref_colour_rgb - (*psrc);

	    if (diff >= RGB_VAL(threshold,c)) {
          count++;
        }
        psrc += colours;
      }
      *pdest = (count*255)/n_images;
      pdest += 3;
    }
  }
  return result;
}

/* New function to allow buffer re-using instead of allocationg memory for the delta image every time */
void Image::Delta( const Image &image, Image* targetimage) const {
#ifdef ZM_IMAGE_PROFILING
  struct timespec start,end,diff;
  unsigned long long executetime;
  unsigned long milpixels;
#endif

  if ( !(width == image.width && height == image.height && colours == image.colours && subpixelorder == image.subpixelorder) ) {
    Panic( "Attempt to get delta of different sized images, expected %dx%dx%d %d, got %dx%dx%d %d",
        width, height, colours, subpixelorder, image.width, image.height, image.colours, image.subpixelorder);
  }

  uint8_t *pdiff = targetimage->WriteBuffer(width, height, ZM_COLOUR_GRAY8, ZM_SUBPIX_ORDER_NONE);

  if ( pdiff == NULL ) {
    Panic("Failed requesting writeable buffer for storing the delta image");
  }

#ifdef ZM_IMAGE_PROFILING
  clock_gettime(CLOCK_THREAD_CPUTIME_ID,&start);
#endif

  switch (colours) {
    case ZM_COLOUR_RGB24:
      if ( subpixelorder == ZM_SUBPIX_ORDER_BGR ) {
        /* BGR subpixel order */
        (*delta8_bgr)(buffer, image.buffer, pdiff, pixels);
      } else {
        /* Assume RGB subpixel order */
        (*delta8_rgb)(buffer, image.buffer, pdiff, pixels);
      }
      break;
    case ZM_COLOUR_RGB32:
      if ( subpixelorder == ZM_SUBPIX_ORDER_ARGB ) {
        /* ARGB subpixel order */
        (*delta8_argb)(buffer, image.buffer, pdiff, pixels);
      } else if(subpixelorder == ZM_SUBPIX_ORDER_ABGR) {
        /* ABGR subpixel order */
        (*delta8_abgr)(buffer, image.buffer, pdiff, pixels);
      } else if(subpixelorder == ZM_SUBPIX_ORDER_BGRA) {
        /* BGRA subpixel order */
        (*delta8_bgra)(buffer, image.buffer, pdiff, pixels);
      } else {
        /* Assume RGBA subpixel order */
        (*delta8_rgba)(buffer, image.buffer, pdiff, pixels);
      }
      break;
    case ZM_COLOUR_GRAY8:
      (*delta8_gray8)(buffer, image.buffer, pdiff, pixels);
      break;
    default:
      Panic("Delta called with unexpected colours: %d",colours);
      break;
  }

#ifdef ZM_IMAGE_PROFILING
  clock_gettime(CLOCK_THREAD_CPUTIME_ID,&end);
  timespec_diff(&start,&end,&diff);

  executetime = (1000000000ull * diff.tv_sec) + diff.tv_nsec;
  milpixels = (unsigned long)((long double)pixels)/((((long double)executetime)/1000));
  Debug(5, "Delta: %u delta pixels generated in %llu nanoseconds, %lu million pixels/s",pixels,executetime,milpixels);
#endif
}

const Coord Image::centreCoord( const char *text ) const {
  int index = 0;
  int line_no = 0;
  int text_len = strlen( text );
  int line_len = 0;
  int max_line_len = 0;
  const char *line = text;

  while ( (index < text_len) && (line_len = strcspn( line, "\n" )) ) {
    if ( line_len > max_line_len )
      max_line_len = line_len;

    index += line_len;
    while ( text[index] == '\n' ) {
      index++;
    }
    line = text+index;
    line_no++;
  }
  int x = (width - (max_line_len * ZM_CHAR_WIDTH) ) / 2;
  int y = (height - (line_no * LINE_HEIGHT) ) / 2;
  return( Coord( x, y ) );
}

/* RGB32 compatible: complete */
void Image::MaskPrivacy( const unsigned char *p_bitmask, const Rgb pixel_colour ) {
  const uint8_t pixel_r_col = RED_VAL_RGBA(pixel_colour);
  const uint8_t pixel_g_col = GREEN_VAL_RGBA(pixel_colour);
  const uint8_t pixel_b_col = BLUE_VAL_RGBA(pixel_colour);
  const uint8_t pixel_bw_col = pixel_colour & 0xff;
  const Rgb pixel_rgb_col = rgb_convert(pixel_colour,subpixelorder);

  unsigned char *ptr = &buffer[0];
  unsigned int i = 0;

  for ( unsigned int y = 0; y < height; y++ ) {
    if ( colours == ZM_COLOUR_GRAY8 ) {
      for ( unsigned int x = 0; x < width; x++, ptr++ ) {
        if ( p_bitmask[i] )
          *ptr = pixel_bw_col;
        i++;
      }
    } else if ( colours == ZM_COLOUR_RGB24 ) {
      for ( unsigned int x = 0; x < width; x++, ptr += colours ) {
        if ( p_bitmask[i] ) {
          RED_PTR_RGBA(ptr) = pixel_r_col;
          GREEN_PTR_RGBA(ptr) = pixel_g_col;
          BLUE_PTR_RGBA(ptr) = pixel_b_col;
        }
        i++;
      }
    } else if ( colours == ZM_COLOUR_RGB32 ) {
      for ( unsigned int x = 0; x < width; x++, ptr += colours ) {
        Rgb *temp_ptr = (Rgb*)ptr;
        if ( p_bitmask[i] )
          *temp_ptr = pixel_rgb_col;
        i++;
      }
    } else {
      Panic("MaskPrivacy called with unexpected colours: %d", colours);
      return;
    }
  } // end foreach y
}

/* RGB32 compatible: complete */
void Image::Annotate( const char *p_text, const Coord &coord, const unsigned int size, const Rgb fg_colour, const Rgb bg_colour )
{
  strncpy( text, p_text, sizeof(text)-1 );

  unsigned int index = 0;
  unsigned int line_no = 0;
  unsigned int text_len = strlen( text );
  unsigned int line_len = 0;
  const char *line = text;

  const uint8_t fg_r_col = RED_VAL_RGBA(fg_colour);
  const uint8_t fg_g_col = GREEN_VAL_RGBA(fg_colour);
  const uint8_t fg_b_col = BLUE_VAL_RGBA(fg_colour);
  const uint8_t fg_bw_col = fg_colour & 0xff;
  const Rgb fg_rgb_col = rgb_convert(fg_colour,subpixelorder);
  const bool fg_trans = (fg_colour == RGB_TRANSPARENT);

  const uint8_t bg_r_col = RED_VAL_RGBA(bg_colour);
  const uint8_t bg_g_col = GREEN_VAL_RGBA(bg_colour);
  const uint8_t bg_b_col = BLUE_VAL_RGBA(bg_colour);
  const uint8_t bg_bw_col = bg_colour & 0xff;
  const Rgb bg_rgb_col = rgb_convert(bg_colour,subpixelorder);
  const bool bg_trans = (bg_colour == RGB_TRANSPARENT);

  int zm_text_bitmask = 0x80;
  if (size == 2)
    zm_text_bitmask = 0x8000;

  while ( (index < text_len) && (line_len = strcspn( line, "\n" )) ) {

    unsigned int line_width = line_len * ZM_CHAR_WIDTH * size;

    unsigned int lo_line_x = coord.X();
    unsigned int lo_line_y = coord.Y() + (line_no * LINE_HEIGHT * size);

    unsigned int min_line_x = 0;
    unsigned int max_line_x = width - line_width;
    unsigned  int min_line_y = 0;
    unsigned int max_line_y = height - (LINE_HEIGHT * size);

    if ( lo_line_x > max_line_x )
      lo_line_x = max_line_x;
    if ( lo_line_x < min_line_x )
      lo_line_x = min_line_x;
    if ( lo_line_y > max_line_y )
      lo_line_y = max_line_y;
    if ( lo_line_y < min_line_y )
      lo_line_y = min_line_y;

    unsigned int hi_line_x = lo_line_x + line_width;
    unsigned int hi_line_y = lo_line_y + (LINE_HEIGHT * size);

    // Clip anything that runs off the right of the screen
    if ( hi_line_x > width )
      hi_line_x = width;
    if ( hi_line_y > height )
      hi_line_y = height;

    if ( colours == ZM_COLOUR_GRAY8 ) {
      unsigned char *ptr = &buffer[(lo_line_y*width)+lo_line_x];
      for ( unsigned int y = lo_line_y, r = 0; y < hi_line_y && r < (ZM_CHAR_HEIGHT * size); y++, r++, ptr += width ) {
        unsigned char *temp_ptr = ptr;
        for ( unsigned int x = lo_line_x, c = 0; x < hi_line_x && c < line_len; c++ ) {
          int f;
          if (size == 2)
            f = bigfontdata[(line[c] * ZM_CHAR_HEIGHT * size) + r];
          else
            f = fontdata[(line[c] * ZM_CHAR_HEIGHT) + r];
          for ( unsigned int i = 0; i < (ZM_CHAR_WIDTH * size) && x < hi_line_x; i++, x++, temp_ptr++ ) {
            if ( f & (zm_text_bitmask >> i) ) {
              if ( !fg_trans )
                *temp_ptr = fg_bw_col;
            } else if ( !bg_trans ) {
              *temp_ptr = bg_bw_col;
            }
          }
        }
      }
    } else if ( colours == ZM_COLOUR_RGB24 ) {
      unsigned int wc = width * colours;

      unsigned char *ptr = &buffer[((lo_line_y*width)+lo_line_x)*colours];
      for ( unsigned int y = lo_line_y, r = 0; y < hi_line_y && r < (ZM_CHAR_HEIGHT * size); y++, r++, ptr += wc ) {
        unsigned char *temp_ptr = ptr;
        for ( unsigned int x = lo_line_x, c = 0; x < hi_line_x && c < line_len; c++ ) {
          int f;
          if (size == 2)
            f = bigfontdata[(line[c] * ZM_CHAR_HEIGHT * size) + r];
          else
            f = fontdata[(line[c] * ZM_CHAR_HEIGHT) + r];
          for ( unsigned int i = 0; i < (ZM_CHAR_WIDTH * size) && x < hi_line_x; i++, x++, temp_ptr += colours ) {
            if ( f & (zm_text_bitmask >> i) ) {
              if ( !fg_trans ) {
                RED_PTR_RGBA(temp_ptr) = fg_r_col;
                GREEN_PTR_RGBA(temp_ptr) = fg_g_col;
                BLUE_PTR_RGBA(temp_ptr) = fg_b_col;
              }
            } else if ( !bg_trans ) {
              RED_PTR_RGBA(temp_ptr) = bg_r_col;
              GREEN_PTR_RGBA(temp_ptr) = bg_g_col;
              BLUE_PTR_RGBA(temp_ptr) = bg_b_col;
            }
          }
        }
      }
    } else if ( colours == ZM_COLOUR_RGB32 ) {
      unsigned int wc = width * colours;

      uint8_t *ptr = &buffer[((lo_line_y*width)+lo_line_x)<<2];
      for ( unsigned int y = lo_line_y, r = 0; y < hi_line_y && r < (ZM_CHAR_HEIGHT * size); y++, r++, ptr += wc ) {
        Rgb* temp_ptr = (Rgb*)ptr;
        for ( unsigned int x = lo_line_x, c = 0; x < hi_line_x && c < line_len; c++ ) {
          int f;
          if (size == 2)
            f = bigfontdata[(line[c] * ZM_CHAR_HEIGHT * size) + r];
          else
            f = fontdata[(line[c] * ZM_CHAR_HEIGHT) + r];
          for ( unsigned int i = 0; i < (ZM_CHAR_WIDTH * size) && x < hi_line_x; i++, x++, temp_ptr++ ) {
            if ( f & (zm_text_bitmask >> i) ) {
              if ( !fg_trans ) {
                *temp_ptr = fg_rgb_col;
              }
            } else if ( !bg_trans ) {
              *temp_ptr = bg_rgb_col;
            }
          }
        }
      }

    } else {
      Panic("Annotate called with unexpected colours: %d",colours);
      return;
    }

    index += line_len;
    while ( text[index] == '\n' ) {
      index++;
    }
    line = text+index;
    line_no++;
  }
}

void Image::Timestamp( const char *label, const time_t when, const Coord &coord, const int size ) {
  char time_text[64];
  strftime(time_text, sizeof(time_text), "%y/%m/%d %H:%M:%S", localtime(&when));
  if ( label ) {
    // Assume label is max 64, + ' - ' + 64 chars of time_text
    char text[132];
    snprintf(text, sizeof(text), "%s - %s", label, time_text);
    Annotate(text, coord, size);
  } else {
    Annotate(time_text, coord, size);
  }
}

/* RGB32 compatible: complete */
void Image::Colourise(const unsigned int p_reqcolours, const unsigned int p_reqsubpixelorder) {
  Debug(9, "Colourise: Req colours: %u Req subpixel order: %u Current colours: %u Current subpixel order: %u",p_reqcolours,p_reqsubpixelorder,colours,subpixelorder);

  if ( colours != ZM_COLOUR_GRAY8) {
    Warning("Target image is already colourised, colours: %u",colours);
    return;
  }

  if ( p_reqcolours == ZM_COLOUR_RGB32 ) {
    /* RGB32 */
    Rgb* new_buffer = (Rgb*)AllocBuffer(pixels*sizeof(Rgb));

    const uint8_t *psrc = buffer;
    Rgb* pdest = new_buffer;
    Rgb subpixel;
    Rgb newpixel;

    if ( p_reqsubpixelorder == ZM_SUBPIX_ORDER_ABGR || p_reqsubpixelorder == ZM_SUBPIX_ORDER_ARGB ) {
      /* ARGB\ABGR subpixel order. alpha byte is first (mem+0), so we need to shift the pixel left in the end */
      for ( unsigned int i=0; i < pixels; i++ ) {
        newpixel = subpixel = psrc[i];
        newpixel = (newpixel<<8) | subpixel;
        newpixel = (newpixel<<8) | subpixel;
        pdest[i] = (newpixel<<8);
      }
    } else {
      /* RGBA\BGRA subpixel order, alpha byte is last (mem+3) */
      for ( unsigned int i=0; i < pixels; i++ ) {
        newpixel = subpixel = psrc[i];
        newpixel = (newpixel<<8) | subpixel;
        newpixel = (newpixel<<8) | subpixel;
        pdest[i] = newpixel;
      }
    }

    /* Directly assign the new buffer and make sure it will be freed when not needed anymore */
    AssignDirect( width, height, p_reqcolours, p_reqsubpixelorder, (uint8_t*)new_buffer, pixels*4, ZM_BUFTYPE_ZM);

  } else if ( p_reqcolours == ZM_COLOUR_RGB24 ) {
    /* RGB24 */
    uint8_t *new_buffer = AllocBuffer(pixels*3);

    uint8_t *pdest = new_buffer;
    const uint8_t *psrc = buffer;

    for ( unsigned int i=0; i < (unsigned int)pixels; i++, pdest += 3 ) {
      RED_PTR_RGBA(pdest) = GREEN_PTR_RGBA(pdest) = BLUE_PTR_RGBA(pdest) = psrc[i];
    }

    /* Directly assign the new buffer and make sure it will be freed when not needed anymore */
    AssignDirect( width, height, p_reqcolours, p_reqsubpixelorder, new_buffer, pixels*3, ZM_BUFTYPE_ZM);
  } else {
    Error("Colourise called with unexpected colours: %d", colours);
    return;
  }
}

/* RGB32 compatible: complete */
void Image::DeColourise() {
  colours = ZM_COLOUR_GRAY8;
  subpixelorder = ZM_SUBPIX_ORDER_NONE;
  size = width * height;

  if ( colours == ZM_COLOUR_RGB32 && config.cpu_extensions && sseversion >= 35 ) {
    /* Use SSSE3 functions */
    switch (subpixelorder) {
      case ZM_SUBPIX_ORDER_BGRA:
        ssse3_convert_bgra_gray8(buffer,buffer,pixels);
        break;
      case ZM_SUBPIX_ORDER_ARGB:
        ssse3_convert_argb_gray8(buffer,buffer,pixels);
        break;
      case ZM_SUBPIX_ORDER_ABGR:
        ssse3_convert_abgr_gray8(buffer,buffer,pixels);
        break;
      case ZM_SUBPIX_ORDER_RGBA:
      default:
        ssse3_convert_rgba_gray8(buffer,buffer,pixels);
        break;
    }
  } else {
    /* Use standard functions */
    if ( colours == ZM_COLOUR_RGB32 ) {
      if ( pixels % 16 ) {
        switch (subpixelorder) {
          case ZM_SUBPIX_ORDER_BGRA:
            std_convert_bgra_gray8(buffer,buffer,pixels);
            break;
          case ZM_SUBPIX_ORDER_ARGB:
            std_convert_argb_gray8(buffer,buffer,pixels);
            break;
          case ZM_SUBPIX_ORDER_ABGR:
            std_convert_abgr_gray8(buffer,buffer,pixels);
            break;
          case ZM_SUBPIX_ORDER_RGBA:
          default:
            std_convert_rgba_gray8(buffer,buffer,pixels);
            break;
        }
      } else {
        switch (subpixelorder) {
          case ZM_SUBPIX_ORDER_BGRA:
            fast_convert_bgra_gray8(buffer,buffer,pixels);
            break;
          case ZM_SUBPIX_ORDER_ARGB:
            fast_convert_argb_gray8(buffer,buffer,pixels);
            break;
          case ZM_SUBPIX_ORDER_ABGR:
            fast_convert_abgr_gray8(buffer,buffer,pixels);
            break;
          case ZM_SUBPIX_ORDER_RGBA:
          default:
            fast_convert_rgba_gray8(buffer,buffer,pixels);
            break;
        }
      } // end if pixels % 16 to use loop unrolled functions
    } else {
      /* Assume RGB24 */
      if ( pixels % 12 ) {
        switch (subpixelorder) {
          case ZM_SUBPIX_ORDER_BGR:
            std_convert_bgr_gray8(buffer,buffer,pixels);
            break;
          case ZM_SUBPIX_ORDER_RGB:
          default:
            std_convert_rgb_gray8(buffer,buffer,pixels);
            break;
        }
      } else {
        switch (subpixelorder) {
          case ZM_SUBPIX_ORDER_BGR:
            fast_convert_bgr_gray8(buffer,buffer,pixels);
            break;
          case ZM_SUBPIX_ORDER_RGB:
          default:
            fast_convert_rgb_gray8(buffer,buffer,pixels);
            break;
        }
      } // end if pixels % 12 to use loop unrolled functions
    }
  }
}

/* RGB32 compatible: complete */
void Image::Fill( Rgb colour, const Box *limits ) {
  if ( !(colours == ZM_COLOUR_GRAY8 || colours == ZM_COLOUR_RGB24 || colours == ZM_COLOUR_RGB32 ) ) {
    Panic("Attempt to fill image with unexpected colours %d", colours);
  }

  /* Convert the colour's RGBA subpixel order into the image's subpixel order */
  colour = rgb_convert(colour,subpixelorder);

  unsigned int lo_x = limits?limits->Lo().X():0;
  unsigned int lo_y = limits?limits->Lo().Y():0;
  unsigned int hi_x = limits?limits->Hi().X():width-1;
  unsigned int hi_y = limits?limits->Hi().Y():height-1;
  if ( colours == ZM_COLOUR_GRAY8 ) {
    for ( unsigned int y = lo_y; y <= hi_y; y++ ) {
      unsigned char *p = &buffer[(y*width)+lo_x];
      for ( unsigned int x = lo_x; x <= hi_x; x++, p++) {
        *p = colour;
      }
    }
  } else if ( colours == ZM_COLOUR_RGB24 ) {
    for ( unsigned int y = lo_y; y <= hi_y; y++ ) {
      unsigned char *p = &buffer[colours*((y*width)+lo_x)];
      for ( unsigned int x = lo_x; x <= hi_x; x++, p += 3) {
        RED_PTR_RGBA(p) = RED_VAL_RGBA(colour);
        GREEN_PTR_RGBA(p) = GREEN_VAL_RGBA(colour);
        BLUE_PTR_RGBA(p) = BLUE_VAL_RGBA(colour);
      }
    }
  } else if ( colours == ZM_COLOUR_RGB32 ) /* RGB32 */ {
    for ( unsigned int y = lo_y; y <= (unsigned int)hi_y; y++ ) {
      Rgb *p = (Rgb*)&buffer[((y*width)+lo_x)<<2];

      for ( unsigned int x = lo_x; x <= (unsigned int)hi_x; x++, p++) {
        /* Fast, copies the entire pixel in a single pass */
        *p = colour;
      }
    }
  }
}

/* RGB32 compatible: complete */
void Image::Fill( Rgb colour, int density, const Box *limits ) {
  /* Allow the faster version to be used if density is not used (density=1) */
  if ( density <= 1 )
    return Fill(colour,limits);

  if ( !(colours == ZM_COLOUR_GRAY8 || colours == ZM_COLOUR_RGB24 || colours == ZM_COLOUR_RGB32  ) ) {
    Panic("Attempt to fill image with unexpected colours %d", colours);
  }

  /* Convert the colour's RGBA subpixel order into the image's subpixel order */
  colour = rgb_convert(colour,subpixelorder);

  unsigned int lo_x = limits?limits->Lo().X():0;
  unsigned int lo_y = limits?limits->Lo().Y():0;
  unsigned int hi_x = limits?limits->Hi().X():width-1;
  unsigned int hi_y = limits?limits->Hi().Y():height-1;
  if ( colours == ZM_COLOUR_GRAY8 ) {
    for ( unsigned int y = lo_y; y <= hi_y; y++ ) {
      unsigned char *p = &buffer[(y*width)+lo_x];
      for ( unsigned int x = lo_x; x <= hi_x; x++, p++) {
        if ( ( x == lo_x || x == hi_x || y == lo_y || y == hi_y ) || (!(x%density) && !(y%density) ) )
          *p = colour;
      }
    }
  } else if ( colours == ZM_COLOUR_RGB24 ) {
    for ( unsigned int y = lo_y; y <= hi_y; y++ ) {
      unsigned char *p = &buffer[colours*((y*width)+lo_x)];
      for ( unsigned int x = lo_x; x <= hi_x; x++, p += 3) {
        if ( ( x == lo_x || x == hi_x || y == lo_y || y == hi_y ) || (!(x%density) && !(y%density) ) ) {
          RED_PTR_RGBA(p) = RED_VAL_RGBA(colour);
          GREEN_PTR_RGBA(p) = GREEN_VAL_RGBA(colour);
          BLUE_PTR_RGBA(p) = BLUE_VAL_RGBA(colour);
        }
      }
    }
  } else if ( colours == ZM_COLOUR_RGB32 ) /* RGB32 */ {
    for ( unsigned int y = lo_y; y <= hi_y; y++ ) {
      Rgb* p = (Rgb*)&buffer[((y*width)+lo_x)<<2];

      for ( unsigned int x = lo_x; x <= hi_x; x++, p++) {
        if ( ( x == lo_x || x == hi_x || y == lo_y || y == hi_y ) || (!(x%density) && !(y%density) ) )
          /* Fast, copies the entire pixel in a single pass */
          *p = colour;
      }
    }
  }
}

/* RGB32 compatible: complete */
void Image::Outline( Rgb colour, const Polygon &polygon ) {
  if ( !(colours == ZM_COLOUR_GRAY8 || colours == ZM_COLOUR_RGB24 || colours == ZM_COLOUR_RGB32 ) ) {
    Panic("Attempt to outline image with unexpected colours %d", colours);
  }

  /* Convert the colour's RGBA subpixel order into the image's subpixel order */
  colour = rgb_convert(colour,subpixelorder);

  int n_coords = polygon.getNumCoords();
  for ( int j = 0, i = n_coords-1; j < n_coords; i = j++ ) {
    const Coord &p1 = polygon.getCoord( i );
    const Coord &p2 = polygon.getCoord( j );

    int x1 = p1.X();
    int x2 = p2.X();
    int y1 = p1.Y();
    int y2 = p2.Y();

    double dx = x2 - x1;
    double dy = y2 - y1;

    double grad;

    //Debug( 9, "dx: %.2lf, dy: %.2lf", dx, dy );
    if ( fabs(dx) <= fabs(dy) ) {
      //Debug( 9, "dx <= dy" );
      if ( y1 != y2 )
        grad = dx/dy;
      else
        grad = width;

      double x;
      int y, yinc = (y1<y2)?1:-1;
      grad *= yinc;
      if ( colours == ZM_COLOUR_GRAY8 ) {
        //Debug( 9, "x1:%d, x2:%d, y1:%d, y2:%d, gr:%.2f", x1, x2, y1, y2, grad );
        for ( x = x1, y = y1; y != y2; y += yinc, x += grad ) {
          //Debug( 9, "x:%.2f, y:%d", x, y );
          buffer[(y*width)+int(round(x))] = colour;
        }
      } else if ( colours == ZM_COLOUR_RGB24 ) {
        for ( x = x1, y = y1; y != y2; y += yinc, x += grad ) {
          unsigned char *p = &buffer[colours*((y*width)+int(round(x)))];
          RED_PTR_RGBA(p) = RED_VAL_RGBA(colour);
          GREEN_PTR_RGBA(p) = GREEN_VAL_RGBA(colour);
          BLUE_PTR_RGBA(p) = BLUE_VAL_RGBA(colour);
        }
      } else if ( colours == ZM_COLOUR_RGB32 ) {
        for ( x = x1, y = y1; y != y2; y += yinc, x += grad ) {
          *(Rgb*)(buffer+(((y*width)+int(round(x)))<<2)) = colour;
        }
      }
    } else {
      //Debug( 9, "dx > dy" );
      if ( x1 != x2 )
        grad = dy/dx;
      else
        grad = height;
      //Debug( 9, "grad: %.2lf", grad );

      double y;
      int x, xinc = (x1<x2)?1:-1;
      grad *= xinc;
      if ( colours == ZM_COLOUR_GRAY8 ) {
        //Debug( 9, "x1:%d, x2:%d, y1:%d, y2:%d, gr:%.2lf", x1, x2, y1, y2, grad );
        for ( y = y1, x = x1; x != x2; x += xinc, y += grad ) {
          //Debug( 9, "x:%d, y:%.2f", x, y );
          buffer[(int(round(y))*width)+x] = colour;
        }
      } else if ( colours == ZM_COLOUR_RGB24 ) {
        for ( y = y1, x = x1; x != x2; x += xinc, y += grad ) {
          unsigned char *p = &buffer[colours*((int(round(y))*width)+x)];
          RED_PTR_RGBA(p) = RED_VAL_RGBA(colour);
          GREEN_PTR_RGBA(p) = GREEN_VAL_RGBA(colour);
          BLUE_PTR_RGBA(p) = BLUE_VAL_RGBA(colour);
        }
      } else if ( colours == ZM_COLOUR_RGB32 ) {
        for ( y = y1, x = x1; x != x2; x += xinc, y += grad ) {
          *(Rgb*)(buffer+(((int(round(y))*width)+x)<<2)) = colour;
        }
      }
    }
  } // end foreach coordinate in the polygon
}

/* RGB32 compatible: complete */
void Image::Fill( Rgb colour, int density, const Polygon &polygon ) {
  if ( !(colours == ZM_COLOUR_GRAY8 || colours == ZM_COLOUR_RGB24 || colours == ZM_COLOUR_RGB32 ) ) {
    Panic( "Attempt to fill image with unexpected colours %d", colours );
  }

  /* Convert the colour's RGBA subpixel order into the image's subpixel order */
  colour = rgb_convert(colour,subpixelorder);

  int n_coords = polygon.getNumCoords();
  int n_global_edges = 0;
  Edge global_edges[n_coords];
  for ( int j = 0, i = n_coords-1; j < n_coords; i = j++ ) {
    const Coord &p1 = polygon.getCoord( i );
    const Coord &p2 = polygon.getCoord( j );

    int x1 = p1.X();
    int x2 = p2.X();
    int y1 = p1.Y();
    int y2 = p2.Y();

    //Debug( 9, "x1:%d,y1:%d x2:%d,y2:%d", x1, y1, x2, y2 );
    if ( y1 == y2 )
      continue;

    double dx = x2 - x1;
    double dy = y2 - y1;

    global_edges[n_global_edges].min_y = y1<y2?y1:y2;
    global_edges[n_global_edges].max_y = y1<y2?y2:y1;
    global_edges[n_global_edges].min_x = y1<y2?x1:x2;
    global_edges[n_global_edges]._1_m = dx/dy;
    n_global_edges++;
  }
  qsort( global_edges, n_global_edges, sizeof(*global_edges), Edge::CompareYX );

#ifndef ZM_DBG_OFF
  if ( logLevel() >= Logger::DEBUG9 ) {
    for ( int i = 0; i < n_global_edges; i++ ) {
      Debug( 9, "%d: min_y: %d, max_y:%d, min_x:%.2f, 1/m:%.2f", i, global_edges[i].min_y, global_edges[i].max_y, global_edges[i].min_x, global_edges[i]._1_m );
    }
  }
#endif

  int n_active_edges = 0;
  Edge active_edges[n_global_edges];
  int y = global_edges[0].min_y;
  do {
    for ( int i = 0; i < n_global_edges; i++ ) {
      if ( global_edges[i].min_y == y ) {
        Debug(9, "Moving global edge");
        active_edges[n_active_edges++] = global_edges[i];
        if ( i < (n_global_edges-1) ) {
          //memcpy( &global_edges[i], &global_edges[i+1], sizeof(*global_edges)*(n_global_edges-i) );
          memmove( &global_edges[i], &global_edges[i+1], sizeof(*global_edges)*(n_global_edges-i) );
          i--;
        }
        n_global_edges--;
      } else {
        break;
      }
    }
    qsort( active_edges, n_active_edges, sizeof(*active_edges), Edge::CompareX );
#ifndef ZM_DBG_OFF
    if ( logLevel() >= Logger::DEBUG9 ) {
      for ( int i = 0; i < n_active_edges; i++ ) {
        Debug( 9, "%d - %d: min_y: %d, max_y:%d, min_x:%.2f, 1/m:%.2f", y, i, active_edges[i].min_y, active_edges[i].max_y, active_edges[i].min_x, active_edges[i]._1_m );
      }
    }
#endif
    if ( !(y%density) ) {
      //Debug( 9, "%d", y );
      for ( int i = 0; i < n_active_edges; ) {
        int lo_x = int(round(active_edges[i++].min_x));
        int hi_x = int(round(active_edges[i++].min_x));
        if ( colours == ZM_COLOUR_GRAY8 ) {
          unsigned char *p = &buffer[(y*width)+lo_x];
          for ( int x = lo_x; x <= hi_x; x++, p++) {
            if ( !(x%density) ) {
              //Debug( 9, " %d", x );
              *p = colour;
            }
          }
        } else if ( colours == ZM_COLOUR_RGB24 ) {
          unsigned char *p = &buffer[colours*((y*width)+lo_x)];
          for ( int x = lo_x; x <= hi_x; x++, p += 3) {
            if ( !(x%density) ) {
              RED_PTR_RGBA(p) = RED_VAL_RGBA(colour);
              GREEN_PTR_RGBA(p) = GREEN_VAL_RGBA(colour);
              BLUE_PTR_RGBA(p) = BLUE_VAL_RGBA(colour);
            }
          }
        } else if( colours == ZM_COLOUR_RGB32 ) {
          Rgb *p = (Rgb*)&buffer[((y*width)+lo_x)<<2];
          for ( int x = lo_x; x <= hi_x; x++, p++) {
            if ( !(x%density) ) {
              /* Fast, copies the entire pixel in a single pass */
              *p = colour;
            }
          }
        }
      }
    }
    y++;
    for ( int i = n_active_edges-1; i >= 0; i-- ) {
      if ( y >= active_edges[i].max_y ) {
        // Or >= as per sheets
        Debug( 9, "Deleting active_edge" );
        if ( i < (n_active_edges-1) ) {
          //memcpy( &active_edges[i], &active_edges[i+1], sizeof(*active_edges)*(n_active_edges-i) );
          memmove( &active_edges[i], &active_edges[i+1], sizeof(*active_edges)*(n_active_edges-i) );
        }
        n_active_edges--;
      } else {
        active_edges[i].min_x += active_edges[i]._1_m;
      }
    }
  } while ( n_global_edges || n_active_edges );
}

void Image::Fill( Rgb colour, const Polygon &polygon ) {
  Fill( colour, 1, polygon );
}

/* RGB32 compatible: complete */
void Image::Rotate( int angle ) {

  angle %= 360;

  if ( !angle ) {
    return;
  }
  if ( angle%90 ) {
    return;
  }

  unsigned int new_height = height;
  unsigned int new_width = width;
  uint8_t* rotate_buffer = AllocBuffer(size);

  switch( angle ) {
    case 90 :
      {
        new_height = width;
        new_width = height;

        unsigned int line_bytes = new_width*colours;
        unsigned char *s_ptr = buffer;

        if ( colours == ZM_COLOUR_GRAY8 ) {
          for ( unsigned int i = new_width; i > 0; i-- ) {
            unsigned char *d_ptr = rotate_buffer+(i-1);
            for ( unsigned int j = new_height; j > 0; j-- ) {
              *d_ptr = *s_ptr++;
              d_ptr += line_bytes;
            }
          }
        } else if ( colours == ZM_COLOUR_RGB32 ) {
          Rgb* s_rptr = (Rgb*)s_ptr;
          for ( unsigned int i = new_width; i > 0; i-- ) {
            Rgb* d_rptr = (Rgb*)(rotate_buffer+((i-1)<<2));
            for ( unsigned int j = new_height; j > 0; j-- ) {
              *d_rptr = *s_rptr++;
              d_rptr += new_width;
            }
          }
        } else /* Assume RGB24 */ {
          for ( unsigned int i = new_width; i > 0; i-- ) {
            unsigned char *d_ptr = rotate_buffer+((i-1)*3);
            for ( unsigned int j = new_height; j > 0; j-- ) {
              *d_ptr = *s_ptr++;
              *(d_ptr+1) = *s_ptr++;
              *(d_ptr+2) = *s_ptr++;
              d_ptr += line_bytes;
            }
          }
        }
        break;
      }
    case 180 :
      {
        unsigned char *s_ptr = buffer+size;
        unsigned char *d_ptr = rotate_buffer;

        if ( colours == ZM_COLOUR_GRAY8 ) {
          while( s_ptr > buffer ) {
            s_ptr--;
            *d_ptr++ = *s_ptr;
          }
        } else if ( colours == ZM_COLOUR_RGB32 ) {
          Rgb* s_rptr = (Rgb*)s_ptr;
          Rgb* d_rptr = (Rgb*)d_ptr;
          while( s_rptr > (Rgb*)buffer ) {
            s_rptr--;
            *d_rptr++ = *s_rptr;
          }
        } else /* Assume RGB24 */ {
          while( s_ptr > buffer ) {
            s_ptr -= 3;
            *d_ptr++ = *s_ptr;
            *d_ptr++ = *(s_ptr+1);
            *d_ptr++ = *(s_ptr+2);
          }
        }
        break;
      }
    case 270 :
      {
        new_height = width;
        new_width = height;

        unsigned int line_bytes = new_width*colours;
        unsigned char *s_ptr = buffer+size;

        if ( colours == ZM_COLOUR_GRAY8 ) {
          for ( unsigned int i = new_width; i > 0; i-- ) {
            unsigned char *d_ptr = rotate_buffer+(i-1);
            for ( unsigned int j = new_height; j > 0; j-- ) {
              s_ptr--;
              *d_ptr = *s_ptr;
              d_ptr += line_bytes;
            }
          }
        } else if ( colours == ZM_COLOUR_RGB32 ) {
          Rgb* s_rptr = (Rgb*)s_ptr;
          for ( unsigned int i = new_width; i > 0; i-- ) {
            Rgb* d_rptr = (Rgb*)(rotate_buffer+((i-1)<<2));
            for ( unsigned int j = new_height; j > 0; j-- ) {
              s_rptr--;
              *d_rptr = *s_rptr;
              d_rptr += new_width;
            }
          }
        } else /* Assume RGB24 */ {
          for ( unsigned int i = new_width; i > 0; i-- ) {
            unsigned char *d_ptr = rotate_buffer+((i-1)*3);
            for ( unsigned int j = new_height; j > 0; j-- ) {
              *(d_ptr+2) = *(--s_ptr);
              *(d_ptr+1) = *(--s_ptr);
              *d_ptr = *(--s_ptr);
              d_ptr += line_bytes;
            }
          }
        }
        break;
      }
  }

  AssignDirect( new_width, new_height, colours, subpixelorder, rotate_buffer, size, ZM_BUFTYPE_ZM);
}

/* RGB32 compatible: complete */
void Image::Flip( bool leftright ) {
  uint8_t* flip_buffer = AllocBuffer(size);

  unsigned int line_bytes = width*colours;
  unsigned int line_bytes2 = 2*line_bytes;
  if ( leftright ) {
    // Horizontal flip, left to right
    unsigned char *s_ptr = buffer+line_bytes;
    unsigned char *d_ptr = flip_buffer;
    unsigned char *max_d_ptr = flip_buffer + size;

    if ( colours == ZM_COLOUR_GRAY8 ) {
      while( d_ptr < max_d_ptr ) {
        for ( unsigned int j = 0; j < width; j++ ) {
          s_ptr--;
          *d_ptr++ = *s_ptr;
        }
        s_ptr += line_bytes2;
      }
    } else if ( colours == ZM_COLOUR_RGB32 ) {
      Rgb* s_rptr = (Rgb*)s_ptr;
      Rgb* d_rptr = (Rgb*)flip_buffer;
      Rgb* max_d_rptr = (Rgb*)max_d_ptr;
      while( d_rptr < max_d_rptr ) {
        for ( unsigned int j = 0; j < width; j++ ) {
          s_rptr--;
          *d_rptr++ = *s_rptr;
        }
        s_rptr += width * 2;
      }
    } else /* Assume RGB24 */ {
      while( d_ptr < max_d_ptr ) {
        for ( unsigned int j = 0; j < width; j++ ) {
          s_ptr -= 3;
          *d_ptr++ = *s_ptr;
          *d_ptr++ = *(s_ptr+1);
          *d_ptr++ = *(s_ptr+2);
        }
        s_ptr += line_bytes2;
      }
    }
  } else {
    // Vertical flip, top to bottom
    unsigned char *s_ptr = buffer+(height*line_bytes);
    unsigned char *d_ptr = flip_buffer;

    while( s_ptr > buffer ) {
      s_ptr -= line_bytes;
      memcpy( d_ptr, s_ptr, line_bytes );
      d_ptr += line_bytes;
    }
  }

  AssignDirect( width, height, colours, subpixelorder, flip_buffer, size, ZM_BUFTYPE_ZM);

}

void Image::Scale( unsigned int factor ) {
  if ( !factor ) {
    Error( "Bogus scale factor %d found", factor );
    return;
  }
  if ( factor == ZM_SCALE_BASE ) {
    return;
  }

  unsigned int new_width = (width*factor)/ZM_SCALE_BASE;
  unsigned int new_height = (height*factor)/ZM_SCALE_BASE;

  size_t scale_buffer_size = (new_width+1) * (new_height+1) * colours;

  uint8_t* scale_buffer = AllocBuffer(scale_buffer_size);

  if ( factor > ZM_SCALE_BASE ) {
    unsigned char *pd = scale_buffer;
    unsigned int wc = width*colours;
    unsigned int nwc = new_width*colours;
    unsigned int h_count = ZM_SCALE_BASE/2;
    unsigned int last_h_index = 0;
    unsigned int last_w_index = 0;
    unsigned int h_index;
    for ( unsigned int y = 0; y < height; y++ ) {
      unsigned char *ps = &buffer[y*wc];
      unsigned int w_count = ZM_SCALE_BASE/2;
      unsigned int w_index;
      last_w_index = 0;
      for ( unsigned int x = 0; x < width; x++ ) {
        w_count += factor;
        w_index = w_count/ZM_SCALE_BASE;
        for (unsigned int f = last_w_index; f < w_index; f++ ) {
          for ( unsigned int c = 0; c < colours; c++ ) {
            *pd++ = *(ps+c);
          }
        }
        ps += colours;
        last_w_index = w_index;
      }
      h_count += factor;
      h_index = h_count/ZM_SCALE_BASE;
      for ( unsigned int f = last_h_index+1; f < h_index; f++ ) {
        memcpy( pd, pd-nwc, nwc );
        pd += nwc;
      }
      last_h_index = h_index;
    }
    new_width = last_w_index;
    new_height = last_h_index;
  } else {
    unsigned char *pd = scale_buffer;
    unsigned int wc = width*colours;
    unsigned int xstart = factor/2;
    unsigned int ystart = factor/2;
    unsigned int h_count = ystart;
    unsigned int last_h_index = 0;
    unsigned int last_w_index = 0;
    unsigned int h_index;
    for ( unsigned int y = 0; y < (unsigned int)height; y++ ) {
      h_count += factor;
      h_index = h_count/ZM_SCALE_BASE;
      if ( h_index > last_h_index ) {
        unsigned int w_count = xstart;
        unsigned int w_index;
        last_w_index = 0;

        unsigned char *ps = &buffer[y*wc];
        for ( unsigned int x = 0; x < (unsigned int)width; x++ ) {
          w_count += factor;
          w_index = w_count/ZM_SCALE_BASE;

          if ( w_index > last_w_index ) {
            for ( unsigned int c = 0; c < colours; c++ ) {
              *pd++ = *ps++;
            }
          } else {
            ps += colours;
          }
          last_w_index = w_index;
        }
      }
      last_h_index = h_index;
    }
    new_width = last_w_index;
    new_height = last_h_index;
  }

  AssignDirect( new_width, new_height, colours, subpixelorder, scale_buffer, scale_buffer_size, ZM_BUFTYPE_ZM);

}

void Image::Deinterlace_Discard() {
  /* Simple deinterlacing. Copy the even lines into the odd lines */

  if ( colours == ZM_COLOUR_GRAY8 ) {
    const uint8_t *psrc;
    uint8_t *pdest;
    for (unsigned int y = 0; y < (unsigned int)height; y += 2) {
      psrc = buffer + (y * width);
      pdest = buffer + ((y+1) * width);
      for (unsigned int x = 0; x < (unsigned int)width; x++) {
        *pdest++ = *psrc++;
      }
    }
  } else if ( colours == ZM_COLOUR_RGB24 ) {
    const uint8_t *psrc;
    uint8_t *pdest;
    for (unsigned int y = 0; y < (unsigned int)height; y += 2) {
      psrc = buffer + ((y * width) * 3);
      pdest = buffer + (((y+1) * width) * 3);
      for (unsigned int x = 0; x < (unsigned int)width; x++) {
        *pdest++ = *psrc++;
        *pdest++ = *psrc++;
        *pdest++ = *psrc++;
      }
    }
  } else if ( colours == ZM_COLOUR_RGB32 ) {
    const Rgb *psrc;
    Rgb *pdest;
    for (unsigned int y = 0; y < (unsigned int)height; y += 2) {
      psrc = (Rgb*)(buffer + ((y * width) << 2));
      pdest = (Rgb*)(buffer + (((y+1) * width) << 2));
      for (unsigned int x = 0; x < (unsigned int)width; x++) {
        *pdest++ = *psrc++;
      }
    }
  } else {
    Error("Deinterlace called with unexpected colours: %d", colours);
  }

}

void Image::Deinterlace_Linear() {
  /* Simple deinterlacing. The odd lines are average of the line above and line below */

  const uint8_t *pbelow, *pabove;
  uint8_t *pcurrent;

  if ( colours == ZM_COLOUR_GRAY8 ) {
    for (unsigned int y = 1; y < (unsigned int)(height-1); y += 2) {
      pabove = buffer + ((y-1) * width);
      pbelow = buffer + ((y+1) * width);
      pcurrent = buffer + (y * width);
      for (unsigned int x = 0; x < (unsigned int)width; x++) {
        *pcurrent++ = (*pabove++ + *pbelow++) >> 1;
      }
    }
    /* Special case for the last line */
    pcurrent = buffer + ((height-1) * width);
    pabove = buffer + ((height-2) * width);
    for (unsigned int x = 0; x < (unsigned int)width; x++) {
      *pcurrent++ = *pabove++;
    }
  } else if ( colours == ZM_COLOUR_RGB24 ) {
    for (unsigned int y = 1; y < (unsigned int)(height-1); y += 2) {
      pabove = buffer + (((y-1) * width) * 3);
      pbelow = buffer + (((y+1) * width) * 3);
      pcurrent = buffer + ((y * width) * 3);
      for (unsigned int x = 0; x < (unsigned int)width; x++) {
        *pcurrent++ = (*pabove++ + *pbelow++) >> 1;
        *pcurrent++ = (*pabove++ + *pbelow++) >> 1;
        *pcurrent++ = (*pabove++ + *pbelow++) >> 1;
      }
    }
    /* Special case for the last line */
    pcurrent = buffer + (((height-1) * width) * 3);
    pabove = buffer + (((height-2) * width) * 3);
    for (unsigned int x = 0; x < (unsigned int)width; x++) {
      *pcurrent++ = *pabove++;
      *pcurrent++ = *pabove++;
      *pcurrent++ = *pabove++;
    }
  } else if ( colours == ZM_COLOUR_RGB32 ) {
    for (unsigned int y = 1; y < (unsigned int)(height-1); y += 2) {
      pabove = buffer + (((y-1) * width) << 2);
      pbelow = buffer + (((y+1) * width) << 2);
      pcurrent = buffer + ((y * width) << 2);
      for (unsigned int x = 0; x < (unsigned int)width; x++) {
        *pcurrent++ = (*pabove++ + *pbelow++) >> 1;
        *pcurrent++ = (*pabove++ + *pbelow++) >> 1;
        *pcurrent++ = (*pabove++ + *pbelow++) >> 1;
        *pcurrent++ = (*pabove++ + *pbelow++) >> 1;
      }
    }
    /* Special case for the last line */
    pcurrent = buffer + (((height-1) * width) << 2);
    pabove = buffer + (((height-2) * width) << 2);
    for (unsigned int x = 0; x < (unsigned int)width; x++) {
      *pcurrent++ = *pabove++;
      *pcurrent++ = *pabove++;
      *pcurrent++ = *pabove++;
      *pcurrent++ = *pabove++;
    }
  } else {
    Error("Deinterlace called with unexpected colours: %d", colours);
  }

}

void Image::Deinterlace_Blend() {
  /* Simple deinterlacing. Blend the fields together. 50% blend */

  uint8_t *pabove, *pcurrent;

  if ( colours == ZM_COLOUR_GRAY8 ) {
    for (unsigned int y = 1; y < (unsigned int)height; y += 2) {
      pabove = buffer + ((y-1) * width);
      pcurrent = buffer + (y * width);
      for (unsigned int x = 0; x < (unsigned int)width; x++) {
        *pabove = (*pabove + *pcurrent) >> 1;
        *pcurrent++ = *pabove++;
      }
    }
  } else if ( colours == ZM_COLOUR_RGB24 ) {
    for (unsigned int y = 1; y < (unsigned int)height; y += 2) {
      pabove = buffer + (((y-1) * width) * 3);
      pcurrent = buffer + ((y * width) * 3);
      for (unsigned int x = 0; x < (unsigned int)width; x++) {
        *pabove = (*pabove + *pcurrent) >> 1;
        *pcurrent++ = *pabove++;
        *pabove = (*pabove + *pcurrent) >> 1;
        *pcurrent++ = *pabove++;
        *pabove = (*pabove + *pcurrent) >> 1;
        *pcurrent++ = *pabove++;
      }
    }
  } else if ( colours == ZM_COLOUR_RGB32 ) {
    for (unsigned int y = 1; y < (unsigned int)height; y += 2) {
      pabove = buffer + (((y-1) * width) << 2);
      pcurrent = buffer + ((y * width) << 2);
      for (unsigned int x = 0; x < (unsigned int)width; x++) {
        *pabove = (*pabove + *pcurrent) >> 1;
        *pcurrent++ = *pabove++;
        *pabove = (*pabove + *pcurrent) >> 1;
        *pcurrent++ = *pabove++;
        *pabove = (*pabove + *pcurrent) >> 1;
        *pcurrent++ = *pabove++;
        *pabove = (*pabove + *pcurrent) >> 1;
        *pcurrent++ = *pabove++;
      }
    }
  } else {
    Error("Deinterlace called with unexpected colours: %d", colours);
  }

}

void Image::Deinterlace_Blend_CustomRatio(int divider) {
  /* Simple deinterlacing. Blend the fields together at a custom ratio. */
  /* 1 = 50% blending   */
  /* 2 = 25% blending   */
  /* 3 = 12.% blending  */
  /* 4 = 6.25% blending */

  uint8_t *pabove, *pcurrent;
  uint8_t subpix1, subpix2;

  if ( divider < 1 || divider > 4 ) {
    Error("Deinterlace called with invalid blend ratio");
  }

  if ( colours == ZM_COLOUR_GRAY8 ) {
    for (unsigned int y = 1; y < (unsigned int)height; y += 2) {
      pabove = buffer + ((y-1) * width);
      pcurrent = buffer + (y * width);
      for (unsigned int x = 0; x < (unsigned int)width; x++) {
        subpix1 = ((*pabove - *pcurrent)>>divider) + *pcurrent;
        subpix2 = ((*pcurrent - *pabove)>>divider) + *pabove;
        *pcurrent++ = subpix1;
        *pabove++ = subpix2;
      }
    }
  } else if ( colours == ZM_COLOUR_RGB24 ) {
    for (unsigned int y = 1; y < (unsigned int)height; y += 2) {
      pabove = buffer + (((y-1) * width) * 3);
      pcurrent = buffer + ((y * width) * 3);
      for (unsigned int x = 0; x < (unsigned int)width; x++) {
        subpix1 = ((*pabove - *pcurrent)>>divider) + *pcurrent;
        subpix2 = ((*pcurrent - *pabove)>>divider) + *pabove;
        *pcurrent++ = subpix1;
        *pabove++ = subpix2;
        subpix1 = ((*pabove - *pcurrent)>>divider) + *pcurrent;
        subpix2 = ((*pcurrent - *pabove)>>divider) + *pabove;
        *pcurrent++ = subpix1;
        *pabove++ = subpix2;
        subpix1 = ((*pabove - *pcurrent)>>divider) + *pcurrent;
        subpix2 = ((*pcurrent - *pabove)>>divider) + *pabove;
        *pcurrent++ = subpix1;
        *pabove++ = subpix2;
      }
    }
  } else if ( colours == ZM_COLOUR_RGB32 ) {
    for (unsigned int y = 1; y < (unsigned int)height; y += 2) {
      pabove = buffer + (((y-1) * width) << 2);
      pcurrent = buffer + ((y * width) << 2);
      for (unsigned int x = 0; x < (unsigned int)width; x++) {
        subpix1 = ((*pabove - *pcurrent)>>divider) + *pcurrent;
        subpix2 = ((*pcurrent - *pabove)>>divider) + *pabove;
        *pcurrent++ = subpix1;
        *pabove++ = subpix2;
        subpix1 = ((*pabove - *pcurrent)>>divider) + *pcurrent;
        subpix2 = ((*pcurrent - *pabove)>>divider) + *pabove;
        *pcurrent++ = subpix1;
        *pabove++ = subpix2;
        subpix1 = ((*pabove - *pcurrent)>>divider) + *pcurrent;
        subpix2 = ((*pcurrent - *pabove)>>divider) + *pabove;
        *pcurrent++ = subpix1;
        *pabove++ = subpix2;
        subpix1 = ((*pabove - *pcurrent)>>divider) + *pcurrent;
        subpix2 = ((*pcurrent - *pabove)>>divider) + *pabove;
        *pcurrent++ = subpix1;
        *pabove++ = subpix2;
      }
    }
  } else {
    Error("Deinterlace called with unexpected colours: %d", colours);
  }

}


void Image::Deinterlace_4Field(const Image* next_image, unsigned int threshold)
{
  if ( !(width == next_image->width && height == next_image->height && colours == next_image->colours && subpixelorder == next_image->subpixelorder) )
  {
    Panic( "Attempt to deinterlace different sized images, expected %dx%dx%d %d, got %dx%dx%d %d", width, height, colours, subpixelorder, next_image->width, next_image->height, next_image->colours, next_image->subpixelorder);
  }

  switch(colours) {
    case ZM_COLOUR_RGB24:
      {
        if(subpixelorder == ZM_SUBPIX_ORDER_BGR) {
          /* BGR subpixel order */
          std_deinterlace_4field_bgr(buffer, next_image->buffer, threshold, width, height);
        } else {
          /* Assume RGB subpixel order */
          std_deinterlace_4field_rgb(buffer, next_image->buffer, threshold, width, height);
        }
        break;
      }
    case ZM_COLOUR_RGB32:
      {
        if(subpixelorder == ZM_SUBPIX_ORDER_ARGB) {
          /* ARGB subpixel order */
          (*fptr_deinterlace_4field_argb)(buffer, next_image->buffer, threshold, width, height);
        } else if(subpixelorder == ZM_SUBPIX_ORDER_ABGR) {
          /* ABGR subpixel order */
          (*fptr_deinterlace_4field_abgr)(buffer, next_image->buffer, threshold, width, height);
        } else if(subpixelorder == ZM_SUBPIX_ORDER_BGRA) {
          /* BGRA subpixel order */
          (*fptr_deinterlace_4field_bgra)(buffer, next_image->buffer, threshold, width, height);
        } else {
          /* Assume RGBA subpixel order */
          (*fptr_deinterlace_4field_rgba)(buffer, next_image->buffer, threshold, width, height);
        }
        break;
      }
    case ZM_COLOUR_GRAY8:
      (*fptr_deinterlace_4field_gray8)(buffer, next_image->buffer, threshold, width, height);
      break;
    default:
      Panic("Deinterlace_4Field called with unexpected colours: %d",colours);
      break;
  }

}


/************************************************* BLEND FUNCTIONS *************************************************/


#if defined(__i386__) || defined(__x86_64__)
__attribute__((noinline,__target__("sse2")))
#endif
void sse2_fastblend(const uint8_t* col1, const uint8_t* col2, uint8_t* result, unsigned long count, double blendpercent) {
#if ((defined(__i386__) || defined(__x86_64__) || defined(ZM_KEEP_SSE)) && !defined(ZM_STRIP_SSE))
  static uint32_t divider = 0;
  static uint32_t clearmask = 0;
  static double current_blendpercent = 0.0;

  if ( current_blendpercent != blendpercent ) {
    /* Attempt to match the blending percent to one of the possible values */
    if ( blendpercent < 2.34375 ) {
      // 1.5625% blending
      divider = 6;
      clearmask = 0x03030303;
    } else if ( blendpercent < 4.6875 ) {
      // 3.125% blending
      divider = 5;
      clearmask = 0x07070707;
    } else if ( blendpercent < 9.375 ) {
      // 6.25% blending
      divider = 4;
      clearmask = 0x0F0F0F0F;
    } else if ( blendpercent < 18.75 ) {
      // 12.5% blending
      divider = 3;
      clearmask = 0x1F1F1F1F;
    } else if ( blendpercent < 37.5 ) {
      // 25% blending
      divider = 2;
      clearmask = 0x3F3F3F3F;
    } else {
      // 50% blending
      divider = 1;
      clearmask = 0x7F7F7F7F;
    }
    current_blendpercent = blendpercent;
  }

  __asm__ __volatile__(
      "movd %4, %%xmm3\n\t"
      "movd %5, %%xmm4\n\t"
      "pshufd $0x0, %%xmm3, %%xmm3\n\t"
      "sub $0x10, %0\n\t"
      "sub $0x10, %1\n\t"
      "sub $0x10, %2\n\t"
      "sse2_fastblend_iter:\n\t"
      "movdqa (%0,%3),%%xmm0\n\t"
      "movdqa %%xmm0,%%xmm2\n\t"
      "movdqa (%1,%3),%%xmm1\n\t"
      "psrlq  %%xmm4,%%xmm0\n\t"
      "psrlq  %%xmm4,%%xmm1\n\t"
      "pand   %%xmm3,%%xmm1\n\t"
      "pand   %%xmm3,%%xmm0\n\t"
      "psubb  %%xmm0,%%xmm1\n\t"
      "paddb  %%xmm2,%%xmm1\n\t"
      "movntdq %%xmm1,(%2,%3)\n\t"
      "sub $0x10, %3\n\t"
      "jnz sse2_fastblend_iter\n\t"
      :
      : "r" (col1), "r" (col2), "r" (result), "r" (count), "m" (clearmask), "m" (divider)
      : "%xmm0", "%xmm1", "%xmm2", "%xmm3", "%xmm4", "cc", "memory"
        );
#else
  Panic("SSE function called on a non x86\\x86-64 platform");
#endif
}

__attribute__((noinline)) void std_fastblend(const uint8_t* col1, const uint8_t* col2, uint8_t* result, unsigned long count, double blendpercent) {
  static int divider = 0;
  static double current_blendpercent = 0.0;
  const uint8_t* const max_ptr = result + count;

  if ( current_blendpercent != blendpercent ) {
    /* Attempt to match the blending percent to one of the possible values */
    if ( blendpercent < 2.34375 ) {
      // 1.5625% blending
      divider = 6;
    } else if ( blendpercent < 4.6875 ) {
      // 3.125% blending
      divider = 5;
    } else if ( blendpercent < 9.375 ) {
      // 6.25% blending
      divider = 4;
    } else if ( blendpercent < 18.75 ) {
      // 12.5% blending
      divider = 3;
    } else if ( blendpercent < 37.5 ) {
      // 25% blending
      divider = 2;
    } else {
      // 50% blending
      divider = 1;
    }
    current_blendpercent = blendpercent;
  }

  while ( result < max_ptr ) {
    result[0] = ((col2[0] - col1[0])>>divider) + col1[0];
    result[1] = ((col2[1] - col1[1])>>divider) + col1[1];
    result[2] = ((col2[2] - col1[2])>>divider) + col1[2];
    result[3] = ((col2[3] - col1[3])>>divider) + col1[3];
    result[4] = ((col2[4] - col1[4])>>divider) + col1[4];
    result[5] = ((col2[5] - col1[5])>>divider) + col1[5];
    result[6] = ((col2[6] - col1[6])>>divider) + col1[6];
    result[7] = ((col2[7] - col1[7])>>divider) + col1[7];
    result[8] = ((col2[8] - col1[8])>>divider) + col1[8];
    result[9] = ((col2[9] - col1[9])>>divider) + col1[9];
    result[10] = ((col2[10] - col1[10])>>divider) + col1[10];
    result[11] = ((col2[11] - col1[11])>>divider) + col1[11];
    result[12] = ((col2[12] - col1[12])>>divider) + col1[12];
    result[13] = ((col2[13] - col1[13])>>divider) + col1[13];
    result[14] = ((col2[14] - col1[14])>>divider) + col1[14];
    result[15] = ((col2[15] - col1[15])>>divider) + col1[15];

    col1 += 16;
    col2 += 16;
    result += 16;
  }
}

/* FastBlend Neon for AArch32 */
#if (defined(__arm__) && defined(__ARM_PCS_VFP) && !defined(ZM_STRIP_NEON))
__attribute__((noinline,__target__("fpu=neon")))
#endif
void neon32_armv7_fastblend(const uint8_t* col1, const uint8_t* col2, uint8_t* result, unsigned long count, double blendpercent) {
#if (defined(__arm__) && defined(__ARM_PCS_VFP) && !defined(ZM_STRIP_NEON))
  static int8_t divider = 0;
  static double current_blendpercent = 0.0;

  if(current_blendpercent != blendpercent) {
    /* Attempt to match the blending percent to one of the possible values */
    if(blendpercent < 2.34375) {
      // 1.5625% blending
      divider = 6;
    } else if(blendpercent >= 2.34375 && blendpercent < 4.6875) {
      // 3.125% blending
      divider = 5;
    } else if(blendpercent >= 4.6875 && blendpercent < 9.375) {
      // 6.25% blending
      divider = 4;
    } else if(blendpercent >= 9.375 && blendpercent < 18.75) {
      // 12.5% blending
      divider = 3;
    } else if(blendpercent >= 18.75 && blendpercent < 37.5) {
      // 25% blending
      divider = 2;
    } else if(blendpercent >= 37.5) {
      // 50% blending
      divider = 1;
    }
    // We only have instruction to shift left by a variable, going negative shifts right :)
    divider *= -1;
    current_blendpercent = blendpercent;
  }

  /* Q0(D0,D1)    = col1+0 */
  /* Q1(D2,D3)    = col1+16 */
  /* Q2(D4,D5)    = col1+32 */
  /* Q3(D6,D7)    = col1+48 */
  /* Q4(D8,D9)    = col2+0 */
  /* Q5(D10,D11)  = col2+16 */
  /* Q6(D12,D13)  = col2+32 */
  /* Q7(D14,D15)  = col2+48 */
  /* Q8(D16,D17)  = col1tmp+0 */
  /* Q9(D18,D19)  = col1tmp+16 */
  /* Q10(D20,D21) = col1tmp+32 */
  /* Q11(D22,D23) = col1tmp+48 */
  /* Q12(D24,D25) = divider */

  __asm__ __volatile__ (
  "mov r12, %4\n\t"
  "vdup.8 q12, r12\n\t"
  "neon32_armv7_fastblend_iter%=:\n\t"
  "vldm %0!, {q0,q1,q2,q3}\n\t"
  "vldm %1!, {q4,q5,q6,q7}\n\t"
  "pld [%0, #256]\n\t"
  "pld [%1, #256]\n\t"
  "vrshl.u8 q8, q0, q12\n\t"
  "vrshl.u8 q9, q1, q12\n\t"
  "vrshl.u8 q10, q2, q12\n\t"
  "vrshl.u8 q11, q3, q12\n\t"
  "vrshl.u8 q4, q4, q12\n\t"
  "vrshl.u8 q5, q5, q12\n\t"
  "vrshl.u8 q6, q6, q12\n\t"
  "vrshl.u8 q7, q7, q12\n\t"
  "vsub.i8 q4, q4, q8\n\t"
  "vsub.i8 q5, q5, q9\n\t"
  "vsub.i8 q6, q6, q10\n\t"
  "vsub.i8 q7, q7, q11\n\t"
  "vadd.i8 q4, q4, q0\n\t"
  "vadd.i8 q5, q5, q1\n\t"
  "vadd.i8 q6, q6, q2\n\t"
  "vadd.i8 q7, q7, q3\n\t"
  "vstm %2!, {q4,q5,q6,q7}\n\t"
  "subs %3, %3, #64\n\t"
  "bne neon32_armv7_fastblend_iter%=\n\t"
  :
  : "r" (col1), "r" (col2), "r" (result), "r" (count), "r" (divider)
  : "%r12", "%q0", "%q1", "%q2", "%q3", "%q4", "%q5", "%q6", "%q7", "%q8", "%q9", "%q10", "%q11", "%q12", "cc", "memory"
  );
#else
  Panic("Neon function called on a non-ARM platform or Neon code is absent");
#endif
}

__attribute__((noinline)) void neon64_armv8_fastblend(const uint8_t* col1, const uint8_t* col2, uint8_t* result, unsigned long count, double blendpercent) {
#if (defined(__aarch64__) && !defined(ZM_STRIP_NEON))
  static int8_t divider = 0;
  static double current_blendpercent = 0.0;

  if(current_blendpercent != blendpercent) {
    /* Attempt to match the blending percent to one of the possible values */
    if(blendpercent < 2.34375) {
      // 1.5625% blending
      divider = 6;
    } else if(blendpercent >= 2.34375 && blendpercent < 4.6875) {
      // 3.125% blending
      divider = 5;
    } else if(blendpercent >= 4.6875 && blendpercent < 9.375) {
      // 6.25% blending
      divider = 4;
    } else if(blendpercent >= 9.375 && blendpercent < 18.75) {
      // 12.5% blending
      divider = 3;
    } else if(blendpercent >= 18.75 && blendpercent < 37.5) {
      // 25% blending
      divider = 2;
    } else if(blendpercent >= 37.5) {
      // 50% blending
      divider = 1;
    }
    // We only have instruction to shift left by a variable, going negative shifts right :)
    divider *= -1;
    current_blendpercent = blendpercent;
  }

  /* V16 = col1+0     */
  /* V17 = col1+16    */
  /* V18 = col1+32    */
  /* V19 = col1+48    */
  /* V20 = col2+0     */
  /* V21 = col2+16    */
  /* V22 = col2+32    */
  /* V23 = col2+48    */
  /* V24 = col1tmp+0  */
  /* V25 = col1tmp+16 */
  /* V26 = col1tmp+32 */
  /* V27 = col1tmp+48 */
  /* V28 = divider    */

  __asm__ __volatile__ (
  "mov x12, %4\n\t"
  "dup v28.16b, w12\n\t"
  "neon64_armv8_fastblend_iter%=:\n\t"
  "ldp q16, q17, [%0], #32\n\t"
  "ldp q18, q19, [%0], #32\n\t"
  "ldp q20, q21, [%1], #32\n\t"
  "ldp q22, q23, [%1], #32\n\t"
  "prfm pldl1keep, [%0, #256]\n\t"
  "prfm pldl1keep, [%1, #256]\n\t"
  "urshl v24.16b, v16.16b, v28.16b\n\t"
  "urshl v25.16b, v17.16b, v28.16b\n\t"
  "urshl v26.16b, v18.16b, v28.16b\n\t"
  "urshl v27.16b, v19.16b, v28.16b\n\t"
  "urshl v20.16b, v20.16b, v28.16b\n\t"
  "urshl v21.16b, v21.16b, v28.16b\n\t"
  "urshl v22.16b, v22.16b, v28.16b\n\t"
  "urshl v23.16b, v23.16b, v28.16b\n\t"
  "sub v20.16b, v20.16b, v24.16b\n\t"
  "sub v21.16b, v21.16b, v25.16b\n\t"
  "sub v22.16b, v22.16b, v26.16b\n\t"
  "sub v23.16b, v23.16b, v27.16b\n\t"
  "add v20.16b, v20.16b, v16.16b\n\t"
  "add v21.16b, v21.16b, v17.16b\n\t"
  "add v22.16b, v22.16b, v18.16b\n\t"
  "add v23.16b, v23.16b, v19.16b\n\t"
  "stp q20, q21, [%2], #32\n\t"
  "stp q22, q23, [%2], #32\n\t"
  "subs %3, %3, #64\n\t"
  "bne neon64_armv8_fastblend_iter%=\n\t"
  :
  : "r" (col1), "r" (col2), "r" (result), "r" (count), "r" (divider)
  : "%x12", "%v16", "%v17", "%v18", "%v19", "%v20", "%v21", "%v22", "%v23", "%v24", "%v25", "%v26", "%v27", "%v28", "cc", "memory"
);
#else
  Panic("Neon function called on a non-ARM platform or Neon code is absent");
#endif
}

__attribute__((noinline)) void std_blend(const uint8_t* col1, const uint8_t* col2, uint8_t* result, unsigned long count, double blendpercent) {
  double divide = blendpercent / 100.0;
  double opacity = 1.0 - divide;
  const uint8_t* const max_ptr = result + count;

  while ( result < max_ptr ) {
    *result++ = (*col1++ * opacity) + (*col2++ * divide);
  }
}

/************************************************* DELTA FUNCTIONS *************************************************/

/* Grayscale */
__attribute__((noinline)) void fast_delta8_gray8(const uint8_t* col1, const uint8_t* col2, uint8_t* result, unsigned long count) {
  /* Loop unrolling is used to work on 16 bytes (16 grayscale pixels) at a time */
  const uint8_t* const max_ptr = result + count;

  while(result < max_ptr) {
    result[0] = abs(col1[0] - col2[0]);
    result[1] = abs(col1[1] - col2[1]);
    result[2] = abs(col1[2] - col2[2]);
    result[3] = abs(col1[3] - col2[3]);
    result[4] = abs(col1[4] - col2[4]);
    result[5] = abs(col1[5] - col2[5]);
    result[6] = abs(col1[6] - col2[6]);
    result[7] = abs(col1[7] - col2[7]);
    result[8] = abs(col1[8] - col2[8]);
    result[9] = abs(col1[9] - col2[9]);
    result[10] = abs(col1[10] - col2[10]);
    result[11] = abs(col1[11] - col2[11]);
    result[12] = abs(col1[12] - col2[12]);
    result[13] = abs(col1[13] - col2[13]);
    result[14] = abs(col1[14] - col2[14]);
    result[15] = abs(col1[15] - col2[15]);

    col1 += 16;
    col2 += 16;
    result += 16;
  }
}

__attribute__((noinline)) void std_delta8_gray8(const uint8_t* col1, const uint8_t* col2, uint8_t* result, unsigned long count) {
  const uint8_t* const max_ptr = result + count;

  while(result < max_ptr) {
    result[0] = abs(col1[0] - col2[0]);

    col1 += 1;
    col2 += 1;
    result += 1;
  }
}

/* RGB24: RGB */
__attribute__((noinline)) void fast_delta8_rgb(const uint8_t* col1, const uint8_t* col2, uint8_t* result, unsigned long count) {
  /* Loop unrolling is used to work on 12 bytes (4 rgb24 pixels) at a time */
  int r,g,b;
  const uint8_t* const max_ptr = result + count;

  while(result < max_ptr) {
    r = abs(col1[0] - col2[0]);
    g = abs(col1[1] - col2[1]);
    b = abs(col1[2] - col2[2]);
    result[0] = (r + r + b + g + g + g + g + g)>>3;
    r = abs(col1[3] - col2[3]);
    g = abs(col1[4] - col2[4]);
    b = abs(col1[5] - col2[5]);
    result[1] = (r + r + b + g + g + g + g + g)>>3;
    r = abs(col1[6] - col2[6]);
    g = abs(col1[7] - col2[7]);
    b = abs(col1[8] - col2[8]);
    result[2] = (r + r + b + g + g + g + g + g)>>3;
    r = abs(col1[9] - col2[9]);
    g = abs(col1[10] - col2[10]);
    b = abs(col1[11] - col2[11]);
    result[3] = (r + r + b + g + g + g + g + g)>>3;

    col1 += 12;
    col2 += 12;
    result += 4;
  }
}

__attribute__((noinline)) void std_delta8_rgb(const uint8_t* col1, const uint8_t* col2, uint8_t* result, unsigned long count) {
  /* Loop unrolling is used to work on 12 bytes (4 rgb24 pixels) at a time */
  int r,g,b;
  const uint8_t* const max_ptr = result + count;

  while (result < max_ptr) {
    r = abs(col1[0] - col2[0]);
    g = abs(col1[1] - col2[1]);
    b = abs(col1[2] - col2[2]);
    result[0] = (r + r + b + g + g + g + g + g)>>3;

    col1 += 3;
    col2 += 3;
    result += 1;
  }
}

/* RGB24: BGR */
__attribute__((noinline)) void fast_delta8_bgr(const uint8_t* col1, const uint8_t* col2, uint8_t* result, unsigned long count) {
  /* Loop unrolling is used to work on 12 bytes (4 rgb24 pixels) at a time */
  int r,g,b;
  const uint8_t* const max_ptr = result + count;

  while(result < max_ptr) {
    b = abs(col1[0] - col2[0]);
    g = abs(col1[1] - col2[1]);
    r = abs(col1[2] - col2[2]);
    result[0] = (r + r + b + g + g + g + g + g)>>3;
    b = abs(col1[3] - col2[3]);
    g = abs(col1[4] - col2[4]);
    r = abs(col1[5] - col2[5]);
    result[1] = (r + r + b + g + g + g + g + g)>>3;
    b = abs(col1[6] - col2[6]);
    g = abs(col1[7] - col2[7]);
    r = abs(col1[8] - col2[8]);
    result[2] = (r + r + b + g + g + g + g + g)>>3;
    b = abs(col1[9] - col2[9]);
    g = abs(col1[10] - col2[10]);
    r = abs(col1[11] - col2[11]);
    result[3] = (r + r + b + g + g + g + g + g)>>3;

    col1 += 12;
    col2 += 12;
    result += 4;
  }
}

__attribute__((noinline)) void std_delta8_bgr(const uint8_t* col1, const uint8_t* col2, uint8_t* result, unsigned long count) {
  /* Loop unrolling is used to work on 12 bytes (4 rgb24 pixels) at a time */
  int r,g,b;
  const uint8_t* const max_ptr = result + count;

  while(result < max_ptr) {
    b = abs(col1[0] - col2[0]);
    g = abs(col1[1] - col2[1]);
    r = abs(col1[2] - col2[2]);
    result[0] = (r + r + b + g + g + g + g + g)>>3;

    col1 += 3;
    col2 += 3;
    result += 1;
  }
}

/* RGB32: RGBA */
__attribute__((noinline)) void fast_delta8_rgba(const uint8_t* col1, const uint8_t* col2, uint8_t* result, unsigned long count) {
  /* Loop unrolling is used to work on 16 bytes (4 rgb32 pixels) at a time */
  int r,g,b;
  const uint8_t* const max_ptr = result + count;

  while(result < max_ptr) {
    r = abs(col1[0] - col2[0]);
    g = abs(col1[1] - col2[1]);
    b = abs(col1[2] - col2[2]);
    result[0] = (r + r + b + g + g + g + g + g)>>3;
    r = abs(col1[4] - col2[4]);
    g = abs(col1[5] - col2[5]);
    b = abs(col1[6] - col2[6]);
    result[1] = (r + r + b + g + g + g + g + g)>>3;
    r = abs(col1[8] - col2[8]);
    g = abs(col1[9] - col2[9]);
    b = abs(col1[10] - col2[10]);
    result[2] = (r + r + b + g + g + g + g + g)>>3;
    r = abs(col1[12] - col2[12]);
    g = abs(col1[13] - col2[13]);
    b = abs(col1[14] - col2[14]);
    result[3] = (r + r + b + g + g + g + g + g)>>3;

    col1 += 16;
    col2 += 16;
    result += 4;
  }
}

__attribute__((noinline)) void std_delta8_rgba(const uint8_t* col1, const uint8_t* col2, uint8_t* result, unsigned long count) {
  /* Loop unrolling is used to work on 16 bytes (4 rgb32 pixels) at a time */
  int r,g,b;
  const uint8_t* const max_ptr = result + count;

  while(result < max_ptr) {
    r = abs(col1[0] - col2[0]);
    g = abs(col1[1] - col2[1]);
    b = abs(col1[2] - col2[2]);
    result[0] = (r + r + b + g + g + g + g + g)>>3;

    col1 += 4;
    col2 += 4;
    result += 1;
  }
}

/* RGB32: BGRA */
__attribute__((noinline)) void fast_delta8_bgra(const uint8_t* col1, const uint8_t* col2, uint8_t* result, unsigned long count) {
  /* Loop unrolling is used to work on 16 bytes (4 rgb32 pixels) at a time */
  int r,g,b;
  const uint8_t* const max_ptr = result + count;

  while(result < max_ptr) {
    b = abs(col1[0] - col2[0]);
    g = abs(col1[1] - col2[1]);
    r = abs(col1[2] - col2[2]);
    result[0] = (r + r + b + g + g + g + g + g)>>3;
    b = abs(col1[4] - col2[4]);
    g = abs(col1[5] - col2[5]);
    r = abs(col1[6] - col2[6]);
    result[1] = (r + r + b + g + g + g + g + g)>>3;
    b = abs(col1[8] - col2[8]);
    g = abs(col1[9] - col2[9]);
    r = abs(col1[10] - col2[10]);
    result[2] = (r + r + b + g + g + g + g + g)>>3;
    b = abs(col1[12] - col2[12]);
    g = abs(col1[13] - col2[13]);
    r = abs(col1[14] - col2[14]);
    result[3] = (r + r + b + g + g + g + g + g)>>3;

    col1 += 16;
    col2 += 16;
    result += 4;
  }
}
__attribute__((noinline)) void std_delta8_bgra(const uint8_t* col1, const uint8_t* col2, uint8_t* result, unsigned long count) {
  /* Loop unrolling is used to work on 16 bytes (4 rgb32 pixels) at a time */
  int r,g,b;
  const uint8_t* const max_ptr = result + count;

  while(result < max_ptr) {
    b = abs(col1[0] - col2[0]);
    g = abs(col1[1] - col2[1]);
    r = abs(col1[2] - col2[2]);
    result[0] = (r + r + b + g + g + g + g + g)>>3;

    col1 += 4;
    col2 += 4;
    result += 1;
  }
}

/* RGB32: ARGB */
__attribute__((noinline)) void fast_delta8_argb(const uint8_t* col1, const uint8_t* col2, uint8_t* result, unsigned long count) {
  /* Loop unrolling is used to work on 16 bytes (4 rgb32 pixels) at a time */
  int r,g,b;
  const uint8_t* const max_ptr = result + count;

  while(result < max_ptr) {
    r = abs(col1[1] - col2[1]);
    g = abs(col1[2] - col2[2]);
    b = abs(col1[3] - col2[3]);
    result[0] = (r + r + b + g + g + g + g + g)>>3;
    r = abs(col1[5] - col2[5]);
    g = abs(col1[6] - col2[6]);
    b = abs(col1[7] - col2[7]);
    result[1] = (r + r + b + g + g + g + g + g)>>3;
    r = abs(col1[9] - col2[9]);
    g = abs(col1[10] - col2[10]);
    b = abs(col1[11] - col2[11]);
    result[2] = (r + r + b + g + g + g + g + g)>>3;
    r = abs(col1[13] - col2[13]);
    g = abs(col1[14] - col2[14]);
    b = abs(col1[15] - col2[15]);
    result[3] = (r + r + b + g + g + g + g + g)>>3;

    col1 += 16;
    col2 += 16;
    result += 4;
  }
}
__attribute__((noinline)) void std_delta8_argb(const uint8_t* col1, const uint8_t* col2, uint8_t* result, unsigned long count) {
  /* Loop unrolling is used to work on 16 bytes (4 rgb32 pixels) at a time */
  int r,g,b;
  const uint8_t* const max_ptr = result + count;

  while(result < max_ptr) {
    r = abs(col1[1] - col2[1]);
    g = abs(col1[2] - col2[2]);
    b = abs(col1[3] - col2[3]);
    result[0] = (r + r + b + g + g + g + g + g)>>3;

    col1 += 4;
    col2 += 4;
    result += 1;
  }
}

/* RGB32: ABGR */
__attribute__((noinline)) void fast_delta8_abgr(const uint8_t* col1, const uint8_t* col2, uint8_t* result, unsigned long count) {
  /* Loop unrolling is used to work on 16 bytes (4 rgb32 pixels) at a time */
  int r,g,b;
  const uint8_t* const max_ptr = result + count;

  while(result < max_ptr) {
    b = abs(col1[1] - col2[1]);
    g = abs(col1[2] - col2[2]);
    r = abs(col1[3] - col2[3]);
    result[0] = (r + r + b + g + g + g + g + g)>>3;
    b = abs(col1[5] - col2[5]);
    g = abs(col1[6] - col2[6]);
    r = abs(col1[7] - col2[7]);
    result[1] = (r + r + b + g + g + g + g + g)>>3;
    b = abs(col1[9] - col2[9]);
    g = abs(col1[10] - col2[10]);
    r = abs(col1[11] - col2[11]);
    result[2] = (r + r + b + g + g + g + g + g)>>3;
    b = abs(col1[13] - col2[13]);
    g = abs(col1[14] - col2[14]);
    r = abs(col1[15] - col2[15]);
    result[3] = (r + r + b + g + g + g + g + g)>>3;

    col1 += 16;
    col2 += 16;
    result += 4;
  }
}
__attribute__((noinline)) void std_delta8_abgr(const uint8_t* col1, const uint8_t* col2, uint8_t* result, unsigned long count) {
  int r,g,b;
  const uint8_t* const max_ptr = result + count;

  while(result < max_ptr) {
    b = abs(col1[1] - col2[1]);
    g = abs(col1[2] - col2[2]);
    r = abs(col1[3] - col2[3]);
    result[0] = (r + r + b + g + g + g + g + g)>>3;

    col1 += 4;
    col2 += 4;
    result += 1;
  }
}

/* Grayscale Neon for AArch32 */
#if (defined(__arm__) && defined(__ARM_PCS_VFP) && !defined(ZM_STRIP_NEON))
__attribute__((noinline,__target__("fpu=neon")))
#endif
void neon32_armv7_delta8_gray8(const uint8_t* col1, const uint8_t* col2, uint8_t* result, unsigned long count) {
#if (defined(__arm__) && defined(__ARM_PCS_VFP) && !defined(ZM_STRIP_NEON))

  /* Q0(D0,D1)   = col1+0 */
  /* Q1(D2,D3)   = col1+16 */
  /* Q2(D4,D5)   = col1+32 */
  /* Q3(D6,D7)   = col1+48 */
  /* Q4(D8,D9)   = col2+0 */
  /* Q5(D10,D11) = col2+16 */
  /* Q6(D12,D13) = col2+32 */
  /* Q7(D14,D15) = col2+48 */

  __asm__ __volatile__ (
  "neon32_armv7_delta8_gray8_iter%=:\n\t"
  "vldm %0!, {q0,q1,q2,q3}\n\t"
  "vldm %1!, {q4,q5,q6,q7}\n\t"
  "pld [%0, #512]\n\t"
  "pld [%1, #512]\n\t"
  "vabd.u8 q0, q0, q4\n\t"
  "vabd.u8 q1, q1, q5\n\t"
  "vabd.u8 q2, q2, q6\n\t"
  "vabd.u8 q3, q3, q7\n\t"
  "vstm %2!, {q0,q1,q2,q3}\n\t"
  "subs %3, %3, #64\n\t"
  "bne neon32_armv7_delta8_gray8_iter%=\n\t"
  :
  : "r" (col1), "r" (col2), "r" (result), "r" (count)
  : "%q0", "%q1", "%q2", "%q3", "%q4", "%q5", "%q6", "%q7", "cc", "memory"
  );
#else
  Panic("Neon function called on a non-ARM platform or Neon code is absent");
#endif
}

/* Grayscale Neon for AArch64 */
__attribute__((noinline)) void neon64_armv8_delta8_gray8(const uint8_t* col1, const uint8_t* col2, uint8_t* result, unsigned long count) {
#if (defined(__aarch64__) && !defined(ZM_STRIP_NEON))

  /* V16 = col1+0  */
  /* V17 = col1+16 */
  /* V18 = col1+32 */
  /* V19 = col1+48 */
  /* V20 = col2+0  */
  /* V21 = col2+16 */
  /* V22 = col2+32 */
  /* V23 = col2+48 */

  __asm__ __volatile__ (
  "neon64_armv8_delta8_gray8_iter%=:\n\t"
  "ldp q16, q17, [%0], #32\n\t"
  "ldp q18, q19, [%0], #32\n\t"
  "ldp q20, q21, [%1], #32\n\t"
  "ldp q22, q23, [%1], #32\n\t"
  "prfm pldl1keep, [%0, #512]\n\t"
  "prfm pldl1keep, [%1, #512]\n\t"
  "uabd v16.16b, v16.16b, v20.16b\n\t"
  "uabd v17.16b, v17.16b, v21.16b\n\t"
  "uabd v18.16b, v18.16b, v22.16b\n\t"
  "uabd v19.16b, v19.16b, v23.16b\n\t"
  "stp q16, q17, [%2], #32\n\t"
  "stp q18, q19, [%2], #32\n\t"
  "subs %3, %3, #64\n\t"
  "bne neon64_armv8_delta8_gray8_iter%=\n\t"
  :
  : "r" (col1), "r" (col2), "r" (result), "r" (count)
  : "%v16", "%v17", "%v18", "%v19", "%v20", "%v21", "%v22", "%v23", "cc", "memory"
  );
#else
  Panic("Neon function called on a non-ARM platform or Neon code is absent");
#endif
}

/* RGB32 Neon for AArch32 */
#if (defined(__arm__) && defined(__ARM_PCS_VFP) && !defined(ZM_STRIP_NEON))
__attribute__((noinline,__target__("fpu=neon")))
#endif
void neon32_armv7_delta8_rgb32(const uint8_t* col1, const uint8_t* col2, uint8_t* result, unsigned long count, uint32_t multiplier) {
#if (defined(__arm__) && defined(__ARM_PCS_VFP) && !defined(ZM_STRIP_NEON))

  /* Q0(D0,D1)   = col1+0 */
  /* Q1(D2,D3)   = col1+16 */
  /* Q2(D4,D5)   = col1+32 */
  /* Q3(D6,D7)   = col1+48 */
  /* Q4(D8,D9)   = col2+0 */
  /* Q5(D10,D11) = col2+16 */
  /* Q6(D12,D13) = col2+32 */
  /* Q7(D14,D15) = col2+48 */
  /* Q8(D16,D17) = multiplier */

  __asm__ __volatile__ (
  "mov r12, %4\n\t"
  "vdup.32 q8, r12\n\t"
  "neon32_armv7_delta8_rgb32_iter%=:\n\t"
  "vldm %0!, {q0,q1,q2,q3}\n\t"
  "vldm %1!, {q4,q5,q6,q7}\n\t"
  "pld [%0, #256]\n\t"
  "pld [%1, #256]\n\t"
  "vabd.u8 q0, q0, q4\n\t"
  "vabd.u8 q1, q1, q5\n\t"
  "vabd.u8 q2, q2, q6\n\t"
  "vabd.u8 q3, q3, q7\n\t"
  "vrshr.u8 q0, q0, #3\n\t"
  "vrshr.u8 q1, q1, #3\n\t"
  "vrshr.u8 q2, q2, #3\n\t"
  "vrshr.u8 q3, q3, #3\n\t"
  "vmul.i8 q0, q0, q8\n\t"
  "vmul.i8 q1, q1, q8\n\t"
  "vmul.i8 q2, q2, q8\n\t"
  "vmul.i8 q3, q3, q8\n\t"
  "vpadd.i8 d0, d0, d1\n\t"
  "vpadd.i8 d2, d2, d3\n\t"
  "vpadd.i8 d4, d4, d5\n\t"
  "vpadd.i8 d6, d6, d7\n\t"
  "vpadd.i8 d0, d0, d0\n\t"
  "vpadd.i8 d1, d2, d2\n\t"
  "vpadd.i8 d2, d4, d4\n\t"
  "vpadd.i8 d3, d6, d6\n\t"
  "vst4.32 {d0[0],d1[0],d2[0],d3[0]}, [%2]!\n\t"
  "subs %3, %3, #16\n\t"
  "bne neon32_armv7_delta8_rgb32_iter%=\n\t"
  :
  : "r" (col1), "r" (col2), "r" (result), "r" (count), "r" (multiplier)
  : "%r12", "%q0", "%q1", "%q2", "%q3", "%q4", "%q5", "%q6", "%q7", "%q8", "cc", "memory"
  );
#else
  Panic("Neon function called on a non-ARM platform or Neon code is absent");
#endif
}

/* RGB32 Neon for AArch64 */
__attribute__((noinline)) void neon64_armv8_delta8_rgb32(const uint8_t* col1, const uint8_t* col2, uint8_t* result, unsigned long count, uint32_t multiplier) {
#if (defined(__aarch64__) && !defined(ZM_STRIP_NEON))

  /* V16 = col1+0  */
  /* V17 = col1+16 */
  /* V18 = col1+32  */
  /* V19 = col1+48 */
  /* V20 = col2+0  */
  /* V21 = col2+16 */
  /* V22 = col2+32 */
  /* V23 = col2+48 */
  /* V24 = multiplier */

  __asm__ __volatile__ (
  "mov x12, %4\n\t"
  "dup v24.4s, w12\n\t"
  "neon64_armv8_delta8_rgb32_iter%=:\n\t"
  "ldp q16, q17, [%0], #32\n\t"
  "ldp q18, q19, [%0], #32\n\t"
  "ldp q20, q21, [%1], #32\n\t"
  "ldp q22, q23, [%1], #32\n\t"
  "prfm pldl1keep, [%0, #256]\n\t"
  "prfm pldl1keep, [%1, #256]\n\t"
  "uabd v16.16b, v16.16b, v20.16b\n\t"
  "uabd v17.16b, v17.16b, v21.16b\n\t"
  "uabd v18.16b, v18.16b, v22.16b\n\t"
  "uabd v19.16b, v19.16b, v23.16b\n\t"
  "urshr v16.16b, v16.16b, #3\n\t"
  "urshr v17.16b, v17.16b, #3\n\t"
  "urshr v18.16b, v18.16b, #3\n\t"
  "urshr v19.16b, v19.16b, #3\n\t"
  "mul v16.16b, v16.16b, v24.16b\n\t"
  "mul v17.16b, v17.16b, v24.16b\n\t"
  "mul v18.16b, v18.16b, v24.16b\n\t"
  "mul v19.16b, v19.16b, v24.16b\n\t"
  "addp v16.16b, v16.16b, v16.16b\n\t"
  "addp v17.16b, v17.16b, v17.16b\n\t"
  "addp v18.16b, v18.16b, v18.16b\n\t"
  "addp v19.16b, v19.16b, v19.16b\n\t"
  "addp v16.16b, v16.16b, v16.16b\n\t"
  "addp v17.16b, v17.16b, v17.16b\n\t"
  "addp v18.16b, v18.16b, v18.16b\n\t"
  "addp v19.16b, v19.16b, v19.16b\n\t"
  "st4 {v16.s, v17.s, v18.s, v19.s}[0], [%2], #16\n\t"
  "subs %3, %3, #16\n\t"
  "bne neon64_armv8_delta8_rgb32_iter%=\n\t"
  :
  : "r" (col1), "r" (col2), "r" (result), "r" (count), "r" (multiplier)
  : "%x12", "%v16", "%v17", "%v18", "%v19", "%v20", "%v21", "%v22", "%v23", "%v24", "cc", "memory"
  );
#else
  Panic("Neon function called on a non-ARM platform or Neon code is absent");
#endif
}

/* RGB32: RGBA Neon for AArch32 */
void neon32_armv7_delta8_rgba(const uint8_t* col1, const uint8_t* col2, uint8_t* result, unsigned long count) {
  neon32_armv7_delta8_rgb32(col1, col2, result, count, 0x00010502);
}

/* RGB32: BGRA Neon for AArch32 */
void neon32_armv7_delta8_bgra(const uint8_t* col1, const uint8_t* col2, uint8_t* result, unsigned long count) {
  neon32_armv7_delta8_rgb32(col1, col2, result, count, 0x00020501);
}

/* RGB32: ARGB Neon for AArch32 */
void neon32_armv7_delta8_argb(const uint8_t* col1, const uint8_t* col2, uint8_t* result, unsigned long count) {
  neon32_armv7_delta8_rgb32(col1, col2, result, count, 0x01050200);
}

/* RGB32: ABGR Neon for AArch32 */
void neon32_armv7_delta8_abgr(const uint8_t* col1, const uint8_t* col2, uint8_t* result, unsigned long count) {
  neon32_armv7_delta8_rgb32(col1, col2, result, count, 0x02050100);
}

/* RGB32: RGBA Neon for AArch64 */
void neon64_armv8_delta8_rgba(const uint8_t* col1, const uint8_t* col2, uint8_t* result, unsigned long count) {
  neon64_armv8_delta8_rgb32(col1, col2, result, count, 0x00010502);
}

/* RGB32: BGRA Neon for AArch64 */
void neon64_armv8_delta8_bgra(const uint8_t* col1, const uint8_t* col2, uint8_t* result, unsigned long count) {
  neon64_armv8_delta8_rgb32(col1, col2, result, count, 0x00020501);
}

/* RGB32: ARGB Neon for AArch64 */
void neon64_armv8_delta8_argb(const uint8_t* col1, const uint8_t* col2, uint8_t* result, unsigned long count) {
  neon64_armv8_delta8_rgb32(col1, col2, result, count, 0x01050200);
}

/* RGB32: ABGR Neon for AArch64 */
void neon64_armv8_delta8_abgr(const uint8_t* col1, const uint8_t* col2, uint8_t* result, unsigned long count) {
  neon64_armv8_delta8_rgb32(col1, col2, result, count, 0x02050100);
}

/* Grayscale SSE2 */
#if defined(__i386__) || defined(__x86_64__)
__attribute__((noinline,__target__("sse2")))
#endif
void sse2_delta8_gray8(const uint8_t* col1, const uint8_t* col2, uint8_t* result, unsigned long count) {
#if ((defined(__i386__) || defined(__x86_64__) || defined(ZM_KEEP_SSE)) && !defined(ZM_STRIP_SSE))

  __asm__ __volatile__ (
      "sub $0x10, %0\n\t"
      "sub $0x10, %1\n\t"
      "sub $0x10, %2\n\t"
      "sse2_delta8_gray8_iter:\n\t"
      "movdqa (%0,%3), %%xmm1\n\t"
      "movdqa (%1,%3), %%xmm2\n\t"
      "movdqa %%xmm1, %%xmm3\n\t"
      "movdqa %%xmm2, %%xmm4\n\t"
      "pmaxub %%xmm1, %%xmm2\n\t"
      "pminub %%xmm3, %%xmm4\n\t"
      "psubb  %%xmm4, %%xmm2\n\t"
      "movntdq %%xmm2, (%2,%3)\n\t"
      "sub $0x10, %3\n\t"
      "jnz sse2_delta8_gray8_iter\n\t"
      :
      : "r" (col1), "r" (col2), "r" (result), "r" (count)
      : "%xmm1", "%xmm2", "%xmm3", "%xmm4", "cc", "memory"
      );
#else
  Panic("SSE function called on a non x86\\x86-64 platform");
#endif
}

/* RGB32: RGBA SSE2 */
#if defined(__i386__) || defined(__x86_64__)
__attribute__((noinline,__target__("sse2")))
#endif
void sse2_delta8_rgba(const uint8_t* col1, const uint8_t* col2, uint8_t* result, unsigned long count) {
#if ((defined(__i386__) || defined(__x86_64__) || defined(ZM_KEEP_SSE)) && !defined(ZM_STRIP_SSE))

  __asm__ __volatile__ (
      "mov $0x1F1F1F1F, %%eax\n\t"
      "movd %%eax, %%xmm4\n\t"
      "pshufd $0x0, %%xmm4, %%xmm4\n\t"
      "mov $0xff, %%eax\n\t"
      "movd %%eax, %%xmm0\n\t"
      "pshufd $0x0, %%xmm0, %%xmm0\n\t"
      "sub $0x10, %0\n\t"
      "sub $0x10, %1\n\t"
      "sub $0x4, %2\n\t"
      "sse2_delta8_rgba_iter:\n\t"
      "movdqa (%0,%3,4), %%xmm1\n\t"
      "movdqa (%1,%3,4), %%xmm2\n\t"
      "psrlq $0x3, %%xmm1\n\t"
      "psrlq $0x3, %%xmm2\n\t"
      "pand %%xmm4, %%xmm1\n\t"
      "pand %%xmm4, %%xmm2\n\t"
      "movdqa %%xmm1, %%xmm5\n\t"
      "movdqa %%xmm2, %%xmm6\n\t"
      "pmaxub %%xmm1, %%xmm2\n\t"
      "pminub %%xmm5, %%xmm6\n\t"
      "psubb %%xmm6, %%xmm2\n\t"
      "movdqa %%xmm2, %%xmm3\n\t"
      "psrld $0x8, %%xmm2\n\t"
      "pand %%xmm0, %%xmm2\n\t"
      "movdqa %%xmm2, %%xmm1\n\t"
      "pslld $0x2, %%xmm2\n\t"
      "paddd %%xmm1, %%xmm2\n\t"
      "movdqa %%xmm3, %%xmm1\n\t"
      "pand %%xmm0, %%xmm1\n\t"
      "paddd %%xmm1, %%xmm1\n\t"
      "paddd %%xmm2, %%xmm1\n\t"
      "movdqa %%xmm3, %%xmm2\n\t"
      "psrld $0x10, %%xmm2\n\t"
      "pand %%xmm0, %%xmm2\n\t"
      "paddd %%xmm2, %%xmm1\n\t"
      "packssdw %%xmm1, %%xmm1\n\t"
      "packuswb %%xmm1, %%xmm1\n\t"
      "movd %%xmm1, %%eax\n\t"
      "movnti %%eax, (%2,%3)\n\t"
      "sub $0x4, %3\n\t"
      "jnz sse2_delta8_rgba_iter\n\t"
      :
      : "r" (col1), "r" (col2), "r" (result), "r" (count)
      : "%eax", "%xmm0", "%xmm1", "%xmm2", "%xmm3", "%xmm4", "%xmm5", "%xmm6", "cc", "memory"
        );
#else
  Panic("SSE function called on a non x86\\x86-64 platform");
#endif
}

/* RGB32: BGRA SSE2 */
#if defined(__i386__) || defined(__x86_64__)
__attribute__((noinline,__target__("sse2")))
#endif
void sse2_delta8_bgra(const uint8_t* col1, const uint8_t* col2, uint8_t* result, unsigned long count) {
#if ((defined(__i386__) || defined(__x86_64__) || defined(ZM_KEEP_SSE)) && !defined(ZM_STRIP_SSE))

  __asm__ __volatile__ (
      "mov $0x1F1F1F1F, %%eax\n\t"
      "movd %%eax, %%xmm4\n\t"
      "pshufd $0x0, %%xmm4, %%xmm4\n\t"
      "mov $0xff, %%eax\n\t"
      "movd %%eax, %%xmm0\n\t"
      "pshufd $0x0, %%xmm0, %%xmm0\n\t"
      "sub $0x10, %0\n\t"
      "sub $0x10, %1\n\t"
      "sub $0x4, %2\n\t"
      "sse2_delta8_bgra_iter:\n\t"
      "movdqa (%0,%3,4), %%xmm1\n\t"
      "movdqa (%1,%3,4), %%xmm2\n\t"
      "psrlq $0x3, %%xmm1\n\t"
      "psrlq $0x3, %%xmm2\n\t"
      "pand %%xmm4, %%xmm1\n\t"
      "pand %%xmm4, %%xmm2\n\t"
      "movdqa %%xmm1, %%xmm5\n\t"
      "movdqa %%xmm2, %%xmm6\n\t"
      "pmaxub %%xmm1, %%xmm2\n\t"
      "pminub %%xmm5, %%xmm6\n\t"
      "psubb %%xmm6, %%xmm2\n\t"
      "movdqa %%xmm2, %%xmm3\n\t"
      "psrld $0x8, %%xmm2\n\t"
      "pand %%xmm0, %%xmm2\n\t"
      "movdqa %%xmm2, %%xmm1\n\t"
      "pslld $0x2, %%xmm2\n\t"
      "paddd %%xmm1, %%xmm2\n\t"
      "movdqa %%xmm3, %%xmm1\n\t"
      "pand %%xmm0, %%xmm1\n\t"
      "paddd %%xmm2, %%xmm1\n\t"
      "movdqa %%xmm3, %%xmm2\n\t"
      "psrld $0x10, %%xmm2\n\t"
      "pand %%xmm0, %%xmm2\n\t"
      "paddd %%xmm2, %%xmm2\n\t"
      "paddd %%xmm2, %%xmm1\n\t"
      "packssdw %%xmm1, %%xmm1\n\t"
      "packuswb %%xmm1, %%xmm1\n\t"
      "movd %%xmm1, %%eax\n\t"
      "movnti %%eax, (%2,%3)\n\t"
      "sub $0x4, %3\n\t"
      "jnz sse2_delta8_bgra_iter\n\t"
      :
      : "r" (col1), "r" (col2), "r" (result), "r" (count)
      : "%eax", "%xmm0", "%xmm1", "%xmm2", "%xmm3", "%xmm4", "%xmm5", "%xmm6", "cc", "memory"
        );
#else
  Panic("SSE function called on a non x86\\x86-64 platform");
#endif
}

/* RGB32: ARGB SSE2 */
#if defined(__i386__) || defined(__x86_64__)
__attribute__((noinline,__target__("sse2")))
#endif
void sse2_delta8_argb(const uint8_t* col1, const uint8_t* col2, uint8_t* result, unsigned long count) {
#if ((defined(__i386__) || defined(__x86_64__) || defined(ZM_KEEP_SSE)) && !defined(ZM_STRIP_SSE))

  __asm__ __volatile__ (
      "mov $0x1F1F1F1F, %%eax\n\t"
      "movd %%eax, %%xmm4\n\t"
      "pshufd $0x0, %%xmm4, %%xmm4\n\t"
      "mov $0xff, %%eax\n\t"
      "movd %%eax, %%xmm0\n\t"
      "pshufd $0x0, %%xmm0, %%xmm0\n\t"
      "sub $0x10, %0\n\t"
      "sub $0x10, %1\n\t"
      "sub $0x4, %2\n\t"
      "sse2_delta8_argb_iter:\n\t"
      "movdqa (%0,%3,4), %%xmm1\n\t"
      "movdqa (%1,%3,4), %%xmm2\n\t"
      "psrlq $0x3, %%xmm1\n\t"
      "psrlq $0x3, %%xmm2\n\t"
      "pand %%xmm4, %%xmm1\n\t"
      "pand %%xmm4, %%xmm2\n\t"
      "movdqa %%xmm1, %%xmm5\n\t"
      "movdqa %%xmm2, %%xmm6\n\t"
      "pmaxub %%xmm1, %%xmm2\n\t"
      "pminub %%xmm5, %%xmm6\n\t"
      "psubb %%xmm6, %%xmm2\n\t"
      "movdqa %%xmm2, %%xmm3\n\t"
      "psrld $0x10, %%xmm2\n\t"
      "pand %%xmm0, %%xmm2\n\t"
      "movdqa %%xmm2, %%xmm1\n\t"
      "pslld $0x2, %%xmm2\n\t"
      "paddd %%xmm1, %%xmm2\n\t"
      "movdqa %%xmm3, %%xmm1\n\t"
      "psrld $0x8, %%xmm1\n\t"
      "pand %%xmm0, %%xmm1\n\t"
      "paddd %%xmm1, %%xmm1\n\t"
      "paddd %%xmm2, %%xmm1\n\t"
      "movdqa %%xmm3, %%xmm2\n\t"
      "psrld $0x18, %%xmm2\n\t"
      "pand %%xmm0, %%xmm2\n\t"
      "paddd %%xmm2, %%xmm1\n\t"
      "packssdw %%xmm1, %%xmm1\n\t"
      "packuswb %%xmm1, %%xmm1\n\t"
      "movd %%xmm1, %%eax\n\t"
      "movnti %%eax, (%2,%3)\n\t"
      "sub $0x4, %3\n\t"
      "jnz sse2_delta8_argb_iter\n\t"
      :
      : "r" (col1), "r" (col2), "r" (result), "r" (count)
      : "%eax", "%xmm0", "%xmm1", "%xmm2", "%xmm3", "%xmm4", "%xmm5", "%xmm6", "cc", "memory"
        );
#else
  Panic("SSE function called on a non x86\\x86-64 platform");
#endif
}

/* RGB32: ABGR SSE2 */
#if defined(__i386__) || defined(__x86_64__)
__attribute__((noinline,__target__("sse2")))
#endif
void sse2_delta8_abgr(const uint8_t* col1, const uint8_t* col2, uint8_t* result, unsigned long count) {
#if ((defined(__i386__) || defined(__x86_64__) || defined(ZM_KEEP_SSE)) && !defined(ZM_STRIP_SSE))

  __asm__ __volatile__ (
      "mov $0x1F1F1F1F, %%eax\n\t"
      "movd %%eax, %%xmm4\n\t"
      "pshufd $0x0, %%xmm4, %%xmm4\n\t"
      "mov $0xff, %%eax\n\t"
      "movd %%eax, %%xmm0\n\t"
      "pshufd $0x0, %%xmm0, %%xmm0\n\t"
      "sub $0x10, %0\n\t"
      "sub $0x10, %1\n\t"
      "sub $0x4, %2\n\t"
      "sse2_delta8_abgr_iter:\n\t"
      "movdqa (%0,%3,4), %%xmm1\n\t"
      "movdqa (%1,%3,4), %%xmm2\n\t"
      "psrlq $0x3, %%xmm1\n\t"
      "psrlq $0x3, %%xmm2\n\t"
      "pand %%xmm4, %%xmm1\n\t"
      "pand %%xmm4, %%xmm2\n\t"
      "movdqa %%xmm1, %%xmm5\n\t"
      "movdqa %%xmm2, %%xmm6\n\t"
      "pmaxub %%xmm1, %%xmm2\n\t"
      "pminub %%xmm5, %%xmm6\n\t"
      "psubb %%xmm6, %%xmm2\n\t"
      "movdqa %%xmm2, %%xmm3\n\t"
      "psrld $0x10, %%xmm2\n\t"
      "pand %%xmm0, %%xmm2\n\t"
      "movdqa %%xmm2, %%xmm1\n\t"
      "pslld $0x2, %%xmm2\n\t"
      "paddd %%xmm1, %%xmm2\n\t"
      "movdqa %%xmm3, %%xmm1\n\t"
      "psrld $0x8, %%xmm1\n\t"
      "pand %%xmm0, %%xmm1\n\t"
      "paddd %%xmm2, %%xmm1\n\t"
      "movdqa %%xmm3, %%xmm2\n\t"
      "psrld $0x18, %%xmm2\n\t"
      "pand %%xmm0, %%xmm2\n\t"
      "paddd %%xmm2, %%xmm2\n\t"
      "paddd %%xmm2, %%xmm1\n\t"
      "packssdw %%xmm1, %%xmm1\n\t"
      "packuswb %%xmm1, %%xmm1\n\t"
      "movd %%xmm1, %%eax\n\t"
      "movnti %%eax, (%2,%3)\n\t"
      "sub $0x4, %3\n\t"
      "jnz sse2_delta8_abgr_iter\n\t"
      :
      : "r" (col1), "r" (col2), "r" (result), "r" (count)
      : "%eax", "%xmm0", "%xmm1", "%xmm2", "%xmm3", "%xmm4", "%xmm5", "%xmm6", "cc", "memory"
        );
#else
  Panic("SSE function called on a non x86\\x86-64 platform");
#endif
}

/* RGB32 SSSE3 */
#if defined(__i386__) || defined(__x86_64__)
__attribute__((noinline,__target__("ssse3")))
#endif
void ssse3_delta8_rgb32(const uint8_t* col1, const uint8_t* col2, uint8_t* result, unsigned long count, uint32_t multiplier) {
#if ((defined(__i386__) || defined(__x86_64__) || defined(ZM_KEEP_SSE)) && !defined(ZM_STRIP_SSE))

  /* XMM0 - zero */
  /* XMM1 - col1 */
  /* XMM2 - col2 */
  /* XMM3 - multiplier */
  /* XMM4 - divide mask */

  __asm__ __volatile__ (
      "mov $0x1F1F1F1F, %%eax\n\t"
      "movd %%eax, %%xmm4\n\t"
      "pshufd $0x0, %%xmm4, %%xmm4\n\t"
      "mov %4, %%eax\n\t"
      "movd %%eax, %%xmm3\n\t"
      "pshufd $0x0, %%xmm3, %%xmm3\n\t"
      "pxor %%xmm0, %%xmm0\n\t"
      "sub $0x10, %0\n\t"
      "sub $0x10, %1\n\t"
      "sub $0x4, %2\n\t"
      "ssse3_delta8_rgb32_iter:\n\t"
      "movdqa (%0,%3,4), %%xmm1\n\t"
      "movdqa (%1,%3,4), %%xmm2\n\t"
      "psrlq $0x3, %%xmm1\n\t"
      "psrlq $0x3, %%xmm2\n\t"
      "pand %%xmm4, %%xmm1\n\t"
      "pand %%xmm4, %%xmm2\n\t"
      "psubb %%xmm2, %%xmm1\n\t"
      "pabsb %%xmm1, %%xmm1\n\t"
      "pmaddubsw %%xmm3, %%xmm1\n\t"
      "phaddw %%xmm0, %%xmm1\n\t"
      "packuswb %%xmm1, %%xmm1\n\t"
      "movd %%xmm1, %%eax\n\t"
      "movnti %%eax, (%2,%3)\n\t"
      "sub $0x4, %3\n\t"
      "jnz ssse3_delta8_rgb32_iter\n\t"
      :
      : "r" (col1), "r" (col2), "r" (result), "r" (count), "g" (multiplier)
      : "%eax", "%xmm0", "%xmm1", "%xmm2", "%xmm3", "%xmm4", "cc", "memory"
        );
#else
  Panic("SSE function called on a non x86\\x86-64 platform");
#endif
}

/* RGB32: RGBA SSSE3 */
void ssse3_delta8_rgba(const uint8_t* col1, const uint8_t* col2, uint8_t* result, unsigned long count) {
  ssse3_delta8_rgb32(col1, col2, result, count, 0x00010502);
}

/* RGB32: BGRA SSSE3 */
void ssse3_delta8_bgra(const uint8_t* col1, const uint8_t* col2, uint8_t* result, unsigned long count) {
  ssse3_delta8_rgb32(col1, col2, result, count, 0x00020501);
}

/* RGB32: ARGB SSSE3 */
void ssse3_delta8_argb(const uint8_t* col1, const uint8_t* col2, uint8_t* result, unsigned long count) {
  ssse3_delta8_rgb32(col1, col2, result, count, 0x01050200);
}

/* RGB32: ABGR SSSE3 */
void ssse3_delta8_abgr(const uint8_t* col1, const uint8_t* col2, uint8_t* result, unsigned long count) {
  ssse3_delta8_rgb32(col1, col2, result, count, 0x02050100);
}


/************************************************* CONVERT FUNCTIONS *************************************************/

/* RGB24 to grayscale */
__attribute__((noinline)) void fast_convert_rgb_gray8(const uint8_t* col1, uint8_t* result, unsigned long count) {
  unsigned int r,g,b;
  const uint8_t* const max_ptr = result + count;

  while(result < max_ptr) {
    r = col1[0];
    g = col1[1];
    b = col1[2];
    result[0] = (r + r + b + g + g + g + g + g)>>3;
    r = col1[3];
    g = col1[4];
    b = col1[5];
    result[1] = (r + r + b + g + g + g + g + g)>>3;
    r = col1[6];
    g = col1[7];
    b = col1[8];
    result[2] = (r + r + b + g + g + g + g + g)>>3;
    r = col1[9];
    g = col1[10];
    b = col1[11];
    result[3] = (r + r + b + g + g + g + g + g)>>3;

    col1 += 12;
    result += 4;
  }
}
__attribute__((noinline)) void std_convert_rgb_gray8(const uint8_t* col1, uint8_t* result, unsigned long count) {
  unsigned int r,g,b;
  const uint8_t* const max_ptr = result + count;

  while(result < max_ptr) {
    r = col1[0];
    g = col1[1];
    b = col1[2];
    result[0] = (r + r + b + g + g + g + g + g)>>3;

    col1 += 3;
    result += 1;
  }
}

/* BGR24 to grayscale */
__attribute__((noinline)) void fast_convert_bgr_gray8(const uint8_t* col1, uint8_t* result, unsigned long count) {
  unsigned int r,g,b;
  const uint8_t* const max_ptr = result + count;

  while(result < max_ptr) {
    b = col1[0];
    g = col1[1];
    r = col1[2];
    result[0] = (r + r + b + g + g + g + g + g)>>3;
    b = col1[3];
    g = col1[4];
    r = col1[5];
    result[1] = (r + r + b + g + g + g + g + g)>>3;
    b = col1[6];
    g = col1[7];
    r = col1[8];
    result[2] = (r + r + b + g + g + g + g + g)>>3;
    b = col1[9];
    g = col1[10];
    r = col1[11];
    result[3] = (r + r + b + g + g + g + g + g)>>3;

    col1 += 12;
    result += 4;
  }
}
__attribute__((noinline)) void std_convert_bgr_gray8(const uint8_t* col1, uint8_t* result, unsigned long count) {
  unsigned int r,g,b;
  const uint8_t* const max_ptr = result + count;

  while(result < max_ptr) {
    b = col1[0];
    g = col1[1];
    r = col1[2];
    result[0] = (r + r + b + g + g + g + g + g)>>3;

    col1 += 3;
    result += 1;
  }
}

/* RGBA to grayscale */
__attribute__((noinline)) void fast_convert_rgba_gray8(const uint8_t* col1, uint8_t* result, unsigned long count) {
  unsigned int r,g,b;
  const uint8_t* const max_ptr = result + count;

  while(result < max_ptr) {
    r = col1[0];
    g = col1[1];
    b = col1[2];
    result[0] = (r + r + b + g + g + g + g + g)>>3;
    r = col1[4];
    g = col1[5];
    b = col1[6];
    result[1] = (r + r + b + g + g + g + g + g)>>3;
    r = col1[8];
    g = col1[9];
    b = col1[10];
    result[2] = (r + r + b + g + g + g + g + g)>>3;
    r = col1[12];
    g = col1[13];
    b = col1[14];
    result[3] = (r + r + b + g + g + g + g + g)>>3;

    col1 += 16;
    result += 4;
  }
}
__attribute__((noinline)) void std_convert_rgba_gray8(const uint8_t* col1, uint8_t* result, unsigned long count) {
  unsigned int r,g,b;
  const uint8_t* const max_ptr = result + count;

  while(result < max_ptr) {
    r = col1[0];
    g = col1[1];
    b = col1[2];
    result[0] = (r + r + b + g + g + g + g + g)>>3;

    col1 += 4;
    result += 1;
  }
}

/* BGRA to grayscale */
__attribute__((noinline)) void fast_convert_bgra_gray8(const uint8_t* col1, uint8_t* result, unsigned long count) {
  unsigned int r,g,b;
  const uint8_t* const max_ptr = result + count;

  while(result < max_ptr) {
    b = col1[0];
    g = col1[1];
    r = col1[2];
    result[0] = (r + r + b + g + g + g + g + g)>>3;
    b = col1[4];
    g = col1[5];
    r = col1[6];
    result[1] = (r + r + b + g + g + g + g + g)>>3;
    b = col1[8];
    g = col1[9];
    r = col1[10];
    result[2] = (r + r + b + g + g + g + g + g)>>3;
    b = col1[12];
    g = col1[13];
    r = col1[14];
    result[3] = (r + r + b + g + g + g + g + g)>>3;

    col1 += 16;
    result += 4;
  }
}

__attribute__((noinline)) void std_convert_bgra_gray8(const uint8_t* col1, uint8_t* result, unsigned long count) {
  unsigned int r,g,b;
  const uint8_t* const max_ptr = result + count;

  while(result < max_ptr) {
    b = col1[0];
    g = col1[1];
    r = col1[2];
    result[0] = (r + r + b + g + g + g + g + g)>>3;

    col1 += 4;
    result += 1;
  }
}
/* ARGB to grayscale */
__attribute__((noinline)) void fast_convert_argb_gray8(const uint8_t* col1, uint8_t* result, unsigned long count) {
  unsigned int r,g,b;
  const uint8_t* const max_ptr = result + count;

  while(result < max_ptr) {
    r = col1[1];
    g = col1[2];
    b = col1[3];
    result[0] = (r + r + b + g + g + g + g + g)>>3;
    r = col1[5];
    g = col1[6];
    b = col1[7];
    result[1] = (r + r + b + g + g + g + g + g)>>3;
    r = col1[9];
    g = col1[10];
    b = col1[11];
    result[2] = (r + r + b + g + g + g + g + g)>>3;
    r = col1[13];
    g = col1[14];
    b = col1[15];
    result[3] = (r + r + b + g + g + g + g + g)>>3;

    col1 += 16;
    result += 4;
  }
}
__attribute__((noinline)) void std_convert_argb_gray8(const uint8_t* col1, uint8_t* result, unsigned long count) {
  unsigned int r,g,b;
  const uint8_t* const max_ptr = result + count;

  while(result < max_ptr) {
    r = col1[1];
    g = col1[2];
    b = col1[3];
    result[0] = (r + r + b + g + g + g + g + g)>>3;

    col1 += 4;
    result += 1;
  }
}

/* ABGR to grayscale */
__attribute__((noinline)) void fast_convert_abgr_gray8(const uint8_t* col1, uint8_t* result, unsigned long count) {
  unsigned int r,g,b;
  const uint8_t* const max_ptr = result + count;

  while(result < max_ptr) {
    b = col1[1];
    g = col1[2];
    r = col1[3];
    result[0] = (r + r + b + g + g + g + g + g)>>3;
    b = col1[5];
    g = col1[6];
    r = col1[7];
    result[1] = (r + r + b + g + g + g + g + g)>>3;
    b = col1[9];
    g = col1[10];
    r = col1[11];
    result[2] = (r + r + b + g + g + g + g + g)>>3;
    b = col1[13];
    g = col1[14];
    r = col1[15];
    result[3] = (r + r + b + g + g + g + g + g)>>3;

    col1 += 16;
    result += 4;
  }
}
__attribute__((noinline)) void std_convert_abgr_gray8(const uint8_t* col1, uint8_t* result, unsigned long count) {
  unsigned int r,g,b;
  const uint8_t* const max_ptr = result + count;

  while(result < max_ptr) {
    b = col1[1];
    g = col1[2];
    r = col1[3];
    result[0] = (r + r + b + g + g + g + g + g)>>3;

    col1 += 4;
    result += 1;
  }
}

/* Converts a YUYV image into grayscale by extracting the Y channel */
__attribute__((noinline)) void fast_convert_yuyv_gray8(const uint8_t* col1, uint8_t* result, unsigned long count) {
  const uint16_t* yuvbuf = (const uint16_t*)col1;
  const uint8_t* const max_ptr = result + count;

  while(result < max_ptr) {
    result[0] = (uint8_t)yuvbuf[0];
    result[1] = (uint8_t)yuvbuf[1];
    result[2] = (uint8_t)yuvbuf[2];
    result[3] = (uint8_t)yuvbuf[3];
    result[4] = (uint8_t)yuvbuf[4];
    result[5] = (uint8_t)yuvbuf[5];
    result[6] = (uint8_t)yuvbuf[6];
    result[7] = (uint8_t)yuvbuf[7];
    result[8] = (uint8_t)yuvbuf[8];
    result[9] = (uint8_t)yuvbuf[9];
    result[10] = (uint8_t)yuvbuf[10];
    result[11] = (uint8_t)yuvbuf[11];
    result[12] = (uint8_t)yuvbuf[12];
    result[13] = (uint8_t)yuvbuf[13];
    result[14] = (uint8_t)yuvbuf[14];
    result[15] = (uint8_t)yuvbuf[15];

    yuvbuf += 16;
    result += 16;
  }
}
__attribute__((noinline)) void std_convert_yuyv_gray8(const uint8_t* col1, uint8_t* result, unsigned long count) {
  const uint16_t* yuvbuf = (const uint16_t*)col1;
  const uint8_t* const max_ptr = result + count;

  while(result < max_ptr) {
    result[0] = (uint8_t)yuvbuf[0];

    yuvbuf += 1;
    result += 1;
  }
}

/* RGB32 to grayscale SSSE3 */
#if defined(__i386__) || defined(__x86_64__)
__attribute__((noinline,__target__("ssse3")))
#endif
void ssse3_convert_rgb32_gray8(const uint8_t* col1, uint8_t* result, unsigned long count, uint32_t multiplier) {
#if ((defined(__i386__) || defined(__x86_64__) || defined(ZM_KEEP_SSE)) && !defined(ZM_STRIP_SSE))

  /* XMM0 - zero */
  /* XMM1 - col1 */
  /* XMM3 - multiplier */
  /* XMM4 - divide mask */

  __asm__ __volatile__ (
      "mov $0x1F1F1F1F, %%eax\n\t"
      "movd %%eax, %%xmm4\n\t"
      "pshufd $0x0, %%xmm4, %%xmm4\n\t"
      "mov %3, %%eax\n\t"
      "movd %%eax, %%xmm3\n\t"
      "pshufd $0x0, %%xmm3, %%xmm3\n\t"
      "pxor %%xmm0, %%xmm0\n\t"
      "sub $0x10, %0\n\t"
      "sub $0x4, %1\n\t"
      "ssse3_convert_rgb32_gray8_iter:\n\t"
      "movdqa (%0,%2,4), %%xmm1\n\t"
      "psrlq $0x3, %%xmm1\n\t"
      "pand %%xmm4, %%xmm1\n\t"
      "pmaddubsw %%xmm3, %%xmm1\n\t"
      "phaddw %%xmm0, %%xmm1\n\t"
      "packuswb %%xmm1, %%xmm1\n\t"
      "movd %%xmm1, %%eax\n\t"
      "movnti %%eax, (%1,%2)\n\t"
      "sub $0x4, %2\n\t"
      "jnz ssse3_convert_rgb32_gray8_iter\n\t"
      :
      : "r" (col1), "r" (result), "r" (count), "g" (multiplier)
      : "%eax", "%xmm0", "%xmm1", "%xmm3", "%xmm4", "cc", "memory"
        );
#else
  Panic("SSE function called on a non x86\\x86-64 platform");
#endif
}

/* RGBA to grayscale SSSE3 */
void ssse3_convert_rgba_gray8(const uint8_t* col1, uint8_t* result, unsigned long count) {
  ssse3_convert_rgb32_gray8(col1, result, count, 0x00010502);
}

/* BGRA to grayscale SSSE3 */
void ssse3_convert_bgra_gray8(const uint8_t* col1, uint8_t* result, unsigned long count) {
  ssse3_convert_rgb32_gray8(col1, result, count, 0x00020501);
}

/* ARGB to grayscale SSSE3 */
void ssse3_convert_argb_gray8(const uint8_t* col1, uint8_t* result, unsigned long count) {
  ssse3_convert_rgb32_gray8(col1, result, count, 0x01050200);
}

/* ABGR to grayscale SSSE3 */
void ssse3_convert_abgr_gray8(const uint8_t* col1, uint8_t* result, unsigned long count) {
  ssse3_convert_rgb32_gray8(col1, result, count, 0x02050100);
}

/* Converts a YUYV image into grayscale by extracting the Y channel */
#if defined(__i386__) || defined(__x86_64__)
__attribute__((noinline,__target__("ssse3")))
#endif
void ssse3_convert_yuyv_gray8(const uint8_t* col1, uint8_t* result, unsigned long count) {
#if ((defined(__i386__) || defined(__x86_64__) || defined(ZM_KEEP_SSE)) && !defined(ZM_STRIP_SSE))
  unsigned long i = 0;

  __attribute__((aligned(16))) static const uint8_t movemask1[16] = {0,2,4,6,8,10,12,14,0xFF,0xFF,0xFF,0xFF,0xFF,0xFF,0xFF,0xFF};
  __attribute__((aligned(16))) static const uint8_t movemask2[16] = {0xFF,0xFF,0xFF,0xFF,0xFF,0xFF,0xFF,0xFF,0,2,4,6,8,10,12,14};

  /* XMM0 - General purpose */
  /* XMM1 - General purpose */
  /* XMM2 - unused */
  /* XMM3 - shift mask 1 */
  /* XMM4 - shift mask 2 */
  /* XMM5 - unused*/
  /* XMM6 - unused */
  /* XMM7 - unused */

  __asm__ __volatile__ (
      "movdqa %4, %%xmm3\n\t"
      "movdqa %5, %%xmm4\n\t"
      "algo_ssse3_convert_yuyv_gray8:\n\t"
      "movdqa (%0), %%xmm0\n\t"
      "pshufb %%xmm3, %%xmm0\n\t"
      "movdqa 0x10(%0), %%xmm1\n\t"
      "pshufb %%xmm4, %%xmm1\n\t"
      "por %%xmm1, %%xmm0\n\t"
      "movntdq %%xmm0, (%1)\n\t"
      "add $0x10, %3\n\t"
      "add $0x10, %1\n\t"
      "add $0x20, %0\n\t"
      "cmp %2, %3\n\t"
      "jb algo_ssse3_convert_yuyv_gray8\n\t"
      :
#if (defined(_DEBUG) && !defined(__x86_64__)) /* Use one less register to allow compilation to success on 32bit with omit frame pointer disabled */
      : "r" (col1), "r" (result), "m" (count), "r" (i), "m" (*movemask1), "m" (*movemask2)
#else
      : "r" (col1), "r" (result), "r" (count), "r" (i), "m" (*movemask1), "m" (*movemask2)
#endif
      : "%xmm3", "%xmm4", "cc", "memory"
      );
#else
  Panic("SSE function called on a non x86\\x86-64 platform");
#endif
}

/* YUYV to RGB24 - relocated from zm_local_camera.cpp */
__attribute__((noinline)) void zm_convert_yuyv_rgb(const uint8_t* col1, uint8_t* result, unsigned long count) {
  unsigned int r,g,b;
  unsigned int y1,y2,u,v;
  for(unsigned int i=0; i < count; i += 2, col1 += 4, result += 6) {
    y1 = col1[0];
    u = col1[1];
    y2 = col1[2];
    v = col1[3];

    r = y1 + r_v_table[v];
    g = y1 - (g_u_table[u]+g_v_table[v]);
    b = y1 + b_u_table[u];

    result[0] = r<0?0:(r>255?255:r);
    result[1] = g<0?0:(g>255?255:g);
    result[2] = b<0?0:(b>255?255:b);

    r = y2 + r_v_table[v];
    g = y2 - (g_u_table[u]+g_v_table[v]);
    b = y2 + b_u_table[u];

    result[3] = r<0?0:(r>255?255:r);
    result[4] = g<0?0:(g>255?255:g);
    result[5] = b<0?0:(b>255?255:b);
  }

}

/* YUYV to RGBA - modified the one above */
__attribute__((noinline)) void zm_convert_yuyv_rgba(const uint8_t* col1, uint8_t* result, unsigned long count) {
  unsigned int r,g,b;
  unsigned int y1,y2,u,v;
  for(unsigned int i=0; i < count; i += 2, col1 += 4, result += 8) {
    y1 = col1[0];
    u = col1[1];
    y2 = col1[2];
    v = col1[3];

    r = y1 + r_v_table[v];
    g = y1 - (g_u_table[u]+g_v_table[v]);
    b = y1 + b_u_table[u];

    result[0] = r>255?255:r;
    result[1] = g>255?255:g;
    result[2] = b>255?255:b;

    r = y2 + r_v_table[v];
    g = y2 - (g_u_table[u]+g_v_table[v]);
    b = y2 + b_u_table[u];

    result[4] = r>255?255:r;
    result[5] = g>255?255:g;
    result[6] = b>255?255:b;
  }

}

/* RGB555 to RGB24 - relocated from zm_local_camera.cpp */
__attribute__((noinline)) void zm_convert_rgb555_rgb(const uint8_t* col1, uint8_t* result, unsigned long count) {
  unsigned int r,g,b;
  for(unsigned int i=0; i < count; i++, col1 += 2, result += 3) {
    b = ((*col1)<<3)&0xf8;
    g = (((*(col1+1))<<6)|((*col1)>>2))&0xf8;
    r = ((*(col1+1))<<1)&0xf8;
    result[0] = r;
    result[1] = g;
    result[2] = b;
  }
}

/* RGB555 to RGBA - modified the one above */
__attribute__((noinline)) void zm_convert_rgb555_rgba(const uint8_t* col1, uint8_t* result, unsigned long count) {
  unsigned int r,g,b;
  for(unsigned int i=0; i < count; i++, col1 += 2, result += 4) {
    b = ((*col1)<<3)&0xf8;
    g = (((*(col1+1))<<6)|((*col1)>>2))&0xf8;
    r = ((*(col1+1))<<1)&0xf8;
    result[0] = r;
    result[1] = g;
    result[2] = b;
  }
}

/* RGB565 to RGB24 - relocated from zm_local_camera.cpp */
__attribute__((noinline)) void zm_convert_rgb565_rgb(const uint8_t* col1, uint8_t* result, unsigned long count) {
  unsigned int r,g,b;
  for(unsigned int i=0; i < count; i++, col1 += 2, result += 3) {
    b = ((*col1)<<3)&0xf8;
    g = (((*(col1+1))<<5)|((*col1)>>3))&0xfc;
    r = (*(col1+1))&0xf8;
    result[0] = r;
    result[1] = g;
    result[2] = b;
  }
}

/* RGB565 to RGBA - modified the one above */
__attribute__((noinline)) void zm_convert_rgb565_rgba(const uint8_t* col1, uint8_t* result, unsigned long count) {
  unsigned int r,g,b;
  for ( unsigned int i=0; i < count; i++, col1 += 2, result += 4 ) {
    b = ((*col1)<<3)&0xf8;
    g = (((*(col1+1))<<5)|((*col1)>>3))&0xfc;
    r = (*(col1+1))&0xf8;
    result[0] = r;
    result[1] = g;
    result[2] = b;
  }
}

/************************************************* DEINTERLACE FUNCTIONS *************************************************/

/* Grayscale */
__attribute__((noinline)) void std_deinterlace_4field_gray8(uint8_t* col1, uint8_t* col2, unsigned int threshold, unsigned int width, unsigned int height) {
  uint8_t *pcurrent, *pabove, *pncurrent, *pnabove, *pbelow;
  const uint8_t* const max_ptr = col1 + (width*(height-1));
  const uint8_t *max_ptr2;

  pcurrent = col1 + width;
  pncurrent = col2 + width;
  pabove = col1;
  pnabove = col2;
  pbelow = col1 + (width*2);
  while(pcurrent < max_ptr) {
    max_ptr2 = pcurrent + width;
    while(pcurrent < max_ptr2) {
      if((unsigned int)((abs(*pnabove - *pabove) + abs(*pncurrent - *pcurrent)) >> 1) >= threshold) {
        *pcurrent = (*pabove + *pbelow) >> 1;
      }
      pabove++;
      pnabove++;
      pcurrent++;
      pncurrent++;
      pbelow++;
    }
    pcurrent += width;
    pncurrent += width;
    pabove += width;
    pnabove += width;
    pbelow += width;
  }

  /* Special case for the last line */
  max_ptr2 = pcurrent + width;
  while(pcurrent < max_ptr2) {
    if((unsigned int)((abs(*pnabove - *pabove) + abs(*pncurrent - *pcurrent)) >> 1) >= threshold) {
      *pcurrent = *pabove;
    }
    pabove++;
    pnabove++;
    pcurrent++;
    pncurrent++;
  }
}

/* RGB */
__attribute__((noinline)) void std_deinterlace_4field_rgb(uint8_t* col1, uint8_t* col2, unsigned int threshold, unsigned int width, unsigned int height) {
  uint8_t *pcurrent, *pabove, *pncurrent, *pnabove, *pbelow;
  const unsigned int row_width = width*3;
  const uint8_t* const max_ptr = col1 + (row_width * (height-1));
  const uint8_t *max_ptr2;
  unsigned int b, g, r;
  unsigned int delta1, delta2;

  pcurrent = col1 + (width*3);
  pncurrent = col2 + (width*3);
  pabove = col1;
  pnabove = col2;
  pbelow = col1 + ((width*2)*3);
  while(pcurrent < max_ptr) {
    max_ptr2 = pcurrent + row_width;
    while(pcurrent < max_ptr2) {
      r = abs(pnabove[0] - pabove[0]);
      g = abs(pnabove[1] - pabove[1]);
      b = abs(pnabove[2] - pabove[2]);
      delta1 = (r + r + b + g + g + g + g + g)>>3;
      r = abs(pncurrent[0] - pcurrent[0]);
      g = abs(pncurrent[1] - pcurrent[1]);
      b = abs(pncurrent[2] - pcurrent[2]);
      delta2 = (r + r + b + g + g + g + g + g)>>3;
      if(((delta1 + delta2) >> 1) >= threshold) {
        pcurrent[0] = (pabove[0] + pbelow[0]) >> 1;
        pcurrent[1] = (pabove[1] + pbelow[1]) >> 1;
        pcurrent[2] = (pabove[2] + pbelow[2]) >> 1;
      }
      pabove += 3;
      pnabove += 3;
      pcurrent += 3;
      pncurrent += 3;
      pbelow += 3;
    }
    pcurrent += row_width;
    pncurrent += row_width;
    pabove += row_width;
    pnabove += row_width;
    pbelow += row_width;
  }

  /* Special case for the last line */
  max_ptr2 = pcurrent + row_width;
  while(pcurrent < max_ptr2) {
    r = abs(pnabove[0] - pabove[0]);
    g = abs(pnabove[1] - pabove[1]);
    b = abs(pnabove[2] - pabove[2]);
    delta1 = (r + r + b + g + g + g + g + g)>>3;
    r = abs(pncurrent[0] - pcurrent[0]);
    g = abs(pncurrent[1] - pcurrent[1]);
    b = abs(pncurrent[2] - pcurrent[2]);
    delta2 = (r + r + b + g + g + g + g + g)>>3;
    if(((delta1 + delta2) >> 1) >= threshold) {
      pcurrent[0] = pabove[0];
      pcurrent[1] = pabove[1];
      pcurrent[2] = pabove[2];
    }
    pabove += 3;
    pnabove += 3;
    pcurrent += 3;
    pncurrent += 3;
  }
}

/* BGR */
__attribute__((noinline)) void std_deinterlace_4field_bgr(uint8_t* col1, uint8_t* col2, unsigned int threshold, unsigned int width, unsigned int height) {
  uint8_t *pcurrent, *pabove, *pncurrent, *pnabove, *pbelow;
  const unsigned int row_width = width*3;
  const uint8_t* const max_ptr = col1 + (row_width * (height-1));
  const uint8_t *max_ptr2;
  unsigned int b, g, r;
  unsigned int delta1, delta2;

  pcurrent = col1 + (width*3);
  pncurrent = col2 + (width*3);
  pabove = col1;
  pnabove = col2;
  pbelow = col1 + ((width*2)*3);
  while(pcurrent < max_ptr) {
    max_ptr2 = pcurrent + row_width;
    while(pcurrent < max_ptr2) {
      b = abs(pnabove[0] - pabove[0]);
      g = abs(pnabove[1] - pabove[1]);
      r = abs(pnabove[2] - pabove[2]);
      delta1 = (r + r + b + g + g + g + g + g)>>3;
      b = abs(pncurrent[0] - pcurrent[0]);
      g = abs(pncurrent[1] - pcurrent[1]);
      r = abs(pncurrent[2] - pcurrent[2]);
      delta2 = (r + r + b + g + g + g + g + g)>>3;
      if(((delta1 + delta2) >> 1) >= threshold) {
        pcurrent[0] = (pabove[0] + pbelow[0]) >> 1;
        pcurrent[1] = (pabove[1] + pbelow[1]) >> 1;
        pcurrent[2] = (pabove[2] + pbelow[2]) >> 1;
      }
      pabove += 3;
      pnabove += 3;
      pcurrent += 3;
      pncurrent += 3;
      pbelow += 3;
    }
    pcurrent += row_width;
    pncurrent += row_width;
    pabove += row_width;
    pnabove += row_width;
    pbelow += row_width;

  }

  /* Special case for the last line */
  max_ptr2 = pcurrent + row_width;
  while(pcurrent < max_ptr2) {
    b = abs(pnabove[0] - pabove[0]);
    g = abs(pnabove[1] - pabove[1]);
    r = abs(pnabove[2] - pabove[2]);
    delta1 = (r + r + b + g + g + g + g + g)>>3;
    b = abs(pncurrent[0] - pcurrent[0]);
    g = abs(pncurrent[1] - pcurrent[1]);
    r = abs(pncurrent[2] - pcurrent[2]);
    delta2 = (r + r + b + g + g + g + g + g)>>3;
    if(((delta1 + delta2) >> 1) >= threshold) {
      pcurrent[0] = pabove[0];
      pcurrent[1] = pabove[1];
      pcurrent[2] = pabove[2];
    }
    pabove += 3;
    pnabove += 3;
    pcurrent += 3;
    pncurrent += 3;
  }
}

/* RGBA */
__attribute__((noinline)) void std_deinterlace_4field_rgba(uint8_t* col1, uint8_t* col2, unsigned int threshold, unsigned int width, unsigned int height) {
  uint8_t *pcurrent, *pabove, *pncurrent, *pnabove, *pbelow;
  const unsigned int row_width = width*4;
  const uint8_t* const max_ptr = col1 + (row_width * (height-1));
  const uint8_t *max_ptr2;
  unsigned int b, g, r;
  unsigned int delta1, delta2;

  pcurrent = col1 + row_width;
  pncurrent = col2 + row_width;
  pabove = col1;
  pnabove = col2;
  pbelow = col1 + (row_width*2);
  while(pcurrent < max_ptr) {
    max_ptr2 = pcurrent + row_width;
    while(pcurrent < max_ptr2) {
      r = abs(pnabove[0] - pabove[0]);
      g = abs(pnabove[1] - pabove[1]);
      b = abs(pnabove[2] - pabove[2]);
      delta1 = (r + r + b + g + g + g + g + g)>>3;
      r = abs(pncurrent[0] - pcurrent[0]);
      g = abs(pncurrent[1] - pcurrent[1]);
      b = abs(pncurrent[2] - pcurrent[2]);
      delta2 = (r + r + b + g + g + g + g + g)>>3;
      if(((delta1 + delta2) >> 1) >= threshold) {
        pcurrent[0] = (pabove[0] + pbelow[0]) >> 1;
        pcurrent[1] = (pabove[1] + pbelow[1]) >> 1;
        pcurrent[2] = (pabove[2] + pbelow[2]) >> 1;
      }
      pabove += 4;
      pnabove += 4;
      pcurrent += 4;
      pncurrent += 4;
      pbelow += 4;
    }
    pcurrent += row_width;
    pncurrent += row_width;
    pabove += row_width;
    pnabove += row_width;
    pbelow += row_width;
  }

  /* Special case for the last line */
  max_ptr2 = pcurrent + row_width;
  while(pcurrent < max_ptr2) {
    r = abs(pnabove[0] - pabove[0]);
    g = abs(pnabove[1] - pabove[1]);
    b = abs(pnabove[2] - pabove[2]);
    delta1 = (r + r + b + g + g + g + g + g)>>3;
    r = abs(pncurrent[0] - pcurrent[0]);
    g = abs(pncurrent[1] - pcurrent[1]);
    b = abs(pncurrent[2] - pcurrent[2]);
    delta2 = (r + r + b + g + g + g + g + g)>>3;
    if(((delta1 + delta2) >> 1) >= threshold) {
      pcurrent[0] = pabove[0];
      pcurrent[1] = pabove[1];
      pcurrent[2] = pabove[2];
    }
    pabove += 4;
    pnabove += 4;
    pcurrent += 4;
    pncurrent += 4;
  }
}

/* BGRA */
__attribute__((noinline)) void std_deinterlace_4field_bgra(uint8_t* col1, uint8_t* col2, unsigned int threshold, unsigned int width, unsigned int height) {
  uint8_t *pcurrent, *pabove, *pncurrent, *pnabove, *pbelow;
  const unsigned int row_width = width*4;
  const uint8_t* const max_ptr = col1 + (row_width * (height-1));
  const uint8_t *max_ptr2;
  unsigned int b, g, r;
  unsigned int delta1, delta2;

  pcurrent = col1 + row_width;
  pncurrent = col2 + row_width;
  pabove = col1;
  pnabove = col2;
  pbelow = col1 + (row_width*2);
  while(pcurrent < max_ptr) {
    max_ptr2 = pcurrent + row_width;
    while(pcurrent < max_ptr2) {
      b = abs(pnabove[0] - pabove[0]);
      g = abs(pnabove[1] - pabove[1]);
      r = abs(pnabove[2] - pabove[2]);
      delta1 = (r + r + b + g + g + g + g + g)>>3;
      b = abs(pncurrent[0] - pcurrent[0]);
      g = abs(pncurrent[1] - pcurrent[1]);
      r = abs(pncurrent[2] - pcurrent[2]);
      delta2 = (r + r + b + g + g + g + g + g)>>3;
      if(((delta1 + delta2) >> 1) >= threshold) {
        pcurrent[0] = (pabove[0] + pbelow[0]) >> 1;
        pcurrent[1] = (pabove[1] + pbelow[1]) >> 1;
        pcurrent[2] = (pabove[2] + pbelow[2]) >> 1;
      }
      pabove += 4;
      pnabove += 4;
      pcurrent += 4;
      pncurrent += 4;
      pbelow += 4;
    }
    pcurrent += row_width;
    pncurrent += row_width;
    pabove += row_width;
    pnabove += row_width;
    pbelow += row_width;
  }

  /* Special case for the last line */
  max_ptr2 = pcurrent + row_width;
  while(pcurrent < max_ptr2) {
    b = abs(pnabove[0] - pabove[0]);
    g = abs(pnabove[1] - pabove[1]);
    r = abs(pnabove[2] - pabove[2]);
    delta1 = (r + r + b + g + g + g + g + g)>>3;
    b = abs(pncurrent[0] - pcurrent[0]);
    g = abs(pncurrent[1] - pcurrent[1]);
    r = abs(pncurrent[2] - pcurrent[2]);
    delta2 = (r + r + b + g + g + g + g + g)>>3;
    if(((delta1 + delta2) >> 1) >= threshold) {
      pcurrent[0] = pabove[0];
      pcurrent[1] = pabove[1];
      pcurrent[2] = pabove[2];
    }
    pabove += 4;
    pnabove += 4;
    pcurrent += 4;
    pncurrent += 4;
  }
}

/* ARGB */
__attribute__((noinline)) void std_deinterlace_4field_argb(uint8_t* col1, uint8_t* col2, unsigned int threshold, unsigned int width, unsigned int height) {
  uint8_t *pcurrent, *pabove, *pncurrent, *pnabove, *pbelow;
  const unsigned int row_width = width*4;
  const uint8_t* const max_ptr = col1 + (row_width * (height-1));
  const uint8_t *max_ptr2;
  unsigned int b, g, r;
  unsigned int delta1, delta2;

  pcurrent = col1 + row_width;
  pncurrent = col2 + row_width;
  pabove = col1;
  pnabove = col2;
  pbelow = col1 + (row_width*2);
  while(pcurrent < max_ptr) {
    max_ptr2 = pcurrent + row_width;
    while(pcurrent < max_ptr2) {
      r = abs(pnabove[1] - pabove[1]);
      g = abs(pnabove[2] - pabove[2]);
      b = abs(pnabove[3] - pabove[3]);
      delta1 = (r + r + b + g + g + g + g + g)>>3;
      r = abs(pncurrent[1] - pcurrent[1]);
      g = abs(pncurrent[2] - pcurrent[2]);
      b = abs(pncurrent[3] - pcurrent[3]);
      delta2 = (r + r + b + g + g + g + g + g)>>3;
      if(((delta1 + delta2) >> 1) >= threshold) {
        pcurrent[1] = (pabove[1] + pbelow[1]) >> 1;
        pcurrent[2] = (pabove[2] + pbelow[2]) >> 1;
        pcurrent[3] = (pabove[3] + pbelow[3]) >> 1;
      }
      pabove += 4;
      pnabove += 4;
      pcurrent += 4;
      pncurrent += 4;
      pbelow += 4;
    }
    pcurrent += row_width;
    pncurrent += row_width;
    pabove += row_width;
    pnabove += row_width;
    pbelow += row_width;
  }

  /* Special case for the last line */
  max_ptr2 = pcurrent + row_width;
  while(pcurrent < max_ptr2) {
    r = abs(pnabove[1] - pabove[1]);
    g = abs(pnabove[2] - pabove[2]);
    b = abs(pnabove[3] - pabove[3]);
    delta1 = (r + r + b + g + g + g + g + g)>>3;
    r = abs(pncurrent[1] - pcurrent[1]);
    g = abs(pncurrent[2] - pcurrent[2]);
    b = abs(pncurrent[3] - pcurrent[3]);
    delta2 = (r + r + b + g + g + g + g + g)>>3;
    if(((delta1 + delta2) >> 1) >= threshold) {
      pcurrent[1] = pabove[1];
      pcurrent[2] = pabove[2];
      pcurrent[3] = pabove[3];
    }
    pabove += 4;
    pnabove += 4;
    pcurrent += 4;
    pncurrent += 4;
  }
}

/* ABGR */
__attribute__((noinline)) void std_deinterlace_4field_abgr(uint8_t* col1, uint8_t* col2, unsigned int threshold, unsigned int width, unsigned int height) {
  uint8_t *pcurrent, *pabove, *pncurrent, *pnabove, *pbelow;
  const unsigned int row_width = width*4;
  const uint8_t* const max_ptr = col1 + (row_width * (height-1));
  const uint8_t *max_ptr2;
  unsigned int b, g, r;
  unsigned int delta1, delta2;

  pcurrent = col1 + row_width;
  pncurrent = col2 + row_width;
  pabove = col1;
  pnabove = col2;
  pbelow = col1 + (row_width*2);
  while(pcurrent < max_ptr) {
    max_ptr2 = pcurrent + row_width;
    while(pcurrent < max_ptr2) {
      b = abs(pnabove[1] - pabove[1]);
      g = abs(pnabove[2] - pabove[2]);
      r = abs(pnabove[3] - pabove[3]);
      delta1 = (r + r + b + g + g + g + g + g)>>3;
      b = abs(pncurrent[1] - pcurrent[1]);
      g = abs(pncurrent[2] - pcurrent[2]);
      r = abs(pncurrent[3] - pcurrent[3]);
      delta2 = (r + r + b + g + g + g + g + g)>>3;
      if(((delta1 + delta2) >> 1) >= threshold) {
        pcurrent[1] = (pabove[1] + pbelow[1]) >> 1;
        pcurrent[2] = (pabove[2] + pbelow[2]) >> 1;
        pcurrent[3] = (pabove[3] + pbelow[3]) >> 1;
      }
      pabove += 4;
      pnabove += 4;
      pcurrent += 4;
      pncurrent += 4;
      pbelow += 4;
    }
    pcurrent += row_width;
    pncurrent += row_width;
    pabove += row_width;
    pnabove += row_width;
    pbelow += row_width;

  }

  /* Special case for the last line */
  max_ptr2 = pcurrent + row_width;
  while(pcurrent < max_ptr2) {
    b = abs(pnabove[1] - pabove[1]);
    g = abs(pnabove[2] - pabove[2]);
    r = abs(pnabove[3] - pabove[3]);
    delta1 = (r + r + b + g + g + g + g + g)>>3;
    b = abs(pncurrent[1] - pcurrent[1]);
    g = abs(pncurrent[2] - pcurrent[2]);
    r = abs(pncurrent[3] - pcurrent[3]);
    delta2 = (r + r + b + g + g + g + g + g)>>3;
    if(((delta1 + delta2) >> 1) >= threshold) {
      pcurrent[1] = pabove[1];
      pcurrent[2] = pabove[2];
      pcurrent[3] = pabove[3];
    }
    pabove += 4;
    pnabove += 4;
    pcurrent += 4;
    pncurrent += 4;
  }
}
