//
// ZoneMinder Local Camera Class Implementation, $Date$, $Revision$
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

#include "zm_local_camera.h"

#include "zm_packet.h"
#include "zm_utils.h"
#include <fcntl.h>
#include <sys/mman.h>
#include <sys/stat.h>
#include <unistd.h>
#if ZM_HAS_V4L2
#include <libv4l2.h>
#endif // ZM_HAS_V4L2


#if ZM_HAS_V4L2

/* Workaround for GNU/kFreeBSD and FreeBSD */
#if defined(__FreeBSD_kernel__) || defined(__FreeBSD__)
#ifndef ENODATA
#define ENODATA ENOATTR
#endif
#endif

static unsigned int BigEndian;
static bool primed;

#if ZM_HAS_V4L2
static int vidioctl(int fd, int request, void *arg) {
  int result = -1;
  do {
    result = v4l2_ioctl( fd, request, arg );
  } while( result == -1 && errno == EINTR );
  return result;
}
#endif // ZM_HAS_V4L2

static _AVPIXELFORMAT getFfPixFormatFromV4lPalette(int v4l_version, int palette) {
  _AVPIXELFORMAT pixFormat = AV_PIX_FMT_NONE;

  switch (palette) {
#if defined(V4L2_PIX_FMT_RGB444) && defined(AV_PIX_FMT_RGB444)
  case V4L2_PIX_FMT_RGB444 :
    pixFormat = AV_PIX_FMT_RGB444;
    break;
#endif // V4L2_PIX_FMT_RGB444
  case V4L2_PIX_FMT_RGB555 :
    pixFormat = AV_PIX_FMT_RGB555;
    break;
  case V4L2_PIX_FMT_RGB565 :
    pixFormat = AV_PIX_FMT_RGB565;
    break;
  case V4L2_PIX_FMT_BGR24 :
    pixFormat = AV_PIX_FMT_BGR24;
    break;
  case V4L2_PIX_FMT_RGB24 :
    pixFormat = AV_PIX_FMT_RGB24;
    break;
  case V4L2_PIX_FMT_BGR32 :
    pixFormat = AV_PIX_FMT_BGRA;
    break;
  case V4L2_PIX_FMT_RGB32 :
    pixFormat = AV_PIX_FMT_ARGB;
    break;
  case V4L2_PIX_FMT_GREY :
    pixFormat = AV_PIX_FMT_GRAY8;
    break;
  case V4L2_PIX_FMT_YUYV :
    pixFormat = AV_PIX_FMT_YUYV422;
    break;
  case V4L2_PIX_FMT_YUV422P :
    pixFormat = AV_PIX_FMT_YUV422P;
    break;
  case V4L2_PIX_FMT_YUV411P :
    pixFormat = AV_PIX_FMT_YUV411P;
    break;
#ifdef V4L2_PIX_FMT_YUV444
  case V4L2_PIX_FMT_YUV444 :
    pixFormat = AV_PIX_FMT_YUV444P;
    break;
#endif // V4L2_PIX_FMT_YUV444
  case V4L2_PIX_FMT_YUV410 :
    pixFormat = AV_PIX_FMT_YUV410P;
    break;
  case V4L2_PIX_FMT_YUV420 :
    pixFormat = AV_PIX_FMT_YUV420P;
    break;
  case V4L2_PIX_FMT_JPEG :
  case V4L2_PIX_FMT_MJPEG :
    pixFormat = AV_PIX_FMT_YUVJ444P;
    break;
  case V4L2_PIX_FMT_UYVY :
    pixFormat = AV_PIX_FMT_UYVY422;
    break;
  // These don't seem to have ffmpeg equivalents
  // See if you can match any of the ones in the default clause below!?
  case V4L2_PIX_FMT_RGB332 :
  case V4L2_PIX_FMT_RGB555X :
  case V4L2_PIX_FMT_RGB565X :
  //case V4L2_PIX_FMT_Y16 :
  //case V4L2_PIX_FMT_PAL8 :
  case V4L2_PIX_FMT_YVU410 :
  case V4L2_PIX_FMT_YVU420 :
  case V4L2_PIX_FMT_Y41P :
  //case V4L2_PIX_FMT_YUV555 :
  //case V4L2_PIX_FMT_YUV565 :
  //case V4L2_PIX_FMT_YUV32 :
  case V4L2_PIX_FMT_NV12 :
  case V4L2_PIX_FMT_NV21 :
  case V4L2_PIX_FMT_YYUV :
  case V4L2_PIX_FMT_HI240 :
  case V4L2_PIX_FMT_HM12 :
  //case V4L2_PIX_FMT_SBGGR8 :
  //case V4L2_PIX_FMT_SGBRG8 :
  //case V4L2_PIX_FMT_SBGGR16 :
  case V4L2_PIX_FMT_DV :
  case V4L2_PIX_FMT_MPEG :
  case V4L2_PIX_FMT_WNVA :
  case V4L2_PIX_FMT_SN9C10X :
  case V4L2_PIX_FMT_PWC1 :
  case V4L2_PIX_FMT_PWC2 :
  case V4L2_PIX_FMT_ET61X251 :
  //case V4L2_PIX_FMT_SPCA501 :
  //case V4L2_PIX_FMT_SPCA505 :
  //case V4L2_PIX_FMT_SPCA508 :
  //case V4L2_PIX_FMT_SPCA561 :
  //case V4L2_PIX_FMT_PAC207 :
  //case V4L2_PIX_FMT_PJPG :
  //case V4L2_PIX_FMT_YVYU :
  default : {
    Fatal("Can't find swscale format for palette %d", palette);
    break;
#if 0
    // These are all spare and may match some of the above
    pixFormat = AV_PIX_FMT_YUVJ420P;
    pixFormat = AV_PIX_FMT_YUVJ422P;
    pixFormat = AV_PIX_FMT_UYVY422;
    pixFormat = AV_PIX_FMT_UYYVYY411;
    pixFormat = AV_PIX_FMT_BGR565;
    pixFormat = AV_PIX_FMT_BGR555;
    pixFormat = AV_PIX_FMT_BGR8;
    pixFormat = AV_PIX_FMT_BGR4;
    pixFormat = AV_PIX_FMT_BGR4_BYTE;
    pixFormat = AV_PIX_FMT_RGB8;
    pixFormat = AV_PIX_FMT_RGB4;
    pixFormat = AV_PIX_FMT_RGB4_BYTE;
    pixFormat = AV_PIX_FMT_NV12;
    pixFormat = AV_PIX_FMT_NV21;
    pixFormat = AV_PIX_FMT_RGB32_1;
    pixFormat = AV_PIX_FMT_BGR32_1;
    pixFormat = AV_PIX_FMT_GRAY16BE;
    pixFormat = AV_PIX_FMT_GRAY16LE;
    pixFormat = AV_PIX_FMT_YUV440P;
    pixFormat = AV_PIX_FMT_YUVJ440P;
    pixFormat = AV_PIX_FMT_YUVA420P;
    //pixFormat = AV_PIX_FMT_VDPAU_H264;
    //pixFormat = AV_PIX_FMT_VDPAU_MPEG1;
    //pixFormat = AV_PIX_FMT_VDPAU_MPEG2;
#endif
  }
  } // end switch palette

  return pixFormat;
} // end getFfPixFormatFromV4lPalette

