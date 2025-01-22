
#include "zm_logger.h"
#include "zm_ffmpeg.h"

#include "zm_netint_yolo.h"

static const char *roi_class[] = {"person", "bicycle", "car", "motorcycle", "airplane", "bus", "train", "truck", "boat",
  "traffic light", "fire hydrant", "stop sign", "parking meter", "bench", "bird", "cat",
  "dog", "horse", "sheep", "cow", "elephant", "bear", "zebra", "giraffe", "backpack",
  "umbrella", "handbag", "tie", "suitcase", "frisbee", "skis", "snowboard", "sports ball",
  "kite", "baseball bat", "baseball glove", "skateboard", "surfboard", "tennis racket",
  "bottle", "wine glass", "cup", "fork", "knife", "spoon", "bowl", "banana", "apple",
  "sandwich", "orange", "broccoli", "carrot", "hot dog", "pizza", "donut", "cake", "chair",
  "couch", "potted plant", "bed", "dining table", "toilet", "tv", "laptop", "mouse", "remote",
  "keyboard", "cell phone", "microwave", "oven", "toaster", "sink", "refrigerator", "book",
  "clock", "vase", "scissors", "teddy bear", "hair drier", "toothbrush"};

AVRational qp_offset = { 0 , 0 };

Quadra_Yolo::Quadra_Yolo(Monitor *p_monitor) :
  monitor(p_monitor),
  model_width(640),
  model_height(640),
  model_format(GC620_RGB888_PLANAR),
  network_ctx(nullptr),
  model(nullptr),
  model_ctx(nullptr),
  network_data(nullptr),
  frame(nullptr),
  ai_frame(nullptr),
  scaled_frame({}),
  //swscale({}),
  sw_scale_ctx(nullptr),
  draw_box(true),
  drawbox_filter(nullptr),
  hwdl_filter(nullptr),
  drawbox_filter_ctx(nullptr),

  dec_stream(nullptr),
  dec_ctx(nullptr),

  aiframe_number(0),
  processed_frame(nullptr),
  last_roi(nullptr),
  last_roi_extra(nullptr),
  last_roi_count(0),

  use_hwframe(true)
{
  scaled_frame.width  = model_width;
  scaled_frame.height = model_height;
  scaled_frame.format = AV_PIX_FMT_RGB24;
}

Quadra_Yolo::~Quadra_Yolo() {
  if (model) {
    model->destroy_model(model_ctx);
    delete model_ctx;
  }
  ni_cleanup_network_context(network_ctx, use_hwframe);

  free(last_roi);
  free(last_roi_extra);

  av_frame_unref(&scaled_frame);
  sws_freeContext(sw_scale_ctx);

  if (draw_box) {
    avfilter_graph_free(&drawbox_filter->filter_graph);
    free(drawbox_filter);
  }
  avfilter_graph_free(&hwdl_filter->filter_graph);
  free(hwdl_filter);
}

