//
// ZoneMinder Pixel Format Helpers
// Copyright (C) 2026 ZoneMinder
//
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; either version 2
// of the License, or (at your option) any later version.
//

#ifndef ZM_PIXFORMAT_H
#define ZM_PIXFORMAT_H

#include "zm_ffmpeg.h"
#include "zm_rgb.h"

//
// Central pixel format conversion helpers.
//
// These replace the legacy ZM_COLOUR_* / ZM_SUBPIX_ORDER_* integer pair with
// AVPixelFormat as the single source of truth. The ZM_COLOUR_* constants
// (1=GRAY8, 3=RGB24, 4=RGB32) are retained only as bytes-per-pixel values for
// backwards compatibility with pixel-stride arithmetic.
//

// Forward mapping: (ZM colours, ZM subpixelorder) -> AVPixelFormat
inline AVPixelFormat zm_pixformat_from_colours(unsigned int colours, unsigned int subpixelorder) {
  if (colours == ZM_COLOUR_GRAY8) {
    if (subpixelorder == ZM_SUBPIX_ORDER_YUV420P)  return AV_PIX_FMT_YUV420P;
    if (subpixelorder == ZM_SUBPIX_ORDER_YUVJ420P) return AV_PIX_FMT_YUVJ420P;
    return AV_PIX_FMT_GRAY8;
  }
  if (colours == ZM_COLOUR_RGB24) {
    if (subpixelorder == ZM_SUBPIX_ORDER_BGR) return AV_PIX_FMT_BGR24;
    return AV_PIX_FMT_RGB24;
  }
  if (colours == ZM_COLOUR_RGB32) {
    if (subpixelorder == ZM_SUBPIX_ORDER_ARGB) return AV_PIX_FMT_ARGB;
    if (subpixelorder == ZM_SUBPIX_ORDER_ABGR) return AV_PIX_FMT_ABGR;
    if (subpixelorder == ZM_SUBPIX_ORDER_BGRA) return AV_PIX_FMT_BGRA;
    return AV_PIX_FMT_RGBA;
  }
  return AV_PIX_FMT_NONE;
}

// Inverse mapping: AVPixelFormat -> (ZM colours, ZM subpixelorder)
// Returns true if the format is recognised, false otherwise (out params untouched).
inline bool zm_colours_from_pixformat(AVPixelFormat fmt,
                                      unsigned int &colours,
                                      unsigned int &subpixelorder) {
  switch (fmt) {
    case AV_PIX_FMT_GRAY8:
      colours = ZM_COLOUR_GRAY8;
      subpixelorder = ZM_SUBPIX_ORDER_NONE;
      return true;
    case AV_PIX_FMT_YUV420P:
      colours = ZM_COLOUR_GRAY8;
      subpixelorder = ZM_SUBPIX_ORDER_YUV420P;
      return true;
    case AV_PIX_FMT_YUVJ420P:
      colours = ZM_COLOUR_GRAY8;
      subpixelorder = ZM_SUBPIX_ORDER_YUVJ420P;
      return true;
    case AV_PIX_FMT_YUV422P:
    case AV_PIX_FMT_YUVJ422P:
      colours = ZM_COLOUR_GRAY8;  // Y-plane is 1 byte/pixel
      subpixelorder = ZM_SUBPIX_ORDER_NONE;
      return true;
    case AV_PIX_FMT_RGB24:
      colours = ZM_COLOUR_RGB24;
      subpixelorder = ZM_SUBPIX_ORDER_RGB;
      return true;
    case AV_PIX_FMT_BGR24:
      colours = ZM_COLOUR_RGB24;
      subpixelorder = ZM_SUBPIX_ORDER_BGR;
      return true;
    case AV_PIX_FMT_RGBA:
      colours = ZM_COLOUR_RGB32;
      subpixelorder = ZM_SUBPIX_ORDER_RGBA;
      return true;
    case AV_PIX_FMT_BGRA:
      colours = ZM_COLOUR_RGB32;
      subpixelorder = ZM_SUBPIX_ORDER_BGRA;
      return true;
    case AV_PIX_FMT_ARGB:
      colours = ZM_COLOUR_RGB32;
      subpixelorder = ZM_SUBPIX_ORDER_ARGB;
      return true;
    case AV_PIX_FMT_ABGR:
      colours = ZM_COLOUR_RGB32;
      subpixelorder = ZM_SUBPIX_ORDER_ABGR;
      return true;
    default:
      return false;
  }
}

// Bytes-per-pixel in the primary buffer. For planar YUV formats this is the
// Y-plane stride (1), not the overall average.
inline unsigned int zm_bytes_per_pixel(AVPixelFormat fmt) {
  switch (fmt) {
    case AV_PIX_FMT_GRAY8:
    case AV_PIX_FMT_YUV420P:
    case AV_PIX_FMT_YUVJ420P:
    case AV_PIX_FMT_YUV422P:
    case AV_PIX_FMT_YUVJ422P:
      return 1;
    case AV_PIX_FMT_RGB24:
    case AV_PIX_FMT_BGR24:
      return 3;
    case AV_PIX_FMT_RGBA:
    case AV_PIX_FMT_BGRA:
    case AV_PIX_FMT_ARGB:
    case AV_PIX_FMT_ABGR:
      return 4;
    default:
      return 0;
  }
}

// Map the persisted DB Monitors.Colours value to an AVPixelFormat.
// Valid DB values are {1, 3, 4}.
inline AVPixelFormat zm_db_colours_to_pixformat(int db_colours) {
  switch (db_colours) {
    case ZM_COLOUR_GRAY8:  return AV_PIX_FMT_GRAY8;
    case ZM_COLOUR_RGB24:  return AV_PIX_FMT_RGB24;
    case ZM_COLOUR_RGB32:  return AV_PIX_FMT_RGBA;
    default:               return AV_PIX_FMT_NONE;
  }
}

// Format-family predicates. Prefer these over ZM_COLOUR_* comparisons when
// dispatching on pixel format.
inline bool zm_is_rgb32(AVPixelFormat fmt) {
  return fmt == AV_PIX_FMT_RGBA
      || fmt == AV_PIX_FMT_BGRA
      || fmt == AV_PIX_FMT_ARGB
      || fmt == AV_PIX_FMT_ABGR;
}

inline bool zm_is_rgb24(AVPixelFormat fmt) {
  return fmt == AV_PIX_FMT_RGB24 || fmt == AV_PIX_FMT_BGR24;
}

inline bool zm_is_yuv420(AVPixelFormat fmt) {
  return fmt == AV_PIX_FMT_YUV420P || fmt == AV_PIX_FMT_YUVJ420P;
}

#endif // ZM_PIXFORMAT_H
