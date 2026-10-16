#ifndef ZM_SWSCALE_H
#define ZM_SWSCALE_H

#include "zm_config.h"
#include "zm_ffmpeg.h"

class Image;

/* SWScale wrapper class to make our life easier and reduce code reuse */
class SWScale {
 public:
  SWScale();
  ~SWScale();
  bool init();
  int Convert(AVFrame *in_frame, AVFrame *out_frame);
  // A buffer's row alignment cannot be derived from its dimensions — the
  // caller must state how each buffer is laid out. ZM Image buffers are
  // always align-32 (av_image_* with align=32); raw device buffers (V4L2,
  // VNC framebuffers) are packed rows (alignment 1).
  int Convert(const Image* img, uint8_t* out_buffer, const size_t out_buffer_size, enum _AVPIXELFORMAT in_pf, enum _AVPIXELFORMAT out_pf, unsigned int width, unsigned int height, int out_alignment);
  int Convert(const uint8_t* in_buffer, const size_t in_buffer_size, uint8_t* out_buffer, const size_t out_buffer_size, enum _AVPIXELFORMAT in_pf, enum _AVPIXELFORMAT out_pf, unsigned int width, unsigned int height, int in_alignment, int out_alignment);
  int Convert(const uint8_t* in_buffer, const size_t in_buffer_size, uint8_t* out_buffer, const size_t out_buffer_size, enum _AVPIXELFORMAT in_pf, enum _AVPIXELFORMAT out_pf, unsigned int width, unsigned int height, unsigned int new_width, unsigned int new_height, int in_alignment, int out_alignment);
  static size_t GetBufferSize(enum _AVPIXELFORMAT in_pf, unsigned int width, unsigned int height, int alignment);

 protected:
  struct SwsContext* swscale_ctx;
  av_frame_ptr input_avframe;
  av_frame_ptr output_avframe;
};

#endif // ZM_SWSCALE_H
