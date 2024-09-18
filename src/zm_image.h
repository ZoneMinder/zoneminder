//
// ZoneMinder Image Class Interface, $Date$, $Revision$
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

#ifndef ZM_IMAGE_H
#define ZM_IMAGE_H

#include "zm_ffmpeg.h"
#include "zm_jpeg.h"
#include "zm_logger.h"
#include "zm_mem_utils.h"
#include "zm_rgb.h"
#include "zm_time.h"
#include "zm_vector2.h"

#if HAVE_ZLIB_H
#include <zlib.h>
#endif // HAVE_ZLIB_H

class Box;
class Polygon;

#define ZM_BUFTYPE_DONTFREE 0
#define ZM_BUFTYPE_MALLOC 1
#define ZM_BUFTYPE_NEW 2
#define ZM_BUFTYPE_AVMALLOC 3
#define ZM_BUFTYPE_ZM 4

typedef void (*blend_fptr_t)(const uint8_t*, const uint8_t*, uint8_t*, unsigned long, double);
typedef void (*delta_fptr_t)(const uint8_t*, const uint8_t*, uint8_t*, unsigned long);
typedef void (*convert_fptr_t)(const uint8_t*, uint8_t*, unsigned long);
typedef void (*deinterlace_4field_fptr_t)(uint8_t*, uint8_t*, unsigned int, unsigned int, unsigned int);
typedef void* (*imgbufcpy_fptr_t)(void*, const void*, size_t);

extern imgbufcpy_fptr_t fptr_imgbufcpy;

/* Should be called from Image class functions */
inline static uint8_t* AllocBuffer(size_t p_bufsize) {
  uint8_t* buffer = (uint8_t*)zm_mallocaligned(64, p_bufsize);
  if ( buffer == nullptr )
    Fatal("Memory allocation failed: %s", strerror(errno));

  return buffer;
}

inline static void DumpBuffer(uint8_t* buffer, int buffertype) {
  if ( buffer && (buffertype != ZM_BUFTYPE_DONTFREE) ) {
    if ( buffertype == ZM_BUFTYPE_ZM ) {
      zm_freealigned(buffer);
    } else if ( buffertype == ZM_BUFTYPE_MALLOC ) {
      free(buffer);
    } else if ( buffertype == ZM_BUFTYPE_NEW ) {
      delete buffer;
      /*else if(buffertype == ZM_BUFTYPE_AVMALLOC)
      	av_free(buffer);
      */
    } else {
      Error("Unknown buffer type in DumpBuffer(%d)", buffertype);
    }
  }
}


//
// This is image class, and represents a frame captured from a
// camera in raw form.
//
class Image {
 private:
  delta_fptr_t delta8_rgb;
  delta_fptr_t delta8_bgr;
  delta_fptr_t delta8_rgba;
  delta_fptr_t delta8_bgra;
  delta_fptr_t delta8_argb;
  delta_fptr_t delta8_abgr;
  delta_fptr_t delta8_gray8;

  // Per object function pointer that we can set once we know the image dimensions
  blend_fptr_t blend;

  void update_function_pointers();

 protected:
  inline void AllocImgBuffer(size_t p_bufsize) {
    if ( buffer )
      DumpImgBuffer();

    buffer = AllocBuffer(p_bufsize);
    buffertype = ZM_BUFTYPE_ZM;
    allocation = p_bufsize;
  }

 public:
  enum { ZM_CHAR_HEIGHT=11, ZM_CHAR_WIDTH=6 };
  enum { LINE_HEIGHT=ZM_CHAR_HEIGHT+0 };

 protected:
  static bool initialised;
  static unsigned char *abs_table;
  static unsigned char *y_r_table;
  static unsigned char *y_g_table;
  static unsigned char *y_b_table;
  static jpeg_compress_struct *writejpg_ccinfo[101];
  static jpeg_compress_struct *encodejpg_ccinfo[101];
  static jpeg_decompress_struct *readjpg_dcinfo;
  static jpeg_decompress_struct *decodejpg_dcinfo;
  static struct zm_error_mgr jpg_err;