static char palette_desc[32];
/* Automatic format selection preferred formats */
static const uint32_t prefered_rgb32_formats[] = {
  V4L2_PIX_FMT_BGR32,
  V4L2_PIX_FMT_RGB32,
  V4L2_PIX_FMT_BGR24,
  V4L2_PIX_FMT_RGB24,
  V4L2_PIX_FMT_YUYV,
  V4L2_PIX_FMT_UYVY,
  V4L2_PIX_FMT_JPEG,
  V4L2_PIX_FMT_MJPEG,
  V4L2_PIX_FMT_YUV422P,
  V4L2_PIX_FMT_YUV420
};
static const uint32_t prefered_rgb24_formats[] = {
  V4L2_PIX_FMT_BGR24,
  V4L2_PIX_FMT_RGB24,
  V4L2_PIX_FMT_YUYV,
  V4L2_PIX_FMT_UYVY,
  V4L2_PIX_FMT_JPEG,
  V4L2_PIX_FMT_MJPEG,
  V4L2_PIX_FMT_YUV422P,
  V4L2_PIX_FMT_YUV420
};
static const uint32_t prefered_gray8_formats[] = {
  V4L2_PIX_FMT_GREY,
  V4L2_PIX_FMT_YUYV,
  V4L2_PIX_FMT_UYVY,
  V4L2_PIX_FMT_JPEG,
  V4L2_PIX_FMT_MJPEG,
  V4L2_PIX_FMT_YUV422P,
  V4L2_PIX_FMT_YUV420
};

int LocalCamera::camera_count = 0;
int LocalCamera::channel_count = 0;
int LocalCamera::channels[VIDEO_MAX_FRAME];
int LocalCamera::standards[VIDEO_MAX_FRAME];

int LocalCamera::vid_fd = -1;

int LocalCamera::v4l_version = 0;
LocalCamera::V4L2Data LocalCamera::v4l2_data;

av_frame_ptr *LocalCamera::capturePictures;

LocalCamera *LocalCamera::last_camera = nullptr;

LocalCamera::LocalCamera(
  const Monitor *monitor,
  const std::string &p_device,
  int p_channel,
  int p_standard,
  bool p_v4l_multi_buffer,
  unsigned int p_v4l_captures_per_frame,
  const std::string &p_method,
  int p_width,
  int p_height,
  int p_colours,
  int p_palette,
  int p_brightness,
  int p_contrast,
  int p_hue,
  int p_colour,
  bool p_capture,
  bool p_record_audio,
  unsigned int p_extras) :
  Camera(monitor, LOCAL_SRC, p_width, p_height, p_colours, ZM_SUBPIX_ORDER_DEFAULT_FOR_COLOUR(p_colours), p_brightness, p_contrast, p_hue, p_colour, p_capture, p_record_audio),
  device(p_device),
  channel(p_channel),
  standard(p_standard),
  palette(p_palette),
  channel_index(0),
  extras(p_extras) {
  // If we are the first, or only, input on this device then
  // do the initial opening etc
  device_prime = (camera_count++ == 0);
  v4l_version = (p_method=="v4l2"?2:1);
  v4l_multi_buffer = p_v4l_multi_buffer;
  v4l_captures_per_frame = p_v4l_captures_per_frame;

  if (capture) {
    if (device_prime) {
      Debug(2, "V4L support enabled, using V4L%d api", v4l_version);
    }

    if ((!last_camera) || (channel != last_camera->channel)) {
      // We are the first, or only, input that uses this channel
      channel_prime = true;
      channel_index = channel_count++;
      channels[channel_index] = channel;
      standards[channel_index] = standard;
    } else {
      // We are the second, or subsequent, input using this channel
      channel_prime = false;
    }
  }

  /* The V4L1 API doesn't care about endianness, we need to check the endianness of the machine */
  uint32_t checkval = 0xAABBCCDD;
  if (*(unsigned char*)&checkval == 0xDD) {
    BigEndian = 0;
    Debug(2, "little-endian processor detected");
  } else if (*(unsigned char*)&checkval == 0xAA) {
    BigEndian = 1;
    Debug(2, "Big-endian processor detected");
  } else {
    Error("Unable to detect the processor's endianness. Assuming little-endian.");
    BigEndian = 0;
  }

  if (palette == 0) {
    /* Use automatic format selection */
    Debug(2,"Using automatic format selection");
    palette = AutoSelectFormat(colours);
    if (palette == 0) {
      Error("Automatic format selection failed. Falling back to YUYV");
      palette = V4L2_PIX_FMT_YUYV;
    } else {
      if (capture) {
        Info("Selected capture palette: %s (0x%02hhx%02hhx%02hhx%02hhx)",
             palette_desc,
             static_cast<uint8>((palette >> 24) & 0xff),
             static_cast<uint8>((palette >> 16) & 0xff),
             static_cast<uint8>((palette >> 8) & 0xff),
             static_cast<uint8>((palette) & 0xff));
      }
    }
  }

  if (capture) {
    if (last_camera) {
      if (standard != last_camera->standard)
        Warning("Different video standards defined for monitors sharing same device, results may be unpredictable or completely wrong");

      if (palette != last_camera->palette)
        Warning("Different video palettes defined for monitors sharing same device, results may be unpredictable or completely wrong");

      if (width != last_camera->width or height != last_camera->height)
        Warning("Different capture sizes defined for monitors sharing same device, results may be unpredictable or completely wrong");
    }

    /* Get ffmpeg pixel format based on capture palette and endianness */
    capturePixFormat = getFfPixFormatFromV4lPalette(v4l_version, palette);
    imagePixFormat = AV_PIX_FMT_NONE;
  }

  /* Try to find a match for the selected palette and target colourspace */

  /* RGB32 palette and 32bit target colourspace */
  if (palette == V4L2_PIX_FMT_RGB32 && colours == ZM_COLOUR_RGB32) {
    conversion_type = 0;
    subpixelorder = ZM_SUBPIX_ORDER_ARGB;

    /* BGR32 palette and 32bit target colourspace */
  } else if (palette == V4L2_PIX_FMT_BGR32 && colours == ZM_COLOUR_RGB32) {
    conversion_type = 0;
    subpixelorder = ZM_SUBPIX_ORDER_BGRA;

    /* RGB24 palette and 24bit target colourspace */
  } else if (palette == V4L2_PIX_FMT_RGB24 && colours == ZM_COLOUR_RGB24) {
    conversion_type = 0;
    conversion_type = 0;
    subpixelorder = ZM_SUBPIX_ORDER_BGR;

    /* Grayscale palette and grayscale target colourspace */
  } else if (palette == V4L2_PIX_FMT_GREY && colours == ZM_COLOUR_GRAY8) {
    conversion_type = 0;
    subpixelorder = ZM_SUBPIX_ORDER_NONE;
    /* Unable to find a solution for the selected palette and target colourspace. Conversion required. Notify the user of performance penalty */
  } else {
    if (capture) {
      Info(
        "No direct match for the selected palette (%d) and target colorspace (%02u). Format conversion is required, performance penalty expected",
        capturePixFormat,
        colours);
    }
    /* Try using swscale for the conversion */
    conversion_type = 1;
    Debug(2, "Using swscale for image conversion");
    if (colours == ZM_COLOUR_RGB32) {
      subpixelorder = ZM_SUBPIX_ORDER_RGBA;
      imagePixFormat = AV_PIX_FMT_RGBA;
    } else if (colours == ZM_COLOUR_RGB24) {
      subpixelorder = ZM_SUBPIX_ORDER_RGB;
      imagePixFormat = AV_PIX_FMT_RGB24;
    } else if (colours == ZM_COLOUR_GRAY8) {
      subpixelorder = ZM_SUBPIX_ORDER_NONE;
      imagePixFormat = AV_PIX_FMT_GRAY8;
    } else {
      Panic("Unexpected colours: %u",colours);
    }
    if (capture) {
      if (!sws_isSupportedInput(capturePixFormat)) {
        Error("swscale does not support the used capture format: %d", capturePixFormat);
        conversion_type = 2; /* Try ZM format conversions */
      }
      if (!sws_isSupportedOutput(imagePixFormat)) {
        Error("swscale does not support the target format: 0x%d", imagePixFormat);
        conversion_type = 2; /* Try ZM format conversions */
      }
    }
    /* Our YUYV->Grayscale conversion is a lot faster than swscale's */
    if (colours == ZM_COLOUR_GRAY8 && palette == V4L2_PIX_FMT_YUYV) {
      conversion_type = 2;
    }

    /* JPEG */
    if (palette == V4L2_PIX_FMT_JPEG || palette == V4L2_PIX_FMT_MJPEG) {
      Debug(2,"Using JPEG image decoding");
      conversion_type = 3;
    }

    if (conversion_type == 2) {
      Debug(2,"Using ZM for image conversion");
      if ( palette == V4L2_PIX_FMT_RGB32 && colours == ZM_COLOUR_GRAY8 ) {
        conversion_fptr = &std_convert_argb_gray8;
        subpixelorder = ZM_SUBPIX_ORDER_NONE;
      } else if (palette == V4L2_PIX_FMT_BGR32 && colours == ZM_COLOUR_GRAY8) {
        conversion_fptr = &std_convert_bgra_gray8;
        subpixelorder = ZM_SUBPIX_ORDER_NONE;
      } else if (palette == V4L2_PIX_FMT_YUYV && colours == ZM_COLOUR_GRAY8) {
        /* Fast YUYV->Grayscale conversion by extracting the Y channel */
        if (config.cpu_extensions && sse_version >= 35) {
          conversion_fptr = &ssse3_convert_yuyv_gray8;
          Debug(2,"Using SSSE3 YUYV->grayscale fast conversion");
        } else {
          conversion_fptr = &std_convert_yuyv_gray8;
          Debug(2,"Using standard YUYV->grayscale fast conversion");
        }
        subpixelorder = ZM_SUBPIX_ORDER_NONE;
      } else if (palette == V4L2_PIX_FMT_YUYV && colours == ZM_COLOUR_RGB24) {
        conversion_fptr = &zm_convert_yuyv_rgb;
        subpixelorder = ZM_SUBPIX_ORDER_RGB;
      } else if (palette == V4L2_PIX_FMT_YUYV && colours == ZM_COLOUR_RGB32) {
        conversion_fptr = &zm_convert_yuyv_rgba;
        subpixelorder = ZM_SUBPIX_ORDER_RGBA;
      } else if (palette == V4L2_PIX_FMT_RGB555 && colours == ZM_COLOUR_RGB24) {
        conversion_fptr = &zm_convert_rgb555_rgb;
        subpixelorder = ZM_SUBPIX_ORDER_RGB;
      } else if (palette == V4L2_PIX_FMT_RGB555 && colours == ZM_COLOUR_RGB32) {
        conversion_fptr = &zm_convert_rgb555_rgba;
        subpixelorder = ZM_SUBPIX_ORDER_RGBA;
      } else if (palette == V4L2_PIX_FMT_RGB565 && colours == ZM_COLOUR_RGB24) {
        conversion_fptr = &zm_convert_rgb565_rgb;
        subpixelorder = ZM_SUBPIX_ORDER_RGB;
      } else if (palette == V4L2_PIX_FMT_RGB565 && colours == ZM_COLOUR_RGB32) {
        conversion_fptr = &zm_convert_rgb565_rgba;
        subpixelorder = ZM_SUBPIX_ORDER_RGBA;
      } else {
        Fatal("Unable to find a suitable format conversion for the selected palette and target colorspace.");
      }
    } // end if conversion_type == 2
  } // end if needs conversion

  last_camera = this;
  Debug(3, "Selected subpixelorder: %u", subpixelorder);

  /* Initialize swscale stuff */
  if (capture and (conversion_type == 1)) {
    tmpPicture = av_frame_ptr{zm_av_frame_alloc()};

    if (!tmpPicture)
      Fatal("Could not allocate temporary picture");

    unsigned int pSize = av_image_get_buffer_size(imagePixFormat, width, height, 1);

    if (pSize != imagesize) {
      Fatal("Image size mismatch. Required: %d Available: %llu", pSize, imagesize);
    }

    imgConversionContext = sws_getContext(
                             width, height, capturePixFormat,
                             width, height, imagePixFormat, SWS_BICUBIC,
                             nullptr, nullptr, nullptr);

    if (!imgConversionContext) {
      Fatal("Unable to initialise image scaling context");
    }
  } else {
    imgConversionContext = nullptr;
  } // end if capture and conversion_tye == swscale
  if (capture and device_prime)
    Initialise();
} // end LocalCamera::LocalCamera

