#ifndef ZM_QUADRA_H
#define ZM_QUADRA_H

#include "nierrno.h"
#include "ni_device_api.h"
#include "ni_util.h"

extern "C" {
#include <libavcodec/avcodec.h>
#include <libavformat/avformat.h>
#include <libavfilter/buffersink.h>
#include <libavfilter/buffersrc.h>
#include <libavutil/opt.h>
#include <libswscale/swscale.h>
}

class Quadra {
  public:
    typedef struct _filter_worker {
      AVFilterContext *buffersink_ctx;
      AVFilterContext *buffersrc_ctx;
      AVFilterGraph *filter_graph;
    } filter_worker;

  private:
    av_frame_ptr scaled_frame;
    //SWScale swscale;
    SwsContext *sw_scale_ctx;

    filter_worker *drawbox_filter;
    filter_worker *hwdl_filter;
    AVFilterContext *drawbox_filter_ctx;

    bool use_hwframe;

  public:
    Quadra();
    ~Quadra();
    bool  setup(int deviceid=-1);
    bool  setup_drawbox(AVPixelFormat pixfmt, int width, int height);
    int   init_filter(const char *filters_desc, filter_worker *f, bool hwmode, int, int, AVPixelFormat in_ipxfmt);
    int draw_box(AVFrame *inframe, AVFrame **outframe, int x, int y, int w, int h, const std::string &colour);
  private:
    int dlhw_frame(AVFrame *hwframe, AVFrame **filt_frame);
};

#endif
