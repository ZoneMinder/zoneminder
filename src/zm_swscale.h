#ifndef ZM_SWSCALE_H
#define ZM_SWSCALE_H

#include "zm_image.h"
#include "zm_ffmpeg.h"

/* SWScale wrapper class to make our life easier and reduce code reuse */
#if HAVE_LIBSWSCALE && HAVE_LIBAVUTIL
class SWScale {
  public:
    SWScale();
    ~SWScale();
    bool init();
    int SetDefaults(enum _AVPIXELFORMAT in_pf, enum _AVPIXELFORMAT out_pf, unsigned int width, unsigned int height);
    int ConvertDefaults(const Image* img, uint8_t* out_buffer, const size_t out_buffer_size);
    int ConvertDefaults(const uint8_t* in_buffer, const size_t in_buffer_size, uint8_t* out_buffer, const size_t out_buffer_size);
    int Convert(const Image* img, uint8_t* out_buffer, const size_t out_buffer_size, enum _AVPIXELFORMAT in_pf, enum _AVPIXELFORMAT out_pf, unsigned int width, unsigned int height);
    int Convert(const uint8_t* in_buffer, const size_t in_buffer_size, uint8_t* out_buffer, const size_t out_buffer_size, enum _AVPIXELFORMAT in_pf, enum _AVPIXELFORMAT out_pf, unsigned int width, unsigned int height);
    int Convert(const uint8_t* in_buffer, const size_t in_buffer_size, uint8_t* out_buffer, const size_t out_buffer_size, enum _AVPIXELFORMAT in_pf, enum _AVPIXELFORMAT out_pf, unsigned int width, unsigned int height, unsigned int new_width, unsigned int new_height);
    
  protected:
    bool gotdefaults;
    struct SwsContext* swscale_ctx;
    AVFrame* input_avframe;
    AVFrame* output_avframe;
    enum _AVPIXELFORMAT default_input_pf;
    enum _AVPIXELFORMAT default_output_pf;
    unsigned int default_width;
    unsigned int default_height;
};
#endif // HAVE_LIBSWSCALE && HAVE_LIBAVUTIL

#endif