LocalCamera::~LocalCamera() {
  if (device_prime && capture)
    Terminate();

  /* Clean up swscale stuff */
  if (capture && (conversion_type == 1)) {
    sws_freeContext(imgConversionContext);
    imgConversionContext = nullptr;
  }
} // end LocalCamera::~LocalCamera

int LocalCamera::Close() {
  if (device_prime && capture)
    Terminate();
  return 0;
};

void LocalCamera::Initialise() {
  Debug(3, "Opening video device %s", device.c_str());
  if ( (vid_fd = v4l2_open( device.c_str(), O_RDWR, 0 )) < 0 )
    Fatal("Failed to open video device %s: %s", device.c_str(), strerror(errno));

  struct stat st;
  if (stat(device.c_str(), &st) < 0)
    Fatal("Failed to stat video device %s: %s", device.c_str(), strerror(errno));

  if (!S_ISCHR(st.st_mode))
    Fatal("File %s is not device file: %s", device.c_str(), strerror(errno));

  struct v4l2_capability vid_cap;

  Debug(3, "Checking video device capabilities");
  if ( vidioctl(vid_fd, VIDIOC_QUERYCAP, &vid_cap) < 0 )
    Fatal("Failed to query video device: %s", strerror(errno));

  if ( !(vid_cap.capabilities & V4L2_CAP_VIDEO_CAPTURE) )
    Fatal("Video device is not video capture device");

  if ( !(vid_cap.capabilities & V4L2_CAP_STREAMING) )
    Fatal("Video device does not support streaming i/o");

  Debug(3, "Setting up video format");

  memset(&v4l2_data.fmt, 0, sizeof(v4l2_data.fmt));
  v4l2_data.fmt.type = V4L2_BUF_TYPE_VIDEO_CAPTURE;

  if ( vidioctl( vid_fd, VIDIOC_G_FMT, &v4l2_data.fmt ) < 0 )
    Fatal("Failed to get video format: %s", strerror(errno));

  Debug(4,
        " v4l2_data.fmt.type = %08x\n"
        " v4l2_data.fmt.fmt.pix.width = %d\n"
        " v4l2_data.fmt.fmt.pix.height = %d\n"
        " v4l2_data.fmt.fmt.pix.pixelformat = %08x\n"
        " v4l2_data.fmt.fmt.pix.field = %08x\n"
        " v4l2_data.fmt.fmt.pix.bytesperline = %d\n"
        " v4l2_data.fmt.fmt.pix.sizeimage = %d\n"
        " v4l2_data.fmt.fmt.pix.colorspace = %08x\n"
        " v4l2_data.fmt.fmt.pix.priv = %08x\n"
        , v4l2_data.fmt.type
        , v4l2_data.fmt.fmt.pix.width
        , v4l2_data.fmt.fmt.pix.height
        , v4l2_data.fmt.fmt.pix.pixelformat
        , v4l2_data.fmt.fmt.pix.field
        , v4l2_data.fmt.fmt.pix.bytesperline
        , v4l2_data.fmt.fmt.pix.sizeimage
        , v4l2_data.fmt.fmt.pix.colorspace
        , v4l2_data.fmt.fmt.pix.priv
       );

  v4l2_data.fmt.type = V4L2_BUF_TYPE_VIDEO_CAPTURE;
  v4l2_data.fmt.fmt.pix.width = width;
  v4l2_data.fmt.fmt.pix.height = height;
  v4l2_data.fmt.fmt.pix.pixelformat = palette;

  if ((extras & 0xff) != 0) {
    v4l2_data.fmt.fmt.pix.field = (v4l2_field)(extras & 0xff);

    if (vidioctl(vid_fd, VIDIOC_S_FMT, &v4l2_data.fmt) < 0) {
      Warning("Failed to set V4L2 field to %d, falling back to auto", (extras & 0xff));
      v4l2_data.fmt.fmt.pix.field = V4L2_FIELD_ANY;
      if (vidioctl(vid_fd, VIDIOC_S_FMT, &v4l2_data.fmt) < 0) {
        Fatal("Failed to set video format: %s", strerror(errno));
      }
    }
  } else {
    if (vidioctl(vid_fd, VIDIOC_S_FMT, &v4l2_data.fmt) < 0) {
      Error("Failed to set video format: %s", strerror(errno));
    }
  }

  /* Note VIDIOC_S_FMT may change width and height. */
  Debug(4,
        " v4l2_data.fmt.type = %08x\n"
        " v4l2_data.fmt.fmt.pix.width = %d\n"
        " v4l2_data.fmt.fmt.pix.height = %d\n"
        " v4l2_data.fmt.fmt.pix.pixelformat = %08x\n"
        " v4l2_data.fmt.fmt.pix.field = %08x\n"
        " v4l2_data.fmt.fmt.pix.bytesperline = %d\n"
        " v4l2_data.fmt.fmt.pix.sizeimage = %d\n"
        " v4l2_data.fmt.fmt.pix.colorspace = %08x\n"
        " v4l2_data.fmt.fmt.pix.priv = %08x\n"
        , v4l2_data.fmt.type
        , v4l2_data.fmt.fmt.pix.width
        , v4l2_data.fmt.fmt.pix.height
        , v4l2_data.fmt.fmt.pix.pixelformat
        , v4l2_data.fmt.fmt.pix.field
        , v4l2_data.fmt.fmt.pix.bytesperline
        , v4l2_data.fmt.fmt.pix.sizeimage
        , v4l2_data.fmt.fmt.pix.colorspace
        , v4l2_data.fmt.fmt.pix.priv
       );

  if (v4l2_data.fmt.fmt.pix.width != width) {
    Warning("Failed to set requested width");
  }
  if (v4l2_data.fmt.fmt.pix.height != height) {
    Warning("Failed to set requested height");
  }

  /* Buggy driver paranoia. */
  unsigned int min;
  min = v4l2_data.fmt.fmt.pix.width * 2;
  if (v4l2_data.fmt.fmt.pix.bytesperline < min)
    v4l2_data.fmt.fmt.pix.bytesperline = min;
  min = v4l2_data.fmt.fmt.pix.bytesperline * v4l2_data.fmt.fmt.pix.height;
  if (v4l2_data.fmt.fmt.pix.sizeimage < min)
    v4l2_data.fmt.fmt.pix.sizeimage = min;

  if (palette == V4L2_PIX_FMT_JPEG || palette == V4L2_PIX_FMT_MJPEG) {
    v4l2_jpegcompression jpeg_comp;
    if (vidioctl(vid_fd, VIDIOC_G_JPEGCOMP, &jpeg_comp) < 0) {
      if (errno == EINVAL) {
        Debug(2, "JPEG compression options are not available");
      } else {
        Warning("Failed to get JPEG compression options: %s", strerror(errno));
      }
    } else {
      /* Set flags and quality. MJPEG should not have the huffman tables defined */
      if (palette == V4L2_PIX_FMT_MJPEG) {
        jpeg_comp.jpeg_markers |= V4L2_JPEG_MARKER_DQT | V4L2_JPEG_MARKER_DRI;
      } else {
        jpeg_comp.jpeg_markers |= V4L2_JPEG_MARKER_DQT | V4L2_JPEG_MARKER_DRI | V4L2_JPEG_MARKER_DHT;
      }
      jpeg_comp.quality = 85;

      /* Update the JPEG options */
      if (vidioctl(vid_fd, VIDIOC_S_JPEGCOMP, &jpeg_comp) < 0) {
        Warning("Failed to set JPEG compression options: %s", strerror(errno));
      } else {
        if (vidioctl(vid_fd, VIDIOC_G_JPEGCOMP, &jpeg_comp) < 0) {
          Debug(3,"Failed to get updated JPEG compression options: %s", strerror(errno));
        } else {
          Debug(4, "JPEG quality: %d, markers: %#x",
                jpeg_comp.quality, jpeg_comp.jpeg_markers);
        }
      }
    }
  } // end if JPEG/MJPEG

  Debug(3, "Setting up request buffers");

  memset(&v4l2_data.reqbufs, 0, sizeof(v4l2_data.reqbufs));
  if (channel_count > 1) {
    Debug(3, "Channel count is %d", channel_count);
    if (v4l_multi_buffer) {
      v4l2_data.reqbufs.count = 2*channel_count;
    } else {
      v4l2_data.reqbufs.count = 1;
    }
  } else {
    v4l2_data.reqbufs.count = 8;
  }
  Debug(3, "Request buffers count is %d", v4l2_data.reqbufs.count);

  v4l2_data.reqbufs.type = v4l2_data.fmt.type;
  v4l2_data.reqbufs.memory = V4L2_MEMORY_MMAP;

  if (vidioctl(vid_fd, VIDIOC_REQBUFS, &v4l2_data.reqbufs) < 0) {
    if (errno == EINVAL) {
      Fatal("Unable to initialise memory mapping, unsupported in device");
    } else {
      Fatal("Unable to initialise memory mapping: %s", strerror(errno));
    }
  }

  if (v4l2_data.reqbufs.count < (v4l_multi_buffer?2:1))
    Fatal("Insufficient buffer memory %d on video device", v4l2_data.reqbufs.count);

  Debug(3, "Setting up data buffers: Channels %d MultiBuffer %d Buffers: %d",
        channel_count, v4l_multi_buffer, v4l2_data.reqbufs.count);

  v4l2_data.buffers = new V4L2MappedBuffer[v4l2_data.reqbufs.count];
  capturePictures = new av_frame_ptr[v4l2_data.reqbufs.count];

  for (unsigned int i = 0; i < v4l2_data.reqbufs.count; i++) {
    struct v4l2_buffer vid_buf;

    memset(&vid_buf, 0, sizeof(vid_buf));

    //vid_buf.type = V4L2_BUF_TYPE_VIDEO_CAPTURE;
    vid_buf.type = v4l2_data.fmt.type;
    //vid_buf.memory = V4L2_MEMORY_MMAP;
    vid_buf.memory = v4l2_data.reqbufs.memory;
    vid_buf.index = i;

    if (vidioctl(vid_fd, VIDIOC_QUERYBUF, &vid_buf) < 0)
      Fatal("Unable to query video buffer: %s", strerror(errno));

    v4l2_data.buffers[i].length = vid_buf.length;
    v4l2_data.buffers[i].start = v4l2_mmap(nullptr, vid_buf.length, PROT_READ|PROT_WRITE, MAP_SHARED, vid_fd, vid_buf.m.offset);

    if (v4l2_data.buffers[i].start == MAP_FAILED)
      Fatal("Can't map video buffer %u (%u bytes) to memory: %s(%d)",
            i, vid_buf.length, strerror(errno), errno);

    capturePictures[i] = av_frame_ptr{zm_av_frame_alloc()};

    if (!capturePictures[i])
      Fatal("Could not allocate picture");

    av_image_fill_arrays(
      capturePictures[i]->data,
      capturePictures[i]->linesize,
      (uint8_t*)v4l2_data.buffers[i].start,
      capturePixFormat,
      v4l2_data.fmt.fmt.pix.width,
      v4l2_data.fmt.fmt.pix.height,
      1);
  } // end foreach request buf

  Debug(3, "Configuring video source");

  if (vidioctl(vid_fd, VIDIOC_S_INPUT, &channel) < 0) {
    Fatal("Failed to set camera source %d: %s", channel, strerror(errno));
  }

  struct v4l2_input input;
  v4l2_std_id stdId;

  memset(&input, 0, sizeof(input));
  input.index = channel;

  if (vidioctl(vid_fd, VIDIOC_ENUMINPUT, &input) < 0) {
    Fatal("Failed to enumerate input %d: %s", channel, strerror(errno));
  }

  if ((input.std != V4L2_STD_UNKNOWN) && ((input.std & standard) == V4L2_STD_UNKNOWN)) {
    Error("Device does not support video standard %d", standard);
  }

  stdId = standard;
  if ((vidioctl(vid_fd, VIDIOC_S_STD, &stdId) < 0)) {
    Error("Failed to set video standard %d: %d %s", standard, errno, strerror(errno));
  }

  Contrast(contrast);
  Brightness(brightness);
  Hue(hue);
  Colour(colour);
} // end LocalCamera::Initialize

