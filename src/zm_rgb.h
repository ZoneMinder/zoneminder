//
// ZoneMinder RGB Interface, $Date$, $Revision$
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

#ifndef ZM_RGB_H
#define ZM_RGB_H

#include "zm_define.h"
#include "zm_logger.h"

typedef uint32 Rgb;  // RGB colour type
typedef uint32 YUV;  // YUV colour type

constexpr uint8 kWhite = 0xff;
constexpr uint8 kWhiteR = 0xff;
constexpr uint8 kWhiteG = 0xff;
constexpr uint8 kWhiteB = 0xff;

constexpr uint8 kBlack = 0x00;
constexpr uint8 kBlackR = 0x00;
constexpr uint8 kBlackG = 0x00;
constexpr uint8 kBlackB = 0x00;

constexpr Rgb kRGBWhite = 0x00ffffff;
constexpr Rgb kRGBBlack = 0x00000000;
constexpr Rgb kRGBRed = 0x000000ff;
constexpr Rgb kRGBGreen = 0x0000ff00;
constexpr Rgb kRGBBlue = 0x00ff0000;
constexpr Rgb kRGBOrange = 0x0000a5ff;
constexpr Rgb kRGBPurple = 0x00800080;
constexpr Rgb kRGBTransparent = 0x01000000;

#define RGB_VAL(v,c)    (((v)>>(16-((c)*8)))&0xff)

/* RGB or RGBA macros */
#define BLUE_VAL_RGBA(v)  (((v)>>16)&0xff)
#define GREEN_VAL_RGBA(v)  (((v)>>8)&0xff)
#define RED_VAL_RGBA(v)   ((v)&0xff)

#define ALPHA_VAL_RGBA(v)  ((v)>>24)&0xff)
#define RED_PTR_RGBA(ptr)  (*((uint8_t*)ptr))
#define GREEN_PTR_RGBA(ptr)  (*((uint8_t*)ptr+1))
#define BLUE_PTR_RGBA(ptr)  (*((uint8_t*)ptr+2))
#define ALPHA_PTR_RGBA(ptr)  (*((uint8_t*)ptr+3))

/* BGR or BGRA */
#define RED_VAL_BGRA(v)   (((v)>>16)&0xff)
#define GREEN_VAL_BGRA(v)  (((v)>>8)&0xff)
#define BLUE_VAL_BGRA(v)  ((v)&0xff)
#define ALPHA_VAL_BGRA(v)  ((v)>>24)&0xff)
#define RED_PTR_BGRA(ptr)  (*((uint8_t*)ptr+2))
#define GREEN_PTR_BGRA(ptr)  (*((uint8_t*)ptr+1))
#define BLUE_PTR_BGRA(ptr)  (*((uint8_t*)ptr))
#define ALPHA_PTR_BGRA(ptr)  (*((uint8_t*)ptr+3))

/* ARGB */
#define BLUE_VAL_ARGB(v)  (((v)>>24)&0xff)
#define GREEN_VAL_ARGB(v)  (((v)>>16)&0xff)
#define RED_VAL_ARGB(v)   (((v)>>8)&0xff)
#define ALPHA_VAL_ARGB(v)  ((v)&0xff)
#define RED_PTR_ARGB(ptr)  (*((uint8_t*)ptr+1))
#define GREEN_PTR_ARGB(ptr)  (*((uint8_t*)ptr+2))
#define BLUE_PTR_ARGB(ptr)  (*((uint8_t*)ptr+3))
#define ALPHA_PTR_ARGB(ptr)  (*((uint8_t*)ptr))

/* ABGR */
#define BLUE_VAL_ABGR(v)  (((v)>>8)&0xff)
#define GREEN_VAL_ABGR(v)  (((v)>>16)&0xff)
#define RED_VAL_ABGR(v)   (((v)>>24)&0xff)
#define ALPHA_VAL_ABGR(v)  ((v)&0xff)
#define RED_PTR_ABGR(ptr)  (*((uint8_t*)ptr+3))
#define GREEN_PTR_ABGR(ptr)  (*((uint8_t*)ptr+2))
#define BLUE_PTR_ABGR(ptr)  (*((uint8_t*)ptr+1))
#define ALPHA_PTR_ABGR(ptr)  (*((uint8_t*)ptr))


#define RGBA_BGRA_ZEROALPHA(v)  ((v)&0x00ffffff)
#define ARGB_ABGR_ZEROALPHA(v)  ((v)&0xffffff00)

