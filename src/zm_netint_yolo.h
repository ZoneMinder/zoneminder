#ifndef ZM_QUADRA_YOLO_H
#define ZM_QUADRA_YOLO_H

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
    typedef struct _filter_worker {
      AVFilterContext *buffersink_ctx;
      AVFilterContext *buffersrc_ctx;
      AVFilterGraph *filter_graph;
    }filter_worker;

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
    ni_network_data_t *network_data;
    NiNetworkFrame frame;
    //ni_session_data_io_t *ai_frame;

    av_frame_ptr scaled_frame;
    //SWScale swscale;
    SwsContext *sw_scale_ctx;


    bool draw_box;
    filter_worker *drawbox_filter;
    filter_worker *hwdl_filter;
    AVFilterContext *drawbox_filter_ctx;

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
    int send_packet(std::shared_ptr<ZMPacket> in_packet);
    int  receive_detection(std::shared_ptr<ZMPacket> out_packet);
    int  detect(std::shared_ptr<ZMPacket>in_packet, std::shared_ptr<ZMPacket> out_packet);
    int draw_last_roi(std::shared_ptr<ZMPacket> packet);
    int init_filter(const char *filters_desc, filter_worker *f, bool hwmode, AVPixelFormat in_ipxfmt);
  private:
    int draw_roi_box(AVFrame *inframe, AVFrame **outframe, AVRegionOfInterest roi, AVRegionOfInterestNetintExtra roi_extra);
    int ni_recreate_ai_frame(ni_frame_t *ni_frame, AVFrame *frame);
    int generate_ai_frame(ni_session_data_io_t *ai_frame, AVFrame *avframe, bool hwframe);
    int process_roi(AVFrame *frame, AVFrame **filt_frame);
    int dlhw_frame(AVFrame *hwframe, AVFrame **filt_frame);
    int check_movement( AVRegionOfInterest cur_roi, AVRegionOfInterestNetintExtra cur_roi_extra);
    int ni_read_roi(AVFrame *out, int frame_count);
};

#endif
