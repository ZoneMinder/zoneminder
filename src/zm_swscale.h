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
  int SetDefaults(enum _AVPIXELFORMAT in_pf, enum _AVPIXELFORMAT out_pf, unsigned int width, unsigned int height);
  int ConvertDefaults(const Image* img, uint8_t* out_buffer, const size_t out_buffer_size);
  int ConvertDefaults(const uint8_t* in_buffer, const size_t in_buffer_size, uint8_t* out_buffer, const size_t out_buffer_size);
  int Convert( AVFrame *in_frame, AVFrame *out_frame );
  int Convert(const Image* img, uint8_t* out_buffer, const size_t out_buffer_size, enum _AVPIXELFORMAT in_pf, enum _AVPIXELFORMAT out_pf, unsigned int width, unsigned int height);
  int Convert(const uint8_t* in_buffer, const size_t in_buffer_size, uint8_t* out_buffer, const size_t out_buffer_size, enum _AVPIXELFORMAT in_pf, enum _AVPIXELFORMAT out_pf, unsigned int width, unsigned int height);
  int Convert(const uint8_t* in_buffer, const size_t in_buffer_size, uint8_t* out_buffer, const size_t out_buffer_size, enum _AVPIXELFORMAT in_pf, enum _AVPIXELFORMAT out_pf, unsigned int width, unsigned int height, unsigned int new_width, unsigned int new_height);
  static size_t GetBufferSize(enum _AVPIXELFORMAT in_pf, unsigned int width, unsigned int height);

 protected:
  bool gotdefaults;
  struct SwsContext* swscale_ctx;
  av_frame_ptr input_avframe;
  av_frame_ptr output_avframe;
  enum _AVPIXELFORMAT default_input_pf;
  enum _AVPIXELFORMAT default_output_pf;
  unsigned int default_width;
  unsigned int default_height;
};

#endif // ZM_SWSCALE_H