bool Quadra_Yolo::setup(AVStream *p_dec_stream, AVCodecContext *decoder_ctx, const std::string &modelname, const std::string &nbg_file) {
  dec_stream = p_dec_stream;
  dec_ctx = decoder_ctx;
//model_ctx = (YoloModelCtx *)calloc(1, sizeof(YoloModelCtx));
  model_ctx = new YoloModelCtx;
  if (model_ctx == nullptr) {
    Error("failed to allocate yolo model");
    return false;
  }

  int ret = ni_alloc_network_context(&network_ctx, use_hwframe,
      0 /*dev_id*/, 30 /* keep alive */, model_format, model_width, model_height, nbg_file.c_str());
  if (ret != 0) {
    Error("failed to allocate network context");
    return false;
  }
  network_data = &network_ctx->network_data;

  if (modelname == "yolov4") {
      model = &yolov4;
  } else if (modelname == "yolov5") {
      model = &yolov5;
      model_width = model_height = 640;
  } else {
      Error("Unsupported yolo model");
      return false;
  }

  ret = model->create_model(model_ctx, network_data, obj_thresh, nms_thresh, model_width, model_height);
  if (ret != 0) {
    Error("failed to initialize yolov4 model");
    return false;
  }

  if (av_frame_get_buffer(&scaled_frame, 32)) {
    Error("cannot allocate scaled frame buffer");
    return false;
  }

#if 0
  if (!swscale.init()) {
    Error("Failed to init swscale");
    return false;
  }
#endif

  frame = new NiNetworkFrame();
  frame->scale_width = model_width;
  frame->scale_height = model_height;
  frame->scale_format = model_format;

  ret = ni_ai_packet_buffer_alloc(&frame->api_packet.data.packet, &network_ctx->network_data);
  if (ret != NI_RETCODE_SUCCESS) {
    Error( "failed to allocate packet");
    return false;
  }
  ret = ni_frame_buffer_alloc_hwenc(&frame->api_frame.data.frame,
      frame->scale_width,
      frame->scale_height, 0);
  if (ret != NI_RETCODE_SUCCESS) {
    Error("failed to allocate frame");
    return false;
  }

  if (use_hwframe == false) {
    scaled_frame.width  = model_width;
    scaled_frame.height = model_height;
    scaled_frame.format = AV_PIX_FMT_RGB24;

    if (av_frame_get_buffer(&scaled_frame, 32)) {
      Error("cannot allocate scaled frame buffer");
      return false;
    }
  }

  if (draw_box) {
    drawbox_filter = (filter_worker*)malloc(sizeof(filter_worker));
    drawbox_filter->buffersink_ctx = nullptr;
    drawbox_filter->buffersrc_ctx = nullptr;
    drawbox_filter->filter_graph = nullptr;
    if ((ret = init_filter("drawbox", drawbox_filter, false)) < 0) {
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
  } // end if draw_box

  hwdl_filter = (filter_worker*)malloc(sizeof(filter_worker));
  hwdl_filter->buffersink_ctx = nullptr;
  hwdl_filter->buffersrc_ctx = nullptr;
  hwdl_filter->filter_graph = nullptr;

  const char *hwdl_desc = "[in]hwdownload,format=yuv420p[out]";
  if ((ret = init_filter(hwdl_desc, hwdl_filter, true)) < 0) {
    Error("cannot initialize hwdl filter");
    return false;
  }

  return true;
}

bool Quadra_Yolo::detect(AVFrame *avframe, AVFrame **ai_frame) {
  if (!sw_scale_ctx) {
    sw_scale_ctx = sws_getContext(avframe->width, avframe->height, AV_PIX_FMT_YUV420P,
        scaled_frame.width, scaled_frame.height, static_cast<AVPixelFormat>(scaled_frame.format),
        SWS_BICUBIC, nullptr, nullptr, nullptr);
    if (!sw_scale_ctx) {
      Error("cannot create sw scale context for scaling");
      return false;
    }
  }

  ni_session_data_io_t ai_input_frame = {};

  Debug(1, "Quadra: generate_ai_frame");
  int ret = generate_ai_frame(&ai_input_frame, avframe, use_hwframe);
  if (ret < 0) {
    Error("Quadra: cannot generate ai frame");
    return false;
  }

  Debug(1, "Quadra: ni_set_network_input");
  ret = ni_set_network_input(network_ctx, use_hwframe, &ai_input_frame, nullptr,
      avframe->width, avframe->height, frame, true);
  if (ret != 0 && ret != NIERROR(EAGAIN)) {
    Error("Error while feeding the ai");
    return false;
  }

  /* pull filtered frames from the filtergraph */
  ret = ni_get_network_output(network_ctx, use_hwframe, frame, false /* blockable */,
      true /*convert*/, model_ctx->out_tensor);
  if (ret != 0 && ret != NIERROR(EAGAIN)) {
    Error("Error when getting output %d", ret);
    return false;
  } else if (ret != NIERROR(EAGAIN)) {
    ret = ni_read_roi(avframe, aiframe_number);
    if (ret < 0) {
      Error("read roi failed");
      return false;
    } else if (ret == 0) {
      Debug(1, "ni_read_roi == 0");
      return false;
    }
    aiframe_number++;
    ret = process_roi(avframe, ai_frame);
    if (ret < 0) {
      Error("cannot draw roi");
      return false;
    }
    AVFrame *blah = *ai_frame;
    zm_dump_video_frame(blah, "ai");
  } else {
    Debug(1, "EAGAIN");
    return false;
  }

  return true;
} // end detect

int Quadra_Yolo::ni_recreate_ai_frame(ni_frame_t *ni_frame, AVFrame *frame) {
  uint8_t *p_data = ni_frame->p_data[0];

  Debug(1,
      "linesize %d/%d/%d, data %p/%p/%p, pixel %dx%d",
      frame->linesize[0], frame->linesize[1], frame->linesize[2],
      frame->data[0], frame->data[1], frame->data[2], frame->width,
      frame->height);

  if (frame->format == AV_PIX_FMT_RGB24) {
    /* RGB24 -> BGRP */
    uint8_t *r_data = p_data + frame->width * frame->height * 2;
    uint8_t *g_data = p_data + frame->width * frame->height * 1;
    uint8_t *b_data = p_data + frame->width * frame->height * 0;
    uint8_t *fdata  = frame->data[0];
    int x, y;

    Debug(1, "%s(): rgb24 to bgrp, pix %dx%d, linesize %d", __func__,
        frame->width, frame->height, frame->linesize[0]);

    for (y = 0; y < frame->height; y++) {
      for (x = 0; x < frame->width; x++) {
        int fpos  = y * frame->linesize[0];
        int ppos  = y * frame->width;
        uint8_t r = fdata[fpos + x * 3 + 0];
        uint8_t g = fdata[fpos + x * 3 + 1];
        uint8_t b = fdata[fpos + x * 3 + 2];

        r_data[ppos + x] = r;
        g_data[ppos + x] = g;
        b_data[ppos + x] = b;
      }
    }
  } else {
    Error("cannot recreate frame: invalid frame format");
  }
  return 0;
}

int Quadra_Yolo::generate_ai_frame(ni_session_data_io_t *ai_frame, AVFrame *avframe, bool hwframe) {
  int ret = 0;

  if (hwframe == false) {
    ret = sws_scale(sw_scale_ctx, (const uint8_t * const *)avframe->data,
        avframe->linesize, 0, avframe->height, scaled_frame.data, scaled_frame.linesize);
    if (ret < 0) {
      Error("cannot do sw scale: inframe data 0x%lx, linesize %d/%d/%d/%d, height %d to %d linesize",
          (unsigned long)avframe->data, avframe->linesize[0], avframe->linesize[1],
          avframe->linesize[2], avframe->linesize[3], avframe->height, scaled_frame.linesize[0]);
      return ret;
    }
    ni_retcode_t retval = ni_ai_frame_buffer_alloc(&ai_frame->data.frame, &network_ctx->network_data);
    if (retval != NI_RETCODE_SUCCESS) {
      Error("cannot allocate sw ai frame buffer");
      return NIERROR(ENOMEM);
    }
    ret = ni_recreate_ai_frame(&ai_frame->data.frame, &scaled_frame);
    if (ret < 0) {
      Error("cannot recreate sw ai frame");
      return ret;
    }
  } else {
    ai_frame->data.frame.p_data[3] = avframe->data[3];
  }

  return ret;
}



int Quadra_Yolo::draw_roi_box(
    AVFrame *inframe,
    AVFrame **outframe,
    AVRegionOfInterest roi,
    AVRegionOfInterestNetintExtra roi_extra) {

    char drawbox_option[32];
    std::string color;
    int n, ret;
    int x, y, w, h;

    int cls = roi_extra.cls;
    if (cls == 0) {
        color = "Blue";
    } else {
        color = "Red";
    }
    float prob = roi_extra.prob;
    x = roi.left;
    y = roi.top;
    w = roi.right - roi.left;
    h = roi.bottom - roi.top;

    Debug(1, "x %d, y %d, w %d, h %d class %s prob %f",
            x, y, w, h, roi_class[cls], prob);

    n = snprintf(drawbox_option, sizeof(drawbox_option), "%d", x); drawbox_option[n] = '\0';
    av_opt_set(drawbox_filter_ctx->priv, "x", drawbox_option, 0);

    n = snprintf(drawbox_option, sizeof(drawbox_option), "%d", y); drawbox_option[n] = '\0';
    av_opt_set(drawbox_filter_ctx->priv, "y", drawbox_option, 0);

    n = snprintf(drawbox_option, sizeof(drawbox_option), "%d", w); drawbox_option[n] = '\0';
    av_opt_set(drawbox_filter_ctx->priv, "w", drawbox_option, 0);

    n = snprintf(drawbox_option, sizeof(drawbox_option), "%d", h); drawbox_option[n] = '\0';
    av_opt_set(drawbox_filter_ctx->priv, "h", drawbox_option, 0);

    ret = avfilter_graph_send_command(drawbox_filter->filter_graph, "drawbox", "color", color.c_str(), nullptr, 0, 0);
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
    } while (1);
    return 0;
}