  unsigned int width;
  unsigned int linesize;
  unsigned int height;
  unsigned int pixels;
  unsigned int colours;
  unsigned int padding;
  unsigned int size;
  unsigned int subpixelorder;
  unsigned long allocation;
  _AVPIXELFORMAT      imagePixFormat;
  uint8_t *buffer;
  int buffertype; /* 0=not ours, no need to call free(), 1=malloc() buffer, 2=new buffer */
  int holdbuffer; /* Hold the buffer instead of replacing it with new one */
  std::string annotation_;
  std::string filename_;

 public:
  Image();
  explicit Image(const std::string &filename);
  Image(int p_width, int p_height, int p_colours, int p_subpixelorder, uint8_t *p_buffer=0, unsigned int padding=0);
  Image(int p_width, int p_linesize, int p_height, int p_colours, int p_subpixelorder, uint8_t *p_buffer=0, unsigned int padding=0);
  explicit Image(const Image &p_image);
  explicit Image(const AVFrame *frame, int p_width=-1, int p_height=-1);

  ~Image();

  static void Initialise();
  static void Deinitialise();

  inline void DumpImgBuffer() {
    if (buffertype != ZM_BUFTYPE_DONTFREE)
      DumpBuffer(buffer, buffertype);
    buffertype = ZM_BUFTYPE_DONTFREE;
    buffer = nullptr;
    allocation = 0;
  }
  inline unsigned int Width() const { return width; }
  inline unsigned int LineSize() const { return linesize; }
  inline unsigned int Height() const { return height; }
  inline unsigned int Pixels() const { return pixels; }
  inline unsigned int Colours() const { return colours; }
  inline unsigned int SubpixelOrder() const { return subpixelorder; }
  inline unsigned int Size() const { return size; }
  std::string Filename() const { return filename_; }

  AVPixelFormat AVPixFormat() const;

  inline uint8_t* Buffer() { return buffer; }
  inline const uint8_t* Buffer() const { return buffer; }
  inline uint8_t* Buffer(unsigned int x, unsigned int y=0) { return &buffer[(y*linesize) + x*colours]; }
  inline const uint8_t* Buffer(unsigned int x, unsigned int y=0) const { return &buffer[(y*linesize) + x*colours]; }

  /* Request writeable buffer */
  uint8_t* WriteBuffer(const unsigned int p_width, const unsigned int p_height, const unsigned int p_colours, const unsigned int p_subpixelorder);
  // Is only acceptable on a pre-allocated buffer
  uint8_t* WriteBuffer() { return holdbuffer ? buffer : nullptr; };

  inline int IsBufferHeld() const { return holdbuffer; }
  inline void HoldBuffer(int tohold) { holdbuffer = tohold; }

  inline void Empty() {
    if ( !holdbuffer )
      DumpImgBuffer();

    width = linesize = height = colours = size = pixels = subpixelorder = 0;
  }

  void Assign(
    unsigned int p_width,
    unsigned int p_height,
    unsigned int p_colours,
    unsigned int p_subpixelorder,
    const uint8_t* new_buffer,
    const size_t buffer_size);
  void Assign(const Image &image);
  bool Assign(const AVFrame *frame);
  bool Assign(const AVFrame *frame, SwsContext *convert_context, AVFrame *temp_frame);
  void AssignDirect(
    const unsigned int p_width,
    const unsigned int p_height,
    const unsigned int p_colours,
    const unsigned int p_subpixelorder,
    uint8_t *new_buffer,
    const size_t buffer_size,
    const int p_buffertype);

  int PopulateFrame(AVFrame *frame) const;

  inline void CopyBuffer(const Image &image) {
    Assign(image);
  }
  inline Image &operator=(const Image &image) {
    Assign(image);
    return *this;
  }
  inline Image &operator=(const unsigned char *new_buffer) {
    (*fptr_imgbufcpy)(buffer, new_buffer, size);
    return *this;
  }

  bool ReadRaw(const std::string &filename);
  bool WriteRaw(const std::string &filename) const;

  bool ReadJpeg(const std::string &filename, unsigned int p_colours, unsigned int p_subpixelorder);

