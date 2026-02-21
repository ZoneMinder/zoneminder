#include "zm_signal.h"
#include "zm_logger.h"
#include "zm_ffmpeg.h"

#include "zm_avfilter_worker.h"

filter_worker::filter_worker() :
  buffersink_ctx(nullptr),
  buffersrc_ctx(nullptr),
  filter_graph(nullptr),
  filter_ctx(nullptr),
  dec_ctx(nullptr),
  time_base(AV_TIME_BASE_Q),
  initialised(false)
{
}

filter_worker::~filter_worker() {
  if (filter_graph) {
    avfilter_graph_free(&filter_graph);
  }
}

bool filter_worker::setup(const std::string &filter_desc, const std::string &filter_of_interest, AVCodecContext *p_dec_ctx, AVRational p_time_base, AVBufferRef *hw_frames_ctx, AVPixelFormat pix_fmt) {
  dec_ctx = p_dec_ctx;
  time_base = p_time_base;

  int ret;
  Debug(1, "Trying %s", filter_desc.c_str());
  if ((ret = init_filter(filter_desc.c_str(), hw_frames_ctx, pix_fmt)) < 0) {
    Error("cannot initialize %s filter", filter_desc.c_str());
    return false;
  }

  if (!filter_of_interest.empty()) {
    for (unsigned int i = 0; i < filter_graph->nb_filters; i++) {
      if (strstr(filter_graph->filters[i]->name, filter_of_interest.c_str()) != nullptr) {
        filter_ctx = filter_graph->filters[i];
        break;
      }
    }

    if (filter_ctx == nullptr) {
      // Only filters that need later config need ctx
      Debug(1, "cannot find valid ctx for filter %s of interest %s", filter_desc.c_str(), filter_of_interest.c_str());
    }
  }

  return initialised = true;
} // end setup

int filter_worker::execute(AVFrame *in_frame, AVFrame **out_frame) {
  av_frame_ptr output{av_frame_alloc()};
  if (!output) {
    Error("cannot allocate output filter frame");
    return AVERROR(ENOMEM);
  }

  int ret = av_buffersrc_add_frame_flags(this->buffersrc_ctx, in_frame, AV_BUFFERSRC_FLAG_KEEP_REF);
  if (ret < 0) {
    Error("cannot add frame to %s buffer src %d %s",
        filter_ctx ? filter_ctx->name : "unknown", ret, av_make_error_string(ret).c_str());
    return ret;
  }

  int count = 10;
  do {
    ret = av_buffersink_get_frame(this->buffersink_ctx, output.get());
    if ((ret == AVERROR(EAGAIN)) and count) {
      count --;
      Debug(1, "EAGAIN %s", filter_ctx ? filter_ctx->name : "unknown");
    } else if (ret < 0) {
      Error("cannot get frame from %s buffer sink %d %s",
          filter_ctx ? filter_ctx->name : "unknown", ret, av_make_error_string(ret).c_str());
      return ret;
    } else {
      break;
    }
  } while (!zm_terminate);

  *out_frame = output.release();
  return 0;
}

int filter_worker::opt_set(const std::string &opt, const std::string &value) {
  return av_opt_set(filter_ctx->priv, opt.c_str(), value.c_str(), 0);
}

int filter_worker::opt_set(const std::string &opt, int value) {
  return av_opt_set(filter_ctx->priv, opt.c_str(), std::to_string(value).c_str(), 0);
}

int filter_worker::send_command(const char *filter_name, const char *command, const char *option) {
  int ret = avfilter_graph_send_command(filter_graph, filter_name, command, option, nullptr, 0, 0);
  if (ret < 0) {
    Error("cannot send drawbox filter command %s option %s, ret %d %s.",
        command, option, ret, av_make_error_string(ret).c_str());
  } else {
    Debug(1, "sent drawbox filter command %s option %s, ret %d.", command, option, ret);
  }
  return ret;
}