int Quadra_Yolo::init_filter(const char *filters_desc, filter_worker *f, bool hwmode) {
    char args[512] = { 0 };
    char name[32] = { 0 };
    int i, ret = 0;
    AVFilterInOut *inputs, *outputs, *cur;
    AVBufferSrcParameters *par;
    enum AVPixelFormat input_fmt;
    if (hwmode) {
        input_fmt = dec_ctx->pix_fmt;
    } else {
        input_fmt = dec_ctx->sw_pix_fmt;
    }

    f->filter_graph = avfilter_graph_alloc();
    if (!f->filter_graph) {
        Error( "failed to allocate filter graph");
        return AVERROR(ENOMEM);
    }

    ret = avfilter_graph_parse2(f->filter_graph, filters_desc, &inputs, &outputs);
    if (ret < 0) {
        Error( "failed to parse graph");
        return ret;
    }

    // link input
    cur = inputs, i = 0;
    AVRational time_base = dec_stream->time_base;

    snprintf(name, sizeof(name), "in_%d", i);
    snprintf(args, sizeof(args),
        "video_size=%dx%d:pix_fmt=%d:time_base=%d/%d:pixel_aspect=%d/%d:frame_rate=%d/%d",
        dec_ctx->width, dec_ctx->height, input_fmt, time_base.num, time_base.den,
        dec_ctx->sample_aspect_ratio.num, dec_ctx->sample_aspect_ratio.den,dec_ctx->framerate.num,dec_ctx->framerate.den);
    Debug(1, "Setting filter %s to %s", name, args);

    ret = avfilter_graph_create_filter(&f->buffersrc_ctx, avfilter_get_by_name("buffer"), name,
                                    args, nullptr, f->filter_graph);
    if (ret < 0) {
        Error("Cannot create buffer source");
        return ret;
    }

    // decoder out=hw
    if (hwmode && (dec_ctx->hw_frames_ctx != nullptr)) {
        Debug(1, "hw mode filter");
        // Allocate a new AVBufferSrcParameters instance when decoder out=hw
        par = av_buffersrc_parameters_alloc();
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

int Quadra_Yolo::process_roi(AVFrame *frame, AVFrame **filt_frame) {
  int i, num, ret;
  Debug(1, "frame %p", frame);
  AVFrameSideData *sd = frame->side_data ? av_frame_get_side_data(frame, AV_FRAME_DATA_REGIONS_OF_INTEREST) : nullptr;
  Debug(1, "frame %p", frame);
  AVFrameSideData *sd_roi_extra = frame->side_data ? av_frame_get_side_data(
      frame, AV_FRAME_DATA_NETINT_REGIONS_OF_INTEREST_EXTRA) : nullptr;
  Debug(1, "frame %p", frame);
  AVFrame *input = nullptr;
  static int filt_cnt = 0;
  int detected = 0;

  Debug(1, "Filt %d frame pts %3" PRId64, ++filt_cnt, frame->pts);

  ret = dlhw_frame(frame, &input);
  if (ret < 0) {
    Error("cannot download hwframe");
    return ret;
  }
  Debug(1, "Have dlhw_frame");

  if (!sd || !sd_roi_extra || sd->size == 0 || sd_roi_extra->size == 0) {
    *filt_frame = input;
    if (*filt_frame == nullptr) {
      Error("cannot clone frame");
      return NIERROR(ENOMEM);
    }
    Debug(1, "no roi area in frame %d", filt_cnt);
    return 0;
  }

  Debug(1, "Have roi");
  AVRegionOfInterest *roi = (AVRegionOfInterest *)sd->data;
  AVRegionOfInterestNetintExtra *roi_extra = (AVRegionOfInterestNetintExtra *)sd_roi_extra->data;
  if ((sd->size % roi->self_size) ||
      (sd_roi_extra->size % roi_extra->self_size) ||
      (sd->size / roi->self_size !=
       sd_roi_extra->size / roi_extra->self_size)) {
    Error( "invalid roi side data");
    return ret = NIERROR(EINVAL);
  }
  num = sd->size / roi->self_size;

  char result[2048] = { 0 };
  snprintf(result, sizeof(result), "Predicts of frame %d\n", filt_cnt);

  for (i = 0; i < num; i++) {
    if (check_movement(roi[i], roi_extra[i])) {
      continue;
    }
    detected++;

    if (draw_box) {
      AVFrame *output = av_frame_alloc();
      if (!output) {
        Error("cannot allocate output filter frame");
        return NIERROR(ENOMEM);
      }

      ret = draw_roi_box(input, &output, roi[i], roi_extra[i]);
      if (ret < 0) {
        Error("draw %d roi box failed", i);
        return ret;
      }

      av_frame_free(&input);
      input = output;
    }
    snprintf(result + strlen(result), sizeof(result) - strlen(result),
        "   type:%s,left:%d,right:%d,top:%d,bottom:%d,prob:%f\n",
        roi_class[roi_extra[i].cls], roi[i].left, roi[i].right, roi[i].top,
        roi[i].bottom, roi_extra[i].prob);
  }
  *filt_frame = input;
  free(last_roi);
  free(last_roi_extra);
  AVRegionOfInterest* cur_roi = nullptr;
  AVRegionOfInterestNetintExtra *cur_roi_extra = nullptr;

  if (num > 0) {
    cur_roi = (AVRegionOfInterest *)av_malloc((int)(num * sizeof(AVRegionOfInterest)));
    cur_roi_extra = (AVRegionOfInterestNetintExtra *)av_malloc((int)(num * sizeof(AVRegionOfInterestNetintExtra)));
    for (i = 0; i < num; i++) {
      (cur_roi[i]).self_size = roi[i].self_size;
      (cur_roi[i]).top       = roi[i].top;
      (cur_roi[i]).bottom    = roi[i].bottom;
      (cur_roi[i]).left      = roi[i].left;
      (cur_roi[i]).right     = roi[i].right;
      (cur_roi[i]).qoffset   = qp_offset;
      cur_roi_extra[i].self_size = roi_extra[i].self_size;
      cur_roi_extra[i].cls       = roi_extra[i].cls;
      cur_roi_extra[i].prob      = roi_extra[i].prob;
    }
  }
  last_roi = cur_roi;
  last_roi_extra = cur_roi_extra;
  last_roi_count = num;

  return detected;
}

int Quadra_Yolo::dlhw_frame(AVFrame *hwframe, AVFrame **filt_frame) {
  int ret;
  AVFrame *output;

  output = av_frame_alloc();
  if (!output) {
    Error("cannot allocate output filter frame");
    return NIERROR(ENOMEM);
  }

  ret = av_buffersrc_add_frame_flags(hwdl_filter->buffersrc_ctx, hwframe, AV_BUFFERSRC_FLAG_KEEP_REF);
  if (ret < 0) {
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
      return ret;
    } else {
      break;
    }
  } while (1);

  *filt_frame = output;
  return 0;
}

int Quadra_Yolo::check_movement(
    AVRegionOfInterest cur_roi,
    AVRegionOfInterestNetintExtra cur_roi_extra)
{
    int i;
    for (i = 0; i < last_roi_count; i++) {
        if (last_roi_extra[i].cls == cur_roi_extra.cls) {
            if (abs(cur_roi.left - last_roi[i].left) < NI_SAME_BORDER_THRESH &&
                abs(cur_roi.right - last_roi[i].right) < NI_SAME_BORDER_THRESH &&
                abs(cur_roi.top - last_roi[i].top) < NI_SAME_BORDER_THRESH &&
                abs(cur_roi.bottom - last_roi[i].bottom) < NI_SAME_BORDER_THRESH &&
                abs((last_roi[i].left + last_roi[i].right) / 2 - (cur_roi.left + cur_roi.right) / 2) < NI_SAME_CENTER_THRESH &&
                abs((last_roi[i].top + last_roi[i].bottom) / 2 - (cur_roi.top + cur_roi.bottom) / 2) < NI_SAME_CENTER_THRESH) {
                    return 1;
                }
        }
    }
    return 0;
}

int Quadra_Yolo::ni_read_roi(AVFrame *out, int frame_count) {
  AVFrameSideData *sd;
  AVFrameSideData *sd_roi_extra;
  AVRegionOfInterest *roi;
  AVRegionOfInterestNetintExtra *roi_extra;
  struct roi_box *roi_box = nullptr;
  int roi_num = 0;
  int ret = 1;
  int i, j;
  int width = out->width;
  int height = out->height;

  ret = model->ni_get_boxes(model_ctx, width, height, &roi_box, &roi_num);
  if (ret < 0) {
    Error( "failed to get roi.");
    return ret;
  }
  ret = 1;

  if (roi_num == 0) {
    Debug(1, "no roi available");
    return 0;
  }
#if 0
  if (0) {
    pr_err("frame %d roi num %d\n", frame_count, roi_num);
    for (i = 0; i < roi_num; i++) {
      pr_err("frame count %d roi %d: top %d, bottom %d, left %d, right %d, class %d name %s prob %f\n",
          frame_count, i, roi_box[i].top, roi_box[i].bottom, roi_box[i].left,
          roi_box[i].right, roi_box[i].class, roi_class[roi_box[i].class], roi_box[i].prob);
    }
  }
  for (i = 0; i < roi_num; i++) {
    if (roi_box[i].ai_class == 0 || roi_box[i].ai_class == 7) {
      reserve_roi_num++;
    }
  }
  if (reserve_roi_num == 0) {
    Debug(1, "no reserve roi available");
    ret = 0;
    goto out;
  }
#endif
  sd = av_frame_new_side_data(out, AV_FRAME_DATA_REGIONS_OF_INTEREST,
      (int)(roi_num * sizeof(AVRegionOfInterest)));
  sd_roi_extra = av_frame_new_side_data(
      out, AV_FRAME_DATA_NETINT_REGIONS_OF_INTEREST_EXTRA,
      (int)(roi_num * sizeof(AVRegionOfInterestNetintExtra)));
  if (!sd || !sd_roi_extra) {
    ret = NIERROR(ENOMEM);
    Error("Failed to allocate roi sidedata %ld roi_num %d ret:%d", roi_num * sizeof(AVRegionOfInterestNetintExtra), roi_num, ret);
    Error("%d", out->nb_side_data);
    goto out;
  }

  roi = (AVRegionOfInterest *)sd->data;
  roi_extra = (AVRegionOfInterestNetintExtra *)sd_roi_extra->data;
  for (i = 0, j = 0; i < roi_num; i++) {
    //if (roi_box[i].ai_class == 0 || roi_box[i].ai_class == 7) {
      roi[j].self_size = sizeof(*roi);
      roi[j].top       = roi_box[i].top;
      roi[j].bottom    = roi_box[i].bottom;
      roi[j].left      = roi_box[i].left;
      roi[j].right     = roi_box[i].right;
      roi[j].qoffset   = qp_offset;
      roi_extra[j].self_size = sizeof(*roi_extra);
      roi_extra[j].cls       = roi_box[i].ai_class;
      roi_extra[j].prob      = roi_box[i].prob;
      Debug(1, "roi %d: top %d, bottom %d, left %d, right %d, class %d prob %f qpo %d/%d",
          j, roi[j].top, roi[j].bottom, roi[j].left, roi[j].right,
          roi_extra[j].cls, roi_extra[j].prob, roi[j].qoffset.num, roi[j].qoffset.den);
      j++;
    //}
  }

out:
  free(roi_box);
  return ret;
}