  bool WriteJpeg(const std::string &filename) const;
  bool WriteJpeg(const std::string &filename, bool on_blocking_abort) const;
  bool WriteJpeg(const std::string &filename, int quality_override) const;
  bool WriteJpeg(const std::string &filename, SystemTimePoint timestamp) const;
  bool WriteJpeg(const std::string &filename, int quality_override, SystemTimePoint timestamp) const;
  bool WriteJpeg(const std::string &filename,
                 const int &quality_override,
                 SystemTimePoint timestamp,
                 bool on_blocking_abort) const;
  bool WriteJpeg(const std::string &filename,
                 AVCodecContext *p_jpegcodeccontext,
                 SwsContext *p_jpegswscontext) const;

  bool DecodeJpeg(const JOCTET *inbuffer, int inbuffer_size, unsigned int p_colours, unsigned int p_subpixelorder);
  bool EncodeJpeg(JOCTET *outbuffer, int *outbuffer_size, int quality_override=0) const;
  bool EncodeJpeg(JOCTET *outbuffer, int *outbuffer_size, AVCodecContext *p_jpegcodeccontext, SwsContext *p_jpegswscontext) const;

#if HAVE_ZLIB_H
  bool Unzip(const Bytef *inbuffer, unsigned long inbuffer_size);
  bool Zip(Bytef *outbuffer, unsigned long *outbuffer_size, int compression_level=Z_BEST_SPEED) const;
#endif // HAVE_ZLIB_H

  bool Crop(unsigned int lo_x, unsigned int lo_y, unsigned int hi_x, unsigned int hi_y);
  bool Crop(const Box &limits);

  void Overlay(const Image &image);
  void Overlay(const Image &image, unsigned int x, unsigned int y);
  void Blend(const Image &image, int transparency=12);
  static Image *Merge( unsigned int n_images, Image *images[] );
  static Image *Merge( unsigned int n_images, Image *images[], double weight );
  static Image *Highlight(unsigned int n_images, Image *images[], Rgb threshold = kRGBBlack, Rgb ref_colour = kRGBRed);
  //Image *Delta( const Image &image ) const;
  void Delta( const Image &image, Image* targetimage) const;

  const Vector2 centreCoord(const char *text, const int size) const;
  void MaskPrivacy( const unsigned char *p_bitmask, const Rgb pixel_colour=0x00222222 );
  void Annotate(const std::string &text,
                const Vector2 &coord,
                uint8 size = 1,
                Rgb fg_colour = kRGBWhite,
                Rgb bg_colour = kRGBBlack);
  Image *HighlightEdges( Rgb colour, unsigned int p_colours, unsigned int p_subpixelorder, const Box *limits=0 );
  //Image *HighlightEdges( Rgb colour, const Polygon &polygon );
  void Timestamp(const char *label, SystemTimePoint when, const Vector2 &coord, int label_size);
  void Colourise(const unsigned int p_reqcolours, const unsigned int p_reqsubpixelorder);
  void DeColourise();

  void Clear() { memset( buffer, 0, size ); }
  void Fill( Rgb colour, const Box *limits=0 );
  void Fill( Rgb colour, int density, const Box *limits=0 );
  void Outline( Rgb colour, const Polygon &polygon );
  void Fill(Rgb colour, const Polygon &polygon) { Fill(colour, 1, polygon); };
  void Fill(Rgb colour, int density, const Polygon &polygon);

  void Rotate( int angle );
  void Flip( bool leftright );
  void Scale( unsigned int factor );
  void Scale(unsigned int x, unsigned int y);

  void Deinterlace_Discard();
  void Deinterlace_Linear();
  void Deinterlace_Blend();
  void Deinterlace_Blend_CustomRatio(int divider);
  void Deinterlace_4Field(const Image* next_image, unsigned int threshold);
};

// Scan-line polygon fill algorithm
namespace PolygonFill {
class Edge {
 public:
  Edge() = default;
  Edge(int32 min_y, int32 max_y, double min_x, double _1_m) : min_y(min_y), max_y(max_y), min_x(min_x), _1_m(_1_m) {}

  static bool CompareYX(const Edge &e1, const Edge &e2) {
    if (e1.min_y == e2.min_y) {
      return e1.min_x < e2.min_x;
    }
    return e1.min_y < e2.min_y;
  }

  static bool CompareX(const Edge &e1, const Edge &e2) {
    return e1.min_x < e2.min_x;
  }

 public:
  int32 min_y;
  int32 max_y;
  double min_x;
  double _1_m;
};
}

#endif // ZM_IMAGE_H