void LocalCamera::Terminate() {
  if ( v4l_version == 2 ) {
    Debug(3, "Terminating video stream");
    //enum v4l2_buf_type type = V4L2_BUF_TYPE_VIDEO_CAPTURE;
    // enum v4l2_buf_type type = v4l2_data.fmt.type;
    enum v4l2_buf_type type = (v4l2_buf_type)v4l2_data.fmt.type;
    if ( vidioctl(vid_fd, VIDIOC_STREAMOFF, &type) < 0 )
      Error("Failed to stop capture stream: %s", strerror(errno));

    Debug(3, "Unmapping video buffers");
    for ( unsigned int i = 0; i < v4l2_data.reqbufs.count; i++ ) {
      capturePictures[i] = nullptr;

      if ( v4l2_munmap(v4l2_data.buffers[i].start, v4l2_data.buffers[i].length) < 0 )
        Error("Failed to munmap buffer %d: %s", i, strerror(errno));
    }
  }

 v4l2_close(vid_fd);
  primed = false;
} // end LocalCamera::Terminate

uint32_t LocalCamera::AutoSelectFormat(int p_colours) {
  /* Automatic format selection */
  uint32_t selected_palette = 0;
  char fmt_desc[64][32];
  uint32_t fmt_fcc[64];
  v4l2_fmtdesc fmtinfo;
  unsigned int nIndex = 0;
  //int nRet = 0; // compiler say it isn't used
  int enum_fd;

  /* Open the device */
  if ( (enum_fd = v4l2_open(device.c_str(), O_RDWR, 0)) < 0 ) {
    Error("Automatic format selection failed to open video device %s: %s",
          device.c_str(), strerror(errno));
    return selected_palette;
  }

  /* Enumerate available formats */
  memset(&fmtinfo, 0, sizeof(fmtinfo));
  fmtinfo.index = nIndex;
  fmtinfo.type = V4L2_BUF_TYPE_VIDEO_CAPTURE;
  // FIXME This will crash if there are more than 64 formats.
  while ( vidioctl(enum_fd, VIDIOC_ENUM_FMT, &fmtinfo) >= 0 ) {
    if ( nIndex >= 64 ) {
      Error("More than 64 formats detected, can't handle that.");
      break;
    }
    /* Got a format. Copy it to the array */
    strcpy(fmt_desc[nIndex], (const char*)(fmtinfo.description));
    fmt_fcc[nIndex] = fmtinfo.pixelformat;

    Debug(3, "Got format: %s (0x%02hhx%02hhx%02hhx%02hhx) at index %d",
          fmt_desc[nIndex],
          static_cast<uint8>((fmt_fcc[nIndex] >> 24) & 0xff),
          static_cast<uint8>((fmt_fcc[nIndex] >> 16) & 0xff),
          static_cast<uint8>((fmt_fcc[nIndex] >> 8) & 0xff),
          static_cast<uint8>((fmt_fcc[nIndex]) & 0xff),
          nIndex);

    /* Proceed to the next index */
    memset(&fmtinfo, 0, sizeof(fmtinfo));
    fmtinfo.index = ++nIndex;
    fmtinfo.type = V4L2_BUF_TYPE_VIDEO_CAPTURE;
  }

  /* Select format */
  int nIndexUsed = -1;
  unsigned int n_preferedformats = 0;
  const uint32_t* preferedformats;
  if ( p_colours == ZM_COLOUR_RGB32 ) {
    /* 32bit */
    preferedformats = prefered_rgb32_formats;
    n_preferedformats = sizeof(prefered_rgb32_formats) / sizeof(uint32_t);
  } else if ( p_colours == ZM_COLOUR_GRAY8 ) {
    /* Grayscale */
    preferedformats = prefered_gray8_formats;
    n_preferedformats = sizeof(prefered_gray8_formats) / sizeof(uint32_t);
  } else {
    /* Assume 24bit */
    preferedformats = prefered_rgb24_formats;
    n_preferedformats = sizeof(prefered_rgb24_formats) / sizeof(uint32_t);
  }
  for ( unsigned int i=0; i < n_preferedformats && nIndexUsed < 0; i++ ) {
    for ( unsigned int j=0; j < nIndex; j++ ) {
      if ( preferedformats[i] == fmt_fcc[j] ) {
        Debug(6, "Choosing format: %s (0x%02hhx%02hhx%02hhx%02hhx) at index %u",
              fmt_desc[j],
              static_cast<uint8>(fmt_fcc[j] & 0xff),
              static_cast<uint8>((fmt_fcc[j] >> 8) & 0xff),
              static_cast<uint8>((fmt_fcc[j] >> 16) & 0xff),
              static_cast<uint8>((fmt_fcc[j] >> 24) & 0xff),
              j);
        /* Found a format! */
        nIndexUsed = j;
        break;
      } else {
        Debug(6, "No match for format: %s (0x%02hhx%02hhx%02hhx%02hhx) at index %u",
              fmt_desc[j],
              static_cast<uint8>(fmt_fcc[j] & 0xff),
              static_cast<uint8>((fmt_fcc[j] >> 8) & 0xff),
              static_cast<uint8>((fmt_fcc[j] >> 16) & 0xff),
              static_cast<uint8>((fmt_fcc[j] >> 24) & 0xff),
              j);
      }
    }
  }

  /* Have we found a match? */
  if ( nIndexUsed >= 0 ) {
    /* Found a match */
    selected_palette = fmt_fcc[nIndexUsed];
    strcpy(palette_desc,fmt_desc[nIndexUsed]);
  }

  /* Close the device */
 v4l2_close(enum_fd);

  return selected_palette;
} //uint32_t LocalCamera::AutoSelectFormat(int p_colours)

