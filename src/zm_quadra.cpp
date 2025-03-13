#include "config.h"

#if HAVE_QUADRA

#include "zm_logger.h"
#include "zm_ffmpeg.h"
#include "zm_quadra.h"
#include "zm_signal.h"
#include "zm_utils.h"
#include "zm_vector2.h"

Quadra::Quadra() :
  sw_scale_ctx(nullptr),
  drawbox_filter(nullptr),
  hwdl_filter(nullptr),
  drawbox_filter_ctx(nullptr)
{
  //scaled_frame = av_frame_ptr{zm_av_frame_alloc()};
  //scaled_frame->width  = model_width;
  //scaled_frame->height = model_height;
  //scaled_frame->format = AV_PIX_FMT_RGB24;
}

Quadra::~Quadra() {
}

bool Quadra::setup(int deviceid) {
  Debug(1, "Setup NETint Quadra  on %d, use hwframe %d", deviceid, use_hwframe);
  return true;
}

bool Quadra::setup_drawbox(AVPixelFormat pixfmt, int width, int height) {
  int ret;
  if (drawbox_filter) {
    Warning("filter_worker already defined");
    avfilter_graph_free(&drawbox_filter->filter_graph);
    free(drawbox_filter);
  }

  drawbox_filter = (filter_worker*)malloc(sizeof(filter_worker));
  drawbox_filter->buffersink_ctx = nullptr;
  drawbox_filter->buffersrc_ctx = nullptr;
  drawbox_filter->filter_graph = nullptr;
  if ((ret = init_filter("drawbox", drawbox_filter, false, width, height, pixfmt)) < 0) {
    Error("cannot initialize drawbox filter");
    return false;
  }

  for (unsigned int i = 0; i < drawbox_filter->filter_graph->nb_filters; i++) {
    if (strstr(drawbox_filter->filter_graph->filters[i]->name, "drawbox") != nullptr) {
      drawbox_filter_ctx = drawbox_filter->filter_graph->filters[i];
      break;
    }
  }

  if (drawbox_filter_ctx == nullptr) {
    Error( "cannot find valid drawbox filter");
    return false;
  }
  return true;
}  // end Quadra::setup_drawbox

#if 0
  hwdl_filter = (filter_worker*)malloc(sizeof(filter_worker));
  hwdl_filter->buffersink_ctx = nullptr;
  hwdl_filter->buffersrc_ctx = nullptr;
  hwdl_filter->filter_graph = nullptr;

  const char *hwdl_desc = "[in]hwdownload,format=yuv420p[out]";
  if ((ret = init_filter(hwdl_desc, hwdl_filter, true, dec_ctx->pix_fmt)) < 0) {
    Error("cannot initialize hwdl filter");
    return false;
  }
  return true;
}
#endif

int Quadra::draw_box(
    AVFrame *inframe,
    AVFrame **outframe,
    int x, int y, int w, int h,
    const std::string &colour
    ) {

  av_opt_set(drawbox_filter_ctx->priv, "x", stringtf("%d", x).c_str(), 0);
  av_opt_set(drawbox_filter_ctx->priv, "y", stringtf("%d", y).c_str(), 0);
  av_opt_set(drawbox_filter_ctx->priv, "w", stringtf("%d", w).c_str(), 0);
  av_opt_set(drawbox_filter_ctx->priv, "h", stringtf("%d", h).c_str(), 0);

  int ret = avfilter_graph_send_command(drawbox_filter->filter_graph, "drawbox", "color", colour.c_str(), nullptr, 0, 0);
  if (ret < 0) {
    Error("cannot send drawbox filter command, ret %d.", ret);
    return ret;
  }

  ret = av_buffersrc_add_frame_flags(drawbox_filter->buffersrc_ctx, inframe, AV_BUFFERSRC_FLAG_KEEP_REF);
  if (ret < 0) {
    Error("cannot add frame to drawbox buffer src %d", ret);
    return ret;
  }

  do {
    ret = av_buffersink_get_frame(drawbox_filter->buffersink_ctx, *outframe);
    if (ret == AVERROR(EAGAIN)) {
      continue;
    } else if (ret < 0) {
      Error("cannot get frame from drawbox buffer sink %d", ret);
      return ret;
    } else {
      break;
    }
  } while (!zm_terminate);
  return 0;
}  // end draw_box