/* Blend functions */
void sse2_fastblend(const uint8_t* col1, const uint8_t* col2, uint8_t* result, unsigned long count, double blendpercent);
void std_fastblend(const uint8_t* col1, const uint8_t* col2, uint8_t* result, unsigned long count, double blendpercent);
void neon32_armv7_fastblend(const uint8_t* col1, const uint8_t* col2, uint8_t* result, unsigned long count, double blendpercent);
void neon64_armv8_fastblend(const uint8_t* col1, const uint8_t* col2, uint8_t* result, unsigned long count, double blendpercent);
void std_blend(const uint8_t* col1, const uint8_t* col2, uint8_t* result, unsigned long count, double blendpercent);

/* Delta functions */
void std_delta8_gray8(const uint8_t* col1, const uint8_t* col2, uint8_t* result, unsigned long count);
void std_delta8_rgb(const uint8_t* col1, const uint8_t* col2, uint8_t* result, unsigned long count);
void std_delta8_bgr(const uint8_t* col1, const uint8_t* col2, uint8_t* result, unsigned long count);
void std_delta8_rgba(const uint8_t* col1, const uint8_t* col2, uint8_t* result, unsigned long count);
void std_delta8_bgra(const uint8_t* col1, const uint8_t* col2, uint8_t* result, unsigned long count);
void std_delta8_argb(const uint8_t* col1, const uint8_t* col2, uint8_t* result, unsigned long count);
void std_delta8_abgr(const uint8_t* col1, const uint8_t* col2, uint8_t* result, unsigned long count);

void fast_delta8_gray8(const uint8_t* col1, const uint8_t* col2, uint8_t* result, unsigned long count);
void fast_delta8_rgb(const uint8_t* col1, const uint8_t* col2, uint8_t* result, unsigned long count);
void fast_delta8_bgr(const uint8_t* col1, const uint8_t* col2, uint8_t* result, unsigned long count);
void fast_delta8_rgba(const uint8_t* col1, const uint8_t* col2, uint8_t* result, unsigned long count);
void fast_delta8_bgra(const uint8_t* col1, const uint8_t* col2, uint8_t* result, unsigned long count);
void fast_delta8_argb(const uint8_t* col1, const uint8_t* col2, uint8_t* result, unsigned long count);
void fast_delta8_abgr(const uint8_t* col1, const uint8_t* col2, uint8_t* result, unsigned long count);

void neon32_armv7_delta8_gray8(const uint8_t* col1, const uint8_t* col2, uint8_t* result, unsigned long count);
void neon32_armv7_delta8_rgba(const uint8_t* col1, const uint8_t* col2, uint8_t* result, unsigned long count);
void neon32_armv7_delta8_bgra(const uint8_t* col1, const uint8_t* col2, uint8_t* result, unsigned long count);
void neon32_armv7_delta8_argb(const uint8_t* col1, const uint8_t* col2, uint8_t* result, unsigned long count);
void neon32_armv7_delta8_abgr(const uint8_t* col1, const uint8_t* col2, uint8_t* result, unsigned long count);
void neon64_armv8_delta8_gray8(const uint8_t* col1, const uint8_t* col2, uint8_t* result, unsigned long count);
void neon64_armv8_delta8_rgba(const uint8_t* col1, const uint8_t* col2, uint8_t* result, unsigned long count);
void neon64_armv8_delta8_bgra(const uint8_t* col1, const uint8_t* col2, uint8_t* result, unsigned long count);
void neon64_armv8_delta8_argb(const uint8_t* col1, const uint8_t* col2, uint8_t* result, unsigned long count);
void neon64_armv8_delta8_abgr(const uint8_t* col1, const uint8_t* col2, uint8_t* result, unsigned long count);
void sse2_delta8_gray8(const uint8_t* col1, const uint8_t* col2, uint8_t* result, unsigned long count);
void sse2_delta8_rgba(const uint8_t* col1, const uint8_t* col2, uint8_t* result, unsigned long count);
void sse2_delta8_bgra(const uint8_t* col1, const uint8_t* col2, uint8_t* result, unsigned long count);
void sse2_delta8_argb(const uint8_t* col1, const uint8_t* col2, uint8_t* result, unsigned long count);
void sse2_delta8_abgr(const uint8_t* col1, const uint8_t* col2, uint8_t* result, unsigned long count);
void ssse3_delta8_rgba(const uint8_t* col1, const uint8_t* col2, uint8_t* result, unsigned long count);
void ssse3_delta8_bgra(const uint8_t* col1, const uint8_t* col2, uint8_t* result, unsigned long count);
void ssse3_delta8_argb(const uint8_t* col1, const uint8_t* col2, uint8_t* result, unsigned long count);
void ssse3_delta8_abgr(const uint8_t* col1, const uint8_t* col2, uint8_t* result, unsigned long count);

