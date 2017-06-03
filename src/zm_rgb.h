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

typedef uint32_t Rgb;  // RGB colour type

#define WHITE     0xff
#define WHITE_R   0xff
#define WHITE_G   0xff
#define WHITE_B   0xff

#define BLACK     0x00
#define BLACK_R   0x00
#define BLACK_G   0x00
#define BLACK_B   0x00

#define RGB_WHITE     (0x00ffffff)
#define RGB_BLACK     (0x00000000)
#define RGB_RED     (0x000000ff)
#define RGB_GREEN     (0x0000ff00)
#define RGB_BLUE    (0x00ff0000)
#define RGB_ORANGE    (0x0000a5ff)
#define RGB_PURPLE    (0x00800080)
#define RGB_TRANSPARENT  (0x01000000)

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

/* ZM colours */
#define ZM_COLOUR_RGB32 4
#define ZM_COLOUR_RGB24 3
#define ZM_COLOUR_GRAY8 1

/* Subpixel ordering */
/* Based on byte order naming. For example, for ARGB (on both little endian or big endian) byte+0 should be alpha, byte+1 should be red, and so on. */
#define ZM_SUBPIX_ORDER_NONE 2
#define ZM_SUBPIX_ORDER_RGB 6
#define ZM_SUBPIX_ORDER_BGR 5
#define ZM_SUBPIX_ORDER_BGRA 7
#define ZM_SUBPIX_ORDER_RGBA 8
#define ZM_SUBPIX_ORDER_ABGR 9
#define ZM_SUBPIX_ORDER_ARGB 10

/* A macro to use default subpixel order for a specified colour. */
/* for grayscale it will use NONE, for 3 colours it will use R,G,B, for 4 colours it will use R,G,B,A */
#define ZM_SUBPIX_ORDER_DEFAULT_FOR_COLOUR(c)  ((c)<<1)

/* Convert RGB colour value into BGR\ARGB\ABGR */
inline Rgb rgb_convert(Rgb p_col, int p_subpixorder) {
  Rgb result;
  
  switch(p_subpixorder) {
    
    case ZM_SUBPIX_ORDER_BGR:
    case ZM_SUBPIX_ORDER_BGRA:
    {
    BLUE_PTR_BGRA(&result) = BLUE_VAL_RGBA(p_col);
    GREEN_PTR_BGRA(&result) = GREEN_VAL_RGBA(p_col);
    RED_PTR_BGRA(&result) = RED_VAL_RGBA(p_col);
    }
    break;
    case ZM_SUBPIX_ORDER_ARGB:
    {
    BLUE_PTR_ARGB(&result) = BLUE_VAL_RGBA(p_col);
    GREEN_PTR_ARGB(&result) = GREEN_VAL_RGBA(p_col);
    RED_PTR_ARGB(&result) = RED_VAL_RGBA(p_col);
    }
    break;
    case ZM_SUBPIX_ORDER_ABGR:
    {
    BLUE_PTR_ABGR(&result) = BLUE_VAL_RGBA(p_col);
    GREEN_PTR_ABGR(&result) = GREEN_VAL_RGBA(p_col);
    RED_PTR_ABGR(&result) = RED_VAL_RGBA(p_col);
    }
    break;
    /* Grayscale */
    case ZM_SUBPIX_ORDER_NONE:
    result = p_col & 0xff;
    break;
    default:
    return p_col;
    break;
  }
  
  return result;
}

#endif // ZM_RGB_H