/* ITU-R BT.709: Y = (0.2126 * R) + (0.7152 * G) + (0.0722 * B) */
/* ITU-R BT.601: Y = (0.299  * R) + (0.587  * G) + (0.114  * B) */
/* The formulas below produce an almost identical result to the weighted algorithms from the ITU-R BT.601 standard and the newer ITU-R BT.709 standard, but a lot faster */
// #define RGB_FASTLUM_SINGLE_ITU709(v)    ((RED(v)+RED(v)+BLUE(v)+GREEN(v)+GREEN(v)+GREEN(v)+GREEN(v)+GREEN(v))>>3)
// #define RGB_FASTLUM_VALUES_ITU709(ra,ga,ba)  (((ra)+(ra)+(ba)+(ga)+(ga)+(ga)+(ga)+(ga))>>3)
// #define RGB_FASTLUM_SINGLE_ITU601(v)    ((RED(v)+RED(v)+RED(v)+BLUE(v)+GREEN(v)+GREEN(v)+GREEN(v)+GREEN(v))>>3)
// #define RGB_FASTLUM_VALUES_ITU601(ra,ga,ba)  (((ra)+(ra)+(ra)+(ba)+(ga)+(ga)+(ga)+(ga))>>3)

// DEPRECATED: ZM_COLOUR_* and ZM_SUBPIX_ORDER_* are being replaced by
// AVPixelFormat throughout the codebase. Use the helpers in zm_pixformat.h for
// format identification. These values are retained only for byte-per-pixel
// stride arithmetic and backwards compatibility with the DB Monitors.Colours
// column which stores {1, 3, 4}.
#define ZM_COLOUR_RGB32 4
#define ZM_COLOUR_RGB24 3
#define ZM_COLOUR_GRAY8 1
#define ZM_COLOUR_YUV420P 1   // DEPRECATED: collides with GRAY8; use AVPixelFormat
#define ZM_COLOUR_YUVJ420P 1  // DEPRECATED: collides with GRAY8; use AVPixelFormat

// DEPRECATED: Subpixel ordering constants. Use AVPixelFormat directly.
#define ZM_SUBPIX_ORDER_NONE 2
#define ZM_SUBPIX_ORDER_RGB 6
#define ZM_SUBPIX_ORDER_BGR 5
#define ZM_SUBPIX_ORDER_BGRA 7
#define ZM_SUBPIX_ORDER_RGBA 8
#define ZM_SUBPIX_ORDER_ABGR 9
#define ZM_SUBPIX_ORDER_ARGB 10
#define ZM_SUBPIX_ORDER_YUV420P 11
#define ZM_SUBPIX_ORDER_YUVJ420P 12

// DEPRECATED: Use zm_pixformat_from_colours() in zm_pixformat.h instead.
// Only valid for DB-persisted colours values {1, 3, 4}.
#define ZM_SUBPIX_ORDER_DEFAULT_FOR_COLOUR(c)  ((c)<<1)

/* Convert RGB colour value into BGR\ARGB\ABGR */
inline Rgb rgb_convert(Rgb p_col, int p_subpixorder) {
  Rgb result = 0;

  switch (p_subpixorder) {
  case ZM_SUBPIX_ORDER_BGR:
  case ZM_SUBPIX_ORDER_BGRA:
    BLUE_PTR_BGRA(&result) = BLUE_VAL_RGBA(p_col);
    GREEN_PTR_BGRA(&result) = GREEN_VAL_RGBA(p_col);
    RED_PTR_BGRA(&result) = RED_VAL_RGBA(p_col);
    break;
  case ZM_SUBPIX_ORDER_ARGB:
    BLUE_PTR_ARGB(&result) = BLUE_VAL_RGBA(p_col);
    GREEN_PTR_ARGB(&result) = GREEN_VAL_RGBA(p_col);
    RED_PTR_ARGB(&result) = RED_VAL_RGBA(p_col);
    break;
  case ZM_SUBPIX_ORDER_ABGR:
    BLUE_PTR_ABGR(&result) = BLUE_VAL_RGBA(p_col);
    GREEN_PTR_ABGR(&result) = GREEN_VAL_RGBA(p_col);
    RED_PTR_ABGR(&result) = RED_VAL_RGBA(p_col);
    break;
  /* Grayscale */
  case ZM_SUBPIX_ORDER_NONE:
    Debug(1, "greyscale conversion");
    result = p_col & 0xff;
    break;
  default:
    Debug(1, "Default to not rgb conversion");
    result = p_col;
    break;
  }

  return result;
}

inline YUV brg_to_yuv(Rgb colour) {
  float R = RED_VAL_RGBA(colour);
  float G = GREEN_VAL_RGBA(colour);
  float B = BLUE_VAL_RGBA(colour);

  float Y = 0.257*R + 0.504*G + 0.098*B + 16;
  float U = -0.148*R - 0.291*G + 0.439*B + 128;
  float V = 0.439*R -0.368*G - 0.071*B + 128;
  return (static_cast<uint8_t>(Y)<<16) + (static_cast<uint8_t>(U) << 8) + static_cast<uint8_t>(V);
}

#define Y_VAL(v)  (((v)>>16)&0xff)
#define U_VAL(v)  (((v)>>8)&0xff)
#define V_VAL(v)  ((v)&0xff)

#endif // ZM_RGB_H