#define capString(test,prefix,yesString,noString,capability) \
  (test) ? (prefix yesString " " capability "\n") : (prefix noString " " capability "\n")

bool LocalCamera::GetCurrentSettings(
  const std::string& device,
  char *output,
  int version,
  bool verbose) {
  output[0] = 0;
  char *output_ptr = output;

  std::string queryDevice;
  int devIndex = 0;
  do {
    if (!device.empty()) {
      queryDevice = device;
    } else {
      queryDevice = stringtf("/dev/video%d", devIndex);
    }

    if ((vid_fd =v4l2_open(queryDevice.c_str(), O_RDWR)) <= 0) {
      if (!device.empty()) {
        Error("Failed to open video device %s: %s", queryDevice.c_str(), strerror(errno));
        if (verbose) {
          output_ptr += sprintf(output_ptr, "Error, failed to open video device %s: %s\n",
                                queryDevice.c_str(), strerror(errno));
        } else {
          output_ptr += sprintf(output_ptr, "error%d\n", errno);
        }
        return false;
      } else {
        return true;
      }
    }

    if (verbose) {
      output_ptr += sprintf(output_ptr, "Video Device: %s\n", queryDevice.c_str());
    } else {
      output_ptr += sprintf(output_ptr, "d:%s|", queryDevice.c_str());
    }

    if (version == 2) {
      v4l2_capability vid_cap = {};
      if (vidioctl(vid_fd, VIDIOC_QUERYCAP, &vid_cap) < 0) {
        Error("Failed to query video device: %s", strerror(errno));
        if (verbose) {
          output_ptr += sprintf(output_ptr, "Error, failed to query video capabilities %s: %s\n",
                                queryDevice.c_str(), strerror(errno));
        } else {
          output_ptr += sprintf(output_ptr, "error%d\n", errno);
        }
        if (!device.empty()) {
          return false;
        }
      }

      if ( verbose ) {
        output_ptr += sprintf(output_ptr, "General Capabilities\n"
                              "  Driver: %s\n"
                              "  Card: %s\n"
                              "  Bus: %s\n"
                              "  Version: %u.%u.%u\n"
                              "  Type: 0x%x\n%s%s%s%s%s%s%s%s%s%s%s%s%s%s",
                              vid_cap.driver, vid_cap.card, vid_cap.bus_info,
                              (vid_cap.version>>16)&0xff, (vid_cap.version>>8)&0xff, vid_cap.version&0xff,
                              vid_cap.capabilities,
                              capString(vid_cap.capabilities&V4L2_CAP_VIDEO_CAPTURE,         "    ", "Supports", "Does not support", "video capture (X)"),
                              capString(vid_cap.capabilities&V4L2_CAP_VIDEO_OUTPUT,          "    ", "Supports", "Does not support", "video output"),
                              capString(vid_cap.capabilities&V4L2_CAP_VIDEO_OVERLAY,         "    ", "Supports", "Does not support", "frame buffer overlay"),
                              capString(vid_cap.capabilities&V4L2_CAP_VBI_CAPTURE,           "    ", "Supports", "Does not support", "VBI capture"),
                              capString(vid_cap.capabilities&V4L2_CAP_VBI_OUTPUT,            "    ", "Supports", "Does not support", "VBI output"),
                              capString(vid_cap.capabilities&V4L2_CAP_SLICED_VBI_CAPTURE,    "    ", "Supports", "Does not support", "sliced VBI capture"),
                              capString(vid_cap.capabilities&V4L2_CAP_SLICED_VBI_OUTPUT,     "    ", "Supports", "Does not support", "sliced VBI output"),
#ifdef V4L2_CAP_VIDEO_OUTPUT_OVERLAY
                              capString(vid_cap.capabilities&V4L2_CAP_VIDEO_OUTPUT_OVERLAY,  "    ", "Supports", "Does not support", "video output overlay"),
#else // V4L2_CAP_VIDEO_OUTPUT_OVERLAY
                              "",
#endif // V4L2_CAP_VIDEO_OUTPUT_OVERLAY
                              capString(vid_cap.capabilities&V4L2_CAP_TUNER,                 "    ", "Has", "Does not have", "tuner"),
                              capString(vid_cap.capabilities&V4L2_CAP_AUDIO,                 "    ", "Has", "Does not have", "audio in and/or out"),
                              capString(vid_cap.capabilities&V4L2_CAP_RADIO,                 "    ", "Has", "Does not have", "radio"),
                              capString(vid_cap.capabilities&V4L2_CAP_READWRITE,             "    ", "Supports", "Does not support", "read/write i/o (X)"),
                              capString(vid_cap.capabilities&V4L2_CAP_ASYNCIO,               "    ", "Supports", "Does not support", "async i/o"),
                              capString(vid_cap.capabilities&V4L2_CAP_STREAMING,             "    ", "Supports", "Does not support", "streaming i/o (X)")
                             );
      } else {
        output_ptr += sprintf(output_ptr, "D:%s|C:%s|B:%s|V:%u.%u.%u|T:0x%x|"
                              , vid_cap.driver
                              , vid_cap.card
                              , vid_cap.bus_info
                              , (vid_cap.version>>16)&0xff, (vid_cap.version>>8)&0xff, vid_cap.version&0xff
                              , vid_cap.capabilities);
      }

      output_ptr += sprintf(output_ptr, verbose ? "    Standards:\n" : "S:");

      v4l2_standard standard = {};
      int standardIndex = 0;
      do {
        memset(&standard, 0, sizeof(standard));
        standard.index = standardIndex;

        if ( vidioctl(vid_fd, VIDIOC_ENUMSTD, &standard) < 0 ) {
          if ( errno == EINVAL || errno == ENODATA || errno == ENOTTY ) {
            Debug(6, "Done enumerating standard %d: %d %s", standard.index, errno, strerror(errno));
            standardIndex = -1;
            break;
          } else {
            Error("Failed to enumerate standard %d: %d %s", standard.index, errno, strerror(errno));
            if ( verbose )
              output_ptr += sprintf(output_ptr, "Error, failed to enumerate standard %d: %d %s\n", standard.index, errno, strerror(errno));
            else
              output_ptr += sprintf(output_ptr, "error%d\n", errno);
            // Why return? Why not continue trying other things?
            return false;
          }
        }
        output_ptr += sprintf(output_ptr, (verbose ? "      %s\n" : "%s/"), standard.name);
      } while ( standardIndex++ >= 0 );

      if ( !verbose && (*(output_ptr-1) == '/') )
        *(output_ptr-1) = '|';

      output_ptr += sprintf(output_ptr, verbose ? "  Formats:\n" : "F:");

      int formatIndex = 0;
      do {
        v4l2_fmtdesc format = {};
        format.type = V4L2_BUF_TYPE_VIDEO_CAPTURE;
        format.index = formatIndex;

        if ( vidioctl(vid_fd, VIDIOC_ENUM_FMT, &format) < 0 ) {
          if ( errno == EINVAL ) {
            formatIndex = -1;
            break;
          } else {
            Error("Failed to enumerate format %d: %s", format.index, strerror(errno));
            if ( verbose )
              output_ptr += sprintf(output_ptr, "Error, failed to enumerate format %d: %s\n", format.index, strerror(errno));
            else
              output_ptr += sprintf(output_ptr, "error%d\n", errno);
            return false;
          }
        }
        if ( verbose )
          output_ptr += sprintf(
                          output_ptr,
                          "  %s (0x%02x%02x%02x%02x)\n",
                          format.description,
                          (format.pixelformat >> 24) & 0xff,
                          (format.pixelformat >> 16) & 0xff,
                          (format.pixelformat >> 8) & 0xff,
                          format.pixelformat & 0xff);
        else
          output_ptr += sprintf(
                          output_ptr,
                          "0x%02x%02x%02x%02x/",
                          (format.pixelformat >> 24) & 0xff,
                          (format.pixelformat >> 16) & 0xff,
                          (format.pixelformat >> 8) & 0xff,
                          format.pixelformat & 0xff);
      } while ( formatIndex++ >= 0 );

      if ( !verbose )
        *(output_ptr-1) = '|';
      else
        output_ptr += sprintf(output_ptr, "Crop Capabilities\n");

      v4l2_cropcap cropcap = {};
      cropcap.type = V4L2_BUF_TYPE_VIDEO_CAPTURE;

      if ( vidioctl(vid_fd, VIDIOC_CROPCAP, &cropcap) < 0 ) {
        if ( errno != EINVAL ) {
          /* Failed querying crop capability, write error to the log and continue as if crop is not supported */
          Error("Failed to query crop capabilities for %s: %d, %s",
                device.c_str(), errno, strerror(errno));
        }

        if ( verbose ) {
          output_ptr += sprintf(output_ptr, "  Cropping is not supported\n");
        } else {
          /* Send fake crop bounds to not confuse things parsing this, such as monitor probe */
          output_ptr += sprintf(output_ptr, "B:%dx%d|", 0, 0);
        }
      } else {
        v4l2_crop crop = {};

        crop.type = V4L2_BUF_TYPE_VIDEO_CAPTURE;

        if ( vidioctl(vid_fd, VIDIOC_G_CROP, &crop) < 0 ) {
          if ( errno != EINVAL ) {
            /* Failed querying crop sizes, write error to the log and continue as if crop is not supported */
            Error("Failed to query crop: %s", strerror(errno));
          }

          if ( verbose ) {
            output_ptr += sprintf(output_ptr, "  Cropping is not supported\n");
          } else {
            /* Send fake crop bounds to not confuse things parsing this, such as monitor probe */
            output_ptr += sprintf(output_ptr, "B:%dx%d|",0,0);
          }
        } else {
          /* Cropping supported */
          if ( verbose ) {
            output_ptr += sprintf(output_ptr,
                                  "  Bounds: %d x %d\n"
                                  "  Default: %d x %d\n"
                                  "  Current: %d x %d\n"
                                  , cropcap.bounds.width, cropcap.bounds.height
                                  , cropcap.defrect.width, cropcap.defrect.height
                                  , crop.c.width, crop.c.height);
          } else {
            output_ptr += sprintf(output_ptr, "B:%dx%d|", cropcap.bounds.width, cropcap.bounds.height);
          }
        }
      } /* Crop code */

      struct v4l2_input input;
      int inputIndex = 0;
      do {
        memset(&input, 0, sizeof(input));
        input.index = inputIndex;

        if ( vidioctl(vid_fd, VIDIOC_ENUMINPUT, &input) < 0 ) {
          if ( errno == EINVAL ) {
            break;
          }
          Error("Failed to enumerate input for %s %d: %d %s",
                device.c_str(), input.index, errno, strerror(errno));
          if ( verbose )
            output_ptr += sprintf(output_ptr, "Error, failed to enumerate input %d: %s\n", input.index, strerror(errno));
          else
            output_ptr += sprintf(output_ptr, "error%d\n", errno);
          return false;
        }
      } while ( inputIndex++ >= 0 );

      output_ptr += sprintf(output_ptr, verbose?"Inputs: %d\n":"I:%d|", inputIndex);

      inputIndex = 0;
      do {
        memset(&input, 0, sizeof(input));
        input.index = inputIndex;

        if ( vidioctl(vid_fd, VIDIOC_ENUMINPUT, &input) < 0 ) {
          if ( errno == EINVAL ) {
            inputIndex = -1;
            break;
          }
          Error("Failed to enumerate input %d: %s", input.index, strerror(errno));
          if ( verbose )
            output_ptr += sprintf(output_ptr, "Error, failed to enumerate input %d: %s\n", input.index, strerror(errno));
          else
            output_ptr += sprintf(output_ptr, "error%d\n", errno);
          return false;
        }

        if ( vidioctl(vid_fd, VIDIOC_S_INPUT, &input.index) < 0 ) {
          Error("Failed to set video input %d: %s", input.index, strerror(errno));
          if ( verbose )
            output_ptr += sprintf(output_ptr, "Error, failed to switch to input %d: %s\n", input.index, strerror(errno));
          else
            output_ptr += sprintf(output_ptr, "error%d\n", errno);
          return false;
        }

        if ( verbose ) {
          output_ptr += sprintf( output,
                                 "  Input %d\n"
                                 "    Name: %s\n"
                                 "    Type: %s\n"
                                 "    Audioset: %08x\n"
                                 "    Standards: 0x%" PRIx64"\n"
                                 , input.index
                                 , input.name
                                 , input.type==V4L2_INPUT_TYPE_TUNER?"Tuner":(input.type==V4L2_INPUT_TYPE_CAMERA?"Camera":"Unknown")
                                 , input.audioset
                                 , static_cast<uint64>(input.std));
        } else {
          output_ptr += sprintf( output_ptr, "i%d:%s|i%dT:%s|i%dS:%" PRIx64 "|"
                                 , input.index, input.name
                                 , input.index, input.type==V4L2_INPUT_TYPE_TUNER?"Tuner":(input.type==V4L2_INPUT_TYPE_CAMERA?"Camera":"Unknown")
                                 , input.index, static_cast<uint64>(input.std));
        }

        if ( verbose ) {
          output_ptr += sprintf( output_ptr, "    %s    %s    %s    %s"
                                 , capString(input.status&V4L2_IN_ST_NO_POWER, "Power ", "off", "on", " (X)")
                                 , capString(input.status&V4L2_IN_ST_NO_SIGNAL, "Signal ", "not detected", "detected", " (X)")
                                 , capString(input.status&V4L2_IN_ST_NO_COLOR, "Colour Signal ", "not detected", "detected", "")
                                 , capString(input.status&V4L2_IN_ST_NO_H_LOCK, "Horizontal Lock ", "not detected", "detected", ""));
        } else {
          output_ptr += sprintf( output_ptr, "i%dSP:%d|i%dSS:%d|i%dSC:%d|i%dHP:%d|"
                                 , input.index, (input.status&V4L2_IN_ST_NO_POWER)?0:1
                                 , input.index, (input.status&V4L2_IN_ST_NO_SIGNAL)?0:1
                                 , input.index, (input.status&V4L2_IN_ST_NO_COLOR)?0:1
                                 , input.index, (input.status&V4L2_IN_ST_NO_H_LOCK)?0:1 );
        }
      } while ( inputIndex++ >= 0 );
      if ( !verbose )
        *(output_ptr-1) = '\n';
    }

   v4l2_close(vid_fd);
    if (!device.empty()) {
      break;
    }
  } while ( ++devIndex < 32 );
  return true;
}