/* Convert functions */
void std_convert_rgb_gray8(const uint8_t* col1, uint8_t* result, unsigned long count);
void std_convert_bgr_gray8(const uint8_t* col1, uint8_t* result, unsigned long count);
void std_convert_rgba_gray8(const uint8_t* col1, uint8_t* result, unsigned long count);
void std_convert_bgra_gray8(const uint8_t* col1, uint8_t* result, unsigned long count);
void std_convert_argb_gray8(const uint8_t* col1, uint8_t* result, unsigned long count);
void std_convert_abgr_gray8(const uint8_t* col1, uint8_t* result, unsigned long count);
void std_convert_yuyv_gray8(const uint8_t* col1, uint8_t* result, unsigned long count);

void fast_convert_rgb_gray8(const uint8_t* col1, uint8_t* result, unsigned long count);
void fast_convert_bgr_gray8(const uint8_t* col1, uint8_t* result, unsigned long count);
void fast_convert_rgba_gray8(const uint8_t* col1, uint8_t* result, unsigned long count);
void fast_convert_bgra_gray8(const uint8_t* col1, uint8_t* result, unsigned long count);
void fast_convert_argb_gray8(const uint8_t* col1, uint8_t* result, unsigned long count);
void fast_convert_abgr_gray8(const uint8_t* col1, uint8_t* result, unsigned long count);
void fast_convert_yuyv_gray8(const uint8_t* col1, uint8_t* result, unsigned long count);

void ssse3_convert_rgba_gray8(const uint8_t* col1, uint8_t* result, unsigned long count);
void ssse3_convert_bgra_gray8(const uint8_t* col1, uint8_t* result, unsigned long count);
void ssse3_convert_argb_gray8(const uint8_t* col1, uint8_t* result, unsigned long count);
void ssse3_convert_abgr_gray8(const uint8_t* col1, uint8_t* result, unsigned long count);
void ssse3_convert_yuyv_gray8(const uint8_t* col1, uint8_t* result, unsigned long count);
void zm_convert_yuyv_rgb(const uint8_t* col1, uint8_t* result, unsigned long count);
void zm_convert_yuyv_rgba(const uint8_t* col1, uint8_t* result, unsigned long count);
void zm_convert_rgb555_rgb(const uint8_t* col1, uint8_t* result, unsigned long count);
void zm_convert_rgb555_rgba(const uint8_t* col1, uint8_t* result, unsigned long count);
void zm_convert_rgb565_rgb(const uint8_t* col1, uint8_t* result, unsigned long count);
void zm_convert_rgb565_rgba(const uint8_t* col1, uint8_t* result, unsigned long count);

/* Deinterlace_4Field functions */
void std_deinterlace_4field_gray8(uint8_t* col1, uint8_t* col2, unsigned int threshold, unsigned int width, unsigned int height);
void std_deinterlace_4field_rgb(uint8_t* col1, uint8_t* col2, unsigned int threshold, unsigned int width, unsigned int height);
void std_deinterlace_4field_bgr(uint8_t* col1, uint8_t* col2, unsigned int threshold, unsigned int width, unsigned int height);
void std_deinterlace_4field_rgba(uint8_t* col1, uint8_t* col2, unsigned int threshold, unsigned int width, unsigned int height);
void std_deinterlace_4field_bgra(uint8_t* col1, uint8_t* col2, unsigned int threshold, unsigned int width, unsigned int height);
void std_deinterlace_4field_argb(uint8_t* col1, uint8_t* col2, unsigned int threshold, unsigned int width, unsigned int height);
void std_deinterlace_4field_abgr(uint8_t* col1, uint8_t* col2, unsigned int threshold, unsigned int width, unsigned int height);