int Quadra::init_filter(
    const char *filters_desc, filter_worker *f,
    bool hwmode,
    int width, int height, AVPixelFormat input_fmt
    ) {

  char args[512] = { 0 };
  char name[32] = { 0 };
  int i;
  AVFilterInOut *inputs, *outputs, *cur;

  f->filter_graph = avfilter_graph_alloc();
  if (!f->filter_graph) {
    Error( "failed to allocate filter graph");
    return AVERROR(ENOMEM);
  }

  int ret = avfilter_graph_parse2(f->filter_graph, filters_desc, &inputs, &outputs);
  if (ret < 0) {
    avfilter_graph_free(&f->filter_graph);
    Error( "failed to parse graph");
    return ret;
  }

  // link input
  cur = inputs, i = 0;
  AVRational time_base = AV_TIME_BASE_Q;

  snprintf(name, sizeof(name), "in_%d", i);
  snprintf(args, sizeof(args),
      "video_size=%dx%d:pix_fmt=%d:time_base=%d/%d:pixel_aspect=%d/%d:frame_rate=%d/%d",
      width, height, input_fmt, time_base.num, time_base.den,
      1, 1,
//sample_aspect_ratio.num, sample_aspect_ratio.den,
90000, 1
      //dec_ctx->framerate.num,dec_ctx->framerate.den
      );
  Debug(1, "Setting filter %s to %s", name, args);

  ret = avfilter_graph_create_filter(&f->buffersrc_ctx, avfilter_get_by_name("buffer"), name,
      args, nullptr, f->filter_graph);
  if (ret < 0) {
    Error("Cannot create buffer source");
    return ret;
  }

#if 0
  // decoder out=hw
  if (hwmode) {
    Debug(1, "hw mode filter");
    // Allocate a new AVBufferSrcParameters instance when decoder out=hw
    AVBufferSrcParameters *par = av_buffersrc_parameters_alloc();
    if (!par) {
      Error("cannot allocate hwdl buffersrc parameters");
      return ret = AVERROR(ENOMEM);
    }
    memset(par, 0, sizeof(*par));
    // set format and hw_frames_ctx to AVBufferSrcParameters when out=hw
    par->format = AV_PIX_FMT_NONE;
    par->hw_frames_ctx = dec_ctx->hw_frames_ctx;
    Debug(1, "hw_frames_ctx %p", par->hw_frames_ctx);
    // Initialize the buffersrc filter with the provided parameters
    ret = av_buffersrc_parameters_set(f->buffersrc_ctx, par);
    av_freep(&par);
    if (ret < 0)
      return ret;
  } else { // decoder out=sw
    Debug(1, "sw mode filter %d %p", hwmode, dec_ctx->hw_frames_ctx);
  }
#endif

  ret = avfilter_link(f->buffersrc_ctx, 0, cur->filter_ctx, cur->pad_idx);
  if (ret < 0) {
    Error("failed to link input filter");
    return ret;
  }

  cur = outputs, i = 0;
  snprintf(name, sizeof(name), "out_%d", i);
  ret = avfilter_graph_create_filter(&f->buffersink_ctx, avfilter_get_by_name("buffersink"),
      name, nullptr, nullptr, f->filter_graph);
  if (ret < 0) {
    Error("failed to create output filter: %d", i);
    return ret;
  }

  // connect  dst (index i) pads to one of buffer sink (index 0) pad
  ret = avfilter_link(cur->filter_ctx, cur->pad_idx, f->buffersink_ctx, 0);
  if (ret < 0) {
    Error("failed to link output filter: %d", i);
    return ret;
  }

  // configure and validate the filter graph
  ret = avfilter_graph_config(f->filter_graph, nullptr);
  if (ret < 0) {
    Error("%s failed to config graph filter", __func__);
    return ret;
  } else {
    Debug(1, "%s success config graph filter %s", __func__, filters_desc);
  }

  avfilter_inout_free(&inputs);
  avfilter_inout_free(&outputs);
  return ret;
}

int Quadra::dlhw_frame(AVFrame *hwframe, AVFrame **filt_frame) {
  int ret;
  AVFrame *output = av_frame_alloc();
  if (!output) {
    Error("cannot allocate output filter frame");
    return NIERROR(ENOMEM);
  }

  ret = av_buffersrc_add_frame_flags(hwdl_filter->buffersrc_ctx, hwframe, AV_BUFFERSRC_FLAG_KEEP_REF);
  if (ret < 0) {
    av_frame_free(&output);
    Error("cannot add frame to hwdl buffer src");
    return ret;
  }

  do {
    ret = av_buffersink_get_frame(hwdl_filter->buffersink_ctx, output);
    if (ret == AVERROR(EAGAIN)) {
      Debug(1, "EAGAIN");
      continue;
    } else if (ret < 0) {
      Error("cannot get frame from hwdl buffer sink");
      av_frame_free(&output);
      return ret;
    } else {
      break;
    }
  } while (!zm_terminate);

  *filt_frame = output;
  return 0;
}

#endif