int LocalCamera::Control(int vid_id, int newvalue) {
  struct v4l2_control vid_control;

  memset(&vid_control, 0, sizeof(vid_control));
  vid_control.id = vid_id;

  if (vidioctl(vid_fd, VIDIOC_G_CTRL, &vid_control) < 0) {
    if (errno != EINVAL) {
      Error("Unable to query control: %s", strerror(errno));
    } else {
      Warning("Control is not supported");
    }
  } else if (newvalue >= 0) {
    vid_control.value = newvalue;

    /* The driver may clamp the value or return ERANGE, ignored here */
    if ( vidioctl(vid_fd, VIDIOC_S_CTRL, &vid_control) ) {
      if (errno != ERANGE) {
        Error("Unable to set control: %s", strerror(errno));
      } else {
        Warning("Given control value (%d) may be out-of-range", newvalue);
      }
    }
    v4l2_close( vid_fd );
  }
  return vid_control.value;
}

int LocalCamera::Brightness(int p_brightness) {
  return Control(V4L2_CID_BRIGHTNESS, p_brightness);
}

int LocalCamera::Hue(int p_hue) {
  return Control(V4L2_CID_HUE, p_hue);
}

int LocalCamera::Colour( int p_colour ) {
  return Control(V4L2_CID_SATURATION, p_colour);
}