int filter_worker::init_filter(const char *filters_desc, AVBufferRef *hw_frames_ctx, AVPixelFormat input_fmt) {
  char args[512] = { 0 };
  char name[32] = { 0 };
  int ret = 0;
  AVFilterInOut *inputs = nullptr, *outputs = nullptr;

  filter_graph = avfilter_graph_alloc();
  if (!filter_graph) {
    Error("failed to allocate filter graph");
    return AVERROR(ENOMEM);
  }

  ret = avfilter_graph_parse2(filter_graph, filters_desc, &inputs, &outputs);
  if (ret < 0) {
    Error("failed to parse graph for %s", filters_desc);
    avfilter_inout_free(&inputs);
    avfilter_inout_free(&outputs);
    return ret;
  }

  // link input
  AVFilterInOut *cur = inputs;

  snprintf(name, sizeof(name), "in_0");
  snprintf(args, sizeof(args),
      "video_size=%dx%d:pix_fmt=%d:time_base=%d/%d:pixel_aspect=%d/%d:frame_rate=%d/%d",
      dec_ctx->width, dec_ctx->height, input_fmt, time_base.num, time_base.den,
      dec_ctx->sample_aspect_ratio.num, dec_ctx->sample_aspect_ratio.den,
      dec_ctx->framerate.num, dec_ctx->framerate.den);
  Debug(1, "Setting filter %s to %s", name, args);

  ret = avfilter_graph_create_filter(&buffersrc_ctx, avfilter_get_by_name("buffer"), name, args, nullptr, filter_graph);
  if (ret < 0) {
    Error("Cannot create buffer source: %s (args: %s)", av_make_error_string(ret).c_str(), args);
    avfilter_inout_free(&inputs);
    avfilter_inout_free(&outputs);
    return ret;
  }

  // decoder out=hw
  if (hw_frames_ctx) {
    Debug(1, "hw mode filter");
    // Allocate a new AVBufferSrcParameters instance when decoder out=hw
    AVBufferSrcParameters *par = av_buffersrc_parameters_alloc();
    if (!par) {
      Error("cannot allocate hwdl buffersrc parameters");
      avfilter_inout_free(&inputs);
      avfilter_inout_free(&outputs);
      return AVERROR(ENOMEM);
    }
    memset(par, 0, sizeof(*par));
    // set format and hw_frames_ctx to AVBufferSrcParameters when out=hw
    par->format = AV_PIX_FMT_NONE;
    par->hw_frames_ctx = hw_frames_ctx;
    // Initialize the buffersrc filter with the provided parameters
    ret = av_buffersrc_parameters_set(buffersrc_ctx, par);
    av_freep(&par);
    if (ret < 0) {
      avfilter_inout_free(&inputs);
      avfilter_inout_free(&outputs);
      return ret;
    }
  } else {
    Debug(1, "sw mode filter");
  }

  ret = avfilter_link(buffersrc_ctx, 0, cur->filter_ctx, cur->pad_idx);
  if (ret < 0) {
    Error("failed to link input filter");
    avfilter_inout_free(&inputs);
    avfilter_inout_free(&outputs);
    return ret;
  }

  cur = outputs;
  snprintf(name, sizeof(name), "out_0");
  ret = avfilter_graph_create_filter(&buffersink_ctx, avfilter_get_by_name("buffersink"), name, nullptr, nullptr, filter_graph);
  if (ret < 0) {
    Error("failed to create output filter");
    avfilter_inout_free(&inputs);
    avfilter_inout_free(&outputs);
    return ret;
  } else {
    Debug(1, "Success creating output filter");
  }

  ret = avfilter_link(cur->filter_ctx, cur->pad_idx, buffersink_ctx, 0);
  if (ret < 0) {
    Error("failed to link output filter");
    avfilter_inout_free(&inputs);
    avfilter_inout_free(&outputs);
    return ret;
  }

  // configure and validate the filter graph
  ret = avfilter_graph_config(filter_graph, nullptr);
  if (ret < 0) {
    Error("%s failed to config graph filter", __func__);
    avfilter_inout_free(&inputs);
    avfilter_inout_free(&outputs);
    return ret;
  } else {
    Debug(1, "%s success config graph filter %s", __func__, filters_desc);
  }

  avfilter_inout_free(&inputs);
  avfilter_inout_free(&outputs);
  return ret;
}
