#ifndef ZM_QUADRA_YOLO_H
#define ZM_QUADRA_YOLO_H

#include "zm_signal.h"
extern "C" {
#include <libavcodec/avcodec.h>
#include <libavformat/avformat.h>

#include <libavfilter/buffersink.h>
#include <libavfilter/buffersrc.h>

#include <libavutil/frame.h>
#include <libavutil/opt.h>

#include <libswscale/swscale.h>
}

#include "yolo_model.h"
#include "netint_network.h"


#include <nlohmann/json.hpp>

#define NI_TRANSCODE_FRAME_NUM 3
#define NI_SAME_CENTER_THRESH 2
#define NI_SAME_BORDER_THRESH 8

class Monitor;

class Quadra_Yolo {
  public:
    class filter_worker {
      public:
      AVFilterContext *buffersink_ctx;
      AVFilterContext *buffersrc_ctx;
      AVFilterGraph *filter_graph;
      AVFilterContext *filter_ctx;
      bool initialised;

      filter_worker() :
        buffersink_ctx(nullptr),
        buffersrc_ctx(nullptr),
        filter_graph(nullptr),
        filter_ctx(nullptr),
        initialised(false)
      { 
      };
      ~filter_worker() {
        if (filter_graph) {
          avfilter_graph_free(&filter_graph);
        }
        filter_ctx = nullptr; // Something else will free it.
      };
      bool setup(Quadra_Yolo *quadra, const std::string &filter_desc, const std::string filter_of_interest, AVBufferRef *hw_frames_ctx, AVPixelFormat pix_fmt) {
        int ret;
        Debug(1, "Trying %s", filter_desc.c_str());
        if ((ret = quadra->init_filter(filter_desc.c_str(), this, hw_frames_ctx, pix_fmt)) < 0) {
          Error("cannot initialize %s filter", filter_desc.c_str());
          return false;
        }

        if (!filter_of_interest.empty()) {
          for (unsigned int i = 0; i < filter_graph->nb_filters; i++) {
            if (strstr(filter_graph->filters[i]->name, filter_of_interest.c_str()) != nullptr) {
              filter_ctx = filter_graph->filters[i];
              break;
            } else {
              Debug(1, "Didn't match %s != %s", filter_graph->filters[i]->name, filter_of_interest.c_str());
            }
          }

          if (filter_ctx == nullptr) {
            // Only filters that need later config need ctx
            Debug(1, "cannot find valid ctx for filter %s of interest %s", filter_desc.c_str(), filter_of_interest.c_str());
          }
        }

        return initialised = true;
      }; // end setup

      int execute(AVFrame *in_frame, AVFrame **out_frame) {
        AVFrame *output = av_frame_alloc();
        if (!output) {
          Error("cannot allocate output filter frame");
          return NIERROR(ENOMEM);
        }

        int ret = av_buffersrc_add_frame_flags(this->buffersrc_ctx, in_frame, AV_BUFFERSRC_FLAG_KEEP_REF);
        if (ret < 0) {
          av_frame_free(&output);
          Error("cannot add frame to hwdl buffer src");
          return ret;
        }

        do {
          ret = av_buffersink_get_frame(this->buffersink_ctx, output);
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

        *out_frame = output;
        return 0;
      };
      int opt_set(const std::string &opt, const std::string &value) {
        return av_opt_set(filter_ctx->priv, opt.c_str(), value.c_str(), 0);
      }
      int opt_set(const std::string &opt, int value) {
        return av_opt_set(filter_ctx->priv, opt.c_str(), stringtf("%d", value).c_str(), 0);
      }
    };

  private:
    Monitor *monitor;
    int model_width = 640;
    int model_height = 640;
    int model_format;
    float obj_thresh = 0.25;
    float nms_thresh = 0.45;
    NiNetworkContext *network_ctx;
    YoloModel *model;
    YoloModelCtx *model_ctx;
    NiNetworkFrame net_frame;
    //ni_session_data_io_t *ai_frame;

    av_frame_ptr scaled_frame;
    //SWScale swscale;
    SwsContext *sw_scale_ctx;

    filter_worker hwdl_filter;

    bool drawbox;
    filter_worker drawbox_filter;

    bool drawtext;
    filter_worker drawtext_filter;
    // Needed for format conversion
    filter_worker scale_to_rgba_filter;
    filter_worker scale_to_yuv420p_filter;

    AVStream *dec_stream;
    AVCodecContext *dec_ctx;

    int aiframe_number;
    AVRegionOfInterest *last_roi;
    AVRegionOfInterestNetintExtra *last_roi_extra;
    int last_roi_count;

    bool use_hwframe;
    nlohmann::json detections;

    int filt_cnt;
  public:
    Quadra_Yolo(Monitor *p_monitor, bool p_use_hwframe);
    ~Quadra_Yolo();
    bool  setup(AVStream *p_dec_stream, AVCodecContext *decoder_ctx, const std::string &model_name="", const std::string &nbg_file="", int deviceid=-1);
    bool setup_drawbox();
    bool setup_drawtext();
    int send_packet(std::shared_ptr<ZMPacket> in_packet);
    int receive_detection(std::shared_ptr<ZMPacket> out_packet);
    int detect(std::shared_ptr<ZMPacket>in_packet, std::shared_ptr<ZMPacket> out_packet);
    int draw_last_roi(std::shared_ptr<ZMPacket> packet);
    int init_filter(const char *filters_desc, filter_worker *f, AVBufferRef * 	hw_frames_ctx, AVPixelFormat in_ipxfmt);
    int draw_text(AVFrame *input, AVFrame **output, const std::string &text, int x, int y, const std::string &colour);
  private:
    int draw_roi_box(AVFrame *inframe, AVFrame **outframe, AVRegionOfInterest roi, AVRegionOfInterestNetintExtra roi_extra, int line_width);
    int ni_recreate_ai_frame(ni_frame_t *ni_frame, AVFrame *frame);
    int generate_ai_frame(ni_session_data_io_t *ai_frame, AVFrame *avframe, bool hwframe);
    int process_roi(AVFrame *frame, AVFrame **filt_frame);
    int check_movement( AVRegionOfInterest cur_roi, AVRegionOfInterestNetintExtra cur_roi_extra);
    int ni_read_roi(AVFrame *out, int frame_count);
};

#endif