int LocalCamera::Contrast(int p_contrast) {
  return Control(V4L2_CID_CONTRAST, p_contrast);
}

int LocalCamera::PrimeCapture() {
  getVideoStream();
  if (!device_prime)
    return 1;

  Debug(3, "Queueing (%d) buffers", v4l2_data.reqbufs.count);
  for (unsigned int frame = 0; frame < v4l2_data.reqbufs.count; frame++) {
    struct v4l2_buffer vid_buf;

    memset(&vid_buf, 0, sizeof(vid_buf));
    if (v4l2_data.fmt.type != V4L2_BUF_TYPE_VIDEO_CAPTURE) {
      Warning("Unknown type: (%d)", v4l2_data.fmt.type);
    }

    vid_buf.type = v4l2_data.fmt.type;
    vid_buf.memory = v4l2_data.reqbufs.memory;
    vid_buf.index = frame;

    if (vidioctl(vid_fd, VIDIOC_QBUF, &vid_buf) < 0) {
      Error("Failed to queue buffer %d: %s", frame, strerror(errno));
      return 0;
    }
  }
  v4l2_data.bufptr = nullptr;

  Debug(3, "Starting video stream");
  //enum v4l2_buf_type type = V4L2_BUF_TYPE_VIDEO_CAPTURE;
  //enum v4l2_buf_type type = v4l2_data.fmt.type;
  enum v4l2_buf_type type = (v4l2_buf_type)v4l2_data.fmt.type;
  if (vidioctl(vid_fd, VIDIOC_STREAMON, &type) < 0) {
    Error("Failed to start capture stream: %s", strerror(errno));
    return -1;
  }

  return 1;
} // end LocalCamera::PrimeCapture

int LocalCamera::PreCapture() {
  return 1;
}

int LocalCamera::Capture(std::shared_ptr<ZMPacket> &zm_packet) {
  // We assume that the avpacket is allocated, and just needs to be filled
  static uint8_t* buffer = nullptr;
  int buffer_bytesused = 0;
  int capture_frame = -1;

  int captures_per_frame = 1;
  if (channel_count > 1)
    captures_per_frame = v4l_captures_per_frame;
  if (captures_per_frame <= 0) {
    captures_per_frame = 1;
    Warning("Invalid Captures Per Frame setting: %d", captures_per_frame);
  }

  // Do the capture, unless we are the second or subsequent camera on a channel, in which case just reuse the buffer
  if (channel_prime) {
    static struct v4l2_buffer vid_buf;

    memset(&vid_buf, 0, sizeof(vid_buf));

    vid_buf.type = v4l2_data.fmt.type;
    vid_buf.memory = v4l2_data.reqbufs.memory;

    Debug(3, "Capturing %d frames", captures_per_frame);
    while (captures_per_frame) {
      if (vidioctl(vid_fd, VIDIOC_DQBUF, &vid_buf) < 0) {
        if (errno == EIO) {
          Warning("Capture failure, possible signal loss?: %s", strerror(errno));
        } else {
          Error("Unable to capture frame %d: %s", vid_buf.index, strerror(errno));
        }
        return -1;
      }
      Debug(5, "Captured a frame");

      v4l2_data.bufptr = &vid_buf;
      capture_frame = v4l2_data.bufptr->index;
      bytes += vid_buf.bytesused;

      if (--captures_per_frame) {
        if (vidioctl(vid_fd, VIDIOC_QBUF, &vid_buf) < 0) {
          Error("Unable to requeue buffer %d: %s", vid_buf.index, strerror(errno));
          return -1;
        }
      }
    } // while captures_per_frame

    Debug(3, "Captured frame %d/%d from channel %d", capture_frame, v4l2_data.bufptr->sequence, channel);

    buffer = (unsigned char *)v4l2_data.buffers[v4l2_data.bufptr->index].start;
    buffer_bytesused = v4l2_data.bufptr->bytesused;
    bytes += buffer_bytesused;

    if ((v4l2_data.fmt.fmt.pix.width * v4l2_data.fmt.fmt.pix.height) > (width * height)) {
      Fatal("Captured image dimensions larger than image buffer: V4L2: %dx%d monitor: %dx%d",
            v4l2_data.fmt.fmt.pix.width, v4l2_data.fmt.fmt.pix.height, width, height);
    } else if ((v4l2_data.fmt.fmt.pix.width * v4l2_data.fmt.fmt.pix.height) != (width * height)) {
      Error("Captured image dimensions differ: V4L2: %dx%d monitor: %dx%d",
            v4l2_data.fmt.fmt.pix.width, v4l2_data.fmt.fmt.pix.height, width, height);
    }

    if (channel_count > 1) {
      int next_channel = (channel_index+1)%channel_count;
      Debug(3, "Switching video source to %d", channels[next_channel]);
      if (vidioctl(vid_fd, VIDIOC_S_INPUT, &channels[next_channel]) < 0) {
        Error("Failed to set camera source %d: %s", channels[next_channel], strerror(errno));
        return -1;
      }

      v4l2_std_id stdId = standards[next_channel];
      if (vidioctl(vid_fd, VIDIOC_S_STD, &stdId) < 0) {
        Error("Failed to set video format %d: %s", standards[next_channel], strerror(errno));
      }
    }
    if (v4l2_data.bufptr) {
      Debug(3, "Requeueing buffer %d", v4l2_data.bufptr->index);
      if (vidioctl(vid_fd, VIDIOC_QBUF, v4l2_data.bufptr) < 0) {
        Error("Unable to requeue buffer %d: %s", v4l2_data.bufptr->index, strerror(errno));
        return -1;
      }
    } else {
      Error("Unable to requeue buffer due to not v4l2_data");
    }
  } /* prime capture */

  if (!zm_packet->image) {
    Debug(4, "Allocating image");
    zm_packet->image = new Image(width, height, colours, subpixelorder);
  }

  if (conversion_type != 0) {
    Debug(3, "Performing format conversion %d", conversion_type);

    /* Request a writeable buffer of the target image */
    uint8_t *directbuffer = zm_packet->image->WriteBuffer(width, height, colours, subpixelorder);
    if (directbuffer == nullptr) {
      Error("Failed requesting writeable buffer for the captured image.");
      return -1;
    }
    if (conversion_type == 1) {
      Debug(9, "Calling sws_scale to perform the conversion");
      /* Use swscale to convert the image directly into the shared memory */
      av_image_fill_arrays(tmpPicture->data,
                           tmpPicture->linesize, directbuffer,
                           imagePixFormat, width, height, 1);

      sws_scale(
        imgConversionContext,
        capturePictures[capture_frame]->data,
        capturePictures[capture_frame]->linesize,
        0,
        height,
        tmpPicture->data,
        tmpPicture->linesize
      );
    } else if (conversion_type == 2) {
      Debug(9, "Calling the conversion function");
      /* Call the image conversion function and convert directly into the shared memory */
      (*conversion_fptr)(buffer, directbuffer, pixels);
    } else if ( conversion_type == 3 ) {
      // Need to store the jpeg data too
      Debug(9, "Decoding the JPEG image");
      /* JPEG decoding */
      zm_packet->image->DecodeJpeg(buffer, buffer_bytesused, colours, subpixelorder);
    }
  } else {
    Debug(3, "No format conversion performed. Assigning the image");

    /* No conversion was performed, the image is in the V4L buffers and needs to be copied into the shared memory */
    zm_packet->image->Assign(width, height, colours, subpixelorder, buffer, imagesize);
  } // end if doing conversion or not

  zm_packet->packet->stream_index = mVideoStreamId;
  zm_packet->stream = mVideoStream;
  zm_packet->codec_type = AVMEDIA_TYPE_VIDEO;
  zm_packet->keyframe = 1;
  return 1;
} // end int LocalCamera::Capture()

int LocalCamera::PostCapture() {
  return 1;
}
#endif // ZM_HAS_V4L2
